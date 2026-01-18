<?php
/**
 * Site Diagnostics Tab
 */
if (!defined('ABSPATH')) exit;

// Get diagnostic data
$cfseo_sitemap_status = CFSEO_Validation::check_sitemap_visibility();
$cfseo_duplicate_status = CFSEO_Validation::detect_duplicate_outputs();
$cfseo_has_issues = !empty($cfseo_duplicate_status['active_plugins']) || !empty($cfseo_duplicate_status['duplicates']);
?>

<!-- Sitemap Visibility -->
<div class="cfseo-card">
  <h2>
    <span class="dashicons dashicons-networking"></span> Can search engines easily find your pages?
    <?php CFSEO_Help_Modal::render_help_icon('sitemap-discovery', 'Learn about sitemap discovery'); ?>
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
        <td><?php echo $cfseo_sitemap_status['found'] ? '<span style="color: #46b450;">✓ Pass</span>' : '<span style="color: #dc3232;">✗ Issue</span>'; ?></td>
        <td><?php echo $cfseo_sitemap_status['found'] ? 'Found: ' : 'Not Found: '; echo esc_html($cfseo_sitemap_status['url']); ?></td>
      </tr>
      <tr>
        <td><strong>HTTP Status</strong></td>
        <td><?php echo $cfseo_sitemap_status['http_status'] === 200 ? '<span style="color: #46b450;">✓ Pass</span>' : '<span style="color: #dc3232;">✗ Issue</span>'; ?></td>
        <td><?php echo $cfseo_sitemap_status['http_status'] === 200 ? 'HTTP ' . esc_html($cfseo_sitemap_status['http_status']) . ' - ' . esc_html($cfseo_sitemap_status['http_message']) : 'Sitemap URL could not be checked'; ?></td>
      </tr>
      <tr>
        <td><strong>Robots.txt Reference</strong></td>
        <td><?php echo $cfseo_sitemap_status['in_robots'] ? '<span style="color: #46b450;">✓ Pass</span>' : '<span style="color: #f0ad4e;">⚠ Warning</span>'; ?></td>
        <td><?php echo $cfseo_sitemap_status['in_robots'] ? 'Referenced in robots.txt - ' : 'Not found in robots.txt - '; echo esc_html($cfseo_sitemap_status['robots_message']); ?></td>
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
<div class="cfseo-card">
  <h2>
    <span class="dashicons dashicons-yes"></span> Is your site sending clear, single signals?
    <?php CFSEO_Help_Modal::render_help_icon('duplicate-signals', 'Learn about duplicate signals'); ?>
  </h2>
  <?php if ($cfseo_has_issues): ?>
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
        <td><?php echo empty($cfseo_duplicate_status['active_plugins']) ? '<span style="color: #46b450;">✓ Pass</span>' : '<span style="color: #dc3232;">✗ Issue</span>'; ?></td>
        <td><?php echo empty($cfseo_duplicate_status['active_plugins']) ? 'Only this plugin - No conflicts' : 'Multiple detected: ' . esc_html(implode(', ', $cfseo_duplicate_status['active_plugins'])); ?></td>
      </tr>
      <?php foreach (['title', 'description', 'canonical', 'robots', 'schema'] as $type): ?>
        <tr>
          <td><strong><?php echo esc_html(ucfirst($type)); ?> Tags</strong></td>
          <td><?php echo empty($cfseo_duplicate_status['duplicates'][$type]) ? '<span style="color: #46b450;">✓ Pass</span>' : '<span style="color: #dc3232;">✗ Issue</span>'; ?></td>
          <td><?php echo empty($cfseo_duplicate_status['duplicates'][$type]) ? 'Single output - No duplicates' : 'Duplicate found: ' . esc_html($cfseo_duplicate_status['duplicates'][$type]); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Indexing Safety (Patterns) -->
<div class="cfseo-card">
  <h2>
    <span class="dashicons dashicons-shield"></span> Is anything blocking your site from search results?
    <?php CFSEO_Help_Modal::render_help_icon('indexing-blocks', 'Learn about indexing blocks'); ?>
  </h2>
  <?php
  // Check for site-wide indexing safety patterns
  $cfseo_indexing_warnings = [];
  
  // 1. Check if site is set to discourage search engines (global noindex)
  if (get_option('blog_public') == '0') {
    $cfseo_indexing_warnings[] = [
      'check' => 'Global Noindex',
      'status' => 'conflict',
      'details' => '❌ Site is set to discourage search engines (Settings → Reading)',
      'why' => 'This prevents all pages from being indexed'
    ];
  } else {
    $cfseo_indexing_warnings[] = [
      'check' => 'Global Noindex',
      'status' => 'pass',
      'details' => '✅ No global noindex detected'
    ];
  }
  
  // 2. Check robots.txt for blocks affecting large sections
  $cfseo_robots_url = home_url('/robots.txt');
  $cfseo_robots_response = wp_remote_get($cfseo_robots_url, ['timeout' => 5, 'sslverify' => false]);
  if (!is_wp_error($cfseo_robots_response) && wp_remote_retrieve_response_code($cfseo_robots_response) === 200) {
    $cfseo_robots_content = wp_remote_retrieve_body($cfseo_robots_response);
    $cfseo_blocked_sections = [];
    
    // Check for common large-section blocks
    if (preg_match('/Disallow:\s*\/\s*$/m', $robots_content)) {
      $cfseo_blocked_sections[] = 'entire site';
    }
    if (stripos($robots_content, 'Disallow: /wp-content') !== false) {
      $cfseo_blocked_sections[] = '/wp-content';
    }
    if (stripos($robots_content, 'Disallow: /category') !== false) {
      $cfseo_blocked_sections[] = '/category';
    }
    if (stripos($robots_content, 'Disallow: /tag') !== false) {
      $cfseo_blocked_sections[] = '/tag';
    }
    
    if (!empty($cfseo_blocked_sections)) {
      $cfseo_indexing_warnings[] = [
        'check' => 'Robots.txt Large Blocks',
        'status' => 'warning',
        'details' => '⚠️ Robots.txt blocks: ' . implode(', ', $cfseo_blocked_sections),
        'why' => 'These rules may prevent crawling of large sections'
      ];
    } else {
      $cfseo_indexing_warnings[] = [
        'check' => 'Robots.txt Large Blocks',
        'status' => 'pass',
        'details' => '✅ No large-section blocks in robots.txt'
      ];
    }
  } else {
    $cfseo_indexing_warnings[] = [
      'check' => 'Robots.txt Large Blocks',
      'status' => 'pass',
      'details' => '✅ No robots.txt or accessible'
    ];
  }
  
  // 3. Check sitemap URLs returning non-200
  $cfseo_sitemap_check = esc_html(CFSEO_Validation::check_sitemap_visibility();
  if ($cfseo_sitemap_check['found'] && $cfseo_sitemap_check['http_status'] !== 200) {
    $cfseo_indexing_warnings[] = [
      'check' => 'Sitemap Accessibility',
      'status' => 'warning',
      'details' => '⚠️ Sitemap returns HTTP ' . $cfseo_sitemap_check['http_status'],
      'why' => 'Search engines cannot access your sitemap for URL discovery'
    ];
  } else if ($cfseo_sitemap_check['found']) {
    $cfseo_indexing_warnings[] = [
      'check' => 'Sitemap Accessibility',
      'status' => 'pass',
      'details' => '✅ Sitemap accessible (HTTP 200)'
    ];
  } else {
    $cfseo_indexing_warnings[] = [
      'check' => 'Sitemap Accessibility',
      'status' => 'pass',
      'details' => '✅ No sitemap configured'
    ];
  }
  
  // 4. Check for redirect chains (sample homepage and a few posts)
  $cfseo_test_urls = [home_url('/')];
  $cfseo_recent_posts = get_posts(['numberposts' => 3, 'post_status' => 'publish']);
  foreach ($cfseo_recent_posts as $post) {
    $cfseo_test_urls[] = get_permalink($post->ID);
  }
  
  $cfseo_redirect_chains = 0;
  foreach ($cfseo_test_urls as $cfseo_url) {
    $cfseo_response = wp_remote_head($cfseo_url, ['timeout' => 5, 'redirection' => 0, 'sslverify' => false]);
    if (!is_wp_error($cfseo_response)) {
      $cfseo_code = wp_remote_retrieve_response_code($cfseo_response);
      if (in_array($cfseo_code, [301, 302, 307, 308])) {
        $cfseo_redirect_chains++;
      }
    }
  }
  
  if ($cfseo_redirect_chains > 0) {
    $cfseo_indexing_warnings[] = [
      'check' => 'Redirect Chains',
      'status' => 'warning',
      'details' => '⚠️ ' . $cfseo_redirect_chains . ' of ' . count($cfseo_test_urls) . ' sampled URLs redirect',
      'why' => 'Repeated redirects can delay or prevent indexing'
    ];
  } else {
    $cfseo_indexing_warnings[] = [
      'check' => 'Redirect Chains',
      'status' => 'pass',
      'details' => '✅ No redirects detected in sample'
    ];
  }
  
  // Determine overall status
  $cfseo_has_conflicts = false;
  $cfseo_has_warnings = false;
  foreach ($cfseo_indexing_warnings as $cfseo_item) {
    if ($cfseo_item['status'] === 'conflict') {
      $cfseo_has_conflicts = true;
      break;
    }
    if ($cfseo_item['status'] === 'warning') {
      $cfseo_has_warnings = true;
    }
  }
  ?>
  
  <?php if ($cfseo_has_conflicts): ?>
    <div style="background: #f8d7da; border-left: 4px solid #dc3232; padding: 12px; margin-bottom: 15px;">
      <strong>❌ Search engines are blocked from indexing your site</strong>
      <p style="margin: 5px 0 0 0;">Your site has settings that prevent search engine indexing.</p>
    </div>
  <?php elseif ($cfseo_has_warnings): ?>
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
      <?php foreach ($cfseo_indexing_warnings as $cfseo_item): ?>
        <tr>
          <td><strong><?php echo esc_html($cfseo_item['check']); ?></strong></td>
          <td>
            <?php 
            if ($cfseo_item['status'] === 'pass') {
              echo '<span style="color: #46b450;">✓ Pass</span>';
            } elseif ($cfseo_item['status'] === 'warning') {
              echo '<span style="color: #f0ad4e;">⚠ Warning</span>';
            } elseif ($cfseo_item['status'] === 'conflict') {
              echo '<span style="color: #dc3232;">✗ Issue</span>';
            }
            ?>
          </td>
          <td>
            <?php echo esc_html($cfseo_item['details']); ?>
            <?php if (isset($cfseo_item['why'])): ?>
              <br><span style="color: #646970; font-size: 13px;"><?php echo esc_html($cfseo_item['why']); ?></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Canonical Consistency (Patterns) -->
<div class="cfseo-card">
  <h2>
    <span class="dashicons dashicons-admin-links"></span> Do pages clearly identify their main URL?
    <?php CFSEO_Help_Modal::render_help_icon('canonical-consistency', 'Learn about canonical URLs'); ?>
  </h2>
  <?php
  // Check canonical patterns across the site
  global $wpdb;
  $cfseo_canonical_checks = [];
  $cfseo_home_url_normalized = untrailingslashit(strtolower(home_url('/')));
  
  // 1. Detect pages canonicalizing to homepage with caching
  $cfseo_cache_key = 'cfseo_pages_to_home_' . md5($cfseo_home_url_normalized);
  $cfseo_pages_to_home = wp_cache_get($cfseo_cache_key, 'cfseo_diagnostics');
  
  if (false === $cfseo_pages_to_home) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching implemented with wp_cache_get/set above
    $cfseo_pages_to_home = $wpdb->get_var($wpdb->prepare("
      SELECT COUNT(*) 
      FROM {$wpdb->postmeta} pm
      INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
      WHERE pm.meta_key = '_CFSEO_canonical'
      AND LOWER(TRIM(TRAILING '/' FROM pm.meta_value)) = %s
      AND p.post_status = 'publish'
      AND p.ID != %d
    ", $cfseo_home_url_normalized, get_option('page_on_front')));
    wp_cache_set($cfseo_cache_key, $cfseo_pages_to_home, 'cfseo_diagnostics', 300);
  }
  
  if ($cfseo_pages_to_home > 5) {
    $cfseo_canonical_checks[] = [
      'check' => 'Pages Canonicalizing to Homepage',
      'status' => 'warning',
      'details' => '⚠️ ' . $cfseo_pages_to_home . ' pages point their canonical to homepage',
      'why' => 'This may indicate duplicate content or misconfiguration'
    ];
  } else if ($cfseo_pages_to_home > 0) {
    $cfseo_canonical_checks[] = [
      'check' => 'Pages Canonicalizing to Homepage',
      'status' => 'pass',
      'details' => '✅ ' . $cfseo_pages_to_home . ' pages (within normal range)'
    ];
  } else {
    $cfseo_canonical_checks[] = [
      'check' => 'Pages Canonicalizing to Homepage',
      'status' => 'pass',
      'details' => '✅ No pages canonicalize to homepage'
    ];
  }
  
  // 2. Detect canonical loops (pages canonicalizing to each other) with caching
  $cfseo_canonical_cache_key = 'cfseo_canonical_urls_check';
  $cfseo_canonical_urls = wp_cache_get($cfseo_canonical_cache_key, 'cfseo_diagnostics');
  
  if (false === $cfseo_canonical_urls) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching implemented with wp_cache_get/set above
    $cfseo_canonical_urls = $wpdb->get_results("
      SELECT p.ID, pm.meta_value as canonical_url, p.guid
      FROM {$wpdb->postmeta} pm
      INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE pm.meta_key = '_CFSEO_canonical'
    AND pm.meta_value != ''
    AND p.post_status = 'publish'
    LIMIT 100
  ");
    wp_cache_set($cfseo_canonical_cache_key, $cfseo_canonical_urls, 'cfseo_diagnostics', 300);
  }
  
  $cfseo_loop_detected = false;
  $cfseo_canonical_map = [];
  foreach ($cfseo_canonical_urls as $cfseo_row) {
    $cfseo_canonical_map[$cfseo_row->ID] = untrailingslashit(strtolower($cfseo_row->canonical_url));
  }
  
  // Simple loop detection (A->B->A pattern)
  foreach ($cfseo_canonical_map as $post_id => $cfseo_canonical_url) {
    $cfseo_reverse_match = array_search(untrailingslashit(strtolower(get_permalink($post_id))), $cfseo_canonical_map);
    if ($cfseo_reverse_match && $cfseo_reverse_match != $post_id) {
      $cfseo_loop_detected = true;
      break;
    }
  }
  
  if ($cfseo_loop_detected) {
    $cfseo_canonical_checks[] = [
      'check' => 'Canonical Loops',
      'status' => 'conflict',
      'details' => '❌ Canonical loop detected (pages pointing to each other)',
      'why' => 'This creates ambiguity about which page is canonical'
    ];
  } else {
    $cfseo_canonical_checks[] = [
      'check' => 'Canonical Loops',
      'status' => 'pass',
      'details' => '✅ No canonical loops detected'
    ];
  }
  
  // 3. Detect canonicals pointing to redirected URLs (sample check)
  $cfseo_redirected_canonicals = 0;
  $cfseo_sampled_urls = array_slice($cfseo_canonical_urls, 0, 10);
  foreach ($cfseo_sampled_urls as $cfseo_row) {
    $cfseo_response = wp_remote_head($cfseo_row->canonical_url, ['timeout' => 3, 'redirection' => 0, 'sslverify' => false]);
    if (!is_wp_error($cfseo_response)) {
      $cfseo_code = wp_remote_retrieve_response_code($cfseo_response);
      if (in_array($cfseo_code, [301, 302, 307, 308])) {
        $cfseo_redirected_canonicals++;
      }
    }
  }
  
  if ($cfseo_redirected_canonicals > 0) {
    $cfseo_canonical_checks[] = [
      'check' => 'Canonicals to Redirected URLs',
      'status' => 'warning',
      'details' => '⚠️ ' . $cfseo_redirected_canonicals . ' of ' . count($cfseo_sampled_urls) . ' sampled canonicals redirect',
      'why' => 'Canonical URLs should point to the final destination'
    ];
  } else if (!empty($cfseo_sampled_urls)) {
    $cfseo_canonical_checks[] = [
      'check' => 'Canonicals to Redirected URLs',
      'status' => 'pass',
      'details' => '✅ Sampled canonicals point to final URLs'
    ];
  } else {
    $cfseo_canonical_checks[] = [
      'check' => 'Canonicals to Redirected URLs',
      'status' => 'pass',
      'details' => '✅ No custom canonicals to check'
    ];
  }
  
  // 4. Detect mixed protocol (http/https)
  $cfseo_site_protocol = wp_parse_url(home_url('/'), PHP_URL_SCHEME);
  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Simple count query, caching not needed
  $cfseo_mixed_protocol = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(*) 
    FROM {$wpdb->postmeta} pm
    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE pm.meta_key = '_CFSEO_canonical'
    AND pm.meta_value LIKE %s
    AND p.post_status = 'publish'
  ", ($cfseo_site_protocol === 'https' ? 'http://%' : 'https://%')));
  
  if ($cfseo_mixed_protocol > 0) {
    $cfseo_canonical_checks[] = [
      'check' => 'Mixed Protocol (http/https)',
      'status' => 'warning',
      'details' => '⚠️ ' . $cfseo_mixed_protocol . ' canonicals use different protocol than site',
      'why' => 'All canonicals should use consistent protocol (https recommended)'
    ];
  } else {
    $cfseo_canonical_checks[] = [
      'check' => 'Mixed Protocol (http/https)',
      'status' => 'pass',
      'details' => '✅ Consistent protocol usage'
    ];
  }
  
  // 5. Detect missing canonicals on many pages
  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Simple count query, caching not needed
  $cfseo_total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('post', 'page')");
  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Simple count query, caching not needed
  $cfseo_posts_with_canonical = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_CFSEO_canonical' AND meta_value != ''");
  
  if ($cfseo_total_posts > 0 && $cfseo_posts_with_canonical > 0) {
    $cfseo_percentage_with = round(($cfseo_posts_with_canonical / $cfseo_total_posts) * 100);
    $cfseo_canonical_checks[] = [
      'check' => 'Custom Canonical Usage',
      'status' => 'pass',
      'details' => '✅ ' . $cfseo_posts_with_canonical . ' of ' . $cfseo_total_posts . ' posts (' . $cfseo_percentage_with . '%) have custom canonicals'
    ];
  } else {
    $cfseo_canonical_checks[] = [
      'check' => 'Custom Canonical Usage',
      'status' => 'pass',
      'details' => '✅ Using default WordPress permalinks as canonicals'
    ];
  }
  
  // Determine overall status
  $cfseo_has_conflicts = false;
  $cfseo_has_warnings = false;
  foreach ($cfseo_canonical_checks as $cfseo_item) {
    if ($cfseo_item['status'] === 'conflict') {
      $cfseo_has_conflicts = true;
      break;
    }
    if ($cfseo_item['status'] === 'warning') {
      $cfseo_has_warnings = true;
    }
  }
  ?>
  
  <?php if ($cfseo_has_conflicts): ?>
    <div style="background: #f8d7da; border-left: 4px solid #dc3232; padding: 12px; margin-bottom: 15px;">
      <strong>❌ Canonical loop detected</strong>
      <p style="margin: 5px 0 0 0;">Pages are pointing to each other creating circular references.</p>
    </div>
  <?php elseif ($cfseo_has_warnings): ?>
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
      <?php foreach ($cfseo_canonical_checks as $cfseo_item): ?>
        <tr>
          <td><strong><?php echo esc_html($cfseo_item['check']); ?></strong></td>
          <td>
            <?php 
            if ($cfseo_item['status'] === 'pass') {
              echo '<span style="color: #46b450;">✓ Pass</span>';
            } elseif ($cfseo_item['status'] === 'warning') {
              echo '<span style="color: #f0ad4e;">⚠ Warning</span>';
            } elseif ($cfseo_item['status'] === 'conflict') {
              echo '<span style="color: #dc3232;">✗ Issue</span>';
            }
            ?>
          </td>
          <td>
            <?php echo esc_html($cfseo_item['details']); ?>
            <?php if (isset($cfseo_item['why'])): ?>
              <br><span style="color: #646970; font-size: 13px;"><?php echo esc_html($cfseo_item['why']); ?></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

