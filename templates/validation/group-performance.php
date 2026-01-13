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
      <h3>⚡ <?php _e('Performance & Technical SEO', 'clarity-first-seo'); ?></h3>
      <span class="cfseo-confidence-badge confidence-medium"><?php _e('Confidence: Medium', 'clarity-first-seo'); ?></span>
    </div>
    <div class="cfseo-group-summary">
      <?php echo CFSEO_Validation::get_status_badge($perf_pass, $perf_total); ?>
      <span><?php echo $perf_pass; ?> / <?php echo $perf_total; ?> <?php _e('passed', 'clarity-first-seo'); ?></span>
      <span class="cfseo-toggle">▼</span>
    </div>
  </div>
  <div class="cfseo-group-content" id="group-performance" style="display: none;">
    <p class="cfseo-group-description">
      <?php _e('Technical optimizations for faster indexing', 'clarity-first-seo'); ?>
    </p>
    
    <!-- IndexNow -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('IndexNow Configuration', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engines: Bing, Yandex', 'clarity-first-seo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge($indexnow && $indexnow['configured'] ? 1 : 0); ?>
      </div>
      <?php if ($indexnow && $indexnow['configured']): ?>
        <div class="cfseo-check-details">
          <p class="description">
            ✓ <?php _e('IndexNow is configured', 'clarity-first-seo'); ?>
            <?php if (!empty($indexnow['api_key'])): ?>
              <br><?php _e('API Key:', 'clarity-first-seo'); ?> <code><?php echo esc_html(substr($indexnow['api_key'], 0, 20)); ?>...</code>
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">
          ⚠ <?php _e('IndexNow is not configured', 'clarity-first-seo'); ?>
        </p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Bing, Yandex</p>
          <p><strong><?php _e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php _e('Instantly notify search engines when content is published or updated', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php _e('Indirect (medium) - Faster indexing but not a ranking factor', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'clarity-first-seo'); ?></strong> <?php _e('Check plugin settings for API key', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('CONFIDENCE LEVEL:', 'clarity-first-seo'); ?></strong> <?php _e('Medium - Checks configuration only, not actual notification delivery', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Core Web Vitals Note -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Core Web Vitals', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engine: Google', 'clarity-first-seo'); ?></span>
        <span class="cfseo-status-badge status-info">ℹ <?php _e('Info', 'clarity-first-seo'); ?></span>
      </div>
      <div class="cfseo-check-details">
        <p class="description">
          ℹ <?php _e('Core Web Vitals require external testing tools (PageSpeed Insights, Lighthouse)', 'clarity-first-seo'); ?>
          <br><a href="https://pagespeed.web.dev/" target="_blank"><?php _e('Test with PageSpeed Insights', 'clarity-first-seo'); ?> ↗</a>
        </p>
      </div>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google</p>
          <p><strong><?php _e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php _e('Measures page loading performance, interactivity, and visual stability', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php _e('Direct (medium) - Ranking factor for Google', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('METRICS:', 'clarity-first-seo'); ?></strong> <?php _e('LCP (Largest Contentful Paint), FID (First Input Delay), CLS (Cumulative Layout Shift)', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'clarity-first-seo'); ?></strong> <?php _e('Cannot be tested via HTML parsing - requires real browser measurement', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
