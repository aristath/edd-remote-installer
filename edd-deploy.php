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

	private static $instance;

	/**
	 * The main plugin loader class
	 */
	class EDD_Deployer {

		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof EDD_Deployer ) ) {
				self::$instance = new EDD_Deployer;
				self::$instance->runner();
			}

			return self::$instance;
		}

		function runner() {

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
			add_action( 'edd_get_download', array( $this, 'get_file' ) );

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

			function data_to_object( $data ) {

				$item_name  = urldecode( $data['item_name'] );
				$args       = array( 'item_name' => $item_name );
				$download   = get_page_by_title( $item_name, OBJECT, 'download' );

				return $download;

			}

			function check( $data ) {

				$download = $this->data_to_object( $data );

				if ( ! $download ) {

					$status = 'invalid';

				} else {

					$status = ( 0 < $this->edd_price( $download->ID ) ) ? 'chargeable' : 'free';

				}

				echo json_encode( array( 'download' => $result ) );

				exit;

			}

		}

		function system_init() {

			if ( ! edd_is_func_disabled( 'set_time_limit' ) ) {
				set_time_limit( 0 );
			}
			if ( function_exists( 'get_magic_quotes_runtime' ) && get_magic_quotes_runtime() ) {
				set_magic_quotes_runtime(0);
			}

			@session_write_close();
			if ( function_exists( 'apache_setenv' ) ) @apache_setenv( 'no-gzip', 1 );
			@ini_set( 'zlib.output_compression', 'Off' );

		}

		function file_headers( $file = false, $ctype ) {

			// Do not proceed if no file has been specified
			if ( ! $file ) {
				return;
			}

			nocache_headers();
			header( 'Robots: none' );
			header( 'Content-Type: ' . $ctype );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename="' . apply_filters( 'edd_requested_file_name', basename( $file ) ) . '";' );
			header( 'Content-Transfer-Encoding: binary' );

		}

		function get_file( $data ) {

			$download = $this->data_to_object( $data );
			$file_key = get_post_meta( $download->ID, '_edd_sl_upgrade_file_key', true );


			$user = array();

			if ( 0 !< $this->edd_price( $download->ID ) ) {

				$user['email'] = 'EDD-Deployer';
				$user['id']    = 'EDD-Deployer';

			} else {

				$args = array(
					'key'      => urlencode( $data['license'] ),
					'item_key' => urlencode( $data['item_name'] ),
				)

				$edd_sl = edd_software_licensing();
				$status = $edd_sl->check_license( $args );

				if ( 'valid' != $status ) {
					return $status;
				}

				$user = edd_get_payment_meta_user_info( get_post_meta( $edd_sl->get_license_by_key( $args['key'] ), '_edd_sl_payment_id', true ) );

			}

			edd_record_download_in_log( $download->ID, $file_key, $user, edd_get_ip(), -1 );
			$files     = edd_get_download_files( $download->ID );
			$file      = apply_filters( 'edd_requested_file', $files[$file_key]['file'], $files, $file_key );
			$filename  = $files[$file_key]['name'];

			$extension = edd_get_file_extension( $file );
			$ctype     = edd_get_file_ctype( $extension );

			$this->system_init();
			$this->file_headers( $file, $ctype );

			$path = realpath( $file );

			if ( file_exists( $path ) ) {

				readfile( $path );

			} elseif ( strpos( $file, WP_CONTENT_URL ) {

				$upload_dir = wp_upload_dir();
				$path = realpath( $path );

				if ( ! file_exists( $path ) ) {

					header( 'Location: ' . $file );

				} else {

					readfile( $path );

				}

			} else {

				header( 'Locaion: ' $file );
			}

			exit;

		}

	}

}
$edd_deployer = EDD_Deployer::instance();
