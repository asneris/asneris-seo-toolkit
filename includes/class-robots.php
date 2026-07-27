<?php
/**
 * Robots.txt Editor & Validator
 *
 * @package Asneris_SEO_Toolkit
 */

if (!defined('ABSPATH')) {
    exit;
}

class ASNERISSEO_Robots {
    
    private static $robots_file;
    private static $validation_results = [];
    
    public static function init() {
        self::$robots_file = ABSPATH . 'robots.txt';
        add_action('admin_post_ASNERISSEO_save_robots', [__CLASS__, 'save_robots']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    public static function register_menu() {
        add_submenu_page(
            ASNERIS_MENU_SLUG,
            __('Robots.txt', 'asneris-seo-toolkit'),
            __('Robots.txt', 'asneris-seo-toolkit'),
            'manage_options',
            ASNERIS_MENU_SLUG . '-robots',
            [__CLASS__, 'render_page']
        );
    }
    
    public static function enqueue_assets($hook) {
        // WordPress uses sanitized menu TITLE (not slug) as parent identifier
        if ($hook !== 'asneris-seo-toolkit_page_' . ASNERIS_MENU_SLUG . '-robots') {
            return;
        }

        $react_asset_path = ASNERISSEO_DIR . 'build/admin/index.asset.php';
        if (file_exists($react_asset_path)) {
            $react_asset = include $react_asset_path;
            wp_enqueue_script(
                'asnerisseo-admin-dashboard',
                ASNERISSEO_URL . 'build/admin/index.js',
                $react_asset['dependencies'],
                $react_asset['version'],
                true
            );

            $summary_payload = ASNERISSEO_Dashboard::get_dashboard_summary_payload();
            wp_localize_script('asnerisseo-admin-dashboard', 'asnerisseoAdminDashboardData', [
                'summary' => $summary_payload,
                'dashboardSummaryRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/dashboard-summary' ) ),
                'socialSettingsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/social' ) ),
                'schemaSettingsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/schema' ) ),
                'indexNowSettingsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/indexnow' ) ),
                'pageDiagnosticsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/page-diagnostics/overview' ) ),
                'diagnosticsUrlRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/diagnostics-url' ) ),
                'siteDiagnosticsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-diagnostics' ) ),
                'siteDiagnosticsUrlCheckRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-diagnostics/url-check' ) ),
                'redirectsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/redirects' ) ),
                'robotsRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/robots' ) ),
                'bulkEditContentRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/bulk-edit/content' ) ),
                'bulkEditSaveRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/bulk-edit/save' ) ),
                'restNonce' => wp_create_nonce( 'wp_rest' ),
                'mountSelector' => '.asnerisseo-fallback-robots',
                'hideFallback' => true,
            ]);
        }
        
        wp_enqueue_style(
            'asnerisseo-admin-style',
            plugins_url('../assets/css/admin-style.css', __FILE__),
            [],
            ASNERISSEO_VERSION
        );
        
        wp_enqueue_script(
            'asnerisseo-robots',
            plugins_url('../assets/js/robots.js', __FILE__),
            ['jquery'],
            ASNERISSEO_VERSION,
            true
        );
    }
    
    /**
     * Validate robots.txt
     */
    public static function validate() {
        $results = [
            'status' => 'success',
            'checks' => [],
            'warnings' => [],
            'errors' => []
        ];

        $has_content = false;
        $content = '';
        $has_sitemap = false;
        $sitemap_url = '';
        
        // Check 1: robots.txt exists
        $exists = file_exists(self::$robots_file);
        $results['checks']['exists'] = [
            'status' => $exists ? 'pass' : 'fail',
            'label' => 'robots.txt file',
            'message' => $exists ? 'robots.txt file found' : 'Not found on your site'
        ];
        
        if (!$exists) {
            if ($results['status'] !== 'error') {
                $results['status'] = 'warning';
            }
            $results['warnings'][] = 'No robots.txt file found. Search engines will crawl all accessible pages by default.';
        }
        
        // Check 2: HTTP 200 response
        $response = wp_remote_get(home_url('/robots.txt'));
        if (is_wp_error($response)) {
            $results['status'] = 'error';
            $results['checks']['http_200'] = [
                'status' => 'fail',
                'label' => 'HTTP 200 response',
                'message' => 'Could not fetch robots.txt: ' . $response->get_error_message()
            ];
            $results['errors'][] = 'Could not fetch robots.txt: ' . $response->get_error_message();
        } else {
            $status_code = wp_remote_retrieve_response_code($response);
            $is_200 = $status_code === 200;

            $results['checks']['http_200'] = [
                'status' => $is_200 ? 'pass' : 'fail',
                'label' => 'HTTP 200 response',
                'message' => $is_200 ? 'robots.txt is accessible' : "robots.txt returns HTTP $status_code"
            ];

            if (!$is_200) {
                $results['status'] = 'error';
                $results['errors'][] = "robots.txt is not accessible (HTTP $status_code)";
            }
        }
        
        // Get content
        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        if ( $exists ) {
            $content = $wp_filesystem->get_contents( self::$robots_file );
            if ( false === $content ) {
                $results['status'] = 'error';
                $results['errors'][] = 'Could not read robots.txt file contents.';
                $content = '';
            } else {
                $has_content = true;
            }
        }
        
        // Check 3: No sitewide crawl block
        if ( $has_content ) {
            $has_sitewide_block = preg_match('/User-agent:\s*\*\s+Disallow:\s*\/\s*$/im', $content);
            $results['checks']['sitewide_block'] = [
                'status' => $has_sitewide_block ? 'fail' : 'pass',
                'label' => 'No sitewide crawl block',
                'message' => $has_sitewide_block ? 'Found: Disallow: / - blocks all crawling' : 'No sitewide block found'
            ];

            if ($has_sitewide_block) {
                $results['status'] = 'error';
                $results['errors'][] = 'Sitewide crawl block detected. Search engines cannot crawl any content.';
            }
        } else {
            $results['checks']['sitewide_block'] = [
                'status' => 'fail',
                'label' => 'No sitewide crawl block',
                'message' => 'Could not validate because robots.txt content is unavailable'
            ];
        }
        
        // Check 4: Required assets allowed (CSS, JS, images)
        if ( $has_content ) {
            $blocks_assets = preg_match('/Disallow:\s*\/wp-content\//i', $content) || 
                            preg_match('/Disallow:\s*\*\.css/i', $content) ||
                            preg_match('/Disallow:\s*\*\.js/i', $content);

            $results['checks']['assets_allowed'] = [
                'status' => $blocks_assets ? 'warning' : 'pass',
                'label' => 'Required assets allowed',
                'message' => $blocks_assets ? 'Warning: CSS/JS files may be blocked' : 'CSS and JS files are accessible'
            ];

            if ($blocks_assets) {
                if ($results['status'] !== 'error') {
                    $results['status'] = 'warning';
                }
                $results['warnings'][] = 'Blocking CSS/JS assets can hurt SEO. Google needs these to render pages correctly.';
            }
        } else {
            $results['checks']['assets_allowed'] = [
                'status' => 'warning',
                'label' => 'Required assets allowed',
                'message' => 'Could not validate because robots.txt content is unavailable'
            ];
        }
        
        // Check 5: Sitemap declared
        if ( $has_content ) {
            $has_sitemap = preg_match('/Sitemap:\s*(.+)/i', $content, $matches);
            $sitemap_url = $has_sitemap ? trim( $matches[1] ) : '';
            $results['checks']['sitemap_declared'] = [
                'status' => $has_sitemap ? 'pass' : 'warning',
                'label' => 'Sitemap declared',
                'message' => $has_sitemap ? 'Sitemap URL declared: ' . $sitemap_url : 'No sitemap declared in robots.txt'
            ];

            if (!$has_sitemap) {
                if ($results['status'] === 'success') {
                    $results['status'] = 'warning';
                }
                $results['warnings'][] = 'Add a Sitemap directive to help search engines discover your content.';
            }
        } else {
            $results['checks']['sitemap_declared'] = [
                'status' => 'warning',
                'label' => 'Sitemap declared',
                'message' => 'Could not validate because robots.txt content is unavailable'
            ];
        }
        
        // Check 6: Sitemap reachable (if declared)
        if ($has_sitemap && '' !== $sitemap_url) {
            $sitemap_response = wp_remote_get($sitemap_url);
            if (is_wp_error($sitemap_response)) {
                $results['checks']['sitemap_reachable'] = [
                    'status' => 'fail',
                    'label' => 'Sitemap reachable',
                    'message' => 'Could not fetch sitemap: ' . $sitemap_response->get_error_message()
                ];
                if ($results['status'] !== 'error') {
                    $results['status'] = 'warning';
                }
                $results['warnings'][] = 'Could not fetch sitemap: ' . $sitemap_response->get_error_message();
            } else {
                $sitemap_status = wp_remote_retrieve_response_code($sitemap_response);
                $sitemap_reachable = $sitemap_status === 200;
                
                $results['checks']['sitemap_reachable'] = [
                    'status' => $sitemap_reachable ? 'pass' : 'fail',
                    'label' => 'Sitemap reachable',
                    'message' => $sitemap_reachable ? 'Sitemap is accessible' : "Sitemap returns HTTP $sitemap_status"
                ];
                
                if (!$sitemap_reachable) {
                    if ($results['status'] !== 'error') {
                        $results['status'] = 'warning';
                    }
                    $results['warnings'][] = "Declared sitemap is not accessible (HTTP $sitemap_status)";
                }
            }
        } elseif ( ! $has_content ) {
            $results['checks']['sitemap_reachable'] = [
                'status' => 'warning',
                'label' => 'Sitemap reachable',
                'message' => 'Skipped because robots.txt content is unavailable'
            ];
        } else {
            $results['checks']['sitemap_reachable'] = [
                'status' => 'warning',
                'label' => 'Sitemap reachable',
                'message' => 'Skipped because no Sitemap directive was declared'
            ];
        }
        
        // Check 7: No conflicting rules
        if ( $has_content ) {
            $has_conflicts = self::check_conflicts($content);
            $results['checks']['no_conflicts'] = [
                'status' => $has_conflicts ? 'warning' : 'pass',
                'label' => 'No conflicting rules',
                'message' => $has_conflicts ? 'Potential conflicting rules detected' : 'No obvious conflicts found'
            ];

            if ($has_conflicts) {
                if ($results['status'] === 'success') {
                    $results['status'] = 'warning';
                }
                $results['warnings'][] = 'Check for conflicting Allow/Disallow rules that may cause unexpected behavior.';
            }
        } else {
            $results['checks']['no_conflicts'] = [
                'status' => 'warning',
                'label' => 'No conflicting rules',
                'message' => 'Could not validate because robots.txt content is unavailable'
            ];
        }
        
        self::$validation_results = $results;
        return $results;
    }
    
    /**
     * Check for conflicting rules
     */
    private static function check_conflicts($content) {
        $lines = preg_split('/\r\n|\r|\n/', (string) $content);
        $current_user_agent = '*';
        $rules_by_agent = [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s*#.*$/', '', $line));
            if ($line === '') {
                continue;
            }

            if (stripos($line, 'User-agent:') === 0) {
                $ua = trim(substr($line, strlen('User-agent:')));
                $current_user_agent = $ua !== '' ? strtolower($ua) : '*';
                if (!isset($rules_by_agent[$current_user_agent])) {
                    $rules_by_agent[$current_user_agent] = ['allow' => [], 'disallow' => []];
                }
                continue;
            }

            if (stripos($line, 'Allow:') === 0) {
                $path = trim(substr($line, strlen('Allow:')));
                if ($path !== '') {
                    $rules_by_agent[$current_user_agent]['allow'][] = $path;
                }
                continue;
            }

            if (stripos($line, 'Disallow:') === 0) {
                $path = trim(substr($line, strlen('Disallow:')));
                if ($path !== '') {
                    $rules_by_agent[$current_user_agent]['disallow'][] = $path;
                }
            }
        }

        foreach ($rules_by_agent as $rules) {
            $overlap = array_intersect(
                array_unique($rules['allow']),
                array_unique($rules['disallow'])
            );
            if (!empty($overlap)) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Get default robots.txt content
     */
    private static function get_default_content() {
        $sitemap_url = home_url('/wp-sitemap.xml');
        
        return "# Default robots.txt for WordPress
# Generated by Asneris SEO Toolkit

User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

# Sitemap location
Sitemap: {$sitemap_url}
";
    }
    
    /**
     * Save robots.txt
     */
    public static function save_robots() {
        check_admin_referer('ASNERISSEO_save_robots');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Intentionally accessing raw value to detect dangerous patterns before sanitization strips them
        $content = isset($_POST['robots_content']) ? wp_unslash($_POST['robots_content']) : '';
        
        // Validation: Check for dangerous patterns BEFORE sanitization
        $validation_errors = [];
        
        // Check for script tags and PHP code
        if (preg_match('/<script|<\?php|javascript:/i', $content)) {
            $validation_errors[] = 'Invalid content: Script tags and code are not allowed in robots.txt';
        }
        
        // Check for executable file extensions
        if (preg_match('/\.(ps1|exe|bat|cmd|sh)\b/i', $content)) {
            $validation_errors[] = 'Invalid content: Executable file references are not allowed';
        }
        
        // Check for HTML tags (robots.txt should be plain text only)
        if (preg_match('/<[a-z][\s\S]*>/i', $content)) {
            $validation_errors[] = 'Invalid content: HTML tags are not allowed in robots.txt';
        }
        
        // Validate robots.txt syntax (strict directive check)
        $lines = explode("\n", $content);
        foreach ($lines as $line_number => $line) {
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            // Strict directive validation: every non-empty, non-comment line must be a known valid directive
            if (!preg_match('/^(User-agent|Allow|Disallow|Sitemap|Crawl-delay)\s*:\s*.+$/i', $line)) {
                $validation_errors[] = sprintf(
                    'Line %d is not a valid robots.txt directive: "%s". Allowed directives: User-agent, Allow, Disallow, Sitemap, Crawl-delay.',
                    $line_number + 1,
                    strlen($line) > 80 ? substr($line, 0, 80) . '…' : $line
                );
            }
            
            // Check for suspicious URLs (non-standard protocols)
            if (preg_match('/\b(file|ftp|data|tel|javascript):/i', $line)) {
                $validation_errors[] = 'Line ' . ($line_number + 1) . ' contains suspicious protocol - only http/https URLs are recommended';
            }
        }
        
        // Block save if validation errors exist
        if (!empty($validation_errors)) {
            wp_safe_redirect(add_query_arg([
                'page' => ASNERIS_MENU_SLUG . '-robots',
                'validation_error' => '1',
                'error_msg' => urlencode(implode(' | ', $validation_errors))
            ], admin_url('admin.php')));
            exit;
        }
        
        // Sanitize content after validation (removes any remaining unwanted characters)
        $content = sanitize_textarea_field($content);
        
        // Save to file using WP_Filesystem
        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        $saved = $wp_filesystem->put_contents( self::$robots_file, $content, FS_CHMOD_FILE );
        
        if ($saved !== false) {
            wp_safe_redirect(add_query_arg([
                'page' => ASNERIS_MENU_SLUG . '-robots',
                'saved' => '1'
            ], admin_url('admin.php')));
        } else {
            wp_safe_redirect(add_query_arg([
                'page' => ASNERIS_MENU_SLUG . '-robots',
                'error' => '1'
            ], admin_url('admin.php')));
        }
        exit;
    }
    
    /**
     * Render page
     */
    public static function render_page() {
        // Get current content
        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        $content = '';
        if (file_exists(self::$robots_file)) {
            $content = $wp_filesystem->get_contents( self::$robots_file );
        } else {
            $content = self::get_default_content();
        }
        
        // Run validation
        $validation = self::validate();
        
        // Check for save status
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of status flags set after nonce-verified save_robots action
        $saved = isset( $_GET['saved'] ) && sanitize_key( $_GET['saved'] ) === '1';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of status flags set after nonce-verified save_robots action
        $error = isset( $_GET['error'] ) && sanitize_key( $_GET['error'] ) === '1';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of validation errors from nonce-verified save
        $validation_error = isset( $_GET['validation_error'] ) && sanitize_key( $_GET['validation_error'] ) === '1';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of error message from nonce-verified save
        $error_msg = isset( $_GET['error_msg'] ) ? urldecode( sanitize_text_field( wp_unslash( $_GET['error_msg'] ) ) ) : '';
        
        ?>
        <div class="wrap ASNERISSEO-admin-wrap">
            <div id="asnerisseo-react-admin-shell-root"></div>
            <?php if ( defined( 'ASNERISSEO_REACT_ONLY_ADMIN' ) && ASNERISSEO_REACT_ONLY_ADMIN ) { ?></div><?php return; } ?>
            <div class="asnerisseo-fallback-robots">
            <h1>
                <?php esc_html_e('Robots.txt Editor & Validator', 'asneris-seo-toolkit'); ?>
                <?php ASNERISSEO_Help_Modal::render_help_icon('robots-overview', 'Learn about robots.txt'); ?>
            </h1>
            <p class="ASNERISSEO-subtitle"><?php esc_html_e('Control which parts of your site search engines are allowed to visit.', 'asneris-seo-toolkit'); ?></p>
            
            <?php if ($saved): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('robots.txt saved successfully!', 'asneris-seo-toolkit'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($validation_error): ?>
                <div class="notice notice-error is-dismissible" style="border-left-color: #dc3232;">
                    <p><strong><?php esc_html_e('Validation Error:', 'asneris-seo-toolkit'); ?></strong></p>
                    <p><?php echo esc_html($error_msg); ?></p>
                    <p><em><?php esc_html_e('No changes were saved.', 'asneris-seo-toolkit'); ?></em></p>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php esc_html_e('Failed to save robots.txt. Check file permissions.', 'asneris-seo-toolkit'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="ASNERISSEO-settings-form">
                <div class="ASNERISSEO-tab-content">
                    
                    <!-- Validation Status -->
                    <div class="ASNERISSEO-validation-status" style="margin-bottom: 24px;">
                        <?php if ($validation['status'] === 'success'): ?>
                            <div class="notice notice-success inline" style="margin: 0; padding: 16px;">
                                <p style="margin: 0; font-weight: 500;">
                                    ✓ <?php esc_html_e('robots.txt is accessible, valid, and does not block important paths.', 'asneris-seo-toolkit'); ?>
                                </p>
                            </div>
                        <?php elseif ($validation['status'] === 'warning'): ?>
                            <div class="notice notice-warning inline" style="margin: 0; padding: 16px;">
                                <p style="margin: 0 0 8px 0; font-weight: 500;">⚠ <?php esc_html_e('robots.txt has warnings:', 'asneris-seo-toolkit'); ?></p>
                                <ul style="margin: 0; padding-left: 20px;">
                                    <?php foreach ($validation['warnings'] as $warning): ?>
                                        <li><?php echo esc_html($warning); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="notice notice-error inline" style="margin: 0; padding: 16px;">
                                <p style="margin: 0 0 8px 0; font-weight: 500;">✕ <?php esc_html_e('robots.txt has errors:', 'asneris-seo-toolkit'); ?></p>
                                <ul style="margin: 0; padding-left: 20px;">
                                    <?php foreach ($validation['errors'] as $error_msg): ?>
                                        <li><?php echo esc_html($error_msg); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Validation Checklist -->
                    <div class="ASNERISSEO-validation-checks" style="margin-bottom: 24px;">
                        <h2>
                            <?php esc_html_e('Validation Checks', 'asneris-seo-toolkit'); ?>
                            <?php ASNERISSEO_Help_Modal::render_help_icon('robots-validation', 'Learn about robots.txt validation'); ?>
                        </h2>
                        <table class="wp-list-table widefat striped">
                            <tbody>
                                <?php foreach ($validation['checks'] as $check): ?>
                                    <tr>
                                        <td style="width: 40px; text-align: center;">
                                            <?php if ($check['status'] === 'pass'): ?>
                                                <span style="color: #46b450; font-size: 18px;">✓</span>
                                            <?php elseif ($check['status'] === 'warning'): ?>
                                                <span style="color: #dba617; font-size: 18px;">⚠</span>
                                            <?php else: ?>
                                                <span style="color: #d63638; font-size: 18px;">✕</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo esc_html($check['label']); ?></strong></td>
                                        <td><?php echo esc_html($check['message']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Editor -->
                    <div class="ASNERISSEO-robots-editor">
                        <h2>
                            <?php esc_html_e('Edit robots.txt', 'asneris-seo-toolkit'); ?>
                            <?php ASNERISSEO_Help_Modal::render_help_icon('robots-syntax', 'Learn about robots.txt syntax'); ?>
                        </h2>
                        
                        <p class="description" style="margin-bottom: 12px;">
                            <strong><?php esc_html_e('Controls which URLs search engines are allowed to crawl.', 'asneris-seo-toolkit'); ?></strong><br>
                            <?php esc_html_e('It does not control rankings or guarantee indexing.', 'asneris-seo-toolkit'); ?>
                        </p>
                        
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('ASNERISSEO_save_robots'); ?>
                            <input type="hidden" name="action" value="ASNERISSEO_save_robots">
                            
                            <textarea 
                                name="robots_content" 
                                rows="20" 
                                style="width: 100%; font-family: monospace; font-size: 13px; padding: 12px;"
                                spellcheck="false"
                            ><?php echo esc_textarea($content); ?></textarea>
                            
                            <p style="margin-top: 12px;">
                                <button type="submit" class="button button-primary button-large">
                                    <?php esc_html_e('Save robots.txt', 'asneris-seo-toolkit'); ?>
                                </button>
                                
                                <a href="<?php echo esc_url(home_url('/robots.txt')); ?>" target="_blank" class="button" style="margin-left: 8px;" title="<?php esc_attr_e('Opens the active robots.txt file as seen by search engines.', 'asneris-seo-toolkit'); ?>">
                                    <?php esc_html_e('View Live File', 'asneris-seo-toolkit'); ?>
                                </a>
                            </p>
                        </form>
                        
                        <!-- Safe Defaults Info -->
                        <div style="margin-top: 24px; padding: 16px; background: #f6f7f7; border-left: 4px solid #00a0d2;">
                            <h3 style="margin-top: 0;">
                                <?php esc_html_e('Recommended Safe Defaults', 'asneris-seo-toolkit'); ?>
                                <?php ASNERISSEO_Help_Modal::render_help_icon('robots-best-practices', 'Learn about best practices'); ?>
                            </h3>
                            <p><?php esc_html_e('If you\'re unsure, use these safe defaults:', 'asneris-seo-toolkit'); ?></p>
                            <ul style="list-style: disc; padding-left: 20px;">
                                <li><?php esc_html_e('Block /wp-admin/ except admin-ajax.php', 'asneris-seo-toolkit'); ?></li>
                                <li><?php esc_html_e('Block /wp-includes/ (system files)', 'asneris-seo-toolkit'); ?></li>
                                <li><?php esc_html_e('Allow all public content (no Disallow: /)', 'asneris-seo-toolkit'); ?></li>
                                <li><?php esc_html_e('Include your sitemap URL', 'asneris-seo-toolkit'); ?></li>
                            </ul>
                        </div>
                    </div>
                    
                </div><!-- .ASNERISSEO-tab-content -->
            </div><!-- .ASNERISSEO-settings-form -->
                
            <?php // ASNERISSEO_Help_Content::render_sidebar('robots-txt'); ?>
            </div><!-- .asnerisseo-fallback-robots -->
        </div><!-- .wrap -->
        <?php ASNERISSEO_Help_Modal::render_modals('robots-txt'); ?>
        <?php
    }
    
    /**
     * Get validation results for external use (Validation page)
     */
    public static function get_validation_results() {
        if (empty(self::$validation_results)) {
            return self::validate();
        }
        return self::$validation_results;
    }
}
