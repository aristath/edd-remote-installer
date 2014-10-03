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

		}

	}

}
$edd_deployer = new EDD_Deployer();
