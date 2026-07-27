import { __ } from '@wordpress/i18n';
import { categorizeDiscoverabilityCheck } from './discoverabilityDataModel';

export const mapDiscoverabilityStatus = (status, passed = null) => {
	const normalized = String(status || '').toLowerCase();
	if (normalized === 'pass') {
		return 'pass';
	}
	if (normalized === 'warning' || normalized === 'warn') {
		return 'warning';
	}
	if (normalized === 'fail' || normalized === 'error') {
		return 'fail';
	}
	if (passed === true) {
		return 'pass';
	}
	if (passed === false) {
		return 'warning';
	}
	return 'warning';
};

export const buildTopIssueCategories = (checks = []) => {
	const buckets = new Map([
		[ 'search', { label: __( 'Search Appearance', 'asneris-seo-toolkit' ), hasFail: false, hasWarning: false } ],
		[ 'advanced', { label: __( 'Indexability', 'asneris-seo-toolkit' ), hasFail: false, hasWarning: false } ],
		[ 'content', { label: __( 'Content Quality', 'asneris-seo-toolkit' ), hasFail: false, hasWarning: false } ],
		[ 'ai', { label: __( 'AI Discoverability', 'asneris-seo-toolkit' ), hasFail: false, hasWarning: false } ],
	]);

	(checks || []).forEach((check) => {
		const status = mapDiscoverabilityStatus(check?.status, check?.passed);
		if (status === 'pass') {
			return;
		}

		const category = categorizeDiscoverabilityCheck(check?.label);
		let bucketKey = null;
		if (category === 'search') {
			bucketKey = 'search';
		} else if (category === 'advanced') {
			bucketKey = 'advanced';
		} else if (category === 'quality' || category === 'links' || category === 'images') {
			bucketKey = 'content';
		} else if (category === 'ai' || category === 'schema') {
			bucketKey = 'ai';
		}

		if (!bucketKey || !buckets.has(bucketKey)) {
			return;
		}

		if (status === 'fail') {
			buckets.get(bucketKey).hasFail = true;
		} else {
			buckets.get(bucketKey).hasWarning = true;
		}
	});

	return [ 'search', 'advanced', 'content', 'ai' ]
		.map((key) => ({ key, ...buckets.get(key) }))
		.filter((item) => item.hasFail || item.hasWarning)
		.map((item) => ({
			label: item.label,
			severity: item.hasFail ? 'High' : 'Medium',
		}));
};

export const buildIssueBreakdownRows = (checks = [], options = {}) => {
	const allowedFields = Array.isArray(options?.allowedFields) ? options.allowedFields : [];
	const includePass = options?.includePass === true;
	const normalizeLabel = typeof options?.normalizeLabel === 'function'
		? options.normalizeLabel
		: (value) => String(value || '').trim();
	const dedupeByLabel = options?.dedupeByLabel !== false;
	const allowedLookup = new Set(
		allowedFields
			.map((field) => normalizeLabel(field))
			.filter(Boolean)
	);

	const candidates = (checks || [])
		.filter((check) => includePass || mapDiscoverabilityStatus(check?.status, check?.passed) !== 'pass')
		.map((check) => {
			const rawLabel = String(check?.label || '').trim();
			const normalizedLabel = normalizeLabel(rawLabel);
			if (!normalizedLabel) {
				return null;
			}

			if (allowedLookup.size > 0 && !allowedLookup.has(normalizedLabel)) {
				return null;
			}

			return {
				...check,
				label: normalizedLabel,
			};
		})
		.filter(Boolean);

	const dedupedChecks = dedupeByLabel
		? Array.from(candidates.reduce((acc, check) => {
			const key = String(check?.label || '').trim().toLowerCase();
			if (!key) {
				return acc;
			}

			const status = mapDiscoverabilityStatus(check?.status, check?.passed);
			const score = status === 'fail' ? 2 : status === 'warning' ? 1 : 0;
			const existing = acc.get(key);

			if (!existing) {
				acc.set(key, check);
				return acc;
			}

			const existingStatus = mapDiscoverabilityStatus(existing?.status, existing?.passed);
			const existingScore = existingStatus === 'fail' ? 2 : existingStatus === 'warning' ? 1 : 0;
			if (score > existingScore) {
				acc.set(key, check);
			}

			return acc;
		}, new Map()).values())
		: candidates;

	return dedupedChecks
		.slice(0, 8)
		.map((check, index) => {
		const label = check?.label || '-';
		const details = check?.details || check?.detail || '';
		const status = mapDiscoverabilityStatus(check?.status, check?.passed);
		const isFail = status === 'fail';
		const isPass = status === 'pass';
		let impact = __('This can reduce visibility and user trust.', 'asneris-seo-toolkit');
		let recommendation = __('Review this finding and fix it in the page SEO settings or content editor.', 'asneris-seo-toolkit');

		if (isPass) {
			impact = __('This field is currently healthy and aligned with SEO best practices.', 'asneris-seo-toolkit');
			recommendation = __('No action required. Recheck after major content or settings changes.', 'asneris-seo-toolkit');
		}

		if (/meta description/i.test(label)) {
			impact = __('Search engines may generate weaker snippets, reducing click-through rate.', 'asneris-seo-toolkit');
			recommendation = __('Write a unique 120-160 character meta description focused on search intent.', 'asneris-seo-toolkit');
		} else if (/title/i.test(label)) {
			impact = __('Unclear titles can lower relevance signals and reduce clicks from search.', 'asneris-seo-toolkit');
			recommendation = __('Use one clear title with primary topic terms and keep length around 30-60 characters.', 'asneris-seo-toolkit');
		} else if (/index|noindex|robots|canonical/i.test(label)) {
			impact = __('Indexing issues can prevent ranking or send traffic to the wrong URL.', 'asneris-seo-toolkit');
			recommendation = __('Set index/follow correctly and ensure canonical points to the preferred live URL.', 'asneris-seo-toolkit');
		} else if (/http status|redirect|final destination/i.test(label)) {
			impact = __('Crawl inefficiency and redirect chains can waste authority and delay indexing.', 'asneris-seo-toolkit');
			recommendation = __('Resolve non-200 responses and shorten redirect chains to a single hop where possible.', 'asneris-seo-toolkit');
		} else if (/word count|h1|h2|heading|readability|content/i.test(label)) {
			impact = __('Thin or unclear content can reduce topical relevance and conversion confidence.', 'asneris-seo-toolkit');
			recommendation = __('Expand content depth, keep heading hierarchy clean, and align copy with user intent.', 'asneris-seo-toolkit');
		} else if (/image|alt/i.test(label)) {
			impact = __('Missing image context reduces accessibility and image search discoverability.', 'asneris-seo-toolkit');
			recommendation = __('Add descriptive ALT text and optimize image format/size for faster load performance.', 'asneris-seo-toolkit');
		} else if (/link|orphan/i.test(label)) {
			impact = __('Weak internal linking can reduce crawl depth and page authority flow.', 'asneris-seo-toolkit');
			recommendation = __('Add contextual internal links from related pages and fix broken or redirected targets.', 'asneris-seo-toolkit');
		} else if (/schema|faq|entity|ai|geo/i.test(label)) {
			impact = __('Weak structured context can reduce eligibility for rich and AI-assisted results.', 'asneris-seo-toolkit');
			recommendation = __('Add relevant schema, reinforce entities, and include concise Q&A where useful.', 'asneris-seo-toolkit');
		}

		if (details) {
			recommendation = `${ recommendation } ${ __('Context:', 'asneris-seo-toolkit') } ${ details }`;
		}

		return {
			key: `issue-${ index }-${ label }`,
			cells: [
				label,
				impact,
				recommendation,
				isPass
					? __('Low', 'asneris-seo-toolkit')
					: (isFail ? __('High', 'asneris-seo-toolkit') : __('Medium', 'asneris-seo-toolkit')),
			],
		};
		});
};
