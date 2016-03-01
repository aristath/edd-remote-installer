<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class EDD_RI_Client
 */
class EDD_RI_Client {

	private static $instance;

	/**
	 * @return EDD_RI_Client
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof EDD_RI_Client ) ) {
			self::$instance = new EDD_RI_Client;
		}

		return self::$instance;
	}

	/**
	 * EDD_RI_Client constructor.
	 */
	public static function init() {

		add_action( 'init', array( self::get_instance(), 'client_admin' ) );
		add_action( 'plugins_loaded', array( self::get_instance(), 'server_admin' ), 10 );
		add_action( 'plugins_api', array( self::get_instance(), 'plugins_api' ), 99, 3 );
	}

	/**
	 * Instantiate the admin client class.
	 */
	public function client_admin() {

		if ( is_admin() ) {

			if ( ! class_exists( 'EDD_RI_Client_Admin' ) ) {
				include_once( dirname( __FILE__ ) . '/class-EDD_RI_Client_Admin.php' );
			}

			new EDD_RI_Client_Admin;
		}
	}

	/**
	 * Instantiate the admin server client class.
	 * The `EDD_RI_IS_SERVER` variable must be defined before `plugins_loaded` of priority '19'.
	 */
	public function server_admin() {

		if ( defined( 'EDD_RI_IS_SERVER' ) && EDD_RI_IS_SERVER ) {
			if ( ! class_exists( 'EDD_RI', false ) ) {
				require( __DIR__ . '/server/class-EDD_RI.php' );
			}

			EDD_RI::init();
		}
	}

	/**
	 * Hook into the API.
	 * Allows us to use URLs from our EDD store.
	 *
	 * @param $api
	 * @param $action
	 * @param $args
	 *
	 * @return stdClass
	 */
	public function plugins_api( $api, $action, $args ) {

		if ( 'plugin_information' == $action ) {

			if ( isset( $_POST[ 'edd_ri' ] ) && isset( $_POST[ 'edd_ri_api_url' ] ) ) {

				$api_params = array(
					'edd_action' => 'get_download',
					'item_name'  => urlencode( $_POST[ 'name' ] ),
					'license'    => isset( $_POST[ 'license' ] ) ? urlencode( $_POST[ 'license' ] ) : null,
				);

				$api                = new stdClass();
				$api->name          = $args->slug;
				$api->version       = '';
				$api->download_link = esc_url(
					add_query_arg(
						array(
							'edd_action' => 'get_download',
							'item_name'  => $api_params[ 'item_name' ],
							'license'    => $api_params[ 'license' ],
						),
						$_POST[ 'edd_ri_api_url' ]
					)
				);
			}
		}

		return $api;
	}
}
