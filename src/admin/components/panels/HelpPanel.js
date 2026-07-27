import { __ } from '@wordpress/i18n';
import PanelScaffold from '../common/PanelScaffold';

const HelpPanel = () => (
	<PanelScaffold
		title={ __('Help & Documentation', 'asneris-seo-toolkit') }
		panelClass="ASNERISSEO-react-help-panel"
	>
		<p className="ASNERISSEO-react-text-muted-strong">{ __('Asneris SEO Toolkit validates what search engines can see. It does not predict rankings.', 'asneris-seo-toolkit') }</p>

		<h3 className="ASNERISSEO-heading-h3">{ __('What This Plugin Does', 'asneris-seo-toolkit') }</h3>
		<ul>
			<li>{ __('Detects technical SEO signals on your pages', 'asneris-seo-toolkit') }</li>
			<li>{ __('Identifies conflicts and ambiguities', 'asneris-seo-toolkit') }</li>
			<li>{ __('Explains why clarity matters', 'asneris-seo-toolkit') }</li>
			<li>{ __('Helps prevent accidental SEO misconfiguration', 'asneris-seo-toolkit') }</li>
			<li>{ __('Provides safe redirect management', 'asneris-seo-toolkit') }</li>
			<li>{ __('Validates robots.txt syntax', 'asneris-seo-toolkit') }</li>
		</ul>

		<h3 className="ASNERISSEO-heading-h3 ASNERISSEO-react-text-danger">{ __('What This Plugin Does NOT Do', 'asneris-seo-toolkit') }</h3>
		<ul>
			<li>{ __('Does NOT promise higher rankings', 'asneris-seo-toolkit') }</li>
			<li>{ __('Does NOT provide SEO scores or grades', 'asneris-seo-toolkit') }</li>
			<li>{ __('Does NOT predict algorithm behavior', 'asneris-seo-toolkit') }</li>
			<li>{ __('Does NOT track backlinks or competitors', 'asneris-seo-toolkit') }</li>
			<li>{ __('Does NOT rewrite your content with AI', 'asneris-seo-toolkit') }</li>
			<li>{ __('Does NOT analyze keyword density', 'asneris-seo-toolkit') }</li>
			<li>{ __('Does NOT guarantee traffic or conversions', 'asneris-seo-toolkit') }</li>
			<li>{ __('Does NOT submit data to third-party services without consent', 'asneris-seo-toolkit') }</li>
		</ul>

		<h3 className="ASNERISSEO-heading-h3">{ __('Key SEO Concepts', 'asneris-seo-toolkit') }</h3>
		<p><strong>{ __('Title Tag:', 'asneris-seo-toolkit') }</strong> { __('Appears in browser tabs and search results to describe page topic.', 'asneris-seo-toolkit') }</p>
		<p><strong>{ __('Canonical URL:', 'asneris-seo-toolkit') }</strong> { __('Suggests the preferred URL when similar content exists.', 'asneris-seo-toolkit') }</p>
		<p><strong>{ __('Meta Robots (noindex):', 'asneris-seo-toolkit') }</strong> { __('Controls indexing, not page access for visitors.', 'asneris-seo-toolkit') }</p>
		<p><strong>{ __('Schema Markup:', 'asneris-seo-toolkit') }</strong> { __('Structured data that helps engines understand entities and context.', 'asneris-seo-toolkit') }</p>
		<p><strong>{ __('301 Redirect:', 'asneris-seo-toolkit') }</strong> { __('Permanently moves a URL and preserves existing signals.', 'asneris-seo-toolkit') }</p>
		<p><strong>{ __('Robots.txt:', 'asneris-seo-toolkit') }</strong> { __('Guides crawling behavior but is not a security control.', 'asneris-seo-toolkit') }</p>

		<h3 className="ASNERISSEO-heading-h3">{ __('Understanding Validation Status', 'asneris-seo-toolkit') }</h3>
		<p><strong className="ASNERISSEO-react-text-success">{ __('Pass:', 'asneris-seo-toolkit') }</strong> { __('Clear signals were detected. This is not a ranking guarantee.', 'asneris-seo-toolkit') }</p>
		<p><strong className="ASNERISSEO-react-text-warning">{ __('Warning:', 'asneris-seo-toolkit') }</strong> { __('A signal may be missing or unclear. This is not a penalty.', 'asneris-seo-toolkit') }</p>
		<p><strong className="ASNERISSEO-react-text-danger">{ __('Conflict:', 'asneris-seo-toolkit') }</strong> { __('Contradictory signals exist and should be resolved.', 'asneris-seo-toolkit') }</p>

		<div className="ASNERISSEO-react-note-box ASNERISSEO-react-mt-12">
			<h3 className="ASNERISSEO-heading-h3 ASNERISSEO-react-note-box-title ASNERISSEO-react-mb-8">{ __('Our Philosophy', 'asneris-seo-toolkit') }</h3>
			<p className="ASNERISSEO-react-mb-0">{ __('SEO is about clarity and accessibility for search engines. This plugin validates clarity, nothing more and nothing less.', 'asneris-seo-toolkit') }</p>
		</div>

		<h3 className="ASNERISSEO-heading-h3">{ __('Support', 'asneris-seo-toolkit') }</h3>
		<p className="ASNERISSEO-react-mb-8 ASNERISSEO-react-muted">
			<strong>{ __('This is beta software.', 'asneris-seo-toolkit') }</strong>{ ' ' }
			{ __('Features and behavior may change.', 'asneris-seo-toolkit') }
		</p>
		<p>
			<a href="https://wordpress.org/support/plugin/asneris-seo-toolkit/" target="_blank" rel="noopener noreferrer">
				{ __('WordPress.org Support Forum', 'asneris-seo-toolkit') }
			</a>
		</p>
	</PanelScaffold>
);

export default HelpPanel;
