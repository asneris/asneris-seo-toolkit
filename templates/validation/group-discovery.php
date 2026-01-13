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
<div class="cfseo-function-group">
  <div class="cfseo-group-header" data-group="discovery">
    <div class="cfseo-group-title">
      <span class="dashicons dashicons-admin-site"></span>
      <h3>🗺️ <?php _e('Discovery & Crawling', 'cfseo'); ?></h3>
      <span class="cfseo-confidence-badge confidence-high"><?php _e('Confidence: High', 'cfseo'); ?></span>
    </div>
    <div class="cfseo-group-summary">
      <?php echo CFSEO_Validation::get_status_badge($discovery_pass, $discovery_total); ?>
      <span><?php echo $discovery_pass; ?> / <?php echo $discovery_total; ?> <?php _e('passed', 'cfseo'); ?></span>
      <span class="cfseo-toggle">▼</span>
    </div>
  </div>
  <div class="cfseo-group-content" id="group-discovery" style="display: none;">
    <p class="cfseo-group-description">
      <?php _e('Helps search engines discover and crawl your content', 'cfseo'); ?>
    </p>
    
    <!-- XML Sitemap -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('XML Sitemap', 'cfseo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engines: Google, Bing, Yandex', 'cfseo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge($sitemap && $sitemap['status'] === 'exists' ? 1 : 0); ?>
      </div>
      <?php if ($sitemap && $sitemap['status'] === 'exists'): ?>
        <div class="cfseo-check-details">
          <p class="description">
            ✓ <?php printf(__('Sitemap found at %s', 'cfseo'), '<code>/wp-sitemap.xml</code>'); ?>
            <?php if (isset($sitemap['page_count']) && $sitemap['page_count'] > 0): ?>
              <br>📄 <?php printf(__('%d pages indexed', 'cfseo'), $sitemap['page_count']); ?>
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php _e('Sitemap not accessible at', 'cfseo'); ?> <code>/wp-sitemap.xml</code>
        </p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'cfseo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'cfseo'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php _e('PURPOSE:', 'cfseo'); ?></strong> <?php _e('Lists all important URLs for search engines to crawl', 'cfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'cfseo'); ?></strong> <?php _e('Indirect (medium) - Improves discovery but not a ranking factor', 'cfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'cfseo'); ?></strong> <?php _e('HTTP GET /wp-sitemap.xml, check HTTP 200', 'cfseo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Robots.txt -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Robots.txt', 'cfseo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engines: Google, Bing, Yandex', 'cfseo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge($robots && $robots['status'] === 'exists' ? 1 : 0); ?>
      </div>
      <?php if ($robots && $robots['status'] === 'exists'): ?>
        <div class="cfseo-check-details">
          <p class="description">
            ✓ <?php printf(__('Robots.txt found at %s', 'cfseo'), '<code>/robots.txt</code>'); ?>
            <?php if (!empty($robots['rules'])): ?>
              <br><strong><?php _e('Rules:', 'cfseo'); ?></strong>
              <pre style="max-height: 200px; overflow-y: auto;"><?php echo esc_html(implode("\n", array_slice($robots['rules'], 0, 10))); ?></pre>
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php _e('Robots.txt not accessible at', 'cfseo'); ?> <code>/robots.txt</code>
        </p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'cfseo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'cfseo'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php _e('PURPOSE:', 'cfseo'); ?></strong> <?php _e('Controls which URLs search engines should crawl', 'cfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'cfseo'); ?></strong> <?php _e('Direct (critical if blocking important URLs)', 'cfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'cfseo'); ?></strong> <?php _e('HTTP GET /robots.txt, check HTTP 200', 'cfseo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- HTTP Status -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('HTTP Status Code', 'cfseo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engines: All', 'cfseo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge($results['http_status'] === 200 ? 1 : 0); ?>
      </div>
      <div class="cfseo-check-details">
        <code><?php echo $results['http_status']; ?> <?php echo $results['http_status'] === 200 ? 'OK' : 'Error'; ?></code>
        <?php if ($results['http_status'] === 200): ?>
          <p class="description">✓ <?php _e('Page is accessible', 'cfseo'); ?></p>
        <?php else: ?>
          <p class="description" style="color: #d63638;">✗ <?php _e('Page returned error status', 'cfseo'); ?></p>
        <?php endif; ?>
      </div>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'cfseo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'cfseo'); ?></strong> <?php _e('All search engines', 'cfseo'); ?></p>
          <p><strong><?php _e('PURPOSE:', 'cfseo'); ?></strong> <?php _e('Indicates page accessibility', 'cfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'cfseo'); ?></strong> <?php _e('Direct (critical) - Non-200 status prevents indexing', 'cfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'cfseo'); ?></strong> <?php _e('HTTP HEAD request', 'cfseo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
