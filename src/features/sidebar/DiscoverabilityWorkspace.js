import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useMemo, useState } from '@wordpress/element';
import './DiscoverabilityWorkspace.css';
import {
	evaluateAiDiscoverability,
	EVALUATOR_VERSION,
	evaluateSeoReadiness,
	getAiRecommendations,
	getSearchRecommendations,
} from './evaluatorUtils';
import {
	DISCOVERABILITY_DATA_SOURCES,
	DISCOVERABILITY_EXPECTED_CHECKS,
	filterDiscoverabilityChecksByTab,
} from '../../app/discoverabilityDataModel';
import DiscoverabilityTopIssues from '../../app/components/DiscoverabilityTopIssues';
import { buildTopIssueCategories } from '../../app/discoverabilityIssueModel';

const tabs = [
	{ id: 'overview', label: __( 'Overview', 'asneris-seo-toolkit' ), icon: '⌂' },
	{ id: 'ai', label: __( 'AI Discoverability', 'asneris-seo-toolkit' ), icon: '◉' },
	{ id: 'search', label: __( 'Search Appearance', 'asneris-seo-toolkit' ), icon: '◌' },
	{ id: 'quality', label: __( 'Content Quality', 'asneris-seo-toolkit' ), icon: '✦' },
	{ id: 'links', label: __( 'Internal Links', 'asneris-seo-toolkit' ), icon: '↗' },
	{ id: 'schema', label: __( 'Schema', 'asneris-seo-toolkit' ), icon: '◇' },
	{ id: 'social', label: __( 'Social Preview', 'asneris-seo-toolkit' ), icon: '✶' },
	{ id: 'advanced', label: __( 'Advanced Settings', 'asneris-seo-toolkit' ), icon: '⚙' },
];

const tabDescriptions = {
	overview: __( 'Quick status and top actions to improve visibility.', 'asneris-seo-toolkit' ),
	ai: __( 'Readability and AI-friendliness signals in one place.', 'asneris-seo-toolkit' ),
	search: __( 'Preview how your result appears on search engines.', 'asneris-seo-toolkit' ),
	quality: __( 'Content depth and structure quality checks.', 'asneris-seo-toolkit' ),
	links: __( 'Internal linking strength and crawl support.', 'asneris-seo-toolkit' ),
	schema: __( 'Structured data readiness and schema status.', 'asneris-seo-toolkit' ),
	social: __( 'Social snippet completeness across platforms.', 'asneris-seo-toolkit' ),
	advanced: __( 'Canonical and robots settings health.', 'asneris-seo-toolkit' ),
};

const scoreColor = ( score ) => {
	if ( score >= 85 ) {
		return '#1f9d55';
	}
	if ( score >= 65 ) {
		return '#2563eb';
	}
	return '#d97706';
};

const normalizeVariableSyntax = ( value ) =>
	String( value || '' ).replace( /\{%\s*([a-z_]+)\s*%\}/gi, '{$1}' );

const resolveInlineVariables = ( value, context = {} ) => {
	let output = normalizeVariableSyntax( value );

	Object.entries( context ).forEach( ( [ key, rawValue ] ) => {
		const tokenRegex = new RegExp( `\\{${ key }\\}`, 'gi' );
		output = output.replace( tokenRegex, String( rawValue || '' ) );
	} );

	return output.replace( /\s+/g, ' ' ).trim();
};

const getTodayIsoDate = () => new Date().toISOString().split( 'T' )[ 0 ];

const WorkspaceCard = ( { children, className = '' } ) => {
	const classes = [ 'asneris-workspace-card', className ].filter( Boolean ).join( ' ' );
	return <div className={ classes }>{ children }</div>;
};

const SearchEngineReviewCard = ( {
	title,
	passCount,
	failCount,
	score,
	checks,
	emptyText,
 	className = '',
	recommendations = [],
	recommendationsTitle = __( 'Top recommendations', 'asneris-seo-toolkit' ),
} ) => (
	<WorkspaceCard className={ [ 'asneris-standard-content-card', className ].filter( Boolean ).join( ' ' ) }>
		<h4 style={ { marginTop: 0, marginBottom: '12px' } }>{ title }</h4>
		{ recommendations.length ? (
			<div style={ { marginBottom: '12px' } }>
				<h5 className="asneris-recommendation-title">{ recommendationsTitle }</h5>
				<div className="asneris-recommendation-list">
					{ recommendations.map( ( item ) => (
						<div key={ item } className="asneris-recommendation-item">{ item }</div>
					) ) }
				</div>
			</div>
		) : null }
		<div className="asneris-review-summary">
			<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#15803d', fontWeight: 'var(--asneris-h3-weight)' } }>
				{ __( 'Passed', 'asneris-seo-toolkit' ) }: { passCount }
			</div>
			<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#b45309', fontWeight: 'var(--asneris-h3-weight)' } }>
				{ __( 'Needs attention', 'asneris-seo-toolkit' ) }: { failCount }
			</div>
			{ typeof score === 'number' ? (
				<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#475569', fontWeight: 'var(--asneris-h3-weight)' } }>
					{ __( 'Score', 'asneris-seo-toolkit' ) }: { score } / 100
				</div>
			) : null }
		</div>
		<div className="asneris-review-check-list">
			{ checks.length ? (
				checks.map( ( check ) => (
					<div
						key={ `${ check.label }-${ check.detail }-${ check.keySuffix || 'row' }` }
						className={ `asneris-review-check ${ check.passed ? 'is-pass' : 'is-review' }` }
					>
						<div className="asneris-review-check-header">
							<strong className="asneris-review-check-label">{ check.label }</strong>
							<span className={ `asneris-review-status ${ check.passed ? 'is-pass' : 'is-review' }` }>
								{ check.passed ? __( 'OK', 'asneris-seo-toolkit' ) : __( 'Review', 'asneris-seo-toolkit' ) }
							</span>
						</div>
						<div className="asneris-review-check-detail">{ check.detail }</div>
					</div>
				) )
			) : (
				<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#475569' } }>{ emptyText }</div>
			) }
		</div>
	</WorkspaceCard>
);

const DiscoverabilityWorkspace = () => {
	const [ activeTab, setActiveTab ] = useState( 'overview' );
	const [ searchPreviewDevice, setSearchPreviewDevice ] = useState( 'desktop' );

	const postTitle = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'title' )
	);
	const postExcerpt = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'excerpt' )
	);
	const postSlug = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'slug' )
	);
	const postDate = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'date' )
	);
	const content = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostContent()
	);
	const meta = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'meta' )
	);

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

	const searchRecommendations = getSearchRecommendations( seoResult );
	const aiRecommendations = getAiRecommendations( aiResult );
	const searchEngineScore = Math.round( seoResult.score * 0.7 + aiResult.score * 0.3 );

	const qualityScore = Math.min(
		100,
		Math.max(
			0,
			Math.round(
				( seoResult.stats.wordCount >= 300 ? 55 : 35 ) +
				( seoResult.stats.altCoverage >= 80 ? 25 : 10 ) +
				( seoResult.stats.internalLinkCount >= 2 ? 20 : 8 )
			)
		)
	);

	const diagnosticsChecks = useMemo( () => {
		const normalized = [ ...( seoResult.checks || [] ), ...( aiResult.checks || [] ) ]
			.map( ( check ) => ( {
				label: check.label,
				detail: check.detail,
				details: check.detail,
				status: check.status === 'pass' ? 'pass' : 'warning',
				passed: check.status === 'pass' || check.ok === true,
			} ) );

		return normalized;
	}, [ aiResult.checks, seoResult.checks ] );

	const topIssues = useMemo( () => {
		return buildTopIssueCategories( diagnosticsChecks );
	}, [ diagnosticsChecks ] );

	const variableContext = {
		title: String( postTitle || '' ).trim(),
		site: String( window.asnerisseoData?.siteName || '' ).trim(),
		separator: String( window.asnerisseoData?.titleSeparator || '|' ),
		excerpt: String( postExcerpt?.rendered || postExcerpt?.raw || postExcerpt || '' )
			.replace( /<[^>]*>/g, ' ' )
			.replace( /\s+/g, ' ' )
			.trim(),
		date: String( postDate || '' ).split( 'T' )[ 0 ] || getTodayIsoDate(),
		author: String( window.asnerisseoData?.authorName || '' ).trim(),
		term: String( window.asnerisseoData?.primaryTerm || '' ).trim(),
	};
	const resolvedTitle = resolveInlineVariables( seoResult.effectiveTitle || '', variableContext );
	const resolvedDescription = resolveInlineVariables(
		seoResult.effectiveDescription || '',
		variableContext
	);
	const previewTitle = resolvedTitle || postTitle || __( 'Untitled post', 'asneris-seo-toolkit' );
	const previewDescription =
		resolvedDescription ||
		__( 'Add a meta description to improve search preview quality.', 'asneris-seo-toolkit' );
	const previewUrl = `${ window.location.origin }/${ postSlug || 'sample-post' }`;
	const socialTitle = String( meta?._ASNERISSEO_og_title || '' ).trim();
	const socialDescription = String( meta?._ASNERISSEO_og_description || '' ).trim();
	const socialImage = String( meta?._ASNERISSEO_og_image || '' ).trim();
	const effectiveSocialTitle = socialTitle || previewTitle;
	const effectiveSocialDescription = socialDescription || previewDescription;
	const schemaEnabled = !! meta?._ASNERISSEO_schema_enabled;
	const canonicalUrl = String( meta?._ASNERISSEO_canonical || '' ).trim();
	const robotsIndex = String( meta?._ASNERISSEO_robots_index || 'index' ).trim();
	const robotsFollow = String( meta?._ASNERISSEO_robots_follow || 'follow' ).trim();

	const scoreCards = [
		{
			id: 'seo',
			label: __( 'Indexability', 'asneris-seo-toolkit' ),
			score: seoResult.score,
			color: scoreColor( seoResult.score ),
		},
		{
			id: 'ai',
			label: __( 'AI Discoverability', 'asneris-seo-toolkit' ),
			score: aiResult.score,
			color: scoreColor( aiResult.score ),
		},
		{
			id: 'quality',
			label: __( 'Content Quality', 'asneris-seo-toolkit' ),
			score: qualityScore,
			color: scoreColor( qualityScore ),
		},
		{
			id: 'search',
			label: __( 'Search Engine Review', 'asneris-seo-toolkit' ),
			score: searchEngineScore,
			color: scoreColor( searchEngineScore ),
		},
	];

	const diagnosticsPassCount = diagnosticsChecks.filter( ( check ) => check.passed ).length;
	const diagnosticsFailCount = diagnosticsChecks.length - diagnosticsPassCount;
	const diagnosticsPreviewRows = diagnosticsChecks.slice( 0, 6 );
	const getCheckByPattern = ( pattern, checks = diagnosticsChecks ) =>
		checks.find( ( check ) => pattern.test( String( check?.label || '' ) ) );
	const isFailedCheck = ( pattern, checks = diagnosticsChecks ) => {
		const matched = getCheckByPattern( pattern, checks );
		return !! matched && ! matched.passed;
	};
	const totalChecksLabel = `${ diagnosticsFailCount } / ${ diagnosticsChecks.length || 0 }`;
	const overviewKeyFieldFailures = [
		isFailedCheck( /seo title/i ),
		isFailedCheck( /meta description/i ),
		isFailedCheck( /h1 present|heading structure|heading hierarchy/i ),
		isFailedCheck( /internal links/i ),
		isFailedCheck( /content depth|word count|content present/i ),
	].filter( Boolean ).length;

	const titleLength = String( seoResult.effectiveTitle || '' ).trim().length;
	const titleLengthSegment = titleLength > 0
		? `${ titleLength }/60`
		: __( 'length unknown', 'asneris-seo-toolkit' );
	const metaLength = String( seoResult.effectiveDescription || '' ).trim().length;
	const metaLengthSegment = metaLength > 0
		? `${ metaLength }/160`
		: __( 'length unknown', 'asneris-seo-toolkit' );
	const titleFailed = isFailedCheck( /seo title|title quality/i );
	const metaFailed = isFailedCheck( /meta description|description quality/i );
	const h1Check = getCheckByPattern( /h1 present/i, aiResult.checks || [] );
	const h1Count = Number( aiResult.stats.headingCount );
	const h1CountLabel = Number.isFinite( h1Count )
		? `${ h1Count } ${ h1Count === 1 ? __( 'H1', 'asneris-seo-toolkit' ) : __( 'H1s', 'asneris-seo-toolkit' ) }`
		: __( 'count unknown', 'asneris-seo-toolkit' );
	const contentKeyFieldFailures = [
		titleFailed,
		metaFailed,
		! ( h1Check?.passed ?? false ),
		isFailedCheck( /content depth|word count|content present/i ),
	].filter( Boolean ).length;
	const contentDetailRows = [
		{
			label: __( 'SEO Title', 'asneris-seo-toolkit' ),
			value: titleFailed
				? __( 'Missing', 'asneris-seo-toolkit' )
				: `${ __( 'Present', 'asneris-seo-toolkit' ) } (${ titleLengthSegment })`,
		},
		{
			label: __( 'Meta Description', 'asneris-seo-toolkit' ),
			value: metaFailed
				? __( 'Missing', 'asneris-seo-toolkit' )
				: `${ __( 'Present', 'asneris-seo-toolkit' ) } (${ metaLengthSegment })`,
		},
		{
			label: __( 'H1 Heading', 'asneris-seo-toolkit' ),
			value: h1Check?.passed
				? h1CountLabel
				: __( 'Missing', 'asneris-seo-toolkit' ),
		},
		{
			label: __( 'Content Quality', 'asneris-seo-toolkit' ),
			value:
				Number.isFinite( seoResult.stats.wordCount )
					? seoResult.stats.wordCount >= 500
					? `${ seoResult.stats.wordCount.toLocaleString() } ${ __( 'words - Good content depth.', 'asneris-seo-toolkit' ) }`
					: `${ seoResult.stats.wordCount.toLocaleString() } ${ __( 'words - Needs more depth.', 'asneris-seo-toolkit' ) }`
					: __( 'Unknown', 'asneris-seo-toolkit' ),
		},
	];
	const searchReviewChecks = filterDiscoverabilityChecksByTab( diagnosticsChecks, 'search' );
	const qualityReviewChecks = filterDiscoverabilityChecksByTab( diagnosticsChecks, 'quality' );
	const linkReviewChecks = filterDiscoverabilityChecksByTab( diagnosticsChecks, 'links' );
	const schemaModelChecks = filterDiscoverabilityChecksByTab( diagnosticsChecks, 'schema' );
	const advancedModelChecks = filterDiscoverabilityChecksByTab( diagnosticsChecks, 'advanced' );
	const aiModelChecks = filterDiscoverabilityChecksByTab( diagnosticsChecks, 'ai' );
	const socialReviewChecks = [
		{
			label: __( 'Social title', 'asneris-seo-toolkit' ),
			detail: socialTitle
				? __( 'Social title is configured.', 'asneris-seo-toolkit' )
				: __( 'Add a custom social title for sharing previews.', 'asneris-seo-toolkit' ),
			passed: !! socialTitle,
			keySuffix: 'social-title',
		},
		{
			label: __( 'Social description', 'asneris-seo-toolkit' ),
			detail: socialDescription
				? __( 'Social description is configured.', 'asneris-seo-toolkit' )
				: __( 'Add a social description for stronger social snippets.', 'asneris-seo-toolkit' ),
			passed: !! socialDescription,
			keySuffix: 'social-description',
		},
		{
			label: __( 'Social image', 'asneris-seo-toolkit' ),
			detail: socialImage
				? __( 'Social image is configured.', 'asneris-seo-toolkit' )
				: __( 'Add a social image to improve shared post appearance.', 'asneris-seo-toolkit' ),
			passed: !! socialImage,
			keySuffix: 'social-image',
		},
	];
	const schemaReviewChecks = [
		{
			label: __( 'Schema markup', 'asneris-seo-toolkit' ),
			detail: schemaEnabled
				? __( 'Schema is enabled for this content.', 'asneris-seo-toolkit' )
				: __( 'Enable schema to improve structured understanding.', 'asneris-seo-toolkit' ),
			passed: schemaEnabled,
			keySuffix: 'schema',
		},
	];
	const socialNetworkBreakdown = [
		{ id: 'facebook', label: __( 'Facebook', 'asneris-seo-toolkit' ) },
		{ id: 'linkedin', label: __( 'LinkedIn', 'asneris-seo-toolkit' ) },
		{ id: 'x', label: __( 'X', 'asneris-seo-toolkit' ) },
	].map( ( network ) => ( {
		...network,
		title: effectiveSocialTitle,
		description: effectiveSocialDescription,
		imageUrl: socialImage,
		hasCustomTitle: !! socialTitle,
		hasCustomDescription: !! socialDescription,
		hasImage: !! socialImage,
		isReady: !! socialTitle && !! socialDescription && !! socialImage,
	} ) );

	const nonDiagnosticsChecks = useMemo( () => {
		switch ( activeTab ) {
			case 'overview':
				return diagnosticsPreviewRows;
			case 'ai':
				return aiModelChecks.length ? aiModelChecks : diagnosticsChecks;
			case 'search':
				return searchReviewChecks.length ? searchReviewChecks : diagnosticsChecks;
			case 'quality':
				return qualityReviewChecks.length ? qualityReviewChecks : diagnosticsChecks;
			case 'links':
				return linkReviewChecks.length ? linkReviewChecks : diagnosticsChecks;
			case 'schema':
				return schemaModelChecks.length ? schemaModelChecks : schemaReviewChecks;
			case 'social':
				return socialReviewChecks;
			case 'advanced':
				return [
					...( advancedModelChecks.length ? advancedModelChecks : diagnosticsChecks.filter( ( check ) =>
						/(canonical url|robots directives)/i.test( check.label )
					) ),
					...schemaReviewChecks,
				];
			default:
				return diagnosticsPreviewRows;
		}
	}, [
		activeTab,
		advancedModelChecks,
		aiModelChecks,
		diagnosticsChecks,
		diagnosticsPreviewRows,
		linkReviewChecks,
		qualityReviewChecks,
		schemaModelChecks,
		schemaReviewChecks,
		searchReviewChecks,
		socialReviewChecks,
	] );

	const nonDiagnosticsPassCount = nonDiagnosticsChecks.filter( ( check ) => check.passed ).length;
	const nonDiagnosticsFailCount = nonDiagnosticsChecks.length - nonDiagnosticsPassCount;
	const aiMetricChecks = aiModelChecks.length ? aiModelChecks : diagnosticsChecks;
	const aiMetricFailCount = aiMetricChecks.filter( ( check ) => ! check.passed ).length;
	const getAiCheckState = ( pattern ) => {
		const match = aiMetricChecks.find( ( check ) => pattern.test( String( check?.label || '' ) ) );
		if ( !match ) {
			return __( 'Not checked', 'asneris-seo-toolkit' );
		}

		return match.passed
			? __( 'Present', 'asneris-seo-toolkit' )
			: __( 'Missing', 'asneris-seo-toolkit' );
	};
	const aiDetailRows = [
		{
			label: DISCOVERABILITY_EXPECTED_CHECKS.ai[ 0 ],
			value: getAiCheckState( /primary entity|brand mentions|product\/context mentions/i ),
		},
		{
			label: DISCOVERABILITY_EXPECTED_CHECKS.ai[ 1 ],
			value: getAiCheckState( /semantic heading structure|h1 present|heading hierarchy|sections coverage/i ),
		},
		{
			label: DISCOVERABILITY_EXPECTED_CHECKS.ai[ 2 ],
			value: getAiCheckState( /author information|trust signals/i ),
		},
		{
			label: DISCOVERABILITY_EXPECTED_CHECKS.ai[ 3 ],
			value: getAiCheckState( /language declaration/i ),
		},
		{
			label: DISCOVERABILITY_EXPECTED_CHECKS.ai[ 4 ],
			value: getAiCheckState( /internal references|internal links/i ),
		},
		{
			label: DISCOVERABILITY_EXPECTED_CHECKS.ai[ 5 ],
			value: getAiCheckState( /table\/list detection|list usage|table usage/i ),
		},
	];
	const aiKeyFieldFailures = aiDetailRows.filter( ( row ) => row.value === __( 'Missing', 'asneris-seo-toolkit' ) ).length;
	const searchMetricChecks = searchReviewChecks.length ? searchReviewChecks : diagnosticsChecks;
	const searchReviewPassCount = searchMetricChecks.filter( ( check ) => check.passed ).length;
	const searchReviewFailCount = searchMetricChecks.length - searchReviewPassCount;
	const searchActionItems = useMemo( () => {
		const failedChecks = searchMetricChecks
			.filter( ( check ) => ! check.passed )
			.map( ( check ) => String( check.detail || '' ).trim() )
			.filter( Boolean );

		const shouldUseFallbackRecommendations =
			! Array.isArray( searchReviewChecks ) || searchReviewChecks.length === 0;

		const fallbackItems = shouldUseFallbackRecommendations && Array.isArray( searchRecommendations )
			? searchRecommendations.filter( Boolean )
			: [];

		const seen = new Set();
		const merged = [ ...failedChecks, ...fallbackItems ].filter( ( item ) => {
			const normalized = item.toLowerCase();
			if ( seen.has( normalized ) ) {
				return false;
			}
			seen.add( normalized );
			return true;
		} );

		if ( merged.length > 0 ) {
			return merged.slice( 0, 6 );
		}

		return [ __( 'Search preview checks look good.', 'asneris-seo-toolkit' ) ];
	}, [ searchMetricChecks, searchRecommendations, searchReviewChecks ] );
	const searchStatusRows = useMemo( () => {
		const titleCheck = searchMetricChecks.find( ( check ) => /seo title|title quality/i.test( String( check?.label || '' ) ) );
		const metaCheck = searchMetricChecks.find( ( check ) => /meta description|description quality/i.test( String( check?.label || '' ) ) );
		const titleState = titleCheck?.passed
			? `${ __( 'Present', 'asneris-seo-toolkit' ) } (${ titleLengthSegment })`
			: __( 'Missing', 'asneris-seo-toolkit' );
		const metaState = metaCheck?.passed
			? `${ __( 'Present', 'asneris-seo-toolkit' ) } (${ metaLengthSegment })`
			: __( 'Missing', 'asneris-seo-toolkit' );

		return [
			{ label: __( 'SEO Title', 'asneris-seo-toolkit' ), value: titleState },
			{ label: __( 'Meta Description', 'asneris-seo-toolkit' ), value: metaState },
			...searchMetricChecks
				.filter( ( check ) => !/seo title|title quality|meta description|description quality/i.test( String( check?.label || '' ) ) )
				.map( ( check ) => ( {
					label: check.label,
					value: check.passed ? __( 'Present', 'asneris-seo-toolkit' ) : __( 'Missing', 'asneris-seo-toolkit' ),
				} ) ),
		].slice( 0, 6 );
	}, [ metaLengthSegment, searchMetricChecks, titleLengthSegment ] );
	const openIssueCount = useMemo( () => {
		switch ( activeTab ) {
			case 'overview':
				return topIssues.length;
			case 'ai':
					return aiMetricFailCount;
			case 'search':
				return searchReviewFailCount;
			default:
				return nonDiagnosticsFailCount;
		}
	}, [
		activeTab,
		topIssues.length,
		aiMetricFailCount,
		searchReviewFailCount,
		nonDiagnosticsFailCount,
	] );
	const activeTabLabel = tabs.find( ( tab ) => tab.id === activeTab )?.label || __( 'Overview', 'asneris-seo-toolkit' );
	const activeTabDescription = tabDescriptions[ activeTab ] || tabDescriptions.overview;

	const renderDetailPanel = () => {
		switch ( activeTab ) {
			case 'ai':
				return (
						<WorkspaceCard className="asneris-workspace-detail-card">
							<h4>{ __( 'AI Discoverability', 'asneris-seo-toolkit' ) }</h4>
							<div className="asneris-detail-list" style={ { marginBottom: '12px' } }>
								<div className="asneris-detail-item">
									<strong>{ __( 'Key Fields Failure Count', 'asneris-seo-toolkit' ) }:</strong> { `${ aiKeyFieldFailures } / ${ aiDetailRows.length }` }
								</div>
								<div className="asneris-detail-item">
									<strong>{ __( 'Total Check Failure Count', 'asneris-seo-toolkit' ) }:</strong> { `${ aiMetricFailCount } / ${ aiMetricChecks.length || 0 }` }
								</div>
							</div>
							<div className="asneris-detail-list" style={ { marginBottom: '12px' } }>
								{ aiDetailRows.map( ( row ) => (
									<div key={ row.label } className="asneris-detail-item">
										<strong>{ row.label }:</strong> { row.value }
									</div>
								) ) }
							</div>
							<div className="asneris-detail-list">
								{ ( aiRecommendations.length ? aiRecommendations : [ __( 'AI signals look good.', 'asneris-seo-toolkit' ) ] )
									.slice( 0, 5 )
									.map( ( item ) => (
										<div key={ item } className="asneris-detail-item">{ item }</div>
									) ) }
							</div>
						</WorkspaceCard>
				);
			case 'search':
				return (
					<WorkspaceCard className="asneris-workspace-detail-card">
						<h4>{ __( 'Search Preview Review', 'asneris-seo-toolkit' ) }</h4>
						<div style={ { display: 'grid', gap: '12px' } }>
								<div
									style={ {
										display: 'flex',
										justifyContent: 'space-between',
										alignItems: 'center',
										flexWrap: 'wrap',
										rowGap: '4px',
										fontSize: 'var(--asneris-table-chip-size)',
										color: '#64748b',
									} }
								>
									{/* <span>{ __( 'Google-style comparison preview', 'asneris-seo-toolkit' ) }</span> */}
									<div
										style={ {
											display: 'inline-flex',
											border: '1px solid #dbe4ef',
											borderRadius: '8px',
											overflow: 'hidden',
										} }
									>
										<button
											type="button"
											onClick={ () => setSearchPreviewDevice( 'desktop' ) }
											style={ {
												border: 'none',
												padding: '4px 10px',
												fontSize: 'var(--asneris-table-chip-size)',
												fontWeight: 'var(--asneris-h3-weight)',
												cursor: 'pointer',
												background: searchPreviewDevice === 'desktop' ? '#eaf2ff' : '#fff',
												color: searchPreviewDevice === 'desktop' ? '#2563eb' : '#475569',
											} }
										>
											{ __( 'Desktop', 'asneris-seo-toolkit' ) }
										</button>
										<button
											type="button"
											onClick={ () => setSearchPreviewDevice( 'mobile' ) }
											style={ {
												border: 'none',
												borderLeft: '1px solid #dbe4ef',
												padding: '4px 10px',
												fontSize: 'var(--asneris-table-chip-size)',
												fontWeight: 'var(--asneris-h3-weight)',
												cursor: 'pointer',
												background: searchPreviewDevice === 'mobile' ? '#eaf2ff' : '#fff',
												color: searchPreviewDevice === 'mobile' ? '#2563eb' : '#475569',
											} }
										>
											{ __( 'Mobile', 'asneris-seo-toolkit' ) }
										</button>
									</div>
								</div>

								{ searchPreviewDevice === 'desktop' ? (
									<div
										style={ {
											border: '1px solid #dbe4ef',
											borderRadius: '10px',
											overflow: 'hidden',
											background: '#f8fafc',
										} }
									>
										<div
											style={ {
												padding: '8px 10px',
												fontSize: 'var(--asneris-table-chip-size)',
												fontWeight: 'var(--asneris-h1-weight)',
												color: '#334155',
												background: '#eef2ff',
												borderBottom: '1px solid #dbe4ef',
											} }
										>
											{ __( 'Desktop (wide result)', 'asneris-seo-toolkit' ) }
										</div>
										<div style={ { padding: '10px 12px', background: '#fff' } }>
											<div
												style={ {
													fontSize: 'var(--asneris-table-chip-size)',
													color: '#64748b',
													marginBottom: '6px',
													whiteSpace: 'nowrap',
													overflow: 'hidden',
													textOverflow: 'ellipsis',
												} }
											>
												{ previewUrl }
											</div>
											<div
												style={ {
													color: '#1d4ed8',
													fontWeight: 'var(--asneris-h1-weight)',
													lineHeight: 'var(--asneris-h1-line)',
													fontSize: 'var(--asneris-h1-size)',
													marginBottom: '6px',
												} }
											>
												{ previewTitle }
											</div>
											<div style={ { color: '#334155', fontSize: 'var(--asneris-body-size)', lineHeight: 'var(--asneris-body-line)' } }>{ previewDescription }</div>
										</div>
									</div>
								) : (
									<div
										style={ {
											border: '1px solid #dbe4ef',
											borderRadius: '18px',
											background: '#0f172a',
											padding: '10px',
											maxWidth: '250px',
											margin: '0 auto',
										} }
									>
										<div
											style={ {
												padding: '6px 8px',
												fontSize: 'var(--asneris-table-chip-size)',
												fontWeight: 'var(--asneris-h1-weight)',
												color: '#e2e8f0',
												background: '#1e293b',
												borderRadius: '10px 10px 0 0',
											} }
										>
											{ __( 'Mobile (compact result)', 'asneris-seo-toolkit' ) }
										</div>
										<div style={ { background: '#fff', borderRadius: '0 0 10px 10px', padding: '8px' } }>
											<div
												style={ {
													fontSize: 'var(--asneris-table-chip-size)',
													color: '#64748b',
													marginBottom: '5px',
													whiteSpace: 'nowrap',
													overflow: 'hidden',
													textOverflow: 'ellipsis',
												} }
											>
												{ previewUrl }
											</div>
											<div
												style={ {
													color: '#1d4ed8',
													fontWeight: 'var(--asneris-h1-weight)',
													lineHeight: 'var(--asneris-h3-line)',
													fontSize: 'var(--asneris-h3-size)',
													marginBottom: '4px',
												} }
											>
												{ previewTitle }
											</div>
											<div style={ { color: '#334155', fontSize: 'var(--asneris-helper-size)', lineHeight: 'var(--asneris-h3-line)' } }>{ previewDescription }</div>
										</div>
									</div>
								) }

							<div
								style={ {
									borderTop: '1px solid #e2e8f0',
									paddingTop: '10px',
								} }
							>
								<h5 style={ { margin: '0 0 8px', fontSize: 'var(--asneris-body-size)', color: '#334155' } }>
									{ __( 'Actionable SEO Review', 'asneris-seo-toolkit' ) }
								</h5>
								<div className="asneris-detail-list">
									{ searchActionItems.map( ( item ) => (
										<div key={ item } className="asneris-detail-item">{ item }</div>
									) ) }
								</div>
							</div>

							<div
								style={ {
									borderTop: '1px solid #e2e8f0',
									paddingTop: '10px',
								} }
							>
								<h5 style={ { margin: '0 0 8px', fontSize: 'var(--asneris-body-size)', color: '#334155' } }>
									{ __( 'Live Check Status', 'asneris-seo-toolkit' ) }
								</h5>
								<div className="asneris-detail-list">
									{ searchStatusRows.length > 0 ? (
										searchStatusRows.map( ( check ) => (
											<div
												key={ `${ check.label }-${ check.value }` }
												className="asneris-detail-item"
											>
												<strong>{ check.label }:</strong> { check.value }
											</div>
										) )
									) : (
										<div className="asneris-detail-item">{ __( 'No search checks available yet.', 'asneris-seo-toolkit' ) }</div>
									) }
								</div>
							</div>
						</div>
					</WorkspaceCard>
				);
			case 'social':
				return (
					<WorkspaceCard className="asneris-workspace-detail-card asneris-standard-content-card">
						<h4>{ __( 'Social Preview', 'asneris-seo-toolkit' ) }</h4>
						<div className="asneris-breakdown-rows">
							{ socialNetworkBreakdown.map( ( network ) => (
								<div key={ network.id } className="asneris-breakdown-row-card">
									<div className="asneris-breakdown-header">
										<strong>{ network.label }</strong>
										<span className={ `asneris-breakdown-badge ${ network.isReady ? 'is-ready' : 'is-needs' }` }>
											{ network.isReady
												? __( 'Ready', 'asneris-seo-toolkit' )
												: __( 'Needs setup', 'asneris-seo-toolkit' ) }
										</span>
									</div>
									<div className="asneris-social-row-grid">
										<div className="asneris-social-preview-box">
											{ network.hasImage ? (
												<img
													src={ network.imageUrl }
													alt={ `${ network.label } ${ __( 'preview image', 'asneris-seo-toolkit' ) }` }
													className="asneris-social-preview-image"
												/>
											) : (
												<div className="asneris-social-preview-placeholder">{ __( 'No image preview', 'asneris-seo-toolkit' ) }</div>
											) }
										</div>
										<div className="asneris-social-readonly-fields">
											<div className="asneris-breakdown-row">
												<span>{ __( 'Title', 'asneris-seo-toolkit' ) }</span>
												<span>{ network.hasCustomTitle ? __( 'Custom', 'asneris-seo-toolkit' ) : __( 'SEO fallback', 'asneris-seo-toolkit' ) }</span>
											</div>
											<div className="asneris-readonly-field">{ network.title }</div>
											<div className="asneris-breakdown-row">
												<span>{ __( 'Description', 'asneris-seo-toolkit' ) }</span>
												<span>{ network.hasCustomDescription ? __( 'Custom', 'asneris-seo-toolkit' ) : __( 'Meta fallback', 'asneris-seo-toolkit' ) }</span>
											</div>
											<div className="asneris-readonly-field asneris-readonly-field-multiline">{ network.description }</div>
											<div className="asneris-breakdown-row">
												<span>{ __( 'Image URL', 'asneris-seo-toolkit' ) }</span>
												<span>{ network.hasImage ? __( 'Configured', 'asneris-seo-toolkit' ) : __( 'Missing', 'asneris-seo-toolkit' ) }</span>
											</div>
											<div className="asneris-readonly-field">{ network.imageUrl || __( 'No image URL set', 'asneris-seo-toolkit' ) }</div>
										</div>
									</div>
								</div>
							) ) }
						</div>
						<div className="asneris-breakdown-note">
							{ __( 'Facebook, LinkedIn, and X currently use shared Open Graph fields in this setup.', 'asneris-seo-toolkit' ) }
						</div>
					</WorkspaceCard>
				);
			case 'schema':
				return (
					<WorkspaceCard className="asneris-workspace-detail-card">
						<h4>{ __( 'Schema', 'asneris-seo-toolkit' ) }</h4>
						<div className="asneris-snapshot-grid">
							<div className="asneris-snapshot-item">
								<div className="asneris-snapshot-label">{ __( 'Schema enabled', 'asneris-seo-toolkit' ) }</div>
								<div className="asneris-snapshot-value">{ schemaEnabled ? __( 'Yes', 'asneris-seo-toolkit' ) : __( 'No', 'asneris-seo-toolkit' ) }</div>
							</div>
							<div className="asneris-snapshot-item">
								<div className="asneris-snapshot-label">{ __( 'Recommendation', 'asneris-seo-toolkit' ) }</div>
								<div className="asneris-snapshot-value">{ schemaEnabled ? __( 'Keep active', 'asneris-seo-toolkit' ) : __( 'Enable schema', 'asneris-seo-toolkit' ) }</div>
							</div>
						</div>
					</WorkspaceCard>
				);
			case 'links':
				return (
					<WorkspaceCard className="asneris-workspace-detail-card">
						<h4>{ __( 'Internal Links', 'asneris-seo-toolkit' ) }</h4>
						<div className="asneris-detail-list">
							<div className="asneris-detail-item">
								<strong>{ __( 'Detected internal links', 'asneris-seo-toolkit' ) }:</strong> { seoResult.stats.internalLinkCount }
							</div>
							{ seoResult.checks
								.filter( ( check ) => /link/i.test( check.label ) )
								.slice( 0, 4 )
								.map( ( check ) => (
									<div key={ check.label } className="asneris-detail-item">{ check.detail }</div>
								) ) }
						</div>
					</WorkspaceCard>
				);
			case 'quality':
				return (
					<WorkspaceCard className="asneris-workspace-detail-card">
						<h4>{ __( 'Content Quality', 'asneris-seo-toolkit' ) }</h4>
						<div className="asneris-detail-list" style={ { marginBottom: '12px' } }>
							<div className="asneris-detail-item">
								<strong>{ __( 'Key Fields Failure Count', 'asneris-seo-toolkit' ) }:</strong> { `${ contentKeyFieldFailures } / 4` }
							</div>
							<div className="asneris-detail-item">
								<strong>{ __( 'Total Check Failure Count', 'asneris-seo-toolkit' ) }:</strong> { `${ nonDiagnosticsFailCount } / ${ nonDiagnosticsChecks.length || 0 }` }
							</div>
						</div>
						<div className="asneris-detail-list">
							{ contentDetailRows.map( ( row ) => (
								<div key={ row.label } className="asneris-detail-item">
									<strong>{ row.label }:</strong> { row.value }
								</div>
							) ) }
						</div>
					</WorkspaceCard>
				);
			case 'advanced':
				return (
					<WorkspaceCard className="asneris-workspace-detail-card">
						<h4>{ __( 'Advanced Settings', 'asneris-seo-toolkit' ) }</h4>
						<div className="asneris-snapshot-grid">
							<div className="asneris-snapshot-item">
								<div className="asneris-snapshot-label">{ __( 'Canonical URL', 'asneris-seo-toolkit' ) }</div>
								<div className="asneris-snapshot-value">{ canonicalUrl || __( 'Current URL', 'asneris-seo-toolkit' ) }</div>
							</div>
							<div className="asneris-snapshot-item">
								<div className="asneris-snapshot-label">{ __( 'Robots Index', 'asneris-seo-toolkit' ) }</div>
								<div className="asneris-snapshot-value">{ robotsIndex }</div>
							</div>
							<div className="asneris-snapshot-item">
								<div className="asneris-snapshot-label">{ __( 'Robots Follow', 'asneris-seo-toolkit' ) }</div>
								<div className="asneris-snapshot-value">{ robotsFollow }</div>
							</div>
						</div>
					</WorkspaceCard>
				);
			default:
				return (
					<WorkspaceCard className="asneris-workspace-detail-card asneris-standard-content-card">
						<h4>{ __( 'Current Snapshot', 'asneris-seo-toolkit' ) }</h4>
						<div className="asneris-snapshot-grid">
							<div className="asneris-snapshot-item">
								<div className="asneris-snapshot-label">{ __( 'Indexability', 'asneris-seo-toolkit' ) }</div>
								<div className="asneris-snapshot-value">{ seoResult.score } / 100</div>
							</div>
							<div className="asneris-snapshot-item">
								<div className="asneris-snapshot-label">{ __( 'AI Discoverability', 'asneris-seo-toolkit' ) }</div>
								<div className="asneris-snapshot-value">{ aiResult.score } / 100</div>
							</div>
							<div className="asneris-snapshot-item">
								<div className="asneris-snapshot-label">{ __( 'Word Count', 'asneris-seo-toolkit' ) }</div>
								<div className="asneris-snapshot-value">{ seoResult.stats.wordCount }</div>
							</div>
							<div className="asneris-snapshot-item">
								<div className="asneris-snapshot-label">{ __( 'Internal Links', 'asneris-seo-toolkit' ) }</div>
								<div className="asneris-snapshot-value">{ seoResult.stats.internalLinkCount }</div>
							</div>
							<div className="asneris-snapshot-item">
								<div className="asneris-snapshot-label">{ __( 'Image Alt Coverage', 'asneris-seo-toolkit' ) }</div>
								<div className="asneris-snapshot-value">{ seoResult.stats.altCoverage }%</div>
							</div>
						</div>
					</WorkspaceCard>
				);
		}
	};

	return (
		<div className="asneris-workspace-shell">
			<div className="asneris-workspace-layout">
				<WorkspaceCard className="asneris-workspace-sidebar-card">
					<div className="asneris-workspace-sidebar-title" style={ { fontSize: 'var(--asneris-helper-size)', fontWeight: 'var(--asneris-h1-weight)', marginBottom: '4px' } }>
						{ __( 'Asneris SEO Toolkit', 'asneris-seo-toolkit' ) }
					</div>
					<div
						className="asneris-workspace-sidebar-badge"
						style={ {
							fontSize: 'var(--asneris-table-chip-size)',
							color: '#6d28d9',
							background: '#f3e8ff',
							display: 'inline-block',
							borderRadius: '999px',
							padding: '2px 8px',
							marginBottom: '10px',
						} }
					>
						{ __( 'Review Signals', 'asneris-seo-toolkit' ) }
					</div>
					
					<div className="asneris-workspace-tabs ASNERISSEO-react-tabs" role="tablist" aria-label={ __( 'Discoverability Tabs', 'asneris-seo-toolkit' ) }>
						{ tabs.map( ( tab ) => {
							const isActive = activeTab === tab.id;
							return (
								<button
									key={ tab.id }
									className={ `asneris-workspace-tab-card ASNERISSEO-react-tab${ isActive ? ' is-active' : '' }` }
									type="button"
									role="tab"
									aria-selected={ isActive }
									tabIndex={ isActive ? 0 : -1 }
									onClick={ () => setActiveTab( tab.id ) }
								>
									<span className="ASNERISSEO-react-tab-icon">{ tab.icon }</span>
									<span className="asneris-workspace-tab-label">{ tab.label }</span>
								</button>
							);
						} ) }
					</div>
					
				</WorkspaceCard>

				<div className="asneris-workspace-main">
					<div className="asneris-workspace-detail-wrap">
						<div className="asneris-workspace-intro">
							<div>
								<div className="asneris-workspace-intro-title">{ activeTabLabel }</div>
								<div className="asneris-workspace-intro-description">{ activeTabDescription }</div>
							</div>
							<div className="asneris-workspace-intro-meta">
								{ __( 'Open issues', 'asneris-seo-toolkit' ) }: { openIssueCount }
							</div>
						</div>
					</div>

					<div className="asneris-count-card-row">
						{ scoreCards.map( ( metric ) => (
							<div
								key={ metric.id }
								className="asneris-count-card"
							>
								<div className="asneris-count-card-title">
									{ metric.label }
								</div>
								<div className={ `asneris-count-card-meta ${ metric.id === 'search' ? '' : 'is-hidden' }` }>
									{ metric.id === 'search'
										? `${ __( 'Passed', 'asneris-seo-toolkit' ) }: ${ searchReviewPassCount } | ${ __( 'Needs', 'asneris-seo-toolkit' ) }: ${ searchReviewFailCount }`
										: '\u00A0' }
								</div>
								<div style={ { display: 'flex', alignItems: 'baseline', gap: '4px' } }>
									<span style={ { fontSize: 'var(--asneris-h1-size)', lineHeight: 'var(--asneris-h3-line)', fontWeight: 'var(--asneris-h1-weight)', color: metric.color } }>
										{ metric.score }
									</span>
									<span style={ { color: '#64748b', fontWeight: 'var(--asneris-h3-weight)' } }>/ 100</span>
								</div>
								<div
									style={ {
										height: '5px',
										borderRadius: '999px',
										background: '#e2e8f0',
										overflow: 'hidden',
										marginTop: 'auto',
									} }
								>
									<div
										style={ {
											height: '100%',
											width: `${ metric.score }%`,
											background: metric.color,
										} }
									/>
								</div>
							</div>
						) ) }
					</div>

					{ activeTab === 'overview' ? (
						<div className="asneris-workspace-detail-wrap">
						<WorkspaceCard className="asneris-workspace-detail-card asneris-standard-content-card">
							<DiscoverabilityTopIssues checks={ diagnosticsChecks } showBreakdown={ true } />
						</WorkspaceCard>
						</div>
					) : null }

					<div className="asneris-workspace-detail-wrap">{ renderDetailPanel() }</div>

					<div className="asneris-workspace-footer">
						{ __( 'Scoring model', 'asneris-seo-toolkit' ) }: { `shared evaluator ${ EVALUATOR_VERSION }` } | { __( 'Data source', 'asneris-seo-toolkit' ) }: { DISCOVERABILITY_DATA_SOURCES.seoReview } | { __( 'Issues', 'asneris-seo-toolkit' ) }: { openIssueCount }
					</div>
				</div>
			</div>
		</div>
	);
};

export default DiscoverabilityWorkspace;

