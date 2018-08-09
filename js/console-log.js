jQuery(document).ready(function($) {
	
	var isAdmin = $('#wp-admin-bar-conlog-main-menu').hasClass('is-admin-1');//is_admin is stored in the menu because it always returns true when using admin-ajax, which isn't correct
	var console_log_admin = false;
	var console_log_front = false;
	var console_log_polling_ms = 2000;
	var error_log_admin = false;
	var error_log_front = false;
	
	var isInitialized = false;
	
	var conlog_session_id = Math.floor(Math.random() * 10000) + 1;
	
	var show_time_stamp = false;
	var show_rand_page_id = false;
	var show_is_ajax = false;
	var show_conlog_json = false;
	var show_conlog_json_row = false;
	var show_conlog_meta = false;
		
	//-----------------------------------  AJAX Polling    -------------------------------------------------
	
	$.post(
		ajaxurl, 
		{	
			'action': 'get_logging_settings',
			'session_id': conlog_session_id
		}, 
		function(response){		
		
			var a = JSON.parse(response);
			
			console_log_polling_ms = a[0];
			console_log_admin = a[1] > 0 ? true : false;
			console_log_front = a[2] > 0 ? true : false;
			error_log_admin = a[3] > 0 ? true : false;
			error_log_front = a[4] > 0 ? true : false;	
			
			show_time_stamp = a[5] > 0 ? true : false;	
			show_rand_page_id = a[6] > 0 ? true : false;	
			show_is_ajax = a[7] > 0 ? true : false;	
			show_conlog_json = a[8] > 0 ? true : false;	
			show_conlog_json_row = a[9] > 0 ? true : false;	
			show_conlog_meta = a[10] > 0 ? true : false;	
						
			isInitialized = true;	
	
			//Initialize each option
			set_console_log_admin(console_log_admin, false, false);
			set_console_log_front_end(console_log_front, false, false);
			set_error_log_admin(error_log_admin, false, false);
			set_error_log_front_end(error_log_front, false, false);
			
			set_tag_time_stamp(show_time_stamp, false, false);
			set_tag_page_id(show_rand_page_id, false, false);
			set_tag_is_ajax(show_is_ajax, false, false);
			set_tag_show_json(show_conlog_json, false, false);
			set_tag_show_json_row(show_conlog_json_row, false, false);
			set_tag_show_json_meta(show_conlog_meta, false, false);	

			update_ui_db(true, false);			
			
			sendPoll();
		}
	);
	
	function update_db_logging_settings(){

		var cla = console_log_admin ? 1 : 0;
		var clf = console_log_front ? 1 : 0;
		var ela = error_log_admin ? 1 : 0;
		var elf = error_log_front ? 1 : 0;
		
		var sts = show_time_stamp ? 1 : 0;
		var srp = show_rand_page_id ? 1 : 0;
		var sia = show_is_ajax ? 1 : 0;
		var scj = show_conlog_json ? 1 : 0;
		var scjr = show_conlog_json_row ? 1 : 0;
		var scjm = show_conlog_meta ? 1 : 0;
		
		$.post(
			ajaxurl, 
			{	
				'action': 'set_logging_settings',
				'session_id': conlog_session_id,
				'poll-delta-ms' : console_log_polling_ms,
				'console-log-admin' : cla,
				'console-log-front' : clf,
				'error-log-admin' : ela,
				'error-log-front' : elf,
				'show_time_stamp' : sts,
				'show_rand_page_id' : srp,
				'show_is_ajax' : sia,
				'show_conlog_json' : scj,
				'show_conlog_json_row' : scjr,
				'show_conlog_meta' : scjm				
			}, 
			function(response){	
			}
		);		
	}
	
	function doPoll(){
		return (isAdmin && console_log_admin) || (!isAdmin && console_log_front) ? true : false;
	}
	
	function sendPoll() {
		if(!doPoll()){
			return;
		}
		
		//Get the consoleLogs that have been piling up in the db
		$.post(
			ajaxurl, 
			{	
				'action': 'get_console_logs',
				'session_id': conlog_session_id
			}, 
			function(response){
				if(response != null && response != ''){
					//If the string had an apostrophe then it was converted to \' to avoid an error. Rever it
					var a = JSON.parse(response);				
					var i;
					if(show_conlog_json) console.log(response);//already shows time_stamp, rand_page_id, is_ajax
					for (i = 0; i < a.length; i++) { 
						if(show_conlog_json_row) console.log(a[i]);//already shows time_stamp, rand_page_id, is_ajax
						if(show_conlog_meta){
							var ts = show_time_stamp ? a[i]['ts'] + " " : '';
							var pid = show_rand_page_id ? a[i]['page_id'] + " " : '';
							var ajax = show_is_ajax ? (a[i]['is_ajax'] > 0 ? 'AJAX' : 'NOT_AJAX') + " " : ''; 
							var meta = a[i]['meta_value'].replace("\\'", "'");
							meta = ts + pid + ajax + meta;
							
							if(a[i]['is_alert'] > 0){
								alert(meta);
							}else{
								console.log(meta);
							}
						} 
					}
				}
			}
		);

		setTimeout(function(){ 
			sendPoll();
		}, console_log_polling_ms);
	}
	
	//---------------------------- Main Click Detection, Response, Enable/Disable -------------------------------------------------
	
	$("#wp-admin-bar-conlog-main-menu").click(function(event){
		if(stop_propogation()){ return; }
		set_all_loggers(!$(this).hasClass('log-enabled'), true, true);
	});
		
	function set_all_loggers(enable, doUpdateUI, doUpdateDB){
		//Don't update ui and db until everything is updated - ie make only one call for all updates
		set_console_log_admin(enable, false, false);
		set_console_log_front_end(enable, false, false);
		set_error_log_admin(enable, false, false);
		set_error_log_front_end(enable, false, false);
		
		update_ui_db(doUpdateUI, doUpdateDB);
	}
	
	function update_ui_db(doUpdateUI, doUpdateDB){
		if(doUpdateUI){
			update_main_ui();
			update_console_ui();
			update_error_ui();
			update_tag_ui();
		}
		
		if(doUpdateDB){
			update_db_logging_settings();
		}
	}
	
	function update_main_ui(){		
		var main = $('#wp-admin-bar-conlog-main-menu');
		if(main.find('.log-enabled').length !== 0){		
			main.addClass('log-enabled');
		}else{
			main.removeClass('log-enabled');
		}
	}
	
	function stop_propogation(){
		event.stopPropagation();
		event.stopImmediatePropagation();
		return !isInitialized ? true : false;
	}
	
	//---------------------------- Console Log Click Detection and Response -------------------------------------------------
	
	$("#wp-admin-bar-conlog-main").click(function(event){
		if(stop_propogation()){ return; }		
		set_all_console_logs(!$(this).hasClass('log-child-enabled'), true, true);
	});
	
	$("#wp-admin-bar-conlog-admin").click(function(event){
		if(stop_propogation()){ return; }		
		set_console_log_admin(toggle_log_enabled(this), true, true);
	});
	
	$("#wp-admin-bar-conlog-front-end").click(function(event){
		if(stop_propogation()){ return; }		
		set_console_log_front_end(toggle_log_enabled(this), true, true);
	});
	
	$("#wp-admin-bar-conlog-clear").click(function(event){
		if(stop_propogation()){ return; }		
		clear_console_logs();
	});
		
	//---------------------------- Console Log Enable/Disable ---------------------------------------------------
	
	function update_console_ui(){		
		var main = $('#wp-admin-bar-conlog-main');
		if(main.find('.log-enabled').length !== 0){		
			main.addClass('log-child-enabled');
		}else{
			main.removeClass('log-child-enabled');
		}
	}
	
	function set_all_console_logs(enable, doUpdateUI, doUpdateDB){
		set_console_log_admin( enable, false, false);
		set_console_log_front_end( enable, false, false);
		
		update_ui_db(doUpdateUI, doUpdateDB);
	}
	
	function set_console_log_admin(enable, doUpdateUI, doUpdateDB){
		console_log_admin = enable;
		set_log('#wp-admin-bar-conlog-admin', enable, doUpdateUI, doUpdateDB);
	}
	
	function set_console_log_front_end(enable, doUpdateUI, doUpdateDB){
		console_log_front = enable;
		set_log('#wp-admin-bar-conlog-front-end', enable, doUpdateUI, doUpdateDB);
	}
	
	function toggle_log_enabled(component){
		return !$(component).hasClass('log-enabled');	
	}
	
	function set_log(id, enable, doUpdateUI, doUpdateDB){

		if(enable){
			$(id).addClass('log-enabled');
			sendPoll();
		}else{
			$(id).removeClass('log-enabled');
		}
		
		update_ui_db(doUpdateUI, doUpdateDB);		
	}
	
	function clear_console_logs(){
		//Indicate an action is being taken
		$("#wp-admin-bar-conlog-clear").addClass('clearing-log');
		
		//Get the consoleLogs that have been piling up in the db
		$.post(
			ajaxurl, 
			{	
				'action': 'clear_console_logs'
			}, 
			function(response){
				$("#wp-admin-bar-conlog-clear").removeClass('clearing-log');
			}
		);
	}
	
	//---------------------------- Error Log Click Detection and Response ---------------------------------------------------
	
	$("#wp-admin-bar-errlog-main").click(function(event){
		if(stop_propogation()){ return; }
		set_all_error_logs(!$(this).hasClass('log-child-enabled'), true, true);
	});	
	
	$("#wp-admin-bar-errlog-admin").click(function(event){
		if(stop_propogation()){ return; }
		set_error_log_admin(toggle_log_enabled(this), true, true);
	});
	
	$("#wp-admin-bar-errlog-front-end").click(function(event){
		if(stop_propogation()){ return; }
		set_error_log_front_end(toggle_log_enabled(this), true, true);
	});
		
	$("#wp-admin-bar-errlog-clear").click(function(event){
		if(stop_propogation()){ return; }		
		clear_error_log();
	});
	
	//---------------------------- Error Log Enable/Disable ---------------------------------------------------
	function update_error_ui(){		
		var main = $('#wp-admin-bar-errlog-main');
		if(main.find('.log-enabled').length !== 0){		
			main.addClass('log-child-enabled');
		}else{
			main.removeClass('log-child-enabled');
		}
	}
	
	function set_all_error_logs(enable, doUpdateUI, doUpdateDB){
		set_error_log_admin( enable, false, false);
		set_error_log_front_end( enable, false, false);
		
		update_ui_db(doUpdateUI, doUpdateDB);
	}
	
	function set_error_log_admin(enable, doUpdateUI, doUpdateDB){
		error_log_admin = enable;
		set_log('#wp-admin-bar-errlog-admin', enable, doUpdateUI, doUpdateDB);
	}
	
	function set_error_log_front_end(enable, doUpdateUI, doUpdateDB){
		error_log_front = enable;
		set_log('#wp-admin-bar-errlog-front-end', enable, doUpdateUI, doUpdateDB);
	}
	
	function clear_error_log(){
		//Indicate an action is being taken
		$("#wp-admin-bar-errlog-clear").addClass('clearing-log');
		
		//Get the consoleLogs that have been piling up in the db
		$.post(
			ajaxurl, 
			{	
				'action': 'clear_error_log'
			}, 
			function(response){
				$("#wp-admin-bar-errlog-clear").removeClass('clearing-log');
			}
		);
	}
	
	//---------------------------- TAG Click Detection and Response ---------------------------------------------------
	
	$("#wp-admin-bar-meta-main").click(function(event){
		if(stop_propogation()){ return; }
		set_all_tags(!$(this).hasClass('tag-child-enabled'), true, true);
	});	
	
	$("#wp-admin-bar-meta-time-stamp").click(function(event){
		if(stop_propogation()){ return; }
		set_tag_time_stamp(toggle_tag_enabled(this), true, true);
	});
		
	$("#wp-admin-bar-meta-page-id").click(function(event){
		if(stop_propogation()){ return; }	
		set_tag_page_id(toggle_tag_enabled(this), true, true);		
	});
	
	$("#wp-admin-bar-meta-is-ajax").click(function(event){
		if(stop_propogation()){ return; }	
		set_tag_is_ajax(toggle_tag_enabled(this), true, true);
	});
	
	$("#wp-admin-bar-meta-show-json").click(function(event){
		if(stop_propogation()){ return; }
		set_tag_show_json(toggle_tag_enabled(this), true, true);
	});
	
	$("#wp-admin-bar-meta-show-json-row").click(function(event){
		if(stop_propogation()){ return; }
		set_tag_show_json_row(toggle_tag_enabled(this), true, true);
	});
	
	$("#wp-admin-bar-meta-show-json-meta").click(function(event){
		if(stop_propogation()){ return; }		
		set_tag_show_json_meta(toggle_tag_enabled(this), true, true);
	});
		
	//---------------------------- TAG Enable/Disable ---------------------------------------------------
	function update_tag_ui(){		
		var main = $('#wp-admin-bar-meta-main');
		if(main.find('.tag-enabled').length !== 0){		
			main.addClass('tag-child-enabled');
		}else{
			main.removeClass('tag-child-enabled');
		}
	}
	
	function set_all_tags(enable, doUpdateUI, doUpdateDB){
		//Don't update ui and db until everything is updated - ie make only one call for all updates
		set_tag_time_stamp(enable, false, false);
		set_tag_page_id(enable, false, false);
		set_tag_is_ajax(enable, false, false);
		set_tag_show_json(enable, false, false);
		set_tag_show_json_row(enable, false, false);
		set_tag_show_json_meta(enable, false, false);
		
		update_ui_db(doUpdateUI, doUpdateDB);
	}
	
	function set_tag_time_stamp(enable, doUpdateUI, doUpdateDB){	
		show_time_stamp = enable;	
		set_tag('#wp-admin-bar-meta-time-stamp', enable, doUpdateUI, doUpdateDB);
	}
		
	function set_tag_page_id(enable, doUpdateUI, doUpdateDB){		
		show_rand_page_id = enable;
		set_tag('#wp-admin-bar-meta-page-id', enable, doUpdateUI, doUpdateDB);
	}
		
	function set_tag_is_ajax(enable, doUpdateUI, doUpdateDB){	
		show_is_ajax = enable;
		set_tag('#wp-admin-bar-meta-is-ajax', enable, doUpdateUI, doUpdateDB);
	}
	
	function set_tag_show_json(enable, doUpdateUI, doUpdateDB){	
		show_conlog_json = enable;
		set_tag('#wp-admin-bar-meta-show-json', enable, doUpdateUI, doUpdateDB);
	}
	
	function set_tag_show_json_row(enable, doUpdateUI, doUpdateDB){	
		show_conlog_json_row = enable;
		set_tag('#wp-admin-bar-meta-show-json-row', enable, doUpdateUI, doUpdateDB);
	}
	
	function set_tag_show_json_meta(enable, doUpdateUI, doUpdateDB){		
		show_conlog_meta = enable;
		set_tag('#wp-admin-bar-meta-show-json-meta', enable, doUpdateUI, doUpdateDB);
	}

	function toggle_tag_enabled(component){
		return $(component).hasClass('tag-enabled') ? false : true;	
	}	
	
	function set_tag (id, enable, doUpdateUI, doUpdateDB){		
		if(enable){
			$(id).addClass('tag-enabled');
		}else{
			$(id).removeClass('tag-enabled');
		}
		
		update_ui_db(doUpdateUI, doUpdateDB);		
	}
});