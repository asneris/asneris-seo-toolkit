import { __ } from '@wordpress/i18n';

const AsnerisCard = ( { title, action, children, footer } ) => {
	return (
		<div
			style={ {
				padding: '16px',
				background: 'var(--asneris-white, #ffffff)',
				borderRadius: '6px',
				marginBottom: '6px',
				border: '1px solid var(--asneris-cyan, #4EB8C5)',
				color: 'var(--asneris-text, #000000)'
			} }
		>
			{ ( title || action ) && (
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'space-between',
						marginBottom: '8px',
					} }
				>
					<strong>
						{ title || __( 'Card', 'asneris-seo-toolkit' ) }
					</strong>
					{ action }
				</div>
			) }

			{ children }

			{ footer && <div style={ { marginTop: '12px' } }>{ footer }</div> }
		</div>
	);
};

export default AsnerisCard;
