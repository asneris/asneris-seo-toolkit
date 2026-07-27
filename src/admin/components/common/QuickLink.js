const QuickLink = ({ href, label, iconClass = 'dashicons-arrow-right-alt2' }) => (
	<a className="button ASNERISSEO-react-quick-link ASNERISSEO-react-button ASNERISSEO-react-button-primary" href={ href }>
		<span className={ `dashicons ${ iconClass } ASNERISSEO-react-quick-link-icon` } aria-hidden="true" />
		<span className="ASNERISSEO-react-quick-link-label">{ label }</span>
	</a>
);

export default QuickLink;
