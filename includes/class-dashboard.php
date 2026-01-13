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
      __('Dashboard', 'clarity-first-seo'),
      __('Dashboard', 'clarity-first-seo'),
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
   * Get configuration status for all sections
   */
  private static function get_config_status() {
    $settings = get_option('CFSEO_settings', []);
    
    return [
      'general' => [
        'label' => 'General Settings',
        'icon' => 'dashicons-admin-generic',
        'completed' => !empty($settings['org_name']) && !empty($settings['org_logo']),
        'items' => [
          'Organization name configured' => !empty($settings['org_name']),
          'Logo uploaded' => !empty($settings['org_logo']),
        ]
      ],
      'verification' => [
        'label' => 'Search Engine Verification',
        'icon' => 'dashicons-yes-alt',
        'completed' => !empty($settings['google_verification']) && !empty($settings['bing_verification']) && !empty($settings['yandex_verification']),
        'items' => [
          'Google Search Console' => !empty($settings['google_verification']),
          'Bing Webmaster Tools' => !empty($settings['bing_verification']),
          'Yandex Webmaster' => !empty($settings['yandex_verification']),
        ]
      ],
      'indexnow' => [
        'label' => 'IndexNow',
        'icon' => 'dashicons-update',
        'completed' => !empty($settings['indexnow_enabled']) && !empty($settings['indexnow_key']),
        'items' => [
          'IndexNow enabled' => !empty($settings['indexnow_enabled']),
          'API key generated' => !empty($settings['indexnow_key']),
        ]
      ],
      'social' => [
        'label' => 'Social Media',
        'icon' => 'dashicons-share',
        'completed' => !empty($settings['default_og_image']) && !empty($settings['twitter_username']) && !empty($settings['facebook_app_id']),
        'items' => [
          'Default OG image set' => !empty($settings['default_og_image']),
          'Twitter username' => !empty($settings['twitter_username']),
          'Facebook App ID' => !empty($settings['facebook_app_id']),
        ]
      ],
      'schema' => [
        'label' => 'Schema Markup',
        'icon' => 'dashicons-editor-code',
        'completed' => !empty($settings['enable_breadcrumbs']) && !empty($settings['enable_local_business']),
        'items' => [
          'Breadcrumbs enabled' => !empty($settings['enable_breadcrumbs']),
          'Local Business schema' => !empty($settings['enable_local_business']),
        ]
      ],
      'templates' => [
        'label' => 'SEO Templates',
        'icon' => 'dashicons-text',
        'completed' => !empty($settings['title_templates']) && !empty($settings['description_templates']),
        'items' => [
          'Title templates configured' => !empty($settings['title_templates']),
          'Description templates configured' => !empty($settings['description_templates']),
        ]
      ],
    ];
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
    $config_status = self::get_config_status();
    $total_sections = count($config_status);
    $completed_sections = count(array_filter($config_status, function($s) { return $s['completed']; }));
    $progress_percent = round(($completed_sections / $total_sections) * 100);
    ?>
    <div class="wrap cfseo-admin-wrap" style="max-width: 1400px;">
      <h1>
        <span class="dashicons dashicons-dashboard"></span>
        <?php _e('Dashboard', 'clarity-first-seo'); ?>
      </h1>
      <p class="cfseo-subtitle">
        <?php _e('Clarity-First SEO checks what search engines can see on your site. It does not predict rankings.', 'clarity-first-seo'); ?>
      </p>
      
      <!-- Configuration Status -->
      <div class="cfseo-card" style="margin: 30px 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
        <div style="padding: 20px;">
          <h2 style="margin: 0 0 15px; color: white; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px;"></span>
            <?php _e('Configuration Status', 'clarity-first-seo'); ?>
          </h2>
          
          <div style="background: rgba(255,255,255,0.2); border-radius: 10px; height: 20px; margin-bottom: 15px; overflow: hidden;">
            <div style="background: #00a32a; height: 100%; width: <?php echo $progress_percent; ?>%; transition: width 0.3s;"></div>
          </div>
          
          <p style="margin: 0 0 20px; font-size: 16px; opacity: 0.95;">
            <strong><?php echo $completed_sections; ?> <?php _e('of', 'clarity-first-seo'); ?> <?php echo $total_sections; ?></strong> <?php _e('sections configured', 'clarity-first-seo'); ?> 
            (<?php echo $progress_percent; ?>%)
            <a href="?page=cfseo-settings" style="color: white; text-decoration: underline; margin-left: 15px; opacity: 0.9;">→ <?php _e('Go to Settings', 'clarity-first-seo'); ?></a>
          </p>
          
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
            <?php foreach ($config_status as $key => $section): ?>
              <div style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 15px; backdrop-filter: blur(10px);">
                <h3 style="margin: 0 0 10px; color: white; display: flex; align-items: center; gap: 8px; font-size: 14px;">
                  <span class="dashicons <?php echo $section['icon']; ?>"></span>
                  <?php echo $section['label']; ?>
                  <?php if ($section['completed']): ?>
                    <span class="dashicons dashicons-yes-alt" style="color: #00a32a; background: white; border-radius: 50%; font-size: 16px; width: 20px; height: 20px; line-height: 20px; margin-left: auto;"></span>
                  <?php else: ?>
                    <span class="dashicons dashicons-warning" style="color: #ffc107; background: white; border-radius: 50%; font-size: 16px; width: 20px; height: 20px; line-height: 20px; margin-left: auto;"></span>
                  <?php endif; ?>
                </h3>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.6; opacity: 0.9;">
                  <?php foreach ($section['items'] as $item => $done): ?>
                    <li style="<?php echo $done ? 'color: #c3ffd8;' : 'opacity: 0.6;'; ?>">
                      <?php echo $done ? '✓' : '○'; ?> <?php echo $item; ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      
      <!-- Diagnostics Tools -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 30px;">
        
        <!-- Site Diagnostics -->
        <div class="cfseo-card" style="background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
          <h2 style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
            <span class="dashicons dashicons-analytics" style="color: #2271b1; font-size: 24px;"></span>
            Site Diagnostics
          </h2>
          <p style="color: #646970; margin: 0 0 15px 0; line-height: 1.6;">
            Check site-wide SEO configuration including sitemaps, robots.txt, verification codes, and plugin conflicts.
          </p>
          <ul style="margin: 0 0 20px 20px; padding: 0; list-style: none; color: #50575e; line-height: 1.8;">
            <li>✓ Sitemap accessibility</li>
            <li>✓ Robots.txt validation</li>
            <li>✓ Search engine verification</li>
            <li>✓ Plugin conflict detection</li>
          </ul>
          <a href="?page=cfseo-validation" class="button button-primary button-large" style="width: 100%;">
            <span class="dashicons dashicons-yes-alt" style="margin-top: 3px;"></span> 
            Run Site Diagnostics
          </a>
        </div>
        
        <!-- Page Diagnostics -->
        <div class="cfseo-card" style="background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
          <h2 style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
            <span class="dashicons dashicons-search" style="color: #2271b1; font-size: 24px;"></span>
            Page Diagnostics
          </h2>
          <p style="color: #646970; margin: 0 0 15px 0; line-height: 1.6;">
            Inspect what search engines see on individual pages including title tags, meta descriptions, and structured data.
          </p>
          <ul style="margin: 0 0 20px 20px; padding: 0; list-style: none; color: #50575e; line-height: 1.8;">
            <li>✓ Title tags & meta descriptions</li>
            <li>✓ Canonical URLs & robots directives</li>
            <li>✓ Open Graph & Twitter cards</li>
            <li>✓ Schema markup validation</li>
          </ul>
          <a href="?page=cfseo-diagnostics" class="button button-primary button-large" style="width: 100%;">
            <span class="dashicons dashicons-visibility" style="margin-top: 3px;"></span> 
            Analyze a Page
          </a>
        </div>
        
      </div>
      
      <div class="cfseo-dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-top: 30px;">
        
        <!-- Quick Actions -->
        <div class="cfseo-card" style="background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
          <h2><span class="dashicons dashicons-admin-tools"></span> Quick Actions</h2>
          <p style="color: #646970; margin-top: 5px;">Common SEO tasks you can do right now</p>
          
          <div style="margin-top: 20px;">
            <!-- Action 1 -->
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
