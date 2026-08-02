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

const withCacheBust = (url) => {
	const requestUrl = new URL(url, window.location.origin);
	requestUrl.searchParams.set('_asneris_cache_bust', String(Date.now()));
	return requestUrl.toString();
};

const fetchJson = async (url, options = {}) => {
	const method = String(options.method || 'GET').toUpperCase();
	const requestUrl = method === 'GET' ? withCacheBust(url) : url;
	const fetchOptions = {
		cache: 'no-store',
		credentials: 'same-origin',
		...options,
		method,
		headers: {
			Accept: 'application/json',
			...(options.headers || {}),
		},
	};

	if (options.signal) {
		fetchOptions.signal = options.signal;
	}

	try {
		const response = await fetch(requestUrl, fetchOptions);
		const payload = await response.json().catch(() => null);
		if (!response.ok) {
			throw buildRequestError(payload || {}, response);
		}
		return payload;
	} catch (error) {
		if (error?.name === 'AbortError') {
			throw error;
		}
		throw error;
	}
};

export default fetchJson;
