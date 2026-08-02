import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import PanelScaffold from '../common/PanelScaffold';
import MetricCard from '../common/MetricCard';
import fetchJson from '../common/fetchJson';
import PerformanceTrackerCard from '../common/PerformanceTrackerCard';

const getRelativeDateInput = (daysFromToday = 0) => {
	const date = new Date();
	date.setHours(0, 0, 0, 0);
	date.setDate(date.getDate() + Number(daysFromToday || 0));

	const year = date.getFullYear();
	const month = String(date.getMonth() + 1).padStart(2, '0');
	const day = String(date.getDate()).padStart(2, '0');
	return `${ year }-${ month }-${ day }`;
};

const DEFAULT_FILTERS = {
	search: '',
	priority_filter: ['all'],
	recommendation_filter: ['proposed_redirect_url'],
	status: 'active',
	sort_by: 'last_seen',
	sort_dir: 'desc',
	date_from: getRelativeDateInput(-7),
	date_to: getRelativeDateInput(0),
};

const DEFAULT_STATS = {
	total_urls: 0,
	active_count: 0,
	redirected_count: 0,
	ignored_count: 0,
	last_7_days_hits: 0,
};

const DEFAULT_MONITOR_SETTINGS = {
	enabled: false,
	first_time: true,
	current_records: 0,
	max_records: 1000,
	log_limit_reached: false,
};

const formatDurationMs = (value) => `${ Number(value || 0).toLocaleString() } ms`;

const formatBytesToMb = (value) => `${ (Number(value || 0) / (1024 * 1024)).toFixed(2) } MB`;

const toStatusChipClass = (status) => {
	if (status === 'warning') {
		return 'is-warning';
	}

	if (status === 'good' || status === 'excellent') {
		return 'is-success';
	}

	return 'is-neutral';
};

const formatPerformanceStatus = (status) => {
	const normalized = String(status || '').trim().toLowerCase();
	if (!normalized) {
		return '-';
	}

	return `${ normalized.charAt(0).toUpperCase() }${ normalized.slice(1) }`;
};

const formatCronStatusLabel = (status) => {
	const normalized = String(status || '').toLowerCase();
	if (normalized === 'scheduled') {
		return __('Scheduled', 'asneris-seo-toolkit');
	}
	if (normalized === 'schedule_mismatch') {
		return __('Schedule mismatch', 'asneris-seo-toolkit');
	}
	return __('Not scheduled', 'asneris-seo-toolkit');
};

const MAX_DATE_RANGE_DAYS = 90;
const TAB_RECOMMANDATION = 'recommandation';
const TAB_WHAT_HAPPENED = 'what_happened';

const toDateValue = (value) => {
	if (!value) {
		return null;
	}
	const parsed = new Date(`${ value }T00:00:00`);
	return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const toInputDate = (date) => {
	if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
		return '';
	}
	const year = date.getFullYear();
	const month = String(date.getMonth() + 1).padStart(2, '0');
	const day = String(date.getDate()).padStart(2, '0');
	return `${ year }-${ month }-${ day }`;
};

const shiftDate = (value, days) => {
	const base = toDateValue(value);
	if (!base) {
		return '';
	}
	const shifted = new Date(base);
	shifted.setDate(shifted.getDate() + days);
	return toInputDate(shifted);
};

const buildRequestUrl = (baseUrl, params = {}) => {
	const normalizedBaseUrl = String(baseUrl || '').trim();

	if (!normalizedBaseUrl) {
		return '';
	}

	try {
		const url = new URL(normalizedBaseUrl, window.location.href);
		Object.entries(params).forEach(([key, value]) => {
			if (value !== '' && value !== null && value !== undefined) {
				url.searchParams.set(key, String(value));
			}
		});
		return url.toString();
	} catch (error) {
		const query = new URLSearchParams();
		Object.entries(params).forEach(([key, value]) => {
			if (value !== '' && value !== null && value !== undefined) {
				query.set(key, String(value));
			}
		});

		if (!query.toString()) {
			return normalizedBaseUrl;
		}

		return `${ normalizedBaseUrl }${ normalizedBaseUrl.includes('?') ? '&' : '?' }${ query.toString() }`;
	}
};

const clampToToday = (value) => {
	if (!value) {
		return '';
	}
	const inputDate = toDateValue(value);
	if (!inputDate) {
		return '';
	}
	const today = new Date();
	today.setHours(0, 0, 0, 0);
	return inputDate > today ? toInputDate(today) : value;
};

const getPriorityMeta = (priorityValue) => {
	const priority = Number(priorityValue);

	if (priority >= 3) {
		return { label: 'Critical', value: 3 };
	}

	if (priority === 2) {
		return { label: 'High', value: 2 };
	}

	if (priority === 1) {
		return { label: 'Medium', value: 1 };
	}

	return { label: 'Low', value: 0 };
};

const FilterDropdown = ({
	dropdownId,
	openDropdownId,
	setOpenDropdownId,
	label,
	value,
	values,
	options,
	onChange,
	allValue = 'all',
	wrapperClassName = '',
	triggerClassName = '',
	disabled = false,
	multi = false,
}) => {
	const dropdownRef = useRef(null);
	const isOpen = openDropdownId === dropdownId;

	useEffect(() => {
		const handleOutsideClick = (event) => {
			if (isOpen && dropdownRef.current && !dropdownRef.current.contains(event.target)) {
				setOpenDropdownId(null);
			}
		};

		document.addEventListener('mousedown', handleOutsideClick);
		return () => {
			document.removeEventListener('mousedown', handleOutsideClick);
		};
	}, [isOpen, setOpenDropdownId]);

	const selectedValues = Array.isArray(values) ? values : [];
	const selectedOption = options.find((option) => option.value === value);
	const selectedLabel = selectedOption?.label || options[0]?.label || '';
	const selectedMultiLabel = selectedValues.includes(allValue)
		? (options.find((option) => option.value === allValue)?.label || options[0]?.label || '')
		: options
			.filter((option) => selectedValues.includes(option.value) && option.value !== allValue)
			.map((option) => option.label)
			.join(', ');

	const handleSingleSelect = (optionValue) => {
		onChange(optionValue);
		setOpenDropdownId(null);
	};

	const handleMultiToggle = (optionValue) => {
		if (allValue === optionValue) {
			onChange([allValue]);
			return;
		}

		const current = selectedValues.includes(allValue) ? [] : [...selectedValues];
		const exists = current.includes(optionValue);
		const next = exists
			? current.filter((existingValue) => existingValue !== optionValue)
			: [...current, optionValue];

		onChange(next.length > 0 ? next : [allValue]);
	};

	return (
		<div className={ `ASNERISSEO-react-404-filter-single-dropdown${ wrapperClassName ? ` ${ wrapperClassName }` : '' }` } ref={ dropdownRef }>
			{ label ? <div className="ASNERISSEO-react-field-label">{ label }</div> : null }
			<button
				type="button"
				className={ triggerClassName }
				onClick={ () => {
					if (!disabled) {
						setOpenDropdownId(isOpen ? null : dropdownId);
					}
				} }
				aria-haspopup="listbox"
				aria-expanded={ isOpen }
				disabled={ disabled }
			>
				<span className="ASNERISSEO-react-404-filter-multi-text">{ multi ? (selectedMultiLabel || selectedLabel) : selectedLabel }</span>
				<span className="ASNERISSEO-react-404-filter-multi-caret" aria-hidden="true">▾</span>
			</button>
			{ isOpen ? (
				multi ? (
					<div className="ASNERISSEO-react-404-filter-multi-menu" role="listbox" aria-multiselectable="true">
						{ options.map((option) => {
							const checked = option.value === allValue
								? selectedValues.includes(allValue)
								: selectedValues.includes(option.value);

							return (
								<label key={ option.value } className="ASNERISSEO-react-404-filter-multi-option">
									<input
										type="checkbox"
										checked={ checked }
										onChange={ () => handleMultiToggle(option.value) }
									/>
									<span>{ option.label }</span>
								</label>
							);
						}) }
					</div>
				) : (
					<div className="ASNERISSEO-react-404-filter-single-menu" role="listbox">
						{ options.map((option) => (
							<button
								key={ option.value }
								type="button"
								className={ `ASNERISSEO-react-404-filter-single-option${ option.value === value ? ' is-selected' : '' }` }
								onClick={ () => handleSingleSelect(option.value) }
							>
								{ option.label }
							</button>
						)) }
					</div>
				)
			) : null }
		</div>
	);
};

const Monitor404Panel = ({ logsRestUrl, statsRestUrl, bulkRestUrl, exportRestUrl, settingsRestUrl, restNonce, onStatus }) => {
	const modalLogoUrl = String(window.asnerisseoAdminDashboardData?.logoUrl || window.asnerisseoData?.logoUrl || '').trim();
	const [filters, setFilters] = useState(DEFAULT_FILTERS);
	const [submittedFilters, setSubmittedFilters] = useState(DEFAULT_FILTERS);
	const [logsPayload, setLogsPayload] = useState({ items: [], total: 0, page: 1, per_page: 50, total_pages: 0 });
	const [stats, setStats] = useState(DEFAULT_STATS);
	const [selectedIds, setSelectedIds] = useState([]);
	const [bulkAction, setBulkAction] = useState('ignore');
	const [redirectTarget, setRedirectTarget] = useState('');
	const [isLoading, setIsLoading] = useState(false);
	const [isSubmitting, setIsSubmitting] = useState(false);
	const [errorMessage, setErrorMessage] = useState('');
	const [monitorSettings, setMonitorSettings] = useState(DEFAULT_MONITOR_SETTINGS);
	const [openDropdownId, setOpenDropdownId] = useState(null);
	const [activeMonitorView, setActiveMonitorView] = useState(TAB_WHAT_HAPPENED);
	const [isMobileFilterOpen, setIsMobileFilterOpen] = useState(false);
	const [isMobileSummaryOpen, setIsMobileSummaryOpen] = useState(false);
	const [expandedMobileRowKey, setExpandedMobileRowKey] = useState(null);
	const [lastPerformance, setLastPerformance] = useState(null);
	const [redirectDialog, setRedirectDialog] = useState({
		isOpen: false,
		itemId: 0,
		sourcePath: '',
		target: '/',
		code: 301,
	});
	const [detailDialog, setDetailDialog] = useState({
		isOpen: false,
		item: null,
	});
	const [manualRefreshKey, setManualRefreshKey] = useState(0);
	const [actionConfirmDialog, setActionConfirmDialog] = useState({
		isOpen: false,
		title: '',
		message: '',
		onProceed: null,
	});
	const latestLoadRequestRef = useRef(0);

	const headers = useMemo(
		() => ({
			'Content-Type': 'application/json',
			'X-WP-Nonce': restNonce || '',
		}),
		[restNonce]
	);

	const queryString = useMemo(() => {
		const params = new URLSearchParams();
		Object.entries(submittedFilters).forEach(([key, value]) => {
			if (value !== '' && value !== null && value !== undefined) {
				params.set(key, String(value));
			}
		});
		params.set('page', String(logsPayload.page || 1));
		params.set('per_page', String(logsPayload.per_page || 20));
		return params.toString();
	}, [submittedFilters, logsPayload.page, logsPayload.per_page]);

	const loadData = () => {
		if (!logsRestUrl || !statsRestUrl) {
			return;
		}

		const requestId = latestLoadRequestRef.current + 1;
		latestLoadRequestRef.current = requestId;

		setIsLoading(true);
		setErrorMessage('');

		const settingsPromise = settingsRestUrl
			? fetchJson(settingsRestUrl, { method: 'GET', headers })
			: Promise.resolve(DEFAULT_MONITOR_SETTINGS);

		Promise.all([
			fetchJson(buildRequestUrl(logsRestUrl, Object.fromEntries(new URLSearchParams(queryString))), { method: 'GET', headers }),
			fetchJson(statsRestUrl, { method: 'GET', headers }),
			settingsPromise,
		])
			.then(([logsResponse, statsResponse, settingsResponse]) => {
				if (requestId !== latestLoadRequestRef.current) {
					return;
				}

				setLogsPayload((previous) => ({
					...previous,
					items: Array.isArray(logsResponse?.items) ? logsResponse.items : [],
					total: Number(logsResponse?.total || 0),
					total_pages: Number(logsResponse?.total_pages || 0),
				}));
				setStats({ ...DEFAULT_STATS, ...(statsResponse || {}) });
				setMonitorSettings({ ...DEFAULT_MONITOR_SETTINGS, ...(settingsResponse || {}) });
				setSelectedIds([]);
			})
			.catch((error) => {
				if (requestId !== latestLoadRequestRef.current) {
					return;
				}

				const message = error.message || __('Failed to load 404 monitor data.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => {
				if (requestId === latestLoadRequestRef.current) {
					setIsLoading(false);
				}
			});
	};

	const applyCurrentFilters = () => {
		setLogsPayload((previous) => ({ ...previous, page: 1 }));
		setSubmittedFilters({ ...filters });
		setManualRefreshKey((previous) => previous + 1);
	};

	useEffect(() => {
		loadData();
	}, [logsRestUrl, statsRestUrl, settingsRestUrl, queryString, manualRefreshKey]);

	useEffect(() => {
		if (activeMonitorView === TAB_WHAT_HAPPENED) {
			setLogsPayload((previous) => ({
				...previous,
				page: 1,
				per_page: 50,
			}));
			setFilters((previous) => ({
				...previous,
				priority_filter: ['all'],
				recommendation_filter: ['all'],
			}));
			setSubmittedFilters((previous) => ({
				...previous,
				priority_filter: ['all'],
				recommendation_filter: ['all'],
			}));
		} else if (activeMonitorView === TAB_RECOMMANDATION) {
			setLogsPayload((previous) => ({
				...previous,
				page: 1,
				per_page: 20,
			}));
			setFilters((previous) => {
				const allowedPriority = ['all_non_low', 'critical', 'high', 'medium'];
				const rawPriority = Array.isArray(previous.priority_filter)
					? previous.priority_filter
					: [String(previous.priority_filter || 'all_non_low')];
				let normalizedPriority = rawPriority
					.map((value) => String(value || '').trim().toLowerCase())
					.map((value) => (value === 'all' ? 'all_non_low' : value))
					.filter((value) => allowedPriority.includes(value));

				if (normalizedPriority.length === 0 || normalizedPriority.includes('all_non_low')) {
					normalizedPriority = ['all_non_low'];
				} else {
					normalizedPriority = normalizedPriority.filter((value) => value !== 'all_non_low');
				}

				const currentPriority = Array.isArray(previous.priority_filter)
					? previous.priority_filter
					: [String(previous.priority_filter || '')];
				const priorityChanged = currentPriority.join(',') !== normalizedPriority.join(',');

				if (previous.status !== 'all' || priorityChanged) {
					return {
						...previous,
						status: 'all',
						priority_filter: normalizedPriority,
					};
				}

				return previous;
			});
			setSubmittedFilters((previous) => {
				const allowedPriority = ['all_non_low', 'critical', 'high', 'medium'];
				const rawPriority = Array.isArray(previous.priority_filter)
					? previous.priority_filter
					: [String(previous.priority_filter || 'all_non_low')];
				let normalizedPriority = rawPriority
					.map((value) => String(value || '').trim().toLowerCase())
					.map((value) => (value === 'all' ? 'all_non_low' : value))
					.filter((value) => allowedPriority.includes(value));

				if (normalizedPriority.length === 0 || normalizedPriority.includes('all_non_low')) {
					normalizedPriority = ['all_non_low'];
				} else {
					normalizedPriority = normalizedPriority.filter((value) => value !== 'all_non_low');
				}

				return {
					...previous,
					status: 'all',
					priority_filter: normalizedPriority,
				};
			});
		}
	}, [activeMonitorView]);

	useEffect(() => {
		setExpandedMobileRowKey(null);
	}, [activeMonitorView, logsPayload.items]);

	const toggleSelection = (id) => {
		setSelectedIds((previous) => {
			if (previous.includes(id)) {
				return previous.filter((existingId) => existingId !== id);
			}
			return [...previous, id];
		});
	};

	const runSingleAction = (id, payload, successText) => {
		setIsSubmitting(true);
		setErrorMessage('');
		fetchJson(`${ logsRestUrl }/${ id }`, {
			method: 'POST',
			headers,
			body: JSON.stringify(payload),
		})
			.then(() => {
				onStatus?.({ tone: 'success', text: successText });
				loadData();
			})
			.catch((error) => {
				const message = error.message || __('Action failed for selected 404 URL.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setIsSubmitting(false));
	};

	const runDeleteAction = (id) => {
		setIsSubmitting(true);
		setErrorMessage('');
		fetchJson(`${ logsRestUrl }/${ id }`, {
			method: 'DELETE',
			headers,
		})
			.then(() => {
				onStatus?.({ tone: 'success', text: __('404 URL deleted successfully.', 'asneris-seo-toolkit') });
				loadData();
			})
			.catch((error) => {
				const message = error.message || __('Delete failed for selected 404 URL.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setIsSubmitting(false));
	};

	const runBulkAction = () => {
		if (!bulkRestUrl || selectedIds.length === 0) {
			return;
		}

		if (bulkAction === 'redirect' && !redirectTarget) {
			setErrorMessage(__('Redirect target is required for bulk redirect.', 'asneris-seo-toolkit'));
			return;
		}

		setIsSubmitting(true);
		setErrorMessage('');
		fetchJson(bulkRestUrl, {
			method: 'POST',
			headers,
			body: JSON.stringify({
				ids: selectedIds,
				action: bulkAction,
				redirect_target: redirectTarget,
			}),
		})
			.then((payload) => {
				onStatus?.({ tone: 'success', text: payload?.message || __('Bulk action completed.', 'asneris-seo-toolkit') });
				if (payload?.performance) {
					setLastPerformance(payload.performance);
				}
				setRedirectTarget('');
				loadData();
			})
			.catch((error) => {
				const message = error.message || __('Bulk action failed.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setIsSubmitting(false));
	};

	const handleExport = () => {
		if (!exportRestUrl) {
			return;
		}

		setIsSubmitting(true);
		setErrorMessage('');
		fetchJson(buildRequestUrl(exportRestUrl, Object.fromEntries(new URLSearchParams(queryString))), {
			method: 'GET',
			headers,
		})
			.then((payload) => {
				const rows = Array.isArray(payload?.rows) ? payload.rows : [];
				const headersRow = ['Path', 'Requested URL', 'Method', 'User Agent', 'Priority', 'Recommandation', 'Target Redirect', 'Hits', 'Status', 'First Seen', 'Last Seen', 'Referrer'];
				const csvRows = [headersRow.join(',')];
				rows.forEach((row) => {
					const recommendationItems = getRecommendationItems(row.recommandation);
					const cells = [
						row.path || '',
						row.requested_url || '',
						row.method || '',
						row.user_agent || '',
						String(row.priority || 0),
						recommendationItems.join(' | '),
						row.redirect_target || '',
						String(row.hit_count || 0),
						row.status || '',
						row.first_seen || '',
						row.last_seen || '',
						row.referrer || '',
					].map((cell) => `"${ String(cell).replace(/"/g, '""') }"`);
					csvRows.push(cells.join(','));
				});

				const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
				const link = document.createElement('a');
				link.href = URL.createObjectURL(blob);
				link.download = payload?.filename || 'asneris-404-logs.csv';
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);

				onStatus?.({ tone: 'success', text: __('CSV exported successfully.', 'asneris-seo-toolkit') });
			})
			.catch((error) => {
				const message = error.message || __('Failed to export CSV.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setIsSubmitting(false));
	};

	const setFilterValue = (key, value) => {
		if (key === 'date_from' || key === 'date_to') {
			const nextDateValue = clampToToday(value);
			const nextFilters = {
				...filters,
				[key]: nextDateValue,
			};

			const fromDate = toDateValue(nextFilters.date_from);
			const toDate = toDateValue(nextFilters.date_to);
			if (fromDate && toDate) {
				const rangeInMs = toDate.getTime() - fromDate.getTime();
				const rangeInDays = Math.floor(rangeInMs / 86400000);
				if (rangeInDays < 0) {
					setErrorMessage(__('From date must be before To date.', 'asneris-seo-toolkit'));
					return;
				}
				if (rangeInDays > MAX_DATE_RANGE_DAYS) {
					setErrorMessage(
						sprintf(
							__('Date range cannot exceed %d days.', 'asneris-seo-toolkit'),
							MAX_DATE_RANGE_DAYS
						)
					);
					return;
				}
			}

			setErrorMessage('');
			setLogsPayload((previous) => ({ ...previous, page: 1 }));
			setFilters(nextFilters);
			if (activeMonitorView === TAB_WHAT_HAPPENED) {
				setSubmittedFilters(nextFilters);
			}
			return;
		}

		setLogsPayload((previous) => ({ ...previous, page: 1 }));
		setFilters((previous) => {
			const nextFilters = { ...previous, [key]: value };
			if (activeMonitorView === TAB_WHAT_HAPPENED) {
				setSubmittedFilters(nextFilters);
			}
			return nextFilters;
		});
	};

	const setRecommendationFilterValue = (selectedValues) => {
		const allowedValues = ['proposed_redirect_url'];
		let normalized = Array.isArray(selectedValues)
			? selectedValues.map((value) => String(value || '').trim().toLowerCase()).filter(Boolean)
			: [];

		normalized = normalized.filter((value) => allowedValues.includes(value));

		if (normalized.length === 0) {
			normalized = ['proposed_redirect_url'];
		}

		setFilterValue('recommendation_filter', normalized);
	};

	const setPriorityFilterValue = (selectedValues) => {
		const isRecommendationView = activeMonitorView === TAB_RECOMMANDATION;
		const allowedValues = isRecommendationView
			? ['all_non_low', 'critical', 'high', 'medium']
			: ['all', 'all_non_low', 'critical', 'high', 'medium', 'low'];
		let normalized = Array.isArray(selectedValues)
			? selectedValues.map((value) => String(value || '').trim().toLowerCase()).filter(Boolean)
			: [];

		if (isRecommendationView) {
			normalized = normalized.map((value) => (value === 'all' ? 'all_non_low' : value));
		} else {
			normalized = normalized.map((value) => (value === 'all_non_low' ? 'all' : value));
		}
		normalized = normalized.filter((value) => allowedValues.includes(value));

		if (isRecommendationView) {
			if (normalized.length === 0 || normalized.includes('all_non_low')) {
				normalized = ['all_non_low'];
			} else {
				normalized = normalized.filter((value) => value !== 'all_non_low');
			}
		} else {
			if (normalized.length === 0 || normalized.includes('all')) {
				normalized = ['all'];
			} else {
				normalized = normalized.filter((value) => value !== 'all');
			}
		}

		setFilterValue('priority_filter', normalized);
	};

	const recommendationOptions = [
		{ value: 'proposed_redirect_url', label: __('Proposed Redirect Page', 'asneris-seo-toolkit') },
	];
	const priorityOptions = activeMonitorView === TAB_RECOMMANDATION
		? [
			{ value: 'all_non_low', label: __('All', 'asneris-seo-toolkit') },
			{ value: 'critical', label: __('Critical', 'asneris-seo-toolkit') },
			{ value: 'high', label: __('High', 'asneris-seo-toolkit') },
			{ value: 'medium', label: __('Medium', 'asneris-seo-toolkit') },
		]
		: [
			{ value: 'all', label: __('All', 'asneris-seo-toolkit') },
			{ value: 'critical', label: __('Critical', 'asneris-seo-toolkit') },
			{ value: 'high', label: __('High', 'asneris-seo-toolkit') },
			{ value: 'medium', label: __('Medium', 'asneris-seo-toolkit') },
			{ value: 'low', label: __('Low', 'asneris-seo-toolkit') },
		];
	const statusOptions = activeMonitorView === TAB_RECOMMANDATION
		? [
			{ value: 'active', label: __('Active', 'asneris-seo-toolkit') },
			{ value: 'redirected', label: __('Redirected', 'asneris-seo-toolkit') },
		]
		: [
			{ value: 'active', label: __('Active', 'asneris-seo-toolkit') },
			{ value: 'redirected', label: __('Redirected', 'asneris-seo-toolkit') },
			{ value: 'ignored', label: __('Ignored', 'asneris-seo-toolkit') },
			{ value: 'fixed', label: __('Fixed', 'asneris-seo-toolkit') },
			{ value: 'all', label: __('All', 'asneris-seo-toolkit') },
		];
	const sortByOptions = [
		{ value: 'last_seen', label: __('Sort: Last Seen', 'asneris-seo-toolkit') },
		{ value: 'first_seen', label: __('Sort: First Seen', 'asneris-seo-toolkit') },
		{ value: 'hit_count', label: __('Sort: Hits', 'asneris-seo-toolkit') },
		{ value: 'priority', label: __('Sort: Priority', 'asneris-seo-toolkit') },
		{ value: 'path', label: __('Sort: Broken URL', 'asneris-seo-toolkit') },
		{ value: 'redirect_target', label: __('Sort: Target Redirect', 'asneris-seo-toolkit') },
	];
	const sortDirOptions = [
		{ value: 'desc', label: __('Descending', 'asneris-seo-toolkit') },
		{ value: 'asc', label: __('Ascending', 'asneris-seo-toolkit') },
	];
	const bulkActionOptions = [
		{ value: 'ignore', label: __('Ignore', 'asneris-seo-toolkit') },
		{ value: 'activate', label: __('Activate', 'asneris-seo-toolkit') },
		{ value: 'analyze', label: __('Analysis', 'asneris-seo-toolkit') },
		{ value: 'redirect', label: __('Add Redirect', 'asneris-seo-toolkit') },
		{ value: 'delete', label: __('Bulk Delete', 'asneris-seo-toolkit') },
	];
	const redirectCodeOptions = [
		{ value: '301', label: __('301 (Permanent)', 'asneris-seo-toolkit') },
		{ value: '302', label: __('302 (Temporary)', 'asneris-seo-toolkit') },
		{ value: '307', label: __('307 (Temporary, method preserved)', 'asneris-seo-toolkit') },
	];

	const selectedRecommendationValues = Array.isArray(filters.recommendation_filter) && filters.recommendation_filter.length
		? filters.recommendation_filter
		: ['all'];
	const selectedPriorityValues = Array.isArray(filters.priority_filter) && filters.priority_filter.length
		? filters.priority_filter
		: (activeMonitorView === TAB_RECOMMANDATION ? ['all_non_low'] : ['all']);
	const currentPage = Math.max(1, Number(logsPayload.page || 1));
	const totalPages = Math.max(1, Number(logsPayload.total_pages || 1));
	const totalRecords = Math.max(0, Number(logsPayload.total || 0));
	const perPage = Math.max(1, Number(logsPayload.per_page || 20));
	const hasPreviousPage = currentPage > 1;
	const hasNextPage = currentPage < totalPages;
	const pageStart = totalRecords > 0 ? ((currentPage - 1) * perPage) + 1 : 0;
	const pageEnd = Math.min(currentPage * perPage, totalRecords);

	const goToPage = (nextPage) => {
		const normalizedPage = Math.max(1, Math.min(totalPages, Number(nextPage || 1)));
		if (normalizedPage === currentPage || isLoading || isSubmitting) {
			return;
		}

		setLogsPayload((previous) => ({
			...previous,
			page: normalizedPage,
		}));
	};

	const openRedirectDialog = (item) => {
		const dialogCode = Number(item?.redirect_code || 301);
		setRedirectDialog({
			isOpen: true,
			itemId: Number(item?.id || 0),
			sourcePath: String(item?.path || item?.requested_url || ''),
			target: String(item?.redirect_target || '/'),
			code: [301, 302, 307].includes(dialogCode) ? dialogCode : 301,
		});
	};

	const closeRedirectDialog = () => {
		setRedirectDialog({
			isOpen: false,
			itemId: 0,
			sourcePath: '',
			target: '/',
			code: 301,
		});
	};

	const openDetailsDialog = (item) => {
		setDetailDialog({
			isOpen: true,
			item: item || null,
		});
	};

	const closeDetailsDialog = () => {
		setDetailDialog({
			isOpen: false,
			item: null,
		});
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

	const confirmDetailAction = (title, message, onProceed) => {
		openActionConfirmDialog(title, message, onProceed);
	};

	const runDetailStatusAction = (nextStatus, successMessage) => {
		const itemId = Number(detailDialog?.item?.id || 0);
		if (!itemId) {
			return;
		}

		runSingleAction(itemId, { status: nextStatus }, successMessage);
		closeDetailsDialog();
	};

	const runDetailDeleteAction = () => {
		const itemId = Number(detailDialog?.item?.id || 0);
		if (!itemId) {
			return;
		}

		runDeleteAction(itemId);
		closeDetailsDialog();
	};

	const openDetailRedirectDialog = () => {
		if (!detailDialog?.item) {
			return;
		}

		openRedirectDialog(detailDialog.item);
		closeDetailsDialog();
	};

	const requestDetailStatusAction = (nextStatus, successMessage, actionLabel) => {
		const itemPath = String(detailDialog?.item?.path || detailDialog?.item?.requested_url || 'this 404 URL');
		confirmDetailAction(
			__('Confirm Action', 'asneris-seo-toolkit'),
			sprintf(
				__('Do you want to %1$s for %2$s?', 'asneris-seo-toolkit'),
				actionLabel,
				itemPath
			),
			() => runDetailStatusAction(nextStatus, successMessage)
		);
	};

	const requestDetailDeleteAction = () => {
		const itemPath = String(detailDialog?.item?.path || detailDialog?.item?.requested_url || 'this 404 URL');
		confirmDetailAction(
			__('Confirm Delete', 'asneris-seo-toolkit'),
			sprintf(
				__('This will permanently delete %s. Continue?', 'asneris-seo-toolkit'),
				itemPath
			),
			runDetailDeleteAction
		);
	};

	const requestDetailRedirectAction = () => {
		const itemPath = String(detailDialog?.item?.path || detailDialog?.item?.requested_url || 'this 404 URL');
		confirmDetailAction(
			__('Confirm Redirect', 'asneris-seo-toolkit'),
			sprintf(
				__('Proceed to add a redirect for %s?', 'asneris-seo-toolkit'),
				itemPath
			),
			openDetailRedirectDialog
		);
	};

	const formatDetailValue = (value) => {
		if (value === null || value === undefined || value === '') {
			return '-';
		}

		if (typeof value === 'string') {
			if (value === '[]' || value === '{}') {
				return value;
			}
			if (value.startsWith('[') || value.startsWith('{')) {
				try {
					return JSON.stringify(JSON.parse(value), null, 2);
				} catch (error) {
					return value;
				}
			}
		}

		return String(value);
	};

	const formatPriorityDisplay = (value) => {
		const meta = getPriorityMeta(value);
		return `${ meta.label } (${ meta.value })`;
	};

	const normalizeRecommendationItem = (item) => {
		const text = String(item || '').trim();
		if (!text) {
			return '';
		}

		const redirectSuggestion = 'Suggested redirect is available. Validate target and publish redirect.';
		if (text.toLowerCase() === redirectSuggestion.toLowerCase()) {
			return `Medium : ${ redirectSuggestion }`;
		}

		return text;
	};

	const getRecommendationItems = (value) => {
		if (value === null || value === undefined || value === '') {
			return [];
		}

		if (Array.isArray(value)) {
			return value.map((item) => normalizeRecommendationItem(item)).filter(Boolean);
		}

		if (typeof value === 'string') {
			const trimmed = value.trim();
			if (!trimmed) {
				return [];
			}

			if (trimmed.startsWith('[')) {
				try {
					const parsed = JSON.parse(trimmed);
					if (Array.isArray(parsed)) {
						return parsed.map((item) => normalizeRecommendationItem(item)).filter(Boolean);
					}
				} catch (error) {
					// Fall through and treat as plain text.
				}
			}

			return [normalizeRecommendationItem(trimmed)];
		}

		return [normalizeRecommendationItem(value)];
	};

	const renderSeenDateTime = (value) => {
		const input = String(value || '').trim();
		if (!input) {
			return '-';
		}

		const normalized = input.replace('T', ' ');
		const [datePart, timePartRaw] = normalized.split(' ');
		const timePart = String(timePartRaw || '').slice(0, 8);

		if (!datePart) {
			return input;
		}

		return (
			<span style={ { display: 'inline-grid', lineHeight: 1.25 } }>
				<span>{ datePart }</span>
				<span>{ timePart || '-' }</span>
			</span>
		);
	};

	const formatDateTimeLabel = (value) => {
		const input = String(value || '').trim();
		if (!input) {
			return '-';
		}

		const parsed = new Date(input.replace(' ', 'T'));
		if (Number.isNaN(parsed.getTime())) {
			return input;
		}

		return `${ parsed.toLocaleDateString() } ${ parsed.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }`;
	};

	const saveRedirectDialog = () => {
		if (!redirectDialog.itemId) {
			return;
		}
		const trimmedTarget = String(redirectDialog.target || '').trim();
		if (!trimmedTarget) {
			setErrorMessage(__('Redirect target URL/path is required.', 'asneris-seo-toolkit'));
			return;
		}

		runSingleAction(
			redirectDialog.itemId,
			{ status: 'redirected', redirect_target: trimmedTarget, redirect_code: Number(redirectDialog.code || 301) },
			__('Redirect saved.', 'asneris-seo-toolkit')
		);
		closeRedirectDialog();
	};

	const todayValue = toInputDate(new Date());
	const isMonitoringEnabled = !!monitorSettings.enabled;
	const dateFromMin = filters.date_to ? shiftDate(filters.date_to, -MAX_DATE_RANGE_DAYS) : '';
	const dateFromMax = filters.date_to ? filters.date_to : todayValue;
	const dateToMin = filters.date_from || '';
	const dateToMaxRaw = filters.date_from ? shiftDate(filters.date_from, MAX_DATE_RANGE_DAYS) : todayValue;
	const dateToMaxDate = toDateValue(dateToMaxRaw);
	const todayDate = toDateValue(todayValue);
	const dateToMax = (dateToMaxDate && todayDate && dateToMaxDate < todayDate) ? dateToMaxRaw : todayValue;
	const compactSelectClass = 'ASNERISSEO-react-404-filter-multi-trigger ASNERISSEO-react-control-base ASNERISSEO-react-control-small ASNERISSEO-react-dropdown-trigger-small ASNERISSEO-react-404-filter-compact ASNERISSEO-react-select-small-width';
	const compactDropdownTriggerClass = 'ASNERISSEO-react-404-filter-multi-trigger ASNERISSEO-react-control-base ASNERISSEO-react-control-small ASNERISSEO-react-dropdown-trigger-small';

	return (
		<PanelScaffold title={ __('404 Monitor', 'asneris-seo-toolkit') } panelClass="ASNERISSEO-react-data-panel">
			<div className="ASNERISSEO-react-404-bulk-row ASNERISSEO-react-block">
				<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-muted">
					{ isMonitoringEnabled
						? __('Feature: ON. 404 capture ENABLED.', 'asneris-seo-toolkit')
						: __('Feature: OFF. 404 capture DISABLED.', 'asneris-seo-toolkit') }
				</p>
			</div>

			{ monitorSettings.log_limit_reached ? (
				<div className="ASNERISSEO-react-404-bulk-row ASNERISSEO-react-block" style={ { borderLeft: '4px solid #b32d2e', background: '#fff2f1' } }>
					<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-text-danger">
						{ sprintf(
							__('Storage limit reached (%1$d/%2$d). Delete existing 404 records to allow new captures.', 'asneris-seo-toolkit'),
							Number(monitorSettings.current_records || 0),
							Number(monitorSettings.max_records || 1000)
						) }
					</p>
				</div>
			) : null }

			<div className="ASNERISSEO-react-mobile-summary-toggle">
				<strong className="ASNERISSEO-react-mobile-summary-title">{ __('Summary', 'asneris-seo-toolkit') }</strong>
				<button
					type="button"
					className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-small"
					onClick={ () => setIsMobileSummaryOpen((current) => !current) }
					aria-expanded={ isMobileSummaryOpen }
				>
					{ isMobileSummaryOpen ? __('Collapse', 'asneris-seo-toolkit') : __('Expand', 'asneris-seo-toolkit') }
				</button>
			</div>

			<div className={ `ASNERISSEO-react-metrics-grid ASNERISSEO-react-grid-metrics ASNERISSEO-react-404-summary-grid ASNERISSEO-react-mobile-summary-target ASNERISSEO-react-block${ isMobileSummaryOpen ? '' : ' is-mobile-collapsed' }` }>
				<MetricCard label={ __('Total 404 URLs', 'asneris-seo-toolkit') } value={ stats.total_urls } />
				<MetricCard label={ __('Active', 'asneris-seo-toolkit') } value={ stats.active_count } />
				<MetricCard label={ __('Redirected', 'asneris-seo-toolkit') } value={ stats.redirected_count } />
				<MetricCard label={ __('Ignored', 'asneris-seo-toolkit') } value={ stats.ignored_count } />
				<MetricCard label={ __('Last 7 Days Hits', 'asneris-seo-toolkit') } value={ stats.last_7_days_hits } />
			</div>

			{ lastPerformance ? (
				<PerformanceTrackerCard
					title={ __('Run Performance', 'asneris-seo-toolkit') }
					statusLabel={ formatPerformanceStatus(lastPerformance?.status) }
					statusClassName={ toStatusChipClass(lastPerformance?.status) }
					advisoryMessage={ lastPerformance?.advisory?.recommendNotToRun
						? (lastPerformance?.advisory?.reason || __('Server headroom appears low. We recommend not running this analysis now.', 'asneris-seo-toolkit'))
						: '' }
					performance={ lastPerformance }
					modalTitle={ __('Performance Details', 'asneris-seo-toolkit') }
					className="ASNERISSEO-react-block"
				/>
			) : null }

			<div className="ASNERISSEO-react-404-view-tabs ASNERISSEO-react-tabs ASNERISSEO-react-tabs-strip ASNERISSEO-react-block" role="tablist" aria-label={ __('404 Monitor Tabs', 'asneris-seo-toolkit') }>
				<button
					type="button"
					role="tab"
					aria-selected={ activeMonitorView === TAB_RECOMMANDATION }
					className={ `ASNERISSEO-react-404-view-tab ASNERISSEO-react-tab${activeMonitorView === TAB_RECOMMANDATION ? ' is-active' : ''}` }
					onClick={ () => setActiveMonitorView(TAB_RECOMMANDATION) }
				>
					{ __('Recommendations', 'asneris-seo-toolkit') }
				</button>
				<button
					type="button"
					role="tab"
					aria-selected={ activeMonitorView === TAB_WHAT_HAPPENED }
					className={ `ASNERISSEO-react-404-view-tab ASNERISSEO-react-tab${activeMonitorView === TAB_WHAT_HAPPENED ? ' is-active' : ''}` }
					onClick={ () => setActiveMonitorView(TAB_WHAT_HAPPENED) }
				>
					{ __('Events', 'asneris-seo-toolkit') }
				</button>
			</div>

			<button
				type="button"
				className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-mobile-filter-toggle"
				onClick={ () => setIsMobileFilterOpen((current) => !current) }
				aria-expanded={ isMobileFilterOpen }
			>
				{ isMobileFilterOpen ? __('Hide Filters', 'asneris-seo-toolkit') : __('Show Filters', 'asneris-seo-toolkit') }
			</button>

			<div className={ `ASNERISSEO-react-404-filter-grid ASNERISSEO-react-mobile-filter-target ASNERISSEO-react-block${ isMobileFilterOpen ? '' : ' is-mobile-collapsed' }` }>
				{ activeMonitorView === TAB_RECOMMANDATION ? (
					<>
						<div className="ASNERISSEO-react-404-filter-field ASNERISSEO-react-field-small">
							<div className="ASNERISSEO-react-field-label">{ __('Priority', 'asneris-seo-toolkit') }</div>
							<FilterDropdown
								dropdownId="priority-filter"
								openDropdownId={ openDropdownId }
								setOpenDropdownId={ setOpenDropdownId }
								multi
								allValue={ activeMonitorView === TAB_RECOMMANDATION ? 'all_non_low' : 'all' }
								values={ selectedPriorityValues }
								options={ priorityOptions }
								onChange={ setPriorityFilterValue }
								wrapperClassName="ASNERISSEO-react-404-filter-multi-dropdown ASNERISSEO-react-dropdown-small"
								triggerClassName={ compactDropdownTriggerClass }
							/>
						</div>
						<div className="ASNERISSEO-react-404-filter-field ASNERISSEO-react-field-small">
							<div className="ASNERISSEO-react-field-label">{ __('Recommendation', 'asneris-seo-toolkit') }</div>
							<FilterDropdown
								dropdownId="recommendation-filter"
								openDropdownId={ openDropdownId }
								setOpenDropdownId={ setOpenDropdownId }
								multi
								allValue="all"
								values={ selectedRecommendationValues }
								options={ recommendationOptions }
								onChange={ setRecommendationFilterValue }
								wrapperClassName="ASNERISSEO-react-404-filter-multi-dropdown ASNERISSEO-react-dropdown-small"
								triggerClassName={ compactDropdownTriggerClass }
							/>
						</div>
						<div className="ASNERISSEO-react-404-filter-field ASNERISSEO-react-404-filter-action-right">
							<div className="ASNERISSEO-react-field-label">&nbsp;</div>
							<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary ASNERISSEO-react-button-small" disabled={ isLoading || isSubmitting } onClick={ applyCurrentFilters }>
								{ __('Refresh', 'asneris-seo-toolkit') }
							</button>
						</div>
					</>
				) : (
					<>
						<div className="ASNERISSEO-react-404-filter-field ASNERISSEO-react-field-small">
							<div className="ASNERISSEO-react-field-label">{ __('Status', 'asneris-seo-toolkit') }</div>
							<FilterDropdown
								dropdownId="status-filter"
								openDropdownId={ openDropdownId }
								setOpenDropdownId={ setOpenDropdownId }
								value={ filters.status }
								options={ statusOptions }
								onChange={ (nextValue) => setFilterValue('status', nextValue) }
								triggerClassName={ compactSelectClass }
							/>
						</div>
						<div className="ASNERISSEO-react-404-filter-field ASNERISSEO-react-field-small">
							<div className="ASNERISSEO-react-field-label">{ __('Sort By', 'asneris-seo-toolkit') }</div>
							<FilterDropdown
								dropdownId="sort-by-filter"
								openDropdownId={ openDropdownId }
								setOpenDropdownId={ setOpenDropdownId }
								value={ filters.sort_by }
								options={ sortByOptions }
								onChange={ (nextValue) => setFilterValue('sort_by', nextValue) }
								triggerClassName={ compactSelectClass }
							/>
						</div>
						<div className="ASNERISSEO-react-404-filter-field ASNERISSEO-react-field-small">
							<div className="ASNERISSEO-react-field-label">{ __('Sort Order', 'asneris-seo-toolkit') }</div>
							<FilterDropdown
								dropdownId="sort-dir-filter"
								openDropdownId={ openDropdownId }
								setOpenDropdownId={ setOpenDropdownId }
								value={ filters.sort_dir }
								options={ sortDirOptions }
								onChange={ (nextValue) => setFilterValue('sort_dir', nextValue) }
								triggerClassName={ compactSelectClass }
							/>
						</div>
						<div className="ASNERISSEO-react-404-filter-field ASNERISSEO-react-404-date-group">
							<div className="ASNERISSEO-react-field-label">{ __('Last Seen Date', 'asneris-seo-toolkit') }</div>
							<div className="ASNERISSEO-react-404-date-range">
								<label className="ASNERISSEO-react-field-label ASNERISSEO-react-404-date-field">
									<div className="ASNERISSEO-react-field-label">{ __('From', 'asneris-seo-toolkit') }</div>
									<input type="date" className="ASNERISSEO-react-input ASNERISSEO-react-input-small ASNERISSEO-react-404-date-input" value={ filters.date_from } min={ dateFromMin } max={ dateFromMax } onChange={ (event) => setFilterValue('date_from', event.target.value) } />
								</label>
								<label className="ASNERISSEO-react-field-label ASNERISSEO-react-404-date-field">
									<div className="ASNERISSEO-react-field-label">{ __('To', 'asneris-seo-toolkit') }</div>
									<input type="date" className="ASNERISSEO-react-input ASNERISSEO-react-input-small ASNERISSEO-react-404-date-input" value={ filters.date_to } min={ dateToMin } max={ dateToMax } onChange={ (event) => setFilterValue('date_to', event.target.value) } />
								</label>
							</div>
						</div>
						<div className="ASNERISSEO-react-404-filter-field ASNERISSEO-react-404-filter-search">
							<div className="ASNERISSEO-react-field-label">{ __('Broken Link', 'asneris-seo-toolkit') }</div>
							<input
								type="search"
								className="ASNERISSEO-react-input ASNERISSEO-react-input-small"
								placeholder={ __('Search broken URL...', 'asneris-seo-toolkit') }
								value={ filters.search }
								onChange={ (event) => setFilterValue('search', event.target.value) }
							/>
						</div>
						<div className="ASNERISSEO-react-404-filter-field ASNERISSEO-react-404-filter-action-right">
							<div className="ASNERISSEO-react-field-label">&nbsp;</div>
							<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary ASNERISSEO-react-button-small" disabled={ isLoading || isSubmitting } onClick={ applyCurrentFilters }>
								{ __('Refresh', 'asneris-seo-toolkit') }
							</button>
						</div>
					</>
				) }
			</div>

			{ activeMonitorView === TAB_WHAT_HAPPENED ? (
				<div className="ASNERISSEO-react-404-bulk-row ASNERISSEO-react-404-mobile-bulk-controls ASNERISSEO-react-block">
					<div className="ASNERISSEO-react-404-bulk-row-left">
						<FilterDropdown
							dropdownId="bulk-action-filter"
							openDropdownId={ openDropdownId }
							setOpenDropdownId={ setOpenDropdownId }
							value={ bulkAction }
							options={ bulkActionOptions }
							onChange={ setBulkAction }
							wrapperClassName="ASNERISSEO-react-select-small-width"
							triggerClassName={ compactSelectClass }
							disabled={ isSubmitting }
						/>
						<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary ASNERISSEO-react-button-small" disabled={ isSubmitting || selectedIds.length === 0 } onClick={ runBulkAction }>
							{ __('Apply to Records', 'asneris-seo-toolkit') }
						</button>
						<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-muted">
							{ __('Bulk actions apply to selected records on this filtered page (50 per page).', 'asneris-seo-toolkit') }
						</p>
						{ bulkAction === 'redirect' ? (
							<input
								type="url"
								className="regular-text ASNERISSEO-react-input ASNERISSEO-react-input-small"
								placeholder={ __('Redirect target URL/path', 'asneris-seo-toolkit') }
								value={ redirectTarget }
								onChange={ (event) => setRedirectTarget(event.target.value) }
							/>
						) : null }
					</div>
					<div className="ASNERISSEO-react-404-bulk-row-right">
						<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-small" disabled={ isSubmitting } onClick={ handleExport }>
							{ __('Export CSV', 'asneris-seo-toolkit') }
						</button>
					</div>
				</div>
			) : null }

			{ isLoading ? <p>{ __('Loading 404 logs...', 'asneris-seo-toolkit') }</p> : null }
			{ errorMessage ? <p className="ASNERISSEO-react-text-danger">{ errorMessage }</p> : null }

			{ logsPayload.items.length > 0 ? (
				<>
					<div className="ASNERISSEO-react-table-wrap">
						{ activeMonitorView === TAB_RECOMMANDATION ? (
						<table className="widefat striped ASNERISSEO-react-404-log-table">
							<thead>
								<tr>
									<th>{ __('Broken URL', 'asneris-seo-toolkit') }</th>
									<th>{ __('Priority', 'asneris-seo-toolkit') }</th>
									<th>{ __('Recommandation', 'asneris-seo-toolkit') }</th>
									<th>{ __('Last Seen', 'asneris-seo-toolkit') }</th>
									<th>{ __('Actions', 'asneris-seo-toolkit') }</th>
								</tr>
							</thead>
							<tbody>
								{ logsPayload.items.map((item) => {
									const rowKey = `rec-${ item.id }`;
									const isMobileExpanded = expandedMobileRowKey === rowKey;

									return (
									<tr key={ item.id } className={ isMobileExpanded ? 'is-mobile-expanded' : '' }>
										<td className="ASNERISSEO-react-404-card-toggle-cell" colSpan={ 5 }>
											<button
												type="button"
												className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-404-card-toggle"
												onClick={ () => setExpandedMobileRowKey((current) => (current === rowKey ? null : rowKey)) }
												aria-expanded={ isMobileExpanded }
											>
												<span className="ASNERISSEO-react-404-card-toggle-title"><code>{ item.path || item.requested_url }</code></span>
												<span className="ASNERISSEO-react-404-card-toggle-state">{ isMobileExpanded ? __('Collapse', 'asneris-seo-toolkit') : __('Expand', 'asneris-seo-toolkit') }</span>
											</button>
										</td>
										<td data-label={ __('Broken URL', 'asneris-seo-toolkit') }>
											<button
												type="button"
												className="button-link"
												onClick={ () => openDetailsDialog(item) }
											>
												<code>{ item.path || item.requested_url }</code>
											</button>
										</td>
										<td data-label={ __('Priority', 'asneris-seo-toolkit') }>{ formatPriorityDisplay(item.priority) }</td>
										<td data-label={ __('Recommandation', 'asneris-seo-toolkit') }>
											{ getRecommendationItems(item.recommandation).length > 0 ? (
												<ul style={ { margin: 0, paddingLeft: '18px', listStyleType: 'disc', listStylePosition: 'outside' } }>
													{ getRecommendationItems(item.recommandation).map((entry, index) => (
														<li key={ `${ item.id }-rec-${ index }` } style={ { listStyleType: 'disc' } }>{ entry }</li>
													)) }
												</ul>
											) : '-'}
										</td>
										<td data-label={ __('Last Seen', 'asneris-seo-toolkit') }>{ renderSeenDateTime(item.last_seen) }</td>
										<td data-label={ __('Actions', 'asneris-seo-toolkit') } className="ASNERISSEO-react-404-action-cell">
											<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" disabled={ isSubmitting } onClick={ () => openDetailsDialog(item) }>
												{ __('Details', 'asneris-seo-toolkit') }
											</button>
										</td>
									</tr>
									);
								}) }
							</tbody>
						</table>
						) : (
						<table className="widefat striped ASNERISSEO-react-404-log-table">
							<thead>
								<tr>
									<th>
										<input
											type="checkbox"
											checked={ logsPayload.items.length > 0 && selectedIds.length === logsPayload.items.length }
											onChange={ (event) => setSelectedIds(event.target.checked ? logsPayload.items.map((item) => item.id) : []) }
										/>
									</th>
									<th>{ __('Broken URL', 'asneris-seo-toolkit') }</th>
									<th>{ __('Method', 'asneris-seo-toolkit') }</th>
									<th>{ __('Hits', 'asneris-seo-toolkit') }</th>
									<th>{ __('Priority', 'asneris-seo-toolkit') }</th>
									<th>{ __('First Seen', 'asneris-seo-toolkit') }</th>
									<th>{ __('Last Seen', 'asneris-seo-toolkit') }</th>
									<th>{ __('Target Redirect', 'asneris-seo-toolkit') }</th>
									<th>{ __('Referrer', 'asneris-seo-toolkit') }</th>
									<th>{ __('Status', 'asneris-seo-toolkit') }</th>
									<th>{ __('Actions', 'asneris-seo-toolkit') }</th>
								</tr>
							</thead>
							<tbody>
								{ logsPayload.items.map((item) => {
									const rowKey = `what-${ item.id }`;
									const isMobileExpanded = expandedMobileRowKey === rowKey;

									return (
									<tr key={ item.id } className={ isMobileExpanded ? 'is-mobile-expanded' : '' }>
										<td className="ASNERISSEO-react-404-card-toggle-cell" colSpan={ 11 }>
											<button
												type="button"
												className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-404-card-toggle"
												onClick={ () => setExpandedMobileRowKey((current) => (current === rowKey ? null : rowKey)) }
												aria-expanded={ isMobileExpanded }
											>
												<span className="ASNERISSEO-react-404-card-toggle-title"><code>{ item.path || item.requested_url }</code></span>
												<span className="ASNERISSEO-react-404-card-toggle-state">{ isMobileExpanded ? __('Collapse', 'asneris-seo-toolkit') : __('Expand', 'asneris-seo-toolkit') }</span>
											</button>
										</td>
										<td data-label={ __('Select', 'asneris-seo-toolkit') }>
											<input type="checkbox" checked={ selectedIds.includes(item.id) } onChange={ () => toggleSelection(item.id) } />
										</td>
										<td data-label={ __('Broken URL', 'asneris-seo-toolkit') }>
											<button
												type="button"
												className="button-link"
												onClick={ () => openDetailsDialog(item) }
											>
												<code>{ item.path || item.requested_url }</code>
											</button>
										</td>
										<td data-label={ __('Method', 'asneris-seo-toolkit') }>{ item.method || 'GET' }</td>
										<td data-label={ __('Hits', 'asneris-seo-toolkit') }>{ item.hit_count || 0 }</td>
										<td data-label={ __('Priority', 'asneris-seo-toolkit') }>{ formatPriorityDisplay(item.priority) }</td>
										<td data-label={ __('First Seen', 'asneris-seo-toolkit') }>{ renderSeenDateTime(item.first_seen) }</td>
										<td data-label={ __('Last Seen', 'asneris-seo-toolkit') }>{ renderSeenDateTime(item.last_seen) }</td>
										<td data-label={ __('Target Redirect', 'asneris-seo-toolkit') }>{ item.redirect_target || '-' }</td>
										<td data-label={ __('Referrer', 'asneris-seo-toolkit') }>{ item.referrer ? <a href={ item.referrer } target="_blank" rel="noopener noreferrer">{ item.referrer }</a> : '-' }</td>
										<td data-label={ __('Status', 'asneris-seo-toolkit') }>{ item.status || 'active' }</td>
										<td data-label={ __('Actions', 'asneris-seo-toolkit') } className="ASNERISSEO-react-404-action-cell">
											<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" disabled={ isSubmitting } onClick={ () => openDetailsDialog(item) }>
												{ __('Details', 'asneris-seo-toolkit') }
											</button>
										</td>
									</tr>
									);
								}) }
							</tbody>
						</table>
						) }
					</div>

					<div className="ASNERISSEO-react-404-bulk-row ASNERISSEO-react-block" style={ { alignItems: 'center', justifyContent: 'space-between', gap: '12px' } }>
						<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-muted">
							{ totalRecords > 0
								? sprintf(
									__('Showing %1$d-%2$d of %3$d records', 'asneris-seo-toolkit'),
									pageStart,
									pageEnd,
									totalRecords
								)
								: __('No records to show.', 'asneris-seo-toolkit') }
						</p>
						<div className="ASNERISSEO-react-404-bulk-row-right" style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
							<button
								type="button"
								className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-small"
								onClick={ () => goToPage(currentPage - 1) }
								disabled={ !hasPreviousPage || isLoading || isSubmitting }
							>
								{ __('Previous', 'asneris-seo-toolkit') }
							</button>
							<span className="ASNERISSEO-react-muted">{ sprintf(__('Page %1$d of %2$d', 'asneris-seo-toolkit'), currentPage, totalPages) }</span>
							<button
								type="button"
								className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-small"
								onClick={ () => goToPage(currentPage + 1) }
								disabled={ !hasNextPage || isLoading || isSubmitting }
							>
								{ __('Next', 'asneris-seo-toolkit') }
							</button>
						</div>
					</div>
				</>
			) : (
				!isLoading ? <p>{ activeMonitorView === TAB_RECOMMANDATION ? __('No Recommandation Found', 'asneris-seo-toolkit') : __('No 404 logs found for current filters.', 'asneris-seo-toolkit') }</p> : null
			) }

			<div className={ `ASNERISSEO-modal-overlay${ redirectDialog.isOpen ? ' active' : '' }` }>
				<div className="ASNERISSEO-modal ASNERISSEO-modal-small ASNERISSEO-modal-redirect" role="dialog" aria-modal="true" aria-label={ __('Add Redirect', 'asneris-seo-toolkit') }>
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
							<span>{ __('Add Redirect', 'asneris-seo-toolkit') }</span>
						</h3>
						<button type="button" className="ASNERISSEO-modal-close ASNERISSEO-modal-close-light" onClick={ closeRedirectDialog } disabled={ isSubmitting }>
							&times;
						</button>
					</div>
					<div className="ASNERISSEO-modal-content">
						<div className="ASNERISSEO-react-404-redirect-row">
							<div className="ASNERISSEO-react-field-label">{ __('Source URL', 'asneris-seo-toolkit') }</div>
							<input type="text" className="ASNERISSEO-react-input" value={ redirectDialog.sourcePath } readOnly />
						</div>
						<div className="ASNERISSEO-react-404-redirect-row">
							<div className="ASNERISSEO-react-field-label">{ __('Target URL/Path', 'asneris-seo-toolkit') }</div>
							<input
								type="url"
								className="ASNERISSEO-react-input"
								value={ redirectDialog.target }
								placeholder={ __('Enter redirect target URL/path', 'asneris-seo-toolkit') }
								onChange={ (event) => setRedirectDialog((previous) => ({ ...previous, target: event.target.value })) }
							/>
						</div>
						<div className="ASNERISSEO-react-404-redirect-row">
							<div className="ASNERISSEO-react-field-label">{ __('Redirect Code', 'asneris-seo-toolkit') }</div>
							<FilterDropdown
								dropdownId="redirect-code-filter"
								openDropdownId={ openDropdownId }
								setOpenDropdownId={ setOpenDropdownId }
								value={ String(redirectDialog.code || 301) }
								options={ redirectCodeOptions }
								onChange={ (nextValue) => setRedirectDialog((previous) => ({ ...previous, code: Number(nextValue) || 301 })) }
								triggerClassName={ compactSelectClass }
								disabled={ isSubmitting }
							/>
						</div>
					</div>
					<div className="ASNERISSEO-modal-footer">
						<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" onClick={ closeRedirectDialog } disabled={ isSubmitting }>
							{ __('Cancel', 'asneris-seo-toolkit') }
						</button>
						<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary" onClick={ saveRedirectDialog } disabled={ isSubmitting }>
							{ __('Save Redirect', 'asneris-seo-toolkit') }
						</button>
					</div>
				</div>
			</div>

			<div className={ `ASNERISSEO-modal-overlay${ detailDialog.isOpen ? ' active' : '' }` }>
				<div className="ASNERISSEO-modal ASNERISSEO-modal-large ASNERISSEO-react-404-detail-modal" role="dialog" aria-modal="true" aria-label={ __('404 Record Details', 'asneris-seo-toolkit') }>
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
							<span>{ __('404 Record Details', 'asneris-seo-toolkit') }</span>
						</h3>
						<button type="button" className="ASNERISSEO-modal-close ASNERISSEO-modal-close-light" onClick={ closeDetailsDialog }>
							&times;
						</button>
					</div>
					<div className="ASNERISSEO-modal-content">
						{ detailDialog.item ? (
							<div className="ASNERISSEO-react-table-wrap ASNERISSEO-react-404-detail-table-wrap">
								<table className="ASNERISSEO-react-status-table ASNERISSEO-react-404-detail-table">
									<tbody>
										{ [
											['id', detailDialog.item.id],
											['url', detailDialog.item.url || detailDialog.item.requested_url],
											['path', detailDialog.item.path],
											['method', detailDialog.item.method],
											['referrer', detailDialog.item.referrer],
											['user_agent', detailDialog.item.user_agent],
											['ip_hash', detailDialog.item.ip_hash],
											['hit_count', detailDialog.item.hit_count],
											['first_seen', detailDialog.item.first_seen],
											['last_seen', detailDialog.item.last_seen],
											['last_analysed', detailDialog.item.last_analysed],
											['cron_status', formatCronStatusLabel(monitorSettings?.analysis_cron_status)],
											['next_expected_run', formatDateTimeLabel(monitorSettings?.analysis_next_run_gmt)],
											['status', detailDialog.item.status],
											['last_20_hits_json', detailDialog.item.last_20_hits_json],
											['priority', formatPriorityDisplay(detailDialog.item.priority)],
											['recommandation', detailDialog.item.recommandation],
											['redirect_target', detailDialog.item.redirect_target],
											['resolved_at', detailDialog.item.resolved_at],
										].map(([label, value]) => (
											<tr key={ label }>
												<th scope="row">{ label }</th>
												<td>
													<pre className="ASNERISSEO-react-404-detail-value">{ formatDetailValue(value) }</pre>
												</td>
											</tr>
										)) }
									</tbody>
								</table>
							</div>
						) : null }
					</div>
					<div className="ASNERISSEO-modal-footer ASNERISSEO-react-404-detail-footer">
						<button
							type="button"
							className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
							onClick={ () => requestDetailStatusAction('ignored', __('URL marked as ignored.', 'asneris-seo-toolkit'), __('ignore this URL', 'asneris-seo-toolkit')) }
							disabled={ isSubmitting || !detailDialog.item }
						>
							{ __('Ignore', 'asneris-seo-toolkit') }
						</button>
						<button
							type="button"
							className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
							onClick={ () => requestDetailStatusAction('active', __('URL activated.', 'asneris-seo-toolkit'), __('activate this URL', 'asneris-seo-toolkit')) }
							disabled={ isSubmitting || !detailDialog.item }
						>
							{ __('Activate', 'asneris-seo-toolkit') }
						</button>
						<button
							type="button"
							className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
							onClick={ () => requestDetailStatusAction('fixed', __('URL marked as fixed.', 'asneris-seo-toolkit'), __('mark this URL as fixed', 'asneris-seo-toolkit')) }
							disabled={ isSubmitting || !detailDialog.item }
						>
							{ __('Fixed', 'asneris-seo-toolkit') }
						</button>
						<button
							type="button"
							className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
							onClick={ requestDetailRedirectAction }
							disabled={ isSubmitting || !detailDialog.item }
						>
							{ __('Add Redirect', 'asneris-seo-toolkit') }
						</button>
						<button
							type="button"
							className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
							onClick={ requestDetailDeleteAction }
							disabled={ isSubmitting || !detailDialog.item }
						>
							{ __('Delete', 'asneris-seo-toolkit') }
						</button>
						<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary" onClick={ closeDetailsDialog }>
							{ __('Close', 'asneris-seo-toolkit') }
						</button>
					</div>
				</div>
			</div>

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
						<button type="button" className="ASNERISSEO-modal-close ASNERISSEO-modal-close-light" onClick={ closeActionConfirmDialog } disabled={ isSubmitting }>
							&times;
						</button>
					</div>
					<div className="ASNERISSEO-modal-content">
						<p>{ actionConfirmDialog.message || __('Do you want to proceed?', 'asneris-seo-toolkit') }</p>
					</div>
					<div className="ASNERISSEO-modal-footer">
						<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" onClick={ closeActionConfirmDialog } disabled={ isSubmitting }>
							{ __('Cancel', 'asneris-seo-toolkit') }
						</button>
						<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary" onClick={ proceedWithActionConfirmDialog } disabled={ isSubmitting }>
							{ __('Proceed', 'asneris-seo-toolkit') }
						</button>
					</div>
				</div>
			</div>
		</PanelScaffold>
	);
};

export default Monitor404Panel;
