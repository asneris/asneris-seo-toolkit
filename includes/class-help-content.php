<?php
/**
 * Help Content Manager
 * Loads help content from local JSON file
 */

if (!defined('ABSPATH')) exit;

class ASNERISSEO_Help_Content {
  
  private static $content_cache = null;
  
  /**
   * Set to false to hide all "Review Guide" links
   */
  const SHOW_REVIEW_LINKS = false;

  private static $assets_enqueued = false;
  
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
    <aside class="ASNERISSEO-sidebar">
      <button type="button" class="ASNERISSEO-sidebar-toggle" id="ASNERISSEO-sidebar-toggle">
        <span class="dashicons dashicons-editor-help"></span>
        <span class="ASNERISSEO-sidebar-toggle-text">Help & Tips</span>
      </button>
      
      <div class="ASNERISSEO-sidebar-content" id="ASNERISSEO-sidebar-content">
        <?php foreach ($content['cards'] as $card): ?>
          <div class="ASNERISSEO-help-card">
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
    
    <?php self::enqueue_assets(false); ?>
    <?php
  }
  
  /**
   * Render tabbed sidebar with dynamic content based on active tab
   */
  private static function render_tabbed_sidebar($page_id, $tabs) {
    ?>
    <aside class="ASNERISSEO-sidebar">
      <button type="button" class="ASNERISSEO-sidebar-toggle" id="ASNERISSEO-sidebar-toggle">
        <span class="dashicons dashicons-editor-help"></span>
        <span class="ASNERISSEO-sidebar-toggle-text">Help & Tips</span>
      </button>
      
      <div class="ASNERISSEO-sidebar-content" id="ASNERISSEO-sidebar-content">
        <?php foreach ($tabs as $tab_key => $cards): ?>
          <div class="ASNERISSEO-tab-help" data-tab="<?php echo esc_attr($tab_key); ?>" style="display: none;">
            <?php foreach ($cards as $card): ?>
              <div class="ASNERISSEO-help-card">
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
    
    <?php self::enqueue_assets(true); ?>
    <?php
  }

  /**
   * Enqueue sidebar scripts
   */
  private static function enqueue_assets($tabbed) {
    if (!self::$assets_enqueued) {
      wp_register_script('ASNERISSEO-help-content', '', [], ASNERISSEO_VERSION, true);
      wp_enqueue_script('ASNERISSEO-help-content');
      self::$assets_enqueued = true;
    }

    if ($tabbed) {
      $inline_js = '(function(){\n'
        . '  const toggle=document.getElementById("ASNERISSEO-sidebar-toggle");\n'
        . '  const content=document.getElementById("ASNERISSEO-sidebar-content");\n'
        . '  const storageKey="ASNERISSEO_sidebar_visible";\n'
        . '  const tabButtons=document.querySelectorAll(".nav-tab");\n'
        . '  const tabHelp=document.querySelectorAll(".ASNERISSEO-tab-help");\n'
        . '  if (!toggle || !content) return;\n'
        . '  const isVisible=localStorage.getItem(storageKey)!=="false";\n'
        . '  if(!isVisible){content.style.display="none";toggle.classList.add("collapsed");}\n'
        . '  toggle.addEventListener("click",function(){\n'
        . '    const visible=content.style.display!=="none";\n'
        . '    content.style.display=visible?"none":"block";\n'
        . '    toggle.classList.toggle("collapsed");\n'
        . '    localStorage.setItem(storageKey,!visible);\n'
        . '  });\n'
        . '  function updateHelpContent(){\n'
        . '    const activeTab=document.querySelector(".nav-tab-active");\n'
        . '    if(!activeTab) return;\n'
        . '    const tabHref=activeTab.getAttribute("href");\n'
        . '    if(!tabHref) return;\n'
        . '    const urlParams=new URLSearchParams(tabHref.split("?")[1]||"");\n'
        . '    const tabName=urlParams.get("tab")||"general";\n'
        . '    tabHelp.forEach(function(help){help.style.display="none";});\n'
        . '    const matchingHelp=document.querySelector(".ASNERISSEO-tab-help[data-tab=\""+tabName+"\"]");\n'
        . '    if(matchingHelp){matchingHelp.style.display="block";}\n'
        . '  }\n'
        . '  updateHelpContent();\n'
        . '  tabButtons.forEach(function(button){\n'
        . '    button.addEventListener("click",function(){setTimeout(updateHelpContent,50);});\n'
        . '  });\n'
        . '})();';
      wp_add_inline_script('ASNERISSEO-help-content', $inline_js);
      return;
    }

    $inline_js = '(function(){\n'
      . '  const toggle=document.getElementById("ASNERISSEO-sidebar-toggle");\n'
      . '  const content=document.getElementById("ASNERISSEO-sidebar-content");\n'
      . '  const storageKey="ASNERISSEO_sidebar_visible";\n'
      . '  if (!toggle || !content) return;\n'
      . '  const isVisible=localStorage.getItem(storageKey)!=="false";\n'
      . '  if(!isVisible){content.style.display="none";toggle.classList.add("collapsed");}\n'
      . '  toggle.addEventListener("click",function(){\n'
      . '    const visible=content.style.display!=="none";\n'
      . '    content.style.display=visible?"none":"block";\n'
      . '    toggle.classList.toggle("collapsed");\n'
      . '    localStorage.setItem(storageKey,!visible);\n'
      . '  });\n'
      . '})();';
    wp_add_inline_script('ASNERISSEO-help-content', $inline_js);
  }
}
