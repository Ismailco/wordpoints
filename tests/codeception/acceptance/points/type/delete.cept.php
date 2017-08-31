<?php

/**
 * Tests deleting a points type.
 *
 * @package WordPoints\Codeception
 * @since 2.1.0
 */

$I = new AcceptanceTester( $scenario );
$I->wantTo( 'Delete a points type' );
$I->hadCreatedAPointsType();
$I->amLoggedInAsAdminOnPage( 'wp-admin/admin.php?page=wordpoints_points_types' );
$I->see( 'Points Types' );
$I->see( 'Points', '.nav-tab-active' );
$I->see( 'Slug: points' );
$I->canSeeInFormFields(
	'#settings form'
	, array(
		'points-name' => 'Points',
		'points-prefix' => '',
		'points-suffix' => '',
	)
);
$I->click( 'Delete' );
$I->seeJQueryDialog( 'Are you sure?' );
$I->fillField( '.wordpoints-points-delete-confirm-input', 'Points' );
$I->click( 'Delete', '.wordpoints-delete-type-dialog' );
$I->seeSuccessMessage();
$I->see( 'Points Types' );
$I->see( 'Add New', '.nav-tab-active' );

// EOF
