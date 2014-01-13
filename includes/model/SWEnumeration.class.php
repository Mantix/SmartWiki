<?php
class SWEnumeration extends SWModelElement {
	private $predefinedValues;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	* Enumerations are not parsed from the XMI file, so an empty function is given
	*
	* @param SWSyntaxTransformer $syntaxTransformer
	*/
	public static function parseXMI(SWXmlParser $xmlParser) {
		// DO NOTHING
	}

	/**
	* Create a page for the current object with the given template
	*
	* @param String $form_template
	*
	* @return Title object of the created page
	*/
	public function createPage($form_template) {
		# Edit the Article
		$article = new Article($this->getTitle());
	
		# Fill the template with the values provided in the $pageArray
		$form_template['template'] = str_replace('%Title%', $this->getTitle()->getText(), $form_template['template']);
		$form_template['template'] = str_replace('%Predefined values%', $this->getPackage()->getTitle()->getText(), $form_template['template']);
	
		# Fill the page with the content
		SWHelper::editPage($article, $form_template['template'] . $this->getContent(), '(Re)created ' . $this->getTitle()->getText());
	
		# Return the Title object
		return $this->getTitle();
	}

	/**
	 * Create an array of Enumeration objects, fill it with the fields provided
	 * 
	 * @param $fieldsArray - An array of fields
	 * 
	 * @return $enumerationArray - An array of enumerations
	 */
	public static function fill($fieldsArray) {

		# SWModel object
		$smartwikiModel = SWModel::singleton();
	
		for ($i = 0; $i < count($fieldsArray); $i++) {
			$enumeration = new SWEnumeration();
			$enumeration->setTitle($fieldsArray[$i]['titleObject']);
			//$enumeration->setTitle(Title::newFromText($fieldsArray[$i]['Title']));
			$enumeration->setDescription(new SWString($fieldsArray[$i]['Predefined values']));
			$smartwikiModel->setEnumeration($enumeration);
		}

	}

	public function getWikiText() {
		// TODO
	}

	public function getPredefinedValues() {
		return $this->predefinedValues;
	}

	public function setPredefinedValues(SWString $predefinedValues) {
		$this->predefinedValues = $predefinedValues;
	}

}
?>