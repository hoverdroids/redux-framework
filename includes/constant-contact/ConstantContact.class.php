<?php
/**
 * ConstantContact Subscription
 */

use Ctct\ConstantContact;
use Ctct\Components\Contacts\Contact;

class MTSNB_ConstantContact {

	public function init( $api_key ) {

		require_once 'autoload.php';
		return new ConstantContact( $api_key );
	}

	public function get_lists( $api_key, $token ) {

		$api = $this->init( $api_key );

		$result = $api->listService->getLists( $token );

		$lists = array();
		foreach( $result as $id => $list ) {
			$lists[ $list->id ] = $list->name;
		}

		return $lists;
	}

    public function subscribe( $identity, $options ) {

		$api = $this->init( $options['api_key'] );

		$vars = array(
			'status' => 'ACTIVE',
			'email_addresses' => array(
				array( 'email_address' => $identity['email'] )
			),
			'lists' => array(
				array( 'id' => $options['list_id'] )
			)
		);

        if ( !empty( $identity['name'] ) ) {
            $vars['first_name'] = $identity['name'];
        }

		try {
			$api->contactService->addContact( $options['token'], Contact::create($vars) );
		}
		catch( Exception $e ) {

			$message = $e->getErrors();

			$message = $message[0]->error_message;
			return array(
				'status' => 'warning',
				'message' => $message
			);
			if ( false === strpos( $message, 'already exists' ) ) {
                throw new Exception( $message );
            }
		}

		return array(
			'status' => 'subscribed'
		);
	}
}