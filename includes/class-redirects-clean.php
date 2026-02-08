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
    if (!isset($_SERVER['REQUEST_URI'])) return;
    $request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
    $request_path = wp_parse_url($request_uri, PHP_URL_PATH);
    $query_string = isset($_SERVER['QUERY_STRING']) ? sanitize_text_field(wp_unslash($_SERVER['QUERY_STRING'])) : '';
    
    $redirects = self::get_redirects();
    
    foreach ($redirects as $redirect) {
      if (!$redirect['enabled']) {
        continue;
      }
      
      $from = $redirect['from'];
      $to = $redirect['to'];
      $code = (int)$redirect['code'];
      
      // Parse the 'from' URL to check if it has query parameters
      $from_parsed = wp_parse_url($from);
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
          
          wp_safe_redirect($to, $code);
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
          
          wp_safe_redirect($to, $code);
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
  public static function enqueue_assets($hook) {
    if ($hook !== ASNERIS_MENU_SLUG . '_page_' . ASNERIS_MENU_SLUG . '-redirects') return;
    wp_enqueue_style('ASNERISSEO-admin', ASNERISSEO_URL . 'assets/css/admin-style.css', [], ASNERISSEO_VERSION);
  }
  
  /**
   * Render redirects management page
   */
  public static function render_page() {
    // Handle form submissions
    if (isset($_POST['ASNERISSEO_add_redirect']) && check_admin_referer('ASNERISSEO_redirect_add')) {
      $from = isset($_POST['from']) ? sanitize_text_field(wp_unslash($_POST['from'])) : '';
      $to = isset($_POST['to']) ? sanitize_url(wp_unslash($_POST['to'])) : '';
      $code = isset($_POST['code']) ? (int) $_POST['code'] : 301;
      
      // Strip domain from URLs to store only path + query
      $from = str_replace(home_url(), '', $from);
      $to = str_replace(home_url(), '', $to);
      
      if (!empty($from) && !empty($to)) {
        self::add_redirect($from, $to, $code, 'manual');
        echo '<div class="notice notice-success"><p>' . esc_html__('Redirect added successfully!', 'asneris-seo-toolkit') . '</p></div>';
      }
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['index'])) {
      $index = (int) sanitize_text_field(wp_unslash($_GET['index']));
      check_admin_referer('ASNERISSEO_redirect_delete_' . $index);
      self::delete_redirect($index);
      echo '<div class="notice notice-success"><p>' . esc_html__('Redirect deleted!', 'asneris-seo-toolkit') . '</p></div>';
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['index'])) {
      $index = (int) sanitize_text_field(wp_unslash($_GET['index']));
      check_admin_referer('ASNERISSEO_redirect_toggle_' . $index);
      self::toggle_redirect($index);
      echo '<div class="notice notice-success"><p>' . esc_html__('Redirect status updated!', 'asneris-seo-toolkit') . '</p></div>';
    }
    
    if (isset($_POST['ASNERISSEO_clear_auto']) && check_admin_referer('ASNERISSEO_clear_auto')) {
      self::clear_auto_redirects();
      echo '<div class="notice notice-success"><p>' . esc_html__('Automatic redirects cleared!', 'asneris-seo-toolkit') . '</p></div>';
    }
    
    $redirects = self::get_redirects();
    ?>
    <div class="wrap ASNERISSEO-admin-wrap has-sidebar">
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
                  <?php ASNERISSEO_Help_Modal::render_help_icon('redirect-codes'); ?>
                </label>
              </th>
              <td>
                <select id="code" name="code" required>
                  <option value="301"><?php esc_html_e('301 Permanent', 'asneris-seo-toolkit'); ?></option>
                  <option value="302"><?php esc_html_e('302 Temporary', 'asneris-seo-toolkit'); ?></option>
                  <option value="307"><?php esc_html_e('307 Temporary (Preserve Method)', 'asneris-seo-toolkit'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Use 301 when the old page is permanently replaced by the new page.', 'asneris-seo-toolkit'); ?></p>
              </td>
            </tr>
          </table>
          <button type="submit" name="ASNERISSEO_add_redirect" class="button button-primary">
            <?php esc_html_e('Add Redirect', 'asneris-seo-toolkit'); ?>
          </button>
        </form>
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
        
      <?php ASNERISSEO_Help_Content::render_sidebar('redirects'); ?>
    </div>
    
    <?php ASNERISSEO_Help_Modal::render_modals('redirects'); ?>
    <?php
  }
}
