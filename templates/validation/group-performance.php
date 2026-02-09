<?php
/**
 * Function Group: Performance & Technical SEO
 * @var array $indexnow IndexNow configuration status
 */
if (!defined('ABSPATH')) exit;

$ASNERISSEO_perf_pass = 0;
$ASNERISSEO_perf_total = 0;
if ($indexnow && $indexnow['configured']) $perf_pass++;
?>
<div class="ASNERISSEO-function-group">
  <div class="ASNERISSEO-group-header" data-group="performance">
    <div class="ASNERISSEO-group-title">
      <span class="dashicons dashicons-performance"></span>
      <h3>⚡ <?php esc_html_e('Performance & Technical SEO', 'asneris-seo-toolkit'); ?></h3>
      <span class="ASNERISSEO-confidence-badge confidence-medium"><?php esc_html_e('Confidence: Medium', 'asneris-seo-toolkit'); ?></span>
    </div>
    <div class="ASNERISSEO-group-summary">
      <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($perf_pass, $perf_total); ?>
        <span><?php echo esc_html($perf_pass); ?> / <?php echo esc_html($perf_total); ?> <?php esc_html_e('passed', 'asneris-seo-toolkit'); ?></span>
      <span class="ASNERISSEO-toggle">▼</span>
    </div>
  </div>
  <div class="ASNERISSEO-group-content" id="group-performance" style="display: none;">
    <p class="ASNERISSEO-group-description">
      <?php esc_html_e('Technical optimizations for faster indexing', 'asneris-seo-toolkit'); ?>
    </p>
    
    <!-- IndexNow -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('IndexNow Configuration', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engines: Bing, Yandex', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($indexnow && $indexnow['configured'] ? 1 : 0); ?>
      </div>
      <?php if ($indexnow && $indexnow['configured']): ?>
        <div class="ASNERISSEO-check-details">
          <p class="description">
            ✓ <?php esc_html_e('IndexNow is configured', 'asneris-seo-toolkit'); ?>
            <?php if (!empty($indexnow['api_key'])): ?>
              <br><?php esc_html_e('API Key:', 'asneris-seo-toolkit'); ?> <code><?php echo esc_html(substr($indexnow['api_key'], 0, 20)); ?>...</code>
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">
          ⚠ <?php esc_html_e('IndexNow is not configured', 'asneris-seo-toolkit'); ?>
        </p>
      <?php endif; ?>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Bing, Yandex</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Instantly notify search engines when content is published or updated', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Indirect (medium) - Faster indexing but not a ranking factor', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Check plugin settings for API key', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('CONFIDENCE LEVEL:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Medium - Checks configuration only, not actual notification delivery', 'asneris-seo-toolkit'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Core Web Vitals Note -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Core Web Vitals', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engine: Google', 'asneris-seo-toolkit'); ?></span>
        <span class="ASNERISSEO-status-badge status-info">ℹ <?php esc_html_e('Info', 'asneris-seo-toolkit'); ?></span>
      </div>
      <div class="ASNERISSEO-check-details">
        <p class="description">
          ℹ <?php esc_html_e('Core Web Vitals require external testing tools (PageSpeed Insights, Lighthouse)', 'asneris-seo-toolkit'); ?>
          <br><a href="https://pagespeed.web.dev/" target="_blank"><?php esc_html_e('Test with PageSpeed Insights', 'asneris-seo-toolkit'); ?> ↗</a>
        </p>
      </div>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Google</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Measures page loading performance, interactivity, and visual stability', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Direct (medium) - Ranking factor for Google', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('METRICS:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('LCP (Largest Contentful Paint), FID (First Input Delay), CLS (Cumulative Layout Shift)', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Cannot be tested via HTML parsing - requires real browser measurement', 'asneris-seo-toolkit'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
