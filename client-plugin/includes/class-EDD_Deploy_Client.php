<?php

class EDD_Deploy_Client {

	private $api_url;

	function __construct( $store_url ) {

		$this->api_url = trailingslashit( $store_url );
		add_action( 'plugins_api', array($this, 'plugins_api'), 999, 3 );

	}

	/**
	 * Check if the user is allowed to install the plugin
	 */
	function check_capabilities() {

		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
			// TODO: Error message
		}

	}

	/**
	 * Hook into the API.
	 * Allows us to use URLs from our EDD store.
	 */
	public function plugins_api( $api, $action, $args ) {

		if ( 'plugin_information' == $action ) {

			if ( isset( $_GET['edd_deploy'] ) ) {

				$api_params = array(
					'edd_action' => 'get_download',
					'item_name'  => urlencode( $_GET['name'] ),
					'license'    => isset( $_GET['license'] ) ? urlencode( $_GET['license'] ) : null,
				);

				$api = new stdClass();
				$api->name          = $args->slug;
				$api->version       = '';
				$api->download_link = add_query_arg( $api_params, $this->api_url );;

			}

		}

		return $api;

	}

	public static function install_url( $type = 'plugin', $download_name = '', $license = '' ) {

		$download_name = ( '' == $download_name ) ? $_POST['download'] : $download_name;

		if ( '' == $license ) {
			$license = isset( $_POST['license'] ) ? $_POST['license'] : '';
		} else {
			$license = '';
		}

		$name = urlencode( $download_name );
		$slug = sanitize_title( $download_name );
		$url  = admin_url( 'update.php?action=install-' . $type . '&' . $type . '=' . $slug . '&name=' . $name . '&license=' . $license . '&_wpnonce=' . wp_create_nonce( 'install-plugin_' . $slug ) );

		return $url;
	}


	/**
	 * Check if he download exists on the remote server and it status (free/chargeable)
	 */
	public static function check_download() {

		// Check the user's capabilities before proceeding
		$this->check_capabilities();

		$api_params = array(
			'edd_action' => 'check_download',
			'item_name'  => urlencode( $_POST['download'] ),
		);

		// Send our details to the remote server
		$request = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// There was no error, we can proceed
		if ( ! is_wp_error( $request ) ) {

			$request = maybe_unserialize( json_decode( wp_remote_retrieve_body( $request ) ) );

			if ( 'free' == $request->download ) {
				// This is a free download.
				$response = 0;
			} else if ( 'chargeable' == $request->download ) {
				// This is a chargeable download.
				// We'll probably need to ask for a license.
				$response = 1;
			} else {
				// File does not exist
				$response = 'invalid';
			}

		} else {

			// Server was unreacheable
			$response = 'Server error';

		}

		die( json_encode( $response ) );

	}

	/**
	 * This is where the fun stuff happens. (pr is it just funny stuff?)
	 * Get the file from the remote server and install it.
	 */
	public static function deploy() {

		$licence = null;

		// Check if we're allowed to install plugins before proceeding
		$this->check_capabilities();

		$download = $_POST['download'];

		// If this is a chargeable product and a license is needed,
		// Try to validate it and if valid, activate it.
		if ( isset( $_POST['license'] ) ) {

			$license = $_POST['license'];

			$api_args = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_name'  => urlencode( $download )
			);

			// Get a response from our EDD server
			$response = wp_remote_get( add_query_arg( $api_args, $this->api_url ), array( 'timeout' => 15, 'sslverify' => false ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				return false;
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// If the licence is not valid, early exit.
			if ( 'valid' != $license_data->license ) {
				return;
				// TODO: Add an error message.
			}

		}

		// Get the download
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