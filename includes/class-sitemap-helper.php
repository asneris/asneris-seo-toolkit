<?php
if (!defined('ABSPATH')) exit;

class GSCSEO_Sitemap_Helper {
  
  /**
   * Get WordPress core sitemap URL
   */
  public static function get_sitemap_url() {
    return get_sitemap_url('index');
  }

  /**
   * Check if sitemap is accessible
   */
  public static function is_sitemap_accessible() {
    $sitemap_url = self::get_sitemap_url();
    
    $response = wp_remote_get($sitemap_url, [
      'timeout' => 5,
      'sslverify' => false
    ]);
    
    if (is_wp_error($response)) {
      return false;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    return $status_code === 200;
  }

  /**
   * Get sitemap status
   */
  public static function get_sitemap_status() {
    $accessible = self::is_sitemap_accessible();
    
    return [
      'url' => self::get_sitemap_url(),
      'accessible' => $accessible,
      'message' => $accessible 
        ? __('Sitemap is accessible', 'bfseo')
        : __('Sitemap may not be accessible. Check your permalink settings.', 'bfseo')
    ];
  }

  /**
   * Render sitemap info box
   */
  public static function render_sitemap_info() {
    $status = self::get_sitemap_status();
    $icon_class = $status['accessible'] ? 'dashicons-yes-alt' : 'dashicons-warning';
    $box_class = $status['accessible'] ? 'gscseo-success-box' : '';
    ?>
    <div class="gscseo-info-box <?php echo esc_attr($box_class); ?>">
      <h3>
        <span class="dashicons <?php echo esc_attr($icon_class); ?>"></span> 
        <?php _e('XML Sitemap', 'bfseo'); ?>
      </h3>
      <p>
        <?php _e('WordPress automatically generates an XML sitemap for your site.', 'bfseo'); ?>
      </p>
      <p>
        <strong><?php _e('Sitemap URL:', 'bfseo'); ?></strong><br>
        <code style="background: #fff; padding: 5px 10px; display: inline-block; margin: 5px 0;">
          <?php echo esc_html($status['url']); ?>
        </code>
        <a href="<?php echo esc_url($status['url']); ?>" target="_blank" class="button button-small">
          <span class="dashicons dashicons-external" style="margin-top: 4px;"></span>
          <?php _e('View Sitemap', 'bfseo'); ?>
        </a>
      </p>
      <p style="color: #646970; font-size: 13px;">
        <?php echo esc_html($status['message']); ?>
      </p>
      
      <hr style="margin: 15px 0; border: none; border-top: 1px solid #e0e0e0;">
      
      <p><strong><?php _e('Submit to Search Engines:', 'bfseo'); ?></strong></p>
      <ul style="margin: 8px 0 0 20px; line-height: 1.8;">
        <li>
          <a href="https://search.google.com/search-console" target="_blank">
            <?php _e('Google Search Console', 'bfseo'); ?> 
            <span class="dashicons dashicons-external" style="font-size: 12px; margin-top: 2px;"></span>
          </a>
          - <?php _e('Sitemaps section', 'bfseo'); ?>
        </li>
        <li>
          <a href="https://www.bing.com/webmasters" target="_blank">
            <?php _e('Bing Webmaster Tools', 'bfseo'); ?>
            <span class="dashicons dashicons-external" style="font-size: 12px; margin-top: 2px;"></span>
          </a>
          - <?php _e('Sitemaps section', 'bfseo'); ?>
        </li>
      </ul>
    </div>
    <?php
  }

  /**
   * Check if sitemaps are enabled
   */
  public static function are_sitemaps_enabled() {
    return (bool) get_option('blog_public');
  }
}
