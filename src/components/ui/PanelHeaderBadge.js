import { __ } from '@wordpress/i18n';
import StatusBadge from './StatusBadge';

const presetMap = {
	phase1: {
		label: __( 'Phase 1', 'asneris-seo-toolkit' ),
		tone: 'info',
	},
	live: {
		label: __( 'Live', 'asneris-seo-toolkit' ),
		tone: 'success',
	},
	defaults: {
		label: __( 'Defaults', 'asneris-seo-toolkit' ),
		tone: 'neutral',
	},
	manualPing: {
		label: __( 'Manual ping', 'asneris-seo-toolkit' ),
		tone: 'neutral',
	},
	openGraph: {
		label: __( 'Open Graph', 'asneris-seo-toolkit' ),
		tone: 'info',
	},
};

const PanelHeaderBadge = ( { preset, label, tone } ) => {
	const resolvedPreset = preset ? presetMap[ preset ] : null;
	const resolvedLabel = resolvedPreset?.label || label;
	const resolvedTone = tone || resolvedPreset?.tone || 'info';

	if ( ! resolvedLabel ) {
		return null;
	}

	return <StatusBadge label={ resolvedLabel } tone={ resolvedTone } />;
};

export default PanelHeaderBadge;
