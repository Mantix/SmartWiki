<?php
class SmartWiki extends SpecialPage {

	/**
	 * Create links using a array of Title objects
	 * 
	 * @param array $returnTitles
	 * 
	 * @return HTML output code
	 */
	public static function createLinks($allTitles) {
		# HTML output
		$htmlOut  = '';

		# Get the skin to use for the links
		$sk = SmartWiki::getSkin();

		# Loop the array, sort the links
		$htmlTitles = array();
		for ($i = 0; $i < count($allTitles); $i++) {
			$htmlTitles[] = $sk->link($allTitles[$i]);
		}
		sort($htmlTitles);
		$htmlOut .= implode(Xml::element('br'), $htmlTitles);

		# Return the result
		return $htmlOut;
	}

	/**
	 * Read the contents of a file
	 * 
	 * @var $file_name - The location to the file we need to read
	 * 
	 * @return $file_content - The contents of the file
	 */
	public static function readFile($file_name) {
		$file_opening = ImportStreamSource::newFromFile($file_name);
		$file_content = '';

		# Als het bestand bestaat
		if ($file_opening instanceof Status && $file_opening->isGood()) {
			$file_stream = $file_opening->value;

			# Read the contents of the file
			while (!$file_stream->atEnd()) {
				$file_content .= $file_stream->readChunk();
			}
		}

		# Return the result
		return $file_content;
	}

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
			# Initialize SmartWiki
			case 'initialize':
				# Initialization class
				$initialize = new SmartWikiInitializeController();
				$initialize->execute($wgRequest->getText( 'showInMenu' ));
				break;

			# Upload an XMI file
			case 'upload':
				# Upload class
				$upload = new SmartWikiUploadController();
				$upload->execute($wgRequest->getFileTempName('xmiUploadFile'));
				break;

			# Parse the XMI file
			case 'parse':
				# Parse class
				$parse = new SmartWikiParseController();
				$parse->execute();
				break;

			# Fill in the missing parts
			case 'fill':
				# Fill class
				$fill = new SmartWikiFillController();
				$fill->execute($wgRequest);
				break;

			# Create forms using the XMI file
			case 'create':
				# Creation class
				$create = new SmartWikiCreateController();
				$create->execute();
				break;
				
			case 'test':
				$test = new SmartWikiTestController();
				$test->execute();
				break;
				
			case 'redlinks':
				$controller = new SmartWikiRedLinksController();
				$controller->execute();
				break;

			# Show default page
			default:
				# Home class
				$home = new SmartWikiHomeController();
				$home->execute();
		}
	}
}
