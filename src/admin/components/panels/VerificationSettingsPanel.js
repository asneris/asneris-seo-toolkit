import { __ } from '@wordpress/i18n';
import RestSettingsPanel from '../common/RestSettingsPanel';
import InlineHelpDetails from '../common/InlineHelpDetails';

const VerificationSettingsPanel = ({ restUrl, restNonce, onStatus }) => (
	<RestSettingsPanel
		restUrl={ restUrl }
		restNonce={ restNonce }
		onStatus={ onStatus }
		title={ __('Verification Settings', 'asneris-seo-toolkit') }
		description={ __('Manage search engine verification codes.', 'asneris-seo-toolkit') }
		loadMessage={ __('Loading verification settings...', 'asneris-seo-toolkit') }
		saveMessage={ __('Verification settings saved successfully.', 'asneris-seo-toolkit') }
		loadErrorMessage={ __('Unable to load verification settings.', 'asneris-seo-toolkit') }
		saveErrorMessage={ __('Failed to save verification settings.', 'asneris-seo-toolkit') }
		initialForm={ {
			google_verification: '',
			bing_verification: '',
			yandex_verification: '',
		} }
		mapLoadToForm={ (payload) => ({
			google_verification: payload.google_verification || '',
			bing_verification: payload.bing_verification || '',
			yandex_verification: payload.yandex_verification || '',
		}) }
		mapSaveToForm={ (saved) => ({
			google_verification: saved.google_verification || '',
			bing_verification: saved.bing_verification || '',
			yandex_verification: saved.yandex_verification || '',
		}) }
		renderFields={ (form, updateField) => (
			<>
				{ /* Sample records shown next to each tool field for quick copy guidance. */ }
				<InlineHelpDetails
					title={ __('Help: Search Engine Verification', 'asneris-seo-toolkit') }
					items={ [
						__('Verification confirms site ownership and unlocks webmaster reports.', 'asneris-seo-toolkit'),
						__('Paste only the content value, not the full meta tag HTML.', 'asneris-seo-toolkit'),
						__('After saving, complete verification inside each webmaster tool.', 'asneris-seo-toolkit'),
					] }
					note={ __('Verification itself does not improve rankings.', 'asneris-seo-toolkit') }
				/>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Google Verification Code', 'asneris-seo-toolkit') }</div>
					<input className="regular-text ASNERISSEO-react-input" value={ form.google_verification } onChange={ (e) => updateField('google_verification', e.target.value) } />
					<p className="ASNERISSEO-react-helper-text">
						<strong>{ __('Enter ONLY the code value.', 'asneris-seo-toolkit') }</strong>{ ' ' }
						{ __('If given a full meta tag, copy only the content value.', 'asneris-seo-toolkit') }
					</p>
					<p className="ASNERISSEO-react-helper-text ASNERISSEO-react-mb-0">
						<strong>{ __('Sample:', 'asneris-seo-toolkit') }</strong>{ ' ' }
						<code>google12ab34cd56ef78gh90ij</code>
					</p>
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Bing Verification Code', 'asneris-seo-toolkit') }</div>
					<input className="regular-text ASNERISSEO-react-input" value={ form.bing_verification } onChange={ (e) => updateField('bing_verification', e.target.value) } />
					<p className="ASNERISSEO-react-helper-text">
						<strong>{ __('Enter ONLY the code value.', 'asneris-seo-toolkit') }</strong>{ ' ' }
						{ __('If given a full meta tag, copy only the content value.', 'asneris-seo-toolkit') }
					</p>
					<p className="ASNERISSEO-react-helper-text ASNERISSEO-react-mb-0">
						<strong>{ __('Sample:', 'asneris-seo-toolkit') }</strong>{ ' ' }
						<code>1234567890ABCDEF1234567890ABCDEF</code>
					</p>
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Yandex Verification Code', 'asneris-seo-toolkit') }</div>
					<input className="regular-text ASNERISSEO-react-input" value={ form.yandex_verification } onChange={ (e) => updateField('yandex_verification', e.target.value) } />
					<p className="ASNERISSEO-react-helper-text">
						<strong>{ __('Enter ONLY the code value.', 'asneris-seo-toolkit') }</strong>{ ' ' }
						{ __('If given a full meta tag, copy only the content value.', 'asneris-seo-toolkit') }
					</p>
					<p className="ASNERISSEO-react-helper-text ASNERISSEO-react-mb-0">
						<strong>{ __('Sample:', 'asneris-seo-toolkit') }</strong>{ ' ' }
						<code>1a2b3c4d5e6f7g8h9i0j</code>
					</p>
				</label>
				<div className="ASNERISSEO-react-note-box">
					<p className="ASNERISSEO-react-note-box-title ASNERISSEO-react-mb-8">{ __('What are Webmaster Tools?', 'asneris-seo-toolkit') }</p>
					<ul className="ASNERISSEO-react-list ASNERISSEO-react-mb-10 is-muted">
						<li>{ __('Monitor search performance and indexing health.', 'asneris-seo-toolkit') }</li>
						<li>{ __('Submit sitemaps for better content discovery.', 'asneris-seo-toolkit') }</li>
						<li>{ __('Identify crawl and indexing issues early.', 'asneris-seo-toolkit') }</li>
					</ul>
					<p className="ASNERISSEO-react-mb-6">{ __('Open webmaster tools after saving:', 'asneris-seo-toolkit') }</p>
					<p className="ASNERISSEO-react-mb-0">
						<a href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer">{ __('Google Search Console', 'asneris-seo-toolkit') }</a>{ ' | ' }
						<a href="https://www.bing.com/webmasters" target="_blank" rel="noopener noreferrer">{ __('Bing Webmaster Tools', 'asneris-seo-toolkit') }</a>{ ' | ' }
						<a href="https://webmaster.yandex.com" target="_blank" rel="noopener noreferrer">{ __('Yandex Webmaster', 'asneris-seo-toolkit') }</a>
					</p>
				</div>
			</>
		) }
		saveButtonLabel={ __('Save Verification Settings', 'asneris-seo-toolkit') }
	/>
);

export default VerificationSettingsPanel;
