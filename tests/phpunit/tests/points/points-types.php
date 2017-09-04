<?php

/**
 * Test points type API.
 *
 * @package WordPoints\Tests\Points
 * @since 1.0.0
 */

/**
 * Points types test case.
 *
 * @since 1.0.0
 *
 * @group points
 */
class WordPoints_Points_Type_Test extends WordPoints_PHPUnit_TestCase_Points {

	//
	// wordpoints_is_points_type().
	//

	/**
	 * Test that it returns true when a points type exists.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_is_points_type
	 */
	public function test_returns_true_if_exists() {

		$this->assertTrue( wordpoints_is_points_type( 'points' ) );
	}

	/**
	 * Test that it returns false if a type doesn't exist.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_is_points_type
	 */
	public function test_returns_false_if_nonexistent() {

		$this->assertFalse( wordpoints_is_points_type( 'nonexistent' ) );
	}

	//
	// wordpoints_get_points_type().
	//

	/**
	 * Test that it returns an array of types present.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_get_points_types
	 */
	public function test_get_returns_array_of_types() {

		$this->assertSame( array( 'points' => $this->points_data ), wordpoints_get_points_types() );
	}

	/**
	 * Test behavior when no types exist.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_get_points_types
	 */
	public function test_get_returns_empty_array_if_none() {

		wordpoints_delete_maybe_network_option( 'wordpoints_points_types' );

		$this->assertSame( array(), wordpoints_get_points_types() );
	}

	//
	// wordpoints_add_points_type().
	//

	/**
	 * Test passing invalid settings.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_add_points_type
	 */
	public function test_add_returns_false_if_invalid_settings() {

		$this->assertFalse( wordpoints_add_points_type( '' ) );
		$this->assertFalse( wordpoints_add_points_type( array() ) );
		$this->assertFalse( wordpoints_add_points_type( array( 'name' => '' ) ) );
	}

	/**
	 * Test creating a new type.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_add_points_type
	 */
	public function test_add_updates_option() {

		$points_type = array( 'name' => 'Credits', 'suffix' => 'cr.' );

		$slug = wordpoints_add_points_type( $points_type );

		$this->assertSame(
			array( 'points' => $this->points_data, $slug => $points_type )
			, wordpoints_get_maybe_network_option( 'wordpoints_points_types' )
		);
	}

	/**
	 * Test slug generation for names with multiple words.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_add_points_type
	 */
	public function test_add_generates_slug_with_dashes() {

		$this->assertSame( 'a-test', wordpoints_add_points_type( array( 'name' => 'A test' ) ) );
		$this->assertSame( 'an-other-test', wordpoints_add_points_type( array( 'name' => 'An - other test' ) ) );
		$this->assertSame( 'third-test', wordpoints_add_points_type( array( 'name' => '- THIRD test- ' ) ) );
	}

	//
	// wordpoints_update_points_type().
	//

	/**
	 * Test updating a points type.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_update_points_type
	 */
	public function test_update_updates_option() {

		$this->points_data['prefix'] = '€';
		wordpoints_update_points_type( 'points', $this->points_data );

		$this->assertSame(
			array( 'points' => $this->points_data )
			, wordpoints_get_maybe_network_option( 'wordpoints_points_types' )
		);
	}

	/**
	 * Test that false is returned if $type is invalid.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_update_points_type
	 */
	public function test_update_false_if_not_type() {

		$this->assertFalse( wordpoints_update_points_type( 'nonexistent', array( 'name' => 'existent' ) ) );
	}

	/**
	 * Test that false is returned if 'name' isn't set.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_update_points_type
	 */
	public function test_update_false_if_name_missing() {

		$this->assertFalse( wordpoints_update_points_type( 'points', array( 'prefix' => 'P' ) ) );
	}

	//
	// wordpoints_delete_points_type().
	//

	/**
	 * Test that false is returned if the slug isn't registered.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_delete_points_type
	 * @covers WordPoints_Points_Type_Delete
	 */
	public function test_delete_returns_false_if_nonexistent() {

		$this->assertFalse( wordpoints_delete_points_type( 'nonexistent' ) );
	}

	/**
	 * Test that it deletes the points type and related stuff.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_delete_points_type
	 * @covers WordPoints_Points_Type_Delete
	 */
	public function test_points_type_deleted() {

		// Get the meta key now, because we can't after the points type is deleted.
		$meta_key = wordpoints_get_points_user_meta_key( 'points' );

		$user_id = $this->factory->user->create();

		wordpoints_add_points( $user_id, 10, 'points', 'test', array( 'a' => 1 ) );
		wordpointstests_add_points_hook( 'wordpoints_registration_points_hook', array( 'points' => 10 ) );

		$was_deleted = wordpoints_delete_points_type( 'points' );

		$this->assertTrue( $was_deleted );
		$this->assertFalse( wordpoints_is_points_type( 'points' ) );
		$this->assertSame( '', get_user_meta( $user_id, $meta_key, true ) );
		$this->assertSame( array(), WordPoints_Points_Hooks::get_points_type_hooks( 'points' ) );

		global $wpdb;

		$logs = $wpdb->get_var(
			$wpdb->prepare(
				"
					SELECT COUNT(id)
					FROM {$wpdb->wordpoints_points_logs}
					WHERE `user_id` = %d
				"
				, $user_id
			)
		);

		$this->assertSame( '0', $logs );

		$meta = $wpdb->get_var(
			"
				SELECT COUNT(meta_id)
				FROM {$wpdb->wordpoints_points_log_meta}
				WHERE `meta_key` = 'a'
			"
		);

		$this->assertSame( '0', $meta );
	}

	/**
	 * Test that it calls an action.
	 *
	 * @since 2.1.0
	 *
	 * @covers ::wordpoints_delete_points_type
	 * @covers WordPoints_Points_Type_Delete
	 */
	public function test_delete_calls_action() {

		$mock = new WordPoints_PHPUnit_Mock_Filter();
		$mock->add_action( 'wordpoints_delete_points_type', 10, 6 );

		$this->assertTrue( wordpoints_delete_points_type( 'points' ) );

		$this->assertSame(
			array( array( 'points', $this->points_data ) )
			, $mock->calls
		);
	}

	/**
	 * Test that it deletes all reactions associated with this points type.
	 *
	 * @since 2.1.0
	 *
	 * @covers ::wordpoints_delete_points_type
	 * @covers WordPoints_Points_Type_Delete
	 */
	public function test_delete_deletes_reactions() {

		wordpoints_add_points_type( array( 'name' => 'Other' ) );

		$reaction = $this->create_points_reaction();
		$other_reaction = $this->create_points_reaction(
			array( 'points_type' => 'other' )
		);

		$this->assertIsReaction( $reaction );
		$this->assertIsReaction( $other_reaction );

		$reaction_store = wordpoints_hooks()->get_reaction_store( 'points' );

		$reaction_id = $reaction->get_id();
		$other_reaction_id = $other_reaction->get_id();

		$this->assertTrue( $reaction_store->reaction_exists( $reaction_id ) );
		$this->assertTrue( $reaction_store->reaction_exists( $other_reaction_id ) );

		wordpoints_delete_points_type( 'points' );

		$this->assertFalse( $reaction_store->reaction_exists( $reaction_id ) );
		$this->assertTrue( $reaction_store->reaction_exists( $other_reaction_id ) );
	}

	/**
	 * Tests that it deletes reactions for all sites when network-active.
	 *
	 * @since 2.4.0
	 *
	 * @requires WordPoints network-active
	 *
	 * @covers ::wordpoints_delete_points_type
	 * @covers WordPoints_Points_Type_Delete
	 */
	public function test_delete_deletes_reactions_multisite() {

		$reaction = $this->create_points_reaction();

		$this->assertIsReaction( $reaction );

		$reaction_id = $reaction->get_id();

		$reaction_store = wordpoints_hooks()->get_reaction_store( 'points' );

		$this->assertTrue( $reaction_store->reaction_exists( $reaction_id ) );

		$site_id = $this->factory->blog->create();

		switch_to_blog( $site_id );
		$other_reaction = $this->create_points_reaction();

		$this->assertIsReaction( $other_reaction );

		$other_reaction_id = $other_reaction->get_id();

		$this->assertTrue( $reaction_store->reaction_exists( $other_reaction_id ) );
		restore_current_blog();

		wordpoints_delete_points_type( 'points' );

		$this->assertFalse( $reaction_store->reaction_exists( $reaction_id ) );

		switch_to_blog( $site_id );
		$this->assertFalse( $reaction_store->reaction_exists( $other_reaction_id ) );
		restore_current_blog();
	}

	/**
	 * Tests that it deletes network reactions when network-active.
	 *
	 * @since 2.4.0
	 *
	 * @requires WordPoints network-active
	 *
	 * @covers ::wordpoints_delete_points_type
	 * @covers WordPoints_Points_Type_Delete
	 */
	public function test_delete_deletes_reactions_network() {

		wordpoints_add_points_type( array( 'name' => 'Other' ) );

		wordpoints_hooks()->set_current_mode( 'network' );

		$reaction = $this->create_points_reaction();
		$other_reaction = $this->create_points_reaction(
			array( 'points_type' => 'other' )
		);

		$this->assertIsReaction( $reaction );
		$this->assertIsReaction( $other_reaction );

		$reaction_store = wordpoints_hooks()->get_reaction_store( 'points' );

		$reaction_id = $reaction->get_id();
		$other_reaction_id = $other_reaction->get_id();

		$this->assertTrue( $reaction_store->reaction_exists( $reaction_id ) );
		$this->assertTrue( $reaction_store->reaction_exists( $other_reaction_id ) );

		wordpoints_hooks()->set_current_mode( 'standard' );

		wordpoints_delete_points_type( 'points' );

		wordpoints_hooks()->set_current_mode( 'network' );

		$this->assertFalse( $reaction_store->reaction_exists( $reaction_id ) );
		$this->assertTrue( $reaction_store->reaction_exists( $other_reaction_id ) );

		wordpoints_hooks()->set_current_mode( 'standard' );
	}

	//
	// wordpoints_get_points_type_setting().
	//

	/**
	 * Test that null is returned if the points type doesn't exist.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_get_points_type_setting
	 */
	public function test_null_returned_if_nonexistent_setting() {

		$this->assertSame( null, wordpoints_get_points_type_setting( 'points', 'image' ) );
	}

	/**
	 * Test retrieval of a single setting.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_get_points_type_setting
	 */
	public function test_returns_setting_value() {

		$this->assertSame( 'Points', wordpoints_get_points_type_setting( 'points', 'name' ) );
	}
}

// EOF
