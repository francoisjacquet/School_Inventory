<?php
/**
 * School Inventory
 *
 * @package School Inventory module
 */

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'ProgramFunctions/TipMessage.fnc.php';
require_once 'includes/SchoolInventory.fnc.php';

DrawHeader( ProgramTitle() );

// Save.
if ( $_REQUEST['modfunc'] === 'save'
	&& $_REQUEST['values']
	&& $_POST['values']
	&& isset( $_REQUEST['category_id'] )
	&& isset( $_REQUEST['category_type'] )
	&& AllowEdit() )
{
	$table = 'SCHOOL_INVENTORY_CATEGORIES';

	$id_column = 'CATEGORY_ID';

	if ( $_REQUEST['category_type'] !== '' )
	{
		$table = 'SCHOOL_INVENTORY_ITEMS';

		$id_column = 'ITEM_ID';
	}

	$category_columns = array(
		'CATEGORY',
		'STATUS',
		'LOCATION',
		'WORK_ORDER',
		'USER_ID',
	);

	$file_attached_ext_white_list = array(
		/**
		 * Extensions white list.
		 *
		 * Common file types.
		 * Obviously, we won't include executable types
		 * .php, .sql, .js, .exe...
		 * If you file type is not white listed,
		 * put it in a ZIP archive!
		 *
		 * @link http://fileinfo.com/filetypes/common
		 */
		// Micro$oft Office.
		'.doc',
		'.docx',
		'.xls',
		'.xlsx',
		'.xlr',
		'.pps',
		'.ppt',
		'.pptx',
		'.wps',
		'.wpd',
		'.rtf',
		// Libre Office.
		'.odt',
		'.ods',
		'.odp',
		// Images.
		'.jpg',
		'.jpeg',
		'.png',
		'.gif',
		'.bmp',
		'.svg',
		'.ico',
		'.psd',
		'.ai',
		'.eps',
		'.ps',
		// Audio.
		'.mp3',
		'.ogg',
		'.wav',
		'.mid',
		'.wma',
		// Video.
		'.avi',
		'.mp4',
		'.mpg',
		'.ogv',
		'.webm',
		'.wmv',
		'.mov',
		'.m4v',
		'.flv',
		'.swf',
		// Text.
		'.txt',
		'.pdf',
		'.md',
		'.csv',
		'.tex',
		// Web.
		'.xml',
		'.xhtml',
		'.html',
		'.htm',
		'.css',
		'.rss',
		// Compressed.
		'.zip',
		'.rar',
		'.7z',
		'.tar',
		'.gz',
	);

	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		if ( isset( $columns['QUANTITY'] ) )
		{
			// Sanitize quantity.
			$value = preg_replace( '/[^0-9.-]/', '', $value );

			if ( ! is_numeric( $value ) )
			{
				$value = 1;
			}
		}

		if ( $id !== 'new'
			&& mb_strpos( $id, 'new' ) === false )
		{
			$sql = "UPDATE " . DBEscapeIdentifier( $table ) . " SET ";

			$go = false;

			foreach ( (array) $columns as $column => $value )
			{
				if ( $table === 'SCHOOL_INVENTORY_ITEMS'
					&& ( in_array( $column, $category_columns )
						|| mb_strpos( $column, '_WAS_NA' ) !== false ) )
				{
					continue;
				}

				$go = true;

				$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) .
				" WHERE " . DBEscapeIdentifier( $id_column ) . "='" . $id . "'";

			if ( $go )
			{
				DBQuery( $sql );
			}

			if ( $table === 'SCHOOL_INVENTORY_ITEMS' )
			{
				$categories_sql = '';

				// Relate Item to Categories.
				foreach ( (array) $category_columns as $category_col )
				{
					if ( ! isset( $columns[ $category_col ] ) )
					{
						continue;
					}

					$category_where_sql = "ITEM_ID='" . $id . "'
							AND CATEGORY_TYPE='" . $category_col . "'";

					if ( $columns[ $category_col ] )
					{
						// Check if was N/A.
						if ( $columns[ $category_col . '_WAS_NA' ] )
						{
							// Insert.
							$categories_sql .= "INSERT INTO SCHOOL_INVENTORY_CATEGORYXITEM
								(CATEGORY_ID,ITEM_ID,CATEGORY_TYPE)
								values('" . $columns[ $category_col ] . "','" . $id . "','" . $category_col . "');";
						}
						else
						{
							// Update.
							$categories_sql .= "UPDATE SCHOOL_INVENTORY_CATEGORYXITEM SET
								CATEGORY_ID='" . $columns[ $category_col ] . "'
								WHERE " . $category_where_sql . ";";
						}
					}
					elseif ( ! $columns[ $category_col . '_WAS_NA' ] )
					{
						// Delete.
						$categories_sql .= "DELETE FROM SCHOOL_INVENTORY_CATEGORYXITEM
							WHERE " . $category_where_sql . ";";
					}
				}

				if ( $categories_sql )
				{
					DBQuery( $categories_sql );
				}
			}
		}
		// New: check for Title.
		elseif ( $columns['TITLE'] )
		{
			if ( $table === 'SCHOOL_INVENTORY_CATEGORIES' )
			{
				// If category, extract category type from id.
				$columns['CATEGORY_TYPE'] = mb_substr( $id, 3 );
			}

			$sql = "INSERT INTO " . DBEscapeIdentifier( $table ) . " ";

			$fields = DBEscapeIdentifier( $id_column ) . ',SCHOOL_ID,';

			$id = DBGet( DBQuery( "SELECT " . db_seq_nextval( $table . '_SEQ' ) . " AS ID;" ) );

			$id = $id[1]['ID'];

			$values = "'" . $id . "','" . UserSchool() . "',";

			if ( $table === 'SCHOOL_INVENTORY_ITEMS'
				&& isset( $_FILES['FILE'] ) )
			{
				$file_ext = mb_strtolower( mb_strrchr( $_FILES['FILE']['name'], '.' ) );

				if ( in_array( $file_ext, array( '.jpg', '.jpeg', '.png', '.gif' ) ) )
				{
					// Photo.
					$columns['FILE'] = ImageUpload(
						'FILE',
						array( 'width' => 600, 'height' => 600 ),
						$FileUploadsPath . 'SchoolInventory/'
					);
				}
				else
				{
					// Document.
					$columns['FILE'] = FileUpload(
						'FILE',
						$FileUploadsPath . 'SchoolInventory/',
						$file_attached_ext_white_list,
						0,
						$error
					);
				}
			}

			$go = 0;
			foreach ( (array) $columns as $column => $value)
			{
				if ( $table === 'SCHOOL_INVENTORY_ITEMS'
					&& in_array( $column, $category_columns ) )
				{
					continue;
				}

				if ( ! empty( $value )
					|| $value === '0' )
				{
					$fields .= DBEscapeIdentifier( $column ) . ',';
					$values .= "'" . $value . "',";
					$go = true;
				}
			}

			$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

			if ( $go )
			{
				DBQuery( $sql );
			}

			// TODO.
			if ( $go
				&& $table === 'SCHOOL_INVENTORY_ITEMS' )
			{
				// Relate Item to Categories.
				$insert_cat_sql = '';

				foreach ( (array) $category_columns as $category_col )
				{
					if ( isset( $columns[ $category_col ] )
						&& $columns[ $category_col ] )
					{
						$insert_cat_sql .= "('" . $id . "','" .
							$columns[ $category_col ] . "','" . $category_col . "'),";
					}
				}

				if ( $insert_cat_sql )
				{
					DBQuery( "INSERT INTO SCHOOL_INVENTORY_CATEGORYXITEM
						(ITEM_ID,CATEGORY_ID,CATEGORY_TYPE) values " .
						mb_substr( $insert_cat_sql, 0, -1 ) );
				}
			}
		}

		// $error[] = _( 'Please enter a valid Sort Order.' );
		// $note[] = dgettext( 'School_Inventory', 'The school inventory has been updated.' );
	}

	// Unset modfunc & redirect URL.
	if ( function_exists( 'RedirectURL' ) )
	{
		// @since 3.3.
		RedirectURL( 'modfunc' );
	}
	else
	{
		// @deprecated.
		unset( $_SESSION['_REQUEST_vars']['modfunc'] );

		unset( $_REQUEST['modfunc'] );
	}
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( isset( $_REQUEST['item_id'] ) )
	{
		if ( DeletePrompt( _( 'Item' ) ) )
		{
			// Uploaded file?
			$file_RET = DBGet( DBQuery( "SELECT FILE
				FROM SCHOOL_INVENTORY_ITEMS
				WHERE ITEM_ID='" . $_REQUEST['item_id'] . "'
				AND FILE IS NOT NULL" ) );

			if ( $file_RET
				&& $file_RET[1]['FILE'] )
			{
				// Delete file.
				@unlink( $file_RET[1]['FILE'] );
			}

			DBQuery( "DELETE FROM SCHOOL_INVENTORY_ITEMS
				WHERE ITEM_ID='" . $_REQUEST['item_id'] . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			DBQuery( "DELETE FROM SCHOOL_INVENTORY_CATEGORYXITEM
				WHERE ITEM_ID='" . $_REQUEST['item_id'] . "'" );

			// Unset modfunc & ID & redirect URL.
			if ( function_exists( 'RedirectURL' ) )
			{
				// @since 3.3.
				RedirectURL( array( 'modfunc', 'item_id' ) );
			}
			else
			{
				// @deprecated.
				$_REQUEST['modfunc'] = false;
				$_SESSION['_REQUEST_vars']['modfunc'] = false;
				$_SESSION['_REQUEST_vars']['item_id'] = false;
				$_REQUEST['item_id'] = false;
			}
		}
	}
	elseif ( isset( $_REQUEST['category_id'] )
		&& $_REQUEST['category_id'] > 0 )
	{
		if ( DeletePrompt( _( 'Category' ) ) )
		{
			DBQuery( "DELETE FROM SCHOOL_INVENTORY_CATEGORIES
				WHERE CATEGORY_ID='" . $_REQUEST['category_id'] . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			DBQuery( "DELETE FROM SCHOOL_INVENTORY_CATEGORYXITEM
				WHERE CATEGORY_ID='" . $_REQUEST['category_id'] . "'" );

			// Unset modfunc & ID & redirect URL.
			if ( function_exists( 'RedirectURL' ) )
			{
				// @since 3.3.
				RedirectURL( array( 'modfunc', 'category_id' ) );
			}
			else
			{
				// @deprecated.
				$_REQUEST['modfunc'] = false;
				$_SESSION['_REQUEST_vars']['modfunc'] = false;
				$_SESSION['_REQUEST_vars']['category_id'] = false;
				$_REQUEST['category_id'] = false;
			}
		}
	}
}

// Display errors if any.
echo ErrorMessage( $error );

// Display notes if any.
echo ErrorMessage( $note, 'note' );


// Display Search screen or Student list.
if ( empty( $_REQUEST['modfunc'] ) )
{
	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] .
		'&category_id=' . $_REQUEST['category_id'] .
		'&category_type=' . $_REQUEST['category_type'] .
		'&modfunc=save" method="POST" enctype="multipart/form-data">';

	$category_types = array(
		'CATEGORY' => _( 'Category' ),
		'STATUS' => _( 'Status' ),
		'LOCATION' => dgettext( 'School_Inventory', 'Location' ),
		// 'WORK_ORDER' => _( 'Work Order' ),
		'USER_ID' => dgettext( 'School_Inventory', 'Person' ),
	);

	if ( isset( $_REQUEST['category_id'] )
		&& isset( $_REQUEST['category_type'] )
		&& ( ( array_key_exists( $_REQUEST['category_type'], $category_types )
				&& GetSICategory( $_REQUEST['category_id'], $_REQUEST['category_type'] ) )
			|| $_REQUEST['category_id'] === '-1' ) )
	{
		if ( array_key_exists( $_REQUEST['category_type'], $category_types ) )
		{
			// Display Category title in header.
			$category = GetSICategory( $_REQUEST['category_id'], $_REQUEST['category_type'] );

			$category_header = '<b>' . $category_types[ $category['CATEGORY_TYPE'] ] . ':</b> ' .
				$category['TITLE'];
		}
		else
		{
			// All items.
			$category_header = '<b>' . dgettext( 'School_Inventory', 'All Items' ) . '</b>';
		}

		$back_link = PreparePHP_SELF( $_GET, array( 'category_id', 'category_type', 'modfunc' ) );

		DrawHeader(
			'<b><a href="' . $back_link . '">&laquo; ' . dgettext( 'School_Inventory', 'Back' ) . '</a></b>',
			SubmitButton( _( 'Save' ) )
		);

		DrawHeader( $category_header );

		// Display items, filtered by that category.
		$items_RET = GetSIItemsByCategory( $_REQUEST['category_id'], $_REQUEST['category_type'] );

		// Display list.
		$columns = array(
			'TITLE' => _( 'Name' ),
			'QUANTITY' => dgettext( 'School_Inventory', 'Quantity' ),
			'FILE' => _( 'File' ),
			'COMMENTS' => _( 'Comments' ),
		);

		$columns += $category_types;

		$LO_options = array( 'add' => true );

		$link = array();

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=remove&category_id=' . $_REQUEST['category_id'] .
			'&category_type=' . $_REQUEST['category_type'];

		$link['remove']['variables'] = array( 'item_id' => 'ITEM_ID' );

		$link['add']['html'] = array(
			'TITLE' => MakeSITextInput( '', 'TITLE' ),
			'QUANTITY' => MakeSINumberInput( '', 'QUANTITY' ),
			'FILE' => MakeSIFileInput( '', 'FILE' ),
			'COMMENTS' => MakeSITextInput( '', 'COMMENTS' ),
			'CATEGORY' => MakeSICategorySelect( '', 'CATEGORY' ),
			'STATUS' => MakeSICategorySelect( '', 'STATUS' ),
			'LOCATION' => MakeSICategorySelect( '', 'LOCATION' ),
			'WORK_ORDER' => MakeSICategorySelect( '', 'WORK_ORDER' ),
			'USER_ID' => MakeSICategorySelect( '', 'USER_ID' ),
		);

		ListOutput(
			$items_RET,
			$columns,
			'Item',
			'Items',
			$link,
			array(),
			$LO_options
		);
	}
	else
	{
		$all_link = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&category_id=-1&category_type=-1';

		$items_total = GetSIItemsTotal();

		DrawHeader(
			'<b><a href="' . $all_link . '">' . dgettext( 'School_Inventory', 'All Items' ) .
				'</a></b> (' . $items_total . ')',
			SubmitButton( _( 'Save' ) )
		);

		// Display Categories.
		// Categories.

		echo '<div class="st">';

		SICategoryItemsListOutput( 'CATEGORY', 'Category', 'Categories' );

		echo '</div>';

		// Statuses.
		echo '<div class="st">';

		SICategoryItemsListOutput( 'STATUS', 'Status', 'Statuses' );

		echo '</div>';

		// Locations.
		echo '<div class="st">';

		SICategoryItemsListOutput(
			'LOCATION',
			dgettext( 'School_Inventory', 'Location' ),
			dgettext( 'School_Inventory', 'Locations' )
		);

		echo '</div>';

		// Work orders.
		/*echo '<div class="st">';

		SICategoryItemsListOutput( 'WORK_ORDER', 'Work Order', 'Work Orders' );

		echo '</div>';*/

		// Person.
		echo '<div class="st">';

		SICategoryItemsListOutput(
			'USER_ID',
			dgettext( 'School_Inventory', 'Person' ),
			dgettext( 'School_Inventory', 'Persons' )
		);

		echo '</div>';

	}

	// Submit & Close Form.
	echo '<br style="clear: left;" /><div class="center">' .
		SubmitButton( _( 'Save' ) ) . '</div>';
	echo '</form>';
}

