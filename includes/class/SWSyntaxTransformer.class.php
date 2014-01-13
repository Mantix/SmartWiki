<?php
class SWSyntaxTransformer {
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
		$log = new SWLogger(new SWString('parse'));

		# The SmartWiki model
		$smartwikiModel = SWModel::singleton();

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
			$this->xmlParser = new SWXmlParser($uml);
			
			

			# Parse the parts we need
			SWPackage::parseXMI($this->xmlParser);
			SWClass::parseXMI($this->xmlParser);
			SWAssociationClass::parseXMI($this->xmlParser);
			SWAssociation::parseXMI($this->xmlParser);
			SWGeneralization::parseXMI($this->xmlParser);
			SWAttribute::parseXMI($this->xmlParser);
			
			$smartwikiModel->sortAndOrderNr();

			# Create the pages
			SWModelElement::createPages(new SWString('SmartWiki Package'), $smartwikiModel->getPackages(), $log);
			SWModelElement::createPages(new SWString('SmartWiki Class'), $smartwikiModel->getClasses(), $log);
			SWModelElement::createPages(new SWString('SmartWiki Association'), $smartwikiModel->getAssociations(), $log);
			SWModelElement::createPages(new SWString('SmartWiki Association class'), $smartwikiModel->getAssociationClasses(), $log);
			SWModelElement::createPages(new SWString('SmartWiki Generalization'), $smartwikiModel->getGeneralizations(), $log);
			SWModelElement::createPages(new SWString('SmartWiki Attribute'), $smartwikiModel->getAttributes(), $log);

			# Return an array of Title objects
			return $log;

		} else {

			# Return false
			return false;

		}
	}

}
?>