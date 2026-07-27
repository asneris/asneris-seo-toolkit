import { __ } from '@wordpress/i18n';
import { PanelHeaderBadge } from '../../components/ui';
import SidebarSectionShell from './SidebarSectionShell';

const DiagnosticsPanel = ( {
	activeRoute,
	expandedSection,
	onExpand,
	DiagnosticsPreviewComponent,
} ) => {
	const DiagnosticsPreview = DiagnosticsPreviewComponent;

	return (
		<SidebarSectionShell
			sectionKey="diagnostics"
			expandedSection={ expandedSection }
			onExpand={ onExpand }
			title={ __( 'Live Diagnostics', 'asneris-seo-toolkit' ) }
			headerDescription={ __( 'Technical issues that need immediate attention', 'asneris-seo-toolkit' ) }
			headerIcon="dashicons dashicons-chart-line"
			headerIconStyle={ { background: '#ecf8f0', color: '#22a35a' } }
			initialOpen={ activeRoute === 'diagnostics' }
			headerAction={ <PanelHeaderBadge preset="live" /> }
		>
			<DiagnosticsPreview />
		</SidebarSectionShell>
	);
};

export default DiagnosticsPanel;
