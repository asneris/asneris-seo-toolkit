<?php
/**
 * Migration Helper - Handles data migration from old naming (gscseo) to new naming (ASNERISSEO)
 */

if (!defined('ABSPATH')) exit;

class ASNERISSEO_Migration {
  
  const MIGRATION_VERSION = '1.0.0';
  const MIGRATION_OPTION = 'ASNERISSEO_migration_version';
  
  /**
   * Run all migrations if needed
   */
  public static function run() {
    $current_version = get_option(self::MIGRATION_OPTION, '0.0.0');
    
    if (version_compare($current_version, self::MIGRATION_VERSION, '<')) {
      self::migrate_post_meta();
      self::migrate_options();
      update_option(self::MIGRATION_OPTION, self::MIGRATION_VERSION);
    }
  }
  
  /**
   * Migrate post meta from _gscseo_* to _ASNERISSEO_*
   */
  private static function migrate_post_meta() {
    global $wpdb;
    
    $old_meta_keys = [
      '_gscseo_title',
      '_gscseo_description',
      '_gscseo_canonical',
      '_gscseo_robots_index',
      '_gscseo_robots_follow',
      '_gscseo_og_title',
      '_gscseo_og_description',
      '_gscseo_og_image',
      '_gscseo_schema_enabled',
      '_gscseo_schema_type'
    ];
    
    foreach ($old_meta_keys as $old_key) {
      $new_key = str_replace('_gscseo_', '_ASNERISSEO_', $old_key);
      
      // Copy old meta to new key (only if new key doesn't exist)
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Migration query runs once during plugin upgrade
      $migration_result = $wpdb->query($wpdb->prepare(
        "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value)
         SELECT post_id, %s, meta_value
         FROM {$wpdb->postmeta}
         WHERE meta_key = %s
         AND post_id NOT IN (
           SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s
         )",
        $new_key,
        $old_key,
        $new_key
      ));
      
      // Clear cache after migration
      wp_cache_flush();
      
      // Log migration progress
      if ($migration_result !== false) {
        wp_cache_delete('ASNERISSEO_migration_progress', 'ASNERISSEO');
      }
    }
  }
  
  /**
   * Migrate WordPress options from gscseo_* to ASNERISSEO_*
   */
  private static function migrate_options() {
    // Migrate main settings
    $old_settings = get_option('gscseo_settings', []);
    if (!empty($old_settings) && !get_option('ASNERISSEO_settings')) {
      update_option('ASNERISSEO_settings', $old_settings);
    }
    
    // Migrate redirects
    $old_redirects = get_option('gscseo_redirects', []);
    if (!empty($old_redirects) && !get_option('ASNERISSEO_redirects')) {
      update_option('ASNERISSEO_redirects', $old_redirects);
    }
    
    // Migrate indexnow submissions
    $old_indexnow = get_option('gscseo_indexnow_submissions', []);
    if (!empty($old_indexnow) && !get_option('ASNERISSEO_indexnow_submissions')) {
      update_option('ASNERISSEO_indexnow_submissions', $old_indexnow);
    }
  }
  
  /**
   * Get backward compatible meta value (fallback to old key if new doesn't exist)
   */
  public static function get_meta($post_id, $key, $default = '') {
    // Try new key first
    $new_key = str_replace('_gscseo_', '_ASNERISSEO_', $key);
    $value = get_post_meta($post_id, $new_key, true);
    
    // Fallback to old key
    if (empty($value)) {
      $old_key = str_replace('_ASNERISSEO_', '_gscseo_', $key);
      $value = get_post_meta($post_id, $old_key, true);
    }
    
    return !empty($value) ? $value : $default;
  }
}
