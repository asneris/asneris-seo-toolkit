<?php
/**
 * Function Group: Indexing Control
 * @var array $results Validation results
 * @var array $canonical_check Canonical URL validation results
 */
if (!defined('ABSPATH')) exit;

$ASNERISSEO_indexing_pass = 0;
$ASNERISSEO_indexing_total = 2;
if (count($results['robots']) === 1) $ASNERISSEO_indexing_pass++;
if ($canonical_check['status'] === 'pass') $ASNERISSEO_indexing_pass++;
?>
<div class="ASNERISSEO-function-group">
  <div class="ASNERISSEO-group-header" data-group="indexing">
    <div class="ASNERISSEO-group-title">
      <span class="dashicons dashicons-admin-settings"></span>
      <h3>🚦 <?php esc_html_e('Indexing Control', 'asneris-seo-toolkit'); ?></h3>
      <span class="ASNERISSEO-confidence-badge confidence-high"><?php esc_html_e('Confidence: High', 'asneris-seo-toolkit'); ?></span>
    </div>
    <div class="ASNERISSEO-group-summary">
      <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($ASNERISSEO_indexing_pass, $ASNERISSEO_indexing_total)); ?>
        <span><?php echo esc_html($ASNERISSEO_indexing_pass); ?> / <?php echo esc_html($ASNERISSEO_indexing_total); ?> <?php esc_html_e('passed', 'asneris-seo-toolkit'); ?></span>
      <span class="ASNERISSEO-toggle">▼</span>
    </div>
  </div>
  <div class="ASNERISSEO-group-content" id="group-indexing" style="display: none;">
    <p class="ASNERISSEO-group-description">
      <?php esc_html_e('Controls whether search engines can index this page', 'asneris-seo-toolkit'); ?>
    </p>
    
    <!-- Robots Meta Tag -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Robots Meta Tag', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engines: Google, Bing, Yandex', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge(count($results['robots'])); ?>
      </div>
      <?php if (count($results['robots']) === 1): ?>
        <div class="ASNERISSEO-check-details">
          <code><?php echo esc_html($results['robots'][0]); ?></code>
          <?php 
          $ASNERISSEO_robots_lower = strtolower($results['robots'][0]);
          $ASNERISSEO_has_noindex = stripos($ASNERISSEO_robots_lower, 'noindex') !== false;
          $ASNERISSEO_has_nofollow = stripos($ASNERISSEO_robots_lower, 'nofollow') !== false;
          if ($ASNERISSEO_has_noindex || $ASNERISSEO_has_nofollow):
          ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php esc_html_e('Page has indexing restrictions:', 'asneris-seo-toolkit'); ?>
            <?php if ($ASNERISSEO_has_noindex): ?><?php esc_html_e('NOINDEX', 'asneris-seo-toolkit'); ?><?php endif; ?>
            <?php if ($ASNERISSEO_has_nofollow): ?><?php esc_html_e('NOFOLLOW', 'asneris-seo-toolkit'); ?><?php endif; ?>
          </p>
          <?php else: ?>
          <p class="description">✓ <?php esc_html_e('Page is indexable', 'asneris-seo-toolkit'); ?></p>
          <?php endif; ?>
        </div>
      <?php elseif (count($results['robots']) === 0): ?>
        <p class="description">✓ <?php esc_html_e('No robots meta (indexable by default)', 'asneris-seo-toolkit'); ?></p>
      <?php else: ?>
        <p class="description" style="color: #d63638;">
          ✗ <?php esc_html_e('Multiple robots meta tags found', 'asneris-seo-toolkit'); ?>:
          <?php foreach ($results['robots'] as $ASNERISSEO_idx => $ASNERISSEO_r): ?>
            <br><?php echo esc_html($ASNERISSEO_idx + 1); ?>. <code><?php echo esc_html($ASNERISSEO_r); ?></code>
          <?php endforeach; ?>
        </p>
      <?php endif; ?>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Google, Bing, Yandex</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Controls indexing and link following behavior', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Direct (critical) - Prevents indexing if noindex is set', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> XPath <code>//meta[@name="robots"]</code></p>
          <p><strong><?php esc_html_e('EXPECTED:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('0 or 1 tag, no conflicting directives', 'asneris-seo-toolkit'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Canonical URL -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Canonical URL', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engines: Google, Bing', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($canonical_check['status'] === 'pass' ? 1 : 0); ?>
      </div>
      <div class="ASNERISSEO-check-details">
        <?php if (count($results['canonical']) === 1): ?>
          <code><?php echo esc_html($results['canonical'][0]); ?></code>
          <?php if ($canonical_check['status'] === 'pass'): ?>
            <p class="description">✓ <?php esc_html_e('Canonical URL is valid (HTTP 200, indexable)', 'asneris-seo-toolkit'); ?></p>
          <?php else: ?>
            <p class="description" style="color: #d63638;">
              ✗ <?php echo esc_html($canonical_check['message']); ?>
            </p>
          <?php endif; ?>
        <?php elseif (count($results['canonical']) === 0): ?>
          <p class="description" style="color: #dba617;">⚠ <?php esc_html_e('No canonical URL found', 'asneris-seo-toolkit'); ?></p>
        <?php else: ?>
          <p class="description" style="color: #d63638;">
            ✗ <?php esc_html_e('Multiple canonical URLs found', 'asneris-seo-toolkit'); ?>:
            <?php foreach ($results['canonical'] as $ASNERISSEO_idx => $ASNERISSEO_c): ?>
              <br><?php echo esc_html($ASNERISSEO_idx + 1); ?>. <code><?php echo esc_html($ASNERISSEO_c); ?></code>
            <?php endforeach; ?>
          </p>
        <?php endif; ?>
      </div>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Google, Bing</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Tells search engines which URL is the authoritative version', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Direct (strong) - Consolidates signals for duplicate content', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> XPath <code>//link[@rel="canonical"]</code></p>
          <p><strong><?php esc_html_e('EXPECTED:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Exactly 1, URL must return HTTP 200, must be indexable (not noindex)', 'asneris-seo-toolkit'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>

