import { __ } from '@wordpress/i18n';
import RestSettingsPanel from '../common/RestSettingsPanel';
import InlineHelpDetails from '../common/InlineHelpDetails';
import MediaUrlField from '../common/MediaUrlField';
const GeneralSettingsPanel = ({ restUrl, restNonce, onStatus }) => (
	<RestSettingsPanel
			restUrl={ restUrl }
			restNonce={ restNonce }
			onStatus={ onStatus }
			title={ __('General Settings', 'asneris-seo-toolkit') }
			description={ __('Update organization and default robots settings.', 'asneris-seo-toolkit') }
			loadMessage={ __('Loading general settings...', 'asneris-seo-toolkit') }
			saveMessage={ __('General settings saved successfully.', 'asneris-seo-toolkit') }
			loadErrorMessage={ __('Unable to load general settings.', 'asneris-seo-toolkit') }
			saveErrorMessage={ __('Failed to save general settings.', 'asneris-seo-toolkit') }
			initialForm={ {
				org_name: '',
				org_logo: '',
				default_robots_index: 'index',
				default_robots_follow: 'follow',
			} }
			mapLoadToForm={ (payload) => ({
				org_name: payload.org_name || '',
				org_logo: payload.org_logo || '',
				default_robots_index: payload.default_robots_index || 'index',
				default_robots_follow: payload.default_robots_follow || 'follow',
			}) }
			mapSaveToForm={ (saved) => ({
				org_name: saved.org_name || '',
				org_logo: saved.org_logo || '',
				default_robots_index: saved.default_robots_index || 'index',
				default_robots_follow: saved.default_robots_follow || 'follow',
			}) }
			renderFields={ (form, updateField) => (
				<>
					<InlineHelpDetails
						title={ __('Help: General Settings Overview', 'asneris-seo-toolkit') }
						items={ [
							__('Set a stable site/organization identity used in schema and social metadata.', 'asneris-seo-toolkit'),
							__('Use your official brand name and keep it consistent everywhere.', 'asneris-seo-toolkit'),
							__('Logo should be publicly reachable and readable at small sizes.', 'asneris-seo-toolkit'),
						] }
						note={ __('These settings improve clarity and consistency, not rankings by themselves.', 'asneris-seo-toolkit') }
					/>
					<label className="ASNERISSEO-react-field-label">
						<div className="ASNERISSEO-react-field-label">{ __('Organization/Site Name', 'asneris-seo-toolkit') }</div>
						<input className="regular-text ASNERISSEO-react-input" value={ form.org_name } onChange={ (e) => updateField('org_name', e.target.value) } />
						<p className="ASNERISSEO-react-helper-text">{ __('Used for schema markup and social meta tags.', 'asneris-seo-toolkit') }</p>
					</label>
					<MediaUrlField
						label={ __('Logo URL', 'asneris-seo-toolkit') }
						value={ form.org_logo }
						onChange={ (nextValue) => updateField('org_logo', nextValue) }
						uploadTitle={ __('Select or Upload Site Logo', 'asneris-seo-toolkit') }
						uploadButtonLabel={ __('Upload Logo', 'asneris-seo-toolkit') }
						description={ __('Recommended: 600x60 for best display across platforms.', 'asneris-seo-toolkit') }
						previewMaxWidth="200px"
					/>
					<div className="ASNERISSEO-react-note-box">
						<strong>{ __('Sitemap Information', 'asneris-seo-toolkit') }</strong>
						<p>
							{ __('Your WordPress sitemap is usually available at:', 'asneris-seo-toolkit') }{' '}
							<a href="/wp-sitemap.xml" target="_blank" rel="noopener noreferrer">/wp-sitemap.xml</a>
						</p>
					</div>
					<label className="ASNERISSEO-react-field-label">
						<div className="ASNERISSEO-react-field-label">{ __('Allow Search Engines to Index', 'asneris-seo-toolkit') }</div>
						<select className="regular-text ASNERISSEO-react-select" value={ form.default_robots_index } onChange={ (e) => updateField('default_robots_index', e.target.value) }>
							<option value="index">index</option>
							<option value="noindex">noindex</option>
						</select>
					</label>
					<label className="ASNERISSEO-react-field-label">
						<div className="ASNERISSEO-react-field-label">{ __('Allow Search Engines to Follow Page Links', 'asneris-seo-toolkit') }</div>
						<select className="regular-text ASNERISSEO-react-select" value={ form.default_robots_follow } onChange={ (e) => updateField('default_robots_follow', e.target.value) }>
							<option value="follow">follow</option>
							<option value="nofollow">nofollow</option>
						</select>
					</label>
					<InlineHelpDetails
						title={ __('Help: Default Robots', 'asneris-seo-toolkit') }
						tone="warning"
						items={ [
							__('Index + Follow is the safe default for most public websites.', 'asneris-seo-toolkit'),
							__('NoIndex prevents pages from appearing in search results.', 'asneris-seo-toolkit'),
							__('NoFollow prevents search engines from following internal links on the page.', 'asneris-seo-toolkit'),
							__('Use page-level overrides for exceptions like thank-you pages.', 'asneris-seo-toolkit'),
						] }
						note={ __('Setting global NoIndex usually hides the site from search engines.', 'asneris-seo-toolkit') }
					/>
				</>
			) }
			saveButtonLabel={ __('Save General Settings', 'asneris-seo-toolkit') }
	/>
);

export default GeneralSettingsPanel;
