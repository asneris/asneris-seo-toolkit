<?php
if (!defined('ABSPATH')) exit;

class ASNERISSEO_Templates {
  
  /**
   * Parse template string with variables
   * 
   * @param string $template Template string with {variables}
   * @param array $context Context data (post, site info, etc)
   * @return string Parsed template
   */
  public static function parse($template, $context = []) {
    if (empty($template)) {
      return '';
    }
    
    $defaults = [
      'title' => '',
      'site' => get_bloginfo('name'),
      'separator' => '|',
      'post_type' => '',
      'category' => '',
      'tag' => '',
      'author' => '',
      'date' => '',
    ];
    
    $vars = wp_parse_args($context, $defaults);
    
    // Replace variables
    $output = $template;
    foreach ($vars as $key => $value) {
      $output = str_replace('{' . $key . '}', $value, $output);
    }
    
    // Clean up any remaining unreplaced variables
    $output = preg_replace('/\{[^}]+\}/', '', $output);
    
    // Clean up extra spaces
    $output = preg_replace('/\s+/', ' ', $output);
    $output = trim($output);
    
    return $output;
  }
  
  /**
   * Get title template for a post type
   * 
   * @param string $post_type Post type name
   * @return string Template string
   */
  public static function get_title_template($post_type) {
    $templates = ASNERISSEO_Admin_Settings::get('title_templates', []);
    return $templates[$post_type] ?? '';
  }
  
  /**
   * Get description template for a post type
   * 
   * @param string $post_type Post type name
   * @return string Template string
   */
  public static function get_description_template($post_type) {
    $templates = ASNERISSEO_Admin_Settings::get('description_templates', []);
    return $templates[$post_type] ?? '';
  }
  
  /**
   * Generate title for a post using template
   * 
   * @param WP_Post $post Post object
   * @return string Generated title
   */
  public static function generate_title($post) {
    $template = self::get_title_template($post->post_type);
    
    if (empty($template)) {
      return $post->post_title;
    }
    
    $context = self::build_context($post);
    return self::parse($template, $context);
  }
  
  /**
   * Generate description for a post using template or excerpt
   * 
   * @param WP_Post $post Post object
   * @return string Generated description
   */
  public static function generate_description($post) {
    $template = self::get_description_template($post->post_type);
    
    // If template exists, use it
    if (!empty($template)) {
      $context = self::build_context($post);
      return self::parse($template, $context);
    }
    
    // Fallback to excerpt
    if (!empty($post->post_excerpt)) {
      return wp_trim_words($post->post_excerpt, 30, '...');
    }
    
    // Fallback to content
    $content = wp_strip_all_tags($post->post_content);
    $content = preg_replace('/\s+/', ' ', $content);
    return wp_trim_words($content, 30, '...');
  }
  
  /**
   * Build context array for template parsing
   * 
   * @param WP_Post $post Post object
   * @return array Context data
   */
  private static function build_context($post) {
    $context = [
      'title' => $post->post_title,
      'site' => get_bloginfo('name'),
      'separator' => ASNERISSEO_Admin_Settings::get('title_separator', '|'),
      'post_type' => get_post_type_object($post->post_type)->labels->singular_name ?? $post->post_type,
    ];
    
    // Get primary category
    $categories = get_the_category($post->ID);
    if (!empty($categories)) {
      $context['category'] = $categories[0]->name;
    }
    
    // Get first tag
    $tags = get_the_tags($post->ID);
    if (!empty($tags)) {
      $context['tag'] = $tags[0]->name;
    }
    
    // Author
    $author = get_userdata($post->post_author);
    if ($author) {
      $context['author'] = $author->display_name;
    }
    
    // Date
    $context['date'] = get_the_date('', $post->ID);
    $context['year'] = get_the_date('Y', $post->ID);
    $context['month'] = get_the_date('F', $post->ID);
    
    return $context;
  }
  
  /**
   * Get available template variables
   * 
   * @return array Variable descriptions
   */
  public static function get_available_variables() {
    return [
      '{title}' => __('Post/Page title', 'asneris-seo-toolkit'),
      '{site}' => __('Site name', 'asneris-seo-toolkit'),
      '{separator}' => __('Title separator', 'asneris-seo-toolkit'),
      '{post_type}' => __('Post type label', 'asneris-seo-toolkit'),
      '{category}' => __('Primary category', 'asneris-seo-toolkit'),
      '{tag}' => __('First tag', 'asneris-seo-toolkit'),
      '{author}' => __('Author name', 'asneris-seo-toolkit'),
      '{date}' => __('Publication date', 'asneris-seo-toolkit'),
      '{year}' => __('Publication year', 'asneris-seo-toolkit'),
      '{month}' => __('Publication month', 'asneris-seo-toolkit'),
    ];
  }
}
