<?php
class SmartWikiUploadController {
	/**
	 * Constructor
	 */
	function __construct() {
		# TODO: Do something or remove
	}

	/**
	 * Display a default page with the action buttons
	 *
	 * @var $upload_file - XMI file that was uploaded by the user
	 *
	 * @return $htmlOut - HTML codes with a status message
	 */
	public function execute($upload_file) {
		global $wgOut, $wgUser;

		# Variable for the HTML codes
		$htmlOut = '';

		# Get the skin for the link
		$sk = $wgUser->getSkin();

		# Get contents of uploaded file
		$upload_content = SmartWiki::readFile($upload_file);

		# Try to load the uploaded XML file
		$xml = null;
		try {
			$xml = @ new SimpleXMLElement($upload_content);
		} catch (Exception $e) {
		}

		# Check if it is a valid XMI file using the namespaces
		if (($xml) && ($namespaces = $xml->getDocNamespaces()) && (substr($namespaces['UML'], 0, 11) == 'org.omg.xmi')) {

			# (Re)create a page with the contents of the XMI file
			$title = Title::newFromText('SmartWiki XMI File');
			$article = new Article($title);
			$article->doEdit(wfMsgForContent('smartwiki-upload-header') . "\n\nXMI:\n<pre>" . $xml->asXML() . "</pre>" , "(Re)created a page with the contents of an uploaded XMI file");
	
			# Output the upload message and display a link to the stored XMI file
			$htmlOut .= Xml::tags( 'h2', null, wfMsgForContent('smartwiki-upload-title'));
			$htmlOut .= Xml::tags( 'p', null, wfMsgForContent('smartwiki-upload-page'));
			$htmlOut .= Xml::tags( 'p', null, $sk->link(Title::newFromText('SmartWiki XMI File')));
			$htmlOut .= Xml::tags( 'p', null, $sk->link(Title::newFromText('SmartWiki', NS_SPECIAL), wfMsgForContent('smartwiki-back')));

		} else {

			# Output the upload error message and display a link back to the SmartWiki page
			$htmlOut .= Xml::tags( 'h2', null, wfMsgForContent('smartwiki-upload-title'));
			$htmlOut .= Xml::tags( 'p', null, wfMsgForContent('smartwiki-upload-error'));
			$htmlOut .= Xml::tags( 'p', null, $sk->link(Title::newFromText('SmartWiki', NS_SPECIAL), wfMsgForContent('smartwiki-back')));

		}

		# Output the HTML codes
		$wgOut->addHTML( $htmlOut );
	}
}
