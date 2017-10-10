<?php

/**
 * A test case for the comment removed points hook.
 *
 * @package WordPoints\Tests
 * @since 1.4.0
 */

/**
 * Test that the comment removed points hook functions as expected.
 *
 * @since 1.4.0
 *
 * @group points
 * @group points_hooks
 *
 * @covers WordPoints_Comment_Removed_Points_Hook
 *
 * @expectedDeprecated WordPoints_Comment_Removed_Points_Hook::__construct
 * @expectedDeprecated WordPoints_Comment_Removed_Points_Hook::hook
 * @expectedDeprecated WordPoints_Comment_Removed_Points_Hook::logs
 */
class WordPoints_Comment_Removed_Points_Hook_Test extends WordPoints_PHPUnit_TestCase_Points {

	/**
	 * Test that points are removed as expected.
	 *
	 * @since 1.4.0
	 */
	public function test_points_removed() {

		$hook = wordpointstests_add_points_hook(
			new WordPoints_Comment_Removed_Points_Hook()
			, array( 'points' => 10 )
		);
		$this->assertInstanceOf( 'WordPoints_Comment_Removed_Points_Hook', $hook );

		$user_id     = $this->factory->user->create();
		$comment_ids = $this->factory->comment->create_many(
			3
			, array(
				'user_id'         => $user_id,
				'comment_post_ID' => $this->factory->post->create(
					array( 'post_author' => $user_id )
				),
			)
		);

		wordpoints_set_points( $user_id, 100, 'points', 'test' );
		$this->assertSame( 100, wordpoints_get_points( $user_id, 'points' ) );

		// Test that status transitions remove points correctly.
		wp_set_comment_status( array_pop( $comment_ids ), 'hold' );
		$this->assertSame( 90, wordpoints_get_points( $user_id, 'points' ) );

		wp_set_comment_status( array_pop( $comment_ids ), 'spam' );
		$this->assertSame( 80, wordpoints_get_points( $user_id, 'points' ) );

		wp_set_comment_status( array_pop( $comment_ids ), 'trash' );
		$this->assertSame( 70, wordpoints_get_points( $user_id, 'points' ) );
	}

	/**
	 * Test that points are only awarded for the specified post type.
	 *
	 * @since 1.5.0
	 */
	public function test_points_only_removed_for_specified_post_type() {

		wordpointstests_add_points_hook(
			new WordPoints_Comment_Removed_Points_Hook()
			, array( 'points' => 20, 'post_type' => 'post' )
		);

		$user_id = $this->factory->user->create();
		wordpoints_set_points( $user_id, 100, 'points', 'test' );

		// Create a comment on a post.
		$comment_id = $this->factory->comment->create(
			array(
				'user_id'         => $user_id,
				'comment_post_ID' => $this->factory->post->create(
					array( 'post_type' => 'post' )
				),
			)
		);

		wp_set_comment_status( $comment_id, 'spam' );

		// Test that points were removed for the comment.
		$this->assertSame( 80, wordpoints_get_points( $user_id, 'points' ) );

		// Now create a comment on a page.
		$this->factory->comment->create(
			array(
				'user_id'         => $user_id,
				'comment_post_ID' => $this->factory->post->create(
					array( 'post_type' => 'page' )
				),
			)
		);

		wp_set_comment_status( $comment_id, 'spam' );

		// Test that no points were removed for the comment.
		$this->assertSame( 80, wordpoints_get_points( $user_id, 'points' ) );

	} // End public function test_points_only_awarded_for_specified_post_type().

	/**
	 * Test that points are removed again after the comment points hook runs.
	 *
	 * Since 1.4.0 this had been a part of the Comment points hook's tests, but it
	 * was moved to here because these tests all need to be run separately, because
	 * of tests bleeding into each other.
	 *
	 * @since 1.9.0
	 */
	public function test_points_removed_again_after_comment_hook_runs() {

		$hook = wordpointstests_add_points_hook(
			'wordpoints_comment_points_hook'
			, array( 'points' => 10, 'auto_reverse' => 0 )
		);
		$this->assertInstanceOf( 'WordPoints_Comment_Points_Hook', $hook );

		$hook->set_option( 'disable_auto_reverse_label', true );

		$hook = wordpointstests_add_points_hook(
			new WordPoints_Comment_Removed_Points_Hook()
			, array( 'points' => 10 )
		);
		$this->assertInstanceOf( 'WordPoints_Comment_Removed_Points_Hook', $hook );

		$user_id = $this->factory->user->create();

		wordpoints_set_points( $user_id, 100, 'points', 'test' );
		$this->assertSame( 100, wordpoints_get_points( $user_id, 'points' ) );

		$comment_id = $this->factory->comment->create(
			array(
				'user_id'         => $user_id,
				'comment_post_ID' => $this->factory->post->create(
					array( 'post_author' => $user_id )
				),
			)
		);

		$this->assertSame( 110, wordpoints_get_points( $user_id, 'points' ) );

		// Test that status transitions award/remove points correctly.
		wp_set_comment_status( $comment_id, 'hold' );

		$this->assertSame( 100, wordpoints_get_points( $user_id, 'points' ) );

		wp_set_comment_status( $comment_id, 'approve' );
		$this->assertSame( 110, wordpoints_get_points( $user_id, 'points' ) );

		wp_set_comment_status( $comment_id, 'spam' );
		$this->assertSame( 100, wordpoints_get_points( $user_id, 'points' ) );

		wp_set_comment_status( $comment_id, 'approve' );
		$this->assertSame( 110, wordpoints_get_points( $user_id, 'points' ) );

		wp_set_comment_status( $comment_id, 'trash' );
		$this->assertSame( 100, wordpoints_get_points( $user_id, 'points' ) );

		wp_set_comment_status( $comment_id, 'approve' );
		$this->assertSame( 110, wordpoints_get_points( $user_id, 'points' ) );

	} // End public function test_points_removed_again_after_comment_hook_runs().
}

// EOF
