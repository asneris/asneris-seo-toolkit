import { __ } from '@wordpress/i18n';
import RestSettingsPanel from '../common/RestSettingsPanel';
import InlineHelpDetails from '../common/InlineHelpDetails';

const SchemaPanel = ({ restUrl, restNonce, onStatus, businessTypeGroups = [] }) => (
	<RestSettingsPanel
		restUrl={ restUrl }
		restNonce={ restNonce }
		onStatus={ onStatus }
		title={ __('Schema', 'asneris-seo-toolkit') }
		description={ __('Edit local business and schema defaults using existing backend validation.', 'asneris-seo-toolkit') }
		loadMessage={ __('Loading schema settings...', 'asneris-seo-toolkit') }
		saveMessage={ __('Schema settings saved successfully.', 'asneris-seo-toolkit') }
		loadErrorMessage={ __('Unable to load schema settings.', 'asneris-seo-toolkit') }
		saveErrorMessage={ __('Failed to save schema settings.', 'asneris-seo-toolkit') }
		initialForm={ {
			enable_breadcrumbs: false,
			enable_local_business: false,
			business_type: 'LocalBusiness',
			business_phone: '',
			business_address: '',
			business_hours: '',
			service_area: '',
			price_range: '',
			payment_methods: '',
			languages_spoken: '',
		} }
		mapLoadToForm={ (payload) => ({
			enable_breadcrumbs: !!payload.enable_breadcrumbs,
			enable_local_business: !!payload.enable_local_business,
			business_type: payload.business_type || 'LocalBusiness',
			business_phone: payload.business_phone || '',
			business_address: payload.business_address || '',
			business_hours: payload.business_hours || '',
			service_area: payload.service_area || '',
			price_range: payload.price_range || '',
			payment_methods: payload.payment_methods || '',
			languages_spoken: payload.languages_spoken || '',
		}) }
		mapSaveToForm={ (saved) => ({
			enable_breadcrumbs: !!saved.enable_breadcrumbs,
			enable_local_business: !!saved.enable_local_business,
			business_type: saved.business_type || 'LocalBusiness',
			business_phone: saved.business_phone || '',
			business_address: saved.business_address || '',
			business_hours: saved.business_hours || '',
			service_area: saved.service_area || '',
			price_range: saved.price_range || '',
			payment_methods: saved.payment_methods || '',
			languages_spoken: saved.languages_spoken || '',
		}) }
		renderFields={ (form, updateField) => (
			<>
				<InlineHelpDetails
					title={ __('Help: Schema Basics', 'asneris-seo-toolkit') }
					items={ [
						__('Schema describes entities and content context to search engines.', 'asneris-seo-toolkit'),
						__('Breadcrumb schema helps communicate page hierarchy.', 'asneris-seo-toolkit'),
						__('Use Local Business schema only for physical/service-area businesses.', 'asneris-seo-toolkit'),
					] }
					note={ __('Schema improves clarity but does not guarantee rich results.', 'asneris-seo-toolkit') }
				/>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Enable Breadcrumb Structured Data', 'asneris-seo-toolkit') }</div>
					<input type="checkbox" checked={ !!form.enable_breadcrumbs } onChange={ (e) => updateField('enable_breadcrumbs', e.target.checked) } />
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Enable Local Business Schema', 'asneris-seo-toolkit') }</div>
					<input type="checkbox" checked={ !!form.enable_local_business } onChange={ (e) => updateField('enable_local_business', e.target.checked) } />
				</label>
				{ form.enable_local_business ? (
					<div className="ASNERISSEO-react-note-box is-warning">
						<strong>{ __('Important:', 'asneris-seo-toolkit') }</strong>{ ' ' }
						{ __('Business details should match your Google Business Profile.', 'asneris-seo-toolkit') }
					</div>
				) : null }
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Business Type', 'asneris-seo-toolkit') }</div>
					<select className="regular-text ASNERISSEO-react-select" value={ form.business_type } onChange={ (e) => updateField('business_type', e.target.value) }>
						<option value="LocalBusiness">{ __('Local Business (General)', 'asneris-seo-toolkit') }</option>
						{ businessTypeGroups.map((group) => (
							<optgroup key={ group.label } label={ group.label }>
								{ group.options.map((option) => (
									<option key={ option.value } value={ option.value }>{ option.label }</option>
								)) }
							</optgroup>
						)) }
					</select>
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Phone', 'asneris-seo-toolkit') }</div>
					<input className="regular-text ASNERISSEO-react-input" value={ form.business_phone } onChange={ (e) => updateField('business_phone', e.target.value) } />
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Address', 'asneris-seo-toolkit') }</div>
					<textarea className="large-text ASNERISSEO-react-input" rows="2" value={ form.business_address } onChange={ (e) => updateField('business_address', e.target.value) } />
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Business Hours', 'asneris-seo-toolkit') }</div>
					<textarea className="large-text ASNERISSEO-react-input" rows="2" value={ form.business_hours } onChange={ (e) => updateField('business_hours', e.target.value) } />
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Service Area', 'asneris-seo-toolkit') }</div>
					<textarea className="large-text ASNERISSEO-react-input" rows="2" value={ form.service_area } onChange={ (e) => updateField('service_area', e.target.value) } />
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Price Range', 'asneris-seo-toolkit') }</div>
					<select className="regular-text ASNERISSEO-react-select" value={ form.price_range } onChange={ (e) => updateField('price_range', e.target.value) }>
						<option value="">{ __('Not specified', 'asneris-seo-toolkit') }</option>
						<option value="$">$</option>
						<option value="$$">$$</option>
						<option value="$$$">$$$</option>
						<option value="$$$$">$$$$</option>
					</select>
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Payment Methods', 'asneris-seo-toolkit') }</div>
					<input className="large-text ASNERISSEO-react-input" value={ form.payment_methods } onChange={ (e) => updateField('payment_methods', e.target.value) } placeholder={ __('Cash, Credit Card, PayPal', 'asneris-seo-toolkit') } />
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Languages Spoken', 'asneris-seo-toolkit') }</div>
					<input className="large-text ASNERISSEO-react-input" value={ form.languages_spoken } onChange={ (e) => updateField('languages_spoken', e.target.value) } placeholder={ __('English, Spanish', 'asneris-seo-toolkit') } />
				</label>
			</>
		) }
		saveButtonLabel={ __('Save Schema Settings', 'asneris-seo-toolkit') }
	/>
);

export default SchemaPanel;
