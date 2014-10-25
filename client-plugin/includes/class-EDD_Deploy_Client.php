<?php

class EDD_Deploy_Client {

	private $api_url;

	function __construct( $store_url ) {

		$this->api_url = trailingslashit( $store_url );
		add_action( 'plugins_api', array( $this, 'plugins_api' ), 99, 3 );
		add_action( 'admin_init', array( $this, 'deploy' ) );

		add_action( 'admin_footer', array( $this, 'register_scripts' ) );

		add_action( 'wp_ajax_edd_deployer_install', array( $this, 'install') );
//		add_action( 'wp_ajax_edd_deployer_check_download', array( $this, 'check_download') );
//		add_action( 'wp_ajax_edd_deployer_deploy', array( $this, 'deploy') );

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'prefix_deployer_admin_page_content', 'prefix_deployer_admin_page_content' );

		add_thickbox();

	}

	function admin_menu () {

		add_options_page( 'Deployer Demo', 'Deployer Demo', 'install_plugins', 'deployer-demo', array( $this, 'settings_page' ) );
	}

	public function register_scripts() {
		wp_enqueue_script( 'edd_deployer_script', EDD_DEPLOY_PLUGIN_URL . 'assets/js/edd-deploy.js', array( 'jquery' ) );
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

	function get_downloads() {

		$api_params = array( 'edd_action' => 'get_downloads' );
		$request    = wp_remote_post( $this->api_url . '/?edd_action=get_downloads' );

		if ( is_wp_error( $request ) ) {
			return;
		}

		$request = json_decode( wp_remote_retrieve_body( $request ), true );

		return $request;

	}

	/**
	 * Hook into the API.
	 * Allows us to use URLs from our EDD store.
	 */
	public function plugins_api( $api, $action, $args ) {

		if ( 'plugin_information' == $action ) {

			if ( isset( $_POST['edd_deploy'] ) ) {

				$api_params = array(
					'edd_action' => 'get_download',
					'item_name'  => urlencode( $_POST['name'] ),
					'license'    => isset( $_POST['license'] ) ? urlencode( $_POST['license'] ) : null,
				);

				$api = new stdClass();
				$api->name          = $args->slug;
				$api->version       = '';
				$api->download_link = $this->api_url . '?edd_action=get_download&item_name=' . $api_args['item_name'] . '&license=' . $api_args['license'];

			}

		}

		return $api;

	}

	public static function install_url( $type = 'plugin', $name = '', $license = '' ) {

		$name = ( '' == $name ) ? $_POST['download'] : $name;
		$slug = sanitize_title_with_dashes( $name );

		$license = '';

		if ( isset( $_POST['license'] ) ) {
			$license = $_POST['license'];
		}

		$url = admin_url( 'update.php?action=install-' . $type . '&' . $type . '=' . $slug . '&name=' . $slug . '&license=' . $license . '&_wpnonce=' . wp_create_nonce( 'install-plugin_' . $slug ) . '&edd_deploy' );

		return $url;

	}

	/**
	 * Tries to install the plugin
	 *
	 * @access public
	 */
	public function install(){
		$this->check_capabilities();

		$download = $_POST['download'];
		$license = $_POST['license'];

		$message = __("An Error Happened");
		$download_type = $this->_check_download($download);

		/**
		 * Throw error of the product is not free and license it empty
		 */
		if( empty( $download ) || ( empty( $license ) && $download_type !== "free" )){
			wp_send_json_error($message);
		}

		/**
		 * Install the plugin if it's free
		 */
		if( $download_type === "free" ){
			$installed = $this->_install_plugin( $download, "" );
			wp_send_json_success( $installed );
		}

		/**
		 * Check for license and then install if it's a valid licens
		 */
		if( $this->_check_license($license, $download) ){
			$installed = $this->_install_plugin( $download, $license );
			wp_send_json_success( $installed );
		}else{
			wp_send_json_error(__("Invalid license"));
		}


	}


	/**
	 * Checks license against API
	 *
	 * @param $license
	 * @param $download
	 *
	 * @return bool
	 */
	private function _check_license( $license, $download ){
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
		return $license_data->license === "valid";
	}

	/**
	 * Literally installs the plugin
	 * @param $download
	 * @param $license
	 *
	 * @return bool
	 */
	private function _install_plugin( $download,  $license){

		$api_args = array(
			'edd_action' => 'get_download',
			'item_name'  => urlencode( $download ),
			'license'	 => $license,
		);

		$download_link = add_query_arg( $api_args, $this->api_url );

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$upgrader = new Plugin_Upgrader( $skin = new Plugin_Installer_Skin( compact( 'type', 'title', 'url', 'nonce', 'plugin', 'api' ) ) );
		return $upgrader->install( $download_link );

	}


	/**
	 * Checkes download type
	 * @param $download
	 *
	 * @return string free|type2|type3
	 */
	private function _check_download($download) {
		// Check the user's capabilities before proceeding
		$this->check_capabilities();

		if( $this->is_plugin_installed( $download ) ) die( json_encode( __("Already installed") ) );

		$api_params = array(
			'edd_action' => 'check_download',
			'item_name'  => urlencode( $download ),
		);

		// Send our details to the remote server
		$request = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		$response = 'invalid';
		// There was no error, we can proceed
		if ( ! is_wp_error( $request ) ) {

			$request = maybe_unserialize( json_decode( wp_remote_retrieve_body( $request ) ) );
			$response = isset( $request->download  ) ? $request->download  : $response;

		} else {
			// Server was unreacheable
			$response = 'Server error';
		}

		return $response;

	}



	/**
	 * @deprecated
	 * This is where the fun stuff happens. (pr is it just funny stuff?)
	 * Get the file from the remote server and install it.
	 */
	public function deploy() {

		$licence = null;

		// Check if we're allowed to install plugins before proceeding
		$this->check_capabilities();

		$download = $_POST['download'];

		if ( ! $_POST['edd_deploy'] ) {
			return;
		}

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

		$upgrader = new Plugin_Upgrader( $skin = new Plugin_Installer_Skin( compact( 'type', 'title', 'url', 'nonce', 'plugin', 'api' ) ) );
		$install  = $upgrader->install( $download_link );

		if ( $install == 1 ) {

			// TODO: Install the plugin.

		}

		die();

	}


	/**
	 * Checks if plugin is intalled
	 *
	 * @param $plugin_name
	 *
	 * @return bool
	 */
	public function is_plugin_installed( $plugin_name ){

		$return = false;
		if( empty( $plugin_name ) ) return $return;

		foreach( get_plugins() as $plugin ){
			if( $plugin['Name'] === $plugin_name ){
				$return = true;
			}
		}
		return $return;
	}

	function settings_page() {

		echo '<style>.deploy-item { width: 32%; margin-right: 1%; float: left; min-width: 250px; } .deploy-item h3.hndle { padding: 0 1em 1em 1em; } .deploy-item img { width: 100%; height: auto; }</style>';

		echo '<div class="wrap">';
		echo '<h2>' . __( 'Deployer Demo', 'prefix' ) . '</h2>';

		$downloads = $this->get_downloads();

		$i = 0;

		$plugins = $downloads['plugins'];
		$themes  = $downloads['themes'];

		foreach ( $plugins as $download ) {

			if ( ! $download['bundle'] ) {

				$data_free   = (int) $download['free'];
				$disabled    = $this->is_plugin_installed( $download['title'] ) ? ' disabled="disabled" ' : '';
				$button_text = $this->is_plugin_installed( $download['title'] ) ? __( 'Installed' ) : __( 'Install' );

				$i = $i == 3 ? 0 : $i;
				if ( $i == 0 ) {
					echo '<div style="clear:both; display: block; float: none;"></div>';
				}

				echo '<div id="' . sanitize_title( $download['title'] ) . '" class="deploy-item postbox plugin">';
					echo '<h3 class="hndle"><span>' . $download['title'] . '</span></h3>';
					echo '<div class="inside">';
						echo '<div class="main">';

							if ( '' != $download['thumbnail'] ) {
								echo '<img class="deployer-item-image" src="' . $download['thumbnail'][0] . '">';
							}

							if ( '' != $download['description'] ) {
								echo '<p class="deployer-item-description">' . $download['description'] . '</p>';
							}

							echo '<p class="deployer-actions">';
								echo '<span class="spinner"></span>';
								echo '<button class="button button-primary" data-free="' . $data_free . '"' . $disabled . 'data-deploy="' . $download['title'] . '">' . $button_text . '</button>';
							echo '</p>';
						echo '</div>';
					echo '</div>';
				echo '</div>';

				$i++;

			}

		}

		echo '<div id="edd_deployer_license_thickbox" style="display:none;">';
		echo '<h3>' . __( 'Enter your license') . '</h3>';
		echo '<form action="" method="post" id="edd_deployer_license_form">';
		echo '<input style="width: 100%" type="text" id="edd_deployer_license"/>';
		echo '<button style="margin-top: 10px" type="submit" class="button button-primary">' . __( 'Submit' ) . '</button>';
		echo '</form>';
		echo '</div>';

		echo '</div>';

	}
}