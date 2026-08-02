export const DISCOVERABILITY_DATA_SOURCES = {
	seoReview: 'latest-draft-or-published',
	diagnostics: 'published-content',
};

export const OVERVIEW_PRIMARY_FIELDS = [
	'Page Fetch',
	'Local Fallback',
	'HTTP Status',
	'Robots Meta',
	'SEO Title Length',
	'Meta Description Length',
	'H1 Presence',
	'Internal Links',
	'Content Depth (Word Count)',
	'Post Freshness',
	'Post Context',
];

export const normalizeOverviewFieldLabel = (value = '') => {
	const label = String(value || '').trim();
	if (!label) {
		return '';
	}

	return label;
};

export const isOverviewPrimaryField = (value = '') => {
	const normalized = normalizeOverviewFieldLabel(value);
	return OVERVIEW_PRIMARY_FIELDS.includes(normalized);
};

// Review-only catalog: mirrors proposed-fields.md for easy field audits.
// This does not alter runtime matching logic.
export const PROPOSED_FIELDS_REVIEW = {
	overview: OVERVIEW_PRIMARY_FIELDS,
	searchAppearance: [
		'SEO Title',
		'SEO Title Length',
		'Meta Description',
		'Meta Description Length',
		'Google Preview',
		'Open Graph Setup',
		'Open Graph Title',
		'Open Graph Description',
		'Open Graph Image',
		'Twitter Card',
	],
	indexability: [
		'Redirect Status',
		'Final Destination',
		'Canonical Exists',
		'Self Canonical',
		'Canonical Valid URL',
		'Canonical Target HTTP 200',
		'Robots Meta',
		'X-Robots-Tag',
		'HTTP Status',
		'Indexability',
		'Follow Directive',
	],
	contentQuality: [
		'SEO Title',
		'SEO Title Length',
		'Meta Description',
		'Meta Description Length',
		'H1 Presence',
		'Multiple H1',
		'Heading Structure',
		'Heading Hierarchy',
		'Content Depth (Word Count)',
		'Content Present',
		'Readability',
	],
	images: [
		'Images Found',
		'Image ALT Coverage',
		'Missing ALT',
		'Empty ALT',
		'Featured Image',
	],
	links: [
		'Internal Links',
		'External Links',
		'Nofollow Links',
	],
	structuredData: [
		'Schema Settings',
		'Structured Data Found',
		'Structured Data Present',
		'Schema Validation',
		'Primary Schema',
		'Primary Entity',
		'Organization Schema',
		'Article Schema',
		'FAQ Schema',
		'Breadcrumb Schema',
	],
	aiDiscoverability: [
		'Content Structure',
		'Author Information',
		'Machine Readability',
		'Primary Entity',
		'Topic Consistency',
		'Clear Page Purpose',
		'Summary Section',
		'Content Completeness',
		'Brand Mentions',
		'Product/Context Mentions',
		'Trust Signals',
		'Structured Content',
		'Table/List Detection',
		'Definition Content',
		'FAQ Ready',
		'FAQ Content',
		'FAQ Signals',
		'Language Declaration',
		'Internal References',
		'External References',
		'Published Date',
		'Last Updated Date',
		'Organization Information',
		'Media Context',
	],
};

export const TAB_FIELD_REGISTRY = {
	overview: OVERVIEW_PRIMARY_FIELDS,
	searchAppearance: PROPOSED_FIELDS_REVIEW.searchAppearance,
	indexability: PROPOSED_FIELDS_REVIEW.indexability,
	contentQuality: PROPOSED_FIELDS_REVIEW.contentQuality,
	images: PROPOSED_FIELDS_REVIEW.images,
	links: PROPOSED_FIELDS_REVIEW.links,
	structuredData: PROPOSED_FIELDS_REVIEW.structuredData,
	aiDiscoverability: PROPOSED_FIELDS_REVIEW.aiDiscoverability,
};

export const getCanonicalModelTabKeys = (options = {}) => {
	const includeOverview = options?.includeOverview !== false;
	const keys = Object.keys(TAB_FIELD_REGISTRY || {});
	return includeOverview ? keys : keys.filter((key) => key !== 'overview');
};

// Canonical scoring authority -> raw evidence mapping.
// This is the single JS source of truth for UI transparency and evidence lookups.
// UNIFIED DESIGN FIX: Updated to include completeness and metadata fields from response contract
export const CANONICAL_RAW_FIELD_MAP = {
	overview: {
		'Page Fetch': ['source', 'httpStatus'],
		'Local Fallback': ['source', 'sourceIsStale'],
		'Robots Meta': ['robotsIndex', 'robotsFollow'],
		'HTTP Status': ['httpStatus'],
		'SEO Title': ['metaTitle'],
		'SEO Title Length': ['metaTitleLength'],
		'Meta Description': ['metaDescription', 'seoDescription'],
		'Meta Description Length': ['descriptionLength', 'effectiveDescriptionLength'],
		Canonical: ['hasCanonical', 'canonical'],
		'H1 Heading': ['h1Count'],
		'H1 Presence': ['h1Count'],
		'Image ALT Coverage': ['imageCount', 'imagesMissingAlt'],
		'Internal Links': ['internalLinks'],
		'Content Depth (Word Count)': ['contentWords'],
		'Post Freshness': ['modifiedGmt'],
		'Post Context': ['postType', 'postStatus'],
		'Data Source': ['source'],
		'Data Freshness': ['sourceIsStale', 'lastScanGmt'],
	},
	searchAppearance: {
		'SEO Title': ['metaTitle', 'seoTitle'],
		'SEO Title Length': ['metaTitleLength', 'titleLength', 'effectiveTitleLength'],
		'Meta Description': ['metaDescription', 'seoDescription'],
		'Meta Description Length': ['descriptionLength', 'effectiveDescriptionLength'],
		'Google Preview': ['metaTitle', 'seoTitle', 'metaDescription', 'seoDescription', 'url'],
		Canonical: ['hasCanonical', 'canonical'],
		'Open Graph Setup': ['ogTitle', 'ogDescription', 'ogImage'],
		'Open Graph Title': ['ogTitle'],
		'Open Graph Description': ['ogDescription'],
		'Open Graph Image': ['ogImage'],
		'Twitter Card': ['ogTitle', 'ogDescription', 'ogImage'],
	},
	indexability: {
		'Redirect Status': ['httpStatus', 'url'],
		'Final Destination': ['url'],
		'HTTP Status': ['httpStatus'],
		'Robots Meta': ['robotsIndex', 'robotsFollow'],
		'Canonical Exists': ['hasCanonical', 'canonical', 'canonicalCount'],
		'Self Canonical': ['canonical', 'url'],
		'Canonical Valid URL': ['canonical'],
		'Canonical Target HTTP 200': ['canonical', 'httpStatus'],
		'X-Robots-Tag': ['xRobotsTag'],
		'Indexability': ['robotsIndex', 'xRobotsTag', 'httpStatus'],
		'Follow Directive': ['robotsFollow'],
	},
	contentQuality: {
		'SEO Title': ['metaTitle'],
		'SEO Title Length': ['metaTitleLength'],
		'Meta Description': ['metaDescription', 'seoDescription'],
		'Meta Description Length': ['descriptionLength', 'effectiveDescriptionLength'],
		'H1 Heading': ['h1Count'],
		'Content Quality': ['contentWords', 'h1Count', 'h2Count'],
		'H1 Presence': ['h1Count'],
		'Multiple H1': ['h1Count'],
		'Heading Structure': ['h1Count', 'h2Count'],
		'Heading Hierarchy': ['h1Count', 'h2Count'],
		'Content Depth (Word Count)': ['contentWords'],
		'Content Present': ['contentWords'],
		Readability: ['contentWords'],
	},
	images: {
		'Images Found': ['imageCount'],
		'Image ALT Coverage': ['imageCount', 'imagesMissingAlt'],
		'Missing ALT': ['imagesMissingAlt'],
		'Empty ALT': ['imagesEmptyAlt'],
		'Featured Image': ['featuredImage', 'imageCount'],
	},
	links: {
		'Internal Links': ['internalLinks'],
		'External Links': ['externalLinks'],
		'Nofollow Links': ['nofollowLinks'],
	},
	structuredData: {
		'Schema Settings': ['schemaEnabled', 'schemaType'],
		'Structured Data Found': ['schemaEnabled', 'schemaType', 'faqCount'],
		'Structured Data Present': ['schemaEnabled', 'schemaType', 'faqCount'],
		'Schema Validation': ['schemaEnabled', 'schemaType'],
		'Organization Schema': ['organizationSchema'],
		'Article Schema': ['schemaType'],
		'Primary Schema': ['schemaType'],
		'Primary Entity': ['schemaType'],
		'FAQ Schema': ['faqCount', 'schemaType'],
		'Breadcrumb Schema': ['breadcrumbSchema'],
	},
	aiDiscoverability: {
		'Content Structure': ['h1Present', 'headingHierarchyValid', 'sectionsCoverage', 'listDetected', 'tableDetected', 'summaryPattern'],
		'Author Information': ['author', 'authorName', 'authorInformation', 'organizationInformation'],
		'Machine Readability': ['languageDeclaration', 'avgSentenceLength'],
		'Primary Entity': ['schemaType', 'primaryEntity'],
		'Topic Consistency': ['keywordTopCount', 'keywordRatio'],
		'Clear Page Purpose': ['h1Present', 'headingHierarchyValid', 'sectionsCoverage', 'wordCount', 'avgSentenceLength'],
		'Summary Section': ['summaryPattern'],
		'Content Completeness': ['wordCount', 'internalLinks', 'imageCount'],
		'Brand Mentions': ['siteName', 'siteNameMentioned'],
		'Product/Context Mentions': ['productContextPattern'],
		'Trust Signals': ['trustPattern'],
		'Structured Content': ['listDetected', 'tableDetected'],
		'Table/List Detection': ['listDetected', 'tableDetected'],
		'Definition Content': ['definitionPattern'],
		'FAQ Ready': ['faqPattern', 'faqCount', 'schemaType'],
		'FAQ Content': ['faqPattern'],
		'FAQ Signals': ['faqPattern'],
		'Language Declaration': ['languageDeclaration'],
		'Internal References': ['internalLinks'],
		'External References': ['externalLinks'],
		'Published Date': ['publishedGmt', 'modifiedGmt'],
		'Last Updated Date': ['modifiedGmt'],
		'Organization Information': ['organizationSchema', 'schemaType'],
		'Media Context': ['imageCount', 'imagesMissingAlt', 'imagesEmptyAlt'],
	},
	// UNIFIED DESIGN FIX: Completeness tracking metadata available in all response structures
	completeness: {
		'Data Capture Quality': ['completeness.captureQuality'],
		'Captured Fields': ['completeness.capturedFields'],
		'Missing Fields': ['completeness.missingFields'],
		'Check Completeness': ['isDataComplete'],
		'Check Missing Fields': ['missingFields'],
	},
};

const normalizeModelLabel = (value = '') =>
    String(value || '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();

const tokenizeModelLabel = (value = '') =>
    normalizeModelLabel(value)
        .split(' ')
        .filter(Boolean);

const MODEL_MATCH_STOPWORDS = new Set([
    'url',
    'meta',
    'tag',
    'exists',
    'exist',
    'present',
    'found',
    'check',
    'checks',
]);

const hasLengthTokenMismatch = (aTokens = [], bTokens = []) => {
    const aHasLength = aTokens.includes('length');
    const bHasLength = bTokens.includes('length');
    return aHasLength !== bHasLength;
};

export const getCanonicalFieldsByTab = (tabKey = '') => {
    const key = String(tabKey || '').trim();
    const fields = TAB_FIELD_REGISTRY?.[key];
    return Array.isArray(fields) ? fields.slice() : [];
};

export const getExpectedCategoryForCanonicalTab = (tabKey = '') =>
    String(TAB_CATEGORY_EXPECTATION?.[String(tabKey || '').trim()] || '');

export const doesCheckLabelMatchCanonicalField = (tabKey = '', canonicalField = '', checkLabel = '') => {
    const canonical = String(canonicalField || '').trim();
    const check = String(checkLabel || '').trim();
    if (!canonical || !check) {
        return false;
    }

    const expectedCategory = getExpectedCategoryForCanonicalTab(tabKey);
    if (expectedCategory) {
        const routed = categorizeDiscoverabilityCheck(check);
        if (routed !== expectedCategory) {
            return false;
        }
    }

    const canonicalNorm = normalizeModelLabel(canonical);
    const checkNorm = normalizeModelLabel(check);

    if (!canonicalNorm || !checkNorm) {
        return false;
    }

    if (canonicalNorm === checkNorm) {
        return true;
    }

    const canonicalTokens = tokenizeModelLabel(canonicalNorm);
    const checkTokens = tokenizeModelLabel(checkNorm);

    if (canonicalTokens.length < 1 || checkTokens.length < 1) {
        return false;
    }

    // Avoid false matches like "SEO Title" vs "SEO Title Length".
    if (hasLengthTokenMismatch(canonicalTokens, checkTokens)) {
        return false;
    }

    const canonicalTokenSet = new Set(canonicalTokens);
    const checkTokenSet = new Set(checkTokens);

    const allCanonicalInCheck = canonicalTokens.every((token) => checkTokenSet.has(token));
    if (!allCanonicalInCheck) {
        return false;
    }

    // Allow only minor suffix tokens in check labels.
    const extraTokens = checkTokens.filter((token) => !canonicalTokenSet.has(token));
    if (extraTokens.length < 1) {
        return true;
    }

    return extraTokens.every((token) => MODEL_MATCH_STOPWORDS.has(token));
};

const normalizeCanonicalLabel = (value) => String(value || '').trim().toLowerCase();

const buildCanonicalLookup = (mapping) => {
	const lookup = {};
	Object.entries(mapping || {}).forEach(([tabKey, tabMap]) => {
		const normalizedTab = String(tabKey || '').trim();
		lookup[normalizedTab] = {};
		Object.entries(tabMap || {}).forEach(([canonicalField, rawFields]) => {
			lookup[normalizedTab][normalizeCanonicalLabel(canonicalField)] = Array.isArray(rawFields) ? rawFields : [];
		});
	});

	return lookup;
};

const CANONICAL_RAW_FIELD_LOOKUP = buildCanonicalLookup(CANONICAL_RAW_FIELD_MAP);

export const getCanonicalRawFields = (tabKey = '', canonicalField = '') => {
	const normalizedTab = String(tabKey || '').trim();
	if (!normalizedTab) {
		return [];
	}

	const byTab = CANONICAL_RAW_FIELD_LOOKUP[normalizedTab];
	if (!byTab) {
		return [];
	}

	const normalizedCanonical = normalizeCanonicalLabel(canonicalField);
	if (!normalizedCanonical) {
		return [];
	}

	return Array.isArray(byTab[normalizedCanonical]) ? byTab[normalizedCanonical] : [];
};

export const getCanonicalRawFieldsForTabs = (tabKeys = [], canonicalField = '') => {
	const keys = Array.isArray(tabKeys) ? tabKeys : [];
	const merged = keys.flatMap((tabKey) => getCanonicalRawFields(tabKey, canonicalField));
	return Array.from(new Set(merged));
};

export const getCanonicalRawFieldsAcrossTabs = (canonicalField = '', preferredTabKeys = []) => {
	const preferred = Array.isArray(preferredTabKeys) ? preferredTabKeys : [];
	const allTabs = Object.keys(CANONICAL_RAW_FIELD_LOOKUP || {});
	const lookupOrder = Array.from(new Set([ ...preferred, ...allTabs ].filter(Boolean)));
	return getCanonicalRawFieldsForTabs(lookupOrder, canonicalField);
};

export const hasCanonicalRawMapping = (tabKey = '', canonicalField = '') =>
	getCanonicalRawFields(tabKey, canonicalField).length > 0;

export const getCanonicalMappingGaps = (proposedFieldsByTab = {}) => {
	const gaps = [];

	Object.entries(proposedFieldsByTab || {}).forEach(([tabKey, fields]) => {
		const canonicalFields = Array.isArray(fields) ? fields : [];
		canonicalFields.forEach((fieldLabel) => {
			if (!hasCanonicalRawMapping(tabKey, fieldLabel)) {
				gaps.push({
					tabKey,
					canonicalField: String(fieldLabel || '').trim(),
				});
			}
		});
	});

	return gaps;
};

export const DISCOVERABILITY_EXPECTED_CHECKS = {
	searchAppearance: [
		'google preview',
		'open graph title',
		'open graph description',
		'open graph image',
		'twitter card',
		'breadcrumb schema',
	],
	structuredData: [
		'Structured Data Present',
		'Schema Validation',
		'Organization Schema',
		'Primary Schema',
		'FAQ Schema',
		'Breadcrumb Schema',
	],
	links: [
		'Internal Links',
		'External Links',
		'Nofollow Links',
	],
	images: [
		'Images Found',
		'Missing ALT',
		'Empty ALT',
		'Featured Image',
	],
	ai: [
		'Content Structure',
		'Author Information',
		'Machine Readability',
		'Internal References',
		'Primary Entity',
	],
};

export const DISCOVERABILITY_DETAIL_PATTERNS = {
	overview: /robots meta|http status|seo title|seo title length|meta description|meta description length|h1 heading|internal links/i,
	indexability: /http status|robots meta|canonical exists|self canonical|canonical valid url|x-robots-tag/i,
	content: /seo title|meta description|h1 heading|content quality|h1 presence|multiple h1|heading structure|heading hierarchy|content depth|content present|readability/i,
	searchAppearance: /seo title|seo title length|meta description|meta description length|google preview|open graph title|open graph description|open graph image|twitter card|\bcanonical\b(?!\s+(exists|valid|validation|target|self))/i,
	structuredData: /structured data present|schema validation|json-ld valid|organization schema|primary schema|faq schema|breadcrumb schema/i,
	links: /internal links|external links|nofollow links/i,
	images: /images found|image alt coverage|missing alt|empty alt|featured image/i,
	ai: /content structure|author information|machine readability|internal references|primary entity|topic consistency|clear page purpose|summary section|content completeness|brand mentions|product\/context mentions|trust signals|table\/list detection|definition content|faq signals|language declaration/i,
};

const matchesAny = (label, patterns) => patterns.some((pattern) => pattern.test(label));

const CHECK_PATTERNS = {
	search: [
		/seo title|seo title length|meta description|meta description length|google preview|\bcanonical\b(?!\s+(exists|valid|validation|target|self))/i,
	],
	social: [
		/open graph title|open graph description|open graph image|twitter card/i,
	],
	advanced: [
		/http status|robots meta|x-robots-tag|canonical exists|self canonical|canonical valid url|canonical validation|canonical target http 200|redirect status|final destination|indexability|follow directive|robots directives/i,
	],
	quality: [
		/seo title|meta description|h1 heading|content quality|h1 presence|multiple h1|heading structure|heading hierarchy|content depth|content present|readability/i,
	],
	links: [
		/internal links|external links|nofollow links/i,
	],
	schema: [
		/structured data present|schema validation|json-ld valid|organization schema|primary schema|faq schema|breadcrumb schema/i,
	],
	images: [
		/images found|image alt coverage|missing alt|empty alt|featured image|images? & alt/i,
	],
	ai: [
		/content structure|author information|language declaration|machine readability/i,
		/internal references|table\/list detection/i,
		/definition content|topic consistency|clear page purpose|summary section/i,
		/primary entity|faq signals/i,
		/brand mentions|product\/context mentions/i,
		/trust signals/i,
	],
};

export const categorizeDiscoverabilityCheck = (checkLabel = '') => {
	const label = String(checkLabel || '').toLowerCase();

	if (matchesAny(label, CHECK_PATTERNS.advanced)) {
		return 'advanced';
	}
	if (matchesAny(label, CHECK_PATTERNS.search)) {
		return 'search';
	}
	if (matchesAny(label, CHECK_PATTERNS.social)) {
		return 'search';
	}
	if (matchesAny(label, CHECK_PATTERNS.links)) {
		return 'links';
	}
	if (matchesAny(label, CHECK_PATTERNS.schema)) {
		return 'schema';
	}
	if (matchesAny(label, CHECK_PATTERNS.images)) {
		return 'images';
	}
	if (matchesAny(label, CHECK_PATTERNS.ai)) {
		return 'ai';
	}
	if (matchesAny(label, CHECK_PATTERNS.quality)) {
		return 'quality';
	}

	return 'overview';
};

export const filterDiscoverabilityChecksByTab = (checks = [], tabId = 'overview') => {
	if (!Array.isArray(checks) || checks.length < 1) {
		return [];
	}

	if (tabId === 'overview') {
		return checks;
	}

	if (tabId === 'social') {
		return [];
	}

	const normalizedTarget = tabId === 'schema'
		? 'schema'
		: tabId === 'links'
			? 'links'
			: tabId === 'quality'
				? 'quality'
				: tabId === 'advanced'
					? 'advanced'
					: tabId === 'search'
						? 'search'
						: tabId === 'ai'
							? 'ai'
							: tabId;

	return checks.filter((check) => categorizeDiscoverabilityCheck(check?.label) === normalizedTarget);
};

const TAB_CATEGORY_EXPECTATION = {
	overview: '',
	searchAppearance: 'search',
	indexability: 'advanced',
	contentQuality: 'quality',
	images: 'images',
	links: 'links',
	structuredData: 'schema',
	aiDiscoverability: 'ai',
};

export const getDiscoverabilityModelInvariantViolations = () => {
	const violations = [];

	Object.entries(TAB_FIELD_REGISTRY || {}).forEach(([tabKey, canonicalFields]) => {
		const tabMap = CANONICAL_RAW_FIELD_MAP?.[tabKey];
		if (!tabMap || typeof tabMap !== 'object') {
			violations.push({
				type: 'missing-tab-map',
				tabKey,
				canonicalField: '',
			});
			return;
		}

		(canonicalFields || []).forEach((fieldLabel) => {
			if (!hasCanonicalRawMapping(tabKey, fieldLabel)) {
				violations.push({
					type: 'missing-registry-mapping',
					tabKey,
					canonicalField: String(fieldLabel || '').trim(),
				});
			}
		});
	});

	Object.entries(CANONICAL_RAW_FIELD_MAP || {}).forEach(([tabKey, tabMap]) => {
		const seenCanonical = new Set();
		Object.entries(tabMap || {}).forEach(([canonicalField, rawFields]) => {
			const canonicalLabel = String(canonicalField || '').trim();
			const normalizedCanonical = canonicalLabel.toLowerCase();

			if (!canonicalLabel) {
				violations.push({
					type: 'empty-canonical-label',
					tabKey,
					canonicalField: canonicalLabel,
				});
			}

			if (seenCanonical.has(normalizedCanonical)) {
				violations.push({
					type: 'duplicate-canonical-label',
					tabKey,
					canonicalField: canonicalLabel,
				});
			}
			seenCanonical.add(normalizedCanonical);

			if (!Array.isArray(rawFields) || rawFields.length < 1) {
				violations.push({
					type: 'missing-raw-evidence-fields',
					tabKey,
					canonicalField: canonicalLabel,
				});
			}

			const expectedCategory = TAB_CATEGORY_EXPECTATION[tabKey] || '';
			if (expectedCategory && expectedCategory !== 'overview') {
				const routedCategory = categorizeDiscoverabilityCheck(canonicalLabel);
				if (routedCategory !== expectedCategory) {
					violations.push({
						type: 'category-routing-mismatch',
						tabKey,
						canonicalField: canonicalLabel,
						expectedCategory,
						routedCategory,
					});
				}
			}
		});
	});

	return violations;
};

// UNIFIED DESIGN FIX #4: Helper functions for working with completeness metadata from unified response
export const getResponseCompletenessStatus = (response = {}) => {
	const completeness = response?.completeness;
	if (!completeness) {
		return {
			isComplete: true,
			captureQuality: 'complete',
			missingFields: [],
			capturedFields: [],
		};
	}

	return {
		isComplete: !completeness.missingFields || completeness.missingFields.length === 0,
		captureQuality: completeness.captureQuality || 'complete',
		missingFields: completeness.missingFields || [],
		capturedFields: completeness.capturedFields || [],
	};
};

export const getCheckCompletenessStatus = (check = {}) => {
	return {
		isDataComplete: check?.isDataComplete !== false,
		missingFields: check?.missingFields || [],
	};
};

export const getResponseSourceLabel = (response = {}) => {
	const source = response?.source || 'live-scan';
	const isStale = response?.sourceIsStale === true;
	
	const sourceMap = {
		'live-scan': 'Live Scan',
		'live-scan-no-store': 'Live Scan (Draft)',
		'live-scan-non-priority': 'Live Scan',
		'fallback-local': 'Local Data',
		'cron-scan': 'Background Scan',
		'snapshot-skip': 'Cached Data',
	};
	
	const label = sourceMap[source] || 'Unknown Source';
	return isStale ? `${label} (stale)` : label;
};

export const shouldDegradeCheckStatus = (check = {}) => {
	return check?.isDataComplete === false && Array.isArray(check?.missingFields) && check.missingFields.length > 0;
};

export const degradeCheckStatus = (originalStatus, check = {}) => {
	if (!shouldDegradeCheckStatus(check)) {
		return originalStatus;
	}

	// Degrade status when data is incomplete
	if (originalStatus === 'pass') {
		return 'warning';
	}
	if (originalStatus === 'warning') {
		return 'fail';
	}

	return originalStatus;
};
