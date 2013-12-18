<?php
class SmartWikiModel {
	private static $instance;

	private $attributes;
	private $enumerations;
	private $associations;
	private $associationClasses;
	private $generalizations;
	private $classes;
	private $packages;

	private function __construct() {
		$this->attributes = array();
		$this->enumerations = array();
		$this->associations = array();
		$this->associationClasses = array();
		$this->generalizations = array();
		$this->classes = array();
		$this->packages = array();
	}

	public static function singleton() {
		if (!isset(self::$instance)) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	public function __clone() {
		trigger_error('Clone is not allowed.', E_USER_ERROR);
	}

	public function __wakeup() {
		trigger_error('Unserializing is not allowed.', E_USER_ERROR);
	}

	public function getAttribute(Title $title = NULL) {
		if ($title == NULL) {
			return NULL;
		}
	
		foreach ($this->attributes AS $attributeKey => $attributeValue) {
			if ($title->equals($attributeValue->getTitle())) {
				return $attributeValue;
			}
		}

		return NULL;
	}

	public function getAttributes() {
		return $this->attributes;
	}

	public function getAttributeByID(SmartWikiString $id = NULL) {
		if ($id == NULL) {
			return NULL;
		}
	
		foreach ($this->attributes AS $attributeKey => $attributeValue) {
			if ($id->equals($attributeValue->getID())) {
				return $attributeValue;
			}
		}

		return NULL;
	}

	public function setAttribute(SmartWikiAttribute $attribute) {
		foreach ($this->attributes AS $attributeKey => $attributeValue) {
			if ($attribute->equals($attributeValue)) {
				$this->attributes[$attributeKey] = $attribute;
				return false;
			}
		}

		$this->attributes[] = $attribute;
		return true;
	}

	public function getEnumeration(Title $title = NULL) {
		if ($title == NULL) {
			return NULL;
		}
	
		foreach ($this->enumerations AS $enumerationKey => $enumerationValue) {
			if ( get_class($enumerationValue) == "SmartWikiEnumeration" ) {
				if ($title->equals($enumerationValue->getTitle())) {
					return $enumerationValue;
				}
			} 
		}

		return NULL;
	}

	public function getEnumerations() {
		return $this->enumerations;
	}

	public function getEnumerationByID(SmartWikiString $id = NULL) {
		if ($id == NULL) {
			return NULL;
		}
	
		foreach ($this->enumerations AS $enumerationKey => $enumerationValue) {
			if ($id->equals($enumerationValue->getID())) {
				return $enumerationValue;
			}
		}

		return NULL;
	}

	public function setEnumeration(SmartWikiEnumeration $enumeration) {
		foreach ($this->enumerations AS $enumerationKey => $enumerationValue) {
			if ($enumeration->equals($enumerationValue)) {
				$this->enumerations[$enumerationKey] = $enumeration;
				return false;
			}
		}

		$this->enumerations[] = $enumeration;
		return true;
	}

	public function getAssociation(Title $title = NULL) {
		if ($title == NULL) {
			return NULL;
		}
	
		foreach ($this->associations AS $associationKey => $associationValue) {
			if ($title->equals($associationValue->getTitle())) {
				return $associationValue;
			}
		}

		return NULL;
	}

	public function getAssociations() {
		return $this->associations;
	}

	public function getAssociationByID(SmartWikiString $id = NULL) {
		if ($id == NULL) {
			return NULL;
		}
	
		foreach ($this->associations AS $associationKey => $associationValue) {
			if ($id->equals($associationValue->getID())) {
				return $associationValue;
			}
		}

		return NULL;
	}

	public function setAssociation(SmartWikiAssociation $association) {
		foreach ($this->associations AS $associationKey => $associationValue) {
			if ($association->equals($associationValue)) {
				$this->associations[$associationKey] = $association;
				return false;
			}
		}

		$this->associations[] = $association;
		return true;
	}

	public function getAssociationClass(Title $title = NULL) {
		if ($title == NULL) {
			return NULL;
		}
	
		foreach ($this->associationClasses AS $associationClassKey => $associationClassValue) {
			if ($title->equals($associationClassValue->getTitle())) {
				return $associationClassValue;
			}
		}

		return NULL;
	}

	public function getAssociationClasses() {
		return $this->associationClasses;
	}

	public function getAssociationClassByID(SmartWikiString $id = NULL) {
		if ($id == NULL) {
			return NULL;
		}
	
		foreach ($this->associationClasses AS $associationClassKey => $associationClassValue) {
			if ($id->equals($associationClassValue->getID())) {
				return $associationClassValue;
			}
		}

		return NULL;
	}

	public function setAssociationClass(SmartWikiAssociationClass $associationClass) {
		foreach ($this->associationClasses AS $associationClassKey => $associationClassValue) {
			if ($associationClass->equals($associationClassValue)) {
				$this->associationClasses[$associationClassKey] = $associationClass;
				return false;
			}
		}

		$this->associationClasses[] = $associationClass;
		return true;
	}

	public function getGeneralization(Title $title = NULL) {
		if ($title == NULL) {
			return NULL;
		}
	
		foreach ($this->generalizations AS $generalizationKey => $generalizationValue) {
			if ($title->equals($generalizationValue->getTitle())) {
				return $generalizationValue;
			}
		}

		return NULL;
	}

	public function getGeneralizations() {
		return $this->generalizations;
	}

	public function getGeneralizationByID(SmartWikiString $id = NULL) {
		if ($id == NULL) {
			return NULL;
		}
	
		foreach ($this->generalizations AS $generalizationKey => $generalizationValue) {
			if ($id->equals($generalizationValue->getID())) {
				return $generalizationValue;
			}
		}

		return NULL;
	}

	public function setGeneralization(SmartWikiGeneralization $generalization) {
		foreach ($this->generalizations AS $generalizationKey => $generalizationValue) {
			if ($generalization->equals($generalizationValue)) {
				$this->generalizations[$generalizationKey] = $generalization;
				return false;
			}
		}

		$this->generalizations[] = $generalization;
		return true;
	}

	public function getClass(Title $title = NULL) {
		if ($title == NULL) {
			return NULL;
		}
	
		foreach ($this->classes AS $classKey => $classValue) {
			if ($title->equals($classValue->getTitle())) {
				return $classValue;
			}
		}

		return NULL;
	}

	public function getClasses() {
		return $this->classes;
	}

	public function getClassByID(SmartWikiString $id = NULL) {
		if ($id == NULL) {
			return NULL;
		}
	
		foreach ($this->classes AS $classKey => $classValue) {
			if ($id->equals($classValue->getID())) {
				return $classValue;
			}
		}

		return NULL;
	}

	public function setClass(SmartWikiClass $class) {
		foreach ($this->classes AS $classKey => $classValue) {
			if ($class->equals($classValue)) {
				$this->classes[$classKey] = $class;
				return false;
			}
		}

		$this->classes[] = $class;
		return true;
	}

	public function getPackage(Title $title = NULL) {
		if ( is_null($title) ) {
			return NULL;
		}

		foreach ($this->packages AS $packageKey => $packageValue) {
			if ($title->equals($packageValue->getTitle())) {
				return $packageValue;
			}
		}
		return NULL;
	}

	public function getPackages() {
		return $this->packages;
	}

	public function getPackageByID(SmartWikiString $id = NULL) {
		if ($id == NULL) {
			return NULL;
		}
	
		foreach ($this->packages AS $packageKey => $packageValue) {
			if ($id->equals($packageValue->getID())) {
				return $packageValue;
			}
		}

		return NULL;
	}

	public function setPackage(SmartWikiPackage $package) {
		foreach ($this->packages AS $packageKey => $packageValue) {
			if ($package->equals($packageValue)) {
				$this->packages[$packageKey] = $package;
				return false;
			}
		}

		$this->packages[] = $package;
		return true;
	}
	
	public function prepareForTransformation() {
		// Remove prefix
		
		$vars = array("classes", "associations", "associationClasses", "attributes", "enumerations", "generalizations", "packages");
		
		$prefix = wfMsgForContent('smartwiki-prefix');
		
		foreach($vars as $var) {
				
			foreach($this->$var as $classKey => $classValue ) {
				$x = &$this->$var;	// php does not allow direct array access when property is a variable as well
				$oldTitle = $x[$classKey]->getTitle();
				if ( get_class($oldTitle) == "Title" ) {
					$newTitle = Title::newFromText(str_replace($prefix, "", $oldTitle->getText()), $oldTitle->getNamespace());
					$x[$classKey]->setTitle($newTitle);
				}
			}
		}
			
	}
	
	/**
	 * Sort's the model arrays and sets orderNr's
	 * This function will preserve current order using orderNr's, but will rewrite the numbers.
	 * Example: You have 5 SmartWiki Class's with the following texts and order nr's: Ananas (-1), Choclate (-1), Banana (-1), Donald (10), Emoes (6)
	 * After you call this function the new order and numbers will be:
	 * Ananas (1), Banana (2), Choclate (3), Emoes (4), Donald (5)
	 * Sorting and numbering happens per package name (each package will start at 1)
	 * 
	 * Also This function will sort and (re-)number attributes in the same way. This happens per parent class ($attribute->getClass())
	 */
	public function sortAndOrderNr() {
		
		// First sort them in the internal array
		usort($this->classes, array($this, "modelSmartWikiClassSortFunction"));
		usort($this->attributes, array($this, "modelSmartWikiAttributeSortFunction"));
		usort($this->associations, array($this, "modelSmartWikiClassSortFunction"));
		usort($this->associationClasses, array($this, "modelSmartWikiClassSortFunction"));
		usort($this->generalizations, array($this, "modelSmartWikiClassSortFunction"));
		
		// Then (re)set the order field
		$this->setOrderNumbers();
		
	}
	
	// (Re-)Set's order numbers 
	private function setOrderNumbers() {
		// Sort per Package
		for ($y = 0; $y < count($this->packages); $y++) {
			$packageTitle = $this->packages[$y]->getTitle();
					
			// (Re-)set order nr's
			$orderNr = 1; // always start at 1
			for ($i = 0; $i < count($this->classes); $i++) {
				$class = $this->classes[$i];
				if ($class->getPackage() == null || $class->getPackage()->getTitle()->equals($packageTitle)) {
					$class->setOrder(new SmartWikiNumber($orderNr));
					$orderNr++;
				}
			}
			
			// (Re-)set order nr's
			$orderNr = 1; // always start at 1
			for ($i = 0; $i < count($this->associationClasses); $i++) {
				$class = $this->associationClasses[$i];
				if ($class->getPackage() == null || $class->getPackage()->getTitle()->equals($packageTitle)) {
					$class->setOrder(new SmartWikiNumber($orderNr));
					$orderNr++;
				}
			}

			// (Re-)set order nr's
			$orderNr = 1; // always start at 1
			for ($i = 0; $i < count($this->associations); $i++) {
				$class = $this->associations[$i];
				if ($class->getPackage() == null || $class->getPackage()->getTitle()->equals($packageTitle)) {
					$class->setOrder(new SmartWikiNumber($orderNr));
					$orderNr++;
				}
			}
			
			// (Re-)set order nr's
			$orderNr = 1; // always start at 1
			for ($i = 0; $i < count($this->generalizations); $i++) {
				$class = $this->generalizations[$i];
				if ($class->getPackage() == null || $class->getPackage()->getTitle()->equals($packageTitle)) {
					$class->setOrder(new SmartWikiNumber($orderNr));
					$orderNr++;
				}
			}			
			
		}
		
		// Sort Attributes
		$attributeOrderNrs = array();
		
		foreach ($this->attributes AS $attributeKey => $attributeValue) {
			$attributeClassTitle = $attributeValue->getClass()->getTitle()->getText();
			if ( !isset($attributeOrderNrs[$attributeClassTitle])) {
				$attributeOrderNrs[$attributeClassTitle] = 0;
			}
			$attributeOrderNrs[$attributeClassTitle]++;
			$this->attributes[$attributeKey]->setOrder(new SmartWikiNumber($attributeOrderNrs[$attributeClassTitle]));
		}		
	}
	
	// Sort SmartWiki Classes on: package name, current order nr, title
	private function modelSmartWikiClassSortFunction($a,$b) {
		// Sort on package name first
		if ( $a->getPackage() == null ) {
			$a_packageName = "";
		} else {
			$a_packageName = strtolower($a->getPackage()->getTitle()->getText());
		}
		if ( $b->getPackage() == null ) {
			$b_packageName = "";
		} else {
			$b_packageName = strtolower($b->getPackage()->getTitle()->getText());
		}
		
		if ( $a_packageName < $b_packageName ) {
			return -1;
		} else if ( $a_packageName > $b_packageName ) {
			return 1;
		}
		
		// Sort on order nr second
		$a_orderNr = $a->getOrder();
		$b_orderNr = $b->getOrder();
		
		if ( $a_orderNr < $b_orderNr ) {
			return -1;
		} else if ( $a_orderNr > $b_orderNr ) {
			return 1;
		}
		
		// Order nr is the same, sort on title name
		$a_titleName = strtolower($a->getTitle()->getText());
		$b_titleName = strtolower($b->getTitle()->getText());
		return $a_titleName < $b_titleName ? -1 : 1; 
	}
	
	// Sort SmartWiki Attributes on: parent class title, current order nr, title
	private function modelSmartWikiAttributeSortFunction($a,$b) {
		// Sort on class title first
		$a_classTitle = strtolower($a->getClass()->getTitle()->getText());
		$b_classTitle = strtolower($b->getClass()->getTitle()->getText());
	
		if ( $a_classTitle < $b_classTitle ) {
			return -1;
		} else if ( $a_classTitle > $b_classTitle ) {
			return 1;
		}
	
		// Sort on order nr second
		$a_orderNr = $a->getOrder();
		$b_orderNr = $b->getOrder();
	
		if ( $a_orderNr < $b_orderNr ) {
			return -1;
		} else if ( $a_orderNr > $b_orderNr ) {
			return 1;
		}
	
		// Order nr is the same, sort on title name
		$a_titleName = strtolower($a->getTitle()->getText());
		$b_titleName = strtolower($b->getTitle()->getText());
		return $a_titleName < $b_titleName ? -1 : 1;
	}
	
	// This function will fill the spcial class attribute "fromAssocations" and "toAssocations", 
	// basically linking associated classes with each other via the $associations list
	public function fillClassAssociations() {
		// This function only needs to be called once
		static $called = false;
		if ( $called ) return;
		$called = true;
		
		$smartwikiModel = SmartWikiModel::singleton();
		$classes = $smartwikiModel->getClasses();
		$associationClasses = $smartwikiModel->getAssociationClasses();
		$associations = $smartwikiModel->getAssociations();

		$assoLinks = array();
		
		foreach($associations as $keyAss => $valueAss ) {
			$fromCLS = $valueAss->getFromClass();
			if ( $fromCLS == NULL ) {
				throw new Exception("Invalid association. Missing from-point. (".$valueAss->getTitle()->getText().")");
			}
			$toCLS = $valueAss->getToClass();
			if ( $toCLS == NULL ) {
				throw new Exception("Invalid association. Missing to-point. (".$valueAss->getTitle()->getText().")");
			}
			$from = $valueAss->getFromClass()->getTitle()->getText();
			$to = $valueAss->getToClass()->getTitle()->getText();
			
			$assoLinks[$from]['from'][] = &$associations[$keyAss];
			$assoLinks[$to]['to'][] = &$associations[$keyAss];
		}

		
		foreach($classes as $keyClass => $valueClass) {
			$className = $valueClass->getTitle()->getText();
			if ( isset($assoLinks[$className]) && isset($assoLinks[$className]['from']) && is_array($assoLinks[$className]['from']) ) {
				usort($assoLinks[$className]['from'], array("SmartWikiModel", "sortSmartWikiClass"));
				$classes[$keyClass]->setFromAssociations($assoLinks[$className]['from']);
			} 
			if ( isset($assoLinks[$className]) && isset($assoLinks[$className]['to']) && is_array($assoLinks[$className]['to']) ) {
				usort($assoLinks[$className]['to'], array("SmartWikiModel", "sortSmartWikiClass"));
				$classes[$keyClass]->setToAssociations($assoLinks[$className]['to']);
			}
			 
			// Sort my associations
			
		}																	 
		
		//   +---------+   aso1     +---------+
		//   | Class A |----------->| Class B |
		//   +---------+     .      +---------+
		//                   .
		//                   .
		//         +-------------------+   aso2  +---------+
		//         | Association Class |-------->| Class C |
		//         +-------------------+         +---------+
		// Association classes can have associations as well
		foreach($associationClasses as $keyClass => $valueClass) {			 
			$className = $valueClass->getTitle()->getText();            
			if ( isset($assoLinks[$className]) && isset($assoLinks[$className]['from']) && is_array($assoLinks[$className]['from']) ) {
				$associationClasses[$keyClass]->setFromAssociations($assoLinks[$className]['from']);
			}
			if ( isset($assoLinks[$className]) && isset($assoLinks[$className]['to']) && is_array($assoLinks[$className]['to']) ) {
				$associationClasses[$keyClass]->setToAssociations($assoLinks[$className]['to']);
			}
		}
		
	}
	
	public static function sortSmartWikiClass($a, $b) {
		return $a->getOrder()->getValue() < $b->getOrder()->getValue() ? -1 : 1;
	}


	// This function will fill the special class atteribute "parentGeneralizations" and "childGeneralizations",
	// basically linking the generalized and specialized classes with each other via the $generalizations list 
	public function fillClassGeneralizations() {
		// This function only needs to be called once
		static $called = false;
		if ( $called ) return;
		$called = true;
		
		$smartwikiModel = SmartWikiModel::singleton();
		$classes = $smartwikiModel->getClasses();
		$gens = $smartwikiModel->getGeneralizations();
	
		$genFamily = array();
	
		foreach($gens as $keyGen => $valueGen) {
			$child = $valueGen->getChildClass()->getTitle()->getText();
			$parent = $valueGen->getParentClass()->getTitle()->getText();
				
			$genFamily[$child]['parents'][] = &$gens[$keyGen];
			$genFamily[$parent]['childs'][] = &$gens[$keyGen];
				
		}
	
		foreach($classes as $keyClass => $valueClass) {
			$className = $valueClass->getTitle()->getText();
			if ( isset($genFamily[$className]) ) {
				if ( isset($genFamily[$className]['parents']) && count($genFamily[$className]['parents']) > 0 ) {
					$classes[$keyClass]->setParentGeneralizations($genFamily[$className]['parents']);
				}
				if ( isset($genFamily[$className]['childs']) && count($genFamily[$className]['childs']) > 0 ) {
					$classes[$keyClass]->setChildGeneralizations($genFamily[$className]['childs']);
				}
			}
		}
	}
	
	static public function fillClassAttributes() {
		$smartwikiModel = SmartWikiModel::singleton();
		
		$attrs = $smartwikiModel->getAttributes();
		$attrResult = array();
		foreach($attrs as $k => $a ) {
			$c = (string)$a->getClass()->getName();
			if ( !isset($attrResult[$c]) ) {
				$attrResult[$c] = array();
			}
			$attrResult[$c][] = $attrs[$k];
		}
		
		$classes = $smartwikiModel->getClasses();
		
		foreach($classes as $keyClass => $valueClass) {
			$c = (string)$valueClass->getName();
			if ( isset($attrResult[$c]) ) {
				$classes[$keyClass]->setAttributes($attrResult[$c]);
			}
		}
		
		// todo: assoc classes
		
			
	}
	
	static public function getFilledSmartWikiModel() {
		static $alreadyProcessed = false;
		# The SmartWiki model
		$smartwikiModel = SmartWikiModel::singleton();
		
		if ( !$alreadyProcessed ) {
			$alreadyProcessed = true;
			
			# Get the current objects
			SmartWikiEnumeration::fill(SmartWikiTransformer::getFieldsByCategory('SmartWiki Enumerations'));
			SmartWikiPackage::fill(SmartWikiTransformer::getFieldsByCategory('SmartWiki Packages'));
			SmartWikiClass::fill(SmartWikiTransformer::getFieldsByCategory('SmartWiki Classes'));
			SmartWikiAssociationClass::fill(SmartWikiTransformer::getFieldsByCategory('SmartWiki Association classes'));
			SmartWikiAssociation::fill(SmartWikiTransformer::getFieldsByCategory('SmartWiki Associations'));
			SmartWikiGeneralization::fill(SmartWikiTransformer::getFieldsByCategory('SmartWiki Generalizations'));
			SmartWikiAttribute::fill(SmartWikiTransformer::getFieldsByCategory('SmartWiki Attributes'));
			
			$smartwikiModel->fillClassGeneralizations();
			$smartwikiModel->fillClassAssociations();
			$smartwikiModel->fillClassAttributes();
			
			$smartwikiModel->prepareForTransformation();
		}
		
		return $smartwikiModel;		
	}

	// Get recursive parent classes
	public static function getParentGeneralizations($class, &$classList) {
		$gens = $class->getParentGeneralizations();
		for($j = 0; $j < count($gens); $j++ ) {
			$classList[] = $gens[$j]->getParentClass();
			self::getParentGeneralizations($gens[$j]->getParentClass(), $classList);
		}
	}
	
	// Get recursive child classes
	public static function getChildGeneralizations($class, &$classList) {
		$gens = $class->getChildGeneralizations();
		for($j = 0; $j < count($gens); $j++ ) {
			$classList[] = $gens[$j]->getChildClass();
			self::getChildGeneralizations($gens[$j]->getChildClass(), $classList);
		}
	}
	
	/**
	 * fills $classes->attributes with associated attributes and parents (recursive) attributes
	 */
	/*
	public function fillClassAttributes() {
		$this->fillClassGeneralizations();
		$smartwikiModel = SmartWikiModel::singleton();
		$classes = $smartwikiModel->getClasses();
		foreach($classes as $keyClass => $valueClass) {
			$attrList = array();
			$this->getClassAttributes($valueClass, $attrList);
			$classes[$keyClass]->setAttributes($attrList);
		}
	}
	
	private function getClassAttributes($class, &$curAttrList) {
		$smartwikiModel = SmartWikiModel::singleton();
		// Add attributes of $class to $curAttrList
		$attrs = $smartwikiModel->getAttributes();
		for($i=0;$i<count($attrs);$i++) {
			if ( $attrs[$i]->getClass()->getTitle()->equals($class->getTitle()) ) {
				$curAttrList[] = &$attrs[$i];
			}
		}
	
		// Check for parents
		$genP = $class->getParentGeneralizations();
		if ( is_array($genP) && count($genP) > 0 ) {
			// This class has parents!
			foreach($genP as $gen) {
				$this->getClassAttributes($gen->getParentClass(), $curAttrList);
			}
		}
	}
	*/
	
	
	

}
?>