import { PanelBody } from '@wordpress/components';
import { useId } from '@wordpress/element';

const SidebarSectionShell = ( {
	title,
	initialOpen = false,
	sectionKey,
	expandedSection,
	onExpand,
	headerAction,
	headerDescription,
	headerIcon,
	headerIconStyle,
	children,
} ) => {
	const panelId = useId();
	const panelStateKey = initialOpen ? 'open' : 'closed';
	const isControlled =
		typeof sectionKey === 'string' &&
		typeof expandedSection === 'string' &&
		typeof onExpand === 'function';
	const isOpen = isControlled ? expandedSection === sectionKey : initialOpen;
	const isCompactSection = false;
	const resolvedDescription = '';
	const titleChipBackground = 'transparent';
	const titleChipColor = 'var(--asneris-dark-blue)';
	const titleChipDescriptionColor = '#4f6275';
	const iconBubbleBackground = '#f6f7f7';
	const iconBubbleColor = '#50575e';
	const iconBubbleBorder = 'none';

	const resolvedTitle =
		resolvedDescription || headerIcon ? (
			<span
				style={ {
					display: 'flex',
					alignItems: 'center',
					gap: isCompactSection ? '6px' : '8px',
					minWidth: 0,
					padding: '0',
					borderRadius: '0',
					background: titleChipBackground,
				} }
			>
				{ headerIcon ? (
					<span
						style={ {
							width: isCompactSection ? '22px' : '30px',
							height: isCompactSection ? '22px' : '30px',
							borderRadius: '999px',
							display: 'inline-flex',
							alignItems: 'center',
							justifyContent: 'center',
								background: iconBubbleBackground,
								color: iconBubbleColor,
								border: iconBubbleBorder,
							fontSize: isCompactSection ? 'var(--asneris-table-chip-size)' : 'var(--asneris-body-large-size)',
							flex: isCompactSection ? '0 0 22px' : '0 0 30px',
							...headerIconStyle,
						} }
					>
						<span className={ headerIcon } aria-hidden="true" />
					</span>
				) : null }
				<span
					style={ {
						display: isCompactSection ? 'flex' : 'grid',
						alignItems: isCompactSection ? 'center' : 'initial',
						gap: isCompactSection ? '0' : '2px',
						minWidth: 0,
					} }
				>
					<span
						style={ {
							fontWeight: 'var(--asneris-h3-weight)',
							fontSize: isCompactSection ? 'var(--asneris-table-chip-size)' : 'var(--asneris-body-size)',
							lineHeight: isCompactSection ? 'var(--asneris-h3-line)' : 'var(--asneris-body-line)',
							color: titleChipColor,
							whiteSpace: isCompactSection ? 'nowrap' : 'normal',
							overflow: isCompactSection ? 'hidden' : 'visible',
							textOverflow: isCompactSection ? 'ellipsis' : 'clip',
							maxWidth: isCompactSection ? '150px' : 'none',
						} }
					>
						{ title }
					</span>
					{ resolvedDescription ? (
						<span style={ { fontSize: 'var(--asneris-table-chip-size)', color: titleChipDescriptionColor, fontWeight: 'var(--asneris-h3-weight)', lineHeight: 'var(--asneris-h3-line)' } }>
							{ resolvedDescription }
						</span>
					) : null }
				</span>
			</span>
		) : (
			<span
				style={ {
					display: 'inline-flex',
					alignItems: 'center',
					padding: '6px 8px',
					borderRadius: '10px',
					background: titleChipBackground,
					fontWeight: 'var(--asneris-h1-weight)',
					fontSize: 'var(--asneris-body-large-size)',
					lineHeight: 'var(--asneris-h1-line)',
					color: titleChipColor,
				} }
			>
				{ title }
			</span>
		);

	return (
		<PanelBody
			key={ `${ panelId }-${ panelStateKey }` }
			title={
				headerAction ? (
					<span
						style={ {
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'space-between',
							gap: '8px',
							width: '100%',
						} }
					>
						<span style={ { minWidth: 0, flex: '1 1 auto' } }>{ resolvedTitle }</span>
						<span style={ { display: 'inline-flex', alignItems: 'center', flex: '0 0 auto' } }>
							{ headerAction }
						</span>
					</span>
				) : (
					resolvedTitle
				)
			}
			initialOpen={ initialOpen }
			opened={ isOpen }
			onToggle={ () => {
				if ( isControlled ) {
					onExpand( sectionKey );
				}
			} }
		>
			<div
				style={ {
					display: 'grid',
					gap: '8px',
					fontFamily: 'var(--asneris-font-body)',
				} }
			>
				{ children }
			</div>
		</PanelBody>
	);
};

export default SidebarSectionShell;

