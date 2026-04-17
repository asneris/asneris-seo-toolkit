<?php
/**
 * Help Page - Education & Support
 *
 * Purpose: Explain concepts and set expectations
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class ASNERISSEO_Help {

public static function register_menu() {
add_submenu_page(
ASNERIS_MENU_SLUG,
__( 'Help', 'asneris-seo-toolkit' ),
__( 'Help', 'asneris-seo-toolkit' ),
'manage_options',
ASNERIS_MENU_SLUG . '-help',
array( __CLASS__, 'render_page' )
);
}

public static function render_page() {
?>
<div class="wrap ASNERISSEO-admin-wrap">
<h1>
<span class="dashicons dashicons-editor-help"></span>
<?php esc_html_e( 'Help &amp; Documentation', 'asneris-seo-toolkit' ); ?>
</h1>

<div class="ASNERISSEO-card">
<h2><?php esc_html_e( 'What This Plugin Does', 'asneris-seo-toolkit' ); ?></h2>
<p><strong><?php esc_html_e( 'Asneris SEO Toolkit validates what search engines can see. It does not predict rankings.', 'asneris-seo-toolkit' ); ?></strong></p>
<ul style="line-height: 2;">
<li><span class="dashicons dashicons-yes-alt" style="color:#46b450"></span> <?php esc_html_e( 'Detects technical SEO signals on your pages', 'asneris-seo-toolkit' ); ?></li>
<li><span class="dashicons dashicons-yes-alt" style="color:#46b450"></span> <?php esc_html_e( 'Identifies conflicts and ambiguities', 'asneris-seo-toolkit' ); ?></li>
<li><span class="dashicons dashicons-yes-alt" style="color:#46b450"></span> <?php esc_html_e( 'Explains why clarity matters', 'asneris-seo-toolkit' ); ?></li>
<li><span class="dashicons dashicons-yes-alt" style="color:#46b450"></span> <?php esc_html_e( 'Helps prevent accidental SEO misconfiguration', 'asneris-seo-toolkit' ); ?></li>
<li><span class="dashicons dashicons-yes-alt" style="color:#46b450"></span> <?php esc_html_e( 'Provides safe redirect management', 'asneris-seo-toolkit' ); ?></li>
<li><span class="dashicons dashicons-yes-alt" style="color:#46b450"></span> <?php esc_html_e( 'Validates robots.txt syntax', 'asneris-seo-toolkit' ); ?></li>
</ul>
</div>

<div class="ASNERISSEO-card">
<h2 style="color: #d63638;"><?php esc_html_e( 'What This Plugin Does NOT Do', 'asneris-seo-toolkit' ); ?></h2>
<ul style="line-height: 2;">
<li><span class="dashicons dashicons-no-alt" style="color:#d63638"></span> <?php esc_html_e( 'Does NOT promise higher rankings', 'asneris-seo-toolkit' ); ?></li>
<li><span class="dashicons dashicons-no-alt" style="color:#d63638"></span> <?php esc_html_e( 'Does NOT provide SEO scores or grades', 'asneris-seo-toolkit' ); ?></li>
<li><span class="dashicons dashicons-no-alt" style="color:#d63638"></span> <?php esc_html_e( 'Does NOT predict algorithm behavior', 'asneris-seo-toolkit' ); ?></li>
<li><span class="dashicons dashicons-no-alt" style="color:#d63638"></span> <?php esc_html_e( 'Does NOT track backlinks or competitors', 'asneris-seo-toolkit' ); ?></li>
<li><span class="dashicons dashicons-no-alt" style="color:#d63638"></span> <?php esc_html_e( 'Does NOT rewrite your content with AI', 'asneris-seo-toolkit' ); ?></li>
<li><span class="dashicons dashicons-no-alt" style="color:#d63638"></span> <?php esc_html_e( 'Does NOT analyze keyword density', 'asneris-seo-toolkit' ); ?></li>
<li><span class="dashicons dashicons-no-alt" style="color:#d63638"></span> <?php esc_html_e( 'Does NOT guarantee traffic or conversions', 'asneris-seo-toolkit' ); ?></li>
<li><span class="dashicons dashicons-no-alt" style="color:#d63638"></span> <?php esc_html_e( 'Does NOT submit data to third-party services without consent', 'asneris-seo-toolkit' ); ?></li>
</ul>
</div>

<div class="ASNERISSEO-card">
<h2><?php esc_html_e( 'Key SEO Concepts', 'asneris-seo-toolkit' ); ?></h2>

<h3><?php esc_html_e( 'What is a Title Tag?', 'asneris-seo-toolkit' ); ?></h3>
<p><?php esc_html_e( 'The', 'asneris-seo-toolkit' ); ?> <code>&lt;title&gt;</code> <?php esc_html_e( 'element in your page HTML. It appears in browser tabs and search results. Search engines may use it to understand page content.', 'asneris-seo-toolkit' ); ?></p>
<p><strong><?php esc_html_e( 'Important:', 'asneris-seo-toolkit' ); ?></strong> <?php esc_html_e( 'Having a title tag does not guarantee rankings. It provides clarity about your page topic.', 'asneris-seo-toolkit' ); ?></p>

<h3><?php esc_html_e( 'What is a Canonical URL?', 'asneris-seo-toolkit' ); ?></h3>
<p><?php esc_html_e( 'A', 'asneris-seo-toolkit' ); ?> <code>&lt;link rel="canonical"&gt;</code> <?php esc_html_e( 'tag tells search engines which URL is the official version when duplicate or similar content exists.', 'asneris-seo-toolkit' ); ?></p>
<p><strong><?php esc_html_e( 'Important:', 'asneris-seo-toolkit' ); ?></strong> <?php esc_html_e( 'Canonical tags are suggestions, not commands. Search engines may choose different URLs.', 'asneris-seo-toolkit' ); ?></p>

<h3><?php esc_html_e( 'What is Meta Robots (noindex)?', 'asneris-seo-toolkit' ); ?></h3>
<p><?php esc_html_e( 'A', 'asneris-seo-toolkit' ); ?> <code>&lt;meta name="robots" content="noindex"&gt;</code> <?php esc_html_e( 'tag blocks search engines from indexing a page.', 'asneris-seo-toolkit' ); ?></p>
<p><strong><?php esc_html_e( 'Important:', 'asneris-seo-toolkit' ); ?></strong> <?php esc_html_e( 'This does NOT hide the page from users or remove it from your website. It only affects search engine indexing.', 'asneris-seo-toolkit' ); ?></p>

<h3><?php esc_html_e( 'What is Schema Markup?', 'asneris-seo-toolkit' ); ?></h3>
<p><?php esc_html_e( 'Structured data (JSON-LD) that helps search engines understand entities on your page (articles, products, events, etc.).', 'asneris-seo-toolkit' ); ?></p>
<p><strong><?php esc_html_e( 'Important:', 'asneris-seo-toolkit' ); ?></strong> <?php esc_html_e( 'Schema does NOT guarantee rich results. It provides clarity, not ranking boosts.', 'asneris-seo-toolkit' ); ?></p>

<h3><?php esc_html_e( 'What is a 301 Redirect?', 'asneris-seo-toolkit' ); ?></h3>
<p><?php esc_html_e( 'A permanent redirect from one URL to another. Used when content moves. Redirects help preserve existing signals when URLs change.', 'asneris-seo-toolkit' ); ?></p>
<p><strong><?php esc_html_e( 'Important:', 'asneris-seo-toolkit' ); ?></strong> <?php esc_html_e( 'Redirects preserve existing value. They do not improve rankings or create new value.', 'asneris-seo-toolkit' ); ?></p>

<h3><?php esc_html_e( 'What is Robots.txt?', 'asneris-seo-toolkit' ); ?></h3>
<p><?php esc_html_e( 'A file that tells search engine crawlers which parts of your site to avoid crawling.', 'asneris-seo-toolkit' ); ?></p>
<p><strong><?php esc_html_e( 'Important:', 'asneris-seo-toolkit' ); ?></strong> <?php esc_html_e( 'Robots.txt is about crawl efficiency, not security. It does not hide content from users.', 'asneris-seo-toolkit' ); ?></p>
</div>

<div class="ASNERISSEO-card">
<h2><?php esc_html_e( 'Understanding Validation Status', 'asneris-seo-toolkit' ); ?></h2>

<h3 style="color: #46b450;"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Pass', 'asneris-seo-toolkit' ); ?></h3>
<p><?php esc_html_e( 'Clear, unambiguous signals were detected. This does NOT mean "perfect SEO" or "guaranteed ranking."', 'asneris-seo-toolkit' ); ?></p>

<h3 style="color: #f0ad4e;"><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Warning', 'asneris-seo-toolkit' ); ?></h3>
<p><?php esc_html_e( 'Something is missing or could be clearer. This does NOT mean failure or penalty.', 'asneris-seo-toolkit' ); ?></p>

<h3 style="color: #dc3232;"><span class="dashicons dashicons-no-alt"></span> <?php esc_html_e( 'Conflict', 'asneris-seo-toolkit' ); ?></h3>
<p><?php esc_html_e( 'Contradictory signals were detected. This creates clarity risk but does NOT guarantee indexing failure.', 'asneris-seo-toolkit' ); ?></p>
</div>

<div class="ASNERISSEO-card">
<h2><?php esc_html_e( 'Support &amp; Feedback', 'asneris-seo-toolkit' ); ?></h2>
<p><strong><span class="dashicons dashicons-beaker"></span> <?php esc_html_e( 'This is beta software.', 'asneris-seo-toolkit' ); ?></strong> <?php esc_html_e( 'Features and behavior may change.', 'asneris-seo-toolkit' ); ?></p>
<p><strong><?php esc_html_e( 'Need help or found a bug?', 'asneris-seo-toolkit' ); ?></strong></p>
<ul>
<li><span class="dashicons dashicons-flag"></span> <?php esc_html_e( 'Report issues:', 'asneris-seo-toolkit' ); ?> <a href="https://wordpress.org/support/plugin/asneris-seo-toolkit/" target="_blank"><?php esc_html_e( 'WordPress.org Support', 'asneris-seo-toolkit' ); ?></a></li>
</ul>
</div>

<div class="ASNERISSEO-card" style="background: #f6f7f7; border-left: 4px solid #2271b1;">
<h2><?php esc_html_e( 'Our Philosophy', 'asneris-seo-toolkit' ); ?></h2>
<p style="font-size: 16px; line-height: 1.8;">
<?php esc_html_e( 'SEO is not about gaming algorithms or chasing scores. It is about making your content clear, accessible, and understandable to search engines. This plugin helps you validate that clarity - nothing more, nothing less.', 'asneris-seo-toolkit' ); ?>
</p>
<p style="font-size: 14px; color: #646970; margin-top: 15px;">
<em><?php esc_html_e( '"Asneris SEO Toolkit validates what search engines can see. It does not predict rankings."', 'asneris-seo-toolkit' ); ?></em>
</p>
</div>

</div>
<?php
}
}