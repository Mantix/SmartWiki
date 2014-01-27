<?php

class SWLogger {
	private $logItems;
	private $type;

	/**
	 * Constructor
	 */
	function __construct(SWString $t = NULL) {
		$this->logItems = array();
		$this->type = $t;
	}

	/**
	 * Add a SWLogItem to the array
	 * @param SWLogItem $logItem
	 */
	public function add(Title $title, $state = SWState::LOG_UNKNOWN) {
		$logItem = new SWLogItem($title, $state);

		foreach ($this->logItems AS $value) {
			if ($logItem->equals($value)) {
				$value->setState($state);
				return false;
			}
		}

		$this->logItems[] = $logItem;
		return true;
	}

	public function getTitlesWithState($state) {
		$titleArray = array();

		foreach ($this->logItems AS $value) {
			if ($value->getState() == $state) {
				$titleArray[] = $value->getTitle();
			}
		}

		return $titleArray;
	}

	/**
	 * Output the log as HTML
	 */
	public function output() {
		global $wgSitename;

		# Open the <pre> tag
		$pageHtml = '
			<pre style="font-size: 120%;">';
		$wikiOut = '';

		# Loop the array, sort the links
		$htmlTitles = array();
		$wikiTitles = array();
		foreach ($this->logItems AS $value) {
			$htmlTitles[] = $value->getHtmlText();
			$wikiTitles[] = $value->getWikiText();
		}
		sort($htmlTitles);
		sort($wikiTitles);

		# Create HTML code
		$pageHtml .= implode(Xml::element('br'), $htmlTitles);
		$wikiOut .= implode("\n\n", $wikiTitles);

		# Close the <pre> tag
		$pageHtml .= '
			</pre>';
		$wikiOut .= '';

		$log_title = Title::newFromText('SmartWiki ' . $this->type . ' log (' . date('c') . ')');
		$log_article = new Article($log_title);
		$log_article->doEdit($wikiOut, 'Added a SmartWiki ' . $this->type . ' log');

		$about_title = Title::newFromText('About', NS_PROJECT);
		$about_article = new Article($about_title);
		$text = $about_article->getRawText();
		if ($text != '') {
			$text .= "\n\n";
		}
		$text .= "[[" . $log_title->getText() . "]]";
		$about_article->doEdit($text, 'Added a SmartWiki ' . $this->type . ' log');

		return $pageHtml;
	}
}

class SWLogItem {
	private $title;
	private $state;

	# Link attributes
	public static $attribsNoAction	= array('style' => 'color: #000;');
	public static $attribsCreated	= array('style' => 'color: #00F;');
	public static $attribsEdited	= array('style' => 'color: #880;');
	public static $attribsDeleted	= array('style' => 'color: #F00;');

	/**
	 * Constructor
	 */
	function __construct(Title $t, $s = NULL) {
		$this->title = $t;

		# If state if given, set it, otherwise set to NoAction
		if ($s != NULL) {
			$this->state = $s;
		} else {
			$this->state = SWLogItem::$attribsNoAction;
		}
	}

	public function setState($s) {
		if ($s != NULL) {
			$this->state = $s;
		}
	}

	public function getState() {
		return $this->state;
	}

	public function getTitle() {
		return $this->title;
	}

	public function equals(SWLogItem $other) {
		if (isset($this->title) && isset($other->title)) {
			if ($this->title->getFullURL() == $other->title->getFullURL()) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function getHtmlText() {
		global $wgUser;
		$sk = $wgUser->getSkin();

		$attribs = array();
		$i18Key = '';

		switch ($this->state) {
			case SWState::LOG_CREATED:
				$attribs = SWLogItem::$attribsCreated;
				$i18Key = 'smartwiki-log-created';
				break;
			case SWState::LOG_EDITED:
				$attribs = SWLogItem::$attribsEdited;
				$i18Key = 'smartwiki-log-edited';
				break;
			case SWState::LOG_DELETED:
				$attribs = SWLogItem::$attribsDeleted;
				$i18Key = 'smartwiki-log-deleted';
				break;
			default:
				$attribs = SWLogItem::$attribsNoAction;
				$i18Key = 'smartwiki-log-noaction';
		}

		return '<!-- ' . ($this->title->getNsText() ? $this->title->getNsText() : wfMsgForContent('smartwiki-log-page')) . ' -->' . $sk->link($this->title, wfMsgForContent($i18Key, ($this->title->getNsText() ? $this->title->getNsText() : wfMsgForContent('smartwiki-log-page')), $this->title->getText()), $attribs);
	}

	public function getWikiText() {
		$i18Key = '';

		switch ($this->state) {
			case SWState::LOG_CREATED:
				$i18Key = 'smartwiki-log-created';
				break;
			case SWState::LOG_EDITED:
				$i18Key = 'smartwiki-log-edited';
				break;
			case SWState::LOG_DELETED:
				$i18Key = 'smartwiki-log-deleted';
				break;
			default:
				$i18Key = 'smartwiki-log-noaction';
		}

		return "[[:" . $this->title->getFullText() . "|" . wfMsgForContent($i18Key, ($this->title->getNsText() ? $this->title->getNsText() : wfMsgForContent('smartwiki-log-page')), $this->title->getText()) . "]]";
	}
}
