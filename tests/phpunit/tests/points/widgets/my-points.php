<?php

/**
 * A test case for the My Points widget.
 *
 * @package WordPoints\Tests
 * @since 1.9.0
 */

/**
 * Test that the My Points widget functions correctly.
 *
 * @since 1.9.0
 *
 * @group points
 * @group widgets
 *
 * @covers WordPoints_Points_Widget_User_Points
 */
class WordPoints_My_Points_Widget_Test extends WordPoints_PHPUnit_TestCase_Points {

	/**
	 * @since 1.9.0
	 */
	protected $widget_class = 'WordPoints_Points_Widget_User_Points';

	/**
	 * Set up for each test.
	 *
	 * @since 1.9.0
	 */
	public function setUp() {

		parent::setUp();

		wp_set_current_user( $this->factory->user->create() );
	}

	/**
	 * Test that the old version of the class is deprecated.
	 *
	 * @since 2.3.0
	 *
	 * @covers WordPoints_My_Points_Widget
	 *
	 * @expectedDeprecated WordPoints_My_Points_Widget::__construct
	 */
	public function test_deprecated_version() {

		new WordPoints_My_Points_Widget();
	}

	/**
	 * Test behavior with the user not logged in and there is no alt text.
	 *
	 * @since 1.9.0
	 */
	public function test_user_not_logged_in_no_alt_text() {

		wp_set_current_user( 0 );

		$html = $this->get_widget_html(
			array( 'alt_text' => '', 'points_type' => 'points' )
		);

		$this->assertSame( '', $html );
	}

	/**
	 * Test that the alt text is displayed when the user is not logged in.
	 *
	 * @since 1.9.0
	 */
	public function test_alt_text() {

		wp_set_current_user( 0 );

		$xpath = $this->get_widget_xpath(
			array( 'alt_text' => 'Alt text', 'points_type' => 'points' )
		);

		$node = $xpath->query( '//div[@class = "wordpoints-points-widget-text"]' )
			->item( 0 );

		$this->assertSame( 'Alt text', $node->textContent );
	}

	/**
	 * Test that the points logs aren't displayed when the user is not logged in.
	 *
	 * @since 1.10.1
	 *
	 * @covers WordPoints_My_Points_Widget::widget_body
	 */
	public function test_logged_out_no_points_logs() {

		wp_set_current_user( 0 );

		$xpath = $this->get_widget_xpath(
			array(
				'alt_text'    => 'Alt text',
				'points_type' => 'points',
				'number_logs' => 3,
			)
		);

		$this->assertSame( 0, $xpath->query( '//table' )->length );
	}

	/**
	 * Test the default behavior.
	 *
	 * @since 1.9.0
	 */
	public function test_defaults() {

		$this->factory->wordpoints->points_log->create_many(
			4
			, array( 'user_id' => get_current_user_id() )
		);

		$xpath = $this->get_widget_xpath( array( 'points_type' => 'points' ) );

		$node = $xpath->query( '//div[@class = "wordpoints-points-widget-text"]' )
			->item( 0 );

		$this->assertSame( 'Points: $40pts.', $node->textContent );
		$this->assertSame( 0, $xpath->query( '//tbody/tr' )->length );
	}

	/**
	 * Test that an invalid points_type setting results in an error.
	 *
	 * @since 1.9.0
	 */
	public function test_invalid_points_type_setting() {

		$this->give_current_user_caps( 'edit_theme_options' );

		$this->assertWordPointsWidgetError(
			$this->get_widget_html( array( 'points_type' => 'invalid' ) )
		);

		// It should not error when the points type is empty.
		$xpath = $this->get_widget_xpath( array( 'points_type' => '' ) );

		$node = $xpath->query( '//div[@class = "wordpoints-points-widget-text"]' )
			->item( 0 );

		$this->assertSame( 'Points: $0pts.', $node->textContent );
	}

	/**
	 * Test the text setting.
	 *
	 * @since 1.9.0
	 */
	public function test_text_setting() {

		$xpath = $this->get_widget_xpath(
			array( 'points_type' => 'points', 'text' => 'Widget %points% text' )
		);

		$nodes = $xpath->query( '//div[@class = "wordpoints-points-widget-text"]' );

		$this->assertSame( 'Widget $0pts. text', $nodes->item( 0 )->textContent );
	}

	/**
	 * Test the number_logs setting.
	 *
	 * @since 1.9.0
	 */
	public function test_number_logs_setting() {

		$this->factory->wordpoints->points_log->create_many(
			4
			, array( 'user_id' => get_current_user_id() )
		);

		$xpath = $this->get_widget_xpath(
			array( 'points_type' => 'points', 'number_logs' => 3 )
		);

		$this->assertSame( 3, $xpath->query( '//tbody/tr' )->length );
	}

	/**
	 * Test the update() method.
	 *
	 * @since 1.9.0
	 */
	public function test_update_method() {

		/** @var WordPoints_Widget $widget */
		$widget = new $this->widget_class();

		$sanitized = $widget->update(
			array(
				'title'       => '<p>Title</p>',
				'text'        => '  Some text. ',
				'alt_text'    => ' Alt text.   ',
				'number_logs' => '5dd',
				'points_type' => 'invalid',
			)
			, array()
		);

		$this->assertSame( 'Title', $sanitized['title'] );
		$this->assertSame( 'Some text.', $sanitized['text'] );
		$this->assertSame( 'Alt text.', $sanitized['alt_text'] );
		$this->assertSame( 0, $sanitized['number_logs'] );
		$this->assertSame( 'points', $sanitized['points_type'] );
	}
}

// EOF
