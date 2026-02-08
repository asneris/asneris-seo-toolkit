<?php
/**
 * Help Page - Education & Support
 * 
 * Purpose: Explain concepts and set expectations
 */

if (!defined('ABSPATH')) exit;

class ASNERISSEO_Help {
  
  /**
   * Register help page
   */
  public static function register_menu() {
    add_submenu_page(
      ASNERIS_MENU_SLUG,
      __('Help', 'asneris-seo-toolkit'),
      __('Help', 'asneris-seo-toolkit'),
      'manage_options',
      ASNERIS_MENU_SLUG . '-help',
      [__CLASS__, 'render_page']
    );
  }
  
  /**
   * Render help page
   */
  public static function render_page() {
    ?>
    <div class="wrap ASNERISSEO-admin-wrap">
      <h1>
        <span class="dashicons dashicons-editor-help"></span>
        <?php esc_html_e('Help & Documentation', 'asneris-seo-toolkit'); ?>
      </h1>
      
      <!-- What This Plugin Does -->
      <div class="ASNERISSEO-card">
        <h2>What This Plugin Does</h2>
        <p><strong>Asneris SEO Toolkit validates what search engines can see. It does not predict rankings.</strong></p>
        
        <ul style="line-height: 2;">
          <li>✅ Detects technical SEO signals on your pages</li>
          <li>✅ Identifies conflicts and ambiguities</li>
          <li>✅ Explains why clarity matters</li>
          <li>✅ Helps prevent accidental SEO misconfiguration</li>
          <li>✅ Provides safe redirect management</li>
          <li>✅ Validates robots.txt syntax</li>
        </ul>
      </div>
      
      <!-- What This Plugin Does NOT Do -->
      <div class="ASNERISSEO-card">
        <h2 style="color: #d63638;">What This Plugin Does NOT Do</h2>
        
        <ul style="line-height: 2;">
          <li>❌ Does NOT promise higher rankings</li>
          <li>❌ Does NOT provide SEO scores or grades</li>
          <li>❌ Does NOT predict algorithm behavior</li>
          <li>❌ Does NOT track backlinks or competitors</li>
          <li>❌ Does NOT rewrite your content with AI</li>
          <li>❌ Does NOT analyze keyword density</li>
          <li>❌ Does NOT guarantee traffic or conversions</li>
          <li>❌ Does NOT submit data to third-party services without consent</li>
        </ul>
      </div>
      
      <!-- Key Concepts -->
      <div class="ASNERISSEO-card">
        <h2>Key SEO Concepts</h2>
        
        <h3>What is a Title Tag?</h3>
        <p>The <code>&lt;title&gt;</code> element in your page's HTML. It appears in browser tabs and search results. Search engines may use it to understand page content.</p>
        <p><strong>Important:</strong> Having a title tag does not guarantee rankings. It provides clarity about your page's topic.</p>
        
        <h3>What is a Canonical URL?</h3>
        <p>A <code>&lt;link rel="canonical"&gt;</code> tag tells search engines which URL is the "official" version when duplicate or similar content exists.</p>
        <p><strong>Important:</strong> Canonical tags are suggestions, not commands. Search engines may choose different URLs.</p>
        
        <h3>What is Meta Robots (noindex)?</h3>
        <p>A <code>&lt;meta name="robots" content="noindex"&gt;</code> tag blocks search engines from indexing a page.</p>
        <p><strong>Important:</strong> This does NOT hide the page from users or remove it from your website. It only affects search engine indexing.</p>
        
        <h3>What is Schema Markup?</h3>
        <p>Structured data (JSON-LD) that helps search engines understand entities on your page (articles, products, events, etc.).</p>
        <p><strong>Important:</strong> Schema does NOT guarantee rich results. It provides clarity, not ranking boosts.</p>
        
        <h3>What is a 301 Redirect?</h3>
        <p>A permanent redirect from one URL to another. Used when content moves. Redirects help preserve existing signals when URLs change.</p>
        <p><strong>Important:</strong> Redirects preserve existing value. They do not improve rankings or create new value.</p>
        
        <h3>What is Robots.txt?</h3>
        <p>A file that tells search engine crawlers which parts of your site to avoid crawling.</p>
        <p><strong>Important:</strong> Robots.txt is about crawl efficiency, not security. It does not hide content from users.</p>
      </div>
      
      <!-- Understanding Validation Status -->
      <div class="ASNERISSEO-card">
        <h2>Understanding Validation Status</h2>
        
        <h3 style="color: #46b450;">✅ Pass</h3>
        <p>Clear, unambiguous signals were detected. This does NOT mean "perfect SEO" or "guaranteed ranking."</p>
        
        <h3 style="color: #f0ad4e;">⚠️ Warning</h3>
        <p>Something is missing or could be clearer. This does NOT mean failure or penalty.</p>
        
        <h3 style="color: #dc3232;">❌ Conflict</h3>
        <p>Contradictory signals were detected. This creates clarity risk but does NOT guarantee indexing failure.</p>
      </div>
      
      <!-- Support & Feedback -->
      <div class="ASNERISSEO-card">
        <h2>Support & Feedback</h2>
        
        <p><strong>🧪 This is beta software.</strong> Features and behavior may change.</p>
        
        <p><strong>Need help or found a bug?</strong></p>
        <ul>
          <li>🐛 Report issues: <a href="https://github.com/asneris/asneris-seo-toolkit/issues" target="_blank">GitHub Issues</a></li>
        </ul>
      </div>
      
      <!-- Philosophy -->
      <div class="ASNERISSEO-card" style="background: #f6f7f7; border-left: 4px solid #2271b1;">
        <h2>Our Philosophy</h2>
        <p style="font-size: 16px; line-height: 1.8;">
          SEO is not about gaming algorithms or chasing scores. 
          It's about making your content clear, accessible, and understandable to search engines.
          This plugin helps you validate that clarity—nothing more, nothing less.
        </p>
        <p style="font-size: 14px; color: #646970; margin-top: 15px;">
          <em>"Asneris SEO Toolkit validates what search engines can see. It does not predict rankings."</em>
        </p>
      </div>
      
    </div>
    <?php
  }
}
