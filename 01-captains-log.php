<?php
/*
    Plugin Name: Captain's Log
    Plugin URI: http://HoverDroids.com/
    Description:  A simple plugin for debugging stuff
    Author: Chris Sprague | HoverDroids
    Version: 1.0.0 
    Author URI: http://HoverDroids.com/
	Option Name: captains_log
	Enable On All Pages: all
*/ 
if ( ! defined( 'ABSPATH' ) ) { exit; }

//Load the core functionality that is shared between all plugins that derive from this plugin. This allows us to update core functionality separately from
//extension functionality - which is good since our intention is have extensions become similar to WP plugins but with the added benefit of aggregated css,js, and http requests
include( plugin_dir_path( __FILE__ ) . 'functions.php');

//Allow the plugin to be loaded based on admin/front end/or specific admin page
//FYI the current_screen hook is not called on the front end
$load_now = do_load_redux_plugin(__FILE__);
if($load_now === '') {
	function to_load_or_not_to_load_captains_log(){
		//Recheck loading status now that we have screen info - only necessary when our headers indicated the plugin should only load on a specific admin page
		if(!do_load_redux_plugin(__FILE__)){
			return;
		}
		init_captains_log();
	}
	
	//Add the action hook to execute in the function after we have screen info
	add_action( 'current_screen', 'to_load_or_not_to_load_captains_log' );
	
	//REMINDER The following are useful action hooks for styles		
	//'redux/page/captains_log/enqueue'							Triggered by redux framework for enqueing plugin scripts and styles.
	//																This is the best way to append css to Redux according to https://docs.reduxframework.com/core/advanced/custom-panel-css/ 
	//'admin_print_scripts-toplevel_page_captains_log_settings'	Triggers for the plugin page styles in particularI believe this is triggered by WP. The page name is set by Redux.

}else if(!$load_now){
	//We only want to load this plugin or its extensions on the plugin's admin page. So, do nothing since we're not on it.
	return;
}else{
	init_captains_log();
}

//Only load plugin stuff on desired pages
function init_captains_log(){

	function enqueue_captains_log_styles_scripts($hook) {

		if(is_admin()){
			//Always enqueue the style with the plugin's iconography so the icon shows in the menu on all pages. But, it's useless on the front end
			$style_name = 'plugin-style.css';
			wp_enqueue_style('captains_log_enqueue_style', plugins_url( '/admin/assets/css/'.$style_name, __FILE__ ));
		}
		
		//The Plugin JS is only useful for this plugin so don't load it anywhere except this admin page
		if("toplevel_page_" . opt_name( __FILE__ ) . "_settings" === $hook ){			
			wp_enqueue_script('enqueue_captains_log_script', plugins_url( '/admin/assets/js/admin.js', __FILE__ ), array( 'jquery' ));
			
			//Pass the image urls for our header, from PHP to JS			
			wp_localize_script('enqueue_captains_log_script', 'captains_log_script', array(
				'headerUrl' => 'http://HoverDroids.com',
				'headerImgLeftUrl' => plugins_url( '/admin/assets/img/Header-Left.png', __FILE__ ),
				'headerImgRightUrl' => plugins_url( '/admin/assets/img/Header-Right.png', __FILE__ ),	
				'altL' => 'captains_log Admin Panel',
				'altR' => 'captains_log Admin Panel'
			));	
		}
		do_action('enqueue_redux_' . opt_name(__FILE__) . '_extension_styles_scripts', $hook); 
	}

	function enqueue_captains_log_styles_scripts_front($hook) {
		//No need to check if scripts/styles are allowed on front end by page because redux page is never on front end
		do_action('enqueue_redux_' . opt_name(__FILE__) . '_extension_styles_scripts_front_end', $hook);	
	}
	
	/*
	*	This will load the framework on the front end and backend. The extension field code (including cs and js) will only ever be loaded on pages that use that field.
	*	The plugin and extension css and js loading is dependent on settings such as user role, page, etc.
	*
	*	The Admin Panel itself is not loaded except on the plugin page.
	*	
	*	Functions are always loaded, except when "Enable On All Pages" is false, because it includes many useful functions for retrieving the settings that are created by
	*	the Admin panel.
	*
	*	Extensions are not loaded if they are not in the extensions folder - e.g. if they're in the extensions-disabled folder.
	*
	*	This must be called after the options retrieval functions are added so that the extensions can use the functions
	*/

	include( plugin_dir_path( __FILE__ ) . 'admin/admin-init.php');
	
	add_action( 'admin_enqueue_scripts', 'enqueue_captains_log_styles_scripts' );
	add_action( 'wp_enqueue_scripts', 'enqueue_captains_log_styles_scripts_front' ); 
}
?>