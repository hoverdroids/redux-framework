<?php
	
    if ( file_exists( dirname( __FILE__ ) . '/tgm/tgm-init.php' ) ) {
        require_once dirname( __FILE__ ) . '/tgm/tgm-init.php';
    }
		
    if ( file_exists( dirname( __FILE__ ).'/redux-framework/framework.php' ) ) {
        require_once dirname(__FILE__).'/redux-framework/framework.php';
    }
	
	//The Redux Framework does not load extensions until after the activation hook has been called. So, we must load the activation/deactivation files separately. Note that extensions can be enabled and disabled from the plugin admin
	//and that there are action hooks for those events. This activation/deactivation is for the plugin and should only include actions that the extension needs done when the plugin is activated/deactivate
	
	//Each plugin will independently activate/deactivate thier own extensions using a hook that is tied to the plugin. Since this wp hook is only called when the plugin is
	//activated, and since it lets us generalize the activation of files, I am OK using an annonomous function in this case.
	register_activation_hook( pluginFilePath(__FILE__), function () {
		load_redux_extension_activation_files(__FILE__ );
		do_action('redux_' . opt_name(__FILE__) . '_register_activation_hook');
	});
	
	register_deactivation_hook( pluginFilePath(__FILE__), function () {
		load_redux_extension_deactivation_files(__FILE__ );
		do_action('redux_' . opt_name(__FILE__) . '_register_deactivation_hook');
	});
	
    if ( file_exists( dirname( __FILE__ ) . '/options-init.php' ) ) {
        require_once dirname( __FILE__ ) . '/options-init.php';
    }
    
    if ( file_exists( dirname( __FILE__ ) . '/redux-extensions/extensions-init.php' ) ) {
        require_once dirname( __FILE__ ) . '/redux-extensions/extensions-init.php';
    }
?>