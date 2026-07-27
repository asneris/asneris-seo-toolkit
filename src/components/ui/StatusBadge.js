const toneMap = {
	success: {
		background: '#edfaef',
		color: '#1f7a1f',
		border: '#90d79b',
	},
	warning: {
		background: '#fff8e5',
		color: '#8a5800',
		border: '#f0b849',
	},
	error: {
		background: '#fdf0f0',
		color: '#8f2424',
		border: '#f0a4a4',
	},
	info: {
		background: '#edf5ff',
		color: '#1b5c9d',
		border: '#a9c7e8',
	},
	neutral: {
		background: '#f6f7f7',
		color: '#50575e',
		border: '#dcdcde',
	},
};

const StatusBadge = ( { label, tone = 'neutral' } ) => {
	const palette = toneMap[ tone ] || toneMap.neutral;

	return (
		<span
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				padding: '2px 8px',
				borderRadius: '999px',
				border: `1px solid ${ palette.border }`,
				background: palette.background,
				color: palette.color,
				fontSize: '11px',
				fontWeight: '600',
			} }
		>
			{ label }
		</span>
	);
};

export default StatusBadge;
