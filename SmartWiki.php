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
$wgSpecialPages['SmartWiki'] 						= 'SmartWiki';
$wgSpecialPageGroups['SmartWiki']					= 'smw_group';

# Load classes
$dir = dirname( __FILE__ ) . '/';
$wgAutoloadClasses['SmartWiki']						= $dir . 'includes/SmartWiki_body.php';

# Class files
$wgAutoloadClasses['SmartWikiInitialize']			= $dir . 'includes/class/SmartWikiInitialize.class.php';
$wgAutoloadClasses['SmartWikiLog']					= $dir . 'includes/class/SmartWikiLog.class.php';
$wgAutoloadClasses['SmartWikiPage']					= $dir . 'includes/class/SmartWikiPage.class.php';
$wgAutoloadClasses['SmartWikiSyntaxTransformer']	= $dir . 'includes/class/SmartWikiSyntaxTransformer.class.php';
$wgAutoloadClasses['SmartWikiTransformer']			= $dir . 'includes/class/SmartWikiTransformer.class.php';
$wgAutoloadClasses['SmartWikiXmlParser']			= $dir . 'includes/class/SmartWikiXmlParser.class.php';

# XMI Parsers
$wgAutoloadClasses['XMIBase']					= $dir . 'includes/class/XMIBase.class.php';
$wgAutoloadClasses['XMIArgoUML']				= $dir . 'includes/class/XMIArgoUML.class.php';
$wgAutoloadClasses['XMIEnterpriseArchitect']	= $dir . 'includes/class/XMIEnterpriseArchitect.class.php';

# Controller files
$wgAutoloadClasses['SmartWikiCreateController']		= $dir . 'includes/controller/SmartWikiCreateController.class.php';
$wgAutoloadClasses['SmartWikiFillController']		= $dir . 'includes/controller/SmartWikiFillController.class.php';
$wgAutoloadClasses['SmartWikiHomeController']		= $dir . 'includes/controller/SmartWikiHomeController.class.php';
$wgAutoloadClasses['SmartWikiInitializeController']	= $dir . 'includes/controller/SmartWikiInitializeController.class.php';
$wgAutoloadClasses['SmartWikiParseController']		= $dir . 'includes/controller/SmartWikiParseController.class.php';
$wgAutoloadClasses['SmartWikiUploadController']		= $dir . 'includes/controller/SmartWikiUploadController.class.php';
$wgAutoloadClasses['SmartWikiTestController']		= $dir . 'includes/controller/SmartWikiTestController.class.php';
$wgAutoloadClasses['SmartWikiRedLinksController']	= $dir . 'includes/controller/SmartWikiRedLinksController.class.php';

# Model files
$wgAutoloadClasses['SmartWikiAssociation']			= $dir . 'includes/model/SmartWikiAssociation.class.php';
$wgAutoloadClasses['SmartWikiAssociationClass']		= $dir . 'includes/model/SmartWikiAssociationClass.class.php';
$wgAutoloadClasses['SmartWikiAttribute']			= $dir . 'includes/model/SmartWikiAttribute.class.php';
$wgAutoloadClasses['SmartWikiClass']				= $dir . 'includes/model/SmartWikiClass.class.php';
$wgAutoloadClasses['SmartWikiEnumeration']			= $dir . 'includes/model/SmartWikiEnumeration.class.php';
$wgAutoloadClasses['SmartWikiGeneralization']		= $dir . 'includes/model/SmartWikiGeneralization.class.php';
$wgAutoloadClasses['SmartWikiModel']				= $dir . 'includes/model/SmartWikiModel.class.php';
$wgAutoloadClasses['SmartWikiModelElement']			= $dir . 'includes/model/SmartWikiModelElement.class.php';
$wgAutoloadClasses['SmartWikiPackage']				= $dir . 'includes/model/SmartWikiPackage.class.php';

# Type files
$wgAutoloadClasses['SmartWikiBoolean']				= $dir . 'includes/type/SmartWikiBoolean.class.php';
$wgAutoloadClasses['SmartWikiNumber']				= $dir . 'includes/type/SmartWikiNumber.class.php';
$wgAutoloadClasses['SmartWikiState']				= $dir . 'includes/type/SmartWikiState.class.php';
$wgAutoloadClasses['SmartWikiString']				= $dir . 'includes/type/SmartWikiString.class.php';

# SmartWiki Hooks
$wgAutoloadClasses['SmartWikiHooks']				= $dir . 'includes/SmartWiki.hooks.php';
$wgHooks['ParserFirstCallInit'][] 				= 'SmartWikiHooks::registerFunctions';
$wgHooks['LanguageGetMagic'][] 					= 'SmartWikiHooks::languageGetMagic';
$wgHooks['sfEditFormPreloadText'][]				= 'SmartWikiHooks::smwSmartWikiHook';
$wgHooks['EditPage::attemptSave'][]				= 'SmartWikiHooks::smwValidate';
$wgHooks['BeforePageDisplay'][] 				= 'SmartWikiHooks::wfJavaScriptAddModules';

# Other files
$wgExtensionMessagesFiles['SmartWiki']			= $dir . 'includes/SmartWiki.i18n.php';
$wgExtensionAliasesFiles['SmartWiki']			= $dir . 'includes/SmartWiki.alias.php';

# Fix the "Edit with form" button
$sfgRenameEditTabs = true;

# Autocomplete on every char
$sfgAutocompleteOnAllChars = true;
