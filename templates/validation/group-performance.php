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
<div class="gscseo-function-group">
  <div class="gscseo-group-header" data-group="performance">
    <div class="gscseo-group-title">
      <span class="dashicons dashicons-performance"></span>
      <h3>⚡ <?php _e('Performance & Technical SEO', 'bfseo'); ?></h3>
      <span class="gscseo-confidence-badge confidence-medium"><?php _e('Confidence: Medium', 'bfseo'); ?></span>
    </div>
    <div class="gscseo-group-summary">
      <?php echo GSCSEO_Validation::get_status_badge($perf_pass, $perf_total); ?>
      <span><?php echo $perf_pass; ?> / <?php echo $perf_total; ?> <?php _e('passed', 'bfseo'); ?></span>
      <span class="gscseo-toggle">▼</span>
    </div>
  </div>
  <div class="gscseo-group-content" id="group-performance" style="display: none;">
    <p class="gscseo-group-description">
      <?php _e('Technical optimizations for faster indexing', 'bfseo'); ?>
    </p>
    
    <!-- IndexNow -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('IndexNow Configuration', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engines: Bing, Yandex', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge($indexnow && $indexnow['configured'] ? 1 : 0); ?>
      </div>
      <?php if ($indexnow && $indexnow['configured']): ?>
        <div class="gscseo-check-details">
          <p class="description">
            ✓ <?php _e('IndexNow is configured', 'bfseo'); ?>
            <?php if (!empty($indexnow['api_key'])): ?>
              <br><?php _e('API Key:', 'bfseo'); ?> <code><?php echo esc_html(substr($indexnow['api_key'], 0, 20)); ?>...</code>
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">
          ⚠ <?php _e('IndexNow is not configured', 'bfseo'); ?>
        </p>
      <?php endif; ?>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Bing, Yandex</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Instantly notify search engines when content is published or updated', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('Indirect (medium) - Faster indexing but not a ranking factor', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> <?php _e('Check plugin settings for API key', 'bfseo'); ?></p>
          <p><strong><?php _e('CONFIDENCE LEVEL:', 'bfseo'); ?></strong> <?php _e('Medium - Checks configuration only, not actual notification delivery', 'bfseo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Core Web Vitals Note -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Core Web Vitals', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engine: Google', 'bfseo'); ?></span>
        <span class="gscseo-status-badge status-info">ℹ <?php _e('Info', 'bfseo'); ?></span>
      </div>
      <div class="gscseo-check-details">
        <p class="description">
          ℹ <?php _e('Core Web Vitals require external testing tools (PageSpeed Insights, Lighthouse)', 'bfseo'); ?>
          <br><a href="https://pagespeed.web.dev/" target="_blank"><?php _e('Test with PageSpeed Insights', 'bfseo'); ?> ↗</a>
        </p>
      </div>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Google</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Measures page loading performance, interactivity, and visual stability', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('Direct (medium) - Ranking factor for Google', 'bfseo'); ?></p>
          <p><strong><?php _e('METRICS:', 'bfseo'); ?></strong> <?php _e('LCP (Largest Contentful Paint), FID (First Input Delay), CLS (Cumulative Layout Shift)', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> <?php _e('Cannot be tested via HTML parsing - requires real browser measurement', 'bfseo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
