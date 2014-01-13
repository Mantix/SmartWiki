<?php
class SWGeneralization extends SWModelElement {
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
	 * @param SWSyntaxTransformer $syntaxTransformer
	 * 
	 * @return An array of Generalizations
	 */
	public static function parseXMI(SWXmlParser $xmlParser) {

		# SWModel object
		$smartwikiModel = SWModel::singleton();

		# Search for all the generalizations in the parser array
		$generalizationsXMI = SWModelElement::arraySearchRecursive('UML:Generalization', $xmlParser->array);

		$xmiParser = XMIBase::getXMIParser($xmlParser);
	
		# Loop through the parsed associations
		foreach($generalizationsXMI as $key => $value) {

			# We need an xmi.id, or else it could be an reference
			if (isset($value['_']['xmi.id'])) {

				$description = $xmiParser->getDescriptionFromTaggedValues($value['UML:ModelElement.taggedValue']['UML:TaggedValue']);
		
				# Add a generalization
				$generalization = new SWGeneralization();
				$generalization->setSearchPath(new SWString($value['searchPath']));
				$generalization->setPackageID($generalization->getContainer($value['searchPath'], $xmlParser));
				$generalization->setPackage($smartwikiModel->getPackageByID($generalization->getPackageID()));

				# Get the parent and the child
				$generalization->setParentClassID(new SWString($value['UML:Generalization.parent']['UML:Class']['_']['xmi.idref']));
				$generalization->setParentClass($smartwikiModel->getClassByID($generalization->getParentClassID()));
				$generalization->setChildClassID(new SWString($value['UML:Generalization.child']['UML:Class']['_']['xmi.idref']));
				$generalization->setChildClass($smartwikiModel->getClassByID($generalization->getChildClassID()));

				# Build the name and the title
				$generalization->setID(new SWString($value['_']['xmi.id']));
				$generalization->setName(new SWString($generalization->getChildClass()->getName()->getValue() . ' ' . (isset($value['_']['name']) ? $value['_']['name'] : 'generalizes') . ' ' . $generalization->getParentClass()->getName()->getValue()));
				$generalization->setTitle(Title::newFromText(($generalization->getPackage() != NULL ? $generalization->getPackage()->getTitle()->getText() . ' : ' : wfMsgForContent('smartwiki-prefix')) . $generalization->getName()));

				# Current article of this class, is excists, and its values
				if ($generalization->getTitle()->isKnown()) {

					$current_values = $generalization->getValuesFromArticle();

					# Set the values:
					# - Use the "Description" currently used in the article
					$generalization->setDescription(new SWString($description));
					# - Use the "Child order" currently used in the article
					$generalization->setChildOrder(new SWNumber($current_values['Child order']));
					# - Use the "Container order" currently used in the article
					$generalization->setOrder(new SWNumber($current_values['Container order']));

					$generalization->setState(SWState::LOG_EDITED);

				} else {

					# Set the values:
					# - Set the "Description" to blank
					$generalization->setDescription(new SWString($description));
					# - Set the "Child order" to -1
					$generalization->setChildOrder(new SWNumber(-1));
					# - Set the "Container order" to -1
					$generalization->setOrder(new SWNumber(-1));

					$generalization->setState(SWState::LOG_CREATED);

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
			$generalization = new SWGeneralization();

			// SWModelElement
			$generalization->setID(new SWString($fieldsArray[$i]['ID']));
			$generalization->setName(new SWString($fieldsArray[$i]['Name']));
			//$generalization->setTitle(Title::newFromText($fieldsArray[$i]['Title']));
			$generalization->setTitle($fieldsArray[$i]['titleObject']);
			$generalization->setID(new SWString($fieldsArray[$i]['Description']));
			$generalization->setOrder(new SWNumber($fieldsArray[$i]['Container order']));
			$generalization->setPackage($smartwikiModel->getPackage(Title::newFromText($fieldsArray[$i]['Package'])));
			$generalization->setDescription(new SWString($fieldsArray[$i]['Description']));
				
			// SWGeneralization
			$generalization->setChildOrder(new SWNumber($fieldsArray[$i]['Child order']));
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

	public function setChildOrder(SWNumber $childOrder) {
		$this->childOrder = $childOrder;
	}

	public function getParentClassID() {
		return $this->parentClassID;
	}

	public function setParentClassID(SWString $parentClassID) {
		$this->parentClassID = $parentClassID;
	}

	public function getParentClass() {
		return $this->parentClass;
	}

	public function setParentClass(SWClass $parentClass) {
		$this->parentClass = $parentClass;
	}

	public function getChildClassID() {
		return $this->childClassID;
	}
	
	public function setChildClassID(SWString $childClassID) {
		$this->childClassID = $childClassID;
	}

	public function getChildClass() {
		return $this->childClass;
	}
	
	public function setChildClass(SWClass $childClass) {
		$this->childClass = $childClass;
	}

}
?>