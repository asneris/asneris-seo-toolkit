import { __ } from '@wordpress/i18n';
import { DISCOVERABILITY_EXPECTED_CHECKS } from '../../../app/discoverabilityDataModel';

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

export const buildSearchAppearanceVisualModel = (rows = [], item = null) => {
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
