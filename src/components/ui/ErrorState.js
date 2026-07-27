import AlertMessage from './AlertMessage';

const ErrorState = ( { message } ) => {
	if ( ! message ) {
		return null;
	}

	return <AlertMessage tone="error">{ message }</AlertMessage>;
};

export default ErrorState;
