import { __ } from '@wordpress/i18n';

const SearchAppearanceViewportToggle = ({
	isMobileViewport,
	isSearchAppearanceMobile,
	onViewportChange,
}) => {
	if (isMobileViewport) {
		return null;
	}

	return (
		<div className="ASNERISSEO-react-sa-preview-viewport-toggle">
			<button
				type="button"
				className={ `button ASNERISSEO-react-button ASNERISSEO-react-button-small ASNERISSEO-react-sa-preview-viewport-button${ !isSearchAppearanceMobile ? ' is-active' : '' }` }
				onClick={ () => onViewportChange?.('desktop') }
			>
				{ __('Desktop', 'asneris-seo-toolkit') }
			</button>
			<button
				type="button"
				className={ `button ASNERISSEO-react-button ASNERISSEO-react-button-small ASNERISSEO-react-sa-preview-viewport-button${ isSearchAppearanceMobile ? ' is-active' : '' }` }
				onClick={ () => onViewportChange?.('mobile') }
			>
				{ __('Mobile', 'asneris-seo-toolkit') }
			</button>
		</div>
	);
};

export default SearchAppearanceViewportToggle;
