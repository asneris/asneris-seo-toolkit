<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASNERISSEO_SEO_Generator {
	const MANUAL_TITLE_KEY = '_ASNERISSEO_title';
	const MANUAL_DESCRIPTION_KEY = '_ASNERISSEO_description';
	const MANUAL_TITLE_KEY_ALT = '_asneris_seo_title';
	const MANUAL_DESCRIPTION_KEY_ALT = '_asneris_meta_description';

	// Spec-defined generated storage keys.
	const GENERATED_TITLE_KEY = '_asneris_generated_title';
	const GENERATED_DESCRIPTION_KEY = '_asneris_generated_description';
	const GENERATED_HASH_KEY = '_asneris_generated_hash';

	const BATCH_CURSOR_OPTION = 'asneris_seo_generator_batch_cursor';
	const BATCH_FAILURE_LOG_OPTION = 'asneris_seo_generator_batch_failures';
	const BATCH_METRICS_OPTION = 'asneris_seo_generator_batch_metrics';

	public static function init() {
		add_action( 'save_post', array( __CLASS__, 'on_save_post' ), 20, 3 );
		add_action( 'set_object_terms', array( __CLASS__, 'on_set_object_terms' ), 20, 6 );
	}

	public static function on_save_post( $post_id, $post, $update ) {
		unset( $update );

		if ( ! ( $post instanceof WP_Post ) ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		$post_type_obj = get_post_type_object( $post->post_type );
		if ( ! $post_type_obj || empty( $post_type_obj->public ) ) {
			return;
		}

		self::generate_for_post( $post_id, true );
	}

	public static function on_set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		unset( $terms, $tt_ids, $append, $old_tt_ids );

		if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
			return;
		}

		$post = get_post( $object_id );
		if ( ! $post || wp_is_post_revision( $object_id ) || wp_is_post_autosave( $object_id ) ) {
			return;
		}

		self::generate_for_post( (int) $object_id, true );
	}

	public static function generate_for_post( $post_id, $skip_if_manual_exists = true, $force_regenerate = false ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array( 'updated' => false, 'reason' => 'missing_post' );
		}

		$collector = new ASNERISSEO_SEO_Data_Collector();
		$cleaner = new ASNERISSEO_SEO_Content_Cleaner();
		$keyword_detector = new ASNERISSEO_SEO_Keyword_Detector();
		$title_generator = new ASNERISSEO_SEO_Title_Generator();
		$description_generator = new ASNERISSEO_SEO_Description_Generator();
		$validator = new ASNERISSEO_SEO_Validator();
		$duplicate_checker = new ASNERISSEO_SEO_Duplicate_Checker();
		$storage = new ASNERISSEO_SEO_Metadata_Storage();

		$data = $collector->collect( $post );
		$data = $cleaner->clean( $data );

		$source_hash = md5( wp_json_encode( array(
			'post_title' => (string) $data['post_title'],
			'slug' => (string) $data['slug'],
			'excerpt' => (string) $data['excerpt'],
			'content' => (string) $data['content'],
			'first_paragraph' => (string) $data['first_paragraph'],
			'h1' => (string) $data['h1'],
			'categories' => (array) $data['categories'],
		) ) );

		$existing_hash = (string) get_post_meta( $post_id, self::GENERATED_HASH_KEY, true );
		if ( ! $force_regenerate && $existing_hash !== '' && hash_equals( $existing_hash, $source_hash ) ) {
			return array( 'updated' => false, 'reason' => 'unchanged_source' );
		}

		$manual_title = self::get_manual_title( $post_id );
		$manual_description = self::get_manual_description( $post_id );

		if ( $skip_if_manual_exists && ( $manual_title !== '' || $manual_description !== '' ) ) {
			update_post_meta( $post_id, self::GENERATED_HASH_KEY, $source_hash );
			return array( 'updated' => false, 'reason' => 'manual_exists' );
		}

		$primary_keyword = $keyword_detector->detect( $data );

		$title = $title_generator->generate( $data, $primary_keyword );
		$title = $validator->validate_title( $title, $primary_keyword );
		$title = $duplicate_checker->ensure_unique_title( $title, $post_id, $data );

		$description = $description_generator->generate( $data, $primary_keyword );
		$description = $validator->validate_description( $description, $primary_keyword );

		$storage_result = $storage->store_generated( $post_id, $title, $description );
		update_post_meta( $post_id, self::GENERATED_HASH_KEY, $source_hash );

		return array(
			'updated' => (bool) $storage_result,
			'keyword' => $primary_keyword,
			'generatedTitle' => $title,
			'generatedDescription' => $description,
		);
	}

	private static function read_manual_meta( $post_id, $primary_key, $alt_key ) {
		$primary = trim( (string) get_post_meta( $post_id, $primary_key, true ) );
		if ( $primary !== '' ) {
			return $primary;
		}

		return trim( (string) get_post_meta( $post_id, $alt_key, true ) );
	}

	public static function get_manual_title( $post_id ) {
		return self::read_manual_meta( $post_id, self::MANUAL_TITLE_KEY, self::MANUAL_TITLE_KEY_ALT );
	}

	public static function get_manual_description( $post_id ) {
		return self::read_manual_meta( $post_id, self::MANUAL_DESCRIPTION_KEY, self::MANUAL_DESCRIPTION_KEY_ALT );
	}

	public static function get_effective_title( $post_id, $post = null ) {
		$manual = self::get_manual_title( $post_id );
		if ( $manual !== '' ) {
			return $manual;
		}

		$generated = trim( (string) get_post_meta( $post_id, self::GENERATED_TITLE_KEY, true ) );
		if ( $generated !== '' ) {
			return $generated;
		}

		if ( ! $post ) {
			$post = get_post( $post_id );
		}
		if ( $post ) {
			$template_title = ASNERISSEO_Templates::generate_title( $post );
			if ( ! empty( $template_title ) ) {
				return $template_title;
			}
		}

		return '';
	}

	public static function get_effective_description( $post_id, $post = null ) {
		$manual = self::get_manual_description( $post_id );
		if ( $manual !== '' ) {
			return $manual;
		}

		$generated = trim( (string) get_post_meta( $post_id, self::GENERATED_DESCRIPTION_KEY, true ) );
		if ( $generated !== '' ) {
			return $generated;
		}

		if ( ! $post ) {
			$post = get_post( $post_id );
		}
		if ( $post ) {
			$template_desc = ASNERISSEO_Templates::generate_description( $post );
			if ( ! empty( $template_desc ) ) {
				return $template_desc;
			}
		}

		return '';
	}

	public static function run_batch( $batch_size = 50, $options = array() ) {
		$batch_size = max( 1, min( 100, (int) $batch_size ) );
		$reset_cursor = ! empty( $options['resetCursor'] );
		$force_regenerate = ! empty( $options['regenerate'] );
		$skip_if_manual_exists = ! isset( $options['skipManual'] ) || (bool) $options['skipManual'];

		if ( $reset_cursor ) {
			delete_option( self::BATCH_CURSOR_OPTION );
		}

		$cursor = $reset_cursor ? 0 : max( 0, (int) get_option( self::BATCH_CURSOR_OPTION, 0 ) );

		$query = new WP_Query( array(
			'post_type' => get_post_types( array( 'public' => true ), 'names' ),
			'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'fields' => 'ids',
			'posts_per_page' => $batch_size,
			'offset' => $cursor,
			'orderby' => 'ID',
			'order' => 'ASC',
			'no_found_rows' => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		) );

		$processed = 0;
		$updated = 0;
		$skipped_manual = 0;
		$skipped_unchanged = 0;
		$failures = array();
		$total_elapsed_ms = 0.0;

		foreach ( (array) $query->posts as $post_id ) {
			$processed++;
			$started_at = microtime( true );
			try {
				$result = self::generate_for_post( (int) $post_id, $skip_if_manual_exists, $force_regenerate );
				if ( ! empty( $result['updated'] ) ) {
					$updated++;
				} elseif ( isset( $result['reason'] ) && 'manual_exists' === $result['reason'] ) {
					$skipped_manual++;
				} elseif ( isset( $result['reason'] ) && 'unchanged_source' === $result['reason'] ) {
					$skipped_unchanged++;
				}
			} catch ( Throwable $e ) {
				$failure = array(
					'post_id' => (int) $post_id,
					'error' => self::redact_failure_message( $e->getMessage() ),
					'time' => gmdate( 'c' ),
				);
				$failures[] = $failure;
				self::append_failure_log( $failure );
			} finally {
				$total_elapsed_ms += max( 0, ( microtime( true ) - $started_at ) * 1000 );
			}
		}

		$next_cursor = $cursor + $processed;
		$total_posts = (int) $query->found_posts;
		$completed = $next_cursor >= $total_posts;

		if ( $completed ) {
			delete_option( self::BATCH_CURSOR_OPTION );
		} else {
			update_option( self::BATCH_CURSOR_OPTION, $next_cursor, false );
		}

		$avg_generation_ms = $processed > 0 ? round( $total_elapsed_ms / $processed, 2 ) : 0;
		$batch_metrics = array(
			'processed' => $processed,
			'updated' => $updated,
			'skippedManual' => $skipped_manual,
			'skippedUnchanged' => $skipped_unchanged,
			'failed' => count( $failures ),
			'avgGenerationMs' => $avg_generation_ms,
			'elapsedMs' => round( $total_elapsed_ms, 2 ),
			'generatedAt' => gmdate( 'c' ),
			'mode' => $force_regenerate ? 'regenerate' : 'generate',
		);
		update_option( self::BATCH_METRICS_OPTION, $batch_metrics, false );

		return array(
			'batchSize' => $batch_size,
			'processed' => $processed,
			'updated' => $updated,
			'skippedManual' => $skipped_manual,
			'skippedUnchanged' => $skipped_unchanged,
			'failed' => count( $failures ),
			'failures' => $failures,
			'avgGenerationMs' => $avg_generation_ms,
			'elapsedMs' => round( $total_elapsed_ms, 2 ),
			'mode' => $force_regenerate ? 'regenerate' : 'generate',
			'cursor' => $next_cursor,
			'total' => $total_posts,
			'completed' => $completed,
		);
	}

	public static function get_last_batch_metrics() {
		$metrics = get_option( self::BATCH_METRICS_OPTION, array() );
		return is_array( $metrics ) ? $metrics : array();
	}

	private static function append_failure_log( array $failure ) {
		$existing = get_option( self::BATCH_FAILURE_LOG_OPTION, array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		$existing[] = $failure;
		if ( count( $existing ) > 200 ) {
			$existing = array_slice( $existing, -200 );
		}

		update_option( self::BATCH_FAILURE_LOG_OPTION, $existing, false );
	}

	private static function redact_failure_message( $message ) {
		$clean = sanitize_text_field( (string) $message );

		// Redact common local filesystem paths and URLs from exception text.
		$clean = preg_replace( '/[A-Za-z]:\\[^\s]+/', '[path redacted]', $clean );
		$clean = preg_replace( '#/(?:[^\s/]+/){2,}[^\s]*#', '[path redacted]', (string) $clean );
		$clean = preg_replace( '#https?://\S+#i', '[url redacted]', (string) $clean );

		$clean = trim( (string) $clean );
		if ( '' === $clean ) {
			return 'Batch generation failed.';
		}

		if ( strlen( $clean ) > 220 ) {
			$clean = substr( $clean, 0, 220 ) . '...';
		}

		return $clean;
	}
}

class ASNERISSEO_SEO_Data_Collector {
	public function collect( WP_Post $post ) {
		$post_id = (int) $post->ID;
		$content_raw = (string) $post->post_content;
		$excerpt_raw = (string) $post->post_excerpt;

		$h1 = $this->extract_heading( $content_raw, 'h1' );
		$h2 = $this->extract_all_headings( $content_raw, 'h2' );
		$first_paragraph = $this->extract_first_paragraph( $content_raw );

		$categories = array();
		$category_terms = get_the_terms( $post_id, 'category' );
		if ( is_array( $category_terms ) ) {
			$categories = wp_list_pluck( $category_terms, 'name' );
		}

		$tags = array();
		$tag_terms = get_the_terms( $post_id, 'post_tag' );
		if ( is_array( $tag_terms ) ) {
			$tags = wp_list_pluck( $tag_terms, 'name' );
		}

		$author_name = '';
		$author = get_userdata( (int) $post->post_author );
		if ( $author ) {
			$author_name = (string) $author->display_name;
		}

		return array(
			'post_id' => $post_id,
			'post_title' => (string) $post->post_title,
			'slug' => (string) $post->post_name,
			'excerpt' => $excerpt_raw,
			'content' => $content_raw,
			'first_paragraph' => $first_paragraph,
			'h1' => $h1,
			'h2_headings' => $h2,
			'categories' => $categories,
			'tags' => $tags,
			'author' => $author_name,
			'site_name' => get_bloginfo( 'name' ),
			'post_type' => (string) $post->post_type,
			'date' => get_the_date( 'Y-m-d', $post_id ),
		);
	}

	private function extract_heading( $html, $tag ) {
		if ( preg_match( '#<' . preg_quote( $tag, '#' ) . '[^>]*>(.*?)</' . preg_quote( $tag, '#' ) . '>#is', (string) $html, $matches ) ) {
			return wp_strip_all_tags( (string) $matches[1], true );
		}
		return '';
	}

	private function extract_all_headings( $html, $tag ) {
		$headings = array();
		if ( preg_match_all( '#<' . preg_quote( $tag, '#' ) . '[^>]*>(.*?)</' . preg_quote( $tag, '#' ) . '>#is', (string) $html, $matches ) ) {
			foreach ( (array) $matches[1] as $raw ) {
				$text = trim( wp_strip_all_tags( (string) $raw, true ) );
				if ( $text !== '' ) {
					$headings[] = $text;
				}
			}
		}
		return $headings;
	}

	private function extract_first_paragraph( $html ) {
		if ( preg_match( '#<p[^>]*>(.*?)</p>#is', (string) $html, $matches ) ) {
			return wp_strip_all_tags( (string) $matches[1], true );
		}
		return '';
	}
}

class ASNERISSEO_SEO_Content_Cleaner {
	public function clean( array $data ) {
		foreach ( array( 'post_title', 'slug', 'excerpt', 'content', 'first_paragraph', 'h1', 'author', 'site_name' ) as $field ) {
			$data[ $field ] = $this->normalize_text( isset( $data[ $field ] ) ? $data[ $field ] : '' );
		}

		if ( isset( $data['h2_headings'] ) && is_array( $data['h2_headings'] ) ) {
			$data['h2_headings'] = array_values( array_filter( array_map( array( $this, 'normalize_text' ), $data['h2_headings'] ) ) );
		}

		if ( isset( $data['categories'] ) && is_array( $data['categories'] ) ) {
			$data['categories'] = array_values( array_filter( array_map( array( $this, 'normalize_text' ), $data['categories'] ) ) );
		}

		if ( isset( $data['tags'] ) && is_array( $data['tags'] ) ) {
			$data['tags'] = array_values( array_filter( array_map( array( $this, 'normalize_text' ), $data['tags'] ) ) );
		}

		if ( empty( $data['first_paragraph'] ) ) {
			$data['first_paragraph'] = $this->extract_first_meaningful_sentence( (string) $data['content'] );
		}

		return $data;
	}

	private function normalize_text( $value ) {
		$text = (string) $value;
		$text = preg_replace( '#<script[^>]*>.*?</script>#is', ' ', $text );
		$text = preg_replace( '#<style[^>]*>.*?</style>#is', ' ', $text );
		$text = strip_shortcodes( $text );
		$text = wp_strip_all_tags( $text, true );
		$text = preg_replace( '/[\x00-\x1F\x7F\x{200B}-\x{200D}\x{FEFF}]/u', ' ', $text );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( $text );
	}

	private function extract_first_meaningful_sentence( $content ) {
		if ( $content === '' ) {
			return '';
		}

		if ( preg_match( '/(.{40,}?[.!?])\s/u', $content, $matches ) ) {
			return trim( $matches[1] );
		}

		return trim( mb_substr( $content, 0, 220 ) );
	}
}

class ASNERISSEO_SEO_Keyword_Detector {
	public function detect( array $data ) {
		$candidates = array();

		$candidates[] = $this->normalize_phrase( (string) $data['post_title'] );
		$candidates[] = $this->normalize_phrase( (string) $data['h1'] );
		$candidates[] = $this->normalize_phrase( str_replace( '-', ' ', (string) $data['slug'] ) );

		if ( ! empty( $data['categories'][0] ) ) {
			$candidates[] = $this->normalize_phrase( (string) $data['categories'][0] );
		}

		$frequent = $this->extract_frequent_phrase( (string) $data['content'] );
		if ( $frequent !== '' ) {
			$candidates[] = $frequent;
		}

		foreach ( $candidates as $candidate ) {
			if ( $candidate !== '' ) {
				return $candidate;
			}
		}

		return $this->normalize_phrase( (string) $data['post_title'] );
	}

	private function normalize_phrase( $text ) {
		$text = strtolower( trim( (string) $text ) );
		$text = preg_replace( '/[^a-z0-9\s-]/', ' ', $text );
		$text = preg_replace( '/\s+/', ' ', $text );
		$parts = array_filter( explode( ' ', $text ) );
		$stop_words = array( 'the', 'a', 'an', 'and', 'or', 'for', 'to', 'of', 'in', 'on', 'with', 'by', 'at', 'is', 'are', 'be' );
		$filtered = array();
		foreach ( $parts as $part ) {
			if ( strlen( $part ) > 2 && ! in_array( $part, $stop_words, true ) ) {
				$filtered[] = $part;
			}
		}

		return trim( implode( ' ', array_slice( $filtered, 0, 5 ) ) );
	}

	private function extract_frequent_phrase( $text ) {
		$words = preg_split( '/\s+/', strtolower( preg_replace( '/[^a-z0-9\s]/', ' ', (string) $text ) ) );
		if ( ! is_array( $words ) || count( $words ) < 6 ) {
			return '';
		}

		$stop_words = array( 'the', 'a', 'an', 'and', 'or', 'for', 'to', 'of', 'in', 'on', 'with', 'by', 'at', 'is', 'are', 'be' );
		$freq = array();
		foreach ( $words as $word ) {
			$word = trim( $word );
			if ( strlen( $word ) <= 3 || in_array( $word, $stop_words, true ) ) {
				continue;
			}
			$freq[ $word ] = isset( $freq[ $word ] ) ? $freq[ $word ] + 1 : 1;
		}

		arsort( $freq );
		$top = array_slice( array_keys( $freq ), 0, 3 );
		return implode( ' ', $top );
	}
}

class ASNERISSEO_SEO_Title_Generator {
	public function generate( array $data, $keyword ) {
		$template = ASNERISSEO_Admin_Settings::get( 'title_templates', array() );
		$post_type = (string) $data['post_type'];
		$separator = (string) ASNERISSEO_Admin_Settings::get( 'title_separator', '|' );
		$separator = trim( $separator ) !== '' ? trim( $separator ) : '|';

		$template_for_type = '';
		if ( is_array( $template ) && isset( $template[ $post_type ] ) ) {
			$template_for_type = (string) $template[ $post_type ];
		}
		if ( $template_for_type === '' ) {
			$template_for_type = '%title% ' . $separator . ' %sitename%';
		}

		$title_text = trim( (string) $data['post_title'] );
		if ( $keyword !== '' && stripos( $title_text, $keyword ) === false ) {
			$title_text = trim( $keyword . ' - ' . $title_text );
		}

		$replacements = array(
			'%title%' => $title_text,
			'%sitename%' => (string) $data['site_name'],
			'%category%' => isset( $data['categories'][0] ) ? (string) $data['categories'][0] : '',
			'%author%' => (string) $data['author'],
			'%date%' => (string) $data['date'],
			'%separator%' => $separator,
		);

		$title = strtr( $template_for_type, $replacements );
		$title = preg_replace( '/\s+/', ' ', trim( $title ) );
		$title = preg_replace( '/\s*\|\s*\|+\s*/', ' | ', $title );
		$title = $this->trim_to_length( $title, 60, 50 );
		$title = $this->remove_repeated_words( $title );

		return trim( $title );
	}

	private function trim_to_length( $text, $max, $min ) {
		$text = trim( (string) $text );
		if ( mb_strlen( $text ) <= $max ) {
			return $text;
		}

		$trimmed = mb_substr( $text, 0, $max + 1 );
		$last_space = mb_strrpos( $trimmed, ' ' );
		if ( false !== $last_space && $last_space >= $min ) {
			return trim( mb_substr( $trimmed, 0, $last_space ) );
		}
		return trim( mb_substr( $text, 0, $max ) );
	}

	private function remove_repeated_words( $text ) {
		$parts = preg_split( '/\s+/', (string) $text );
		$out = array();
		$seen = array();
		foreach ( (array) $parts as $part ) {
			$key = strtolower( preg_replace( '/[^a-z0-9]/i', '', $part ) );
			if ( $key === '' ) {
				continue;
			}
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$out[] = $part;
		}

		return implode( ' ', $out );
	}
}

class ASNERISSEO_SEO_Description_Generator {
	public function generate( array $data, $keyword ) {
		$source = '';

		if ( trim( (string) $data['excerpt'] ) !== '' ) {
			$source = (string) $data['excerpt'];
		} elseif ( trim( (string) $data['first_paragraph'] ) !== '' ) {
			$source = (string) $data['first_paragraph'];
		} else {
			$source = (string) $data['content'];
		}

		$source = trim( preg_replace( '/\s+/', ' ', $source ) );
		if ( $source === '' ) {
			$source = sprintf(
				/* translators: %s: site name */
				__( 'Discover practical insights and SEO improvements on %s.', 'asneris-seo-toolkit' ),
				(string) $data['site_name']
			);
		}

		$description = $this->trim_to_sentence( $source, 160, 140 );
		if ( $keyword !== '' && stripos( $description, $keyword ) === false ) {
			$description = $this->prepend_keyword( $description, $keyword, 160 );
		}

		return $description;
	}

	private function trim_to_sentence( $text, $max, $min ) {
		$text = trim( (string) $text );
		if ( mb_strlen( $text ) <= $max && mb_strlen( $text ) >= $min ) {
			return $this->ensure_sentence_end( $text );
		}

		$trimmed = mb_substr( $text, 0, $max + 1 );
		$last_punctuation = max(
			(int) mb_strrpos( $trimmed, '.' ),
			(int) mb_strrpos( $trimmed, '!' ),
			(int) mb_strrpos( $trimmed, '?' )
		);

		if ( $last_punctuation > 0 && $last_punctuation >= $min - 1 ) {
			return trim( mb_substr( $trimmed, 0, $last_punctuation + 1 ) );
		}

		$last_space = mb_strrpos( mb_substr( $trimmed, 0, $max ), ' ' );
		if ( false !== $last_space ) {
			$trimmed = mb_substr( $trimmed, 0, $last_space );
		}

		return $this->ensure_sentence_end( trim( $trimmed ) );
	}

	private function ensure_sentence_end( $text ) {
		$text = rtrim( (string) $text, " \t\n\r\0\x0B" );
		if ( $text === '' ) {
			return $text;
		}
		if ( ! preg_match( '/[.!?]$/', $text ) ) {
			$text .= '.';
		}
		return $text;
	}

	private function prepend_keyword( $description, $keyword, $max ) {
		$keyword = trim( (string) $keyword );
		if ( $keyword === '' ) {
			return $description;
		}

		$candidate = ucfirst( $keyword ) . ': ' . ltrim( (string) $description );
		if ( mb_strlen( $candidate ) > $max ) {
			return (new self())->trim_to_sentence( $candidate, $max, 120 );
		}
		return $candidate;
	}
}

class ASNERISSEO_SEO_Validator {
	public function validate_title( $title, $keyword ) {
		$title = trim( preg_replace( '/\s+/', ' ', (string) $title ) );
		$title = preg_replace( '/\|\s*\|+/', '|', $title );
		$title = $this->remove_excessive_separators( $title );

		if ( mb_strlen( $title ) > 60 ) {
			$title = rtrim( mb_substr( $title, 0, 60 ) );
			$last_space = mb_strrpos( $title, ' ' );
			if ( false !== $last_space ) {
				$title = mb_substr( $title, 0, $last_space );
			}
		}

		if ( $keyword !== '' && stripos( $title, $keyword ) === false ) {
			$title = trim( $keyword . ' | ' . $title );
		}

		return $title;
	}

	public function validate_description( $description, $keyword ) {
		$description = trim( preg_replace( '/\s+/', ' ', (string) $description ) );

		if ( mb_strlen( $description ) > 160 ) {
			$description = rtrim( mb_substr( $description, 0, 160 ) );
			$last_space = mb_strrpos( $description, ' ' );
			if ( false !== $last_space ) {
				$description = mb_substr( $description, 0, $last_space );
			}
		}

		if ( ! preg_match( '/[.!?]$/', $description ) ) {
			$description .= '.';
		}

		if ( $keyword !== '' && stripos( $description, $keyword ) === false ) {
			$prefix = ucfirst( trim( $keyword ) ) . ' - ';
			if ( mb_strlen( $prefix . $description ) <= 160 ) {
				$description = $prefix . $description;
			}
		}

		return $description;
	}

	private function remove_excessive_separators( $title ) {
		return trim( preg_replace( '/\s*([|\-:])\s*\1+\s*/', ' $1 ', (string) $title ) );
	}
}

class ASNERISSEO_SEO_Duplicate_Checker {
	public function ensure_unique_title( $title, $post_id, array $data ) {
		global $wpdb;

		$base = trim( (string) $title );
		if ( $base === '' ) {
			return $base;
		}

		$cache_key = 'title_exists:' . md5( strtolower( $base ) . '|' . (int) $post_id );
		$exists = wp_cache_get( $cache_key, 'asnerisseo_seo' );

		if ( false === $exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Prepared and cached metadata existence lookup for duplicate-title validation.
			$exists = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(1) FROM {$wpdb->postmeta} pm
					 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
					 WHERE pm.meta_key IN (%s, %s, %s)
					   AND pm.meta_value = %s
					   AND pm.post_id != %d
					   AND p.post_status NOT IN ('trash', 'auto-draft')",
					ASNERISSEO_SEO_Generator::MANUAL_TITLE_KEY,
					ASNERISSEO_SEO_Generator::MANUAL_TITLE_KEY_ALT,
					ASNERISSEO_SEO_Generator::GENERATED_TITLE_KEY,
					$base,
					(int) $post_id
				)
			);
			wp_cache_set( $cache_key, (int) $exists, 'asnerisseo_seo', 5 * MINUTE_IN_SECONDS );
		}

		if ( (int) $exists < 1 ) {
			return $base;
		}

		$suffixes = array_filter( array(
			(string) $data['site_name'],
			isset( $data['categories'][0] ) ? (string) $data['categories'][0] : '',
			gmdate( 'Y' ),
		) );

		foreach ( $suffixes as $suffix ) {
			$candidate = trim( $base . ' | ' . $suffix );
			if ( mb_strlen( $candidate ) <= 60 ) {
				return $candidate;
			}
		}

		return $base;
	}
}

class ASNERISSEO_SEO_Metadata_Storage {
	public function store_generated( $post_id, $title, $description ) {
		$title = sanitize_text_field( (string) $title );
		$description = sanitize_text_field( (string) $description );

		$ok1 = update_post_meta( $post_id, ASNERISSEO_SEO_Generator::GENERATED_TITLE_KEY, $title );
		$ok2 = update_post_meta( $post_id, ASNERISSEO_SEO_Generator::GENERATED_DESCRIPTION_KEY, $description );

		return (bool) ( $ok1 || $ok2 );
	}
}
