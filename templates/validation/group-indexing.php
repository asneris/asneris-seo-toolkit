<?php
/**
 * Function Group: Indexing Control
 * @var array $results Validation results
 * @var array $canonical_check Canonical URL validation results
 */
if (!defined('ABSPATH')) exit;

$cfseo_indexing_pass = 0;
$cfseo_indexing_total = 2;
if (count($results['robots']) === 1) $cfseo_indexing_pass++;
if ($canonical_check['status'] === 'pass') $cfseo_indexing_pass++;
?>
<div class="cfseo-function-group">
  <div class="cfseo-group-header" data-group="indexing">
    <div class="cfseo-group-title">
      <span class="dashicons dashicons-admin-settings"></span>
      <h3>🚦 <?php esc_html_e('Indexing Control', 'clarity-first-seo'); ?></h3>
      <span class="cfseo-confidence-badge confidence-high"><?php esc_html_e('Confidence: High', 'clarity-first-seo'); ?></span>
    </div>
    <div class="cfseo-group-summary">
      <?php echo esc_html(CFSEO_Validation::get_status_badge($cfseo_indexing_pass, $cfseo_indexing_total)); ?>
        <span><?php echo esc_html($cfseo_indexing_pass); ?> / <?php echo esc_html($cfseo_indexing_total); ?> <?php esc_html_e('passed', 'clarity-first-seo'); ?></span>
      <span class="cfseo-toggle">▼</span>
    </div>
  </div>
  <div class="cfseo-group-content" id="group-indexing" style="display: none;">
    <p class="cfseo-group-description">
      <?php esc_html_e('Controls whether search engines can index this page', 'clarity-first-seo'); ?>
    </p>
    
    <!-- Robots Meta Tag -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('Robots Meta Tag', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Engines: Google, Bing, Yandex', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge(count($results['robots'])); ?>
      </div>
      <?php if (count($results['robots']) === 1): ?>
        <div class="cfseo-check-details">
          <code><?php echo esc_html($results['robots'][0]); ?></code>
          <?php 
          $cfseo_robots_lower = strtolower($results['robots'][0]);
          $cfseo_has_noindex = stripos($cfseo_robots_lower, 'noindex') !== false;
          $cfseo_has_nofollow = stripos($cfseo_robots_lower, 'nofollow') !== false;
          if ($cfseo_has_noindex || $cfseo_has_nofollow):
          ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php esc_html_e('Page has indexing restrictions:', 'clarity-first-seo'); ?>
            <?php if ($cfseo_has_noindex): ?><?php esc_html_e('NOINDEX', 'clarity-first-seo'); ?><?php endif; ?>
            <?php if ($cfseo_has_nofollow): ?><?php esc_html_e('NOFOLLOW', 'clarity-first-seo'); ?><?php endif; ?>
          </p>
          <?php else: ?>
          <p class="description">✓ <?php esc_html_e('Page is indexable', 'clarity-first-seo'); ?></p>
          <?php endif; ?>
        </div>
      <?php elseif (count($results['robots']) === 0): ?>
        <p class="description">✓ <?php esc_html_e('No robots meta (indexable by default)', 'clarity-first-seo'); ?></p>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php esc_html_e('Multiple robots meta tags found', 'clarity-first-seo'); ?>:
          <?php foreach ($results['robots'] as $cfseo_idx => $cfseo_r): ?>
            <br><?php echo esc_html($cfseo_idx + 1); ?>. <code><?php echo esc_html($cfseo_r); ?></code>
          <?php endforeach; ?>
        </p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Controls indexing and link following behavior', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Direct (critical) - Prevents indexing if noindex is set', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//meta[@name="robots"]</code></p>
          <p><strong><?php esc_html_e('EXPECTED:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('0 or 1 tag, no conflicting directives', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Canonical URL -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('Canonical URL', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Engines: Google, Bing', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge($canonical_check['status'] === 'pass' ? 1 : 0); ?>
      </div>
      <div class="cfseo-check-details">
        <?php if (count($results['canonical']) === 1): ?>
          <code><?php echo esc_html($results['canonical'][0]); ?></code>
          <?php if ($canonical_check['status'] === 'pass'): ?>
            <p class="description">✓ <?php esc_html_e('Canonical URL is valid (HTTP 200, indexable)', 'clarity-first-seo'); ?></p>
          <?php else: ?>
            <p class="description" style="color: #d63638;">
              ✗ <?php echo esc_html($canonical_check['message']); ?>
            </p>
          <?php endif; ?>
        <?php elseif (count($results['canonical']) === 0): ?>
          <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No canonical URL found', 'clarity-first-seo'); ?></p>
        <?php else: ?>
          <p class="description" style="color: #d63638;">
            ✗ <?php esc_html_e('Multiple canonical URLs found', 'clarity-first-seo'); ?>:
            <?php foreach ($results['canonical'] as $cfseo_idx => $cfseo_c): ?>
              <br><?php echo esc_html($cfseo_idx + 1); ?>. <code><?php echo esc_html($cfseo_c); ?></code>
            <?php endforeach; ?>
          </p>
        <?php endif; ?>
      </div>
      <details class="cfseo-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google, Bing</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Tells search engines which URL is the authoritative version', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Direct (strong) - Consolidates signals for duplicate content', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//link[@rel="canonical"]</code></p>
          <p><strong><?php esc_html_e('EXPECTED:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Exactly 1, URL must return HTTP 200, must be indexable (not noindex)', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>

