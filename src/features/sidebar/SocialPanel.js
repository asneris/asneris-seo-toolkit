import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { PanelHeaderBadge, SectionBox } from '../../components/ui';
import SidebarSectionShell from './SidebarSectionShell';

const truncateText = ( value, maxLength ) => {
	const normalized = String( value || '' ).trim();
	if ( normalized.length <= maxLength ) {
		return normalized;
	}

	return `${ normalized.slice( 0, maxLength - 1 ).trim() }...`;
};

const stripHtmlText = ( value ) =>
	String( value || '' )
		.replace( /<[^>]*>/g, ' ' )
		.replace( /\s+/g, ' ' )
		.trim();

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

const getTodayIsoDate = () => new Date().toISOString().split( 'T' )[ 0 ];

const SocialPreviewCard = ( { network, title, description, imageUrl } ) => (
	<div
		style={ {
			border: '1px solid #dcdcde',
			borderRadius: '8px',
			overflow: 'hidden',
			background: '#fff',
		} }
	>
		<div
			style={ {
				padding: '6px 10px',
				fontSize: 'var(--asneris-table-chip-size)',
				fontWeight: 'var(--asneris-h3-weight)',
				background: '#f6f7f7',
				borderBottom: '1px solid #dcdcde',
			} }
		>
			{ network }
		</div>
		<div
			style={ {
				height: '120px',
				background: '#eef1f4',
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				color: '#646970',
				fontSize: 'var(--asneris-table-chip-size)',
			} }
		>
			{ imageUrl ? (
				<img
					src={ imageUrl }
					alt={ __( 'Social preview', 'asneris-seo-toolkit' ) }
					style={ {
						width: '100%',
						height: '100%',
						objectFit: 'cover',
					} }
				/>
			) : (
				__( 'No social image selected', 'asneris-seo-toolkit' )
			) }
		</div>
		<div style={ { padding: '10px' } }>
			<div
				style={ {
					fontWeight: 'var(--asneris-h3-weight)',
					fontSize: 'var(--asneris-body-size)',
					marginBottom: '4px',
				} }
			>
				{ title || __( 'Social title preview', 'asneris-seo-toolkit' ) }
			</div>
			<div
				style={ {
					fontSize: 'var(--asneris-helper-size)',
					color: '#50575e',
					lineHeight: 'var(--asneris-body-line)',
				} }
			>
				{ description ||
					__(
						'Social description preview appears here.',
						'asneris-seo-toolkit'
					) }
			</div>
		</div>
	</div>
);

const SocialPanel = ( {
	activeRoute,
	expandedSection,
	onExpand,
	onOpenWorkflow,
	MetaFieldComponent,
	separateByNetwork = false,
} ) => {
	const MetaField = MetaFieldComponent;
	const [ activeNetwork, setActiveNetwork ] = useState( 'facebook' );
	const { editPost } = useDispatch( 'core/editor' );
	const meta = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'meta' )
	);
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
	const postDate = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'date' )
	);

	const ogTitle = String( meta?._ASNERISSEO_og_title || '' ).trim();
	const ogDescription = String( meta?._ASNERISSEO_og_description || '' ).trim();
	const ogImage = String( meta?._ASNERISSEO_og_image || '' ).trim();
	const ogImageDisabled = !! meta?._ASNERISSEO_og_image_disabled;
	const seoTitleManual = String( meta?._ASNERISSEO_title || '' ).trim();
	const seoDescriptionManual = String( meta?._ASNERISSEO_description || '' ).trim();
	const excerptValue = getExcerptSourceText( postExcerpt, postContent );
	const titleTemplates = window.asnerisseoData?.titleTemplates || {};
	const descriptionTemplates = window.asnerisseoData?.descriptionTemplates || {};
	const variableContext = {
		title: String( postTitle || '' ).trim(),
		site: String( window.asnerisseoData?.siteName || '' ).trim(),
		separator: String( window.asnerisseoData?.titleSeparator || '|' ),
		excerpt: truncateText( stripHtmlText( excerptValue ), 160 ),
		date: String( postDate || '' ).split( 'T' )[ 0 ] || getTodayIsoDate(),
		author: String( window.asnerisseoData?.authorName || '' ).trim(),
		term: String( window.asnerisseoData?.primaryTerm || '' ).trim(),
	};
	const resolvedSeoTitleManual = resolveInlineVariables( seoTitleManual, variableContext );
	const resolvedDefaultSeoTitle = resolveInlineVariables(
		String( titleTemplates?.[ postType ] || '' ),
		variableContext
	);
	const resolvedOgTitle = resolveInlineVariables( ogTitle, variableContext );
	const resolvedSeoDescriptionManual = resolveInlineVariables(
		String( seoDescriptionManual || '' ),
		variableContext
	)
		.replace( /<[^>]*>/g, '' )
		.trim();
	const resolvedDefaultSeoDescription = resolveInlineVariables(
		String( descriptionTemplates?.[ postType ] || '' ),
		variableContext
	)
		.replace( /<[^>]*>/g, '' )
		.trim();
	const resolvedOgDescription = resolveInlineVariables( ogDescription, variableContext );
	const excerptFallbackDescription = stripHtmlText( excerptValue );
	const isUsingDefaultTitleTemplate =
		!resolvedOgTitle && !resolvedSeoTitleManual && !!resolvedDefaultSeoTitle;
	const isUsingDefaultDescriptionTemplate =
		!resolvedOgDescription && !resolvedSeoDescriptionManual && !!resolvedDefaultSeoDescription;

	const effectiveTitle = truncateText(
		resolvedOgTitle || resolvedSeoTitleManual || resolvedDefaultSeoTitle || String( postTitle || '' ).trim(),
		70
	);
	const effectiveDescription = truncateText(
		resolvedOgDescription || resolvedSeoDescriptionManual || resolvedDefaultSeoDescription || excerptFallbackDescription,
		140
	);
	const effectiveImage = ogImageDisabled ? '' : ogImage;

	const socialTone = ogTitle && ogDescription && effectiveImage ? 'success' : 'warning';
	const socialLabel =
		socialTone === 'success'
			? __( 'Social ready', 'asneris-seo-toolkit' )
			: __( 'Needs social setup', 'asneris-seo-toolkit' );
	const socialNetworks = [
		{
			id: 'facebook',
			label: __( 'Facebook', 'asneris-seo-toolkit' ),
			previewLabel: __( 'Facebook Preview', 'asneris-seo-toolkit' ),
		},
		{
			id: 'linkedin',
			label: __( 'LinkedIn', 'asneris-seo-toolkit' ),
			previewLabel: __( 'LinkedIn Preview', 'asneris-seo-toolkit' ),
		},
		{
			id: 'x',
			label: __( 'X', 'asneris-seo-toolkit' ),
			previewLabel: __( 'X Preview', 'asneris-seo-toolkit' ),
		},
		{
			id: 'pinterest',
			label: __( 'Pinterest', 'asneris-seo-toolkit' ),
			previewLabel: __( 'Pinterest Preview', 'asneris-seo-toolkit' ),
		},
		{
			id: 'whatsapp',
			label: __( 'WhatsApp', 'asneris-seo-toolkit' ),
			previewLabel: __( 'WhatsApp Preview', 'asneris-seo-toolkit' ),
		},
		{
			id: 'discord',
			label: __( 'Discord', 'asneris-seo-toolkit' ),
			previewLabel: __( 'Discord Preview', 'asneris-seo-toolkit' ),
		},
		{
			id: 'telegram',
			label: __( 'Telegram', 'asneris-seo-toolkit' ),
			previewLabel: __( 'Telegram Preview', 'asneris-seo-toolkit' ),
		},
		{
			id: 'slack',
			label: __( 'Slack', 'asneris-seo-toolkit' ),
			previewLabel: __( 'Slack Preview', 'asneris-seo-toolkit' ),
		},
	];
	const selectedNetwork =
		socialNetworks.find( ( network ) => network.id === activeNetwork ) ||
		socialNetworks[ 0 ];
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

	const openMediaPicker = () => {
		if ( ! window.wp?.media ) {
			return;
		}

		const frame = window.wp.media( {
			title: __( 'Select Social Image', 'asneris-seo-toolkit' ),
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

	return (
		<SidebarSectionShell
			sectionKey="social"
			expandedSection={ expandedSection }
			onExpand={ onExpand }
			title={ __( 'Social Media (Open Graph)', 'asneris-seo-toolkit' ) }
			headerDescription={ __( 'Control how your content appears on social platforms', 'asneris-seo-toolkit' ) }
			headerIcon="dashicons dashicons-share"
			headerIconStyle={ { background: '#f5efff', color: '#7e3af2' } }
			initialOpen={ activeRoute === 'social' }
			headerAction={ <PanelHeaderBadge label={ socialLabel } tone={ socialTone } /> }
		>
			<SectionBox>
				{ separateByNetwork ? (
					<>
						<div
							style={ {
								display: 'flex',
								gap: '8px',
								flexWrap: 'wrap',
								marginBottom: '8px',
							} }
						>
							{ socialNetworks.map( ( network ) => (
								<Button
									key={ network.id }
									variant={
										activeNetwork === network.id
											? 'primary'
											: 'secondary'
									}
									className={
										activeNetwork === network.id
											? 'ASNERISSEO-react-button ASNERISSEO-react-button-primary'
											: 'ASNERISSEO-react-button ASNERISSEO-react-button-secondary'
									}
									onClick={ () => setActiveNetwork( network.id ) }
								>
									{ network.label }
								</Button>
							) ) }
						</div>
						<SocialPreviewCard
							network={ selectedNetwork.previewLabel }
							title={ effectiveTitle }
							description={ effectiveDescription }
							imageUrl={ effectiveImage }
						/>
						{ isUsingDefaultTitleTemplate || isUsingDefaultDescriptionTemplate ? (
							<div style={ { display: 'flex', gap: '6px', flexWrap: 'wrap', marginTop: '8px' } }>
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
					</>
				) : (
					<div style={ { display: 'grid', gap: '8px' } }>
						{ socialNetworks.map( ( network ) => (
							<SocialPreviewCard
								key={ network.id }
								network={ network.previewLabel }
								title={ effectiveTitle }
								description={ effectiveDescription }
								imageUrl={ effectiveImage }
							/>
						) ) }
						{ isUsingDefaultTitleTemplate || isUsingDefaultDescriptionTemplate ? (
							<div style={ { display: 'flex', gap: '6px', flexWrap: 'wrap' } }>
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
					</div>
				) }
			</SectionBox>

			<SectionBox>
				<div style={ { marginBottom: '8px' } }>
					<PanelHeaderBadge
						label={
							ogTitle
								? __( 'Social title: manual', 'asneris-seo-toolkit' )
								: isUsingDefaultTitleTemplate
								? __( 'Using default title template', 'asneris-seo-toolkit' )
								: __( 'Using SEO title fallback', 'asneris-seo-toolkit' )
						}
						tone={ ogTitle ? 'info' : 'warning' }
					/>
				</div>
				<MetaField
					label={ __( 'Social Title', 'asneris-seo-toolkit' ) }
					metaKey="_ASNERISSEO_og_title"
					placeholder="Title for social media"
					help="Leave empty to use SEO title"
					onInteractionRedirect={ onOpenWorkflow }
				/>
			</SectionBox>

			<SectionBox>
				<div style={ { marginBottom: '8px' } }>
					<PanelHeaderBadge
						label={
							ogDescription
								? __( 'Social description: manual', 'asneris-seo-toolkit' )
								: isUsingDefaultDescriptionTemplate
								? __( 'Using default description template', 'asneris-seo-toolkit' )
								: __( 'Using meta description fallback', 'asneris-seo-toolkit' )
						}
						tone={ ogDescription ? 'info' : 'warning' }
					/>
				</div>
				<MetaField
					label={ __( 'Social Description', 'asneris-seo-toolkit' ) }
					metaKey="_ASNERISSEO_og_description"
					type="textarea"
					placeholder="Description for social media"
					help="Leave empty to use meta description"
					onInteractionRedirect={ onOpenWorkflow }
				/>
			</SectionBox>

			<SectionBox>
				<div style={ { marginBottom: '8px' } }>
					<PanelHeaderBadge
						label={
							ogImageDisabled
								? __( 'Image disabled', 'asneris-seo-toolkit' )
								: ogImage
								? __( 'Image selected', 'asneris-seo-toolkit' )
								: __( 'Image missing', 'asneris-seo-toolkit' )
						}
						tone={ ogImageDisabled ? 'neutral' : ogImage ? 'success' : 'warning' }
					/>
				</div>
				<MetaField
					label={ __( 'Social Image URL', 'asneris-seo-toolkit' ) }
					metaKey="_ASNERISSEO_og_image"
					placeholder="https://example.com/image.jpg"
					help="Recommended: 1200x630px"
					onInteractionRedirect={ onOpenWorkflow }
				/>
				<div
					style={ {
						display: 'flex',
						gap: '8px',
						flexWrap: 'wrap',
					} }
				>
					<Button
						variant="secondary"
						className="ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
						onClick={ () => ( onOpenWorkflow ? onOpenWorkflow() : openMediaPicker() ) }
					>
						{ __( 'Choose from Media Library', 'asneris-seo-toolkit' ) }
					</Button>
					{ ( ogImage || ogImageDisabled ) && (
						<Button
							variant="secondary"
							className="ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
							onClick={ () =>
								onOpenWorkflow
									? onOpenWorkflow()
									: editPost( {
											meta: {
												_ASNERISSEO_og_image: '',
												_ASNERISSEO_og_image_disabled: true,
											},
									  } )
							}
						>
							{ __( 'Remove Image', 'asneris-seo-toolkit' ) }
						</Button>
					) }
					{ ogImageDisabled ? (
						<Button
							variant="secondary"
							className="ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
							onClick={ () =>
								onOpenWorkflow
									? onOpenWorkflow()
									: editPost( {
											meta: {
												_ASNERISSEO_og_image_disabled: false,
											},
									  } )
							}
						>
							{ __( 'Use fallback image', 'asneris-seo-toolkit' ) }
						</Button>
					) : null }
				</div>
				<p style={ { marginTop: '8px', fontSize: 'var(--asneris-helper-size)', color: '#646970' } }>
					{ __(
						'Recommended image size: 1200 x 630 for best cross-platform preview quality.',
						'asneris-seo-toolkit'
					) }
				</p>
			</SectionBox>
		</SidebarSectionShell>
	);
};

export default SocialPanel;

