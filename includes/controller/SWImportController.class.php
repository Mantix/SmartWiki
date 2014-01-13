<?php
class SWImportController {
	private $log;

	/**
	 * Constructor
	 */
	function __construct() {
		# TODO: Do something or remove
	}

	/**
	 * Do the import of SmartWiki
	 * 
	 * @param boolean $showInMenu
	 * 
	 * @return HTML output code
	 */
	public function execute($showInMenu = true) {
		global $wgOut, $wgUser;

		$sk = $wgUser->getSkin();

		# Log handler
		$log = new SWLogger(new SWString('import'));

		# HTML output
		$htmlOut  = '';

		# The import class
		$importer = new SWImport();

		# Call the functions to import everything, store the links
		$links_photo	= $importer->importFile($log);						# Install the files
		$links_main		= $importer->importNamespace($log, NS_MAIN);			# Install the SmartWiki main pages
		$links_help		= $importer->importNamespace($log, NS_HELP);			# Install the SmartWiki help pages
		$links_property	= $importer->importNamespace($log, SMW_NS_PROPERTY);	# Install the SmartWiki properties 
		$links_template	= $importer->importNamespace($log, NS_TEMPLATE);		# Install the SmartWiki templates
		$links_form		= $importer->importNamespace($log, SF_NS_FORM);		# Install the SmartWiki forms
		$links_category	= $importer->importNamespace($log, NS_CATEGORY);		# Install the SmartWiki categories
		$links_menu 	= $importer->importMenu($log, $showInMenu, $links_category);

		# Output the import message and display all the links and a link to go back
		$htmlOut .= Xml::tags( 'h2', null, wfMsgForContent('smartwiki-import-title'));
		$htmlOut .= Xml::tags( 'p', null, wfMsgForContent('smartwiki-import-page'));
		$htmlOut .= Xml::tags( 'p', null, $log->output());
		$htmlOut .= Xml::tags( 'p', null, $sk->link(Title::newFromText('SmartWiki', NS_SPECIAL), wfMsgForContent('smartwiki-back')));

		# Output the HTML codes
		$wgOut->addHTML( $htmlOut );
	}

}
