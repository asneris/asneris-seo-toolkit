import { Tooltip } from '@wordpress/components';

const HelpTooltip = ( { text } ) => {
	if ( ! text ) {
		return null;
	}

	return (
		<Tooltip text={ text }>
			<span
				style={ {
					display: 'inline-flex',
					alignItems: 'center',
					justifyContent: 'center',
					width: '16px',
					height: '16px',
					borderRadius: '50%',
					background: '#eef2f6',
					color: '#3858a5',
					fontSize: '11px',
					fontWeight: '700',
					marginLeft: '6px',
					cursor: 'help',
				} }
			>
				i
			</span>
		</Tooltip>
	);
};

export default HelpTooltip;
