import { __ } from '@wordpress/i18n';

const isUnifiedContractError = (payload) => {
	const code = String(payload?.code || '').toLowerCase();
	return code.startsWith('asnerisseo_unified_contract_');
};

const buildRequestError = (payload, response) => {
	let message = payload?.message || __('Request failed.', 'asneris-seo-toolkit');

	if (isUnifiedContractError(payload)) {
		message = __('Unified diagnostics contract violation detected. Please update providers and refresh.', 'asneris-seo-toolkit');
	}

	const error = new Error(message);
	error.code = payload?.code || null;
	error.status = Number(response?.status || 0);
	error.details = payload?.data?.details || payload?.details || null;
	error.context = payload?.data?.context || payload?.context || null;

	return error;
};

const fetchJson = async (url, options = {}) => {
	const response = await fetch(url, options);
	const payload = await response.json().catch(() => null);
	if (!response.ok) {
		throw buildRequestError(payload || {}, response);
	}
	return payload;
};

export default fetchJson;
