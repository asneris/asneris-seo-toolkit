import { __ } from '@wordpress/i18n';
import OverviewSummary from './OverviewSummary';

const OverviewTabContent = ({
	isHistoryLocked,
	shouldShowSnapshotHistory,
	isHistoryLoading,
	historyTrend,
	historyRows,
	historySectionRef,
	isSnapshotHistorySuppressedInEmbeddedFlow,
	isEmbeddedDetailOpenFlow,
	historyCount,
	historyLimit,
	jumpToSnapshotHistory,
	overviewSummaryRows,
	overviewIssueCount,
	topPriorityItems,
	hasCriticalIssues,
	hasOverviewScoreIssues,
	overviewHealthMessage,
	showGoodNews,
	StatusTableComponent,
}) => {
	return (
		<>
			{ isHistoryLocked && shouldShowSnapshotHistory ? (
				<div className="ASNERISSEO-react-note-box is-warning">
					<p className="ASNERISSEO-react-note-box-title is-warning">{ __('History limit reached', 'asneris-seo-toolkit') }</p>
					<p className="ASNERISSEO-react-mb-0">{ `${ __('This page has reached', 'asneris-seo-toolkit') } ${ historyCount }/${ historyLimit } ${ __('history records. Delete old history records to continue using all report sections.', 'asneris-seo-toolkit') }` }</p>
				</div>
			) : null }
			<OverviewSummary
				rows={ overviewSummaryRows }
				issueCount={ overviewIssueCount }
				totalCount={ overviewSummaryRows.length }
				topPriorityItems={ topPriorityItems }
				hasCriticalIssues={ hasCriticalIssues }
				hasOverviewScoreIssues={ hasOverviewScoreIssues }
				healthMessage={ overviewHealthMessage }
				showGoodNews={ showGoodNews }
				StatusTableComponent={ StatusTableComponent }
				isHistoryLocked={ isHistoryLocked && shouldShowSnapshotHistory }
			/>
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
					<StatusTableComponent
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
	);
};

export default OverviewTabContent;
