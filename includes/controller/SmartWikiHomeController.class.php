<?php
class SmartWikiHomeController {
	/**
	 * Constructor
	 */
	function __construct() {
		# TODO: Do something or remove
	}

	/**
	 * Display a default page with the action buttons
	 */
	public function execute() {
		global $wgOut;

		# Variable for the HTML codes
		$htmlOut = '';

		# Initialize
		$htmlOut .=
			Xml::tags( 'h2', null, wfMsgForContent('smartwiki-initialize-title') ) . 
			Xml::tags( 'p', null, wfMsgForContent('smartwiki-initialize-description') ) . 
			Xml::tags( 'p', NULL, 
				Xml::tags(
					'form',
					array(
						'name' => 'SmartWiki-initialize',
						'id' => 'SmartWiki-initialize',
						'class' => 'SmartWiki-initialize',
						'action' => '',
						'method' => 'get',
					),
					Xml::element(
						'input',
						array(
							'name' => 'action',
							'type' => 'hidden',
							'value' => 'initialize',
						)
					) . 
					Xml::tags( 'p', null, 
						Xml::element(
							'input',
							array(
								'name' => 'showInMenu',
								'type' => 'checkbox',
							)
						) . 
						wfMsgForContent('smartwiki-initialize-showinmenu')
					) . 
					Xml::tags( 'p', null,
						Xml::element(
							'input',
							array(
								'type' => 'submit',
								'value' => wfMsgForContent('smartwiki-initialize-button'),
							)
						)
					)
				)
			);

		# Upload
		$htmlOut .=
			Xml::tags( 'h2', null, wfMsgForContent('smartwiki-upload-title') ) . 
			Xml::tags( 'p', null, wfMsgForContent('smartwiki-upload-description') ) . 
			Xml::tags( 'p', NULL, 
				Xml::tags(
					'form',
					array(
						'name' => 'SmartWiki-upload',
						'id' => 'SmartWiki-upload',
						'class' => 'SmartWiki-upload',
						'action' => '',
						'method' => 'post',
						'enctype' => 'multipart/form-data',
					),
					Xml::element(
						'input',
						array(
							'name' => 'action',
							'type' => 'hidden',
							'value' => 'upload',
						)
					) . 
					Xml::tags( 'p', null,
						Xml::element(
							'input',
							array(
								'name' => 'xmiUploadFile',
								'type' => 'file',
								'size' => 30,
							)
						)
					) . 
					Xml::tags( 'p', null,
						Xml::element(
							'input',
							array(
								'type' => 'submit',
								'value' => wfMsgForContent('smartwiki-upload-button'),
							)
						)
					)
				)
			);

		# Parse
		$htmlOut .=
			Xml::tags( 'h2', null, wfMsgForContent('smartwiki-parse-title') ) . 
			Xml::tags( 'p', null, wfMsgForContent('smartwiki-parse-description') ) . 
			Xml::tags( 'p', NULL, 
				Xml::tags(
					'form',
					array(
						'name' => 'SmartWiki-parse',
						'id' => 'SmartWiki-parse',
						'class' => 'SmartWiki-parse',
						'action' => '',
						'method' => 'get',
					),
					Xml::element(
						'input',
						array(
							'name' => 'action',
							'type' => 'hidden',
							'value' => 'parse',
						)
					) . 
					Xml::tags( 'p', null,
						Xml::element(
							'input',
							array(
								'type' => 'submit',
								'value' => wfMsgForContent('smartwiki-parse-button'),
							)
						)
					)
				)
			);

		# Fill
		$htmlOut .=
			Xml::tags( 'h2', null, wfMsgForContent('smartwiki-fill-title') ) . 
			Xml::tags( 'p', null, wfMsgForContent('smartwiki-fill-description') ) . 
			Xml::tags( 'p', NULL, 
				Xml::tags(
					'form',
					array(
						'name' => 'SmartWiki-fill',
						'id' => 'SmartWiki-fill',
						'class' => 'SmartWiki-fill',
						'action' => '',
						'method' => 'get',
					),
					Xml::element(
						'input',
						array(
							'name' => 'action',
							'type' => 'hidden',
							'value' => 'fill',
						)
					) . 
					Xml::tags( 'p', null,
						Xml::element(
							'input',
							array(
								'type' => 'submit',
								'value' => wfMsgForContent('smartwiki-fill-button'),
							)
						)
					)
				)
			);

		# Create
		$htmlOut .=
			Xml::tags( 'h2', null, wfMsgForContent('smartwiki-create-title') ) . 
			Xml::tags( 'p', null, wfMsgForContent('smartwiki-create-description') ) . 
			Xml::tags( 'p', NULL, 
				Xml::tags(
					'form',
					array(
						'name' => 'SmartWiki-create',
						'id' => 'SmartWiki-create',
						'class' => 'SmartWiki-create',
						'action' => '',
						'method' => 'get',
					),
					Xml::element(
						'input',
						array(
							'name' => 'action',
							'type' => 'hidden',
							'value' => 'create',
						)
					) . 
					Xml::tags( 'p', null,
						Xml::element(
							'input',
							array(
								'type' => 'submit',
								'value' => wfMsgForContent('smartwiki-create-button'),
							)
						)
					)
				)
			);

		# Output the HTML codes
		$wgOut->addHTML( $htmlOut );
	}
}
