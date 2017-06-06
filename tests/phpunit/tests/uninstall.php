<?php

/**
 * Test uninstallation.
 *
 * @package WordPoints\Tests
 * @since 1.0.0
 */

/**
 * WordPoints uninstall test case.
 *
 * @since 1.0.0
 *
 * @covers WordPoints_Un_Installer
 * @covers WordPoints_Points_Un_Installer
 * @covers WordPoints_Ranks_Un_Installer
 */
class WordPoints_Uninstall_Test extends WPPPB_TestCase_Uninstall {

	//
	// Public methods.
	//

	/**
	 * Tear down after the tests.
	 *
	 * @since 1.2.0
	 */
	public function tearDown() {

		// We've just deleted the tables, so this will have a DB error.
		remove_action( 'delete_blog', 'wordpoints_delete_points_logs_for_blog' );

		parent::tearDown();
	}

	/**
	 * Test installation and uninstallation.
	 *
	 * @since 1.0.0
	 */
	public function test_uninstall() {

		global $wpdb;

		/*
		 * Install.
		 */

		// Check that the basic plugin data option was added.
		if ( $this->network_wide ) {
			$wordpoints_data = get_site_option( 'wordpoints_data' );
		} else {
			$wordpoints_data = get_option( 'wordpoints_data' );
		}

		$this->assertInternalType( 'array', $wordpoints_data );
		$this->assertArrayHasKey( 'version', $wordpoints_data );
		$this->assertSame( WORDPOINTS_VERSION, $wordpoints_data['version'] );

		// Flush the cache.
		unset( $GLOBALS['wp_roles'] );

		// Check that the capabilities were added.
		$administrator = get_role( 'administrator' );
		$this->assertTrue( $administrator->has_cap( 'install_wordpoints_extensions' ) );
		$this->assertTrue( $administrator->has_cap( 'activate_wordpoints_extensions' ) );
		$this->assertTrue( $administrator->has_cap( 'delete_wordpoints_extensions' ) );
		$this->assertTrue( $administrator->has_cap( 'update_wordpoints_extensions' ) );

		if ( $this->network_wide ) {
			$active_components = get_site_option( 'wordpoints_active_components' );
		} else {
			$active_components = get_option( 'wordpoints_active_components' );
		}

		$this->assertInternalType( 'array', $active_components );

		// Check that the tables were added.
		$this->assertTableExists( $wpdb->base_prefix . 'wordpoints_hook_hits' );
		$this->assertTableExists( $wpdb->base_prefix . 'wordpoints_hook_hitmeta' );
		$this->assertTableExists( $wpdb->base_prefix . 'wordpoints_hook_periods' );

		$this->assertLegacyPointsHooksDisabled();

		$this->assertPointsComponentInstalled( $active_components );

		/**
		 * Run install tests.
		 *
		 * @since 1.0.1
		 *
		 * @param WordPoints_Uninstall_Test $testcase The current instance.
		 */
		do_action( 'wordpoints_install_tests', $this );

		/*
		 * Simulated Usage.
		 */

		$this->simulate_usage();

		$this->assertRanksComponentInstalled();

		// Check that a module was installed by the simulator
		if ( is_multisite() ) {
			$value = get_site_option( 'wordpoints_tests_module_6' );
		} else {
			$value = get_option( 'wordpoints_tests_module_6' );
		}

		$this->assertSame( 'Testing!', $value );

		/*
		 * Uninstall.
		 */

		$this->uninstall();

		$this->assertPointsComponentUninstalled();
		$this->assertRanksComponentUninstalled();

		// The module should have been uninstalled.
		$this->assertFalse(
			wordpoints_get_maybe_network_option( 'wordpoints_tests_module_6' )
		);

		$this->assertNoUserMetaWithPrefix( 'wordpoints' );

		$this->assertTableNotExists( $wpdb->base_prefix . 'wordpoints_hook_hits' );
		$this->assertTableNotExists( $wpdb->base_prefix . 'wordpoints_hook_hitmeta' );
		$this->assertTableNotExists( $wpdb->base_prefix . 'wordpoints_hook_periods' );

		if ( is_multisite() ) {

			$this->assertNoSiteOptionsWithPrefix( 'wordpoints' );
			$this->assertNoSiteOptionsWithPrefix( 'widget_wordpoints' );

			$blog_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );

			$original_blog_id = get_current_blog_id();

			foreach ( $blog_ids as $blog_id ) {

				switch_to_blog( $blog_id );

				$this->assertNoUserOptionsWithPrefix( 'wordpoints' );
				$this->assertNoOptionsWithPrefix( 'wordpoints' );
				$this->assertNoOptionsWithPrefix( 'widget_wordpoints' );
				$this->assertNoCommentMetaWithPrefix( 'wordpoints' );

				$administrator = get_role( 'administrator' );
				$this->assertFalse( $administrator->has_cap( 'install_wordpoints_extensions' ) );
				$this->assertFalse( $administrator->has_cap( 'activate_wordpoints_extensions' ) );
				$this->assertFalse( $administrator->has_cap( 'delete_wordpoints_extensions' ) );
				$this->assertFalse( $administrator->has_cap( 'update_wordpoints_extensions' ) );
			}

			switch_to_blog( $original_blog_id );

			// See https://wordpress.stackexchange.com/a/89114/27757
			unset( $GLOBALS['_wp_switched_stack'] );
			$GLOBALS['switched'] = false;

		} else {

			$this->assertNoOptionsWithPrefix( 'wordpoints' );
			$this->assertNoOptionsWithPrefix( 'widget_wordpoints' );
			$this->assertNoCommentMetaWithPrefix( 'wordpoints' );

			$administrator = get_role( 'administrator' );
			$this->assertFalse( $administrator->has_cap( 'install_wordpoints_extensions' ) );
			$this->assertFalse( $administrator->has_cap( 'activate_wordpoints_extensions' ) );
			$this->assertFalse( $administrator->has_cap( 'delete_wordpoints_extensions' ) );
			$this->assertFalse( $administrator->has_cap( 'update_wordpoints_extensions' ) );

		} // End if ( is_multisite() ) else.

	} // End function test_uninstall().

	//
	// Assertions.
	//

	/**
	 * Assert that the legacy points hooks were disabled.
	 *
	 * @since 2.1.0
	 */
	protected function assertLegacyPointsHooksDisabled() {

		$array = array(
			'wordpoints_post_points_hook' => true,
			'wordpoints_comment_points_hook' => true,
			'wordpoints_comment_received_points_hook' => true,
			'wordpoints_periodic_points_hook' => true,
			'wordpoints_registration_points_hook' => true,
		);

		$option = 'wordpoints_legacy_points_hooks_disabled';

		$this->assertSame( $array, get_option( $option ) );

		if ( $this->network_wide ) {
			$this->assertSame( $array, get_site_option( $option ) );
		}
	}

	/**
	 * Assert that the points component is installed.
	 *
	 * @since 1.7.0
	 *
	 * @param array $active_components The list of active components.
	 */
	protected function assertPointsComponentInstalled( $active_components ) {

		global $wpdb;

		// Check that the points component is active.
		$this->assertArrayHasKey( 'points', $active_components );

		// Check that the points tables were added.
		$this->assertTableExists( $wpdb->base_prefix . 'wordpoints_points_logs' );
		$this->assertTableExists( $wpdb->base_prefix . 'wordpoints_points_log_meta' );

		// Check that the capabilities were added.
		$administrator = get_role( 'administrator' );
		$this->assertTrue( $administrator->has_cap( 'set_wordpoints_points' ) );

		if ( $this->network_wide ) {
			$this->assertFalse( $administrator->has_cap( 'manage_wordpoints_points_types' ) );
		} else {
			$this->assertTrue( $administrator->has_cap( 'manage_wordpoints_points_types' ) );
		}
	}

	/**
	 * Assert that the points component is uninstalled.
	 *
	 * @since 1.7.0
	 */
	protected function assertPointsComponentUninstalled() {

		global $wpdb;

		$this->assertTableNotExists( $wpdb->base_prefix . 'wordpoints_points_logs' );
		$this->assertTableNotExists( $wpdb->base_prefix . 'wordpoints_points_log_meta' );

		if ( is_multisite() ) {

			if ( $this->network_wide ) {

				$blog_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );

				$original_blog_id = get_current_blog_id();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );

					$administrator = get_role( 'administrator' );
					$this->assertFalse(
						$administrator->has_cap( 'set_wordpoints_points' )
					);
				}

				switch_to_blog( $original_blog_id );

				// See https://wordpress.stackexchange.com/a/89114/27757
				unset( $GLOBALS['_wp_switched_stack'] );
				$GLOBALS['switched'] = false;

			} else {

				$administrator = get_role( 'administrator' );
				$this->assertFalse(
					$administrator->has_cap( 'set_wordpoints_points' )
				);
			}

		} else {

			$administrator = get_role( 'administrator' );
			$this->assertFalse(
				$administrator->has_cap( 'set_wordpoints_points' )
			);

		} // End if ( is_multisite() ) else.
	}

	/**
	 * Assert that the ranks component is installed.
	 *
	 * @since 1.7.0
	 */
	protected function assertRanksComponentInstalled() {

		global $wpdb;

		if ( $this->network_wide ) {
			$active_components = get_site_option( 'wordpoints_active_components' );
		} else {
			$active_components = get_option( 'wordpoints_active_components' );
		}

		$this->assertInternalType( 'array', $active_components );

		$this->assertArrayHasKey( 'ranks', $active_components );

		$this->assertTableExists( $wpdb->base_prefix . 'wordpoints_ranks' );
		$this->assertTableExists( $wpdb->base_prefix . 'wordpoints_rankmeta' );
		$this->assertTableExists( $wpdb->base_prefix . 'wordpoints_user_ranks' );
	}

	/**
	 * Assert that the ranks component is uninstalled.
	 *
	 * @since 2.0.0
	 */
	protected function assertRanksComponentUninstalled() {

		global $wpdb;

		$this->assertTableNotExists( $wpdb->base_prefix . 'wordpoints_ranks' );
		$this->assertTableNotExists( $wpdb->base_prefix . 'wordpoints_rankmeta' );
		$this->assertTableNotExists( $wpdb->base_prefix . 'wordpoints_user_ranks' );

		$this->assertNoOptionsWithPrefix( 'wordpoints_rank_groups-' );
	}
}

// EOF
