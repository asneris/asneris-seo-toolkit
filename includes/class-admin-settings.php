<?php
if (!defined('ABSPATH')) exit;

class GSCSEO_Admin_Settings {
  const OPT = 'gscseo_settings';

  public static function register_menu() {
    add_options_page(
      'Clarity-First SEO',
      'Clarity-First SEO',
      'manage_options',
      'gscseo',
      [__CLASS__, 'render']
    );
  }

  public static function register_settings() {
    register_setting('gscseo', self::OPT, ['sanitize_callback' => [__CLASS__, 'sanitize']]);
  }

  /**
   * Enqueue admin styles and scripts
   */
  public static function enqueue_admin_assets($hook) {
    if ($hook !== 'settings_page_gscseo') return;
    
    wp_enqueue_style('gscseo-admin', GSCSEO_URL . 'assets/css/admin-style.css', [], GSCSEO_VERSION);
    wp_enqueue_script('gscseo-admin', GSCSEO_URL . 'assets/js/admin-script.js', ['jquery'], GSCSEO_VERSION, true);
    wp_enqueue_media(); // For media uploader
    
    wp_localize_script('gscseo-admin', 'gscseoAdmin', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('gscseo_admin_nonce'),
    ]);
  }

  public static function get($key, $default = '') {
    $opt = get_option(self::OPT, []);
    return $opt[$key] ?? $default;
  }

  public static function sanitize($opt) {
    $clean = [
      'google_verification' => sanitize_text_field($opt['google_verification'] ?? ''),
      'bing_verification'   => sanitize_text_field($opt['bing_verification'] ?? ''),
      'yandex_verification' => sanitize_text_field($opt['yandex_verification'] ?? ''),
      'default_og_image'    => esc_url_raw($opt['default_og_image'] ?? ''),
      'org_name'            => sanitize_text_field($opt['org_name'] ?? ''),
      'org_logo'            => esc_url_raw($opt['org_logo'] ?? ''),
      'indexnow_enabled'    => !empty($opt['indexnow_enabled']) ? 1 : 0,
      'indexnow_key'        => sanitize_text_field($opt['indexnow_key'] ?? ''),
      'twitter_username'    => sanitize_text_field($opt['twitter_username'] ?? ''),
      'facebook_app_id'     => sanitize_text_field($opt['facebook_app_id'] ?? ''),
      'theme_color'         => sanitize_hex_color($opt['theme_color'] ?? ''),
      'default_robots_index' => sanitize_text_field($opt['default_robots_index'] ?? 'index'),
      'default_robots_follow' => sanitize_text_field($opt['default_robots_follow'] ?? 'follow'),
      'enable_breadcrumbs'  => !empty($opt['enable_breadcrumbs']) ? 1 : 0,
      'enable_local_business' => !empty($opt['enable_local_business']) ? 1 : 0,
      'business_type'       => sanitize_text_field($opt['business_type'] ?? 'LocalBusiness'),
      'business_phone'      => sanitize_text_field($opt['business_phone'] ?? ''),
      'business_address'    => sanitize_textarea_field($opt['business_address'] ?? ''),
      'title_separator'     => sanitize_text_field($opt['title_separator'] ?? '|'),
      'title_templates'     => isset($opt['title_templates']) && is_array($opt['title_templates']) ? array_map('sanitize_text_field', $opt['title_templates']) : [],
      'description_templates' => isset($opt['description_templates']) && is_array($opt['description_templates']) ? array_map('sanitize_textarea_field', $opt['description_templates']) : [],
    ];

    if ($clean['indexnow_enabled'] && $clean['indexnow_key'] === '') {
      $clean['indexnow_key'] = GSCSEO_IndexNow::generate_key();
    }
    return $clean;
  }

  public static function render_page() {
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
    $indexnow_key = esc_attr(self::get('indexnow_key', ''));
    $key_url = $indexnow_key ? esc_url(home_url('/' . $indexnow_key . '.txt')) : '';
    ?>
    <div class="wrap gscseo-admin-wrap">
      <h1>
        <span class="dashicons dashicons-search"></span>
        Clarity-First SEO
      </h1>
      <p class="gscseo-subtitle">Clear, Simple SEO Configuration for WordPress</p>

      <!-- Tab Navigation -->
      <nav class="nav-tab-wrapper gscseo-nav-tab-wrapper">
        <a href="?page=clarity-first-seo&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
          <span class="dashicons dashicons-admin-generic"></span> General
        </a>
        <a href="?page=clarity-first-seo&tab=verification" class="nav-tab <?php echo $current_tab === 'verification' ? 'nav-tab-active' : ''; ?>">
          <span class="dashicons dashicons-yes-alt"></span> Verification
        </a>
        <a href="?page=clarity-first-seo&tab=indexnow" class="nav-tab <?php echo $current_tab === 'indexnow' ? 'nav-tab-active' : ''; ?>">
          <span class="dashicons dashicons-update"></span> IndexNow
        </a>
        <a href="?page=clarity-first-seo&tab=social" class="nav-tab <?php echo $current_tab === 'social' ? 'nav-tab-active' : ''; ?>">
          <span class="dashicons dashicons-share"></span> Social Media
        </a>
        <a href="?page=clarity-first-seo&tab=schema" class="nav-tab <?php echo $current_tab === 'schema' ? 'nav-tab-active' : ''; ?>">
          <span class="dashicons dashicons-editor-code"></span> Schema
        </a>
        <a href="?page=clarity-first-seo&tab=templates" class="nav-tab <?php echo $current_tab === 'templates' ? 'nav-tab-active' : ''; ?>">
          <span class="dashicons dashicons-text"></span> Templates
        </a>
        <a href="?page=clarity-first-seo&tab=advanced" class="nav-tab <?php echo $current_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
          <span class="dashicons dashicons-admin-tools"></span> Advanced
        </a>
      </nav>

      <form method="post" action="options.php" class="gscseo-settings-form">
        <?php settings_fields('gscseo'); ?>

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
        <?php elseif ($current_tab === 'advanced'): ?>
          <?php self::render_advanced_tab(); ?>
        <?php endif; ?>

        <?php submit_button('Save Settings', 'primary large'); ?>
      </form>

      <!-- Sidebar Info -->
      <div class="gscseo-sidebar">
        <div class="gscseo-info-box">
          <h3><span class="dashicons dashicons-info"></span> Quick Tips</h3>
          <ul>
            <li>Configure verification codes to connect with Google Search Console and Bing Webmaster Tools</li>
            <li>Enable IndexNow for instant search engine indexing</li>
            <li>Set default social media images for better sharing</li>
            <li>Use Schema markup to enhance search results</li>
          </ul>
        </div>
        
        <div class="gscseo-info-box gscseo-success-box">
          <h3><span class="dashicons dashicons-yes"></span> Need Help?</h3>
          <p>Check out our <a href="#" target="_blank">documentation</a> for detailed guides.</p>
        </div>
      </div>
    </div>
    <?php
  }

  private static function render_general_tab() {
    ?>
    <div class="gscseo-tab-content">
      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-admin-home"></span> Site Information</h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="org_name">Organization/Site Name</label>
            </th>
            <td>
              <input type="text" id="org_name" class="large-text" name="<?php echo self::OPT; ?>[org_name]" value="<?php echo esc_attr(self::get('org_name', get_bloginfo('name'))); ?>">
              <p class="description">Used for schema markup and social meta tags.</p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="org_logo">Logo URL</label>
            </th>
            <td>
              <div class="gscseo-media-upload">
                <input type="url" id="org_logo" class="large-text gscseo-media-url" name="<?php echo self::OPT; ?>[org_logo]" value="<?php echo esc_url(self::get('org_logo')); ?>">
                <button type="button" class="button gscseo-upload-button" data-target="#org_logo">
                  <span class="dashicons dashicons-upload"></span> Upload Logo
                </button>
                <div class="gscseo-image-preview">
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
      <?php GSCSEO_Sitemap_Helper::render_sitemap_info(); ?>

      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-visibility"></span> Default Robots Settings</h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="default_robots_index">Default Indexing</label>
            </th>
            <td>
              <select id="default_robots_index" name="<?php echo self::OPT; ?>[default_robots_index]">
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
              <select id="default_robots_follow" name="<?php echo self::OPT; ?>[default_robots_follow]">
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
    <div class="gscseo-tab-content">
      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-google"></span> Google Search Console</h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="google_verification">Verification Code</label>
            </th>
            <td>
              <input type="text" id="google_verification" class="large-text code" name="<?php echo self::OPT; ?>[google_verification]" value="<?php echo esc_attr(self::get('google_verification')); ?>">
              <p class="description">
                Paste the content value only from:<br>
                <code>&lt;meta name="google-site-verification" content="<strong>YOUR_CODE_HERE</strong>" /&gt;</code>
              </p>
            </td>
          </tr>
        </table>
      </div>

      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-admin-site"></span> Bing Webmaster Tools</h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="bing_verification">Verification Code</label>
            </th>
            <td>
              <input type="text" id="bing_verification" class="large-text code" name="<?php echo self::OPT; ?>[bing_verification]" value="<?php echo esc_attr(self::get('bing_verification')); ?>">
              <p class="description">
                Paste the content value only from:<br>
                <code>&lt;meta name="msvalidate.01" content="<strong>YOUR_CODE_HERE</strong>" /&gt;</code>
              </p>
            </td>
          </tr>
        </table>
      </div>

      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-admin-site-alt2"></span> Yandex Webmaster</h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="yandex_verification">Verification Code</label>
            </th>
            <td>
              <input type="text" id="yandex_verification" class="large-text code" name="<?php echo self::OPT; ?>[yandex_verification]" value="<?php echo esc_attr(self::get('yandex_verification')); ?>">
              <p class="description">
                Paste the content value only from:<br>
                <code>&lt;meta name="yandex-verification" content="<strong>YOUR_CODE_HERE</strong>" /&gt;</code>
              </p>
            </td>
          </tr>
        </table>
      </div>

      <div class="gscseo-info-box gscseo-info">
        <p><strong>Note:</strong> After adding verification codes, visit your respective webmaster tools to complete the verification process.</p>
      </div>
    </div>
    <?php
  }

  private static function render_indexnow_tab($indexnow_key, $key_url) {
    ?>
    <div class="gscseo-tab-content">
      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-update"></span> IndexNow Configuration</h2>
        <p class="description" style="margin-bottom: 20px;">
          IndexNow is a protocol that allows you to instantly notify search engines about content changes on your site.
        </p>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="indexnow_enabled">Enable IndexNow</label>
            </th>
            <td>
              <label class="gscseo-toggle">
                <input type="checkbox" id="indexnow_enabled" name="<?php echo self::OPT; ?>[indexnow_enabled]" value="1" <?php checked((int)self::get('indexnow_enabled', 0), 1); ?>>
                <span class="gscseo-toggle-slider"></span>
              </label>
              <p class="description">Automatically submit updated URLs to search engines.</p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="indexnow_key">API Key</label>
            </th>
            <td>
              <input type="text" id="indexnow_key" class="large-text code" name="<?php echo self::OPT; ?>[indexnow_key]" value="<?php echo $indexnow_key; ?>" placeholder="Auto-generated when enabled">
              <?php if ($key_url): ?>
                <p class="description">
                  <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> 
                  Key file URL: <code><?php echo $key_url; ?></code>
                  <a href="<?php echo $key_url; ?>" target="_blank" class="button button-small">Test Key File</a>
                </p>
              <?php else: ?>
                <p class="description">Enable IndexNow and save to auto-generate a key.</p>
              <?php endif; ?>
            </td>
          </tr>
        </table>
      </div>

      <?php if (self::get('indexnow_enabled')): ?>
      <div class="gscseo-info-box gscseo-success-box">
        <h3><span class="dashicons dashicons-yes"></span> IndexNow is Active</h3>
        <p>Your site is automatically notifying search engines when content is published or updated.</p>
        <p><strong>Important:</strong> If this is your first time enabling IndexNow, visit <strong>Settings → Permalinks</strong> and click "Save Changes" to flush rewrite rules.</p>
      </div>
      <?php endif; ?>
    </div>
    <?php
  }

  private static function render_social_tab() {
    ?>
    <div class="gscseo-tab-content">
      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-format-image"></span> Default Open Graph Image</h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="default_og_image">Default Image</label>
            </th>
            <td>
              <div class="gscseo-media-upload">
                <input type="url" id="default_og_image" class="large-text gscseo-media-url" name="<?php echo self::OPT; ?>[default_og_image]" value="<?php echo esc_url(self::get('default_og_image')); ?>">
                <button type="button" class="button gscseo-upload-button" data-target="#default_og_image">
                  <span class="dashicons dashicons-upload"></span> Upload Image
                </button>
                <div class="gscseo-image-preview">
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

      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-twitter"></span> Social Profiles</h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="twitter_username">Twitter Username</label>
            </th>
            <td>
              <div class="gscseo-input-prefix">
                <span class="prefix">@</span>
                <input type="text" id="twitter_username" class="regular-text" name="<?php echo self::OPT; ?>[twitter_username]" value="<?php echo esc_attr(self::get('twitter_username')); ?>" placeholder="username">
              </div>
              <p class="description">Your Twitter/X username (without @).</p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="facebook_app_id">Facebook App ID</label>
            </th>
            <td>
              <input type="text" id="facebook_app_id" class="regular-text" name="<?php echo self::OPT; ?>[facebook_app_id]" value="<?php echo esc_attr(self::get('facebook_app_id')); ?>">
              <p class="description">Optional: For Facebook Insights and Open Graph validation.</p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="theme_color">Theme Color</label>
            </th>
            <td>
              <input type="text" id="theme_color" class="regular-text" name="<?php echo self::OPT; ?>[theme_color]" value="<?php echo esc_attr(self::get('theme_color')); ?>" placeholder="#2271b1">
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
    <div class="gscseo-tab-content">
      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-admin-home"></span> Organization Schema</h2>
        <p class="description" style="margin-bottom: 20px;">
          Schema.org markup helps search engines understand your content better and can enhance search results with rich snippets.
        </p>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="enable_breadcrumbs">Enable Breadcrumbs</label>
            </th>
            <td>
              <label class="gscseo-toggle">
                <input type="checkbox" id="enable_breadcrumbs" name="<?php echo self::OPT; ?>[enable_breadcrumbs]" value="1" <?php checked((int)self::get('enable_breadcrumbs', 0), 1); ?>>
                <span class="gscseo-toggle-slider"></span>
              </label>
              <p class="description">Add breadcrumb schema to posts and pages.</p>
            </td>
          </tr>
        </table>
      </div>

      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-store"></span> Local Business Schema</h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="enable_local_business">Enable Local Business</label>
            </th>
            <td>
              <label class="gscseo-toggle">
                <input type="checkbox" id="enable_local_business" name="<?php echo self::OPT; ?>[enable_local_business]" value="1" <?php checked((int)self::get('enable_local_business', 0), 1); ?>>
                <span class="gscseo-toggle-slider"></span>
              </label>
              <p class="description">Add local business schema for better local search visibility.</p>
            </td>
          </tr>
          <tr class="gscseo-conditional" data-depends="enable_local_business">
            <th scope="row">
              <label for="business_type">Business Type</label>
            </th>
            <td>
              <select id="business_type" name="<?php echo self::OPT; ?>[business_type]">
                <option value="LocalBusiness" <?php selected(self::get('business_type', 'LocalBusiness'), 'LocalBusiness'); ?>>Local Business</option>
                <option value="Restaurant" <?php selected(self::get('business_type'), 'Restaurant'); ?>>Restaurant</option>
                <option value="Store" <?php selected(self::get('business_type'), 'Store'); ?>>Store</option>
                <option value="ProfessionalService" <?php selected(self::get('business_type'), 'ProfessionalService'); ?>>Professional Service</option>
                <option value="HealthAndBeautyBusiness" <?php selected(self::get('business_type'), 'HealthAndBeautyBusiness'); ?>>Health & Beauty</option>
              </select>
            </td>
          </tr>
          <tr class="gscseo-conditional" data-depends="enable_local_business">
            <th scope="row">
              <label for="business_phone">Phone Number</label>
            </th>
            <td>
              <input type="tel" id="business_phone" class="regular-text" name="<?php echo self::OPT; ?>[business_phone]" value="<?php echo esc_attr(self::get('business_phone')); ?>">
            </td>
          </tr>
          <tr class="gscseo-conditional" data-depends="enable_local_business">
            <th scope="row">
              <label for="business_address">Address</label>
            </th>
            <td>
              <textarea id="business_address" class="large-text" rows="3" name="<?php echo self::OPT; ?>[business_address]"><?php echo esc_textarea(self::get('business_address')); ?></textarea>
              <p class="description">Full business address for local SEO.</p>
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
    $variables = GSCSEO_Templates::get_available_variables();
    ?>
    <div class="gscseo-tab-content">
      <div class="gscseo-info-box gscseo-info">
        <h3><span class="dashicons dashicons-info"></span> About Templates</h3>
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
      </div>

      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-admin-settings"></span> Title Separator</h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="title_separator">Separator Character</label>
            </th>
            <td>
              <input type="text" id="title_separator" name="<?php echo self::OPT; ?>[title_separator]" value="<?php echo esc_attr($separator); ?>" class="regular-text" maxlength="3">
              <p class="description">Used in template variable <code>{separator}</code>. Common: | - · •</p>
            </td>
          </tr>
        </table>
      </div>

      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-editor-code"></span> Title Templates</h2>
        <p style="margin-top: 0; color: #646970;">Define title templates for each post type. Leave empty to use default behavior.</p>
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
                  name="<?php echo self::OPT; ?>[title_templates][<?php echo esc_attr($post_type->name); ?>]" 
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

      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-text"></span> Description Templates</h2>
        <p style="margin-top: 0; color: #646970;">Define description templates for each post type. Leave empty to auto-generate from content.</p>
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
                  name="<?php echo self::OPT; ?>[description_templates][<?php echo esc_attr($post_type->name); ?>]" 
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
    <div class="gscseo-tab-content">
      <!-- Conflict Detection Status -->
      <?php GSCSEO_Conflict_Detector::render_status(); ?>
      
      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-admin-tools"></span> Import / Export Settings</h2>
        <table class="form-table">
          <tr>
            <th scope="row">Export Settings</th>
            <td>
              <button type="button" class="button" id="gscseo-export-settings">
                <span class="dashicons dashicons-download"></span> Export Configuration
              </button>
              <p class="description">Download your current settings as a JSON file.</p>
            </td>
          </tr>
          <tr>
            <th scope="row">Import Settings</th>
            <td>
              <input type="file" id="gscseo-import-file" accept=".json" style="display:none;">
              <button type="button" class="button" id="gscseo-import-settings">
                <span class="dashicons dashicons-upload"></span> Import Configuration
              </button>
              <p class="description">Upload a previously exported settings file.</p>
            </td>
          </tr>
        </table>
      </div>

      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-trash"></span> Reset Settings</h2>
        <table class="form-table">
          <tr>
            <th scope="row">Clear All Data</th>
            <td>
              <button type="button" class="button button-link-delete" id="gscseo-reset-settings">
                <span class="dashicons dashicons-warning"></span> Reset All Settings
              </button>
              <p class="description">This will delete all plugin settings. This action cannot be undone.</p>
            </td>
          </tr>
        </table>
      </div>

      <div class="gscseo-info-box">
        <h3><span class="dashicons dashicons-info"></span> Plugin Information</h3>
        <p><strong>Version:</strong> <?php echo GSCSEO_VERSION; ?></p>
        <p><strong>Plugin Path:</strong> <code><?php echo GSCSEO_DIR; ?></code></p>
      </div>
    </div>
    <?php
  }
  /**
   * AJAX handler for exporting settings
   */
  public static function ajax_export_settings() {
    check_ajax_referer('gscseo_admin_nonce', 'nonce');
    
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
    check_ajax_referer('gscseo_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error('Unauthorized');
    }

    $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
    
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
    check_ajax_referer('gscseo_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error('Unauthorized');
    }

    delete_option(self::OPT);
    wp_send_json_success('Settings reset successfully');
  }
}