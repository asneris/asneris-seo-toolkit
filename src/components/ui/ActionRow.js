const ActionRow = ( { children, wrap = true, marginBottom = '12px' } ) => {
	return (
		<div
			style={ {
				display: 'flex',
				gap: '8px',
				marginBottom,
				flexWrap: wrap ? 'wrap' : 'nowrap',
			} }
		>
			{ children }
		</div>
	);
};

export default ActionRow;
