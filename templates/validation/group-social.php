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
<div class="gscseo-function-group">
  <div class="gscseo-group-header" data-group="social">
    <div class="gscseo-group-title">
      <span class="dashicons dashicons-share"></span>
      <h3>📱 <?php _e('Social Sharing Optimization', 'bfseo'); ?></h3>
      <span class="gscseo-confidence-badge confidence-high"><?php _e('Confidence: High', 'bfseo'); ?></span>
    </div>
    <div class="gscseo-group-summary">
      <?php echo GSCSEO_Validation::get_status_badge($social_pass, $social_total); ?>
      <span><?php echo $social_pass; ?> / <?php echo $social_total; ?> <?php _e('passed', 'bfseo'); ?></span>
      <span class="gscseo-toggle">▼</span>
    </div>
  </div>
  <div class="gscseo-group-content" id="group-social" style="display: none;">
    <p class="gscseo-group-description">
      <?php _e('Controls how your page appears when shared on social media platforms', 'bfseo'); ?>
    </p>
    
    <!-- Open Graph Title -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Open Graph Title', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Platforms: Facebook, LinkedIn', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge(count($results['og_title'])); ?>
      </div>
      <?php if (count($results['og_title']) >= 1): ?>
        <div class="gscseo-check-details">
          <code><?php echo esc_html($results['og_title'][0]); ?></code>
          <p class="description">✓ <?php _e('Open Graph title found', 'bfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php _e('No Open Graph title', 'bfseo'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Open Graph Description -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Open Graph Description', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Platforms: Facebook, LinkedIn', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge(count($results['og_description'])); ?>
      </div>
      <?php if (count($results['og_description']) >= 1): ?>
        <div class="gscseo-check-details">
          <code><?php echo esc_html(substr($results['og_description'][0], 0, 100)); ?>...</code>
          <p class="description">✓ <?php _e('Open Graph description found', 'bfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php _e('No Open Graph description', 'bfseo'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Open Graph Image -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Open Graph Image', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Platforms: Facebook, LinkedIn', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge(count($results['og_image'])); ?>
      </div>
      <?php if (count($results['og_image']) >= 1): ?>
        <div class="gscseo-check-details">
          <a href="<?php echo esc_url($results['og_image'][0]); ?>" target="_blank">
            <?php echo esc_html($results['og_image'][0]); ?>
          </a>
          <p class="description">✓ <?php _e('Open Graph image found', 'bfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php _e('No Open Graph image', 'bfseo'); ?></p>
      <?php endif; ?>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('PLATFORMS:', 'bfseo'); ?></strong> Facebook, LinkedIn</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Image shown in social media share previews', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> XPath <code>//meta[@property="og:image"]</code></p>
          <p><strong><?php _e('RECOMMENDED:', 'bfseo'); ?></strong> <?php _e('Image size under 300KB for optimal loading', 'bfseo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Twitter Card Type -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Twitter Card Type', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Platform: X (Twitter)', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge(count($results['twitter_card'])); ?>
      </div>
      <?php if (count($results['twitter_card']) >= 1): ?>
        <div class="gscseo-check-details">
          <code><?php echo esc_html($results['twitter_card'][0]); ?></code>
          <p class="description">✓ <?php _e('Twitter Card type found', 'bfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php _e('No Twitter Card type', 'bfseo'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Twitter Title -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Twitter Title', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Platform: X (Twitter)', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge(count($results['twitter_title'])); ?>
      </div>
      <?php if (count($results['twitter_title']) >= 1): ?>
        <div class="gscseo-check-details">
          <code><?php echo esc_html($results['twitter_title'][0]); ?></code>
          <p class="description">✓ <?php _e('Twitter title found', 'bfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php _e('No Twitter title', 'bfseo'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Twitter Description -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Twitter Description', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Platform: X (Twitter)', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge(count($results['twitter_description'])); ?>
      </div>
      <?php if (count($results['twitter_description']) >= 1): ?>
        <div class="gscseo-check-details">
          <code><?php echo esc_html(substr($results['twitter_description'][0], 0, 100)); ?>...</code>
          <p class="description">✓ <?php _e('Twitter description found', 'bfseo'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php _e('No Twitter description', 'bfseo'); ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>
