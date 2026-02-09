<?php
/**
 * Plugin Name: Asneris SEO Toolkit
 * Plugin URI: https://asneris.com/asneris-seo-toolkit
 * Description: Asneris: The Systematic SEO Toolkit for WordPress with intuitive UI.
 * Version: 0.1.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Asneris
 * Author URI: https://asneris.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: asneris-seo-toolkit
 * Domain Path: /languages
 *
 * @package Asneris_SEO_Toolkit
 */

if (!defined('ABSPATH')) exit;

// Plugin constants
define('ASNERISSEO_VERSION', '0.1.0');
define('ASNERISSEO_DIR', plugin_dir_path(__FILE__));
define('ASNERISSEO_URL', plugin_dir_url(__FILE__));
define('ASNERISSEO_BASENAME', plugin_basename(__FILE__));
define('ASNERIS_TEXT_DOMAIN', 'asneris-seo-toolkit');
define('ASNERIS_MENU_SLUG', 'asneris-seo');


require_once ASNERISSEO_DIR . 'includes/class-meta.php';
require_once ASNERISSEO_DIR . 'includes/class-render.php';
require_once ASNERISSEO_DIR . 'includes/class-schema.php';
require_once ASNERISSEO_DIR . 'includes/class-admin-settings.php';
require_once ASNERISSEO_DIR . 'includes/class-dashboard.php';
require_once ASNERISSEO_DIR . 'includes/class-diagnostics-page.php';
require_once ASNERISSEO_DIR . 'includes/class-indexnow.php';
require_once ASNERISSEO_DIR . 'includes/class-conflict-detector.php';
require_once ASNERISSEO_DIR . 'includes/class-sitemap-helper.php';
require_once ASNERISSEO_DIR . 'includes/class-templates.php';
require_once ASNERISSEO_DIR . 'includes/class-bulk-edit.php';
require_once ASNERISSEO_DIR . 'includes/class-redirects.php';
require_once ASNERISSEO_DIR . 'includes/class-validation.php';
require_once ASNERISSEO_DIR . 'includes/class-diagnostics.php';
require_once ASNERISSEO_DIR . 'includes/class-robots.php';
require_once ASNERISSEO_DIR . 'includes/class-help.php';
require_once ASNERISSEO_DIR . 'includes/class-help-modal.php';
require_once ASNERISSEO_DIR . 'includes/class-migration.php';
require_once ASNERISSEO_DIR . 'includes/class-help-content.php';

add_action('init', function () {
  // Remove WordPress default SEO tags to prevent duplicates
  remove_action('wp_head', 'rel_canonical');
  remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
  remove_action('wp_head', 'wp_shortlink_wp_head', 10);
  remove_action('wp_head', 'wp_robots', 1); // Remove WordPress 5.7+ robots meta tag
  
  ASNERISSEO_Meta::register_post_meta();
  ASNERISSEO_IndexNow::register_rewrite();
  ASNERISSEO_Redirects::init();
  ASNERISSEO_Robots::init();
  ASNERISSEO_Help_Modal::init();
  
  // Run migrations
  ASNERISSEO_Migration::run();
});

add_action('admin_menu', function () {
  // Create top-level menu
  add_menu_page(
    __('Asneris SEO Toolkit', 'asneris-seo-toolkit'),
    __('Asneris SEO Toolkit', 'asneris-seo-toolkit'),
    'manage_options',
    ASNERIS_MENU_SLUG,
    [ASNERISSEO_Dashboard::class, 'render_page'],
    'dashicons-chart-line',
    30
  );
  
  // Register submenus in order
  // Dashboard (replaces default first submenu)
  add_submenu_page(
    ASNERIS_MENU_SLUG,
    __('Dashboard', 'asneris-seo-toolkit'),
    __('Dashboard', 'asneris-seo-toolkit'),
    'manage_options',
    ASNERIS_MENU_SLUG,
    [ASNERISSEO_Dashboard::class, 'render_page']
  );
  
  // Settings (configuration)
  add_submenu_page(
    ASNERIS_MENU_SLUG,
    __('Settings', 'asneris-seo-toolkit'),
    __('Settings', 'asneris-seo-toolkit'),
    'manage_options',
    ASNERIS_MENU_SLUG . '-settings',
    [ASNERISSEO_Admin_Settings::class, 'render_page']
  );
  
  // Diagnostics (read-only facts)
  ASNERISSEO_Diagnostics_Page::register_menu();
  
  // Validation (interpretation with status)
  ASNERISSEO_Validation::register_menu();
  
  // Redirects
  ASNERISSEO_Redirects::register_menu();
  
  // Robots.txt
  ASNERISSEO_Robots::register_menu();
  
  // Bulk Edit
  ASNERISSEO_Bulk_Edit::register_menu();
  
  // Help
  ASNERISSEO_Help::register_menu();
});

add_action('admin_init', function () {
  ASNERISSEO_Admin_Settings::register_settings();
});

add_action('admin_enqueue_scripts', function ($hook) {
  ASNERISSEO_Admin_Settings::enqueue_admin_assets($hook);
  ASNERISSEO_Dashboard::enqueue_assets($hook);
  ASNERISSEO_Diagnostics_Page::enqueue_assets($hook);
  ASNERISSEO_Validation::enqueue_assets($hook);
  ASNERISSEO_Bulk_Edit::enqueue_assets($hook);
  ASNERISSEO_Redirects::enqueue_assets($hook);
});

add_action('enqueue_block_editor_assets', function () {
  $asset_path = ASNERISSEO_DIR . 'build/index.asset.php';
  if (!file_exists($asset_path)) return;
  $asset = include $asset_path;

  wp_enqueue_script(
    'ASNERISSEO-editor',
    ASNERISSEO_URL . 'build/index.js',
    $asset['dependencies'],
    $asset['version'],
    true
  );

  wp_localize_script('ASNERISSEO-editor', 'gscseoData', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'indexnowNonce' => wp_create_nonce('ASNERISSEO_manual_indexnow')
  ]);
});

add_action('wp_head', function () {
  ASNERISSEO_Render::render_meta_tags();
  ASNERISSEO_Schema::render_jsonld();
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
  $seo_title = get_post_meta($id, '_ASNERISSEO_title', true);
  
  if (!empty($seo_title)) {
    return $seo_title;
  }
  
  // Try template
  $template_title = ASNERISSEO_Templates::generate_title($post);
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
  if (!ASNERISSEO_IndexNow::is_enabled()) return;
  
  if (!($post instanceof WP_Post)) return;
  if ($new_status !== 'publish') return;
  if (wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID)) return;

  $ptype = get_post_type_object($post->post_type);
  if (!$ptype || empty($ptype->public)) return;

  // Throttle: avoid repeated submissions within 10 minutes for same post
  $last = (int) get_post_meta($post->ID, '_ASNERISSEO_indexnow_last', true);
  if ($last && (time() - $last) < 600) return;

  update_post_meta($post->ID, '_ASNERISSEO_indexnow_last', time());
  ASNERISSEO_IndexNow::submit_url(get_permalink($post->ID));
}, 10, 3);

add_action('before_delete_post', function ($post_id) {
  // Only check for IndexNow if it's actually enabled
  if (!ASNERISSEO_IndexNow::is_enabled()) return;
  
  $post = get_post($post_id);
  if (!$post) return;
  if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;

  $ptype = get_post_type_object($post->post_type);
  if (!$ptype || empty($ptype->public)) return;

  $url = get_permalink($post_id);
  if ($url) {
    ASNERISSEO_IndexNow::submit_url($url);
  }
});

// AJAX handlers for admin settings
add_action('wp_ajax_ASNERISSEO_export_settings', ['ASNERISSEO_Admin_Settings', 'ajax_export_settings']);
add_action('wp_ajax_ASNERISSEO_import_settings', ['ASNERISSEO_Admin_Settings', 'ajax_import_settings']);
add_action('wp_ajax_ASNERISSEO_reset_settings', ['ASNERISSEO_Admin_Settings', 'ajax_reset_settings']);
add_action('wp_ajax_ASNERISSEO_http_test', ['ASNERISSEO_Diagnostics', 'ajax_http_test']);
add_action('wp_ajax_ASNERISSEO_manual_indexnow', ['ASNERISSEO_IndexNow', 'ajax_manual_submit']);
add_action('wp_ajax_ASNERISSEO_bulk_save', ['ASNERISSEO_Bulk_Edit', 'ajax_bulk_save']);

// Admin notices
add_action('admin_notices', function() {
  // Check if we're on the plugin's settings page
  if (isset($_GET['page']) && wp_verify_nonce(wp_create_nonce('admin_page_check'), 'admin_page_check') && sanitize_key($_GET['page']) === ASNERIS_MENU_SLUG . '-settings') {
    ASNERISSEO_Conflict_Detector::admin_notice();
  }
});

