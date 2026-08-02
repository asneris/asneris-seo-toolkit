import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { PanelHeaderBadge, SectionBox } from '../../components/ui';
import SidebarSectionShell from './SidebarSectionShell';
import {
	evaluateSeoReadiness,
	EVALUATOR_VERSION,
	getScoreTone,
} from './evaluatorUtils';
import { assertUnifiedData, getUnifiedComputed } from '../../app/unifiedDataModel';

const SeoReadinessPanel = ( { activeRoute, expandedSection, onExpand } ) => {
	const postTitle = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'title' )
	);
	const postExcerpt = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'excerpt' )
	);
	const postContent = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostContent()
	);
	const meta = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'meta' )
	);
	const currentPostId = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostId()
	);
	const permalink = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		return typeof editor.getPermalink === 'function' ? editor.getPermalink() : '';
	} );
	const isEditorDirty = useSelect( ( select ) =>
		select( 'core/editor' ).isEditedPostDirty()
	);
	const [ reportSeoScore, setReportSeoScore ] = useState( null );

	const readiness = evaluateSeoReadiness( {
		postTitle,
		postExcerpt,
		meta,
		content: postContent,
		origin: window.location.origin,
	} );

	useEffect( () => {
		if ( ! currentPostId ) {
			setReportSeoScore( null );
			return undefined;
		}

		const controller = new AbortController();
		const restRoot = String( window.asnerisseoData?.restRoot || '/wp-json/' );
		const restNamespace = String( window.asnerisseoData?.restNamespace || 'asneris-seo/v1' );
		const restBase = `${ restRoot.replace( /\/+$/, '' ) }/${ restNamespace.replace( /^\/+|\/+$/g, '' ) }`;
		const endpoint = isEditorDirty
			? `${ restBase }/page-diagnostics-v2/draft-policy`
			: `${ restBase }/page-diagnostics-v2/run/${ encodeURIComponent( String( currentPostId ) ) }?no_store=1`;
		const requestOptions = {
			method: 'POST',
			cache: 'no-store',
			headers: {
				'X-WP-Nonce': window.asnerisseoData?.restNonce || '',
			},
			signal: controller.signal,
		};

		if ( isEditorDirty ) {
			requestOptions.headers[ 'Content-Type' ] = 'application/json';
			requestOptions.body = JSON.stringify( {
				postId: currentPostId,
				postTitle,
				postExcerpt,
				content: postContent,
				meta: meta || {},
				url: permalink || '',
			} );
		}

		fetch( endpoint, requestOptions )
			.then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error( `HTTP ${ response.status }` );
				}
				return response.json();
			} )
			.then( ( payload ) => {
				const item = payload;
				assertUnifiedData( item, 'readiness.overviewItem' );

				const computed = getUnifiedComputed( item );
				const score = Number( computed?.seoScore );
				setReportSeoScore( Number.isFinite( score ) ? score : null );
			} )
			.catch( () => {
				// Keep local readiness score as fallback if diagnostics API is unavailable.
			} );

		return () => {
			controller.abort();
		};
	}, [ currentPostId, isEditorDirty, postTitle, postExcerpt, postContent, meta, permalink ] );

	const displayedReadinessScore = Number.isFinite( Number( reportSeoScore ) )
		? Number( reportSeoScore )
		: 0;

	const readinessTone = getScoreTone( displayedReadinessScore );

	return (
		<SidebarSectionShell
			sectionKey="readiness"
			expandedSection={ expandedSection }
			onExpand={ onExpand }
			title={ __( 'Indexability', 'asneris-seo-toolkit' ) }
			headerDescription={ __( 'Overall health score and priority SEO issues', 'asneris-seo-toolkit' ) }
			headerIcon="dashicons dashicons-chart-line"
			headerIconStyle={ { background: '#ecf8f0', color: '#2f9e44' } }
			initialOpen={ activeRoute === 'readiness' }
			headerAction={
				<PanelHeaderBadge
					label={ `${ displayedReadinessScore }/100` }
					tone={ readinessTone }
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
					<strong>{ __( 'Live indexability score', 'asneris-seo-toolkit' ) }</strong>
					<PanelHeaderBadge
						label={
							readinessTone === 'success'
								? __( 'Ready', 'asneris-seo-toolkit' )
								: __( 'Needs work', 'asneris-seo-toolkit' )
						}
						tone={ readinessTone }
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
							width: `${ displayedReadinessScore }%`,
							background:
								readinessTone === 'success'
									? '#46b450'
									: readinessTone === 'warning'
									? '#dba617'
									: '#d63638',
							transition: 'width 0.2s ease',
						} }
					/>
				</div>
				<div style={ { display: 'grid', gap: '6px' } }>
					<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#50575e' } }>
						{ __( 'Word count', 'asneris-seo-toolkit' ) }: { readiness.stats.wordCount }
					</div>
					<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#50575e' } }>
						{ __( 'Images', 'asneris-seo-toolkit' ) }: { readiness.stats.imageCount }
					</div>
					<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#50575e' } }>
						{ __( 'Alt text coverage', 'asneris-seo-toolkit' ) }: { readiness.stats.altCoverage }%
					</div>
					<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#50575e' } }>
						{ __( 'Internal links', 'asneris-seo-toolkit' ) }: { readiness.stats.internalLinkCount }
					</div>
					<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#787c82' } }>
						{ __( 'Scoring model', 'asneris-seo-toolkit' ) }: { `shared evaluator ${ EVALUATOR_VERSION }` }
					</div>
				</div>
			</SectionBox>

			<SectionBox>
				<strong>{ __( 'Top recommendations', 'asneris-seo-toolkit' ) }</strong>
				<ul style={ { margin: '8px 0 0 18px' } }>
					{ readiness.warnings.slice( 0, 4 ).map( ( warning ) => (
						<li key={ warning.label } style={ { marginBottom: '4px', fontSize: 'var(--asneris-helper-size)' } }>
							{ warning.detail }
						</li>
					) ) }
				</ul>
			</SectionBox>

			<SectionBox>
				<strong>{ __( 'Checks', 'asneris-seo-toolkit' ) }</strong>
				<div style={ { display: 'grid', gap: '8px', marginTop: '8px' } }>
					{ readiness.checks.map( ( check ) => (
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
									tone={ check.status === 'pass' ? 'success' : 'warning' }
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

export default SeoReadinessPanel;

