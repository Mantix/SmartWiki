<?php
class SWTestController {
	/**
	 * Constructor
	 */
	function __construct() {
		# TODO: Do something or remove
	}

	/**
	 * Display a default page with the action buttons
	 *
	 * @var $upload_file - XMI file that was uploaded by the user
	 *
	 * @return $htmlOut - HTML codes with a status message
	 */
	public function execute() {
		global $wgOut, $wgUser;
		$htmlOut = "";
		
		$smartwikiModel = SWModel::singleton();
		# Get the current objects
		SWPackage::fill(SWTransformer::getFieldsByCategory('SmartWiki Packages'));
		SWClass::fill(SWTransformer::getFieldsByCategory('SmartWiki Classes'));
		
		$u = SWTransformer::getFieldsByCategory('SmartWiki Association classes');
		SWAssociationClass::fill($u);
		
		SWAssociation::fill(SWTransformer::getFieldsByCategory('SmartWiki Associations'));
		SWGeneralization::fill(SWTransformer::getFieldsByCategory('SmartWiki Generalizations'));
		SWAttribute::fill(SWTransformer::getFieldsByCategory('SmartWiki Attributes'));
		SWEnumeration::fill(SWTransformer::getFieldsByCategory('SmartWiki Enumerations'));
		
		$smartwikiModel->fillClassGeneralizations();
		$smartwikiModel->fillClassAssociations();
				
		$cls = $smartwikiModel->getClasses();
		foreach($cls as $c) {
			$htmlOut .= $c->getName()."<BR>";
			if ( is_array($c->getToAssociations()) ) {
				foreach ($c->getToAssociations() AS $outerKey => $value) {
					$htmlOut .= " --- ".$value->getTitle()->getText()."<BR>";
				}
			}
		}
		
		$wgOut->addHTML( $htmlOut );
		
		return;
		
		$ass = $smartwikiModel->getAssociations();
		$htmlOut .= "asscount = ".count($ass)."<BR>";
		$asscls = $smartwikiModel->getAssociationClasses();
		$htmlOut .= "assclscount = ".count($asscls)."<BR>";
		foreach($asscls as $k => $v) {
			echo $v->getTitle()->getText()."<BR>";
		}
		$wgOut->addHTML( $htmlOut );
		return;
		
		$classes = $smartwikiModel->getClasses();
		$htmlOut .= "class count = ".count($classes);
		foreach($classes as $class) {
			$gen = $class->getChildGeneralizations();
			$genP = $class->getParentGeneralizations();
			$htmlOut .= "gen count for ".$class->getTitle()->getText()." = ".count($gen)." kids and ".count($genP)." parents.<BR>";
			if (count($gen) > 0 ) {
				$htmlOut .= "kids = ";
				foreach($gen as $g) {
					$htmlOut .= $g->getChildClass()->getTitle()->getText().", ";
				}
				$htmlOut .= "<br>";
			}
			if ( count($genP) > 0 ) {
				$htmlOut .= "parents = ";
				foreach($genP as $g) {
					$htmlOut .= $g->getParentClass()->getTitle()->getText().", ";
				}
				$htmlOut .= "<br>";
				
			}
			
			$attr = $class->getAttributes();
			$htmlOut .= "attr count = ".count($attr)."<BR>";
			foreach($attr as $a){
				$htmlOut .= $a->getTitle()->getText().", ";
			}
			$htmlOut .= "<BR>";
			
		}
		
		$gens = $smartwikiModel->getGeneralizations();
		$htmlOut .= "gen count = ".count($gens);
		foreach($gens as $gen) {
			$htmlOut .= "gen name = ".$gen->getTitle()->getText()." [child:".$gen->getChildClass()->getTitle()->getText()." -> parent:".$gen->getParentClass()->getTitle()->getText()."<BR>";
		}
		
		// attr
		
		
		
		$wgOut->addHTML( $htmlOut );
	}
	
	public function fillClassGeneralizations() {
		$smartwikiModel = SWModel::singleton();
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
	
	// Make sure you called "fillClassGeneralizations" or else you will only get direct class attributes
	// and not the parents attributes.
	public function fillClassAttributes() {
		$smartwikiModel = SWModel::singleton();
		$classes = $smartwikiModel->getClasses();
		foreach($classes as $keyClass => $valueClass) {
			$attrList = array();
			$this->getClassAttributes($valueClass, $attrList);
			$classes[$keyClass]->setAttributes($attrList);
		}
	}
	
	private function getClassAttributes($class, &$curAttrList) {
		$smartwikiModel = SWModel::singleton();
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
	
	
}
