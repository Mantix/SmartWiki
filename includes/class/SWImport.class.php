<?php
class SWImport {
	/**
	 * Constructor
	 */ 
	function __construct() {
		// Empty constructor
	}

	/**
	 * Import the SmartWiki links in the menu
	 * 
	 * @param $showInMenu - Should we add links to the menu
	 * 
	 * @param $links_category - Links to the categories
	 * 
	 * @return Array of Title objects
	 */
	public function importMenu(SWLogger $log, $showInMenu = true, $links_category = array()) {
		# Get the article used as the menu on the left
		$title = Title::newFromText('Sidebar', NS_MEDIAWIKI);
		$article = new Article($title);

		# Get the content of the article used as the menu
		$oldText = $article->getRawText();

		# We'll use another variable to actually do the changes
		$newText = $oldText;

		# If the links already excist, we first remove them
		if (strpos($oldText, '* ' . wfMsgForContent('smartwiki-menu-title')) !== false) {

			# The position just before our old links
			$start	= strpos($oldText, "* " . wfMsgForContent('smartwiki-menu-title') . "\n");
			# The position just after our old links, does not excist if it's the end of the menu
			$end1	= strpos($oldText, "\n* ", $start);
			$end2	= strpos($oldText, "SMART_WIKI_BEGIN", $start);

			# We copy the text before our old links
			$newText = substr($oldText, 0, $start);

			# And if there is any, we also copy the text after our old links
			if (($end1 !== false) && ((($end2 !== false) && ($end1 < $end2)) || ($end2 === false))) {
				$newText .= substr($oldText, $end1);
			} elseif ($end2 !== false) {
				$newText .= substr($oldText, $end2);
			}
		}

		# Check if the user selected the option to show the links in the menu
		if ($showInMenu == true) {

			# Make sure the menu ends with an enter ("\n")
			if (strrpos($newText, "\n") != strlen($newText) - 1) {
				$newText .= "\n";
			}
	
			# We will now add the links
			$newText .= 
				"* " . wfMsgForContent('smartwiki-menu-title') . "\n" . 
				"** Special:" . wfMsgForContent('smartwiki') . " | " . wfMsgForContent('smartwiki') . "\n";

			# We will add the links to categories
			for ($i = 0; $i < count($links_category); $i++) {
				$newText .= "** " . $links_category[$i]->getPrefixedURL() . " | " . $links_category[$i]->getText() . "\n";
			}
		}

		// Do the edit
		$logStatus = SWHelper::editPage($article, $newText, '(Re)created the menu for initialization of SmartWiki.');
		// Add this to the log
		$log->add($title, $logStatus);
	}

	/**
	 * Import a file in the "/import/files/" folder
	 * 
	 * @param $log - SmartWiki logger
	 */
	public function importFile(SWLogger $log) {
		//TODO Can we import images and use them on the SmartWiki Help page?
	}

	/**
	 * Import a article from a file in the "/import/" folder
	 * 
	 * @param $namespace_id - The ID of the Namespace we need to import
	 */
	public function importNamespace(SWLogger $log, $namespace_id) {

		if ( MWNamespace::exists( $namespace_id ) || ($namespace_id == NS_MAIN)) {
			# Get the name of the namespace
			$namespace_name = $namespace_id == NS_MAIN ? 'Main' : MWNamespace::getCanonicalName( $namespace_id );

			# Get the files from the Namespace folder in "/import/"
			$dir = dirname( __FILE__ ) . '/../../imports/' . $namespace_name . '/';
			$file_list	= $this->readDir($dir);
			$file_count	= count($file_list);

			# For every file
			for ($i = 0; $i < $file_count; $i++) {

				# Get the info of the file
				$file_info = pathinfo($file_list[$i]);

				# Get the contents of the file
				$file_contents = SWHelper::readFile($file_list[$i]);

				# Create the article
				$name = str_replace( '_', ' ', $file_info['filename'] );
				$title = Title::newFromText($name, $namespace_id);
				$article = new Article($title);

				# Fill the article with the content
				$logStatus = SWHelper::editPage($article, $file_contents, wfMsgForContent('smartwiki-import-recreate', $namespace_name, $name));

				# Add the title
				$log->add($title, $logStatus);
			}
		}
	}

	/**
	 * Read all import files in the given directory or its subdirectories
	 *
	 * @param $dir String: directory to process
	 */
	private function readDir( $dir ) {
		$file_list = array();

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir ), 
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $file ) {
			$file_list[] = $file->getRealPath();
		}

		return $file_list;
	}

}
