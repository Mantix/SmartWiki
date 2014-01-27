<?php
class SWHomeController {
	/**
	 * Constructor
	 */
	function __construct() {
		# TODO: Do something or remove
	}

	/**
	 * Display a default page with the action buttons
	 */
	public function execute() {
		global $wgOut;

		# Variable for the HTML codes
		$pageHtml = '';

		# Import
		$pageHtml .= '
			<h2>' . wfMsgForContent('smartwiki-import-title') . '</h2>
			<p>' . wfMsgForContent('smartwiki-import-description') . '</p>
			<form name="SmartWiki-import" id="SmartWiki-import" class="SmartWiki-import" action="" method="get">
				<input name="action" type="hidden" value="import" />
				<p><input name="showInMenu" type="checkbox" /> ' . wfMsgForContent('smartwiki-import-showinmenu') . '</p>
				<p><input type="submit" value="' . wfMsgForContent('smartwiki-import-button') . '" /></p>
			</form>';

		# Upload
		$pageHtml .= '
			<h2>' . wfMsgForContent('smartwiki-upload-title') . '</h2>
			<p>' . wfMsgForContent('smartwiki-upload-description') . '</p> 
			<form name="SmartWiki-upload" id="SmartWiki-upload" class="SmartWiki-upload" action="" method="post" enctype="multipart/form-data">
				<input name="action" type="hidden" value="upload" />
				<p><input name="xmiUploadFile" type="file" size="30" /></p>
				<p><input type="submit" value="' . wfMsgForContent('smartwiki-upload-button') . '" /></p>
			</form>';

		# Parse
		$pageHtml .= '
			<h2>' . wfMsgForContent('smartwiki-parse-title') . '</h2> 
			<p>' . wfMsgForContent('smartwiki-parse-description') . '</p>
			<form name="SmartWiki-parse" id="SmartWiki-parse" class="SmartWiki-parse" action="" method="get">
				<input name="action" type="hidden" value="parse" />
				<p><input type="submit" value="' . wfMsgForContent('smartwiki-parse-button') . '" />
			</form>';

		# Fill
		$pageHtml .= '
			<h2>' . wfMsgForContent('smartwiki-fill-title') . '</h2>
			<p>' . wfMsgForContent('smartwiki-fill-description') . '</p>
			<form name="SmartWiki-fill" id="SmartWiki-fill" class="SmartWiki-fill" action="" method="get">
				<input name="action" type="hidden" value="fill" />
				<p><input type="submit" value="' . wfMsgForContent('smartwiki-fill-button') . '" /></p>
			</form>';

		# Create
		$pageHtml .= '
			<h2>' . wfMsgForContent('smartwiki-create-title') . '</h2>
			<p>' . wfMsgForContent('smartwiki-create-description') . '</p>
			<form name="SmartWiki-create" id="SmartWiki-create" class="SmartWiki-create" action="" method="get">
				<input name="action" type="hidden" value="create" />
				<p><input type="submit" value="' . wfMsgForContent('smartwiki-create-button') . '" /></p>
			</form>';

		# Output the HTML codes
		$wgOut->addHTML( $pageHtml );
	}
}
