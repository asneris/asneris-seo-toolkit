<?php
if (!defined('ABSPATH')) exit;

class ASNERISSEO_Schema {
  public static function render_jsonld() {
    $org_name = ASNERISSEO_Admin_Settings::get('org_name', get_bloginfo('name'));
    $org_logo = ASNERISSEO_Admin_Settings::get('org_logo', '');

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
    if (ASNERISSEO_Admin_Settings::get('enable_local_business')) {
      $business = [
        '@type' => ASNERISSEO_Admin_Settings::get('business_type', 'LocalBusiness'),
        '@id' => $site_url . '#localbusiness',
        'name' => $org_name ?: get_bloginfo('name'),
        'url' => $site_url,
      ];
      
      if ($org_logo) {
        $business['image'] = $org_logo;
      }
      
      $phone = ASNERISSEO_Admin_Settings::get('business_phone');
      if ($phone) {
        $business['telephone'] = $phone;
      }
      
      $address = ASNERISSEO_Admin_Settings::get('business_address');
      if ($address) {
        $business['address'] = [
          '@type' => 'PostalAddress',
          'streetAddress' => $address
        ];
      }
      
      // Opening Hours
      $hours = ASNERISSEO_Admin_Settings::get('business_hours');
      if ($hours) {
        $hours_array = array_filter(array_map('trim', explode("\n", $hours)));
        if (!empty($hours_array)) {
          $opening_hours = [];
          foreach ($hours_array as $line) {
            // Parse lines like "Monday: 9:00 AM - 5:00 PM" or "Monday-Friday: 9:00 AM - 5:00 PM"
            if (preg_match('/^([^:]+):\s*(.+)$/i', $line, $matches)) {
              $opening_hours[] = trim($matches[1]) . ' ' . trim($matches[2]);
            }
          }
          if (!empty($opening_hours)) {
            $business['openingHoursSpecification'] = array_map(function($spec) {
              return ['@type' => 'OpeningHoursSpecification', 'description' => $spec];
            }, $opening_hours);
          }
        }
      }
      
      // Service Area
      $service_area = ASNERISSEO_Admin_Settings::get('service_area');
      if ($service_area) {
        $areas = array_filter(array_map('trim', explode(',', $service_area)));
        if (!empty($areas)) {
          $business['areaServed'] = count($areas) === 1 ? $areas[0] : $areas;
        }
      }
      
      // Price Range
      $price_range = ASNERISSEO_Admin_Settings::get('price_range');
      if ($price_range) {
        $business['priceRange'] = $price_range;
      }
      
      // Payment Methods
      $payment_methods = ASNERISSEO_Admin_Settings::get('payment_methods');
      if ($payment_methods) {
        $methods = array_filter(array_map('trim', explode(',', $payment_methods)));
        if (!empty($methods)) {
          $business['paymentAccepted'] = implode(', ', $methods);
        }
      }
      
      // Languages Spoken
      $languages = ASNERISSEO_Admin_Settings::get('languages_spoken');
      if ($languages) {
        $langs = array_filter(array_map('trim', explode(',', $languages)));
        if (!empty($langs)) {
          $business['availableLanguage'] = count($langs) === 1 ? $langs[0] : $langs;
        }
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
      $enabled = get_post_meta($id, '_ASNERISSEO_schema_enabled', true);
      
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
        
        $description = get_post_meta($id, '_ASNERISSEO_description', true);
        if ($description) {
          $webpage['description'] = $description;
        }
        
        $graph[] = $webpage;
      }
      
      // Content-specific schemas based on post type and metadata
      $schema_type = get_post_meta($id, '_ASNERISSEO_schema_type', true);
      
      // Auto-detect if not manually set
      if (!$schema_type) {
        $schema_type = self::auto_detect_schema_type($id);
      }
      
      // Generate appropriate schema
      $content_schema = self::generate_content_schema($id, $schema_type, $post, $permalink, $org, $site_url);
      if ($content_schema) {
        $graph[] = $content_schema;
      }
      
      // BreadcrumbList schema (if enabled)
      if (ASNERISSEO_Admin_Settings::get('enable_breadcrumbs')) {
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
   * Auto-detect schema type based on post type and content
   */
  private static function auto_detect_schema_type($post_id) {
    $post_type = get_post_type($post_id);
    
    // Check for WooCommerce
    if ($post_type === 'product' && class_exists('WooCommerce')) {
      return 'Product';
    }
    
    // Check for events plugins
    if (in_array($post_type, ['tribe_events', 'event', 'mec-events'])) {
      return 'Event';
    }
    
    // Check for course/learning plugins
    if (in_array($post_type, ['sfwd-courses', 'course', 'lp_course'])) {
      return 'Course';
    }
    
    // Check for recipe plugins
    if (in_array($post_type, ['recipe', 'wprm_recipe'])) {
      return 'Recipe';
    }
    
    // Default based on post type
    if ($post_type === 'post') {
      return 'Article';
    }
    
    if ($post_type === 'page') {
      return 'WebPage';
    }
    
    return 'Article'; // Fallback
  }
  
  /**
   * Generate content-specific schema
   */
  private static function generate_content_schema($id, $type, $post, $permalink, $org, $site_url) {
    // Common properties
    $description = get_post_meta($id, '_ASNERISSEO_description', true);
    $og_image = get_post_meta($id, '_ASNERISSEO_og_image', true);
    if (!$og_image && has_post_thumbnail($id)) {
      $og_image = get_the_post_thumbnail_url($id, 'large');
    }
    if (!$og_image) {
      $og_image = ASNERISSEO_Admin_Settings::get('default_og_image');
    }
    
    switch ($type) {
      case 'Article':
      case 'BlogPosting':
      case 'NewsArticle':
        return self::generate_article_schema($id, $type, $post, $permalink, $org, $site_url, $description, $og_image);
      
      case 'Product':
        return self::generate_product_schema($id, $post, $permalink, $og, $description, $og_image);
      
      case 'Event':
        return self::generate_event_schema($id, $post, $permalink, $org, $description, $og_image);
      
      case 'Course':
        return self::generate_course_schema($id, $post, $permalink, $org, $description, $og_image);
      
      case 'Recipe':
        return self::generate_recipe_schema($id, $post, $permalink, $org, $description, $og_image);
      
      case 'VideoObject':
        return self::generate_video_schema($id, $post, $permalink, $org, $description, $og_image);
      
      case 'FAQPage':
        return self::generate_faq_schema($id, $post, $permalink, $description);
      
      case 'HowTo':
        return self::generate_howto_schema($id, $post, $permalink, $org, $description, $og_image);
      
      case 'JobPosting':
        return self::generate_job_schema($id, $post, $permalink, $org, $description);
      
      case 'Service':
        return self::generate_service_schema($id, $post, $permalink, $org, $description, $og_image);
      
      case 'WebPage':
      default:
        return self::generate_webpage_schema($id, $post, $permalink, $site_url, $description);
    }
  }
  
  /**
   * Article schema (BlogPosting, NewsArticle, etc.)
   */
  private static function generate_article_schema($id, $type, $post, $permalink, $org, $site_url, $description, $og_image) {
    $article = [
      '@type' => $type,
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
    
    if ($description) {
      $article['description'] = $description;
    }
    
    // Author
    $author_id = $post->post_author;
    if ($author_id) {
      $author_name = get_the_author_meta('display_name', $author_id);
      $article['author'] = [
        '@type' => 'Person',
        'name' => $author_name,
        'url' => get_author_posts_url($author_id)
      ];
    }
    
    // Image
    if ($og_image) {
      $article['image'] = [
        '@type' => 'ImageObject',
        'url' => $og_image
      ];
    }
    
    return $article;
  }
  
  /**
   * Product schema (WooCommerce/E-commerce)
   */
  private static function generate_product_schema($id, $post, $permalink, $org, $description, $og_image) {
    $product = [
      '@type' => 'Product',
      '@id' => $permalink . '#product',
      'name' => get_the_title($id),
      'url' => $permalink,
    ];
    
    if ($description) {
      $product['description'] = $description;
    }
    
    if ($og_image) {
      $product['image'] = $og_image;
    }
    
    // WooCommerce specific
    if (class_exists('WooCommerce')) {
      $wc_product = wc_get_product($id);
      if ($wc_product) {
        $product['offers'] = [
          '@type' => 'Offer',
          'price' => $wc_product->get_price(),
          'priceCurrency' => get_woocommerce_currency(),
          'availability' => $wc_product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
          'url' => $permalink
        ];
        
        // Brand
        $brand = get_post_meta($id, '_ASNERISSEO_product_brand', true);
        if ($brand) {
          $product['brand'] = [
            '@type' => 'Brand',
            'name' => $brand
          ];
        }
        
        // Rating
        if ($wc_product->get_average_rating()) {
          $product['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $wc_product->get_average_rating(),
            'reviewCount' => $wc_product->get_review_count()
          ];
        }
      }
    }
    
    return $product;
  }
  
  /**
   * Event schema
   */
  private static function generate_event_schema($id, $post, $permalink, $org, $description, $og_image) {
    $event = [
      '@type' => 'Event',
      '@id' => $permalink . '#event',
      'name' => get_the_title($id),
      'url' => $permalink,
    ];
    
    if ($description) {
      $event['description'] = $description;
    }
    
    if ($og_image) {
      $event['image'] = $og_image;
    }
    
    // Start date
    $start_date = get_post_meta($id, '_ASNERISSEO_event_start_date', true);
    if ($start_date) {
      $event['startDate'] = $start_date;
    }
    
    // End date
    $end_date = get_post_meta($id, '_ASNERISSEO_event_end_date', true);
    if ($end_date) {
      $event['endDate'] = $end_date;
    }
    
    // Location
    $location_name = get_post_meta($id, '_ASNERISSEO_event_location_name', true);
    $location_address = get_post_meta($id, '_ASNERISSEO_event_location_address', true);
    if ($location_name) {
      $event['location'] = [
        '@type' => 'Place',
        'name' => $location_name
      ];
      if ($location_address) {
        $event['location']['address'] = [
          '@type' => 'PostalAddress',
          'streetAddress' => $location_address
        ];
      }
    }
    
    // Organizer
    $event['organizer'] = ['@id' => $org['@id']];
    
    return $event;
  }
  
  /**
   * Course schema
   */
  private static function generate_course_schema($id, $post, $permalink, $org, $description, $og_image) {
    $course = [
      '@type' => 'Course',
      '@id' => $permalink . '#course',
      'name' => get_the_title($id),
      'url' => $permalink,
      'provider' => ['@id' => $org['@id']]
    ];
    
    if ($description) {
      $course['description'] = $description;
    }
    
    if ($og_image) {
      $course['image'] = $og_image;
    }
    
    return $course;
  }
  
  /**
   * Recipe schema
   */
  private static function generate_recipe_schema($id, $post, $permalink, $org, $description, $og_image) {
    $recipe = [
      '@type' => 'Recipe',
      '@id' => $permalink . '#recipe',
      'name' => get_the_title($id),
      'url' => $permalink,
    ];
    
    if ($description) {
      $recipe['description'] = $description;
    }
    
    if ($og_image) {
      $recipe['image'] = $og_image;
    }
    
    // Author
    $author_id = $post->post_author;
    if ($author_id) {
      $author_name = get_the_author_meta('display_name', $author_id);
      $recipe['author'] = [
        '@type' => 'Person',
        'name' => $author_name
      ];
    }
    
    // Cooking time
    $cook_time = get_post_meta($id, '_ASNERISSEO_recipe_cook_time', true);
    if ($cook_time) {
      $recipe['cookTime'] = 'PT' . $cook_time . 'M';
    }
    
    // Prep time
    $prep_time = get_post_meta($id, '_ASNERISSEO_recipe_prep_time', true);
    if ($prep_time) {
      $recipe['prepTime'] = 'PT' . $prep_time . 'M';
    }
    
    return $recipe;
  }
  
  /**
   * Video schema
   */
  private static function generate_video_schema($id, $post, $permalink, $org, $description, $og_image) {
    $video = [
      '@type' => 'VideoObject',
      '@id' => $permalink . '#video',
      'name' => get_the_title($id),
      'url' => $permalink,
      'uploadDate' => get_the_date('c', $id),
    ];
    
    if ($description) {
      $video['description'] = $description;
    }
    
    if ($og_image) {
      $video['thumbnailUrl'] = $og_image;
    }
    
    // Video URL
    $video_url = get_post_meta($id, '_ASNERISSEO_video_url', true);
    if ($video_url) {
      $video['contentUrl'] = $video_url;
    }
    
    // Duration
    $duration = get_post_meta($id, '_ASNERISSEO_video_duration', true);
    if ($duration) {
      $video['duration'] = 'PT' . $duration . 'S';
    }
    
    return $video;
  }
  
  /**
   * FAQ schema
   */
  private static function generate_faq_schema($id, $post, $permalink, $description) {
    $faq = [
      '@type' => 'FAQPage',
      '@id' => $permalink . '#faq',
      'url' => $permalink,
    ];
    
    // Parse FAQ from content or custom field
    $faq_items = get_post_meta($id, '_ASNERISSEO_faq_items', true);
    if ($faq_items && is_array($faq_items)) {
      $main_entity = [];
      foreach ($faq_items as $item) {
        $main_entity[] = [
          '@type' => 'Question',
          'name' => $item['question'],
          'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $item['answer']
          ]
        ];
      }
      $faq['mainEntity'] = $main_entity;
    }
    
    return $faq;
  }
  
  /**
   * HowTo schema
   */
  private static function generate_howto_schema($id, $post, $permalink, $org, $description, $og_image) {
    $howto = [
      '@type' => 'HowTo',
      '@id' => $permalink . '#howto',
      'name' => get_the_title($id),
      'url' => $permalink,
    ];
    
    if ($description) {
      $howto['description'] = $description;
    }
    
    if ($og_image) {
      $howto['image'] = $og_image;
    }
    
    // Steps
    $steps = get_post_meta($id, '_ASNERISSEO_howto_steps', true);
    if ($steps && is_array($steps)) {
      $step_list = [];
      foreach ($steps as $index => $step) {
        $step_list[] = [
          '@type' => 'HowToStep',
          'position' => $index + 1,
          'name' => $step['name'],
          'text' => $step['text']
        ];
      }
      $howto['step'] = $step_list;
    }
    
    return $howto;
  }
  
  /**
   * Job Posting schema
   */
  private static function generate_job_schema($id, $post, $permalink, $org, $description) {
    $job = [
      '@type' => 'JobPosting',
      '@id' => $permalink . '#job',
      'title' => get_the_title($id),
      'url' => $permalink,
      'datePosted' => get_the_date('c', $id),
      'hiringOrganization' => ['@id' => $org['@id']]
    ];
    
    if ($description) {
      $job['description'] = $description;
    }
    
    // Job location
    $location = get_post_meta($id, '_ASNERISSEO_job_location', true);
    if ($location) {
      $job['jobLocation'] = [
        '@type' => 'Place',
        'address' => [
          '@type' => 'PostalAddress',
          'addressLocality' => $location
        ]
      ];
    }
    
    // Employment type
    $employment_type = get_post_meta($id, '_ASNERISSEO_job_employment_type', true);
    if ($employment_type) {
      $job['employmentType'] = $employment_type;
    }
    
    return $job;
  }
  
  /**
   * Service schema
   */
  private static function generate_service_schema($id, $post, $permalink, $org, $description, $og_image) {
    $service = [
      '@type' => 'Service',
      '@id' => $permalink . '#service',
      'name' => get_the_title($id),
      'url' => $permalink,
      'provider' => ['@id' => $org['@id']]
    ];
    
    if ($description) {
      $service['description'] = $description;
    }
    
    if ($og_image) {
      $service['image'] = $og_image;
    }
    
    return $service;
  }
  
  /**
   * WebPage schema (default fallback)
   */
  private static function generate_webpage_schema($id, $post, $permalink, $site_url, $description) {
    $webpage = [
      '@type' => 'WebPage',
      '@id' => $permalink . '#webpage',
      'url' => $permalink,
      'name' => get_the_title($id),
      'isPartOf' => ['@id' => $site_url . '#website'],
      'datePublished' => get_the_date('c', $id),
      'dateModified' => get_the_modified_date('c', $id),
    ];
    
    if ($description) {
      $webpage['description'] = $description;
    }
    
    return $webpage;
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
