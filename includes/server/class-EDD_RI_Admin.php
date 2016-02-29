<?php

defined( 'ABSPATH' ) || exit;

/**
 * The EDD Remote Installer admin page
 *
 * Class EDD_RI_Admin
 */
class EDD_RI_Admin {

	private static $instance;

	/**
	 * @return EDD_RI_Admin
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof EDD_RI_Admin ) ) {
			self::$instance = new EDD_RI_Admin;
		}

		return self::$instance;
	}

	/**
	 * EDD_RI_Admin constructor.
	 */
	public static function init() {

		// Add the admin menu
		add_action( 'admin_menu', array( self::get_instance(), 'add_admin_menu' ) );

		// Initialize the settings the settings
		add_action( 'admin_init', array( self::get_instance(), 'settings_init' ) );
	}

	/**
	 * Create the admin menu
	 */
	public function add_admin_menu() {
		add_options_page( 'EDD Remote Installer', 'EDD Remote Installer', 'manage_options', 'edd_ri', array(
			$this,
			'options_page',
		) );
	}

	/**
	 * Initialize settings
	 */
	public function settings_init() {

		register_setting( 'edd_ri_settings', 'edd_ri_settings' );

		add_settings_section( 'edd_ri', __( 'EDD Remote Installer Settings', 'edd_ri' ), array(
			$this,
			'callback',
		), 'edd_ri_settings' );

		add_settings_field( 'edd_ri_enable', __( 'Enable/Disable the json feed', 'edd_ri' ), array(
			$this,
			'enable_render',
		), 'edd_ri_settings', 'edd_ri' );

		add_settings_field( 'edd_ri_plugins_select', __( 'Select plugins category', 'edd_ri' ), array(
			$this,
			'plugins_select_render',
		), 'edd_ri_settings', 'edd_ri' );

//		add_settings_field( 'edd_ri_download_tags_enable', __( 'Enable/Disable queries by tags', 'edd_ri' ), array(
//			$this,
//			'enable_render',
//		), 'edd_ri_settings', 'edd_ri' );

//		add_settings_field( 'edd_ri_tags_select', __( 'Select download tag', 'edd_ri' ), array(
//			$this,
//			'tags_select_render',
//		), 'edd_ri_settings', 'edd_ri' );

		add_settings_field( 'edd_ri_themes_select', __( 'Select themes category', 'edd_ri' ), array(
			$this,
			'themes_select_render',
		), 'edd_ri_settings', 'edd_ri' );

	}

	/**
	 * Render the enable/disable control
	 */
	public function enable_render() {
		?>
		<input type='checkbox' name='edd_ri_settings[edd_ri_enable]'
			<?php checked( EDD_RI::get_option( 'edd_ri_enable' ), 1 ); ?> value='1'>
		<?php
	}

	/**
	 * Render the plugin category selection dropdown
	 */
	public function plugins_select_render() {

		$terms = $this->get_download_terms();
		?>
		<select name="edd_ri_settings[edd_ri_plugins_select]">
			<?php foreach ( $terms as $term ) : ?>
				<option
					value="<?php echo $term->term_id; ?>"
					<?php selected( EDD_RI::get_option( 'edd_ri_plugins_select' ), $term->term_id ); ?>>
					<?php echo $term->name; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render the plugin tag selection dropdown
	 */
	public function tags_select_render() {

		$terms = $this->get_download_terms( 'download_tag' );
		?>
		<select name="edd_ri_settings[edd_ri_tags_select]">
			<?php foreach ( $terms as $term ) : ?>
				<option
					value="<?php echo $term->term_id; ?>"
					<?php selected( EDD_RI::get_option( 'edd_ri_tags_select' ), $term->term_id ); ?>>
					<?php echo $term->name; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render the plugin category selection dropdown
	 */
	public function themes_select_render() {

		$terms = $this->get_download_terms();
		?>
		<select name="edd_ri_settings[edd_ri_themes_select]">
			<?php foreach ( $terms as $term ) : ?>
				<option
					value="<?php echo $term->term_id; ?>"
					<?php selected( EDD_RI::get_option( 'edd_ri_themes_select' ), $term->term_id ); ?>>
					<?php echo $term->name; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Add the description
	 */
	public function callback() {
		_e( 'Below you can edit your settings for EDD Remote Installer. By selecting a category for plugins and themes ' .
		    'you\'re limiting the products that will be displayed to clients. This is a necessary step if you\'re ' .
		    'selling both plugins and themes so that themes can use the theme installer instead of the plugin installer.',
			'edd_ri'
		);
	}

	/**
	 * Create the page content
	 */
	public function options_page() {
		?>
		<form action="options.php" method='post'>

			<?php
			settings_fields( 'edd_ri_settings' );
			do_settings_sections( 'edd_ri_settings' );
			submit_button();
			?>
		</form>
		<?php
	}

	/**
	 * @param string $term
	 *
	 * @return array|int|WP_Error
	 */
	private function get_download_terms( $term = 'download_category' ) {
		return get_terms( $term );
	}
}
