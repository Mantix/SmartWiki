<?php
class SmartWikiInitializeController {
	private $log;

	/**
	 * Constructor
	 */
	function __construct() {
		# TODO: Do something or remove
	}

	/**
	 * Do the initialize of SmartWiki
	 * 
	 * @param boolean $showInMenu
	 * 
	 * @return HTML output code
	 */
	public function execute($showInMenu = true) {
		global $wgOut, $wgUser;

		$sk = $wgUser->getSkin();

		# Log handler
		$log = new SmartWikiLog(new SmartWikiString('initialize'));

		# HTML output
		$htmlOut  = '';

		# The initialize class
		$initializer = new SmartWikiInitialize();

		# Call the functions to initialize everything, store the links
		$links_photo	= $initializer->initializeFile($log);						# Install the files
		$links_main		= $initializer->initializeNamespace($log, NS_MAIN);			# Install the SmartWiki main pages
		$links_help		= $initializer->initializeNamespace($log, NS_HELP);			# Install the SmartWiki help pages
		$links_property	= $initializer->initializeNamespace($log, SMW_NS_PROPERTY);	# Install the SmartWiki properties 
		$links_template	= $initializer->initializeNamespace($log, NS_TEMPLATE);		# Install the SmartWiki templates
		$links_form		= $initializer->initializeNamespace($log, SF_NS_FORM);		# Install the SmartWiki forms
		$links_category	= $initializer->initializeNamespace($log, NS_CATEGORY);		# Install the SmartWiki categories
		$links_menu 	= $initializer->initializeMenu($log, $showInMenu, $links_category);

		# Output the initialize message and display all the links and a link to go back
		$htmlOut .= Xml::tags( 'h2', null, wfMsgForContent('smartwiki-initialize-title'));
		$htmlOut .= Xml::tags( 'p', null, wfMsgForContent('smartwiki-initialize-page'));
		$htmlOut .= Xml::tags( 'p', null, $log->output());
		$htmlOut .= Xml::tags( 'p', null, $sk->link(Title::newFromText('SmartWiki', NS_SPECIAL), wfMsgForContent('smartwiki-back')));

		# Output the HTML codes
		$wgOut->addHTML( $htmlOut );
	}

}
