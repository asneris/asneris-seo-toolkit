<?php
/**
 * Function Group: Content Structure Analysis
 * @var array $headings Heading structure analysis
 * @var array $images Image analysis results
 * @var array $links Internal/external link analysis
 */
if (!defined('ABSPATH')) exit;

$content_pass = 0;
$content_total = 3;
if ($headings && $headings['has_h1']) $content_pass++;
if ($images && $images['with_alt'] > 0) $content_pass++;
if ($links && ($links['internal'] > 0 || $links['external'] > 0)) $content_pass++;
?>
<div class="cfseo-function-group">
  <div class="cfseo-group-header" data-group="content">
    <div class="cfseo-group-title">
      <span class="dashicons dashicons-media-text"></span>
      <h3>📝 <?php _e('Content Structure Analysis', 'clarity-first-seo'); ?></h3>
      <span class="cfseo-confidence-badge confidence-high"><?php _e('Confidence: High', 'clarity-first-seo'); ?></span>
    </div>
    <div class="cfseo-group-summary">
      <?php echo CFSEO_Validation::get_status_badge($content_pass, $content_total); ?>
      <span><?php echo $content_pass; ?> / <?php echo $content_total; ?> <?php _e('passed', 'clarity-first-seo'); ?></span>
      <span class="cfseo-toggle">▼</span>
    </div>
  </div>
  <div class="cfseo-group-content" id="group-content" style="display: none;">
    <p class="cfseo-group-description">
      <?php _e('Analysis of your page content structure, images, and links', 'clarity-first-seo'); ?>
    </p>
    
    <!-- Heading Structure -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Heading Structure', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engines: Google, Bing', 'clarity-first-seo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge($headings && $headings['has_h1'] ? 1 : 0); ?>
      </div>
      <?php if ($headings): ?>
        <div class="cfseo-check-details">
          <p class="description">
            <?php if ($headings['has_h1']): ?>
              ✓ <?php _e('H1 heading found', 'clarity-first-seo'); ?>
            <?php else: ?>
              <span style="color: #dba617;">⚠ <?php _e('No H1 heading found', 'clarity-first-seo'); ?></span>
            <?php endif; ?>
          </p>
          <p class="description">
            <?php 
            printf(
              __('Headings: H1=%d, H2=%d, H3=%d, H4=%d, H5=%d, H6=%d', 'clarity-first-seo'),
              $headings['h1_count'],
              $headings['h2_count'],
              $headings['h3_count'],
              $headings['h4_count'],
              $headings['h5_count'],
              $headings['h6_count']
            );
            ?>
          </p>
          <?php if (!empty($headings['hierarchy_issues'])): ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php _e('Hierarchy warnings:', 'clarity-first-seo'); ?>
            <?php foreach ($headings['hierarchy_issues'] as $issue): ?>
              <br>• <?php echo esc_html($issue); ?>
            <?php endforeach; ?>
          </p>
          <?php endif; ?>
          <?php if (!empty($headings['headings'])): ?>
          <details style="margin-top: 10px;">
            <summary><?php _e('View all headings', 'clarity-first-seo'); ?></summary>
            <ul style="margin-top: 5px;">
              <?php foreach ($headings['headings'] as $h): ?>
                <li><strong><?php echo esc_html($h['tag']); ?>:</strong> <?php echo esc_html($h['text']); ?></li>
              <?php endforeach; ?>
            </ul>
          </details>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <p class="description" style="color: #d63638;">✗ <?php _e('Could not analyze heading structure', 'clarity-first-seo'); ?></p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google, Bing</p>
          <p><strong><?php _e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php _e('Helps search engines understand content hierarchy', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php _e('Indirect (weak-medium) - Supports content understanding', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//h1, //h2, //h3, //h4, //h5, //h6</code></p>
          <p><strong><?php _e('NOTE:', 'clarity-first-seo'); ?></strong> <?php _e('Hierarchy issues are warnings, not errors. Perfect hierarchy is ideal but not critical.', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Image Analysis -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Image Optimization', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engines: Google Images', 'clarity-first-seo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge($images && $images['with_alt'] > 0 ? 1 : 0); ?>
      </div>
      <?php if ($images): ?>
        <div class="cfseo-check-details">
          <p class="description">
            <?php printf(__('Total images: %d', 'clarity-first-seo'), $images['total']); ?>
            <br>
            <?php printf(__('Images with alt text: %d', 'clarity-first-seo'), $images['with_alt']); ?>
            <?php if ($images['total'] > 0): ?>
              (<?php echo round(($images['with_alt'] / $images['total']) * 100); ?>%)
            <?php endif; ?>
            <?php if ($images['without_alt'] > 0): ?>
              <br><span style="color: #dba617;">⚠ <?php printf(__('%d images missing alt text', 'clarity-first-seo'), $images['without_alt']); ?></span>
            <?php endif; ?>
          </p>
          <?php if (!empty($images['size_warnings'])): ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php _e('Image size warnings:', 'clarity-first-seo'); ?>
            <?php foreach ($images['size_warnings'] as $warning): ?>
              <br>• <?php echo esc_html($warning); ?>
            <?php endforeach; ?>
          </p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <p class="description">ℹ <?php _e('No images found on this page', 'clarity-first-seo'); ?></p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google Images</p>
          <p><strong><?php _e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php _e('Alt text helps search engines understand image content', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php _e('Indirect (medium for image search, accessibility)', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//img</code>, <?php _e('check @alt attribute', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('RECOMMENDED:', 'clarity-first-seo'); ?></strong> <?php _e('Social media images (OG, Twitter) should be under 300KB', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Internal Links -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php _e('Internal & External Links', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php _e('Engines: Google, Bing', 'clarity-first-seo'); ?></span>
        <?php echo CFSEO_Validation::get_status_badge($links && ($links['internal'] > 0 || $links['external'] > 0) ? 1 : 0); ?>
      </div>
      <?php if ($links): ?>
        <div class="cfseo-check-details">
          <p class="description">
            <?php printf(__('Internal links: %d', 'clarity-first-seo'), $links['internal']); ?>
            <br>
            <?php printf(__('External links: %d', 'clarity-first-seo'), $links['external']); ?>
          </p>
          <?php if ($links['internal'] === 0): ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php _e('No internal links found. Consider adding links to related content.', 'clarity-first-seo'); ?>
          </p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <p class="description">ℹ <?php _e('No links found on this page', 'clarity-first-seo'); ?></p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google, Bing</p>
          <p><strong><?php _e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php _e('Internal links help with site navigation and PageRank distribution', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php _e('Indirect (medium) - Supports crawlability and authority flow', 'clarity-first-seo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//a[@href]</code>, <?php _e('compare domain', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
