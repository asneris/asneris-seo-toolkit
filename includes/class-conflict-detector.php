<?php
if (!defined('ABSPATH')) exit;

class ASNERISSEO_Conflict_Detector {
  
  /**
   * Known SEO plugins that may conflict
   */
  private static $known_plugins = [
    'wordpress-seo/wp-seo.php' => 'Yoast SEO',
    'wordpress-seo-premium/wp-seo-premium.php' => 'Yoast SEO Premium',
    'seo-by-rank-math/rank-math.php' => 'Rank Math',
    'seo-by-rank-math-pro/rank-math-pro.php' => 'Rank Math Pro',
    'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
    'all-in-one-seo-pack-pro/all_in_one_seo_pack_pro.php' => 'All in One SEO Pro',
    'wp-seopress/seopress.php' => 'SEOPress',
    'wp-seopress-pro/seopress-pro.php' => 'SEOPress Pro',
    'slim-seo/slim-seo.php' => 'Slim SEO',
    'autodescription/autodescription.php' => 'The SEO Framework',
    'squirrly-seo/squirrly.php' => 'Squirrly SEO',
  ];

  /**
   * Check for active SEO plugins
   */
  public static function detect_conflicts() {
    $conflicts = [];
    
    if (!function_exists('is_plugin_active')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    foreach (self::$known_plugins as $plugin_path => $plugin_name) {
      if (is_plugin_active($plugin_path)) {
        $conflicts[] = $plugin_name;
      }
    }
    
    return $conflicts;
  }

  /**
   * Check if there are conflicts
   */
  public static function has_conflicts() {
    $conflicts = self::detect_conflicts();
    return !empty($conflicts);
  }

  /**
   * Get conflict message
   */
  public static function get_conflict_message() {
    $conflicts = self::detect_conflicts();
    
    if (empty($conflicts)) {
      return '';
    }
    
    $count = count($conflicts);
    $plugin_list = implode(', ', $conflicts);
    
    $message = sprintf(
      /* translators: %s: list of conflicting plugin names */
      _n(
        'Warning: Another SEO plugin detected: %s. Having multiple SEO plugins active may cause duplicate meta tags and conflicts.',
        'Warning: Multiple SEO plugins detected: %s. Having multiple SEO plugins active may cause duplicate meta tags and conflicts.',
        $count,
        'asneris-seo-toolkit'
      ),
      '<strong>' . $plugin_list . '</strong>'
    );
    
    return $message;
  }

  /**
   * Display admin notice
   */
  public static function admin_notice() {
    if (!self::has_conflicts()) {
      return;
    }
    
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'settings_page_gscseo') {
      return;
    }
    
    $message = self::get_conflict_message();
    ?>
    <div class="notice notice-warning is-dismissible">
      <p><?php echo wp_kses_post($message); ?></p>
      <p>
        <strong><?php esc_html_e('Recommendation:', 'asneris-seo-toolkit'); ?></strong> 
        <?php esc_html_e('Deactivate other SEO plugins to avoid conflicts and ensure proper functionality.', 'asneris-seo-toolkit'); ?>
      </p>
    </div>
    <?php
  }

  /**
   * Show conflict status in settings
   */
  public static function render_status() {
    $conflicts = self::detect_conflicts();
    
    if (empty($conflicts)) {
      ?>
      <div class="ASNERISSEO-info-box ASNERISSEO-success-box">
        <h3><span class="dashicons dashicons-yes"></span> <?php esc_html_e('No Conflicts Detected', 'asneris-seo-toolkit'); ?></h3>
        <p><?php esc_html_e('No other SEO plugins are currently active. Your site is using Asneris SEO Toolkit exclusively.', 'asneris-seo-toolkit'); ?></p>
        <p style="margin-top: 8px; color: #2271b1;"><strong>Note:</strong> This confirms no other SEO plugins are active.</p>
      </div>
      <?php
    } else {
      ?>
      <div class="ASNERISSEO-info-box" style="background: #fff8e5; border-left-color: #f0b849;">
        <h3><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Potential Conflicts', 'asneris-seo-toolkit'); ?></h3>
        <p><?php echo wp_kses_post(self::get_conflict_message()); ?></p>
        <ul style="margin: 10px 0 0 20px;">
          <?php foreach ($conflicts as $plugin_name): ?>
            <li><?php echo esc_html($plugin_name); ?></li>
          <?php endforeach; ?>
        </ul>
        <p>
          <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="button">
            <?php esc_html_e('Manage Plugins', 'asneris-seo-toolkit'); ?>
          </a>
        </p>
      </div>
      <?php
    }
  }
}
