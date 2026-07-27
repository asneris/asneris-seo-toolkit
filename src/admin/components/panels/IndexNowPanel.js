import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import RestSettingsPanel from '../common/RestSettingsPanel';
import InlineHelpDetails from '../common/InlineHelpDetails';

const IndexNowPanel = ({ restUrl, restNonce, onStatus }) => {
	const [notice, setNotice] = useState('');

	return (
		<RestSettingsPanel
			restUrl={ restUrl }
			restNonce={ restNonce }
			onStatus={ onStatus }
			title={ __('IndexNow', 'asneris-seo-toolkit') }
			description={ __('Manage IndexNow mode and key via REST-backed settings.', 'asneris-seo-toolkit') }
			loadMessage={ __('Loading IndexNow settings...', 'asneris-seo-toolkit') }
			saveMessage={ __('IndexNow settings saved successfully.', 'asneris-seo-toolkit') }
			loadErrorMessage={ __('Unable to load IndexNow settings.', 'asneris-seo-toolkit') }
			saveErrorMessage={ __('Failed to save IndexNow settings.', 'asneris-seo-toolkit') }
			initialForm={ {
				indexnow_enabled: false,
				indexnow_key_mode: 'auto',
				indexnow_key: '',
				indexnow_key_url: '',
			} }
			mapLoadToForm={ (payload) => ({
				indexnow_enabled: !!payload.indexnow_enabled,
				indexnow_key_mode: payload.indexnow_key_mode || 'auto',
				indexnow_key: payload.indexnow_key || '',
				indexnow_key_url: payload.indexnow_key_url || '',
			}) }
			mapSaveToForm={ (saved) => ({
				indexnow_enabled: !!saved.indexnow_enabled,
				indexnow_key_mode: saved.indexnow_key_mode || 'auto',
				indexnow_key: saved.indexnow_key || '',
				indexnow_key_url: saved.indexnow_key_url || '',
			}) }
			onAfterSave={ (payload) => {
				setNotice(payload?.key_management_notice || '');
			} }
			renderFields={ (form, updateField) => (
				<>
					<InlineHelpDetails
						title={ __('Help: IndexNow', 'asneris-seo-toolkit') }
						items={ [
							__('IndexNow notifies participating engines when content changes.', 'asneris-seo-toolkit'),
							__('Supported by Bing, Yandex, and other IndexNow partners.', 'asneris-seo-toolkit'),
							__('Auto-generated keys are recommended for most sites.', 'asneris-seo-toolkit'),
						] }
						note={ __('IndexNow can improve discovery speed, but indexing is not guaranteed.', 'asneris-seo-toolkit') }
					/>
					<label className="ASNERISSEO-react-field-label">
						<div className="ASNERISSEO-react-field-label">{ __('Enable IndexNow', 'asneris-seo-toolkit') }</div>
						<input type="checkbox" checked={ !!form.indexnow_enabled } onChange={ (e) => updateField('indexnow_enabled', e.target.checked) } />
					</label>
					<p className="ASNERISSEO-react-helper-text-tight">{ __('Automatically submit updated URLs to participating search engines (Google not included).', 'asneris-seo-toolkit') }</p>
					<label className="ASNERISSEO-react-field-label">
						<div className="ASNERISSEO-react-field-label">{ __('Key Mode', 'asneris-seo-toolkit') }</div>
						<select className="regular-text ASNERISSEO-react-select" value={ form.indexnow_key_mode } onChange={ (e) => updateField('indexnow_key_mode', e.target.value) }>
							<option value="auto">auto</option>
							<option value="custom">custom</option>
						</select>
						<p className="ASNERISSEO-react-helper-text">
							{ __('Use auto mode for most sites. Use custom mode for key reuse across environments or controlled migration.', 'asneris-seo-toolkit') }
						</p>
					</label>
					{ form.indexnow_key_mode === 'custom' ? (
						<label className="ASNERISSEO-react-field-label">
							<div className="ASNERISSEO-react-field-label">{ __('IndexNow Key', 'asneris-seo-toolkit') }</div>
							<input className="regular-text ASNERISSEO-react-input" value={ form.indexnow_key } onChange={ (e) => updateField('indexnow_key', e.target.value) } />
							<p className="ASNERISSEO-react-helper-text">{ __('Custom key validation: alphanumeric only, 8-128 characters.', 'asneris-seo-toolkit') }</p>
						</label>
					) : (
						<p className="ASNERISSEO-react-helper-text-tight">{ __('Key is system-managed in auto mode.', 'asneris-seo-toolkit') }</p>
					) }
					{ form.indexnow_key_url ? <p className="ASNERISSEO-react-mb-0"><strong>{ __('Key URL:', 'asneris-seo-toolkit') }</strong> { form.indexnow_key_url }</p> : null }
					{ form.indexnow_key_url ? (
						<p className="ASNERISSEO-react-helper-text">
							<a href={ form.indexnow_key_url } target="_blank" rel="noopener noreferrer" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary">{ __('Test Key File', 'asneris-seo-toolkit') }</a>
						</p>
					) : null }
					{ notice ? <p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-text-info">{ notice }</p> : null }
					{ form.indexnow_enabled ? (
						<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mt-8 is-success">
							<p className="ASNERISSEO-react-note-box-title is-success ASNERISSEO-react-mb-6">{ __('IndexNow is Active', 'asneris-seo-toolkit') }</p>
							<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-text-info">
								{ __('If this is your first activation, go to Settings > Permalinks and click Save Changes once to refresh rewrite rules for key routing.', 'asneris-seo-toolkit') }
							</p>
						</div>
					) : null }
				</>
			) }
			saveButtonLabel={ __('Save IndexNow Settings', 'asneris-seo-toolkit') }
		/>
	);
};

export default IndexNowPanel;
