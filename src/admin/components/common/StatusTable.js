import { Fragment, useEffect, useState } from '@wordpress/element';

const getStatusTone = (value) => {
	const normalized = String(value || '').trim().toLowerCase();

	if (!normalized) {
		return null;
	}

	if (/^(waiting|pending|running|loading|queued)$/.test(normalized)) {
		return 'waiting';
	}

	if (/^(fail|failed|failure|error|conflict|issue|noindex|missing)$/.test(normalized)) {
		return 'fail';
	}

	if (/^(success|pass|ok|enabled|found|indexable)$/.test(normalized)) {
		return 'success';
	}

	if (/^(warning|warn|review)$/.test(normalized)) {
		return 'warning';
	}

	if (/^(info|information|unknown|n\/a|na)$/.test(normalized)) {
		return 'neutral';
	}

	return null;
};

const isStatusColumn = (column) => {
	const key = String(column?.key || '').toLowerCase();
	const label = String(column?.label || '').toLowerCase();

	return key === 'status' || label === 'status';
};

const renderCellContent = (cell, column) => {
	if (!isStatusColumn(column) || typeof cell !== 'string') {
		return cell ?? <Fragment>-</Fragment>;
	}

	const tone = getStatusTone(cell);
	if (!tone) {
		return cell;
	}

	return (
		<span className={ `ASNERISSEO-react-status-chip is-${ tone }` }>{ cell }</span>
	);
};

const StatusTable = ({
	columns = [],
	rows = [],
	emptyMessage = '-',
	wrapClassName = '',
	tableClassName = '',
	mobileAccordion = false,
}) => {
	const [expandedRowKey, setExpandedRowKey] = useState(null);

	useEffect(() => {
		if (!mobileAccordion) {
			return;
		}

		setExpandedRowKey(null);
	}, [rows, mobileAccordion]);

	const tableClasses = [
		'ASNERISSEO-react-status-table',
		tableClassName,
		mobileAccordion ? 'ASNERISSEO-react-status-table-mobile-accordion' : '',
	]
		.filter(Boolean)
		.join(' ');
	const wrapClasses = [
		'ASNERISSEO-react-table-wrap',
		wrapClassName,
	]
		.filter(Boolean)
		.join(' ');

	return (
		<div className={ wrapClasses }>
			<table className={ tableClasses }>
				{ columns.length > 0 ? (
					<colgroup>
						{ columns.map((column, index) => (
							<col key={ column?.key || index } style={ column?.width ? { width: column.width } : undefined } />
						)) }
					</colgroup>
				) : null }
				<thead>
					<tr>
						{ columns.map((column, index) => (
							<th
								key={ column?.key || index }
								className={ column?.align === 'center' ? 'is-center' : '' }
							>
								{ column?.label || '-' }
							</th>
						)) }
					</tr>
				</thead>
				<tbody>
					{ rows.length > 0 ? rows.map((row, rowIndex) => {
						const rowKey = row?.key || rowIndex;
						const isExpanded = mobileAccordion && expandedRowKey === rowKey;
						const rowAccordionTitle = String(row?.mobileAccordionTitle || `${ rowIndex + 1 }`).trim() || `${ rowIndex + 1 }`;

						return (
							<tr key={ rowKey } className={ mobileAccordion && isExpanded ? 'is-mobile-expanded' : '' }>
								{ mobileAccordion ? (
									<td className="ASNERISSEO-react-status-table-card-toggle-cell" colSpan={ Math.max(columns.length, 1) }>
										<button
											type="button"
											className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-status-table-card-toggle"
											onClick={ () => setExpandedRowKey((current) => (current === rowKey ? null : rowKey)) }
											aria-expanded={ isExpanded }
										>
											<span className="ASNERISSEO-react-status-table-card-toggle-title">{ rowAccordionTitle }</span>
											<span className="ASNERISSEO-react-status-table-card-toggle-state">{ isExpanded ? 'Collapse' : 'Expand' }</span>
										</button>
									</td>
								) : null }
								{ (row?.cells || []).map((cell, cellIndex) => (
									<td
										key={ `${ rowKey }-${ cellIndex }` }
										className={ `${ columns[cellIndex]?.align === 'center' ? 'is-center' : '' }${ mobileAccordion ? ' ASNERISSEO-react-status-table-card-body-cell' : '' }`.trim() }
										data-label={ columns[cellIndex]?.label || '-' }
									>
										{ renderCellContent(cell, columns[cellIndex]) }
									</td>
								)) }
							</tr>
						);
					}) : (
						<tr>
							<td colSpan={ Math.max(columns.length, 1) }>{ emptyMessage }</td>
						</tr>
					) }
				</tbody>
			</table>
		</div>
	);
};

export default StatusTable;
