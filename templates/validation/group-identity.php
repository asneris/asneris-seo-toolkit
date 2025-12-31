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
<div class="gscseo-function-group">
  <div class="gscseo-group-header" data-group="identity">
    <div class="gscseo-group-title">
      <span class="dashicons dashicons-search"></span>
      <h3>🎯 <?php _e('Page Identity & Search Appearance', 'bfseo'); ?></h3>
      <span class="gscseo-confidence-badge confidence-high"><?php _e('Confidence: High', 'bfseo'); ?></span>
    </div>
    <div class="gscseo-group-summary">
      <?php echo GSCSEO_Validation::get_status_badge($identity_pass, $identity_total); ?>
      <span><?php echo $identity_pass; ?> / <?php echo $identity_total; ?> <?php _e('passed', 'bfseo'); ?></span>
      <span class="gscseo-toggle">▼</span>
    </div>
  </div>
  <div class="gscseo-group-content" id="group-identity" style="display: none;">
    <p class="gscseo-group-description">
      <?php _e('Controls how your page appears in Google search results and browser tabs', 'bfseo'); ?>
    </p>
    
    <!-- Title Tag -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Title Tag', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engines: Google, Bing', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge(count($results['title'])); ?>
      </div>
      <?php if (count($results['title']) === 1): ?>
        <div class="gscseo-check-details">
          <code><?php echo esc_html($results['title'][0]); ?></code>
          <p class="description">
            ✓ <?php _e('Single title tag found', 'bfseo'); ?> (<?php echo strlen($results['title'][0]); ?> <?php _e('characters', 'bfseo'); ?>)
          </p>
        </div>
      <?php elseif (count($results['title']) === 0): ?>
        <p class="description" style="color: #d63638;">✗ <?php _e('No title tag found', 'bfseo'); ?></p>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php _e('Multiple title tags found', 'bfseo'); ?>:
          <?php foreach ($results['title'] as $idx => $t): ?>
            <br><?php echo ($idx + 1); ?>. <code><?php echo esc_html($t); ?></code>
          <?php endforeach; ?>
        </p>
      <?php endif; ?>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Google, Bing</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('The main title shown in search results and browser tabs', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('Direct (strong) - Major ranking factor and CTR influence', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> XPath <code>//title</code>, <?php _e('Expected: Exactly 1', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPORTANT:', 'bfseo'); ?></strong> ℹ <?php _e('Google may rewrite titles for some queries. This validates your implementation, not guaranteed SERP output.', 'bfseo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Meta Description -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Meta Description', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engines: Google, Bing (CTR only)', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge(count($results['description']), 1); ?>
      </div>
      <?php if (count($results['description']) === 1): 
        $desc_length = strlen($results['description'][0]);
      ?>
        <div class="gscseo-check-details">
          <code><?php echo esc_html(substr($results['description'][0], 0, 160)); ?><?php if ($desc_length > 160): ?>...<?php endif; ?></code>
          <p class="description">
            <?php printf(__('Length: %d characters', 'bfseo'), $desc_length); ?>
            <?php if ($desc_length < 120 || $desc_length > 160): ?>
              <span style="color: #dba617;"> (<?php _e('recommended: 120-160', 'bfseo'); ?>)</span>
            <?php else: ?>
              ✓ <?php _e('Optimal length', 'bfseo'); ?>
            <?php endif; ?>
          </p>
        </div>
      <?php elseif (count($results['description']) === 0): ?>
        <p class="description" style="color: #dba617;">⚠ <?php _e('No meta description found', 'bfseo'); ?></p>
      <?php endif; ?>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Google, Bing</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Suggested snippet text under the title in search results', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('CTR only (not a ranking factor)', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> XPath <code>//meta[@name="description"]</code></p>
          <p><strong><?php _e('NOTE:', 'bfseo'); ?></strong> ℹ <?php _e('Google may rewrite descriptions based on query.', 'bfseo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
