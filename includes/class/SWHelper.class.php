<?php
class SWHelper {

	public static function trimAll($text) {
		return str_replace(array("\n", "\r", "\t", "\0", "\x0B", "\s"), "", $text);
	}

	public static function editPage(Article $article, $newValue, $summary) {

		$logStatus = SWState::LOG_UNKNOWN;

		if (strcmp(SWHelper::trimAll($article->getRawText()), SWHelper::trimAll($newValue)) == 0) {

			// Set the log status
			$logStatus = SWState::LOG_NOACTION;

		} else {

			// Set the log status
			$logStatus = ($article->getTitle()->isKnown() ? SWState::LOG_EDITED : SWState::LOG_CREATED);

			# Edit the article
			$article->doEdit($newValue, $summary);

		}

		return $logStatus;
	}

	/**
	 * Create links using a array of Title objects
	 *
	 * @param array $returnTitles
	 *
	 * @return HTML output code
	 */
	public static function createLinks($allTitles) {
		# HTML output
		$pageHtml  = '';
	
		# Get the skin to use for the links
		$sk = SmartWiki::getSkin();
	
		# Loop the array, sort the links
		$htmlTitles = array();
		for ($i = 0; $i < count($allTitles); $i++) {
			$htmlTitles[] = $sk->link($allTitles[$i]);
		}
		sort($htmlTitles);
		$pageHtml .= implode(Xml::element('br'), $htmlTitles);
	
		# Return the result
		return $pageHtml;
	}
	
	/**
	 * Read the contents of a file
	 *
	 * @var $file_name - The location to the file we need to read
	 *
	 * @return $file_content - The contents of the file
	 */
	public static function readFile($file_name) {
		$file_opening = ImportStreamSource::newFromFile($file_name);
		$file_content = '';
	
		# Als het bestand bestaat
		if ($file_opening instanceof Status && $file_opening->isGood()) {
			$file_stream = $file_opening->value;
	
			# Read the contents of the file
			while (!$file_stream->atEnd()) {
				$file_content .= $file_stream->readChunk();
			}
		}
	
		# Return the result
		return $file_content;
	}

}