import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	SelectControl,
	Button,
	ExternalLink,
	Notice,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import {
	useState,
	useEffect,
	useCallback,
	useMemo,
	createPortal,
} from '@wordpress/element';
import {
	AsnerisCard,
	StatusBadge,
	AlertMessage,
	LoadingState,
	ErrorState,
	AsnerisModal,
} from './components/ui';
import useSidebarRoute from './app/useSidebarRoute';
import OverviewPanel from './features/sidebar/OverviewPanel';
import DiagnosticsPanel from './features/sidebar/DiagnosticsPanel';
import SearchAppearancePanel from './features/sidebar/SearchAppearancePanel';
import SeoReadinessPanel from './features/sidebar/SeoReadinessPanel';
import AiDiscoverabilityPanel from './features/sidebar/AiDiscoverabilityPanel';
import RobotsMetaPanel from './features/sidebar/RobotsMetaPanel';
import SocialPanel from './features/sidebar/SocialPanel';
import SchemaPanel from './features/sidebar/SchemaPanel';
import IndexNowPanel from './features/sidebar/IndexNowPanel';
import PageDiagnosticsPanel from './admin/components/panels/PageDiagnosticsPanel';
import {
	evaluateAiDiscoverability,
	evaluateSeoReadiness,
	EVALUATOR_VERSION,
} from './features/sidebar/evaluatorUtils';
import { OVERVIEW_PRIMARY_FIELDS, normalizeOverviewFieldLabel } from './app/discoverabilityDataModel';
import { assertUnifiedData, getUnifiedChecks, getUnifiedComputed } from './app/unifiedDataModel';

const getRestBaseUrl = () => {
	const root = window.asnerisseoData?.restRoot || '/wp-json/';
	const namespace = window.asnerisseoData?.restNamespace || 'asneris-seo/v1';

	return `${ root.replace( /\/$/, '' ) }/${ namespace }`;
};

const isUnifiedContractError = ( payload ) => {
	const code = String( payload?.code || '' ).toLowerCase();
	return code.startsWith( 'asnerisseo_unified_contract_' );
};

const createRestError = ( payload, response ) => {
	let message = __( 'REST request failed.', 'asneris-seo-toolkit' );

	if ( isUnifiedContractError( payload ) ) {
		message = __( 'Unified diagnostics contract violation detected. Please update providers and refresh.', 'asneris-seo-toolkit' );
	} else if ( payload?.message ) {
		message = payload.message;
	} else if ( response?.statusText ) {
		message = response.statusText;
	}

	const error = new Error( message );
	error.code = payload?.code || null;
	error.status = Number( response?.status || 0 );
	error.details = payload?.data?.details || payload?.details || null;
	error.context = payload?.data?.context || payload?.context || null;

	return error;
};

const restGet = async ( path ) => {
	const restNonce = window.asnerisseoData?.restNonce || '';
	const response = await fetch( `${ getRestBaseUrl() }${ path }`, {
		method: 'GET',
		headers: {
			'X-WP-Nonce': restNonce,
		},
		credentials: 'same-origin',
	} );

	if ( ! response.ok ) {
		let errorPayload = null;
		try {
			errorPayload = await response.json();
		} catch {
			errorPayload = null;
		}

		throw createRestError( errorPayload || {}, response );
	}

	return response.json();
};

const restPost = async ( path, body = null ) => {
	const restNonce = window.asnerisseoData?.restNonce || '';
	const requestInit = {
		method: 'POST',
		headers: {
			'X-WP-Nonce': restNonce,
		},
		credentials: 'same-origin',
	};

	if ( body !== null ) {
		requestInit.headers[ 'Content-Type' ] = 'application/json';
		requestInit.body = JSON.stringify( body );
	}

	const response = await fetch( `${ getRestBaseUrl() }${ path }`, requestInit );

	if ( ! response.ok ) {
		let errorPayload = null;
		try {
			errorPayload = await response.json();
		} catch {
			errorPayload = null;
		}

		throw createRestError( errorPayload || {}, response );
	}

	return response.json();
};

const getDefaultEditorConfig = () => ( {
	siteName: window.asnerisseoData?.siteName || 'Site',
	titleSeparator: window.asnerisseoData?.titleSeparator || '|',
	titleTemplates: window.asnerisseoData?.titleTemplates || {},
	descriptionTemplates: window.asnerisseoData?.descriptionTemplates || {},
} );

const useEditorConfig = () => {
	const [ editorConfig, setEditorConfig ] = useState(
		getDefaultEditorConfig
	);

	useEffect( () => {
		let isMounted = true;

		restGet( '/editor-config' )
			.then( ( data ) => {
				if ( ! isMounted ) {
					return;
				}

				setEditorConfig( {
					siteName:
						data?.siteName || getDefaultEditorConfig().siteName,
					titleSeparator:
						data?.titleSeparator ||
						getDefaultEditorConfig().titleSeparator,
					titleTemplates:
						data?.titleTemplates ||
						getDefaultEditorConfig().titleTemplates,
					descriptionTemplates:
						data?.descriptionTemplates ||
						getDefaultEditorConfig().descriptionTemplates,
				} );
			} )
			.catch( () => {
				if ( ! isMounted ) {
					return;
				}

				setEditorConfig( getDefaultEditorConfig() );
			} );

		return () => {
			isMounted = false;
		};
	}, [] );

	return editorConfig;
};

// Template resolver helper (mirrors PHP parse behavior for sidebar preview only)
const resolveTemplate = ( template, context = {} ) => {
	if ( ! template ) {
		return '';
	}

	let output = template;
	Object.entries( context ).forEach( ( [ key, value ] ) => {
		output = output.split( `{${ key }}` ).join( value || '' );
	} );

	output = output.replace( /\{[^}]+\}/g, '' );
	output = output.replace( /\s+/g, ' ' ).trim();

	return output;
};

const getResolvedTemplateByMetaKey = (
	metaKey,
	postType,
	context,
	editorConfig = getDefaultEditorConfig()
) => {
	const titleTemplates = editorConfig.titleTemplates || {};
	const descriptionTemplates = editorConfig.descriptionTemplates || {};

	if ( metaKey === '_ASNERISSEO_title' ) {
		return resolveTemplate( titleTemplates[ postType ] || '', context );
	}

	if ( metaKey === '_ASNERISSEO_description' ) {
		return resolveTemplate(
			descriptionTemplates[ postType ] || '',
			context
		);
	}

	return '';
};

const ASNERIS_PLUGIN_ID = 'asneris-seo-sidebar';
const ASNERIS_SIDEBAR_SLUG = 'asneris-seo-sidebar-panel';
const ASNERIS_SIDEBAR_ID = `${ ASNERIS_PLUGIN_ID }/${ ASNERIS_SIDEBAR_SLUG }`;
const ASNERIS_SIDEBAR_FALLBACK_ID = ASNERIS_SIDEBAR_SLUG;
const ASNERIS_SIDEBAR_CLASS = 'asneris-seo-plugin-sidebar';
const ASNERIS_SIDEBAR_STYLE_ID = 'asneris-seo-sidebar-width-style';
const ASNERIS_INTERFACE_SCOPES = [ 'core' ];
const ASNERIS_RUNTIME_BUILD_MARKER = 'sidebar-debug-2026-06-25-r5';

const getHeaderSettingsContainer = () => {
	const selectors = [
		'.edit-post-header__settings',
		'.editor-header__settings',
	];

	for ( const selector of selectors ) {
		const element = document.querySelector( selector );
		if ( element ) {
			return element;
		}
	}

	return null;
};

const AsnerisTopRightLauncher = ( { seoScore, aiScore, onOpenToolkit } ) => {
	const [ settingsContainer, setSettingsContainer ] = useState( null );
	const [ useLogoFallback, setUseLogoFallback ] = useState( false );
	const [ isCompactToolbar, setIsCompactToolbar ] = useState( false );
	const logoUrl = window.asnerisseoData?.logoUrl || '';
	const hasSeoScore = Number.isFinite( Number( seoScore ) );
	const seoScoreLabel = hasSeoScore ? `${ Number( seoScore ) }%` : __( 'Loading', 'asneris-seo-toolkit' );

	useEffect( () => {
		const updateContainer = () => {
			setSettingsContainer( getHeaderSettingsContainer() );
			setIsCompactToolbar( window.innerWidth <= 782 );
		};

		updateContainer();

		const observer = new MutationObserver( updateContainer );
		observer.observe( document.body, {
			childList: true,
			subtree: true,
			attributes: true,
		} );

		window.addEventListener( 'resize', updateContainer );

		return () => {
			observer.disconnect();
			window.removeEventListener( 'resize', updateContainer );
		};
	}, [] );

	if ( ! settingsContainer ) {
		return null;
	}

	return createPortal(
		<button
			type="button"
			onClick={ onOpenToolkit }
			style={ {
				position: 'relative',
				zIndex: 2,
				display: 'inline-flex',
				alignItems: 'center',
				gap: isCompactToolbar ? '0' : '4px',
				padding: isCompactToolbar ? '6px' : '6px 10px 6px 8px',
				marginRight: isCompactToolbar ? '4px' : '8px',
				borderRadius: '999px',
				border: '1px solid #06295F',
				background: '#06295F',
				boxShadow: '0 1px 3px rgba(0,0,0,0.08)',
				cursor: 'pointer',
				minWidth: isCompactToolbar ? '32px' : 'auto',
				height: isCompactToolbar ? '32px' : 'auto',
			} }
			aria-label={ __( 'Open Asneris SEO Toolkit', 'asneris-seo-toolkit' ) }
			title={ __( 'Open Asneris SEO Toolkit', 'asneris-seo-toolkit' ) }
		>
			{ logoUrl && ! useLogoFallback ? (
				<img
					src={ logoUrl }
					alt={ __( 'Asneris logo', 'asneris-seo-toolkit' ) }
					style={ {
						width: '20px',
						height: '20px',
						borderRadius: '999px',
						objectFit: 'cover',
						display: 'block',
					} }
					onError={ () => setUseLogoFallback( true ) }
				/>
			) : (
				<span
					style={ {
						display: 'inline-flex',
						alignItems: 'center',
						justifyContent: 'center',
						width: '20px',
						height: '20px',
						borderRadius: '999px',
						background: '#111827',
						color: '#ffffff',
						fontSize: '10px',
						fontWeight: '700',
						letterSpacing: '0.2px',
					} }
				>
					AS
				</span>
			) }

			{ ! isCompactToolbar ? (
				<span
					style={ {
						fontSize: '11px',
						fontWeight: '600',
						color: '#ffffff',
						whiteSpace: 'nowrap',
					} }
				>
					{ seoScoreLabel }
				</span>
			) : (
				<span
					style={ {
						position: 'absolute',
						right: '-8px',
						top: '-6px',
						fontSize: '9px',
						fontWeight: '700',
						lineHeight: 1,
						padding: '2px 4px',
						borderRadius: '999px',
						background: '#06295F',
						border: '1px solid rgba(255, 255, 255, 0.45)',
						color: '#ffffff',
						pointerEvents: 'none',
					} }
				>
					{ seoScoreLabel }
				</span>
			) }
			{/* <span
				style={ {
					width: '1px',
					height: '14px',
					background: '#e5e7eb',
				} }
			/>
			<span
				style={ {
					fontSize: '11px',
					fontWeight: '600',
					color: '#1f2937',
					whiteSpace: 'nowrap',
				} }
			>
				{ __( 'AI ', 'asneris-seo-toolkit' ) } { aiScore }%
			</span> */}
		</button>,
		settingsContainer
	);
};

const isSidebarDebugEnabled = () => {
	try {
		return window.localStorage?.getItem( 'asneris:sidebar-debug' ) === '1';
	} catch {
		return false;
	}
};

const debugSidebar = ( label, payload = {} ) => {
	if ( ! isSidebarDebugEnabled() ) {
		return;
	}

	// eslint-disable-next-line no-console
	console.log( `[Asneris Sidebar] ${ label }`, payload );
};

const getActiveSidebarCandidates = ( select ) => {
	const interfaceStore = select( 'core/interface' );
	const editPostStore = select( 'core/edit-post' );

	const activeSidebarNameFromInterface = ASNERIS_INTERFACE_SCOPES.map(
		( scope ) => interfaceStore?.getActiveComplementaryArea?.( scope ) || ''
	);
	const activeGeneralSidebarName =
		editPostStore?.getActiveGeneralSidebarName?.() || '';

	return [ activeGeneralSidebarName, ...activeSidebarNameFromInterface ];
};

const IndexNowSubmit = () => {
	const [ isSubmitting, setIsSubmitting ] = useState( false );
	const [ notice, setNotice ] = useState( null );
	const postId = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostId()
	);
	const postStatus = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'status' )
	);

	const handleSubmit = () => {
		setIsSubmitting( true );
		setNotice( null );

		const ajaxurl =
			window.asnerisseoData?.ajaxurl ||
			window.ajaxurl ||
			'/wp-admin/admin-ajax.php';
		const nonce =
			window.asnerisseoData?.indexnowNonce ||
			window.ASNERISSEO_indexnow_nonce;

		fetch( ajaxurl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams( {
				action: 'ASNERISSEO_manual_indexnow',
				nonce,
				post_id: postId,
			} ),
		} )
			.then( ( response ) => response.json() )
			.then( ( data ) => {
				setIsSubmitting( false );
				if ( data.success ) {
					setNotice( {
						type: 'success',
						message: data.data.message,
					} );
				} else {
					setNotice( {
						type: 'error',
						message:
							data.data.message ||
							__( 'Failed to submit', 'asneris-seo-toolkit' ),
					} );
				}
				setTimeout( () => setNotice( null ), 5000 );
			} )
			.catch( () => {
				setIsSubmitting( false );
				setNotice( {
					type: 'error',
					message: __( 'Request failed', 'asneris-seo-toolkit' ),
				} );
				setTimeout( () => setNotice( null ), 5000 );
			} );
	};

	if ( postStatus !== 'publish' ) {
		return (
			<Notice status="warning" isDismissible={ false }>
				{ __(
					'Post must be published to submit to IndexNow',
					'asneris-seo-toolkit'
				) }
			</Notice>
		);
	}

	return (
		<>
			{ notice && (
				<Notice
					status={ notice.type }
					isDismissible={ false }
					style={ { marginBottom: '12px' } }
				>
					{ notice.message }
				</Notice>
			) }

			<p
				style={ {
					marginBottom: '12px',
					color: '#646970',
					fontSize: '13px',
				} }
			>
				{ __(
					'Manually notify search engines about this page update via IndexNow protocol.',
					'asneris-seo-toolkit'
				) }
			</p>

			<Button
				variant="primary"
				className="ASNERISSEO-react-button ASNERISSEO-react-button-primary"
				onClick={ handleSubmit }
				disabled={ isSubmitting }
				style={ { width: '100%' } }
			>
				{ isSubmitting
					? __( 'Submitting…', 'asneris-seo-toolkit' )
					: __( 'Submit to IndexNow', 'asneris-seo-toolkit' ) }
			</Button>

			<p
				style={ {
					marginTop: '12px',
					color: '#646970',
					fontSize: '12px',
					fontStyle: 'italic',
				} }
			>
				{ __(
					'Note: IndexNow must be enabled in plugin settings.',
					'asneris-seo-toolkit'
				) }
			</p>
		</>
	);
};

const DiagnosticsPreview = ( { onOpenReport } ) => {
	const postId = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostId()
	);
	const postStatus = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'status' )
	);
	const [ diagnostics, setDiagnostics ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( '' );

	const loadDiagnostics = useCallback( async () => {
		if ( ! postId || postStatus !== 'publish' ) {
			setDiagnostics( [] );
			setError( '' );
			return;
		}

		setIsLoading( true );
		setError( '' );

		try {
			const data = await restGet(
				`/page-diagnostics/overview?postId=${ encodeURIComponent( String( postId ) ) }&scope=all&perPage=1&page=1&postStatus=publish`
			);
			const firstItem = Array.isArray( data?.items ) ? data.items[ 0 ] : null;
			if ( firstItem ) {
				assertUnifiedData( firstItem, 'editor.preview.diagnostics.overview' );
				setDiagnostics( getUnifiedChecks( firstItem ) );
			} else {
				setDiagnostics( [] );
			}
		} catch ( requestError ) {
			setDiagnostics( [] );
			setError(
				requestError.message ||
					__( 'Unable to load diagnostics.', 'asneris-seo-toolkit' )
			);
		} finally {
			setIsLoading( false );
		}
	}, [ postId, postStatus ] );

	useEffect( () => {
		loadDiagnostics();
	}, [ loadDiagnostics ] );

	if ( postStatus !== 'publish' ) {
		return (
			<AlertMessage tone="info">
				{ __(
					'Publish the post to load live diagnostics from the REST API.',
					'asneris-seo-toolkit'
				) }
			</AlertMessage>
		);
	}

	const topChecks = diagnostics.slice( 0, 4 );

	return (
		<>
			{ error && <ErrorState message={ error } /> }

			{ ! error && isLoading && (
				<LoadingState
					label={ __(
						'Loading diagnostics…',
						'asneris-seo-toolkit'
					) }
				/>
			) }

			{ ! error && topChecks.length > 0 && (
				<div style={ { display: 'grid', gap: '8px' } }>
					{ topChecks.map( ( check, index ) => {
						let color = '#d63638';
						if ( check.status === 'pass' ) {
							color = '#46b450';
						} else if ( check.status === 'warning' ) {
							color = '#dba617';
						}

						return (
							<div
								key={ `${ check.label }-${ index }` }
								style={ {
									border: '1px solid #dcdcde',
									borderLeft: `4px solid ${ color }`,
									borderRadius: '4px',
									padding: '8px 10px',
									background: '#fff',
								} }
							>
								<div
									style={ {
										fontWeight: '600',
										marginBottom: '4px',
									} }
								>
									{ check.label }
								</div>
								<div
									style={ {
										fontSize: '12px',
										color: 'var(--asneris-text, #10233f)',
									} }
								>
									{ check.result }
								</div>
								<div
									style={ {
										fontSize: '12px',
										color: '#646970',
										marginTop: '2px',
									} }
								>
									{ check.details }
								</div>
							</div>
						);
					} ) }
				</div>
			) }

			{ ! error && ! isLoading && topChecks.length === 0 && (
				<AlertMessage tone="warning">
					{ __(
						'No diagnostics data is available yet for this post.',
						'asneris-seo-toolkit'
					) }
				</AlertMessage>
			) }

					<Button
						variant="primary"
						className="ASNERISSEO-react-button ASNERISSEO-react-button-primary"
						onClick={ () => ( onOpenReport ? onOpenReport( postId ) : loadDiagnostics() ) }
						disabled={ isLoading }
						style={ { marginTop: '12px', width: '100%' } }
					>
						{ isLoading
							? __( 'Loading diagnostics…', 'asneris-seo-toolkit' )
							: __( 'Run Diagnostics', 'asneris-seo-toolkit' ) }
					</Button>

		</>
	);
};

const MetaField = ( {
	label,
	metaKey,
	help,
	type = 'text',
	placeholder = '',
	onInteractionRedirect,
} ) => {
	const value = useSelect(
		( select ) =>
			select( 'core/editor' ).getEditedPostAttribute( 'meta' )[
				metaKey
			] || '',
		[ metaKey ]
	);
	const { editPost } = useDispatch( 'core/editor' );
	const [ validationError, setValidationError ] = useState( '' );

	const validateInput = ( inputValue ) => {
		// Clear error if empty (empty is always valid)
		if ( ! inputValue || inputValue.trim() === '' ) {
			setValidationError( '' );
			return true;
		}

		// Check for dangerous patterns first (applies to all fields)
		if (
			/<script|<\/script|javascript:|onerror=|onload=|<iframe|eval\(|data:text\/html/i.test(
				inputValue
			)
		) {
			setValidationError(
				__(
					'Content contains potentially dangerous patterns',
					'asneris-seo-toolkit'
				)
			);
			return false;
		}

		// Validate URLs for canonical and OG image
		if (
			metaKey === '_ASNERISSEO_canonical' ||
			metaKey === '_ASNERISSEO_og_image'
		) {
			// Additional URL-specific security checks
			if (
				/script|javascript|data:|vbscript:|file:|about:/i.test(
					inputValue
				)
			) {
				setValidationError(
					__(
						'URL contains potentially dangerous protocols or content',
						'asneris-seo-toolkit'
					)
				);
				return false;
			}

			try {
				const urlObj = new URL( inputValue );

				// Only allow http and https protocols
				if ( ! [ 'http:', 'https:' ].includes( urlObj.protocol ) ) {
					setValidationError(
						__(
							'Only HTTP and HTTPS URLs are allowed',
							'asneris-seo-toolkit'
						)
					);
					return false;
				}

				// Additional validation for OG image
				if ( metaKey === '_ASNERISSEO_og_image' ) {
					const allowedExtensions = [
						'jpg',
						'jpeg',
						'png',
						'gif',
						'webp',
						'svg',
						'bmp',
						'ico',
					];
					const pathname = urlObj.pathname;
					const extension = pathname
						.split( '.' )
						.pop()
						.toLowerCase()
						.split( '?' )[ 0 ]
						.split( '#' )[ 0 ];

					if ( ! allowedExtensions.includes( extension ) ) {
						setValidationError(
							__(
								'Image URL must have a valid image extension (jpg, png, gif, webp, svg)',
								'asneris-seo-toolkit'
							)
						);
						return false;
					}
				}

				setValidationError( '' );
				return true;
			} catch {
				if ( metaKey === '_ASNERISSEO_canonical' ) {
					setValidationError(
						__(
							'Canonical URL must be a valid URL (e.g., https://example.com/page)',
							'asneris-seo-toolkit'
						)
					);
				} else {
					setValidationError(
						__(
							'OG Image URL must be a valid URL (e.g., https://example.com/page)',
							'asneris-seo-toolkit'
						)
					);
				}
				return false;
			}
		}

		setValidationError( '' );
		return true;
	};

	const handleChange = ( inputValue ) => {
		if ( onInteractionRedirect ) {
			onInteractionRedirect();
			return;
		}

		validateInput( inputValue );
		editPost( { meta: { [ metaKey ]: inputValue } } );
	};

	const Component = type === 'textarea' ? TextareaControl : TextControl;

	return (
		<>
			<Component
				label={ label }
				value={ value }
				onChange={ handleChange }
				help={ help }
				placeholder={ placeholder }
				className={ validationError ? 'has-error' : '' }
			/>
			{ validationError && (
				<Notice
					status="error"
					isDismissible={ false }
					style={ { margin: '-12px 0 12px 0', padding: '8px 12px' } }
				>
					{ validationError }
				</Notice>
			) }
		</>
	);
};

const RobotsControl = ( { metaKey, label, options } ) => {
	const value = useSelect(
		( select ) =>
			select( 'core/editor' ).getEditedPostAttribute( 'meta' )[
				metaKey
			] || options[ 0 ].value,
		[ metaKey, options ]
	);
	const { editPost } = useDispatch( 'core/editor' );

	return (
		<SelectControl
			label={ label }
			value={ value }
			options={ options }
			onChange={ ( v ) => editPost( { meta: { [ metaKey ]: v } } ) }
		/>
	);
};

const CharacterCount = ( {
	text,
	targetLength,
	warningLimit,
	validationProfile = null,
} ) => {
	const length = text ? text.length : 0;

	let tone = 'good';
	let color = 'var(--asneris-success-text, #1d6f42)';
	let status = __( 'Good', 'asneris-seo-toolkit' );
	let progressLimit = warningLimit;

	if ( validationProfile === 'searchTitle' ) {
		progressLimit = 70;
		if ( length < 20 ) {
			tone = 'danger';
			color = 'var(--asneris-error-text, #b91c1c)';
			status = __( 'Too Short', 'asneris-seo-toolkit' );
		} else if ( length <= 29 ) {
			tone = 'good';
			color = 'var(--asneris-success-text, #1d6f42)';
			status = __( 'Good', 'asneris-seo-toolkit' );
		} else if ( length <= 60 ) {
			tone = 'good';
			color = 'var(--asneris-success-text, #1d6f42)';
			status = __( 'Excellent', 'asneris-seo-toolkit' );
		} else if ( length <= 70 ) {
			tone = 'warning';
			color = 'var(--asneris-warning-text, #996800)';
			status = __( 'Acceptable', 'asneris-seo-toolkit' );
		} else {
			tone = 'danger';
			color = 'var(--asneris-error-text, #b91c1c)';
			status = __( 'Too Long', 'asneris-seo-toolkit' );
		}
	} else if ( validationProfile === 'searchDescription' ) {
		progressLimit = 180;
		if ( length < 90 ) {
			tone = 'danger';
			color = 'var(--asneris-error-text, #b91c1c)';
			status = __( 'Too Short', 'asneris-seo-toolkit' );
		} else if ( length <= 119 ) {
			tone = 'good';
			color = 'var(--asneris-success-text, #1d6f42)';
			status = __( 'Good', 'asneris-seo-toolkit' );
		} else if ( length <= 160 ) {
			tone = 'good';
			color = 'var(--asneris-success-text, #1d6f42)';
			status = __( 'Excellent', 'asneris-seo-toolkit' );
		} else if ( length <= 180 ) {
			tone = 'warning';
			color = 'var(--asneris-warning-text, #996800)';
			status = __( 'Acceptable', 'asneris-seo-toolkit' );
		} else {
			tone = 'danger';
			color = 'var(--asneris-error-text, #b91c1c)';
			status = __( 'Too Long', 'asneris-seo-toolkit' );
		}
	} else if ( validationProfile === 'socialTitle' ) {
		progressLimit = 70;
		if ( length < 30 ) {
			tone = 'danger';
			color = 'var(--asneris-error-text, #b91c1c)';
			status = __( 'Too Short', 'asneris-seo-toolkit' );
		} else if ( length <= 39 ) {
			tone = 'good';
			color = 'var(--asneris-success-text, #1d6f42)';
			status = __( 'Good', 'asneris-seo-toolkit' );
		} else if ( length <= 60 ) {
			tone = 'good';
			color = 'var(--asneris-success-text, #1d6f42)';
			status = __( 'Excellent', 'asneris-seo-toolkit' );
		} else if ( length <= 70 ) {
			tone = 'warning';
			color = 'var(--asneris-warning-text, #996800)';
			status = __( 'Acceptable', 'asneris-seo-toolkit' );
		} else {
			tone = 'danger';
			color = 'var(--asneris-error-text, #b91c1c)';
			status = __( 'Too Long', 'asneris-seo-toolkit' );
		}
	} else if ( validationProfile === 'socialDescription' ) {
		progressLimit = 200;
		if ( length < 50 ) {
			tone = 'danger';
			color = 'var(--asneris-error-text, #b91c1c)';
			status = __( 'Too Short', 'asneris-seo-toolkit' );
		} else if ( length <= 69 ) {
			tone = 'good';
			color = 'var(--asneris-success-text, #1d6f42)';
			status = __( 'Good', 'asneris-seo-toolkit' );
		} else if ( length <= 140 ) {
			tone = 'good';
			color = 'var(--asneris-success-text, #1d6f42)';
			status = __( 'Excellent', 'asneris-seo-toolkit' );
		} else if ( length <= 200 ) {
			tone = 'warning';
			color = 'var(--asneris-warning-text, #996800)';
			status = __( 'Acceptable', 'asneris-seo-toolkit' );
		} else {
			tone = 'danger';
			color = 'var(--asneris-error-text, #b91c1c)';
			status = __( 'Too Long', 'asneris-seo-toolkit' );
		}
	} else if ( length > warningLimit ) {
		tone = 'danger';
		color = 'var(--asneris-error-text, #b91c1c)';
		status = __( 'Too long', 'asneris-seo-toolkit' );
	} else if ( length > targetLength ) {
		tone = 'warning';
		color = 'var(--asneris-warning-text, #996800)';
		status = __( 'Warning', 'asneris-seo-toolkit' );
	}

	const progress = Math.min( ( length / progressLimit ) * 100, 100 );

	const fillColor =
		tone === 'danger'
			? '#ef4444'
			: tone === 'warning'
				? '#f59e0b'
				: '#22c55e';

	const recommendedMessage =
		validationProfile === 'searchTitle'
			? __(
				'Recommended: 30-60 characters (Excellent), 20-29 (Good), 61-70 (Acceptable). Below 30 is often too short; above 60 may be truncated.',
				'asneris-seo-toolkit'
			)
			: validationProfile === 'searchDescription'
				? __(
					'Recommended: 120-160 characters (Excellent), 90-119 (Good), 161-180 (Acceptable). This range helps summarize the page effectively.',
					'asneris-seo-toolkit'
				)
				: validationProfile === 'socialTitle'
			? __(
				'Recommended: 40-60 characters (Excellent), 30-39 (Good), 61-70 (Acceptable).',
				'asneris-seo-toolkit'
			)
			: validationProfile === 'socialDescription'
				? __(
					'Recommended: 70-140 characters (Excellent), 50-69 (Good), 141-200 (Acceptable).',
					'asneris-seo-toolkit'
				)
				: targetLength <= 70
			? __(
				'Recommended length: 50-60 characters. Good titles are clear, concise, and include important keywords.',
				'asneris-seo-toolkit'
			)
			: targetLength >= 140
				? __(
					'Recommended length: 150-180 characters. Write a clear and compelling description.',
					'asneris-seo-toolkit'
				)
				: __(
					'Recommended length helps improve readability and search visibility.',
					'asneris-seo-toolkit'
				);

	const isSocialProfile =
		validationProfile === 'socialTitle' ||
		validationProfile === 'socialDescription';

	return (
		<div
			style={ {
				marginTop: isSocialProfile ? '8px' : '-8px',
				marginBottom: '12px',
			} }
		>
			<div
				style={ {
					display: 'flex',
					justifyContent: 'space-between',
					alignItems: 'center',
					fontSize: '12px',
					color,
					fontWeight: 600,
					marginBottom: '6px',
				} }
			>
				<span>
					{ length } / { targetLength } { __( 'characters', 'asneris-seo-toolkit' ) }
				</span>
				<span>{ status }</span>
			</div>
			<div
				style={ {
					height: '8px',
					borderRadius: '999px',
					background: '#e5e7eb',
					overflow: 'hidden',
				} }
			>
				<div
					style={ {
						height: '100%',
						width: `${ progress }%`,
						background: fillColor,
						transition: 'width 150ms ease-out',
					} }
				/>
			</div>
			<div
				style={ {
					fontSize: '11px',
					color: '#6b7280',
					marginTop: '4px',
				} }
			>
				{ recommendedMessage }
			</div>
		</div>
	);
};

const sanitizePreviewText = ( value ) =>
	String( value || '' )
		// Remove CSS/JS style comments that sometimes leak from theme snippets.
		.replace( /\/\*[\s\S]*?\*\//g, ' ' )
		// Remove common selector lists like ".entry-title, .page-title, h1.entry-title".
		.replace( /(?:[a-z]+\s*)?[.#][a-z0-9_-]+(?:\s*,\s*(?:[a-z]+\s*)?[.#][a-z0-9_-]+)+/gi, ' ' )
		.replace( /\s+/g, ' ' )
		.trim();

const stripHtmlText = ( value ) => {
	if ( ! value ) {
		return '';
	}

	const input = String( value );

	if ( typeof window !== 'undefined' && typeof window.DOMParser !== 'undefined' ) {
		try {
			const parser = new window.DOMParser();
			const doc = parser.parseFromString( input, 'text/html' );

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

			return sanitizePreviewText( doc.body?.textContent || '' );
		} catch {
			// Fall back to regex-based cleanup below.
		}
	}

	return sanitizePreviewText(
		input
			.replace( /<!--[\s\S]*?-->/g, ' ' )
			.replace( /<(script|style|noscript|template)[\s\S]*?<\/\1>/gi, ' ' )
			.replace( /<[^>]*>/g, ' ' )
	);
};

const getExcerptSourceText = ( postExcerpt, postContent = '' ) => {
	const excerptText =
		typeof postExcerpt === 'string'
			? postExcerpt
			: postExcerpt?.rendered || postExcerpt?.raw || '';

	const cleanedExcerpt = stripHtmlText( excerptText );
	if ( cleanedExcerpt ) {
		return cleanedExcerpt;
	}

	return stripHtmlText( postContent );
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

const trimToLength = ( value, maxLength, minWordBoundary = 24 ) => {
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

const includesIgnoreCase = ( text, phrase ) => {
	const source = String( text || '' ).trim();
	const target = String( phrase || '' ).trim();
	if ( ! source || ! target ) {
		return false;
	}

	return source.toLowerCase().includes( target.toLowerCase() );
};

const useIsMobileViewport = ( breakpoint = 782 ) => {
	const [ isMobile, setIsMobile ] = useState( () => {
		if ( typeof window === 'undefined' ) {
			return false;
		}
		return window.innerWidth <= breakpoint;
	} );

	useEffect( () => {
		if ( typeof window === 'undefined' ) {
			return undefined;
		}

		const onResize = () => {
			setIsMobile( window.innerWidth <= breakpoint );
		};

		onResize();
		window.addEventListener( 'resize', onResize );

		return () => {
			window.removeEventListener( 'resize', onResize );
		};
	}, [ breakpoint ] );

	return isMobile;
};

const AsnerisModalTitle = ( { text } ) => {
	const logoUrl = window.asnerisseoData?.logoUrl || '';

	return (
		<span
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				gap: '8px',
				fontWeight: 700,
			} }
		>
			{ logoUrl ? (
				<img
					src={ logoUrl }
					alt={ __( 'Asneris logo', 'asneris-seo-toolkit' ) }
					style={ {
						width: '22px',
						height: '22px',
						borderRadius: '999px',
						objectFit: 'cover',
						display: 'block',
					} }
				/>
			) : (
				<span
					style={ {
						display: 'inline-flex',
						alignItems: 'center',
						justifyContent: 'center',
						width: '22px',
						height: '22px',
						borderRadius: '999px',
						background: 'rgba(255,255,255,0.16)',
						color: '#ffffff',
						fontSize: '10px',
						fontWeight: 700,
						letterSpacing: '0.2px',
					} }
				>
					AS
				</span>
			) }
			<span>{ text }</span>
		</span>
	);
};

const ManagerSocialTab = () => {
	const isMobile = useIsMobileViewport();
	const isSmallMobile = useIsMobileViewport( 420 );
	const { editPost } = useDispatch( 'core/editor' );
	const postTitle = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'title' ) || ''
	);
	const postExcerpt = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'excerpt' ) || ''
	);
	const postContent = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostContent() || ''
	);
	const postType = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostType() || 'post'
	);
	const postDate = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'date' ) || ''
	);
	const meta = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {}
	);
	const editorConfig = useEditorConfig();

	const excerptText = getExcerptSourceText( postExcerpt, postContent );
	const seoTitleValue = String( meta._ASNERISSEO_title || '' ).trim();
	const seoDescriptionValue = String( meta._ASNERISSEO_description || '' ).trim();
	const ogTitleValue = String( meta._ASNERISSEO_og_title || '' ).trim();
	const ogDescriptionValue = String( meta._ASNERISSEO_og_description || '' ).trim();
	const ogImageValue = String( meta._ASNERISSEO_og_image || '' ).trim();
	const defaultOgImage = window.asnerisseoData?.defaultOgImage || '';
	const activePreviewImage = ogImageValue || defaultOgImage || '';
	const isUsingTemplateSocialImage = ! ogImageValue && !! defaultOgImage;
	const variableContext = {
		title: String( postTitle || '' ).trim(),
		site: String( window.asnerisseoData?.siteName || '' ).trim(),
		separator: String( window.asnerisseoData?.titleSeparator || '|' ),
		excerpt: stripHtmlText( excerptText ).slice( 0, 160 ),
		date: String( postDate || '' ).split( 'T' )[ 0 ] || getTodayIsoDate(),
		author: String( window.asnerisseoData?.authorName || '' ).trim(),
		term: String( window.asnerisseoData?.primaryTerm || '' ).trim(),
	};
	const resolvedSeoTitle = resolveInlineVariables( seoTitleValue, variableContext );
	const resolvedDefaultSeoTitle = resolveTemplate(
		editorConfig?.titleTemplates?.[ postType ] || '',
		variableContext
	);
	const resolvedSeoDescription = resolveInlineVariables(
		seoDescriptionValue,
		variableContext
	);
	const resolvedDefaultSeoDescription = resolveTemplate(
		editorConfig?.descriptionTemplates?.[ postType ] || '',
		variableContext
	);
	const resolvedOgTitle = resolveInlineVariables( ogTitleValue, variableContext );
	const resolvedOgDescription = resolveInlineVariables(
		ogDescriptionValue,
		variableContext
	);
	const effectiveTitle =
		resolvedOgTitle ||
		resolvedSeoTitle ||
		resolvedDefaultSeoTitle ||
		postTitle ||
		__( 'Untitled post', 'asneris-seo-toolkit' );
	const effectiveDescription =
		resolvedOgDescription ||
		resolvedSeoDescription ||
		resolvedDefaultSeoDescription ||
		stripHtmlText( excerptText ).slice( 0, 160 ) ||
		__( 'Add social description to improve your preview.', 'asneris-seo-toolkit' );
	const [ activeSocialActionButtons, setActiveSocialActionButtons ] = useState( {
		title: ogTitleValue ? 'manual' : 'auto',
		description: ogDescriptionValue ? 'manual' : 'auto',
	} );

	useEffect( () => {
		setActiveSocialActionButtons( ( current ) => ( {
			...current,
			title: ogTitleValue ? 'manual' : 'auto',
			description: ogDescriptionValue ? 'manual' : 'auto',
		} ) );
	}, [ ogTitleValue, ogDescriptionValue ] );

	const getSocialActionButtonClassName = ( group, action ) => {
		const baseClassName =
			'ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-small ASNERISSEO-react-button-action-toggle';

		if ( activeSocialActionButtons[ group ] === action ) {
			return `${ baseClassName } ASNERISSEO-react-button-action-active`;
		}

		return baseClassName;
	};
	const siteLabel = window.asnerisseoData?.siteUrl || window.location.hostname || 'example.com';
	const siteLabelDisplay = String( siteLabel )
		.replace( /^https?:\/\//, '' )
		.replace( /\/$/, '' );

	const previewTabs = [
		{ id: 'facebook', label: 'Facebook' },
		{ id: 'linkedin', label: 'LinkedIn' },
		{ id: 'x', label: 'X (Twitter)' },
		{ id: 'whatsapp', label: 'WhatsApp' },
		{ id: 'pinterest', label: 'Pinterest' },
		{ id: 'discord', label: 'Discord' },
		{ id: 'telegram', label: 'Telegram' },
		{ id: 'slack', label: 'Slack' },
	];

	const openSocialLogoPicker = () => {
		if ( ! window.wp?.media ) {
			return;
		}

		const frame = window.wp.media( {
			title: __( 'Select Social Logo / Image', 'asneris-seo-toolkit' ),
			multiple: false,
			library: {
				type: 'image',
			},
		} );

		frame.on( 'select', () => {
			const selected = frame.state().get( 'selection' ).first()?.toJSON();
			if ( selected?.url ) {
				editPost( {
					meta: {
						_ASNERISSEO_og_image: selected.url,
						_ASNERISSEO_og_image_disabled: false,
					},
				} );
			}
		} );

		frame.open();
	};

	const handleRemoveSocialLogo = () => {
		editPost( {
			meta: {
				_ASNERISSEO_og_image: '',
				_ASNERISSEO_og_image_disabled: false,
			},
		} );
	};

	const handleSmartGenerateSocialTitle = () => {
		const baseTitle = String( postTitle || '' ).trim();
		const primaryTerm = String( variableContext.term || '' ).trim();
		const siteName = String( variableContext.site || '' ).trim();
		const separator = String( variableContext.separator || '|' ).trim() || '|';

		let smartTitle = baseTitle || primaryTerm || __( 'Untitled post', 'asneris-seo-toolkit' );
		if ( primaryTerm && baseTitle && ! includesIgnoreCase( baseTitle, primaryTerm ) ) {
			smartTitle = `${ primaryTerm } - ${ baseTitle }`;
		}

		if ( siteName && ! includesIgnoreCase( smartTitle, siteName ) ) {
			smartTitle = `${ smartTitle } ${ separator } ${ siteName }`;
		}

		editPost( {
			meta: {
				_ASNERISSEO_og_title: trimToLength( smartTitle, 70, 30 ),
			},
		} );
		setActiveSocialActionButtons( ( current ) => ( {
			...current,
			title: 'manual',
		} ) );
	};

	const handleSmartGenerateSocialDescription = () => {
		const primaryTerm = String( variableContext.term || '' ).trim();
		const excerptBase = stripHtmlText( excerptText ) || String( postTitle || '' ).trim();
		let description = trimToLength( excerptBase, 160, 100 );

		if ( primaryTerm && ! includesIgnoreCase( description, primaryTerm ) ) {
			description = trimToLength( `${ primaryTerm }: ${ description }`, 160, 100 );
		}

		editPost( {
			meta: {
				_ASNERISSEO_og_description: description,
			},
		} );
		setActiveSocialActionButtons( ( current ) => ( {
			...current,
			description: 'manual',
		} ) );
	};

	const handleUseAutoSocialTitle = () => {
		editPost( {
			meta: {
				_ASNERISSEO_og_title: '',
			},
		} );
		setActiveSocialActionButtons( ( current ) => ( {
			...current,
			title: 'auto',
		} ) );
	};

	const handleUseAutoSocialDescription = () => {
		editPost( {
			meta: {
				_ASNERISSEO_og_description: '',
			},
		} );
		setActiveSocialActionButtons( ( current ) => ( {
			...current,
			description: 'auto',
		} ) );
	};

	return (
		<div style={ { display: 'grid', gap: '14px' } }>
			<div style={ { border: '1px solid #d7e3f3', background: '#ffffff', borderRadius: '10px', padding: isSmallMobile ? '10px' : isMobile ? '12px' : '14px', boxShadow: '0 8px 22px rgba(15, 43, 84, 0.06)' } }>
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
					<h3 className="ASNERISSEO-heading-h3" style={ { margin: 0 } }>
						{ __( 'Social Preview', 'asneris-seo-toolkit' ) }
					</h3>
					<span style={ { display: 'inline-flex', alignItems: 'center', padding: '4px 9px', borderRadius: '999px', background: '#eff6ff', color: '#1d4ed8', fontSize: '11px', fontWeight: 600 } }>
						{ __( 'Platform Preview', 'asneris-seo-toolkit' ) }
					</span>
				</div>

				<p style={ { margin: '6px 0 0', fontSize: isSmallMobile ? '12px' : '13px', color: 'var(--asneris-muted, #5f718a)' } }>
					{ __(
						'This is how your page may appear when shared on social media.',
						'asneris-seo-toolkit'
					) }
				</p>

				<div style={ { marginTop: '12px' } }>
					<div style={ { fontSize: isSmallMobile ? '11px' : '12px', fontWeight: 600, marginBottom: '6px' } }>
						{ __( 'Supported social channels:', 'asneris-seo-toolkit' ) }
					</div>
					<div
						style={ {
							display: 'flex',
							gap: '6px',
							flexWrap: 'wrap',
						} }
					>
						{ previewTabs.map( ( tab ) => (
							<span
								key={ tab.id }
								style={ {
									display: 'inline-flex',
									alignItems: 'center',
									padding: isSmallMobile ? '5px 8px' : isMobile ? '5px 10px' : '6px 11px',
									borderRadius: '999px',
									border: '1px solid #d6e3ef',
									background: '#f5f8fc',
									color: '#415a74',
									fontSize: isSmallMobile ? '10px' : isMobile ? '11px' : '12px',
									whiteSpace: 'nowrap',
									fontWeight: 500,
									cursor: 'default',
									userSelect: 'none',
								} }
							>
								{ tab.label }
							</span>
						) ) }
					</div>
					<div style={ { marginTop: '8px', fontSize: isSmallMobile ? '11px' : '12px', color: 'var(--asneris-text-secondary, #334155)' } }>
						{ __( 'One shared social title, description, and logo is used for all supported channels.', 'asneris-seo-toolkit' ) }
					</div>
				</div>

				<div
					style={ {
						marginTop: '12px',
						border: '1px solid #dbe5f4',
						borderRadius: '10px',
						overflow: 'hidden',
						background: '#fff',
						boxShadow: '0 8px 18px rgba(17, 33, 57, 0.06)',
					} }
				>
					<div
						style={ {
							height: isSmallMobile ? '156px' : isMobile ? '180px' : '240px',
							background:
								activePreviewImage
									? '#12214d'
									: 'linear-gradient(135deg, #16275b, #102042)',
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							overflow: 'hidden',
						} }
					>
						{ activePreviewImage ? (
							<img
								src={ activePreviewImage }
								alt={ __( 'Social image preview', 'asneris-seo-toolkit' ) }
								style={ { width: '100%', height: '100%', objectFit: 'cover' } }
							/>
						) : (
							<div style={ { color: '#e2e8f0', fontSize: '16px', fontWeight: 700 } }>
								{ __( 'Social image preview', 'asneris-seo-toolkit' ) }
							</div>
						) }
					</div>
					<div style={ { padding: '14px' } }>
						<div style={ { color: 'var(--asneris-muted, #5f718a)', fontSize: isSmallMobile ? '11px' : '12px', marginBottom: '4px' } }>{ siteLabelDisplay }</div>
						<div style={ { fontSize: isSmallMobile ? '16px' : isMobile ? '18px' : '22px', lineHeight: 1.2, fontWeight: 700, marginBottom: '7px' } }>
							{ effectiveTitle }
						</div>
						<div style={ { fontSize: isSmallMobile ? '12px' : isMobile ? '13px' : '14px', lineHeight: 1.35, color: 'var(--asneris-text-secondary, #334155)' } }>
							{ effectiveDescription }
						</div>
					</div>
				</div>
			</div>

			<div style={ { border: '1px solid #d7e3f3', background: '#ffffff', borderRadius: '10px', padding: isSmallMobile ? '10px' : isMobile ? '12px' : '14px', boxShadow: '0 8px 22px rgba(15, 43, 84, 0.06)' } }>
				<div style={ { display: 'grid', gap: '12px' } }>
					<div>
						<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px', marginBottom: '8px', flexWrap: 'wrap' } }>
							<div style={ { fontWeight: 700 } }>{ __( 'Logo', 'asneris-seo-toolkit' ) }</div>
							<div style={ { display: 'inline-flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' } }>
								<Button
									variant="secondary"
									className="ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-small"
									onClick={ openSocialLogoPicker }
								>
									{ __( 'Upload', 'asneris-seo-toolkit' ) }
								</Button>
								<Button
									variant="secondary"
									className="ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-small"
									onClick={ handleRemoveSocialLogo }
									disabled={ ! activePreviewImage }
								>
									<span
										className="dashicons dashicons-trash"
										style={ {
											fontSize: '14px',
											width: '14px',
											height: '14px',
											marginRight: '6px',
											color: '#d63638',
										} }
									/>
									<span style={ { color: '#d63638', fontWeight: 600 } }>
										{ __( 'Remove', 'asneris-seo-toolkit' ) }
									</span>
								</Button>
							</div>
						</div>
						<div style={ { display: 'flex', alignItems: 'center', gap: '10px' } }>
							<div style={ { width: '56px', height: '56px', borderRadius: '8px', overflow: 'hidden', border: '1px solid #dbe5f4', background: '#f8fafc', display: 'flex', alignItems: 'center', justifyContent: 'center' } }>
								{ activePreviewImage ? (
									<img src={ activePreviewImage } alt={ __( 'Social logo preview', 'asneris-seo-toolkit' ) } style={ { width: '100%', height: '100%', objectFit: 'cover' } } />
								) : (
									<span style={ { fontSize: '11px', color: '#64748b' } }>{ __( 'No logo', 'asneris-seo-toolkit' ) }</span>
								) }
							</div>
							<div style={ { fontSize: '12px', color: '#64748b' } }>
								{ isUsingTemplateSocialImage ? (
									<div style={ { color: '#d63638', fontWeight: 600, marginBottom: '2px' } }>
										{ __( 'Image from site setting.', 'asneris-seo-toolkit' ) }
									</div>
								) : null }
								{ __( 'Shown in social preview cards where supported.', 'asneris-seo-toolkit' ) }
							</div>
						</div>
						<div style={ { marginTop: '8px', fontSize: '12px', color: '#475569', background: '#f8fafc', border: '1px solid #dbe5f4', borderRadius: '8px', padding: '8px 10px' } }>
							<strong>{ __( 'Recommended image:', 'asneris-seo-toolkit' ) }</strong>{ ' ' }
							{ __( '1200 x 630 px (1.91:1). File types: JPG, PNG, or WebP. Keep under 1 MB for faster social previews.', 'asneris-seo-toolkit' ) }
						</div>
					</div>

					<div>
						<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px', marginBottom: '8px', flexWrap: 'wrap' } }>
							<div style={ { fontWeight: 700 } }>{ __( 'Title', 'asneris-seo-toolkit' ) }</div>
							<div style={ { display: 'inline-flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' } }>
								<Button
									variant="secondary"
									className={ getSocialActionButtonClassName( 'title', 'manual' ) }
									onClick={ handleSmartGenerateSocialTitle }
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
									className={ getSocialActionButtonClassName( 'title', 'auto' ) }
									onClick={ handleUseAutoSocialTitle }
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
						<TextControl
							label={ __( 'Social Title', 'asneris-seo-toolkit' ) }
							value={ ogTitleValue }
							placeholder={ __( 'Title is generated using Site Social Template Settings', 'asneris-seo-toolkit' ) }
							onChange={ ( nextValue ) =>
								editPost( {
									meta: {
										_ASNERISSEO_og_title: nextValue,
									},
								} )
							}
							help={ __( '', 'asneris-seo-toolkit' ) }
						/>
						<CharacterCount
							text={ ogTitleValue }
							targetLength={ 70 }
							warningLimit={ 90 }
							validationProfile="socialTitle"
						/>
					</div>

					<div>
						<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px', marginBottom: '8px', flexWrap: 'wrap' } }>
							<div style={ { fontWeight: 700 } }>{ __( 'Description', 'asneris-seo-toolkit' ) }</div>
							<div style={ { display: 'inline-flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' } }>
								<Button
									variant="secondary"
									className={ getSocialActionButtonClassName( 'description', 'manual' ) }
									onClick={ handleSmartGenerateSocialDescription }
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
									className={ getSocialActionButtonClassName( 'description', 'auto' ) }
									onClick={ handleUseAutoSocialDescription }
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
						<TextareaControl
							label={ __( 'Social Description', 'asneris-seo-toolkit' ) }
							value={ ogDescriptionValue }
							placeholder={ __( 'Description is generated using Site Social Template Settings', 'asneris-seo-toolkit' ) }
							onChange={ ( nextValue ) =>
								editPost( {
									meta: {
										_ASNERISSEO_og_description: nextValue,
									},
								} )
							}
							help={ __( '', 'asneris-seo-toolkit' ) }
						/>
						<CharacterCount
							text={ ogDescriptionValue }
							targetLength={ 140 }
							warningLimit={ 170 }
							validationProfile="socialDescription"
						/>
					</div>
				</div>
			</div>
		</div>
	);
};

const ManagerAiTab = ( { onAiSuggestionsClick } ) => {
	const isMobile = useIsMobileViewport();
	const isSmallMobile = useIsMobileViewport( 420 );
	const { editPost } = useDispatch( 'core/editor' );
	const postTitle = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'title' ) || ''
	);
	const postExcerpt = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'excerpt' ) || ''
	);
	const content = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostContent() || ''
	);
	const meta = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {}
	);

	const seo = evaluateSeoReadiness( {
		postTitle,
		postExcerpt,
		meta,
		content,
		origin: window.location.origin,
	} );
	const ai = evaluateAiDiscoverability( {
		content,
		siteName: window.asnerisseoData?.siteName || '',
		origin: window.location.origin,
	} );

	const score = ai.score;
	const aiSummaryRows = [
		{ label: __( 'Word count', 'asneris-seo-toolkit' ), value: ai.stats?.wordCount ?? 0 },
		{ label: __( 'Headings', 'asneris-seo-toolkit' ), value: ai.stats?.headingCount ?? 0 },
		{ label: __( 'Internal links', 'asneris-seo-toolkit' ), value: ai.stats?.internalLinkCount ?? 0 },
		{ label: __( 'Images', 'asneris-seo-toolkit' ), value: ai.stats?.imageCount ?? 0 },
		{ label: __( 'Avg sentence length', 'asneris-seo-toolkit' ), value: ai.stats?.avgSentenceLength ?? 0 },
		{ label: __( 'Primary keyword', 'asneris-seo-toolkit' ), value: ai.stats?.primaryKeyword || __( 'Not detected', 'asneris-seo-toolkit' ) },
		{ label: __( 'Scoring model', 'asneris-seo-toolkit' ), value: `shared evaluator ${ EVALUATOR_VERSION }` },
	];
	const topAiRecommendations = [
		__( 'Add a clear H1 heading.', 'asneris-seo-toolkit' ),
		__( 'Use bullet or numbered lists for key points.', 'asneris-seo-toolkit' ),
		__( 'Consider tables for structured comparisons.', 'asneris-seo-toolkit' ),
		__( 'Expand intro and heading context to clarify page purpose.', 'asneris-seo-toolkit' ),
		__( 'Add a concise summary section.', 'asneris-seo-toolkit' ),
	];

	const scoreStatus = score >= 80 ? 'Good' : score >= 60 ? 'Recommended' : 'Needs Improvement';
	const scoreStatusStyle =
		score >= 80
			? { background: '#dcfce7', color: 'var(--asneris-success-text, #1d6f42)' }
			: score >= 60
				? { background: '#fef3c7', color: 'var(--asneris-warning-text, #996800)' }
				: { background: '#fee2e2', color: 'var(--asneris-error-text, #b91c1c)' };

	const rows = ( ai.checks || [] ).map( ( check, index ) => ( {
		key: `${ check.group || 'check' }-${ index }`,
		label: check.label,
		text: check.detail,
		state: check.status === 'pass' ? 'good' : 'recommended',
	} ) );

	const getStatusUi = ( state ) => {
		if ( state === 'good' ) {
			return { label: 'Good', background: '#dcfce7', color: 'var(--asneris-success-text, #1d6f42)', iconBg: '#16a34a', iconText: '✓' };
		}
		if ( state === 'recommended' ) {
			return { label: 'Recommended', background: '#fef3c7', color: 'var(--asneris-warning-text, #996800)', iconBg: '#f59e0b', iconText: '!' };
		}
		return { label: 'Needs Improvement', background: '#fee2e2', color: 'var(--asneris-error-text, #b91c1c)', iconBg: '#ef4444', iconText: '!' };
	};

	return (
		<div style={ { display: 'grid', gap: '12px' } }>
			<div style={ { border: '1px solid var(--asneris-cyan, #4EB8C5)', background: 'var(--asneris-light-cyan, #ECF8FB)', borderRadius: '10px', padding: isSmallMobile ? '10px' : isMobile ? '12px' : '14px' } }>
				<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' } }>
					<h3 className="ASNERISSEO-heading-h3" style={ { margin: 0 } }>
						{ __( 'AI Discoverability', 'asneris-seo-toolkit' ) }
					</h3>
					<span style={ { color: '#8c8f94' } }>▾</span>
				</div>

				<div
					style={ {
						display: 'flex',
						flexDirection: isMobile ? 'column' : 'row',
						justifyContent: 'space-between',
						alignItems: 'flex-start',
						gap: '12px',
					} }
				>
					<div>
						<p style={ { margin: 0, fontSize: isSmallMobile ? '12px' : '13px', color: 'var(--asneris-muted, #5f718a)', lineHeight: 1.35 } }>
							{ __(
								'These signals help AI systems and search engines understand and reference your content.',
								'asneris-seo-toolkit'
							) }
						</p>
					</div>
					<div
						style={ {
							border: '1px solid #dcdcde',
							borderRadius: '10px',
							padding: isSmallMobile ? '9px 10px' : '10px 12px',
							minWidth: isMobile ? '100%' : '190px',
							background: '#fff',
							alignSelf: 'flex-start',
						} }
					>
						<div style={ { fontSize: isSmallMobile ? '11px' : '12px', color: 'var(--asneris-text-secondary, #334155)', marginBottom: '4px' } }>AI Readiness Score</div>
						<div style={ { fontSize: isSmallMobile ? '28px' : '32px', fontWeight: 700, color: 'var(--asneris-success-text, #1d6f42)', lineHeight: 1 } }>
							{ score }<span style={ { fontSize: isSmallMobile ? '15px' : '17px', color: 'var(--asneris-text-secondary, #334155)' } }>/100</span>
						</div>
						<span style={ { display: 'inline-block', marginTop: '7px', padding: '3px 9px', borderRadius: '999px', fontSize: isSmallMobile ? '11px' : '12px', fontWeight: 600, ...scoreStatusStyle } }>
							{ scoreStatus }
						</span>
					</div>
				</div>

				<div
					style={ {
						marginTop: '12px',
						border: '1px solid #dcdcde',
						borderRadius: '10px',
						padding: isSmallMobile ? '10px' : '12px',
						background: '#fff',
					} }
				>
					<div style={ { fontSize: isSmallMobile ? '12px' : '13px', fontWeight: 700, marginBottom: '8px' } }>
						{ __( 'Summary', 'asneris-seo-toolkit' ) }
					</div>
					<div
						style={ {
							display: 'grid',
							gridTemplateColumns: isMobile ? '1fr' : '1fr 1fr',
							gap: '6px 14px',
							fontSize: isSmallMobile ? '11px' : '12px',
							color: 'var(--asneris-muted, #5f718a)',
						} }
					>
						{ aiSummaryRows.map( ( row ) => (
							<div key={ row.label }>
								<strong style={ { color: 'var(--asneris-text, #10233f)', fontWeight: 600 } }>{ row.label }:</strong>{ ' ' }
								{ row.value }
							</div>
						) ) }
					</div>
				</div>

				<div
					style={ {
						marginTop: '12px',
						border: '1px solid #dcdcde',
						borderRadius: '10px',
						padding: isSmallMobile ? '10px' : '12px',
						background: '#fff',
					} }
				>
					<div style={ { fontSize: isSmallMobile ? '12px' : '13px', fontWeight: 700, marginBottom: '8px' } }>
						{ __( 'Top AI recommendations (from right panel)', 'asneris-seo-toolkit' ) }
					</div>
					<ol style={ { margin: '0 0 0 18px', padding: 0, display: 'grid', gap: '6px' } }>
						{ topAiRecommendations.map( ( recommendation ) => (
							<li key={ recommendation } style={ { fontSize: isSmallMobile ? '11px' : '12px', color: 'var(--asneris-muted, #5f718a)' } }>
								{ recommendation }
							</li>
						) ) }
					</ol>
				</div>

				<div style={ { marginTop: '12px', border: '1px solid #dcdcde', borderRadius: '10px', overflow: 'hidden', background: '#fff' } }>
					{ rows.map( ( row, index ) => {
						const ui = getStatusUi( row.state );
						return (
							<div
								key={ row.key }
								style={ {
									display: 'grid',
									gridTemplateColumns: isMobile ? '1fr auto' : '1fr auto',
									gap: '10px',
									padding: isSmallMobile ? '10px 10px' : '11px 12px',
									alignItems: 'center',
									borderBottom: index === rows.length - 1 ? 'none' : '1px solid #eef2f7',
								} }
							>
								<div>
									<div
										title={ row.label }
										style={ {
											fontSize: isSmallMobile ? '12px' : '13px',
											fontWeight: 700,
											marginBottom: '2px',
											maxWidth: isSmallMobile ? '170px' : 'none',
											overflow: isSmallMobile ? 'hidden' : 'visible',
											whiteSpace: isSmallMobile ? 'nowrap' : 'normal',
											textOverflow: isSmallMobile ? 'ellipsis' : 'clip',
										} }
									>
										{ row.label }
									</div>
									<div style={ { fontSize: isSmallMobile ? '11px' : '12px', color: 'var(--asneris-text-secondary, #334155)' } }>{ row.text }</div>
								</div>
								<div
									style={ {
										display: 'inline-flex',
										alignItems: 'center',
										gap: '6px',
										justifySelf: 'end',
									} }
								>
									<span
										style={ {
											width: '16px',
											height: '16px',
											borderRadius: '999px',
											background: ui.iconBg,
											color: '#fff',
											fontSize: '11px',
											fontWeight: 700,
											display: 'inline-flex',
											alignItems: 'center',
											justifyContent: 'center',
										} }
									>
										{ ui.iconText }
									</span>
									<span style={ { fontSize: isSmallMobile ? '11px' : '12px', fontWeight: 600, padding: '3px 8px', borderRadius: '999px', background: ui.background, color: ui.color } }>
										{ ui.label }
									</span>
								</div>
							</div>
						);
					} ) }
				</div>

				<div style={ { marginTop: '12px', border: '1px solid #dcdcde', background: '#fff', borderRadius: '10px', padding: isSmallMobile ? '10px' : '12px' } }>
					<div style={ { fontSize: isSmallMobile ? '12px' : '13px', fontWeight: 700, marginBottom: '3px' } }>
						{ __( 'Why this matters?', 'asneris-seo-toolkit' ) }
					</div>
					<div style={ { fontSize: isSmallMobile ? '11px' : '12px', color: 'var(--asneris-text-secondary, #334155)' } }>
						{ __(
							'AI systems like ChatGPT, Perplexity, and Google SGE rely on these signals to discover, understand, and recommend your content.',
							'asneris-seo-toolkit'
						) }
					</div>
				</div>

				<div
					style={ {
						marginTop: '12px',
						border: '1px solid #dcdcde',
						background: '#fff',
						borderRadius: '8px',
						padding: isSmallMobile ? '10px' : '10px 12px',
						display: 'flex',
						flexDirection: isMobile ? 'column' : 'row',
						alignItems: isMobile ? 'stretch' : 'center',
						justifyContent: 'space-between',
						gap: '10px',
					} }
				>
					<div>
						<div style={ { fontSize: isSmallMobile ? '12px' : '13px', fontWeight: 700 } }>{ __( 'Need help improving?', 'asneris-seo-toolkit' ) }</div>
						<div style={ { fontSize: isSmallMobile ? '11px' : '12px', color: 'var(--asneris-text-secondary, #334155)' } }>{ __( 'Get AI-powered suggestions to improve your content.', 'asneris-seo-toolkit' ) }</div>
					</div>
					<Button
						variant="primary"
						className="ASNERISSEO-react-button ASNERISSEO-react-button-primary"
						style={ { minHeight: '32px', fontSize: '12px' } }
						onClick={ onAiSuggestionsClick }
					>
						<span
							className="dashicons dashicons-star-filled"
							aria-hidden="true"
							style={ { fontSize: '14px', width: '14px', height: '14px', marginRight: '6px' } }
						/>
						{ __( 'Get AI Suggestions', 'asneris-seo-toolkit' ) }
					</Button>
				</div>
			</div>
		</div>
	);
};

const AsnerisManagerModal = ( {
	isOpen,
	onRequestClose,
	initialTab = 'appearance',
	seoTitleValue,
	seoDescriptionValue,
	MetaFieldComponent,
	CharacterCountComponent,
} ) => {
	const isMobile = useIsMobileViewport();
	const isSmallMobile = useIsMobileViewport( 420 );
	const { createNotice } = useDispatch( 'core/notices' );
	const [ activeTab, setActiveTab ] = useState( initialTab );

	const handleOpenAiComingSoon = useCallback( () => {
		createNotice(
			'info',
			__( 'AI suggestions are coming soon.', 'asneris-seo-toolkit' ),
			{
				type: 'snackbar',
				isDismissible: true,
			}
		);
	}, [ createNotice ] );

	useEffect( () => {
		if ( isOpen ) {
			setActiveTab(
				initialTab === 'appearance' ||
				initialTab === 'social' ||
				initialTab === 'advanced'
					? initialTab
					: 'appearance'
			);
		}
	}, [ isOpen, initialTab ] );

	const tabs = [
		{
			id: 'appearance',
			label: __( 'Search Preview', 'asneris-seo-toolkit' ),
			iconClass: 'dashicons dashicons-search',
		},
		{
			id: 'social',
			label: __( 'Social Preview', 'asneris-seo-toolkit' ),
			iconClass: 'dashicons dashicons-share',
		}
	];
	const tabContentFrameStyle = {
		overflow: 'visible',
	};
	const managerModalBodyStyle = {
		width: '100%',
		maxWidth: 'none',
		height: 'auto',
		maxHeight: 'none',
		display: 'block',
		overflow: 'visible',
		padding: '0',
		boxSizing: 'border-box',
	};
	const managerTabShellStyle = {
		maxWidth: '100%',
		margin: '0',
		width: '100%',
		padding: isMobile
			? isSmallMobile
				? '6px 6px 8px'
				: '8px 8px 10px'
			: isSmallMobile
				? '10px 10px 12px'
				: '12px 12px 14px',
		boxSizing: 'border-box',
	};

	return (
		<>
		<AsnerisModal
			isOpen={ isOpen }
			title={
				<AsnerisModalTitle
					text={ __( 'Search Appearance', 'asneris-seo-toolkit' ) }
				/>
			}
			onRequestClose={ onRequestClose }
			showFooter={ false }
			className="asneris-fixed-manager-modal"
		>
			<div style={ managerModalBodyStyle }>
			<div className="ASNERISSEO-react-module-nav-grid ASNERISSEO-react-tabs ASNERISSEO-react-tabs-strip asneris-manager-tabs" role="tablist" aria-label={ __( 'Search Appearance Manager Tabs', 'asneris-seo-toolkit' ) }>
				{ tabs.map( ( tab ) => (
					<button
						key={ tab.id }
						type="button"
						onClick={ () => setActiveTab( tab.id ) }
						className={ activeTab === tab.id ? 'ASNERISSEO-react-module-pill ASNERISSEO-react-tab is-active asneris-manager-tab' : 'ASNERISSEO-react-module-pill ASNERISSEO-react-tab asneris-manager-tab' }
						role="tab"
						aria-selected={ activeTab === tab.id }
						aria-controls={ `asneris-manager-panel-${ tab.id }` }
						id={ `asneris-manager-tab-${ tab.id }` }
					>
						<span
							className={ `${ tab.iconClass } ASNERISSEO-react-tab-icon` }
							aria-hidden="true"
						/>
						{ tab.label }
					</button>
				) ) }
			</div>

			<div style={ tabContentFrameStyle }>
				{ activeTab === 'appearance' ? (
					<div style={ managerTabShellStyle } role="tabpanel" id="asneris-manager-panel-appearance" aria-labelledby="asneris-manager-tab-appearance">
						<SearchAppearancePanel
							activeRoute="appearance"
							expandedSection="appearance"
							onExpand={ () => {} }
							managerMode={ true }
							mobilePreviewOnly={ isMobile }
							seoTitleValue={ seoTitleValue }
							seoDescriptionValue={ seoDescriptionValue }
							onAiSuggestionsClick={ handleOpenAiComingSoon }
							onManagerCancel={ onRequestClose }
							MetaFieldComponent={ MetaFieldComponent }
							CharacterCountComponent={ CharacterCountComponent }
						/>
					</div>
				) : null }

				{ activeTab === 'social' ? (
					<div style={ managerTabShellStyle } role="tabpanel" id="asneris-manager-panel-social" aria-labelledby="asneris-manager-tab-social">
						<ManagerSocialTab />
					</div>
				) : null }
			</div>
			</div>
		</AsnerisModal>
		</>
	);
};

const SEOScore = ( { scoreOverride = null, useLocalFallback = true, suggestionsOverride = null } ) => {
	const meta = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'meta' )
	);
	const postTitle = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'title' )
	);
	const postExcerpt = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'excerpt' )
	);
	const postContent = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostContent()
	);

	const readiness = evaluateSeoReadiness( {
		postTitle,
		postExcerpt,
		meta,
		content: postContent,
		origin: window.location.origin,
	} );

	const hasOverrideScore = Number.isFinite( Number( scoreOverride ) );
	const score = hasOverrideScore
		? Number( scoreOverride )
		: ( useLocalFallback ? readiness.score : null );
	const hasScore = Number.isFinite( Number( score ) );
	const overviewCanonicalFieldSet = useMemo(
		() => new Set(
			( OVERVIEW_PRIMARY_FIELDS || [] )
				.map( ( field ) => normalizeOverviewFieldLabel( field ) )
				.filter( Boolean )
		),
		[]
	);
	const localSuggestions = readiness.warnings
		.filter( ( warning ) => {
			const candidateField = normalizeOverviewFieldLabel( warning?.canonicalField || warning?.label || '' );
			return overviewCanonicalFieldSet.has( candidateField );
		} )
		.slice( 0, 6 )
		.map( ( warning ) => warning.detail );
	const suggestions = Array.isArray( suggestionsOverride ) && suggestionsOverride.length > 0
		? suggestionsOverride
		: ( useLocalFallback ? localSuggestions : [] );

	const getScoreColor = () => {
		if ( ! hasScore ) {
			return '#6b7280';
		}
		if ( score >= 80 ) {
			return '#46b450';
		}
		if ( score >= 50 ) {
			return '#dba617';
		}
		return '#d63638';
	};

	const getScoreTone = () => {
		if ( ! hasScore ) {
			return 'warning';
		}
		if ( score >= 80 ) {
			return 'success';
		}
		if ( score >= 50 ) {
			return 'warning';
		}
		return 'error';
	};

	return (
		<AsnerisCard
			title={ __( 'SEO Score', 'asneris-seo-toolkit' ) }
			action={
				<StatusBadge label={ hasScore ? `${ score }%` : __( 'Loading', 'asneris-seo-toolkit' ) } tone={ getScoreTone() } />
			}
		>
			<div
				style={ {
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'space-between',
					marginBottom: '8px',
				} }
			>
				<strong>{ __( 'Readiness', 'asneris-seo-toolkit' ) }</strong>
				<span
					style={ {
						fontSize: '24px',
						fontWeight: 'bold',
						color: getScoreColor(),
					} }
				>
					{ hasScore ? `${ score }%` : __( 'Loading', 'asneris-seo-toolkit' ) }
				</span>
			</div>

			<div
				style={ {
					height: '8px',
					background: '#ddd',
					borderRadius: '4px',
					overflow: 'hidden',
				} }
			>
				<div
					style={ {
						height: '100%',
						width: `${ hasScore ? score : 0 }%`,
						background: getScoreColor(),
						transition: 'width 0.3s ease',
					} }
				/>
			</div>

			{ suggestions.length > 0 && (
				<div style={ { marginTop: '12px' } }>
					<strong style={ { fontSize: '13px', color: '#1e1e1e' } }>
						RECOMMENDATIONS:
					</strong>
					<ol
						style={ {
							margin: '8px 0 0 0',
							padding: '0 0 0 20px',
							fontSize: '13px',
							color: '#1e1e1e',
							listStyleType: 'decimal',
						} }
					>
						{ suggestions.map( ( tip, index ) => (
							<li key={ index }>{ tip }</li>
						) ) }
					</ol>
				</div>
			) }
		</AsnerisCard>
	);
};

const AsnerisSeoSidebar = () => {
	const hasConflicts = !! window.asnerisseoData?.hasConflicts;
	const conflictNames = window.asnerisseoData?.conflicts || [];
	const pluginsUrl =
		window.asnerisseoData?.pluginsUrl || '/wp-admin/plugins.php';
	const schemaEnabled = useSelect( ( select ) => {
		const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' );
		return meta?._ASNERISSEO_schema_enabled || false;
	} );
	const seoTitleValue = useSelect( ( select ) => {
		const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' );
		return meta?._ASNERISSEO_title || '';
	} );
	const seoDescriptionValue = useSelect( ( select ) => {
		const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' );
		return meta?._ASNERISSEO_description || '';
	} );
	const scoreContext = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		return {
			postTitle: editor.getEditedPostAttribute( 'title' ),
			postExcerpt: editor.getEditedPostAttribute( 'excerpt' ),
			meta: editor.getEditedPostAttribute( 'meta' ) || {},
			content: editor.getEditedPostContent(),
			permalink: typeof editor.getPermalink === 'function' ? editor.getPermalink() : '',
		};
	}, [] );
	const editorDraftDiagnosticsContext = useMemo(
		() => ( {
			postTitle: scoreContext.postTitle,
			postExcerpt: scoreContext.postExcerpt,
			meta: scoreContext.meta || {},
			content: scoreContext.content || '',
			url: scoreContext.permalink || '',
			siteName: window.asnerisseoData?.siteName || '',
			origin: window.location.origin,
		} ),
		[ scoreContext ]
	);
	const [ reportLauncherScores, setReportLauncherScores ] = useState( {
		seo: null,
		ai: null,
	} );
	const [ reportLauncherRunPayload, setReportLauncherRunPayload ] = useState( null );
	const editorDispatch = useDispatch( 'core/editor' ) || {};
	const editPost = editorDispatch.editPost || ( () => {} );
	const editPostDispatch = useDispatch( 'core/edit-post' ) || {};
	const openGeneralSidebar = editPostDispatch.openGeneralSidebar || null;
	const closeGeneralSidebar = editPostDispatch.closeGeneralSidebar || null;
	const interfaceDispatch = useDispatch( 'core/interface' ) || {};
	const enableComplementaryArea =
		interfaceDispatch.enableComplementaryArea || null;
	const disableComplementaryArea =
		interfaceDispatch.disableComplementaryArea || null;
	const noticesDispatch = useDispatch( 'core/notices' ) || {};
	const createNotice = noticesDispatch.createNotice || ( () => {} );
	const currentPostId = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostId()
	);
	const isEditorDirty = useSelect( ( select ) =>
		select( 'core/editor' ).isEditedPostDirty()
	);
	const { activeRoute, navigate } = useSidebarRoute( 'overview' );
	
	const editorConfig = useEditorConfig();
	const [ expandedSection, setExpandedSection ] = useState( 'overview' );
	const isAsnerisSidebarActive = useSelect( ( select ) => {
		const activeCandidates = getActiveSidebarCandidates( select );

		debugSidebar( 'sidebar:active-candidates', {
			activeCandidates,
		} );

		return (
			activeCandidates.includes( ASNERIS_SIDEBAR_ID ) ||
			activeCandidates.includes( ASNERIS_SIDEBAR_FALLBACK_ID )
		);
	}, [] );
	const [ isWorkspaceDrawerOpen, setIsWorkspaceDrawerOpen ] =
		useState( false );
	const [ workspaceDetailPostId, setWorkspaceDetailPostId ] = useState( null );
	const [ workspaceDetailOpenToken, setWorkspaceDetailOpenToken ] = useState( 0 );
	const [ isPhaseInfoOpen, setIsPhaseInfoOpen ] = useState( false );
	const [ isManagerDialogOpen, setIsManagerDialogOpen ] = useState( false );
	const [ managerDialogTab, setManagerDialogTab ] =
		useState( 'appearance' );
	const workspaceEnabled = true;
	const pageDiagnosticsRestUrl = `${ getRestBaseUrl() }/page-diagnostics/overview`;
	const overviewCanonicalFieldSet = useMemo(
		() => new Set(
			( OVERVIEW_PRIMARY_FIELDS || [] )
				.map( ( field ) => normalizeOverviewFieldLabel( field ) )
				.filter( Boolean )
		),
		[]
	);
	const reportSeoScore = Number.isFinite( Number( reportLauncherScores?.seo ) )
		? Number( reportLauncherScores.seo )
		: null;
	const reportAiScore = Number.isFinite( Number( reportLauncherScores?.ai ) )
		? Number( reportLauncherScores.ai )
		: null;
	const stableServerSeoScore = reportSeoScore;
	const stableServerAiScore = reportAiScore;
	const launcherSeoScore = stableServerSeoScore;
	const launcherAiScore = stableServerAiScore;
	const launcherSeoSuggestions = useMemo( () => {
		const hasServerPayload = !! reportLauncherRunPayload;
		const issueRecords = Array.isArray( reportLauncherRunPayload?.overviewIssueRecords )
			? reportLauncherRunPayload.overviewIssueRecords
			: [];
		const fromServer = issueRecords
			.map( ( record ) => {
				const canonicalField = normalizeOverviewFieldLabel( record?.canonical_field || '' );
				const recommendedFix = String( record?.recommended_fix || '' ).trim();
				const scoreImpact = Number( record?.score_impact || 0 );
				const canonicalStatus = String( record?.canonical_status || 'warning' ).toLowerCase();

				return {
					canonicalField,
					recommendedFix,
					scoreImpact: Number.isFinite( scoreImpact ) ? scoreImpact : 0,
					canonicalStatus,
				};
			} )
			.filter(
				( record ) =>
					overviewCanonicalFieldSet.has( record.canonicalField ) &&
					record.recommendedFix &&
					( record.canonicalStatus === 'warning' || record.canonicalStatus === 'fail' )
			)
			.sort( ( a, b ) => {
				const severityRank = ( status ) => ( status === 'fail' ? 2 : status === 'warning' ? 1 : 0 );
				const severityDiff = severityRank( b.canonicalStatus ) - severityRank( a.canonicalStatus );
				if ( severityDiff !== 0 ) {
					return severityDiff;
				}

				return b.scoreImpact - a.scoreImpact;
			} )
			.slice( 0, 6 )
			.map( ( record ) => record.recommendedFix );

		if ( fromServer.length > 0 ) {
			return fromServer;
		}

		const rawChecks = reportLauncherRunPayload?.checks;
		const checks = Array.isArray( rawChecks )
			? rawChecks
			: ( rawChecks && typeof rawChecks === 'object' ? Object.values( rawChecks ) : [] );
		const fromChecks = checks
			.map( ( check ) => {
				const canonicalField = normalizeOverviewFieldLabel( check?.canonicalField || check?.label || '' );
				const canonicalStatus = String( check?.canonicalStatus || check?.status || '' ).toLowerCase();
				const detail = String( check?.recommendedFix || check?.details || check?.detail || '' ).trim();

				return {
					canonicalField,
					canonicalStatus,
					detail,
				};
			} )
			.filter(
				( check ) =>
					overviewCanonicalFieldSet.has( check.canonicalField ) &&
					( check.canonicalStatus === 'warning' || check.canonicalStatus === 'fail' ) &&
					check.detail
			)
			.slice( 0, 6 )
			.map( ( check ) => check.detail );

		if ( fromChecks.length > 0 ) {
			return fromChecks;
		}

		if ( hasServerPayload ) {
			return [];
		}

		const localReadiness = evaluateSeoReadiness( {
			postTitle: scoreContext.postTitle,
			postExcerpt: scoreContext.postExcerpt,
			meta: scoreContext.meta,
			content: scoreContext.content,
			origin: window.location.origin,
		} );

		return ( localReadiness?.warnings || [] )
			.filter( ( warning ) => {
				const candidateField = normalizeOverviewFieldLabel( warning?.canonicalField || warning?.label || '' );
				return overviewCanonicalFieldSet.has( candidateField );
			} )
			.map( ( warning ) => String( warning?.detail || '' ).trim() )
			.filter( Boolean )
			.slice( 0, 6 );
	}, [ reportLauncherRunPayload, overviewCanonicalFieldSet, scoreContext ] );

	const normalizeChecks = useCallback( ( checks ) => {
		if ( Array.isArray( checks ) ) {
			return checks;
		}
		if ( checks && typeof checks === 'object' ) {
			return Object.values( checks );
		}
		return [];
	}, [] );

	const handlePanelStatus = useCallback( ( status ) => {
		if ( ! status?.text ) {
			return;
		}

		createNotice( status?.tone === 'error' ? 'error' : 'success', status.text, {
			type: 'snackbar',
			isDismissible: true,
		} );
	}, [ createNotice ] );

	const resolvedEditorPostId = useMemo( () => {
		const normalizedCurrentPostId = Number( currentPostId );
		if ( Number.isFinite( normalizedCurrentPostId ) && normalizedCurrentPostId > 0 ) {
			return normalizedCurrentPostId;
		}

		try {
			const queryPostId = Number( new URLSearchParams( window.location.search ).get( 'post' ) );
			if ( Number.isFinite( queryPostId ) && queryPostId > 0 ) {
				return queryPostId;
			}
		} catch ( error ) {
			// Keep existing flow when URL params are unavailable.
		}

		return null;
	}, [ currentPostId ] );

	const openDiscoverabilityReport = useCallback( ( postId = null ) => {
		const normalizedPostId = Number( postId );
		const nextPostId = Number.isFinite( normalizedPostId ) && normalizedPostId > 0
			? normalizedPostId
			: resolvedEditorPostId;
		setWorkspaceDetailPostId( nextPostId || null );
		setWorkspaceDetailOpenToken( ( previous ) => previous + 1 );
		setIsWorkspaceDrawerOpen( true );
	}, [ resolvedEditorPostId ] );

	useEffect( () => {
		if ( ! currentPostId ) {
			setReportLauncherScores( { seo: null, ai: null } );
			setReportLauncherRunPayload( null );
			return undefined;
		}

		let isCancelled = false;
		const requestPath = '/page-diagnostics/draft-policy';
		const requestBody = {
			postId: currentPostId,
			postTitle: scoreContext.postTitle,
			postExcerpt: scoreContext.postExcerpt,
			content: scoreContext.content,
			meta: scoreContext.meta || {},
			url: scoreContext.permalink || '',
		};

		restPost( requestPath, requestBody )
			.then( ( payload ) => {
				if ( isCancelled ) {
					return;
				}

				assertUnifiedData( payload, 'editor.launcher.runPayload' );

				const computed = getUnifiedComputed( payload );
				const nextSeo = Number( computed?.seoScore ?? payload?.seoScore );
				const nextAi = Number( computed?.aiScore ?? payload?.aiScore );
				const safeSeo = Number.isFinite( nextSeo ) ? nextSeo : null;
				const safeAi = Number.isFinite( nextAi ) ? nextAi : null;
				const normalizedRunPayload = {
					...payload,
					postId: payload?.postId || payload?.post_id || payload?.id || currentPostId,
				};
				setReportLauncherRunPayload( normalizedRunPayload );
				setReportLauncherScores( {
					seo: safeSeo,
					ai: safeAi,
				} );
			} )
			.catch( ( error ) => {
				if ( String( error?.code || '' ).toLowerCase().startsWith( 'asnerisseo_unified_contract_' ) ) {
					createNotice( 'error', error.message || __( 'Unified diagnostics contract violation detected. Please update providers and refresh.', 'asneris-seo-toolkit' ) );
				}

				setReportLauncherScores( { seo: null, ai: null } );
				setReportLauncherRunPayload( null );
			} );

		return () => {
			isCancelled = true;
		};
	}, [ currentPostId, isEditorDirty, scoreContext, createNotice ] );

	const embeddedInitialDiagnostics = useMemo( () => {
		if ( ! reportLauncherRunPayload ) {
			return null;
		}

		const payloadPostId = Number( reportLauncherRunPayload?.postId || 0 );
		const targetPostId = Number( workspaceDetailPostId || currentPostId || 0 );

		if ( targetPostId > 0 && payloadPostId > 0 && payloadPostId !== targetPostId ) {
			return null;
		}

		return reportLauncherRunPayload;
	}, [ reportLauncherRunPayload, workspaceDetailPostId, currentPostId ] );

	const handleExpandSection = useCallback( ( sectionKey ) => {
		setExpandedSection( ( previousSection ) =>
			previousSection === sectionKey ? '' : sectionKey
		);
	}, [] );

	useEffect( () => {
		window.__ASNERIS_SIDEBAR_BUILD = ASNERIS_RUNTIME_BUILD_MARKER;
	}, [] );

	useEffect( () => {
		if ( document.getElementById( ASNERIS_SIDEBAR_STYLE_ID ) ) {
			return;
		}

		const styleElement = document.createElement( 'style' );
		styleElement.id = ASNERIS_SIDEBAR_STYLE_ID;
		styleElement.textContent = `
			:root {
				--asneris-font-heading: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
				--asneris-light-cyan: #ECF8FB;
			}

			/* Embedded Page Diagnostics in editor modal: detail-only surface (no list layer) */
			.ASNERISSEO-react-pd-embedded > :not(.ASNERISSEO-modal-overlay) {
				display: none !important;
			}

			.ASNERISSEO-react-pd-embedded.is-report-open > .ASNERISSEO-modal-overlay.active {
				position: static !important;
				top: auto !important;
				left: auto !important;
				right: auto !important;
				bottom: auto !important;
				background: transparent !important;
				display: block !important;
				opacity: 1 !important;
				visibility: visible !important;
				pointer-events: auto !important;
			}

			.ASNERISSEO-react-pd-embedded.is-report-open > .ASNERISSEO-modal-overlay.active .ASNERISSEO-react-detail-modal {
				width: 800px !important;
				max-width: 90vw !important;
				height: 85vh !important;
				max-height: 85vh !important;
				margin: 0 auto !important;
				padding: 0 !important;
				border-radius: 0 !important;
				box-shadow: none !important;
				animation: none !important;
			}

			@media (max-width: 782px) {
				.ASNERISSEO-react-pd-embedded.is-report-open > .ASNERISSEO-modal-overlay.active {
					position: fixed !important;
					top: 0 !important;
					left: 0 !important;
					right: 0 !important;
					bottom: 0 !important;
					width: 100vw !important;
					height: 100dvh !important;
					margin: 0 !important;
					padding: 0 !important;
					z-index: 100000 !important;
					overflow: hidden !important;
					background: #ffffff !important;
				}

				.ASNERISSEO-react-pd-embedded.is-report-open > .ASNERISSEO-modal-overlay.active .ASNERISSEO-react-detail-modal {
					width: 100vw !important;
					max-width: 100vw !important;
					height: 100dvh !important;
					max-height: 100dvh !important;
					margin: 0 !important;
					border-radius: 0 !important;
				}

				.components-modal__frame.asneris-fixed-workspace-modal,
				.components-modal__frame.asneris-fixed-workspace-modal:has(.ASNERISSEO-react-pd-embedded.is-report-open) {
					width: 100vw !important;
					max-width: 100vw !important;
					height: 100dvh !important;
					max-height: 100dvh !important;
					margin: 0 !important;
					border-radius: 0 !important;
				}

				.components-modal__frame.asneris-fixed-workspace-modal .components-modal__content {
					scrollbar-gutter: auto;
					padding: 0 !important;
				}
			}

			.components-modal__frame.asneris-fixed-manager-modal {
				width: min(860px, 92vw) !important;
				max-width: min(860px, 92vw) !important;
			}

			.components-modal__frame.asneris-fixed-workspace-modal {
				width: min(1400px, 96vw) !important;
				max-width: min(1400px, 96vw) !important;
				height: 85vh !important;
				max-height: 85vh !important;
			}

			.components-modal__frame.asneris-fixed-workspace-modal:has(.ASNERISSEO-react-pd-embedded.is-report-open) {
				width: min(860px, 92vw) !important;
				max-width: min(860px, 92vw) !important;
				padding: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
			}

			.components-modal__frame.asneris-fixed-workspace-modal:has(.ASNERISSEO-react-pd-embedded.is-report-open) .components-modal__header {
				display: none !important;
			}

			.components-modal__frame.asneris-fixed-workspace-modal .components-modal__content {
				overflow: hidden !important;
				scrollbar-gutter: stable both-edges;
			}

			.components-modal__frame.asneris-fixed-manager-modal .components-modal__content {
				overflow-x: hidden !important;
				overflow-y: auto !important;
				-webkit-overflow-scrolling: touch !important;
				touch-action: pan-y !important;
				scrollbar-gutter: stable both-edges;
			}

			.components-modal__frame.asneris-fixed-workspace-modal .components-modal__content {
				padding: 0 !important;
			}

			.components-modal__frame.asneris-fixed-workspace-modal:has(.ASNERISSEO-react-pd-embedded.is-report-open) .components-modal__content {
				background: transparent !important;
				padding: 0 !important;
			}

			.components-modal__frame.asneris-fixed-manager-modal .components-modal__header,
			.components-modal__frame.asneris-fixed-workspace-modal .components-modal__header {
				background: var(--asneris-navy, #12214d);
				color: #ffffff;
				padding-block: 10px;
				padding-inline: 16px 12px;
			}

			.components-modal__frame.asneris-fixed-manager-modal .components-modal__header-heading,
			.components-modal__frame.asneris-fixed-workspace-modal .components-modal__header-heading {
				color: #ffffff;
			}

			.components-modal__frame.asneris-fixed-manager-modal .components-modal__header .components-button,
			.components-modal__frame.asneris-fixed-workspace-modal .components-modal__header .components-button {
				color: #ffffff;
			}

			.components-modal__frame.asneris-fixed-manager-modal .components-modal__header .components-button:hover,
			.components-modal__frame.asneris-fixed-workspace-modal .components-modal__header .components-button:hover {
				color: #ffffff;
				opacity: 0.85;
			}

			@media (max-width: 782px) {
				.components-modal__screen-overlay:has(.components-modal__frame.asneris-fixed-manager-modal) {
					padding: 0 !important;
				}

				.components-modal__frame.asneris-fixed-manager-modal {
					width: 100vw !important;
					max-width: 100vw !important;
					height: 100dvh !important;
					max-height: 100dvh !important;
					margin: 0 !important;
					border-radius: 0 !important;
					padding: 0 !important;
					box-shadow: none !important;
				}

				.components-modal__frame.asneris-fixed-manager-modal .components-modal__header {
					padding-block: 8px !important;
					padding-inline: 12px 10px !important;
				}

				.components-modal__frame.asneris-fixed-manager-modal .components-modal__content {
					overflow-y: auto !important;
					overflow-x: hidden !important;
					-webkit-overflow-scrolling: touch !important;
					touch-action: pan-y !important;
					padding: 0 !important;
					scrollbar-gutter: auto !important;
				}

				.components-modal__screen-overlay:has(.components-modal__frame.asneris-fixed-workspace-modal) {
					padding: 0 !important;
				}

				.components-modal__frame.asneris-fixed-workspace-modal,
				.components-modal__frame.asneris-fixed-workspace-modal:has(.ASNERISSEO-react-pd-embedded.is-report-open) {
					width: 100vw !important;
					max-width: 100vw !important;
					height: 100dvh !important;
					max-height: 100dvh !important;
					margin: 0 !important;
					border-radius: 0 !important;
					padding: 0 !important;
					box-shadow: none !important;
				}

				.components-modal__frame.asneris-fixed-workspace-modal:has(.ASNERISSEO-react-pd-embedded.is-report-open) .components-modal__header {
					display: none !important;
				}

				.components-modal__frame.asneris-fixed-workspace-modal .components-modal__content {
					scrollbar-gutter: auto !important;
					padding: 0 !important;
				}

				.ASNERISSEO-react-pd-embedded.is-report-open > .ASNERISSEO-modal-overlay.active .ASNERISSEO-react-detail-modal {
					width: 100vw !important;
					max-width: 100vw !important;
					height: 100dvh !important;
					max-height: 100dvh !important;
					margin: 0 !important;
				}
			}

			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] {
				width: clamp(256px, 24vw, 336px) !important;
				max-width: 336px !important;
			}

			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .interface-complementary-area__header,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .interface-complementary-area-header,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .components-panel__header,
			.asneris-seo-plugin-sidebar .interface-complementary-area__header,
			.asneris-seo-plugin-sidebar .interface-complementary-area-header,
			.asneris-seo-plugin-sidebar .components-panel__header {
				background: var(--asneris-navy, #06295f) !important;
				color: var(--asneris-cyan, #4eb8c5) !important;
				padding: 6px 10px !important;
				min-height: 40px !important;
				border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
			}

			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .interface-complementary-area__header h2,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .interface-complementary-area__title,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .components-panel__header h2,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .components-panel__header .components-panel__header-title,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .components-panel__header .components-panel__header-label,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .interface-complementary-area__header .interface-complementary-area__header-title,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .interface-complementary-area__header .interface-complementary-area__header-item,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .interface-complementary-area-header .interface-complementary-area-header__title,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .interface-complementary-area-header .interface-complementary-area-header__item,
			.asneris-seo-plugin-sidebar .interface-complementary-area__header h2,
			.asneris-seo-plugin-sidebar .interface-complementary-area__title,
			.asneris-seo-plugin-sidebar .interface-complementary-area-header h2,
			.asneris-seo-plugin-sidebar .interface-complementary-area-header .interface-complementary-area-header__title,
			.asneris-seo-plugin-sidebar .interface-complementary-area-header .interface-complementary-area-header__item,
			.asneris-seo-plugin-sidebar .components-panel__header h2,
			.asneris-seo-plugin-sidebar .components-panel__header .components-panel__header-title,
			.asneris-seo-plugin-sidebar .components-panel__header .components-panel__header-label,
			.asneris-seo-plugin-sidebar .interface-complementary-area__header .interface-complementary-area__header-title,
			.asneris-seo-plugin-sidebar .interface-complementary-area__header .interface-complementary-area__header-item {
				color: var(--asneris-light-cyan, #ECF8FB) !important;
				font-size: 14px !important;
				line-height: 1.15 !important;
				font-family: var(--asneris-font-heading, 'Segoe UI', 'Inter', 'Helvetica Neue', Arial, sans-serif) !important;
				font-weight: 600 !important;
				margin: 0 !important;
			}

			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .interface-complementary-area__header .components-button,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .interface-complementary-area-header .components-button,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .components-panel__header .components-button,
			.asneris-seo-plugin-sidebar .interface-complementary-area-header .components-button {
				color: var(--asneris-light-cyan, #ECF8FB) !important;
				min-height: 30px !important;
				width: 30px !important;
				height: 30px !important;
				padding: 0 !important;
			}

			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .interface-complementary-area__header .components-button:hover,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .interface-complementary-area-header .components-button:hover,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .components-panel__header .components-button:hover,
			.asneris-seo-plugin-sidebar .interface-complementary-area-header .components-button:hover {
				background: rgba(255, 255, 255, 0.12) !important;
				color: var(--asneris-light-cyan, #ECF8FB) !important;
			}

			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .interface-complementary-area__header .components-button svg,
			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .components-panel__header .components-button svg {
				fill: currentColor;
			}

			@media (max-width: 1280px) {
				.interface-complementary-area[aria-label="Asneris SEO Toolkit"] {
					width: min(304px, 29vw) !important;
				}
			}

			@media (max-width: 782px) {
				.interface-complementary-area[aria-label="Asneris SEO Toolkit"] {
					width: 100vw !important;
					max-width: 100vw !important;
				}
			}

			.interface-complementary-area[aria-label="Asneris SEO Toolkit"] .components-panel__body {
				overflow-wrap: anywhere;
				background: var(--asneris-light-cyan, #ECF8FB) !important;
			}

			.asneris-seo-plugin-sidebar .components-panel__body {
				background: var(--asneris-light-cyan, #ECF8FB) !important;
				border-bottom: 2px solid #c4d3df !important;
			}

			.asneris-seo-plugin-sidebar .components-panel__body .components-panel__body-title {
				margin: 0 !important;
			}

			.asneris-seo-plugin-sidebar .components-panel__body .components-panel__body-toggle {
				padding: 4px 10px !important;
				min-height: 34px !important;
			}

			.asneris-seo-plugin-sidebar .components-panel__body .components-panel__body-toggle .components-panel__arrow {
				width: 16px !important;
				height: 16px !important;
			}

			.asneris-seo-plugin-sidebar .components-panel__body:last-of-type {
				border-bottom-width: 0 !important;
			}

			.asneris-seo-plugin-sidebar .components-panel__header {
				padding: 6px 10px !important;
				min-height: 40px !important;
			}

			.asneris-seo-plugin-sidebar .components-panel__header h2,
			.asneris-seo-plugin-sidebar .components-panel__header .components-panel__header-title,
			.asneris-seo-plugin-sidebar .components-panel__header .components-panel__header-label {
				font-size: 14px !important;
				line-height: 1.15 !important;
				margin: 0 !important;
			}

			.asneris-seo-plugin-sidebar .components-panel__header .components-button {
				min-height: 30px !important;
				width: 30px !important;
				height: 30px !important;
				padding: 0 !important;
			}
		`;

		document.head.appendChild( styleElement );
	}, [] );

	useEffect( () => {
		const targetTitle = 'Asneris SEO Toolkit';
		const headerSelector = '.interface-complementary-area__header, .interface-complementary-area-header, .components-panel__header';
		const titleSelector = 'h2, .interface-complementary-area__title, .interface-complementary-area__header-title, .interface-complementary-area-header__title, .components-panel__header-title, .components-panel__header-label';

		const applyHeaderTheme = () => {
			document.querySelectorAll( headerSelector ).forEach( ( header ) => {
				const headerText = ( header.textContent || '' ).trim();
				const inAsnerisSidebar = !! header.closest( `.${ ASNERIS_SIDEBAR_CLASS }` );
				const isAsnerisHeader = inAsnerisSidebar || headerText.includes( targetTitle );

				if ( ! isAsnerisHeader ) {
					return;
				}

				header.style.background = 'var(--asneris-navy, #06295f)';
				header.style.color = 'var(--asneris-cyan, #4eb8c5)';
				header.style.borderBottom = '1px solid rgba(255, 255, 255, 0.2)';

				header.querySelectorAll( titleSelector ).forEach( ( titleNode ) => {
					titleNode.style.color = 'var(--asneris-light-cyan, #ECF8FB)';
					titleNode.style.fontFamily = "var(--asneris-font-heading, 'Segoe UI', 'Inter', 'Helvetica Neue', Arial, sans-serif)";
					titleNode.style.fontWeight = '600';
				} );

				header.querySelectorAll( '.components-button' ).forEach( ( button ) => {
					button.style.color = 'var(--asneris-light-cyan, #ECF8FB)';
				} );
			} );
		};

		applyHeaderTheme();

		const observer = new MutationObserver( () => {
			applyHeaderTheme();
		} );

		observer.observe( document.body, {
			childList: true,
			subtree: true,
		} );

		return () => {
			observer.disconnect();
		};
	}, [] );

	const openAsnerisSidebar = useCallback( () => {
		debugSidebar( 'openAsnerisSidebar:start', {
			sidebarId: ASNERIS_SIDEBAR_ID,
			fallbackId: ASNERIS_SIDEBAR_FALLBACK_ID,
			hasOpenGeneralSidebar: !! openGeneralSidebar,
			hasEnableComplementaryArea: !! enableComplementaryArea,
		} );

		try {
			if ( openGeneralSidebar ) {
				openGeneralSidebar( ASNERIS_SIDEBAR_ID );
				debugSidebar( 'dispatch:openGeneralSidebar', {
					sidebarId: ASNERIS_SIDEBAR_ID,
				} );
			}

			if ( enableComplementaryArea ) {
				ASNERIS_INTERFACE_SCOPES.forEach( ( scope ) => {
					enableComplementaryArea( scope, ASNERIS_SIDEBAR_ID );
					debugSidebar( 'dispatch:enableComplementaryArea', {
						scope,
						sidebarId: ASNERIS_SIDEBAR_ID,
					} );
				} );
			}
		} catch ( error ) {
			debugSidebar( 'dispatch:error', {
				sidebarId: ASNERIS_SIDEBAR_ID,
				error: error?.message || String( error ),
			} );
		}

		// Compatibility fallback for WordPress builds that resolve the sidebar by slug.
		setTimeout( () => {
			const wpSelect = window.wp?.data?.select;
			if ( ! wpSelect ) {
				return;
			}

			const activeCandidates = getActiveSidebarCandidates( wpSelect );
			const isPrimaryActive = activeCandidates.includes( ASNERIS_SIDEBAR_ID );
			const isFallbackActive = activeCandidates.includes(
				ASNERIS_SIDEBAR_FALLBACK_ID
			);

			debugSidebar( 'dispatch:post-check', {
				activeCandidates,
				isPrimaryActive,
				isFallbackActive,
			} );

			if ( isPrimaryActive || isFallbackActive ) {
				return;
			}

			try {
				if ( openGeneralSidebar ) {
					openGeneralSidebar( ASNERIS_SIDEBAR_FALLBACK_ID );
					debugSidebar( 'dispatch:fallback-openGeneralSidebar', {
						sidebarId: ASNERIS_SIDEBAR_FALLBACK_ID,
					} );
				}

				if ( enableComplementaryArea ) {
					ASNERIS_INTERFACE_SCOPES.forEach( ( scope ) => {
						enableComplementaryArea(
							scope,
							ASNERIS_SIDEBAR_FALLBACK_ID
						);
						debugSidebar( 'dispatch:fallback-enableComplementaryArea', {
							scope,
							sidebarId: ASNERIS_SIDEBAR_FALLBACK_ID,
						} );
					} );
				}
			} catch ( error ) {
				debugSidebar( 'dispatch:fallback-error', {
					sidebarId: ASNERIS_SIDEBAR_FALLBACK_ID,
					error: error?.message || String( error ),
				} );
			}
		}, 0 );
	}, [ openGeneralSidebar, enableComplementaryArea ] );

	const closeAsnerisSidebar = useCallback( () => {
		debugSidebar( 'closeAsnerisSidebar:start', {
			hasCloseGeneralSidebar: !! closeGeneralSidebar,
			hasOpenGeneralSidebar: !! openGeneralSidebar,
			hasDisableComplementaryArea: !! disableComplementaryArea,
		} );

		try {
			if ( closeGeneralSidebar ) {
				closeGeneralSidebar();
				debugSidebar( 'dispatch:closeGeneralSidebar' );
			}

			if ( disableComplementaryArea ) {
				ASNERIS_INTERFACE_SCOPES.forEach( ( scope ) => {
					disableComplementaryArea( scope );
					debugSidebar( 'dispatch:disableComplementaryArea', { scope } );
				} );
			}

			// Compatibility fallback for builds without close action.
			if ( ! closeGeneralSidebar && openGeneralSidebar ) {
				openGeneralSidebar( '' );
				debugSidebar( 'dispatch:fallback-openGeneralSidebar-empty' );
			}
		} catch ( error ) {
			debugSidebar( 'dispatch:close-error', {
				error: error?.message || String( error ),
			} );
		}
	}, [ closeGeneralSidebar, disableComplementaryArea, openGeneralSidebar ] );

	const handleMoreMenuClick = useCallback( () => {
		debugSidebar( 'menu:click', {
			workspaceEnabled: false,
		} );

		openAsnerisSidebar();
	}, [ openAsnerisSidebar ] );

	const handleLauncherClick = useCallback( () => {
		if ( isAsnerisSidebarActive ) {
			closeAsnerisSidebar();
			return;
		}

		openAsnerisSidebar();
	}, [ isAsnerisSidebarActive, closeAsnerisSidebar, openAsnerisSidebar ] );

	const openAppearanceDialog = useCallback( () => {
		setManagerDialogTab( 'appearance' );
		setIsManagerDialogOpen( true );
	}, [] );

	const openSocialDialog = useCallback( () => {
		setManagerDialogTab( 'social' );
		setIsManagerDialogOpen( true );
	}, [] );

	const openAiDialog = useCallback( () => {
		setManagerDialogTab( 'ai' );
		setIsManagerDialogOpen( true );
	}, [] );

	// Monitor save errors
	const saveError = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		return editor.getLastEntitySaveError?.(
			'postType',
			editor.getCurrentPostType(),
			editor.getCurrentPostId()
		);
	} );

	useEffect( () => {
		if ( saveError && saveError.message ) {
			// Check if it's a meta validation error
			if (
				saveError.message.includes( 'meta' ) ||
				saveError.message.includes( 'WP_Error' )
			) {
				createNotice(
					'error',
					__(
						'Some SEO fields contain invalid data and were not saved. Please check the error messages above each field.',
						'asneris-seo-toolkit'
					),
					{
						type: 'snackbar',
						isDismissible: true,
					}
				);
			}
		}
	}, [ saveError, createNotice ] );

	useEffect( () => {
		debugSidebar( 'sidebar:active-state', {
			isAsnerisSidebarActive,
		} );
	}, [ isAsnerisSidebarActive ] );

	// Auto-open sidebar when accessed via ?asneris-seo-open=1
	useEffect( () => {
		const shouldOpen = sessionStorage.getItem( 'asneris-seo-open' );
		debugSidebar( 'session:auto-open-flag', {
			shouldOpen,
		} );

		if ( shouldOpen !== '1' ) {
			return;
		}

		// Clear the flag so it only opens once
		sessionStorage.removeItem( 'asneris-seo-open' );

		// Attempt to open the sidebar
		setTimeout( () => {
			debugSidebar( 'session:auto-open-dispatch' );
			openAsnerisSidebar();
		}, 100 );
	}, [ openAsnerisSidebar ] );

	if ( hasConflicts ) {
		return (
			<>
				<PluginSidebarMoreMenuItem target={ ASNERIS_SIDEBAR_SLUG }>
					{ __( 'Asneris SEO Toolkit', 'asneris-seo-toolkit' ) }
				</PluginSidebarMoreMenuItem>

				<PluginSidebar
					name={ ASNERIS_SIDEBAR_SLUG }
					className={ ASNERIS_SIDEBAR_CLASS }
					title={
						<span style={ { color: 'var(--asneris-cyan, #4eb8c5)' } }>
							{ __( 'Asneris SEO Toolkit', 'asneris-seo-toolkit' ) }
						</span>
					}
					icon="search"
				>
					<PanelBody
						title={ __(
							'SEO Plugin Conflict',
							'asneris-seo-toolkit'
						) }
						initialOpen={ true }
					>
						<AlertMessage tone="warning">
							{ __(
								'Another SEO plugin is active. Asneris editor controls are disabled to avoid conflicting SEO output.',
								'asneris-seo-toolkit'
							) }
						</AlertMessage>
						{ conflictNames.length > 0 && (
							<p
								style={ {
									marginTop: '12px',
									marginBottom: '12px',
								} }
							>
								<strong>
									{ __(
										'Detected plugins:',
										'asneris-seo-toolkit'
									) }
								</strong>{ ' ' }
								{ conflictNames.join( ', ' ) }
							</p>
						) }
						<ExternalLink href={ pluginsUrl }>
							{ __( 'Manage Plugins', 'asneris-seo-toolkit' ) }
						</ExternalLink>
					</PanelBody>
				</PluginSidebar>
			</>
		);
	}

	return (
		<>
			<AsnerisTopRightLauncher
				seoScore={ launcherSeoScore }
				aiScore={ launcherAiScore }
				onOpenToolkit={ handleLauncherClick }
			/>

			<PluginSidebarMoreMenuItem
				target={ ASNERIS_SIDEBAR_SLUG }
				onClick={ handleMoreMenuClick }
			>
				{ __( 'Asneris SEO Toolkit', 'asneris-seo-toolkit' ) }
			</PluginSidebarMoreMenuItem>

			<PluginSidebar
				name={ ASNERIS_SIDEBAR_SLUG }
				className={ ASNERIS_SIDEBAR_CLASS }
				title={
					<span style={ { color: 'var(--asneris-cyan, #4eb8c5)' } }>
						{ __( 'Asneris SEO Toolkit', 'asneris-seo-toolkit' ) }
					</span>
				}
				icon="search"
			>
				<OverviewPanel
					activeRoute={ activeRoute }
					navigate={ navigate }
					expandedSection={ expandedSection }
					onExpand={ handleExpandSection }
					onOpenAppearanceDialog={ openAppearanceDialog }
					seoScore={ launcherSeoScore }
					useLocalScoreFallback={ false }
					seoSuggestions={ launcherSeoSuggestions }
					SEOScoreComponent={ SEOScore }
				/>

				{/* <SeoReadinessPanel
					activeRoute={ activeRoute }
					expandedSection={ expandedSection }
					onExpand={ handleExpandSection }
				/>

				<DiagnosticsPanel
					activeRoute={ activeRoute }
					expandedSection={ expandedSection }
					onExpand={ handleExpandSection }
					DiagnosticsPreviewComponent={ () => (
						<DiagnosticsPreview onOpenReport={ ( postId ) => openDiscoverabilityReport( postId ) } />
					) }
				/>



				<AiDiscoverabilityPanel
					activeRoute={ activeRoute }
					expandedSection={ expandedSection }
					onExpand={ handleExpandSection }
				/> */}

				<RobotsMetaPanel
					expandedSection={ expandedSection }
					onExpand={ handleExpandSection }
					RobotsControlComponent={ RobotsControl }
				/>

				<IndexNowPanel
					expandedSection={ expandedSection }
					onExpand={ handleExpandSection }
					IndexNowSubmitComponent={ IndexNowSubmit }
				/>

				<SchemaPanel
					expandedSection={ expandedSection }
					onExpand={ handleExpandSection }
					schemaEnabled={ schemaEnabled }
					onSchemaToggle={ ( v ) =>
						editPost( { meta: { _ASNERISSEO_schema_enabled: v } } )
					}
					RobotsControlComponent={ RobotsControl }
				/>

				<div style={ { padding: '0 16px 12px', marginTop: '8px' } }>
					<Button
						variant="primary"
						className="ASNERISSEO-react-button ASNERISSEO-react-button-primary"
						onClick={ () => openDiscoverabilityReport( resolvedEditorPostId ) }
						style={ { width: '100%' } }
					>
						{ __( 'Page SEO Discoverability Report', 'asneris-seo-toolkit' ) }
					</Button>
				</div>
			</PluginSidebar>

			<AsnerisModal
				isOpen={ isWorkspaceDrawerOpen }
				title={
					<AsnerisModalTitle
						text={ __( 'Page SEO Discoverability Report', 'asneris-seo-toolkit' ) }
					/>
				}
				onRequestClose={ () => setIsWorkspaceDrawerOpen( false ) }
				showFooter={ false }
				className="asneris-fixed-workspace-modal"
			>
				<PageDiagnosticsPanel
					restUrl={ pageDiagnosticsRestUrl }
					restNonce={ window.asnerisseoData?.restNonce || '' }
					onStatus={ handlePanelStatus }
					normalizeChecks={ normalizeChecks }
					initialPostId={ workspaceDetailPostId }
					detailOpenToken={ workspaceDetailOpenToken }
					embeddedInEditorModal={ true }
					editorIsDirty={ isEditorDirty }
					editorDraftContext={ editorDraftDiagnosticsContext }
					embeddedInitialDiagnostics={ embeddedInitialDiagnostics }
					onEmbeddedRequestClose={ () => setIsWorkspaceDrawerOpen( false ) }
				/>
			</AsnerisModal>

			<AsnerisModal
				isOpen={ isPhaseInfoOpen }
				title={ __( 'Phase 1-7 Scope', 'asneris-seo-toolkit' ) }
				onRequestClose={ () => setIsPhaseInfoOpen( false ) }
			>
				<ul>
					<li>
						{ __(
							'Reusable UI components and sidebar route state are in place.',
							'asneris-seo-toolkit'
						) }
					</li>
					<li>
						{ __(
							'Phase 2 modernized Search Appearance with live preview, fallback indicators, and snippet quality checks.',
							'asneris-seo-toolkit'
						) }
					</li>
					<li>
						{ __(
							'Phase 3 modernized Social Preview with platform cards, fallback indicators, and media library image selection.',
							'asneris-seo-toolkit'
						) }
					</li>
					<li>
						{ __(
							'Phase 4 adds a live Indexability score with checks for title, description, canonical, robots, headings, image alt coverage, and internal links.',
							'asneris-seo-toolkit'
						) }
					</li>
					<li>
						{ __(
							'Phase 5 adds local AI Discoverability analysis for structure, clarity, entity signals, knowledge signals, trust signals, and content completeness.',
							'asneris-seo-toolkit'
						) }
					</li>
					<li>
						{ __(
							'Phase 6 adds a lightweight sidebar quick review and a full bottom-drawer discoverability workspace.',
							'asneris-seo-toolkit'
						) }
					</li>
					<li>
						{ __(
							'Phase 7 adds smart recommendations for internal linking, discoverability improvements, and search visibility tuning.',
							'asneris-seo-toolkit'
						) }
					</li>
					<li>
						{ __(
							'No meta keys, settings storage, or frontend SEO output were changed.',
							'asneris-seo-toolkit'
						) }
					</li>
				</ul>
			</AsnerisModal>

			<AsnerisManagerModal
				isOpen={ isManagerDialogOpen }
				onRequestClose={ () => setIsManagerDialogOpen( false ) }
				initialTab={ managerDialogTab }
				seoTitleValue={ seoTitleValue }
				seoDescriptionValue={ seoDescriptionValue }
				MetaFieldComponent={ MetaField }
				CharacterCountComponent={ CharacterCount }
			/>
		</>
	);
};

registerPlugin( 'asneris-seo-sidebar', {
	render: AsnerisSeoSidebar,
} );



