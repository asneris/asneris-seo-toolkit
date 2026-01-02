<?php
/**
 * Function Group: Page Identity & Search Appearance
 * @var array $results Validation results
 */
if (!defined('ABSPATH')) exit;

$identity_pass = 0;
$identity_total = 2;
if (count($results['title']) === 1) $identity_pass++;
if (count($results['description']) === 1) $identity_pass++;
?>
<div class="cfseo-function-group">
  <div class="cfseo-group-header" data-group="identity">
    <div class="cfseo-group-title">
      <span class="dashicons dashicons-search"></span>
      <h3>🎯 <?php _e('Page Identity & Search Appearance', 'cfseo'); ?></h3>
      <span class="cfseo-confidence-badge confidence-high"><?php _e('Confidence: High', 'cfseo'); ?></span>
    </div>
    <div class="cfseo-group-summary">
      <?php echo CFSEO_Validation::get_status_badge($identity_pass, $identity_total); ?>
      <span><?php echo $identity_pass; ?> / <?php echo $identity_total; ?> <?php _e('passed', 'cfseo'); ?></span>
      <span class="cfseo-toggle">▼</span>
    </div>
  </div>
  <div class="cfseo-group-content" id="group-identity" style="display: none;">
    <p class="cfseo-group-description">
      <?php _e('Controls how your page appears in Google search results and browser tabs', 'cfseo'); ?>
    </p>
    
    <!-- Title Tag -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Title Tag', 'cfseo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engines: Google, Bing', 'cfseo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge(count($results['title'])); ?>
      </div>
      <?php if (count($results['title']) === 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html($results['title'][0]); ?></code>
          <p class="description">
            ✓ <?php _e('Single title tag found', 'cfseo'); ?> (<?php echo strlen($results['title'][0]); ?> <?php _e('characters', 'cfseo'); ?>)
          </p>
        </div>
      <?php elseif (count($results['title']) === 0): ?>
        <p class="description" style="color: #d63638;">✗ <?php _e('No title tag found', 'cfseo'); ?></p>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php _e('Multiple title tags found', 'cfseo'); ?>:
          <?php foreach ($results['title'] as $idx => $t): ?>
            <br><?php echo ($idx + 1); ?>. <code><?php echo esc_html($t); ?></code>
          <?php endforeach; ?>
        </p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'cfseo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'cfseo'); ?></strong> Google, Bing</p>
          <p><strong><?php _e('PURPOSE:', 'cfseo'); ?></strong> <?php _e('The main title shown in search results and browser tabs', 'cfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'cfseo'); ?></strong> <?php _e('Direct (strong) - Major ranking factor and CTR influence', 'cfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'cfseo'); ?></strong> XPath <code>//title</code>, <?php _e('Expected: Exactly 1', 'cfseo'); ?></p>
          <p><strong><?php _e('IMPORTANT:', 'cfseo'); ?></strong> ℹ <?php _e('Google may rewrite titles for some queries. This validates your implementation, not guaranteed SERP output.', 'cfseo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Meta Description -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Meta Description', 'cfseo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engines: Google, Bing (CTR only)', 'cfseo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge(count($results['description']), 1); ?>
      </div>
      <?php if (count($results['description']) === 1): 
        $desc_length = strlen($results['description'][0]);
      ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html(substr($results['description'][0], 0, 160)); ?><?php if ($desc_length > 160): ?>...<?php endif; ?></code>
          <p class="description">
            <?php printf(__('Length: %d characters', 'cfseo'), $desc_length); ?>
            <?php if ($desc_length < 120 || $desc_length > 160): ?>
              <span style="color: #dba617;"> (<?php _e('recommended: 120-160', 'cfseo'); ?>)</span>
            <?php else: ?>
              ✓ <?php _e('Optimal length', 'cfseo'); ?>
            <?php endif; ?>
          </p>
        </div>
      <?php elseif (count($results['description']) === 0): ?>
        <p class="description" style="color: #dba617;">⚠ <?php _e('No meta description found', 'cfseo'); ?></p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'cfseo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'cfseo'); ?></strong> Google, Bing</p>
          <p><strong><?php _e('PURPOSE:', 'cfseo'); ?></strong> <?php _e('Suggested snippet text under the title in search results', 'cfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'cfseo'); ?></strong> <?php _e('CTR only (not a ranking factor)', 'cfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'cfseo'); ?></strong> XPath <code>//meta[@name="description"]</code></p>
          <p><strong><?php _e('NOTE:', 'cfseo'); ?></strong> ℹ <?php _e('Google may rewrite descriptions based on query.', 'cfseo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
