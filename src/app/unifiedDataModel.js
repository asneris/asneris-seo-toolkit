export const getUnifiedData = (item) => {
	if (!item || typeof item !== 'object') {
		return null;
	}

	const unifiedData = item.unifiedData;
	if (!unifiedData || typeof unifiedData !== 'object') {
		return null;
	}

	return unifiedData;
};

export const hasUnifiedData = (item) => !!getUnifiedData(item);

export const UNIFIED_CONTRACT_ERROR = 'Unified diagnostics contract is required but missing. Please update plugin data providers and refresh the page.';

export const assertUnifiedData = (item, context = 'payload') => {
	if (!hasUnifiedData(item)) {
		throw new Error(`${ UNIFIED_CONTRACT_ERROR } (${ context })`);
	}

	return getUnifiedData(item);
};

export const assertUnifiedCollection = (items, context = 'collection') => {
	const list = Array.isArray(items) ? items : [];
	list.forEach((item, index) => {
		assertUnifiedData(item, `${ context }[${ index }]`);
	});

	return list;
};

export const getUnifiedRaw = (item) => {
	const unifiedData = getUnifiedData(item);
	if (!unifiedData || !unifiedData.raw || typeof unifiedData.raw !== 'object') {
		return {};
	}

	return unifiedData.raw;
};

export const getUnifiedComputed = (item) => {
	const unifiedData = getUnifiedData(item);
	if (!unifiedData || !unifiedData.computed || typeof unifiedData.computed !== 'object') {
		return {};
	}

	return unifiedData.computed;
};

export const mergeUnifiedItem = (item) => {
	if (!item || typeof item !== 'object') {
		return null;
	}

	if (!hasUnifiedData(item)) {
		return null;
	}

	const raw = getUnifiedRaw(item);
	const computed = getUnifiedComputed(item);

	return {
		...item,
		...raw,
		...computed,
	};
};

export const getUnifiedChecks = (item) => {
	const raw = getUnifiedRaw(item);
	return Array.isArray(raw?.checks) ? raw.checks : [];
};
