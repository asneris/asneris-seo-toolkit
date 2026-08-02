import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const PageDiagnosticsReportHeader = ({
	title,
	url,
	seoScore,
	health,
	sourceLabel,
	generatedAtLabel,
	scoreMessage,
	isCollapsed,
	onToggleCollapse,
	actions,
	children,
	showSourceLabel = true,
	showGeneratedAtLabel = true,
	completenessStatus = '',
	scoreBandClassName = '',
	statusClassName = '',
	scoreValueLabel = '-',
	statusValueLabel = '-',
	showScoreMessage = true,
}) => {
	const headerTitle = title || __('Diagnostics Detail', 'asneris-seo-toolkit');

	return (
		<Fragment>
			{ !isCollapsed ? (
				<div className="ASNERISSEO-react-detail-header-toolbar">
					{ actions }
					<button
						type="button"
						className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-small"
						onClick={ () => onToggleCollapse(true) }
						aria-expanded={ true }
					>
						{ __('Collapse Top Details', 'asneris-seo-toolkit') }
					</button>
				</div>
			) : null }

			{ isCollapsed ? (
				<div className="ASNERISSEO-react-detail-header-collapsed">
					<div>
						<strong>{ headerTitle }</strong>
						<div className="ASNERISSEO-react-muted">
							{ generatedAtLabel }{ showSourceLabel ? ` | ${ sourceLabel }` : '' }
							{ completenessStatus ? ` | ⚠️ ${ completenessStatus }` : '' }
						</div>
					</div>
					<div className="ASNERISSEO-react-detail-header-collapsed-meta">
						<span className={ `ASNERISSEO-react-score-pill is-${ scoreBandClassName }` }>{ scoreValueLabel }</span>
						<span className={ `ASNERISSEO-react-status-chip is-${ statusClassName }` }>{ statusValueLabel }</span>
						<button
							type="button"
							className="ASNERISSEO-react-detail-header-collapsed-toggle"
							onClick={ () => onToggleCollapse(false) }
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
						<h3 className="ASNERISSEO-react-mb-0">{ headerTitle }</h3>
						{ url ? <a href={ url } target="_blank" rel="noopener noreferrer">{ url }</a> : null }
					</div>
					<div className="ASNERISSEO-react-detail-header-meta">
						<div className="ASNERISSEO-react-muted">
							{ showGeneratedAtLabel ? `${ __('Last Scan', 'asneris-seo-toolkit') }: ${ generatedAtLabel }` : null }
							{ showGeneratedAtLabel && showSourceLabel ? ' | ' : null }
							{ showSourceLabel ? `${ __('Source', 'asneris-seo-toolkit') }: ${ sourceLabel }` : null }
							{ completenessStatus ? ` | ⚠️ ${ completenessStatus }` : '' }
						</div>
						{ children }
					</div>
					<div className="ASNERISSEO-react-detail-score-box">
						<div className={ `ASNERISSEO-react-score-pill is-${ scoreBandClassName }` }>{ scoreValueLabel }</div>
						<div className="ASNERISSEO-react-muted">{ __('Overall SEO Score', 'asneris-seo-toolkit') }</div>
						<div className={ `ASNERISSEO-react-status-chip is-${ statusClassName }` }>{ statusValueLabel }</div>
						{ showScoreMessage && scoreMessage ? (
							<p className="ASNERISSEO-react-text-danger ASNERISSEO-react-mb-0">{ scoreMessage }</p>
						) : null }
					</div>
				</div>
			) }
		</Fragment>
	);
};

export default PageDiagnosticsReportHeader;
