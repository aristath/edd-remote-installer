<?php

/*
Plugin Name: Easy Digital Downloads - Deployer (client demo plugin)
Plugin URL: http://wpmu.io
Description: Allows remote installation of WordPress plugins and themes
Version: 1.0-alpha1
Author: Aristeides Stathopoulos
Author URI: http://aristeides.com
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'EDD_DEPLOY_PLUGIN_URL' ) ) {
	define( 'EDD_DEPLOY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! class_exists( 'EDD_Deploy_Client' ) ) {
	include( dirname( __FILE__ ) . '/includes/class-EDD_Deploy_Client.php' );
}

new EDD_Deploy_Client( 'http://press.codes' );
