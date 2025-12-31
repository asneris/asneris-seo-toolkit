<?php
/**
 * Function Group: Indexing Control
 * @var array $results Validation results
 * @var array $canonical_check Canonical URL validation results
 */
if (!defined('ABSPATH')) exit;

$indexing_pass = 0;
$indexing_total = 2;
if (count($results['robots']) === 1) $indexing_pass++;
if ($canonical_check['status'] === 'pass') $indexing_pass++;
?>
<div class="gscseo-function-group">
  <div class="gscseo-group-header" data-group="indexing">
    <div class="gscseo-group-title">
      <span class="dashicons dashicons-admin-settings"></span>
      <h3>🚦 <?php _e('Indexing Control', 'bfseo'); ?></h3>
      <span class="gscseo-confidence-badge confidence-high"><?php _e('Confidence: High', 'bfseo'); ?></span>
    </div>
    <div class="gscseo-group-summary">
      <?php echo GSCSEO_Validation::get_status_badge($indexing_pass, $indexing_total); ?>
      <span><?php echo $indexing_pass; ?> / <?php echo $indexing_total; ?> <?php _e('passed', 'bfseo'); ?></span>
      <span class="gscseo-toggle">▼</span>
    </div>
  </div>
  <div class="gscseo-group-content" id="group-indexing" style="display: none;">
    <p class="gscseo-group-description">
      <?php _e('Controls whether search engines can index this page', 'bfseo'); ?>
    </p>
    
    <!-- Robots Meta Tag -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Robots Meta Tag', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engines: Google, Bing, Yandex', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge(count($results['robots'])); ?>
      </div>
      <?php if (count($results['robots']) === 1): ?>
        <div class="gscseo-check-details">
          <code><?php echo esc_html($results['robots'][0]); ?></code>
          <?php 
          $robots_lower = strtolower($results['robots'][0]);
          $has_noindex = stripos($robots_lower, 'noindex') !== false;
          $has_nofollow = stripos($robots_lower, 'nofollow') !== false;
          if ($has_noindex || $has_nofollow):
          ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php _e('Page has indexing restrictions:', 'bfseo'); ?>
            <?php if ($has_noindex): ?><?php _e('NOINDEX', 'bfseo'); ?><?php endif; ?>
            <?php if ($has_nofollow): ?><?php _e('NOFOLLOW', 'bfseo'); ?><?php endif; ?>
          </p>
          <?php else: ?>
          <p class="description">✓ <?php _e('Page is indexable', 'bfseo'); ?></p>
          <?php endif; ?>
        </div>
      <?php elseif (count($results['robots']) === 0): ?>
        <p class="description">✓ <?php _e('No robots meta (indexable by default)', 'bfseo'); ?></p>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php _e('Multiple robots meta tags found', 'bfseo'); ?>:
          <?php foreach ($results['robots'] as $idx => $r): ?>
            <br><?php echo ($idx + 1); ?>. <code><?php echo esc_html($r); ?></code>
          <?php endforeach; ?>
        </p>
      <?php endif; ?>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Controls indexing and link following behavior', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('Direct (critical) - Prevents indexing if noindex is set', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> XPath <code>//meta[@name="robots"]</code></p>
          <p><strong><?php _e('EXPECTED:', 'bfseo'); ?></strong> <?php _e('0 or 1 tag, no conflicting directives', 'bfseo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Canonical URL -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Canonical URL', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engines: Google, Bing', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge($canonical_check['status'] === 'pass' ? 1 : 0); ?>
      </div>
      <div class="gscseo-check-details">
        <?php if (count($results['canonical']) === 1): ?>
          <code><?php echo esc_html($results['canonical'][0]); ?></code>
          <?php if ($canonical_check['status'] === 'pass'): ?>
            <p class="description">✓ <?php _e('Canonical URL is valid (HTTP 200, indexable)', 'bfseo'); ?></p>
          <?php else: ?>
            <p class="description" style="color: #d63638;">
              ✗ <?php echo esc_html($canonical_check['message']); ?>
            </p>
          <?php endif; ?>
        <?php elseif (count($results['canonical']) === 0): ?>
          <p class="description" style="color: #dba617;">⚠ <?php _e('No canonical URL found', 'bfseo'); ?></p>
        <?php else: ?>
          <p class="description" style="color: #d63638;">
            ✗ <?php _e('Multiple canonical URLs found', 'bfseo'); ?>:
            <?php foreach ($results['canonical'] as $idx => $c): ?>
              <br><?php echo ($idx + 1); ?>. <code><?php echo esc_html($c); ?></code>
            <?php endforeach; ?>
          </p>
        <?php endif; ?>
      </div>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Google, Bing</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Tells search engines which URL is the authoritative version', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('Direct (strong) - Consolidates signals for duplicate content', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> XPath <code>//link[@rel="canonical"]</code></p>
          <p><strong><?php _e('EXPECTED:', 'bfseo'); ?></strong> <?php _e('Exactly 1, URL must return HTTP 200, must be indexable (not noindex)', 'bfseo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
