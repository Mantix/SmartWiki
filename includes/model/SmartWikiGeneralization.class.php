<?php
class SmartWikiGeneralization extends SmartWikiModelElement {
	private $childOrder;
	private $parentClassID;
	private $parentClass;
	private $childClassID;
	private $childClass;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Parse an XMI file for Generalizations, using the given SyntaxTransformer
	 * 
	 * @param SmartWikiSyntaxTransformer $syntaxTransformer
	 * 
	 * @return An array of Generalizations
	 */
	public static function parseXMI(SmartWikiXmlParser $xmlParser) {

		# SmartWikiModel object
		$smartwikiModel = SmartWikiModel::singleton();

		# Search for all the generalizations in the parser array
		$generalizationsXMI = SmartWikiModelElement::arraySearchRecursive('UML:Generalization', $xmlParser->array);

		$xmiParser = XMIBase::getXMIParser($xmlParser);
	
		# Loop through the parsed associations
		foreach($generalizationsXMI as $key => $value) {

			# We need an xmi.id, or else it could be an reference
			if (isset($value['_']['xmi.id'])) {

				$description = $xmiParser->getDescriptionFromTaggedValues($value['UML:ModelElement.taggedValue']['UML:TaggedValue']);
		
				# Add a generalization
				$generalization = new SmartWikiGeneralization();
				$generalization->setSearchPath(new SmartWikiString($value['searchPath']));
				$generalization->setPackageID($generalization->getContainer($value['searchPath'], $xmlParser));
				$generalization->setPackage($smartwikiModel->getPackageByID($generalization->getPackageID()));

				# Get the parent and the child
				$generalization->setParentClassID(new SmartWikiString($value['UML:Generalization.parent']['UML:Class']['_']['xmi.idref']));
				$generalization->setParentClass($smartwikiModel->getClassByID($generalization->getParentClassID()));
				$generalization->setChildClassID(new SmartWikiString($value['UML:Generalization.child']['UML:Class']['_']['xmi.idref']));
				$generalization->setChildClass($smartwikiModel->getClassByID($generalization->getChildClassID()));

				# Build the name and the title
				$generalization->setID(new SmartWikiString($value['_']['xmi.id']));
				$generalization->setName(new SmartWikiString($generalization->getChildClass()->getName()->getValue() . ' ' . (isset($value['_']['name']) ? $value['_']['name'] : 'generalizes') . ' ' . $generalization->getParentClass()->getName()->getValue()));
				$generalization->setTitle(Title::newFromText(($generalization->getPackage() != NULL ? $generalization->getPackage()->getTitle()->getText() . ' : ' : wfMsgForContent('smartwiki-prefix')) . $generalization->getName()));

				# Current article of this class, is excists, and its values
				if ($generalization->getTitle()->isKnown()) {

					$current_values = $generalization->getValuesFromArticle();

					# Set the values:
					# - Use the "Description" currently used in the article
					$generalization->setDescription(new SmartWikiString($description));
					# - Use the "Child order" currently used in the article
					$generalization->setChildOrder(new SmartWikiNumber($current_values['Child order']));
					# - Use the "Container order" currently used in the article
					$generalization->setOrder(new SmartWikiNumber($current_values['Container order']));

					$generalization->setState(SmartWikiState::LOG_EDITED);

				} else {

					# Set the values:
					# - Set the "Description" to blank
					$generalization->setDescription(new SmartWikiString($description));
					# - Set the "Child order" to -1
					$generalization->setChildOrder(new SmartWikiNumber(-1));
					# - Set the "Container order" to -1
					$generalization->setOrder(new SmartWikiNumber(-1));

					$generalization->setState(SmartWikiState::LOG_CREATED);

				}

				$smartwikiModel->setGeneralization($generalization);

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

		$form_template['template'] = str_replace('%Child order%', $this->getChildOrder()->getWikiValue(), $form_template['template']);
		$form_template['template'] = str_replace('%Parent class%', $this->getParentClass()->getTitle()->getText(), $form_template['template']);
		$form_template['template'] = str_replace('%Child class%', $this->getChildClass()->getTitle()->getText(), $form_template['template']);

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
			$generalization = new SmartWikiGeneralization();

			// SmartWikiModelElement
			$generalization->setID(new SmartWikiString($fieldsArray[$i]['ID']));
			$generalization->setName(new SmartWikiString($fieldsArray[$i]['Name']));
			//$generalization->setTitle(Title::newFromText($fieldsArray[$i]['Title']));
			$generalization->setTitle($fieldsArray[$i]['titleObject']);
			$generalization->setID(new SmartWikiString($fieldsArray[$i]['Description']));
			$generalization->setOrder(new SmartWikiNumber($fieldsArray[$i]['Container order']));
			$generalization->setPackage($smartwikiModel->getPackage(Title::newFromText($fieldsArray[$i]['Package'])));
			$generalization->setDescription(new SmartWikiString($fieldsArray[$i]['Description']));
				
			// SmartWikiGeneralization
			$generalization->setChildOrder(new SmartWikiNumber($fieldsArray[$i]['Child order']));
			$generalization->setParentClass($smartwikiModel->getClass(Title::newFromText($fieldsArray[$i]['Parent class'])));
			$generalization->setChildClass($smartwikiModel->getClass(Title::newFromText($fieldsArray[$i]['Child class'])));

			$smartwikiModel->setGeneralization($generalization);

		}

	}

	public function getWikiText() {
		// TODO
	}

	public function getChildOrder() {
		return $this->childOrder;
	}

	public function setChildOrder(SmartWikiNumber $childOrder) {
		$this->childOrder = $childOrder;
	}

	public function getParentClassID() {
		return $this->parentClassID;
	}

	public function setParentClassID(SmartWikiString $parentClassID) {
		$this->parentClassID = $parentClassID;
	}

	public function getParentClass() {
		return $this->parentClass;
	}

	public function setParentClass(SmartWikiClass $parentClass) {
		$this->parentClass = $parentClass;
	}

	public function getChildClassID() {
		return $this->childClassID;
	}
	
	public function setChildClassID(SmartWikiString $childClassID) {
		$this->childClassID = $childClassID;
	}

	public function getChildClass() {
		return $this->childClass;
	}
	
	public function setChildClass(SmartWikiClass $childClass) {
		$this->childClass = $childClass;
	}

}
?>