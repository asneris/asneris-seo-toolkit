import { useEffect, useRef, useState } from '@wordpress/element';
import { Popover } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const InlineHelpDetails = ({ title, items = [], note = '', tone = 'info' }) => {
	const [isOpen, setIsOpen] = useState(false);
	const iconRef = useRef(null);
	const popoverContentRef = useRef(null);
	const toneStyles = {
		info: {
			background: '#f0f6fc',
			border: '#72aee6',
			text: '#365169',
		},
		warning: {
			background: '#fff8e5',
			border: '#dba617',
			text: '#6b5200',
		},
	};

	const style = toneStyles[tone] || toneStyles.info;
	const tooltipLabel = __('Open help details', 'asneris-seo-toolkit');
	const hasContent = items.length > 0 || note;

	useEffect(() => {
		if (!isOpen) {
			return undefined;
		}

		const handleDocumentPointerDown = (event) => {
			const eventPath = typeof event.composedPath === 'function' ? event.composedPath() : [];
			const clickedIcon = iconRef.current && (
				eventPath.includes(iconRef.current) || iconRef.current.contains(event.target)
			);
			const clickedPopover = popoverContentRef.current && (
				eventPath.includes(popoverContentRef.current) || popoverContentRef.current.contains(event.target)
			);
			if (!clickedIcon && !clickedPopover) {
				setIsOpen(false);
			}
		};

		document.addEventListener('pointerdown', handleDocumentPointerDown, true);
		return () => {
			document.removeEventListener('pointerdown', handleDocumentPointerDown, true);
		};
	}, [isOpen]);

	if (!title && !hasContent) {
		return null;
	}

	return (
		<div className="ASNERISSEO-react-help-inline">
			{ title ? <strong className="ASNERISSEO-react-help-inline-title">{ title }</strong> : null }
			<button
				type="button"
				ref={ iconRef }
				className="ASNERISSEO-react-help-icon"
				aria-label={ tooltipLabel }
				title={ tooltipLabel }
				aria-expanded={ isOpen ? 'true' : 'false' }
				onClick={ () => setIsOpen((previous) => !previous) }
			>
				i
			</button>
			{ isOpen && iconRef.current ? (
				<Popover
					anchor={ iconRef.current }
					onClose={ () => setIsOpen(false) }
					placement="bottom-start"
					shift
				>
					<div
						ref={ popoverContentRef }
						className="ASNERISSEO-react-help-popover"
						style={ { borderLeft: `3px solid ${ style.border }`, background: style.background, color: style.text } }
					>
						{ title ? <p className="ASNERISSEO-react-help-popover-title">{ title }</p> : null }
						{ items.length > 0 ? (
							<ul className="ASNERISSEO-react-help-popover-list">
								{ items.map((item) => <li key={ item }>{ item }</li>) }
							</ul>
						) : null }
						{ note ? <p className="ASNERISSEO-react-help-popover-note"><strong>{ __('Note:', 'asneris-seo-toolkit') }</strong> { note }</p> : null }
					</div>
				</Popover>
			) : null }
		</div>
	);
};

export default InlineHelpDetails;
