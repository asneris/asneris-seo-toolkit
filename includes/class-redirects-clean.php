<?php
if (!defined('ABSPATH')) exit;

class CFSEO_Redirects {
  
  const OPTION_KEY = 'CFSEO_redirects';
  
  /**
   * Initialize redirects hooks
   */
  public static function init() {
    add_action('template_redirect', [__CLASS__, 'handle_redirects'], 1);
    add_action('post_updated', [__CLASS__, 'track_slug_change'], 10, 3);
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
    
    // Get old and new permalinks
    $old_url = str_replace(home_url(), '', get_permalink($post_before));
    $new_url = str_replace(home_url(), '', get_permalink($post_after));
    
    if ($old_url === $new_url) {
      return;
    }
    
    // Add redirect
    self::add_redirect($old_url, $new_url, 301, 'auto');
  }
  
  /**
   * Handle redirects
   */
  public static function handle_redirects() {
    $request_uri = $_SERVER['REQUEST_URI'];
    $request_path = parse_url($request_uri, PHP_URL_PATH);
    $query_string = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    
    $redirects = self::get_redirects();
    
    foreach ($redirects as $redirect) {
      if (!$redirect['enabled']) {
        continue;
      }
      
      $from = $redirect['from'];
      $to = $redirect['to'];
      $code = (int)$redirect['code'];
      
      // Parse the 'from' URL to check if it has query parameters
      $from_parsed = parse_url($from);
      $from_path = isset($from_parsed['path']) ? rtrim($from_parsed['path'], '/') : '';
      $from_query = isset($from_parsed['query']) ? $from_parsed['query'] : '';
      
      // Check if 'from' URL has query parameters
      if (!empty($from_query)) {
        // Match with query string
        $full_request = rtrim($request_path, '/') . ($query_string ? '?' . $query_string : '');
        $full_from = $from_path . '?' . $from_query;
        
        if ($full_request === $full_from || rtrim($request_path, '/') . '?' . $query_string === $full_from) {
          // Make sure we have full URL for redirect
          if (!preg_match('/^https?:\/\//', $to)) {
            $to = home_url($to);
          }
          
          wp_redirect($to, $code);
          exit;
        }
      } else {
        // Exact path match (without query string)
        $from_path = rtrim($from, '/');
        if (rtrim($request_path, '/') === $from_path) {
          // Make sure we have full URL for redirect
          if (!preg_match('/^https?:\/\//', $to)) {
            $to = home_url($to);
          }
          
          wp_redirect($to, $code);
          exit;
        }
      }
    }
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
    
    // Remove existing redirect with same "from"
    $redirects = array_filter($redirects, function($r) use ($from) {
      return $r['from'] !== $from;
    });
    
    // Add new redirect
    $redirects[] = [
      'from' => $from,
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
      'clarity-first-seo',
      __('Redirect', 'clarity-first-seo'),
      __('Redirect', 'clarity-first-seo'),
      'manage_options',
      'cfseo-redirects',
      [__CLASS__, 'render_page']
    );
  }
  
  /**
   * Enqueue admin styles
   */
  public static function enqueue_assets($hook) {
    if ($hook !== 'clarity-first-seo_page_cfseo-redirects') return;
    wp_enqueue_style('cfseo-admin', CFSEO_URL . 'assets/css/admin-style.css', [], CFSEO_VERSION);
  }
  
  /**
   * Render redirects management page
   */
  public static function render_page() {
    // Handle form submissions
    if (isset($_POST['CFSEO_add_redirect']) && check_admin_referer('CFSEO_redirect_add')) {
      $from = sanitize_text_field($_POST['from']);
      $to = sanitize_text_field($_POST['to']);
      $code = (int)$_POST['code'];
      
      // Strip domain from URLs to store only path + query
      $from = str_replace(home_url(), '', $from);
      $to = str_replace(home_url(), '', $to);
      
      if (!empty($from) && !empty($to)) {
        self::add_redirect($from, $to, $code, 'manual');
        echo '<div class="notice notice-success"><p>' . __('Redirect added successfully!', 'clarity-first-seo') . '</p></div>';
      }
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['index'])) {
      check_admin_referer('CFSEO_redirect_delete_' . $_GET['index']);
      self::delete_redirect((int)$_GET['index']);
      echo '<div class="notice notice-success"><p>' . __('Redirect deleted!', 'clarity-first-seo') . '</p></div>';
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['index'])) {
      check_admin_referer('CFSEO_redirect_toggle_' . $_GET['index']);
      self::toggle_redirect((int)$_GET['index']);
      echo '<div class="notice notice-success"><p>' . __('Redirect status updated!', 'clarity-first-seo') . '</p></div>';
    }
    
    if (isset($_POST['CFSEO_clear_auto']) && check_admin_referer('CFSEO_clear_auto')) {
      self::clear_auto_redirects();
      echo '<div class="notice notice-success"><p>' . __('Automatic redirects cleared!', 'clarity-first-seo') . '</p></div>';
    }
    
    $redirects = self::get_redirects();
    ?>
    <div class="wrap cfseo-admin-wrap has-sidebar">
      <h1>
        <span class="dashicons dashicons-controls-forward"></span>
        <?php _e('SEO Redirects', 'clarity-first-seo'); ?>
        <?php CFSEO_Help_Modal::render_help_icon('redirects-overview', 'Learn about redirects'); ?>
      </h1>
      <p class="cfseo-subtitle"><?php _e('Send visitors and search engines to the right page when a URL changes.', 'clarity-first-seo'); ?></p>
      
      <div class="cfseo-settings-form">
        <div class="cfseo-tab-content">
      
      <!-- Add New Redirect -->
      <div class="cfseo-card">
        <h2><span class="dashicons dashicons-plus-alt"></span> <?php _e('Add New Redirect', 'clarity-first-seo'); ?></h2>
        <form method="post" action="">
          <?php wp_nonce_field('CFSEO_redirect_add'); ?>
          <table class="form-table">
            <tr>
              <th scope="row">
                <label for="from">
                  <?php _e('From (Old URL)', 'clarity-first-seo'); ?>
                  <?php CFSEO_Help_Modal::render_help_icon('from-url'); ?>
                </label>
              </th>
              <td>
                <input type="text" id="from" name="from" class="regular-text" placeholder="/?page_id=2 or /old-page/" required>
                <p class="description"><?php _e('The old page address (path or query string). Examples: /old-page/ or /?page_id=2', 'clarity-first-seo'); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="to">
                  <?php _e('To (New URL)', 'clarity-first-seo'); ?>
                  <?php CFSEO_Help_Modal::render_help_icon('to-url'); ?>
                </label>
              </th>
              <td>
                <input type="text" id="to" name="to" class="regular-text" placeholder="/?page_id=10 or /new-page/" required>
                <p class="description"><?php _e('The destination page. Examples: /new-page/ or /?page_id=10 or https://example.com/page/', 'clarity-first-seo'); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="code">
                  <?php _e('Redirect Type', 'clarity-first-seo'); ?>
                  <?php CFSEO_Help_Modal::render_help_icon('redirect-types'); ?>
                </label>
              </th>
              <td>
                <select id="code" name="code">
                  <option value="301"><?php _e('301 Permanent', 'clarity-first-seo'); ?></option>
                  <option value="302"><?php _e('302 Temporary', 'clarity-first-seo'); ?></option>
                  <option value="307"><?php _e('307 Temporary (Preserve Method)', 'clarity-first-seo'); ?></option>
                </select>
                <p class="description"><?php _e('Use 301 when the old page is permanently replaced by the new page.', 'clarity-first-seo'); ?></p>
              </td>
            </tr>
          </table>
          <button type="submit" name="CFSEO_add_redirect" class="button button-primary">
            <?php _e('Add Redirect', 'clarity-first-seo'); ?>
          </button>
        </form>
      </div>
      
      <!-- Redirects List -->
      <div class="cfseo-card" style="max-width: 100%; margin-top: 20px;">
        <h2><span class="dashicons dashicons-list-view"></span> <?php _e('Active Redirects', 'clarity-first-seo'); ?></h2>
        
        <?php if (empty($redirects)): ?>
          <p style="color: #646970;"><?php _e('No redirects added yet.', 'clarity-first-seo'); ?><br><?php _e('Add one above when a page URL changes.', 'clarity-first-seo'); ?></p>
        <?php else: ?>
          <table class="wp-list-table widefat fixed striped">
            <thead>
              <tr>
                <th style="width: 10%;"><?php _e('Status', 'clarity-first-seo'); ?></th>
                <th style="width: 30%;"><?php _e('From', 'clarity-first-seo'); ?></th>
                <th style="width: 30%;"><?php _e('To', 'clarity-first-seo'); ?></th>
                <th style="width: 10%;"><?php _e('Code', 'clarity-first-seo'); ?></th>
                <th style="width: 10%;"><?php _e('Type', 'clarity-first-seo'); ?></th>
                <th style="width: 10%;"><?php _e('Actions', 'clarity-first-seo'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($redirects as $index => $redirect): ?>
                <tr>
                  <td>
                    <?php if ($redirect['enabled']): ?>
                      <span style="color: #46b450;">● <?php _e('Active', 'clarity-first-seo'); ?></span>
                    <?php else: ?>
                      <span style="color: #dba617;">● <?php _e('Disabled', 'clarity-first-seo'); ?></span>
                    <?php endif; ?>
                  </td>
                  <td><code><?php echo esc_html($redirect['from']); ?></code></td>
                  <td><code><?php echo esc_html($redirect['to']); ?></code></td>
                  <td><?php echo esc_html($redirect['code']); ?></td>
                  <td>
                    <?php if ($redirect['type'] === 'auto'): ?>
                      <span class="dashicons dashicons-update" title="<?php esc_attr_e('Auto-generated', 'clarity-first-seo'); ?>"></span> <?php _e('Auto', 'clarity-first-seo'); ?>
                    <?php else: ?>
                      <span class="dashicons dashicons-admin-tools" title="<?php esc_attr_e('Manual', 'clarity-first-seo'); ?>"></span> <?php _e('Manual', 'clarity-first-seo'); ?>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="<?php echo wp_nonce_url(admin_url('options-general.php?page=cfseo-redirects&action=toggle&index=' . $index), 'CFSEO_redirect_toggle_' . $index); ?>" class="button button-small">
                      <?php $redirect['enabled'] ? _e('Disable', 'clarity-first-seo') : _e('Enable', 'clarity-first-seo'); ?>
                    </a>
                    <a href="<?php echo wp_nonce_url(admin_url('options-general.php?page=cfseo-redirects&action=delete&index=' . $index), 'CFSEO_redirect_delete_' . $index); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e('Delete this redirect?', 'clarity-first-seo'); ?>');">
                      <?php _e('Delete', 'clarity-first-seo'); ?>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          
          <div style="margin-top: 20px;">
            <form method="post" action="" style="display: inline;">
              <?php wp_nonce_field('CFSEO_clear_auto'); ?>
              <button type="submit" name="CFSEO_clear_auto" class="button" onclick="return confirm('<?php esc_attr_e('Clear all automatic redirects?', 'clarity-first-seo'); ?>');">
                <?php _e('Clear All Auto Redirects', 'clarity-first-seo'); ?>
              </button>
            </form>
          </div>
        <?php endif; ?>
      </div>
      
        </div><!-- .cfseo-tab-content -->
      </div><!-- .cfseo-settings-form -->
        
      <?php CFSEO_Help_Content::render_sidebar('redirects'); ?>
    </div>
    
    <?php CFSEO_Help_Modal::render_modals('redirects'); ?>
    <?php
  }
}
