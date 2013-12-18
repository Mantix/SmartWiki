<?php 

class SmartWikiHooks {
	static function registerFunctions( &$parser ) {
		$parser->setFunctionHook( 'smartwiki', array( 'SmartWikiHooks', 'renderSmartWiki' ) );
		return true;
	}
	
	static function languageGetMagic( &$magicWords, $langCode = "en" ) {
		switch ( $langCode ) {
			default:
				$magicWords['smartwiki'] = array ( 0, 'smartwiki' );
		}
		return true;
	}	
	
	// Handle for {{#smartwiki}}
	static function renderSmartWiki( &$parser) {
		$args = func_get_args();
		$parser = array_shift($args);
		$action = array_shift($args);
		switch ($action) {
			case "check":
				return self::smartwikiCheck($parser);
				
			case "title":
				return self::smartwikiTitle($parser, $args[0]);
				
			case "glossary":
				return self::smartwikiGlossary($parser);
			
			case "className":
				return self::smartwikiGetClassName($parser);
				
			case "objectName":
				return self::smartwikiGetClassNameNoLink($parser);
				
			case "query":
				return self::smartwikiQuery(implode("|",$args));
				
			default:
				return "";
		}

	}
	
	// pre-load form ($page_contents will contain whatever 'Edit Source' shows) 
	static public function smwSmartWikiHook(&$page_contents, $target_title, $from_title) {
		$page_contents = preg_replace("/Class name=(.*)(\||\})/sU", "Class name=".substr($from_title,5)."\n}", $page_contents);
		return true;
	}

	static public function smwValidate($editpage) {
		//echo $editpage->textbox1;
		//die();
		return true;
	}
	
	static private function smartwikiTitle(&$parser, $num) {
		$user = $parser->getUser();
		$request = $user->getRequest();
		$title = explode("/",$request->getVal("title",3));
		return array($title[$num], "found" => true);		
	}
	
	static private function smartwikiGetClassName(&$parser) {
		$user = $parser->getUser();
		$request = $user->getRequest();
		$title = explode("/",$request->getVal("title",3));
		
		$targetObject = str_replace("_", " ", $title[0]);
		unset($title);
		
		$title = Title::newFromText($targetObject);
		if ( $targetObject == "" || !$title->isKnown() ) {
			$classForm = "";
			return array(self::changeObjectText($targetObject, $classForm), "isHTML" => true);
			//return ""; // Target page does not exist yet, so no need to check anything
		}
		$article = new Article($title);
		$article_text = $article->getRawText();
		
		preg_match("/Class name=(.*)(\||\})/sU", $article_text, $m);
		$curClassName = isset($m[1]) ? trim($m[1]) : "";
		$p = strrpos($curClassName," : ");
		if ( $p !== false ) { 
			$p += 3;
		} else {
			$p = 0;
		}
		$classNameShort = substr($curClassName, $p);
		
		return "[[".$curClassName."|".$classNameShort."]]";
				
	}
	
	static private function smartwikiGetClassNameNoLink(&$parser) {
		$classForm = "";
		$user = $parser->getUser();
		$request = $user->getRequest();
		$title = explode("/",$request->getVal("title",3));
	
		$targetObject = str_replace("_", " ", $title[0]);
		unset($title);
	
		$title = Title::newFromText($targetObject);
		if ( $targetObject == "" || !$title->isKnown() ) {
			return array(self::changeObjectText($targetObject, $classForm), "isHTML" => true);
			//return ""; // Target page does not exist yet, so no need to check anything
		}
		$article = new Article($title);
		$article_text = $article->getRawText();
	
		preg_match("/Class name=(.*)(\||\})/sU", $article_text, $m);
		$curClassName = isset($m[1]) ? trim($m[1]) : "";
		$p = strrpos($curClassName," : ");
		if ( $p !== false ) {
			$p += 3;
		} else {
			$p = 0;
		}
		$classNameShort = substr($curClassName, $p);
	
		return $classNameShort;
	
	}	
	/*
	 * Checks if a page is edited with the correct form and show error or warning box if forms do not match with existing page(object)
	 */
	static private function smartwikiCheck(&$parser) {
		global $wgOut;
		$user = $parser->getUser();
		$request = $user->getRequest();
		$title = explode("/",$request->getVal("title",3));
		//echo "<pre>".var_export($_SERVER,1)."</pre>";
		
		if ( $title[0] != "Special:FormEdit" ) {
			if ( isset($_REQUEST['action']) && $_REQUEST['action'] == "formedit" ) {
				// Simple editing of existing object
				return array(self::changeObjectText($_REQUEST['title']),  "isHTML" => true);
			} else {
				return "";	// This parser hook is only meant for SMW Forms, specifically the FormEdit functionality.
			}
		}
		
		$classForm = str_replace("_", " ", $title[1]);	// refering to a SMW Form
		$classFormPageID = $title[1];	// with underscore
		$targetObject = $title[2]; // Objectname, example: Onzebank (refering to a MW Page)
		unset($title);
		
		$title = Title::newFromText($targetObject);
		if ( $targetObject == "" || !$title->isKnown() ) {
			return array(self::changeObjectText($targetObject, $classForm), "isHTML" => true);
			//return ""; // Target page does not exist yet, so no need to check anything
		}
		$article = new Article($title);
		$article_text = $article->getRawText();
		
		preg_match("/Class name=(.*)(\||\})/sU", $article_text, $m);
		
		if ( count($m) == 0 ) {
			// This is not a smartwiki object page
			return "";
		}
		
		$curClassName = isset($m[1]) ? trim($m[1]) : "";
		$curClassObj = self::getSmartWikiClassByString($curClassName);
		$classFormObj = self::getSmartWikiClassByString($classForm);
		
		$result = "";
		if ( $curClassName != $classForm ) {
			if ( $classFormObj == null || $curClassObj == null || !in_array($classForm, self::getValidClassNames($curClassName))) {
				// Target object is not family of edit form class, abruptly end the page processing and displays an error
				$editForm = str_replace($classFormPageID, str_replace(" ", "_",$curClassName), $request->getRequestUrl());
				$result = "<div style='clear:both' class='errorbox'>Error! The page '".str_replace("_", " ",$targetObject)."' currently exists as a '".$curClassObj->getName()."' and cannot be edited as a '".$classFormObj->getName()."'!</div><br>";
				$result .= "<div style='clear:both'>";
				$result .= "<a href='".$editForm."'>Edit ".str_replace("_", " ",$targetObject)." with the correct form</a> or ";
				$result .= "<a href='javascript:history.go(-1);'>go back</a>.</div>";
				
				$wgOut->setPageTitle("Edit ".str_replace("_", " ",$targetObject)."");
				$wgOut->clearHTML();
				$wgOut->addHTML($result);
				$wgOut->output();
				die();
			} else {
				// Display a warning that form mismatches with existing object, but target object is family of edit form class.
				$result = "<div style='clear:both' class='warningbox'>Warning! The page '".str_replace("_", " ",$targetObject)."' currently exists as a '".$curClassObj->getName()."', but this edit form is a '".$classFormObj->getName()."'! Not all fields may be displayed.</div><br>";
				$result .= self::changeObjectText($targetObject, $classForm);
			}
		}  else {
			$result .= self::changeObjectText($targetObject, $classForm);
		}
			
		return array($result, "found" => true, "isHTML" => true);		
	}
	
	// returns string array with full class names "PackageName : Classname"
	static private function getValidClassNames($className) {
		$smartwikiModel = SmartWikiModel::getFilledSmartWikiModel();
		
		$cls = self::getSmartWikiClassByString($className);
		if ( $cls == null ) {
			return "";
			//throw new Exception("Not supposed to happen");
			return array(); //Not supposed to happen
		}
		
		$list = array();
		SmartWikiModel::getParentGeneralizations($cls, $list);
		SmartWikiModel::getChildGeneralizations($cls, $list);
		
		$x = array($className);
		foreach($list as $p) {
			if ( $p->getAbstract()->equals(new SmartWikiBoolean(false)) ) {
				$x[] = $p->getTitle()->getText();
			}
		}
		return $x;
	}
	
	static private function getSmartWikiClassByString($strClassName) {
		$smartwikiModel = SmartWikiModel::getFilledSmartWikiModel();
		
		$cls = null;
		$strClassName = str_replace("_", " ", $strClassName);
		foreach($smartwikiModel->getClasses() as $c) {
			$a = trim($c->getTitle()->getText());
			$b = trim($strClassName);
			
			if ( trim((string)$c->getTitle()->getText()) == trim((string)$strClassName) ) {
				$cls = $c;
				break;
			}
		}
		return $cls;
	
	}
	
	static private function changeObjectText($pageName, $selected = "") {
		$title = Title::newFromText($pageName);
		if ( $pageName == "" /*|| !$title->isKnown()*/ ) {
			return ""; // Target page does not exist yet, so no need to check anything
		}
		if ( $title->isKnown() ) {
			$article = new Article($title);
			$article_text = $article->getRawText();
			
			preg_match("/Class name=(.*)(\||\})/sU", $article_text, $m);
			$curClassName = isset($m[1]) ? trim($m[1]) : "";
			$s = "";
		} else {
			if ( !isset($_GET['s']) ) {
				$s = "?s=".$selected;
				$curClassName = $selected;
			} else {
				$s = "?s=".$_GET['s'];
				$curClassName = str_replace("_", " ",$_GET['s']);
			}
		}
		
		
		$opties = self::getValidClassNames($curClassName);
		if ( !is_array($opties) ) return "";
		if ( $selected == "" ) $selected = $curClassName;
		$list = "";
		foreach($opties as $o) {
			$list .= "<option".($o==$selected?" selected":"")." value='".str_replace(" ", "_", $o)."'>".$o.($o==$curClassName?" (current)":"")."</option>";
		}
		
		$text = "[curClassName=".$curClassName.", count(opties)=".count($opties)."]";
		$text = "";
		$smartwikiClass = self::getSmartWikiClassByString($selected);
		if ( $smartwikiClass->getAbstract()->equals(new SmartWikiBoolean(false)) ) {
			$text .= "<select onChange=\"location='".$_SERVER['SCRIPT_NAME']."/Special:FormEdit/'+this.value+'/".$pageName.str_replace(" ", "_", $s)."';\">".$list."</select>";
		}
		return $text;
	}
	
	static private function smartwikiGlossary(&$parser) {
		
		// Add everything from smartwiki model
			$smartwikiModel = SmartWikiModel::getFilledSmartWikiModel();
			$result = array();
			$x = array("getPackages", "getClasses", "getAttributes", "getAssociationClasses", "getAssociations");
			foreach($x as $y) {
				$z = $smartwikiModel->$y();
				foreach($z as $c) {
					$result[] = array(
						$c->getTitle()->getText(),
						$c->getName(),
						$c->getDescription(),
						in_array($y, array("getAttributes", "getAssociationClasses", "getAssociations"))
					);
				}
			}
			
			//$baseUrl = $_SERVER['SCRIPT_NAME']."/"; // 'SCRIPT_NAME' => '/smartwiki-development/index.php', (use when isHTML=true)
			
		// Add everything within the category "SmartWiki Glossary"
			$category = Category::newFromName('SmartWiki Glossary term');
			$titleIterator = $category->getMembers();
			while ($titleIterator->valid()) {
				$titleObject = $titleIterator->current();
				
				if ( !self::inArrayFirstColumn($titleObject->getText(), $result) ) {
				
					$article = new Article($titleObject);
					$article_text = $article->getRawText();
					
					preg_match("/Description=(.*)(\||\})/sU", $article_text, $m);
					if ( isset($m[1]) ) {
						$result[] = array(
							$titleObject->getText(),
							"",
							$m[1],
							false
						);
					}
				}
				
				$titleIterator->next();
			}			
			
		// Sort the results
			usort($result, array("SmartWikiHooks", "sortArrayByFirstColumn"));
			
		// Create a nice html table
			// <thead> and <tbody> tags are not recognized in the return value (when isHTML is false (default))
		
			$html = "<table class='sortable wikitable smwtable jquery-tablesorter'>";
			//$html .= "<thead>";
			$html .= "<tr><th class='headerSort'>&nbsp;</th><th class='headerSort'>&nbsp;</th><th class='headerSort'>Description</th></tr>";
			//$html .= "</thead>";
			
			//$html .= "<tbody>";
			foreach($result as $item) {
				//$a = "<a href='".$baseUrl.$item[0]."'>".$item[0]."</a>";  (use when isHTML=true)
				$f = ($item[3] ? "Property:" : "" ). $item[0];
				$a = "[[".$f."|".$item[0]."]]";							//  (use when isHTML=false)
				$b = "[[".$f."|".$item[1]."]]";
				$html .= "<tr><td nowrap valign='top'>$a</td><td>".$b."</td><td valign='top'>".$item[2]."</td></tr>";
			}
			//$html .= "</tbody>";
			$html .= "</table>";
			
		// Return it. Not adding "isHTML" because <div> and <table> are accepted html elements already and
		// we need the [[...]] link to be transformed which will not happen if isHTML is set
		return array($html);
	}
	
	static function sortArrayByFirstColumn($a, $b) {
		return ( $a[0] < $b[0] ) ? -1 : 1; 
	}
	
	static function inArrayFirstColumn($needle, $haystack) {
		foreach($haystack as $a) {
			if ( $needle == $a[0] ) {
				return true;
			}
		}
		return false;
	}
	
	static public function wfJavaScriptAddModules( &$out, $skin = false ) {
		$out->addModules( 'ext.smartwiki' );
		return true;
	}
	
	static public function smartwikiQuery($smartwikiQuery) {
		return self::doSmartWikiQuery($smartwikiQuery);
	}
	
	
	static private function doSmartWikiQuery($smartwikiquery) {
		/*
		$smartwikiquery = "select from (Request) 
where [Is sent by Organisational entity](Organisational entity) = {
	where [is active in Domain] = 'DOMEIN X'
} 
where [Is qualified in Qualification](Qualification) = {
	where [result] = 'NO-BID'
}
		";
		*/
		$testMode = false;
		if ( $testMode ) {
			$smartwikiquery = "
select table [Is active in Domain][Expertises][Expertises.level] from (Person) 
where [Expertises.level] > '3'
			";
		}
		$queryMatch = array();
		preg_match("#select\s+([\w\s]*)\s*(\[.*\]|)\s*from \((.*)\)#Ums", $smartwikiquery, $queryMatch);

		$flags = explode(" ",$queryMatch[1]);
		//echo "<pre>".var_export($queryMatch,1)."</pre>";die();
		
		if ( in_array("debug", $flags) ) {
			echo "Debug ON<BR>";
			$GLOBALS['query_debug'] = true;
		}
		
		if ( !isset($queryMatch[3]) ) return "Query syntax error. Example: select [attribute] from (SmartWikiClassName)";
		$selectFrom = $queryMatch[3];
		$listStyle = in_array("table", $flags) ? "table" : "list";
		$columns = self::extractColumns($queryMatch[2]);

		// Get smartwikimodel
		$smartwikimodel = SmartWikiModel::getFilledSmartWikiModel();
		
		$pages = self::getTitlesByCategory($selectFrom);
		$wheres = self::parseQuery($smartwikiquery);
		$result = array();
		
		if ( isset($GLOBALS['query_debug']) && $GLOBALS['query_debug'] ) {
			echo "<pre>".var_export($wheres,1)."</pre>";
		}
		//return "<pre>".var_export($wheres,1)."</pre>"; 		
		
		// First get all titles from "select from"
		$pages = self::runQuery($pages, $wheres, $selectFrom, $columns);
		
		$html = "";
		
		preg_match("#limit (\d+)$#", $smartwikiquery, $limitMatch);
		$limitResults = -1;
		if ( isset($limitMatch[1]) ) {
			$limitResults = $limitMatch[1];
		}
		
		if ( $listStyle == 'table' ) {
			// Header
			$html = "{| class=\"smartwikiTopDownTable\"\n";
			$html .= "! ".$selectFrom."\n";
			foreach($columns as $col) {
				$html .= "! ".$col."\n";
			}
			$html .= "|-\n";
			foreach($pages as $pageName => $page) {
				$html .= "| [[".$pageName."]]\n";
				foreach($columns as $col) {
					$r = self::getColData($page, $col);
					$html .= "| ".$r."\n";
				}
				$html .= "|-\n";
				$limitResults--;
				if ( $limitResults == 0 ) {
					$html .= "| ... \n".(count($columns)>1?str_repeat("| \n", count($columns)-1):"")."|-\n";
					break;
				}
			}
			$html .= "|}\n";
		} else {
			$html = "";
			foreach($pages as $pageName => $page) {
				$html .= "[[".$pageName."]] ";
				$limitResults--;
				if ( $limitResults == 0 ) break;
			}
		}
		if ( $testMode ) {
			echo "<pre>".var_export($html,1)."</pre>" ;die();
		}
		return $html;
		
	}
	
	private static function getColData($page, $colName) {
		if ( !isset($page['cols'][$colName]) ) {
			// Check subpages
			if (isset($page['sp']) ) {
				$list = array();
				foreach($page['sp'] as $subPageList) {
					foreach($subPageList as $subPageName => $subPage) {
						$item = self::getColData($subPage, $colName);
						if ( $item != "" ) {
							$list[] = $item;
						}
					}
				}
				return implode("<br>",$list);
			} else {
				return "";
			}
		} else {
			//				$pages[$pageName]['cols'][$textOnThisPage][] = array with strings;
			$c = "";
			foreach($page['cols'][$colName] as $a) {
				if ( is_array($a) ) {
					foreach ( $a as $b) {
						$c .= "[[".$b."]]<br>";
					}
				} else {
					$c .= "[[".$a."]], ";
				}
			}
			
		}
		if ( substr($c, -2) == ", " ) {
			$c = substr($c,0,-2);
		}
		return $c;
	}
	
	public static function getParentGeneralizations($class, &$classList) {
		$gens = $class->getParentGeneralizations();
		for($j = 0; $j < count($gens); $j++ ) {
			$classList[] = $gens[$j]->getParentClass();
			self::getParentGeneralizations($gens[$j]->getParentClass(), $classList);
		}
	}
	
	public static function getChildGeneralizations($class, &$classList) {
		$gens = $class->getChildGeneralizations();
		for($j = 0; $j < count($gens); $j++ ) {
			$classList[] = $gens[$j]->getChildClass();
			self::getChildGeneralizations($gens[$j]->getChildClass(), $classList);
		}
	}
	
	public static function getAssociationClasses($class) {
		$smartwikimodel = SmartWikiModel::getFilledSmartWikiModel();
		$clsList = $smartwikimodel->getAssociationClasses();
		$l = array();
		foreach($clsList as $cls) {
			if ( $cls->getFromClass()->equals($class) ) {
				$l[] = $cls;
			}
		}
		return $l;
	}
	
	public static function getReverseAssociationClasses($class) {
		$smartwikimodel = SmartWikiModel::getFilledSmartWikiModel();
		$clsList = $smartwikimodel->getAssociationClasses();
		$l = array();
		foreach($clsList as $cls) {
			if ( $cls->getToClass()->equals($class) ) {
				$l[] = $cls;
			}
		}
		return $l;
	}
	
	private static function extractColumns($cols) {
		$result = array();
		$state = 0;
		$buffer = "";
		for($i = 0; $i < strlen($cols); $i++) {
			$l = substr($cols,$i,1);
			if ( $state == 0 && $l == "[" ) {
				$state = 1;
			} elseif ( $state == 1 && $l == "]" || $i+1 == strlen($cols) ) {
				$state = 0;
				if ( $buffer != "" ) {
					$result[] = strtolower($buffer);
				}
				$buffer = "";
				
			} elseif ( $state == 1 ) {
				$buffer .= $l;
			} 
		}
		return $result;
	}
	
	public static function runQuery($pages, $wheres, $selectFrom, $columns) {
		$debug = false;
		
		if ( isset($GLOBALS['query_debug']) && $GLOBALS['query_debug'] ) {
			$debug = true;
		}
		$cls2 = self::getSmartWikiClass($selectFrom);
		
		if ( $cls2 == null ) {
			return "'".$selectFrom."' is not a valid smartwiki class.";
		}
		
		$classList = array($cls2);
		self::getParentGeneralizations($cls2, $classList);
		self::getChildGeneralizations($cls2, $classList);
		
		// Reverse Association:
		$assList = array();
		foreach($classList as $class) {
			$assListTemp = $class->getToAssociations();
			if ( $assListTemp != null && is_array($assListTemp) ) {
				$assList = array_merge($assList,$assListTemp);
			}
		}		
		
		if ( $assList != null ) {
			foreach($assList as $value) {
				
					
				$toClass = $value->getToClass();
				$fromClass = $value->getFromClass();
				$reverseName = (string)$value->getReverseName();
				if ( empty($reverseName) ) {
					$reverseName = "(reverse of: ".$value->getName().")";
				}
				$textOnThisPage  = trim(strtolower($reverseName . " " . $fromClass->getName()));
				
				
				$fromCategory = $fromClass->getName();
				$subPages = self::getTitlesByCategory($fromCategory);
				
				foreach($subPages as $S => $sp) {
					$article = new Article($sp['title']);
					$article_text = $article->getRawText();
					$assName = $value->getTitle()->getText();
					
					preg_match("#".$assName . "=([^\|\}]*)[\|\}]#Ums", $article_text, $m);
					if ( isset($m[1]) ) {
						$matches = explode(",", str_replace(", ", ",", trim($m[1]," \n\r,")));
						foreach($matches as $objectName) {
							$objectName = trim($objectName);
							if ( empty($objectName) ) continue;
							if (isset($pages[$objectName]) ) {
								preg_match("/Class name=(.*)(\||\})/sU", $article_text, $m);
								$thisClassName = isset($m[1]) ? trim($m[1]) : "";
								$thisClassName = explode(" : ",$thisClassName);
								$thisClassName = array_pop($thisClassName);
								$pages[$objectName][$textOnThisPage][$thisClassName][$sp['title']->getText()] = $sp;
							}
						}
					}
					// flat data
					foreach($pages as $pageName => $page) {
						// (part of $sp['text'])
						// $assName=$pageName (part of!) , if yes: add $S
						$p = "#".$assName."=([^\|\}]*)[\|\}]#Ums";
						preg_match($p, $sp['text'], $m);
						if ( isset($m[1]) ) {
							$matches = explode(",", str_replace(", ", ",", trim($m[1]," \n\r,")));
							if ( in_array($pageName, $matches) ) {
								$pages[$pageName]['cols'][$textOnThisPage][] = trim($S);
							}
						}
					}
					
				}
				
			}
		}
		
		// Attributes
		$attrList = array();
		foreach($classList as $class) {
			$attrListTemp = $class->getAttributes();
			if ( $attrListTemp != null && is_array($attrListTemp) ) {
				$attrList = array_merge($attrList,$attrListTemp);
			}
		}

		if ( $attrList != null ) {
			foreach($attrList as $value) {
				foreach($pages as $pageName => $page ) {
					$matchText = $value->getTitle()->getText();
					$textOnThisPage = (string)$value->getName();
					preg_match("#".$matchText . "[\s]*=([^\|\}]*)[\|\}]#Ums", $page['text'], $m);
					if ( isset($m[1]) ) {
						$attributeValue = $m[1];
						if (isset($pages[$pageName]) ) {
							preg_match("/Class name=(.*)(\||\})/sU", $page['text'], $m);
							$thisClassName = isset($m[1]) ? trim($m[1]) : "";
							$thisClassName = explode(" : ",$thisClassName);
							$thisClassName = array_pop($thisClassName);
							$pages[$pageName][$textOnThisPage][$thisClassName] = trim($attributeValue);
							$pages[$pageName]['cols'][$textOnThisPage][] = trim($attributeValue);
						}
					}
				}
			}
		}
		
		// Normal assocs
		$assList = array();
		foreach($classList as $class) {
			$assListTemp = $class->getFromAssociations();
			if ( $assListTemp != null && is_array($assListTemp) ) {
				$assList = array_merge($assList,$assListTemp);
			}
		}
		
		foreach($assList as $value) {
				$toClass = $value->getToClass();
				$fromClass = $value->getFromClass();
				$textOnThisPage  = strtolower($value->getName() . " " . $toClass->getName());
				$matchText = $value->getTitle()->getText();
				foreach($pages as $pageName => $page) {
					if ( !isset($page['text']) ) {
						//echo "page[text] not found in $pageName.<br><pre>".var_export($page,1)."</pre>";die();
						$title = Title::newFromText($pageName);						
						$article = new Article($title);
						$page['title'] = $title;
						$page['text'] = $article->getRawText();
					}
					preg_match("#".$matchText . "[\s]*=([^\|\}]*)[\|\}]#Ums", $page['text'], $m);
					if ( isset($m[1]) ) {
						$matches = explode(",", str_replace(", ", ",", trim($m[1]," \n\r,")));
						foreach($matches as $objectName) {
							$objectName = trim($objectName);
							if ( empty($objectName) ) continue;
							if (isset($pages[$pageName]) ) {
								/*
								preg_match("/Class name=(.*)(\||\})/sU", $page['text'], $m);
								$thisClassName = isset($m[1]) ? trim($m[1]) : "";
								$thisClassName = explode(" : ",$thisClassName);
								$thisClassName = array_pop($thisClassName);
								*/
								//echo "Setting [$pageName][$textOnThisPage][$thisClassName][$objectName] Had moeten zijn: ".$toClass->getName()."<br>";
								$targetClassName = (string)$toClass->getName();
								$pages[$pageName][$textOnThisPage][$targetClassName][$objectName] = array(
										 
								);
							} 
						}
						if ( in_array($textOnThisPage, $columns) ) {
							$pages[$pageName]['cols'][$textOnThisPage] = $matches;
						}
					} 

				}
				
		}
		
		// Assoc classes
		$assList = array();
		foreach($classList as $class) {
			$assListTemp = self::getAssociationClasses($class);
			if ( $assListTemp != null && is_array($assListTemp) ) {
				$assList = array_merge($assList,$assListTemp);
			}
		}		
		
		foreach($assList as $value) {
			$toClass = $value->getToClass();
			$fromClass = $value->getFromClass();
			$textOnThisPage  = strtolower($value->getPluralName());
			$matchText = $value->getTitle()->getText();
			foreach($pages as $pageName => $page) {
				// tricky thing is: there can be multiple matches!
				preg_match_all("#".$matchText . "[\s\n]*\|toClass=([^\|\}]*)[\s\n]*(?:\}|\|(.*)\})#Ums", $page['text'], $mWrap, PREG_SET_ORDER);
				foreach ( $mWrap as $m ) {
					if ( isset($m[1]) ) {
						// explode is actually not neccesairy since assoclasses are only allowed 1
						$matches = explode(",", str_replace(", ", ",", trim($m[1]," \n\r,")));
						foreach($matches as $objectName) {
							$objectName = trim($objectName);
							if ( empty($objectName) ) continue;
							if (isset($pages[$pageName]) ) {
								preg_match("/Class name=(.*)(\||\})/sU", $page['text'], $sm);
								$thisClassName = isset($sm[1]) ? trim($sm[1]) : "";
								$thisClassName = explode(" : ",$thisClassName);
								$thisClassName = array_pop($thisClassName);
								$pages[$pageName][$textOnThisPage][$thisClassName][$objectName] = array(
											
								);
							}
						}
						if ( in_array($textOnThisPage, $columns) ) {
							$pages[$pageName]['cols'][strtolower($textOnThisPage.".".$toClass->getName())][] = $matches; // // [Expertises.aspect]
							$pages[$pageName]['cols'][strtolower($textOnThisPage." ".$toClass->getName())][] = $matches; // [Expertises aspect]
							$pages[$pageName]['cols'][strtolower($textOnThisPage)][] = $matches;	// [Expertises]
						} 
						
						if ( isset($m[2]) ) {
							// assoc class has attributes!
							$mTemp = explode("|", str_replace(array("\n","\r"),"",$m[2]));
							
							foreach($mTemp as $mTemp2) {
								$mTemp3 = substr($mTemp2, strrpos($mTemp2, " : ")+3);
								$mTemp4 = explode("=",$mTemp3,2);
								if ( in_array($textOnThisPage.".".$mTemp4[0], $columns) ) {
									$pages[$pageName][$textOnThisPage.".".$mTemp4[0]][] = trim($mTemp4[1]);
									$pages[$pageName]['cols'][$textOnThisPage.".".$mTemp4[0]][] = array($mTemp4[1]);
									$pages[$pageName]['cols'][$textOnThisPage." ".$mTemp4[0]][] = array($mTemp4[1]);
								} 
							}							
						}
					}
				}
			}
		
		}		
		
		// Reverse Assoc Classes
		$assList = array();
		foreach($classList as $class) {
			$assListTemp = self::getReverseAssociationClasses($class);
			if ( $assListTemp != null && is_array($assListTemp) ) {
				$assList = array_merge($assList,$assListTemp);
			}
		}
		if ( $assList != null && is_array($assList) ) {
			foreach($assList as $value) {
				$toClass = $value->getToClass();
				$fromClass = $value->getFromClass();
				$textOnThisPage  = strtolower($value->getReverseName()." ".$fromClass->getPluralName());
				$fromCategory = $fromClass->getName();
				$subPages = self::getTitlesByCategory($fromCategory);
				
				$assName = $value->getTitle()->getText();
				
				foreach($subPages as $S => $sp) {
					$article = new Article($sp['title']);
					$article_text = $article->getRawText();
					
					// Check if this pages is linked to this $class
					preg_match_all("#".$assName . "[\s\n]*\|toClass=([^\|\}]*)[\s\n]*(?:\}|\|(.*)\})#Ums", $sp['text'], $mWrap, PREG_SET_ORDER);

					foreach ( $mWrap as $m ) {
						if ( isset($m[1]) ) {
							// explode is actually not neccesairy since assoclasses are only allowed 1
							$matches = explode(",", str_replace(", ", ",", trim($m[1]," \n\r,")));
							foreach($matches as $objectName) {
								$objectName = trim($objectName);
								if ( empty($objectName) ) continue;
								if (isset($pages[$objectName]) ) {
									preg_match("/Class name=(.*)(\||\})/sU", $page['text'], $sm);
									$thisClassName = isset($sm[1]) ? trim($sm[1]) : "";
									$thisClassName = explode(" : ",$thisClassName);
									$thisClassName = array_pop($thisClassName);
									$pages[$objectName][$textOnThisPage][$thisClassName][$sp['title']->getText()] = $sp;
							
									if ( in_array($textOnThisPage, $columns) ) {
										$pages[$objectName]['cols'][$textOnThisPage][] = $S;
									}
							
									if ( isset($m[2]) ) {
										// assoc class has attributes!
										$mTemp = explode("|", str_replace(array("\n","\r"),"",$m[2]));
										foreach($mTemp as $mTemp2) {
											$mTemp3 = substr($mTemp2, strrpos($mTemp2, " : ")+3);
											$mTemp4 = explode("=",$mTemp3,2);
											if ( in_array($textOnThisPage.".".$mTemp4[0], $columns) ) {
												$pages[$objectName][$textOnThisPage.".".$mTemp4[0]][] = trim($mTemp4[1]);
												$pages[$objectName]['cols'][$textOnThisPage.".".$mTemp4[0]][] = array($mTemp4[1]);
											}
										}
									}
								}
							}
						}
					}
						
				}
				
				
			}
		}
		/* $pages
		 * Requests				
		 * ["Ontsluiting overheid"]
		 *		 ['title'] (title object)
		 *		 ['text'] (article text)
		 *		 ['is followed by bid project']	(reverse assoc: array)
		 * 		 ['is sent by organisational entity'] (reverse assoc array)
		 * 			['Organisational entity']
		 *          	['E-Overheid'] =>
		 * 			   		['title'] (from assoc title object)
		 *             		['text'] (article text of from assoc)
		 *             		['is active in domain'] =>
		 *             			['TestDomain'] = array();
		 *             			...
		 *          	...
		 *       ['attribute']
		 *          ['value'] (value of attribute)
		 * 							
		 * ["RfP NOT"]
		 * 
		 * 
		 * $wheres
		 * array (
			  0 => 
			  array (
			    0 => 'is sent by organisational entity',
			    1 => 'Organisational entity',
			    2 => 'where [is active in Domain] = \'DOMEIN X\'',
			    3 => 2,
			    4 => 
			    array (
			      0 => 
			      array (
			        0 => 'is active in domain',
			        1 => '',
			        2 => 'DOMEIN X',
			        3 => 1,
			        4 => NULL,
			      ),
			    ),
			  ),
			  1 => 
			  array (
			    0 => 'is qualified in qualification',
			    1 => 'Qualification',
			    2 => 'where [result] = \'REJECTED\'',
			    3 => 2,
			    4 => 
			    array (
			      0 => 
			      array (
			        0 => 'result',
			        1 => '',
			        2 => 'REJECTED',
			        3 => 1,
			        4 => NULL,
			      ),
			    ),
			  ),
			)
		 */
		foreach($wheres as $where) {
			foreach($pages as $pageName => $page) {
				if ( $debug ) echo "***$pageName*** has : ".implode(", ",array_keys($page))."<BR>";
				if ( isset($page[$where[0]]) ) {
					if ( $where[3] == 1 ) {	// Simple attribute: where X = 'Y'
						
						if ( $debug ) echo $where[0] ." was found in '".$pageName."' (1)!<br>";
						if ( $debug ) echo "Looking for $where[2] in ".implode(",",array_keys($page[$where[0]]))."<BR>";
						$found = false;
						if ( $where[1] == "" ) {
							if ( $where[5] == 3 ) {
								$found = true;
							}
							// look in each class
							foreach($page[$where[0]] as $objName => $inhoud) {
								$where2arr = explode("|", $where[2]);
								foreach($where2arr as $where2Item) {
									if ( $where[5] == 0 ) {	// ==
										if ( !is_array($inhoud) && $inhoud == $where2Item || is_array($inhoud) && in_array($where2Item, array_keys($inhoud)) ) {
											$found = true;
											break;
										} else {
											if ( $debug ) 
												echo "<B>Attemping to find '".$where2Item."' in ".var_export($inhoud,1)."</b><BR>";
										}
									} else if ( $where[5] == 1 ) { // >
										if ( !is_array($inhoud) && $inhoud > $where2Item ) {
											$found = true;
											break;
										} else {
											if ( $debug ) echo "<B>Attemping to find ".$where2Item." in ".var_export($inhoud,1)."</b><BR>";
										}
									} else if ( $where[5] == 2 ) { // <
										if ( !is_array($inhoud) && $inhoud < $where2Item ) {
											$found = true;
											break;
										} else {
											if ( $debug ) echo "<B>Attemping to find ".$where2Item." in ".var_export($inhoud,1)."</b><BR>";
										}
									} else if ( $where[5] == 3 ) { // !=
										if ( !is_array($inhoud) && $inhoud == $where2Item || is_array($inhoud) && in_array($where2Item, array_keys($inhoud)) ) {
											$found = false;
											break;
										} else {
											if ( $debug ) echo "<B>Attemping to find ".$where2Item." in ".var_export($inhoud,1)."</b><BR>";
										}
									} 
								}
								if ( $found ) break;
							}
						} else {
							if ( !is_array($inhoud) && $inhoud == $where[2] || is_array($inhoud) && in_array($where[2], array_keys($inhoud)) ) {
								$found = true;
							}
						}
						if ( !$found ) {
							if ( $debug ) echo "Unsetting $pageName... (1)<BR>";
							unset($pages[$pageName]);
							//continue;
						}
					} elseif ( $where[3] == 2 ) { // Subquery: where X = {where Y = 'Z'}
						if ( $debug ) echo $where[0] ." was found in '".$pageName."' (2)!<br>";
						if ( $debug ) echo "<span style='color:red;'>";
						
						if ( $where[1] == "" ) {
							$subPages = array();
							foreach(array_keys($page[$where[0]]) as $whereTemp) {
								$subPagesR = self::runQuery($page[$where[0]][$whereTemp], $where[4], $whereTemp, $columns);
								if ( is_array($subPagesR) ) {
									$subPages = array_merge($subPages, $subPagesR);
								}
							}
						} else {
							if ( !isset($page[$where[0]][$where[1]]) ) {
								echo "Invalid class name '".$where[1]."'<BR>";
								echo "<pre>".var_export($where,1)."</pre>";
								echo "<pre>".var_export($page,1)."</pre>";
								die();
							}
							$subPages = self::runQuery($page[$where[0]][$where[1]], $where[4], $where[1], $columns);
						}
						if ( $debug ) echo "</span>";
						if (count($subPages) == 0 ) {
							if ( $debug ) echo "Unsetting $pageName... (2)<BR>";
							unset($pages[$pageName]);
						} else {
							$pages[$pageName]['sp'][] = $subPages;
						}
						if ( $debug ) echo "<span style='color:green;'>SUBPAGE:";
						if ( $debug ) echo implode(", ", array_keys($subPages))."<BR>";
						if ( $debug ) echo "</span>";
					}
				} elseif ( strtolower($where[0]) == strtolower($page['object_name']) ) {
					//&& strtolower($where[2]) == strtolower($pageName)
					$temp = explode("|", strtolower($where[2]));
					$temp_found = false;
					if ( in_array(strtolower($pageName), $temp) ) {
						$temp_found = true;
					}
					if ( $where[5] == 3 ) {
						$temp_found = !$temp_found;
					}
					if ( !$temp_found ) {
						// Unset
						unset($pages[$pageName]);
						if ( $debug ) echo "Title check: No match, unsetting $pageName !<BR>";
					} else {
						if ( $debug ) echo "Title check: Match, not unsetting $pageName !<BR>";
					}
				} else {
					if ( $debug ) echo "'".$where[0] ."' does not exist in '$pageName'. (I have: ".implode(", ",array_keys($page)).")<BR>";
					if ( $where[3] != 2 || $where[3] == 2 && count($where[4]) > 0 ) {
						if ( $debug ) echo "Unsetting $pageName... (3)<BR>";
						// when w3 == 1 and w4 == '' and w5 == 0 (=)   -> SHOW
						//					w4 != '' and w5 == 0 (=)   -> NO SHOW
						//					w4 == '' and w5 == 3 (!=)  -> NO SHOW
						//					w4 != '' and w5 == 3 (!=)  -> SHOW
						if ( $where[2] == '' && $where[5] == 0 ) {
							// show
						} elseif ( $where[2] != '' && $where[5] == 0 ) {
							// no show
							unset($pages[$pageName]);
						} elseif ( $where[2] == '' && $where[5] == 3 ) {
							// no show
							unset($pages[$pageName]);
						} elseif ( $where[2] != '' && $where[5] == 3 ) {
							// show
						} else {
							unset($pages[$pageName]);
						}
					} else {
						if ( $debug ) 
							echo "NOT UNSETTING $pageName... because where-type is 2 ($where[0]) but has no subwheres!<BR>";
					}
				}
			}
		}
		/*
		foreach($pages as $pageName => $page) {
			echo $pageName ."<br>";
			echo "<pre>".var_export(array_keys($page),1)."</pre>";
		}
		*/
		return $pages;
		
	}
	
	static public function getSmartWikiClass($naam) {
		$smartwikimodel = SmartWikiModel::getFilledSmartWikiModel();
		$cls = $smartwikimodel->getClasses();
		foreach($cls as $c) {
			if ( $c->getName() == $naam ) {
				return $c;
			}
		}
		return null;		
	}
	
	
	static public function getTitlesByCategory($catName) {
		$result = array();
		
		$category = Category::newFromName($catName);
		$titleIterator = $category->getMembers();
		while ($titleIterator->valid()) {
			$titleObject = $titleIterator->current();
			$titleString = (string)$titleObject;
			$article = new Article($titleObject);
			$article_text = $article->getRawText();
			
			$result[$titleString]['title'] = $titleObject;
			$result[$titleString]['text'] = $article_text;
			$result[$titleString]['object_name'] = self::getSmartWikiObjectName($article_text);

			$titleIterator->next();
		}
		return $result;		
	} 
	
	
	/*
	 * Query example:
	 * 
	 * $smartwikiquery = "
		{{#smartwiki:query|
		select from (Request) 
		where [Is sent by Organisational entity](Organisational entity) = {
			where [is active in Domain] = 'DOMEIN X'
		} 
		where [Is qualified in Qualification](Qualification) = {
			where [result] = 'REJECTED'
		}
		where [Managed by Parties] = 'onzebank'
		where [Another test](ObjectName) = {
			where [depth1] = {
				where [depth2](Iets) = 'Test'
			}
		}
	  }}";
	 * 
	 * 
	 */
	static function parseQuery($query) {
	
		$pos = strpos($query, "where");
		$b = 0;
		$state = 0;
		$wheres = array();
		$curWhere = 0;
		$buffer = "";
		
		if ( $pos === false ) return $wheres;
		
		
		$whereTarget = "";
		$whereObject = "";
		$whereContents = "";
		$whereType = 0;
		$whereSubType = 0;
		for($i = $pos; $i < strlen($query); $i++) {
			$l = substr($query, $i,1);
			//echo "\'$l\' : ".$state." -> ";
			if ( ($state == 0 || $state == 3) && substr($query,$i,5) == "where" || $i+1 == strlen($query)) {
				$state = 1;
				$i += 5;
				if ( $whereTarget != "" ) {
					$subWheres = null;
					if ( $whereType == 2 ) {
						$subWheres = self::parseQuery($whereContents);
					}
					$wheres[] = array(strtolower($whereTarget), $whereObject, trim($whereContents), $whereType, $subWheres, $whereSubType);
				}
				$whereTarget = "";
				$whereObject = "";
				$whereContents = "";
				$whereType = 0;
				$whereSubType = 0;
					
			} else 	if ( $state == 1 && $l == "[" ) {
				$state = 2;
			} else if ( $state == 2 && $l != "]" ) {
				$whereTarget .= $l;
			} else if ( $state == 2 && $l == "]" ) {
				$state = 3;
			} else if ( $state == 3 && $l == "(" ) {
				$state = 4;
			} else if ( $state == 4 && $l != ")" ) {
				$whereObject .= $l;
			} else if ( $state == 4 && $l == ")" ) {
				$state = 3;
			} else if ( $state == 3 && $l == "=" ) {
				$state = 5;
				$whereSubType = 0;
			} else if ( $state == 3 && $l == ">" ) {
				$state = 5;
				$whereSubType = 1;
			} else if ( $state == 3 && $l == "<" ) {
				$state = 5;
				$whereSubType = 2;
			} else if ( $state == 3 && $l == "!" && substr($query,$i,2) == "!=" ) {
				$state = 5;
				$whereSubType = 3;
				$i += 2;
			} else if ( $state == 5 && $l == "'" ) {
				$whereType = 1;
				$state = 6;
			} else if ( $state == 6 && $l != "'" ) {
				$whereContents .= $l;
			} else if ( $state == 6 && $l == "'" ) {
				$state = 0;
			} else if ( $state == 5 && $l == "{" ) {
				$whereType = 2;
				$state = 7;
				$b = 0;
			} else if ( $state == 7 && $l == "{" ) {
				$b++;
				$whereContents .= $l;
			} else if ( $state == 7 && $l != "}") {
				$whereContents .= $l;
			} else if ( $state == 7 && $l == "}") {
				if ( $b == 0 ) {
					$state = 0;
				} else {
					$b--;
					$whereContents .= $l;
				}
			}
			//echo $state."<BR>";
		}
		//echo "<pre>".var_export($wheres,1)."</pre>";die();
		return $wheres;
	}
	
	private static function getSmartWikiObjectName($rawText, $shortName = true) {
		preg_match("/Class name=(.*)(\||\})/sU", $rawText, $m);
		if ( count($m) == 0 ) {
			return false;
		}
		
		if ( $shortName ) {
			$curClassName = isset($m[1]) ? trim($m[1]) : "";
			$p = strrpos($curClassName," : ");
			if ( $p !== false ) {
				$p += 3;
			} else {
				$p = 0;
			}
			$classNameShort = substr($curClassName, $p);
			return $classNameShort;
		}
		return $m[1];
		
	}
	
	
}
?>