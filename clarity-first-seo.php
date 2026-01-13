<?php
/**
 * Plugin Name: Clarity First SEO
 * Plugin URI: https://clarityfirstseo.com
 * Description: Clarity-first SEO tools for WordPress: site diagnostics, page diagnostics, robots.txt editor, redirects, verification codes, and optional IndexNow notifications. Focuses on factual output — no ranking promises.
 * Version: 0.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Clarity First SEO
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: clarity-first-seo
 * Domain Path: /languages
 *
 * @package Clarity_First_SEO
 */

if (!defined('ABSPATH')) exit;

// Plugin constants
define('CFSEO_VERSION', '0.0.1');
define('CFSEO_DIR', plugin_dir_path(__FILE__));
define('CFSEO_URL', plugin_dir_url(__FILE__));
define('CFSEO_BASENAME', plugin_basename(__FILE__));


require_once CFSEO_DIR . 'includes/class-meta.php';
require_once CFSEO_DIR . 'includes/class-render.php';
require_once CFSEO_DIR . 'includes/class-schema.php';
require_once CFSEO_DIR . 'includes/class-admin-settings.php';
require_once CFSEO_DIR . 'includes/class-dashboard.php';
require_once CFSEO_DIR . 'includes/class-diagnostics-page.php';
require_once CFSEO_DIR . 'includes/class-indexnow.php';
require_once CFSEO_DIR . 'includes/class-conflict-detector.php';
require_once CFSEO_DIR . 'includes/class-sitemap-helper.php';
require_once CFSEO_DIR . 'includes/class-templates.php';
require_once CFSEO_DIR . 'includes/class-bulk-edit.php';
require_once CFSEO_DIR . 'includes/class-redirects.php';
require_once CFSEO_DIR . 'includes/class-validation.php';
require_once CFSEO_DIR . 'includes/class-diagnostics.php';
require_once CFSEO_DIR . 'includes/class-robots.php';
require_once CFSEO_DIR . 'includes/class-help.php';
require_once CFSEO_DIR . 'includes/class-help-modal.php';
require_once CFSEO_DIR . 'includes/class-migration.php';
require_once CFSEO_DIR . 'includes/class-help-content.php';

add_action('init', function () {
  // Load text domain for translations
  load_plugin_textdomain(
    'clarity-first-seo',
    false,
    dirname(plugin_basename(__FILE__)) . '/languages'
  );
  
  CFSEO_Meta::register_post_meta();
  CFSEO_IndexNow::register_rewrite();
  CFSEO_Redirects::init();
  CFSEO_Robots::init();
  
  // Run migrations
  CFSEO_Migration::run();
});

add_action('admin_menu', function () {
  // Create top-level menu
  add_menu_page(
    __('Clarity-First SEO', 'clarity-first-seo'),
    __('Clarity-First SEO', 'clarity-first-seo'),
    'manage_options',
    'clarity-first-seo',
    [CFSEO_Dashboard::class, 'render_page'],
    'dashicons-chart-line',
    30
  );
  
  // Register submenus in order
  // Dashboard (replaces default first submenu)
  add_submenu_page(
    'clarity-first-seo',
    __('Dashboard', 'clarity-first-seo'),
    __('Dashboard', 'clarity-first-seo'),
    'manage_options',
    'clarity-first-seo',
    [CFSEO_Dashboard::class, 'render_page']
  );
  
  // Settings (configuration)
  add_submenu_page(
    'clarity-first-seo',
    __('Settings', 'clarity-first-seo'),
    __('Settings', 'clarity-first-seo'),
    'manage_options',
    'cfseo-settings',
    [CFSEO_Admin_Settings::class, 'render_page']
  );
  
  // Diagnostics (read-only facts)
  CFSEO_Diagnostics_Page::register_menu();
  
  // Validation (interpretation with status)
  CFSEO_Validation::register_menu();
  
  // Redirects
  CFSEO_Redirects::register_menu();
  
  // Robots.txt
  CFSEO_Robots::register_menu();
  
  // Bulk Edit
  CFSEO_Bulk_Edit::register_menu();
  
  // Help
  CFSEO_Help::register_menu();
});

add_action('admin_init', function () {
  CFSEO_Admin_Settings::register_settings();
});

add_action('admin_enqueue_scripts', function ($hook) {
  CFSEO_Admin_Settings::enqueue_admin_assets($hook);
  CFSEO_Dashboard::enqueue_assets($hook);
  CFSEO_Diagnostics_Page::enqueue_assets($hook);
  CFSEO_Validation::enqueue_assets($hook);
  CFSEO_Bulk_Edit::enqueue_assets($hook);
  CFSEO_Redirects::enqueue_assets($hook);
});

add_action('enqueue_block_editor_assets', function () {
  $asset_path = CFSEO_DIR . 'build/index.asset.php';
  if (!file_exists($asset_path)) return;
  $asset = include $asset_path;

  wp_enqueue_script(
    'cfseo-editor',
    CFSEO_URL . 'build/index.js',
    $asset['dependencies'],
    $asset['version'],
    true
  );

  wp_localize_script('cfseo-editor', 'gscseoData', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'indexnowNonce' => wp_create_nonce('CFSEO_manual_indexnow')
  ]);
});

add_action('wp_head', function () {
  CFSEO_Render::render_meta_tags();
  CFSEO_Schema::render_jsonld();
}, 1);

// Control the <title> tag output
add_filter('pre_get_document_title', function($title) {
  if (!is_singular()) {
    return $title;
  }
  
  $id = get_queried_object_id();
  if (!$id) {
    return $title;
  }
  
  $post = get_post($id);
  if (!$post) {
    return $title;
  }
  
  // Check for custom SEO title
  $seo_title = get_post_meta($id, '_CFSEO_title', true);
  
  if (!empty($seo_title)) {
    return $seo_title;
  }
  
  // Try template
  $template_title = CFSEO_Templates::generate_title($post);
  if (!empty($template_title)) {
    return $template_title;
  }
  
  // Fallback to default
  return $title;
}, 10);

/**
 * IndexNow: submit on publish/update + delete.
 * Implements POST to api.indexnow.org/IndexNow per Bing's IndexNow docs.
 */
add_action('transition_post_status', function ($new_status, $old_status, $post) {
  // Only check for IndexNow if it's actually enabled
  if (!CFSEO_IndexNow::is_enabled()) return;
  
  if (!($post instanceof WP_Post)) return;
  if ($new_status !== 'publish') return;
  if (wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID)) return;

  $ptype = get_post_type_object($post->post_type);
  if (!$ptype || empty($ptype->public)) return;

  // Throttle: avoid repeated submissions within 10 minutes for same post
  $last = (int) get_post_meta($post->ID, '_CFSEO_indexnow_last', true);
  if ($last && (time() - $last) < 600) return;

  update_post_meta($post->ID, '_CFSEO_indexnow_last', time());
  CFSEO_IndexNow::submit_url(get_permalink($post->ID));
}, 10, 3);

add_action('before_delete_post', function ($post_id) {
  // Only check for IndexNow if it's actually enabled
  if (!CFSEO_IndexNow::is_enabled()) return;
  
  $post = get_post($post_id);
  if (!$post) return;
  if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;

  $ptype = get_post_type_object($post->post_type);
  if (!$ptype || empty($ptype->public)) return;

  $url = get_permalink($post_id);
  if ($url) {
    CFSEO_IndexNow::submit_url($url);
  }
});

// AJAX handlers for admin settings
add_action('wp_ajax_CFSEO_export_settings', ['CFSEO_Admin_Settings', 'ajax_export_settings']);
add_action('wp_ajax_CFSEO_import_settings', ['CFSEO_Admin_Settings', 'ajax_import_settings']);
add_action('wp_ajax_CFSEO_reset_settings', ['CFSEO_Admin_Settings', 'ajax_reset_settings']);
add_action('wp_ajax_CFSEO_http_test', ['CFSEO_Diagnostics', 'ajax_http_test']);
add_action('wp_ajax_CFSEO_manual_indexnow', ['CFSEO_IndexNow', 'ajax_manual_submit']);
add_action('wp_ajax_CFSEO_bulk_save', ['CFSEO_Bulk_Edit', 'ajax_bulk_save']);

// Admin notices
add_action('admin_notices', function() {
  // Check if we're on the plugin's settings page
  if (isset($_GET['page']) && $_GET['page'] === 'cfseo-settings') {
    CFSEO_Conflict_Detector::admin_notice();
  }
});

