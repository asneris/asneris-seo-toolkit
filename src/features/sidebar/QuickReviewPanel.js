import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { PanelHeaderBadge, SectionBox } from '../../components/ui';
import SidebarSectionShell from './SidebarSectionShell';
import {
	evaluateAiDiscoverability,
	EVALUATOR_VERSION,
	evaluateSeoReadiness,
	getIssueCount,
} from './evaluatorUtils';

const QuickReviewPanel = ( {
	expandedSection,
	onExpand,
	onOpenWorkspace,
	workspaceEnabled = true,
} ) => {
	const handleOpenWorkspace = ( event ) => {
		event?.preventDefault?.();
		event?.stopPropagation?.();
		onOpenWorkspace?.();
	};

	const postTitle = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'title' )
	);
	const postExcerpt = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'excerpt' )
	);
	const content = useSelect( ( select ) => select( 'core/editor' ).getEditedPostContent() );
	const meta = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'meta' ) );

	const seoResult = evaluateSeoReadiness( {
		postTitle,
		postExcerpt,
		meta,
		content,
		origin: window.location.origin,
	} );
	const aiResult = evaluateAiDiscoverability( {
		content,
		siteName: window.asnerisseoData?.siteName || '',
		origin: window.location.origin,
	} );
	const seoScore = seoResult.score;
	const aiScore = aiResult.score;

	const issueCount = getIssueCount( seoResult, aiResult );

	return (
		<SidebarSectionShell
			sectionKey="quickReview"
			expandedSection={ expandedSection }
			onExpand={ onExpand }
			title={ __( 'Quick Review', 'asneris-seo-toolkit' ) }
			initialOpen={ true }
			headerAction={ <PanelHeaderBadge label={ __( 'Phase 6', 'asneris-seo-toolkit' ) } tone="info" /> }
		>
			<SectionBox>
				<div style={ { display: 'grid', gap: '8px' } }>
					<div style={ { display: 'flex', justifyContent: 'space-between' } }>
						<span>{ __( 'Indexability', 'asneris-seo-toolkit' ) }</span>
						<strong>{ seoScore } / 100</strong>
					</div>
					<div style={ { display: 'flex', justifyContent: 'space-between' } }>
						<span>{ __( 'AI Discoverability', 'asneris-seo-toolkit' ) }</span>
						<strong>{ aiScore } / 100</strong>
					</div>
					<div style={ { display: 'flex', justifyContent: 'space-between' } }>
						<span>{ __( 'Issues Found', 'asneris-seo-toolkit' ) }</span>
						<strong>{ issueCount }</strong>
					</div>
					<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#646970' } }>
						{ __(
							'Search preview quality and discoverability checks are available in the full workspace.',
							'asneris-seo-toolkit'
						) }
					</div>
					<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#787c82' } }>
						{ __( 'Scoring model', 'asneris-seo-toolkit' ) }: { `shared evaluator ${ EVALUATOR_VERSION }` }
					</div>
				</div>
			</SectionBox>
			{ workspaceEnabled ? (
				<Button
					variant="primary"
					className="ASNERISSEO-react-button ASNERISSEO-react-button-primary"
					onClick={ handleOpenWorkspace }
				>
					{ __( 'Open Full SEO Workspace', 'asneris-seo-toolkit' ) }
				</Button>
			) : null }
		</SidebarSectionShell>
	);
};

export default QuickReviewPanel;

