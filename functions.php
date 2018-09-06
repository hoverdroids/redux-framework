<?php
/* 
* -------------------------------------------------------------------------------------------------------------------------------------------
*	Only add generic functions that can be used by any extension to this file. Otherwise, add the function to the extension's main file. 
* -------------------------------------------------------------------------------------------------------------------------------------------
*/
if ( ! defined( 'ABSPATH' ) ) { exit; }
if(!function_exists('is_windows')){
	/*
	*	Let's assume that if one function has been created already then all have been. This simplifies and clarfies our code - and is essential since we know
	*	that every plugin derived from this plugin will call this file by design.
	*/

	/* --------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	/*-----------------------------------------------                       Paths, URLs, Files, OSs                         -----------------------------------------------*/
	/* --------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	function is_windows(){
		global $is_IIS;
		return $is_IIS || strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' ? true : false;    
	}

	/* Get the full and normalized filepath from the relative path provided. Any file within the plugin can be used as it is only used to obtain the plugin base path */
	function rel_norm_path($file, $rel_path){
		return wp_normalize_path( pluginPath($file).$rel_path );//Can't use plugin_dir_path(__FILE__) because functions.php can be used by different plugins 
	}

	/* Get the full and normalized filepath from the relative path provided. Any file within the plugin can be used as it is only used to obtain the plugin base path */
	function rel_norm_url($file, $rel_url){
		return wp_normalize_path( trailingslashit(pluginUrl($file)) . $rel_url );//Can't use plugin_dir_url(__FILE__) because functions.php can be used by different plugins
	}

	/* */
	function get_minified_file($use_minified, $filename, $ext){
		return $use_minified && file_exists($filename.'.min.'.$ext) ? $filename.'.min.'.$ext : $filename.'.'.$ext;
	}

	/* The path of the plugin that contains the file */
	function pluginPath($file){	
		return trailingslashit(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . pluginFolderName($file));
	}

	/* The url of the plugin that contains the file */
	function pluginUrl($file){
		return plugins_url() . DIRECTORY_SEPARATOR . pluginFolderName($file);
	}

	/* 
		The path of the main plugin file, of the plugin that contains the file
		This only works if the plugin file with headers has the same name as the plugin folder
	*/
	function pluginFilePath($file){	
		$plugin = pluginFolderName($file);
		return trailingslashit(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin . DIRECTORY_SEPARATOR ) . $plugin . ".php";
	}

	/* 
		The url of the main plugin file, of the plugin that contains the file
		This only works if the plugin file with headers has the same name as the plugin folder
	*/
	function pluginFileUrl($file){
		$plugin = pluginFolderName($file);
		return plugins_url() . DIRECTORY_SEPARATOR . $plugin . DIRECTORY_SEPARATOR . $plugin . ".php";
	}

	/* The name of the plugin folder that contains the file */
	function pluginFolderName($file){
		//Do not use dirname as we don't know how many levels to go to reach plugins folder
		$basename = plugin_basename( $file );
		$folder = explode('/', $basename);
		return $folder[0];
	}

	function redux_extensions_dir($file){
		return rel_norm_path( $file, 'admin\redux-extensions\extensions' );	
	}

	function redux_extensions_url($file){
		return rel_norm_url( $file, 'admin\redux-extensions\extensions' );	
	}

	function redux_extensions_disabled_dir($file){
		return rel_norm_path( $file, 'admin\redux-extensions\extensions-disabled' );	
	}

	function redux_extensions_disabled_url($file){
		return rel_norm_url( $file, 'admin\redux-extensions\extensions-disabled' );	
	}

	function redux_assets_dir($file){
		return rel_norm_path( $file, 'admin\assets' );
	}

	function redux_assets_url($file){
		return rel_norm_url( $file, 'admin\assets' );	
	}

	/*
		All of the following are taken from the headers in the plugin, if the headers are set.
		The follwing calls to $plugin_data will return empty if these value are not provided in the plugin's header.
		This only works if the the plugin file with headers has the same name as the plugin folder
	*/
	function pluginName($file){
		$plugin_data = get_plugin_data( pluginFilePath($file) );
		return $plugin_data['Name'];
	}

	function pluginTitle($file){
		$plugin_data = get_plugin_data( pluginFilePath($file) );
		return $plugin_data['Title'];
	}

	function pluginDescription($file){
		$plugin_data = get_plugin_data( pluginFilePath($file) );
		return $plugin_data['Description'];
	}

	function pluginAuthor($file){
		$plugin_data = get_plugin_data( pluginFilePath($file) );
		return $plugin_data['Author'];
	}

	function pluginAuthorURI($file){
		$plugin_data = get_plugin_data( pluginFilePath($file) );
		return $plugin_data['AuthorURI'];
	}

	function pluginVersion($file){
		$plugin_data = get_plugin_data( pluginFilePath($file) );
		return $plugin_data['Version'];
	}

	function pluginURI($file){
		$plugin_data = get_plugin_data( pluginFilePath($file) );
		return $plugin_data['PluginURI'];
	}

	function pluginTextDomain($file){
		$plugin_data = get_plugin_data( pluginFilePath($file) );
		return $plugin_data['TextDomain'];
	}

	function pluginDomainPath($file){
		$plugin_data = get_plugin_data( pluginFilePath($file) );
		return $plugin_data['DomainPath'];
	}

	function pluginNetwork($file){
		$plugin_data = get_plugin_data( pluginFilePath($file) );
		return $plugin_data['Network'];
	}

	function rsearch($folder, $pattern) {
		$iti = new RecursiveDirectoryIterator($folder);
		foreach(new RecursiveIteratorIterator($iti) as $file){
			 if(strpos($file , $pattern) !== false){
				return $file;
			 }
		}
		return "";
	}

	/* Return the default WordPress plugin headers */
	function wp_plugin_headers(){
		return array(
			'Name'        => 'Extension Name',
			'PluginURI'   => 'Extension URI',
			'Version'     => 'Version',
			'Description' => 'Description',
			'Author'      => 'Author',
			'AuthorURI'   => 'Author URI',
			'TextDomain'  => 'Text Domain',
			'DomainPath'  => 'Domain Path',
			'Network'     => 'Network',
			// Site Wide Only is deprecated in favor of Network.
			'_sitewide'   => 'Site Wide Only',
		);
	}

	function get_custom_header_info($file){
		$heads = array(
			'Option Name'        => 'Option Name',
			'Enable On All Pages'   => 'Enable On All Pages',
		);
		return get_file_data(pluginFilePath($file), $heads);  
	}

	function opt_name($file){
		$heads = array(
			'Option Name'        => 'Option Name',
		);
		$opt = get_file_data(pluginFilePath($file), $heads);  
		
		return !empty($opt) ? $opt['Option Name'] : '';
	}

	function opt_name_upper($file){
		$opt_name = opt_name($file);
		return empty($opt_name) ? 'NO_OPT_NAME' : strtoupper($opt_name);
	}

	function is_enabled_all_pages($file){
		$heads = array(
			'Enable On All Pages'   => 'Enable On All Pages',
		);
		$opt = get_file_data(pluginFilePath($file), $heads);  
		
		return !empty($opt) ? $opt['Enable On All Pages'] : ''; 
	}

	function is_current_redux_opt_name_page($file){
		$heads = get_custom_header_info($file);
		$current_screen = get_current_screen();
		if(!empty($heads) && $current_screen ->id === "toplevel_page_".$heads["Option Name"]."_settings" ) {
			return true;
		}
		return false;
	}

	function do_load_redux_plugin($file){
		global $current_screen;	
		$admin = is_admin();
		
		//$heads['Enable On All Pages'] => 'all' or 'front' or 'back' or 'self'
		$heads = get_custom_header_info($file);
		$hdNotEmp = !empty($heads) && !empty($heads['Enable On All Pages']);
		$p = $hdNotEmp ? $heads['Enable On All Pages'] : 'all';
		
		$admin1 = $admin && ($p === 'all' || $p === 'back');
		$front = !$admin && ($p === 'all' || $p === 'front');
		
		$currScrnLoaded = $admin && !empty( $current_screen ) && !empty( $current_screen ->id ) ? true : false;
		
		if( $front || $admin1 ){
			return true;
		} elseif ( $currScrnLoaded ){		
			//Plugin always loads on its own page. So, detect if it's a different page and don't load if loading is not enabled on all pages
			if( $hdNotEmp && $p === 'self' && $current_screen ->id !== "toplevel_page_" . $heads["Option Name"] . "_settings") { 
				return false;
			}
			//It's ok to load on all pages - most likely because "enable on all pages" is true or headers did not specify
			return true;
		}else{
			//Not sure what to do since it was in admin and should only load on the current page but don't have the screen info yet,
			//so we don't know what page we're on.
			return '';
		}	
	}

	function get_redux_extensions_all($file){
		$en_exts = get_redux_extensions($file, true);
		$dis_exts = get_redux_extensions($file, false); 
		return array_merge($en_exts, $dis_exts);
	}

	function get_redux_extensions($file, $enabled){
		
		$dir = $enabled ? redux_extensions_dir($file) : redux_extensions_disabled_dir($file);
		$state = $enabled ? 'enabled' : 'disabled';
		
		//This will be an array of arrays where the key is the extension name and the value is an array of extension info
		$extens = array();
		
		//First look at the enabled extensions - and move the folder to the disabled folder if the option is not enabled (this means the disable operation started but didn't finsih)
		if(file_exists($dir)){
			$extensions = scandir($dir);
			foreach($extensions as $extension) {
				//This check is required because folders called . and .. are returned and it also ignores files. ie only act on real folders
				if( is_dir( $dir . DS . $extension ) && $extension != '.' && $extension != '..') {				
					$ext_file = $dir . DS . $extension . DS . 'extension_'.$extension . '.php';
					
					//Get extension headers
					$ext_heads = get_file_data($ext_file, wp_plugin_headers());
					
					//Add other info to the headers if needed
					$ext_heads = array_merge($ext_heads, array("id" => $extension, "State" => $state));	//TODO this should based on the folder			
					array_push($extens, $ext_heads);
				}
			}
		}	
		return $extens;
	}

	function load_redux_extension_option_files($file){
		return load_redux_extension_files($file, 'options.php');
	}

	function load_redux_extension_activation_files($file){
		return load_redux_extension_files($file, 'activation.php');
	}

	function load_redux_extension_deactivation_files($file){
		return load_redux_extension_files($file, 'deactivation.php');
	}
	 
	function load_redux_extension_files( $file, $suffix ){

		$dir = redux_extensions_dir( $file );
		$enabled = true; //TODO this needs to be passed!
		$state = $enabled ? 'enabled' : 'disabled';//TODO indicates if we should scan the enabled or disabled folder

		$extens = array();
		if(file_exists($dir)){
			$extensions = scandir($dir);
			foreach($extensions as $extension) {
				//This check is required because folders called . and .. are returned and it also ignores files. ie only act on real folders
				if( is_dir( $dir . DS . $extension ) && $extension != '.' && $extension != '..') {					
					//If the extension has an options file then add it now so that the options can be enqueued for the next step
					$ext_file = $dir . DS . $extension . DS . 'extension_'.$extension . '.php';
					$f = $dir . DS . $extension . DS . $extension . '-' . $suffix;				

					//Get extension headers
					$ext_heads = get_file_data($ext_file, wp_plugin_headers());
					
					//Add other info to the headers if needed
					$ext_heads = array_merge($ext_heads, array("id" => $extension, "State" => $state));	//TODO this should based on the folder			
					array_push($extens, $ext_heads);
					
					if(file_exists($f)){	
						include( $f );
					}
				}
			}
		}		
		return $extens;
	}

	/* --------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	/* ---------------------------------------------                     Upload and Download                  -------------------------------------------------------------*/
	/* --------------------------------------------------------------------------------------------------------------------------------------------------------------------*/

	//This is set in wp-config by default. Modify there for a permanent change.
	//This update is removed when the plugin is deactivated
	/*
	require_once(ABSPATH . 'wp-settings.php');//Required to set uploads folder
	define( 'UPLOADS', 'wp-content/whatever' );
	/*

	/* --------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	/* ---------------------------------------------         Url Dir and Path Modifications Functions         -------------------------------------------------------------*/
	/* --------------------------------------------------------------------------------------------------------------------------------------------------------------------*/






	/* --------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	/* ---------------------------------------------                            Other                         -------------------------------------------------------------*/
	/* --------------------------------------------------------------------------------------------------------------------------------------------------------------------*/

	/*
	*	Determines if the current page is made from an AJAX call. This methods supposedly works even with WooCommerce AJAX and is hence better than:
	*	defined('DOING_AJAX') && DOING_AJAX;
	*/
	function is_ajax_page(){
		return ( ! empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ]) == 'xmlhttprequest' ) ? true : false;
	}

	/*
	*	admin-ajax.php is not avilable on the front end by default. If you want to make ajax calls on the front end, using the typical wordpress 
	*	methods then call this when loading the front end page.
	*
	*	This is meant to be used as follows:
	*	add_action('wp_head', 'enable_front_end_ajaxurl');
	*/
	function enable_front_end_ajaxurl() {
		echo '<script type="text/javascript"> var ajaxurl = "' . admin_url('admin-ajax.php') . '"; </script>';
	}

	function is_user_allowed($allowed_roles, $allowed_roles_restricted){
		global $current_user;
				
		//TODO, also base it on the page if you really want
		
		$user_roles = $current_user -> roles;			
		$user_allowed = array_intersect($allowed_roles, $user_roles);
		$user_restricted = array_intersect($allowed_roles_restricted, $user_roles);
		
		return $user_allowed ? 2 : ($user_restricted ? 1 : 0);
	}

	function array_filter_key( $input, $callback ) {
		if ( !is_array( $input ) ) {
			trigger_error( 'array_filter_key() expects parameter 1 to be array, ' . gettype( $input ) . ' given', E_USER_WARNING );
			return null;
		}
		
		if ( empty( $input ) ) {
			return $input;
		}
		
		$filteredKeys = array_filter( array_keys( $input ), $callback );
		if ( empty( $filteredKeys ) ) {
			return array();
		}
		
		$input = array_intersect_key( array_flip( $filteredKeys ), $input );
		
		return $input;
	}

	function get_all_keys_($a, $prefix){
		$array = array_filter_key($a, function($key) {
			return strpos($key, $prefix) === 0;
		});
		return $array;
	}

	function get_keys_value_pairs($a, $prefix){
		$keys = array();
		foreach ($a as $key => $value) {
			if (strpos($key, $prefix) === 0) {
				$keys[$key] = $value;
			}
		}
		return $keys;
	}

	//TODO ENABLE/DISABLE EXTENSION HOOKS

	/* --------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	/*-----------------------------------------------            Options Retrieval                                          -----------------------------------------------*/
	/* --------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	/* 
	*	Description		Options can have states like 'on' 'off' and 'global' and this function returns the option value based on the state.
	*					hasNoGlobalState is used in the global settings to indicate that it is the highest possible global setting - ie that
	*					the option is currently the one defined as 'global' by dependent settings
	*
	*					$subsection_name is the child tab of the default tab. For example, the WebGL Plugin has a game settings tab and each game
	*					has a separate tab that is a child to the game settings tab. The game settings tab holds the "global" settings that can be
	*					used instead of having local settings for each game.
	*
	*					$onlyHasLocalState is true if there is no global setting for it; only a local one. Like a game's name.
	*
	*					$off_vals indicates what should be returned if the state is off.
	*
	*					Pass an empty string - e.g. '' - for the global tab name
	*/
	//TODO...this actually might not currently work for retrieving the "global" tab since that was not its intended purpose. I had assumed that this would be called
	//by a game that defers to the global settings when local settings are off. So, if no local settings are on or off or global, how will this behave?
	/*
		Example Usage:
		$gameId = getOptionsFromState($game ,'', array('id'), substr(md5(rand()), 0, 10), 0, 1);
		$gameName = getOptionsFromState($game ,'', array('title'), '', 0, 1);
		
		$aspectRatio = getOptionsFromState($game ,'width_height_ratio', array('width_height_ratio'), '', 1, 0);
		$aspectRatio = ! empty( $aspectRatio ) && ! empty( $aspectRatio['0'] ) ? explode(":", $aspectRatio['0']) : array('16','9'); 
		$aspectRatio =  ((float)$aspectRatio[1]/(float)$aspectRatio[0]) * 100.0;
		
		$loadingAlign = getOptionsFromState($game,'loading_align',array('loading_horz_align','loading_vert_align'), '', 1, 0);
		$loadAlignHorz = '4';
		$loadAlignVert = '4';
		if($loadingAlign != ''){
			$loadAlignHorz = $loadingAlign['0'];
			$loadAlignVert = $loadingAlign['1'];
		}	
		$loadAlignHorz = 'load-align-horz-'.$loadAlignHorz;
		$loadAlignVert = 'load-align-vert-'.$loadAlignVert;
		
		$spinnerImg = getOptionsFromState($game,'spinner', array('spinner'), 'OFF', 0, 0);
		$spinnerImg = getImageUrl($spinnerImg, 'spinner','');
		
		$loadingBar = getOptionsFromState($game,'loadingbar', array('loadingbar_fg_color','loadingbar_bg_color','loadingbar_loading_text','loadingbar_preparing_text','loadingbar_text_color'), '', 0, 0);
		$loadDisp = 'none';
		if($loadingBar != ''){
			$loadingBarFg = $loadingBar['0'];
			$loadingBarBg = $loadingBar['1'];
			$loadingBarText = $loadingBar['2'];
			$loadingBarText2 = $loadingBar['3'];
			$loadingBarTextColor = $loadingBar['4'];
			$loadDisp = 'inherit';
		}

	*///don't forget...we just added $opt_name
	function get_redux_options($opt_name, $section_name, $state, $opts, $off_vals, $hasNoGlobalState, $onlyHasLocalState){	
		//Get the global var that Redux Framework declares for our option. This assumes the global settings use the default naming convention
		$opt_settings = $opt_name . '_settings';
		global $$opt_settings; 
		
		//Make it more readable
		$opt_settings = $$opt_settings;
		
		//If the section or subsection name has spaces, remove them from the options; this allows section names to be displayed with spaces in admin without breaking the admin options - which doesn't like the spaces
		$name_rewrite = str_replace(' ', '', $section_name);
		
		//TODO we should not have to know the options. We should only need to know the main option name. That would return us the opts.
		//I actually still think that we should not have to know the options. Instead, why don't we get all the section options using the section name. Then, if we
		//need to determine hierarchy with the state option, we use the state option! Also, rather than using numberic keys, use the key as the key! This way we are not
		//dependent on knowing the order and can simply use issset
		
		//e.g. #1 $section_use_key = $opt_name . '_' . $ext_name . '_' . 'instructions';
		//e.g. #2 $section_key . '_' . 'poll_interval'
		//Both of the above just use the section key. #2 adds the exact value we want to get
		//get all instructions
		//we should also be able to get just one instruction
		$opts_all_with_pre = get_all_keys($opt_settings, $prefix);//What is the prefix we're looking for? I think section.field_name
		
		//TODO now we can determine if there is even a state for the given options that we are looking for
		//Note that we don't just want section data because we are ultimately trying to find the values based on the state of the section's value.
		//ie on a single game section we have the width_height_ratio state; it has one dependent, that of the width_height_ratio text field.
		//If we look for width_height_ratio prefix then it will return two value, one with _state suffix and another with nothing (ie nothing after width_height_ratio)
		
		//TODO now that we will likely change the settings so that the default tab has a name, we will need to understand how to point to the default tab - likely it's just
		//section_subsection ex. unity_game_def_game_name
		
		
		$os = array();
		foreach($opts as $key => $opt) {
			$os[$key] = $opt_name.'_'.$name_rewrite.'_'.$opt;
		}
		
		if($onlyHasLocalState){
			//This means only one option since state is required for sibling option values
			$o = $opt_name.'_'.$name_rewrite.'_'.$opts['0'];
			if( !empty($speedy_dev_settings[$o])){
				return $speedy_dev_settings[$o];
			}else{
				return $off_vals;
			}	
		}
		
		$s = $opt_name.'_'.$name_rewrite.'_'.$state.'_state';	
		if(isset($speedy_dev_settings[$s]) && !empty($speedy_dev_settings[$s]) && $speedy_dev_settings[$s] != '3'){
			if($speedy_dev_settings[$s] == '1'){			
				foreach($os as $key => $opt) {
					$os[$key] = $speedy_dev_settings[$opt];
				}			
				return $os;
			}else{
				return $off_vals;
			}
		}
		
		$os = array();
		foreach($opts as $key => $opt) {
			$os[$key] = $opt_name.'_'.$opt;
		}
		
		$s = $opt_name.'_'.$state.'_state';
		if($hasNoGlobalState || (isset($speedy_dev_settings[$s]) && !empty($speedy_dev_settings[$s]))){
			if($hasNoGlobalState || $speedy_dev_settings[$s] == '1'){
				foreach($os as $key => $opt) {
					$os[$key] = $speedy_dev_settings[$opt];
				}						
				return $os;			
			}else{
				return $off_vals;
			}
		}
		return '';
	}
	/*
	*	Description		Retrieve the image from the option. 
	*					$ad = getOptionsFromState($game,'ad',array('ad','ad_link','ad_shortcode'), '', 0, 0);
	*					$adImg = getImageUrl($ad, 'ad', '');
	*/
	function getImageUrl($imgOption, $defImageName, $transImageName){	
		//TODO we can also assume that the image image we are looking for has the pattern of 'curr-opt-name-img' instead of assuming it is the first value
		//or we can detect if it has an url?
		if (!empty($imgOption) && !is_array($imgOption) && !($imgOption instanceof Traversable) && strcmp($imgOption, 'OFF') == 0){
			return '';
		}
		
		if(!empty($imgOption) && !empty($imgOption['0']) && !empty($imgOption['0']['url'])){
			//Assume that an image option always has the image url first
			return $imgOption['0']['url'];
		}else{
			//Look to see if there is a default image of the given name in the template directory
			$exts = array('.gif','.png','.jpg');
			$imgName = !empty($imgOption) && !is_array($imgOption) && !($imgOption instanceof Traversable) && strcmp($imgOption, 'OFF-TRANS') == 0 ? $transImageName : $defImageName;
			foreach ($exts as $ext){
				if(file_exists(PLUGIN_TEMPLATE_DIR.DS.$imgName.$ext)){
					return PLUGIN_TEMPLATE_DIR_URL.DSURL.$imgName.$ext;
				}				
			}
			return '';
		}
	}




	/* --------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	/* --------------------------------------                           Type Conversions                              -----------------------------------------------------*/
	/* --------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	function toTrueFalse($str){
		return $str === 'true' ? true : false;
	}












	/* --------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	/* --------------------------------------           Constants are last in case they rely on functions above       -----------------------------------------------------*/
	/* --------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	DEFINE('DS', is_windows() ? '/' : DIRECTORY_SEPARATOR);
	DEFINE('DSURL', '/'); 
	DEFINE('RELEASE_SUFFIX', '');		
}									