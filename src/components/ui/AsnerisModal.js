import { Modal, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const AsnerisModal = ( {
	isOpen,
	title,
	onRequestClose,
	children,
	closeLabel,
	showFooter = true,
	className,
} ) => {
	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			title={ title || __( 'Details', 'asneris-seo-toolkit' ) }
			onRequestClose={ onRequestClose }
			className={ className }
		>
			{ children }
			{ showFooter ? (
				<div style={ { marginTop: '16px' } }>
					<Button
						variant="primary"
						className="ASNERISSEO-react-button ASNERISSEO-react-button-primary"
						onClick={ onRequestClose }
					>
						{ closeLabel || __( 'Close', 'asneris-seo-toolkit' ) }
					</Button>
				</div>
			) : null }
		</Modal>
	);
};

export default AsnerisModal;
