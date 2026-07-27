import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import PanelScaffold from '../common/PanelScaffold';
import fetchJson from '../common/fetchJson';
import InlineHelpDetails from '../common/InlineHelpDetails';

const SITE_DIAGNOSTICS_EDUCATION_URLS = {
	sitemap: 'https://asneris.com/wp-toolkits/asneris-wordpress-seo-toolkit-sitemap/',
	robots: 'https://asneris.com/wp-toolkits/asneris-wordpress-seo-toolkit-robots-txt/',
	engineLogin: 'https://app.asneris.com',
	engineInfo: 'https://asneris.com/asneris-seo-engine/',
};

const normalizeStatus = (value) => String(value || '').toLowerCase();

const isPassStatus = (value) => {
	const status = normalizeStatus(value);
	return status === 'pass' || status === 'success' || status === 'ok';
};

const isIssueStatus = (value) => {
	const status = normalizeStatus(value);
	return status === 'issue' || status === 'error' || status === 'fail' || status === 'failed' || status === 'conflict' || status === 'missing';
};

const toStatusTone = (value) => {
	if (isPassStatus(value)) {
		return 'success';
	}

	if (isIssueStatus(value)) {
		return 'danger';
	}

	return 'warning';
};

const CircularProgress = ({ value, tone }) => {
	const safeValue = Number.isFinite(value) ? Math.max(0, Math.min(100, Math.round(value))) : 0;
	return (
		<div className={ `ASNERISSEO-react-site-report-progress is-${ tone }` } style={ { '--as-site-pct': safeValue } } aria-label={ `${ safeValue }%` }>
			<span>{ `${ safeValue }%` }</span>
		</div>
	);
};

const countPassed = (items) => items.filter((item) => isPassStatus(item.status)).length;

const toPercent = (passed, total) => {
	if (total < 1) {
		return 0;
	}
	return Math.round((passed / total) * 100);
};

const SiteDiagnosticsPanel = ({ restUrl, restNonce, onStatus, normalizeChecks, formatStatusText }) => {
	const [data, setData] = useState({ sitemap: {}, duplicates: {}, robots: {}, canonical: {}, summary: {} });
	const [isLoading, setIsLoading] = useState(false);
	const [errorMessage, setErrorMessage] = useState('');
	const [expandedDetailSection, setExpandedDetailSection] = useState(null);

	const loadDiagnostics = useCallback(() => {
		if (!restUrl) {
			return;
		}

		setIsLoading(true);
		setErrorMessage('');

		fetchJson(restUrl, {
			method: 'GET',
			headers: { 'X-WP-Nonce': restNonce || '' },
		})
			.then((payload) => {
				setData({
					sitemap: payload?.sitemap || {},
					duplicates: payload?.duplicates || {},
					robots: payload?.robots || {},
					canonical: payload?.canonical || {},
					summary: payload?.summary || {},
				});
			})
			.catch((error) => {
				const message = error.message || __('Unable to load site diagnostics.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setIsLoading(false));
	}, [onStatus, restNonce, restUrl]);

	useEffect(() => {
		loadDiagnostics();
	}, [loadDiagnostics]);

	const activePlugins = Array.isArray(data.duplicates?.active_plugins) ? data.duplicates.active_plugins : [];
	const duplicateSignals = data.duplicates?.duplicates || {};
	const robotChecks = normalizeChecks(data.robots?.checks).map((check) => ({
		label: check?.label || '-',
		status: check?.status || 'warning',
		details: check?.message || '-',
	}));
	const canonicalChecks = Array.isArray(data.canonical?.checks) ? data.canonical.checks : [];
	const canonicalItems = canonicalChecks.map((item) => ({
		label: item?.check || '-',
		status: item?.status || 'warning',
		details: item?.details || '-',
	}));

	const sitemapChecks = useMemo(() => ([
		{ label: __('Sitemap URL', 'asneris-seo-toolkit'), status: data.sitemap?.found ? 'pass' : 'issue', details: data.sitemap?.url || __('Not found', 'asneris-seo-toolkit') },
		{ label: __('HTTP Validation', 'asneris-seo-toolkit'), status: (data.sitemap?.http_status || 0) === 200 ? 'pass' : 'issue', details: data.sitemap?.http_status ? `HTTP ${ data.sitemap.http_status } - ${ data.sitemap.http_message || '' }` : '-' },
		{ label: __('robots.txt Reference', 'asneris-seo-toolkit'), status: data.sitemap?.in_robots ? 'pass' : 'warning', details: data.sitemap?.robots_message || '-' },
		{ label: __('Controlled By', 'asneris-seo-toolkit'), status: 'pass', details: data.sitemap?.controller || '-' },
	]), [data.sitemap?.controller, data.sitemap?.found, data.sitemap?.http_message, data.sitemap?.http_status, data.sitemap?.in_robots, data.sitemap?.robots_message, data.sitemap?.url]);

	const duplicateItems = useMemo(() => ([
		{
			label: __('Single SEO Plugin', 'asneris-seo-toolkit'),
			status: activePlugins.length === 0 ? 'pass' : 'issue',
			details: activePlugins.length === 0 ? __('Only this plugin detected', 'asneris-seo-toolkit') : activePlugins.join(', '),
		},
		...['canonical', 'robots'].map((key) => ({
			label: key,
			status: duplicateSignals?.[key] ? 'issue' : 'pass',
			details: duplicateSignals?.[key] || __('No duplicates', 'asneris-seo-toolkit'),
		})),
	]), [activePlugins, duplicateSignals]);

	const warningsCount = Number(data.summary?.warnings || 0);
	const conflictsCount = Number(data.summary?.conflicts || 0);
	const hasPluginConflicts = activePlugins.length > 0;
	const robotsStatus = normalizeStatus(data.robots?.status);
	const canonicalHasConflicts = Boolean(data.canonical?.has_conflicts);
	const canonicalHasWarnings = Boolean(data.canonical?.has_warnings);

	const technicalChecks = useMemo(() => ([
		{
			label: __('Sitemap Available', 'asneris-seo-toolkit'),
			status: data.sitemap?.found ? 'pass' : 'issue',
			details: data.sitemap?.url || __('Not found', 'asneris-seo-toolkit'),
		},
		{
			label: __('Sitemap HTTP 200', 'asneris-seo-toolkit'),
			status: (data.sitemap?.http_status || 0) === 200 ? 'pass' : 'issue',
			details: data.sitemap?.http_status ? `HTTP ${ data.sitemap.http_status } - ${ data.sitemap.http_message || '' }` : '-',
		},
		{
			label: __('robots.txt Validation', 'asneris-seo-toolkit'),
			status: robotsStatus === 'success' ? 'pass' : 'warning',
			details: data.robots?.message || __('Crawling rules require review.', 'asneris-seo-toolkit'),
		},
		{
			label: __('Canonical Conflicts', 'asneris-seo-toolkit'),
			status: canonicalHasConflicts ? 'issue' : 'pass',
			details: canonicalHasConflicts ? __('Conflicts detected.', 'asneris-seo-toolkit') : __('No conflicts detected.', 'asneris-seo-toolkit'),
		},
		{
			label: __('Canonical Warnings', 'asneris-seo-toolkit'),
			status: canonicalHasWarnings ? 'warning' : 'pass',
			details: canonicalHasWarnings ? __('Warnings detected.', 'asneris-seo-toolkit') : __('No warnings detected.', 'asneris-seo-toolkit'),
		},
		{
			label: __('Plugin Compatibility', 'asneris-seo-toolkit'),
			status: hasPluginConflicts ? 'issue' : 'pass',
			details: hasPluginConflicts ? activePlugins.join(', ') : __('No plugin conflicts detected.', 'asneris-seo-toolkit'),
		},
	]), [activePlugins, canonicalHasConflicts, canonicalHasWarnings, data.robots?.message, data.sitemap?.found, data.sitemap?.http_message, data.sitemap?.http_status, data.sitemap?.url, hasPluginConflicts, robotsStatus]);

	const detailSectionsByCategory = useMemo(() => ([
		{
			key: 'technical',
			label: __('Technical SEO', 'asneris-seo-toolkit'),
			title: __('Technical SEO Validation', 'asneris-seo-toolkit'),
			description: __('Core crawl and technical readiness checks', 'asneris-seo-toolkit'),
			items: technicalChecks,
		},
		{
			key: 'canonical',
			label: __('Canonical Signals', 'asneris-seo-toolkit'),
			title: __('Canonical URL Validation', 'asneris-seo-toolkit'),
			description: __('Checks canonical usage and consistency patterns', 'asneris-seo-toolkit'),
			items: canonicalItems.length > 0 ? canonicalItems : [{ label: __('Canonical checks', 'asneris-seo-toolkit'), status: 'warning', details: __('No canonical diagnostics returned.', 'asneris-seo-toolkit') }],
		},
		{
			key: 'robots',
			label: __('Robots', 'asneris-seo-toolkit'),
			title: __('Robots.txt Validation', 'asneris-seo-toolkit'),
			description: __('Checks robots.txt file and crawl directives', 'asneris-seo-toolkit'),
			items: robotChecks.length > 0 ? robotChecks : [{ label: __('Robots checks', 'asneris-seo-toolkit'), status: 'warning', details: __('No robots diagnostics returned.', 'asneris-seo-toolkit') }],
		},
		{
			key: 'sitemap',
			label: __('Sitemap', 'asneris-seo-toolkit'),
			title: __('Sitemap Visibility', 'asneris-seo-toolkit'),
			description: __('Checks related to sitemap availability and validation', 'asneris-seo-toolkit'),
			items: sitemapChecks,
		},
		{
			key: 'plugins',
			label: __('SEO Plugins', 'asneris-seo-toolkit'),
			title: __('SEO Plugin Validation', 'asneris-seo-toolkit'),
			description: __('Checks for duplicate or conflicting SEO outputs', 'asneris-seo-toolkit'),
			items: duplicateItems,
		},
	]), [canonicalItems, duplicateItems, robotChecks, sitemapChecks, technicalChecks]);

	const discoverabilityCards = useMemo(() => {
		const makeCard = (section) => {
			const total = Math.max((section.items || []).length, 1);
			const passed = countPassed(section.items || []);
			const percent = toPercent(passed, total);
			const tone = percent >= 80 ? 'success' : (percent >= 50 ? 'warning' : 'danger');
			const status = percent >= 80 ? __('Good', 'asneris-seo-toolkit') : (percent >= 50 ? __('Needs Attention', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit'));
			return {
				key: section.key,
				label: section.label,
				percent,
				tone,
				status,
				summary: `${ passed } ${ __('of', 'asneris-seo-toolkit') } ${ total } ${ __('checks passed', 'asneris-seo-toolkit') }`,
			};
		};

		return detailSectionsByCategory.map((section) => makeCard(section));
	}, [detailSectionsByCategory]);

	const overallPercent = discoverabilityCards.length > 0
		? Math.round(discoverabilityCards.reduce((sum, item) => sum + item.percent, 0) / discoverabilityCards.length)
		: 0;
	const overallHealth = conflictsCount > 0 ? __('Needs Attention', 'asneris-seo-toolkit') : (overallPercent >= 80 ? __('Healthy', 'asneris-seo-toolkit') : __('Good', 'asneris-seo-toolkit'));

	const highPriorityActions = [
		!data.sitemap?.found ? {
			key: 'sitemap-missing',
			tone: 'danger',
			title: __('Sitemap not found', 'asneris-seo-toolkit'),
			detail: __('Search engines may discover pages less efficiently without a sitemap.', 'asneris-seo-toolkit'),
			learnMoreUrl: SITE_DIAGNOSTICS_EDUCATION_URLS.sitemap,
			learnMoreTopic: __('Sitemap', 'asneris-seo-toolkit'),
		} : null,
		robotsStatus !== 'success' ? {
			key: 'robots-warning',
			tone: 'warning',
			title: __('robots.txt needs review', 'asneris-seo-toolkit'),
			detail: __('Crawling rules could not be fully validated.', 'asneris-seo-toolkit'),
			learnMoreUrl: SITE_DIAGNOSTICS_EDUCATION_URLS.robots,
			learnMoreTopic: __('robots.txt', 'asneris-seo-toolkit'),
		} : null,
		hasPluginConflicts ? {
			key: 'plugin-conflicts',
			tone: 'danger',
			title: __('Plugin conflicts detected', 'asneris-seo-toolkit'),
			detail: activePlugins.join(', '),
		} : null,
		canonicalHasConflicts ? {
			key: 'canonical-conflicts',
			tone: 'warning',
			title: __('Canonical conflicts found', 'asneris-seo-toolkit'),
			detail: __('Some canonical references appear contradictory or looped.', 'asneris-seo-toolkit'),
		} : null,
	].filter(Boolean);

	const detailedSections = detailSectionsByCategory;

	const toggleDetailSection = (sectionKey) => {
		setExpandedDetailSection((previous) => (previous === sectionKey ? null : sectionKey));
	};

	const recommendedSteps = useMemo(() => {
		const issueSteps = [];
		const warningSteps = [];
		const infoSteps = [];

		if (!data.sitemap?.found) {
			issueSteps.push({
				key: 'step-sitemap-generate',
				title: __('Generate sitemap.xml', 'asneris-seo-toolkit'),
				detail: __('Create and submit your XML sitemap.', 'asneris-seo-toolkit'),
				tone: 'danger',
			});
		}

		if (!data.sitemap?.in_robots) {
			warningSteps.push({
				key: 'step-sitemap-robots-ref',
				title: __('Add sitemap reference to robots.txt', 'asneris-seo-toolkit'),
				detail: __('Include the Sitemap directive so crawlers can discover it quickly.', 'asneris-seo-toolkit'),
				tone: 'warning',
			});
		}

		if (robotsStatus !== 'success') {
			warningSteps.push({
				key: 'step-robots-repair',
				title: __('Create or repair robots.txt', 'asneris-seo-toolkit'),
				detail: __('Add clear crawl instructions and resolve validation issues.', 'asneris-seo-toolkit'),
				tone: 'warning',
			});
		}

		if (hasPluginConflicts || duplicateItems.some((item) => item.status === 'issue')) {
			issueSteps.push({
				key: 'step-duplicates-fix',
				title: __('Resolve duplicate SEO signals', 'asneris-seo-toolkit'),
				detail: __('Keep one source of title, description, canonical, and schema output.', 'asneris-seo-toolkit'),
				tone: 'danger',
			});
		}

		if (canonicalHasConflicts || canonicalHasWarnings) {
			(canonicalHasConflicts ? issueSteps : warningSteps).push({
				key: 'step-canonical-audit',
				title: __('Audit canonical destinations', 'asneris-seo-toolkit'),
				detail: __('Review canonical targets and remove loops or unintended consolidation.', 'asneris-seo-toolkit'),
				tone: canonicalHasConflicts ? 'danger' : 'warning',
			});
		}

		const hasBlockers = issueSteps.length > 0 || warningSteps.length > 0;

		if (!hasBlockers) {
			infoSteps.push({
				key: 'step-resolved',
				title: __('Resolved: No critical blockers detected', 'asneris-seo-toolkit'),
				detail: __('Current crawl and indexing signals look stable. Keep monitoring after major site changes.', 'asneris-seo-toolkit'),
				tone: 'resolved',
			});
		}

		infoSteps.push({
			key: 'step-rerun',
			title: __('Re-run Site Diagnostics', 'asneris-seo-toolkit'),
			detail: __('Scan again after fixes are applied.', 'asneris-seo-toolkit'),
			tone: 'info',
		});

		infoSteps.push({
			key: 'step-validate-indexing',
			title: __('Validate indexing readiness', 'asneris-seo-toolkit'),
			detail: __('Confirm sitemap, robots, and canonical signals align.', 'asneris-seo-toolkit'),
			tone: 'info',
		});

		return [ ...issueSteps, ...warningSteps, ...infoSteps ].slice(0, 5);
	}, [canonicalHasConflicts, canonicalHasWarnings, data.sitemap?.found, data.sitemap?.in_robots, duplicateItems, hasPluginConflicts, robotsStatus]);

	return (
		<PanelScaffold
			title={ __('Site Diagnostics', 'asneris-seo-toolkit') }
			description={ __('Discoverability analysis of your published website.', 'asneris-seo-toolkit') }
			panelClass="ASNERISSEO-react-data-panel"
		>
			<InlineHelpDetails
				title={ __('Help: Site Diagnostics', 'asneris-seo-toolkit') }
				items={ [
					__('Use this page to detect sitemap visibility, canonical consistency, and conflict signals.', 'asneris-seo-toolkit'),
					__('Warnings indicate risk patterns, while conflicts indicate contradictory signals.', 'asneris-seo-toolkit'),
					__('Review duplicate and canonical sections together before making structural SEO changes.', 'asneris-seo-toolkit'),
				] }
				note={ __('This panel reports technical clarity signals and is not a ranking predictor.', 'asneris-seo-toolkit') }
			/>

			<div className="ASNERISSEO-react-site-report-header ASNERISSEO-react-block">
				<div>
					<h3 className="ASNERISSEO-heading-h3 ASNERISSEO-react-mb-0">{ __('Site Diagnostics', 'asneris-seo-toolkit') }</h3>
					<p className="ASNERISSEO-react-muted ASNERISSEO-react-mb-0">{ __('Discoverability analysis of your published website', 'asneris-seo-toolkit') }</p>
				</div>
				<div className="ASNERISSEO-react-site-report-header-actions">
					<span className="ASNERISSEO-react-site-report-live"><i />{ __('Live Scan', 'asneris-seo-toolkit') }</span>
					{/* <span>{ `${ __('Last Scan', 'asneris-seo-toolkit') }: ${ data.summary?.last_checked || __('Just now', 'asneris-seo-toolkit') }` }</span> */}
					<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary" onClick={ loadDiagnostics } disabled={ isLoading }>
						{ isLoading ? __('Scanning...', 'asneris-seo-toolkit') : __('Scan Again', 'asneris-seo-toolkit') }
					</button>
				</div>
			</div>

			{ isLoading ? <p>{ __('Loading site diagnostics...', 'asneris-seo-toolkit') }</p> : null }
			{ errorMessage ? <p className="ASNERISSEO-react-text-danger">{ errorMessage }</p> : null }

			<div className="ASNERISSEO-react-site-report-kpi-grid ASNERISSEO-react-block">
				<div className="ASNERISSEO-react-site-report-kpi-card is-health">
					<div className="ASNERISSEO-react-site-report-kpi-head"><span className="ASNERISSEO-react-site-report-kpi-icon is-health" aria-hidden="true">+</span><p className="ASNERISSEO-react-site-report-kpi-label">{ __('Overall Health', 'asneris-seo-toolkit') }</p></div>
					<p className="ASNERISSEO-react-site-report-kpi-value">{ overallHealth }</p>
					<p className="ASNERISSEO-react-site-report-kpi-sub">{ __('Based on sitemap, robots, canonical, and plugin checks.', 'asneris-seo-toolkit') }</p>
				</div>
				<div className="ASNERISSEO-react-site-report-kpi-card is-danger">
					<div className="ASNERISSEO-react-site-report-kpi-head"><span className="ASNERISSEO-react-site-report-kpi-icon is-danger" aria-hidden="true">!</span><p className="ASNERISSEO-react-site-report-kpi-label">{ __('Issues Found', 'asneris-seo-toolkit') }</p></div>
					<p className="ASNERISSEO-react-site-report-kpi-value">{ conflictsCount }</p>
					<p className="ASNERISSEO-react-site-report-kpi-sub">{ __('Conflicts that may affect crawling and indexing.', 'asneris-seo-toolkit') }</p>
				</div>
				<div className="ASNERISSEO-react-site-report-kpi-card is-warning">
					<div className="ASNERISSEO-react-site-report-kpi-head"><span className="ASNERISSEO-react-site-report-kpi-icon is-warning" aria-hidden="true">!</span><p className="ASNERISSEO-react-site-report-kpi-label">{ __('Warnings', 'asneris-seo-toolkit') }</p></div>
					<p className="ASNERISSEO-react-site-report-kpi-value">{ warningsCount }</p>
					<p className="ASNERISSEO-react-site-report-kpi-sub">{ __('Warnings that should be reviewed.', 'asneris-seo-toolkit') }</p>
				</div>
				<div className="ASNERISSEO-react-site-report-kpi-card is-plugin">
					<div className="ASNERISSEO-react-site-report-kpi-head"><span className="ASNERISSEO-react-site-report-kpi-icon is-plugin" aria-hidden="true">*</span><p className="ASNERISSEO-react-site-report-kpi-label">{ __('Plugin Status', 'asneris-seo-toolkit') }</p></div>
					<p className="ASNERISSEO-react-site-report-kpi-value">{ hasPluginConflicts ? __('Attention', 'asneris-seo-toolkit') : __('Healthy', 'asneris-seo-toolkit') }</p>
					<p className="ASNERISSEO-react-site-report-kpi-sub">{ hasPluginConflicts ? activePlugins.join(', ') : __('No plugin conflicts detected.', 'asneris-seo-toolkit') }</p>
				</div>
			</div>

			<div className="ASNERISSEO-react-block">
				<h4 className="ASNERISSEO-heading-h3 ASNERISSEO-react-section-title">{ __('High Priority Actions', 'asneris-seo-toolkit') }</h4>
				{ highPriorityActions.length > 0 ? (
					<div className="ASNERISSEO-react-site-report-actions-grid">
						{ highPriorityActions.map((action) => (
							<div key={ action.key } className={ `ASNERISSEO-react-site-report-action-card is-${ action.tone }` }>
								<div>
									<p className="ASNERISSEO-react-site-report-action-title"><span className={ `ASNERISSEO-react-site-report-action-dot is-${ action.tone }` } aria-hidden="true" />{ action.title }</p>
									<p className="ASNERISSEO-react-site-report-action-text">{ action.detail }</p>
								</div>
								{ action.learnMoreUrl ? (
									<a
										className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
										href={ action.learnMoreUrl }
										target="_blank"
										rel="noopener noreferrer"
										aria-label={ sprintf(
											__('Learn more about %s', 'asneris-seo-toolkit'),
											action.learnMoreTopic || __('this topic', 'asneris-seo-toolkit')
										) }
									>
										{ __('Learn More', 'asneris-seo-toolkit') }
										<span aria-hidden="true"> (external)</span>
									</a>
								) : (
									<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary">{ __('Learn More', 'asneris-seo-toolkit') }</button>
								) }
							</div>
						)) }
					</div>
				) : (
					<div className="ASNERISSEO-react-note-box is-success">
						<p className="ASNERISSEO-react-note-box-title is-success">{ __('No high-priority blockers detected.', 'asneris-seo-toolkit') }</p>
					</div>
				) }
			</div>

			<div className="ASNERISSEO-react-block">
				<h4 className="ASNERISSEO-heading-h3 ASNERISSEO-react-section-title">{ __('Discoverability Health', 'asneris-seo-toolkit') }</h4>
				<div className="ASNERISSEO-react-site-report-health-grid">
					{ discoverabilityCards.map((item) => (
						<div key={ item.key } className="ASNERISSEO-react-site-report-health-card">
							<p className="ASNERISSEO-react-site-report-health-label">{ item.label }</p>
							<CircularProgress value={ item.percent } tone={ item.tone } />
							<p className={ `ASNERISSEO-react-site-report-health-status is-${ item.tone }` }>{ item.status }</p>
							<p className="ASNERISSEO-react-site-report-health-sub">{ item.summary }</p>
						</div>
					)) }
				</div>
			</div>

			<div className="ASNERISSEO-react-block">
				<h4 className="ASNERISSEO-heading-h3 ASNERISSEO-react-section-title">{ __('Detailed Results', 'asneris-seo-toolkit') }</h4>
				<div className="ASNERISSEO-react-site-report-details-list">
					{ detailedSections.map((section) => (
						<details key={ section.key } className="ASNERISSEO-react-site-report-detail-row" open={ expandedDetailSection === section.key }>
							<summary onClick={ (event) => {
								event.preventDefault();
								toggleDetailSection(section.key);
							} }>
								<div className="ASNERISSEO-react-site-report-detail-title-wrap">
									<p className="ASNERISSEO-react-site-report-detail-title">{ section.title }</p>
									<p className="ASNERISSEO-react-site-report-detail-desc">{ section.description }</p>
								</div>
								<div className="ASNERISSEO-react-site-report-detail-chips">
									{ section.items.slice(0, 5).map((item, idx) => (
										<span key={ `${ section.key }-${ idx }` } className={ `ASNERISSEO-react-site-report-chip is-${ toStatusTone(item.status) }` }>{ item.label }</span>
									)) }
								</div>
							</summary>
							<div className="ASNERISSEO-react-site-report-detail-grid">
								{ section.items.map((item, idx) => (
									<div key={ `${ section.key }-item-${ idx }` } className="ASNERISSEO-react-site-report-detail-item">
										<span className={ `ASNERISSEO-react-site-report-chip is-${ toStatusTone(item.status) }` }>{ formatStatusText ? formatStatusText(item.status) : String(item.status || '-') }</span>
										<strong>{ item.label }</strong>
										<p>{ item.details }</p>
									</div>
								)) }
							</div>
						</details>
					)) }
				</div>
			</div>

			<div className="ASNERISSEO-react-block">
				<h4 className="ASNERISSEO-heading-h3 ASNERISSEO-react-section-title">{ __('Recommended Next Steps', 'asneris-seo-toolkit') }</h4>
				<div className="ASNERISSEO-react-site-report-steps-grid">
					{ recommendedSteps.map((step, index) => (
						<div key={ step.key } className={ `ASNERISSEO-react-site-report-step-card is-${ step.tone || 'info' }` }>
							<span className={ `ASNERISSEO-react-site-report-step-num is-${ step.tone || 'info' }` }>{ index + 1 }</span>
							<p className="ASNERISSEO-react-site-report-step-title">{ step.title }</p>
							<p className="ASNERISSEO-react-site-report-step-text">{ step.detail }</p>
						</div>
					)) }
				</div>
			</div>

			{/* <div className="ASNERISSEO-react-note-box ASNERISSEO-react-site-report-disclaimer">
				<p className="ASNERISSEO-react-note-box-title">{ __('Upgrade to Asneris Engine', 'asneris-seo-toolkit') }</p>
				<p>{ __('Get advanced insights, more history, competitor tracking, and AI recommendations.', 'asneris-seo-toolkit') }</p>
				<p>
					<a
						href={ SITE_DIAGNOSTICS_EDUCATION_URLS.engineLogin }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __('Login at app.asneris.com', 'asneris-seo-toolkit') }
					</a>{ ' ' }
					<span aria-hidden="true">(external)</span>
				</p>
				<p>
					<a
						href={ SITE_DIAGNOSTICS_EDUCATION_URLS.engineInfo }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ SITE_DIAGNOSTICS_EDUCATION_URLS.engineInfo }
					</a>{ ' ' }
					<span aria-hidden="true">(external)</span>
				</p>
			</div> */}

			<div className="ASNERISSEO-react-note-box ASNERISSEO-react-site-report-disclaimer">
				<p className="ASNERISSEO-react-note-box-title">{ __('About This Scan', 'asneris-seo-toolkit') }</p>
				<ul>
					<li>{ __('Analysis is performed on your published website and publicly accessible pages.', 'asneris-seo-toolkit') }</li>
					<li>{ __('Results are generated by Asneris using discoverability, technical SEO, metadata, semantic structure, and indexing readiness checks.', 'asneris-seo-toolkit') }</li>
					<li>{ __('This report does not use Google Search Console (GSC), Google Analytics, search rankings, impressions, clicks, or other third-party performance data.', 'asneris-seo-toolkit') }</li>
					<li>{ __('Results reflect the website state at the time of this scan.', 'asneris-seo-toolkit') }</li>
				</ul>
			</div>
		</PanelScaffold>
	);
};

export default SiteDiagnosticsPanel;
