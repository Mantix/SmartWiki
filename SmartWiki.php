<?php
# Not a valid entry point, skip unless MEDIAWIKI is defined
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/SmartWiki/SmartWiki.php" );
EOT;
	exit( 1 );
}

# Check if Semantic Media Wiki is installed
if ( !defined( 'SMW_VERSION' ) ) {
	echo <<<EOT
SmartWiki needs the extension Semantic Media Wiki installed.
Download it at http://semantic-mediawiki.org/wiki/Help:Download
EOT;
	exit( 1 );
}

# Check if Semantic Forms is installed
if ( !defined( 'SF_VERSION' ) ) {
	echo <<<EOT
SmartWiki needs the extension Semantic Forms installed.
Download it at http://www.mediawiki.org/wiki/Extension:Semantic_Forms#Download
EOT;
	exit( 1 );
}

# Add credits for the Special Page
$wgExtensionCredits[defined( 'SEMANTIC_EXTENSION_TYPE' ) ? 'semantic' : 'specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'SmartWiki',
	'version' => '1.0',
	'author' => 'Pieter Naber (pnaber@mantix.nl)',
	'url' => 'http://www.smartwiki.nl',
	'descriptionmsg' => 'SmartWiki-desc',
);


// Javascript
$wgResourceModules['ext.smartwiki'] = array(
		      'scripts' => array( "ext.smartwiki.main.js"),
		      'styles' => array(),
		      'dependencies' => array(),
		      'localBasePath' => dirname( __FILE__ )."/js",
		      'remoteExtPath' => basename( dirname( __FILE__ ) ),
			  'position' => 'top'
);


define( 'SMART_WIKI_VERSION', 1.0);

# Create a Special Page
$wgSpecialPages['SmartWiki'] 				= 'SmartWiki';
$wgSpecialPageGroups['SmartWiki']			= 'smw_group';

# Load classes
$dir = dirname( __FILE__ ) . '/';
$wgAutoloadClasses['SmartWiki']				= $dir . 'includes/SmartWiki_body.php';

# Class files
$wgAutoloadClasses['SWImport']				= $dir . 'includes/class/SWImport.class.php';
$wgAutoloadClasses['SWLogger']				= $dir . 'includes/class/SWLogger.class.php';
$wgAutoloadClasses['SWHelper']				= $dir . 'includes/class/SWHelper.class.php';
$wgAutoloadClasses['SWSyntaxTransformer']	= $dir . 'includes/class/SWSyntaxTransformer.class.php';
$wgAutoloadClasses['SWTransformer']			= $dir . 'includes/class/SWTransformer.class.php';
$wgAutoloadClasses['SWXmlParser']			= $dir . 'includes/class/SWXmlParser.class.php';

# XMI Parsers
$wgAutoloadClasses['XMIBase']				= $dir . 'includes/class/XMIBase.class.php';
$wgAutoloadClasses['XMIArgoUML']			= $dir . 'includes/class/XMIArgoUML.class.php';
$wgAutoloadClasses['XMIEnterpriseArchitect']= $dir . 'includes/class/XMIEnterpriseArchitect.class.php';

# Controller files
$wgAutoloadClasses['SWCreateController']	= $dir . 'includes/controller/SWCreateController.class.php';
$wgAutoloadClasses['SWFillController']		= $dir . 'includes/controller/SWFillController.class.php';
$wgAutoloadClasses['SWHomeController']		= $dir . 'includes/controller/SWHomeController.class.php';
$wgAutoloadClasses['SWImportController']	= $dir . 'includes/controller/SWImportController.class.php';
$wgAutoloadClasses['SWParseController']		= $dir . 'includes/controller/SWParseController.class.php';
$wgAutoloadClasses['SWUploadController']	= $dir . 'includes/controller/SWUploadController.class.php';
$wgAutoloadClasses['SWTestController']		= $dir . 'includes/controller/SWTestController.class.php';
$wgAutoloadClasses['SWRedLinksController']	= $dir . 'includes/controller/SWRedLinksController.class.php';

# Model files
$wgAutoloadClasses['SWAssociation']			= $dir . 'includes/model/SWAssociation.class.php';
$wgAutoloadClasses['SWAssociationClass']	= $dir . 'includes/model/SWAssociationClass.class.php';
$wgAutoloadClasses['SWAttribute']			= $dir . 'includes/model/SWAttribute.class.php';
$wgAutoloadClasses['SWClass']				= $dir . 'includes/model/SWClass.class.php';
$wgAutoloadClasses['SWEnumeration']			= $dir . 'includes/model/SWEnumeration.class.php';
$wgAutoloadClasses['SWGeneralization']		= $dir . 'includes/model/SWGeneralization.class.php';
$wgAutoloadClasses['SWModel']				= $dir . 'includes/model/SWModel.class.php';
$wgAutoloadClasses['SWModelElement']		= $dir . 'includes/model/SWModelElement.class.php';
$wgAutoloadClasses['SWPackage']				= $dir . 'includes/model/SWPackage.class.php';

# Type files
$wgAutoloadClasses['SWBoolean']				= $dir . 'includes/type/SWBoolean.class.php';
$wgAutoloadClasses['SWNumber']				= $dir . 'includes/type/SWNumber.class.php';
$wgAutoloadClasses['SWState']				= $dir . 'includes/type/SWState.class.php';
$wgAutoloadClasses['SWString']				= $dir . 'includes/type/SWString.class.php';

# SmartWiki Hooks
$wgAutoloadClasses['SmartWikiHooks']		= $dir . 'includes/SmartWiki.hooks.php';
$wgHooks['ParserFirstCallInit'][] 			= 'SmartWikiHooks::registerFunctions';
$wgHooks['LanguageGetMagic'][] 				= 'SmartWikiHooks::languageGetMagic';
$wgHooks['sfEditFormPreloadText'][]			= 'SmartWikiHooks::smwSmartWikiHook';
$wgHooks['EditPage::attemptSave'][]			= 'SmartWikiHooks::smwValidate';
$wgHooks['BeforePageDisplay'][] 			= 'SmartWikiHooks::wfJavaScriptAddModules';

# Other files
$wgExtensionMessagesFiles['SmartWiki']		= $dir . 'includes/SmartWiki.i18n.php';
$wgExtensionAliasesFiles['SmartWiki']		= $dir . 'includes/SmartWiki.alias.php';

# Fix the "Edit with form" button
$sfgRenameEditTabs = true;

# Autocomplete on every char
$sfgAutocompleteOnAllChars = true;
