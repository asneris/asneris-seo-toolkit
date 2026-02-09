<?php
/**
 * Function Group: Rich Results (Structured Data)
 * @var array $results Validation results
 * @var array $schema_check Schema validation results
 */
if (!defined('ABSPATH')) exit;

$ASNERISSEO_schema_pass = ($schema_check['status'] === 'pass') ? 1 : 0;
$ASNERISSEO_schema_total = 1;
?>
<div class="ASNERISSEO-function-group">
  <div class="ASNERISSEO-group-header" data-group="schema">
    <div class="ASNERISSEO-group-title">
      <span class="dashicons dashicons-editor-code"></span>
      <h3>⭐ <?php esc_html_e('Rich Results (Structured Data)', 'asneris-seo-toolkit'); ?></h3>
      <span class="ASNERISSEO-confidence-badge confidence-medium"><?php esc_html_e('Confidence: Medium', 'asneris-seo-toolkit'); ?></span>
    </div>
    <div class="ASNERISSEO-group-summary">
      <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($schema_pass, $schema_total); ?>
        <span><?php echo esc_html($schema_pass); ?> / <?php echo esc_html($schema_total); ?> <?php esc_html_e('passed', 'asneris-seo-toolkit'); ?></span>
      <span class="ASNERISSEO-toggle">▼</span>
    </div>
  </div>
  <div class="ASNERISSEO-group-content" id="group-schema" style="display: none;">
    <p class="ASNERISSEO-group-description">
      <?php esc_html_e('Structured data that enables rich results in search engines', 'asneris-seo-toolkit'); ?>
    </p>
    
    <!-- Schema JSON-LD -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Schema.org JSON-LD', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engines: Google, Bing, Yandex', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($schema_pass, $schema_total); ?>
      </div>
      <div class="ASNERISSEO-check-details">
        <?php if (!empty($results['schema'])): ?>
          <p class="description">
            ✓ <?php
            /* translators: %d: number of schema blocks found */
            printf(esc_html__('%d Schema block(s) found', 'asneris-seo-toolkit'), count($results['schema'])); ?>
          </p>
          <?php foreach ($results['schema'] as $ASNERISSEO_idx => $ASNERISSEO_schema): 
            $ASNERISSEO_schema_data = json_decode($ASNERISSEO_schema, true);
            if (is_array($ASNERISSEO_schema_data)):
              $ASNERISSEO_type = isset($ASNERISSEO_schema_data['@type']) ? $ASNERISSEO_schema_data['@type'] : 'Unknown';
          ?>
          <details style="margin-top: 10px;">
            <summary><strong><?php
            /* translators: %d: block number */
            printf(esc_html__('Block %d:', 'asneris-seo-toolkit'), esc_html($idx + 1)); ?></strong> <?php echo esc_html($type); ?></summary>
            <pre style="max-height: 300px; overflow-y: auto; background: #f6f7f7; padding: 10px; border-radius: 4px;"><?php echo esc_html(wp_json_encode($schema_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
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
            ⚠ <?php esc_html_e('No Schema.org structured data found', 'asneris-seo-toolkit'); ?>
          </p>
        <?php endif; ?>
      </div>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Enables rich results (reviews, events, recipes, etc.) in SERPs', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Indirect (medium-high) - Can improve CTR via rich snippets', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> XPath <code>//script[@type="application/ld+json"]</code>, <?php esc_html_e('JSON parse check', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('ALLOWED:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Multiple blocks are valid. Check for type conflicts (e.g., Product + Article)', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('CONFIDENCE LEVEL:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Medium - Basic syntax validation only. Use Google Rich Results Test for full validation.', 'asneris-seo-toolkit'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
