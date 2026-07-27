import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PanelScaffold from '../common/PanelScaffold';
import fetchJson from '../common/fetchJson';
import StatusTable from '../common/StatusTable';
import InlineHelpDetails from '../common/InlineHelpDetails';

const RobotsPanel = ({ restUrl, restNonce, onStatus, normalizeChecks, formatStatusText, robotsSafeDefaults = [] }) => {
	const [content, setContent] = useState('');
	const [validation, setValidation] = useState({ status: 'warning', checks: {}, warnings: [], errors: [] });
	const [isLoading, setIsLoading] = useState(false);
	const [isSaving, setIsSaving] = useState(false);
	const [errorMessage, setErrorMessage] = useState('');
	const liveRobotsUrl = `${ window.location.origin || '' }/robots.txt`;

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
				setContent(payload?.content || '');
				setValidation(payload?.validation || { status: 'warning', checks: {}, warnings: [], errors: [] });
			})
			.catch((error) => {
				const message = error.message || __('Unable to load robots.txt.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setIsLoading(false));
	}, [restNonce, restUrl]);

	const saveRobots = () => {
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
			body: JSON.stringify({ content }),
		})
			.then((payload) => {
				setValidation(payload?.validation || validation);
				onStatus?.({ tone: 'success', text: payload?.message || __('robots.txt saved successfully.', 'asneris-seo-toolkit') });
			})
			.catch((error) => {
				const message = error.message || __('Failed to save robots.txt.', 'asneris-seo-toolkit');
				setErrorMessage(message);
				onStatus?.({ tone: 'error', text: message });
			})
			.finally(() => setIsSaving(false));
	};

	const checks = normalizeChecks(validation?.checks);
	const validationStatus = validation?.status || '';
	const validationClass = validationStatus === 'success'
		? 'ASNERISSEO-react-text-success'
		: validationStatus === 'warning'
			? 'ASNERISSEO-react-text-warning'
			: 'ASNERISSEO-react-text-danger';
	const rows = checks.map((check, idx) => ({
		key: `check-${ idx }`,
		cells: [check?.label || '-', check?.status || '-', check?.message || '-'],
	}));

	return (
		<PanelScaffold
			title={ __('Robots.txt', 'asneris-seo-toolkit') }
			panelClass="ASNERISSEO-react-data-panel"
		>
			<InlineHelpDetails
				title={ __('Help: Robots.txt', 'asneris-seo-toolkit') }
				items={ [
					__('Robots.txt controls crawling hints, not page security or access control.', 'asneris-seo-toolkit'),
					__('Keep a Sitemap line so engines can discover your sitemap quickly.', 'asneris-seo-toolkit'),
					__('Avoid broad blocks like Disallow: / on production sites.', 'asneris-seo-toolkit'),
				] }
				note={ __('Always validate syntax after edits to prevent accidental crawling issues.', 'asneris-seo-toolkit') }
			/>
			{ isLoading ? <p>{ __('Loading robots.txt...', 'asneris-seo-toolkit') }</p> : null }
			{ errorMessage ? <p className="ASNERISSEO-react-text-danger">{ errorMessage }</p> : null }
			<div className="ASNERISSEO-react-note-box">
				<strong className={ validationClass }>{ __('Validation Status:', 'asneris-seo-toolkit') } { formatStatusText(validationStatus) }</strong>
				<p>{ __('Controls which URLs search engines are allowed to crawl.', 'asneris-seo-toolkit') }</p>
			</div>

			<textarea className="large-text code ASNERISSEO-react-mb-10 ASNERISSEO-react-input ASNERISSEO-react-input-wide" rows="14" value={ content } onChange={ (e) => setContent(e.target.value) } />
			<div className="ASNERISSEO-react-btn-row">
				<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-primary" onClick={ saveRobots } disabled={ isSaving || isLoading }>{ isSaving ? __('Saving...', 'asneris-seo-toolkit') : __('Save robots.txt', 'asneris-seo-toolkit') }</button>
				<a className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" href={ liveRobotsUrl } target="_blank" rel="noopener noreferrer">
					{ __('View Live File', 'asneris-seo-toolkit') }
				</a>
			</div>

			{ checks.length > 0 ? (
				<StatusTable
					columns={ [
						{ key: 'check', label: __('Check', 'asneris-seo-toolkit'), width: '30%' },
						{ key: 'status', label: __('Status', 'asneris-seo-toolkit'), width: '18%', align: 'center' },
						{ key: 'message', label: __('Message', 'asneris-seo-toolkit'), width: '52%' },
					] }
					rows={ rows }
				/>
			) : null }

			{ Array.isArray(validation?.errors) && validation.errors.length > 0 ? (
				<div className="ASNERISSEO-react-note-box ASNERISSEO-react-indent-top is-danger">
					<strong className="ASNERISSEO-react-note-box-title is-danger">{ __('Validator Errors', 'asneris-seo-toolkit') }</strong>
					<ul className="ASNERISSEO-react-list is-danger">
						{ validation.errors.map((item, index) => (
							<li key={ `robots-error-${ index }` }>{ item }</li>
						)) }
					</ul>
				</div>
			) : null }

			{ Array.isArray(validation?.warnings) && validation.warnings.length > 0 ? (
				<div className="ASNERISSEO-react-note-box ASNERISSEO-react-indent-top is-warning">
					<strong className="ASNERISSEO-react-note-box-title is-warning">{ __('Validator Warnings', 'asneris-seo-toolkit') }</strong>
					<ul className="ASNERISSEO-react-list is-warning">
						{ validation.warnings.map((item, index) => (
							<li key={ `robots-warning-${ index }` }>{ item }</li>
						)) }
					</ul>
				</div>
			) : null }

			<div className="ASNERISSEO-react-note-box ASNERISSEO-react-indent-top">
				<h3 className="ASNERISSEO-heading-h3 ASNERISSEO-react-note-box-title ASNERISSEO-react-mb-8">{ __('Recommended Safe Defaults', 'asneris-seo-toolkit') }</h3>
				<ul className="ASNERISSEO-react-list ASNERISSEO-react-mb-0">
					{ robotsSafeDefaults.map((item) => (
						<li key={ item }>{ item }</li>
					)) }
				</ul>
			</div>
		</PanelScaffold>
	);
};

export default RobotsPanel;
