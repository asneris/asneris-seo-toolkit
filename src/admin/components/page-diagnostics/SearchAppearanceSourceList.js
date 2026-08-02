import { __ } from '@wordpress/i18n';

const SearchAppearanceSourceList = ({ rows }) => {
	if (!Array.isArray(rows) || rows.length < 1) {
		return null;
	}

	return (
		<div className="ASNERISSEO-react-sa-kv-card">
			<p className="ASNERISSEO-react-note-box-title ASNERISSEO-react-mb-0">{ __('Search Appearance Sources', 'asneris-seo-toolkit') }</p>
			<div className="ASNERISSEO-react-sa-kv-grid">
				{ rows.map((row) => (
					<div className="ASNERISSEO-react-sa-kv-row" key={ row.label }>
						<div className="ASNERISSEO-react-sa-kv-key">{ row.label }</div>
						<div className="ASNERISSEO-react-sa-kv-value">{ row.value }</div>
						<div className={ `ASNERISSEO-react-sa-kv-source is-${ row.sourceTone || 'fallback' }` }>{ row.source }</div>
					</div>
				)) }
			</div>
		</div>
	);
};

export default SearchAppearanceSourceList;
