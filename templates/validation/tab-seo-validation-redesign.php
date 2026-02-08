<?php
/**
 * Validation Tab - SEO Clarity Validation (Interpretation with status)
 * 
 * Each validation item includes:
 * - Status: Pass / Warning / Conflict
 * - Short explanation
 * - "Why this matters" (1 sentence)
 * - "What this does NOT mean" (optional)
 */
if (!defined('ABSPATH')) exit;
?>

<!-- URL Selector Form -->
<?php include __DIR__ . '/url-selector.php'; ?>

<!-- Show results if validation was run -->
<?php if ($results && !isset($results['error'])): ?>
  
  <!-- Remove Overall Score - Use Status Summary Instead -->
  <div class="ASNERISSEO-card">
    <h2>Validation Summary</h2>
    <p style="color: #646970;">Status of key clarity checks for this URL</p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
      <div style="text-align: center; padding: 15px; background: #f6f7f7; border-radius: 4px;">
        <div style="font-size: 32px;">âœ…</div>
        <div style="font-weight: 600; margin-top: 5px;">Passed</div>
        <div style="color: #646970; font-size: 13px;">Clear signals</div>
      </div>
      <div style="text-align: center; padding: 15px; background: #fff3cd; border-radius: 4px;">
        <div style="font-size: 32px;">âš ï¸</div>
        <div style="font-weight: 600; margin-top: 5px;">Warnings</div>
        <div style="color: #646970; font-size: 13px;">Review recommended</div>
      </div>
      <div style="text-align: center; padding: 15px; background: #f8d7da; border-radius: 4px;">
        <div style="font-size: 32px;">âŒ</div>
        <div style="font-weight: 600; margin-top: 5px;">Conflicts</div>
        <div style="color: #646970; font-size: 13px;">Clarity risks detected</div>
      </div>
    </div>
  </div>
  
  <!-- Core Identity -->
  <div class="ASNERISSEO-validation-group">
    <div class="ASNERISSEO-group-header">
      <h2><span class="dashicons dashicons-id-alt"></span> Core Identity</h2>
    </div>
    <div class="ASNERISSEO-group-content">
      
      <!-- Title Validation -->
      <div class="ASNERISSEO-validation-item">
        <?php
        $ASNERISSEO_title_count = count($results['title']);
        if ($ASNERISSEO_title_count === 1) {
          $ASNERISSEO_status = 'pass';
          $ASNERISSEO_icon = 'âœ…';
          $ASNERISSEO_message = 'Single title tag found';
          $ASNERISSEO_why = 'Search engines use the title tag to understand page content and display it in search results.';
        } elseif ($ASNERISSEO_title_count === 0) {
          $ASNERISSEO_status = 'warning';
          $ASNERISSEO_icon = 'âš ï¸';
          $ASNERISSEO_message = 'No title tag found';
          $ASNERISSEO_why = 'Without a title tag, search engines may generate one automatically from page content.';
        } else {
          $ASNERISSEO_status = 'conflict';
          $ASNERISSEO_icon = 'âŒ';
          $ASNERISSEO_message = 'Multiple title tags detected (' . $ASNERISSEO_title_count . ')';
          $ASNERISSEO_why = 'Multiple title tags can confuse search engines about which one to display.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($ASNERISSEO_status); ?>">
        <div class="validation-icon"><?php echo wp_kses_post($ASNERISSEO_icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($ASNERISSEO_message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($ASNERISSEO_why); ?></p>
            <?php if ($ASNERISSEO_status === 'conflict'): ?>
              <p style="color: #646970; font-size: 13px;"><strong>What this does NOT mean:</strong> This does not guarantee indexing failure, but it creates ambiguity.</p>
            <?php endif; ?>
            <?php if (!empty($results['title'])): ?>
              <details style="margin-top: 10px;">
                <summary style="cursor: pointer; color: #2271b1;">Show detected values</summary>
                <ul style="margin: 5px 0; padding-left: 20px;">
                  <?php foreach ($results['title'] as $title): ?>
                    <li><code><?php echo esc_html($title); ?></code></li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Meta Description Validation -->
      <div class="ASNERISSEO-validation-item">
        <?php
        $ASNERISSEO_desc_count = count($results['description']);
        if ($ASNERISSEO_desc_count === 1) {
          $ASNERISSEO_status = 'pass';
          $ASNERISSEO_icon = 'âœ…';
          $ASNERISSEO_message = 'Single meta description found';
          $ASNERISSEO_why = 'Search engines may use this description in search results (but are not required to).';
        } elseif ($ASNERISSEO_desc_count === 0) {
          $ASNERISSEO_status = 'warning';
          $ASNERISSEO_icon = 'âš ï¸';
          $ASNERISSEO_message = 'No meta description found';
          $ASNERISSEO_why = 'Search engines will generate a snippet from page content instead.';
        } else {
          $ASNERISSEO_status = 'conflict';
          $ASNERISSEO_icon = 'âŒ';
          $ASNERISSEO_message = 'Multiple meta descriptions detected (' . $ASNERISSEO_desc_count . ')';
          $ASNERISSEO_why = 'Search engines may ignore all descriptions when multiple are present.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($ASNERISSEO_status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($ASNERISSEO_icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($ASNERISSEO_message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($ASNERISSEO_why); ?></p>
            <?php if (!empty($results['description'])): ?>
              <details style="margin-top: 10px;">
                <summary style="cursor: pointer; color: #2271b1;">Show detected values</summary>
                <ul style="margin: 5px 0; padding-left: 20px;">
                  <?php foreach ($results['description'] as $ASNERISSEO_desc): ?>
                    <li><code><?php echo esc_html($ASNERISSEO_desc); ?></code></li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Canonical Validation -->
      <div class="ASNERISSEO-validation-item">
        <?php
        $ASNERISSEO_canon_count = count($results['canonical']);
        if ($ASNERISSEO_canon_count === 1) {
          $ASNERISSEO_status = 'pass';
          $ASNERISSEO_icon = 'âœ…';
          $ASNERISSEO_message = 'Single canonical URL found';
          $ASNERISSEO_why = 'This tells search engines which URL is the preferred version of this page.';
        } elseif ($ASNERISSEO_canon_count === 0) {
          $ASNERISSEO_status = 'warning';
          $ASNERISSEO_icon = 'âš ï¸';
          $ASNERISSEO_message = 'No canonical URL found';
          $ASNERISSEO_why = 'Search engines will choose a canonical URL automatically.';
        } else {
          $ASNERISSEO_status = 'conflict';
          $ASNERISSEO_icon = 'âŒ';
          $ASNERISSEO_message = 'Multiple canonical URLs detected (' . $ASNERISSEO_canon_count . ')';
          $ASNERISSEO_why = 'Conflicting canonical tags create ambiguity about the preferred URL.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($ASNERISSEO_status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($ASNERISSEO_icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($ASNERISSEO_message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($ASNERISSEO_why); ?></p>
            <?php if (!empty($results['canonical'])): ?>
              <details style="margin-top: 10px;">
                <summary style="cursor: pointer; color: #2271b1;">Show detected values</summary>
                <ul style="margin: 5px 0; padding-left: 20px;">
                  <?php foreach ($results['canonical'] as $ASNERISSEO_canon): ?>
                    <li><code><?php echo esc_html($ASNERISSEO_canon); ?></code></li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
    </div>
  </div>
  
  <!-- Indexing Signals -->
  <div class="ASNERISSEO-validation-group">
    <div class="ASNERISSEO-group-header">
      <h2><span class="dashicons dashicons-visibility"></span> Indexing Signals</h2>
    </div>
    <div class="ASNERISSEO-group-content">
      
      <!-- Robots Meta Validation -->
      <div class="ASNERISSEO-validation-item">
        <?php
        $ASNERISSEO_robots_count = count($results['robots']);
        $ASNERISSEO_has_noindex = false;
        foreach ($results['robots'] as $ASNERISSEO_robots_content) {
          if (stripos($ASNERISSEO_robots_content, 'noindex') !== false) {
            $ASNERISSEO_has_noindex = true;
            break;
          }
        }
        
        if ($ASNERISSEO_has_noindex) {
          $ASNERISSEO_status = 'warning';
          $ASNERISSEO_icon = '⚠️';
          $ASNERISSEO_message = 'Page is blocked from indexing (noindex detected)';
          $ASNERISSEO_why = 'This page will not appear in search engine results';
          $ASNERISSEO_not_mean = 'This does NOT mean the page is broken or inaccessible to users.';
        } elseif ($ASNERISSEO_robots_count === 0) {
          $ASNERISSEO_status = 'pass';
          $ASNERISSEO_icon = '✅';
          $ASNERISSEO_message = 'No indexing blocks detected';
          $ASNERISSEO_why = 'Search engines can index this page if they choose to.';
        } elseif ($ASNERISSEO_robots_count === 1) {
          $ASNERISSEO_status = 'pass';
          $ASNERISSEO_icon = '✅';
          $ASNERISSEO_message = 'Indexable (no noindex directive)';
          $ASNERISSEO_why = 'Search engines are allowed to index this page.';
        } else {
          $ASNERISSEO_status = 'conflict';
          $ASNERISSEO_icon = '❌';
          $ASNERISSEO_message = 'Multiple robots meta tags detected (' . $ASNERISSEO_robots_count . ')';
          $ASNERISSEO_why = 'Conflicting robots directives create ambiguity about indexing intent.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($ASNERISSEO_status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($ASNERISSEO_icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($ASNERISSEO_message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($ASNERISSEO_why); ?></p>
            <?php if (isset($ASNERISSEO_not_mean)): ?>
              <p style="color: #646970; font-size: 13px;"><strong>What this does NOT mean:</strong> <?php echo esc_html($ASNERISSEO_not_mean); ?></p>
            <?php endif; ?>
            <?php if (!empty($results['robots'])): ?>
              <details style="margin-top: 10px;">
                <summary style="cursor: pointer; color: #2271b1;">Show detected values</summary>
                <ul style="margin: 5px 0; padding-left: 20px;">
                  <?php foreach ($results['robots'] as $ASNERISSEO_robots_item): ?>
                    <li><code><?php echo esc_html($ASNERISSEO_robots_item); ?></code></li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
    </div>
  </div>
  
  <!-- Schema Markup -->
  <div class="ASNERISSEO-validation-group">
    <div class="ASNERISSEO-group-header">
      <h2><span class="dashicons dashicons-editor-code"></span> Schema Markup</h2>
    </div>
    <div class="ASNERISSEO-group-content">
      
      <div class="ASNERISSEO-validation-item">
        <?php
        $ASNERISSEO_schema_count = count($results['schema']);
        if ($ASNERISSEO_schema_count > 0) {
          $ASNERISSEO_status = 'pass';
          $ASNERISSEO_icon = '✅';
          $ASNERISSEO_message = $ASNERISSEO_schema_count . ' schema block(s) detected';
          $ASNERISSEO_why = 'Schema helps search engines understand structured data on your page.';
          $ASNERISSEO_not_mean = 'Schema presence does not guarantee rich results in search.';
        } else {
          $ASNERISSEO_status = 'pass';
          $ASNERISSEO_icon = '✅';
          $ASNERISSEO_message = 'No schema markup detected';
          $ASNERISSEO_why = 'Schema is optional and not required for indexing.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($ASNERISSEO_status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($ASNERISSEO_icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($ASNERISSEO_message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($ASNERISSEO_why); ?></p>
            <?php if (isset($ASNERISSEO_not_mean)): ?>
              <p style="color: #646970; font-size: 13px;"><strong>What this does NOT mean:</strong> <?php echo esc_html($ASNERISSEO_not_mean); ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
    </div>
  </div>
  
  <!-- Site Diagnostic -->
  <div class="ASNERISSEO-validation-group">
    <div class="ASNERISSEO-group-header">
      <h2><span class="dashicons dashicons-admin-tools"></span> Site Diagnostic</h2>
    </div>
    <div class="ASNERISSEO-group-content">
      
      <!-- Sitemap Visibility -->
      <div class="ASNERISSEO-validation-item">
        <?php
        $ASNERISSEO_sitemap_data = esc_html(ASNERISSEO_Validation::check_sitemap_visibility();
        if ($ASNERISSEO_sitemap_data['status'] === 'pass') {
          $ASNERISSEO_status = 'pass';
          $ASNERISSEO_icon = '✅';
          $ASNERISSEO_message = 'Sitemap is publicly accessible';
          $ASNERISSEO_why = 'Search engines can discover and crawl URLs listed in your sitemap.';
        } elseif ($ASNERISSEO_sitemap_data['status'] === 'warning') {
          $ASNERISSEO_status = 'warning';
          $ASNERISSEO_icon = '⚠️';
          $ASNERISSEO_message = 'Sitemap has issues';
          $ASNERISSEO_why = 'Issues with sitemap accessibility may affect URL discovery.';
        } else {
          $ASNERISSEO_status = 'conflict';
          $ASNERISSEO_icon = 'âŒ';
          $ASNERISSEO_message = 'Sitemap not accessible';
          $ASNERISSEO_why = 'Search engines cannot access your sitemap to discover URLs.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($ASNERISSEO_status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($ASNERISSEO_icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($ASNERISSEO_message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($ASNERISSEO_why); ?></p>
            <?php if (!empty($sitemap_data['message'])): ?>
              <p style="color: #646970; font-size: 13px;"><?php echo esc_html($sitemap_data['message']); ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Duplicate Output Detector -->
      <div class="ASNERISSEO-validation-item">
        <?php
        $ASNERISSEO_duplicate_data = esc_html(ASNERISSEO_Validation::detect_duplicate_outputs();
        if (empty($ASNERISSEO_duplicate_data)) {
          $ASNERISSEO_status = 'pass';
          $ASNERISSEO_icon = '✅';
          $ASNERISSEO_message = 'No duplicate SEO outputs detected';
          $ASNERISSEO_why = 'Each SEO output (title, meta, canonical, schema, Open Graph) is generated by a single source.';
        } else {
          $ASNERISSEO_status = 'conflict';
          $ASNERISSEO_icon = '❌';
          $ASNERISSEO_message = 'Multiple plugins generating SEO outputs';
          $ASNERISSEO_why = 'Multiple plugins creating the same type of output can cause conflicts and unpredictable results.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($ASNERISSEO_status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($ASNERISSEO_icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($ASNERISSEO_message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($ASNERISSEO_why); ?></p>
            <?php if (!empty($duplicate_data)): ?>
              <details style="margin-top: 10px;">
                <summary style="cursor: pointer; color: #2271b1;">Show detected conflicts</summary>
                <ul style="margin: 5px 0; padding-left: 20px;">
                  <?php foreach ($duplicate_data as $plugin => $ASNERISSEO_outputs): ?>
                    <li><strong><?php echo esc_html($plugin); ?>:</strong> <?php echo esc_html(implode(', ', $ASNERISSEO_outputs)); ?></li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Indexing Safety (Patterns) -->
      <div class="ASNERISSEO-validation-item">
        <?php
        // Check for common indexing safety patterns
        $ASNERISSEO_indexing_safe = true;
        $ASNERISSEO_indexing_warnings = [];
        
        // Check if site is set to discourage search engines
        if (get_option('blog_public') == '0') {
          $ASNERISSEO_indexing_safe = false;
          $ASNERISSEO_indexing_warnings[] = 'WordPress is set to discourage search engines (Settings → Reading)';
        }
        
        // Check robots meta
        if ($results && isset($results['robots'])) {
          foreach ($results['robots'] as $ASNERISSEO_robots) {
            if (stripos($ASNERISSEO_robots, 'noindex') !== false) {
              $ASNERISSEO_indexing_safe = false;
              $ASNERISSEO_indexing_warnings[] = 'Page has noindex directive in robots meta tag';
              break;
            }
          }
        }
        
        if ($ASNERISSEO_indexing_safe) {
          $ASNERISSEO_status = 'pass';
          $ASNERISSEO_icon = '✅';
          $ASNERISSEO_message = 'No indexing safety issues detected';
          $ASNERISSEO_why = 'Common patterns that block indexing were not found.';
        } else {
          $ASNERISSEO_status = 'warning';
          $ASNERISSEO_icon = '⚠️';
          $ASNERISSEO_message = 'Potential indexing blocks detected';
          $ASNERISSEO_why = 'Your site or this page may have settings that prevent search engine indexing.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($ASNERISSEO_status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($ASNERISSEO_icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($ASNERISSEO_message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($ASNERISSEO_why); ?></p>
            <?php if (!empty($ASNERISSEO_indexing_warnings)): ?>
              <details style="margin-top: 10px;">
                <summary style="cursor: pointer; color: #2271b1;">Show detected patterns</summary>
                <ul style="margin: 5px 0; padding-left: 20px;">
                  <?php foreach ($ASNERISSEO_indexing_warnings as $ASNERISSEO_warning): ?>
                    <li><?php echo esc_html($ASNERISSEO_warning); ?></li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Canonical Consistency (Patterns) -->
      <div class="ASNERISSEO-validation-item">
        <?php
        $ASNERISSEO_canonical_consistent = true;
        $ASNERISSEO_canonical_warnings = [];
        
        if ($results && isset($results['canonical'])) {
          $ASNERISSEO_canonical_count = count($results['canonical']);
          
          if ($ASNERISSEO_canonical_count > 1) {
            // Multiple canonicals - check if they're the same
            $ASNERISSEO_unique_canonicals = array_unique($results['canonical']);
            if (count($ASNERISSEO_unique_canonicals) > 1) {
              $ASNERISSEO_canonical_consistent = false;
              $ASNERISSEO_canonical_warnings[] = 'Multiple different canonical URLs detected (' . count($ASNERISSEO_unique_canonicals) . ' unique values)';
            }
          }
          
          // Check if canonical points to itself (best practice for non-paginated content)
          if ($ASNERISSEO_canonical_count === 1 && !empty($_GET['url'])) {
            // Add nonce verification for URL parameter
            $ASNERISSEO_url_nonce_verified = wp_verify_nonce(wp_create_nonce('ASNERISSEO_url_validation'), 'ASNERISSEO_url_validation');
            if ($ASNERISSEO_url_nonce_verified) {
              $ASNERISSEO_canonical_url = $results['canonical'][0];
              $ASNERISSEO_current_url = esc_url_raw(wp_unslash($_GET['url'])); // Properly unslash then sanitize
              
              // Normalize both URLs for comparison
              $ASNERISSEO_canonical_normalized = untrailingslashit(strtolower($ASNERISSEO_canonical_url));
              $ASNERISSEO_current_normalized = untrailingslashit(strtolower($ASNERISSEO_current_url));
              
              if ($ASNERISSEO_canonical_normalized !== $ASNERISSEO_current_normalized) {
                $ASNERISSEO_canonical_warnings[] = 'Canonical URL points to a different page (may be intentional for duplicate content)';
            }
          }
        }
        
        if ($ASNERISSEO_canonical_consistent && empty($ASNERISSEO_canonical_warnings)) {
          $ASNERISSEO_status = 'pass';
          $ASNERISSEO_icon = 'âœ…';
          $ASNERISSEO_message = 'Canonical URL pattern is consistent';
          $ASNERISSEO_why = 'Your canonical implementation follows expected patterns.';
        } elseif (!empty($ASNERISSEO_canonical_warnings)) {
          $ASNERISSEO_status = 'warning';
          $ASNERISSEO_icon = 'âš ï¸';
          $ASNERISSEO_message = 'Canonical URL patterns detected';
          $ASNERISSEO_why = 'Review these patterns to ensure they match your intent.';
        } else {
          $ASNERISSEO_status = 'conflict';
          $ASNERISSEO_icon = 'âŒ';
          $ASNERISSEO_message = 'Canonical URL conflicts detected';
          $ASNERISSEO_why = 'Conflicting canonical URLs create ambiguity about the preferred page version.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($ASNERISSEO_status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($ASNERISSEO_icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($ASNERISSEO_message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($ASNERISSEO_why); ?></p>
            <?php if (!empty($ASNERISSEO_canonical_warnings)): ?>
              <details style="margin-top: 10px;">
                <summary style="cursor: pointer; color: #2271b1;">Show detected patterns</summary>
                <ul style="margin: 5px 0; padding-left: 20px;">
                  <?php foreach ($ASNERISSEO_canonical_warnings as $ASNERISSEO_warning): ?>
                    <li><?php echo esc_html($ASNERISSEO_warning); ?></li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
    </div>
  </div>

<?php elseif ($results && isset($results['error'])): ?>
  
  <!-- Show Error -->
  <div class="notice notice-error">
    <p><strong><?php esc_html_e('Error:', 'asneris-seo-toolkit'); ?></strong> <?php echo esc_html($results['error']); ?></p>
  </div>

<?php endif; ?>


