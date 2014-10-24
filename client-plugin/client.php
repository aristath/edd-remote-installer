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

if ( ! defined( 'EDD_DEPLOY_PLUGIN_URL' ) ) {
	define( 'EDD_DEPLOY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! class_exists( 'EDD_Deploy_Client' ) ) {
	include( dirname( __FILE__ ) . '/includes/class-EDD_Deploy_Client.php' );
}

function prefix_define_downloads( $downloads ) {

	// Add our plugins
	$downloads['Shoestrap Shortcodes'] = array(
		'type'        => 'plugin',
		"free"        => true,
		'image'       => 'http://dummyimage.com/600x400/333333/fff.png&text=Dummy+item+image',
		'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut ornare gravida mauris, vel vehicula purus auctor imperdiet. Maecenas a commodo magna, vel semper purus. Etiam id ipsum urna.',
	);
	$downloads['Shoestrap 3 Gridder Addon'] = array(
		'type'        => 'plugin',
		"free"        => false,
		'image'       => 'http://dummyimage.com/600x400/333333/fff.png&text=Dummy+item+image',
		'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut ornare gravida mauris, vel vehicula purus auctor imperdiet. Maecenas a commodo magna, vel semper purus. Etiam id ipsum urna.',
	);

	// Add our themes
	$downloads['Theme 1'] = array(
		'type'        => 'theme',
		'image'       => 'http://dummyimage.com/600x400/333333/fff.png&text=Dummy+item+image',
		'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut ornare gravida mauris, vel vehicula purus auctor imperdiet. Maecenas a commodo magna, vel semper purus. Etiam id ipsum urna.',
	);
	$downloads['Theme 2'] = array(
		'type'        => 'theme',
		'image'       => 'http://dummyimage.com/600x400/333333/fff.png&text=Dummy+item+image',
		'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut ornare gravida mauris, vel vehicula purus auctor imperdiet. Maecenas a commodo magna, vel semper purus. Etiam id ipsum urna.',
	);
	$downloads['Theme 3'] = array(
		'type'        => 'theme',
		'image'       => 'http://dummyimage.com/600x400/333333/fff.png&text=Dummy+item+image',
		'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut ornare gravida mauris, vel vehicula purus auctor imperdiet. Maecenas a commodo magna, vel semper purus. Etiam id ipsum urna.',
	);

	return $downloads;

}
add_filter( 'prefix_edd_deployer_downloads', 'prefix_define_downloads' );


class prefix_Admin_Page {

	private $_deployer;

	function __construct() {

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'prefix_deployer_admin_page_content', 'prefix_deployer_admin_page_content' );
		$this->_deployer = new EDD_Deploy_Client( 'http://press.codes');
		add_thickbox();
	}

	function admin_menu () {

		add_options_page( 'Deployer Demo', 'Deployer Demo', 'install_plugins', 'deployer-demo', array( $this, 'settings_page' ) );
	}

	function  settings_page () {

		$downloads = apply_filters( 'prefix_edd_deployer_downloads', array() );
		?>

		<style>.deploy-item { width: 32%; margin-right: 1%; float: left; min-width: 250px; } .deploy-item h3.hndle { padding: 0 1em 1em 1em; } .deploy-item img { width: 100%; height: auto; }</style>
		<div class="wrap">

			<h2><?php _e( 'Deployer Demo', 'prefix' ); ?></h2>

			<?php

			$i = 0;

			foreach ( $downloads as $download => $value ) {

				$i = $i == 3 ? 0 : $i;
				if ( $i == 0 ) { echo '<div style="clear:both; display: block; float: none;"></div>'; } ?>

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
								<span class="spinner"></span>
								<button class="button button-primary" data-free="<?php echo (int) $value['free']; ?>" <?php echo $this->_deployer->is_plugin_installed($download) ?  "disabled='disabled'" : "";  ?> data-deploy="<?php echo $download; ?>"><?php echo $this->_deployer->is_plugin_installed($download) ? __( 'Installed' ) : __( 'Install' ); ?></button>
							</p>
						</div>
					</div>
				</div>

			<?php $i++; } ?>

			<div id="edd_deployer_license_thickbox" style="display:none;">
				<h3><?php _e("Please provide license"); ?></h3>
				<form action="" method="post" id="edd_deployer_license_form">
						<input style="width: 100%" type="text" id="edd_deployer_license"/>

						<button style="margin-top: 10px" type="submit" class="button button-primary"><?php _e("Submit"); ?></button>
				</form>
			</div>


		</div>


		<?php

	}



}
new prefix_Admin_Page;
