<?php
/**
 * Site Diagnostics Tab
 */
if (!defined('ABSPATH')) exit;

// Get diagnostic data
$sitemap_status = GSCSEO_Validation::check_sitemap_visibility();
$duplicate_status = GSCSEO_Validation::detect_duplicate_outputs();
$has_issues = !empty($duplicate_status['active_plugins']) || !empty($duplicate_status['duplicates']);
?>

<!-- Sitemap Visibility -->
<div class="gscseo-card">
  <h2><span class="dashicons dashicons-networking"></span> Sitemap Visibility</h2>
  <p style="margin-top: 0; color: #646970;">Validate existing sitemaps (we don't generate them)</p>
  <table class="widefat striped">
    <thead>
      <tr>
        <th>Check</th>
        <th>Status</th>
        <th>Details</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>Sitemap URL</strong></td>
        <td><?php echo $sitemap_status['found'] ? '<span style="color: #46b450;">✓ Found</span>' : '<span style="color: #dc3232;">✗ Not Found</span>'; ?></td>
        <td><?php echo esc_html($sitemap_status['url']); ?></td>
      </tr>
      <tr>
        <td><strong>HTTP Status</strong></td>
        <td><?php echo $sitemap_status['http_status'] === 200 ? '<span style="color: #46b450;">✓ 200 OK</span>' : '<span style="color: #dc3232;">✗ ' . esc_html($sitemap_status['http_status']) . '</span>'; ?></td>
        <td><?php echo esc_html($sitemap_status['http_message']); ?></td>
      </tr>
      <tr>
        <td><strong>Robots.txt Reference</strong></td>
        <td><?php echo $sitemap_status['in_robots'] ? '<span style="color: #46b450;">✓ Referenced</span>' : '<span style="color: #f0ad4e;">⚠ Not Found</span>'; ?></td>
        <td><?php echo esc_html($sitemap_status['robots_message']); ?></td>
      </tr>
      <tr>
        <td><strong>Controlled By</strong></td>
        <td colspan="2"><?php echo wp_kses_post($sitemap_status['controller']); ?></td>
      </tr>
    </tbody>
  </table>
</div>

<!-- Duplicate Output Detector -->
<div class="gscseo-card">
  <h2><span class="dashicons dashicons-warning"></span> Duplicate Output Detector</h2>
  <p style="margin-top: 0; color: #646970;">Detect multiple SEO plugins causing conflicts</p>
  <?php if ($has_issues): ?>
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin-bottom: 15px;">
      <strong>⚠️ Potential Conflicts Detected</strong>
      <p style="margin: 5px 0 0 0;">Multiple SEO plugins may be outputting duplicate meta tags.</p>
    </div>
  <?php else: ?>
    <div style="background: #d4edda; border-left: 4px solid #46b450; padding: 12px; margin-bottom: 15px;">
      <strong>✓ No Conflicts Detected</strong>
      <p style="margin: 5px 0 0 0;">Your site appears to be configured correctly.</p>
    </div>
  <?php endif; ?>
  
  <table class="widefat striped">
    <thead>
      <tr>
        <th>Check</th>
        <th>Status</th>
        <th>Details</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>Active SEO Plugins</strong></td>
        <td><?php echo empty($duplicate_status['active_plugins']) ? '<span style="color: #46b450;">✓ Only This Plugin</span>' : '<span style="color: #dc3232;">✗ Multiple Detected</span>'; ?></td>
        <td><?php echo empty($duplicate_status['active_plugins']) ? 'No conflicts' : esc_html(implode(', ', $duplicate_status['active_plugins'])); ?></td>
      </tr>
      <?php foreach (['title', 'description', 'canonical', 'robots', 'schema'] as $type): ?>
        <tr>
          <td><strong><?php echo ucfirst($type); ?> Tags</strong></td>
          <td><?php echo empty($duplicate_status['duplicates'][$type]) ? '<span style="color: #46b450;">✓ Single Output</span>' : '<span style="color: #dc3232;">✗ Duplicate Found</span>'; ?></td>
          <td><?php echo empty($duplicate_status['duplicates'][$type]) ? 'No duplicates' : esc_html($duplicate_status['duplicates'][$type]); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Indexing Validation -->
<div class="gscseo-card">
  <h2><span class="dashicons dashicons-performance"></span> Indexing Validation</h2>
  <p style="margin-top: 0; color: #646970;">Validate HTTP status, redirects, and indexability for any URL</p>
  <table class="form-table">
    <tr>
      <th scope="row"><label for="gscseo_test_url">Test URL</label></th>
      <td>
        <input type="url" id="gscseo_test_url" class="large-text" placeholder="<?php echo esc_url(home_url('/')); ?>page-to-test/" value="<?php echo esc_url(home_url('/')); ?>">
        <button type="button" class="button button-primary" id="gscseo_run_http_test">Run Indexing Validation</button>
        <p class="description">Test any URL for status code, redirects, canonical destination, and indexability</p>
      </td>
    </tr>
  </table>
  <div id="gscseo_http_results" style="display: none; margin-top: 15px;">
    <table class="widefat striped">
      <thead>
        <tr>
          <th>Check</th>
          <th>Result</th>
          <th>Details</th>
        </tr>
      </thead>
      <tbody id="gscseo_http_results_body"></tbody>
    </table>
  </div>
</div>
