<?php
if (!defined('ABSPATH')) exit;

class GSCSEO_Redirects {
  
  const OPTION_KEY = 'gscseo_redirects';
  
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
    
    $redirects = self::get_redirects();
    
    foreach ($redirects as $redirect) {
      if (!$redirect['enabled']) {
        continue;
      }
      
      $from = rtrim($redirect['from'], '/');
      $to = $redirect['to'];
      $code = (int)$redirect['code'];
      
      // Exact match
      if (rtrim($request_path, '/') === $from) {
        // Make sure we have full URL for redirect
        if (!preg_match('/^https?:\/\//', $to)) {
          $to = home_url($to);
        }
        
        wp_redirect($to, $code);
        exit;
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
      __('Redirect', 'bfseo'),
      __('Redirect', 'bfseo'),
      'manage_options',
      'gscseo-redirects',
      [__CLASS__, 'render_page']
    );
  }
  
  /**
   * Enqueue admin styles
   */
  public static function enqueue_assets($hook) {
    if ($hook !== 'clarity-first-seo_page_gscseo-redirects') return;
    wp_enqueue_style('gscseo-admin', GSCSEO_URL . 'assets/css/admin-style.css', [], GSCSEO_VERSION);
  }
  
  /**
   * Render redirects management page
   */
  public static function render_page() {
    // Handle form submissions
    if (isset($_POST['gscseo_add_redirect']) && check_admin_referer('gscseo_redirect_add')) {
      $from = sanitize_text_field($_POST['from']);
      $to = sanitize_text_field($_POST['to']);
      $code = (int)$_POST['code'];
      
      if (!empty($from) && !empty($to)) {
        self::add_redirect($from, $to, $code, 'manual');
        echo '<div class="notice notice-success"><p>' . __('Redirect added successfully!', 'bfseo') . '</p></div>';
      }
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['index'])) {
      check_admin_referer('gscseo_redirect_delete_' . $_GET['index']);
      self::delete_redirect((int)$_GET['index']);
      echo '<div class="notice notice-success"><p>' . __('Redirect deleted!', 'bfseo') . '</p></div>';
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['index'])) {
      check_admin_referer('gscseo_redirect_toggle_' . $_GET['index']);
      self::toggle_redirect((int)$_GET['index']);
      echo '<div class="notice notice-success"><p>' . __('Redirect status updated!', 'bfseo') . '</p></div>';
    }
    
    if (isset($_POST['gscseo_clear_auto']) && check_admin_referer('gscseo_clear_auto')) {
      self::clear_auto_redirects();
      echo '<div class="notice notice-success"><p>' . __('Automatic redirects cleared!', 'bfseo') . '</p></div>';
    }
    
    $redirects = self::get_redirects();
    ?>
    <div class="wrap gscseo-admin-wrap">
      <h1>
        <span class="dashicons dashicons-controls-forward"></span>
        <?php _e('SEO Redirects', 'bfseo'); ?>
      </h1>
      <p class="gscseo-subtitle"><?php _e('Manage 301 redirects for changed URLs', 'bfseo'); ?></p>
      
      <div class="gscseo-settings-form">
        <div class="gscseo-tab-content">
      
      <!-- Add New Redirect -->
      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-plus-alt"></span> <?php _e('Add New Redirect', 'bfseo'); ?></h2>
        <form method="post" action="">
          <?php wp_nonce_field('gscseo_redirect_add'); ?>
          <table class="form-table">
            <tr>
              <th scope="row">
                <label for="from"><?php _e('From (Old URL)', 'bfseo'); ?></label>
              </th>
              <td>
                <input type="text" id="from" name="from" class="regular-text" placeholder="/old-page/" required>
                <p class="description"><?php _e('Relative path without domain. Example: /old-page/', 'bfseo'); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="to"><?php _e('To (New URL)', 'bfseo'); ?></label>
              </th>
              <td>
                <input type="text" id="to" name="to" class="regular-text" placeholder="/new-page/" required>
                <p class="description"><?php _e('Relative path or full URL. Example: /new-page/', 'bfseo'); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="code"><?php _e('Redirect Type', 'bfseo'); ?></label>
              </th>
              <td>
                <select id="code" name="code">
                  <option value="301"><?php _e('301 Permanent', 'bfseo'); ?></option>
                  <option value="302"><?php _e('302 Temporary', 'bfseo'); ?></option>
                  <option value="307"><?php _e('307 Temporary (Preserve Method)', 'bfseo'); ?></option>
                </select>
                <p class="description"><?php _e('Use 301 for permanent moves (recommended for SEO)', 'bfseo'); ?></p>
              </td>
            </tr>
          </table>
          <button type="submit" name="gscseo_add_redirect" class="button button-primary">
            <?php _e('Add Redirect', 'bfseo'); ?>
          </button>
        </form>
      </div>
      
      <!-- Redirects List -->
      <div class="gscseo-card" style="max-width: 100%; margin-top: 20px;">
        <h2><span class="dashicons dashicons-list-view"></span> <?php _e('Active Redirects', 'bfseo'); ?></h2>
        
        <?php if (empty($redirects)): ?>
          <p style="color: #646970;"><?php _e('No redirects configured yet.', 'bfseo'); ?></p>
        <?php else: ?>
          <table class="wp-list-table widefat fixed striped">
            <thead>
              <tr>
                <th style="width: 10%;"><?php _e('Status', 'bfseo'); ?></th>
                <th style="width: 30%;"><?php _e('From', 'bfseo'); ?></th>
                <th style="width: 30%;"><?php _e('To', 'bfseo'); ?></th>
                <th style="width: 10%;"><?php _e('Code', 'bfseo'); ?></th>
                <th style="width: 10%;"><?php _e('Type', 'bfseo'); ?></th>
                <th style="width: 10%;"><?php _e('Actions', 'bfseo'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($redirects as $index => $redirect): ?>
                <tr>
                  <td>
                    <?php if ($redirect['enabled']): ?>
                      <span style="color: #46b450;">● <?php _e('Active', 'bfseo'); ?></span>
                    <?php else: ?>
                      <span style="color: #dba617;">● <?php _e('Disabled', 'bfseo'); ?></span>
                    <?php endif; ?>
                  </td>
                  <td><code><?php echo esc_html($redirect['from']); ?></code></td>
                  <td><code><?php echo esc_html($redirect['to']); ?></code></td>
                  <td><?php echo esc_html($redirect['code']); ?></td>
                  <td>
                    <?php if ($redirect['type'] === 'auto'): ?>
                      <span class="dashicons dashicons-update" title="<?php esc_attr_e('Auto-generated', 'bfseo'); ?>"></span> <?php _e('Auto', 'bfseo'); ?>
                    <?php else: ?>
                      <span class="dashicons dashicons-admin-tools" title="<?php esc_attr_e('Manual', 'bfseo'); ?>"></span> <?php _e('Manual', 'bfseo'); ?>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="<?php echo wp_nonce_url(admin_url('options-general.php?page=gscseo-redirects&action=toggle&index=' . $index), 'gscseo_redirect_toggle_' . $index); ?>" class="button button-small">
                      <?php $redirect['enabled'] ? _e('Disable', 'bfseo') : _e('Enable', 'bfseo'); ?>
                    </a>
                    <a href="<?php echo wp_nonce_url(admin_url('options-general.php?page=gscseo-redirects&action=delete&index=' . $index), 'gscseo_redirect_delete_' . $index); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e('Delete this redirect?', 'bfseo'); ?>');">
                      <?php _e('Delete', 'bfseo'); ?>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          
          <div style="margin-top: 20px;">
            <form method="post" action="" style="display: inline;">
              <?php wp_nonce_field('gscseo_clear_auto'); ?>
              <button type="submit" name="gscseo_clear_auto" class="button" onclick="return confirm('<?php esc_attr_e('Clear all automatic redirects?', 'bfseo'); ?>');">
                <?php _e('Clear All Auto Redirects', 'bfseo'); ?>
              </button>
            </form>
          </div>
        <?php endif; ?>
      </div>
      
        </div><!-- .gscseo-tab-content -->
      </div><!-- .gscseo-settings-form -->
        
      <!-- Sidebar Info -->
      <div class="gscseo-sidebar">
          <div class="gscseo-info-box">
            <h3><span class="dashicons dashicons-info"></span> Quick Tips</h3>
            <ul>
              <li>Use 301 Permanent redirects for changed URLs to preserve SEO value</li>
              <li>Automatic redirects are created when you change post/page slugs</li>
              <li>Test redirects after adding to ensure they work correctly</li>
              <li>Review automatic redirects periodically and delete unnecessary ones</li>
              <li>Redirects use relative paths (/old-page/) for portability across domains</li>
            </ul>
          </div>
          
          <div class="gscseo-info-box gscseo-success-box">
            <h3><span class="dashicons dashicons-yes"></span> Need Help?</h3>
            <p>Learn about URL redirects and readiness:</p>
            <ul>
              <li><a href="https://clarityfirstseo.com/docs/redirects/" target="_blank">Redirect Management</a></li>
              <li><a href="https://clarityfirstseo.com/docs/301-vs-302/" target="_blank">301 vs 302 Redirects</a></li>
              <li><a href="https://clarityfirstseo.com/docs/seo-readiness/" target="_blank">SEO Readiness Guide</a></li>
            </ul>
          </div>
        </div>
    </div>
    <?php
  }
}
