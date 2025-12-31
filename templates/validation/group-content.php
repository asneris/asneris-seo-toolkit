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
<div class="gscseo-function-group">
  <div class="gscseo-group-header" data-group="content">
    <div class="gscseo-group-title">
      <span class="dashicons dashicons-media-text"></span>
      <h3>📝 <?php _e('Content Structure Analysis', 'bfseo'); ?></h3>
      <span class="gscseo-confidence-badge confidence-high"><?php _e('Confidence: High', 'bfseo'); ?></span>
    </div>
    <div class="gscseo-group-summary">
      <?php echo GSCSEO_Validation::get_status_badge($content_pass, $content_total); ?>
      <span><?php echo $content_pass; ?> / <?php echo $content_total; ?> <?php _e('passed', 'bfseo'); ?></span>
      <span class="gscseo-toggle">▼</span>
    </div>
  </div>
  <div class="gscseo-group-content" id="group-content" style="display: none;">
    <p class="gscseo-group-description">
      <?php _e('Analysis of your page content structure, images, and links', 'bfseo'); ?>
    </p>
    
    <!-- Heading Structure -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Heading Structure', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engines: Google, Bing', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge($headings && $headings['has_h1'] ? 1 : 0); ?>
      </div>
      <?php if ($headings): ?>
        <div class="gscseo-check-details">
          <p class="description">
            <?php if ($headings['has_h1']): ?>
              ✓ <?php _e('H1 heading found', 'bfseo'); ?>
            <?php else: ?>
              <span style="color: #dba617;">⚠ <?php _e('No H1 heading found', 'bfseo'); ?></span>
            <?php endif; ?>
          </p>
          <p class="description">
            <?php 
            printf(
              __('Headings: H1=%d, H2=%d, H3=%d, H4=%d, H5=%d, H6=%d', 'bfseo'),
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
            ⚠ <?php _e('Hierarchy warnings:', 'bfseo'); ?>
            <?php foreach ($headings['hierarchy_issues'] as $issue): ?>
              <br>• <?php echo esc_html($issue); ?>
            <?php endforeach; ?>
          </p>
          <?php endif; ?>
          <?php if (!empty($headings['headings'])): ?>
          <details style="margin-top: 10px;">
            <summary><?php _e('View all headings', 'bfseo'); ?></summary>
            <ul style="margin-top: 5px;">
              <?php foreach ($headings['headings'] as $h): ?>
                <li><strong><?php echo esc_html($h['tag']); ?>:</strong> <?php echo esc_html($h['text']); ?></li>
              <?php endforeach; ?>
            </ul>
          </details>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <p class="description" style="color: #d63638;">✗ <?php _e('Could not analyze heading structure', 'bfseo'); ?></p>
      <?php endif; ?>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Google, Bing</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Helps search engines understand content hierarchy', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('Indirect (weak-medium) - Supports content understanding', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> XPath <code>//h1, //h2, //h3, //h4, //h5, //h6</code></p>
          <p><strong><?php _e('NOTE:', 'bfseo'); ?></strong> <?php _e('Hierarchy issues are warnings, not errors. Perfect hierarchy is ideal but not critical.', 'bfseo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Image Analysis -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Image Optimization', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engines: Google Images', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge($images && $images['with_alt'] > 0 ? 1 : 0); ?>
      </div>
      <?php if ($images): ?>
        <div class="gscseo-check-details">
          <p class="description">
            <?php printf(__('Total images: %d', 'bfseo'), $images['total']); ?>
            <br>
            <?php printf(__('Images with alt text: %d', 'bfseo'), $images['with_alt']); ?>
            <?php if ($images['total'] > 0): ?>
              (<?php echo round(($images['with_alt'] / $images['total']) * 100); ?>%)
            <?php endif; ?>
            <?php if ($images['without_alt'] > 0): ?>
              <br><span style="color: #dba617;">⚠ <?php printf(__('%d images missing alt text', 'bfseo'), $images['without_alt']); ?></span>
            <?php endif; ?>
          </p>
          <?php if (!empty($images['size_warnings'])): ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php _e('Image size warnings:', 'bfseo'); ?>
            <?php foreach ($images['size_warnings'] as $warning): ?>
              <br>• <?php echo esc_html($warning); ?>
            <?php endforeach; ?>
          </p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <p class="description">ℹ <?php _e('No images found on this page', 'bfseo'); ?></p>
      <?php endif; ?>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Google Images</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Alt text helps search engines understand image content', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('Indirect (medium for image search, accessibility)', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> XPath <code>//img</code>, <?php _e('check @alt attribute', 'bfseo'); ?></p>
          <p><strong><?php _e('RECOMMENDED:', 'bfseo'); ?></strong> <?php _e('Social media images (OG, Twitter) should be under 300KB', 'bfseo'); ?></p>
        </div>
      </details>
    </div>
    
    <!-- Internal Links -->
    <div class="gscseo-check-item">
      <div class="gscseo-check-header">
        <strong><?php _e('Internal & External Links', 'bfseo'); ?></strong>
        <span class="gscseo-engine-scope"><?php _e('Engines: Google, Bing', 'bfseo'); ?></span>
        <?php echo GSCSEO_Validation::get_status_badge($links && ($links['internal'] > 0 || $links['external'] > 0) ? 1 : 0); ?>
      </div>
      <?php if ($links): ?>
        <div class="gscseo-check-details">
          <p class="description">
            <?php printf(__('Internal links: %d', 'bfseo'), $links['internal']); ?>
            <br>
            <?php printf(__('External links: %d', 'bfseo'), $links['external']); ?>
          </p>
          <?php if ($links['internal'] === 0): ?>
          <p class="description" style="color: #dba617;">
            ⚠ <?php _e('No internal links found. Consider adding links to related content.', 'bfseo'); ?>
          </p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <p class="description">ℹ <?php _e('No links found on this page', 'bfseo'); ?></p>
      <?php endif; ?>
      <details class="gscseo-technical-details">
        <summary><?php _e('▼ Show technical details', 'bfseo'); ?></summary>
        <div class="gscseo-tech-box">
          <p><strong><?php _e('ENGINE SCOPE:', 'bfseo'); ?></strong> Google, Bing</p>
          <p><strong><?php _e('PURPOSE:', 'bfseo'); ?></strong> <?php _e('Internal links help with site navigation and PageRank distribution', 'bfseo'); ?></p>
          <p><strong><?php _e('IMPACT:', 'bfseo'); ?></strong> <?php _e('Indirect (medium) - Supports crawlability and authority flow', 'bfseo'); ?></p>
          <p><strong><?php _e('VALIDATION:', 'bfseo'); ?></strong> XPath <code>//a[@href]</code>, <?php _e('compare domain', 'bfseo'); ?></p>
        </div>
      </details>
    </div>
  </div>
</div>
