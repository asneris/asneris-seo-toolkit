<?php
/**
 * Overall Score Display with Educational Messaging
 * @var array $score Score data (percentage, passed, total, color, status_text, checks)
 * @var array $results Validation results
 */
if (!defined('ABSPATH')) exit;
?>
<div class="cfseo-card" style="margin-top: 20px;">
  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
    <div>
      <h2 style="margin: 0;">📊 <?php esc_html_e('Overall SEO Score', 'clarity-first-seo'); ?></h2>
      <p style="margin: 5px 0 0 0; color: #646970;">
        <?php esc_html_e('Testing:', 'clarity-first-seo'); ?> 
        <a href="<?php echo esc_url($results['url']); ?>" target="_blank"><?php echo esc_html($results['url']); ?></a>
      </p>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 48px; font-weight: bold; color: <?php echo esc_attr($score['color']); ?>; line-height: 1;">
        <?php echo esc_html($score['percentage']); ?>%
      </div>
      <div style="color: <?php echo esc_attr($score['color']); ?>; font-weight: 600;">
        <?php echo esc_html($score['status_text']); ?>
      </div>
    </div>
  </div>
  <div style="padding: 15px; background: #f6f7f7; border-radius: 4px; margin-bottom: 15px;">
    <strong><?php echo esc_html($score['passed']); ?> / <?php echo esc_html($score['total']); ?> <?php esc_html_e('checks passed', 'clarity-first-seo'); ?></strong>
  </div>
  
  <!-- Educational Messaging -->
  <div style="background: #f0f6fc; border-left: 3px solid #2271b1; padding: 15px; margin-top: 15px;">
    <h3 style="margin: 0 0 10px 0; font-size: 15px; color: #2271b1;">
      💡 <?php esc_html_e('Understanding Your Score', 'clarity-first-seo'); ?>
    </h3>
    
    <?php 
    $cfseo_critical = $score['checks']['critical'];
    $cfseo_recommended = $score['checks']['recommended'];
    $cfseo_optimization = $score['checks']['optimization'];
    
    $cfseo_can_index = $cfseo_critical['passed'] === $cfseo_critical['total'];
    ?>
    
    <div style="margin: 10px 0;">
      <?php if ($cfseo_can_index): ?>
        <p style="margin: 5px 0; color: #00a32a;">
          <strong>✓ <?php esc_html_e('Your page CAN be indexed', 'clarity-first-seo'); ?></strong> 
          (<?php esc_html_e('HTTP 200 + no blocking rules', 'clarity-first-seo'); ?>)
        </p>
      <?php else: ?>
        <p style="margin: 5px 0; color: #d63638;">
          <strong>✗ <?php esc_html_e('Your page CANNOT be indexed', 'clarity-first-seo'); ?></strong>
        </p>
        <?php if ($critical['passed'] < $critical['total']): ?>
          <p style="margin: 5px 0; color: #d63638; font-size: 13px;">
            <?php esc_html_e('Fix critical issues below to allow search engines to index this page.', 'clarity-first-seo'); ?>
          </p>
        <?php endif; ?>
      <?php endif; ?>
      
      <?php if ($recommended['passed'] < $recommended['total']): ?>
        <p style="margin: 5px 0; color: #f0c33c;">
          <strong>⚠️ <?php
          /* translators: %d: number of missing recommended items */
          echo esc_html(sprintf(__('%d recommended items missing', 'clarity-first-seo'), $recommended['total'] - $recommended['passed'])); ?></strong>
        </p>
        <p style="margin: 5px 0; font-size: 13px; color: #666;">
          <?php esc_html_e('These improve how your page appears in search results and gets discovered.', 'clarity-first-seo'); ?>
        </p>
      <?php endif; ?>
      
      <?php if ($optimization['passed'] < $optimization['total'] && $can_index): ?>
        <p style="margin: 5px 0; color: #666;">
          <strong>📈 <?php
          /* translators: %d: number of optimization opportunities */
          echo esc_html(sprintf(__('%d optimization opportunities', 'clarity-first-seo'), $optimization['total'] - $optimization['passed'])); ?></strong>
        </p>
        <p style="margin: 5px 0; font-size: 13px; color: #666;">
          <?php esc_html_e('Add social tags, schema markup, and verification codes for enhanced features.', 'clarity-first-seo'); ?>
        </p>
      <?php endif; ?>
    </div>
    
    <p style="margin: 10px 0 0 0; font-size: 12px; color: #666; font-style: italic; border-top: 1px solid #ddd; padding-top: 10px;">
      <?php esc_html_e('Scoring method: Critical checks (60%), Recommended (30%), Optimization (10%)', 'clarity-first-seo'); ?>
    </p>
  </div>
</div>
