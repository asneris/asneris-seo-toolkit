<?php
/**
 * Function Group: Social Sharing Optimization
 * @var array $results Validation results
 */
if (!defined('ABSPATH')) exit;

$social_pass = 0;
$social_total = 6;
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
      <h3>📱 <?php _e('Social Sharing Optimization', 'cfseo'); ?></h3>
      <span class="cfseo-confidence-badge confidence-high"><?php _e('Confidence: High', 'cfseo'); ?></span>
    </div>
    <div class="cfseo-group-summary">
      <?php echo CFSEO_Validation::get_status_badge($social_pass, $social_total); ?>
      <span><?php echo $social_pass; ?> / <?php echo $social_total; ?> <?php _e('passed', 'cfseo'); ?></span>
      <span class="cfseo-toggle">▼</span>
    </div>
  </div>
  <div class="cfseo-group-content" id="group-social" style="display: none;">
    <p class="cfseo-group-description">
      <?php _e('Controls how your page appears when shared on social media platforms', 'cfseo'); ?>
    </p>
    
    <!-- Open Graph Title -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Open Graph Title', 'cfseo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Platforms: Facebook, LinkedIn', 'cfseo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge(count($results['og_title'])); ?>
      </div>
      <?php if (count($results['og_title']) >= 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html($results['og_title'][0]); ?></code>
          <p class="description">✓ <?php _e('Open Graph title found', 'cfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php _e('No Open Graph title', 'cfseo'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Open Graph Description -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Open Graph Description', 'cfseo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Platforms: Facebook, LinkedIn', 'cfseo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge(count($results['og_description'])); ?>
      </div>
      <?php if (count($results['og_description']) >= 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html(substr($results['og_description'][0], 0, 100)); ?>...</code>
          <p class="description">✓ <?php _e('Open Graph description found', 'cfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php _e('No Open Graph description', 'cfseo'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Open Graph Image -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Open Graph Image', 'cfseo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Platforms: Facebook, LinkedIn', 'cfseo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge(count($results['og_image'])); ?>
      </div>
      <?php if (count($results['og_image']) >= 1): ?>
        <div class="cfseo-check-details">
          <a href="<?php echo esc_url($results['og_image'][0]); ?>" target="_blank">
            <?php echo esc_html($results['og_image'][0]); ?>
          </a>
          <p class="description">✓ <?php _e('Open Graph image found', 'cfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php _e('No Open Graph image', 'cfseo'); ?></p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'cfseo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('PLATFORMS:', 'cfseo'); ?></strong> Facebook, LinkedIn</p>
          <p><strong><?php _e('PURPOSE:', 'cfseo'); ?></strong> <?php _e('Image shown in social media share previews', 'cfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'cfseo'); ?></strong> XPath <code>//meta[@property="og:image"]</code></p>
          <p><strong><?php _e('RECOMMENDED:', 'cfseo'); ?></strong> <?php _e('Image size under 300KB for optimal loading', 'cfseo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Twitter Card Type -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Twitter Card Type', 'cfseo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Platform: X (Twitter)', 'cfseo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge(count($results['twitter_card'])); ?>
      </div>
      <?php if (count($results['twitter_card']) >= 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html($results['twitter_card'][0]); ?></code>
          <p class="description">✓ <?php _e('Twitter Card type found', 'cfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php _e('No Twitter Card type', 'cfseo'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Twitter Title -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Twitter Title', 'cfseo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Platform: X (Twitter)', 'cfseo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge(count($results['twitter_title'])); ?>
      </div>
      <?php if (count($results['twitter_title']) >= 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html($results['twitter_title'][0]); ?></code>
          <p class="description">✓ <?php _e('Twitter title found', 'cfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php _e('No Twitter title', 'cfseo'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Twitter Description -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Twitter Description', 'cfseo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Platform: X (Twitter)', 'cfseo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge(count($results['twitter_description'])); ?>
      </div>
      <?php if (count($results['twitter_description']) >= 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html(substr($results['twitter_description'][0], 0, 100)); ?>...</code>
          <p class="description">✓ <?php _e('Twitter description found', 'cfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php _e('No Twitter description', 'cfseo'); ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>
