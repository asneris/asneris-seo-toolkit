import { __ } from '@wordpress/i18n';
import { PanelHeaderBadge } from '../../components/ui';
import SidebarSectionShell from './SidebarSectionShell';

const IndexNowPanel = ( { expandedSection, onExpand, IndexNowSubmitComponent } ) => {
	const IndexNowSubmit = IndexNowSubmitComponent;

	return (
		<SidebarSectionShell
			sectionKey="indexNow"
			expandedSection={ expandedSection }
			onExpand={ onExpand }
			title={ __( 'IndexNow', 'asneris-seo-toolkit' ) }
			headerDescription={ __( 'Instant indexing for supported search engines', 'asneris-seo-toolkit' ) }
			headerIcon="dashicons dashicons-controls-forward"
			headerIconStyle={ { background: '#f6f7f7', color: '#7a8694' } }
			initialOpen={ false }
			headerAction={ <PanelHeaderBadge preset="manualPing" /> }
		>
			<div
				style={ {
					padding: '10px 12px',
					border: '1px solid var(--asneris-cyan, #4EB8C5)',
					borderRadius: '6px',
					background: '#f8fbfd',
					fontSize: 'var(--asneris-helper-size)',
					color: '#2c3338',
					lineHeight: 'var(--asneris-body-line)',
				} }
			>
				<p style={ { margin: '0 0 8px 0', fontWeight: 'var(--asneris-h3-weight)' } }>
					{ __( 'Which search engines support IndexNow?', 'asneris-seo-toolkit' ) }
				</p>
				<ul style={ { margin: '0 0 8px 18px', padding: 0 } }>
					<li>{ __( 'Microsoft Bing', 'asneris-seo-toolkit' ) }</li>
					<li>{ __( 'Yandex', 'asneris-seo-toolkit' ) }</li>
					<li>{ __( 'Seznam.cz', 'asneris-seo-toolkit' ) }</li>
					<li>{ __( 'Several other participating search engines', 'asneris-seo-toolkit' ) }</li>
				</ul>
				<p style={ { margin: 0 } }>
					{ __( 'Google does not currently support IndexNow as an indexing protocol.', 'asneris-seo-toolkit' ) }
				</p>
			</div>

			<IndexNowSubmit />
		</SidebarSectionShell>
	);
};

export default IndexNowPanel;

