<?php
# _Database.php
#
# Created	2009/10/08 by Dave Henderson (support@cliquesoft.org)
# Updated	2025/09/04 by Dave Henderson (support@cliquesoft.org)
#
# Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
# device, this software is licensed under the Cliquesoft Public License (CPLv2)
# as found on the Cliquesoft website at www.cliquesoft.org.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.
#
# ADDITIONAL
# http://stackoverflow.com/questions/6215789/build-condition-comparison-for-if-statement
# https://stackoverflow.com/questions/57153848/converting-from-mysqli-to-prepared-statements#57154741
# https://phpdelusions.net/articles/error_reporting




# Usage Syntax:
#	$sql = "SELECT * FROM contacts WHERE contacts.id=? AND contacts.name=? ORDER BY id DESC";
#	$stmt = $this->mysqli->prepare($sql);			# create a prepared statement
#	dynamicBind_Param($stmt, 'is', array('4','Dave'));	# dynamically build the 'bind_param' function call, and then execute it
#	$data = $stmt->execute();
#	$Data = $data->get_result()->fetch_assoc();
function dynamicBind_Param($LinkDB=null, $sTypes=null, $aValues=null) {
# Used to dynamically build and execute bind_param() statements for mysqli
	if (! $LinkDB || ! $sTypes || ! $aValues) { return 1; }	# exit if any of the function parameters are not passed

	$bind_params[] = &$sTypes;				# store (by reference) the 'sTypes' string as the first value in the 'bind_params' array
	for ($i=0; $i<count($aValues); $i++) { $bind_params[] = &$aValues[$i]; }		# store (by reference) each 'values' array value in the 'bind_params' array
	return call_user_func_array(array($LinkDB, 'bind_param'), $bind_params);		# call the mySQLi bind_param() function and return its return value
}




# Usage syntax:
#	if (! checkPermission('add')) { echo "failure"; }	this checks for 'add' permission and outputs in the default XML format
#	if (checkPermission('write','t')) {echo "success";}	this checks for 'write' permission and outputs in text format
function checkPermission($sType,$sErrors='x') {	# https://stackoverflow.com/questions/9166914/using-default-arguments-in-a-function
# checks that the user account has permission to perform the passed action within the module
# NOTE:		this MUST be called AFTER loadUser()!
# sValue	the type of permission to check: read, write, add, del
# sErrors	defines how errors should be handled; valid values: (a)rray, (h)tml, (t)ext, (x)ml, blank value disables output
	global $__sInfo,$__sMsgs,$__sUser,$_LinkDB;

	switch($sType) {
	   case 'read': $sExtended = "open (read)"; break;
	   case 'write': $sExtended = "save (write)"; break;
	   case 'add': $sExtended = "append (add)"; break;
	   case 'del': $sExtended = "remove (delete)"; break;
	}

# LEFT OFF - check that the software is not in maintenance mode

	# check that the submitting account has permission to access the module
	$__sInfo['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission.";
	$__sInfo['command'] = "SELECT id FROM ".DB_PRFX."ApplicationSettings_Modules WHERE name='".MODULE."' LIMIT 1";
	$__sInfo['values'] = 'None';
	$Module = $_LinkDB->query($__sInfo['command']);
	$module = $Module->fetch_assoc();

	$__sInfo['error'] = "Failed to find the Employee record in the database when checking for access permission.";
	$__sInfo['command'] = "SELECT `".$sType."` FROM ".DB_PRFX."Employees_Access WHERE employeeID='".$__sUser['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$__sInfo['values'] = 'None';
	$Access = $_LinkDB->query($__sInfo['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		if ($sErrors == 'a') { $__sMsgs[] = "Your account does not have sufficient priviledges to ".$sExtended." data in this module."; }
		else if ($sErrors == 'h') { echo "<div class='fail'>Your account does not have sufficient priviledges to ".$sExtended." data in this module.</div>\n"; }
		else if ($sErrors == 't') { echo "Your account does not have sufficient priviledges to ".$sExtended." data in this module.\n"; }
		else if ($sErrors == 'x') { echo "<f><msg>Your account does not have sufficient priviledges to ".$sExtended." data in this module.</msg></f>"; }
		return false;
	}						# otherwise the account MAY have permission to access, so...
	$access = $Access->fetch_assoc();		# load the access information for the account
	if ($access[$sType] == 0) {			# if the account does NOT have proper access for this module, then...
		if ($sErrors == 'a') { $__sMsgs[] = "Your account does not have sufficient priviledges to ".$sExtended." data in this module."; }
		else if ($sErrors == 'h') { echo "<div class='fail'>Your account does not have sufficient priviledges to ".$sExtended." data in this module.</div>\n"; }
		else if ($sErrors == 't') { echo "Your account does not have sufficient priviledges to ".$sExtended." data in this module.\n"; }
		else if ($sErrors == 'x') { echo "<f><msg>Your account does not have sufficient priviledges to ".$sExtended." data in this module.</msg></f>"; }
		return false;
	}
	return true;
}




# Usage syntax:
#	if (connect2DB('sql.mydomain.com','db_accounts','db_user','top_secret')) { echo "success"; }
function connect2DB($sServer,$sDB,$sUser,$sPass,$sType='oop',$sErrors='x') {
# Connects to the requested database for further interaction via this script
# sServer	the FQDN of the MySQL server to connect to
# sDB		the name of the MySQL database to open
# sUser		the name of the user account with sufficient priviledges to interact with the DB
# sPass		the password for the sUser
# sType		the type of connection: pro(cedural), oop (object oriented programming)
# sErrors	defines how errors should be handled; valid values: (a)rray, (h)tml, (t)ext, (x)ml, blank value disables output
	global $_LinkDB,$_bDebug,$__sInfo,$__sMsgs;

	if ($sServer == '' || $sUser == '' || $sPass == '') {
		# NOTE: we didn't add a sendMail call here since this should ONLY be shown during development
		if ($sErrors == 'a') { $__sMsgs[] = "There was an error processing your request and our staff has been notified.  Please try again in a few minutes."; return 0; }
		else if ($sErrors == 'h') { echo "<div class='divFail'>There was an error processing your request and our staff has been notified.  Please try again in a few minutes.</div>\n"; return 0; }
		else if ($sErrors == 't') { echo "There was an error processing your request and our staff has been notified.  Please try again in a few minutes.\n"; return 0; }
		else if ($sErrors == 'x') { echo "<f><msg>There was an error processing your request and our staff has been notified.  Please try again in a few minutes.</msg></f>"; return 0; }
	}
	if ($sErrors == 'a' && $_bDebug) { $__sMsgs[] = "DEBUG: All SQL Server authentication information has been passed."; }
	else if ($sErrors == 'h' && $_bDebug) { echo "<div class='divInfo'>DEBUG: All SQL Server authentication information has been passed.</div>\n"; }
	else if ($sErrors == 't' && $_bDebug) { echo "DEBUG: All SQL Server authentication information has been passed.\n"; }
	else if ($sErrors == 'x' && $_bDebug) { echo "<i><msg>DEBUG: All SQL Server authentication information has been passed.</msg></i>"; }

	if ($sDB == '') {
		# NOTE: we didn't add a sendMail call here since this should ONLY be shown during development
		if ($sErrors == 'a') { $__sMsgs[] = "There was an error processing your request and our staff has been notified.  Please try again in a few minutes."; return 0; }
		else if ($sErrors == 'h') { echo "<div class='divFail'>There was an error processing your request and our staff has been notified.  Please try again in a few minutes.</div>\n"; return 0; }
		else if ($sErrors == 't') { echo "There was an error processing your request and our staff has been notified.  Please try again in a few minutes.\n"; return 0; }
		else if ($sErrors == 'x') { echo "<f><msg>There was an error processing your request and our staff has been notified.  Please try again in a few minutes.</msg></f>"; return 0; }
	}
	if ($sErrors == 'a' && $_bDebug) { $__sMsgs[] = "DEBUG: The SQL Server database name has been passed."; }
	else if ($sErrors == 'h' && $_bDebug) { echo "<div class='divInfo'>DEBUG: The SQL Server database name has been passed.</div>\n"; }
	else if ($sErrors == 't' && $_bDebug) { echo "DEBUG: The SQL Server database name has been passed.\n"; }
	else if ($sErrors == 'x' && $_bDebug) { echo "<i><msg>DEBUG: The SQL Server database name has been passed.</msg></i>"; }

	# WARNING: do NOT implement this because it will not allow silencing of errors -AT ALL- for better error handling!!!
	# mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);						# turn on MySQLi exception mode to report all errors	https://stackoverflow.com/questions/22662488/how-to-get-mysqli-error-information-in-different-environments-mysqli-fetch-as/22662582#22662582

	$__sInfo['error'] = "An error has occurred while attempting to connect to the DB server.";		# https://stackoverflow.com/questions/15707696/new-mysqli-vs-mysqli-connect
	if ($sType == 'pro') {
		$__sInfo['command'] = "mysqli_connect('p:".$sServer."','".$sUser."','****','".$sDB."')";
		$_LinkDB = mysqli_connect('p:'.$sServer,$sUser,$sPass,$sDB);					# need to pass: mysqli_connect_errno() & mysqli_connect_error() ?
	} else if ($sType == 'oop') {
		$__sInfo['command'] = "new mysqli('p:".$sServer."','".$sUser."','****','".$sDB."')";
		$_LinkDB = new mysqli('p:'.$sServer,$sUser,$sPass,$sDB);
	}

	if ($sErrors == 'a' && $_bDebug) { $__sMsgs[] = "DEBUG: Connection to the SQL Server has been made successfully!"; }
	else if ($sErrors == 'h' && $_bDebug) { echo "<div class='divInfo'>DEBUG: Connection to the SQL Server has been made successfully!</div>\n"; }
	else if ($sErrors == 't' && $_bDebug) { echo "DEBUG: Connection to the SQL Server has been made successfully!\n"; }
	else if ($sErrors == 'x' && $_bDebug) { echo "<i><msg>DEBUG: Connection to the SQL Server has been made successfully!</msg></i>"; }

	return 1;
}




function loadUser($nTimeout,&$Populate,$sDBPerm,$sFields,$sATable,$sMField,$sMValue,$sVField='',$sVValue='',$sChecks='',$sAlerts='',$sDBType='oop',$sErrors='x') {
# Before processing any request by the user, call this function to validate and load the user account information.
# Can optionally perform any additional account checks on a per call basis.
# NOTE: upon a successful call of this function, $strFill will become populated.
#	you need to also perform any value validation for the passed variables *before* calling this routine as it does *not* perform these checks!
# nTimeout	whether or not this function should check if the users session has timed out or not: 0=no, 1=yes
# Populate	if an array is passed, it will be populated with the DB search results; if a variable was passed, the dataset is returned
# sDBPerm	the permissions given to the SQL DB access; valid values: ro (readonly), rw (read/write)
# sFields	the fields (comma separated) to store in 'sPopulate' from the database; '*' for all fields in the row
# sATable	the name of the DB table containing the (a)uthentication field/column used to IDENTIFY and optionally VALIDATE the users (a)ccount
# sMField	the field/column name in 'sATable' used to (m)atch against (typically 'username' or 'id' when using this field as part of the authentication process)	WARNING: the values in this field/column MUST be unique!
# sMValue	the UNIQUE value of 'sMField' to isolate a SINGLE record	NOTE: a SQL value identifier will need to preceed the value [b,d,i,s] such as "'i|'.$_POST['id']" or "'s|'.$_POST['username']"
# sVField	the field/column name in 'strVTable' used to (v)alidate the user is who they say they are (typically 'sid'); blank value disables
# sVValue	the value to match within 'sVField' (to permit comma separated lists; 10 contiguous character minimum match)	NOTE: a SQL value identifier will need to be part of this value too like 'sMValue'
# sChecks	pipe separated checks to perform on the users account before exiting this function (e.g. "$gbl_user['disabled']<1|$gbl_user['status']=='active'|...")	NOTE: if using a variable in this value, you MUST escape the dollar sign ($)!
# sAlerts	pipe separated alerts that correspond to the associated 'strCheck' and get processed upon FAILURE (e.g. "Your account is disabled so...|Your account is not active so...|...")
#		NOTE: the above two options are ONLY available if 'Populate' is an array!
# sDBType	the type of connection: pro(cedural), oop (object oriented programming)
# sErrors	defines how errors should be handled; valid values: (a)rray, (h)tml, (t)ext, (x)ml, blank value disables output
	global $_LinkDB,$_bDebug,$__sInfo,$__sMsgs;
	$results = '';

	# If we need to check that the users session has not timed out, then...
	if ($nTimeout && $_nTimeout > 0) {
		$epoch = time();		# obtain the current time for comparison below
		if (! array_key_exists('Time', $_SESSION)) {			# if the session time doesn't exist, then...
			if ($sErrors == 'a') { $__sMsgs[] = "It appears that your session has timed out, please login again before continuing."; }
			else if ($sErrors == 'h') { echo "<div class='divFail'>It appears that your session has timed out, please login again before continuing.</div>\n"; }
			else if ($sErrors == 't') { echo "It appears that your session has timed out, please login again before continuing.\n"; }
			else if ($sErrors == 'x') { echo "<f><msg>It appears that your session has timed out, please login again before continuing.</msg></f>"; }
			return 0;
		} else if (($_SESSION['Time']+$_nTimeout) < $epoch) {		# if the users session has expired, then...
			if ($sErrors == 'a') { $__sMsgs[] = "For security reasons your session has expired, please log in again before continuing."; }
			else if ($sErrors == 'h') { echo "<div class='divFail'>For security reasons your session has expired, please log in again before continuing.</div>\n"; }
			else if ($sErrors == 't') { echo "For security reasons your session has expired, please log in again before continuing.\n"; }
			else if ($sErrors == 'x') { echo "<f><msg>For security reasons your session has expired, please log in again before continuing.</msg></f>"; }
			return 0;
		}

		if ($sErrors == 'a' && $_bDebug) { $__sMsgs[] = "DEBUG: Updating the users session time so they may continue to interact with our project."; }
		else if ($sErrors == 'h' && $_bDebug) { echo "<div class='divInfo'>DEBUG: Updating the users session time so they may continue to interact with our project.</div>\n"; }
		else if ($sErrors == 't' && $_bDebug) { echo "DEBUG: Updating the users session time so they may continue to interact with our project.\n"; }
		else if ($sErrors == 'x' && $_bDebug) { echo "<i><msg>DEBUG: Updating the users session time so they may continue to interact with our project.</msg></i>"; }
		$_SESSION['Time'] = $epoch;					# actually update the session time
	}

	# Now lets connect to the DB to process the user account
	if ($sDBPerm == 'ro') 
   		{ if (! connect2DB(DB_HOST,DB_NAME,DB_ROUN,DB_ROPW,$sDBType,$sErrors)) { return 0; } }		# the connect2DB has its own error handling so we don't need to do it here!
	else if ($sDBPerm == 'rw') 
		{ if (! connect2DB(DB_HOST,DB_NAME,DB_RWUN,DB_RWPW,$sDBType,$sErrors)) { return 0; } }

	# Time to locate and store the users account info into $__sUser (if the optional validation checks out)
	$__sInfo['error'] = "The users account could not be found or validated in the DB.";
	if ($sVField == '') {		# IF we do NOT need to validate the users account, then...		NOTE: added 'LIMIT 1' so the query doesn't continue searching after the account has been found
		$__sInfo['command'] = "SELECT ".$sFields." FROM ".$sATable." WHERE ".$sMField."=? LIMIT 1";
		$__sInfo['values'] = '['.substr($sMValue,0,1).'] '.substr($sMValue,2);
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param(substr($sMValue,0,1), $match);
		$match = substr($sMValue,2);					# WARNING: we can NOT pass the substr() call as the parameter value
	} else {			# OTHERWISE we need to validate as well as locate the users account, so...
		$__sInfo['command'] = "SELECT ".$sFields." FROM ".$sATable." WHERE ".$sMField."=? AND ".$sVField."=? LIMIT 1";
		$__sInfo['values'] = '['.substr($sMValue,0,1).'] '.substr($sMValue,2).', ['.substr($sVValue,0,1).'] '.substr($sVValue,2);

		if (substr($sVValue,2) == '') {					# to prevent someone using a "logged out" blank validation value to gain access
			$__sInfo['error'] = "A blank validation value was attempting to be passed.";

			echo "<f><msg>There was an error processing your request and our staff has been notified.  Please try again in a few minutes.</msg></f>";
			sendMail($_sSecurityEmail,$_sSecurityName,$_sAlertsEmail,$_sAlertsName,'!!! Possible Cracking Attempt !!!',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nOne of our users was attempting to interact with our site, but encountered an error which has been included below.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<u>Date:</u> ".gmdate("Y-m-d H:i:s",time())." GMT<br />\n<u>From:</u> ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\n<u>Project:</u> ".PROJECT."<br />\n<u>Module:</u> ".MODULE."<br />\n<u>Script:</u> ".SCRIPT."<br />\n<br />\n<u>DB Host:</u> ".DBHOST."<br />\n<u>DB Name:</u> ".DBNAME."<br />\n<u>DB Prefix:</u> ".DB_PRFX."<br />\n<br />\n<u>Name:</u> ".$__sInfo['name']."<br />\n<u>Contact:</u> ".$__sInfo['contact']."<br />\n<u>Other:</u> ".$__sInfo['other']."<br />\n<br />\n<u>Summary:</u> ".$__sInfo['error']."<br />\n<u>Command:</u> ".$__sInfo['command']."<br />\n<u>Values:</u> ".$__sInfo['values']."<br />\n<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br />\n<br />\nVar Dump:<br />\n</p>\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre><br />\n</td>\n<td>&nbsp;</td>\n</tr>\n</table>\n</body>\n</html>");
			exit();
		}

		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param(substr($sMValue,0,1).substr($sVValue,0,1), $match, $value);
		$match = substr($sMValue,2);					# WARNING: we can NOT pass the substr() call as the parameter values
		$value = substr($sVValue,2);
	}
	$stmt->execute();


  	if (gettype($Populate) == 'array') {					# if we need to return a populated array, then...
# UPDATED 2025/08/21
#		{ $Populate += $stmt->get_result()->fetch_assoc(); }		#   populate the array now (adding to it if values already exists)
		$stmt = $stmt->get_result();					#   store the result set
		if ($stmt->num_rows == 0) {					#   if there isn't anything in the result set, then...
			file_put_contents('../data/_logs/'.$_sLogScript, "---------- [ Account Validation Error ] ----------\nDate: ".gmdate("Y-m-d H:i:s",time())." GMT\nFrom: ".$_SERVER['REMOTE_ADDR']."\n\nProject: ".PROJECT."\nModule: ".MODULE."\nScript: ".SCRIPT."\n\nDB Host: ".DB_HOST."\nDB Name: ".DB_NAME."\nDB Prefix: ".DB_PRFX."\n\nMatching Field: ".$sMField."\nMatching Value: ".$sMValue."\nValidate Field: ".$sVField."\nValidate Value: ".$sVValue."\n\nSummary: The account did NOT validate using the passed values\nCommand: loadUser(...)\nFile: _Database.php\n\nVar Dump:\n\n_POST\n".print_r($_POST, true)."\n_GET\n".print_r($_GET, true)."\n\n\n\n", FILE_APPEND);
			sendMail($_sSecurityEmail,$_sSecurityName,$_sAlertsEmail,$_sAlertsName,'*** Account Validation Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nOne of our users was attempting to interact with our site, but encountered an error which has been included below.  Please investigate this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<u>Date:</u> ".gmdate("Y-m-d H:i:s",time())." GMT<br />\n<u>From:</u> ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\n<u>Project:</u> ".PROJECT."<br />\n<u>Module:</u> ".MODULE."<br />\n<u>Script:</u> ".SCRIPT."<br />\n<br />\n<u>DB Host:</u> ".DB_HOST."<br />\n<u>DB Name:</u> ".DB_NAME."<br />\n<u>DB Prefix:</u> ".DB_PRFX."<br />\n<br />\n<u>Matching Field:</u> ".$sMField."<br />\n<u>Matching Value:</u> ".$sMValue."<br />\n<u>Validate Field:</u> ".$sVField."<br />\n<u>Validate Value:</u> ".$sVValue."<br />\n<br />\n<u>Summary:</u> The account did NOT validate using the passed values<br />\n<u>Command:</u> loadUser(...)<br />\n<u>File:</u> _Database.php<br />\n<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br />\n<br />\nVar Dump:<br />\n</p>\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre><br />\n</td>\n<td>&nbsp;</td>\n</tr>\n</table>\n</body>\n</html>");
			return 0;
		}
		$Populate += $stmt->fetch_assoc();				#   if we made it here, we did get the record, so store the values
	} else { $Populate = $stmt->get_result(); return 1; }			# otherwise we only need the dataset returned, so...
		

	if (! $Populate) {							# IF no information was found in the above query, then...
		if ($sErrors == 'a') { $__sMsgs[] = "There was a problem processing your request. If you are attempting to login, please check your spelling and try again. Otherwise, try logging out and back in as your credentials may be stale."; }
		else if ($sErrors == 'h') { echo "<div class='divFail'>There was a problem processing your request. If you are attempting to login, please check your spelling and try again. Otherwise, try logging out and back in as your credentials may be stale.</div>\n"; }
		else if ($sErrors == 't') { echo "There was a problem processing your request. If you are attempting to login, please check your spelling and try again. Otherwise, try logging out and back in as your credentials may be stale.\n"; }
		else if ($sErrors == 'x') { echo "<f><msg>There was a problem processing your request. If you are attempting to\nlogin, please check your spelling and try again. Otherwise, try logging\nout and back in as your credentials may be stale.</msg></f>"; }

		$Populate=array();						# blank out the $__sUser array since we've errored out
		return 0;
	}

	# Now lets process any checks that have been passed
	if ($sChecks != '') {
		$checks = explode("|", $sChecks);				# separate the checks and corresponding alerts
		$alerts = explode("|", $sAlerts);

		for ($i=0; $i<count($checks); $i++) {
			if (eval("return ".$checks[$i].";")) {
				if ($sErrors == 'a') { $__sMsgs[] = $alerts[$i]; }
				else if ($sErrors == 'h') { echo "<div class='divFail'>".$alerts[$i]."</div>\n"; }
				else if ($sErrors == 't') { echo $alerts[$i]."\n"; }
				else if ($sErrors == 'x') { echo "<f><msg>".$alerts[$i]."</msg></f>"; }

				$Populate=array();				# blank out the $__sUser array since we've errored out
				return 0;
			}
		}
	}

	if ($sErrors == 'a' && $_bDebug) { $__sMsgs[] = "DEBUG: The SQL database request you made was successful!"; }
	else if ($sErrors == 'h' && $_bDebug) { echo "<div class='divInfo'>DEBUG: The SQL database request you made was successful!</div>\n"; }
	else if ($sErrors == 't' && $_bDebug) { echo "DEBUG: The SQL database request you made was successful!\n"; }
	else if ($sErrors == 'x' && $_bDebug) { echo "<i><msg>DEBUG: The SQL database request you made was successful!</msg></i>"; }
	return 1;								# return success if we've made it down here
}










# now exit this script if it is being sourced by another script (since we just needed access to the above functions)
if ((php_sapi_name() == 'cli' && count($argv) == 1)) { return true; }










# create the header for any processing below...
#if (($_POST['A'] == 'Load' && $_POST['T'] == 'Module') || ($_POST['A'] == 'render' && $_POST['T'] == 'layout')) {		# if we're loading the HTML/css, then...
#	header('Content-Type: text/html; charset=utf-8');
#} else {						# otherwise, we're interacting with the database and need to use XML
	header('Content-Type: text/xml; charset=utf-8');
	echo "<?xml version='1.0' encoding='UTF-8'?>\n\n";
#}










# -- Database API --

switch($_POST['A']) {								# Process the submitted (A)ction


    # --- AJAX ACTIONS ---

    case 'Load':
	# Loads the requested module/group record information (all tabs for modules)
	# https://stackoverflow.com/questions/4432062/matching-tables-name-with-show-tables
	# SELECT column_name FROM information_schema.columns WHERE table_name='wb_Employees' ORDER BY ordinal_position

	# validate all submitted data
	if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['sTabs'],128,'a-zA-Z|')) { exit(); }		# all the tabs in the module (pipe separated)
	if (! validate($_POST['sModule'],24,'a-zA-Z0-9_')) { exit(); }		# the module name to process
	if (! validate($_POST['sTab'],24,'a-zA-Z')) { exit(); }			# the tab name to process
	if (! validate($_POST['sGroup'],24,'a-zA-Z')) { exit(); }		# the group name within the tab to load
	if (! validate($_POST['sType'],7,'{List|Record}')) { exit(); }		# determines if we're loading a History tab, Module Record (includes multiple tabs), or a Group (e.g. Contacts)
	if (! validate($_POST['id'],20,'0-9')) { exit(); }			# specifies the module's history/record -OR- group id to load

	# load the users account info in the global variable
	if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

	# check that the submitting account has permission to access the module
	if (! checkPermission('read')) { exit(); }


	# if we've made it here, the user is authorized to interact

	if ($_POST['sType'] == 'List') {
		# 1. Obtain the associated group items
		$__sInfo['error'] = "Failed to find the items associated with the group in the database.";
		$__sInfo['command'] = "SELECT id FROM ".DB_PRFX.$_POST['sModule']."_".$_POST['sTab'].($_POST['sTab'] != $_POST['sGroup'] ? '_'.$_POST['sGroup'] : '')." WHERE id=?";
		$__sInfo['values'] = '[i] '.$_POST['id'];
		$Items = $_LinkDB->prepare($__sInfo['command']);
		$Items->bind_param('i', $_POST['id']);
		$Items->execute();
		$Items = $Items->get_result();

		# now write the XML to the clients browser
		echo "<s>\n";
		echo "   <xml>\n";
		echo "	<group>\n";
		echo "		<field sName='".$_POST['sGroup']."'>\n";
		while ($item = $Items->fetch_assoc()) {
# LEFT OFF - we need another parameter (maybe Tag) to specify the text
#	how the hell am i going to format the table? this may have to be done in javascript using data-column1='sName' data-column2='sExpires' in the <th> headers so that I would know what to do elsewhere ...
			echo "	   <item sValue=\"".$item['id']."\" sText=\"".$group['sName']."\" />\n";
		}
		echo "		</field>\n";
		echo "	</group>\n";
		echo "   </xml>\n";
		echo "</s>";
		exit();

	} else {
# LEFT OFF - update database table names:
#	MODULE_GROUP					(this is only applicable to 'Application')
#	MODULE_TAB[_GROUP]				(e.g. Inventory_General_Discounts)	use the column names to determine which tables are involved with the relationship (look for 'fk*')
#	MODULE-RELATIONAL				(e.g. QuotesAndInvoices-Serials)	use the column names to determine which tables are involved with the relationship (look for 'fk*')

		$s_Tabs = explode('||', $_POST['sTabs']);			# store all the tabs used in the module
		$sTable = DB_PRFX.$_POST['sModule'].'_'.$_POST['sTab'].($_POST['sTab'] != $_POST['sGroup'] ? '_'.$_POST['sGroup'] : '');

		# 1. Obtain the module id
		$__sInfo['error'] = "Failed to find the module id in the database.";
		$__sInfo['command'] = "SELECT id FROM ".DB_PRFX."ApplicationSettings_Modules WHERE name='".$_POST['sModule']."'";
		$__sInfo['values'] = 'None';
		$Modules = $_LinkDB->query($__sInfo['command']);
		$module = $Modules->get_result()->fetch_assoc();

# UNUSED 2025/09/26
#		# 2. Obtain all the modules tables
#		$__sInfo['error'] = "Failed to find the modules database tables in the database.";
#		$__sInfo['command'] = "SELECT table_name AS sName FROM information_schema.tables WHERE table_name LIKE '".DB_PRFX.$_POST['sModule']."\\_%' ORDER BY table_schema";
#		$__sInfo['values'] = 'None';
#		$Tables = $_LinkDB->query($__sInfo['command']);
#		$Tables = $Tables->get_result();

		# 3. Check if there are any 'text' columns in the 'primary' table
		$__sInfo['error'] = "Failed to find the table column types with the requested record in the database.";
		$__sInfo['command'] = "SELECT column_name FROM information_schema.columns WHERE table_name='".$sTable."' AND COLUMN_TYPE='text' ORDER BY ordinal_position";
		$__sInfo['values'] = 'None';
		$Texts = $_LinkDB->query($__sInfo['command']);
		$Texts = $Texts->get_result();
		if ($Texts->num_rows === false || $Texts->num_rows === 0) {	# if no records are found, then...
			$text='';
		} else {							# otherwise store the column name (there should only be 1)
			$Texts = $Texts->fetch_assoc();
			foreach ($Texts as $key => $value) { $text = $value; }
		}

# left off - go through the employees table to see if the below code works; then do so for the notes and adapt accordingly
# wb_Employees_General			<-- this would be stored as the 'record retrieval' table via the passed parameters
# wb_Employees_General_Access
# wb_Employees_General_Donation
# wb_Employees_Timesheets
		$XML =	"<s>\n" .
			"   <xml>\n";
		foreach ($s_Tabs = $sTab) {
# General:General_CustomerAccounts:Application_Contacts:Application_Discounts:Application_FreightAccounts:Application_Associated
			$s_Groups = explode(':', $sTab);					# store all the groups used in the module	NOTE: these values are full database table names (e.g. Contacts_Application)
			$sTab = strstr($sTab,':',true);						# removes all the groups from the string to isolate just the iterated tab name

			switch(strtolower($sTab)) {
				case '?': continue;				# we don't process the 'Help' tab here
					break;

				case 'list':
					break;

				case 'data':
					# Obtain all the record data files
					$__sInfo['error'] = "Failed to find the modules database tables in the database.";
					$__sInfo['command'] = "SELECT id,name AS sName,filename AS sFilename FROM ".DB_PRFX."Application_Data WHERE table=".$module['id']." AND rowID=".$__sUser['id'];
					$__sInfo['values'] = 'None';
					$Data = $_LinkDB->query($__sInfo['command']);
					$Data = $Data->get_result();

					$XML .=	"\t<tab name='".$sTab."'>\n" .
						"\t\t<field name='oData_".$_POST['sModule']."'>\n";
					while ($data = $Data->fetch_assoc())
						{ $XML .= "\t\t\t<item sName=\"".$data['sName']."\" sValue=\"".safeXML($data['sFilename'])."\" />"; }
					$XML .=	"\t\t</field>\n" .
						"\t</tab>\n";
					break;

				case 'notes':
					# Obtain all the record notes
					$__sInfo['error'] = "Failed to find the modules database tables in the database.";
					$__sInfo['command'] = "
						SELECT
							tblNotes.id,tblNotes.created AS eCreated,tblEmployees.name AS sName,tblNotes.note AS sNote
						FROM
							".DB_PRFX."Application_Notes tblNotes
						LEFT JOIN
							".DB_PRFX."Employees_General tblEmployees ON tblNotes.creatorID=tblEmployees.id
						WHERE
							tblNotes.type=".$module['id']." AND (tblNotes.access='everyone' || tblNotes.access=".$__sUser['id'].")";
					$__sInfo['values'] = 'None';
					$Notes = $_LinkDB->query($__sInfo['command']);
					$Notes = $Notes->get_result();

					$XML .=	"\t<tab name='".$sTab."'>\n" .
						"\t\t<field name='oNotes_".$_POST['sModule']."'>\n";
					while ($note = $Notes->fetch_assoc())
						{ $XML .= "\t\t\t<item sColumn1='".$note['eCreated']."' sColumn2=\"".$note['sName']."\">".safeXML($note['sNote'])."</item>"; }
					$XML .=	"\t\t</field>\n" .
						"\t</tab>\n";
					break;

				case 'specs':
					# Obtain all the record specs
					$__sInfo['error'] = "Failed to find the modules database tables in the database.";
					$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."Application_Specs WHERE table=".$module['id']." AND rowID=?";
					$__sInfo['values'] = '[i] '.$_POST['id'];
					$Specs = $_LinkDB->prepare($__sInfo['command']);
					$Specs->bind_param('i', $_POST['id']);
					$Specs->execute();
					$Specs = $Specs->get_result();

					$XML .=	"\t<tab name='".$sTab."'>\n" .
						"\t\t<field name='oSpecs_".$_POST['sModule']."'>\n";
					while ($spec = $Specs->fetch_assoc()) {
						for ($i=1; $i<21; $i++)
							{ $XML .= "\t\t\t<item sName=\"".$spec['title'+$i]."\" sValue=\"".safeXML($spec['value'+$i])."\" sTag=\"".$spec['type']."\" />"; }
					}
					$XML .=	"\t\t</field>\n" .
						"\t</tab>\n";
					break;

				default:
					$XML .=	"\t<tab name='".$sTab."'>\n";
					for ($i=0; $i<count($s_Groups); $i++) {
						$sTable = strstr($s_Group[$i],'=',true);
						$sText = substr($s_Group[$i], strpos($s_Group[$i],'=')+1);

						# Obtain all the fields within the group
						$__sInfo['error'] = "Failed to find the fields associated with the requested record in the database.";
						$__sInfo['command'] = "SELECT * FROM ".DB_PRFX.$sTable." WHERE id=? LIMIT 1";
						$__sInfo['values'] = '[i] '.$_POST['id'];
						$Fields = $_LinkDB->prepare($__sInfo['command']);
						$Fields->bind_param('i', $_POST['id']);
						$Fields->execute();
						$field = $Fields->get_result()->fetch_assoc();

						if ($_POST['sModule'].'_'.$sTab == $sTable) {			# if the module and group names are the same (e.g. CustomerAccounts_General), then the table belongs to the module so...
							foreach ($field as $key => $value)			#   store each field directly
								{ $XML .= "\t\t<field sName=\"".$key."\" sValue=\"".safeXML($value)."\" />\n"; }
						} else {							# otherwise it is a non-native group, so return its contents as <item>'s
# LEFT OFF - also look for fkMODULE/ftMODULE columns and add that to the search criteria above; this is for tables like Application_Associated
							$XML .= "\t\t<field sName=\"".$sTable."\">\n";
							foreach ($field as $key => $value)
								{ $XML .= "\t\t\t<item sText=\"".safeXML($field[$sText])."\" sValue=\"".$field['id']."\" />\n"; }
							$XML .= "\t\t</field>\n";
						}
					}
					$XML .=	"\t</tab>\n";
				}
			}
		}
		echo $XML;
		exit();
	}

# DEV NOTE: this needs to be returned from php like:
#	<tab name='General'>
#		<field sName='sName' sValue='dave' />						<-- this way we know to document.getElementById('').value = ?
#		<field sName='sAddressLine1' sValue='1234 road name' />				<-- this way we know to document.getElementById('').value = ?
#		<field sName='sGender' sValue='male' />						<-- this way we know to Listbox('SelectOption')
#		<field sName='nSickLeave' sValue='48 hours' />					<-- this way we know to document.getElementById('').innerHTML = ?
#		<field sName='Application_Contacts'>						<-- this way we know to cycle calling Listbox('AddOption')
#			<item sText='Joan Jet' sValue='123-456-7890' sOptgroup='' sClasses='' sTag='' />
#			<item sText='Paul Simon' sValue='234-567-8901' sOptgroup='' sClasses='' sTag='selected' />	<-- the 'sTag' indicates that this <option> needs to be selected
#			...
#		<field>
#		<field sName='oHistory' sSeparate='sColumn5' sDescription='sColumn2'>			<-- this way we know all the columns to add to the table
#												    NOTES:
#													- the 'sSeparate' tag means that column5 needs to be on its own line (see the assetmanagement history loading as an example for this)
#													- the 'sDescription' tag means that one of the inner columns (instead of the last column) needs to pull it's text from the item.firstChild.data (e.g. the description for an invoice item); if this is blank, but sSeparate isn't, then the last column can use .firstChild.data
#			<item sColumn1='1234' sClasses1='col1 center' sColumn2='check' sClasses2='col2 left' sColumn3='2025/04/05' sClasses3='col3 right' sColumn4='$400.00' sClasses4='col4' sClasses5='col5 justify'>
#				this is the description of the item; NOTE: that this is sColumn5 (but can also be the column specified by 'sDescription' as to which column this information needs to be placed in)
#			</item>
#			...
#		<field>
#	</tab>
#	<tab name='Notes'>
#		...
#	</tab>
#	...
# -OR-
#	<group>
#		<field sName='oContacts'>							<-- this must be present so the javascript works correctly between the two types
#			<item sText='Bob Lennon' sValue='1234' />
#			<item sText='Charles Smith' sValue='2345' />
#		</field>
#	</group>
# -OR-
#	<group>											<-- this is the name of the table (e.g. oList_WorkOrders)
#		<field sName='oList' sSeparate='sColumn5' sDescription='sColumn2'>		<-- this way we know all the columns to add to the table
#												    NOTES:
#													- the 'sSeparate' tag means that column5 needs to be on its own line (see the assetmanagement history loading as an example for this)
#													- the 'sDescription' tag means that one of the inner columns (instead of the last column) needs to pull it's text from the item.firstChild.data (e.g. the description for an invoice item); if this is blank, but sSeparate isn't, then the last column can use .firstChild.data
#			<item sColumn1='1234' sClasses1='col1 center' sColumn2='check' sClasses2='col2 left' sColumn3='2025/04/05' sClasses3='col3 right' sColumn4='$400.00' sClasses4='col4' sClasses5='col5 justify'>
#				this is the description of the item; NOTE: that this is sColumn5 (but can also be the column specified by 'sDescription' as to which column this information needs to be placed in)
#			</item>
#			...
#		<field>
#	</group>
	break;




    case 'Save':
	# Save the Selected Object

	# validate all submitted data
	if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['id'],20,'0-9')) { exit(); }
	if (! validate($_POST['fkGroups'],20,'0-9')) { exit(); }

	# load the users account info in the global variable
	if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

	# check that the submitting account has permission to access the module
	if (! checkPermission('read')) { exit(); }


	# if we've made it here, the user is authorized to interact

	# 1. Obtain the selected object info
	break;




    case 'Delete':
    case 'Disable':
	# Deletes/Disables the requested tab/group record information

	# validate all submitted data
	if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['sModule'],24,'a-zA-Z0-9_')) { exit(); }
	if (! validate($_POST['sTab'],24,'a-zA-Z')) { exit(); }
	if (! validate($_POST['sGroup'],24,'a-zA-Z')) { exit(); }
	if (! validate($_POST['id'],20,'0-9')) { exit(); }

	# load the users account info in the global variable
	if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

	# check that the submitting account has permission to access the module
	if (! checkPermission('write')) { exit(); }


	# if we've made it here, the user is authorized to interact

	$__sInfo['error'] = "Failed to ".strtolower($_POST['A'])." the selected item in the database.";
	if ($_POST['A'] == 'Delete')
		{ $__sInfo['command'] = "DELETE FROM ".DB_PRFX.$_POST['sModule']."_".$_POST['sTab'].($_POST['sTab'] != $_POST['sGroup'] ? '_'.$_POST['sGroup'] : '')." WHERE id=?"; }
	else if ($_POST['A'] == 'Disable')
		{ $__sInfo['command'] = "UPDATE ".DB_PRFX.$_POST['sModule']."_".$_POST['sTab'].($_POST['sTab'] != $_POST['sGroup'] ? '_'.$_POST['sGroup'] : '')." SET bDisabled=1 WHERE id=?"; }
	$__sInfo['values'] = '[i] '.$_POST['id'];
	$stmt = $_LinkDB->prepare($__sInfo['command']);
	$stmt->bind_param('i', $_POST['id']);
	$stmt->execute();

	echo "<s><msg>The selected item has been ".strtolower($_POST['A'])."d successfully!</msg></s>";
	break;










    # --- ERROR ---


    default:
	echo "<f><msg>An invalid request has occurred and our staff has been notified.</msg></f>";
	if (! array_key_exists('sUsername', $_POST)) { $_POST['sUsername'] = 'unknown'; }
	if (! array_key_exists('email', $_POST)) { $_POST['email'] = 'Not Provided'; }
	sendMail($_sSecurityEmail,$_sSecurityName,$_sAlertsEmail,$_sAlertsName,'!!! Possible Cracking Attempt !!!',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."/home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nWe might have had a possible cracking attempt made on ".$_.", from ".$_SERVER['REMOTE_ADDR'].", while attempting to pass an invalid API value.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nUsername: ".$_POST['sUsername']."<br />\nEmail: ".$_POST['email']."<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: An invalid API value was passed to the script.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br />\n<br />\n[".SCRIPT."; Body]<br />\n<br />\nVar Dump:<br />\n</p>\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre><br />\n</td>\n<td>&nbsp;</td>\n</tr>\n</table>\n</body>\n</html>");
}
?>
