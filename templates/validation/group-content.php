<?php
/**
 * Function Group: Content Structure Analysis
 * @var array $headings Heading structure analysis
 * @var array $images Image analysis results
 * @var array $links Internal/external link analysis
 */
if (!defined('ABSPATH')) exit;

$cfseo_content_pass = 0;
$cfseo_content_total = 3;
if ($headings && $headings['has_h1']) $cfseo_content_pass++;
if ($images && $images['with_alt'] > 0) $cfseo_content_pass++;
if ($links && ($links['internal'] > 0 || $links['external'] > 0)) $cfseo_content_pass++;
?>
<div class="cfseo-function-group">
  <div class="cfseo-group-header" data-group="content">
    <div class="cfseo-group-title">
      <span class="dashicons dashicons-media-text"></span>
      <h3>📝 <?php esc_html_e('Content Structure Analysis', 'clarity-first-seo'); ?></h3>
      <span class="cfseo-confidence-badge confidence-high"><?php esc_html_e('Confidence: High', 'clarity-first-seo'); ?></span>
    </div>
    <div class="cfseo-group-summary">
      <?php echo esc_html(CFSEO_Validation::get_status_badge($cfseo_content_pass, $cfseo_content_total)); ?>
        <span><?php echo esc_html($cfseo_content_pass); ?> / <?php echo esc_html($cfseo_content_total); ?> <?php esc_html_e('passed', 'clarity-first-seo'); ?></span>
      <span class="cfseo-toggle">▼</span>
    </div>
  </div>
  <div class="cfseo-group-content" id="group-content" style="display: none;">
    <p class="cfseo-group-description">
      <?php esc_html_e('Analysis of your page content structure, images, and links', 'clarity-first-seo'); ?>
    </p>
    
    <!-- Heading Structure -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('Heading Structure', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Engines: Google, Bing', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge($headings && $headings['has_h1'] ? 1 : 0); ?>
      </div>
      <?php if ($headings): ?>
        <div class="cfseo-check-details">
          <p class="description">
            <?php if ($headings['has_h1']): ?>
              ✓ <?php esc_html_e('H1 heading found', 'clarity-first-seo'); ?>
            <?php else: ?>
              <span style="color: #dba617;">⚠ <?php esc_html_e('No H1 heading found', 'clarity-first-seo'); ?></span>
            <?php endif; ?>
          </p>
          <p class="description">
            <?php 
            printf(
              /* translators: %1$d to %6$d: heading counts for H1 through H6 */
              esc_html__('Headings: H1=%1$d, H2=%2$d, H3=%3$d, H4=%4$d, H5=%5$d, H6=%6$d', 'clarity-first-seo'),
              esc_html($headings['h1_count']),
              esc_html($headings['h2_count']),
              esc_html($headings['h3_count']),
              esc_html($headings['h4_count']),
              esc_html($headings['h5_count']),
              esc_html($headings['h6_count'])
            );
            ?>
          </p>
          <?php if (!empty($headings['hierarchy_issues'])): ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php esc_html_e('Hierarchy warnings:', 'clarity-first-seo'); ?>
            <?php foreach ($headings['hierarchy_issues'] as $cfseo_issue): ?>
              <br>• <?php echo esc_html($cfseo_issue); ?>
            <?php endforeach; ?>
          </p>
          <?php endif; ?>
          <?php if (!empty($headings['headings'])): ?>
          <details style="margin-top: 10px;">
            <summary><?php esc_html_e('View all headings', 'clarity-first-seo'); ?></summary>
            <ul style="margin-top: 5px;">
              <?php foreach ($headings['headings'] as $cfseo_h): ?>
                <li><strong>H<?php echo esc_html($cfseo_h['level']); ?>:</strong> <?php echo esc_html($cfseo_h['text']); ?></li>
              <?php endforeach; ?>
            </ul>
          </details>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <p class="description" style="color: #d63638;">✗ <?php esc_html_e('Could not analyze heading structure', 'clarity-first-seo'); ?></p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google, Bing</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Helps search engines understand content hierarchy', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Indirect (weak-medium) - Supports content understanding', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//h1, //h2, //h3, //h4, //h5, //h6</code></p>
          <p><strong><?php esc_html_e('NOTE:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Hierarchy issues are warnings, not errors. Perfect hierarchy is ideal but not critical.', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Image Analysis -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('Image Optimization', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Engines: Google Images', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge($images && $images['with_alt'] > 0 ? 1 : 0); ?>
      </div>
      <?php if ($images): ?>
        <div class="cfseo-check-details">
          <p class="description">
            <?php
            /* translators: %d: number of total images */
            printf(esc_html__('Total images: %d', 'clarity-first-seo'), esc_html($images['total']));
            ?>
            <br>
            <?php
            /* translators: %d: number of images with alt text */
            printf(esc_html__('Images with alt text: %d', 'clarity-first-seo'), esc_html($images['with_alt']));
            ?>
            <?php if ($images['total'] > 0): ?>
              (<?php echo esc_html(round(($images['with_alt'] / $images['total']) * 100)); ?>%)
            <?php endif; ?>
            <?php if ($images['without_alt'] > 0): ?>
              <br><span style="color: #dba617;">⚠ <?php
              /* translators: %d: number of images missing alt text */
              printf(esc_html__('%d images missing alt text', 'clarity-first-seo'), esc_html($images['without_alt'])); ?></span>
            <?php endif; ?>
          </p>
          <?php if (!empty($images['size_warnings'])): ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php esc_html_e('Image size warnings:', 'clarity-first-seo'); ?>
            <?php foreach ($images['size_warnings'] as $cfseo_warning): ?>
              <br>• <?php echo esc_html($cfseo_warning); ?>
            <?php endforeach; ?>
          </p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <p class="description">ℹ <?php esc_html_e('No images found on this page', 'clarity-first-seo'); ?></p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google Images</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Alt text helps search engines understand image content', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Indirect (medium for image search, accessibility)', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//img</code>, <?php esc_html_e('check @alt attribute', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('RECOMMENDED:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Social media images (OG, Twitter) should be under 300KB', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Internal Links -->
    <div class="cfseo-check-item">
      <div class="cfseo-check-header">
        <strong><?php esc_html_e('Internal & External Links', 'clarity-first-seo'); ?></strong>
        <span class="cfseo-engine-scope"><?php esc_html_e('Engines: Google, Bing', 'clarity-first-seo'); ?></span>
        <?php echo esc_html(CFSEO_Validation::get_status_badge($links && ($links['internal'] > 0 || $links['external'] > 0) ? 1 : 0); ?>
      </div>
      <?php if ($links): ?>
        <div class="cfseo-check-details">
          <p class="description">
            <?php
            /* translators: %d: number of internal links */
            printf(esc_html__('Internal links: %d', 'clarity-first-seo'), esc_html($links['internal']));
            ?>
            <br>
            <?php
            /* translators: %d: number of external links */
            printf(esc_html__('External links: %d', 'clarity-first-seo'), esc_html($links['external']));
            ?>
          </p>
          <?php if ($links['internal'] === 0): ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php esc_html_e('No internal links found. Consider adding links to related content.', 'clarity-first-seo'); ?>
          </p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <p class="description">ℹ <?php esc_html_e('No links found on this page', 'clarity-first-seo'); ?></p>
      <?php endif; ?>
      <details class="cfseo-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'clarity-first-seo'); ?></summary>
        <div class="cfseo-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'clarity-first-seo'); ?></strong> Google, Bing</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Internal links help with site navigation and PageRank distribution', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'clarity-first-seo'); ?></strong> <?php esc_html_e('Indirect (medium) - Supports crawlability and authority flow', 'clarity-first-seo'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'clarity-first-seo'); ?></strong> XPath <code>//a[@href]</code>, <?php esc_html_e('compare domain', 'clarity-first-seo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>

