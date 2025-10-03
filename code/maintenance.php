#!/usr/bin/php -q
<?php
# maintenance.php	runs the maintenance for each installed module
#		WARNING: this MUST be run from the 'code' directory!
#
# Created	2014/09/23 by Dave Henderson (dhenderson@cliquesoft.org or support@cliquesoft.org)
# Updated	2020/08/26 by Dave Henderson (dhenderson@cliquesoft.org or support@cliquesoft.org)
#
# Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
# device, this software is licensed under the Cliquesoft Public License (CPLv2)
# as found on the Cliquesoft website at www.cliquesoft.org.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.


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
session_start();




# format the dates in UTC
$_ = gmdate("Y-m-d H:i:s",time());				# used this mannor so all the times will be the exact same (also see http://php.net/manual/en/function.gmdate.php)



//	LEFT OFF - rename this maintenance.php > _maintenance.php




// connect to the database for further interaction
if (! connect2DB(DBHOST,DBNAME,DBUSER,DBPASS)) { exit(); }	# the connect2DB has its own error handling so we don't need to do it here!


$gbl_errs['error'] = "Failed to find all installed modules in the database for maintenance.";
$gbl_info['command'] = "SELECT name FROM ".PREFIX."SystemConfiguration_Modules";
$gbl_info['values'] = 'None';
$Module = $linkDB->query($gbl_info['command']);
while ($module = $Module->fetch_assoc()) {
	$MODULE = str_replace(' ', '_', strtolower($module['name']));

	# skip any modules that don't have a file (e.g. modules under development -OR- if there are problems with a module install)
	if (! file_exists($MODULE.'.php')) { continue; }

	# load the file to have the ability to call the maintenance function of the module
	require_once($MODULE.'.php');

	# if the file exists, make sure that the maintenance function exists
	if (! function_exists($MODULE.'_maintenance')) { continue; }
	call_user_func($MODULE.'_maintenance');			# http://stackoverflow.com/questions/1005857/how-to-call-php-function-from-string-stored-in-a-variable

	# if the file exists, make sure that the commerce function exists
	if (! function_exists($MODULE.'_commerce')) { continue; }
	call_user_func($MODULE.'_commerce','send','','');	# send any commerce updates to the paired webBooks
}
?>
