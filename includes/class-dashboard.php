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

class ASNERISSEO_Dashboard {

  /**
   * Compatibility wrapper for plugin activation checks in non-admin contexts.
   */
  private static function is_plugin_active_compat($plugin_file) {
    if (function_exists('is_plugin_active')) {
      return is_plugin_active($plugin_file);
    }

    $active_plugins = (array) get_option('active_plugins', []);
    if (in_array($plugin_file, $active_plugins, true)) {
      return true;
    }

    if (is_multisite()) {
      $network_active = array_keys((array) get_site_option('active_sitewide_plugins', []));
      if (in_array($plugin_file, $network_active, true)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Ensure JSX runtime handle exists on admin pages (needed on some WP versions).
   */
  private static function ensure_react_jsx_runtime() {
    if (wp_script_is('react-jsx-runtime', 'registered')) {
      return;
    }

    wp_register_script(
      'react-jsx-runtime',
      false,
      array('react'),
      ASNERISSEO_VERSION,
      true
    );

    wp_add_inline_script('react-jsx-runtime', '
      window.ReactJSXRuntime = window.ReactJSXRuntime || {
        jsx: function(type, props, key) {
          var args = [type, props];
          if (props && props.children !== undefined) {
            if (Array.isArray(props.children)) {
              args = args.concat(props.children);
            } else {
              args.push(props.children);
            }
          }
          return React.createElement.apply(React, args);
        },
        jsxs: function(type, props, key) {
          var args = [type, props];
          if (props && props.children !== undefined) {
            if (Array.isArray(props.children)) {
              args = args.concat(props.children);
            } else {
              args.push(props.children);
            }
          }
          return React.createElement.apply(React, args);
        },
        Fragment: window.React.Fragment
      };
    ');
  }

  /**
   * Build normalized dashboard summary payload for React and REST consumers.
   */
  public static function get_dashboard_summary_payload() {
    $validation_summary = self::get_validation_summary();
    $diagnostic_summary = self::get_diagnostic_summary();
    $config_status = self::get_config_status();
    $cron_summary = self::get_cron_summary();
    $total_sections = count($config_status);
    $completed_sections = count(array_filter($config_status, function($s) { return $s['completed']; }));
    $progress_percent = $total_sections > 0 ? round(($completed_sections / $total_sections) * 100) : 0;

    return [
      'progress' => [
        'completed' => $completed_sections,
        'total' => $total_sections,
        'percent' => $progress_percent,
      ],
      'configStatus' => $config_status,
      'validation' => $validation_summary,
      'diagnostics' => $diagnostic_summary,
      'cron' => $cron_summary,
      'links' => [
        'dashboard' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG),
        'settings' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-settings'),
        'settingsPriorityPages' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-settings&tab=priorityPages'),
        'settingsPageDiagnostics' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-settings&tab=pageDiagnosticsSettings'),
        'settingsVerification' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-settings&tab=verification'),
        'settingsSocial' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-settings&tab=social'),
        'settingsSchema' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-settings&tab=schema'),
        'settingsIndexNow' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-settings&tab=indexnow'),
        'settingsTemplates' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-settings&tab=templates'),
        'settingsMaintenance' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-settings&tab=maintenance'),
        'settings404Controls' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-settings&tab=monitor404'),
        'siteDiagnostics' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-validation'),
        'pageDiagnostics' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-diagnostics'),
        'bulkEdit' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-bulk-edit'),
        'redirects' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-redirects'),
        'robots' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-robots'),
        'help' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-help'),
        'monitor404' => admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-404-monitor'),
      ],
    ];
  }

  /**
   * Build cron status summary used by dashboard cards.
   */
  private static function get_cron_summary() {
    $system_enabled = !(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON);
    $system_label = $system_enabled ? 'enabled' : 'disabled';

    $monitor_404 = [
      'frequency' => 'disabled',
      'status' => 'not_scheduled',
      'next_run_gmt' => '',
    ];

    if (class_exists('ASNERISSEO_404_Monitor') && method_exists('ASNERISSEO_404_Monitor', 'get_monitor_settings')) {
      $monitor_settings = ASNERISSEO_404_Monitor::get_monitor_settings();
      if (is_array($monitor_settings)) {
        $monitor_404['frequency'] = isset($monitor_settings['analysis_cron_frequency']) ? (string) $monitor_settings['analysis_cron_frequency'] : 'disabled';
        $monitor_404['status'] = isset($monitor_settings['analysis_cron_status']) ? (string) $monitor_settings['analysis_cron_status'] : 'not_scheduled';
        $monitor_404['next_run_gmt'] = isset($monitor_settings['analysis_next_run_gmt']) ? (string) $monitor_settings['analysis_next_run_gmt'] : '';
      }
    }

    $priority_scan = [
      'frequency' => 'disabled',
      'status' => 'not_scheduled',
      'next_run_gmt' => '',
    ];

    if (class_exists('ASNERISSEO_Page_Diagnostics_Snapshots') && method_exists('ASNERISSEO_Page_Diagnostics_Snapshots', 'get_scan_cron_details')) {
      $scan_details = ASNERISSEO_Page_Diagnostics_Snapshots::get_scan_cron_details();
      if (is_array($scan_details)) {
        $priority_scan['frequency'] = isset($scan_details['frequency']) ? (string) $scan_details['frequency'] : 'disabled';
        $priority_scan['status'] = isset($scan_details['status']) ? (string) $scan_details['status'] : 'not_scheduled';
        $priority_scan['next_run_gmt'] = isset($scan_details['next_run_gmt']) ? (string) $scan_details['next_run_gmt'] : '';
      }
    }

    return [
      'system' => [
        'enabled' => $system_enabled,
        'status' => $system_label,
      ],
      'monitor404' => $monitor_404,
      'priorityScan' => $priority_scan,
    ];
  }

  /**
   * Check whether the current admin hook is the top-level dashboard page.
   */
  private static function is_dashboard_hook($hook) {
    return $hook === 'toplevel_page_' . ASNERIS_MENU_SLUG;
  }

  /**
   * Enqueue React dashboard bundle and provide precomputed view data.
   */
  private static function enqueue_react_assets($debug_info = []) {
    $asset_path = ASNERISSEO_DIR . 'build/admin/index.asset.php';
    if (!file_exists($asset_path)) {
      return;
    }

    self::ensure_react_jsx_runtime();

    $asset = include $asset_path;

    wp_enqueue_script(
      'asnerisseo-admin-dashboard',
      ASNERISSEO_URL . 'build/admin/index.js',
      $asset['dependencies'],
      $asset['version'],
      true
    );

    $summary_payload = self::get_dashboard_summary_payload();

    wp_localize_script('asnerisseo-admin-dashboard', 'asnerisseoAdminDashboardData', [
      'summary' => $summary_payload,
      'dashboardSummaryRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/dashboard-summary' ) ),
      'socialSettingsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/social' ) ),
      'schemaSettingsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/schema' ) ),
      'indexNowSettingsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/indexnow' ) ),
      'generalSettingsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/general' ) ),
      'verificationSettingsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/verification' ) ),
      'templatesSettingsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/templates' ) ),
      'maintenanceSettingsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/maintenance' ) ),
      'pageDiagnosticsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/page-diagnostics/overview' ) ),
      'diagnosticsUrlRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/diagnostics-url' ) ),
      'siteDiagnosticsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-diagnostics' ) ),
      'siteDiagnosticsUrlCheckRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-diagnostics/url-check' ) ),
      'redirectsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/redirects' ) ),
      'robotsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/robots' ) ),
      'bulkEditContentRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/bulk-edit/content' ) ),
      'bulkEditSaveRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/bulk-edit/save' ) ),
      'restNonce' => wp_create_nonce( 'wp_rest' ),
      'logs404RestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/404-logs' ) ),
      'logs404StatsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/404-logs/stats' ) ),
      'logs404BulkRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/404-logs/bulk' ) ),
      'logs404ExportRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/404-logs/export' ) ),
      'logs404SettingsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/404-logs/settings' ) ),
      'debugInfo' => $debug_info,
      'mountSelector' => '.asnerisseo-fallback-dashboard',
      'hideFallback' => true,
    ]);
  }
  
  /**
   * Enqueue admin styles
   */
  public static function enqueue_assets($hook) {
    // WordPress uses sanitized menu TITLE (not slug) as parent identifier
    // Dashboard uses the main menu slug directly
    if (!self::is_dashboard_hook($hook)) return;
    $admin_css_path = ASNERISSEO_DIR . 'assets/css/admin-style.css';
    $admin_css_version = file_exists($admin_css_path)
      ? ASNERISSEO_VERSION . '.' . filemtime($admin_css_path)
      : ASNERISSEO_VERSION;
    $react_script_path = ASNERISSEO_DIR . 'build/admin/index.js';
    $react_script_version = file_exists($react_script_path)
      ? ASNERISSEO_VERSION . '.' . filemtime($react_script_path)
      : ASNERISSEO_VERSION;
    wp_enqueue_style('asnerisseo-admin', ASNERISSEO_URL . 'assets/css/admin-style.css', [], $admin_css_version);
    self::enqueue_react_assets([
      'hook' => (string) $hook,
      'cssVersion' => (string) $admin_css_version,
      'reactScriptVersion' => (string) $react_script_version,
      'pluginVersion' => (string) ASNERISSEO_VERSION,
      'pluginUrl' => (string) ASNERISSEO_URL,
      'source' => (string) __FILE__,
      'stamp' => (string) gmdate('c'),
    ]);
  }
  
  /**
   * Get configuration status for all sections
   */
  private static function get_config_status() {
    $settings = get_option('ASNERISSEO_settings', []);
    $priority_ids = isset($settings['priority_page_ids']) && is_array($settings['priority_page_ids'])
      ? array_values(array_unique(array_filter(array_map('absint', $settings['priority_page_ids']))))
      : [];
    $priority_feature_enabled = !empty($settings['page_diagnostics_priority_enabled']);
    $monitor_404_enabled = !empty(get_option('asnerisseo_404_enabled', 0));
    
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
      'priority_pages' => [
        'label' => 'Priority Pages',
        'icon' => 'dashicons-star-filled',
        'completed' => !empty($priority_ids),
        'items' => [
          'Priority pages selected' => !empty($priority_ids),
        ]
      ],
      'page_diagnostics_settings' => [
        'label' => 'Page Diagnostics Settings',
        'icon' => 'dashicons-admin-generic',
        'completed' => $priority_feature_enabled,
        'items' => [
          'Priority feature enabled for Page Diagnostics' => $priority_feature_enabled,
        ]
      ],
      'monitor_404' => [
        'label' => '404 Monitor',
        'icon' => 'dashicons-warning',
        'completed' => $monitor_404_enabled,
        'items' => [
          '404 monitoring enabled' => $monitor_404_enabled,
        ]
      ],
    ];
  }
  
  /**
   * Get validation summary counts
   */
  private static function get_validation_summary() {
    // Get saved validation results from database
    $saved = get_option('ASNERISSEO_validation_summary', null);
    
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
    // Check if sitemap exists (cached for 1 hour to avoid HTTP requests on every dashboard load)
    $sitemap_exists = get_transient('ASNERISSEO_sitemap_exists');
    if (false === $sitemap_exists) {
      $sitemap_exists = 0;
      $sitemap_urls = [home_url('/wp-sitemap.xml'), home_url('/sitemap.xml')];
      foreach ($sitemap_urls as $url) {
        $response = wp_remote_head($url, ['timeout' => 3]);
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
          $sitemap_exists = 1;
          break;
        }
      }
      set_transient('ASNERISSEO_sitemap_exists', $sitemap_exists, HOUR_IN_SECONDS);
    }
    $sitemap_exists = (bool) $sitemap_exists;
    
    // Check for SEO plugin conflicts
    $known_plugins = [
      'wordpress-seo/wp-seo.php' => 'Yoast SEO',
      'seo-by-rank-math/rank-math.php' => 'Rank Math',
      'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
    ];
    $active_seo_plugins = [];
    foreach ($known_plugins as $plugin_file => $plugin_name) {
      if (self::is_plugin_active_compat($plugin_file)) {
        $active_seo_plugins[] = $plugin_name;
      }
    }
    
    return [
      'sitemap_exists' => $sitemap_exists,
      'robots_txt_exists' => file_exists(ABSPATH . 'robots.txt'),
      'seo_plugin_conflicts' => count($active_seo_plugins),
      'seo_plugin_conflict_names' => $active_seo_plugins,
      'redirect_count' => self::get_redirect_count()
    ];
  }
  
  /**
   * Get redirect count
   */
  private static function get_redirect_count() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ASNERISSEO_redirects';
    
    // Check if table exists using prepared statement with caching
    $table_exists_cache_key = 'ASNERISSEO_redirect_table_exists';
    $table_exists = wp_cache_get($table_exists_cache_key);
    
    if (false === $table_exists) {
      $previous_suppress = $wpdb->suppress_errors(true);
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table name derived from $wpdb->prefix.
      $wpdb->get_var("SELECT 1 FROM {$table_name} LIMIT 1");
      $table_exists = '' === (string) $wpdb->last_error;
      $wpdb->suppress_errors($previous_suppress);
      wp_cache_set($table_exists_cache_key, $table_exists, '', 3600); // Cache for 1 hour
    }
    
    if (!$table_exists) {
      return 0;
    }
    
    // Get count with caching
    $cache_key = 'ASNERISSEO_redirect_count';
    $count = wp_cache_get($cache_key);
    
    if (false === $count) {
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Count query with proper caching
      $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ASNERISSEO_redirects WHERE status = %s", 'active'));
      wp_cache_set($cache_key, $count, '', 300); // Cache for 5 minutes
    }
    
    return $count;
  }
  
  /**
   * Render dashboard page
   */
  public static function render_page() {
    // Load help modals for this page
    ASNERISSEO_Help_Modal::render_modals('dashboard');
    
    $validation_summary = self::get_validation_summary();
    $diagnostic_summary = self::get_diagnostic_summary();
    $config_status = self::get_config_status();
    $total_sections = count($config_status);
    $completed_sections = count(array_filter($config_status, function($s) { return $s['completed']; }));
    $progress_percent = round(($completed_sections / $total_sections) * 100);
    ?>
    <div class="wrap ASNERISSEO-admin-wrap asnerisseo-dashboard-wrap">
      <div id="asnerisseo-react-dashboard-root"></div>
      <?php if ( defined( 'ASNERISSEO_REACT_ONLY_ADMIN' ) && ASNERISSEO_REACT_ONLY_ADMIN ) { ?></div><?php return; } ?>
      <div class="asnerisseo-fallback-dashboard">
      <h1>
        <span class="dashicons dashicons-dashboard"></span>
        <?php esc_html_e('Dashboard', 'asneris-seo-toolkit'); ?>
      </h1>
      <p class="ASNERISSEO-subtitle">
        <?php esc_html_e('Asneris SEO Toolkit checks what search engines can see on your site. It does not predict rankings.', 'asneris-seo-toolkit'); ?>
      </p>
      
      <!-- Configuration Status -->
      <div class="ASNERISSEO-card asnerisseo-hero-card">
        <div class="asnerisseo-hero-content">
          <h2 class="asnerisseo-hero-title">
            <span class="dashicons dashicons-admin-settings asnerisseo-hero-icon"></span>
            <?php esc_html_e('Configuration Status', 'asneris-seo-toolkit'); ?>
          </h2>
          
          <div class="asnerisseo-progress-bar">
            <div class="asnerisseo-progress-fill" style="width: <?php echo esc_attr($progress_percent); ?>%;"></div>
          </div>
          
          <p class="asnerisseo-hero-text">
            <strong><?php echo esc_html($completed_sections); ?> <?php esc_html_e('of', 'asneris-seo-toolkit'); ?> <?php echo esc_html($total_sections); ?></strong> <?php esc_html_e('sections configured', 'asneris-seo-toolkit'); ?> 
            (<?php echo esc_html($progress_percent); ?>%)
            <a href="admin.php?page=<?php echo esc_attr(ASNERIS_MENU_SLUG); ?>-settings" class="asnerisseo-hero-link">→ <?php esc_html_e('Go to Settings', 'asneris-seo-toolkit'); ?></a>
          </p>
          
          <div class="asnerisseo-checklist-grid">
            <?php foreach ($config_status as $key => $section): ?>
              <div class="asnerisseo-checklist-item">
                <h3 class="asnerisseo-checklist-title">
                  <span class="dashicons <?php echo esc_attr($section['icon']); ?>"></span>
                  <?php echo esc_html($section['label']); ?>
                  <?php if ($section['completed']): ?>
                    <span class="dashicons dashicons-yes-alt asnerisseo-status-check"></span>
                  <?php else: ?>
                    <span class="dashicons dashicons-warning asnerisseo-status-warning"></span>
                  <?php endif; ?>
                </h3>
                <ul class="asnerisseo-checklist-list">
                  <?php foreach ($section['items'] as $item => $done): ?>
                    <li class="<?php echo esc_attr( $done ? 'asnerisseo-checklist-done' : 'asnerisseo-checklist-pending' ); ?>">
                      <?php echo esc_html( $done ? '✓' : '○' ); ?> <?php echo esc_html($item); ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      
      <!-- Diagnostics Tools -->
      <div class="asnerisseo-features-grid">
        
        <!-- Site Diagnostics -->
        <div class="ASNERISSEO-card asnerisseo-feature-card">
          <h2 class="asnerisseo-feature-title">
            <span class="dashicons dashicons-analytics asnerisseo-feature-icon"></span>
            Site Diagnostics
          </h2>
          <p class="asnerisseo-feature-text">
            Check site-wide SEO configuration including sitemaps, robots.txt, verification codes, and plugin conflicts.
          </p>
          <ul class="asnerisseo-feature-list">
            <li>✓ Sitemap accessibility</li>
            <li>✓ Robots.txt validation</li>
            <li>✓ Search engine verification</li>
            <li>✓ Plugin conflict detection</li>
          </ul>
          <a href="?page=<?php echo esc_attr(ASNERIS_MENU_SLUG . '-validation'); ?>" class="button button-primary button-large asnerisseo-full-width-button">
            <span class="dashicons dashicons-yes-alt asnerisseo-button-icon"></span> 
            Run Site Diagnostics
          </a>
        </div>
        
        <!-- Page Diagnostics -->
        <div class="ASNERISSEO-card asnerisseo-feature-card">
          <h2 class="asnerisseo-feature-title">
            <span class="dashicons dashicons-search asnerisseo-feature-icon"></span>
            Page Diagnostics
          </h2>
          <p class="asnerisseo-feature-text">
            Inspect what search engines see on individual pages including title tags, meta descriptions, and structured data.
          </p>
          <ul class="asnerisseo-feature-list">
            <li>✓ Title tags & meta descriptions</li>
            <li>✓ Canonical URLs & robots directives</li>
            <li>✓ Open Graph & Twitter cards</li>
            <li>✓ Schema markup validation</li>
          </ul>
          <a href="?page=<?php echo esc_attr(ASNERIS_MENU_SLUG . '-diagnostics'); ?>" class="button button-primary button-large asnerisseo-full-width-button">
            <span class="dashicons dashicons-visibility asnerisseo-button-icon"></span> 
            Analyze a Page
          </a>
        </div>
        
      </div>
      
      <div class="asnerisseo-actions-grid">
        
        <!-- Quick Actions -->
        <div class="ASNERISSEO-card asnerisseo-feature-card">
          <h2><span class="dashicons dashicons-admin-tools"></span> Quick Actions</h2>
          <p class="asnerisseo-action-subtitle">Common SEO tasks you can do right now</p>
          
          <div class="asnerisseo-actions-list">
            <!-- Action 1 -->
            <div class="asnerisseo-action-item">
              <h3 class="asnerisseo-action-title">
                <span class="dashicons dashicons-edit asnerisseo-action-icon"></span>
                Bulk Edit Metadata
              </h3>
              <p class="asnerisseo-action-description">
                Update titles and descriptions for multiple posts at once
              </p>
              <a href="?page=<?php echo esc_attr(ASNERIS_MENU_SLUG . '-bulk-edit'); ?>" class="button button-primary button-large asnerisseo-action-button">Edit Metadata</a>
            </div>
            
            <!-- Action 3 -->
            <div class="asnerisseo-action-item">
              <h3 class="asnerisseo-action-title">
                <span class="dashicons dashicons-location asnerisseo-action-icon"></span>
                Google Business Profile
              </h3>
              <p class="asnerisseo-action-description">
                Add your business details to appear in Google Maps and local search
              </p>
              <a href="admin.php?page=<?php echo esc_attr(ASNERIS_MENU_SLUG); ?>-settings&tab=schema" class="button button-primary button-large asnerisseo-action-button">Setup Local Business</a>
            </div>
            
            <!-- Action 4 -->
            <div class="asnerisseo-action-item">
              <h3 class="asnerisseo-action-title">
                <span class="dashicons dashicons-randomize asnerisseo-action-icon"></span>
                Manage Redirects
              </h3>
              <p class="asnerisseo-action-description">
                Guide visitors to correct pages when URLs change
              </p>
              <a href="?page=<?php echo esc_attr(ASNERIS_MENU_SLUG . '-redirects'); ?>" class="button button-primary button-large asnerisseo-action-button">Manage Redirects</a>
            </div>
            
            <!-- Action 5 -->
            <div class="asnerisseo-action-item" style="border-bottom: none;">
              <h3 class="asnerisseo-action-title">
                <span class="dashicons dashicons-shield asnerisseo-action-icon"></span>
                Edit Robots.txt
              </h3>
              <p class="asnerisseo-action-description">
                Control which pages search engines can visit and read
              </p>
              <a href="?page=<?php echo esc_attr(ASNERIS_MENU_SLUG . '-robots'); ?>" class="button button-primary button-large asnerisseo-action-button">Edit Robots.txt</a>
            </div>
          </div>
        </div>
        
      </div>
      
      </div>
    </div>
    <?php
  }
}
