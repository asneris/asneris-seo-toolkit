<?php
/**
 * Help Content Manager
 * Loads help content from local JSON file
 */

if (!defined('ABSPATH')) exit;

class CFSEO_Help_Content {
  
  private static $content_cache = null;
  
  /**
   * Set to false to hide all "Review Guide" links
   */
  const SHOW_REVIEW_LINKS = false;
  
  /**
   * Get help content for a specific page
   */
  public static function get($page_id) {
    $all_content = self::load_content();
    
    if (!isset($all_content[$page_id])) {
      return ['cards' => []];
    }
    
    return $all_content[$page_id];
  }
  
  /**
   * Load content from local JSON file
   */
  private static function load_content() {
    // Use cached content if available
    if (self::$content_cache !== null) {
      return self::$content_cache;
    }
    
    $json_file = CFSEO_DIR . 'help-content.json';
    
    if (!file_exists($json_file)) {
      return [];
    }
    
    $json_content = file_get_contents($json_file);
    $data = json_decode($json_content, true);
    
    if (!$data) {
      return [];
    }
    
    // Cache in memory for this request
    self::$content_cache = $data;
    
    return $data;
  }
  
  /**
   * Render help sidebar
   */
  public static function render_sidebar($page_id) {
    $content = self::get($page_id);
    
    // Check if this page has tab-specific content
    if (!empty($content['tabs'])) {
      self::render_tabbed_sidebar($page_id, $content['tabs']);
      return;
    }
    
    if (empty($content['cards'])) {
      return;
    }
    ?>
    <aside class="cfseo-sidebar">
      <button type="button" class="cfseo-sidebar-toggle" id="cfseo-sidebar-toggle">
        <span class="dashicons dashicons-editor-help"></span>
        <span class="cfseo-sidebar-toggle-text">Help & Tips</span>
      </button>
      
      <div class="cfseo-sidebar-content" id="cfseo-sidebar-content">
        <?php foreach ($content['cards'] as $card): ?>
          <div class="cfseo-help-card">
            <h3>
              <span class="dashicons <?php echo esc_attr($card['icon']); ?>"></span>
              <?php echo esc_html($card['title']); ?>
            </h3>
            <?php echo wp_kses_post($card['content']); ?>
            
            <?php if (self::SHOW_REVIEW_LINKS && !empty($card['review_url'])): ?>
              <p style="margin-top: 12px;">
                <a href="<?php echo esc_url($card['review_url']); ?>" target="_blank" class="button button-small">
                  <span class="dashicons dashicons-external" style="font-size: 13px; margin-top: 3px;"></span>
                  Review Guide
                </a>
              </p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </aside>
    
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
   * Render tabbed sidebar with dynamic content based on active tab
   */
  private static function render_tabbed_sidebar($page_id, $tabs) {
    ?>
    <aside class="cfseo-sidebar">
      <button type="button" class="cfseo-sidebar-toggle" id="cfseo-sidebar-toggle">
        <span class="dashicons dashicons-editor-help"></span>
        <span class="cfseo-sidebar-toggle-text">Help & Tips</span>
      </button>
      
      <div class="cfseo-sidebar-content" id="cfseo-sidebar-content">
        <?php foreach ($tabs as $tab_key => $cards): ?>
          <div class="cfseo-tab-help" data-tab="<?php echo esc_attr($tab_key); ?>" style="display: none;">
            <?php foreach ($cards as $card): ?>
              <div class="cfseo-help-card">
                <h3>
                  <span class="dashicons <?php echo esc_attr($card['icon']); ?>"></span>
                  <?php echo esc_html($card['title']); ?>
                </h3>
                <?php echo wp_kses_post($card['content']); ?>
                
                <?php if (self::SHOW_REVIEW_LINKS && !empty($card['review_url'])): ?>
                  <p style="margin-top: 12px;">
                    <a href="<?php echo esc_url($card['review_url']); ?>" target="_blank" class="button button-small">
                      <span class="dashicons dashicons-external" style="font-size: 13px; margin-top: 3px;"></span>
                      Review Guide
                    </a>
                  </p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </aside>
    
    <script>
    (function() {
      const toggle = document.getElementById('cfseo-sidebar-toggle');
      const content = document.getElementById('cfseo-sidebar-content');
      const storageKey = 'cfseo_sidebar_visible';
      const tabButtons = document.querySelectorAll('.nav-tab');
      const tabHelp = document.querySelectorAll('.cfseo-tab-help');
      
      // Restore sidebar state
      const isVisible = localStorage.getItem(storageKey) !== 'false';
      if (!isVisible) {
        content.style.display = 'none';
        toggle.classList.add('collapsed');
      }
      
      // Toggle sidebar visibility
      toggle.addEventListener('click', function() {
        const visible = content.style.display !== 'none';
        content.style.display = visible ? 'none' : 'block';
        toggle.classList.toggle('collapsed');
        localStorage.setItem(storageKey, !visible);
      });
      
      // Show/hide help based on active tab
      function updateHelpContent() {
        const activeTab = document.querySelector('.nav-tab-active');
        if (!activeTab) return;
        
        const tabHref = activeTab.getAttribute('href');
        if (!tabHref) return;
        
        // Extract tab name from query parameter (e.g., ?page=cfseo-settings&tab=general)
        const urlParams = new URLSearchParams(tabHref.split('?')[1] || '');
        const tabName = urlParams.get('tab') || 'general';
        
        // Hide all tab help
        tabHelp.forEach(help => help.style.display = 'none');
        
        // Show matching tab help
        const matchingHelp = document.querySelector('.cfseo-tab-help[data-tab="' + tabName + '"]');
        if (matchingHelp) {
          matchingHelp.style.display = 'block';
        }
      }
      
      // Initial update
      updateHelpContent();
      
      // Listen for tab clicks
      tabButtons.forEach(button => {
        button.addEventListener('click', function() {
          setTimeout(updateHelpContent, 50);
        });
      });
    })();
    </script>
    <?php
  }
}
