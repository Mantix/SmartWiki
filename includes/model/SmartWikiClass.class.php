<?php
class SmartWikiClass extends SmartWikiModelElement {
	private $indefiniteArticle;
	private $pluralName;
	private $abstract;
	private $isKnowledgeElement;
	private $canHaveDerivables;
	private $canHaveSubelements;

	// Used during create
	private $childGeneralizations;
	private $parentGeneralizations;
	private $attributes;
	private $fromAssociations;
	private $toAssociations;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Parse an XMI file for Classes, using the given SyntaxTransformer
	 * 
	 * @param SmartWikiSyntaxTransformer $syntaxTransformer
	 * 
	 * @return An array of Classes
	 */
	public static function parseXMI(SmartWikiXmlParser $xmlParser) {

		# SmartWikiModel object
		$smartwikiModel = SmartWikiModel::singleton();

		# Search for all the packages in the parser array
		$classesXMI = SmartWikiModelElement::arraySearchRecursive('UML:Class', $xmlParser->array);
	
		$xmiParser = XMIBase::getXMIParser($xmlParser);

		# Loop through the parsed classes
		foreach($classesXMI as $key => $value) {

			# We need an xmi.id, or else it is an reference
			if (isset($value['_']['xmi.id'])) {

				# Add a class
				$class = new SmartWikiClass();
				$class->setSearchPath(new SmartWikiString($value['searchPath']));
				$class->setPackageID($class->getContainer($value['searchPath'], $xmlParser));
				$class->setPackage($smartwikiModel->getPackageByID($class->getPackageID()));
				$class->setTitle(Title::newFromText(($class->getPackage() != NULL ? $class->getPackage()->getTitle()->getText() . ' : ' : wfMsgForContent('smartwiki-prefix')) . $value['_']['name']));

				# Set the values:
				# - Use the ID from the XMI file
				$class->setID(new SmartWikiString($value['_']['xmi.id']));
				# - Use the Name from the XMI file
				$class->setName(new SmartWikiString($value['_']['name']));
				
				if ( isset($value['UML:ModelElement.taggedValue']['UML:TaggedValue']) ) {
					$description = $xmiParser->getDescriptionFromTaggedValues($value['UML:ModelElement.taggedValue']['UML:TaggedValue']);
				} else {
					$description = "";
				}

				# Current article of this class, is excists, and its values
				if ($class->getTitle()->isKnown()) {

					$current_values = $class->getValuesFromArticle();
					
					# - Use the "Description" currently used in the article
					$class->setDescription(new SmartWikiString($description));
					# - Use the container order currently used in the article
					$class->setOrder(new SmartWikiNumber($current_values['Container order']));
					# - Use the "Indefinite article" currently used in the article, otherwise use "a" or "an"
					$class->setIndefiniteArticle(new SmartWikiString($current_values['Indefinite article']));
					# - Use the "Plural name" currently used in the article
					$class->setPluralName(new SmartWikiString($current_values['Plural name']));
					# - Use the Abstract value from the XMI file, translate: "false" => "No", "true" => "Yes"
					$class->setAbstract(new SmartWikiBoolean($value['_']['isAbstract']));
					# - Use the "Is knowledge element" currently used in the article, otherwise set to "Yes"
					$class->setIsKnowledgeElement(new SmartWikiBoolean($current_values['Is knowledge element']));
					# - Use the "Can have derivables" currently used in the article, otherwise set to "Yes"
					$class->setCanHaveDerivables(new SmartWikiBoolean($current_values['Can have derivables']));
					# - Use the "Can have subelements" currently used in the article, otherwise set to "Yes"
					$class->setCanHaveSubelements(new SmartWikiBoolean($current_values['Can have subelements']));

					$class->setState(SmartWikiState::LOG_EDITED);

				} else {

					# - Set the "Description" to blank
					$class->setDescription(new SmartWikiString($description));
					# - Set the container order to -1
					$class->setOrder(new SmartWikiNumber(-1));
					# - Set the "Indefinite article" to use "a" or "an"
					$class->setIndefiniteArticle(new SmartWikiString(strpos(wfMsgForContent('smartwiki-grammar-an-letters'), strtolower(substr($value['_']['name'], 0, 1))) !== false ? wfMsgForContent('smartwiki-grammar-an') : wfMsgForContent('smartwiki-grammar-a')));
					# - Set the "Plural name" to the Name + s
					$class->setPluralName(new SmartWikiString($class->getName()->getValue() . 's'));
					# - Set the Abstract value to "No"
					$class->setAbstract(new SmartWikiBoolean($value['_']['isAbstract'])/*new SmartWikiBoolean(TRUE)*/);
					# - Set the "Is knowledge element" to "Yes"
					$class->setIsKnowledgeElement(new SmartWikiBoolean(TRUE));
					# - Set the "Can have derivables" to "Yes"
					$class->setCanHaveDerivables(new SmartWikiBoolean(TRUE));
					# - Set the "Can have subelements" to "Yes"
					$class->setCanHaveSubelements(new SmartWikiBoolean(TRUE));

					$class->setState(SmartWikiState::LOG_CREATED);

				}

				$smartwikiModel->setClass($class);

			}

		}

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
		$form_template['template'] = str_replace('%ID%', $this->getId()->getWikiValue(), $form_template['template']);
		$form_template['template'] = str_replace('%Name%', $this->getName()->getWikiValue(), $form_template['template']);
		$form_template['template'] = str_replace('%Title%', $this->getTitle()->getText(), $form_template['template']);
		$form_template['template'] = str_replace('%Description%', $this->getDescription()->getWikiValue(), $form_template['template']);
		$form_template['template'] = str_replace('%Container order%', $this->getOrder()->getWikiValue(), $form_template['template']);
		$form_template['template'] = str_replace('%Package%', $this->getPackage()->getTitle()->getText(), $form_template['template']);

		$form_template['template'] = str_replace('%Indefinite article%', $this->getIndefiniteArticle()->getWikiValue(), $form_template['template']);
		$form_template['template'] = str_replace('%Plural name%', $this->getPluralName()->getWikiValue(), $form_template['template']);
		$form_template['template'] = str_replace('%Abstract%', $this->getAbstract()->getWikiValue(), $form_template['template']);
		$form_template['template'] = str_replace('%Is knowledge element%', $this->getIsKnowledgeElement()->getWikiValue(), $form_template['template']);
		$form_template['template'] = str_replace('%Can have derivables%', $this->getCanHaveDerivables()->getWikiValue(), $form_template['template']);
		$form_template['template'] = str_replace('%Can have subelements%', $this->getCanHaveSubelements()->getWikiValue(), $form_template['template']);

		# Fill the page with the content
		SmartWikiPage::editPage($article, $form_template['template'] . $this->getContent(), '(Re)created ' . $this->getTitle()->getText());

		# Return the Title object
		return $this->getTitle();
	}

	/**
	 * Create an array of Class objects, fill it with the fields provided
	 * 
	 * @param $fieldsArray - An array of fields
	 * 
	 * @return $classArray - An array of classes
	 */
	public static function fill($fieldsArray) {

		# SmartWikiModel object
		$smartwikiModel = SmartWikiModel::singleton();

		for ($i = 0; $i < count($fieldsArray); $i++) {
			$class = new SmartWikiClass();
			// SmartWikiModelElement
			$class->setID(new SmartWikiString($fieldsArray[$i]['ID']));
			$class->setName(new SmartWikiString($fieldsArray[$i]['Name']));
			//$class->setTitle(Title::newFromText($fieldsArray[$i]['Title']));
			$class->setTitle($fieldsArray[$i]['titleObject']);
			$class->setDescription(new SmartWikiString($fieldsArray[$i]['Description']));
			$class->setOrder(new SmartWikiNumber($fieldsArray[$i]['Container order']));
			$class->setPackage($smartwikiModel->getPackage(Title::newFromText($fieldsArray[$i]['Package'])));

			// SmartWikiClass
			$class->setIndefiniteArticle(new SmartWikiString($fieldsArray[$i]['Indefinite article']));
			$class->setPluralName(new SmartWikiString($fieldsArray[$i]['Plural name']));
			$class->setAbstract(new SmartWikiBoolean($fieldsArray[$i]['Abstract']));
			$class->setIsKnowledgeElement(new SmartWikiBoolean($fieldsArray[$i]['Is knowledge element']));
			$class->setCanHaveDerivables(new SmartWikiBoolean($fieldsArray[$i]['Can have derivables']));
			$class->setCanHaveSubelements(new SmartWikiBoolean($fieldsArray[$i]['Can have subelements']));

			$smartwikiModel->setClass($class);

		}

	}

	public function getWikiText() {
		// TODO
	}

	public function getIndefiniteArticle() {
		return $this->indefiniteArticle;
	}

	public function setIndefiniteArticle(SmartWikiString $indefiniteArticle) {
		$this->indefiniteArticle = $indefiniteArticle;
	}

	public function getPluralName() {
		return $this->pluralName;
	}

	public function setPluralName(SmartWikiString $pluralName) {
		$this->pluralName = $pluralName;
	}

	public function getAbstract() {
		return $this->abstract;
	}

	public function setAbstract(SmartWikiBoolean $abstract) {
		$this->abstract = $abstract;
	}

	public function getIsKnowledgeElement() {
		return $this->isKnowledgeElement;
	}

	public function setIsKnowledgeElement(SmartWikiBoolean $isKnowledgeElement) {
		$this->isKnowledgeElement = $isKnowledgeElement;
	}

	public function getCanHaveDerivables() {
		return $this->canHaveDerivables;
	}

	public function setCanHaveDerivables(SmartWikiBoolean $canHaveDerivables) {
		$this->canHaveDerivables = $canHaveDerivables;
	}

	public function getCanHaveSubelements() {
		return $this->canHaveSubelements;
	}

	public function setCanHaveSubelements(SmartWikiBoolean $canHaveSubelements) {
		$this->canHaveSubelements = $canHaveSubelements;
	}

	// Used during create
	public function getChildGeneralizations() {
		return $this->childGeneralizations;
	}

	public function setChildGeneralizations($childGeneralizations) {
		$this->childGeneralizations = $childGeneralizations;
	}

	public function getParentGeneralizations() {
		return $this->parentGeneralizations;
	}

	public function setParentGeneralizations($parentGeneralizations) {
		$this->parentGeneralizations = $parentGeneralizations;
	}

	public function getAttributes() {
		return $this->attributes;
	}

	public function setAttributes($attributes) {
		$this->attributes = $attributes;
	}

	public function getFromAssociations($includeParentAssociations = false) {
		$myAssociations = $this->fromAssociations;
		if ( !$includeParentAssociations ) {
			return $myAssociations;
		}
		if ( !is_array($myAssociations) ) {
			$myAssociations = array();
		}
		$myParentAssociations = array();
		//self::getParentAssociations($this, $myParentAssociations);
		$parents = $this->getParentGeneralizations();
		if ( is_array($parents) ) {
			for($i=0;$i<count($parents);$i++) {
				$thisParentAssociations = $parents[$i]->getParentClass()->getFromAssociations(true);
				if ( is_array($thisParentAssociations) ) {
					$myParentAssociations = array_merge($myParentAssociations, $thisParentAssociations);
				}
			}
		}
		return array_merge($myAssociations, $myParentAssociations);
	}

	public function setFromAssociations($fromAssociations) {
		$this->fromAssociations = $fromAssociations;
	}

	public function getToAssociations() {
		return $this->toAssociations;
	}

	public function setToAssociations($toAssociations) {
		$this->toAssociations = $toAssociations;
	}
	
	public function getParentClasses() {
		$result = array();
		$genParents = $this->parentGeneralizations;
		if ( is_array($genParents) ) {
			for($i=0;$i<count($genParents);$i++) {
				$cls = $genParents[$i]->getParentClass();
				$result[] = $cls;
				$thisParentClasses = $cls->getParentClasses();
				if ( is_array($thisParentClasses) ) {
					$result = array_merge($result, $thisParentClasses);
				}
			}
		}
		return $result;
		
	}

	
}
?>