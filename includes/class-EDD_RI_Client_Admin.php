<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class EDD_RI_Client_Admin
 */
class EDD_RI_Client_Admin {

	const NONCE_KEY = 'EDD_RI_Nonce';

	/**
	 * @var string
	 */
	private static $options_page;

	/**
	 * EDD_RI_Client_Admin constructor.
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_edd_ri_install', array( $this, 'edd_ri_install_download' ) );
		add_action( 'wp_ajax_edd_ri_get_downloads', array( $this, 'edd_ri_get_downloads', ) );
	}

	/**
	 * Add options page.
	 */
	public function admin_menu() {

		self::$options_page = add_plugins_page(
			__( 'EDD Remote Installer', 'edd_ri' ),
			__( 'EDD Remote Installer', 'edd_ri' ),
			'install_plugins',
			'edd-remote-installer',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Plugin settings view.
	 */
	public function settings_page() {
		include dirname( __DIR__ ) . '/views/settings-page.php';
	}

	/**
	 * Register our scripts and style and enqueue them only on our settings page.
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		wp_register_script( 'edd_ri', EDD_RI_PLUGIN_URL . 'assets/js/edd-ri.js', array( 'jquery' ), EDD_RI_VERSION, true );
		wp_localize_script( 'edd_ri', 'edd_ri', array(
			'default_api_url' => apply_filters( 'edd_ri_default_api_url', '-' ),
			'api_urls'        => EDD_RI_PLUGIN_URL . 'api_urls.json',
			'nonce'           => wp_create_nonce( self::NONCE_KEY . basename( __FILE__ ) ),
		) );

		wp_register_style( 'edd_ri_bulma', EDD_RI_PLUGIN_URL . 'assets/css/bulma.css', array(), EDD_RI_VERSION );
		wp_register_style( 'edd_ri', EDD_RI_PLUGIN_URL . 'assets/css/style.css', array( 'edd_ri_bulma' ), EDD_RI_VERSION );

		if ( self::$options_page !== $hook ) {
			return;
		}

		wp_enqueue_script( 'edd_ri' );
		wp_enqueue_style( array( 'edd_ri' ) );
		add_thickbox();
	}

	/**
	 * Tries to install the plugin.
	 */
	public function edd_ri_install_download() {

		check_ajax_referer( self::NONCE_KEY . basename( __FILE__ ), 'nonce' );

		if ( ! $this->current_user_can_install_plugins() ) {
			wp_send_json_error();
		}

		$data          = array_map( 'sanitize_text_field', $_POST );
		$download      = $data[ 'download' ];
		$license       = $data[ 'license' ];
		$api_url       = esc_url( $data[ 'api_url' ] );
		$message       = __( 'An Error Occurred', 'edd_ri' );
		$download_type = $this->_check_download( $download, $api_url );

		/**
		 * Throw error of the product is not free and license it empty
		 */
		if ( empty( $download ) || ( empty( $license ) && 'free' !== $download_type ) ) {
			wp_send_json_error( $message );
		}

		/**
		 * Install the plugin if it's free
		 */
		if ( 'free' === $download_type ) {
			$installed = $this->_install_plugin( $download, "", $api_url );
			wp_send_json_success( $installed );
		}

		/**
		 * Check for license and then install if it's a valid license
		 */
		if ( $this->_check_license( $license, $download, $api_url ) ) {
			$installed = $this->_install_plugin( $download, $license, $api_url );
			wp_send_json_success( $installed );
		} else {
			wp_send_json_error( __( 'Invalid License', 'edd_ri' ) );
		}
	}

	/**
	 *
	 */
	public function edd_ri_get_downloads() {

		check_ajax_referer( self::NONCE_KEY . basename( __FILE__ ), 'nonce' );

		$data = array_map( 'sanitize_text_field', $_POST );

		if ( empty( $data[ 'api_url' ] ) ) {
			wp_send_json_error();
		}

		$downloads = $this->get_downloads( $data[ 'api_url' ] );

		if ( is_array( $downloads ) ) {
			$html = $this->get_downloads_html( $downloads[ 'plugins' ], $data[ 'api_url' ] );
			$html .= $this->get_downloads_html( $downloads[ 'themes' ], $data[ 'api_url' ] );
			wp_send_json_success( $html );
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Checks license against API
	 *
	 * @param string $license
	 * @param string $download
	 * @param string $api_url
	 *
	 * @return bool
	 */
	private function _check_license( $license, $download, $api_url ) {

		$api_args = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => urlencode( $download ),
		);

		// Get a response from our EDD server
		$response = wp_remote_get( add_query_arg( $api_args, $api_url ),
			array(
				'timeout'   => 15,
				'sslverify' => false,
			) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return false;
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		return $license_data->license === 'valid';
	}

	/**
	 * Literally installs the plugin
	 *
	 * @param string $download
	 * @param string $license
	 * @param string $api_url
	 *
	 * @return bool
	 */
	private function _install_plugin( $download, $license, $api_url ) {

		$api_args = array(
			'edd_action' => 'get_download',
			'item_name'  => urlencode( $download ),
			'license'    => $license,
		);

		$download_link = add_query_arg( $api_args, $api_url );

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$upgrader = new Plugin_Upgrader( $skin = new Plugin_Installer_Skin(
			compact( 'type', 'title', 'url', 'nonce', 'plugin', 'api' ) ) );

		return $upgrader->install( $download_link );
	}

	/**
	 * Checks download type
	 *
	 * @param string $download
	 * @param string $api_url
	 *
	 * @return string free|type2|type3
	 */
	private function _check_download( $download, $api_url ) {

		$response = 'invalid';

		if ( ! $this->current_user_can_install_plugins() ) {
			return $response;
		}

		if ( $this->is_plugin_installed( $download ) ) {
			die( json_encode( __( 'Already Installed', 'edd_ri' ) ) );
		}

		$api_params = array(
			'edd_action' => 'check_download',
			'item_name'  => urlencode( $download ),
		);

		// Send our details to the remote server
		$request = wp_remote_post( esc_url_raw( $api_url ), array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params,
		) );

		// There was no error, we can proceed
		if ( ! is_wp_error( $request ) ) {
			$request  = maybe_unserialize( json_decode( wp_remote_retrieve_body( $request ) ) );
			$response = isset( $request->download ) ? $request->download : $response;
		} else {
			$response = 'Server error'; // Server was unreachable
		}

		return $response;
	}

	/**
	 * Checks if plugin is installed
	 *
	 * @param string $plugin_name
	 *
	 * @return bool
	 */
	public function is_plugin_installed( $plugin_name ) {

		$return = false;

		if ( empty( $plugin_name ) ) {
			return $return;
		}

		foreach ( get_plugins() as $plugin ) {

			if ( $plugin[ 'Name' ] === $plugin_name ) {
				$return = true;
				break;
			}
		}

		return $return;
	}

	/**
	 * Check if the user has the `install_plugins` cap.
	 *
	 * @return bool
	 */
	private function current_user_can_install_plugins() {
		return current_user_can( 'install_plugins' );
	}

	/**
	 * @param string $api_url
	 *
	 * @return mixed
	 */
	private function get_downloads( $api_url ) {

		$domain    = sanitize_key( $api_url );
		$trans_key = $this->get_transient_key( $domain );

		/**
		 * Get the cache from the transient.
		 * If the cache does not exist, get the json and save it as a transient.
		 */
		if ( false === ( $downloads = get_transient( $trans_key ) ) ) {

			$api_params = array( 'edd_action' => 'get_downloads' );
			$response   = wp_remote_post(
				esc_url_raw( add_query_arg( $api_params, $api_url ) ),
				array(
					'timeout'    => 15,
					'sslverify'  => false,
					'user-agent' => 'EDD_RI/' . EDD_RI_VERSION . '; ' . get_bloginfo( 'url' ),
				)
			);

			if ( is_wp_error( $response ) ) {
				return '';
			}

			if ( isset( $response[ 'body' ] ) && strlen( $response[ 'body' ] ) > 0 ) {

				$downloads = json_decode( wp_remote_retrieve_body( $response ), true );

				/**
				 * Sometimes (if the `server` class isn't activated the remote call returns a 'null'
				 * value. This is because it's not in the JSON format. I've thought about not caching this
				 * response, but I chose to cache the call for a lesser time to avoid hitting the URL
				 * to many times.
				 */
				$expiration = empty( $downloads ) ? DAY_IN_SECONDS : MONTH_IN_SECONDS;
				set_transient( $trans_key, $downloads, $expiration );
			}
		}

		return $downloads;
	}

	/**
	 * @link http://stackoverflow.com/a/8753834
	 *
	 * @param array $downloads
	 * @param string $api_url
	 *
	 * @return string
	 */
	private function get_downloads_html( array $downloads, $api_url ) {

		ob_start();

		$i = 1;

		if ( ! empty( $downloads ) ) {

			foreach ( $downloads as $download ) {

				if ( ! $download[ 'bundle' ] ) {
					$data_free   = (int) $download[ 'free' ];
					$disabled    = $this->is_plugin_installed( $download[ 'title' ] ) ?
						' disabled="disabled" ' : '';
					$button_text = $this->is_plugin_installed( $download[ 'title' ] ) ?
						__( 'Installed', 'edd_ri' ) : __( 'Install', 'edd_ri' );

					echo $i % 3 == 1 ? '<div class="section group">' : '';
					?>
					<div id="<?php echo sanitize_title( $download[ 'title' ] ); ?>"
					     class="col span_1_of_3 edd-ri-item postbox plugin">
						<h3 class="hndle"><span><?php echo $download[ 'title' ]; ?></span></h3>
						<div class="inside">
							<div class="main">
								<?php if ( '' != $download[ 'thumbnail' ] ) : ?>
									<img class="edd-ri-item-image"
									     src="<?php echo $download[ 'thumbnail' ][ 0 ]; ?>">
								<?php endif; ?>

								<?php if ( '' != $download[ 'description' ] ) : ?>
									<p class="edd-ri-item-description"><?php echo $download[ 'description' ]; ?></p>
								<?php endif; ?>

								<p class="edd-ri-actions">
									<span class="spinner"></span>
									<button class="button button-primary"
									        data-free="<?php echo $data_free; ?>"<?php echo $disabled; ?>
									        data-edd-ri="<?php echo $download[ 'title' ]; ?>">
										<?php echo $button_text; ?></button>
									<a class="button" target="_blank"
									   href="<?php echo esc_url( add_query_arg( array( 'p' => $download[ 'id' ] ),
										   $api_url ) ); ?>"><?php _e( 'Details', 'edd_ri' ); ?></a>
								</p>
							</div>
						</div>
					</div>
					<?php
					echo $i % 3 == 0 ? '</div>' : '';
					$i ++;
				}
			}
			echo $i % 3 != 1 ? '</div>' : '';
		}

		return ob_get_clean();
	}

	/**
	 * Get's the cached transient key.
	 *
	 * @param string $input
	 *
	 * @return string
	 */
	private function get_transient_key( $input ) {

		$len = is_multisite() ? 40 : 45;
		$key = 'edd_ri_' . $input . '_';
		$key = $key . substr( md5( $input ), 0, $len - strlen( $key ) );

		return $key;
	}
}
