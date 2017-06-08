<?php
/**
 * Menu.php file
 * Required
 * - Add Menu entries to other modules
 *
 * @package School Inventory module
 */

// Use dgettext() function instead of _() for Module specific strings translation
// See locale/README file for more information.

// Add a Menu entry to the Resources module.
if ( $RosarioModules['Resources'] ) // Verify Resources module is activated.
{
	$menu['Resources']['admin'] += array(
		'School_Inventory/SchoolInventory.php' => dgettext( 'School_Inventory', 'School Inventory' ),
	);

	$menu['Resources']['teacher'] += array(
		'School_Inventory/SchoolInventory.php' => dgettext( 'School_Inventory', 'School Inventory' ),
	);
}
