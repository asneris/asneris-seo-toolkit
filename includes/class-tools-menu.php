<?php
/**
 * Top-level Tools Menu for Clarity-First SEO
 * 
 * @package Clarity_First_SEO
 * @since 0.2.0
 */

if (!defined('ABSPATH')) exit;

class ASNERISSEO_Tools_Menu {
  
  /**
   * Register top-level menu (just creates the parent, dashboard handled by settings)
   */
  public static function register_top_level_menu() {
    add_menu_page(
      __('Asneris SEO Toolkit', 'asneris-seo-toolkit'),
      __('Asneris SEO Toolkit', 'asneris-seo-toolkit'),
      'manage_options',
      ASNERIS_MENU_SLUG,
      '', // No callback - will be handled by first submenu (Dashboard/Settings)
      'dashicons-chart-line',
      26 // Position after Comments
    );
  }
}
