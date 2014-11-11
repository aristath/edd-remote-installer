<?php
/*
Plugin Name: EDD - Remote Installer Server
Plugin URL: http://press.codes
Description: Allows remote installation of WordPress plugins and themes. This is the server-side plugin.
Version: 1.0-alpha1
Author: Aristeides Stathopoulos
Author URI: http://aristeides.com
*/
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EDD_RI' ) ) {
	require( dirname( __FILE__ ) . '/includes/class-EDD_RI.php' );
}
if ( ! class_exists( 'EDD_RI_Admin' ) ) {
	require( dirname( __FILE__ ) . '/includes/class-EDD_RI_Admin.php' );
}

function edd_ri_instantiate() {
	return EDD_RI::instance();
}
edd_ri_instantiate();

$edd_ri = new EDD_RI_Admin();
