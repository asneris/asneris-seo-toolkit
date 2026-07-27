import { Notice } from '@wordpress/components';

const AlertMessage = ( { tone = 'info', children } ) => {
	void tone;

	return (
		<Notice status="info" isDismissible={ false }>
			{ children }
		</Notice>
	);
};

export default AlertMessage;
