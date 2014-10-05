<?php

/*
Plugin Name: Easy Digital Downloads - Deployer (client demo plugin)
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

if ( ! class_exists( 'EDD_Deploy_Client' ) ) {
	include( dirname( __FILE__ ) . '/includes/class-EDD_Deploy_Client.php' );
}

function prefix_define_downloads() {

	$downloads = array(
		'plugins' => array(
			'Plugin 1',
			'Plugin 2',
			'Plugin 2',
		),
		'themes'  => array(
			'Theme 1',
			'Theme 2',
			'Theme 3',
		),
	);

	return $downloads;

}
add_filter( 'prefix_edd_deployer_downloads', 'prefix_define_downloads' );

function 