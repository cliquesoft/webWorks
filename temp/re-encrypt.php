#!/usr/local/bin/php-7.2
<?php
# re-encrypt.php	This file re-encrypts the values in the database that were
#			decrypted prior to updating to php version 7.x
# created		2020/09/16 by Dave Henderson (dhenderson@cliquesoft.org)
# updated		2020/10/01 by Dave Henderson (dhenderson@cliquesoft.org)


# Constant Definitions
define("MODULE",'webBooks');					# the name of this module (NOTE: this can be the same as the PROJECT constant in the envars.php file)
define("SCRIPT",basename($argv[0]));				# the name of this script (for tracing bugs and automated messages)
define("NAME",'re-encrypt database');

# Module Requirements						  NOTE: MUST come below Module Constant Definitions
require_once('../../../sqlaccess');				# NOTE: since this is being called from prior directory structure, this script will be run from 'modules/webbooks'
require_once('../../data/config.php');
if (file_exists('../../data/config.'.strtolower(MODULE).'.php')) { require_once('../../data/config.'.strtolower(MODULE).'.php'); }
require_once('../../code/_mimemail.php');
require_once('../../code/_global.php');

# Start or resume the PHP session				  NOTE: gains access to $_SESSION variables in this script
session_start();




# format the dates in UTC
$_ = gmdate("Y-m-d H:i:s",time());				# used this mannor so all the times will be the exact same (also see http://php.net/manual/en/function.gmdate.php)




# 1. Connect to the SQL server
if (! connect2DB(DBHOST,DBNAME,DBUNRO,DBPWRO)) { exit(); }

# 2. Get the encryption salt
$salt = file_get_contents('../../../denaccess');

# 3. Re-encrypt the prior decrypted database values
file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "\n----- ENCRYPTING (7.2) -----\n", FILE_APPEND);

# [SystemConfiguration_Commerce > sid]
$gbl_errs['error'] = "Failed to find all the commerce records in the database.";
$gbl_info['command'] = "SELECT id,sid FROM ".PREFIX."SystemConfiguration_Commerce ORDER BY id";
$gbl_info['values'] = 'None';
$Commerce = $linkDB->query($gbl_info['command']);
file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "\nCommerce records...\n", FILE_APPEND);
while ($commerce = $Commerce->fetch_assoc()) {
	file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "   ".$commerce['id'].":", FILE_APPEND);
	if (strlen($commerce['sid']) > 1)
		{ $sid = Cipher::encrypt($commerce['sid'], $salt); }
	else
		{ file_put_contents('../../temp/prior_installs/2017.02.14.0.log', " [skipping]\n", FILE_APPEND); continue; }

	file_put_contents('../../temp/prior_installs/2017.02.14.0.log', " [processing]\n", FILE_APPEND);
	$gbl_errs['error'] = "Failed to update the commerce sid value (id: ".$commerce['id'].") in the database.";
	$gbl_info['command'] = "UPDATE ".PREFIX."SystemConfiguration_Commerce SET sid=\"".$sid."\" WHERE id='".$commerce['id']."'";
	$gbl_info['values'] = 'None';
	$stmt = $linkDB->query($gbl_info['command']);
}
# [BusinessConfiguration > merchantID]
$gbl_errs['error'] = "Failed to find all the merchant ID records in the database.";
$gbl_info['command'] = "SELECT id,merchantID FROM ".PREFIX."BusinessConfiguration ORDER BY id";
$gbl_info['values'] = 'None';
$Merchant = $linkDB->query($gbl_info['command']);
file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "\nMerchant records...\n", FILE_APPEND);
while ($merchant = $Merchant->fetch_assoc()) {
	file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "   ".$merchant['id'].":", FILE_APPEND);
	if (strlen($merchant['merchantID']) > 1)
		{ $id = Cipher::encrypt($merchant['merchantID'], $salt); }
	else
		{ file_put_contents('../../temp/prior_installs/2017.02.14.0.log', " [skipping]\n", FILE_APPEND); continue; }

	file_put_contents('../../temp/prior_installs/2017.02.14.0.log', " [processing]\n", FILE_APPEND);
	$gbl_errs['error'] = "Failed to update the merchant ID value (id: ".$merchant['id'].") in the database.";
	$gbl_info['command'] = "UPDATE ".PREFIX."BusinessConfiguration SET merchantID=\"".$id."\" WHERE id='".$merchant['id']."'";
	$gbl_info['values'] = 'None';
	$stmt = $linkDB->query($gbl_info['command']);
}
# [BusinessConfiguration_CreditCards > number,cvv2]
$gbl_errs['error'] = "Failed to find all the credit card records in the database.";
$gbl_info['command'] = "SELECT id,number,cvv2 FROM ".PREFIX."BusinessConfiguration_CreditCards ORDER BY id";
$gbl_info['values'] = 'None';
$Card = $linkDB->query($gbl_info['command']);
file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "\nCredit card records...\n", FILE_APPEND);
while ($card = $Card->fetch_assoc()) {
	file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "   ".$card['id'].":", FILE_APPEND);
	if (strlen($card['number']) > 1) { $num = Cipher::encrypt($card['number'], $salt); } else { $num = ''; }
	if (strlen($card['cvv2']) > 1) { $cvv = Cipher::encrypt($card['cvv2'], $salt); } else { $cvv = ''; }

	file_put_contents('../../temp/prior_installs/2017.02.14.0.log', " [processing]\n", FILE_APPEND);
	$gbl_errs['error'] = "Failed to update the credit card values (id: ".$card['id'].") in the database.";
	$gbl_info['command'] = "UPDATE ".PREFIX."BusinessConfiguration_CreditCards SET number=\"".$num."\",cvv2=\"".$cvv."\" WHERE id='".$card['id']."'";
	$gbl_info['values'] = 'None';
	$stmt = $linkDB->query($gbl_info['command']);
}
# [Employees > password,pes,homePhone,homeMobile,homeEmail,homeAddr1,driversLicense,ssn]
$gbl_errs['error'] = "Failed to find all the employee records in the database.";
$gbl_info['command'] = "SELECT id,password,homePhone,homeMobile,homeEmail,homeAddr1,driversLicense,ssn FROM ".PREFIX."Employees ORDER BY id";
$gbl_info['values'] = 'None';
$Employee = $linkDB->query($gbl_info['command']);
file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "\nEmployee records...\n", FILE_APPEND);
while ($employee = $Employee->fetch_assoc()) {
	file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "   ".$employee['id'].":", FILE_APPEND);
	if (strlen($employee['password']) > 1) { $pass = Cipher::encrypt($employee['password'], $salt); } else { $pass = ''; }
	if (strlen($employee['homePhone']) > 1) { $phone = Cipher::encrypt($employee['homePhone'], $salt); } else { $phone = ''; }
	if (strlen($employee['homeMobile']) > 1) { $mobile = Cipher::encrypt($employee['homeMobile'], $salt); } else { $mobile = ''; }
	if (strlen($employee['homeEmail']) > 1) { $email = Cipher::encrypt($employee['homeEmail'], $salt); } else { $email = ''; }
	if (strlen($employee['homeAddr1']) > 1) { $addr = Cipher::encrypt($employee['homeAddr1'], $salt); } else { $addr = ''; }
	if (strlen($employee['driversLicense']) > 1) { $license = Cipher::encrypt($employee['driversLicense'], $salt); } else { $license = ''; }
	if (strlen($employee['ssn']) > 1 != '') { $ssn = Cipher::encrypt($employee['ssn'], $salt); } else { $ssn = ''; }

	file_put_contents('../../temp/prior_installs/2017.02.14.0.log', " [processing]\n", FILE_APPEND);
	$gbl_errs['error'] = "Failed to update the employee values (id: ".$employee['id'].") in the database.";
	$gbl_info['command'] = "UPDATE ".PREFIX."Employees SET password=\"".$pass."\",homePhone=\"".$phone."\",homeMobile=\"".$mobile."\",homeEmail=\"".$email."\",homeAddr1=\"".$addr."\",driversLicense=\"".$license."\",ssn=\"".$ssn."\" WHERE id='".$employee['id']."'";
	$gbl_info['values'] = 'None';
	$stmt = $linkDB->query($gbl_info['command']);
}
# [BankAccounts > account]
$gbl_errs['error'] = "Failed to find all the bank account records in the database.";
$gbl_info['command'] = "SELECT id,account FROM ".PREFIX."BankAccounts ORDER BY id";
$gbl_info['values'] = 'None';
$Bank = $linkDB->query($gbl_info['command']);
file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "\nBank records...\n", FILE_APPEND);
while ($bank = $Bank->fetch_assoc()) {
	file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "   ".$bank['id'].":", FILE_APPEND);
	if (strlen($bank['account']) > 1)
		{ $acct = Cipher::encrypt($bank['account'], $salt); }
	else
		{ file_put_contents('../../temp/prior_installs/2017.02.14.0.log', " [skipping]\n", FILE_APPEND); continue; }

	file_put_contents('../../temp/prior_installs/2017.02.14.0.log', " [processing]\n", FILE_APPEND);
	$gbl_errs['error'] = "Failed to update the bank account value (id: ".$bank['id'].") in the database.";
	$gbl_info['command'] = "UPDATE ".PREFIX."BankAccounts SET account=\"".$acct."\" WHERE id='".$bank['id']."'";
	$gbl_info['values'] = 'None';
	$stmt = $linkDB->query($gbl_info['command']);
}
# [FreightAccounts > account]
$gbl_errs['error'] = "Failed to find all the freight account records in the database.";
$gbl_info['command'] = "SELECT id,account FROM ".PREFIX."FreightAccounts ORDER BY id";
$gbl_info['values'] = 'None';
$Freight = $linkDB->query($gbl_info['command']);
file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "\nFreight records...\n", FILE_APPEND);
while ($freight = $Freight->fetch_assoc()) {
	file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "   ".$freight['id'].":", FILE_APPEND);
	if (strlen($freight['account']) > 1)
		{ $acct = Cipher::encrypt($freight['account'], $salt); }
	else
		{ file_put_contents('../../temp/prior_installs/2017.02.14.0.log', " [skipping]\n", FILE_APPEND); continue; }

	file_put_contents('../../temp/prior_installs/2017.02.14.0.log', " [processing]\n", FILE_APPEND);
	$gbl_errs['error'] = "Failed to update the freight account value (id: ".$freight['id'].") in the database.";
	$gbl_info['command'] = "UPDATE ".PREFIX."FreightAccounts SET account=\"".$acct."\" WHERE id='".$freight['id']."'";
	$gbl_info['values'] = 'None';
	$stmt = $linkDB->query($gbl_info['command']);
}

# NOTE: we need to encrypt each modules data here as well instead of letting them do it individually since the old encryption ability will be gone after the core update

# [CustomerAccounts > password,commerceSID,mainAddr1,billAddr1]
$gbl_errs['error'] = "Failed to obtain the existence of the 'id' column in the 'CustomerAccounts' table.";	// first check that the modules have been installed
$gbl_info['command'] = "SHOW COLUMNS FROM ".PREFIX."CustomerAccounts LIKE 'id'";
$gbl_info['values'] = 'None';
$table = $linkDB->query($gbl_info['command']);
if ($table) {
	$gbl_errs['error'] = "Failed to find all the customer account records in the database.";
	$gbl_info['command'] = "SELECT id,password,commerceSID,mainAddr1,billAddr1 FROM ".PREFIX."CustomerAccounts ORDER BY id";
	$gbl_info['values'] = 'None';
	$Customer = $linkDB->query($gbl_info['command']);
	file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "\nCustomer records...\n", FILE_APPEND);
	while ($customer = $Customer->fetch_assoc()) {
		file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "   ".$customer['id'].":", FILE_APPEND);
		if (strlen($customer['password']) > 1) { $pass = Cipher::encrypt($customer['password'], $salt); } else { $pass = ''; }
		if (strlen($customer['commerceSID']) > 1) { $sid = Cipher::encrypt($customer['commerceSID'], $salt); } else { $sid = ''; }
		if (strlen($customer['mainAddr1']) > 1) { $main = Cipher::encrypt($customer['mainAddr1'], $salt); } else { $main = ''; }
		if (strlen($customer['billAddr1']) > 1) { $bill = Cipher::encrypt($customer['billAddr1'], $salt); } else { $bill = ''; }

		file_put_contents('../../temp/prior_installs/2017.02.14.0.log', " [processing]\n", FILE_APPEND);
		$gbl_errs['error'] = "Failed to update the freight account value (id: ".$customer['id'].") in the database.";
		$gbl_info['command'] = "UPDATE ".PREFIX."CustomerAccounts SET password=\"".$pass."\",commerceSID=\"".$sid."\",mainAddr1=\"".$main."\",billAddr1=\"".$bill."\" WHERE id='".$customer['id']."'";
		$gbl_info['values'] = 'None';
		$stmt = $linkDB->query($gbl_info['command']);
	}
}

# [QuotesAndInvoices > altAddr1]
$gbl_errs['error'] = "Failed to obtain the existence of the 'id' column in the 'QuotesAndInvoices' table.";
$gbl_info['command'] = "SHOW COLUMNS FROM ".PREFIX."QuotesAndInvoices LIKE 'id'";
$gbl_info['values'] = 'None';
$table = $linkDB->query($gbl_info['command']);
if ($table) {
	$gbl_errs['error'] = "Failed to find all the freight account records in the database.";
	$gbl_info['command'] = "SELECT id,altAddr1 FROM ".PREFIX."QuotesAndInvoices ORDER BY id";
	$gbl_info['values'] = 'None';
	$Invoice = $linkDB->query($gbl_info['command']);
	file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "\nInvoice records...\n", FILE_APPEND);
	while ($invoice = $Invoice->fetch_assoc()) {
		file_put_contents('../../temp/prior_installs/2017.02.14.0.log', "   ".$invoice['id'].":", FILE_APPEND);
		if (strlen($invoice['altAddr1']) > 1)
			{ $addr = Cipher::encrypt($invoice['altAddr1'], $salt); }
		else
			{ file_put_contents('../../temp/prior_installs/2017.02.14.0.log', " [skipping]\n", FILE_APPEND); continue; }

		file_put_contents('../../temp/prior_installs/2017.02.14.0.log', " [processing]\n", FILE_APPEND);
		$gbl_errs['error'] = "Failed to update the freight account value (id: ".$invoice['id'].") in the database.";
		$gbl_info['command'] = "UPDATE ".PREFIX."QuotesAndInvoices SET altAddr1=\"".$addr."\" WHERE id='".$invoice['id']."'";
		$gbl_info['values'] = 'None';
		$stmt = $linkDB->query($gbl_info['command']);
	}
}


?>
