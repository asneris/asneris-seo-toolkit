<?php
/**
 * Diagnostics - Read-only facts about what exists
 * 
 * Purpose: Show what is detected (no judgments, no fix buttons)
 * - HTTP status
 * - Title tags (count + values)
 * - Meta descriptions
 * - Canonical URLs
 * - Meta robots
 * - Schema blocks (JSON-LD count)
 * - Social meta
 * - Verification tags
 */

if (!defined('ABSPATH')) exit;

class ASNERISSEO_Diagnostics_Page {
  
  /**
   * Register diagnostics page
   */
  public static function register_menu() {
    add_submenu_page(
      ASNERIS_MENU_SLUG,
      __('Page Diagnostics', 'asneris-seo-toolkit'),
      __('Page Diagnostics', 'asneris-seo-toolkit'),
      'manage_options',
      ASNERIS_MENU_SLUG . '-diagnostics',
      [__CLASS__, 'render_page']
    );
  }
  
  /**
   * Enqueue admin styles
   */
  public static function enqueue_assets($hook) {
    // WordPress uses sanitized menu TITLE (not slug) as parent identifier
    if ($hook !== 'asneris-seo-toolkit_page_' . ASNERIS_MENU_SLUG . '-diagnostics') return;
    wp_enqueue_style('ASNERISSEO-admin', ASNERISSEO_URL . 'assets/css/admin-style.css', [], ASNERISSEO_VERSION);
    wp_enqueue_script('jquery');
    
    $inline_js = "jQuery(function(\$){\n" .
      "  \$('#page_selector').on('change', function(){\n" .
      "    var selectedUrl = \$(this).val();\n" .
      "    if (selectedUrl) {\n" .
      "      \$('#test_url').val(selectedUrl);\n" .
      "    }\n" .
      "  });\n" .
      "});";
    wp_add_inline_script('jquery', $inline_js);
  }
  
  /**
   * Analyze a URL for diagnostics (facts only)
   */
  private static function analyze_url($url) {
    // Validate URL
    if (!wp_http_validate_url($url)) {
      return ['error' => 'Invalid URL provided'];
    }
    
    $response = wp_remote_get($url, [
      'timeout' => 15,
      'redirection' => 5,
      'reject_unsafe_urls' => true
    ]);
    
    if (is_wp_error($response)) {
      return ['error' => $response->get_error_message()];
    }
    
    $html = wp_remote_retrieve_body($response);
    $status_code = wp_remote_retrieve_response_code($response);
    $headers = wp_remote_retrieve_headers($response);
    
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    
    $results = [
      'tested_url' => $url,
      'final_url' => wp_remote_retrieve_header($response, 'location') ?: $url,
      'fetch_timestamp' => current_time('mysql'),
      'http_status' => $status_code,
      'x_robots_tag' => wp_remote_retrieve_header($response, 'x-robots-tag'),
      'title_tags' => [],
      'meta_description' => [],
      'canonical' => [],
      'canonical_target_status' => [],
      'robots_meta' => [],
      'og_tags' => [],
      'twitter_tags' => [],
      'schema_blocks' => [],
      'verification_tags' => []
    ];
    
    // Extract title tags
    $titles = $xpath->query('//title');
    foreach ($titles as $title) {
      $results['title_tags'][] = trim($title->textContent);
    }
    
    // Extract meta description
    $descriptions = $xpath->query('//meta[@name="description"]');
    foreach ($descriptions as $desc) {
      $results['meta_description'][] = $desc->getAttribute('content');
    }
    
    // Extract canonical
    $canonicals = $xpath->query('//link[@rel="canonical"]');
    foreach ($canonicals as $canonical) {
      $href = $canonical->getAttribute('href');
      $results['canonical'][] = $href;
      
      // Check canonical target status
      if (!empty($href)) {
        $canon_response = wp_remote_head($href, [
          'timeout' => 5,
          'redirection' => 0,
          'reject_unsafe_urls' => true
        ]);
        
        if (!is_wp_error($canon_response)) {
          $results['canonical_target_status'][$href] = [
            'status' => wp_remote_retrieve_response_code($canon_response),
            'redirects' => !empty(wp_remote_retrieve_header($canon_response, 'location'))
          ];
        }
      }
    }
    
    // Extract robots meta
    $robots = $xpath->query('//meta[@name="robots"]');
    foreach ($robots as $robot) {
      $results['robots_meta'][] = $robot->getAttribute('content');
    }
    
    // Extract Open Graph tags
    $og_tags = $xpath->query('//meta[starts-with(@property, "og:")]');
    foreach ($og_tags as $og) {
      $results['og_tags'][] = [
        'property' => $og->getAttribute('property'),
        'content' => $og->getAttribute('content')
      ];
    }
    
    // Extract Twitter tags
    $twitter_tags = $xpath->query('//meta[starts-with(@name, "twitter:")]');
    foreach ($twitter_tags as $twitter) {
      $results['twitter_tags'][] = [
        'name' => $twitter->getAttribute('name'),
        'content' => $twitter->getAttribute('content')
      ];
    }
    
    // Extract Schema JSON-LD
    $scripts = $xpath->query('//script[@type="application/ld+json"]');
    foreach ($scripts as $script) {
      $json = trim($script->textContent);
      if (!empty($json)) {
        $decoded = json_decode($json, true);
        $results['schema_blocks'][] = [
          'raw' => $json,
          'valid' => json_last_error() === JSON_ERROR_NONE,
          'type' => isset($decoded['@type']) ? $decoded['@type'] : 'Unknown'
        ];
      }
    }
    
    // Extract verification tags
    $google = $xpath->query('//meta[@name="google-site-verification"]');
    if ($google->length > 0) {
      $results['verification_tags']['google'] = $google->item(0)->getAttribute('content');
    }
    
    $bing = $xpath->query('//meta[@name="msvalidate.01"]');
    if ($bing->length > 0) {
      $results['verification_tags']['bing'] = $bing->item(0)->getAttribute('content');
    }
    
    return $results;
  }
  
  /**
   * Render diagnostics page
   */
  public static function render_page() {
    $test_url = isset($_POST['test_url']) ? esc_url_raw(wp_unslash($_POST['test_url'])) : '';
    $results = null;
    
    if (!empty($test_url) && isset($_POST['run_diagnostics']) && check_admin_referer('ASNERISSEO_diagnostics', '_wpnonce', false)) {
      $results = self::analyze_url($test_url);
    }
    ?>
    <div class="wrap ASNERISSEO-admin-wrap">
      <h1>
        <span class="dashicons dashicons-analytics"></span>
        <?php esc_html_e('Page Diagnostics', 'asneris-seo-toolkit'); ?>
        <?php ASNERISSEO_Help_Modal::render_help_icon('page-diagnostics-overview', 'Learn about page diagnostics'); ?>
      </h1>
      <p class="ASNERISSEO-subtitle">
        <?php esc_html_e('Inspect your page\'s search footprint. We show you the simple facts: Is it live? (Connectivity), Is it the master version? (Canonical), and Is it ready to rank? (Indexing & Tags).', 'asneris-seo-toolkit'); ?>
      </p>
      
      <!-- URL Input -->
      <div class="ASNERISSEO-card">
        <h2>
          <span class="dashicons dashicons-search"></span> Analyze Any URL
          <?php ASNERISSEO_Help_Modal::render_help_icon('page-fetch-status', 'Learn about page fetch and HTTP status'); ?>
        </h2>
        
        <form method="post" action="" id="ASNERISSEO-diagnostics-form" style="margin-top: 15px;">
          <?php wp_nonce_field('ASNERISSEO_diagnostics'); ?>
          
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <select id="page_selector" style="flex: 1; max-width: 400px; height: 32px;">
              <option value="">-- Select a page/post --</option>
              <optgroup label="Pages">
                <?php
                $pages = get_pages(['sort_column' => 'post_title', 'number' => 100]);
                foreach ($pages as $page) {
                  $page_url = get_permalink($page->ID);
                  echo '<option value="' . esc_attr($page_url) . '">' . esc_html($page->post_title) . '</option>';
                }
                ?>
              </optgroup>
              <optgroup label="Posts (Latest 50)">
                <?php
                $posts = get_posts(['numberposts' => 50, 'post_status' => 'publish']);
                foreach ($posts as $post) {
                  $post_url = get_permalink($post->ID);
                  echo '<option value="' . esc_attr($post_url) . '">' . esc_html($post->post_title) . '</option>';
                }
                ?>
              </optgroup>
              <optgroup label="Special Pages">
                <option value="<?php echo esc_attr(home_url('/')); ?>">Homepage</option>
                <?php
                if (get_option('page_for_posts')) {
                  echo '<option value="' . esc_attr(get_permalink(get_option('page_for_posts'))) . '">Blog Page</option>';
                }
                ?>
              </optgroup>
            </select>
            <span style="color: #646970;">or</span>
            <input type="url" id="test_url" name="test_url" style="flex: 1; max-width: 400px; height: 32px;" 
                   value="<?php echo esc_attr($test_url ?: home_url('/')); ?>" 
                   placeholder="Enter custom URL" required>
            <button type="submit" name="run_diagnostics" class="button button-primary" style="height: 32px; padding: 0 15px;">
              Run Diagnostics
            </button>
          </div>
        </form>
        
      </div>
      
      <?php if ($results && isset($results['error'])): ?>
        <div class="notice notice-error">
          <p><strong>Error:</strong> <?php echo esc_html($results['error']); ?></p>
        </div>
      <?php endif; ?>
      
      <?php if ($results && !isset($results['error'])): ?>
        
        <!-- Fetch Results -->
        <div class="ASNERISSEO-card">
          <h2>
            <span class="dashicons dashicons-cloud"></span> Page Connection Check
            <?php ASNERISSEO_Help_Modal::render_help_icon('page-fetch-status', 'Learn about HTTP status codes'); ?>
          </h2>
          <table class="widefat striped">
            <thead>
              <tr>
                <th style="width: 200px;">Check</th>
                <th style="width: 100px;">Status</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
            <tr>
              <td><strong>HTTP Status</strong></td>
              <td>
                <?php 
                $code = $results['http_status'];
                if ($code == 200) {
                  echo '<span style="color: #46b450; font-weight: 600;">✓ Pass</span>';
                } elseif ($code >= 300 && $code < 400) {
                  echo '<span style="color: #f0ad4e; font-weight: 600;">⚠ Warning</span>';
                } else {
                  echo '<span style="color: #d63638; font-weight: 600;">✗ Fail</span>';
                }
                ?>
              </td>
              <td>
                <strong style="font-size: 16px;"><?php echo esc_html($code); ?></strong>
                <?php 
                if ($code == 200) echo ' - Page accessible';
                elseif ($code >= 300 && $code < 400) echo ' - Redirect detected';
                elseif ($code >= 400 && $code < 500) echo ' - Client error';
                elseif ($code >= 500) echo ' - Server error';
                ?>
              </td>
            </tr>
            <tr>
              <td><strong>URL Redirect</strong></td>
              <td>
                <?php if ($results['tested_url'] !== $results['final_url']): ?>
                  <span style="color: #f0ad4e; font-weight: 600;">⚠ Warning</span>
                <?php else: ?>
                  <span style="color: #46b450; font-weight: 600;">✓ Pass</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($results['tested_url'] !== $results['final_url']): ?>
                  Redirects to: <code><?php echo esc_html($results['final_url']); ?></code>
                <?php else: ?>
                  No redirect - Direct access
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td><strong>Tested URL</strong></td>
              <td>-</td>
              <td><code><?php echo esc_html($results['tested_url']); ?></code></td>
            </tr>
            <tr>
              <td><strong>Fetch Time</strong></td>
              <td>-</td>
              <td><?php echo esc_html($results['fetch_timestamp']); ?></td>
            </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Canonical Details -->
        <div class="ASNERISSEO-card">
          <h2>
            <span class="dashicons dashicons-admin-links"></span> Canonical Details
            <?php ASNERISSEO_Help_Modal::render_help_icon('canonical-url', 'Learn about canonical URLs'); ?>
          </h2>
          
          <table class="widefat striped">
            <thead>
              <tr>
                <th style="width: 200px;">Check</th>
                <th style="width: 100px;">Status</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
            <tr>
              <td><strong>Canonical Tag</strong></td>
              <td>
                <?php 
                $canon_count = count($results['canonical']);
                if ($canon_count == 1) {
                  echo '<span style="color: #46b450; font-weight: 600;">✓ Pass</span>';
                } elseif ($canon_count > 1) {
                  echo '<span style="color: #d63638; font-weight: 600;">✗ Fail</span>';
                } else {
                  echo '<span style="color: #f0ad4e; font-weight: 600;">⚠ Warning</span>';
                }
                ?>
              </td>
              <td>
                <?php if ($canon_count == 1): ?>
                  Single canonical tag detected (correct)
                <?php elseif ($canon_count > 1): ?>
                  <strong>Multiple canonicals detected: <?php echo esc_html($canon_count); ?></strong> (should be only 1)
                <?php else: ?>
                  No canonical tag found
                <?php endif; ?>
              </td>
            </tr>
            <?php if (!empty($results['canonical'])): ?>
              <?php foreach ($results['canonical'] as $i => $canon): ?>
                <tr>
                  <td><strong>Canonical URL</strong></td>
                  <td>-</td>
                  <td><code><?php echo esc_html($canon); ?></code></td>
                </tr>
                <?php if (isset($results['canonical_target_status'][$canon])): ?>
                  <tr>
                    <td><strong>Target Status</strong></td>
                    <td>
                      <?php 
                      $target_status = $results['canonical_target_status'][$canon]['status'];
                      if ($target_status == 200) {
                        echo '<span style="color: #46b450; font-weight: 600;">✓ Pass</span>';
                      } else {
                        echo '<span style="color: #d63638; font-weight: 600;">✗ Fail</span>';
                      }
                      ?>
                    </td>
                    <td>
                      HTTP <?php echo esc_html($target_status); ?>
                      <?php if ($results['canonical_target_status'][$canon]['redirects']): ?>
                        <span style="color: #f0ad4e;"> - Redirects detected</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endif; ?>
                <tr>
                  <td><strong>Self-Referencing</strong></td>
                  <td>
                    <?php 
                    $input_normalized = untrailingslashit(strtolower($results['tested_url']));
                    $canon_normalized = untrailingslashit(strtolower($canon));
                    echo $input_normalized === $canon_normalized 
                      ? '<span style="color: #46b450; font-weight: 600;">✓ Pass</span>' 
                      : '<span style="color: #f0ad4e; font-weight: 600;">⚠ Warning</span>'; 
                    ?>
                  </td>
                  <td>
                    <?php echo $input_normalized === $canon_normalized ? 'Yes - Points to itself (recommended)' : 'No - Points to different URL'; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <!-- Indexing Signals -->
        <div class="ASNERISSEO-card">
          <h2>
            <span class="dashicons dashicons-admin-site-alt3"></span> Indexing Signals
            <?php ASNERISSEO_Help_Modal::render_help_icon('indexing-rules', 'Learn about indexing rules'); ?>
          </h2>
          
          <table class="widefat striped">
            <thead>
              <tr>
                <th style="width: 250px;">Check</th>
                <th style="width: 100px;">Status</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>HTTP Status Code</strong></td>
                <td>
                  <?php 
                  $code = $results['http_status'];
                  if ($code == 200) {
                    echo '<span style="color: #46b450; font-weight: 600;">✓ Pass</span>';
                  } elseif ($code >= 300 && $code < 400) {
                    echo '<span style="color: #f0ad4e; font-weight: 600;">⚠ Warning</span>';
                  } else {
                    echo '<span style="color: #d63638; font-weight: 600;">✗ Fail</span>';
                  }
                  ?>
                </td>
                <td>
                  <span style="font-size: 16px; font-weight: 600;"><?php echo esc_html($code); ?></span> - 
                  <?php 
                  if ($code == 200) echo 'Indexable (Success)';
                  elseif ($code >= 300 && $code < 400) echo 'Redirect (May affect indexing)';
                  elseif ($code >= 400 && $code < 500) echo 'Not indexable (Client error)';
                  elseif ($code >= 500) echo 'Not indexable (Server error)';
                  ?>
                </td>
              </tr>
              <tr>
                <td><strong>X-Robots-Tag Header</strong></td>
                <td>
                  <?php if (!empty($results['x_robots_tag'])): ?>
                    <?php 
                    $xrob = strtolower($results['x_robots_tag']);
                    if (strpos($xrob, 'noindex') !== false) {
                      echo '<span style="color: #d63638; font-weight: 600;">✗ Blocked</span>';
                    } else {
                      echo '<span style="color: #46b450; font-weight: 600;">✓ Pass</span>';
                    }
                    ?>
                  <?php else: ?>
                    <span style="color: #46b450; font-weight: 600;">✓ Pass</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($results['x_robots_tag'])): ?>
                    <code><?php echo esc_html($results['x_robots_tag']); ?></code>
                  <?php else: ?>
                    Not present - No restrictions
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <td><strong>Meta Robots Tag</strong></td>
                <td>
                  <?php if (!empty($results['robots_meta'])): ?>
                    <?php 
                    $has_noindex = false;
                    foreach ($results['robots_meta'] as $robots) {
                      if (strpos(strtolower($robots), 'noindex') !== false) {
                        $has_noindex = true;
                        break;
                      }
                    }
                    $tag_count = count($results['robots_meta']);
                    if ($has_noindex) {
                      echo '<span style="color: #d63638; font-weight: 600;">✗ Blocked</span>';
                    } elseif ($tag_count > 1) {
                      echo '<span style="color: #f0ad4e; font-weight: 600;">⚠ Warning</span>';
                    } else {
                      echo '<span style="color: #46b450; font-weight: 600;">✓ Pass</span>';
                    }
                    ?>
                  <?php else: ?>
                    <span style="color: #46b450; font-weight: 600;">✓ Pass</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($results['robots_meta'])): ?>
                    <?php 
                    $has_noindex = false;
                    foreach ($results['robots_meta'] as $robots) {
                      if (strpos(strtolower($robots), 'noindex') !== false) {
                        $has_noindex = true;
                        echo '<div style="background: #fef8f8; border-left: 3px solid #d63638; padding: 10px; margin: 5px 0;">';
                        echo '<strong style="color: #d63638;">⚠ Page is set to "noindex" - Search engines will NOT index this page</strong><br>';
                        echo '<span style="color: #646970; font-size: 13px; margin-top: 5px; display: block;">To allow indexing: </span>';
                        echo '<a href="' . esc_url(admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-bulk-edit')) . '" class="button button-small" style="margin-top: 5px;">Go to Bulk Edit</a>';
                        echo '</div>';
                        break;
                      }
                    }
                    if (!$has_noindex) {
                      echo 'No blocking directives - Page can be indexed';
                    }
                    ?>
                    <?php if (count($results['robots_meta']) > 1): ?>
                      <br><div style="background: #fff8e5; border-left: 3px solid #f0ad4e; padding: 10px; margin: 5px 0;">
                        <strong style="color: #f0ad4e;">Multiple meta robots tags detected (<?php echo esc_html(count($results['robots_meta'])); ?>)</strong><br>
                        <span style="color: #646970; font-size: 13px; margin-bottom: 8px; display: block;">Only one tag should exist. Here are the tags found:</span>
                        <?php foreach ($results['robots_meta'] as $index => $robots): ?>
                          <div style="margin: 5px 0; padding: 5px; background: white; border-radius: 3px;">
                            <strong>Tag #<?php echo esc_html($index + 1); ?>:</strong> <code><?php echo esc_html($robots); ?></code>
                          </div>
                        <?php endforeach; ?>
                        <span style="color: #646970; font-size: 13px; margin-top: 8px; display: block;">
                          Check for conflicts with other plugins or your theme.
                        </span>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-validation')); ?>" class="button button-small" style="margin-top: 5px;">Check Site Diagnostics</a>
                      </div>
                    <?php endif; ?>
                  <?php else: ?>
                    Not present - No restrictions
                  <?php endif; ?>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Meta Tags (Title & Description) -->
        <div class="ASNERISSEO-card">
          <h2>
            <span class="dashicons dashicons-editor-textmode"></span> Meta Tags
            <?php ASNERISSEO_Help_Modal::render_help_icon('meta-tags', 'Learn about meta tags'); ?>
          </h2>
          
          <table class="widefat striped">
            <thead>
              <tr>
                <th style="width: 200px;">Check</th>
                <th style="width: 100px;">Status</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
            <tr>
              <td><strong>Title Tag</strong></td>
              <td>
                <?php 
                $title_count = count($results['title_tags']);
                if ($title_count == 1) {
                  $title_length = strlen($results['title_tags'][0]);
                  if ($title_length >= 30 && $title_length <= 60) {
                    echo '<span style="color: #46b450; font-weight: 600;">✓ Pass</span>';
                  } elseif ($title_length > 0) {
                    echo '<span style="color: #f0ad4e; font-weight: 600;">⚠ Warning</span>';
                  } else {
                    echo '<span style="color: #d63638; font-weight: 600;">✗ Fail</span>';
                  }
                } elseif ($title_count > 1) {
                  echo '<span style="color: #d63638; font-weight: 600;">✗ Fail</span>';
                } else {
                  echo '<span style="color: #d63638; font-weight: 600;">✗ Fail</span>';
                }
                ?>
              </td>
              <td>
                <?php if ($title_count == 1): ?>
                  <?php 
                  $title = $results['title_tags'][0];
                  $title_length = strlen($title);
                  ?>
                  <code><?php echo esc_html($title); ?></code><br>
                  <span style="color: <?php echo esc_attr(($title_length >= 30 && $title_length <= 60) ? '#46b450' : '#f0ad4e'); ?>; font-size: 12px;">
                    <?php echo esc_html($title_length); ?> characters
                    <?php if ($title_length < 30): ?>
                      (too short, recommended: 30-60)
                    <?php elseif ($title_length > 60): ?>
                      (too long, recommended: 30-60)
                    <?php else: ?>
                      (optimal length)
                    <?php endif; ?>
                  </span>
                  <?php if ($title_length < 30 || $title_length > 60): ?>
                    <br><a href="<?php echo esc_url(admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-bulk-edit')); ?>" class="button button-small" style="margin-top: 5px;">Edit Title in Bulk Edit</a>
                  <?php endif; ?>
                <?php elseif ($title_count > 1): ?>
                  <strong style="color: #d63638;">Multiple title tags detected: <?php echo esc_html($title_count); ?></strong> (should be only 1)<br>
                  <?php foreach ($results['title_tags'] as $title): ?>
                    <code><?php echo esc_html($title); ?></code> (<?php echo esc_html(strlen($title)); ?> chars)<br>
                  <?php endforeach; ?>
                  <a href="<?php echo esc_url(admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-validation')); ?>" class="button button-small" style="margin-top: 5px;">Check Site Diagnostics</a>
                <?php else: ?>
                  <strong style="color: #d63638;">No title tag found</strong><br>
                  <a href="<?php echo esc_url(admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-bulk-edit')); ?>" class="button button-small" style="margin-top: 5px;">Add Title in Bulk Edit</a>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td><strong>Meta Description</strong></td>
              <td>
                <?php 
                $desc_count = count($results['meta_description']);
                if ($desc_count == 1) {
                  $desc_length = strlen($results['meta_description'][0]);
                  if ($desc_length >= 120 && $desc_length <= 160) {
                    echo '<span style="color: #46b450; font-weight: 600;">✓ Pass</span>';
                  } elseif ($desc_length > 0) {
                    echo '<span style="color: #f0ad4e; font-weight: 600;">⚠ Warning</span>';
                  } else {
                    echo '<span style="color: #d63638; font-weight: 600;">✗ Fail</span>';
                  }
                } elseif ($desc_count > 1) {
                  echo '<span style="color: #d63638; font-weight: 600;">✗ Fail</span>';
                } else {
                  echo '<span style="color: #f0ad4e; font-weight: 600;">⚠ Warning</span>';
                }
                ?>
              </td>
              <td>
                <?php if ($desc_count == 1): ?>
                  <?php 
                  $desc = $results['meta_description'][0];
                  $desc_length = strlen($desc);
                  ?>
                  <code><?php echo esc_html($desc); ?></code><br>
                  <span style="color: <?php echo esc_attr(($desc_length >= 120 && $desc_length <= 160) ? '#46b450' : '#f0ad4e'); ?>; font-size: 12px;">
                    <?php echo esc_html($desc_length); ?> characters
                    <?php if ($desc_length < 120): ?>
                      (too short, recommended: 120-160)
                    <?php elseif ($desc_length > 160): ?>
                      (too long, recommended: 120-160)
                    <?php else: ?>
                      (optimal length)
                    <?php endif; ?>
                  </span>
                  <?php if ($desc_length < 120 || $desc_length > 160): ?>
                    <br><a href="<?php echo esc_url(admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-bulk-edit')); ?>" class="button button-small" style="margin-top: 5px;">Edit Description in Bulk Edit</a>
                  <?php endif; ?>
                <?php elseif ($desc_count > 1): ?>
                  <strong style="color: #d63638;">Multiple meta descriptions detected: <?php echo esc_html($desc_count); ?></strong> (should be only 1)<br>
                  <?php foreach ($results['meta_description'] as $desc): ?>
                    <code><?php echo esc_html($desc); ?></code> (<?php echo esc_html(strlen($desc)); ?> chars)<br>
                  <?php endforeach; ?>
                  <a href="<?php echo esc_url(admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-validation')); ?>" class="button button-small" style="margin-top: 5px;">Check Site Diagnostics</a>
                <?php else: ?>
                  <strong style="color: #f0ad4e;">No meta description found</strong> - Search engines will generate one<br>
                  <a href="<?php echo esc_url(admin_url('admin.php?page=' . ASNERIS_MENU_SLUG . '-bulk-edit')); ?>" class="button button-small" style="margin-top: 5px;">Add Description in Bulk Edit</a>
                <?php endif; ?>
              </td>
            </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Visual Separator for Non-SEO Sections -->
        <div style="margin: 40px 0 30px; padding: 20px; border-top: 3px solid #2271b1; border-bottom: 3px solid #2271b1; background-color: #f0f6fc;">
          <p style="text-align: center; color: #135e96; font-size: 14px; margin: 0; font-weight: 500;">
            <span class="dashicons dashicons-info" style="font-size: 18px; vertical-align: middle; color: #2271b1;"></span>
            <strong>Social Media Preview Section</strong> — These elements enhance social sharing but do not directly impact search engine rankings
          </p>
        </div>
        
        <!-- Social Media Preview Tags -->
        <div class="ASNERISSEO-card">
          <h2>
            <span class="dashicons dashicons-share"></span> Social Media Preview
            <?php ASNERISSEO_Help_Modal::render_help_icon('social-preview', 'Learn about social preview tags'); ?>
          </h2>
          <p style="color: #646970;">Open Graph and Twitter Card tags for social sharing</p>
          
          <h3 style="margin-top: 0;">Open Graph (Facebook/LinkedIn)</h3>
          <p><strong>Count:</strong> <?php echo count($results['og_tags']); ?></p>
          <?php if (!empty($results['og_tags'])): ?>
            <table class="widefat striped">
              <thead><tr><th style="width: 200px;">Property</th><th>Content</th></tr></thead>
              <tbody>
                <?php foreach ($results['og_tags'] as $tag): ?>
                  <tr>
                    <td><code><?php echo esc_html($tag['property']); ?></code></td>
                    <td><?php echo esc_html($tag['content']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p style="color: #646970;">No Open Graph tags detected</p>
          <?php endif; ?>
          
          <h3 style="margin-top: 20px;">Twitter Card</h3>
          <p><strong>Count:</strong> <?php echo count($results['twitter_tags']); ?></p>
          <?php if (!empty($results['twitter_tags'])): ?>
            <table class="widefat striped">
              <thead><tr><th style="width: 200px;">Name</th><th>Content</th></tr></thead>
              <tbody>
                <?php foreach ($results['twitter_tags'] as $tag): ?>
                  <tr>
                    <td><code><?php echo esc_html($tag['name']); ?></code></td>
                    <td><?php echo esc_html($tag['content']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p style="color: #646970;">No Twitter Card tags detected</p>
          <?php endif; ?>
        </div>
        
        <!-- Structured Data (Schema) -->
        <div class="ASNERISSEO-card">
          <h2>
            <span class="dashicons dashicons-editor-code"></span> Structured Data (Schema)
            <?php ASNERISSEO_Help_Modal::render_help_icon('structured-data', 'Learn about structured data'); ?>
          </h2>
          <p style="color: #646970;">JSON-LD structured data blocks for this URL</p>
          
          <p><strong>Schema Block Count:</strong> <?php echo count($results['schema_blocks']); ?></p>
          <?php if (!empty($results['schema_blocks'])): ?>
            <table class="widefat striped">
              <thead><tr><th style="width: 100px;">#</th><th style="width: 150px;">Valid JSON?</th><th>@type</th></tr></thead>
              <tbody>
                <?php foreach ($results['schema_blocks'] as $i => $schema): ?>
                  <tr>
                    <td><?php echo esc_html($i + 1); ?></td>
                    <td><?php echo $schema['valid'] ? '✅ Yes' : '❌ No'; ?></td>
                    <td><code><?php echo esc_html($schema['type']); ?></code></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            
            <details style="margin-top: 15px;">
              <summary style="cursor: pointer; color: #2271b1; font-weight: 600;">Show Raw JSON-LD Data</summary>
              <?php foreach ($results['schema_blocks'] as $i => $schema): ?>
                <h4 style="margin: 15px 0 5px 0;">Block <?php echo esc_html($i + 1); ?></h4>
                <pre style="background: #f6f7f7; padding: 10px; border-left: 3px solid #2271b1; overflow-x: auto; overflow-wrap: break-word; white-space: pre-wrap; word-break: break-all; font-size: 12px; max-width: 100%;"><?php 
                  // Format JSON for better readability
                  $decoded = json_decode($schema['raw'], true);
                  if ($decoded !== null) {
                    echo esc_html(wp_json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                  } else {
                    echo esc_html($schema['raw']);
                  }
                ?></pre>
              <?php endforeach; ?>
            </details>
          <?php else: ?>
            <p style="color: #646970;">No schema blocks detected</p>
          <?php endif; ?>
        </div>
        
      <?php endif; ?>
      </div><!-- .ASNERISSEO-tab-content -->
      </div><!-- .ASNERISSEO-card -->
      
    </div><!-- .wrap -->
    <?php ASNERISSEO_Help_Modal::render_modals('page-diagnostics'); ?>
    <?php
  }
}
