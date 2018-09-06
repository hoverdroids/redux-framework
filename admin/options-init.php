<?php
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'Redux' )) exit; //Exit if accessed directly or Redux didn't load

/*
	init_captains_log_settings is called after the declaration of all functions
*/	
function init_captains_log_settings() {
	$opt_name = opt_name( __FILE__ );	
	$domain = $opt_name;

	// Set the default arguments
	set_captains_log_arguments($opt_name, $domain);

	//add_captains_log_help_tabs();
	
	if(is_admin()){
		//There should not be any reason to load options files on front end since they're only used in admin			
		load_redux_extension_option_files( __FILE__ ); //Include option files from extensions			
		add_captains_log_custom_sections($opt_name, $domain);//Add big sections first
		add_captains_log_default_section($opt_name, $domain);//Then add fields that want to attach to the "other loose ends" section and subsection
	}		
}

function set_captains_log_arguments($opt_name, $domain){
	/**
	 * ---> SET ARGUMENTS
	 * All the possible arguments for Redux.
	 * For full documentation on arguments, please refer to: https://github.com/ReduxFramework/ReduxFramework/wiki/Arguments
	 * */

	$args = array(
		'opt_name' => $opt_name,
		'use_cdn' => TRUE,
		'display_name' => 'YOUR DISPLAY NAME',
		'display_version' => FALSE,
		'page_slug' => $opt_name.'_settings',
		'page_title' => 'YOUR PAGE TITLE',
		'update_notice' => TRUE,
		'intro_text' => 'YOUR INTRO TEXT',
		'footer_text' => 'Plugin developed by YOUR NAME | YOUR COMPANY NAME | <a href="http://YOUR_WEBSITE.COM/PLUGIN_PAGE">YOUR COMPANY NAME</a>',
		'menu_type' => 'menu',
		'menu_title' => 'YOUR MENU TITLE',
		'menu_icon' =>  '',//can leave blank and override in css with custom font icon, or use icon name
		'page_icon' => '',
		'allow_sub_menu' => TRUE,
		'page_parent_post_type' => $opt_name,//Not sure what this does actually :p
		'customizer' => TRUE,
		'default_show' => FALSE,
		'default_mark' => '',
		'class' => $opt_name . '-container',
		'enqueue_frontend' => true,
		'enqueue' => true,
		'hints' => array(
			'icon_position' => 'right',
			'icon_color' => 'lightgray',
			'icon_size' => 'normal',
			'tip_style' => array(
				'color' => 'light',
			),
			'tip_position' => array(
				'my' => 'top left',
				'at' => 'bottom right',
			),
			'tip_effect' => array(
				'show' => array(
					'duration' => '500',
					'event' => 'mouseover',
				),
				'hide' => array(
					'duration' => '500',
					'event' => 'mouseleave unfocus',
				),
			),
		),
		'output' => TRUE,
		'output_tag' => TRUE,
		'settings_api' => TRUE,
		'cdn_check_time' => '1440',
		'compiler' => TRUE,
		'page_permissions' => 'manage_options',
		'save_defaults' => TRUE,
		'show_import_export' => TRUE,
		'database' => 'options',
		'transient_time' => '3600',
		'network_sites' => TRUE,
		'dev_mode' => false,
		'global_variable' => $opt_name.'_settings', // Set a different name for your global variable other than the opt_name	
		'admin_bar' => FALSE,		
	);

	// SOCIAL ICONS -> Setup custom links in the footer for quick links in your panel footer icons.
	//$args['share_icons'][] = array(
		//'url'   => 'https://github.com/ReduxFramework/ReduxFramework',
	   // 'title' => 'Visit us on GitHub',
		//'icon'  => 'el el-github'
		//'img'   => '', // You can use icon OR img. IMG needs to be a full URL.
	//);
	$args['share_icons'][] = array(
		'url'   => 'https://www.facebook.com/hoverdroids',
		'title' => 'Like us on Facebook',
		'icon'  => 'el el-facebook'
	);
	$args['share_icons'][] = array(
		'url'   => 'https://twitter.com/hoverdroids',
		'title' => 'Follow us on Twitter',
		'icon'  => 'el el-twitter'
	);
	$args['share_icons'][] = array(
		'url'   => 'https://www.linkedin.com/in/christopher-sprague-25321945/',
		'title' => 'Find us on LinkedIn',
		'icon'  => 'el el-linkedin'
	);
	$args['share_icons'][] = array(
		'url'   => 'https://plus.google.com/u/0/106545163997618735476',
		'title' => 'Find us on Google+',
		'icon'  => 'el el-googleplus'
	);
	$args['share_icons'][] = array(
		'url'   => 'https://www.youtube.com/channel/UCI5SX_Mf2n95ajltf1kquyQ',
		'title' => 'Find us on YouTube',
		'icon'  => 'el el-youtube'
	);
	$args['share_icons'][] = array(
		'url'   => 'https://www.pinterest.com/hoverdroids/',
		'title' => 'Find us on Pinterest',
		'icon'  => 'el el-pinterest'
	);
	$args['share_icons'][] = array(
		'url'   => 'https://www.instagram.com/hoverdroids/',
		'title' => 'Find us on Instagram',
		'icon'  => 'el el-instagram'
	);
	Redux::setArgs( $opt_name, $args );	
}

function add_captains_log_help_tabs($opt_name){
	$tabs = array(
		array(
			'id'      => 'redux-help-tab-1',
			'title'   => __( 'Theme Information 1', 'admin_folder' ),
			'content' => __( '<p>This is the tab content, HTML is allowed.</p>', 'admin_folder' )
		),
		array(
			'id'      => 'redux-help-tab-2',
			'title'   => __( 'Theme Information 2', 'admin_folder' ),
			'content' => __( '<p>This is the tab content, HTML is allowed.</p>', 'admin_folder' )
		)
	);
	Redux::setHelpTab( $opt_name, $tabs );

	// Set the help sidebar
	$content = __( '<p>This is the sidebar content, HTML is allowed.</p>', 'admin_folder' );
	Redux::setHelpSidebar( $opt_name, $content );
}

function add_captains_log_default_section($opt_name, $domain){	
	$sect_name = 'other_functions';
	$section_key = $opt_name . '_' . $sect_name;
	
	//Get options for the default section from extensions that want to add their functionality here instead of a custom subsection
	$fields = array(array());
	$fields = apply_filters( 'redux_' . $opt_name . '_default_section_options', $fields, $section_key, $domain );
	
	$opts = array(
		'icon' => 'el-icon-cogs',
		'id' => $section_key,
		'title' => esc_html__('Other Functions', $domain),
		'fields' => $fields,
	); 

	if(!empty($fields)){
		Redux::setSection( $opt_name, $opts);
		
		//Get options for the default section from the extensions	
		$subs = array(array());
		$subsects = apply_filters( 'redux_' . $opt_name . '_default_subsection_options', $subs, $section_key, $domain );			

		foreach($subsects as $sub){
			Redux::setSection( $opt_name, $sub);			
		}	
	}	
}	

function add_captains_log_custom_sections($opt_name, $domain){
	//See if any extensions have custom sections they want to add
	$secs = array(array());
	$sections = apply_filters( 'redux_' . $opt_name . '_section_options', $secs , $opt_name, $domain );
	
	foreach($sections as $section){
		Redux::setSection( $opt_name, $section);				
	}
}
	
init_captains_log_settings();
?>