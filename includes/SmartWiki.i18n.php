<?php
/**
 * Internationalisation file for extension SmartWiki.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

$messages['en'] = array(
	# Text for admin pages
	'smartwiki'						=> 'SmartWiki',
	'smartwiki-desc'				=> 'Use a UML class diagram to automatically create Semantic Forms',
	'smartwiki-back'				=> 'Back',

	# Text for admin initialize page
	'smartwiki-initialize-title'		=> '1. Initialize SmartWiki',
	'smartwiki-initialize-description'	=> 'Before the first use of SmartWiki, you have to initialize it. Click on the button below to create the necessary semantic forms for SmartWiki extension.',
	'smartwiki-initialize-showinmenu'	=> 'Developer mode (Show links to UML categories in the menu)',
	'smartwiki-initialize-button'		=> 'Initialize',
	'smartwiki-initialize-page'			=> 'You have clicked the button to initialize SmartWiki. We have (re)created the semantic forms, the categories and the menu. The following pages were (re)created:',
	'smartwiki-initialize-recreate'		=> '(Re)created the $1 "$2".',

	# Text for admin upload page
	'smartwiki-upload-title'		=> '2. Upload a XMI file',
	'smartwiki-upload-description'	=> 'Select a XMI file to upload. We will use this XMI file to create the structure of this MediaWiki.',
	'smartwiki-upload-button'		=> 'Upload',
	'smartwiki-upload-page'			=> 'You have clicked the button to upload a XMI file. We have stored this file as a Wiki article:',
	'smartwiki-upload-error'		=> 'Something went wrong during the upload. Remember to select a proper XMI file.',
	'smartwiki-upload-header'		=> 'Below you will find the contents of an uploaded XMI file. SmartWiki can use this file to create a structure for this Wiki.',

	# Text for admin parse page
	'smartwiki-parse-title'			=> '3. Parse the XMI file',
	'smartwiki-parse-description'	=> 'Click on the button below to (re)parse the XMI file you\'ve uploaded in step 2.',
	'smartwiki-parse-button'		=> 'Parse',
	'smartwiki-parse-page'			=> 'You have clicked the button to (re)parse the XMI file. We have created or edited the following pages:',
	'smartwiki-parse-error'			=> 'We can\'t find the XMI File page. Remember to upload a XMI file in step 2 before proceeding to this step.',
	'smartwiki-prefix'				=> 'SmartWiki : ', # Added to the objects from the SmartWiki UML metamodel so they are seperated from the objects from the SmartWiki Design

	# Text for admin fill page
	'smartwiki-fill-title'			=> '4. Fill the missing parts',
	'smartwiki-fill-description'	=> 'Click the button below to show the pages SmartWiki created using the parsed XMI file so you can edit or fill in the (missing) parts.',
	'smartwiki-fill-without-package'=> 'Without package',
	'smartwiki-fill-enumerations'	=> 'Enumerations',
	'smartwiki-fill-button'			=> 'Fill',
	'smartwiki-fill-page'			=> 'You have clicked the button to edit or fill in the (missing) parts from the parsed XMI file. Use the following list:',
	'smartwiki-fill-edit'			=> 'Edit',
	'smartwiki-fill-delete'			=> 'Delete',
	'smartwiki-fill-form'			=> 'Form',
	'smartwiki-fill-name'			=> 'Name',
	'smartwiki-fill-action'			=> 'Action',
	'smartwiki-fill-empty'			=> 'Empty parts',
	'smartwiki-fill-order'			=> 'Order',
	'smartwiki-fill-add-text'		=> 'Add a $1',
	'smartwiki-fill-add-prefix'		=> 'Make sure you start the name of the $1 with the prefix "$2".',
	'smartwiki-fill-add-button'		=> 'Create or edit',
	'smartwiki-fill-order-text'		=> 'Order: ',
	'smartwiki-fill-order-button'	=> 'Set',

	# Text for admin create page
	'smartwiki-create-title'		=> '5. Create the knowledge base',
	'smartwiki-create-description'	=> 'Click on the button below to (re)create the entire knowledge base using the packages, classes and attributes you have created.',
	'smartwiki-create-button'		=> 'Create',
	'smartwiki-create-page'			=> 'You have clicked the button to (re)create the knowledge base using the packages, classes and attributes you created. We have created or edited the following pages:',
	'smartwiki-create-error'		=> 'Something went wrong while creating the knowledge base.',
	'smartwiki-create-no-package'	=> 'Without package',
	'smartwiki-create-more-info'	=> 'More information',

	# Ignore these fields during the SmartWiki transformation
	# Use this to ignore fields the Wiki has as standards
	# Comma seperated, use lowercase.
	'smartwiki-create-ignore-fields'=> 'naam,name,title,titel,description,beschrijving',

	# Text for admin test page
	'smartwiki-test-title'			=> 'X. Test',
	'smartwiki-test-description'	=> 'Click on the button below to test SmartWiki using <a href="http:#www.simpletest.org/" title="SimpleTest" target="_blank">SimpleTest v1.1 alpha (www.simpletest.org)</a>.',
	'smartwiki-test-button'			=> 'Test SmartWiki',

	# Admin menu
	'smartwiki-menu-title'			=> 'SmartWiki menu',

	# SmartWiki Transformator
	'smartwiki-article'				=> 'Article',
	'smartwiki-uncompleted-articles'=> 'Unfinished articles',
	'smartwiki-percentage-completed'=> 'Percentage completed',
	'smartwiki-create-edit'			=> 'Create or edit $1 $2',
	'smartwiki-browse-data'			=> 'Browse and filter all $1',

	# Use "an" in stead of "a" with the following letters
	'smartwiki-grammar-an-letters'	=> 'aeiouh',
	'smartwiki-grammar-a'			=> 'a',
	'smartwiki-grammar-an'			=> 'an',

	# Text for logging
	'smartwiki-log-created'			=> '$1 "<strong>$2</strong>" was created.',
	'smartwiki-log-edited'			=> '$1 "<strong>$2</strong>" was edited.',
	'smartwiki-log-deleted'			=> '$1 "<strong>$2</strong>" was deleted.',
	'smartwiki-log-noaction'		=> '$1 "<strong>$2</strong>" was not changed.',
	'smartwiki-log-page'			=> 'Page',
		
		
	# General help texts
	'smartwiki-help-change-object'  => 'Change the type of the object on this page to another type. The possible types are determined by the specialisation structure of the model.
Be aware: by changing the type to a parent type, i.e., a less specific type, exisiting content on this page may be moved to the free text field. (This can be undone by changing the type back later.) '

);

