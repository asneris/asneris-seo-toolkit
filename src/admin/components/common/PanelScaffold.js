const sectionStyle = {
	background: '#fff',
	border: '1px solid #d1d9e0',
	borderRadius: '12px',
	padding: '18px',
	boxShadow: '0 8px 20px rgba(7, 28, 52, 0.05)',
};

const PanelScaffold = ({ title, description, panelClass, children }) => (
	<div style={ sectionStyle } className={ `ASNERISSEO-react-panel ${ panelClass }` }>
		{ title ? <h2 className="ASNERISSEO-heading-h2" style={ { marginTop: 0 } }>{ title }</h2> : null }
		{ description ? <p style={ { color: '#50575e' } }>{ description }</p> : null }
		{ children }
	</div>
);

export default PanelScaffold;
