<?php
/**
 * Function Group: Search Console Integration
 * @var array $results Validation results
 */
if (!defined('ABSPATH')) exit;

$ASNERISSEO_console_pass = 0;
$ASNERISSEO_console_total = 3;
if (count($results['google_verification']) >= 1) $ASNERISSEO_console_pass++;
if (count($results['msvalidate']) >= 1) $ASNERISSEO_console_pass++;
if (count($results['yandex_verification']) >= 1) $ASNERISSEO_console_pass++;
?>
<div class="ASNERISSEO-function-group">
  <div class="ASNERISSEO-group-header" data-group="console">
    <div class="ASNERISSEO-group-title">
      <span class="dashicons dashicons-admin-generic"></span>
      <h3>🔍 <?php esc_html_e('Search Console Integration', 'asneris-seo-toolkit'); ?></h3>
      <span class="ASNERISSEO-confidence-badge confidence-high"><?php esc_html_e('Confidence: High', 'asneris-seo-toolkit'); ?></span>
    </div>
    <div class="ASNERISSEO-group-summary">
      <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($ASNERISSEO_console_pass, $ASNERISSEO_console_total)); ?>
        <span><?php echo esc_html($ASNERISSEO_console_pass); ?> / <?php echo esc_html($ASNERISSEO_console_total); ?> <?php esc_html_e('passed', 'asneris-seo-toolkit'); ?></span>
      <span class="ASNERISSEO-toggle">▼</span>
    </div>
  </div>
  <div class="ASNERISSEO-group-content" id="group-console" style="display: none;">
    <p class="ASNERISSEO-group-description">
      <?php esc_html_e('Verification tags for search engine webmaster tools', 'asneris-seo-toolkit'); ?>
    </p>
    
    <!-- Google Search Console -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Google Search Console', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engine: Google', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge(count($results['google_verification'])); ?>
      </div>
      <?php if (count($results['google_verification']) >= 1): ?>
        <div class="ASNERISSEO-check-details">
          <code><?php echo esc_html(substr($results['google_verification'][0], 0, 40)); ?>...</code>
          <p class="description">✓ <?php esc_html_e('Google verification tag found', 'asneris-seo-toolkit'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">
          ⚠ <?php esc_html_e('No Google Search Console verification tag', 'asneris-seo-toolkit'); ?>
        </p>
      <?php endif; ?>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Google</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Verifies site ownership in Google Search Console', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('No direct ranking impact - Required for Search Console access', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> XPath <code>//meta[@name="google-site-verification"]</code></p>
        </div>
      </details>
    </div>
    
    <!-- Bing Webmaster Tools -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Bing Webmaster Tools', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engine: Bing', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge(count($results['msvalidate'])); ?>
      </div>
      <?php if (count($results['msvalidate']) >= 1): ?>
        <div class="ASNERISSEO-check-details">
          <code><?php echo esc_html(substr($results['msvalidate'][0], 0, 40)); ?>...</code>
          <p class="description">✓ <?php esc_html_e('Bing verification tag found', 'asneris-seo-toolkit'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">
          ⚠ <?php esc_html_e('No Bing Webmaster Tools verification tag', 'asneris-seo-toolkit'); ?>
        </p>
      <?php endif; ?>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Bing</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Verifies site ownership in Bing Webmaster Tools', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('No direct ranking impact - Required for Bing Webmaster access', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> XPath <code>//meta[@name="msvalidate.01"]</code></p>
        </div>
      </details>
    </div>
    
    <!-- Yandex Webmaster -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Yandex Webmaster', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engine: Yandex', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge(count($results['yandex_verification'])); ?>
      </div>
      <?php if (count($results['yandex_verification']) >= 1): ?>
        <div class="ASNERISSEO-check-details">
          <code><?php echo esc_html(substr($results['yandex_verification'][0], 0, 40)); ?>...</code>
          <p class="description">✓ <?php esc_html_e('Yandex verification tag found', 'asneris-seo-toolkit'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">
          ⚠ <?php esc_html_e('No Yandex Webmaster verification tag', 'asneris-seo-toolkit'); ?>
        </p>
      <?php endif; ?>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Yandex</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Verifies site ownership in Yandex Webmaster', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('No direct ranking impact - Required for Yandex Webmaster access', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> XPath <code>//meta[@name="yandex-verification"]</code></p>
        </div>
      </details>
    </div>
  </div>
</div>
