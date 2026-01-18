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
  <div class="cfseo-card">
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
  <div class="cfseo-validation-group">
    <div class="cfseo-group-header">
      <h2><span class="dashicons dashicons-id-alt"></span> Core Identity</h2>
    </div>
    <div class="cfseo-group-content">
      
      <!-- Title Validation -->
      <div class="cfseo-validation-item">
        <?php
        $cfseo_title_count = count($results['title']);
        if ($cfseo_title_count === 1) {
          $cfseo_status = 'pass';
          $cfseo_icon = 'âœ…';
          $cfseo_message = 'Single title tag found';
          $cfseo_why = 'Search engines use the title tag to understand page content and display it in search results.';
        } elseif ($cfseo_title_count === 0) {
          $cfseo_status = 'warning';
          $cfseo_icon = 'âš ï¸';
          $cfseo_message = 'No title tag found';
          $cfseo_why = 'Without a title tag, search engines may generate one automatically from page content.';
        } else {
          $cfseo_status = 'conflict';
          $cfseo_icon = 'âŒ';
          $cfseo_message = 'Multiple title tags detected (' . $cfseo_title_count . ')';
          $cfseo_why = 'Multiple title tags can confuse search engines about which one to display.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($cfseo_status); ?>">
        <div class="validation-icon"><?php echo wp_kses_post($cfseo_icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($cfseo_message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($cfseo_why); ?></p>
            <?php if ($cfseo_status === 'conflict'): ?>
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
      <div class="cfseo-validation-item">
        <?php
        $cfseo_desc_count = count($results['description']);
        if ($cfseo_desc_count === 1) {
          $cfseo_status = 'pass';
          $cfseo_icon = 'âœ…';
          $cfseo_message = 'Single meta description found';
          $cfseo_why = 'Search engines may use this description in search results (but are not required to).';
        } elseif ($cfseo_desc_count === 0) {
          $cfseo_status = 'warning';
          $cfseo_icon = 'âš ï¸';
          $cfseo_message = 'No meta description found';
          $cfseo_why = 'Search engines will generate a snippet from page content instead.';
        } else {
          $cfseo_status = 'conflict';
          $cfseo_icon = 'âŒ';
          $cfseo_message = 'Multiple meta descriptions detected (' . $cfseo_desc_count . ')';
          $cfseo_why = 'Search engines may ignore all descriptions when multiple are present.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($cfseo_status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($cfseo_icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($cfseo_message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($cfseo_why); ?></p>
            <?php if (!empty($results['description'])): ?>
              <details style="margin-top: 10px;">
                <summary style="cursor: pointer; color: #2271b1;">Show detected values</summary>
                <ul style="margin: 5px 0; padding-left: 20px;">
                  <?php foreach ($results['description'] as $cfseo_desc): ?>
                    <li><code><?php echo esc_html($cfseo_desc); ?></code></li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Canonical Validation -->
      <div class="cfseo-validation-item">
        <?php
        $cfseo_canon_count = count($results['canonical']);
        if ($cfseo_canon_count === 1) {
          $cfseo_status = 'pass';
          $cfseo_icon = 'âœ…';
          $cfseo_message = 'Single canonical URL found';
          $cfseo_why = 'This tells search engines which URL is the preferred version of this page.';
        } elseif ($cfseo_canon_count === 0) {
          $cfseo_status = 'warning';
          $cfseo_icon = 'âš ï¸';
          $cfseo_message = 'No canonical URL found';
          $cfseo_why = 'Search engines will choose a canonical URL automatically.';
        } else {
          $cfseo_status = 'conflict';
          $cfseo_icon = 'âŒ';
          $cfseo_message = 'Multiple canonical URLs detected (' . $cfseo_canon_count . ')';
          $cfseo_why = 'Conflicting canonical tags create ambiguity about the preferred URL.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($cfseo_status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($cfseo_icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($cfseo_message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($cfseo_why); ?></p>
            <?php if (!empty($results['canonical'])): ?>
              <details style="margin-top: 10px;">
                <summary style="cursor: pointer; color: #2271b1;">Show detected values</summary>
                <ul style="margin: 5px 0; padding-left: 20px;">
                  <?php foreach ($results['canonical'] as $cfseo_canon): ?>
                    <li><code><?php echo esc_html($cfseo_canon); ?></code></li>
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
  <div class="cfseo-validation-group">
    <div class="cfseo-group-header">
      <h2><span class="dashicons dashicons-visibility"></span> Indexing Signals</h2>
    </div>
    <div class="cfseo-group-content">
      
      <!-- Robots Meta Validation -->
      <div class="cfseo-validation-item">
        <?php
        $robots_count = count($results['robots']);
        $has_noindex = false;
        foreach ($results['robots'] as $robots_content) {
          if (stripos($robots_content, 'noindex') !== false) {
            $has_noindex = true;
            break;
          }
        }
        
        if ($has_noindex) {
          $status = 'warning';
          $icon = 'âš ï¸';
          $message = 'Page is blocked from indexing (noindex detected)';
          $why = 'This page will not appear in search engine results.';
          $not_mean = 'This does NOT mean the page is broken or inaccessible to users.';
        } elseif ($robots_count === 0) {
          $status = 'pass';
          $icon = 'âœ…';
          $message = 'No indexing blocks detected';
          $why = 'Search engines can index this page if they choose to.';
        } elseif ($robots_count === 1) {
          $status = 'pass';
          $icon = 'âœ…';
          $message = 'Indexable (no noindex directive)';
          $why = 'Search engines are allowed to index this page.';
        } else {
          $status = 'conflict';
          $icon = 'âŒ';
          $message = 'Multiple robots meta tags detected (' . $robots_count . ')';
          $why = 'Conflicting robots directives create ambiguity about indexing intent.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($why); ?></p>
            <?php if (isset($not_mean)): ?>
              <p style="color: #646970; font-size: 13px;"><strong>What this does NOT mean:</strong> <?php echo esc_html($not_mean); ?></p>
            <?php endif; ?>
            <?php if (!empty($results['robots'])): ?>
              <details style="margin-top: 10px;">
                <summary style="cursor: pointer; color: #2271b1;">Show detected values</summary>
                <ul style="margin: 5px 0; padding-left: 20px;">
                  <?php foreach ($results['robots'] as $robots): ?>
                    <li><code><?php echo esc_html($robots); ?></code></li>
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
  <div class="cfseo-validation-group">
    <div class="cfseo-group-header">
      <h2><span class="dashicons dashicons-editor-code"></span> Schema Markup</h2>
    </div>
    <div class="cfseo-group-content">
      
      <div class="cfseo-validation-item">
        <?php
        $schema_count = count($results['schema']);
        if ($schema_count > 0) {
          $status = 'pass';
          $icon = 'âœ…';
          $message = $schema_count . ' schema block(s) detected';
          $why = 'Schema helps search engines understand structured data on your page.';
          $not_mean = 'Schema presence does not guarantee rich results in search.';
        } else {
          $status = 'pass';
          $icon = 'âœ…';
          $message = 'No schema markup detected';
          $why = 'Schema is optional and not required for indexing.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($why); ?></p>
            <?php if (isset($not_mean)): ?>
              <p style="color: #646970; font-size: 13px;"><strong>What this does NOT mean:</strong> <?php echo esc_html($not_mean); ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
    </div>
  </div>
  
  <!-- Site Diagnostic -->
  <div class="cfseo-validation-group">
    <div class="cfseo-group-header">
      <h2><span class="dashicons dashicons-admin-tools"></span> Site Diagnostic</h2>
    </div>
    <div class="cfseo-group-content">
      
      <!-- Sitemap Visibility -->
      <div class="cfseo-validation-item">
        <?php
        $sitemap_data = esc_html(CFSEO_Validation::check_sitemap_visibility();
        if ($sitemap_data['status'] === 'pass') {
          $status = 'pass';
          $icon = 'âœ…';
          $message = 'Sitemap is publicly accessible';
          $why = 'Search engines can discover and crawl URLs listed in your sitemap.';
        } elseif ($sitemap_data['status'] === 'warning') {
          $status = 'warning';
          $icon = 'âš ï¸';
          $message = 'Sitemap has issues';
          $why = 'Issues with sitemap accessibility may affect URL discovery.';
        } else {
          $status = 'conflict';
          $icon = 'âŒ';
          $message = 'Sitemap not accessible';
          $why = 'Search engines cannot access your sitemap to discover URLs.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($why); ?></p>
            <?php if (!empty($sitemap_data['message'])): ?>
              <p style="color: #646970; font-size: 13px;"><?php echo esc_html($sitemap_data['message']); ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Duplicate Output Detector -->
      <div class="cfseo-validation-item">
        <?php
        $duplicate_data = esc_html(CFSEO_Validation::detect_duplicate_outputs();
        if (empty($duplicate_data)) {
          $status = 'pass';
          $icon = 'âœ…';
          $message = 'No duplicate SEO outputs detected';
          $why = 'Each SEO output (title, meta, canonical, schema, Open Graph) is generated by a single source.';
        } else {
          $status = 'conflict';
          $icon = 'âŒ';
          $message = 'Multiple plugins generating SEO outputs';
          $why = 'Multiple plugins creating the same type of output can cause conflicts and unpredictable results.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($why); ?></p>
            <?php if (!empty($duplicate_data)): ?>
              <details style="margin-top: 10px;">
                <summary style="cursor: pointer; color: #2271b1;">Show detected conflicts</summary>
                <ul style="margin: 5px 0; padding-left: 20px;">
                  <?php foreach ($duplicate_data as $plugin => $outputs): ?>
                    <li><strong><?php echo esc_html($plugin); ?>:</strong> <?php echo esc_html(implode(', ', $outputs)); ?></li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Indexing Safety (Patterns) -->
      <div class="cfseo-validation-item">
        <?php
        // Check for common indexing safety patterns
        $indexing_safe = true;
        $indexing_warnings = [];
        
        // Check if site is set to discourage search engines
        if (get_option('blog_public') == '0') {
          $indexing_safe = false;
          $indexing_warnings[] = 'WordPress is set to discourage search engines (Settings â†’ Reading)';
        }
        
        // Check robots meta
        if ($results && isset($results['robots'])) {
          foreach ($results['robots'] as $robots) {
            if (stripos($robots, 'noindex') !== false) {
              $indexing_safe = false;
              $indexing_warnings[] = 'Page has noindex directive in robots meta tag';
              break;
            }
          }
        }
        
        if ($indexing_safe) {
          $status = 'pass';
          $icon = 'âœ…';
          $message = 'No indexing safety issues detected';
          $why = 'Common patterns that block indexing were not found.';
        } else {
          $status = 'warning';
          $icon = 'âš ï¸';
          $message = 'Potential indexing blocks detected';
          $why = 'Your site or this page may have settings that prevent search engine indexing.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($why); ?></p>
            <?php if (!empty($indexing_warnings)): ?>
              <details style="margin-top: 10px;">
                <summary style="cursor: pointer; color: #2271b1;">Show detected patterns</summary>
                <ul style="margin: 5px 0; padding-left: 20px;">
                  <?php foreach ($indexing_warnings as $warning): ?>
                    <li><?php echo esc_html($warning); ?></li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Canonical Consistency (Patterns) -->
      <div class="cfseo-validation-item">
        <?php
        $canonical_consistent = true;
        $canonical_warnings = [];
        
        if ($results && isset($results['canonical'])) {
          $canonical_count = count($results['canonical']);
          
          if ($canonical_count > 1) {
            // Multiple canonicals - check if they're the same
            $unique_canonicals = array_unique($results['canonical']);
            if (count($unique_canonicals) > 1) {
              $canonical_consistent = false;
              $canonical_warnings[] = 'Multiple different canonical URLs detected (' . count($unique_canonicals) . ' unique values)';
            }
          }
          
          // Check if canonical points to itself (best practice for non-paginated content)
          if ($canonical_count === 1 && !empty($_GET['url'])) {
            $canonical_url = $results['canonical'][0];
            $current_url = esc_url_raw($_GET['url']); // Sanitize first
            
            // Normalize both URLs for comparison
            $canonical_normalized = untrailingslashit(strtolower($canonical_url));
            $current_normalized = untrailingslashit(strtolower($current_url));
            
            if ($canonical_normalized !== $current_normalized) {
              $canonical_warnings[] = 'Canonical URL points to a different page (may be intentional for duplicate content)';
            }
          }
        }
        
        if ($canonical_consistent && empty($canonical_warnings)) {
          $status = 'pass';
          $icon = 'âœ…';
          $message = 'Canonical URL pattern is consistent';
          $why = 'Your canonical implementation follows expected patterns.';
        } elseif (!empty($canonical_warnings)) {
          $status = 'warning';
          $icon = 'âš ï¸';
          $message = 'Canonical URL patterns detected';
          $why = 'Review these patterns to ensure they match your intent.';
        } else {
          $status = 'conflict';
          $icon = 'âŒ';
          $message = 'Canonical URL conflicts detected';
          $why = 'Conflicting canonical URLs create ambiguity about the preferred page version.';
        }
        ?>
        <div class="validation-status validation-<?php echo esc_attr($status); ?>">
          <div class="validation-icon"><?php echo wp_kses_post($icon); ?></div>
          <div class="validation-content">
            <h4><?php echo esc_html($message); ?></h4>
            <p><strong>Why this matters:</strong> <?php echo esc_html($why); ?></p>
            <?php if (!empty($canonical_warnings)): ?>
              <details style="margin-top: 10px;">
                <summary style="cursor: pointer; color: #2271b1;">Show detected patterns</summary>
                <ul style="margin: 5px 0; padding-left: 20px;">
                  <?php foreach ($canonical_warnings as $warning): ?>
                    <li><?php echo esc_html($warning); ?></li>
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
    <p><strong><?php esc_html_e('Error:', 'clarity-first-seo'); ?></strong> <?php echo esc_html($results['error']); ?></p>
  </div>

<?php endif; ?>

<style>
.cfseo-validation-item {
  margin-bottom: 20px;
  border-bottom: 1px solid #dcdcde;
  padding-bottom: 20px;
}

.cfseo-validation-item:last-child {
  border-bottom: none;
}

.validation-status {
  display: flex;
  gap: 15px;
  align-items: flex-start;
}

.validation-icon {
  font-size: 32px;
  line-height: 1;
  flex-shrink: 0;
}

.validation-content h4 {
  margin: 0 0 8px 0;
  font-size: 16px;
}

.validation-content p {
  margin: 5px 0;
  line-height: 1.6;
}

.validation-pass .validation-content h4 {
  color: #1e1e1e;
}

.validation-warning .validation-content h4 {
  color: #8a6d3b;
}

.validation-conflict .validation-content h4 {
  color: #a94442;
}
</style>

