<?php
class SWUploadController {
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
	 * @return $pageHtml - HTML codes with a status message
	 */
	public function execute($upload_file) {
		global $wgOut, $wgUser;

		# Variable for the HTML codes
		$pageHtml = '';

		# Get the skin for the link
		$sk = $wgUser->getSkin();

		# Get contents of uploaded file
		$upload_content = SWHelper::readFile($upload_file);

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
			$pageHtml .= '
				<h2>' . wfMsgForContent('smartwiki-upload-title') . '</h2>
				<p>' . wfMsgForContent('smartwiki-upload-page') . '</p>
				<p>' . $sk->link(Title::newFromText('SmartWiki XMI File')) . '</p>
				<p>' . $sk->link(Title::newFromText('SmartWiki', NS_SPECIAL), wfMsgForContent('smartwiki-back')) . '</p>';

		} else {

			# Output the upload error message and display a link back to the SmartWiki page
			$pageHtml .= '
				<h2>' . wfMsgForContent('smartwiki-upload-title') . '</h2>
				<p>' . wfMsgForContent('smartwiki-upload-error') . '</p>
				<p>' . $sk->link(Title::newFromText('SmartWiki', NS_SPECIAL), wfMsgForContent('smartwiki-back')) . '</p>';

		}

		# Output the HTML codes
		$wgOut->addHTML( $pageHtml );
	}
}
