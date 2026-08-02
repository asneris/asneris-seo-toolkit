import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PanelScaffold from '../common/PanelScaffold';
import fetchJson from '../common/fetchJson';
import StatusTable from '../common/StatusTable';
import InlineHelpDetails from '../common/InlineHelpDetails';

const defaultSettings = {
	enabled: true,
	include_pages: true,
	include_posts: true,
	include_custom_post_types: true,
	max_recommended_urls: 50,
};

const AiSearchabilityPanel = ({ restUrl, restNonce, onStatus }) => {
	const [content, setContent] = useState('');
	const [validation, setValidation] = useState({ status: 'warning', checks: {}, warnings: [], errors: [] });
	const [isLoading, setIsLoading] = useState(false);
	const [isGenerating, setIsGenerating] = useState(false);
	const [isPublishing, setIsPublishing] = useState(false);
	const [isApproving, setIsApproving] = useState(false);
	const [errorMessage, setErrorMessage] = useState('');
	const [isDetected, setIsDetected] = useState(false);
	const [liveContent, setLiveContent] = useState('');
	const [lastMessage, setLastMessage] = useState('');
	const [status, setStatus] = useState('not_generated');
	const [summary, setSummary] = useState({ included_count: 0, sections: [] });
	const [publishedVersion, setPublishedVersion] = useState(0);
	const [settings, setSettings] = useState(defaultSettings);
	const mountedRef = useRef(true);
	const activeRequestRef = useRef(null);
	const contentRef = useRef('');
	const liveUrl = `${ window.location.origin || '' }/llms.txt`;

	const loadDraftFromServer = ({ showLoading = false } = {}) => {
		if (!restUrl) {
			return;
		}

		if (showLoading) {
			setIsLoading(true);
		}
		setErrorMessage('');

		const controller = new AbortController();
		activeRequestRef.current?.abort();
		activeRequestRef.current = controller;

		fetchJson(restUrl, {
			method: 'GET',
			headers: { 'X-WP-Nonce': restNonce || '' },
			signal: controller.signal,
		})
			.then((payload) => {
				if (!mountedRef.current) {
					return;
				}
				const nextContent = '';
				setContent(nextContent);
				contentRef.current = nextContent;
				setLiveContent(payload?.liveContent ?? '');
				setValidation(payload?.validation || { status: 'warning', checks: {}, warnings: [], errors: [] });
				setIsDetected(Boolean(payload?.detected));
				setLastMessage(payload?.message || '');
				setStatus(payload?.status || 'not_generated');
				setSummary(payload?.summary || { included_count: 0, sections: [] });
				setPublishedVersion(payload?.publishedVersion || 0);
				setSettings(payload?.settings || defaultSettings);
			})
			.catch((error) => {
				if (error?.name === 'AbortError') {
					return;
				}
				const message = error.message || __('Unable to load llms.txt.', 'asneris-seo-toolkit');
				if (!mountedRef.current) {
					return;
				}
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => {
				if (activeRequestRef.current === controller) {
					activeRequestRef.current = null;
				}
				if (mountedRef.current && showLoading) {
					setIsLoading(false);
				}
			});
	};

	useEffect(() => {
		mountedRef.current = true;
		if (!restUrl) {
			return undefined;
		}

		loadDraftFromServer({ showLoading: true });

		return () => {
			mountedRef.current = false;
			activeRequestRef.current?.abort();
			activeRequestRef.current = null;
		};
	}, [restNonce, restUrl]);

	const runAction = (action, body = {}) => {
		if (!restUrl || (action === 'publish' && isPublishing) || (action === 'generate' && isGenerating) || (action === 'approve_publish' && isApproving)) {
			return;
		}

		if (action === 'generate') {
			setIsGenerating(true);
		} else if (action === 'publish' || action === 'approve_publish') {
			setIsPublishing(true);
			setIsApproving(true);
		}
		setErrorMessage('');

		const controller = new AbortController();
		activeRequestRef.current?.abort();
		activeRequestRef.current = controller;

		const latestContent = contentRef.current;

		fetchJson(restUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': restNonce || '',
			},
			body: JSON.stringify({
				action,
				content: latestContent,
				draft_content: latestContent,
				draftContent: latestContent,
				settings,
				...body,
			}),
			signal: controller.signal,
		})
			.then((payload) => {
				if (!mountedRef.current) {
					return;
				}
				const nextContent = payload?.draftContent ?? payload?.content ?? latestContent;
				setContent(nextContent);
				contentRef.current = nextContent;
				setLiveContent(payload?.liveContent ?? liveContent);
				setValidation(payload?.validation || validation);
				setIsDetected(Boolean(payload?.detected));
				setLastMessage(payload?.message || '');
				setStatus(payload?.status || status);
				setSummary(payload?.summary || summary);
				setPublishedVersion(payload?.publishedVersion || publishedVersion);
				if (payload?.settings) {
					setSettings(payload.settings);
				}
				onStatus?.({ tone: 'success', text: payload?.message || __('llms.txt updated successfully.', 'asneris-seo-toolkit') });
			})
			.catch((error) => {
				if (error?.name === 'AbortError') {
					return;
				}
				const message = error.message || __('Failed to process llms.txt.', 'asneris-seo-toolkit');
				if (!mountedRef.current) {
					return;
				}
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => {
				if (activeRequestRef.current === controller) {
					activeRequestRef.current = null;
				}
				if (!mountedRef.current) {
					return;
				}
				if (action === 'generate') {
					setIsGenerating(false);
				} else if (action === 'publish' || action === 'approve_publish') {
					setIsPublishing(false);
					setIsApproving(false);
				}
			});
	};

	const checks = Object.values(validation?.checks || {}).map((check) => ({
		label: check?.label || __('Check', 'asneris-seo-toolkit'),
		status: check?.status || 'warning',
		message: check?.message || '-',
	}));
	const validationStatus = validation?.status || 'warning';
	const validationClass = validationStatus === 'success'
		? 'ASNERISSEO-react-text-success'
		: validationStatus === 'warning'
			? 'ASNERISSEO-react-text-warning'
			: 'ASNERISSEO-react-text-danger';
	const workflowLabel = status === 'published'
		? __('Published', 'asneris-seo-toolkit')
		: status === 'update_available'
			? __('Update available', 'asneris-seo-toolkit')
			: status === 'needs_review'
				? __('Needs review', 'asneris-seo-toolkit')
				: status === 'draft_generated'
					? __('Draft generated', 'asneris-seo-toolkit')
					: __('Not generated', 'asneris-seo-toolkit');
	const summarySections = Array.isArray(summary?.sections) ? summary.sections : [];
	const includedCount = Number(summary?.included_count || 0);
	const draftReadOnlyMessage = __('Review and edit the generated draft, then approve and publish when ready.', 'asneris-seo-toolkit');

	const populateFromLiveData = () => {
		const nextValue = liveContent || '';
		setContent(nextValue);
		contentRef.current = nextValue;
		setLastMessage(__('Loaded the current live llms.txt content into the draft editor.', 'asneris-seo-toolkit'));
		onStatus?.({ tone: 'info', text: __('Loaded live content into the draft editor.', 'asneris-seo-toolkit') });
	};

	return (
		<PanelScaffold
			title={ __('AI Searchability', 'asneris-seo-toolkit') }
			panelClass="ASNERISSEO-react-data-panel"
		>
			<InlineHelpDetails
				title={ __('Help: AI Searchability', 'asneris-seo-toolkit') }
				items={ [
					__('llms.txt helps AI systems find and understand your most important public pages.', 'asneris-seo-toolkit'),
					__('Generate a starter version or copy the current live content, review it, then approve and publish it to the site root.', 'asneris-seo-toolkit'),
					__('The published file will be available at the site root once approval is complete.', 'asneris-seo-toolkit'),
				] }
				note={ __('This file is separate from robots.txt and is intended to guide AI crawlers toward your best public content.', 'asneris-seo-toolkit') }
			/>
			{ isLoading ? <p>{ __('Loading llms.txt...', 'asneris-seo-toolkit') }</p> : null }
			{ errorMessage ? <p className="ASNERISSEO-react-text-danger">{ errorMessage }</p> : null }
			<div className="ASNERISSEO-react-note-box">
				<strong className={ validationClass }>{ __('Validation Status:', 'asneris-seo-toolkit') } { validationStatus }</strong>
				<p>{ isDetected ? __('An llms.txt file is currently detected on the site root.', 'asneris-seo-toolkit') : __('No llms.txt file was found yet. Generate one to get started.', 'asneris-seo-toolkit') }</p>
				{ lastMessage ? <p className="ASNERISSEO-react-mb-0">{ lastMessage }</p> : null }
			</div>

			<div className="ASNERISSEO-react-note-box ASNERISSEO-react-indent-top">
				<strong>{ __('Draft content (editable)', 'asneris-seo-toolkit') }</strong>
				<p className="ASNERISSEO-react-mb-10 ASNERISSEO-react-text-muted">{ draftReadOnlyMessage }</p>
				<textarea
					className="large-text code ASNERISSEO-react-mb-10 ASNERISSEO-react-input ASNERISSEO-react-input-wide"
					rows="16"
					value={ content }
					onChange={ (e) => {
						const nextValue = e.target.value;
						setContent(nextValue);
						contentRef.current = nextValue;
					} }
				/>
			</div>
			<div className="ASNERISSEO-react-note-box ASNERISSEO-react-indent-top">
				<strong>{ __('Live content (published)', 'asneris-seo-toolkit') }</strong>
				<p className="ASNERISSEO-react-mb-10 ASNERISSEO-react-text-muted">
					{ __('This is the currently published llms.txt content from the site root.', 'asneris-seo-toolkit') }
				</p>
				{ liveContent ? (
					<pre className="ASNERISSEO-react-input ASNERISSEO-react-input-wide" style={ { whiteSpace: 'pre-wrap', padding: '12px', margin: 0, maxHeight: '280px', overflow: 'auto' } }>{ liveContent }</pre>
				) : (
					<p className="ASNERISSEO-react-mb-0 ASNERISSEO-react-text-muted">{ __('No published live file is available yet.', 'asneris-seo-toolkit') }</p>
				) }
				{ liveUrl ? (
					<p className="ASNERISSEO-react-mb-0"><a href={ liveUrl } target="_blank" rel="noreferrer">{ __('Open live llms.txt', 'asneris-seo-toolkit') }</a></p>
				) : null }
			</div>
			<div className="ASNERISSEO-react-btn-row">
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary" onClick={ () => runAction('generate', { force: true, settings }) } disabled={ isGenerating || isLoading }>
					{ isGenerating ? __('Generating...', 'asneris-seo-toolkit') : __('Generate Draft', 'asneris-seo-toolkit') }
				</button>
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" onClick={ populateFromLiveData } disabled={ isLoading || !liveContent }>
					{ __('Copy from Live Data', 'asneris-seo-toolkit') }
				</button>
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" onClick={ () => runAction('approve_publish', { approved: true, settings }) } disabled={ isPublishing || isLoading || isApproving }>
					{ isPublishing ? __('Approving...', 'asneris-seo-toolkit') : __('Approve & Publish', 'asneris-seo-toolkit') }
				</button>
			</div>
			<p className="ASNERISSEO-react-mb-10 ASNERISSEO-react-text-muted">
				{ __('Generate a draft or copy the current live content, then review and publish it to the site root.', 'asneris-seo-toolkit') }
			</p>

			<div className="ASNERISSEO-react-note-box ASNERISSEO-react-indent-top">
				<strong>{ __('Workflow Status', 'asneris-seo-toolkit') }:</strong> { workflowLabel }
				<p className="ASNERISSEO-react-mb-0">{ lastMessage || __('Generate a draft to begin the review workflow.', 'asneris-seo-toolkit') }</p>
				{ publishedVersion > 0 ? <p className="ASNERISSEO-react-mb-0">{ __('Published version', 'asneris-seo-toolkit') }: { publishedVersion }</p> : null }
				{ includedCount > 0 ? <p className="ASNERISSEO-react-mb-0">{ __('Suggested pages included', 'asneris-seo-toolkit') }: { includedCount }</p> : null }
				{ summarySections.length > 0 ? (
					<ul className="ASNERISSEO-react-list">
						{ summarySections.map((section) => <li key={ section }>{ section }</li>) }
					</ul>
				) : null }
			</div>

			{ checks.length > 0 ? (
				<StatusTable
					columns={ [
						{ key: 'check', label: __('Check', 'asneris-seo-toolkit'), width: '30%' },
						{ key: 'status', label: __('Status', 'asneris-seo-toolkit'), width: '18%', align: 'center' },
						{ key: 'message', label: __('Message', 'asneris-seo-toolkit'), width: '52%' },
					] }
					rows={ checks.map((check, index) => ({
						key: `llms-check-${ index }`,
						cells: [check.label, check.status, check.message],
					})) }
				/>
			) : null }

			{ Array.isArray(validation?.errors) && validation.errors.length > 0 ? (
				<div className="ASNERISSEO-react-note-box ASNERISSEO-react-indent-top is-danger">
					<strong className="ASNERISSEO-react-note-box-title is-danger">{ __('Validation Errors', 'asneris-seo-toolkit') }</strong>
					<ul className="ASNERISSEO-react-list is-danger">
						{ validation.errors.map((item, index) => (
							<li key={ `llms-error-${ index }` }>{ item }</li>
						)) }
					</ul>
				</div>
			) : null }

			{ Array.isArray(validation?.warnings) && validation.warnings.length > 0 ? (
				<div className="ASNERISSEO-react-note-box ASNERISSEO-react-indent-top is-warning">
					<strong className="ASNERISSEO-react-note-box-title is-warning">{ __('Validation Warnings', 'asneris-seo-toolkit') }</strong>
					<ul className="ASNERISSEO-react-list is-warning">
						{ validation.warnings.map((item, index) => (
							<li key={ `llms-warning-${ index }` }>{ item }</li>
						)) }
					</ul>
				</div>
			) : null }
		</PanelScaffold>
	);
};

export default AiSearchabilityPanel;
