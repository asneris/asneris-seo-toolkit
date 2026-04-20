<?php
if (!defined('ABSPATH')) exit;

class ASNERISSEO_Redirects {
  
  const OPTION_KEY = 'ASNERISSEO_redirects';
  
  /**
   * Initialize redirects hooks
   */
  public static function init() {
    add_action('template_redirect', [__CLASS__, 'handle_redirects'], 1);
    add_action('post_updated', [__CLASS__, 'track_slug_change'], 10, 3);
  }

  /**
   * Enqueue admin assets for redirects page
   */
  public static function enqueue_assets($hook) {
    // WordPress uses sanitized menu TITLE (not slug) as parent identifier
    if ($hook !== 'asneris-seo-toolkit_page_' . ASNERIS_MENU_SLUG . '-redirects') return;
    wp_enqueue_style('asnerisseo-admin', ASNERISSEO_URL . 'assets/css/admin-style.css', [], ASNERISSEO_VERSION);
    wp_enqueue_script('jquery');
    $inline_js = "(function(){\n" .
      "  const descriptions = {\n" .
      "    '301': '" . esc_js(__('Use 301 when the old page is permanently replaced by the new page.', 'asneris-seo-toolkit')) . "',\n" .
      "    '302': '" . esc_js(__('Use 302 when the redirect is temporary and the original URL may return.', 'asneris-seo-toolkit')) . "',\n" .
      "    '307': '" . esc_js(__('Use 307 for temporary redirects that preserve the HTTP method (POST stays POST).', 'asneris-seo-toolkit')) . "'\n" .
      "  };\n" .
      "  const select = document.getElementById('code');\n" .
      "  const description = document.getElementById('redirect-type-description');\n" .
      "  if (!select || !description) return;\n" .
      "  select.addEventListener('change', function(){\n" .
      "    description.textContent = descriptions[this.value] || '';\n" .
      "  });\n" .
      "})();";
    wp_add_inline_script('jquery', $inline_js);
  }
  
  /**
   * Track post slug changes and create automatic redirects
   */
  public static function track_slug_change($post_id, $post_after, $post_before) {
    // Only for public post types
    if (!is_post_type_viewable($post_after->post_type)) {
      return;
    }
    
    // Check if slug changed
    if ($post_before->post_name === $post_after->post_name) {
      return;
    }
    
    // Don't redirect for drafts or auto-saves
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
      return;
    }
    
    if ($post_after->post_status !== 'publish') {
      return;
    }
    
    // Build stable old/new paths from the current permalink and slug delta.
    $current_permalink = get_permalink($post_id);
    if (!$current_permalink) {
      return;
    }

    $parsed_current = wp_parse_url($current_permalink);
    $new_path = isset($parsed_current['path']) ? $parsed_current['path'] : '';
    if ($new_path === '') {
      return;
    }

    $old_slug = sanitize_title($post_before->post_name);
    $new_slug = sanitize_title($post_after->post_name);
    $normalized_new_path = untrailingslashit($new_path);
    $segments = explode('/', ltrim($normalized_new_path, '/'));
    if (!empty($segments) && end($segments) === $new_slug) {
      $segments[count($segments) - 1] = $old_slug;
    }

    $old_url = '/' . implode('/', array_filter($segments, 'strlen'));
    $new_url = $new_path;
    
    if ($old_url === $new_url) {
      return;
    }
    
    // Add redirect
    self::add_redirect($old_url, $new_url, 301, 'auto');
  }
  
  /**
   * Handle redirects
   * 
   * Performance: Uses static hash-map cache for O(1) lookups instead of O(n) foreach.
   * Cache is built once per request from enabled redirects.
   */
  public static function handle_redirects() {
    if (!isset($_SERVER['REQUEST_URI'])) return;
    
    static $redirect_map = null;
    static $redirect_map_with_query = null;
    
    // Build hash-maps on first call (O(n) once, then O(1) lookups)
    if ($redirect_map === null) {
      $redirect_map = [];
      $redirect_map_with_query = [];
      
      $redirects = self::get_redirects();
      foreach ($redirects as $redirect) {
        if (!$redirect['enabled']) {
          continue;
        }
        
        $from = $redirect['from'];
        $from_parsed = wp_parse_url($from);
        $from_path = self::normalize_redirect_path($from);
        $from_query = isset($from_parsed['query']) ? self::normalize_query_string($from_parsed['query']) : '';
        
        if (!empty($from_query)) {
          // Store redirects with query parameters separately
          $key = $from_path . '?' . $from_query;
          $redirect_map_with_query[$key] = [
            'to' => $redirect['to'],
            'code' => (int)$redirect['code']
          ];
        } else {
          // Store path-only redirects
          $redirect_map[$from_path] = [
            'to' => $redirect['to'],
            'code' => (int)$redirect['code']
          ];
        }
      }
    }
    
    $request_uri = isset($_SERVER['REQUEST_URI'])
      ? wp_kses_no_null(wp_strip_all_tags(wp_unslash($_SERVER['REQUEST_URI'])))
      : '/';
    $request_path = wp_parse_url($request_uri, PHP_URL_PATH);
    $query_string = isset($_SERVER['QUERY_STRING'])
      ? wp_kses_no_null(wp_strip_all_tags(wp_unslash($_SERVER['QUERY_STRING'])))
      : '';
    $query_string = self::normalize_query_string($query_string);
    
    // Normalize request path consistently
    $normalized_path = self::normalize_redirect_path($request_path ?: '');
    
    // O(1) lookup: Check query-based redirects first
    if (!empty($query_string)) {
      $full_request = $normalized_path . '?' . $query_string;
      if (isset($redirect_map_with_query[$full_request])) {
        $match = $redirect_map_with_query[$full_request];
        $to = $match['to'];
        if (!preg_match('/^https?:\/\//', $to)) {
          $to = home_url($to);
        }
        wp_safe_redirect($to, $match['code']);
        exit;
      }
    }
    
    // O(1) lookup: Check path-only redirects
    if (isset($redirect_map[$normalized_path])) {
      $match = $redirect_map[$normalized_path];
      $to = $match['to'];
      if (!preg_match('/^https?:\/\//', $to)) {
        $to = home_url($to);
      }
      wp_safe_redirect($to, $match['code']);
      exit;
    }
  }
  
  /**
   * Normalize redirect path by removing trailing slash (except root /)
   * 
   * @param string $path The path to normalize
   * @return string Normalized path
   */
  private static function normalize_redirect_path($path) {
    $parsed = wp_parse_url($path);
    $normalized_path = isset($parsed['path']) ? sanitize_text_field($parsed['path']) : '';

    // Prevent path traversal attacks
    if (strpos($normalized_path, '..') !== false) {
      return '/';
    }

    // Normalize empty path
    if ($normalized_path === '') {
      return '/';
    }

    // Remove trailing slash except for root path
    if ($normalized_path !== '/' && substr($normalized_path, -1) === '/') {
      $normalized_path = rtrim($normalized_path, '/');
    }

    return $normalized_path;
  }

  /**
   * Normalize query string so parameter order doesn't affect redirect matching.
   */
  private static function normalize_query_string($query) {
    if (!is_string($query) || $query === '') {
      return '';
    }

    parse_str($query, $params);

    if (!is_array($params) || empty($params)) {
      return '';
    }

    // Sanitize keys (WordPress-safe alphanumeric + underscores)
    $params = array_combine(
      array_map('sanitize_key', array_keys($params)),
      $params
    );

    // Sanitize values recursively (handles nested arrays)
    array_walk_recursive($params, function (&$value) {
      $value = sanitize_text_field($value);
    });

    self::ksort_recursive($params);

    return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
  }

  /**
   * Recursively sort arrays by key for deterministic query string normalization.
   */
  private static function ksort_recursive(&$array) {
    if (!is_array($array)) {
      return;
    }

    foreach ($array as &$value) {
      if (is_array($value)) {
        self::ksort_recursive($value);
      }
    }
    ksort($array);
  }

  /**
   * Normalize and validate redirect source path (+ optional query).
   */
  private static function sanitize_redirect_source($from) {
    $from = trim((string) $from);
    if ($from === '') {
      return '';
    }

    if (preg_match('/^https?:\/\//i', $from)) {
      if (!wp_http_validate_url($from)) {
        return '';
      }
      $parsed = wp_parse_url($from);
      
      // Security: Reject cross-domain redirect sources (only allow same-site redirects)
      if (isset($parsed['host'])) {
        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
        if (strtolower($parsed['host']) !== strtolower($site_host)) {
          return '';
        }
      }
    } else {
      $from = '/' . ltrim($from, '/');
      $parsed = wp_parse_url($from);
    }

    $path = isset($parsed['path']) ? self::normalize_redirect_path($parsed['path']) : '/';
    if ($path === '') {
      $path = '/';
    }

    $query = isset($parsed['query']) ? self::normalize_query_string($parsed['query']) : '';
    return $query !== '' ? ($path . '?' . $query) : $path;
  }

  /**
   * Normalize and validate redirect target.
   * Allows internal relative paths or absolute HTTP(S) URLs.
   */
  private static function sanitize_redirect_target($to) {
    $to = trim((string) $to);
    if ($to === '') {
      return '';
    }

    if (preg_match('/^(?:javascript|data|vbscript):/i', $to) || strpos($to, '//') === 0) {
      return '';
    }

    if (preg_match('/^https?:\/\//i', $to)) {
      $to = esc_url_raw($to);
      if (!$to || !wp_http_validate_url($to)) {
        return '';
      }
      
      // Security: Prevent open redirect attacks - only allow same-host redirects
      $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
      $target_host = wp_parse_url($to, PHP_URL_HOST);
      
      if ($target_host && strtolower($target_host) !== strtolower($site_host)) {
        return '';
      }
      
      return $to;
    }

    $to = '/' . ltrim($to, '/');
    $parsed = wp_parse_url($to);
    $path = isset($parsed['path']) ? self::normalize_redirect_path($parsed['path']) : '/';
    if ($path === '') {
      $path = '/';
    }
    $query = isset($parsed['query']) ? self::normalize_query_string($parsed['query']) : '';

    return $query !== '' ? ($path . '?' . $query) : $path;
  }
  
  /**
   * Get all redirects
   */
  public static function get_redirects() {
    $redirects = get_option(self::OPTION_KEY, []);
    return is_array($redirects) ? $redirects : [];
  }
  
  /**
   * Add a redirect
   */
  public static function add_redirect($from, $to, $code = 301, $type = 'manual') {
    $redirects = self::get_redirects();

    $from = self::sanitize_redirect_source($from);
    $to = self::sanitize_redirect_target($to);
    if ($from === '' || $to === '') {
      return false;
    }
    
    // Enforce 500 redirect limit to prevent performance issues
    if (count($redirects) >= 500) {
      if (defined('DOING_AJAX') && DOING_AJAX) {
        wp_send_json_error(['message' => 'Redirect limit reached (500 maximum). Please delete some redirects before adding new ones.']);
      }
      return false;
    }
    
    // Normalize 'from' path to prevent duplicate redirects with/without trailing slash
    $from_normalized = $from;
    
    // Remove existing redirect with same "from"
    $redirects = array_filter($redirects, function($r) use ($from_normalized) {
      $r_normalized = strpos($r['from'], '?') === false ? self::normalize_redirect_path($r['from']) : $r['from'];
      return $r_normalized !== $from_normalized;
    });
    
    // Add new redirect (store normalized path)
    $redirects[] = [
      'from' => $from_normalized,
      'to' => $to,
      'code' => $code,
      'type' => $type,  // 'manual' or 'auto'
      'enabled' => true,
      'created' => current_time('mysql'),
    ];
    
    return update_option(self::OPTION_KEY, $redirects);
  }
  
  /**
   * Update a redirect
   */
  public static function update_redirect($index, $from, $to, $code = 301, $enabled = true) {
    $redirects = self::get_redirects();
    
    if (!isset($redirects[$index])) {
      return false;
    }
    
    $from = self::sanitize_redirect_source($from);
    $to = self::sanitize_redirect_target($to);
    if ($from === '' || $to === '') {
      return false;
    }

    $redirects[$index]['from'] = $from;
    $redirects[$index]['to'] = $to;
    $redirects[$index]['code'] = $code;
    $redirects[$index]['enabled'] = $enabled;
    
    return update_option(self::OPTION_KEY, $redirects);
  }
  
  /**
   * Delete a redirect
   */
  public static function delete_redirect($index) {
    $redirects = self::get_redirects();
    
    if (!isset($redirects[$index])) {
      return false;
    }
    
    unset($redirects[$index]);
    $redirects = array_values($redirects); // Re-index
    
    return update_option(self::OPTION_KEY, $redirects);
  }
  
  /**
   * Toggle redirect status
   */
  public static function toggle_redirect($index) {
    $redirects = self::get_redirects();
    
    if (!isset($redirects[$index])) {
      return false;
    }
    
    $redirects[$index]['enabled'] = !$redirects[$index]['enabled'];
    
    return update_option(self::OPTION_KEY, $redirects);
  }
  
  /**
   * Clear all automatic redirects
   */
  public static function clear_auto_redirects() {
    $redirects = self::get_redirects();
    
    $redirects = array_filter($redirects, function($r) {
      return $r['type'] !== 'auto';
    });
    
    return update_option(self::OPTION_KEY, array_values($redirects));
  }
  
  /**
   * Register admin page
   */
  public static function register_menu() {
    add_submenu_page(
      ASNERIS_MENU_SLUG,
      __('Redirect', 'asneris-seo-toolkit'),
      __('Redirect', 'asneris-seo-toolkit'),
      'manage_options',
      ASNERIS_MENU_SLUG . '-redirects',
      [__CLASS__, 'render_page']
    );
  }
  
  /**
   * Enqueue admin styles
   */
  /**
   * Render redirects management page
   */
  public static function render_page() {
    // Handle form submissions
    if (isset($_POST['ASNERISSEO_add_redirect']) && check_admin_referer('ASNERISSEO_redirect_add')) {
      $from = isset($_POST['from']) ? sanitize_text_field(wp_unslash($_POST['from'])) : '';
      $to = isset($_POST['to']) ? sanitize_url(wp_unslash($_POST['to'])) : '';
      $code = isset($_POST['code']) ? (int) $_POST['code'] : 301;
      $code = in_array($code, [301, 302, 307], true) ? $code : 301;
      
      // Strip domain from source URL; target can be relative or absolute HTTP(S).
      $from = str_replace(home_url(), '', $from);
      
      if (!empty($from) && !empty($to)) {
        if (self::add_redirect($from, $to, $code, 'manual')) {
          echo '<div class="notice notice-success"><p>' . esc_html__('Redirect added successfully!', 'asneris-seo-toolkit') . '</p></div>';
        } else {
          echo '<div class="notice notice-error"><p>' . esc_html__('Invalid redirect source or target URL.', 'asneris-seo-toolkit') . '</p></div>';
        }
      }
    }
    
    if ( isset( $_GET['action'] ) && sanitize_key( wp_unslash( $_GET['action'] ) ) === 'delete' && isset( $_GET['index'] ) ) {
      $index = (int) sanitize_text_field( wp_unslash( $_GET['index'] ) );
      check_admin_referer( 'ASNERISSEO_redirect_delete_' . $index );
      self::delete_redirect( $index );
      echo '<div class="notice notice-success"><p>' . esc_html__('Redirect deleted!', 'asneris-seo-toolkit') . '</p></div>';
    }
    
    if ( isset( $_GET['action'] ) && sanitize_key( wp_unslash( $_GET['action'] ) ) === 'toggle' && isset( $_GET['index'] ) ) {
      $index = (int) sanitize_text_field( wp_unslash( $_GET['index'] ) );
      check_admin_referer( 'ASNERISSEO_redirect_toggle_' . $index );
      self::toggle_redirect( $index );
      echo '<div class="notice notice-success"><p>' . esc_html__('Redirect status updated!', 'asneris-seo-toolkit') . '</p></div>';
    }
    
    if (isset($_POST['ASNERISSEO_clear_auto']) && check_admin_referer('ASNERISSEO_clear_auto')) {
      self::clear_auto_redirects();
      echo '<div class="notice notice-success"><p>' . esc_html__('Automatic redirects cleared!', 'asneris-seo-toolkit') . '</p></div>';
    }
    
    $redirects = self::get_redirects();
    ?>
    <div class="wrap ASNERISSEO-admin-wrap">
      <h1>
        <span class="dashicons dashicons-controls-forward"></span>
        <?php esc_html_e('SEO Redirects', 'asneris-seo-toolkit'); ?>
        <?php ASNERISSEO_Help_Modal::render_help_icon('redirects-overview', 'Learn about redirects'); ?>
      </h1>
      <p class="ASNERISSEO-subtitle"><?php esc_html_e('Send visitors and search engines to the right page when a URL changes.', 'asneris-seo-toolkit'); ?></p>
      
      <div class="ASNERISSEO-settings-form">
        <div class="ASNERISSEO-tab-content">
      
      <!-- Add New Redirect -->
      <div class="ASNERISSEO-card">
        <h2><span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Add New Redirect', 'asneris-seo-toolkit'); ?></h2>
        <form method="post" action="">
          <?php wp_nonce_field('ASNERISSEO_redirect_add'); ?>
          <table class="form-table">
            <tr>
              <th scope="row">
                <label for="from">
                  <?php esc_html_e('From (Old URL)', 'asneris-seo-toolkit'); ?>
                  <?php ASNERISSEO_Help_Modal::render_help_icon('from-url'); ?>
                </label>
              </th>
              <td>
                <input type="text" id="from" name="from" class="regular-text" placeholder="/?page_id=2 or /old-page/" required>
                <p class="description"><?php esc_html_e('The old page address (path or query string). Examples: /old-page/ or /?page_id=2', 'asneris-seo-toolkit'); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="to">
                  <?php esc_html_e('To (New URL)', 'asneris-seo-toolkit'); ?>
                  <?php ASNERISSEO_Help_Modal::render_help_icon('to-url'); ?>
                </label>
              </th>
              <td>
                <input type="text" id="to" name="to" class="regular-text" placeholder="/?page_id=10 or /new-page/" required>
                <p class="description"><?php esc_html_e('The destination page. Examples: /new-page/ or /?page_id=10 or https://example.com/page/', 'asneris-seo-toolkit'); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="code">
                  <?php esc_html_e('Redirect Type', 'asneris-seo-toolkit'); ?>
                  <?php ASNERISSEO_Help_Modal::render_help_icon('redirect-types'); ?>
                </label>
              </th>
              <td>
                <select id="code" name="code">
                  <option value="301"><?php esc_html_e('301 Permanent', 'asneris-seo-toolkit'); ?></option>
                  <option value="302"><?php esc_html_e('302 Temporary', 'asneris-seo-toolkit'); ?></option>
                  <option value="307"><?php esc_html_e('307 Temporary (Preserve Method)', 'asneris-seo-toolkit'); ?></option>
                </select>
                <p class="description" id="redirect-type-description"><?php esc_html_e('Use 301 when the old page is permanently replaced by the new page.', 'asneris-seo-toolkit'); ?></p>
              </td>
            </tr>
          </table>
          <button type="submit" name="ASNERISSEO_add_redirect" class="button button-primary">
            <?php esc_html_e('Add Redirect', 'asneris-seo-toolkit'); ?>
          </button>
        </form>
        
        <!-- Important Notes -->
        <div style="margin-top: 20px; padding: 12px; background: #f0f6fc; border-left: 4px solid #0073aa; border-radius: 4px;">
          <h4 style="margin-top: 0; margin-bottom: 8px; color: #0073aa;">
            <span class="dashicons dashicons-info" style="font-size: 16px; vertical-align: middle;"></span>
            <?php esc_html_e('Important Notes', 'asneris-seo-toolkit'); ?>
          </h4>
          <p style="margin: 0; color: #646970; font-size: 13px;">
            <strong><?php esc_html_e('Auto vs Manual:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Auto redirects are created automatically by the system (for example, when URLs change). Manual redirects are created explicitly on this page.', 'asneris-seo-toolkit'); ?>
          </p>
        </div>
      </div>
      
      <!-- Redirects List -->
      <div class="ASNERISSEO-card" style="max-width: 100%; margin-top: 20px;">
        <h2><span class="dashicons dashicons-list-view"></span> <?php esc_html_e('Active Redirects', 'asneris-seo-toolkit'); ?></h2>
        
        <?php if (empty($redirects)): ?>
          <p style="color: #646970;"><?php esc_html_e('No redirects added yet.', 'asneris-seo-toolkit'); ?><br><?php esc_html_e('Add one above when a page URL changes.', 'asneris-seo-toolkit'); ?></p>
        <?php else: ?>
          <table class="wp-list-table widefat fixed striped">
            <thead>
              <tr>
                <th style="width: 10%;"><?php esc_html_e('Status', 'asneris-seo-toolkit'); ?></th>
                <th style="width: 30%;"><?php esc_html_e('From', 'asneris-seo-toolkit'); ?></th>
                <th style="width: 30%;"><?php esc_html_e('To', 'asneris-seo-toolkit'); ?></th>
                <th style="width: 10%;"><?php esc_html_e('Code', 'asneris-seo-toolkit'); ?></th>
                <th style="width: 10%;"><?php esc_html_e('Type', 'asneris-seo-toolkit'); ?></th>
                <th style="width: 10%;"><?php esc_html_e('Actions', 'asneris-seo-toolkit'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($redirects as $index => $redirect): ?>
                <tr>
                  <td>
                    <?php if ($redirect['enabled']): ?>
                    <span style="color: #46b450;">● <?php esc_html_e('Active', 'asneris-seo-toolkit'); ?></span>
                  <?php else: ?>
                    <span style="color: #dba617;">● <?php esc_html_e('Disabled', 'asneris-seo-toolkit'); ?></span>
                    <?php endif; ?>
                  </td>
                  <td><code><?php echo esc_html($redirect['from']); ?></code></td>
                  <td><code><?php echo esc_html($redirect['to']); ?></code></td>
                  <td><?php echo esc_html($redirect['code']); ?></td>
                  <td>
                    <?php if ($redirect['type'] === 'auto'): ?>
                    <span class="dashicons dashicons-update" title="<?php esc_attr_e('Auto-generated', 'asneris-seo-toolkit'); ?>"></span> <?php esc_html_e('Auto', 'asneris-seo-toolkit'); ?>
                  <?php else: ?>
                    <span class="dashicons dashicons-admin-tools" title="<?php esc_attr_e('Manual', 'asneris-seo-toolkit'); ?>"></span> <?php esc_html_e('Manual', 'asneris-seo-toolkit'); ?>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-redirects&action=toggle&index=' . $index), 'ASNERISSEO_redirect_toggle_' . $index)); ?>" class="button button-small">
                      <?php $redirect['enabled'] ? esc_html_e('Disable', 'asneris-seo-toolkit') : esc_html_e('Enable', 'asneris-seo-toolkit'); ?>
                    </a>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-redirects&action=delete&index=' . $index), 'ASNERISSEO_redirect_delete_' . $index)); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e('Delete this redirect?', 'asneris-seo-toolkit'); ?>');">
                      <?php esc_html_e('Delete', 'asneris-seo-toolkit'); ?>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          
          <div style="margin-top: 20px;">
            <form method="post" action="" style="display: inline;">
              <?php wp_nonce_field('ASNERISSEO_clear_auto'); ?>
              <button type="submit" name="ASNERISSEO_clear_auto" class="button" onclick="return confirm('<?php esc_attr_e('Clear all automatic redirects?', 'asneris-seo-toolkit'); ?>');">
                <?php esc_html_e('Clear All Auto Redirects', 'asneris-seo-toolkit'); ?>
              </button>
            </form>
          </div>
        <?php endif; ?>
      </div>
      
        </div><!-- .ASNERISSEO-tab-content -->
      </div><!-- .ASNERISSEO-settings-form -->
        
      <?php // ASNERISSEO_Help_Content::render_sidebar('redirects'); ?>
    </div>
    
    <?php ASNERISSEO_Help_Modal::render_modals('redirects'); ?>
    <?php
  }
}
