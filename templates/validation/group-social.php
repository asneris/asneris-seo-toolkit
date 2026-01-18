<?php
/**
 * Function Group: Social Sharing Optimization
 * @var array $results Validation results
 */
if (!defined('ABSPATH')) exit;

$cfseo_social_pass = 0;
$cfseo_social_total = 0;
if (count($results['og_title']) >= 1) $social_pass++;
if (count($results['og_description']) >= 1) $social_pass++;
if (count($results['og_image']) >= 1) $social_pass++;
if (count($results['twitter_card']) >= 1) $social_pass++;
if (count($results['twitter_title']) >= 1) $social_pass++;
if (count($results['twitter_description']) >= 1) $social_pass++;
?>
<div class="cfseo-function-group">
  <div class="cfseo-group-header" data-group="social">
    <div class="cfseo-group-title">
      <span class="dashicons dashicons-share"></span>
      <h3>📱 <?php esc_html_e('Social Sharing Optimization', 'clarity-first-seo'); ?></h3>
      <span class="cfseo-confidence-badge confidence-high"><?php esc_html_e('Confidence: High', 'clarity-first-seo'); ?></span>
    </div>
    <div class="cfseo-group-summary">
      <?php echo esc_html(CFSEO_Validation::get_status_badge($social_pass, $social_total); ?>
        <span><?php echo esc_html($social_pass); ?> / <?php echo esc_html($social_total); ?> <?php esc_html_e('passed', 'clarity-first-seo'); ?></span>
      <span class="cfseo-toggle">▼</span>
    </div>
  </div>
  <div class="cfseo-group-content" id="group-social" style="display: none;">
    <p class="cfseo-group-description">
      <?php esc_html_e('Controls how your page appears when shared on social media platforms', 'clarity-first-seo'); ?>
    </p>
    
    <!-- Open Graph Title -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('Open Graph Title', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Platforms: Facebook, LinkedIn', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge(count($results['og_title'])); ?>
      </div>
      <?php if (count($results['og_title']) >= 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html($results['og_title'][0]); ?></code>
          <p class="description">✓ <?php esc_html_e('Open Graph title found', 'clarity-first-seo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No Open Graph title', 'clarity-first-seo'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Open Graph Description -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('Open Graph Description', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Platforms: Facebook, LinkedIn', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge(count($results['og_description'])); ?>
      </div>
      <?php if (count($results['og_description']) >= 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html(substr($results['og_description'][0], 0, 100)); ?>...</code>
          <p class="description">✓ <?php esc_html_e('Open Graph description found', 'clarity-first-seo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No Open Graph description', 'clarity-first-seo'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Open Graph Image -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('Open Graph Image', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Platforms: Facebook, LinkedIn', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge(count($results['og_image'])); ?>
      </div>
      <?php if (count($results['og_image']) >= 1): ?>
        <div class="cfseo-check-details">
          <a href="<?php echo esc_url($results['og_image'][0]); ?>" target="_blank">
            <?php echo esc_html($results['og_image'][0]); ?>
          </a>
          <p class="description">✓ <?php esc_html_e('Open Graph image found', 'clarity-first-seo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No Open Graph image', 'clarity-first-seo'); ?></p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php esc_html_e('PLATFORMS:', 'clarity-first-seo'); ?></strong> Facebook, LinkedIn</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Image shown in social media share previews', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//meta[@property="og:image"]</code></p>
          <p><strong><?php esc_html_e('RECOMMENDED:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Image size under 300KB for optimal loading', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Twitter Card Type -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('Twitter Card Type', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Platform: X (Twitter)', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge(count($results['twitter_card'])); ?>
      </div>
      <?php if (count($results['twitter_card']) >= 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html($results['twitter_card'][0]); ?></code>
          <p class="description">✓ <?php esc_html_e('Twitter Card type found', 'clarity-first-seo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No Twitter Card type', 'clarity-first-seo'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Twitter Title -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('Twitter Title', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Platform: X (Twitter)', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge(count($results['twitter_title'])); ?>
      </div>
      <?php if (count($results['twitter_title']) >= 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html($results['twitter_title'][0]); ?></code>
          <p class="description">✓ <?php esc_html_e('Twitter title found', 'clarity-first-seo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No Twitter title', 'clarity-first-seo'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Twitter Description -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('Twitter Description', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Platform: X (Twitter)', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge(count($results['twitter_description'])); ?>
      </div>
      <?php if (count($results['twitter_description']) >= 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html(substr($results['twitter_description'][0], 0, 100)); ?>...</code>
          <p class="description">✓ <?php esc_html_e('Twitter description found', 'clarity-first-seo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No Twitter description', 'clarity-first-seo'); ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>
