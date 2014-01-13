<?php
class SWTransformer {
	/**
	 * Cache for the objects
	 */
	public $packages;
	public $classes;
	public $associations;
	public $associationClasses;
	public $generalizations;
	public $attributes;
	public $enumerations;

	# For logging
	private $log;

	/**
	 * Constructor
	 */
	function __construct() {
		# TODO: Do something or remove
	}

	/**
	 * Init - Load the XMI file from the XMI file page.
	 */
	public function init() {
		# For logging
		$this->log = new SWLogger(new SWString('transformation'));

		# Load the current SmartWiki created objects
		$category = Category::newFromName('SmartWiki Glossary term');
		$titleIterator = $category->getMembers();
		while ($titleIterator->valid()) {
			$titleObject = $titleIterator->current();
			$this->log->add($titleObject, SWState::LOG_DELETED);
			$titleIterator->next();
		}

		# The SmartWiki model
		$smartwikiModel = SWModel::getFilledSWModel();
		/*
		$smartwikiModel = SWModel::singleton();

		# Get the current objects
		SWPackage::fill(SWTransformer::getFieldsByCategory('SmartWiki Packages'));
		SWClass::fill(SWTransformer::getFieldsByCategory('SmartWiki Classes'));
		SWAssociationClass::fill(SWTransformer::getFieldsByCategory('SmartWiki Association classes'));
		SWAssociation::fill(SWTransformer::getFieldsByCategory('SmartWiki Associations'));
		SWGeneralization::fill(SWTransformer::getFieldsByCategory('SmartWiki Generalizations'));
		SWAttribute::fill(SWTransformer::getFieldsByCategory('SmartWiki Attributes'));
		SWEnumeration::fill(SWTransformer::getFieldsByCategory('SmartWiki Enumerations'));

		$smartwikiModel->fillClassGeneralizations();
		$smartwikiModel->fillClassAssociations();
		
		$smartwikiModel->prepareForTransformation();
		*/
		
		$this->packages = $smartwikiModel->getPackages();
		$this->classes = $smartwikiModel->getClasses();
		$this->associationClasses = $smartwikiModel->getAssociationClasses();
		$this->associations = $smartwikiModel->getAssociations();
		$this->generalizations = $smartwikiModel->getGeneralizations();
		$this->attributes = $smartwikiModel->getAttributes();
		$this->enumerations = $smartwikiModel->getEnumerations();
		
		# Ignore attributes we get for free from Wiki or SmartWiki
		$ignoreFields = explode(',', wfMsgForContent('smartwiki-create-ignore-fields'));
		$attributes = $this->attributes;
		$this->attributes = array();
		foreach ($attributes AS $key => $value) {
			$name = strtolower($value->getName());
			if (!in_array($name, $ignoreFields)) {
				$this->attributes[] = $attributes[$key];
			}
		}

		$this->createFilterPages();
		$this->createSubmenus();
		$this->createPackageOverviewPages();
		$this->createClassCategoryPages();
		$this->createClassProperties();
		$this->createClassTemplates();
		$this->createClassForms();
		$this->createManager();
		$this->createGlossary();

		# Delete the pages we don't need
		$titleArray = $this->log->getTitlesWithState(SWState::LOG_DELETED);
		foreach ($titleArray AS $titleObject) {
			$article = new Article($titleObject);
			$article->doDeleteArticle("This page wasn't needed by SmartWiki anymore.");
		}

		# Return the log
		return $this->log;
	}

	/**
	 * Create Filter pages
	 * 
	 * @return Array of Title objects
	 */
	private function createFilterPages() {
		// For each association we make a filter
		
		for($i=0; $i<count($this->associations);$i++) {
			$association = &$this->associations[$i];
			
			$title = Title::newFromText($association->getTitle()->getText(), SD_NS_FILTER);
			$article = new Article($title);
			$originalText = $article->getRawText();
			
			$newText = "This filter covers the property [[Covers property::".$association->getTitle()->getText()."]].";

			if ( trim($originalText) != trim($newText) ) {
				$this->log->add($title, ($title->isKnown() ? SWState::LOG_EDITED : SWState::LOG_CREATED));
			} else {
				$this->log->add($title, ($title->isKnown() ? SWState::LOG_NOACTION : SWState::LOG_CREATED));
			}
			
			$article->doEdit($newText, '(Re)created the filter.');
		}
		
		// Create filter for each association class as well
		for($i=0; $i<count($this->associationClasses);$i++) {
			$association = &$this->associationClasses[$i];
				
			$title = Title::newFromText($association->getTitle()->getText(), SD_NS_FILTER);
			$article = new Article($title);
			$originalText = $article->getRawText();
				
			$newText = "This filter covers the property [[Covers property::".$association->getTitle()->getText()."]].";

			if ( trim($originalText) != trim($newText) ) {
				$this->log->add($title, ($title->isKnown() ? SWState::LOG_EDITED : SWState::LOG_CREATED));
			} else {
				$this->log->add($title, ($title->isKnown() ? SWState::LOG_NOACTION : SWState::LOG_CREATED));
			}
				
			$article->doEdit($newText, '(Re)created the filter.');
		}
		
		
		
	}
	
	/**
	 * Create the SmartWiki submenu
	 * 
	 * @return Array of Title objects
	 */
	public function createSubmenus() {
		global $wgSitename;

		# Get the article used as the menu on the left
		$title = Title::newFromText('Sidebar', NS_MEDIAWIKI);
		$this->log->add($title, SWState::LOG_EDITED);
		$article = new Article($title);
		$newText = '';

		# We will now add the links
		$processingClasses = $this->classes;
		for ($i = 0; $i < count($this->packages); $i++) {
			$newText .= 
				"* " . str_replace(wfMsgForContent('smartwiki-prefix'), '', $this->packages[$i]->getName()) . "\n";

			foreach ($processingClasses AS $key => $value) {
				if ( !method_exists($value, "getPackage") ) {
					die("<pre>".var_export($value,1)."<pre>");
				} 
				if ($value->getPackage() != NULL && $value->getPackage()->equals($this->packages[$i])) {
					$newText .= 
						"** " . str_replace(wfMsgForContent('smartwiki-prefix'), '', $value->getTitle()->getText()) . " | " . $value->getName() . "\n";

					unset($processingClasses[$key]);
				}
			}
			
			// Subpackages?
			for ($y = 0; $y < count($this->packages); $y++) {
				if ( $this->packages[$y]->getPackage() != null ) {
					if ( $this->packages[$y]->getPackage()->getTitle()->equals(
							$this->packages[$i]->getTitle()
						)) {
						$newText .= "** ".$this->packages[$y]->getTitle()->getText()." | ".$this->packages[$y]->getName()."\n";
					}
				}
			}
		}

		# If we still have classes without a package
		if (count($processingClasses) > 0) {
			$newText .= 
				"* " . wfMsgForContent('smartwiki-create-no-package') . "\n";

			# All classes without a package
			foreach ($processingClasses AS $key => $value) {
				$newText .= 
					"** " . str_replace(wfMsgForContent('smartwiki-prefix'), '', $value->getTitle()->getText()) . " | " . $value->getName() . "\n";
			}
		}

		# And don't forget the tools
		$newText .= 
			"* " . $wgSitename . " tools\n" . 
# TODO			"** " . $wgSitename . " Manager | " . $wgSitename . " Manager\n" . 
			"** " . $wgSitename . " Glossary | " . $wgSitename . " Glossary\n" . 
			"** Help:Contents | Help\n" . 
			"** SmartWiki | SmartWiki\n";

		# Edit the article and store it
		$article->doEdit($newText, '(Re)created the menu for SmartWiki.');
	}

	/**
	 * Create all the "SmartWiki Package overview pages" from the SmartWiki Design
	 */
	private function createPackageOverviewPages() {
		$main_text = '';
		$processingClasses = $this->classes;

		if (count($this->packages) > 0) {
			for ($i = 0; $i < count($this->packages); $i++) {
				$main_text .= 
					"== " . $this->packages[$i]->getName() . " ==\n\n" . 
					"" . $this->packages[$i]->getDescription() . "\n\n";

				# All the classes in this package
				foreach ($processingClasses AS $key => $value) {
					if ($value->getPackage() != NULL && $value->getPackage()->equals($this->packages[$i])) {
						$main_text .=
							"* [[" . $value->getTitle() . "|" . $value->getName() . "]]\n";

						unset($processingClasses[$key]);
					}
				}
	/* // Do not show "Without Package" as it's no longer relevant.
				$main_text .= 
					"[[" . $this->packages[$i]->getTitle() . "|" . wfMsgForContent('smartwiki-create-no-package') . "]]\n\n" . 
					"\n\n";
	*/ 			
				
				// Subpackages?
				for ($y = 0; $y < count($this->packages); $y++) {
					if ( $this->packages[$y]->getPackage() != null ) {
						if ( $this->packages[$y]->getPackage()->getTitle()->equals(
						$this->packages[$i]->getTitle()
						)) {
							$main_text .= "* [[".$this->packages[$y]->getTitle()."|".$this->packages[$y]->getName()."]]\n";
						}
					}
				}				
				
				$main_text .= "\n\n";
				
				$packageTitle = Title::newFromText(str_replace(wfMsgForContent('smartwiki-prefix'), '', $this->packages[$i]->getTitle()->getText()));
				$this->log->add($packageTitle, ($packageTitle->isKnown() ? SWState::LOG_EDITED : SWState::LOG_CREATED));
				$article = new Article($packageTitle);
	
				$package_text = 
					"{{SmartWiki Glossary term\n" . 
					"|Name=" . $this->packages[$i]->getName() . "\n" . 
					"|Description=" . $this->packages[$i]->getDescription() . "\n" . 
					"}}\n\n";
				# TODO: Create a query that links to all the SmartWiki Object overview pages in this package
	
				$article->doEdit($package_text, 'SmartWiki (re)created the "Package overview page" ' . $this->packages[$i]->getName());
			}
		}

		# All the classes without a package
		/*
		if (count($processingClasses) > 0) {
			$main_text .=
				"== " . wfMsgForContent('smartwiki-create-no-package') . " ==\n\n";

			foreach ($processingClasses AS $key => $value) {
				$main_text .= 
					"* [[" . $value->getTitle() . "|" . $value->getName() . "]]\n";

				unset($processingClasses[$key]);
			}
		}
		*/
		
		$main_title = Title::newFromText("Main Page", NS_MAIN);
		$this->log->add($main_title, ($main_title->isKnown() ? SWState::LOG_EDITED : SWState::LOG_CREATED));
		$main_article = new Article($main_title);
		$main_article->doEdit($main_text, 'SmartWiki (re)created the Main Page');
	}

	/**
	 * Create all the "SmartWiki Class category pages" from the SmartWiki Design
	 */
	private function createClassCategoryPages() {
		for ($i = 0; $i < count($this->classes); $i++) {
			# BEGIN - Category
			$category_title = Title::newFromText((string)$this->classes[$i]->getName(), NS_CATEGORY);
			
			$category = new Article($category_title);
			$originalText = $category->getRawText();
			
			$category_text = "";
			if ($this->classes[$i]->getAbstract()->equals(new SWBoolean(FALSE))) {
				//$category_text .= "This category uses the default form [[Has default form::" . $this->classes[$i]->getTitle() . "]]\n\n";
			}
			$assTo = $this->classes[$i]->getToAssociations();
			$assFrom = $this->classes[$i]->getFromAssociations(true);	// true = include parent asso's too!
			if ( is_array($assTo) || is_array($assFrom) ) {
				$category_text .= "This category uses the following filters: ";
			}
			/*
			if ( is_array($assTo) ) {
				foreach($assTo as $ass) {
					$category_text .= "[[Has filter::Filter:".$ass->getTitle()->getText()."]] ";
				}
			}*/
			if ( is_array($assFrom) ) {
				foreach($assFrom as $ass) {
					$category_text .= "[[Has filter::Filter:".$ass->getTitle()->getText()."]] ";
				}
			}
			
			$allParentClasses = $this->classes[$i]->getParentClasses();
			$clsNames = array($this->classes[$i]->getTitle()->getText());
			foreach($allParentClasses as $cls) {
				$clsNames[] = (string)$cls->getTitle()->getText();
			}

			# Association Classes
			for($j=0;$j<count($this->associationClasses);$j++) {
				//if ( $this->associationClasses[$j]->getFromClass()->getTitle()->equals($this->classes[$i]->getTitle()) ) {
				if ( in_array((string)$this->associationClasses[$j]->getFromClass()->getTitle()->getText(), $clsNames) ) {
					$category_text .= "[[Has filter::Filter:".$this->associationClasses[$j]->getTitle()->getText()."]] ";
				}
			}

			if ( trim($originalText) != trim($category_text) ) {
				$this->log->add($category_title, ($category_title->isKnown() ? SWState::LOG_EDITED : SWState::LOG_CREATED));
			} else {
				$this->log->add($category_title, ($category_title->isKnown() ? SWState::LOG_NOACTION : SWState::LOG_CREATED));
			}

			$category->doEdit($category_text, 'SmartWiki (re)created the category ' . $this->classes[$i]->getTitle());
			# END - Category

			# BEGIN - Article
			$article_title = Title::newFromText(str_replace(wfMsgForContent('smartwiki-prefix'), '', $this->classes[$i]->getTitle()->getText()), NS_MAIN);
				
			$article = new Article($article_title);

			$article_text = 
				"{{SmartWiki Glossary term\n" . 
				"|Name=" . $this->classes[$i]->getName() . "\n" . 
				"|Description=" . $this->classes[$i]->getDescription() . "\n" . 
				"}}\n" . 
				"- [[Special:BrowseData/" . str_replace(wfMsgForContent('smartwiki-prefix'), '', $this->classes[$i]->getName()) . "|" . wfMsgForContent('smartwiki-browse-data', $this->classes[$i]->getPluralName()) . "]]\n\n" .
				($this->classes[$i]->getAbstract()->equals(new SWBoolean(FALSE)) ? 
					"== " . wfMsgForContent('smartwiki-create-edit', $this->classes[$i]->getIndefiniteArticle(), $this->classes[$i]->getName()) . " ==\n" . 
					"{{#forminput:form=" . 
						str_replace(wfMsgForContent('smartwiki-prefix'), '', $this->classes[$i]->getTitle()->getText()) . 
						"|autocomplete on category=".$this->classes[$i]->getName()."}}\n\n" 
				:
					""
				).
				"== All " . $this->classes[$i]->getPluralName() . " ==\n\n" . 
				"{{#ask:[[Category:" . $this->classes[$i]->getName() . "]]\n" . 
				"	|?SmartWiki Is completed=Completed %:\n" . 
				"	|format=category\n" . 
				"	|limit=100\n" . 
				"	|offset=0\n" . 
				"}}\n";
			
			$originalText = $article->getRawText();
			if ( trim($originalText) != trim($article_text) ) {
				$this->log->add($article_title, ($article_title->isKnown() ? SWState::LOG_EDITED : SWState::LOG_CREATED));
				
			} else {
				$this->log->add($article_title, ($article_title->isKnown() ? SWState::LOG_NOACTION : SWState::LOG_CREATED));
			}

			$article->doEdit($article_text, 'SmartWiki (re)created the article ' . $this->classes[$i]->getTitle());
			# END - Article
		}
	}

	/**
	 * Create all the "SmartWiki Class properties" from the SmartWiki Design
	 */
	private function createClassProperties() {
		for ($i = 0; $i < count($this->attributes); $i++) {
			$propertyTitle = Title::newFromText(str_replace(wfMsgForContent('smartwiki-prefix'), '', $this->attributes[$i]->getTitle()->getText()), SMW_NS_PROPERTY);
			$article = new Article($propertyTitle);
			
			$originalText = $article->getRawText();

			$container_class = $this->getClassByTitle($this->attributes[$i]->getClass());

			$property_text = 
				"{{SmartWiki Glossary term\n" . 
				"|Name=" . $this->attributes[$i]->getName() . "\n" . 
				"|Description=" . $this->attributes[$i]->getDescription() . "\n" . 
				"}}\n\n" . 
				"This is a property of type [[Has type::" . $this->attributes[$i]->getDatatype() . "]].";
			foreach ($this->enumerations AS $key => $value) {
				if ($this->attributes[$i]->getEnumeration() == $value->getTitle()->getText()) {
					$property_text .= "\n\nThe allowed values for this property are:";

					$possible_values = explode(',', $value->getPredefinedValues());
					foreach ($possible_values AS $allows_value) {
						$property_text .= "\n* [[Allows value::" . $allows_value . "]]";
					}
				}
			}
			if ( trim($originalText) != trim($property_text) ) {
				$this->log->add($propertyTitle, ($propertyTitle->isKnown() ? SWState::LOG_EDITED : SWState::LOG_CREATED));
			} else {
				$this->log->add($propertyTitle, ($propertyTitle->isKnown() ? SWState::LOG_NOACTION : SWState::LOG_CREATED));
				
			}

			$article->doEdit($property_text, 'SmartWiki (re)created the property ' . $this->attributes[$i]->getName());
		}

		for ($i = 0; $i < count($this->associations); $i++) {
			$title = Title::newFromText($this->associations[$i]->getTitle()->getText(), SMW_NS_PROPERTY);
			$article = new Article($title);
			$originalText = $article->getRawText();
				
			$property_text = 
				"{{SmartWiki Glossary term\n" . 
				"|Name=" . $this->associations[$i]->getName() . "\n" . 
				"|Description=" . $this->associations[$i]->getDescription() . "\n" . 
				"}}\n" . 
				"This is a property of type [[Has type::Page]].";

			if ($this->associations[$i]->getToClass() != '') {
				$property_text .= " It links to pages that use the form [[Has default form::" . $this->associations[$i]->getToClass()->getTitle()->getText() . "]].";
			}
			
			if ( trim($originalText) != trim($property_text) ) {
				$this->log->add($title, ($title->isKnown() ? SWState::LOG_EDITED : SWState::LOG_CREATED));
			} else {
				$this->log->add($title, ($title->isKnown() ? SWState::LOG_NOACTION : SWState::LOG_CREATED));
			}

			$article->doEdit($property_text, 'SmartWiki (re)created the property ' . $this->associations[$i]->getName());
		}

		# Assodication Classes
		for ($i = 0; $i < count($this->associationClasses); $i++) {
			$title = Title::newFromText($this->associationClasses[$i]->getTitle()->getText(), SMW_NS_PROPERTY);
			$article = new Article($title);
			$originalText = $article->getRawText();
				
		
			$property_text =
						"{{SmartWiki Glossary term\n" . 
						"|Name=" . $this->associationClasses[$i]->getName() . "\n" . 
						"|Description=" . $this->associationClasses[$i]->getDescription() . "\n" . 
						"}}\n" . 
						"This is a property of type [[Has type::Page]].";
		
			if ($this->associationClasses[$i]->getToClass() != '') {
				$property_text .= " It links to pages that use the form [[Has default form::" . $this->associationClasses[$i]->getToClass()->getTitle()->getText() . "]].";
			}
			
			if ( $originalText != $property_text ) {
				$this->log->add($title, ($title->isKnown() ? SWState::LOG_EDITED : SWState::LOG_CREATED));
			} else {
				$this->log->add($title, ($title->isKnown() ? SWState::LOG_NOACTION : SWState::LOG_CREATED));
			}
			
			$article->doEdit($property_text, 'SmartWiki (re)created the property ' . $this->associationClasses[$i]->getName());
		}
		
		
	}

	/**
	 * Create all the "SmartWiki Class templates" from the SmartWiki Design
	 */
	private function createClassTemplates() {
		# All the classes
		for ($i = 0; $i < count($this->classes); $i++) {
			$isKnowledgeElement = $this->classes[$i]->getIsKnowledgeElement()->equals(new SWBoolean(TRUE));
			$this->classes[$i] = $this->createClassTemplate($this->classes[$i], true, $isKnowledgeElement);
		}

		# All the association classes
		for ($i = 0; $i < count($this->associationClasses); $i++) {
			$this->associationClasses[$i] = $this->createClassTemplate($this->associationClasses[$i], false, false);
		}
	}

	/**
	 * Create a "SmartWiki Class template" from the SmartWiki Design using the given (association) class
	 */
	private function createClassTemplate(&$currentClass, $isInformationEntity = true, $isKnowledgeElement = true) {
		$title = Title::newFromText(str_replace(wfMsgForContent('smartwiki-prefix'), '', $currentClass->getTitle()->getText()), NS_TEMPLATE);
		$this->log->add($title, ($title->isKnown() ? SWState::LOG_EDITED : SWState::LOG_CREATED));
		$article = new Article($title);
		
		# Get all attributes of this class
		$myAttributes = array();
		foreach ($this->attributes AS $key => $value) {
			//if ($value->getClass() == $currentClass->getTitle()->getText()) {
			if ( $value->getClass()->getTitle()->equals($currentClass->getTitle()) ) {
				$order = $value->getOrder()->getValue();
				if ( !isset($myAttributes[$order]) ) {
					$myAttributes[$order] = array();
				}
				$myAttributes[$order][] = $value;
			}
		}
		// Sort lol
		ksort($myAttributes);
		
		$currentClass->setAttributes($myAttributes);

		$myFromAssociations = $currentClass->getFromAssociations();
		if ( !is_array($myFromAssociations) ) $myFromAssociations = array();
		$myToAssociations = $currentClass->getToAssociations();
		if ( !is_array($myToAssociations) ) $myToAssociations = array();
		
		$template_text = 
			"<noinclude>\n" . 
			"This is the \"" . $currentClass->getTitle() . "\" template.\n" . 
			"It should be called in the following format:\n" . 
			"<pre>\n" . 
			"{{" . $currentClass->getTitle() . "\n";

		# Information entity check and knowledge element check
		$isInformationEntity = $currentClass->getAbstract() == NULL || $currentClass->getAbstract()->equals(new SWBoolean(FALSE));
		$isKnowledgeElement = $currentClass->getIsKnowledgeElement() != NULL && $currentClass->getIsKnowledgeElement()->equals(new SWBoolean(TRUE));

		# Information entity fields
		if ($isInformationEntity) {
			$template_text .= 
				"|Description=\n";
		}

		# All fields we add, we use this for the percentage complete later
		$allFields = array();

		foreach ($myAttributes AS $outerKey => $outerValue) {		
			foreach ($outerValue AS $key => $value) {			// order
				$template_text .= 
					"|" . $value->getTitle() . "=\n";

				$allFields[] = $value->getTitle();
			}
		}

		foreach ($myFromAssociations AS $outerKey => $value) {
			//foreach ($outerValue AS $key => $value) {
				$template_text .= 
					"|" . $value->getTitle() . "=\n";

				$allFields[] = $value->getTitle();
			//}
		}

		# Add the knowledge element option if needed
		if ($isKnowledgeElement) {
			$template_text .= 
//				"|Completed=42\n" .  
				"|Status=\n";
		}

		# Add SmartWiki Information Entity
		if ($isInformationEntity) {
			$template_text .= 
				"|Content manager=\n" . 
				"|Owner=\n";
		}

		$template_text .=
			"}}\n" . 
			"</pre>\n" . 
			"Edit the page to see the template text.\n" . 
			"</noinclude><includeonly>";
		$isAssociationClass = get_class($currentClass) == "SWAssociationClass" ? true : false;
		
		$tblClass = $isAssociationClass ? "smartwikiTableAssociationView" : "smartwikiTableView";
		if ( $isAssociationClass ) {
			
			$template_text .= "<table class='smartwikiTableView'><tr><td class='label'>".$currentClass->getPluralName()."</td>" .
					"<td class='info'>{{#info:{{#show: Property:" . $currentClass->getTitle() . " | ?SmartWiki Has description}} }}</td><td class='input'>";
		}
		$template_text .= "<div class='".($isAssociationClass ? "associationClass" : "")."'>";


		# Add SmartWiki Knowledge element
		if ($isKnowledgeElement == true) {
			$template_text .=
				"<div style='display:none;'>Percentage completed " . $currentClass->getName() .
				"[[SmartWiki Is completed::";

			$field_count = count($allFields);
			if ($field_count > 0) {
				$template_text .= 
					"{{#expr:";
	
				$field_count = count($allFields);
				$field_percentage = floor(100 / $field_count);
				for ($i = 0; $i < $field_count; $i++) {
					$template_text .=
						"{{#if: {{{" . $allFields[$i] . "|}}} | " . $field_percentage . " | 0}}" . ($i + 1 < $field_count ? " + " : "") . "";
				}
	
				$template_text .=
						"}}";
			} else {
				$template_text .=
						"100";
			}
			$template_text .=
				"]]%</div>";
		}
// NEWCODE - END
/*
				"|-\n" . 
				"! Status\n" . 
				"| {{#info:{{#show: Property:SmartWiki Has status | ?SmartWiki Has description}} }}\n" . 
				"| [[SmartWiki Has status::{{{Status|}}}]]\n" . 
				"|-\n";
		}

		# Add table to the right
		if (($isInformationEntity == true) || ($isKnowledgeElement == true)) {
			$template_text .=
				"|}\n";
		}

		// End of right-hand side box
		
		# Add SmartWiki Information entity
		if ($isInformationEntity == true) {
			$template_text .=
				"'''Description:''' {{#info:{{#show: Property:SmartWiki Has description | ?SmartWiki Has description}} }}\n\n" . 
				"[[SmartWiki Has description::{{{Description|}}}]]\n\n\n";
		}
		*/
		$d = "";
		//if ( $_SERVER['SITE_ENV'] == 'D') {$template_text .= "== TEMPLATE: ".$currentClass->getName()."==\n\n";}
		$template_text .= "<table class='".$tblClass."'>";
		
		if ( get_class($currentClass) == "SWAssociationClass" ) {
			$targetClass = $currentClass->getToClass();
			$fromClass = $currentClass->getFromClass();
			$d = "";
			if ( $_SERVER['SITE_ENV'] == 'D' ) $d = "[SWAssociationClass From=".$fromClass->getName()." To=".$targetClass->getName()." Name=".$currentClass->getName()."]";
			$template_text .=
				"<tr>" . //{{#smartwiki:objectName}} ".$currentClass->getName(). " " .
				"<td class='label'>" . $targetClass->getName() . "</td>" .
				"<td class='info'>{{#info:{{#show: Property:" . $currentClass->getTitle() . " | ?SmartWiki Has description}} }}</td>" . 
				"<td class='input'>{{#arraymap:{{{toClass|}}}|,|@@@@|[[".$currentClass->getTitle()."::@@@@]]}}</td>" .
				"</tr>";
		}

		foreach ($myAttributes AS $outerKey => $outerValue) {
			foreach ($outerValue AS $key => $value) {
				$template_text .= 
					"<tr>" .
					"<td class='label'>" . $value->getName() . "</td>" .
					"<td class='info'>{{#info:{{#show: Property:" . $value->getTitle() . " | ?SmartWiki Has description}} }}</td>" . 
					"<td class='input'>{{#arraymap:{{{" . $value->getTitle() . "|}}}|,|@@@@|[[" . $value->getTitle() . "::@@@@]]}}</td>" .
					"</tr>";
			}
		}

		foreach ($myFromAssociations AS $outerKey => $value) {
			$toClass = $value->getToClass(); 
			$fromClass = $value->getFromClass();
			$d = "";
			//if ( $_SERVER['SITE_ENV'] == 'D' ) $d = "[myFromAssociations]";
			$template_text .=
				"<tr>" .
				"<td class='label'>" . /*$fromClass->getName() . " " .*/ self::makeFirstLetterAnUpperCaseLetter($value->getName()) . " " . $toClass->getName() . "</td>" .
				"<td class='info'>{{#info:{{#show: Property:" . $value->getTitle() . " | ?SmartWiki Has description}} }}</td>" . 
				"<td class='input'>{{#arraymap:{{{" . $value->getTitle() . "|}}}|,|@@@@|[[" . $value->getTitle() . "::@@@@]]}}</td>" .
				"</tr>";
		}

		# Associations
		foreach ($myToAssociations AS $outerKey => $value) {
			$d = "";
			$toClass = $value->getToClass(); 
			$fromClass = $value->getFromClass();
			$reverseName = (string)$value->getReverseName();
			if ( empty($reverseName) ) {
				$reverseName = "(reverse of: ".$value->getName().")";
			}
			//if ( $_SERVER['SITE_ENV'] == 'D' ) $d = "[myToAssociations (reverseName)]";
			$template_text .= 
				"<tr>" .
				"<td class='label'>" . /*$toClass->getName() . " " .*/ self::makeFirstLetterAnUpperCaseLetter($reverseName) . " " . $fromClass->getName() . "</td>" .
				"<td class='info'>{{#info:{{#show: Property:" . $value->getTitle() . " | ?SmartWiki Has description}} }}</td>" . 
				"<td class='input'>{{#ask:[[" . $value->getTitle() . "::{{SUBJECTPAGENAME}}]]|format=list}}</td>" .
				"</tr>";
		}
		
		# Association classes
		foreach($this->associationClasses as $key => $value) {
			if ( $value->getToClass()->getTitle()->equals($currentClass->getTitle()) ) {
				$d = "";
				$toClass = $value->getToClass();
				$fromClass = $value->getFromClass();
				$reverseName = (string)$value->getReverseName();
				if ( empty($reverseName) ) {
					$reverseName = "(reverse of: ".$value->getName().")";
				}
				//if ( $_SERVER['SITE_ENV'] == 'D' ) $d = "[SWAssociationClass (reverseName) From=".$fromClass->getName()." To=".$toClass->getName()." Name=".$value->getName()."]";
				$template_text .=
					"<tr>" .
					"<td class='label'>". self::makeFirstLetterAnUpperCaseLetter($reverseName) ." ". $fromClass->getPluralName() ."</td>" .
					"<td class='info'>{{#info:{{#show: Property:" . $value->getTitle() . " | ?SmartWiki Has description}} }}</td>" . 
					"<td class='input'>{{#ask:[[" . $value->getTitle() . "::{{SUBJECTPAGENAME}}]]|format=list}}</td>" .
					"</tr>";
			} 
		}
		
		$template_text .= "</table>";
		// Dervied/subelements for associationclasses
		
		if ( get_class($currentClass) == "SWAssociationClass" ) {
			if ( $currentClass->getCanHaveDerivables() != null && $currentClass->getCanHaveDerivables()->equals(new SWBoolean(true) ) ) {
				$template_text .= self::readTemplate("SmartWiki_Derivable".($isAssociationClass?"":"_AC"));
			}
			if ( $currentClass->getCanHaveSubelements() != null && $currentClass->getCanHaveSubelements()->equals(new SWBoolean(true) ) ) {
				$template_text .= self::readTemplate("SmartWiki_Subelement".($isAssociationClass?"":"_AC"));
			}
			
		}

		$template_text .= 
			"[[Category:" . $currentClass->getName() . "]]" . 
			"</div>" . (get_class($currentClass) == "SWAssociationClass" ? "" :"" ) .
			($isAssociationClass ? "</td></tr></table>" : "") .
			"</includeonly>";

		$article->doEdit($template_text, 'SmartWiki (re)created the template ' . $currentClass->getName());

		return $currentClass;
	}

	/**
	 * Create all the "SmartWiki Class forms" from the SmartWiki Design
	 */
	private function createClassForms() {
		for ($i = 0; $i < count($this->classes); $i++) {
			$title = Title::newFromText(str_replace(wfMsgForContent('smartwiki-prefix'), '', $this->classes[$i]->getTitle()->getText()), SF_NS_FORM);
			$this->log->add($title, ($title->isKnown() ? SWState::LOG_EDITED : SWState::LOG_CREATED));
			$article = new Article($title);
			
			if ($this->classes[$i]->getAbstract()->equals(new SWBoolean(FALSE))) {

				$form_text = 
					"__NOTOC__<noinclude>\n" . 
					"This is the \"" . $this->classes[$i]->getTitle() . "\" form.\n" . 
					"To create a page with this form, enter the page name below;\n" . 
					"if a page with that name already exists, you will be sent to a form to edit that page.\n" . 
					"\n" . 
					"{{#forminput:form=" . $this->classes[$i]->getTitle() . "}}\n" . 
					"</noinclude><includeonly>" . 
					"<div id=\"wikiPreview\" style=\"display: none; padding-bottom: 25px; margin-bottom: 25px; border-bottom: 1px solid #AAAAAA;\"></div>"
				;

				// Add pulldown box for "change object type"
				$form_text .= 
					"<table class='smartwikiTableEdit'>".
					"<tr><td class='label'>Change object type</td>" .
					"<td class='info'>{{#info:". wfMsgForContent('smartwiki-help-change-object') ."}}</td>" .
					"<td class='input'>{{#smartwiki: check}}</td></tr>" .
					"</table>"
				;

				$isInformationEntity = $this->classes[$i]->getAbstract()->equals(new SWBoolean(FALSE));
				$isKnowledgeElement = $this->classes[$i]->getIsKnowledgeElement()->equals(new SWBoolean(TRUE));

				// Add SmartWiki Information Entity
				if ( $isInformationEntity || $isKnowledgeElement ) {
					$form_text .= "{{{for template|SmartWiki Information entity}}}\n";
					if ( $isInformationEntity )  {
						$form_text .= 
							"<table class='smartwikiTableEdit'>" .
							"<tr><td class='label'>Description</td>".
							"<td class='info'>{{#info:{{#show: Property:SmartWiki Has description | ?SmartWiki Has description}} }}</td>" . 
							"<td class='input'>{{{field|Description}}}</td></tr>".
							"</table>" .
							// Must put this in first template-block so that the righthandside box will dispaly at the top on the right
							"{{{field|righthandsidebox|holds template}}}" .
							// Hidden class name. This property defines what class this object is derived of! (And defines what form to use)
							"{{{field|Class name|hidden|default=".$this->classes[$i]->getTitle()->getText()."}}}";
					}
					if ( $isKnowledgeElement ) {
						/*
						$form_text .=
							"'''Status:''' {{#info:{{#show: Property:SmartWiki Has status | ?SmartWiki Has description}} }}\n\n" . 
							"{{{field|Status}}}\n\n\n";
							*/
					}
					$form_text .= "{{{end template}}}";
				}
				
				// Add the knowledge element option if needed

				// Get the titles from the Classes this Class generalizes
				/*
				$generalizationTitles = array();
				$n = $this->classes[$i]->getChildGeneralizations();
				if ( is_array($n) ) {
					foreach ($this->classes[$i]->getChildGeneralizations() AS $outerKey => $outerValue) {
						foreach ($outerValue AS $key => $value) {
							$generalizationTitles[] = str_replace(wfMsgForContent('smartwiki-prefix'), '', $value->getParentClass());
						}
					}
				}
				echo "genTitles = ".var_export($generalizationTitles,1)."<BR>";
				// Add templates for all the generalized classes
				for ($j = 0; $j < count($this->classes); $j++) {
					if ((!$this->classes[$i]->getTitle()->equals($this->classes[$j]->getTitle())) && 
					(in_array($this->classes[$j]->getTitle(), $generalizationTitles))) {
						$form_text .= $this->createFormUsingClass($this->classes[$j], false, false, false);
						
					}
				}
				*/
				
				// Add templates for this class itself
				$skipFields = array();
				$form_text_ThisClass = $this->createFormUsingClass($this->classes[$i], false, true, $isKnowledgeElement, $skipFields);
				if ( $form_text_ThisClass == "" ) {
					$form_text_ThisClass = "{{{for template|".$this->classes[$i]->getTitle()."}}}\n{{{end template}}}";
				}
				
				// Add templates of this class parents
				$classList = array();
				$this->getParentGeneralizations($this->classes[$i], $classList);
				
				$form_text_Parents = "";
				/*
				usort($classList,
					function($a, $b) {
						return $a->getOrder()->getValue() < $b->getOrder()->getValue() ? -1 : 1; 
					}
				);*/
				for($j = 0; $j < count($classList); $j++ ) {
					$form_text_Parents .= $this->createFormUsingClass($classList[$j], false, false, false, $skipFields, $this->classes[$i]);
				}
				
				$form_text .= $form_text_ThisClass . $form_text_Parents;

				# Add all the association classes for this form
				for ($j = 0; $j < count($this->associationClasses); $j++) {
					if ($this->associationClasses[$j]->getFromClass() != NULL && 
						$this->associationClasses[$j]->getFromClass()->equals($this->classes[$i])) {
						$form_text .= $this->createFormUsingClass($this->associationClasses[$j], true);
					} else if ( $this->associationClasses[$j]->getFromClass() != NULL ) {
						// Check parents
						for($k =0;$k < count($classList); $k++) {
							if ( $this->associationClasses[$j]->getFromClass()->equals($classList[$k])) {
								$form_text .= $this->createFormUsingClass($this->associationClasses[$j], true);
							}
						}
					}
				}

				// Add the derivable option is needed
				if ($this->classes[$i]->getCanHaveDerivables()->equals(new SWBoolean(TRUE))) {
					$form_text .=
										"{{{for template|SmartWiki Derivable}}}\n" .
										"<table class='smartwikiTableEdit'>" .
										
										"<tr><td class='label'>Is derived from</td>" .
										"<td class='info'>{{#info:{{#show: Property:SmartWiki Is derived from | ?SmartWiki Has description}} }}</td>" . 
										"<td class='input'>{{{field|Is derived element of|values from category=" . $this->classes[$i]->getName() . "}}}</td>" .
										"</tr></table>" . 
										"{{{end template}}}";
				}
				
				// Add the subelement option is needed
				if ($this->classes[$i]->getCanHaveSubelements()->equals(new SWBoolean(TRUE))) {
					$form_text .=
										"{{{for template|SmartWiki Subelement}}}\n" .
										"<table class='smartwikiTableEdit'>" .
										"<tr><td class='label'>Is subelement of</td>" .
										"<td class='info'>{{#info:{{#show: Property:SmartWiki Is subelement of | ?SmartWiki Has description}} }}</td>" . 
										"<td class='input'>{{{field|Is subelement of|values from category=" . $this->classes[$i]->getName() . "}}}</td>" .
										"</tr></table>" . 
										"{{{end template}}}";
				}
				
				$form_text .= 
					"<table class='smartwikiTableEdit'>" .

					// Free text
					"<tr><td class='label'>Free text</td><td class='info'>{{#info:You can add extra text in the box below.}}</td>" . 
					"<td class='input'>{{{standard input|free text|rows=10}}}</td></tr>" .
					
					// Additional info (Remarks)
					"<tr><td class='label'>Additional information</td>" .
					"<td class='info'>{{#info:{{#show: Property:SmartWiki Has remark | ?SmartWiki Has description}} }}</td><td class='input'>" . 
					"{{{for template|SmartWiki Remark|multiple}}}\n" . 
					"{{{field|Remark}}}\n\n\n" . 
					"{{{end template}}}" . 
					"</td></tr>" .

					// Attached files
					"<tr><td class='label'>Attached file(s)</td><td class='info'>{{#info:{{#show: Property:SmartWiki Has attached file | ?SmartWiki Has description}} }}</td>" . 
					"<td class='input'>{{{for template|SmartWiki Attached file|multiple}}}" .
					//"'''Attached file:'''\n\n" . 
					"{{{field|Attached file|uploadable}}}\n\n\n" . 
					"{{{end template}}}" .
					"</td></tr>" . 
					"</table>";

				$form_text .= 
					"{{{for template|SmartWiki Righthand sidebox|embed in field=SmartWiki Information entity[righthandsidebox]|label=Mmm}}}\n" .
					"<table class='smartwikiTableEdit'>" .
				    
				    // Content manager
				    "<tr><td class='label'>Content manager</td>" .
				    "<td class='info'>{{#info:{{#show: Property:SmartWiki Has content manager | ?SmartWiki Has description}} }}</td>" .
					"<td class='input'>{{{field|Content manager}}}</td></tr>" .

					// Owner
					"<tr><td class='label'>Owner</td>" .
					"<td class='info'>{{#info:{{#show: Property:SmartWiki Has owner | ?SmartWiki Has description}} }}</td>" . 
					"<td class='input'>{{{field|Owner}}}</td></tr>" .
					
					// Status
					( $isKnowledgeElement ?
					"<tr><td class='label'>Status</td>" .
					"<td class='info'>{{#info:{{#show: Property:SmartWiki Has status | ?SmartWiki Has description}} }}</td>" .
					"<td class='input'>{{{field|Status}}}</td></tr>"
					: "" ) .
					"</table>" .
					"{{{end template}}}"
				;
				
				$form_text .= 
				"\n" .
				"{{{standard input|summary}}}\n" .
				"\n" .
				"{{{standard input|minor edit}}} {{{standard input|watch}}}\n" .
				"\n" .
				"{{{standard input|save}}} {{{standard input|preview}}} {{{standard input|changes}}} {{{standard input|cancel}}}\n";
				
				
				$form_text .= 
					"</includeonly>";
				
				
			} else {
				$par = array();
				self::getChildGeneralizations($this->classes[$i], $par);
				$list = "";
				foreach($par as $p) {
					$list .= "[[Special:FormEdit/".$p->getTitle()->getText()."/{{#smartwiki:title|2}}|".$p->getName()."]]<br>";
				}
				$form_text =
									"__NOTOC__<noinclude>\n" . 
									"This is the \"" . $this->classes[$i]->getTitle() . "\" form.\n" . 
									"This object is abstract, a selection will be shown." . 
									"</noinclude><includeonly>" . 
									"<div id=\"wikiPreview\" style=\"display: none; padding-bottom: 25px; margin-bottom: 25px; border-bottom: 1px solid #AAAAAA;\"></div>\n".
									"{{#smartwiki:check}}\n".
									"Please select a ".$this->classes[$i]->getName().":<br>".
									$list.
									// Hide form buttons and stuff.
									"<div style='display: none'>".
									"{{{standard input|summary}}}\n" .
									"\n" . 
									"{{{standard input|minor edit}}} {{{standard input|watch}}}\n" . 
									"\n" . 
									"{{{standard input|save}}} {{{standard input|preview}}} {{{standard input|changes}}} {{{standard input|cancel}}}\n";
				"</div>".
				"</includeonly>";
												
			}
			$article->doEdit($form_text, 'SmartWiki (re)created the form ' . $this->classes[$i]->getName());
		}
	}
	
	// Get recursive parent classes
	private function getParentGeneralizations($class, &$classList) {
		$gens = $class->getParentGeneralizations();
		for($j = 0; $j < count($gens); $j++ ) {
			$classList[] = $gens[$j]->getParentClass();
			$this->getParentGeneralizations($gens[$j]->getParentClass(), $classList);
		}
		
	}

	// Get recursive child classes
	private function getChildGeneralizations($class, &$classList) {
		$gens = $class->getChildGeneralizations();
		for($j = 0; $j < count($gens); $j++ ) {
			$classList[] = $gens[$j]->getChildClass();
			$this->getChildGeneralizations($gens[$j]->getChildClass(), $classList);
		}
	}

	private static function makeFirstLetterAnUpperCaseLetter($text) {
		return strtoupper(substr($text,0,1)).substr($text,1);
	}
	
	private function createFormUsingClass($class, $allowMultiple = false, $isInformationEntity = false, $isKnowledgeElement = false, &$skipFields = array(), $rootClass = null) {
		// $rootClass = Original class that calles this function (Example, this can be the 'Child', $class will be the parent or even grandparent)
		# Form text
		$form_text = "";
	
		$fieldCount = 0;

		# Is multiple items are allowed, show title
		if ( get_class($class) == "SWAssociationClass" ) {
			$targetClass = &$class->getToClass();
			$thisClassIsAnAssociationClass = true;
		} else {
			$targetClass = &$class;
			$thisClassIsAnAssociationClass = false;
		}
		
		if ($allowMultiple == true) {
			//$form_text .= "== " . $class->getPluralName() . " ==\n";
			$form_text .= "<table class='smartwikiTableEdit'><tr><td class='label'>".$class->getPluralName()."</td>" .
					"<td class='info'>{{#info:{{#show: Property:" . $class->getTitle() . " | ?SmartWiki Has description}} }}</td>" .
					"<td class='input'>"
			;
		}

		$form_text .= 
			"{{{for template|" . $class->getTitle() . ($allowMultiple == true ? "|multiple" : "") . "}}}\n";
		
		if ( !$allowMultiple ) {
			$form_text .= "<table class='smartwikiTableEdit'>";
		}

		
		# Association Class target name
		if ( $thisClassIsAnAssociationClass ) {
			$label = /*$class->getName() . " " . */ $targetClass->getName();
			$info = "{{#info:{{#show: Property:" . $class->getTitle() . " | ?SmartWiki Has description}} }}";
			$input = "{{{field|toClass|autocomplete|values from category=".$targetClass->getName()."|input type=text with autocomplete|delimiter=}}}";
			
			if ( !$allowMultiple ) {
				$form_text .= "<tr><td class='label'>" . $label . "</td><td class='info'>" . $info . "</td><td class='input'>" . $input . "</td></tr>";
			} else { 
				$form_text .= "".$label." : ".$info."\n\n" . $input."\n\n\n";
			}
		}
		
		
		# Attributes	// Attributes were filled and sorted at createClassTemplate() function
		foreach ($class->getAttributes() AS $outerKey => $outerValue) {
			foreach ($outerValue AS $key => $value) {
				if ( !in_array($value->getTitle()->getText(),$skipFields) ) {
					$label = $value->getName();
					$info = "{{#info:{{#show: Property:" . $value->getTitle() . " | ?SmartWiki Has description}} }}";

					if ( $value->getEnumeration() != null ) {
						$opties = $value->getEnumeration()->getDescription();
						$input = "{{{field|" . $value->getTitle() . "|input type=dropdown|values=".$opties."|size=90}}}";
						
					} else {
						$input = "{{{field|" . $value->getTitle() . "|autocomplete|autocomplete on property=".$value->getTitle()."|input type=text with autocomplete|size=90}}}";
						
					}

					if ( !$allowMultiple ) {
						$form_text .= "<tr><td class='label'>" . $label . "</td><td class='info'>" . $info . "</td><td class='input'>" . $input . "</td></tr>";
					} else { 
						$form_text .= "".$label.": ".$info."\n\n" . $input."\n\n\n";
					}
										
					$fieldCount++;
					$skipFields[] = $value->getTitle()->getText();
				}
			}
		}
		if ( is_array($class->getFromAssociations()) ) {
			// Associations are not sorted yet
			
			foreach ($class->getFromAssociations() AS $outerKey => /*$outerValue*/ $value) {
				if ( !in_array($value->getTitle()->getText(),$skipFields) ) {
					//$fromTitle = ($rootClass != NULL && !$thisClassIsAnAssociationClass ? $rootClass->getName() : $value->getFromClass()->getName());
					$label =  /*$fromTitle . " " .*/ self::makeFirstLetterAnUpperCaseLetter($value->getName()) . " " . $value->getToClass()->getPluralName();
					$info = "{{#info:{{#show: Property:" . $value->getTitle() . " | ?SmartWiki Has description}} }}";
					$input = "{{{field|" . $value->getTitle() . "|autocomplete|values from category=" . $value->getToClass()->getName() . "|input type=text with autocomplete|size=90}}}"; 

					if ( !$allowMultiple ) {
						$form_text .= "<tr><td class='label'>" . $label . "</td><td class='info'>" . $info . "</td><td class='input'>" . $input . "</td></tr>";
					} else {
						$form_text .= "'''".$label.":''' ".$info."\n\n" . $input."\n\n\n";
					}
					
					$fieldCount++;
					$skipFields[] = $value->getTitle()->getText();
				}
			}
		}
		
		if ( $thisClassIsAnAssociationClass ) {
			// Add the derivable option is needed
			if ( $class->getCanHaveDerivables() != null && $class->getCanHaveDerivables()->equals(new SWBoolean(TRUE))) {
				$label = "Is derived from";
				$info = "{{#info:{{#show: Property:SmartWiki Is derived from | ?SmartWiki Has description}} }}";
				$input = "{{{field|Is derived element of|autocomplete|values from category=" . $class->getName() . "}}}";
				
				if ( !$allowMultiple ) {
					$form_text .= "<tr><td class='label'>" . $label . "</td><td class='info'>" . $info . "</td><td class='input'>" . $input . "</td></tr>";
				} else {
					$form_text .= "'''".$label.":''' ".$info."\n\n" . $input."\n\n\n";
				}
				
			}
			
			// Add the subelement option is needed
			if ( $class->getCanHaveSubelements() != null && $class->getCanHaveSubelements()->equals(new SWBoolean(TRUE))) {
				$label = "Is subelement of";
				$info = "{{#info:{{#show: Property:SmartWiki Is subelement of | ?SmartWiki Has description}} }}";
				$input = "{{{field|Is subelement of|autocomplete|values from category=" . $class->getName() . "}}}";
				
				if ( !$allowMultiple ) {
					$form_text .= "<tr><td class='label'>" . $label . "</td><td class='info'>" . $info . "</td><td class='input'>" . $input . "</td></tr>";
				} else {
					$form_text .= "'''".$label.":''' ".$info."\n\n" . $input."\n\n\n";
				}
			
			}
		}
		
		if ( !$allowMultiple ) {
			$form_text .= "</table>";
		}
		
		$form_text .=
			"{{{end template}}}";
		
		if ($allowMultiple == true) {
			$form_text .= "</td></tr></table>";
		}
		

		return $form_text;
	}

	private function createManager() {
		global $wgSitename;

		$title = Title::newFromText($wgSitename . ' Manager', NS_MAIN);
		$this->log->add($title, ($title->isKnown() ? SWState::LOG_EDITED : SWState::LOG_CREATED));
		$article = new Article($title);

		# TODO: Create content
		$manager_text = '';

		foreach ($this->packages AS $key => $value) {
			$manager_text .= 
				"== " . $value->getTitle() . " ==\n" . 
				"{{#ask:\n" . 
				"  [[Category:SmartWiki Wiki elements]]\n" . 
				"  [[SmartWiki Is part of package::SmartWiki Package : " . $value->getTitle() . "]]\n" . 
				"  |?SmartWiki Has name=Name:\n" . 
				"  |format=table\n" . 
				"  |limit=500\n" . 
				"  |headers=plain\n" . 
				"  |mainlabel=Title:\n" . 
				"  |link=all\n" . 
				"  |order=ASC\n" . 
				"  |sort=SmartWiki Has name\n" . 
				"  |offset=0\n" . 
				"}}\n\n\n\n";
		}

		$article->doEdit($manager_text, 'SmartWiki (re)created the SmartWiki Manager');
	}

	private function createGlossary() {
		global $wgSitename;

		$title = Title::newFromText($wgSitename . ' Glossary', NS_MAIN);
		$this->log->add($title, ($title->isKnown() ? SWState::LOG_EDITED : SWState::LOG_CREATED));
		$article = new Article($title);

		# TODO: Create content
		$manager_text = 
			"Here is a list of terms used in this Wiki. You can add a term by entering a name in the field below:\n" .  
			"\n" . 
			"{{#forminput:form=SmartWiki Glossary term}}\n" . 
			"\n" . /*
			"{{#ask:\n" . 
			"  [[Category:SmartWiki Glossary term]]\n" . 
			"  |format=category\n" . 
			"  |limit=500\n" . 
			"  |headers=plain\n" . 
			"  |link=all\n" . 
			"  |order=ASC\n" . 
			"  |offset=0\n" . 
			"}}" .   */ 
			"{{#smartwiki:glossary}}" .
			"\n\n\n\n";

		$article->doEdit($manager_text, 'SmartWiki (re)created the SmartWiki Glossary');
	}

	/**
	 * Get all the fields from pages with the specified category
	 */
	public static function getFieldsByCategory($categoryName) {
		$category = Category::newFromName($categoryName);

		$titleIterator = $category->getMembers();
		$fields = array();
		while ($titleIterator->valid()) {
			$titleObject = $titleIterator->current();
			$f = SWTransformer::getFieldsFromPage($titleObject);
			$fields[] = $f;
			$titleIterator->next();
		}

		return $fields;
	}

	/**
	 * Get all the fields from the specified page
	 */
	public static function getFieldsFromPage(Title $title) {
		# The contents of the page
		$article = new Article($title);
		$article_text = $article->getRawText();

		# Find all fields
		$pos = 0;
		$fields = array();
		$fields['titleObject'] = $title;
		
		// Remove any tabs, whitespaces and enters between list tags
		$article_text = preg_replace("|</li>[\n\t\s]*<li>|Ums", "</li><li>", $article_text);
		$article_text = preg_replace("|<ul>[\n\t\s]*<li>|Ums", "<ul><li>", $article_text);
		$article_text = preg_replace("|</li>[\n\t\s]*</ul>|Ums", "</li></ul>", $article_text);
		
		// Match and get the field names and contents
		$pattern = "#\n\|([\s\w\d_]+)=(.*)(?=\n\||\}\})#Usm";	//U = Ungreedy, s = dot includes newlines, m = multiline mode (optional)
		preg_match_all($pattern, str_replace("\r","",$article_text), $matches);
		
		foreach($matches[1] as $k => $v) {
			$fields[$v] = str_replace(array("::", "\n"), array(":", "<br>"), trim($matches[2][$k]));
		}
		
		return $fields;
	}

	private function getClassByTitle($title) {
		for ($i = 0; $i < count($this->classes); $i++) {
			if (wfMsgForContent('smartwiki-prefix') . $this->classes[$i]->getTitle()->getText() == $title) {
				return $this->classes[$i];
			}
		}
		return false;
	}
	
	private static function readTemplate($name) {
		$dir = realpath(dirname( __FILE__ ) . '/../../imports/Template/') ."/";
		
		$file = $dir.$name.".dat";
		if ( !file_exists($file) )  {
			return "Template not found: ".$file."\n";
		}
		
		$d = file_get_contents($file);
		preg_match("#\<includeonly\>(.*)\<\/includeonly\>#sU", $d, $m);
		if ( isset($m[1]) ) {
			return trim($m[1])."\n\n\n";
		}
		return "Template exists (".$file.") but could not find xml tag 'includeonly'.\n";
	}
}
?>