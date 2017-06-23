<?php

/**
 * Test the points component.
 *
 * @package WordPoints\Tests\Points
 * @since 1.0.0
 */

/**
 * Points test case.
 *
 * @since 1.0.0
 *
 * @group points
 */
class WordPoints_Points_Test extends WordPoints_PHPUnit_TestCase_Points {

	/**
	 * The ID of a user that may be used in the tests.
	 *
	 * @since 1.0.0
	 *
	 * @type int $user_id
	 */
	private $user_id;

	/**
	 * Set up user.
	 *
	 * @since 1.0.0
	 */
	public function setUp() {

		parent::setUp();

		$this->user_id = $this->factory->user->create();
	}

	//
	// wordpoints_get_points().
	//

	/**
	 * Test behavior with nonexistant points type.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_get_points
	 */
	public function test_false_if_nonexistant_points_type() {

		$this->assertFalse( wordpoints_get_points( $this->user_id, 'idontexist' ) );
	}

	/**
	 * Test behavior for an invalid $user_id.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_get_points
	 */
	public function test_false_if_invalid_user_id() {

		$this->assertFalse( wordpoints_get_points( 0, 'points' ) );
	}

	/**
	 * Test behavior with no points awarded yet.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_get_points
	 */
	public function test_zero_if_no_points() {

		$this->assertSame( 0, wordpoints_get_points( $this->user_id, 'points' ) );
	}

	/**
	 * Test behavior with points awarded.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_get_points
	 */
	public function test_returns_points() {

		update_user_meta( $this->user_id, wordpoints_get_points_user_meta_key( 'points' ), 23 );

		$this->assertSame( 23, wordpoints_get_points( $this->user_id, 'points' ) );
	}

	//
	// wordpoints_get_points_minimum().
	//

	/**
	 * Test that the default is 0.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_get_points_minimum
	 */
	public function test_default_minimum_is_0() {

		$this->assertSame( 0, wordpoints_get_points_minimum( 'points' ) );
		$this->assertSame( 0, wordpoints_get_points_above_minimum( $this->factory->user->create(), 'points' ) );
	}

	/**
	 * Test that the 'wordpoints_points_minimum' filter is working.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_get_points_minimum
	 */
	public function test_wordpoints_points_minimum_filter() {

		add_filter( 'wordpoints_points_minimum', array( $this, 'minimum_filter' ) );

		$this->assertSame( -50, wordpoints_get_points_minimum( 'points' ) );
		$this->assertSame( 50, wordpoints_get_points_above_minimum( $this->factory->user->create(), 'points' ) );

		remove_filter( 'wordpoints_points_minimum', array( $this, 'minimum_filter' ) );
	}

	/**
	 * Sample filter.
	 *
	 * @since 1.0.0
	 */
	public function minimum_filter() {

		return -50;
	}

	//
	// wordpoints_format_points().
	//

	/**
	 * Test that the result is unaltered by default.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_format_points
	 * @covers ::wordpoints_format_points_filter
	 */
	public function test_default_format() {

		$this->assertSame( '$5pts.', wordpoints_format_points( 5, 'points', 'testing' ) );
	}

	/**
	 * Test that it formats negative values correctly.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_format_points
	 * @covers ::wordpoints_format_points_filter
	 */
	public function test_format_negative() {

		$this->assertSame( '-$5pts.', wordpoints_format_points( -5, 'points', 'testing' ) );
	}

	/**
	 * Test that it formats with the prefix even when the suffix isn't set.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_format_points
	 * @covers ::wordpoints_format_points_filter
	 */
	public function test_format_prefix() {

		wordpoints_add_points_type( array( 'name' => 'Credits', 'prefix' => '$' ) );

		$this->assertSame( '$5', wordpoints_format_points( 5, 'credits', 'testing' ) );
	}

	/**
	 * Test that it formats with the suffix even when the prefix isn't set.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_format_points
	 * @covers ::wordpoints_format_points_filter
	 */
	public function test_format_suffix() {

		wordpoints_add_points_type( array( 'name' => 'Credits', 'suffix' => 'c.' ) );

		$this->assertSame( '5c.', wordpoints_format_points( 5, 'credits', 'testing' ) );
	}

	/**
	 * Test that the 'wordpoints_points_display' filter is called.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_format_points
	 */
	public function test_format_filter() {

		add_filter( 'wordpoints_format_points', array( $this, 'format_filter' ), 10, 3 );

		$this->assertSame( '5points', wordpoints_format_points( 5, 'points', 'testing' ) );
	}

	/**
	 * Sample filter.
	 *
	 * @since 1.0.0
	 */
	public function format_filter( $formatted, $points, $type ) {

		return $points . $type;
	}

	//
	// wordpoints_alter_points().
	//

	/**
	 * Test set, alter, add and subtract.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 * @covers ::wordpoints_set_points
	 * @covers ::wordpoints_subtract_points
	 * @covers ::wordpoints_add_points
	 */
	public function test_points_altering() {

		wordpoints_set_points( $this->user_id, 50, 'points', 'test' );
		$this->assertSame( 50, wordpoints_get_points( $this->user_id, 'points' ) );

		wordpoints_subtract_points( $this->user_id, 5, 'points', 'test' );
		$this->assertSame( 45, wordpoints_get_points( $this->user_id, 'points' ) );

		wordpoints_add_points( $this->user_id, 10, 'points', 'test' );
		$this->assertSame( 55, wordpoints_get_points( $this->user_id, 'points' ) );

		wordpoints_alter_points( $this->user_id, 5, 'points', 'test' );
		$this->assertSame( 60, wordpoints_get_points( $this->user_id, 'points' ) );

		wordpoints_alter_points( $this->user_id, -10, 'points', 'test' );
		$this->assertSame( 50, wordpoints_get_points( $this->user_id, 'points' ) );

		wordpoints_alter_points( $this->user_id, -60, 'points', 'test' );
		$this->assertSame( 0, wordpoints_get_points( $this->user_id, 'points' ) );
	}

	/**
	 * Test that the log ID is returned.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_log_id_returned() {

		$log_id = wordpoints_alter_points( $this->user_id, 20, 'points', 'test' );

		$this->assertInternalType( 'int', $log_id );
	}

	/**
	 * Test that it just returns true if the transaction isn't logged.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_true_returned_if_not_logged() {

		add_filter( 'wordpoints_points_log', '__return_false' );

		$this->assertTrue(
			wordpoints_alter_points( $this->user_id, 20, 'points', 'test' )
		);
	}

	/**
	 * Test that it just returns true if the transaction is short-circuited.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_true_returned_if_short_circuited() {

		add_filter( 'wordpoints_alter_points', '__return_zero' );

		$this->assertTrue(
			wordpoints_alter_points( $this->user_id, 20, 'points', 'test' )
		);
	}

	/**
	 * Test that it just returns true if the transaction is short-circuited.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_false_returned_if_short_circuited_with_false() {

		add_filter( 'wordpoints_alter_points', '__return_false' );

		$this->assertFalse(
			wordpoints_alter_points( $this->user_id, 20, 'points', 'test' )
		);
	}

	/**
	 * Test that it requires a valid user ID.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_alter_requires_valid_user_id() {

		$this->assertFalse(
			wordpoints_alter_points( 'bad', 20, 'points', 'test' )
		);
	}

	/**
	 * Test that it requires a valid points value.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_alter_requires_valid_points() {

		$this->assertFalse(
			wordpoints_alter_points( $this->user_id, 'bad', 'points', 'test' )
		);
	}

	/**
	 * Test that it requires a valid points type.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_alter_requires_valid_points_type() {

		$this->assertFalse(
			wordpoints_alter_points( $this->user_id, 20, 'bad', 'test' )
		);
	}

	/**
	 * Test that it requires a non-empty log type.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_alter_requires_non_empty_log_type() {

		$this->assertFalse(
			wordpoints_alter_points( $this->user_id, 20, 'points', '' )
		);
	}

	/**
	 * Test that it won't set the points below the minimum.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_alter_wont_set_points_below_minimum() {

		wordpoints_alter_points( $this->user_id, 50, 'points', 'test' );

		$this->assertSame( 50, wordpoints_get_points( $this->user_id, 'points' ) );

		wordpoints_alter_points( $this->user_id, -55, 'points', 'test' );

		// The default minimum is 0.
		$this->assertSame( 0, wordpoints_get_points( $this->user_id, 'points' ) );
	}

	/**
	 * Test that the wordpoints_points_altered action is called.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_wordpoints_points_altered_action() {

		$this->listen_for_filter( 'wordpoints_points_altered' );
		wordpoints_alter_points( $this->user_id, 20, 'points', 'test' );
		$this->assertSame( 1, $this->filter_was_called( 'wordpoints_points_altered' ) );
	}

	/**
	 * Test that the wordpoints_points_logged action is called.
	 *
	 * @since 2.4.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_wordpoints_points_logged_action() {

		$this->listen_for_filter( 'wordpoints_points_logged' );
		wordpoints_alter_points( $this->user_id, 20, 'points', 'test' );
		$this->assertSame( 1, $this->filter_was_called( 'wordpoints_points_logged' ) );
	}

	/**
	 * Test that the wordpoints_points_logged action is not called when not logged.
	 *
	 * @since 2.4.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_wordpoints_points_logged_action_not_logged() {

		add_filter( 'wordpoints_points_log', '__return_false' );

		$this->listen_for_filter( 'wordpoints_points_logged' );
		wordpoints_alter_points( $this->user_id, 20, 'points', 'test' );
		$this->assertSame( 0, $this->filter_was_called( 'wordpoints_points_logged' ) );
	}

	/**
	 * Test that emojis work in logs.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_emoji_in_log() {

		global $wpdb;

		if ( 'utf8mb4' !== $wpdb->charset ) {
			$this->markTestSkipped( 'wpdb database charset must be utf8mb4.' );
		}

		$log_text = "You've got Points! \xf0\x9f\x98\x8e";

		$filter = new WordPoints_PHPUnit_Mock_Filter( $log_text );
		add_filter( 'wordpoints_points_log-test', array( $filter, 'filter' ) );

		$log_id = wordpoints_alter_points( $this->user_id, 20, 'points', 'test' );

		$query = new WordPoints_Points_Logs_Query(
			array( 'fields' => 'text', 'id__in' => array( $log_id ) )
		);

		$this->assertSame( $log_text, $query->get( 'var' ) );
	}

	/**
	 * Test that emojis in logs are encoded if needed.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_emoji_in_log_utf8() {

		$filter = new WordPoints_PHPUnit_Mock_Filter( 'utf8' );
		add_filter( 'pre_get_col_charset', array( $filter, 'filter' ) );

		$filter = new WordPoints_PHPUnit_Mock_Filter( "You've got Points! \xf0\x9f\x98\x8e" );
		add_filter( 'wordpoints_points_log-test', array( $filter, 'filter' ) );

		$log_id = wordpoints_alter_points( $this->user_id, 20, 'points', 'test' );

		$query = new WordPoints_Points_Logs_Query(
			array( 'fields' => 'text', 'id__in' => array( $log_id ) )
		);

		$this->assertSame( "You've got Points! &#x1f60e;", $query->get( 'var' ) );
	}

	/**
	 * Test that emojis in logs are encoded if needed when the log text is passed.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_emoji_in_log_utf8_log_text_param() {

		$filter = new WordPoints_PHPUnit_Mock_Filter( 'utf8' );
		add_filter( 'pre_get_col_charset', array( $filter, 'filter' ) );

		$log_id = wordpoints_alter_points(
			$this->user_id
			, 20
			, 'points'
			, 'test'
			, array()
			, "You've got Points! \xf0\x9f\x98\x8e"
		);

		$query = new WordPoints_Points_Logs_Query(
			array( 'fields' => 'text', 'id__in' => array( $log_id ) )
		);

		$this->assertSame( "You've got Points! &#x1f60e;", $query->get( 'var' ) );
	}

	/**
	 * Test that the log text may be passed as the sixth parameter.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_alter_points
	 */
	public function test_alter_log_text_param() {

		$log_text = 'Just testing.';

		$log_id = wordpoints_alter_points(
			$this->user_id
			, 20
			, 'points'
			, 'test'
			, array()
			, $log_text
		);

		$query = new WordPoints_Points_Logs_Query(
			array( 'fields' => 'text', 'id__in' => array( $log_id ) )
		);

		$this->assertSame( $log_text, $query->get( 'var' ) );
	}

	//
	// wordpoints_add_points().
	//

	/**
	 * Test add() won't subtract.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_add_points
	 */
	public function test_add_wont_subtract() {

		$this->assertFalse( wordpoints_add_points( $this->user_id, -5, 'points', 'test' ) );
	}

	/**
	 * Test that the log text may be passed as the sixth parameter.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_add_points
	 */
	public function test_add_log_text_param() {

		$log_text = 'Just testing.';

		$log_id = wordpoints_add_points(
			$this->user_id
			, 20
			, 'points'
			, 'test'
			, array()
			, $log_text
		);

		$query = new WordPoints_Points_Logs_Query(
			array( 'fields' => 'text', 'id__in' => array( $log_id ) )
		);

		$this->assertSame( $log_text, $query->get( 'var' ) );
	}

	//
	// wordpoints_subtract_points().
	//

	/**
	 * Test that subtract() won't add.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_subtract_points
	 */
	public function test_subtract_wont_add() {

		$this->assertFalse( wordpoints_subtract_points( $this->user_id, -5, 'points', 'test' ) );
	}

	/**
	 * Test that the log text may be passed as the sixth parameter.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_subtract_points
	 */
	public function test_subtract_log_text_param() {

		$log_text = 'Just testing.';

		$log_id = wordpoints_subtract_points(
			$this->user_id
			, 20
			, 'points'
			, 'test'
			, array()
			, $log_text
		);

		$query = new WordPoints_Points_Logs_Query(
			array( 'fields' => 'text', 'id__in' => array( $log_id ) )
		);

		$this->assertSame( $log_text, $query->get( 'var' ) );
	}

	//
	// wordpoints_render_points_log_text().
	//

	/**
	 * Test that it calls the filter.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_render_points_log_text
	 */
	public function test_render_log_text() {

		$log_text = 'Just testing.';

		$filter = new WordPoints_PHPUnit_Mock_Filter( $log_text );
		add_filter( 'wordpoints_points_log-test', array( $filter, 'filter' ) );

		$rendered_text = wordpoints_render_points_log_text(
			$this->user_id
			, 20
			, 'points'
			, 'test'
			, array()
		);

		$this->assertSame( $log_text, $rendered_text );
	}

	/**
	 * Test that the default log text is returned if the text is empty.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_render_points_log_text
	 */
	public function test_render_log_text_default_text() {

		$rendered_text = wordpoints_render_points_log_text(
			$this->user_id
			, 20
			, 'points'
			, 'test'
			, array()
		);

		$this->assertSame(
			_x( '(no description)', 'points log', 'wordpoints' )
			, $rendered_text
		);
	}

	/**
	 * Test that the default log text is returned if the text is empty.
	 *
	 * @since 2.0.0
	 *
	 * @covers ::wordpoints_render_points_log_text
	 */
	public function test_render_log_text_default_text_param() {

		$log_text = 'Just testing.';

		$rendered_text = wordpoints_render_points_log_text(
			$this->user_id
			, 20
			, 'points'
			, 'test'
			, array()
			, $log_text
		);

		$this->assertSame( $log_text, $rendered_text );
	}

	//
	// Multisite.
	//

	/**
	 * Test that multisite behavior is correct.
	 *
	 * @since 1.2.0
	 *
	 * @covers ::wordpoints_get_points
	 * @covers ::wordpoints_alter_points
	 *
	 * @requires WordPress multisite
	 */
	public function test_multisite_behaviour() {

		// Add some points to the user.
		wordpoints_add_points( $this->user_id, 10, 'points', 'test' );

		$this->assertSame( 10, wordpoints_get_points( $this->user_id, 'points' ) );

		// Now create another blog and add some points there.
		$blog_id = $this->factory->blog->create();

		switch_to_blog( $blog_id );

		if ( ! is_wordpoints_network_active() ) {
			wordpoints_add_points_type( array( 'name' => 'points' ) );
		}

		wordpoints_add_points( $this->user_id, 10, 'points', 'test' );

		// Check that the points are separate or universal, based on plugin status.
		if ( is_wordpoints_network_active() ) {
			$this->assertSame( 20, wordpoints_get_points( $this->user_id, 'points' ) );
		} else {
			$this->assertSame( 10, wordpoints_get_points( $this->user_id, 'points' ) );
		}

		restore_current_blog();

		if ( is_wordpoints_network_active() ) {
			$this->assertSame( 20, wordpoints_get_points( $this->user_id, 'points' ) );
		} else {
			$this->assertSame( 10, wordpoints_get_points( $this->user_id, 'points' ) );
		}
	}
}

// EOF
