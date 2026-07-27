import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PanelScaffold from '../common/PanelScaffold';
import InlineHelpDetails from '../common/InlineHelpDetails';
import fetchJson from '../common/fetchJson';

const MaintenanceSettingsPanel = ({ restUrl, restNonce, onStatus, diagnosticsSummary }) => {
	const [isBusy, setIsBusy] = useState(false);
	const [importText, setImportText] = useState('');
	const [errorMessage, setErrorMessage] = useState('');
	const [pluginVersion, setPluginVersion] = useState('');
	const conflictCount = Number(diagnosticsSummary?.seo_plugin_conflicts || 0);
	const conflictNames = Array.isArray(diagnosticsSummary?.seo_plugin_conflict_names)
		? diagnosticsSummary.seo_plugin_conflict_names
		: [];

	useEffect(() => {
		if (!restUrl) {
			return;
		}

		fetchJson(restUrl, {
			method: 'GET',
			headers: {
				'X-WP-Nonce': restNonce || '',
			},
		})
			.then((payload) => {
				setPluginVersion(payload?.version || '');
			})
			.catch(() => {
				// Keep panel functional even if metadata fetch fails.
			});
	}, [restNonce, restUrl]);

	const runAction = (action, payload = {}) => {
		if (!restUrl || isBusy) {
			return;
		}

		setIsBusy(true);
		setErrorMessage('');

		fetchJson(restUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': restNonce || '',
			},
			body: JSON.stringify({ action, ...payload }),
		})
			.then((response) => {
				if (action === 'export') {
					setImportText(JSON.stringify(response?.settings || {}, null, 2));
				}
				onStatus?.({ tone: 'success', text: response?.message || __('Maintenance action completed.', 'asneris-seo-toolkit') });
			})
			.catch((error) => {
				const message = error.message || __('Maintenance action failed.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setIsBusy(false));
	};

	const importSettings = () => {
		let parsed = null;
		try {
			parsed = JSON.parse(importText || '{}');
		} catch (error) {
			setErrorMessage(__('Import JSON is invalid.', 'asneris-seo-toolkit'));
			return;
		}

		runAction('import', { settings: parsed });
	};

	return (
		<PanelScaffold
			title={ __('Maintenance & Safety', 'asneris-seo-toolkit') }
			panelClass="ASNERISSEO-react-form-panel"
		>
			<InlineHelpDetails
				title={ __('Help: Safety & Maintenance', 'asneris-seo-toolkit') }
				tone="warning"
				items={ [
					__('Export before major updates or experimental changes.', 'asneris-seo-toolkit'),
					__('Import only files previously exported by this plugin.', 'asneris-seo-toolkit'),
					__('Use reset only for troubleshooting or clean restarts.', 'asneris-seo-toolkit'),
				] }
				note={ __('Maintenance tools are operational safeguards and do not directly affect rankings.', 'asneris-seo-toolkit') }
			/>
			{ errorMessage ? <p className="ASNERISSEO-react-text-danger">{ errorMessage }</p> : null }
			<div className="ASNERISSEO-react-note-box">
				<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-text-info">
					<strong>{ __('Plugin Version:', 'asneris-seo-toolkit') }</strong>{ ' ' }
					{ pluginVersion || __('Unavailable', 'asneris-seo-toolkit') }
				</p>
				<p className={ conflictCount > 0 ? 'ASNERISSEO-react-text-danger' : 'ASNERISSEO-react-text-success' }>
					<strong>{ __('SEO Conflict Status:', 'asneris-seo-toolkit') }</strong>{ ' ' }
					{ conflictCount > 0
						? `${ conflictCount } ${ __('potential conflict(s) detected', 'asneris-seo-toolkit') }`
						: __('No conflict signals detected from dashboard summary', 'asneris-seo-toolkit') }
				</p>
				{ conflictCount > 0 && conflictNames.length > 0 ? (
					<p className="ASNERISSEO-react-text-danger">
						<strong>{ __('Detected Plugins:', 'asneris-seo-toolkit') }</strong>{ ' ' }
						{ conflictNames.join(', ') }
					</p>
				) : null }
			</div>

			<div className="ASNERISSEO-react-actions-wrap">
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" onClick={ () => runAction('export') } disabled={ isBusy }>{ __('Export Configuration', 'asneris-seo-toolkit') }</button>
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" onClick={ importSettings } disabled={ isBusy }>{ __('Import Configuration', 'asneris-seo-toolkit') }</button>
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" onClick={ () => {
					if (window.confirm(__('Reset all settings?', 'asneris-seo-toolkit'))) {
						runAction('reset');
					}
				} } disabled={ isBusy }>{ __('Reset All Settings', 'asneris-seo-toolkit') }</button>
			</div>

			<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mb-8">
				<p className="ASNERISSEO-react-note-box-title ASNERISSEO-react-text-info ASNERISSEO-react-mb-6">{ __('Import/Export Safety', 'asneris-seo-toolkit') }</p>
				<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-muted">{ __('Import only files previously exported from this plugin to prevent malformed settings payloads.', 'asneris-seo-toolkit') }</p>
			</div>

			<div className="ASNERISSEO-react-kv-box">
				<div className="ASNERISSEO-react-kv-fields">
					<label className="ASNERISSEO-react-field-label">
						<div className="ASNERISSEO-react-field-label">{ __('Import/Export JSON', 'asneris-seo-toolkit') }</div>
						<textarea className="large-text code ASNERISSEO-react-input ASNERISSEO-react-input-wide" rows="12" value={ importText } onChange={ (e) => setImportText(e.target.value) } />
					</label>
				</div>
			</div>
			<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-muted">
				{ __('Tip: export your configuration before running reset. Reset cannot be undone.', 'asneris-seo-toolkit') }
			</p>
		</PanelScaffold>
	);
};

export default MaintenanceSettingsPanel;
