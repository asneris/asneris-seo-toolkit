<?php
/**
 * Diagnostics helper functions
 */

class ASNERISSEO_Diagnostics {
  
  public static function ajax_http_test() {
    check_ajax_referer('ASNERISSEO_http_test', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error('Unauthorized');
      return;
    }

    $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
    
    if (empty($url)) {
      wp_send_json_error('No URL provided');
      return;
    }
    
    // Validate URL
    if (!wp_http_validate_url($url)) {
      wp_send_json_error('Invalid URL provided');
      return;
    }

    $checks = [];
    
    // Check HTTP Status Code (use HEAD for lighter request)
    $response = wp_remote_head($url, [
      'timeout' => 10,
      'redirection' => 0,
      'reject_unsafe_urls' => true
    ]);
    
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
      
      // Follow and check final destination (use HEAD for efficiency)
      $final_response = wp_remote_head($url, [
        'timeout' => 10,
        'redirection' => 5,
        'reject_unsafe_urls' => true
      ]);
      
      if (!is_wp_error($final_response)) {
        $final_status = wp_remote_retrieve_response_code($final_response);
        $final_url = wp_remote_retrieve_header($final_response, 'location');
        if (empty($final_url)) {
          $final_url = $url; // No more redirects, this is final
        }
        
        $checks[] = [
          'label' => 'Final Destination',
          'status' => $final_status === 200 ? 'pass' : 'fail',
          'result' => $final_status,
          'details' => 'After following redirects' . (!empty($final_url) && $final_url !== $url ? ': ' . esc_html($final_url) : '')
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
    
    // Check Canonical Destination and Indexability (need GET for HTML body)
    if ($status_code === 200) {
      $get_response = wp_remote_get($url, [
        'timeout' => 10,
        'redirection' => 5,
        'reject_unsafe_urls' => true
      ]);
      
      if (!is_wp_error($get_response)) {
        $body = wp_remote_retrieve_body($get_response);
        
        // Check canonical using DOM parsing
        $canonical_url = self::extract_canonical_from_html($body);
        
        if (!empty($canonical_url)) {
          $canonical_response = wp_remote_head($canonical_url, [
            'timeout' => 10,
            'reject_unsafe_urls' => true,
            'redirection' => 0
          ]);
          
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
        
        // Check indexability (meta robots + X-Robots-Tag header)
        $is_noindex = false;
        $noindex_source = '';
        
        // Check X-Robots-Tag header first
        $x_robots_header = wp_remote_retrieve_header($get_response, 'x-robots-tag');
        if (!empty($x_robots_header) && stripos($x_robots_header, 'noindex') !== false) {
          $is_noindex = true;
          $noindex_source = 'X-Robots-Tag header';
        }
        
        // Check meta robots tag
        $robots_meta = self::extract_robots_meta_from_html($body);
        if (!empty($robots_meta) && stripos($robots_meta, 'noindex') !== false) {
          $is_noindex = true;
          $noindex_source = $noindex_source ? $noindex_source . ' and meta robots tag' : 'meta robots tag';
        }
        
        if ($is_noindex) {
          $checks[] = [
            'label' => 'Indexability',
            'status' => 'warning',
            'result' => 'Noindex',
            'details' => 'Page has noindex directive (' . $noindex_source . ')'
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
  
  /**
   * Extract canonical URL from HTML using DOM parsing
   */
  private static function extract_canonical_from_html($html) {
    if (empty($html)) {
      return '';
    }
    
    // Suppress warnings from malformed HTML
    libxml_use_internal_errors(true);
    
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    
    $links = $dom->getElementsByTagName('link');
    foreach ($links as $link) {
      if ($link->getAttribute('rel') === 'canonical') {
        $href = $link->getAttribute('href');
        libxml_clear_errors();
        return $href;
      }
    }
    
    libxml_clear_errors();
    return '';
  }
  
  /**
   * Extract robots meta content from HTML using DOM parsing
   */
  private static function extract_robots_meta_from_html($html) {
    if (empty($html)) {
      return '';
    }
    
    // Suppress warnings from malformed HTML
    libxml_use_internal_errors(true);
    
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    
    $metas = $dom->getElementsByTagName('meta');
    foreach ($metas as $meta) {
      if (strtolower($meta->getAttribute('name')) === 'robots') {
        $content = $meta->getAttribute('content');
        libxml_clear_errors();
        return $content;
      }
    }
    
    libxml_clear_errors();
    return '';
  }
}

