import { __ } from '@wordpress/i18n';
import ScoreBadge from './ScoreBadge';

const DetailSectionCard = ({
	title,
	subtitle,
	statusLabel,
	statusTone = 'warning',
	scoreLabel,
	scoreTone = 'neutral',
	summary,
	scoreMessage,
	children,
	headersRight,
	bodyClassName = '',
	showSummary = true,
}) => {
	return (
		<div className="ASNERISSEO-react-tab-card">
			<div className="ASNERISSEO-react-tab-card-header">
				<div>
					<h4 className="ASNERISSEO-react-mb-0">{ title }</h4>
					{ subtitle ? <div className="ASNERISSEO-react-muted">{ subtitle }</div> : null }
				</div>
				<div className="ASNERISSEO-react-detail-section-card-actions">
					{ headersRight }
					{ statusLabel ? <span className={ `ASNERISSEO-react-status-chip is-${ statusTone }` }>{ statusLabel }</span> : null }
				</div>
			</div>
			{ scoreLabel ? (
				<div className="ASNERISSEO-react-tab-card-score">
					<ScoreBadge scoreLabel={ scoreLabel } tone={ scoreTone } />
				</div>
			) : null }
			{ showSummary && summary ? <p className="ASNERISSEO-react-muted">{ summary }</p> : null }
			{ scoreMessage ? <p className="ASNERISSEO-react-text-danger ASNERISSEO-react-mb-0">{ scoreMessage }</p> : null }
			<div className={ bodyClassName }>{ children }</div>
		</div>
	);
};

export default DetailSectionCard;
