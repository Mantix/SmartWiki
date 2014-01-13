<?php
class SWAssociationClass extends SWModelElement {
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
	 * @param SWSyntaxTransformer $syntaxTransformer
	 * 
	 * @return An array of AssociationClasses
	 */
	public static function parseXMI(SWXmlParser $xmlParser) {

		# SWModel object
		$smartwikiModel = SWModel::singleton();

		# Search for all the associations in the parser array
		$associationClassesXMI = SWModelElement::arraySearchRecursive('UML:AssociationClass', $xmlParser->array);
	
		$xmiParser = XMIBase::getXMIParser($xmlParser);
	
		# Loop through the parsed associations
		foreach($associationClassesXMI as $key => $value) {
		
			$description = $xmiParser->getDescriptionFromTaggedValues($value['UML:ModelElement.taggedValue']['UML:TaggedValue']);
		
			# We need an xmi.id, or else it could be an reference
			if (isset($value['_']['xmi.id'])) {

				# Add a association class
				$associationClass = new SWAssociationClass();
				$associationClass->setSearchPath(new SWString($value['searchPath']));
				$associationClass->setPackageID($associationClass->getContainer($value['searchPath'], $xmlParser));
				$associationClass->setPackage($smartwikiModel->getPackageByID($associationClass->getPackageID()));
				$associationClass->setID(new SWString($value['_']['xmi.id']));
				$associationClass->setName(new SWString(isset($value['_']['name']) ? $value['_']['name'] : 'associates'));
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
				$associationClass->setFromMultiplicity(new SWString((!$from_multiplicity['lower'] || $from_multiplicity['lower'] == -1 ? '*' : $from_multiplicity['lower']) . '..' . (!$from_multiplicity['upper'] || $from_multiplicity['upper'] == -1 ? '*' : $from_multiplicity['upper'])));
				$associationClass->setToMultiplicity(new SWString((!$to_multiplicity['lower'] || $to_multiplicity['lower'] == -1 ? '*' : $to_multiplicity['lower']) . '..' . (!$to_multiplicity['upper'] || $to_multiplicity['upper'] == -1 ? '*' : $to_multiplicity['upper'])));
				
				# From class (can be a class or a association class)
				if (isset($from['UML:AssociationEnd.participant']['UML:Class'])) {
					$associationClass->setFromClass($smartwikiModel->getClassByID(new SWString($from['UML:AssociationEnd.participant']['UML:Class']['_']['xmi.idref'])));
				} elseif (isset($from['UML:AssociationEnd.participant']['UML:AssociationClass'])) {
					$associationClass->setFromClass($smartwikiModel->getAssociationClassByID(new SWString($from['UML:AssociationEnd.participant']['UML:AssociationClass']['_']['xmi.idref'])));
				}

				# To class (can be a class or a association class)
				if (isset($to['UML:AssociationEnd.participant']['UML:Class'])) {
					$associationClass->setToClass($smartwikiModel->getClassByID(new SWString($to['UML:AssociationEnd.participant']['UML:Class']['_']['xmi.idref'])));
				} elseif (isset($to['UML:AssociationEnd.participant']['UML:AssociationClass'])) {
					$associationClass->setToClass($smartwikiModel->getAssociationClassByID(new SWString($to['UML:AssociationEnd.participant']['UML:AssociationClass']['_']['xmi.idref'])));
				}

				# Set the values:
				# - Use the "Aggregation type" from the XMI file
				$associationClass->setAggregationType(new SWString($to['_']['aggregation']));
				# - Use the Abstract value from the XMI file, translate: "false" => "No", "true" => "Yes"
				$associationClass->setAbstract(new SWBoolean($value['_']['isAbstract']));

				# Current article of this class, is excists, and its values
				if ($associationClass->getTitle()->isKnown()) {

					$current_values = $associationClass->getValuesFromArticle();
					
					# Set the values:
					# - Use the "Description" currently used in the article
					$associationClass->setDescription(new SWString($description));
					# - Use the container order currently used in the article
					$associationClass->setOrder(new SWNumber($current_values['Container order']));
					# - Use the "Reverse name" currently used in the article
					$associationClass->setReverseName(new SWString($current_values['Reverse name']));

					# - Use the "Indefinite article" currently used in the article, otherwise use "a" or "an"
					$associationClass->setIndefiniteArticle(new SWString($current_values['Indefinite article']));
					# - Use the "Plural name" currently used in the article
					$associationClass->setPluralName(new SWString($current_values['Plural name']));
					# - Use the "Is knowledge element" currently used in the article
					$associationClass->setIsKnowledgeElement(new SWBoolean($current_values['Is knowledge element']));
					# - Use the "Can have derivables" currently used in the article
					$associationClass->setCanHaveDerivables(new SWBoolean($current_values['Can have derivables']));
					# - Use the "Can have subelements" currently used in the article
					$associationClass->setCanHaveSubelements(new SWBoolean($current_values['Can have subelements']));

					$associationClass->setState(SWState::LOG_EDITED);

				} else {

					# Set the values:
					# - Set the "Description" to blank
					$associationClass->setDescription(new SWString($description));
					# - Set the container order to -1
					$associationClass->setOrder(new SWNumber(-1));
					# - Set the "Reverse name" to blank
					$associationClass->setReverseName(new SWString(''));
	
					# - Set the "Indefinite article" to use "a" or "an"
					$associationClass->setIndefiniteArticle(new SWString(strpos(wfMsgForContent('smartwiki-grammar-an-letters'), strtolower(substr($value['_']['name'], 0, 1))) !== false ? wfMsgForContent('smartwiki-grammar-an') : wfMsgForContent('smartwiki-grammar-a')));
					# - Set the "Plural name" to the Name + s
					$associationClass->setPluralName(new SWString($associationClass->getName() . 's'));
					# - Set the "Is knowledge element" to "Yes"
					$associationClass->setIsKnowledgeElement(new SWBoolean(FALSE));
					# - Set the "Can have derivables" to "Yes"
					$associationClass->setCanHaveDerivables(new SWBoolean(FALSE));
					# - Set the "Can have subelements" to "Yes"
					$associationClass->setCanHaveSubelements(new SWBoolean(FALSE));

					$associationClass->setState(SWState::LOG_CREATED);

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
		SWHelper::editPage($article, $form_template['template'] . $this->getContent(), '(Re)created ' . $this->getTitle()->getText());

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

		# SWModel object
		$smartwikiModel = SWModel::singleton();

		for ($i = 0; $i < count($fieldsArray); $i++) {
			$associationClass = new SWAssociationClass();

			// SWModelElement
			$associationClass->setID(new SWString($fieldsArray[$i]['ID']));
			$associationClass->setName(new SWString($fieldsArray[$i]['Name']));
			$associationClass->setTitle(Title::newFromText($fieldsArray[$i]['Title']));
			$associationClass->setTitle($fieldsArray[$i]['titleObject']);
			$associationClass->setID(new SWString($fieldsArray[$i]['Description']));
			$associationClass->setOrder(new SWNumber($fieldsArray[$i]['Container order']));
			$associationClass->setPackage($smartwikiModel->getPackage(Title::newFromText($fieldsArray[$i]['Package'])));
			$associationClass->setDescription(new SWString($fieldsArray[$i]['Description']));
				
			// SWAssociation
			$associationClass->setAggregationType(new SWString($fieldsArray[$i]['Aggregation type']));
			$associationClass->setFromMultiplicity(new SWString($fieldsArray[$i]['From multiplicity']));
			$associationClass->setToMultiplicity(new SWString($fieldsArray[$i]['To multiplicity']));
			$associationClass->setReverseName(new SWString($fieldsArray[$i]['Reverse name']));
			$fromClassTitle = Title::newFromText($fieldsArray[$i]['From class']);
			$associationClass->setFromClass($smartwikiModel->getClass($fromClassTitle) != NULL ? $smartwikiModel->getClass($fromClassTitle) : $smartwikiModel->getAssociationClass($fromClassTitle));
			$toClassTitle = Title::newFromText($fieldsArray[$i]['To class']);
			$associationClass->setToClass($smartwikiModel->getClass($toClassTitle) != NULL ? $smartwikiModel->getClass($toClassTitle) : $smartwikiModel->getAssociationClass($toClassTitle));
			
			// SWClass
			$associationClass->setIndefiniteArticle(new SWString($fieldsArray[$i]['Indefinite article']));
			$associationClass->setPluralName(new SWString($fieldsArray[$i]['Plural name']));
			$associationClass->setAbstract(new SWBoolean($fieldsArray[$i]['Abstract']));
			$associationClass->setIsKnowledgeElement(new SWBoolean($fieldsArray[$i]['Is knowledge element']));
			$associationClass->setCanHaveDerivables(new SWBoolean($fieldsArray[$i]['Can have derivables']));
			$associationClass->setCanHaveSubelements(new SWBoolean($fieldsArray[$i]['Can have subelements']));

			$smartwikiModel->setAssociationClass($associationClass);

		}

	}

	public function getWikiText() {
		// TODO
	}

	public function getIndefiniteArticle() {
		return $this->indefiniteArticle;
	}

	public function setIndefiniteArticle(SWString $indefiniteArticle) {
		$this->indefiniteArticle = $indefiniteArticle;
	}

	public function getPluralName() {
		return $this->pluralName;
	}

	public function setPluralName(SWString $pluralName) {
		$this->pluralName = $pluralName;
	}

	public function getAbstract() {
		return $this->abstract;
	}

	public function setAbstract(SWBoolean $abstract) {
		$this->abstract = $abstract;
	}

	public function getIsKnowledgeElement() {
		return $this->isKnowledgeElement;
	}

	public function setIsKnowledgeElement(SWBoolean $isKnowledgeElement) {
		$this->isKnowledgeElement = $isKnowledgeElement;
	}

	public function getCanHaveDerivables() {
		return $this->canHaveDerivables;
	}

	public function setCanHaveDerivables(SWBoolean $canHaveDerivables) {
		$this->canHaveDerivables = $canHaveDerivables;
	}

	public function getCanHaveSubelements() {
		return $this->canHaveSubelements;
	}

	public function setCanHaveSubelements(SWBoolean $canHaveSubelements) {
		$this->canHaveSubelements = $canHaveSubelements;
	}

	public function getAggregationType() {
		return $this->aggregationType;
	}

	public function setAggregationType(SWString $aggregationType) {
		$this->aggregationType = $aggregationType;
	}

	public function getFromMultiplicity() {
		return $this->fromMultiplicity;
	}

	public function setFromMultiplicity(SWString $fromMultiplicity) {
		$this->fromMultiplicity = $fromMultiplicity;
	}

	public function getToMultiplicity() {
		return $this->toMultiplicity;
	}

	public function setToMultiplicity(SWString $toMultiplicity) {
		$this->toMultiplicity = $toMultiplicity;
	}

	public function getReverseName() {
		return $this->reverseName;
	}
	
	public function setReverseName(SWString $reverseName) {
		$this->reverseName = $reverseName;
	}

	public function getFromClass() {
		return $this->fromClass;
	}
	
	public function setFromClass(SWModelElement $fromClass = NULL) {
		$this->fromClass = $fromClass;
	}
	
	public function getToClass() {
		return $this->toClass;
	}
	
	public function setToClass(SWModelElement $toClass = NULL) {
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