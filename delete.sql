
/**********************************************************
 delete.sql file
 Required as install.sql file present
 - Delete profile exceptions
***********************************************************/

--
-- Delete profile exceptions
--

DELETE FROM profile_exceptions WHERE modname='School_Inventory/SchoolInventory.php';
