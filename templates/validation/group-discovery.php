<?php
/**
 * Function Group: Discovery & Crawling
 * @var array $results Validation results
 * @var array $sitemap Sitemap analysis results
 * @var array $robots Robots.txt analysis results
 */
if (!defined('ABSPATH')) exit;

$ASNERISSEO_discovery_pass = 0;
$ASNERISSEO_discovery_total = 3;
if ($sitemap && $sitemap['status'] === 'exists') $ASNERISSEO_discovery_pass++;
if ($robots && $robots['status'] === 'exists') $ASNERISSEO_discovery_pass++;
if ($results['http_status'] === 200) $ASNERISSEO_discovery_pass++;
?>
<div class="ASNERISSEO-function-group">
  <div class="ASNERISSEO-group-header" data-group="discovery">
    <div class="ASNERISSEO-group-title">
      <span class="dashicons dashicons-admin-site"></span>
      <h3>🗺️ <?php esc_html_e('Discovery & Crawling', 'asneris-seo-toolkit'); ?></h3>
      <span class="ASNERISSEO-confidence-badge confidence-high"><?php esc_html_e('Confidence: High', 'asneris-seo-toolkit'); ?></span>
    </div>
    <div class="ASNERISSEO-group-summary">
      <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($ASNERISSEO_discovery_pass, $ASNERISSEO_discovery_total)); ?>
        <span><?php echo esc_html($ASNERISSEO_discovery_pass); ?> / <?php echo esc_html($ASNERISSEO_discovery_total); ?> <?php esc_html_e('passed', 'asneris-seo-toolkit'); ?></span>
      <span class="ASNERISSEO-toggle">▼</span>
    </div>
  </div>
  <div class="ASNERISSEO-group-content" id="group-discovery" style="display: none;">
    <p class="ASNERISSEO-group-description">
      <?php esc_html_e('Helps search engines discover and crawl your content', 'asneris-seo-toolkit'); ?>
    </p>
    
    <!-- XML Sitemap -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('XML Sitemap', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engines: Google, Bing, Yandex', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($sitemap && $sitemap['status'] === 'exists' ? 1 : 0); ?>
      </div>
      <?php if ($sitemap && $sitemap['status'] === 'exists'): ?>
        <div class="ASNERISSEO-check-details">
          <p class="description">
            ✓ <?php
            /* translators: %s: sitemap file path */
            printf(wp_kses_post(__('Sitemap found at %s', 'asneris-seo-toolkit')), '<code>/wp-sitemap.xml</code>'); ?>
            <?php if (isset($sitemap['page_count']) && $sitemap['page_count'] > 0): ?>
              <br>📄 <?php
              /* translators: %d: number of pages indexed in sitemap */
              printf(esc_html__('%d pages indexed', 'asneris-seo-toolkit'), esc_html($sitemap['page_count'])); ?>
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php esc_html_e('Sitemap not accessible at', 'asneris-seo-toolkit'); ?> <code>/wp-sitemap.xml</code>
        </p>
      <?php endif; ?>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Lists all important URLs for search engines to crawl', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Indirect (medium) - Improves discovery but not a ranking factor', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('HTTP GET /wp-sitemap.xml, check HTTP 200', 'asneris-seo-toolkit'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Robots.txt -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Robots.txt', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engines: Google, Bing, Yandex', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($robots && $robots['status'] === 'exists' ? 1 : 0); ?>
      </div>
      <?php if ($robots && $robots['status'] === 'exists'): ?>
        <div class="ASNERISSEO-check-details">
          <p class="description">
            ✓ <?php
            /* translators: %s: robots.txt file path */
            printf(wp_kses_post(__('Robots.txt found at %s', 'asneris-seo-toolkit')), '<code>/robots.txt</code>'); ?>
            <?php if (!empty($robots['rules'])): ?>
              <br><strong><?php esc_html_e('Rules:', 'asneris-seo-toolkit'); ?></strong>
              <pre style="max-height: 200px; overflow-y: auto;"><?php echo esc_html(implode("\n", array_slice($robots['rules'], 0, 10))); ?></pre>
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php esc_html_e('Robots.txt not accessible at', 'asneris-seo-toolkit'); ?> <code>/robots.txt</code>
        </p>
      <?php endif; ?>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Controls which URLs search engines should crawl', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Direct (critical if blocking important URLs)', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('HTTP GET /robots.txt, check HTTP 200', 'asneris-seo-toolkit'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- HTTP Status -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('HTTP Status Code', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engines: All', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($results['http_status'] === 200 ? 1 : 0); ?>
      </div>
      <div class="ASNERISSEO-check-details">
        <code><?php echo esc_html($results['http_status']); ?> <?php echo $results['http_status'] === 200 ? 'OK' : 'Error'; ?></code>
        <?php if ($results['http_status'] === 200): ?>
          <p class="description">✓ <?php esc_html_e('Page is accessible', 'asneris-seo-toolkit'); ?></p>
        <?php else: ?>
          <p class="description" style="color: #d63638;">✗ <?php esc_html_e('Page returned error status', 'asneris-seo-toolkit'); ?></p>
        <?php endif; ?>
      </div>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('All search engines', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Indicates page accessibility', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Direct (critical) - Non-200 status prevents indexing', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('HTTP HEAD request', 'asneris-seo-toolkit'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
