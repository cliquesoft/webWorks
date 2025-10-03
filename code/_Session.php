<?php
# _Session.php
#
# Created	2012/12/18 by Dave Henderson (support@cliquesoft.org)
# Updated	2025/03/08 by Dave Henderson (support@cliquesoft.org)
#
# Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
# device, this software is licensed under the Cliquesoft Public License (CPLv2)
# as found on the Cliquesoft website at www.cliquesoft.org.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.
#
# NOTES:
# - This script relies on a SQL database table containing user accounts
#	By default it is 'Employees'
#	This can be changed by simply adjusting the 'TABLE' constant below
# - That table must also contain the following columns:
#	'first' and 'last' -OR- 'name'; the latter containing the entire name
#	'email' -OR- 'workEmail' for contacting the user
#	'username' for storing the accounts username
#	'attempts' for storing the number of failed login attempts
#	'notes' storing notes specific to the user (e.g. failed login attempts)


# Constant Definitions
define("MODULE",'_Session');					# the name of this module (NOTE: this can be the same as the PROJECT constant in the envars.php file)
define("SCRIPT",basename($_SERVER['SCRIPT_NAME']));		# the name of this script (for tracing bugs and automated messages)
define("TABLE",'Employees');					# the name of the table containing the user accounts

# Module Requirements						  NOTE: MUST come below Module Constant Definitions
require_once('../../sqlaccess');
require_once('../data/_modules/ApplicationSettings/config.php');
require_once('_Project.php');
require_once('_Contact.php');
require_once('_Database.php');
require_once('_Security.php');

# Start or resume the PHP session				  NOTE: gains access to $_SESSION variables in this script
session_start();




// format the dates in UTC
$_ = gmdate("Y-m-d H:i:s",time());				# used this mannor so all the times will be the exact same (also see http://php.net/manual/en/function.gmdate.php)

header('Content-Type: text/xml; charset=utf-8');
echo "<?xml version='1.0' encoding='UTF-8'?>";




switch ($_POST['A']) {						# Process the submitted (A)ction

    case 'check':						# Check Application Settings
	if ($_POST['T'] == 'settings') {			# Process the submitted (T)arget
		echo "<s><data bUseCaptchas='".($_bUseCaptchas ? 'true' : 'false')."' /></s>";
		exit();
	}
	break;

    case 'logout':						# Logout the User (End Session)
	if ($_POST['T'] == 'account') {				# Process the submitted (T)arget using the supplied 'username'
		# validate all submitted data
		if (! validate($_POST['sUsername'],32,'[^a-zA-Z0-9@\._\-]')) { exit(); }
		if (! validate($_POST['sSessionID'],40,'[^a-z0-9]')) { exit(); }

# REMOVED 2020/07/15 - we can allow logging in, just not accout creation
#		if (USERS != '') {			# WARNING: we do NOT add accounts to non-native Tracker DB tables - this will be the responsibility of the wrapper project!
#			echo "<f><msg>Logging out with your account in a non-native ".PROJECT." database table is not allowed. Please use the logout section of the parent project.</msg></f>";
#			exit();
#		}

		# process the logout
		#if (file_exists('../data/session.php')) {					# NOTE: this script is not meant for handling sessions for users of webBooks, only to authenticate access into the software
		#} else if (AUTH_TB != '') {							# NOTE: ditto
		#} else {									# this is the ONLY option we need - the native webBooks database
			if (! loadUser($_nTimeout,$__sUser,'rw','id,sid',DB_PRFX.TABLE,'username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

# VER2 - update this to be a separate table for separate logins; this allows the employee to see all current logged in sessions [Employees [module] > Logins [tab]]; they should be able to "shutdown" any of those logins (which would just disable the record in the DB)
		# NOTE: since we are using a single SID for all logins, if the user logs out on one device then it will be on all devices - so we have to handle logouts in this fashion instead of a single loadUser() validation call
		if ($__sUser['sid'] != '') {
			if ($__sUser['sid'] != $_POST['sSessionID']) {
				$__sInfo['error'] = "The account SID and passed SID do not match.";
				$__sInfo['command'] = $__sUser['sid']." != ".$_POST['sSessionID'];
				$__sInfo['values'] = 'None';
				$errno = 0;
				$errstr = '';
				$errfile = SCRIPT;
				$errline = __LINE__ - 7;
				echo "<f><msg>There was an error processing your request and our staff has been notified.  Please try again in a few minutes.</msg></f>";
				sendMail($_sSecurityEmail,$_sSecurityName,$_sAlertsEmail,$_sAlertsName,'!!! Possible Cracking Attempt !!!',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nOne of our users was attempting to interact with our site, but encountered an error which has been included below.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<u>Date:</u> ".gmdate("Y-m-d H:i:s",time())." GMT<br />\n<u>From:</u> ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\n<u>Project:</u> ".PROJECT."<br />\n<u>Module:</u> ".MODULE."<br />\n<u>Script:</u> ".SCRIPT."<br />\n<br />\n<u>DB Host:</u> ".DBHOST."<br />\n<u>DB Name:</u> ".DBNAME."<br />\n<u>DB Prefix:</u> ".DB_PRFX."<br />\n<br />\n<u>Name:</u> ".$__sInfo['name']."<br />\n<u>Contact:</u> ".$__sInfo['contact']."<br />\n<u>Other:</u> ".$__sInfo['other']."<br />\n<br />\n<u>Summary:</u> ".$__sInfo['error']."<br />\n<u>Error:</u> (".$errno.") ".$errstr."<br />\n<u>Command:</u> ".$__sInfo['command']."<br />\n<u>Values:</u> ".$__sInfo['values']."<br />\n<u>File:</u> ".$errfile."<br />\n<u>Line:</u> ".$errline."<br />\n<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br />\n<br />\nVar Dump:<br />\n</p>\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre><br />\n</td>\n<td>&nbsp;</td>\n</tr>\n</table>\n</body>\n</html>");
				exit();
			}

			$__sInfo['error'] = "The logout could not be recorded in the database.";
			$__sInfo['command'] = "UPDATE ".DB_PRFX.TABLE." SET sid='',logout='".$_."' WHERE username=?";
			$__sInfo['values'] = '[s] '.$_POST['sUsername'];
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			$stmt->bind_param('s', $_POST['sUsername']);
			$stmt->execute();
		}

		echo "<s><data sUriProject='".$_sUriProject."' /></s>";
		exit();
	}
	break;




    case 'authenticate':					# Authenticate User Account (Start Session)
	if ($_POST['T'] == 'username') {			# Process the submitted (T)arget using the supplied 'username'
		# validate all submitted data
		if (! validate($_POST['Username'],32,'[^a-zA-Z0-9@\._\-]')) { exit(); }
		if (! validate($_POST['Password'],32,'')) { exit(); }
		if ($_bUseCaptchas) { if (! validate($_POST['sCaptcha'],16,'[^a-zA-Z\- ]')) {exit();} }

		# check the captcha is valid (if it's enabled)
		if ($_bUseCaptchas) {
			if (empty($_POST['sCaptcha'])) {
				echo "<f><data issue='captcha' /><msg>You must enter the captcha text before attempting to login.</msg></f>";
				exit();
			} else if (empty($_SESSION['captcha']) || trim(strtolower($_POST['sCaptcha'])) != $_SESSION['captcha']) {
				echo "<f><data issue='captcha' /><msg>The captcha text you entered does NOT match what is found in the graphic, please try again.</msg></f>";
				exit();
			}
			unset($_SESSION['captcha']);
		}

		# set the username to lowercase to prevent any issues
		$_POST['Username'] = strtolower($_POST['Username']);

		# process the login
		if (file_exists('../data/session.php')) {					# if there is an external authentication script, then...

			require_once('../data/session.php');					#   lets obtain it's functions by reading-in the script

			# authenticate with the foreign service...
			$id = status($_POST['Username']);					# DEV NOTE: the status() function should return it's own error message!
			if (! $id) { exit(); }
			if (! authenticate($id,$_POST['Username'],'password',$_POST['Password'])) { exit(); }			# DEV NOTE: the status() function should return it's own error message!

			# now lets grab the account data from the webBooks database!
# VER2 - once the id_auth db column has been added
#			if (! loadUser($_nTimeout,$__sUser,'rw','*',DB_PRFX.TABLE,'id_auth','i|'.$id)) { exit(); }		# NOTE: this obtains the account info based on the UID of the account (incase the username has changed and hasn't been updated in the webBooks database yet)
			if (! loadUser($_nTimeout,$__sUser,'rw','*',DB_PRFX.TABLE,'id','i|'.$id)) { exit(); }			# NOTE: this obtains the account info based on the UID of the account (incase the username has changed and hasn't been updated in the webBooks database yet)
# LEFT OFF - sync the username and password with the webBooks database (for offline authentication); add a db column for the returned 3rd-party id, and store that value
		} else if (AUTH_TB != '')							# if the authentication needs to occur with a different database table (from a 3rd party application), then...
			{ if (! loadUser($_nTimeout,$__sUser,'rw','*',AUTH_TB,AUTH_UN,'s|'.$_POST['Username'])) {exit();} }
		else										# otherwise, we need to use the webBooks native database, so...
			{ if (! loadUser($_nTimeout,$__sUser,'rw','*',DB_PRFX.TABLE,'username','s|'.$_POST['Username'])) {exit();} }

		# check that the account can be logged into
		if ($__sUser['attempts'] >= $_nFailedAuth) {					# DEV NOTE: this is an alternate to 'suspended'; used for flexibility
			echo "<f><data issue='suspended' /><msg>Your account has had too many failed login attempts preventing you from logging in.  Please contact support for additional information.</msg></f>";
			exit();
		} else if ($__sUser['status'] == 'verfying') {
			echo "<f><data issue='".$__sUser['status']."' /><msg>Your account is still waiting for the email validatation.  Please check your email and follow the instructions provided within.</msg></f>";
			exit();
		} else if ($__sUser['status'] == 'locked') {
			echo "<f><data issue='".$__sUser['status']."' /><msg>Your account has been locked preventing you from logging in.  Please use the automated \"Unlock Account\" option to re-enable your account, or contact support for additional information.</msg></f>";
# VER2 - if the account.id == 1 (e.g. admin), then we need to email the Support contact to let them know (in case this is a brute force attack)
			exit();
		} else if ($__sUser['status'] == 'suspended') {
			echo "<f><data issue='".$__sUser['status']."' /><msg>Your account has been suspended preventing you from logging in.  Please contact support for additional information.</msg></f>";
			exit();
		} else if ($__sUser['status'] == 'deleted') {
			echo "<f><data issue='".$__sUser['status']."' /><msg>Your account has been deleted.  Please contact support for additional information.</msg></f>";
			exit();
		} else if ($__sUser['disabled'] == '1') {					# this was intentionally included in the last spot as a 'safety net'
			echo "<f><data issue='disabled' /><msg>Your account has been disabled preventing you from logging in.  Please contact support for additional information.</msg></f>";
			exit();
		}

		# define general info for any error generated below
		if (array_key_exists('first', $__sUser)) { $__sInfo['name'] = $__sUser['first'].' '.$__sUser['last']; }		# if the project (e.g. tracker) uses broken "down names" (e.g. first & last -vs- entire name), then store the full name
		else if (array_key_exists('name', $__sUser)) { $__sInfo['name'] = $__sUser['name']; }				# otherwise for projects with a single name value, so just transpose it

		if (array_key_exists('email', $__sUser)) { $__sInfo['contact'] = $__sUser['email']; }				# if the project has a single 'email' value associated with the user, then store it
		else if (array_key_exists('workEmail', $__sUser)) { $__sInfo['contact'] = $__sUser['workEmail']; }		# otherwise the project has multiple email values, so store the one that we would interact with while at work

		$__sInfo['other'] = '[Username] '.$__sUser['username'];								# store this additional piece of information related to the user (to make it easier for network admins to find their account)
		$__sInfo['values'] = 'None';

# VER2 - this is less secure since the encryption uses the system encryption string; alternatively, encrypt the PES with the hash of the correctly entered password (so we don't have to ask for it every time); give the PES to the user incase they forget their password, we can re-enter it and re-encrypt it with their new password.
#	or maybe have this be a configuration option (in case the organization doesn't want to have to deal with private account strings)
#	we also need to implement 2-factor authentication (2FA) via email and text
# 	we also need to implement single sign-on (SSO)
		# Process the login request
		$hash = md5($_POST['Password']);						# this section decrypts the password entered by the user
		$salt = file_get_contents('../../denaccess');					# store the system encryption string as the decryption 'salt'
		if (array_key_exists('pes', $__sUser))						# if the database uses 'personal encryption string' (pes), then...
			{ $salt = Cipher::decrypt($__sUser['pes'], $salt); }			#   decrypt the users' pes using the system encryption string (above), and store that as the 'salt' to...
		$decrypted = Cipher::decrypt($__sUser['password'], $salt);			# decrypt the users' hashed password in the database using the appropriate 'salt'

		if (strcmp($decrypted, $hash)) {						# IF the entered password doesn't equal the stored hashed password (which would yield 0), then...
			$__sUser['attempts']++;
			if ($__sUser['attempts'] < $_nFailedAuth) {				#   if the count hasn't yet reached the maximum allowed, then just update the "attempts" count (this is done with two if statements intentionally, so the "invalid attempt" count would be correct)
				if (array_key_exists('notes', $__sUser)) {			#   if the table containing the user accounts has the 'notes' column, then...
					$__sInfo['error'] = "The failed login attempts count can not be updated in the database.";
					$__sInfo['command'] = "UPDATE ".DB_PRFX.TABLE." SET attempts='".$__sUser['attempts']."',notes=concat('notes','\n".$_."\nInvalid login attempt from IP Address ".$_SERVER['REMOTE_ADDR']."') WHERE username=\"".$__sUser['username']."\"";
					$_LinkDB->query($__sInfo['command']);
				} else {							#   otherwise the notes have their own table, so...
					$__sInfo['error'] = "The failed login attempts count can not be updated in the database.";
					$__sInfo['command'] = "UPDATE ".DB_PRFX.TABLE." SET attempts='".$__sUser['attempts']."' WHERE username=\"".$__sUser['username']."\"";
					$_LinkDB->query($__sInfo['command']);

					$__sInfo['error'] = "The failed login attempts note can not be added in the database.";
					$__sInfo['command'] = "INSERT INTO ".DB_PRFX."Application_Notes (type,rowID,creatorID,access,note,created,updated) VALUES ('employee','".$__sUser['id']."','0','managers','".$_."\nInvalid login attempt from IP Address ".$_SERVER['REMOTE_ADDR']."','".$_."','".$_."')";
					$_LinkDB->query($__sInfo['command']);
				}
				echo "<f><data issue='password' /><msg>The password does not match our records.  Please try again or use the 'Reset Password' to regain access to your account.</msg></f>";
			} else {								#   otherwise the count has reached the maximum allowed so disable the account
				if (array_key_exists('notes', $__sUser)) {
					$__sInfo['error'] = "Locking of the user account can not be performed in the database.";
					$__sInfo['command'] = "UPDATE ".DB_PRFX.TABLE." SET attempts='".$__sUser['attempts']."',disabled='1',status='locked',notes=concat('notes','\n".$_."\nAccount locked due to max login attempts reached.') WHERE username=\"".$__sUser['username']."\"";
					$_LinkDB->query($__sInfo['command']);
				} else {
					$__sInfo['error'] = "Locking of the user account can not be performed in the database.";
					$__sInfo['command'] = "UPDATE ".DB_PRFX.TABLE." SET attempts='".$__sUser['attempts']."',disabled='1',status='locked' WHERE username=\"".$__sUser['username']."\"";
					$_LinkDB->query($__sInfo['command']);

					$__sInfo['error'] = "Notation of the locking of the user account can not be performed in the database.";
					$__sInfo['command'] = "INSERT INTO ".DB_PRFX."Application_Notes (type,rowID,creatorID,access,note,created,updated) VALUES ('employee','".$__sUser['id']."','0','managers','".$_."\nAccount locked due to max login attempts reached.','".$_."','".$_."')";
					$_LinkDB->query($__sInfo['command']);
				}
				echo "<f><data issue='locked' /><msg>Your account has been locked due to the amount of invalid login attempts.  Please use the automated 'Unlock Account' to regain access to your account.</msg></f>";
# LEFT OFF - update the image used below to a generic name
				sendMail($__sInfo['contact'],$__sInfo['name'],$_sAlertsEmail,$_sAlertsName,'Account Locked',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."imgs/default/webbooks.email_locked.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Account Locked</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$__sInfo['name'].",<br />\n<br />\nThis email was sent to inform you that your account with ".PROJECT." has been locked due to the amount of failed login attempts.  If you were not responsible for this, we would advise you to change your password to something that you have never used as this may be an attempt to crack into your account using familiar passwords or phrases.  If this is a legitimate request, you will need to go to ".$_sUriProject.", and locate an account unlock service to regain access into your account.  If you are still having trouble or would like to report this incident as an attempt on someone cracking into your account, please contact support immediately.<br />\n<br />\n<a href='".$_sUriProject."'>Click here to unlock your account</a><br />\n<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
	   		}
			exit();
		}

		# if we've made it here, everything has checked out so far, so process the request...
		$__sInfo['error'] = "The login could not be recorded in the database.";
		if ($__sUser['sid'] == '') {							# if the user isn't logged in anywhere already, then...
			$SID = genRandom();							#   generate a random SID for account validation
			$__sInfo['command'] = "UPDATE ".DB_PRFX.TABLE." SET attempts='0',login='".$_."',sid='$SID' WHERE username=\"".$__sUser['username']."\"";
		} else {									# otherwise they are already logged into their account from somewhere else, so...
			$SID = $__sUser['sid'];							#   store that SID to relay back to this login
			$__sInfo['command'] = "UPDATE ".DB_PRFX.TABLE." SET attempts='0',login='".$_."' WHERE username=\"".$__sUser['username']."\"";	# update the login timestamp
		}
		$_LinkDB->query($__sInfo['command']);

		# check that the users 'home' directory exists (in the instance that this project is integrated into another project and this step wasn't performed yet)
		if (! file_exists("../home/".$_POST['Username'])) {
			$__sInfo['error'] = "Can not create the users 'home' directory.";
			$__sInfo['command'] = "mkdir(\"../home/".$_POST['Username']."\", 0775, true)";
			mkdir("../home/".$_POST['Username']."", 0775, true);

			$__sInfo['error'] = "Can not create the users associated 'imgs' symlink.";
			$__sInfo['command'] = "symlink(\"../../imgs/default\",\"../home/".$_POST['Username']."/imgs\")";
			symlink("../../imgs/default","../home/".$_POST['Username']."/imgs");

			$__sInfo['error'] = "Can not create the users associated 'look' symlink.";
			$__sInfo ['command'] = "symlink(\"../../look/default\",\"../home/".$_POST['Username']."/look\")";
			symlink("../../look/default","../home/".$_POST['Username']."/look");
		}

		# below displays info to the user about how many invalid login attempts were made on their account prior to a successful login
		$msg = '';
		if ($__sUser['attempts'] > 0) { $msg = "<msg>You have logged in successfully!\n\nPlease note, there has been ".$__sUser['attempts']." failed login\nattempts since your last successful login.</msg>"; }
		if (array_key_exists('first', $__sUser))					# if the project uses broken down names (e.g. tracker), so...
			{ echo "<s><data sSessionID='".$SID."' sUriProject='".$_sUriProject."' bAdmin='".$__sUser['admin']."'>".$__sUser['first']."|".$__sUser['last']."|".$__sUser['username']."|".$__sInfo['contact']."</data>".$msg."</s>"; }
		else										# otherwise for projects with a single name value (e.g. webBooks), then...
			{ echo "<s><data sSessionID='".$SID."' sUriProject='".$_sUriProject."' bAdmin='".(array_key_exists('admin', $__sUser) ? $__sUser['admin'] : '')."'>".$__sUser['name']."|".$__sUser['username']."|".$__sInfo['contact']."</data>".$msg."</s>"; }

		exit();
	}
	break;




    case 'reset':						# Reset Account Password
	if ($_POST['T'] == 'password') {			# Process the submitted (T)arget using the supplied 'password'
# LEFT OFF - test this
		if ($_POST['sResetKey'] == '') {
			echo "<f><msg>You must provide a type of verification before resetting your account password.</msg></f>";
			exit();
		} else if ($_POST['sResetValue'] == '') {
			echo "<f><msg>You must provide the answer to the verification type before resetting your password.</msg></f>";
			exit();
		} else if (file_exists('../data/session.php')) {				# if there is an external authentication script, then we can NOT update the password
			echo "<f><msg>Your password can not be updated here. Please contact support to perform this action.</msg></f>";
			exit();
		}

		# validate all submitted data
		if (! validate($_POST['sResetKey'],9,'{username|workEmail}')) { exit(); }
		if (! validate($_POST['sResetValue'],128,'[^a-zA-Z0-9\.@\-_]')) { exit(); }

# REMOVED 2025/01/13 - if the user has opt'ed to use a 3rd party authentication database, the password fields MUST enc/dec the same way this application does, so we can now do this
#		if (USERS != '') {				# WARNING: we do NOT add accounts to non-native Tracker DB tables - this will be the responsibility of the wrapper project!
#			echo "<f><msg>Resetting an account password in a non-native ".PROJECT." database table is not allowed. Please use the proper section of the parent project.</msg></f>";
#			exit();
#		}

		# connect to the DB for writing below
		if (! connect2DB(DB_HOST,DB_NAME,DB_RWUN,DB_RWPW)) { exit(); }

		# define general info for any error generated below
		if ($_POST['sResetKey'] == 'username') { $__sInfo['name'] = $_POST['sResetValue']; } else { $__sInfo['name'] = 'Unknown'; }
		if ($_POST['sResetKey'] == 'email') { $__sInfo['contact'] = $_POST['sResetValue']; } else { $__sInfo['contact'] = 'Unknown'; }
		$__sInfo['other'] = '['.$_POST['sResetKey'].'] '.$_POST['sResetValue'];
		$__sInfo['values'] = '[s] '.$_POST['sResetValue'];
		$__sInfo['continue'] = TRUE;							# make sure the processing continues if we can't find the account with the original SQL statement
# UPDATED 2025/01/13 - updated to a more concise 'do' block of code (which now has better error handling)
#		$__sInfo['error'] = "Failed to find the user account in the database using the passed values.";
#		$__sInfo['command'] = "SELECT * FROM ".DB_PRFX.TABLE." WHERE ".$_POST['sResetKey']."=? LIMIT 1";
#		$stmt = $_LinkDB->prepare($__sInfo['command']);
#		if ($stmt === FALSE && $_POST['sResetKey'] == 'email') {			# if the user selected 'email' as the key, but that column was not found in the database (e.g. we're using webBooks, not tracker), then...
#			$_POST['sResetKey'] = 'workEmail';					#   update the key value to work with the webBooks DB layout
#			$__sInfo['command'] = "SELECT * FROM ".DB_PRFX.TABLE." WHERE ".$_POST['sResetKey']."=? LIMIT 1";
#			$stmt = $_LinkDB->prepare($__sInfo['command']);
## REMOVED 2025/01/13 - this is duplicated below
##			$stmt->bind_param('s', $_POST['sResetValue']);				#   and try the call again
#		}
		$__sInfo['error'] = "Failed to find the user account in the database using the passed values.";
		do {
			# NOTE: the $__sInfo['command'] needs to be inside the 'do' loop since the $_POST value changes
			if (AUTH_TB != '')								# if the authentication uses a different database table (from a 3rd party application), then...
				{ $__sInfo['command'] = "SELECT * FROM ".AUTH_TB." WHERE ".$_POST['sResetKey']."=? LIMIT 1"; }
			else										# otherwise, we need to use the webBooks native database, so...
				{ $__sInfo['command'] = "SELECT * FROM ".DB_PRFX.TABLE." WHERE ".$_POST['sResetKey']."=? LIMIT 1"; }
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			# if the user selected 'email' as the key in the UI, but that column was not found in the database (e.g. we're using webBooks, not tracker), then...
			if ($stmt === FALSE && $_POST['sResetKey'] == 'email') { $_POST['sResetKey'] = 'workEmail'; }		# update the variable to reflect the column in the webBooks database (and iterate through the loop again)
			else if ($stmt === FALSE && $_POST['sResetKey'] == 'workEmail') { myErrorHandler(0, "The database column could not be found when resetting the account password.", basename($_SERVER['PHP_SELF']), __LINE__ - 3); exit(); }	# if the webBooks column isn't found, then we have a critical problem!
		} while ($stmt === FALSE);
		$stmt->bind_param('s', $_POST['sResetValue']);
		$stmt->execute();
		$__sUser = $stmt->get_result()->fetch_assoc();
		if (! $__sUser) {
			echo "<f><msg>No account containing the submitted information could be found in the database.</msg></f>";
			exit();
		}
		$__sInfo['continue'] = FALSE;							# reset this value so we halt on errors once again

#		$pes = '';
# UPDATED ? - this was the 5.x way of handling passwords before the 7.x branch updates
#		$salt = file_get_contents('../../denaccess');
#		$pes = Cipher::create_encryption_key();
#		$encPES = Cipher::encrypt($pes, $salt);
#		if (strlen($__sUser['pes']) > 100) { $oldPES = Cipher::decrypt($__sUser['pes'], $salt); }			# store the old 'pes' to re-encrypt the users account fields (and it's the new libsodium encryption)
		$salt = file_get_contents('../../denaccess');
		if (array_key_exists('pes', $__sUser)) {					# if the database table uses a personal encryption string (pes), then...
# UPDATED 2025/01/13 - there is NO reason to replace the 'pes' since it is only the password that needs to be updated
#			$pes = Cipher::create_encryption_key();					# generate a personal encryption string for the account
#			$encPES = Cipher::encrypt($pes, $salt);					# encrypt it using the global encryption string
#			$oldPES = Cipher::decrypt($__sUser['pes'], $salt);			# store the old 'pes' to re-encrypt the users account fields (and it's the new libsodium encryption)
#			$salt = $pes;								# update the value so that the below code works with the global or personal encryption string
			$salt = Cipher::decrypt($__sUser['pes'], $salt);			# decyrpt the users' 'pes' to use that as the salt for encrypting the new password below
		 }

		# generate a new random password
		$password = '';
		$other = array('0','1','2','3','4','5','6','7','8','9','.','?','!','@','#','$','%','^','&','*','-','_','+',':','~','`');
		$lower = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
		$upper = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
		for ($i=0; $i<12; $i++) {							# this for loop creates the new random 12 character value
			$type = rand(0,2);							# randomly selects which of the above arrays to pick a character from
			$char = rand(0,25);							# randomly selects which character in the array to add to the new password

			if ($type == 0) { $password .= $other[$char]; }				# creates the 'password' value based on the randomly selected values from above
			else if ($type == 1) { $password .= $lower[$char]; }
			else { $password .= $upper[$char]; }
		}

		# encrypt the new password
		$hash = md5($password);								# hash the password and store that value, not the actual password!!!	https://stackoverflow.com/questions/9262109/simplest-two-way-encryption-using-php
# LEFT OFF - rename 'encPass' to 'encrypted'
		$encPass = Cipher::encrypt($hash, $salt); 

# MIGRATE - move these into the 'Application' module so that if the user wants to change their 'pes' (or restore it - depending on how the system is configured [e.g. the system enc/dec using the system pes, or their password])
#		# re-encrypt the account values with the updated 'pes'
#		if (array_key_exists('pes', $__sUser)) {					# if the database table uses a personal encryption string (pes), then...
#			if (array_key_exists('last', $__sUser)) { $__sUser['last'] = Cipher::encrypt(Cipher::decrypt($__sUser['last'], $oldPES), $salt); }
#			if (! is_null($__sUser['answer1']) && $__sUser['answer1'] != '') { $__sUser['answer1'] = Cipher::encrypt(Cipher::decrypt($__sUser['answer1'], $oldPES), $salt); } else { $__sUser['answer1'] = ''; }
#			if (! is_null($__sUser['answer2']) && $__sUser['answer2'] != '') { $__sUser['answer2'] = Cipher::encrypt(Cipher::decrypt($__sUser['answer2'], $oldPES), $salt); } else { $__sUser['answer2'] = ''; }
#			if (! is_null($__sUser['answer3']) && $__sUser['answer3'] != '') { $__sUser['answer3'] = Cipher::encrypt(Cipher::decrypt($__sUser['answer3'], $oldPES), $salt); } else { $__sUser['answer3'] = ''; }
#		}

# UPDATED 2025/01/13 - using the same code from the 'authentication' section above
#		$name = 'Unknown User';
#		if (array_key_exists('name', $__sUser)) {									# for projects with a single name value (e.g. webBooks), then...
#			if ($__sUser['name'] != '') { $name = $__sUser['name']; }
#			else if ($__sUser['username'] != '') { $name = $__sUser['username']; }
#		} else if (array_key_exists('first', $__sUser) && array_key_exists('alias', $__sUser)) {			# otherwise the project uses broken down names (e.g. tracker), so...
#			if ($__sUser['first'] != '') { $name = $__sUser['first']; }
#			else if ($__sUser['alias'] != '') { $name = $__sUser['alias']; }
#			else if ($__sUser['username'] != '') { $name = $__sUser['username']; }
#		}
		# define general info for any error generated below
		if (array_key_exists('first', $__sUser)) { $__sInfo['name'] = $__sUser['first'].' '.$__sUser['last']; }		# if the project (e.g. tracker) uses broken "down names" (e.g. first & last -vs- entire name), then store the full name
		else if (array_key_exists('name', $__sUser)) { $__sInfo['name'] = $__sUser['name']; }				# otherwise for projects with a single name value, so just transpose it

		if (array_key_exists('email', $__sUser)) { $__sInfo['contact'] = $__sUser['email']; }				# if the project has a single 'email' value associated with the user, then store it
		else if (array_key_exists('workEmail', $__sUser)) { $__sInfo['contact'] = $__sUser['workEmail']; }		# otherwise the project has multiple email values, so store the one that we would interact with while at work

		#update the database
		$__sInfo['error'] = "Failed to reset the user account information in the database.";
# UPDATED 2025/01/13 - we no longer have to update all these fields since we aren't changing the 'pes'
#		if (array_key_exists('name', $__sUser) && array_key_exists('pes', $__sUser))					# for projects with a single name value (e.g. webBooks) -AND- the database uses 'pes' values, then...
#			{ $__sInfo['command'] = "UPDATE ".DB_PRFX.TABLE." SET sid='',pes=\"".$encPES."\",password=\"".$encPass."\",answer1=\"".$__sUser['answer1']."\",answer2=\"".$__sUser['answer2']."\",answer3=\"".$__sUser['answer3']."\",updated='".$_."' WHERE ".$_POST['sResetKey']."=?"; }
#		else if (array_key_exists('name', $__sUser) && ! array_key_exists('pes', $__sUser))				# same as above but without a 'pes' value, then...
#			{ $__sInfo['command'] = "UPDATE ".DB_PRFX.TABLE." SET sid='',password=\"".$encPass."\",answer1=\"".$__sUser['answer1']."\",answer2=\"".$__sUser['answer2']."\",answer3=\"".$__sUser['answer3']."\",updated='".$_."' WHERE ".$_POST['sResetKey']."=?"; }
#		else if (array_key_exists('last', $__sUser) && array_key_exists('pes', $__sUser))				# otherwise the project uses broken down names (e.g. tracker), so...
#			{ $__sInfo['command'] = "UPDATE ".DB_PRFX.TABLE." SET sid='',pes=\"".$encPES."\",password=\"".$encPass."\",last=\"".$__sUser['last']."\",answer1=\"".$__sUser['answer1']."\",answer2=\"".$__sUser['answer2']."\",answer3=\"".$__sUser['answer3']."\",updated='".$_."' WHERE ".$_POST['sResetKey']."=?"; }
#		else if (array_key_exists('last', $__sUser) && ! array_key_exists('pes', $__sUser))				# same as above but without a 'pes' value, so...
#			{ $__sInfo['command'] = "UPDATE ".DB_PRFX.TABLE." SET sid='',password=\"".$encPass."\",last=\"".$__sUser['last']."\",answer1=\"".$__sUser['answer1']."\",answer2=\"".$__sUser['answer2']."\",answer3=\"".$__sUser['answer3']."\",updated='".$_."' WHERE ".$_POST['sResetKey']."=?"; }
		if (AUTH_TB != '')								# if the authentication uses a different database table (from a 3rd party application), then...
			{ $__sInfo['command'] = "UPDATE ".AUTH_TB." SET sid='',password=\"".$encPass."\",updated='".$_."' WHERE ".$_POST['sResetKey']."=?"; }
		else										# otherwise, we need to use the webBooks native database, so...
			{ $__sInfo['command'] = "UPDATE ".DB_PRFX.TABLE." SET sid='',password=\"".$encPass."\",updated='".$_."' WHERE ".$_POST['sResetKey']."=?"; }
		# NOTE: if we use an '../data/session.php' script, we would have exited this script already (see the top of this block of code)
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('s', $_POST['sResetValue']);
		$stmt->execute();

# UPDATED 2025/01/13 - there's no need to separate these any longer
#		if (array_key_exists('name', $__sUser))										# for projects with a single name value (e.g. webBooks), then...
#			{ sendMail($__sUser['workEmail'],$name,$gbl_emailNoReply,$gbl_nameNoReply,'Password Reset Confirmation',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_info.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Password Reset Confirmation</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nThis email is to notify you that your ".PROJECT." account password has been reset using the 'Account Services' functionality from our website.  The new randomly generated password has been included below to allow you access back into your account.  We recommend that you update this value, after logging in, to something that you can better remember.  For your convenience, we have also included a link to our website below.<br /><br /><b>WARNING:</b> If you did not initiate this process, contact our staff immediately as this may be a malicious cracking attempt with your account!<br />\n<br />\nPassword: ".$password."<br />\nWebsite: <a href='".$gbl_uriProject."/#Login' target='_new'>".PROJECT."</a><br /><br />\nSincerely,<br />\n".PROJECT." Staff\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n</table>\n</body>\n</html>"); }
#		else														# otherwise the project uses broken down names (e.g. tracker), so...
#			{ sendMail($__sUser['email'],$name,$gbl_emailNoReply,$gbl_nameNoReply,'Password Reset Confirmation',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_info.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Password Reset Confirmation</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nThis email is to notify you that your ".PROJECT." account password has been reset using the 'Account Services' functionality from our website.  The new randomly generated password has been included below to allow you access back into your account.  We recommend that you update this value, after logging in, to something that you can better remember.  For your convenience, we have also included a link to our website below.<br /><br /><b>WARNING:</b> If you did not initiate this process, contact our staff immediately as this may be a malicious cracking attempt with your account!<br />\n<br />\nPassword: ".$password."<br />\nWebsite: <a href='".$gbl_uriProject."/#Login' target='_new'>".PROJECT."</a><br /><br />\nSincerely,<br />\n".PROJECT." Staff\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n</table>\n</body>\n</html>"); }
		sendMail($__sInfo['contact'],$__sInfo['name'],$_sAlertsEmail,$_sAlertsName,'Password Reset Notification',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/email_info.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Password Reset Notification</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nThis email is to notify you that your ".PROJECT." account password has just been reset.  The new randomly generated password has been included below to allow you access back into your account.  We recommend that you update this value after logging in, to something that you can better remember.  For your convenience, we have also included a link to access your account below.<br /><br /><b>WARNING:</b> If you did not initiate this process, contact support immediately as this may be a malicious cracking attempt with your account!<br />\n<br />\nPassword: ".$password."<br />\nWebsite: <a href='".$_sUriProject."' target='_new'>".PROJECT."</a><br /><br />\nSincerely,<br />\n".PROJECT." Staff\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n</table>\n</body>\n</html>");
		echo "<s><msg>Your password has been reset successfully, please check your email to continue!</msg></s>";
		exit();
	}
	break;




    case 'unlock':						# Unlock User Account
	if ($_POST['T'] == 'account') {				# Process the submitted (T)arget using the supplied 'account'
		if ($_POST['sUnlockKey'] == '') {
			echo "<f><msg>You must provide a type of verification before unlocking your account.</msg></f>";
			exit();
		} else if ($_POST['sUnlockValue'] == '') {
			echo "<f><msg>You must provide the answer to the verification type before unlocking your account.</msg></f>";
			exit();
		} else if (file_exists('../data/session.php')) {				# if there is an external authentication script, then we can NOT update the password
			echo "<f><msg>Your account can not be unlocked here. Please contact support to perform this action.</msg></f>";
			exit();
		}

# REMOVED 2025/01/13 - if the user has opt'ed to use a 3rd party authentication database, the password fields MUST enc/dec the same way this application does, so we can now do this
#		if (USERS != '') {			# WARNING: we do NOT add accounts to non-native Tracker DB tables - this will be the responsibility of the wrapper project!
#			echo "<f><msg>Resetting an account password in a non-native ".PROJECT." database table is not allowed. Please use the proper section of the parent project.</msg></f>";
#			exit();
#		}

		# validate all submitted data
		if (! validate($_POST['SA'],9,'{challenge|verify}')) { exit(); }		# check the (S)ub (A)ction value
		if (! validate($_POST['sUnlockKey'],9,'{username|workEmail}')) { exit(); }
		if (! validate($_POST['sUnlockValue'],128,'[^a-zA-Z0-9\.@\-_]')) { exit(); }
		if (isset($_POST['nQuestion']) && ! validate($_POST['nQuestion'],1,'{1|2|3}')) { exit(); }
		if (isset($_POST['sAnswer']) && ! validate($_POST['sAnswer'],32,'![=<>;]')) { exit(); }

		# connect to the DB for writing below
		if (! connect2DB(DB_HOST,DB_NAME,DB_RWUN,DB_RWPW)) { exit(); }

		# define general info for any error generated below
		if ($_POST['sUnlockKey'] == 'username') { $__sInfo['name'] = $_POST['sUnlockValue']; } else { $__sInfo['name'] = 'Unknown'; }
		if ($_POST['sUnlockKey'] == 'email') { $__sInfo['contact'] = $_POST['sUnlockValue']; } else { $__sInfo['contact'] = 'Unknown'; }
		$__sInfo['other'] = '['.$_POST['sUnlockKey'].'] '.$_POST['sUnlockValue'];
		$__sInfo['values'] = '[s] '.$_POST['sUnlockValue'];
		$__sInfo['continue'] = TRUE;							# make sure the processing continues if we can't find the account with the original SQL statement

# UPDATED 2025/01/13 - updated to be more robust with better error handling
#		$__sInfo['error'] = "Failed to find the user account in the database via the passed values.";
#		$__sInfo['command'] = "SELECT * FROM ".PREFIX.TABLE." WHERE ".$_POST['sUnlockKey']."=? LIMIT 1";
#		$stmt = $_LinkDB->prepare($__sInfo['command']);
#		$stmt->bind_param('s', $_POST['sUnlockValue']);
#		$stmt->execute();
#		$__sUser = $stmt->get_result()->fetch_assoc();
#		if (! $__sUser) {
#			echo "<f><msg>No account containing the submitted information could be found in the database.</msg></f>";
#			exit();
#		}
		$__sInfo['error'] = "Failed to find the user account in the database via the passed values.";
		do {
			# NOTE: the $__sInfo['command'] needs to be inside the 'do' loop since the $_POST value changes
			if (AUTH_TB != '')							# if the authentication uses a different database table (from a 3rd party application), then...
				{ $__sInfo['command'] = "SELECT * FROM ".AUTH_TB." WHERE ".$_POST['sUnlockKey']."=? LIMIT 1"; }
			else									# otherwise, we need to use the webBooks native database, so...
				{ $__sInfo['command'] = "SELECT * FROM ".DB_PRFX.TABLE." WHERE ".$_POST['sUnlockKey']."=? LIMIT 1"; }
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			# if the user selected 'email' as the key in the UI, but that column was not found in the database (e.g. we're using webBooks, not tracker), then...
			if ($stmt === FALSE && $_POST['sUnlockKey'] == 'email') { $_POST['sUnlockKey'] = 'workEmail'; }		# update the variable to reflect the column in the webBooks database (and iterate through the loop again)
			else if ($stmt === FALSE && $_POST['sUnlockKey'] == 'workEmail') { myErrorHandler(0, "The database column could not be found when resetting the account password.", basename($_SERVER['PHP_SELF']), __LINE__ - 3); exit(); }	# if the webBooks column isn't found, then we have a critical problem!
		} while ($stmt === FALSE);
		$stmt->bind_param('s', $_POST['sUnlockValue']);
		$stmt->execute();
		$__sUser = $stmt->get_result()->fetch_assoc();
		if (! $__sUser) {
			echo "<f><msg>No account containing the submitted information could be found in the database.</msg></f>";
			exit();
		}
		$__sInfo['continue'] = FALSE;							# reset this value so we halt on errors once again

		# perform any security checks
		if ($__sUser['attempts'] >= ($_nFailedAuth * 2)) {				# if the count has reached the maximum allowed for unlocking, then...
			echo "<f><msg>You have reached the maximum unlock threshold and will now have contact support to unlock your account.</msg></f>";
			exit();
		} else if ($__sUser['status'] != 'locked') {
			echo "<f><msg>Your account is not locked, so this process has been cancelled.</msg></f>";
			exit();
		}

		if ($_POST['SA'] == 'challenge') {						# if we are in the 'obtain the security question' part of this (S)ub (A)ction, then...
			# obtain a random number between 1 and 3 (since we only have 3 security questions)
			$random = rand(1,3);

			$q = $__sUser['question'.$random];
			# return a random security question
			echo "<s><data id='".$random."'>".safeXML($q)."</data></s>";
			exit();
		} else if ($_POST['SA'] == 'verify') {						# otherwise, we are on the 'process the submitted answer' portion, so...
			# decrypt the account information to get a security question
			$salt = file_get_contents('../../denaccess');				# obtain the salt to decrypt the answer to the selected security question
# LEFT OFF - implement this once we implement 'pes' string
#			if (array_key_exists('pes', $__sUser))					# if the database table uses a personal encryption string (pes), then...
#				{ $salt = Cipher::decrypt($__sUser['pes'], $salt); }		#   decyrpt the users' 'pes' to use that as the salt (instead) to decrypt security question answers below

			$a = Cipher::decrypt($__sUser['answer'.$_POST['nQuestion']], $salt);	# store the decrypted answer

			if (strtolower($a) != strtolower($_POST['sAnswer'])) {			# if the answers do NOT match, then...		NOTE: to avoid issues with capitalization, make the answers all lowercase
				if ($__sUser['attempts'] < ($_nFailedAuth * 2)) {		#   if the count has NOT yet reached the maximum allowed for unlocking, then...
					$__sInfo['error'] = "Failed to update the failed login count in the database.";
# UPDATED 2025/02/27
#					$__sInfo['command'] = "UPDATE ".DB_PRFX.TABLE." SET attempts=attempts+1,notes='".$__sUser['notes']."\n".date("Y-m-d H:i:s",time())."\nInvalid account unlock attempt using wrong answer, from IP Address ".$_SERVER['REMOTE_ADDR'].".' WHERE ".$_POST['sUnlockKey']."=?";
					$__sInfo['command'] = "UPDATE ".DB_PRFX.TABLE." SET attempts=attempts+1 WHERE ".$_POST['sUnlockKey']."=?";
					$stmt = $_LinkDB->prepare($__sInfo['command']);
					$stmt->bind_param('s', $_POST['sUnlockValue']);
					$stmt->execute();

# ADDED 2025/02/27
					$__sInfo['error'] = "Notation of the account unlock wrong answer can not be performed in the database.";
					$__sInfo['command'] = "INSERT INTO ".DB_PRFX."Application_Notes (type,rowID,creatorID,access,note,created,updated) VALUES ('employee','".$__sUser['id']."','0','managers','".$_."\nInvalid account unlock attempt using wrong answer, from IP Address ".$_SERVER['REMOTE_ADDR'].".','".$_."','".$_."')";
					$_LinkDB->query($__sInfo['command']);
				} else {							#   otherwise too many unlock attempts have been made on the account and now it is permanently locked preventing automated unlock services
					$__sInfo['error'] = "Failed to suspend the account in the database due to excessive unlock attempts.";
# UPDATED 2025/02/27
#					$__sInfo['command'] = "UPDATE ".PREFIX.TABLE." SET status='suspended',attempts=attempts+1,notes='".$__sUser['notes']."\n".date("Y-m-d H:i:s",time())."\nAccount now completely suspended due to max unlock attempts reached.' WHERE ".$_POST['sUnlockKey']."=?";
					$__sInfo['command'] = "UPDATE ".PREFIX.TABLE." SET status='suspended',attempts=attempts+1 WHERE ".$_POST['sUnlockKey']."=?";
					$stmt = $_LinkDB->prepare($__sInfo['command']);
					$stmt->bind_param('s', $_POST['sUnlockValue']);
					$stmt->execute();

# ADDED 2025/02/27
					$__sInfo['error'] = "Notation of the account unlock wrong answer can not be performed in the database.";
					$__sInfo['command'] = "INSERT INTO ".DB_PRFX."Application_Notes (type,rowID,creatorID,access,note,created,updated) VALUES ('employee','".$__sUser['id']."','0','managers','".$_."\nAccount now completely suspended due to max unlock attempts reached. Attempted from IP Address ".$_SERVER['REMOTE_ADDR'].".','".$_."','".$_."')";
					$_LinkDB->query($__sInfo['command']);
				}

				echo "<f><msg>The submitted answer is incorrect, please try again.</msg></f>";
				exit();
			}
		}

		$__sInfo['error'] = "Failed to reset the account in the database after successfully unlocking.";
		$__sInfo['command'] = "UPDATE ".PREFIX.TABLE." SET sid='',disabled='0',attempts='0',status='active',updated='".$_."' WHERE ".$_POST['sUnlockKey']."=?";
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('s', $_POST['sUnlockValue']);
		$stmt->execute();

		echo "<s><msg>Your account has been unlocked successfully!</msg></s>";
		exit();
	}
	break;




    default:							# otherwise, we need to indicate that an invalid request was made
	echo "<f><msg>An invalid request has occurred, our staff has been notified.</msg></f>";
	sendMail($_sSecurityEmail,$_sSecurityName,$_sAlertsEmail,$_sAlertsName,'*** Possible Cracking Attempt ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: A user is attempting to pass an invalid 'action' or 'target' values.<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");


}
?>
