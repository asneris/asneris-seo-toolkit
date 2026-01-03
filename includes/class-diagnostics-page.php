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

class CFSEO_Diagnostics_Page {
  
  /**
   * Register diagnostics page
   */
  public static function register_menu() {
    add_submenu_page(
      'clarity-first-seo',
      __('Page Diagnostics', 'cfseo'),
      __('Page Diagnostics', 'cfseo'),
      'manage_options',
      'cfseo-diagnostics',
      [__CLASS__, 'render_page']
    );
  }
  
  /**
   * Enqueue admin styles
   */
  public static function enqueue_assets($hook) {
    if ($hook !== 'clarity-first-seo_page_cfseo-diagnostics') return;
    wp_enqueue_style('cfseo-admin', CFSEO_URL . 'assets/css/admin-style.css', [], CFSEO_VERSION);
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
    
    if (!empty($test_url) && isset($_POST['run_diagnostics']) && check_admin_referer('CFSEO_diagnostics', '_wpnonce', false)) {
      $results = self::analyze_url($test_url);
    }
    ?>
    <div class="wrap cfseo-admin-wrap has-sidebar">
      <h1>
        <span class="dashicons dashicons-analytics"></span>
        <?php _e('Page Diagnostics', 'cfseo'); ?>
      </h1>
      <p class="cfseo-subtitle">
        <?php _e('Fetch and inspect a single page - read-only facts only', 'cfseo'); ?>
      </p>
      
      <!-- URL Input -->
      <div class="cfseo-card">
        <h2><span class="dashicons dashicons-search"></span> Analyze Any URL</h2>
        <p style="color: #646970;">Fetch & inspect a single page</p>
        
        <form method="post" action="" id="cfseo-diagnostics-form">
          <?php wp_nonce_field('CFSEO_diagnostics'); ?>
          
          <table class="form-table">
            <tr>
              <th scope="row"><label for="page_selector">Select Page</label></th>
              <td>
                <select id="page_selector" class="large-text" style="max-width: 600px;">
                  <option value="">-- Select a page or enter custom URL below --</option>
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
                <p class="description">Select a page from the list or enter a custom URL below</p>
              </td>
            </tr>
            <tr>
              <th scope="row"><label for="test_url">Or Custom URL</label></th>
              <td>
                <input type="url" id="test_url" name="test_url" class="large-text" 
                       value="<?php echo esc_attr($test_url ?: home_url('/')); ?>" 
                       placeholder="<?php echo esc_attr(home_url('/')); ?>" required>
                <button type="submit" name="run_diagnostics" class="button button-primary">
                  Run Diagnostics
                </button>
                <p class="description">Or enter any URL from your site manually</p>
              </td>
            </tr>
          </table>
        </form>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
          const selector = document.getElementById('page_selector');
          const urlInput = document.getElementById('test_url');
          
          selector.addEventListener('change', function() {
            if (this.value) {
              urlInput.value = this.value;
            }
          });
        });
        </script>
      </div>
      
      <?php if ($results && isset($results['error'])): ?>
        <div class="notice notice-error">
          <p><strong>Error:</strong> <?php echo esc_html($results['error']); ?></p>
        </div>
      <?php endif; ?>
      
      <?php if ($results && !isset($results['error'])): ?>
        
        <!-- Fetch Results -->
        <div class="cfseo-card">
          <h2>Fetch Results</h2>
          <table class="widefat">
            <tr>
              <th style="width: 200px;">Tested URL</th>
              <td><code><?php echo esc_html($results['tested_url']); ?></code></td>
            </tr>
            <tr>
              <th>Final URL</th>
              <td><code><?php echo esc_html($results['final_url']); ?></code></td>
            </tr>
            <tr>
              <th>Fetch Status</th>
              <td><strong><?php echo esc_html($results['http_status']); ?></strong></td>
            </tr>
            <tr>
              <th>Timestamp</th>
              <td><?php echo esc_html($results['fetch_timestamp']); ?></td>
            </tr>
          </table>
        </div>
        
        <!-- Canonical Details -->
        <div class="cfseo-card">
          <h2><span class="dashicons dashicons-admin-links"></span> Canonical Details</h2>
          <p style="color: #646970;">Canonical tags and target status for this URL</p>
          
          <table class="widefat striped">
            <tr>
              <th style="width: 200px;">Canonical Count</th>
              <td><strong><?php echo count($results['canonical']); ?></strong></td>
            </tr>
            <?php if (!empty($results['canonical'])): ?>
              <?php foreach ($results['canonical'] as $i => $canon): ?>
                <tr>
                  <th>Canonical <?php echo $i + 1; ?> href</th>
                  <td><code><?php echo esc_html($canon); ?></code></td>
                </tr>
                <?php if (isset($results['canonical_target_status'][$canon])): ?>
                  <tr>
                    <th>Target HTTP Status</th>
                    <td>
                      <strong><?php echo esc_html($results['canonical_target_status'][$canon]['status']); ?></strong>
                      <?php if ($results['canonical_target_status'][$canon]['redirects']): ?>
                        <span style="color: #f0ad4e;"> (redirects detected)</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endif; ?>
                <tr>
                  <th>Same as Input URL?</th>
                  <td>
                    <?php 
                    $input_normalized = untrailingslashit(strtolower($results['tested_url']));
                    $canon_normalized = untrailingslashit(strtolower($canon));
                    echo $input_normalized === $canon_normalized ? '✅ Yes (self-referencing)' : '⚠️ No (points to different URL)'; 
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <th>Status</th>
                <td style="color: #646970;">No canonical tag found</td>
              </tr>
            <?php endif; ?>
          </table>
        </div>
        
        <!-- Indexing Signals -->
        <div class="cfseo-card">
          <h2><span class="dashicons dashicons-admin-site-alt3"></span> Indexing Signals</h2>
          <p style="color: #646970;">Signals that may affect indexing for this URL</p>
          
          <table class="widefat striped">
            <thead>
              <tr>
                <th style="width: 250px;">Signal</th>
                <th>Detected Value</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>HTTP Status Code</strong></td>
                <td>
                  <span style="font-size: 18px; font-weight: 600;"><?php echo esc_html($results['http_status']); ?></span>
                  <p style="color: #646970; margin: 5px 0 0 0; font-size: 13px;">
                    <?php 
                    $code = $results['http_status'];
                    if ($code == 200) echo 'Success response';
                    elseif ($code >= 300 && $code < 400) echo 'Redirect response';
                    elseif ($code >= 400 && $code < 500) echo 'Client error';
                    elseif ($code >= 500) echo 'Server error';
                    ?>
                  </p>
                </td>
              </tr>
              <tr>
                <td><strong>X-Robots-Tag Header</strong></td>
                <td>
                  <?php if (!empty($results['x_robots_tag'])): ?>
                    <code><?php echo esc_html($results['x_robots_tag']); ?></code>
                  <?php else: ?>
                    <span style="color: #646970;">Not present</span>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <td><strong>Meta Robots</strong></td>
                <td>
                  <?php if (!empty($results['robots_meta'])): ?>
                    <?php foreach ($results['robots_meta'] as $robots): ?>
                      <code><?php echo esc_html($robots); ?></code><br>
                    <?php endforeach; ?>
                    <p style="color: #646970; margin: 5px 0 0 0; font-size: 13px;">
                      Count: <?php echo count($results['robots_meta']); ?>
                    </p>
                  <?php else: ?>
                    <span style="color: #646970;">Not present</span>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <td><strong>Redirect Detected</strong></td>
                <td>
                  <?php if ($results['tested_url'] !== $results['final_url']): ?>
                    Yes → <code><?php echo esc_html($results['final_url']); ?></code>
                  <?php else: ?>
                    <span style="color: #646970;">No redirect</span>
                  <?php endif; ?>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Meta & Schema Inspection -->
        <div class="cfseo-card">
          <h2><span class="dashicons dashicons-editor-code"></span> Meta & Schema Inspection</h2>
          <p style="color: #646970;">Title, description, social tags, and JSON-LD for this URL</p>
          
          <!-- SEO Snippet Basics -->
          <h3 style="margin-top: 0;">SEO Snippet Basics</h3>
          <table class="widefat striped">
            <tr>
              <th style="width: 200px;">Title Tags</th>
              <td>
                <strong>Count:</strong> <?php echo count($results['title_tags']); ?><br>
                <?php if (!empty($results['title_tags'])): ?>
                  <?php foreach ($results['title_tags'] as $title): ?>
                    <code><?php echo esc_html($title); ?></code> (<?php echo strlen($title); ?> characters)<br>
                  <?php endforeach; ?>
                <?php else: ?>
                  <span style="color: #646970;">Not detected</span>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <th>Meta Description</th>
              <td>
                <strong>Count:</strong> <?php echo count($results['meta_description']); ?><br>
                <?php if (!empty($results['meta_description'])): ?>
                  <?php foreach ($results['meta_description'] as $desc): ?>
                    <code><?php echo esc_html($desc); ?></code> (<?php echo strlen($desc); ?> characters)<br>
                  <?php endforeach; ?>
                <?php else: ?>
                  <span style="color: #646970;">Not detected</span>
                <?php endif; ?>
              </td>
            </tr>
          </table>
          
          <!-- Social Meta -->
          <h3 style="margin-top: 25px;">Social Meta</h3>
          <h4 style="margin: 10px 0 5px 0;">Open Graph (Facebook/LinkedIn)</h4>
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
          
          <h4 style="margin: 20px 0 5px 0;">Twitter Card</h4>
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
          
          <!-- Schema -->
          <h3 style="margin-top: 25px;">Schema (JSON-LD)</h3>
          <p><strong>Count:</strong> <?php echo count($results['schema_blocks']); ?></p>
          <?php if (!empty($results['schema_blocks'])): ?>
            <table class="widefat striped">
              <thead><tr><th style="width: 100px;">#</th><th style="width: 150px;">Valid JSON?</th><th>@type</th></tr></thead>
              <tbody>
                <?php foreach ($results['schema_blocks'] as $i => $schema): ?>
                  <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo $schema['valid'] ? '✅ Yes' : '❌ No'; ?></td>
                    <td><code><?php echo esc_html($schema['type']); ?></code></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            
            <details style="margin-top: 15px;">
              <summary style="cursor: pointer; color: #2271b1; font-weight: 600;">Show Raw JSON-LD Data</summary>
              <?php foreach ($results['schema_blocks'] as $i => $schema): ?>
                <h4 style="margin: 15px 0 5px 0;">Block <?php echo $i + 1; ?></h4>
                <pre style="background: #f6f7f7; padding: 10px; border-left: 3px solid #2271b1; overflow-x: auto; overflow-wrap: break-word; white-space: pre-wrap; word-break: break-all; font-size: 12px; max-width: 100%;"><?php echo esc_html($schema['raw']); ?></pre>
              <?php endforeach; ?>
            </details>
          <?php else: ?>
            <p style="color: #646970;">No schema blocks detected</p>
          <?php endif; ?>
        </div>
        
      <?php endif; ?>
      
      <!-- Help Sidebar -->
      <aside class="cfseo-sidebar">
        <button type="button" class="cfseo-sidebar-toggle" id="cfseo-sidebar-toggle">
          <span class="dashicons dashicons-editor-help"></span>
          <span class="cfseo-sidebar-toggle-text">Help & Tips</span>
        </button>
        
        <div class="cfseo-sidebar-content" id="cfseo-sidebar-content">
          <div class="cfseo-help-card">
            <h3><span class="dashicons dashicons-info"></span> About Page Diagnostics</h3>
            <p>Analyze a single URL to see exactly what meta tags, canonicals, and schema are detected.</p>
          </div>
          
          <div class="cfseo-help-card">
            <h3><span class="dashicons dashicons-search"></span> How to Use</h3>
            <ul>
              <li>Select a page from the dropdown or enter a custom URL</li>
              <li>Click "Run Diagnostics" to fetch data</li>
              <li>Review results across all sections</li>
            </ul>
          </div>
          
          <div class="cfseo-help-card">
            <h3><span class="dashicons dashicons-lightbulb"></span> What You'll See</h3>
            <ul>
              <li><strong>Fetch Results:</strong> HTTP status, redirects, timing</li>
              <li><strong>Canonical:</strong> URL consistency and target status</li>
              <li><strong>Indexing Signals:</strong> Robots meta, X-Robots-Tag</li>
              <li><strong>Meta & Schema:</strong> Title, description, social tags, JSON-LD</li>
            </ul>
          </div>
          
          <div class="cfseo-help-card">
            <h3><span class="dashicons dashicons-warning"></span> Note</h3>
            <p>This tool shows <strong>facts only</strong> - no pass/fail judgments. Use these facts to understand what search engines see.</p>
          </div>
        </div>
      </aside>
      
    </div>
    
    <script>
    (function() {
      const toggle = document.getElementById('cfseo-sidebar-toggle');
      const content = document.getElementById('cfseo-sidebar-content');
      const storageKey = 'cfseo_sidebar_visible';
      
      // Restore sidebar state
      const isVisible = localStorage.getItem(storageKey) !== 'false';
      if (!isVisible) {
        content.style.display = 'none';
        toggle.classList.add('collapsed');
      }
      
      toggle.addEventListener('click', function() {
        const visible = content.style.display !== 'none';
        content.style.display = visible ? 'none' : 'block';
        toggle.classList.toggle('collapsed');
        localStorage.setItem(storageKey, !visible);
      });
    })();
    </script>
    <?php
  }
}
