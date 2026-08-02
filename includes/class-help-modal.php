<?php
/**
 * Help Modal Manager
 * Loads modal help content from local JSON file
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASNERISSEO_Help_Modal {

	private static $assets_enqueued = false;

	private static $content_cache = null;

	/**
	 * Initialize hooks
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_footer', array( __CLASS__, 'render_modal_html' ), 20 );
	}

	/**
	 * Get modal content for a specific page
	 */
	public static function get( $page_id ) {
		$all_content = self::load_content();

		if ( ! isset( $all_content[ $page_id ] ) ) {
			return array( 'modals' => array() );
		}

		return $all_content[ $page_id ];
	}

	/**
	 * Load content from local JSON file.
	 *
	 * Uses file_get_contents() intentionally: WP_Filesystem requires credentials
	 * context (admin form submission or SSH/FTP setup) and can return an
	 * uninitialised object when called during admin_enqueue_scripts, causing
	 * silent empty reads. For a read-only, bundled plugin file this is safe.
	 */
	private static function load_content() {
		if ( self::$content_cache !== null ) {
			return self::$content_cache;
		}

		$json_file = ASNERISSEO_DIR . 'help-content.json';

		if ( ! file_exists( $json_file ) ) {
			return array();
		}

	  // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a bundled read-only plugin asset; WP_Filesystem is unreliable here
		$json_content = file_get_contents( $json_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( $json_content === false ) {
			return array();
		}

		$data = json_decode( $json_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
			return array();
		}

		self::$content_cache = $data;

		return $data;
	}

	/**
	 * Called by page renderers to signal that modal overlay should be present.
	 * With the localize_script approach the content is already embedded — this
	 * only needs to mark that this is a plugin page so the overlay is printed.
	 *
	 * @param string $page_id Unused — kept for backward-compatible call sites.
	 */
	public static function render_modals( $page_id ) {
		// No-op: content is now embedded via wp_localize_script in enqueue_assets().
		// The overlay HTML is always rendered when assets are enqueued.
	}

	/**
	 * Render modal overlay HTML in footer (runs on every admin page where assets loaded).
	 */
	public static function render_modal_html() {
		if ( ! self::$assets_enqueued ) {
			return;
		}
		?>
	<!-- Asneris SEO Help Modal -->
	<div id="ASNERISSEO-help-modal-overlay" class="ASNERISSEO-modal-overlay" onclick="ASNERISSEOHelpModal.closeOnOverlay(event)">
		<div id="ASNERISSEO-help-modal" class="ASNERISSEO-modal">
		<div class="ASNERISSEO-modal-header">
			<h2 id="ASNERISSEO-modal-title"></h2>
			<button type="button" class="ASNERISSEO-modal-close" onclick="ASNERISSEOHelpModal.close()">
			<span class="dashicons dashicons-no"></span>
			</button>
		</div>
		<div class="ASNERISSEO-modal-content" id="ASNERISSEO-modal-content"></div>
		</div>
	</div>
		<?php
	}

	/**
	 * Enqueue modal scripts and styles, and localize ALL modal content so JS can
	 * access it immediately without any footer-hook timing dependency.
	 */
	public static function enqueue_assets() {
		if ( self::$assets_enqueued ) {
			return;
		}

		self::$assets_enqueued = true;

		wp_register_style( 'asnerisseo-help-modal', false, array(), ASNERISSEO_VERSION );
		wp_enqueue_style( 'asnerisseo-help-modal' );

		// Scoped CSS for help modal to avoid leaking global modal styles.
		$css = <<<'CSS'
.ASNERISSEO-help-icon {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  margin-left: 5px;
  color: #2271b1;
  vertical-align: middle;
}

.ASNERISSEO-help-icon:hover {
  color: #135e96;
}

.ASNERISSEO-help-icon .dashicons {
  font-size: 16px;
  width: 16px;
  height: 16px;
}

#ASNERISSEO-help-modal-overlay.ASNERISSEO-modal-overlay {
  display: flex;
  align-items: center;
  justify-content: center;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.7);
  z-index: 100000;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: opacity 0.2s ease, visibility 0.2s ease;
}

#ASNERISSEO-help-modal-overlay.ASNERISSEO-modal-overlay.active {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
}

#ASNERISSEO-help-modal-overlay .ASNERISSEO-modal {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
  z-index: 100001;
  max-width: 600px;
  width: 90%;
  max-height: 80vh;
  overflow: hidden;
  opacity: 0;
  transform: scale(0.95);
  transition: opacity 0.2s ease, transform 0.2s ease;
}

#ASNERISSEO-help-modal-overlay.active .ASNERISSEO-modal {
  opacity: 1;
  transform: scale(1);
}

#ASNERISSEO-help-modal-overlay .ASNERISSEO-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 25px;
  border-bottom: 1px solid #164e91;
  background: #06295f;
  color: #4eb8c5;
}

#ASNERISSEO-help-modal-overlay .ASNERISSEO-modal-header h2 {
  margin: 0;
  font-size: 18px;
  color: #4eb8c5;
}

#ASNERISSEO-help-modal-overlay .ASNERISSEO-modal-close {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  color: #4eb8c5;
  opacity: 0.9;
}

#ASNERISSEO-help-modal-overlay .ASNERISSEO-modal-close:hover {
  color: #ffffff;
  opacity: 1;
}

#ASNERISSEO-help-modal-overlay .ASNERISSEO-modal-close .dashicons {
  font-size: 24px;
  width: 24px;
  height: 24px;
}

#ASNERISSEO-help-modal-overlay .ASNERISSEO-modal-content {
  padding: 25px;
  overflow-y: auto;
  max-height: calc(80vh - 80px);
}

#ASNERISSEO-help-modal-overlay .ASNERISSEO-modal-content h3 {
  margin-top: 0;
  color: #1d2327;
  font-size: 16px;
}

#ASNERISSEO-help-modal-overlay .ASNERISSEO-modal-content p {
  line-height: 1.6;
  color: #3c434a;
}

#ASNERISSEO-help-modal-overlay .ASNERISSEO-modal-content code {
  background: #f6f7f7;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 13px;
}

#ASNERISSEO-help-modal-overlay .ASNERISSEO-modal-content ul {
  line-height: 1.8;
}

#ASNERISSEO-help-modal-overlay .ASNERISSEO-modal-content .ASNERISSEO-info-box {
  background: #e7f5fe;
  border-left: 4px solid #2271b1;
  padding: 12px 15px;
  margin: 15px 0;
  border-radius: 4px;
}

#ASNERISSEO-help-modal-overlay .ASNERISSEO-modal-content .ASNERISSEO-warning-box {
  background: #fff8e5;
  border-left: 4px solid #f0ad4e;
  padding: 12px 15px;
  margin: 15px 0;
  border-radius: 4px;
}
CSS;
		wp_add_inline_style( 'asnerisseo-help-modal', $css );

		// Register and enqueue modal JavaScript
		wp_register_script( 'asnerisseo-help-modal-js', false, array(), ASNERISSEO_VERSION, true );

		// Build a flat id→{title,body} map from every page's modals section.
		// This is embedded via wp_localize_script so it is always available when
		// the page loads — no footer-hook timing dependency.
		$flat_modals = array();
		$all_content = self::load_content();
		foreach ( $all_content as $page_data ) {
			if ( isset( $page_data['modals'] ) && is_array( $page_data['modals'] ) ) {
				foreach ( $page_data['modals'] as $modal_id => $modal ) {
					$flat_modals[ $modal_id ] = $modal;
				}
			}
		}
		// wp_localize_script outputs the data before the script tag — always in time.
		wp_localize_script( 'asnerisseo-help-modal-js', 'asnerisseoModalContent', $flat_modals );

		// SECURITY NOTE: content.innerHTML assignment is safe because modal content comes from:
		// 1. help-content.json bundled with plugin (plugin-controlled, not user input)
		// 2. JSON is parsed server-side via wp_localize_script() and output as a JS object literal
		// 3. No user-generated content is ever injected into modal body
		$core_js = 'window.ASNERISSEOHelpModal={open:function(contentId){var data=window.asnerisseoModalContent||{};if(!data[contentId])return;var overlay=document.getElementById("ASNERISSEO-help-modal-overlay");var title=document.getElementById("ASNERISSEO-modal-title");var content=document.getElementById("ASNERISSEO-modal-content");if(!overlay||!title||!content)return;title.textContent=data[contentId].title;content.innerHTML=data[contentId].body;overlay.classList.add("active");document.body.style.overflow="hidden";},close:function(){var overlay=document.getElementById("ASNERISSEO-help-modal-overlay");if(overlay){overlay.classList.remove("active");}document.body.style.overflow="";},closeOnOverlay:function(e){if(e.target===document.getElementById("ASNERISSEO-help-modal-overlay")){this.close();}}};document.addEventListener("keydown",function(e){if(e.key==="Escape"){window.ASNERISSEOHelpModal.close();}});';
		wp_add_inline_script( 'asnerisseo-help-modal-js', $core_js );
		wp_enqueue_script( 'asnerisseo-help-modal-js' );
	}

	/**
	 * Render help button for page header
	 */
	public static function render_help_button( $modal_id, $label = 'Help' ) {
		?>
	<button type="button" class="button button-secondary" onclick="ASNERISSEOHelpModal.open('<?php echo esc_js( $modal_id ); ?>')" style="margin-left: 10px; vertical-align: middle;">
		<span class="dashicons dashicons-editor-help" style="margin-top: 4px;"></span> <?php echo esc_html( $label ); ?>
	</button>
		<?php
	}

	/**
	 * Render help icon for inline use (next to labels)
	 */
	public static function render_help_icon( $modal_id, $title = 'Help' ) {
		?>
	<button type="button" class="ASNERISSEO-help-icon" onclick="ASNERISSEOHelpModal.open('<?php echo esc_js( $modal_id ); ?>')" title="<?php echo esc_attr( $title ); ?>" style="background: #2271b1; border: none; border-radius: 50%; width: 18px; height: 18px; padding: 0; margin-left: 5px; cursor: pointer; color: #ffffff; font-size: 12px; font-weight: bold; vertical-align: middle; line-height: 18px; display: inline-block; text-align: center;">
		?
	</button>
		<?php
	}
}
