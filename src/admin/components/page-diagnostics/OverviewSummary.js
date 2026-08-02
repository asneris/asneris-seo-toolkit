import { __ } from '@wordpress/i18n';

const OverviewSummary = ({
	rows = [],
	issueCount = 0,
	totalCount = 0,
	topPriorityItems = [],
	hasCriticalIssues = false,
	hasOverviewScoreIssues = false,
	healthMessage = '',
	showGoodNews = false,
	StatusTableComponent,
	isHistoryLocked = false,
	children,
}) => {
	return (
		<div className={ isHistoryLocked ? 'ASNERISSEO-react-history-locked-content' : '' }>
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
					<p>{ healthMessage }</p>
				</div>
				<div className="ASNERISSEO-react-overview-alert-col is-middle ASNERISSEO-react-mobile-hide">
					<strong>{ __('Top Priorities', 'asneris-seo-toolkit') }</strong>
					<ul>
						{ topPriorityItems.length > 0 ? topPriorityItems.map((label, index) => (
							<li key={ `${ label }-${ index }` } style={ { color: '#0073aa' } }>{ `${ label }` }</li>
						)) : (
							<li style={ { color: '#4b5563' } }>{ __('No immediate priorities.', 'asneris-seo-toolkit') }</li>
						) }
					</ul>
				</div>
				<div className="ASNERISSEO-react-overview-alert-col is-visual ASNERISSEO-react-mobile-hide" aria-hidden="true">
					<span className="dashicons dashicons-search" />
				</div>
			</div>

			<div className="ASNERISSEO-react-note-box">
				<p className="ASNERISSEO-react-note-box-title">{ __('Summary', 'asneris-seo-toolkit') }</p>
				<p>{ `${ issueCount } ${ __('issues', 'asneris-seo-toolkit') } / ${ totalCount } ${ __('checks', 'asneris-seo-toolkit') }` }</p>
			</div>
			{ StatusTableComponent ? (
				<StatusTableComponent
					wrapClassName="ASNERISSEO-react-detail-issues-scroll"
					columns={ [
						{ key: 'check', label: __('Check', 'asneris-seo-toolkit'), width: '28%' },
						{ key: 'status', label: __('Status', 'asneris-seo-toolkit'), width: '16%', align: 'center' },
						{ key: 'result', label: __('Result', 'asneris-seo-toolkit'), width: '16%', align: 'center' },
						{ key: 'details', label: __('Details', 'asneris-seo-toolkit'), width: '40%' },
					] }
					rows={ rows }
					emptyMessage={ __('No checks available for this section.', 'asneris-seo-toolkit') }
				/>
			) : null }

			{ showGoodNews ? (
				<div className="ASNERISSEO-react-note-box is-success">
					<p className="ASNERISSEO-react-note-box-title is-success">{ __('Good News! This page is eligible for indexing and accessible to search engines.', 'asneris-seo-toolkit') }</p>
				</div>
			) : null }
			{ children }
		</div>
	);
};

export default OverviewSummary;
