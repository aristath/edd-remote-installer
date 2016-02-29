<div class="wrap metabox-holder">
	<h2><?php _e( 'EDD Remote Installer', 'edd_ri' ); ?></h2>

	<div class="wp-filter">
		<form method="post" style="display: inline-block; margin: 10px 0">
			<ul class="filter-links">
				<li class="plugin-install-featured">
					<div class="control">
                        <span class="select">
						<select id="edd_ri_api_url">
							<option value="-"><?php esc_attr_e( 'Select a site', '' ); ?></option>
						</select>
	                    </span>
					</div>
				</li>
			</ul>
		</form>

		<form method="get" class="search-form search-plugins">
			<input type="hidden" value="search" name="tab">
			<label><span class="screen-reader-text">Search Plugins</span>
				<input type="search" placeholder="Search Plugins" class="wp-filter-search" value="" name="s" disabled>
			</label>
			<input type="submit" value="Search Plugins" class="button screen-reader-text" id="search-submit">
		</form>
	</div>

	<div id="edd-ri-wrapper">
		<img id="edd-ri-loading" src="<?php echo admin_url( '/images/spinner-2x.gif' ); ?>" style="display:none">
		<div id="edd-ri-wrapper-inner">
		</div>
	</div>

	<div id="edd_ri_license_thickbox" style="display:none;">
		<h3><?php _e( 'Enter your license', 'edd_ri' ); ?></h3>
		<form action="" method="post" id="edd_ri_license_form">
			<input style="width: 100%" type="text" id="edd_ri_license">
			<button style="margin-top: 10px" type="submit"
			        class="button button-primary"><?php esc_attr_e( 'Submit', 'edd_ri' ); ?></button>
		</form>
	</div>
	<div class="message-popup" id="MessagePopup" style="display:none;"></div>
</div>