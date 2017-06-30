<?php
/**
 * School Inventory functions
 *
 * @package School Inventory module
 */

/**
 * Get Category by ID and type, for current school.
 *
 * @example GetSICategory( $category_id, $category_type );
 *
 * @param string $category_id   Category ID.
 * @param string $category_type Category Type.
 *
 * @return array Array with category details. Empty if not found.
 */
function GetSICategory( $category_id, $category_type )
{
	static $categories = array();

	$category_id = (int) $category_id;

	if ( $category_id < -1 )
	{
		return array();
	}

	if ( $category_id < 1
		&& ! $category_type )
	{
		return array();
	}

	// N/A or All.
	if ( $category_id < 1 )
	{
		return array(
			'CATEGORY_ID' => $category_id,
			'SCHOOL_ID' => UserSchool(),
			'TITLE' => ( $category_id < 0 ? _( 'All' ) : _( 'N/A' ) ),
			'CATEGORY_TYPE' => $category_type,
			'SORT_ORDER' => ( $category_id < 0 ? 0 : '' ),
		);
	}

	if ( isset( $categories[ $category_id ] ) )
	{
		return $categories[ $category_id ];
	}

	$categories[ $category_id ] = DBGet( DBQuery( "SELECT CATEGORY_ID,SCHOOL_ID,TITLE,CATEGORY_TYPE,SORT_ORDER
		FROM SCHOOL_INVENTORY_CATEGORIES
		WHERE CATEGORY_ID='" . $category_id . "'
		AND SCHOOL_ID='" . UserSchool() . "'" ) );

	$categories[ $category_id ] = $categories[ $category_id ][1];

	return $categories[ $category_id ];
}


/**
 * Get Categories by type, for current school.
 *
 * @example $statuses = GetSICategories( 'STATUS' );
 *
 * @param string  $type     Category Type: CATEGORY|STATUS|LOCATION|WORK_ORDER|USER_ID.
 *
 * @return array  Categories details belonging to this type + All & N/A options.
 */
function GetSICategories( $category_type )
{
	static $categories = array();

	if ( ! $category_type )
	{
		return array();
	}

	if ( isset( $categories[ $category_type ] ) )
	{
		return $categories[ $category_type ];
	}

	// Reactivate custom REMOVE column if N/A / All options.
	/*$functions = array(
		'REMOVE' => 'MakeSICategoryRemove',
	);*/

	$categories_RET = DBGet( DBQuery( "SELECT CATEGORY_ID,SCHOOL_ID,
		'' AS REMOVE,TITLE,CATEGORY_TYPE,SORT_ORDER," .
		// (SELECT SUM(sii.QUANTITY) for Total of quantities, not Items.
		"(SELECT COUNT(sii.ITEM_ID)
			FROM SCHOOL_INVENTORY_CATEGORYXITEM sicxi,SCHOOL_INVENTORY_ITEMS sii
			WHERE sicxi.CATEGORY_TYPE=sic.CATEGORY_TYPE
			AND sic.CATEGORY_ID=sicxi.CATEGORY_ID
			AND sicxi.ITEM_ID=sii.ITEM_ID
			AND sii.SCHOOL_ID='" . UserSchool() . "') AS TOTAL
		FROM SCHOOL_INVENTORY_CATEGORIES sic
		WHERE CATEGORY_TYPE='" . $category_type . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER,TITLE" ), $functions );

	/*$total = $total_na = '0';

	foreach ( (array) $categories_RET as $category )
	{
		$total += $category['TOTAL'];
	}

	// All option.
	$all_option = array(
		'CATEGORY_ID' => '-1',
		'CATEGORY_TYPE' => $category_type,
		'SCHOOL_ID' => UserSchool(),
		'TITLE' => _( 'All' ),
		'SORT_ORDER' => '0',
		'TOTAL' => $total,
	);

	// N/A Total.
	$total_na_RET = DBGet( DBQuery( "SELECT COUNT(DISTINCT sii.ITEM_ID) AS TOTAL_NA
		FROM SCHOOL_INVENTORY_CATEGORYXITEM sicxi,SCHOOL_INVENTORY_ITEMS sii
		WHERE NOT EXISTS(SELECT sicxi.ITEM_ID
			FROM SCHOOL_INVENTORY_CATEGORYXITEM sicxi
			WHERE sicxi.CATEGORY_TYPE='" . $category_type . "'
			AND sicxi.ITEM_ID=sii.ITEM_ID)
		AND sicxi.ITEM_ID=sii.ITEM_ID
		AND sii.SCHOOL_ID='" . UserSchool() . "'" ) );

	$total_na = $total_na_RET[1]['TOTAL_NA'];

	// N/A option.
	$na_option = array(
		'CATEGORY_ID' => '',
		'CATEGORY_TYPE' => $category_type,
		'SCHOOL_ID' => UserSchool(),
		'TITLE' => _( 'N/A' ),
		'SORT_ORDER' => '',
		'TOTAL' => $total_na,
	);

	$categories[ $category_type ] = array();

	if ( $total
		&& count( $categories_RET ) > 1 )
	{
		// Add All option.
		$categories[ $category_type ][0] = $all_option;

		$categories[ $category_type ] += (array) $categories_RET;

		// Start with keyf 1.
		array_unshift( $categories[ $category_type ], null );

		unset( $categories[ $category_type ][0] );

		// Add N/A option.
		$categories[ $category_type ][] = $na_option;
	}
	elseif ( $categories_RET )
	{
		$categories[ $category_type ] += (array) $categories_RET;

		// Add N/A option.
		$categories[ $category_type ][] = $na_option;
	}
	else
	{
		$categories[ $category_type ][1] = $na_option;
	}*/

	$categories[ $category_type ] = $categories_RET;

	// var_dump($categories[ $category_type ]);
	return $categories[ $category_type ];
}


/**
 * Get Items by Category by ID and type, for current school.
 *
 * @example GetSIItemsByCategory( $category_id, $category_type );
 *
 * @param string $category_id   Category ID.
 * @param string $category_type Category Type.
 *
 * @return array Array with category items. Empty if not found.
 */
function GetSIItemsByCategory( $category_id, $category_type )
{
	$category_id = (int) $category_id;

	if ( $category_id < -1
		|| ( $category_id > -1
			&& ! $category_type ) )
	{
		return array();
	}

	$functions = array(
		'TITLE' => 'MakeSITextInput',
		'QUANTITY' => 'MakeSINumberInput',
		'FILE' => 'MakeSIFileInput',
		'COMMENTS' => 'MakeSITextInput',
		'CATEGORY' => 'MakeSICategorySelect',
		'STATUS' => 'MakeSICategorySelect',
		'LOCATION' => 'MakeSICategorySelect',
		'WORK_ORDER' => 'MakeSICategorySelect',
		'USER_ID' => 'MakeSICategorySelect',
	);

	$sql_category_where = "sicxi.CATEGORY_ID='" . $category_id . "'
		AND sicxi.CATEGORY_TYPE='" . $category_type . "'
		AND sicxi.ITEM_ID=sii.ITEM_ID";

	if ( ! $category_id )
	{
		// N/A.
		$sql_category_where = "NOT EXISTS(SELECT sicxi.ITEM_ID
			FROM SCHOOL_INVENTORY_CATEGORYXITEM sicxi
			WHERE sicxi.CATEGORY_TYPE='" . $category_type . "'
			AND sicxi.ITEM_ID=sii.ITEM_ID)
			AND sicxi.ITEM_ID=sii.ITEM_ID";
	}
	elseif ( $category_id === -1
		&& $category_type === '-1' )
	{
		// All Items, all categories.
		$sql_category_where = "TRUE";
	}
	elseif ( $category_id === -1 )
	{
		// All.
		$sql_category_where = "sicxi.CATEGORY_TYPE='" . $category_type . "'
			AND sicxi.ITEM_ID=sii.ITEM_ID";
	}

	$category_items_RET = DBGet( DBQuery( "SELECT DISTINCT sii.ITEM_ID,SCHOOL_ID,TITLE,QUANTITY,FILE,COMMENTS,SORT_ORDER,
		(SELECT CATEGORY_ID
			FROM SCHOOL_INVENTORY_CATEGORYXITEM
			WHERE sii.ITEM_ID=ITEM_ID
			AND CATEGORY_TYPE='CATEGORY' LIMIT 1) AS CATEGORY,
		(SELECT CATEGORY_ID
			FROM SCHOOL_INVENTORY_CATEGORYXITEM
			WHERE sii.ITEM_ID=ITEM_ID
			AND CATEGORY_TYPE='STATUS' LIMIT 1) AS STATUS,
		(SELECT CATEGORY_ID
			FROM SCHOOL_INVENTORY_CATEGORYXITEM
			WHERE sii.ITEM_ID=ITEM_ID
			AND CATEGORY_TYPE='LOCATION' LIMIT 1) AS LOCATION,
		(SELECT CATEGORY_ID
			FROM SCHOOL_INVENTORY_CATEGORYXITEM
			WHERE sii.ITEM_ID=ITEM_ID
			AND CATEGORY_TYPE='WORK_ORDER' LIMIT 1) AS WORK_ORDER,
		(SELECT CATEGORY_ID
			FROM SCHOOL_INVENTORY_CATEGORYXITEM
			WHERE sii.ITEM_ID=ITEM_ID
			AND CATEGORY_TYPE='USER_ID' LIMIT 1) AS USER_ID
		FROM SCHOOL_INVENTORY_ITEMS sii,SCHOOL_INVENTORY_CATEGORYXITEM sicxi
		WHERE " . $sql_category_where .  "
		AND sii.SCHOOL_ID='" . UserSchool() . "'
		ORDER BY sii.SORT_ORDER,sii.TITLE" ), $functions );

	return $category_items_RET;
}


/**
 * Get items total, for the current school.
 *
 * @return int Total.
 */
function GetSIItemsTotal()
{
	$items_total_RET = DBGet( DBQuery( "SELECT COUNT(ITEM_ID) AS TOTAL
		FROM SCHOOL_INVENTORY_ITEMS
		WHERE SCHOOL_ID='" . UserSchool() . "'" ) );

	return $items_total_RET ? (int) $items_total_RET[1]['TOTAL'] : 0;
}


/**
 * Output list of items belonging to a category.
 *
 * @example SICategoryItemsListOutput( 'STATUS', 'Status', 'Statuses' );
 *
 * @uses ListOutput()
 * @uses GetSICategories()
 *
 * @param string $category_type Category type.
 * @param string $singular      Category type name, singular.
 * @param string $plural        Category type name, plural.
 */
function SICategoryItemsListOutput( $category_type, $singular, $plural )
{
	$categories_RET = GetSICategories( $category_type );

	// Display list.
	$columns = array(
		/*'REMOVE' => '',*/
		'TITLE' => _( $singular ),
		'TOTAL' => _( 'Total' ),
	);

	$LO_options = array(
		'save' => false,
		'search' => false,
		'add' => true,
		'responsive' => false,
	);

	$link = array();

	$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'];
	$link['TITLE']['variables'] = array(
		'category_id' => 'CATEGORY_ID',
		'category_type' => 'CATEGORY_TYPE',
	);

	$link['add']['html'] = array(
		'TITLE' => MakeSITextInput( '', 'TITLE', $category_type ),
	);

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove';
	$link['remove']['variables'] = array( 'category_id' => 'CATEGORY_ID' );

	ListOutput(
		$categories_RET,
		$columns,
		$singular,
		$plural,
		$link,
		array(),
		$LO_options
	);
}


function MakeSITextInput( $value, $column, $category_type = '' )
{
	global $THIS_RET;

	if ( $THIS_RET['ITEM_ID'] )
	{
		$id = $THIS_RET['ITEM_ID'];
	}
	elseif ( $THIS_RET['CATEGORY_ID'] )
	{
		$id = $THIS_RET['CATEGORY_ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $column === 'TITLE' )
	{
		$extra = 'size=15 maxlength=255';

		if ( $id !== 'new' )
		{
			$extra .= ' required';
		}
		elseif ( $category_type )
		{
			$id .= $category_type;
		}
	}
	elseif ( $column === 'COMMENTS' )
	{
		$extra = 'size=20 maxlength=2000';
	}
	elseif ( $column === 'SORT_ORDER' )
	{
		$extra = 'size=6 maxlength=8';
	}
	else
	{
		$extra = 'size=10 maxlength=255';
	}

	return TextInput( $value, 'values[' . $id . '][' . $column . ']', '', $extra );
}


function MakeSINumberInput( $value, $column )
{
	global $THIS_RET;

	if ( $THIS_RET['ITEM_ID'] )
	{
		$id = $THIS_RET['ITEM_ID'];
	}
	elseif ( $THIS_RET['CATEGORY_ID'] )
	{
		$id = $THIS_RET['CATEGORY_ID'];
	}
	else
	{
		$id = 'new';
	}

	$extra = 'type="number"';

	if ( $column === 'QUANTITY' )
	{
		$extra .= ' min="0" step="any" size=3 maxlength=6';

		if ( $id !== 'new'
			&& $value < 1 )
		{
			// Quantity in red if < 1.
			$value = array( $value, '<span style="color: red">' . $value . '</span>' );
		}
	}

	return TextInput( $value, 'values[' . $id . '][' . $column . ']', '', $extra );
}


function MakeSICategorySelect( $value, $column )
{
	global $THIS_RET;

	$return = '';

	if ( $THIS_RET['ITEM_ID'] )
	{
		$id = $THIS_RET['ITEM_ID'];

		// If N/A, add an hidden *_WAS_NA field
		// to know value should be inserted instead of updated!
		$return .= '<input type="hidden" value="' . ( $value ? '' : 'Y' ) .
			'" name="values[' . $id . '][' . $column . '_WAS_NA]" />';
	}
	else
	{
		$id = 'new';

		// Preselect current categorie's value.
		if ( $column === $_REQUEST['category_type']
			&& $_REQUEST['category_id'] > 0 )
		{
			$value = $_REQUEST['category_id'];
		}
	}

	$extra = '';

	$options = GetSICategoryOptions( $column );

	// Do not use Chosen inside ListOutput!
	// return $return . ChosenSelectInput(
	return $return . SelectInput(
		$value,
		'values[' . $id . '][' . $column . ']',
		'',
		$options,
		'N/A',
		$extra,
		$id !== 'new'
	);
}


/**
 * Get Categories by type, for current school.
 *
 * @example $options = GetSICategoryOptions( $column );
 *
 * @param string  $type     Category Type: CATEGORY|STATUS|LOCATION|WORK_ORDER|USER_ID.
 *
 * @return array  Categories details belonging to this type.
 */
function GetSICategoryOptions( $category_type )
{
	static $categories = array();

	if ( ! $category_type )
	{
		return array();
	}

	if ( isset( $categories[ $category_type ] ) )
	{
		return $categories[ $category_type ];
	}

	$categories_RET = DBGet( DBQuery( "SELECT CATEGORY_ID,TITLE
		FROM SCHOOL_INVENTORY_CATEGORIES sic
		WHERE CATEGORY_TYPE='" . $category_type . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER,TITLE" ) );

	foreach ( (array) $categories_RET as $category )
	{
		$categories[ $category_type ][ $category['CATEGORY_ID'] ] = $category['TITLE'];
	}

	return $categories[ $category_type ];
}


function MakeSIFileInput( $value, $name )
{
	global $THIS_RET, $FileUploadsPath;

	if ( ! $THIS_RET['ITEM_ID'] )
	{
		$id = 'valuesnewFILE';

		$file_input_html = '<input type="file" id="valuesnewFILE" name="FILE"
			style="width: 230px; padding: 0;"
			title="' . sprintf( _( 'Maximum file size: %01.0fMb' ), FileUploadMaxSize() ) .
			'" /><span class="loading"></span>';

		$button = button( 'add' );

		return InputDivOnclick( $id, $file_input_html, $button, '' );
	}

	$id = $THIS_RET['ID'];

	if ( ! $value
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $value;
	}

	$image_exts = array( '.png', '.gif', '.jpg' );

	$file_ext = mb_substr( $value, -4 );

	if ( in_array( $file_ext, $image_exts ) )
	{
		$photo = '<img src="' . $value . '" style="max-width: 290px" />';

		$button = '<img src="assets/themes/' . Preferences( 'THEME' ) .
			'/btn/visualize.png" class="button bigger" />';

		// It is a photo. Add Tip message.
		return MakeTipMessage( $photo, dgettext( 'School_Inventory', 'Photo' ), $button );
	}

	// It is a document. Download.
	$button = '<img src="assets/themes/' . Preferences( 'THEME' ) .
		'/btn/download.png" class="button bigger" />';

	return '<a href="' . $value .
		'" title="' . str_replace( $FileUploadsPath . 'SchoolInventory/', '', $value ) .
		'" target="_blank">' . $button . '</a>';
}


function MakeSICategoryRemove( $value, $column )
{
	global $THIS_RET;

	if ( ! AllowEdit() )
	{
		return '';
	}

	if ( $THIS_RET['CATEGORY_ID'] < 1 )
	{
		return '';
	}

	return button(
		'remove',
		'',
		'"Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=remove&category_id=' . $THIS_RET['CATEGORY_ID'] . '"'
	);
}
