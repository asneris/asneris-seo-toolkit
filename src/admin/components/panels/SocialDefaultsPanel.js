import { __ } from '@wordpress/i18n';
import RestSettingsPanel from '../common/RestSettingsPanel';
import InlineHelpDetails from '../common/InlineHelpDetails';
import MediaUrlField from '../common/MediaUrlField';

const SocialDefaultsPanel = ({ restUrl, restNonce, onStatus }) => (
	<RestSettingsPanel
		restUrl={ restUrl }
		restNonce={ restNonce }
		onStatus={ onStatus }
		title={ __('Social Defaults', 'asneris-seo-toolkit') }
		description={ __('This panel saves social settings through secure REST endpoints.', 'asneris-seo-toolkit') }
		loadMessage={ __('Loading social settings...', 'asneris-seo-toolkit') }
		saveMessage={ __('Social defaults saved successfully.', 'asneris-seo-toolkit') }
		loadErrorMessage={ __('Unable to load social settings.', 'asneris-seo-toolkit') }
		saveErrorMessage={ __('Failed to save social settings.', 'asneris-seo-toolkit') }
		initialForm={ {
			default_og_image: '',
			twitter_username: '',
			facebook_app_id: '',
			theme_color: '',
		} }
		mapLoadToForm={ (payload) => ({
			default_og_image: payload.default_og_image || '',
			twitter_username: payload.twitter_username || '',
			facebook_app_id: payload.facebook_app_id || '',
			theme_color: payload.theme_color || '',
		}) }
		mapSaveToForm={ (saved) => ({
			default_og_image: saved.default_og_image || '',
			twitter_username: saved.twitter_username || '',
			facebook_app_id: saved.facebook_app_id || '',
			theme_color: saved.theme_color || '',
		}) }
		renderFields={ (form, updateField) => (
			<>
				<InlineHelpDetails
					title={ __('Help: Social Sharing', 'asneris-seo-toolkit') }
					items={ [
						__('Social tags control previews across social apps and messengers.', 'asneris-seo-toolkit'),
						__('A default image is used when no featured image exists.', 'asneris-seo-toolkit'),
						__('Twitter/X and Facebook can require additional platform-specific fields.', 'asneris-seo-toolkit'),
					] }
					note={ __('Social metadata affects previews, not Google rankings.', 'asneris-seo-toolkit') }
				/>
				<MediaUrlField
					label={ __('Default OG Image URL', 'asneris-seo-toolkit') }
					value={ form.default_og_image }
					onChange={ (nextValue) => updateField('default_og_image', nextValue) }
					uploadTitle={ __('Select or Upload Default OG Image', 'asneris-seo-toolkit') }
					uploadButtonLabel={ __('Upload Image', 'asneris-seo-toolkit') }
					description={ __('Recommended: 1200x630. Used when a post does not have a featured image.', 'asneris-seo-toolkit') }
					previewMaxWidth="260px"
				/>
				<div className="ASNERISSEO-react-note-box">
					<p className="ASNERISSEO-react-note-box-title ASNERISSEO-react-mb-6">{ __('Supported Social Platforms', 'asneris-seo-toolkit') }</p>
					<p className="ASNERISSEO-react-mb-0">
						{ __('Facebook, LinkedIn, Pinterest, WhatsApp, Discord, Telegram, Slack, and Twitter/X.', 'asneris-seo-toolkit') }
					</p>
					<p className="ASNERISSEO-react-mt-8 ASNERISSEO-react-text-warning">
						<strong>{ __('Note:', 'asneris-seo-toolkit') }</strong>{ ' ' }
						{ __('Most platforms use Open Graph tags automatically. Twitter and Facebook are the main platform-specific settings below.', 'asneris-seo-toolkit') }
					</p>
				</div>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Twitter Username', 'asneris-seo-toolkit') }</div>
					<input className="regular-text ASNERISSEO-react-input" value={ form.twitter_username } onChange={ (e) => updateField('twitter_username', e.target.value) } />
					<p className="ASNERISSEO-react-helper-text">{ __('Enter your username without @.', 'asneris-seo-toolkit') }</p>
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Facebook App ID', 'asneris-seo-toolkit') }</div>
					<input className="regular-text ASNERISSEO-react-input" value={ form.facebook_app_id } onChange={ (e) => updateField('facebook_app_id', e.target.value) } />
					<p className="ASNERISSEO-react-helper-text">{ __('Optional. Used for Facebook Insights and Open Graph validation.', 'asneris-seo-toolkit') }</p>
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Theme Color', 'asneris-seo-toolkit') }</div>
					<input className="regular-text ASNERISSEO-react-input" value={ form.theme_color } onChange={ (e) => updateField('theme_color', e.target.value) } />
					<p className="ASNERISSEO-react-helper-text">{ __('Hex color used by some embeds and mobile browser UI.', 'asneris-seo-toolkit') }</p>
				</label>
			</>
		) }
		saveButtonLabel={ __('Save Social Settings', 'asneris-seo-toolkit') }
	/>
);

export default SocialDefaultsPanel;
