import { __ } from '@wordpress/i18n';

const defaultTitle = __('Asneris Admin', 'asneris-seo-toolkit');
const defaultSubtitle = __('Great content alone doesn\'t guarantee discoverability. Search engines and AI systems also rely on technical structure, semantic markup, internal linking, metadata, and other discoverability signals to crawl, interpret, and reference your website effectively. Asneris helps identify and strengthen these signals to improve search readiness.', 'asneris-seo-toolkit');

const AdminHero = ({ title = defaultTitle, subtitle = defaultSubtitle }) => (
	<div className="ASNERISSEO-react-admin-hero">
		<div className="ASNERISSEO-react-admin-eyebrow">{ __('Asneris SEO Toolkit', 'asneris-seo-toolkit') }</div>
		<h1 className="ASNERISSEO-heading-h1 ASNERISSEO-react-admin-title">{ title }</h1>
		<p className="ASNERISSEO-react-admin-subtitle">{ subtitle }</p>
	</div>
);

export default AdminHero;