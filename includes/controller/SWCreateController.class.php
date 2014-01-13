<?php
class SWCreateController {
	/**
	 * Cache for the transformer
	 * 
	 * @var SWTransformer Transformer
	 */
	private $transformer;

	/**
	 * Constructor
	 */
	function __construct() {
		# TODO: Do something or remove
	}

	/**
	 * Display a default page with the action buttons
	 * 
	 * @return $htmlOut - HTML output code
	 */
	public function execute() {
		global $wgOut, $wgUser;

		# Variable for the HTML codes
		$htmlOut = '';

		# Get the skin to use for the links
		$sk = $wgUser->getSkin();

		$this->transformer = new SWTransformer();
		$log = $this->transformer->init();

		if ($log != false) {

			# Output the parse message and display links to the articles
			$htmlOut .= Xml::tags( 'h2', null, wfMsgForContent('smartwiki-create-title'));
			$htmlOut .= Xml::tags( 'p', null, wfMsgForContent('smartwiki-create-page'));
			$htmlOut .= Xml::tags( 'p', null, $log->output());
			$htmlOut .= Xml::tags( 'p', null, $sk->link(Title::newFromText('SmartWiki', NS_SPECIAL), wfMsgForContent('smartwiki-back')));

		} else {

			# Output the parse error message and display a link back to the SmartWiki page
			$htmlOut .= Xml::tags( 'h2', null, wfMsgForContent('smartwiki-create-title'));
			$htmlOut .= Xml::tags( 'p', null, wfMsgForContent('smartwiki-create-error'));
			$htmlOut .= Xml::tags( 'p', null, $sk->link(Title::newFromText('SmartWiki', NS_SPECIAL), wfMsgForContent('smartwiki-back')));

		}

		# Output the HTML codes
		$wgOut->addHTML( $htmlOut );
	}
}
