<?php
if (!defined('ABSPATH')) exit;

class ASNERISSEO_Meta {
  const KEYS = [
    '_ASNERISSEO_title' => 'string',
    '_ASNERISSEO_description' => 'string',
    '_ASNERISSEO_canonical' => 'string',
    '_ASNERISSEO_robots_index' => 'string',
    '_ASNERISSEO_robots_follow' => 'string',
    '_ASNERISSEO_og_title' => 'string',
    '_ASNERISSEO_og_description' => 'string',
    '_ASNERISSEO_og_image' => 'string',
    '_ASNERISSEO_schema_enabled' => 'boolean',
    '_ASNERISSEO_schema_type' => 'string',
  ];

  public static function register_post_meta(): void {
    foreach (self::KEYS as $key => $type) {
      register_post_meta('', $key, [
        'type' => $type,
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => function () {
          return current_user_can('edit_posts');
        },
        'sanitize_callback' => [__CLASS__, 'sanitize'],
        'default' => self::default_for($key),
      ]);
    }
  }

  public static function default_for($key) {
    if ($key === '_ASNERISSEO_robots_index') return 'index';
    if ($key === '_ASNERISSEO_robots_follow') return 'follow';
    if ($key === '_ASNERISSEO_schema_enabled') return true;
    return '';
  }

  public static function sanitize($value, $key) {
    if (in_array($key, ['_ASNERISSEO_canonical','_ASNERISSEO_og_image'], true)) {
      return esc_url_raw($value);
    }
    if ($key === '_ASNERISSEO_schema_enabled') {
      return (bool)$value;
    }
    return sanitize_text_field($value);
  }
}
