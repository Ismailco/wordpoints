<?php

/**
 * Class to un/install the plugin.
 *
 * @package WordPoints
 * @since 1.8.0
 */

/**
 * Un/install the plugin.
 *
 * @since 1.8.0
 */
class WordPoints_Un_Installer extends WordPoints_Un_Installer_Base {

	/**
	 * @since 2.0.0
	 */
	protected $type = 'plugin';

	/**
	 * @since 1.8.0
	 */
	protected $updates = array(
		'1.3.0'  => array( 'single' => true, /*     -     */ /*      -      */ ),
		'1.5.0'  => array( /*      -      */ 'site' => true, /*      -      */ ),
		'1.8.0'  => array( /*      -      */ 'site' => true, /*      -      */ ),
		'1.10.3' => array( 'single' => true, /*     -     */ 'network' => true ),
		'2.1.0-alpha-3'  => array( 'single' => true, /*     -     */ 'network' => true ),
		'2.3.0-alpha-2'  => array( 'single' => true, /*     -     */ 'network' => true ),
		'2.4.0-alpha-2'  => array( 'single' => true, 'site' => true, 'network' => true ),
		'2.4.0-alpha-3'  => array( 'single' => true, 'site' => true, 'network' => true ),
	);

	/**
	 * @since 2.1.0
	 */
	protected $schema = array(
		'global' => array(
			'tables' => array(
				'wordpoints_hook_periods' => '
					id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					hit_id BIGINT(20) UNSIGNED NOT NULL,
					signature CHAR(64) NOT NULL,
					PRIMARY KEY  (id),
					KEY period_signature (hit_id,signature(8))',
				'wordpoints_hook_hits' => '
					id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					action_type VARCHAR(255) NOT NULL,
					signature_arg_guids TEXT NOT NULL,
					event VARCHAR(255) NOT NULL,
					reactor VARCHAR(255) NOT NULL,
					reaction_mode VARCHAR(255) NOT NULL,
					reaction_store VARCHAR(255) NOT NULL,
					reaction_context_id TEXT NOT NULL,
					reaction_id BIGINT(20) UNSIGNED NOT NULL,
					date DATETIME NOT NULL,
					PRIMARY KEY  (id)',
				'wordpoints_hook_hitmeta' => '
					meta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					wordpoints_hook_hit_id BIGINT(20) UNSIGNED NOT NULL,
					meta_key VARCHAR(255) NOT NULL,
					meta_value LONGTEXT,
					PRIMARY KEY  (meta_id),
					KEY hit_id (wordpoints_hook_hit_id),
					KEY meta_key (meta_key(191))',
			),
		),
	);

	/**
	 * @since 2.0.0
	 */
	protected $uninstall = array(
		'network' => array(
			'options' => array(
				'wordpoints_sitewide_active_modules',
				'wordpoints_network_install_skipped',
				'wordpoints_network_installed',
				'wordpoints_network_update_skipped',
				'wordpoints_breaking_deactivated_modules',
			),
		),
		'local'   => array(
			'options' => array(
				'wordpoints_active_modules',
				'wordpoints_recently_activated_modules',
			),
		),
		'global' => array(
			'options' => array(
				'wordpoints_edd_sl_module_licenses',
				'wordpoints_edd_sl_module_info',
			),
			'transients' => array(
				'wordpoints_extension_updates',
			),
		),
		'universal' => array(
			'options' => array(
				'wordpoints_data',
				'wordpoints_active_components',
				'wordpoints_excluded_users',
				'wordpoints_incompatible_modules',
				'wordpoints_module_check_rand_str',
				'wordpoints_module_check_nonce',
				'wordpoints_hook_reaction-%',
				'wordpoints_hook_reaction_index-%',
				'wordpoints_hook_reaction_last_id-%',
			),
			'list_tables' => array(
				'wordpoints_extensions' => array(),
				'wordpoints_modules' => array(),
			),
		),
	);

	/**
	 * @since 2.0.0
	 */
	protected $custom_caps_getter = 'wordpoints_get_custom_caps';

	/**
	 * @since 1.8.0
	 */
	public function install( $network ) {

		$filter_func = ( $network ) ? '__return_true' : '__return_false';
		add_filter( 'is_wordpoints_network_active', $filter_func );

		// Check if the plugin has been activated/installed before.
		$installed = (bool) wordpoints_get_maybe_network_option( 'wordpoints_data' );

		parent::install( $network );

		// Activate the Points component, if this is the first activation.
		if ( false === $installed ) {
			$wordpoints_components = WordPoints_Components::instance();
			$wordpoints_components->load();
			$wordpoints_components->activate( 'points' );
		}

		remove_filter( 'is_wordpoints_network_active', $filter_func );
	}

	/**
	 * @since 1.8.0
	 */
	protected function before_update() {

		parent::before_update();

		if ( $this->network_wide ) {
			unset( $this->updates['1_8_0'] );
		}
	}

	/**
	 * @since 1.8.0
	 */
	protected function install_network() {

		$data = wordpoints_get_maybe_network_option( 'wordpoints_data' );

		// Add plugin data.
		if ( ! is_array( $data ) ) {

			wordpoints_update_maybe_network_option(
				'wordpoints_data',
				array(
					'version'    => WORDPOINTS_VERSION,
					'components' => array(), // Components use this to store data.
					'modules'    => array(), // Modules can use this to store data.
				)
			);

		} else {

			// Make sure the version is set properly even if the data is already
			// there, in case the plugin is being reactivated and things had been
			// corrupted somehow.
			$data['version'] = WORDPOINTS_VERSION;

			wordpoints_update_maybe_network_option( 'wordpoints_data', $data );
		}

		$this->install_db_schema();
	}

	/**
	 * @since 1.8.0
	 */
	protected function install_single() {

		$this->install_custom_caps();
		$this->install_network();
	}

	/**
	 * @since 1.8.0
	 */
	protected function load_dependencies() {

		// Note that some things are loaded by
		// WordPoints_Un_Installer_Base::load_base_dependencies().
		require_once WORDPOINTS_DIR . '/includes/modules.php';
	}

	/**
	 * @since 2.0.0
	 */
	protected function before_uninstall() {

		// Set up the components class.
		WordPoints_Components::set_up();

		$this->uninstall_modules();
		$this->uninstall_components();

		parent::before_uninstall();
	}

	/**
	 * Uninstall modules.
	 *
	 * Note that modules aren't active when they are uninstalled, so they need to
	 * include any dependencies in their uninstall.php files.
	 *
	 * @since 1.8.0
	 */
	protected function uninstall_modules() {

		wordpoints_deactivate_modules(
			wordpoints_get_array_option( 'wordpoints_active_modules', 'site' )
		);

		foreach ( array_keys( wordpoints_get_modules() ) as $module ) {
			wordpoints_uninstall_module( $module );
		}

		$this->delete_modules_dir();
	}

	/**
	 * Attempt to delete the modules directory.
	 *
	 * @since 1.8.0
	 */
	protected function delete_modules_dir() {

		global $wp_filesystem;

		if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
			$wp_filesystem->delete( wordpoints_extensions_dir(), true );
		}
	}

	/**
	 * Uninstall the components.
	 *
	 * @since 1.8.0
	 */
	protected function uninstall_components() {

		/** This filter is documented in classes/components.php */
		do_action( 'wordpoints_components_register' );

		$components = WordPoints_Components::instance();

		// Uninstall the components.
		foreach ( $components->get() as $component => $data ) {
			WordPoints_Installables::uninstall( 'component', $component );
		}
	}

	/**
	 * Update the site to 1.3.0.
	 *
	 * @since 1.8.0
	 */
	protected function update_single_to_1_3_0() {
		wordpoints_add_custom_caps( $this->custom_caps );
	}

	/**
	 * Update a site to 1.5.0.
	 *
	 * @since 1.8.0
	 */
	protected function update_site_to_1_5_0() {
		wordpoints_add_custom_caps( $this->custom_caps );
	}

	/**
	 * Update a site to 1.8.0.
	 *
	 * @since 1.8.0
	 */
	protected function update_site_to_1_8_0() {
		$this->add_installed_site_id();
	}

	/**
	 * Update a multisite network to 1.10.3.
	 *
	 * @since 1.10.3
	 */
	protected function update_network_to_1_10_3() {
		$this->update_single_to_1_10_3();
	}

	/**
	 * Update a non-multisite install to 1.10.3
	 *
	 * @since 1.10.3
	 */
	protected function update_single_to_1_10_3() {

		global $wp_filesystem;

		$modules_dir = wordpoints_extensions_dir();

		if ( ! WP_Filesystem( false, $modules_dir ) ) {
			return;
		}

		$index_file = $modules_dir . '/index.php';

		if ( ! $wp_filesystem->exists( $index_file ) ) {
			$wp_filesystem->put_contents( $index_file, '<?php // Gold is silent.' );
		}
	}

	/**
	 * Update a multisite network to 2.1.0.
	 *
	 * @since 2.1.0
	 */
	protected function update_network_to_2_1_0_alpha_3() {
		$this->map_shortcuts( 'schema' );
		$this->install_db_schema();
	}

	/**
	 * Update a non-multisite install to 2.1.0.
	 *
	 * @since 2.1.0
	 */
	protected function update_single_to_2_1_0_alpha_3() {
		$this->map_shortcuts( 'schema' );
		$this->install_db_schema();
	}

	/**
	 * Update a multisite network to 2.3.0.
	 *
	 * @since 2.3.0
	 */
	protected function update_network_to_2_3_0_alpha_2() {

		// We don't need to update the table if the new schema has just been used to
		// install it.
		if ( version_compare( $this->get_db_version(), '2.1.0-alpha-3', '<' ) ) {
			return;
		}

		global $wpdb;

		$wpdb->query(
			"
				ALTER TABLE `{$wpdb->wordpoints_hook_hits}`
				CHANGE `primary_arg_guid` `signature_arg_guids` TEXT NOT NULL
			"
		); // WPCS: cache OK.
	}

	/**
	 * Update a non-multisite install to 2.3.0.
	 *
	 * @since 2.3.0
	 */
	protected function update_single_to_2_3_0_alpha_2() {
		$this->update_network_to_2_3_0_alpha_2();
	}

	/**
	 * Update a multisite network to 2.4.0.
	 *
	 * @since 2.4.0
	 */
	protected function update_network_to_2_4_0_alpha_2() {

		$extension = 'wordpointsorg/wordpointsorg.php';

		if ( true === wordpoints_validate_module( $extension ) ) {
			update_site_option( 'wordpoints_merged_extensions', array( $extension ) );
			wordpoints_deactivate_modules( array( $extension ), false, true );
		}
	}

	/**
	 * Update a single site on a multisite network to 2.4.0.
	 *
	 * @since 2.4.0
	 */
	protected function update_site_to_2_4_0_alpha_2() {

		$this->update_2_4_0_add_new_custom_caps();

		$extension = 'wordpointsorg/wordpointsorg.php';

		if ( is_wordpoints_module_active( $extension ) ) {
			wordpoints_deactivate_modules( array( $extension ) );
		}
	}

	/**
	 * Update a non-multisite install to 2.4.0.
	 *
	 * @since 2.4.0
	 */
	protected function update_single_to_2_4_0_alpha_2() {

		$this->update_2_4_0_add_new_custom_caps();

		$extension = 'wordpointsorg/wordpointsorg.php';

		if ( true === wordpoints_validate_module( $extension ) ) {
			update_option( 'wordpoints_merged_extensions', array( $extension ) );
			wordpoints_deactivate_modules( array( $extension ) );
		}
	}

	/**
	 * Update a multisite network to 2.4.0.
	 *
	 * @since 2.4.0
	 */
	protected function update_network_to_2_4_0_alpha_3() {

		$this->update_2_4_0_rename_extensions_directory();
	}

	/**
	 * Update a single site on a multisite network to 2.4.0.
	 *
	 * @since 2.4.0
	 */
	protected function update_site_to_2_4_0_alpha_3() {

		$this->update_2_4_0_add_new_custom_caps();
	}

	/**
	 * Update a non-multisite install to 2.4.0.
	 *
	 * @since 2.4.0
	 */
	protected function update_single_to_2_4_0_alpha_3() {

		$this->update_2_4_0_add_new_custom_caps();
		$this->update_2_4_0_rename_extensions_directory();
	}

	/**
	 * Adds the new custom caps.
	 *
	 * @since 2.4.0
	 */
	protected function update_2_4_0_add_new_custom_caps() {

		wordpoints_add_custom_caps( wordpoints_get_custom_caps() );
	}

	/**
	 * Renames the extensions directory.
	 *
	 * @since 2.4.0
	 */
	protected function update_2_4_0_rename_extensions_directory() {

		global $wp_filesystem;

		if ( ! $wp_filesystem && ! WP_Filesystem() ) {
			update_site_option( 'wordpoints_legacy_extensions_dir', true );
			return;
		}

		$legacy = WP_CONTENT_DIR . '/wordpoints-modules';

		if ( $wp_filesystem->is_dir( $legacy ) ) {

			$moved = $wp_filesystem->move( $legacy, WP_CONTENT_DIR . '/wordpoints-extensions' );

			if ( ! $moved ) {
				update_site_option( 'wordpoints_legacy_extensions_dir', true );
			}
		}
	}
}

return 'WordPoints_Un_Installer';

// EOF
