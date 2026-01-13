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
<div class="cfseo-function-group">
  <div class="cfseo-group-header" data-group="console">
    <div class="cfseo-group-title">
      <span class="dashicons dashicons-admin-generic"></span>
      <h3>🔍 <?php _e('Search Console Integration', 'clarity-first-seo'); ?></h3>
      <span class="cfseo-confidence-badge confidence-high"><?php _e('Confidence: High', 'clarity-first-seo'); ?></span>
    </div>
    <div class="cfseo-group-summary">
      <?php echo CFSEO_Validation::get_status_badge($console_pass, $console_total); ?>
      <span><?php echo $console_pass; ?> / <?php echo $console_total; ?> <?php _e('passed', 'clarity-first-seo'); ?></span>
      <span class="cfseo-toggle">▼</span>
    </div>
  </div>
  <div class="cfseo-group-content" id="group-console" style="display: none;">
    <p class="cfseo-group-description">
      <?php _e('Verification tags for search engine webmaster tools', 'clarity-first-seo'); ?>
    </p>
    
    <!-- Google Search Console -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Google Search Console', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engine: Google', 'clarity-first-seo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge(count($results['google_verification'])); ?>
      </div>
      <?php if (count($results['google_verification']) >= 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html(substr($results['google_verification'][0], 0, 40)); ?>...</code>
          <p class="description">✓ <?php _e('Google verification tag found', 'clarity-first-seo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">
          ⚠ <?php _e('No Google Search Console verification tag', 'clarity-first-seo'); ?>
        </p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google</p>
          <p><strong><?php _e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php _e('Verifies site ownership in Google Search Console', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php _e('No direct ranking impact - Required for Search Console access', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//meta[@name="google-site-verification"]</code></p>
        </div>
      </details>
    </div>
    
    <!-- Bing Webmaster Tools -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Bing Webmaster Tools', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engine: Bing', 'clarity-first-seo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge(count($results['msvalidate'])); ?>
      </div>
      <?php if (count($results['msvalidate']) >= 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html(substr($results['msvalidate'][0], 0, 40)); ?>...</code>
          <p class="description">✓ <?php _e('Bing verification tag found', 'clarity-first-seo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">
          ⚠ <?php _e('No Bing Webmaster Tools verification tag', 'clarity-first-seo'); ?>
        </p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Bing</p>
          <p><strong><?php _e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php _e('Verifies site ownership in Bing Webmaster Tools', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php _e('No direct ranking impact - Required for Bing Webmaster access', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//meta[@name="msvalidate.01"]</code></p>
        </div>
      </details>
    </div>
    
    <!-- Yandex Webmaster -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Yandex Webmaster', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engine: Yandex', 'clarity-first-seo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge(count($results['yandex_verification'])); ?>
      </div>
      <?php if (count($results['yandex_verification']) >= 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html(substr($results['yandex_verification'][0], 0, 40)); ?>...</code>
          <p class="description">✓ <?php _e('Yandex verification tag found', 'clarity-first-seo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">
          ⚠ <?php _e('No Yandex Webmaster verification tag', 'clarity-first-seo'); ?>
        </p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Yandex</p>
          <p><strong><?php _e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php _e('Verifies site ownership in Yandex Webmaster', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php _e('No direct ranking impact - Required for Yandex Webmaster access', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//meta[@name="yandex-verification"]</code></p>
        </div>
      </details>
    </div>
  </div>
</div>
