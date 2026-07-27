import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { Popover } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import PanelScaffold from '../common/PanelScaffold';
import fetchJson from '../common/fetchJson';

const DEFAULT_MONITOR_SETTINGS = {
	enabled: false,
	first_time: true,
	throttle_limit: 30,
	throttle_window: 60,
	analysis_cron_frequency: 'disabled',
	analysis_cron_status: 'not_scheduled',
	analysis_next_run_gmt: '',
	log_limit: 1000,
	current_records: 0,
	max_records: 1000,
	log_limit_reached: false,
	storage_details: {
		table: {
			name: '',
			exists: false,
		},
		db_version: '',
		ready: false,
		current_records: 0,
		max_records: 1000,
		usage_percent: 0,
		log_limit_reached: false,
	},
	exclude_urls: '',
	exclude_keywords: '',
	ignore_query_params: false,
	wp_cron_enabled: true,
	system_cron_status: '',
	wp_cron_note: '',
};

const MAX_EXCLUDE_ITEMS = 10;

const splitLines = (value) =>
	String(value || '')
		.split(/\r?\n/)
		.map((line) => line.trim())
		.filter(Boolean);

const joinLines = (values) => values.join('\n');

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

const InfoTip = ({ text }) => {
	const [isOpen, setIsOpen] = useState(false);
	const iconRef = useRef(null);
	const contentRef = useRef(null);

	useEffect(() => {
		if (!isOpen) {
			return undefined;
		}

		const handleOutsideClick = (event) => {
			const eventPath = typeof event.composedPath === 'function' ? event.composedPath() : [];
			const clickedIcon = iconRef.current && (
				eventPath.includes(iconRef.current) || iconRef.current.contains(event.target)
			);
			const clickedPopover = contentRef.current && (
				eventPath.includes(contentRef.current) || contentRef.current.contains(event.target)
			);
			if (!clickedIcon && !clickedPopover) {
				setIsOpen(false);
			}
		};

		document.addEventListener('pointerdown', handleOutsideClick, true);
		return () => {
			document.removeEventListener('pointerdown', handleOutsideClick, true);
		};
	}, [isOpen]);

	return (
		<>
			<button
				type="button"
				ref={ iconRef }
				title={ text }
				aria-label={ text }
				onClick={ () => setIsOpen((previous) => !previous) }
				style={ {
					display: 'inline-flex',
					alignItems: 'center',
					justifyContent: 'center',
					width: '18px',
					height: '18px',
					borderRadius: '50%',
					border: '1px solid #8c8f94',
					fontSize: '12px',
					lineHeight: '1',
					cursor: 'pointer',
					marginLeft: '6px',
					padding: 0,
					background: '#f8fbff',
					color: '#2f4f6f',
				} }
			>
				i
			</button>
			{ isOpen && iconRef.current ? (
				<Popover anchor={ iconRef.current } onClose={ () => setIsOpen(false) } placement="bottom-start" shift>
					<div
						ref={ contentRef }
						className="ASNERISSEO-react-help-popover"
						style={ { borderLeft: '3px solid #72aee6', background: '#f0f6fc', color: '#365169' } }
					>
						<p className="ASNERISSEO-react-help-popover-note" style={ { marginTop: 0 } }>{ text }</p>
					</div>
				</Popover>
			) : null }
		</>
	);
};

const Monitor404SettingsPanel = ({ settingsRestUrl, restNonce, onStatus }) => {
	const [settings, setSettings] = useState(DEFAULT_MONITOR_SETTINGS);
	const [throttleLimit, setThrottleLimit] = useState(DEFAULT_MONITOR_SETTINGS.throttle_limit);
	const [throttleWindow, setThrottleWindow] = useState(DEFAULT_MONITOR_SETTINGS.throttle_window);
	const [analysisCronFrequency, setAnalysisCronFrequency] = useState(DEFAULT_MONITOR_SETTINGS.analysis_cron_frequency);
	const [logLimit, setLogLimit] = useState(DEFAULT_MONITOR_SETTINGS.log_limit);
	const [excludeUrls, setExcludeUrls] = useState(splitLines(DEFAULT_MONITOR_SETTINGS.exclude_urls));
	const [excludeKeywords, setExcludeKeywords] = useState(splitLines(DEFAULT_MONITOR_SETTINGS.exclude_keywords));
	const [excludeUrlInput, setExcludeUrlInput] = useState('');
	const [excludeKeywordInput, setExcludeKeywordInput] = useState('');
	const [ignoreQueryParams, setIgnoreQueryParams] = useState(DEFAULT_MONITOR_SETTINGS.ignore_query_params);
	const [isBusy, setIsBusy] = useState(false);
	const [errorMessage, setErrorMessage] = useState('');

	const headers = useMemo(
		() => ({
			'Content-Type': 'application/json',
			'X-WP-Nonce': restNonce || '',
		}),
		[restNonce]
	);

	const resolvedSettingsRestUrl =
		settingsRestUrl ||
		window.asnerisseoAdminDashboardData?.logs404SettingsRestUrl ||
		window.asnerisseoData?.logs404SettingsRestUrl ||
		'';

	const loadSettings = () => {
		if (!resolvedSettingsRestUrl) {
			setErrorMessage(__('404 settings endpoint is not available. Please refresh the page.', 'asneris-seo-toolkit'));
			return;
		}

		setErrorMessage('');
		fetchJson(resolvedSettingsRestUrl, { method: 'GET', headers })
			.then((payload) => {
				const merged = { ...DEFAULT_MONITOR_SETTINGS, ...(payload || {}) };
				setSettings(merged);
				setThrottleLimit(Number(merged.throttle_limit || DEFAULT_MONITOR_SETTINGS.throttle_limit));
				setThrottleWindow(Number(merged.throttle_window || DEFAULT_MONITOR_SETTINGS.throttle_window));
				setAnalysisCronFrequency(String(merged.analysis_cron_frequency || DEFAULT_MONITOR_SETTINGS.analysis_cron_frequency));
				setLogLimit(Number(merged.max_records || DEFAULT_MONITOR_SETTINGS.log_limit));
				setExcludeUrls(splitLines(merged.exclude_urls || DEFAULT_MONITOR_SETTINGS.exclude_urls).slice(0, MAX_EXCLUDE_ITEMS));
				setExcludeKeywords(splitLines(merged.exclude_keywords || DEFAULT_MONITOR_SETTINGS.exclude_keywords).slice(0, MAX_EXCLUDE_ITEMS));
				setExcludeUrlInput('');
				setExcludeKeywordInput('');
				setIgnoreQueryParams(!!merged.ignore_query_params);
			})
			.catch((error) => {
				const message = error.message || __('Unable to load 404 monitoring settings.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			});
	};

	useEffect(() => {
		loadSettings();
	}, [resolvedSettingsRestUrl, restNonce]);

	const saveSettings = (payload, successText) => {
		if (!resolvedSettingsRestUrl || isBusy) {
			if (!resolvedSettingsRestUrl) {
				const message = __('404 settings endpoint is missing. Changes could not be saved.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			}
			return;
		}

		setIsBusy(true);
		setErrorMessage('');
		fetchJson(resolvedSettingsRestUrl, {
			method: 'POST',
			headers,
			body: JSON.stringify(payload),
		})
			.then((response) => {
				const merged = { ...DEFAULT_MONITOR_SETTINGS, ...(response || {}) };
				setSettings(merged);
				setThrottleLimit(Number(merged.throttle_limit || DEFAULT_MONITOR_SETTINGS.throttle_limit));
				setThrottleWindow(Number(merged.throttle_window || DEFAULT_MONITOR_SETTINGS.throttle_window));
				setAnalysisCronFrequency(String(merged.analysis_cron_frequency || DEFAULT_MONITOR_SETTINGS.analysis_cron_frequency));
				setLogLimit(Number(merged.max_records || DEFAULT_MONITOR_SETTINGS.log_limit));
				setExcludeUrls(splitLines(merged.exclude_urls || DEFAULT_MONITOR_SETTINGS.exclude_urls));
				setExcludeKeywords(splitLines(merged.exclude_keywords || DEFAULT_MONITOR_SETTINGS.exclude_keywords));
				setExcludeUrlInput('');
				setExcludeKeywordInput('');
				setIgnoreQueryParams(!!merged.ignore_query_params);
				onStatus?.({ tone: 'success', text: successText });
			})
			.catch((error) => {
				const message = error.message || __('Failed to update 404 monitoring settings.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setIsBusy(false));
	};

	const isEnabled = !!settings.enabled;
	const isFormReadOnly = settings.wp_cron_enabled === false;

	const addExcludeUrl = () => {
		const value = String(excludeUrlInput || '').trim();
		if (!value) {
			return;
		}
		if (excludeUrls.length >= MAX_EXCLUDE_ITEMS) {
			setErrorMessage(
				sprintf(
					__('Maximum %d exclude paths are allowed.', 'asneris-seo-toolkit'),
					MAX_EXCLUDE_ITEMS
				)
			);
			return;
		}
		setErrorMessage('');
		setExcludeUrls((previous) => (previous.includes(value) ? previous : [...previous, value]));
		setExcludeUrlInput('');
	};

	const removeExcludeUrl = (value) => {
		setExcludeUrls((previous) => previous.filter((entry) => entry !== value));
	};

	const addExcludeKeyword = () => {
		const value = String(excludeKeywordInput || '').trim();
		if (!value) {
			return;
		}
		if (excludeKeywords.length >= MAX_EXCLUDE_ITEMS) {
			setErrorMessage(
				sprintf(
					__('Maximum %d exclude keywords are allowed.', 'asneris-seo-toolkit'),
					MAX_EXCLUDE_ITEMS
				)
			);
			return;
		}
		setErrorMessage('');
		setExcludeKeywords((previous) => (previous.includes(value) ? previous : [...previous, value]));
		setExcludeKeywordInput('');
	};

	const removeExcludeKeyword = (value) => {
		setExcludeKeywords((previous) => previous.filter((entry) => entry !== value));
	};

	const saveAllSettings = () => {
		if (excludeUrls.length > MAX_EXCLUDE_ITEMS) {
			setErrorMessage(
				sprintf(
					__('Maximum %d exclude paths are allowed.', 'asneris-seo-toolkit'),
					MAX_EXCLUDE_ITEMS
				)
			);
			return;
		}

		if (excludeKeywords.length > MAX_EXCLUDE_ITEMS) {
			setErrorMessage(
				sprintf(
					__('Maximum %d exclude keywords are allowed.', 'asneris-seo-toolkit'),
					MAX_EXCLUDE_ITEMS
				)
			);
			return;
		}

		setErrorMessage('');
		const limit = Math.min(500, Math.max(1, Number(throttleLimit || 0)));
		const windowSec = Math.min(3600, Math.max(10, Number(throttleWindow || 0)));
		const allowedFrequencies = ['disabled', 'hourly', 'daily', 'weekly', 'monthly'];
		const frequency = allowedFrequencies.includes(String(analysisCronFrequency || '').toLowerCase())
			? String(analysisCronFrequency).toLowerCase()
			: 'disabled';

		saveSettings(
			{
				enabled: !!isEnabled,
				collecting: !!isEnabled,
				throttle_limit: limit,
				throttle_window: windowSec,
				analysis_cron_frequency: frequency,
				exclude_urls: joinLines(excludeUrls),
				exclude_keywords: joinLines(excludeKeywords),
				ignore_query_params: !!ignoreQueryParams,
				acknowledge_first_time: true,
			},
			__('All 404 settings saved.', 'asneris-seo-toolkit')
		);
	};

	return (
		<PanelScaffold
			title={ __('404 Monitoring Controls', 'asneris-seo-toolkit') }
			description={ __('Configure 404 capture behavior using key-value controls.', 'asneris-seo-toolkit') }
			panelClass="ASNERISSEO-react-form-panel"
		>
			<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mb-8">
				<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-muted">
					{ sprintf(
						__('Storage policy: max %1$d records. When full, new 404s are skipped until you delete existing records.', 'asneris-seo-toolkit'),
						Number(settings.max_records || 1000)
					) }
				</p>
				<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-muted">
					{ sprintf(
						__('Current usage: %1$d / %2$d.', 'asneris-seo-toolkit'),
						Number(settings.current_records || 0),
						Number(settings.max_records || 1000)
					) }
				</p>
			</div>

			<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mb-8">
				<p className="ASNERISSEO-react-note-box-title">{ __('404 Storage Details', 'asneris-seo-toolkit') }</p>
				<div style={ { display: 'grid', gap: '8px' } }>
					<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-muted">
						<strong>{ __('Log Table:', 'asneris-seo-toolkit') }</strong>{ ' ' }
						{ settings?.storage_details?.table?.name || '-' }
					</p>
					<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-muted">
						<strong>{ __('Table Status:', 'asneris-seo-toolkit') }</strong>{ ' ' }
						{ settings?.storage_details?.table?.exists ? __('Available', 'asneris-seo-toolkit') : __('Missing', 'asneris-seo-toolkit') }
					</p>
					<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-muted">
						<strong>{ __('DB Version:', 'asneris-seo-toolkit') }</strong>{ ' ' }
						{ settings?.storage_details?.db_version || '-' }
					</p>
					<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-muted">
						<strong>{ __('Usage:', 'asneris-seo-toolkit') }</strong>{ ' ' }
						{ `${ Number(settings?.storage_details?.current_records || settings.current_records || 0) } / ${ Number(settings?.storage_details?.max_records || settings.max_records || 1000) } (${ Number(settings?.storage_details?.usage_percent || 0) }%)` }
					</p>
				</div>
			</div>

			<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mb-8">
				<div style={ { display: 'grid', gap: '12px' } }>
					{ settings.wp_cron_enabled === false && settings.wp_cron_note ? (
						<div className="ASNERISSEO-react-note-box is-warning" style={ { marginBottom: '4px' } }>
							<p className="ASNERISSEO-react-note-box-title">{ __('Automatic analysis disabled', 'asneris-seo-toolkit') }</p>
							<p className="ASNERISSEO-react-mb-0">{ settings.wp_cron_note }</p>
							<p className="ASNERISSEO-react-mb-0" style={ { marginTop: '8px' } }>{ __('Only these fields are locked while System Cron is disabled: 404 Analysis Cron Frequency, Cron Status, and Next Expected Run.', 'asneris-seo-toolkit') }</p>
						</div>
					) : null }

					<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'center', gap: '12px' } }>
						<label className="ASNERISSEO-react-field-label" style={ { margin: 0 } }>
							{ __('Enable Monitoring', 'asneris-seo-toolkit') }<InfoTip text={ __('Master switch for front-end 404 capture.', 'asneris-seo-toolkit') } />
						</label>
						<input type="checkbox" checked={ isEnabled } disabled={ isBusy } onChange={ (event) => setSettings((previous) => ({ ...previous, enabled: event.target.checked })) } />
					</div>

					<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'center', gap: '12px' } }>
						<label className="ASNERISSEO-react-field-label" style={ { margin: 0 } }>
							{ __('Throttle Limit', 'asneris-seo-toolkit') }<InfoTip text={ __('Maximum repeated captures per source/path in the window.', 'asneris-seo-toolkit') } />
						</label>
						<input type="number" min="1" max="500" className="ASNERISSEO-react-input" value={ throttleLimit } disabled={ isBusy } onChange={ (event) => setThrottleLimit(event.target.value) } />
					</div>

					<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'center', gap: '12px' } }>
						<label className="ASNERISSEO-react-field-label" style={ { margin: 0 } }>
							{ __('Throttle Window (sec)', 'asneris-seo-toolkit') }<InfoTip text={ __('Time window used by throttling logic.', 'asneris-seo-toolkit') } />
						</label>
						<input type="number" min="10" max="3600" className="ASNERISSEO-react-input" value={ throttleWindow } disabled={ isBusy } onChange={ (event) => setThrottleWindow(event.target.value) } />
					</div>

					<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'center', gap: '12px' } }>
						<label className="ASNERISSEO-react-field-label" style={ { margin: 0 } }>
							{ __('System Cron Status', 'asneris-seo-toolkit') }
						</label>
						<input
							type="text"
							className="ASNERISSEO-react-input"
							value={ settings.system_cron_status || '-' }
							readOnly
						/>
					</div>

					<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'center', gap: '12px' } }>
						<label className="ASNERISSEO-react-field-label" style={ { margin: 0 } }>
							{ __('404 Analysis Cron Frequency', 'asneris-seo-toolkit') }<InfoTip text={ __('How often automatic 404 analysis runs. Hourly runs every hour from save time; daily/weekly/monthly are anchored to 03:00 site time.', 'asneris-seo-toolkit') } />
						</label>
						<select
							className="ASNERISSEO-react-input"
							value={ analysisCronFrequency }
							disabled={ isFormReadOnly || isBusy }
							onChange={ (event) => setAnalysisCronFrequency(String(event.target.value || 'disabled').toLowerCase()) }
						>
							<option value="disabled">{ __('Disabled', 'asneris-seo-toolkit') }</option>
							<option value="daily">{ __('Daily', 'asneris-seo-toolkit') }</option>
							<option value="weekly">{ __('Weekly', 'asneris-seo-toolkit') }</option>
							<option value="monthly">{ __('Monthly', 'asneris-seo-toolkit') }</option>
							{/* <option value="hourly">{ __('Hourly', 'asneris-seo-toolkit') }</option> */}
						</select>
					</div>

					<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'center', gap: '12px' } }>
						<label className="ASNERISSEO-react-field-label" style={ { margin: 0 } }>
							{ __('Cron Status', 'asneris-seo-toolkit') }
						</label>
						<input type="text" className="ASNERISSEO-react-input" value={ formatCronStatusLabel(settings.analysis_cron_status) } readOnly />
					</div>

					<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'center', gap: '12px' } }>
						<label className="ASNERISSEO-react-field-label" style={ { margin: 0 } }>
							{ __('Next Expected Run', 'asneris-seo-toolkit') }
						</label>
						<input type="text" className="ASNERISSEO-react-input" value={ formatDateTimeLabel(settings.analysis_next_run_gmt) } readOnly />
					</div>

					<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'center', gap: '12px' } }>
						<label className="ASNERISSEO-react-field-label" style={ { margin: 0 } }>
							{ __('Max Stored Records', 'asneris-seo-toolkit') }<InfoTip text={ __('Fixed plugin limit for 404 storage.', 'asneris-seo-toolkit') } />
						</label>
						<input type="number" className="ASNERISSEO-react-input" value={ logLimit } disabled />
					</div>

					<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'center', gap: '12px' } }>
						<label className="ASNERISSEO-react-field-label" style={ { margin: 0 } }>
							{ __('Ignore Query Parameters', 'asneris-seo-toolkit') }<InfoTip text={ __('Skip 404s containing query strings such as ?utm_source=.', 'asneris-seo-toolkit') } />
						</label>
						<input type="checkbox" checked={ ignoreQueryParams } disabled={ isBusy } onChange={ (event) => setIgnoreQueryParams(event.target.checked) } />
					</div>

					<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'start', gap: '12px' } }>
						<label className="ASNERISSEO-react-field-label" style={ { margin: 0 } }>
							{ __('Exclude Paths', 'asneris-seo-toolkit') }<InfoTip text={ __('One by one add path/url patterns to skip. Wildcard * supported.', 'asneris-seo-toolkit') } />
						</label>
						<div>
							<div style={ { display: 'flex', gap: '8px', marginBottom: '8px' } }>
								<input type="text" className="ASNERISSEO-react-input" value={ excludeUrlInput } disabled={ isBusy } onChange={ (event) => setExcludeUrlInput(event.target.value) } placeholder={ __('Example: /tag/*', 'asneris-seo-toolkit') } />
								<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" onClick={ addExcludeUrl } disabled={ isBusy || excludeUrls.length >= MAX_EXCLUDE_ITEMS }>{ __('Add', 'asneris-seo-toolkit') }</button>
							</div>
							<div style={ { display: 'flex', flexWrap: 'wrap', gap: '8px' } }>
								{ excludeUrls.map((value) => (
									<span key={ value } style={ { border: '1px solid #dcdcde', borderRadius: '16px', padding: '4px 10px', display: 'inline-flex', alignItems: 'center', gap: '8px' } }>
										{ value }
										<button type="button" className="button-link" onClick={ () => removeExcludeUrl(value) } disabled={ isBusy }>{ __('x', 'asneris-seo-toolkit') }</button>
									</span>
								)) }
							</div>
							<p className="ASNERISSEO-react-helper-text ASNERISSEO-react-mb-0">
								{ sprintf(__('Max %d paths.', 'asneris-seo-toolkit'), MAX_EXCLUDE_ITEMS) }
							</p>
						</div>
					</div>

					<div style={ { display: 'grid', gridTemplateColumns: '260px 1fr', alignItems: 'start', gap: '12px' } }>
						<label className="ASNERISSEO-react-field-label" style={ { margin: 0 } }>
							{ __('Exclude Keywords', 'asneris-seo-toolkit') }<InfoTip text={ __('One by one add keywords. Matching 404 URLs/paths are skipped.', 'asneris-seo-toolkit') } />
						</label>
						<div>
							<div style={ { display: 'flex', gap: '8px', marginBottom: '8px' } }>
								<input type="text" className="ASNERISSEO-react-input" value={ excludeKeywordInput } disabled={ isBusy } onChange={ (event) => setExcludeKeywordInput(event.target.value) } placeholder={ __('Example: utm_', 'asneris-seo-toolkit') } />
								<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" onClick={ addExcludeKeyword } disabled={ isBusy || excludeKeywords.length >= MAX_EXCLUDE_ITEMS }>{ __('Add', 'asneris-seo-toolkit') }</button>
							</div>
							<div style={ { display: 'flex', flexWrap: 'wrap', gap: '8px' } }>
								{ excludeKeywords.map((value) => (
									<span key={ value } style={ { border: '1px solid #dcdcde', borderRadius: '16px', padding: '4px 10px', display: 'inline-flex', alignItems: 'center', gap: '8px' } }>
										{ value }
										<button type="button" className="button-link" onClick={ () => removeExcludeKeyword(value) } disabled={ isBusy }>{ __('x', 'asneris-seo-toolkit') }</button>
									</span>
								)) }
							</div>
							<p className="ASNERISSEO-react-helper-text ASNERISSEO-react-mb-0">
								{ sprintf(__('Max %d keywords.', 'asneris-seo-toolkit'), MAX_EXCLUDE_ITEMS) }
							</p>
						</div>
					</div>

				</div>
			</div>

			<div className="ASNERISSEO-react-actions-wrap">
				<button
					type="button"
					className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary"
					disabled={ isBusy }
					onClick={ saveAllSettings }
				>
					{ __('Save All Settings', 'asneris-seo-toolkit') }
				</button>
			</div>

			{ errorMessage ? <p className="ASNERISSEO-react-text-danger">{ errorMessage }</p> : null }
		</PanelScaffold>
	);
};

export default Monitor404SettingsPanel;
