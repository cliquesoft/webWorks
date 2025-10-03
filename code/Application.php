<?php
# Application.php
#
# Created	2014/01/30 by Dave Henderson (support@cliquesoft.org)
# Updated	2025/07/14 by Dave Henderson (support@cliquesoft.org)
#
# Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
# device, this software is licensed under the Cliquesoft Public License (CPLv2)
# as found on the Cliquesoft website at www.cliquesoft.org.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.


# Constant Definitions
define("MODULE",'Application');					# the name of this module (NOTE: this can be the same as the PROJECT constant in the envars.php file)
define("SCRIPT",basename($_SERVER['SCRIPT_NAME']));		# the name of this script (for tracing bugs and automated messages)

# Module Requirements						  NOTE: MUST come below Module Constant Definitions
require_once('../../sqlaccess');
require_once('../data/_modules/ApplicationSettings/config.php');
require_once('_Project.php');
require_once('_Contact.php');
require_once('_Database.php');
require_once('_Security.php');

# Start or resume the PHP session				  NOTE: gains access to $_SESSION variables in this script
session_start();




# format the dates in UTC
$_ = gmdate("Y-m-d H:i:s",time());				# used this mannor so all the times will be the exact same (also see http://php.net/manual/en/function.gmdate.php)

header('Content-Type: text/xml; charset=utf-8');
echo "<?xml version='1.0' encoding='UTF-8'?>\n\n";




# Define general info for any error generated below
$__sInfo['name'] = 'Unknown';
$__sInfo['contact'] = 'Unknown';
$__sInfo['other'] = 'n/a';




# -- Application API --

switch($_POST['T']) {						# Process the submitted (T)arget

    # -- General Tab --

    case 'contact':
	# Loads the contact information
	if ($_POST['A'] == 'load') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('read')) { exit(); }


		# if we've made it here, the user is authorized to interact

		# 1. Obtain all the requested record information
		$__sInfo['error'] = "Failed to find the requested contacts in the database.";
		$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."Application_Contacts WHERE id=? LIMIT 1";
		$__sInfo['values'] = '[i] '.$_POST['id'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('i', $_POST['id']);
		$stmt->execute();
		$Contact = $stmt->get_result();


		echo "<s>\n";
		echo "   <xml>\n";
		echo "	<contacts>\n";

		while ($contact = $Contact->fetch_assoc())
			{ echo "	   <contact id=\"".$contact['id']."\" sType=\"".$contact['type']."\" nRow=\"".$contact['rowID']."\" sOPoID=\"".safeXML($contact['OPoID'])."\" sName=\"".safeXML($contact['name'])."\" sEmail=\"".safeXML($contact['workEmail'])."\" nPhone=\"".$contact['workPhone']."\" nExt=\"".$contact['workExt']."\" nMobile=\"".$contact['workMobile']."\" bSMS=\"".$contact['workMobileSMS']."\" bMail=\"".$contact['workMobileEmail']."\" sTitle=\"".safeXML($contact['jobTitle'])."\" />\n"; }

		echo "	</contacts>\n";
		echo "   </xml>\n";
		echo "</s>";
		exit();




	# Saves the (new) contact information
	} else if ($_POST['A'] == 'save') {
		# Define some variables						   NOTE: these still have to pass validation for $_POST below
		$sType = $_POST['sType'];
		$sModule = str_replace(' ', '', $_POST['sModule']);		# the module name in CamelCase

		# strip any non-numbers from the following values:
		$_POST['n'.$sType.'ContactPhone_'.$sModule] = preg_replace('/[^0-9]/','',$_POST['n'.$sType.'ContactPhone_'.$sModule]);		// http://stackoverflow.com/questions/6604455/php-code-to-remove-everything-but-numbers
		$_POST['n'.$sType.'ContactMobile_'.$sModule] = preg_replace('/[^0-9]/','',$_POST['n'.$sType.'ContactMobile_'.$sModule]);

		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9+')) { exit(); }
		if (! validate($_POST['sModule'],24,'a-zA-Z0-9_ ')) { exit(); }
		if (! validate($_POST['sType'],8,'{Customer|Vendor|Provider}')) { exit(); }
		if (! validate($_POST['s'.$sType.'ContactOPoID_'.$sModule],64,'a-zA-Z0-9_\.\-')) { exit(); }
		if (! validate($_POST['s'.$sType.'Contact_'.$sModule],128,'!=<>;')) { exit(); }
		if (! validate($_POST['s'.$sType.'ContactEmail_'.$sModule],128,'a-zA-Z0-9_\.@\-')) { exit(); }
		if (! validate($_POST['n'.$sType.'ContactPhone_'.$sModule],15,'0-9')) { exit(); }
		if (! validate($_POST['n'.$sType.'ContactExt_'.$sModule],7,'0-9')) { exit(); }
		if (! validate($_POST['n'.$sType.'ContactMobile_'.$sModule],15,'0-9')) { exit(); }
		if (! validate($_POST['b'.$sType.'ContactMobileSMS_'.$sModule],1,'{0|1}')) { exit(); }
		if (! validate($_POST['b'.$sType.'ContactMobileEmail_'.$sModule],1,'{0|1}')) { exit(); }
		if (! validate($_POST['s'.$sType.'ContactTitle_'.$sModule],64,'a-zA-Z0-9 _\.@\-')) { exit(); }
		if (array_key_exists('n'.$sType.'ContactList_'.$sModule, $_POST))
			{ if (! validate($_POST['n'.$sType.'ContactList_'.$sModule],20,'0-9')) {exit();} }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if ($_POST['id'] == '+')
			{ if (! checkPermission('add')) {exit();} }
		else
			{ if (! checkPermission('write')) {exit();} }


		# if we've made it here, the user is authorized to interact

		# If we're adding a new record, then...
		if ($_POST['id'] == '+') {
			$__sInfo['error'] = "Failed to create a new contact in the database.";
			$__sInfo['command'] = "INSERT INTO ".DB_PRFX."Application_Contacts (type,rowID,OPoID,name,workEmail,workPhone,workExt,workMobile,workMobileSMS,workMobileEmail,jobTitle) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
			$__sInfo['values'] = '[s] '.strtolower($sType).', [i] '.$_POST['id'].', [s] '.$_POST['s'.$sType.'ContactOPoID_'.$sModule].', [s] '.$_POST['s'.$sType.'Contact_'.$sModule].', [s] '.$_POST['s'.$sType.'ContactEmail_'.$sModule].', [i] '.$_POST['n'.$sType.'ContactPhone_'.$sModule].', [i] '.$_POST['n'.$sType.'ContactExt_'.$sModule].', [i] '.$_POST['n'.$sType.'ContactMobile_'.$sModule].', [i] '.$_POST['b'.$sType.'ContactMobileSMS_'.$sModule].', [i] '.$_POST['b'.$sType.'ContactMobileEmail_'.$sModule].', [s] '.$_POST['s'.$sType.'ContactTitle_'.$sModule];
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			$stmt->bind_param('sisssiiiiis', strtolower($sType), $_POST['id'], $_POST['s'.$sType.'ContactOPoID_'.$sModule], $_POST['s'.$sType.'Contact_'.$sModule], $_POST['s'.$sType.'ContactEmail_'.$sModule], $_POST['n'.$sType.'ContactPhone_'.$sModule], $_POST['n'.$sType.'ContactExt_'.$sModule], $_POST['n'.$sType.'ContactMobile_'.$sModule], $_POST['b'.$sType.'ContactMobileSMS_'.$sModule], $_POST['b'.$sType.'ContactMobileEmail_'.$sModule], $_POST['s'.$sType.'ContactTitle_'.$sModule]);
			$stmt->execute();

			echo "<s><msg>The contact has been created successfully!</msg><data id='".$stmt->insert_id."'></data></s>";

		# Otherwise we're updating a records information, so...
		} else {
			$__sInfo['error'] = "Failed to update the requested contact record in the database.";
			$__sInfo['command'] = "UPDATE ".DB_PRFX."Application_Contacts SET OPoID=?,name=?,workEmail=?,workPhone=?,workExt=?,workMobile=?,workMobileSMS=?,workMobileEmail=?,jobTitle=? WHERE id=?";
			$__sInfo['values'] = '[s] '.$_POST['s'.$sType.'ContactOPoID_'.$sModule].', [s] '.$_POST['s'.$sType.'Contact_'.$sModule].', [s] '.$_POST['s'.$sType.'ContactEmail_'.$sModule].', [i] '.$_POST['n'.$sType.'ContactPhone_'.$sModule].', [i] '.$_POST['n'.$sType.'ContactExt_'.$sModule].', [i] '.$_POST['n'.$sType.'ContactMobile_'.$sModule].', [i] '.$_POST['b'.$sType.'ContactMobileSMS_'.$sModule].', [i] '.$_POST['b'.$sType.'ContactMobileEmail_'.$sModule].', [s] '.$_POST['s'.$sType.'ContactTitle_'.$sModule].', [i] '.$_POST['n'.$sType.'ContactList_'.$sModule];
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			$stmt->bind_param('sssiiiiisi', $_POST['s'.$sType.'ContactOPoID_'.$sModule], $_POST['s'.$sType.'Contact_'.$sModule], $_POST['s'.$sType.'ContactEmail_'.$sModule], $_POST['n'.$sType.'ContactPhone_'.$sModule], $_POST['n'.$sType.'ContactExt_'.$sModule], $_POST['n'.$sType.'ContactMobile_'.$sModule], $_POST['b'.$sType.'ContactMobileSMS_'.$sModule], $_POST['b'.$sType.'ContactMobileEmail_'.$sModule], $_POST['s'.$sType.'ContactTitle_'.$sModule], $_POST['n'.$sType.'ContactList_'.$sModule]);
			$stmt->execute();

			echo "<s><msg>The contact has been updated successfully!</msg></s>";
		}
		exit();




	# Delete/Disable the contact
	} else if ($_POST['A'] == 'delete' || $_POST['A'] == 'disable') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('del')) { exit(); }


		# if we've made it here, the user is authorized to interact

		# 1. "Delete" the requested record
		if ($_POST['A'] == 'delete') {
			$__sInfo['error'] = "Failed to delete the requested contact from the database.";
			$__sInfo['command'] = "DELETE FROM ".DB_PRFX."Application_Contacts WHERE id=? LIMIT 1";
		} else if ($_POST['A'] == 'disable') {
			$__sInfo['error'] = "Failed to disable the requested contact from the database.";
			$__sInfo['command'] = "UPDATE ".DB_PRFX."Application_Contacts SET bDisabled=1 WHERE id=?";
		}
		$__sInfo['values'] = '[i] '.$_POST['id'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('i', $_POST['id']);
		$stmt->execute();

		echo "<s><msg>The contact has been deleted successfully!</msg></s>";
		exit();
	}
	break;




    case 'courier':
	# Loads the courier information
	if ($_POST['A'] == 'load') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('read')) { exit(); }


		# if we've made it here, the user is authorized to interact

		# 1. Obtain all the requested record information
		$__sInfo['error'] = "Failed to find the requested freight accounts in the database.";
		$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."Application_FreightAccounts WHERE id=? LIMIT 1";
		$__sInfo['values'] = '[i] '.$_POST['id'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('i', $_POST['id']);
		$stmt->execute();
		$Couriers = $stmt->get_result();


		echo "<s>\n";
		echo "   <xml>\n";
		echo "	<couriers>\n";

		$salt = file_get_contents('../../denaccess');				# obtain the decryption string
		while ($courier = $Couriers->fetch_assoc()) {
			if (strlen($courier['account']) > 1) { $sAccount = Cipher::decrypt($courier['account'], $salt); } else { $sAccount = ''; }
			echo "	   <courier id=\"".$courier['id']."\" sName=\"".safeXML($courier['name'])."\" sAccount=\"".$sAccount."\" />\n";
		}

		echo "	</couriers>\n";
		echo "   </xml>\n";
		echo "</s>";
		exit();




	# Saves the courier information
	} else if ($_POST['A'] == 'save') {
		# Define some variables						   NOTE: these still have to pass validation for $_POST below
		$sType = $_POST['sType'];
		$sModule = str_replace(' ', '', $_POST['sModule']);		# the module name in CamelCase

		# validate all submitted data
		if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9+')) { exit(); }
		if (! validate($_POST['sModule'],24,'a-zA-Z0-9_ ')) { exit(); }
		if (! validate($_POST['sType'],12,'{Customer|Business}')) { exit(); }
		if (! validate($_POST['s'.$sType.'ShipAccount_'.$sModule],32,'a-zA-Z0-9\-')) { exit(); }
		if (! validate($_POST['s'.$sType.'ShipName_'.$sModule],48,'a-zA-Z0-9 _\.@\-')) { exit(); }
		if (array_key_exists('n'.$sType.'ShipList_'.$sModule, $_POST))
			{ if (! validate($_POST['n'.$sType.'ShipList_'.$sModule],20,'0-9')) {exit();} }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if ($_POST['id'] == '+')
			{ if (! checkPermission('add')) {exit();} }
		else
			{ if (! checkPermission('write')) {exit();} }


		# if we've made it here, the user is authorized to interact

		# If we're adding a new record, then...
		if ($_POST['id'] == '+') {
			# 1. Encrypt the account number
			$sSalt = file_get_contents('../../denaccess');
			$sAccount = Cipher::encrypt($_POST['s'.$sType.'ShipAccount_'.$sModule], $sSalt);

			# 2. Write the record in the database
			$__sInfo['error'] = "Failed to create a new freight account in the database.";
			$__sInfo['command'] = "INSERT INTO ".DB_PRFX."Application_FreightAccounts (type,rowID,name,account) VALUES ('customer','1',?,?)";
			$__sInfo['values'] = '[s] '.$_POST['s'.$sType.'ShipName_'.$sModule].', [s] '.$sAccount;
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			$stmt->bind_param('ss', $_POST['s'.$sType.'ShipName_'.$sModule], $sAccount);
			$stmt->execute();

			echo "<s><msg>The freight account has been created successfully!</msg><data id='".$stmt->insert_id."'></data></s>";

		# Otherwise we're updating a records information, so...
		} else {
			# 1. Decrypt the account number
			$sSalt = file_get_contents('../../denaccess');
			$sAccount = Cipher::encrypt($_POST['s'.$sType.'ShipAccount_'.$sModule], $sSalt);	# encode the account number

			# 2. Update the record in the database
			$__sInfo['error'] = "Failed to update the requested freight account in the database.";
			$__sInfo['command'] = "UPDATE ".DB_PRFX."Application_FreightAccounts SET name=?,account=? WHERE id=?";
			$__sInfo['values'] = '[s] '.$_POST['s'.$sType.'ShipName_'.$sModule].', [s] '.$sAccount.', [i] '.$_POST['n'.$sType.'ShipList_'.$sModule];
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			$stmt->bind_param('ssi', $_POST['s'.$sType.'ShipName_'.$sModule], $sAccount, $_POST['n'.$sType.'ShipList_'.$sModule]);
			$stmt->execute();

			echo "<s><msg>The freight account has been updated successfully!</msg></s>";
		}
		exit();



	# Delete/Disable the courier
	} else if ($_POST['A'] == 'delete' || $_POST['A'] == 'disable') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('del')) { exit(); }


		# if we've made it here, the user is authorized to interact

		# 1. "Delete" the requested record
		if ($_POST['A'] == 'delete') {
			$__sInfo['error'] = "Failed to delete the requested freight account from the database.";
			$__sInfo['command'] = "DELETE FROM ".DB_PRFX."Application_Contacts WHERE id=? LIMIT 1";
		} else if ($_POST['A'] == 'disable') {
			$__sInfo['error'] = "Failed to disable the requested freight account from the database.";
			$__sInfo['command'] = "UPDATE ".DB_PRFX."Application_Contacts SET bDisabled=1 WHERE id=?";
		}
		$__sInfo['values'] = '[i] '.$_POST['id'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('i', $_POST['id']);
		$stmt->execute();

		echo "<s><msg>The freight account has been deleted successfully!</msg></s>";
		exit();
	}
	break;




# LEFT OFF - move these into BusinessManagement since you'd have to have a busines to conduct financial transactions
    case 'bank':
	# Loads the bank information
	if ($_POST['A'] == 'load') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('read')) { exit(); }


		# if we've made it here, the user is authorized to interact

		# 1. Obtain all the requested record information
		$__sInfo['error'] = "Failed to find the requested bank accounts in the database.";
		$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."BusinessConfiguration_BankAccounts WHERE id=? LIMIT 1";
		$__sInfo['values'] = '[i] '.$_POST['id'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('i', $_POST['id']);
		$stmt->execute();
		$Banks = $stmt->get_result();

		echo "<s>\n";
		echo "   <xml>\n";
		echo "	<banks>\n";

		$salt = file_get_contents('../../denaccess');			# obtain the decryption string
		while ($bank = $Banks->fetch_assoc()) {
			if (strlen($bank['account']) > 1) { $nAccount = Cipher::decrypt($bank['account'], $salt); } else { $nAccount = ''; }
			echo "	   <bank id=\"".$bank['id']."\" sName=\"".safeXML($bank['name'])."\" nRouting=\"".$bank['routing']."\" nAccount=\"".$nAccount."\" sType=\"".$bank['checkType']."\" nCheck=\"".$bank['checkNo']."\" />\n";
		}

		echo "	</banks>\n";
		echo "   </xml>\n";
		echo "</s>";
		exit();




	# Saves the (new) bank information
	} else if ($_POST['A'] == 'save') {
		# Define some variables						   NOTE: these still have to pass validation for $_POST below
		$sType = $_POST['sType'];
		$sModule = str_replace(' ', '', $_POST['sModule']);		# the module name in CamelCase

		# strip any non-numbers from the following values:
		$_POST['n'.$sType.'BankRouting_'.$sModule] = preg_replace('/[^0-9]/','',$_POST['n'.$sType.'BankRouting_'.$sModule]);
		$_POST['n'.$sType.'BankAccount_'.$sModule] = preg_replace('/[^0-9]/','',$_POST['n'.$sType.'BankAccount_'.$sModule]);

		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9+')) { exit(); }
		if (! validate($_POST['sModule'],24,'a-zA-Z0-9_ ')) { exit(); }
		if (! validate($_POST['sType'],12,'{Business|Location|Vendor|Provider}')) { exit(); }
		if (! validate($_POST['s'.$sType.'BankDesc_'.$sModule],48,'!=<>;')) { exit(); }
		if (! validate($_POST['n'.$sType.'BankRouting_'.$sModule],24,'0-9')) { exit(); }
		if (! validate($_POST['n'.$sType.'BankAccount_'.$sModule],24,'0-9')) { exit(); }
		if ($_POST['type'] != 'Business') {
			$sCheck = '';
			$nCheck = 0;
		} else {
			if (! validate($_POST['s'.$sType.'BankCheckType_'.$sModule],24,'a-zA-Z0-9_\-')) { exit(); }
			if (! validate($_POST['n'.$sType.'BankCheckNo_'.$sModule],10,'0-9')) { exit(); }
			$sCheck = $_POST['s'.$sType.'BankCheckType_'.$sModule];
			$nCheck = $_POST['n'.$sType.'BankCheckNo_'.$sModule];
		}
		if (array_key_exists('n'.$sType.'BankList_'.$sModule, $_POST))
			{ if (! validate($_POST['n'.$sType.'BankList_'.$sModule],20,'0-9')) {exit();} }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if ($_POST['id'] == '+')
			{ if (! checkPermission('add')) {exit();} }
		else
			{ if (! checkPermission('write')) {exit();} }


		# if we've made it here, the user is authorized to interact

		# If we're adding a new record, then...
		if ($_POST['id'] == '+') {
			# 1. Encrypt the account number
			$sSalt = file_get_contents('../../denaccess');
			$sAccount = Cipher::encrypt($_POST['n'.$sType.'BankAccount_'.$sModule], $sSalt);	# encode the account number

			# 2. Write the record in the database
			$__sInfo['error'] = "Failed to create a new bank account in the database.";
			$__sInfo['command'] = "INSERT INTO ".DB_PRFX."BusinessConfiguration_BankAccounts (type,rowID,name,routing,account,checkType,checkNo) VALUES (?,?,?,?,?,?,?)";
			$__sInfo['values'] = '[s] '.strtolower($sType).', [i] '.$_POST['id'].', [s] '.$_POST['s'.$sType.'BankDesc_'.$sModule].', [i] '.$_POST['n'.$sType.'BankRouting_'.$sModule].', [s] '.$sAccount.', [s] '.$sCheck.', [i] '.$nCheck;
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			$stmt->bind_param('sisissi', strtolower($sType), $_POST['id'], $_POST['s'.$sType.'BankDesc_'.$sModule], $_POST['n'.$sType.'BankRouting_'.$sModule], $sAccount, $sCheck, $nCheck);
			$stmt->execute();

			echo "<s><msg>The bank account has been created successfully!</msg><data id='".$stmt->insert_id."'></data></s>";

		# Otherwise we're updating record information, so...
		} else {
			# 1. Decrypt the account number
			$sSalt = file_get_contents('../../denaccess');
			$sAccount = Cipher::encrypt($_POST['n'.$sType.'BankAccount_'.$sModule], $sSalt);	# encode the account number

			# 2. Update the record in the database
			$__sInfo['error'] = "Failed to update the requested bank account in the database.";
			$__sInfo['command'] = "UPDATE ".DB_PRFX."BusinessConfiguration_BankAccounts SET name=?,routing=?,account=?,checkType=?,checkNo=? WHERE id=?";
			$__sInfo['values'] = '[s] '.$_POST['s'.$sType.'BankDesc_'.$sModule].', [i] '.$_POST['n'.$sType.'BankRouting_'.$sModule].', [s] '.$sAccount.', [s] '.$sCheck.', [i] '.$nCheck.', [i] '.$_POST['n'.$sType.'BankList_'.$sModule];
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			$stmt->bind_param('sissii', $_POST['s'.$sType.'BankDesc_'.$sModule], $_POST['n'.$sType.'BankRouting_'.$sModule], $sAccount, $sCheck, $nCheck, $_POST['n'.$sType.'BankList_'.$sModule]);
			$stmt->execute();

			echo "<s><msg>The bank account has been updated successfully!</msg></s>";
		}
		exit();




	# Delete/Disable the bank account
	} else if ($_POST['A'] == 'delete' || $_POST['A'] == 'disable') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('del')) { exit(); }


		# if we've made it here, the user is authorized to interact

		# 1. "Delete" the requested record
		if ($_POST['A'] == 'delete') {
			$__sInfo['error'] = "Failed to delete the requested bank account from the database.";
			$__sInfo['command'] = "DELETE FROM ".DB_PRFX."BusinessConfiguration_BankAccounts WHERE id=? LIMIT 1";
		} else if ($_POST['A'] == 'disable') {
			$__sInfo['error'] = "Failed to disable the requested bank account from the database.";
			$__sInfo['command'] = "UPDATE ".DB_PRFX."BusinessConfiguration_BankAccounts SET bDisabled=1 WHERE id=?";
		}
		$__sInfo['values'] = '[i] '.$_POST['id'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('i', $_POST['id']);
		$stmt->execute();

		echo "<s><msg>The bank account has been deleted successfully!</msg></s>";
		exit();
	}
	break;




    case 'associated':
	# Loads an associated item
	if ($_POST['A'] == 'load') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9')) { exit(); }
		if (! validate($_POST['sSource'],24,'a-zA-Z0-9_')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('read')) { exit(); }


		# if we've made it here, the user is authorized to interact

		# 1. Obtain the associated info from the DB
		$gbl_errs['error'] = "Failed to find the 'Associated Info' in the database.";
		$gbl_info['command'] = "SELECT id,sourceID,targetID,sourceTable, FROM ".DB_PRFX."Application_Associated WHERE id=? LIMIT 1";
		$gbl_info['values'] = '[i] '.$_POST['id'];
		$Associated = $linkDB->prepare($gbl_info['command']);
		$Associated->bind_param('i', $_POST['id']);
		$Associated->execute();
		$associated = $Associated->get_result()->fetch_assoc();

		if ($associated['sourceTable'] == $_POST['sSource'])
			{ echo "<s><data id='".$associated['targetID']."' module='".$associated['targetTable']."' /></s>\n"; }
		else
			{ echo "<s><data id='".$associated['sourceID']."' module='".$associated['sourceTable']."' /></s>\n"; }
		exit();
	}
	break;




    # -- Notes Tab --


    case 'note':
	# Loads the note information
	if ($_POST['A'] == 'load') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9')) { exit(); }
		if (! validate($_POST['sType'],12,'{Customer|Employee|WO}')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'ro','id,manager,departmentID',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('read')) { exit(); }


		# if we've made it here, the user is authorized to interact

		# 1. Obtain all the requested record information
		$sConditions = "'everyone','".$__sUser['departmentID']."'";		# define the default value which includes notes for 'everyone' and notes for their department (via the record ID)
		if ($__sUser['manager'] > 0) { $sConditions .= ",'managers'"; }		# if the user is a manager, then include those too!

		$__sInfo['error'] = "Failed to find the requested notes in the database.";
//		$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."Application_Notes WHERE type='".strtolower($_POST['sType'])."' AND rowID='".$customer['id']."' AND access IN (".$sConditions.")";
		$__sInfo['command'] = "
			SELECT
				tblNotes.*,tblEmployees.name AS creator
			FROM
				".DB_PRFX."Application_Notes tblNotes
			LEFT JOIN
				".DB_PRFX."Employees tblEmployees ON tblNotes.creatorID=tblEmployees.id
			WHERE
				type='".strtolower($_POST['sType'])."' AND rowID=? AND access IN (".$sConditions.")";
		$__sInfo['values'] = '[i] '.$_POST['id'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('i', $_POST['id']);
		$stmt->execute();
		$Notes = $stmt->get_result();


		echo "<s>\n";
		echo "   <xml>\n";
		echo "	<notes>\n";

		while ($note = $Notes->fetch_assoc())
			{ echo "	   <note id=\"".$note['id']."\" type=\"".$note['type']."\" creator=\"".safeXML($note['creator'])."\" created=\"".$note['created']."\" updated=\"".$note['updated']."\">".safeXML($note['note'])."</note>\n"; }

		echo "	</notes>\n";
		echo "   </xml>\n";
		echo "</s>";
		exit();




	# Saves the (new) note information
	} else if ($_POST['A'] == 'save') {
		# Define some variables						   NOTE: these still have to pass validation for $_POST below
		$sType = $_POST['sType'];
		$sModule = str_replace(' ', '', $_POST['sModule']);		# the module name in CamelCase

		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9+')) { exit(); }
		if (! validate($_POST['sModule'],24,'a-zA-Z0-9_ ')) { exit(); }
		if (! validate($_POST['sType'],12,'{Customer|Employee|WO}')) { exit(); }
		if ($_POST['sNoteAccess_'.$sModule] != 'everyone' && $_POST['sNoteAccess_'.$sModule] != 'managers')
			{ if (! validate($_POST['sNoteAccess_'.$sModule],20,'0-9')) {exit();} }
		if (! validate($_POST['sNote_'.$sModule],3072,'!=<>;')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id,name',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('add')) { exit(); }


		# if we've made it here, the user is authorized to interact

# VER2 - change 'type' column into 'fkTABLE' that way we can link the record to the module instead of by name
		$__sInfo['error'] = "Failed to create the note in the database.";
		$__sInfo['command'] = "INSERT INTO ".DB_PRFX."Application_Notes (type,rowID,creatorID,access,note,created,updated) VALUES ('".strtolower($_POST['sType'])."',?,'".$__sUser['id']."',?,?,'".$_."','".$_."')";
		if ($_POST['sNoteAccess_'.$sModule] != 'everyone' && $_POST['sNoteAccess_'.$sModule] != 'managers') {
			$__sInfo['values'] = '[i] '.$_POST['id'].', [i] '.$_POST['sNoteAccess_'.$sModule].', [s] '.$_POST['sNote_'.$sModule];
			$Work = $_LinkDB->prepare($__sInfo['command']);
			$Work->bind_param('iis', $_POST['id'], $_POST['sNoteAccess_'.$sModule], $_POST['sNote_'.$sModule]);
		} else {
			$__sInfo['values'] = '[i] '.$_POST['id'].', [s] '.$_POST['sNoteAccess_'.$sModule].', [s] '.$_POST['sNote_'.$sModule];
			$Work = $_LinkDB->prepare($__sInfo['command']);
			$Work->bind_param('iss', $_POST['id'], $_POST['sNoteAccess_'.$sModule], $_POST['sNote_'.$sModule]);
		}
		$Work->execute();

		echo "<s><msg>The note has been saved successfully!</msg><data date='".$_."' creator='".$__sUser['name']."'>".safeXML($_POST['sNote_'.$sModule])."</data></s>";
		exit();
	}
	break;




    # -- Specs Tab --


    case 'note':
	# Loads the specs information
	if ($_POST['A'] == 'load') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9')) { exit(); }
		if (! validate($_POST['sType'],10,'{asset|inventory}')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'ro','id,manager,departmentID',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('read')) { exit(); }


		# if we've made it here, the user is authorized to interact

		# 1. Obtain the item info
		if ($_POST['sType'] == 'asset') {
			$__sInfo['error'] = "Failed to find the 'Asset Info' in the database.";
			$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."AssetManagement WHERE id=? LIMIT 1";
		} else if ($_POST['sType'] == 'inventory') {
			$__sInfo['error'] = "Failed to find all 'Inventory Item' information in the database.";
			$__sInfo['command'] = "SELECT * FROM ".PREFIX."Inventory WHERE id=? LIMIT 1";
		}
		$__sInfo['values'] = '[i] '.$_POST['id'];
		$Module = $_LinkDB->prepare($__sInfo['command']);
		$Module->bind_param('i', $_POST['id']);
		$Module->execute();
		$module = $Module->get_result()->fetch_assoc();

		# 2. Obtain the vendor specs
		$__sInfo['error'] = "Failed to find the 'Vendor Specs' in the database.";
		$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."Application_Specs WHERE `table`='".$_POST['sType']."' AND type='vendor' AND rowID='".$module['id']."'";
		$__sInfo['values'] = 'None';
		$Vendor = $_LinkDB->query($__sInfo['command']);

		# 3. Obtain the internal specs
		$__sInfo['error'] = "Failed to find the 'Internal Specs' in the database.";
		$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."Application_Specs WHERE `table`='".$_POST['sType']."' AND type='internal' AND rowID='".$module['id']."'";
		$__sInfo['values'] = 'None';
		$Internal = $_LinkDB->query($__sInfo['command']);

		echo "<s>\n";
		echo "   <xml>\n";
		echo "	<specs>\n";
		echo "		<spec type='manufacturer' manufacturer=\"".safeXML($module['manufacturer'])."\" OPoID='".$module['OPoID']."' phone='".$module['phone']."' fax='".$module['fax']."' website='".$module['website']."' make='".$module['make']."' model='".$module['model']."' version='".$module['version']."' updating='".$module['updating']."' dimensions=\"".safeXML($module['dimensions'])."\" weight='".$module['weight']."' color=\"".$module['color']."\" power='".$module['power']."' runningTemp='".$module['runningTemp']."' runningHumidity='".$module['runningHumidity']."' storageTemp='".$module['storageTemp']."' storageHumidity='".$module['storageHumidity']."' certifications=\"".$module['certifications']."\" warranty='".$module['warranty']."' designed='".$module['designed']."' manufactured='".$module['manufactured']."' />\n";

		if ($Vendor !== false) while ($vs = $Vendor->fetch_assoc())
			{ echo "		<spec type='vendor' id='".$vs['id']."' title01=\"".safeXML($vs['title01'])."\" value01=\"".safeXML($vs['value01'])."\" title02=\"".safeXML($vs['title02'])."\" value02=\"".safeXML($vs['value02'])."\" title03=\"".safeXML($vs['title03'])."\" value03=\"".safeXML($vs['value03'])."\" title04=\"".safeXML($vs['title04'])."\" value04=\"".safeXML($vs['value04'])."\" title05=\"".safeXML($vs['title05'])."\" value05=\"".safeXML($vs['value05'])."\" title06=\"".safeXML($vs['title06'])."\" value06=\"".safeXML($vs['value06'])."\" title07=\"".safeXML($vs['title07'])."\" value07=\"".safeXML($vs['value07'])."\" title08=\"".safeXML($vs['title08'])."\" value08=\"".safeXML($vs['value08'])."\" title09=\"".safeXML($vs['title09'])."\" value09=\"".safeXML($vs['value09'])."\" title10=\"".safeXML($vs['title10'])."\" value10=\"".safeXML($vs['value10'])."\" title11=\"".safeXML($vs['title11'])."\" value11=\"".safeXML($vs['value11'])."\" title12=\"".safeXML($vs['title12'])."\" value12=\"".safeXML($vs['value12'])."\" title13=\"".safeXML($vs['title13'])."\" value13=\"".safeXML($vs['value13'])."\" title14=\"".safeXML($vs['title14'])."\" value14=\"".safeXML($vs['value14'])."\" title15=\"".safeXML($vs['title15'])."\" value15=\"".safeXML($vs['value15'])."\" title16=\"".safeXML($vs['title16'])."\" value16=\"".safeXML($vs['value16'])."\" title17=\"".safeXML($vs['title17'])."\" value17=\"".safeXML($vs['value17'])."\" title18=\"".safeXML($vs['title18'])."\" value18=\"".safeXML($vs['value18'])."\" title19=\"".safeXML($vs['title19'])."\" value19=\"".safeXML($vs['value19'])."\" title20=\"".safeXML($vs['title20'])."\" value20=\"".safeXML($vs['value20'])."\" />\n"; }

		if ($Internal !== false) while ($is = $Internal->fetch_assoc())
			{ echo "		<spec type='internal' id='".$is['id']."' title01=\"".safeXML($is['title01'])."\" value01=\"".safeXML($is['value01'])."\" title02=\"".safeXML($is['title02'])."\" value02=\"".safeXML($is['value02'])."\" title03=\"".safeXML($is['title03'])."\" value03=\"".safeXML($is['value03'])."\" title04=\"".safeXML($is['title04'])."\" value04=\"".safeXML($is['value04'])."\" title05=\"".safeXML($is['title05'])."\" value05=\"".safeXML($is['value05'])."\" title06=\"".safeXML($is['title06'])."\" value06=\"".safeXML($is['value06'])."\" title07=\"".safeXML($is['title07'])."\" value07=\"".safeXML($is['value07'])."\" title08=\"".safeXML($is['title08'])."\" value08=\"".safeXML($is['value08'])."\" title09=\"".safeXML($is['title09'])."\" value09=\"".safeXML($is['value09'])."\" title10=\"".safeXML($is['title10'])."\" value10=\"".safeXML($is['value10'])."\" title11=\"".safeXML($is['title11'])."\" value11=\"".safeXML($is['value11'])."\" title12=\"".safeXML($is['title12'])."\" value12=\"".safeXML($is['value12'])."\" title13=\"".safeXML($is['title13'])."\" value13=\"".safeXML($is['value13'])."\" title14=\"".safeXML($is['title14'])."\" value14=\"".safeXML($is['value14'])."\" title15=\"".safeXML($is['title15'])."\" value15=\"".safeXML($is['value15'])."\" title16=\"".safeXML($is['title16'])."\" value16=\"".safeXML($is['value16'])."\" title17=\"".safeXML($is['title17'])."\" value17=\"".safeXML($is['value17'])."\" title18=\"".safeXML($is['title18'])."\" value18=\"".safeXML($is['value18'])."\" title19=\"".safeXML($is['title19'])."\" value19=\"".safeXML($is['value19'])."\" title20=\"".safeXML($is['title20'])."\" value20=\"".safeXML($is['value20'])."\" />\n"; }

		echo "	</specs>\n";
		echo "   </xml>\n";
		echo "</s>";
		exit();




	# Saves the (new) specs information
	} else if ($_POST['A'] == 'save') {
		# Define some variables						  NOTE: these still have to pass validation for $_POST below
		$sModule = str_replace(' ', '', $_POST['sModule']);		# the module name in CamelCase

		# strip any non-numbers from the following values:
		$_POST['nPhone_'.$sModule] = preg_replace('/[^0-9]/','',$_POST['nPhone_'.$sModule]);
		$_POST['nFax_'.$sModule] = preg_replace('/[^0-9]/','',$_POST['nFax_'.$sModule]);

		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9')) { exit(); }
		if (! validate($_POST['sManufacturer_'.$sModule],20,'a-zA-Z0-9\._ \-')) { exit(); }
		if (! validate($_POST['sOPoID_'.$sModule],64,'a-zA-Z0-9\._\-')) { exit(); }
		if (! validate($_POST['nPhone_'.$sModule],15,'0-9')) { exit(); }
		if (! validate($_POST['nFax_'.$sModule],15,'0-9')) { exit(); }
		if (! validate($_POST['sWebsite_'.$sModule],128,'a-zA-Z0-9_\.:\/\-')) { exit(); }
		if (! validate($_POST['sMake_'.$sModule],32,'a-zA-Z0-9\._\-')) { exit(); }
		if (! validate($_POST['sModel_'.$sModule],32,'a-zA-Z0-9\._\-')) { exit(); }
		if (! validate($_POST['sVersion_'.$sModule],8,'a-zA-Z0-9\.\-')) { exit(); }
		if (! validate($_POST['sSpecUpdate_'.$sModule],4,'{man|auto}')) { exit(); }
		if (! validate($_POST['sDimensions_'.$sModule],24,'a-zA-Z0-9\.')) { exit(); }
		if (! validate($_POST['sWeight_'.$sModule],24,'a-zA-Z0-9\.')) { exit(); }
		if (! validate($_POST['sColor_'.$sModule],48,'a-zA-Z, ')) { exit(); }
		if (! validate($_POST['sPower_'.$sModule],48,'avwAVW0-9')) { exit(); }
		if (! validate($_POST['sRunningTemp_'.$sModule],48,'cfCF0-9\.')) { exit(); }
		if (! validate($_POST['sRunningHumidity_'.$sModule],48,'cfCF0-9\.')) { exit(); }
		if (! validate($_POST['sStorageTemp_'.$sModule],48,'cfCF0-9\.')) { exit(); }
		if (! validate($_POST['sStorageHumidity_'.$sModule],48,'cfCF0-9\.')) { exit(); }
		if (! validate($_POST['sCertifications_'.$sModule],64,'a-zA-Z0-9\., \-')) { exit(); }
		if (! validate($_POST['sWarranty_'.$sModule],64,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sDesigned_'.$sModule],2,'a-z')) { exit(); }
		if (! validate($_POST['sManufactured_'.$sModule],2,'a-z')) { exit(); }
		for ($i=21; $i<41; $i++) {
			if (! validate($_POST['sSpecTitle'.$i.'_'.$sModule],24,'a-zA-Z0-9 _\.@\-')) { exit(); }
			if (! validate($_POST['sSpecValue'.$i.'_'.$sModule],64,'!=<>;')) { exit(); }
		}

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id,name',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('write')) { exit(); }


		# if we've made it here, the user is authorized to interact

		$__sInfo['error'] = "Failed to update the existing 'Inventory Information' in the database.";
		$__sInfo['command'] = "UPDATE ".DB_PRFX."Inventory SET manufacturer=?,OPoID=?,phone=?,fax=?,website=?,make=?,model=?,version=?,updating=?,dimensions=?,weight=?,color=?,power=?,runningTemp=?,runningHumidity=?,storageTemp=?,storageHumidity=?,certifications=?,warranty=?,designed=?,manufactured=?,updatedBy='".$gbl_user['id']."',updatedOn='".$_."' WHERE id=?";
		$__sInfo['values'] = '[s] '.$_POST['sManufacturer_'.$sModule].', [s] '.$_POST['sOPoID_'.$sModule].', [i] '.$_POST['nPhone_'.$sModule].', [i] '.$_POST['nFax_'.$sModule].', [s] '.$_POST['sWebsite_'.$sModule].', [s] '.$_POST['sMake_'.$sModule].', [s] '.$_POST['sModel_'.$sModule].', [s] '.$_POST['sVersion_'.$sModule].', [s] '.$_POST['sSpecUpdate_'.$sModule].', [s] '.$_POST['sDimensions_'.$sModule].', [s] '.$_POST['sWeight_'.$sModule].', [s] '.$_POST['sColor_'.$sModule].', [s] '.$_POST['sPower_'.$sModule].', [s] '.$_POST['sRunningTemp_'.$sModule].', [s] '.$_POST['sRunningHumidity_'.$sModule].', [s] '.$_POST['sStorageTemp_'.$sModule].', [s] '.$_POST['sStorageHumidity_'.$sModule].', [s] '.$_POST['sCertifications_'.$sModule].', [s] '.$_POST['sWarranty_'.$sModule].', [s] '.$_POST['sDesigned_'.$sModule].', [s] '.$_POST['sManufactured_'.$sModule].', [i] '.$_POST['id'];
		$stmt = $linkDB->prepare($__sInfo['command']);
		$stmt->bind_param('ssiisssssssssssssssssi', $_POST['sManufacturer_'.$sModule], $_POST['sOPoID_'.$sModule], $_POST['nPhone_'.$sModule], $_POST['nFax_'.$sModule], $_POST['sWebsite_'.$sModule], $_POST['sMake_'.$sModule], $_POST['sModel_'.$sModule], $_POST['sVersion_'.$sModule], $_POST['sSpecUpdate_'.$sModule], $_POST['sDimensions_'.$sModule], $_POST['sWeight_'.$sModule], $_POST['sColor_'.$sModule], $_POST['sPower_'.$sModule], $_POST['sRunningTemp_'.$sModule], $_POST['sRunningHumidity_'.$sModule], $_POST['sStorageTemp_'.$sModule], $_POST['sStorageHumidity_'.$sModule], $_POST['sCertifications_'.$sModule], $_POST['sWarranty_'.$sModule], $_POST['sDesigned_'.$sModule], $_POST['sManufactured_'.$sModule], $_POST['id']);
		$stmt->execute();

		if ($_POST['sSpecTitle21_'.$sModule] != '') {		# if the user has entered some custom internal specs, then...
			$__sInfo['error'] = "Failed to find all 'Included Inventory ID Codes' in the database.";
			$__sInfo['command'] = "SELECT id FROM ".DB_PRFX."Application_Specs WHERE `table`='inventory' AND type='internal' AND rowID='".$_POST['id']."' LIMIT 1";
			$__sInfo['values'] = 'None';
			$Spec = $linkDB->query($__sInfo['command']);
			if ($Spec->num_rows === 0) {			# if no prior record exists, then...
				$__sInfo['error'] = "Failed to add a new 'Internal Specs' for the inventory item to the database.";
				$__sInfo['command'] = "INSERT INTO ".DB_PRFX."Application_Specs (`table`,type,rowID,title01,value01,title02,value02,title03,value03,title04,value04,title05,value05,title06,value06,title07,value07,title08,value08,title09,value09,title10,value10,title11,value11,title12,value12,title13,value13,title14,value14,title15,value15,title16,value16,title17,value17,title18,value18,title19,value19,title20,value20,created,updated) VALUES ('inventory','internal',?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'".$_."','".$_."')";
				$__sInfo['values'] = '[i] '.$_POST['id'].', [s] '.$_POST['sSpecTitle21_'.$sModule].', [s] '.$_POST['sSpecValue21_'.$sModule].', [s] '.$_POST['sSpecTitle22_'.$sModule].', [s] '.$_POST['sSpecValue22_'.$sModule].', [s] '.$_POST['sSpecTitle23_'.$sModule].', [s] '.$_POST['sSpecValue23_'.$sModule].', [s] '.$_POST['sSpecTitle24_'.$sModule].', [s] '.$_POST['sSpecValue24_'.$sModule].', [s] '.$_POST['sSpecTitle25_'.$sModule].', [s] '.$_POST['sSpecValue25_'.$sModule].', [s] '.$_POST['sSpecTitle26_'.$sModule].', [s] '.$_POST['sSpecValue26_'.$sModule].', [s] '.$_POST['sSpecTitle27_'.$sModule].', [s] '.$_POST['sSpecValue27_'.$sModule].', [s] '.$_POST['sSpecTitle28_'.$sModule].', [s] '.$_POST['sSpecValue28_'.$sModule].', [s] '.$_POST['sSpecTitle29_'.$sModule].', [s] '.$_POST['sSpecValue29_'.$sModule].', [s] '.$_POST['sSpecTitle30_'.$sModule].', [s] '.$_POST['sSpecValue30_'.$sModule].', [s] '.$_POST['sSpecTitle31_'.$sModule].', [s] '.$_POST['sSpecValue31_'.$sModule].', [s] '.$_POST['sSpecTitle32_'.$sModule].', [s] '.$_POST['sSpecValue32_'.$sModule].', [s] '.$_POST['sSpecTitle33_'.$sModule].', [s] '.$_POST['sSpecValue33_'.$sModule].', [s] '.$_POST['sSpecTitle34_'.$sModule].', [s] '.$_POST['sSpecValue34_'.$sModule].', [s] '.$_POST['sSpecTitle35_'.$sModule].', [s] '.$_POST['sSpecValue35_'.$sModule].', [s] '.$_POST['sSpecTitle36_'.$sModule].', [s] '.$_POST['sSpecValue36_'.$sModule].', [s] '.$_POST['sSpecTitle37_'.$sModule].', [s] '.$_POST['sSpecValue37_'.$sModule].', [s] '.$_POST['sSpecTitle38_'.$sModule].', [s] '.$_POST['sSpecValue38_'.$sModule].', [s] '.$_POST['sSpecTitle39_'.$sModule].', [s] '.$_POST['sSpecValue39_'.$sModule].', [s] '.$_POST['sSpecTitle40_'.$sModule].', [s] '.$_POST['sSpecValue40_'.$sModule];
				$stmt = $linkDB->prepare($__sInfo['command']);
				$stmt->bind_param('issssssssssssssssssssssssssssssssssssssss', $_POST['id'], $_POST['sSpecTitle21_'.$sModule], $_POST['sSpecValue21_'.$sModule], $_POST['sSpecTitle22_'.$sModule], $_POST['sSpecValue22_'.$sModule], $_POST['sSpecTitle23_'.$sModule], $_POST['sSpecValue23_'.$sModule], $_POST['sSpecTitle24_'.$sModule], $_POST['sSpecValue24_'.$sModule], $_POST['sSpecTitle25_'.$sModule], $_POST['sSpecValue25_'.$sModule], $_POST['sSpecTitle26_'.$sModule], $_POST['sSpecValue26_'.$sModule], $_POST['sSpecTitle27_'.$sModule], $_POST['sSpecValue27_'.$sModule], $_POST['sSpecTitle28_'.$sModule], $_POST['sSpecValue28_'.$sModule], $_POST['sSpecTitle29_'.$sModule], $_POST['sSpecValue29_'.$sModule], $_POST['sSpecTitle30_'.$sModule], $_POST['sSpecValue30_'.$sModule], $_POST['sSpecTitle31_'.$sModule], $_POST['sSpecValue31_'.$sModule], $_POST['sSpecTitle32_'.$sModule], $_POST['sSpecValue32_'.$sModule], $_POST['sSpecTitle33_'.$sModule], $_POST['sSpecValue33_'.$sModule], $_POST['sSpecTitle34_'.$sModule], $_POST['sSpecValue34_'.$sModule], $_POST['sSpecTitle35_'.$sModule], $_POST['sSpecValue35_'.$sModule], $_POST['sSpecTitle36_'.$sModule], $_POST['sSpecValue36_'.$sModule], $_POST['sSpecTitle37_'.$sModule], $_POST['sSpecValue37_'.$sModule], $_POST['sSpecTitle38_'.$sModule], $_POST['sSpecValue38_'.$sModule], $_POST['sSpecTitle39_'.$sModule], $_POST['sSpecValue39_'.$sModule], $_POST['sSpecTitle40_'.$sModule], $_POST['sSpecValue40_'.$sModule]);
				$stmt->execute();
			} else {					# otherwise update the record
				$spec = $Spec->fetch_assoc();

				$__sInfo['error'] = "Failed to update the existing 'Internal Specs' for the inventory item in the database.";
				$__sInfo['command'] = "UPDATE ".DB_PRFX."Application_Specs SET title01=?,value01=?,title02=?,value02=?,title03=?,value03=?,title04=?,value04=?,title05=?,value05=?,title06=?,value06=?,title07=?,value07=?,title08=?,value08=?,title09=?,value09=?,title10=?,value10=?,title11=?,value11=?,title12=?,value12=?,title13=?,value13=?,title14=?,value14=?,title15=?,value15=?,title16=?,value16=?,title17=?,value17=?,title18=?,value18=?,title19=?,value19=?,title20=?,value20=?,updated='".$_."' WHERE id='".$spec['id']."'";
				$__sInfo['values'] = '[s] '.$_POST['sSpecTitle21_'.$sModule].', [s] '.$_POST['sSpecValue21_'.$sModule].', [s] '.$_POST['sSpecTitle22_'.$sModule].', [s] '.$_POST['sSpecValue22_'.$sModule].', [s] '.$_POST['sSpecTitle23_'.$sModule].', [s] '.$_POST['sSpecValue23_'.$sModule].', [s] '.$_POST['sSpecTitle24_'.$sModule].', [s] '.$_POST['sSpecValue24_'.$sModule].', [s] '.$_POST['sSpecTitle25_'.$sModule].', [s] '.$_POST['sSpecValue25_'.$sModule].', [s] '.$_POST['sSpecTitle26_'.$sModule].', [s] '.$_POST['sSpecValue26_'.$sModule].', [s] '.$_POST['sSpecTitle27_'.$sModule].', [s] '.$_POST['sSpecValue27_'.$sModule].', [s] '.$_POST['sSpecTitle28_'.$sModule].', [s] '.$_POST['sSpecValue28_'.$sModule].', [s] '.$_POST['sSpecTitle29_'.$sModule].', [s] '.$_POST['sSpecValue29_'.$sModule].', [s] '.$_POST['sSpecTitle30_'.$sModule].', [s] '.$_POST['sSpecValue30_'.$sModule].', [s] '.$_POST['sSpecTitle31_'.$sModule].', [s] '.$_POST['sSpecValue31_'.$sModule].', [s] '.$_POST['sSpecTitle32_'.$sModule].', [s] '.$_POST['sSpecValue32_'.$sModule].', [s] '.$_POST['sSpecTitle33_'.$sModule].', [s] '.$_POST['sSpecValue33_'.$sModule].', [s] '.$_POST['sSpecTitle34_'.$sModule].', [s] '.$_POST['sSpecValue34_'.$sModule].', [s] '.$_POST['sSpecTitle35_'.$sModule].', [s] '.$_POST['sSpecValue35_'.$sModule].', [s] '.$_POST['sSpecTitle36_'.$sModule].', [s] '.$_POST['sSpecValue36_'.$sModule].', [s] '.$_POST['sSpecTitle37_'.$sModule].', [s] '.$_POST['sSpecValue37_'.$sModule].', [s] '.$_POST['sSpecTitle38_'.$sModule].', [s] '.$_POST['sSpecValue38_'.$sModule].', [s] '.$_POST['sSpecTitle39_'.$sModule].', [s] '.$_POST['sSpecValue39_'.$sModule].', [s] '.$_POST['sSpecTitle40_'.$sModule].', [s] '.$_POST['sSpecValue40_'.$sModule];
				$stmt = $linkDB->prepare($__sInfo['command']);
				$stmt->bind_param('ssssssssssssssssssssssssssssssssssssssss', $_POST['sSpecTitle21_'.$sModule], $_POST['sSpecValue21_'.$sModule], $_POST['sSpecTitle22_'.$sModule], $_POST['sSpecValue22_'.$sModule], $_POST['sSpecTitle23_'.$sModule], $_POST['sSpecValue23_'.$sModule], $_POST['sSpecTitle24_'.$sModule], $_POST['sSpecValue24_'.$sModule], $_POST['sSpecTitle25_'.$sModule], $_POST['sSpecValue25_'.$sModule], $_POST['sSpecTitle26_'.$sModule], $_POST['sSpecValue26_'.$sModule], $_POST['sSpecTitle27_'.$sModule], $_POST['sSpecValue27_'.$sModule], $_POST['sSpecTitle28_'.$sModule], $_POST['sSpecValue28_'.$sModule], $_POST['sSpecTitle29_'.$sModule], $_POST['sSpecValue29_'.$sModule], $_POST['sSpecTitle30_'.$sModule], $_POST['sSpecValue30_'.$sModule], $_POST['sSpecTitle31_'.$sModule], $_POST['sSpecValue31_'.$sModule], $_POST['sSpecTitle32_'.$sModule], $_POST['sSpecValue32_'.$sModule], $_POST['sSpecTitle33_'.$sModule], $_POST['sSpecValue33_'.$sModule], $_POST['sSpecTitle34_'.$sModule], $_POST['sSpecValue34_'.$sModule], $_POST['sSpecTitle35_'.$sModule], $_POST['sSpecValue35_'.$sModule], $_POST['sSpecTitle36_'.$sModule], $_POST['sSpecValue36_'.$sModule], $_POST['sSpecTitle37_'.$sModule], $_POST['sSpecValue37_'.$sModule], $_POST['sSpecTitle38_'.$sModule], $_POST['sSpecValue38_'.$sModule], $_POST['sSpecTitle39_'.$sModule], $_POST['sSpecValue39_'.$sModule], $_POST['sSpecTitle40_'.$sModule], $_POST['sSpecValue40_'.$sModule]);
				$stmt->execute();
			}
		}
		echo "<s><msg>The item specs have been saved successfully!</msg></s>";
	}
	break;




    # -- Data Tab --


    case 'data':
	# Loads the data fields information
	if ($_POST['A'] == 'load') {
		# Define some variables						   NOTE: these still have to pass validation for $_POST below
		$sType = $_POST['sType'];
		$sModule = str_replace(' ', '', $_POST['sModule']);		# the module name in CamelCase

		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9+')) { exit(); }
		if (! validate($_POST['sModule'],24,'a-zA-Z0-9_ ')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('read')) { exit(); }


		# if we've made it here, the user is authorized to interact

# VER2 - replace 'table' with 'fkModule', 'rowID' with 'fkRow'
#	 rename 'Uploads' > 'Data'
		# 8a. Store all the uploaded file data related to the customer account
		$__sInfo['error'] = "Failed to find the requested uploaded data in the database.";
		$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."Application_Data WHERE `table`='".$sModule."' AND rowID=?";
		$__sInfo['values'] = '[i] '.$_POST['id'];
		$Data = $_LinkDB->prepare($__sInfo['command']);
		$Data->bind_param('i', $_POST['id']);
		$Data->execute();


		echo "<s>\n";
		echo "   <xml>\n";
		echo "	<data>\n";

		while ($data = $Data->fetch_assoc())
			{ echo "	   <entry id='".$data['id']."' title=\"".safeXML($data['name'])."\" filename=\"".safeXML($data['filename'])."\" />\n"; }

		if (file_exists('../data/_modules/'.$sModule.'/'.$_POST['id'])) {
			$__sInfo['error'] = "The \"../data/_modules/".$sModule."/".$_POST['id']."\" directory can not be created for the employee.";
			$__sInfo['command'] = "opendir(\"../data/_modules/".$sModule."/".$_POST['id']."\")";
			$__sInfo['values'] = 'None';
			if ($dir = opendir('../data/_modules/'.$sModule.'/'.$_POST['id'])) {
				while (false !== ($file = readdir($dir))) {
					if (is_dir($file) || $file == '.' || $file == '..') { continue; }	# skip any directories
					echo "	   <file filename=\"".safeXML($file)."\" />\n";
				}
				closedir($dir);
			}
		}

		echo "	</data>\n";
		echo "   </xml>\n";
		echo "</s>";
		exit();




	# Saves the (new) date field information
	} else if ($_POST['A'] == 'save') {
		# Define some variables						   NOTE: these still have to pass validation for $_POST below
		$sModule = str_replace(' ', '', $_POST['sModule']);		# the module name in CamelCase

		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9+')) { exit(); }
		if (! validate($_POST['sModule'],24,'a-zA-Z0-9_ ')) { exit(); }
		if (! validate($_POST['sTitle'],48,'a-zA-Z0-9 _\.@\-')) { exit(); }
		if (! validate($_POST['sFilename'],32,'!=<>')) { exit(); }
		if (! validate($_POST['nModule'],20,'0-9')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if ($_POST['id'] == '+')
			{ if (! checkPermission('add')) {exit();} }
		else
			{ if (! checkPermission('write')) {exit();} }


		# if we've made it here, the user is authorized to interact

		# If we're adding a new record, then...
		if ($_POST['id'] == '+') {
			$__sInfo['error'] = "Failed to create the uploaded file info in the database.";
			$__sInfo['command'] = "INSERT INTO ".DB_PRFX."Application_Data (`table`,rowID,name,filename,createdBy,createdOn,updatedBy,updatedOn) VALUES (?,?,?,?,'".$__sUser['id']."','".$_."','".$__sUser['id']."','".$_."')";
			$__sInfo['values'] = '[s] '.$sModule.', [i] '.$_POST['nModule'].', [s] '.$_POST['sTitle'].', [s] '.$_POST['sFilename'];
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			$stmt->bind_param('siss', $sModule, $_POST['nModule'], $_POST['sTitle'], $_POST['sFilename']);
			$stmt->execute();

			echo "<s><msg>The data field has been created successfully!</msg><data id='".$stmt->insert_id."' /></s>";

		# Otherwise we're updating record information, so...
		} else {
			$__sInfo['error'] = "Failed to update the existing uploaded file info in the database.";
			$__sInfo['command'] = "UPDATE ".DB_PRFX."Application_Data SET rowID=?,name=?,filename=?,updatedBy='".$__sUser['id']."',updatedOn='".$_."' WHERE id=?";
			$__sInfo['values'] = '[i] '.$_POST['nModule'].', [s] '.$_POST['sTitle'].', [s] '.$_POST['sFilename'].', [i] '.$_POST['id'];
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			$stmt->bind_param('issi', $_POST['nModule'], $_POST['sTitle'], $_POST['sFilename'], $_POST['id']);
			$stmt->execute();

			echo "<s><msg>The data field has been updated successfully!</msg></s>";
		}




	# Delete/Disable the data field
	} else if ($_POST['A'] == 'delete' || $_POST['A'] == 'disable') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('del')) { exit(); }


		# if we've made it here, the user is authorized to interact

		# 1. "Delete" the requested record
		if ($_POST['A'] == 'delete') {
			$__sInfo['error'] = "Failed to delete the requested bank account from the database.";
			$__sInfo['command'] = "DELETE FROM ".DB_PRFX."Application_Data WHERE id=? LIMIT 1";
		} else if ($_POST['A'] == 'disable') {
			$__sInfo['error'] = "Failed to disable the requested bank account from the database.";
			$__sInfo['command'] = "UPDATE ".DB_PRFX."Application_Data SET bDisabled=1 WHERE id=?";
		}
		$__sInfo['values'] = '[i] '.$_POST['id'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('i', $_POST['id']);
		$stmt->execute();

		echo "<s><msg>The data field has been deleted successfully!</msg></s>";
		exit();
	}
	break;
}




# -- Module API --

#switch ($_POST['A']) {						# Process the submitted (A)ction
#
#    case 'load':						# ?
#	if ($_POST['T'] == 'module') {				# Process the submitted (T)arget
#	break;
#}




# -- Widgets API --

switch($_POST['A']) {						# Process the submitted (A)ction
    case 'load':						# Load Items
	if ($_POST['T'] == 'exts' || $_POST['T'] == 'themes' || $_POST['T'] == 'icons') {	# Process the submitted (T)arget [Load List of Widgets]
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }

# MOVED 2025/03/08 - moved at the top
		# define general info for any error generated below
#		$__sInfo['name'] = 'Unknown';
#		$__sInfo['contact'] = 'Unknown';
#		$__sInfo['other'] = 'n/a';

		# start the external communication with the URI working before adding a successful pairing to the DB
		# WARNING: make sure the headers are turned OFF below or the SimpleXmlElement will have issues!
		$ch = curl_init();							# http://codular.com/curl-with-php
		$options = array(
			CURLOPT_URL            => "https://www.cliquesoft.org/modules/default/shoppe.php?action=load&target=".$_POST['T']."&username=&SID=&project=webbooks",
			CURLOPT_REFERER        => $_SERVER['SERVER_ADDR'],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_ENCODING       => "",
			CURLOPT_AUTOREFERER    => true,
			CURLOPT_POST           => false,
			#CURLOPT_POSTFIELDS     => array(
			#	item1 => 'value',
			#	item2 => 'value2'
			#),
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_MAXREDIRS      => 10
		);
		curl_setopt_array($ch, $options);
		$response = curl_exec($ch);
		$response = stripslashes($response);					# http://stackoverflow.com/questions/2852601/simplexmlelement-php-throwing-error-on-xml-root-attributes
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		# check that the communication with the target URI was successful
		if ($httpcode != 200) {
			echo "<f><msg>There was an error contacting the Cliquesoft Software Shoppe, please try again in a few minutes.</msg></f>";
			exit();
		}
		if (strpos($response, '<f><msg>' !== false)) {
			echo "<f><msg>There was an error contacting the Cliquesoft Software Shoppe.</msg></f>";
			exit();
		}
		$xml = new SimpleXmlElement($response);
		if ($xml->f) {								# if there was a failure on the targets end, then...
			echo "<f><msg>There was an error contacting the Cliquesoft Software Shoppe: ".$xml->f->msg."</msg></f>";
			sendMail($_sSupportEmail,$_sSupportName,$_sAlertsEmail,$_sAlertsName,'*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".$SCRIPT."<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: An error occurred while attempting to obtain a widget listing.<br />\nExec Error: ".$XML->f->msg."<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
			exit();
		}

		# at this point, we should get some type of XML response (since the communication was successful)
		if (! connect2DB(DB_HOST,DB_NAME,DB_ROUN,DB_ROPW)) { exit(); }		# NOTE: the connect2DB has its own error handling so we don't need to do it here!	WARNING: this MUST come above the '<s>' tags below!

		$XML =	"<s>\n" .
			"   <xml>\n";

		# 1. Store all the modules currently installed in webBooks
		$__sInfo['error'] = "Failed to find the Employee's last timesheet record in the database.";
		$__sInfo['command'] = "SELECT name FROM ".PREFIX."ApplicationSettings_Modules";					# NOTE: this is only applicable for extensions, not themes and icon sets
		$__sInfo['values'] = 'None';
		$Modules = $_LinkDB->query($__sInfo['command']);

		foreach ($xml->xml->children() as $addon) {				# now traverse all returned addons		NOTE: we have to use 'children()' instead of 'ext' since the different types will have different XML tags (e.g. <ext>, <theme>, <icon>)
			$installed=" installed='0'";					# indicate the default installed value for each iterated addon
			if ($_POST['T'] == 'exts') {				# if we're dealing with extensions, then...
				while ($module = $Modules->fetch_assoc())		#   traverse each installed module to mark it in the returned results
					{ if ($module['name'] == $addon) {$installed=" installed='1'"; break;} }
			} else if ($_POST['T'] == 'icons') {			# if we're dealing with icon sets, then...
				$__sInfo['error'] = "The 'imgs' directory can not be opened to find installed icon sets.";
				$__sInfo['command'] = "opendir('../imgs')";
				$__sInfo['values'] = '';
				$dir = opendir('../imgs');
				while ( ($file = readdir($dir)) !== false )
					{ if ($file == $addon) {$installed=" installed='1'"; break;} }
				closedir($dir);
			} else if ($_POST['T'] == 'themes') {			# if we're dealing with themes, then...
# LEFT OFF - test this section
				$__sInfo['error'] = "The 'look' directory can not be opened to find installed themes.";
				$__sInfo['command'] = "opendir('../look')";
				$__sInfo['values'] = '';
				$dir = opendir('../look');
				while ( ($file = readdir($dir)) !== false )
					{ if ($file == $addon) {$installed=" installed='1'"; break;} }
				closedir($dir);
			}

#file_put_contents('debug.txt', "<".substr($_POST['T'],0,-1)." downloads='".$addon['downloads']."'", FILE_APPEND);
#file_put_contents('debug.txt', $installed." score='".$addon['score']."' logo='".$addon['logo']."'>".$addon."</".substr($_POST['T'],0,-1).">\n", FILE_APPEND);
			$XML .= "<".substr($_POST['T'],0,-1)." downloads='".$addon['downloads']."'".$installed." score='".$addon['score']."' logo='".$addon['logo']."'>".$addon."</".substr($_POST['T'],0,-1).">\n";
			$Modules->data_seek(0);						# reset the SQL record pointer back to the first record in the results list to re-process in this 'foreach' loop
		}

		$XML .=	"   </xml>\n" .
			"</s>";
		echo $XML;
		exit();




	} else if ($_POST['T'] == 'details') {				# Process the submitted (T)arget [Show Widget Details]
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		# -----
		if (! validate($_POST['sType'],6,'{exts|themes|icons}')) { exit(); }
		if (! validate($_POST['sWidget'],64,'a-zA-Z0-9\-\. ')) { exit(); }

# MOVED 2025/03/08 - moved at the top
		# define general info for any error generated below
#		$__sInfo['name'] = 'Unknown';
#		$__sInfo['contact'] = 'Unknown';
#		$__sInfo['other'] = 'n/a';

		# encode spaces in the widget name so cURL works correctly below
		$_POST['sWidget'] = str_replace(' ', '%20', $_POST['sWidget']);

		# start the external communication with the URI working before adding a successful pairing to the DB
		# WARNING: make sure the headers are turned OFF below or the SimpleXmlElement will have issues!
		$ch = curl_init();							# http://codular.com/curl-with-php
		$options = array(
			CURLOPT_URL            => "https://www.cliquesoft.org/modules/default/shoppe.php?action=load&target=details&username=&SID=&type=".$_POST['sType']."&project=".$_POST['sWidget']."&close=1",
			CURLOPT_REFERER        => $_SERVER['SERVER_ADDR'],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_ENCODING       => "",
			CURLOPT_AUTOREFERER    => true,
			CURLOPT_POST           => false,
			#CURLOPT_POSTFIELDS     => array(
			#	item1 => 'value',
			#	item2 => 'value2'
			#),
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_MAXREDIRS      => 10
		);
		curl_setopt_array($ch, $options);
		$response = curl_exec($ch);
		$response = stripslashes($response);					# http://stackoverflow.com/questions/2852601/simplexmlelement-php-throwing-error-on-xml-root-attributes
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
#file_put_contents('debug.txt', $response."\n", FILE_APPEND);

		# check that the communication with the target URI was successful
		if ($httpcode != 200) {
			echo "<f><msg>There was an error contacting the Cliquesoft Software Shoppe, please try again in a few minutes.</msg></f>";
			return 0;
		}
		$XML = new SimpleXmlElement($response);
		if ($XML->f) {								# if there was a failure on the targets end, then...
			echo "<f><msg>An error occurred while pairing with target software: ".$XML->f->msg."</msg></f>";
			sendMail($_sSupportEmail,$_sSupportName,$_sAlertsEmail,$_sAlertsName,'*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".$SCRIPT."<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: An unknown error occurred while attempting to pair the software.<br />\nExec Error: ".$XML->f->msg."<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
			return 0;
		}

		# at this point, we should get some type of XML response (since the communication was successful)
		$response=substr($response, strpos($response, "\n\n") + 1);		# remove the <xml ... /> header since it is already presented at the top of this script		http://stackoverflow.com/questions/7740405/php-delete-the-first-line-of-a-text-and-return-the-rest		http://stackoverflow.com/questions/758488/delete-first-four-lines-from-the-top-in-content-stored-in-a-variable
		echo $response;
		exit();
	}
	break;




    case 'adjust':						# (Un)installs the Widget
	if ($_POST['T'] == 'widget') {				# Process the submitted (T)arget
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		# -----
		if (! validate($_POST['sType'],6,'{exts|themes|icons}')) { exit(); }
		if (! validate($_POST['sWidget'],64,'a-zA-Z0-9\-\. ')) { exit(); }
		if (! validate($_POST['sStatus'],9,'{uninstall|install}')) { exit(); }

		# connect to the DB for reading below
		if (! connect2DB(DB_HOST,DB_NAME,DB_ROUN,DB_ROPW)) { exit(); }		# NOTE: the connect2DB has its own error handling so we don't need to do it here!	WARNING: this MUST come above the '<s>' tags below!

# MOVED 2025/03/08 - moved at the top
		# define general info for any error generated below
#		$__sInfo['name'] = 'Unknown';
#		$__sInfo['contact'] = 'Unknown';
#		$__sInfo['other'] = 'n/a';

		if ($_POST['sStatus'] == 'install') {					# perform the install
			if (! file_exists('../temp/install')) {				# create the 'install' directory if it doesn't exist
				$__sInfo['error'] = "The 'temp/install' directory can not be created for widget installation.";
				$__sInfo['command'] = "mkdir('../temp/install', 0775, true)";
				$__sInfo['values'] = '';
				mkdir('../temp/install', 0775, true);
			}

			if (file_exists($_USER['dir'].'/official/vanilla/all/webBooks')) {		# if this script is being called on the same server (e.g. webfice.com) as the distribution website (cliquesoft.org), then copy locally!
				$__sInfo['error'] = "The 'temp/install' directory can not be created for widget installation.";
				$__sInfo['command'] = "copy('/home/digitalpipe/official/vanilla/all/webBooks/_".$_POST['sType']."/".$_POST['sWidget']."/beta.soft', '../temp/install/".$_POST['sWidget'].".tgz')";
				$__sInfo['values'] = '';
				copy('/home/digitalpipe/official/vanilla/all/webBooks/_'.$_POST['sType'].'/'.$_POST['sWidget'].'/beta.soft', '../temp/install/'.$_POST['sWidget'].'.tgz');
				file_put_contents('../temp/install/'.$_POST['sWidget'].'.md5', file_get_contents($_USER['dir'].'/official/vanilla/all/webBooks/_'.$_POST['sType'].'/'.$_POST['sWidget'].'/beta.md5'));

			} else {									# otherwise, we need to grab from our distribution website (cliquesoft.org)
				file_put_contents('../temp/install/'.$_POST['sWidget'].'.tgz', file_get_contents('http://repo.cliquesoft.org/vanilla/all/webBooks/_'.$_POST['sType'].'/'.$_POST['sWidget'].'/beta.soft'));
				file_put_contents('../temp/install/'.$_POST['sWidget'].'.md5', file_get_contents('http://repo.cliquesoft.org/vanilla/all/webBooks/_'.$_POST['sType'].'/'.$_POST['sWidget'].'/beta.md5'));
			}

			// remove any prior attempts at installation
			if (is_dir('../temp/install/'.$_POST['sWidget']))
				{ delTree('../temp/install/'.$_POST['sWidget']); }

			# decompress the tgz file			http://stackoverflow.com/questions/9416508/php-untar-gz-without-exec
			exec("tar zxf ../temp/install/".$_POST['sWidget'].".tgz -C ../temp/install", $__sNull);		// uncompress the .soft (.tar.gz) file

			# run the setup or simply install the files (depending on the widget type)
			if ($_POST['sType'] == 'exts') {
				require('../temp/install/software/code/'.strtolower($_POST['sWidget']).'_setup.php');
				call_user_func(strtolower($_POST['sWidget']).'_install', $_POST['sWidget']);		// http://stackoverflow.com/questions/1005857/how-to-call-php-function-from-string-stored-in-a-variable
			} else if ($_POST['sType'] == 'icons') {
				$__sInfo['error'] = "The 'temp/install/software/imgs/".$_POST['sWidget']."' directory can not be opened for icon set installation.";
				$__sInfo['command'] = "opendir(\"../temp/install/software/imgs/\"".$_POST['sWidget']."\")";
				$__sInfo['values'] = '';
				$dir = opendir('../temp/install/software/imgs/'.$_POST['sWidget']);

				$dst = '../imgs/'.$_POST['sWidget'];
				if (! file_exists($dst)) {
					$__sInfo['error'] = "The \"".$dst."\" directory can not be created for icon set installation.";
					$__sInfo['command'] = "mkdir(\"".$dst."\", 0775, true)";
					$__sInfo['values'] = '';
					mkdir($dst, 0775, true);
				}
				while ( ($file = readdir($dir)) !== false ) {
					if ($file == '.' || $file == '..' || is_dir($file)) { continue; }

					$__sInfo['error'] = "The \"".$dst."\" directory can not be created for icon set installation.";
					$__sInfo['command'] = "copy(\"../temp/install/software/imgs/".$_POST['sWidget']."/".$file."\", \"".$dst."/".$file."\")";
					$__sInfo['values'] = '';
					copy('../temp/install/software/imgs/'.$_POST['sWidget'].'/'.$file, $dst.'/'.$file);
				}
				closedir($dir);

			} else if ($_POST['sType'] == 'themes') {
# LEFT OFF - test this
				$__sInfo['error'] = "The 'temp/install/software/look/".$_POST['sWidget']."' directory can not be opened for theme installation.";
				$__sInfo['command'] = "opendir(\"../temp/install/software/look/".$_POST['sWidget']."\")";
				$__sInfo['values'] = '';
				$dir = opendir('../temp/install/software/look/'.$_POST['sWidget']);

				$dst = '../look'.$_POST['sWidget'];
				if (! file_exists($dst)) {
					$__sInfo['error'] = "The \"".$dst."\" directory can not be created for theme installation.";
					$__sInfo['command'] = "mkdir(\"".$dst."\", 0775, true)";
					$__sInfo['values'] = '';
					mkdir($dst, 0775, true);
				}
				while ( ($file = readdir($dir)) !== false ) {
					if ($file == '.' || $file == '..') { continue; }

					if (is_dir($file)) {
						$__sInfo['error'] = "The \"".$dst."/".$file."\" directory can not be created during theme installation.";
						$__sInfo['command'] = "mkdir(\"".$dst."/".$file."\", 0775, true)";
						$__sInfo['values'] = '';
						mkdir($dst.'/'.$file, 0775, true);
						continue;
					}
					$__sInfo['error'] = "The \"".$dst."\" directory can not be created for icon set installation.";
					$__sInfo['command'] = "copy(\"../temp/install/software/look/".$_POST['sWidget']."/".$file."\", \"".$dst."/".$file."\")";
					$__sInfo['values'] = '';
					copy('../temp/install/software/look/'.$_POST['sWidget'].'/'.$file, $dst.'/'.$file);
				}
				closedir($dir);
			}

			# cleanup
			if (file_exists('../temp/install/'.$_POST['sWidget'].'.tgz')) {
				$__sInfo['error'] = "The \"../temp/install/".$_POST['sWidget']."\" directory can not be removed after widget installation.";
				$__sInfo['command'] = "unlink(\"../temp/install/".$_POST['sWidget'].".tgz\")";
				$__sInfo['values'] = '';
				unlink('../temp/install/'.$_POST['sWidget'].'.tgz');
			}

		} else {												// perform the uninstall
			if ($_POST['sType'] == 'exts') {
				require('../temp/install/software/code/'.$_POST['sWidget'].'/setup.php');
				call_user_func(strtolower($_POST['sWidget']).'_uninstall', $_POST['sWidget']);
			} else if ($_POST['sType'] == 'icons') {
				# remove existing symlink
				# WARNING: this is just a precaution so at least one account will have its icon set configured with the default icons so that one account will still work correctly
				$__sInfo['error'] = "The users configured icons symlink can not be removed after widget uninstallation.";
				$__sInfo['command'] = "unlink(\"../home/".$_POST['sUsername']."/imgs\")";
				$__sInfo['values'] = '';
				unlink('../home/'.$_POST['sUsername'].'/imgs');

				# create new symlink to the default icon set
				$__sInfo['error'] = "The users icons symlink can not be created as the default after widget uninstallation.";
				$__sInfo['command'] = "symlink(\"../../imgs/default\", \"../home/".$_POST['sUsername']."/imgs\")";
				$__sInfo['values'] = '';
				symlink('../../imgs/default', '../home/'.$_POST['sUsername'].'/imgs');

				# delete the icon set
				delTree('../imgs/'.$_POST['sWidget']);
			} else if ($_POST['sType'] == 'themes') {
# LEFT OFF - test this section out
				# remove existing symlinks
				# WARNING: this is just a precaution so at least one account will have its icon set configured with the default icons so that one account will still work correctly
				$__sInfo['error'] = "The users configured icons symlink can not be removed after widget uninstallation.";
				$__sInfo['command'] = "unlink(\"../home/".$_POST['sUsername']."/imgs\")";
				$__sInfo['values'] = '';
				unlink('../home/'.$_POST['sUsername'].'/imgs');

				$__sInfo['error'] = "The users configured theme symlink can not be removed after widget uninstallation.";
				$__sInfo['command'] = "unlink(\"../home/".$_POST['sUsername']."/look\")";
				$__sInfo['values'] = '';
				unlink('../home/'.$_POST['sUsername'].'/look');

# REMOVED 2020/05/11 - we are no longer doing these widgets (this was formally a symlink to the 'styles' directory [containing the css files])
				#$__sInfo['error'] = "The users configured skin symlink can not be removed after widget uninstallation.";
				#$__sInfo['command'] = "unlink(\"../../home/".$_POST['sUsername']."/skin\")";
				#$__sInfo['values'] = '';
				#unlink('../../home/'.$_POST['sUsername'].'/skin');

				// create new symlinks to the default theme
				$__sInfo['error'] = "The users icons symlink can not be created as the default after widget uninstallation.";
				$__sInfo['command'] = "symlink(\"../../imgs/default\", \"../home/".$_POST['sUsername']."/imgs\")";
				$__sInfo['values'] = '';
				symlink('../../imgs/default', '../home/'.$_POST['sUsername'].'/imgs');

				$__sInfo['error'] = "The users theme symlink can not be created as the default after widget uninstallation.";
				$__sInfo['command'] = "symlink(\"../../look/default\", \"../home/".$_POST['sUsername']."/look\")";
				$__sInfo['values'] = '';
				symlink('../../look/default', '../home/'.$_POST['sUsername'].'/look');

# REMOVED 2020/05/11 - we are no longer doing these widgets (this was formally a symlink to the 'styles' directory [containing the css files])
				#$__sInfo['error'] = "The users configured skin symlink can not be removed after widget uninstallation.";
				#$__sInfo['command'] = "symlink(\"../../skin/default\", \"../home/".$_POST['sUsername']."/skin\")";
				#$__sInfo['values'] = '';
				#symlink('../../skin/default', '../home/'.$_POST['sUsername'].'/skin');

				// delete the theme
				delTree('../look/'.$_POST['sWidget']);
			}
		}

		echo "<s><msg>The addon(s) have been ".$_POST['sStatus']."ed successfully!</msg></s>\n";
		exit();
	}
	break;
}




# -- Dashboard API --

switch($_POST['A']) {						# Process the submitted (A)ction
# LEFT OFF - change the 'case' to be the T, not the A
    case 'initialize':						# Initializes the Module UI
	if ($_POST['T'] == 'UI') {				# Process the submitted (T)arget
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }

		# connect to the DB for reading below
		if (! connect2DB(DB_HOST,DB_NAME,DB_ROUN,DB_ROPW)) { exit(); }		# NOTE: the connect2DB has its own error handling so we don't need to do it here!

# REMOVED 2025/03/05 - this was moved to its own function
#		# 1. Gather all the configured dashboard groups for the program
#		$__sInfo['error'] = "Failed to find the dashboard groups in the database.";
#		$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."ApplicationSettings_Groups";
#		$__sInfo['values'] = 'None';
#		$Groups = $_LinkDB->query($__sInfo['command']);

# REMOVED 2025/03/01 - this table is no longer used
#		# 2. Obtain the URI for the users email system
#		$__sInfo['error'] = "Failed to find the 'Social URI' value in the database.";
#		$__sInfo['command'] = "SELECT socialURI FROM ".DB_PRFX."SystemConfiguration WHERE id='1' LIMIT 1";
#		$__sInfo['values'] = 'None';
#		$System = $_LinkDB->query($__sInfo['command']);
#		$system = $System->fetch_assoc();

		# 3. Store the employee information
		$__sInfo['error'] = "Failed to find the Employee's account information in the database.";
		$__sInfo['command'] = "SELECT id,name,timeStatus,timeAvail FROM ".DB_PRFX."Employees WHERE username=? LIMIT 1";
		$__sInfo['values'] = '[s] '.$_POST['sUsername'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('s', $_POST['sUsername']);
		$stmt->execute();
		$user = $stmt->get_result()->fetch_assoc();

# UPDATED 2025/02/15 - these are called individually
#		# 4. Store more employee information
#		$__sInfo['error'] = "Failed to find all Employee status information in the database.";
#		$__sInfo['command'] = "SELECT id,name,timeStatus,timeAvail FROM ".PREFIX."Employees ORDER BY name";
#		$__sInfo['values'] = 'None';
#		$Employees = $_LinkDB->query($__sInfo['command']);
#
#		# 5. Check for certain db tables to obtain 'Assigned To' 'Work Order' tasks for the employee
#		$__sInfo['error'] = "Failed to find the Employee's 'Assigned To' tasks in the database.";
#		$__sInfo['command'] = "SHOW TABLES LIKE '".PREFIX."WorkOrders'";
#		$__sInfo['values'] = 'None';
#		$Tables_WO = $_LinkDB->query($__sInfo['command']);
##		$admins = $stmt->get_result();					required?
#
#		$__sInfo['error'] = "Failed to find the Employee's 'Assigned To' tasks (Customer Accounts) in the database.";
#		$__sInfo['command'] = "SHOW TABLES LIKE '".PREFIX."CustomerAccounts'";
#		$__sInfo['values'] = 'None';
#		$Tables_CA = $_LinkDB->query($__sInfo['command']);

		$XML =	"<s>\n" .
			"   <xml>\n" .

			"	<dashboard sInterface='".$_sInterface."' bHostedService='".($_bHostedService ? 'true' : 'false')."' sUriSocial=\"".safeXML($_sUriSocial)."\" />\n" .

			"	<user id='".$user['id']."' sName=\"".safeXML($user['name'])."\" sStatus=\"".$user['timeStatus']."\" sAvailability=\"".$user['timeAvail']."\" />\n";

# UPDATED 2025/02/15 - these are called individually
#			"	<employees>\n";
#		while ($employee = $Employees->fetch_assoc())
#			{ $XML .= "	   <employee id='".$employee['id']."' sName=\"".safeXML($employee['name'])."\" sStatus=\"".$employee['timeStatus']."\" sAvailability=\"".$employee['timeAvail']."\" />\n"; }
#		$XML .=	"	</employees>\n" .
#
#			"	<work>\n";
## V2 - the below section needs to acquire more 'assigned to' items from various modules instead of just the 'Work Orders' module
#		if ($Tables_WO->num_rows !== 0) {							# if the 'Work Orders' module has been installed, then...
#			if ($Tables_CA->num_rows !== 0) {						# if the 'Customer Accounts' module has been installed, then..
#				# 6. Store the employee's assignment information
#				$gbl_errs['error'] = "Failed to find the Employee's assignment information in the database.";
#				$gbl_info['command'] = "
#					SELECT
#						 tblWO.id,tblWO.acctID,tblWO.required,tblWOA.employeeID
#					FROM
#						 ".PREFIX."WorkOrders tblWO
#					INNER JOIN
#						 ".PREFIX."WorkOrders_Assigned tblWOA ON tblWO.id=tblWOA.rowID
#					WHERE
#						 tblWOA.employeeID='".$user['id']."' AND tblWO.status<>'closed'
#				";									# NOTE: the "$user['id']" value was pulled from the DB, not passed, so no security threat here!
#				$gbl_info['values'] = 'None';
#				$WOs = $_LinkDB->query($gbl_info['command']);
#
#				while ($wo = $WOs->fetch_assoc()) {
#					# 6a. Store related customer account information
#					$gbl_errs['error'] = "Failed to find related customer account information in the database.";
#					$gbl_info['command'] = "SELECT id,name FROM ".PREFIX."CustomerAccounts WHERE id='".$wo['acctID']."' LIMIT 1";
#					$gbl_info['values'] = 'None';					# NOTE: the above "$wo['acctID']" value was pulled from the DB, not passed, so no security threat here!
#					$CAs = $_LinkDB->query($gbl_info['command']);
#					while ($ca = $CAs->fetch_assoc())
#						{ $XML .= "	   <job id='".$wo['id']."' sModuleName='Work Orders' nID=\"".$ca['id']."\" sName=\"".safeXML($ca['name'])."\" eRequired='".$wo['required']."' />\n"; }
#				}
#			}
#		}
#		$XML .=	"	</work>\n";

# REMOVED 2025/03/05 - this is now in it's own function
#		$XML .=	"	<groups>\n";
#		while ($group = $Groups->fetch_assoc()) {
#			$XML .=	"	   <group id='".$group['id']."' sName=\"".safeXML($group['name'])."\" sIcon=\"".safeXML($group['icon'])."\">\n";
#
#			# 7. Obtain the modules related to each iterated group
#			$__sInfo['error'] = "Failed to find the modules related to the iterated group in the database.";
#			$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."ApplicationSettings_Grouped WHERE groupID='".$group['id']."'";
#			$__sInfo['values'] = 'None';							# NOTE: the above "$group['id']" value was pulled from the DB, not passed, so no security threat here!
#			$Grouped = $_LinkDB->query($__sInfo['command']);
#			while ($grouped = $Grouped->fetch_assoc()) {
#				# 7a. Obtain the iterated modules' information
#				$__sInfo['error'] = "Failed to find the iterated modules' information in the database.";
#				$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."ApplicationSettings_Modules WHERE id='".$grouped['moduleID']."'";
#				$__sInfo['values'] = 'None';							# NOTE: the above "$wo['acctID']" value was pulled from the DB, not passed, so no security threat here!
#				$Modules = $_LinkDB->query($__sInfo['command']);
#				while ($module = $Modules->fetch_assoc())
#					{ $XML .= "		<module id='".$module['id']."' sName=\"".safeXML($module['name'])."\" sIcon=\"".safeXML($module['icon'])."\" />\n"; }
#			}
#
#			$XML .=	"	   </group>\n";
#		}
#
#		$XML .=	"	</groups>\n" .

		$XML .=	"   </xml>\n" .
			"</s>";
		echo $XML;
	}
	exit();
	break;




    case 'load':						# Loads the Dashboard
	if ($_POST['T'] == 'dashboard') {			# Process the submitted (T)arget
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }

		# connect to the DB for reading below
		if (! connect2DB(DB_HOST,DB_NAME,DB_ROUN,DB_ROPW)) { exit(); }		# NOTE: the connect2DB has its own error handling so we don't need to do it here!

# MOVED 2025/03/08 - moved at the top
		# define general info for any error generated below
#		$__sInfo['name'] = 'Unknown';
#		$__sInfo['contact'] = 'Unknown';
#		$__sInfo['other'] = 'n/a';


		# 1. Gather all the configured dashboard groups for the program
		$__sInfo['error'] = "Failed to find the dashboard groups in the database.";
		$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."ApplicationSettings_Groups";
		$__sInfo['values'] = 'None';
		$Groups = $_LinkDB->query($__sInfo['command']);


		echo "<s>\n";
		echo "   <xml>\n";

		echo "	<groups>\n";
		while ($group = $Groups->fetch_assoc()) {
			echo "	   <group id='".$group['id']."' sName=\"".safeXML($group['name'])."\" sIcon=\"".safeXML($group['icon'])."\">\n";

			# 1. Obtain the modules related to each iterated group
			$__sInfo['error'] = "Failed to find the modules related to the iterated group in the database.";
			$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."ApplicationSettings_Grouped WHERE groupID='".$group['id']."'";
			$__sInfo['values'] = 'None';							# NOTE: the above "$group['id']" value was pulled from the DB, not passed, so no security threat here!
			$Grouped = $_LinkDB->query($__sInfo['command']);
			while ($grouped = $Grouped->fetch_assoc()) {
				# 1a. Obtain the iterated modules' information
				$__sInfo['error'] = "Failed to find the iterated modules' information in the database.";
				$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."ApplicationSettings_Modules WHERE id='".$grouped['moduleID']."'";
				$__sInfo['values'] = 'None';							# NOTE: the above "$wo['acctID']" value was pulled from the DB, not passed, so no security threat here!
				$Modules = $_LinkDB->query($__sInfo['command']);
				while ($module = $Modules->fetch_assoc())
					{ echo "		<module id='".$module['id']."' sName=\"".safeXML($module['name'])."\" sIcon=\"".safeXML($module['icon'])."\" />\n"; }
			}

			echo "	   </group>\n";
		}
		echo "	</groups>\n";

		echo "   </xml>\n";
		echo "</s>\n";
		exit();
	}
	break;



    case 'toggle':						# Toggles the Interface
	if ($_POST['T'] == 'interface') {			# Process the submitted (T)arget
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		# -----
# DEPRECATED 2025/02/22
#		if (! validate($_POST['_sUriSocial'],128,'a-zA-Z0-9:\/_%\.\-')) { exit(); }

# VER2 - https://cloud.google.com/appengine/docs/php/mail/	for using gmail inside an embedded iframe
		# now check if the provider will allow their email interface in an <iframe> within this project
		$error = false;								# http://stackoverflow.com/questions/21263418/detect-x-frame-options
# DEPRECATED 2025/02/22
#		$URI = $_POST['_sUriSocial'];
		$curl = curl_init();

		$options = array(
			CURLOPT_URL            => $_sUriProject,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_ENCODING       => "",
			CURLOPT_AUTOREFERER    => true,
			CURLOPT_CONNECTTIMEOUT => 120,
			CURLOPT_TIMEOUT        => 120,
			CURLOPT_MAXREDIRS      => 10,
		);
		curl_setopt_array($curl, $options);
		$response = curl_exec($curl);
		$httpCode = curl_getinfo($curl);
		$headers=substr($response, 0, $httpCode['header_size']);
		if (stripos($headers, 'X-Frame-Options: deny') > -1 || stripos($headers, 'X-Frame-Options: SAMEORIGIN') > -1) { $error=true; }

		$httpcode= curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		#echo json_encode(array('httpcode'=>$httpcode, 'error'=>$error));
		if (! $error)
			{ echo "<s><data sUriProject='".$_sUriProject."' /></s>"; }	# NOTE: for safety (code injection) and to reduce duplication of data (it's not in Application.js)
		else
			{ echo "<f><msg>The service provider for your social interaction does not allow embedding in an iframe.</msg></f>"; }
# VER2 - add into the <f> message above that 'We recommend the use of Threads as your social needs.'
	}
	exit();
	break;




    case 'update':					# Update Values
	if ($_POST['T'] == 'status') {			# Process the submitted (T)arget [Updates the Users 'Status']
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		# -----
		if (! validate($_POST['sStatus'],7,'{in|out|delayed|logout}')) { exit(); }

		# load the users account info in the global variable
		if (! loadUser($_nTimeout,$__sUser,'rw','*',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

# MOVED 2025/03/08 - moved at the top
		# define general info for any error generated below
#		$__sInfo['name'] = 'Unknown';
#		$__sInfo['contact'] = 'Unknown';
#		$__sInfo['other'] = 'n/a';

		# process the status change
		if ($_POST['sStatus'] == 'logout') {					# there is no processing of an Employees_Timesheets record for this value since this could be anything (e.g. just switching devices [computer > phone] but are still available and clocked in, etc)
			# NOTE: the javascript side will handle the logout - this .php file won't even be called!  But just in case...
			echo "<s><msg>Your status has been updated successfully!</msg><data status=\"".$_POST['sStatus']."\" /></s>";
			exit();								# exit no matter if success or failure at this point
		}

		# 1. Process the status change (anything but logging out)
		#	pull the last stored record to make sure the user isn't already registered as being 'clocked in' (e.g. the user changed devices [computer > phone])
		$__sInfo['error'] = "Failed to find the Employee's last timesheet record in the database.";
		$__sInfo['command'] = "SELECT id,type FROM ".PREFIX."Employees_Timesheets WHERE employeeID='".$__sUser['id']."' ORDER BY time DESC LIMIT 1";
		$__sInfo['values'] = 'None';
# UPDATED 2025/02/21 - take into account someone without ANY history
#		$last = $_LinkDB->query($__sInfo['command'])->fetch_assoc();
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt->num_rows === 0) { $last['type'] = 'out'; } else { $last = $stmt->fetch_assoc(); }				# if the user has NO prior record (e.g. brand new account), then provide a default, otherwise, store the actual result

		# now apply the change in the database
		if ($last['type'] != 'out' && $_POST['sStatus'] != 'out') {		# if the user is already logged in (via the last record) AND the user isn't changing their status to being logged out, then...	NOTE: the 'in', 'pto', and 'sick' are considered the 'in' records
			$__sInfo['error'] = "The existing employee timesheet record can not be updated in the database.";
			$__sInfo['command'] = "UPDATE ".PREFIX."Employees_Timesheets SET type=? WHERE id='".$last['id']."'";		# overwrite the record as it may have been a mistake (e.g. the user clicking 'clock out', then 'pto')
			$__sInfo['values'] = '[s] '.$_POST['sStatus'];
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			$stmt->bind_param('s', $_POST['sStatus']);
			$stmt->execute();
		} else {								# otherwise, add a new record to the DB
			$__sInfo['error'] = "The new employee timesheet record can not be created in the database.";
			$__sInfo['command'] = "INSERT INTO ".PREFIX."Employees_Timesheets (employeeID,type,time,memo,createdBy,createdOn,updatedBy,updatedOn) VALUES ('".$__sUser['id']."',?,'".$_."','','0','".$_."','0','".$_."')";
			$__sInfo['values'] = '[s] '.$_POST['sStatus'];
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			$stmt->bind_param('s', $_POST['sStatus']);
			$stmt->execute();
		}

		$extra = '';								# used to define extra field values to update in the below UPDATE sql call
		if ($_POST['sStatus'] != 'in' && $_POST['sStatus'] != 'logout') { $extra = ",timeAvail='no'"; }				# if the user is NOT clocking in -AND- they are NOT clocking out of the system, then the only remaining options indicate that the user is unavailable (e.g. pto, sick, clocked-out, etc)

		# update the Employees' status record for the 'Employees' tab on the 'Dashboard' module
		$__sInfo['error'] = "The existing employee status can not be updated in the database.";
		$__sInfo['command'] = "UPDATE ".PREFIX."Employees SET timeStatus=?".$extra." WHERE id='".$__sUser['id']."'";
		$__sInfo['values'] = '[s] '.$_POST['sStatus'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('s', $_POST['sStatus']);
		$stmt->execute();

		echo "<s><msg>Your status has been updated successfully!</msg><data sStatus=\"".safeXML($_POST['sStatus'])."\" /></s>";


	} else if ($_POST['T'] == 'availability') {			# Process the submitted (T)arget [Updates the Users 'Availability']
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		# -----
		if (! validate($_POST['sAvailability'],7,'{yes|no|break|meeting|lunch}')) { exit(); }

		# load the users account info in the global variable
		if (! loadUser($_nTimeout,$__sUser,'rw','*',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

# MOVED 2025/03/08 - moved at the top
		# define general info for any error generated below
#		$__sInfo['name'] = 'Unknown';
#		$__sInfo['contact'] = 'Unknown';
#		$__sInfo['other'] = 'n/a';

		# update the Employees' availability record for the 'Employees' tab on the 'Dashboard' module
		$__sInfo['error'] = "The existing employee availability can not be updated in the database.";
		$__sInfo['command'] = "UPDATE ".DB_PRFX."Employees SET timeAvail=? WHERE id='".$__sUser['id']."'";
		$__sInfo['values'] = '[s] '.$_POST['sAvailability'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('s', $_POST['sAvailability']);
		$stmt->execute();

		echo "<s><msg>Your availability has been updated successfully!</msg><data sAvailability=\"".safeXML($_POST['sAvailability'])."\" /></s>";


	} else if ($_POST['T'] == 'employees') {			# Process the submitted (T)arget [Updates the Employees List]
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
file_put_contents('debug.txt', "01\n", FILE_APPEND);

		# connect to the DB for reading below
		if (! connect2DB(DB_HOST,DB_NAME,DB_ROUN,DB_ROPW)) { exit(); }		# NOTE: the connect2DB has its own error handling so we don't need to do it here!
file_put_contents('debug.txt', "02\n", FILE_APPEND);

# MOVED 2025/03/08 - moved at the top
		# define general info for any error generated below
#		$__sInfo['name'] = 'Unknown';
#		$__sInfo['contact'] = 'Unknown';
#		$__sInfo['other'] = 'n/a';

		# 1. Store the employee information
		$__sInfo['error'] = "Failed to find the Employee's account information in the database.";
		$__sInfo['command'] = "SELECT id,name FROM ".DB_PRFX."Employees WHERE username=? LIMIT 1";
		$__sInfo['values'] = '[s] '.$_POST['sUsername'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('s', $_POST['sUsername']);
		$stmt->execute();
		$user = $stmt->get_result()->fetch_assoc();
file_put_contents('debug.txt', "03\n", FILE_APPEND);

		# 2. Store more employee information
		$__sInfo['error'] = "Failed to find all Employee status information in the database.";
		$__sInfo['command'] = "SELECT id,name,timeStatus,timeAvail FROM ".DB_PRFX."Employees ORDER BY name";
		$__sInfo['values'] = 'None';
		$Employees = $_LinkDB->query($__sInfo['command']);
file_put_contents('debug.txt', "04\n", FILE_APPEND);

		# 3. Check for certain db tables to obtain 'Assigned To' 'Work Order' tasks for the employee
		$__sInfo['error'] = "Failed to find the Employee's 'Assigned To' tasks in the database.";
		$__sInfo['command'] = "SHOW TABLES LIKE '".DB_PRFX."WorkOrders'";
		$__sInfo['values'] = 'None';
		$Tables_WO = $_LinkDB->query($__sInfo['command']);
#		$admins = $stmt->get_result();					required?
file_put_contents('debug.txt', "05\n", FILE_APPEND);

		$__sInfo['error'] = "Failed to find the Employee's 'Assigned To' tasks (Customer Accounts) in the database.";
		$__sInfo['command'] = "SHOW TABLES LIKE '".DB_PRFX."CustomerAccounts'";
		$__sInfo['values'] = 'None';
		$Tables_CA = $_LinkDB->query($__sInfo['command']);
file_put_contents('debug.txt', "06\n", FILE_APPEND);


		echo "<s>\n";
		echo "   <xml>\n";

		echo "	<employees>\n";
		while ($employee = $Employees->fetch_assoc())
			{ echo "	   <employee id='".$employee['id']."' sName=\"".safeXML($employee['name'])."\" sStatus=\"".safeXML($employee['timeStatus'])."\" sAvailability=\"".safeXML($employee['timeAvail'])."\" />\n"; }
		echo "	</employees>\n";
file_put_contents('debug.txt', "07\n", FILE_APPEND);

# V2 - the below section needs to acquire more 'assigned to' items from various modules instead of just the 'Work Orders' module
		echo "	<jobs>\n";
		if ($Tables_WO->num_rows !== false && $Tables_WO->num_rows > 0) {			# if the 'Work Orders' module has been installed, then...
file_put_contents('debug.txt', "inside top\n", FILE_APPEND);
			if ($Tables_CA->num_rows !== false && $Tables_CA->num_rows > 0) {		# if the 'Customer Accounts' module has been installed, then..
file_put_contents('debug.txt', "inside btm\n", FILE_APPEND);
				# 4. Store the employee's assignment information
				$__sInfo['error'] = "Failed to find the Employee's assignment information in the database.";
				$__sInfo['command'] = "
					SELECT
						 tblWO.id,tblWO.acctID,tblWO.required,tblWOA.employeeID
					FROM
						 ".PREFIX."WorkOrders tblWO
					INNER JOIN
						 ".PREFIX."WorkOrders_Assigned tblWOA ON tblWO.id=tblWOA.rowID
					WHERE
						 tblWOA.employeeID='".$user['id']."' AND tblWO.status<>'closed'
				";									# NOTE: the "$user['id']" value was pulled from the DB, not passed, so no security threat here!
				$__sInfo['values'] = 'None';
				$WOs = $_LinkDB->query($__sInfo['command']);

				while ($wo = $WOs->fetch_assoc()) {
					# 6a. Store related customer account information
					$__sInfo['error'] = "Failed to find related customer account information in the database.";
					$__sInfo['command'] = "SELECT id,name FROM ".PREFIX."CustomerAccounts WHERE id='".$wo['acctID']."' LIMIT 1";
					$__sInfo['values'] = 'None';					# NOTE: the above "$wo['acctID']" value was pulled from the DB, not passed, so no security threat here!
					$CAs = $_LinkDB->query($__sInfo['command']);
					while ($ca = $CAs->fetch_assoc())
						{ echo "	   <job id='".$wo['id']."' sModuleName='Work Orders' nID='".$ca['id']."' sName=\"".safeXML($ca['name'])."\" eRequired='".$wo['required']."' />\n"; }
				}
			}
		}
		echo "	</jobs>\n";
file_put_contents('debug.txt', "08\n", FILE_APPEND);

		echo "   </xml>\n";
		echo "</s>";
	}
	exit();
	break;
}




# An invalid API was attempted so report this to security
echo "<f><msg>An invalid request has occurred, our staff has been notified.</msg></f>";
if (array_key_exists('sUsername', $_POST)) { $__sInfo['name'] = $_POST['sUsername']; }
if (array_key_exists('email', $_POST)) { $__sInfo['contact'] = $_POST['email']; }
file_put_contents('../data/_logs/'.$_sLogModule, "---------- [ Possible Cracking Attempt ] ----------\nDate: ".gmdate("Y-m-d H:i:s",time())." GMT\nFrom: ".$_SERVER['REMOTE_ADDR']."\n\nProject: ".PROJECT."\nModule: ".MODULE."\nScript: ".SCRIPT."\n\nDB Host: ".DB_HOST."\nDB Name: ".DB_NAME."\nDB Prefix: ".DB_PRFX."\n\nName: ".$__sInfo['name']."\nContact: ".$__sInfo['contact']."\nOther: ".$__sInfo['other']."\n\nSummary: An invalid API value was passed to the script.\n\nVar Dump:\n\n_POST\n".print_r($_POST, true)."\n_GET\n".print_r($_GET, true)."\n\n\n\n", FILE_APPEND);
# UPDATED 2025/03/08
#sendMail($_sSecurityEmail,$_sSecurityName,$_sAlertsEmail,$_sAlertsName,'!!! Possible Cracking Attempt !!!',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: A user is attempting to pass an invalid 'action' or 'target' values.<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
sendMail($_sSecurityEmail,$_sSecurityName,$_sAlertsEmail,$_sAlertsName,'!!! Possible Cracking Attempt !!!',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nWe might have had a possible cracking attempt made on ".$_.", from ".$_SERVER['REMOTE_ADDR'].", while attempting to pass an invalid API value.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nName: ".$__sInfo['name']."<br />\nContact: ".$__sInfo['contact']."<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: An invalid API value was passed to the script.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br />\n<br />\n[".SCRIPT."; Body]<br />\n<br />\nVar Dump:<br />\n</p>\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre><br />\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");








# DEPRECATED 2025/02/16

#} else if ($_POST['action'] == 'check' && $_POST['target'] == 'addons') {	# CHECK WHICH MODULES ARE INSTALLED
#	# validate all submitted data
#	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
#	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
#
#	# connect to the DB for reading below
#	if (! connect2DB(DBHOST,DBNAME,DBUNRO,DBPWRO)) { exit(); }		# NOTE: the connect2DB has its own error handling so we don't need to do it here!
#
#	$installed = '';
#
#	$gbl_errs['error'] = "Failed to find all of the currently installed modules in the database.";
#	$gbl_info['command'] = "SELECT name FROM ".PREFIX."SystemConfiguration_Modules";					# NOTE: this is only applicable for extensions, not themes and icon sets
#	$gbl_info['values'] = 'None';
#	$Modules = $linkDB->query($gbl_info['command']);
#	while ($module = $Modules->fetch_assoc())
#		{ $installed .= $module['name'].'|'; }
#	echo "<s><data>".substr(str_replace(' ', '_', $installed), 0, -1)."</data></s>";
#	exit();




?>
