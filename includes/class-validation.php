<?php
if (!defined('ABSPATH')) exit;

class ASNERISSEO_Validation {
  
  /**
   * Register validation page
   */
  public static function register_menu() {
    add_submenu_page(
      ASNERIS_MENU_SLUG,
      __('Site Diagnostics', 'asneris-seo-toolkit'),
      __('Site Diagnostics', 'asneris-seo-toolkit'),
      'manage_options',
      ASNERIS_MENU_SLUG . '-validation',
      [__CLASS__, 'render_page']
    );
  }
  
  /**
   * Enqueue admin styles
   */
  public static function enqueue_assets($hook) {
    // WordPress uses sanitized menu TITLE (not slug) as parent identifier
    if ($hook !== 'asneris-seo-toolkit_page_' . ASNERIS_MENU_SLUG . '-validation') return;
    wp_enqueue_style('ASNERISSEO-admin', ASNERISSEO_URL . 'assets/css/admin-style.css', [], ASNERISSEO_VERSION);
    $inline_css = ".ASNERISSEO-validation-item{\n" .
      "  margin-bottom:20px;\n" .
      "  border-bottom:1px solid #dcdcde;\n" .
      "  padding-bottom:20px;\n" .
      "}\n" .
      ".ASNERISSEO-validation-item:last-child{\n" .
      "  border-bottom:none;\n" .
      "}\n" .
      ".validation-status{\n" .
      "  display:flex;\n" .
      "  gap:15px;\n" .
      "  align-items:flex-start;\n" .
      "}\n" .
      ".validation-icon{\n" .
      "  font-size:32px;\n" .
      "  line-height:1;\n" .
      "  flex-shrink:0;\n" .
      "}\n" .
      ".validation-content h4{\n" .
      "  margin:0 0 8px 0;\n" .
      "  font-size:16px;\n" .
      "}\n" .
      ".validation-content p{\n" .
      "  margin:5px 0;\n" .
      "  line-height:1.6;\n" .
      "}\n" .
      ".validation-pass .validation-content h4{\n" .
      "  color:#1e1e1e;\n" .
      "}\n" .
      ".validation-warning .validation-content h4{\n" .
      "  color:#8a6d3b;\n" .
      "}\n" .
      ".validation-conflict .validation-content h4{\n" .
      "  color:#a94442;\n" .
      "}\n";
    wp_add_inline_style('ASNERISSEO-admin', $inline_css);
    wp_enqueue_script('jquery');
    $nonce = wp_create_nonce('ASNERISSEO_http_test');
    $inline_js = "jQuery(document).ready(function(\$){\n" .
      "  \$('.ASNERISSEO-quick-test').on('click', function(){\n" .
      "    var url = \$(this).data('url');\n" .
      "    \$('#test_url').val(url);\n" .
      "  });\n" .
      "  \$('#ASNERISSEO_page_selector').on('change', function(){\n" .
      "    var selectedUrl = \$(this).val();\n" .
      "    if (selectedUrl) { \$('#test_url').val(selectedUrl); }\n" .
      "  });\n" .
      "  \$('.ASNERISSEO-group-header').on('click', function(){\n" .
      "    var group = \$(this).data('group');\n" .
      "    var content = \$('#group-' + group);\n" .
      "    if (content.is(':visible')) { content.slideUp(); \$(this).removeClass('expanded'); }\n" .
      "    else { content.slideDown(); \$(this).addClass('expanded'); }\n" .
      "  });\n" .
      "  \$('#ASNERISSEO_run_http_test').on('click', function(){\n" .
      "    var url = \$('#ASNERISSEO_test_url').val();\n" .
      "    var button = this;\n" .
      "    var results = \$('#ASNERISSEO_http_results');\n" .
      "    var tbody = \$('#ASNERISSEO_http_results_body');\n" .
      "    if (!url) { alert('Please enter a URL to test'); return; }\n" .
      "    \$(button).prop('disabled', true).text('Testing...');\n" .
      "    tbody.html('<tr><td colspan=\"3\">Running validation...</td></tr>');\n" .
      "    results.show();\n" .
      "    \$.ajax({\n" .
      "      url: ajaxurl,\n" .
      "      method: 'POST',\n" .
      "      data: { action: 'ASNERISSEO_http_test', url: url, nonce: '" . $nonce . "' },\n" .
      "      success: function(response){\n" .
      "        if (response.success) {\n" .
      "          var html = '';\n" .
      "          response.data.checks.forEach(function(check){\n" .
      "            var statusColor = check.status === 'pass' ? '#46b450' : (check.status === 'warning' ? '#f0ad4e' : '#dc3232');\n" .
      "            var statusIcon = check.status === 'pass' ? '✓' : (check.status === 'warning' ? '⚠' : '✗');\n" .
      "            html += '<tr>';\n" .
      "            html += '<td><strong>' + check.label + '</strong></td>';\n" .
      "            html += '<td><span style=\"color: ' + statusColor + ';\">' + statusIcon + ' ' + check.result + '</span></td>';\n" .
      "            html += '<td>' + check.details + '</td>';\n" .
      "            html += '</tr>';\n" .
      "          });\n" .
      "          tbody.html(html);\n" .
      "        } else {\n" .
      "          tbody.html('<tr><td colspan=\"3\" style=\"color: #dc3232;\">Error: ' + response.data + '</td></tr>');\n" .
      "        }\n" .
      "      },\n" .
      "      error: function(){\n" .
      "        tbody.html('<tr><td colspan=\"3\" style=\"color: #dc3232;\">Request failed. Please try again.</td></tr>');\n" .
      "      },\n" .
      "      complete: function(){\n" .
      "        \$(button).prop('disabled', false).text('Run Indexing Validation');\n" .
      "      }\n" .
      "    });\n" .
      "  });\n" .
      "});";
    wp_add_inline_script('jquery', $inline_js);
  }
  

  
  /**
   * Analyze a URL for SEO validation
   */
  public static function analyze_url($url) {
    // Allow WordPress to make requests to itself
    add_filter('http_request_host_is_external', '__return_true');
    add_filter('http_request_reject_unsafe_urls', '__return_false');
    
    $response = wp_remote_get($url, [
      'timeout' => 15,
      'sslverify' => false,
      'redirection' => 5,
      'blocking' => true,
      'httpversion' => '1.1',
    ]);
    
    remove_filter('http_request_host_is_external', '__return_true');
    remove_filter('http_request_reject_unsafe_urls', '__return_false');
    
    if (is_wp_error($response)) {
      return [
        'error' => $response->get_error_message(),
        'url' => $url
      ];
    }
    
    $html = wp_remote_retrieve_body($response);
    
    $results = [
      'url' => $url,
      'title' => [],
      'canonical' => [],
      'robots' => [],
      'description' => [],
      'og_tags' => [],
      'twitter_tags' => [],
      'schema' => [],
      'verification' => [],
    ];
    
    // Parse HTML
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    libxml_clear_errors();
    
    $xpath = new DOMXPath($dom);
    
    // Check title tags
    $titles = $xpath->query('//title');
    foreach ($titles as $title) {
      $results['title'][] = trim($title->textContent);
    }
    
    // Check canonical
    $canonicals = $xpath->query('//link[@rel="canonical"]');
    foreach ($canonicals as $canonical) {
      $results['canonical'][] = $canonical->getAttribute('href');
    }
    
    // Check robots meta
    $robots = $xpath->query('//meta[@name="robots"]');
    foreach ($robots as $robot) {
      $results['robots'][] = $robot->getAttribute('content');
    }
    
    // Check meta description
    $descriptions = $xpath->query('//meta[@name="description"]');
    foreach ($descriptions as $desc) {
      $results['description'][] = $desc->getAttribute('content');
    }
    
    // Check Open Graph tags
    $og_tags = $xpath->query('//meta[starts-with(@property, "og:")]');
    foreach ($og_tags as $og) {
      $property = $og->getAttribute('property');
      $content = $og->getAttribute('content');
      $results['og_tags'][$property] = $content;
    }
    
    // Check Twitter tags
    $twitter_tags = $xpath->query('//meta[starts-with(@name, "twitter:")]');
    foreach ($twitter_tags as $twitter) {
      $name = $twitter->getAttribute('name');
      $content = $twitter->getAttribute('content');
      $results['twitter_tags'][$name] = $content;
    }
    
    // Check verification meta tags
    $google_verifications = $xpath->query('//meta[@name="google-site-verification"]');
    if ($google_verifications->length > 0) {
      $results['verification']['google'] = $google_verifications->item(0)->getAttribute('content');
    }
    
    $bing_verifications = $xpath->query('//meta[@name="msvalidate.01"]');
    if ($bing_verifications->length > 0) {
      $results['verification']['bing'] = $bing_verifications->item(0)->getAttribute('content');
    }
    
    $yandex_verifications = $xpath->query('//meta[@name="yandex-verification"]');
    if ($yandex_verifications->length > 0) {
      $results['verification']['yandex'] = $yandex_verifications->item(0)->getAttribute('content');
    }
    
    // Check Schema JSON-LD
    $scripts = $xpath->query('//script[@type="application/ld+json"]');
    foreach ($scripts as $script) {
      $json = trim($script->textContent);
      if (!empty($json)) {
        $results['schema'][] = $json;
      }
    }
    
    return $results;
  }
  
  /**
   * Get validation status badge
   */
  public static function get_status_badge($count, $expected = 1) {
    if ($count === $expected) {
      return '<span class="ASNERISSEO-status-badge status-pass"> Pass</span>';
    } elseif ($count === 0) {
      return '<span class="ASNERISSEO-status-badge status-warning"> Missing</span>';
    } else {
      return '<span class="ASNERISSEO-status-badge status-fail"> Multiple (' . $count . ')</span>';
    }
  }
  
  /**
   * Get published posts and pages for dropdown
   */
  public static function get_published_content() {
    return get_posts([
      'post_type' => ['post', 'page'],
      'post_status' => 'publish',
      'numberposts' => 100,
      'orderby' => 'modified',
      'order' => 'DESC',
    ]);
  }
  
  /**
   * Analyze sitemap accessibility
   */
  public static function analyze_sitemap() {
    $sitemap_url = home_url('/wp-sitemap.xml');
    $response = wp_remote_get($sitemap_url, ['timeout' => 5]);
    
    $result = [
      'url' => $sitemap_url,
      'status' => 'not_exists',
      'page_count' => 0,
    ];
    
    if (!is_wp_error($response)) {
      $status_code = wp_remote_retrieve_response_code($response);
      if ($status_code === 200) {
        $result['status'] = 'exists';
        $xml = wp_remote_retrieve_body($response);
        $result['page_count'] = substr_count($xml, '<loc>');
      }
    }
    
    return $result;
  }
  
  /**
   * Analyze robots.txt file
   */
  public static function analyze_robots_txt() {
    $robots_url = home_url('/robots.txt');
    $response = wp_remote_get($robots_url, ['timeout' => 5]);
    
    $result = [
      'url' => $robots_url,
      'status' => 'not_exists',
      'rules' => [],
    ];
    
    if (!is_wp_error($response)) {
      $status = wp_remote_retrieve_response_code($response);
      if ($status === 200) {
        $result['status'] = 'exists';
        $content = wp_remote_retrieve_body($response);
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
          $line = trim($line);
          if (!empty($line) && strpos($line, '#') !== 0) {
            $result['rules'][] = $line;
          }
        }
      }
    }
    
    return $result;
  }
  

  
  /**
   * Analyze heading structure
   */
  public static function analyze_heading_structure($html) {
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    
    $result = [
      'has_h1' => false,
      'h1_count' => 0,
      'h2_count' => 0,
      'h3_count' => 0,
      'h4_count' => 0,
      'h5_count' => 0,
      'h6_count' => 0,
      'headings' => [],
      'hierarchy_issues' => [],
    ];
    
    for ($i = 1; $i <= 6; $i++) {
      $headings = $xpath->query('//h' . $i);
      $result['h' . $i . '_count'] = $headings->length;
      foreach ($headings as $heading) {
        $result['headings'][] = [
          'tag' => 'H' . $i,
          'text' => trim($heading->textContent),
        ];
      }
    }
    
    $result['has_h1'] = ($result['h1_count'] > 0);
    
    // Check for hierarchy issues (warnings only)
    if ($result['h1_count'] > 1) {
      $result['hierarchy_issues'][] = 'Multiple H1 tags found';
    }
    if ($result['h1_count'] > 0 && $result['h2_count'] === 0 && $result['h3_count'] > 0) {
      $result['hierarchy_issues'][] = 'Heading hierarchy skip (H1H3 without H2)';
    }
    
    return $result;
  }
  
  /**
   * Analyze images for alt text and size
   */
  public static function analyze_images($html, $og_image = '', $twitter_image = '') {
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    
    $result = [
      'total' => 0,
      'with_alt' => 0,
      'without_alt' => 0,
      'size_warnings' => [],
    ];
    
    $images = $xpath->query('//img');
    $result['total'] = $images->length;
    
    foreach ($images as $img) {
      $alt = $img->getAttribute('alt');
      if (!empty($alt)) {
        $result['with_alt']++;
      } else {
        $result['without_alt']++;
      }
    }
    
    // Check social image sizes
    if (!empty($og_image)) {
      $size = self::get_remote_image_size($og_image);
      if ($size && $size > 300000) {
        $result['size_warnings'][] = 'og:image is ' . round($size / 1024) . 'KB (recommended < 300KB)';
      }
    }
    
    if (!empty($twitter_image) && $twitter_image !== $og_image) {
      $size = self::get_remote_image_size($twitter_image);
      if ($size && $size > 300000) {
        $result['size_warnings'][] = 'twitter:image is ' . round($size / 1024) . 'KB (recommended < 300KB)';
      }
    }
    
    return $result;
  }
  
  /**
   * Get remote image file size
   */
  private static function get_remote_image_size($url) {
    $response = wp_remote_head($url, ['timeout' => 5]);
    if (is_wp_error($response)) {
      return null;
    }
    
    $headers = wp_remote_retrieve_headers($response);
    return isset($headers['content-length']) ? (int)$headers['content-length'] : null;
  }
  
  /**
   * Analyze internal links
   */
  public static function analyze_internal_links($html, $base_url) {
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    
    $result = [
      'internal' => 0,
      'external' => 0,
    ];
    
    $links = $xpath->query('//a[@href]');
    $parsed_base = wp_parse_url($base_url);
    $base_host = isset($parsed_base['host']) ? $parsed_base['host'] : '';
    
    foreach ($links as $link) {
      $href = $link->getAttribute('href');
      $parsed = wp_parse_url($href);
      
      if (!isset($parsed['host']) || $parsed['host'] === $base_host) {
        $result['internal']++;
      } else {
        $result['external']++;
      }
    }
    
    return $result;
  }
  
  /**
   * Validate schema blocks
   */
  public static function validate_schema_blocks($schema_array) {
    $result = [
      'status' => 'pass',
      'message' => 'Schema is valid',
    ];
    
    if (empty($schema_array)) {
      $result['status'] = 'fail';
      $result['message'] = 'No schema found';
      return $result;
    }
    
    $types = [];
    foreach ($schema_array as $schema_json) {
      $schema = json_decode($schema_json, true);
      if (isset($schema['@type'])) {
        $types[] = $schema['@type'];
      }
      if (isset($schema['@graph'])) {
        foreach ($schema['@graph'] as $item) {
          if (isset($item['@type'])) {
            $types[] = $item['@type'];
          }
        }
      }
    }
    
    // Check for duplicate conflicting types
    $type_counts = array_count_values($types);
    foreach ($type_counts as $type => $count) {
      if ($count > 1 && in_array($type, ['Article', 'WebPage', 'Product'])) {
        $result['status'] = 'warning';
        $result['message'] = 'Duplicate ' . $type . ' schema found';
        break;
      }
    }
    
    return $result;
  }
  
  /**
   * Get IndexNow status
   */
  public static function get_indexnow_status() {
    $settings = get_option('ASNERISSEO_settings', []);
    $api_key = isset($settings['indexnow_key']) ? $settings['indexnow_key'] : '';
    
    return [
      'configured' => !empty($api_key),
      'api_key' => $api_key,
    ];
  }
  
  /**
   * Prepare all validation data for templates
   */
  private static function prepare_validation_data() {
    $data = [
      'published_posts' => self::get_published_content(),
      'test_url' => '',
      'results' => null,
      'sitemap' => null,
      'robots' => null,
      'headings' => null,
      'images' => null,
      'links' => null,
      'schema_check' => null,
      'indexnow' => null,
      'score' => null,
    ];
    
    // Handle form submission
    $test_url = isset($_POST['test_url']) ? esc_url_raw(wp_unslash($_POST['test_url'])) : '';
    $run_test = isset($_POST['run_validation']) && check_admin_referer('ASNERISSEO_validation', '_wpnonce', false);
    
    $data['test_url'] = $test_url;
    
    // Run analysis if requested
    if ($run_test && !empty($test_url)) {
      $results = self::analyze_url($test_url);
      
      if (!isset($results['error'])) {
        // Extract OG and Twitter tag arrays
        $og_title = isset($results['og_tags']['og:title']) ? [$results['og_tags']['og:title']] : [];
        $og_description = isset($results['og_tags']['og:description']) ? [$results['og_tags']['og:description']] : [];
        $og_image = isset($results['og_tags']['og:image']) ? [$results['og_tags']['og:image']] : [];
        $twitter_card = isset($results['twitter_tags']['twitter:card']) ? [$results['twitter_tags']['twitter:card']] : [];
        $twitter_title = isset($results['twitter_tags']['twitter:title']) ? [$results['twitter_tags']['twitter:title']] : [];
        $twitter_description = isset($results['twitter_tags']['twitter:description']) ? [$results['twitter_tags']['twitter:description']] : [];
        $twitter_image = isset($results['twitter_tags']['twitter:image']) ? [$results['twitter_tags']['twitter:image']] : [];
        $google_verification = isset($results['verification']['google']) ? [$results['verification']['google']] : [];
        $msvalidate = isset($results['verification']['bing']) ? [$results['verification']['bing']] : [];
        $yandex_verification = isset($results['verification']['yandex']) ? [$results['verification']['yandex']] : [];
        
        // Add extracted arrays to results
        $results['og_title'] = $og_title;
        $results['og_description'] = $og_description;
        $results['og_image'] = $og_image;
        $results['twitter_card'] = $twitter_card;
        $results['twitter_title'] = $twitter_title;
        $results['twitter_description'] = $twitter_description;
        $results['twitter_image'] = $twitter_image;
        $results['google_verification'] = $google_verification;
        $results['msvalidate'] = $msvalidate;
        $results['yandex_verification'] = $yandex_verification;
        
        // Run additional analyses
        $data['sitemap'] = self::analyze_sitemap();
        $data['robots'] = self::analyze_robots_txt();
        $data['schema_check'] = !empty($results['schema']) ? self::validate_schema_blocks($results['schema']) : ['status' => 'fail', 'message' => 'No schema found'];
        $data['indexnow'] = self::get_indexnow_status();
        
        // Analyze content
        $response = wp_remote_get($test_url, ['timeout' => 10, 'sslverify' => false]);
        if (!is_wp_error($response)) {
          $html = wp_remote_retrieve_body($response);
          $data['headings'] = self::analyze_heading_structure($html);
          $og_img = !empty($og_image) ? $og_image[0] : null;
          $tw_img = !empty($twitter_image) ? $twitter_image[0] : null;
          $data['images'] = self::analyze_images($html, $og_img, $tw_img);
          $data['links'] = self::analyze_internal_links($html, $test_url);
        }
        
        $data['results'] = $results;
        $data['score'] = self::calculate_overall_score($results, $data);
        
        // Save validation results for dashboard
        self::save_validation_results($data);
      } else {
        $data['results'] = $results; // Contains error
      }
    }
    
    return $data;
  }
  
  /**
   * Calculate overall SEO score with weighted priorities
   * Based on Asneris principles: Critical > Recommended > Optimization
   */
  private static function calculate_overall_score($results, $data) {
    $score = 0;
    $checks = [
      'critical' => ['passed' => 0, 'total' => 0],
      'recommended' => ['passed' => 0, 'total' => 0],
      'optimization' => ['passed' => 0, 'total' => 0],
    ];
    
    // CRITICAL (60% weight) - Blocks indexing or causes major issues
    // Note: HTTP status and robots indexability checks moved to Diagnostics
    $checks['critical']['total'] = 0;
    
    // RECOMMENDED (30% weight) - Presentation & discovery
    $checks['recommended']['total'] = 5;
    if (count($results['title']) === 1) $checks['recommended']['passed']++;
    if (count($results['description']) === 1) $checks['recommended']['passed']++;
    if (count($results['canonical']) === 1) $checks['recommended']['passed']++;
    if ($data['sitemap'] && isset($data['sitemap']['status']) && $data['sitemap']['status'] === 'exists') $checks['recommended']['passed']++;
    if ($data['robots'] && isset($data['robots']['status']) && $data['robots']['status'] === 'exists') $checks['recommended']['passed']++;
    if ($data['headings'] && $data['headings']['has_h1']) $checks['recommended']['passed']++;
    
    // OPTIMIZATION (10% weight) - Enhanced features
    $checks['optimization']['total'] = 13;
    // Social media (6)
    if (count($results['og_title']) >= 1) $checks['optimization']['passed']++;
    if (count($results['og_description']) >= 1) $checks['optimization']['passed']++;
    if (count($results['og_image']) >= 1) $checks['optimization']['passed']++;
    if (count($results['twitter_card']) >= 1) $checks['optimization']['passed']++;
    if (count($results['twitter_title']) >= 1) $checks['optimization']['passed']++;
    if (count($results['twitter_description']) >= 1) $checks['optimization']['passed']++;
    // Rich results (1)
    if ($data['schema_check'] && $data['schema_check']['status'] === 'pass') $checks['optimization']['passed']++;
    // Content (2)
    if ($data['images'] && $data['images']['with_alt'] > 0) $checks['optimization']['passed']++;
    if ($data['links'] && ($data['links']['internal'] > 0 || $data['links']['external'] > 0)) $checks['optimization']['passed']++;
    // Verification (3)
    if (count($results['google_verification']) >= 1) $checks['optimization']['passed']++;
    if (count($results['msvalidate']) >= 1) $checks['optimization']['passed']++;
    if (count($results['yandex_verification']) >= 1) $checks['optimization']['passed']++;
    // Performance (1)
    if ($data['indexnow'] && $data['indexnow']['configured']) $checks['optimization']['passed']++;
    
    // Calculate weighted score (critical checks moved to Diagnostics)
    $recommended_score = $checks['recommended']['total'] > 0 
      ? ($checks['recommended']['passed'] / $checks['recommended']['total']) * 70 
      : 0;
    $optimization_score = $checks['optimization']['total'] > 0 
      ? ($checks['optimization']['passed'] / $checks['optimization']['total']) * 30 
      : 0;
    
    $percentage = round($recommended_score + $optimization_score);
    
    $total_passed = $checks['critical']['passed'] + $checks['recommended']['passed'] + $checks['optimization']['passed'];
    $total_checks = $checks['critical']['total'] + $checks['recommended']['total'] + $checks['optimization']['total'];
    
    // Determine color and status based on percentage
    if ($percentage >= 90) {
      $color = '#00a32a';
      $status_text = __('Excellent', 'asneris-seo-toolkit');
    } elseif ($percentage >= 70) {
      $color = '#00a32a';
      $status_text = __('Good', 'asneris-seo-toolkit');
    } elseif ($percentage >= 50) {
      $color = '#f0c33c';
      $status_text = __('Fair', 'asneris-seo-toolkit');
    } else {
      $color = '#d63638';
      $status_text = __('Needs Work', 'asneris-seo-toolkit');
    }
    
    return [
      'percentage' => $percentage,
      'passed' => $total_passed,
      'total' => $total_checks,
      'color' => $color,
      'status_text' => $status_text,
      'checks' => $checks, // Include breakdown for educational messaging
    ];
  }
  
  /**
   * Check sitemap visibility and status
   */
  public static function check_sitemap_visibility() {
    $site_url = home_url();
    $sitemap_urls = [
      $site_url . '/wp-sitemap.xml', // WordPress Core
      $site_url . '/sitemap.xml',
      $site_url . '/sitemap_index.xml',
    ];
    
    $result = [
      'found' => false,
      'url' => 'Not found',
      'http_status' => 0,
      'http_message' => 'Not checked',
      'in_robots' => false,
      'robots_message' => 'Not found in robots.txt',
      'controller' => 'Unknown'
    ];
    
    // Check which sitemap exists
    foreach ($sitemap_urls as $url) {
      $response = wp_remote_head($url, ['timeout' => 5, 'sslverify' => false]);
      if (!is_wp_error($response)) {
        $status = wp_remote_retrieve_response_code($response);
        if ($status === 200) {
          $result['found'] = true;
          $result['url'] = $url;
          $result['http_status'] = $status;
          $result['http_message'] = 'Sitemap is accessible';
          break;
        }
      }
    }
    
    // Check robots.txt
    if ($result['found']) {
      $robots_url = $site_url . '/robots.txt';
      $robots_response = wp_remote_get($robots_url, ['timeout' => 5, 'sslverify' => false]);
      if (!is_wp_error($robots_response)) {
        $robots_content = wp_remote_retrieve_body($robots_response);
        if (stripos($robots_content, 'sitemap:') !== false && stripos($robots_content, basename($result['url'])) !== false) {
          $result['in_robots'] = true;
          $result['robots_message'] = 'Sitemap is declared in robots.txt';
        }
      }
    }
    
    // Detect controller
    if (function_exists('wp_sitemaps_get_server')) {
      $result['controller'] = '<strong>WordPress Core</strong> (Built-in sitemaps since WP 5.5)';
    } elseif (defined('WPSEO_VERSION')) {
      $result['controller'] = '<strong>Yoast SEO</strong> (Version ' . WPSEO_VERSION . ')';
    } elseif (class_exists('RankMath')) {
      $result['controller'] = '<strong>Rank Math</strong>';
    } elseif (class_exists('AIOSEO\\Plugin\\AIOSEO')) {
      $result['controller'] = '<strong>All in One SEO</strong>';
    } elseif (function_exists('the_seo_framework')) {
      $result['controller'] = '<strong>The SEO Framework</strong>';
    } else {
      $result['controller'] = 'Unknown plugin or theme generating sitemaps';
    }
    
    return $result;
  }

  /**
   * Detect duplicate SEO outputs
   */
  public static function detect_duplicate_outputs() {
    $active_seo_plugins = [];
    $known_plugins = [
      'wordpress-seo/wp-seo.php' => 'Yoast SEO',
      'seo-by-rank-math/rank-math.php' => 'Rank Math',
      'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
      'autodescription/autodescription.php' => 'The SEO Framework',
      'wp-seopress/seopress.php' => 'SEOPress',
      'squirrly-seo/squirrly.php' => 'Squirrly SEO',
    ];
    
    foreach ($known_plugins as $plugin_file => $plugin_name) {
      if (is_plugin_active($plugin_file)) {
        $active_seo_plugins[] = $plugin_name;
      }
    }
    
    $duplicates = [];
    if (count($active_seo_plugins) > 0) {
      $duplicates['title'] = 'Multiple SEO plugins detected - check homepage source';
      $duplicates['description'] = 'Multiple SEO plugins detected - check homepage source';
      $duplicates['canonical'] = 'Multiple SEO plugins detected - check homepage source';
      $duplicates['robots'] = 'Multiple SEO plugins detected - check homepage source';
      $duplicates['schema'] = 'Multiple SEO plugins detected - check homepage source';
    }
    
    return [
      'active_plugins' => $active_seo_plugins,
      'duplicates' => $duplicates
    ];
  }

  /**
   * Save validation results to database
   */
  private static function save_validation_results($data) {
    // Count warnings and conflicts
    $warnings = 0;
    $conflicts = 0;
    
    if (isset($data['results'])) {
      // Check for multiple/missing critical elements
      if (isset($data['results']['title']) && count($data['results']['title']) !== 1) $conflicts++;
      if (isset($data['results']['description']) && count($data['results']['description']) > 1) $warnings++;
      if (isset($data['results']['canonical']) && count($data['results']['canonical']) !== 1) $conflicts++;
      
      // Check sitemap and robots
      if ($data['sitemap'] && $data['sitemap']['status'] !== 'exists') $warnings++;
      if ($data['robots'] && $data['robots']['status'] !== 'exists') $warnings++;
    }
    
    $summary = [
      'last_checked' => 'Today',
      'timestamp' => current_time('timestamp'),
      'warnings' => $warnings,
      'conflicts' => $conflicts,
      'passed' => isset($data['score']) ? $data['score']['passed'] : 0,
    ];
    
    update_option('ASNERISSEO_validation_summary', $summary);
  }
  
  /**
   * Save diagnostics summary (called when Site Diagnostics page loads)
   */
  private static function save_diagnostics_summary() {
    $sitemap_status = self::check_sitemap_visibility();
    $duplicate_status = self::detect_duplicate_outputs();
    
    // Count warnings and issues
    $warnings = 0;
    $conflicts = 0;
    
    // Sitemap checks
    if (!$sitemap_status['found']) $conflicts++;
    if ($sitemap_status['http_status'] !== 200) $conflicts++;
    if (!$sitemap_status['in_robots']) $warnings++;
    
    // Plugin conflicts
    if (!empty($duplicate_status['active_plugins'])) {
      $conflicts += count($duplicate_status['active_plugins']);
    }
    
    $summary = [
      'last_checked' => 'Today',
      'timestamp' => current_time('timestamp'),
      'warnings' => $warnings,
      'conflicts' => $conflicts,
      'passed' => 0, // Site diagnostics doesn't have a "passed" count
    ];
    
    update_option('ASNERISSEO_validation_summary', $summary);
  }
  
  /**
   * Get saved validation results
   */
  public static function get_saved_results() {
    return get_option('ASNERISSEO_validation_summary', null);
  }
  
  /**
   * Render validation checklist page
   */
  public static function render_page() {
    // Prepare all validation data
    $data = self::prepare_validation_data();
    extract($data);
    
    // Save diagnostics results when page loads
    // This happens automatically for Site Diagnostics (no form submission needed)
    self::save_diagnostics_summary();
    
    // Page content with inline header and footer
    ?>
    <div class="wrap ASNERISSEO-admin-wrap">
      <h1>
        <span class="dashicons dashicons-analytics"></span>
        <?php esc_html_e('Site Diagnostics', 'asneris-seo-toolkit'); ?>
        <?php ASNERISSEO_Help_Modal::render_help_icon('site-diagnostics-overview', 'Learn about Site Diagnostics'); ?>
      </h1>
      <p class="ASNERISSEO-subtitle"><?php esc_html_e('Scan your site\'s master settings. We check the global rules that tell search engines how to find, map, and show your entire website to the world.', 'asneris-seo-toolkit'); ?></p>
      
      <div class="ASNERISSEO-settings-form">
        <div class="ASNERISSEO-tab-content">
    <?php
    
    // Load main content template
    $template_dir = plugin_dir_path(dirname(__FILE__)) . 'templates/validation/';
    include $template_dir . 'tab-diagnostics.php';
    
    ?>
        </div><!-- .ASNERISSEO-tab-content -->
      </div><!-- .ASNERISSEO-settings-form -->
        
      <?php // ASNERISSEO_Help_Content::render_sidebar('site-diagnostics'); ?>
    </div><!-- .wrap -->

    <?php ASNERISSEO_Help_Modal::render_modals('site-diagnostics'); ?>
    <?php
  }
}
