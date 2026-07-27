import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PanelScaffold from './PanelScaffold';
import fetchJson from './fetchJson';

const RestSettingsPanel = ({
	restUrl,
	restNonce,
	title,
	description,
	loadMessage,
	saveMessage,
	loadErrorMessage,
	saveErrorMessage,
	initialForm,
	mapLoadToForm,
	mapSaveToForm,
	renderFields,
	saveButtonLabel,
	showSaveButton = true,
	onStatus,
	mapFormToSave,
	onAfterSave,
}) => {
	const [form, setForm] = useState({ ...initialForm });
	const [initialSnapshot, setInitialSnapshot] = useState({ ...initialForm });
	const [isLoading, setIsLoading] = useState(false);
	const [isSaving, setIsSaving] = useState(false);
	const [message, setMessage] = useState('');
	const [errorMessage, setErrorMessage] = useState('');
	const [errorDetails, setErrorDetails] = useState([]);
	const hasUnsavedChanges = JSON.stringify(form) !== JSON.stringify(initialSnapshot);

	useEffect(() => {
		if (!restUrl) {
			return;
		}

		setIsLoading(true);
		setErrorMessage('');
		setErrorDetails([]);

		fetchJson(restUrl, {
			method: 'GET',
			headers: {
				'X-WP-Nonce': restNonce || '',
			},
		})
			.then((payload) => {
				const nextForm = mapLoadToForm(payload || {});
				setForm(nextForm);
				setInitialSnapshot(nextForm);
			})
			.catch(() => {
				setErrorMessage(loadErrorMessage);
				onStatus?.({ tone: 'error', text: loadErrorMessage });
			})
			.finally(() => {
				setIsLoading(false);
			});
	}, [restNonce, restUrl]);

	const updateField = (key, value) => {
		setForm((prev) => ({ ...prev, [key]: value }));
	};

	const saveForm = () => {
		if (!restUrl) {
			return;
		}

		setIsSaving(true);
		setMessage('');
		setErrorMessage('');
		setErrorDetails([]);

		fetchJson(restUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': restNonce || '',
			},
			body: JSON.stringify(typeof mapFormToSave === 'function' ? mapFormToSave(form) : form),
		})
			.then((payload) => {
				const saved = payload?.settings || {};
				const nextForm = mapSaveToForm(saved);
				setForm(nextForm);
				setInitialSnapshot(nextForm);
				setMessage(saveMessage);
				onAfterSave?.(payload, nextForm);
				onStatus?.({ tone: 'success', text: saveMessage });
			})
			.catch((error) => {
				const details = Array.isArray(error?.data?.errors) ? error.data.errors : [];
				if (details.length > 0) {
					setErrorDetails(details);
				}
				setErrorMessage(error.message || saveErrorMessage);
				onStatus?.({ tone: 'error', text: error.message || saveErrorMessage });
			})
			.finally(() => {
				setIsSaving(false);
			});
	};

	return (
		<PanelScaffold
			title={ title }
			description={ description }
			panelClass="ASNERISSEO-react-form-panel"
		>
			{ isLoading ? (
				<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mb-8">
					<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-muted">{ loadMessage }</p>
				</div>
			) : null }
			{ errorMessage ? (
				<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mb-8 is-warning">
					<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-text-danger">{ errorMessage }</p>
				</div>
			) : null }
			{ errorDetails.length > 0 ? (
				<ul className="ASNERISSEO-react-list ASNERISSEO-react-mb-8 ASNERISSEO-react-text-danger">
					{ errorDetails.map((detail, index) => (
						<li key={ `${ index }-${ detail }` }>{ detail }</li>
					)) }
				</ul>
			) : null }
			{ message ? (
				<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mb-8 is-success">
					<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-text-success">{ message }</p>
				</div>
			) : null }

			<div className="ASNERISSEO-react-kv-box">
				<div className="ASNERISSEO-react-kv-fields">
					{ renderFields(form, updateField, {
						saveForm,
						isSaving,
						isLoading,
						hasUnsavedChanges,
					}) }
				</div>
				{ hasUnsavedChanges ? (
					<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-text-warning">
						{ __('You have unsaved changes in this panel.', 'asneris-seo-toolkit') }
					</p>
				) : null }
				{ showSaveButton ? (
					<div className="ASNERISSEO-react-actions-wrap">
						<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary" onClick={ saveForm } disabled={ isSaving || isLoading }>
							{ isSaving ? __('Saving...', 'asneris-seo-toolkit') : saveButtonLabel }
						</button>
					</div>
				) : null }
			</div>
		</PanelScaffold>
	);
};

export default RestSettingsPanel;
