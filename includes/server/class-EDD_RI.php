<?php

defined( 'ABSPATH' ) || exit;

/**
 * The main remote installer class
 *
 * Class EDD_RI
 */
class EDD_RI {

	const SETTING_ID = 'edd_ri_settings';

	private static $instance;

	/**
	 * @return EDD_RI
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof EDD_RI ) ) {
			self::$instance = new EDD_RI;
			self::$instance->add_hooks();
		}

		return self::$instance;
	}

	/**
	 * The main method responsible for returning the one true
	 * instance if all dependency checks are met.
	 *
	 * @return  EDD_RI
	 */
	public static function init() {
		if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
			if ( ! class_exists( 'EDD_Extension_Activation' ) ) {
				require_once __DIR__ . '/class-EDD_Extension_Activation.php';
			}
			$activation = new EDD_Extension_Activation( plugin_dir_path( dirname( __FILE__ ) ), basename( dirname( __FILE__ ) ) );
			$activation->run();
		} else {
			return self::get_instance();
		}
	}

	/**
	 * Add class hooks.
	 */
	private function add_hooks() {

		add_action( 'init', array( $this, 'load_admin' ), 0 );
		add_action( 'edd_check_download', array( $this, 'check_download' ) );
		add_action( 'edd_get_download', array( $this, 'get_download' ) );
		add_action( 'edd_get_downloads', array( $this, 'get_downloads' ) );
	}

	/**
	 * Add admin class.
	 */
	public function load_admin() {

		if ( is_admin() ) {
			if ( ! class_exists( 'EDD_RI_Admin', false ) ) {
				require( __DIR__ . '/class-EDD_RI_Admin.php' );
			}

			EDD_RI_Admin::init();
		}
	}

	/**
	 * Get a json array of our downloads
	 *
	 * @param array $_post Incoming $_POST params
	 */
	public function get_downloads( array $_post ) {

		if ( ! stristr( $_SERVER[ 'HTTP_USER_AGENT' ], 'EDD_RI' ) || 1 != self::get_option( 'edd_ri_enable' ) ) {
			wp_send_json_error( __( 'Illegal api call', 'edd_ri' ) );
		}

		$allowed = array(
			'taxonomy' => array(
				'download_category',
				'download_tag',
			),
			'terms',
		);

		$data = array_map( 'sanitize_text_field', $_post );

		$downloads = array();

		$use_tags    = (bool) self::get_option( 'edd_ri_download_tags_enable' );
		$taxonomy    = $use_tags ? 'download_tag' : 'download_category';

		if ( '-' !== ( $plugin_term = self::get_option( 'edd_ri_plugins_select', '-' ) ) ) {

			$plugins_query_args = array(
				'post_type'      => 'download',
				'posts_per_page' => - 1,
				'tax_query'      => array(
					array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $plugin_term,
					),
				),
			);

			$plugins = get_posts( $plugins_query_args );

			if ( ! empty( $plugins ) ) {
				foreach ( $plugins as $plugin ) {

					$downloads[ 'plugins' ][] = array(
						'id'          => $plugin->ID,
						'title'       => $plugin->post_title,
						'description' => $plugin->post_excerpt,
						'bundle'      => edd_is_bundled_product( $plugin->ID ) ? 1 : 0,
						'price'       => edd_price( $plugin->ID, false ),
						'free'        => 0 != $this->edd_price( $plugin->ID ) ? 0 : 1,
						'thumbnail'   => wp_get_attachment_image_src( get_post_thumbnail_id( $plugin->ID ), 'full' ),
					);
				}
			}

			wp_reset_postdata();
		}

		if ( '-' !== ( $theme_term = self::get_option( 'edd_ri_themes_select', '-' ) ) ) {

			$themes_query_args = array(
				'post_type'      => 'download',
				'posts_per_page' => - 1,
				'tax_query'      => array(
					array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $theme_term,
					),
				),
			);

			$themes = get_posts( $themes_query_args );

			if ( ! empty( $themes ) ) {
				foreach ( $themes as $theme ) {

					$downloads[ 'themes' ][] = array(
						'id'          => $theme->ID,
						'title'       => $theme->post_title,
						'description' => $theme->post_excerpt,
						'bundle'      => edd_is_bundled_product( $theme->ID ) ? 1 : 0,
						'price'       => edd_price( $theme->ID, false ),
						'free'        => 0 != $this->edd_price( $theme->ID ) ? 0 : 1,
						'thumbnail'   => wp_get_attachment_image_src( get_post_thumbnail_id( $theme->ID ), 'full' ),
					);
				}
			}

			wp_reset_postdata();
		}

		if ( empty( $downloads ) ) {
			wp_send_json_error( __( 'No downloads found.', 'edd_ri' ) );
		}

		die( wp_json_encode( $downloads ) );
	}

	/**
	 * Get the price for a download
	 * Derived from
	 * https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/038a293103393cc25c3f4e5592b681c8c8158559/includes/download-functions.php#L155-L206
	 *
	 * @since 1.0
	 *
	 * @param int $download_id ID of the download price to show
	 *
	 * @return string
	 */
	private function edd_price( $download_id = 0 ) {

		if ( empty( $download_id ) ) {
			$download_id = get_the_ID();
		}

		$price = null;

		if ( edd_has_variable_prices( $download_id ) ) {

			$prices = edd_get_variable_prices( $download_id );

			// Return the lowest price
			$i = 0;
			foreach ( $prices as $key => $value ) {

				if ( $i < 1 ) {
					$price = $value[ 'amount' ];
				}

				if ( (float) $value[ 'amount' ] < (float) $price ) {
					$price = (float) $value[ 'amount' ];
				}

				$i ++;
			}

			$price = edd_sanitize_amount( $price );
		} else {
			$price = edd_get_download_price( $download_id );
		}

		return $price;
	}

	/**
	 * Check the status of the download
	 *
	 * @param array $data
	 */
	public function check_download( array $data ) {

		$download = get_page_by_title( urldecode( $data[ 'item_name' ] ), OBJECT, 'download' );

		if ( $download ) {

			$price  = $this->edd_price( $download->ID );
			$result = ( $price > 0 ) ? 'billable' : 'free';
		} else {
			$result = 'invalid';
		}

		echo json_encode( array( 'download' => $result ) );
		exit;
	}

	/**
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function get_download( array $data ) {

		$item_name = urldecode( $data[ 'item_name' ] );

		$args = array();

		$args[ 'item_name' ] = $item_name;
		$download_object     = get_page_by_title( $item_name, OBJECT, 'download' );
		$download_id         = $download_object->ID;
		$price               = $this->edd_price( $download_id );

		$user_info = array();

		$user_info[ 'email' ] = 'Remote-Installer';
		$user_info[ 'id' ]    = 'Remote-Installer';
		$payment              = - 1;

		if ( $price > 0 ) {

			$args[ 'key' ] = urldecode( $data[ 'license' ] );
			$edd_sl        = function_exists( 'edd_software_licensing' ) ? edd_software_licensing() : false;

			if ( false === $edd_sl ) {
				die( 'error' );
			}

			$status = $edd_sl->check_license( $args );

			if ( 'valid' != $status ) {
				die( $status );
			}

			$license_id = $edd_sl->get_license_by_key( $args[ 'key' ] );
			$payment_id = get_post_meta( $license_id, '_edd_sl_payment_id', true );
			$user_info  = edd_get_payment_meta_user_info( $payment_id );
		}

		$download_files = edd_get_download_files( $download_id );

		$file = apply_filters( 'edd_requested_file', $download_files[ 0 ][ 'file' ], $download_files, '' );

		$this->build_file( $file );

		edd_record_download_in_log( $download_id, '', $user_info, edd_get_ip(), $payment );
		exit;
	}

	/**
	 * @param null $file
	 */
	private function build_file( $file = null ) {

		if ( null === $file ) {
			return;
		}

		$requested_file = $file;

		$file_ext = edd_get_file_extension( $file );
		$ctype    = edd_get_file_ctype( $file_ext );

		if ( ! edd_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
			set_time_limit( 0 );
		}

		/// Less than PHP version 5.4
		if ( version_compare( PHP_VERSION, '5.4', '<' ) ) {
			/**
			 * @deprecated 5.4 This function has been DEPRECATED as of PHP 5.4.0. Raises an E_CORE_ERROR.
			 */
			if ( function_exists( 'get_magic_quotes_runtime' ) && get_magic_quotes_runtime() ) {
				set_magic_quotes_runtime( 0 );
			}
		}

		session_write_close();

		if ( function_exists( 'apache_setenv' ) ) {
			apache_setenv( 'no-gzip', 1 );
		}

		ini_set( 'zlib.output_compression', 'Off' );

		nocache_headers();

		header( 'Robots: none' );
		header( 'Content-Type: ' . $ctype );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename="' . apply_filters( 'edd_requested_file_name', basename( $file ) ) . '";' );
		header( 'Content-Transfer-Encoding: binary' );

		$path = realpath( $file );

		if ( false === filter_var( $file, FILTER_VALIDATE_URL ) && file_exists( $path ) ) {
			readfile( $path );
		} elseif ( strpos( $file, WP_CONTENT_URL ) !== false ) {

			$upload_dir = wp_upload_dir();

			$path = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $file );
			$path = realpath( $path );

			if ( file_exists( $path ) ) {
				readfile( $path );
			} else {
				header( 'Location: ' . $file );
			}
		} else {
			header( 'Location: ' . $file );
		}
	}

	/**
	 * @param string $option_key
	 * @param bool $default
	 *
	 * @return mixed
	 */
	public static function get_option( $option_key = '', $default = false ) {

		$options = get_option( self::SETTING_ID, array() );

		if ( isset( $options[ $option_key ] ) ) {
			return $options[ $option_key ];
		} else {
			return $default;
		}
	}
}
