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

function prefix_define_downloads( $downloads ) {

	// Add our plugins
	$downloads['asasasa'] = array(
		'type'        => 'plugin',
		'image'       => 'http://example.com/image.png',
		'description' => 'This is the plugin description',
	);
	$downloads['Plugin 2'] = array(
		'type'        => 'plugin',
		'image'       => 'http://example.com/image.png',
		'description' => 'This is the plugin description',
	);

	// Add our themes
	$downloads['Theme 1'] = array(
		'type'        => 'theme',
		'image'       => 'http://example.com/image.png',
		'description' => 'This is the theme description',
	);

	return $downloads;

}
add_filter( 'prefix_edd_deployer_downloads', 'prefix_define_downloads' );


class prefix_Admin_Page {

	function __construct() {

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'prefix_deployer_admin_page_content', 'prefix_deployer_admin_page_content' );

	}

	function admin_menu () {

		add_options_page( 'Deployer Demo', 'Deployer Demo', 'install_plugins', 'deployer-demo', array( $this, 'settings_page' ) );
	}

	function  settings_page () {

		$deployer = new EDD_Deploy_Client( 'http://example.com' );

		$downloads = apply_filters( 'prefix_edd_deployer_downloads', array() );
		?>

		<div class="wrap">

			<h2><?php _e( 'Deployer Demo', 'prefix' ); ?></h2>

			<?php foreach ( $downloads as $download => $value ) { ?>

				<div id="<?php echo sanitize_title( $download ); ?>" class="deploy-item postbox <?php echo $value['type']; ?>">
					<h3 class="hndle"><span><?php echo $download; ?></span></h3>
					<div class="inside">
						<div class="main">

							<?php if ( '' != $value['image'] ) : ?>
								<img class="deployer-item-image" src="<?php echo $value['image']; ?>">
							<?php endif; ?>

							<?php if ( '' != $value['description'] ) : ?>
								<p class="deployer-item-description"><?php echo $value['description']; ?></p>
							<?php endif; ?>

							<p class="deployer-actions">
								<a class="button button-primary" href="<?php echo $deployer->install_url( $value['type'], $download ); ?>">
									<?php _e( 'install', 'prefix' ); ?>
								</a>
							</p>
						</div>
					</div>
				</div>

			<?php } ?>

		</div>


		<?php

	}

}
new prefix_Admin_Page;