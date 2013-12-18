<?php
class SmartWikiPage {

	public static function editPage(Article $article, $newValue, $summary) {

		$logStatus = SmartWikiState::LOG_UNKNOWN;

		if (trim($article->getRawText()) == trim($newValue)) {

			// Set the log status
			$logStatus = SmartWikiState::LOG_NOACTION;

		} else {

			// Set the log status
			$logStatus = ($article->getTitle()->isKnown() ? SmartWikiState::LOG_EDITED : SmartWikiState::LOG_CREATED);

			# Edit the article
			$article->doEdit($newValue, $summary);

		}

		return $logStatus;
	}

}