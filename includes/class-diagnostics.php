<?php
/**
 * Diagnostics helper functions
 */

class GSCSEO_Diagnostics {
  
  public static function ajax_http_test() {
    check_ajax_referer('gscseo_http_test', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error('Unauthorized');
    }

    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    
    if (empty($url)) {
      wp_send_json_error('No URL provided');
    }

    $checks = [];
    
    // Check HTTP Status Code
    $response = wp_remote_get($url, ['timeout' => 10, 'sslverify' => false, 'redirection' => 0]);
    
    if (is_wp_error($response)) {
      $checks[] = [
        'label' => 'HTTP Status',
        'status' => 'fail',
        'result' => 'Error',
        'details' => $response->get_error_message()
      ];
      wp_send_json_success(['checks' => $checks]);
      return;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $status_type = $status_code === 200 ? 'pass' : ($status_code >= 300 && $status_code < 400 ? 'warning' : 'fail');
    
    $checks[] = [
      'label' => 'HTTP Status',
      'status' => $status_type,
      'result' => $status_code,
      'details' => 'Direct response from server'
    ];
    
    // Check Redirect Chain
    $redirect_location = wp_remote_retrieve_header($response, 'location');
    if (!empty($redirect_location)) {
      $checks[] = [
        'label' => 'Redirect Chain',
        'status' => 'warning',
        'result' => 'Redirect detected',
        'details' => esc_html($redirect_location)
      ];
      
      // Follow and check final destination
      $final_response = wp_remote_get($url, ['timeout' => 10, 'sslverify' => false, 'redirection' => 5]);
      if (!is_wp_error($final_response)) {
        $final_status = wp_remote_retrieve_response_code($final_response);
        $checks[] = [
          'label' => 'Final Destination',
          'status' => $final_status === 200 ? 'pass' : 'fail',
          'result' => $final_status,
          'details' => 'After following redirects'
        ];
      }
    } else {
      $checks[] = [
        'label' => 'Redirect Chain',
        'status' => 'pass',
        'result' => 'No redirects',
        'details' => 'URL loads directly'
      ];
    }
    
    // Check Canonical Destination
    if ($status_code === 200) {
      $body = wp_remote_retrieve_body($response);
      if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\'](https?:\/\/[^"\']+)["\'][^>]*>/i', $body, $matches)) {
        $canonical_url = $matches[1];
        $canonical_response = wp_remote_head($canonical_url, ['timeout' => 10, 'sslverify' => false]);
        
        if (!is_wp_error($canonical_response)) {
          $canonical_status = wp_remote_retrieve_response_code($canonical_response);
          $checks[] = [
            'label' => 'Canonical URL',
            'status' => $canonical_status === 200 ? 'pass' : 'fail',
            'result' => $canonical_status,
            'details' => esc_html($canonical_url)
          ];
        }
      } else {
        $checks[] = [
          'label' => 'Canonical URL',
          'status' => 'warning',
          'result' => 'Not set',
          'details' => 'No canonical tag found in HTML'
        ];
      }
      
      // Check indexability
      if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\'](|[^"\']+)["\'][^>]*>/i', $body, $matches)) {
        $robots_meta = strtolower($matches[1]);
        if (strpos($robots_meta, 'noindex') !== false) {
          $checks[] = [
            'label' => 'Indexability',
            'status' => 'warning',
            'result' => 'Noindex',
            'details' => 'Page has noindex meta tag'
          ];
        } else {
          $checks[] = [
            'label' => 'Indexability',
            'status' => 'pass',
            'result' => 'Indexable',
            'details' => 'No noindex directive found'
          ];
        }
      }
    }
    
    wp_send_json_success(['checks' => $checks]);
  }
}
