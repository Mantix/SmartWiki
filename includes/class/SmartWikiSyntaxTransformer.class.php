<?php
class SmartWikiSyntaxTransformer {
	/**
	 * Cache for the parser
	 */
	private $xmlParser;

	/**
	 * Cache for the objects
	 */
	private $model;

	/**
	 * Constructor
	 */
	function __construct() {
		# TODO: Do something or remove
	}

	/**
	 * Init - Load the XMI file from the XMI file page.
	 */
	public function init() {
		# For logging
		$log = new SmartWikiLog(new SmartWikiString('parse'));

		# The SmartWiki model
		$smartwikiModel = SmartWikiModel::singleton();

		# Step 1: Get the current pages
		# Step 1.1: Load the current Named Elements
		$category = Category::newFromName('SmartWiki Named Elements');
		$titleIterator = $category->getMembers();
		while ($titleIterator->valid()) {
			$titleObject = $titleIterator->current();
			$log->add($titleObject);
			$titleIterator->next();
		}

		# Step 1.2: Load the current attributes
		$category = Category::newFromName('SmartWiki Attributes');
		$titleIterator = $category->getMembers();
		while ($titleIterator->valid()) {
			$titleObject = $titleIterator->current();
			$log->add($titleObject);
			$titleIterator->next();
		}

		# Step 2: Load the XMI file from the Wiki article
		$title = Title::newFromText('SmartWiki XMI File');
		$article = new Article($title);
		$article_text = $article->getRawText();

		# Check if the XMI File page already excists
		if (($title->isKnown()) && (strpos($article_text, '<pre>') !== false) && (strpos($article_text, '</pre>') !== false)) {

			# Get only the UML data inside the XMI document:
			$start	= strpos($article_text, '<pre>') + 5; // Start of XMI file
			$end	= strpos($article_text, '</pre>'); //End of XMI file
			$uml	= substr($article_text, $start, $end - $start);

			# Create XML parser
			$this->xmlParser = new SmartWikiXmlParser($uml);
			
			

			# Parse the parts we need
			SmartWikiPackage::parseXMI($this->xmlParser);
			SmartWikiClass::parseXMI($this->xmlParser);
			SmartWikiAssociationClass::parseXMI($this->xmlParser);
			SmartWikiAssociation::parseXMI($this->xmlParser);
			SmartWikiGeneralization::parseXMI($this->xmlParser);
			SmartWikiAttribute::parseXMI($this->xmlParser);
			
			$smartwikiModel->sortAndOrderNr();

			# Create the pages
			SmartWikiModelElement::createPages(new SmartWikiString('SmartWiki Package'), $smartwikiModel->getPackages(), $log);
			SmartWikiModelElement::createPages(new SmartWikiString('SmartWiki Class'), $smartwikiModel->getClasses(), $log);
			SmartWikiModelElement::createPages(new SmartWikiString('SmartWiki Association'), $smartwikiModel->getAssociations(), $log);
			SmartWikiModelElement::createPages(new SmartWikiString('SmartWiki Association class'), $smartwikiModel->getAssociationClasses(), $log);
			SmartWikiModelElement::createPages(new SmartWikiString('SmartWiki Generalization'), $smartwikiModel->getGeneralizations(), $log);
			SmartWikiModelElement::createPages(new SmartWikiString('SmartWiki Attribute'), $smartwikiModel->getAttributes(), $log);

			# Return an array of Title objects
			return $log;

		} else {

			# Return false
			return false;

		}
	}

}
?>