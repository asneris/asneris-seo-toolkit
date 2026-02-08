<?php
/**
 * Overall Score Display with Educational Messaging
 * @var array $score Score data (percentage, passed, total, color, status_text, checks)
 * @var array $results Validation results
 */
if (!defined('ABSPATH')) exit;
?>
<div class="ASNERISSEO-card" style="margin-top: 20px;">
  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
    <div>
      <h2 style="margin: 0;">📊 <?php esc_html_e('Overall SEO Score', 'asneris-seo-toolkit'); ?></h2>
      <p style="margin: 5px 0 0 0; color: #646970;">
        <?php esc_html_e('Testing:', 'asneris-seo-toolkit'); ?> 
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
    <strong><?php echo esc_html($score['passed']); ?> / <?php echo esc_html($score['total']); ?> <?php esc_html_e('checks passed', 'asneris-seo-toolkit'); ?></strong>
  </div>
  
  <!-- Educational Messaging -->
  <div style="background: #f0f6fc; border-left: 3px solid #2271b1; padding: 15px; margin-top: 15px;">
    <h3 style="margin: 0 0 10px 0; font-size: 15px; color: #2271b1;">
      💡 <?php esc_html_e('Understanding Your Score', 'asneris-seo-toolkit'); ?>
    </h3>
    
    <?php 
    $ASNERISSEO_critical = $score['checks']['critical'];
    $ASNERISSEO_recommended = $score['checks']['recommended'];
    $ASNERISSEO_optimization = $score['checks']['optimization'];
    
    $ASNERISSEO_can_index = $ASNERISSEO_critical['passed'] === $ASNERISSEO_critical['total'];
    ?>
    
    <div style="margin: 10px 0;">
      <?php if ($ASNERISSEO_can_index): ?>
        <p style="margin: 5px 0; color: #00a32a;">
          <strong>✓ <?php esc_html_e('Your page CAN be indexed', 'asneris-seo-toolkit'); ?></strong> 
          (<?php esc_html_e('HTTP 200 + no blocking rules', 'asneris-seo-toolkit'); ?>)
        </p>
      <?php else: ?>
        <p style="margin: 5px 0; color: #d63638;">
          <strong>✗ <?php esc_html_e('Your page CANNOT be indexed', 'asneris-seo-toolkit'); ?></strong>
        </p>
        <?php if ($critical['passed'] < $critical['total']): ?>
          <p style="margin: 5px 0; color: #d63638; font-size: 13px;">
            <?php esc_html_e('Fix critical issues below to allow search engines to index this page.', 'asneris-seo-toolkit'); ?>
          </p>
        <?php endif; ?>
      <?php endif; ?>
      
      <?php if ($recommended['passed'] < $recommended['total']): ?>
        <p style="margin: 5px 0; color: #f0c33c;">
          <strong>⚠️ <?php
          /* translators: %d: number of missing recommended items */
          echo esc_html(sprintf(__('%d recommended items missing', 'asneris-seo-toolkit'), $recommended['total'] - $recommended['passed'])); ?></strong>
        </p>
        <p style="margin: 5px 0; font-size: 13px; color: #666;">
          <?php esc_html_e('These improve how your page appears in search results and gets discovered.', 'asneris-seo-toolkit'); ?>
        </p>
      <?php endif; ?>
      
      <?php if ($optimization['passed'] < $optimization['total'] && $can_index): ?>
        <p style="margin: 5px 0; color: #666;">
          <strong>📈 <?php
          /* translators: %d: number of optimization opportunities */
          echo esc_html(sprintf(__('%d optimization opportunities', 'asneris-seo-toolkit'), $optimization['total'] - $optimization['passed'])); ?></strong>
        </p>
        <p style="margin: 5px 0; font-size: 13px; color: #666;">
          <?php esc_html_e('Add social tags, schema markup, and verification codes for enhanced features.', 'asneris-seo-toolkit'); ?>
        </p>
      <?php endif; ?>
    </div>
    
    <p style="margin: 10px 0 0 0; font-size: 12px; color: #666; font-style: italic; border-top: 1px solid #ddd; padding-top: 10px;">
      <?php esc_html_e('Scoring method: Critical checks (60%), Recommended (30%), Optimization (10%)', 'asneris-seo-toolkit'); ?>
    </p>
  </div>
</div>
