import { __ } from '@wordpress/i18n';
import RestSettingsPanel from '../common/RestSettingsPanel';

const formatCronStatusLabel = (status) => {
	const normalized = String(status || '').toLowerCase();
	if (normalized === 'disabled') {
		return __('Disabled', 'asneris-seo-toolkit');
	}
	if (normalized === 'scheduled') {
		return __('Scheduled', 'asneris-seo-toolkit');
	}
	if (normalized === 'schedule_mismatch') {
		return __('Schedule mismatch', 'asneris-seo-toolkit');
	}
	return __('Not scheduled', 'asneris-seo-toolkit');
};

const formatDateTimeLabel = (value) => {
	if (!value) {
		return '-';
	}

	const date = new Date(String(value).replace(' ', 'T'));
	if (Number.isNaN(date.getTime())) {
		return String(value);
	}

	return `${ date.toLocaleDateString() } ${ date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }`;
};

const PageDiagnosticsSettingsPanel = ({ restUrl, restNonce, onStatus }) => (
	<RestSettingsPanel
		restUrl={ restUrl }
		restNonce={ restNonce }
		onStatus={ onStatus }
		title={ __('Page Diagnostics Settings', 'asneris-seo-toolkit') }
		description={ __('Configure Priority tab availability and diagnostics cron behavior.', 'asneris-seo-toolkit') }
		loadMessage={ __('Loading Page Diagnostics settings...', 'asneris-seo-toolkit') }
		saveMessage={ __('Page Diagnostics settings saved successfully.', 'asneris-seo-toolkit') }
		loadErrorMessage={ __('Unable to load Page Diagnostics settings.', 'asneris-seo-toolkit') }
		saveErrorMessage={ __('Failed to save Page Diagnostics settings.', 'asneris-seo-toolkit') }
		initialForm={ {
			page_diagnostics_priority_enabled: false,
			page_diagnostics_scan_cron_frequency: 'disabled',
			page_diagnostics_scan_cron_status: 'not_scheduled',
			page_diagnostics_scan_next_run_gmt: '',
			wp_cron_enabled: true,
			system_cron_status: '',
			wp_cron_note: '',
			snapshot_tables: {
				latest: { name: '', exists: false },
				history: { name: '', exists: false },
				ready: false,
			},
		} }
		mapLoadToForm={ (payload) => ({
			page_diagnostics_priority_enabled: !!payload?.page_diagnostics_priority_enabled,
			page_diagnostics_scan_cron_frequency: String(payload?.page_diagnostics_scan_cron_frequency || 'disabled'),
			page_diagnostics_scan_cron_status: String(payload?.page_diagnostics_scan_cron_status || 'not_scheduled'),
			page_diagnostics_scan_next_run_gmt: String(payload?.page_diagnostics_scan_next_run_gmt || ''),
			wp_cron_enabled: payload?.wp_cron_enabled !== false,
			system_cron_status: String(payload?.system_cron_status || ''),
			wp_cron_note: String(payload?.wp_cron_note || ''),
			snapshot_tables: {
				latest: {
					name: payload?.snapshot_tables?.latest?.name || '',
					exists: !!payload?.snapshot_tables?.latest?.exists,
				},
				history: {
					name: payload?.snapshot_tables?.history?.name || '',
					exists: !!payload?.snapshot_tables?.history?.exists,
				},
				ready: !!payload?.snapshot_tables?.ready,
			},
		}) }
		mapSaveToForm={ (saved) => ({
			page_diagnostics_priority_enabled: !!saved?.page_diagnostics_priority_enabled,
			page_diagnostics_scan_cron_frequency: String(saved?.page_diagnostics_scan_cron_frequency || 'disabled'),
			page_diagnostics_scan_cron_status: String(saved?.page_diagnostics_scan_cron_status || 'not_scheduled'),
			page_diagnostics_scan_next_run_gmt: String(saved?.page_diagnostics_scan_next_run_gmt || ''),
			wp_cron_enabled: saved?.wp_cron_enabled !== false,
			system_cron_status: String(saved?.system_cron_status || ''),
			wp_cron_note: String(saved?.wp_cron_note || ''),
			snapshot_tables: {
				latest: {
					name: saved?.snapshot_tables?.latest?.name || '',
					exists: !!saved?.snapshot_tables?.latest?.exists,
				},
				history: {
					name: saved?.snapshot_tables?.history?.name || '',
					exists: !!saved?.snapshot_tables?.history?.exists,
				},
				ready: !!saved?.snapshot_tables?.ready,
			},
		}) }
		mapFormToSave={ (form) => ({
			page_diagnostics_priority_enabled: !!form.page_diagnostics_priority_enabled,
			page_diagnostics_scan_cron_frequency: String(form.page_diagnostics_scan_cron_frequency || 'disabled').toLowerCase(),
		}) }
		renderFields={ (form, updateField) => {
			const allowedScanFrequencies = ['disabled', 'hourly', 'daily', 'weekly', 'monthly'];
			const normalizedScanFrequency = allowedScanFrequencies.includes(String(form.page_diagnostics_scan_cron_frequency || '').toLowerCase())
				? String(form.page_diagnostics_scan_cron_frequency || '').toLowerCase()
				: 'disabled';
			const isScanCronLocked = form?.wp_cron_enabled === false;

			return (
				<>
				<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mb-10 is-warning">
					<p className="ASNERISSEO-react-note-box-title is-warning">{ __('Page Diagnostics Settings', 'asneris-seo-toolkit') }</p>
					<p className="ASNERISSEO-react-helper-text ASNERISSEO-react-mb-0">{ __('Configure Priority tab availability and diagnostics cron behavior.', 'asneris-seo-toolkit') }</p>
					<div className="ASNERISSEO-react-btn-row ASNERISSEO-react-align-center">
						<input
							id="ASNERISSEO-page-diagnostics-priority-enabled"
							type="checkbox"
							checked={ !!form.page_diagnostics_priority_enabled }
							onChange={ (event) => updateField('page_diagnostics_priority_enabled', event.target.checked) }
						/>
						<label className="ASNERISSEO-react-field-label ASNERISSEO-react-mb-0" htmlFor="ASNERISSEO-page-diagnostics-priority-enabled">
							{ __('Enable Priority Pages tab in Page Diagnostics', 'asneris-seo-toolkit') }
						</label>
						<span className={ `ASNERISSEO-react-status-chip ${ form.page_diagnostics_priority_enabled ? 'is-success' : 'is-warning' }` }>
							{ form.page_diagnostics_priority_enabled ? __('Feature: ON', 'asneris-seo-toolkit') : __('Feature: OFF', 'asneris-seo-toolkit') }
						</span>
					</div>
					<p className="ASNERISSEO-react-helper-text ASNERISSEO-react-mb-0">
						{ form.page_diagnostics_priority_enabled
							? __('Priority and Non-Priority tabs are available in Page Diagnostics.', 'asneris-seo-toolkit')
							: __('Priority tab is disabled; users can use Non-Priority tab only.', 'asneris-seo-toolkit') }
					</p>
					{ isScanCronLocked && form?.wp_cron_note ? (
						<p className="ASNERISSEO-react-helper-text ASNERISSEO-react-mb-0">{ form.wp_cron_note }</p>
					) : null }
					<div className="ASNERISSEO-react-pt-8" style={ { display: 'grid', gap: '10px' } }>
						<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'center', gap: '12px' } }>
							<span className="ASNERISSEO-react-field-label ASNERISSEO-react-mb-0">{ __('System Cron Status', 'asneris-seo-toolkit') }</span>
							<input type="text" className="ASNERISSEO-react-input" value={ form?.system_cron_status || '-' } readOnly />
						</div>
						<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'center', gap: '12px' } }>
							<label className="ASNERISSEO-react-field-label ASNERISSEO-react-mb-0" htmlFor="ASNERISSEO-page-diagnostics-scan-cron-frequency">
								{ __('Priority Page Scan Cron Frequency', 'asneris-seo-toolkit') }
							</label>
							<select
								id="ASNERISSEO-page-diagnostics-scan-cron-frequency"
								className="ASNERISSEO-react-select"
								value={ normalizedScanFrequency }
								disabled={ isScanCronLocked }
								onChange={ (event) => updateField('page_diagnostics_scan_cron_frequency', String(event.target.value || 'disabled').toLowerCase()) }
							>
								<option value="disabled">{ __('Disabled', 'asneris-seo-toolkit') }</option>
								<option value="daily">{ __('Daily', 'asneris-seo-toolkit') }</option>
								<option value="weekly">{ __('Weekly', 'asneris-seo-toolkit') }</option>
								<option value="monthly">{ __('Monthly', 'asneris-seo-toolkit') }</option>
							</select>
						</div>
						<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'center', gap: '12px' } }>
							<span className="ASNERISSEO-react-field-label ASNERISSEO-react-mb-0">{ __('Cron Status', 'asneris-seo-toolkit') }</span>
							<input type="text" className="ASNERISSEO-react-input" value={ formatCronStatusLabel(form.page_diagnostics_scan_cron_status) } readOnly />
						</div>
						<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'center', gap: '12px' } }>
							<span className="ASNERISSEO-react-field-label ASNERISSEO-react-mb-0">{ __('Next Expected Run', 'asneris-seo-toolkit') }</span>
							<input type="text" className="ASNERISSEO-react-input" value={ formatDateTimeLabel(form.page_diagnostics_scan_next_run_gmt) } readOnly />
						</div>
					</div>
				</div>

				<div className="ASNERISSEO-react-priority-overview-grid">
					<div className={ `ASNERISSEO-react-priority-overview-card ASNERISSEO-react-priority-overview-status ${ form?.snapshot_tables?.ready ? 'is-success' : 'is-warning' }` }>
						<div className="ASNERISSEO-react-priority-overview-head">
							<p className="ASNERISSEO-react-priority-overview-title">{ __('📦 Snapshot Storage', 'asneris-seo-toolkit') }</p>
							<span className={ `ASNERISSEO-react-status-chip ${ form?.snapshot_tables?.ready ? 'is-success' : 'is-warning' }` }>
								{ form?.snapshot_tables?.ready ? __('Ready', 'asneris-seo-toolkit') : __('Setup Needed', 'asneris-seo-toolkit') }
							</span>
						</div>
						<ul className="ASNERISSEO-react-priority-overview-list">
							<li>
								<span>{ __('✓ Latest snapshot', 'asneris-seo-toolkit') }</span>
								<strong>{ form?.snapshot_tables?.latest?.exists ? __('Available', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit') }</strong>
							</li>
							<li>
								<span>{ __('✓ History storage', 'asneris-seo-toolkit') }</span>
								<strong>{ form?.snapshot_tables?.history?.exists ? __('Enabled', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit') }</strong>
							</li>
							<li>
								<span>{ __('✓ Status', 'asneris-seo-toolkit') }</span>
								<strong>{ form?.snapshot_tables?.ready ? __('All good', 'asneris-seo-toolkit') : __('Waiting for first run', 'asneris-seo-toolkit') }</strong>
							</li>
						</ul>
					</div>

					<div className="ASNERISSEO-react-priority-overview-card">
						<div className="ASNERISSEO-react-priority-overview-head">
							<p className="ASNERISSEO-react-priority-overview-title">{ __('🕒 Snapshot Retention', 'asneris-seo-toolkit') }</p>
							<span className="ASNERISSEO-react-priority-overview-readonly">{ __('Read only', 'asneris-seo-toolkit') }</span>
						</div>
						<div className="ASNERISSEO-react-priority-metrics-grid">
							<div className="ASNERISSEO-react-priority-metric-box">
								<p className="ASNERISSEO-react-priority-metric-label">{ __('History limit', 'asneris-seo-toolkit') }</p>
								<p className="ASNERISSEO-react-priority-metric-value">10</p>
								<p className="ASNERISSEO-react-priority-metric-subtext">{ __('Versions', 'asneris-seo-toolkit') }</p>
							</div>
							<div className="ASNERISSEO-react-priority-metric-box">
								<p className="ASNERISSEO-react-priority-metric-label">{ __('Age retention', 'asneris-seo-toolkit') }</p>
								<p className="ASNERISSEO-react-priority-metric-value">30</p>
								<p className="ASNERISSEO-react-priority-metric-subtext">{ __('Days', 'asneris-seo-toolkit') }</p>
							</div>
						</div>
					</div>

					<div className="ASNERISSEO-react-priority-overview-card ASNERISSEO-react-priority-overview-notice">
						<div className="ASNERISSEO-react-priority-overview-head">
							<p className="ASNERISSEO-react-priority-overview-title">{ __('⚠ Cleanup', 'asneris-seo-toolkit') }</p>
						</div>
						<p className="ASNERISSEO-react-mb-0">{ __('Removing a page deletes its diagnostics history.', 'asneris-seo-toolkit') }</p>
						<a href="https://asneris.com/wp-toolkits/asneris-wordpress-seo-toolkit-page-diagnostics/" target="_blank" rel="noopener noreferrer" className="ASNERISSEO-react-priority-learn-link">{ __('Learn more', 'asneris-seo-toolkit') }</a>
					</div>
				</div>

				<section className="ASNERISSEO-react-priority-storage-details" aria-label={ __('Snapshot Storage Details', 'asneris-seo-toolkit') }>
					<p className="ASNERISSEO-react-priority-storage-title">{ __('🗄 Snapshot Storage Details', 'asneris-seo-toolkit') }</p>
					<div className="ASNERISSEO-react-priority-storage-item">
						<p className="ASNERISSEO-react-priority-storage-item-title">{ __('✓ Latest Snapshot Table', 'asneris-seo-toolkit') }</p>
						<p className="ASNERISSEO-react-priority-storage-table-name">{ form?.snapshot_tables?.latest?.name || '-' }</p>
						<p className="ASNERISSEO-react-priority-storage-status">
							{ `${ __('Status:', 'asneris-seo-toolkit') } ${ form?.snapshot_tables?.latest?.exists ? __('Available', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit') }` }
						</p>
					</div>
					<div className="ASNERISSEO-react-priority-storage-item">
						<p className="ASNERISSEO-react-priority-storage-item-title">{ __('✓ History Snapshot Table', 'asneris-seo-toolkit') }</p>
						<p className="ASNERISSEO-react-priority-storage-table-name">{ form?.snapshot_tables?.history?.name || '-' }</p>
						<p className="ASNERISSEO-react-priority-storage-status">
							{ `${ __('Status:', 'asneris-seo-toolkit') } ${ form?.snapshot_tables?.history?.exists ? __('Available', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit') }` }
						</p>
					</div>
				</section>
				</>
			);
		} }
		saveButtonLabel={ __('Save Changes', 'asneris-seo-toolkit') }
		showSaveButton={ true }
	/>
);

export default PageDiagnosticsSettingsPanel;
