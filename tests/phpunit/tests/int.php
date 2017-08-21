<?php

/**
 * Test wordpoints_*int() functions.
 *
 * These test not only proper return values, but also that passing by reference is
 * done correctly in all cases.
 *
 * Note that the 'pos' and 'neg' functions don't need to be tested as harshly, since
 * they are just wrappers for the wordpoints_int() function.
 *
 * @package WordPoints\Test\Int
 * @since 1.0.0
 */

/**
 * Test wordpoints_*int() functions.
 *
 * @since 1.0.0
 */
class WordPoints_Int_Test extends WordPoints_PHPUnit_TestCase {

	//
	// wordpoints_int().
	//

	/**
	 * Test proper handling of integers.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_int
	 */
	public function test_integers_unchanged() {

		$maybe_int = 5;
		wordpoints_int( $maybe_int );
		$this->assertSame( 5, $maybe_int );

		$maybe_int = -5;
		wordpoints_int( $maybe_int );
		$this->assertSame( -5, $maybe_int );
	}

	/**
	 * Test proper handling of 'integer' strings.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_int
	 */
	public function test_string_integers_converted() {

		$maybe_int = '5';
		wordpoints_int( $maybe_int );
		$this->assertSame( 5, $maybe_int );

		$maybe_int = '-5';
		wordpoints_int( $maybe_int );
		$this->assertSame( -5, $maybe_int );
	}

	/**
	 * Test proper handling of 'integer' floats.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_int
	 */
	public function test_float_intetegers_converted() {

		$maybe_int = 5.0;
		wordpoints_int( $maybe_int );
		$this->assertSame( 5, $maybe_int );
	}

	/**
	 * Test proper handling of edge case strings.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_int
	 */
	public function test_false_for_edge_strings() {

		$maybe_int = '3 blind mice';
		wordpoints_int( $maybe_int );
		$this->assertFalse( $maybe_int );

		$maybe_int = '0775';
		wordpoints_int( $maybe_int );
		$this->assertFalse( $maybe_int );
	}

	/**
	 * Test proper handling of non-integer floats.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_int
	 */
	public function test_false_for_floats() {

		$maybe_int = 5.5;
		wordpoints_int( $maybe_int );
		$this->assertFalse( $maybe_int );
	}

	/**
	 * Test non-scalar, boolean and null value handling.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_int
	 */
	public function test_false_for_everything_else() {

		$maybe_int = array( 'foo', 'bar' );
		wordpoints_int( $maybe_int );
		$this->assertFalse( $maybe_int );

		$maybe_int = new stdClass();
		wordpoints_int( $maybe_int );
		$this->assertFalse( $maybe_int );

		$maybe_int = true;
		wordpoints_int( $maybe_int );
		$this->assertFalse( $maybe_int );

		$maybe_int = null;
		wordpoints_int( $maybe_int );
		$this->assertFalse( $maybe_int );
	}

	//
	// wordpoints_posint().
	//

	/**
	 * Test proper handling of positive integers.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_posint
	 */
	public function test_positive_integers_unchanged() {

		$maybe_positive = 5;
		wordpoints_posint( $maybe_positive );
		$this->assertSame( 5, $maybe_positive );
	}

	/**
	 * Test proper handling of negative integers.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_posint
	 */
	public function test_negative_returns_false() {

		$maybe_positive = -5;
		wordpoints_posint( $maybe_positive );
		$this->assertFalse( $maybe_positive );
	}

	/**
	 * Test proper handling of 0.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_posint
	 */
	public function test_zero_not_positive() {

		$maybe_positive = 0;
		wordpoints_posint( $maybe_positive );
		$this->assertFalse( $maybe_positive );
	}

	//
	// wordpoints_negint().
	//

	/**
	 * Test proper handling of positive integers.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_negint
	 */
	public function test_positive_returns_false() {

		$maybe_negative = 5;
		wordpoints_negint( $maybe_negative );
		$this->assertFalse( $maybe_negative );
	}

	/**
	 * Test proper handling of negative integers.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_negint
	 */
	public function test_negative_unchanged() {

		$maybe_negative = -5;
		wordpoints_negint( $maybe_negative );
		$this->assertSame( -5, $maybe_negative );
	}

	/**
	 * Test proper handling of 0.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_negint
	 */
	public function test_zero_not_negative() {

		$maybe_negative = 0;
		wordpoints_negint( $maybe_negative );
		$this->assertFalse( $maybe_negative );
	}
}

// EOF
