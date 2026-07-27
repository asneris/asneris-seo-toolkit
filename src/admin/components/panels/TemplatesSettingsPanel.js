import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PanelScaffold from '../common/PanelScaffold';
import InlineHelpDetails from '../common/InlineHelpDetails';
import fetchJson from '../common/fetchJson';

const TemplatesSettingsPanel = ({ restUrl, restNonce, onStatus, templateVariables = [] }) => {
	const [isLoading, setIsLoading] = useState(false);
	const [isSaving, setIsSaving] = useState(false);
	const [postTypes, setPostTypes] = useState([]);
	const [titleSeparator, setTitleSeparator] = useState('|');
	const [titleTemplates, setTitleTemplates] = useState({});
	const [descriptionTemplates, setDescriptionTemplates] = useState({});
	const [errorMessage, setErrorMessage] = useState('');

	useEffect(() => {
		if (!restUrl) {
			return;
		}

		setIsLoading(true);
		setErrorMessage('');

		fetchJson(restUrl, {
			method: 'GET',
			headers: { 'X-WP-Nonce': restNonce || '' },
		})
			.then((payload) => {
				setPostTypes(Array.isArray(payload?.post_types) ? payload.post_types : []);
				setTitleSeparator(payload?.title_separator || '|');
				setTitleTemplates(payload?.title_templates || {});
				setDescriptionTemplates(payload?.description_templates || {});
			})
			.catch((error) => {
				const message = error.message || __('Unable to load template settings.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setIsLoading(false));
	}, [restNonce, restUrl]);

	const saveTemplates = () => {
		if (!restUrl || isSaving) {
			return;
		}

		setIsSaving(true);
		setErrorMessage('');

		fetchJson(restUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': restNonce || '',
			},
			body: JSON.stringify({
				title_separator: titleSeparator,
				title_templates: titleTemplates,
				description_templates: descriptionTemplates,
			}),
		})
			.then((payload) => {
				const saved = payload?.settings || {};
				setTitleSeparator(saved?.title_separator || '|');
				setTitleTemplates(saved?.title_templates || {});
				setDescriptionTemplates(saved?.description_templates || {});
				onStatus?.({ tone: 'success', text: __('Template settings saved successfully.', 'asneris-seo-toolkit') });
			})
			.catch((error) => {
				const message = error.message || __('Failed to save template settings.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setIsSaving(false));
	};

	return (
		<PanelScaffold
			title={ __('Templates', 'asneris-seo-toolkit') }
			panelClass="ASNERISSEO-react-form-panel"
		>
			<p className="ASNERISSEO-react-heading-reset ASNERISSEO-react-muted">
				{ __('Title and description templates provide automated fallbacks when per-page values are not set.', 'asneris-seo-toolkit') }
			</p>
			<InlineHelpDetails
				title={ __('Help: SEO Templates', 'asneris-seo-toolkit') }
				items={ [
					__('Templates apply only when page-level custom SEO fields are empty.', 'asneris-seo-toolkit'),
					__('Simple templates are easier to maintain and usually perform better.', 'asneris-seo-toolkit'),
					__('Descriptions can be left empty to auto-generate from content.', 'asneris-seo-toolkit'),
				] }
				note={ __('Templates improve consistency, not rankings.', 'asneris-seo-toolkit') }
			/>
			<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mb-8">
				<strong>{ __('Available Variables:', 'asneris-seo-toolkit') }</strong>{ ' ' }
				{ templateVariables.join(', ') }
			</div>
			{ isLoading ? <p>{ __('Loading template settings...', 'asneris-seo-toolkit') }</p> : null }
			{ errorMessage ? <p className="ASNERISSEO-react-text-danger">{ errorMessage }</p> : null }

			<div className="ASNERISSEO-react-kv-box ASNERISSEO-react-mb-8">
				<label className="ASNERISSEO-react-label-block ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Title Separator', 'asneris-seo-toolkit') }</div>
					<input className="regular-text ASNERISSEO-react-input" value={ titleSeparator } onChange={ (e) => setTitleSeparator(e.target.value) } />
				</label>
			</div>

			{ postTypes.map((postType) => (
				<div key={ postType.value } className="ASNERISSEO-react-subsection ASNERISSEO-react-kv-box">
					<h3 className="ASNERISSEO-heading-h3 ASNERISSEO-react-heading-reset">{ postType.label }</h3>
					<p className="ASNERISSEO-react-heading-reset ASNERISSEO-react-muted">{ __('Leave empty to use defaults from content/title.', 'asneris-seo-toolkit') }</p>
					<label className="ASNERISSEO-react-label-block ASNERISSEO-react-mb-8 ASNERISSEO-react-field-label">
						<div className="ASNERISSEO-react-field-label">{ __('Title Template', 'asneris-seo-toolkit') }</div>
						<input
							className="large-text ASNERISSEO-react-input"
							value={ titleTemplates?.[postType.value] || '' }
							onChange={ (e) => setTitleTemplates((prev) => ({ ...prev, [postType.value]: e.target.value })) }
						/>
					</label>
					<label className="ASNERISSEO-react-label-block ASNERISSEO-react-field-label">
						<div className="ASNERISSEO-react-field-label">{ __('Description Template', 'asneris-seo-toolkit') }</div>
						<textarea
							className="large-text ASNERISSEO-react-input"
							rows="3"
							value={ descriptionTemplates?.[postType.value] || '' }
							onChange={ (e) => setDescriptionTemplates((prev) => ({ ...prev, [postType.value]: e.target.value })) }
						/>
					</label>
				</div>
			)) }

			<div className="ASNERISSEO-react-mt-12">
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary" onClick={ saveTemplates } disabled={ isSaving || isLoading }>
					{ isSaving ? __('Saving...', 'asneris-seo-toolkit') : __('Save Template Settings', 'asneris-seo-toolkit') }
				</button>
			</div>
		</PanelScaffold>
	);
};

export default TemplatesSettingsPanel;
