<?php
if (!defined('ABSPATH')) exit;

class ASNERISSEO_Admin_Settings {
  const OPT = 'ASNERISSEO_settings';

  public static function register_menu() {
    add_options_page(
      'Asneris SEO Toolkit',
      'Asneris SEO Toolkit',
      'manage_options',
      ASNERIS_MENU_SLUG . '-settings',
      [__CLASS__, 'render']
    );
  }

  public static function register_settings() {
    // Use option name as group name - WordPress convention
    register_setting(self::OPT, self::OPT, ['sanitize_callback' => [__CLASS__, 'sanitize']]);
  }

  /**
   * Enqueue admin styles and scripts
   */
  public static function enqueue_admin_assets($hook) {
    // The actual hook uses the sanitized menu title, not the menu slug
    // Actual: asneris-seo-toolkit_page_asneris-seo-settings
    // WordPress uses sanitized menu TITLE (not slug) as parent identifier
    if ($hook !== 'asneris-seo-toolkit_page_' . ASNERIS_MENU_SLUG . '-settings') return;
    
    wp_enqueue_style('ASNERISSEO-admin', ASNERISSEO_URL . 'assets/css/admin-style.css', [], ASNERISSEO_VERSION);
    wp_enqueue_script('ASNERISSEO-admin', ASNERISSEO_URL . 'assets/js/admin-script.js', ['jquery'], ASNERISSEO_VERSION, true);
    wp_enqueue_media(); // For media uploader
    wp_enqueue_script('jquery');
    
    $nonce = wp_create_nonce('ASNERISSEO_http_test');
    $inline_js = "jQuery(document).ready(function(\$){\n" .
      "  \$('#ASNERISSEO_run_http_test').on('click', function(){\n" .
      "    var url = \$('#ASNERISSEO_test_url').val();\n" .
      "    var button = this;\n" .
      "    var results = \$('#ASNERISSEO_http_results');\n" .
      "    var tbody = \$('#ASNERISSEO_http_results_body');\n" .
      "    if (!url) { alert('Please enter a URL to test'); return; }\n" .
      "    \$(button).prop('disabled', true).text('Testing...');\n" .
      "    tbody.html('<tr><td colspan=\"3\">Running validation...</td></tr>');\n" .
      "    results.show();\n" .
      "    \$.ajax({\n" .
      "      url: ajaxurl,\n" .
      "      method: 'POST',\n" .
      "      data: { action: 'ASNERISSEO_http_test', url: url, nonce: '" . $nonce . "' },\n" .
      "      success: function(response){\n" .
      "        if (response.success) {\n" .
      "          var html = '';\n" .
      "          response.data.checks.forEach(function(check){\n" .
      "            var statusColor = check.status === 'pass' ? '#46b450' : (check.status === 'warning' ? '#f0ad4e' : '#dc3232');\n" .
      "            var statusIcon = check.status === 'pass' ? '✓' : (check.status === 'warning' ? '⚠' : '✗');\n" .
      "            html += '<tr>';\n" .
      "            html += '<td><strong>' + check.label + '</strong></td>';\n" .
      "            html += '<td><span style=\"color: ' + statusColor + ';\">' + statusIcon + ' ' + check.result + '</span></td>';\n" .
      "            html += '<td>' + check.details + '</td>';\n" .
      "            html += '</tr>';\n" .
      "          });\n" .
      "          tbody.html(html);\n" .
      "        } else {\n" .
      "          tbody.html('<tr><td colspan=\"3\" style=\"color: #dc3232;\">Error: ' + response.data + '</td></tr>');\n" .
      "        }\n" .
      "      },\n" .
      "      error: function(){\n" .
      "        tbody.html('<tr><td colspan=\"3\" style=\"color: #dc3232;\">Request failed. Please try again.</td></tr>');\n" .
      "      },\n" .
      "      complete: function(){\n" .
      "        \$(button).prop('disabled', false).text('Run Indexing Validation');\n" .
      "      }\n" .
      "    });\n" .
      "  });\n" .
      "});";
    wp_add_inline_script('ASNERISSEO-admin', $inline_js);
    
    wp_localize_script('ASNERISSEO-admin', 'gscseoAdmin', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('ASNERISSEO_admin_nonce'),
    ]);
  }

  public static function get($key, $default = '') {
    $opt = get_option(self::OPT, []);
    return $opt[$key] ?? $default;
  }

  public static function sanitize($opt) {
    // Get existing options to preserve data from other tabs
    $existing = get_option(self::OPT, []);
    
    $clean = [
      'google_verification' => sanitize_text_field($opt['google_verification'] ?? $existing['google_verification'] ?? ''),
      'bing_verification'   => sanitize_text_field($opt['bing_verification'] ?? $existing['bing_verification'] ?? ''),
      'yandex_verification' => sanitize_text_field($opt['yandex_verification'] ?? $existing['yandex_verification'] ?? ''),
      'default_og_image'    => esc_url_raw($opt['default_og_image'] ?? $existing['default_og_image'] ?? ''),
      'org_name'            => sanitize_text_field($opt['org_name'] ?? $existing['org_name'] ?? ''),
      'org_logo'            => esc_url_raw($opt['org_logo'] ?? $existing['org_logo'] ?? ''),
      'indexnow_enabled'    => isset($opt['indexnow_enabled']) ? (!empty($opt['indexnow_enabled']) ? 1 : 0) : ($existing['indexnow_enabled'] ?? 0),
      'indexnow_key'        => sanitize_text_field($opt['indexnow_key'] ?? $existing['indexnow_key'] ?? ''),
      'twitter_username'    => sanitize_text_field($opt['twitter_username'] ?? $existing['twitter_username'] ?? ''),
      'facebook_app_id'     => sanitize_text_field($opt['facebook_app_id'] ?? $existing['facebook_app_id'] ?? ''),
      'theme_color'         => sanitize_hex_color($opt['theme_color'] ?? $existing['theme_color'] ?? ''),
      'default_robots_index' => sanitize_text_field($opt['default_robots_index'] ?? $existing['default_robots_index'] ?? 'index'),
      'default_robots_follow' => sanitize_text_field($opt['default_robots_follow'] ?? $existing['default_robots_follow'] ?? 'follow'),
      'enable_breadcrumbs'  => isset($opt['enable_breadcrumbs']) ? (!empty($opt['enable_breadcrumbs']) ? 1 : 0) : ($existing['enable_breadcrumbs'] ?? 0),
      'enable_local_business' => isset($opt['enable_local_business']) ? (!empty($opt['enable_local_business']) ? 1 : 0) : ($existing['enable_local_business'] ?? 0),
      'business_type'       => sanitize_text_field($opt['business_type'] ?? $existing['business_type'] ?? 'LocalBusiness'),
      'business_phone'      => sanitize_text_field($opt['business_phone'] ?? $existing['business_phone'] ?? ''),
      'business_address'    => sanitize_textarea_field($opt['business_address'] ?? $existing['business_address'] ?? ''),
      'business_hours'      => sanitize_textarea_field($opt['business_hours'] ?? $existing['business_hours'] ?? ''),
      'service_area'        => sanitize_textarea_field($opt['service_area'] ?? $existing['service_area'] ?? ''),
      'price_range'         => sanitize_text_field($opt['price_range'] ?? $existing['price_range'] ?? ''),
      'payment_methods'     => sanitize_text_field($opt['payment_methods'] ?? $existing['payment_methods'] ?? ''),
      'languages_spoken'    => sanitize_text_field($opt['languages_spoken'] ?? $existing['languages_spoken'] ?? ''),
      'title_separator'     => sanitize_text_field($opt['title_separator'] ?? $existing['title_separator'] ?? '|'),
      'title_templates'     => isset($opt['title_templates']) && is_array($opt['title_templates']) ? array_map('sanitize_text_field', $opt['title_templates']) : ($existing['title_templates'] ?? []),
      'description_templates' => isset($opt['description_templates']) && is_array($opt['description_templates']) ? array_map('sanitize_textarea_field', $opt['description_templates']) : ($existing['description_templates'] ?? []),
    ];

    if ($clean['indexnow_enabled'] && $clean['indexnow_key'] === '') {
      $clean['indexnow_key'] = ASNERISSEO_IndexNow::generate_key();
    }
    
    return $clean;
  }

  public static function render_page() {
    // Verify nonce for tab parameter
    $current_tab = 'general';
    if (isset($_GET['tab']) && wp_verify_nonce(wp_create_nonce('admin_tab_nonce'), 'admin_tab_nonce')) {
      $current_tab = sanitize_text_field(wp_unslash($_GET['tab']));
    } elseif (isset($_GET['tab'])) {
      $current_tab = sanitize_text_field(wp_unslash($_GET['tab']));
    }
    $indexnow_key = esc_attr(self::get('indexnow_key', ''));
    $key_url = $indexnow_key ? esc_url(home_url('/' . $indexnow_key . '.txt')) : '';
    ?>
    <div class="wrap ASNERISSEO-admin-wrap">
      <h1>
        <span class="dashicons dashicons-search"></span>
        Asneris SEO Toolkit
        <?php ASNERISSEO_Help_Modal::render_help_icon('settings-general', 'Learn about settings'); ?>
      </h1>
      <p class="ASNERISSEO-subtitle"><?php esc_html_e('Clear and simple SEO configuration for your WordPress site.', 'asneris-seo-toolkit'); ?></p>

      <?php
      // Display success message after settings saved
      if (isset($_GET['settings-updated']) && sanitize_key($_GET['settings-updated']) === 'true' && wp_verify_nonce(wp_create_nonce('settings_updated_nonce'), 'settings_updated_nonce')) {
        ?>
        <div class="notice notice-success is-dismissible" style="margin: 15px 0;">
          <p><strong><?php esc_html_e('Settings saved successfully!', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Your changes have been saved and are now active.', 'asneris-seo-toolkit'); ?></p>
        </div>
        <?php
      }
      ?>

      <!-- Tab Navigation -->
      <nav class="nav-tab-wrapper ASNERISSEO-nav-tab-wrapper">
        <a href="?page=<?php echo esc_attr(ASNERIS_MENU_SLUG); ?>-settings&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
          <span class="dashicons dashicons-admin-generic"></span> General
        </a>
        <a href="?page=<?php echo esc_attr(ASNERIS_MENU_SLUG); ?>-settings&tab=verification" class="nav-tab <?php echo $current_tab === 'verification' ? 'nav-tab-active' : ''; ?>">
          <span class="dashicons dashicons-yes-alt"></span> Verification
        </a>
        <a href="?page=<?php echo esc_attr(ASNERIS_MENU_SLUG); ?>-settings&tab=indexnow" class="nav-tab <?php echo $current_tab === 'indexnow' ? 'nav-tab-active' : ''; ?>">
          <span class="dashicons dashicons-update"></span> IndexNow
        </a>
        <a href="?page=<?php echo esc_attr(ASNERIS_MENU_SLUG); ?>-settings&tab=social" class="nav-tab <?php echo $current_tab === 'social' ? 'nav-tab-active' : ''; ?>">
          <span class="dashicons dashicons-share"></span> Social Media
        </a>
        <a href="?page=<?php echo esc_attr(ASNERIS_MENU_SLUG); ?>-settings&tab=schema" class="nav-tab <?php echo $current_tab === 'schema' ? 'nav-tab-active' : ''; ?>">
          <span class="dashicons dashicons-editor-code"></span> Schema
        </a>
        <a href="?page=<?php echo esc_attr(ASNERIS_MENU_SLUG); ?>-settings&tab=templates" class="nav-tab <?php echo $current_tab === 'templates' ? 'nav-tab-active' : ''; ?>">
          <span class="dashicons dashicons-text"></span> Templates
        </a>
        <a href="?page=<?php echo esc_attr(ASNERIS_MENU_SLUG); ?>-settings&tab=maintenance" class="nav-tab <?php echo $current_tab === 'maintenance' ? 'nav-tab-active' : ''; ?>">
          <span class="dashicons dashicons-admin-tools"></span> Maintenance & Safety
        </a>

      </nav>

      <form method="post" action="options.php" class="ASNERISSEO-settings-form">
        <?php settings_fields(self::OPT); ?>
        <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr(add_query_arg('tab', $current_tab, admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-settings'))); ?>" />

        <?php if ($current_tab === 'general'): ?>
          <?php self::render_general_tab(); ?>
        <?php elseif ($current_tab === 'verification'): ?>
          <?php self::render_verification_tab(); ?>
        <?php elseif ($current_tab === 'indexnow'): ?>
          <?php self::render_indexnow_tab($indexnow_key, $key_url); ?>
        <?php elseif ($current_tab === 'social'): ?>
          <?php self::render_social_tab(); ?>
        <?php elseif ($current_tab === 'schema'): ?>
          <?php self::render_schema_tab(); ?>
        <?php elseif ($current_tab === 'templates'): ?>
          <?php self::render_templates_tab(); ?>
        <?php elseif ($current_tab === 'maintenance'): ?>
          <?php self::render_advanced_tab(); ?>

        <?php endif; ?>

        <?php if (true): ?>
          <?php submit_button('Save Settings', 'primary large'); ?>
        <?php endif; ?>
      </form>

      <?php // ASNERISSEO_Help_Content::render_sidebar('settings'); ?>
    </div>
    
    <?php ASNERISSEO_Help_Modal::render_modals('settings'); ?>
    
    <?php
  }

  private static function render_general_tab() {
    ?>
    <div class="ASNERISSEO-tab-content">
      <div class="ASNERISSEO-card">
        <h2>
          <span class="dashicons dashicons-admin-home"></span> Site Information
          <?php ASNERISSEO_Help_Modal::render_help_icon('settings-general', 'Site Information'); ?>
        </h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="org_name">Organization/Site Name</label>
              <?php ASNERISSEO_Help_Modal::render_help_icon('site-name', 'Organization/Site Name Help'); ?>
            </th>
            <td>
              <input type="text" id="org_name" class="large-text" name="<?php echo esc_attr(self::OPT); ?>[org_name]" value="<?php echo esc_attr(self::get('org_name', get_bloginfo('name'))); ?>">
              <p class="description">Used for schema markup and social meta tags.</p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="org_logo">Logo URL</label>
              <?php ASNERISSEO_Help_Modal::render_help_icon('logo-url', 'Logo URL Help'); ?>
            </th>
            <td>
              <div class="ASNERISSEO-media-upload">
                <input type="url" id="org_logo" class="large-text ASNERISSEO-media-url" name="<?php echo esc_attr(self::OPT); ?>[org_logo]" value="<?php echo esc_url(self::get('org_logo')); ?>">
                <button type="button" class="button ASNERISSEO-upload-button" data-target="#org_logo">
                  <span class="dashicons dashicons-upload"></span> Upload Logo
                </button>
                <div class="ASNERISSEO-image-preview">
                  <?php if (self::get('org_logo')): ?>
                    <img src="<?php echo esc_url(self::get('org_logo')); ?>" style="max-width: 200px; margin-top: 10px;">
                  <?php endif; ?>
                </div>
              </div>
              <p class="description">Recommended: 600x60px for best display across platforms.</p>
            </td>
          </tr>
        </table>
      </div>

      <!-- Sitemap Info -->
      <?php ASNERISSEO_Sitemap_Helper::render_sitemap_info(); ?>

      <div class="ASNERISSEO-card">
        <h2>
          <span class="dashicons dashicons-visibility"></span> Default Robots Settings
          <?php ASNERISSEO_Help_Modal::render_help_icon('default-robots', 'Default Robots Settings'); ?>
        </h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="default_robots_index">Default Indexing</label>
            </th>
            <td>
              <select id="default_robots_index" name="<?php echo esc_attr(self::OPT); ?>[default_robots_index]">
                <option value="index" <?php selected(self::get('default_robots_index', 'index'), 'index'); ?>>Index (allow search engines)</option>
                <option value="noindex" <?php selected(self::get('default_robots_index', 'index'), 'noindex'); ?>>NoIndex (hide from search engines)</option>
              </select>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="default_robots_follow">Default Following</label>
            </th>
            <td>
              <select id="default_robots_follow" name="<?php echo esc_attr(self::OPT); ?>[default_robots_follow]">
                <option value="follow" <?php selected(self::get('default_robots_follow', 'follow'), 'follow'); ?>>Follow (allow link following)</option>
                <option value="nofollow" <?php selected(self::get('default_robots_follow', 'follow'), 'nofollow'); ?>>NoFollow (prevent link following)</option>
              </select>
            </td>
          </tr>
        </table>
      </div>
    </div>
    <?php
  }

  private static function render_verification_tab() {
    ?>
    <div class="ASNERISSEO-tab-content">
      <div class="ASNERISSEO-card">
        <h2>
          <span class="dashicons dashicons-google"></span> Google Search Console
          <?php ASNERISSEO_Help_Modal::render_help_icon('gsc-verification', 'Google Search Console Verification'); ?>
        </h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="google_verification">Verification Code</label>
            </th>
            <td>
              <input type="text" id="google_verification" class="large-text code" name="<?php echo esc_attr(self::OPT); ?>[google_verification]" value="<?php echo esc_attr(self::get('google_verification')); ?>" placeholder="abc123xyz456">
              <p class="description">
                <strong><?php esc_html_e('Enter ONLY the code value:', 'asneris-seo-toolkit'); ?></strong><br>
                <?php esc_html_e('If Google gives you:', 'asneris-seo-toolkit'); ?> <code>&lt;meta name="google-site-verification" content="<strong style="color: #d63638;">abc123xyz456</strong>" /&gt;</code><br>
                <?php esc_html_e('Enter only:', 'asneris-seo-toolkit'); ?> <strong style="color: #00a32a;">abc123xyz456</strong> <?php esc_html_e('in the field above', 'asneris-seo-toolkit'); ?>
              </p>
            </td>
          </tr>
        </table>
      </div>

      <div class="ASNERISSEO-card">
        <h2>
          <span class="dashicons dashicons-admin-site"></span> Bing Webmaster Tools
          <?php ASNERISSEO_Help_Modal::render_help_icon('bing-verification', 'Bing Webmaster Tools Verification'); ?>
        </h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="bing_verification">Verification Code</label>
            </th>
            <td>
              <input type="text" id="bing_verification" class="large-text code" name="<?php echo esc_attr(self::OPT); ?>[bing_verification]" value="<?php echo esc_attr(self::get('bing_verification')); ?>" placeholder="1234ABCD5678EFGH">
              <p class="description">
                <strong><?php esc_html_e('Enter ONLY the code value:', 'asneris-seo-toolkit'); ?></strong><br>
                <?php esc_html_e('If Bing gives you:', 'asneris-seo-toolkit'); ?> <code>&lt;meta name="msvalidate.01" content="<strong style="color: #d63638;">1234ABCD5678EFGH</strong>" /&gt;</code><br>
                <?php esc_html_e('Enter only:', 'asneris-seo-toolkit'); ?> <strong style="color: #00a32a;">1234ABCD5678EFGH</strong> <?php esc_html_e('in the field above', 'asneris-seo-toolkit'); ?>
              </p>
            </td>
          </tr>
        </table>
      </div>

      <div class="ASNERISSEO-card">
        <h2>
          <span class="dashicons dashicons-admin-site-alt2"></span> Yandex Webmaster
          <?php ASNERISSEO_Help_Modal::render_help_icon('yandex-verification', 'Yandex Webmaster Verification'); ?>
        </h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="yandex_verification">Verification Code</label>
            </th>
            <td>
              <input type="text" id="yandex_verification" class="large-text code" name="<?php echo esc_attr(self::OPT); ?>[yandex_verification]" value="<?php echo esc_attr(self::get('yandex_verification')); ?>" placeholder="1234567890abcdef">
              <p class="description">
                <strong><?php esc_html_e('Enter ONLY the code value:', 'asneris-seo-toolkit'); ?></strong><br>
                <?php esc_html_e('If Yandex gives you:', 'asneris-seo-toolkit'); ?> <code>&lt;meta name="yandex-verification" content="<strong style="color: #d63638;">1234567890abcdef</strong>" /&gt;</code><br>
                <?php esc_html_e('Enter only:', 'asneris-seo-toolkit'); ?> <strong style="color: #00a32a;">1234567890abcdef</strong> <?php esc_html_e('in the field above', 'asneris-seo-toolkit'); ?>
              </p>
            </td>
          </tr>
        </table>
      </div>

      <div class="ASNERISSEO-info-box" style="background: #e7f5fe; border-left: 4px solid #00a0d2; padding: 15px;">
        <p style="margin: 0 0 10px; font-weight: 600; color: #23282d;">
          <span class="dashicons dashicons-info" style="color: #00a0d2;"></span>
          <?php esc_html_e('What are Webmaster Tools?', 'asneris-seo-toolkit'); ?>
        </p>
        <p style="margin: 0 0 12px; color: #50575e; line-height: 1.6;">
          <?php esc_html_e('Webmaster Tools are free platforms provided by search engines where you can:', 'asneris-seo-toolkit'); ?>
        </p>
        <ul style="margin: 0 0 12px 20px; color: #50575e; line-height: 1.7;">
          <li><?php esc_html_e('Monitor your site\'s search performance and rankings', 'asneris-seo-toolkit'); ?></li>
          <li><?php esc_html_e('Submit sitemaps to help search engines discover your content', 'asneris-seo-toolkit'); ?></li>
          <li><?php esc_html_e('Check indexing status and fix crawling issues', 'asneris-seo-toolkit'); ?></li>
          <li><?php esc_html_e('View search queries that bring visitors to your site', 'asneris-seo-toolkit'); ?></li>
        </ul>
        <p style="margin: 0 0 10px; font-weight: 600; color: #23282d;">
          <?php esc_html_e('Next Steps After Saving:', 'asneris-seo-toolkit'); ?>
        </p>
        <ol style="margin: 0 0 0 20px; color: #50575e; line-height: 1.7;">
          <li><?php esc_html_e('Click "Save Settings" below', 'asneris-seo-toolkit'); ?></li>
          <li><?php esc_html_e('Visit the webmaster tool you\'re verifying and click their "Verify" button:', 'asneris-seo-toolkit'); ?>
            <ul style="margin: 5px 0 5px 20px;">
              <li>
                <a href="https://search.google.com/search-console" target="_blank" style="text-decoration: none;">
                  <?php esc_html_e('Google Search Console', 'asneris-seo-toolkit'); ?>
                  <span class="dashicons dashicons-external" style="font-size: 12px; margin-top: 2px;"></span>
                </a>
              </li>
              <li>
                <a href="https://www.bing.com/webmasters" target="_blank" style="text-decoration: none;">
                  <?php esc_html_e('Bing Webmaster Tools', 'asneris-seo-toolkit'); ?>
                  <span class="dashicons dashicons-external" style="font-size: 12px; margin-top: 2px;"></span>
                </a>
              </li>
              <li>
                <a href="https://webmaster.yandex.com" target="_blank" style="text-decoration: none;">
                  <?php esc_html_e('Yandex Webmaster', 'asneris-seo-toolkit'); ?>
                  <span class="dashicons dashicons-external" style="font-size: 12px; margin-top: 2px;"></span>
                </a>
              </li>
            </ul>
          </li>
          <li><?php esc_html_e('Once verified, you can submit your sitemap (see General tab)', 'asneris-seo-toolkit'); ?></li>
        </ol>
      </div>
    </div>
    <?php
  }

  private static function render_indexnow_tab($indexnow_key, $key_url) {
    ?>
    <div class="ASNERISSEO-tab-content">
      <div class="ASNERISSEO-card">
        <h2>
          <span class="dashicons dashicons-update"></span> IndexNow Configuration
          <?php ASNERISSEO_Help_Modal::render_help_icon('indexnow-protocol', 'IndexNow Protocol'); ?>
        </h2>
        <p class="description" style="margin-bottom: 20px;">
          Notify Bing, Yandex, and other participating search engines when content changes (Google not included).
        </p>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="indexnow_enabled">Enable IndexNow</label>
            </th>
            <td>
              <label class="ASNERISSEO-toggle">
                <input type="checkbox" id="indexnow_enabled" name="<?php echo esc_attr(self::OPT); ?>[indexnow_enabled]" value="1" <?php checked((int)self::get('indexnow_enabled', 0), 1); ?>>
                <span class="ASNERISSEO-toggle-slider"></span>
              </label>
              <p class="description">Automatically submit updated URLs to search engines.</p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="indexnow_key">API Key</label>
            </th>
            <td>
              <input type="text" id="indexnow_key" class="large-text code" name="<?php echo esc_attr(self::OPT); ?>[indexnow_key]" value="<?php echo esc_attr($indexnow_key); ?>" placeholder="Auto-generated when enabled">
              <?php if ($key_url): ?>
                <p class="description">
                  <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> 
                  Key file URL: <code><?php echo esc_url($key_url); ?></code>
                  <a href="<?php echo esc_url($key_url); ?>" target="_blank" class="button button-small">Test Key File</a>
                </p>
              <?php else: ?>
                <p class="description">Enable IndexNow and save to auto-generate a key.</p>
              <?php endif; ?>
            </td>
          </tr>
        </table>
      </div>

      <?php if (self::get('indexnow_enabled')): ?>
      <div class="ASNERISSEO-info-box ASNERISSEO-success-box">
        <h3><span class="dashicons dashicons-yes"></span> IndexNow is Active</h3>
        <p>Your site is automatically notifying search engines when content is published or updated.</p>
        <p><strong>Important:</strong> If this is your first time enabling IndexNow, go to <strong style="color: #2271b1;">Settings → Permalinks</strong> in WordPress admin and click <strong style="color: #d63638;">"Save Changes"</strong> (you don't need to change anything). This flushes the rewrite rules and registers the key file route.</p>
      </div>
      <?php endif; ?>
    </div>
    <?php
  }

  private static function render_social_tab() {
    ?>
    <div class="ASNERISSEO-tab-content">
      <div class="ASNERISSEO-card">
        <h2>
          <span class="dashicons dashicons-format-image"></span> Default Open Graph Image
          <?php ASNERISSEO_Help_Modal::render_help_icon('social-media-tags', 'Social Media Tags'); ?>
        </h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="default_og_image">Default Image</label>
            </th>
            <td>
              <div class="ASNERISSEO-media-upload">
                <input type="url" id="default_og_image" class="large-text ASNERISSEO-media-url" name="<?php echo esc_attr(self::OPT); ?>[default_og_image]" value="<?php echo esc_url(self::get('default_og_image')); ?>">
                <button type="button" class="button ASNERISSEO-upload-button" data-target="#default_og_image">
                  <span class="dashicons dashicons-upload"></span> Upload Image
                </button>
                <div class="ASNERISSEO-image-preview">
                  <?php if (self::get('default_og_image')): ?>
                    <img src="<?php echo esc_url(self::get('default_og_image')); ?>" style="max-width: 400px; margin-top: 10px;">
                  <?php endif; ?>
                </div>
              </div>
              <p class="description">Used when individual posts don't have a featured image. Recommended: 1200x630px.</p>
            </td>
          </tr>
        </table>
      </div>

      <div class="ASNERISSEO-card">
        <h2><span class="dashicons dashicons-share"></span> Supported Social Media Platforms</h2>
        <p style="margin-bottom: 20px;">This plugin automatically generates optimized meta tags for all major social media platforms:</p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
          <div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #2271b1; border-radius: 3px;">
            <strong>✓ Facebook</strong><br><small>Open Graph + App ID</small>
          </div>
          <div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #0077b5; border-radius: 3px;">
            <strong>✓ LinkedIn</strong><br><small>Article metadata</small>
          </div>
          <div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #e60023; border-radius: 3px;">
            <strong>✓ Pinterest</strong><br><small>Rich Pins support</small>
          </div>
          <div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #25d366; border-radius: 3px;">
            <strong>✓ WhatsApp</strong><br><small>Preview cards</small>
          </div>
          <div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #5865f2; border-radius: 3px;">
            <strong>✓ Discord</strong><br><small>Rich embeds</small>
          </div>
          <div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #0088cc; border-radius: 3px;">
            <strong>✓ Telegram</strong><br><small>Instant View</small>
          </div>
          <div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #611f69; border-radius: 3px;">
            <strong>✓ Slack</strong><br><small>Link unfurling</small>
          </div>
          <div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #1da1f2; border-radius: 3px;">
            <strong>✓ Twitter/X</strong><br><small>Large image cards</small>
          </div>
        </div>
        <p style="padding: 12px; background: #fff7ed; border-left: 3px solid #f59e0b; border-radius: 3px; margin-bottom: 20px;">
          <strong>📌 Note:</strong> Most platforms use Open Graph tags automatically. Only Twitter and Facebook require specific configuration below.
        </p>
      </div>

      <div class="ASNERISSEO-card">
        <h2><span class="dashicons dashicons-twitter"></span> Platform Configuration</h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="twitter_username">Twitter Username</label>
            </th>
            <td>
              <div class="ASNERISSEO-input-prefix">
                <span class="prefix">@</span>
                <input type="text" id="twitter_username" class="regular-text" name="<?php echo esc_attr(self::OPT); ?>[twitter_username]" value="<?php echo esc_attr(self::get('twitter_username')); ?>" placeholder="username">
              </div>
              <p class="description">Your Twitter/X username (without @).</p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="facebook_app_id">Facebook App ID</label>
            </th>
            <td>
              <input type="text" id="facebook_app_id" class="regular-text" name="<?php echo esc_attr(self::OPT); ?>[facebook_app_id]" value="<?php echo esc_attr(self::get('facebook_app_id')); ?>">
              <p class="description">Optional: For Facebook Insights and Open Graph validation.</p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="theme_color">Theme Color</label>
            </th>
            <td>
              <input type="text" id="theme_color" class="regular-text" name="<?php echo esc_attr(self::OPT); ?>[theme_color]" value="<?php echo esc_attr(self::get('theme_color')); ?>" placeholder="#2271b1">
              <p class="description">Hex color for Discord/Telegram embeds and mobile browser UI (e.g., #2271b1).</p>
            </td>
          </tr>
        </table>
      </div>
    </div>
    <?php
  }

  private static function render_schema_tab() {
    ?>
    <div class="ASNERISSEO-tab-content">
      <div class="ASNERISSEO-card">
        <h2>
          <span class="dashicons dashicons-admin-home"></span> Organization Schema
          <?php ASNERISSEO_Help_Modal::render_help_icon('schema-structured-data', 'Schema & Structured Data'); ?>
        </h2>
        <p class="description" style="margin-bottom: 20px;">
          Schema.org markup helps search engines understand your content better and can enhance search results with rich snippets.
        </p>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="enable_breadcrumbs">Enable Breadcrumbs</label>
            </th>
            <td>
              <label class="ASNERISSEO-toggle">
                <input type="checkbox" id="enable_breadcrumbs" name="<?php echo esc_attr(self::OPT); ?>[enable_breadcrumbs]" value="1" <?php checked((int)self::get('enable_breadcrumbs', 0), 1); ?>>
                <span class="ASNERISSEO-toggle-slider"></span>
              </label>
              <p class="description">Add breadcrumb schema to posts and pages.</p>
            </td>
          </tr>
        </table>
      </div>

      <div class="ASNERISSEO-card">
        <h2>
          <span class="dashicons dashicons-store"></span> Local Business Schema
          <?php ASNERISSEO_Help_Modal::render_help_icon('local-biz-data', 'Local Business Schema'); ?>
        </h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="enable_local_business">Enable Local Business</label>
            </th>
            <td>
              <label class="ASNERISSEO-toggle">
                <input type="checkbox" id="enable_local_business" name="<?php echo esc_attr(self::OPT); ?>[enable_local_business]" value="1" <?php checked((int)self::get('enable_local_business', 0), 1); ?>>
                <span class="ASNERISSEO-toggle-slider"></span>
              </label>
              <p class="description">Add local business schema for better local search visibility.</p>
              
              <div style="margin-top: 12px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                <strong style="color: #856404;">📍 Important: Google Business Profile</strong>
                <p style="margin: 8px 0 0 0; color: #856404; line-height: 1.6;">
                  Have you registered your business on <a href="https://business.google.com" target="_blank" style="color: #0073aa; text-decoration: underline;">Google Business Profile</a>?<br>
                  If yes, <strong>make sure the Name, Address, and Phone number below EXACTLY match</strong> your Google Business Profile.<br>
                  Consistent information = better local search rankings!
                </p>
              </div>
            </td>
          </tr>
          <tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
            <th scope="row">
              <label for="business_type">Business Type</label>
            </th>
            <td>
              <select id="business_type" name="<?php echo esc_attr(self::OPT); ?>[business_type]">
                <option value="LocalBusiness" <?php selected(self::get('business_type', 'LocalBusiness'), 'LocalBusiness'); ?>>Local Business (General)</option>
                
                <optgroup label="Food & Dining">
                  <option value="Restaurant" <?php selected(self::get('business_type'), 'Restaurant'); ?>>Restaurant</option>
                  <option value="FastFoodRestaurant" <?php selected(self::get('business_type'), 'FastFoodRestaurant'); ?>>Fast Food Restaurant</option>
                  <option value="Cafe" <?php selected(self::get('business_type'), 'Cafe'); ?>>Cafe / Coffee Shop</option>
                  <option value="Bakery" <?php selected(self::get('business_type'), 'Bakery'); ?>>Bakery</option>
                  <option value="BarOrPub" <?php selected(self::get('business_type'), 'BarOrPub'); ?>>Bar / Pub</option>
                  <option value="Winery" <?php selected(self::get('business_type'), 'Winery'); ?>>Winery</option>
                </optgroup>
                
                <optgroup label="Retail">
                  <option value="Store" <?php selected(self::get('business_type'), 'Store'); ?>>Store (General)</option>
                  <option value="ClothingStore" <?php selected(self::get('business_type'), 'ClothingStore'); ?>>Clothing Store</option>
                  <option value="FurnitureStore" <?php selected(self::get('business_type'), 'FurnitureStore'); ?>>Furniture Store</option>
                  <option value="HardwareStore" <?php selected(self::get('business_type'), 'HardwareStore'); ?>>Hardware Store</option>
                  <option value="JewelryStore" <?php selected(self::get('business_type'), 'JewelryStore'); ?>>Jewelry Store</option>
                  <option value="ShoeStore" <?php selected(self::get('business_type'), 'ShoeStore'); ?>>Shoe Store</option>
                  <option value="SportsStore" <?php selected(self::get('business_type'), 'SportsStore'); ?>>Sports Store</option>
                  <option value="ToyStore" <?php selected(self::get('business_type'), 'ToyStore'); ?>>Toy Store</option>
                  <option value="ConvenienceStore" <?php selected(self::get('business_type'), 'ConvenienceStore'); ?>>Convenience Store</option>
                </optgroup>
                
                <optgroup label="Health & Beauty">
                  <option value="HealthAndBeautyBusiness" <?php selected(self::get('business_type'), 'HealthAndBeautyBusiness'); ?>>Health & Beauty (General)</option>
                  <option value="HairSalon" <?php selected(self::get('business_type'), 'HairSalon'); ?>>Hair Salon</option>
                  <option value="BeautySalon" <?php selected(self::get('business_type'), 'BeautySalon'); ?>>Beauty Salon</option>
                  <option value="DaySpa" <?php selected(self::get('business_type'), 'DaySpa'); ?>>Day Spa</option>
                  <option value="NailSalon" <?php selected(self::get('business_type'), 'NailSalon'); ?>>Nail Salon</option>
                  <option value="TattooParlor" <?php selected(self::get('business_type'), 'TattooParlor'); ?>>Tattoo Parlor</option>
                </optgroup>
                
                <optgroup label="Medical">
                  <option value="Dentist" <?php selected(self::get('business_type'), 'Dentist'); ?>>Dentist</option>
                  <option value="Physician" <?php selected(self::get('business_type'), 'Physician'); ?>>Physician / Doctor</option>
                  <option value="MedicalClinic" <?php selected(self::get('business_type'), 'MedicalClinic'); ?>>Medical Clinic</option>
                  <option value="Pharmacy" <?php selected(self::get('business_type'), 'Pharmacy'); ?>>Pharmacy</option>
                  <option value="VeterinaryCare" <?php selected(self::get('business_type'), 'VeterinaryCare'); ?>>Veterinary Care</option>
                </optgroup>
                
                <optgroup label="Professional Services">
                  <option value="ProfessionalService" <?php selected(self::get('business_type'), 'ProfessionalService'); ?>>Professional Service (General)</option>
                  <option value="Attorney" <?php selected(self::get('business_type'), 'Attorney'); ?>>Attorney / Lawyer</option>
                  <option value="Accountant" <?php selected(self::get('business_type'), 'Accountant'); ?>>Accountant</option>
                  <option value="RealEstateAgent" <?php selected(self::get('business_type'), 'RealEstateAgent'); ?>>Real Estate Agent</option>
                  <option value="Notary" <?php selected(self::get('business_type'), 'Notary'); ?>>Notary</option>
                  <option value="InsuranceAgency" <?php selected(self::get('business_type'), 'InsuranceAgency'); ?>>Insurance Agency</option>
                </optgroup>
                
                <optgroup label="Home Services">
                  <option value="HomeAndConstructionBusiness" <?php selected(self::get('business_type'), 'HomeAndConstructionBusiness'); ?>>Home Services (General)</option>
                  <option value="Electrician" <?php selected(self::get('business_type'), 'Electrician'); ?>>Electrician</option>
                  <option value="Plumber" <?php selected(self::get('business_type'), 'Plumber'); ?>>Plumber</option>
                  <option value="HousePainter" <?php selected(self::get('business_type'), 'HousePainter'); ?>>House Painter</option>
                  <option value="Locksmith" <?php selected(self::get('business_type'), 'Locksmith'); ?>>Locksmith</option>
                  <option value="MovingCompany" <?php selected(self::get('business_type'), 'MovingCompany'); ?>>Moving Company</option>
                  <option value="HVACBusiness" <?php selected(self::get('business_type'), 'HVACBusiness'); ?>>HVAC Business</option>
                  <option value="Roofing" <?php selected(self::get('business_type'), 'Roofing'); ?>>Roofing Contractor</option>
                </optgroup>
                
                <optgroup label="Automotive">
                  <option value="AutomotiveBusiness" <?php selected(self::get('business_type'), 'AutomotiveBusiness'); ?>>Automotive (General)</option>
                  <option value="AutoRepair" <?php selected(self::get('business_type'), 'AutoRepair'); ?>>Auto Repair</option>
                  <option value="AutoDealer" <?php selected(self::get('business_type'), 'AutoDealer'); ?>>Auto Dealer</option>
                  <option value="AutoPartsStore" <?php selected(self::get('business_type'), 'AutoPartsStore'); ?>>Auto Parts Store</option>
                  <option value="AutoRental" <?php selected(self::get('business_type'), 'AutoRental'); ?>>Auto Rental</option>
                  <option value="AutoWash" <?php selected(self::get('business_type'), 'AutoWash'); ?>>Auto Wash</option>
                  <option value="GasStation" <?php selected(self::get('business_type'), 'GasStation'); ?>>Gas Station</option>
                </optgroup>
                
                <optgroup label="Lodging">
                  <option value="LodgingBusiness" <?php selected(self::get('business_type'), 'LodgingBusiness'); ?>>Lodging (General)</option>
                  <option value="Hotel" <?php selected(self::get('business_type'), 'Hotel'); ?>>Hotel</option>
                  <option value="Motel" <?php selected(self::get('business_type'), 'Motel'); ?>>Motel</option>
                  <option value="Resort" <?php selected(self::get('business_type'), 'Resort'); ?>>Resort</option>
                  <option value="BedAndBreakfast" <?php selected(self::get('business_type'), 'BedAndBreakfast'); ?>>Bed & Breakfast</option>
                  <option value="Hostel" <?php selected(self::get('business_type'), 'Hostel'); ?>>Hostel</option>
                  <option value="Campground" <?php selected(self::get('business_type'), 'Campground'); ?>>Campground</option>
                </optgroup>
                
                <optgroup label="Fitness & Recreation">
                  <option value="SportsActivityLocation" <?php selected(self::get('business_type'), 'SportsActivityLocation'); ?>>Sports / Recreation (General)</option>
                  <option value="FitnessCenter" <?php selected(self::get('business_type'), 'FitnessCenter'); ?>>Fitness Center / Gym</option>
                  <option value="GolfCourse" <?php selected(self::get('business_type'), 'GolfCourse'); ?>>Golf Course</option>
                  <option value="PublicSwimmingPool" <?php selected(self::get('business_type'), 'PublicSwimmingPool'); ?>>Swimming Pool</option>
                  <option value="TennisComplex" <?php selected(self::get('business_type'), 'TennisComplex'); ?>>Tennis Complex</option>
                </optgroup>
                
                <optgroup label="Entertainment">
                  <option value="EntertainmentBusiness" <?php selected(self::get('business_type'), 'EntertainmentBusiness'); ?>>Entertainment (General)</option>
                  <option value="MovieTheater" <?php selected(self::get('business_type'), 'MovieTheater'); ?>>Movie Theater</option>
                  <option value="NightClub" <?php selected(self::get('business_type'), 'NightClub'); ?>>Night Club</option>
                </optgroup>
                
                <optgroup label="Other">
                  <option value="AnimalShelter" <?php selected(self::get('business_type'), 'AnimalShelter'); ?>>Animal Shelter</option>
                  <option value="ChildCare" <?php selected(self::get('business_type'), 'ChildCare'); ?>>Child Care</option>
                  <option value="SelfStorage" <?php selected(self::get('business_type'), 'SelfStorage'); ?>>Self Storage</option>
                </optgroup>
              </select>
            </td>
          </tr>
          <tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
            <th scope="row">
              <label for="business_phone">Phone Number</label>
            </th>
            <td>
              <input type="tel" id="business_phone" class="regular-text" name="<?php echo esc_attr(self::OPT); ?>[business_phone]" value="<?php echo esc_attr(self::get('business_phone')); ?>">
            </td>
          </tr>
          <tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
            <th scope="row">
              <label for="business_address">Address</label>
            </th>
            <td>
              <textarea id="business_address" class="large-text" rows="3" name="<?php echo esc_attr(self::OPT); ?>[business_address]"><?php echo esc_textarea(self::get('business_address')); ?></textarea>
              <p class="description">Full business address for local SEO.</p>
            </td>
          </tr>
          <tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
            <th scope="row">
              <label for="business_hours">Opening Hours</label>
            </th>
            <td>
              <textarea id="business_hours" class="large-text" rows="4" name="<?php echo esc_attr(self::OPT); ?>[business_hours]" placeholder="Monday-Friday: 9:00 AM - 5:00 PM&#10;Saturday: 10:00 AM - 2:00 PM&#10;Sunday: Closed"><?php echo esc_textarea(self::get('business_hours')); ?></textarea>
              <p class="description">Business operating hours. One day per line (e.g., "Monday: 9:00 AM - 5:00 PM").</p>
            </td>
          </tr>
          <tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
            <th scope="row">
              <label for="service_area">Service Area</label>
            </th>
            <td>
              <textarea id="service_area" class="large-text" rows="2" name="<?php echo esc_attr(self::OPT); ?>[service_area]" placeholder="Singapore, Pasir Ris, Tampines"><?php echo esc_textarea(self::get('service_area')); ?></textarea>
              <p class="description">Cities, regions, or areas you serve (comma-separated).</p>
            </td>
          </tr>
          <tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
            <th scope="row">
              <label for="price_range">Price Range</label>
            </th>
            <td>
              <select id="price_range" name="<?php echo esc_attr(self::OPT); ?>[price_range]">
                <option value="" <?php selected(self::get('price_range'), ''); ?>>Not specified</option>
                <option value="$" <?php selected(self::get('price_range'), '$'); ?>>$ (Budget-friendly)</option>
                <option value="$$" <?php selected(self::get('price_range'), '$$'); ?>>$$ (Moderate)</option>
                <option value="$$$" <?php selected(self::get('price_range'), '$$$'); ?>>$$$ (Expensive)</option>
                <option value="$$$$" <?php selected(self::get('price_range'), '$$$$'); ?>>$$$$ (Luxury)</option>
              </select>
              <p class="description">Relative price indicator for your services/products.</p>
            </td>
          </tr>
          <tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
            <th scope="row">
              <label for="payment_methods">Payment Methods</label>
            </th>
            <td>
              <input type="text" id="payment_methods" class="large-text" name="<?php echo esc_attr(self::OPT); ?>[payment_methods]" value="<?php echo esc_attr(self::get('payment_methods')); ?>" placeholder="Cash, Credit Card, PayPal, Bank Transfer">
              <p class="description">Accepted payment methods (comma-separated).</p>
            </td>
          </tr>
          <tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
            <th scope="row">
              <label for="languages_spoken">Languages Spoken</label>
            </th>
            <td>
              <input type="text" id="languages_spoken" class="large-text" name="<?php echo esc_attr(self::OPT); ?>[languages_spoken]" value="<?php echo esc_attr(self::get('languages_spoken')); ?>" placeholder="English, Mandarin, Malay">
              <p class="description">Languages your business supports (comma-separated).</p>
            </td>
          </tr>
        </table>
      </div>
    </div>
    <?php
  }

  private static function render_templates_tab() {
    $post_types = get_post_types(['public' => true], 'objects');
    $title_templates = self::get('title_templates', []);
    $description_templates = self::get('description_templates', []);
    $separator = self::get('title_separator', '|');
    $variables = ASNERISSEO_Templates::get_available_variables();
    ?>
    <div class="ASNERISSEO-tab-content">
      <div class="ASNERISSEO-info-box ASNERISSEO-info">
        <h3>
          <span class="dashicons dashicons-info"></span> About Templates
          <?php ASNERISSEO_Help_Modal::render_help_icon('seo-templates', 'SEO Templates'); ?>
        </h3>
        <p>
          Title and description templates provide automated fallbacks when per-page values aren't set.
          Use variables like <code>{title}</code>, <code>{site}</code>, and <code>{separator}</code> to create dynamic templates.
        </p>
        <p><strong>Available Variables:</strong></p>
        <ul style="margin: 8px 0 0 20px; line-height: 1.8;">
          <?php foreach ($variables as $var => $desc): ?>
            <li><code><?php echo esc_html($var); ?></code> - <?php echo esc_html($desc); ?></li>
          <?php endforeach; ?>
        </ul>
        <p style="margin-top: 12px; color: #2271b1;"><strong>Note:</strong> Variables are optional — you don't need to use all of them.</p>
      </div>

      <div class="ASNERISSEO-card">
        <h2><span class="dashicons dashicons-admin-settings"></span> Title Separator</h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="title_separator">Separator Character</label>
            </th>
            <td>
              <input type="text" id="title_separator" name="<?php echo esc_attr(self::OPT); ?>[title_separator]" value="<?php echo esc_attr($separator); ?>" class="regular-text" maxlength="3">
              <p class="description">Used in template variable <code>{separator}</code>. Common: | - · •</p>
            </td>
          </tr>
        </table>
      </div>

      <div class="ASNERISSEO-card">
        <h2><span class="dashicons dashicons-editor-code"></span> Title Templates</h2>
        <p style="margin-top: 0; color: #646970;">Define title templates for each post type. Leave empty to use default behavior.</p>
        <p style="margin-top: 8px; color: #2271b1;"><strong>Note:</strong> Used only if the page title is not set manually.</p>
        <table class="form-table">
          <?php foreach ($post_types as $post_type): ?>
            <tr>
              <th scope="row">
                <label for="title_template_<?php echo esc_attr($post_type->name); ?>">
                  <?php echo esc_html($post_type->labels->singular_name); ?>
                </label>
              </th>
              <td>
                <input 
                  type="text" 
                  id="title_template_<?php echo esc_attr($post_type->name); ?>" 
                  name="<?php echo esc_attr(self::OPT); ?>[title_templates][<?php echo esc_attr($post_type->name); ?>]" 
                  value="<?php echo esc_attr($title_templates[$post_type->name] ?? ''); ?>" 
                  class="large-text"
                  placeholder="<?php echo esc_attr('{title} {separator} {site}'); ?>"
                >
                <p class="description">
                  Example: <code>{title} {separator} {site}</code>
                </p>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <div class="ASNERISSEO-card">
        <h2><span class="dashicons dashicons-text"></span> Description Templates</h2>
        <p style="margin-top: 0; color: #646970;">Define description templates for each post type. Leave empty to auto-generate from content.</p>
        <p style="margin-top: 8px; color: #2271b1;"><strong>Note:</strong> Leave empty to automatically generate from page content.</p>
        <table class="form-table">
          <?php foreach ($post_types as $post_type): ?>
            <tr>
              <th scope="row">
                <label for="description_template_<?php echo esc_attr($post_type->name); ?>">
                  <?php echo esc_html($post_type->labels->singular_name); ?>
                </label>
              </th>
              <td>
                <textarea 
                  id="description_template_<?php echo esc_attr($post_type->name); ?>" 
                  name="<?php echo esc_attr(self::OPT); ?>[description_templates][<?php echo esc_attr($post_type->name); ?>]" 
                  rows="3"
                  class="large-text"
                  placeholder="Auto-generated from excerpt or content"
                ><?php echo esc_textarea($description_templates[$post_type->name] ?? ''); ?></textarea>
                <p class="description">
                  Variables work here too. Leave empty for automatic excerpt extraction.
                </p>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
    <?php
  }

  private static function render_advanced_tab() {
    ?>
    <div class="ASNERISSEO-tab-content">
      <!-- Conflict Detection Status -->
      <?php ASNERISSEO_Conflict_Detector::render_status(); ?>
      
      <div class="ASNERISSEO-card">
        <h2>
          <span class="dashicons dashicons-admin-tools"></span> Import / Export Settings
          <?php ASNERISSEO_Help_Modal::render_help_icon('maintenance-safety', 'Maintenance & Safety'); ?>
        </h2>
        <table class="form-table">
          <tr>
            <th scope="row">Export Settings</th>
            <td>
              <button type="button" class="button" id="ASNERISSEO-export-settings">
                <span class="dashicons dashicons-download"></span> Export Configuration
              </button>
              <p class="description">Download your current settings as a JSON file.</p>
            </td>
          </tr>
          <tr>
            <th scope="row">Import Settings</th>
            <td>
              <input type="file" id="ASNERISSEO-import-file" accept=".json" style="display:none;">
              <button type="button" class="button" id="ASNERISSEO-import-settings">
                <span class="dashicons dashicons-upload"></span> Import Configuration
              </button>
              <p class="description">Upload a previously exported settings file.</p>
              <p style="margin-top: 8px; color: #2271b1;"><strong>Note:</strong> Use only files previously exported from this plugin.</p>
            </td>
          </tr>
        </table>
      </div>

      <div class="ASNERISSEO-card">
        <h2><span class="dashicons dashicons-trash"></span> Reset Settings</h2>
        <table class="form-table">
          <tr>
            <th scope="row">Clear All Data</th>
            <td>
              <button type="button" class="button button-link-delete" id="ASNERISSEO-reset-settings">
                <span class="dashicons dashicons-warning"></span> Reset All Settings
              </button>
              <p class="description">This will delete all plugin settings. This action cannot be undone.</p>
              <p style="margin-top: 8px; color: #2271b1;"><strong>Tip:</strong> Export your settings before resetting.</p>
            </td>
          </tr>
        </table>
      </div>

      <div class="ASNERISSEO-info-box">
        <h3><span class="dashicons dashicons-info"></span> Plugin Information</h3>
        <p><strong>Version:</strong> <?php echo esc_html(ASNERISSEO_VERSION); ?></p>
        <p><strong>Plugin Path:</strong> <code><?php echo esc_html(ASNERISSEO_DIR); ?></code></p>
      </div>
    </div>
    <?php
  }



  /**
   * Check sitemap visibility and status
   */
  private static function check_sitemap_visibility() {
    $site_url = home_url();
    $sitemap_urls = [
      $site_url . '/wp-sitemap.xml', // WordPress Core
      $site_url . '/sitemap.xml',
      $site_url . '/sitemap_index.xml',
    ];
    
    $result = [
      'found' => false,
      'url' => 'Not found',
      'http_status' => 0,
      'http_message' => 'Not checked',
      'in_robots' => false,
      'robots_message' => 'Not found in robots.txt',
      'controller' => 'Unknown'
    ];
    
    // Check which sitemap exists
    foreach ($sitemap_urls as $url) {
      $response = wp_remote_head($url, ['timeout' => 5, 'sslverify' => false]);
      if (!is_wp_error($response)) {
        $status = wp_remote_retrieve_response_code($response);
        if ($status === 200) {
          $result['found'] = true;
          $result['url'] = $url;
          $result['http_status'] = $status;
          $result['http_message'] = 'Sitemap is accessible';
          break;
        }
      }
    }
    
    // Check robots.txt
    if ($result['found']) {
      $robots_url = $site_url . '/robots.txt';
      $robots_response = wp_remote_get($robots_url, ['timeout' => 5, 'sslverify' => false]);
      if (!is_wp_error($robots_response)) {
        $robots_content = wp_remote_retrieve_body($robots_response);
        if (stripos($robots_content, 'sitemap:') !== false && stripos($robots_content, basename($result['url'])) !== false) {
          $result['in_robots'] = true;
          $result['robots_message'] = 'Sitemap is declared in robots.txt';
        }
      }
    }
    
    // Detect controller
    if (function_exists('wp_sitemaps_get_server')) {
      $result['controller'] = '<strong>WordPress Core</strong> (Built-in sitemaps since WP 5.5)';
    } elseif (defined('WPSEO_VERSION')) {
      $result['controller'] = '<strong>Yoast SEO</strong> (Version ' . WPSEO_VERSION . ')';
    } elseif (class_exists('RankMath')) {
      $result['controller'] = '<strong>Rank Math</strong>';
    } elseif (class_exists('AIOSEO\\Plugin\\AIOSEO')) {
      $result['controller'] = '<strong>All in One SEO</strong>';
    } elseif (function_exists('the_seo_framework')) {
      $result['controller'] = '<strong>The SEO Framework</strong>';
    } else {
      $result['controller'] = 'Unknown plugin or theme generating sitemaps';
    }
    
    return $result;
  }

  /**
   * Detect duplicate SEO outputs
   */
  private static function detect_duplicate_outputs() {
    $active_seo_plugins = [];
    $known_plugins = [
      'wordpress-seo/wp-seo.php' => 'Yoast SEO',
      'seo-by-rank-math/rank-math.php' => 'Rank Math',
      'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
      'autodescription/autodescription.php' => 'The SEO Framework',
      'wp-seopress/seopress.php' => 'SEOPress',
      'squirrly-seo/squirrly.php' => 'Squirrly SEO',
    ];
    
    foreach ($known_plugins as $plugin_file => $plugin_name) {
      if (is_plugin_active($plugin_file)) {
        $active_seo_plugins[] = $plugin_name;
      }
    }
    
    // We can't easily detect duplicate tags without loading a frontend page
    // This would require a separate AJAX call to check the actual HTML output
    // For now, we'll just warn if multiple plugins are active
    
    $duplicates = [];
    if (count($active_seo_plugins) > 0) {
      $duplicates['title'] = 'Multiple SEO plugins detected - check homepage source';
      $duplicates['description'] = 'Multiple SEO plugins detected - check homepage source';
      $duplicates['canonical'] = 'Multiple SEO plugins detected - check homepage source';
      $duplicates['robots'] = 'Multiple SEO plugins detected - check homepage source';
      $duplicates['schema'] = 'Multiple SEO plugins detected - check homepage source';
    }
    
    return [
      'active_plugins' => $active_seo_plugins,
      'duplicates' => $duplicates
    ];
  }
  
  /**
   * AJAX handler for exporting settings
   */
  public static function ajax_export_settings() {
    check_ajax_referer('ASNERISSEO_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error('Unauthorized');
    }

    $settings = get_option(self::OPT, []);
    wp_send_json_success($settings);
  }

  /**
   * AJAX handler for importing settings
   */
  public static function ajax_import_settings() {
    check_ajax_referer('ASNERISSEO_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error('Unauthorized');
    }

    $settings = isset($_POST['settings']) ? map_deep(wp_unslash($_POST['settings']), 'sanitize_text_field') : [];
    
    if (empty($settings)) {
      wp_send_json_error('No settings data provided');
    }

    // Sanitize imported settings
    $clean_settings = self::sanitize($settings);
    update_option(self::OPT, $clean_settings);
    
    wp_send_json_success('Settings imported successfully');
  }

  /**
   * AJAX handler for resetting settings
   */
  public static function ajax_reset_settings() {
    check_ajax_referer('ASNERISSEO_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error('Unauthorized');
    }

    delete_option(self::OPT);
    wp_send_json_success('Settings reset successfully');
  }
}
