<?php
class SmartWikiAttribute extends SmartWikiModelElement {
	private $datatype;
	private $isIndex;
	private $classID;
	private $class;
	private $enumerationID;
	private $enumeration;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Parse an XMI file for Attributes, using the given SyntaxTransformer
	 * 
	 * @param SmartWikiSyntaxTransformer $syntaxTransformer
	 * 
	 * @return An array of Attributes
	 */
	public static function parseXMI(SmartWikiXmlParser $xmlParser) {

		# SmartWikiModel object
		$smartwikiModel = SmartWikiModel::singleton();

		# Search for all the attributes in the parser array
		$attributesXMI = SmartWikiModelElement::arraySearchRecursive('UML:Attribute', $xmlParser->array);
		
		$xmiParser = XMIBase::getXMIParser($xmlParser);
	
		# Loop through the parsed attributes
		foreach($attributesXMI as $key => $value) {
			# We need an xmi.id, or else it could be an reference
			if (isset($value['_']['xmi.id'])) {
			
				$description = $xmiParser->getDescriptionFromTaggedValues($value['UML:ModelElement.taggedValue']['UML:TaggedValue']);
				
				$attribute = new SmartWikiAttribute();
				$attribute->setSearchPath(new SmartWikiString($value['searchPath']));
				$attribute->setID(new SmartWikiString($value['_']['xmi.id']));
				$attribute->setPackageID($attribute->getContainer($value['searchPath'], $xmlParser));
				$attribute->setPackage($smartwikiModel->getPackageByID($attribute->getPackageID()));

				# Get the container class or association class
				if ($attribute->getContainer($value['searchPath'], $xmlParser, 'UML:Class') != new SmartWikiString('')) {
					$attribute->setClassID(new SmartWikiString($attribute->getContainer($value['searchPath'], $xmlParser, 'UML:Class')));
					$attribute->setClass($smartwikiModel->getClassByID($attribute->getClassID()));
				} elseif ($attribute->getContainer($value['searchPath'], $xmlParser, 'UML:AssociationClass') != new SmartWikiString('')) {
					$attribute->setClassID(new SmartWikiString($attribute->getContainer($value['searchPath'], $xmlParser, 'UML:AssociationClass')));
					$attribute->setClass($smartwikiModel->getAssociationClassByID($attribute->getClassID()));
				}

				# Name and title
				$attribute->setName(new SmartWikiString( /*($attribute->getClass() != NULL ? $attribute->getClass()->getName()->getValue() . ' : ' : '') .*/ $value['_']['name']));
				$attribute->setTitle(Title::newFromText(($attribute->getPackage() != NULL ?	$attribute->getPackage()->getTitle()->getText() . ' : ' : wfMsgForContent('smartwiki-prefix')) . 
				$attribute->getClass()->getName() . " : " .
				$attribute->getName())
				);

				# Current article of this class, is excists, and its values
				if ($attribute->getTitle()->isKnown()) {

					$current_values = $attribute->getValuesFromArticle();

					# Set the values:
					# - Use the "Description" currently used in the article
					$attribute->setDescription(new SmartWikiString($description));
					# - Use the "Container order" currently used in the article
					$attribute->setOrder(new SmartWikiNumber($current_values['Container order']));
					# - Use the "Datatype" currently used in the article
					$attribute->setDatatype(new SmartWikiString($current_values['Datatype']));
					# - Use the "Is index" currently used in the article, otherwise set to "Yes"
					$attribute->setIsIndex(new SmartWikiBoolean($current_values['Is index']));

					$attribute->setState(SmartWikiState::LOG_EDITED);

				} else {

					# Set the values:
					# - Set the "Description" to blank
					$attribute->setDescription(new SmartWikiString($description));
					# - Set the "Container order" to -1
					$attribute->setOrder(new SmartWikiNumber(-1));
					# - Set the "Datatype" to blank
					#   TODO: Can we read this from the XMI file?
					$attribute->setDatatype(new SmartWikiString(''));
					# - Set the "Is index" to "Yes"
					$attribute->setIsIndex(new SmartWikiBoolean(TRUE));

					$attribute->setState(SmartWikiState::LOG_CREATED);

				}

				$smartwikiModel->setAttribute($attribute);

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

		$form_template['template'] = str_replace('%Datatype%', $this->getDatatype()->getWikiValue(), $form_template['template']);
		$form_template['template'] = str_replace('%Is index%', $this->getIsIndex()->getWikiValue(), $form_template['template']);
		$form_template['template'] = str_replace('%Class%', ($this->getClass() != NULL ? $this->getClass()->getTitle()->getText() : ''), $form_template['template']);
		$form_template['template'] = str_replace('%Enumeration%', ($this->getEnumeration() != NULL ? $this->getEnumeration()->getTitle()->getText() : ''), $form_template['template']);

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
			$attribute = new SmartWikiAttribute();

			// SmartWikiModelElement
			$attribute->setID(new SmartWikiString($fieldsArray[$i]['ID']));
			$attribute->setName(new SmartWikiString($fieldsArray[$i]['Name']));
			//$attribute->setTitle(Title::newFromText($fieldsArray[$i]['Title']));
			$attribute->setTitle($fieldsArray[$i]['titleObject']);
			$attribute->setID(new SmartWikiString($fieldsArray[$i]['Description']));
			$attribute->setOrder(new SmartWikiNumber($fieldsArray[$i]['Container order']));
			$attribute->setPackage($smartwikiModel->getPackage(Title::newFromText(isset($fieldsArray[$i]['Package']) ? $fieldsArray[$i]['Package'] : '')));
			$attribute->setDescription(new SmartWikiString($fieldsArray[$i]['Description']));
				
			// SmartWikiAttribute
			if ( !isset($fieldsArray[$i]['Datatype']) ) {
				$fieldsArray[$i]['Datatype'] = "";
			}
			$attribute->setDatatype(new SmartWikiString($fieldsArray[$i]['Datatype']));
			$attribute->setIsIndex(new SmartWikiBoolean($fieldsArray[$i]['Is index']));
			$attribute->setClass($smartwikiModel->getClass(Title::newFromText($fieldsArray[$i]['Class'])) ? $smartwikiModel->getClass(Title::newFromText($fieldsArray[$i]['Class'])) : $smartwikiModel->getAssociationClass(Title::newFromText($fieldsArray[$i]['Class'])));
			if ( isset($fieldsArray[$i]['Enumeration']) && $fieldsArray[$i]['Enumeration'] != "" ) {
				$x = $smartwikiModel->getEnumeration(Title::newFromText($fieldsArray[$i]['Enumeration']));
				$attribute->setEnumeration($x);
			}

			$smartwikiModel->setAttribute($attribute);

		}

	}

	public function getWikiText() {
		// TODO
	}

	public function getDatatype() {
		return $this->datatype;
	}

	public function setDatatype(SmartWikiString $datatype) {
		$this->datatype = $datatype;
	}

	public function getIsIndex() {
		return $this->isIndex;
	}

	public function setIsIndex(SmartWikiBoolean $isIndex) {
		$this->isIndex = $isIndex;
	}

	public function getClassID() {
		return $this->classID;
	}

	public function setClassID(SmartWikiString $classID) {
		$this->classID = $classID;
	}

	public function getClass() {
		return $this->class;
	}

	public function setClass(SmartWikiModelElement $class = NULL) {
		$this->class = $class;
	}

	public function getEnumerationID() {
		return $this->enumeration;
	}

	public function setEnumerationID(SmartWikiString $enumerationID) {
		$this->enumerationID = $enumerationID;
	}

	public function getEnumeration() {
		return $this->enumeration;
	}

	public function setEnumeration(SmartWikiEnumeration $enumeration = NULL) {
		$this->enumeration = $enumeration;
	}

}
?>