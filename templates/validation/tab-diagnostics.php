<?php
/**
 * Site Diagnostics Tab
 */
if (!defined('ABSPATH')) exit;

// Get diagnostic data
$ASNERISSEO_sitemap_status = ASNERISSEO_Validation::check_sitemap_visibility();
$ASNERISSEO_duplicate_status = ASNERISSEO_Validation::detect_duplicate_outputs();
$ASNERISSEO_has_issues = !empty($ASNERISSEO_duplicate_status['active_plugins']) || !empty($ASNERISSEO_duplicate_status['duplicates']);
?>

<!-- Sitemap Visibility -->
<div class="ASNERISSEO-card">
  <h2>
    <span class="dashicons dashicons-networking"></span> Can search engines easily find your pages?
    <?php ASNERISSEO_Help_Modal::render_help_icon('sitemap-discovery', 'Learn about sitemap discovery'); ?>
  </h2>
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
        <td><?php echo $ASNERISSEO_sitemap_status['found'] ? '<span style="color: #46b450;">✓ Pass</span>' : '<span style="color: #dc3232;">✗ Issue</span>'; ?></td>
        <td><?php echo $ASNERISSEO_sitemap_status['found'] ? 'Found: ' : 'Not Found: '; echo esc_html($ASNERISSEO_sitemap_status['url']); ?></td>
      </tr>
      <tr>
        <td><strong>HTTP Status</strong></td>
        <td><?php echo $ASNERISSEO_sitemap_status['http_status'] === 200 ? '<span style="color: #46b450;">✓ Pass</span>' : '<span style="color: #dc3232;">✗ Issue</span>'; ?></td>
        <td><?php echo $ASNERISSEO_sitemap_status['http_status'] === 200 ? 'HTTP ' . esc_html($ASNERISSEO_sitemap_status['http_status']) . ' - ' . esc_html($ASNERISSEO_sitemap_status['http_message']) : 'Sitemap URL could not be checked'; ?></td>
      </tr>
      <tr>
        <td><strong>Robots.txt Reference</strong></td>
        <td><?php echo $ASNERISSEO_sitemap_status['in_robots'] ? '<span style="color: #46b450;">✓ Pass</span>' : '<span style="color: #f0ad4e;">⚠ Warning</span>'; ?></td>
        <td><?php echo $ASNERISSEO_sitemap_status['in_robots'] ? 'Referenced in robots.txt - ' : 'Not found in robots.txt - '; echo esc_html($ASNERISSEO_sitemap_status['robots_message']); ?></td>
      </tr>
      <tr>
        <td><strong>Controlled By</strong></td>
        <td><span style="color: #46b450;">✓ Pass</span></td>
        <td>WordPress automatically manages your sitemap.</td>
      </tr>
    </tbody>
  </table>
</div>

<!-- Duplicate Output Detector -->
<div class="ASNERISSEO-card">
  <h2>
    <span class="dashicons dashicons-yes"></span> Is your site sending clear, single signals?
    <?php ASNERISSEO_Help_Modal::render_help_icon('duplicate-signals', 'Learn about duplicate signals'); ?>
  </h2>
  <?php if ($ASNERISSEO_has_issues): ?>
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
        <td><?php echo empty($ASNERISSEO_duplicate_status['active_plugins']) ? '<span style="color: #46b450;">✓ Pass</span>' : '<span style="color: #dc3232;">✗ Issue</span>'; ?></td>
        <td><?php echo empty($ASNERISSEO_duplicate_status['active_plugins']) ? 'Only this plugin - No conflicts' : 'Multiple detected: ' . esc_html(implode(', ', $ASNERISSEO_duplicate_status['active_plugins'])); ?></td>
      </tr>
      <?php foreach (['title', 'description', 'canonical', 'robots', 'schema'] as $type): ?>
        <tr>
          <td><strong><?php echo esc_html(ucfirst($type)); ?> Tags</strong></td>
          <td><?php echo empty($ASNERISSEO_duplicate_status['duplicates'][$type]) ? '<span style="color: #46b450;">✓ Pass</span>' : '<span style="color: #dc3232;">✗ Issue</span>'; ?></td>
          <td><?php echo empty($ASNERISSEO_duplicate_status['duplicates'][$type]) ? 'Single output - No duplicates' : 'Duplicate found: ' . esc_html($ASNERISSEO_duplicate_status['duplicates'][$type]); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Indexing Safety (Patterns) -->
<div class="ASNERISSEO-card">
  <h2>
    <span class="dashicons dashicons-shield"></span> Is anything blocking your site from search results?
    <?php ASNERISSEO_Help_Modal::render_help_icon('indexing-blocks', 'Learn about indexing blocks'); ?>
  </h2>
  <?php
  // Check for site-wide indexing safety patterns
  $ASNERISSEO_indexing_warnings = [];
  
  // 1. Check if site is set to discourage search engines (global noindex)
  if (get_option('blog_public') == '0') {
    $ASNERISSEO_indexing_warnings[] = [
      'check' => 'Global Noindex',
      'status' => 'conflict',
      'details' => '❌ Site is set to discourage search engines (Settings → Reading)',
      'why' => 'This prevents all pages from being indexed'
    ];
  } else {
    $ASNERISSEO_indexing_warnings[] = [
      'check' => 'Global Noindex',
      'status' => 'pass',
      'details' => '✅ No global noindex detected'
    ];
  }
  
  // 2. Check robots.txt for blocks affecting large sections
  $ASNERISSEO_robots_url = home_url('/robots.txt');
  $ASNERISSEO_robots_response = wp_remote_get($ASNERISSEO_robots_url, ['timeout' => 5, 'sslverify' => false]);
  if (!is_wp_error($ASNERISSEO_robots_response) && wp_remote_retrieve_response_code($ASNERISSEO_robots_response) === 200) {
    $ASNERISSEO_robots_content = wp_remote_retrieve_body($ASNERISSEO_robots_response);
    $ASNERISSEO_blocked_sections = [];
    
    // Check for common large-section blocks
    if (preg_match('/Disallow:\s*\/\s*$/m', $ASNERISSEO_robots_content)) {
      $ASNERISSEO_blocked_sections[] = 'entire site';
    }
    if (stripos($ASNERISSEO_robots_content, 'Disallow: /wp-content') !== false) {
      $ASNERISSEO_blocked_sections[] = '/wp-content';
    }
    if (stripos($ASNERISSEO_robots_content, 'Disallow: /category') !== false) {
      $ASNERISSEO_blocked_sections[] = '/category';
    }
    if (stripos($ASNERISSEO_robots_content, 'Disallow: /tag') !== false) {
      $ASNERISSEO_blocked_sections[] = '/tag';
    }
    
    if (!empty($ASNERISSEO_blocked_sections)) {
      $ASNERISSEO_indexing_warnings[] = [
        'check' => 'Robots.txt Large Blocks',
        'status' => 'warning',
        'details' => '⚠️ Robots.txt blocks: ' . implode(', ', $ASNERISSEO_blocked_sections),
        'why' => 'These rules may prevent crawling of large sections'
      ];
    } else {
      $ASNERISSEO_indexing_warnings[] = [
        'check' => 'Robots.txt Large Blocks',
        'status' => 'pass',
        'details' => '✅ No large-section blocks in robots.txt'
      ];
    }
  } else {
    $ASNERISSEO_indexing_warnings[] = [
      'check' => 'Robots.txt Large Blocks',
      'status' => 'pass',
      'details' => '✅ No robots.txt or accessible'
    ];
  }
  
  // 3. Check sitemap URLs returning non-200
  $ASNERISSEO_sitemap_check = ASNERISSEO_Validation::check_sitemap_visibility();
  if ($ASNERISSEO_sitemap_check['found'] && $ASNERISSEO_sitemap_check['http_status'] !== 200) {
    $ASNERISSEO_indexing_warnings[] = [
      'check' => 'Sitemap Accessibility',
      'status' => 'warning',
      'details' => '⚠️ Sitemap returns HTTP ' . $ASNERISSEO_sitemap_check['http_status'],
      'why' => 'Search engines cannot access your sitemap for URL discovery'
    ];
  } else if ($ASNERISSEO_sitemap_check['found']) {
    $ASNERISSEO_indexing_warnings[] = [
      'check' => 'Sitemap Accessibility',
      'status' => 'pass',
      'details' => '✅ Sitemap accessible (HTTP 200)'
    ];
  } else {
    $ASNERISSEO_indexing_warnings[] = [
      'check' => 'Sitemap Accessibility',
      'status' => 'pass',
      'details' => '✅ No sitemap configured'
    ];
  }
  
  // 4. Check for redirect chains (sample homepage and a few posts)
  $ASNERISSEO_test_urls = [home_url('/')];
  $ASNERISSEO_recent_posts = get_posts(['numberposts' => 3, 'post_status' => 'publish']);
  foreach ($ASNERISSEO_recent_posts as $post) {
    $ASNERISSEO_test_urls[] = get_permalink($post->ID);
  }
  
  $ASNERISSEO_redirect_chains = 0;
  foreach ($ASNERISSEO_test_urls as $ASNERISSEO_url) {
    $ASNERISSEO_response = wp_remote_head($ASNERISSEO_url, ['timeout' => 5, 'redirection' => 0, 'sslverify' => false]);
    if (!is_wp_error($ASNERISSEO_response)) {
      $ASNERISSEO_code = wp_remote_retrieve_response_code($ASNERISSEO_response);
      if (in_array($ASNERISSEO_code, [301, 302, 307, 308])) {
        $ASNERISSEO_redirect_chains++;
      }
    }
  }
  
  if ($ASNERISSEO_redirect_chains > 0) {
    $ASNERISSEO_indexing_warnings[] = [
      'check' => 'Redirect Chains',
      'status' => 'warning',
      'details' => '⚠️ ' . $ASNERISSEO_redirect_chains . ' of ' . count($ASNERISSEO_test_urls) . ' sampled URLs redirect',
      'why' => 'Repeated redirects can delay or prevent indexing'
    ];
  } else {
    $ASNERISSEO_indexing_warnings[] = [
      'check' => 'Redirect Chains',
      'status' => 'pass',
      'details' => '✅ No redirects detected in sample'
    ];
  }
  
  // Determine overall status
  $ASNERISSEO_has_conflicts = false;
  $ASNERISSEO_has_warnings = false;
  foreach ($ASNERISSEO_indexing_warnings as $ASNERISSEO_item) {
    if ($ASNERISSEO_item['status'] === 'conflict') {
      $ASNERISSEO_has_conflicts = true;
      break;
    }
    if ($ASNERISSEO_item['status'] === 'warning') {
      $ASNERISSEO_has_warnings = true;
    }
  }
  ?>
  
  <?php if ($ASNERISSEO_has_conflicts): ?>
    <div style="background: #f8d7da; border-left: 4px solid #dc3232; padding: 12px; margin-bottom: 15px;">
      <strong>❌ Search engines are blocked from indexing your site</strong>
      <p style="margin: 5px 0 0 0;">Your site has settings that prevent search engine indexing.</p>
    </div>
  <?php elseif ($ASNERISSEO_has_warnings): ?>
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin-bottom: 15px;">
      <strong>⚠️ Some URLs blocked by robots.txt</strong>
      <p style="margin: 5px 0 0 0;">Review these patterns to ensure they match your intent.</p>
    </div>
  <?php else: ?>
    <div style="background: #d4edda; border-left: 4px solid #46b450; padding: 12px; margin-bottom: 15px;">
      <strong>✅ No site-wide indexing blocks detected</strong>
      <p style="margin: 5px 0 0 0;">No patterns that prevent indexing were found.</p>
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
      <?php foreach ($ASNERISSEO_indexing_warnings as $ASNERISSEO_item): ?>
        <tr>
          <td><strong><?php echo esc_html($ASNERISSEO_item['check']); ?></strong></td>
          <td>
            <?php 
            if ($ASNERISSEO_item['status'] === 'pass') {
              echo '<span style="color: #46b450;">✓ Pass</span>';
            } elseif ($ASNERISSEO_item['status'] === 'warning') {
              echo '<span style="color: #f0ad4e;">⚠ Warning</span>';
            } elseif ($ASNERISSEO_item['status'] === 'conflict') {
              echo '<span style="color: #dc3232;">✗ Issue</span>';
            }
            ?>
          </td>
          <td>
            <?php echo esc_html($ASNERISSEO_item['details']); ?>
            <?php if (isset($ASNERISSEO_item['why'])): ?>
              <br><span style="color: #646970; font-size: 13px;"><?php echo esc_html($ASNERISSEO_item['why']); ?></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Canonical Consistency (Patterns) -->
<div class="ASNERISSEO-card">
  <h2>
    <span class="dashicons dashicons-admin-links"></span> Do pages clearly identify their main URL?
    <?php ASNERISSEO_Help_Modal::render_help_icon('canonical-consistency', 'Learn about canonical URLs'); ?>
  </h2>
  <?php
  // Check canonical patterns across the site
  global $wpdb;
  $ASNERISSEO_canonical_checks = [];
  $ASNERISSEO_home_url_normalized = untrailingslashit(strtolower(home_url('/')));
  
  // 1. Detect pages canonicalizing to homepage with caching
  $ASNERISSEO_cache_key = 'ASNERISSEO_pages_to_home_' . md5($ASNERISSEO_home_url_normalized);
  $ASNERISSEO_pages_to_home = wp_cache_get($ASNERISSEO_cache_key, 'ASNERISSEO_diagnostics');
  
  if (false === $ASNERISSEO_pages_to_home) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching implemented with wp_cache_get/set above
    $ASNERISSEO_pages_to_home = $wpdb->get_var($wpdb->prepare("
      SELECT COUNT(*) 
      FROM {$wpdb->postmeta} pm
      INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
      WHERE pm.meta_key = '_ASNERISSEO_canonical'
      AND LOWER(TRIM(TRAILING '/' FROM pm.meta_value)) = %s
      AND p.post_status = 'publish'
      AND p.ID != %d
    ", $ASNERISSEO_home_url_normalized, get_option('page_on_front')));
    wp_cache_set($ASNERISSEO_cache_key, $ASNERISSEO_pages_to_home, 'ASNERISSEO_diagnostics', 300);
  }
  
  if ($ASNERISSEO_pages_to_home > 5) {
    $ASNERISSEO_canonical_checks[] = [
      'check' => 'Pages Canonicalizing to Homepage',
      'status' => 'warning',
      'details' => '⚠️ ' . $ASNERISSEO_pages_to_home . ' pages point their canonical to homepage',
      'why' => 'This may indicate duplicate content or misconfiguration'
    ];
  } else if ($ASNERISSEO_pages_to_home > 0) {
    $ASNERISSEO_canonical_checks[] = [
      'check' => 'Pages Canonicalizing to Homepage',
      'status' => 'pass',
      'details' => '✅ ' . $ASNERISSEO_pages_to_home . ' pages (within normal range)'
    ];
  } else {
    $ASNERISSEO_canonical_checks[] = [
      'check' => 'Pages Canonicalizing to Homepage',
      'status' => 'pass',
      'details' => '✅ No pages canonicalize to homepage'
    ];
  }
  
  // 2. Detect canonical loops (pages canonicalizing to each other) with caching
  $ASNERISSEO_canonical_cache_key = 'ASNERISSEO_canonical_urls_check';
  $ASNERISSEO_canonical_urls = wp_cache_get($ASNERISSEO_canonical_cache_key, 'ASNERISSEO_diagnostics');
  
  if (false === $ASNERISSEO_canonical_urls) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching implemented with wp_cache_get/set above
    $ASNERISSEO_canonical_urls = $wpdb->get_results("
      SELECT p.ID, pm.meta_value as canonical_url, p.guid
      FROM {$wpdb->postmeta} pm
      INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE pm.meta_key = '_ASNERISSEO_canonical'
    AND pm.meta_value != ''
    AND p.post_status = 'publish'
    LIMIT 100
  ");
    wp_cache_set($ASNERISSEO_canonical_cache_key, $ASNERISSEO_canonical_urls, 'ASNERISSEO_diagnostics', 300);
  }
  
  $ASNERISSEO_loop_detected = false;
  $ASNERISSEO_canonical_map = [];
  foreach ($ASNERISSEO_canonical_urls as $ASNERISSEO_row) {
    $ASNERISSEO_canonical_map[$ASNERISSEO_row->ID] = untrailingslashit(strtolower($ASNERISSEO_row->canonical_url));
  }
  
  // Simple loop detection (A->B->A pattern)
  foreach ($ASNERISSEO_canonical_map as $post_id => $ASNERISSEO_canonical_url) {
    $ASNERISSEO_reverse_match = array_search(untrailingslashit(strtolower(get_permalink($post_id))), $ASNERISSEO_canonical_map);
    if ($ASNERISSEO_reverse_match && $ASNERISSEO_reverse_match != $post_id) {
      $ASNERISSEO_loop_detected = true;
      break;
    }
  }
  
  if ($ASNERISSEO_loop_detected) {
    $ASNERISSEO_canonical_checks[] = [
      'check' => 'Canonical Loops',
      'status' => 'conflict',
      'details' => '❌ Canonical loop detected (pages pointing to each other)',
      'why' => 'This creates ambiguity about which page is canonical'
    ];
  } else {
    $ASNERISSEO_canonical_checks[] = [
      'check' => 'Canonical Loops',
      'status' => 'pass',
      'details' => '✅ No canonical loops detected'
    ];
  }
  
  // 3. Detect canonicals pointing to redirected URLs (sample check)
  $ASNERISSEO_redirected_canonicals = 0;
  $ASNERISSEO_sampled_urls = array_slice($ASNERISSEO_canonical_urls, 0, 10);
  foreach ($ASNERISSEO_sampled_urls as $ASNERISSEO_row) {
    $ASNERISSEO_response = wp_remote_head($ASNERISSEO_row->canonical_url, ['timeout' => 3, 'redirection' => 0, 'sslverify' => false]);
    if (!is_wp_error($ASNERISSEO_response)) {
      $ASNERISSEO_code = wp_remote_retrieve_response_code($ASNERISSEO_response);
      if (in_array($ASNERISSEO_code, [301, 302, 307, 308])) {
        $ASNERISSEO_redirected_canonicals++;
      }
    }
  }
  
  if ($ASNERISSEO_redirected_canonicals > 0) {
    $ASNERISSEO_canonical_checks[] = [
      'check' => 'Canonicals to Redirected URLs',
      'status' => 'warning',
      'details' => '⚠️ ' . $ASNERISSEO_redirected_canonicals . ' of ' . count($ASNERISSEO_sampled_urls) . ' sampled canonicals redirect',
      'why' => 'Canonical URLs should point to the final destination'
    ];
  } else if (!empty($ASNERISSEO_sampled_urls)) {
    $ASNERISSEO_canonical_checks[] = [
      'check' => 'Canonicals to Redirected URLs',
      'status' => 'pass',
      'details' => '✅ Sampled canonicals point to final URLs'
    ];
  } else {
    $ASNERISSEO_canonical_checks[] = [
      'check' => 'Canonicals to Redirected URLs',
      'status' => 'pass',
      'details' => '✅ No custom canonicals to check'
    ];
  }
  
  // 4. Detect mixed protocol (http/https)
  $ASNERISSEO_site_protocol = wp_parse_url(home_url('/'), PHP_URL_SCHEME);
  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Simple count query, caching not needed
  $ASNERISSEO_mixed_protocol = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(*) 
    FROM {$wpdb->postmeta} pm
    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE pm.meta_key = '_ASNERISSEO_canonical'
    AND pm.meta_value LIKE %s
    AND p.post_status = 'publish'
  ", ($ASNERISSEO_site_protocol === 'https' ? 'http://%' : 'https://%')));
  
  if ($ASNERISSEO_mixed_protocol > 0) {
    $ASNERISSEO_canonical_checks[] = [
      'check' => 'Mixed Protocol (http/https)',
      'status' => 'warning',
      'details' => '⚠️ ' . $ASNERISSEO_mixed_protocol . ' canonicals use different protocol than site',
      'why' => 'All canonicals should use consistent protocol (https recommended)'
    ];
  } else {
    $ASNERISSEO_canonical_checks[] = [
      'check' => 'Mixed Protocol (http/https)',
      'status' => 'pass',
      'details' => '✅ Consistent protocol usage'
    ];
  }
  
  // 5. Detect missing canonicals on many pages
  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Simple count query, caching not needed
  $ASNERISSEO_total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('post', 'page')");
  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Simple count query, caching not needed
  $ASNERISSEO_posts_with_canonical = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ASNERISSEO_canonical' AND meta_value != ''");
  
  if ($ASNERISSEO_total_posts > 0 && $ASNERISSEO_posts_with_canonical > 0) {
    $ASNERISSEO_percentage_with = round(($ASNERISSEO_posts_with_canonical / $ASNERISSEO_total_posts) * 100);
    $ASNERISSEO_canonical_checks[] = [
      'check' => 'Custom Canonical Usage',
      'status' => 'pass',
      'details' => '✅ ' . $ASNERISSEO_posts_with_canonical . ' of ' . $ASNERISSEO_total_posts . ' posts (' . $ASNERISSEO_percentage_with . '%) have custom canonicals'
    ];
  } else {
    $ASNERISSEO_canonical_checks[] = [
      'check' => 'Custom Canonical Usage',
      'status' => 'pass',
      'details' => '✅ Using default WordPress permalinks as canonicals'
    ];
  }
  
  // Determine overall status
  $ASNERISSEO_has_conflicts = false;
  $ASNERISSEO_has_warnings = false;
  foreach ($ASNERISSEO_canonical_checks as $ASNERISSEO_item) {
    if ($ASNERISSEO_item['status'] === 'conflict') {
      $ASNERISSEO_has_conflicts = true;
      break;
    }
    if ($ASNERISSEO_item['status'] === 'warning') {
      $ASNERISSEO_has_warnings = true;
    }
  }
  ?>
  
  <?php if ($ASNERISSEO_has_conflicts): ?>
    <div style="background: #f8d7da; border-left: 4px solid #dc3232; padding: 12px; margin-bottom: 15px;">
      <strong>❌ Canonical loop detected</strong>
      <p style="margin: 5px 0 0 0;">Pages are pointing to each other creating circular references.</p>
    </div>
  <?php elseif ($ASNERISSEO_has_warnings): ?>
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin-bottom: 15px;">
      <strong>⚠️ Multiple pages canonicalize to homepage</strong>
      <p style="margin: 5px 0 0 0;">Review canonical patterns to ensure they match your intent.</p>
    </div>
  <?php else: ?>
    <div style="background: #d4edda; border-left: 4px solid #46b450; padding: 12px; margin-bottom: 15px;">
      <strong>✅ Pages clearly point to their main URL</strong>
      <p style="margin: 5px 0 0 0;">No structural issues detected with canonical URLs.</p>
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
      <?php foreach ($ASNERISSEO_canonical_checks as $ASNERISSEO_item): ?>
        <tr>
          <td><strong><?php echo esc_html($ASNERISSEO_item['check']); ?></strong></td>
          <td>
            <?php 
            if ($ASNERISSEO_item['status'] === 'pass') {
              echo '<span style="color: #46b450;">✓ Pass</span>';
            } elseif ($ASNERISSEO_item['status'] === 'warning') {
              echo '<span style="color: #f0ad4e;">⚠ Warning</span>';
            } elseif ($ASNERISSEO_item['status'] === 'conflict') {
              echo '<span style="color: #dc3232;">✗ Issue</span>';
            }
            ?>
          </td>
          <td>
            <?php echo esc_html($ASNERISSEO_item['details']); ?>
            <?php if (isset($ASNERISSEO_item['why'])): ?>
              <br><span style="color: #646970; font-size: 13px;"><?php echo esc_html($ASNERISSEO_item['why']); ?></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

