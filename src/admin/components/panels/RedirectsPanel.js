import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import PanelScaffold from '../common/PanelScaffold';
import MetricCard from '../common/MetricCard';
import fetchJson from '../common/fetchJson';
import InlineHelpDetails from '../common/InlineHelpDetails';

const RedirectsPanel = ({ restUrl, restNonce, onStatus }) => {
	const [data, setData] = useState({ stats: {}, items: [] });
	const [form, setForm] = useState({ from: '', to: '', code: '301' });
	const [isLoading, setIsLoading] = useState(false);
	const [isActing, setIsActing] = useState(false);
	const [errorMessage, setErrorMessage] = useState('');
	const [actionConfirmDialog, setActionConfirmDialog] = useState({
		isOpen: false,
		title: '',
		message: '',
		onProceed: null,
	});
	const baseUrl = (restUrl || '').replace(/\/+$/, '');
	const modalLogoUrl = String(
		window.asnerisseoAdminDashboardData?.logoUrl || window.asnerisseoData?.logoUrl || ''
	).trim();

	const applyPayload = (payload) => {
		const nextData = payload?.data || payload || {};
		setData({
			stats: nextData?.stats || {},
			items: Array.isArray(nextData?.items) ? nextData.items : [],
		});
	};

	const loadRedirects = () => {
		if (!restUrl) {
			return;
		}

		setIsLoading(true);
		setErrorMessage('');

		fetchJson(restUrl, {
			method: 'GET',
			headers: { 'X-WP-Nonce': restNonce || '' },
		})
			.then((payload) => applyPayload(payload))
			.catch((error) => {
				const message = error.message || __('Unable to load redirects overview.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setIsLoading(false));
	};

	const sendAction = ({ url, method = 'POST', body, successMessage, confirmMessage, onSuccess }) => {
		if (!url || isActing) {
			return;
		}
		if (confirmMessage && !window.confirm(confirmMessage)) {
			return;
		}

		setIsActing(true);
		setErrorMessage('');

		fetchJson(url, {
			method,
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': restNonce || '',
			},
			body: body ? JSON.stringify(body) : undefined,
		})
			.then((payload) => {
				if (payload) {
					applyPayload(payload);
				}
				onSuccess?.(payload);
				onStatus?.({ tone: 'success', text: payload?.message || successMessage });
			})
			.catch((error) => {
				const message = error.message || __('Action failed for redirects.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setIsActing(false));
	};

	const openActionConfirmDialog = (title, message, onProceed) => {
		setActionConfirmDialog({
			isOpen: true,
			title: String(title || ''),
			message: String(message || ''),
			onProceed: typeof onProceed === 'function' ? onProceed : null,
		});
	};

	const closeActionConfirmDialog = () => {
		setActionConfirmDialog({
			isOpen: false,
			title: '',
			message: '',
			onProceed: null,
		});
	};

	const proceedWithActionConfirmDialog = () => {
		const proceedFn = actionConfirmDialog?.onProceed;
		closeActionConfirmDialog();
		if (typeof proceedFn === 'function') {
			proceedFn();
		}
	};

	const requestDeleteRedirect = (item) => {
		openActionConfirmDialog(
			__('Confirm Delete', 'asneris-seo-toolkit'),
			__('This will permanently delete this redirect. Continue?', 'asneris-seo-toolkit'),
			() => sendAction({
				url: `${ baseUrl }/${ item.index }`,
				method: 'DELETE',
				successMessage: __('Redirect deleted successfully.', 'asneris-seo-toolkit'),
			})
		);
	};

	const requestToggleRedirect = (item) => {
		if (!item) {
			return;
		}

		const isCurrentlyEnabled = Boolean(item.enabled);
		const actionText = isCurrentlyEnabled
			? __('disable', 'asneris-seo-toolkit')
			: __('enable', 'asneris-seo-toolkit');

		const successMessage = isCurrentlyEnabled
			? __('Redirect disabled successfully.', 'asneris-seo-toolkit')
			: __('Redirect enabled successfully.', 'asneris-seo-toolkit');

		openActionConfirmDialog(
			__('Confirm Action', 'asneris-seo-toolkit'),
			sprintf(
				__('This will %s this redirect. Continue?', 'asneris-seo-toolkit'),
				actionText
			),
			() => sendAction({
				url: `${ baseUrl }/${ item.index }/toggle`,
				method: 'POST',
				successMessage,
			})
		);
	};

	useEffect(() => {
		loadRedirects();
	}, [restUrl, restNonce]);

	const handleAddRedirect = (event) => {
		event.preventDefault();
		sendAction({
			url: baseUrl,
			method: 'POST',
			body: { from: form.from, to: form.to, code: Number(form.code || 301) },
			successMessage: __('Redirect added successfully.', 'asneris-seo-toolkit'),
			onSuccess: () => setForm({ from: '', to: '', code: '301' }),
		});
	};

	return (
		<>
		<PanelScaffold
			title={ __('Redirect Manager', 'asneris-seo-toolkit') }
			panelClass="ASNERISSEO-react-data-panel"
		>
			<InlineHelpDetails
				title={ __('Help: Redirect Manager', 'asneris-seo-toolkit') }
				items={ [
					__('Use 301 for permanent URL moves and 302/307 for temporary cases.', 'asneris-seo-toolkit'),
					__('Use paths (for example /old-page) instead of full domains in most cases.', 'asneris-seo-toolkit'),
					__('Avoid redirect chains and loops to keep crawling efficient.', 'asneris-seo-toolkit'),
				] }
				note={ __('Delete and toggle actions affect live redirect behavior immediately after save.', 'asneris-seo-toolkit') }
			/>
			<form className="ASNERISSEO-react-inline-form ASNERISSEO-react-redirect-form is-grid" onSubmit={ handleAddRedirect }>
				<label className="ASNERISSEO-react-field-label is-required">
					<div className="ASNERISSEO-react-field-label">{ __('From', 'asneris-seo-toolkit') }</div>
					<input type="text" className="regular-text ASNERISSEO-react-input" value={ form.from } onChange={ (e) => setForm((prev) => ({ ...prev, from: e.target.value })) } required />
				</label>
				<label className="ASNERISSEO-react-field-label is-required">
					<div className="ASNERISSEO-react-field-label">{ __('To', 'asneris-seo-toolkit') }</div>
					<input type="text" className="regular-text ASNERISSEO-react-input" value={ form.to } onChange={ (e) => setForm((prev) => ({ ...prev, to: e.target.value })) } required />
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Code', 'asneris-seo-toolkit') }</div>
					<select className="regular-text ASNERISSEO-react-select" value={ form.code } onChange={ (e) => setForm((prev) => ({ ...prev, code: e.target.value })) }>
						<option value="301">301 - Permanent Redirect</option>
                        <option value="302">302 - Temporary Redirect</option>
                        <option value="307">307 - Temporary Redirect (Preserve Method)</option>
					</select>
				</label>
				<button type="submit" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary" disabled={ isActing || isLoading }>{ isActing ? __('Saving...', 'asneris-seo-toolkit') : __('Add Redirect', 'asneris-seo-toolkit') }</button>
			</form>

			<div className="ASNERISSEO-react-block">
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" disabled={ isActing || isLoading } onClick={ () => sendAction({ url: `${ baseUrl }/clear-auto`, method: 'POST', successMessage: __('Automatic redirects cleared successfully.', 'asneris-seo-toolkit'), confirmMessage: __('Clear all automatic redirects?', 'asneris-seo-toolkit') }) }>
					{ __('Clear All Auto Redirects', 'asneris-seo-toolkit') }
				</button>
			</div>

			{ isLoading ? <p>{ __('Loading redirects...', 'asneris-seo-toolkit') }</p> : null }
			{ errorMessage ? <p className="ASNERISSEO-react-text-danger">{ errorMessage }</p> : null }

			<div className="ASNERISSEO-react-metrics-grid ASNERISSEO-react-grid-metrics ASNERISSEO-react-block ASNERISSEO-react-redirects-metrics">
				<MetricCard label={ __('Total', 'asneris-seo-toolkit') } value={ data.stats.total || 0 } />
				<MetricCard label={ __('Active', 'asneris-seo-toolkit') } value={ data.stats.active || 0 } />
				<MetricCard label={ __('Disabled', 'asneris-seo-toolkit') } value={ data.stats.disabled || 0 } />
			</div>

			<div className="ASNERISSEO-react-block">
				<h4 className="ASNERISSEO-heading-h3 ASNERISSEO-react-section-title">{ __('Redirect Results', 'asneris-seo-toolkit') }</h4>
				<div className="ASNERISSEO-react-table-wrap">
					<table className="widefat striped ASNERISSEO-react-redirects-table">
						<thead>
							<tr>
								<th>{ __('Status', 'asneris-seo-toolkit') }</th>
								<th>{ __('From', 'asneris-seo-toolkit') }</th>
								<th>{ __('To', 'asneris-seo-toolkit') }</th>
								<th>{ __('Code', 'asneris-seo-toolkit') }</th>
								<th>{ __('Type', 'asneris-seo-toolkit') }</th>
								<th>{ __('Actions', 'asneris-seo-toolkit') }</th>
							</tr>
						</thead>
						<tbody>
							{ data.items.length > 0 ? data.items.map((item, index) => (
								<tr key={ `${ item.index || index }-${ item.from || '' }` }>
									<td data-label={ __('Status', 'asneris-seo-toolkit') }>{ item.enabled ? __('Active', 'asneris-seo-toolkit') : __('Disabled', 'asneris-seo-toolkit') }</td>
									<td data-label={ __('From', 'asneris-seo-toolkit') }><code>{ item.from || '' }</code></td>
									<td data-label={ __('To', 'asneris-seo-toolkit') }><code>{ item.to || '' }</code></td>
									<td data-label={ __('Code', 'asneris-seo-toolkit') }>{ item.code || 301 }</td>
									<td data-label={ __('Type', 'asneris-seo-toolkit') }>{ item.type || 'manual' }</td>
									<td data-label={ __('Actions', 'asneris-seo-toolkit') }>
										<button type="button" className="button ASNERISSEO-react-action-inline ASNERISSEO-react-button ASNERISSEO-react-button-secondary" disabled={ isActing || isLoading } onClick={ () => requestToggleRedirect(item) }>
											{ item.enabled ? __('Disable', 'asneris-seo-toolkit') : __('Enable', 'asneris-seo-toolkit') }
										</button>
										<button
											type="button"
											className="button ASNERISSEO-react-button ASNERISSEO-react-button-destructive-secondary"
											disabled={ isActing || isLoading }
											onClick={ () => requestDeleteRedirect(item) }
										>
											{ __('Remove', 'asneris-seo-toolkit') }
										</button>
									</td>
								</tr>
							)) : (
								<tr>
									<td colSpan="6" className="ASNERISSEO-react-muted">
										{ isLoading
											? __('Loading redirect data...', 'asneris-seo-toolkit')
											: __('No redirect data found.', 'asneris-seo-toolkit') }
									</td>
								</tr>
							) }
						</tbody>
					</table>
				</div>
			</div>
		</PanelScaffold>
		<div className={ `ASNERISSEO-modal-overlay${ actionConfirmDialog.isOpen ? ' active' : '' }` }>
			<div className="ASNERISSEO-modal ASNERISSEO-modal-small" role="dialog" aria-modal="true" aria-label={ __('Confirm Action', 'asneris-seo-toolkit') }>
				<div className="ASNERISSEO-modal-header ASNERISSEO-modal-header-standard">
					<h3 className="ASNERISSEO-modal-title ASNERISSEO-modal-title-with-brand">
						{ modalLogoUrl ? (
							<img
								src={ modalLogoUrl }
								alt={ __('Asneris SEO Toolkit', 'asneris-seo-toolkit') }
								className="ASNERISSEO-modal-title-logo"
							/>
						) : (
							<span className="ASNERISSEO-modal-title-mark" aria-hidden="true">A</span>
						) }
						<span>{ actionConfirmDialog.title || __('Confirm Action', 'asneris-seo-toolkit') }</span>
					</h3>
					<button
						type="button"
						className="ASNERISSEO-modal-close ASNERISSEO-modal-close-light"
						onClick={ closeActionConfirmDialog }
						disabled={ isActing }
					>
						&times;
					</button>
				</div>
				<div className="ASNERISSEO-modal-content">
					<p>{ actionConfirmDialog.message || __('Do you want to proceed?', 'asneris-seo-toolkit') }</p>
				</div>
				<div className="ASNERISSEO-modal-footer">
					<button
						type="button"
						className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
						onClick={ closeActionConfirmDialog }
						disabled={ isActing }
					>
						{ __('Cancel', 'asneris-seo-toolkit') }
					</button>
					<button
						type="button"
						className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary"
						onClick={ proceedWithActionConfirmDialog }
						disabled={ isActing }
					>
						{ __('Proceed', 'asneris-seo-toolkit') }
					</button>
				</div>
			</div>
		</div>
		</>
	);
};

export default RedirectsPanel;
