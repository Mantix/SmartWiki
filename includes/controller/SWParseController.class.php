<?php
class SWParseController {
	/**
	 * Cache for the transformer
	 * 
	 * @var SWSyntaxTransformer Transformer
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
	 * @return $pageHtml - HTML output code
	 */
	public function execute() {
		global $wgOut, $wgUser;

		# Variable for the HTML codes
		$pageHtml = '';

		# Get the skin to use for the links
		$sk = $wgUser->getSkin();

		$this->transformer = new SWSyntaxTransformer();
		$log = $this->transformer->init();

		if ($log != false) {

			# Output the parse message and display links to the articles
			$pageHtml .= '
				<h2>' . wfMsgForContent('smartwiki-parse-title') . '</h2>
				<p>' . wfMsgForContent('smartwiki-parse-page') . '</p>
				<p>' . $log->output() . '</p>
				<p>' . $sk->link(Title::newFromText('SmartWiki', NS_SPECIAL), wfMsgForContent('smartwiki-back')) . '</p>';

		} else {

			# Output the parse error message and display a link back to the SmartWiki page
			$pageHtml .= '
				<h2>' . wfMsgForContent('smartwiki-parse-title') . '</h2>
				<p>' . wfMsgForContent('smartwiki-parse-error') . '</p>
				<p>' . $sk->link(Title::newFromText('SmartWiki', NS_SPECIAL), wfMsgForContent('smartwiki-back')) . '</p>';

		}

		# Output the HTML codes
		$wgOut->addHTML( $pageHtml );
	}
}
