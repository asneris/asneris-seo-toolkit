import { __ } from '@wordpress/i18n';
import { Button, TextControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { PanelHeaderBadge, SectionBox } from '../../components/ui';
import SidebarSectionShell from './SidebarSectionShell';

const stripHtml = ( value ) => {
	if ( ! value ) {
		return '';
	}

	const input = String( value );

	if ( typeof window !== 'undefined' && typeof window.DOMParser !== 'undefined' ) {
		try {
			const parser = new window.DOMParser();
			const doc = parser.parseFromString( input, 'text/html' );

			// Remove nodes that should never contribute to excerpt text.
			doc.querySelectorAll( 'script,style,noscript,template,svg,canvas,iframe,object' ).forEach( ( node ) => {
				node.remove();
			} );

			const walker = doc.createTreeWalker( doc.body || doc, window.NodeFilter.SHOW_COMMENT );
			const comments = [];
			let current = walker.nextNode();
			while ( current ) {
				comments.push( current );
				current = walker.nextNode();
			}
			comments.forEach( ( commentNode ) => commentNode.parentNode?.removeChild( commentNode ) );

			return ( doc.body?.textContent || '' ).replace( /\s+/g, ' ' ).trim();
		} catch {
			// Fall through to regex fallback below.
		}
	}

	// Fallback for environments where DOMParser is unavailable.
	return input
		.replace( /<!--[\s\S]*?-->/g, ' ' )
		.replace( /<(script|style|noscript|template)[\s\S]*?<\/\1>/gi, ' ' )
		.replace( /<[^>]*>/g, ' ' )
		.replace( /\s+/g, ' ' )
		.trim();
};

const getExcerptSourceText = ( postExcerpt, postContent = '' ) => {
	const excerptText =
		typeof postExcerpt === 'string'
			? postExcerpt
			: postExcerpt?.rendered || postExcerpt?.raw || '';

	const cleanedExcerpt = stripHtml( excerptText );
	if ( cleanedExcerpt ) {
		return cleanedExcerpt;
	}

	return stripHtml( postContent );
};

const truncateText = ( value, maxLength ) => {
	if ( ! value ) {
		return '';
	}

	if ( value.length <= maxLength ) {
		return value;
	}

	return `${ value.slice( 0, maxLength - 1 ).trim() }…`;
};

const trimToLength = ( value, maxLength, minWordBoundary = 30 ) => {
	const normalized = String( value || '' ).replace( /\s+/g, ' ' ).trim();
	if ( ! normalized ) {
		return '';
	}

	if ( normalized.length <= maxLength ) {
		return normalized;
	}

	const sliced = normalized.slice( 0, maxLength ).trim();
	const lastSpaceIndex = sliced.lastIndexOf( ' ' );

	if ( lastSpaceIndex >= minWordBoundary ) {
		return sliced.slice( 0, lastSpaceIndex ).trim();
	}

	return sliced;
};

const trimDescriptionToLength = ( value, maxLength = 160 ) => {
	const normalized = String( value || '' ).replace( /\s+/g, ' ' ).trim();
	if ( ! normalized ) {
		return '';
	}

	if ( normalized.length <= maxLength ) {
		return normalized;
	}

	const sliced = normalized.slice( 0, maxLength + 1 ).trim();
	const punctuationMatch = sliced.match( /[.!?](?=[^.!?]*$)/ );

	if ( punctuationMatch?.index && punctuationMatch.index >= 120 ) {
		return sliced.slice( 0, punctuationMatch.index + 1 ).trim();
	}

	const safeSlice = sliced.slice( 0, maxLength );
	const lastSpaceIndex = safeSlice.lastIndexOf( ' ' );
	if ( lastSpaceIndex >= 100 ) {
		return safeSlice.slice( 0, lastSpaceIndex ).trim();
	}

	return safeSlice.trim();
};

const escapeRegExp = ( value ) =>
	String( value || '' ).replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );

const includesIgnoreCase = ( text, phrase ) => {
	const source = String( text || '' ).trim();
	const target = String( phrase || '' ).trim();

	if ( ! source || ! target ) {
		return false;
	}

	return new RegExp( escapeRegExp( target ), 'i' ).test( source );
};

const fallbackSlug = ( titleValue ) => {
	const normalized = String( titleValue || '' )
		.toLowerCase()
		.trim()
		.replace( /[^a-z0-9\s-]/g, '' )
		.replace( /\s+/g, '-' )
		.replace( /-+/g, '-' );

	return normalized || 'sample-page';
};

const sanitizeSlug = ( value ) =>
	String( value || '' )
		.toLowerCase()
		.trim()
		.replace( /[^a-z0-9\s-]/g, '' )
		.replace( /\s+/g, '-' )
		.replace( /-+/g, '-' );

const sanitizeCanonicalUrl = ( value ) => {
	const input = String( value || '' ).trim();
	if ( ! input ) {
		return '';
	}

	try {
		const parsed = new URL( input, window.location.origin );
		if ( parsed.protocol !== 'http:' && parsed.protocol !== 'https:' ) {
			return '';
		}
		return parsed.href;
	} catch {
		return input;
	}
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

// Approximate Google snippet pixel width to avoid clipping in previews.
const estimatePixelWidth = ( value ) => {
	if ( ! value ) {
		return 0;
	}

	let total = 0;
	const narrowChars = 'fijltI1|';
	const wideChars = 'MW@#%&';

	for ( const char of value ) {
		if ( char === ' ' ) {
			total += 4;
		} else if ( narrowChars.includes( char ) ) {
			total += 5;
		} else if ( wideChars.includes( char ) ) {
			total += 10;
		} else {
			total += 8;
		}
	}

	return total;
};

const getLengthStatus = ( length, optimalRange ) => {
	if ( length === 0 ) {
		return {
			label: __( 'Missing', 'asneris-seo-toolkit' ),
			tone: 'error',
		};
	}

	if ( length < optimalRange.min ) {
		return {
			label: __( 'Too short', 'asneris-seo-toolkit' ),
			tone: 'warning',
		};
	}

	if ( length > optimalRange.max ) {
		return {
			label: __( 'Too long', 'asneris-seo-toolkit' ),
			tone: 'warning',
		};
	}

	return {
		label: __( 'Optimal', 'asneris-seo-toolkit' ),
		tone: 'success',
	};
};

const getPixelStatus = ( width, maxWidth ) => {
	if ( width <= maxWidth ) {
		return {
			label: __( 'Within limit', 'asneris-seo-toolkit' ),
			tone: 'success',
		};
	}

	return {
		label: __( 'May truncate', 'asneris-seo-toolkit' ),
		tone: 'warning',
	};
};

const SearchAppearancePanel = ( {
	activeRoute,
	expandedSection,
	onExpand,
	seoTitleValue,
	seoDescriptionValue,
	onOpenWorkflow,
	onAiSuggestionsClick,
	onManagerCancel,
	MetaFieldComponent,
	CharacterCountComponent,
	managerMode = false,
	mobilePreviewOnly = false,
} ) => {
	const MetaField = MetaFieldComponent;
	const CharacterCount = CharacterCountComponent;
	const [ previewDevice, setPreviewDevice ] = useState(
		mobilePreviewOnly ? 'mobile' : 'desktop'
	);

	useEffect( () => {
		if ( mobilePreviewOnly && previewDevice !== 'mobile' ) {
			setPreviewDevice( 'mobile' );
		}
	}, [ mobilePreviewOnly, previewDevice ] );
	const { editPost, savePost } = useDispatch( 'core/editor' );
	const postTitle = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'title' )
	);
	const postType = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostType() || 'post'
	);
	const postExcerpt = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'excerpt' )
	);
	const postContent = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostContent() || ''
	);
	const postSlug = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'slug' )
	);
	const postDate = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'date' )
	);
	const canonicalValue = useSelect( ( select ) => {
		const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' );
		return meta?._ASNERISSEO_canonical || '';
	} );

	const normalizedTitle = ( seoTitleValue || '' ).trim();
	const normalizedDescription = ( seoDescriptionValue || '' ).trim();
	const normalizedCanonical = String( canonicalValue || '' ).trim();
	const excerptValue = getExcerptSourceText( postExcerpt, postContent );
	const variableContext = {
		title: String( postTitle || '' ).trim(),
		site: String( window.asnerisseoData?.siteName || '' ).trim(),
		separator: String( window.asnerisseoData?.titleSeparator || '|' ),
		excerpt: truncateText( stripHtml( excerptValue ), 160 ),
		date: String( postDate || '' ).split( 'T' )[ 0 ] || getTodayIsoDate(),
		author: String( window.asnerisseoData?.authorName || '' ).trim(),
		term: String( window.asnerisseoData?.primaryTerm || '' ).trim(),
	};
	const resolvedManualTitle = resolveInlineVariables( normalizedTitle, variableContext );
	const titleTemplates = window.asnerisseoData?.titleTemplates || {};
	const defaultTitleTemplate = String( titleTemplates?.[ postType ] || '' ).trim();
	const resolvedDefaultTitle = resolveInlineVariables(
		defaultTitleTemplate,
		variableContext
	);
	const hasConfiguredTitle = !!( resolvedManualTitle || resolvedDefaultTitle );
	const resolvedManualDescription = resolveInlineVariables(
		normalizedDescription,
		variableContext
	);
	const descriptionTemplates = window.asnerisseoData?.descriptionTemplates || {};
	const defaultDescriptionTemplate = String(
		descriptionTemplates?.[ postType ] || ''
	).trim();
	const resolvedDefaultDescription = resolveInlineVariables(
		defaultDescriptionTemplate,
		variableContext
	);
	const isUsingDefaultTitleTemplate = ! resolvedManualTitle && !! resolvedDefaultTitle;
	const isUsingDefaultDescriptionTemplate =
		! resolvedManualDescription && !! resolvedDefaultDescription;
	const hasConfiguredDescription = !!(
		resolvedManualDescription ||
		resolvedDefaultDescription
	);

	const effectiveTitle =
		resolvedManualTitle ||
		resolvedDefaultTitle ||
		String( postTitle || '' ).trim() ||
		__( 'Untitled post', 'asneris-seo-toolkit' );
	const effectiveDescription =
		resolvedManualDescription ||
		resolvedDefaultDescription ||
		truncateText( stripHtml( excerptValue ), 160 ) ||
		__(
			'Add a custom meta description to control your search snippet.',
			'asneris-seo-toolkit'
		);
	const effectiveSlug =
		String( postSlug || '' ).trim() || fallbackSlug( postTitle );
	const previewUrl =
		normalizedCanonical ||
		`${ window.location.origin }/${ effectiveSlug }`;
	const supportedTitleVariablesNote = __(
		'Preferred format: {title}. Supported variables: {title}, {site}, {separator}, {date}, {author}, {term}.',
		'asneris-seo-toolkit'
	);
	const supportedDescriptionVariablesNote = __(
		'Preferred format: {excerpt}. Supported variables: {excerpt}, {title}, {site}, {separator}, {date}, {author}, {term}.',
		'asneris-seo-toolkit'
	);

	const titleWidth = estimatePixelWidth( effectiveTitle );
	const descriptionWidth = estimatePixelWidth( effectiveDescription );
	const titleLengthStatus = getLengthStatus( effectiveTitle.length, {
		min: 30,
		max: 60,
	} );
	const descriptionLengthStatus = getLengthStatus( effectiveDescription.length, {
		min: 120,
		max: 160,
	} );
	const slugLengthStatus = getLengthStatus( effectiveSlug.length, {
		min: 10,
		max: 75,
	} );
	const titlePixelStatus = getPixelStatus( titleWidth, 580 );
	const descriptionPixelStatus = getPixelStatus( descriptionWidth, 920 );
	const usesCanonicalOverride = !! normalizedCanonical;
	const manualSlugValue = String( postSlug || '' ).trim();
	const manualCanonicalValue = String( normalizedCanonical || '' ).trim();
	const isSlugAuto = ! manualSlugValue;
	const isCanonicalAuto = ! manualCanonicalValue;
	const isTitleAuto = ! normalizedTitle;
	const isDescriptionAuto = ! normalizedDescription;
	const [ activeActionButtons, setActiveActionButtons ] = useState( {
		title: isTitleAuto ? 'auto' : 'manual',
		description: isDescriptionAuto ? 'auto' : 'manual',
		canonical: isCanonicalAuto ? 'auto' : 'manual',
	} );

	useEffect( () => {
		setActiveActionButtons( ( current ) => ( {
			...current,
			title: isTitleAuto ? 'auto' : 'manual',
			description: isDescriptionAuto ? 'auto' : 'manual',
			canonical: isCanonicalAuto ? 'auto' : 'manual',
		} ) );
	}, [ isTitleAuto, isDescriptionAuto, isCanonicalAuto ] );

	const getActionButtonClassName = ( group, action ) => {
		const baseClassName =
			'ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-small ASNERISSEO-react-button-action-toggle';

		if ( activeActionButtons[ group ] === action ) {
			return `${ baseClassName } ASNERISSEO-react-button-action-active`;
		}

		return baseClassName;
	};
	const titleTemplateLabel = defaultTitleTemplate || '{title}';
	const isMobilePreview = previewDevice === 'mobile';
	const forceMobilePreview = Boolean( mobilePreviewOnly );
	const previewCardStyle = {
		border: '1px solid #dcdcde',
		borderRadius: isMobilePreview ? '12px' : '6px',
		padding: isMobilePreview ? '12px' : '10px',
		background: '#fff',
		maxWidth: forceMobilePreview ? '100%' : isMobilePreview ? '360px' : 'none',
		boxShadow: isMobilePreview
			? '0 2px 8px rgba(15, 23, 42, 0.08)'
			: 'none',
	};
	const previewTitleStyle = {
		fontSize: isMobilePreview ? 'var(--asneris-h3-size)' : 'var(--asneris-h2-size)',
		lineHeight: isMobilePreview ? '24px' : '26px',
		color: '#1a0dab',
		marginBottom: isMobilePreview ? '4px' : '2px',
	};
	const previewUrlStyle = {
		fontSize: isMobilePreview ? 'var(--asneris-helper-size)' : 'var(--asneris-body-size)',
		lineHeight: isMobilePreview ? '18px' : '20px',
		color: '#006621',
		marginBottom: isMobilePreview ? '6px' : '4px',
		wordBreak: 'break-all',
	};
	const previewDescriptionStyle = {
		fontSize: isMobilePreview ? 'var(--asneris-helper-size)' : 'var(--asneris-body-size)',
		lineHeight: isMobilePreview ? '18px' : '20px',
		color: '#4d5156',
	};
	const previewTemplateBadgeStyle = {
		display: 'inline-flex',
		alignItems: 'center',
		minHeight: '22px',
		padding: '0 8px',
		borderRadius: '999px',
		border: '1px solid #bfd8ff',
		background: '#eef5ff',
		color: '#1f4f9a',
		fontSize: 'var(--asneris-table-chip-size)',
		fontWeight: 'var(--asneris-h3-weight)',
		lineHeight: 'var(--asneris-h3-line)',
	};
	const appearanceChecks = [
		{ key: 'titleLength', ok: titleLengthStatus.tone === 'success', weight: 20 },
		{ key: 'titleWidth', ok: titlePixelStatus.tone === 'success', weight: 10 },
		{ key: 'descriptionLength', ok: descriptionLengthStatus.tone === 'success', weight: 20 },
		{ key: 'descriptionWidth', ok: descriptionPixelStatus.tone === 'success', weight: 10 },
		{ key: 'slugLength', ok: slugLengthStatus.tone !== 'error', weight: 15 },
		{ key: 'hasSeoTitle', ok: hasConfiguredTitle, weight: 10 },
		{ key: 'hasMetaDescription', ok: hasConfiguredDescription, weight: 10 },
		{ key: 'canonical', ok: ! usesCanonicalOverride || !! normalizedCanonical, weight: 5 },
	];
	const appearanceScore = Math.max(
		0,
		Math.min(
			100,
			appearanceChecks.reduce(
				( total, check ) => total + ( check.ok ? check.weight : 0 ),
				0
			)
		)
	);
	const scoreLabel =
		appearanceScore >= 80
			? __( 'Good', 'asneris-seo-toolkit' )
			: __( 'Needs work', 'asneris-seo-toolkit' );
	const scoreColor =
		appearanceScore >= 80
			? '#46b450'
			: appearanceScore >= 50
			? '#dba617'
			: '#d63638';
	const scoreCircleStyle = {
		width: '92px',
		height: '92px',
		borderRadius: '50%',
		background: `conic-gradient(${ scoreColor } ${ appearanceScore * 3.6 }deg, #e2e4e7 0deg)`,
		display: 'flex',
		alignItems: 'center',
		justifyContent: 'center',
	};
	const quickFixes = [];

	if ( ! hasConfiguredDescription ) {
		quickFixes.push( {
			id: 'add-description',
			severity: 'error',
			label: __( 'Add a meta description', 'asneris-seo-toolkit' ),
			detail: __( 'Meta description is missing.', 'asneris-seo-toolkit' ),
			action: __( 'Fix it', 'asneris-seo-toolkit' ),
			onClick: () =>
				editPost( {
					meta: {
						_ASNERISSEO_description: stripHtml( excerptValue ).slice( 0, 160 ),
					},
				} ),
		} );
	}

	if ( titleLengthStatus.tone !== 'success' ) {
		quickFixes.push( {
			id: 'title-length',
			severity: 'warning',
			label: __( 'SEO title length needs tuning', 'asneris-seo-toolkit' ),
			detail: __( 'Keep title between 30 and 60 characters.', 'asneris-seo-toolkit' ),
			action: __( 'Fix it', 'asneris-seo-toolkit' ),
			onClick: () =>
				editPost( {
					meta: {
						_ASNERISSEO_title: String( postTitle || '' ).trim().slice( 0, 60 ),
					},
				} ),
		} );
	}

	if ( ! usesCanonicalOverride ) {
		quickFixes.push( {
			id: 'canonical',
			severity: 'success',
			label: __( 'Add canonical URL override', 'asneris-seo-toolkit' ),
			detail: __( 'Optional, but useful for canonical control.', 'asneris-seo-toolkit' ),
			action: __( 'Add', 'asneris-seo-toolkit' ),
			onClick: () =>
				editPost( {
					meta: {
						_ASNERISSEO_canonical: `${ window.location.origin }/${ effectiveSlug }`,
					},
				} ),
		} );
	}

	let appearanceTone = 'warning';
	let appearanceLabel = __( 'Needs tuning', 'asneris-seo-toolkit' );

	if (
		titleLengthStatus.tone === 'success' &&
		titlePixelStatus.tone === 'success'
	) {
		appearanceTone = 'info';
		appearanceLabel = __( 'Good title', 'asneris-seo-toolkit' );
	}

	if (
		titleLengthStatus.tone === 'success' &&
		descriptionLengthStatus.tone === 'success' &&
		titlePixelStatus.tone === 'success' &&
		descriptionPixelStatus.tone === 'success' &&
		slugLengthStatus.tone !== 'error'
	) {
		appearanceTone = 'success';
		appearanceLabel = __( 'Ready snippet', 'asneris-seo-toolkit' );
	}

	const handleResetToDefaults = () => {
		editPost( {
			meta: {
				_ASNERISSEO_title: '',
				_ASNERISSEO_description: '',
				_ASNERISSEO_canonical: '',
			},
		} );
	};

	const handleSaveChanges = () => {
		savePost();
	};

	const handleUseAutoSlug = () => {
		editPost( { slug: '' } );
	};

	const handleUseAutoCanonical = () => {
		editPost( {
			meta: {
				_ASNERISSEO_canonical: '',
			},
		} );
		setActiveActionButtons( ( current ) => ( {
			...current,
			canonical: 'auto',
		} ) );
	};

	const handleSetCanonicalValue = () => {
		const generatedCanonical = sanitizeCanonicalUrl(
			`${ window.location.origin }/${ effectiveSlug }`
		);

		editPost( {
			meta: {
				_ASNERISSEO_canonical: generatedCanonical,
			},
		} );
		setActiveActionButtons( ( current ) => ( {
			...current,
			canonical: 'manual',
		} ) );
	};

	const handleUseAutoTitle = () => {
		editPost( {
			meta: {
				_ASNERISSEO_title: '',
			},
		} );
		setActiveActionButtons( ( current ) => ( {
			...current,
			title: 'auto',
		} ) );
	};

	const handleUseAutoDescription = () => {
		editPost( {
			meta: {
				_ASNERISSEO_description: '',
			},
		} );
		setActiveActionButtons( ( current ) => ( {
			...current,
			description: 'auto',
		} ) );
	};

	const handleSmartGenerateTitle = () => {
		const separatorToken = String( variableContext.separator || '|' ).trim() || '|';
		const baseTitle = String( postTitle || '' ).trim();
		const primaryTerm = String( variableContext.term || '' ).trim();
		const siteName = String( variableContext.site || '' ).trim();
		const minTitleLength = 30;
		const fallbackSuffix =
			postType === 'page'
				? __( 'Official Page', 'asneris-seo-toolkit' )
				: __( 'Complete Guide', 'asneris-seo-toolkit' );

		let smartTitle = baseTitle || primaryTerm || __( 'Untitled post', 'asneris-seo-toolkit' );
		if ( primaryTerm && baseTitle && ! includesIgnoreCase( baseTitle, primaryTerm ) ) {
			smartTitle = `${ primaryTerm } - ${ baseTitle }`;
		}

		let regeneratedTitle = trimToLength( smartTitle, 60 );

		if ( regeneratedTitle.length < minTitleLength ) {
			const enrichers = [ siteName, fallbackSuffix ].filter( Boolean );
			enrichers.forEach( ( item ) => {
				if ( regeneratedTitle.length >= minTitleLength ) {
					return;
				}

				if ( includesIgnoreCase( regeneratedTitle, item ) ) {
					return;
				}

				const expanded = trimToLength(
					`${ regeneratedTitle } ${ separatorToken } ${ item }`,
					60
				);

				if ( expanded.length > regeneratedTitle.length ) {
					regeneratedTitle = expanded;
				}
			} );
		}

		if ( regeneratedTitle.length < minTitleLength ) {
			const resilientBase = baseTitle || primaryTerm || __( 'SEO Friendly Title', 'asneris-seo-toolkit' );
			regeneratedTitle = trimToLength(
				siteName
					? `${ resilientBase } ${ separatorToken } ${ siteName }`
					: resilientBase,
				60
			);
		}

		editPost( {
			meta: {
				_ASNERISSEO_title: regeneratedTitle,
			},
		} );
		setActiveActionButtons( ( current ) => ( {
			...current,
			title: 'manual',
		} ) );
	};

	const handleSmartGenerateDescription = () => {
		const excerptFallback = stripHtml( excerptValue );
		const primaryTerm = String( variableContext.term || '' ).trim();
		const composedFallbackDescription =
			excerptFallback ||
			String( postTitle || '' ).trim() ||
			__(
				'Discover practical insights and SEO improvements for this page.',
				'asneris-seo-toolkit'
			);

		let regeneratedDescription = trimDescriptionToLength(
			composedFallbackDescription,
			160
		);
		if ( primaryTerm && ! includesIgnoreCase( regeneratedDescription, primaryTerm ) ) {
			regeneratedDescription = trimDescriptionToLength(
				`${ primaryTerm }: ${ regeneratedDescription }`,
				160
			);
		}

		editPost( {
			meta: {
				_ASNERISSEO_description: regeneratedDescription,
			},
		} );
		setActiveActionButtons( ( current ) => ( {
			...current,
			description: 'manual',
		} ) );
	};

	const managerInsightsSection = managerMode ? (
		<SectionBox>
			<div
				style={ {
					display: 'grid',
					gridTemplateColumns: '1.1fr 1.1fr 0.8fr',
					gap: '12px',
					alignItems: 'stretch',
				} }
			>
				<div
					style={ {
						border: '1px solid #dcdcde',
						borderRadius: '8px',
						padding: '12px',
						background: '#fff',
						minHeight: '200px',
						display: 'flex',
						flexDirection: 'column',
					} }
				>
					<strong>{ __( 'SEO Score', 'asneris-seo-toolkit' ) }</strong>
					<div style={ { display: 'flex', alignItems: 'center', gap: '10px', marginTop: '10px' } }>
						<div style={ scoreCircleStyle }>
							<div
								style={ {
									width: '70px',
									height: '70px',
									borderRadius: '50%',
									background: '#fff',
									display: 'flex',
									flexDirection: 'column',
									alignItems: 'center',
									justifyContent: 'center',
								} }
							>
								<span style={ { fontSize: 'var(--asneris-h1-size)', lineHeight: 'var(--asneris-h3-line)', fontWeight: 'var(--asneris-h1-weight)' } }>{ appearanceScore }</span>
								<span style={ { fontSize: 'var(--asneris-table-chip-size)', color: '#646970' } }>/100</span>
							</div>
						</div>
						<div>
							<div style={ { fontSize: 'var(--asneris-h1-size)', color: scoreColor, lineHeight: 'var(--asneris-h3-line)' } }>{ scoreLabel }</div>
							<p style={ { margin: '6px 0 0 0', color: '#50575e', fontSize: 'var(--asneris-helper-size)' } }>
								{ __(
									'Well done! Some minor improvements can make your content even better.',
									'asneris-seo-toolkit'
								) }
							</p>
						</div>
					</div>
				</div>

				<div
					style={ {
						border: '1px solid #dcdcde',
						borderRadius: '8px',
						padding: '12px',
						background: '#fff',
						minHeight: '200px',
						display: 'flex',
						flexDirection: 'column',
					} }
				>
					<strong>
						{ __( 'Quick Fixes', 'asneris-seo-toolkit' ) } ({ quickFixes.length })
					</strong>
					<div style={ { display: 'grid', gap: '8px', marginTop: '10px', flex: 1 } }>
						{ quickFixes.slice( 0, 3 ).map( ( item ) => (
							<div
								key={ item.id }
								style={ {
									display: 'flex',
									justifyContent: 'space-between',
									gap: '8px',
								} }
							>
								<div>
									<div
										className="ASNERISSEO-react-field-label"
										style={ { fontSize: 'var(--asneris-helper-size)', display: 'flex', alignItems: 'center', gap: '6px' } }
									>
										<span
											style={ {
												width: '8px',
												height: '8px',
												borderRadius: '50%',
												background:
													item.severity === 'error'
														? '#d63638'
														: item.severity === 'warning'
														? '#dba617'
														: '#46b450',
											} }
										/>
										{ item.label }
									</div>
									<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#50575e' } }>{ item.detail }</div>
								</div>
							</div>
						) ) }
						{ quickFixes.length === 0 ? (
							<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#46b450' } }>
								{ __( 'No immediate fixes needed.', 'asneris-seo-toolkit' ) }
							</div>
						) : null }
					</div>
				</div>

				<div
					style={ {
						border: '1px solid #dcdcde',
						borderRadius: '8px',
						padding: '12px',
						background: '#fff',
						minHeight: '200px',
						display: 'flex',
						flexDirection: 'column',
					} }
				>
					<strong>{ __( 'How it works', 'asneris-seo-toolkit' ) }</strong>
					<p style={ { fontSize: 'var(--asneris-helper-size)', color: '#50575e', marginTop: '8px' } }>
						{ __(
							'This is how your page may appear in search engine results. Make sure your title and description are clear, relevant, and engaging.',
							'asneris-seo-toolkit'
						) }
					</p>
					<div
						style={ {
							fontSize: 'var(--asneris-table-chip-size)',
							color: '#1d4ed8',
							background: '#eff6ff',
							border: '1px solid #bfdbfe',
							borderRadius: '6px',
							padding: '8px 10px',
							marginTop: 'auto',
						} }
					>
						{ __(
							'Tip: Changes update your search preview in real time.',
							'asneris-seo-toolkit'
						) }
					</div>
				</div>
			</div>
		</SectionBox>
	) : null;

	if ( managerMode ) {
		return (
			<div style={ { display: 'grid', gap: '8px' } }>
				<SectionBox>
					<div style={ { display: 'grid', gap: '12px' } }>
						<div style={ { fontSize: 'var(--asneris-h2-size)', fontWeight: 'var(--asneris-h1-weight)', color: '#10233f' } }>
							{ __( 'Search Engine Preview', 'asneris-seo-toolkit' ) }
						</div>

						<div
							style={ {
								display: 'flex',
								justifyContent: 'space-between',
								alignItems: 'center',
								gap: '10px',
								flexWrap: 'wrap',
							} }
						>
							{ ! forceMobilePreview ? (
								<div
									style={ {
										display: 'inline-flex',
										border: '1px solid #dcdcde',
										borderRadius: '6px',
										overflow: 'hidden',
									} }
								>
									<button
										type="button"
										onClick={ () => setPreviewDevice( 'desktop' ) }
										style={ {
											border: 'none',
											background: previewDevice === 'desktop' ? '#f0f6ff' : '#fff',
											color: previewDevice === 'desktop' ? '#3858e9' : '#50575e',
											padding: '6px 10px',
											fontSize: 'var(--asneris-table-chip-size)',
											fontWeight: 'var(--asneris-h3-weight)',
											cursor: 'pointer',
										} }
									>
										{ __( 'Desktop', 'asneris-seo-toolkit' ) }
									</button>
									<button
										type="button"
										onClick={ () => setPreviewDevice( 'mobile' ) }
										style={ {
											border: 'none',
											borderLeft: '1px solid #dcdcde',
											background: previewDevice === 'mobile' ? '#f0f6ff' : '#fff',
											color: previewDevice === 'mobile' ? '#3858e9' : '#50575e',
											padding: '6px 10px',
											fontSize: 'var(--asneris-table-chip-size)',
											fontWeight: 'var(--asneris-h3-weight)',
											cursor: 'pointer',
										} }
									>
										{ __( 'Mobile', 'asneris-seo-toolkit' ) }
									</button>
								</div>
							) : (
								<span
									style={ {
										display: 'inline-flex',
										alignItems: 'center',
										border: '1px solid #bfdbfe',
										borderRadius: '999px',
										padding: '4px 10px',
										fontSize: 'var(--asneris-table-chip-size)',
										fontWeight: 'var(--asneris-h3-weight)',
										background: '#eff6ff',
										color: '#1d4ed8',
									} }
								>
									{ __( 'Mobile preview', 'asneris-seo-toolkit' ) }
								</span>
							) }

						</div>

						<div key={ previewDevice } style={ previewCardStyle }>
							<div style={ previewUrlStyle }>{ previewUrl }</div>
							<div style={ previewTitleStyle }>{ effectiveTitle }</div>
							<div style={ previewDescriptionStyle }>{ effectiveDescription }</div>
							<div style={ { marginTop: '10px', fontSize: 'var(--asneris-table-chip-size)', color: '#16a34a', fontWeight: 'var(--asneris-h3-weight)' } }>
								{ __( 'Preview updated just now', 'asneris-seo-toolkit' ) }
							</div>
						</div>
					</div>
				</SectionBox>

				<SectionBox>
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px', marginBottom: '8px', flexWrap: 'wrap' } }>
						<div style={ { fontWeight: 'var(--asneris-h1-weight)' } }>{ __( 'SEO Title', 'asneris-seo-toolkit' ) }</div>
						<div style={ { display: 'inline-flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' } }>
							<Button
								variant="secondary"
								className={ getActionButtonClassName( 'title', 'manual' ) }
								onClick={ handleSmartGenerateTitle }
							>
								<span
									className="dashicons dashicons-edit"
									aria-hidden="true"
									style={ { fontSize: '14px', width: '14px', height: '14px', marginRight: '6px' } }
								/>
								{ __( 'Edit Manually', 'asneris-seo-toolkit' ) }
							</Button>
							<Button
								variant="secondary"
								className={ getActionButtonClassName( 'title', 'auto' ) }
								onClick={ handleUseAutoTitle }
							>
								<span
									className="dashicons dashicons-update"
									aria-hidden="true"
									style={ { fontSize: '14px', width: '14px', height: '14px', marginRight: '6px' } }
								/>
								{ __( 'Use Automatic', 'asneris-seo-toolkit' ) }
							</Button>
						</div>
					</div>
					<MetaField
						label={ __( 'SEO TITLE', 'asneris-seo-toolkit' ) }
						metaKey="_ASNERISSEO_title"
						placeholder="Title is generated using Site Template Settings"
						help={ __( '', 'asneris-seo-toolkit' ) }
					/>
					<div style={ { marginTop: '10px' } }>
						<CharacterCount
							text={ seoTitleValue }
							targetLength={ 60 }
							warningLimit={ 80 }
							validationProfile="searchTitle"
						/>
					</div>
				</SectionBox>

				<SectionBox>
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px', marginBottom: '8px', flexWrap: 'wrap' } }>
						<div style={ { fontWeight: 'var(--asneris-h1-weight)' } }>{ __( 'Meta Description', 'asneris-seo-toolkit' ) }</div>
						<div style={ { display: 'inline-flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' } }>
							<Button
								variant="secondary"
								className={ getActionButtonClassName( 'description', 'manual' ) }
								onClick={ handleSmartGenerateDescription }
							>
								<span
									className="dashicons dashicons-edit"
									aria-hidden="true"
									style={ { fontSize: '14px', width: '14px', height: '14px', marginRight: '6px' } }
								/>
								{ __( 'Edit Manually', 'asneris-seo-toolkit' ) }
							</Button>
							<Button
								variant="secondary"
								className={ getActionButtonClassName( 'description', 'auto' ) }
								onClick={ handleUseAutoDescription }
							>
								<span
									className="dashicons dashicons-update"
									aria-hidden="true"
									style={ { fontSize: '14px', width: '14px', height: '14px', marginRight: '6px' } }
								/>
								{ __( 'Use Automatic', 'asneris-seo-toolkit' ) }
							</Button>
						</div>
					</div>
					<MetaField
						label={ __( 'META DESCRIPTION', 'asneris-seo-toolkit' ) }
						metaKey="_ASNERISSEO_description"
						type="textarea"
						placeholder="Description is generated using Site Template Settings"
						help={ __( '', 'asneris-seo-toolkit' ) }
					/>
					<div style={ { marginTop: '10px' } }>
						<CharacterCount
							text={ seoDescriptionValue }
							targetLength={ 160 }
							warningLimit={ 180 }
							validationProfile="searchDescription"
						/>
					</div>
				</SectionBox>

				<SectionBox>
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px', marginBottom: '8px', flexWrap: 'wrap' } }>
						<div style={ { fontWeight: 'var(--asneris-h1-weight)' } }>{ __( 'URL Slug', 'asneris-seo-toolkit' ) }</div>
						<div />
					</div>
					<TextControl
						value={ manualSlugValue }
						onChange={ ( nextValue ) =>
							editPost( { slug: sanitizeSlug( nextValue ) } )
						}
						disabled={ true }
						placeholder={ __( 'Leave empty to use generated slug from title', 'asneris-seo-toolkit' ) }
					/>
					<div style={ { marginTop: '8px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '8px', flexWrap: 'wrap' } }>
						<div style={ { fontSize: 'var(--asneris-table-chip-size)', color: '#16a34a', fontWeight: 'var(--asneris-h3-weight)' } }>
							{ __( 'Looks good!', 'asneris-seo-toolkit' ) }
						</div>
						<div style={ { fontSize: 'var(--asneris-table-chip-size)', color: '#64748b', fontWeight: 'var(--asneris-h3-weight)' } }>
							{ manualSlugValue.length || effectiveSlug.length } / 75
						</div>
					</div>
				</SectionBox>

				<SectionBox>
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px', marginBottom: '8px', flexWrap: 'wrap' } }>
						<div style={ { fontWeight: 'var(--asneris-h1-weight)' } }>{ __( 'Canonical URL', 'asneris-seo-toolkit' ) }</div>
						<div style={ { display: 'inline-flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' } }>
							<Button
								variant="secondary"
								className={ getActionButtonClassName( 'canonical', 'manual' ) }
								onClick={ handleSetCanonicalValue }
							>
								{ __( 'Set Canonical', 'asneris-seo-toolkit' ) }
							</Button>
							{ ! isCanonicalAuto ? (
								<Button
									variant="secondary"
									className={ getActionButtonClassName( 'canonical', 'auto' ) }
									onClick={ handleUseAutoCanonical }
								>
									{ __( 'Use Automatic', 'asneris-seo-toolkit' ) }
								</Button>
							) : null }
						</div>
					</div>
					<TextControl
						value={ manualCanonicalValue }
						onChange={ ( nextValue ) =>
							editPost( {
								meta: {
									_ASNERISSEO_canonical: sanitizeCanonicalUrl( nextValue ),
								},
							} )
						}
						placeholder={ __( 'Leave empty to use generated canonical URL', 'asneris-seo-toolkit' ) }
					/>
					<div style={ { marginTop: '8px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '8px', flexWrap: 'wrap' } }>
						<div style={ { fontSize: 'var(--asneris-table-chip-size)', color: '#16a34a', fontWeight: 'var(--asneris-h3-weight)' } }>
							{ __( 'Looks good!', 'asneris-seo-toolkit' ) }
						</div>
						<div style={ { fontSize: 'var(--asneris-table-chip-size)', color: '#64748b', fontWeight: 'var(--asneris-h3-weight)' } }>
							{ manualCanonicalValue.length || previewUrl.length } / 200
						</div>
					</div>
				</SectionBox>

				
			</div>
		);
	}

	return (
		<SidebarSectionShell
			sectionKey="appearance"
			expandedSection={ expandedSection }
			onExpand={ onExpand }
			title={ managerMode ? null : __( 'Search Appearance', 'asneris-seo-toolkit' ) }
			headerDescription={
				managerMode
					? null
					: __( 'Title, meta description, URL, canonical and snippets', 'asneris-seo-toolkit' )
			}
			headerIcon={ managerMode ? null : 'dashicons dashicons-search' }
			headerIconStyle={ { background: '#f0f4ff', color: '#3858e9' } }
			initialOpen={ activeRoute === 'appearance' }
			headerAction={
				managerMode ? null : (
					<PanelHeaderBadge
						label={ appearanceLabel }
						tone={ appearanceTone }
					/>
				)
			}
		>
			<SectionBox>
				<div
					style={ {
						display: 'flex',
						justifyContent: 'space-between',
						alignItems: 'center',
						marginBottom: '10px',
					} }
				>
					<strong>
						{ __( 'Search Engine Review', 'asneris-seo-toolkit' ) }
					</strong>
					<div style={ { display: 'flex', gap: '8px', alignItems: 'center' } }>
						{ managerMode ? (
							<>
								<div
									style={ {
										display: 'inline-flex',
										border: '1px solid #dcdcde',
										borderRadius: '6px',
										overflow: 'hidden',
									} }
								>
									<button
										type="button"
										onClick={ () => setPreviewDevice( 'desktop' ) }
										style={ {
											border: 'none',
											background:
												previewDevice === 'desktop' ? '#f0f6ff' : '#fff',
											color:
												previewDevice === 'desktop' ? '#3858e9' : '#50575e',
											padding: '6px 10px',
											fontSize: 'var(--asneris-table-chip-size)',
											fontWeight: 'var(--asneris-h3-weight)',
											cursor: 'pointer',
										} }
									>
										{ __( 'Desktop', 'asneris-seo-toolkit' ) }
									</button>
									<button
										type="button"
										onClick={ () => setPreviewDevice( 'mobile' ) }
										style={ {
											border: 'none',
											borderLeft: '1px solid #dcdcde',
											background:
												previewDevice === 'mobile' ? '#f0f6ff' : '#fff',
											color:
												previewDevice === 'mobile' ? '#3858e9' : '#50575e',
											padding: '6px 10px',
											fontSize: 'var(--asneris-table-chip-size)',
											fontWeight: 'var(--asneris-h3-weight)',
											cursor: 'pointer',
										} }
									>
										{ __( 'Mobile', 'asneris-seo-toolkit' ) }
									</button>
								</div>
							</>
						) : null }
						<PanelHeaderBadge label={ appearanceLabel } tone={ appearanceTone } />
					</div>
				</div>
				<p style={ { fontSize: 'var(--asneris-helper-size)', color: '#50575e', margin: '0 0 10px 0' } }>
					{ __(
						'This is how your page may appear in search engine results.',
						'asneris-seo-toolkit'
					) }
				</p>

				<div key={ previewDevice } style={ previewCardStyle }>
					{ isUsingDefaultTitleTemplate || isUsingDefaultDescriptionTemplate ? (
						<div style={ { display: 'flex', gap: '6px', flexWrap: 'wrap', marginBottom: '8px' } }>
							{ isUsingDefaultTitleTemplate ? (
								<span className="ASNERISSEO-react-preview-source-badge" style={ previewTemplateBadgeStyle }>
									{ __( 'Using default template (title)', 'asneris-seo-toolkit' ) }
								</span>
							) : null }
							{ isUsingDefaultDescriptionTemplate ? (
								<span className="ASNERISSEO-react-preview-source-badge" style={ previewTemplateBadgeStyle }>
									{ __( 'Using default template (description)', 'asneris-seo-toolkit' ) }
								</span>
							) : null }
						</div>
					) : null }
					<div style={ previewTitleStyle }>
						{ effectiveTitle }
					</div>
					<div style={ previewUrlStyle }>
						{ previewUrl }
					</div>
					<div style={ previewDescriptionStyle }>
						{ effectiveDescription }
					</div>
				</div>


			</SectionBox>

			<SectionBox>
				<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '8px', marginBottom: '12px', flexWrap: 'wrap' } }>
					<PanelHeaderBadge
						label={
							normalizedTitle
								? __( 'SEO title: manual', 'asneris-seo-toolkit' )
								: isUsingDefaultTitleTemplate
								? __( 'Using default title template', 'asneris-seo-toolkit' )
								: __( 'Using Page Title Automatically', 'asneris-seo-toolkit' )
						}
						tone={ normalizedTitle ? 'info' : 'warning' }
					/>
				</div>
				
				<MetaField
					label={ __( 'SEO TITLE', 'asneris-seo-toolkit' ) }
					metaKey="_ASNERISSEO_title"
					placeholder="Title is generated using Site Template Setting"
					help="Leave empty to use Templates tab title template (fallback: post title)"
					onInteractionRedirect={ ! managerMode ? onOpenWorkflow : undefined }
				/>
				<div style={ { marginTop: '14px' } }>
					<CharacterCount
						text={ seoTitleValue }
						targetLength={ 60 }
						warningLimit={ 80 }
						validationProfile="searchTitle"
					/>
				</div>
			</SectionBox>

			<SectionBox>
				<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '8px', marginBottom: '12px', flexWrap: 'wrap' } }>
					<PanelHeaderBadge
						label={
							normalizedDescription
								? __( 'Meta description: manual', 'asneris-seo-toolkit' )
								: isUsingDefaultDescriptionTemplate
								? __( 'Using default description template', 'asneris-seo-toolkit' )
								: __( 'Using Excerpt Automatically', 'asneris-seo-toolkit' )
						}
						tone={ normalizedDescription ? 'info' : 'warning' }
					/>
					<div />
				</div>
				 
				<MetaField
					label={ __( 'META DESCRIPTION', 'asneris-seo-toolkit' ) }
					metaKey="_ASNERISSEO_description"
					type="textarea"
					placeholder="Description is generated using Site Template Settings"
					help="Leave empty to use Templates tab description template (fallback: excerpt/content)"
					onInteractionRedirect={ ! managerMode ? onOpenWorkflow : undefined }
				/>
				<div style={ { marginTop: '14px' } }>
					<CharacterCount
						text={ seoDescriptionValue }
						targetLength={ 160 }
						warningLimit={ 180 }
						validationProfile="searchDescription"
					/>
				</div>
			</SectionBox>

			<SectionBox>
				{ managerMode ? (
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '8px', marginBottom: '12px', flexWrap: 'wrap' } }>
						<PanelHeaderBadge
							label={
								String( postSlug || '' ).trim()
									? __( 'Manual slug', 'asneris-seo-toolkit' )
									: __( 'Auto slug', 'asneris-seo-toolkit' )
							}
							tone={ String( postSlug || '' ).trim() ? 'info' : 'neutral' }
						/>
						<div />
					</div>
				) : null }
				<TextControl
					label={ __( 'SLUG', 'asneris-seo-toolkit' ) }
					value={ manualSlugValue }
					onChange={ ( nextValue ) => {
						if ( ! managerMode && onOpenWorkflow ) {
							onOpenWorkflow();
							return;
						}

						editPost( { slug: sanitizeSlug( nextValue ) } );
					} }
					placeholder={ __(
						'Leave empty to use generated slug from title',
						'asneris-seo-toolkit'
					) }
					help={ __(
						'Controls the URL path used in the search preview and post permalink.',
						'asneris-seo-toolkit'
					) }
				/>
				<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#64748b', marginTop: '-4px' } }>
					{ __( 'Recommended slug length: 10-75 characters (best: 30-60).', 'asneris-seo-toolkit' ) }
				</div>
			</SectionBox>

			{ managerInsightsSection }

			{ managerMode ? (
				<SectionBox>
					<div style={ { marginBottom: '10px' } }>
						<PanelHeaderBadge
							label={
								usesCanonicalOverride
									? __( 'Canonical: custom', 'asneris-seo-toolkit' )
									: __( 'Canonical: current URL', 'asneris-seo-toolkit' )
							}
							tone={ usesCanonicalOverride ? 'info' : 'neutral' }
						/>
					</div>
					<MetaField
						label={ __( 'Canonical URL', 'asneris-seo-toolkit' ) }
						metaKey="_ASNERISSEO_canonical"
						placeholder="https://example.com/canonical-url"
						help="Leave empty to use the current URL"
						onInteractionRedirect={ ! managerMode ? onOpenWorkflow : undefined }
					/>
				</SectionBox>
			) : (
				<SectionBox>
					<MetaField
						label={ __( 'Canonical URL', 'asneris-seo-toolkit' ) }
						metaKey="_ASNERISSEO_canonical"
						placeholder="https://example.com/canonical-url"
						help="Leave empty to use the current URL"
						onInteractionRedirect={ ! managerMode ? onOpenWorkflow : undefined }
					/>
				</SectionBox>
			) }

			{ managerMode ? (
				<SectionBox>
					<div
						style={ {
							display: 'flex',
							justifyContent: 'space-between',
							alignItems: 'center',
							gap: '8px',
							flexWrap: 'wrap',
						} }
					>
						<Button
							variant="secondary"
							className="ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
							onClick={ handleResetToDefaults }
						>
							{ __( 'Reset to Defaults', 'asneris-seo-toolkit' ) }
						</Button>
						<div style={ { display: 'flex', gap: '8px' } }>
							<Button
								variant="primary"
								className="ASNERISSEO-react-button ASNERISSEO-react-button-primary"
								onClick={ handleSaveChanges }
							>
								{ __( 'Save Changes', 'asneris-seo-toolkit' ) }
							</Button>
						</div>
					</div>
				</SectionBox>
			) : null }

			{ managerMode ? (
				<SectionBox>
					<div
						style={ {
							border: '1px solid #e5e7eb',
							background: '#fafafa',
							borderRadius: '8px',
							padding: '10px 12px',
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'space-between',
							gap: '10px',
							flexWrap: 'wrap',
						} }
					>
						<div>
							<div style={ { fontSize: 'var(--asneris-helper-size)', fontWeight: 'var(--asneris-h1-weight)' } }>
								{ __( 'Need help improving?', 'asneris-seo-toolkit' ) }
							</div>
							<div style={ { fontSize: 'var(--asneris-helper-size)', color: '#475569' } }>
								{ __( 'Get AI-powered suggestions to improve your content.', 'asneris-seo-toolkit' ) }
							</div>
						</div>
						<Button
							variant="primary"
							className="ASNERISSEO-react-button ASNERISSEO-react-button-primary"
							style={ { minHeight: '32px', fontSize: 'var(--asneris-table-chip-size)' } }
							onClick={ onAiSuggestionsClick }
						>
							<span
								className="dashicons dashicons-star-filled"
								aria-hidden="true"
								style={ { fontSize: 'var(--asneris-body-size)', width: '14px', height: '14px', marginRight: '6px' } }
							/>
							{ __( 'Get AI Suggestions', 'asneris-seo-toolkit' ) }
						</Button>
					</div>
				</SectionBox>
			) : null }
		</SidebarSectionShell>
	);
};

export default SearchAppearancePanel;

