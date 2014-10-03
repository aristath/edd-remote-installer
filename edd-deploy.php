<?php
/*
Plugin Name: Easy Digital Downloads - Deployer
Plugin URL: http://wpmu.io
Description: Allows remote installation of WordPress plugins and themes
Version: 1.0-dev1
Author: Aristeides Stathopoulos
Author URI: http://aristeides.com
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EDD_Deployer' ) ) {

	/**
	 * The main plugin loader class
	 */
	class EDD_Deployer {

		function __construct() {

			if ( ! defined( 'EDD_DEPLOY_PLUGIN_DIR' ) ) {
				define( 'EDD_DEPLOY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'EDD_DEPLOY_PLUGIN_URL' ) ) {
				define( 'EDD_DEPLOY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}

			if ( ! defined( 'EDD_DEPLOY_PLUGIN_FILE' ) ) {
				define( 'EDD_DEPLOY_PLUGIN_FILE', __FILE__ );
			}

			add_action( 'edd_check_download', array( $this, 'check' ) );

		}

		/**
		 * Get the price for a download
		 *
		 * @since 1.0
		 * @param int $download_id ID of the download price to show
		 * @param bool $echo Whether to echo or return the results
		 * @return void
		 * This is derived from https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/038a293103393cc25c3f4e5592b681c8c8158559/includes/download-functions.php#L155-L206
		 */
		function edd_price( $download_id = 0 ) {

			if ( empty( $download_id ) ) {
				$download_id = get_the_ID();
			}

			if ( edd_has_variable_prices( $download_id ) ) {

				$prices = edd_get_variable_prices( $download_id );

				// Return the lowest price
				$i = 0;
				foreach ( $prices as $key => $value ) {

					if ( $i < 1 ) {
						$price = $value['amount'];
					}

					if ( (float) $value['amount'] < (float) $price ) {

						$price = (float) $value['amount'];

					}
					$i++;
				}

				$price = edd_sanitize_amount( $price );

			} else {

				$price = edd_get_download_price( $download_id );

			}

			return $price;

			}

			function check( $data ) {

				$item_name  = urldecode( $data['item_name'] );
				$args       = array( 'item_name' => $item_name );
				$download   = get_page_by_title( $item_name, OBJECT, 'download' );

				if ( $download ) {

					$price  = $this->edd_price( $download->ID );
					$status = ( $price > 0 ) ? 'chargeable' : 'free';

				} else {

					$status = 'invalid';

				}

				echo json_encode( array( 'download' => $result ) );

				exit;

			}

		}

	}

}
$edd_deployer = new EDD_Deployer();
