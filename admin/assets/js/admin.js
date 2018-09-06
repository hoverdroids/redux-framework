(function( $ ) {
	"use strict";
	$(document).ready(function(){
		//Add an clickable image header to the admin panel
		var href = captains_log_script.headerUrl;
		var hrefLeft = captains_log_script.headerImgLeftUrl;
		var hrefRight = captains_log_script.headerImgRightUrl;
		var altL = captains_log_script.altL;
		var altR = captains_log_script.altR;
		$('#redux-header').prepend(
			'<div class="display_header_left"><a target="_blank" href="' + href + '" style="display: block; height: 100%"><img src="' + hrefLeft + '" alt="' + altL + '" /></a></div><div class="display_header_right"><a target="_blank" href="' + href + '" style="display: block; height: 100%"><img src="' + hrefRight + '" alt="' + altR + '" /></a></div>'
		);	
	});   
})( jQuery );