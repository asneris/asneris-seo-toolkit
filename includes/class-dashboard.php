<?php
/**
 * Dashboard - High-level clarity overview
 * 
 * Purpose: Show summary of validation results
 * - Counts only (no scoring)
 * - No judgments
 * - Links to relevant tabs
 */

if (!defined('ABSPATH')) exit;

class CFSEO_Dashboard {
  
  /**
   * Register dashboard page
   */
  public static function register_menu() {
    add_submenu_page(
      'clarity-first-seo',
      __('Dashboard', 'cfseo'),
      __('Dashboard', 'cfseo'),
      'manage_options',
      'cfseo-dashboard',
      [__CLASS__, 'render_page']
    );
  }
  
  /**
   * Enqueue admin styles
   */
  public static function enqueue_assets($hook) {
    if ($hook !== 'clarity-first-seo_page_cfseo-dashboard') return;
    wp_enqueue_style('cfseo-admin', CFSEO_URL . 'assets/css/admin-style.css', [], CFSEO_VERSION);
  }
  
  /**
   * Get validation summary counts
   */
  private static function get_validation_summary() {
    // This would typically check recent validation results
    // For now, return placeholder data
    return [
      'passed' => 0,
      'warnings' => 0,
      'conflicts' => 0,
      'last_checked' => null
    ];
  }
  
  /**
   * Get diagnostic summary
   */
  private static function get_diagnostic_summary() {
    // Check if sitemap exists
    $sitemap_exists = false;
    $sitemap_urls = [home_url('/wp-sitemap.xml'), home_url('/sitemap.xml')];
    foreach ($sitemap_urls as $url) {
      $response = @wp_remote_head($url, ['timeout' => 3]);
      if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $sitemap_exists = true;
        break;
      }
    }
    
    // Check for SEO plugin conflicts
    $known_plugins = [
      'wordpress-seo/wp-seo.php' => 'Yoast SEO',
      'seo-by-rank-math/rank-math.php' => 'Rank Math',
      'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
    ];
    $active_seo_plugins = [];
    foreach ($known_plugins as $plugin_file => $plugin_name) {
      if (is_plugin_active($plugin_file)) {
        $active_seo_plugins[] = $plugin_name;
      }
    }
    
    return [
      'sitemap_exists' => $sitemap_exists,
      'robots_txt_exists' => file_exists(ABSPATH . 'robots.txt'),
      'seo_plugin_conflicts' => count($active_seo_plugins),
      'redirect_count' => self::get_redirect_count()
    ];
  }
  
  /**
   * Get redirect count
   */
  private static function get_redirect_count() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'CFSEO_redirects';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
      return 0;
    }
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active'");
  }
  
  /**
   * Render dashboard page
   */
  public static function render_page() {
    $validation_summary = self::get_validation_summary();
    $diagnostic_summary = self::get_diagnostic_summary();
    ?>
    <div class="wrap cfseo-admin-wrap">
      <h1>
        <span class="dashicons dashicons-dashboard"></span>
        <?php _e('Dashboard', 'cfseo'); ?>
      </h1>
      <p class="cfseo-subtitle">
        <?php _e('Clarity-First SEO validates what search engines can see. It does not predict rankings.', 'cfseo'); ?>
      </p>
      
      <div class="cfseo-dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 30px;">
        
        <!-- Validation Summary -->
        <div class="cfseo-card">
          <h2><span class="dashicons dashicons-yes-alt"></span> Validation Status</h2>
          <p style="color: #646970; margin-top: 5px;">Most recent validation check results</p>
          
          <?php if ($validation_summary['last_checked']): ?>
            <div class="cfseo-stat-row">
              <div class="cfseo-stat">
                <div class="cfseo-stat-value" style="font-size: 36px; font-weight: 600; color: #46b450;">
                  <?php echo esc_html($validation_summary['passed']); ?>
                </div>
                <div class="cfseo-stat-label" style="color: #646970; font-size: 13px;">Passed Checks</div>
              </div>
              <div class="cfseo-stat">
                <div class="cfseo-stat-value" style="font-size: 36px; font-weight: 600; color: #f0ad4e;">
                  <?php echo esc_html($validation_summary['warnings']); ?>
                </div>
                <div class="cfseo-stat-label" style="color: #646970; font-size: 13px;">Warnings</div>
              </div>
              <div class="cfseo-stat">
                <div class="cfseo-stat-value" style="font-size: 36px; font-weight: 600; color: #dc3232;">
                  <?php echo esc_html($validation_summary['conflicts']); ?>
                </div>
                <div class="cfseo-stat-label" style="color: #646970; font-size: 13px;">Conflicts</div>
              </div>
            </div>
            <p style="margin-top: 15px;">
              <a href="?page=cfseo-validation" class="button">View Validation Details</a>
            </p>
          <?php else: ?>
            <p style="margin: 20px 0;">No validation checks have been run yet.</p>
            <p>
              <a href="?page=cfseo-validation" class="button button-primary">Run First Validation</a>
            </p>
          <?php endif; ?>
        </div>
        
        <!-- Diagnostics Summary -->
        <div class="cfseo-card">
          <h2><span class="dashicons dashicons-analytics"></span> Site Diagnostics</h2>
          <p style="color: #646970; margin-top: 5px;">Current site detection results</p>
          
          <table class="cfseo-summary-table" style="width: 100%; margin-top: 15px;">
            <tr>
              <td style="padding: 8px 0;">Sitemap Detected:</td>
              <td style="text-align: right; font-weight: 600;">
                <?php echo $diagnostic_summary['sitemap_exists'] ? '<span style="color: #46b450;">Yes</span>' : '<span style="color: #646970;">Not Found</span>'; ?>
              </td>
            </tr>
            <tr>
              <td style="padding: 8px 0;">Robots.txt File:</td>
              <td style="text-align: right; font-weight: 600;">
                <?php echo $diagnostic_summary['robots_txt_exists'] ? '<span style="color: #46b450;">Found</span>' : '<span style="color: #646970;">Not Found</span>'; ?>
              </td>
            </tr>
            <tr>
              <td style="padding: 8px 0;">Plugin Conflicts:</td>
              <td style="text-align: right; font-weight: 600;">
                <?php 
                if ($diagnostic_summary['seo_plugin_conflicts'] > 0) {
                  echo '<span style="color: #f0ad4e;">' . esc_html($diagnostic_summary['seo_plugin_conflicts']) . ' Detected</span>';
                } else {
                  echo '<span style="color: #46b450;">None</span>';
                }
                ?>
              </td>
            </tr>
            <tr>
              <td style="padding: 8px 0;">Active Redirects:</td>
              <td style="text-align: right; font-weight: 600;">
                <?php echo esc_html($diagnostic_summary['redirect_count']); ?>
              </td>
            </tr>
          </table>
          
          <p style="margin-top: 15px;">
            <a href="?page=cfseo-diagnostics" class="button">View Full Diagnostics</a>
          </p>
        </div>
        
        <!-- Quick Actions -->
        <div class="cfseo-card">
          <h2><span class="dashicons dashicons-admin-tools"></span> Quick Actions</h2>
          <p style="color: #646970; margin-top: 5px;">Common tasks and tools</p>
          
          <div style="margin-top: 15px;">
            <p style="margin: 10px 0;">
              <a href="?page=cfseo-validation" class="button button-large" style="width: 100%; text-align: center;">
                <span class="dashicons dashicons-yes-alt"></span> Run Validation Check
              </a>
            </p>
            <p style="margin: 10px 0;">
              <a href="?page=cfseo-bulk-edit" class="button button-large" style="width: 100%; text-align: center;">
                <span class="dashicons dashicons-edit"></span> Bulk Edit Metadata
              </a>
            </p>
            <p style="margin: 10px 0;">
              <a href="?page=cfseo-redirects" class="button button-large" style="width: 100%; text-align: center;">
                <span class="dashicons dashicons-randomize"></span> Manage Redirects
              </a>
            </p>
            <p style="margin: 10px 0;">
              <a href="?page=cfseo-robots" class="button button-large" style="width: 100%; text-align: center;">
                <span class="dashicons dashicons-shield"></span> Edit Robots.txt
              </a>
            </p>
          </div>
        </div>
        
      </div>
      
      <!-- Beta Notice -->
      <div class="notice notice-info" style="margin-top: 30px;">
        <p>
          <strong>🧪 Beta Software</strong> – 
          This plugin is in active development. 
          <a href="?page=cfseo-help">Learn what this plugin does and doesn't do</a>
        </p>
      </div>
      
    </div>
    <?php
  }
}
