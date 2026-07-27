import { __ } from '@wordpress/i18n';

const GlobalStatusNotice = ({ status, onDismiss }) => {
	if (!status?.text) {
		return null;
	}

	const toneStyles = {
		success: { background: '#f0f6eb', borderColor: '#72aee6', color: '#1d6f42' },
		error: { background: '#fcf0f1', borderColor: '#d63638', color: '#8a2424' },
		warning: { background: '#fcf9e8', borderColor: '#dba617', color: '#7c5f00' },
		info: { background: '#f0f6fc', borderColor: '#72aee6', color: '#1d4f8c' },
	};

	const style = toneStyles[status.tone || 'info'] || toneStyles.info;

	return (
		<div
			style={ {
				...style,
				border: `1px solid ${ style.borderColor }`,
				padding: '10px 12px',
				borderRadius: '6px',
				marginBottom: '12px',
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'space-between',
				gap: '8px',
			} }
		>
			<div>{ status.text }</div>
			<button type="button" className="button ASNERISSEO-react-button ASNERISSEO-react-button-secondary" onClick={ onDismiss }>
				{ __('Dismiss', 'asneris-seo-toolkit') }
			</button>
		</div>
	);
};

export default GlobalStatusNotice;
