<?php
class SmartWikiAssociation extends SmartWikiModelElement {
	private $aggregationType;
	private $fromMultiplicity;
	private $toMultiplicity;
	private $reverseName;
	private $fromClass;
	private $toClass;
	
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Parse an XMI file for Associations, using the given SyntaxTransformer
	 * 
	 * @param SmartWikiSyntaxTransformer $syntaxTransformer
	 * 
	 * @return An array of Associations
	 */
	public static function parseXMI(SmartWikiXmlParser $xmlParser) {

		# SmartWikiModel object
		$smartwikiModel = SmartWikiModel::singleton();

		# Search for all the associations in the parser array
		$associationXMI = SmartWikiModelElement::arraySearchRecursive('UML:Association', $xmlParser->array);

		$xmiParser = XMIBase::getXMIParser($xmlParser);
	
		# Loop through the parsed associations
		foreach($associationXMI as $key => $value) {

			# We need an xmi.id, or else it could be an reference
			if (isset($value['_']['xmi.id'])) {

				if ( isset($value['UML:ModelElement.taggedValue']['UML:TaggedValue']) ) {
					$description = $xmiParser->getDescriptionFromTaggedValues($value['UML:ModelElement.taggedValue']['UML:TaggedValue']);
				} else {
					$description = "";
				}
				# Add a association class
				$association = new SmartWikiAssociation();
				$association->setSearchPath(new SmartWikiString($value['searchPath']));
				$association->setPackageID($association->getContainer($value['searchPath'], $xmlParser));
				$association->setPackage($smartwikiModel->getPackageByID($association->getPackageID()));
				$association->setID(new SmartWikiString($value['_']['xmi.id']));
				$association->setName(new SmartWikiString(isset($value['_']['name']) ? $value['_']['name'] : 'associates'));

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
				$association->setFromMultiplicity(new SmartWikiString(($from_multiplicity['lower'] == -1 ? '*' : $from_multiplicity['lower']) . '..' . ($from_multiplicity['upper'] == -1 ? '*' : $from_multiplicity['upper'])));
				$association->setToMultiplicity(new SmartWikiString(($to_multiplicity['lower'] == -1 ? '*' : $to_multiplicity['lower']) . '..' . ($to_multiplicity['upper'] == -1 ? '*' : $to_multiplicity['upper'])));

				/*
				echo $value['_']['name'] ." = ".var_export($from_multiplicity,1)." .. ".var_export($to_multiplicity,1).
					$association->getFromMultiplicity()." , ".$association->getToMultiplicity().
				"<BR>";
				*/
				
				# From class (can be a class or a association class)
				if (isset($from['UML:AssociationEnd.participant']['UML:Class'])) {
					$association->setFromClass($smartwikiModel->getClassByID(new SmartWikiString($from['UML:AssociationEnd.participant']['UML:Class']['_']['xmi.idref'])));
				} elseif (isset($from['UML:AssociationEnd.participant']['UML:AssociationClass'])) {
					$association->setFromClass($smartwikiModel->getAssociationClassByID(new SmartWikiString($from['UML:AssociationEnd.participant']['UML:AssociationClass']['_']['xmi.idref'])));
				} 

				# To class (can be a class or a association class)
				if (isset($to['UML:AssociationEnd.participant']['UML:Class'])) {
					$association->setToClass($smartwikiModel->getClassByID(new SmartWikiString($to['UML:AssociationEnd.participant']['UML:Class']['_']['xmi.idref'])));
				} elseif (isset($to['UML:AssociationEnd.participant']['UML:AssociationClass'])) {
					$association->setToClass($smartwikiModel->getAssociationClassByID(new SmartWikiString($to['UML:AssociationEnd.participant']['UML:AssociationClass']['_']['xmi.idref'])));
				}

				$association->setTitle(Title::newFromText(($association->getPackage() != NULL ? $association->getPackage()->getTitle()->getText() . ' : ' : wfMsgForContent('smartwiki-prefix')) .
					$association->getFromClass()->getName() . " " . 
					$association->getName()->getValue() . " " .
					$association->getToClass()->getName()
				));
				
				# Set the values:
				# - Use the "Aggregation type" from the XMI file
				$association->setAggregationType(new SmartWikiString($to['_']['aggregation']));

				# Current article of this class, is excists, and its values
				if ($association->getTitle()->isKnown()) {

					$current_values = $association->getValuesFromArticle();
					
					# Set the values:
					# - Use the "Description" currently used in the article
					$association->setDescription(new SmartWikiString($description));
					# - Use the container order currently used in the article
					$association->setOrder(new SmartWikiNumber($current_values['Container order']));
					# - Use the "Reverse name" currently used in the article
					$association->setReverseName(new SmartWikiString($current_values['Reverse name']));

					$association->setState(SmartWikiState::LOG_EDITED);

				} else {

					# Set the values:
					# - Set the "Description" to blank
					$association->setDescription(new SmartWikiString($description));
					# - Set the container order to -1
					$association->setOrder(new SmartWikiNumber(-1));
					# - Set the "Reverse name" to blank
					$association->setReverseName(new SmartWikiString(''));
	
					$association->setState(SmartWikiState::LOG_CREATED);

				}

				$smartwikiModel->setAssociation($association);

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
			$association = new SmartWikiAssociation();

			// SmartWikiModelElement
			$association->setID(new SmartWikiString($fieldsArray[$i]['ID']));
			$association->setName(new SmartWikiString($fieldsArray[$i]['Name']));
			//$association->setTitle(Title::newFromText($fieldsArray[$i]['Title']));
			$association->setTitle($fieldsArray[$i]['titleObject']);
			$association->setDescription(new SmartWikiString($fieldsArray[$i]['Description']));
			$association->setOrder(new SmartWikiNumber($fieldsArray[$i]['Container order']));
			$association->setPackage($smartwikiModel->getPackage(Title::newFromText($fieldsArray[$i]['Package'])));

			// SmartWikiAssociation
			$association->setAggregationType(new SmartWikiString($fieldsArray[$i]['Aggregation type']));
			$association->setFromMultiplicity(new SmartWikiString($fieldsArray[$i]['From multiplicity']));
			$association->setToMultiplicity(new SmartWikiString($fieldsArray[$i]['To multiplicity']));
			$association->setReverseName(new SmartWikiString($fieldsArray[$i]['Reverse name']));
			$fromClassTitle = Title::newFromText($fieldsArray[$i]['From class']);
			$association->setFromClass($smartwikiModel->getClass($fromClassTitle) != NULL ? $smartwikiModel->getClass($fromClassTitle) : $smartwikiModel->getAssociationClass($fromClassTitle));
			$toClassTitle = Title::newFromText($fieldsArray[$i]['To class']);
			$association->setToClass($smartwikiModel->getClass($toClassTitle) != NULL ? $smartwikiModel->getClass($toClassTitle) : $smartwikiModel->getAssociationClass($toClassTitle));

			$smartwikiModel->setAssociation($association);

		}

	}

	public function getWikiText() {
		// TODO
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

	public function setFromClass($fromClass = NULL) {
		if ( !in_array(get_class($fromClass), array("SmartWikiClass", "SmartWikiAssociationClass")) ) {
			throw new Exception("Bad class (".(get_class($fromClass)).")");
			echo "Invalid setFromClass call (".get_class($fromClass).") in ".$this->getTitle()->getText()."<BR>";
			return;
		}
		$this->fromClass = $fromClass;
	}

	public function getToClass() {
		return $this->toClass;
	}

	public function setToClass(SmartWikiClass $toClass = NULL) {
		$this->toClass = $toClass;
	}

}
?>