<?php
/**
 * Function Group: Performance & Technical SEO
 * @var array $indexnow IndexNow configuration status
 */
if (!defined('ABSPATH')) exit;

$perf_pass = 0;
$perf_total = 1;
if ($indexnow && $indexnow['configured']) $perf_pass++;
?>
<div class="cfseo-function-group">
  <div class="cfseo-group-header" data-group="performance">
    <div class="cfseo-group-title">
      <span class="dashicons dashicons-performance"></span>
      <h3>⚡ <?php esc_html_e('Performance & Technical SEO', 'clarity-first-seo'); ?></h3>
      <span class="cfseo-confidence-badge confidence-medium"><?php esc_html_e('Confidence: Medium', 'clarity-first-seo'); ?></span>
    </div>
    <div class="cfseo-group-summary">
      <?php echo esc_html(CFSEO_Validation::get_status_badge($perf_pass, $perf_total); ?>
        <span><?php echo esc_html($perf_pass); ?> / <?php echo esc_html($perf_total); ?> <?php esc_html_e('passed', 'clarity-first-seo'); ?></span>
      <span class="cfseo-toggle">▼</span>
    </div>
  </div>
  <div class="cfseo-group-content" id="group-performance" style="display: none;">
    <p class="cfseo-group-description">
      <?php esc_html_e('Technical optimizations for faster indexing', 'clarity-first-seo'); ?>
    </p>
    
    <!-- IndexNow -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('IndexNow Configuration', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Engines: Bing, Yandex', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge($indexnow && $indexnow['configured'] ? 1 : 0); ?>
      </div>
      <?php if ($indexnow && $indexnow['configured']): ?>
        <div class="cfseo-check-details">
          <p class="description">
            ✓ <?php esc_html_e('IndexNow is configured', 'clarity-first-seo'); ?>
            <?php if (!empty($indexnow['api_key'])): ?>
              <br><?php esc_html_e('API Key:', 'clarity-first-seo'); ?> <code><?php echo esc_html(substr($indexnow['api_key'], 0, 20)); ?>...</code>
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">
          ⚠ <?php esc_html_e('IndexNow is not configured', 'clarity-first-seo'); ?>
        </p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Bing, Yandex</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Instantly notify search engines when content is published or updated', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Indirect (medium) - Faster indexing but not a ranking factor', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Check plugin settings for API key', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('CONFIDENCE LEVEL:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Medium - Checks configuration only, not actual notification delivery', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Core Web Vitals Note -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('Core Web Vitals', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Engine: Google', 'clarity-first-seo'); ?></span>
        <span class="cfseo-status-badge status-info">ℹ <?php esc_html_e('Info', 'clarity-first-seo'); ?></span>
      </div>
      <div class="cfseo-check-details">
        <p class="description">
          ℹ <?php esc_html_e('Core Web Vitals require external testing tools (PageSpeed Insights, Lighthouse)', 'clarity-first-seo'); ?>
          <br><a href="https://pagespeed.web.dev/" target="_blank"><?php esc_html_e('Test with PageSpeed Insights', 'clarity-first-seo'); ?> ↗</a>
        </p>
      </div>
      <details class="cfseo-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Measures page loading performance, interactivity, and visual stability', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Direct (medium) - Ranking factor for Google', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('METRICS:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('LCP (Largest Contentful Paint), FID (First Input Delay), CLS (Cumulative Layout Shift)', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Cannot be tested via HTML parsing - requires real browser measurement', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
