<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASNERISSEO_Page_Diagnostics_Response_Contract {
	const SOURCE_FLOW         = 'page_diagnostics';
	const SOURCE_ENGINE       = 'weightage_policy_v4_1';
	const DEFAULT_SOURCE_MODE = 'live_scan';

	public static function build_payload( array $payload, $source_mode = self::DEFAULT_SOURCE_MODE ) {
		$normalized               = self::apply_defaults( $payload );
		$normalized['sourceMode'] = sanitize_key( (string) $source_mode );

		if ( class_exists( 'ASNERISSEO_Data_Interface_Normalizer' ) ) {
			$normalized['unifiedData'] = ASNERISSEO_Data_Interface_Normalizer::normalize_diagnostics_payload(
				$normalized,
				array(
					'sourceFlow'   => self::SOURCE_FLOW,
					'sourceEngine' => self::SOURCE_ENGINE,
					'sourceMode'   => $normalized['sourceMode'],
				)
			);
		}

		return $normalized;
	}

	public static function apply_defaults( array $payload ) {
		$defaults                           = self::get_payload_defaults();
		$normalized                         = array_merge( $defaults, $payload );
		$normalized['checks']               = self::normalize_checks( isset( $payload['checks'] ) && is_array( $payload['checks'] ) ? $payload['checks'] : array() );
		$normalized['issueGroups']          = isset( $payload['issueGroups'] ) && is_array( $payload['issueGroups'] ) ? array_values( $payload['issueGroups'] ) : array();
		$normalized['overviewIssueRecords'] = isset( $payload['overviewIssueRecords'] ) && is_array( $payload['overviewIssueRecords'] ) ? array_values( $payload['overviewIssueRecords'] ) : array();
		$normalized['overviewScoreRecords'] = isset( $payload['overviewScoreRecords'] ) && is_array( $payload['overviewScoreRecords'] ) ? array_values( $payload['overviewScoreRecords'] ) : array();
		$normalized['aiIssueRecords']       = isset( $payload['aiIssueRecords'] ) && is_array( $payload['aiIssueRecords'] ) ? array_values( $payload['aiIssueRecords'] ) : array();
		$normalized['tabIssueRecords']      = isset( $payload['tabIssueRecords'] ) && is_array( $payload['tabIssueRecords'] ) ? $payload['tabIssueRecords'] : array();
		$normalized['aiCanonicalSignals']   = isset( $payload['aiCanonicalSignals'] ) && is_array( $payload['aiCanonicalSignals'] ) ? $payload['aiCanonicalSignals'] : array();
		$normalized['tabModels']            = isset( $payload['tabModels'] ) && is_array( $payload['tabModels'] ) ? $payload['tabModels'] : array();
		$normalized['performance']          = isset( $payload['performance'] ) && is_array( $payload['performance'] ) ? $payload['performance'] : null;
		$normalized['unifiedData']          = isset( $payload['unifiedData'] ) && is_array( $payload['unifiedData'] ) ? $payload['unifiedData'] : array();

		return $normalized;
	}

	public static function normalize_checks( array $checks ) {
		$normalized = array();

		foreach ( $checks as $check ) {
			if ( ! is_array( $check ) ) {
				continue;
			}

			$normalized[] = array_merge(
				self::get_check_defaults(),
				$check,
				array(
					'rawEvidence'       => isset( $check['rawEvidence'] ) && is_array( $check['rawEvidence'] ) ? $check['rawEvidence'] : array(),
					'rawEvidenceFields' => isset( $check['rawEvidenceFields'] ) && is_array( $check['rawEvidenceFields'] ) ? array_values( $check['rawEvidenceFields'] ) : array(),
				)
			);
		}

		return $normalized;
	}

	public static function validate_payload( $payload, $context = 'payload' ) {
		if ( ! is_array( $payload ) ) {
			return self::build_contract_error( 'asnerisseo_page_diagnostics_contract_invalid', $context, 'Payload must be an array.' );
		}

		foreach ( array_keys( self::get_payload_defaults() ) as $required_key ) {
			if ( ! array_key_exists( $required_key, $payload ) ) {
				return self::build_contract_error(
					'asnerisseo_page_diagnostics_contract_missing_key',
					$context,
					sprintf( 'Missing required top-level key: %s', (string) $required_key )
				);
			}
		}

		if ( ! isset( $payload['checks'] ) || ! is_array( $payload['checks'] ) ) {
			return self::build_contract_error( 'asnerisseo_page_diagnostics_contract_invalid_checks', $context, 'Checks must be an array.' );
		}

		foreach ( $payload['checks'] as $index => $check ) {
			if ( ! is_array( $check ) ) {
				return self::build_contract_error(
					'asnerisseo_page_diagnostics_contract_invalid_check',
					$context,
					sprintf( 'Check at index %d must be an array.', (int) $index )
				);
			}

			foreach ( array_keys( self::get_check_defaults() ) as $required_key ) {
				if ( ! array_key_exists( $required_key, $check ) ) {
					return self::build_contract_error(
						'asnerisseo_page_diagnostics_contract_missing_check_key',
						$context,
						sprintf( 'Check at index %d missing key: %s', (int) $index, (string) $required_key )
					);
				}
			}
		}

		return true;
	}

	public static function get_payload_defaults() {
		return array(
			'postId'               => 0,
			'title'                => '',
			'postType'             => '',
			'postStatus'           => '',
			'author'               => '',
			'isDraftQualityOnly'   => false,
			'url'                  => '',
			'publishedGmt'         => '',
			'modifiedGmt'          => '',
			'lastScanGmt'          => '',
			'source'               => '',
			'sourceIsStale'        => false,
			'sourceMode'           => '',
			'scoreEngine'          => '',
			'seoScore'             => 0,
			'aiScore'              => 0,
			'health'               => 'warning',
			'issueGroups'          => array(),
			'checks'               => array(),
			'overviewIssueRecords' => array(),
			'overviewScoreRecords' => array(),
			'aiIssueRecords'       => array(),
			'tabIssueRecords'      => array(),
			'tabModels'            => array(),
			'aiCanonicalSignals'   => array(),
			'overviewRunId'        => '',
			'seoScoreMessage'      => '',
			'isPriority'           => false,
			'performance'          => null,
			'metaTitle'            => '',
			'metaTitleLength'      => 0,
			'seoTitle'             => '',
			'metaSummary'          => '',
			'metaDescription'      => '',
			'seoDescription'       => '',
			'excerpt'              => '',
			'ogTitle'              => '',
			'ogDescription'        => '',
			'ogImage'              => '',
			'ogImageDisabled'      => false,
			'hasCustomTitle'       => false,
			'hasCustomDescription' => false,
			'effectiveTitle'       => '',
			'titleLength'          => 0,
			'effectiveDescription' => '',
			'descriptionLength'    => 0,
			'canonical'            => '',
			'hasCanonical'         => false,
			'canonicalCount'       => 0,
			'robotsIndex'          => 'index',
			'robotsFollow'         => 'follow',
			'xRobotsTag'           => '',
			'httpStatus'           => 0,
			'contentWords'         => 0,
			'h1Count'              => 0,
			'h2Count'              => 0,
			'faqCount'             => 0,
			'schemaEnabled'        => false,
			'schemaType'           => '',
			'organizationSchema'   => false,
			'breadcrumbSchema'     => false,
			'internalLinks'        => 0,
			'externalLinks'        => 0,
			'nofollowLinks'        => 0,
			'imageCount'           => 0,
			'imagesMissingAlt'     => 0,
			'imagesEmptyAlt'       => 0,
			'featuredImage'        => false,
			'languageDeclaration'  => '',
			'unifiedData'          => array(),
			'completeness'         => array(
				'capturedFields' => array(),
				'missingFields'  => array(),
				'captureQuality' => 'complete',
			),
		);
	}

	public static function get_check_defaults() {
		return array(
			'label'             => '',
			'category'          => '',
			'status'            => 'warning',
			'result'            => '',
			'details'           => '',
			'canonicalField'    => '',
			'rawEvidence'       => array(),
			'rawEvidenceFields' => array(),
			'isDataComplete'    => true,
			'missingFields'     => array(),
		);
	}

	private static function build_contract_error( $code, $context, $details = '' ) {
		return new WP_Error(
			sanitize_key( (string) $code ),
			/* translators: %s: page diagnostics response contract validation context. */
			sprintf( __( 'Page diagnostics response contract validation failed for %s.', 'asneris-seo-toolkit' ), (string) $context ),
			array(
				'status'  => 500,
				'context' => (string) $context,
				'details' => (string) $details,
			)
		);
	}
}
