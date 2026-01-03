<?php
/**
 * Robots.txt Editor & Validator
 *
 * @package Clarity_First_SEO
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFSEO_Robots {
    
    private static $robots_file;
    private static $validation_results = [];
    
    public static function init() {
        self::$robots_file = ABSPATH . 'robots.txt';
        add_action('admin_post_CFSEO_save_robots', [__CLASS__, 'save_robots']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    public static function register_menu() {
        add_submenu_page(
            'clarity-first-seo',
            __('Robots.txt', 'cfseo'),
            __('Robots.txt', 'cfseo'),
            'manage_options',
            'clarity-first-seo-robots',
            [__CLASS__, 'render_page']
        );
    }
    
    public static function enqueue_assets($hook) {
        if ($hook !== 'clarity-first-seo_page_clarity-first-seo-robots') {
            return;
        }
        
        wp_enqueue_style(
            'cfseo-admin-style',
            plugins_url('../assets/css/admin-style.css', __FILE__),
            [],
            CFSEO_VERSION
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
        
        // Check 1: robots.txt exists
        $exists = file_exists(self::$robots_file);
        $results['checks']['exists'] = [
            'status' => $exists ? 'pass' : 'fail',
            'label' => 'robots.txt exists',
            'message' => $exists ? 'robots.txt file found' : 'robots.txt file not found'
        ];
        
        if (!$exists) {
            $results['status'] = 'warning';
            $results['warnings'][] = 'No robots.txt file. Search engines will assume full access.';
            return $results;
        }
        
        // Check 2: HTTP 200 response
        $response = wp_remote_get(home_url('/robots.txt'));
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
            return $results;
        }
        
        // Get content
        $content = file_get_contents(self::$robots_file);
        
        // Check 3: No sitewide crawl block
        $has_sitewide_block = preg_match('/User-agent:\s*\*\s+Disallow:\s*\//i', $content);
        $results['checks']['sitewide_block'] = [
            'status' => $has_sitewide_block ? 'fail' : 'pass',
            'label' => 'No sitewide crawl block',
            'message' => $has_sitewide_block ? 'Found: Disallow: / - blocks all crawling' : 'No sitewide block found'
        ];
        
        if ($has_sitewide_block) {
            $results['status'] = 'error';
            $results['errors'][] = 'Sitewide crawl block detected. Search engines cannot crawl any content.';
        }
        
        // Check 4: Required assets allowed (CSS, JS, images)
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
        
        // Check 5: Sitemap declared
        $has_sitemap = preg_match('/Sitemap:\s*(.+)/i', $content, $matches);
        $results['checks']['sitemap_declared'] = [
            'status' => $has_sitemap ? 'pass' : 'warning',
            'label' => 'Sitemap declared',
            'message' => $has_sitemap ? 'Sitemap URL declared: ' . trim($matches[1]) : 'No sitemap declared in robots.txt'
        ];
        
        if (!$has_sitemap) {
            if ($results['status'] === 'success') {
                $results['status'] = 'warning';
            }
            $results['warnings'][] = 'Add a Sitemap directive to help search engines discover your content.';
        }
        
        // Check 6: Sitemap reachable (if declared)
        if ($has_sitemap) {
            $sitemap_url = trim($matches[1]);
            $sitemap_response = wp_remote_get($sitemap_url);
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
        
        // Check 7: No conflicting rules
        $has_conflicts = $this->check_conflicts($content);
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
        
        self::$validation_results = $results;
        return $results;
    }
    
    /**
     * Check for conflicting rules
     */
    private static function check_conflicts($content) {
        // Simple check: same path with both Allow and Disallow
        preg_match_all('/(?:Allow|Disallow):\s*(.+)/i', $content, $matches);
        if (count($matches[1]) !== count(array_unique($matches[1]))) {
            return true;
        }
        return false;
    }
    
    /**
     * Get default robots.txt content
     */
    private static function get_default_content() {
        $sitemap_url = home_url('/wp-sitemap.xml');
        
        return "# Default robots.txt for WordPress
# Generated by Clarity-First SEO

User-agent: *
Disallow: /wp-admin/
Disallow: /wp-includes/
Allow: /wp-admin/admin-ajax.php

# Sitemap location
Sitemap: {$sitemap_url}
";
    }
    
    /**
     * Save robots.txt
     */
    public static function save_robots() {
        check_admin_referer('CFSEO_save_robots');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $content = isset($_POST['robots_content']) ? wp_unslash($_POST['robots_content']) : '';
        
        // Save to file
        $saved = file_put_contents(self::$robots_file, $content);
        
        if ($saved !== false) {
            wp_redirect(add_query_arg([
                'page' => 'clarity-first-seo-robots',
                'saved' => '1'
            ], admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg([
                'page' => 'clarity-first-seo-robots',
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
        $content = '';
        if (file_exists(self::$robots_file)) {
            $content = file_get_contents(self::$robots_file);
        } else {
            $content = self::get_default_content();
        }
        
        // Run validation
        $validation = self::validate();
        
        // Check for save status
        $saved = isset($_GET['saved']) && $_GET['saved'] === '1';
        $error = isset($_GET['error']) && $_GET['error'] === '1';
        
        ?>
        <div class="wrap cfseo-admin-wrap has-sidebar">
            <h1><?php _e('Robots.txt Editor & Validator', 'cfseo'); ?></h1>
            
            <?php if ($saved): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('robots.txt saved successfully!', 'cfseo'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php _e('Failed to save robots.txt. Check file permissions.', 'cfseo'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="cfseo-settings-form">
                <div class="cfseo-tab-content">
                    
                    <!-- Validation Status -->
                    <div class="cfseo-validation-status" style="margin-bottom: 24px;">
                        <?php if ($validation['status'] === 'success'): ?>
                            <div class="notice notice-success inline" style="margin: 0; padding: 16px;">
                                <p style="margin: 0; font-weight: 500;">
                                    ✓ <?php _e('robots.txt is accessible, valid, and does not block important paths.', 'cfseo'); ?>
                                </p>
                            </div>
                        <?php elseif ($validation['status'] === 'warning'): ?>
                            <div class="notice notice-warning inline" style="margin: 0; padding: 16px;">
                                <p style="margin: 0 0 8px 0; font-weight: 500;">⚠ <?php _e('robots.txt has warnings:', 'cfseo'); ?></p>
                                <ul style="margin: 0; padding-left: 20px;">
                                    <?php foreach ($validation['warnings'] as $warning): ?>
                                        <li><?php echo esc_html($warning); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="notice notice-error inline" style="margin: 0; padding: 16px;">
                                <p style="margin: 0 0 8px 0; font-weight: 500;">✕ <?php _e('robots.txt has errors:', 'cfseo'); ?></p>
                                <ul style="margin: 0; padding-left: 20px;">
                                    <?php foreach ($validation['errors'] as $error_msg): ?>
                                        <li><?php echo esc_html($error_msg); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Validation Checklist -->
                    <div class="cfseo-validation-checks" style="margin-bottom: 24px;">
                        <h2><?php _e('Validation Checks', 'cfseo'); ?></h2>
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
                    <div class="cfseo-robots-editor">
                        <h2><?php _e('Edit robots.txt', 'cfseo'); ?></h2>
                        
                        <p class="description" style="margin-bottom: 12px;">
                            <strong><?php _e('Controls which URLs search engines may crawl.', 'cfseo'); ?></strong>
                            <?php _e('It does not control ranking or indexing.', 'cfseo'); ?>
                        </p>
                        
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                            <?php wp_nonce_field('CFSEO_save_robots'); ?>
                            <input type="hidden" name="action" value="CFSEO_save_robots">
                            
                            <textarea 
                                name="robots_content" 
                                rows="20" 
                                style="width: 100%; font-family: monospace; font-size: 13px; padding: 12px;"
                                spellcheck="false"
                            ><?php echo esc_textarea($content); ?></textarea>
                            
                            <p style="margin-top: 12px;">
                                <button type="submit" class="button button-primary button-large">
                                    <?php _e('Save robots.txt', 'cfseo'); ?>
                                </button>
                                
                                <a href="<?php echo home_url('/robots.txt'); ?>" target="_blank" class="button" style="margin-left: 8px;">
                                    <?php _e('View Live File', 'cfseo'); ?>
                                </a>
                            </p>
                        </form>
                        
                        <!-- Safe Defaults Info -->
                        <div style="margin-top: 24px; padding: 16px; background: #f6f7f7; border-left: 4px solid #00a0d2;">
                            <h3 style="margin-top: 0;"><?php _e('Safe Defaults', 'cfseo'); ?></h3>
                            <p><?php _e('If you\'re unsure, use these safe defaults:', 'cfseo'); ?></p>
                            <ul style="list-style: disc; padding-left: 20px;">
                                <li><?php _e('Block /wp-admin/ except admin-ajax.php', 'cfseo'); ?></li>
                                <li><?php _e('Block /wp-includes/ (system files)', 'cfseo'); ?></li>
                                <li><?php _e('Allow all public content (no Disallow: /)', 'cfseo'); ?></li>
                                <li><?php _e('Include your sitemap URL', 'cfseo'); ?></li>
                            </ul>
                        </div>
                    </div>
                    
                </div><!-- .cfseo-tab-content -->
            </div><!-- .cfseo-settings-form -->
                
            <!-- Help Sidebar -->
            <aside class="cfseo-sidebar">
                <button type="button" class="cfseo-sidebar-toggle" id="cfseo-sidebar-toggle">
                    <span class="dashicons dashicons-editor-help"></span>
                    <span class="cfseo-sidebar-toggle-text">Help & Tips</span>
                </button>
                    
                    <div class="cfseo-sidebar-content" id="cfseo-sidebar-content">
                        <div class="cfseo-help-card">
                            <h3><span class="dashicons dashicons-info"></span> Quick Tips</h3>
                            <ul>
                                <li>robots.txt only controls <strong>crawling</strong>, not ranking</li>
                                <li>Don't use robots.txt to hide sensitive content</li>
                                <li>Allow CSS and JS files for proper rendering</li>
                                <li>Always include your sitemap location</li>
                                <li>Test changes with Google Search Console</li>
                            </ul>
                        </div>
                        
                        <div class="cfseo-help-card">
                            <h3><span class="dashicons dashicons-admin-links"></span> Integration</h3>
                            <p>This works together with:</p>
                            <ul>
                                <li><a href="<?php echo admin_url('admin.php?page=clarity-first-seo-validation'); ?>">Validation checks</a></li>
                                <li>Canonical validation</li>
                                <li>Meta robots checks</li>
                                <li>Sitemap checks</li>
                            </ul>
                            <p style="margin-top: 12px; font-weight: 500;">
                                Together they answer: "Can search engines reach, crawl, and understand my site correctly?"
                            </p>
                        </div>
                    </div>
                </aside>
            </div><!-- .wrap -->
        
        <script>
        (function() {
            const toggle = document.getElementById('cfseo-sidebar-toggle');
            const content = document.getElementById('cfseo-sidebar-content');
            const storageKey = 'cfseo_sidebar_visible';
            
            // Restore sidebar state
            const isVisible = localStorage.getItem(storageKey) !== 'false';
            if (!isVisible) {
                content.style.display = 'none';
                toggle.classList.add('collapsed');
            }
            
            toggle.addEventListener('click', function() {
                const visible = content.style.display !== 'none';
                content.style.display = visible ? 'none' : 'block';
                toggle.classList.toggle('collapsed');
                localStorage.setItem(storageKey, !visible);
            });
        })();
        </script>
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
