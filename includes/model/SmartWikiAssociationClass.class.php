<?php
class SmartWikiAssociationClass extends SmartWikiModelElement {
	private $indefiniteArticle;
	private $pluralName;
	private $abstract;
	private $isKnowledgeElement;
	private $canHaveDerivables;
	private $canHaveSubelements;
	private $aggregationType;
	private $fromMultiplicity;
	private $toMultiplicity;
	private $reverseName;
	private $fromClass;
	private $toClass;

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
	 * Parse an XMI file for AssociationClasses, using the given SyntaxTransformer
	 * 
	 * @param SmartWikiSyntaxTransformer $syntaxTransformer
	 * 
	 * @return An array of AssociationClasses
	 */
	public static function parseXMI(SmartWikiXmlParser $xmlParser) {

		# SmartWikiModel object
		$smartwikiModel = SmartWikiModel::singleton();

		# Search for all the associations in the parser array
		$associationClassesXMI = SmartWikiModelElement::arraySearchRecursive('UML:AssociationClass', $xmlParser->array);
	
		$xmiParser = XMIBase::getXMIParser($xmlParser);
	
		# Loop through the parsed associations
		foreach($associationClassesXMI as $key => $value) {
		
			$description = $xmiParser->getDescriptionFromTaggedValues($value['UML:ModelElement.taggedValue']['UML:TaggedValue']);
		
			# We need an xmi.id, or else it could be an reference
			if (isset($value['_']['xmi.id'])) {

				# Add a association class
				$associationClass = new SmartWikiAssociationClass();
				$associationClass->setSearchPath(new SmartWikiString($value['searchPath']));
				$associationClass->setPackageID($associationClass->getContainer($value['searchPath'], $xmlParser));
				$associationClass->setPackage($smartwikiModel->getPackageByID($associationClass->getPackageID()));
				$associationClass->setID(new SmartWikiString($value['_']['xmi.id']));
				$associationClass->setName(new SmartWikiString(isset($value['_']['name']) ? $value['_']['name'] : 'associates'));
				$associationClass->setTitle(Title::newFromText(($associationClass->getPackage() != NULL ? $associationClass->getPackage()->getTitle()->getText() . ' : ' : wfMsgForContent('smartwiki-prefix')) . $associationClass->getName()->getValue()));

				# First find out which AssociationEnd points to the from class
				#  and which AssociationEnd points to the to class
				$end1 = $value['UML:Association.connection']['UML:AssociationEnd'][0];
				$end2 = $value['UML:Association.connection']['UML:AssociationEnd'][1];
				if ($end1['_']['isNavigable'] == true) {
					$from = $end1;
					$to = $end2;
				} else {
					$from = $end2;
					$to = $end1;
				}

				# Get the multiplicities for both ends. If one doesn't excist,
				#  we will set it to the predefined value "0..*"
				$from_multiplicity = isset($from['UML:AssociationEnd.multiplicity']['UML:Multiplicity']['UML:Multiplicity.range']['UML:MultiplicityRange']['_']) ? $from['UML:AssociationEnd.multiplicity']['UML:Multiplicity']['UML:Multiplicity.range']['UML:MultiplicityRange']['_'] : array('lower' => '0', 'upper' => '*');
				$to_multiplicity = isset($to['UML:AssociationEnd.multiplicity']['UML:Multiplicity']['UML:Multiplicity.range']['UML:MultiplicityRange']['_']) ? $to['UML:AssociationEnd.multiplicity']['UML:Multiplicity']['UML:Multiplicity.range']['UML:MultiplicityRange']['_'] : array('lower' => '0', 'upper' => '*');
				$associationClass->setFromMultiplicity(new SmartWikiString((!$from_multiplicity['lower'] || $from_multiplicity['lower'] == -1 ? '*' : $from_multiplicity['lower']) . '..' . (!$from_multiplicity['upper'] || $from_multiplicity['upper'] == -1 ? '*' : $from_multiplicity['upper'])));
				$associationClass->setToMultiplicity(new SmartWikiString((!$to_multiplicity['lower'] || $to_multiplicity['lower'] == -1 ? '*' : $to_multiplicity['lower']) . '..' . (!$to_multiplicity['upper'] || $to_multiplicity['upper'] == -1 ? '*' : $to_multiplicity['upper'])));
				
				# From class (can be a class or a association class)
				if (isset($from['UML:AssociationEnd.participant']['UML:Class'])) {
					$associationClass->setFromClass($smartwikiModel->getClassByID(new SmartWikiString($from['UML:AssociationEnd.participant']['UML:Class']['_']['xmi.idref'])));
				} elseif (isset($from['UML:AssociationEnd.participant']['UML:AssociationClass'])) {
					$associationClass->setFromClass($smartwikiModel->getAssociationClassByID(new SmartWikiString($from['UML:AssociationEnd.participant']['UML:AssociationClass']['_']['xmi.idref'])));
				}

				# To class (can be a class or a association class)
				if (isset($to['UML:AssociationEnd.participant']['UML:Class'])) {
					$associationClass->setToClass($smartwikiModel->getClassByID(new SmartWikiString($to['UML:AssociationEnd.participant']['UML:Class']['_']['xmi.idref'])));
				} elseif (isset($to['UML:AssociationEnd.participant']['UML:AssociationClass'])) {
					$associationClass->setToClass($smartwikiModel->getAssociationClassByID(new SmartWikiString($to['UML:AssociationEnd.participant']['UML:AssociationClass']['_']['xmi.idref'])));
				}

				# Set the values:
				# - Use the "Aggregation type" from the XMI file
				$associationClass->setAggregationType(new SmartWikiString($to['_']['aggregation']));
				# - Use the Abstract value from the XMI file, translate: "false" => "No", "true" => "Yes"
				$associationClass->setAbstract(new SmartWikiBoolean($value['_']['isAbstract']));

				# Current article of this class, is excists, and its values
				if ($associationClass->getTitle()->isKnown()) {

					$current_values = $associationClass->getValuesFromArticle();
					
					# Set the values:
					# - Use the "Description" currently used in the article
					$associationClass->setDescription(new SmartWikiString($description));
					# - Use the container order currently used in the article
					$associationClass->setOrder(new SmartWikiNumber($current_values['Container order']));
					# - Use the "Reverse name" currently used in the article
					$associationClass->setReverseName(new SmartWikiString($current_values['Reverse name']));

					# - Use the "Indefinite article" currently used in the article, otherwise use "a" or "an"
					$associationClass->setIndefiniteArticle(new SmartWikiString($current_values['Indefinite article']));
					# - Use the "Plural name" currently used in the article
					$associationClass->setPluralName(new SmartWikiString($current_values['Plural name']));
					# - Use the "Is knowledge element" currently used in the article
					$associationClass->setIsKnowledgeElement(new SmartWikiBoolean($current_values['Is knowledge element']));
					# - Use the "Can have derivables" currently used in the article
					$associationClass->setCanHaveDerivables(new SmartWikiBoolean($current_values['Can have derivables']));
					# - Use the "Can have subelements" currently used in the article
					$associationClass->setCanHaveSubelements(new SmartWikiBoolean($current_values['Can have subelements']));

					$associationClass->setState(SmartWikiState::LOG_EDITED);

				} else {

					# Set the values:
					# - Set the "Description" to blank
					$associationClass->setDescription(new SmartWikiString($description));
					# - Set the container order to -1
					$associationClass->setOrder(new SmartWikiNumber(-1));
					# - Set the "Reverse name" to blank
					$associationClass->setReverseName(new SmartWikiString(''));
	
					# - Set the "Indefinite article" to use "a" or "an"
					$associationClass->setIndefiniteArticle(new SmartWikiString(strpos(wfMsgForContent('smartwiki-grammar-an-letters'), strtolower(substr($value['_']['name'], 0, 1))) !== false ? wfMsgForContent('smartwiki-grammar-an') : wfMsgForContent('smartwiki-grammar-a')));
					# - Set the "Plural name" to the Name + s
					$associationClass->setPluralName(new SmartWikiString($associationClass->getName() . 's'));
					# - Set the "Is knowledge element" to "Yes"
					$associationClass->setIsKnowledgeElement(new SmartWikiBoolean(FALSE));
					# - Set the "Can have derivables" to "Yes"
					$associationClass->setCanHaveDerivables(new SmartWikiBoolean(FALSE));
					# - Set the "Can have subelements" to "Yes"
					$associationClass->setCanHaveSubelements(new SmartWikiBoolean(FALSE));

					$associationClass->setState(SmartWikiState::LOG_CREATED);

				}

				$smartwikiModel->setAssociationClass($associationClass);

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
		$form_template['template'] = str_replace('%ID%', $this->getId() ? $this->getId()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%Name%', $this->getName() ? $this->getName()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%Title%', $this->getTitle() ? $this->getTitle()->getText() : '', $form_template['template']);
		$form_template['template'] = str_replace('%Description%', $this->getDescription() ? $this->getDescription()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%Container order%', $this->getOrder() ? $this->getOrder()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%Package%', $this->getPackage() ? $this->getPackage()->getTitle()->getText() : '', $form_template['template']);

		$form_template['template'] = str_replace('%Aggregation type%', $this->getAggregationType() ? $this->getAggregationType()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%From multiplicity%', $this->getFromMultiplicity() ? $this->getFromMultiplicity()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%To multiplicity%', $this->getToMultiplicity() ? $this->getToMultiplicity()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%Reverse name%', $this->getReverseName() ? $this->getReverseName()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%From class%', $this->getFromClass() ? $this->getFromClass()->getTitle()->getText() : '', $form_template['template']);
		$form_template['template'] = str_replace('%To class%', $this->getToClass() ? $this->getToClass()->getTitle()->getText() : '', $form_template['template']);

		$form_template['template'] = str_replace('%Indefinite article%', $this->getIndefiniteArticle() ? $this->getIndefiniteArticle()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%Plural name%', $this->getPluralName() ? $this->getPluralName()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%Abstract%', $this->getAbstract() ? $this->getAbstract()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%Is knowledge element%', $this->getIsKnowledgeElement() ? $this->getIsKnowledgeElement()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%Can have derivables%', $this->getCanHaveDerivables() ? $this->getCanHaveDerivables()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%Can have subelements%', $this->getCanHaveSubelements() ? $this->getCanHaveSubelements()->getWikiValue() : '', $form_template['template']);

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
			$associationClass = new SmartWikiAssociationClass();

			// SmartWikiModelElement
			$associationClass->setID(new SmartWikiString($fieldsArray[$i]['ID']));
			$associationClass->setName(new SmartWikiString($fieldsArray[$i]['Name']));
			$associationClass->setTitle(Title::newFromText($fieldsArray[$i]['Title']));
			$associationClass->setTitle($fieldsArray[$i]['titleObject']);
			$associationClass->setID(new SmartWikiString($fieldsArray[$i]['Description']));
			$associationClass->setOrder(new SmartWikiNumber($fieldsArray[$i]['Container order']));
			$associationClass->setPackage($smartwikiModel->getPackage(Title::newFromText($fieldsArray[$i]['Package'])));
			$associationClass->setDescription(new SmartWikiString($fieldsArray[$i]['Description']));
				
			// SmartWikiAssociation
			$associationClass->setAggregationType(new SmartWikiString($fieldsArray[$i]['Aggregation type']));
			$associationClass->setFromMultiplicity(new SmartWikiString($fieldsArray[$i]['From multiplicity']));
			$associationClass->setToMultiplicity(new SmartWikiString($fieldsArray[$i]['To multiplicity']));
			$associationClass->setReverseName(new SmartWikiString($fieldsArray[$i]['Reverse name']));
			$fromClassTitle = Title::newFromText($fieldsArray[$i]['From class']);
			$associationClass->setFromClass($smartwikiModel->getClass($fromClassTitle) != NULL ? $smartwikiModel->getClass($fromClassTitle) : $smartwikiModel->getAssociationClass($fromClassTitle));
			$toClassTitle = Title::newFromText($fieldsArray[$i]['To class']);
			$associationClass->setToClass($smartwikiModel->getClass($toClassTitle) != NULL ? $smartwikiModel->getClass($toClassTitle) : $smartwikiModel->getAssociationClass($toClassTitle));
			
			// SmartWikiClass
			$associationClass->setIndefiniteArticle(new SmartWikiString($fieldsArray[$i]['Indefinite article']));
			$associationClass->setPluralName(new SmartWikiString($fieldsArray[$i]['Plural name']));
			$associationClass->setAbstract(new SmartWikiBoolean($fieldsArray[$i]['Abstract']));
			$associationClass->setIsKnowledgeElement(new SmartWikiBoolean($fieldsArray[$i]['Is knowledge element']));
			$associationClass->setCanHaveDerivables(new SmartWikiBoolean($fieldsArray[$i]['Can have derivables']));
			$associationClass->setCanHaveSubelements(new SmartWikiBoolean($fieldsArray[$i]['Can have subelements']));

			$smartwikiModel->setAssociationClass($associationClass);

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

	public function getAggregationType() {
		return $this->aggregationType;
	}

	public function setAggregationType(SmartWikiString $aggregationType) {
		$this->aggregationType = $aggregationType;
	}

	public function getFromMultiplicity() {
		return $this->fromMultiplicity;
	}

	public function setFromMultiplicity(SmartWikiString $fromMultiplicity) {
		$this->fromMultiplicity = $fromMultiplicity;
	}

	public function getToMultiplicity() {
		return $this->toMultiplicity;
	}

	public function setToMultiplicity(SmartWikiString $toMultiplicity) {
		$this->toMultiplicity = $toMultiplicity;
	}

	public function getReverseName() {
		return $this->reverseName;
	}
	
	public function setReverseName(SmartWikiString $reverseName) {
		$this->reverseName = $reverseName;
	}

	public function getFromClass() {
		return $this->fromClass;
	}
	
	public function setFromClass(SmartWikiModelElement $fromClass = NULL) {
		$this->fromClass = $fromClass;
	}
	
	public function getToClass() {
		return $this->toClass;
	}
	
	public function setToClass(SmartWikiModelElement $toClass = NULL) {
		$this->toClass = $toClass;
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

	public function getFromAssociations() {
		return $this->fromAssociations;
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

}
?>