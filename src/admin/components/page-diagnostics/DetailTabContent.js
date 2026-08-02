import { __ } from '@wordpress/i18n';
import DetailSectionCard from './DetailSectionCard';
import DetailSectionSummaryContainer from './DetailSectionSummaryContainer';
import HistoryTimelineContainer from './HistoryTimelineContainer';

const DetailTabContent = ({
	activeDetailTabLabel,
	activeDetailCard,
	activeDetailCardScore,
	activeDetailCardScoreLabel,
	activeDetailIssues,
	activeIssueListRows,
	activeSummaryHighlights,
	hasSummaryHighlightSources,
	useDetailIssueAccordion,
	effectiveDetailContentSection,
	shouldShowSnapshotHistory,
	isHistoryLoading,
	detailTabHistoryRows,
	StatusTableComponent,
	getScoreBand,
	detailContentSectionDetailsKey,
	detailContentSectionIssuesKey,
	setActiveDetailContentSection,
	hasDetailSummaryContent,
	hasDerivedDetailSummaryRows,
	activeDetailHighlights,
	tabSpecificRenderer,
}) => {
	const detailStatusTone = activeDetailCard?.noteTone === 'success' ? 'success' : 'warning';
	const detailScoreTone = getScoreBand(activeDetailCardScore);
	const renderedTabSpecificContent = typeof tabSpecificRenderer === 'function'
		? tabSpecificRenderer({
			activeDetailTabLabel,
			activeDetailCard,
			activeDetailCardScore,
			activeDetailCardScoreLabel,
			activeDetailIssues,
			activeIssueListRows,
			activeSummaryHighlights,
			activeDetailHighlights,
		})
		: null;

	return (
		<>
			<DetailSectionCard
				title={ activeDetailCard.title }
				statusLabel={ activeDetailCard.status }
				statusTone={ detailStatusTone }
				scoreLabel={ activeDetailCardScoreLabel }
				scoreTone={ detailScoreTone }
				summary={ activeDetailCard.summary }
				scoreMessage={ activeDetailCard.scoreMessage }
			>
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
			</DetailSectionCard>
			{ useDetailIssueAccordion && hasDetailSummaryContent ? (
				<div className="ASNERISSEO-react-detail-section-toggle-row" role="tablist" aria-label={ __('Detail Content Sections', 'asneris-seo-toolkit') }>
					<button
						type="button"
						className={ `button ASNERISSEO-react-button ASNERISSEO-react-button-small ${ effectiveDetailContentSection === detailContentSectionDetailsKey ? 'ASNERISSEO-react-button-primary' : 'ASNERISSEO-react-button-secondary' }` }
						onClick={ () => setActiveDetailContentSection(detailContentSectionDetailsKey) }
						aria-selected={ effectiveDetailContentSection === detailContentSectionDetailsKey }
					>
						{ __('Summary', 'asneris-seo-toolkit') }
					</button>
					<button
						type="button"
						className={ `button ASNERISSEO-react-button ASNERISSEO-react-button-small ${ effectiveDetailContentSection === detailContentSectionIssuesKey ? 'ASNERISSEO-react-button-primary' : 'ASNERISSEO-react-button-secondary' }` }
						onClick={ () => setActiveDetailContentSection(detailContentSectionIssuesKey) }
						aria-selected={ effectiveDetailContentSection === detailContentSectionIssuesKey }
					>
						{ __('Issues', 'asneris-seo-toolkit') }
					</button>
				</div>
			) : null }

			{ (!useDetailIssueAccordion || effectiveDetailContentSection === detailContentSectionDetailsKey) && hasDetailSummaryContent ? (
				<DetailSectionSummaryContainer
					title={ __('Summary', 'asneris-seo-toolkit') }
					note={ activeDetailCard.note }
					noteTone={ activeDetailCard.noteTone === 'success' ? 'success' : 'warning' }
					rows={ hasDerivedDetailSummaryRows ? activeIssueListRows : activeDetailHighlights }
				>
					{ hasDerivedDetailSummaryRows ? (
						<StatusTableComponent
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
					) : (
						<ul className="ASNERISSEO-react-tab-detail-list">
							{ activeDetailHighlights.map((entry, index) => (
								<li key={ `${ entry.label }-detail-${ index }` }><span>{ entry.label }</span><strong>{ entry.value }</strong></li>
							)) }
						</ul>
					) }
				</DetailSectionSummaryContainer>
			) : null }

			{ renderedTabSpecificContent ? (
				<div className="ASNERISSEO-react-detail-tab-specific-content">{ renderedTabSpecificContent }</div>
			) : null }
			{ !useDetailIssueAccordion || effectiveDetailContentSection === detailContentSectionIssuesKey ? (
				<>
					<div className="ASNERISSEO-react-note-box"><p className="ASNERISSEO-react-note-box-title">{ activeDetailTabLabel }</p><p>{ `${ activeDetailIssues } ${ __('issues', 'asneris-seo-toolkit') } / ${ activeIssueListRows.length } ${ __('checks', 'asneris-seo-toolkit') }` }</p></div>
					<StatusTableComponent
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
				<HistoryTimelineContainer
					title={ `${ activeDetailTabLabel } ${ __('Snapshot History', 'asneris-seo-toolkit') }` }
					isLoading={ isHistoryLoading }
					rows={ detailTabHistoryRows }
					columns={ [
						{ key: 'scan', label: __('Scan Time', 'asneris-seo-toolkit'), width: '22%' },
						{ key: 'coverage', label: __('Coverage', 'asneris-seo-toolkit'), width: '12%', align: 'center' },
						{ key: 'source', label: __('Source', 'asneris-seo-toolkit'), width: '16%', align: 'center' },
						{ key: 'issues', label: __('Issues/Checks', 'asneris-seo-toolkit'), width: '14%', align: 'center' },
						{ key: 'pass', label: __('Pass', 'asneris-seo-toolkit'), width: '12%', align: 'center' },
						{ key: 'warning', label: __('Warning', 'asneris-seo-toolkit'), width: '12%', align: 'center' },
						{ key: 'fail', label: __('Fail', 'asneris-seo-toolkit'), width: '12%', align: 'center' },
					] }
					StatusTableComponent={ StatusTableComponent }
					emptyMessage={ __('No snapshot history available for this tab yet.', 'asneris-seo-toolkit') }
				/>
			) : null }
		</>
	);
};

export default DetailTabContent;
