const ScoreCircle = ({ scoreLabel, tone = 'neutral', className = '' }) => {
	return (
		<span className={ `ASNERISSEO-react-score-pill is-${ tone } ${ className }`.trim() }>{ scoreLabel }</span>
	);
};

export default ScoreCircle;
