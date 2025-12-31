<?php
/**
 * Function Group: Search Console Integration
 * @var array $results Validation results
 */
if (!defined('ABSPATH')) exit;

$console_pass = 0;
$console_total = 3;
if (count($results['google_verification']) >= 1) $console_pass++;
if (count($results['msvalidate']) >= 1) $console_pass++;
if (count($results['yandex_verification']) >= 1) $console_pass++;
?>
<div class="gscseo-function-group">
  <div class="gscseo-group-header" data-group="console">
    <div class="gscseo-group-title">
      <span class="dashicons dashicons-admin-generic"></span>
      <h3>🔍 <?php _e('Search Console Integration', 'bfseo'); ?></h3>
      <span class="gscseo-confidence-badge confidence-high"><?php _e('Confidence: High', 'bfseo'); ?></span>
    </div>
    <div class="gscseo-group-summary">
      <?php echo GSCSEO_Validation::get_status_badge($console_pass, $console_total); ?>
      <span><?php echo $console_pass; ?> / <?php echo $console_total; ?> <?php _e('passed', 'bfseo'); ?></span>
      <span class="gscseo-toggle">▼</span>
    </div>
  </div>
  <div class="gscseo-group-content" id="group-console" style="display: none;">
    <p class="gscseo-group-description">
      <?php _e('Verification tags for search engine webmaster tools', 'bfseo'); ?>
    </p>
    
    <!-- Google Search Console -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Google Search Console', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engine: Google', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge(count($results['google_verification'])); ?>
      </div>
      <?php if (count($results['google_verification']) >= 1): ?>
        <div class="gscseo-check-details">
          <code><?php echo esc_html(substr($results['google_verification'][0], 0, 40)); ?>...</code>
          <p class="description">✓ <?php _e('Google verification tag found', 'bfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">
          ⚠ <?php _e('No Google Search Console verification tag', 'bfseo'); ?>
        </p>
      <?php endif; ?>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Google</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Verifies site ownership in Google Search Console', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('No direct ranking impact - Required for Search Console access', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> XPath <code>//meta[@name="google-site-verification"]</code></p>
        </div>
      </details>
    </div>
    
    <!-- Bing Webmaster Tools -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Bing Webmaster Tools', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engine: Bing', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge(count($results['msvalidate'])); ?>
      </div>
      <?php if (count($results['msvalidate']) >= 1): ?>
        <div class="gscseo-check-details">
          <code><?php echo esc_html(substr($results['msvalidate'][0], 0, 40)); ?>...</code>
          <p class="description">✓ <?php _e('Bing verification tag found', 'bfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">
          ⚠ <?php _e('No Bing Webmaster Tools verification tag', 'bfseo'); ?>
        </p>
      <?php endif; ?>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Bing</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Verifies site ownership in Bing Webmaster Tools', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('No direct ranking impact - Required for Bing Webmaster access', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> XPath <code>//meta[@name="msvalidate.01"]</code></p>
        </div>
      </details>
    </div>
    
    <!-- Yandex Webmaster -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Yandex Webmaster', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engine: Yandex', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge(count($results['yandex_verification'])); ?>
      </div>
      <?php if (count($results['yandex_verification']) >= 1): ?>
        <div class="gscseo-check-details">
          <code><?php echo esc_html(substr($results['yandex_verification'][0], 0, 40)); ?>...</code>
          <p class="description">✓ <?php _e('Yandex verification tag found', 'bfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">
          ⚠ <?php _e('No Yandex Webmaster verification tag', 'bfseo'); ?>
        </p>
      <?php endif; ?>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Yandex</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Verifies site ownership in Yandex Webmaster', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('No direct ranking impact - Required for Yandex Webmaster access', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> XPath <code>//meta[@name="yandex-verification"]</code></p>
        </div>
      </details>
    </div>
  </div>
</div>
