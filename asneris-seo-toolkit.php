<?php
/**
 * Plugin Name: Asneris SEO Toolkit
 * Plugin URI: https://asneris.com/asneris-seo-toolkit
 * Description: Asneris: The Systematic SEO Toolkit for WordPress with intuitive UI.
 * Version: 0.1.2
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
define('ASNERISSEO_VERSION', '0.1.2');
define('ASNERISSEO_DIR', plugin_dir_path(__FILE__));
define('ASNERISSEO_URL', plugin_dir_url(__FILE__));
define('ASNERISSEO_BASENAME', plugin_basename(__FILE__));
define('ASNERIS_MENU_SLUG', 'asneris-seo');

// Load all plugin classes
require_once ASNERISSEO_DIR . 'includes/class-meta.php';
require_once ASNERISSEO_DIR . 'includes/class-render.php';
require_once ASNERISSEO_DIR . 'includes/class-schema.php';
require_once ASNERISSEO_DIR . 'includes/class-redirects.php';
require_once ASNERISSEO_DIR . 'includes/class-indexnow.php';
require_once ASNERISSEO_DIR . 'includes/class-robots.php';
require_once ASNERISSEO_DIR . 'includes/class-help-modal.php';
require_once ASNERISSEO_DIR . 'includes/class-migration.php';
require_once ASNERISSEO_DIR . 'includes/class-admin-settings.php';
require_once ASNERISSEO_DIR . 'includes/class-dashboard.php';
require_once ASNERISSEO_DIR . 'includes/class-diagnostics-page.php';
require_once ASNERISSEO_DIR . 'includes/class-conflict-detector.php';
require_once ASNERISSEO_DIR . 'includes/class-sitemap-helper.php';
require_once ASNERISSEO_DIR . 'includes/class-templates.php';
require_once ASNERISSEO_DIR . 'includes/class-bulk-edit.php';
require_once ASNERISSEO_DIR . 'includes/class-validation.php';
require_once ASNERISSEO_DIR . 'includes/class-diagnostics.php';



/**
 * Bootstrap class — named static methods so hooks can be removed by third parties.
 *
 * @package Asneris_SEO_Toolkit
 */
class ASNERISSEO_Bootstrap {

  /**
   * Wire up all WordPress hooks.
   */
  public static function register_hooks() {
    add_action( 'init',                     [ __CLASS__, 'on_init' ] );
    add_action( 'admin_menu',               [ __CLASS__, 'on_admin_menu' ] );
    add_action( 'admin_init',               [ __CLASS__, 'on_admin_init' ] );
    add_action( 'admin_notices',            [ __CLASS__, 'on_admin_notices' ] );
    add_action( 'admin_enqueue_scripts',    [ __CLASS__, 'on_admin_enqueue_scripts' ] );
    add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'on_enqueue_block_editor_assets' ] );
    add_action( 'wp_head',                  [ __CLASS__, 'on_wp_head' ], 1 );
    add_action( 'transition_post_status',   [ __CLASS__, 'on_transition_post_status' ], 10, 3 );
    add_action( 'before_delete_post',       [ __CLASS__, 'on_before_delete_post' ] );

    add_filter( 'wp_robots',               [ 'ASNERISSEO_Render', 'filter_wp_robots' ], 20 );
    add_filter( 'pre_get_document_title',  [ __CLASS__, 'on_pre_get_document_title' ], 10 );

    // AJAX handlers
    add_action( 'wp_ajax_ASNERISSEO_export_settings',  [ 'ASNERISSEO_Admin_Settings', 'ajax_export_settings' ] );
    add_action( 'wp_ajax_ASNERISSEO_import_settings',  [ 'ASNERISSEO_Admin_Settings', 'ajax_import_settings' ] );
    add_action( 'wp_ajax_ASNERISSEO_reset_settings',   [ 'ASNERISSEO_Admin_Settings', 'ajax_reset_settings' ] );
    add_action( 'wp_ajax_ASNERISSEO_http_test',        [ 'ASNERISSEO_Diagnostics',    'ajax_http_test' ] );
    add_action( 'wp_ajax_ASNERISSEO_manual_indexnow',  [ 'ASNERISSEO_IndexNow',       'ajax_manual_submit' ] );
    add_action( 'wp_ajax_ASNERISSEO_bulk_save',        [ 'ASNERISSEO_Bulk_Edit',      'ajax_bulk_save' ] );
  }

  /** Fires on 'init'. */
  public static function on_init() {
    // Keep core canonical/robots behavior for non-singular contexts.
    // We only output custom canonical/robots on singular views in render layer.
    remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
    remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );

    ASNERISSEO_Meta::register_post_meta();
    ASNERISSEO_Meta::init();
    // IndexNow rewrite is registered on activation hook only (performance optimization)
    ASNERISSEO_Redirects::init();
    ASNERISSEO_Robots::init();
    ASNERISSEO_Help_Modal::init();

    // Run migrations (admin-only for performance)
    if ( is_admin() ) {
      ASNERISSEO_Migration::run();
    }
  }

  /** Fires on 'admin_menu'. */
  public static function on_admin_menu() {
    add_menu_page(
      __( 'Asneris SEO Toolkit', 'asneris-seo-toolkit' ),
      __( 'Asneris SEO Toolkit', 'asneris-seo-toolkit' ),
      'manage_options',
      ASNERIS_MENU_SLUG,
      [ 'ASNERISSEO_Dashboard', 'render_page' ],
      'dashicons-chart-line',
      30
    );

    // Dashboard (replaces default first submenu)
    add_submenu_page(
      ASNERIS_MENU_SLUG,
      __( 'Dashboard', 'asneris-seo-toolkit' ),
      __( 'Dashboard', 'asneris-seo-toolkit' ),
      'manage_options',
      ASNERIS_MENU_SLUG,
      [ 'ASNERISSEO_Dashboard', 'render_page' ]
    );

    // Settings
    add_submenu_page(
      ASNERIS_MENU_SLUG,
      __( 'Settings', 'asneris-seo-toolkit' ),
      __( 'Settings', 'asneris-seo-toolkit' ),
      'manage_options',
      ASNERIS_MENU_SLUG . '-settings',
      [ 'ASNERISSEO_Admin_Settings', 'render_page' ]
    );

    ASNERISSEO_Diagnostics_Page::register_menu();
    ASNERISSEO_Validation::register_menu();
    ASNERISSEO_Redirects::register_menu();
    ASNERISSEO_Robots::register_menu();
    ASNERISSEO_Bulk_Edit::register_menu();
  }

  /** Fires on 'admin_init'. */
  public static function on_admin_init() {
    ASNERISSEO_Admin_Settings::register_settings();
  }

  /** Fires on 'admin_notices'. */
  public static function on_admin_notices() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check on admin page slug, no data modification
    if ( isset( $_GET['page'] ) && sanitize_key( $_GET['page'] ) === ASNERIS_MENU_SLUG . '-settings' ) {
      ASNERISSEO_Conflict_Detector::admin_notice();
    }
  }

  /** Fires on 'admin_enqueue_scripts'. */
  public static function on_admin_enqueue_scripts( $hook ) {
    ASNERISSEO_Admin_Settings::enqueue_admin_assets( $hook );
    ASNERISSEO_Dashboard::enqueue_assets( $hook );
    ASNERISSEO_Diagnostics_Page::enqueue_assets( $hook );
    ASNERISSEO_Validation::enqueue_assets( $hook );
    ASNERISSEO_Bulk_Edit::enqueue_assets( $hook );
    ASNERISSEO_Redirects::enqueue_assets( $hook );
  }

  /** Fires on 'enqueue_block_editor_assets'. */
  public static function on_enqueue_block_editor_assets() {
    $asset_path = ASNERISSEO_DIR . 'build/index.asset.php';
    if ( ! file_exists( $asset_path ) ) {
      return;
    }
    $asset = include $asset_path;

    wp_enqueue_script(
      'ASNERISSEO-editor',
      ASNERISSEO_URL . 'build/index.js',
      $asset['dependencies'],
      $asset['version'],
      true
    );

    wp_localize_script( 'ASNERISSEO-editor', 'asnerisseoData', [
      'ajaxurl'       => admin_url( 'admin-ajax.php' ),
      'indexnowNonce' => wp_create_nonce( 'ASNERISSEO_manual_indexnow' ),
      'siteName'      => get_bloginfo( 'name' ),
    ] );

    // Check if auto-open parameter is present and inject sessionStorage flag
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL parameter for UX feature, no data modification
    if ( isset( $_GET['asneris-seo-open'] ) && $_GET['asneris-seo-open'] === '1' ) {
      wp_add_inline_script(
        'ASNERISSEO-editor',
        'sessionStorage.setItem("asneris-seo-open", "1");',
        'before'
      );
    }
  }

  /** Fires on 'wp_head' at priority 1. */
  public static function on_wp_head() {
    try {
      ASNERISSEO_Render::render_meta_tags();
      ASNERISSEO_Schema::render_jsonld();
    } catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
      // Intentionally silent — exceptions must never corrupt <head> output.
    }
  }

  /**
   * Filters 'pre_get_document_title'.
   *
   * @param string $title Current title.
   * @return string
   */
  public static function on_pre_get_document_title( $title ) {
    if ( ! is_singular() ) {
      return $title;
    }

    $id = get_queried_object_id();
    if ( ! $id ) {
      return $title;
    }

    $post = get_post( $id );
    if ( ! $post ) {
      return $title;
    }

    $seo_title = get_post_meta( $id, '_ASNERISSEO_title', true );
    if ( ! empty( $seo_title ) ) {
      return $seo_title;
    }

    $template_title = ASNERISSEO_Templates::generate_title( $post );
    if ( ! empty( $template_title ) ) {
      return $template_title;
    }

    return $title;
  }

  /**
   * Fires on 'transition_post_status'.
   * IndexNow: submit on publish/update.
   *
   * @param string  $new_status New post status.
   * @param string  $old_status Previous post status.
   * @param WP_Post $post       Post object.
   */
  public static function on_transition_post_status( $new_status, $old_status, $post ) {
    if ( ! ASNERISSEO_IndexNow::is_enabled() ) {
      return;
    }

    if ( ! ( $post instanceof WP_Post ) ) {
      return;
    }
    if ( $new_status !== 'publish' ) {
      return;
    }
    if ( wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) ) {
      return;
    }

    $ptype = get_post_type_object( $post->post_type );
    if ( ! $ptype || empty( $ptype->public ) ) {
      return;
    }

    // Throttle: avoid repeated submissions within 10 minutes for the same post
    $last = (int) get_post_meta( $post->ID, '_ASNERISSEO_indexnow_last', true );
    if ( $last && ( time() - $last ) < 600 ) {
      return;
    }

    update_post_meta( $post->ID, '_ASNERISSEO_indexnow_last', time() );
    ASNERISSEO_IndexNow::submit_url( get_permalink( $post->ID ) );
  }

  /**
   * Fires on 'before_delete_post'.
   * IndexNow: notify on deletion.
   *
   * @param int $post_id Post ID.
   */
  public static function on_before_delete_post( $post_id ) {
    if ( ! ASNERISSEO_IndexNow::is_enabled() ) {
      return;
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
      return;
    }
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
      return;
    }

    $ptype = get_post_type_object( $post->post_type );
    if ( ! $ptype || empty( $ptype->public ) ) {
      return;
    }

    $url = get_permalink( $post_id );
    if ( $url ) {
      ASNERISSEO_IndexNow::submit_url( $url );
    }
  }
}

ASNERISSEO_Bootstrap::register_hooks();

// Activation hook - flush rewrite rules for IndexNow key file
register_activation_hook( __FILE__, function() {
  ASNERISSEO_IndexNow::register_rewrite();
  flush_rewrite_rules();
} );

// Deactivation hook - clean up rewrite rules
register_deactivation_hook( __FILE__, function() {
  flush_rewrite_rules();
} );

