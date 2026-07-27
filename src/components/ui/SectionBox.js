const SectionBox = ( { children } ) => {
	return (
		<div
			style={ {
				padding: '10px',
				border: '1px solid var(--asneris-cyan, #4EB8C5)',
				borderRadius: '6px',
				background: '#fff',
				fontFamily: 'var(--asneris-font-body)',
			} }
		>
			{ children }
		</div>
	);
};

export default SectionBox;
