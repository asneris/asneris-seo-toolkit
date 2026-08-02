import { __ } from '@wordpress/i18n';
import SearchAppearanceViewportToggle from './SearchAppearanceViewportToggle';
import SearchAppearanceSourceList from './SearchAppearanceSourceList';

const SearchAppearancePreview = ({
	isMobileViewport,
	isSearchAppearanceMobile,
	activeSearchAppearanceVisual,
	hasSocialImageTemplate,
	socialImageFallbackTitle,
	searchAppearanceSourceRows,
	onViewportChange,
}) => {
	return (
		<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mt-10">
			<div className="ASNERISSEO-react-sa-preview-header">
				<p className="ASNERISSEO-react-note-box-title ASNERISSEO-react-mb-0">{ __('Search Appearance Preview', 'asneris-seo-toolkit') }</p>
				<SearchAppearanceViewportToggle
					isMobileViewport={ isMobileViewport }
					isSearchAppearanceMobile={ isSearchAppearanceMobile }
					onViewportChange={ onViewportChange }
				/>
			</div>
			<p className="ASNERISSEO-react-muted ASNERISSEO-react-sa-preview-section-label">{ __('Search Engine Preview', 'asneris-seo-toolkit') }</p>
			<div className="ASNERISSEO-react-sa-preview-center ASNERISSEO-react-sa-preview-center-google">
				<div className={ `ASNERISSEO-react-sa-preview-frame ASNERISSEO-react-sa-preview-frame-google${ isSearchAppearanceMobile ? ' is-mobile' : ' is-desktop' }` }>
					{ activeSearchAppearanceVisual?.url ? <p className="ASNERISSEO-react-sa-preview-url">{ activeSearchAppearanceVisual.url }</p> : null }
					<p className="ASNERISSEO-react-sa-preview-title">{ activeSearchAppearanceVisual?.googleTitle || '-' }</p>
					<p className="ASNERISSEO-react-sa-preview-description">{ activeSearchAppearanceVisual?.googleDescription || '-' }</p>
					<div className="ASNERISSEO-react-sa-preview-status">{ __('Preview generated from diagnostics', 'asneris-seo-toolkit') }</div>
				</div>
			</div>
			<p className="ASNERISSEO-react-muted ASNERISSEO-react-sa-preview-section-label">{ __('Social Media', 'asneris-seo-toolkit') }</p>
			<div className="ASNERISSEO-react-sa-preview-center ASNERISSEO-react-sa-preview-center-social">
				<div className={ `ASNERISSEO-react-sa-preview-frame ASNERISSEO-react-sa-preview-frame-social${ isSearchAppearanceMobile ? ' is-mobile' : ' is-desktop' }` }>
					<div className={ `ASNERISSEO-react-sa-preview-media${ isSearchAppearanceMobile ? ' is-mobile' : ' is-desktop' }${ activeSearchAppearanceVisual?.socialImage ? ' has-image' : ' no-image' }` }>
						{ activeSearchAppearanceVisual?.socialImage ? (
							<img
								src={ activeSearchAppearanceVisual.socialImage }
								alt={ __('Social image preview', 'asneris-seo-toolkit') }
								className="ASNERISSEO-react-sa-preview-social-image"
							/>
						) : hasSocialImageTemplate ? (
							<div className="ASNERISSEO-react-sa-preview-fallback-title">
								{ socialImageFallbackTitle }
							</div>
						) : (
							<div className="ASNERISSEO-react-sa-preview-fallback-text">
								{ __('Social image unavailable', 'asneris-seo-toolkit') }
							</div>
						) }
					</div>
					<div className="ASNERISSEO-react-sa-preview-social-body">
						<p className="ASNERISSEO-react-sa-preview-social-title">{ activeSearchAppearanceVisual?.socialTitle || activeSearchAppearanceVisual?.googleTitle || '-' }</p>
						<p className="ASNERISSEO-react-sa-preview-social-description">{ activeSearchAppearanceVisual?.socialDescription || activeSearchAppearanceVisual?.googleDescription || '-' }</p>
					</div>
				</div>
			</div>
			<SearchAppearanceSourceList rows={ searchAppearanceSourceRows } />
		</div>
	);
};

export default SearchAppearancePreview;
