<?php
if (!defined('ABSPATH')) exit;

class GSCSEO_Schema {
  public static function render_jsonld() {
    $org_name = GSCSEO_Admin_Settings::get('org_name', get_bloginfo('name'));
    $org_logo = GSCSEO_Admin_Settings::get('org_logo', '');

    $site_url = home_url('/');

    // Organization schema
    $org = [
      '@type' => 'Organization',
      '@id'   => $site_url . '#organization',
      'name'  => $org_name ?: get_bloginfo('name'),
      'url'   => $site_url,
    ];
    if ($org_logo) {
      $org['logo'] = [
        '@type' => 'ImageObject',
        'url' => $org_logo,
      ];
    }

    // Website schema
    $website = [
      '@type' => 'WebSite',
      '@id'   => $site_url . '#website',
      'url'   => $site_url,
      'name'  => get_bloginfo('name'),
      'publisher' => ['@id' => $org['@id']],
    ];

    $graph = [$org, $website];

    // Add Local Business schema if enabled
    if (GSCSEO_Admin_Settings::get('enable_local_business')) {
      $business = [
        '@type' => GSCSEO_Admin_Settings::get('business_type', 'LocalBusiness'),
        '@id' => $site_url . '#localbusiness',
        'name' => $org_name ?: get_bloginfo('name'),
        'url' => $site_url,
      ];
      
      if ($org_logo) {
        $business['image'] = $org_logo;
      }
      
      $phone = GSCSEO_Admin_Settings::get('business_phone');
      if ($phone) {
        $business['telephone'] = $phone;
      }
      
      $address = GSCSEO_Admin_Settings::get('business_address');
      if ($address) {
        $business['address'] = [
          '@type' => 'PostalAddress',
          'streetAddress' => $address
        ];
      }
      
      $graph[] = $business;
    }

    // Page-specific schema
    if (is_singular()) {
      $id = get_queried_object_id();
      if (!$id) {
        self::output_schema($graph);
        return;
      }

      $post = get_post($id);
      $enabled = get_post_meta($id, '_gscseo_schema_enabled', true);
      
      if ($enabled === '0') {
        self::output_schema($graph);
        return;
      }

      $permalink = get_permalink($id);
      
      // WebPage schema (for pages)
      if (get_post_type($id) === 'page') {
        $webpage = [
          '@type' => 'WebPage',
          '@id' => $permalink . '#webpage',
          'url' => $permalink,
          'name' => get_the_title($id),
          'isPartOf' => ['@id' => $site_url . '#website'],
          'datePublished' => get_the_date('c', $id),
          'dateModified' => get_the_modified_date('c', $id),
        ];
        
        $description = get_post_meta($id, '_gscseo_description', true);
        if ($description) {
          $webpage['description'] = $description;
        }
        
        $graph[] = $webpage;
      }
      
      // Article schema (for posts)
      if (get_post_type($id) === 'post') {
        $article = [
          '@type' => 'Article',
          '@id' => $permalink . '#article',
          'headline' => get_the_title($id),
          'url' => $permalink,
          'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $permalink
          ],
          'datePublished' => get_the_date('c', $id),
          'dateModified' => get_the_modified_date('c', $id),
          'publisher' => ['@id' => $org['@id']],
          'isPartOf' => ['@id' => $site_url . '#website'],
        ];
        
        // Add description if available
        $description = get_post_meta($id, '_gscseo_description', true);
        if ($description) {
          $article['description'] = $description;
        }
        
        // Add author
        $author_id = $post->post_author;
        if ($author_id) {
          $author_name = get_the_author_meta('display_name', $author_id);
          $article['author'] = [
            '@type' => 'Person',
            'name' => $author_name,
            'url' => get_author_posts_url($author_id)
          ];
        }
        
        // Add image
        $og_image = get_post_meta($id, '_gscseo_og_image', true);
        if (!$og_image && has_post_thumbnail($id)) {
          $og_image = get_the_post_thumbnail_url($id, 'large');
        }
        if (!$og_image) {
          $og_image = GSCSEO_Admin_Settings::get('default_og_image');
        }
        
        if ($og_image) {
          $article['image'] = [
            '@type' => 'ImageObject',
            'url' => $og_image
          ];
        }
        
        $graph[] = $article;
      }
      
      // BreadcrumbList schema (if enabled)
      if (GSCSEO_Admin_Settings::get('enable_breadcrumbs')) {
        $breadcrumbs = self::generate_breadcrumbs($id);
        if (!empty($breadcrumbs)) {
          $graph[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $permalink . '#breadcrumb',
            'itemListElement' => $breadcrumbs
          ];
        }
      }
    }

    self::output_schema($graph);
  }

  /**
   * Generate breadcrumb schema
   */
  private static function generate_breadcrumbs($post_id) {
    $items = [];
    $position = 1;
    
    // Home
    $items[] = [
      '@type' => 'ListItem',
      'position' => $position++,
      'name' => get_bloginfo('name'),
      'item' => home_url('/')
    ];
    
    // For posts, add category
    if (get_post_type($post_id) === 'post') {
      $categories = get_the_category($post_id);
      if (!empty($categories)) {
        $category = $categories[0];
        $items[] = [
          '@type' => 'ListItem',
          'position' => $position++,
          'name' => $category->name,
          'item' => get_category_link($category->term_id)
        ];
      }
    }
    
    // For pages, add parent pages
    if (get_post_type($post_id) === 'page') {
      $ancestors = get_post_ancestors($post_id);
      $ancestors = array_reverse($ancestors);
      foreach ($ancestors as $ancestor_id) {
        $items[] = [
          '@type' => 'ListItem',
          'position' => $position++,
          'name' => get_the_title($ancestor_id),
          'item' => get_permalink($ancestor_id)
        ];
      }
    }
    
    // Current page
    $items[] = [
      '@type' => 'ListItem',
      'position' => $position,
      'name' => get_the_title($post_id),
      'item' => get_permalink($post_id)
    ];
    
    return $items;
  }

  /**
   * Output the schema JSON
   */
  private static function output_schema($graph) {
    $data = [
      '@context' => 'https://schema.org',
      '@graph'   => $graph,
    ];

    echo '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
  }
}
