<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ASNERISSEO_404_Monitor {

  private static $redirect_candidates_cache = null;
  private static $redirect_token_lookup_cache = null;

  const TABLE_SUFFIX = 'asneris_404_logs';
  const DB_VERSION = '1.4.0';
  const DB_VERSION_OPTION = 'asnerisseo_404_db_version';
  const ENABLED_OPTION = 'asnerisseo_404_enabled';
  const COLLECTING_OPTION = 'asnerisseo_404_collecting';
  const FIRST_TIME_OPTION = 'asnerisseo_404_first_time_notice';
  const THROTTLE_LIMIT_OPTION = 'asnerisseo_404_throttle_limit';
  const THROTTLE_WINDOW_OPTION = 'asnerisseo_404_throttle_window';
  const ANALYSIS_CRON_FREQUENCY_OPTION = 'asnerisseo_404_analysis_cron_frequency';
  const EXCLUDE_URLS_OPTION = 'asnerisseo_404_exclude_urls';
  const EXCLUDE_KEYWORDS_OPTION = 'asnerisseo_404_exclude_keywords';
  const IGNORE_QUERY_PARAMS_OPTION = 'asnerisseo_404_ignore_query_params';
  const MAX_LOG_RECORDS = 1000;
  const CRON_HOOK = 'asnerisseo_404_cleanup_daily';
  const ANALYSIS_CRON_HOOK = 'asnerisseo_404_analysis_hourly';

  public static function init() {
    if ( (string) get_option( self::DB_VERSION_OPTION, '' ) !== self::DB_VERSION ) {
      self::maybe_create_table();
    }

    add_action( 'template_redirect', [ __CLASS__, 'capture_404_request' ], 20 );
    add_action( self::ANALYSIS_CRON_HOOK, [ __CLASS__, 'run_priority_analysis' ] );
    add_filter( 'cron_schedules', [ __CLASS__, 'register_custom_cron_schedules' ] );
    wp_clear_scheduled_hook( self::CRON_HOOK );
    self::ensure_analysis_cron_scheduled();
  }

  public static function register_custom_cron_schedules( $schedules ) {
    if ( ! is_array( $schedules ) ) {
      $schedules = [];
    }

    if ( ! isset( $schedules['monthly'] ) ) {
      $schedules['monthly'] = [
        'interval' => 30 * DAY_IN_SECONDS,
        'display' => __( 'Once Monthly', 'asneris-seo-toolkit' ),
      ];
    }

    return $schedules;
  }

  public static function activate() {
    self::maybe_create_table();
    update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
    self::ensure_monitor_options();
    wp_clear_scheduled_hook( self::CRON_HOOK );
    self::ensure_analysis_cron_scheduled();
  }

  public static function deactivate() {
    // Housekeeping is intentionally disabled for fixed-capacity log storage.
    wp_clear_scheduled_hook( self::ANALYSIS_CRON_HOOK );
  }

  public static function run_priority_analysis( $limit = 200, $force_manual = false, $restrict_ids = [] ) {
    global $wpdb;

    $limit = max( 1, min( 1000, absint( $limit ) ) );
    $force_manual = ! empty( $force_manual );
    $restrict_ids = is_array( $restrict_ids ) ? array_values( array_filter( array_map( 'absint', $restrict_ids ) ) ) : [];

    $where_sql = 'WHERE status IN (%s, %s)';
    $params = [ 'active', 'redirected' ];

    if ( ! $force_manual ) {
      $where_sql .= ' AND (last_analysed IS NULL OR last_seen > last_analysed)';
    }

    if ( ! empty( $restrict_ids ) ) {
      $id_placeholders = implode( ', ', array_fill( 0, count( $restrict_ids ), '%d' ) );
      $where_sql .= ' AND id IN (' . $id_placeholders . ')';
      $params = array_merge( $params, $restrict_ids );
    }

    $query_sql = 'SELECT id, hit_count, last_20_hits_json, referrer, user_agent, path, redirect_target, first_seen, last_seen FROM ' . esc_sql( self::table_name() ) . ' ' . $where_sql . ' ORDER BY last_seen DESC LIMIT %d';
    $params[] = $limit;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Internal analytics query; SQL placeholders are prepared and dynamic parts are constrained.
    $rows = $wpdb->get_results(
      // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query template is constructed with placeholders and prepared immediately.
      $wpdb->prepare( $query_sql, $params ),
      ARRAY_A
    );

    if ( ! is_array( $rows ) || empty( $rows ) ) {
      return [
        'processed' => 0,
        'updated' => 0,
      ];
    }

    $updated = 0;
    $unique_paths = [];
    $suggested_redirects = 0;
    $rules_evaluated = 0;
    foreach ( $rows as $row ) {
      $path = (string) ( $row['path'] ?? '' );
      if ( '' !== $path ) {
        $unique_paths[ $path ] = true;
      }

      $hits_json = self::backfill_recent_hits_json_if_missing(
        (string) ( $row['last_20_hits_json'] ?? '' ),
        (int) ( $row['hit_count'] ?? 0 ),
        (string) ( $row['first_seen'] ?? '' ),
        (string) ( $row['last_seen'] ?? '' )
      );

      // Step 1: Priority analysis.
      $priority_analysis = self::calculate_priority_analysis(
        (int) ( $row['hit_count'] ?? 0 ),
        $hits_json,
        (string) ( $row['referrer'] ?? '' ),
        (string) ( $row['user_agent'] ?? '' ),
        (string) ( $row['path'] ?? '' )
      );

      $priority = (int) ( $priority_analysis['priority'] ?? 0 );

      // Step 2: Redirect suggestion.
      $current_redirect_target = self::sanitize_redirect_target( (string) ( $row['redirect_target'] ?? '' ) );
      $suggested_redirect_target = '';

      if ( '' === $current_redirect_target ) {
        $suggested_redirect_target = self::suggest_redirect_target_from_content( $path );
        if ( '' !== $suggested_redirect_target ) {
          $suggested_redirects++;
        }
      }

      // Priority + suggestion + recommendation generation represent core rule evaluations.
      $rules_evaluated += 3;

      // Step 3: Referrer logic and ordered recommendation payload.
      $final_redirect_target = '' !== $current_redirect_target ? $current_redirect_target : $suggested_redirect_target;
      $recommandation = self::build_recommandation_text( (string) ( $priority_analysis['level'] ?? 'low' ), $priority_analysis, $final_redirect_target );

      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal plugin analytics update
      $result = $wpdb->update(
        self::table_name(),
        [
          'priority' => $priority,
          'Recommandation' => $recommandation,
          'last_20_hits_json' => $hits_json,
          'redirect_target' => $final_redirect_target,
          'last_analysed' => current_time( 'mysql' ),
          'updated_at' => current_time( 'mysql' ),
        ],
        [ 'id' => absint( $row['id'] ?? 0 ) ],
        [ '%d', '%s', '%s', '%s', '%s', '%s' ],
        [ '%d' ]
      );

      if ( false !== $result ) {
        $updated++;
      }
    }

    return [
      'processed' => count( $rows ),
      'updated' => $updated,
      'unique_urls' => count( $unique_paths ),
      'suggested_redirects' => $suggested_redirects,
      'ignored_records' => 0,
      'rules_evaluated' => $rules_evaluated,
    ];
  }

  private static function ensure_analysis_cron_scheduled() {
    if ( ! self::is_wp_cron_enabled() ) {
      wp_clear_scheduled_hook( self::ANALYSIS_CRON_HOOK );
      return;
    }

    $next = wp_next_scheduled( self::ANALYSIS_CRON_HOOK );
    $schedule = wp_get_schedule( self::ANALYSIS_CRON_HOOK );
    $desired_schedule = self::get_analysis_cron_frequency();

    if ( 'disabled' === $desired_schedule ) {
      if ( $next ) {
        wp_clear_scheduled_hook( self::ANALYSIS_CRON_HOOK );
      }
      return;
    }

    $available_schedules = wp_get_schedules();
    if ( ! isset( $available_schedules[ $desired_schedule ] ) ) {
      $desired_schedule = 'disabled';
    }

    if ( 'disabled' === $desired_schedule ) {
      if ( $next ) {
        wp_clear_scheduled_hook( self::ANALYSIS_CRON_HOOK );
      }
      return;
    }

    if ( $next && $desired_schedule !== $schedule ) {
      wp_clear_scheduled_hook( self::ANALYSIS_CRON_HOOK );
      $next = false;
    }

    if ( $next && 'hourly' === $desired_schedule && (int) $next > ( time() + ( 2 * HOUR_IN_SECONDS ) ) ) {
      wp_clear_scheduled_hook( self::ANALYSIS_CRON_HOOK );
      $next = false;
    }

    if ( ! $next ) {
      $start_timestamp = self::get_next_analysis_start_timestamp_utc( $desired_schedule );
      wp_schedule_event( $start_timestamp, $desired_schedule, self::ANALYSIS_CRON_HOOK );
    }
  }

  private static function get_next_analysis_start_timestamp_utc( $desired_schedule ) {
    if ( 'hourly' === $desired_schedule ) {
      return time() + HOUR_IN_SECONDS;
    }

    return self::get_next_site_anchor_timestamp_utc( 3, 0 );
  }

  private static function get_next_site_anchor_timestamp_utc( $hour, $minute = 0 ) {
    $hour = max( 0, min( 23, (int) $hour ) );
    $minute = max( 0, min( 59, (int) $minute ) );

    $timezone = function_exists( 'wp_timezone' )
      ? wp_timezone()
      : new DateTimeZone( 'UTC' );

    $now = new DateTimeImmutable( 'now', $timezone );
    $anchor = $now->setTime( $hour, $minute, 0 );

    if ( $anchor <= $now ) {
      $anchor = $anchor->modify( '+1 day' );
    }

    return (int) $anchor->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'U' );
  }

  public static function register_menu() {
    add_submenu_page(
      ASNERIS_MENU_SLUG,
      __( '404 Monitor', 'asneris-seo-toolkit' ),
      __( '404 Monitor', 'asneris-seo-toolkit' ),
      'manage_options',
      ASNERIS_MENU_SLUG . '-404-monitor',
      [ __CLASS__, 'render_page' ]
    );
  }

  public static function enqueue_assets( $hook ) {
    if ( $hook !== 'asneris-seo-toolkit_page_' . ASNERIS_MENU_SLUG . '-404-monitor' ) return;

    wp_enqueue_style( 'asnerisseo-admin', ASNERISSEO_URL . 'assets/css/admin-style.css', [], ASNERISSEO_VERSION );

    $react_asset_path = ASNERISSEO_DIR . 'build/admin/index.asset.php';
    if ( file_exists( $react_asset_path ) ) {
      $react_asset = include $react_asset_path;
      wp_enqueue_script(
        'asnerisseo-admin-dashboard',
        ASNERISSEO_URL . 'build/admin/index.js',
        $react_asset['dependencies'],
        $react_asset['version'],
        true
      );

      $summary_payload = ASNERISSEO_Dashboard::get_dashboard_summary_payload();
      wp_localize_script( 'asnerisseo-admin-dashboard', 'asnerisseoAdminDashboardData', [
        'summary' => $summary_payload,
        'dashboardSummaryRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/dashboard-summary' ) ),
        'logs404RestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/404-logs' ) ),
        'logs404StatsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/404-logs/stats' ) ),
        'logs404BulkRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/404-logs/bulk' ) ),
        'logs404AnalyzeRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/404-logs/analyze' ) ),
        'logs404ExportRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/404-logs/export' ) ),
        'logs404SettingsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/404-logs/settings' ) ),
        'logoUrl' => esc_url_raw( ASNERISSEO_URL . 'assets/images/logo.png' ),
        'restNonce' => wp_create_nonce( 'wp_rest' ),
        'mountSelector' => '.asnerisseo-fallback-404-monitor',
        'hideFallback' => true,
      ] );
    }
  }

  public static function render_page() {
    ?>
    <div class="wrap ASNERISSEO-admin-wrap">
      <div id="asnerisseo-react-admin-shell-root"></div>
      <?php if ( defined( 'ASNERISSEO_REACT_ONLY_ADMIN' ) && ASNERISSEO_REACT_ONLY_ADMIN ) { ?></div><?php return; } ?>
      <div class="asnerisseo-fallback-404-monitor">
        <h1>
          <span class="dashicons dashicons-warning"></span>
          <?php esc_html_e( '404 Monitor', 'asneris-seo-toolkit' ); ?>
        </h1>
        <p class="ASNERISSEO-subtitle">
          <?php esc_html_e( 'Identify missing URLs and convert them into SEO fixes.', 'asneris-seo-toolkit' ); ?>
        </p>
      </div>
    </div>
    <?php
  }

  public static function maybe_create_table() {
    global $wpdb;

    $table = self::table_name();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      requested_url TEXT NOT NULL,
      path VARCHAR(1024) NOT NULL,
      method VARCHAR(10) NOT NULL DEFAULT 'GET',
      referrer TEXT NULL,
      user_agent TEXT NULL,
      ip_hash CHAR(64) NULL,
      hit_count INT UNSIGNED NOT NULL DEFAULT 1,
      status VARCHAR(20) NOT NULL DEFAULT 'active',
      last_20_hits_json TEXT NULL,
      priority SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      Recommandation TEXT NULL,
      redirect_target TEXT NULL,
      first_seen DATETIME NOT NULL,
      last_seen DATETIME NOT NULL,
      last_analysed DATETIME NULL,
      resolved_at DATETIME NULL,
      deleted_at DATETIME NULL,
      deleted_by BIGINT UNSIGNED NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY uq_path_method (path(191), method),
      KEY idx_status (status),
      KEY idx_last_seen (last_seen),
      KEY idx_first_seen (first_seen),
      KEY idx_status_last_seen (status, last_seen),
      KEY idx_priority (priority),
      KEY idx_deleted_at (deleted_at)
    ) {$charset_collate};";

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- One-time schema creation for plugin-owned table.
    $wpdb->query( $sql );
    self::ensure_table_schema( $table );
    update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
  }

  private static function ensure_table_schema( $table ) {
    global $wpdb;

    $table = (string) $table;
    if ( '' === $table || 1 !== preg_match( '/^[A-Za-z0-9_]+$/', $table ) ) {
      return;
    }

    $previous_suppress = $wpdb->suppress_errors( true );
    try {
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Identifier is validated plugin-owned table name.
      $columns = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`", 0 );
      $show_columns_error = (string) $wpdb->last_error;
    } catch ( \Throwable $e ) {
      $wpdb->suppress_errors( $previous_suppress );
      return;
    }
    $wpdb->suppress_errors( $previous_suppress );

    // Some DB drivers (for example pg4wp) do not support SHOW statements.
    if ( '' !== $show_columns_error ) {
      return;
    }

    if ( ! is_array( $columns ) ) {
      $columns = [];
    }

    $required_columns = [
      'path' => "VARCHAR(1024) NOT NULL DEFAULT ''",
      'method' => "VARCHAR(10) NOT NULL DEFAULT 'GET'",
      'status' => "VARCHAR(20) NOT NULL DEFAULT 'active'",
      'last_20_hits_json' => 'TEXT NULL',
      'priority' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
      'Recommandation' => 'TEXT NULL',
      'redirect_target' => 'TEXT NULL',
      'last_analysed' => 'DATETIME NULL',
      'deleted_at' => 'DATETIME NULL',
      'deleted_by' => 'BIGINT UNSIGNED NULL',
    ];

    foreach ( $required_columns as $column_name => $column_definition ) {
      if ( in_array( $column_name, $columns, true ) ) {
        continue;
      }

      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Identifier SQL with validated table/whitelisted column definitions.
      $wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `{$column_name}` {$column_definition}" );
    }

    $previous_suppress = $wpdb->suppress_errors( true );
    try {
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Identifier is validated plugin-owned table name.
      $indexes = $wpdb->get_results( "SHOW INDEX FROM `{$table}`", ARRAY_A );
      $show_indexes_error = (string) $wpdb->last_error;
    } catch ( \Throwable $e ) {
      $wpdb->suppress_errors( $previous_suppress );
      return;
    }
    $wpdb->suppress_errors( $previous_suppress );

    if ( '' !== $show_indexes_error ) {
      return;
    }

    $existing_index_names = [];
    if ( is_array( $indexes ) ) {
      foreach ( $indexes as $index_row ) {
        if ( isset( $index_row['Key_name'] ) ) {
          $existing_index_names[] = (string) $index_row['Key_name'];
        }
      }
    }

    $required_indexes = [
      'uq_path_method' => 'ADD UNIQUE KEY `uq_path_method` (`path`(191), `method`)',
      'idx_status' => 'ADD KEY `idx_status` (`status`)',
      'idx_last_seen' => 'ADD KEY `idx_last_seen` (`last_seen`)',
      'idx_first_seen' => 'ADD KEY `idx_first_seen` (`first_seen`)',
      'idx_status_last_seen' => 'ADD KEY `idx_status_last_seen` (`status`, `last_seen`)',
      'idx_priority' => 'ADD KEY `idx_priority` (`priority`)',
      'idx_deleted_at' => 'ADD KEY `idx_deleted_at` (`deleted_at`)',
    ];

    foreach ( $required_indexes as $index_name => $index_sql ) {
      if ( in_array( $index_name, $existing_index_names, true ) ) {
        continue;
      }

      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Identifier SQL with validated table/whitelisted index definitions.
      $wpdb->query( "ALTER TABLE `{$table}` {$index_sql}" );
    }
  }

  private static function table_exists( $table_name ) {
    global $wpdb;

    $table_name = (string) $table_name;
    if ( '' === $table_name || 1 !== preg_match( '/^[A-Za-z0-9_]+$/', $table_name ) ) {
      return false;
    }

    $previous_suppress = $wpdb->suppress_errors( true );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is validated and plugin-owned.
    $wpdb->get_var( "SELECT 1 FROM {$table_name} LIMIT 1" );
    $error = strtolower( (string) $wpdb->last_error );
    $wpdb->suppress_errors( $previous_suppress );

    if ( '' === $error ) {
      return true;
    }

    if ( false !== strpos( $error, 'doesn\'t exist' ) || false !== strpos( $error, 'relation' ) || false !== strpos( $error, 'no such table' ) ) {
      return false;
    }

    return false;
  }

  public static function table_name() {
    global $wpdb;
    return $wpdb->prefix . self::TABLE_SUFFIX;
  }

  public static function capture_404_request() {
    if ( ! self::is_feature_enabled() ) {
      return;
    }

    if ( ! is_404() ) {
      return;
    }

    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
      return;
    }

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( (string) wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
    if ( '' === $request_uri ) {
      return;
    }

    if ( self::should_ignore_query_params() && false !== strpos( $request_uri, '?' ) ) {
      return;
    }

    $requested_url = esc_url_raw( home_url( add_query_arg( [], $request_uri ) ) );
    if ( strlen( $requested_url ) > 2048 ) {
      $requested_url = substr( $requested_url, 0, 2048 );
    }

    $path = self::normalize_path( (string) wp_parse_url( $requested_url, PHP_URL_PATH ) );

    if ( '' === $path || strlen( $path ) > 1024 ) {
      return;
    }

    if ( self::is_excluded_path( $path ) ) {
      return;
    }

    if ( self::is_excluded_by_custom_rules( $requested_url, $path ) ) {
      return;
    }

    $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
    if ( ! in_array( $method, [ 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS' ], true ) ) {
      $method = 'GET';
    }

    if ( self::is_log_limit_reached() && ! self::has_existing_log_entry( $path, $method ) ) {
      return;
    }

    $referrer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( (string) wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
    if ( strlen( $referrer ) > 2048 ) {
      $referrer = substr( $referrer, 0, 2048 );
    }

    $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
    if ( strlen( $user_agent ) > 512 ) {
      $user_agent = substr( $user_agent, 0, 512 );
    }

    $ip_hash = self::hash_client_ip();
    if ( ! self::is_within_throttle_limit( $path, $ip_hash ) ) {
      return;
    }

    self::upsert_log( [
      'requested_url' => $requested_url,
      'path' => $path,
      'method' => $method,
      'referrer' => $referrer,
      'user_agent' => $user_agent,
      'ip_hash' => $ip_hash,
    ] );
  }

  public static function get_logs( $args = [] ) {
    global $wpdb;

    $page = max( 1, absint( $args['page'] ?? 1 ) );
    $per_page = absint( $args['per_page'] ?? 20 );
    $per_page = max( 1, min( 100, $per_page ) );
    $offset = ( $page - 1 ) * $per_page;

    $allowed_sort = [ 'last_seen', 'first_seen', 'hit_count', 'path', 'priority', 'redirect_target' ];
    $sort_by = sanitize_key( $args['sort_by'] ?? 'last_seen' );
    if ( ! in_array( $sort_by, $allowed_sort, true ) ) {
      $sort_by = 'last_seen';
    }

    $sort_dir = strtolower( sanitize_text_field( $args['sort_dir'] ?? 'desc' ) ) === 'asc' ? 'ASC' : 'DESC';

    $status = sanitize_key( $args['status'] ?? 'active' );
    if ( ! in_array( $status, [ 'active', 'ignored', 'redirected', 'fixed', 'all' ], true ) ) {
      $status = 'active';
    }

    $search = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
    $priority_filter_raw = isset( $args['priority_filter'] ) ? sanitize_text_field( (string) $args['priority_filter'] ) : 'all';
    $priority_filter_parts = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $priority_filter_raw ) ) ) );
    $allowed_priority_filters = [ 'all', 'all_non_low', 'critical', 'high', 'medium', 'low' ];
    $priority_filters = array_values( array_intersect( $priority_filter_parts, $allowed_priority_filters ) );
    if ( empty( $priority_filters ) ) {
      $priority_filters = [ 'all' ];
    }
    if ( in_array( 'all_non_low', $priority_filters, true ) ) {
      $priority_filters = [ 'all_non_low' ];
    } elseif ( in_array( 'all', $priority_filters, true ) ) {
      $priority_filters = [ 'all' ];
    }

    $recommendation_filter_raw = isset( $args['recommendation_filter'] ) ? sanitize_text_field( (string) $args['recommendation_filter'] ) : 'all';
    $recommendation_filter_parts = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $recommendation_filter_raw ) ) ) );
    $allowed_recommendation_filters = [ 'all', 'proposed_redirect_url', 'hotfix_broken_url', 'find_redirect_page' ];
    $recommendation_filters = array_values( array_intersect( $recommendation_filter_parts, $allowed_recommendation_filters ) );
    if ( empty( $recommendation_filters ) || in_array( 'all', $recommendation_filters, true ) ) {
      $recommendation_filters = [ 'all' ];
    }
    $like = '%' . $wpdb->esc_like( $search ) . '%';

    $date_from = isset( $args['date_from'] ) ? sanitize_text_field( (string) $args['date_from'] ) : '';
    if ( '' !== $date_from && ! self::is_valid_date( $date_from ) ) {
      $date_from = '';
    }

    $date_to = isset( $args['date_to'] ) ? sanitize_text_field( (string) $args['date_to'] ) : '';
    if ( '' !== $date_to && ! self::is_valid_date( $date_to ) ) {
      $date_to = '';
    }

    $where = [];
    $params = [];

    if ( 'all' !== $status ) {
      $where[] = 'status = %s';
      $params[] = $status;
    } else {
      $where[] = "status IN ('active','ignored','redirected','fixed')";
    }

    if ( '' !== $search ) {
      $where[] = '(path LIKE %s OR requested_url LIKE %s OR referrer LIKE %s OR redirect_target LIKE %s)';
      $params[] = $like;
      $params[] = $like;
      $params[] = $like;
      $params[] = $like;
    }

    if ( ! in_array( 'all', $priority_filters, true ) ) {
      $priority_values = [];
      foreach ( $priority_filters as $priority_filter ) {
        if ( 'all_non_low' === $priority_filter ) {
          $priority_values[] = 3;
          $priority_values[] = 2;
          $priority_values[] = 1;
        } elseif ( 'critical' === $priority_filter ) {
          $priority_values[] = 3;
        } elseif ( 'high' === $priority_filter ) {
          $priority_values[] = 2;
        } elseif ( 'medium' === $priority_filter ) {
          $priority_values[] = 1;
        } elseif ( 'low' === $priority_filter ) {
          $priority_values[] = 0;
        }
      }

      $priority_values = array_values( array_unique( array_map( 'absint', $priority_values ) ) );
      if ( ! empty( $priority_values ) ) {
        $placeholders = implode( ', ', array_fill( 0, count( $priority_values ), '%d' ) );
        $where[] = 'priority IN (' . $placeholders . ')';
        foreach ( $priority_values as $priority_value ) {
          $params[] = $priority_value;
        }
      }
    }

    if ( ! in_array( 'all', $recommendation_filters, true ) ) {
      $recommendation_where = [];
      foreach ( $recommendation_filters as $recommendation_key ) {
        if ( 'proposed_redirect_url' === $recommendation_key ) {
          $recommendation_where[] = 'Recommandation LIKE %s';
          $params[] = '%PROPOSED_REDIRECT_URL%';
        } elseif ( 'hotfix_broken_url' === $recommendation_key ) {
          $recommendation_where[] = 'Recommandation LIKE %s';
          $params[] = '%HOTFIX_BROKEN_URL%';
        } elseif ( 'find_redirect_page' === $recommendation_key ) {
          $recommendation_where[] = 'Recommandation LIKE %s';
          $params[] = '%FIND_REDIRECT_PAGE%';
        }
      }

      if ( ! empty( $recommendation_where ) ) {
        $where[] = '( ' . implode( ' OR ', $recommendation_where ) . ' )';
      }
    }

    if ( '' !== $date_from ) {
      $where[] = 'DATE(last_seen) >= %s';
      $params[] = $date_from;
    }

    if ( '' !== $date_to ) {
      $where[] = 'DATE(last_seen) <= %s';
      $params[] = $date_to;
    }

    $where_sql = empty( $where ) ? '1=1' : implode( ' AND ', $where );
    $order_by = in_array( $sort_by, $allowed_sort, true ) ? $sort_by : 'last_seen';
    $order_dir = 'ASC' === $sort_dir ? 'ASC' : 'DESC';

    $count_sql = 'SELECT COUNT(*) FROM ' . esc_sql( self::table_name() ) . ' WHERE ' . $where_sql;
    if ( empty( $params ) ) {
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Query parts are internally constrained (trusted table + whitelisted sort + sanitized filters).
      $total = (int) $wpdb->get_var( $count_sql );
    } else {
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Dynamic WHERE placeholders are prepared via $wpdb->prepare.
      $total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );
    }

    $query_sql = 'SELECT id, requested_url, path, method, referrer, user_agent, ip_hash, hit_count, status, last_20_hits_json, priority, Recommandation AS recommandation, redirect_target, first_seen, last_seen, last_analysed, resolved_at FROM ' . esc_sql( self::table_name() ) . ' WHERE ' . $where_sql . ' ORDER BY ' . $order_by . ' ' . $order_dir . ' LIMIT %d OFFSET %d';
    $query_params = array_merge( $params, [ $per_page, $offset ] );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Dynamic WHERE placeholders are prepared via $wpdb->prepare; ORDER BY values are whitelist constrained.
    $rows = $wpdb->get_results( $wpdb->prepare( $query_sql, $query_params ), ARRAY_A );
    if ( ! is_array( $rows ) ) {
      $rows = [];
    }

    $items = array_map( [ __CLASS__, 'sanitize_log_row' ], $rows );

    return [
      'items' => $items,
      'total' => $total,
      'page' => $page,
      'per_page' => $per_page,
      'total_pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 0,
    ];
  }

  public static function get_log_by_id( $id ) {
    global $wpdb;

    $id = absint( $id );
    if ( $id <= 0 ) {
      return null;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal plugin analytics query
    $row = $wpdb->get_row( $wpdb->prepare( 'SELECT id, requested_url, path, method, referrer, user_agent, ip_hash, hit_count, status, last_20_hits_json, priority, Recommandation AS recommandation, redirect_target, first_seen, last_seen, last_analysed, resolved_at FROM ' . esc_sql( self::table_name() ) . ' WHERE id = %d', $id ), ARRAY_A );

    if ( ! is_array( $row ) ) {
      return null;
    }

    return self::sanitize_log_row( $row );
  }

  public static function update_log( $id, $payload = [] ) {
    global $wpdb;

    $id = absint( $id );
    if ( $id <= 0 ) {
      return false;
    }

    $table = esc_sql( self::table_name() );
    $existing = self::get_log_by_id( $id );
    if ( ! $existing ) {
      return false;
    }

    $status = isset( $payload['status'] ) ? sanitize_key( $payload['status'] ) : $existing['status'];
    if ( ! in_array( $status, [ 'active', 'ignored', 'redirected', 'fixed', 'deleted' ], true ) ) {
      $status = $existing['status'];
    }

    if ( 'deleted' === $status ) {
      return self::delete_log( $id );
    }

    $redirect_source = isset( $payload['redirect_target'] ) ? (string) $payload['redirect_target'] : (string) $existing['redirect_target'];
    $redirect_target = self::sanitize_redirect_target( $redirect_source );

    $redirect_code = isset( $payload['redirect_code'] ) ? (int) $payload['redirect_code'] : 301;
    if ( ! in_array( $redirect_code, [ 301, 302, 307 ], true ) ) {
      $redirect_code = 301;
    }

    // Keep 404 Monitor and Redirects feature in sync.
    if ( 'redirected' === $status ) {
      $from_path = isset( $existing['path'] ) ? sanitize_text_field( (string) $existing['path'] ) : '';
      if ( '' === $from_path ) {
        $from_path = self::normalize_path( (string) wp_parse_url( (string) ( $existing['requested_url'] ?? '' ), PHP_URL_PATH ) );
      }

      if ( '' === $from_path || ! self::is_safe_redirect_target( $redirect_target ) ) {
        return false;
      }

      if ( class_exists( 'ASNERISSEO_Redirects' ) ) {
        if ( ! ASNERISSEO_Redirects::add_redirect( $from_path, $redirect_target, $redirect_code, 'manual' ) ) {
          return false;
        }
      }
    }

    $now = current_time( 'mysql' );
    $data = [
      'status' => $status,
      'redirect_target' => $redirect_target,
      'updated_at' => $now,
    ];
    $format = [ '%s', '%s', '%s' ];

    if ( 'redirected' === $status ) {
      $data['resolved_at'] = $now;
      $format[] = '%s';
    } else {
      $data['resolved_at'] = null;
      $format[] = '%s';
      if ( 'active' === $status ) {
        $data['redirect_target'] = '';
      }
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal plugin analytics update
    $updated = $wpdb->update( $table, $data, [ 'id' => $id ], $format, [ '%d' ] );

    return false !== $updated;
  }

  public static function soft_delete( $id ) {
    return self::delete_log( $id );
  }

  public static function delete_log( $id ) {
    global $wpdb;

    $id = absint( $id );
    if ( $id <= 0 ) {
      return false;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal plugin analytics delete
    $deleted = $wpdb->delete( self::table_name(), [ 'id' => $id ], [ '%d' ] );

    return false !== $deleted && $deleted > 0;
  }

  public static function bulk_action( $ids, $action, $redirect_target = '' ) {
    if ( ! is_array( $ids ) ) {
      return [ 'updated' => 0, 'failed' => 0 ];
    }

    $action = sanitize_key( $action );

    if ( 'analyze' === $action ) {
      $valid_ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
      if ( empty( $valid_ids ) ) {
        return [ 'updated' => 0, 'failed' => 0 ];
      }

      $result = self::run_priority_analysis( count( $valid_ids ), true, $valid_ids );
      $processed = absint( $result['processed'] ?? 0 );
      $updated = absint( $result['updated'] ?? 0 );

      return [
        'updated' => $updated,
        'failed' => max( 0, count( $valid_ids ) - $processed ),
      ];
    }

    $updated = 0;
    $failed = 0;

    foreach ( $ids as $id ) {
      $id = absint( $id );
      if ( $id <= 0 ) {
        $failed++;
        continue;
      }

      $ok = false;
      if ( 'delete' === $action ) {
        $ok = self::soft_delete( $id );
      } elseif ( 'ignore' === $action ) {
        $ok = self::update_log( $id, [ 'status' => 'ignored' ] );
      } elseif ( 'activate' === $action ) {
        $ok = self::update_log( $id, [ 'status' => 'active' ] );
      } elseif ( 'redirect' === $action ) {
        $ok = self::update_log( $id, [
          'status' => 'redirected',
          'redirect_target' => $redirect_target,
        ] );
      }

      if ( $ok ) {
        $updated++;
      } else {
        $failed++;
      }
    }

    return [
      'updated' => $updated,
      'failed' => $failed,
    ];
  }

  public static function get_stats() {
    global $wpdb;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal plugin analytics query
    $total_urls = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . esc_sql( self::table_name() ) . ' WHERE status IN (%s, %s, %s, %s)', 'active', 'ignored', 'redirected', 'fixed' ) );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal plugin analytics query
    $active_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . esc_sql( self::table_name() ) . ' WHERE status = %s', 'active' ) );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal plugin analytics query
    $redirected_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . esc_sql( self::table_name() ) . ' WHERE status = %s', 'redirected' ) );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal plugin analytics query
    $ignored_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . esc_sql( self::table_name() ) . ' WHERE status = %s', 'ignored' ) );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal plugin analytics query
    $last_7_days_hits = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(hit_count), 0) FROM ' . esc_sql( self::table_name() ) . ' WHERE status IN (%s, %s, %s, %s) AND last_seen >= DATE_SUB(NOW(), INTERVAL 7 DAY)', 'active', 'ignored', 'redirected', 'fixed' ) );

    return [
      'total_urls' => $total_urls,
      'active_count' => $active_count,
      'redirected_count' => $redirected_count,
      'ignored_count' => $ignored_count,
      'last_7_days_hits' => $last_7_days_hits,
    ];
  }

  public static function export_logs( $args = [] ) {
    $args['per_page'] = 100;
    $args['page'] = 1;

    $logs = self::get_logs( $args );
    $all_items = is_array( $logs['items'] ?? null ) ? $logs['items'] : [];
    $total_pages = max( 1, absint( $logs['total_pages'] ?? 1 ) );

    if ( $total_pages > 1 ) {
      for ( $page = 2; $page <= $total_pages; $page++ ) {
        $args['page'] = $page;
        $page_logs = self::get_logs( $args );
        $page_items = is_array( $page_logs['items'] ?? null ) ? $page_logs['items'] : [];
        if ( ! empty( $page_items ) ) {
          $all_items = array_merge( $all_items, $page_items );
        }
      }
    }

    $rows = [];
    foreach ( $all_items as $item ) {
      $rows[] = [
        'url' => self::sanitize_csv_cell( $item['url'] ),
        'path' => self::sanitize_csv_cell( $item['path'] ),
        'requested_url' => self::sanitize_csv_cell( $item['requested_url'] ),
        'method' => self::sanitize_csv_cell( $item['method'] ),
        'user_agent' => self::sanitize_csv_cell( $item['user_agent'] ),
        'hit_count' => (int) $item['hit_count'],
        'priority' => (int) $item['priority'],
        'recommandation' => self::sanitize_csv_cell( $item['recommandation'] ),
        'status' => self::sanitize_csv_cell( $item['status'] ),
        'first_seen' => self::sanitize_csv_cell( $item['first_seen'] ),
        'last_seen' => self::sanitize_csv_cell( $item['last_seen'] ),
        'referrer' => self::sanitize_csv_cell( $item['referrer'] ),
      ];
    }

    return [
      'filename' => 'asneris-404-logs-' . gmdate( 'Ymd-His' ) . '.csv',
      'rows' => $rows,
      'total' => count( $rows ),
    ];
  }

  public static function get_monitor_settings() {
    self::ensure_monitor_options();
    $current_records = self::get_current_log_count();
    $storage_details = self::get_storage_details();
    $cron_details = self::get_analysis_cron_details();
    $wp_cron_enabled = self::is_wp_cron_enabled();

    return [
      'enabled' => self::is_feature_enabled(),
      'collecting' => self::is_feature_enabled(),
      'first_time' => ! empty( get_option( self::FIRST_TIME_OPTION, true ) ),
      'throttle_limit' => self::get_throttle_limit(),
      'throttle_window' => self::get_throttle_window(),
      'analysis_cron_frequency' => self::get_analysis_cron_frequency(),
      'analysis_cron_status' => (string) ( $cron_details['status'] ?? 'not_scheduled' ),
      'analysis_next_run_gmt' => (string) ( $cron_details['next_run_gmt'] ?? '' ),
      'log_limit' => self::MAX_LOG_RECORDS,
      'current_records' => $current_records,
      'max_records' => self::MAX_LOG_RECORDS,
      'log_limit_reached' => $current_records >= self::MAX_LOG_RECORDS,
      'exclude_urls' => self::get_exclude_urls_text(),
      'exclude_keywords' => self::get_exclude_keywords_text(),
      'ignore_query_params' => self::should_ignore_query_params(),
      'storage_details' => $storage_details,
      'wp_cron_enabled' => $wp_cron_enabled,
      'system_cron_status' => $wp_cron_enabled
        ? __( 'WP-Cron Enabled', 'asneris-seo-toolkit' )
        : __( 'WP-Cron Disabled', 'asneris-seo-toolkit' ),
      'wp_cron_note' => $wp_cron_enabled
        ? ''
        : __( 'WP-Cron is disabled in this environment. Automatic 404 analysis is disabled by default. Run analysis manually whenever needed, or enable WP-Cron / set up a system cron for scheduled analysis.', 'asneris-seo-toolkit' ),
    ];
  }

  private static function get_analysis_cron_details() {
    self::ensure_analysis_cron_scheduled();

    if ( ! self::is_wp_cron_enabled() ) {
      return [
        'status' => 'disabled',
        'next_run_gmt' => '',
      ];
    }

    $selected_frequency = self::get_analysis_cron_frequency();
    if ( 'disabled' === $selected_frequency ) {
      return [
        'status' => 'disabled',
        'next_run_gmt' => '',
      ];
    }

    $scheduled_frequency = (string) wp_get_schedule( self::ANALYSIS_CRON_HOOK );
    $next_run = wp_next_scheduled( self::ANALYSIS_CRON_HOOK );

    if ( ! $next_run ) {
      return [
        'status' => 'not_scheduled',
        'next_run_gmt' => '',
      ];
    }

    if ( '' !== $scheduled_frequency && $scheduled_frequency !== $selected_frequency ) {
      return [
        'status' => 'schedule_mismatch',
        'next_run_gmt' => gmdate( 'Y-m-d H:i:s', (int) $next_run ),
      ];
    }

    return [
      'status' => 'scheduled',
      'next_run_gmt' => gmdate( 'Y-m-d H:i:s', (int) $next_run ),
    ];
  }

  private static function get_storage_details() {
    global $wpdb;

    $table_name = self::table_name();
    $cache_key = 'table_exists:' . md5( get_current_blog_id() . '|' . $table_name );
    $table_exists = wp_cache_get( $cache_key, 'asnerisseo_404_monitor' );

    if ( false === $table_exists ) {
      $table_exists = self::table_exists( $table_name );
      wp_cache_set( $cache_key, $table_exists ? 1 : 0, 'asnerisseo_404_monitor', 5 * MINUTE_IN_SECONDS );
    }

    $table_exists = ! empty( $table_exists );

    $current_records = self::get_current_log_count();
    $max_records = self::MAX_LOG_RECORDS;
    $usage_percent = $max_records > 0 ? (int) round( ( $current_records / $max_records ) * 100 ) : 0;

    return [
      'table' => [
        'name' => self::mask_table_name_for_display( $table_name ),
        'exists' => $table_exists,
      ],
      'db_version' => (string) get_option( self::DB_VERSION_OPTION, '' ),
      'ready' => $table_exists,
      'current_records' => $current_records,
      'max_records' => $max_records,
      'usage_percent' => max( 0, min( 100, $usage_percent ) ),
      'log_limit_reached' => $current_records >= $max_records,
    ];
  }

  private static function mask_table_name_for_display( $table_name ) {
    global $wpdb;

    $name = (string) $table_name;
    $prefix = (string) ( $wpdb->prefix ?? '' );

    if ( '' !== $prefix && 0 === strpos( $name, $prefix ) ) {
      return 'xxx_' . substr( $name, strlen( $prefix ) );
    }

    return 'xxx_' . ltrim( $name, '_' );
  }

  public static function update_monitor_settings( $enabled = null, $collecting = null, $acknowledge_first_time = null, $throttle_limit = null, $throttle_window = null, $retention_days = null, $cron_enabled = null, $cron_hour = null, $cron_minute = null, $log_limit = null, $exclude_urls = null, $exclude_keywords = null, $ignore_query_params = null, $analysis_cron_frequency = null ) {
    self::ensure_monitor_options();

    if ( null !== $enabled ) {
      update_option( self::ENABLED_OPTION, ! empty( $enabled ) ? 1 : 0 );
      update_option( self::COLLECTING_OPTION, ! empty( $enabled ) ? 1 : 0 );
    }

    if ( null !== $collecting ) {
      update_option( self::COLLECTING_OPTION, ! empty( $collecting ) ? 1 : 0 );
    }

    if ( ! empty( $acknowledge_first_time ) ) {
      update_option( self::FIRST_TIME_OPTION, 0 );
    }

    if ( null !== $throttle_limit ) {
      update_option( self::THROTTLE_LIMIT_OPTION, self::normalize_throttle_limit( $throttle_limit ) );
    }

    if ( null !== $throttle_window ) {
      update_option( self::THROTTLE_WINDOW_OPTION, self::normalize_throttle_window( $throttle_window ) );
    }

    if ( null !== $exclude_urls ) {
      update_option( self::EXCLUDE_URLS_OPTION, self::normalize_exclusions_text( $exclude_urls ) );
    }

    if ( null !== $exclude_keywords ) {
      update_option( self::EXCLUDE_KEYWORDS_OPTION, self::normalize_exclusions_text( $exclude_keywords ) );
    }

    if ( null !== $ignore_query_params ) {
      update_option( self::IGNORE_QUERY_PARAMS_OPTION, ! empty( $ignore_query_params ) ? 1 : 0 );
    }

    if ( null !== $analysis_cron_frequency ) {
      $normalized_frequency = self::normalize_analysis_cron_frequency( $analysis_cron_frequency );
      if ( ! self::is_wp_cron_enabled() ) {
        $normalized_frequency = 'disabled';
      }

      update_option( self::ANALYSIS_CRON_FREQUENCY_OPTION, $normalized_frequency );
      self::ensure_analysis_cron_scheduled();
    }

    return self::get_monitor_settings();
  }

  private static function upsert_log( $row ) {
    global $wpdb;

    $now = current_time( 'mysql' );
    $now_utc = gmdate( 'Y-m-d H:i:s' );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal plugin analytics query
    $existing = $wpdb->get_row(
      $wpdb->prepare(
        'SELECT id, hit_count, last_20_hits_json, redirect_target FROM ' . esc_sql( self::table_name() ) . ' WHERE path = %s AND method = %s AND status IN (%s, %s, %s) ORDER BY last_seen DESC LIMIT 1',
        $row['path'],
        (string) ( $row['method'] ?? 'GET' ),
        'active',
        'ignored',
        'redirected'
      ),
      ARRAY_A
    );

    $hits_json = self::append_recent_hit_json( (string) ( $existing['last_20_hits_json'] ?? '' ), $now_utc );
    $updated_hit_count = (int) ( $existing['hit_count'] ?? 0 ) + 1;

    if ( is_array( $existing ) && ! empty( $existing['id'] ) ) {
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal plugin analytics update
      $wpdb->update(
        self::table_name(),
        [
          'hit_count' => $updated_hit_count,
          'last_seen' => $now,
          'requested_url' => $row['requested_url'],
          'method' => $row['method'],
          'referrer' => $row['referrer'],
          'user_agent' => $row['user_agent'],
          'ip_hash' => $row['ip_hash'],
          'last_20_hits_json' => $hits_json,
          'updated_at' => $now,
        ],
        [ 'id' => (int) $existing['id'] ],
        [ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ],
        [ '%d' ]
      );
      return;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal plugin analytics insert
    $wpdb->insert(
      self::table_name(),
      [
        'requested_url' => $row['requested_url'],
        'path' => $row['path'],
        'method' => $row['method'],
        'referrer' => $row['referrer'],
        'user_agent' => $row['user_agent'],
        'ip_hash' => $row['ip_hash'],
        'hit_count' => 1,
        'status' => 'active',
        'last_20_hits_json' => $hits_json,
        'priority' => 0,
        'Recommandation' => '',
        'redirect_target' => '',
        'first_seen' => $now,
        'last_seen' => $now,
        'created_at' => $now,
        'updated_at' => $now,
      ],
      [ '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
    );
  }

  private static function suggest_redirect_target_from_content( $missing_path ) {
    $normalized_missing_path = self::normalize_search_text( (string) $missing_path );
    if ( '' === $normalized_missing_path ) {
      return '';
    }

    $index = self::get_redirect_candidates_from_posts_pages();
    if ( empty( $index ) ) {
      return '';
    }

    $missing = [
      'slug' => basename( $normalized_missing_path ),
      'path' => $normalized_missing_path,
      'tokens' => self::tokenize_path_for_matching( $normalized_missing_path ),
    ];

    $candidate_indexes = self::get_candidate_indexes_by_tokens( (array) $missing['tokens'], $index );
    if ( empty( $candidate_indexes ) ) {
      $candidate_indexes = array_keys( $index );
    }

    $best = null;
    $best_score = 0;

    foreach ( $candidate_indexes as $candidate_index ) {
      if ( ! isset( $index[ $candidate_index ] ) || ! is_array( $index[ $candidate_index ] ) ) {
        continue;
      }

      $candidate = $index[ $candidate_index ];
      $candidate_path = (string) ( $candidate['path'] ?? '' );
      if ( '' === $candidate_path || $candidate_path === $normalized_missing_path ) {
        continue;
      }

      $score = self::score_redirect_candidate( $missing, $candidate );
      if ( $score > $best_score ) {
        $best_score = $score;
        $best = $candidate;
      }
    }

    if ( $best_score < 60 || ! is_array( $best ) ) {
      return '';
    }

    $best_path = '/' . ltrim( (string) ( $best['path'] ?? '' ), '/' );
    return self::sanitize_redirect_target( $best_path );
  }

  private static function get_redirect_candidates_from_posts_pages() {
    if ( is_array( self::$redirect_candidates_cache ) ) {
      return self::$redirect_candidates_cache;
    }

    $post_ids = get_posts(
      [
        'post_type' => [ 'post', 'page' ],
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'suppress_filters' => false,
      ]
    );

    if ( ! is_array( $post_ids ) || empty( $post_ids ) ) {
      self::$redirect_candidates_cache = [];
      self::$redirect_token_lookup_cache = [];
      return self::$redirect_candidates_cache;
    }

    $candidates = self::build_search_index( $post_ids );
    self::$redirect_token_lookup_cache = self::build_redirect_token_lookup( $candidates );

    self::$redirect_candidates_cache = $candidates;
    return self::$redirect_candidates_cache;
  }

  private static function build_search_index( $post_ids ) {
    $index = [];

    foreach ( $post_ids as $post_id ) {
      $permalink = get_permalink( (int) $post_id );
      if ( ! is_string( $permalink ) || '' === $permalink ) {
        continue;
      }

      $path = self::normalize_search_text( (string) wp_parse_url( $permalink, PHP_URL_PATH ) );
      if ( '' === $path ) {
        continue;
      }

      $tokens = self::tokenize_path_for_matching( $path );

      $old_slugs = get_post_meta( (int) $post_id, '_wp_old_slug', false );
      if ( ! is_array( $old_slugs ) ) {
        $old_slugs = [];
      }

      $normalized_old_slugs = [];
      foreach ( $old_slugs as $old_slug ) {
        $old_slug = self::normalize_search_text( (string) $old_slug );
        if ( '' !== $old_slug ) {
          $normalized_old_slugs[] = basename( $old_slug );
        }
      }

      $index[] = [
        'id' => (int) $post_id,
        'slug' => basename( $path ),
        'path' => $path,
        'title' => sanitize_text_field( (string) get_the_title( (int) $post_id ) ),
        'tokens' => $tokens,
        'old_slugs' => array_values( array_unique( $normalized_old_slugs ) ),
      ];
    }

    return $index;
  }

  private static function normalize_search_text( $text ) {
    $text = strtolower( urldecode( (string) $text ) );
    $text = preg_replace( '#\?.*$#', '', $text );
    $text = preg_replace( '#\.[a-z0-9]+$#', '', $text );
    $text = trim( (string) $text, '/' );
    $text = preg_replace( '/[-_\.]+/', '-', (string) $text );
    $text = preg_replace( '#/+#', '/', (string) $text );

    return sanitize_text_field( (string) $text );
  }

  private static function tokenize_path_for_matching( $path ) {
    $normalized = self::normalize_search_text( (string) $path );
    if ( '' === $normalized ) {
      return [];
    }

    $raw = preg_split( '/[-\/_\.]+/', $normalized );
    if ( ! is_array( $raw ) ) {
      return [];
    }

    $stop_words = [
      'a', 'an', 'the', 'of', 'for', 'to', 'in', 'on',
      'page', 'post', 'category', 'tag',
    ];

    $tokens = [];
    foreach ( $raw as $token ) {
      $token = sanitize_key( (string) $token );
      if ( '' === $token || in_array( $token, $stop_words, true ) ) {
        continue;
      }

      $token = preg_replace( '/\d+$/', '', $token );
      if ( '' !== $token ) {
        $tokens[] = $token;
      }
    }

    return array_values( array_unique( $tokens ) );
  }

  private static function build_redirect_token_lookup( $candidates ) {
    $lookup = [];

    foreach ( $candidates as $index => $candidate ) {
      $tokens = (array) ( $candidate['tokens'] ?? [] );
      foreach ( $tokens as $token ) {
        $token = sanitize_key( (string) $token );
        if ( '' === $token ) {
          continue;
        }

        if ( ! isset( $lookup[ $token ] ) ) {
          $lookup[ $token ] = [];
        }

        $lookup[ $token ][ $index ] = true;
      }
    }

    return $lookup;
  }

  private static function get_candidate_indexes_by_tokens( $tokens, $candidates ) {
    unset( $candidates );

    if ( ! is_array( self::$redirect_token_lookup_cache ) || empty( self::$redirect_token_lookup_cache ) ) {
      return [];
    }

    $matched = [];
    foreach ( (array) $tokens as $token ) {
      $token = sanitize_key( (string) $token );
      if ( '' === $token || empty( self::$redirect_token_lookup_cache[ $token ] ) ) {
        continue;
      }

      foreach ( self::$redirect_token_lookup_cache[ $token ] as $candidate_index => $enabled ) {
        if ( $enabled ) {
          $matched[ (int) $candidate_index ] = true;
        }
      }
    }

    return array_keys( $matched );
  }

  private static function score_redirect_candidate( $missing, $candidate ) {
    $missing_slug = sanitize_key( (string) ( $missing['slug'] ?? '' ) );
    $candidate_slug = sanitize_key( (string) ( $candidate['slug'] ?? '' ) );
    $missing_path = (string) ( $missing['path'] ?? '' );
    $candidate_path = (string) ( $candidate['path'] ?? '' );

    if ( '' !== $missing_slug && '' !== $candidate_slug && $missing_slug === $candidate_slug ) {
      return 100;
    }

    $score = 0;
    $distance = levenshtein( $missing_slug, $candidate_slug );
    $max_length = max( strlen( $missing_slug ), strlen( $candidate_slug ) );
    $similarity = 100 - ( ( $distance / max( $max_length, 1 ) ) * 100 );
    $score += (int) round( max( 0, $similarity ) * 0.35 );

    $missing_tokens = array_values( array_unique( array_map( 'sanitize_key', (array) ( $missing['tokens'] ?? [] ) ) ) );
    $candidate_tokens = array_values( array_unique( array_map( 'sanitize_key', (array) ( $candidate['tokens'] ?? [] ) ) ) );
    $common = array_intersect( $missing_tokens, $candidate_tokens );

    if ( ! empty( $missing_tokens ) ) {
      $score += (int) round( ( count( $common ) / count( $missing_tokens ) ) * 35 );
    }

    similar_text( $missing_path, $candidate_path, $path_percent );
    $score += (int) round( max( 0, (float) $path_percent ) * 0.20 );

    $candidate_title = strtolower( sanitize_text_field( (string) ( $candidate['title'] ?? '' ) ) );
    $missing_title = str_replace( '-', ' ', $missing_slug );
    similar_text( $candidate_title, $missing_title, $title_percent );
    $score += (int) round( max( 0, (float) $title_percent ) * 0.10 );

    $old_slugs = (array) ( $candidate['old_slugs'] ?? [] );
    if ( in_array( $missing_slug, $old_slugs, true ) ) {
      $score += 30;
    }

    return min( 100, $score );
  }

  private static function append_recent_hit_json( $current_json, $utc_datetime ) {
    $existing = json_decode( (string) $current_json, true );
    if ( ! is_array( $existing ) ) {
      $existing = [];
    }

    $timestamps = [];

    foreach ( $existing as $entry ) {
      if ( is_string( $entry ) ) {
        $candidate = sanitize_text_field( $entry );
        if ( self::is_valid_utc_datetime_string( $candidate ) ) {
          $timestamps[] = $candidate;
        }
        continue;
      }

      if ( is_array( $entry ) && isset( $entry['timestamp'] ) ) {
        $candidate = sanitize_text_field( (string) $entry['timestamp'] );
        if ( self::is_valid_utc_datetime_string( $candidate ) ) {
          $timestamps[] = $candidate;
        }
      }
    }

    $new_timestamp = sanitize_text_field( (string) $utc_datetime );
    if ( ! self::is_valid_utc_datetime_string( $new_timestamp ) ) {
      $new_timestamp = gmdate( 'Y-m-d H:i:s' );
    }

    $timestamps[] = $new_timestamp;

    if ( count( $timestamps ) > 20 ) {
      $timestamps = array_slice( $timestamps, -20 );
    }

    $encoded = wp_json_encode( array_values( $timestamps ) );
    return is_string( $encoded ) ? $encoded : '[]';
  }

  private static function backfill_recent_hits_json_if_missing( $current_json, $hit_count, $first_seen, $last_seen ) {
    $existing = json_decode( (string) $current_json, true );
    if ( is_array( $existing ) && ! empty( $existing ) ) {
      $timestamps = [];
      foreach ( $existing as $entry ) {
        if ( is_string( $entry ) && self::is_valid_utc_datetime_string( $entry ) ) {
          $timestamps[] = $entry;
        }
      }

      if ( ! empty( $timestamps ) ) {
        $timestamps = array_slice( array_values( $timestamps ), -20 );
        $encoded_existing = wp_json_encode( $timestamps );
        return is_string( $encoded_existing ) ? $encoded_existing : '[]';
      }
    }

    $fallback = sanitize_text_field( (string) $last_seen );
    if ( ! self::is_valid_utc_datetime_string( $fallback ) ) {
      $fallback = sanitize_text_field( (string) $first_seen );
    }
    if ( ! self::is_valid_utc_datetime_string( $fallback ) ) {
      $fallback = gmdate( 'Y-m-d H:i:s' );
    }

    $count = max( 1, min( 20, absint( $hit_count ) ) );
    $timestamps = array_fill( 0, $count, $fallback );
    $encoded = wp_json_encode( $timestamps );
    return is_string( $encoded ) ? $encoded : '[]';
  }

  private static function is_valid_utc_datetime_string( $value ) {
    if ( ! is_string( $value ) ) {
      return false;
    }

    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value ) ) {
      return false;
    }

    $date = DateTime::createFromFormat( 'Y-m-d H:i:s', $value, new DateTimeZone( 'UTC' ) );
    return $date instanceof DateTime && $date->format( 'Y-m-d H:i:s' ) === $value;
  }

  private static function calculate_priority_analysis( $hit_count, $last_20_hits_json, $referrer, $user_agent, $path ) {
    $timestamps = self::extract_recent_hit_timestamps( (string) $last_20_hits_json );

    $hits_24h = self::count_hits_within_hours( $timestamps, 24 );
    $hits_7d = self::count_hits_within_hours( $timestamps, 24 * 7 );
    $hits_30d = self::count_hits_within_hours( $timestamps, 24 * 30 );

    $internal_referrer = self::is_internal_referrer( $referrer );
    $search_referrer = self::is_search_referrer( $referrer );
    $external_referrer = self::is_external_referrer( $referrer );
    $homepage_referrer = self::is_homepage_referrer( $referrer );
    $bot_noise = self::is_bot_noise_signal( $user_agent, $path );

    $level = 'low';
    $priority = 0;

    if ( $hits_24h >= 15 || ( $internal_referrer && $hits_7d >= 8 ) || $search_referrer ) {
      $level = 'critical';
      $priority = 3;
    } elseif ( $hits_7d >= 8 || $external_referrer ) {
      $level = 'high';
      $priority = 2;
    } elseif ( $hits_30d >= 3 ) {
      $level = 'medium';
      $priority = 1;
    }

    if ( $bot_noise && ! $search_referrer ) {
      $level = 'low';
      $priority = 0;
    }

    if ( absint( $hit_count ) <= 2 && ! $search_referrer ) {
      $level = 'low';
      $priority = 0;
    }

    return [
      'priority' => $priority,
      'level' => $level,
      'hits_24h' => $hits_24h,
      'hits_7d' => $hits_7d,
      'hits_30d' => $hits_30d,
      'internal_referrer' => $internal_referrer,
      'search_referrer' => $search_referrer,
      'external_referrer' => $external_referrer,
      'homepage_referrer' => $homepage_referrer,
      'bot_noise' => $bot_noise,
    ];
  }

  private static function extract_recent_hit_timestamps( $last_20_hits_json ) {
    $entries = json_decode( (string) $last_20_hits_json, true );
    if ( ! is_array( $entries ) ) {
      return [];
    }

    $timestamps = [];
    foreach ( $entries as $entry ) {
      if ( is_string( $entry ) && self::is_valid_utc_datetime_string( $entry ) ) {
        $timestamps[] = $entry;
      }
    }

    return $timestamps;
  }

  private static function count_hits_within_hours( $timestamps, $hours ) {
    if ( ! is_array( $timestamps ) || empty( $timestamps ) ) {
      return 0;
    }

    $now = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
    $window_start = $now->sub( new DateInterval( 'PT' . absint( $hours ) . 'H' ) );

    $count = 0;
    foreach ( $timestamps as $ts ) {
      if ( ! is_string( $ts ) || ! self::is_valid_utc_datetime_string( $ts ) ) {
        continue;
      }

      $dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $ts, new DateTimeZone( 'UTC' ) );
      if ( $dt instanceof DateTimeImmutable && $dt >= $window_start ) {
        $count++;
      }
    }

    return $count;
  }

  private static function is_internal_referrer( $referrer ) {
    $host = wp_parse_url( home_url(), PHP_URL_HOST );
    $ref_host = wp_parse_url( (string) $referrer, PHP_URL_HOST );

    return is_string( $host )
      && '' !== $host
      && is_string( $ref_host )
      && '' !== $ref_host
      && strtolower( $host ) === strtolower( $ref_host );
  }

  private static function is_homepage_referrer( $referrer ) {
    $referrer_value = trim( (string) $referrer );
    if ( '' === $referrer_value ) {
      return false;
    }

    if ( ! self::is_internal_referrer( $referrer_value ) ) {
      return false;
    }

    $ref_path = wp_parse_url( $referrer_value, PHP_URL_PATH );
    if ( ! is_string( $ref_path ) || '' === $ref_path ) {
      return true;
    }

    return '/' === untrailingslashit( '/' . ltrim( $ref_path, '/' ) );
  }

  private static function is_external_referrer( $referrer ) {
    $ref_host = wp_parse_url( (string) $referrer, PHP_URL_HOST );
    if ( ! is_string( $ref_host ) || '' === $ref_host ) {
      return false;
    }

    return ! self::is_internal_referrer( $referrer );
  }

  private static function is_search_referrer( $referrer ) {
    $ref_host = strtolower( (string) wp_parse_url( (string) $referrer, PHP_URL_HOST ) );
    if ( '' === $ref_host ) {
      return false;
    }

    $search_hosts = [ 'google.', 'bing.', 'yahoo.', 'duckduckgo.', 'yandex.', 'baidu.' ];
    foreach ( $search_hosts as $needle ) {
      if ( false !== strpos( $ref_host, $needle ) ) {
        return true;
      }
    }

    return false;
  }

  private static function is_bot_noise_signal( $user_agent, $path ) {
    $ua = strtolower( (string) $user_agent );
    $path_value = strtolower( (string) $path );

    $ua_bot = preg_match( '/bot|spider|crawler|crawl|scanner|curl|wget|python|scrapy|httpclient/i', $ua );
    $random_path = preg_match( '/\d{4,}|[a-f0-9]{16,}|\.(php|asp|aspx|env|bak|sql)$/i', $path_value );

    return 1 === $ua_bot || 1 === $random_path;
  }

  private static function build_recommandation_text( $level, $analysis = [], $redirect_target = '' ) {
    unset( $level );
    $search_referrer = ! empty( $analysis['search_referrer'] );
    $internal_referrer = ! empty( $analysis['internal_referrer'] );
    $external_referrer = ! empty( $analysis['external_referrer'] );
    $homepage_referrer = ! empty( $analysis['homepage_referrer'] );

    if ( $homepage_referrer ) {
      $internal_referrer = false;
      $external_referrer = false;
      $search_referrer = false;
    }

    $has_referrer_signal = $search_referrer || $internal_referrer || $external_referrer;
    $has_proposed_redirect = '' !== sanitize_text_field( (string) $redirect_target );

    $referrer_message = '';
    if ( $has_proposed_redirect ) {
      $referrer_message = 'PROPOSED_REDIRECT_URL: Suggested redirect is available. Validate target and publish redirect.';
    } elseif ( ! $has_referrer_signal ) {
      $referrer_message = 'HOTFIX_BROKEN_URL: Referer is null and proposed redirect is not found. Apply immediate fix by creating redirect to the best matching live page.';
    } elseif ( $search_referrer ) {
      $referrer_message = 'FIND_REDIRECT_PAGE: Referer is found and proposed redirect is not found. High SEO impact traffic from search engine. Find destination page and publish 301 redirect.';
    } elseif ( $internal_referrer ) {
      $referrer_message = 'HOTFIX_BROKEN_URL: Referer is internal and proposed redirect is not found. Fix internal source link and map to correct destination page.';
    } elseif ( $external_referrer ) {
      $referrer_message = 'FIND_REDIRECT_PAGE: Referer is found and proposed redirect is not found. External source detected. Find destination page and create redirect.';
    }

    $payload = [
      $referrer_message,
    ];

    $encoded = wp_json_encode( $payload );
    return is_string( $encoded ) ? $encoded : '[""]';
  }

  private static function normalize_path( $path ) {
    $path = strtolower( sanitize_text_field( (string) $path ) );
    $path = strtok( $path, '?' );
    $path = strtok( (string) $path, '#' );
    $path = '/' . ltrim( (string) $path, '/' );

    if ( '/' !== $path ) {
      $path = untrailingslashit( $path );
    }

    return $path;
  }

  private static function is_excluded_path( $path ) {
    if ( strlen( (string) $path ) > 1024 ) {
      return true;
    }

    $excluded = [
      '/wp-admin/',
      '/wp-login.php',
      '/xmlrpc.php',
      '/favicon.ico',
    ];

    foreach ( $excluded as $exclude ) {
      if ( '/' === substr( $exclude, -1 ) ) {
        if ( strpos( $path, $exclude ) === 0 ) {
          return true;
        }
      } elseif ( $path === $exclude ) {
        return true;
      }
    }

    if ( preg_match( '/\.(?:css|js|map|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot|pdf|txt|xml)$/i', $path ) ) {
      return true;
    }

    return false;
  }

  private static function get_client_ip() {
    if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
      $remote_addr = sanitize_text_field( (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
      if ( filter_var( $remote_addr, FILTER_VALIDATE_IP ) ) {
        return $remote_addr;
      }
    }

    return '';
  }

  private static function is_feature_enabled() {
    self::ensure_monitor_options();
    return ! empty( get_option( self::ENABLED_OPTION, 0 ) );
  }

  private static function ensure_monitor_options() {
    if ( false === get_option( self::ENABLED_OPTION, false ) ) {
      add_option( self::ENABLED_OPTION, 0 );
    }

    if ( false === get_option( self::COLLECTING_OPTION, false ) ) {
      add_option( self::COLLECTING_OPTION, 1 );
    }

    if ( false === get_option( self::FIRST_TIME_OPTION, false ) ) {
      add_option( self::FIRST_TIME_OPTION, 1 );
    }

    if ( false === get_option( self::THROTTLE_LIMIT_OPTION, false ) ) {
      add_option( self::THROTTLE_LIMIT_OPTION, 30 );
    }

    if ( false === get_option( self::THROTTLE_WINDOW_OPTION, false ) ) {
      add_option( self::THROTTLE_WINDOW_OPTION, 60 );
    }

    if ( false === get_option( self::ANALYSIS_CRON_FREQUENCY_OPTION, false ) ) {
      add_option( self::ANALYSIS_CRON_FREQUENCY_OPTION, 'disabled' );
    }

    if ( false === get_option( self::EXCLUDE_URLS_OPTION, false ) ) {
      add_option( self::EXCLUDE_URLS_OPTION, '' );
    }

    if ( false === get_option( self::EXCLUDE_KEYWORDS_OPTION, false ) ) {
      add_option( self::EXCLUDE_KEYWORDS_OPTION, '' );
    }

    if ( false === get_option( self::IGNORE_QUERY_PARAMS_OPTION, false ) ) {
      add_option( self::IGNORE_QUERY_PARAMS_OPTION, 0 );
    }

  }

  private static function is_within_throttle_limit( $path, $ip_hash ) {
    $limit = self::get_throttle_limit();
    $window = self::get_throttle_window();

    $identity = '' !== $ip_hash ? $ip_hash : 'anonymous';
    $throttle_key = 'asn404th_' . substr( hash( 'sha256', $identity . '|' . (string) $path ), 0, 32 );
    $current_count = (int) get_transient( $throttle_key );

    if ( $current_count >= $limit ) {
      return false;
    }

    set_transient( $throttle_key, $current_count + 1, $window );
    return true;
  }

  private static function get_throttle_limit() {
    self::ensure_monitor_options();
    return self::normalize_throttle_limit( get_option( self::THROTTLE_LIMIT_OPTION, 30 ) );
  }

  private static function get_throttle_window() {
    self::ensure_monitor_options();
    return self::normalize_throttle_window( get_option( self::THROTTLE_WINDOW_OPTION, 60 ) );
  }

  private static function normalize_throttle_limit( $value ) {
    $value = absint( $value );
    if ( $value < 1 ) {
      $value = 1;
    }
    if ( $value > 500 ) {
      $value = 500;
    }
    return $value;
  }

  private static function normalize_throttle_window( $value ) {
    $value = absint( $value );
    if ( $value < 10 ) {
      $value = 10;
    }
    if ( $value > 3600 ) {
      $value = 3600;
    }
    return $value;
  }

  private static function get_analysis_cron_frequency() {
    self::ensure_monitor_options();
    return self::normalize_analysis_cron_frequency( get_option( self::ANALYSIS_CRON_FREQUENCY_OPTION, 'disabled' ) );
  }

  private static function normalize_analysis_cron_frequency( $value ) {
    $value = sanitize_key( (string) $value );
    if ( ! in_array( $value, [ 'disabled', 'hourly', 'daily', 'weekly', 'monthly' ], true ) ) {
      $value = 'disabled';
    }
    return $value;
  }

  private static function is_wp_cron_enabled() {
    return ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON );
  }

  private static function normalize_exclusions_text( $value ) {
    $value = sanitize_textarea_field( (string) $value );
    $value = str_replace( [ "\r\n", "\r" ], "\n", $value );
    $lines = array_filter( array_map( 'trim', explode( "\n", $value ) ) );
    $clean = [];
    foreach ( $lines as $line ) {
      $line = substr( sanitize_text_field( $line ), 0, 255 );
      if ( '' !== $line ) {
        $clean[] = $line;
      }
    }
    return implode( "\n", array_slice( $clean, 0, 100 ) );
  }

  private static function get_exclude_urls_text() {
    self::ensure_monitor_options();
    return (string) get_option( self::EXCLUDE_URLS_OPTION, '' );
  }

  private static function get_exclude_keywords_text() {
    self::ensure_monitor_options();
    return (string) get_option( self::EXCLUDE_KEYWORDS_OPTION, '' );
  }

  private static function should_ignore_query_params() {
    self::ensure_monitor_options();
    return ! empty( get_option( self::IGNORE_QUERY_PARAMS_OPTION, 0 ) );
  }

  private static function get_exclusion_entries( $text ) {
    $text = str_replace( [ "\r\n", "\r" ], "\n", (string) $text );
    $lines = array_filter( array_map( 'trim', explode( "\n", $text ) ) );
    return array_values( array_slice( $lines, 0, 100 ) );
  }

  private static function is_excluded_by_custom_rules( $requested_url, $path ) {
    $url = strtolower( (string) $requested_url );
    $path = strtolower( (string) $path );

    $exclude_urls = self::get_exclusion_entries( self::get_exclude_urls_text() );
    foreach ( $exclude_urls as $pattern ) {
      $pattern = strtolower( $pattern );
      if ( self::matches_exclusion_pattern( $url, $pattern ) || self::matches_exclusion_pattern( $path, $pattern ) ) {
        return true;
      }
    }

    $exclude_keywords = self::get_exclusion_entries( self::get_exclude_keywords_text() );
    foreach ( $exclude_keywords as $keyword ) {
      $keyword = strtolower( trim( $keyword ) );
      if ( '' !== $keyword && ( false !== strpos( $url, $keyword ) || false !== strpos( $path, $keyword ) ) ) {
        return true;
      }
    }

    return false;
  }

  private static function matches_exclusion_pattern( $subject, $pattern ) {
    if ( '' === $pattern ) {
      return false;
    }

    if ( false !== strpos( $pattern, '*' ) ) {
      $regex = '/^' . str_replace( '\\*', '.*', preg_quote( $pattern, '/' ) ) . '$/i';
      return 1 === preg_match( $regex, $subject );
    }

    return false !== strpos( $subject, $pattern );
  }

  private static function is_log_limit_reached() {
    return self::get_current_log_count() >= self::MAX_LOG_RECORDS;
  }

  private static function has_existing_log_entry( $path, $method ) {
    global $wpdb;

    $path = self::normalize_path( (string) $path );
    $method = strtoupper( sanitize_text_field( (string) $method ) );

    if ( '' === $path || '' === $method ) {
      return false;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared query for existing 404 key lookup.
    $id = $wpdb->get_var(
      $wpdb->prepare(
        'SELECT id FROM ' . esc_sql( self::table_name() ) . ' WHERE path = %s AND method = %s LIMIT 1',
        $path,
        $method
      )
    );

    return ! empty( $id );
  }

  private static function get_current_log_count() {
    global $wpdb;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared query for fixed-capacity 404 log limit.
    return (int) $wpdb->get_var(
      $wpdb->prepare(
        'SELECT COUNT(*) FROM ' . esc_sql( self::table_name() ) . ' WHERE status IN (%s, %s, %s)',
        'active',
        'ignored',
        'redirected'
      )
    );
  }

  private static function sanitize_redirect_target( $url ) {
    $target = trim( sanitize_text_field( (string) $url ) );
    if ( '' === $target ) {
      return '';
    }

    if ( 0 === strpos( $target, '/' ) ) {
      if ( 0 === strpos( $target, '//' ) ) {
        return '';
      }

      return '/' . ltrim( $target, '/' );
    }

    $target = esc_url_raw( $target );
    if ( ! is_string( $target ) || '' === $target ) {
      return '';
    }

    return $target;
  }

  private static function is_safe_redirect_target( $url ) {
    if ( empty( $url ) ) {
      return false;
    }

    $target = self::sanitize_redirect_target( $url );
    if ( '' === $target ) {
      return false;
    }

    if ( 0 === strpos( $target, '/' ) && 0 !== strpos( $target, '//' ) ) {
      return true;
    }

    $host = wp_parse_url( home_url(), PHP_URL_HOST );
    $target_host = wp_parse_url( $target, PHP_URL_HOST );

    return is_string( $host )
      && '' !== $host
      && is_string( $target_host )
      && '' !== $target_host
      && strtolower( $host ) === strtolower( $target_host );
  }

  private static function is_valid_date( $date ) {
    if ( ! is_string( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
      return false;
    }

    $parts = explode( '-', $date );
    if ( 3 !== count( $parts ) ) {
      return false;
    }

    return checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] );
  }

  private static function hash_client_ip() {
    $ip = self::get_client_ip();
    if ( '' === $ip ) {
      return '';
    }

    return hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
  }

  private static function sanitize_log_row( $row ) {
    $requested_url = esc_url_raw( (string) ( $row['requested_url'] ?? '' ) );
    $last_20_hits_json = sanitize_textarea_field( (string) ( $row['last_20_hits_json'] ?? '[]' ) );

    return [
      'id' => absint( $row['id'] ?? 0 ),
      'url' => $requested_url,
      'requested_url' => $requested_url,
      'path' => sanitize_text_field( (string) ( $row['path'] ?? '' ) ),
      'method' => sanitize_text_field( (string) ( $row['method'] ?? 'GET' ) ),
      'referrer' => esc_url_raw( (string) ( $row['referrer'] ?? '' ) ),
      'user_agent' => sanitize_text_field( (string) ( $row['user_agent'] ?? '' ) ),
      'ip_hash' => sanitize_text_field( (string) ( $row['ip_hash'] ?? '' ) ),
      'hit_count' => absint( $row['hit_count'] ?? 0 ),
      'status' => sanitize_key( (string) ( $row['status'] ?? 'active' ) ),
      'last_20_hits_json' => '' !== $last_20_hits_json ? $last_20_hits_json : '[]',
      'priority' => absint( $row['priority'] ?? 0 ),
      'recommandation' => sanitize_textarea_field( (string) ( $row['recommandation'] ?? '' ) ),
      'redirect_target' => esc_url_raw( (string) ( $row['redirect_target'] ?? '' ) ),
      'first_seen' => sanitize_text_field( (string) ( $row['first_seen'] ?? '' ) ),
      'last_seen' => sanitize_text_field( (string) ( $row['last_seen'] ?? '' ) ),
      'last_analysed' => sanitize_text_field( (string) ( $row['last_analysed'] ?? '' ) ),
      'resolved_at' => sanitize_text_field( (string) ( $row['resolved_at'] ?? '' ) ),
    ];
  }

  private static function sanitize_csv_cell( $value ) {
    $value = (string) $value;
    if ( '' === $value ) {
      return '';
    }

    $first_char = substr( $value, 0, 1 );
    if ( in_array( $first_char, [ '=', '+', '-', '@' ], true ) ) {
      return "'" . $value;
    }

    return $value;
  }
}
