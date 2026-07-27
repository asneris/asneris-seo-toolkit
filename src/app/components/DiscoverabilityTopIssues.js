import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { buildIssueBreakdownRows, buildTopIssueCategories } from '../discoverabilityIssueModel';

const DiscoverabilityTopIssues = ( {
	checks = [],
	title = null,
	emptyLabel = null,
	showBreakdown = false,
	useAccordion = false,
	breakdownTitle = null,
	breakdownAllowedFields = [],
	breakdownNormalizeLabel = null,
	breakdownDedupeByLabel = true,
	breakdownIncludePass = false,
	topIssuesOverride = null,
	breakdownRowsOverride = null,
} ) => {
	const [ activeSection, setActiveSection ] = useState( 'breakdown' );
	const computedIssues = buildTopIssueCategories( checks );
	const computedBreakdownRows = buildIssueBreakdownRows( checks, {
		allowedFields: breakdownAllowedFields,
		normalizeLabel: breakdownNormalizeLabel,
		dedupeByLabel: breakdownDedupeByLabel,
		includePass: breakdownIncludePass,
	} );
	const issues = Array.isArray( topIssuesOverride ) ? topIssuesOverride : computedIssues;
	const breakdownRows = Array.isArray( breakdownRowsOverride )
		? breakdownRowsOverride
		: computedBreakdownRows;
	const resolvedTitle = title || __( 'Top Issues', 'asneris-seo-toolkit' );
	const resolvedBreakdownTitle = breakdownTitle || __( 'Issues Breakdown', 'asneris-seo-toolkit' );
	const resolvedEmpty = emptyLabel || __( 'No major issues detected.', 'asneris-seo-toolkit' );
	const showTopSection = !showBreakdown || !useAccordion || activeSection === 'top';
	const showBreakdownSection = showBreakdown && ( !useAccordion || activeSection === 'breakdown' );

	return (
		<div className="asneris-shared-top-issues">
			{ useAccordion && showBreakdown ? (
				<div className="ASNERISSEO-react-detail-section-toggle-row" role="tablist" aria-label={ __( 'Detail and Issue Sections', 'asneris-seo-toolkit' ) }>
					<button
						type="button"
						className={ `button ASNERISSEO-react-button ASNERISSEO-react-button-small ${ activeSection === 'top' ? 'ASNERISSEO-react-button-primary' : 'ASNERISSEO-react-button-secondary' }` }
						onClick={ () => setActiveSection( 'top' ) }
						aria-selected={ activeSection === 'top' }
					>
						{ resolvedTitle }
					</button>
					<button
						type="button"
						className={ `button ASNERISSEO-react-button ASNERISSEO-react-button-small ${ activeSection === 'breakdown' ? 'ASNERISSEO-react-button-primary' : 'ASNERISSEO-react-button-secondary' }` }
						onClick={ () => setActiveSection( 'breakdown' ) }
						aria-selected={ activeSection === 'breakdown' }
					>
						{ resolvedBreakdownTitle }
					</button>
				</div>
			) : null }
			{ showTopSection ? (
				<>
					<h4 style={ { marginTop: 0, marginBottom: '10px', fontSize: '13px' } }>{ resolvedTitle }</h4>
					<div className="ASNERISSEO-react-table-wrap ASNERISSEO-react-detail-issues-scroll ASNERISSEO-react-top-issues-summary-scroll">
				<table className="ASNERISSEO-react-status-table">
					<thead>
						<tr>
							<th>{ __( 'Category', 'asneris-seo-toolkit' ) }</th>
							<th>{ __( 'Risk Level', 'asneris-seo-toolkit' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ ( issues.length
							? issues
							: [
									{
										label: resolvedEmpty,
										severity: 'Low',
									},
							]
						).map( ( item ) => {
							const severity = String( item.severity || 'Low' );
							const tone = severity.toLowerCase() === 'high'
								? 'is-fail'
								: severity.toLowerCase() === 'medium'
									? 'is-warning'
									: 'is-success';

							return (
								<tr key={ item.label }>
									<td style={ { textAlign: 'center', verticalAlign: 'middle' } }>{ item.label }</td>
									<td style={ { textAlign: 'center', verticalAlign: 'middle' } }>
										<span className={ `ASNERISSEO-react-status-chip ${ tone }` }>
											{ severity }
										</span>
									</td>
								</tr>
							);
						} ) }
					</tbody>
				</table>
					</div>
				</>
			) : null }
			{ showBreakdownSection ? (
				<div style={ { marginTop: '12px' } }>
					<h4 style={ { marginTop: 0, marginBottom: '10px', fontSize: '13px' } }>{ resolvedBreakdownTitle }</h4>
					{ breakdownRows.length > 0 ? (
						<div className="ASNERISSEO-react-table-wrap ASNERISSEO-react-detail-issues-scroll">
							<table className="ASNERISSEO-react-status-table">
								<thead>
									<tr>
										<th>{ __( 'Issue', 'asneris-seo-toolkit' ) }</th>
										<th>{ __( 'Impact to Your Business', 'asneris-seo-toolkit' ) }</th>
										<th>{ __( 'Recommendation', 'asneris-seo-toolkit' ) }</th>
										<th>{ __( 'Priority', 'asneris-seo-toolkit' ) }</th>
									</tr>
								</thead>
								<tbody>
									{ breakdownRows.map( ( row ) => {
										const priority = String( row?.cells?.[ 3 ] || 'Medium' );
											const normalizedPriority = priority.toLowerCase();
											const severityClass = normalizedPriority === 'high'
												? 'is-fail'
												: ( normalizedPriority === 'medium' ? 'is-warning' : 'is-neutral' );

										return (
											<tr key={ row.key }>
												<td>{ row?.cells?.[ 0 ] }</td>
												<td>{ row?.cells?.[ 1 ] }</td>
												<td>{ row?.cells?.[ 2 ] }</td>
												<td>
													<span className={ `ASNERISSEO-react-status-chip ${ severityClass }` }>{ priority }</span>
												</td>
											</tr>
										);
									} ) }
								</tbody>
							</table>
						</div>
					) : (
						<p className="ASNERISSEO-react-muted">{ __( 'No issues detected in latest scan.', 'asneris-seo-toolkit' ) }</p>
					) }
				</div>
			) : null }
		</div>
	);
};

export default DiscoverabilityTopIssues;
