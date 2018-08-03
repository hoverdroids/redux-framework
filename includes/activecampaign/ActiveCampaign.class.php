<?php

require_once('Connector.class.php');

if( ! class_exists('ActiveCampaign') ) :

class ActiveCampaign extends AC_Connector {

	public $url_base;
	public $url;
	public $api_key;
	public $version = 1;
	public $debug = false;

	function __construct($url, $api_key, $api_user = "", $api_pass = "") {
		$this->url_base = $this->url = $url;
		$this->api_key = $api_key;
		parent::__construct($url, $api_key, $api_user, $api_pass);
	}

	function version($version) {
		$this->version = (int)$version;
		if ($version == 2) {
			$this->url_base = $this->url_base . "/2";
		}
	}

	function api($path, $post_data = array()) {
		// IE: "contact/view"
		$components = explode("/", $path);
		$component = $components[0];

		if (count($components) > 2) {
			// IE: "contact/tag/add?whatever"
			// shift off the first item (the component, IE: "contact").
			array_shift($components);
			// IE: convert to "tag_add?whatever"
			$method_str = implode("_", $components);
			$components = array($component, $method_str);
		}

		if (preg_match("/\?/", $components[1])) {
			// query params appended to method
			// IE: contact/edit?overwrite=0
			$method_arr = explode("?", $components[1]);
			$method = $method_arr[0];
			$params = $method_arr[1];
		}
		else {
			// just a method provided
			// IE: "contact/view
			if ( isset($components[1]) ) {
				$method = $components[1];
				$params = "";
			}
			else {
				return "Invalid method.";
			}
		}

		// adjustments
		if ($component == "list") {
			// reserved word
			$component = "list_";
		}
		elseif ($component == "branding") {
			$component = "design";
		}
		elseif ($component == "sync") {
			$component = "contact";
			$method = "sync";
		}
		elseif ($component == "singlesignon") {
			$component = "auth";
		}

		$class = ucwords($component); // IE: "contact" becomes "Contact"
		$class = "AC_" . $class;
		// IE: new Contact();

		$class = new $class($this->version, $this->url_base, $this->url, $this->api_key);
		// IE: $contact->view()

		if ($method == "list") {
			// reserved word
			$method = "list_";
		}

		$class->debug = $this->debug;

		$response = $class->$method($params, $post_data);
		return $response;
	}

}
endif;

require_once('Contact.class.php');
require_once('List.class.php');