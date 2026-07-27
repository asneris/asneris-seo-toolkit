<?php
if (!defined('ABSPATH')) exit;

/**
 * Canonical data interface normalizer for diagnostics payloads.
 *
 * This class is additive-only and does not change existing response fields.
 */
class ASNERISSEO_Data_Interface_Normalizer {
  const INTERFACE_VERSION = '2026-07-03.1';

  private static function value_or_null(array $source, $key) {
    if (!array_key_exists($key, $source)) {
      return null;
    }

    return $source[$key];
  }

  private static function field_state(array $source, $key) {
    if (!array_key_exists($key, $source)) {
      return 'cannot_find';
    }

    $value = $source[$key];

    if (null === $value) {
      return 'can_get(null)';
    }

    if (is_string($value) && '' === trim($value)) {
      return 'can_get(null)';
    }

    if (is_array($value) && empty($value)) {
      return 'can_get(null)';
    }

    return 'can_get(value)';
  }

  private static function with_envelope(array $raw, array $computed, array $field_states, array $source_meta) {
    $flow = isset($source_meta['sourceFlow']) ? sanitize_key((string) $source_meta['sourceFlow']) : 'unknown';
    $engine = isset($source_meta['sourceEngine']) ? sanitize_key((string) $source_meta['sourceEngine']) : 'unknown';
    $mode = isset($source_meta['sourceMode']) ? sanitize_key((string) $source_meta['sourceMode']) : 'unknown';

    return [
      'interfaceVersion' => self::INTERFACE_VERSION,
      'sourceFlow' => $flow,
      'sourceEngine' => $engine,
      'sourceMode' => $mode,
      'raw' => $raw,
      'computed' => $computed,
      'fieldStates' => $field_states,
    ];
  }

  public static function normalize_overview_item(array $item, array $source_meta = []) {
    $raw = [
      'postId' => is_numeric(self::value_or_null($item, 'postId')) ? (int) self::value_or_null($item, 'postId') : null,
      'postType' => null !== self::value_or_null($item, 'postType') ? (string) self::value_or_null($item, 'postType') : null,
      'postStatus' => null !== self::value_or_null($item, 'postStatus') ? (string) self::value_or_null($item, 'postStatus') : null,
      'url' => null !== self::value_or_null($item, 'url') ? esc_url_raw((string) self::value_or_null($item, 'url')) : null,
      'title' => null !== self::value_or_null($item, 'title') ? (string) self::value_or_null($item, 'title') : null,
      'excerpt' => null !== self::value_or_null($item, 'excerpt') ? (string) self::value_or_null($item, 'excerpt') : null,
      'seoTitle' => null !== self::value_or_null($item, 'seoTitle') ? (string) self::value_or_null($item, 'seoTitle') : null,
      'seoDescription' => null !== self::value_or_null($item, 'seoDescription') ? (string) self::value_or_null($item, 'seoDescription') : null,
      'metaDescription' => null !== self::value_or_null($item, 'metaDescription') ? (string) self::value_or_null($item, 'metaDescription') : null,
      'titleLength' => is_numeric(self::value_or_null($item, 'titleLength')) ? (int) self::value_or_null($item, 'titleLength') : null,
      'hasCustomTitle' => null !== self::value_or_null($item, 'hasCustomTitle') ? (bool) self::value_or_null($item, 'hasCustomTitle') : null,
      'hasCustomDescription' => null !== self::value_or_null($item, 'hasCustomDescription') ? (bool) self::value_or_null($item, 'hasCustomDescription') : null,
      'hasCanonical' => null !== self::value_or_null($item, 'hasCanonical') ? (bool) self::value_or_null($item, 'hasCanonical') : null,
      'robotsIndex' => null !== self::value_or_null($item, 'robotsIndex') ? (string) self::value_or_null($item, 'robotsIndex') : null,
      'ogTitle' => null !== self::value_or_null($item, 'ogTitle') ? (string) self::value_or_null($item, 'ogTitle') : null,
      'ogDescription' => null !== self::value_or_null($item, 'ogDescription') ? (string) self::value_or_null($item, 'ogDescription') : null,
      'ogImage' => null !== self::value_or_null($item, 'ogImage') ? (string) self::value_or_null($item, 'ogImage') : null,
      'ogImageDisabled' => null !== self::value_or_null($item, 'ogImageDisabled') ? (bool) self::value_or_null($item, 'ogImageDisabled') : null,
      'contentWords' => is_numeric(self::value_or_null($item, 'contentWords')) ? (int) self::value_or_null($item, 'contentWords') : null,
      'h1Count' => is_numeric(self::value_or_null($item, 'h1Count')) ? (int) self::value_or_null($item, 'h1Count') : null,
      'h2Count' => is_numeric(self::value_or_null($item, 'h2Count')) ? (int) self::value_or_null($item, 'h2Count') : null,
      'faqCount' => is_numeric(self::value_or_null($item, 'faqCount')) ? (int) self::value_or_null($item, 'faqCount') : null,
      'internalLinks' => is_numeric(self::value_or_null($item, 'internalLinks')) ? (int) self::value_or_null($item, 'internalLinks') : null,
      'imageCount' => is_numeric(self::value_or_null($item, 'imageCount')) ? (int) self::value_or_null($item, 'imageCount') : null,
      'imagesMissingAlt' => is_numeric(self::value_or_null($item, 'imagesMissingAlt')) ? (int) self::value_or_null($item, 'imagesMissingAlt') : null,
      'modifiedGmt' => null !== self::value_or_null($item, 'modifiedGmt') ? (string) self::value_or_null($item, 'modifiedGmt') : null,
      'lastScanGmt' => null !== self::value_or_null($item, 'lastScanGmt') ? (string) self::value_or_null($item, 'lastScanGmt') : null,
    ];

    $computed = [
      'seoScore' => is_numeric(self::value_or_null($item, 'seoScore')) ? (int) self::value_or_null($item, 'seoScore') : null,
      'aiScore' => is_numeric(self::value_or_null($item, 'aiScore')) ? (int) self::value_or_null($item, 'aiScore') : null,
      'health' => null !== self::value_or_null($item, 'health') ? (string) self::value_or_null($item, 'health') : null,
      'metaSummary' => null !== self::value_or_null($item, 'metaSummary') ? (string) self::value_or_null($item, 'metaSummary') : null,
      'issueGroups' => is_array(self::value_or_null($item, 'issueGroups')) ? self::value_or_null($item, 'issueGroups') : null,
    ];

    $field_states = [];
    foreach (array_keys($raw) as $key) {
      $field_states[$key] = self::field_state($item, $key);
    }

    return self::with_envelope($raw, $computed, $field_states, $source_meta);
  }

  public static function normalize_post_seo(array $payload, array $source_meta = []) {
    $meta = isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : [];

    $raw = [
      'postId' => isset($payload['postId']) ? (int) $payload['postId'] : 0,
      'postType' => isset($payload['postType']) ? (string) $payload['postType'] : '',
      'seoTitle' => isset($meta['_ASNERISSEO_title']) ? (string) $meta['_ASNERISSEO_title'] : '',
      'seoDescription' => isset($meta['_ASNERISSEO_description']) ? (string) $meta['_ASNERISSEO_description'] : '',
      'canonical' => isset($meta['_ASNERISSEO_canonical']) ? (string) $meta['_ASNERISSEO_canonical'] : '',
      'robotsIndex' => isset($meta['_ASNERISSEO_robots_index']) ? (string) $meta['_ASNERISSEO_robots_index'] : '',
      'robotsFollow' => isset($meta['_ASNERISSEO_robots_follow']) ? (string) $meta['_ASNERISSEO_robots_follow'] : '',
      'ogTitle' => isset($meta['_ASNERISSEO_og_title']) ? (string) $meta['_ASNERISSEO_og_title'] : '',
      'ogDescription' => isset($meta['_ASNERISSEO_og_description']) ? (string) $meta['_ASNERISSEO_og_description'] : '',
      'ogImage' => isset($meta['_ASNERISSEO_og_image']) ? (string) $meta['_ASNERISSEO_og_image'] : '',
      'ogImageDisabled' => !empty($meta['_ASNERISSEO_og_image_disabled']),
      'schemaEnabled' => !empty($meta['_ASNERISSEO_schema_enabled']),
      'schemaType' => isset($meta['_ASNERISSEO_schema_type']) ? (string) $meta['_ASNERISSEO_schema_type'] : '',
    ];

    $field_states = [
      'postId' => self::field_state($payload, 'postId'),
      'postType' => self::field_state($payload, 'postType'),
      'seoTitle' => self::field_state($meta, '_ASNERISSEO_title'),
      'seoDescription' => self::field_state($meta, '_ASNERISSEO_description'),
      'canonical' => self::field_state($meta, '_ASNERISSEO_canonical'),
      'robotsIndex' => self::field_state($meta, '_ASNERISSEO_robots_index'),
      'robotsFollow' => self::field_state($meta, '_ASNERISSEO_robots_follow'),
      'ogTitle' => self::field_state($meta, '_ASNERISSEO_og_title'),
      'ogDescription' => self::field_state($meta, '_ASNERISSEO_og_description'),
      'ogImage' => self::field_state($meta, '_ASNERISSEO_og_image'),
      'ogImageDisabled' => self::field_state($meta, '_ASNERISSEO_og_image_disabled'),
      'schemaEnabled' => self::field_state($meta, '_ASNERISSEO_schema_enabled'),
      'schemaType' => self::field_state($meta, '_ASNERISSEO_schema_type'),
    ];

    return self::with_envelope($raw, [], $field_states, $source_meta);
  }

  public static function normalize_diagnostics_payload(array $payload, array $source_meta = []) {
    $normalized = self::normalize_overview_item($payload, $source_meta);
    $checks = [];

    if (isset($payload['checks']) && is_array($payload['checks'])) {
      foreach ($payload['checks'] as $check) {
        if (!is_array($check)) {
          continue;
        }

        $checks[] = [
          'label' => isset($check['label']) ? (string) $check['label'] : '',
          'status' => isset($check['status']) ? (string) $check['status'] : '',
          'result' => isset($check['result']) ? (string) $check['result'] : '',
          'details' => isset($check['details']) ? (string) $check['details'] : '',
          'category' => isset($check['category']) ? (string) $check['category'] : '',
        ];
      }
    }

    $normalized['raw']['checks'] = $checks;
    $normalized['raw']['source'] = isset($payload['source']) ? (string) $payload['source'] : '';

    $normalized['fieldStates']['checks'] = self::field_state($payload, 'checks');
    $normalized['fieldStates']['source'] = self::field_state($payload, 'source');

    return $normalized;
  }
}
