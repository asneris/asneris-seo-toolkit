import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const LoadingState = ( { label } ) => {
	return (
		<div
			style={ {
				display: 'flex',
				alignItems: 'center',
				gap: '8px',
				color: '#50575e',
				padding: '8px 0',
			} }
		>
			<Spinner />
			<span>{ label || __( 'Loading…', 'asneris-seo-toolkit' ) }</span>
		</div>
	);
};

export default LoadingState;
