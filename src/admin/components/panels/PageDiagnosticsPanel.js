import { createPortal, Fragment, useCallback, useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PanelScaffold from '../common/PanelScaffold';
import fetchJson from '../common/fetchJson';
import StatusTable from '../common/StatusTable';
import InlineHelpDetails from '../common/InlineHelpDetails';
import PerformanceTrackerCard from '../common/PerformanceTrackerCard';
import PageDiagnosticsReportHeader from '../page-diagnostics/PageDiagnosticsReportHeader';
import PageDiagnosticsTabs from '../page-diagnostics/PageDiagnosticsTabs';
import SearchAppearancePreview from '../page-diagnostics/SearchAppearancePreview';
import DetailTabContent from '../page-diagnostics/DetailTabContent';
import OverviewTabContent from '../page-diagnostics/OverviewTabContent';
import ContentTabContent from '../page-diagnostics/ContentTabContent';
import ImagesTabContent from '../page-diagnostics/ImagesTabContent';
import LinksTabContent from '../page-diagnostics/LinksTabContent';
import AIDiscoverabilityTabContent from '../page-diagnostics/AIDiscoverabilityTabContent';
import { buildSearchAppearanceVisualModel } from '../page-diagnostics/searchAppearanceModel';
import {
	categorizeDiscoverabilityCheck,
	getCanonicalModelTabKeys,
	getCanonicalFieldsByTab,
	degradeCheckStatus,
} from '../../../app/discoverabilityDataModel';
import { assertUnifiedCollection, assertUnifiedData, getUnifiedComputed, mergeUnifiedItem } from '../../../app/unifiedDataModel';


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

const normalizeHealthStatus = (value) => String(value || '').trim().toLowerCase();

const hasDirectSeoScore = (item) => clampScore(Number(item?.seoScore)) !== null;

const hasDirectAiScore = (item) => clampScore(Number(item?.aiScore)) !== null;

const deriveSeoScore = (item) => {
	const direct = clampScore(Number(item?.seoScore));
	if (direct !== null) {
		return direct;
	}

	const computedScore = clampScore(Number(item?.computed?.seoScore));
	if (computedScore !== null) {
		return computedScore;
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
	const status = normalizeHealthStatus(item?.health);
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

const asArray = (value) => {
	if (Array.isArray(value)) {
		return value;
	}
	if (value && typeof value === 'object') {
		return Object.values(value);
	}
	return [];
};

const mapCheckStatus = (status) => {
	const normalized = String(status || '').trim().toLowerCase();
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
		|| normalized === 'pending'
		|| normalized === 'queued'
		|| normalized === 'running'
		|| normalized === 'processing'
		|| normalized === 'in progress'
		|| normalized === 'in-progress'
		|| normalized === 'in_progress'
	) {
		return normalized === 'pending' || normalized === 'queued' || normalized === 'running' || normalized === 'processing' || normalized === 'in progress' || normalized === 'in-progress' || normalized === 'in_progress'
			? 'pending'
			: 'not_scanned';
	}
	if (normalized === 'fail' || normalized === 'error' || normalized === 'critical') {
		return 'fail';
	}

	return 'fail';
};

const formatCheckStatusLabel = (status) => {
	const normalized = mapCheckStatus(status);
	if (normalized === 'pass') {
		return __('Pass', 'asneris-seo-toolkit');
	}
	if (normalized === 'warning') {
		return __('Warning', 'asneris-seo-toolkit');
	}
	if (normalized === 'pending') {
		return __('Pending', 'asneris-seo-toolkit');
	}
	if (normalized === 'not_scanned') {
		return __('Not scanned', 'asneris-seo-toolkit');
	}

	return __('Fail', 'asneris-seo-toolkit');
};

const getDetailRowStatus = (row) => mapCheckStatus(row?.cells?.[1] || row?.status || 'not scanned');

const countDetailRowsByStatus = (rows = []) => {
	return (Array.isArray(rows) ? rows : []).reduce((acc, row) => {
		const status = getDetailRowStatus(row);

		if (status === 'pass') {
			acc.pass += 1;
		} else if (status === 'warning') {
			acc.warning += 1;
		} else if (status === 'fail') {
			acc.fail += 1;
		} else if (status === 'pending' || status === 'not_scanned') {
			acc.notScanned += 1;
		}

		acc.total += 1;
		return acc;
	}, { pass: 0, warning: 0, fail: 0, notScanned: 0, total: 0 });
};

const countDetailIssues = (counts) => Number(counts?.warning || 0) + Number(counts?.fail || 0) + Number(counts?.notScanned || 0);

const normalizeEvidenceKey = (value) => String(value || '').replace(/[^A-Za-z0-9]/g, '').toLowerCase();

const formatOverviewEvidenceValue = (value) => {
	if (typeof value === 'boolean') {
		return value ? __('Yes', 'asneris-seo-toolkit') : __('No', 'asneris-seo-toolkit');
	}

	if (value === null || value === undefined || value === '') {
		return '';
	}

	return String(value);
};

const resolveOverviewRecordEvidence = (record, fieldName) => {
	const rawEvidence = getRawEvidenceObject(record);
	const requestedKey = String(fieldName || '').trim();
	if (!requestedKey || !rawEvidence || typeof rawEvidence !== 'object') {
		return '';
	}

	if (Object.prototype.hasOwnProperty.call(rawEvidence, requestedKey)) {
		return formatOverviewEvidenceValue(rawEvidence[requestedKey]);
	}

	const normalizedRequestedKey = normalizeEvidenceKey(requestedKey);
	const matchedKey = Object.keys(rawEvidence).find((rawKey) => normalizeEvidenceKey(rawKey) === normalizedRequestedKey);
	return matchedKey ? formatOverviewEvidenceValue(rawEvidence[matchedKey]) : '';
};

const formatOverviewScoreRecordResult = (record) => {
	const linkedFields = Array.isArray(record?.linked_raw_evidence_fields)
		? record.linked_raw_evidence_fields
		: (Array.isArray(record?.linkedRawEvidenceFields) ? record.linkedRawEvidenceFields : []);
	const values = linkedFields
		.map((fieldName) => resolveOverviewRecordEvidence(record, fieldName))
		.map((value) => String(value || '').trim())
		.filter(Boolean);

	return values.length > 0 ? values.join(' / ') : '-';
};

const overviewScoreRecordToStatusRow = (record, index = 0) => {
	const scoreImpact = Number(record?.score_impact ?? record?.scoreImpact ?? 0);
	const recommendedFix = sanitizeUiEvidenceText(record?.recommended_fix || record?.recommendedFix || '');
	const details = Number.isFinite(scoreImpact) && scoreImpact > 0
		? `${ __('Score impact', 'asneris-seo-toolkit') }: -${ scoreImpact } | ${ recommendedFix }`
		: (recommendedFix && recommendedFix !== '-' ? recommendedFix : __('Contributes to overview score.', 'asneris-seo-toolkit'));

	return {
		key: `overview-score-record-${ record?.canonical_field || record?.canonicalField || index }`,
		cells: [
			record?.canonical_field || record?.canonicalField || __('Overview Field', 'asneris-seo-toolkit'),
			mapCheckStatus(record?.canonical_status || record?.canonicalStatus || 'not scanned'),
			formatOverviewScoreRecordResult(record),
			details,
		],
		scoreImpact: Number.isFinite(scoreImpact) ? scoreImpact : 0,
		sectionKey: DETAIL_TAB_OVERVIEW,
		label: String(record?.canonical_field || record?.canonicalField || '').toLowerCase(),
		rawEvidence: getRawEvidenceObject(record),
	};
};

const formatCompletenessIndicator = (check) => {
	if (check?.isDataComplete === false && Array.isArray(check?.missingFields) && check.missingFields.length > 0) {
		const missingList = check.missingFields.join(', ');
		return `âš ï¸ ${__('Incomplete:', 'asneris-seo-toolkit')} ${missingList}`;
	}
	return '';
};

// UNIFIED DESIGN FIX #4: Add source and freshness display
const formatSourceLabel = (source, sourceIsStale) => {
	let sourceLabel = source || 'live-scan';
	const sourceMap = {
		'live-scan': __('Live Scan', 'asneris-seo-toolkit'),
		'live-scan-no-store': __('Live Scan (Draft)', 'asneris-seo-toolkit'),
		'live-scan-non-priority': __('Live Scan', 'asneris-seo-toolkit'),
		'fallback-local': __('Local Data', 'asneris-seo-toolkit'),
		'cron-scan': __('Background Scan', 'asneris-seo-toolkit'),
		'snapshot-skip': __('Cached Data', 'asneris-seo-toolkit'),
		'snapshot': __('Stored Snapshot', 'asneris-seo-toolkit'),
		'latest-fallback': __('Stored Snapshot', 'asneris-seo-toolkit'),
		'stored-snapshot': __('Stored Snapshot', 'asneris-seo-toolkit'),
		'editor-policy-dirty': __('Draft Policy', 'asneris-seo-toolkit'),
		'editor-policy': __('Draft Policy', 'asneris-seo-toolkit'),
		'editor_policy_dirty': __('Draft Policy', 'asneris-seo-toolkit'),
		'editor-draft-policy': __('Draft Policy', 'asneris-seo-toolkit'),
		'editor-local-dirty': __('Live Draft (Local)', 'asneris-seo-toolkit'),
		'editor-local': __('Live Draft (Local)', 'asneris-seo-toolkit'),
	};
	
	if (String(sourceLabel).startsWith('stored-snapshot')) {
		sourceLabel = 'stored-snapshot';
	}

	let displayLabel = sourceMap[sourceLabel] || __('Unknown', 'asneris-seo-toolkit');
	if (sourceIsStale) {
		displayLabel += ` (${__('stale', 'asneris-seo-toolkit')})`;
	}
	return displayLabel;
};

const isStoredSnapshotSource = (source) => {
	const normalized = String(source || '').trim().toLowerCase();
	return normalized === 'snapshot'
		|| normalized === 'snapshot-skip'
		|| normalized === 'latest-fallback'
		|| normalized.startsWith('stored-snapshot');
};

const formatCompletenessStatus = (payload) => {
	if (!payload?.completeness) {
		return null;
	}
	
	const { capturedFields, missingFields, captureQuality } = payload.completeness;
	if (missingFields && missingFields.length > 0) {
		return `${__('Data Incomplete', 'asneris-seo-toolkit')}: ${missingFields.join(', ')}`;
	}
	return null;
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

		return issues.length > 0 ? issues.join(', ') : 'â€”';
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
		return 'â€”';
	}

	return issues.join(', ');
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

const getRawEvidenceObject = (source = {}) => {
	if (source?.rawEvidence && typeof source.rawEvidence === 'object') {
		return source.rawEvidence;
	}

	if (source?.raw_evidence && typeof source.raw_evidence === 'object') {
		return source.raw_evidence;
	}

	return {};
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
	const tabModels = item?.tabModels && typeof item.tabModels === 'object' ? item.tabModels : {};
	const counts = Object.values(tabModels).reduce(
		(acc, model) => {
			const modelCounts = model?.counts && typeof model.counts === 'object' ? model.counts : {};
			acc.critical += Number(modelCounts.fail || 0);
			acc.warning += Number(modelCounts.warning || 0);
			return acc;
		},
		{ critical: 0, warning: 0 }
	);
	const critical = counts.critical;
	const warning = counts.warning;

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

const getBackendTabModel = (item, detailTabKey) => {
	const modelKey = DETAIL_TAB_TO_CANONICAL_MAP_KEY[detailTabKey] || detailTabKey;
	const tabModels = item?.tabModels && typeof item.tabModels === 'object' ? item.tabModels : null;
	const model = tabModels && modelKey ? tabModels[modelKey] : null;
	return model && typeof model === 'object' ? model : null;
};

const backendTabRowsToStatusRows = (model, detailTabKey) => {
	return asArray(model?.rows).map((row) => ({
		cells: [
			row?.label || '-',
			mapCheckStatus(row?.status || 'not scanned') === 'not_scanned' ? 'not scanned' : mapCheckStatus(row?.status || 'not scanned'),
			row?.result ?? '-',
			row?.details || '-',
		],
		sectionKey: DETAIL_TAB_SECTION_KEY_MAP[detailTabKey] || detailTabKey,
		label: String(row?.label || '').toLowerCase(),
		rawEvidence: row?.rawEvidence && typeof row.rawEvidence === 'object' ? row.rawEvidence : {},
	}));
};

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
	'Canonical': [/canonical/i],
	'Self Canonical': [/self canonical|canonical self-check/i],
	'X-Robots-Tag': [/x-robots-tag/i],
	'HTTP Status': [/http status/i],
	'H1 Presence': [/h1 exists|h1 present/i],
	'Multiple H1': [/multiple h1/i],
	'Heading Structure': [/heading structure/i],
	'Heading Hierarchy': [/heading hierarchy/i],
	'Content Depth (Word Count)': [/word count|content depth/i],
	'Content Present': [/content present/i],
	'Readability': [/readability/i],
	'Images Found': [/images found/i],
	'Image ALT Coverage': [/image alt coverage|images? & alt/i],
	'Missing ALT': [/missing alt/i],
	'Empty ALT': [/empty alt/i],
	'Featured Image': [/featured image/i],
	'Internal Links': [/internal links/i],
	'External Links': [/external links/i],
	'Nofollow Links': [/nofollow links/i],
	'Structured Data Present': [/structured data present|structured data found/i],
	'Schema Validation': [/schema validation|json-ld valid|json-ld/i],
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
	const source = String(item?.source || '').trim();
	const sourceIsStale = item?.sourceIsStale === true;
	const publishedGmt = String(item?.publishedGmt || '').trim();
	const modifiedGmt = String(item?.modifiedGmt || '').trim();
	const postType = String(item?.postType || '').trim();
	const postStatus = String(item?.postStatus || '').trim();
	const author = String(item?.author || item?.authorName || '').trim();
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

	if (fieldLabel === 'Page Fetch') {
		if (!source && (!Number.isFinite(httpStatus) || httpStatus <= 0)) {
			return unknown;
		}

		const status = Number.isFinite(httpStatus) && httpStatus >= 200 && httpStatus < 400 ? 'pass' : 'warning';
		return { status, result: source || __('Available', 'asneris-seo-toolkit'), details: `${ __('HTTP Status', 'asneris-seo-toolkit') }: ${ Number.isFinite(httpStatus) ? httpStatus : '-' }` };
	}
	if (fieldLabel === 'Local Fallback') {
		if (!source) {
			return unknown;
		}

		const isFallback = /fallback/i.test(source) || sourceIsStale;
		return { status: isFallback ? 'warning' : 'pass', result: isFallback ? __('Fallback used', 'asneris-seo-toolkit') : __('Not used', 'asneris-seo-toolkit'), details: `${ __('Source', 'asneris-seo-toolkit') }: ${ source }` };
	}
	if (fieldLabel === 'Post Freshness' || fieldLabel === 'Last Updated Date') {
		return modifiedGmt
			? { status: 'pass', result: modifiedGmt, details: __('Source: unifiedData.raw.modifiedGmt', 'asneris-seo-toolkit') }
			: unknown;
	}
	if (fieldLabel === 'Post Context') {
		return postType || postStatus
			? { status: 'pass', result: [postType, postStatus].filter(Boolean).join(' / '), details: __('Source: unified post type/status fields.', 'asneris-seo-toolkit') }
			: unknown;
	}
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
	if (fieldLabel === 'Open Graph Setup') {
		const present = [ogTitle, ogDescription, ogImage].filter(Boolean).length;
		return { status: present >= 2 ? 'pass' : 'warning', result: `${ present }/3`, details: __('Derived from Open Graph title, description, and image fields.', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Twitter Card') {
		return { status: ogTitle || ogDescription || ogImage ? 'warning' : 'not scanned', result: ogTitle || ogDescription || ogImage ? __('Derived from OG fields', 'asneris-seo-toolkit') : __('Not available', 'asneris-seo-toolkit'), details: __('No dedicated twitter-card source field in current payload.', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Redirect Status') {
		if (!Number.isFinite(httpStatus) || httpStatus <= 0) {
			return unknown;
		}

		return { status: httpStatus >= 300 && httpStatus < 400 ? 'warning' : 'pass', result: httpStatus >= 300 && httpStatus < 400 ? __('Redirect detected', 'asneris-seo-toolkit') : __('No redirect', 'asneris-seo-toolkit'), details: `${ httpStatus }` };
	}
	if (fieldLabel === 'Final Destination') {
		return pageUrl
			? { status: 'pass', result: pageUrl, details: __('Source: unifiedData.raw.url', 'asneris-seo-toolkit') }
			: unknown;
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
	if (fieldLabel === 'Canonical Target HTTP 200') {
		if (!canonical || !Number.isFinite(httpStatus) || httpStatus <= 0) {
			return unknown;
		}

		return { status: httpStatus >= 200 && httpStatus < 300 ? 'pass' : 'warning', result: `${ httpStatus }`, details: __('Derived from available page HTTP status for canonical target transparency.', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Robots Meta') {
		return robotsIndex || robotsFollow
			? { status: 'pass', result: `${ robotsIndex || 'index' }/${ robotsFollow || 'follow' }`, details: __('Source: unified robots fields', 'asneris-seo-toolkit') }
			: unknown;
	}
	if (fieldLabel === 'Follow Directive') {
		return robotsFollow
			? { status: /nofollow/i.test(robotsFollow) ? 'warning' : 'pass', result: robotsFollow, details: __('Source: unifiedData.raw.robotsFollow', 'asneris-seo-toolkit') }
			: unknown;
	}
	if (fieldLabel === 'Indexability') {
		if (!robotsIndex && !xRobotsTag && (!Number.isFinite(httpStatus) || httpStatus <= 0)) {
			return unknown;
		}

		const blocked = /noindex|none/i.test(`${ robotsIndex } ${ xRobotsTag }`);
		const statusOk = !Number.isFinite(httpStatus) || httpStatus <= 0 || (httpStatus >= 200 && httpStatus < 400);
		return { status: blocked || !statusOk ? 'warning' : 'pass', result: blocked ? __('Noindex', 'asneris-seo-toolkit') : __('Indexable', 'asneris-seo-toolkit'), details: __('Derived from robots and HTTP status fields.', 'asneris-seo-toolkit') };
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
	if (fieldLabel === 'Schema Settings') {
		if (typeof schemaEnabled !== 'boolean' && !schemaType) {
			return unknown;
		}

		const enabled = typeof schemaEnabled === 'boolean' ? schemaEnabled : Boolean(schemaType);
		return { status: enabled ? 'pass' : 'warning', result: enabled ? __('Enabled', 'asneris-seo-toolkit') : __('Disabled', 'asneris-seo-toolkit'), details: __('Source: schema enabled/type fields.', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Structured Data Found' || fieldLabel === 'Structured Data Present') {
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
	if (fieldLabel === 'Schema Validation' || fieldLabel === 'JSON-LD Valid') {
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
			: { status: 'warning', result: __('Missing', 'asneris-seo-toolkit'), details: __('No schema type detected in source payload.', 'asneris-seo-toolkit') };
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
	if (fieldLabel === 'FAQ Ready' || fieldLabel === 'FAQ Content' || fieldLabel === 'FAQ Signals') {
		const signal = getAiCanonicalSignal('FAQ Signals');
		if (signal) {
			return signal;
		}

		if (!Number.isFinite(faqCount)) {
			return unknown;
		}
		return { status: faqCount > 0 ? 'pass' : 'warning', result: `${ faqCount }`, details: __('Source: unifiedData.raw.faqCount', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'External References') {
		if (!Number.isFinite(externalLinks)) {
			return unknown;
		}

		return { status: externalLinks > 0 ? 'pass' : 'warning', result: `${ externalLinks }`, details: __('Derived from external link count.', 'asneris-seo-toolkit') };
	}
	if (fieldLabel === 'Published Date') {
		return publishedGmt || modifiedGmt
			? { status: 'pass', result: publishedGmt || modifiedGmt, details: publishedGmt ? __('Source: unifiedData.raw.publishedGmt', 'asneris-seo-toolkit') : __('Fallback: using modified date because published date is not exposed in current payload.', 'asneris-seo-toolkit') }
			: unknown;
	}
	if (fieldLabel === 'Organization Information') {
		if (typeof organizationSchema === 'boolean') {
			return { status: organizationSchema ? 'pass' : 'warning', result: organizationSchema ? __('Present', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'), details: __('Derived from organization schema/type fields.', 'asneris-seo-toolkit') };
		}

		return schemaType ? { status: /organization|localbusiness/i.test(schemaType) ? 'pass' : 'warning', result: schemaType, details: __('Derived from schema type.', 'asneris-seo-toolkit') } : unknown;
	}
	if (fieldLabel === 'Media Context') {
		if (!Number.isFinite(imageCount)) {
			return unknown;
		}

		const issueCount = (Number.isFinite(missingAlt) ? missingAlt : 0) + (Number.isFinite(emptyAlt) ? emptyAlt : 0);
		return { status: imageCount > 0 && issueCount === 0 ? 'pass' : 'warning', result: imageCount > 0 ? `${ imageCount } ${ __('images', 'asneris-seo-toolkit') }` : __('No images', 'asneris-seo-toolkit'), details: `${ __('ALT issues', 'asneris-seo-toolkit') }: ${ issueCount }` };
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
	if (fieldLabel === 'Structured Content') {
		const signal = getAiCanonicalSignal('Table/List Detection');
		return signal || unknown;
	}
	if (fieldLabel === 'Author Information') {
		const signal = getAiCanonicalSignal('Brand Mentions');
		if (signal) {
			return signal;
		}

		return author ? { status: 'pass', result: author, details: __('Source: unified author field.', 'asneris-seo-toolkit') } : unknown;
	}
	if (fieldLabel === 'Machine Readability') {
		const signal = getAiCanonicalSignal('Language Declaration');
		return signal || unknown;
	}

	return unknown;
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

const getCanonicalHistoryRows = (historyItem, tabKey) => {
	const backendModel = getBackendTabModel(historyItem, tabKey);
	if (backendModel) {
		return backendTabRowsToStatusRows(backendModel, tabKey);
	}

	return [];
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
	const backendModel = getBackendTabModel(historyItem, tabKey);
	if (backendModel?.counts && typeof backendModel.counts === 'object') {
		const counts = backendModel.counts;
		const notScanned = Number(counts.notScanned || 0);
		return {
			pass: Number(counts.pass || 0),
			warning: Number(counts.warning || 0),
			fail: Number(counts.fail || 0) + notScanned,
			total: Number(counts.total || 0),
			issues: Number(counts.issues || 0),
		};
	}

	const rows = getCanonicalHistoryRows(historyItem, tabKey);

	if (!Array.isArray(rows) || rows.length < 1) {
		return { pass: 0, warning: 0, fail: 0, total: 0, issues: 0 };
	}

	const counts = countDetailRowsByStatus(rows);
	const total = counts.total;
	const issues = countDetailIssues(counts);
	return {
		pass: counts.pass,
		warning: counts.warning,
		fail: counts.fail + counts.notScanned,
		total,
		issues,
	};
};

const buildCanonicalHistoryUxMeta = (historyItem, tabKey) => {
	const canonicalMapKey = DETAIL_TAB_TO_CANONICAL_MAP_KEY[tabKey] || '';
	const canonicalFields = getCanonicalFieldsByTab(canonicalMapKey);
	const rows = getCanonicalHistoryRows(historyItem, tabKey);
	const expectedTotal = Array.isArray(canonicalFields) ? canonicalFields.length : 0;

	if (expectedTotal < 1) {
		return {
			coverage: '-/-',
			coverageTone: 'neutral',
			sourceLabel: __('Unknown', 'asneris-seo-toolkit'),
		};
	}

	const presentSet = new Set(
		(Array.isArray(rows) ? rows : [])
			.filter((row) => mapCheckStatus(row?.cells?.[1] || 'not scanned') !== 'not_scanned')
			.map((row) => String(row?.cells?.[0] || '').trim().toLowerCase())
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
	const source = String(historyItem?.source || '').trim().toLowerCase();
	const isStoredSnapshot = isStoredSnapshotSource(source);

	const coverageTone = covered >= expectedTotal
		? 'success'
		: (covered > 0 ? 'warning' : 'fail');

	return {
		coverage: `${ covered }/${ expectedTotal }`,
		coverageTone,
		sourceLabel: isStoredSnapshot
			? __('Stored Snapshot', 'asneris-seo-toolkit')
			: hasDirectTabBucket
			? __('Tab Snapshot', 'asneris-seo-toolkit')
			: __('Legacy Fallback', 'asneris-seo-toolkit'),
	};
};

const countStatus = (rows) => countDetailRowsByStatus(rows);

const toStatusTone = (counts) => {
	if (counts.fail > 0) {
		return 'fail';
	}
	if (counts.warning > 0) {
		return 'warning';
	}
	if (counts.notScanned > 0) {
		return 'warning';
	}
	return 'success';
};

const buildTabCardModel = (tabKey, item) => {
	const backendModel = getBackendTabModel(item || {}, tabKey);
	const counts = backendModel?.counts && typeof backendModel.counts === 'object'
		? {
			pass: Number(backendModel.counts.pass || 0),
			warning: Number(backendModel.counts.warning || 0),
			fail: Number(backendModel.counts.fail || 0),
			notScanned: Number(backendModel.counts.notScanned || 0),
			total: Number(backendModel.counts.total || 0),
		}
		: { pass: 0, warning: 0, fail: 0, notScanned: 0, total: 0 };
	const tone = backendModel ? toStatusTone(counts) : 'warning';
	const title = DETAIL_TAB_LABEL_BY_KEY[tabKey] || __('Details', 'asneris-seo-toolkit');
	const backendScore = Number(backendModel?.score);

	return {
		title,
		status: tone === 'success' ? __('Good', 'asneris-seo-toolkit') : __('Needs Work', 'asneris-seo-toolkit'),
		score: Number.isFinite(backendScore) ? Math.max(0, Math.min(100, Math.round(backendScore))) : null,
		scoreMessage: backendModel ? null : __('Backend tab model is unavailable for this report. Rerun diagnostics.', 'asneris-seo-toolkit'),
		summary: backendModel
			? __('Backend diagnostics model loaded for this tab.', 'asneris-seo-toolkit')
			: __('No backend diagnostics model is available for this tab.', 'asneris-seo-toolkit'),
		detailsTitle: `${ title } ${ __('Details', 'asneris-seo-toolkit') }`,
		summaryHighlights: [],
		detailHighlights: [],
		highlights: [],
		note: backendModel
			? __('Score and issue status are supplied by the backend diagnostics engine.', 'asneris-seo-toolkit')
			: __('Run diagnostics again to generate backend tab data.', 'asneris-seo-toolkit'),
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
	const historyPopupPortalTarget = typeof document !== 'undefined' ? document.body : null;
	const detailTabsDragStateRef = useRef({
		isDragging: false,
		startX: 0,
		startScrollLeft: 0,
		hasMoved: false,
		suppressClick: false,
	});

	const diagnosticsPostBaseUrl = useMemo(
		() => (restUrl || '').replace(/\/page-diagnostics\/overview$/, '/page-diagnostics-v2/run'),
		[restUrl]
	);

	const diagnosticsDraftPolicyBaseUrl = useMemo(
		() => (restUrl || '').replace(/\/page-diagnostics\/overview$/, '/page-diagnostics-v2/draft-policy'),
		[restUrl]
	);

	const diagnosticsReadBaseUrl = useMemo(
		() => (restUrl || '').replace(/\/page-diagnostics\/overview$/, '/diagnostics'),
		[restUrl]
	);

	const diagnosticsHistoryReadBaseUrl = useMemo(
		() => (restUrl || '').replace(/\/page-diagnostics\/overview$/, '/page-diagnostics-v2/history'),
		[restUrl]
	);

	const diagnosticsHistoryDeleteBaseUrl = useMemo(
		() => (restUrl || '').replace(/\/page-diagnostics\/overview$/, '/page-diagnostics/history'),
		[restUrl]
	);

	const buildHistoryRequestUrl = useCallback((postId, limit = 10) => {
		if (!diagnosticsHistoryReadBaseUrl || !postId) {
			return '';
		}

		const requestUrl = new URL(diagnosticsHistoryReadBaseUrl, window.location.origin);
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
	}, [diagnosticsHistoryReadBaseUrl]);

	const diagnosticsRecordsClearBaseUrl = useMemo(
		() => (restUrl || '').replace(/\/page-diagnostics\/overview$/, '/page-diagnostics/records/clear'),
		[restUrl]
	);

	const closeReportDialog = useCallback(() => {
		setIsClearConfirmOpen(false);
		setIsReportDialogOpen(false);
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
		() => new Set(asArray(data.priorityItems).map((item) => String(item.postId))),
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

				const items = asArray(rawItems).map((item) => mergeUnifiedItem(item)).filter(Boolean);
				const priorityItems = asArray(rawPriorityItems).map((item) => mergeUnifiedItem(item)).filter(Boolean);
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
		if (!isHistoryPopupOpen || typeof document === 'undefined') {
			return undefined;
		}

		const originalBodyOverflow = document.body.style.overflow;
		document.body.style.overflow = 'hidden';

		const onKeyDown = (event) => {
			if (event.key === 'Escape') {
				setIsHistoryPopupOpen(false);
			}
		};

		document.addEventListener('keydown', onKeyDown);
		return () => {
			document.removeEventListener('keydown', onKeyDown);
			document.body.style.overflow = originalBodyOverflow;
		};
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
		if (!postId || !diagnosticsHistoryReadBaseUrl) {
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
				const rows = asArray(payload?.history);
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
		if (!item?.postId) {
			return;
		}

		setHistoryPopupItem(item);
		setHistoryPopupItems([]);
		setHistoryPopupCount(0);
		setHistoryPopupLimit(10);
		setHistoryPopupFetchLimit(0);
		setHistoryPopupMode('history');
		setHistoryPopupError('');
		setComparisonFilter(COMPARISON_FILTER_ALL);
		setIsHistoryPopupOpen(true);
		loadPopupHistory(item.postId, 10);
	};

	useEffect(() => {
		if (!isReportDialogOpen || !selectedResult?.postId || !diagnosticsHistoryReadBaseUrl || !shouldShowSnapshotHistory) {
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
	}, [isReportDialogOpen, selectedResult?.postId, diagnosticsHistoryReadBaseUrl, restNonce, shouldShowSnapshotHistory, buildHistoryRequestUrl]);

	const deleteHistoryRecord = (historyId) => {
		if (!selectedResult?.postId || !diagnosticsHistoryDeleteBaseUrl || !historyId || deletingHistoryId) {
			return;
		}

		setDeletingHistoryId(historyId);

		fetchJson(`${ diagnosticsHistoryDeleteBaseUrl }/${ selectedResult.postId }/delete`, {
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
			&& editorIsDirty
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
					items: asArray(previous.items).map((item) => (
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
					priorityItems: asArray(previous.priorityItems).map((item) => (
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

		if (!diagnosticsHistoryReadBaseUrl || !postId || testingPostId) {
			return Promise.resolve(null);
		}

		setTestingPostId(postId);
		setErrorMessage('');

		const requestUrl = buildHistoryRequestUrl(postId, 1);

		const requestOptions = {
			method: 'GET',
			headers: {
				'X-WP-Nonce': restNonce || '',
			},
		};

		return fetchJson(requestUrl, requestOptions)
			.then((payload) => {
				const latestStoredPayload = Array.isArray(payload?.history) && payload.history.length > 0
					? payload.history[0]
					: payload;

				return latestStoredPayload;
			})
			.then((latestStoredPayload) => {
				assertUnifiedData(latestStoredPayload, 'diagnostics.view.stored');
				const normalizedPayload = mergeUnifiedItem(latestStoredPayload || {});

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

				return latestStoredPayload;
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
			// UNIFIED DESIGN FIX #4: Use degraded status if data incomplete
			const degradedCheckStatus = degradeCheckStatus(check?.status, check);
			const status = mapCheckStatus(degradedCheckStatus);
			const completenessInfo = formatCompletenessIndicator(check);
			const rawEvidence = getRawEvidenceObject(check);
			const details = sanitizeUiEvidenceText(check?.details || '-');
			sectionBuckets[category].push({
				id: `${ selectedResult?.postId || 'post' }-${ index }`,
				label: check?.label || '-',
				status,
				result: String(check?.result ?? '-'),
				details,
				rawEvidence,
				completenessInfo,
			});

			debugRows.push({
				id: `${ selectedResult?.postId || 'post' }-debug-${ index }`,
				label: check?.label || '-',
				category,
				sourceKey: hasBackendCategory ? 'backend' : 'fallback',
				sourceLabel: hasBackendCategory ? __('backend', 'asneris-seo-toolkit') : __('fallback', 'asneris-seo-toolkit'),
				isDataComplete: check?.isDataComplete !== false,
				missingFields: check?.missingFields || [],
			});
		});

		const counts = checks.reduce(
			(acc, check) => {
				const degradedCheckStatus = degradeCheckStatus(check?.status, check);
				const status = mapCheckStatus(degradedCheckStatus);
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
				cells: [row.label, formatCheckStatusLabel(row.status), row.result, row.completenessInfo ? `${row.details} | ${row.completenessInfo}` : row.details],
				rawEvidence: row.rawEvidence,
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
		? reconcileMetaTitleLengthFields(
			isStoredSnapshotSource(selectedResult?.source)
				? { ...selectedResult }
				: {
					...([...asArray(data.priorityItems), ...asArray(data.items)].find((item) => String(item.postId) === String(selectedResult.postId)) || {}),
					...selectedResult,
				}
		)
		: null;
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

	const filteredPriorityItems = asArray(data.priorityItems).filter((item) => matchesIndexability(item));
	const nonPriorityItems = asArray(data.items)
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
	const visibleRunPostIds = asArray(visibleRunItems).map((item) => String(item.postId));
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

	const pagedPriorityRows = asArray(pagedPriorityItems).map((item) => toItemRow(item));
	const nonPriorityRows = asArray(nonPriorityItems).map((item) => ({
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
	const selectedDetailSourceLabel = isStoredSnapshotSource(selectedDetailSource)
		? __('Stored snapshot', 'asneris-seo-toolkit')
		: selectedDetailSource === 'editor-local-dirty'
			? __('Live draft (local)', 'asneris-seo-toolkit')
			: selectedDetailSource === 'editor-draft-policy'
				? __('Draft policy', 'asneris-seo-toolkit')
			: selectedDetailSource.includes('live-scan')
				? __('Live run', 'asneris-seo-toolkit')
				: __('Live run', 'asneris-seo-toolkit');
	const isSnapshotSource = isStoredSnapshotSource(selectedDetailSource);
	const activeDetailRows = useMemo(
		() => {
			const sourceItem = selectedResultItem || selectedResult || {};
			const backendModel = getBackendTabModel(sourceItem, activeDetailTab);
			return backendModel ? backendTabRowsToStatusRows(backendModel, activeDetailTab) : [];
		},
		[activeDetailTab, selectedResultItem, selectedResult]
	);
	const overviewSummaryRows = useMemo(
		() => {
			const sourceItem = selectedResultItem || selectedResult || {};
			const overviewScoreRecords = Array.isArray(sourceItem?.overviewScoreRecords) ? sourceItem.overviewScoreRecords : [];
			if (overviewScoreRecords.length > 0) {
				return overviewScoreRecords.map((record, index) => overviewScoreRecordToStatusRow(record, index));
			}

			const overviewModel = getBackendTabModel(sourceItem, DETAIL_TAB_OVERVIEW);
			const overviewRows = overviewModel ? backendTabRowsToStatusRows(overviewModel, DETAIL_TAB_OVERVIEW) : [];
			if (overviewRows.length > 0) {
				return overviewRows;
			}

			const identifiedFieldsModel = getBackendTabModel(sourceItem, DETAIL_TAB_SEARCH_APPEARANCE);
			return identifiedFieldsModel ? backendTabRowsToStatusRows(identifiedFieldsModel, DETAIL_TAB_OVERVIEW) : [];
		},
		[selectedResultItem, selectedResult]
	);
	const overviewStatusCounts = countDetailRowsByStatus(overviewSummaryRows);
	const overviewIssueCount = countDetailIssues(overviewStatusCounts);
	const topPriorityItems = overviewSummaryRows
		.filter((row) => {
			const status = getDetailRowStatus(row);
			return status === 'fail' || status === 'warning' || status === 'not_scanned';
		})
		.sort((left, right) => Number(right?.scoreImpact || 0) - Number(left?.scoreImpact || 0))
		.slice(0, 4)
		.map((row) => String(row?.cells?.[0] || '').trim())
		.filter(Boolean);
	const explicitHealthStatus = normalizeHealthStatus(selectedResultItem?.health || selectedResult?.health || '');
	const hasExplicitHealth = Boolean(healthMeta[explicitHealthStatus]);
	const hasCriticalIssues = hasExplicitHealth
		? explicitHealthStatus === 'poor'
		: overviewStatusCounts.fail > 0;
	const hasOverviewScoreIssues = overviewIssueCount > 0;
	const overviewHealthMessage = hasCriticalIssues
		? __('This page has critical score contributors that are impacting its search visibility and performance.', 'asneris-seo-toolkit')
		: (hasOverviewScoreIssues
			? __('No critical blockers were detected, but some overview score contributors need attention.', 'asneris-seo-toolkit')
			: __('All overview score contributors are currently passing.', 'asneris-seo-toolkit'));
	const showGoodNews = (selectedResultItem?.robotsIndex || 'index') === 'index' && !hasOverviewScoreIssues;
	const historyRows = asArray(historyItems).map((row) => {
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

		return asArray(historyItems).map((item, index) => {
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

		const normalized = asArray(historyItems)
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
		return asArray(historyPopupItems)
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
		const descending = [ ...asArray(popupHistorySeries) ].sort((a, b) => b.timestamp - a.timestamp);
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
					delta === null ? 'â€”' : `${ delta > 0 ? '+' : '' }${ delta }`,
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
		const series = asArray(popupHistorySeries).map((item) => ({
			id: `score-${ item.id }-${ item.timestamp }`,
			label: formatChartDateLabel(item.generatedAt),
			value: Number(item.score || 0),
		}));

		return buildLineChartModel(series, { yMax: 100, width: 640, height: 220 });
	}, [popupHistorySeries]);

	const popupIssueChart = useMemo(() => {
		const warningSeries = asArray(popupHistorySeries).map((item) => ({
			id: `warning-${ item.id }-${ item.timestamp }`,
			label: formatChartDateLabel(item.generatedAt),
			value: Number(item.counts.warning || 0),
		}));
		const failSeries = asArray(popupHistorySeries).map((item) => ({
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
	const activeIssueListRows = activeDetailRows;

	const activeDetailStatusCounts = countDetailRowsByStatus(activeIssueListRows);
	const activeDetailIssues = countDetailIssues(activeDetailStatusCounts);
	const activeDetailCard = buildTabCardModel(activeDetailTab, selectedResultItem);
	const activeDetailCardScore = activeDetailCard.score;
	const activeDetailCardScoreLabel = Number.isFinite(activeDetailCardScore)
		? `${ activeDetailCardScore }/100`
		: '-';
	const rawSummaryHighlights = activeDetailCard.summaryHighlights || activeDetailCard.highlights || [];
	const activeSummaryHighlights = DETAIL_TAB_HIDE_MATCH_COUNT.has(activeDetailTab)
		? asArray(rawSummaryHighlights).filter((entry) => entry?.kind !== 'match-count')
		: asArray(rawSummaryHighlights);
	const hasSummaryHighlightSources = activeSummaryHighlights.some((entry) => entry?.source);
	const activeDetailHighlights = asArray(activeDetailCard.detailHighlights || activeDetailCard.highlights || []);
	const hasDerivedDetailSummaryRows = activeDetailHighlights.length < 1 && activeIssueListRows.length > 0;
	const hasDetailSummaryContent = activeDetailHighlights.length > 0 || hasDerivedDetailSummaryRows;
	const effectiveDetailContentSection = hasDetailSummaryContent
		? activeDetailContentSection
		: DETAIL_CONTENT_SECTION_ISSUES;
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
				label: __('Search Preview: Title', 'asneris-seo-toolkit'),
				value: activeSearchAppearanceVisual.googleTitle || '-',
				source: activeSearchAppearanceVisual.searchTitleSource || __('Fallback', 'asneris-seo-toolkit'),
				sourceTone: resolveSearchAppearanceSourceTone(activeSearchAppearanceVisual.searchTitleSource || __('Fallback', 'asneris-seo-toolkit')),
			},
			{
				label: __('Search Preview: Description', 'asneris-seo-toolkit'),
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
		const detailTabContent = (() => {
			if (activeDetailTab === DETAIL_TAB_OVERVIEW) {
				return (
					<OverviewTabContent
						isHistoryLocked={ isHistoryLocked && shouldShowSnapshotHistory }
						shouldShowSnapshotHistory={ shouldShowSnapshotHistory }
						isHistoryLoading={ isHistoryLoading }
						historyTrend={ historyTrend }
						historyRows={ historyRows }
						historySectionRef={ historySectionRef }
						isSnapshotHistorySuppressedInEmbeddedFlow={ isSnapshotHistorySuppressedInEmbeddedFlow }
						isEmbeddedDetailOpenFlow={ isEmbeddedDetailOpenFlow }
						historyCount={ historyCount }
						historyLimit={ historyLimit }
						jumpToSnapshotHistory={ jumpToSnapshotHistory }
						overviewSummaryRows={ overviewSummaryRows }
						overviewIssueCount={ overviewIssueCount }
						topPriorityItems={ topPriorityItems }
						hasCriticalIssues={ hasCriticalIssues }
						hasOverviewScoreIssues={ hasOverviewScoreIssues }
						overviewHealthMessage={ overviewHealthMessage }
						showGoodNews={ showGoodNews }
						StatusTableComponent={ StatusTable }
					/>
				);
			}

			if (activeDetailTab === DETAIL_TAB_SEARCH_APPEARANCE) {
				return (
					<SearchAppearancePreview
						isMobileViewport={ isMobileViewport }
						isSearchAppearanceMobile={ isSearchAppearanceMobile }
						activeSearchAppearanceVisual={ activeSearchAppearanceVisual }
						hasSocialImageTemplate={ hasSocialImageTemplate }
						socialImageFallbackTitle={ socialImageFallbackTitle }
						searchAppearanceSourceRows={ searchAppearanceSourceRows }
						onViewportChange={ setSearchAppearanceViewport }
					/>
				);
			}

			const detailTabRenderers = {
				[DETAIL_TAB_CONTENT]: ContentTabContent,
				[DETAIL_TAB_IMAGES]: ImagesTabContent,
				[DETAIL_TAB_LINKS]: LinksTabContent,
				[DETAIL_TAB_AI_DISCOVERABILITY]: AIDiscoverabilityTabContent,
			};
			const SharedDetailTabContent = detailTabRenderers[activeDetailTab] || DetailTabContent;

			return (
				<SharedDetailTabContent
					activeDetailTabLabel={ activeDetailTabLabel }
					activeDetailCard={ activeDetailCard }
					activeDetailCardScore={ activeDetailCardScore }
					activeDetailCardScoreLabel={ activeDetailCardScoreLabel }
					activeDetailIssues={ activeDetailIssues }
					activeIssueListRows={ activeIssueListRows }
					activeSummaryHighlights={ activeSummaryHighlights }
					hasSummaryHighlightSources={ hasSummaryHighlightSources }
					useDetailIssueAccordion={ useDetailIssueAccordion }
					effectiveDetailContentSection={ effectiveDetailContentSection }
					shouldShowSnapshotHistory={ shouldShowSnapshotHistory }
					isHistoryLoading={ isHistoryLoading }
					detailTabHistoryRows={ detailTabHistoryRows }
					StatusTableComponent={ StatusTable }
					getScoreBand={ getScoreBand }
					detailContentSectionDetailsKey={ DETAIL_CONTENT_SECTION_DETAILS }
					detailContentSectionIssuesKey={ DETAIL_CONTENT_SECTION_ISSUES }
					setActiveDetailContentSection={ setActiveDetailContentSection }
					hasDetailSummaryContent={ hasDetailSummaryContent }
					hasDerivedDetailSummaryRows={ hasDerivedDetailSummaryRows }
					activeDetailHighlights={ activeDetailHighlights }
				/>
			);
		})();

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
						{ asArray(availablePostTypes).map((typeOption) => (
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
						{ asArray(availablePostStatuses).map((statusOption) => (
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
						
												<button type="button" className="ASNERISSEO-modal-close ASNERISSEO-react-detail-close" onClick={ closeReportDialog }>
								&times;
							</button>
							<PageDiagnosticsReportHeader
								title={ selectedResultItem?.title || __('Diagnostics Detail', 'asneris-seo-toolkit') }
								url={ selectedResult.url }
								generatedAtLabel={ formatDateTimeLabel(selectedResult.lastScanGmt) }
								sourceLabel={ formatSourceLabel(selectedResult?.source, selectedResult?.sourceIsStale) }
								completenessStatus={ formatCompletenessStatus(selectedResult) }
								isCollapsed={ isDetailHeaderCollapsed }
								onToggleCollapse={ setIsDetailHeaderCollapsed }
								actions={ (
									<Fragment>
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
									</Fragment>
								) }
								scoreBandClassName={ getScoreBand(effectiveSelectedSeoScore) }
								statusClassName={ selectedHealth.tone }
								scoreValueLabel={ `${ effectiveSelectedSeoScore ?? '-' }/100` }
								statusValueLabel={ selectedHealth.label }
								scoreMessage={ selectedSeoScoreMessage }
							>
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
							</PageDiagnosticsReportHeader>

							<PageDiagnosticsTabs
								tabs={ DETAIL_VIEW_TABS.map((tab) => ({
									...tab,
									disabled: isHistoryLocked && shouldShowSnapshotHistory && tab.key !== DETAIL_TAB_OVERVIEW,
								})) }
								activeTab={ activeDetailTab }
								onTabChange={ setActiveDetailTab }
								tabListRef={ detailTabsRef }
								onWheel={ handleDetailTabsWheel }
								onPointerDown={ handleDetailTabsPointerDown }
								onPointerMove={ handleDetailTabsPointerMove }
								onPointerUp={ stopDetailTabsDrag }
								onPointerCancel={ stopDetailTabsDrag }
								onPointerLeave={ stopDetailTabsDrag }
								onClickCapture={ handleDetailTabsClickCapture }
								tabListStyle={ {
									display: 'flex',
									alignItems: 'stretch',
									gap: '2px',
									minHeight: '42px',
									overflowX: 'auto',
									overflowY: 'visible',
									paddingBottom: '2px',
								} }
								tabButtonStyle={ {
									minHeight: '40px',
									height: '40px',
									lineHeight: 1,
									padding: '0 14px',
									whiteSpace: 'nowrap',
									flex: '0 0 auto',
								} }
							>
								{ null }
							</PageDiagnosticsTabs>

							<div className="ASNERISSEO-react-detail-scroll-area">
{ detailTabContent }
</div>
			{/* <div className="ASNERISSEO-modal-footer">
							<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" onClick={ () => setIsClearConfirmOpen(false) } disabled={ isClearingRecords }>
								{ __('Cancel', 'asneris-seo-toolkit') }
							</button>
							<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary" onClick={ confirmClearPageRecords } disabled={ isClearingRecords }>
								{ isClearingRecords ? __('Cleaning...', 'asneris-seo-toolkit') : __('Yes, Page Non-Prioritize', 'asneris-seo-toolkit') }
							</button>
			</div> */}
		</div>
	</div>
	) : null }

			{ isHistoryPopupOpen && historyPopupPortalTarget ? createPortal(
				<div
					className={ `ASNERISSEO-modal-overlay ASNERISSEO-react-history-modal-overlay${ isHistoryPopupOpen ? ' active' : '' }` }
					onClick={ (event) => {
						if (event.target === event.currentTarget) {
							setIsHistoryPopupOpen(false);
						}
					} }
				>
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
												{ asArray(comparisonModel.grouped).map((group) => {
													const filteredRows = asArray(group.rows).filter((row) => comparisonFilter === COMPARISON_FILTER_ALL || row.changeType === comparisonFilter);
													if (filteredRows.length < 1) {
														return null;
													}

													return (
														<Fragment key={ group.key }>
															<tr key={ `${ group.key }-header` } className="ASNERISSEO-react-history-compare-category-row">
																<td colSpan="4">{ group.label }</td>
															</tr>
																	{ asArray(filteredRows).map((row, index) => {
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
				</div>
			, historyPopupPortalTarget) : null }
			</div>
			</div>
		</PanelScaffold>
	);
};

export default PageDiagnosticsPanel;

