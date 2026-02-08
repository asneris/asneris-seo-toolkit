<?php
if (!defined('ABSPATH')) exit;

class ASNERISSEO_Render {

  public static function render_meta_tags() {
    // Site-wide verification tags
    $google = ASNERISSEO_Admin_Settings::get('google_verification');
    if ($google) {
      echo '<meta name="google-site-verification" content="' . esc_attr($google) . '">' . "\n";
    }

    $bing = ASNERISSEO_Admin_Settings::get('bing_verification');
    if ($bing) {
      echo '<meta name="msvalidate.01" content="' . esc_attr($bing) . '">' . "\n";
    }

    $yandex = ASNERISSEO_Admin_Settings::get('yandex_verification');
    if ($yandex) {
      echo '<meta name="yandex-verification" content="' . esc_attr($yandex) . '">' . "\n";
    }

    if (!is_singular()) return;

    $id = get_queried_object_id();
    if (!$id) return;

    $post = get_post($id);
    if (!$post) return;

    // Get post meta
    $seo_title = get_post_meta($id, '_ASNERISSEO_title', true);
    $desc = get_post_meta($id, '_ASNERISSEO_description', true);
    $canon = get_post_meta($id, '_ASNERISSEO_canonical', true) ?: get_permalink($id);
    $robots_index = get_post_meta($id, '_ASNERISSEO_robots_index', true) ?: 'index';
    $robots_follow = get_post_meta($id, '_ASNERISSEO_robots_follow', true) ?: 'follow';
    
    // Open Graph meta
    $og_title = get_post_meta($id, '_ASNERISSEO_og_title', true);
    $og_description = get_post_meta($id, '_ASNERISSEO_og_description', true);
    $og_image = get_post_meta($id, '_ASNERISSEO_og_image', true);

    // Fallback values with template support
    $final_title = $seo_title;
    if (empty($final_title)) {
      // Try template first, then fallback to post title
      $template_title = ASNERISSEO_Templates::generate_title($post);
      $final_title = !empty($template_title) ? $template_title : get_the_title($id);
    }
    
    $final_desc = $desc;
    if (empty($final_desc)) {
      // Try template first, then fallback to excerpt
      $template_desc = ASNERISSEO_Templates::generate_description($post);
      $final_desc = !empty($template_desc) ? $template_desc : wp_trim_words(get_the_excerpt($id), 30);
    }
    
    $final_og_title = $og_title ?: $final_title;
    $final_og_desc = $og_description ?: $final_desc;
    $final_og_image = $og_image ?: ASNERISSEO_Admin_Settings::get('default_og_image');
    
    // If still no image, try featured image
    if (!$final_og_image && has_post_thumbnail($id)) {
      $final_og_image = get_the_post_thumbnail_url($id, 'large');
    }

    // Meta description
    if ($final_desc) {
      echo '<meta name="description" content="' . esc_attr($final_desc) . '">' . "\n";
    }

    // Canonical
    echo '<link rel="canonical" href="' . esc_url($canon) . '">' . "\n";
    
    // Robots with preview policies
    $robots_parts = [$robots_index, $robots_follow, 'max-image-preview:large', 'max-snippet:-1', 'max-video-preview:-1'];
    echo '<meta name="robots" content="' . esc_attr(implode(',', $robots_parts)) . '">' . "\n";

    // Open Graph tags
    echo '<meta property="og:title" content="' . esc_attr($final_og_title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($final_og_desc) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($canon) . '">' . "\n";
    echo '<meta property="og:type" content="' . (is_front_page() ? 'website' : 'article') . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '">' . "\n";
    
    if ($final_og_image) {
      echo '<meta property="og:image" content="' . esc_url($final_og_image) . '">' . "\n";
      
      // Get image dimensions if possible
      $image_id = attachment_url_to_postid($final_og_image);
      if ($image_id) {
        $image_meta = wp_get_attachment_metadata($image_id);
        if (isset($image_meta['width']) && isset($image_meta['height'])) {
          echo '<meta property="og:image:width" content="' . esc_attr($image_meta['width']) . '">' . "\n";
          echo '<meta property="og:image:height" content="' . esc_attr($image_meta['height']) . '">' . "\n";
        }
        $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        if ($alt_text) {
          echo '<meta property="og:image:alt" content="' . esc_attr($alt_text) . '">' . "\n";
        }
      }
    }

    // Article-specific OG tags (Pinterest Rich Pins, LinkedIn Articles)
    if (get_post_type($id) === 'post') {
      echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c', $id)) . '">' . "\n";
      echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c', $id)) . '">' . "\n";
      
      $author_id = get_post_field('post_author', $id);
      if ($author_id) {
        $author_name = get_the_author_meta('display_name', $author_id);
        echo '<meta property="article:author" content="' . esc_attr(get_author_posts_url($author_id)) . '">' . "\n";
        echo '<meta name="author" content="' . esc_attr($author_name) . '">' . "\n";
      }
      
      // Article section for LinkedIn
      $categories = get_the_category($id);
      if (!empty($categories)) {
        echo '<meta property="article:section" content="' . esc_attr($categories[0]->name) . '">' . "\n";
      }
      
      // Article tags for Pinterest Rich Pins
      $tags = get_the_tags($id);
      if ($tags) {
        foreach ($tags as $tag) {
          echo '<meta property="article:tag" content="' . esc_attr($tag->name) . '">' . "\n";
        }
      }
    }

    // Facebook App ID
    $fb_app_id = ASNERISSEO_Admin_Settings::get('facebook_app_id');
    if ($fb_app_id) {
      echo '<meta property="fb:app_id" content="' . esc_attr($fb_app_id) . '">' . "\n";
    }

    // Twitter Cards
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($final_og_title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($final_og_desc) . '">' . "\n";
    
    if ($final_og_image) {
      echo '<meta name="twitter:image" content="' . esc_url($final_og_image) . '">' . "\n";
    }
    
    $twitter_username = ASNERISSEO_Admin_Settings::get('twitter_username');
    if ($twitter_username) {
      $twitter_handle = (strpos($twitter_username, '@') === 0) ? $twitter_username : '@' . $twitter_username;
      echo '<meta name="twitter:site" content="' . esc_attr($twitter_handle) . '">' . "\n";
    }
    
    // Theme color for Discord, Telegram embeds, and mobile browsers
    $theme_color = ASNERISSEO_Admin_Settings::get('theme_color');
    if ($theme_color) {
      echo '<meta name="theme-color" content="' . esc_attr($theme_color) . '">' . "\n";
    }
  }
}
