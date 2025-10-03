<?php
# setup.php	the setup routine for the core software when initially installing
#
# Created	2015/03/04 by Dave Henderson (support@cliquesoft.org)
# Updated	2025/10/01 by Dave Henderson (support@cliquesoft.org)
#
# Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
# device, this software is licensed under the Cliquesoft Public License (CPLv2)
# as found on the Cliquesoft website at www.cliquesoft.org.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.
#
# DESIGNATIONS:			(of the first letter of variables)
# These are the first letter of variables and indicate the value it should contain.
# A preceeding underscore indicates it's a global variable (e.g. _sAlertName).
# 2 preceeding dashes indicates it's a global array of that type (e.g. __sErrs).
#	b - boolean  (true/false)
#	e - epoch    (date and time)
#	n - number   (numbers, commas, and period)
#	s - string   (any character)




# Constant Definitions
define("MODULE",'webBooks');					# the name of this module (NOTE: this can be the same as the PROJECT constant in the envars.php file)
define("SCRIPT",basename($_SERVER['SCRIPT_NAME']));		# the name of this script (for tracing bugs and automated messages)
define("NAME",'Application Setup');




# format the dates in UTC
$_ = gmdate("Y-m-d H:i:s",time());				# format the dates in UTC so all the times will be "in the same timezone" (also see http://php.net/manual/en/function.gmdate.php)




# Function Declarations
function writeSystemConfigs() {
# the function to write the configuration of this project
	global $__sInfo,$_sAlertsName,$_sAlertsEmail,$_sSupportName,$_sSupportEmail,$_sSecurityName,$_sSecurityEmail,$_sUriPayment,$_sUriProject,$_sUriSocial;

	# WARNING: this overwrites the config file regardless if it exists already or not!!!
	if (! $_POST['bExistingConfigs']) {			# this will allow for changes to be written to file, instead of checking for the files' existance
		$__sInfo['error'] = "The 'config.php' config file can not be created.";
		$__sInfo['command'] = "fopen('../data/_modules/ApplicationSettings/config.php', 'w')";
		$__sInfo['values'] = 'None';

		$fh = fopen('../data/_modules/ApplicationSettings/config.php', 'w');
		fwrite($fh, "<?php\n");
		fwrite($fh, "# Application.php	the global definitions used by all modules in the application\n");
		fwrite($fh, "# Created	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n");
		fwrite($fh, "# Updated	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n\n");

		fwrite($fh, "# Non-editable Definitions\n");
		fwrite($fh, "define('PROJECT','".PROJECT."');\n\n");

		fwrite($fh, "# Directory Definitions\n");
# REMOVED 2025/02/23
#		fwrite($fh, "\$_sDirCfgs='../data/_cfgs';\n");
# VER2 - remove the ability to change DirData (it causes race conditions)
		fwrite($fh, "\$_sDirData='../data';\n");
		fwrite($fh, "\$_sDirCron=\$_sDirData.'/_cron';\n");
		fwrite($fh, "\$_sDirLogs=\$_sDirData.'/_logs';\n");
# VER2 - update sDirMail > sDirSocial
		fwrite($fh, "\$_sDirMail=\$_sDirData.'/_mail';\n");		# NOTE: this directory is used for the native Social interface (Threads)
		fwrite($fh, "\$_sDirTemp='../temp';\n");
# VER2 - update sDirVrfy > sDirVerify
		fwrite($fh, "\$_sDirVrfy=\$_sDirData.'/_verify';\n\n");

		fwrite($fh, "# Log Definitions\n");
		fwrite($fh, "\$_sLogEmail='email.log';\n");
		fwrite($fh, "\$_sLogScript=SCRIPT.'.log';\n");			# these are for libraries
		fwrite($fh, "\$_sLogModule=MODULE.'.log';\n");			# these are for modules
		fwrite($fh, "\$_sLogProject=PROJECT.'.log';\n\n");		# these are for the overall project
# VER2 - implement Errors (_Project.php > myErrorHandler), Commerce (every module writes what comes in/out over commerce), and Authentication logs (_Session.php; bad authentication only)

		fwrite($fh, "# Operation Definitions\n");
		fwrite($fh, "\$_sInterface='".$_POST['sInterface']."';\n");
		if ($_POST['bHostedService']) { fwrite($fh, "\$_bHostedService=true;\n"); } else { fwrite($fh, "\$_bHostedService=false;\n"); }
		fwrite($fh, "\$_sUpdates='automatic';\n");
		fwrite($fh, "\$_sInstall='manual';\n\n");

		fwrite($fh, "# Security Definitions\n");
		fwrite($fh, "\$_nTimeout=0;\n");
		fwrite($fh, "\$_nFailedAuth=5;\n");				# NOTE: this value multiplied by 2 is how many unlock attempts can be made on the account before it's disabled
		if ($_POST['bUseCaptchas']) { fwrite($fh, "\$_bUseCaptchas=true;\n\n"); } else { fwrite($fh, "\$_bUseCaptchas=false;\n\n"); }

		fwrite($fh, "# URI Definitions\n");
		fwrite($fh, "\$_sUriPayment='".$_sUriPayment."';\n");		# used for payment processing (if applicable)
		fwrite($fh, "\$_sUriProject='".$_sUriProject."';\n");		# used in emails so the referenced images have a full URI; should NOT be used otherwise
		fwrite($fh, "\$_sUriSocial='".$_sUriSocial."';\n\n");		# used for the social interface

		fwrite($fh, "# Email Definitions\n");
		fwrite($fh, "\$_sAlertsName=\"".$_sAlertsName."\";\n");
		fwrite($fh, "\$_sAlertsEmail='".$_sAlertsEmail."';\n");
		fwrite($fh, "\$_sSupportName=\"".$_sSupportName."\";\n");
		fwrite($fh, "\$_sSupportEmail='".$_sSupportEmail."';\n");
		fwrite($fh, "\$_sSecurityName=\"".$_sSecurityName."\";\n");
		fwrite($fh, "\$_sSecurityEmail='".$_sSecurityEmail."';\n\n");

		fwrite($fh, "# Maintenance Variables\n");
		fwrite($fh, "\$_bMaintenance=false;\n");
		fwrite($fh, "\$_sMaintenance='2:30pm EST - down for 30 min';\n\n");

		fwrite($fh, "# System Variables\n");
		fwrite($fh, "\$_bDebug=false;\n");
		fwrite($fh, "\$_LinkDB;\n");
		fwrite($fh, "\$__sInfo=array();\n");
		fwrite($fh, "\$__sMsgs=array();\n");
# LEFT OFF - we need to rename __sUser > __User; __sNull > __Null
		fwrite($fh, "\$__sUser=array();\n");
		fwrite($fh, "\$__sNull=array();\n");
		fwrite($fh, "?>\n");
		fclose($fh);




		$__sInfo['error'] = "The 'Application.js' config file can not be created.";		# WARNING: this should NEVER store any sensative information due to (accidental) exposure with misconfigured web servers!!!
		$__sInfo['command'] = "fopen('../data/_modules/ApplicationSettings/config.js', 'w')";
		$__sInfo['values'] = 'None';

		$fh = fopen('../data/_modules/ApplicationSettings/config.js', 'w');
		fwrite($fh, "// Application.js	the global definitions used by all modules in the application\n");
		fwrite($fh, "// Created	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n");
		fwrite($fh, "// Updated	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n\n");

// REMOVED 2025/03/01 - to reduce duplication of settings, these will only be pulled from config.php
//		fwrite($fh, "// Operation Definitions\n");
//		if ($_POST['bHostedService']) { fwrite($fh, "var _bService=true;\n"); } else { fwrite($fh, "var _bService=false;\n\n"); }
//
//		fwrite($fh, "// Security Declarations\n");
//		if ($_POST['bUseCaptchas']) { fwrite($fh, "var _bCaptchas=true;\n\n"); } else { fwrite($fh, "var _bCaptchas=false;\n\n"); }

		fwrite($fh, "// URI Definitions\n");
		fwrite($fh, "var _sUriProject='';\n\n");			# WARNING: this should ONLY have a value if we need to REDIRECT to localhost/127.0.0.1 for a client-side module; null value otherwise!
		fclose($fh);
	}




# LEGACY - delete the below blocks in a future version
	global $gbl_msgs,$gbl_nameNoReply,$gbl_emailNoReply,$gbl_nameHackers,$gbl_emailHackers,$gbl_nameCrackers,$gbl_emailCrackers,$gbl_uriContact,$gbl_uriProject,$gbl_uriPPV,$TIMEOUT,$HOSTED,$CAPTCHAS,$TABLE;

	# if the file does NOT already exist (e.g. this project isn't getting added to an existing project), then create the config.js, config.php, and config.PROJECT.php files
	if (! $_POST['bExistingConfigs']) {			# this will allow for changes to be written to file, instead of checking for the files' existance
		$__sInfo['error'] = "The 'config.js' config file can not be created.";
		$__sInfo['command'] = "fopen('../data/config.js', 'w')";
		$__sInfo['values'] = 'None';

		$fh = fopen('../data/config.js', 'w');
		fwrite($fh, "// config.js	the global definitions used by all projects distributed by Cliquesoft.org\n");
		fwrite($fh, "// Created	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n");
		fwrite($fh, "// Updated	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n\n");
		fwrite($fh, "// Global Declarations\n\n");
		fwrite($fh, "var gbl_nameUser=(getCookie('username') == undefined) ? 'guest' : getCookie('username');\n\n");
		fwrite($fh, "var gbl_uriContact='".$gbl_uriContact."';\n");
		fwrite($fh, "var gbl_uriProject='".$gbl_uriProject."';\n\n");
		fwrite($fh, "var gbl_uriPaypal='".$gbl_uriPPV."';\n\n");
		fwrite($fh, "var gbl_PID=0;\n\n\n\n\n");
		fwrite($fh, "// Module Declarations\n\n");
		if ($CAPTCHAS) { fwrite($fh, "var CAPTCHAS=true;\n"); } else { fwrite($fh, "var CAPTCHAS=false;\n"); }
		if ($TABLE == '') { fwrite($fh, "var MAPPED=false;\n"); } else { fwrite($fh, "var MAPPED=true;\n"); }
		if ($HOSTED) { fwrite($fh, "var HOSTED=true;\n"); } else { fwrite($fh, "var HOSTED=false;\n"); }
		fclose($fh);
	}

	# if the file does NOT already exist (e.g. this project isn't getting added to an existing project), then create the config.php, config.PROJECT.php, and config.js files
	if (! $_POST['bExistingConfigs']) {			# this will allow for changes to be written to file, instead of checking for the files' existance
		$__sInfo['error'] = "The 'config.php' config file can not be created.";
		$__sInfo['command'] = "fopen('../data/config.php', 'w')";
		$__sInfo['values'] = 'None';

		$fh = fopen('../data/config.php', 'w');
		fwrite($fh, "<?php\n");
		fwrite($fh, "# config.php	the global definitions used by all projects distributed by Cliquesoft.org\n");
		fwrite($fh, "# Created	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n");
		fwrite($fh, "# Updated	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n\n");
		fwrite($fh, "# Global Constant Definitions\n");
		fwrite($fh, "define('PROJECT','".PROJECT."');\n");
		fwrite($fh, "define('TIMEOUT',".$TIMEOUT.");\n");
		if ($CAPTCHAS) { fwrite($fh, "define('CAPTCHAS',true);\n"); } else { fwrite($fh, "define('CAPTCHAS',false);\n"); }
		if ($HOSTED) { fwrite($fh, "define('HOSTED',true);\n\n"); } else { fwrite($fh, "define('HOSTED',false);\n\n"); }
		fwrite($fh, "# Global Directory Definitions\n");
		fwrite($fh, "\$gbl_dirCron='../data/_cron';\n");
		fwrite($fh, "\$gbl_dirData='../data';\n");
		fwrite($fh, "\$gbl_dirLogs='../data/_logs';\n");
		fwrite($fh, "\$gbl_dirMail='../data/_mail';\n");
		fwrite($fh, "\$gbl_dirTemp='../temp';\n");
		fwrite($fh, "\$gbl_dirVerify='../data/_verify';\n\n");
		fwrite($fh, "# Global Log Definitions\n");
		fwrite($fh, "\$gbl_logEmail='email.log';\n");
		fwrite($fh, "\$gbl_logScript=SCRIPT.'.log';\n");
		fwrite($fh, "\$gbl_logModule=MODULE.'.log';\n");
		fwrite($fh, "\$gbl_logProject=PROJECT.'.log';\n\n");
		fwrite($fh, "# Global URI Definitions\n");
		fwrite($fh, "\$gbl_uriPPV='".$gbl_uriPPV."';\n");
		fwrite($fh, "\$gbl_uriContact='".$gbl_uriContact."';\n");
		fwrite($fh, "\$gbl_uriProject='".$gbl_uriProject."';\n\n");
		fwrite($fh, "# Global Mail Definitions\n");
		fwrite($fh, "\$gbl_nameNoReply=\"".$gbl_nameNoReply."\";\n");
		fwrite($fh, "\$gbl_emailNoReply='".$gbl_emailNoReply."';\n");
		fwrite($fh, "\$gbl_nameHackers=\"".$gbl_nameHackers."\";\n");
		fwrite($fh, "\$gbl_emailHackers='".$gbl_emailHackers."';\n");
		fwrite($fh, "\$gbl_nameCrackers=\"".$gbl_nameCrackers."\";\n");
		fwrite($fh, "\$gbl_emailCrackers='".$gbl_emailCrackers."';\n\n");
		fwrite($fh, "# Global Failure Definitions\n");
		fwrite($fh, "\$gbl_intFailedAuth=5;\n");
		fwrite($fh, "\$gbl_intFailedCaptcha=5;\n\n");
		fwrite($fh, "# Global System Variables\n");
		fwrite($fh, "\$gbl_intMaintenance=0;\n");
		fwrite($fh, "\$gbl_strMaintenance='2:30pm EST - down for 30 min';\n");
		fwrite($fh, "\$gbl_debug=0;\n");
		fwrite($fh, "\$gbl_dbug=array();\n");
		fwrite($fh, "\$gbl_errs=array();\n");				# VER2 - this is getting phased out in favor of gbl_info['error']
		fwrite($fh, "\$gbl_info=array();\n");
		fwrite($fh, "\$gbl_msgs=array();\n");
		fwrite($fh, "\$gbl_user=array();\n");
		fwrite($fh, "\$gbl_null=array();\n");
		fwrite($fh, "\$linkDB;\n");
		fwrite($fh, "?>\n");
		fclose($fh);
	}

	# if the file does NOT already exist (e.g. this project isn't getting added to an existing project), then create the config.php, config.PROJECT.php, and config.js files
	if (! $_POST['bExistingConfigs']) {			# this will allow for changes to be written to file, instead of checking for the files' existance
		$__sInfo['error'] = "The 'config.".strtolower(MODULE).".php' config file can not be created.";
		$__sInfo['command'] = "fopen('../data/config.".strtolower(MODULE).".php', 'w')";
		$__sInfo['values'] = 'None';

# UPDATED - merged this file into the one above; leave this blank (so we don't have to update all the modules yet) and we'll delete it in the setup update() function
		$fh = fopen('../data/config.'.strtolower(MODULE).'.php', 'w');
		fwrite($fh, "<?php\n");
		fwrite($fh, "?>\n");
		fclose($fh);
	}
}


function writeDatabaseConfig() {
# the function to write the configuration of this project
	global $__sInfo,$DB_HOST,$DB_NAME,$DB_ROUN,$DB_ROPW,$DB_RWUN,$DB_RWPW,$DB_PRFX,$AUTH_TB,$AUTH_ID,$AUTH_UN,$AUTH_PW;

	# if the file does NOT already exist (e.g. this project isn't getting added to an existing project), then create the config.php, config.PROJECT.php, and config.js files
	if (! $_POST['bExistingConfigs']) {			# this will allow for changes to be written to file, instead of checking for the files' existance
		$__sInfo['error'] = "The 'sqlaccess' config file can not be created.";
		$__sInfo['command'] = "fopen('../../sqlaccess', 'w')";
		$__sInfo['values'] = 'None';

		$fh = fopen('../../sqlaccess', 'w');
		fwrite($fh, "<?php\n");
		fwrite($fh, "# sqlaccess	the credentials to make a SQL server connection\n");
		fwrite($fh, "# Created	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n");
		fwrite($fh, "# Updated	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n\n");

		fwrite($fh, "# Database Server Definitions\n");
		fwrite($fh, "define('DB_HOST','".$DB_HOST."');\n");
		fwrite($fh, "define('DB_NAME','".$DB_NAME."');\n");
		fwrite($fh, "define('DB_ROUN','".$DB_ROUN."');\n");
		fwrite($fh, "define('DB_ROPW','".str_replace("'", "\\'", $DB_ROPW)."');\n");
		fwrite($fh, "define('DB_RWUN','".$DB_RWUN."');\n");
		fwrite($fh, "define('DB_RWPW','".str_replace("'", "\\'", $DB_RWPW)."');\n");
		fwrite($fh, "define('DB_PRFX','".$DB_PRFX."');\n\n");

		fwrite($fh, "# Application Authentication Definitions\n");
		fwrite($fh, "define('AUTH_TB','".$AUTH_TB."');\n\n");
		fwrite($fh, "define('AUTH_ID','".$AUTH_ID."');\n");
		fwrite($fh, "define('AUTH_UN','".$AUTH_UN."');\n");
		fwrite($fh, "define('AUTH_PW','".str_replace("'", "\\'", $AUTH_PW)."');\n");

# LEGACY - delete at a future time
	global $DBHOST,$DBNAME,$DBUNRO,$DBPWRO,$DBUNRW,$DBPWRW,$PREFIX,$TABLE,$UID,$USERNAME,$PASSWORD;
		fwrite($fh, "\n\n# LEGACY - Main Database Definitions\n");
		fwrite($fh, "define('DBHOST','".$DBHOST."');\n");
		fwrite($fh, "define('DBNAME','".$DBNAME."');\n");
		fwrite($fh, "define('DBUNRO','".$DBUNRO."');\n");
		fwrite($fh, "define('DBPWRO','".str_replace("'", "\\'", $DBPWRO)."');\n");
		fwrite($fh, "define('DBUNRW','".$DBUNRW."');\n");
		fwrite($fh, "define('DBPWRW','".str_replace("'", "\\'", $DBPWRW)."');\n\n");
		fwrite($fh, "# LEGACY - --migrated from config.webbooks.php--\n");
		fwrite($fh, "define('PREFIX','".$PREFIX."');\n");
		fwrite($fh, "define('USERS','".$TABLE."');\n\n");
		fwrite($fh, "define('UID','".$UID."');\n");
		fwrite($fh, "define('USERNAME','".$USERNAME."');\n");
		fwrite($fh, "define('PASSWORD','".$PASSWORD."');\n");
		fwrite($fh, "?>\n");
		fclose($fh);
	}
}


function createDirectories() {
# the function to create the initial directory structure under the 'data' and 'home' directories
	global $__sInfo;

	# now create the neccessary account and application 'data' directory structures
	if (! file_exists('../home/admin')) {
		$__sInfo['error'] = "The 'admin' home directory can not be created.";
		$__sInfo['command'] = "mkdir('../home/admin', 0775, true)";
		$__sInfo['values'] = '';

# LEFT OFF - include the '@' symbol in front of any calls like this to not pollute the output
		@mkdir('../home/admin', 0775, true);
		@symlink('../../imgs/default', '../home/admin/imgs');
		@symlink('../../look/default', '../home/admin/look');
	}
	if (! file_exists('../home/guest')) {
		$__sInfo['error'] = "The 'guest' home directory can not be created.";
		$__sInfo['command'] = "mkdir('../home/guest', 0775, true)";
		$__sInfo['values'] = '';

		mkdir('../home/guest', 0775, true);
		symlink('../../imgs/default', '../home/guest/imgs');
		symlink('../../look/default', '../home/guest/look');
	}

	if (! file_exists('../data/_cron')) {
		$__sInfo['error'] = "The 'data/_cron' directory can not be created.";
		$__sInfo['command'] = "mkdir('../data/_cron', 0775, true)";
		$__sInfo['values'] = '';

		mkdir('../data/_cron', 0775, true);
	}
	if (! file_exists('../data/_logs')) {
		$__sInfo['error'] = "The 'data/_logs' directory can not be created.";
		$__sInfo['command'] = "mkdir('../data/_logs', 0775, true)";
		$__sInfo['values'] = '';

		mkdir('../data/_logs', 0775, true);
	}
	if (! file_exists('../data/_mail')) {
		$__sInfo['error'] = "The 'data/_mail' directory can not be created.";
		$__sInfo['command'] = "mkdir('../data/_mail', 0775, true)";
		$__sInfo['values'] = '';

		mkdir('../data/_mail', 0775, true);
	}
	if (! file_exists('../data/_modules')) {
		$__sInfo['error'] = "The 'data/_modules' directory can not be created.";
		$__sInfo['command'] = "mkdir('../data/_modules', 0775, true)";
		$__sInfo['values'] = '';

		mkdir('../data/_modules', 0775, true);
	}
	if (! file_exists('../data/_verify')) {
		$__sInfo['error'] = "The 'data/_verify' directory can not be created.";
		$__sInfo['command'] = "mkdir('../data/_verify', 0775, true)";
		$__sInfo['values'] = '';

		mkdir('../data/_verify', 0775, true);
	}

	if (! file_exists('../data/_modules/ApplicationSettings')) {
		$__sInfo['error'] = "The 'data/_modules/ApplicationSettings' directory can not be created.";
		$__sInfo['command'] = "mkdir('../data/_modules/ApplicationSettings', 0775, true)";
		$__sInfo['values'] = '';

		mkdir('../data/_modules/Application', 0775, true);
	}
	if (! file_exists('../data/_modules/Employees')) {
		$__sInfo['error'] = "The 'data/_modules/Employees' directory can not be created.";
		$__sInfo['command'] = "mkdir('../data/_modules/Employees', 0775, true)";
		$__sInfo['values'] = '';

		mkdir('../data/_modules/Employees', 0775, true);
	}

	if ($_POST['sOperation'] == 'app') {					# if the user selected a "Business Application" operation, then...
		if (! file_exists('../data/_modules/BusinessManagement')) {
# VER2 - retrieve the $__sInfo['error'] value from the ../code/BusinessManagement.lang.js file (so that different languages can systematically be installed); do this throughout the project for this variable -AND- <s><msg> + <f></msg>
			$__sInfo['error'] = "The 'data/_modules/BusinessManagement' directory can not be created.";
			$__sInfo['command'] = "mkdir('../data/_modules/BusinessManagement', 0775, true)";
			$__sInfo['values'] = '';

			mkdir('../data/_modules/BusinessManagement', 0775, true);
		}
	}
}


function setupDatabase($Password = 'admin') {
# the function to setup the project via DB i/o and file copying
	global $_,$__sInfo,$_sSupportEmail,$DB_PRFX,$DB_HOST,$DB_NAME,$DB_RWUN,$_LinkDB;

	if ($_POST['bExistingDatabase']) { return true; }		# if the user indicated that there is already existing data in the database, then no need to go any further with this function

# REMOVED - 2025/03/01 - these values will all reside in the config.php file
#	# check if the database tables already exist in the DB or create them if not!
#	$__sInfo['error'] = "The 'ApplicationSettings' table can not be added to the database.";	# https://stackoverflow.com/questions/28756236/php-and-mysqli-check-to-see-if-the-table-exists-and-if-it-doesnt-create-it
#	$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."ApplicationSettings (
#		id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
#		adminType VARCHAR(8) NOT NULL DEFAULT 'employee',
#		adminID BIGINT,
#
#		socialURI VARCHAR(128),
#		moduleUpdate VARCHAR(10) NOT NULL DEFAULT 'automatic',
#		moduleInstall VARCHAR(10) NOT NULL DEFAULT 'automatic'
#	)";
#	$__sInfo['values'] = 'None';
#	$stmt = $_LinkDB->query($__sInfo['command']);
#	if ($stmt === FALSE) { return false; }


	$__sInfo['error'] = "The 'Application_Associated' table can not be added to the database.";
	$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."Application_Associated (
		id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
		sourceName VARCHAR(32) NOT NULL,
		sourceTable VARCHAR(32) NOT NULL,
		sourceID BIGINT NOT NULL,
		targetName VARCHAR(32) NOT NULL,
		targetTable VARCHAR(32) NOT NULL,
		targetID BIGINT NOT NULL,

		created DATETIME NOT NULL,
		updated DATETIME NOT NULL
	)";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }


	$__sInfo['error'] = "The 'Application_Commerce' table can not be added to the database.";
	$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."Application_Commerce (
		id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
		tblName VARCHAR(48) NOT NULL,
		rowID BIGINT NOT NULL,
		uri VARCHAR(128) NOT NULL,
		sid VARCHAR(128) NOT NULL,

		createdBy BIGINT NOT NULL,
		createdOn DATETIME NOT NULL,
		updatedBy BIGINT NOT NULL,
		updatedOn DATETIME NOT NULL
	)";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }


	$__sInfo['error'] = "The 'Application_Contacts' table can not be added to the database.";
	$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."Application_Contacts (
		id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
		type VARCHAR(12) NOT NULL DEFAULT 'employee',
		rowID BIGINT NOT NULL,

		OPoID VARCHAR(64),
		name VARCHAR(128) NOT NULL,
		workEmail VARCHAR(128),
		workPhone VARCHAR(15),
		workExt VARCHAR(7),
		workMobile VARCHAR(15),
		workMobileSMS TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
		workMobileEmail TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
		jobTitle VARCHAR(48)
	)";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }


	$__sInfo['error'] = "The 'Application_Data' table can not be added to the database.";
	$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."Application_Data (
		id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
		`table` VARCHAR(128) NOT NULL,
		rowID BIGINT NOT NULL,
		name VARCHAR(48) NOT NULL,
		filename VARCHAR(24) NOT NULL,

		createdBy BIGINT,
		createdOn DATETIME NOT NULL,
		updatedBy BIGINT,
		updatedOn DATETIME NOT NULL
	)";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }


# UPDATED 2025/07/14 - this is a core aspect of the application (since it's used by multiple modules)
#	if ($_POST['sOperation'] == 'app') {					# if the user selected a "Business Application" operation, then...
		$__sInfo['error'] = "The 'Application_FreightAccounts' table can not be added to the database.";
		$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."Application_FreightAccounts (
			id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
			type VARCHAR(12) NOT NULL DEFAULT 'customer',
			rowID BIGINT NOT NULL,

			name VARCHAR(48) NOT NULL,
			account VARCHAR(128) NOT NULL
		)";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }
#	}


	$__sInfo['error'] = "The 'Application_Notes' table can not be added to the database.";
	$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."Application_Notes (
		id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
		type VARCHAR(12) NOT NULL DEFAULT 'employee',
		rowID BIGINT NOT NULL,
		creatorID BIGINT NOT NULL,
		access VARCHAR(12) NOT NULL DEFAULT 'everyone',
		note TEXT NOT NULL,

		created DATETIME NOT NULL,
		updated DATETIME NOT NULL
	)";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }


	$__sInfo['error'] = "The 'Application_Specs' table can not be added to the database.";
	$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."Application_Specs (
		id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
		`table` VARCHAR(12) NOT NULL DEFAULT 'inventory',
		type VARCHAR(8) NOT NULL DEFAULT 'vendor',
		rowID BIGINT NOT NULL,

		title01 VARCHAR(24),
		value01 VARCHAR(64),
		title02 VARCHAR(24),
		value02 VARCHAR(64),
		title03 VARCHAR(24),
		value03 VARCHAR(64),
		title04 VARCHAR(24),
		value04 VARCHAR(64),
		title05 VARCHAR(24),
		value05 VARCHAR(64),
		title06 VARCHAR(24),
		value06 VARCHAR(64),
		title07 VARCHAR(24),
		value07 VARCHAR(64),
		title08 VARCHAR(24),
		value08 VARCHAR(64),
		title09 VARCHAR(24),
		value09 VARCHAR(64),
		title10 VARCHAR(24),
		value10 VARCHAR(64),
		title11 VARCHAR(24),
		value11 VARCHAR(64),
		title12 VARCHAR(24),
		value12 VARCHAR(64),
		title13 VARCHAR(24),
		value13 VARCHAR(64),
		title14 VARCHAR(24),
		value14 VARCHAR(64),
		title15 VARCHAR(24),
		value15 VARCHAR(64),
		title16 VARCHAR(24),
		value16 VARCHAR(64),
		title17 VARCHAR(24),
		value17 VARCHAR(64),
		title18 VARCHAR(24),
		value18 VARCHAR(64),
		title19 VARCHAR(24),
		value19 VARCHAR(64),
		title20 VARCHAR(24),
		value20 VARCHAR(64),

		created DATETIME NOT NULL,
		updated DATETIME NOT NULL
	)";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }


# LEFT OFF - add in a enable/disable toggle column since we MUST keep module UID's constant (since Module Maker relies on this!!!!); so when modules are uninstalled, they have to leave this information and just disable their record
	$__sInfo['error'] = "The 'ApplicationSettings_Modules' table can not be added to the database.";
	$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."ApplicationSettings_Modules (
		id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
		name VARCHAR(24) NOT NULL,
		icon VARCHAR(48) NOT NULL,

		UNIQUE (name)
	)";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }


	$__sInfo['error'] = "The 'ApplicationSettings_Groups' table can not be added to the database.";
	$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."ApplicationSettings_Groups (
		id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
		name VARCHAR(24) NOT NULL,
		icon VARCHAR(48) NOT NULL,

		UNIQUE (name)
	)";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }


	$__sInfo['error'] = "The 'ApplicationSettings_Grouped' table can not be added to the database.";
	$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."ApplicationSettings_Grouped (
		id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
		groupID BIGINT NOT NULL,
		moduleID BIGINT NOT NULL
	)";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }


# LEFT OFF - rename this 'BusinessManagement'
	if ($_POST['sOperation'] == 'app') {					# if the user selected a "Business Application" operation, then...
		$__sInfo['error'] = "The 'BusinessConfiguration' table can not be added to the database.";
		$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."BusinessConfiguration (
			id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
			name VARCHAR(128) NOT NULL,
			type VARCHAR(10) NOT NULL DEFAULT 'solo',
			fein VARCHAR(10) NOT NULL,
			salesTax VARCHAR(16),
			salesTaxRate DECIMAL(4,2) NOT NULL DEFAULT '0.00',
			unemploymentTax VARCHAR(16),
			unemploymentTaxRate DECIMAL(4,2) NOT NULL DEFAULT '0.00',
			founded DATE NOT NULL,
			foundedCountry VARCHAR(2) NOT NULL DEFAULT 'us',
			fiscal DATE NOT NULL,
			timezone VARCHAR(3),
			beginTime TIME NOT NULL DEFAULT '080000',
			endTime TIME NOT NULL DEFAULT '170000',
			phone VARCHAR(15),
			fax VARCHAR(15),
			website VARCHAR(128),
			mainAddr1 VARCHAR(48) NOT NULL,
			mainAddr2 VARCHAR(48),
			mainCity VARCHAR(48) NOT NULL,
			mainState VARCHAR(2) NOT NULL,
			mainZip VARCHAR(10) NOT NULL,
			mainCountry VARCHAR(2) NOT NULL,
			billAddr1 VARCHAR(48) NOT NULL,
			billAddr2 VARCHAR(48),
			billCity VARCHAR(48) NOT NULL,
			billState VARCHAR(2) NOT NULL,
			billZip VARCHAR(10) NOT NULL,
			billCountry VARCHAR(2) NOT NULL,
			shipAddr1 VARCHAR(48) NOT NULL,
			shipAddr2 VARCHAR(48),
			shipCity VARCHAR(48) NOT NULL,
			shipState VARCHAR(2) NOT NULL,
			shipZip VARCHAR(10) NOT NULL,
			shipCountry VARCHAR(2) NOT NULL,
			merchant VARCHAR(12),
			merchantID VARCHAR(128),
			OPoID VARCHAR(64),
			bbn TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
			payDay DATE NOT NULL,
			payTerm VARCHAR(10) NOT NULL DEFAULT 'weekly'
		)";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }


# LEFT OFF - rename this 'BusinessManagement_Additional'
		$__sInfo['error'] = "The 'BusinessConfiguration_Additional' table can not be added to the database.";
		$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."BusinessConfiguration_Additional (
			id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
			type VARCHAR(8) NOT NULL DEFAULT 'location',
			name VARCHAR(48) NOT NULL,
			OPoID VARCHAR(64),
			phone VARCHAR(15),
			fax VARCHAR(15),
			website VARCHAR(128),
			mainAddr1 VARCHAR(48) NOT NULL,
			mainAddr2 VARCHAR(48),
			mainCity VARCHAR(48) NOT NULL,
			mainState VARCHAR(2) NOT NULL,
			mainZip VARCHAR(10) NOT NULL,
			mainCountry VARCHAR(2) NOT NULL,
			timezone VARCHAR(3),
			beginTime TIME,
			endTime TIME,
			founded DATE,
			foundedCountry VARCHAR(2),
			status VARCHAR(10),
			salesTax VARCHAR(16),
			salesTaxRate DECIMAL(4,2) NOT NULL DEFAULT '0.00',
			rating DECIMAL(3,2)
		)";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }


# LEFT OFF - rename this 'BusinessManagement__BankAccounts'
		$__sInfo['error'] = "The 'BusinessConfiguration_BankAccounts' table can not be added to the database.";
		$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."BusinessConfiguration_BankAccounts (
			id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
			type VARCHAR(12) NOT NULL DEFAULT 'business',
			rowID BIGINT NOT NULL,

			name VARCHAR(48) NOT NULL,
			routing VARCHAR(24) NOT NULL,
			account VARCHAR(128) NOT NULL,
			checkType VARCHAR(24),
			checkNo INT UNSIGNED DEFAULT '1000'
		)";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }


# LEFT OFF - add in a BusinessManagement__CryptoAccounts table
#	id
#	type VARCHAR		(e.g. bitcoin, ethereum, litecoin, etc)
#	name VARCHAR		(e.g. Accounts Payable, Accounts Receivable, Cold Wallet, etc)
#	address VARCHAR		the wallet address


# LEFT OFF - rename this 'BusinessManagement__CreditCards'
		$__sInfo['error'] = "The 'BusinessConfiguration_CreditCards' table can not be added to the database.";
		$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."BusinessConfiguration_CreditCards (
			id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
			name VARCHAR(48) NOT NULL,
			number VARCHAR(128) NOT NULL,
			employeeID BIGINT NOT NULL,
			type VARCHAR(8) NOT NULL,
			cvv2 VARCHAR(128) NOT NULL,
			expMonth TINYINT(2) UNSIGNED NOT NULL DEFAULT '01',
			expYear TINYINT(2) UNSIGNED NOT NULL
		)";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }


# LEFT OFF - rename this 'BusinessManagement__Departments'
		$__sInfo['error'] = "The 'BusinessConfiguration_Departments' table can not be added to the database.";
		$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."BusinessConfiguration_Departments (
			id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
			name VARCHAR(48) NOT NULL
		)";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }


# LEFT OFF - rename this 'BusinessManagement__Positions'
		$__sInfo['error'] = "The 'BusinessConfiguration_Positions' table can not be added to the database.";
		$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."BusinessConfiguration_Positions (
			id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
			name VARCHAR(48) NOT NULL,
			deptID BIGINT NOT NULL,
			type VARCHAR(10) NOT NULL DEFAULT 'full-time',
			pay VARCHAR(16) NOT NULL DEFAULT 'hourly',
			basePay DECIMAL(10,2) NOT NULL DEFAULT '10000.00',
			OTRate DECIMAL(3,2) NOT NULL DEFAULT '1.5',
			PTORate DECIMAL(4,2) NOT NULL DEFAULT '0.33',
			SickRate DECIMAL(4,2) NOT NULL DEFAULT '0.33',
			payMileage DECIMAL(4,2) NOT NULL DEFAULT '0.00',
			payCOLA DECIMAL(4,2) NOT NULL DEFAULT '0.00',
			payPerDiem DECIMAL(7,2) NOT NULL DEFAULT '0.00'
		)";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }
	}


# VER2 - add in an 'id_auth' that corresponds to the returned UID from a 'data/auth.php' file; id_auth BIGINT UNIQUE,
	$__sInfo['error'] = "The 'Employees' table can not be added to the database.";
	$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."Employees (
		id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
		sid VARCHAR(255),
		manager TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
		disabled TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
		attempts TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
		status VARCHAR(16) NOT NULL DEFAULT 'active',	
		username VARCHAR(128) NOT NULL,
		password VARCHAR(255) NOT NULL,
		decryptGBL VARCHAR(128) NOT NULL,
		pes VARCHAR(128) NOT NULL,
		timeStatus VARCHAR(7) NOT NULL DEFAULT 'in',
		timeAvail VARCHAR(7) NOT NULL DEFAULT 'yes',

		name VARCHAR(128) NOT NULL,
		OPoID VARCHAR(64),
		homePhone VARCHAR(128),
		homeMobile VARCHAR(128),
		homeMobileSMS TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
		homeMobileEmail TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
		homeEmail VARCHAR(128),
		homeAddr1 VARCHAR(128) NOT NULL,
		homeAddr2 VARCHAR(48),
		homeCity VARCHAR(48) NOT NULL,
		homeState VARCHAR(2) NOT NULL,
		homeZip VARCHAR(10) NOT NULL,
		homeCountry VARCHAR(2) NOT NULL,
		workPhone VARCHAR(15),
		workExt VARCHAR(7),
		workMobile VARCHAR(15),
		workMobileSMS TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
		workMobileEmail TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
		workEmail VARCHAR(128),
		workAddr1 VARCHAR(48) NOT NULL,
		workAddr2 VARCHAR(48),
		workCity VARCHAR(48) NOT NULL,
		workState VARCHAR(2) NOT NULL,
		workZip VARCHAR(10) NOT NULL,
		workCountry VARCHAR(2) NOT NULL,
		locationID BIGINT NOT NULL,
		departmentID BIGINT NOT NULL,
		supervisorID BIGINT NOT NULL,
		positionID BIGINT NOT NULL,
		payTerms VARCHAR(10) NOT NULL DEFAULT 'full-time',
		payType VARCHAR(16) NOT NULL DEFAULT 'hourly',
		basePay DECIMAL(10,2) NOT NULL DEFAULT '10000.00',
		OTRate DECIMAL(3,2) NOT NULL DEFAULT '1.5',
		PTORate DECIMAL(4,2) NOT NULL DEFAULT '0.33',
		SickRate DECIMAL(4,2) NOT NULL DEFAULT '0.33',
		payCOLA DECIMAL(4,2) NOT NULL DEFAULT '0.00',
		payMileage DECIMAL(4,2) NOT NULL DEFAULT '0.00',
		payPerDiem DECIMAL(7,2) NOT NULL DEFAULT '0.00',
		hired DATE NOT NULL,
		driversLicense VARCHAR(128),
		gender VARCHAR(8) NOT NULL,
		ssn VARCHAR(128) NOT NULL,
		dob DATE NOT NULL,
		race VARCHAR(10) NOT NULL DEFAULT 'caucasian',
		married VARCHAR(8) NOT NULL DEFAULT 'single',
		withholdings DECIMAL(6,2) NOT NULL DEFAULT '0.00',
		additional SMALLINT NOT NULL DEFAULT '0',
		dependents TINYINT(2) UNSIGNED NOT NULL DEFAULT '0',

		question1 VARCHAR(128) NOT NULL,
		question2 VARCHAR(128) NOT NULL,
		question3 VARCHAR(128) NOT NULL,
		answer1 VARCHAR(128) NOT NULL,
		answer2 VARCHAR(128) NOT NULL,
		answer3 VARCHAR(128) NOT NULL,

		created DATETIME NOT NULL,
		updated DATETIME NOT NULL,
		login DATETIME NOT NULL,
		logout DATETIME NOT NULL,

		UNIQUE (username,workEmail)
	)";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }


# LEFT OFF - rename this 'Employees__Access'
	$__sInfo['error'] = "The 'Employees_Access' table can not be added to the database.";
	$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."Employees_Access (
		id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
		employeeID BIGINT NOT NULL,
		moduleID BIGINT NOT NULL,
		`read` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
		`write` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
		`add` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
		del TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'
	)";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }


# LEFT OFF - rename this 'Employees__Donation'
	$__sInfo['error'] = "The 'Employees_Donation' table can not be added to the database.";
	$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."Employees_Donation (
		id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
		sourceID BIGINT NOT NULL,
		targetID BIGINT NOT NULL,
		hours DECIMAL (4,2),
		type VARCHAR(4) NOT NULL DEFAULT 'pto',

		created DATETIME NOT NULL,
		updated DATETIME NOT NULL
	)";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }


	$__sInfo['error'] = "The 'Employees_Timesheets' table can not be added to the database.";
	$__sInfo['command'] = "CREATE TABLE IF NOT EXISTS ".$DB_PRFX."Employees_Timesheets (
		id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
		employeeID BIGINT NOT NULL,
		type VARCHAR(7) NOT NULL DEFAULT 'in',
		time DATETIME NOT NULL,
		memo VARCHAR(64),

		createdBy BIGINT,
		createdOn DATETIME NOT NULL,
		updatedBy BIGINT,
		updatedOn DATETIME NOT NULL
	)";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }


# LEFT OFF - rename this 'Application_Funds'	bizcfg_funds?
	if ($_POST['bHostedService']) {					# if the user selected to use "Multiple Interfaces", then...
		$__sInfo['error'] = "The 'Funds' table can not be added to the database.";
		$__sInfo['command'] = "CREATE TABLE ".$DB_PRFX."Funds (
			id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE,
			type VARCHAR(6) NOT NULL DEFAULT 'system',		# valid values are: 'paypal' (e.g. account funding), 'system' (e.g. user changed config), 'charge' (e.g. a monthly charge)
			amount SMALLINT NOT NULL,				# the transaction amount: how much paid (via paypal), a zero value ('0.00' for 'system'), charge amount (monthly webfice.com fee)
			balance SMALLINT NOT NULL DEFAULT '0.00',		# the balance as of this transaction
			custom1 VARCHAR(32) NOT NULL,				# the transaction ID from paypal || the login count to incur a monthly charge (for 'system' and 'charge' types)
			custom2 VARCHAR(128) NOT NULL,				# the item ID from paypal (e.g. webficeSUB, webficeSC) || charge for tech support: 0=no, 1=yes (for 'system' and 'charge' types)

			createdOn DATETIME NOT NULL
		)";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }
	}




	# Create the default table values for the "included" modules
	# NOTE: we don't have to perform any checks for existing records because there can't be any duplicates with the 'name' column
	$__sInfo['error'] = "The 'Application Settings' module can not be added to the database.";
	$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Modules (name,icon) VALUES ('Application Settings','ApplicationSettings.png')";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }

	$__sInfo['error'] = "The 'Employees' module can not be added to the database.";
	$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Modules (name,icon) VALUES ('Employees','Employees.png')";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }

	if ($_POST['sOperation'] == 'app') {					# if the user selected a "Business Application" operation, then...
		$__sInfo['error'] = "The 'Business Management' module can not be added to the database.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Modules (name,icon) VALUES ('Business Management','BusinessManagement.png')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }
	}




	# Create the default table values for the module groups
	# First, lets clear the table of any existing records (e.g. this is a subsequent run after encountering an error, and the user may have changed this)
	$__sInfo['error'] = "A failure occurred attempting to erase any prior contents from the module groupings.";
	$__sInfo['command'] = "TRUNCATE TABLE ".$DB_PRFX."ApplicationSettings_Groups";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	#if ($stmt === FALSE) { return false; }					# this was removed to prevent any issues with the above code running initially when there aren't any records in the table
	#	WARNING: issuing a 'TRUNCATE' call resets the unique index value back to 1! (which is desired in this case)
	#	https://stackoverflow.com/questions/3000917/delete-all-from-table
	# Now add the appropriate records
	if ($_POST['sInterface'] == 'pro') {					# if the user selected a "Professional" operation, then...
		# NOTE: we added the IGNORE syntax to silently ignore the INSERT command instead of updating or inserting
		$__sInfo['error'] = "The 'Productivity' module group can not be added to the database.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Groups (name,icon) VALUES ('Productivity','group.departments.png')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }

		$__sInfo['error'] = "The 'Administration' module group can not be added to the database.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Groups (name,icon) VALUES ('Management','group.administration.png')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }
	} else {								# otherwise, we need to configure as "Enterprise", so...
		# NOTE: we added the IGNORE syntax to silently ignore the INSERT command instead of updating or inserting
		$__sInfo['error'] = "The 'Departments' module group can not be added to the database.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Groups (name,icon) VALUES ('Departments','group.departments.png')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }

		$__sInfo['error'] = "The 'Administration' module group can not be added to the database.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Groups (name,icon) VALUES ('Administration','group.administration.png')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }

		$__sInfo['error'] = "The 'Employees' module group can not be added to the database.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Groups (name,icon) VALUES ('Employees','group.employees.png')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }

		$__sInfo['error'] = "The 'Management' module group can not be added to the database.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Groups (name,icon) VALUES ('Management','group.management.png')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }

		$__sInfo['error'] = "The 'Sales' module group can not be added to the database.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Groups (name,icon) VALUES ('Sales','group.sales.png')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }

		$__sInfo['error'] = "The 'Service' module group can not be added to the database.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Groups (name,icon) VALUES ('Service','group.service.png')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }

		$__sInfo['error'] = "The 'Ship & Receive' module group can not be added to the database.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Groups (name,icon) VALUES ('Ship & Receive','group.ship_and_receive.png')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }

		$__sInfo['error'] = "The 'Support' module group can not be added to the database.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Groups (name,icon) VALUES ('Support','group.support.png')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }
	}




	# Create the default table values to link the modules with the groups
	# First, lets clear the table of any existing records (e.g. this is a subsequent run after encountering an error, and the user may have changed this)
	$__sInfo['error'] = "A failure occurred attempting to erase any prior contents from the grouped modules.";
	$__sInfo['command'] = "TRUNCATE TABLE ".$DB_PRFX."ApplicationSettings_Grouped";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	#if ($stmt === FALSE) { return false; }					# this was removed to prevent any issues with the above code running initially when there aren't any records in the table
	#	WARNING: issuing a 'TRUNCATE' call resets the unique index value back to 1! (which is desired in this case)
	#	https://stackoverflow.com/questions/3000917/delete-all-from-table
	# Now add the appropriate records
	if ($_POST['sInterface'] == 'pro') {					# if the user selected a "Professional" operation, then...
		// Add Employees to the 'Productivity' group (so employee access to their own records)
		$__sInfo['error'] = "The 'Employees' module can not be added to the 'Employees' group.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Grouped (groupID,moduleID) VALUES ('1','2')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }

		// Add them all to the 'Management' group
		$__sInfo['error'] = "The 'System Configuration' module can not be added to the 'Administration' group.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Grouped (groupID,moduleID) VALUES ('2','1')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }

		$__sInfo['error'] = "The 'Employees' module can not be added to the 'Employees' group.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Grouped (groupID,moduleID) VALUES ('2','2')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }

		if ($_POST['sOperation'] == 'app') {				# if the user selected a "Business Application" operation, then...
			$__sInfo['error'] = "The 'Business Management' module can not be added to the 'Management' group.";
			$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Grouped (groupID,moduleID) VALUES ('2','3')";
			$__sInfo['values'] = 'None';
			$stmt = $_LinkDB->query($__sInfo['command']);
			if ($stmt === FALSE) { return false; }
		}
	} else {								# otherwise, we need to configure as "Enterprise", so...
		$__sInfo['error'] = "The 'System Configuration' module can not be added to the 'Administration' group.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Grouped (groupID,moduleID) VALUES ('2','1')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }

		$__sInfo['error'] = "The 'Employees' module can not be added to the 'Employees' group.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Grouped (groupID,moduleID) VALUES ('3','2')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }

		if ($_POST['sOperation'] == 'app') {				# if the user selected a "Business Application" operation, then...
			$__sInfo['error'] = "The 'Business Management' module can not be added to the 'Administration' group.";
			$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Grouped (groupID,moduleID) VALUES ('2','3')";
			$__sInfo['values'] = 'None';
			$stmt = $_LinkDB->query($__sInfo['command']);
			if ($stmt === FALSE) { return false; }

			$__sInfo['error'] = "The 'Business Management' module can not be added to the 'Management' group.";
			$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."ApplicationSettings_Grouped (groupID,moduleID) VALUES ('4','3')";
			$__sInfo['values'] = 'None';
			$stmt = $_LinkDB->query($__sInfo['command']);
			if ($stmt === FALSE) { return false; }
		}
	}




file_put_contents('debug.txt', "Employees pre\n", FILE_APPEND);
	# if the 'Employees' table was NOT mapped to another software, then create the default 'admin' account
	if ($_POST['sTableName'] == "" || ! file_exists("../../denaccess")) {
		$__sInfo['error'] = "The default administrator acount can not be found in the database.";
		$__sInfo['command'] = "SELECT * FROM ".PREFIX."Employees WHERE username='admin' LIMIT 1";
		$__sInfo['values'] = '';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt->num_rows < 1 || ($stmt->num_rows > 0 && ! file_exists("../../denaccess"))) {				# if the default 'admin' account is not already created, then...
file_put_contents('debug.txt', "Employees inside\n", FILE_APPEND);
			# create the 'denaccess' file
			if (! file_exists("../../denaccess")) {
				$den = Cipher::create_encryption_key();
										
				$__sInfo['error'] = "The encryption/decryption file can not be opened.";
				$__sInfo['command'] = "fopen(\"../../denaccess\", 'w')";
				$__sInfo['values'] = '';
				$fh = fopen("../../denaccess", 'w');
				fwrite($fh, $den."\n");
				fclose($fh);
			}

			# create the account's (p)ersonal (e)ncryption (s)tring [PES] value
			$salt = file_get_contents('../../denaccess');
			$pes = Cipher::create_encryption_key();
			$encPES = Cipher::encrypt($pes, $salt);

			# this section deals with the encryption of the users password
# VER2- update these to sha256
			$hash = md5($Password);					# hash the password and store that value, not the actual password!!!	https://stackoverflow.com/questions/9262109/simplest-two-way-encryption-using-php
			$encrypted = Cipher::encrypt($hash, $pes);		# create the default password string for the DB - 'admin'		NOTE: this is defined as a default value for the function above

			if ($stmt->num_rows < 1) {				# if we need to create the admin account, then...
file_put_contents('debug.txt', "Employees create\n", FILE_APPEND);
				$__sInfo['error'] = "The default 'admin' account can not be added to the database.";
				$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."Employees (manager,disabled,status,username,password,decryptGBL,pes,timeStatus,timeAvail,name,homeAddr1,homeCity,homeState,homeZip,homeCountry,workEmail,workAddr1,workCity,workState,workZip,workCountry,locationID,departmentID,supervisorID,positionID,hired,gender,ssn,dob,question1,question2,question3,answer1,answer2,answer3,created,updated) VALUES ('1','0','active','admin','".$encrypted."','','".$encPES."','out','no','Administrator',' ',' ',' ',' ',' ','".$_sSupportEmail."',' ',' ',' ',' ',' ','0','0','0','0','".gmdate('Y-m-d',time())."','male',' ','".date('Y-m-d',time())."',\"What is the SQL server URI used for this software?\",\"What is the SQL database name used for this software?\",\"What is the SQL server username (read/write) used for this software\",\"".Cipher::encrypt($DB_HOST, $pes)."\",\"".Cipher::encrypt($DB_NAME, $pes)."\",\"".Cipher::encrypt($DB_RWUN, $pes)."\",'".$_."','".$_."')";
			} else {						# otherwise we are just missing the denaccess file (from a migration or botched installation), so...
file_put_contents('debug.txt', "Employees update\n", FILE_APPEND);
				$__sInfo['error'] = "The default 'admin' account can not be updated in the database.";
				$__sInfo['command'] = "UPDATE ".$DB_PRFX."Employees SET password=\"".$encrypted."\",pes=\"".$encPES."\",question1=\"What is the SQL server URI used for this software?\",question2=\"What is the SQL database name used for this software?\",question3=\"What is the SQL server username (read/write) used for this software\",answer1=\"".Cipher::encrypt($DB_HOST, $pes)."\",answer2=\"".Cipher::encrypt($DB_NAME, $pes)."\",answer3=\"".Cipher::encrypt($DB_RWUN, $pes)."\",updated='".$_."' WHERE id=1";
			}
			$__sInfo['values'] = 'None';
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			$stmt->execute();
		}
	#} else {
	#	Otherwise the user has configured to use the mapped user accounts table.
	#	Since we don't know how the passwords are being protected, we will have
	#	to rely on an existing 'admin' account to already be setup.
	}
file_put_contents('debug.txt', "Employees post\n", FILE_APPEND);




	# define full access for the 'admin' account to the installed modules (Application Settings, Employees, [Business Management])
	# First, lets clear the table of any existing records (e.g. this is a subsequent run after encountering an error, and the user may have changed this)
	$__sInfo['error'] = "A failure occurred attempting to erase any prior contents with the employee access.";
	$__sInfo['command'] = "TRUNCATE TABLE ".$DB_PRFX."Employees_Access";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	#if ($stmt === FALSE) { return false; }					# this was removed to prevent any issues with the above code running initially when there aren't any records in the table
	#	WARNING: issuing a 'TRUNCATE' call resets the unique index value back to 1! (which is desired in this case)
	#	https://stackoverflow.com/questions/3000917/delete-all-from-table
	# Now add the appropriate records
	$__sInfo['error'] = "The access permissions of the 'Application Settings' module can not be added to the database.";
	$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."Employees_Access (employeeID,moduleID,`read`,`write`,`add`,`del`) VALUES ('1','1','1','1','1','1')";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }

	$__sInfo['error'] = "The access permissions of the 'Employees' module can not be added to the database.";
	$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."Employees_Access (employeeID,moduleID,`read`,`write`,`add`,`del`) VALUES ('1','2','1','1','1','1')";
	$__sInfo['values'] = 'None';
	$stmt = $_LinkDB->query($__sInfo['command']);
	if ($stmt === FALSE) { return false; }

	if ($_POST['sOperation'] == 'app') {					# if the user selected a "Business Application" operation, then...
		$__sInfo['error'] = "The access permissions of the 'Business Management' module can not be added to the database.";
		$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."Employees_Access (employeeID,moduleID,`read`,`write`,`add`,`del`) VALUES ('1','3','1','1','1','1')";
		$__sInfo['values'] = 'None';
		$stmt = $_LinkDB->query($__sInfo['command']);
		if ($stmt === FALSE) { return false; }
	}




# DEPRECATED 2025/05/22
#	# create the default 'Application Settings' module configuration
#	# First, lets clear the table of any existing records (e.g. this is a subsequent run after encountering an error, and the user may have changed this)
#	$__sInfo['error'] = "A failure occurred attempting to erase any prior contents from the Application Settings.";
#	$__sInfo['command'] = "TRUNCATE TABLE ".$DB_PRFX."SystemConfiguration";
#	$__sInfo['values'] = 'None';
#	$stmt = $_LinkDB->query($__sInfo['command']);
#	#if ($stmt === FALSE) { return false; }					# this was removed to prevent any issues with the above code running initially when there aren't any records in the table
#	#	WARNING: issuing a 'TRUNCATE' call resets the unique index value back to 1! (which is desired in this case)
#	#	https://stackoverflow.com/questions/3000917/delete-all-from-table
#	# Now add the appropriate records
#	$__sInfo['error'] = "The default 'Application Settings' can not be added to the database.";
#	$__sInfo['command'] = "INSERT IGNORE INTO ".$DB_PRFX."SystemConfiguration (adminID) VALUES ('1')";	# NOTE: this adds the whole record using the defaults for the other columns
#	$__sInfo['values'] = 'None';
#	$stmt = $_LinkDB->query($__sInfo['command']);
#	if ($stmt === FALSE) { return false; }
}


file_put_contents('debug.txt', "01\n", FILE_APPEND);


# EXIT IF APPROPRIATE	this triggers when this script is called like '. ../setup.php' from within another script (e.g. webfice.com) so that the above functions are accessible within it, but the below code isn't executed that's specific to the script
$included_files = get_included_files();
if ($included_files[0] != __FILE__) { return; }	# NOTE: no wrapper script should use code below this point!!!!




# Module Requirements				  NOTE: MUST come below Module Constant Definitions
require_once('_Project.php');
require_once('_Contact.php');
require_once('_Database.php');

# Start or resume the PHP session		  NOTE: gains access to $_SESSION variables in this script
session_start();


file_put_contents('debug.txt', "02 |".php_sapi_name()."|".$_SERVER['REMOTE_ADDR']."|\n", FILE_APPEND);


if (php_sapi_name() == 'cli' || empty($_SERVER['REMOTE_ADDR'])) {		# if this script was called from the cli (by the install.php script), then...	http://stackoverflow.com/questions/190759/can-php-detect-if-its-run-from-a-cron-job-or-from-the-command-line
	parse_str(implode('&', array_slice($argv, 1)), $_POST);			#   convert CLI passed parameters into the $_POST array for normal processing	http://php.net/manual/en/features.commandline.php#108883

	# add these constants to prevent errors/warnings with php
	$_SERVER['REMOTE_ADDR'] = 'cli';
	$_SERVER['HTTP_REFERER'] = '';
file_put_contents('debug.txt', "02a - text\n", FILE_APPEND);
} else {									# otherwise, this was called via the webpage GUI so...
	header('Content-Type: text/xml; charset=utf-8');
	echo "<?xml version='1.0' encoding='UTF-8'?>\n\n";
file_put_contents('debug.txt', "02a - XML\n", FILE_APPEND);
}

file_put_contents('debug.txt', "03\n", FILE_APPEND);

# define/transpose these values so all errors can be conveyed via email (which uses these and will cause errors if they are NOT present)
# Non-editable Definitions
if (file_exists('BusinessManagement.php'))
	{ define("PROJECT",'webBooks'); }
else
	{ define("PROJECT",'webWorks'); }
#    URI Definitions
$_sUriProject=substr($_SERVER['HTTP_REFERER'], 0, strrpos($_SERVER['HTTP_REFERER'],"/")).'/';	# removes the trailing portion from the URI (e.g. /default.php?p=tracker.setup.html)								# used in emails so the referenced images have a full URI; should NOT be used otherwise
$_sUriPayment='https://www.paypal.com/cgi-bin/webscr';						# used for payment processing (if applicable)
#    Log Definitions
$_sLogEmail='email.log';
$_sLogScript=SCRIPT.'.log';
$_sLogModule=MODULE.'.log';
$_sLogProject=PROJECT.'.log';
# Directory Definitions
#$_sDirCfgs='../data/_cfgs';			REMOVED - 2025/02/23
$_sDirCron='../data/_cron';
$_sDirData='../data';
$_sDirLogs='../data/_logs';
$_sDirMail='../data/_mail';
$_sDirTemp='../temp';
$_sDirVrfy='../data/_verify';
# Email Definitions
$_sAlertsName=$_POST['sAlertName'];
$_sAlertsEmail=$_POST['sAlertEmail'];
$_sSupportName=$_POST['sSupportName'];
$_sSupportEmail=$_POST['sSupportEmail'];
$_sSecurityName=$_POST['sSecurityName'];
$_sSecurityEmail=$_POST['sSecurityEmail'];
# System Variables
$_bDebug=0;
$_LinkDB;
$__sInfo=array();
$__sMsgs=array();
$__sUser=array();
$__sNull=array();

# SQL Server Definitions
$DB_HOST = $_POST['sSQLServer'];
$DB_NAME = $_POST['sDatabaseName'];
$DB_ROUN = $_POST['sROUsername'];
$DB_ROPW = $_POST['sROPassword'];
$DB_RWUN = $_POST['sRWUsername'];
$DB_RWPW = $_POST['sRWPassword'];
$DB_PRFX = $_POST['sTablePrefix'];
$AUTH_TB = $_POST['sTableName'];
$AUTH_ID = $_POST['sUIDColumn'];
$AUTH_UN = $_POST['sUsernameColumn'];
$AUTH_PW = $_POST['sPasswordColumn'];




# Global Constant Definitions															LEGACY - delete in a future version
define('TIMEOUT',0);
define("DBHOST",$_POST['sSQLServer']);
define("DBNAME",$_POST['sDatabaseName']);
define("PREFIX",$_POST['sTablePrefix']);
# Global Directory Definitions
$gbl_dirCron='../data/_cron';
$gbl_dirData='../data';
$gbl_dirLogs='../data/_logs';
$gbl_dirMail='../data/_mail';
$gbl_dirTemp='../temp';
# Global URI Definitions
$gbl_uriPPV='https://www.paypal.com/cgi-bin/webscr';
$gbl_uriProject=substr($_SERVER['HTTP_REFERER'], 0, strrpos($_SERVER['HTTP_REFERER'],"/")).'/';			# removes the trailing portion from the URI (e.g. /default.php?p=tracker.setup.html)
$gbl_uriContact=substr($_POST['sAlertEmail'], strpos($_POST['sAlertEmail'], '@')+1);				# store the domain portion of the 'No Reply' email address as the contact domain
# Global Mail Definitions
$gbl_nameNoReply = $_POST['sAlertName'];
$gbl_emailNoReply = $_POST['sAlertEmail'];
$gbl_nameHackers = $_POST['sSupportName'];
$gbl_emailHackers = $_POST['sSupportEmail'];
$gbl_nameCrackers = $_POST['sSecurityName'];
$gbl_emailCrackers = $_POST['sSecurityEmail'];
# Global System Variables
$gbl_debug=0;
$gbl_dbug=array();
$gbl_errs=array();							# VER2 - this is getting phased out in favor of gbl_info['error']
$gbl_info=array();
$gbl_msgs=array();
$gbl_user=array();
$gbl_null=array();
$linkDB;

$TIMEOUT = 0;
$DBHOST = $_POST['sSQLServer'];
$DBNAME = $_POST['sDatabaseName'];
$DBUNRO = $_POST['sROUsername'];
$DBPWRO = $_POST['sROPassword'];
$DBUNRW = $_POST['sRWUsername'];
$DBPWRW = $_POST['sRWPassword'];
$CAPTCHAS = $_POST['bUseCaptchas'];
$HOSTED = $_POST['bHostedService'];
$PREFIX = $_POST['sTablePrefix'];
$TABLE = $_POST['sTableName'];
$UID = $_POST['sUIDColumn'];
$USERNAME = $_POST['sUsernameColumn'];
$PASSWORD = $_POST['sPasswordColumn'];

# transpose these values so we don't run into other errors during the running of this script							LEGACY - delete in a future version
$gbl_info['admin'] = $_POST['sSupportName'];
$gbl_info['email'] = $_POST['sSupportEmail'];

file_put_contents('debug.txt', "04\n", FILE_APPEND);
#left off - add a gbl_errors['errors'] message and retest



# set the alert/error output to use the appropriate method
if (php_sapi_name() == 'cli' || empty($_SERVER['REMOTE_ADDR'])) { $__sInfo['output'] = 't'; } else { $__sInfo['output'] = 'x'; }

file_put_contents('debug.txt', "04a\n", FILE_APPEND);

# validate all submitted data		WARNING: this MUST come after the above declarations!
if (! validate($_POST['sAlertName'],128,'![=<>;]')) { exit(); }
if (! validate($_POST['sAlertEmail'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
if (! validate($_POST['sSupportName'],128,'![=<>;]')) { exit(); }
if (! validate($_POST['sSupportEmail'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
if (! validate($_POST['sSecurityName'],128,'![=<>;]')) { exit(); }
if (! validate($_POST['sSecurityEmail'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
if (! validate($_POST['sAdminPassword'],32,'')) { exit(); }
if (! validate($_POST['sInterface'],3,'{pro|ent}')) { exit(); }
if (! validate($_POST['sOperation'],3,'{app|fw}')) { exit(); }
if (! validate($_POST['bUseCaptchas'],5,'{true|false}')) { exit(); }
if (! validate($_POST['bHostedService'],5,'{true|false}')) { exit(); }
file_put_contents('debug.txt', "04b\n", FILE_APPEND);
# -----
if (! validate($_POST['sSQLServer'],128,'[^a-zA-Z0-9:\/%_\.\-]')) { exit(); }
if (! validate($_POST['sDatabaseName'],24,'[^a-zA-Z0-9_\-]')) { exit(); }
if (! validate($_POST['sROUsername'],32,'[^a-zA-Z0-9@\._\-]')) { exit(); }
if (! validate($_POST['sROPassword'],32,'')) { exit(); }
if (! validate($_POST['sRWUsername'],32,'[^a-zA-Z0-9@\._\-]')) { exit(); }
if (! validate($_POST['sRWPassword'],32,'')) { exit(); }
if (! validate($_POST['sTablePrefix'],16,'[^a-zA-Z0-9_\-]')) { exit(); }
file_put_contents('debug.txt', "04c\n", FILE_APPEND);
# -----
if (! validate($_POST['sTableName'],32,'[^a-zA-Z0-9_\-]')) { exit(); }
if (! validate($_POST['sUIDColumn'],32,'[^a-zA-Z0-9_\-]')) { exit(); }
if (! validate($_POST['sUsernameColumn'],32,'[^a-zA-Z0-9_\-]')) { exit(); }
if (! validate($_POST['sPasswordColumn'],32,'')) { exit(); }
file_put_contents('debug.txt', "04e\n", FILE_APPEND);
# -----
if (! validate($_POST['bExistingConfigs'],5,'{true|false}')) { exit(); }
if (! validate($_POST['bExistingDatabase'],5,'{true|false}')) { exit(); }


file_put_contents('debug.txt', "05\n", FILE_APPEND);


if ($_POST['A'] == 'install' && $_POST['T'] == 'software') {
file_put_contents('debug.txt', "06\n", FILE_APPEND);

# VER2 - add a file upload for the data/auth.php file to the setup page

# VER2 -	webBooks will manage it's own SID's.
#		the _login.php script SHOULD sync the username between the two databases (in case it got changed on the other end -OR- it can be used to login using a "cached" credential in the event connectivity to the LDAP/MS AD/Alt DB/etc server is severed
#		We will just use the other database's values for UID (so the username can be changed), Username, and Password.
#		The developer can include a file called 'data/session.php' which can include four functions:
#			"UID=status(USERNAME)" which simply returns 0 for false (upon encountering an issue with the account [e.g. it's locked]), the users' UID otherwise	<- call this one before any other function below; this one should check that the account is not locked or has some other issue with it before attempting to authenticate (to prevent repeated brute force attacking password)
#			"authenticate(UID,USERNAME,TYPE{'password'|'biometrics'|'Google Authenticator'|etc},VALUE{string associated with TYPE})" [simply returns true/false for success] that can be called in the _login.php that can be used to authenticate the password with their system (basically should just be a copy of their own code)
#
#		Without a 'data/session.php' file, and the authentication will be with another database (e.g. NOT LDAP/MS AD/etc) they can update their application to use our way of encryption/decryption and webBooks can authenticate using that field value in THEIR database
#		SO either the PASSWORD field has to have a value and webBooks will use it's native encryption/decryption methods against it -OR- a data/session.php file must be present
#		webBooks will need to store the UID value for the user from the other database/auth server so that the other fields can be updated in the event that the username got changed (can match against the UID in that event)

	# create the directory structure under 'data' and 'home'			WARNING: this MUST come FIRST since the logs directory needs to be created (in case an error is encountered below)!!!
	createDirectories();
	# WARNING: we had to set this value so that the user will know that an error with the database communication has occurred (in case there is an issue with email being sent, or a fake email was set)
	$__sInfo['prompt'] = "There was an issue connecting to the database server. The \"Support\"\ncontact provided will be emailed a detailed error message.";
	# connect to the DB now that we need to interact with it!			WARNING: this was put above everything else so that if we can't connect to the server and DB, then we won't have written bad envars files below!
	if (! connect2DB($DB_HOST,$DB_NAME,$DB_RWUN,$DB_RWPW,'oop',$__sInfo['output'])) { exit(); }
file_put_contents('debug.txt', "06a\n", FILE_APPEND);
	# setup the DB and install the project files
	if (! $_POST['bExistingDatabase']) { setupDatabase($_POST['sAdminPassword']); }
file_put_contents('debug.txt', "06b\n", FILE_APPEND);
file_put_contents('debug.txt', "06c\n", FILE_APPEND);
	# write the various configurations to file and then read them in for standard operation
	if (! $_POST['bExistingConfigs']) {
		writeSystemConfigs();
		writeDatabaseConfig();
	}
file_put_contents('debug.txt', "07\n", FILE_APPEND);

	if ($_POST['bHostedService']) {
# VER2 - if we don't setup a cron job, how are the automated things (e.g. maintenance.php) going to occur?
		sendMail($_sSupportEmail,$_sSupportName,$_sAlertsEmail,$_sAlertsName,'Your software is ready!',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_info.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".MODULE."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Your software is ready!</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nCongrats!<br />\n<br />\nFirst we would like to welcome you to our site and congratulate you on your first step at making the jobs of your staff members easier. You now have access to the most advanced business management software on the market! Below we will include your account information as well as the various ways to contact our parent company for any support you may require along the way. If your unique website address given below is too difficult to remember, feel free to use <a href='".$_sUriProject."/services'>".$_sUriProject."/services</a> to quickly navigate to your individual hosted service.<br />\n<br />\n<br />\nUsername: admin<br />\nPassword: [LEFT BLANK FOR SECURITY]<br />\nWebsite: <a href='".$_sUriProject."'>".$_sUriProject."</a><br />\n<br />\n<br />\nCliquesoft Support:<br />\nHours: Mon - Fri | 8am - 5pm EST<br />\nPhone: 407-770-1776<br />\nEmail: support@cliquesoft.org<br />\nForums: <a href='https://forums.cliquesoft.org'>https://forums.cliquesoft.org</a><br />\nWebsite: <a href='https://www.cliquesoft.org'>https://www.cliquesoft.org</a><br />\n\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
	} else {				# otherwise we need to setup the crontab
		if (! file_exists("../data/_cron/maintenance")) {
			$__sInfo['error'] = "The '../data/_cron/maintenance' config file can not be created.";
			$__sInfo['command'] = "fopen('../data/_cron/maintenance', 'w')";
			$__sInfo['values'] = 'None';

			$fh = fopen("../data/_cron/maintenance", 'w');
			fwrite($fh, "# email address of person or group who needs execution reports\n");
			fwrite($fh, "MAILTO='".$_sAlertsEmail."'\n\n");
			fwrite($fh, "# minute (0-59),\n");
			fwrite($fh, "# |	hour (0-23),\n");
			fwrite($fh, "# |	|	day of the month (1-31),\n");
			fwrite($fh, "# |	|	|	month of the year (1-12),\n");
			fwrite($fh, "# |	|	|	|	day of the week (0-6 with 0=Sunday).\n");
			fwrite($fh, "# |	|	|	|	|	commands\n\n");
			fwrite($fh, "0	23	*	*	*	cd ".getcwd()."; ./maintenance.php\n");						# WARNING: the maintenance.php script needs to be run from the 'code' directory, not 'data/_cron' (or else the paths in the script will be incorrect and it will fail)
			fclose($fh);

			$crondir = getcwd();					# gets the current directory (since this is where the software is installed)
			$crondir = substr($crondir, 0, -5).'/data/_cron';	# replaces the trailing '/code' portion of the pwd with '/data/_cron'
# DEBUG - uncomment for live; post-development
#			exec('echo -e "$(crontab -l 2>/dev/null)\n$(cat "'.$crondir.'/maintenance" | sed "/#.*/d")" | crontab -');			# NOTE: this *APPENDS* the webBooks maintenance script to the crontab for the user	http://stackoverflow.com/questions/5134952/how-can-i-set-cron-job-through-php-script
		}
	}
	echo "<s><data URI='".$_sUriProject."' hosted='".$_POST['bHostedService']."' /></s>";
file_put_contents('debug.txt', "08\n", FILE_APPEND);

	# remove Business Management module if not needed
	if ($_POST['sOperation'] == 'fw') {					# if the user selected a "Project Framework" operation, then...
		@unlink("BusinessManagement.js");				#   delete the "Business Management" files
		@unlink("BusinessManagement.php");
		@unlink("../look/default/BusinessManagement.css");
		@unlink("../look/default/BusinessManagement.html");
	}
file_put_contents('debug.txt', "09\n", FILE_APPEND);

	# remove deprecated files
	if (file_exists('setup.php')) { @unlink('setup.php'); }
	if (file_exists('../modules')) { delTree('../modules'); }
	if (file_exists('../temp/denaccess_gen.php')) { @unlink('../temp/denaccess_gen.php'); }
	if (file_exists('../temp/re-encrypt.php')) { @unlink('../temp/re-encrypt.php'); }
file_put_contents('debug.txt', "10\n", FILE_APPEND);

	# remove the setup files now that we are done with them
# DEBUG - uncomment for live; post-development
	@unlink('../install.php');
	@unlink('Application_Setup.php');
	@unlink('../look/default/Setup.css');
	@unlink('../look/default/Setup.html');
	@unlink('Setup.js');
	@unlink('Setup.php');
file_put_contents('debug.txt', "11\n", FILE_APPEND);


} else {					# otherwise, we need to indicate that an invalid request was made
	#require_once('../data/_cfgs/Application.php');		# so that the below variables have correct values			NOTE: this was commented out since we are constructing it here and it wouldn't exist if we went into here
file_put_contents('debug.txt', "oops\n", FILE_APPEND);

	echo "<f><msg>An invalid request has occurred, our staff has been notified.</msg></f>";
	sendMail($_sSecurityEmail,$_sSecurityName,$_sAlertsEmail,$_sAlertsName,'*** Possible Cracking Attempt ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nWe might have had a possible cracking attempt made on ".$_." due to someone attempting to pass an invalid values for the form submission.  The call was made from ".$_SERVER['REMOTE_ADDR']." while trying to interact with one of our server-side scripts.  All relevant information has been included below, so please investigate this issue immediately!<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".$MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nOur Error: An invalid set of values has been sent to the script.<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");


}
?>
