import { __ } from '@wordpress/i18n';

const HistoryTimelineContainer = ({
	title,
	isLoading,
	rows,
	columns,
	StatusTableComponent,
	emptyMessage = __('No snapshot history available for this tab yet.', 'asneris-seo-toolkit'),
	children,
}) => {
	return (
		<div className="ASNERISSEO-react-mobile-hide">
			<h4 className="ASNERISSEO-react-overview-heading">{ title }</h4>
			{ isLoading ? <p>{ __('Loading history...', 'asneris-seo-toolkit') }</p> : null }
			{ children }
			<StatusTableComponent
				wrapClassName="ASNERISSEO-react-detail-issues-scroll"
				columns={ columns }
				rows={ rows }
				emptyMessage={ emptyMessage }
			/>
		</div>
	);
};

export default HistoryTimelineContainer;
