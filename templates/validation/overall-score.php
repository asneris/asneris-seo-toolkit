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
      <h2 style="margin: 0;">📊 <?php _e('Overall SEO Score', 'clarity-first-seo'); ?></h2>
      <p style="margin: 5px 0 0 0; color: #646970;">
        <?php _e('Testing:', 'clarity-first-seo'); ?> 
        <a href="<?php echo esc_url($results['url']); ?>" target="_blank"><?php echo esc_html($results['url']); ?></a>
      </p>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 48px; font-weight: bold; color: <?php echo $score['color']; ?>; line-height: 1;">
        <?php echo $score['percentage']; ?>%
      </div>
      <div style="color: <?php echo $score['color']; ?>; font-weight: 600;">
        <?php echo $score['status_text']; ?>
      </div>
    </div>
  </div>
  <div style="padding: 15px; background: #f6f7f7; border-radius: 4px; margin-bottom: 15px;">
    <strong><?php echo $score['passed']; ?> / <?php echo $score['total']; ?> <?php _e('checks passed', 'clarity-first-seo'); ?></strong>
  </div>
  
  <!-- Educational Messaging -->
  <div style="background: #f0f6fc; border-left: 3px solid #2271b1; padding: 15px; margin-top: 15px;">
    <h3 style="margin: 0 0 10px 0; font-size: 15px; color: #2271b1;">
      💡 <?php _e('Understanding Your Score', 'clarity-first-seo'); ?>
    </h3>
    
    <?php 
    $critical = $score['checks']['critical'];
    $recommended = $score['checks']['recommended'];
    $optimization = $score['checks']['optimization'];
    
    $can_index = $critical['passed'] === $critical['total'];
    ?>
    
    <div style="margin: 10px 0;">
      <?php if ($can_index): ?>
        <p style="margin: 5px 0; color: #00a32a;">
          <strong>✓ <?php _e('Your page CAN be indexed', 'clarity-first-seo'); ?></strong> 
          (<?php _e('HTTP 200 + no blocking rules', 'clarity-first-seo'); ?>)
        </p>
      <?php else: ?>
        <p style="margin: 5px 0; color: #d63638;">
          <strong>✗ <?php _e('Your page CANNOT be indexed', 'clarity-first-seo'); ?></strong>
        </p>
        <?php if ($critical['passed'] < $critical['total']): ?>
          <p style="margin: 5px 0; color: #d63638; font-size: 13px;">
            <?php _e('Fix critical issues below to allow search engines to index this page.', 'clarity-first-seo'); ?>
          </p>
        <?php endif; ?>
      <?php endif; ?>
      
      <?php if ($recommended['passed'] < $recommended['total']): ?>
        <p style="margin: 5px 0; color: #f0c33c;">
          <strong>⚠️ <?php printf(__('%d recommended items missing', 'clarity-first-seo'), $recommended['total'] - $recommended['passed']); ?></strong>
        </p>
        <p style="margin: 5px 0; font-size: 13px; color: #666;">
          <?php _e('These improve how your page appears in search results and gets discovered.', 'clarity-first-seo'); ?>
        </p>
      <?php endif; ?>
      
      <?php if ($optimization['passed'] < $optimization['total'] && $can_index): ?>
        <p style="margin: 5px 0; color: #666;">
          <strong>📈 <?php printf(__('%d optimization opportunities', 'clarity-first-seo'), $optimization['total'] - $optimization['passed']); ?></strong>
        </p>
        <p style="margin: 5px 0; font-size: 13px; color: #666;">
          <?php _e('Add social tags, schema markup, and verification codes for enhanced features.', 'clarity-first-seo'); ?>
        </p>
      <?php endif; ?>
    </div>
    
    <p style="margin: 10px 0 0 0; font-size: 12px; color: #666; font-style: italic; border-top: 1px solid #ddd; padding-top: 10px;">
      <?php _e('Scoring method: Critical checks (60%), Recommended (30%), Optimization (10%)', 'clarity-first-seo'); ?>
    </p>
  </div>
</div>
