import { __ } from '@wordpress/i18n';

const DetailSectionSummaryContainer = ({
	title,
	note,
	noteTone = 'warning',
	rows,
	children,
	showEmptyState = true,
	emptyMessage = __('No checks available for this section.', 'asneris-seo-toolkit'),
}) => {
	return (
		<div className="ASNERISSEO-react-tab-card ASNERISSEO-react-detail-section-body">
			<div className="ASNERISSEO-react-tab-card-header">
				<h4>{ title }</h4>
			</div>
			{ rows && rows.length > 0 ? children : (showEmptyState ? <p className="ASNERISSEO-react-muted">{ emptyMessage }</p> : null) }
			{ note ? (
				<div className={ `ASNERISSEO-react-note-box ${ noteTone === 'success' ? 'is-success' : 'is-warning' }` }>
					<p className={ `ASNERISSEO-react-note-box-title ${ noteTone === 'success' ? 'is-success' : 'is-warning' }` }>{ note }</p>
				</div>
			) : null }
		</div>
	);
};

export default DetailSectionSummaryContainer;
