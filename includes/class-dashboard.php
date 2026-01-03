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
    // Get saved validation results from database
    $saved = get_option('cfseo_validation_summary', null);
    
    if ($saved === null) {
      // State 1: Never run
      return [
        'passed' => 0,
        'warnings' => 0,
        'conflicts' => 0,
        'last_checked' => null
      ];
    }
    
    // Return saved results
    return [
      'passed' => isset($saved['passed']) ? $saved['passed'] : 0,
      'warnings' => isset($saved['warnings']) ? $saved['warnings'] : 0,
      'conflicts' => isset($saved['conflicts']) ? $saved['conflicts'] : 0,
      'last_checked' => isset($saved['last_checked']) ? $saved['last_checked'] : 'Today'
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
    <div class="wrap cfseo-admin-wrap" style="max-width: 1400px;">
      <h1>
        <span class="dashicons dashicons-dashboard"></span>
        <?php _e('Dashboard', 'cfseo'); ?>
      </h1>
      <p class="cfseo-subtitle">
        <?php _e('Clarity-First SEO checks what search engines can see on your site. It does not predict rankings.', 'cfseo'); ?>
      </p>
      
      <div class="cfseo-dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-top: 30px;">
        
        <!-- Site Diagnostics Summary -->
        <div class="cfseo-card" style="background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
          <h2><span class="dashicons dashicons-analytics"></span> Site Diagnostics</h2>
          
          <?php if ($validation_summary['last_checked'] === null): ?>
            <!-- State 1: Never run (Blue) -->
            <div class="cfseo-validation-status cfseo-status-never-run">
              <div class="cfseo-status-icon">🟦</div>
              <p class="cfseo-status-message">You haven't reviewed your site's SEO configuration yet.</p>
              <p class="cfseo-status-actions">
                <a href="?page=cfseo-validation" class="button button-primary button-large">Run First Validation</a>
              </p>
            </div>
          
          <?php elseif ($validation_summary['warnings'] === 0 && $validation_summary['conflicts'] === 0): ?>
            <!-- State 2: Recently run, healthy (Green) -->
            <div class="cfseo-validation-status cfseo-status-healthy">
              <div class="cfseo-status-icon">🟩</div>
              <div class="cfseo-status-info">
                <div class="cfseo-status-line"><strong>Last checked:</strong> <?php echo esc_html($validation_summary['last_checked']); ?></div>
                <div class="cfseo-status-line"><strong>Status:</strong> Configuration looks clear</div>
              </div>
              <p class="cfseo-status-actions">
                <a href="?page=cfseo-validation" class="button button-primary">View Site Diagnostics</a>
              </p>
            </div>
          
          <?php else: ?>
            <!-- State 3: Issues detected (Yellow) -->
            <div class="cfseo-validation-status cfseo-status-issues">
              <div class="cfseo-status-icon">🟨</div>
              <div class="cfseo-status-info">
                <div class="cfseo-status-line"><strong>Last checked:</strong> <?php echo esc_html($validation_summary['last_checked']); ?></div>
                <div class="cfseo-status-line"><strong>Status:</strong> Some settings may affect indexing</div>
              </div>
              <div style="background: #fff9e6; border-left: 3px solid #f0ad4e; padding: 12px; margin: 15px 0; border-radius: 4px;">
                <p style="margin: 0 0 10px 0; font-size: 13px; line-height: 1.6; color: #1d2327;">
                  <strong>What this means:</strong> Your site has configuration issues that could prevent search engines from properly indexing your content.
                </p>
                <table style="width: 100%; font-size: 14px;">
                  <tr>
                    <td style="padding: 6px 0; width: 60px; text-align: left; font-weight: 600; color: #f0ad4e; font-size: 24px;"><?php echo esc_html($validation_summary['warnings']); ?></td>
                    <td style="padding: 6px 0;">⚠ <strong>Warnings</strong><br><span style="font-size: 12px; color: #646970;">Minor issues that should be reviewed</span></td>
                  </tr>
                  <tr>
                    <td colspan="2" style="padding: 5px 0;"></td>
                  </tr>
                  <tr>
                    <td style="padding: 6px 0; width: 60px; text-align: left; font-weight: 600; color: #dc3232; font-size: 24px;"><?php echo esc_html($validation_summary['conflicts']); ?></td>
                    <td style="padding: 6px 0;">✗ <strong>Issues</strong><br><span style="font-size: 12px; color: #646970;">Problems that may block indexing</span></td>
                  </tr>
                </table>
              </div>
              <p class="cfseo-status-actions">
                <a href="?page=cfseo-validation" class="button button-primary">View Site Diagnostics</a>
              </p>
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Page Diagnostics Summary -->
        <div class="cfseo-card" style="background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
          <h2><span class="dashicons dashicons-search"></span> Page Diagnostics</h2>
          <p style="color: #646970; margin-top: 5px;">Inspect what a single page exposes to search engines</p>
          
          <div style="padding: 20px 0;">
            <p style="margin: 0 0 15px 0; font-size: 14px; line-height: 1.6;">
              Analyze individual pages to see exactly what search engines read from your content.
            </p>
            <ul style="margin: 0; padding-left: 20px; font-size: 14px; line-height: 1.8; color: #50575e;">
              <li>Title tags and meta descriptions</li>
              <li>Canonical URLs and robots directives</li>
              <li>Open Graph and Twitter cards</li>
              <li>Schema markup and structured data</li>
            </ul>
          </div>
          
          <p style="margin-top: 15px;">
            <a href="?page=cfseo-diagnostics" class="button button-primary">Analyze a Page</a>
          </p>
        </div>
        
        <!-- Quick Actions -->
        <div class="cfseo-card" style="background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
          <h2><span class="dashicons dashicons-admin-tools"></span> Quick Actions</h2>
          <p style="color: #646970; margin-top: 5px;">Common SEO tasks you can do right now</p>
          
          <div style="margin-top: 20px;">
            <!-- Action 1 -->
            <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f1;">
              <h3 style="margin: 0 0 5px 0; font-size: 15px; font-weight: 600;">
                <span class="dashicons dashicons-chart-line" style="color: #2271b1; font-size: 18px; vertical-align: middle;"></span>
                Run Validation Check
              </h3>
              <p style="margin: 0 0 10px 0; font-size: 13px; color: #646970; line-height: 1.5;">
                Check site-wide SEO patterns like sitemaps and plugin conflicts
              </p>
              <a href="?page=cfseo-validation" class="button">Run Check</a>
            </div>
            
            <!-- Action 2 -->
            <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f1;">
              <h3 style="margin: 0 0 5px 0; font-size: 15px; font-weight: 600;">
                <span class="dashicons dashicons-edit" style="color: #2271b1; font-size: 18px; vertical-align: middle;"></span>
                Bulk Edit Metadata
              </h3>
              <p style="margin: 0 0 10px 0; font-size: 13px; color: #646970; line-height: 1.5;">
                Update titles and descriptions for multiple posts at once
              </p>
              <a href="?page=cfseo-bulk-edit" class="button">Edit Metadata</a>
            </div>
            
            <!-- Action 3 -->
            <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f1;">
              <h3 style="margin: 0 0 5px 0; font-size: 15px; font-weight: 600;">
                <span class="dashicons dashicons-location" style="color: #2271b1; font-size: 18px; vertical-align: middle;"></span>
                Google Business Profile
              </h3>
              <p style="margin: 0 0 10px 0; font-size: 13px; color: #646970; line-height: 1.5;">
                Add your business details to appear in Google Maps and local search
              </p>
              <a href="?page=cfseo-settings&tab=schema" class="button">Setup Local Business</a>
            </div>
            
            <!-- Action 4 -->
            <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f1;">
              <h3 style="margin: 0 0 5px 0; font-size: 15px; font-weight: 600;">
                <span class="dashicons dashicons-randomize" style="color: #2271b1; font-size: 18px; vertical-align: middle;"></span>
                Manage Redirects
              </h3>
              <p style="margin: 0 0 10px 0; font-size: 13px; color: #646970; line-height: 1.5;">
                Guide visitors to correct pages when URLs change
              </p>
              <a href="?page=cfseo-redirects" class="button">Manage Redirects</a>
            </div>
            
            <!-- Action 5 -->
            <div>
              <h3 style="margin: 0 0 5px 0; font-size: 15px; font-weight: 600;">
                <span class="dashicons dashicons-shield" style="color: #2271b1; font-size: 18px; vertical-align: middle;"></span>
                Edit Robots.txt
              </h3>
              <p style="margin: 0 0 10px 0; font-size: 13px; color: #646970; line-height: 1.5;">
                Control which pages search engines can visit and read
              </p>
              <a href="?page=cfseo-robots" class="button">Edit Robots.txt</a>
            </div>
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
