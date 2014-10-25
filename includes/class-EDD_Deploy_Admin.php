<?php

/**
* The EDD Deployer admin page
*/
class EDD_Deploy_Admin {

	function __construct() {

		// Add the admin menu
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		// Initialize the settings the settings
		add_action( 'admin_init', array( $this, 'settings_init' ) );

	}

	/**
	 * Create the admin menu
	 */
	function add_admin_menu() { 
		add_options_page( 'EDD Deployer', 'EDD Deployer', 'manage_options', 'edd_deployer', array( $this, 'options_page' ) );
	}

	/**
	 * Initialize settings
	 */
	function settings_init() { 

		register_setting( 'edd_deploy_settings', 'edd_deploy_settings' );

		add_settings_section( 'edd_deploy', __( 'EDD Deployer Settings', 'edd_deploy' ), array( $this, 'callback' ), 'edd_deploy_settings' );
		add_settings_field( 'edd_deploy_enable', __( 'Enable/Disable the json feed', 'edd_deploy' ), array( $this, 'enable_render' ), 'edd_deploy_settings', 'edd_deploy' );
		add_settings_field( 'edd_deploy_plugins_select', __( 'Select plugins category', 'edd_deploy' ), array( $this, 'plugins_select_render' ), 'edd_deploy_settings', 'edd_deploy' );
		add_settings_field( 'edd_deploy_themes_select', __( 'Select themes category', 'edd_deploy' ), array( $this, 'themes_select_render' ), 'edd_deploy_settings', 'edd_deploy' );

	}

	/**
	 * Render the enable/disable control
	 */
	function enable_render() { 

		$options = get_option( 'edd_deploy_settings' );	?>
		<input type='checkbox' name='edd_deploy_settings[edd_deploy_enable]' <?php checked( $options['edd_deploy_enable'], 1 ); ?> value='1'>
		<?php

	}

	/**
	 * Render the plugin category selection dropdown
	 */
	function plugins_select_render() { 

		$options = get_option( 'edd_deploy_settings' );
		$terms   = get_terms( 'download_category', $terms_args );
		?>

		<select name="edd_deploy_settings[edd_deploy_plugins_select]">
		
			<?php foreach ( $terms as $term ) : ?>
				<option value="<?php echo $term->term_id; ?>" <?php selected( $options['edd_deploy_plugins_select'], $term->term_id ); ?>><?php echo $term->name; ?></option>
			<?php endforeach; ?>

		</select>

		<?php

	}

	/**
	 * Render the plugin category selection dropdown
	 */
	function themes_select_render() { 

		$options = get_option( 'edd_deploy_settings' );
		$terms   = get_terms( 'download_category', $terms_args );
		?>

		<select name="edd_deploy_settings[edd_deploy_themes_select]">
		
			<?php foreach ( $terms as $term ) : ?>
				<option value="<?php echo $term->term_id; ?>" <?php selected( $options['edd_deploy_themes_select'], $term->term_id ); ?>><?php echo $term->name; ?></option>
			<?php endforeach; ?>

		</select>

		<?php

	}

	/**
	 * Add the description
	 */
	function callback() { 
		_e( 'Below you can edit your settings for EDD-Deployer. By selecting a category for plugins and themes you\'re limiting the products that will be displayed to clients. This is a necessary step if you\'re selling both plugins and themes so that themes can use the theme installer instead of the plugin installer.', 'edd_deploy' );
	}

	/**
	 * Create the page content
	 */
	function options_page() { 

		?>
		<form action='options.php' method='post'>
			
			<h2><?php _e( 'EDD Deployer', 'edd_deploy' ); ?></h2>
			
			<?php
			settings_fields( 'edd_deploy_settings' );
			do_settings_sections( 'edd_deploy_settings' );
			submit_button();
			?>
			
		</form>
		<?php

	}

}