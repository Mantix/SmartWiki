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
	 * @return $pageHtml - HTML output code
	 */
	public function createLinks($allTitles, $allOrders) {
		global $wgOut, $wgUser;

		# HTML output
		$pageHtml  = '';

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
			$pageHtml .= '
				<tr>
					<td>' . (count($form_names) > 0 ? $form_names[0] : '') . '</td>
					<td>' . $sk->link($allTitles[$i], str_replace("SmartWiki : ","",$allTitles[$i])) . '</td>
					<td><a href="' . $allTitles[$i]->getLocalURL('action=formedit') . '" title="' . wfMsgForContent('smartwiki-fill-edit') . '">' . wfMsgForContent('smartwiki-fill-edit') . '</a></td>
					<td><a href="' . $allTitles[$i]->getLocalURL('action=delete') . '" title="' . wfMsgForContent('smartwiki-fill-delete') . '">' . wfMsgForContent('smartwiki-fill-delete') . '</a></td>
					<td>' . $this->getMissingParts($allTitles[$i]) . '</td>
					<td>' . 
						($allOrders[$i] == null ? '' : ' 
							<form action="" method="post">
								<input type="hidden" name="classTitle" value="' . $allTitles[$i]->getText() . '" />
								<input type="hidden" name="classOrderOld" value="' . $allOrders[$i] . '" />
								<input type="text" name="classOrderNew" size="2" value="' . $allOrders[$i] . '" class="formInput" />
								<input type="submit" value="' . wfMsgForContent('smartwiki-fill-order-button') . '" />
							</form>') . '
					</td>
				</tr>';
		}

		# Return the HTML codes
		return $pageHtml;
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

		return '
			<tr>
				<th style="background: #ccf;" colspan="6">' . 
					($listPackage instanceof SWPackage ? '
						<span style="weight: 700; float: right;">
							<form action="" method="post">
								<input type="hidden" name="packageTitle" value="' . $listPackage->getTitle()->getText() . '" />
								<input type="hidden" name="packageOrderOld" value="' . $listPackage->getOrder() . '" />
								' . wfMsgForContent('smartwiki-fill-order-text') . '
								<input type="text" name="packageOrderNew" size="2" value="' . $listPackage->getOrder() . '" class="formInput" />
								<input type="submit" value="' . wfMsgForContent('smartwiki-fill-order-button') . '" />
							</form>
						</span>' : '') . '
					' . $listName . '
				</th>
			</tr>
			<tr>
				<th>' . wfMsgForContent('smartwiki-fill-form') . '</th> 
				<th>' . wfMsgForContent('smartwiki-fill-name') . '</th>
				<th>' . wfMsgForContent('smartwiki-fill-action') . '</th>
				<th>' . wfMsgForContent('smartwiki-fill-empty') . '</th>
				<th>' . wfMsgForContent('smartwiki-fill-order') . '</th>
			</tr>
			' . $this->createLinks($titleArray, $orderArray) . '
			<tr>
				<td colspan="5" style="background: #fff; border: #FFF 0px;">&nbsp;</td>
			</tr>';
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
		$pageHtml  = '
			<h2>' . wfMsgForContent('smartwiki-fill-title') . '</h2>
			<p>' . wfMsgForContent('smartwiki-fill-page') . '</p>';

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
		$pageHtml .= '
			<table class="wikitable" style="margin: 0 auto; border: 0;">
				' . $this->createPackageLists() . '
				<tr>
					<td colspan="4">
						<h2>' . wfMsgForContent('smartwiki-fill-add-text', 'Enumeration') . '</h2>
						<p>' . wfMsgForContent('smartwiki-fill-add-prefix', 'Enumeration', wfMsgForContent('smartwiki-prefix')) . '</p>
						<form action="' . $titleFormStart->getLocalURL() . '"  method="get" onSubmit="return SmartWiki.addPrefix(\'page_name\', \'' . wfMsgForContent('smartwiki-prefix') . '\');">
							<input type="text" name="page_name" size="25" value="" class="formInput" />
							<input type="hidden" name="form" value="SmartWiki Enumeration" />
							<input type="submit" value="' . wfMsgForContent('smartwiki-fill-add-button') . '" />
						</form>
					</td>
				</tr>
			</table>';

		# Get the skin for the "back" link
		$sk = $wgUser->getSkin();
		# Html code for the "back" link
		$pageHtml .= '
			<p>' . $sk->link(Title::newFromText('SmartWiki', NS_SPECIAL), wfMsgForContent('smartwiki-back')) . '</p>';
	
		# Output the HTML code
		$wgOut->addHTML( $pageHtml );
	}

	/**
	 * Loop through the packages, creating the lists
	 */
	private function createPackageLists() {
		$pageHtml = '';

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
			$pageHtml .= $this->createOnePackageList($this->packages[$i]);
		}

		# ...everything without a package
		$pageHtml .= $this->createOnePackageList();

		# The current enumerations
		$titleArray = array(); 
		$orderArray = array();
		foreach ($this->enumerations AS $enumerationKey => $enumerationValue) {
			$titleArray[] = $enumerationValue->getTitle();
			$orderArray[] = null;	// Enumerations do not have any order
		}
		$pageHtml .= $this->createList(wfMsgForContent('smartwiki-fill-enumerations'), $titleArray, $orderArray);

		return $pageHtml;
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
