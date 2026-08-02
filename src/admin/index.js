import { Component, createRoot, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { findActiveRoute, getAdminRoutes, getCurrentPageContext } from './routes';
import MetricCard from './components/common/MetricCard';
import QuickLink from './components/common/QuickLink';
import GlobalStatusNotice from './components/common/GlobalStatusNotice';
import AdminHero from './components/common/AdminHero';
import PanelScaffold from './components/common/PanelScaffold';
import fetchJson from './components/common/fetchJson';
import GeneralSettingsPanel from './components/panels/GeneralSettingsPanel';
import PriorityPagesSettingsPanel from './components/panels/PriorityPagesSettingsPanel';
import PageDiagnosticsSettingsPanel from './components/panels/PageDiagnosticsSettingsPanel';
import HelpPanel from './components/panels/HelpPanel';
import VerificationSettingsPanel from './components/panels/VerificationSettingsPanel';
import SocialDefaultsPanel from './components/panels/SocialDefaultsPanel';
import SchemaPanel from './components/panels/SchemaPanel';
import IndexNowPanel from './components/panels/IndexNowPanel';
import TemplatesSettingsPanel from './components/panels/TemplatesSettingsPanel';
import MaintenanceSettingsPanel from './components/panels/MaintenanceSettingsPanel';
import PageDiagnosticsPanel from './components/panels/PageDiagnosticsPanel';
import RedirectsPanel from './components/panels/RedirectsPanel';
import Monitor404Panel from './components/panels/Monitor404Panel';
import SiteDiagnosticsPanel from './components/panels/SiteDiagnosticsPanel';
import RobotsPanel from './components/panels/RobotsPanel';
import AiSearchabilityPanel from './components/panels/AiSearchabilityPanel';
import BulkEditPanel from './components/panels/BulkEditPanel';
import Monitor404SettingsPanel from './components/panels/Monitor404SettingsPanel';

const dashboardData = window.asnerisseoAdminDashboardData || {};
const fallbackSummary = dashboardData.summary || {};

const sectionStyle = {
	background: '#fff',
	border: '1px solid #d1d9e0',
	borderRadius: '12px',
	padding: '18px',
	boxShadow: '0 8px 20px rgba(7, 28, 52, 0.05)',
};

const SCHEMA_BUSINESS_TYPE_GROUPS = [
	{
		label: __('Food & Dining', 'asneris-seo-toolkit'),
		options: [
			{ value: 'Restaurant', label: __('Restaurant', 'asneris-seo-toolkit') },
			{ value: 'FastFoodRestaurant', label: __('Fast Food Restaurant', 'asneris-seo-toolkit') },
			{ value: 'Cafe', label: __('Cafe / Coffee Shop', 'asneris-seo-toolkit') },
			{ value: 'Bakery', label: __('Bakery', 'asneris-seo-toolkit') },
			{ value: 'BarOrPub', label: __('Bar / Pub', 'asneris-seo-toolkit') },
		],
	},
	{
		label: __('Retail', 'asneris-seo-toolkit'),
		options: [
			{ value: 'Store', label: __('Store (General)', 'asneris-seo-toolkit') },
			{ value: 'ClothingStore', label: __('Clothing Store', 'asneris-seo-toolkit') },
			{ value: 'FurnitureStore', label: __('Furniture Store', 'asneris-seo-toolkit') },
			{ value: 'HardwareStore', label: __('Hardware Store', 'asneris-seo-toolkit') },
			{ value: 'JewelryStore', label: __('Jewelry Store', 'asneris-seo-toolkit') },
			{ value: 'SportsStore', label: __('Sports Store', 'asneris-seo-toolkit') },
		],
	},
	{
		label: __('Health & Beauty', 'asneris-seo-toolkit'),
		options: [
			{ value: 'HealthAndBeautyBusiness', label: __('Health & Beauty (General)', 'asneris-seo-toolkit') },
			{ value: 'HairSalon', label: __('Hair Salon', 'asneris-seo-toolkit') },
			{ value: 'BeautySalon', label: __('Beauty Salon', 'asneris-seo-toolkit') },
			{ value: 'DaySpa', label: __('Day Spa', 'asneris-seo-toolkit') },
		],
	},
	{
		label: __('Medical', 'asneris-seo-toolkit'),
		options: [
			{ value: 'Dentist', label: __('Dentist', 'asneris-seo-toolkit') },
			{ value: 'Physician', label: __('Physician / Doctor', 'asneris-seo-toolkit') },
			{ value: 'MedicalClinic', label: __('Medical Clinic', 'asneris-seo-toolkit') },
			{ value: 'Pharmacy', label: __('Pharmacy', 'asneris-seo-toolkit') },
		],
	},
	{
		label: __('Professional Services', 'asneris-seo-toolkit'),
		options: [
			{ value: 'ProfessionalService', label: __('Professional Service (General)', 'asneris-seo-toolkit') },
			{ value: 'Attorney', label: __('Attorney / Lawyer', 'asneris-seo-toolkit') },
			{ value: 'Accountant', label: __('Accountant', 'asneris-seo-toolkit') },
			{ value: 'RealEstateAgent', label: __('Real Estate Agent', 'asneris-seo-toolkit') },
		],
	},
	{
		label: __('Home Services', 'asneris-seo-toolkit'),
		options: [
			{ value: 'HomeAndConstructionBusiness', label: __('Home Services (General)', 'asneris-seo-toolkit') },
			{ value: 'Electrician', label: __('Electrician', 'asneris-seo-toolkit') },
			{ value: 'Plumber', label: __('Plumber', 'asneris-seo-toolkit') },
			{ value: 'Locksmith', label: __('Locksmith', 'asneris-seo-toolkit') },
		],
	},
];

const TEMPLATE_VARIABLES = ['{title}', '{site}', '{separator}', '{excerpt}', '{date}', '{author}', '{term}'];

const ROBOTS_SAFE_DEFAULTS = [
	__('Block /wp-admin/ except admin-ajax.php', 'asneris-seo-toolkit'),
	__('Block /wp-includes/ system paths', 'asneris-seo-toolkit'),
	__('Do not use Disallow: / on production', 'asneris-seo-toolkit'),
	__('Include an explicit Sitemap line', 'asneris-seo-toolkit'),
];

const formatStatusText = (status) => {
	if (status === 'success') {
		return __('Pass', 'asneris-seo-toolkit');
	}
	if (status === 'warning') {
		return __('Warning', 'asneris-seo-toolkit');
	}
	if (status === 'error') {
		return __('Conflict', 'asneris-seo-toolkit');
	}
	return status || '-';
};

const formatCronStatusLabel = (status) => {
	const normalized = String(status || '').toLowerCase();
	if (normalized === 'disabled') {
		return __('Disabled', 'asneris-seo-toolkit');
	}
	if (normalized === 'scheduled') {
		return __('Scheduled', 'asneris-seo-toolkit');
	}
	if (normalized === 'schedule_mismatch') {
		return __('Schedule mismatch', 'asneris-seo-toolkit');
	}
	return __('Not scheduled', 'asneris-seo-toolkit');
};

const formatDateTimeLabel = (value) => {
	if (!value) {
		return '-';
	}

	const date = new Date(String(value).replace(' ', 'T'));
	if (Number.isNaN(date.getTime())) {
		return String(value);
	}

	return `${ date.toLocaleDateString() } ${ date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }`;
};

const CONFIG_SECTION_LABELS = {
	general: __('General Settings', 'asneris-seo-toolkit'),
	verification: __('Verification', 'asneris-seo-toolkit'),
	social: __('Social', 'asneris-seo-toolkit'),
	schema: __('Schema', 'asneris-seo-toolkit'),
	indexnow: __('IndexNow', 'asneris-seo-toolkit'),
	templates: __('Templates', 'asneris-seo-toolkit'),
	aiSearchability: __('AI Searchability', 'asneris-seo-toolkit'),
	pageDiagnosticsSettings: __('Page Diagnostics Settings', 'asneris-seo-toolkit'),
};

const CHECKLIST_SECTION_META = [
	{
		keys: ['general', 'settings'],
		icon: 'dashicons-admin-generic',
		actionLabel: __('Continue Setup', 'asneris-seo-toolkit'),
		getHref: (links) => links.settings,
	},
	{
		keys: ['verification', 'searchengineverification'],
		icon: 'dashicons-search',
		actionLabel: __('Connect Now', 'asneris-seo-toolkit'),
		getHref: (links) => links.settingsVerification,
	},
	{
		keys: ['social', 'socialdefaults', 'socialmedia'],
		icon: 'dashicons-share',
		actionLabel: __('Configure', 'asneris-seo-toolkit'),
		getHref: (links) => links.settingsSocial,
	},
	{
		keys: ['schema', 'schemamarkup'],
		icon: 'dashicons-editor-code',
		actionLabel: __('Setup Schema', 'asneris-seo-toolkit'),
		getHref: (links) => links.settingsSchema,
	},
	{
		keys: ['indexnow'],
		icon: 'dashicons-update',
		actionLabel: __('Enable Now', 'asneris-seo-toolkit'),
		getHref: (links) => links.settingsIndexNow,
	},
	{
		keys: ['templates', 'seotemplates'],
		icon: 'dashicons-media-text',
		actionLabel: __('Manage Templates', 'asneris-seo-toolkit'),
		getHref: (links) => links.settingsTemplates,
	},
	{
		keys: ['prioritypages', 'prioritypagesettings'],
		icon: 'dashicons-star-filled',
		actionLabel: __('View Pages', 'asneris-seo-toolkit'),
		getHref: (links) => links.settingsPriorityPages,
	},
	{
		keys: ['aisearchability', 'llms', 'llmstxt'],
		icon: 'dashicons-search',
		actionLabel: __('Configure', 'asneris-seo-toolkit'),
		getHref: (links) => links.settingsAiSearchability,
	},
	{
		keys: ['pagediagnosticssettings', 'pagediagnosticsconfig', 'pagediagnosticsscan'],
		icon: 'dashicons-media-document',
		actionLabel: __('Configure', 'asneris-seo-toolkit'),
		getHref: (links) => links.settingsPageDiagnostics,
	},
	{
		keys: ['monitor404', '404monitor', 'monitor404settings'],
		icon: 'dashicons-shield',
		actionLabel: __('View Report', 'asneris-seo-toolkit'),
		getHref: (links) => links.monitor404,
	},
];

const SECTION_FALLBACK_ICON = 'dashicons-admin-generic';
const SETTINGS_ROUTE_IDS = new Set([
	'settings',
	'verification',
	'social-defaults',
	'schema',
	'indexnow',
	'templates',
	'maintenance',
	'ai-searchability',
	'monitor-404-settings',
	'priority-pages',
	'page-diagnostics-settings',
]);
const SETTINGS_TAB_TO_ROUTE_ID = {
	priorityPages: 'priority-pages',
	pageDiagnosticsSettings: 'page-diagnostics-settings',
	verification: 'verification',
	social: 'social-defaults',
	schema: 'schema',
	indexnow: 'indexnow',
	templates: 'templates',
	maintenance: 'maintenance',
	aiSearchability: 'ai-searchability',
	monitor404: 'monitor-404-settings',
};
const SETTINGS_TAB_ICON = {
	settings: 'settings',
	'priority-pages': 'star',
	'page-diagnostics-settings': 'document',
	verification: 'shield',
	schema: 'code',
	'social-defaults': 'share',
	indexnow: 'flash',
	templates: 'document',
	maintenance: 'wrench',
	'ai-searchability': 'search',
	'monitor-404-settings': 'alert-circle',
};

const SettingsTabIcon = ({ name }) => {
	const common = {
		fill: 'none',
		stroke: 'currentColor',
		strokeWidth: 1.6,
		strokeLinecap: 'round',
		strokeLinejoin: 'round',
	};

	switch (name) {
		case 'settings':
			return <path d="M6.5 1.5h3l.5 1.8a3.9 3.9 0 0 1 1 .6l1.8-.6 1.5 2.6-1.4 1.2c.1.4.1.8 0 1.2l1.4 1.2-1.5 2.6-1.8-.6c-.3.2-.7.4-1 .6l-.5 1.8h-3L6 12.9c-.4-.2-.7-.4-1-.6l-1.8.6-1.5-2.6L3.1 9c-.1-.4-.1-.8 0-1.2L1.7 6.6l1.5-2.6 1.8.6c.3-.2.6-.4 1-.6l.5-1.9zM8 10.4a2.4 2.4 0 1 0 0-4.8 2.4 2.4 0 0 0 0 4.8z" {...common} />;
		case 'star':
			return <path d="m8 1.7 1.9 3.8 4.2.6-3 2.9.7 4.1L8 11.1l-3.8 2 .7-4.1-3-2.9 4.2-.6z" {...common} />;
		case 'shield':
			return <path d="M8 1.5c1.5 1.2 3.3 1.8 5.2 2v4.1c0 2.8-2 5-5.2 6.9-3.2-1.9-5.2-4.1-5.2-6.9V3.5c1.9-.2 3.7-.8 5.2-2z" {...common} />;
		case 'code':
			return <path d="M5.2 4 2.4 8l2.8 4M10.8 4l2.8 4-2.8 4M9.2 2.8 6.8 13.2" {...common} />;
		case 'share':
			return <path d="M11.9 5a2 2 0 1 0-1.6-3.2L5.7 4.3a2 2 0 0 0 0 3.4l4.6 2.5a2 2 0 1 0 .7-1.3L6.4 6.4a2.2 2.2 0 0 0 0-.8L11 3.1c.3.3.6.5.9.7z" {...common} />;
		case 'flash':
			return <path d="M9.2 1.8 4.5 8h3l-1 6.2L11.5 8h-3z" {...common} />;
		case 'document':
			return <path d="M4 1.8h5l3 3v8.9a1.3 1.3 0 0 1-1.3 1.3H4a1.3 1.3 0 0 1-1.3-1.3V3.1A1.3 1.3 0 0 1 4 1.8zm5 .1v3h3M5.1 8h5.8M5.1 10.4h5.8M5.1 12.8h3.8" {...common} />;
		case 'wrench':
			return <path d="M9.9 2.2a3 3 0 0 0 2.8 4L8.2 10.7a1.7 1.7 0 1 0 2.4 2.4l4.5-4.5a3 3 0 0 0-4.2-4.2z" {...common} />;
		case 'alert-circle':
			return <path d="M8 14.2A6.2 6.2 0 1 0 8 1.8a6.2 6.2 0 0 0 0 12.4zm0-8v3.3m0 2.2h.01" {...common} />;
		default:
			return <circle cx="8" cy="8" r="5.5" {...common} />;
	}
};
const PAGE_TO_ROUTE_ID = {
	'asneris-seo': 'dashboard',
	'asneris-seo-settings': 'settings',
	'asneris-seo-diagnostics': 'page-diagnostics',
	'asneris-seo-validation': 'site-diagnostics',
	'asneris-seo-redirects': 'redirects',
	'asneris-seo-404-monitor': 'monitor-404',
	'asneris-seo-robots': 'robots',
	'asneris-seo-bulk-edit': 'bulk-edit',
	'asneris-seo-help': 'help',
};
const MOUNT_SELECTOR_TO_ROUTE_ID = {
	'.asnerisseo-fallback-dashboard': 'dashboard',
	'.asnerisseo-fallback-settings': 'settings',
	'.asnerisseo-fallback-page-diagnostics': 'page-diagnostics',
	'.asnerisseo-fallback-validation': 'site-diagnostics',
	'.asnerisseo-fallback-redirects': 'redirects',
	'.asnerisseo-fallback-404-monitor': 'monitor-404',
	'.asnerisseo-fallback-robots': 'robots',
	'.asnerisseo-fallback-bulk-edit': 'bulk-edit',
	'.asnerisseo-fallback-help': 'help',
};
const DEFAULT_ADMIN_LINKS = {
	dashboard: 'admin.php?page=asneris-seo',
	settings: 'admin.php?page=asneris-seo-settings',
	settingsVerification: 'admin.php?page=asneris-seo-settings&tab=verification',
	settingsSocial: 'admin.php?page=asneris-seo-settings&tab=social',
	settingsSchema: 'admin.php?page=asneris-seo-settings&tab=schema',
	settingsIndexNow: 'admin.php?page=asneris-seo-settings&tab=indexnow',
	settingsTemplates: 'admin.php?page=asneris-seo-settings&tab=templates',
	settingsMaintenance: 'admin.php?page=asneris-seo-settings&tab=maintenance',
	settingsAiSearchability: 'admin.php?page=asneris-seo-settings&tab=aiSearchability',
	settings404Controls: 'admin.php?page=asneris-seo-settings&tab=monitor404',
	settingsPriorityPages: 'admin.php?page=asneris-seo-settings&tab=priorityPages',
	settingsPageDiagnostics: 'admin.php?page=asneris-seo-settings&tab=pageDiagnosticsSettings',
	pageDiagnostics: 'admin.php?page=asneris-seo-diagnostics',
	siteDiagnostics: 'admin.php?page=asneris-seo-validation',
	bulkEdit: 'admin.php?page=asneris-seo-bulk-edit',
	redirects: 'admin.php?page=asneris-seo-redirects',
	monitor404: 'admin.php?page=asneris-seo-404-monitor',
	robots: 'admin.php?page=asneris-seo-robots',
	help: 'admin.php?page=asneris-seo-help',
};

const HERO_SUBTITLE_BY_ROUTE_ID = {
	'dashboard': __('Great content alone doesn\'t guarantee discoverability. Search engines and AI systems also rely on technical structure, semantic markup, internal linking, metadata, and other discoverability signals to crawl, interpret, and reference your website effectively. Asneris helps identify and strengthen these signals to improve search readiness.', 'asneris-seo-toolkit'),
	'settings': __('Clear and simple SEO configuration for your WordPress site.', 'asneris-seo-toolkit'),
	'page-diagnostics': __('Inspect your page\'s search footprint. We show you the simple facts: Is it live? (Connectivity), Is it the master version? (Canonical), and Is it ready to rank? (Indexing & Tags).', 'asneris-seo-toolkit'),
	'site-diagnostics': __('Scan your site\'s master settings. We check the global rules that tell search engines how to find, map, and show your entire website to the world.', 'asneris-seo-toolkit'),
	'redirects': __('Send visitors and search engines to the right page when a URL changes.', 'asneris-seo-toolkit'),
	'monitor-404': __('Track real missing URLs, prioritize fixes, and convert issues into redirects.', 'asneris-seo-toolkit'),
	'robots': __('Control which parts of your site search engines are allowed to visit.', 'asneris-seo-toolkit'),
	'bulk-edit': __('Update SEO titles, descriptions, and indexing settings for multiple pages/posts at once.', 'asneris-seo-toolkit'),
	'verification': __('Add verification codes for major webmaster tools to confirm site ownership.', 'asneris-seo-toolkit'),
	'social-defaults': __('Set default social metadata so shared links look consistent across platforms.', 'asneris-seo-toolkit'),
	'schema': __('Configure structured data defaults so search engines better understand your content.', 'asneris-seo-toolkit'),
	'indexnow': __('Notify supported search engines quickly when content is published or updated.', 'asneris-seo-toolkit'),
	'templates': __('Create reusable SEO title and description templates for posts and pages.', 'asneris-seo-toolkit'),
	'maintenance': __('Run maintenance tools to keep SEO settings and generated data healthy.', 'asneris-seo-toolkit'),
	'monitor-404-settings': __('Configure 404 monitoring state and throttling safeguards.', 'asneris-seo-toolkit'),
	'priority-pages': __('Manage the pages/posts included in the Priority Pages recommendation list.', 'asneris-seo-toolkit'),
	'page-diagnostics-settings': __('Configure Priority tab visibility and Page Diagnostics scan scheduling.', 'asneris-seo-toolkit'),
	'help': __('Find guidance, troubleshooting steps, and support resources for this plugin.', 'asneris-seo-toolkit'),
};

const normalizeChecks = (checks) => {
	if (Array.isArray(checks)) {
		return checks;
	}
	if (checks && typeof checks === 'object') {
		return Object.values(checks);
	}
	return [];
};

const resolveRouteId = (context, mountSelector) => {
	if (!context?.page) {
		return MOUNT_SELECTOR_TO_ROUTE_ID[mountSelector] || null;
	}

	if (context.page === 'asneris-seo-settings') {
		return SETTINGS_TAB_TO_ROUTE_ID[context.tab] || 'settings';
	}

	return PAGE_TO_ROUTE_ID[context.page] || MOUNT_SELECTOR_TO_ROUTE_ID[mountSelector] || null;
};

class AdminErrorBoundary extends Component {
	constructor(props) {
		super(props);
		this.state = { hasError: false };
	}

	static getDerivedStateFromError() {
		return { hasError: true };
	}

	componentDidCatch(error) {
		this.props.onError?.(error);
	}

	render() {
		if (this.state.hasError) {
			return (
				<div className="notice notice-error" style={ { padding: '12px 14px', margin: '0 0 12px 0' } }>
					<p>
						<strong>{ __('React admin failed to render.', 'asneris-seo-toolkit') }</strong>{ ' ' }
						{ __('Please refresh the page and check browser console for details.', 'asneris-seo-toolkit') }
					</p>
				</div>
			);
		}

		return this.props.children;
	}
}

class AdminPanelErrorBoundary extends Component {
	constructor(props) {
		super(props);
		this.state = { hasError: false };
	}

	static getDerivedStateFromError() {
		return { hasError: true };
	}

	componentDidCatch(error) {
		// eslint-disable-next-line no-console
		console.error('ASNERISSEO panel render error:', error);
	}

	render() {
		if (this.state.hasError) {
			return (
				<div className="notice notice-error" style={ { padding: '12px 14px', margin: '0 0 12px 0' } }>
					<p>
						<strong>{ __('A module failed to render.', 'asneris-seo-toolkit') }</strong>{ ' ' }
						{ __('Please refresh the page and check browser console for details.', 'asneris-seo-toolkit') }
					</p>
				</div>
			);
		}

		return this.props.children;
	}
}

const ReactDashboard = () => {
	const [summary, setSummary] = useState(fallbackSummary);
	const [isLoading, setIsLoading] = useState(false);
	const [adminStatus, setAdminStatus] = useState(null);

	useEffect(() => {
		document.body.classList.remove('ASNERISSEO-react-admin-booting');
		document.body.classList.add('ASNERISSEO-react-admin-mounted');
		return () => {
			document.body.classList.remove('ASNERISSEO-react-admin-mounted');
			document.body.classList.remove('ASNERISSEO-react-admin-booting');
		};
	}, []);

	const progress = summary.progress || {};
	const configStatus = summary.configStatus || {};
	const validation = summary.validation || {};
	const diagnostics = summary.diagnostics || {};
	const cron = summary.cron || {};
	const systemCron = cron.system || {};
	const cron404 = cron.monitor404 || {};
	const priorityScanCron = cron.priorityScan || {};
	const links = { ...DEFAULT_ADMIN_LINKS, ...(summary.links || {}) };
	const configSectionOrder = Object.keys(CONFIG_SECTION_LABELS);
	const orderedSections = configSectionOrder
		.map((key) => ({ key, data: configStatus[key] }))
		.filter((section) => section.data && typeof section.data === 'object');
	const additionalSections = Object.entries(configStatus)
		.filter(([key, value]) => !configSectionOrder.includes(key) && value && typeof value === 'object')
		.map(([key, value]) => ({ key, data: value }));
	const configSections = [...orderedSections, ...additionalSections];

	const routes = getAdminRoutes(links);
	const settingsRoutes = routes.filter((route) => SETTINGS_ROUTE_IDS.has(route.id));
	const settingsRouteIds = settingsRoutes.map((route) => route.id);
	const settingsTabRefs = useRef({});
	const currentPageContext = getCurrentPageContext();
	const currentRouteId = resolveRouteId(currentPageContext, dashboardData.mountSelector);
	const resolvedActiveRoute = routes.find((route) => route.id === currentRouteId) || findActiveRoute(routes, currentPageContext);
	const [activeRouteId, setActiveRouteId] = useState(resolvedActiveRoute?.id || routes[0]?.id || 'dashboard');
	const activeRoute = routes.find((route) => route.id === activeRouteId) || resolvedActiveRoute || routes[0] || null;

	const isDashboardModule = activeRoute?.id === 'dashboard';
	const isGeneralSettingsModule = activeRoute?.id === 'settings';
	const isVerificationModule = activeRoute?.id === 'verification';
	const isPriorityPagesModule = activeRoute?.id === 'priority-pages';
	const isPageDiagnosticsModule = activeRoute?.id === 'page-diagnostics';
	const isPageDiagnosticsSettingsModule = activeRoute?.id === 'page-diagnostics-settings';
	const isSiteDiagnosticsModule = activeRoute?.id === 'site-diagnostics';
	const isSocialDefaultsModule = activeRoute?.id === 'social-defaults';
	const isSchemaModule = activeRoute?.id === 'schema';
	const isIndexNowModule = activeRoute?.id === 'indexnow';
	const isTemplatesModule = activeRoute?.id === 'templates';
	const isMaintenanceModule = activeRoute?.id === 'maintenance';
	const isAiSearchabilityModule = activeRoute?.id === 'ai-searchability';
	const isMonitor404SettingsModule = activeRoute?.id === 'monitor-404-settings';
	const isRedirectsModule = activeRoute?.id === 'redirects';
	const isMonitor404Module = activeRoute?.id === 'monitor-404';
	const isRobotsModule = activeRoute?.id === 'robots';
	const isBulkEditModule = activeRoute?.id === 'bulk-edit';
	const isHelpModule = activeRoute?.id === 'help';
	const isSettingsModule = SETTINGS_ROUTE_IDS.has(activeRoute?.id || '');

	const focusSettingsTab = (routeId) => {
		const target = settingsTabRefs.current?.[routeId];
		if (target && typeof target.focus === 'function') {
			target.focus();
		}
	};

	const handleSettingsTabChange = (routeId) => {
		if (!routeId || routeId === activeRouteId) {
			return;
		}

		const nextRoute = routes.find((route) => route.id === routeId);
		if (!nextRoute) {
			return;
		}

		setActiveRouteId(routeId);
	};

	const handleSettingsTabKeyDown = (event, currentRouteIdValue) => {
		if (!settingsRouteIds.length) {
			return;
		}

		const currentIndex = settingsRouteIds.indexOf(currentRouteIdValue);
		if (currentIndex < 0) {
			return;
		}

		if (event.key === 'ArrowRight') {
			event.preventDefault();
			const nextIndex = (currentIndex + 1) % settingsRouteIds.length;
			const nextRouteId = settingsRouteIds[nextIndex];
			handleSettingsTabChange(nextRouteId);
			focusSettingsTab(nextRouteId);
			return;
		}

		if (event.key === 'ArrowLeft') {
			event.preventDefault();
			const previousIndex = (currentIndex - 1 + settingsRouteIds.length) % settingsRouteIds.length;
			const previousRouteId = settingsRouteIds[previousIndex];
			handleSettingsTabChange(previousRouteId);
			focusSettingsTab(previousRouteId);
			return;
		}

		if (event.key === 'Home') {
			event.preventDefault();
			const firstRouteId = settingsRouteIds[0];
			handleSettingsTabChange(firstRouteId);
			focusSettingsTab(firstRouteId);
			return;
		}

		if (event.key === 'End') {
			event.preventDefault();
			const lastRouteId = settingsRouteIds[settingsRouteIds.length - 1];
			handleSettingsTabChange(lastRouteId);
			focusSettingsTab(lastRouteId);
		}
	};

	useEffect(() => {
		if (routes.some((route) => route.id === activeRouteId)) {
			return;
		}

		setActiveRouteId(resolvedActiveRoute?.id || routes[0]?.id || 'dashboard');
	}, [activeRouteId, routes, resolvedActiveRoute?.id]);


	useEffect(() => {
		if (!dashboardData.dashboardSummaryRestUrl) {
			return;
		}

		setIsLoading(true);
		fetchJson(dashboardData.dashboardSummaryRestUrl, {
			method: 'GET',
			headers: {
				'X-WP-Nonce': dashboardData.restNonce || '',
			},
		})
			.then((payload) => {
				if (payload) {
					setSummary(payload);
				}
			})
			.catch(() => {
				// Keep fallback summary.
			})
			.finally(() => setIsLoading(false));
	}, []);

	const siteReadiness = `${ progress.percent || 0 }%`;
	const siteReadinessPercent = Number(progress.percent || 0);
	const aiStatus = diagnostics.seo_plugin_conflicts > 0 ? __('Needs Attention', 'asneris-seo-toolkit') : __('Good', 'asneris-seo-toolkit');
	const crawlAccessStatus = diagnostics.sitemap_exists && diagnostics.robots_txt_exists ? __('Healthy', 'asneris-seo-toolkit') : __('Needs Attention', 'asneris-seo-toolkit');
	const pluginCompatibilityHint = diagnostics.seo_plugin_conflicts > 0 ? __('Potential conflict detected. Review active SEO plugins.', 'asneris-seo-toolkit') : __('No plugin conflicts detected.', 'asneris-seo-toolkit');
	const crawlAccessHint = diagnostics.sitemap_exists && diagnostics.robots_txt_exists ? __('Search engines can access your site basics.', 'asneris-seo-toolkit') : __('Review sitemap or robots settings.', 'asneris-seo-toolkit');
	const criticalIssuesHint = validation.conflicts > 0 ? __('Review Site Diagnostics and fix these issues first to improve visibility.', 'asneris-seo-toolkit') : __('No blocking issues detected.', 'asneris-seo-toolkit');
	const systemCronLabel = systemCron.enabled === false ? __('Disabled', 'asneris-seo-toolkit') : __('Enabled', 'asneris-seo-toolkit');
	const cron404Hint = `${ __('System Cron:', 'asneris-seo-toolkit') } ${ systemCronLabel } · ${ __('Next:', 'asneris-seo-toolkit') } ${ formatDateTimeLabel(cron404.next_run_gmt) }`;
	const priorityScanCronHint = `${ __('System Cron:', 'asneris-seo-toolkit') } ${ systemCronLabel } · ${ __('Next:', 'asneris-seo-toolkit') } ${ formatDateTimeLabel(priorityScanCron.next_run_gmt) }`;
	const handlePanelStatus = (status) => setAdminStatus(status || null);
	const heroTitle = activeRoute?.label || __('Asneris Admin', 'asneris-seo-toolkit');
	const heroSubtitle = HERO_SUBTITLE_BY_ROUTE_ID[activeRoute?.id] || HERO_SUBTITLE_BY_ROUTE_ID.dashboard;
	const activeSettingsTabId = isSettingsModule ? (activeRoute?.id || settingsRouteIds[0] || 'settings') : '';

	return (
		<div className="ASNERISSEO-react-admin-shell">
			<AdminHero title={ heroTitle } subtitle={ heroSubtitle } />

			<GlobalStatusNotice status={ adminStatus } onDismiss={ () => setAdminStatus(null) } />

			{ isSettingsModule ? (
				<div className="ASNERISSEO-react-module-nav-wrap">
					<div className="ASNERISSEO-react-module-nav-header">
						<h2 className="ASNERISSEO-heading-h2 ASNERISSEO-react-module-nav-title">{ __('Admin Functions', 'asneris-seo-toolkit') }</h2>
					</div>
					<div className="ASNERISSEO-react-settings-tabs" role="tablist" aria-label={ __('Settings Tabs', 'asneris-seo-toolkit') }>
						{ settingsRoutes.map((route) => {
							const iconName = SETTINGS_TAB_ICON[route.id] || 'settings';
							return (
							<button
								key={ route.id }
								ref={ (element) => {
									settingsTabRefs.current[route.id] = element;
								} }
								type="button"
								role="tab"
								id={ `asneris-settings-tab-${ route.id }` }
								aria-controls={ `asneris-settings-panel-${ route.id }` }
								aria-selected={ route.id === activeRoute?.id }
								tabIndex={ route.id === activeRoute?.id ? 0 : -1 }
								onKeyDown={ (event) => handleSettingsTabKeyDown(event, route.id) }
								onClick={ () => handleSettingsTabChange(route.id) }
								className={ route.id === activeRoute?.id ? 'ASNERISSEO-react-settings-tab is-active' : 'ASNERISSEO-react-settings-tab' }
							>
								<span className="ASNERISSEO-react-tab-icon" aria-hidden="true">
									<svg viewBox="0 0 16 16" width="14" height="14" focusable="false" aria-hidden="true">
										<SettingsTabIcon name={ iconName } />
									</svg>
								</span>
								{ route.label }
							</button>
							);
						}) }
					</div>
				</div>
			) : null}

			{ isLoading ? <p style={ { color: 'var(--asneris-muted, #5f718a)', fontSize: '13px', marginBottom: '12px' } }>{ __('Refreshing dashboard data...', 'asneris-seo-toolkit') }</p> : null }

			<AdminPanelErrorBoundary>
				<div
					role={ isSettingsModule ? 'tabpanel' : undefined }
					id={ isSettingsModule ? `asneris-settings-panel-${ activeSettingsTabId }` : undefined }
					aria-labelledby={ isSettingsModule ? `asneris-settings-tab-${ activeSettingsTabId }` : undefined }
				>
				{ isDashboardModule ? (
					<>
					<PanelScaffold
						title={ __('Configuration Checklist', 'asneris-seo-toolkit') }
						description={ __('Review each section and complete missing setup items.', 'asneris-seo-toolkit') }
						panelClass="ASNERISSEO-react-data-panel"
					>

						{ configSections.length > 0 ? (
							<div className="ASNERISSEO-react-grid-checklist">
								{ configSections.map(({ key, data }) => {
									const normalizedKey = String(key || '').toLowerCase().replace(/[^a-z0-9]/g, '');
									const sectionMeta = CHECKLIST_SECTION_META.find((meta) => meta.keys.includes(normalizedKey));
									const sectionLabel = data?.label || CONFIG_SECTION_LABELS[key] || key;
									const sectionIcon = data?.icon || sectionMeta?.icon || SECTION_FALLBACK_ICON;
									const sectionCompleted = !!data?.completed;
									const sectionStateClass = sectionCompleted ? 'is-complete' : 'is-pending';
									const sectionItems = data?.items && typeof data.items === 'object' ? Object.entries(data.items) : [];
									const sectionActionLabel = sectionMeta?.actionLabel || __('Review', 'asneris-seo-toolkit');
									const sectionActionHref = sectionMeta?.getHref ? sectionMeta.getHref(links) : links.settings;

									return (
										<div key={ key } className={ `ASNERISSEO-react-checklist-card ${ sectionStateClass }` }>
											<div className="ASNERISSEO-react-checklist-card-head">
												<strong className="ASNERISSEO-react-checklist-title">
													<span className="ASNERISSEO-react-checklist-icon-wrap" aria-hidden="true">
														<span className={ `dashicons ${ sectionIcon }` } />
													</span>
													<span>{ sectionLabel }</span>
												</strong>
												<span className="ASNERISSEO-react-checklist-status">
													{ sectionCompleted ? __('Complete', 'asneris-seo-toolkit') : __('Pending', 'asneris-seo-toolkit') }
												</span>
											</div>
											<ul className="ASNERISSEO-react-checklist-items">
												{ sectionItems.map(([itemLabel, itemState]) => (
													<li key={ itemLabel } className={ itemState ? 'is-complete' : 'is-pending' }>
														<span className="ASNERISSEO-react-checklist-item-mark" aria-hidden="true">{ itemState ? '✓' : '○' }</span>
														<span>{ itemLabel }</span>
													</li>
												)) }
											</ul>
											<a className="ASNERISSEO-react-checklist-link" href={ sectionActionHref }>
												{ sectionActionLabel }
												<span className="dashicons dashicons-arrow-right-alt2" aria-hidden="true" />
											</a>
										</div>
									);
								}) }
							</div>
						) : (
							<p>{ __('No configuration checklist data is available.', 'asneris-seo-toolkit') }</p>
						) }
					</PanelScaffold>

					<div style={ sectionStyle } className="ASNERISSEO-react-quick-actions-card">
						<div className="ASNERISSEO-react-quick-actions-header">
							<h2 className="ASNERISSEO-heading-h2" style={ { marginTop: 0, marginBottom: '4px' } }>{ __('Site Status', 'asneris-seo-toolkit') }</h2>
							<p>{ __('Current readiness, compatibility, crawl access, and critical issues.', 'asneris-seo-toolkit') }</p>
						</div>
						<div className="ASNERISSEO-react-metrics-grid ASNERISSEO-react-grid-metrics">
							<MetricCard label={ __('Site Configuration Readiness', 'asneris-seo-toolkit') } value={ siteReadiness } hint={ `${ progress.completed || 0 }/${ progress.total || 0 } ${ __('sections configured', 'asneris-seo-toolkit') }` } iconClass="dashicons-chart-line" progressPercent={ siteReadinessPercent } variant="dashboard" />
							<MetricCard label={ __('SEO Plugin Compatibility', 'asneris-seo-toolkit') } value={ aiStatus } hint={ pluginCompatibilityHint } hintClassName="ASNERISSEO-react-metric-hint-lower" iconClass="dashicons-superhero-alt" variant="dashboard" />
							<MetricCard label={ __('Crawl Access', 'asneris-seo-toolkit') } value={ crawlAccessStatus } hint={ crawlAccessHint } hintClassName="ASNERISSEO-react-metric-hint-lower" iconClass="dashicons-search" variant="dashboard" />
							<MetricCard label={ __('Critical Issues', 'asneris-seo-toolkit') } value={ validation.conflicts || 0 } hint={ criticalIssuesHint } hintClassName="ASNERISSEO-react-metric-hint-lower" iconClass="dashicons-shield" variant="dashboard" />
							{/* <MetricCard label={ __('404 Monitor Cron', 'asneris-seo-toolkit') } value={ formatCronStatusLabel(cron404.status) } hint={ cron404Hint } hintClassName="ASNERISSEO-react-metric-hint-lower" iconClass="dashicons-clock" variant="dashboard" />
							<MetricCard label={ __('Page Diagnostics Scan Cron', 'asneris-seo-toolkit') } value={ formatCronStatusLabel(priorityScanCron.status) } hint={ priorityScanCronHint } hintClassName="ASNERISSEO-react-metric-hint-lower" iconClass="dashicons-backup" variant="dashboard" /> */}
						</div>
					</div>

					<div style={ sectionStyle } className="ASNERISSEO-react-quick-actions-card">
						<div className="ASNERISSEO-react-quick-actions-header">
							<h2 className="ASNERISSEO-heading-h2" style={ { marginTop: 0, marginBottom: '4px' } }>{ __('Quick Actions', 'asneris-seo-toolkit') }</h2>
							<p>{ __('Navigate directly to key SEO operations.', 'asneris-seo-toolkit') }</p>
						</div>
						<div className="ASNERISSEO-react-quick-actions-grid">
						<QuickLink href={ links.siteDiagnostics } label={ __('Site Diagnostics', 'asneris-seo-toolkit') } iconClass="dashicons-chart-area" />
						<QuickLink href={ links.pageDiagnostics } label={ __('Page Diagnostics', 'asneris-seo-toolkit') } iconClass="dashicons-media-document" />
						<QuickLink href={ links.bulkEdit } label={ __('Bulk Edit', 'asneris-seo-toolkit') } iconClass="dashicons-edit" />
						<QuickLink href={ links.monitor404 } label={ __('404 Monitor', 'asneris-seo-toolkit') } iconClass="dashicons-shield" />
						</div>
					</div>
					</>
				) : null }

				{ isGeneralSettingsModule ? <GeneralSettingsPanel restUrl={ dashboardData.generalSettingsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } /> : null }
				{ isPriorityPagesModule ? <PriorityPagesSettingsPanel restUrl={ dashboardData.generalSettingsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } prioritySourceRestUrl={ dashboardData.pageDiagnosticsRestUrl } /> : null }
				{ isPageDiagnosticsSettingsModule ? <PageDiagnosticsSettingsPanel restUrl={ dashboardData.generalSettingsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } /> : null }
				{ isMonitor404SettingsModule ? <Monitor404SettingsPanel settingsRestUrl={ dashboardData.logs404SettingsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } /> : null }
				{ isVerificationModule ? <VerificationSettingsPanel restUrl={ dashboardData.verificationSettingsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } /> : null }
				{ isSocialDefaultsModule ? <SocialDefaultsPanel restUrl={ dashboardData.socialSettingsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } /> : null }
				{ isSchemaModule ? <SchemaPanel restUrl={ dashboardData.schemaSettingsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } businessTypeGroups={ SCHEMA_BUSINESS_TYPE_GROUPS } /> : null }
				{ isIndexNowModule ? <IndexNowPanel restUrl={ dashboardData.indexNowSettingsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } /> : null }
				{ isTemplatesModule ? <TemplatesSettingsPanel restUrl={ dashboardData.templatesSettingsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } templateVariables={ TEMPLATE_VARIABLES } /> : null }
				{ isMaintenanceModule ? <MaintenanceSettingsPanel restUrl={ dashboardData.maintenanceSettingsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } diagnosticsSummary={ dashboardData?.summary?.diagnostics } /> : null }
				{ isAiSearchabilityModule ? <AiSearchabilityPanel restUrl={ dashboardData.aiSearchabilityRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } /> : null }
				{ isPageDiagnosticsModule ? <PageDiagnosticsPanel restUrl={ dashboardData.pageDiagnosticsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } normalizeChecks={ normalizeChecks } /> : null }
				{ isSiteDiagnosticsModule ? <SiteDiagnosticsPanel restUrl={ dashboardData.siteDiagnosticsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } normalizeChecks={ normalizeChecks } formatStatusText={ formatStatusText } /> : null }
				{ isRedirectsModule ? <RedirectsPanel restUrl={ dashboardData.redirectsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } /> : null }
				{ isMonitor404Module ? <Monitor404Panel logsRestUrl={ dashboardData.logs404RestUrl } statsRestUrl={ dashboardData.logs404StatsRestUrl } bulkRestUrl={ dashboardData.logs404BulkRestUrl } analyzeRestUrl={ dashboardData.logs404AnalyzeRestUrl } exportRestUrl={ dashboardData.logs404ExportRestUrl } settingsRestUrl={ dashboardData.logs404SettingsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } /> : null }
				{ isRobotsModule ? <RobotsPanel restUrl={ dashboardData.robotsRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } normalizeChecks={ normalizeChecks } formatStatusText={ formatStatusText } robotsSafeDefaults={ ROBOTS_SAFE_DEFAULTS } /> : null }
				{ isBulkEditModule ? <BulkEditPanel contentRestUrl={ dashboardData.bulkEditContentRestUrl } saveRestUrl={ dashboardData.bulkEditSaveRestUrl } restNonce={ dashboardData.restNonce } onStatus={ handlePanelStatus } /> : null }
				{ isHelpModule ? <HelpPanel /> : null }
				</div>
			</AdminPanelErrorBoundary>
		</div>
	);
};

const mountRoot = document.getElementById('asnerisseo-react-dashboard-root') || document.getElementById('asnerisseo-react-admin-shell-root');

if (mountRoot) {
	const mountSelector = dashboardData.mountSelector || '.asnerisseo-fallback-dashboard';
	const fallbackScope = mountRoot.closest('.ASNERISSEO-admin-wrap') || document;
	let selectedFallbackRoots = [];
	try {
		selectedFallbackRoots = Array.from(fallbackScope.querySelectorAll(mountSelector));
	} catch (error) {
		selectedFallbackRoots = [];
	}
	const fallbackClassRoots = Array.from(fallbackScope.querySelectorAll('[class*="asnerisseo-fallback-"]'));
	const fallbackRoots = Array.from(new Set([...selectedFallbackRoots, ...fallbackClassRoots]))
		.filter((fallbackRoot) => !fallbackRoot.contains(mountRoot));
	const hideFallbackFlag = dashboardData.hideFallback;
	const shouldHideFallback = hideFallbackFlag !== false && hideFallbackFlag !== 'false' && hideFallbackFlag !== 0 && hideFallbackFlag !== '0';
	const applyFallbackVisibility = (isVisible) => {
		fallbackRoots.forEach((fallbackRoot) => {
			if (isVisible) {
				fallbackRoot.style.removeProperty('display');
				return;
			}
			fallbackRoot.style.setProperty('display', 'none', 'important');
		});
	};

	if (shouldHideFallback) {
		document.body.classList.add('ASNERISSEO-react-admin-booting');
		applyFallbackVisibility(false);
	}

	try {
		createRoot(mountRoot).render(
			<AdminErrorBoundary>
				<ReactDashboard />
			</AdminErrorBoundary>
		);
	} catch (error) {
		document.body.classList.remove('ASNERISSEO-react-admin-booting');
		if (shouldHideFallback) {
			applyFallbackVisibility(true);
		}
		// eslint-disable-next-line no-console
		console.error('ASNERISSEO React admin runtime error:', error);
	}
}

