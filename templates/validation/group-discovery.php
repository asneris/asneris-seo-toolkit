<?php
/**
 * Function Group: Discovery & Crawling
 * @var array $results Validation results
 * @var array $sitemap Sitemap analysis results
 * @var array $robots Robots.txt analysis results
 */
if (!defined('ABSPATH')) exit;

$discovery_pass = 0;
$discovery_total = 3;
if ($sitemap && $sitemap['status'] === 'exists') $discovery_pass++;
if ($robots && $robots['status'] === 'exists') $discovery_pass++;
if ($results['http_status'] === 200) $discovery_pass++;
?>
<div class="gscseo-function-group">
  <div class="gscseo-group-header" data-group="discovery">
    <div class="gscseo-group-title">
      <span class="dashicons dashicons-admin-site"></span>
      <h3>🗺️ <?php _e('Discovery & Crawling', 'bfseo'); ?></h3>
      <span class="gscseo-confidence-badge confidence-high"><?php _e('Confidence: High', 'bfseo'); ?></span>
    </div>
    <div class="gscseo-group-summary">
      <?php echo GSCSEO_Validation::get_status_badge($discovery_pass, $discovery_total); ?>
      <span><?php echo $discovery_pass; ?> / <?php echo $discovery_total; ?> <?php _e('passed', 'bfseo'); ?></span>
      <span class="gscseo-toggle">▼</span>
    </div>
  </div>
  <div class="gscseo-group-content" id="group-discovery" style="display: none;">
    <p class="gscseo-group-description">
      <?php _e('Helps search engines discover and crawl your content', 'bfseo'); ?>
    </p>
    
    <!-- XML Sitemap -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('XML Sitemap', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engines: Google, Bing, Yandex', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge($sitemap && $sitemap['status'] === 'exists' ? 1 : 0); ?>
      </div>
      <?php if ($sitemap && $sitemap['status'] === 'exists'): ?>
        <div class="gscseo-check-details">
          <p class="description">
            ✓ <?php printf(__('Sitemap found at %s', 'bfseo'), '<code>/wp-sitemap.xml</code>'); ?>
            <?php if (isset($sitemap['page_count']) && $sitemap['page_count'] > 0): ?>
              <br>📄 <?php printf(__('%d pages indexed', 'bfseo'), $sitemap['page_count']); ?>
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php _e('Sitemap not accessible at', 'bfseo'); ?> <code>/wp-sitemap.xml</code>
        </p>
      <?php endif; ?>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Lists all important URLs for search engines to crawl', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('Indirect (medium) - Improves discovery but not a ranking factor', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> <?php _e('HTTP GET /wp-sitemap.xml, check HTTP 200', 'bfseo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Robots.txt -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Robots.txt', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engines: Google, Bing, Yandex', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge($robots && $robots['status'] === 'exists' ? 1 : 0); ?>
      </div>
      <?php if ($robots && $robots['status'] === 'exists'): ?>
        <div class="gscseo-check-details">
          <p class="description">
            ✓ <?php printf(__('Robots.txt found at %s', 'bfseo'), '<code>/robots.txt</code>'); ?>
            <?php if (!empty($robots['rules'])): ?>
              <br><strong><?php _e('Rules:', 'bfseo'); ?></strong>
              <pre style="max-height: 200px; overflow-y: auto;"><?php echo esc_html(implode("\n", array_slice($robots['rules'], 0, 10))); ?></pre>
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php _e('Robots.txt not accessible at', 'bfseo'); ?> <code>/robots.txt</code>
        </p>
      <?php endif; ?>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Controls which URLs search engines should crawl', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('Direct (critical if blocking important URLs)', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> <?php _e('HTTP GET /robots.txt, check HTTP 200', 'bfseo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- HTTP Status -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('HTTP Status Code', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engines: All', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge($results['http_status'] === 200 ? 1 : 0); ?>
      </div>
      <div class="gscseo-check-details">
        <code><?php echo $results['http_status']; ?> <?php echo $results['http_status'] === 200 ? 'OK' : 'Error'; ?></code>
        <?php if ($results['http_status'] === 200): ?>
          <p class="description">✓ <?php _e('Page is accessible', 'bfseo'); ?></p>
        <?php else: ?>
          <p class="description" style="color: #d63638;">✗ <?php _e('Page returned error status', 'bfseo'); ?></p>
        <?php endif; ?>
      </div>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> <?php _e('All search engines', 'bfseo'); ?></p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Indicates page accessibility', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('Direct (critical) - Non-200 status prevents indexing', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> <?php _e('HTTP HEAD request', 'bfseo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
