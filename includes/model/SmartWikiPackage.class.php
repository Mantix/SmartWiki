<?php
class SmartWikiPackage extends SmartWikiModelElement {
	private $owner;
	private $contentManager;
	private $dependency;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Parse an XMI file for Packages, using the given SyntaxTransformer
	 * 
	 * @param SmartWikiSyntaxTransformer $syntaxTransformer
	 * 
	 * @return An array of Packages
	 */
	public static function parseXMI(SmartWikiXmlParser $xmlParser) {
		
		$xmiParser = XMIBase::getXMIParser($xmlParser);
		
		# SmartWikiModel object
		$smartwikiModel = SmartWikiModel::singleton();
	
		// Add root package as first thing! There should always be a root package, but in the XMI, this is called a Model
		
		$rootPackage = $xmlParser->array['XMI']['XMI.content']['UML:Model'];
		
		$package = new SmartWikiPackage();
		$package->setName(new SmartWikiString($rootPackage['_']['name']));
		$package->setID(new SmartWikiString($rootPackage['_']['xmi.id']));
		$package->setSearchPath(new SmartWikiString("/XMI/XMI.content/UML:Model"));
		$package->setPackageID(new SmartWikiString(""));	// Root package heeft geen parent package
		$package->setPackage(NULL);
		if ( isset($rootPackage['UML:ModelElement.taggedValue']['UML:TaggedValue']) ) {
			$description = $xmiParser->getDescriptionFromTaggedValues($rootPackage['UML:ModelElement.taggedValue']['UML:TaggedValue']);
		} else {
			$description = "";
		}
		$package->setDescription(new SmartWikiString($description));
		$smartwikiModel->setPackage($package);

		# Search for all the packages in the parser array
		$packagesXMI = SmartWikiModelElement::arraySearchRecursive('UML:Package', $xmlParser->array);
	
		# Loop through the parsed packages
		foreach($packagesXMI as $key => $value) {

			# We need an xmi.id, or else it could be an reference
			if (isset($value['_']['xmi.id'])) {
				
				if ( isset($value['UML:ModelElement.taggedValue']['UML:TaggedValue']) ) {
					$description = $xmiParser->getDescriptionFromTaggedValues($value['UML:ModelElement.taggedValue']['UML:TaggedValue']);
				} else {
					$description = "";
				}
				
				# Add a package
				$package = new SmartWikiPackage();
				$package->setSearchPath(new SmartWikiString($value['searchPath']));
				$package->setTitle(NULL); # We'll build the Title later
				$package->setDescription(new SmartWikiString($description));

				# Set the values:
				# - Use the "Package ID" from the XMI file
				$package->setPackageID($package->getContainer($value['searchPath'], $xmlParser));
				# - We'll get the container "Package" later
				$package->setPackage(NULL);
				# - Use the "ID" from the XMI file
				$package->setID(new SmartWikiString($value['_']['xmi.id']));
				# - Use the "Name" from the XMI file
				$package->setName(new SmartWikiString($value['_']['name']));

				$smartwikiModel->setPackage($package);
			}
		}
		
		

		# Let's build the Title and get the container Package Title.
		$processingPackages = $smartwikiModel->getPackages();
		while (count($processingPackages) > 0) {
			foreach($processingPackages as $key => $value) {

				$currentPackage = $smartwikiModel->getPackageByID($value->getID());
				$parentPackage = $value->getPackageID() == NULL || $value->getPackageID()->isEmpty() ? FALSE : $smartwikiModel->getPackageByID($value->getPackageID());

				# If this package isn't part of another package, the Title is equal to the name
				if ($parentPackage == FALSE) {

					$currentPackage->setTitle(Title::newFromText(wfMsgForContent('smartwiki-prefix') . $currentPackage->getName()->getValue()));
					unset($processingPackages[$key]);

				# Else if the container package has a Title, build a title and a reference
				} elseif ($parentPackage != FALSE && $parentPackage != NULL && $parentPackage->getTitle() != NULL) {

					$currentPackage->setPackage($parentPackage);
					$currentPackage->setTitle(Title::newFromText($parentPackage->getTitle()->getText() . ' : ' . $currentPackage->getName()->getValue()));
					unset($processingPackages[$key]);

				}
			}
		}

		# Loop through the parsed packages
		$processingPackages = $smartwikiModel->getPackages();
		foreach($processingPackages as $key => $value) {

			
			# Current article of this class, is excists, and its values
			if ($value->getTitle()->isKnown()) {
				# Existing page

				# Current values from the page
				$current_values = $value->getValuesFromArticle();

				# Set the values:
				# - Use the "Description" used in the article
				//$value->setDescription(new SmartWikiString($description));
				# - Use the "Container order" currently used in the article
				$value->setOrder(new SmartWikiNumber($current_values['Container order']));
				# - Use the "Dependency" currently used in the article
				$value->setDependency(new SmartWikiString($current_values['Dependency']));
				# - Use the "Owner" currently used in the article
				$value->setOwner(new SmartWikiString($current_values['Owner']));
				# - Use the "Content manager" currently used in the article
				$value->setContentManager(new SmartWikiString($current_values['Content manager']));

				$value->setState(SmartWikiState::LOG_EDITED);

		} else {
				# New page

				# Set the values:
				# - Set the "Description" to blank
				//$value->setDescription(new SmartWikiString($description));
				# - Set the "Container order" to -1
				$value->setOrder(new SmartWikiNumber(-1));
				# - Set the "Dependency" to blank
				#   TODO -> Read the Dependency from the XMI file
				$value->setDependency(new SmartWikiString(''));
				# - Set the "Owner" to blank
				$value->setOwner(new SmartWikiString(''));
				# - Set the "Content manager" to blank
				$value->setContentManager(new SmartWikiString(''));

				$value->setState(SmartWikiState::LOG_CREATED);

			}

			$smartwikiModel->setPackage($value);

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

		$form_template['template'] = str_replace('%Owner%', $this->getOwner() ? $this->getOwner()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%Content manager%', $this->getContentManager() ? $this->getContentManager()->getWikiValue() : '', $form_template['template']);
		$form_template['template'] = str_replace('%Dependency%', $this->getDependency() ? $this->getDependency()->getWikiValue() : '', $form_template['template']);

		# Fill the page with the content
		SmartWikiPage::editPage($article, $form_template['template'] . $this->getContent(), '(Re)created ' . $this->getTitle()->getText());

		# Return the Title object
		return $this->getTitle();
	}
	

	/**
	 * Create an array of Package objects, fill it with the fields provided
	 * 
	 * @param $fieldsArray - An array of fields
	 * 
	 * @return $packageArray - An array of packages
	 */
	public static function fill($fieldsArray) {

		$smartwikiModel = SmartWikiModel::singleton();

		for ($i = 0; $i < count($fieldsArray); $i++) {
			$package = new SmartWikiPackage();

			// SmartWikiModelElement
			$package->setID(new SmartWikiString($fieldsArray[$i]['ID']));
			$package->setName(new SmartWikiString($fieldsArray[$i]['Name']));
			//$package->setTitle(Title::newFromText($fieldsArray[$i]['Title']));
			$package->setTitle($fieldsArray[$i]['titleObject']);
			$package->setID(new SmartWikiString($fieldsArray[$i]['Description']));
			$package->setOrder(new SmartWikiNumber($fieldsArray[$i]['Container order']));
			$package->setPackage($smartwikiModel->getPackage(Title::newFromText($fieldsArray[$i]['Package'])));
			$package->setDescription(new SmartWikiString($fieldsArray[$i]['Description']));
				
			// SmartWikiPackage
			$package->setOwner(new SmartWikiString($fieldsArray[$i]['Owner']));
			$package->setContentManager(new SmartWikiString($fieldsArray[$i]['Content manager']));
			$package->setDependency(new SmartWikiString($fieldsArray[$i]['Dependency']));

			$smartwikiModel->setPackage($package);
		}

	}

	public function getWikiText() {
		// TODO
	}

	public function getOwner() {
		return $this->owner;
	}

	public function setOwner(SmartWikiString $owner) {
		$this->owner = $owner;
	}

	public function getContentManager() {
		return $this->contentManager;
	}

	public function setContentManager(SmartWikiString $contentManager) {
		$this->contentManager = $contentManager;
	}

	public function getDependency() {
		return $this->dependency;
	}

	public function setDependency(SmartWikiString $dependency) {
		$this->dependency = $dependency;
	}

}
?>