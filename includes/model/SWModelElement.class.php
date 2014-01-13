<?php
// todo: abstract static functions are not allowed in strict php
// Dropped abstract static class functions. Due to an oversight, PHP 5.0.x and 5.1.x allowed abstract static functions in classes. As of PHP 5.2.x, only interfaces can have them. 
// http://php.net/manual/en/migration52.incompatible.php

abstract class SWModelElement {
	// SWString object
	private $searchPath;

	// SWString object
	private $id;

	// Title object
	private $title;

	// SWState object
	private $state;

	// SWString object
	private $packageID;

	// SWPackage object
	private $package;

	// SWString object
	private $name;

	// SWString object
	private $description;

	// SWString object
	private $content;

	// SWNumber object
	private $order;

	/**
	 * Constructor
	 */
	function __construct() {
		$this->setState(SWState::LOG_NOACTION);
	}

	/**
	 * Create a page for the current object with the given template
	 * 
	 * @param String $form_template
	 * 
	 * @return Title object of the created page
	 */
	abstract public function createPage($form_template);

	/**
	 * Read an Article, is excists, and return the title, the content and the template values
	 */
	public function getValuesFromArticle() {
		# Set the return array of values
		$values = array();

		# Current content of the article
		$article = new Article($this->title);
		$content = $article->getRawText();
		$content_template_start = strpos($content, '{{');
		$content_template_end = strrpos($content, '}}') + 2;
		if (($content_template_start !== false) && ($content_template_end !== false)) {
			$content_template = substr($content, $content_template_start, $content_template_end - $content_template_start);
			$this->setContent(new SWString(substr($content, 0, $content_template_start) . substr($content, $content_template_end)));
		} else {
			$content_template = '';
			$this->setContent(new SWString($content));
		}

		# Find all fields already filled in
		$pos = 0;
		while (($start = strpos($content_template, "\n|", $pos)) !== false) {
			# Key
			$key_start = $start + 2;
			$key_end = strpos($content_template, "=", $key_start);
			$key = substr($content_template, $key_start, $key_end - $key_start);

			#Value
			$field_start = $key_end + 1;
			$field_end = strpos($content_template, "\n", $field_start);
			$field = substr($content_template, $field_start, $field_end - $field_start);
			$pos = $field_end;

			# Store it to the array
			$values[$key] = $field;
		}

		return $values;
	}

	/**
	 * Parse a XMI file for ModelElements, using the given SyntaxTransformer
	 * 
	 * @param SWSyntaxTransformer $syntaxTransformer
	 * 
	 * @return An array of ModelElements
	 */
	abstract public static function parseXMI(SWXmlParser $xmlParser);

	/**
	* Search in a array, returning a array of all the results
	* 
	*	# We actually use a static function to lower load on the webserver, known as PHP bug #29107: http://bugs.php.net/bug.php?id=29107
	*
	* @var $searchKey - The key we need to search for
	*
	* @var SWXmlParser $xmlParser - The array we need to search in
	*
	* @var $searchPath - The path we have already searched
	*
	* @return $returnArray - An array of searchresults
	*/
	public static function arraySearchRecursive($searchKey, $searchArray, $searchPath = '') {
		$returnArray = array();
	
		# Check if the $searchArray is an array
		if (is_array($searchArray)) {
			foreach ($searchArray AS $key => $value) {
				$innerPath = $searchPath . '/' . $key;
	
				# If the key is identical to our $searchKey, we'll add it to the result
				if (strcmp($key, $searchKey) == 0) {
					# If a list of values is provided
					if ((isset($value[0])) && (is_array($value[0]))) {
						foreach ($value AS $innerKey => $innerValue) {
							$innerValue['searchPath'] = $innerPath . '/' . $innerKey;
							$returnArray[] = $innerValue;
						}
					}
	
					# If only one value is provied
					if ((isset($value['_'])) && (is_array($value['_']))) {
						$value['searchPath'] = $innerPath;
						$returnArray[] = $value;
					}
				}
		
				# Continue the search recursively
				$returnArray = array_merge($returnArray, SWModelElement::arraySearchRecursive($searchKey, $value, $innerPath));
			}
		}
	
		# Return the result
		return $returnArray;
	}

	/**
	 * Create the UML forms for the model elements
	 * 
	 * @var $elementArray - An array of model elements
	 * 
	 * @var SWSyntaxTransformer $syntaxTransformer - The transformer
	 * 
	 * @var String $formName - The name of the form we need to use
	 * 
	 * @return $titleArray - An array of the Titles we've created
	 */
	public static function createPages(SWString $formName, array $elementArray, SWLogger $log) {
		# Get the template of this page form
		$form_template = SWModelElement::getTemplatesOfForm($formName);
		foreach ($elementArray AS $key => $value) {
			$currentTitle = $value->createPage($form_template);

			$log->add($currentTitle, $value->getState());
		}
	}

	public function equals(SWModelElement $other) {
		if ($this->getTitle() == NULL || $other == NULL || $other->getTitle() == NULL) {
			return false;
		}

		return $this->getTitle()->equals($other->getTitle());
	}

	/**
	 * Create an array of ModelElement objects, fill it with the fields provided
	 * 
	 * @param $fieldsArray - An array of fields
	 * 
	 * @return $modelElementArray - An array of model elements
	 */
	abstract public static function fill($fieldsArray);

	/**
	* Read a given form, give back the forms template
	*
	* @var $formName - The Form we need to get
	*
	* @return $returnValues - The template and the fields used in the form
	*/
	private static function getTemplatesOfForm(SWString $formName) {
		# Get the contents of the form
		$title = Title::newFromText($formName->getValue(), SF_NS_FORM);
		$article = new Article($title);
		$article_text = $article->getRawText();
		
		# Create an array for the results
		$returnValues = array();
		$returnValues['fields'] = array();
		$returnValues['template'] = "";
		
		$article_pos = 0;
		# A form can contain multiple Templates
		while (strpos($article_text, '{{{for template', $article_pos)) {
			# Get the Template
			$template_start = strpos($article_text, '{{{for template', $article_pos);
			$template_end = strpos($article_text, '{{{end template}}}', $template_start) + 18;
			$template = substr($article_text, $template_start, $template_end - $template_start);

			# Get the Template name
			$template_name_start = strpos($template, '{{{for template|') + 16;
			$template_name_end = strpos($template, '}}}', $template_name_start);
			$template_name = substr($template, $template_name_start, $template_name_end - $template_name_start);

			# Add the name to the return template
			$returnValues['template'] .= "{{" . $template_name . "\n";

			# Loop through the fields
			$template_pos = 0;
			while (strpos($template, '{{{field|', $template_pos)) {
				$field_start = strpos($template, '{{{field|', $template_pos) + 9;
				$field_end1 = strpos($template, '|', $field_start);
				$field_end2 = strpos($template, '}}}', $field_start);

				if (($field_end1 !== false) && ($field_end1 < $field_end2)) {
					$field = substr($template, $field_start, $field_end1 - $field_start);
				} else {
					$field = substr($template, $field_start, $field_end2 - $field_start);
				}

				# Add the field to the return array
				$returnValues['fields'][] = $field;
				$returnValues['template'] .= "|" . $field . "=%" . $field . "%\n";

				$template_pos = $field_end2;
			}
	
			$returnValues['template'] .= "}}\n";

			$article_pos = $template_end;
		}

		# Return the results
		return $returnValues;
	}

	public function __toString() {
		return 'SWModelElement: ' . $this->getID() . '; ' . $this->getTitle();
	}

	/**
	* Get the container of the item in the $searchPath
	*
	* @var $searchPath - The path we need to use
	*
	* @var $containerName - The container name we search for
	*
	* @return ID - The ID of the container
	*/
	public function getContainer($searchPath, SWXmlParser $xmlParser, $containerName = 'UML:Package') {
		# Do some magic

		$name_pos = strrpos($searchPath, 'UML:') - strlen($searchPath) - 1;
		if ( strrpos($searchPath, '/' . $containerName . '/', $name_pos) === false && $containerName == 'UML:Package' ) {
			// Package is a bit special because the root package is UML:Model and not UML:Package
			$container_pos = strrpos($searchPath, '/UML:Model/', $name_pos) + 1;
		} else {
			$container_pos = strrpos($searchPath, '/' . $containerName . '/', $name_pos) + 1;
		}
		$slash_after_container = strpos($searchPath, '/', $container_pos);
		$slash_after_number = strpos($searchPath, '/', $slash_after_container + 1);
		$the_number = substr($searchPath, $slash_after_container + 1, $slash_after_number - $slash_after_container - 1);
		$path_to_container = substr($searchPath, 0, (substr($the_number, 0, 4) == 'UML:' ? $slash_after_container : $slash_after_number));
		
		# If we've found a container
		if ($container_pos !== false) {
			$path = explode('/', $path_to_container);
			$current = $xmlParser->array;
		
			# Walk through the parser using the path
			for ($i = 1; $i < count($path); $i++) {
				$current = $current[$path[$i]];
			}
		}
		
		# Return the result
		$id = isset($current['_']['xmi.id']) ? $current['_']['xmi.id'] : '';
		return new SWString($id);
	}

	abstract public function getWikiText();

	public function getSearchPath() {
		return $this->searchPath;
	}

	public function setSearchPath(SWString $searchPath) {
		$this->searchPath = $searchPath;
	}

	public function getId() {
		return $this->id;
	}

	public function setId(SWString $id) {
		$this->id = $id;
	}

	public function getTitle() {
		return $this->title;
	}

	public function setTitle(Title $title = NULL) {
		$this->title = $title;
	}

	public function getState() {
		return $this->state;
	}

	public function setState($state = SWState::LOG_UNKNOWN) {
		$this->state = $state;
	}

	public function getPackageID() {
		return $this->packageID;
	}
	
	public function setPackageID(SWString $packageID) {
		$this->packageID = $packageID;
	}

	public function getPackage() {
		return $this->package;
	}
	
	public function setPackage(SWPackage $package = NULL) {
		$this->package = $package;
	}

	public function getName() {
		return $this->name;
	}
	
	public function setName(SWString $name) {
		$this->name = $name;
	}

	public function getDescription() {
		return $this->description;
	}
	
	public function setDescription(SWString $description) {
		$this->description = $description;
	}

	public function getContent() {
		return $this->content;
	}

	public function setContent(SWString $content) {
		$this->content = $content;
	}

	public function getOrder() {
		return $this->order;
	}

	public function setOrder(SWNumber $order) {
		$this->order = $order;
	}
}
?>