const metricCardStyle = {
	background: '#fff',
	border: '1px solid #d1d9e0',
	borderRadius: '12px',
	padding: '18px 16px',
	boxShadow: '0 8px 20px rgba(7, 28, 52, 0.06)',
	minHeight: '108px',
};

const MetricCard = ({ label, value, hint, iconClass = '', progressPercent = null, variant = '', hintClassName = '' }) => {
	const variantClass = variant ? ` ASNERISSEO-react-metric-card-${ variant }` : '';
	const percentValue = typeof progressPercent === 'number' ? Math.max(0, Math.min(100, progressPercent)) : null;
	const hintClass = hintClassName ? ` ASNERISSEO-react-metric-hint-custom ${ hintClassName }` : '';

	return (
		<div style={ metricCardStyle } className={ `ASNERISSEO-react-metric-card${ variantClass}` }>
			<div className="ASNERISSEO-react-metric-main">
				{ iconClass ? (
					<span className="ASNERISSEO-react-metric-icon-wrap" aria-hidden="true">
						<span className={ `dashicons ${ iconClass } ASNERISSEO-react-metric-icon` } />
					</span>
				) : null }
				<div className="ASNERISSEO-react-metric-copy">
					<div className="ASNERISSEO-react-metric-label">{ label }</div>
					<div className="ASNERISSEO-react-metric-value">{ value }</div>
					{ hint ? <div className={ `ASNERISSEO-react-metric-hint${ hintClass }` }>{ hint }</div> : null }
				</div>
			</div>
			{ percentValue !== null ? (
				<div className="ASNERISSEO-react-metric-progress" role="progressbar" aria-valuemin={ 0 } aria-valuemax={ 100 } aria-valuenow={ percentValue } aria-label={ label }>
					<span className="ASNERISSEO-react-metric-progress-fill" style={ { width: `${ percentValue }%` } } />
				</div>
			) : null }
		</div>
	);
};

export default MetricCard;
