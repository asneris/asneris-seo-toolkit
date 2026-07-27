import { __ } from '@wordpress/i18n';
import { PanelHeaderBadge, SectionBox } from '../../components/ui';
import SidebarSectionShell from './SidebarSectionShell';

const RobotsMetaPanel = ( { expandedSection, onExpand, RobotsControlComponent } ) => {
	const RobotsControl = RobotsControlComponent;

	return (
		<SidebarSectionShell
			sectionKey="robots"
			expandedSection={ expandedSection }
			onExpand={ onExpand }
			title={ __( 'Robots Meta', 'asneris-seo-toolkit' ) }
			headerDescription={ __( 'Control how search engines crawl and index your site', 'asneris-seo-toolkit' ) }
			headerIcon="dashicons dashicons-admin-generic"
			headerIconStyle={ { background: '#f6f7f7', color: '#646970' } }
			initialOpen={ false }
			headerAction={ <PanelHeaderBadge preset="defaults" /> }
		>
			<SectionBox>
				<div style={ { marginBottom: '8px' } }>
					<PanelHeaderBadge
						label={ __( 'Purpose: control search result visibility', 'asneris-seo-toolkit' ) }
						tone="info"
					/>
				</div>
				<RobotsControl
					label={ __( 'Index', 'asneris-seo-toolkit' ) }
					metaKey="_ASNERISSEO_robots_index"
					options={ [
						{
							label: 'Index (allow search engines)',
							value: 'index',
						},
						{
							label: 'No Index (hide from search)',
							value: 'noindex',
						},
					] }
				/>
			</SectionBox>

			<SectionBox>
				<div style={ { marginBottom: '8px' } }>
					<PanelHeaderBadge
						label={ __( 'Purpose: control crawler link discovery', 'asneris-seo-toolkit' ) }
						tone="info"
					/>
				</div>
				<RobotsControl
					label={ __( 'Follow', 'asneris-seo-toolkit' ) }
					metaKey="_ASNERISSEO_robots_follow"
					options={ [
						{
							label: 'Follow (allow link following)',
							value: 'follow',
						},
						{
							label: 'No Follow (prevent link following)',
							value: 'nofollow',
						},
					] }
				/>
			</SectionBox>
		</SidebarSectionShell>
	);
};

export default RobotsMetaPanel;
