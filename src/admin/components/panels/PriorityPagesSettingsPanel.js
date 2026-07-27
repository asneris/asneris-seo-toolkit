import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import RestSettingsPanel from '../common/RestSettingsPanel';
import InlineHelpDetails from '../common/InlineHelpDetails';
import fetchJson from '../common/fetchJson';

const toPriorityIds = (value) => {
	if (!Array.isArray(value)) {
		return [];
	}

	return Array.from(new Set(value
		.map((item) => Number(item))
		.filter((item) => Number.isFinite(item) && item > 0)))
		.slice(0, 30);
};

const PriorityPagesSettingsPanel = ({ restUrl, restNonce, onStatus, prioritySourceRestUrl }) => {
	const [priorityOptions, setPriorityOptions] = useState([]);
	const [cleanupSummary, setCleanupSummary] = useState('');
	const [selectedSourceIds, setSelectedSourceIds] = useState([]);
	const [selectedPriorityIds, setSelectedPriorityIds] = useState([]);
	const [availableSearch, setAvailableSearch] = useState('');
	const [availableFilter, setAvailableFilter] = useState('all');

	useEffect(() => {
		if (!prioritySourceRestUrl) {
			setPriorityOptions([]);
			return;
		}

		const requestUrl = new URL(prioritySourceRestUrl, window.location.origin);
		requestUrl.searchParams.set('perPage', '200');
		requestUrl.searchParams.set('page', '1');
		requestUrl.searchParams.set('postType', 'all');
		requestUrl.searchParams.set('postStatus', 'all');

		fetchJson(requestUrl.toString(), {
			method: 'GET',
			headers: {
				'X-WP-Nonce': restNonce || '',
			},
		})
			.then((payload) => {
				const items = Array.isArray(payload?.items) ? payload.items : [];
				setPriorityOptions(items.map((item) => ({
					value: Number(item?.postId) || 0,
					title: item?.title || __('(Untitled)', 'asneris-seo-toolkit'),
					kind: String(item?.postType || 'post').toUpperCase(),
					label: `${ item?.title || __('(Untitled)', 'asneris-seo-toolkit') } (${ String(item?.postType || 'post').toUpperCase() })`,
				})).filter((option) => option.value > 0));
			})
			.catch(() => {
				setPriorityOptions([]);
			});
	}, [prioritySourceRestUrl, restNonce]);

	return (
		<RestSettingsPanel
			restUrl={ restUrl }
			restNonce={ restNonce }
			onStatus={ onStatus }
			title={ __('Priority Pages', 'asneris-seo-toolkit') }
			description={ __('Manage the pages/posts that should always appear in the Priority list (max 30).', 'asneris-seo-toolkit') }
			loadMessage={ __('Loading Priority Pages settings...', 'asneris-seo-toolkit') }
			saveMessage={ __('Priority Pages saved successfully.', 'asneris-seo-toolkit') }
			loadErrorMessage={ __('Unable to load Priority Pages settings.', 'asneris-seo-toolkit') }
			saveErrorMessage={ __('Failed to save Priority Pages settings.', 'asneris-seo-toolkit') }
			initialForm={ {
				priority_page_ids: [],
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
				priority_page_ids: toPriorityIds(payload.priority_page_ids),
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
				priority_page_ids: toPriorityIds(saved.priority_page_ids),
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
				priority_page_ids: toPriorityIds(form.priority_page_ids),
			}) }
			onAfterSave={ (payload) => {
				const removedIds = Array.isArray(payload?.cleanup?.removedPriorityIds)
					? payload.cleanup.removedPriorityIds
					: [];
				const deletedRows = Number(payload?.cleanup?.deletedRows || 0);

				if (removedIds.length < 1) {
					setCleanupSummary('');
					return;
				}

				setCleanupSummary(
					`${ __('Cleanup complete:', 'asneris-seo-toolkit') } ${ removedIds.length } ${ __('page(s) removed from Priority and', 'asneris-seo-toolkit') } ${ deletedRows } ${ __('diagnostics row(s) deleted.', 'asneris-seo-toolkit') }`
				);
			} }
				renderFields={ (form, updateField, panelActions) => {

				const selectedPrioritySet = new Set((form.priority_page_ids || []).map((id) => Number(id)));
				const sourceOptions = priorityOptions.filter((option) => !selectedPrioritySet.has(Number(option.value)));
				const selectedOptionMap = new Map(priorityOptions.map((option) => [Number(option.value), option]));
				const selectedPriorityOptions = (form.priority_page_ids || [])
					.map((id) => selectedOptionMap.get(Number(id)))
					.filter(Boolean);
				const currentPriorityCount = (form.priority_page_ids || []).length;
				const hasSearchValue = availableSearch.trim().length > 0;

				const filteredSourceOptions = sourceOptions.filter((option) => {
					const searchTerm = availableSearch.trim().toLowerCase();
					const matchesSearch = !searchTerm
						|| String(option.title || '').toLowerCase().includes(searchTerm)
						|| String(option.label || '').toLowerCase().includes(searchTerm);

					const matchesType = availableFilter === 'all'
						|| (availableFilter === 'page' && option.kind === 'PAGE')
						|| (availableFilter === 'post' && option.kind === 'POST');

					return matchesSearch && matchesType;
				});

				const toggleSourceSelection = (id) => {
					const numericId = Number(id);
					setSelectedSourceIds((prev) => (
						prev.includes(numericId)
							? prev.filter((item) => item !== numericId)
							: [ ...prev, numericId ]
					));
				};

				const togglePrioritySelection = (id) => {
					const numericId = Number(id);
					setSelectedPriorityIds((prev) => (
						prev.includes(numericId)
							? prev.filter((item) => item !== numericId)
							: [ ...prev, numericId ]
					));
				};

				const addSelectedToPriority = () => {
					if (selectedSourceIds.length < 1) {
						return;
					}

					setCleanupSummary('');
					const next = toPriorityIds([ ...(form.priority_page_ids || []), ...selectedSourceIds ]);
					updateField('priority_page_ids', next);
					setSelectedSourceIds([]);
				};

				const removeSelectedFromPriority = () => {
					if (selectedPriorityIds.length < 1) {
						return;
					}

					setCleanupSummary('');
					const removeSet = new Set(selectedPriorityIds.map((id) => Number(id)));
					const next = (form.priority_page_ids || []).filter((id) => !removeSet.has(Number(id)));
					updateField('priority_page_ids', toPriorityIds(next));
					setSelectedPriorityIds([]);
				};

				const removeSingleFromPriority = (id) => {
					setCleanupSummary('');
					const next = (form.priority_page_ids || []).filter((item) => Number(item) !== Number(id));
					updateField('priority_page_ids', toPriorityIds(next));
					setSelectedPriorityIds((prev) => prev.filter((item) => Number(item) !== Number(id)));
				};

				const clearAllPriority = () => {
					if ((form.priority_page_ids || []).length < 1) {
						return;
					}

					setCleanupSummary('');
					updateField('priority_page_ids', []);
					setSelectedPriorityIds([]);
				};

				return (
					<>
						<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mb-10">
							<p className="ASNERISSEO-react-note-box-title">{ __('Priority Page Settings', 'asneris-seo-toolkit') }</p>
							<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-muted">{ __('Select, order, and maintain your Priority Pages list.', 'asneris-seo-toolkit') }</p>
						</div>

						<div className="ASNERISSEO-react-priority-workspace">
							<div className="ASNERISSEO-react-priority-workspace-head">
								<div>
									<p className="ASNERISSEO-react-priority-workspace-title">{ __('Page Selection', 'asneris-seo-toolkit') }</p>
									<p className="ASNERISSEO-react-priority-workspace-subtitle">{ `${ currentPriorityCount } ${ __('of 30 pages selected', 'asneris-seo-toolkit') }` }</p>
								</div>
								<div className="ASNERISSEO-react-priority-workspace-head-actions">
									<button
										type="button"
										className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary"
										onClick={ panelActions?.saveForm }
										disabled={ !!panelActions?.isSaving || !!panelActions?.isLoading }
									>
										{ panelActions?.isSaving ? __('Saving...', 'asneris-seo-toolkit') : __('Save Changes', 'asneris-seo-toolkit') }
									</button>
								</div>
							</div>

							<div className="ASNERISSEO-react-priority-transfer">
								<div className="ASNERISSEO-react-priority-transfer-block">
									<div className="ASNERISSEO-react-priority-transfer-head">
										<div className="ASNERISSEO-react-field-label">{ __('📄 Available Pages', 'asneris-seo-toolkit') }</div>
										<span className="ASNERISSEO-react-priority-transfer-count">{ `${ priorityOptions.length } ${ __('Available', 'asneris-seo-toolkit') }` }</span>
									</div>
									<div className="ASNERISSEO-react-priority-filter-row">
										<label className="ASNERISSEO-react-priority-filter-control">
											<span>{ __('Search pages...', 'asneris-seo-toolkit') }</span>
											<div className="ASNERISSEO-react-priority-search-wrap is-inline-action">
												<span className="dashicons dashicons-search ASNERISSEO-react-priority-search-icon" aria-hidden="true" />
												<input
													type="text"
													className="ASNERISSEO-react-input"
													value={ availableSearch }
													onChange={ (event) => setAvailableSearch(event.target.value) }
													placeholder={ __('Search pages...', 'asneris-seo-toolkit') }
												/>
												<button
													type="button"
													className="ASNERISSEO-react-priority-search-clear"
													onClick={ () => setAvailableSearch('') }
													disabled={ !hasSearchValue }
													aria-label={ __('Clear search', 'asneris-seo-toolkit') }
												>
													×
												</button>
											</div>
										</label>
										<label className="ASNERISSEO-react-priority-filter-control is-small">
											<span>{ __('Filter', 'asneris-seo-toolkit') }</span>
											<select className="ASNERISSEO-react-select" value={ availableFilter } onChange={ (event) => setAvailableFilter(event.target.value) }>
												<option value="all">{ __('All', 'asneris-seo-toolkit') }</option>
												<option value="page">{ __('Pages', 'asneris-seo-toolkit') }</option>
												<option value="post">{ __('Posts', 'asneris-seo-toolkit') }</option>
											</select>
										</label>
									</div>
									<div className="ASNERISSEO-react-priority-list-wrap" role="listbox" aria-multiselectable="true">
										{ filteredSourceOptions.map((option) => {
											const checked = selectedSourceIds.includes(Number(option.value));

											return (
												<label key={ option.value } className="ASNERISSEO-react-priority-row">
													<span className="ASNERISSEO-react-priority-drag-spacer" aria-hidden="true" />
													<input type="checkbox" checked={ checked } onChange={ () => toggleSourceSelection(option.value) } />
													<span className="ASNERISSEO-react-priority-row-icon" aria-hidden="true">📄</span>
													<span className="ASNERISSEO-react-priority-row-title">{ option.title }</span>
													<span className="ASNERISSEO-react-priority-kind-tag">{ option.kind }</span>
												</label>
											);
										}) }
										{ filteredSourceOptions.length < 1 ? <p className="ASNERISSEO-react-priority-empty">{ __('No pages match your search/filter.', 'asneris-seo-toolkit') }</p> : null }
									</div>
									<p className="ASNERISSEO-react-helper-text ASNERISSEO-react-mb-0">
										{ selectedSourceIds.length > 0
											? `${ selectedSourceIds.length } ${ __('selected', 'asneris-seo-toolkit') }`
											: __('Select one or more pages to add.', 'asneris-seo-toolkit') }
									</p>
									<button
										type="button"
										className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-priority-panel-action"
										onClick={ addSelectedToPriority }
										title={ __('Add selected pages to Priority', 'asneris-seo-toolkit') }
									>
										{ __('+ Add Selected', 'asneris-seo-toolkit') }
									</button>
								</div>

								<div className="ASNERISSEO-react-priority-transfer-block">
									<div className="ASNERISSEO-react-priority-transfer-head">
										<div className="ASNERISSEO-react-field-label">{ __('⭐ Priority Pages (max 30)', 'asneris-seo-toolkit') }</div>
										<div className="ASNERISSEO-react-priority-right-head-meta">
											<button
												type="button"
												className="button ASNERISSEO-react-button ASNERISSEO-react-button-destructive-secondary ASNERISSEO-react-priority-header-action"
												onClick={ removeSelectedFromPriority }
												title={ __('Remove selected pages from Priority', 'asneris-seo-toolkit') }
											>
												{ __('− Remove Selected', 'asneris-seo-toolkit') }
											</button>
											<button type="button" className="ASNERISSEO-react-priority-clear-all" onClick={ clearAllPriority }>
												<span className="dashicons dashicons-trash" aria-hidden="true" />
												{ __('Clear All', 'asneris-seo-toolkit') }
											</button>
										</div>
									</div>
									<div className="ASNERISSEO-react-priority-list-wrap" role="listbox" aria-multiselectable="true">
										{ selectedPriorityOptions.map((option) => {
											const checked = selectedPriorityIds.includes(Number(option.value));

											return (
												<div key={ option.value } className="ASNERISSEO-react-priority-row is-priority">
													<label className="ASNERISSEO-react-priority-row-main">
														<span className="ASNERISSEO-react-priority-drag-handle" aria-hidden="true">☰</span>
														<input type="checkbox" checked={ checked } onChange={ () => togglePrioritySelection(option.value) } />
														<span className="ASNERISSEO-react-priority-row-icon" aria-hidden="true">📄</span>
														<span className="ASNERISSEO-react-priority-row-title">{ option.title }</span>
														<span className="ASNERISSEO-react-priority-kind-tag">{ option.kind }</span>
													</label>
													<button
														type="button"
														className="ASNERISSEO-react-priority-row-remove"
														onClick={ () => removeSingleFromPriority(option.value) }
														title={ __('Remove this page from Priority', 'asneris-seo-toolkit') }
														aria-label={ __('Remove this page from Priority', 'asneris-seo-toolkit') }
													>
														<span className="dashicons dashicons-trash" aria-hidden="true" />
													</button>
												</div>
											);
										}) }
										{ selectedPriorityOptions.length > 0 && selectedPriorityOptions.length < 7 ? (
											<div className="ASNERISSEO-react-priority-no-more-state">
												<p>{ __('No more selected pages', 'asneris-seo-toolkit') }</p>
												<p>{ __('Drag pages here to reorder.', 'asneris-seo-toolkit') }</p>
											</div>
										) : null }
										{ selectedPriorityOptions.length < 1 ? <p className="ASNERISSEO-react-priority-empty">{ __('No priority pages selected.', 'asneris-seo-toolkit') }</p> : null }
									</div>
									<div className="ASNERISSEO-react-priority-panel-note">
										<p className="ASNERISSEO-react-helper-text ASNERISSEO-react-mb-0">{ __('Use drag handles to reorder.', 'asneris-seo-toolkit') }</p>
										<p className="ASNERISSEO-react-helper-text ASNERISSEO-react-mb-0">
											{ selectedPriorityIds.length > 0
												? `${ selectedPriorityIds.length } ${ __('selected', 'asneris-seo-toolkit') }`
												: __('These pages always appear in diagnostics recommendations.', 'asneris-seo-toolkit') }
										</p>
									</div>
								</div>
							</div>
						</div>

						{ cleanupSummary ? (
							<div className="ASNERISSEO-react-note-box is-success">
								<p className="ASNERISSEO-react-note-box-title is-success">{ __('Priority cleanup summary', 'asneris-seo-toolkit') }</p>
								<p className="ASNERISSEO-react-mb-0">{ cleanupSummary }</p>
							</div>
						) : null }

						<div className="ASNERISSEO-react-priority-help-row">
							<InlineHelpDetails
								title={ __('Help: Priority Pages', 'asneris-seo-toolkit') }
								items={ [
									__('Select up to 30 pages/posts that should always be treated as Priority Pages.', 'asneris-seo-toolkit'),
									__('These pages appear in the Recommendation view in Page Diagnostics.', 'asneris-seo-toolkit'),
									__('Use Add and Remove controls to move selected items between lists.', 'asneris-seo-toolkit'),
								] }
								note={ __('If no items are selected, the plugin uses automatic fallback ranking.', 'asneris-seo-toolkit') }
							/>
							<a href="https://asneris.com/wp-toolkits/asneris-wordpress-seo-toolkit-priority-pages/" target="_blank" rel="noopener noreferrer" className="ASNERISSEO-react-priority-learn-link">
								{ __('Learn more about Priority Pages', 'asneris-seo-toolkit') }
							</a>
						</div>

						<div className="ASNERISSEO-react-priority-info-card" role="note" aria-label={ __('Priority information', 'asneris-seo-toolkit') }>
							<p className="ASNERISSEO-react-helper-text ASNERISSEO-react-mb-0">{ __('ℹ Priority pages always appear in Page Diagnostics recommendations.', 'asneris-seo-toolkit') }</p>
						</div>
					</>
				);
			} }
			saveButtonLabel={ __('Save Changes', 'asneris-seo-toolkit') }
			showSaveButton={ false }
		/>
	);
};

export default PriorityPagesSettingsPanel;
