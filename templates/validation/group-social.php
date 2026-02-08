<?php
/**
 * Function Group: Social Sharing Optimization
 * @var array $results Validation results
 */
if (!defined('ABSPATH')) exit;

$ASNERISSEO_social_pass = 0;
$ASNERISSEO_social_total = 0;
if (count($results['og_title']) >= 1) $social_pass++;
if (count($results['og_description']) >= 1) $social_pass++;
if (count($results['og_image']) >= 1) $social_pass++;
if (count($results['twitter_card']) >= 1) $social_pass++;
if (count($results['twitter_title']) >= 1) $social_pass++;
if (count($results['twitter_description']) >= 1) $social_pass++;
?>
<div class="ASNERISSEO-function-group">
  <div class="ASNERISSEO-group-header" data-group="social">
    <div class="ASNERISSEO-group-title">
      <span class="dashicons dashicons-share"></span>
      <h3>📱 <?php esc_html_e('Social Sharing Optimization', 'asneris-seo-toolkit'); ?></h3>
      <span class="ASNERISSEO-confidence-badge confidence-high"><?php esc_html_e('Confidence: High', 'asneris-seo-toolkit'); ?></span>
    </div>
    <div class="ASNERISSEO-group-summary">
      <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($social_pass, $social_total); ?>
        <span><?php echo esc_html($social_pass); ?> / <?php echo esc_html($social_total); ?> <?php esc_html_e('passed', 'asneris-seo-toolkit'); ?></span>
      <span class="ASNERISSEO-toggle">▼</span>
    </div>
  </div>
  <div class="ASNERISSEO-group-content" id="group-social" style="display: none;">
    <p class="ASNERISSEO-group-description">
      <?php esc_html_e('Controls how your page appears when shared on social media platforms', 'asneris-seo-toolkit'); ?>
    </p>
    
    <!-- Open Graph Title -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Open Graph Title', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Platforms: Facebook, LinkedIn', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge(count($results['og_title'])); ?>
      </div>
      <?php if (count($results['og_title']) >= 1): ?>
        <div class="ASNERISSEO-check-details">
          <code><?php echo esc_html($results['og_title'][0]); ?></code>
          <p class="description">✓ <?php esc_html_e('Open Graph title found', 'asneris-seo-toolkit'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No Open Graph title', 'asneris-seo-toolkit'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Open Graph Description -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Open Graph Description', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Platforms: Facebook, LinkedIn', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge(count($results['og_description'])); ?>
      </div>
      <?php if (count($results['og_description']) >= 1): ?>
        <div class="ASNERISSEO-check-details">
          <code><?php echo esc_html(substr($results['og_description'][0], 0, 100)); ?>...</code>
          <p class="description">✓ <?php esc_html_e('Open Graph description found', 'asneris-seo-toolkit'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No Open Graph description', 'asneris-seo-toolkit'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Open Graph Image -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Open Graph Image', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Platforms: Facebook, LinkedIn', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge(count($results['og_image'])); ?>
      </div>
      <?php if (count($results['og_image']) >= 1): ?>
        <div class="ASNERISSEO-check-details">
          <a href="<?php echo esc_url($results['og_image'][0]); ?>" target="_blank">
            <?php echo esc_html($results['og_image'][0]); ?>
          </a>
          <p class="description">✓ <?php esc_html_e('Open Graph image found', 'asneris-seo-toolkit'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No Open Graph image', 'asneris-seo-toolkit'); ?></p>
      <?php endif; ?>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('PLATFORMS:', 'asneris-seo-toolkit'); ?></strong> Facebook, LinkedIn</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Image shown in social media share previews', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> XPath <code>//meta[@property="og:image"]</code></p>
          <p><strong><?php esc_html_e('RECOMMENDED:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Image size under 300KB for optimal loading', 'asneris-seo-toolkit'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Twitter Card Type -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Twitter Card Type', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Platform: X (Twitter)', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge(count($results['twitter_card'])); ?>
      </div>
      <?php if (count($results['twitter_card']) >= 1): ?>
        <div class="ASNERISSEO-check-details">
          <code><?php echo esc_html($results['twitter_card'][0]); ?></code>
          <p class="description">✓ <?php esc_html_e('Twitter Card type found', 'asneris-seo-toolkit'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No Twitter Card type', 'asneris-seo-toolkit'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Twitter Title -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Twitter Title', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Platform: X (Twitter)', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge(count($results['twitter_title'])); ?>
      </div>
      <?php if (count($results['twitter_title']) >= 1): ?>
        <div class="ASNERISSEO-check-details">
          <code><?php echo esc_html($results['twitter_title'][0]); ?></code>
          <p class="description">✓ <?php esc_html_e('Twitter title found', 'asneris-seo-toolkit'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No Twitter title', 'asneris-seo-toolkit'); ?></p>
      <?php endif; ?>
    </div>
    
    <!-- Twitter Description -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Twitter Description', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Platform: X (Twitter)', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge(count($results['twitter_description'])); ?>
      </div>
      <?php if (count($results['twitter_description']) >= 1): ?>
        <div class="ASNERISSEO-check-details">
          <code><?php echo esc_html(substr($results['twitter_description'][0], 0, 100)); ?>...</code>
          <p class="description">✓ <?php esc_html_e('Twitter description found', 'asneris-seo-toolkit'); ?></p>
        </div>
      <?php else: ?>
        <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No Twitter description', 'asneris-seo-toolkit'); ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>
