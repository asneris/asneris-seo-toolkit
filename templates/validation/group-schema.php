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
<div class="gscseo-function-group">
  <div class="gscseo-group-header" data-group="schema">
    <div class="gscseo-group-title">
      <span class="dashicons dashicons-editor-code"></span>
      <h3>⭐ <?php _e('Rich Results (Structured Data)', 'bfseo'); ?></h3>
      <span class="gscseo-confidence-badge confidence-medium"><?php _e('Confidence: Medium', 'bfseo'); ?></span>
    </div>
    <div class="gscseo-group-summary">
      <?php echo GSCSEO_Validation::get_status_badge($schema_pass, $schema_total); ?>
      <span><?php echo $schema_pass; ?> / <?php echo $schema_total; ?> <?php _e('passed', 'bfseo'); ?></span>
      <span class="gscseo-toggle">▼</span>
    </div>
  </div>
  <div class="gscseo-group-content" id="group-schema" style="display: none;">
    <p class="gscseo-group-description">
      <?php _e('Structured data that enables rich results in search engines', 'bfseo'); ?>
    </p>
    
    <!-- Schema JSON-LD -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Schema.org JSON-LD', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engines: Google, Bing, Yandex', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge($schema_pass, $schema_total); ?>
      </div>
      <div class="gscseo-check-details">
        <?php if (!empty($results['schema'])): ?>
          <p class="description">
            ✓ <?php printf(__('%d Schema block(s) found', 'bfseo'), count($results['schema'])); ?>
          </p>
          <?php foreach ($results['schema'] as $idx => $schema): 
            $schema_data = json_decode($schema, true);
            if (is_array($schema_data)):
              $type = isset($schema_data['@type']) ? $schema_data['@type'] : 'Unknown';
          ?>
          <details style="margin-top: 10px;">
            <summary><strong><?php printf(__('Block %d:', 'bfseo'), $idx + 1); ?></strong> <?php echo esc_html($type); ?></summary>
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
            ⚠ <?php _e('No Schema.org structured data found', 'bfseo'); ?>
          </p>
        <?php endif; ?>
      </div>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Enables rich results (reviews, events, recipes, etc.) in SERPs', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('Indirect (medium-high) - Can improve CTR via rich snippets', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> XPath <code>//script[@type="application/ld+json"]</code>, <?php _e('JSON parse check', 'bfseo'); ?></p>
          <p><strong><?php _e('ALLOWED:', 'bfseo'); ?></strong> <?php _e('Multiple blocks are valid. Check for type conflicts (e.g., Product + Article)', 'bfseo'); ?></p>
          <p><strong><?php _e('CONFIDENCE LEVEL:', 'bfseo'); ?></strong> <?php _e('Medium - Basic syntax validation only. Use Google Rich Results Test for full validation.', 'bfseo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
