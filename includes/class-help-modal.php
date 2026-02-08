<?php
/**
 * Help Modal Manager
 * Loads modal help content from local JSON file
 */

if (!defined('ABSPATH')) exit;

class ASNERISSEO_Help_Modal {

  private static $assets_enqueued = false;
  
  private static $content_cache = null;
  
  private static $modals_to_render = [];
  
  /**
   * Initialize hooks
   */
  public static function init() {
    add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    add_action('admin_footer', [__CLASS__, 'render_modal_html']);
  }
  
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
    
    $json_file = ASNERISSEO_DIR . 'help-content.json';
    
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
    
    // Store modals to render in footer
    self::$modals_to_render = $content['modals'];
  }
  
  /**
   * Render modal HTML in footer
   */
  public static function render_modal_html() {
    if (empty(self::$modals_to_render)) {
      return;
    }
    ?>
    <script>
    if (typeof ASNERISSEOHelpModal !== "undefined") {
      ASNERISSEOHelpModal.setContent(<?php echo wp_json_encode(self::$modals_to_render); ?>);
    }
    </script>
    <!-- Help Modals -->
    <div id="ASNERISSEO-help-modal-overlay" class="ASNERISSEO-modal-overlay" onclick="ASNERISSEOHelpModal.close()"></div>
    <div id="ASNERISSEO-help-modal" class="ASNERISSEO-modal">
      <div class="ASNERISSEO-modal-header">
        <h2 id="ASNERISSEO-modal-title"></h2>
        <button type="button" class="ASNERISSEO-modal-close" onclick="ASNERISSEOHelpModal.close()">
          <span class="dashicons dashicons-no"></span>
        </button>
      </div>
      <div class="ASNERISSEO-modal-content" id="ASNERISSEO-modal-content"></div>
    </div>
    <?php
  }
  
  /**
   * Enqueue modal scripts and styles
   */
  public static function enqueue_assets() {
    if (self::$assets_enqueued) {
      return;
    }
    
    // Only enqueue on plugin admin pages
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'asneris-seo') === false) {
      return;
    }
    
    self::$assets_enqueued = true;

    wp_register_style('ASNERISSEO-help-modal', false, [], ASNERISSEO_VERSION);
    wp_enqueue_style('ASNERISSEO-help-modal');
    
    // Use heredoc for clean CSS
    $css = <<<'CSS'
/* Help Icon Button */
.ASNERISSEO-help-icon{background:none;border:none;cursor:pointer;padding:0;margin-left:5px;color:#2271b1;vertical-align:middle;}
.ASNERISSEO-help-icon:hover{color:#135e96;}
.ASNERISSEO-help-icon .dashicons{font-size:16px;width:16px;height:16px;}
/* Modal Overlay */
.ASNERISSEO-modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:100000;}
.ASNERISSEO-modal-overlay.active{display:block;}
/* Modal Container */
.ASNERISSEO-modal{display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:8px;box-shadow:0 5px 15px rgba(0,0,0,0.3);z-index:100001;max-width:600px;width:90%;max-height:80vh;overflow:hidden;}
.ASNERISSEO-modal.active{display:block;}
/* Modal Header */
.ASNERISSEO-modal-header{display:flex;justify-content:space-between;align-items:center;padding:20px 25px;border-bottom:1px solid #dcdcde;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;}
.ASNERISSEO-modal-header h2{margin:0;font-size:18px;color:#fff;}
.ASNERISSEO-modal-close{background:none;border:none;cursor:pointer;padding:0;color:#fff;opacity:0.8;}
.ASNERISSEO-modal-close:hover{opacity:1;}
.ASNERISSEO-modal-close .dashicons{font-size:24px;width:24px;height:24px;}
/* Modal Content */
.ASNERISSEO-modal-content{padding:25px;overflow-y:auto;max-height:calc(80vh - 80px);}
.ASNERISSEO-modal-content h3{margin-top:0;color:#1d2327;font-size:16px;}
.ASNERISSEO-modal-content p{line-height:1.6;color:#3c434a;}
.ASNERISSEO-modal-content code{background:#f6f7f7;padding:2px 6px;border-radius:3px;font-size:13px;}
.ASNERISSEO-modal-content ul{line-height:1.8;}
.ASNERISSEO-modal-content .ASNERISSEO-info-box{background:#e7f5fe;border-left:4px solid #2271b1;padding:12px 15px;margin:15px 0;border-radius:4px;}
.ASNERISSEO-modal-content .ASNERISSEO-warning-box{background:#fff8e5;border-left:4px solid #f0ad4e;padding:12px 15px;margin:15px 0;border-radius:4px;}
CSS;
    wp_add_inline_style('ASNERISSEO-help-modal', $css);

    // Register script to load in header with no dependencies
    wp_register_script('ASNERISSEO-help-modal', '', [], ASNERISSEO_VERSION, false);
    wp_enqueue_script('ASNERISSEO-help-modal');
    
    // Use heredoc for clean JavaScript - modal object defined but without content yet
    $inline_js = <<<'JAVASCRIPT'
window.ASNERISSEOHelpModal = {
  content: {},
  setContent: function(modals) {
    this.content = modals;
  },
  open: function(contentId) {
    var modal = document.getElementById("ASNERISSEO-help-modal");
    var overlay = document.getElementById("ASNERISSEO-help-modal-overlay");
    var title = document.getElementById("ASNERISSEO-modal-title");
    var content = document.getElementById("ASNERISSEO-modal-content");
    if (!this.content || !this.content[contentId]) return;
    title.textContent = this.content[contentId].title;
    content.innerHTML = this.content[contentId].body;
    modal.classList.add("active");
    overlay.classList.add("active");
    document.body.style.overflow = "hidden";
  },
  close: function() {
    var modal = document.getElementById("ASNERISSEO-help-modal");
    var overlay = document.getElementById("ASNERISSEO-help-modal-overlay");
    modal.classList.remove("active");
    overlay.classList.remove("active");
    document.body.style.overflow = "";
  }
};
document.addEventListener("keydown", function(e) {
  if (e.key === "Escape") { window.ASNERISSEOHelpModal.close(); }
});
JAVASCRIPT;
    wp_add_inline_script('ASNERISSEO-help-modal', $inline_js);
  }
  
  /**
   * Render help button for page header
   */
  public static function render_help_button($modal_id, $label = 'Help') {
    ?>
    <button type="button" class="button button-secondary" onclick="ASNERISSEOHelpModal.open('<?php echo esc_js($modal_id); ?>')" style="margin-left: 10px; vertical-align: middle;">
      <span class="dashicons dashicons-editor-help" style="margin-top: 4px;"></span> <?php echo esc_html($label); ?>
    </button>
    <?php
  }
  
  /**
   * Render help icon for inline use (next to labels)
   */
  public static function render_help_icon($modal_id, $title = 'Help') {
    ?>
    <button type="button" class="ASNERISSEO-help-icon" onclick="ASNERISSEOHelpModal.open('<?php echo esc_js($modal_id); ?>')" title="<?php echo esc_attr($title); ?>" style="background: #2271b1; border: none; border-radius: 50%; width: 18px; height: 18px; padding: 0; margin-left: 5px; cursor: pointer; color: #ffffff; font-size: 12px; font-weight: bold; vertical-align: middle; line-height: 18px; display: inline-block; text-align: center;">
      ?
    </button>
    <?php
  }
}
