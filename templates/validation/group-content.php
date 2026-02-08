<?php
/**
 * Function Group: Content Structure Analysis
 * @var array $headings Heading structure analysis
 * @var array $images Image analysis results
 * @var array $links Internal/external link analysis
 */
if (!defined('ABSPATH')) exit;

$ASNERISSEO_content_pass = 0;
$ASNERISSEO_content_total = 3;
if ($headings && $headings['has_h1']) $ASNERISSEO_content_pass++;
if ($images && $images['with_alt'] > 0) $ASNERISSEO_content_pass++;
if ($links && ($links['internal'] > 0 || $links['external'] > 0)) $ASNERISSEO_content_pass++;
?>
<div class="ASNERISSEO-function-group">
  <div class="ASNERISSEO-group-header" data-group="content">
    <div class="ASNERISSEO-group-title">
      <span class="dashicons dashicons-media-text"></span>
      <h3>📝 <?php esc_html_e('Content Structure Analysis', 'asneris-seo-toolkit'); ?></h3>
      <span class="ASNERISSEO-confidence-badge confidence-high"><?php esc_html_e('Confidence: High', 'asneris-seo-toolkit'); ?></span>
    </div>
    <div class="ASNERISSEO-group-summary">
      <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($ASNERISSEO_content_pass, $ASNERISSEO_content_total)); ?>
        <span><?php echo esc_html($ASNERISSEO_content_pass); ?> / <?php echo esc_html($ASNERISSEO_content_total); ?> <?php esc_html_e('passed', 'asneris-seo-toolkit'); ?></span>
      <span class="ASNERISSEO-toggle">▼</span>
    </div>
  </div>
  <div class="ASNERISSEO-group-content" id="group-content" style="display: none;">
    <p class="ASNERISSEO-group-description">
      <?php esc_html_e('Analysis of your page content structure, images, and links', 'asneris-seo-toolkit'); ?>
    </p>
    
    <!-- Heading Structure -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Heading Structure', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engines: Google, Bing', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($headings && $headings['has_h1'] ? 1 : 0); ?>
      </div>
      <?php if ($headings): ?>
        <div class="ASNERISSEO-check-details">
          <p class="description">
            <?php if ($headings['has_h1']): ?>
              ✓ <?php esc_html_e('H1 heading found', 'asneris-seo-toolkit'); ?>
            <?php else: ?>
              <span style="color: #dba617;">⚠ <?php esc_html_e('No H1 heading found', 'asneris-seo-toolkit'); ?></span>
            <?php endif; ?>
          </p>
          <p class="description">
            <?php 
            printf(
              /* translators: %1$d to %6$d: heading counts for H1 through H6 */
              esc_html__('Headings: H1=%1$d, H2=%2$d, H3=%3$d, H4=%4$d, H5=%5$d, H6=%6$d', 'asneris-seo-toolkit'),
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
            ⚠ <?php esc_html_e('Hierarchy warnings:', 'asneris-seo-toolkit'); ?>
            <?php foreach ($headings['hierarchy_issues'] as $ASNERISSEO_issue): ?>
              <br>• <?php echo esc_html($ASNERISSEO_issue); ?>
            <?php endforeach; ?>
          </p>
          <?php endif; ?>
          <?php if (!empty($headings['headings'])): ?>
          <details style="margin-top: 10px;">
            <summary><?php esc_html_e('View all headings', 'asneris-seo-toolkit'); ?></summary>
            <ul style="margin-top: 5px;">
              <?php foreach ($headings['headings'] as $ASNERISSEO_h): ?>
                <li><strong>H<?php echo esc_html($ASNERISSEO_h['level']); ?>:</strong> <?php echo esc_html($ASNERISSEO_h['text']); ?></li>
              <?php endforeach; ?>
            </ul>
          </details>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <p class="description" style="color: #d63638;">✗ <?php esc_html_e('Could not analyze heading structure', 'asneris-seo-toolkit'); ?></p>
      <?php endif; ?>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Google, Bing</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Helps search engines understand content hierarchy', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Indirect (weak-medium) - Supports content understanding', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> XPath <code>//h1, //h2, //h3, //h4, //h5, //h6</code></p>
          <p><strong><?php esc_html_e('NOTE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Hierarchy issues are warnings, not errors. Perfect hierarchy is ideal but not critical.', 'asneris-seo-toolkit'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Image Analysis -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Image Optimization', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engines: Google Images', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($images && $images['with_alt'] > 0 ? 1 : 0); ?>
      </div>
      <?php if ($images): ?>
        <div class="ASNERISSEO-check-details">
          <p class="description">
            <?php
            /* translators: %d: number of total images */
            printf(esc_html__('Total images: %d', 'asneris-seo-toolkit'), esc_html($images['total']));
            ?>
            <br>
            <?php
            /* translators: %d: number of images with alt text */
            printf(esc_html__('Images with alt text: %d', 'asneris-seo-toolkit'), esc_html($images['with_alt']));
            ?>
            <?php if ($images['total'] > 0): ?>
              (<?php echo esc_html(round(($images['with_alt'] / $images['total']) * 100)); ?>%)
            <?php endif; ?>
            <?php if ($images['without_alt'] > 0): ?>
              <br><span style="color: #dba617;">⚠ <?php
              /* translators: %d: number of images missing alt text */
              printf(esc_html__('%d images missing alt text', 'asneris-seo-toolkit'), esc_html($images['without_alt'])); ?></span>
            <?php endif; ?>
          </p>
          <?php if (!empty($images['size_warnings'])): ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php esc_html_e('Image size warnings:', 'asneris-seo-toolkit'); ?>
            <?php foreach ($images['size_warnings'] as $ASNERISSEO_warning): ?>
              <br>• <?php echo esc_html($ASNERISSEO_warning); ?>
            <?php endforeach; ?>
          </p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <p class="description">ℹ <?php esc_html_e('No images found on this page', 'asneris-seo-toolkit'); ?></p>
      <?php endif; ?>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Google Images</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Alt text helps search engines understand image content', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Indirect (medium for image search, accessibility)', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> XPath <code>//img</code>, <?php esc_html_e('check @alt attribute', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('RECOMMENDED:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Social media images (OG, Twitter) should be under 300KB', 'asneris-seo-toolkit'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Internal Links -->
    <div class="ASNERISSEO-check-item">
      <div class="ASNERISSEO-check-header">
        <strong><?php esc_html_e('Internal & External Links', 'asneris-seo-toolkit'); ?></strong>
        <span class="ASNERISSEO-engine-scope"><?php esc_html_e('Engines: Google, Bing', 'asneris-seo-toolkit'); ?></span>
        <?php echo esc_html(ASNERISSEO_Validation::get_status_badge($links && ($links['internal'] > 0 || $links['external'] > 0) ? 1 : 0); ?>
      </div>
      <?php if ($links): ?>
        <div class="ASNERISSEO-check-details">
          <p class="description">
            <?php
            /* translators: %d: number of internal links */
            printf(esc_html__('Internal links: %d', 'asneris-seo-toolkit'), esc_html($links['internal']));
            ?>
            <br>
            <?php
            /* translators: %d: number of external links */
            printf(esc_html__('External links: %d', 'asneris-seo-toolkit'), esc_html($links['external']));
            ?>
          </p>
          <?php if ($links['internal'] === 0): ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php esc_html_e('No internal links found. Consider adding links to related content.', 'asneris-seo-toolkit'); ?>
          </p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <p class="description">ℹ <?php esc_html_e('No links found on this page', 'asneris-seo-toolkit'); ?></p>
      <?php endif; ?>
      <details class="ASNERISSEO-technical-details">
        <summary><?php esc_html_e('▼ Show technical details', 'asneris-seo-toolkit'); ?></summary>
        <div class="ASNERISSEO-tech-box">
          <p><strong><?php esc_html_e('ENGINE SCOPE:', 'asneris-seo-toolkit'); ?></strong> Google, Bing</p>
          <p><strong><?php esc_html_e('PURPOSE:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Internal links help with site navigation and PageRank distribution', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('IMPACT:', 'asneris-seo-toolkit'); ?></strong> <?php esc_html_e('Indirect (medium) - Supports crawlability and authority flow', 'asneris-seo-toolkit'); ?></p>
          <p><strong><?php esc_html_e('VALIDATION:', 'asneris-seo-toolkit'); ?></strong> XPath <code>//a[@href]</code>, <?php esc_html_e('compare domain', 'asneris-seo-toolkit'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>

