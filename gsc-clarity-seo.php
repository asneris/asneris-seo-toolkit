<?php
/**
 * Plugin Name: Clarity-First SEO
 * Plugin URI: https://clarityfirstseo.com
 * Description: Professional SEO plugin with modern UI, designed for clarity and simplicity. Features intuitive tabbed interface, media uploader, SEO score calculator, and comprehensive schema markup.
 * Version: 0.2.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Clarity-First SEO
 * Author URI: https://clarityfirstseo.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bfseo
 * Domain Path: /languages
 * 
 * @package Clarity_First_SEO
 * @version 0.2.0
 */

if (!defined('ABSPATH')) exit;

// Plugin constants
define('GSCSEO_VERSION', '0.2.0');
define('GSCSEO_DIR', plugin_dir_path(__FILE__));
define('GSCSEO_URL', plugin_dir_url(__FILE__));
define('GSCSEO_BASENAME', plugin_basename(__FILE__));

require_once GSCSEO_DIR . 'includes/class-meta.php';
require_once GSCSEO_DIR . 'includes/class-render.php';
require_once GSCSEO_DIR . 'includes/class-schema.php';
require_once GSCSEO_DIR . 'includes/class-admin-settings.php';
require_once GSCSEO_DIR . 'includes/class-indexnow.php';
require_once GSCSEO_DIR . 'includes/class-conflict-detector.php';
require_once GSCSEO_DIR . 'includes/class-sitemap-helper.php';
require_once GSCSEO_DIR . 'includes/class-templates.php';
require_once GSCSEO_DIR . 'includes/class-bulk-edit.php';
require_once GSCSEO_DIR . 'includes/class-redirects.php';
require_once GSCSEO_DIR . 'includes/class-validation.php';

add_action('init', function () {
  GSCSEO_Meta::register_post_meta();
  GSCSEO_IndexNow::register_rewrite();
  GSCSEO_Redirects::init();
});

add_action('admin_menu', function () {
  // Create top-level menu (first submenu will be "Settings")
  add_menu_page(
    __('Clarity-First SEO', 'bfseo'),
    __('Clarity-First SEO', 'bfseo'),
    'manage_options',
    'clarity-first-seo',
    [GSCSEO_Admin_Settings::class, 'render_page'],
    'dashicons-chart-line',
    30
  );
  
  // Rename the first submenu item from "Clarity-First SEO" to "Settings"
  add_submenu_page(
    'clarity-first-seo',
    __('Settings', 'bfseo'),
    __('Settings', 'bfseo'),
    'manage_options',
    'clarity-first-seo',
    [GSCSEO_Admin_Settings::class, 'render_page']
  );
  
  // Register other submenus
  GSCSEO_Validation::register_menu();
  GSCSEO_Bulk_Edit::register_menu();
  GSCSEO_Redirects::register_menu();
});

add_action('admin_init', function () {
  GSCSEO_Admin_Settings::register_settings();
});

add_action('admin_enqueue_scripts', function ($hook) {
  GSCSEO_Admin_Settings::enqueue_admin_assets($hook);
  GSCSEO_Bulk_Edit::enqueue_assets($hook);
  GSCSEO_Validation::enqueue_assets($hook);
  GSCSEO_Redirects::enqueue_assets($hook);
});

add_action('enqueue_block_editor_assets', function () {
  $asset_path = GSCSEO_DIR . 'build/index.asset.php';
  if (!file_exists($asset_path)) return;
  $asset = include $asset_path;

  wp_enqueue_script(
    'gscseo-editor',
    GSCSEO_URL . 'build/index.js',
    $asset['dependencies'],
    $asset['version'],
    true
  );

  wp_localize_script('gscseo-editor', 'gscseo_indexnow_nonce', wp_create_nonce('gscseo_manual_indexnow'));
});

add_action('wp_head', function () {
  GSCSEO_Render::render_meta_tags();
  GSCSEO_Schema::render_jsonld();
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
  $seo_title = get_post_meta($id, '_gscseo_title', true);
  
  if (!empty($seo_title)) {
    return $seo_title;
  }
  
  // Try template
  $template_title = GSCSEO_Templates::generate_title($post);
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
  if (!($post instanceof WP_Post)) return;
  if ($new_status !== 'publish') return;
  if (wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID)) return;

  $ptype = get_post_type_object($post->post_type);
  if (!$ptype || empty($ptype->public)) return;

  // Throttle: avoid repeated submissions within 10 minutes for same post
  $last = (int) get_post_meta($post->ID, '_gscseo_indexnow_last', true);
  if ($last && (time() - $last) < 600) return;

  update_post_meta($post->ID, '_gscseo_indexnow_last', time());
  GSCSEO_IndexNow::submit_url(get_permalink($post->ID));
}, 10, 3);

add_action('before_delete_post', function ($post_id) {
  $post = get_post($post_id);
  if (!$post) return;
  if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;

  $ptype = get_post_type_object($post->post_type);
  if (!$ptype || empty($ptype->public)) return;

  $url = get_permalink($post_id);
  if ($url) {
    GSCSEO_IndexNow::submit_url($url);
  }
});

// AJAX handlers for admin settings
add_action('wp_ajax_gscseo_export_settings', ['GSCSEO_Admin_Settings', 'ajax_export_settings']);
add_action('wp_ajax_gscseo_import_settings', ['GSCSEO_Admin_Settings', 'ajax_import_settings']);
add_action('wp_ajax_gscseo_reset_settings', ['GSCSEO_Admin_Settings', 'ajax_reset_settings']);
add_action('wp_ajax_gscseo_manual_indexnow', ['GSCSEO_IndexNow', 'ajax_manual_submit']);
add_action('wp_ajax_gscseo_bulk_save', ['GSCSEO_Bulk_Edit', 'ajax_bulk_save']);

// Admin notices
add_action('admin_notices', function() {
  // Check if we're on the plugin's settings page
  if (isset($_GET['page']) && $_GET['page'] === 'gscseo-settings') {
    GSCSEO_Conflict_Detector::admin_notice();
  }
});

