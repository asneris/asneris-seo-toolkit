<?php
if (!defined('ABSPATH')) exit;

class CFSEO_Meta {
  const KEYS = [
    '_CFSEO_title' => 'string',
    '_CFSEO_description' => 'string',
    '_CFSEO_canonical' => 'string',
    '_CFSEO_robots_index' => 'string',
    '_CFSEO_robots_follow' => 'string',
    '_CFSEO_og_title' => 'string',
    '_CFSEO_og_description' => 'string',
    '_CFSEO_og_image' => 'string',
    '_CFSEO_schema_enabled' => 'boolean',
    '_CFSEO_schema_type' => 'string',
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
    if ($key === '_CFSEO_robots_index') return 'index';
    if ($key === '_CFSEO_robots_follow') return 'follow';
    if ($key === '_CFSEO_schema_enabled') return true;
    return '';
  }

  public static function sanitize($value, $key) {
    if (in_array($key, ['_CFSEO_canonical','_CFSEO_og_image'], true)) {
      return esc_url_raw($value);
    }
    if ($key === '_CFSEO_schema_enabled') {
      return (bool)$value;
    }
    return sanitize_text_field($value);
  }
}
