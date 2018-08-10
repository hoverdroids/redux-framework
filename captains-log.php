<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Captain's Log
 * Plugin URI:        https://playtcubed.com
 * Description:       Allows debbugging to console.log in browser console, even after the page is loaded! This is essential since <script>console.log('msg')</script> can't be called from php after the headers are sent. It also provides live readings of the php log file  - ie where the error_log() results end up. This is unlike other php logging solutions that require a page refresh to update result (ugh);
 * Version:           1.0.0
 * Author:            tCubed
 * Author URI:        https://playtcubed.com
 */

// If this file is called directly, abort.
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

$conlog_session_id = 0;
$random_page_id = rand(1,1000);

/*-----------------------------------------------------------------------------------------------------------------------*/
/*--------------------------------------          User Interface                      -----------------------------------*/
/*-----------------------------------------------------------------------------------------------------------------------*/

/*
 * Admin Menu top bar. Click "Console Logging" to toggle all loggers on/off. Click an individual logger to toggle it on/of. If any
 * logger is on then "Console Logging" will be highlighted. Otherwise not.
 */
function admin_bar_menu( $wp_admin_bar ) {

	$wp_admin_bar->add_menu( array(
		'title'		=> '<span class="ab-icon"></span><span class="ab-label">' . __( 'Captain\'s Log' , 'console-logging' ) . '</span>',
		'id'		=> 'conlog-main-menu',
		'parent'	=> false,
		'href'		=> '',
		'meta'		=> array('class' => 'is-admin-'.is_admin())//save admin stat here because it isn't correct when using admin-ajax (it always returns true)
	) );
	
	$wp_admin_bar->add_menu( array(
		'title'		=> __( 'console.log' , 'console-logging' ),
		'id'		=> 'conlog-main',
		'parent'	=> 'conlog-main-menu',
		'href'		=> ''
	) );

	$wp_admin_bar->add_menu( array(
		'title'		=> __( 'The Bridge' , 'console-logging' ),
		'id'		=> 'conlog-admin',
		'parent'	=> 'conlog-main',
		'href'		=> ''
	) );
	
	$wp_admin_bar->add_menu( array(
		'title'		=> __( 'Earth' , 'console-logging' ),
		'id'		=> 'conlog-front-end',
		'parent'	=> 'conlog-main',
		'href'		=> ''
	) );

	$wp_admin_bar->add_menu( array(
		'title'		=> __( 'CLEAR Logs' , 'console-logging' ),
		'id'		=> 'conlog-clear',
		'parent'	=> 'conlog-main',
		'href'		=> ''
	) );
/* TODO add the ability to view the error logs in the console window. Too much work for now though
	$wp_admin_bar->add_menu( array(
		'title'		=> __( 'error_log' , 'console-logging' ),
		'id'		=> 'errlog-main',
		'parent'	=> 'conlog-main-menu',
		'href'		=> ''
	) );

	$wp_admin_bar->add_menu( array(
		'title'		=> __( 'The Bridge' , 'console-logging' ),
		'id'		=> 'errlog-admin',
		'parent'	=> 'errlog-main',
		'href'		=> ''
	) );

	$wp_admin_bar->add_menu( array(
		'title'		=> __( 'Earth' , 'console-logging' ),
		'id'		=> 'errlog-front-end',
		'parent'	=> 'errlog-main',
		'href'		=> ''
	) );
	
	wp_admin_bar->add_menu( array(
		'title'		=> __( 'CLEAR Log' , 'console-logging' ),
		'id'		=> 'errlog-clear',
		'parent'	=> 'errlog-main',
		'href'		=> ''
	) );
*/
	$wp_admin_bar->add_menu( array(
		'title'		=> __( 'Meta Data' , 'console-logging' ),
		'id'		=> 'meta-main',
		'parent'	=> 'conlog-main-menu',
		'href'		=> ''
	) );
	
	$wp_admin_bar->add_menu( array(
		'title'		=> __( 'Star Date' , 'console-logging' ),
		'id'		=> 'meta-time-stamp',
		'parent'	=> 'meta-main',
		'href'		=> ''
	) );
	
	$wp_admin_bar->add_menu( array(
		'title'		=> __( 'RAND PageId' , 'console-logging' ),
		'id'		=> 'meta-page-id',
		'parent'	=> 'meta-main',
		'href'		=> ''
	) );
	
	$wp_admin_bar->add_menu( array(
		'title'		=> __( 'Is AJAX' , 'console-logging' ),
		'id'		=> 'meta-is-ajax',
		'parent'	=> 'meta-main',
		'href'		=> ''
	) );
	
	$wp_admin_bar->add_menu( array(
		'title'		=> __( 'Show JSON' , 'console-logging' ),
		'id'		=> 'meta-show-json',
		'parent'	=> 'meta-main',
		'href'		=> ''
	) );
	
	$wp_admin_bar->add_menu( array(
		'title'		=> __( 'Show JSON Row' , 'console-logging' ),
		'id'		=> 'meta-show-json-row',
		'parent'	=> 'meta-main',
		'href'		=> ''
	) );
	
	$wp_admin_bar->add_menu( array(
		'title'		=> __( 'Show JSON Meta' , 'console-logging' ),
		'id'		=> 'meta-show-json-meta',
		'parent'	=> 'meta-main',
		'href'		=> ''
	) );
}

/*-----------------------------------------------------------------------------------------------------------------------*/
/*--------------------------------------          console.log                         -----------------------------------*/
/*-----------------------------------------------------------------------------------------------------------------------*/

function init_console_log(){

	global $current_user;

	if(!get_option('console_log_settings')){
		//Initialize our settings for the first time with all logging disabled to avoid ajax calls without permission
		//poll refresh Time in ms, console.log.admin, console.log.front, error_log.admin, error_log.front
		update_option('console_log_settings',array(3000,0,0,0,0,0,0,0,0,0,1));
	}
	
	//DON'T clear the previous data because it could be new data that just hasn't been processed
	//Instead, if you want to ensure a clean cache then reset all
	//clear_tagged_rows_and_dead_sessions(get_current_user_id(), $conlog_session_id);
	
	$user_roles = $current_user -> roles;
	$allowed_roles = array('administrator');
	$user_allowed = array_intersect($allowed_roles, $user_roles);
 
	if($user_allowed){
		//Always load the script for admins but nobody else. This ensures that our visitors are not burdened by a script they'll never need
		//Admins can enable/disable the polling by toggling the menu options
		if(is_admin()){
			setup_console_log('admin_enqueue_scripts');
		}else{
			add_action('wp_head', 'enable_front_end_ajaxurl');//ajaxurl is not defined on the front end; so we must create it since this console logging method relies on ajax to work
			setup_console_log('wp_enqueue_scripts');
		}
	}
}
add_action( 'init', 'init_console_log' );//Must wait for wp_loaded to get the current user

function setup_console_log($script){

	add_action( 'admin_bar_menu', 'admin_bar_menu', 90 );
	add_action($script, 'enqueue_console_log_scripts');
	
	//This allows the javascript polling to periodically grab the latest writes to the console array
	add_action( "wp_ajax_get_console_logs", "get_console_logs");
	add_action( "wp_ajax_nopriv_get_console_logs", "get_console_logs");
	add_action( "wp_ajax_get_logging_settings", "get_logging_settings");
	add_action( "wp_ajax_nopriv_get_logging_settings", "get_logging_settings");
	add_action( "wp_ajax_set_logging_settings", "set_logging_settings");
	add_action( "wp_ajax_nopriv_set_logging_settings", "set_logging_settings");	
	
	add_action( "wp_ajax_clear_console_logs", "clear_console_logs");
	add_action( "wp_ajax_nopriv_clear_console_logs", "clear_console_logs");
	
	add_action( "wp_ajax_clear_error_log", "clear_error_log");
	add_action( "wp_ajax_nopriv_clear_error_log", "clear_error_log");
}

//Always add a button to the admin bar for toggling console log on the front end
function enqueue_console_log_scripts() {   
    wp_enqueue_script( 'console_log_js', plugin_dir_url( __FILE__ ) . '/js/console-log.js', array('jquery'), '1.0' );
	wp_enqueue_style( 'console_log_css', plugin_dir_url( __FILE__ ) . '/css/console-log.css' );
}

//TODO TODO TODO re-read all of the descriptions and ensure that they accurately reflect how consoleLog works, how to pass tags, strings, arrays, arrays of strings (including)
//with apostrohpes, how to use multiple sessions - ie, possibly wait for load if you're noticing issues because the session id =0 and then how to add into your ajax and read
//on your pages if you want to cusomize the log id or have multiple concurrent sessions
/*
*	Brief: 			Print to console.log() from PHP
*	Description: 	Print as many strings,arrays, objects, and other data types to console.log from PHP.
*					To use, just call consoleLog($data1, $data2, ... $dataN) and each dataI will be sent to console.log - note that
*					you can pass as many data as you want an this will still work.
*
*					This is very powerful as it shows the entire contents of objects and arrays that can be read inside of the browser console log.
* 					
*					A tag can be set by passing a string that has the prefix TAG- as one of the arguments. Everytime a string with the TAG- prefix is
*					detected, the tag is updated. This allows you to pass a tag that is applied to all data until it reaches another tag, which can then
*					be applied to all data after it.
*
*					Example:
*					consoleLog('TAG-FirstTag',$data,$data2,'TAG-SecTag,$data3); 
*					Result:
*						FirstTag '...data...'
*						FirstTag '...data2...'
*						SecTag   '...data3...' 
*
*					Example 2:
*					Say you want to use one tag and output different info when initially building the page and then when in AJAX, for a call to the same function
*					consoleLog('TAG-YourOptionalTag',
*						'log_ajax-0','A','B',
*						'log_main-0','c','d',
*						'log_main-1','e','f',
*						'log_ajax-1','g',h');
*
*					This is pretty complicated looking, but it is straight forward. First, log_ajax-0 sets everything that follows not to show when the page is being
*					created from an ajax call. So A and B would only show in the main page build. Then log_main-0 sets everything that follows not to show when the main
*					page is building. At this point logging is off for everything proceeds which is c and d. log_main-1 turns on logging in the main page build for e and
*					f. Finally, log_ajax-1 turns on logging during ajax and so g and h will show during main building and ajax.
*
*					To show this more clearly I would personally just call:
*					consoleLog('log_ajax-0','A','B');
*					consoleLog('log_ajax-0','log_main-0','c','d'); //Pointless since you're saying to never log c or d
*					consoleLog('log_ajax-0','e','f'); 
*					consoleLog('e','f'); //Logging in the main build and ajax are both on automatically - ie log_ajax-1 and log_main-1 - so no need to add it
*
*	NOTE:			The following situations break WordPress when this function is used:
*					* wp_get_attachment_url filter -> Media Upload keeps looping and can't upload a file
*					*get_attached_file
*
*					There is a "flaw" is this function. It's more a limit based on php/js/html. This can be called at any time after WordPress has loaded this plugin,
*					but, the conlog_session_id will be -1 when this page and any derived pages are first created - derived pages are blank pages that get loaded in this
*					page for a multitude of reasons. Each page starts anew and hence can't access the modified global state from the other "pages" and hence there can't
*					be a persistent id across different "pages" that are derived to create the end page that is sent to the user. A cookie wouldn't work either because
*					it would simply provide all browser tabs with the same session Id, which is what we're trying to avoid.
*
*					So, let's simply agree that one browser tab that has enabled console logging is going to be loaded and then the next. This allows us to display all of the
*					console logs in the first tab, then set the session id in javascript and use it ever after that for any ajax calls. This allows new sessions to be logged
*					from any loading state and then to still be logged after the php has served the page and is interacting with AJAX.
*
*					If you load multiple pages simultaneously then you will see random logs from any page because each are still logging to the default user -1 and session -1 
*
*/
if ( ! function_exists( 'console_log' ) ) {
	function console_log(){
		global $wpdb, $conlog_session_id, $random_page_id;
		
		set_conlog_session_id();
		
		if(func_num_args() == 0){
			return;
		}
		
		$table_name = conlog_table_name();
		$format = array('%s','%d');

		$tag = '';
		$log_in_ajax = true;
		$log_in_main = true;
		$is_alert = false;
		for ($i = 0; $i < func_num_args(); $i++) {
			$arg = func_get_arg($i);
			if(!empty($arg)){
				
				if(is_string($arg)&& strtolower(substr($arg,0,9)) === 'log_ajax-'){
					$log_in_ajax = (int) substr($arg,9) > 0 ? true : false;
					
				}else if(is_string($arg)&& strtolower(substr($arg,0,9)) === 'log_main-'){
					$log_in_main = (int) substr($arg,9) > 0 ? true : false;
					
				} else if(is_string($arg) && strtolower(substr($arg,0,4)) === 'tag-'){
					$tag = substr($arg,4);
					
				}else if(is_string($arg)&& strtolower(substr($arg,0,6)) === 'alert-'){
					$is_alert = (int) substr($arg,6) > 0 ? true : false;
					
				}else{	
					
					$arg = is_array($arg) ? wp_json_encode($arg, JSON_HEX_TAG | JSON_HEX_AMP ) :  $arg;	
					$arg = str_replace("'", "\'", $tag." ".$arg);//Prevents the following error when strings (single or in array) have apostrophes
																 //error: Uncaught SyntaxError: missing ) after argument list 
				
					//Log everything to the database so that calls to console.log will be shown in the order they were received. This also prevents any
					//logs from being shown in the browser until logging in admin/front end are enabled.
					//Also, it is sometimes possible to call '<script>console.log(\''.$arg.'\');</script>'
					//but this generates the warning
					$is_ajax = is_ajax_page();//defined('DOING_AJAX') && DOING_AJAX;<-supposedly the is_ajax function works with WooCommerce too vs this method
					$is_main = !$is_ajax;
					$allowed  = ($is_ajax && $log_in_ajax) || ($is_main && $log_in_main);
							
					if($allowed){
						$ajx = $is_ajax ? array( 'is_ajax'=> 1) : array();//Not sure why, but can't set is_ajax => 0 without excluding it altogether
						$alrt = $is_alert ? array( 'is_alert'=> 1) : array();//Not sure why, but can't set is_ajax => 0 without excluding it altogether
						
						$data = array_merge($ajx,$alrt, array('user_id' => get_current_user_id(), 'session_id' => $conlog_session_id, 'page_id' => $random_page_id, 'meta_value' => $arg));
						
						$wpdb->insert(conlog_table_name(), $data, $format);
						$my_id = $wpdb->insert_id;
					}
				}		
			}
		}		
	}
}

if ( ! function_exists( 'console_log_main' ) ) {
	function console_log_main(){
		$args = array_merge(array('log_ajax-0'), func_get_args());
		console_log(...$args);
	}
}

if ( ! function_exists( 'console_log_ajax' ) ) {
	function console_log_ajax(){
		$args = array_merge(array('log_main-0'), func_get_args());
		console_log(...$args);
	}
}

if ( ! function_exists( 'console_alert' ) ) {
	function console_alert(){
		$args = array_merge(array('alert-1'), func_get_args());
		console_log(...$args);
	}
}

if ( ! function_exists( 'console_alert_main' ) ) {
	function console_alert_main(){
		$args = array_merge(array('log_ajax-0','alert-1'), func_get_args());
		console_log(...$args);
	}
}

if ( ! function_exists( 'console_alert_ajax' ) ) {
	function console_alert_ajax(){
		$args = array_merge(array('log_main-0','alert-1'), func_get_args());
		console_log(...$args);
	}
}

/*-----------------------------------------------------------------------------------------------------------------------*/
/*--------------------------------------          console.log AJAX                    -----------------------------------*/
/*-----------------------------------------------------------------------------------------------------------------------*/

function set_conlog_session_id(){
	
	//Every call from AJAX must pass the session_id since each call creates a new page. Declare it globally so that other functions that are called from this single
	//AJAX call can use it - until the the end of the AJAX call
	global $conlog_session_id;
	$conlog_session_id = isset( $_POST['session_id'] ) ? (int) $_POST['session_id'] : 0;
}

function get_logging_settings(){
	global $conlog_session_id;
	
	set_conlog_session_id();

	//Replace session 0 logs (ie sessions created at startup without a unique id available) and associtate them with the current session. This is necessary
	//because it's not possible to sync the id between main and derived pages during initial build - plus it's just as easy and less convoluted to do this.
	replace_session_zero($conlog_session_id);

	$settings = get_option('console_log_settings');
	echo json_encode($settings, JSON_HEX_TAG | JSON_HEX_AMP );		
	
	die();	
}

function set_logging_settings(){

	//TODO 
	//if ( ! isset( $_POST['secret'] ) || $_POST['secret'] != md5( md5( AUTH_KEY . SECURE_AUTH_KEY ) . '-' . $this->parent->args['opt_name'] ) ) {
	//	wp_die( 'Invalid Secret for options' );
	//	exit;
	//}
	set_conlog_session_id();
	
	//Use the current settings unless explicit override for that setting was sent
	$settings = get_option('console_log_settings');	
	$s = array(
		isset( $_POST['poll-delta-ms'] ) ? (int) $_POST['poll-delta-ms'] : $settings[0],
		isset( $_POST['console-log-admin'] ) ? (int) $_POST['console-log-admin'] : $settings[1],
		isset( $_POST['console-log-front'] ) ? (int) $_POST['console-log-front'] : $settings[2],
		isset( $_POST['error-log-admin'] ) ? (int) $_POST['error-log-admin'] : $settings[3],
		isset( $_POST['error-log-front'] ) ? (int) $_POST['error-log-front'] : $settings[4],
		isset( $_POST['show_time_stamp'] ) ? (int) $_POST['show_time_stamp'] : $settings[5],
		isset( $_POST['show_rand_page_id'] ) ? (int) $_POST['show_rand_page_id'] : $settings[6],
		isset( $_POST['show_is_ajax'] ) ? (int) $_POST['show_is_ajax'] : $settings[7],
		isset( $_POST['show_conlog_json'] ) ? (int) $_POST['show_conlog_json'] : $settings[8],
		isset( $_POST['show_conlog_json_row'] ) ? (int) $_POST['show_conlog_json_row'] : $settings[9],
		isset( $_POST['show_conlog_meta'] ) ? (int) $_POST['show_conlog_meta'] : $settings[10],
	);
	
	update_option('console_log_settings', $s);
	
	die();
}

function get_console_logs(){
	global $conlog_session_id;

	set_conlog_session_id();
	
	//Retrieve the logs for the current session id and then clear the backlog
	$rows = session_id_rows($conlog_session_id);
	
	echo empty($rows) ? '' : json_encode($rows, JSON_HEX_TAG | JSON_HEX_AMP );
	clear_rows_by_session_id($conlog_session_id);
	
	
	die();
}

/*-----------------------------------------------------------------------------------------------------------------------*/
/*--------------------------------------          console.log Database                -----------------------------------*/
/*-----------------------------------------------------------------------------------------------------------------------*/

function conlog_table_name(){
	global $wpdb;
	return $wpdb->prefix.'console_logging';	
}

function init_conlog_database(){
	
	global $wpdb, $conlog_db_version;
	$conlog_db_version = '1.0';
	$table_name = conlog_table_name();
	
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		 //table not in database. Create new table
		 $charset_collate = $wpdb->get_charset_collate();

		 $sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				user_id bigint(20) unsigned NOT NULL default '0',
				session_id bigint(20) unsigned NOT NULL default '0',
				page_id bigint(20) unsigned NOT NULL default '0',
				is_ajax bit NOT NULL default 0,
				is_alert bit NOT NULL default 0,
				meta_value longtext,
				PRIMARY KEY  (id)
			) $charset_collate;";
			
		 require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		 dbDelta( $sql );
		 
		 update_option( 'conlog_db_version', $conlog_db_version );
	}
}

function init_conlog_database_data() {
//	global $wpdb;
	
//	$welcome_name = 'Mr. WordPress';
//	$welcome_text = 'Congratulations, you just completed the installation!';
	
//	$table_name = $wpdb->prefix . 'liveshoutbox';
	
//	$wpdb->insert( 
//		$table_name, 
//		array( 
//			'time' => current_time( 'mysql' ), 
//			'name' => $welcome_name, 
//			'text' => $welcome_text, 
//		) 
//	);
}
//Create the database and welcome message when the plugin is activated
register_activation_hook( __FILE__, 'init_conlog_database' );
register_activation_hook( __FILE__, 'init_conlog_database_data' );

function remove_console_logging_database() {
     global $wpdb;
     $table_name = conlog_table_name();
     $sql = "DROP TABLE IF EXISTS $table_name";
     $wpdb->query($sql);
     delete_option('conlog_db_version');
}  
//No useful data in table unless activated, so drop table when deactivated
register_deactivation_hook( __FILE__, 'remove_console_logging_database' );

/**
*	Clear rows that have the current session's tag. Also, clear rows that haven't been used in over a minute since that means the page is no longer
*	using the logging - and we don't want to fill up the database with console logs since. Don't clear all since multiple session could be logging
*	at the same time.
*
*/
function clear_rows_by_uid_session_id($user_id, $session_id){
	
	global $wpdb;
	$table_name = conlog_table_name();
	$sql = "DELETE FROM $table_name WHERE user_id = $user_id AND session_id = $session_id;";
	$wpdb->query($sql);
}

/**
*	Clear rows that have the current session's tag. Also, clear rows that haven't been used in over a minute since that means the page is no longer
*	using the logging - and we don't want to fill up the database with console logs since. Don't clear all since multiple session could be logging
*	at the same time.
*
*/
function clear_rows_by_session_id($session_id){
	
	global $wpdb;
	$table_name = conlog_table_name();
	$sql = "DELETE FROM $table_name WHERE session_id = $session_id;";
	$wpdb->query($sql);
}

/*
*	Clear all logs for all users for all sessions
*/
function clear_console_logs(){
	global $wpdb;
	$table_name = conlog_table_name();
	$sql = "DELETE FROM $table_name;";
	$wpdb->query($sql);
}

/*
*	Clear all logs for a specified user and all sessions
*/
function clear_console_logs_by_user_id($user_id){
	global $wpdb;
	$table_name = conlog_table_name();
	$sql = "DELETE FROM $table_name WHERE user_id = $user_id;";
	$wpdb->query($sql);
}

/*
*	Clear all logs for current user
*/
function clear_current_user_console_logs(){
	clear_console_logs_by_user_id(get_current_user_id());
}

function session_id_rows_meta($session_id){
	global $wpdb;
	$table_name = conlog_table_name();
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
		$sql = "SELECT meta_value FROM $table_name WHERE session_id = $session_id ORDER BY id ASC;";
		$rows = $wpdb->get_results( $sql , ARRAY_A);		
		if(!empty($rows)){			
			return $rows;
		}
	}
	return '';	
}

function session_id_rows($session_id){
	global $wpdb;
	$table_name = conlog_table_name();
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
		$sql = "SELECT * FROM $table_name WHERE session_id = $session_id ORDER BY id ASC;";
		$rows = $wpdb->get_results( $sql , ARRAY_A);		
		if(!empty($rows)){			
			return $rows;
		}
	}
	return '';	
}

/**
*	The current user can have any number of logging sessions at a given time and we need to determine the largest session number - ie the last one
*	used and return the next integer
*/
function next_session_number(){
	global $wpdb;
	$session = 0;
	$table_name = conlog_table_name();
	$uid = get_current_user_id();
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
		$sql = "SELECT MAX(session_id) FROM $table_name WHERE user_id = $uid;";
		$rows = $wpdb->get_results( $sql , ARRAY_A);		
		$session = !empty($rows) && !empty($rows[0]) && !empty($rows[0]['MAX(session_id)']) ? $rows[0]['MAX(session_id)'] + 1 : $session;
	}
	return $session;
}

function replace_session_zero($id){
	global $wpdb;

	$table_name = conlog_table_name();
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
		$sql = "UPDATE $table_name SET session_id = $id WHERE session_id = 0";
		$wpdb->query($sql);
	}	
}

/*-----------------------------------------------------------------------------------------------------------------------*/
/*--------------------------------------          error_log                           -----------------------------------*/
/*-----------------------------------------------------------------------------------------------------------------------*/

/*
*	This allows arrays to be shown in error_log as more than "array". It also allows us to not just print to the php error log, but to
*	see those errors in a browser console
*
*/
function errorLog(){
	
	if(func_num_args() == 0){
		return;
	}

	$tag = '';
	for ($i = 0; $i < func_num_args(); $i++) {
		$arg = func_get_arg($i);
		if(!empty($arg)){		
			if(is_string($arg)&& strtolower(substr($arg,0,4)) === 'tag-'){
				$tag = substr($arg,4);
			}else{		
				error_log($tag." ".json_encode($arg, JSON_HEX_TAG | JSON_HEX_AMP ));
			}		
		}
	}
}
/*
*	Clear all logs for all users for all sessions
*/
function clear_error_log(){
	//TODO this will need to erase the contents of the error log file
}

/*-----------------------------------------------------------------------------------------------------------------------*/
/*--------------------------------------          Other                               -----------------------------------*/
/*-----------------------------------------------------------------------------------------------------------------------*/

function is_ajax_page(){
	if( ! empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ]) == 'xmlhttprequest' ) {
		return true;
	}
	return false;
}

function enable_front_end_ajaxurl() {
	echo '<script type="text/javascript">
			var ajaxurl = "' . admin_url('admin-ajax.php') . '";
		</script>';
}

/**
*	--------------------------- Actions and filters --------------------------------------------
*	TODO we should include enter and exit functions - one function that sets priority to 0 to be firect first and then one that is set to some
*	obsurdly large number to go last. This lets us know before anything is modified and after everything is modified
*/
/*
function conlog_muplugins_loaded(){
	
}
add_action( 'muplugins_loaded', 'conlog_muplugins_loaded');	

function conlog_registered_taxonomy(){//$taxonomy, $object_type, $args 
	
}
add_action( 'registered_taxonomy', 'conlog_registered_taxonomy');	

function conlog_registered_post_type(){//$post_type, $post_type_object 
	
}
add_action( 'registered_post_type', 'conlog_registered_post_type');	






function conlog_plugins_loaded($array){
	
}
add_action( 'plugins_loaded', 'conlog_plugins_loaded');	

function conlog_sanitize_comment_cookies($sanitize_comment_cookies ){
	
}
add_action( 'sanitize_comment_cookies', 'conlog_sanitize_comment_cookies');	

function conlog_setup_theme( $theme_setup ){
	
}
add_action( 'setup_theme', 'conlog_setup_theme');	

function conlog_load_textdomain(){//$domain, $mofile
	
}
add_action( 'load_textdomain', 'conlog_load_textdomain');	

function conlog_after_setup_theme(){// $array, $int 
	
}
add_action( 'after_setup_theme', 'conlog_after_setup_theme');	

function conlog_auth_cookie_malformed($rest_cookie_collect_status ){
	
}
add_action( 'auth_cookie_malformed', 'conlog_auth_cookie_malformed');







function conlog_auth_cookie_valid($rest_cookie_collect_status){
	
}
add_action( 'auth_cookie_valid', 'conlog_auth_cookie_valid');	

function conlog_set_current_user( $kses_init ){
	
}
add_action( 'set_current_user', 'conlog_set_current_user');	

function conlog_init(){
	
}
add_action( 'init', 'conlog_init');	

function conlog_widgets_init(){
	
}
add_action( 'widgets_init', 'conlog_widgets_init');	

function conlog_register_sidebar( $sidebar ){
	
}
add_action( 'register_sidebar', 'conlog_register_sidebar');
	
function conlog_wp_register_sidebar_widget( $widget ){
	
}
add_action( 'wp_register_sidebar_widget', 'conlog_wp_register_sidebar_widget');	







function conlog_wp_default_scripts( $scripts ){
	
}
add_action( 'wp_default_scripts', 'conlog_wp_default_scripts');	

function conlog_wp_default_styles( $styles ){
	
}
add_action( 'wp_default_styles', 'conlog_wp_default_styles');	

function conlog_admin_bar_init( ){
	
}
add_action( 'admin_bar_init', 'conlog_admin_bar_init');	

function conlog_add_admin_bar_menus( $wp_admin_bar ){
	
}
add_action( 'add_admin_bar_menus', 'conlog_add_admin_bar_menus');	

function conlog_wp_loaded(){// $array, $int 
	
}
add_action( 'wp_loaded', 'conlog_wp_loaded');	

function conlog_parse_request( $query){
	
}
add_action( 'parse_request', 'conlog_parse_request');	

function conlog_send_headers(){
	
}
add_action( 'send_headers', 'conlog_send_headers');


function conlog_parse_query( $wp_query  ){
	
}
add_action( 'parse_query', 'conlog_parse_query');	

function conlog_pre_get_posts(  $query  ){
	
}
add_action( 'pre_get_posts', 'conlog_pre_get_posts');
	
function conlog_posts_selection( $query  ){
	
}
add_action( 'posts_selection', 'conlog_posts_selection');	

function conlog_wp(){
	
}
add_action( 'wp', 'conlog_wp');	

*/