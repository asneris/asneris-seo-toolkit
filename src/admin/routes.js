import { __ } from '@wordpress/i18n';

export const getAdminRoutes = (links = {}) => [
	{
		id: 'dashboard',
		label: __('Dashboard', 'asneris-seo-toolkit'),
		href: links.dashboard || links.settings || '#',
		page: 'asneris-seo',
	},
	{
		id: 'page-diagnostics',
		label: __('Page Diagnostics', 'asneris-seo-toolkit'),
		href: links.pageDiagnostics || '#',
		page: 'asneris-seo-diagnostics',
	},
	{
		id: 'site-diagnostics',
		label: __('Site Diagnostics', 'asneris-seo-toolkit'),
		href: links.siteDiagnostics || '#',
		page: 'asneris-seo-validation',
	},
	{
		id: 'redirects',
		label: __('Redirect Manager', 'asneris-seo-toolkit'),
		href: links.redirects || '#',
		page: 'asneris-seo-redirects',
	},
	{
		id: 'monitor-404',
		label: __('404 Monitor', 'asneris-seo-toolkit'),
		href: links.monitor404 || '#',
		page: 'asneris-seo-404-monitor',
	},
	{
		id: 'robots',
		label: __('Robots.txt', 'asneris-seo-toolkit'),
		href: links.robots || '#',
		page: 'asneris-seo-robots',
	},
	{
		id: 'bulk-edit',
		label: __('Bulk SEO Editor', 'asneris-seo-toolkit'),
		href: links.bulkEdit || '#',
		page: 'asneris-seo-bulk-edit',
	},
	{
		id: 'help',
		label: __('Help', 'asneris-seo-toolkit'),
		href: links.help || '#',
		page: 'asneris-seo-help',
	},
	{
		id: 'settings',
		label: __('General Setting', 'asneris-seo-toolkit'),
		href: links.settings || '#',
		page: 'asneris-seo-settings',
	},
	{
		id: 'verification',
		label: __('Verification', 'asneris-seo-toolkit'),
		href: links.settingsVerification || links.settings || '#',
		page: 'asneris-seo-settings',
		tab: 'verification',
	},
	{
		id: 'templates',
		label: __('Template', 'asneris-seo-toolkit'),
		href: links.settingsTemplates || links.settings || '#',
		page: 'asneris-seo-settings',
		tab: 'templates',
	},
	{
		id: 'social-defaults',
		label: __('Social Template', 'asneris-seo-toolkit'),
		href: links.settingsSocial || links.settings || '#',
		page: 'asneris-seo-settings',
		tab: 'social',
	},
	{
		id: 'schema',
		label: __('Schema', 'asneris-seo-toolkit'),
		href: links.settingsSchema || links.settings || '#',
		page: 'asneris-seo-settings',
		tab: 'schema',
	},
	{
		id: 'indexnow',
		label: __('IndexNow', 'asneris-seo-toolkit'),
		href: links.settingsIndexNow || links.settings || '#',
		page: 'asneris-seo-settings',
		tab: 'indexnow',
	},
	{
		id: 'ai-searchability',
		label: __('AI Searchability', 'asneris-seo-toolkit'),
		href: links.settingsAiSearchability || links.settings || '#',
		page: 'asneris-seo-settings',
		tab: 'aiSearchability',
	},
	{
		id: 'priority-pages',
		label: __('Priority Page', 'asneris-seo-toolkit'),
		href: links.settingsPriorityPages || links.settings || '#',
		page: 'asneris-seo-settings',
		tab: 'priorityPages',
	},
	{
		id: 'page-diagnostics-settings',
		label: __('Page Diagnostic', 'asneris-seo-toolkit'),
		href: links.settingsPageDiagnostics || links.settings || '#',
		page: 'asneris-seo-settings',
		tab: 'pageDiagnosticsSettings',
	},
	{
		id: 'monitor-404-settings',
		label: __('404 Controls', 'asneris-seo-toolkit'),
		href: links.settings404Controls || links.settings || '#',
		page: 'asneris-seo-settings',
		tab: 'monitor404',
	},
	{
		id: 'maintenance',
		label: __('Maintenance', 'asneris-seo-toolkit'),
		href: links.settingsMaintenance || links.settings || '#',
		page: 'asneris-seo-settings',
		tab: 'maintenance',
	},
];

export const getCurrentPageContext = () => {
	try {
		const params = new URLSearchParams(window.location.search);
		return {
			page: params.get('page') || '',
			tab: params.get('tab') || '',
		};
	} catch (error) {
		return { page: '', tab: '' };
	}
};

export const findActiveRoute = (routes, context) => {
	if (!context?.page) {
		return routes[0] || null;
	}

	const exactTabMatch = routes.find(
		(route) => route.page === context.page && route.tab && route.tab === context.tab
	);
	if (exactTabMatch) {
		return exactTabMatch;
	}

	return routes.find((route) => route.page === context.page && !route.tab) || routes[0] || null;
};
