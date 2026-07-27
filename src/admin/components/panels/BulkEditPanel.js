import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PanelScaffold from '../common/PanelScaffold';
import fetchJson from '../common/fetchJson';
import InlineHelpDetails from '../common/InlineHelpDetails';

const normalizeVariableSyntax = (value) =>
	String(value || '').replace(/\{%\s*([a-z_]+)\s*%\}/gi, '{$1}');

const resolveInlineVariables = (value, context = {}) => {
	let output = normalizeVariableSyntax(value);

	Object.entries(context).forEach(([key, rawValue]) => {
		const tokenRegex = new RegExp(`\\{${ key }\\}`, 'gi');
		output = output.replace(tokenRegex, String(rawValue || ''));
	});

	return output.replace(/\s+/g, ' ').trim();
};

const getTodayIsoDate = () => new Date().toISOString().split('T')[0];

const BulkEditPanel = ({ contentRestUrl, saveRestUrl, restNonce, onStatus }) => {
	const previewLogoUrl = String(window.asnerisseoAdminDashboardData?.logoUrl || '').trim();
	const [postType, setPostType] = useState('post');
	const [indexing, setIndexing] = useState('all');
	const [searchInput, setSearchInput] = useState('');
	const [searchQuery, setSearchQuery] = useState('');
	const [currentPage, setCurrentPage] = useState(1);
	const [postTypes, setPostTypes] = useState([]);
	const [items, setItems] = useState([]);
	const [total, setTotal] = useState(0);
	const [pagination, setPagination] = useState({});
	const [selected, setSelected] = useState({});
	const [isLoading, setIsLoading] = useState(false);
	const [isSaving, setIsSaving] = useState(false);
	const [savingPostId, setSavingPostId] = useState(null);
	const [expandedCardPostId, setExpandedCardPostId] = useState(null);
	const [isMobileFilterOpen, setIsMobileFilterOpen] = useState(false);
	const [errorMessage, setErrorMessage] = useState('');
	const [previewItems, setPreviewItems] = useState([]);
	const latestLoadRequestRef = useRef(0);

	const loadContent = () => {
		if (!contentRestUrl) {
			return;
		}

		const requestId = latestLoadRequestRef.current + 1;
		latestLoadRequestRef.current = requestId;

		setIsLoading(true);
		setErrorMessage('');
		const query = new URLSearchParams({
			postType,
			indexing,
			search: searchQuery,
			page: String(currentPage),
			perPage: '10',
		}).toString();

		fetchJson(`${ contentRestUrl }?${ query }`, {
			method: 'GET',
			headers: { 'X-WP-Nonce': restNonce || '' },
		})
			.then((payload) => {
				if (requestId !== latestLoadRequestRef.current) {
					return;
				}

				setPostTypes(Array.isArray(payload?.filters?.postTypes) ? payload.filters.postTypes : []);
				setItems(Array.isArray(payload?.items) ? payload.items : []);
				setTotal(Number(payload?.total || 0));
				setPagination(payload?.pagination || {});
				setSelected({});
			})
			.catch((error) => {
				if (requestId !== latestLoadRequestRef.current) {
					return;
				}

				const message = error.message || __('Unable to load bulk edit items.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => {
				if (requestId === latestLoadRequestRef.current) {
					setIsLoading(false);
				}
			});
	};

	useEffect(() => {
		const timerId = setTimeout(() => {
			setSearchQuery(searchInput.trim());
			setCurrentPage(1);
		}, 250);

		return () => clearTimeout(timerId);
	}, [searchInput]);

	useEffect(() => {
		loadContent();
	}, [contentRestUrl, postType, indexing, restNonce, searchQuery, currentPage]);

	useEffect(() => {
		if (!items.length) {
			setExpandedCardPostId(null);
			return;
		}

		const hasExpandedItem = items.some((item) => item.postId === expandedCardPostId);
		if (!hasExpandedItem && expandedCardPostId !== null) {
			setExpandedCardPostId(null);
		}
	}, [items, expandedCardPostId]);

	const hasPrev = Boolean(pagination?.hasPrev);
	const hasNext = Boolean(pagination?.hasNext);
	const totalPages = Number(pagination?.totalPages || 1);
	const pageLabel = Number(pagination?.page || currentPage);

	const toggleSelect = (postId, checked) => {
		setSelected((prev) => ({ ...prev, [postId]: checked }));
	};

	const updateField = (postId, key, value) => {
		setItems((prev) => prev.map((item) => (item.postId === postId ? { ...item, [key]: value } : item)));
	};

	const applyBulk = (changes) => {
		setItems((prev) => prev.map((item) => (selected[item.postId] ? { ...item, ...changes } : item)));
	};

	const saveSelected = () => {
		if (!saveRestUrl || isSaving) {
			return;
		}

		const updates = items
			.filter((item) => selected[item.postId])
			.map((item) => ({
				postId: item.postId,
				seoTitle: item.seoTitle || '',
				seoDescription: item.seoDescription || '',
				robotsIndex: item.robotsIndex || 'index',
			}));

		if (updates.length === 0) {
			onStatus?.({ tone: 'warning', text: __('Select at least one row before saving.', 'asneris-seo-toolkit') });
			return;
		}

		setIsSaving(true);
		setErrorMessage('');

		fetchJson(saveRestUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': restNonce || '',
			},
			body: JSON.stringify({ updates }),
		})
			.then((payload) => {
				onStatus?.({ tone: 'success', text: payload?.message || __('Bulk changes saved successfully.', 'asneris-seo-toolkit') });
				loadContent();
			})
			.catch((error) => {
				const message = error.message || __('Bulk save failed.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setIsSaving(false));
	};

	const saveSingle = (item) => {
		if (!saveRestUrl || isSaving || savingPostId) {
			return;
		}

		const update = {
			postId: item.postId,
			seoTitle: item.seoTitle || '',
			seoDescription: item.seoDescription || '',
			robotsIndex: item.robotsIndex || 'index',
		};

		setSavingPostId(item.postId);
		setErrorMessage('');

		fetchJson(saveRestUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': restNonce || '',
			},
			body: JSON.stringify({ updates: [update] }),
		})
			.then((payload) => {
				onStatus?.({ tone: 'success', text: payload?.message || __('Row saved successfully.', 'asneris-seo-toolkit') });
				loadContent();
			})
			.catch((error) => {
				const message = error.message || __('Save failed for this row.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setSavingPostId(null));
	};

	const closePreview = () => setPreviewItems([]);
	const getPreviewContext = (item) => ({
		title: String(item?.title || '').trim(),
		site: String(window.asnerisseoData?.siteName || '').trim(),
		separator: String(window.asnerisseoData?.titleSeparator || '|'),
		excerpt: String(item?.excerpt || item?.seoDescription || '').trim(),
		date: String(item?.date || '').split('T')[0] || getTodayIsoDate(),
		author: String(item?.author || '').trim(),
		term: String(item?.term || item?.category || '').trim(),
	});
	const getPreviewTitle = (item) => {
		if (!String(item?.seoTitle || '').trim() && String(item?.titleTemplatePreview || '').trim()) {
			return String(item.titleTemplatePreview).trim();
		}
		const resolved = resolveInlineVariables(item?.seoTitle || '', getPreviewContext(item));
		return (resolved || item?.title || __('(Untitled)', 'asneris-seo-toolkit')).trim();
	};
	const getPreviewDescription = (item) => {
		if (!String(item?.seoDescription || '').trim() && String(item?.descriptionTemplatePreview || '').trim()) {
			return String(item.descriptionTemplatePreview).trim();
		}
		const text = resolveInlineVariables(item?.seoDescription || '', getPreviewContext(item));
		if (text) {
			return text;
		}
		return __('No custom meta description set. Search engines may generate a snippet from page content.', 'asneris-seo-toolkit');
	};

	const getTemplateBadges = (item) => {
		const usesTitleTemplate = Boolean(item?.isUsingDefaultTitleTemplate);
		const usesDescriptionTemplate = Boolean(item?.isUsingDefaultDescriptionTemplate);
		return { usesTitleTemplate, usesDescriptionTemplate };
	};

	const getRobotsStatus = (item) => {
		const robotsValue = String(item?.robotsIndex || 'index').toLowerCase();
		if (robotsValue === 'noindex') {
			return {
				text: __('Page is blocked from Search Engine', 'asneris-seo-toolkit'),
				className: 'is-blocked',
			};
		}

		return {
			text: __('Indexability On', 'asneris-seo-toolkit'),
			className: 'is-open',
		};
	};

	const viewSelectedPreviews = () => {
		const selectedItems = items.filter((item) => selected[item.postId]);
		if (selectedItems.length === 0) {
			onStatus?.({ tone: 'warning', text: __('Select at least one row to preview.', 'asneris-seo-toolkit') });
			return;
		}

		setPreviewItems(selectedItems);
	};

	const getCharCount = (value) => Array.from(String(value || '')).length;

	const toggleMobileCard = (postId) => {
		setExpandedCardPostId((current) => (current === postId ? null : postId));
	};

	return (
		<PanelScaffold
			title={ __('Bulk SEO Editor', 'asneris-seo-toolkit') }
			panelClass="ASNERISSEO-react-data-panel"
		>
			<InlineHelpDetails
				title={ __('Help: Bulk SEO Editor', 'asneris-seo-toolkit') }
				items={ [
					__('Filter by type/indexing and use search to narrow down large content sets.', 'asneris-seo-toolkit'),
					__('Use bulk actions for quick robots/title/description updates, then save selected rows.', 'asneris-seo-toolkit'),
					__('Use View / View Selected to preview how snippets can appear in search results.', 'asneris-seo-toolkit'),
				] }
				note={ __('Character counters help keep titles and descriptions within practical snippet lengths.', 'asneris-seo-toolkit') }
			/>
			<button
				type="button"
				className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-mobile-filter-toggle"
				onClick={ () => setIsMobileFilterOpen((current) => !current) }
				aria-expanded={ isMobileFilterOpen }
			>
				{ isMobileFilterOpen ? __('Hide Filters', 'asneris-seo-toolkit') : __('Show Filters', 'asneris-seo-toolkit') }
			</button>
			<div className={ `ASNERISSEO-react-filter-row ASNERISSEO-react-bulk-filter-row ASNERISSEO-react-mobile-filter-target ASNERISSEO-react-block${ isMobileFilterOpen ? '' : ' is-mobile-collapsed' }` }>
				<label className="ASNERISSEO-react-flex-1 ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Search by title', 'asneris-seo-toolkit') }</div>
					<input
						type="search"
						className="regular-text ASNERISSEO-react-input"
						placeholder={ __('Type to search content...', 'asneris-seo-toolkit') }
						value={ searchInput }
						onChange={ (e) => setSearchInput(e.target.value) }
					/>
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Content Type', 'asneris-seo-toolkit') }</div>
					<select className="regular-text ASNERISSEO-react-select" value={ postType } onChange={ (e) => {
						setPostType(e.target.value);
						setCurrentPage(1);
					} }>
						{ postTypes.map((pt) => <option key={ pt.value } value={ pt.value }>{ pt.label }</option>) }
					</select>
				</label>
				<label className="ASNERISSEO-react-field-label">
					<div className="ASNERISSEO-react-field-label">{ __('Indexing', 'asneris-seo-toolkit') }</div>
					<select className="regular-text ASNERISSEO-react-select" value={ indexing } onChange={ (e) => {
						setIndexing(e.target.value);
						setCurrentPage(1);
					} }>
						<option value="all">{ __('All', 'asneris-seo-toolkit') }</option>
						<option value="indexed">{ __('Indexed', 'asneris-seo-toolkit') }</option>
						<option value="noindex">{ __('NoIndex', 'asneris-seo-toolkit') }</option>
					</select>
				</label>
			</div>

			<div className="ASNERISSEO-react-btn-row ASNERISSEO-react-bulk-pagination-row ASNERISSEO-react-mb-10">
				<span>{ __('Items', 'asneris-seo-toolkit') }: { total }</span>
				<button
					type="button"
					className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
					onClick={ () => setCurrentPage((value) => Math.max(1, value - 1)) }
					disabled={ isLoading || !hasPrev }
				>
					{ __('Previous', 'asneris-seo-toolkit') }
				</button>
				<span>{ __('Page', 'asneris-seo-toolkit') } { pageLabel } { __('of', 'asneris-seo-toolkit') } { totalPages }</span>
				<button
					type="button"
					className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
					onClick={ () => setCurrentPage((value) => value + 1) }
					disabled={ isLoading || !hasNext }
				>
					{ __('Next', 'asneris-seo-toolkit') }
				</button>
			</div>

			<div className="ASNERISSEO-react-action-row ASNERISSEO-react-bulk-action-row ASNERISSEO-react-block">
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-bulk-set-feature" onClick={ () => applyBulk({ robotsIndex: 'index' }) }>{ __('Set Index', 'asneris-seo-toolkit') }</button>
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-bulk-set-feature" onClick={ () => applyBulk({ robotsIndex: 'noindex' }) }>{ __('Set NoIndex', 'asneris-seo-toolkit') }</button>
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-bulk-set-feature" onClick={ () => applyBulk({ seoTitle: '' }) }>{ __('Clear Titles', 'asneris-seo-toolkit') }</button>
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-bulk-set-feature" onClick={ () => applyBulk({ seoDescription: '' }) }>{ __('Clear Descriptions', 'asneris-seo-toolkit') }</button>
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-button-accent-orange" onClick={ viewSelectedPreviews }>{ __('View Selected', 'asneris-seo-toolkit') }</button>
			</div>

			{ isLoading ? <p>{ __('Loading bulk items...', 'asneris-seo-toolkit') }</p> : null }
			{ errorMessage ? <p className="ASNERISSEO-react-text-danger">{ errorMessage }</p> : null }

			{ items.length > 0 ? (
				<div className="ASNERISSEO-react-table-wrap ASNERISSEO-react-bulk-table-wrap">
					<table id="ASNERISSEO-bulk-edit-table" className="widefat striped ASNERISSEO-react-bulk-table">
						<colgroup>
							<col className="col-checkbox" />
							<col className="col-title" />
							<col className="col-seo-title" />
							<col className="col-description" />
							<col className="col-robots" />
							<col className="col-view" />
						</colgroup>
						<thead>
							<tr>
								<th>
									<input type="checkbox" onChange={ (e) => {
										const checked = e.target.checked;
										const next = {};
										items.forEach((item) => {
											next[item.postId] = checked;
										});
										setSelected(next);
									} } />
								</th>
								<th>{ __('Title', 'asneris-seo-toolkit') }</th>
								<th>{ __('SEO Title', 'asneris-seo-toolkit') }</th>
								<th>{ __('Meta Description', 'asneris-seo-toolkit') }</th>
								<th>{ __('Robots', 'asneris-seo-toolkit') }</th>
								<th>{ __('View', 'asneris-seo-toolkit') }</th>
							</tr>
						</thead>
						<tbody>
							{ items.map((item) => {
								const isCardExpanded = expandedCardPostId === item.postId;
								return (
								<tr key={ item.postId } className={ isCardExpanded ? 'is-mobile-expanded' : '' }>
									<td className="ASNERISSEO-react-bulk-card-toggle-cell" colSpan="6">
										<button
											type="button"
											className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary ASNERISSEO-react-bulk-card-toggle"
											onClick={ () => toggleMobileCard(item.postId) }
											aria-expanded={ isCardExpanded }
										>
											<span className="ASNERISSEO-react-bulk-card-toggle-title">{ item.title || __('(Untitled)', 'asneris-seo-toolkit') }</span>
											<span className="ASNERISSEO-react-bulk-card-toggle-state">{ isCardExpanded ? __('Collapse', 'asneris-seo-toolkit') : __('Expand', 'asneris-seo-toolkit') }</span>
										</button>
									</td>
									<td className="ASNERISSEO-react-bulk-select-cell" data-label={ __('Select', 'asneris-seo-toolkit') }><input type="checkbox" checked={ !!selected[item.postId] } onChange={ (e) => toggleSelect(item.postId, e.target.checked) } /></td>
									<td data-label={ __('Title', 'asneris-seo-toolkit') }><a href={ item.url } target="_blank" rel="noopener noreferrer">{ item.title || __('(Untitled)', 'asneris-seo-toolkit') }</a></td>
									<td data-label={ __('SEO Title', 'asneris-seo-toolkit') }>
										<textarea
											className="large-text ASNERISSEO-react-input"
											rows="3"
											maxLength={ 80 }
											value={ item.seoTitle || '' }
											onChange={ (e) => updateField(item.postId, 'seoTitle', e.target.value) }
										/>
										<div className="ASNERISSEO-react-char-count">
											{ getCharCount(item.seoTitle) } / 80
										</div>
									</td>
									<td data-label={ __('Meta Description', 'asneris-seo-toolkit') }>
										<textarea
											className="large-text ASNERISSEO-react-input"
											rows="4"
											maxLength={ 200 }
											value={ item.seoDescription || '' }
											onChange={ (e) => updateField(item.postId, 'seoDescription', e.target.value) }
										/>
										<div className="ASNERISSEO-react-char-count">
											{ getCharCount(item.seoDescription) } / 200
										</div>
									</td>
									<td data-label={ __('Robots', 'asneris-seo-toolkit') }>
										<select className="ASNERISSEO-react-select ASNERISSEO-react-bulk-robots-select" value={ item.robotsIndex || 'index' } onChange={ (e) => updateField(item.postId, 'robotsIndex', e.target.value) }>
											<option value="index">index</option>
											<option value="noindex">noindex</option>
										</select>
									</td>
									<td data-label={ __('View', 'asneris-seo-toolkit') }>
										<button
											type="button"
											className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary"
											onClick={ () => setPreviewItems([item]) }
											disabled={ isSaving || Boolean(savingPostId) }
										>
											{ __('View', 'asneris-seo-toolkit') }
										</button>
										<button
											type="button"
											className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary ASNERISSEO-react-bulk-row-save"
											onClick={ () => saveSingle(item) }
											disabled={ isSaving || Boolean(savingPostId) }
										>
											{ savingPostId === item.postId ? __('Saving...', 'asneris-seo-toolkit') : __('Save', 'asneris-seo-toolkit') }
										</button>
									</td>
								</tr>
									);
								}) }
						</tbody>
					</table>
				</div>
			) : null }

			{ previewItems.length > 0 ? (
				<div className="ASNERISSEO-react-preview-backdrop" role="presentation" onClick={ closePreview }>
					<div className="ASNERISSEO-react-preview-dialog" role="dialog" aria-modal="true" aria-label={ __('Search preview', 'asneris-seo-toolkit') } onClick={ (e) => e.stopPropagation() }>
						<div className="ASNERISSEO-react-preview-header">
							<h3 className="ASNERISSEO-heading-h3 ASNERISSEO-react-preview-title-heading">
								{ previewLogoUrl ? (
									<img
										src={ previewLogoUrl }
										alt={ __('Asneris SEO Toolkit', 'asneris-seo-toolkit') }
										className="ASNERISSEO-react-preview-title-logo"
									/>
								) : (
									<span className="ASNERISSEO-react-preview-title-fallback" aria-hidden="true">A</span>
								) }
								<span>{ __('Search Engine Preview', 'asneris-seo-toolkit') } ({ previewItems.length })</span>
							</h3>
							<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" onClick={ closePreview }>{ __('Close', 'asneris-seo-toolkit') }</button>
						</div>
						<div className="ASNERISSEO-react-preview-list">
							{ previewItems.map((previewItem) => {
								const robotsStatus = getRobotsStatus(previewItem);
								const templateBadges = getTemplateBadges(previewItem);
								return (
									<div key={ previewItem.postId } className="ASNERISSEO-react-preview-snippet">
										{ templateBadges.usesTitleTemplate || templateBadges.usesDescriptionTemplate ? (
											<div className="ASNERISSEO-react-preview-source-row">
												{ templateBadges.usesTitleTemplate ? (
													<span className="ASNERISSEO-react-preview-source-badge">{ __('Using default template (title)', 'asneris-seo-toolkit') }</span>
												) : null }
												{ templateBadges.usesDescriptionTemplate ? (
													<span className="ASNERISSEO-react-preview-source-badge">{ __('Using default template (description)', 'asneris-seo-toolkit') }</span>
												) : null }
											</div>
										) : null }
										<div className="ASNERISSEO-react-preview-url">{ previewItem.url || '-' }</div>
										<div className="ASNERISSEO-react-preview-title">{ getPreviewTitle(previewItem) }</div>
										<div className="ASNERISSEO-react-preview-description">{ getPreviewDescription(previewItem) }</div>
										<div className={ `ASNERISSEO-react-preview-meta ${ robotsStatus.className }` }>
											{ robotsStatus.text }
										</div>
									</div>
								);
							}) }
						</div>
					</div>
				</div>
			) : null }

			<div className="ASNERISSEO-react-mt-12 ASNERISSEO-react-bulk-save-all-wrap">
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary" onClick={ saveSelected } disabled={ isSaving || isLoading }>
					{ isSaving ? __('Saving...', 'asneris-seo-toolkit') : __('Save Selected Changes', 'asneris-seo-toolkit') }
				</button>
			</div>
		</PanelScaffold>
	);
};

export default BulkEditPanel;
