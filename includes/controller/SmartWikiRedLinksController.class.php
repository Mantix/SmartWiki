<?php
class SmartWikiRedLinksController {
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

		$query1="SELECT distinct pl_title FROM smartwiki_pagelinks WHERE pl_title NOT IN ( SELECT page_title FROM smartwiki_page ) order by pl_title";
		$result = mysql_query($query1);
		
		$htmlOut = "<ul>";
		while ($row = mysql_fetch_row($result)) {
			$title = Title::newFromText($row[0]);
			$htmlOut .= "<li><a href='".$title->getLocalUrl()."'>".$row[0]."</a></li>";
		}
		$htmlOut .= "</ul>";
		mysql_free_result($result);
		
		$wgOut->addHTML( $htmlOut );
	}

}
