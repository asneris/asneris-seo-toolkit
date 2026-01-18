<?php
/**
 * Function Group: Discovery & Crawling
 * @var array $results Validation results
 * @var array $sitemap Sitemap analysis results
 * @var array $robots Robots.txt analysis results
 */
if (!defined('ABSPATH')) exit;

$cfseo_discovery_pass = 0;
$cfseo_discovery_total = 3;
if ($sitemap && $sitemap['status'] === 'exists') $cfseo_discovery_pass++;
if ($robots && $robots['status'] === 'exists') $cfseo_discovery_pass++;
if ($results['http_status'] === 200) $cfseo_discovery_pass++;
?>
<div class="cfseo-function-group">
  <div class="cfseo-group-header" data-group="discovery">
    <div class="cfseo-group-title">
      <span class="dashicons dashicons-admin-site"></span>
      <h3>🗺️ <?php esc_html_e('Discovery & Crawling', 'clarity-first-seo'); ?></h3>
      <span class="cfseo-confidence-badge confidence-high"><?php esc_html_e('Confidence: High', 'clarity-first-seo'); ?></span>
    </div>
    <div class="cfseo-group-summary">
      <?php echo esc_html(CFSEO_Validation::get_status_badge($cfseo_discovery_pass, $cfseo_discovery_total)); ?>
        <span><?php echo esc_html($cfseo_discovery_pass); ?> / <?php echo esc_html($cfseo_discovery_total); ?> <?php esc_html_e('passed', 'clarity-first-seo'); ?></span>
      <span class="cfseo-toggle">▼</span>
    </div>
  </div>
  <div class="cfseo-group-content" id="group-discovery" style="display: none;">
    <p class="cfseo-group-description">
      <?php esc_html_e('Helps search engines discover and crawl your content', 'clarity-first-seo'); ?>
    </p>
    
    <!-- XML Sitemap -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('XML Sitemap', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Engines: Google, Bing, Yandex', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge($sitemap && $sitemap['status'] === 'exists' ? 1 : 0); ?>
      </div>
      <?php if ($sitemap && $sitemap['status'] === 'exists'): ?>
        <div class="cfseo-check-details">
          <p class="description">
            ✓ <?php
            /* translators: %s: sitemap file path */
            printf(wp_kses_post(__('Sitemap found at %s', 'clarity-first-seo')), '<code>/wp-sitemap.xml</code>'); ?>
            <?php if (isset($sitemap['page_count']) && $sitemap['page_count'] > 0): ?>
              <br>📄 <?php
              /* translators: %d: number of pages indexed in sitemap */
              printf(esc_html__('%d pages indexed', 'clarity-first-seo'), esc_html($sitemap['page_count'])); ?>
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php esc_html_e('Sitemap not accessible at', 'clarity-first-seo'); ?> <code>/wp-sitemap.xml</code>
        </p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Lists all important URLs for search engines to crawl', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Indirect (medium) - Improves discovery but not a ranking factor', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('HTTP GET /wp-sitemap.xml, check HTTP 200', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Robots.txt -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('Robots.txt', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Engines: Google, Bing, Yandex', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge($robots && $robots['status'] === 'exists' ? 1 : 0); ?>
      </div>
      <?php if ($robots && $robots['status'] === 'exists'): ?>
        <div class="cfseo-check-details">
          <p class="description">
            ✓ <?php
            /* translators: %s: robots.txt file path */
            printf(wp_kses_post(__('Robots.txt found at %s', 'clarity-first-seo')), '<code>/robots.txt</code>'); ?>
            <?php if (!empty($robots['rules'])): ?>
              <br><strong><?php esc_html_e('Rules:', 'clarity-first-seo'); ?></strong>
              <pre style="max-height: 200px; overflow-y: auto;"><?php echo esc_html(implode("\n", array_slice($robots['rules'], 0, 10))); ?></pre>
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php esc_html_e('Robots.txt not accessible at', 'clarity-first-seo'); ?> <code>/robots.txt</code>
        </p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Controls which URLs search engines should crawl', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Direct (critical if blocking important URLs)', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('HTTP GET /robots.txt, check HTTP 200', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- HTTP Status -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('HTTP Status Code', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Engines: All', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge($results['http_status'] === 200 ? 1 : 0); ?>
      </div>
      <div class="cfseo-check-details">
        <code><?php echo esc_html($results['http_status']); ?> <?php echo $results['http_status'] === 200 ? 'OK' : 'Error'; ?></code>
        <?php if ($results['http_status'] === 200): ?>
          <p class="description">✓ <?php esc_html_e('Page is accessible', 'clarity-first-seo'); ?></p>
        <?php else: ?>
          <p class="description" style="color: #d63638;">✗ <?php esc_html_e('Page returned error status', 'clarity-first-seo'); ?></p>
        <?php endif; ?>
      </div>
      <details class="cfseo-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('All search engines', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Indicates page accessibility', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Direct (critical) - Non-200 status prevents indexing', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('HTTP HEAD request', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
