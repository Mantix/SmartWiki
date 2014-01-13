<?php
class SWFillController {
	private $packages;
	private $classes;
	private $associationClasses;
	private $associations;
	private $generalizations;
	private $attributes;
	private $enumerations;

	/**
	 * Constructor
	 */
	function __construct() {
		# TODO: Do something or remove
	}

	/**
	 * Create links using a array of Title objects
	 * 
	 * @param array $allTitles - Array of Title objects
	 * 
	 * @return $htmlOut - HTML output code
	 */
	public function createLinks($allTitles, $allOrders) {
		global $wgOut, $wgUser;

		# HTML output
		$htmlOut  = '';

		# Get the skin to use for the links
		$sk = $wgUser->getSkin();

		# Form edit page
		$formEdit = Title::newFromText('FormEdit', NS_SPECIAL);

		# Loop the array
		for ($i = 0; $i < count($allTitles); $i++) {
			if ( get_class($allTitles[$i]) == "Title" ) {
				$form_names = SFFormLinker::getDefaultFormsForPage($allTitles[$i]);
			} else {
				$form_names = array();
			}
			$htmlOut .= 
				Xml::tags(
					'tr',
					array(),
					Xml::tags(
						'td',
						null,
						(count($form_names) > 0 ? $form_names[0] : '')
					) . 
					Xml::tags(
						'td',
						array(),
						$sk->link($allTitles[$i], str_replace("SmartWiki : ","",$allTitles[$i]))
					) . 
					Xml::tags(
						'td',
						array(),
						Xml::tags(
							'a',
							array(
								'href' => $allTitles[$i]->getLocalURL( 'action=formedit' ),
								'title' => wfMsgForContent('smartwiki-fill-edit'),
							),
							wfMsgForContent('smartwiki-fill-edit')
						) . 
						' &nbsp; - &nbsp; ' . 
						Xml::tags(
							'a',
							array(
								'href' => $allTitles[$i]->getLocalURL( 'action=delete' ),
								'title' => wfMsgForContent('smartwiki-fill-delete'),
							),
							wfMsgForContent('smartwiki-fill-delete')
						)
					) . 
					Xml::tags(
						'td',
						array(),
						$this->getMissingParts($allTitles[$i])
					) . 
					Xml::tags(
						'td',
						array(),
						($allOrders[$i] == null ? '' : 
							Xml::tags(
								'form',
								array(
									'action' => '',
									'method' => 'post',
								),
								Xml::element(
									'input',
									array(
										'type' => 'hidden',
										'name' => 'classTitle',
										'value' => $allTitles[$i]->getText(),
									)
								) . 
								Xml::element(
									'input',
									array(
										'type' => 'hidden',
										'name' => 'classOrderOld',
										'value' => $allOrders[$i]
									)
								) . 
								Xml::element(
									'input',
									array(
										'type' => 'text',
										'name' => 'classOrderNew',
										'size' => '2',
										'value' => $allOrders[$i],
										'class' => 'formInput',
									)
								) . 
								Xml::element(
									'input',
									array(
										'type' => 'submit',
										'value' => wfMsgForContent('smartwiki-fill-order-button'),
									)
								)
							)
						)
					)
				);
		}

		# Return the HTML codes
		return $htmlOut;
	}

	/**
	 * Create a list using the specified name and Title object array
	 */
	private function createList($listPackage, $titleArray, $orderArray) {
		// Current name
		$listName = $listPackage instanceof SWPackage ? $listPackage->getTitle() : ($listPackage == null ? wfMsgForContent('smartwiki-fill-without-package') : $listPackage);

		# Form start
		$formstart = Title::newFromText('FormStart', NS_SPECIAL);
		$formstart_link = $formstart->getLocalURL();

		return 
			Xml::tags( 'tr', null, 
				Xml::tags(
					'th',
					array(
						'style' => 'background-color:#ccccff;',
						'colspan' => 6,
					),
					($listPackage instanceof SWPackage ? 
						Xml::tags(
							'span',
							array(
								'style' => 'weight: 700; float: right;',
							),
							Xml::tags(
								'form',
								array(
									'action' => '',
									'method' => 'post',
								),
								Xml::element(
									'input',
									array(
										'type' => 'hidden',
										'name' => 'packageTitle',
										'value' => $listPackage->getTitle()->getText(),
									)
								) . 
								Xml::element(
									'input',
									array(
										'type' => 'hidden',
										'name' => 'packageOrderOld',
										'value' => $listPackage->getOrder(),
									)
								) . 
								wfMsgForContent('smartwiki-fill-order-text') . 
								Xml::element(
									'input',
									array(
										'type' => 'text',
										'name' => 'packageOrderNew',
										'size' => '2',
										'value' => $listPackage->getOrder(),
										'class' => 'formInput',
									)
								) . 
								Xml::element(
									'input',
									array(
										'type' => 'submit',
										'value' => wfMsgForContent('smartwiki-fill-order-button'),
									)
								)
							)
						)
					: '') . 
					$listName
				)
			) . 
			Xml::tags( 'tr', null, 
				Xml::tags( 'th', null, wfMsgForContent('smartwiki-fill-form') ) . 
				Xml::tags( 'th', null, wfMsgForContent('smartwiki-fill-name') ) . 
				Xml::tags( 'th', null, wfMsgForContent('smartwiki-fill-action') ) . 
				Xml::tags( 'th', null, wfMsgForContent('smartwiki-fill-empty') ) . 
				Xml::tags( 'th', null, wfMsgForContent('smartwiki-fill-order') )
			) . 
			$this->createLinks($titleArray, $orderArray) . 
			Xml::tags( 'tr', null, 
				Xml::tags(
					'td',
					array(
						'colspan' => 5,
						'style' => 'background-color: #fff; border: #FFF 0px;'
					),
					'&nbsp;'
				)
			);
	}

	/**
	 * Get the missing parts of a page
	 */
	private function getMissingParts($title) {
		# Current content of the article
		$article = new Article($title);
		$content = $article->getRawText();

		$template_start = strpos($content, '{{');
		$template_end = strrpos($content, '}}');
		$template = substr($content, $template_start, $template_end - $template_start);

		$parts = '';
		$key_end = 0;
		while (($template != '') && ($key_end = strpos($template, "=\n", $key_end + 1)) != false) {
			$content_key = substr($template, 0, $key_end);
			$key_start = strrpos($content_key, '|');
			$key = substr($content_key, $key_start + 1);

			$parts .= $key . ', ';
		}

		if (strlen($parts) > 0) {
			$parts = substr($parts, 0, -2);
		}

		return $parts;
	}

	/**
	 * The fill page of SmartWiki
	 */
	public function execute($wgRequest) {
		global $wgOut, $wgUser;
		# Handle package order request
		if (($packageTitle = $wgRequest->getText( 'packageTitle' )) && 
			($packageOrderOld = $wgRequest->getText( 'packageOrderOld' )) &&
			($packageOrderNew = $wgRequest->getText( 'packageOrderNew' ))) {

			$title = Title::newFromText($packageTitle);
			$article = new Article($title);
			$text = $article->getRawText();
			$text = str_replace('|Container order=' . $packageOrderOld, '|Container order=' . $packageOrderNew, $text);
			$article->doEdit($text, 'SmartWiki has changed the order to ' . $packageOrderNew);
		}

		# Handle class order request
		if (($classTitle = $wgRequest->getText( 'classTitle' )) && 
			($classOrderOld = $wgRequest->getText( 'classOrderOld' )) &&
			($classOrderNew = $wgRequest->getText( 'classOrderNew' ))) {

			$title = Title::newFromText($classTitle);
			$article = new Article($title);
			$text = $article->getRawText();
			$text = str_replace('|Container order=' . $classOrderOld, '|Container order=' . $classOrderNew, $text);
			$article->doEdit($text, 'SmartWiki has changed the order to ' . $classOrderNew);

		}

		# HTML code container
		$htmlOut  = '';

		# Html code for the fill message
		$htmlOut .= Xml::tags( 'h2', null, wfMsgForContent('smartwiki-fill-title'));
		$htmlOut .= Xml::tags( 'p', null, wfMsgForContent('smartwiki-fill-page'));

		# Get the current objects
		SWPackage::fill(SWTransformer::getFieldsByCategory('SmartWiki Packages'));
		SWClass::fill(SWTransformer::getFieldsByCategory('SmartWiki Classes'));
		SWAssociationClass::fill(SWTransformer::getFieldsByCategory('SmartWiki Association classes'));
		SWAssociation::fill(SWTransformer::getFieldsByCategory('SmartWiki Associations'));
		SWGeneralization::fill(SWTransformer::getFieldsByCategory('SmartWiki Generalizations'));
		SWAttribute::fill(SWTransformer::getFieldsByCategory('SmartWiki Attributes'));
		SWEnumeration::fill(SWTransformer::getFieldsByCategory('SmartWiki Enumerations'));

		$titleFormStart = Title::newFromText('FormStart', NS_SPECIAL);

		# Html code for the table with all the parsed pages
		$htmlOut .= Xml::tags(
			'table',
			array(
				'class' => 'wikitable',
				'style' => 'margin: 0 auto; border: 0;'
			),
			$this->createPackageLists() . 

			# Form to add an enumeration
			Xml::tags(
				'tr',
				null,
				Xml::tags(
					'td',
					array(
						'colspan' => 4,
					),
					Xml::tags(
						'h2',
						null,
						wfMsgForContent('smartwiki-fill-add-text', 'Enumeration')
					) . 
						/*
					Xml::tags(
						'p',
						null,
						wfMsgForContent('smartwiki-fill-add-prefix', 'Enumeration', wfMsgForContent('smartwiki-prefix'))
					) . */
					Xml::tags(
						'form',
						array(
							'action' => $titleFormStart->getLocalURL(),
							'method' => 'get',
							'onSubmit' => 'return SmartWiki.addPrefix(\'page_name\', \''.wfMsgForContent('smartwiki-prefix').'\');',
						),
						Xml::element(
							'input',
							array(
								'type' => 'text',
								'name' => 'page_name',
								'size' => '25',
								'value' => '',
								'class' => 'formInput',
							)
						) . 
						Xml::element(
							'input',
							array(
								'type' => 'hidden',
								'value' => 'SmartWiki Enumeration',
								'name' => 'form',
							)
						) . 
						Xml::element(
							'input',
							array(
								'type' => 'submit',
								'value' => wfMsgForContent('smartwiki-fill-add-button'),
							)
						)
					)
				)
			)
		);

		# Get the skin for the "back" link
		$sk = $wgUser->getSkin();
		# Html code for the "back" link
		$htmlOut .= Xml::tags( 'p', null, $sk->link(Title::newFromText('SmartWiki', NS_SPECIAL), wfMsgForContent('smartwiki-back')));
	
		# Output the HTML code
		$wgOut->addHTML( $htmlOut );
	}

	/**
	 * Loop through the packages, creating the lists
	 */
	private function createPackageLists() {
		$htmlOut = '';

		# The SmartWiki model
		$smartwikiModel = SWModel::singleton();
		$this->packages = $smartwikiModel->getPackages();
		$this->classes = $smartwikiModel->getClasses();
		$this->associationClasses = $smartwikiModel->getAssociationClasses();
		$this->associations = $smartwikiModel->getAssociations();
		$this->generalizations = $smartwikiModel->getGeneralizations();
		$this->attributes = $smartwikiModel->getAttributes();
		$this->enumerations = $smartwikiModel->getEnumerations();

		# Everything with a package and...
		for ($i = 0; $i < count($this->packages); $i++) {
			$htmlOut .= $this->createOnePackageList($this->packages[$i]);
		}

		# ...everything without a package
		$htmlOut .= $this->createOnePackageList();

		# The current enumerations
		$titleArray = array(); 
		$orderArray = array();
		foreach ($this->enumerations AS $enumerationKey => $enumerationValue) {
			$titleArray[] = $enumerationValue->getTitle();
			$orderArray[] = null;	// Enumerations do not have any order
		}
		$htmlOut .= $this->createList(wfMsgForContent('smartwiki-fill-enumerations'), $titleArray, $orderArray);

		return $htmlOut;
	}

	private function createOnePackageList($currentPackage = NULL) {

		$titleArray = array();
		$orderArray = array();

		if (isset($currentPackage)) {
			$currentTitle = $currentPackage->getTitle();
			$titleArray[] = $currentPackage->getTitle();
			$orderArray[] = null;
		} else {
			$currentTitle = null;
		}

		# Get all classes
		foreach ($this->classes AS $key => $value) {
			# Preset
			$currentClassTitle = NULL;

			if ((isset($currentPackage)) && ($value->getPackage()->getTitle()->equals($currentTitle))) {
				$titleArray[] = $value->getTitle();
				$orderArray[] = $value->getOrder();
				$currentClassTitle = $value->getTitle();
			}

			# Get all attributes
			foreach ($this->attributes AS $attributeKey => $attributeValue) {
				if ($currentClassTitle != NULL && $attributeValue->getClass()->getTitle()->equals($currentClassTitle)) {
					$titleArray[] = $attributeValue->getTitle();
					$orderArray[] = $attributeValue->getOrder();
					unset($this->attributes[$attributeKey]);
				}
			}
		}

		# Get all association classes
		foreach ($this->associationClasses AS $key => $value) {
			# Preset
			$currentClassTitle = NULL;
		
			if ((isset($currentPackage)) && ($value->getPackage()->getTitle()->equals($currentTitle))) {
				$titleArray[] = $value->getTitle();
				$orderArray[] = $value->getOrder();
				$currentClassTitle = $value->getTitle();
			}
		
			# Get all attributes
			foreach ($this->attributes AS $attributeKey => $attributeValue) {
				if ($currentClassTitle != NULL && $attributeValue->getClass()->getTitle()->equals($currentClassTitle)) {
					$titleArray[] = $attributeValue->getTitle();
					$orderArray[] = $attributeValue->getOrder();
					unset($this->attributes[$attributeKey]);
				}
			}
		}		

		# Get all associations
		foreach ($this->associations AS $key => $value) {
			if ((!isset($currentPackage)) || ($value->getPackage()->getTitle()->equals($currentTitle))) {
				$titleArray[] = $value->getTitle();
				$orderArray[] = $value->getOrder();
				unset($this->associations[$key]);
			}
		}
		
		# Get all generalizations
		foreach ($this->generalizations AS $key => $value) {
			if ((!isset($currentPackage)) || ($value->getPackage()->getTitle()->equals($currentTitle))) {
				$titleArray[] = $value->getTitle();
				$orderArray[] = $value->getOrder();
				unset($this->generalizations[$key]);
			}
		}

		# Create the list
		return $this->createList($currentPackage, $titleArray, $orderArray);

	}

}
