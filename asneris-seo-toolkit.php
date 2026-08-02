<?php
/**
 * Plugin Name: Asneris SEO Toolkit
 * Plugin URI: https://asneris.com/asneris-seo-toolkit
 * Description: Asneris: The Systematic SEO Toolkit for WordPress with intuitive UI.
 * Version: 0.1.5
 * Requires at least: 6.5
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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'ASNERISSEO_VERSION', '0.1.5' );
define( 'ASNERISSEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASNERISSEO_URL', plugin_dir_url( __FILE__ ) );
define( 'ASNERISSEO_BASENAME', plugin_basename( __FILE__ ) );
define( 'ASNERIS_MENU_SLUG', 'asneris-seo' );
if ( ! defined( 'ASNERISSEO_REACT_ONLY_ADMIN' ) ) {
	define( 'ASNERISSEO_REACT_ONLY_ADMIN', true );
}

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
require_once ASNERISSEO_DIR . 'includes/class-data-interface-normalizer.php';
require_once ASNERISSEO_DIR . 'includes/class-page-diagnostics-response-contract.php';
require_once ASNERISSEO_DIR . 'includes/class-rest-api-ai-searchability.php';
require_once ASNERISSEO_DIR . 'includes/class-rest-api.php';
require_once ASNERISSEO_DIR . 'includes/class-page-diagnostics-rest-api-migration.php';
require_once ASNERISSEO_DIR . 'includes/class-dashboard.php';
require_once ASNERISSEO_DIR . 'includes/class-diagnostics-page.php';
require_once ASNERISSEO_DIR . 'includes/class-conflict-detector.php';
require_once ASNERISSEO_DIR . 'includes/class-sitemap-helper.php';
require_once ASNERISSEO_DIR . 'includes/class-templates.php';
require_once ASNERISSEO_DIR . 'includes/class-bulk-edit.php';
require_once ASNERISSEO_DIR . 'includes/class-validation.php';
require_once ASNERISSEO_DIR . 'includes/class-diagnostics.php';
require_once ASNERISSEO_DIR . 'includes/class-page-diagnostics-snapshots.php';
require_once ASNERISSEO_DIR . 'includes/class-page-diagnostics-history-storage-api.php';
require_once ASNERISSEO_DIR . 'includes/class-seo-generator.php';
require_once ASNERISSEO_DIR . 'includes/class-help.php';
require_once ASNERISSEO_DIR . 'includes/class-404-monitor.php';



/**
 * Bootstrap class — named static methods so hooks can be removed by third parties.
 *
 * @package Asneris_SEO_Toolkit
 */
class ASNERISSEO_Bootstrap {

	/**
	 * Ensure JSX runtime handle exists on admin screens.
	 */
	private static function ensure_react_jsx_runtime() {
		if ( wp_script_is( 'react-jsx-runtime', 'registered' ) ) {
			return;
		}

		wp_register_script(
			'react-jsx-runtime',
			false,
			array( 'react' ),
			ASNERISSEO_VERSION,
			true
		);

		wp_add_inline_script(
			'react-jsx-runtime',
			'window.ReactJSXRuntime = window.ReactJSXRuntime || {
        jsx: function(type, props, key) {
          var args = [type, props];
          if (props && props.children !== undefined) {
            if (Array.isArray(props.children)) {
              args = args.concat(props.children);
            } else {
              args.push(props.children);
            }
          }
          return React.createElement.apply(React, args);
        },
        jsxs: function(type, props, key) {
          var args = [type, props];
          if (props && props.children !== undefined) {
            if (Array.isArray(props.children)) {
              args = args.concat(props.children);
            } else {
              args.push(props.children);
            }
          }
          return React.createElement.apply(React, args);
        },
        Fragment: window.React.Fragment
      };'
		);
	}

	/**
	 * Returns branded Asneris admin menu icon image URL.
	 *
	 * @return string
	 */
	private static function get_admin_menu_icon() {
		return ASNERISSEO_URL . 'assets/images/logo.png';
	}

	/**
	 * Wire up all WordPress hooks.
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'on_init' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'on_rest_api_init' ) );
		add_action( 'admin_menu', array( __CLASS__, 'on_admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'on_admin_init' ) );
		add_action( 'admin_notices', array( __CLASS__, 'on_admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'on_admin_enqueue_scripts' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'on_enqueue_block_editor_assets' ) );
		add_action( 'wp_head', array( __CLASS__, 'on_wp_head' ), 1 );
		add_action( 'transition_post_status', array( __CLASS__, 'on_transition_post_status' ), 10, 3 );
		add_action( 'before_delete_post', array( __CLASS__, 'on_before_delete_post' ) );

		add_filter( 'wp_robots', array( 'ASNERISSEO_Render', 'filter_wp_robots' ), 20 );
		add_filter( 'pre_get_document_title', array( __CLASS__, 'on_pre_get_document_title' ), 10 );
		add_filter( 'rest_send_nocache_headers', array( __CLASS__, 'on_rest_send_nocache_headers' ), 10, 1 );
		add_filter( 'rest_post_dispatch', array( __CLASS__, 'on_rest_post_dispatch' ), 10, 3 );

		// AJAX handlers
		add_action( 'wp_ajax_ASNERISSEO_export_settings', array( 'ASNERISSEO_Admin_Settings', 'ajax_export_settings' ) );
		add_action( 'wp_ajax_ASNERISSEO_import_settings', array( 'ASNERISSEO_Admin_Settings', 'ajax_import_settings' ) );
		add_action( 'wp_ajax_ASNERISSEO_reset_settings', array( 'ASNERISSEO_Admin_Settings', 'ajax_reset_settings' ) );
		add_action( 'wp_ajax_ASNERISSEO_http_test', array( 'ASNERISSEO_Diagnostics', 'ajax_http_test' ) );
		add_action( 'wp_ajax_ASNERISSEO_manual_indexnow', array( 'ASNERISSEO_IndexNow', 'ajax_manual_submit' ) );
		add_action( 'wp_ajax_ASNERISSEO_bulk_save', array( 'ASNERISSEO_Bulk_Edit', 'ajax_bulk_save' ) );
	}

	/** Fires on 'rest_api_init'. */
	public static function on_rest_api_init() {
		ASNERISSEO_REST_API::register_routes();
		ASNERISSEO_Page_Diagnostics_REST_API_Migration::register_routes();
	}

	/** Enables WordPress core no-cache headers for Asneris REST requests. */
	public static function on_rest_send_nocache_headers( $send ) {
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return $send;
		}

		$server = rest_get_server();
		if ( ! $server || ! method_exists( $server, 'get_current_request' ) ) {
			return $send;
		}

		$request = $server->get_current_request();
		if ( ! $request instanceof WP_REST_Request ) {
			return $send;
		}

		return self::is_asneris_rest_route( (string) $request->get_route() ) ? true : $send;
	}

	/** Applies no-cache headers to Asneris REST responses. */
	public static function on_rest_post_dispatch( $response, $server, $request ) {
		unset( $server );

		if ( ! $request instanceof WP_REST_Request ) {
			return $response;
		}

		if ( ! self::is_asneris_rest_route( (string) $request->get_route() ) ) {
			return $response;
		}

		if ( ! $response instanceof WP_HTTP_Response ) {
			return $response;
		}

		foreach ( wp_get_nocache_headers() as $header => $value ) {
			if ( false === $value ) {
				continue;
			}

			$response->header( $header, $value );
		}
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, s-maxage=0' );
		$response->header( 'Vary', 'Cookie, Authorization, X-WP-Nonce' );
		$response->header( 'X-Accel-Expires', '0' );

		return $response;
	}

	/** Checks whether a REST route belongs to this plugin namespace. */
	private static function is_asneris_rest_route( $route ) {
		$namespace_route = '/' . ASNERISSEO_REST_API::NAMESPACE;

		return $route === $namespace_route || 0 === strpos( $route, $namespace_route . '/' );
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
		ASNERISSEO_404_Monitor::init();
		ASNERISSEO_Page_Diagnostics_Snapshots::init();
		ASNERISSEO_SEO_Generator::init();

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
			array( 'ASNERISSEO_Dashboard', 'render_page' ),
			self::get_admin_menu_icon(),
			30
		);

		// Dashboard (replaces default first submenu)
		add_submenu_page(
			ASNERIS_MENU_SLUG,
			__( 'Dashboard', 'asneris-seo-toolkit' ),
			__( 'Dashboard', 'asneris-seo-toolkit' ),
			'manage_options',
			ASNERIS_MENU_SLUG,
			array( 'ASNERISSEO_Dashboard', 'render_page' )
		);

		// Settings
		add_submenu_page(
			ASNERIS_MENU_SLUG,
			__( 'Settings', 'asneris-seo-toolkit' ),
			__( 'Settings', 'asneris-seo-toolkit' ),
			'manage_options',
			ASNERIS_MENU_SLUG . '-settings',
			array( 'ASNERISSEO_Admin_Settings', 'render_page' )
		);

		ASNERISSEO_Diagnostics_Page::register_menu();
		ASNERISSEO_Validation::register_menu();
		ASNERISSEO_Redirects::register_menu();
		ASNERISSEO_404_Monitor::register_menu();
		ASNERISSEO_Robots::register_menu();
		ASNERISSEO_Bulk_Edit::register_menu();
		ASNERISSEO_Help::register_menu();
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
		self::ensure_react_jsx_runtime();

		ASNERISSEO_Admin_Settings::enqueue_admin_assets( $hook );
		ASNERISSEO_Dashboard::enqueue_assets( $hook );
		ASNERISSEO_Diagnostics_Page::enqueue_assets( $hook );
		ASNERISSEO_Validation::enqueue_assets( $hook );
		ASNERISSEO_Bulk_Edit::enqueue_assets( $hook );
		ASNERISSEO_Redirects::enqueue_assets( $hook );
		ASNERISSEO_404_Monitor::enqueue_assets( $hook );
		ASNERISSEO_Help::enqueue_assets( $hook );

		// Keep custom top-level admin menu icon readable in non-active state.
		if ( ! wp_style_is( 'ASNERISSEO-admin-menu-icon-visibility', 'enqueued' ) ) {
			wp_register_style( 'ASNERISSEO-admin-menu-icon-visibility', false, array(), ASNERISSEO_VERSION );
			wp_enqueue_style( 'ASNERISSEO-admin-menu-icon-visibility' );
			wp_add_inline_style(
				'ASNERISSEO-admin-menu-icon-visibility',
				'#adminmenu #toplevel_page_' . ASNERIS_MENU_SLUG . ' .wp-menu-image img{opacity:1 !important;filter:none !important;width:20px !important;height:20px !important;}'
			);
		}
	}

	/** Fires on 'enqueue_block_editor_assets'. */
	public static function on_enqueue_block_editor_assets() {
		wp_enqueue_media();

		$asset_path = ASNERISSEO_DIR . 'build/index.asset.php';
		if ( ! file_exists( $asset_path ) ) {
			return;
		}
		$asset = include $asset_path;

		// Ensure react-jsx-runtime is available (WordPress 6.0+)
		// Some WordPress versions don't automatically register this
		if ( ! wp_script_is( 'react-jsx-runtime', 'registered' ) ) {
			wp_register_script(
				'react-jsx-runtime',
				false,
				array( 'react' ),
				ASNERISSEO_VERSION,
				true
			);
			// Polyfill for JSX runtime - provides jsx, jsxs, and Fragment functions with correct signature
			wp_add_inline_script(
				'react-jsx-runtime',
				'
        window.ReactJSXRuntime = {
          jsx: function(type, props, key) {
            var args = [type, props];
            if (props && props.children !== undefined) {
              if (Array.isArray(props.children)) {
                args = args.concat(props.children);
              } else {
                args.push(props.children);
              }
            }
            return React.createElement.apply(React, args);
          },
          jsxs: function(type, props, key) {
            var args = [type, props];
            if (props && props.children !== undefined) {
              if (Array.isArray(props.children)) {
                args = args.concat(props.children);
              } else {
                args.push(props.children);
              }
            }
            return React.createElement.apply(React, args);
          },
          Fragment: window.React.Fragment
        };
      '
			);
		}

		wp_enqueue_script(
			'ASNERISSEO-editor',
			ASNERISSEO_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		$editor_style_deps = array();
		if ( file_exists( ASNERISSEO_DIR . 'build/index.css' ) ) {
			wp_enqueue_style(
				'ASNERISSEO-editor-style',
				ASNERISSEO_URL . 'build/index.css',
				array(),
				$asset['version']
			);
			$editor_style_deps[] = 'ASNERISSEO-editor-style';
		}

		$shared_admin_style_path    = ASNERISSEO_DIR . 'assets/css/admin-style.css';
		$shared_admin_style_version = file_exists( $shared_admin_style_path )
		? (string) filemtime( $shared_admin_style_path )
		: ASNERISSEO_VERSION;

		// Reuse shared admin UI classes/tokens in editor to avoid duplicated style definitions.
		wp_enqueue_style(
			'ASNERISSEO-shared-admin-style',
			ASNERISSEO_URL . 'assets/css/admin-style.css',
			$editor_style_deps,
			$shared_admin_style_version
		);

		$editor_settings              = get_option( ASNERISSEO_Admin_Settings::OPT, array() );
		$editor_title_templates       = isset( $editor_settings['title_templates'] ) && is_array( $editor_settings['title_templates'] )
		? $editor_settings['title_templates']
		: array();
		$editor_description_templates = isset( $editor_settings['description_templates'] ) && is_array( $editor_settings['description_templates'] )
		? $editor_settings['description_templates']
		: array();
		$editor_title_separator       = isset( $editor_settings['title_separator'] )
		? sanitize_text_field( $editor_settings['title_separator'] )
		: '|';
		$editor_conflicts             = ASNERISSEO_Conflict_Detector::detect_conflicts();

		wp_localize_script(
			'ASNERISSEO-editor',
			'asnerisseoData',
			array(
				'ajaxurl'              => admin_url( 'admin-ajax.php' ),
				'indexnowNonce'        => wp_create_nonce( 'ASNERISSEO_manual_indexnow' ),
				'restRoot'             => esc_url_raw( rest_url() ),
				'restNamespace'        => ASNERISSEO_REST_API::NAMESPACE,
				'restNonce'            => wp_create_nonce( 'wp_rest' ),
				'logoUrl'              => esc_url_raw( ASNERISSEO_URL . 'assets/images/logo.png' ),
				'defaultOgImage'       => esc_url_raw( $editor_settings['default_og_image'] ?? '' ),
				'siteName'             => get_bloginfo( 'name' ),
				'titleSeparator'       => $editor_title_separator,
				'titleTemplates'       => $editor_title_templates,
				'descriptionTemplates' => $editor_description_templates,
				'hasConflicts'         => ! empty( $editor_conflicts ),
				'conflicts'            => array_values( $editor_conflicts ),
				'pluginsUrl'           => admin_url( 'plugins.php' ),
			)
		);

		// Check if auto-open parameter is present and inject sessionStorage flag
	  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL parameter for UX feature, no data modification
		if ( isset( $_GET['asneris-seo-open'] ) && sanitize_text_field( wp_unslash( $_GET['asneris-seo-open'] ) ) === '1' ) {
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

		$effective_title = ASNERISSEO_SEO_Generator::get_effective_title( $id, $post );
		if ( ! empty( $effective_title ) ) {
			return $effective_title;
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
register_activation_hook(
	__FILE__,
	function () {
		ASNERISSEO_IndexNow::register_rewrite();
		ASNERISSEO_404_Monitor::activate();
		ASNERISSEO_Page_Diagnostics_Snapshots::activate();
		flush_rewrite_rules();
	}
);

// Deactivation hook - clean up rewrite rules
register_deactivation_hook(
	__FILE__,
	function () {
		ASNERISSEO_404_Monitor::deactivate();
		ASNERISSEO_Page_Diagnostics_Snapshots::deactivate();
		flush_rewrite_rules();
	}
);
