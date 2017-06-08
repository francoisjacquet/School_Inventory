<?php
/**
 * English Help texts - School Inventory module
 *
 * Texts are organized by:
 * - Module
 * - Profile
 *
 * @author François Jacquet
 *
 * @uses Heredoc syntax
 * @see  http://php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc
 *
 * @package School Inventory module
 */

// SCHOOL INVENTORY ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['School_Inventory/SchoolInventory.php'] = <<<HTML
<p>
	<i>School Inventory</i> allows you to manage and keep track of your school asset.
</p>
<p>
	Items can be organized and filtered by category, status, location (for example a class room) and person (for example, the owner or the person in charge or the person the item was lended to).
</p>
<p>
	Categories must first be added using the Title fields at the bottom the corresponiding category list. Then press the "Save" button.
</p>
<p>
	Items should be added under a preexisting category.
</p>
<p>
	To add an Item, use the Title, Quantity, Comments, and category fields at the bottom of the list. Then press the "Save" button.
</p>
HTML;

endif;
