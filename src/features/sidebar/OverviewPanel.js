import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ActionRow } from '../../components/ui';
import SidebarSectionShell from './SidebarSectionShell';

const OverviewPanel = ( {
	activeRoute,
	navigate,
	onOpenAppearanceDialog,
	seoScore,
	useLocalScoreFallback = true,
	seoSuggestions = [],
	SEOScoreComponent,
} ) => {
	const SEOScore = SEOScoreComponent;
	const keepOverviewExpanded = () => {};

	const handleOpenAppearance = () => {
		if ( onOpenAppearanceDialog ) {
			onOpenAppearanceDialog();
			return;
		}

		navigate( 'appearance' );
	};

	return (
		<SidebarSectionShell
			sectionKey="overview"
			expandedSection="overview"
			onExpand={ keepOverviewExpanded }
			title={ __( 'SEO Overview', 'asneris-seo-toolkit' ) }
			headerDescription={ __( 'Quick snapshot of score and key SEO actions', 'asneris-seo-toolkit' ) }
			headerIcon="dashicons dashicons-dashboard"
			headerIconStyle={ { background: '#f6f7f7', color: '#7a8694' } }
			initialOpen={ activeRoute === 'overview' }
		>
			<SEOScore scoreOverride={ seoScore } useLocalFallback={ useLocalScoreFallback } suggestionsOverride={ seoSuggestions } />
			<ActionRow>
				<Button
					variant="primary"
					className="ASNERISSEO-react-button ASNERISSEO-react-button-primary"
					style={ { width: '100%' } }
					onClick={ handleOpenAppearance }
				>
					{ __( 'Edit Search Appearance', 'asneris-seo-toolkit' ) }
				</Button>
			</ActionRow>
		</SidebarSectionShell>
	);
};

export default OverviewPanel;
