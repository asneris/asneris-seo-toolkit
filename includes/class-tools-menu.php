<?php
/**
 * Top-level Tools Menu for Clarity-First SEO
 * 
 * @package Clarity_First_SEO
 * @since 0.2.0
 */

if (!defined('ABSPATH')) exit;

class CFSEO_Tools_Menu {
  
  /**
   * Register top-level menu (just creates the parent, dashboard handled by settings)
   */
  public static function register_top_level_menu() {
    add_menu_page(
      __('Clarity-First SEO', 'clarity-first-seo'),
      __('Clarity-First SEO', 'clarity-first-seo'),
      'manage_options',
      'clarity-first-seo',
      '', // No callback - will be handled by first submenu (Dashboard/Settings)
      'dashicons-chart-line',
      26 // Position after Comments
    );
  }
}
