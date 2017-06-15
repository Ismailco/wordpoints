<?php

/**
 * A test case for the ranks component update to 2.0.0.
 *
 * @package WordPoints\Tests
 * @since 2.0.0
 */

/**
 * Test that the ranks component updates to 2.0.0 properly.
 *
 * @since 2.0.0
 *
 * @group ranks
 * @group update
 *
 * @covers WordPoints_Ranks_Un_Installer::update_network_to_2_0_0
 * @covers WordPoints_Ranks_Un_Installer::update_single_to_2_0_0
 */
class WordPoints_Ranks_2_0_0_Update_Test extends WordPoints_PHPUnit_TestCase_Ranks {

	/**
	 * @since 2.0.0
	 */
	protected $previous_version = '1.10.0';

	/**
	 * Test that database table character sets are updated.
	 *
	 * @since 2.0.0
	 *
	 * @covers WordPoints_Ranks_Un_Installer::update_network_to_2_0_0
	 * @covers WordPoints_Ranks_Un_Installer::update_single_to_2_0_0
	 */
	public function test_db_table_charsets_updated() {

		global $wpdb;

		if ( 'utf8mb4' !== $wpdb->charset ) {
			$this->markTestSkipped( 'wpdb database charset must be utf8mb4.' );
		}

		$this->create_tables_with_charset( 'utf8' );

		// Simulate the update.
		$installer = WordPoints_Installables::get_installer( 'component', 'ranks' );
		$installer->update( $this->previous_version, '2.0.0', is_wordpoints_network_active() );

		$this->assertTablesHaveCharset( 'utf8mb4' );
	}
}

// EOF
