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
<div class="cfseo-function-group">
  <div class="cfseo-group-header" data-group="indexing">
    <div class="cfseo-group-title">
      <span class="dashicons dashicons-admin-settings"></span>
      <h3>🚦 <?php _e('Indexing Control', 'clarity-first-seo'); ?></h3>
      <span class="cfseo-confidence-badge confidence-high"><?php _e('Confidence: High', 'clarity-first-seo'); ?></span>
    </div>
    <div class="cfseo-group-summary">
      <?php echo CFSEO_Validation::get_status_badge($indexing_pass, $indexing_total); ?>
      <span><?php echo $indexing_pass; ?> / <?php echo $indexing_total; ?> <?php _e('passed', 'clarity-first-seo'); ?></span>
      <span class="cfseo-toggle">▼</span>
    </div>
  </div>
  <div class="cfseo-group-content" id="group-indexing" style="display: none;">
    <p class="cfseo-group-description">
      <?php _e('Controls whether search engines can index this page', 'clarity-first-seo'); ?>
    </p>
    
    <!-- Robots Meta Tag -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Robots Meta Tag', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engines: Google, Bing, Yandex', 'clarity-first-seo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge(count($results['robots'])); ?>
      </div>
      <?php if (count($results['robots']) === 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html($results['robots'][0]); ?></code>
          <?php 
          $robots_lower = strtolower($results['robots'][0]);
          $has_noindex = stripos($robots_lower, 'noindex') !== false;
          $has_nofollow = stripos($robots_lower, 'nofollow') !== false;
          if ($has_noindex || $has_nofollow):
          ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php _e('Page has indexing restrictions:', 'clarity-first-seo'); ?>
            <?php if ($has_noindex): ?><?php _e('NOINDEX', 'clarity-first-seo'); ?><?php endif; ?>
            <?php if ($has_nofollow): ?><?php _e('NOFOLLOW', 'clarity-first-seo'); ?><?php endif; ?>
          </p>
          <?php else: ?>
          <p class="description">✓ <?php _e('Page is indexable', 'clarity-first-seo'); ?></p>
          <?php endif; ?>
        </div>
      <?php elseif (count($results['robots']) === 0): ?>
        <p class="description">✓ <?php _e('No robots meta (indexable by default)', 'clarity-first-seo'); ?></p>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php _e('Multiple robots meta tags found', 'clarity-first-seo'); ?>:
          <?php foreach ($results['robots'] as $idx => $r): ?>
            <br><?php echo ($idx + 1); ?>. <code><?php echo esc_html($r); ?></code>
          <?php endforeach; ?>
        </p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php _e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php _e('Controls indexing and link following behavior', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php _e('Direct (critical) - Prevents indexing if noindex is set', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//meta[@name="robots"]</code></p>
          <p><strong><?php _e('EXPECTED:', 'clarity-first-seo'); ?></strong> <?php _e('0 or 1 tag, no conflicting directives', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Canonical URL -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Canonical URL', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engines: Google, Bing', 'clarity-first-seo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge($canonical_check['status'] === 'pass' ? 1 : 0); ?>
      </div>
      <div class="cfseo-check-details">
        <?php if (count($results['canonical']) === 1): ?>
          <code><?php echo esc_html($results['canonical'][0]); ?></code>
          <?php if ($canonical_check['status'] === 'pass'): ?>
            <p class="description">✓ <?php _e('Canonical URL is valid (HTTP 200, indexable)', 'clarity-first-seo'); ?></p>
          <?php else: ?>
            <p class="description" style="color: #d63638;">
              ✗ <?php echo esc_html($canonical_check['message']); ?>
            </p>
          <?php endif; ?>
        <?php elseif (count($results['canonical']) === 0): ?>
          <p class="description" style="color: #dba617;">⚠ <?php _e('No canonical URL found', 'clarity-first-seo'); ?></p>
        <?php else: ?>
          <p class="description" style="color: #d63638;">
            ✗ <?php _e('Multiple canonical URLs found', 'clarity-first-seo'); ?>:
            <?php foreach ($results['canonical'] as $idx => $c): ?>
              <br><?php echo ($idx + 1); ?>. <code><?php echo esc_html($c); ?></code>
            <?php endforeach; ?>
          </p>
        <?php endif; ?>
      </div>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google, Bing</p>
          <p><strong><?php _e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php _e('Tells search engines which URL is the authoritative version', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php _e('Direct (strong) - Consolidates signals for duplicate content', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//link[@rel="canonical"]</code></p>
          <p><strong><?php _e('EXPECTED:', 'clarity-first-seo'); ?></strong> <?php _e('Exactly 1, URL must return HTTP 200, must be indexable (not noindex)', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
