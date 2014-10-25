<?php

/**
* The main deployer class
*/
class EDD_Deploy {

	private static $instance;

	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof EDD_Deployer ) ) {
			self::$instance = new EDD_Deploy;
			self::$instance->runner();
		}

		return self::$instance;

	}

	function runner() {

		add_action( 'edd_check_download', array( $this, 'check_download' ) );
		add_action( 'edd_get_download',   array( $this, 'get_download' ) );
		add_action( 'edd_get_downloads',  array( $this, 'get_downloads' ) );

	}

	/**
	* Get a json array of our downloads
	*/
	function get_downloads() {

		$options = get_option( 'edd_deploy_settings' );

		$plugins_query_args = array(
			'post_type'        => 'download',
			'posts_per_page'   => -1,
			'tax_query'        => array(
				array(
					'taxonomy' => 'download_category',
					'field'    => 'id',
					'terms'    => $options['edd_deploy_plugins_select']
				)
			)
		);

		$themes_query_args = array(
			'post_type'        => 'download',
			'posts_per_page'   => -1,
			'tax_query'        => array(
				array(
					'taxonomy' => 'download_category',
					'field'    => 'id',
					'terms'    => $options['edd_deploy_themes_select']
				)
			)
		);

		$plugins   = get_posts( $plugins_query_args );
		$themes    = get_posts( $themes_query_args );
		$downloads = array();

		foreach ( $plugins as $plugin ) {

			$downloads['plugins'][] = array(
				'id'          => $plugin->ID,
				'title'       => $plugin->post_title,
				'description' => $plugin->post_excerpt,
				'bundle'      => edd_is_bundled_product( $plugin->ID ) ? 1 : 0,
				'price'       => edd_price( $plugin->ID, false ),
				'free'        => 0 != $this->edd_price( $plugin->ID ) ? 0 : 1,
				'thumbnail'   => wp_get_attachment_image_src( get_post_thumbnail_id( $plugin->ID ), 'full' ),
			);

		}

		foreach ( $themes as $theme ) {

			$downloads['themes'][] = array(
				'id'          => $theme->ID,
				'title'       => $theme->post_title,
				'description' => $theme->post_excerpt,
				'bundle'      => edd_is_bundled_product( $theme->ID ) ? 1 : 0,
				'price'       => edd_price( $theme->ID, false ),
				'free'        => 0 != $this->edd_price( $theme->ID ) ? 0 : 1,
				'thumbnail'   => wp_get_attachment_image_src( get_post_thumbnail_id( $theme->ID ), 'full' ),
			);

		}

		echo json_encode( $downloads );

		exit;

	}

	/**
	* Get the price for a download
	* Derived from https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/038a293103393cc25c3f4e5592b681c8c8158559/includes/download-functions.php#L155-L206
	*
	* @since 1.0
	* @param int $download_id ID of the download price to show
	* @param bool $echo Whether to echo or return the results
	* @return void
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

	/**
	* Check the status of the download
	*/
	function check_download( $data ) {

		$download = get_page_by_title( urldecode( $data['item_name'] ), OBJECT, 'download' );

		if ( $download ) {

			$price  = $this->edd_price( $download->ID );
			$result = ( $price > 0 ) ? 'billable' : 'free';

		} else {

			$result = 'invalid';

		}

		echo json_encode( array( 'download' => $result ) );

		exit;

	}


	function get_download( $data ) {

		$item_name 	= urldecode( $data['item_name'] );

		$args = array();

		$args['item_name'] = $item_name;
		$download_object   = get_page_by_title( $item_name, OBJECT, 'download' );
		$download          = $download_object->ID;
		$price             = $this->edd_price( $download );

		$user_info = array();

		$user_info['email'] = 'Deployer';
		$user_info['id']    = 'Deployer';
		$payment            = -1;

		if ( $price > 0 ) {

			$args['key'] = urldecode( $data['license'] );
			$edd_sl      = EDD_Software_Licensing();
			$status      = $edd_sl->check_license( $args );

			if ( 'valid' != $status ) {
				return $status;
			}

			$license_id = $edd_sl->get_license_by_key( $args['key'] );
			$payment_id = get_post_meta( $license_id, '_edd_sl_payment_id', true );
			$user_info  = edd_get_payment_meta_user_info( $payment_id );

		}

		$download_files = edd_get_download_files( $download );

		$file = apply_filters( 'edd_requested_file', $download_files[0]['file'], $download_files, $key );

		$this->build_file( $file );

		edd_record_download_in_log( $download, $key, $user_info, edd_get_ip(), $payment );

		exit;

	}

	function build_file( $file = null ) {

		if ( null == $file ) {
			return;
		}

		$requested_file = $file;

		$file_ext = edd_get_file_extension( $file );
		$ctype    = edd_get_file_ctype( $file_ext );

		if ( ! edd_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
			set_time_limit( 0 );
		}

		if ( function_exists( 'get_magic_quotes_runtime' ) && get_magic_quotes_runtime() ) {
			set_magic_quotes_runtime( 0 );
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

}
