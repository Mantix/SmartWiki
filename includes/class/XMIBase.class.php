<?php
	class XMIBase {
		
		protected $xmlParser;
		
		private function XMIBase() {
			//
		}
		
		public static function getXMIParser(SmartWikiXmlParser $xmlParser) {
			// Detect XMI source
			$exportInfo = $xmlParser->array['XMI']['XMI.header']['XMI.documentation'];
			$exporter = (string)$exportInfo['XMI.exporter'];
			$exporterVersion = (string)$exportInfo['XMI.exporterVersion'];
			
			switch ($exporter) {
				case "Enterprise Architect":
					// Any version
					$xmiParser = new XMIEnterpriseArchitect();
					break;
					
				case "ArgoUML (using Netbeans XMI Writer version 1.0)":
					// Any version
					$xmiParser = new XMIArgoUML();
					break;
					
				default:
					$xmiParser = new XMIBase();
					break;
				
			}
			$xmiParser->setXmlParser($xmlParser);
			return $xmiParser;
		}
		
		public function getDescriptionFromTaggedValues($taggedValueArray) {
			static $idRef = "";
			if ( $idRef == "" ) {
				$tagDefinitions = SmartWikiModelElement::arraySearchRecursive('UML:TagDefinition', $this->xmlParser->array);
				foreach($tagDefinitions as $tagDef ) {
					if ( isset($tagDef['_']) && isset($tagDef['_']['xmi.id']) && isset($tagDef['_']['name']) && $tagDef['_']['name'] == 'documentation' ) {
						$idRef = (string)$tagDef['_']['xmi.id'];
					}
				}
			}
			if ( $idRef == "" ) {
				// Unsupported
				return "";
			}
			
			$description = "";
			foreach($taggedValueArray as $tagElement) {
				if ( $tagElement['UML:TaggedValue.type']['UML:TagDefinition']['_']['xmi.idref'] == $idRef ) {
					$description = (string)$tagElement['UML:TaggedValue.dataValue'];
					break;
				}
			}
			return $description;	
		}
				
		public function setXmlParser($xmlParser) {
			$this->xmlParser = $xmlParser;
		}
		
	}
?>