import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createPortal } from '@wordpress/element';

const AsnerisDrawer = ( { isOpen, title, onClose, children } ) => {
	if ( ! isOpen ) {
		return null;
	}

	return createPortal(
		<div
			style={ {
				position: 'fixed',
				left: '0',
				right: '0',
				bottom: '0',
				zIndex: 100000,
				display: 'flex',
				justifyContent: 'center',
			} }
		>
			<div
				style={ {
					width: '100%',
					height: 'min(52vh, 560px)',
					minHeight: '320px',
					maxHeight: '70vh',
					resize: 'vertical',
					background: '#fff',
					boxShadow: '0 -8px 28px rgba(15, 23, 42, 0.24)',
					padding: '16px',
					overflowY: 'auto',
					borderTopLeftRadius: '12px',
					borderTopRightRadius: '12px',
					border: '1px solid #dcdcde',
					borderBottom: 'none',
				} }
			>
				<div
					style={ {
						display: 'flex',
						justifyContent: 'space-between',
						alignItems: 'center',
						marginBottom: '12px',
					} }
				>
					<strong>
						{ title ||
							__( 'SEO Workspace', 'asneris-seo-toolkit' ) }
					</strong>
					<Button
						variant="primary"
						className="ASNERISSEO-react-button ASNERISSEO-react-button-primary"
						onClick={ onClose }
					>
						{ __( 'Close', 'asneris-seo-toolkit' ) }
					</Button>
				</div>
				{ children }
			</div>
		</div>,
		document.body
	);
};

export default AsnerisDrawer;
