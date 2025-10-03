<?php
# commerce.php	the receipient side of *COMMERCE EXCHANGES* between paired webBooks
#
# Created	2014/09/26 by Dave Henderson (dhenderson@cliquesoft.org or support@cliquesoft.org)
# Updated	2020/12/17 by Dave Henderson (dhenderson@cliquesoft.org or support@cliquesoft.org)
#
# Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
# device, this software is licensed under the Cliquesoft Public License (CPLv2)
# as found on the Cliquesoft website at www.cliquesoft.org.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.
#
# ADDITIONAL:
# http://php.net/manual/en/simplexml.examples-basic.php
# http://stackoverflow.com/questions/9374224/how-to-receive-xml-requests-and-send-response-xml-in-php
# http://stackoverflow.com/questions/7916184/how-to-properly-send-and-receive-xml-using-curl?rq=1
# http://stackoverflow.com/questions/2841399/php-form-security-with-referer
# http://stackoverflow.com/questions/11006390/echo-simplexml-object
# http://stackoverflow.com/questions/1560827/php-simplexml-check-if-a-child-exist
# https://www.zulius.com/how-to/close-browser-connection-continue-execution/
# http://stackoverflow.com/questions/138374/close-a-connection-early?lq=1
# http://stackoverflow.com/questions/4236040/example-of-how-to-use-fastcgi-finish-request


# Constant Definitions
define("MODULE",'webBooks');					# the name of this module (NOTE: this can be the same as the PROJECT constant in the envars.php file)
define("SCRIPT",basename($_SERVER['SCRIPT_NAME']));		# the name of this script (for tracing bugs and automated messages)

# Module Requirements						  NOTE: MUST come below Module Constant Definitions
require_once('../../sqlaccess');
require_once('../data/config.php');
if (file_exists('../data/config.'.strtolower(MODULE).'.php')) { require_once('../data/config.'.strtolower(MODULE).'.php'); }
require_once('_mimemail.php');
require_once('_global.php');

# Start or resume the PHP session				  NOTE: gains access to $_SESSION variables in this script
#session_start();




# format the dates in UTC
$_ = gmdate("Y-m-d H:i:s",time());				# used this mannor so all the times will be the exact same (also see http://php.net/manual/en/function.gmdate.php)




# NOTE: the below is the *RECEIPIENT* side of the *SOFTWARE PAIRING* and will be engaged by a calling business_configuration.php or customer_accounts.php
if ($_POST['action'] == 'pair' && $_POST['target'] == 'software') {		# PAIR THIS SOFTWARE WITH ANOTHER, PEER-2-PEER
	header('Content-Type: text/xml; charset=utf-8');
	echo "<?xml version='1.0' encoding='UTF-8'?>\n\n";


	# validate all submitted data
	if (! validate($_SERVER["HTTP_REFERER"],128,'[^a-zA-Z0-9_\.:\/\-]')) { exit(); }
	if (! validate($_POST['sid'],40,'[^a-zA-Z0-9]')) { exit(); }


	if ($_SERVER["HTTP_REFERER"] == '' || is_null($_SERVER["HTTP_REFERER"])) {
		echo "<req>\n";
		echo "	<f>\n";
		echo "		<msg>The referer value is invalid.</msg>\n";
		echo "	</f>\n";
		echo "</req>\n";
		sendMail($gbl_emailCrackers,$gbl_nameCrackers,$gbl_emailNoReply,SCRIPT.' script','*** Possible Cracking Attempt ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nOur Error: No referrer value was passed while attempting to pair the software.<br />\n<br />\nVar Dump:<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		exit();
	}

	if (! connect2DB(DBHOST,DBNAME,DBUNRW,DBPWRW,'oop','a')) {		# NOTE: we store the generic reply "We have encountered an error..." in an array and output a meaningful message here
		echo "<req>\n";
		echo "	<f>\n";
		echo "		<msg>Error connecting to the Database server.</msg>\n";
		echo "	</f>\n";
		echo "</req>\n";
		exit();
	}


	# 1. Check that the referer is actually in the system and that this isn't a hack attempt
	$gbl_errs['error'] = "Failed to find the 'HTTP Referer' information in the database when pairing webBooks.";
	$gbl_info['command'] = "SELECT * FROM ".PREFIX."SystemConfiguration_Commerce WHERE uri=? LIMIT 1";
	$gbl_info['values'] = '[s] '.$_SERVER["HTTP_REFERER"];
	$System = $linkDB->prepare($gbl_info['command']);
	$System->bind_param('s', $_SERVER["HTTP_REFERER"]);
	$System->execute();
	$System = $System->get_result();
	if ($System->num_rows === 0) {			# if there are no results, then...
		echo "<req>\n";
		echo "	<f>\n";
		echo "		<msg>No pairing record was found.</msg>\n";
		echo "	</f>\n";
		echo "</req>\n";
# LEFT OFF - update all the sendMail() calls to have the proper locations and variables
		sendMail($gbl_emailHackers,$gbl_nameHackers,$gbl_emailNoReply,SCRIPT.' script','*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nOur Error: The commerce record can not be found in the database. WARNING: This may be a cracking attempt!<br />\nSQL Error: ".mysql_errno().": ".mysql_error()."<br />\n<br />\nSQL Query: SELECT * FROM ".PREFIX."SystemConfiguration_Commerce WHERE uri='".$_SERVER["HTTP_REFERER"])."' LIMIT 1<br />\n<br />\nVar Dump:<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		exit();
	}
	$system = $System->fetch_assoc();

	# 2. Store the decryption key and decrypt the SID stored in the DB
	$salt = file_get_contents('../../../denaccess');
	$sid = Cipher::decrypt($system['sid'], $salt);

	# 3. Check 'sid' values for authentication
	if ($_POST['sid'] == '' || is_null($_POST['sid'])) {
		echo "<req>\n";
		echo "	<f>\n";
		echo "		<msg>The SID can not be blank.</msg>\n";
		echo "	</f>\n";
		echo "</req>\n";
		exit();
	} else if ($sid != $_POST['sid']) {
		echo "<req>\n";
		echo "	<f>\n";
		echo "		<msg>The SIDs do not match.</msg>\n";
		echo "	</f>\n";
		echo "</req>\n";
		sendMail($gbl_emailCrackers,$gbl_nameCrackers,$gbl_emailNoReply,SCRIPT.' script','*** Possible Cracking Attempt ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nOur Error: The commerce SID's do not match.<br />\n<br />\nVar Dump:<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		exit();
	}

	# 4. Generate a random SID and encrypt it for the above found account
	$NEW = genRandom();
	$SID = Cipher::encrypt($NEW, $salt);

	# 5. Update the database with the next randomly-generated, single-use SID
	$gbl_errs['error'] = "Failed to update the 'Commerce SID' value in the database when pairing webBooks.";
	$gbl_info['command'] = "UPDATE ".PREFIX."SystemConfiguration_Commerce SET sid='".$SID."' WHERE id='".$system['id']."'";
	$gbl_info['values'] = 'None';
	$stmt = $linkDB->query($gbl_info['command']);


	# if we've made it down here, we are ready to start pairing the software
	echo "<req>\n";
	echo "	<s>\n";
	echo "		<msg>The pairing was successful!</msg>\n";
	echo "		<data sid='".$NEW."' />\n";
	echo "	</s>\n";
	echo "</req>\n";
	exit();							# exit no matter if success or failure at this point


# this is the *RECEIPIENT* side to validate the exchanged commerce info (after the code below this 'if' has executed on the *SENDER* side)
} else if ($_POST['action'] == 'validate' && $_POST['target'] == 'exchange') {
	# validate all submitted data
	if (! validate($_SERVER["HTTP_REFERER"],128,'[^a-zA-Z0-9_\.:\/\-]')) { exit(); }
	if (! validate($_POST['md5'],40,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['new'],40,'[^a-zA-Z0-9]')) { exit(); }
# LEFT OFF - need to figure out what characters this contains so it can be validated (which is identified below)
#$_POST['fid']

	# no referer, no business!
	if ($_SERVER["HTTP_REFERER"] == '' || is_null($_SERVER["HTTP_REFERER"])) {
		sendMail($gbl_emailCrackers,$gbl_nameCrackers,$gbl_emailNoReply,SCRIPT.' script','*** Possible Cracking Attempt ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nOur Error: No referrer value was passed while attempting to exchange commerce data.<br />\n<br />\nVar Dump:<br />\n".$RAW."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		echo 'FAIL';
		exit();
	}

	# check that the temp commerce directory exists
	if (! file_exists('../temp/commerce')) {
		sendMail($gbl_emailHackers,$gbl_nameHackers,$gbl_emailNoReply,SCRIPT.' script','*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\n<br />\nOur Error: The 'temp/commerce' directory does not exist for commerce information validation.<br />\n<br />\nTarget Dir: ../../temp/commerce<br />\nCurrent Dir: ".getcwd()."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		echo 'FAIL';
		exit();
	}

	# check that the validation file exists and if not, this could be a cracking attempt!
	if (! file_exists('../temp/commerce/'.$_POST['fid'])) {
		sendMail($gbl_emailCrackers,$gbl_nameCrackers,$gbl_emailNoReply,SCRIPT.' script','*** Possible Cracking Attempt ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\n<br />\nOur Error: The temp commerce file '".$_POST['fid']."' does not exist.<br />\n<br />\nVar Dump:<br />\n".print_r($_POST, true)."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		echo 'FAIL';
		exit();
	}

	# validate that the XML sent matches the MD5 hash that was originally passed
	$MD5 = file_get_contents('../temp/commerce/'.$_POST['fid']);
	if ($MD5 != $_POST['md5']) {
		sendMail($gbl_emailCrackers,$gbl_nameCrackers,$gbl_emailNoReply,SCRIPT.' script','*** Possible Cracking Attempt ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\n<br />\nOur Error: The temp commerce file '".$_POST['fid']."' does not contain the same MD5 hash of the XML originally sent.<br />\n<br />\nVar Dump:<br />\n".print_r($_POST, true)."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		echo 'FAIL';
		exit();
	}

	# encrypt and update the stored single-use SID for the account
	$salt = file_get_contents('../../../denaccess');
	$SID = Cipher::encrypt($_POST['new'], $salt);

	$gbl_errs['error'] = "Failed to update the 'Commerce SID' value in the database when pairing webBooks.";
	$gbl_info['command'] = "UPDATE ".PREFIX."SystemConfiguration_Commerce SET sid='".$SID."' WHERE uri=?";
	$gbl_info['values'] = '[s] '.$_SERVER["HTTP_REFERER"];
	$stmt = $linkDB->prepare($gbl_info['command']);
	$stmt->bind_param('s', $_SERVER["HTTP_REFERER"]);
	$stmt->execute();
	if ($stmt === false) {
		sendMail($gbl_emailHackers,$gbl_nameHackers,$gbl_emailNoReply,SCRIPT.' script','*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nOur Error: The SID could not be updated in the database for the sender.<br />\nSQL Error: ".mysql_errno().": ".mysql_error()."<br />\n<br />\nSQL Query: UPDATE ".PREFIX."SystemConfiguration_Commerce SET sid=\"".$SID."\" WHERE uri='".safeXML($_SERVER["HTTP_REFERER"]."'<br />\n<br />\nVar Dump:<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		echo 'FAIL';
		exit();
	}

	echo 'ACK';				# return an (ACK)nowledgement
	exit();
}




#---------- For any communication below, we are exchanging commerce info as the *RECEIVER* ----------#




# validate all submitted data
if (! validate($_SERVER["HTTP_REFERER"],128,'[^a-zA-Z0-9_\.:\/\-]')) { exit(); }




// STEP 1: process the raw POST data coming in from the unknown outside source

$RAW = trim(file_get_contents('php://input'));				# this gets the XML sent from the modules' curl call (e.g. processReorders_Inventory())
$XML = simplexml_load_string($RAW);

# no referer, no business!
if ($_SERVER["HTTP_REFERER"] == '' || is_null($_SERVER["HTTP_REFERER"])) {
	sendMail($gbl_emailCrackers,$gbl_nameCrackers,$gbl_emailNoReply,SCRIPT.' script','*** Possible Cracking Attempt ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nOur Error: No referrer value was passed while attempting to exchange commerce data.<br />\n<br />\nVar Dump:<br />\n".$RAW."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
	exit();
}

# connect to the SQL server
if (! connect2DB(DBHOST,DBNAME,DBUNRW,DBPWRW,'oop','a')) { exit(); }	# NOTE: we use the 'a' value to prevent any reply to the caller

# obtain the paired webBooks DB record via the REFERER
$gbl_errs['error'] = "Failed to find the 'Commerce URI' value in the database when exchanging commerce data.";
$gbl_info['command'] = "SELECT * FROM ".PREFIX."SystemConfiguration_Commerce WHERE uri=? LIMIT 1";
$gbl_info['values'] = '[s] '.$_SERVER["HTTP_REFERER"];
$Commerce = $linkDB->prepare($gbl_info['command']);
$Commerce->bind_param('s', $_SERVER["HTTP_REFERER"]);
$Commerce->execute();
$Commerce = $Commerce->get_result();
if ($Commerce->num_rows === 0) {					# if the commerce record can't be found, then...
	sendMail($gbl_emailHackers,$gbl_nameHackers,$gbl_emailNoReply,SCRIPT.' script','*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nOur Error: The commerce record can not be found in the database. WARNING: This may be a cracking attempt!<br />\nSQL Error: ".mysql_errno().": ".mysql_error()."<br />\n<br />\nSQL Query: SELECT id,uri,sid FROM ".PREFIX."SystemConfiguration_Commerce WHERE uri='".safeXML($_SERVER["HTTP_REFERER"])."' LIMIT 1<br />\n<br />\nVar Dump:<br />\n".$RAW."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
	exit();
}
$commerce = $Commerce->fetch_assoc();

# decrypt the stored SID for the found account
$salt = file_get_contents('../../../denaccess');
$SID = Cipher::decrypt($commerce['sid'], $salt);

# validate that the sent SID matches the stored SID
$validate = $XML->validate[0];
if ($validate['sid'] != $SID) {
	sendMail($gbl_emailCrackers,$gbl_nameCrackers,$gbl_emailNoReply,SCRIPT.' script','*** Possible Cracking Attempt ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nOur Error: The incoming SID does not match the SID in our database!<br />\n<br />\nVar Dump:<br />\n".$RAW."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
	exit();
}

# send a success response to the sender			V2 - get this working to prevent a hung connection if processing below takes a long time
#header('Content-Type: text/html; charset=utf-8');
#header("Connection: close\r\n");
#header("Content-Encoding: none\r\n");

#ob_end_clean();
#ignore_user_abort(true);	// just to be safe

#ob_start();
#echo "SUCCESS";
#$contentLength = ob_get_length();
#header("Content-Length: ".$contentLength);
#header("Content-Length: ".strlen('SUCCESS'));

// flush all output
#ob_end_flush();
#ob_flush();
#flush();

#file_put_contents('debug.txt', "flushed curl contents\n", FILE_APPEND);

// close current session
#session_write_close();
#file_put_contents('debug.txt', "call session_write_close()\n", FILE_APPEND);
#fastcgi_finish_request();				NOTE: this line stops script execution
#file_put_contents('debug.txt', "called fastcgi_finish_request()\n", FILE_APPEND);
#if (session_id()) { session_write_close(); }

#file_put_contents('debug.txt', "terminated the init curl\n", FILE_APPEND);

#sleep(10);

#file_put_contents('debug.txt', "processing second stage...\n", FILE_APPEND);




// STEP 2: send validation back to sender that all the data has been received

$NEW = genRandom();				# generate the new (single-use) SID for future commerce exchanges
$MD5 = md5($XML->COMMERCE->asXML());		# generate the MD5 hash for ONLY the <COMMERCE> section of the XML
$ADD = "action=validate&target=exchange&sid=".$SID."&new=".$NEW."&md5=".$MD5."&fid=".$validate['fid'];

$ch = curl_init($commerce['uri']);
curl_setopt($ch, CURLOPT_REFERER, $gbl_uriProject."code/commerce.php");
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);	# makes it so the call to curl_exec returns the HTML from the web page as a $
curl_setopt($ch, CURLOPT_POSTFIELDS, $ADD);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

if (! ($ACK = curl_exec($ch))) {
	sendMail($gbl_emailHackers,$gbl_nameHackers,$gbl_emailNoReply,SCRIPT.' script','*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\n<br />\nOur Error: cURL encountered an error during its execution.<br />\nExec Error: ".print_r(error_get_last(), true)."<br />\n<br />\n".trigger_error(curl_error($ch))."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
	curl_close($ch);
	exit();
}
curl_close($ch);




// STEP 3: check the returned acknowledgement and process the XML from above if all is ok

if ($ACK != 'ACK') {				# if we got any other response aside from 'ACK', then there was a problem, so do NOT process any of the received data
	if ($ACK != 'FAIL')			# if it was something other than 'FAIL', then we might be having a cracking attempt!
		{ sendMail($gbl_emailCrackers,$gbl_nameCrackers,$gbl_emailNoReply,SCRIPT.' script','*** Possible Cracking Attempt ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nOur Error: The returned response (".$ACK.") from the sender is invalid!<br />\n<br />\nVar Dump:<br />\n".$RAW."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>"); }
	exit();					# at this point, we need to exit no matter what!
}

# update the database with the next single-use SID
$SID = Cipher::encrypt($NEW, $salt);

$gbl_errs['error'] = "Failed to update the 'Commerce SID' value in the database when exchanging commerce data.";
$gbl_info['command'] = "UPDATE ".PREFIX."SystemConfiguration_Commerce SET sid='".$SID."' WHERE id='".$commerce['id']."'";
$gbl_info['values'] = 'None';
$gbl_info['prompt'] = 'off';
$stmt = $linkDB->query($gbl_info['command']);

# go through each installed module to see if any XML was sent for it
$gbl_errs['error'] = "Failed to find the 'Module Names' in the database when exchanging commerce data.";
$gbl_info['command'] = "SELECT name FROM ".PREFIX."SystemConfiguration_Modules";
$gbl_info['values'] = 'None';
$gbl_info['prompt'] = 'off';
$Module = $linkDB->query($gbl_info['command']);
if ($Module->num_rows === 0) {			# if the modules can't be found, then...
	sendMail($gbl_emailHackers,$gbl_nameHackers,$gbl_emailNoReply,SCRIPT.' script','*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nOur Error: No modules appear to be installed which prevents commerce exchanges.<br />\nSQL Error: ".mysql_errno().": ".mysql_error()."<br />\n<br />\nSQL Query: SELECT name FROM ".PREFIX."SystemConfiguration_Modules<br />\n<br />\nVar Dump:<br />\n".$RAW."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
	exit();
}
while ($module = $Module->fetch_assoc()) {
	$MODULE = str_replace(' ', '_', strtolower($module['name']));
	if (empty($XML->COMMERCE->$MODULE)) { continue; }
	$COMMERCE = $XML->COMMERCE[0];		# store the <COMMERCE callback='...'> tag

	# there aren't any commerce routines to perform on the 3 default modules
	if ($MODULE == 'business_configuration' || $MODULE == 'employees' || $MODULE == 'system_configuration') { continue; }

	# skip any modules that don't have a file (e.g. modules under development -OR- if there are problems with a module install)
	if (! file_exists($MODULE.'.php')) { continue; }

	# used to have the ability to call the maintenance function of the module
	require_once($MODULE.'.php');

	# if the file exists, make sure that the commerce function exists
	if (! function_exists($MODULE.'_commerce')) { continue; }
	call_user_func($MODULE.'_commerce',$COMMERCE['callback'],$XML->COMMERCE->$MODULE->asXML(),$commerce);
}


#file_put_contents('debug.txt', 'down here', FILE_APPEND);


?>
