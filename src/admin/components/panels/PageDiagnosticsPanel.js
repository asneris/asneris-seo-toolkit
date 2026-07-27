import { Fragment, useCallback, useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PanelScaffold from '../common/PanelScaffold';
import fetchJson from '../common/fetchJson';
import StatusTable from '../common/StatusTable';
import InlineHelpDetails from '../common/InlineHelpDetails';
import PerformanceTrackerCard from '../common/PerformanceTrackerCard';
import {
	categorizeDiscoverabilityCheck,
	DISCOVERABILITY_DETAIL_PATTERNS,
	DISCOVERABILITY_EXPECTED_CHECKS,
	getDiscoverabilityModelInvariantViolations,
	getCanonicalMappingGaps,
	getCanonicalRawFields,
	normalizeOverviewFieldLabel as normalizeOverviewPrimaryFieldLabel,
	OVERVIEW_PRIMARY_FIELDS,
	PROPOSED_FIELDS_REVIEW,
	TAB_FIELD_REGISTRY,
	getCanonicalModelTabKeys,
	getCanonicalFieldsByTab,
	doesCheckLabelMatchCanonicalField,
} from '../../../app/discoverabilityDataModel';
import { assertUnifiedCollection, assertUnifiedData, getUnifiedComputed, mergeUnifiedItem } from '../../../app/unifiedDataModel';
import DiscoverabilityTopIssues from '../../../app/components/DiscoverabilityTopIssues';



const SHOW_CANONICAL_DEBUG_BUTTON = false; // Set to true to show the canonical debug button, false to hide it
const formatModifiedLabel = (modifiedGmt) => {
	if (!modifiedGmt) {
		return '-';
	}

	const date = new Date(modifiedGmt);
	if (Number.isNaN(date.getTime())) {
		return '-';
	}

	const now = new Date();
	const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
	const target = new Date(date.getFullYear(), date.getMonth(), date.getDate());
	const diffDays = Math.floor((today.getTime() - target.getTime()) / (1000 * 60 * 60 * 24));
	

	if (diffDays === 0) {
		return __('Today', 'asneris-seo-toolkit');
	}

	if (diffDays === 1) {
		return __('Yesterday', 'asneris-seo-toolkit');
	}

	if (diffDays > 1 && diffDays <= 30) {
		return `${ diffDays } ${ __('days ago', 'asneris-seo-toolkit') }`;
	}

	return date.toLocaleDateString();
};

const formatLastScanLabel = (lastScanGmt) => {
	if (!lastScanGmt) {
		return '-';
	}

	return formatModifiedLabel(lastScanGmt);
};

const formatDateTimeLabel = (value) => {
	if (!value) {
		return '-';
	}

	const date = new Date(value);
	if (Number.isNaN(date.getTime())) {
		return '-';
	}

	return `${ formatModifiedLabel(value) } ${ date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }`;
};

const formatDurationMs = (value) => `${ Number(value || 0).toLocaleString() } ms`;

const formatBytesToMb = (value) => `${ (Number(value || 0) / (1024 * 1024)).toFixed(2) } MB`;

const formatPerformanceStatus = (status) => {
	const normalized = String(status || '').trim().toLowerCase();
	if (!normalized) {
		return '-';
	}

	return `${ normalized.charAt(0).toUpperCase() }${ normalized.slice(1) }`;
};

const toStatusChipClass = (status) => {
	if (status === 'warning') {
		return 'is-warning';
	}

	if (status === 'good' || status === 'excellent') {
		return 'is-success';
	}

	return 'is-neutral';
};

const clampScore = (score) => {
	if (!Number.isFinite(score)) {
		return null;
	}

	return Math.max(0, Math.min(100, Math.round(score)));
};

const SOFT_FAILURE_SCORE_MESSAGE = __('Something went wrong while reading score data. Please rerun diagnostics and report this issue if it continues.', 'asneris-seo-toolkit');

const hasDirectSeoScore = (item) => {
	if (item?.isDraftQualityOnly) {
		return false;
	}

	return clampScore(Number(item?.seoScore)) !== null;
};

const hasDirectAiScore = (item) => clampScore(Number(item?.aiScore)) !== null;

const deriveSeoScore = (item) => {
	if (item?.isDraftQualityOnly) {
		return null;
	}

	const direct = clampScore(Number(item?.seoScore));
	if (direct !== null) {
		return direct;
	}

	return 0;
};

const deriveAiScore = (item) => {
	const direct = clampScore(Number(item?.aiScore));
	if (direct !== null) {
		return direct;
	}

	return 0;
};

const healthMeta = {
	good: { tone: 'success', label: __('Healthy', 'asneris-seo-toolkit') },
	warning: { tone: 'warning', label: __('Warning', 'asneris-seo-toolkit') },
	poor: { tone: 'fail', label: __('Critical', 'asneris-seo-toolkit') },
};

const deriveHealth = (item) => {
	const status = (item?.health || '').toLowerCase();
	if (healthMeta[status]) {
		return healthMeta[status];
	}

	const score = deriveSeoScore(item);
	if (score === null) {
		return { tone: 'neutral', label: '-' };
	}

	if (score >= 85) {
		return healthMeta.good;
	}

	if (score >= 65) {
		return healthMeta.warning;
	}

	return healthMeta.poor;
};

const formatIndexLabel = (item) => {
	const robotsIndex = (item?.robotsIndex || '').toLowerCase();
	return robotsIndex === 'noindex' ? __('Noindex', 'asneris-seo-toolkit') : __('Index', 'asneris-seo-toolkit');
};

const formatMetaSummary = (item) => {
	if (item?.metaSummary) {
		return item.metaSummary;
	}

	return item?.hasCustomDescription
		? __('Complete', 'asneris-seo-toolkit')
		: __('Missing', 'asneris-seo-toolkit');
};

const formatContentSummary = (item) => {
	const words = Number(item?.contentWords);
	if (Number.isFinite(words) && words >= 0) {
		return `${ words.toLocaleString() } ${ __('words', 'asneris-seo-toolkit') }`;
	}

	return '-';
};

const formatImageSummary = (item) => {
	const imageCount = Number(item?.imageCount);
	const imagesMissingAlt = Number(item?.imagesMissingAlt);

	if (!Number.isFinite(imageCount) || imageCount < 1) {
		return __('No images', 'asneris-seo-toolkit');
	}

	if (!Number.isFinite(imagesMissingAlt) || imagesMissingAlt <= 0) {
		return __('All ALT present', 'asneris-seo-toolkit');
	}

	return `${ imagesMissingAlt } ${ __('ALT missing', 'asneris-seo-toolkit') }`;
};

const formatLinksSummary = (item) => {
	const links = Number(item?.internalLinks);
	if (!Number.isFinite(links) || links < 0) {
		return '-';
	}

	return links.toLocaleString();
};

const formatStatusLabel = (postStatus) => {
	const status = (postStatus || '').toLowerCase();
	if (status === 'publish') {
		return __('Published', 'asneris-seo-toolkit');
	}
	if (status === 'draft') {
		return __('Draft', 'asneris-seo-toolkit');
	}

	if (!status) {
		return '-';
	}

	return `${ status.charAt(0).toUpperCase() }${ status.slice(1) }`;
};

const TAXONOMY_SECTIONS = [
	{ key: 'overview', label: __('Overview', 'asneris-seo-toolkit') },
	{ key: 'search', label: __('Search Appearance', 'asneris-seo-toolkit') },
	{ key: 'quality', label: __('Content Quality', 'asneris-seo-toolkit') },
	{ key: 'links', label: __('Internal Links', 'asneris-seo-toolkit') },
	{ key: 'schema', label: __('Schema', 'asneris-seo-toolkit') },
	{ key: 'social', label: __('Social Preview', 'asneris-seo-toolkit') },
	{ key: 'advanced', label: __('Advanced Settings', 'asneris-seo-toolkit') },
	{ key: 'ai', label: __('AI Discoverability', 'asneris-seo-toolkit') },
];

const TAXONOMY_SECTION_KEYS = new Set(TAXONOMY_SECTIONS.map((section) => section.key));

const normalizeCheckCategory = (category) => {
	if (category === 'images') {
		return 'quality';
	}

	if (category === 'overview' || TAXONOMY_SECTION_KEYS.has(category)) {
		return category;
	}

	return 'overview';
};

const toCheckCategory = (label) => {
	return normalizeCheckCategory(categorizeDiscoverabilityCheck(label));
};

const mapCheckStatus = (status) => {
	const normalized = String(status || '').toLowerCase();
	if (normalized === 'pass') {
		return 'pass';
	}
	if (normalized === 'warning' || normalized === 'warn') {
		return 'warning';
	}
	if (
		normalized === 'not scanned'
		|| normalized === 'not_scanned'
		|| normalized === 'not checked'
		|| normalized === 'not_checked'
		|| normalized === 'not available'
		|| normalized === 'na'
		|| normalized === 'n/a'
		|| normalized === 'unknown'
	) {
		return 'not_scanned';
	}
	if (normalized === 'fail' || normalized === 'error' || normalized === 'critical') {
		return 'fail';
	}

	return 'fail';
};

const getTabIssueStatusLookup = (item, canonicalMapKey) => {
	const tabIssueRecords = item?.tabIssueRecords && typeof item.tabIssueRecords === 'object'
		? item.tabIssueRecords
		: null;

	if (!tabIssueRecords || !Array.isArray(tabIssueRecords[canonicalMapKey])) {
		return new Map();
	}

	const records = Array.isArray(tabIssueRecords[canonicalMapKey]) ? tabIssueRecords[canonicalMapKey] : [];
	const preferredOverviewRunId = canonicalMapKey === 'overview'
		? String(item?.overviewRunId || '').trim()
		: '';
	const matchingOverviewRunRecords = preferredOverviewRunId
		? records.filter((record) => String(record?.run_id || '').trim() === preferredOverviewRunId)
		: [];
	const sourceRecords = matchingOverviewRunRecords.length > 0 ? matchingOverviewRunRecords : records;

	const toRank = (status) => {
		if (status === 'fail') {
			return 3;
		}
		if (status === 'warning') {
			return 2;
		}
		if (status === 'pass') {
			return 1;
		}

		return 0;
	};

	return sourceRecords.reduce((acc, record) => {
		const field = String(record?.canonical_field || '').trim().toLowerCase();
		if (!field) {
			return acc;
		}

		const status = mapCheckStatus(record?.canonical_status || 'not scanned');
		if (status !== 'pass' && status !== 'warning' && status !== 'fail') {
			return acc;
		}

		const current = acc.get(field);
		if (!current || toRank(status) > toRank(current)) {
			acc.set(field, status);
		}

		return acc;
	}, new Map());
};

const formatCheckStatusLabel = (status) => {
	const normalized = mapCheckStatus(status);
	if (normalized === 'pass') {
		return __('Pass', 'asneris-seo-toolkit');
	}
	if (normalized === 'warning') {
		return __('Warning', 'asneris-seo-toolkit');
	}
	if (normalized === 'not_scanned') {
		return __('Not scanned', 'asneris-seo-toolkit');
	}

	return __('Fail', 'asneris-seo-toolkit');
};

const formatIssuesSummary = (item) => {
	const issues = [];
	const issueGroups = item?.issueGroups || {};

	if (typeof issueGroups.meta === 'boolean') {
		if (issueGroups.meta) {
			issues.push(__('Search Appearance', 'asneris-seo-toolkit'));
		}
		if (issueGroups.indexability) {
			issues.push(__('Advanced Settings', 'asneris-seo-toolkit'));
		}
		if (issueGroups.content) {
			issues.push(__('Content Quality', 'asneris-seo-toolkit'));
		}
		if (issueGroups.ai) {
			issues.push(__('AI Discoverability', 'asneris-seo-toolkit'));
		}

		return issues.length > 0 ? issues.join(', ') : '—';
	}

	const meta = (item?.metaSummary || '').toLowerCase();
	const titleLength = Number(item?.titleLength);
	const h1Count = Number(item?.h1Count);
	const h2Count = Number(item?.h2Count);
	const faqCount = Number(item?.faqCount);
	const links = Number(item?.internalLinks);
	const missingAlt = Number(item?.imagesMissingAlt);
	const words = Number(item?.contentWords);
	const robotsIndex = (item?.robotsIndex || '').toLowerCase();

	const hasMetaIssue =
		meta === 'missing' ||
		(Number.isFinite(titleLength) && (titleLength < 30 || titleLength > 60)) ||
		item?.hasCanonical === false;

	const hasIndexabilityIssue = robotsIndex === 'noindex';

	const hasContentIssue =
		(Number.isFinite(words) && words < 300) ||
		(Number.isFinite(missingAlt) && missingAlt > 0);

	const hasAiDiscoverabilityIssue =
		(Number.isFinite(h1Count) && h1Count !== 1) ||
		(Number.isFinite(h2Count) && h2Count <= 1) ||
		(Number.isFinite(faqCount) && faqCount < 1) ||
		(Number.isFinite(links) && links < 1);

	if (hasMetaIssue) {
		issues.push(__('Search Appearance', 'asneris-seo-toolkit'));
	}

	if (hasIndexabilityIssue) {
		issues.push(__('Advanced Settings', 'asneris-seo-toolkit'));
	}

	if (hasContentIssue) {
		issues.push(__('Content Quality', 'asneris-seo-toolkit'));
	}

	if (hasAiDiscoverabilityIssue) {
		issues.push(__('AI Discoverability', 'asneris-seo-toolkit'));
	}

	if (issues.length === 0) {
		return '—';
	}

	return issues.join(', ');
};

const formatOverviewEvidenceValue = (value) => {
	if (value === null || value === undefined) {
		return '-';
	}

	if (Array.isArray(value)) {
		return value.length > 0 ? value.join(', ') : '-';
	}

	if (typeof value === 'boolean') {
		return value ? 'true' : 'false';
	}

	if (typeof value === 'object') {
		try {
			return JSON.stringify(value);
		} catch {
			return '-';
		}
	}

	const text = String(value).trim();
	return text || '-';
};

const sanitizeUiEvidenceText = (value) => {
	const text = String(value || '').trim();
	if (!text) {
		return '-';
	}

	return text
		.replace(/unifiedData\.raw(\.[A-Za-z0-9_]+)*/gi, __('diagnostics source', 'asneris-seo-toolkit'))
		.replace(/linked_raw_evidence_fields/gi, __('linked evidence fields', 'asneris-seo-toolkit'))
		.replace(/raw_evidence/gi, __('evidence', 'asneris-seo-toolkit'));
};

const CANONICAL_MAPPING_GAP_MESSAGE = __('Rule check: Canonical-to-raw mapping is missing for this field. Please update mapping to show rule-compliant evidence.', 'asneris-seo-toolkit');
const CANONICAL_EVIDENCE_UNAVAILABLE_MESSAGE = __('Rule check: Mapping exists, but this diagnostics source does not contain a value for the mapped evidence field(s).', 'asneris-seo-toolkit');

const RAW_EVIDENCE_LABELS = {
	metaTitle: __('Meta Title', 'asneris-seo-toolkit'),
	metaTitleLength: __('Meta Title Length', 'asneris-seo-toolkit'),
	effectiveTitleLength: __('SEO Title Length', 'asneris-seo-toolkit'),
	effectiveDescriptionLength: __('Meta Description Length', 'asneris-seo-toolkit'),
	hasCanonical: __('Canonical', 'asneris-seo-toolkit'),
	robotsIndex: __('Robots Index', 'asneris-seo-toolkit'),
	robotsFollow: __('Robots Follow', 'asneris-seo-toolkit'),
	httpStatus: __('HTTP Status', 'asneris-seo-toolkit'),
	hasHeading: __('Heading Presence', 'asneris-seo-toolkit'),
	internalLinks: __('Internal Links', 'asneris-seo-toolkit'),
	wordCount: __('Word Count', 'asneris-seo-toolkit'),
	imageCount: __('Image Count', 'asneris-seo-toolkit'),
	keywordTopCount: __('Keyword Top Count', 'asneris-seo-toolkit'),
	keywordRatio: __('Keyword Ratio', 'asneris-seo-toolkit'),
	summaryPattern: __('Summary Section', 'asneris-seo-toolkit'),
	siteName: __('Site Name', 'asneris-seo-toolkit'),
	siteNameMentioned: __('Brand Mention Found', 'asneris-seo-toolkit'),
	productContextPattern: __('Product/Context Signal', 'asneris-seo-toolkit'),
	trustPattern: __('Trust Signal', 'asneris-seo-toolkit'),
	listDetected: __('List Detected', 'asneris-seo-toolkit'),
	tableDetected: __('Table Detected', 'asneris-seo-toolkit'),
	definitionPattern: __('Definition Signal', 'asneris-seo-toolkit'),
	faqPattern: __('FAQ Signal', 'asneris-seo-toolkit'),
	languageDeclaration: __('Language', 'asneris-seo-toolkit'),
	h1Present: __('H1 Presence', 'asneris-seo-toolkit'),
	headingHierarchyValid: __('Heading Hierarchy', 'asneris-seo-toolkit'),
	sectionsCoverage: __('Sections Coverage', 'asneris-seo-toolkit'),
	avgSentenceLength: __('Average Sentence Length', 'asneris-seo-toolkit'),
};

const humanizeEvidenceKey = (key) => {
	const normalized = String(key || '').trim();
	if (!normalized) {
		return __('Evidence', 'asneris-seo-toolkit');
	}

	if (RAW_EVIDENCE_LABELS[normalized]) {
		return RAW_EVIDENCE_LABELS[normalized];
	}

	return normalized
		.replace(/([a-z0-9])([A-Z])/g, '$1 $2')
		.replace(/[_-]+/g, ' ')
		.replace(/\b\w/g, (char) => char.toUpperCase());
};

const formatEvidenceFromFields = (fields = [], sourceItem = {}, rawEvidence = {}) => {
	const normalizedFields = Array.isArray(fields)
		? fields
			.map((field) => String(field || '').trim())
			.filter(Boolean)
		: [];

	if (normalizedFields.length < 1) {
		return '-';
	}

	const entries = normalizedFields.map((key) => {
		const candidate = Object.prototype.hasOwnProperty.call(rawEvidence, key)
			? rawEvidence[key]
			: sourceItem?.[key];
		const value = formatOverviewEvidenceValue(candidate);
		return `${ humanizeEvidenceKey(key) }: ${ sanitizeUiEvidenceText(value) }`;
	});

	return entries.join(' | ');
};

const resolveCanonicalEvidenceGapMessage = (mappedFields = [], mappedSummary = '-') => {
	const hasMapping = Array.isArray(mappedFields) && mappedFields.length > 0;
	if (!hasMapping) {
		return sanitizeUiEvidenceText(CANONICAL_MAPPING_GAP_MESSAGE);
	}

	const normalizedSummary = String(mappedSummary || '').trim();
	if (!normalizedSummary || normalizedSummary === '-') {
		return sanitizeUiEvidenceText(CANONICAL_EVIDENCE_UNAVAILABLE_MESSAGE);
	}

	return sanitizeUiEvidenceText(CANONICAL_MAPPING_GAP_MESSAGE);
};

const reconcileMetaTitleLengthFields = (item) => {
	if (!item || typeof item !== 'object') {
		return item;
	}

	const normalizedTitle = String(item?.metaTitle || item?.seoTitle || item?.effectiveTitle || item?.title || '')
		.replace(/<[^>]*>/g, ' ')
		.replace(/\s+/g, ' ')
		.trim();

	if (!normalizedTitle) {
		return item;
	}

	const computedLength = normalizedTitle.length;
	const storedLengths = [item?.metaTitleLength, item?.titleLength, item?.effectiveTitleLength]
		.map((value) => Number(value))
		.filter((value) => Number.isFinite(value) && value >= 0)
		.map((value) => Math.round(value));

	const allStoredMatchComputed = storedLengths.length > 0 && storedLengths.every((value) => value === computedLength);
	if (allStoredMatchComputed) {
		return item;
	}

	return {
		...item,
		metaTitleLength: computedLength,
		titleLength: computedLength,
		effectiveTitleLength: computedLength,
	};
};

const formatOverviewEvidenceSummary = (record, options = {}) => {
	const linkedFields = Array.isArray(record?.linked_raw_evidence_fields)
		? record.linked_raw_evidence_fields || []
		: [];
	const rawEvidence = record?.raw_evidence && typeof record.raw_evidence === 'object'
		? record.raw_evidence
		: {};
	const sourceItem = options?.sourceItem && typeof options.sourceItem === 'object'
		? options.sourceItem
		: {};
	const canonicalMappedFields = Array.isArray(options?.mappedFields)
		? options.mappedFields
		: [];

	if (canonicalMappedFields.length > 0) {
		const mappedSummary = formatEvidenceFromFields(canonicalMappedFields, sourceItem, rawEvidence);
		if (mappedSummary !== '-') {
			return mappedSummary;
		}

		return resolveCanonicalEvidenceGapMessage(canonicalMappedFields, mappedSummary);
	}

	return resolveCanonicalEvidenceGapMessage(canonicalMappedFields, '-');
};

const toScoreImpactLabel = (statusValue) => {
	const status = mapCheckStatus(statusValue || 'not scanned');
	if (status === 'pass') {
		return __('Positive score impact', 'asneris-seo-toolkit');
	}
	if (status === 'warning') {
		return __('Partial score impact', 'asneris-seo-toolkit');
	}
	if (status === 'fail') {
		return __('Negative score impact', 'asneris-seo-toolkit');
	}

	return __('No score impact (not scanned)', 'asneris-seo-toolkit');
};

const toRecommendedFix = (statusValue, canonicalField) => {
	const status = mapCheckStatus(statusValue || 'not scanned');
	if (status === 'pass') {
		return __('No action required', 'asneris-seo-toolkit');
	}
	if (status === 'not_scanned') {
		return __('Re-run diagnostics to capture mapped evidence', 'asneris-seo-toolkit');
	}

	return `${ __('Improve', 'asneris-seo-toolkit') } ${ canonicalField }`;
};

const buildTransparencyExplanation = (canonicalField, rawEvidenceSummary, statusValue) => {
	const evidenceText = String(rawEvidenceSummary || '').trim() || sanitizeUiEvidenceText(CANONICAL_MAPPING_GAP_MESSAGE);
	return `${ canonicalField } -> ${ evidenceText } -> ${ toScoreImpactLabel(statusValue) } -> ${ toRecommendedFix(statusValue, canonicalField) }`;
};

const TAB_RECOMMANDATION = 'recommandation';
const MAIN_TAB_PRIORITY = 'priority';
const MAIN_TAB_NON_PRIORITY = 'non_priority';

const DETAIL_TAB_OVERVIEW = 'overview';
const DETAIL_TAB_INDEXABILITY = 'indexability';
const DETAIL_TAB_CONTENT = 'content';
const DETAIL_TAB_SEARCH_APPEARANCE = 'search_appearance';
const DETAIL_TAB_STRUCTURED_DATA = 'structured_data';
const DETAIL_TAB_LINKS = 'links';
const DETAIL_TAB_IMAGES = 'images';
const DETAIL_TAB_AI_DISCOVERABILITY = 'ai_discoverability';
const DETAIL_CONTENT_SECTION_DETAILS = 'details';
const DETAIL_CONTENT_SECTION_ISSUES = 'issues';
const DETAIL_TAB_HIDE_MATCH_COUNT = new Set([
	DETAIL_TAB_INDEXABILITY,
	DETAIL_TAB_CONTENT,
	DETAIL_TAB_AI_DISCOVERABILITY,
	DETAIL_TAB_LINKS,
	DETAIL_TAB_IMAGES,
	DETAIL_TAB_STRUCTURED_DATA,
]);

const DETAIL_VIEW_TABS = [
	{ key: DETAIL_TAB_OVERVIEW, label: __('Overview', 'asneris-seo-toolkit') },
	{ key: DETAIL_TAB_INDEXABILITY, label: __('Indexability', 'asneris-seo-toolkit') },
	{ key: DETAIL_TAB_AI_DISCOVERABILITY, label: __('AI Discoverability', 'asneris-seo-toolkit') },
	{ key: DETAIL_TAB_CONTENT, label: __('Content', 'asneris-seo-toolkit') },
	{ key: DETAIL_TAB_LINKS, label: __('Links', 'asneris-seo-toolkit') },
	{ key: DETAIL_TAB_IMAGES, label: __('Images', 'asneris-seo-toolkit') },
	{ key: DETAIL_TAB_STRUCTURED_DATA, label: __('Structured Data', 'asneris-seo-toolkit') },
	{ key: DETAIL_TAB_SEARCH_APPEARANCE, label: __('Search Appearance', 'asneris-seo-toolkit') },
];

const getScoreBand = (score) => {
	if (!Number.isFinite(score)) {
		return 'neutral';
	}

	if (score >= 80) {
		return 'good';
	}

	if (score >= 50) {
		return 'warning';
	}

	return 'critical';
};

const getIssueSummary = (item) => {
	const statusRows = Object.entries(TAB_FIELD_REGISTRY || {}).flatMap(([canonicalMapKey, fields]) => {
		if (!Array.isArray(fields) || fields.length < 1) {
			return [];
		}

		const statusLookup = getTabIssueStatusLookup(item || {}, canonicalMapKey);

		return fields.map((fieldLabel) => {
			const normalizedField = String(fieldLabel || '').trim().toLowerCase();
			const statusFromSnapshot = statusLookup.get(normalizedField);
			if (statusFromSnapshot) {
				return statusFromSnapshot;
			}

			const derived = deriveProposedFieldFromSource(fieldLabel, item || {});
			return mapCheckStatus(derived?.status || 'not scanned');
		});
	});

	const statuses = statusRows;
	const critical = statuses.filter((status) => status === 'fail').length;
	const warning = statuses.filter((status) => status === 'warning').length;

	return {
		total: critical + warning,
		critical,
		warning,
	};
};

const getHealthLabel = (health) => {
	const normalized = String(health || '').toLowerCase();
	if (normalized === 'good') {
		return __('Good', 'asneris-seo-toolkit');
	}
	if (normalized === 'warning') {
		return __('Warning', 'asneris-seo-toolkit');
	}
	if (normalized === 'poor') {
		return __('Poor', 'asneris-seo-toolkit');
	}

	return '-';
};

const COMPARISON_FILTER_ALL = 'all';
const COMPARISON_FILTER_IMPROVED = 'improved';
const COMPARISON_FILTER_REGRESSED = 'regressed';
const COMPARISON_FILTER_NO_CHANGE = 'no_change';

const COMPARISON_CATEGORY_ORDER = DETAIL_VIEW_TABS
	.filter((tab) => tab.key !== DETAIL_TAB_OVERVIEW)
	.map((tab) => tab.key);

const COMPARISON_CATEGORY_LABELS = DETAIL_VIEW_TABS.reduce((acc, tab) => {
	acc[tab.key] = tab.label;
	return acc;
}, {});

const toComparisonChangeLabel = (changeType) => {
	if (changeType === COMPARISON_FILTER_IMPROVED) {
		return __('Improved', 'asneris-seo-toolkit');
	}
	if (changeType === COMPARISON_FILTER_REGRESSED) {
		return __('Regressed', 'asneris-seo-toolkit');
	}

	return __('No Change', 'asneris-seo-toolkit');
};

const parseFirstNumber = (value) => {
	const text = String(value || '');
	const match = text.match(/-?\d+(?:\.\d+)?/);
	if (!match) {
		return null;
	}

	const parsed = Number(match[0]);
	return Number.isFinite(parsed) ? parsed : null;
};

const getCheckValueForComparison = (check) => {
	if (!check) {
		return '-';
	}

	const result = String(check?.result || '').trim();
	if (result) {
		return result;
	}

	const details = String(check?.details || '').trim();
	if (details) {
		return details;
	}

	return '-';
};

const deriveComparisonChangeType = (label, previousCheck, currentCheck) => {
	if (!previousCheck && currentCheck) {
		return COMPARISON_FILTER_IMPROVED;
	}

	if (previousCheck && !currentCheck) {
		return COMPARISON_FILTER_REGRESSED;
	}

	const prevStatus = mapCheckStatus(previousCheck?.status);
	const currStatus = mapCheckStatus(currentCheck?.status);
	const statusScore = { not_scanned: 0, fail: 0, warning: 1, pass: 2 };
	if (statusScore[currStatus] > statusScore[prevStatus]) {
		return COMPARISON_FILTER_IMPROVED;
	}
	if (statusScore[currStatus] < statusScore[prevStatus]) {
		return COMPARISON_FILTER_REGRESSED;
	}

	const prevValue = getCheckValueForComparison(previousCheck);
	const currValue = getCheckValueForComparison(currentCheck);
	if (prevValue === currValue) {
		return COMPARISON_FILTER_NO_CHANGE;
	}

	const prevNumber = parseFirstNumber(prevValue);
	const currNumber = parseFirstNumber(currValue);
	if (prevNumber !== null && currNumber !== null) {
		const lowerIsBetter = /missing|warning|fail|error|dropped|noindex|blocked/i.test(String(label || ''));
		if (lowerIsBetter) {
			return currNumber < prevNumber ? COMPARISON_FILTER_IMPROVED : COMPARISON_FILTER_REGRESSED;
		}

		const higherIsBetter = /score|pass|word|link|schema|fresh/i.test(String(label || ''));
		if (higherIsBetter) {
			return currNumber > prevNumber ? COMPARISON_FILTER_IMPROVED : COMPARISON_FILTER_REGRESSED;
		}
	}

	return COMPARISON_FILTER_NO_CHANGE;
};

const formatChartDateLabel = (value) => {
	if (!value) {
		return '-';
	}

	const date = new Date(value);
	if (Number.isNaN(date.getTime())) {
		return '-';
	}

	return date.toLocaleDateString([], { day: 'numeric', month: 'short' });
};

const buildLineChartModel = (series, options = {}) => {
	const width = Number(options.width || 640);
	const height = Number(options.height || 220);
	const padding = {
		left: 36,
		right: 10,
		top: 12,
		bottom: 28,
	};
	const points = Array.isArray(series) ? series : [];
	if (points.length < 1) {
		return null;
	}

	const chartWidth = Math.max(1, width - padding.left - padding.right);
	const chartHeight = Math.max(1, height - padding.top - padding.bottom);
	const yMaxInput = Number(options.yMax);
	const yMax = Number.isFinite(yMaxInput) && yMaxInput > 0
		? yMaxInput
		: Math.max(1, ...points.map((point) => Number(point?.value || 0)));

	const toX = (index) => {
		if (points.length === 1) {
			return padding.left + chartWidth / 2;
		}
		return padding.left + (index / (points.length - 1)) * chartWidth;
	};

	const toY = (value) => {
		const safe = Math.max(0, Math.min(yMax, Number(value || 0)));
		return padding.top + chartHeight - (safe / yMax) * chartHeight;
	};

	const dots = points.map((point, index) => ({
		x: toX(index),
		y: toY(point.value),
		value: Number(point?.value || 0),
		label: point?.label || '-',
		id: point?.id || `${ index }`,
	}));

	const path = dots.map((dot, index) => `${ index === 0 ? 'M' : 'L' } ${ dot.x.toFixed(2) } ${ dot.y.toFixed(2) }`).join(' ');
	const step = Math.max(1, Math.ceil(points.length / 6));
	const xLabels = dots.filter((dot, index) => index % step === 0 || index === dots.length - 1);
	const yTicks = [0, Math.round(yMax / 2), yMax];

	return {
		width,
		height,
		padding,
		path,
		dots,
		xLabels,
		yTicks,
		toY,
	};
};

const normalizeVariableSyntax = (value) => String(value || '').replace(/\{%\s*([a-z_]+)\s*%\}/gi, '{$1}');

const resolveInlineVariables = (value, context = {}) => {
	let output = normalizeVariableSyntax(value);

	Object.entries(context).forEach(([key, rawValue]) => {
		const tokenRegex = new RegExp(`\\{${ key }\\}`, 'gi');
		output = output.replace(tokenRegex, String(rawValue || ''));
	});

	return output.replace(/\s+/g, ' ').trim();
};

const resolveTemplate = (template, context = {}) => {
	if (!template) {
		return '';
	}

	return resolveInlineVariables(String(template), context);
};

const stripHtml = (value) => {
	if (!value) {
		return '';
	}

	const input = String(value);

	if (typeof window !== 'undefined' && typeof window.DOMParser !== 'undefined') {
		try {
			const parser = new window.DOMParser();
			const doc = parser.parseFromString(input, 'text/html');
			doc.querySelectorAll('script,style,noscript,template,svg,canvas,iframe,object').forEach((node) => node.remove());
			return (doc.body?.textContent || '').replace(/\s+/g, ' ').trim();
		} catch {
			// Fall through to regex fallback.
		}
	}

	return input
		.replace(/<!--[\s\S]*?-->/g, ' ')
		.replace(/<(script|style|noscript|template)[\s\S]*?<\/\1>/gi, ' ')
		.replace(/<[^>]*>/g, ' ')
		.replace(/\s+/g, ' ')
		.trim();
};

const trimPreview = (value, maxLength) => {
	const normalized = String(value || '').replace(/\s+/g, ' ').trim();
	if (!normalized) {
		return '';
	}

	if (normalized.length <= maxLength) {
		return normalized;
	}

	return `${ normalized.slice(0, maxLength - 1).trim() }...`;
};

const buildSearchAppearanceVisualModel = (rows = [], item = null) => {
	const normalizedRows = Array.isArray(rows) ? rows : [];
	const labelSet = new Set(
		normalizedRows.map((row) => String(row?.cells?.[0] || '').toLowerCase())
	);

	const getResult = (labelKey, fallback = '-') => {
		const row = normalizedRows.find((entry) => String(entry?.cells?.[0] || '').toLowerCase() === labelKey);
		if (!row) {
			return fallback;
		}

		const value = row?.cells?.[2];
		if (value === undefined || value === null || String(value).trim() === '') {
			return fallback;
		}

		return String(value);
	};

	const getDetails = (labelKey, fallback = '') => {
		const row = normalizedRows.find((entry) => String(entry?.cells?.[0] || '').toLowerCase() === labelKey);
		if (!row) {
			return fallback;
		}

		const value = row?.cells?.[3];
		if (value === undefined || value === null || String(value).trim() === '') {
			return fallback;
		}

		return String(value);
	};

	const missingChecks = DISCOVERABILITY_EXPECTED_CHECKS.searchAppearance
		.filter((key) => !labelSet.has(key))
		.map((key) => key.replace(/\b\w/g, (char) => char.toUpperCase()));

	const siteName = String(window?.asnerisseoData?.siteName || window?.location?.hostname || '').trim();
	const titleSeparator = String(window?.asnerisseoData?.titleSeparator || '|').trim() || '|';
	const pageTitle = String(item?.title || '').trim();
	const postType = String(item?.postType || 'post').trim();
	const cleanedExcerpt = stripHtml(item?.excerpt || '');
	const variableContext = {
		title: pageTitle,
		site: siteName,
		separator: titleSeparator,
		excerpt: trimPreview(cleanedExcerpt, 160),
		date: new Date().toISOString().split('T')[0],
		author: String(window?.asnerisseoData?.authorName || '').trim(),
		term: String(window?.asnerisseoData?.primaryTerm || '').trim(),
	};

	const titleTemplate = window?.asnerisseoData?.titleTemplates?.[postType] || '';
	const descriptionTemplate = window?.asnerisseoData?.descriptionTemplates?.[postType] || '';
	const templateTitle = resolveTemplate(titleTemplate, variableContext);
	const templateDescription = resolveTemplate(descriptionTemplate, variableContext);

	const rawSeoTitle = String(item?.metaTitle || item?.seoTitle || '').trim();
	const rawSeoDescription = String(item?.seoDescription || item?.metaDescription || '').trim();
	const resolvedManualTitle = resolveTemplate(rawSeoTitle, variableContext);
	const resolvedManualDescription = resolveTemplate(rawSeoDescription, variableContext);
	const excerptFallback = trimPreview(cleanedExcerpt, 160);

	const googleTitle = resolvedManualTitle || templateTitle || pageTitle || __('Untitled page', 'asneris-seo-toolkit');
	const googleDescription = resolvedManualDescription || templateDescription || excerptFallback || __('Add a custom meta description to control your search snippet.', 'asneris-seo-toolkit');

	const ogTitleStatus = getResult('open graph title', __('Missing', 'asneris-seo-toolkit'));
	const ogDescriptionStatus = getResult('open graph description', __('Missing', 'asneris-seo-toolkit'));
	const ogImageStatus = getResult('open graph image', __('Missing', 'asneris-seo-toolkit'));
	const ogImageDetails = getDetails('open graph image', '');
	const ogImageUrl = /^https?:\/\//i.test(ogImageDetails) ? ogImageDetails : '';
	const defaultOgImage = String(
		window?.asnerisseoAdminDashboardData?.defaultOgImage
		|| window?.asnerisseoAdminDashboardData?.default_og_image
		|| window?.asnerisseoData?.defaultOgImage
		|| window?.asnerisseoData?.default_og_image
		|| ''
	).trim();

	const socialTitle = String(item?.ogTitle || '').trim() || (String(ogTitleStatus).toLowerCase() === 'present' ? googleTitle : googleTitle);
	const socialDescription = String(item?.ogDescription || '').trim() || (String(ogDescriptionStatus).toLowerCase() === 'present' ? googleDescription : googleDescription);
	const socialImage =
		String(item?.ogImage || '').trim()
		|| (String(ogImageStatus).toLowerCase() === 'present' ? ogImageUrl : '')
		|| defaultOgImage
		|| '';

	const searchTitleSource = resolvedManualTitle
		? __('Customer key-in', 'asneris-seo-toolkit')
		: (templateTitle ? __('Template', 'asneris-seo-toolkit') : __('Fallback', 'asneris-seo-toolkit'));
	const searchDescriptionSource = resolvedManualDescription
		? __('Customer key-in', 'asneris-seo-toolkit')
		: (templateDescription ? __('Template', 'asneris-seo-toolkit') : __('Fallback', 'asneris-seo-toolkit'));
	const socialTitleSource = String(item?.ogTitle || '').trim()
		? __('Customer key-in', 'asneris-seo-toolkit')
		: __('Template / fallback', 'asneris-seo-toolkit');
	const socialDescriptionSource = String(item?.ogDescription || '').trim()
		? __('Customer key-in', 'asneris-seo-toolkit')
		: __('Template / fallback', 'asneris-seo-toolkit');
	const socialImageSource = String(item?.ogImage || '').trim()
		? __('Customer key-in', 'asneris-seo-toolkit')
		: (defaultOgImage
			? __('Template (Social Settings)', 'asneris-seo-toolkit')
			: (ogImageUrl ? __('Template / fallback', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit')));

	const titleSource = resolvedManualTitle
		? __('Metadata', 'asneris-seo-toolkit')
		: (templateTitle ? __('Template', 'asneris-seo-toolkit') : __('Page title fallback', 'asneris-seo-toolkit'));
	const descriptionSource = resolvedManualDescription
		? __('Metadata', 'asneris-seo-toolkit')
		: (templateDescription ? __('Template', 'asneris-seo-toolkit') : __('Excerpt fallback', 'asneris-seo-toolkit'));

	return {
		title: googleTitle,
		description: googleDescription,
		url: item?.url || '',
		titleSource,
		descriptionSource,
		googleTitle,
		googleDescription,
		socialTitle,
		socialDescription,
		socialImage,
		searchTitleSource,
		searchDescriptionSource,
		socialTitleSource,
		socialDescriptionSource,
		socialImageSource,
		supportedPlatforms: ['Facebook', 'LinkedIn', 'X', 'WhatsApp', 'Telegram', 'Slack'],
		platformCards: [
			{
				id: 'facebook',
				label: 'Facebook',
				title: trimPreview(socialTitle, 88),
				description: trimPreview(socialDescription, 170),
			},
			{
				id: 'x',
				label: 'X',
				title: trimPreview(socialTitle, 70),
				description: trimPreview(socialDescription, 145),
			},
			{
				id: 'linkedin',
				label: 'LinkedIn',
				title: trimPreview(socialTitle, 92),
				description: trimPreview(socialDescription, 155),
			},
		],
		signals: [
			{ label: __('Google Preview', 'asneris-seo-toolkit'), value: getResult('google preview', __('Missing', 'asneris-seo-toolkit')) },
			{ label: __('Open Graph Title', 'asneris-seo-toolkit'), value: ogTitleStatus },
			{ label: __('Open Graph Description', 'asneris-seo-toolkit'), value: ogDescriptionStatus },
			{ label: __('Open Graph Image', 'asneris-seo-toolkit'), value: ogImageStatus },
			{ label: __('Twitter Card', 'asneris-seo-toolkit'), value: getResult('twitter card', __('Missing', 'asneris-seo-toolkit')) },
			{ label: __('Breadcrumb Schema', 'asneris-seo-toolkit'), value: getResult('breadcrumb schema', __('Missing', 'asneris-seo-toolkit')) },
		],
		missingChecks,
	};
};

const DETAIL_TAB_PATTERN_MAP = {
	[DETAIL_TAB_INDEXABILITY]: 'indexability',
	[DETAIL_TAB_CONTENT]: 'content',
	[DETAIL_TAB_SEARCH_APPEARANCE]: 'searchAppearance',
	[DETAIL_TAB_STRUCTURED_DATA]: 'structuredData',
	[DETAIL_TAB_LINKS]: 'links',
	[DETAIL_TAB_IMAGES]: 'images',
	[DETAIL_TAB_AI_DISCOVERABILITY]: 'ai',
};

const DETAIL_TAB_CATEGORY_MAP = {
	[DETAIL_TAB_INDEXABILITY]: 'advanced',
	[DETAIL_TAB_CONTENT]: 'quality',
	[DETAIL_TAB_LINKS]: 'links',
	[DETAIL_TAB_AI_DISCOVERABILITY]: 'ai',
};

const INDEXABILITY_RULE_FIELDS = TAB_FIELD_REGISTRY.indexability;

const OVERVIEW_RULE_FIELDS = [
	'HTTP Status',
	'Robots Meta',
	'SEO Title Length',
	'Meta Description Length',
	'H1 Presence',
	'Internal Links',
	'Content Depth (Word Count)',
];

const SEARCH_APPEARANCE_RULE_FIELDS = [
	'SEO Title',
	'Meta Description',
	'SEO Title Length',
	'Meta Description Length',
	'Canonical',
];

const SEARCH_APPEARANCE_PRIMARY_RULE_FIELDS = TAB_FIELD_REGISTRY.searchAppearance;

const CONTENT_RULE_FIELDS = [
	'SEO Title',
	'SEO Title Length',
	'Meta Description',
	'Meta Description Length',
	'H1 Presence',
	'Content Depth (Word Count)',
];

const CONTENT_PRIMARY_RULE_FIELDS = TAB_FIELD_REGISTRY.contentQuality;

const AI_RULE_FIELDS = [
	'Topic Consistency',
	'Clear Page Purpose',
	'Summary Section',
	'Content Completeness',
	'Internal References',
];

const AI_PRIMARY_RULE_FIELDS = TAB_FIELD_REGISTRY.aiDiscoverability;

const LINKS_RULE_FIELDS = TAB_FIELD_REGISTRY.links;

const IMAGES_RULE_FIELDS = TAB_FIELD_REGISTRY.images;

const STRUCTURED_DATA_RULE_FIELDS = TAB_FIELD_REGISTRY.structuredData;

const RULE_FIELDS_BY_TAB = {
	[DETAIL_TAB_OVERVIEW]: OVERVIEW_RULE_FIELDS,
	[DETAIL_TAB_SEARCH_APPEARANCE]: SEARCH_APPEARANCE_PRIMARY_RULE_FIELDS,
	[DETAIL_TAB_INDEXABILITY]: INDEXABILITY_RULE_FIELDS,
	[DETAIL_TAB_CONTENT]: CONTENT_PRIMARY_RULE_FIELDS,
	[DETAIL_TAB_IMAGES]: IMAGES_RULE_FIELDS,
	[DETAIL_TAB_LINKS]: LINKS_RULE_FIELDS,
	[DETAIL_TAB_STRUCTURED_DATA]: STRUCTURED_DATA_RULE_FIELDS,
	[DETAIL_TAB_AI_DISCOVERABILITY]: AI_PRIMARY_RULE_FIELDS,
};

const PRIMARY_LIST_FIELDS_BY_TAB = {
	[DETAIL_TAB_SEARCH_APPEARANCE]: SEARCH_APPEARANCE_PRIMARY_RULE_FIELDS,
	[DETAIL_TAB_CONTENT]: CONTENT_PRIMARY_RULE_FIELDS,
	[DETAIL_TAB_AI_DISCOVERABILITY]: AI_PRIMARY_RULE_FIELDS,
};

const RULE_FIELD_LOOKUP = Object.values(RULE_FIELDS_BY_TAB)
	.reduce((acc, fields) => {
		(fields || []).forEach((field) => {
			acc[String(field || '').trim().toLowerCase()] = String(field || '').trim();
		});
		return acc;
	}, {});

const getCanonicalRuleField = (label = '') => {
	const normalized = String(label || '').trim().toLowerCase();
	if (!normalized) {
		return '';
	}

	return RULE_FIELD_LOOKUP[normalized] || '';
};

const maybeResolveRuleCanonicalMapKey = (canonicalField = '') => {
	if (OVERVIEW_RULE_FIELDS.includes(canonicalField)) return 'overview';
	if (SEARCH_APPEARANCE_RULE_FIELDS.includes(canonicalField)) return 'searchAppearance';
	if (SEARCH_APPEARANCE_PRIMARY_RULE_FIELDS.includes(canonicalField)) return 'searchAppearance';
	if (INDEXABILITY_RULE_FIELDS.includes(canonicalField)) return 'indexability';
	if (CONTENT_RULE_FIELDS.includes(canonicalField)) return 'contentQuality';
	if (CONTENT_PRIMARY_RULE_FIELDS.includes(canonicalField)) return 'contentQuality';
	if (IMAGES_RULE_FIELDS.includes(canonicalField)) return 'images';
	if (LINKS_RULE_FIELDS.includes(canonicalField)) return 'links';
	if (STRUCTURED_DATA_RULE_FIELDS.includes(canonicalField)) return 'structuredData';
	if (AI_RULE_FIELDS.includes(canonicalField)) return 'aiDiscoverability';
	if (AI_PRIMARY_RULE_FIELDS.includes(canonicalField)) return 'aiDiscoverability';
	return '';
};

const normalizeStructuredDataFieldLabel = (label) => {
	const normalized = String(label || '').trim();
	if (!normalized) {
		return '';
	}

	return normalized;
};

const normalizeSearchAppearanceFieldLabel = (label) => {
	const normalized = String(label || '').trim();
	if (!normalized) {
		return '';
	}

	return normalized;
};

const normalizeOverviewFieldLabel = (label) => {
	const normalized = String(label || '').trim();
	if (!normalized) {
		return '';
	}

	return normalized;
};

const normalizeIndexabilityFieldLabel = (label) => {
	const normalized = String(label || '').trim();
	if (!normalized) {
		return '';
	}

	return normalized;
};

const normalizeContentFieldLabel = (label) => {
	const normalized = String(label || '').trim();
	if (!normalized) {
		return '';
	}

	const normalizedKey = normalized.toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
	const contentAliasMap = {
		'h1 heading': 'H1 Presence',
		'h1 exists': 'H1 Presence',
		'h1 present': 'H1 Presence',
		'content quality': 'Content Depth (Word Count)',
		'content depth': 'Content Depth (Word Count)',
		'word count': 'Content Depth (Word Count)',
	};

	if (contentAliasMap[normalizedKey]) {
		return contentAliasMap[normalizedKey];
	}

	return normalized;
};

const normalizeAiFieldLabel = (label) => {
	const normalized = String(label || '').trim();
	if (!normalized) {
		return '';
	}

	return normalized;
};

const normalizeFieldForRuleTab = (tabKey, label) => {
	if (tabKey === DETAIL_TAB_OVERVIEW) {
		return normalizeOverviewFieldLabel(label);
	}

	if (tabKey === DETAIL_TAB_SEARCH_APPEARANCE) {
		return normalizeSearchAppearanceFieldLabel(label);
	}

	if (tabKey === DETAIL_TAB_INDEXABILITY) {
		return normalizeIndexabilityFieldLabel(label);
	}

	if (tabKey === DETAIL_TAB_CONTENT) {
		return normalizeContentFieldLabel(label);
	}

	if (tabKey === DETAIL_TAB_AI_DISCOVERABILITY) {
		return normalizeAiFieldLabel(label);
	}

	if (tabKey === DETAIL_TAB_STRUCTURED_DATA) {
		return normalizeStructuredDataFieldLabel(label);
	}

	return String(label || '').trim();
};

const resolveRuleCanonicalField = (fieldLabel) => {
	const rawLabel = String(fieldLabel || '').trim();
	if (!rawLabel) {
		return { canonicalField: '', canonicalMapKey: '' };
	}

	const canonicalRuleField = getCanonicalRuleField(rawLabel);
	if (canonicalRuleField) {
		const canonicalMapKey = maybeResolveRuleCanonicalMapKey(canonicalRuleField);
		if (canonicalMapKey) {
			return { canonicalField: canonicalRuleField, canonicalMapKey };
		}
	}

	if (SEARCH_APPEARANCE_PRIMARY_RULE_FIELDS.includes(rawLabel)) {
		return { canonicalField: rawLabel, canonicalMapKey: 'searchAppearance' };
	}

	if (CONTENT_PRIMARY_RULE_FIELDS.includes(rawLabel)) {
		return { canonicalField: rawLabel, canonicalMapKey: 'contentQuality' };
	}

	if (AI_PRIMARY_RULE_FIELDS.includes(rawLabel)) {
		return { canonicalField: rawLabel, canonicalMapKey: 'aiDiscoverability' };
	}

	return {
		canonicalField: rawLabel,
		canonicalMapKey: getCanonicalMapKeyByFieldLabel(rawLabel),
	};
};

const PROPOSED_FIELDS_BY_TAB = {
	[DETAIL_TAB_OVERVIEW]: OVERVIEW_RULE_FIELDS,
	[DETAIL_TAB_SEARCH_APPEARANCE]: SEARCH_APPEARANCE_RULE_FIELDS,
	[DETAIL_TAB_INDEXABILITY]: INDEXABILITY_RULE_FIELDS,
	[DETAIL_TAB_CONTENT]: CONTENT_RULE_FIELDS,
	[DETAIL_TAB_IMAGES]: IMAGES_RULE_FIELDS,
	[DETAIL_TAB_LINKS]: LINKS_RULE_FIELDS,
	[DETAIL_TAB_STRUCTURED_DATA]: STRUCTURED_DATA_RULE_FIELDS,
	[DETAIL_TAB_AI_DISCOVERABILITY]: AI_RULE_FIELDS,
};

const SHARED_CANONICAL_FIELD_LABELS = (() => {
	const occurrences = new Map();

	Object.values(PROPOSED_FIELDS_BY_TAB || {}).forEach((fields) => {
		if (!Array.isArray(fields)) {
			return;
		}

		fields.forEach((field) => {
			const normalized = String(field || '').trim().toLowerCase();
			if (!normalized) {
				return;
			}

			occurrences.set(normalized, (occurrences.get(normalized) || 0) + 1);
		});
	});

	return new Set(
		Array.from(occurrences.entries())
			.filter(([, count]) => count > 1)
			.map(([label]) => label)
	);
})();

const isSharedCanonicalFieldLabel = (fieldLabel) => SHARED_CANONICAL_FIELD_LABELS.has(String(fieldLabel || '').trim().toLowerCase());

const DETAIL_TAB_SECTION_KEY_MAP = {
	[DETAIL_TAB_OVERVIEW]: 'overview',
	[DETAIL_TAB_SEARCH_APPEARANCE]: 'search',
	[DETAIL_TAB_INDEXABILITY]: 'advanced',
	[DETAIL_TAB_CONTENT]: 'quality',
	[DETAIL_TAB_IMAGES]: 'quality',
	[DETAIL_TAB_LINKS]: 'links',
	[DETAIL_TAB_STRUCTURED_DATA]: 'schema',
	[DETAIL_TAB_AI_DISCOVERABILITY]: 'ai',
};

const DETAIL_TAB_TO_CANONICAL_MAP_KEY = {
	[DETAIL_TAB_OVERVIEW]: 'overview',
	[DETAIL_TAB_SEARCH_APPEARANCE]: 'searchAppearance',
	[DETAIL_TAB_INDEXABILITY]: 'indexability',
	[DETAIL_TAB_CONTENT]: 'contentQuality',
	[DETAIL_TAB_IMAGES]: 'images',
	[DETAIL_TAB_LINKS]: 'links',
	[DETAIL_TAB_STRUCTURED_DATA]: 'structuredData',
	[DETAIL_TAB_AI_DISCOVERABILITY]: 'aiDiscoverability',
};

const MODEL_TAB_TO_DETAIL_TAB_KEY = Object.entries(DETAIL_TAB_TO_CANONICAL_MAP_KEY).reduce((acc, [detailTabKey, modelTabKey]) => {
	acc[modelTabKey] = detailTabKey;
	return acc;
}, {});

const HISTORY_CATEGORY_KEYS = getCanonicalModelTabKeys({ includeOverview: false })
	.map((modelTabKey) => MODEL_TAB_TO_DETAIL_TAB_KEY[modelTabKey])
	.filter(Boolean);

const DETAIL_TAB_LABEL_BY_KEY = DETAIL_VIEW_TABS.reduce((acc, tab) => {
	acc[tab.key] = tab.label;
	return acc;
}, {});

const HISTORY_CATEGORY_LABELS = HISTORY_CATEGORY_KEYS.reduce((acc, detailTabKey) => {
	acc[detailTabKey] = DETAIL_TAB_LABEL_BY_KEY[detailTabKey] || detailTabKey;
	return acc;
}, {});

const TAXONOMY_TO_MODEL_TAB_FALLBACK = {
	search: 'searchAppearance',
	advanced: 'indexability',
	quality: 'contentQuality',
	links: 'links',
	schema: 'structuredData',
	images: 'images',
	ai: 'aiDiscoverability',
	social: 'searchAppearance',
};

const resolveHistoryDetailTabKeyForCheck = (check) => {
	const label = String(check?.label || '').trim();
	if (!label) {
		return '';
	}

	const category = TAXONOMY_SECTION_KEYS.has(check?.category)
		? String(check.category)
		: toCheckCategory(label);

	const fallbackModelTabKey = TAXONOMY_TO_MODEL_TAB_FALLBACK[category] || '';
	const fallbackDetailTabKey = fallbackModelTabKey
		? (MODEL_TAB_TO_DETAIL_TAB_KEY[fallbackModelTabKey] || '')
		: '';

	const candidateDetailKeys = [
		...HISTORY_CATEGORY_KEYS,
		...(fallbackDetailTabKey ? [fallbackDetailTabKey] : []),
	].filter((value, index, list) => value && list.indexOf(value) === index);

	for (const detailTabKey of candidateDetailKeys) {
		const modelTabKey = DETAIL_TAB_TO_CANONICAL_MAP_KEY[detailTabKey] || '';
		if (!modelTabKey) {
			continue;
		}

		const canonicalFields = getCanonicalFieldsByTab(modelTabKey);
		if (canonicalFields.some((canonicalField) => doesCheckLabelMatchCanonicalField(modelTabKey, canonicalField, label))) {
			return detailTabKey;
		}
	}

	return fallbackDetailTabKey;
};

const getCanonicalMapKeyByFieldLabel = (fieldLabel) => {
	const normalizedField = String(fieldLabel || '').trim().toLowerCase(); if (!normalizedField) return '';

	for (const [detailTabKey, fields] of Object.entries(PROPOSED_FIELDS_BY_TAB)) {
		if (!Array.isArray(fields)) {
			continue;
		}

		if (fields.some((field) => String(field || '').trim().toLowerCase() === normalizedField)) {
			return DETAIL_TAB_TO_CANONICAL_MAP_KEY[detailTabKey] || '';
		}
	}

	return '';
};

const CANONICAL_MAPPING_GAP_SOURCE = {
	overview: TAB_FIELD_REGISTRY.overview,
	searchAppearance: TAB_FIELD_REGISTRY.searchAppearance,
	indexability: TAB_FIELD_REGISTRY.indexability,
	contentQuality: TAB_FIELD_REGISTRY.contentQuality,
	images: TAB_FIELD_REGISTRY.images,
	links: TAB_FIELD_REGISTRY.links,
	structuredData: TAB_FIELD_REGISTRY.structuredData,
	aiDiscoverability: TAB_FIELD_REGISTRY.aiDiscoverability,
};

const CANONICAL_MAPPING_TAB_LABELS = {
	overview: __('Overview', 'asneris-seo-toolkit'),
	searchAppearance: __('Search Appearance', 'asneris-seo-toolkit'),
	indexability: __('Indexability', 'asneris-seo-toolkit'),
	contentQuality: __('Content', 'asneris-seo-toolkit'),
	images: __('Images', 'asneris-seo-toolkit'),
	links: __('Links', 'asneris-seo-toolkit'),
	structuredData: __('Structured Data', 'asneris-seo-toolkit'),
	aiDiscoverability: __('AI Discoverability', 'asneris-seo-toolkit'),
};

const PROPOSED_FIELD_CHECK_MATCHERS = {
	'SEO Title': [/seo title/i],
	'SEO Title Length': [/seo title length/i],
	'Meta Description': [/meta description$/i, /meta description/i],
	'Meta Description Length': [/meta description length|description length/i],
	'Google Preview': [/google preview|search preview/i],
	'Open Graph Title': [/open graph title/i],
	'Open Graph Description': [/open graph description/i],
	'Open Graph Image': [/open graph image/i],
	'Twitter Card': [/twitter card/i],
	Canonical: [/canonical/i],
	'Self Canonical': [/self canonical|canonical self-check/i],
	'X-Robots-Tag': [/x-robots-tag/i],
	'HTTP Status': [/http status/i],
	'H1 Presence': [/h1 exists|h1 present/i],
	'Multiple H1': [/multiple h1/i],
	'Heading Structure': [/heading structure/i],
	'Heading Hierarchy': [/heading hierarchy/i],
	'Content Depth (Word Count)': [/word count|content depth/i],
	'Content Present': [/content present/i],
	Readability: [/readability/i],
	'Images Found': [/images found/i],
	'Image ALT Coverage': [/image alt coverage|images? & alt/i],
	'Missing ALT': [/missing alt/i],
	'Empty ALT': [/empty alt/i],
	'Featured Image': [/featured image/i],
	'Internal Links': [/internal links/i],
	'External Links': [/external links/i],
	'Nofollow Links': [/nofollow links/i],
	'Structured Data Present': [/structured data present|structured data found/i],
	'JSON-LD Valid': [/json-ld valid|json-ld|schema validation/i],
	'Primary Schema': [/primary schema|primary entity|article schema/i],
	'Organization Schema': [/organization schema/i],
	'FAQ Schema': [/faq schema/i],
	'Breadcrumb Schema': [/breadcrumb schema/i],
	'Topic Consistency': [/topic consistency/i],
	'Clear Page Purpose': [/clear page purpose/i],
	'Summary Section': [/summary section/i],
	'Content Completeness': [/content completeness/i],
	'Brand Mentions': [/brand mentions/i],
	'Product/Context Mentions': [/product\/context mentions/i],
	'Trust Signals': [/trust signals/i],
	'Table/List Detection': [/table\/list detection|table usage|list usage/i],
	'Definition Content': [/definition content|definitions\/examples\/how-to/i],
	'FAQ Signals': [/faq signals|faq content|faq ready/i],
	'Language Declaration': [/language declaration/i],
	'Internal References': [/internal references/i],
};

const toPresenceStatus = (value) => (value ? 'pass' : 'warning');

const findExistingProposedRow = (rows, fieldLabel) => {
	const matchers = PROPOSED_FIELD_CHECK_MATCHERS[fieldLabel] || [];
	return rows.find((row) => {
		const label = String(row?.cells?.[0] || '');
		if (label.toLowerCase() === String(fieldLabel).toLowerCase()) {
			return true;
		}

		return matchers.some((pattern) => pattern.test(label));
	});
};

const deriveProposedFieldFromSource = (fieldLabel, item = {}) => {
	const normalizeTextForLength = (value) => {
		if (value === null || value === undefined) {
			return '';
		}

		const asText = Array.isArray(value)
			? value.join(' ')
			: (typeof value === 'object' ? String(value?.rendered || value?.raw || '') : String(value));
		return asText
			.replace(/<[^>]*>/g, ' ')
			.replace(/\s+/g, ' ')
			.trim();
	};
	const resolveNumericLength = (...candidates) => {
		for (const candidate of candidates) {
			const numeric = Number(candidate);
			if (Number.isFinite(numeric) && numeric >= 0) {
				return Math.round(numeric);
			}
		}

		return null;
	};
	const seoTitle = normalizeTextForLength(item?.metaTitle || item?.seoTitle || item?.effectiveTitle || item?.title || '');
	const seoDescription = normalizeTextForLength(item?.seoDescription || item?.metaDescription || item?.effectiveDescription || '');
	const canonical = String(item?.canonical || '').trim();
	const pageUrl = String(item?.url || '').trim();
	const robotsIndex = String(item?.robotsIndex || '').trim();
	const robotsFollow = String(item?.robotsFollow || '').trim();
	const xRobotsTag = String(item?.xRobotsTag || '').trim();
	const httpStatus = Number(item?.httpStatus);
	const ogTitle = String(item?.ogTitle || '').trim();
	const ogDescription = String(item?.ogDescription || '').trim();
	const ogImage = String(item?.ogImage || '').trim();
	const wordCount = Number(item?.contentWords);
	const h1Count = Number(item?.h1Count);
	const h2Count = Number(item?.h2Count);
	const faqCount = Number(item?.faqCount);
	const imageCount = Number(item?.imageCount);
	const missingAlt = Number(item?.imagesMissingAlt);
	const emptyAlt = Number(item?.imagesEmptyAlt);
	const featuredImage = item?.featuredImage;
	const internalLinks = Number(item?.internalLinks);
	const externalLinks = Number(item?.externalLinks);
	const nofollowLinks = Number(item?.nofollowLinks);
	const schemaEnabled = item?.schemaEnabled;
	const schemaType = String(item?.schemaType || '').trim();
	const organizationSchema = item?.organizationSchema;
	const breadcrumbSchema = item?.breadcrumbSchema;
	const aiCanonicalSignals = item?.aiCanonicalSignals && typeof item.aiCanonicalSignals === 'object' ? item.aiCanonicalSignals : {};

	const unknown = { status: 'not scanned', result: __('Not available from source data', 'asneris-seo-toolkit'), details: __('No direct value found in diagnostics payload.', 'asneris-seo-toolkit') };
	const getAiCanonicalSignal = (canonicalField) => {
		const signal = aiCanonicalSignals?.[canonicalField];
		if (!signal || typeof signal !== 'object') {
			return null;
		}

		const normalizedStatus = String(signal?.canonical_status || signal?.status || '').toLowerCase();
		let status = 'not scanned';
		if (normalizedStatus === 'pass') {
			status = 'pass';
		} else if (normalizedStatus === 'warning' || normalizedStatus === 'warn') {
			status = 'warning';
		} else if (normalizedStatus === 'fail') {
			status = 'fail';
		}

		const result = signal?.result;
		const details = signal?.details;
			return {
			status,
			result: result === undefined || result === null || String(result).trim() === ''
				? __('Not checked', 'asneris-seo-toolkit')
				: String(result),
			details: details === undefined || details === null || String(details).trim() === ''
					? __('Source: diagnostics source', 'asneris-seo-toolkit')
				: String(details),
		};
	};

	if (fieldLabel === 'SEO Title') {
		return { status: toPresenceStatus(seoTitle), result: seoTitle ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'), details: seoTitle || __('Source: unifiedData.raw.metaTitle', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'SEO Title Length') {
		const length = resolveNumericLength(item?.metaTitleLength, item?.titleLength, item?.effectiveTitleLength, seoTitle.length) || 0;
		return {
			status: length >= 30 && length <= 60 ? 'pass' : (length > 0 ? 'warning' : 'fail'),
			result: length > 0 ? String(length) : __('Not found', 'asneris-seo-toolkit'),
			details: __('Source: unifiedData.raw.metaTitleLength (fallback: titleLength / seoTitle text length)', 'asneris-seo-toolkit'),
		};
	}
	if (fieldLabel === 'Meta Description') {
		return { status: toPresenceStatus(seoDescription), result: seoDescription ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'), details: seoDescription || __('Source: unifiedData.raw.metaDescription', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Meta Description Length') {
		const length = resolveNumericLength(item?.descriptionLength, item?.effectiveDescriptionLength, seoDescription.length) || 0;
		return {
			status: length >= 120 && length <= 160 ? 'pass' : (length > 0 ? 'warning' : 'fail'),
			result: length > 0 ? String(length) : __('Not found', 'asneris-seo-toolkit'),
			details: __('Source: unifiedData.raw.descriptionLength/effectiveDescriptionLength (fallback: meta description text length)', 'asneris-seo-toolkit'),
		};
	}
	if (fieldLabel === 'Google Preview') {
		const hasPreview = Boolean(seoTitle || seoDescription || item?.title);
		return { status: toPresenceStatus(hasPreview), result: hasPreview ? __('Generated', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'), details: __('Source: title + meta description fields', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Open Graph Title') {
		return { status: toPresenceStatus(ogTitle), result: ogTitle ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'), details: ogTitle || __('Source: unifiedData.raw.ogTitle', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Open Graph Description') {
		return { status: toPresenceStatus(ogDescription), result: ogDescription ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'), details: ogDescription || __('Source: unifiedData.raw.ogDescription', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Open Graph Image') {
		return { status: toPresenceStatus(ogImage), result: ogImage ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'), details: ogImage || __('Source: unifiedData.raw.ogImage', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Twitter Card') {
		return { status: ogTitle || ogDescription || ogImage ? 'warning' : 'not scanned', result: ogTitle || ogDescription || ogImage ? __('Derived from OG fields', 'asneris-seo-toolkit') : __('Not available', 'asneris-seo-toolkit'), details: __('No dedicated twitter-card source field in current payload.', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Canonical') {
		const hasCanonical = typeof item?.hasCanonical === 'boolean' ? item.hasCanonical : Boolean(canonical);
		return { status: toPresenceStatus(hasCanonical), result: hasCanonical ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'), details: canonical || __('Source: unifiedData.raw.hasCanonical', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Canonical Exists') {
		const hasCanonical = typeof item?.hasCanonical === 'boolean' ? item.hasCanonical : Boolean(canonical);
		return {
			status: toPresenceStatus(hasCanonical),
			result: hasCanonical ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'),
			details: canonical || __('Source: unifiedData.raw.hasCanonical', 'asneris-seo-toolkit'),
		};
	}
	if (fieldLabel === 'Canonical Valid URL') {
		const valid = /^https?:\/\//i.test(canonical);
		return canonical
			? { status: valid ? 'pass' : 'warning', result: valid ? __('Valid URL', 'asneris-seo-toolkit') : __('Invalid URL', 'asneris-seo-toolkit'), details: canonical }
			: unknown;
	}
	if (fieldLabel === 'Self Canonical') {
		if (!canonical || !pageUrl) {
			return unknown;
		}
		const self = canonical.replace(/\/$/, '') === pageUrl.replace(/\/$/, '');
		return { status: self ? 'pass' : 'warning', result: self ? __('Yes', 'asneris-seo-toolkit') : __('No', 'asneris-seo-toolkit'), details: `${ canonical } -> ${ pageUrl }` };
	}
	if (fieldLabel === 'Robots Meta') {
		return robotsIndex || robotsFollow
			? { status: 'pass', result: `${ robotsIndex || 'index' }/${ robotsFollow || 'follow' }`, details: __('Source: unified robots fields', 'asneris-seo-toolkit') }
			: unknown;
	}
	if (fieldLabel === 'HTTP Status') {
		if (!Number.isFinite(httpStatus) || httpStatus <= 0) {
			return unknown;
		}
		const status = httpStatus >= 200 && httpStatus < 300 ? 'pass' : (httpStatus >= 300 && httpStatus < 400 ? 'warning' : 'fail');
		return { status, result: `${ httpStatus }`, details: __('Source: unifiedData.raw.httpStatus', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'X-Robots-Tag') {
		if (!xRobotsTag) {
			return {
				status: 'warning',
				result: __('Not detected', 'asneris-seo-toolkit'),
				details: __('No X-Robots-Tag header value in source payload.', 'asneris-seo-toolkit'),
			};
		}

		const lower = xRobotsTag.toLowerCase();
		const status = /noindex|none/.test(lower) ? 'fail' : 'pass';
		return { status, result: xRobotsTag, details: __('Source: unifiedData.raw.xRobotsTag', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'H1 Presence') {
		if (!Number.isFinite(h1Count)) {
			return unknown;
		}
		return { status: h1Count >= 1 ? 'pass' : 'warning', result: h1Count >= 1 ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'), details: `${ h1Count }` };
	}
	if (fieldLabel === 'H1 Heading') {
		if (!Number.isFinite(h1Count)) {
			return unknown;
		}
		return { status: h1Count >= 1 ? 'pass' : 'warning', result: h1Count >= 1 ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'), details: `${ h1Count }` };
	}
	if (fieldLabel === 'Multiple H1') {
		if (!Number.isFinite(h1Count)) {
			return unknown;
		}
		return { status: h1Count <= 1 ? 'pass' : 'warning', result: h1Count > 1 ? __('Yes', 'asneris-seo-toolkit') : __('No', 'asneris-seo-toolkit'), details: `${ h1Count }` };
	}
	if (fieldLabel === 'Heading Structure' || fieldLabel === 'Heading Hierarchy') {
		if (!Number.isFinite(h1Count) && !Number.isFinite(h2Count)) {
			return unknown;
		}
		const pass = Number.isFinite(h1Count) && h1Count === 1 && Number.isFinite(h2Count) && h2Count >= 1;
		return { status: pass ? 'pass' : 'warning', result: pass ? __('Valid', 'asneris-seo-toolkit') : __('Needs review', 'asneris-seo-toolkit'), details: `h1=${ Number.isFinite(h1Count) ? h1Count : '-' }, h2=${ Number.isFinite(h2Count) ? h2Count : '-' }` };
	}
	if (fieldLabel === 'Content Depth (Word Count)') {
		if (!Number.isFinite(wordCount)) {
			return unknown;
		}
		return { status: wordCount >= 300 ? 'pass' : 'warning', result: `${ wordCount }`, details: __('Source: unifiedData.raw.contentWords', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Content Present') {
		if (!Number.isFinite(wordCount)) {
			return unknown;
		}
		return { status: wordCount > 0 ? 'pass' : 'warning', result: wordCount > 0 ? __('Yes', 'asneris-seo-toolkit') : __('No', 'asneris-seo-toolkit'), details: `${ wordCount }` };
	}
	if (fieldLabel === 'Readability') {
		if (!Number.isFinite(wordCount)) {
			return unknown;
		}

		const status = wordCount >= 300 ? 'pass' : 'warning';
		return {
			status,
			result: status === 'pass' ? __('Readable', 'asneris-seo-toolkit') : __('Needs improvement', 'asneris-seo-toolkit'),
			details: __('Derived from available content-depth signal.', 'asneris-seo-toolkit'),
		};
	}
	if (fieldLabel === 'Content Quality') {
		if (!Number.isFinite(wordCount)) {
			return unknown;
		}

		const status = wordCount >= 300 ? 'pass' : 'warning';
		return {
			status,
			result: status === 'pass' ? __('Good', 'asneris-seo-toolkit') : __('Needs improvement', 'asneris-seo-toolkit'),
			details: `${ wordCount } ${ __('words', 'asneris-seo-toolkit') }`,
		};
	}
	if (fieldLabel === 'Images Found') {
		if (!Number.isFinite(imageCount)) {
			return unknown;
		}
		return { status: imageCount > 0 ? 'pass' : 'warning', result: `${ imageCount }`, details: __('Source: unifiedData.raw.imageCount', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Image ALT Coverage') {
		if (!Number.isFinite(imageCount) || imageCount <= 0) {
			return unknown;
		}
		const miss = Number.isFinite(missingAlt) ? missingAlt : 0;
		const pct = Math.max(0, Math.min(100, Math.round(((imageCount - miss) / imageCount) * 100)));
		return { status: pct >= 80 ? 'pass' : 'warning', result: `${ pct }%`, details: `${ imageCount - miss }/${ imageCount }` };
	}
	if (fieldLabel === 'Missing ALT') {
		if (!Number.isFinite(missingAlt)) {
			return unknown;
		}
		return { status: missingAlt === 0 ? 'pass' : 'warning', result: `${ missingAlt }`, details: __('Source: unifiedData.raw.imagesMissingAlt', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Empty ALT') {
		if (!Number.isFinite(emptyAlt)) {
			return unknown;
		}
		return { status: emptyAlt === 0 ? 'pass' : 'warning', result: `${ emptyAlt }`, details: __('Source: unifiedData.raw.imagesEmptyAlt', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Featured Image') {
		if (typeof featuredImage === 'boolean') {
			return {
				status: featuredImage ? 'pass' : 'warning',
				result: featuredImage ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'),
				details: __('Source: unifiedData.raw.featuredImage', 'asneris-seo-toolkit'),
			};
		}

		if (!Number.isFinite(imageCount)) {
			return unknown;
		}
		return { status: imageCount > 0 ? 'pass' : 'warning', result: imageCount > 0 ? __('Likely available', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'), details: __('Derived from image count.', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Internal Links') {
		if (!Number.isFinite(internalLinks)) {
			return unknown;
		}
		return { status: internalLinks > 0 ? 'pass' : 'warning', result: `${ internalLinks }`, details: __('Source: unifiedData.raw.internalLinks', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'External Links') {
		if (!Number.isFinite(externalLinks)) {
			return unknown;
		}
		return { status: externalLinks > 0 ? 'pass' : 'warning', result: `${ externalLinks }`, details: __('Source: unifiedData.raw.externalLinks', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Nofollow Links') {
		if (!Number.isFinite(nofollowLinks)) {
			return unknown;
		}
		return { status: 'pass', result: `${ nofollowLinks }`, details: __('Source: unifiedData.raw.nofollowLinks', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Structured Data Present') {
		if (typeof schemaEnabled === 'boolean') {
			return { status: schemaEnabled ? 'pass' : 'warning', result: schemaEnabled ? __('Yes', 'asneris-seo-toolkit') : __('No', 'asneris-seo-toolkit'), details: __('Source: unifiedData.raw.schemaEnabled', 'asneris-seo-toolkit') };
		}

		const inferredPresent = Boolean(schemaType) || (Number.isFinite(faqCount) && faqCount > 0);
		if (!inferredPresent) {
			return unknown;
		}

		return {
			status: 'pass',
			result: __('Yes', 'asneris-seo-toolkit'),
			details: __('Derived from schema type or FAQ signal.', 'asneris-seo-toolkit'),
		};
	}
	if (fieldLabel === 'JSON-LD Valid') {
		if (typeof schemaEnabled !== 'boolean' && !schemaType) {
			return unknown;
		}

		const hasSchema = typeof schemaEnabled === 'boolean' ? schemaEnabled : Boolean(schemaType);
		return {
			status: hasSchema ? 'pass' : 'warning',
			result: hasSchema ? __('Valid', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'),
			details: __('Derived from schema-enabled/schema-type signals.', 'asneris-seo-toolkit'),
		};
	}
	if (fieldLabel === 'Primary Entity' || fieldLabel === 'Primary Schema') {
		return schemaType
			? { status: 'pass', result: schemaType, details: __('Source: unifiedData.raw.schemaType', 'asneris-seo-toolkit') }
			: unknown;
	}
	if (fieldLabel === 'Organization Schema') {
		if (typeof organizationSchema !== 'boolean') {
			return unknown;
		}

		return {
			status: organizationSchema ? 'pass' : 'warning',
			result: organizationSchema ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'),
			details: __('Source: unifiedData.raw.organizationSchema', 'asneris-seo-toolkit'),
		};
	}
	if (fieldLabel === 'Article Schema') {
		if (!schemaType) {
			return unknown;
		}

		const isArticle = /article|blogposting|newsarticle/i.test(schemaType);
		return {
			status: isArticle ? 'pass' : 'warning',
			result: isArticle ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'),
			details: __('Derived from unifiedData.raw.schemaType', 'asneris-seo-toolkit'),
		};
	}
	if (fieldLabel === 'FAQ Schema') {
		if (!Number.isFinite(faqCount) && !schemaType) {
			return unknown;
		}

		const hasFaqSchema = (Number.isFinite(faqCount) && faqCount > 0) || /faqpage/i.test(schemaType);
		return {
			status: hasFaqSchema ? 'pass' : 'warning',
			result: hasFaqSchema ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'),
			details: __('Source: unifiedData.raw.faqCount / unifiedData.raw.schemaType', 'asneris-seo-toolkit'),
		};
	}
	if (fieldLabel === 'Breadcrumb Schema') {
		if (typeof breadcrumbSchema !== 'boolean') {
			return unknown;
		}

		return {
			status: breadcrumbSchema ? 'pass' : 'warning',
			result: breadcrumbSchema ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'),
			details: __('Source: unifiedData.raw.breadcrumbSchema', 'asneris-seo-toolkit'),
		};
	}
	if (fieldLabel === 'FAQ Signals') {
		const signal = getAiCanonicalSignal('FAQ Signals');
		if (signal) {
			return signal;
		}

		if (!Number.isFinite(faqCount)) {
			return unknown;
		}
		return { status: faqCount > 0 ? 'pass' : 'warning', result: `${ faqCount }`, details: __('Source: unifiedData.raw.faqCount', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Internal References') {
		const signal = getAiCanonicalSignal('Internal References');
		if (signal) {
			return signal;
		}

		if (!Number.isFinite(internalLinks)) {
			return unknown;
		}
		return { status: internalLinks > 0 ? 'pass' : 'warning', result: `${ internalLinks }`, details: __('Derived from internal link count.', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Content Completeness') {
		const signal = getAiCanonicalSignal('Content Completeness');
		if (signal) {
			return signal;
		}

		if (!Number.isFinite(wordCount)) {
			return unknown;
		}
		return { status: wordCount >= 500 ? 'pass' : 'warning', result: wordCount >= 500 ? __('Good', 'asneris-seo-toolkit') : __('Needs more depth', 'asneris-seo-toolkit'), details: `${ wordCount } ${ __('words', 'asneris-seo-toolkit') }` };
	}
	if (fieldLabel === 'Topic Consistency') {
		const signal = getAiCanonicalSignal('Topic Consistency');
		return signal || unknown;
	}
	if (fieldLabel === 'Clear Page Purpose') {
		const signal = getAiCanonicalSignal('Clear Page Purpose');
		return signal || unknown;
	}
	if (fieldLabel === 'Summary Section') {
		const signal = getAiCanonicalSignal('Summary Section');
		return signal || unknown;
	}
	if (fieldLabel === 'Brand Mentions') {
		const signal = getAiCanonicalSignal('Brand Mentions');
		return signal || unknown;
	}
	if (fieldLabel === 'Product/Context Mentions') {
		const signal = getAiCanonicalSignal('Product/Context Mentions');
		return signal || unknown;
	}
	if (fieldLabel === 'Trust Signals') {
		const signal = getAiCanonicalSignal('Trust Signals');
		return signal || unknown;
	}
	if (fieldLabel === 'Table/List Detection') {
		const signal = getAiCanonicalSignal('Table/List Detection');
		return signal || unknown;
	}
	if (fieldLabel === 'Definition Content') {
		const signal = getAiCanonicalSignal('Definition Content');
		return signal || unknown;
	}
	if (fieldLabel === 'Language Declaration') {
		const signal = getAiCanonicalSignal('Language Declaration');
		return signal || unknown;
	}
	if (fieldLabel === 'Content Structure') {
		const clearPurpose = getAiCanonicalSignal('Clear Page Purpose');
		if (clearPurpose) {
			return clearPurpose;
		}

		const structureSignal = getAiCanonicalSignal('Table/List Detection');
		return structureSignal || unknown;
	}
	if (fieldLabel === 'Author Information') {
		const signal = getAiCanonicalSignal('Brand Mentions');
		return signal || unknown;
	}
	if (fieldLabel === 'Machine Readability') {
		const signal = getAiCanonicalSignal('Language Declaration');
		return signal || unknown;
	}

	return unknown;
};

const getDetailTabBaseline = (tabKey) => {
	if (tabKey === DETAIL_TAB_STRUCTURED_DATA) {
		return {
			checks: DISCOVERABILITY_EXPECTED_CHECKS.structuredData,
			sectionKey: 'schema',
		};
	}

	if (tabKey === DETAIL_TAB_LINKS) {
		return {
			checks: DISCOVERABILITY_EXPECTED_CHECKS.links,
			sectionKey: 'links',
		};
	}

	if (tabKey === DETAIL_TAB_IMAGES) {
		return {
			checks: DISCOVERABILITY_EXPECTED_CHECKS.images,
			sectionKey: 'quality',
		};
	}

	if (tabKey === DETAIL_TAB_AI_DISCOVERABILITY) {
		return {
			checks: DISCOVERABILITY_EXPECTED_CHECKS.ai,
			sectionKey: 'ai',
		};
	}

	return null;
};

const matchesDetailTabRow = (row, tabKey, options = {}) => {
	const patternKey = DETAIL_TAB_PATTERN_MAP[tabKey];
	if (!patternKey) {
		return true;
	}

	const label = String(row?.label || '').toLowerCase();
	const category = String(row?.category || '');
	const byCategory = DETAIL_TAB_CATEGORY_MAP[tabKey]
		? category === DETAIL_TAB_CATEGORY_MAP[tabKey]
		: false;
	const byPattern = DISCOVERABILITY_DETAIL_PATTERNS[patternKey]
		? DISCOVERABILITY_DETAIL_PATTERNS[patternKey].test(label)
		: false;

	if (tabKey === DETAIL_TAB_AI_DISCOVERABILITY && options.includeAiWordCount) {
		return byCategory || byPattern || /word count/.test(label);
	}

	return byCategory || byPattern;
};

const getRowsByDetailTab = (categorizedReport, tabKey, item = null) => {
	const normalizeDetailStatusForTab = (statusValue) => {
		const normalized = mapCheckStatus(statusValue);
		if (tabKey === DETAIL_TAB_CONTENT) {
			if (normalized === 'pass' || normalized === 'warning' || normalized === 'fail') {
				return normalized;
			}

			return 'not scanned';
		}

		return normalized === 'not_scanned' ? 'not scanned' : normalized;
	};

	const allRows = (categorizedReport?.sections || []).flatMap((section) =>
		(section.rows || []).map((row) => ({
			...row,
			sectionKey: section.key,
			label: String(row?.cells?.[0] || '').toLowerCase(),
			category: TAXONOMY_SECTION_KEYS.has(section.key) ? section.key : toCheckCategory(row?.cells?.[0]),
		}))
	).filter((row) => !/page fetch|data source|local fallback|final destination/.test(row.label));

	const ensureBaselineRows = (rows, expectedChecks, sectionKey) => {
		const existingLabels = new Set(rows.map((row) => String(row?.cells?.[0] || '').toLowerCase()));
		const missingRows = expectedChecks
			.filter((check) => !existingLabels.has(check.toLowerCase()))
			.map((check) => ({
				cells: [
					check,
					__('Not scanned', 'asneris-seo-toolkit'),
					__('Not checked', 'asneris-seo-toolkit'),
					__('This check is not available in the current scan output.', 'asneris-seo-toolkit'),
				],
				sectionKey,
				label: check.toLowerCase(),
				isBaseline: true,
			}));

		return [ ...rows, ...missingRows ];
	};

	const ensureProposedRows = (rows, currentTabKey, sourceItem) => {
		const proposedFields = PROPOSED_FIELDS_BY_TAB[currentTabKey] || [];
		if (proposedFields.length < 1) {
			return rows;
		}

		const nextRows = [ ...rows ];
		const sectionKey = DETAIL_TAB_SECTION_KEY_MAP[currentTabKey] || 'overview';
		const canonicalMapKey = DETAIL_TAB_TO_CANONICAL_MAP_KEY[currentTabKey] || '';
		proposedFields.forEach((fieldLabel) => {
			const derived = deriveProposedFieldFromSource(fieldLabel, sourceItem || {});
			const normalizedStatus = normalizeDetailStatusForTab(derived.status || 'not scanned');
			const canonicalMappedFields = getCanonicalRawFields(canonicalMapKey, fieldLabel);
			const mappedDetails = formatEvidenceFromFields(canonicalMappedFields, sourceItem || {}, {});
			const hasMappedEvidence = Array.isArray(canonicalMappedFields) && canonicalMappedFields.length > 0 && mappedDetails !== '-';
			const evidenceGatedStatus = (!hasMappedEvidence && normalizedStatus !== 'not scanned')
				? 'not scanned'
				: normalizedStatus;
			const resolvedDetails = buildTransparencyExplanation(
				fieldLabel,
				hasMappedEvidence ? mappedDetails : resolveCanonicalEvidenceGapMessage(canonicalMappedFields, mappedDetails),
				evidenceGatedStatus
			);
			const existing = findExistingProposedRow(nextRows, fieldLabel);
			if (existing) {
				const shouldOverrideExisting = isSharedCanonicalFieldLabel(fieldLabel) || (
					currentTabKey === DETAIL_TAB_AI_DISCOVERABILITY
					&& derived.status
					&& String(derived.status).toLowerCase() !== 'not scanned'
				);

				if (shouldOverrideExisting) {
					existing.cells = [
						fieldLabel,
						evidenceGatedStatus,
						evidenceGatedStatus === 'not scanned'
							? __('Not checked', 'asneris-seo-toolkit')
							: (derived.result || __('Not available from source data', 'asneris-seo-toolkit')),
						resolvedDetails,
					];
					existing.label = String(fieldLabel).toLowerCase();
				}

				return;
			}

			nextRows.push({
				cells: [
					fieldLabel,
					evidenceGatedStatus,
					evidenceGatedStatus === 'not scanned'
						? __('Not checked', 'asneris-seo-toolkit')
						: (derived.result || __('Not available from source data', 'asneris-seo-toolkit')),
					resolvedDetails,
				],
				sectionKey,
				label: String(fieldLabel).toLowerCase(),
				isBaseline: true,
			});
		});

		return nextRows;
	};

	let rows = allRows.filter((row) => matchesDetailTabRow(row, tabKey, { includeAiWordCount: true }));
	const baseline = getDetailTabBaseline(tabKey);
	if (baseline) {
		rows = ensureBaselineRows(rows, baseline.checks, baseline.sectionKey);
	}

	const proposedRows = ensureProposedRows(rows, tabKey, item || {});
	const getSnapshotTabIssueStatusByField = (sourceItem, currentTabKey, expectedFields = []) => {
		const canonicalMapKey = DETAIL_TAB_TO_CANONICAL_MAP_KEY[currentTabKey] || '';
		if (!canonicalMapKey) {
			return null;
		}

		const source = String(sourceItem?.source || '').trim().toLowerCase();
		const isSnapshotReview = source === 'snapshot' || source === 'snapshot-skip' || source === 'latest-fallback';
		if (!isSnapshotReview) {
			return null;
		}

		const tabIssueRecords = sourceItem?.tabIssueRecords && typeof sourceItem.tabIssueRecords === 'object'
			? sourceItem.tabIssueRecords
			: null;
		if (!tabIssueRecords || !Array.isArray(tabIssueRecords[canonicalMapKey])) {
			return null;
		}

		const toPriorityRank = (status) => {
			if (status === 'fail') {
				return 3;
			}
			if (status === 'warning') {
				return 2;
			}
			if (status === 'pass') {
				return 1;
			}

			return 0;
		};

		const statusByField = new Map();
		const records = tabIssueRecords[canonicalMapKey];
		(expectedFields || []).forEach((expectedFieldLabel) => {
			const normalizedExpected = normalizeFieldForRuleTab(currentTabKey, expectedFieldLabel);
			if (!normalizedExpected) {
				return;
			}

			const directStatuses = [];
			const canonicalStatuses = [];
			records.forEach((record) => {
				const canonicalField = String(record?.canonical_field || '').trim();
				if (!canonicalField) {
					return;
				}

				const directLabel = normalizeFieldForRuleTab(currentTabKey, canonicalField);
				const matchesDirect = directLabel === normalizedExpected;
				const matchesCanonical = doesCheckLabelMatchCanonicalField(canonicalMapKey, canonicalField, normalizedExpected);
				if (!matchesDirect && !matchesCanonical) {
					return;
				}

				const normalizedStatus = normalizeDetailStatusForTab(record?.canonical_status || 'not scanned');
				if (normalizedStatus !== 'pass' && normalizedStatus !== 'warning' && normalizedStatus !== 'fail') {
					return;
				}

				if (matchesDirect) {
					directStatuses.push(normalizedStatus);
				} else {
					canonicalStatuses.push(normalizedStatus);
				}
			});

			const pool = directStatuses.length > 0 ? directStatuses : canonicalStatuses;
			let bestStatus = '';
			pool.forEach((status) => {
				if (toPriorityRank(status) > toPriorityRank(bestStatus)) {
					bestStatus = status;
				}
			});

			if (bestStatus) {
				statusByField.set(normalizedExpected, bestStatus);
			}
		});

		return statusByField;
	};

	if (RULE_FIELDS_BY_TAB[tabKey]) {
		const allowedFields = RULE_FIELDS_BY_TAB[tabKey];
		const snapshotStatusByField = getSnapshotTabIssueStatusByField(item || {}, tabKey, allowedFields);
		const normalizedRows = proposedRows
				.map((row) => {
					const normalizedLabel = normalizeFieldForRuleTab(tabKey, String(row?.cells?.[0] || ''));
					if (!allowedFields.includes(normalizedLabel)) {
						return null;
					}

					const snapshotStatus = snapshotStatusByField?.get(normalizedLabel);
					const resolvedStatus = snapshotStatusByField
						? (snapshotStatus || 'pass')
						: normalizeDetailStatusForTab(row?.cells?.[1] || 'not scanned');

					return {
						...row,
						cells: [
							normalizedLabel,
							resolvedStatus,
							row?.cells?.[2] || __('Not checked', 'asneris-seo-toolkit'),
							row?.cells?.[3] || resolveCanonicalEvidenceGapMessage([], '-'),
						],
						label: normalizedLabel.toLowerCase(),
					};
				})
				.filter(Boolean);

			const byLabel = new Map();
			normalizedRows.forEach((row) => {
				const label = String(row?.cells?.[0] || '');
				if (!byLabel.has(label)) {
					byLabel.set(label, row);
				}
			});

		return allowedFields.map((label) => {
			if (byLabel.has(label)) {
				return byLabel.get(label);
			}

			const snapshotStatus = snapshotStatusByField?.get(label);

			return {
				cells: [
					label,
					snapshotStatus || normalizeDetailStatusForTab('not scanned'),
					snapshotStatus ? __('Captured in tab snapshot', 'asneris-seo-toolkit') : __('Not checked', 'asneris-seo-toolkit'),
					snapshotStatus ? __('Source: tabIssueRecords snapshot', 'asneris-seo-toolkit') : resolveCanonicalEvidenceGapMessage([], '-'),
				],
				sectionKey: DETAIL_TAB_SECTION_KEY_MAP[tabKey] || 'overview',
				label: label.toLowerCase(),
				isBaseline: true,
			};
		});
	}

	return proposedRows;
};

const collapseCanonicalStatus = (statuses = []) => {
    const normalized = (Array.isArray(statuses) ? statuses : [])
        .map((value) => mapCheckStatus(value || 'not scanned'))
        .filter((status) => status !== 'not_scanned');

    if (normalized.length < 1) {
        return 'not_scanned';
    }

    if (normalized.every((status) => status === 'pass')) {
        return 'pass';
    }

    if (normalized.every((status) => status === 'fail')) {
        return 'fail';
    }

    return 'warning';
};

const getPreferredHistoryIssueRecords = (historyItem, canonicalMapKey) => {
	const tabIssueRecords = historyItem?.tabIssueRecords && typeof historyItem.tabIssueRecords === 'object'
		? historyItem.tabIssueRecords
		: null;

	if (tabIssueRecords && Array.isArray(tabIssueRecords[canonicalMapKey])) {
		return tabIssueRecords[canonicalMapKey];
	}

	const canonicalIssueRecords = Array.isArray(historyItem?.overviewIssueRecords)
		? historyItem.overviewIssueRecords
		: [];
	const aiIssueRecords = Array.isArray(historyItem?.aiIssueRecords)
		? historyItem.aiIssueRecords
		: [];

	if (canonicalMapKey === 'aiDiscoverability') {
		return aiIssueRecords.length > 0 ? aiIssueRecords : canonicalIssueRecords;
	}

	return canonicalIssueRecords;
};

const buildCanonicalFieldStatusMap = (issueRecords = []) => {
	return (Array.isArray(issueRecords) ? issueRecords : []).reduce((acc, record) => {
		const field = String(record?.canonical_field || '').trim().toLowerCase();
		const status = mapCheckStatus(record?.canonical_status || 'warning');
		if (!field || (status !== 'warning' && status !== 'fail')) {
			return acc;
		}

		if (status === 'fail') {
			acc[field] = 'fail';
			return acc;
		}

		if (!acc[field]) {
			acc[field] = 'warning';
		}

		return acc;
	}, {});
};

const getCanonicalHistoryStatusForField = (historyItem, detailTabKey, canonicalField) => {
	const canonicalMapKey = DETAIL_TAB_TO_CANONICAL_MAP_KEY[detailTabKey] || '';
	if (!canonicalMapKey) {
		return 'not_scanned';
	}

	const preferredIssueRecords = getPreferredHistoryIssueRecords(historyItem, canonicalMapKey);
	const statusByCanonicalField = buildCanonicalFieldStatusMap(preferredIssueRecords);
	const normalizedField = String(canonicalField || '').trim().toLowerCase();

	return statusByCanonicalField[normalizedField] || 'pass';
};

const buildCanonicalComparisonRowsFromSnapshot = (historyItem) => {
	return COMPARISON_CATEGORY_ORDER.flatMap((detailTabKey) => {
		const canonicalMapKey = DETAIL_TAB_TO_CANONICAL_MAP_KEY[detailTabKey] || '';
		const canonicalFields = getCanonicalFieldsByTab(canonicalMapKey);

		if (!Array.isArray(canonicalFields) || canonicalFields.length < 1) {
			return [];
		}

		return canonicalFields.map((canonicalField) => {
			const status = getCanonicalHistoryStatusForField(historyItem, detailTabKey, canonicalField);
			return {
				key: `${ detailTabKey }::${ canonicalField }`,
				label: canonicalField,
				category: detailTabKey,
				status,
				value: formatCheckStatusLabel(status),
			};
		});
	});
};

const buildCanonicalHistoryCounts = (historyItem, tabKey) => {
    const canonicalMapKey = DETAIL_TAB_TO_CANONICAL_MAP_KEY[tabKey] || '';
    const canonicalFields = getCanonicalFieldsByTab(canonicalMapKey);

    if (!Array.isArray(canonicalFields) || canonicalFields.length < 1) {
        return { pass: 0, warning: 0, fail: 0, total: 0, issues: 0 };
    }

	const preferredIssueRecords = getPreferredHistoryIssueRecords(historyItem, canonicalMapKey);
	const statusByCanonicalField = buildCanonicalFieldStatusMap(preferredIssueRecords);
	const counts = canonicalFields.reduce((acc, canonicalField) => {
		const normalizedField = String(canonicalField || '').trim().toLowerCase();
		const status = statusByCanonicalField[normalizedField] || 'pass';

		if (status === 'fail') {
			acc.fail += 1;
		} else if (status === 'warning') {
			acc.warning += 1;
		}

		return acc;
	}, { warning: 0, fail: 0 });

	const total = canonicalFields.length;
	const issues = counts.warning + counts.fail;
	return {
		pass: Math.max(0, total - issues),
		warning: counts.warning,
		fail: counts.fail,
		total,
		issues,
	};
};

const buildCanonicalHistoryUxMeta = (historyItem, tabKey) => {
	const canonicalMapKey = DETAIL_TAB_TO_CANONICAL_MAP_KEY[tabKey] || '';
	const canonicalFields = getCanonicalFieldsByTab(canonicalMapKey);
	const preferredIssueRecords = getPreferredHistoryIssueRecords(historyItem, canonicalMapKey);
	const expectedTotal = Array.isArray(canonicalFields) ? canonicalFields.length : 0;

	if (expectedTotal < 1) {
		return {
			coverage: '-/-',
			coverageTone: 'neutral',
			sourceLabel: __('Unknown', 'asneris-seo-toolkit'),
		};
	}

	const presentSet = new Set(
		(Array.isArray(preferredIssueRecords) ? preferredIssueRecords : [])
			.map((record) => String(record?.canonical_field || '').trim().toLowerCase())
			.filter(Boolean)
	);

	const covered = canonicalFields.reduce((count, fieldLabel) => {
		const normalized = String(fieldLabel || '').trim().toLowerCase();
		return presentSet.has(normalized) ? count + 1 : count;
	}, 0);

	const hasDirectTabBucket = Boolean(
		historyItem?.tabIssueRecords &&
		typeof historyItem.tabIssueRecords === 'object' &&
		Array.isArray(historyItem.tabIssueRecords[canonicalMapKey])
	);

	const coverageTone = covered >= expectedTotal
		? 'success'
		: (covered > 0 ? 'warning' : 'fail');

	return {
		coverage: `${ covered }/${ expectedTotal }`,
		coverageTone,
		sourceLabel: hasDirectTabBucket
			? __('Tab Snapshot', 'asneris-seo-toolkit')
			: __('Legacy Fallback', 'asneris-seo-toolkit'),
	};
};

const getHistoryChecksByDetailTab = (checks, tabKey) => {
	const rows = (Array.isArray(checks) ? checks : []).map((check) => {
		const category = resolveHistoryDetailTabKeyForCheck(check);
		return {
			category,
			label: String(check?.label || '').toLowerCase(),
			status: mapCheckStatus(check?.status),
		};
	}).filter((row) => !/page fetch|data source|local fallback|final destination/.test(row.label));

	if (tabKey === DETAIL_TAB_OVERVIEW) {
		return rows;
	}

	return rows.filter((row) => row.category === tabKey);
};

const countStatus = (rows) => rows.reduce((acc, row) => {
	const status = String(row?.cells?.[1] || '').toLowerCase();
	if (status === 'pass') {
		acc.pass += 1;
	} else if (status === 'warning') {
		acc.warning += 1;
	} else if (status === 'fail') {
		acc.fail += 1;
	}
	return acc;
}, { pass: 0, warning: 0, fail: 0 });

const toStatusTone = (counts) => {
	if (counts.fail > 0) {
		return 'fail';
	}
	if (counts.warning > 0) {
		return 'warning';
	}
	return 'success';
};

const buildTabCardModel = (tabKey, item, rows) => {
	const counts = countStatus(rows);
	const tone = toStatusTone(counts);
	const seo = deriveSeoScore(item);
	const backendSeoScoreMessage = typeof item?.seoScoreMessage === 'string' ? item.seoScoreMessage.trim() : '';
	const rowByLabel = (pattern) => rows.find((row) => pattern.test(String(row?.cells?.[0] || '')));
	const rowResult = (pattern, fallback = '-') => {
		const row = rowByLabel(pattern);
		if (!row) {
			return fallback;
		}
		const value = row?.cells?.[2];
		if (value === undefined || value === null || String(value).trim() === '') {
			return fallback;
		}
		return String(value);
	};
	const rowStatus = (pattern, fallback = 'not_scanned') => {
		const row = rowByLabel(pattern);
		if (!row) {
			return fallback;
		}
		return mapCheckStatus(row?.cells?.[1] || 'not scanned');
	};
	const sourceItem = item || {};
	const scannedStatusByField = (fieldLabel) => {
		const matchedRow = findExistingProposedRow(rows, fieldLabel);
		if (!matchedRow) {
			return null;
		}

		const normalized = mapCheckStatus(matchedRow?.cells?.[1] || 'not scanned');
		return normalized === 'not_scanned' ? null : normalized;
	};
	const canonicalStatusByField = (fieldLabel) => {
		if (!isSharedCanonicalFieldLabel(fieldLabel)) {
			const scannedStatus = scannedStatusByField(fieldLabel);
			if (scannedStatus) {
				return scannedStatus;
			}
		}

		const canonicalMapKey = getCanonicalMapKeyByFieldLabel(fieldLabel) || (DETAIL_TAB_TO_CANONICAL_MAP_KEY[tabKey] || '');
		const canonicalMappedFields = getCanonicalRawFields(canonicalMapKey, fieldLabel);
		const mappedDetails = formatEvidenceFromFields(canonicalMappedFields, sourceItem, {});
		const hasMappedEvidence = Array.isArray(canonicalMappedFields) && canonicalMappedFields.length > 0 && mappedDetails !== '-';
		const derivedStatus = mapCheckStatus(deriveProposedFieldFromSource(fieldLabel, sourceItem)?.status || 'not scanned');

		if (!hasMappedEvidence && derivedStatus !== 'not_scanned') {
			return 'not_scanned';
		}

		return derivedStatus;
	};
	const normalizeScoreStatus = (statusValue) => mapCheckStatus(statusValue || 'not scanned');
	const scoreByStatus = (statusValue, maxPoints) => {
		const status = normalizeScoreStatus(statusValue);
		if (status === 'pass') {
			return maxPoints;
		}
		if (status === 'warning') {
			return Math.round(maxPoints * 0.5);
		}
		if (status === 'fail') {
			return 0;
		}

		return null;
	};
	const mergeRawStatusesToCanonicalStatus = (rawStatuses = []) => {
		const normalized = rawStatuses
			.map((statusValue) => normalizeScoreStatus(statusValue))
			.filter((status) => status !== 'not_scanned');

		if (normalized.length < 1) {
			return 'not_scanned';
		}

		// Strict pass: any warning/fail prevents pass.
		if (normalized.every((status) => status === 'pass')) {
			return 'pass';
		}

		// Any fail keeps it warning unless all are fail.
		if (normalized.every((status) => status === 'fail')) {
			return 'fail';
		}

		return 'warning';
	};
	const buildCanonicalScoreModel = (signalDefinitions = []) => {
		const scoredSignals = signalDefinitions.map((signal) => ({
			...signal,
			points: scoreByStatus(signal.status, signal.maxPoints),
		}));

		const availableSignals = scoredSignals.filter((signal) => signal.points !== null && Number.isFinite(signal.maxPoints));
		if (availableSignals.length < 1) {
			return {
				score: 0,
				softFailure: true,
			};
		}

		const earnedPoints = availableSignals.reduce((total, signal) => total + signal.points, 0);
		const maxAvailablePoints = availableSignals.reduce((total, signal) => total + signal.maxPoints, 0);
		const score = maxAvailablePoints > 0 ? Math.round((earnedPoints / maxAvailablePoints) * 100) : 0;

		return {
			score: Math.max(0, Math.min(100, score)),
			softFailure: false,
		};
	};
	const deriveSearchAppearanceScoreModel = () => {
		const signalDefinitions = [
			{ key: 'seoTitle', status: canonicalStatusByField('SEO Title'), maxPoints: 20 },
			{ key: 'metaDescription', status: canonicalStatusByField('Meta Description'), maxPoints: 20 },
			{ key: 'seoTitleLength', status: canonicalStatusByField('SEO Title Length'), maxPoints: 20 },
			{ key: 'metaDescriptionLength', status: canonicalStatusByField('Meta Description Length'), maxPoints: 20 },
			{ key: 'canonical', status: canonicalStatusByField('Canonical'), maxPoints: 20 },
		];

		return buildCanonicalScoreModel(signalDefinitions);
	};
	const deriveIndexabilityScoreModel = () => {
		const signalDefinitions = [
			{ key: 'httpStatus', status: canonicalStatusByField('HTTP Status'), maxPoints: 20 },
			{ key: 'robotsMeta', status: canonicalStatusByField('Robots Meta'), maxPoints: 20 },
			{ key: 'canonicalExists', status: canonicalStatusByField('Canonical Exists'), maxPoints: 15 },
			{ key: 'selfCanonical', status: canonicalStatusByField('Self Canonical'), maxPoints: 15 },
			{ key: 'canonicalValidUrl', status: canonicalStatusByField('Canonical Valid URL'), maxPoints: 15 },
			{ key: 'xRobotsTag', status: canonicalStatusByField('X-Robots-Tag'), maxPoints: 15 },
		];

		return buildCanonicalScoreModel(signalDefinitions);
	};
	const deriveContentQualityScoreModel = () => {
		const sourceStatus = (fieldLabel) => canonicalStatusByField(fieldLabel);

		const seoTitleStatus = mergeRawStatusesToCanonicalStatus([
			sourceStatus('SEO Title'),
			sourceStatus('SEO Title Length'),
		]);
		const metaDescriptionStatus = mergeRawStatusesToCanonicalStatus([
			sourceStatus('Meta Description'),
			sourceStatus('Meta Description Length'),
		]);
		const h1HeadingStatus = mergeRawStatusesToCanonicalStatus([
			sourceStatus('H1 Presence'),
			sourceStatus('Multiple H1'),
		]);
		const contentQualityStatus = mergeRawStatusesToCanonicalStatus([
			sourceStatus('Heading Structure'),
			sourceStatus('Heading Hierarchy'),
			sourceStatus('Content Depth (Word Count)'),
			sourceStatus('Content Present'),
			sourceStatus('Readability'),
		]);
		const signalDefinitions = [
			{ key: 'seoTitle', status: seoTitleStatus, maxPoints: 25 },
			{ key: 'metaDescription', status: metaDescriptionStatus, maxPoints: 25 },
			{ key: 'h1Heading', status: h1HeadingStatus, maxPoints: 25 },
			{ key: 'contentQuality', status: contentQualityStatus, maxPoints: 25 },
		];

		const model = buildCanonicalScoreModel(signalDefinitions);
		return {
			...model,
			canonicalStatuses: {
				seoTitle: seoTitleStatus,
				metaDescription: metaDescriptionStatus,
				h1Heading: h1HeadingStatus,
				contentQuality: contentQualityStatus,
			},
		};
	};
	const deriveLinksScoreModel = () => {
		const signalDefinitions = [
			{ key: 'internalLinks', status: canonicalStatusByField('Internal Links'), maxPoints: 50 },
			{ key: 'externalLinks', status: canonicalStatusByField('External Links'), maxPoints: 30 },
			{ key: 'nofollowLinks', status: canonicalStatusByField('Nofollow Links'), maxPoints: 20 },
		];

		return buildCanonicalScoreModel(signalDefinitions);
	};
	const deriveImagesScoreModel = () => {
		const signalDefinitions = [
			{ key: 'imagesFound', status: canonicalStatusByField('Images Found'), maxPoints: 30 },
			{ key: 'missingAlt', status: canonicalStatusByField('Missing ALT'), maxPoints: 30 },
			{ key: 'emptyAlt', status: canonicalStatusByField('Empty ALT'), maxPoints: 20 },
			{ key: 'featuredImage', status: canonicalStatusByField('Featured Image'), maxPoints: 20 },
		];

		return buildCanonicalScoreModel(signalDefinitions);
	};
	const deriveStructuredDataScoreModel = () => {
		const signalDefinitions = [
			{ key: 'structuredDataPresent', status: canonicalStatusByField('Structured Data Present'), maxPoints: 20 },
			{ key: 'jsonLdValid', status: canonicalStatusByField('JSON-LD Valid'), maxPoints: 20 },
			{ key: 'organizationSchema', status: canonicalStatusByField('Organization Schema'), maxPoints: 15 },
			{ key: 'primarySchema', status: canonicalStatusByField('Primary Schema'), maxPoints: 15 },
			{ key: 'faqSchema', status: canonicalStatusByField('FAQ Schema'), maxPoints: 15 },
			{ key: 'breadcrumbSchema', status: canonicalStatusByField('Breadcrumb Schema'), maxPoints: 15 },
		];

		return buildCanonicalScoreModel(signalDefinitions);
	};
	const deriveAiDiscoverabilityScoreModel = () => {
		const pointsByAiStatus = (statusValue, passPoints, partialPoints) => {
			const normalized = normalizeScoreStatus(statusValue);
			if (normalized === 'pass') {
				return passPoints;
			}

			if (normalized === 'warning' || normalized === 'fail') {
				return partialPoints;
			}

			return 0;
		};

		const clearPagePurposeStatus = canonicalStatusByField('Clear Page Purpose');
		const tableListDetectionStatus = canonicalStatusByField('Table/List Detection');
		const topicConsistencyStatus = canonicalStatusByField('Topic Consistency');
		const summarySectionStatus = canonicalStatusByField('Summary Section');
		const brandMentionsStatus = canonicalStatusByField('Brand Mentions');
		const productContextMentionsStatus = canonicalStatusByField('Product/Context Mentions');
		const faqSignalsStatus = canonicalStatusByField('FAQ Signals');
		const definitionContentStatus = canonicalStatusByField('Definition Content');
		const trustSignalsStatus = canonicalStatusByField('Trust Signals');
		const contentCompletenessStatus = canonicalStatusByField('Content Completeness');

		const weightedChecks = [
			pointsByAiStatus(clearPagePurposeStatus, 8, 3),
			pointsByAiStatus(clearPagePurposeStatus, 8, 4),
			pointsByAiStatus(clearPagePurposeStatus, 8, 4),
			pointsByAiStatus(tableListDetectionStatus, 5, 2),
			pointsByAiStatus(tableListDetectionStatus, 4, 2),
			pointsByAiStatus(clearPagePurposeStatus, 7, 3),
			pointsByAiStatus(topicConsistencyStatus, 7, 3),
			pointsByAiStatus(summarySectionStatus, 5, 2),
			pointsByAiStatus(clearPagePurposeStatus, 6, 3),
			pointsByAiStatus(brandMentionsStatus, 6, 2),
			pointsByAiStatus(productContextMentionsStatus, 5, 2),
			pointsByAiStatus(faqSignalsStatus, 7, 2),
			pointsByAiStatus(definitionContentStatus, 7, 3),
			pointsByAiStatus(trustSignalsStatus, 10, 4),
			pointsByAiStatus(contentCompletenessStatus, 15, 8),
		];

		const maxPoints = 108;
		const earnedPoints = weightedChecks.reduce((total, points) => total + points, 0);
		const hasAnySignal = [
			clearPagePurposeStatus,
			tableListDetectionStatus,
			topicConsistencyStatus,
			summarySectionStatus,
			brandMentionsStatus,
			productContextMentionsStatus,
			faqSignalsStatus,
			definitionContentStatus,
			trustSignalsStatus,
			contentCompletenessStatus,
		].some((status) => normalizeScoreStatus(status) !== 'not_scanned');

		return {
			score: Math.max(0, Math.min(100, Math.round((earnedPoints / maxPoints) * 100))),
			softFailure: !hasAnySignal,
		};
	};
	const isPassStatus = (status) => status === 'pass';
	const scannedStatuses = rows
		.map((row) => mapCheckStatus(row?.cells?.[1] || 'not scanned'))
		.filter((status) => status !== 'not_scanned');
	const countMatchedStatuses = (statuses) => statuses.filter((status) => isPassStatus(status)).length;
	const toBinaryStatusLabel = (status) => isPassStatus(status)
		? __('Pass', 'asneris-seo-toolkit')
		: __('Fail', 'asneris-seo-toolkit');
	const formatTotalCheckMatchValue = (totalCount) => {
		const matchedCount = countMatchedStatuses(scannedStatuses);
		return scannedStatuses.length < totalCount
			? `${ matchedCount } / ${ totalCount } (${ scannedStatuses.length } ${ __('scanned', 'asneris-seo-toolkit') })`
			: `${ matchedCount } / ${ totalCount }`;
	};
	const formatStrictTotalCheckMatchValue = (totalCount) => {
		const matchedCount = countMatchedStatuses(scannedStatuses);
		return `${ matchedCount } / ${ totalCount }`;
	};
	const resolveTabScoreMessage = (isSoftFailure, options = {}) => {
		const includeBackendMessage = options?.includeBackendMessage === true;
		if (includeBackendMessage && backendSeoScoreMessage) {
			return backendSeoScoreMessage;
		}

		return isSoftFailure && scannedStatuses.length > 0
			? SOFT_FAILURE_SCORE_MESSAGE
			: null;
	};
	const formatPrimaryTotalCheckMatchValue = (detailTabKey) => {
		const primaryFields = PRIMARY_LIST_FIELDS_BY_TAB[detailTabKey];
		if (!Array.isArray(primaryFields) || primaryFields.length < 1) {
			return formatTotalCheckMatchValue(rows.length);
		}

		const primaryStatuses = primaryFields.map((fieldLabel) => {
			const derived = deriveProposedFieldFromSource(fieldLabel, item || {});
			return String(derived?.status || 'not scanned').toLowerCase();
		});
		const primaryScannedStatuses = primaryStatuses.filter((status) => status !== 'not scanned');
		const primaryMatchCount = countMatchedStatuses(primaryScannedStatuses);
		const primaryTotal = primaryFields.length;

		return primaryScannedStatuses.length < primaryTotal
			? `${ primaryMatchCount } / ${ primaryTotal } (${ primaryScannedStatuses.length } ${ __('scanned', 'asneris-seo-toolkit') })`
			: `${ primaryMatchCount } / ${ primaryTotal }`;
	};

	if (tabKey === DETAIL_TAB_INDEXABILITY) {
		const indexabilityScoreModel = deriveIndexabilityScoreModel();
		const indexabilitySoftFailure = indexabilityScoreModel.softFailure;
		const tableTotalChecks = rows.length;
		const indexabilityStatuses = [
			canonicalStatusByField('HTTP Status'),
			canonicalStatusByField('Robots Meta'),
			canonicalStatusByField('Canonical Exists'),
			canonicalStatusByField('Self Canonical'),
			canonicalStatusByField('Canonical Valid URL'),
			canonicalStatusByField('X-Robots-Tag'),
		];
		const detailHighlights = [
			{ label: __('HTTP Status', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(indexabilityStatuses[0]) },
			{ label: __('Robots Meta', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(indexabilityStatuses[1]) },
			{ label: __('Canonical Exists', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(indexabilityStatuses[2]) },
			{ label: __('Self Canonical', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(indexabilityStatuses[3]) },
			{ label: __('Canonical Valid URL', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(indexabilityStatuses[4]) },
			{ label: __('X-Robots-Tag', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(indexabilityStatuses[5]) },
		];
		const keyFieldStatuses = indexabilityStatuses;
		const keyFieldMatchCount = countMatchedStatuses(keyFieldStatuses);
		const summaryHighlights = [
			{ label: __('Key Fields Match Count', 'asneris-seo-toolkit'), value: `${ keyFieldMatchCount } / ${ detailHighlights.length }`, kind: 'match-count' },
			{ label: __('Total Check Match Count', 'asneris-seo-toolkit'), value: formatStrictTotalCheckMatchValue(tableTotalChecks), kind: 'match-count' },
		];

		return {
			title: __('Indexability', 'asneris-seo-toolkit'),
			status: tone === 'success' ? __('Good', 'asneris-seo-toolkit') : __('Needs Work', 'asneris-seo-toolkit'),
			score: indexabilityScoreModel.score,
			scoreMessage: resolveTabScoreMessage(indexabilitySoftFailure),
			summary: (item?.robotsIndex || 'index') === 'index'
				? __('This page appears eligible for indexing based on current diagnostics.', 'asneris-seo-toolkit')
				: __('This page has indexability constraints that should be reviewed.', 'asneris-seo-toolkit'),
			detailsTitle: __('Indexability Details', 'asneris-seo-toolkit'),
				summaryHighlights,
				detailHighlights,
				highlights: summaryHighlights,
			note: __('This page is eligible for indexing and accessible to search engines.', 'asneris-seo-toolkit'),
			noteTone: 'success',
		};
	}

	if (tabKey === DETAIL_TAB_CONTENT) {
			const contentQualityScoreModel = deriveContentQualityScoreModel();
			const contentQualitySoftFailure = contentQualityScoreModel.softFailure;
			const tableTotalChecks = rows.length;
			const contentDetailStatuses = [
				{ label: __('SEO Title', 'asneris-seo-toolkit'), status: canonicalStatusByField('SEO Title') },
				{ label: __('SEO Title Length', 'asneris-seo-toolkit'), status: canonicalStatusByField('SEO Title Length') },
				{ label: __('Meta Description', 'asneris-seo-toolkit'), status: canonicalStatusByField('Meta Description') },
				{ label: __('Meta Description Length', 'asneris-seo-toolkit'), status: canonicalStatusByField('Meta Description Length') },
				{ label: __('H1 Presence', 'asneris-seo-toolkit'), status: canonicalStatusByField('H1 Presence') },
				{ label: __('Content Depth (Word Count)', 'asneris-seo-toolkit'), status: canonicalStatusByField('Content Depth (Word Count)') },
			];
			const detailHighlights = contentDetailStatuses.map((entry) => ({
				label: entry.label,
				value: toBinaryStatusLabel(entry.status),
			}));
			const keyFieldStatuses = contentDetailStatuses.map((entry) => entry.status);
			const keyFieldMatchCount = countMatchedStatuses(keyFieldStatuses);
			const summaryHighlights = [
				{ label: __('Key Fields Match Count', 'asneris-seo-toolkit'), value: `${ keyFieldMatchCount } / ${ detailHighlights.length }`, kind: 'match-count' },
				{ label: __('Total Check Match Count', 'asneris-seo-toolkit'), value: formatPrimaryTotalCheckMatchValue(DETAIL_TAB_CONTENT), kind: 'match-count' },
			];

		return {
			title: __('Content', 'asneris-seo-toolkit'),
			status: tone === 'success' ? __('Good', 'asneris-seo-toolkit') : __('Needs Work', 'asneris-seo-toolkit'),
			score: contentQualityScoreModel.score,
			scoreMessage: resolveTabScoreMessage(contentQualitySoftFailure),
			summary: tone === 'success'
				? __('Content structure and depth are in a healthy range.', 'asneris-seo-toolkit')
				: __('Content quality signals need improvement for stronger relevance.', 'asneris-seo-toolkit'),
			detailsTitle: __('Content Details', 'asneris-seo-toolkit'),
				summaryHighlights,
				detailHighlights,
				highlights: summaryHighlights,
			note: __('Improve readability and add more related keywords for better relevance.', 'asneris-seo-toolkit'),
			noteTone: 'warning',
		};
	}

	if (tabKey === DETAIL_TAB_SEARCH_APPEARANCE) {
		const searchAppearanceScoreModel = deriveSearchAppearanceScoreModel();
		const searchAppearanceSoftFailure = searchAppearanceScoreModel.softFailure;
		const searchAppearanceStatuses = [
			canonicalStatusByField('SEO Title'),
			canonicalStatusByField('Meta Description'),
			canonicalStatusByField('SEO Title Length'),
			canonicalStatusByField('Meta Description Length'),
			canonicalStatusByField('Canonical'),
		];
		const detailHighlights = [
			{ label: __('SEO Title', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(searchAppearanceStatuses[0]) },
			{ label: __('Meta Description', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(searchAppearanceStatuses[1]) },
			{ label: __('SEO Title Length', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(searchAppearanceStatuses[2]) },
			{ label: __('Meta Description Length', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(searchAppearanceStatuses[3]) },
			{ label: __('Canonical', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(searchAppearanceStatuses[4]) },
		];
		const keyFieldStatuses = searchAppearanceStatuses;
		const keyFieldMatchCount = countMatchedStatuses(keyFieldStatuses);
		const summaryHighlights = [
			{ label: __('Key Fields Match Count', 'asneris-seo-toolkit'), value: `${ keyFieldMatchCount } / ${ detailHighlights.length }` },
			{ label: __('Total Check Match Count', 'asneris-seo-toolkit'), value: formatPrimaryTotalCheckMatchValue(DETAIL_TAB_SEARCH_APPEARANCE) },
		];

		return {
			title: __('Search Appearance', 'asneris-seo-toolkit'),
			status: tone === 'success' ? __('Good', 'asneris-seo-toolkit') : __('Needs Work', 'asneris-seo-toolkit'),
			score: searchAppearanceScoreModel.score,
			scoreMessage: resolveTabScoreMessage(searchAppearanceSoftFailure),
			summary: __('Search snippets and social preview signals are mostly healthy with room to improve.', 'asneris-seo-toolkit'),
			detailsTitle: __('Search Appearance Details', 'asneris-seo-toolkit'),
				summaryHighlights,
				detailHighlights,
				highlights: summaryHighlights,
			note: __('Improve missing social metadata to strengthen CTR and share previews.', 'asneris-seo-toolkit'),
			noteTone: 'warning',
		};
	}

	if (tabKey === DETAIL_TAB_STRUCTURED_DATA) {
			const structuredDataScoreModel = deriveStructuredDataScoreModel();
			const structuredDataSoftFailure = structuredDataScoreModel.softFailure;
			const structuredDataStatuses = [
				canonicalStatusByField('Structured Data Present'),
				canonicalStatusByField('JSON-LD Valid'),
				canonicalStatusByField('Organization Schema'),
				canonicalStatusByField('Primary Schema'),
				canonicalStatusByField('FAQ Schema'),
				canonicalStatusByField('Breadcrumb Schema'),
			];
			const detailHighlights = [
				{ label: __('Structured Data Present', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(structuredDataStatuses[0]) },
				{ label: __('JSON-LD Valid', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(structuredDataStatuses[1]) },
				{ label: __('Organization Schema', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(structuredDataStatuses[2]) },
				{ label: __('Primary Schema', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(structuredDataStatuses[3]) },
				{ label: __('FAQ Schema', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(structuredDataStatuses[4]) },
				{ label: __('Breadcrumb Schema', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(structuredDataStatuses[5]) },
			];
			const keyFieldStatuses = structuredDataStatuses;
			const keyFieldMatchCount = countMatchedStatuses(keyFieldStatuses);
			const summaryHighlights = [
				{ label: __('Key Fields Match Count', 'asneris-seo-toolkit'), value: `${ keyFieldMatchCount } / ${ detailHighlights.length }`, kind: 'match-count' },
				{ label: __('Total Check Match Count', 'asneris-seo-toolkit'), value: formatStrictTotalCheckMatchValue(rows.length), kind: 'match-count' },
			];

		return {
			title: __('Structured Data', 'asneris-seo-toolkit'),
			status: tone === 'success' ? __('Good', 'asneris-seo-toolkit') : __('Needs Work', 'asneris-seo-toolkit'),
			score: structuredDataScoreModel.score,
			scoreMessage: resolveTabScoreMessage(structuredDataSoftFailure),
			summary: __('Structured data coverage is available but can be expanded for richer results.', 'asneris-seo-toolkit'),
			detailsTitle: __('Structured Data Details', 'asneris-seo-toolkit'),
				summaryHighlights,
				detailHighlights,
				highlights: summaryHighlights,
			note: __('Add missing schema types to improve eligible rich results.', 'asneris-seo-toolkit'),
			noteTone: 'warning',
		};
	}

	if (tabKey === DETAIL_TAB_LINKS) {
			const linksScoreModel = deriveLinksScoreModel();
			const linksSoftFailure = linksScoreModel.softFailure;
			const linkStatuses = [
				canonicalStatusByField('Internal Links'),
				canonicalStatusByField('External Links'),
				canonicalStatusByField('Nofollow Links'),
			];
			const detailHighlights = [
				{ label: __('Internal Links', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(linkStatuses[0]) },
				{ label: __('External Links', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(linkStatuses[1]) },
				{ label: __('Nofollow Links', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(linkStatuses[2]) },
			];
			const keyFieldStatuses = linkStatuses;
			const keyFieldMatchCount = countMatchedStatuses(keyFieldStatuses);
			const summaryHighlights = [
				{ label: __('Key Fields Match Count', 'asneris-seo-toolkit'), value: `${ keyFieldMatchCount } / ${ detailHighlights.length }`, kind: 'match-count' },
				{ label: __('Total Check Match Count', 'asneris-seo-toolkit'), value: formatStrictTotalCheckMatchValue(rows.length), kind: 'match-count' },
			];

		return {
			title: __('Links', 'asneris-seo-toolkit'),
			status: tone === 'success' ? __('Good', 'asneris-seo-toolkit') : __('Needs Work', 'asneris-seo-toolkit'),
			score: linksScoreModel.score,
			scoreMessage: resolveTabScoreMessage(linksSoftFailure),
			summary: tone === 'success'
				? __('Link signals look healthy with no major blockers detected.', 'asneris-seo-toolkit')
				: __('Internal and external link quality should be improved to support discoverability.', 'asneris-seo-toolkit'),
			detailsTitle: __('Links Details', 'asneris-seo-toolkit'),
				summaryHighlights,
				detailHighlights,
				highlights: summaryHighlights,
			note: __('Fix broken links and add more internal links from relevant pages.', 'asneris-seo-toolkit'),
			noteTone: 'warning',
		};
	}

	if (tabKey === DETAIL_TAB_IMAGES) {
			const imagesScoreModel = deriveImagesScoreModel();
			const imagesSoftFailure = imagesScoreModel.softFailure;
			const imageStatuses = [
				canonicalStatusByField('Images Found'),
				canonicalStatusByField('Missing ALT'),
				canonicalStatusByField('Empty ALT'),
				canonicalStatusByField('Featured Image'),
			];
			const detailHighlights = [
				{ label: __('Images Found', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(imageStatuses[0]) },
				{ label: __('Missing ALT', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(imageStatuses[1]) },
				{ label: __('Empty ALT', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(imageStatuses[2]) },
				{ label: __('Featured Image', 'asneris-seo-toolkit'), value: toBinaryStatusLabel(imageStatuses[3]) },
			];
			const keyFieldStatuses = imageStatuses;
			const keyFieldMatchCount = countMatchedStatuses(keyFieldStatuses);
			const summaryHighlights = [
				{ label: __('Key Fields Match Count', 'asneris-seo-toolkit'), value: `${ keyFieldMatchCount } / ${ detailHighlights.length }`, kind: 'match-count' },
				{ label: __('Total Check Match Count', 'asneris-seo-toolkit'), value: formatStrictTotalCheckMatchValue(rows.length), kind: 'match-count' },
			];

		return {
			title: __('Images', 'asneris-seo-toolkit'),
			status: tone === 'success' ? __('Good', 'asneris-seo-toolkit') : __('Needs Work', 'asneris-seo-toolkit'),
			score: imagesScoreModel.score,
			scoreMessage: resolveTabScoreMessage(imagesSoftFailure),
			summary: tone === 'success'
				? __('Image accessibility and supporting signals look healthy.', 'asneris-seo-toolkit')
				: __('Image optimization and descriptive metadata should be improved.', 'asneris-seo-toolkit'),
			detailsTitle: __('Images Details', 'asneris-seo-toolkit'),
				summaryHighlights,
				detailHighlights,
				highlights: summaryHighlights,
			note: __('Add ALT text to images and compress large images for better performance.', 'asneris-seo-toolkit'),
			noteTone: 'warning',
		};
	}

		const aiDetailFieldStatuses = [
			{ label: __('Topic Consistency', 'asneris-seo-toolkit'), status: canonicalStatusByField('Topic Consistency') },
			{ label: __('Clear Page Purpose', 'asneris-seo-toolkit'), status: canonicalStatusByField('Clear Page Purpose') },
			{ label: __('Summary Section', 'asneris-seo-toolkit'), status: canonicalStatusByField('Summary Section') },
			{ label: __('Content Completeness', 'asneris-seo-toolkit'), status: canonicalStatusByField('Content Completeness') },
			{ label: __('Internal References', 'asneris-seo-toolkit'), status: canonicalStatusByField('Internal References') },
		];
		const aiKeyFieldStatuses = aiDetailFieldStatuses.map((entry) => entry.status);
		const aiDetailHighlights = aiDetailFieldStatuses.map((entry) => ({
			label: entry.label,
			value: toBinaryStatusLabel(entry.status),
		}));
		const aiKeyFieldMatchCount = countMatchedStatuses(aiKeyFieldStatuses);
		const aiKeyFieldTotal = aiDetailHighlights.length;
		const aiSummaryHighlights = [
			{ label: __('Key Fields Match Count', 'asneris-seo-toolkit'), value: `${ aiKeyFieldMatchCount } / ${ aiKeyFieldTotal }`, kind: 'match-count' },
			{ label: __('Total Check Match Count', 'asneris-seo-toolkit'), value: formatPrimaryTotalCheckMatchValue(DETAIL_TAB_AI_DISCOVERABILITY), kind: 'match-count' },
		];
	const aiScoreModel = deriveAiDiscoverabilityScoreModel();

	return {
		title: __('AI Discoverability', 'asneris-seo-toolkit'),
		status: tone === 'success' ? __('Good', 'asneris-seo-toolkit') : __('Needs Work', 'asneris-seo-toolkit'),
		score: aiScoreModel.score,
		scoreMessage: resolveTabScoreMessage(aiScoreModel.softFailure, { includeBackendMessage: true }),
		summary: tone === 'success'
			? __('This page has strong signals for AI and generative search interpretation.', 'asneris-seo-toolkit')
			: __('AI discoverability signals can be strengthened with clearer structure and retrieval cues.', 'asneris-seo-toolkit'),
		detailsTitle: __('AI Discoverability Details', 'asneris-seo-toolkit'),
			summaryHighlights: aiSummaryHighlights,
			detailHighlights: aiDetailHighlights,
			highlights: aiSummaryHighlights,
		note: tone === 'success'
			? __('Good structure for AI retrieval and generative search visibility.', 'asneris-seo-toolkit')
			: __('Strengthen structured cues and content organization for AI retrieval.', 'asneris-seo-toolkit'),
		noteTone: tone === 'success' ? 'success' : 'warning',
	};
};

const PageDiagnosticsPanel = ({
	restUrl,
	restNonce,
	onStatus,
	normalizeChecks,
	initialPostId = null,
	detailOpenToken = 0,
	embeddedInEditorModal = false,
	editorIsDirty = false,
	editorDraftContext = null,
	embeddedInitialDiagnostics = null,
	onEmbeddedRequestClose = null,
}) => {
	const [data, setData] = useState({ total: 0, items: [], priorityItems: [], filters: {}, pagination: {} });
	const [isLoading, setIsLoading] = useState(false);
	const [selectedPostId, setSelectedPostId] = useState('');
	const [testingPostId, setTestingPostId] = useState(null);
	const [selectedResult, setSelectedResult] = useState(null);
	const [isReportDialogOpen, setIsReportDialogOpen] = useState(false);
	const [errorMessage, setErrorMessage] = useState('');
	const [postTypeFilter, setPostTypeFilter] = useState('all');
	const [postStatusFilter, setPostStatusFilter] = useState('publish');
	const [indexabilityFilter, setIndexabilityFilter] = useState('all');
	const [searchInput, setSearchInput] = useState('');
	const [searchQuery, setSearchQuery] = useState('');
	const [currentPage, setCurrentPage] = useState(1);
	const [priorityCurrentPage, setPriorityCurrentPage] = useState(1);
	const [activeMainTab, setActiveMainTab] = useState(MAIN_TAB_PRIORITY);
	const [priorityFeatureEnabled, setPriorityFeatureEnabled] = useState(true);
	const [activeDetailTab, setActiveDetailTab] = useState(DETAIL_TAB_OVERVIEW);
	const [activeDetailContentSection, setActiveDetailContentSection] = useState(DETAIL_CONTENT_SECTION_DETAILS);
	const [isDetailHeaderCollapsed, setIsDetailHeaderCollapsed] = useState(true);
	const [isCanonicalDebugOpen, setIsCanonicalDebugOpen] = useState(false);
	const [searchAppearanceViewport, setSearchAppearanceViewport] = useState('desktop');
	const [isMobileFilterOpen, setIsMobileFilterOpen] = useState(false);
	const [isMobileViewport, setIsMobileViewport] = useState(() => {
		if (typeof window === 'undefined') {
			return false;
		}

		return window.innerWidth <= 782;
	});
	const [historyItems, setHistoryItems] = useState([]);
	const [isHistoryLoading, setIsHistoryLoading] = useState(false);
	const [historyCount, setHistoryCount] = useState(0);
	const [historyLimit, setHistoryLimit] = useState(10);
	const [isHistoryPopupOpen, setIsHistoryPopupOpen] = useState(false);
	const [historyPopupItem, setHistoryPopupItem] = useState(null);
	const [historyPopupItems, setHistoryPopupItems] = useState([]);
	const [historyPopupCount, setHistoryPopupCount] = useState(0);
	const [historyPopupLimit, setHistoryPopupLimit] = useState(10);
	const [historyPopupFetchLimit, setHistoryPopupFetchLimit] = useState(10);
	const [isHistoryPopupLoading, setIsHistoryPopupLoading] = useState(false);
	const [historyPopupError, setHistoryPopupError] = useState('');
	const [historyPopupMode, setHistoryPopupMode] = useState('history');
	const [comparisonFilter, setComparisonFilter] = useState(COMPARISON_FILTER_ALL);
	const [expandedComparisonRowKey, setExpandedComparisonRowKey] = useState('');
	const [isHistoryLocked, setIsHistoryLocked] = useState(false);
	const [deletingHistoryId, setDeletingHistoryId] = useState(null);
	const [isClearingRecords, setIsClearingRecords] = useState(false);
	const [isClearConfirmOpen, setIsClearConfirmOpen] = useState(false);
	const [pendingHistoryJump, setPendingHistoryJump] = useState(false);
	const [selectedPostIds, setSelectedPostIds] = useState([]);
	const [isBulkAnalyzing, setIsBulkAnalyzing] = useState(false);
	const [lastPerformance, setLastPerformance] = useState(null);
	const historySectionRef = useRef(null);
	const detailTabsRef = useRef(null);
	const detailTabsDragStateRef = useRef({
		isDragging: false,
		startX: 0,
		startScrollLeft: 0,
		hasMoved: false,
		suppressClick: false,
	});

	const diagnosticsPostBaseUrl = useMemo(
		() => (restUrl || '').replace(/\/page-diagnostics\/overview$/, '/page-diagnostics/run'),
		[restUrl]
	);

	const diagnosticsDraftPolicyBaseUrl = useMemo(
		() => (restUrl || '').replace(/\/page-diagnostics\/overview$/, '/page-diagnostics/draft-policy'),
		[restUrl]
	);

	const diagnosticsReadBaseUrl = useMemo(
		() => (restUrl || '').replace(/\/page-diagnostics\/overview$/, '/diagnostics'),
		[restUrl]
	);

	const diagnosticsHistoryBaseUrl = useMemo(
		() => (restUrl || '').replace(/\/page-diagnostics\/overview$/, '/page-diagnostics/history'),
		[restUrl]
	);

	const buildHistoryRequestUrl = useCallback((postId, limit = 10) => {
		if (!diagnosticsHistoryBaseUrl || !postId) {
			return '';
		}

		const requestUrl = new URL(diagnosticsHistoryBaseUrl, window.location.origin);
		const restRoute = requestUrl.searchParams.get('rest_route');

		if (restRoute) {
			const normalizedRoute = String(restRoute).replace(/\/$/, '');
			requestUrl.searchParams.set('rest_route', `${ normalizedRoute }/${ postId }`);
		} else {
			requestUrl.pathname = `${ requestUrl.pathname.replace(/\/$/, '') }/${ postId }`;
		}

		const numericLimit = Number(limit);
		if (Number.isFinite(numericLimit) && numericLimit > 0) {
			requestUrl.searchParams.set('limit', String(Math.floor(numericLimit)));
		} else {
			requestUrl.searchParams.delete('limit');
		}

		return requestUrl.toString();
	}, [diagnosticsHistoryBaseUrl]);

	const diagnosticsRecordsClearBaseUrl = useMemo(
		() => (restUrl || '').replace(/\/page-diagnostics\/overview$/, '/page-diagnostics/records/clear'),
		[restUrl]
	);

	const closeReportDialog = useCallback(() => {
		setIsClearConfirmOpen(false);
		setIsReportDialogOpen(false);
		setIsCanonicalDebugOpen(false);
		if (embeddedInEditorModal) {
			onEmbeddedRequestClose?.();
		}
	}, [embeddedInEditorModal, onEmbeddedRequestClose]);

	useEffect(() => {
		setExpandedComparisonRowKey('');
	}, [historyPopupMode, comparisonFilter, historyPopupItem?.postId]);

	const buildEmbeddedLocalDiagnosticsResult = useCallback((postId) => {
		if (!embeddedInEditorModal || !postId) {
			return null;
		}

		const embeddedPayloadPostId = String(
			embeddedInitialDiagnostics?.postId
				|| embeddedInitialDiagnostics?.post_id
				|| embeddedInitialDiagnostics?.id
				|| ''
		).trim();
		const targetPostId = String(postId || '').trim();
		const isMatchingEmbeddedPayload = !embeddedPayloadPostId || embeddedPayloadPostId === targetPostId;

		if (
			embeddedInitialDiagnostics &&
			isMatchingEmbeddedPayload
		) {
			const initialPayload = mergeUnifiedItem(embeddedInitialDiagnostics) || embeddedInitialDiagnostics;
			const inferredSource = editorIsDirty ? 'editor-local-dirty' : 'editor-draft-policy';
			return {
				...initialPayload,
				postId: initialPayload?.postId || postId,
				lastScanGmt: initialPayload?.lastScanGmt || new Date().toISOString(),
				source: initialPayload?.source || inferredSource,
				checks: normalizeChecks(initialPayload?.checks),
			};
		}

		return null;
	}, [embeddedInEditorModal, editorIsDirty, embeddedInitialDiagnostics, normalizeChecks]);


	const priorityPageIdSet = useMemo(
		() => new Set((data.priorityItems || []).map((item) => String(item.postId))),
		[data.priorityItems]
	);
	const isSelectedResultPriority = useMemo(() => {
		if (typeof selectedResult?.isPriority === 'boolean') {
			return selectedResult.isPriority;
		}

		if (!selectedResult?.postId) {
			return false;
		}

		return priorityPageIdSet.has(String(selectedResult.postId));
	}, [selectedResult, priorityPageIdSet]);
	const isEmbeddedDetailOpenFlow = useMemo(
		() => embeddedInEditorModal && !!initialPostId && !!detailOpenToken,
		[embeddedInEditorModal, initialPostId, detailOpenToken]
	);
	const isSnapshotHistorySuppressedInEmbeddedFlow = useMemo(
		() => isEmbeddedDetailOpenFlow,
		[isEmbeddedDetailOpenFlow]
	);
	const shouldShowSnapshotHistory = useMemo(
		() => isSelectedResultPriority && !isSnapshotHistorySuppressedInEmbeddedFlow,
		[isSelectedResultPriority, isSnapshotHistorySuppressedInEmbeddedFlow]
	);

	const loadOverview = () => {
		if (embeddedInEditorModal || !restUrl) {
			return;
		}

		setIsLoading(true);
		setErrorMessage('');

		const requestUrl = new URL(restUrl, window.location.origin);
		requestUrl.searchParams.set('perPage', '10');
		requestUrl.searchParams.set('page', String(currentPage));
		if (postTypeFilter !== 'all') {
			requestUrl.searchParams.set('postType', postTypeFilter);
		}
		requestUrl.searchParams.set('postStatus', postStatusFilter || 'publish');
		if (searchQuery) {
			requestUrl.searchParams.set('search', searchQuery);
		}
		requestUrl.searchParams.set('scope', activeMainTab === MAIN_TAB_NON_PRIORITY ? 'non_priority' : 'priority');

		fetchJson(requestUrl.toString(), {
			method: 'GET',
			headers: {
				'X-WP-Nonce': restNonce || '',
			},
		})
			.then((payload) => {
				const rawItems = assertUnifiedCollection(payload?.items, 'overview.items');
				const rawPriorityItems = assertUnifiedCollection(payload?.priorityItems, 'overview.priorityItems');

				const items = rawItems.map((item) => mergeUnifiedItem(item)).filter(Boolean);
				const priorityItems = rawPriorityItems.map((item) => mergeUnifiedItem(item)).filter(Boolean);
				const nextPriorityFeatureEnabled = payload?.priorityFeatureEnabled !== false;
				const pagination = payload?.pagination || {};
				setPriorityFeatureEnabled(nextPriorityFeatureEnabled);
				setData({
					total: payload?.total || 0,
					items,
					priorityItems,
					filters: payload?.filters || {},
					pagination,
				});
				if (pagination?.page && Number(pagination.page) !== currentPage) {
					setCurrentPage(Number(pagination.page));
				}
			})
			.catch((error) => {
				const message = error.message || __('Unable to load diagnostics overview.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => {
				setIsLoading(false);
			});
	};

	useEffect(() => {
		const timerId = setTimeout(() => {
			setSearchQuery(searchInput.trim());
			setCurrentPage(1);
		}, 250);

		return () => clearTimeout(timerId);
	}, [searchInput]);

	useEffect(() => {
		if (embeddedInEditorModal) {
			return;
		}

		loadOverview();
	}, [restUrl, restNonce, postTypeFilter, postStatusFilter, searchQuery, currentPage, activeMainTab, embeddedInEditorModal]);

	useEffect(() => {
		if (!priorityFeatureEnabled && activeMainTab === MAIN_TAB_PRIORITY) {
			setCurrentPage(1);
			setActiveMainTab(MAIN_TAB_NON_PRIORITY);
		}
	}, [priorityFeatureEnabled, activeMainTab]);

	useEffect(() => {
		if (!isHistoryPopupOpen) {
			return undefined;
		}

		const onKeyDown = (event) => {
			if (event.key === 'Escape') {
				setIsHistoryPopupOpen(false);
			}
		};

		document.addEventListener('keydown', onKeyDown);
		return () => document.removeEventListener('keydown', onKeyDown);
	}, [isHistoryPopupOpen]);

	useEffect(() => {
		if (!isReportDialogOpen) {
			setIsClearConfirmOpen(false);
			return undefined;
		}

		const onKeyDown = (event) => {
			if (event.key === 'Escape') {
				if (isClearConfirmOpen) {
					setIsClearConfirmOpen(false);
					return;
				}

				closeReportDialog();
			}
		};

		document.addEventListener('keydown', onKeyDown);
		return () => document.removeEventListener('keydown', onKeyDown);
	}, [isReportDialogOpen, isClearConfirmOpen, closeReportDialog]);

	const loadPopupHistory = (postId, limit = historyPopupFetchLimit) => {
		if (!postId || !diagnosticsHistoryBaseUrl) {
			return;
		}

		setIsHistoryPopupLoading(true);
		setHistoryPopupError('');

		const requestUrl = buildHistoryRequestUrl(postId, limit);
		if (!requestUrl) {
			setIsHistoryPopupLoading(false);
			setHistoryPopupError(__('Unable to load page history.', 'asneris-seo-toolkit'));
			return;
		}

		fetchJson(requestUrl, {
			method: 'GET',
			headers: {
				'X-WP-Nonce': restNonce || '',
			},
		})
			.then((payload) => {
				const rows = Array.isArray(payload?.history) ? payload.history : [];
				setHistoryPopupItems(rows);
				setHistoryPopupCount(Number(payload?.historyCount || rows.length || 0));
				setHistoryPopupLimit(Number(payload?.historyLimit || 10));
				setHistoryPopupFetchLimit(rows.length);
			})
			.catch((error) => {
				setHistoryPopupItems([]);
				setHistoryPopupCount(0);
				setHistoryPopupLimit(10);
				setHistoryPopupFetchLimit(0);
				setHistoryPopupError(error?.message || __('Unable to load page history.', 'asneris-seo-toolkit'));
			})
			.finally(() => {
				setIsHistoryPopupLoading(false);
			});
	};

	const openHistoryPopup = (item) => {
		if (isEmbeddedDetailOpenFlow) {
			return;
		}

		if (!item?.postId) {
			return;
		}

		setHistoryPopupItem(item);
		setHistoryPopupFetchLimit(0);
		setHistoryPopupMode('history');
		setComparisonFilter(COMPARISON_FILTER_ALL);
		setIsHistoryPopupOpen(true);
		loadPopupHistory(item.postId, 10);
	};

	useEffect(() => {
		if (!isReportDialogOpen || !selectedResult?.postId || !diagnosticsHistoryBaseUrl || !shouldShowSnapshotHistory) {
			setHistoryItems([]);
			setHistoryCount(0);
			setHistoryLimit(10);
			setIsHistoryLocked(false);
			return;
		}

		const requestUrl = buildHistoryRequestUrl(selectedResult.postId, 10);
		if (!requestUrl) {
			setHistoryItems([]);
			setHistoryCount(0);
			setHistoryLimit(10);
			setIsHistoryLocked(false);
			return;
		}
		setIsHistoryLoading(true);

		fetchJson(requestUrl, {
			method: 'GET',
			headers: {
				'X-WP-Nonce': restNonce || '',
			},
		})
			.then((payload) => {
				const rows = Array.isArray(payload?.history) ? payload.history : [];
				setHistoryItems(rows);
				const nextHistoryCount = Number(payload?.historyCount || rows.length || 0);
				const nextHistoryLimit = Number(payload?.historyLimit || 10);
				setHistoryCount(nextHistoryCount);
				setHistoryLimit(nextHistoryLimit);
				setIsHistoryLocked(Boolean(payload?.historyLocked) || nextHistoryCount >= nextHistoryLimit);
			})
			.catch(() => {
				setHistoryItems([]);
				setHistoryCount(0);
				setHistoryLimit(10);
				setIsHistoryLocked(false);
			})
			.finally(() => {
				setIsHistoryLoading(false);
			});
	}, [isReportDialogOpen, selectedResult?.postId, diagnosticsHistoryBaseUrl, restNonce, shouldShowSnapshotHistory, buildHistoryRequestUrl]);

	const deleteHistoryRecord = (historyId) => {
		if (!selectedResult?.postId || !diagnosticsHistoryBaseUrl || !historyId || deletingHistoryId) {
			return;
		}

		setDeletingHistoryId(historyId);

		fetchJson(`${ diagnosticsHistoryBaseUrl }/${ selectedResult.postId }/delete`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': restNonce || '',
			},
			body: JSON.stringify({ historyId }),
		})
			.then((payload) => {
				setHistoryItems((previous) => previous.filter((row) => Number(row?.id) !== Number(historyId)));
				const nextHistoryCount = Number(payload?.historyCount || 0);
				const nextHistoryLimit = Number(payload?.historyLimit || historyLimit || 10);
				setHistoryCount(nextHistoryCount);
				setHistoryLimit(nextHistoryLimit);
				setIsHistoryLocked(Boolean(payload?.historyLocked) || nextHistoryCount >= nextHistoryLimit);
				onStatus?.({ tone: 'success', text: __('History record deleted.', 'asneris-seo-toolkit') });
			})
			.catch((error) => {
				onStatus?.({ tone: 'error', text: error?.message || __('Unable to delete history record.', 'asneris-seo-toolkit') });
			})
			.finally(() => {
				setDeletingHistoryId(null);
			});
	};

	const clearPageRecords = () => {
		if (!selectedResult?.postId || !diagnosticsRecordsClearBaseUrl || isClearingRecords) {
			return;
		}

		setIsClearConfirmOpen(true);
	};

	const confirmClearPageRecords = () => {
		if (!selectedResult?.postId || !diagnosticsRecordsClearBaseUrl || isClearingRecords) {
			return;
		}

		setIsClearConfirmOpen(false);
		setIsClearingRecords(true);

		fetchJson(`${ diagnosticsRecordsClearBaseUrl }/${ selectedResult.postId }`, {
			method: 'POST',
			headers: {
				'X-WP-Nonce': restNonce || '',
			},
		})
			.then((payload) => {
				setHistoryItems([]);
				setHistoryCount(0);
				setIsHistoryLocked(false);
				setSelectedResult((previous) => {
					if (!previous) {
						return previous;
					}

					return {
						...previous,
						isPriority: false,
					};
				});
				loadOverview();
				onStatus?.({
					tone: 'success',
					text: payload?.removedFromPriority
						? __('Page removed from Priority and diagnostics records cleaned.', 'asneris-seo-toolkit')
						: __('Diagnostics records cleaned for this page.', 'asneris-seo-toolkit'),
				});
			})
			.catch((error) => {
				onStatus?.({ tone: 'error', text: error?.message || __('Unable to clean diagnostics records.', 'asneris-seo-toolkit') });
			})
			.finally(() => {
				setIsClearingRecords(false);
			});
	};

	const runPostDiagnostics = (postId, options = {}) => {
		const { openReport = true, statusNotice = true, forceRefresh = false, noStore = false } = options;
		const effectiveNoStore = noStore || isEmbeddedDetailOpenFlow;
		const isCurrentEditorDetailTarget = !initialPostId || String(postId) === String(initialPostId);
		const shouldUseDraftPolicyRequest = Boolean(
			embeddedInEditorModal
			&& diagnosticsDraftPolicyBaseUrl
			&& editorDraftContext
			&& isCurrentEditorDetailTarget
		);

		if ((!diagnosticsPostBaseUrl && !shouldUseDraftPolicyRequest) || !postId || testingPostId) {
			return Promise.resolve(null);
		}

		setTestingPostId(postId);
		setErrorMessage('');

		const requestPromise = shouldUseDraftPolicyRequest
			? fetchJson(diagnosticsDraftPolicyBaseUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': restNonce || '',
				},
				body: JSON.stringify({
					postId,
					postTitle: editorDraftContext?.postTitle || '',
					postExcerpt: editorDraftContext?.postExcerpt || '',
					content: editorDraftContext?.content || '',
					meta: editorDraftContext?.meta || {},
					url: editorDraftContext?.url || '',
				}),
			})
			: (() => {
				let requestUrl = `${ diagnosticsPostBaseUrl }/${ postId }`;
				const queryParams = new URLSearchParams();
				if ( effectiveNoStore ) {
					queryParams.set('no_store', '1');
				}
				if ( forceRefresh ) {
					queryParams.set('force', '1');
				}
				const query = queryParams.toString();
				if ( query ) {
					requestUrl += `?${ query }`;
				}

				return fetchJson(requestUrl, {
					method: 'POST',
					headers: {
						'X-WP-Nonce': restNonce || '',
					},
				});
			})();

		return requestPromise
			.then((payload) => {
				assertUnifiedData(payload, 'diagnostics.scan');

				const normalizedPayload = mergeUnifiedItem(payload || {});
				const computed = getUnifiedComputed(normalizedPayload || payload || {});
				const resolvedSeoScore = Number.isFinite(Number(computed?.seoScore))
					? Number(computed.seoScore)
					: (Number.isFinite(Number(normalizedPayload?.seoScore)) ? Number(normalizedPayload.seoScore) : null);
				const resolvedAiScore = Number.isFinite(Number(computed?.aiScore))
					? Number(computed.aiScore)
					: (Number.isFinite(Number(normalizedPayload?.aiScore)) ? Number(normalizedPayload.aiScore) : null);

				if (payload?.performance) {
					setLastPerformance(payload.performance);
				}

				if (openReport) {
					setSelectedResult({
						...normalizedPayload,
						postId: normalizedPayload?.postId || postId,
						seoScore: resolvedSeoScore ?? normalizedPayload?.seoScore,
						aiScore: resolvedAiScore ?? normalizedPayload?.aiScore,
						url: normalizedPayload?.url || '',
						lastScanGmt: normalizedPayload?.lastScanGmt || new Date().toISOString(),
						isPriority: typeof normalizedPayload?.isPriority === 'boolean' ? normalizedPayload.isPriority : undefined,
						checks: normalizeChecks(normalizedPayload?.checks),
						performance: normalizedPayload?.performance || null,
					});
					setActiveDetailTab(DETAIL_TAB_OVERVIEW);
					setIsReportDialogOpen(true);
				}
				setData((previous) => ({
					...previous,
					items: (previous.items || []).map((item) => (
						String(item.postId) === String(postId)
							? {
								...item,
								lastScanGmt: normalizedPayload?.lastScanGmt || new Date().toISOString(),
								seoScore: resolvedSeoScore ?? item?.seoScore,
								aiScore: resolvedAiScore ?? item?.aiScore,
								health: normalizedPayload?.health || item?.health,
								issueGroups: normalizedPayload?.issueGroups || item?.issueGroups,
								checks: normalizeChecks(normalizedPayload?.checks).length > 0 ? normalizeChecks(normalizedPayload?.checks) : item?.checks,
								unifiedData: normalizedPayload?.unifiedData,
							}
							: item
					)),
					priorityItems: (previous.priorityItems || []).map((item) => (
						String(item.postId) === String(postId)
							? {
								...item,
								lastScanGmt: normalizedPayload?.lastScanGmt || new Date().toISOString(),
								seoScore: resolvedSeoScore ?? item?.seoScore,
								aiScore: resolvedAiScore ?? item?.aiScore,
								health: normalizedPayload?.health || item?.health,
								issueGroups: normalizedPayload?.issueGroups || item?.issueGroups,
								checks: normalizeChecks(normalizedPayload?.checks).length > 0 ? normalizeChecks(normalizedPayload?.checks) : item?.checks,
								unifiedData: normalizedPayload?.unifiedData,
							}
							: item
					)),
				}));
				if (statusNotice) {
					onStatus?.({ tone: 'success', text: __('Diagnostics completed for selected item.', 'asneris-seo-toolkit') });
				}
				return payload;
			})
			.catch((error) => {
				const message = error.message || __('Diagnostics failed.', 'asneris-seo-toolkit');
				if (statusNotice) {
					setErrorMessage(message);
					onStatus?.({ tone: 'error', text: message });
				}
				throw error;
			})
			.finally(() => {
				setTestingPostId(null);
			});
	};

	const viewStoredDiagnostics = (postId, options = {}) => {
		const { statusNotice = false } = options;

		if (embeddedInEditorModal || isEmbeddedDetailOpenFlow) {
			return runPostDiagnostics(postId, {
				openReport: true,
				statusNotice,
				noStore: true,
			});
		}

		if (!diagnosticsReadBaseUrl || !postId || testingPostId) {
			return Promise.resolve(null);
		}

		setTestingPostId(postId);
		setErrorMessage('');

		const requestUrl = `${ diagnosticsReadBaseUrl }/${ postId }`;

		return fetchJson(requestUrl, {
			method: 'GET',
			headers: {
				'X-WP-Nonce': restNonce || '',
			},
		})
			.then((payload) => {
				assertUnifiedData(payload, 'diagnostics.view.stored');
				const normalizedPayload = mergeUnifiedItem(payload || {});

				setSelectedResult({
					...normalizedPayload,
					postId: normalizedPayload?.postId || postId,
					url: normalizedPayload?.url || '',
					lastScanGmt: normalizedPayload?.lastScanGmt || new Date().toISOString(),
					isPriority: typeof normalizedPayload?.isPriority === 'boolean' ? normalizedPayload.isPriority : undefined,
					checks: normalizeChecks(normalizedPayload?.checks),
					performance: normalizedPayload?.performance || null,
				});
				setActiveDetailTab(DETAIL_TAB_OVERVIEW);
				setIsReportDialogOpen(true);

				if (statusNotice) {
					onStatus?.({ tone: 'success', text: __('Loaded stored diagnostics for selected item.', 'asneris-seo-toolkit') });
				}

				return payload;
			})
			.catch((error) => {
				const message = error?.message || __('Unable to load stored diagnostics for this page.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
				throw error;
			})
			.finally(() => {
				setTestingPostId(null);
			});
	};

	const lastDetailOpenKeyRef = useRef('');

	useEffect(() => {
		if (!initialPostId || !detailOpenToken) {
			return;
		}

		const detailOpenKey = `${ detailOpenToken }-${ String(initialPostId) }`;
		if (lastDetailOpenKeyRef.current === detailOpenKey) {
			return;
		}

		lastDetailOpenKeyRef.current = detailOpenKey;

		const embeddedResult = buildEmbeddedLocalDiagnosticsResult(initialPostId);
		if (embeddedResult) {
			setSelectedResult(embeddedResult);
			setActiveDetailTab(DETAIL_TAB_OVERVIEW);
			setIsReportDialogOpen(true);
			return;
		}

		runPostDiagnostics(initialPostId, {
			openReport: true,
			statusNotice: false,
		}).catch((error) => {
			const message = error?.message || __('Unable to open diagnostics detail for this page.', 'asneris-seo-toolkit');
			setErrorMessage(message);
			onStatus?.({ tone: 'error', text: message });
		});
	}, [
		initialPostId,
		detailOpenToken,
		onStatus,
		buildEmbeddedLocalDiagnosticsResult,
		runPostDiagnostics,
	]);

	const canApplyEmbeddedDraftData = useMemo(() => {
		if (!selectedResult?.postId) {
			return false;
		}

		return !!buildEmbeddedLocalDiagnosticsResult(selectedResult.postId);
	}, [selectedResult?.postId, buildEmbeddedLocalDiagnosticsResult]);

	const applyEmbeddedDraftData = useCallback(() => {
		if (!selectedResult?.postId) {
			return;
		}

		const localDirtyResult = buildEmbeddedLocalDiagnosticsResult(selectedResult.postId);
		if (!localDirtyResult) {
			return;
		}

		setSelectedResult(localDirtyResult);
		onStatus?.({ tone: 'info', text: __('Showing draft (unsaved) data preview.', 'asneris-seo-toolkit') });
	}, [selectedResult?.postId, buildEmbeddedLocalDiagnosticsResult, onStatus]);

	const runSelectedDiagnostics = (event) => {
		event.preventDefault();
		if (!selectedPostId || testingPostId) {
			return;
		}

		runPostDiagnostics(selectedPostId);
	};

	const toggleSelectedPost = (postId) => {
		const key = String(postId);
		setSelectedPostIds((previous) => (
			previous.includes(key)
				? previous.filter((item) => item !== key)
				: [ ...previous, key ]
		));
	};

	const categorizedReport = useMemo(() => {
		const checks = Array.isArray(selectedResult?.checks) ? selectedResult.checks : [];
		const sectionBuckets = TAXONOMY_SECTIONS.reduce((acc, section) => ({
			...acc,
			[section.key]: [],
		}), {});
		const debugRows = [];

		checks.forEach((check, index) => {
			const hasBackendCategory = TAXONOMY_SECTION_KEYS.has(check?.category);
			const category = hasBackendCategory
				? check.category
				: toCheckCategory(check?.label);
			const status = mapCheckStatus(check?.status);
			sectionBuckets[category].push({
				id: `${ selectedResult?.postId || 'post' }-${ index }`,
				label: check?.label || '-',
				status,
				result: String(check?.result ?? '-'),
				details: sanitizeUiEvidenceText(check?.details || '-'),
			});

			debugRows.push({
				id: `${ selectedResult?.postId || 'post' }-debug-${ index }`,
				label: check?.label || '-',
				category,
				sourceKey: hasBackendCategory ? 'backend' : 'fallback',
				sourceLabel: hasBackendCategory ? __('backend', 'asneris-seo-toolkit') : __('fallback', 'asneris-seo-toolkit'),
			});
		});

		const counts = checks.reduce(
			(acc, check) => {
				const status = mapCheckStatus(check?.status);
				if (status === 'pass') {
					acc.pass += 1;
				} else if (status === 'warning') {
					acc.warning += 1;
				} else if (status === 'fail') {
					acc.fail += 1;
				}

				return acc;
			},
			{ pass: 0, warning: 0, fail: 0 }
		);

		const sectionRows = TAXONOMY_SECTIONS.map((section) => {
			const rows = sectionBuckets[section.key].map((row) => ({
				key: row.id,
				cells: [row.label, formatCheckStatusLabel(row.status), row.result, row.details],
			}));

			const sectionIssues = sectionBuckets[section.key]
				.filter((row) => row.status === 'warning' || row.status === 'fail').length;

			return {
				...section,
				rows,
				total: rows.length,
				issues: sectionIssues,
			};
		});

		return {
			counts,
			sections: sectionRows,
			debugRows,
		};
	}, [selectedResult]);

	const availablePostTypes = Array.isArray(data?.filters?.postTypes) && data.filters.postTypes.length > 0
		? data.filters.postTypes
		: [
			{ value: 'all', label: __('All (Pages + Posts)', 'asneris-seo-toolkit') },
			{ value: 'page', label: __('Pages', 'asneris-seo-toolkit') },
			{ value: 'post', label: __('Posts', 'asneris-seo-toolkit') },
		];
	const availablePostStatuses = Array.isArray(data?.filters?.postStatuses) && data.filters.postStatuses.length > 0
		? data.filters.postStatuses
		: [
			{ value: 'publish', label: __('Published', 'asneris-seo-toolkit') },
		];
	const selectedResultItem = selectedResult
		? reconcileMetaTitleLengthFields({
			...([...(data.priorityItems || []), ...(data.items || [])].find((item) => String(item.postId) === String(selectedResult.postId)) || {}),
			...selectedResult,
		})
		: null;
	const canonicalDebugRows = useMemo(() => {
		if (!selectedResultItem || typeof selectedResultItem !== 'object') {
			return [];
		}

		return Object.entries(TAB_FIELD_REGISTRY || {}).flatMap(([tabKey, fields]) => {
			const canonicalFields = Array.isArray(fields) ? fields : [];

			return canonicalFields.map((canonicalField) => {
				const mappedFields = getCanonicalRawFields(tabKey, canonicalField);
				const derived = deriveProposedFieldFromSource(canonicalField, selectedResultItem);
				const status = mapCheckStatus(derived?.status || 'not scanned');
				const evidence = formatEvidenceFromFields(mappedFields, selectedResultItem, {});

				return {
					key: `${ tabKey }::${ canonicalField }`,
					tabLabel: CANONICAL_MAPPING_TAB_LABELS[tabKey] || tabKey,
					canonicalField,
					status: status === 'not_scanned' ? 'not scanned' : status,
					mappedFields: mappedFields.length > 0 ? mappedFields.join(', ') : '-',
					evidence: evidence === '-' ? resolveCanonicalEvidenceGapMessage(mappedFields, evidence) : evidence,
				};
			});
		});
	}, [selectedResultItem]);
	const reportSectionsWithChecks = categorizedReport.sections.filter((section) => section.total > 0);
	const matchesIndexability = (item) => {
		if (indexabilityFilter === 'all') {
			return true;
		}

		const robots = (item?.robotsIndex || 'index').toLowerCase();
		if (indexabilityFilter === 'indexable') {
			return robots === 'index';
		}

		return robots === 'noindex';
	};

	const toItemRow = (item) => {
		const seoScore = deriveSeoScore(item);
		const health = deriveHealth(item);
		const issueSummary = getIssueSummary(item);

		const warningIssues = issueSummary.warning ?? Math.max(0, issueSummary.total - issueSummary.critical);
		const hasAnyIssues = issueSummary.total > 0;
		const issuePrimary = !hasAnyIssues
			? { tone: 'success', label: __('No Issues', 'asneris-seo-toolkit') }
			: issueSummary.critical > 0
				? { tone: 'fail', label: `${ __('Critical', 'asneris-seo-toolkit') } ${ issueSummary.critical }` }
				: { tone: 'warning', label: `${ __('Warning', 'asneris-seo-toolkit') } ${ warningIssues }` };
		const issueSecondary = !hasAnyIssues
			? null
			: issueSummary.critical > 0 && warningIssues > 0
				? { tone: 'warning', label: `${ __('Warning', 'asneris-seo-toolkit') } ${ warningIssues }` }
				: null;
		const isIndexable = ((item?.robotsIndex || 'index').toLowerCase() !== 'noindex');
		const healthSubtext = health.tone === 'success' ? __('Good to go', 'asneris-seo-toolkit') : __('Needs fixes', 'asneris-seo-toolkit');
		const scoreBand = getScoreBand(seoScore);
		const isChecked = selectedPostIds.includes(String(item.postId));
		const lastScanDateLabel = formatLastScanLabel(item.lastScanGmt);
		const lastScanTimeLabel = item.lastScanGmt
			? (() => {
				const date = new Date(item.lastScanGmt);
				if (Number.isNaN(date.getTime())) {
					return null;
				}

				return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
			})()
			: null;

		return {
			key: `item-${ item.postId }`,
			mobileAccordionTitle: item.title || __('(Untitled)', 'asneris-seo-toolkit'),
			cells: [
				<label className="ASNERISSEO-react-checkbox-inline ASNERISSEO-react-pd-select-cell">
					<input
						type="checkbox"
						checked={ isChecked }
						onChange={ () => toggleSelectedPost(item.postId) }
						disabled={ isLoading || !!testingPostId || isBulkAnalyzing }
					/>
					<span className="screen-reader-text">{ __('Select page', 'asneris-seo-toolkit') }</span>
				</label>,
				<div>
					{ item.url ? (
						<a href={ item.url } target="_blank" rel="noopener noreferrer">{ item.title || __('(Untitled)', 'asneris-seo-toolkit') }</a>
					) : (
						<span>{ item.title || __('(Untitled)', 'asneris-seo-toolkit') }</span>
					) }
					<div className="ASNERISSEO-react-muted">
						{ item.url ? String(item.url).replace(/^https?:\/\//i, '') : '-' }
					</div>
				</div>,
				<div>
					{ `${ (item.postType || 'post').charAt(0).toUpperCase() }${ (item.postType || 'post').slice(1) }` }
				</div>,
				<div className="ASNERISSEO-react-pd-cell-stack is-center">
					<span className={ `ASNERISSEO-react-score-pill is-${ scoreBand }` }>{ seoScore ?? '-' }</span>
					{ item?.isDraftQualityOnly ? <div className="ASNERISSEO-react-muted ASNERISSEO-react-pd-cell-subtext">{ __('Draft: quality only', 'asneris-seo-toolkit') }</div> : null }
				</div>,
				<div className="ASNERISSEO-react-pd-cell-stack">
					<span className={ `ASNERISSEO-react-status-chip ASNERISSEO-react-status-chip-compact ASNERISSEO-react-pd-uniform-badge is-${ health.tone }` }>{ health.label }</span>
					{/* <span className="ASNERISSEO-react-pd-health-sub">{ healthSubtext }</span> */}
				</div>,
				<div className="ASNERISSEO-react-pd-cell-stack ASNERISSEO-react-pd-index-stack">
					{/* <span className="ASNERISSEO-react-pd-index-subtext">{ __('Index', 'asneris-seo-toolkit') }</span> */}
					<span className={ `ASNERISSEO-react-status-chip ASNERISSEO-react-status-chip-compact ASNERISSEO-react-pd-uniform-badge ASNERISSEO-react-pd-index-badge is-${ isIndexable ? 'success' : 'fail' }` }>
						{ isIndexable ? __('Eligible', 'asneris-seo-toolkit') : formatIndexLabel(item) }
					</span>
				</div>,
				<div className="ASNERISSEO-react-pd-issues-badges ASNERISSEO-react-pd-issues-badges-loose" style={{ rowGap: '6px' }}>
					<span className={ `ASNERISSEO-react-status-chip ASNERISSEO-react-status-chip-compact ASNERISSEO-react-pd-uniform-badge is-${ issuePrimary.tone }` }>{ issuePrimary.label }</span>
					{ issueSecondary ? (
						<span className={ `ASNERISSEO-react-status-chip ASNERISSEO-react-status-chip-compact ASNERISSEO-react-pd-uniform-badge is-${ issueSecondary.tone }` }>{ issueSecondary.label }</span>
					) : (
						<span className="ASNERISSEO-react-status-chip ASNERISSEO-react-status-chip-compact ASNERISSEO-react-pd-uniform-badge ASNERISSEO-react-pd-badge-placeholder" aria-hidden="true">&nbsp;</span>
					) }
				</div>,
				<div className="ASNERISSEO-react-pd-cell-stack ASNERISSEO-react-pd-last-scan-stack">
					<span className="ASNERISSEO-react-pd-last-scan-date">{ lastScanDateLabel }</span>
					{ lastScanTimeLabel ? <span className="ASNERISSEO-react-pd-last-scan-time">{ lastScanTimeLabel }</span> : null }
				</div>,
				<div className="ASNERISSEO-react-pd-action-stack">
					<button
						type="button"
						className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
						onClick={ () => {
							setSelectedPostId(String(item.postId));
							viewStoredDiagnostics(item.postId);
						} }
						disabled={ !!testingPostId || isLoading || isBulkAnalyzing }
					>
						{ testingPostId === item.postId ? __('Running...', 'asneris-seo-toolkit') : __('View', 'asneris-seo-toolkit') }
					</button>
					<button
						type="button"
						className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
						onClick={ () => openHistoryPopup(item) }
						disabled={ isLoading || !!testingPostId || isBulkAnalyzing }
					>
						{ __('View History', 'asneris-seo-toolkit') }
					</button>
				</div>,
			],
		};
	};

	const filteredPriorityItems = (data.priorityItems || []).filter((item) => matchesIndexability(item));
	const nonPriorityItems = (data.items || [])
		.filter((item) => !priorityPageIdSet.has(String(item.postId)))
		.filter((item) => matchesIndexability(item));
	const activeRunItems = activeMainTab === MAIN_TAB_PRIORITY ? filteredPriorityItems : nonPriorityItems;
	const paginationMeta = data?.pagination || {};
	const listHasPrevPage = Boolean(paginationMeta?.hasPrev);
	const listHasNextPage = Boolean(paginationMeta?.hasNext);
	const listTotalPages = Number(paginationMeta?.totalPages || 1);
	const listPageLabel = Number(paginationMeta?.page || currentPage);
	const priorityPerPage = 10;
	const priorityTotalPages = Math.max(1, Math.ceil(filteredPriorityItems.length / priorityPerPage));
	const priorityPageLabel = Math.min(priorityCurrentPage, priorityTotalPages);
	const priorityHasPrev = priorityPageLabel > 1;
	const priorityHasNext = priorityPageLabel < priorityTotalPages;
	const pagedPriorityItems = filteredPriorityItems.slice((priorityPageLabel - 1) * priorityPerPage, priorityPageLabel * priorityPerPage);
	const visibleRunItems = activeMainTab === MAIN_TAB_PRIORITY ? pagedPriorityItems : nonPriorityItems;
	const visibleRunPostIds = visibleRunItems.map((item) => String(item.postId));
	const visibleSelectedCount = visibleRunPostIds.filter((postId) => selectedPostIds.includes(postId)).length;
	const allVisibleSelected = visibleRunPostIds.length > 0 && visibleSelectedCount === visibleRunPostIds.length;
	const selectedInActiveTab = activeRunItems.filter((item) => selectedPostIds.includes(String(item.postId)));

	const selectAllVisible = () => {
		setSelectedPostIds((previous) => {
			const next = new Set(previous);
			visibleRunPostIds.forEach((postId) => next.add(postId));
			return Array.from(next);
		});
	};

	const clearVisibleSelection = () => {
		setSelectedPostIds((previous) => previous.filter((postId) => !visibleRunPostIds.includes(postId)));
	};

	const runSelectedBulkDiagnostics = async () => {
		if (isBulkAnalyzing || testingPostId) {
			return;
		}

		if (selectedInActiveTab.length < 1) {
			onStatus?.({
				tone: 'warning',
				text: __('Select one or more pages to run analysis.', 'asneris-seo-toolkit'),
			});
			return;
		}

		setIsBulkAnalyzing(true);
		setErrorMessage('');

		let successCount = 0;
		let failedCount = 0;

		for (const item of selectedInActiveTab) {
			try {
				await runPostDiagnostics(item.postId, { openReport: false, statusNotice: false });
				successCount += 1;
			} catch (error) {
				failedCount += 1;
			}
		}

		setIsBulkAnalyzing(false);

		if (successCount > 0) {
			onStatus?.({
				tone: failedCount > 0 ? 'warning' : 'success',
				text: `${ successCount } ${ __('page(s) analyzed successfully.', 'asneris-seo-toolkit') }${ failedCount > 0 ? ` ${ failedCount } ${ __('failed.', 'asneris-seo-toolkit') }` : '' }`,
			});
		}

		if (successCount < 1 && failedCount > 0) {
			onStatus?.({
				tone: 'error',
				text: __('Analysis failed for selected pages.', 'asneris-seo-toolkit'),
			});
		}

		loadOverview();
	};

	const pagedPriorityRows = pagedPriorityItems.map((item) => toItemRow(item));
	const nonPriorityRows = nonPriorityItems.map((item) => ({
		key: `non-priority-${ item.postId }`,
		mobileAccordionTitle: item.title || __('(Untitled)', 'asneris-seo-toolkit'),
		cells: [
			
			<div>
				{ item.url ? (
					<a href={ item.url } target="_blank" rel="noopener noreferrer">{ item.title || __('(Untitled)', 'asneris-seo-toolkit') }</a>
				) : (
					<span>{ item.title || __('(Untitled)', 'asneris-seo-toolkit') }</span>
				) }
				<div className="ASNERISSEO-react-muted">
					{ item.url ? String(item.url).replace(/^https?:\/\//i, '') : '-' }
				</div>
			</div>,
			`${ (item.postType || 'post').charAt(0).toUpperCase() }${ (item.postType || 'post').slice(1) }`,
			item?.author || '-',
			formatModifiedLabel(item.modifiedGmt),
			<button
				type="button"
				className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
				onClick={ () => runPostDiagnostics(item.postId) }
				disabled={ !!testingPostId || isLoading || isBulkAnalyzing }
			>
				{ testingPostId === item.postId ? __('Running...', 'asneris-seo-toolkit') : __('Run Diagnostics', 'asneris-seo-toolkit') }
			</button>,
		],
	}));
	const selectedSeoScore = deriveSeoScore(selectedResultItem);
	const selectedBackendSeoScoreMessage = typeof selectedResultItem?.seoScoreMessage === 'string'
		? selectedResultItem.seoScoreMessage.trim()
		: '';
	const selectedSeoSoftFailure = (!selectedResultItem?.isDraftQualityOnly && !hasDirectSeoScore(selectedResultItem)) || selectedBackendSeoScoreMessage.length > 0;
	const selectedSeoScoreMessage = selectedSeoSoftFailure
		? (selectedBackendSeoScoreMessage || SOFT_FAILURE_SCORE_MESSAGE)
		: '';
	const effectiveSelectedSeoScore = selectedSeoScore;
	const selectedHealth = deriveHealth(selectedResultItem);
	const selectedDetailSource = String(selectedResultItem?.source || '').trim().toLowerCase();
	const selectedDetailSourceLabel = selectedDetailSource === 'snapshot-skip' || selectedDetailSource === 'snapshot' || selectedDetailSource === 'latest-fallback'
		? __('Stored snapshot', 'asneris-seo-toolkit')
		: selectedDetailSource === 'editor-local-dirty'
			? __('Live draft (local)', 'asneris-seo-toolkit')
			: selectedDetailSource === 'editor-draft-policy'
				? __('Draft policy', 'asneris-seo-toolkit')
			: selectedDetailSource.includes('live-scan')
				? __('Live run', 'asneris-seo-toolkit')
				: __('Live run', 'asneris-seo-toolkit');
	const canonicalOverviewBreakdownRows = useMemo(() => {
		const sourceItem = selectedResultItem || {};
		const source = String(sourceItem?.source || '').trim().toLowerCase();
		const isSnapshotReview = source === 'snapshot' || source === 'snapshot-skip' || source === 'latest-fallback';
		const overviewStatusLookup = getTabIssueStatusLookup(sourceItem, 'overview');
		const sourceEntries = Object.entries(sourceItem || {});
		const resolveMappedNumericValue = (candidateKeys = []) => {
			for (const candidateKey of candidateKeys) {
				const normalizedKey = String(candidateKey || '').trim().toLowerCase();
				if (!normalizedKey) {
					continue;
				}

				const entry = sourceEntries.find(([rawKey]) => String(rawKey || '').trim().toLowerCase() === normalizedKey);
				if (!entry) {
					continue;
				}

				const numeric = Number(entry[1]);
				if (Number.isFinite(numeric) && numeric >= 0) {
					return Math.round(numeric);
				}
			}

			return null;
		};

		return (OVERVIEW_PRIMARY_FIELDS || []).map((field, index) => {
			const fieldLabel = normalizeOverviewPrimaryFieldLabel(field);
			const canonicalResolution = resolveRuleCanonicalField(fieldLabel);
			const canonicalField = canonicalResolution?.canonicalField || fieldLabel;
			const normalizedCanonicalField = String(canonicalField || '').trim().toLowerCase();
			const statusFromSnapshot = overviewStatusLookup.get(normalizedCanonicalField);
			const mappedFields = getCanonicalRawFields('overview', canonicalField);
			const mappedEvidence = formatEvidenceFromFields(mappedFields, sourceItem, {});
			const hasMappedEvidence = mappedFields.length > 0 && mappedEvidence !== '-';
			const derived = deriveProposedFieldFromSource(fieldLabel, sourceItem);
			const fallbackStatus = mapCheckStatus(derived?.status || 'not scanned');
			const mappedLengthValue = (canonicalField === 'SEO Title Length' || canonicalField === 'Meta Description Length')
				? resolveMappedNumericValue([
					...mappedFields,
					canonicalField === 'SEO Title Length' ? 'metaTitleLength' : 'metaDescriptionLength',
					canonicalField === 'SEO Title Length' ? 'titleLength' : 'descriptionLength',
					canonicalField === 'SEO Title Length' ? 'effectiveTitleLength' : 'effectiveDescriptionLength',
				])
				: null;
			const statusFromMappedLength = canonicalField === 'SEO Title Length'
				? (mappedLengthValue === null ? null : (mappedLengthValue >= 30 && mappedLengthValue <= 60 ? 'pass' : (mappedLengthValue > 0 ? 'warning' : 'fail')))
				: (canonicalField === 'Meta Description Length'
					? (mappedLengthValue === null ? null : (mappedLengthValue >= 120 && mappedLengthValue <= 160 ? 'pass' : (mappedLengthValue > 0 ? 'warning' : 'fail')))
					: null);
			let status = fallbackStatus;

			if (isSnapshotReview) {
				// Snapshot mode must trust persisted snapshot status and avoid live-evidence overrides.
				status = statusFromSnapshot || fallbackStatus;
			} else {
				// Live mode evaluates from current mapped evidence and derived values only.
				if (statusFromMappedLength) {
					status = statusFromMappedLength;
				}
				if (!hasMappedEvidence && fallbackStatus !== 'not_scanned') {
					status = 'not_scanned';
				}
			}

			const evidenceText = buildTransparencyExplanation(
				canonicalField,
				hasMappedEvidence ? mappedEvidence : resolveCanonicalEvidenceGapMessage(mappedFields, mappedEvidence),
				status
			);

			const impact = status === 'pass'
				? __('This field is currently healthy and aligned with SEO best practices.', 'asneris-seo-toolkit')
				: __('This can reduce visibility and user trust.', 'asneris-seo-toolkit');

			const recommendation = status === 'pass'
				? __('No action required. Recheck after major content or settings changes.', 'asneris-seo-toolkit')
				: `${ __('Review this finding and fix it in the page SEO settings or content editor.', 'asneris-seo-toolkit') } ${ __('Context:', 'asneris-seo-toolkit') } ${ evidenceText }`;

			const priority = status === 'pass'
				? __('Low', 'asneris-seo-toolkit')
				: (status === 'fail' ? __('High', 'asneris-seo-toolkit') : __('Medium', 'asneris-seo-toolkit'));

			return {
				key: `overview-canonical-issue-${ index }-${ fieldLabel }`,
				cells: [fieldLabel, impact, recommendation, priority],
				status,
			};
		});
	}, [selectedResultItem]);

	const canonicalOverviewTopIssues = useMemo(() => {
		const hasHigh = canonicalOverviewBreakdownRows.some((row) => String(row?.cells?.[3] || '').toLowerCase() === 'high');
		const hasMedium = canonicalOverviewBreakdownRows.some((row) => String(row?.cells?.[3] || '').toLowerCase() === 'medium');

		if (hasHigh) {
			return [
				{ label: __('Overview', 'asneris-seo-toolkit'), severity: 'High' },
			];
		}

		if (hasMedium) {
			return [
				{ label: __('Overview', 'asneris-seo-toolkit'), severity: 'Medium' },
			];
		}

		return [
			{ label: __('No major issues detected.', 'asneris-seo-toolkit'), severity: 'Low' },
		];
	}, [canonicalOverviewBreakdownRows]);

	const topPriorityItems = useMemo(() => {
		return canonicalOverviewBreakdownRows
			.filter((row) => {
				const priority = String(row?.cells?.[3] || '').toLowerCase();
				return priority === 'high' || priority === 'medium';
			})
			.map((row) => String(row?.cells?.[0] || '').trim())
			.filter(Boolean);
	}, [canonicalOverviewBreakdownRows]);
	const selectedIssueSummary = getIssueSummary(selectedResultItem || {});
	const isSnapshotSource = selectedDetailSource === 'snapshot-skip'
		|| selectedDetailSource === 'snapshot'
		|| selectedDetailSource === 'latest-fallback';
	const hasOverviewCriticalIssues = canonicalOverviewBreakdownRows.some((row) => String(row?.status || '').toLowerCase() === 'fail');
	const hasCriticalIssues = isSnapshotSource
		? hasOverviewCriticalIssues
		: (selectedIssueSummary.critical > 0 || hasOverviewCriticalIssues);
	const showGoodNews = (selectedResultItem?.robotsIndex || 'index') === 'index' && !hasCriticalIssues;
	const activeDetailRows = useMemo(
		() => getRowsByDetailTab(categorizedReport, activeDetailTab, selectedResultItem || selectedResult || {}),
		[categorizedReport, activeDetailTab, selectedResultItem, selectedResult]
	);
	const historyRows = historyItems.map((row) => {
		const canDeleteRow = Number(row?.id) > 0;

		return {
			key: `history-${ row.id }`,
			cells: [
				formatDateTimeLabel(row.generatedAtGmt || row.createdAt),
				`${ Number(row.seoScore || 0) }/100`,
				String(row.health || '-').charAt(0).toUpperCase() + String(row.health || '-').slice(1),
				canDeleteRow ? (
					<button
						type="button"
						className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
						onClick={ () => deleteHistoryRecord(row.id) }
						disabled={ deletingHistoryId === row.id || isHistoryLoading }
					>
						{ deletingHistoryId === row.id ? __('Deleting...', 'asneris-seo-toolkit') : __('Delete', 'asneris-seo-toolkit') }
					</button>
				) : <span className="ASNERISSEO-react-muted">{ __('Latest', 'asneris-seo-toolkit') }</span>,
			],
		};
	});
	const detailTabHistoryRows = useMemo(() => {
		if (activeDetailTab === DETAIL_TAB_OVERVIEW) {
			return [];
		}

		return historyItems.map((item, index) => {
			const counts = buildCanonicalHistoryCounts(item, activeDetailTab);
			const uxMeta = buildCanonicalHistoryUxMeta(item, activeDetailTab);
			const coverageClassName = `ASNERISSEO-react-status-chip is-${ uxMeta.coverageTone }`;

			return {
				key: 'detail-tab-history-' + (item?.id || index) + '-' + activeDetailTab,
				cells: [
					formatDateTimeLabel(item?.generatedAtGmt || item?.createdAt),
					<span className={ coverageClassName }>{ uxMeta.coverage }</span>,
					uxMeta.sourceLabel,
					String(counts.issues) + '/' + String(counts.total),
					String(counts.pass),
					String(counts.warning),
					String(counts.fail),
				],
			};
		});
	}, [historyItems, activeDetailTab]);
	const historyTrend = useMemo(() => {
		if (!Array.isArray(historyItems) || historyItems.length < 2) {
			return null;
		}

		const normalized = historyItems
			.map((item) => {
				const score = Number(item?.seoScore);
				const rawDate = item?.generatedAtGmt || item?.createdAt;
				const timestamp = new Date(rawDate || '').getTime();
				if (!Number.isFinite(score) || Number.isNaN(timestamp)) {
					return null;
				}
				return {
					score,
					timestamp,
				};
			})
			.filter(Boolean)
			.sort((a, b) => a.timestamp - b.timestamp);

		if (normalized.length < 2) {
			return null;
		}

		const first = normalized[0];
		const last = normalized[normalized.length - 1];
		const delta = Math.round((last.score - first.score) * 10) / 10;
		const days = Math.max(1, (last.timestamp - first.timestamp) / (1000 * 60 * 60 * 24));
		const avgPerDay = Math.round((delta / days) * 10) / 10;
		const best = Math.max(...normalized.map((item) => item.score));
		const worst = Math.min(...normalized.map((item) => item.score));
		const direction = delta > 0 ? 'up' : (delta < 0 ? 'down' : 'flat');

		return {
			delta,
			avgPerDay,
			best,
			worst,
			direction,
			sampleCount: normalized.length,
		};
	}, [historyItems]);

	const popupHistorySeries = useMemo(() => {
		return (historyPopupItems || [])
			.map((row) => {
				const categoryBreakdown = HISTORY_CATEGORY_KEYS.reduce((acc, key) => {
					const canonicalCounts = buildCanonicalHistoryCounts(row, key);
					acc[key] = canonicalCounts;
					return acc;
				}, {});

				const counts = Object.values(categoryBreakdown).reduce((acc, bucket) => {
					acc.pass += Number(bucket?.pass || 0);
					acc.warning += Number(bucket?.warning || 0);
					acc.fail += Number(bucket?.fail || 0);
					return acc;
				}, { pass: 0, warning: 0, fail: 0 });

				const generatedAt = row?.generatedAtGmt || row?.createdAt || '';
				const generatedDate = new Date(generatedAt);
				const timestamp = generatedDate.getTime();
				const categoryRates = HISTORY_CATEGORY_KEYS.reduce((acc, key) => {
					const total = Number(categoryBreakdown?.[key]?.total || 0);
					const pass = Number(categoryBreakdown?.[key]?.pass || 0);
					acc[key] = total > 0 ? Math.round((pass / total) * 100) : 0;
					return acc;
				}, {});

				return {
					id: Number(row?.id || 0),
					score: Number(row?.seoScore || 0),
					health: String(row?.health || 'warning'),
					generatedAt,
					timestamp: Number.isNaN(timestamp) ? 0 : timestamp,
					counts,
					categoryRates,
					canonicalRows: buildCanonicalComparisonRowsFromSnapshot(row),
				};
			})
			.filter((row) => row.timestamp > 0)
			.sort((a, b) => a.timestamp - b.timestamp);
	}, [historyPopupItems]);

	const popupCurrent = popupHistorySeries.length > 0 ? popupHistorySeries[popupHistorySeries.length - 1] : null;
	const popupPrevious = popupHistorySeries.length > 1 ? popupHistorySeries[popupHistorySeries.length - 2] : null;

	const popupCategoryRows = useMemo(() => {
		return HISTORY_CATEGORY_KEYS.map((key) => {
			const currentValue = Number(popupCurrent?.categoryRates?.[key] || 0);
			const previousValue = Number(popupPrevious?.categoryRates?.[key] || 0);
			const delta = currentValue - previousValue;

			return {
				key,
				label: HISTORY_CATEGORY_LABELS[key],
				currentValue,
				previousValue,
				delta,
			};
		});
	}, [popupCurrent, popupPrevious]);

	const popupWhatChanged = useMemo(() => {
		const improved = [];
		const needs = [];

		popupCategoryRows.forEach((row) => {
			if (row.delta > 0) {
				improved.push(`${ row.label } +${ row.delta }%`);
			}
			if (row.delta < 0) {
				needs.push(`${ row.label } ${ row.delta }%`);
			}
		});

		if (popupCurrent?.counts?.warning > 0) {
			needs.push(`${ popupCurrent.counts.warning } ${ __('warnings in latest report', 'asneris-seo-toolkit') }`);
		}

		if (popupCurrent?.counts?.fail > 0) {
			needs.push(`${ popupCurrent.counts.fail } ${ __('failed checks in latest report', 'asneris-seo-toolkit') }`);
		}

		if (!improved.length) {
			improved.push(__('No major regressions detected.', 'asneris-seo-toolkit'));
		}

		if (!needs.length) {
			needs.push(__('No urgent actions required.', 'asneris-seo-toolkit'));
		}

		return {
			improved: improved.slice(0, 4),
			needs: needs.slice(0, 4),
		};
	}, [popupCategoryRows, popupCurrent]);

	const popupTimelineRows = useMemo(() => {
		const descending = [ ...popupHistorySeries ].sort((a, b) => b.timestamp - a.timestamp);
		return descending.map((item, index) => {
			const nextItem = descending[index + 1] || null;
			const delta = nextItem ? item.score - nextItem.score : null;

			return {
				key: `popup-history-${ item.id }-${ index }`,
				cells: [
					index === 0 ? __('Current', 'asneris-seo-toolkit') : String(descending.length - index),
					formatDateTimeLabel(item.generatedAt),
					String(item.score),
					getHealthLabel(item.health),
					String(item.counts.pass),
					String(item.counts.warning),
					String(item.counts.fail),
					delta === null ? '—' : `${ delta > 0 ? '+' : '' }${ delta }`,
				],
			};
		});
	}, [popupHistorySeries]);

	const comparisonModel = useMemo(() => {
		const currentSnapshot = popupHistorySeries.length > 0 ? popupHistorySeries[popupHistorySeries.length - 1] : null;
		const previousSnapshot = popupHistorySeries.length > 1 ? popupHistorySeries[popupHistorySeries.length - 2] : null;
		if (!currentSnapshot || !previousSnapshot) {
			return null;
		}

		const currentRows = Array.isArray(currentSnapshot.canonicalRows) ? currentSnapshot.canonicalRows : [];
		const previousRows = Array.isArray(previousSnapshot.canonicalRows) ? previousSnapshot.canonicalRows : [];
		const currentMap = new Map(currentRows.map((row) => [ row.key, row ]));
		const previousMap = new Map(previousRows.map((row) => [ row.key, row ]));
		const rowKeys = Array.from(new Set([ ...currentMap.keys(), ...previousMap.keys() ])).filter(Boolean);
		const statusScore = { not_scanned: 0, fail: 0, warning: 1, pass: 2 };

		const rows = rowKeys.map((key) => {
			const currentRow = currentMap.get(key) || null;
			const previousRow = previousMap.get(key) || null;
			const label = String(currentRow?.label || previousRow?.label || '').trim();
			const category = String(currentRow?.category || previousRow?.category || '');
			const previousStatus = mapCheckStatus(previousRow?.status || 'not scanned');
			const currentStatus = mapCheckStatus(currentRow?.status || 'not scanned');
			let changeType = COMPARISON_FILTER_NO_CHANGE;

			if (statusScore[currentStatus] > statusScore[previousStatus]) {
				changeType = COMPARISON_FILTER_IMPROVED;
			} else if (statusScore[currentStatus] < statusScore[previousStatus]) {
				changeType = COMPARISON_FILTER_REGRESSED;
			}

			return {
				label,
				category,
				previousStatus,
				currentStatus,
				changeType,
				previousValue: formatCheckStatusLabel(previousStatus),
				currentValue: formatCheckStatusLabel(currentStatus),
				changeText: toComparisonChangeLabel(changeType),
			};
		});

		const visibleRows = rows.filter((row) => COMPARISON_CATEGORY_ORDER.includes(row.category));

		const grouped = COMPARISON_CATEGORY_ORDER
			.map((key) => ({
				key,
				label: COMPARISON_CATEGORY_LABELS[key] || key,
				rows: visibleRows.filter((row) => row.category === key),
			}))
			.filter((group) => group.rows.length > 0);

		const counts = visibleRows.reduce((acc, row) => {
			if (row.changeType === COMPARISON_FILTER_IMPROVED) {
				acc.improved += 1;
			} else if (row.changeType === COMPARISON_FILTER_REGRESSED) {
				acc.regressed += 1;
			} else {
				acc.noChange += 1;
			}

			return acc;
		}, { improved: 0, regressed: 0, noChange: 0 });

		const previousCounts = visibleRows.reduce((acc, row) => {
			if (row.previousStatus === 'pass') {
				acc.pass += 1;
			} else if (row.previousStatus === 'warning') {
				acc.warning += 1;
			} else if (row.previousStatus === 'fail') {
				acc.fail += 1;
			}

			return acc;
		}, { pass: 0, warning: 0, fail: 0 });

		const currentCounts = visibleRows.reduce((acc, row) => {
			if (row.currentStatus === 'pass') {
				acc.pass += 1;
			} else if (row.currentStatus === 'warning') {
				acc.warning += 1;
			} else if (row.currentStatus === 'fail') {
				acc.fail += 1;
			}

			return acc;
		}, { pass: 0, warning: 0, fail: 0 });

		const total = currentCounts.pass + currentCounts.warning + currentCounts.fail;

		return {
			currentSnapshot,
			previousSnapshot,
			grouped,
			counts,
			previousCounts,
			currentCounts,
			total,
		};
	}, [popupHistorySeries]);

	const popupScoreChart = useMemo(() => {
		const series = popupHistorySeries.map((item) => ({
			id: `score-${ item.id }-${ item.timestamp }`,
			label: formatChartDateLabel(item.generatedAt),
			value: Number(item.score || 0),
		}));

		return buildLineChartModel(series, { yMax: 100, width: 640, height: 220 });
	}, [popupHistorySeries]);

	const popupIssueChart = useMemo(() => {
		const warningSeries = popupHistorySeries.map((item) => ({
			id: `warning-${ item.id }-${ item.timestamp }`,
			label: formatChartDateLabel(item.generatedAt),
			value: Number(item.counts.warning || 0),
		}));
		const failSeries = popupHistorySeries.map((item) => ({
			id: `fail-${ item.id }-${ item.timestamp }`,
			label: formatChartDateLabel(item.generatedAt),
			value: Number(item.counts.fail || 0),
		}));

		const yMax = Math.max(
			6,
			...warningSeries.map((item) => item.value),
			...failSeries.map((item) => item.value)
		);

		return {
			warning: buildLineChartModel(warningSeries, { yMax, width: 640, height: 210 }),
			fail: buildLineChartModel(failSeries, { yMax, width: 640, height: 210 }),
			yMax,
		};
	}, [popupHistorySeries]);
	const activeIssueListRows = useMemo(() => {
		const primaryFields = PRIMARY_LIST_FIELDS_BY_TAB[activeDetailTab];
		if (!Array.isArray(primaryFields) || primaryFields.length < 1) {
			return activeDetailRows;
		}

		const rowByNormalizedLabel = new Map(
			(Array.isArray(activeDetailRows) ? activeDetailRows : []).map((row) => {
				const normalized = normalizeFieldForRuleTab(activeDetailTab, String(row?.cells?.[0] || ''));
				return [normalized, row];
			}).filter(([normalized]) => normalized)
		);

		return primaryFields.map((fieldLabel) => {
			const normalizedLabel = normalizeFieldForRuleTab(activeDetailTab, String(fieldLabel || ''));
			if (rowByNormalizedLabel.has(normalizedLabel)) {
				const resolvedRow = rowByNormalizedLabel.get(normalizedLabel);
				return {
					...resolvedRow,
					cells: [
						fieldLabel,
						resolvedRow?.cells?.[1] || 'not scanned',
						resolvedRow?.cells?.[2] || __('Not checked', 'asneris-seo-toolkit'),
						resolvedRow?.cells?.[3] || sanitizeUiEvidenceText(CANONICAL_MAPPING_GAP_MESSAGE),
					],
				};
			}

			return {
				cells: [
					fieldLabel,
					'not scanned',
					__('Not checked', 'asneris-seo-toolkit'),
					sanitizeUiEvidenceText(CANONICAL_MAPPING_GAP_MESSAGE),
				],
				sectionKey: DETAIL_TAB_SECTION_KEY_MAP[activeDetailTab] || 'overview',
				label: String(fieldLabel).toLowerCase(),
				isBaseline: true,
			};
		});
	}, [activeDetailRows, activeDetailTab, selectedResultItem]);

	const activeDetailIssues = activeIssueListRows.filter((row) => {
		const status = String(row?.cells?.[1] || '').toLowerCase();
		return status === 'warning' || status === 'fail';
	}).length;
	const activeDetailCard = buildTabCardModel(activeDetailTab, selectedResultItem, activeDetailRows);
	const activeDetailCardScore = activeDetailCard.score;
	const activeDetailCardScoreLabel = Number.isFinite(activeDetailCardScore)
		? `${ activeDetailCardScore }/100`
		: '-';
	const rawSummaryHighlights = activeDetailCard.summaryHighlights || activeDetailCard.highlights || [];
	const activeSummaryHighlights = DETAIL_TAB_HIDE_MATCH_COUNT.has(activeDetailTab)
		? rawSummaryHighlights.filter((entry) => entry?.kind !== 'match-count')
		: rawSummaryHighlights;
	const hasSummaryHighlightSources = activeSummaryHighlights.some((entry) => entry?.source);
	const activeDetailHighlights = activeDetailCard.detailHighlights || activeDetailCard.highlights || [];
	const useDetailIssueAccordion = activeDetailTab !== DETAIL_TAB_OVERVIEW && activeDetailTab !== DETAIL_TAB_SEARCH_APPEARANCE;
	const activeSearchAppearanceVisual = activeDetailTab === DETAIL_TAB_SEARCH_APPEARANCE
		? buildSearchAppearanceVisualModel(activeDetailRows, selectedResultItem)
		: null;
	const socialImageFallbackTitle = String(
		activeSearchAppearanceVisual?.socialTitle ||
		activeSearchAppearanceVisual?.googleTitle ||
		''
	).trim();

	const hasSocialImageTemplate = Boolean(
		activeSearchAppearanceVisual?.socialImage || socialImageFallbackTitle
	);
	const resolveSearchAppearanceSourceTone = (sourceLabel) => {
		const normalized = String(sourceLabel || '').toLowerCase();
		if (normalized.includes('customer key-in')) {
			return 'keyin';
		}
		if (normalized.includes('missing')) {
			return 'missing';
		}
		if (normalized.includes('template')) {
			return 'template';
		}
		return 'fallback';
	};
	const searchAppearanceSourceRows = activeSearchAppearanceVisual
		? [
			{
				label: __('Search Engine: Title', 'asneris-seo-toolkit'),
				value: activeSearchAppearanceVisual.googleTitle || '-',
				source: activeSearchAppearanceVisual.searchTitleSource || __('Fallback', 'asneris-seo-toolkit'),
				sourceTone: resolveSearchAppearanceSourceTone(activeSearchAppearanceVisual.searchTitleSource || __('Fallback', 'asneris-seo-toolkit')),
			},
			{
				label: __('Search Engine: Description', 'asneris-seo-toolkit'),
				value: activeSearchAppearanceVisual.googleDescription || '-',
				source: activeSearchAppearanceVisual.searchDescriptionSource || __('Fallback', 'asneris-seo-toolkit'),
				sourceTone: resolveSearchAppearanceSourceTone(activeSearchAppearanceVisual.searchDescriptionSource || __('Fallback', 'asneris-seo-toolkit')),
			},
			{
				label: __('Social Media: Title', 'asneris-seo-toolkit'),
				value: activeSearchAppearanceVisual.socialTitle || activeSearchAppearanceVisual.googleTitle || '-',
				source: activeSearchAppearanceVisual.socialTitleSource || __('Template / fallback', 'asneris-seo-toolkit'),
				sourceTone: resolveSearchAppearanceSourceTone(activeSearchAppearanceVisual.socialTitleSource || __('Template / fallback', 'asneris-seo-toolkit')),
			},
			{
				label: __('Social Media: Description', 'asneris-seo-toolkit'),
				value: activeSearchAppearanceVisual.socialDescription || activeSearchAppearanceVisual.googleDescription || '-',
				source: activeSearchAppearanceVisual.socialDescriptionSource || __('Template / fallback', 'asneris-seo-toolkit'),
				sourceTone: resolveSearchAppearanceSourceTone(activeSearchAppearanceVisual.socialDescriptionSource || __('Template / fallback', 'asneris-seo-toolkit')),
			},
			{
				label: __('Social Media: Image', 'asneris-seo-toolkit'),
				value: activeSearchAppearanceVisual.socialImage || __('Missing', 'asneris-seo-toolkit'),
				source: activeSearchAppearanceVisual.socialImageSource || __('Missing', 'asneris-seo-toolkit'),
				sourceTone: resolveSearchAppearanceSourceTone(activeSearchAppearanceVisual.socialImageSource || __('Missing', 'asneris-seo-toolkit')),
			},
		]
		: [];
	const isSearchAppearanceMobile = isMobileViewport || searchAppearanceViewport === 'mobile';
	const activeDetailTabLabel = DETAIL_VIEW_TABS.find((tab) => tab.key === activeDetailTab)?.label || __('Details', 'asneris-seo-toolkit');
	const canonicalMappingGaps = useMemo(() => getCanonicalMappingGaps(CANONICAL_MAPPING_GAP_SOURCE), []);
	const canonicalMappingGapCount = canonicalMappingGaps.length;
	const discoverabilityInvariantViolations = useMemo(
		() => getDiscoverabilityModelInvariantViolations(),
		[]
	);
	const runtimeCoverageGaps = useMemo(() => {
		const hasSelectedChecks = Array.isArray(selectedResult?.checks) && selectedResult.checks.length > 0;
		if (!selectedResultItem || !hasSelectedChecks) {
			return [];
		}

		const sourceItem = selectedResultItem || {};
		const gaps = [];

		Object.entries(TAB_FIELD_REGISTRY || {}).forEach(([tabKey, fields]) => {
			(fields || []).forEach((fieldLabel) => {
				const canonicalField = String(fieldLabel || '').trim();
				if (!canonicalField) {
					return;
				}

				const normalizedStatus = mapCheckStatus(deriveProposedFieldFromSource(canonicalField, sourceItem)?.status || 'not scanned');
				const mappedFields = getCanonicalRawFields(tabKey, canonicalField);
				const mappedSummary = formatEvidenceFromFields(mappedFields, sourceItem, {});
				const hasMappedEvidence = Array.isArray(mappedFields) && mappedFields.length > 0 && mappedSummary !== '-';

				if (normalizedStatus === 'not_scanned' || !hasMappedEvidence) {
					gaps.push({
						tabKey,
						canonicalField,
						status: normalizedStatus,
						hasMappedEvidence,
					});
				}
			});
		});

		return gaps;
	}, [selectedResult, selectedResultItem]);
	const runtimeStatusParityMismatches = useMemo(() => {
		const hasSelectedChecks = Array.isArray(selectedResult?.checks) && selectedResult.checks.length > 0;
		if (!selectedResultItem || !hasSelectedChecks) {
			return [];
		}

		const sourceItem = selectedResultItem || {};
		const scanStatusByField = new Map();
		const statusRank = { pass: 1, warning: 2, fail: 3 };

		(selectedResult?.checks || []).forEach((check) => {
			const resolution = resolveRuleCanonicalField(check?.label || '');
			const canonicalField = String(resolution?.canonicalField || '').trim();
			const tabKey = String(resolution?.canonicalMapKey || '').trim();
			if (!canonicalField || !tabKey) {
				return;
			}

			const tabFields = Array.isArray(TAB_FIELD_REGISTRY?.[tabKey]) ? TAB_FIELD_REGISTRY[tabKey] : [];
			if (!tabFields.includes(canonicalField)) {
				return;
			}

			const normalizedStatus = mapCheckStatus(check?.status || 'not scanned');
			if (normalizedStatus === 'not_scanned') {
				return;
			}

			const key = `${ tabKey }::${ canonicalField }`;
			const existing = scanStatusByField.get(key);
			if (!existing || (statusRank[normalizedStatus] || 0) > (statusRank[existing.status] || 0)) {
				scanStatusByField.set(key, { tabKey, canonicalField, status: normalizedStatus });
			}
		});

		const mismatches = [];
		scanStatusByField.forEach((entry) => {
			const derivedStatus = mapCheckStatus(deriveProposedFieldFromSource(entry.canonicalField, sourceItem)?.status || 'not scanned');
			if (derivedStatus === 'not_scanned') {
				return;
			}

			if (derivedStatus !== entry.status) {
				mismatches.push({
					tabKey: entry.tabKey,
					canonicalField: entry.canonicalField,
					scanStatus: entry.status,
					derivedStatus,
				});
			}
		});

		return mismatches;
	}, [selectedResult, selectedResultItem]);
	const invariantViolationCount = discoverabilityInvariantViolations.length;
	const runtimeCoverageGapCount = runtimeCoverageGaps.length;
	const runtimeStatusParityMismatchCount = runtimeStatusParityMismatches.length;
	const runtimeCoverageSummaryByTab = useMemo(() => {
		const summaries = [];
		Object.entries(TAB_FIELD_REGISTRY || {}).forEach(([tabKey, fields]) => {
			const total = Array.isArray(fields) ? fields.length : 0;
			const gapCount = runtimeCoverageGaps.filter((gap) => gap?.tabKey === tabKey).length;
			const covered = Math.max(0, total - gapCount);
			const tabLabel = CANONICAL_MAPPING_TAB_LABELS[tabKey] || String(tabKey || __('Unknown', 'asneris-seo-toolkit'));
			summaries.push({
				tabKey,
				tabLabel,
				total,
				covered,
				gapCount,
			});
		});

		return summaries;
	}, [runtimeCoverageGaps]);
	const modelContractViolationItems = useMemo(() => {
		const statusParityItems = runtimeStatusParityMismatches.map((mismatch, index) => {
			const tabLabel = CANONICAL_MAPPING_TAB_LABELS[mismatch?.tabKey] || String(mismatch?.tabKey || __('Unknown', 'asneris-seo-toolkit'));
			const fieldLabel = String(mismatch?.canonicalField || __('Unknown field', 'asneris-seo-toolkit'));
			return {
				key: `runtime-status-parity-${ index }`,
				label: `${ tabLabel }: ${ fieldLabel } (scan=${ mismatch?.scanStatus }, derived=${ mismatch?.derivedStatus })`,
			};
		});

		const runtimeCoverageItems = runtimeCoverageGaps.map((gap, index) => {
			const tabLabel = CANONICAL_MAPPING_TAB_LABELS[gap?.tabKey] || String(gap?.tabKey || __('Unknown', 'asneris-seo-toolkit'));
			const fieldLabel = String(gap?.canonicalField || __('Unknown field', 'asneris-seo-toolkit'));
			const reasonLabel = gap?.status === 'not_scanned'
				? __('not scanned', 'asneris-seo-toolkit')
				: __('missing mapped evidence', 'asneris-seo-toolkit');
			return {
				key: `runtime-coverage-gap-${ index }`,
				label: `${ tabLabel }: ${ fieldLabel } (${ reasonLabel })`,
			};
		});

		const mappedGapItems = canonicalMappingGaps.map((gap, index) => {
			const tabLabel = CANONICAL_MAPPING_TAB_LABELS[gap?.tabKey] || String(gap?.tabKey || __('Unknown', 'asneris-seo-toolkit'));
			const fieldLabel = String(gap?.canonicalField || __('Unknown field', 'asneris-seo-toolkit'));
			return {
				key: `mapping-gap-${ gap?.tabKey || 'unknown' }-${ fieldLabel }-${ index }`,
				label: `${ tabLabel }: ${ fieldLabel }`,
			};
		});

		const mappedInvariantItems = discoverabilityInvariantViolations.map((violation, index) => {
			const tabLabel = CANONICAL_MAPPING_TAB_LABELS[violation?.tabKey] || String(violation?.tabKey || __('Unknown', 'asneris-seo-toolkit'));
			const fieldLabel = String(violation?.canonicalField || __('Unknown field', 'asneris-seo-toolkit'));
			const typeLabel = String(violation?.type || 'invariant').replace(/-/g, ' ');
			return {
				key: `invariant-violation-${ index }`,
				label: `${ tabLabel }: ${ fieldLabel } (${ typeLabel })`,
			};
		});

		return [ ...mappedGapItems, ...mappedInvariantItems, ...runtimeCoverageItems, ...statusParityItems ].slice(0, 20);
	}, [canonicalMappingGaps, discoverabilityInvariantViolations, runtimeCoverageGaps, runtimeStatusParityMismatches]);
	const hasModelContractViolations = invariantViolationCount > 0 || canonicalMappingGapCount > 0 || runtimeCoverageGapCount > 0 || runtimeStatusParityMismatchCount > 0;

	useEffect(() => {
		if (invariantViolationCount < 1) {
			return;
		}

		// Keep this visible in dev tools so schema/routing drift is caught early.
		// eslint-disable-next-line no-console
		console.warn('[AsnerisSEO] Discoverability model invariant violations detected', discoverabilityInvariantViolations);
	}, [discoverabilityInvariantViolations, invariantViolationCount]);

	useEffect(() => {
		if (!hasModelContractViolations) {
			return;
		}

		// Keep hardening diagnostics internal; do not block end-user report usage.
		// eslint-disable-next-line no-console
		console.warn('[AsnerisSEO] Contract parity diagnostics detected', {
			canonicalMappingGapCount,
			invariantViolationCount,
			runtimeCoverageGapCount,
			runtimeStatusParityMismatchCount,
			items: modelContractViolationItems,
		});
	}, [
		hasModelContractViolations,
		canonicalMappingGapCount,
		invariantViolationCount,
		runtimeCoverageGapCount,
		runtimeStatusParityMismatchCount,
		modelContractViolationItems,
	]);

	useEffect(() => {
		setSelectedPostId((previous) => {
			if (previous && activeRunItems.some((item) => String(item.postId) === String(previous))) {
				return previous;
			}

			return activeRunItems[0]?.postId ? String(activeRunItems[0].postId) : '';
		});
	}, [activeRunItems]);

		useEffect(() => {
			if (!pendingHistoryJump || activeDetailTab !== DETAIL_TAB_OVERVIEW) {
				return;
			}

			const timer = window.setTimeout(() => {
				historySectionRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
				setPendingHistoryJump(false);
			}, 0);

			return () => window.clearTimeout(timer);
		}, [pendingHistoryJump, activeDetailTab]);

		useEffect(() => {
			if (!isReportDialogOpen || !selectedResult?.postId) {
				return;
			}

			    setIsDetailHeaderCollapsed(true);
		}, [isReportDialogOpen, selectedResult?.postId]);

		useEffect(() => {
			if (!isReportDialogOpen) {
				setIsCanonicalDebugOpen(false);
			}
		}, [isReportDialogOpen]);

		useEffect(() => {
			setActiveDetailContentSection(DETAIL_CONTENT_SECTION_DETAILS);
		}, [activeDetailTab, selectedResult?.postId]);

		useEffect(() => {
			if (typeof window === 'undefined') {
				return undefined;
			}

			const onResize = () => {
				setIsMobileViewport(window.innerWidth <= 782);
			};

			onResize();
			window.addEventListener('resize', onResize);

			return () => window.removeEventListener('resize', onResize);
		}, []);

		const jumpToSnapshotHistory = () => {
			setPendingHistoryJump(true);
			setActiveDetailTab(DETAIL_TAB_OVERVIEW);
		};

		const handleDetailTabsWheel = useCallback((event) => {
			const tabsNode = detailTabsRef.current;
			if (!tabsNode) {
				return;
			}

			const canScrollHorizontally = tabsNode.scrollWidth > (tabsNode.clientWidth + 1);
			if (!canScrollHorizontally) {
				return;
			}

			const dominantDelta = Math.abs(event.deltaX) > Math.abs(event.deltaY)
				? event.deltaX
				: event.deltaY;

			if (Math.abs(dominantDelta) < 1) {
				return;
			}

			tabsNode.scrollLeft += dominantDelta;
			if (event.cancelable) {
				event.preventDefault();
			}
		}, []);

		const handleDetailTabsPointerDown = useCallback((event) => {
			if (event.pointerType === 'mouse' && event.button !== 0) {
				return;
			}

			const tabsNode = detailTabsRef.current;
			if (!tabsNode) {
				return;
			}

			const canScrollHorizontally = tabsNode.scrollWidth > (tabsNode.clientWidth + 1);
			if (!canScrollHorizontally) {
				return;
			}

			detailTabsDragStateRef.current = {
				isDragging: true,
				startX: event.clientX,
				startScrollLeft: tabsNode.scrollLeft,
				hasMoved: false,
				suppressClick: false,
			};
		}, []);

		const handleDetailTabsPointerMove = useCallback((event) => {
			const tabsNode = detailTabsRef.current;
			const dragState = detailTabsDragStateRef.current;

			if (!tabsNode || !dragState.isDragging) {
				return;
			}

			const deltaX = event.clientX - dragState.startX;
			if (!dragState.hasMoved && Math.abs(deltaX) >= 4) {
				detailTabsDragStateRef.current = {
					...dragState,
					hasMoved: true,
					suppressClick: true,
				};
				tabsNode.classList.add('is-drag-scrolling');
			}

			if (!detailTabsDragStateRef.current.hasMoved) {
				return;
			}

			tabsNode.scrollLeft = dragState.startScrollLeft - deltaX;
			if (event.cancelable) {
				event.preventDefault();
			}
		}, []);

		const stopDetailTabsDrag = useCallback((event) => {
			const tabsNode = detailTabsRef.current;
			if (tabsNode) {
				tabsNode.classList.remove('is-drag-scrolling');
			}

			detailTabsDragStateRef.current = {
				isDragging: false,
				startX: 0,
				startScrollLeft: 0,
				hasMoved: false,
				suppressClick: detailTabsDragStateRef.current.suppressClick,
			};
		}, []);

		const handleDetailTabsClickCapture = useCallback((event) => {
			if (!detailTabsDragStateRef.current.suppressClick) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			detailTabsDragStateRef.current = {
				...detailTabsDragStateRef.current,
				suppressClick: false,
			};
		}, []);

		const panelClassName = [
			'ASNERISSEO-react-data-panel',
			embeddedInEditorModal ? 'ASNERISSEO-react-pd-embedded' : '',
			embeddedInEditorModal && isReportDialogOpen && selectedResult ? 'is-report-open' : '',
		].filter(Boolean).join(' ');
		const panelTitle = embeddedInEditorModal ? '' : __('Page Diagnostics', 'asneris-seo-toolkit');
		const modalLogoUrl = String(window.asnerisseoAdminDashboardData?.logoUrl || window.asnerisseoData?.logoUrl || '').trim();
		const detailDialogClassName = 'ASNERISSEO-modal ASNERISSEO-modal-large ASNERISSEO-react-detail-modal';

	return (
		<PanelScaffold
				title={ panelTitle }
		>
				{ !embeddedInEditorModal ? (
					<InlineHelpDetails
						title={ __('Help: Page Diagnostics', 'asneris-seo-toolkit') }
						items={ [
							__('Use search and content-type filters to find pages/posts quickly.', 'asneris-seo-toolkit'),
							__('Run diagnostics per row for targeted checks before bulk updates.', 'asneris-seo-toolkit'),
							__('Review Latest Diagnostics Result to inspect check details and statuses.', 'asneris-seo-toolkit'),
						] }
						note={ __('Results are diagnostic guidance and do not guarantee ranking changes.', 'asneris-seo-toolkit') }
					/>
				) : null }
			<div className={ panelClassName }>
			<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mb-10">
				<p className="ASNERISSEO-react-note-box-title">{ __('Priority Tab Feature', 'asneris-seo-toolkit') }</p>
				<p className="ASNERISSEO-react-mb-0">
					{ priorityFeatureEnabled
						? __('Feature: ON. Priority and Non-Priority tabs are available.', 'asneris-seo-toolkit')
						: __('Feature: OFF. Priority tab is disabled from Settings > Priority Pages.', 'asneris-seo-toolkit') }
				</p>
			</div>
			<div className="ASNERISSEO-react-tabs ASNERISSEO-react-tabs-strip ASNERISSEO-react-block" role="tablist" aria-label={ __('Page Diagnostics Tabs', 'asneris-seo-toolkit') }>
				<button
					type="button"
					role="tab"
					aria-selected={ activeMainTab === MAIN_TAB_PRIORITY }
					aria-disabled={ !priorityFeatureEnabled }
					className={ `ASNERISSEO-react-tab${ activeMainTab === MAIN_TAB_PRIORITY ? ' is-active' : '' }` }
					disabled={ !priorityFeatureEnabled }
					onClick={ () => {
						if (!priorityFeatureEnabled) {
							return;
						}
						setCurrentPage(1);
						setActiveMainTab(MAIN_TAB_PRIORITY);
					} }
				>
					{ __('Priority Pages', 'asneris-seo-toolkit') }
				</button>
				<button
					type="button"
					role="tab"
					aria-selected={ activeMainTab === MAIN_TAB_NON_PRIORITY }
					className={ `ASNERISSEO-react-tab${ activeMainTab === MAIN_TAB_NON_PRIORITY ? ' is-active' : '' }` }
					onClick={ () => {
						setCurrentPage(1);
						setActiveMainTab(MAIN_TAB_NON_PRIORITY);
					} }
				>
					{ __('Non-Priority Pages', 'asneris-seo-toolkit') }
				</button>
			</div>
			{ isLoading ? <p>{ __('Loading diagnostics overview...', 'asneris-seo-toolkit') }</p> : null }
			{ errorMessage ? <p className="ASNERISSEO-react-text-danger">{ errorMessage }</p> : null }

			{ lastPerformance ? (
				<PerformanceTrackerCard
					title={ __('Run Performance', 'asneris-seo-toolkit') }
					statusLabel={ formatPerformanceStatus(lastPerformance?.status) }
					statusClassName={ toStatusChipClass(lastPerformance?.status) }
					advisoryMessage={ lastPerformance?.advisory?.recommendNotToRun
						? (lastPerformance?.advisory?.reason || __('Server memory headroom is low. We recommend not running this analysis now.', 'asneris-seo-toolkit'))
						: '' }
					performance={ lastPerformance }
					modalTitle={ __('Performance Details', 'asneris-seo-toolkit') }
					className="ASNERISSEO-react-mb-10"
				/>
			) : null }

			{ activeMainTab === MAIN_TAB_NON_PRIORITY ? (
			<>
				<button
					type="button"
					className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-mobile-filter-toggle"
					onClick={ () => setIsMobileFilterOpen((current) => !current) }
					aria-expanded={ isMobileFilterOpen }
				>
					{ isMobileFilterOpen ? __('Hide Filters', 'asneris-seo-toolkit') : __('Show Filters', 'asneris-seo-toolkit') }
				</button>
			<form className={ `ASNERISSEO-react-inline-form is-horizontal ASNERISSEO-react-mobile-filter-target ASNERISSEO-react-block ASNERISSEO-react-pd-filter-row${ isMobileFilterOpen ? '' : ' is-mobile-collapsed' }` } onSubmit={ (event) => event.preventDefault() }>
				<label className="ASNERISSEO-react-field-label ASNERISSEO-react-flex-1">
					<input
						type="search"
						className="regular-text ASNERISSEO-react-input"
						placeholder={ __('Search by title...', 'asneris-seo-toolkit') }
						value={ searchInput }
						onChange={ (e) => setSearchInput(e.target.value) }
					/>
				</label>
				<label className="ASNERISSEO-react-field-label ASNERISSEO-react-pd-filter-control ASNERISSEO-react-pd-filter-control-type">
					<select
						className="ASNERISSEO-react-select ASNERISSEO-react-pd-filter-select"
						value={ postTypeFilter }
						onChange={ (e) => {
							setPostTypeFilter(e.target.value);
							setCurrentPage(1);
						} }
					>
						{ availablePostTypes.map((typeOption) => (
							<option key={ typeOption.value } value={ typeOption.value }>{ typeOption.value === 'all' ? __('All Types', 'asneris-seo-toolkit') : typeOption.label }</option>
						)) }
					</select>
				</label>
				<label className="ASNERISSEO-react-field-label ASNERISSEO-react-pd-filter-control ASNERISSEO-react-pd-filter-control-status">
					<select
						className="ASNERISSEO-react-select ASNERISSEO-react-pd-filter-select"
						value={ postStatusFilter }
						onChange={ (e) => {
							setPostStatusFilter(e.target.value);
							setCurrentPage(1);
						} }
					>
						{ availablePostStatuses.map((statusOption) => (
							<option key={ statusOption.value } value={ statusOption.value }>{ statusOption.value === 'all' ? __('All Status', 'asneris-seo-toolkit') : statusOption.label }</option>
						)) }
					</select>
				</label>
				<label className="ASNERISSEO-react-field-label ASNERISSEO-react-pd-filter-control ASNERISSEO-react-pd-filter-control-indexability">
					<select className="ASNERISSEO-react-select ASNERISSEO-react-pd-filter-select" value={ indexabilityFilter } onChange={ (e) => setIndexabilityFilter(e.target.value) }>
						<option value="all">{ __('All Indexability', 'asneris-seo-toolkit') }</option>
						<option value="indexable">{ __('Index', 'asneris-seo-toolkit') }</option>
						<option value="noindex">{ __('Noindex', 'asneris-seo-toolkit') }</option>
					</select>
				</label>
				<button
					type="button"
					className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
					onClick={ () => loadOverview() }
					disabled={ isLoading }
				>
					{ __('Refresh', 'asneris-seo-toolkit') }
				</button>
			</form>
			</>
			) : null }

			{ activeMainTab === MAIN_TAB_PRIORITY && filteredPriorityItems.length > 0 ? (
				<>
					<p className="ASNERISSEO-react-mt-8"><strong>{ __('Priority Pages to Fix First', 'asneris-seo-toolkit') }</strong></p>
					<p className="ASNERISSEO-react-muted ASNERISSEO-react-mb-10">{ __('These pages are configured in Settings > Priority Pages.', 'asneris-seo-toolkit') }</p>
					<div className="ASNERISSEO-react-btn-row ASNERISSEO-react-bulk-pagination-row ASNERISSEO-react-mb-10">
						<span>{ __('Items', 'asneris-seo-toolkit') }: { filteredPriorityItems.length }</span>
						<button
							type="button"
							className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
							onClick={ () => setPriorityCurrentPage((value) => Math.max(1, value - 1)) }
							disabled={ isLoading || !priorityHasPrev }
						>
							{ __('Previous', 'asneris-seo-toolkit') }
						</button>
						<span>{ __('Page', 'asneris-seo-toolkit') } { priorityPageLabel } { __('of', 'asneris-seo-toolkit') } { priorityTotalPages }</span>
						<button
							type="button"
							className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
							onClick={ () => setPriorityCurrentPage((value) => Math.min(priorityTotalPages, value + 1)) }
							disabled={ isLoading || !priorityHasNext }
						>
							{ __('Next', 'asneris-seo-toolkit') }
						</button>
					</div>
					<div className="ASNERISSEO-react-btn-row ASNERISSEO-react-pd-bulk-row ASNERISSEO-react-pd-mobile-bulk ASNERISSEO-react-mb-10">
						<span className="ASNERISSEO-react-pd-bulk-count">{ __('Selected in this tab', 'asneris-seo-toolkit') }: { selectedInActiveTab.length }</span>
						<button
							type="button"
							className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
							onClick={ allVisibleSelected ? clearVisibleSelection : selectAllVisible }
							disabled={ isLoading || !!testingPostId || isBulkAnalyzing || visibleRunItems.length < 1 }
						>
							{ allVisibleSelected ? __('Clear This Page Selection', 'asneris-seo-toolkit') : __('Select All on This Page', 'asneris-seo-toolkit') }
						</button>
						<button
							type="button"
							className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary"
							onClick={ runSelectedBulkDiagnostics }
							disabled={ isLoading || !!testingPostId || isBulkAnalyzing }
						>
							{ isBulkAnalyzing
								? __('Analyzing Selected...', 'asneris-seo-toolkit')
								: __('Analyze Selected', 'asneris-seo-toolkit') }
						</button>
					</div>
					<StatusTable
						tableClassName="ASNERISSEO-react-status-table-pd"
						mobileAccordion={ true }
						columns={ [
							{ key: 'select', label: __('Select', 'asneris-seo-toolkit'), width: '6%', align: 'center' },
							{ key: 'title', label: __('Page Title', 'asneris-seo-toolkit'), width: '22%' },
							{ key: 'type', label: __('Type', 'asneris-seo-toolkit'), width: '7%', align: 'center' },
							{ key: 'seo', label: __('SEO Score', 'asneris-seo-toolkit'), width: '9%', align: 'center' },
							{ key: 'health', label: __('Health', 'asneris-seo-toolkit'), width: '13%' },
							{ key: 'indexability', label: __('Indexability', 'asneris-seo-toolkit'), width: '10%' },
							{ key: 'issues', label: __('Issues', 'asneris-seo-toolkit'), width: '10%' },
							{ key: 'lastScan', label: __('Last Scan', 'asneris-seo-toolkit'), width: '11%' },
							{ key: 'action', label: __('Actions', 'asneris-seo-toolkit'), width: '12%', align: 'center' },
						] }
						rows={ pagedPriorityRows }
					/>
				</>
			) : null }

			{ activeMainTab === MAIN_TAB_PRIORITY && filteredPriorityItems.length === 0 ? (
				<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mb-10 is-warning">
					<p className="ASNERISSEO-react-mb-0">{ __('No Priority Pages configured yet. Go to Settings > Priority Pages and select pages/posts.', 'asneris-seo-toolkit') }</p>
				</div>
			) : null }
			{ activeMainTab === MAIN_TAB_NON_PRIORITY && nonPriorityItems.length === 0 ? (
				<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mb-10 is-warning">
					<p className="ASNERISSEO-react-note-box-title">{ __('No non-priority pages available for this filter.', 'asneris-seo-toolkit') }</p>
					<p className="ASNERISSEO-react-mb-0">{ __('Adjust filters/search or move to another page to run diagnostics for non-priority pages.', 'asneris-seo-toolkit') }</p>
				</div>
			) : null }
			{ activeMainTab === MAIN_TAB_NON_PRIORITY ? (
				<>
					<div className="ASNERISSEO-react-btn-row ASNERISSEO-react-bulk-pagination-row ASNERISSEO-react-mb-10">
						<span>{ __('Items', 'asneris-seo-toolkit') }: { data.total }</span>
						<button
							type="button"
							className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
							onClick={ () => setCurrentPage((value) => Math.max(1, value - 1)) }
							disabled={ isLoading || !listHasPrevPage }
						>
							{ __('Previous', 'asneris-seo-toolkit') }
						</button>
						<span>{ __('Page', 'asneris-seo-toolkit') } { listPageLabel } { __('of', 'asneris-seo-toolkit') } { listTotalPages }</span>
						<button
							type="button"
							className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
							onClick={ () => setCurrentPage((value) => value + 1) }
							disabled={ isLoading || !listHasNextPage }
						>
							{ __('Next', 'asneris-seo-toolkit') }
						</button>
					</div>
					{/* <div className="ASNERISSEO-react-btn-row ASNERISSEO-react-pd-bulk-row ASNERISSEO-react-pd-mobile-bulk ASNERISSEO-react-mb-10">
						<span className="ASNERISSEO-react-pd-bulk-count">{ __('Selected in this tab', 'asneris-seo-toolkit') }: { selectedInActiveTab.length }</span>
						<button
							type="button"
							className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
							onClick={ allVisibleSelected ? clearVisibleSelection : selectAllVisible }
							disabled={ isLoading || !!testingPostId || isBulkAnalyzing || visibleRunItems.length < 1 }
						>
							{ allVisibleSelected ? __('Clear This Page Selection', 'asneris-seo-toolkit') : __('Select All on This Page', 'asneris-seo-toolkit') }
						</button>
						<button
							type="button"
							className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary"
							onClick={ runSelectedBulkDiagnostics }
							disabled={ isLoading || !!testingPostId || isBulkAnalyzing }
						>
							{ isBulkAnalyzing
								? __('Analyzing Selected...', 'asneris-seo-toolkit')
								: __('Analyze Selected', 'asneris-seo-toolkit') }
						</button>
					</div> */}
					<StatusTable
						tableClassName="ASNERISSEO-react-status-table-pd"
						mobileAccordion={ true }
						columns={ [
							{ key: 'title', label: __('Page Title', 'asneris-seo-toolkit'), width: '38%' },
							{ key: 'type', label: __('Type', 'asneris-seo-toolkit'), width: '10%', align: 'center' },
							{ key: 'author', label: __('Author', 'asneris-seo-toolkit'), width: '18%', align: 'center' },
							{ key: 'modified', label: __('Last Modified', 'asneris-seo-toolkit'), width: '14%', align: 'center' },
							{ key: 'action', label: __('Actions', 'asneris-seo-toolkit'), width: '12%', align: 'center' },
						] }
						rows={ nonPriorityRows }
						emptyMessage={ __('No non-priority pages found on this page.', 'asneris-seo-toolkit') }
					/>
				</>
			) : null }

			<div className="ASNERISSEO-react-note-box ASNERISSEO-react-page-diagnostics-disclaimer ASNERISSEO-react-mb-10">
				<p className="ASNERISSEO-react-note-box-title">{ __('About This Report', 'asneris-seo-toolkit') }</p>
				<ul className="ASNERISSEO-react-page-diagnostics-disclaimer-list">
					<li>{ __('This report is generated by Asneris through analysis of your website\'s published content and publicly accessible discoverability signals, including search appearance, metadata, page structure, semantic signals, and indexability readiness.', 'asneris-seo-toolkit') }</li>
					<li>{ __('Historical comparisons are based only on Asneris snapshot reports collected during previous scans, allowing you to monitor changes and trends over time.', 'asneris-seo-toolkit') }</li>
					<li>{ __('This report does not use Google Search Console (GSC), Google Analytics, search rankings, impressions, clicks, crawl statistics, or other third-party search performance data.', 'asneris-seo-toolkit') }</li>
					<li>{ __('Results provide diagnostic guidance based on the website\'s state at the time of analysis and should be used alongside other SEO insights for a complete evaluation.', 'asneris-seo-toolkit') }</li>
				</ul>
			</div>
			<div
				style={ {
					display: 'grid',
					gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
					gap: '12px',
					marginTop: '12px',
					marginBottom: '12px',
				} }
			>
				{/* <div className={ `ASNERISSEO-react-note-box ${ priorityFeatureEnabled ? 'is-success' : 'is-warning' }` }>
					<p className={ `ASNERISSEO-react-note-box-title ${ priorityFeatureEnabled ? 'is-success' : 'is-warning' }` }>{ __('Priority Page Configuration', 'asneris-seo-toolkit') }</p>
					<p className="ASNERISSEO-react-mb-0">
						{ priorityFeatureEnabled
							? __('Priority pages are enabled. Configure and reorder key pages in Settings > Priority Pages.', 'asneris-seo-toolkit')
							: __('Priority pages are currently disabled. Enable them in Settings > Priority Pages.', 'asneris-seo-toolkit') }
					</p>
				</div> */}
			</div>

			<div
				className={ `ASNERISSEO-modal-overlay${ isReportDialogOpen && selectedResult ? ' active' : '' }` }
				onClick={ (event) => {
					if (event.target === event.currentTarget) {
						closeReportDialog();
					}
				} }
			>
				{ selectedResult ? (
					<div className={ detailDialogClassName } role="dialog" aria-modal="true" aria-label={ __('Diagnostics Report', 'asneris-seo-toolkit') }>
						<div className="ASNERISSEO-modal-content">
							<div className="ASNERISSEO-modal-header ASNERISSEO-react-detail-report-header">
								<h3 className="ASNERISSEO-modal-title ASNERISSEO-modal-title-with-brand">
									{ modalLogoUrl ? (
										<img
											src={ modalLogoUrl }
											alt={ __('Asneris SEO Toolkit', 'asneris-seo-toolkit') }
											className="ASNERISSEO-modal-title-logo"
										/>
									) : (
										<span className="ASNERISSEO-modal-title-mark" aria-hidden="true">A</span>
									) }
									<span>{ __('Page SEO Discoverability Report', 'asneris-seo-toolkit') }</span>
								</h3>
							</div>
						
							{ SHOW_CANONICAL_DEBUG_BUTTON   ? (  // Debug toggle button for canonical debug panel
							<button
								type="button"
								className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-small"
								onClick={ () => setIsCanonicalDebugOpen((previous) => !previous) }
								aria-expanded={ isCanonicalDebugOpen }
								aria-label={ __('Toggle canonical debug panel', 'asneris-seo-toolkit') }
								title={ __('Toggle canonical debug panel', 'asneris-seo-toolkit') }
								style={ {
									position: 'absolute',
									top: '12px',
									right: '48px',
									zIndex: 4,
									display: 'inline-flex',
									alignItems: 'center',
									justifyContent: 'center',
									width: '30px',
									height: '30px',
									padding: 0,
								} }
							>
								<span className="dashicons dashicons-editor-code" aria-hidden="true" />
							</button>
							) : null }
							<button type="button" className="ASNERISSEO-modal-close ASNERISSEO-react-detail-close" onClick={ closeReportDialog }>
								&times;
							</button>


							{ isCanonicalDebugOpen ? (
								<div className="ASNERISSEO-react-note-box" style={ { marginBottom: '12px' } }>
									<p className="ASNERISSEO-react-note-box-title">{ __('Canonical/Raw Debug Verification', 'asneris-seo-toolkit') }</p>
									<div className="ASNERISSEO-react-table-wrap ASNERISSEO-react-detail-issues-scroll">
										<table className="ASNERISSEO-react-status-table">
											<thead>
												<tr>
													<th>{ __('Tab', 'asneris-seo-toolkit') }</th>
													<th>{ __('Canonical Field', 'asneris-seo-toolkit') }</th>
													<th>{ __('Status', 'asneris-seo-toolkit') }</th>
													<th>{ __('Raw Fields', 'asneris-seo-toolkit') }</th>
													<th>{ __('Resolved Raw Evidence', 'asneris-seo-toolkit') }</th>
												</tr>
											</thead>
											<tbody>
												{ canonicalDebugRows.map((row) => (
													<tr key={ row.key }>
														<td>{ row.tabLabel }</td>
														<td>{ row.canonicalField }</td>
														<td>{ row.status }</td>
														<td>{ row.mappedFields }</td>
														<td>{ row.evidence }</td>
													</tr>
												)) }
											</tbody>
										</table>
									</div>
								</div>
							) : null }
							{ !isDetailHeaderCollapsed ? (
								<div className="ASNERISSEO-react-detail-header-toolbar">
									{ embeddedInEditorModal && editorIsDirty && canApplyEmbeddedDraftData ? (
										<button
											type="button"
											className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-small"
											onClick={ applyEmbeddedDraftData }
										>
											{ __('Use Draft (Unsaved) Data', 'asneris-seo-toolkit') }
										</button>
									) : null }
									<button
										type="button"
										className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-small"
										onClick={ () => setIsDetailHeaderCollapsed(true) }
										aria-expanded={ true }
									>
										{ __('Collapse Top Details', 'asneris-seo-toolkit') }
									</button>
								</div>
							) : null }

							{ isDetailHeaderCollapsed ? (
								<div className="ASNERISSEO-react-detail-header-collapsed">
									<div>
										<strong>{ selectedResultItem?.title || __('Diagnostics Detail', 'asneris-seo-toolkit') }</strong>
										<div className="ASNERISSEO-react-muted">
											{ `${ __('Last Scan', 'asneris-seo-toolkit') }: ${ formatDateTimeLabel(selectedResult.lastScanGmt) } | ${ __('Source', 'asneris-seo-toolkit') }: ${ selectedDetailSourceLabel }` }
										</div>
									</div>
									<div className="ASNERISSEO-react-detail-header-collapsed-meta">
										<span className={ `ASNERISSEO-react-score-pill is-${ getScoreBand(effectiveSelectedSeoScore) }` }>{ `${ effectiveSelectedSeoScore ?? '-' }/100` }</span>
										<span className={ `ASNERISSEO-react-status-chip is-${ selectedHealth.tone }` }>{ selectedHealth.label }</span>
										<button
											type="button"
											className="ASNERISSEO-react-detail-header-collapsed-toggle"
											onClick={ () => setIsDetailHeaderCollapsed(false) }
											aria-label={ __('Expand Top Details', 'asneris-seo-toolkit') }
											title={ __('Expand Top Details', 'asneris-seo-toolkit') }
										>
											<span className="dashicons dashicons-arrow-down-alt2" aria-hidden="true" />
										</button>
									</div>
								</div>
							) : (
								<div className="ASNERISSEO-react-detail-header-row">
									<div>
										<h3 className="ASNERISSEO-react-mb-0">{ selectedResultItem?.title || __('Diagnostics Detail', 'asneris-seo-toolkit') }</h3>
										{ selectedResult.url ? <a href={ selectedResult.url } target="_blank" rel="noopener noreferrer">{ selectedResult.url }</a> : null }
									</div>
									<div className="ASNERISSEO-react-detail-header-meta">
										<div className="ASNERISSEO-react-muted">{ `${ __('Last Scan', 'asneris-seo-toolkit') }: ${ formatDateTimeLabel(selectedResult.lastScanGmt) }` }</div>
										{ selectedResult?.performance ? (
											<PerformanceTrackerCard
												title={ __('Run Performance', 'asneris-seo-toolkit') }
												statusLabel={ formatPerformanceStatus(selectedResult.performance?.status) }
												statusClassName={ toStatusChipClass(selectedResult.performance?.status) }
												advisoryMessage={ selectedResult.performance?.advisory?.recommendNotToRun
													? (selectedResult.performance?.advisory?.reason || __('Server memory headroom is low. We recommend not running this analysis now.', 'asneris-seo-toolkit'))
													: '' }
												performance={ selectedResult.performance }
												modalTitle={ __('Performance Details', 'asneris-seo-toolkit') }
												className="ASNERISSEO-react-mt-10"
											/>
										) : null }

										{ shouldShowSnapshotHistory ? (
											<div className="ASNERISSEO-react-history-status-strip ASNERISSEO-react-mobile-hide" role="status" aria-live="polite">
												<span className="ASNERISSEO-react-muted">{ __('Snapshot History', 'asneris-seo-toolkit') }</span>
												<strong>{ `${ historyCount }/${ historyLimit }` }</strong>
												{ isHistoryLocked ? (
													<button
														type="button"
														onClick={ jumpToSnapshotHistory }
														className="ASNERISSEO-react-status-chip-button"
													>
														<span className="ASNERISSEO-react-status-chip is-warning">{ __('Locked', 'asneris-seo-toolkit') }</span>
													</button>
												) : (
													<span className="ASNERISSEO-react-status-chip is-success">{ __('Active', 'asneris-seo-toolkit') }</span>
												) }
											</div>
										) : null }
									</div>
									<div className="ASNERISSEO-react-detail-score-box">
										<div className={ `ASNERISSEO-react-score-pill is-${ getScoreBand(effectiveSelectedSeoScore) }` }>{ `${ effectiveSelectedSeoScore ?? '-' }/100` }</div>
										<div className="ASNERISSEO-react-muted">{ __('Overall SEO Score', 'asneris-seo-toolkit') }</div>
										<div className={ `ASNERISSEO-react-status-chip is-${ selectedHealth.tone }` }>{ selectedHealth.label }</div>
										{ selectedSeoScoreMessage ? (
											<p className="ASNERISSEO-react-text-danger ASNERISSEO-react-mb-0">{ selectedSeoScoreMessage }</p>
										) : null }
									</div>
								</div>
							) }

							<div
								className="ASNERISSEO-react-detail-tabs ASNERISSEO-react-tabs ASNERISSEO-react-tabs-strip"
								ref={ detailTabsRef }
								role="tablist"
								aria-label={ __('Diagnostics Detail Tabs', 'asneris-seo-toolkit') }
								onWheel={ handleDetailTabsWheel }
								onPointerDown={ handleDetailTabsPointerDown }
								onPointerMove={ handleDetailTabsPointerMove }
								onPointerUp={ stopDetailTabsDrag }
								onPointerCancel={ stopDetailTabsDrag }
								onPointerLeave={ stopDetailTabsDrag }
								onClickCapture={ handleDetailTabsClickCapture }
								style={ {
									display: 'flex',
									alignItems: 'stretch',
									gap: '2px',
									minHeight: '42px',
									overflowX: 'auto',
									overflowY: 'visible',
									paddingBottom: '2px',
								} }
							>
								{ DETAIL_VIEW_TABS.map((tab) => (
									<button
										key={ tab.key }
										type="button"
										role="tab"
										aria-selected={ activeDetailTab === tab.key }
										className={ `ASNERISSEO-react-tab${ activeDetailTab === tab.key ? ' is-active' : ''}` }
										disabled={ isHistoryLocked && shouldShowSnapshotHistory && tab.key !== DETAIL_TAB_OVERVIEW }
										onClick={ () => setActiveDetailTab(tab.key) }
										style={ {
											minHeight: '40px',
											height: '40px',
											lineHeight: 1,
											padding: '0 14px',
											whiteSpace: 'nowrap',
											flex: '0 0 auto',
										} }
									>
										{ tab.label }
									</button>
								)) }
							</div>

							<div className="ASNERISSEO-react-detail-scroll-area">
								{ activeDetailTab === DETAIL_TAB_OVERVIEW ? (
									<>
										{ isHistoryLocked && shouldShowSnapshotHistory ? (
											<div className="ASNERISSEO-react-note-box is-warning">
												<p className="ASNERISSEO-react-note-box-title is-warning">{ __('History limit reached', 'asneris-seo-toolkit') }</p>
												<p className="ASNERISSEO-react-mb-0">{ `${ __('This page has reached', 'asneris-seo-toolkit') } ${ historyCount }/${ historyLimit } ${ __('history records. Delete old history records to continue using all report sections.', 'asneris-seo-toolkit') }` }</p>
											</div>
										) : null }
										<div className={ isHistoryLocked && shouldShowSnapshotHistory ? 'ASNERISSEO-react-history-locked-content' : '' }>
											<h4 className="ASNERISSEO-react-overview-heading">{ __('SEO Health Summary', 'asneris-seo-toolkit') }</h4>
											<div className="ASNERISSEO-react-overview-alert-box">
											<div className="ASNERISSEO-react-overview-alert-col">
												<div
													className="ASNERISSEO-react-overview-alert-title"
													style={ { color: hasCriticalIssues ? '#c23535' : '#1d6f42' } }
												>
													<span
														className={ `dashicons ${ hasCriticalIssues ? 'dashicons-warning' : 'dashicons-yes-alt' }` }
														aria-hidden="true"
														style={ { color: hasCriticalIssues ? '#df4b4b' : '#16a76a' } }
													/>
													<strong>{ hasCriticalIssues ? __('Critical Issues Found', 'asneris-seo-toolkit') : __('No Critical Issues Found', 'asneris-seo-toolkit') }</strong>
												</div>
												<p>{ hasCriticalIssues ? __('This page has critical issues that are impacting its search visibility and performance.', 'asneris-seo-toolkit') : __('This page is in good condition and no critical blockers were detected.', 'asneris-seo-toolkit') }</p>
											</div>
											<div className={ `ASNERISSEO-react-overview-alert-col is-middle${ isMobileViewport ? ' ASNERISSEO-react-mobile-hide' : '' }` }>
												<strong>{ __('Top Priorities', 'asneris-seo-toolkit') }</strong>
												<ul>
														{ topPriorityItems.length > 0 ? topPriorityItems.map((label, index) => (
															<li key={ `${ label }-${ index }` } style={{ color: '#0073aa' }}>{ `${ label }` }</li>
														)) : (
															<li style={{ color: '#4b5563' }}>{ __('No immediate priorities.', 'asneris-seo-toolkit') }</li>
														) }
												</ul>
											</div>
											<div className="ASNERISSEO-react-overview-alert-col is-visual ASNERISSEO-react-mobile-hide" aria-hidden="true">
												<span className="dashicons dashicons-search" />
											</div>
											</div>

										<DiscoverabilityTopIssues
											checks={ [] }
											title={ __('By Category', 'asneris-seo-toolkit') }
											breakdownTitle={ __('Issues', 'asneris-seo-toolkit') }
											showBreakdown={ true }
											useAccordion={ true }
											breakdownAllowedFields={ OVERVIEW_PRIMARY_FIELDS }
											breakdownNormalizeLabel={ normalizeOverviewPrimaryFieldLabel }
											breakdownDedupeByLabel={ true }
											breakdownIncludePass={ true }
											topIssuesOverride={ canonicalOverviewTopIssues }
											breakdownRowsOverride={ canonicalOverviewBreakdownRows }
										/>

											{ showGoodNews ? (
											<div className="ASNERISSEO-react-note-box is-success">
												<p className="ASNERISSEO-react-note-box-title is-success">{ __('Good News! This page is eligible for indexing and accessible to search engines.', 'asneris-seo-toolkit') }</p>
											</div>
											) : null }
										</div>

										{ !isSnapshotHistorySuppressedInEmbeddedFlow ? (
											<div className="ASNERISSEO-react-mobile-hide">
												<h4 className="ASNERISSEO-react-overview-heading" ref={ historySectionRef }>{ __('Snapshot History', 'asneris-seo-toolkit') }</h4>
											</div>
										) : null }
										{ shouldShowSnapshotHistory ? (
											<div className="ASNERISSEO-react-mobile-hide">
												
												{ isHistoryLoading ? <p>{ __('Loading history...', 'asneris-seo-toolkit') }</p> : null }
												{ historyTrend ? (
													<div className={ `ASNERISSEO-react-note-box ${ historyTrend.direction === 'up' ? 'is-success' : (historyTrend.direction === 'down' ? 'is-warning' : '') }` }>
														<p className="ASNERISSEO-react-note-box-title">
															{ historyTrend.direction === 'up'
																? __('Trend: Improving', 'asneris-seo-toolkit')
																: (historyTrend.direction === 'down' ? __('Trend: Declining', 'asneris-seo-toolkit') : __('Trend: Stable', 'asneris-seo-toolkit')) }
														</p>
														<p>{ `${ __('Score change', 'asneris-seo-toolkit') }: ${ historyTrend.delta > 0 ? '+' : '' }${ historyTrend.delta }` }</p>
														<p>{ `${ __('Average change per day', 'asneris-seo-toolkit') }: ${ historyTrend.avgPerDay > 0 ? '+' : '' }${ historyTrend.avgPerDay }` }</p>
														<p>{ `${ __('Best', 'asneris-seo-toolkit') }: ${ historyTrend.best }  |  ${ __('Worst', 'asneris-seo-toolkit') }: ${ historyTrend.worst }  |  ${ __('Snapshots', 'asneris-seo-toolkit') }: ${ historyTrend.sampleCount }` }</p>
													</div>
												) : null }
												<StatusTable
													wrapClassName="ASNERISSEO-react-detail-issues-scroll"
													columns={ [
														{ key: 'scan', label: __('Scan Time', 'asneris-seo-toolkit'), width: '45%' },
														{ key: 'score', label: __('SEO Score', 'asneris-seo-toolkit'), width: '25%', align: 'center' },
														{ key: 'health', label: __('Health', 'asneris-seo-toolkit'), width: '20%', align: 'center' },
														{ key: 'action', label: __('Actions', 'asneris-seo-toolkit'), width: '10%', align: 'center' },
													] }
													rows={ historyRows }
													emptyMessage={ __('No history snapshots yet. Add this page to Priority Pages to retain history.', 'asneris-seo-toolkit') }
												/>
											</div>
										) : !isEmbeddedDetailOpenFlow ? (
											<div className="ASNERISSEO-react-note-box is-warning">
												<p className="ASNERISSEO-react-note-box-title">{ __('Snapshot History is available for Priority Pages only.', 'asneris-seo-toolkit') }</p>
												<p className="ASNERISSEO-react-mb-0">{ __('This page is non-priority, so diagnostics run live on demand and no snapshots are stored.', 'asneris-seo-toolkit') }</p>
											</div>
										) : null }
									</>
								) : (
									<>
										{ activeDetailTab === DETAIL_TAB_SEARCH_APPEARANCE ? (
											<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mt-10">
												<div className="ASNERISSEO-react-sa-preview-header">
													<p className="ASNERISSEO-react-note-box-title ASNERISSEO-react-mb-0">{ __('Search Appearance Preview', 'asneris-seo-toolkit') }</p>
													{ !isMobileViewport ? (
														<div className="ASNERISSEO-react-sa-preview-viewport-toggle">
															<button
																type="button"
																className={ `button ASNERISSEO-react-button ASNERISSEO-react-button-small ASNERISSEO-react-sa-preview-viewport-button${ !isSearchAppearanceMobile ? ' is-active' : '' }` }
																onClick={ () => setSearchAppearanceViewport('desktop') }
															>
																{ __('Desktop', 'asneris-seo-toolkit') }
															</button>
															<button
																type="button"
																className={ `button ASNERISSEO-react-button ASNERISSEO-react-button-small ASNERISSEO-react-sa-preview-viewport-button${ isSearchAppearanceMobile ? ' is-active' : '' }` }
																onClick={ () => setSearchAppearanceViewport('mobile') }
															>
																{ __('Mobile', 'asneris-seo-toolkit') }
															</button>
														</div>
													) : null }
												</div>
												<p className="ASNERISSEO-react-muted ASNERISSEO-react-sa-preview-section-label">{ __('Search Engine', 'asneris-seo-toolkit') }</p>
												<div className="ASNERISSEO-react-sa-preview-center ASNERISSEO-react-sa-preview-center-google">
													<div className={ `ASNERISSEO-react-sa-preview-frame ASNERISSEO-react-sa-preview-frame-google${ isSearchAppearanceMobile ? ' is-mobile' : ' is-desktop' }` }>
														<div className="ASNERISSEO-react-sa-preview-engine-label">{ __('Google Result', 'asneris-seo-toolkit') }</div>
														<p className="ASNERISSEO-react-sa-preview-title">{ activeSearchAppearanceVisual?.googleTitle || '-' }</p>
														{ activeSearchAppearanceVisual?.url ? <p className="ASNERISSEO-react-sa-preview-url">{ activeSearchAppearanceVisual.url }</p> : null }
														<p className="ASNERISSEO-react-sa-preview-description">{ activeSearchAppearanceVisual?.googleDescription || '-' }</p>
													</div>
												</div>
												<p className="ASNERISSEO-react-muted ASNERISSEO-react-sa-preview-section-label">{ __('Social Media', 'asneris-seo-toolkit') }</p>
												<div className="ASNERISSEO-react-sa-preview-center ASNERISSEO-react-sa-preview-center-social">
													<div className={ `ASNERISSEO-react-sa-preview-frame ASNERISSEO-react-sa-preview-frame-social${ isSearchAppearanceMobile ? ' is-mobile' : ' is-desktop' }` }>
														<div className={ `ASNERISSEO-react-sa-preview-media${ isSearchAppearanceMobile ? ' is-mobile' : ' is-desktop' }${ activeSearchAppearanceVisual?.socialImage ? ' has-image' : ' no-image' }` }>
														{ activeSearchAppearanceVisual?.socialImage ? (
															<img
																src={ activeSearchAppearanceVisual.socialImage }
																alt={ __('Social image preview', 'asneris-seo-toolkit') }
																className="ASNERISSEO-react-sa-preview-social-image"
															/>
														) : hasSocialImageTemplate ? (
															<div className="ASNERISSEO-react-sa-preview-fallback-title">
																{ socialImageFallbackTitle }
															</div>
														) : (
															<div className="ASNERISSEO-react-sa-preview-fallback-text">
																{ __('Social image unavailable', 'asneris-seo-toolkit') }
															</div>
														) }
														</div>
														<div className="ASNERISSEO-react-sa-preview-social-body">
															<p className="ASNERISSEO-react-sa-preview-social-title">{ activeSearchAppearanceVisual?.socialTitle || activeSearchAppearanceVisual?.googleTitle || '-' }</p>
															<p className="ASNERISSEO-react-sa-preview-social-description">{ activeSearchAppearanceVisual?.socialDescription || activeSearchAppearanceVisual?.googleDescription || '-' }</p>
														</div>
													</div>
												</div>
												<div className="ASNERISSEO-react-sa-kv-card">
													<p className="ASNERISSEO-react-note-box-title ASNERISSEO-react-mb-0">{ __('Source Summary', 'asneris-seo-toolkit') }</p>
													<div className="ASNERISSEO-react-sa-kv-grid">
														{ searchAppearanceSourceRows.map((row) => (
															<div className="ASNERISSEO-react-sa-kv-row" key={ row.label }>
																<div className="ASNERISSEO-react-sa-kv-key">{ row.label }</div>
																<div className="ASNERISSEO-react-sa-kv-value">{ row.value }</div>
																<div className={ `ASNERISSEO-react-sa-kv-source is-${ row.sourceTone || 'fallback' }` }>{ row.source }</div>
															</div>
														)) }
													</div>
												</div>
											</div>
										) : (
											<>
												<div className="ASNERISSEO-react-tab-cards-grid">
													<div className="ASNERISSEO-react-tab-card">
														<div className="ASNERISSEO-react-tab-card-header">
															<h4>{ activeDetailCard.title }</h4>
															<span className={ `ASNERISSEO-react-status-chip is-${ activeDetailCard.noteTone === 'success' ? 'success' : 'warning' }` }>{ activeDetailCard.status }</span>
														</div>
														<div className="ASNERISSEO-react-tab-card-score">
															<span className={ `ASNERISSEO-react-score-pill is-${ getScoreBand(activeDetailCardScore) }` }>{ activeDetailCardScoreLabel }</span>
														</div>
														<p className="ASNERISSEO-react-muted">{ activeDetailCard.summary }</p>
														{ activeDetailCard.scoreMessage ? (
															<p className="ASNERISSEO-react-text-danger ASNERISSEO-react-mb-0">{ activeDetailCard.scoreMessage }</p>
														) : null }
														{ hasSummaryHighlightSources ? (
															<div className="ASNERISSEO-react-detail-issues-scroll">
																<table className="ASNERISSEO-react-table ASNERISSEO-react-table-compact">
																	<thead>
																		<tr>
																			<th>{ __('Check', 'asneris-seo-toolkit') }</th>
																			<th>{ __('Source', 'asneris-seo-toolkit') }</th>
																			<th>{ __('Cost', 'asneris-seo-toolkit') }</th>
																		</tr>
																	</thead>
																	<tbody>
																		{ activeSummaryHighlights.map((entry, index) => (
																			<tr key={ `${ entry.label }-${ index }` }>
																				<td>{ entry.label }</td>
																				<td>{ entry.source || '-' }</td>
																				<td><strong>{ entry.value }</strong></td>
																			</tr>
																		)) }
																	</tbody>
																</table>
															</div>
															) : (
																activeSummaryHighlights.length > 0 ? (
															<ul className="ASNERISSEO-react-tab-highlight-list">
																{ activeSummaryHighlights.map((entry, index) => (
																	<li key={ `${ entry.label }-${ index }` }><span>{ entry.label }</span><strong>{ entry.value }</strong></li>
																)) }
															</ul>
																) : null
														) }
													</div>
												</div>
												{ useDetailIssueAccordion ? (
													<div className="ASNERISSEO-react-detail-section-toggle-row" role="tablist" aria-label={ __('Detail Content Sections', 'asneris-seo-toolkit') }>
														<button
															type="button"
															className={ `button ASNERISSEO-react-button ASNERISSEO-react-button-small ${ activeDetailContentSection === DETAIL_CONTENT_SECTION_DETAILS ? 'ASNERISSEO-react-button-primary' : 'ASNERISSEO-react-button-secondary' }` }
															onClick={ () => setActiveDetailContentSection(DETAIL_CONTENT_SECTION_DETAILS) }
															aria-selected={ activeDetailContentSection === DETAIL_CONTENT_SECTION_DETAILS }
														>
															{ __('Summary', 'asneris-seo-toolkit') }
														</button>
														<button
															type="button"
															className={ `button ASNERISSEO-react-button ASNERISSEO-react-button-small ${ activeDetailContentSection === DETAIL_CONTENT_SECTION_ISSUES ? 'ASNERISSEO-react-button-primary' : 'ASNERISSEO-react-button-secondary' }` }
															onClick={ () => setActiveDetailContentSection(DETAIL_CONTENT_SECTION_ISSUES) }
															aria-selected={ activeDetailContentSection === DETAIL_CONTENT_SECTION_ISSUES }
														>
															{ __('Issues', 'asneris-seo-toolkit') }
														</button>
													</div>
												) : null }

												{ !useDetailIssueAccordion || activeDetailContentSection === DETAIL_CONTENT_SECTION_DETAILS ? (
													<div className="ASNERISSEO-react-tab-card ASNERISSEO-react-detail-section-body">
														<div className="ASNERISSEO-react-tab-card-header"><h4>{ activeDetailCard.detailsTitle }</h4></div>
														<ul className="ASNERISSEO-react-tab-detail-list">
															{ activeDetailHighlights.map((entry, index) => (
																<li key={ `${ entry.label }-detail-${ index }` }><span>{ entry.label }</span><strong>{ entry.value }</strong></li>
															)) }
														</ul>
														<div className={ `ASNERISSEO-react-note-box ${ activeDetailCard.noteTone === 'success' ? 'is-success' : 'is-warning' }` }>
															<p className={ `ASNERISSEO-react-note-box-title ${ activeDetailCard.noteTone === 'success' ? 'is-success' : 'is-warning' }` }>{ activeDetailCard.note }</p>
														</div>
													</div>
												) : null }

												{ !useDetailIssueAccordion || activeDetailContentSection === DETAIL_CONTENT_SECTION_ISSUES ? (
													<>
														<div className="ASNERISSEO-react-note-box"><p className="ASNERISSEO-react-note-box-title">{ activeDetailTabLabel }</p><p>{ `${ activeDetailIssues } ${ __('issues', 'asneris-seo-toolkit') } / ${ activeIssueListRows.length } ${ __('checks', 'asneris-seo-toolkit') }` }</p></div>
														<StatusTable
															wrapClassName="ASNERISSEO-react-detail-issues-scroll"
															columns={ [
																{ key: 'check', label: __('Check', 'asneris-seo-toolkit'), width: '28%' },
																{ key: 'status', label: __('Status', 'asneris-seo-toolkit'), width: '16%', align: 'center' },
																{ key: 'result', label: __('Result', 'asneris-seo-toolkit'), width: '16%', align: 'center' },
																{ key: 'details', label: __('Details', 'asneris-seo-toolkit'), width: '40%' },
															] }
															rows={ activeIssueListRows }
															emptyMessage={ __('No checks available for this section.', 'asneris-seo-toolkit') }
														/>
													</>
												) : null }
												{ shouldShowSnapshotHistory ? (
													<div className="ASNERISSEO-react-mobile-hide">
														<h4 className="ASNERISSEO-react-overview-heading">{ `${ activeDetailTabLabel } ${ __('Snapshot History', 'asneris-seo-toolkit') }` }</h4>
														{ isHistoryLoading ? <p>{ __('Loading history...', 'asneris-seo-toolkit') }</p> : null }
														<StatusTable
															wrapClassName="ASNERISSEO-react-detail-issues-scroll"
															columns={ [
																{ key: 'scan', label: __('Scan Time', 'asneris-seo-toolkit'), width: '22%' },
																{ key: 'coverage', label: __('Coverage', 'asneris-seo-toolkit'), width: '12%', align: 'center' },
																{ key: 'source', label: __('Source', 'asneris-seo-toolkit'), width: '16%', align: 'center' },
																{ key: 'issues', label: __('Issues/Checks', 'asneris-seo-toolkit'), width: '14%', align: 'center' },
																{ key: 'pass', label: __('Pass', 'asneris-seo-toolkit'), width: '12%', align: 'center' },
																{ key: 'warning', label: __('Warning', 'asneris-seo-toolkit'), width: '12%', align: 'center' },
																{ key: 'fail', label: __('Fail', 'asneris-seo-toolkit'), width: '12%', align: 'center' },
															] }
															rows={ detailTabHistoryRows }
															emptyMessage={ __('No snapshot history available for this tab yet.', 'asneris-seo-toolkit') }
														/>
													</div>
												) : null }
											</>
										) }
									</>
								) }

							</div>
						</div>
						<div className="ASNERISSEO-modal-footer">
							<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary" onClick={ closeReportDialog }>
								{ __('Close', 'asneris-seo-toolkit') }
							</button>
						</div>
					</div>
				) : null }
			</div>
			{ isReportDialogOpen && isClearConfirmOpen ? (
				<div className="ASNERISSEO-modal-overlay active" onClick={ () => setIsClearConfirmOpen(false) }>
					<div className="ASNERISSEO-modal ASNERISSEO-modal-small" role="dialog" aria-modal="true" aria-label={ __('Confirm Page Non-Prioritize', 'asneris-seo-toolkit') } onClick={ (event) => event.stopPropagation() }>
						<div className="ASNERISSEO-modal-header ASNERISSEO-modal-header-standard">
							<h3 className="ASNERISSEO-modal-title">{ __('Confirm Page Non-Prioritize', 'asneris-seo-toolkit') }</h3>
							<button type="button" className="ASNERISSEO-modal-close ASNERISSEO-modal-close-light" onClick={ () => setIsClearConfirmOpen(false) } disabled={ isClearingRecords }>
								&times;
							</button>
						</div>
						<div className="ASNERISSEO-modal-content">
							<p>{ __('This will delete latest and history diagnostics records for this page. Continue?', 'asneris-seo-toolkit') }</p>
						</div>
						<div className="ASNERISSEO-modal-footer">
							<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" onClick={ () => setIsClearConfirmOpen(false) } disabled={ isClearingRecords }>
								{ __('Cancel', 'asneris-seo-toolkit') }
							</button>
							<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary" onClick={ confirmClearPageRecords } disabled={ isClearingRecords }>
								{ isClearingRecords ? __('Cleaning...', 'asneris-seo-toolkit') : __('Yes, Page Non-Prioritize', 'asneris-seo-toolkit') }
							</button>
						</div>
					</div>
				</div>
			) : null }

			<div
				className={ `ASNERISSEO-modal-overlay${ isHistoryPopupOpen ? ' active' : '' }` }
				onClick={ (event) => {
					if (event.target === event.currentTarget) {
						setIsHistoryPopupOpen(false);
					}
				} }
			>
				{ isHistoryPopupOpen ? (
					<div className="ASNERISSEO-modal ASNERISSEO-modal-large ASNERISSEO-react-history-modal" role="dialog" aria-modal="true" aria-label={ __('Page Diagnostics History', 'asneris-seo-toolkit') }>
						<div className="ASNERISSEO-react-history-modal-header">
							<div>
								<h3 className="ASNERISSEO-modal-title ASNERISSEO-modal-title-with-brand">
									{ modalLogoUrl ? (
										<img
											src={ modalLogoUrl }
											alt={ __('Asneris SEO Toolkit', 'asneris-seo-toolkit') }
											className="ASNERISSEO-modal-title-logo"
										/>
									) : (
										<span className="ASNERISSEO-modal-title-mark" aria-hidden="true">A</span>
									) }
									<span>{ __('Page Diagnostics History', 'asneris-seo-toolkit') }</span>
								</h3>
								<p>{ __('Track your page SEO performance over time', 'asneris-seo-toolkit') }</p>
							</div>
							<div className="ASNERISSEO-react-history-modal-controls">
								{ historyPopupMode === 'comparison' ? (
									<button
										type="button"
										className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
										onClick={ () => setHistoryPopupMode('history') }
									>
										{ __('Back to History', 'asneris-seo-toolkit') }
									</button>
								) : null }
								<label htmlFor="ASNERISSEO-history-limit" className="screen-reader-text">{ __('Available reports', 'asneris-seo-toolkit') }</label>
								<select
									id="ASNERISSEO-history-limit"
									value={ String(historyPopupFetchLimit) }
									disabled
									aria-disabled="true"
								>
									<option value={ String(historyPopupFetchLimit) }>
										{ isHistoryPopupLoading
											? __('Loading reports...', 'asneris-seo-toolkit')
											: historyPopupFetchLimit > 0
												? `${ __('Available reports', 'asneris-seo-toolkit') }: ${ historyPopupFetchLimit }`
												: __('No reports available', 'asneris-seo-toolkit') }
									</option>
								</select>
								<button type="button" className="ASNERISSEO-modal-close" onClick={ () => setIsHistoryPopupOpen(false) } aria-label={ __('Close history popup', 'asneris-seo-toolkit') }>
									&times;
								</button>
							</div>
						</div>

						<div className="ASNERISSEO-react-history-modal-page-meta">
							<strong>{ historyPopupItem?.url || '-' }</strong>
							<span>
								{ `${ __('Page ID', 'asneris-seo-toolkit') }: ${ historyPopupItem?.postId || '-' }` }
								{ historyPopupItem?.postType ? ` | ${ String(historyPopupItem.postType).toUpperCase() }` : '' }
							</span>
						</div>

						{ isHistoryPopupLoading ? <p>{ __('Loading history...', 'asneris-seo-toolkit') }</p> : null }
						{ historyPopupError ? <p className="ASNERISSEO-react-text-danger">{ historyPopupError }</p> : null }

						{ historyPopupMode === 'comparison' ? (
							comparisonModel ? (
								<>
									<div className="ASNERISSEO-react-history-compare-top">
										<div className="ASNERISSEO-react-history-compare-report-card">
											<div className="ASNERISSEO-react-history-summary-label">{ __('Previous Report', 'asneris-seo-toolkit') }</div>
											<div className="ASNERISSEO-react-history-compare-report-time">{ formatDateTimeLabel(comparisonModel.previousSnapshot.generatedAt) }</div>
											<div className="ASNERISSEO-react-history-compare-report-score">{ `${ __('Score', 'asneris-seo-toolkit') }: ${ comparisonModel.previousSnapshot.score }` }</div>
										</div>
										<div className="ASNERISSEO-react-history-compare-vs">VS</div>
										<div className="ASNERISSEO-react-history-compare-report-card">
											<div className="ASNERISSEO-react-history-summary-label">{ __('Current Report', 'asneris-seo-toolkit') }</div>
											<div className="ASNERISSEO-react-history-compare-report-time">{ formatDateTimeLabel(comparisonModel.currentSnapshot.generatedAt) }</div>
											<div className="ASNERISSEO-react-history-compare-report-score">{ `${ __('Score', 'asneris-seo-toolkit') }: ${ comparisonModel.currentSnapshot.score }` }</div>
										</div>
										<div className="ASNERISSEO-react-history-compare-report-card is-highlight">
											<div className="ASNERISSEO-react-history-summary-label">{ __('Change in Score', 'asneris-seo-toolkit') }</div>
											<div className="ASNERISSEO-react-history-summary-value">{ `${ comparisonModel.currentSnapshot.score - comparisonModel.previousSnapshot.score > 0 ? '+' : '' }${ comparisonModel.currentSnapshot.score - comparisonModel.previousSnapshot.score }` }</div>
											<div className="ASNERISSEO-react-history-summary-sub">{ __('Improvement', 'asneris-seo-toolkit') }</div>
										</div>
									</div>

									<div className="ASNERISSEO-react-history-summary-grid ASNERISSEO-react-history-summary-grid-compare">
										<div className="ASNERISSEO-react-history-summary-card"><div className="ASNERISSEO-react-history-summary-label">{ __('Passed Checks', 'asneris-seo-toolkit') }</div><div className="ASNERISSEO-react-history-summary-sub">{ `${ comparisonModel.previousCounts.pass } -> ${ comparisonModel.currentCounts.pass }` }</div></div>
										<div className="ASNERISSEO-react-history-summary-card"><div className="ASNERISSEO-react-history-summary-label">{ __('Warnings', 'asneris-seo-toolkit') }</div><div className="ASNERISSEO-react-history-summary-sub">{ `${ comparisonModel.previousCounts.warning } -> ${ comparisonModel.currentCounts.warning }` }</div></div>
										<div className="ASNERISSEO-react-history-summary-card"><div className="ASNERISSEO-react-history-summary-label">{ __('Failed Checks', 'asneris-seo-toolkit') }</div><div className="ASNERISSEO-react-history-summary-sub">{ `${ comparisonModel.previousCounts.fail } -> ${ comparisonModel.currentCounts.fail }` }</div></div>
										<div className="ASNERISSEO-react-history-summary-card"><div className="ASNERISSEO-react-history-summary-label">{ __('Total Checks', 'asneris-seo-toolkit') }</div><div className="ASNERISSEO-react-history-summary-sub">{ comparisonModel.total }</div></div>
									</div>

									<div className="ASNERISSEO-react-history-compare-tabs" role="tablist" aria-label={ __('Comparison Filters', 'asneris-seo-toolkit') }>
										<button type="button" className={ `ASNERISSEO-react-history-compare-tab${ comparisonFilter === COMPARISON_FILTER_ALL ? ' is-active' : '' }` } onClick={ () => setComparisonFilter(COMPARISON_FILTER_ALL) }>{ __('All Changes', 'asneris-seo-toolkit') }</button>
										<button type="button" className={ `ASNERISSEO-react-history-compare-tab${ comparisonFilter === COMPARISON_FILTER_IMPROVED ? ' is-active' : '' }` } onClick={ () => setComparisonFilter(COMPARISON_FILTER_IMPROVED) }>{ `${ __('Improved', 'asneris-seo-toolkit') } (${ comparisonModel.counts.improved })` }</button>
										<button type="button" className={ `ASNERISSEO-react-history-compare-tab${ comparisonFilter === COMPARISON_FILTER_REGRESSED ? ' is-active' : '' }` } onClick={ () => setComparisonFilter(COMPARISON_FILTER_REGRESSED) }>{ `${ __('Regressed', 'asneris-seo-toolkit') } (${ comparisonModel.counts.regressed })` }</button>
										<button type="button" className={ `ASNERISSEO-react-history-compare-tab${ comparisonFilter === COMPARISON_FILTER_NO_CHANGE ? ' is-active' : '' }` } onClick={ () => setComparisonFilter(COMPARISON_FILTER_NO_CHANGE) }>{ `${ __('No Change', 'asneris-seo-toolkit') } (${ comparisonModel.counts.noChange })` }</button>
									</div>

									<div className="ASNERISSEO-react-history-compare-table-wrap">
										<table className="ASNERISSEO-react-history-compare-table">
											<thead>
												<tr>
													<th>{ __('Check', 'asneris-seo-toolkit') }</th>
													<th>{ __('Previous Report', 'asneris-seo-toolkit') }</th>
													<th>{ __('Current Report', 'asneris-seo-toolkit') }</th>
													<th>{ __('Change', 'asneris-seo-toolkit') }</th>
												</tr>
											</thead>
											<tbody>
												{ comparisonModel.grouped.map((group) => {
													const filteredRows = group.rows.filter((row) => comparisonFilter === COMPARISON_FILTER_ALL || row.changeType === comparisonFilter);
													if (filteredRows.length < 1) {
														return null;
													}

													return (
														<Fragment key={ group.key }>
															<tr key={ `${ group.key }-header` } className="ASNERISSEO-react-history-compare-category-row">
																<td colSpan="4">{ group.label }</td>
															</tr>
															{ filteredRows.map((row, index) => {
																const rowKey = `${ group.key }-${ index }`;
																const isExpanded = expandedComparisonRowKey === rowKey;

																return (
																	<tr key={ rowKey } className={ isExpanded ? 'is-mobile-expanded' : '' }>
																		<td data-label={ __('Check', 'asneris-seo-toolkit') } className="ASNERISSEO-react-history-compare-check-cell">
																			<button
																				type="button"
																				className="ASNERISSEO-react-history-compare-card-toggle"
																				onClick={ () => setExpandedComparisonRowKey((current) => (current === rowKey ? '' : rowKey)) }
																				aria-expanded={ isExpanded }
																			>
																				<span className="ASNERISSEO-react-history-compare-card-toggle-label">{ row.label }</span>
																				<span className="ASNERISSEO-react-history-compare-card-toggle-state">{ isExpanded ? __('Collapse', 'asneris-seo-toolkit') : __('Expand', 'asneris-seo-toolkit') }</span>
																			</button>
																		</td>
																	<td data-label={ __('Previous Report', 'asneris-seo-toolkit') }>{ row.previousValue }</td>
																	<td data-label={ __('Current Report', 'asneris-seo-toolkit') }>{ row.currentValue }</td>
																	<td data-label={ __('Change', 'asneris-seo-toolkit') }><span className={ `ASNERISSEO-react-history-compare-change is-${ row.changeType }` }>{ row.changeText }</span></td>
																	</tr>
																);
															}) }
														</Fragment>
													);
												}) }
											</tbody>
										</table>
									</div>
								</>
							) : (
								<div className="ASNERISSEO-react-note-box is-warning">
									<p className="ASNERISSEO-react-note-box-title">{ __('Comparison needs at least two snapshots.', 'asneris-seo-toolkit') }</p>
									<p className="ASNERISSEO-react-mb-0">{ __('Run diagnostics more than once for this priority page to unlock full comparison view.', 'asneris-seo-toolkit') }</p>
								</div>
							)
						) : null }

						{ historyPopupMode === 'history' ? (
							<>

						<div className="ASNERISSEO-react-history-summary-grid">
							<div className="ASNERISSEO-react-history-summary-card is-score">
								<div className="ASNERISSEO-react-history-summary-label">{ __('Current SEO Score', 'asneris-seo-toolkit') }</div>
								<div className="ASNERISSEO-react-history-summary-value">{ popupCurrent ? popupCurrent.score : '-' }</div>
								<div className="ASNERISSEO-react-history-summary-sub">{ popupPrevious ? `${ __('vs previous', 'asneris-seo-toolkit') }: ${ popupPrevious.score } (${ (popupCurrent?.score || 0) - popupPrevious.score > 0 ? '+' : '' }${ (popupCurrent?.score || 0) - popupPrevious.score })` : __('No previous snapshot', 'asneris-seo-toolkit') }</div>
							</div>
							<div className="ASNERISSEO-react-history-summary-card is-health">
								<div className="ASNERISSEO-react-history-summary-label">{ __('Current Health', 'asneris-seo-toolkit') }</div>
								<div className="ASNERISSEO-react-history-summary-value is-text">{ popupCurrent ? getHealthLabel(popupCurrent.health) : '-' }</div>
								<div className="ASNERISSEO-react-history-summary-sub">{ popupPrevious ? `${ __('vs previous', 'asneris-seo-toolkit') }: ${ getHealthLabel(popupPrevious.health) }` : __('No previous snapshot', 'asneris-seo-toolkit') }</div>
							</div>
							<div className="ASNERISSEO-react-history-summary-card is-scans">
								<div className="ASNERISSEO-react-history-summary-label">{ __('Total Scans', 'asneris-seo-toolkit') }</div>
								<div className="ASNERISSEO-react-history-summary-value">{ historyPopupCount }</div>
								<div className="ASNERISSEO-react-history-summary-sub">{ `${ __('Snapshots', 'asneris-seo-toolkit') }: ${ historyPopupItems.length } / ${ historyPopupLimit }` }</div>
							</div>
							<div className="ASNERISSEO-react-history-summary-card is-checks">
								<div className="ASNERISSEO-react-history-summary-label">{ __('Checks Summary (Current)', 'asneris-seo-toolkit') }</div>
								<div className="ASNERISSEO-react-history-checks-inline">
									<span>{ `${ popupCurrent?.counts?.pass || 0 } ${ __('Passed', 'asneris-seo-toolkit') }` }</span>
									<span>{ `${ popupCurrent?.counts?.warning || 0 } ${ __('Warnings', 'asneris-seo-toolkit') }` }</span>
									<span>{ `${ popupCurrent?.counts?.fail || 0 } ${ __('Failed', 'asneris-seo-toolkit') }` }</span>
								</div>
							</div>
						</div>

						<div className="ASNERISSEO-react-history-grid-two">
							<div className="ASNERISSEO-react-history-card">
								<h3>{ __('SEO Score Trend', 'asneris-seo-toolkit') }</h3>
								{ popupScoreChart ? (
									<div className="ASNERISSEO-react-history-line-chart-wrap">
										<svg viewBox={ `0 0 ${ popupScoreChart.width } ${ popupScoreChart.height }` } className="ASNERISSEO-react-history-line-chart" role="img" aria-label={ __('SEO score trend chart', 'asneris-seo-toolkit') }>
											{ popupScoreChart.yTicks.map((tick) => (
												<g key={ `score-y-${ tick }` }>
													<line x1={ popupScoreChart.padding.left } x2={ popupScoreChart.width - popupScoreChart.padding.right } y1={ popupScoreChart.toY(tick) } y2={ popupScoreChart.toY(tick) } className="ASNERISSEO-react-history-line-grid" />
													<text x={ popupScoreChart.padding.left - 8 } y={ popupScoreChart.toY(tick) + 4 } className="ASNERISSEO-react-history-line-y-label">{ tick }</text>
												</g>
											)) }
											<path d={ popupScoreChart.path } className="ASNERISSEO-react-history-line-path is-score" />
											{ popupScoreChart.dots.map((dot) => (
												<g key={ dot.id }>
													<circle cx={ dot.x } cy={ dot.y } r="4" className="ASNERISSEO-react-history-line-dot is-score" />
													<text x={ dot.x } y={ dot.y - 10 } textAnchor="middle" className="ASNERISSEO-react-history-line-value">{ dot.value }</text>
												</g>
											)) }
											{ popupScoreChart.xLabels.map((label) => (
												<text key={ `score-x-${ label.id }` } x={ label.x } y={ popupScoreChart.height - 8 } textAnchor="middle" className="ASNERISSEO-react-history-line-x-label">{ label.label }</text>
											)) }
										</svg>
									</div>
								) : (
									<p className="ASNERISSEO-react-muted">{ __('No trend data available yet.', 'asneris-seo-toolkit') }</p>
								) }
							</div>

							<div className="ASNERISSEO-react-history-card">
								<h3>{ __('Category Performance (Current vs Previous)', 'asneris-seo-toolkit') }</h3>
								<div className="ASNERISSEO-react-history-category-list">
									{ popupCategoryRows.map((row) => (
										<div key={ row.key } className="ASNERISSEO-react-history-category-row">
											<div className="ASNERISSEO-react-history-category-label">{ row.label }</div>
											<div className="ASNERISSEO-react-history-category-bars">
												<div className="ASNERISSEO-react-history-bar-track"><div className="ASNERISSEO-react-history-bar-fill is-prev" style={ { width: `${ row.previousValue }%` } } /></div>
												<div className="ASNERISSEO-react-history-bar-track"><div className="ASNERISSEO-react-history-bar-fill is-current" style={ { width: `${ row.currentValue }%` } } /></div>
											</div>
											<div className="ASNERISSEO-react-history-category-meta">{ `${ row.previousValue }% -> ${ row.currentValue }% (${ row.delta > 0 ? '+' : '' }${ row.delta }%)` }</div>
										</div>
									)) }
								</div>
							</div>
						</div>

						<div className="ASNERISSEO-react-history-grid-two ASNERISSEO-react-history-grid-bottom">
							<div className="ASNERISSEO-react-history-card">
								<h3>{ __('Issue Progress Over Time', 'asneris-seo-toolkit') }</h3>
								{ popupIssueChart.warning && popupIssueChart.fail ? (
									<div className="ASNERISSEO-react-history-line-chart-wrap">
										<div className="ASNERISSEO-react-history-line-legend">
											<span><i className="is-warning" />{ __('Warnings', 'asneris-seo-toolkit') }</span>
											<span><i className="is-fail" />{ __('Failed', 'asneris-seo-toolkit') }</span>
										</div>
										<svg viewBox={ `0 0 ${ popupIssueChart.warning.width } ${ popupIssueChart.warning.height }` } className="ASNERISSEO-react-history-line-chart" role="img" aria-label={ __('Issue progress chart', 'asneris-seo-toolkit') }>
											{ popupIssueChart.warning.yTicks.map((tick) => (
												<g key={ `issue-y-${ tick }` }>
													<line x1={ popupIssueChart.warning.padding.left } x2={ popupIssueChart.warning.width - popupIssueChart.warning.padding.right } y1={ popupIssueChart.warning.toY(tick) } y2={ popupIssueChart.warning.toY(tick) } className="ASNERISSEO-react-history-line-grid" />
													<text x={ popupIssueChart.warning.padding.left - 8 } y={ popupIssueChart.warning.toY(tick) + 4 } className="ASNERISSEO-react-history-line-y-label">{ tick }</text>
												</g>
											)) }
											<path d={ popupIssueChart.warning.path } className="ASNERISSEO-react-history-line-path is-warning" />
											<path d={ popupIssueChart.fail.path } className="ASNERISSEO-react-history-line-path is-fail" />
											{ popupIssueChart.warning.dots.map((dot) => <circle key={ `warning-dot-${ dot.id }` } cx={ dot.x } cy={ dot.y } r="3.5" className="ASNERISSEO-react-history-line-dot is-warning" />) }
											{ popupIssueChart.fail.dots.map((dot) => <circle key={ `fail-dot-${ dot.id }` } cx={ dot.x } cy={ dot.y } r="3.5" className="ASNERISSEO-react-history-line-dot is-fail" />) }
											{ popupIssueChart.warning.xLabels.map((label) => (
												<text key={ `issue-x-${ label.id }` } x={ label.x } y={ popupIssueChart.warning.height - 8 } textAnchor="middle" className="ASNERISSEO-react-history-line-x-label">{ label.label }</text>
											)) }
										</svg>
									</div>
								) : (
									<p className="ASNERISSEO-react-muted">{ __('No issue trend data available yet.', 'asneris-seo-toolkit') }</p>
								) }
							</div>

							<div className="ASNERISSEO-react-history-stack-col">
								<div className="ASNERISSEO-react-history-card ASNERISSEO-react-history-upgrade-card">
									<h3>{ __('Upgrade to Asneris Engine', 'asneris-seo-toolkit') }</h3>
									<p>{ __('Get advanced insights, more history, competitor tracking, and AI recommendations.', 'asneris-seo-toolkit') }</p>
									<a
										href="https://asneris.com/asneris-seo-engine/"
										target="_blank"
										rel="noopener noreferrer"
										className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
									>
										{ __('Login at app.asneris.com', 'asneris-seo-toolkit') }
										<span aria-hidden="true">(external)</span>
									</a>
								</div>

								<div className="ASNERISSEO-react-history-card">
									<h3>{ __('What Changed (Current vs Previous)', 'asneris-seo-toolkit') }</h3>
									<div className="ASNERISSEO-react-history-change-grid">
										<div>
											<h4>{ __('Improved', 'asneris-seo-toolkit') }</h4>
											<ul>
												{ popupWhatChanged.improved.map((entry, index) => <li key={ `improved-${ index }` }>{ entry }</li>) }
											</ul>
										</div>
										<div>
											<h4>{ __('Needs Attention', 'asneris-seo-toolkit') }</h4>
											<ul>
												{ popupWhatChanged.needs.map((entry, index) => <li key={ `needs-${ index }` }>{ entry }</li>) }
											</ul>
										</div>
									</div>
									<div className="ASNERISSEO-react-history-card-actions">
										<button
											type="button"
											className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary"
											onClick={ () => setHistoryPopupMode('comparison') }
											disabled={ !comparisonModel }
										>
											{ __('View Full Report Comparison', 'asneris-seo-toolkit') }
										</button>
									</div>
								</div>

							</div>
						</div>

						<div className="ASNERISSEO-react-history-card ASNERISSEO-react-history-card-full">
							<h3>{ __('History Timeline', 'asneris-seo-toolkit') }</h3>
							<StatusTable
								wrapClassName="ASNERISSEO-react-detail-issues-scroll ASNERISSEO-react-history-timeline-scroll"
								columns={ [
									{ key: 'num', label: '#', width: '8%', align: 'center' },
									{ key: 'date', label: __('Date & Time', 'asneris-seo-toolkit'), width: '28%' },
									{ key: 'seo', label: __('SEO Score', 'asneris-seo-toolkit'), width: '10%', align: 'center' },
									{ key: 'health', label: __('Health', 'asneris-seo-toolkit'), width: '12%', align: 'center' },
									{ key: 'pass', label: __('Passed', 'asneris-seo-toolkit'), width: '10%', align: 'center' },
									{ key: 'warning', label: __('Warnings', 'asneris-seo-toolkit'), width: '10%', align: 'center' },
									{ key: 'fail', label: __('Failed', 'asneris-seo-toolkit'), width: '10%', align: 'center' },
									{ key: 'change', label: __('Change', 'asneris-seo-toolkit'), width: '12%', align: 'center' },
								] }
								rows={ popupTimelineRows }
								emptyMessage={ __('No history data available yet.', 'asneris-seo-toolkit') }
							/>
						</div>
							</>
						) : null }
					</div>
				) : null }
			</div>
			</div>
		</PanelScaffold>
	);
};

export default PageDiagnosticsPanel;
