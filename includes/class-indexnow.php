<?php
if (!defined('ABSPATH')) exit;

class ASNERISSEO_IndexNow {

  public static function generate_key(): string {
    // 32 chars, URL-safe (no special characters)
    return wp_generate_password(32, false);
  }

  public static function is_enabled(): bool {
    return (int)ASNERISSEO_Admin_Settings::get('indexnow_enabled', 0) === 1;
  }

  public static function key(): string {
    return (string)ASNERISSEO_Admin_Settings::get('indexnow_key', '');
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

    add_rewrite_rule('^' . preg_quote($key, '/') . '\\.txt$', 'index.php?ASNERISSEO_indexnow_keyfile=1', 'top');

    add_filter('query_vars', function ($vars) {
      $vars[] = 'ASNERISSEO_indexnow_keyfile';
      return $vars;
    });

    add_action('template_redirect', function () use ($key) {
      if (get_query_var('ASNERISSEO_indexnow_keyfile') != 1) return;
      header('Content-Type: text/plain; charset=utf-8');
      // Output key as plain text (alphanumeric, already validated)
      echo esc_html($key);
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

    $response = wp_remote_post('https://api.indexnow.org/IndexNow', $args);
    
    // Handle errors silently; callers may implement their own handling as needed.
    if (is_wp_error($response)) {
      return;
    }
    
    $status = wp_remote_retrieve_response_code($response);
    if ($status !== 200 && $status !== 202) {
      return;
    }
  }

  /**
   * AJAX handler for manual IndexNow submission
   */
  public static function ajax_manual_submit(): void {
    check_ajax_referer('ASNERISSEO_manual_indexnow', 'nonce');
    
    if (!current_user_can('edit_posts')) {
      wp_send_json_error(['message' => __('Permission denied', 'asneris-seo-toolkit')]);
      return;
    }

    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    if (!$post_id) {
      wp_send_json_error(['message' => __('Invalid post ID', 'asneris-seo-toolkit')]);
      return;
    }

    $post = get_post($post_id);
    if (!$post || $post->post_status !== 'publish') {
      wp_send_json_error(['message' => __('Post must be published', 'asneris-seo-toolkit')]);
      return;
    }

    if (!self::is_enabled()) {
      wp_send_json_error(['message' => __('IndexNow is not enabled', 'asneris-seo-toolkit')]);
      return;
    }

    $url = get_permalink($post_id);
    if (!$url) {
      wp_send_json_error(['message' => __('Could not get permalink', 'asneris-seo-toolkit')]);
      return;
    }

    // Submit to IndexNow with error handling
    $key = self::key();
    $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
    
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

    $response = wp_remote_post('https://api.indexnow.org/IndexNow', $args);
    
    if (is_wp_error($response)) {
      wp_send_json_error(['message' => __('IndexNow API error: ', 'asneris-seo-toolkit') . $response->get_error_message()]);
      return;
    }
    
    $status = wp_remote_retrieve_response_code($response);
    if ($status !== 200 && $status !== 202) {
      /* translators: %d is the HTTP status code returned by the IndexNow API */
      wp_send_json_error(['message' => sprintf(__('IndexNow API returned status %d', 'asneris-seo-toolkit'), $status)]);
      return;
    }

    update_post_meta($post_id, '_ASNERISSEO_indexnow_last', time());
    
    wp_send_json_success([
      'message' => __('URL successfully submitted to IndexNow!', 'asneris-seo-toolkit'),
      'url' => $url
    ]);
  }
}

