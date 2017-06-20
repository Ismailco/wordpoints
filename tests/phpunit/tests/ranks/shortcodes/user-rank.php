<?php

/**
 * A test case for the user rank shortcode.
 *
 * @package WordPoints\Tests
 * @since 1.8.0
 */

/**
 * Test the [wordpoints_user_rank] shortcode.
 *
 * @since 1.8.0
 *
 * @group ranks
 * @group shortcodes
 *
 * @covers WordPoints_Rank_Shortcode_User_Rank
 */
class WordPoints_User_Rank_Shortcode_Test extends WordPoints_PHPUnit_TestCase_Ranks {

	/**
	 * Test that the [wordpoints_user_rank] shortcode exists.
	 *
	 * @since 1.8.0
	 */
	public function test_shortcode_exists() {

		$this->assertTrue( shortcode_exists( 'wordpoints_user_rank' ) );
	}

	/**
	 * Test that the old version of the class is deprecated.
	 *
	 * @since 2.3.0
	 *
	 * @covers WordPoints_User_Rank_Shortcode
	 *
	 * @expectedDeprecated WordPoints_User_Rank_Shortcode::__construct
	 */
	public function test_deprecated_version() {

		new WordPoints_User_Rank_Shortcode( array(), '' );
	}

	/**
	 * Test that the shortcode works.
	 *
	 * @since 1.8.0
	 */
	public function test_it_works() {

		$user_id = $this->factory->user->create();
		$rank_id = $this->factory->wordpoints->rank->create();

		wordpoints_update_user_rank( $user_id, $rank_id );

		$result = $this->do_shortcode(
			'wordpoints_user_rank'
			, array( 'user_id' => $user_id, 'rank_group' => $this->rank_group )
		);

		$formatted_rank = wordpoints_get_formatted_user_rank(
			$user_id
			, $this->rank_group
			, 'user_rank_shortcode'
		);

		$this->assertSame( $formatted_rank, $result );
	}

	/**
	 * Test the it defaults to the current user if the user_id attribute is ommitted.
	 *
	 * @since 1.8.0
	 */
	public function test_defaults_to_current_user() {

		$user_id = $this->factory->user->create();

		$old_current_user = wp_get_current_user();
		wp_set_current_user( $user_id );

		$rank_id = $this->factory->wordpoints->rank->create();

		wordpoints_update_user_rank( $user_id, $rank_id );

		$result = $this->do_shortcode(
			'wordpoints_user_rank'
			, array( 'rank_group' => $this->rank_group )
		);

		$formatted_rank = wordpoints_get_formatted_user_rank(
			$user_id
			, $this->rank_group
			, 'user_rank_shortcode'
		);

		$this->assertSame( $formatted_rank, $result );
	}

	/**
	 * Test that nothing is displayed to a normal user on failure.
	 *
	 * @since 1.8.0
	 */
	public function test_nothing_displayed_to_normal_user_on_failure() {

		$user_id = $this->factory->user->create();

		$old_current_user = wp_get_current_user();
		wp_set_current_user( $user_id );

		$rank = $this->factory->wordpoints->rank->create_and_get();

		wordpoints_update_user_rank( $user_id, $rank->ID );

		$result = $this->do_shortcode( 'wordpoints_user_rank' );

		// There should be no error with an invalid points type.
		$this->assertSame( '', $result );

		wp_set_current_user( $old_current_user->ID );
	}

	/**
	 * Test that an error is displayed to an admin user on failure.
	 *
	 * @since 1.8.0
	 */
	public function test_error_displayed_to_admin_user_on_failure() {

		// Create a user and assign them admin-like capabilities.
		$user = $this->factory->user->create_and_get();
		$user->add_cap( 'manage_options' );

		$old_current_user = wp_get_current_user();
		wp_set_current_user( $user->ID );

		$rank = $this->factory->wordpoints->rank->create_and_get();

		wordpoints_update_user_rank( $user->ID, $rank->ID );

		$this->assertWordPointsShortcodeError(
			$this->do_shortcode( 'wordpoints_user_rank' )
		);

		wp_set_current_user( $old_current_user->ID );
	}
}

// EOF
