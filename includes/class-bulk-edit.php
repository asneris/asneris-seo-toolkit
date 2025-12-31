<?php
if (!defined('ABSPATH')) exit;

class GSCSEO_Bulk_Edit {
  
  /**
   * Register bulk edit admin page
   */
  public static function register_menu() {
    add_submenu_page(
      'clarity-first-seo',
      __('Bulk Edit', 'bfseo'),
      __('Bulk Edit', 'bfseo'),
      'edit_posts',
      'gscseo-bulk-edit',
      [__CLASS__, 'render_page']
    );
  }
  
  /**
   * Enqueue bulk edit assets
   */
  public static function enqueue_assets($hook) {
    if ($hook !== 'clarity-first-seo_page_gscseo-bulk-edit' && $hook !== 'toplevel_page_gscseo-bulk-edit') return;
    
    wp_enqueue_style('gscseo-bulk-edit', GSCSEO_URL . 'assets/css/admin-style.css', [], GSCSEO_VERSION);
    wp_enqueue_script('gscseo-bulk-edit', GSCSEO_URL . 'assets/js/bulk-edit.js', ['jquery'], GSCSEO_VERSION, true);
    
    wp_localize_script('gscseo-bulk-edit', 'gscseoBulkEdit', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('gscseo_bulk_edit'),
    ]);
  }
  
  /**
   * Render bulk edit page
   */
  public static function render_page() {
    $post_types = get_post_types(['public' => true], 'objects');
    $selected_post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'post';
    $indexing_filter = isset($_GET['indexing']) ? sanitize_text_field($_GET['indexing']) : 'all';
    
    // Query posts
    $args = [
      'post_type' => $selected_post_type,
      'post_status' => 'publish',
      'posts_per_page' => 50,
      'orderby' => 'date',
      'order' => 'DESC',
    ];
    
    // Apply indexing filter
    if ($indexing_filter === 'indexed') {
      $args['meta_query'] = [
        'relation' => 'OR',
        ['key' => '_gscseo_robots_index', 'compare' => 'NOT EXISTS'],
        ['key' => '_gscseo_robots_index', 'value' => 'index'],
      ];
    } elseif ($indexing_filter === 'noindex') {
      $args['meta_query'] = [
        ['key' => '_gscseo_robots_index', 'value' => 'noindex'],
      ];
    }
    
    $posts_query = new WP_Query($args);
    ?>
    <div class="wrap gscseo-admin-wrap">
      <h1>
        <span class="dashicons dashicons-edit"></span>
        <?php _e('SEO Bulk Edit', 'bfseo'); ?>
      </h1>
      <p class="gscseo-subtitle"><?php _e('Bulk edit SEO metadata for multiple posts', 'bfseo'); ?></p>
      
      <div class="gscseo-settings-form">
        <div class="gscseo-tab-content">
      
      <!-- Filters -->
      <div class="gscseo-card">
        <h2><span class="dashicons dashicons-filter"></span> <?php _e('Filters', 'bfseo'); ?></h2>
        <form method="get" action="">
          <input type="hidden" name="page" value="gscseo-bulk-edit">
          <table class="form-table">
            <tr>
              <th scope="row">
                <label for="post_type"><?php _e('Post Type', 'bfseo'); ?></label>
              </th>
              <td>
                <select name="post_type" id="post_type">
                  <?php foreach ($post_types as $pt): ?>
                    <option value="<?php echo esc_attr($pt->name); ?>" <?php selected($selected_post_type, $pt->name); ?>>
                      <?php echo esc_html($pt->labels->name); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              
              <th scope="row">
                <label for="indexing"><?php _e('Indexing Status', 'bfseo'); ?></label>
              </th>
              <td>
                <select name="indexing" id="indexing">
                  <option value="all" <?php selected($indexing_filter, 'all'); ?>><?php _e('All', 'bfseo'); ?></option>
                  <option value="indexed" <?php selected($indexing_filter, 'indexed'); ?>><?php _e('Indexed', 'bfseo'); ?></option>
                  <option value="noindex" <?php selected($indexing_filter, 'noindex'); ?>><?php _e('NoIndex', 'bfseo'); ?></option>
                </select>
              </td>
              
              <td>
                <button type="submit" class="button"><?php _e('Filter', 'bfseo'); ?></button>
              </td>
            </tr>
          </table>
        </form>
      </div>
      
      <!-- Bulk Actions -->
      <div class="gscseo-card" style="max-width: 100%; margin-top: 20px;">
        <h2><span class="dashicons dashicons-admin-generic"></span> <?php _e('Bulk Actions', 'bfseo'); ?></h2>
        <p style="color: #646970;"><?php _e('Select posts below and apply bulk actions. Changes will be previewed before applying.', 'bfseo'); ?></p>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
          <button type="button" id="gscseo-bulk-set-index" class="button">
            <?php _e('Set to Index', 'bfseo'); ?>
          </button>
          <button type="button" id="gscseo-bulk-set-noindex" class="button">
            <?php _e('Set to NoIndex', 'bfseo'); ?>
          </button>
          <button type="button" id="gscseo-bulk-clear-title" class="button">
            <?php _e('Clear SEO Titles', 'bfseo'); ?>
          </button>
          <button type="button" id="gscseo-bulk-clear-description" class="button">
            <?php _e('Clear Descriptions', 'bfseo'); ?>
          </button>
        </div>
      </div>
      
      <!-- Posts Table -->
      <div class="gscseo-card" style="max-width: 100%; margin-top: 20px;">
        <form id="gscseo-bulk-edit-form">
          <table class="wp-list-table widefat fixed striped">
            <thead>
              <tr>
                <td class="check-column">
                  <input type="checkbox" id="gscseo-select-all">
                </td>
                <th><?php _e('Title', 'bfseo'); ?></th>
                <th><?php _e('SEO Title', 'bfseo'); ?></th>
                <th><?php _e('Description', 'bfseo'); ?></th>
                <th><?php _e('Robots', 'bfseo'); ?></th>
                <th><?php _e('Actions', 'bfseo'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if ($posts_query->have_posts()): ?>
                <?php while ($posts_query->have_posts()): $posts_query->the_post(); 
                  $post_id = get_the_ID();
                  $seo_title = get_post_meta($post_id, '_gscseo_title', true);
                  $seo_desc = get_post_meta($post_id, '_gscseo_description', true);
                  $robots_index = get_post_meta($post_id, '_gscseo_robots_index', true) ?: 'index';
                ?>
                  <tr>
                    <th class="check-column">
                      <input type="checkbox" name="post_ids[]" value="<?php echo esc_attr($post_id); ?>" class="gscseo-post-checkbox">
                    </th>
                    <td>
                      <strong><?php the_title(); ?></strong>
                      <div class="row-actions">
                        <span><a href="<?php echo get_permalink(); ?>" target="_blank"><?php _e('View', 'bfseo'); ?></a></span>
                      </div>
                    </td>
                    <td>
                      <input 
                        type="text" 
                        name="seo_title[<?php echo esc_attr($post_id); ?>]" 
                        value="<?php echo esc_attr($seo_title); ?>" 
                        class="regular-text"
                        placeholder="<?php _e('Default from title', 'bfseo'); ?>"
                      >
                    </td>
                    <td>
                      <textarea 
                        name="seo_description[<?php echo esc_attr($post_id); ?>]" 
                        rows="2" 
                        class="large-text"
                        placeholder="<?php _e('Default from excerpt', 'bfseo'); ?>"
                      ><?php echo esc_textarea($seo_desc); ?></textarea>
                    </td>
                    <td>
                      <select name="robots_index[<?php echo esc_attr($post_id); ?>]">
                        <option value="index" <?php selected($robots_index, 'index'); ?>><?php _e('Index', 'bfseo'); ?></option>
                        <option value="noindex" <?php selected($robots_index, 'noindex'); ?>><?php _e('NoIndex', 'bfseo'); ?></option>
                      </select>
                    </td>
                    <td>
                      <a href="<?php echo get_edit_post_link($post_id); ?>" class="button button-small">
                        <?php _e('Edit', 'bfseo'); ?>
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" style="text-align: center; padding: 40px;">
                    <?php _e('No posts found.', 'bfseo'); ?>
                  </td>
                </tr>
              <?php endif; ?>
              <?php wp_reset_postdata(); ?>
            </tbody>
          </table>
          
          <?php if ($posts_query->have_posts()): ?>
            <div style="margin-top: 20px;">
              <button type="submit" class="button button-primary button-large">
                <?php _e('Save All Changes', 'bfseo'); ?>
              </button>
              <span id="gscseo-bulk-status" style="margin-left: 15px;"></span>
            </div>
          <?php endif; ?>
        </form>
      </div>
      
        </div><!-- .gscseo-tab-content -->
      </div><!-- .gscseo-settings-form -->
        
      <!-- Sidebar Info -->
      <div class="gscseo-sidebar">
          <div class="gscseo-info-box">
            <h3><span class="dashicons dashicons-info"></span> Quick Tips</h3>
            <ul>
              <li>Filter by post type and indexing status to target specific content</li>
              <li>Use bulk actions (Set to Index, Set to Noindex, Clear Titles, Clear Descriptions) for quick changes</li>
              <li>Edit individual fields directly in the table for precise control</li>
              <li>Changes are previewed before saving - review carefully</li>
              <li>Click "Save All Changes" to apply modifications to all visible posts</li>
            </ul>
          </div>
          
          <div class="gscseo-info-box gscseo-success-box">
            <h3><span class="dashicons dashicons-yes"></span> Need Help?</h3>
            <p>Learn about bulk editing SEO metadata:</p>
            <ul>
              <li><a href="https://clarityfirstseo.com/docs/bulk-edit/" target="_blank">Bulk Edit Guide</a></li>
              <li><a href="https://clarityfirstseo.com/docs/meta-tags/" target="_blank">Meta Tags Best Practices</a></li>
              <li><a href="https://clarityfirstseo.com/docs/indexing/" target="_blank">Indexing Control</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    <?php
  }
  
  /**
   * AJAX handler for bulk save
   */
  public static function ajax_bulk_save() {
    check_ajax_referer('gscseo_bulk_edit', 'nonce');
    
    if (!current_user_can('edit_posts')) {
      wp_send_json_error(['message' => __('Permission denied', 'bfseo')]);
      return;
    }
    
    $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
    $titles = isset($_POST['seo_title']) ? $_POST['seo_title'] : [];
    $descriptions = isset($_POST['seo_description']) ? $_POST['seo_description'] : [];
    $robots = isset($_POST['robots_index']) ? $_POST['robots_index'] : [];
    
    $updated = 0;
    
    foreach ($post_ids as $post_id) {
      if (!current_user_can('edit_post', $post_id)) continue;
      
      if (isset($titles[$post_id])) {
        update_post_meta($post_id, '_gscseo_title', sanitize_text_field($titles[$post_id]));
      }
      
      if (isset($descriptions[$post_id])) {
        update_post_meta($post_id, '_gscseo_description', sanitize_textarea_field($descriptions[$post_id]));
      }
      
      if (isset($robots[$post_id])) {
        update_post_meta($post_id, '_gscseo_robots_index', sanitize_text_field($robots[$post_id]));
      }
      
      $updated++;
    }
    
    wp_send_json_success([
      'message' => sprintf(__('%d posts updated successfully!', 'bfseo'), $updated),
      'updated' => $updated
    ]);
  }
}
