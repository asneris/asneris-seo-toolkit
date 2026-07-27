import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { PanelHeaderBadge, SectionBox } from '../../components/ui';
import SidebarSectionShell from './SidebarSectionShell';

const SchemaPanel = ( {
	expandedSection,
	onExpand,
	schemaEnabled,
	onSchemaToggle,
	RobotsControlComponent,
} ) => {
	const RobotsControl = RobotsControlComponent;

	return (
		<SidebarSectionShell
			sectionKey="schema"
			expandedSection={ expandedSection }
			onExpand={ onExpand }
			title={ __( 'Schema (Structured Data)', 'asneris-seo-toolkit' ) }
			headerDescription={ __( 'Help search engines understand your content', 'asneris-seo-toolkit' ) }
			headerIcon="dashicons dashicons-editor-code"
			headerIconStyle={ { background: '#ecf8f0', color: '#2f9e44' } }
			initialOpen={ false }
			headerAction={
				<PanelHeaderBadge
					label={
						schemaEnabled
							? __( 'Enabled', 'asneris-seo-toolkit' )
							: __( 'Disabled', 'asneris-seo-toolkit' )
					}
					tone={ schemaEnabled ? 'success' : 'neutral' }
				/>
			}
		>
			<SectionBox>
				<ToggleControl
					label={ __( 'Enable Schema', 'asneris-seo-toolkit' ) }
					checked={ schemaEnabled }
					onChange={ onSchemaToggle }
					help="Adds structured data markup for better search results"
				/>
			</SectionBox>

			{ schemaEnabled && (
				<SectionBox>
					<RobotsControl
						label={ __( 'Schema Type', 'asneris-seo-toolkit' ) }
						metaKey="_ASNERISSEO_schema_type"
						options={ [
							{
								label: 'Auto-detect (recommended)',
								value: '',
							},
							{
								label: 'Article / Blog Post',
								value: 'Article',
							},
							{
								label: 'News Article',
								value: 'NewsArticle',
							},
							{
								label: 'Blog Posting',
								value: 'BlogPosting',
							},
							{ label: 'Web Page', value: 'WebPage' },
							{ label: 'Product', value: 'Product' },
							{ label: 'Event', value: 'Event' },
							{ label: 'Recipe', value: 'Recipe' },
							{ label: 'FAQ Page', value: 'FAQPage' },
							{ label: 'How-To', value: 'HowTo' },
							] 
						}
					/>
				</SectionBox>
			) }
		</SidebarSectionShell>
	);
};

export default SchemaPanel;
