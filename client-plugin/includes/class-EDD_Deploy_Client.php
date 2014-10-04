<?php

class EDD_Deploy_Client {

	private $api_url;

	function check_capabilities() {

		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
			// TODO: Error message
		}

	}

	public function api( $api, $action, $args ) {

		if ( 'plugin_information' == $action ) {

			if ( isset( $_GET['edd_deploy'] ) ) {

				$api_params = array(
					'edd_action' => 'get_download',
					'item_name'  => urlencode( $_GET['name'] ),
					'license'    => iiset( $_GET['license'] ) ? urlencode( $_GET['license'] ) : null,
				);

				$api = new stdClass();
				$api->name          = $args->slug;
				$api->version       = '';
				$api->download_link = add_query_arg( $api_params, $this->api_url );;

			}

		}

		return $api;

	}

	public static function check_server() {

		$this->check_capabilities();

		$api_params = array(
			'edd_action' => 'check_download',
			'item_name'  => urlencode( $_POST['download'] ),
		);



		$request = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		if ( ! is_wp_error( $request ) ) {

			$request = maybe_unserialize( json_decode( wp_remote_retrieve_body( $request ) ) );

			if ( 'free' == $request->download ) {
				$response = 0;
			} else if ( 'chargeable' == $request->download ) {
				$response = 1;
			} else {
				$response = 'invalid';
			}

		} else {

			$response = 'Server error';

		}

		die( json_encode( $response ) );

	}


	public static function deploy() {

		$licence = null;

		$this->check_capabilities();

		$download = $_POST['download'];

		if ( isset( $_POST['license'] ) ) {

			$license = $_POST['license'];

			$api_args = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_name'  => urlencode( $download )
			);

			$response = wp_remote_get( add_query_arg( $api_args, $this->api_url ), array( 'timeout' => 15, 'sslverify' => false ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				return false;
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( 'valid' != $license_data->license ) {
				return;
			}

		}

		$api_args = array(
			'edd_action' => 'get_download',
			'item_name'  => urlencode( $download ),
			'license'	 => $license,
		);

		$download_link = add_query_arg( $api_args, $this->api_url );

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$upgrader = new Plugin_Upgrader();
		$install  = $upgrader->install( $download_link );

		if ( $install == 1 ) {

			// TODO: Install the plugin.

		}

		die();

	}

}