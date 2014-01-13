<?php
class SmartWiki extends SpecialPage {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( "SmartWiki", "protect" );
	}

	/**
	 * Main execute function, called upon when the SpecialPage is opened
	 */
	function execute( $par ) {
		global $wgUser, $wgRequest, $wgOut;

		if ( !$this->userCanExecute( $wgUser ) ) {
			// If the user is not authorized, show an error.
			$this->displayRestrictionError();
			return;
		}

		# Sets headers - this parent function must be called
		$this->setHeaders();

		# Get the action variable from the GET
		$action = $wgRequest->getText( 'action' );

		switch ($action) {
			# Import SmartWiki
			case 'import':
				# Initialization class
				$import = new SWImportController();
				$import->execute($wgRequest->getText( 'showInMenu' ));
				break;

			# Upload an XMI file
			case 'upload':
				# Upload class
				$upload = new SWUploadController();
				$upload->execute($wgRequest->getFileTempName('xmiUploadFile'));
				break;

			# Parse the XMI file
			case 'parse':
				# Parse class
				$parse = new SWParseController();
				$parse->execute();
				break;

			# Fill in the missing parts
			case 'fill':
				# Fill class
				$fill = new SWFillController();
				$fill->execute($wgRequest);
				break;

			# Create forms using the XMI file
			case 'create':
				# Creation class
				$create = new SWCreateController();
				$create->execute();
				break;
				
			case 'test':
				$test = new SWTestController();
				$test->execute();
				break;
				
			case 'redlinks':
				$controller = new SWRedLinksController();
				$controller->execute();
				break;

			# Show default page
			default:
				# Home class
				$home = new SWHomeController();
				$home->execute();
		}
	}
}
