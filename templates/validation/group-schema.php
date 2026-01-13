<?php
/**
 * Function Group: Rich Results (Structured Data)
 * @var array $results Validation results
 * @var array $schema_check Schema validation results
 */
if (!defined('ABSPATH')) exit;

$schema_pass = ($schema_check['status'] === 'pass') ? 1 : 0;
$schema_total = 1;
?>
<div class="cfseo-function-group">
  <div class="cfseo-group-header" data-group="schema">
    <div class="cfseo-group-title">
      <span class="dashicons dashicons-editor-code"></span>
      <h3>⭐ <?php _e('Rich Results (Structured Data)', 'clarity-first-seo'); ?></h3>
      <span class="cfseo-confidence-badge confidence-medium"><?php _e('Confidence: Medium', 'clarity-first-seo'); ?></span>
    </div>
    <div class="cfseo-group-summary">
      <?php echo CFSEO_Validation::get_status_badge($schema_pass, $schema_total); ?>
      <span><?php echo $schema_pass; ?> / <?php echo $schema_total; ?> <?php _e('passed', 'clarity-first-seo'); ?></span>
      <span class="cfseo-toggle">▼</span>
    </div>
  </div>
  <div class="cfseo-group-content" id="group-schema" style="display: none;">
    <p class="cfseo-group-description">
      <?php _e('Structured data that enables rich results in search engines', 'clarity-first-seo'); ?>
    </p>
    
    <!-- Schema JSON-LD -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Schema.org JSON-LD', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engines: Google, Bing, Yandex', 'clarity-first-seo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge($schema_pass, $schema_total); ?>
      </div>
      <div class="cfseo-check-details">
        <?php if (!empty($results['schema'])): ?>
          <p class="description">
            ✓ <?php printf(__('%d Schema block(s) found', 'clarity-first-seo'), count($results['schema'])); ?>
          </p>
          <?php foreach ($results['schema'] as $idx => $schema): 
            $schema_data = json_decode($schema, true);
            if (is_array($schema_data)):
              $type = isset($schema_data['@type']) ? $schema_data['@type'] : 'Unknown';
          ?>
          <details style="margin-top: 10px;">
            <summary><strong><?php printf(__('Block %d:', 'clarity-first-seo'), $idx + 1); ?></strong> <?php echo esc_html($type); ?></summary>
            <pre style="max-height: 300px; overflow-y: auto; background: #f6f7f7; padding: 10px; border-radius: 4px;"><?php echo esc_html(json_encode($schema_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
          </details>
          <?php 
            endif;
          endforeach; 
          ?>
          <?php if ($schema_check['status'] !== 'pass'): ?>
          <p class="description" style="color: #dba617; margin-top: 10px;">
            ⚠ <?php echo esc_html($schema_check['message']); ?>
          </p>
          <?php endif; ?>
        <?php else: ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php _e('No Schema.org structured data found', 'clarity-first-seo'); ?>
          </p>
        <?php endif; ?>
      </div>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php _e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php _e('Enables rich results (reviews, events, recipes, etc.) in SERPs', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php _e('Indirect (medium-high) - Can improve CTR via rich snippets', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//script[@type="application/ld+json"]</code>, <?php _e('JSON parse check', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('ALLOWED:', 'clarity-first-seo'); ?></strong> <?php _e('Multiple blocks are valid. Check for type conflicts (e.g., Product + Article)', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('CONFIDENCE LEVEL:', 'clarity-first-seo'); ?></strong> <?php _e('Medium - Basic syntax validation only. Use Google Rich Results Test for full validation.', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
