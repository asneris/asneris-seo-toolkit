<?php
/**
 * Help Modal Manager
 * Loads modal help content from local JSON file
 */

if (!defined('ABSPATH')) exit;

class CFSEO_Help_Modal {
  
  private static $content_cache = null;
  
  /**
   * Get modal content for a specific page
   */
  public static function get($page_id) {
    $all_content = self::load_content();
    
    if (!isset($all_content[$page_id])) {
      return ['modals' => []];
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
   * Render modal HTML and JavaScript
   */
  public static function render_modals($page_id) {
    $content = self::get($page_id);
    
    if (empty($content['modals'])) {
      return;
    }
    
    $modals_json = json_encode($content['modals']);
    ?>
    <!-- Help Modals -->
    <div id="cfseo-help-modal-overlay" class="cfseo-modal-overlay" onclick="CFSEOHelpModal.close()"></div>
    <div id="cfseo-help-modal" class="cfseo-modal">
      <div class="cfseo-modal-header">
        <h2 id="cfseo-modal-title"></h2>
        <button type="button" class="cfseo-modal-close" onclick="CFSEOHelpModal.close()">
          <span class="dashicons dashicons-no"></span>
        </button>
      </div>
      <div class="cfseo-modal-content" id="cfseo-modal-content"></div>
    </div>
    
    <?php self::render_styles(); ?>
    
    <script>
    const CFSEOHelpModal = {
      content: <?php echo $modals_json; ?>,
      
      open: function(contentId) {
        const modal = document.getElementById('cfseo-help-modal');
        const overlay = document.getElementById('cfseo-help-modal-overlay');
        const title = document.getElementById('cfseo-modal-title');
        const content = document.getElementById('cfseo-modal-content');
        
        if (!this.content[contentId]) return;
        
        title.textContent = this.content[contentId].title;
        content.innerHTML = this.content[contentId].body;
        
        modal.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
      },
      
      close: function() {
        const modal = document.getElementById('cfseo-help-modal');
        const overlay = document.getElementById('cfseo-help-modal-overlay');
        
        modal.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
      }
    };
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        CFSEOHelpModal.close();
      }
    });
    </script>
    <?php
  }
  
  /**
   * Render modal styles
   */
  private static function render_styles() {
    ?>
    <style>
    /* Help Icon Button */
    .cfseo-help-icon {
      background: none;
      border: none;
      cursor: pointer;
      padding: 0;
      margin-left: 5px;
      color: #2271b1;
      vertical-align: middle;
    }
    .cfseo-help-icon:hover {
      color: #135e96;
    }
    .cfseo-help-icon .dashicons {
      font-size: 16px;
      width: 16px;
      height: 16px;
    }
    
    /* Modal Overlay */
    .cfseo-modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      z-index: 100000;
    }
    .cfseo-modal-overlay.active {
      display: block;
    }
    
    /* Modal Container */
    .cfseo-modal {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: white;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
      z-index: 100001;
      max-width: 600px;
      width: 90%;
      max-height: 80vh;
      overflow: hidden;
    }
    .cfseo-modal.active {
      display: block;
    }
    
    /* Modal Header */
    .cfseo-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 25px;
      border-bottom: 1px solid #dcdcde;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    .cfseo-modal-header h2 {
      margin: 0;
      font-size: 18px;
      color: white;
    }
    .cfseo-modal-close {
      background: none;
      border: none;
      cursor: pointer;
      padding: 0;
      color: white;
      opacity: 0.8;
    }
    .cfseo-modal-close:hover {
      opacity: 1;
    }
    .cfseo-modal-close .dashicons {
      font-size: 24px;
      width: 24px;
      height: 24px;
    }
    
    /* Modal Content */
    .cfseo-modal-content {
      padding: 25px;
      overflow-y: auto;
      max-height: calc(80vh - 80px);
    }
    .cfseo-modal-content h3 {
      margin-top: 0;
      color: #1d2327;
      font-size: 16px;
    }
    .cfseo-modal-content p {
      line-height: 1.6;
      color: #3c434a;
    }
    .cfseo-modal-content code {
      background: #f6f7f7;
      padding: 2px 6px;
      border-radius: 3px;
      font-size: 13px;
    }
    .cfseo-modal-content ul {
      line-height: 1.8;
    }
    .cfseo-modal-content .cfseo-info-box {
      background: #e7f5fe;
      border-left: 4px solid #2271b1;
      padding: 12px 15px;
      margin: 15px 0;
      border-radius: 4px;
    }
    .cfseo-modal-content .cfseo-warning-box {
      background: #fff8e5;
      border-left: 4px solid #f0ad4e;
      padding: 12px 15px;
      margin: 15px 0;
      border-radius: 4px;
    }
    </style>
    <?php
  }
  
  /**
   * Render help button for page header
   */
  public static function render_help_button($modal_id, $label = 'Help') {
    ?>
    <button type="button" class="button button-secondary" onclick="CFSEOHelpModal.open('<?php echo esc_js($modal_id); ?>')" style="margin-left: 10px; vertical-align: middle;">
      <span class="dashicons dashicons-editor-help" style="margin-top: 4px;"></span> <?php echo esc_html($label); ?>
    </button>
    <?php
  }
  
  /**
   * Render help icon for inline use (next to labels)
   */
  public static function render_help_icon($modal_id, $title = 'Help') {
    ?>
    <button type="button" class="cfseo-help-icon" onclick="CFSEOHelpModal.open('<?php echo esc_js($modal_id); ?>')" title="<?php echo esc_attr($title); ?>" style="background: #2271b1; border: none; border-radius: 50%; width: 18px; height: 18px; padding: 0; margin-left: 5px; cursor: pointer; color: #ffffff; font-size: 12px; font-weight: bold; vertical-align: middle; line-height: 18px; display: inline-block; text-align: center;">
      ?
    </button>
    <?php
  }
}
