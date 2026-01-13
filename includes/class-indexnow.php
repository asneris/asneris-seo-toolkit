<?php
if (!defined('ABSPATH')) exit;

class CFSEO_IndexNow {

  public static function generate_key(): string {
    // 32 chars, URL-safe
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $key = '';
    for ($i = 0; $i < 32; $i++) {
      $key .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $key;
  }

  public static function is_enabled(): bool {
    return (int)CFSEO_Admin_Settings::get('indexnow_enabled', 0) === 1;
  }

  public static function key(): string {
    return (string)CFSEO_Admin_Settings::get('indexnow_key', '');
  }

  public static function key_url(): string {
    $key = self::key();
    return $key ? home_url('/' . $key . '.txt') : '';
  }

  /**
   * Serve https://example.com/{key}.txt with the key as content (UTF-8),
   * matching IndexNow requirement to host key file at the root.
   *
   * Note: you may need to re-save permalinks once after enabling IndexNow so
   * WordPress flushes rewrite rules.
   */
  public static function register_rewrite(): void {
    $key = self::key();
    if (!$key) return;

    add_rewrite_rule('^' . preg_quote($key, '/') . '\\.txt$', 'index.php?CFSEO_indexnow_keyfile=1', 'top');

    add_filter('query_vars', function ($vars) {
      $vars[] = 'CFSEO_indexnow_keyfile';
      return $vars;
    });

    add_action('template_redirect', function () use ($key) {
      if (get_query_var('CFSEO_indexnow_keyfile') != 1) return;
      header('Content-Type: text/plain; charset=utf-8');
      echo $key;
      exit;
    });
  }

  public static function submit_url(string $url): void {
    if (!self::is_enabled()) return;
    $key = self::key();
    if (!$key) return;

    $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
    if (!$host) return;

    $payload = [
      'host' => $host,
      'key' => $key,
      'keyLocation' => self::key_url(),
      'urlList' => [ $url ],
    ];

    $args = [
      'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
      'body' => wp_json_encode($payload),
      'timeout' => 5,
    ];

    wp_remote_post('https://api.indexnow.org/IndexNow', $args);
  }

  /**
   * AJAX handler for manual IndexNow submission
   */
  public static function ajax_manual_submit(): void {
    check_ajax_referer('CFSEO_manual_indexnow', 'nonce');
    
    if (!current_user_can('edit_posts')) {
      wp_send_json_error(['message' => __('Permission denied', 'clarity-first-seo')]);
      return;
    }

    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    if (!$post_id) {
      wp_send_json_error(['message' => __('Invalid post ID', 'clarity-first-seo')]);
      return;
    }

    $post = get_post($post_id);
    if (!$post || $post->post_status !== 'publish') {
      wp_send_json_error(['message' => __('Post must be published', 'clarity-first-seo')]);
      return;
    }

    if (!self::is_enabled()) {
      wp_send_json_error(['message' => __('IndexNow is not enabled', 'clarity-first-seo')]);
      return;
    }

    $url = get_permalink($post_id);
    if (!$url) {
      wp_send_json_error(['message' => __('Could not get permalink', 'clarity-first-seo')]);
      return;
    }

    self::submit_url($url);
    update_post_meta($post_id, '_CFSEO_indexnow_last', time());
    
    wp_send_json_success([
      'message' => __('URL successfully submitted to IndexNow!', 'clarity-first-seo'),
      'url' => $url
    ]);
  }
}

