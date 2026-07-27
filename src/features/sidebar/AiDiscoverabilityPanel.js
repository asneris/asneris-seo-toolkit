import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { PanelHeaderBadge, SectionBox } from '../../components/ui';
import SidebarSectionShell from './SidebarSectionShell';
import {
	evaluateAiDiscoverability,
	EVALUATOR_VERSION,
	getCheckTone,
	getScoreTone,
} from './evaluatorUtils';

const AiDiscoverabilityPanel = ( { activeRoute, expandedSection, onExpand } ) => {
	const postContent = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostContent()
	);
	const siteName = useSelect( () => window.asnerisseoData?.siteName || '' );

	const result = evaluateAiDiscoverability( {
		content: postContent,
		siteName,
		origin: window.location.origin,
	} );

	const scoreTone = getScoreTone( result.score );

	return (
		<SidebarSectionShell
			sectionKey="ai"
			expandedSection={ expandedSection }
			onExpand={ onExpand }
			title={ __( 'AI Discoverability', 'asneris-seo-toolkit' ) }
			headerDescription={ __( 'Optimize for AI platforms and LLMs', 'asneris-seo-toolkit' ) }
			headerIcon="dashicons dashicons-superhero-alt"
			headerIconStyle={ { background: '#f5efff', color: '#7e3af2' } }
			initialOpen={ activeRoute === 'ai' }
			headerAction={
				<PanelHeaderBadge
					label={ `${ result.score }/100` }
					tone={ scoreTone }
				/>
			}
		>
			<SectionBox>
				<div
					style={ {
						display: 'flex',
						justifyContent: 'space-between',
						alignItems: 'center',
						marginBottom: '8px',
					} }
				>
					<strong>{ __( 'Discoverability score', 'asneris-seo-toolkit' ) }</strong>
					<PanelHeaderBadge
						label={
							scoreTone === 'success'
								? __( 'Strong', 'asneris-seo-toolkit' )
								: __( 'Improve', 'asneris-seo-toolkit' )
						}
						tone={ scoreTone }
					/>
				</div>
				<div
					style={ {
						height: '8px',
						background: '#e2e4e7',
						borderRadius: '999px',
						overflow: 'hidden',
						marginBottom: '10px',
					} }
				>
					<div
						style={ {
							height: '100%',
							width: `${ result.score }%`,
							background:
								scoreTone === 'success'
									? '#46b450'
									: scoreTone === 'warning'
									? '#dba617'
									: '#d63638',
							transition: 'width 0.2s ease',
						} }
					/>
				</div>
				<div style={ { display: 'grid', gap: '6px', fontSize: 'var(--asneris-body-size)', color: '#50575e' } }>
					<div>{ __( 'Word count', 'asneris-seo-toolkit' ) }: { result.stats.wordCount }</div>
					<div>{ __( 'Headings', 'asneris-seo-toolkit' ) }: { result.stats.headingCount }</div>
					<div>{ __( 'Internal links', 'asneris-seo-toolkit' ) }: { result.stats.internalLinkCount }</div>
					<div>{ __( 'Images', 'asneris-seo-toolkit' ) }: { result.stats.imageCount }</div>
					<div>{ __( 'Avg sentence length', 'asneris-seo-toolkit' ) }: { result.stats.avgSentenceLength }</div>
					<div>
						{ __( 'Primary keyword', 'asneris-seo-toolkit' ) }:{ ' ' }
						{ result.stats.primaryKeyword || __( 'Not detected', 'asneris-seo-toolkit' ) }
					</div>
					<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#787c82' } }>
						{ __( 'Scoring model', 'asneris-seo-toolkit' ) }: { `shared evaluator ${ EVALUATOR_VERSION }` }
					</div>
				</div>
			</SectionBox>

			{ result.warnings.length > 0 && (
				<SectionBox>
					<strong>{ __( 'Top AI recommendations', 'asneris-seo-toolkit' ) }</strong>
					<ul style={ { margin: '8px 0 0 18px' } }>
						{ result.warnings.slice( 0, 5 ).map( ( warning ) => (
							<li key={ warning.label } style={ { marginBottom: '4px', fontSize: 'var(--asneris-helper-size)' } }>
								{ warning.detail }
							</li>
						) ) }
					</ul>
				</SectionBox>
			) }

			<SectionBox>
				<strong>{ __( 'AI checks', 'asneris-seo-toolkit' ) }</strong>
				<div style={ { display: 'grid', gap: '8px', marginTop: '8px' } }>
					{ result.checks.map( ( check ) => (
						<div
							key={ check.label }
							style={ {
								border: '1px solid var(--asneris-cyan, #4EB8C5)',
								borderRadius: '6px',
								padding: '8px 10px',
								background: '#fff',
							} }
						>
							<div
								style={ {
									display: 'flex',
									justifyContent: 'space-between',
									alignItems: 'center',
									marginBottom: '4px',
								} }
							>
								<span style={ { fontWeight: 'var(--asneris-h3-weight)' } }>{ check.label }</span>
								<PanelHeaderBadge
									label={
										check.status === 'pass'
											? __( 'Pass', 'asneris-seo-toolkit' )
											: __( 'Warning', 'asneris-seo-toolkit' )
									}
									tone={ getCheckTone( check.status ) }
								/>
							</div>
							<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#50575e' } }>{ check.detail }</div>
						</div>
					) ) }
				</div>
			</SectionBox>
		</SidebarSectionShell>
	);
};

export default AiDiscoverabilityPanel;

