<?php
/**
 * Page Diagnostics History Storage API
 *
 * Handles storage and retrieval of diagnostic snapshots via REST API.
 *
 * @package AsnerisEO
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page Diagnostics History Storage API class
 *
 * Manages REST API endpoints for retrieving, updating, and deleting
 * diagnostic snapshot history.
 *
 * @since 1.0.0
 */
class ASNERISSEO_Page_Diagnostics_History_Storage_API {
	const NAMESPACE = 'asneris-seo/v1';

	/**
	 * Init
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/page-diagnostics/history/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_page_diagnostics_history' ),
				'permission_callback' => array( 'ASNERISSEO_REST_API', 'can_edit_post' ),
				'args'                => array(
					'id'    => array(
						'validate_callback' => static function ( $value ) {
							return is_numeric( $value ) && (int) $value > 0;
						},
						'sanitize_callback' => 'absint',
					),
					'limit' => array(
						'validate_callback' => static function ( $value ) {
							return is_numeric( $value ) && (int) $value > 0;
						},
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/page-diagnostics/history/(?P<id>\d+)/delete',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( __CLASS__, 'delete_page_diagnostics_history_record' ),
				'permission_callback' => array( 'ASNERISSEO_REST_API', 'can_manage_settings' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => static function ( $value ) {
							return is_numeric( $value ) && (int) $value > 0;
						},
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/page-diagnostics/records/clear/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( __CLASS__, 'clear_page_diagnostics_records' ),
				'permission_callback' => array( 'ASNERISSEO_REST_API', 'can_manage_settings' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => static function ( $value ) {
							return is_numeric( $value ) && (int) $value > 0;
						},
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Get page diagnostics history
	 *
	 * Retrieves paginated history of diagnostic snapshots for a page.
	 * Applies response contract normalization to ensure schema consistency.
	 *
	 * @param WP_REST_Request $request REST request object.
	 *
	 * @return WP_REST_Response|WP_Error Response with history or error.
	 */
	public static function get_page_diagnostics_history( WP_REST_Request $request ) {
		if ( class_exists( 'ASNERISSEO_Page_Diagnostics_REST_API_Migration' ) && method_exists( 'ASNERISSEO_Page_Diagnostics_REST_API_Migration', 'get_stored_page_diagnostics_history' ) ) {
			return ASNERISSEO_Page_Diagnostics_REST_API_Migration::get_stored_page_diagnostics_history( $request );
		}

		return new WP_Error(
			'asnerisseo_page_diagnostics_migration_unavailable',
			__( 'Page Diagnostics migration API is unavailable.', 'asneris-seo-toolkit' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Delete page diagnostics history record
	 *
	 * Deletes a single snapshot record from history.
	 *
	 * @param WP_REST_Request $request REST request object.
	 *
	 * @return WP_REST_Response|WP_Error Response with operation result or error.
	 */
	public static function delete_page_diagnostics_history_record( WP_REST_Request $request ) {
		$post_id  = absint( $request['id'] );
		$incoming = $request->get_json_params();
		if ( ! is_array( $incoming ) ) {
			$incoming = array();
		}
		$history_id = isset( $incoming['historyId'] ) ? absint( $incoming['historyId'] ) : 0;

		if ( $post_id < 1 || $history_id < 1 ) {
			return new WP_Error(
				'asnerisseo_history_delete_invalid',
				__( 'Invalid history delete request.', 'asneris-seo-toolkit' ),
				array( 'status' => 400 )
			);
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'asnerisseo_post_not_found',
				__( 'Post not found.', 'asneris-seo-toolkit' ),
				array( 'status' => 404 )
			);
		}

		$deleted       = ASNERISSEO_Page_Diagnostics_Snapshots::delete_history_record( $post_id, $history_id );
		$history_count = ASNERISSEO_Page_Diagnostics_Snapshots::get_history_count( $post_id );
		$history_limit = ASNERISSEO_Page_Diagnostics_Snapshots::DEFAULT_HISTORY_LIMIT;

		return rest_ensure_response(
			array(
				'success'       => $deleted > 0,
				'deleted'       => (int) $deleted,
				'historyCount'  => $history_count,
				'historyLimit'  => $history_limit,
				'historyLocked' => $history_count >= $history_limit,
			)
		);
	}

	/**
	 * Clear page diagnostics records
	 *
	 * Clears all diagnostic records (snapshots) for a page and removes
	 * it from priority pages if applicable.
	 *
	 * @param WP_REST_Request $request REST request object.
	 *
	 * @return WP_REST_Response|WP_Error Response with operation result or error.
	 */
	public static function clear_page_diagnostics_records( WP_REST_Request $request ) {
		$post_id = absint( $request['id'] );
		if ( $post_id < 1 ) {
			return new WP_Error(
				'asnerisseo_cleanup_invalid',
				__( 'Invalid cleanup request.', 'asneris-seo-toolkit' ),
				array( 'status' => 400 )
			);
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'asnerisseo_post_not_found',
				__( 'Post not found.', 'asneris-seo-toolkit' ),
				array( 'status' => 404 )
			);
		}

		$settings              = get_option( ASNERISSEO_Admin_Settings::OPT, array() );
		$existing_priority_ids = isset( $settings['priority_page_ids'] ) && is_array( $settings['priority_page_ids'] )
			? array_slice( array_values( array_unique( array_filter( array_map( 'absint', $settings['priority_page_ids'] ) ) ) ), 0, ASNERISSEO_Page_Diagnostics_Snapshots::MAX_PRIORITY_PAGES )
			: array();

		$updated_priority_ids = array_values(
			array_filter(
				$existing_priority_ids,
				static function ( $priority_id ) use ( $post_id ) {
					return (int) $priority_id !== (int) $post_id;
				}
			)
		);

		$removed_from_priority = count( $updated_priority_ids ) !== count( $existing_priority_ids );

		if ( $removed_from_priority ) {
			$updated_settings = array_merge(
				is_array( $settings ) ? $settings : array(),
				array(
					'priority_page_ids' => array_slice( $updated_priority_ids, 0, ASNERISSEO_Page_Diagnostics_Snapshots::MAX_PRIORITY_PAGES ),
				)
			);

			// Sanitize and validate settings
			if ( class_exists( 'ASNERISSEO_Admin_Settings' ) ) {
				$clean             = ASNERISSEO_Admin_Settings::sanitize( $updated_settings );
				$validation_errors = get_transient( 'asneris_settings_validation_errors' );

				if ( ! empty( $validation_errors ) ) {
					delete_transient( 'asneris_settings_validation_errors' );
					return new WP_Error(
						'asnerisseo_cleanup_validation_failed',
						__( 'Unable to update Priority Pages settings.', 'asneris-seo-toolkit' ),
						array( 'status' => 400 )
					);
				}

				update_option( ASNERISSEO_Admin_Settings::OPT, $updated_settings );
			}
		}

		$cleanup = ASNERISSEO_Page_Diagnostics_Snapshots::delete_page_records( $post_id );

		return rest_ensure_response(
			array(
				'success'             => true,
				'postId'              => $post_id,
				'removedFromPriority' => $removed_from_priority,
				'priorityPageIds'     => $updated_priority_ids,
				'cleanup'             => $cleanup,
			)
		);
	}
}

// Initialize on load
ASNERISSEO_Page_Diagnostics_History_Storage_API::init();
