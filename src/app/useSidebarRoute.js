import { useState, useEffect, useCallback } from '@wordpress/element';

const STORAGE_KEY = 'asneris-seo-sidebar-route';

const isValidRoute = ( value ) => {
	return [
		'overview',
		'diagnostics',
		'appearance',
		'social',
		'readiness',
		'ai',
	].includes(
		value
	);
};

const getInitialRoute = ( fallbackRoute ) => {
	const fromStorage = sessionStorage.getItem( STORAGE_KEY );
	if ( isValidRoute( fromStorage ) ) {
		return fromStorage;
	}

	return fallbackRoute;
};

const useSidebarRoute = ( fallbackRoute = 'overview' ) => {
	const [ activeRoute, setActiveRoute ] = useState( () =>
		getInitialRoute( fallbackRoute )
	);

	useEffect( () => {
		sessionStorage.setItem( STORAGE_KEY, activeRoute );
	}, [ activeRoute ] );

	const navigate = useCallback( ( route ) => {
		if ( isValidRoute( route ) ) {
			setActiveRoute( route );
		}
	}, [] );

	return {
		activeRoute,
		navigate,
	};
};

export default useSidebarRoute;
