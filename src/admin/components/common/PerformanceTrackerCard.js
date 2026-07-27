import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const formatDurationMs = (value) => `${ Number(value || 0).toLocaleString() } ms`;
const formatBytesToMb = (value) => `${ (Number(value || 0) / (1024 * 1024)).toFixed(2) } MB`;
const formatCount = (value) => Number(value || 0).toLocaleString();

const getValueTone = (value, mode = 'none') => {
	const parsed = Number(value);
	if (!Number.isFinite(parsed)) {
		return '';
	}

	if (mode === 'warning') {
		return parsed > 0 ? 'is-warning' : '';
	}

	if (mode === 'error') {
		return parsed > 0 ? 'is-danger' : 'is-success';
	}

	if (mode === 'positive-zero') {
		return parsed >= 0 ? 'is-success' : 'is-danger';
	}

	return '';
};

const getGroupIconClass = (key) => {
	if (key === 'execution') {
		return 'dashicons-clock';
	}
	if (key === 'memory') {
		return 'dashicons-database-view';
	}
	if (key === 'database') {
		return 'dashicons-database';
	}
	if (key === 'results') {
		return 'dashicons-clipboard';
	}

	return 'dashicons-admin-tools';
};

const buildPerformanceGroups = (performance) => {
	if (!performance) {
		return [];
	}

	const rulesEvaluated =
		performance?.analysis?.seoRulesEvaluated ??
		performance?.analysis?.rulesEvaluated ??
		0;
	const processedLabel =
		performance?.analysis?.pagesProcessed !== undefined
			? __('Pages Processed', 'asneris-seo-toolkit')
			: __('Records Processed', 'asneris-seo-toolkit');
	const processedCount =
		performance?.analysis?.pagesProcessed ??
		performance?.analysis?.recordsProcessed ??
		0;

	const databaseRows = [
		{ label: __('Query Delta', 'asneris-seo-toolkit'), value: formatCount(performance?.database?.queryDelta) },
	];

	if (performance?.database?.recordsRead !== undefined) {
		databaseRows.push({ label: __('Records Read', 'asneris-seo-toolkit'), value: formatCount(performance?.database?.recordsRead) });
	}
	if (performance?.database?.recordsUpdated !== undefined) {
		databaseRows.push({ label: __('Records Updated', 'asneris-seo-toolkit'), value: formatCount(performance?.database?.recordsUpdated) });
	}
	if (performance?.database?.recordsInserted !== undefined) {
		databaseRows.push({ label: __('Records Inserted', 'asneris-seo-toolkit'), value: formatCount(performance?.database?.recordsInserted) });
	}

	return [
		{
			key: 'execution',
			title: __('Execution', 'asneris-seo-toolkit'),
			rows: [
				{ label: __('Started', 'asneris-seo-toolkit'), value: performance?.timing?.startedAt || '-' },
				{ label: __('Ended', 'asneris-seo-toolkit'), value: performance?.timing?.endedAt || '-' },
				{ label: __('Duration', 'asneris-seo-toolkit'), value: formatDurationMs(performance?.timing?.executionMs) },
			],
		},
		{
			key: 'memory',
			title: __('Memory', 'asneris-seo-toolkit'),
			rows: [
				{ label: __('Start Memory', 'asneris-seo-toolkit'), value: formatBytesToMb(performance?.memory?.startBytes) },
				{ label: __('End Memory', 'asneris-seo-toolkit'), value: formatBytesToMb(performance?.memory?.endBytes) },
				{ label: __('Peak Memory', 'asneris-seo-toolkit'), value: formatBytesToMb(performance?.memory?.peakBytes) },
				{ label: __('Memory Increase', 'asneris-seo-toolkit'), value: formatBytesToMb(performance?.memory?.increaseBytes), tone: getValueTone(performance?.memory?.increaseBytes, 'positive-zero') },
			],
		},
		{
			key: 'database',
			title: __('Database', 'asneris-seo-toolkit'),
			rows: databaseRows,
		},
		{
			key: 'results',
			title: __('Results', 'asneris-seo-toolkit'),
			rows: [
				{ label: __('Rules Evaluated', 'asneris-seo-toolkit'), value: formatCount(rulesEvaluated) },
				{ label: __('Warnings', 'asneris-seo-toolkit'), value: formatCount(performance?.analysis?.warnings), tone: getValueTone(performance?.analysis?.warnings, 'warning') },
				{ label: __('Errors', 'asneris-seo-toolkit'), value: formatCount(performance?.analysis?.errors), tone: getValueTone(performance?.analysis?.errors, 'error') },
				{ label: processedLabel, value: formatCount(processedCount) },
			],
		},
		{
			key: 'environment',
			title: __('Environment', 'asneris-seo-toolkit'),
			rows: [
				{ label: __('PHP Version', 'asneris-seo-toolkit'), value: performance?.environment?.phpVersion || '-' },
				{ label: __('WordPress Version', 'asneris-seo-toolkit'), value: performance?.environment?.wpVersion || '-' },
				{ label: __('Plugin Version', 'asneris-seo-toolkit'), value: performance?.environment?.pluginVersion || '-' },
			],
		},
	];
};

const PerformanceTrackerCard = ({
	title,
	statusLabel,
	statusClassName,
	advisoryMessage,
	performance = null,
	rows = [],
	modalTitle,
	className = '',
}) => {
	const [isPopupOpen, setIsPopupOpen] = useState(false);
	const [showPopupDetails, setShowPopupDetails] = useState(true);
	const wrapperClassName = ['ASNERISSEO-react-note-box', className].filter(Boolean).join(' ');
	const groups = buildPerformanceGroups(performance);
	const rulesEvaluated =
		performance?.analysis?.seoRulesEvaluated ??
		performance?.analysis?.rulesEvaluated ??
		0;
	const processedCount =
		performance?.analysis?.pagesProcessed ??
		performance?.analysis?.recordsProcessed ??
		0;
	const processedLabel =
		performance?.analysis?.pagesProcessed !== undefined
			? __('Pages Processed', 'asneris-seo-toolkit')
			: __('Records Processed', 'asneris-seo-toolkit');
	const popupStatusLabel = statusLabel || __('N/A', 'asneris-seo-toolkit');

	return (
		<>
			<div className={ wrapperClassName }>
				<div className="ASNERISSEO-react-404-bulk-row" style={ { justifyContent: 'space-between', alignItems: 'center', gap: '12px' } }>
					<p className="ASNERISSEO-react-note-box-title ASNERISSEO-react-mb-0">{ title }</p>
					<div className="ASNERISSEO-react-404-bulk-row-right" style={ { alignItems: 'center' } }>
						{ statusLabel ? (
							<span className={ `ASNERISSEO-react-status-chip ${ statusClassName || 'is-neutral' }` }>{ statusLabel }</span>
						) : null }
						<button
							type="button"
							className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-small"
							onClick={ () => setIsPopupOpen(true) }
							aria-label={ __( 'Open performance details', 'asneris-seo-toolkit' ) }
						>
							<span className="dashicons dashicons-visibility" aria-hidden="true" style={ { fontSize: '14px', width: '14px', height: '14px' } } />
						</button>
					</div>
				</div>
				{ advisoryMessage ? (
					<p className="ASNERISSEO-react-text-danger ASNERISSEO-react-mb-0 ASNERISSEO-react-mt-10">{ advisoryMessage }</p>
				) : null }
			</div>

			<div
				className={ `ASNERISSEO-modal-overlay${ isPopupOpen ? ' active' : '' }` }
				onClick={ (event) => {
					if (event.target === event.currentTarget) {
						setIsPopupOpen(false);
					}
				} }
			>
				{ isPopupOpen ? (
					<div className="ASNERISSEO-modal ASNERISSEO-modal-large ASNERISSEO-react-performance-modal" role="dialog" aria-modal="true" aria-label={ modalTitle || __( 'Performance details', 'asneris-seo-toolkit' ) }>
						<div className="ASNERISSEO-modal-header ASNERISSEO-modal-header-standard">
							<h3 className="ASNERISSEO-modal-title">{ modalTitle || __( 'Performance details', 'asneris-seo-toolkit' ) }</h3>
							<button type="button" className="ASNERISSEO-modal-close ASNERISSEO-modal-close-light" onClick={ () => setIsPopupOpen(false) }>
								&times;
							</button>
						</div>
						<div className="ASNERISSEO-modal-content">
							{ groups.length > 0 ? (
								<div className="ASNERISSEO-react-performance-popup">
									<div className="ASNERISSEO-react-performance-summary-grid">
										<div className="ASNERISSEO-react-performance-summary-card is-run">
											<div className="ASNERISSEO-react-performance-summary-title">{ __('Run Performance', 'asneris-seo-toolkit') }</div>
											<div className="ASNERISSEO-react-performance-run-row">
												<span className={ `ASNERISSEO-react-status-chip ${ statusClassName || 'is-neutral' }` }>{ popupStatusLabel }</span>
												<span className="dashicons dashicons-chart-line ASNERISSEO-react-performance-summary-icon" aria-hidden="true" />
											</div>
										</div>

										<div className="ASNERISSEO-react-performance-summary-card">
											<div className="ASNERISSEO-react-performance-summary-title">{ __('Execution Time', 'asneris-seo-toolkit') }</div>
											<div className="ASNERISSEO-react-performance-summary-value-row is-blue">
												<span className="dashicons dashicons-clock ASNERISSEO-react-performance-summary-icon" aria-hidden="true" />
												<strong>{ formatDurationMs(performance?.timing?.executionMs) }</strong>
											</div>
										</div>

										<div className="ASNERISSEO-react-performance-summary-card">
											<div className="ASNERISSEO-react-performance-summary-title">{ __('Peak Memory', 'asneris-seo-toolkit') }</div>
											<div className="ASNERISSEO-react-performance-summary-value-row is-purple">
												<span className="dashicons dashicons-database-view ASNERISSEO-react-performance-summary-icon" aria-hidden="true" />
												<strong>{ formatBytesToMb(performance?.memory?.peakBytes) }</strong>
											</div>
										</div>

										<div className="ASNERISSEO-react-performance-summary-card">
											<div className="ASNERISSEO-react-performance-summary-title">{ __('Database Queries', 'asneris-seo-toolkit') }</div>
											<div className="ASNERISSEO-react-performance-summary-value-row is-cyan">
												<span className="dashicons dashicons-database ASNERISSEO-react-performance-summary-icon" aria-hidden="true" />
												<strong>{ formatCount(performance?.database?.queryDelta) }</strong>
											</div>
										</div>

										<div className="ASNERISSEO-react-performance-summary-card">
											<div className="ASNERISSEO-react-performance-summary-title">{ processedLabel }</div>
											<div className="ASNERISSEO-react-performance-summary-value-row is-orange">
												<span className="dashicons dashicons-media-document ASNERISSEO-react-performance-summary-icon" aria-hidden="true" />
												<strong>{ formatCount(processedCount) }</strong>
											</div>
										</div>

										<div className="ASNERISSEO-react-performance-summary-card">
											<div className="ASNERISSEO-react-performance-summary-title">{ __('Rules Evaluated', 'asneris-seo-toolkit') }</div>
											<div className="ASNERISSEO-react-performance-summary-value-row is-violet">
												<span className="dashicons dashicons-yes-alt ASNERISSEO-react-performance-summary-icon" aria-hidden="true" />
												<strong>{ formatCount(rulesEvaluated) }</strong>
											</div>
										</div>
									</div>

									<div className="ASNERISSEO-react-performance-details-shell">
										<div className="ASNERISSEO-react-performance-details-header">
											<h4>{ __('Performance Details', 'asneris-seo-toolkit') }</h4>
											<button
												type="button"
												className="ASNERISSEO-react-performance-details-toggle"
												onClick={ () => setShowPopupDetails((current) => !current) }
											>
												{ showPopupDetails ? __('Hide Details', 'asneris-seo-toolkit') : __('Show Details', 'asneris-seo-toolkit') }
												<span className={ `dashicons ${ showPopupDetails ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2' }` } aria-hidden="true" />
											</button>
										</div>

										{ showPopupDetails ? (
											<div className="ASNERISSEO-react-performance-details-columns">
												{ groups.map((group) => (
													<section key={ group.key } className="ASNERISSEO-react-performance-details-group">
														<div className="ASNERISSEO-react-performance-details-group-title">
															<span className={ `dashicons ${ getGroupIconClass(group.key) }` } aria-hidden="true" />
															<strong>{ group.title }</strong>
														</div>
														<div className="ASNERISSEO-react-performance-details-rows">
															{ group.rows.map((row) => (
																<div key={ `${ group.key }-${ row.label }` } className="ASNERISSEO-react-performance-details-row">
																	<span>{ row.label }</span>
																	<span className={ row.tone || '' }>{ row.value }</span>
																</div>
															)) }
														</div>
													</section>
												)) }
											</div>
										) : null }
									</div>
								</div>
							) : (
								<div className="ASNERISSEO-react-table-wrap">
									<table className="ASNERISSEO-react-status-table">
										<tbody>
											{ rows.map((row) => (
												<tr key={ row.key || row.label }>
													<th scope="row">{ row.label }</th>
													<td>{ row.value }</td>
												</tr>
											)) }
										</tbody>
									</table>
								</div>
							) }
						</div>
					</div>
				) : null }
			</div>
		</>
	);
};

export default PerformanceTrackerCard;
