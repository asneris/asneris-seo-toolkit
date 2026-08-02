import { __ } from '@wordpress/i18n';

const PageDiagnosticsTabs = ({
	tabs = [],
	activeTab,
	onTabChange,
	countsByTab = {},
	children,
	tabListClassName = '',
	tabClassName = '',
	tabListStyle = {},
	tabButtonStyle = {},
	tabListRef,
	onWheel,
	onPointerDown,
	onPointerMove,
	onPointerUp,
	onPointerCancel,
	onPointerLeave,
	onClickCapture,
}) => {
	return (
		<>
			<div
				ref={ tabListRef }
				className={ `ASNERISSEO-react-detail-tabs ASNERISSEO-react-tabs ASNERISSEO-react-tabs-strip${ tabListClassName ? ` ${tabListClassName}` : '' }` }
				role="tablist"
				aria-label={ __('Diagnostics Detail Tabs', 'asneris-seo-toolkit') }
				style={ tabListStyle }
				onWheel={ onWheel }
				onPointerDown={ onPointerDown }
				onPointerMove={ onPointerMove }
				onPointerUp={ onPointerUp }
				onPointerCancel={ onPointerCancel }
				onPointerLeave={ onPointerLeave }
				onClickCapture={ onClickCapture }
			>
				{ tabs.map((tab) => (
					<button
						key={ tab.key }
						type="button"
						role="tab"
						aria-selected={ activeTab === tab.key }
						className={ `ASNERISSEO-react-tab${ activeTab === tab.key ? ' is-active' : ''}${ tabClassName ? ` ${tabClassName}` : '' }` }
						disabled={ tab.disabled }
						onClick={ () => onTabChange?.(tab.key) }
						style={ tabButtonStyle }
					>
						{ tab.label }
						{ typeof countsByTab[tab.key] === 'number' ? ` (${ countsByTab[tab.key] })` : null }
					</button>
				)) }
			</div>
			{ children }
		</>
	);
};

export default PageDiagnosticsTabs;
