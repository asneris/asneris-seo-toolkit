import { __ } from '@wordpress/i18n';

const MediaUrlField = ({
	label,
	value,
	onChange,
	uploadTitle,
	uploadButtonLabel,
	description,
	previewMaxWidth = '220px',
}) => {
	const openMediaPicker = () => {
		const mediaApi = window?.wp?.media;
		if (!mediaApi) {
			window.alert(__('Media uploader is not available on this screen. Please paste an image URL manually.', 'asneris-seo-toolkit'));
			return;
		}

		const frame = mediaApi({
			title: uploadTitle,
			button: { text: uploadButtonLabel },
			multiple: false,
			library: {
				type: 'image',
			},
		});

		frame.on('select', () => {
			const selection = frame.state().get('selection');
			const attachment = selection?.first()?.toJSON?.();
			if (attachment?.url) {
				onChange(attachment.url);
			}
		});

		frame.open();
	};

	return (
		<label className="ASNERISSEO-react-field-label">
			<div style={ { marginBottom: '4px', fontWeight: 600 } }>{ label }</div>
			<div style={ { display: 'flex', gap: '8px', flexWrap: 'wrap', alignItems: 'center' } }>
				<input className="regular-text ASNERISSEO-react-input" value={ value } onChange={ (e) => onChange(e.target.value) } />
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" onClick={ openMediaPicker }>{ uploadButtonLabel }</button>
				{ value ? (
					<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-danger" onClick={ () => onChange('') }>
						<span className="dashicons dashicons-trash ASNERISSEO-react-button-icon" aria-hidden="true" />
						{ __('Remove', 'asneris-seo-toolkit') }
					</button>
				) : null }
			</div>
			{ description ? <p style={ { marginTop: '4px', color: '#50575e' } }>{ description }</p> : null }
			{ value ? (
				<div style={ { marginTop: '8px' } }>
					<img src={ value } alt={ label } style={ { maxWidth: previewMaxWidth, height: 'auto', borderRadius: '6px', border: '1px solid #d5e2ed' } } />
				</div>
			) : null }
		</label>
	);
};

export default MediaUrlField;
