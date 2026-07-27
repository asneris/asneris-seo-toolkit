import { __, sprintf } from '@wordpress/i18n';

export const EVALUATOR_VERSION = 'v1';

export const stripHtml = ( value ) =>
	String( value || '' ).replace( /<[^>]*>/g, ' ' );

const normalizeText = ( value ) =>
	stripHtml( value )
		.toLowerCase()
		.replace( /\s+/g, ' ' )
		.trim();

const clamp = ( value, min, max ) => Math.min( Math.max( value, min ), max );

export const getWordCount = ( value ) =>
	stripHtml( value )
		.trim()
		.split( /\s+/ )
		.filter( Boolean ).length;

export const getImageCount = ( html ) =>
	( String( html || '' ).match( /<img\b[^>]*>/gi ) || [] ).length;

export const getInternalLinks = ( html, origin ) => {
	const links = Array.from(
		String( html || '' ).matchAll( /href\s*=\s*['\"]([^'\"]+)['\"]/gi )
	).map( ( match ) => String( match[ 1 ] || '' ).trim() );

	return links.filter( ( href ) => {
		if ( href.startsWith( '/' ) || href.startsWith( '#' ) ) {
			return true;
		}

		try {
			const parsed = new URL( href, origin );
			return parsed.origin === origin;
		} catch {
			return false;
		}
	} );
};

export const getScoreTone = ( score ) => {
	if ( score >= 80 ) {
		return 'success';
	}

	if ( score >= 55 ) {
		return 'warning';
	}

	return 'error';
};

export const getCheckTone = ( status ) => {
	if ( status === 'pass' ) {
		return 'success';
	}

	if ( status === 'warn' || status === 'warning' ) {
		return 'warning';
	}

	return 'error';
};

export const evaluateSeoReadiness = ( {
	postTitle,
	postExcerpt,
	meta,
	content,
	origin,
} ) => {
	const excerptText =
		typeof postExcerpt === 'string'
			? postExcerpt
			: postExcerpt?.rendered || postExcerpt?.raw || '';
	const effectiveTitle = String( meta?._ASNERISSEO_title || postTitle || '' ).trim();
	const effectiveDescription = String(
		meta?._ASNERISSEO_description || stripHtml( excerptText ) || ''
	).trim();
	const canonical = String( meta?._ASNERISSEO_canonical || '' ).trim();
	const robotsIndex = String( meta?._ASNERISSEO_robots_index || 'index' ).trim();
	const robotsFollow = String( meta?._ASNERISSEO_robots_follow || 'follow' ).trim();
	const hasCanonical = canonical.length > 0;
	const httpStatusRaw = Number( meta?._ASNERISSEO_http_status );
	const httpStatus = Number.isFinite( httpStatusRaw ) && httpStatusRaw > 0 ? httpStatusRaw : 0;
	const normalizedContent = String( content || '' );

	const wordCount = getWordCount( normalizedContent );
	const hasHeading = /<h[1-6][^>]*>/i.test( normalizedContent );
	const images = normalizedContent.match( /<img\b[^>]*>/gi ) || [];
	const imageCount = images.length;
	const imageWithAltCount = images.filter( ( imageTag ) =>
		/alt\s*=\s*['\"][^'\"]+['\"]/i.test( imageTag )
	).length;
	const imagesMissingAlt = Math.max( 0, imageCount - imageWithAltCount );
	const altCoverage =
		imageCount === 0 ? 100 : Math.round( ( imageWithAltCount / imageCount ) * 100 );
	const internalLinks = getInternalLinks( normalizedContent, origin );

	const checks = [
		{
			label: __( 'SEO title quality', 'asneris-seo-toolkit' ),
			canonicalField: 'SEO Title Length',
			rawEvidenceFields: [ 'metaTitleLength' ],
			status:
				effectiveTitle.length >= 30 && effectiveTitle.length <= 60
					? 'pass'
					: effectiveTitle.length > 0
					? 'warning'
					: 'fail',
			detail:
				effectiveTitle.length >= 30 && effectiveTitle.length <= 60
					? __( 'Title is within the recommended range.', 'asneris-seo-toolkit' )
					: __( 'Target 30-60 characters for the title.', 'asneris-seo-toolkit' ),
			points:
				effectiveTitle.length >= 30 && effectiveTitle.length <= 60
					? 10
					: effectiveTitle.length > 0
					? 5
					: 0,
			maxPoints: 10,
		},
		{
			label: __( 'Meta description quality', 'asneris-seo-toolkit' ),
			canonicalField: 'Meta Description Length',
			rawEvidenceFields: [ 'effectiveDescriptionLength' ],
			status:
				effectiveDescription.length >= 120 && effectiveDescription.length <= 160
					? 'pass'
					: effectiveDescription.length > 0
					? 'warning'
					: 'fail',
			detail:
				effectiveDescription.length >= 120 && effectiveDescription.length <= 160
					? __( 'Description length is on target.', 'asneris-seo-toolkit' )
					: __(
						'Target 120-160 characters for the description.',
						'asneris-seo-toolkit'
					  ),
			points:
				effectiveDescription.length >= 120 && effectiveDescription.length <= 160
					? 10
					: effectiveDescription.length > 0
					? 5
					: 0,
			maxPoints: 10,
		},
		{
			label: __( 'Canonical URL', 'asneris-seo-toolkit' ),
			canonicalField: 'Canonical',
			rawEvidenceFields: [ 'hasCanonical' ],
			status: hasCanonical ? 'pass' : 'warning',
			detail: hasCanonical
				? __( 'Canonical URL is present.', 'asneris-seo-toolkit' )
				: __( 'Set a canonical URL for this page.', 'asneris-seo-toolkit' ),
			points: 0,
			maxPoints: 0,
		},
		{
			label: __( 'Robots meta directives', 'asneris-seo-toolkit' ),
			canonicalField: 'Robots Meta',
			rawEvidenceFields: [ 'robotsIndex', 'robotsFollow' ],
			status:
				robotsIndex === 'index' && robotsFollow === 'follow'
					? 'pass'
					: 'warning',
			detail:
				robotsIndex === 'index' && robotsFollow === 'follow'
					? __( 'Robots directives are index/follow.', 'asneris-seo-toolkit' )
					: __( 'Update robots directives to index/follow for crawlable pages.', 'asneris-seo-toolkit' ),
			points: robotsIndex === 'index' && robotsFollow === 'follow' ? 20 : 10,
			maxPoints: 20,
		},
		{
			label: __( 'HTTP status', 'asneris-seo-toolkit' ),
			canonicalField: 'HTTP Status',
			rawEvidenceFields: [ 'httpStatus' ],
			status:
				httpStatus >= 200 && httpStatus < 300
					? 'pass'
					: httpStatus >= 300 && httpStatus < 400
					? 'warning'
					: 'fail',
			detail:
				httpStatus >= 200 && httpStatus < 300
					? __( 'HTTP status is healthy (2xx).', 'asneris-seo-toolkit' )
					: __( 'Ensure this URL resolves with HTTP 200.', 'asneris-seo-toolkit' ),
			points:
				httpStatus >= 200 && httpStatus < 300
					? 30
					: httpStatus >= 300 && httpStatus < 400
					? 15
					: 0,
			maxPoints: 30,
		},
		{
			label: __( 'H1 presence', 'asneris-seo-toolkit' ),
			canonicalField: 'H1 Presence',
			rawEvidenceFields: [ 'hasHeading' ],
			status: hasHeading ? 'pass' : 'warning',
			detail: hasHeading
				? __( 'At least one heading exists in content.', 'asneris-seo-toolkit' )
				: __( 'Add headings to improve scanning and structure.', 'asneris-seo-toolkit' ),
			points: hasHeading ? 10 : 2,
			maxPoints: 10,
		},
		{
			label: __( 'Image ALT coverage', 'asneris-seo-toolkit' ),
			canonicalField: 'Image ALT Coverage',
			rawEvidenceFields: [ 'imageCount', 'imagesMissingAlt' ],
			status:
				imageCount === 0
					? 'warning'
					: altCoverage >= 80
					? 'pass'
					: 'warning',
			detail:
				imageCount === 0
					? __( 'No images found; ALT coverage is neutral.', 'asneris-seo-toolkit' )
					: altCoverage >= 80
					? __( 'Image ALT coverage is healthy.', 'asneris-seo-toolkit' )
					: __( 'Improve ALT coverage to at least 80%.', 'asneris-seo-toolkit' ),
			points: 0,
			maxPoints: 0,
		},
		{
			label: __( 'Internal links', 'asneris-seo-toolkit' ),
			canonicalField: 'Internal Links',
			rawEvidenceFields: [ 'internalLinks' ],
			status:
				internalLinks.length >= 2
					? 'pass'
					: internalLinks.length === 1
					? 'warning'
					: 'fail',
			detail:
				internalLinks.length >= 2
					? __( 'Internal linking depth looks healthy.', 'asneris-seo-toolkit' )
					: __(
						'Add at least 2 internal links to strengthen crawl paths.',
						'asneris-seo-toolkit'
					  ),
			points: internalLinks.length >= 2 ? 10 : internalLinks.length === 1 ? 6 : 2,
			maxPoints: 10,
		},
		{
			label: __( 'Content depth', 'asneris-seo-toolkit' ),
			canonicalField: 'Content Depth (Word Count)',
			rawEvidenceFields: [ 'contentWords' ],
			status: wordCount >= 300 ? 'pass' : 'warning',
			detail:
				wordCount >= 300
					? __( 'Content depth is healthy.', 'asneris-seo-toolkit' )
					: __( 'Expand content depth to at least 300 words where appropriate.', 'asneris-seo-toolkit' ),
			points: wordCount >= 300 ? 10 : 5,
			maxPoints: 10,
		},
	];

	const score = clamp(
		Math.round( checks.reduce( ( total, check ) => total + check.points, 0 ) ),
		0,
		100
	);
	const warnings = checks.filter( ( check ) => check.status !== 'pass' );
	const issueRecords = warnings.map( ( check ) => ( {
		run_id: 'editor-seo-overview',
		canonical_field: check.canonicalField || check.label,
		canonical_status:
			check.status === 'pass'
				? 'pass'
				: check.status === 'fail'
				? 'fail'
				: 'warning',
		linked_raw_evidence_fields: Array.isArray( check.rawEvidenceFields )
			? check.rawEvidenceFields
			: [],
		score_impact: Math.max( 0, ( check.maxPoints || check.points || 0 ) - ( check.points || 0 ) ),
		recommended_fix: check.detail || '',
	} ) );

	return {
		score,
		checks,
		warnings,
		issueRecords,
		stats: {
			wordCount,
			imageCount,
			imagesMissingAlt,
			altCoverage,
			internalLinkCount: internalLinks.length,
		},
		effectiveTitle,
		effectiveDescription,
		canonical,
		robotsIndex,
		robotsFollow,
	};
};

const extractHeadings = ( html ) => {
	const matches = Array.from(
		String( html || '' ).matchAll( /<h([1-6])[^>]*>(.*?)<\/h\1>/gi )
	);

	return matches.map( ( match ) => ( {
		level: Number( match[ 1 ] ),
		text: stripHtml( match[ 2 ] ).trim(),
	} ) );
};

const getKeywordStats = ( text ) => {
	const stopWords = new Set( [
		'the',
		'and',
		'for',
		'with',
		'that',
		'this',
		'from',
		'your',
		'have',
		'will',
		'into',
		'about',
		'page',
		'content',
	] );

	const words = String( text || '' )
		.split( /[^a-z0-9]+/ )
		.filter( ( word ) => word.length >= 4 && ! stopWords.has( word ) );

	const map = new Map();
	words.forEach( ( word ) => {
		map.set( word, ( map.get( word ) || 0 ) + 1 );
	} );

	let topWord = '';
	let topCount = 0;
	map.forEach( ( count, word ) => {
		if ( count > topCount ) {
			topWord = word;
			topCount = count;
		}
	} );

	return {
		topWord,
		topCount,
		totalWords: words.length,
		ratio: words.length > 0 ? topCount / words.length : 0,
	};
};

export const evaluateAiDiscoverability = ( { content, siteName, origin } ) => {
	const contentHtml = String( content || '' );
	const contentText = stripHtml( contentHtml );
	const lowerText = normalizeText( contentText );
	const headings = extractHeadings( contentHtml );
	const headingLevels = headings.map( ( heading ) => heading.level );
	const hasH1 = headingLevels.includes( 1 );
	const hasList = /<(ul|ol)\b/i.test( contentHtml );
	const hasTable = /<table\b/i.test( contentHtml );
	const wordCount = lowerText.trim() ? lowerText.trim().split( /\s+/ ).length : 0;
	const keywordStats = getKeywordStats( lowerText );
	const internalLinkCount = getInternalLinks( contentHtml, origin ).length;
	const imageCount = getImageCount( contentHtml );

	const sentences = lowerText
		.split( /[.!?]+/ )
		.map( ( sentence ) => sentence.trim() )
		.filter( Boolean );
	const avgSentenceLength =
		sentences.length > 0
			? Math.round(
					sentences.reduce(
						( total, sentence ) => total + sentence.split( /\s+/ ).length,
						0
					) / sentences.length
			  )
			: 0;

	const hierarchyValid =
		headingLevels.length === 0 ||
		headingLevels.every( ( level, index ) => {
			if ( index === 0 ) {
				return true;
			}

			return Math.abs( level - headingLevels[ index - 1 ] ) <= 2;
		} );

	const checks = [
		{
			label: __( 'H1 present', 'asneris-seo-toolkit' ),
			status: hasH1 ? 'pass' : 'warn',
			detail: hasH1
				? __( 'Primary page heading detected.', 'asneris-seo-toolkit' )
				: __( 'Add a clear H1 heading.', 'asneris-seo-toolkit' ),
			points: hasH1 ? 8 : 3,
			group: 'structure',
		},
		{
			label: __( 'Heading hierarchy', 'asneris-seo-toolkit' ),
			status: hierarchyValid ? 'pass' : 'warn',
			detail: hierarchyValid
				? __( 'Heading levels follow a readable structure.', 'asneris-seo-toolkit' )
				: __( 'Avoid large heading level jumps.', 'asneris-seo-toolkit' ),
			points: hierarchyValid ? 8 : 4,
			group: 'structure',
		},
		{
			label: __( 'Sections coverage', 'asneris-seo-toolkit' ),
			status: headings.length >= 3 ? 'pass' : 'warn',
			detail:
				headings.length >= 3
					? __( 'Multiple sections detected.', 'asneris-seo-toolkit' )
					: __( 'Add more section headings for discoverability.', 'asneris-seo-toolkit' ),
			points: headings.length >= 3 ? 8 : 4,
			group: 'structure',
		},
		{
			label: __( 'List usage', 'asneris-seo-toolkit' ),
			status: hasList ? 'pass' : 'warn',
			detail: hasList
				? __( 'List structures were detected.', 'asneris-seo-toolkit' )
				: __( 'Use bullet or numbered lists for key points.', 'asneris-seo-toolkit' ),
			points: hasList ? 5 : 2,
			group: 'structure',
		},
		{
			label: __( 'Table usage', 'asneris-seo-toolkit' ),
			status: hasTable ? 'pass' : 'warn',
			detail: hasTable
				? __( 'Tabular data is present.', 'asneris-seo-toolkit' )
				: __( 'Consider tables for structured comparisons.', 'asneris-seo-toolkit' ),
			points: hasTable ? 4 : 2,
			group: 'structure',
		},
		{
			label: __( 'Clear page purpose', 'asneris-seo-toolkit' ),
			status: wordCount >= 180 && headings.length >= 1 ? 'pass' : 'warn',
			detail:
				wordCount >= 180 && headings.length >= 1
					? __( 'Purpose appears clear from headings and depth.', 'asneris-seo-toolkit' )
					: __(
						'Expand intro and heading context to clarify page purpose.',
						'asneris-seo-toolkit'
					  ),
			points: wordCount >= 180 && headings.length >= 1 ? 7 : 3,
			group: 'clarity',
		},
		{
			label: __( 'Topic consistency', 'asneris-seo-toolkit' ),
			status:
				keywordStats.topCount >= 3 &&
				keywordStats.ratio >= 0.03 &&
				keywordStats.ratio <= 0.16
					? 'pass'
					: 'warn',
			detail:
				keywordStats.topCount >= 3 &&
				keywordStats.ratio >= 0.03 &&
				keywordStats.ratio <= 0.16
					? __( 'Core topic terms are consistent.', 'asneris-seo-toolkit' )
					: __( 'Reinforce core topic terms naturally.', 'asneris-seo-toolkit' ),
			points:
				keywordStats.topCount >= 3 &&
				keywordStats.ratio >= 0.03 &&
				keywordStats.ratio <= 0.16
					? 7
					: 3,
			group: 'clarity',
		},
		{
			label: __( 'Summary section', 'asneris-seo-toolkit' ),
			status: /(summary|in summary|conclusion|tl;dr)/i.test( lowerText ) ? 'pass' : 'warn',
			detail: /(summary|in summary|conclusion|tl;dr)/i.test( lowerText )
				? __( 'Summary language detected.', 'asneris-seo-toolkit' )
				: __( 'Add a concise summary section.', 'asneris-seo-toolkit' ),
			points: /(summary|in summary|conclusion|tl;dr)/i.test( lowerText ) ? 5 : 2,
			group: 'clarity',
		},
		{
			label: __( 'Readability', 'asneris-seo-toolkit' ),
			status: avgSentenceLength >= 8 && avgSentenceLength <= 24 ? 'pass' : 'warn',
			detail:
				avgSentenceLength >= 8 && avgSentenceLength <= 24
					? __( 'Sentence length is readable.', 'asneris-seo-toolkit' )
					: __( 'Aim for medium-length sentences for clarity.', 'asneris-seo-toolkit' ),
			points: avgSentenceLength >= 8 && avgSentenceLength <= 24 ? 6 : 3,
			group: 'clarity',
		},
		{
			label: __( 'Brand mentions', 'asneris-seo-toolkit' ),
			status:
				siteName && lowerText.includes( String( siteName ).toLowerCase() )
					? 'pass'
					: 'warn',
			detail:
				siteName && lowerText.includes( String( siteName ).toLowerCase() )
					? __( 'Brand/entity mention found.', 'asneris-seo-toolkit' )
					: __( 'Add clear brand or product entity mentions.', 'asneris-seo-toolkit' ),
			points:
				siteName && lowerText.includes( String( siteName ).toLowerCase() )
					? 6
					: 2,
			group: 'entities',
		},
		{
			label: __( 'Product/context mentions', 'asneris-seo-toolkit' ),
			status: /(plugin|toolkit|product|service|platform)/i.test( lowerText ) ? 'pass' : 'warn',
			detail: /(plugin|toolkit|product|service|platform)/i.test( lowerText )
				? __( 'Product context is present.', 'asneris-seo-toolkit' )
				: __( 'Add product/service context terms.', 'asneris-seo-toolkit' ),
			points: /(plugin|toolkit|product|service|platform)/i.test( lowerText ) ? 5 : 2,
			group: 'entities',
		},
		{
			label: __( 'FAQ signals', 'asneris-seo-toolkit' ),
			status: /(faq|frequently asked)/i.test( lowerText ) ? 'pass' : 'warn',
			detail: /(faq|frequently asked)/i.test( lowerText )
				? __( 'FAQ signal detected.', 'asneris-seo-toolkit' )
				: __( 'Add FAQ content to answer common questions.', 'asneris-seo-toolkit' ),
			points: /(faq|frequently asked)/i.test( lowerText ) ? 7 : 2,
			group: 'knowledge',
		},
		{
			label: __( 'Definitions/examples/how-to', 'asneris-seo-toolkit' ),
			status:
				/(defined as|means|for example|e\.g\.|how to|step-by-step|steps)/i.test( lowerText )
					? 'pass'
					: 'warn',
			detail:
				/(defined as|means|for example|e\.g\.|how to|step-by-step|steps)/i.test(
					lowerText
				)
					? __( 'Knowledge helper patterns detected.', 'asneris-seo-toolkit' )
					: __( 'Add definitions, examples, or how-to guidance.', 'asneris-seo-toolkit' ),
			points:
				/(defined as|means|for example|e\.g\.|how to|step-by-step|steps)/i.test(
					lowerText
				)
					? 7
					: 3,
			group: 'knowledge',
		},
		{
			label: __( 'Trust signals', 'asneris-seo-toolkit' ),
			status:
				/(author|written by|contact|support|about|updated|last updated|20\d\d)/i.test(
					lowerText
				)
					? 'pass'
					: 'warn',
			detail:
				/(author|written by|contact|support|about|updated|last updated|20\d\d)/i.test(
					lowerText
				)
					? __( 'Basic trust markers are present.', 'asneris-seo-toolkit' )
					: __(
						'Add author, contact, about, or updated-date references.',
						'asneris-seo-toolkit'
					  ),
			points:
				/(author|written by|contact|support|about|updated|last updated|20\d\d)/i.test(
					lowerText
				)
					? 10
					: 4,
			group: 'trust',
		},
		{
			label: __( 'Content completeness', 'asneris-seo-toolkit' ),
			status:
				wordCount >= 300 && internalLinkCount >= 2 && imageCount >= 1
					? 'pass'
					: 'warn',
			detail:
				wordCount >= 300 && internalLinkCount >= 2 && imageCount >= 1
					? __( 'Content depth and support assets look complete.', 'asneris-seo-toolkit' )
					: __( 'Increase depth, links, and supporting media.', 'asneris-seo-toolkit' ),
			points:
				wordCount >= 300 && internalLinkCount >= 2 && imageCount >= 1
					? 15
					: 8,
			group: 'completeness',
		},
	];

	const score = clamp(
		Math.round( checks.reduce( ( total, check ) => total + check.points, 0 ) ),
		0,
		100
	);
	const warnings = checks.filter( ( check ) => check.status !== 'pass' );

	return {
		score,
		checks,
		warnings,
		stats: {
			wordCount,
			headingCount: headings.length,
			internalLinkCount,
			imageCount,
			avgSentenceLength,
			primaryKeyword: keywordStats.topWord,
		},
	};
};

export const getIssueCount = ( seoResult, aiResult ) => {
	const readinessIssueCount = Math.min( 3, seoResult.warnings.length );
	const aiIssueCount = Math.min( 3, aiResult.warnings.length );
	return readinessIssueCount + aiIssueCount;
};

export const getSearchRecommendations = ( seoResult ) =>
	seoResult.warnings
		.filter( ( warning ) =>
			[
				__( 'SEO title quality', 'asneris-seo-toolkit' ),
				__( 'Meta description quality', 'asneris-seo-toolkit' ),
				__( 'Canonical URL', 'asneris-seo-toolkit' ),
			].includes( warning.label )
		)
		.map( ( warning ) => warning.detail );

export const getAiRecommendations = ( aiResult ) =>
	aiResult.warnings.slice( 0, 5 ).map( ( warning ) => warning.detail );

export const getInternalLinkRecommendations = ( seoResult, suggestedPosts ) => {
	const recommendations = [];
	const internalWarning = seoResult.warnings.find(
		( warning ) => warning.label === __( 'Internal links', 'asneris-seo-toolkit' )
	);

	if ( internalWarning ) {
		recommendations.push( internalWarning.detail );
	}

	if ( ( suggestedPosts || [] ).length > 0 ) {
		recommendations.push(
			__( 'Consider linking to these related published posts:', 'asneris-seo-toolkit' )
		);
	}

	return recommendations;
};
