<?php
class SWPackage extends SWModelElement {
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
	 * @param SWSyntaxTransformer $syntaxTransformer
	 * 
	 * @return An array of Packages
	 */
	public static function parseXMI(SWXmlParser $xmlParser) {
		
		$xmiParser = XMIBase::getXMIParser($xmlParser);
		
		# SWModel object
		$smartwikiModel = SWModel::singleton();
	
		// Add root package as first thing! There should always be a root package, but in the XMI, this is called a Model
		
		$rootPackage = $xmlParser->array['XMI']['XMI.content']['UML:Model'];
		
		$package = new SWPackage();
		$package->setName(new SWString($rootPackage['_']['name']));
		$package->setID(new SWString($rootPackage['_']['xmi.id']));
		$package->setSearchPath(new SWString("/XMI/XMI.content/UML:Model"));
		$package->setPackageID(new SWString(""));	// Root package heeft geen parent package
		$package->setPackage(NULL);
		if ( isset($rootPackage['UML:ModelElement.taggedValue']['UML:TaggedValue']) ) {
			$description = $xmiParser->getDescriptionFromTaggedValues($rootPackage['UML:ModelElement.taggedValue']['UML:TaggedValue']);
		} else {
			$description = "";
		}
		$package->setDescription(new SWString($description));
		$smartwikiModel->setPackage($package);

		# Search for all the packages in the parser array
		$packagesXMI = SWModelElement::arraySearchRecursive('UML:Package', $xmlParser->array);
	
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
				$package = new SWPackage();
				$package->setSearchPath(new SWString($value['searchPath']));
				$package->setTitle(NULL); # We'll build the Title later
				$package->setDescription(new SWString($description));

				# Set the values:
				# - Use the "Package ID" from the XMI file
				$package->setPackageID($package->getContainer($value['searchPath'], $xmlParser));
				# - We'll get the container "Package" later
				$package->setPackage(NULL);
				# - Use the "ID" from the XMI file
				$package->setID(new SWString($value['_']['xmi.id']));
				# - Use the "Name" from the XMI file
				$package->setName(new SWString($value['_']['name']));

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
				//$value->setDescription(new SWString($description));
				# - Use the "Container order" currently used in the article
				$value->setOrder(new SWNumber($current_values['Container order']));
				# - Use the "Dependency" currently used in the article
				$value->setDependency(new SWString($current_values['Dependency']));
				# - Use the "Owner" currently used in the article
				$value->setOwner(new SWString($current_values['Owner']));
				# - Use the "Content manager" currently used in the article
				$value->setContentManager(new SWString($current_values['Content manager']));

				$value->setState(SWState::LOG_EDITED);

		} else {
				# New page

				# Set the values:
				# - Set the "Description" to blank
				//$value->setDescription(new SWString($description));
				# - Set the "Container order" to -1
				$value->setOrder(new SWNumber(-1));
				# - Set the "Dependency" to blank
				#   TODO -> Read the Dependency from the XMI file
				$value->setDependency(new SWString(''));
				# - Set the "Owner" to blank
				$value->setOwner(new SWString(''));
				# - Set the "Content manager" to blank
				$value->setContentManager(new SWString(''));

				$value->setState(SWState::LOG_CREATED);

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
		SWHelper::editPage($article, $form_template['template'] . $this->getContent(), '(Re)created ' . $this->getTitle()->getText());

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

		$smartwikiModel = SWModel::singleton();

		for ($i = 0; $i < count($fieldsArray); $i++) {
			$package = new SWPackage();

			// SWModelElement
			$package->setID(new SWString($fieldsArray[$i]['ID']));
			$package->setName(new SWString($fieldsArray[$i]['Name']));
			//$package->setTitle(Title::newFromText($fieldsArray[$i]['Title']));
			$package->setTitle($fieldsArray[$i]['titleObject']);
			$package->setID(new SWString($fieldsArray[$i]['Description']));
			$package->setOrder(new SWNumber($fieldsArray[$i]['Container order']));
			$package->setPackage($smartwikiModel->getPackage(Title::newFromText($fieldsArray[$i]['Package'])));
			$package->setDescription(new SWString($fieldsArray[$i]['Description']));
				
			// SWPackage
			$package->setOwner(new SWString($fieldsArray[$i]['Owner']));
			$package->setContentManager(new SWString($fieldsArray[$i]['Content manager']));
			$package->setDependency(new SWString($fieldsArray[$i]['Dependency']));

			$smartwikiModel->setPackage($package);
		}

	}

	public function getWikiText() {
		// TODO
	}

	public function getOwner() {
		return $this->owner;
	}

	public function setOwner(SWString $owner) {
		$this->owner = $owner;
	}

	public function getContentManager() {
		return $this->contentManager;
	}

	public function setContentManager(SWString $contentManager) {
		$this->contentManager = $contentManager;
	}

	public function getDependency() {
		return $this->dependency;
	}

	public function setDependency(SWString $dependency) {
		$this->dependency = $dependency;
	}

}
?>