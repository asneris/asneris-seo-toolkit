<?php
/**
 * Function Group: Page Identity & Search Appearance
 * @var array $results Validation results
 */
if (!defined('ABSPATH')) exit;

$ASNERISSEO_identity_pass = 0;
$ASNERISSEO_identity_total = 0;
if (count($results['title']) === 1) $identity_pass++;
if (count($results['description']) === 1) $identity_pass++;
?>
<div class="ASNERISSEO-function-group">
  <div class="ASNERISSEO-group-header" data-group="identity">
    <div class="ASNERISSEO-group-title">
      <span class="dashicons dashicons-search"></span>
      <h3>🎯 <?php esc_html_e('Page Identity & Search Appearance', 'asneris-seo-toolkit'); ?></h3>
      <span class="ASNERISSEO-confidence-badge confidence-high"><?php esc_html_e('Confidence: High', 'asneris-seo-toolkit'); ?></span>
    </div>
    <div class="ASNERISSEO-group-summary">
      <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($identity_pass, $identity_total); ?>
        <span><?php echo esc_html($identity_pass); ?> / <?php echo esc_html($identity_total); ?> <?php esc_html_e('passed', 'asneris-seo-toolkit'); ?></span>
      <span class="ASNERISSEO-toggle">▼</span>
    </div>
  </div>
  <div class="ASNERISSEO-group-content" id="group-identity" style="display: none;">
    <p class="ASNERISSEO-group-description">
      <?php esc_html_e('Controls how your page appears in Google search results and browser tabs', 'asneris-seo-toolkit'); ?>
    </p>
    
    <!-- Title Tag -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Title Tag', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engines: Google, Bing', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge(count($results['title'])); ?>
      </div>
      <?php if (count($results['title']) === 1): ?>
        <div class="ASNERISSEO-check-details">
          <code><?php echo esc_html($results['title'][0]); ?></code>
          <p class="description">
            ✓ <?php esc_html_e('Single title tag found', 'asneris-seo-toolkit'); ?> (<?php echo esc_html(strlen($results['title'][0])); ?> <?php esc_html_e('characters', 'asneris-seo-toolkit'); ?>)
          </p>
        </div>
      <?php elseif (count($results['title']) === 0): ?>
        <p class="description" style="color: #d63638;">✗ <?php esc_html_e('No title tag found', 'asneris-seo-toolkit'); ?></p>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php esc_html_e('Multiple title tags found', 'asneris-seo-toolkit'); ?>:
          <?php foreach ($results['title'] as $ASNERISSEO_idx => $ASNERISSEO_t): ?>
            <br><?php echo esc_html($ASNERISSEO_idx + 1); ?>. <code><?php echo esc_html($ASNERISSEO_t); ?></code>
          <?php endforeach; ?>
        </p>
      <?php endif; ?>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Google, Bing</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('The main title shown in search results and browser tabs', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Direct (strong) - Major ranking factor and CTR influence', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> XPath <code>//title</code>, <?php esc_html_e('Expected: Exactly 1', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPORTANT:', 'asneris-seo-toolkit'); ?></strong> ℹ <?php esc_html_e('Google may rewrite titles for some queries. This validates your implementation, not guaranteed SERP output.', 'asneris-seo-toolkit'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Meta Description -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Meta Description', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engines: Google, Bing (CTR only)', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge(count($results['description']), 1); ?>
      </div>
      <?php if (count($results['description']) === 1): 
        $ASNERISSEO_desc_length = strlen($results['description'][0]);
      ?>
        <div class="ASNERISSEO-check-details">
          <code><?php echo esc_html(substr($results['description'][0], 0, 160)); ?><?php if ($ASNERISSEO_desc_length > 160): ?>...<?php endif; ?></code>
          <p class="description">
            <?php
            /* translators: %d: number of characters in description */
            printf(esc_html__('Length: %d characters', 'asneris-seo-toolkit'), esc_html($ASNERISSEO_desc_length)); ?>
            <?php if ($ASNERISSEO_desc_length < 120 || $ASNERISSEO_desc_length > 160): ?>
              <span style="color: #dba617;"> (<?php esc_html_e('recommended: 120-160', 'asneris-seo-toolkit'); ?>)</span>
            <?php else: ?>
              ✓ <?php esc_html_e('Optimal length', 'asneris-seo-toolkit'); ?>
            <?php endif; ?>
          </p>
        </div>
      <?php elseif (count($results['description']) === 0): ?>
        <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No meta description found', 'asneris-seo-toolkit'); ?></p>
      <?php endif; ?>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Google, Bing</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Suggested snippet text under the title in search results', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('CTR only (not a ranking factor)', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> XPath <code>//meta[@name="description"]</code></p>
          <p><strong><?php esc_html_e('NOTE:', 'asneris-seo-toolkit'); ?></strong> ℹ <?php esc_html_e('Google may rewrite descriptions based on query.', 'asneris-seo-toolkit'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
