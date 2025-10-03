<?php
# ApplicationSettings.php
#
# Created	2014/01/28 by Dave Henderson (support@cliquesoft.org)
# Updated	2025/08/21 by Dave Henderson (support@cliquesoft.org)
#
# Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
# device, this software is licensed under the Cliquesoft Public License (CPLv2)
# as found on the Cliquesoft website at www.cliquesoft.org.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.


# Constant Definitions
define("MODULE",'Application Settings');			# the name of this module
define("SCRIPT",basename($_SERVER['SCRIPT_NAME']));		# the name of this script (for tracing bugs and automated messages)

# Module Requirements						  NOTE: MUST come below Module Constant Definitions
require_once('../../sqlaccess');
require_once('../data/_modules/ApplicationSettings/config.php');
# DEPRECATED 2025/03/01
#require_once('../data/config.php');
#if (file_exists('../data/config.'.strtolower(MODULE).'.php')) { require_once('../data/config.'.strtolower(MODULE).'.php'); }
require_once('_Project.php');
require_once('_Contact.php');
require_once('_Database.php');
require_once('_Security.php');

# Start or resume the PHP session				  NOTE: gains access to $_SESSION variables in this script
session_start();




# format the dates in UTC
$_ = gmdate("Y-m-d H:i:s",time());		# used this mannor so all the times will be the exact same (also see http://php.net/manual/en/function.gmdate.php)




# define general info for any error generated below
$__sInfo['name'] = 'Unknown';
$__sInfo['contact'] = 'Unknown';
$__sInfo['other'] = 'n/a';


# define the maintenance function
function ApplicationSettings_Maintenance() {
	global $__sInfo,$_LinkDB,$_sUpdates,$_sInstall;
# LEFT OFF - test to make sure that these work in each module

	# connect to the DB for reading below
	if (! connect2DB(DB_HOST,DB_NAME,DB_ROUN,DB_ROPW)) { exit(); }		# NOTE: the connect2DB has its own error handling so we don't need to do it here!

	# check the configured update settings to see if we need to automatically obtain and/or install any updates
# UPDATED 2025/03/05
#	$__sInfo['error'] = "Failed to find the 'Module Settings' record in the database.";
#	$__sInfo['command'] = "SELECT moduleUpdate,moduleInstall FROM ".PREFIX."SystemConfiguration";
#	$__sInfo['values'] = 'None';
#	$Config = $_LinkDB->query($__sInfo['command']);
#	$config = $Config->fetch_assoc();
#	if ($config['moduleUpdate'] == 'manual') { return true; }		# if the admin has choosen to perform manual updates, then exit this routine
	if ($_sUpdates == 'manual') { return true; }				# if the admin has choosen to perform manual updates, then exit this routine

	ApplicationSettings_Update();		# obtain any software updates

# UPDATED 2025/03/05
#	if ($config['moduleInstall'] == 'manual') { return true; }		# if the admin has choosen to perform manual updates, then exit this routine
	if ($_sInstall == 'manual') {						# if the admin has choosen to perform manual updates, then exit this routine
# LEFT OFF - alert the admin that updates are available for installation
		return true;
	}

# LEFT OFF - delete temp/exported.* files (for security); note in the automated email that they have until 12pm to download the file

	# if there isn't an 'update' directory, then we have no updates to install
	if (! file_exists('../temp/update')) { return true; }

# LEFT OFF - add a nightly check in here for updates and notify the system admin (Dashboard > Employees > Outstanding Work ) if the update installation setting is 'manual'
#		- make this a DB table for "Outstanding Work" that modules just add a record to [simple table with pointer to their 'id_module_fk' (for 'which module') and 'id_module_rec_fk' (for 'what job')]

# LEFT OFF - the below needs to process each FILE, not the module name!!!
	# cycle all the installed modules to see if any have updates
	$__sInfo['error'] = "Failed to find the installed modules in the database while conducting maintenance.";
	$__sInfo['command'] = "SELECT name FROM ".DB_PRFX."ApplicationSettings_Modules";
	$__sInfo['values'] = 'None';
	$Modules = $_LinkDB->query($__sInfo['command']);
	while ($module = $Modules->fetch_assoc())
		{ ApplicationSettings_Install($module['name']); }		# install any software updates
	return true;
}




# define the commerce function
function ApplicationSettings_Commerce() {
	# there is currently no processing for commerce
	return true;
}




# the below two functions are used in the 'maintenance' calls as well as through user-invocation from the form, to obtain and install updates
function ApplicationSettings_Update() {
# this function is used to obtain updates
# NOTE	errors are stored in the $__sMsgs variable!!!
	global $__sInfo,$_LinkDB,$__sMsgs;

	$sExtraName = '';								# used if we need extra text in the filename
	$sExtraPath = '';								# used if we need extra text in the path
	$sExtension = '';
	$sInstalled = '';								# used to store the installed modules current hash or version number
	$sAvailable = '';								# used to store the modules newly available hash or version number
	$temp = '';

	# obtain the users home directory
	$__sInfo['error'] = "The 'home directory' of the current user can not be found while checking for updates.";
	$__sInfo['command'] = "posix_getpwuid(posix_getuid())";
	$__sInfo['values'] = 'None';
	$sUser=posix_getpwuid(posix_getuid());						# used to get the home directory of the server-side user account	http://stackoverflow.com/questions/20535474/php-get-user-home-directory-for-virtual-hosting

	# create the update directory if it doesn't already exist
	if (! file_exists('../temp/update')) {
		$__sInfo['error'] = "The temp update directory can not be created.";
		$__sInfo['command'] = "mkdir('../temp/update', 0775, true)";
		$__sInfo['values'] = '';
		@mkdir('../temp/update', 0775, true);
	}

	# remove all existing downloaded updates; (could be stale or the module may no longer be installed - keeps things tidy)
	# NOTE: these files get renamed from '.soft' to '.tgz' below (avoiding issues with php uncompression)
	$Files = glob('../temp/update/*.tgz', GLOB_BRACE);				# http://stackoverflow.com/questions/6155533/loop-code-for-each-file-in-a-directory
	foreach ($Files as $file) {
		$file=basename($file, '.tgz');

		$__sInfo['error'] = "The \"../temp/update/".$file.".tgz\" file can not be removed prior to updating.";
		$__sInfo['command'] = "unlink(\"../temp/update/".$file.".tgz\")";
		$__sInfo['values'] = 'None';
		@unlink('../temp/update/'.$file.'.tgz');
		if (file_exists('../../temp/update/'.$file.'.hash')) {			# NOTE: we have to do two separate unlink() calls because we can glob (e.g. $file.*)
			$__sInfo['error'] = "The \"../temp/update/".$file.".hash\" file can not be removed prior to updating.";
			$__sInfo['command'] = "unlink(\"../temp/update/".$file.".hash\")";
			$__sInfo['values'] = 'None';
			@unlink('../temp/update/'.$file.'.hash');
		}
	}

	# cycle all the installed modules to see if any have updates too
	$__sInfo['error'] = "Failed to find the installed modules in the database while checking for updates.";
	$__sInfo['command'] = "SELECT name FROM ".DB_PRFX."ApplicationSettings_Modules";
	$__sInfo['values'] = 'None';
	$Modules = $_LinkDB->query($__sInfo['command']);
	while ($module = $Modules->fetch_assoc()) {
		$sModule = str_replace(' ', '', $module['name']);			# remove the space in the module name to store in CamelCase

		if ($sModule == 'Employees') { continue; }				# this module is taken care of with 'Application Settings' as part of the 'core' software
		if ($sModule == 'ApplicationSettings') {				# if we're processing the application itself, then...
			$sExtraName = '.all.src';					#    add the additional filename text so the download works correctly
			$sExtension = '.hash';						#    add the proper file extension
		} else {								# otherwise we're dealing with an actual module, so append the directory structure
			$sExtraName = '';						#    reset value
			$sExtraPath = '_exts/'.$sModule.'/';
			$sExtension = '.md5';
		}

		# if we're dealing with a legacy filename, then update to the new filename
		if (file_exists('../data/_modules/'.$sModule.'/md5')) {
			$__sInfo['error'] = "The 'data/_modules/".$sModule."/md5' file can not be renamed while checking for updates.";
			$__sInfo['command'] = "rename('../data/_modules/".$sModule."/md5', '../data/_modules/".$sModule."/hash')";
			$__sInfo['values'] = 'None';
			@rename('../data/_modules/'.$sModule.'/md5', '../data/_modules/'.$sModule.'/hash');
		# if the module has NEVER been updated
		} else if (file_exists('../data/_modules/'.$sModule.'/version')) {	# if we've never checked for updates for this module, then...
			$sInstalled = trim(file_get_contents('../data/_modules/'.$sModule.'/version'));	# obtain the module version from the file to know which hash to fetch

			# check if the hash is available in the repo
			# https://stackoverflow.com/questions/10444059/file-exists-returns-false-for-remote-url
			$s_Headers = @get_headers('http://repo.cliquesoft.org/vanilla/1.0/webbooks/'.$sExtraPath.$sInstalled.$sExtraName.$sExtension);
			if (strpos($s_Headers[0], '404')) {
				$__sMsgs[0] = "The \"".$module['name']." hash could not be found in the online repo.\"";
				return false;
			}

			$__sInfo['error'] = "The modules' hash file can not be obtained from the repo while checking for updates.";
			$__sInfo['command'] = "file_put_contents('../data/_modules/".$sModule."/hash', file_get_contents('http://repo.cliquesoft.org/vanilla/1.0/webbooks/".$sExtraPath.$sInstalled.$sExtraName.$sExtension."))";
			$__sInfo['values'] = 'None';
			file_put_contents('../data/_modules/'.$sModule.'/hash', file_get_contents('http://repo.cliquesoft.org/vanilla/1.0/webbooks/'.$sExtraPath.$sInstalled.$sExtraName.$sExtension));

			$__sInfo['error'] = "The 'data/_modules/".$sModule."/version' file can not be removed while checking for updates.";
			$__sInfo['command'] = "unlink('../data/_modules/".$sModule."/version')";
			$__sInfo['values'] = 'None';
			@unlink('../data/_modules/'.$sModule.'/version');	# now that we've obtained the modules' hash file, we can get rid of this one
		# if we don't have a hash at this point, then...
		} else if (! file_exists('../data/_modules/'.$sModule.'/hash')) {
			$__sMsgs[0] = "The \"".$module['name']."\" hash or version file does not exist to check for updates.";
			return false;
		}

		# we should have the modules' hash file at this point
		$__sInfo['error'] = "The 'data/_modules/".$sModule."/hash' file can not be found while checking for updates.";
		$__sInfo['command'] = "file_get_contents('../data/_modules/".$sModule."/hash')";
		$__sInfo['values'] = 'None';
		$temp = file_get_contents('../data/_modules/'.$sModule.'/hash');	# obtain the source hash file contents
		$sInstalled = explode(" ", $temp);					# isolate just the hash

		# obtain the hash of the latest version of the module
		$__sInfo['error'] = "The module hash can not be found while checking for updates.";
		$__sInfo['values'] = 'None';
		$__sInfo['command'] = "file_get_contents('http://repo.cliquesoft.org/vanilla/1.0/webbooks/".$sExtraPath."beta.md5')";
		$temp = file_get_contents('http://repo.cliquesoft.org/vanilla/1.0/webbooks/'.$sExtraPath.'beta.md5');
		$sAvailable = explode(" ", $temp);					# isolate just the md5 hash

		if ($sInstalled[0] == $sAvailable[0]) { continue; }			# if the two hashes ARE the same, then no need to process anything for the iterated module - on to the next!

		# if we've made it here, we need to obtain the module update
		if (! file_exists('../temp/update')) {
			$__sInfo['error'] = "The 'temp/update' directory can not be created while checking for updates.";
			$__sInfo['command'] = "mkdir('../temp/update', 0775, true)";
			$__sInfo['values'] = 'None';
			@mkdir('../temp/update', 0775, true);
		}
		$__sInfo['error'] = "The module update can not be downloaded.";
		$__sInfo['values'] = 'None';
		$__sInfo['command'] = "file_get_contents('http://repo.cliquesoft.org/vanilla/1.0/webbooks/'.$sExtraPath.'beta.soft')";
		file_put_contents('../temp/update/'.$sModule.'.tgz', file_get_contents('http://repo.cliquesoft.org/vanilla/1.0/webbooks/'.$sExtraPath.'beta.soft'));
		file_put_contents('../temp/update/'.$sModule.'.hash', $sAvailable[0]);		# store the hash of the update as well

		# now that we've obtained the files, lets validate them against their hash file
		$filehash = @md5_file('../temp/update/'.$sModule.'.tgz');
		if ($filehash != $sAvailable[0]) {
			file_put_contents('../data/_logs/'.$sModule.'.log', $_."\nWhile obtaining module updates, the \"".$sModule.".tgz\" file did not validate against its hash file and was deleted.\nCompared |".$filehash."|".$sAvailable[0]."|\n\n");
			@unlink('../temp/update/'.$sModule.'.tgz');
			@unlink('../temp/update/'.$sModule.'.hash');
		}
	}
	return true;


# UPDATED 2025/03/06 - the above is the new code
#	# check if the webBooks core (System Configuration, Business Configuration, and Employees) has any updates
#	$__sInfo['error'] = "The 'data/_modules/ApplicationSettings/md5' file can not be found while checking for updates.";
#	$__sInfo['command'] = "file_get_contents('../data/_modules/ApplicationSettings/md5')";
#	$__sInfo['values'] = 'None';
#	$sSourceHash = file_get_contents('../data/_modules/ApplicationSettings/md5');	# obtain the entire *SOURCE* md5 file contents
#	$sSourceHash = explode(" ", $sSourceHash);					# isolate just the md5 hash
#
#	$__sInfo['error'] = "The 'home directory' of the current user can not be found while checking for updates.";
#	$__sInfo['command'] = "posix_getpwuid(posix_getuid())";
#	$__sInfo['values'] = 'None';
#	$sUser=posix_getpwuid(posix_getuid());						# used to get the home directory of the server-side user account	http://stackoverflow.com/questions/20535474/php-get-user-home-directory-for-virtual-hosting
#
#	$__sInfo['error'] = "The webBooks hash can not be found while checking for updates.";
#	$__sInfo['values'] = 'None';
## UPDATED 2025/03/06 - for uniformity, just pull from online
##	if (file_exists($sUser['dir'].'/official/vanilla/1.0/webbooks')) {		# if this script is being called on the same server (e.g. webfice.com) as the distribution website (cliquesoft.org), then copy locally!
##		$__sInfo['command'] = "file_get_contents('".$sUser['dir']."/official/vanilla/1.0/webbooks/beta.md5')";
##		$sTargetHash = file_get_contents($sUser['dir'].'/official/vanilla/1.0/webbooks/beta.md5');	# obtain the entire *TARGET* md5 file contents
##	} else {									# otherwise, we need to grab from our distribution website (cliquesoft.org)
#		$__sInfo['command'] = "file_get_contents('http://repo.cliquesoft.org/vanilla/1.0/webbooks/beta.md5')";
#		$sTargetHash = file_get_contents('http://repo.cliquesoft.org/vanilla/1.0/webbooks/beta.md5');
##	}
#	$sTargetHash = explode(" ", $sTargetHash);					# isolate just the md5 hash
#
#	if ($sSourceHash[0] != $sTargetHash[0]) {					# if the two MD5 hashes are NOT the same, then there is a newer version!
#		# if we've made it down here, we need to obtain the update
#		if (! file_exists('../temp/update')) {
#			$__sInfo['error'] = "The 'temp/update' directory can not be created while checking for updates.";
#			$__sInfo['command'] = "mkdir('../temp/update', 0775, true)";
#			$__sInfo['values'] = 'None';
#			@mkdir('../temp/update', 0775, true);
#		}
#
## UPDATED 2025/03/06 - for uniformity, just pull from online
##		if (file_exists($sUser['dir'].'/official/vanilla/1.0/webbooks')) {	# if this script is being called on the same server (e.g. webfice.com) as the distribution website (cliquesoft.org), then copy locally!
##			$__sInfo['error'] = "The 'temp/install' directory can not be created while checking for updates.";
##			$__sInfo['command'] = "copy(\"".$sUser['dir']."/official/vanilla/1.0/webbooks/beta.soft\", \"../temp/update/webbooks.tgz\")";
##			$__sInfo['values'] = 'None';
##			copy($sUser['dir'].'/official/vanilla/1.0/webbooks/beta.soft', '../temp/update/webbooks.tgz');
##		} else {								# otherwise, we need to grab from our distribution website (cliquesoft.org)
#			file_put_contents('../temp/update/webbooks.tgz', file_get_contents('http://repo.cliquesoft.org/vanilla/1.0/webbooks/beta.soft'));
##		}
#
#		# store the md5 of the update as well
#		file_put_contents('../temp/update/webbooks.md5', $sTargetHash);
## LEFT OFF - now that we have the hash, we can remove the 'version.txt' file
#	}
#
#	# cycle all the installed modules to see if any have updates too
#	$__sInfo['error'] = "Failed to find the installed modules in the database while checking for updates.";
#	$__sInfo['command'] = "SELECT name FROM ".DB_PRFX."ApplicationSettings_Modules";
#	$__sInfo['values'] = 'None';
#	$Modules = $linkDB->query($__sInfo['command']);
#	while ($module = $Modules->fetch_assoc()) {
#		if ($module['name'] == 'Application Settings' || $module['name'] == 'Employees') { continue; }	# this was already handled above for the webBooks 'core' modules
#
#		$sModule = str_replace(' ', '', $module['name']);			# remove the space in the module name to store in CamelCase
## REMOVED 2025/03/06 - everything is in CamelCase
##		$usMOD = str_replace(' ', '_', $module['name']);			# replace the space in the module name with an underscore
#
## VER2 - we need to log any module that is skipped below (since there would be an issue going forward); unless it's Employees (since it would be the only module that would
#		if (! file_exists('../data/_modules/'.$sModule)) { continue; }		# skipping any module that doesn't have a 'data/_modules/$sModule' directory (e.g. Employees)
#		if (! file_exists('../data/_modules/'.$sModule.'/md5')) { continue; }	# skipping any module that doesn't have an associated md5 file (e.g. Employees)
#
#		$sSourceHash = file_get_contents('../data/_modules/'.$sModule.'/md5');	# obtain the entire *SOURCE* md5 file contents
#		$sSourceHash = explode(" ", $sSourceHash);				# isolate just the md5 hash
#
#		if (file_exists($sUser['dir'].'/official/vanilla/1.0/webbooks'))	# if this script is being called on the same server (e.g. webfice.com) as the distribution website (cliquesoft.org), then copy locally!
#			{ $sTargetHash = file_get_contents($sUser['dir'].'/official/vanilla/1.0/webbooks/_exts/'.$sModule.'/beta.md5'); }			# obtain the entire *TARGET* md5 file contents
#		else									# otherwise, we need to grab from our distribution website (cliquesoft.org)
#			{ $sTargetHash = file_get_contents('http://repo.cliquesoft.org/vanilla/1.0/webbooks/_exts/'.$sModule.'/beta.md5'); }
#		if ($sTargetHash === false) { continue; }				# if the module was not found in the repo (e.g. it's a custom extension -OR- the extension my no longer be available), then skip to the module in the list
#		$sTargetHash = explode(" ", $sTargetHash);				# isolate just the md5 hash
#
#		if ($sSourceHash[0] == $sTargetHash[0]) { continue; }			# if the two MD5 hashes are the same, then the version is the most up-to-date
#
#		# if we've made it down here, we need to obtain the updates
#		if (! file_exists('../temp/update')) {
#			$__sInfo['error'] = "The 'temp/update' directory can not be created for widget updates.";
#			$__sInfo['command'] = "mkdir('../temp/update', 0775, true)";
#			$__sInfo['values'] = 'None';
#			@mkdir('../temp/update', 0775, true);
#		}
#
#		if (file_exists($sUser['dir'].'/official/vanilla/1.0/webbooks')) {	# if this script is being called on the same server (e.g. webfice.com) as the distribution website (cliquesoft.org), then copy locally!
#			$__sInfo['error'] = "The 'temp/install' directory can not be created for widget updates.";
#			$__sInfo['command'] = "copy(\"".$sUser['dir']."/official/vanilla/1.0/webbooks/_exts/".$sModule."/beta.soft\", \"../temp/update/".$sModule.".tgz\")";
#			$__sInfo['values'] = 'None';
#			@copy($sUser['dir'].'/official/vanilla/1.0/webbooks/_exts/'.$sModule.'/beta.soft', '../temp/update/'.$sModule.'.tgz');
#		} else {								# otherwise, we need to grab from our distribution website (cliquesoft.org)
#			file_put_contents('../temp/update/'.$sModule.'.tgz', file_get_contents('http://repo.cliquesoft.org/vanilla/1.0/webbooks/_exts/'.$sModule.'/beta.soft'));
#		}
#
#		# store the md5 of the update as well
#		file_put_contents('../temp/update/'.$sModule.'.md5', $sTargetHash);
#	}
#
#	# cycle all the downloaded modules to remove any previously downloaded updates for modules that may no longer be installed
#	$Files = glob('../temp/update/*.tgz', GLOB_BRACE);				# http://stackoverflow.com/questions/6155533/loop-code-for-each-file-in-a-directory
#	foreach ($files as $file) {
#		$file=basename($file, '.tgz');
#		$bFound=0;
#		if ($file == 'webbooks') { continue; }					# skip processing the 'core' of webBooks
#
#		$Modules->data_seek(0);							# reset the SQL record pointer back to the first record in the results list to re-process in this 'foreach' loop
#		while ($module = $Modules->fetch_assoc()) {
#			$module['name'] = str_replace(' ', '', $module['name']);	# replace the space in the module name with an underscore and lowercase
#			if ($module['name'] == $file) { $bFound=1; break; }		# if the module and filename match, then the update is applicable, so indicate that and break from the 'while' loop
#		}
#		if (! $bFound) {							# if the update doesn't match an installed module, then delete it!
#			$__sInfo['error'] = "The \"../temp/update/".$file.".tgz\" file can not be removed after widget update.";
#			$__sInfo['command'] = "unlink(\"../temp/update/".$file.".tgz\")";
#			$__sInfo['values'] = 'None';
#			@unlink('../temp/update/'.$file.'.tgz');
#			if (file_exists('../../temp/update/'.$file.'.md5')) {
#				$__sInfo['error'] = "The \"../temp/update/".$file.".md5\" file can not be removed after widget update.";
#				$__sInfo['command'] = "unlink(\"../temp/update/".$file.".md5\")";
#				$__sInfo['values'] = 'None';
#				@unlink('../temp/update/'.$file.'.md5');
#			}
#		}
#	}
#
#	return true;
}




function ApplicationSettings_Install($sFilename) {
# this function is used to install the obtained updates
# NOTE	errors are stored in the $__sMsgs variable!!!
# sFilename	the filename of the module to process
	global $__sInfo,$__sMsgs;
file_put_contents('debug.txt', "01\n", FILE_APPEND);
	# exit if no update directory exists and skipping any module that doesn't have a downloaded update
	if (! file_exists('../temp/update')) { return false; }
file_put_contents('debug.txt', "01a |".$sFilename."|\n", FILE_APPEND);
	if (! file_exists('../temp/update/'.$sFilename)) { return false; }
file_put_contents('debug.txt', "02\n", FILE_APPEND);
# REMOVED 2025/05/31 - there's no way this file would exist using the UI
#	# if an uncompressed version of the update exists, then delete it before proceeding
#	if (file_exists('../temp/update/'.$sModule.'.tar')) {
#file_put_contents('debug.txt', "02a\n", FILE_APPEND);
#		$__sInfo['error'] = "The \"../temp/update/".$sModule.".tar\" file can not be removed prior to installation.";
#		$__sInfo['command'] = "unlink(\"../temp/update/".$sModule.".tar\")";
#		$__sInfo['values'] = '';
#		@unlink('../temp/update/'.$sModule.'.tar');
#	}
file_put_contents('debug.txt', "03\n", FILE_APPEND);
	# remove any prior installation directory to ensure a sanitary environment
	if (file_exists('../temp/install')) {
file_put_contents('debug.txt', "03a\n", FILE_APPEND);
		if (! delTree('../temp/install/')) {
file_put_contents('debug.txt', "03b\n", FILE_APPEND);
			$__sMsgs[0] = "An error was encountered clearing out the temp installation directory.";
			return false;
		}
	}
file_put_contents('debug.txt', "04\n", FILE_APPEND);
	$__sInfo['error'] = "The 'temp/install' directory can not be created for widget installation.";
	$__sInfo['command'] = "mkdir('../temp/install', 0775, true)";
	$__sInfo['values'] = '';
	@mkdir('../temp/install', 0775, true);
file_put_contents('debug.txt', "05\n", FILE_APPEND);
	# decompress the tgz file
	exec("tar zxf ../temp/update/".$sFilename." -C ../temp/install", $__sNull);		# uncompress the .soft (.tar.gz) file		http://stackoverflow.com/questions/9416508/php-untar-gz-without-exec
file_put_contents('debug.txt', "06\n", FILE_APPEND);
	if (! file_exists('../temp/install/software/MODULE')) {
file_put_contents('debug.txt', "07\n", FILE_APPEND);
		$__sMsgs[0] = "The \"".basename($sFilename, '.tgz')."\" module installation has stopped due to the upload missing a 'MODULE' file.";
		delTree('../temp/install/');
		return false;
	}
	$sMODULE = trim(file_get_contents('../temp/install/software/MODULE'));
	$sModule = str_replace(' ','',$sMODULE);						# store the modules name in CamelCase (e.g. 'ApplicationSettings')
file_put_contents('debug.txt', "08\n", FILE_APPEND);
	# run the module setup
# LEFT OFF - replace webBooks with Application (that way we can get rid of this 'if' to split between webBooks and modules)
	if ($sModule == 'webBooks') {					# if we are working with the core of webBooks, then source this setup file
file_put_contents('debug.txt', "08a\n", FILE_APPEND);
# LEFT OFF - update the below to be Application_Setup.php? (since Setup.php is used to initially install the software, not update the webBooks core)
		if (! file_exists('../temp/install/software/code/Setup.php')) {
file_put_contents('debug.txt', "08b\n", FILE_APPEND);
			$__sMsgs[0] = "The \"".$sMODULE."\" module installation has stopped due to the upload missing a \"Setup.php\" file.";
			return false;
		}
		require('../temp/install/software/code/Setup.php');
	} else {							# otherwise, source the modules setup file
file_put_contents('debug.txt', "08c |".$sModule."|\n", FILE_APPEND);
		if (! file_exists('../temp/install/software/code/'.$sModule.'_Setup.php')) {
file_put_contents('debug.txt', "08d\n", FILE_APPEND);
			$__sMsgs[0] = "The \"".$sMODULE."\" module installation has stopped due to the upload missing a \"".$sModule."_Setup.php\" file.";
			return false;
		}
		require('../temp/install/software/code/'.$sModule.'_Setup.php');
	}
file_put_contents('debug.txt', "09\n", FILE_APPEND);
#	if (! call_user_func($sModule.'_Update', $strModule)) {		// http://stackoverflow.com/questions/1005857/how-to-call-php-function-from-string-stored-in-a-variable
	if (! call_user_func($sModule.'_Update')) {			# http://stackoverflow.com/questions/1005857/how-to-call-php-function-from-string-stored-in-a-variable
file_put_contents('debug.txt', "09a\n", FILE_APPEND);
		# NOTE: the function will be responsible for STORING any error messages in $__sMsgs
		delTree('../../temp/install');				# delete the install files
		return false;
	}
file_put_contents('debug.txt', "10\n", FILE_APPEND);
	# if the install function call completed successfully, then...
	if (file_exists('../temp/update/'.basename($sFilename, '.tgz').'.hash')) {
file_put_contents('debug.txt', "10a\n", FILE_APPEND);
		$gbl_errs['error'] = "The \"../temp/update/".basename($sFilename, '.tgz').".hash\" file can not be removed post installation.";
		$gbl_info['command'] = "unlink(\"../temp/update/".basename($sFilename, '.tgz').".hash\")";
		$gbl_info['values'] = '';
		@unlink('../temp/update/'.basename($sFilename, '.tgz').'.hash');		# delete the hash update file
	}
	if (file_exists('../temp/update/'.$sFilename)) {
file_put_contents('debug.txt', "10b\n", FILE_APPEND);
		$gbl_errs['error'] = "The \"../temp/update/".$sFilename."\" file can not be removed post installation.";
		$gbl_info['command'] = "unlink(\"../temp/update/".$sFilename."\")";
		$gbl_info['values'] = '';
		@unlink('../temp/update/'.$sFilename);		# delete the .tgz update file
	}
file_put_contents('debug.txt', "11\n", FILE_APPEND);
	# directory cleanup
	delTree('../temp/install');					# delete the install files
file_put_contents('debug.txt', "12\n", FILE_APPEND);
	# delete the '../temp/update/' directory if it is empty; NOTE: the count is 2 because of . and ..
	if (count(scandir('../temp/update/')) == 2) {
		$gbl_errs['error'] = "The \"../temp/update\" directory can not be removed post installation.";
		$gbl_info['command'] = "unlink(\"../temp/update\")";
		$gbl_info['values'] = '';
		@rmdir('../temp/update');
	}
file_put_contents('debug.txt', "13\n", FILE_APPEND);
	return true;



#	if ($strModule == 'Webbooks') { $strModule = 'webBooks'; }	# this will correct any issues with the case when installing the webBooks core
#	$ccMOD = str_replace(' ', '', $strModule);			# remove the space in the module name to store in CamelCase
#	$usMOD = str_replace(' ', '_', $strModule);			# replace the space in the module name with an underscore
#
#	# exit if no update directory exists and skipping any module that doesn't have a downloaded update
#	if (! file_exists('../temp/update')) { return false; }
#	if (! file_exists('../temp/update/'.strtolower($usMOD).'.tgz')) { return false; }
#	if (file_exists('../temp/update/'.strtolower($usMOD).'.tar')) {
#		$gbl_errs['error'] = "The \"../temp/update/".strtolower($usMOD).".tar\" file can not be removed prior to installation.";
#		$gbl_info['command'] = "unlink(\"../temp/update/".strtolower($usMOD).".tar\")";
#		$gbl_info['values'] = '';
#		@unlink('../temp/update/'.strtolower($usMOD).'.tar');
#	}
#
#	# decompress the tgz file					http://stackoverflow.com/questions/9416508/php-untar-gz-without-exec
#	$gbl_errs['error'] = "The 'temp/install' directory can not be created for widget installation.";
#	$gbl_info['command'] = "mkdir('../temp/install', 0775, true)";
#	$gbl_info['values'] = '';
#	if (file_exists('../temp/install')) { delTree('../temp/install'); }				# if any prior (failed) attempts still exist, delete those files
#	@mkdir('../temp/install', 0775, true);
#	exec("tar zxf ../temp/update/".strtolower($usMOD).".tgz -C ../temp/install", $gbl_null);	# uncompress the .soft (.tar.gz) file		http://stackoverflow.com/questions/9416508/php-untar-gz-without-exec
#
#	# run the module setup
#	$gbl_info['command'] = "unlink(\"../temp/update/".strtolower($usMOD).".md5\")";
#	$gbl_info['values'] = '';
#	if ($strModule == 'webBooks') {					# if we are working with the core of webBooks, then source this setup file
#		$gbl_errs['error'] = "The \"../temp/install/software/code/setup.php\" file can not be found prior to installation.";
#		require('../temp/install/software/code/setup.php');
#	} else {							# otherwise, source the modules setup file
#		$gbl_errs['error'] = "The \"../temp/install/software/code/".strtolower($usMOD)."_setup.php\" file can not be found prior to installation.";
#		require('../temp/install/software/code/'.strtolower($usMOD).'_setup.php');
#	}
#	if (! call_user_func(strtolower($usMOD).'_update', $strModule)) {		// http://stackoverflow.com/questions/1005857/how-to-call-php-function-from-string-stored-in-a-variable
#		delTree('../../temp/install');				# delete the install files
#		return false;
#	}
#
#	# if the install function call completed successfully, then...
#	if (file_exists('../temp/update/'.strtolower($usMOD).'.md5')) {
#		$gbl_errs['error'] = "The \"../temp/update/".strtolower($usMOD).".md5\" file can not be removed post installation.";
#		$gbl_info['command'] = "unlink(\"../temp/update/".strtolower($usMOD).".md5\")";
#		$gbl_info['values'] = '';
#		@unlink('../temp/update/'.strtolower($usMOD).'.md5');	# delete the .md5 update file
#	}
#	if (file_exists('../temp/update/'.strtolower($usMOD).'.tgz')) {
#		$gbl_errs['error'] = "The \"../temp/update/".strtolower($usMOD).".tgz\" file can not be removed post installation.";
#		$gbl_info['command'] = "unlink(\"../temp/update/".strtolower($usMOD).".tgz\")";
#		$gbl_info['values'] = '';
#		@unlink('../temp/update/'.strtolower($usMOD).'.tgz');	# delete the .tgz update file
#	}
#	delTree('../temp/install');					# delete the install files
#
#	# delete the '../../temp/update/' directory if it is empty; NOTE: the count is 2 because of . and ..
#	if (count(scandir('../temp/update/')) == 2) {
#		$gbl_errs['error'] = "The \"../temp/update\" directory can not be removed post installation.";
#		$gbl_info['command'] = "unlink(\"../temp/update\")";
#		$gbl_info['values'] = '';
#		@rmdir('../temp/update');
#	}
#
#	return true;
}










# now exit this script if it is being called from the maintenance.php file (since we just needed access to the above functions)
# UPDATED 2025/03/08
#if((php_sapi_name() == 'cli' || empty($_SERVER['REMOTE_ADDR'])) {
if((php_sapi_name() == 'cli' && count($argv) == 1)) { return true; }	# NOTE: this should execute when sourcing this file to call maintenance and commerce, but not for DB exporting, importing, archiving!
# otherwise, the database calls can proceed [since count($argv)>1]
# DEBUG
#else if(php_sapi_name() == 'cli' || empty($_SERVER['REMOTE_ADDR'])) {
#	file_put_contents('debug.txt', "AS - 1b |".$argv[1]."|\n", FILE_APPEND);
#} else {
#	file_put_contents('debug.txt', "AS - 1b |".$_POST['A']."|".$_POST['T']."|\n", FILE_APPEND);
#}










// process the database import/export/archive and email the user		  See the 'Export > Database' section below
if (isset($argv)) {								# if this script was called from itself (for database io), then...
# LEFT OFF - update this function
	# connect to the DB for reading below
	if (! connect2DB(DB_HOST,DB_NAME,DB_ROUN,DB_ROPW)) { return false; }	# NOTE: the connect2DB has its own error handling so we don't need to do it here!

	# obtain the admin account info to email
	$__sInfo['error'] = "Failed to find the admin account in the database while exporting the database.";
# VER2 - once the username and password fields get added to the Vendors and Service Providers screen, check that if the above does not match any employees (e.g. an on-demand IT company); see the "Init > Values" section below for rough code to use
#				"SELECT id FROM ".PREFIX."BusinessConfiguration_Additional WHERE username='".$argv[2]."' LIMIT 1"
	$__sInfo['command'] = "SELECT name,workEmail FROM ".DB_PRFX."Employees WHERE username='".$argv[2]."' LIMIT 1";
	$__sInfo['values'] = 'None';
	$Account = $_LinkDB->query($__sInfo['command']);

	# if the account can't be found, then...
	if ($Account->num_rows === 0) {
# UPDATED 2025/03/08
#		sendMail($gbl_emailHackers,$gbl_nameHackers,$gbl_emailNoReply,$SCRIPT.' script','*** Account Not Found ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/webbooks.email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Account Not Found</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".$SCRIPT."<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\n<br />\nOur Error: An error occurred while attempting to find the admin account during database exportation.<br />\nExec Error: ".$XML->f->msg."<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		sendMail($_sSupportEmail,$_sSupportName,$_sAlertsEmail,$_sAlertsName,'*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".$SCRIPT."<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: An error occurred while attempting to ".$argv[1]." the database.<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		exit();
	}

# VER2 - update this in the future to somehow delete the export.tgz file after it's been downloaded to prevent theft from a malicious person
	# if we've made it here, everything is good and we can email the location to the database export
	$account = $Account->fetch_assoc();

# VER2 - alert that this will have to put the software in maintenance mode to accomplish each of these DB actions (so data doesn't get distorted)
	if ($argv[1] == 'export') {
		exec("cd ".$_sDirTemp." && tar zcf '".str_replace(array(' ',':'), array('_',''), $_)."_backup.tgz' backup.* && rm backup.log && rm backup.sql", $__sNull);	# compress the data

# MOVED 2025/03/08 - consolidated to the top of this code block
#		# connect to the DB for reading below
#		if (! connect2DB(DB_HOST,DB_NAME,DB_ROUN,DB_ROPW)) { return false; }	# NOTE: the connect2DB has its own error handling so we don't need to do it here!
#
#		# obtain the admin account info to email
#		$__sInfo['error'] = "Failed to find the admin account in the database while exporting the database.";
## VER2 - once the username and password fields get added to the Vendors and Service Providers screen, check that if the above does not match any employees (e.g. an on-demand IT company); see the "Init > Values" section below for rough code to use
##				"SELECT id FROM ".PREFIX."BusinessConfiguration_Additional WHERE username='".$argv[2]."' LIMIT 1"
#		$__sInfo['command'] = "SELECT name,workEmail FROM ".DB_PRFX."Employees WHERE username='".$argv[2]."' LIMIT 1";
#		$__sInfo['values'] = 'None';
#		$Account = $_LinkDB->query($__sInfo['command']);
#
#		# if the account can't be found, then...
#		if ($Account->num_rows === 0) {
## UPDATED 2025/03/08
##			sendMail($gbl_emailHackers,$gbl_nameHackers,$gbl_emailNoReply,$SCRIPT.' script','*** Account Not Found ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/webbooks.email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Account Not Found</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".$SCRIPT."<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\n<br />\nOur Error: An error occurred while attempting to find the admin account during database exportation.<br />\nExec Error: ".$XML->f->msg."<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
#			sendMail($_sSupportEmail,$_sSupportName,$_sAlertsEmail,$_sAlertsName,'*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".$SCRIPT."<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: An error occurred while attempting to export the database.<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
#			exit();
#		}
#
## VER2 - update this in the future to somehow delete the export.tgz file after it's been downloaded to prevent theft from a malicious person
#		# if we've made it here, everything is good and we can email the location to the database export
#		$account = $Account->fetch_assoc();

# UPDATED 2025/03/08
#		sendMail($account['workEmail'],$account['name'],$gbl_emailNoReply,'webBooks','Company Database Exported',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/webbooks.email_info.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Company Database Exported</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\n".$account['name'].",<br />\n<br />\nYou have recently initiated an export of the database information for the company and we are getting in contact to inform you that the process has completed. To obtain the data and its associated log, a download link has been added below for your convenience. The referenced link comes in a '.tgz' file which is a native compression format in Linux and Mac, but not in Windows. To decompress these files in a Microsoft environment, we have included several links to some of the more popular software below:<br />\n<br />\n<a href='http://www.7-zip.org/' target='_new'>7-Zip</a> (freeware)<br />\n<a href='http://www.powerarchiver.com/' target='_new'>PowerArchiver</a> (freeware)<br />\n<a href='http://www.rarlab.com/' target='_new'>WinRAR</a> (commercial)<br />\n<a href='http://www.winzip.com/' target='_new'>WinZip</a> (commercial)<br />\n<br />\n<br />\n<a href='".$gbl_uriProject."/temp/exported.tgz'>Exported Database</a><br />\n<br />\nAny encountered problems should be directed towards your technical support staff.</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		sendMail($account['workEmail'],$account['name'],$_sAlertsEmail,$_sAlertsName,'Company Database Exported',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_info.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Company Database Exported</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\n".$account['name'].",<br />\n<br />\nYou have recently initiated an export of the database information for the company and we are getting in contact to inform you that the process has completed. To obtain the data and its associated log, a download link has been added below for your convenience. The referenced link comes in a '.tgz' file which is a native compression format in Linux and Mac, but not in Windows. To decompress these files in a Microsoft environment, we have included several links to some of the more popular software below:<br />\n<br />\n<a href='http://www.7-zip.org/' target='_new'>7-Zip</a> (freeware)<br />\n<a href='http://www.powerarchiver.com/' target='_new'>PowerArchiver</a> (freeware)<br />\n<a href='http://www.rarlab.com/' target='_new'>WinRAR</a> (commercial)<br />\n<a href='http://www.winzip.com/' target='_new'>WinZip</a> (commercial)<br />\n<br />\n<br />\n<a href='".$_sUriProject."temp/".str_replace(array(' ',':'), array('_',''), $_)."_backup.tgz'>Exported Database</a><br />\n<br />\nAny encountered problems should be directed towards support.</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		exit();


	} else if ($argv[1] == 'import') {
# MOVED 2025/03/08 - consolidated to the top of this code block
#		# connect to the DB for reading below
#		if (! connect2DB(DBHOST,DBNAME,DBUNRO,DBPWRO)) { return false; }	# NOTE: the connect2DB has its own error handling so we don't need to do it here!
#
#		# obtain the admin account info to email
#		$gbl_errs['error'] = "Failed to find the admin account in the database while importing the database.";
## VER2 - once the username and password fields get added to the Vendors and Service Providers screen, check that if the above does not match any employees (e.g. an on-demand IT company); see the "Init > Values" section below for rough code to use
##				"SELECT id FROM ".PREFIX."BusinessConfiguration_Additional WHERE username='".$argv[2]."' LIMIT 1"
#		$gbl_info['command'] = "SELECT name,workEmail FROM ".DB_PRFX."Employees WHERE username='".$argv[2]."' LIMIT 1";
#		$gbl_info['values'] = 'None';
#		$Account = $linkDB->query($gbl_info['command']);
#
#		# if the account can't be found, then...
#		if ($Account->num_rows === 0) {
## UPDATED 2025/03/08
##			sendMail($gbl_emailHackers,$gbl_nameHackers,$gbl_emailNoReply,$SCRIPT.' script','*** Account Not Found ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/webbooks.email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Account Not Found</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".$SCRIPT."<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\n<br />\nOur Error: An error occurred while attempting to find the admin account during database importation.<br />\nExec Error: ".$XML->f->msg."<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
#			sendMail($_sSupportEmail,$_sSupportName,$_sAlertsEmail,$_sAlertsName,'*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".$SCRIPT."<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: An error occurred while attempting to import the database.<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
#			exit();
#		}
#
#		# if we've made it here, everything is good and we can begin the database import
#		$account = $Account->fetch_assoc();

		$sRemaining = 'none';
# REMOVED 2025/03/09 - this is done in the import.sh script
#		$i=0;
#		unlink($argv[3]);							# since this was called upon success of importation (which deletes the file afterwards), we can report the tally
		if (file_exists($_sDirTemp.'/import')) {				# store all the remaining data sets that are currently being processed
			foreach (glob($_sDirTemp."/import/*.sql") as $sFile) {
				if (basename($argv[3]) == basename($sFile)) { continue; }		# if we have iterated to the file that was just processed, skip it (it will be deleted by import.sh)
				if ($sRemaining == 'none') { $sRemaining = ''; }	# remove 'none' from the listing if we have files to add to the list
				$sRemaining .= basename($sFile)."<br />\n";
# REMOVED 2025/03/09 - this is done in the import.sh script
#				$i++;							# also count the data sets that are remaining to know if we can clean up
			}
		}

		$sErrors = file_get_contents($_sDirTemp.'/import.log');
		if (! $sErrors) { $sErrors = 'none'; }
# UPDATED 2025/03/09
#		sendMail($account['workEmail'],$account['name'],$gbl_emailNoReply,'webBooks','Company Data Imported',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/webbooks.email_info.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Company Data Imported</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\n".$account['name'].",<br />\n<br />\nYou have recently initiated the importation of data into the working database for the company and we are getting in contact to inform you that the process has completed for the data set listed below. A list of all the additional data sets that are currently being imported will be provided as a way to indicate progress. If any problems were encountered, the contents of the log will also be shown below.<br />\n<br />\nProcessed: ".basename($argv[3])."<br />\nErrors: ".$sErrors."<br />\n<br />\nRemaining:<br />\n".$sRemaining."<br />\n<br />\n<br />\nAny encountered problems should be directed towards your technical support staff.</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		sendMail($account['workEmail'],$account['name'],$_sAlertsEmail,$_sAlertsName,'Company Database Imported',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_info.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Company Database Imported</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\n".$account['name'].",<br />\n<br />\nYou have recently initiated an importation of data into the working database for the company and we are getting in contact to inform you that the process has completed for the data set listed below. A list of all the additional data sets that are in the queue to be imported will be provided as a way to indicate progress. If any problems were encountered, the contents of the log will also be shown below.<br />\n<br />\nProcessed: ".basename($argv[3])."\n<br />\nRemaining:<br />\n".$sRemaining."<br />\n<br />\nErrors: ".$sErrors."<br />\n<br />\nAny encountered problems should be directed towards support.</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");

# REMOVED 2025/03/09 - this is done in the import.sh script
#		# cleanup
#		if ($i == 0) { delTree($_sDirTemp.'/import/'); }
		exit();


	} else if ($argv[1] == 'archive') {
		exec("cd ".$_sDirTemp." && tar zcf archived.tgz archived*", $gbl_null);	# compress the data

# MOVED 2025/03/08 - consolidated to the top of this code block
#		# connect to the DB for reading below
#		if (! connect2DB(DBHOST,DBNAME,DBUNRO,DBPWRO)) { return false; }	# NOTE: the connect2DB has its own error handling so we don't need to do it here!
#
#		# obtain the admin account info to email
#		$gbl_errs['error'] = "Failed to find the admin account in the database while exporting the database.";
## VER2 - once the username and password fields get added to the Vendors and Service Providers screen, check that if the above does not match any employees (e.g. an on-demand IT company); see the "Init > Values" section below for rough code to use
##				"SELECT id FROM ".PREFIX."BusinessConfiguration_Additional WHERE username='".$argv[2]."' LIMIT 1"
#		$gbl_info['command'] = "SELECT name,workEmail FROM ".DB_PRFX."Employees WHERE username='".$argv[2]."' LIMIT 1";
#		$gbl_info['values'] = 'None';
#		$Account = $linkDB->query($gbl_info['command']);
#
#		# if the account can't be found, then...
#		if ($Account->num_rows === 0) {
## UPDATED 2025/03/08
##			sendMail($gbl_emailHackers,$gbl_nameHackers,$gbl_emailNoReply,$SCRIPT.' script','*** Account Not Found ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/webbooks.email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Account Not Found</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".$SCRIPT."<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\n<br />\nOur Error: An error occurred while attempting to find the admin account during database archiving.<br />\nExec Error: ".$XML->f->msg."<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
#			sendMail($_sSupportEmail,$_sSupportName,$_sAlertsEmail,$_sAlertsName,'*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".$SCRIPT."<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: An error occurred while attempting to archive the database.<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
#			exit();
#		}
#
## VER2 - update this in the future to somehow delete the archive.tgz file after it's been downloaded to prevent theft from a malicious person
#		# if we've made it here, everything is good and we can email the location to the database archive
#		$account = $Account->fetch_assoc();

		sendMail($account['workEmail'],$account['name'],$gbl_emailNoReply,'webBooks','Company Database Archived',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/webbooks.email_info.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Company Database Archived</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'><br />\n".$account['name'].",<br />\n<br />\nYou have recently initiated an archive of the older database information for the company and we are getting in contact to inform you that the process has completed. To obtain the data and its associated log, a download link has been added below for your convenience. The referenced link comes in a '.tgz' file which is a native compression format in Linux and Mac, but not in Windows. To decompress these files in a Microsoft environment, we have included several links to some of the more popular software below:<br />\n<br />\n<a href='http://www.7-zip.org/' target='_new'>7-Zip</a> (freeware)<br />\n<a href='http://www.powerarchiver.com/' target='_new'>PowerArchiver</a> (freeware)<br />\n<a href='http://www.rarlab.com/' target='_new'>WinRAR</a> (commercial)<br />\n<a href='http://www.winzip.com/' target='_new'>WinZip</a> (commercial)<br />\n<br />\n<br />\n<a href='".$gbl_uriProject."/temp/archived.tgz'>Archived Database</a> as of ".$argv[3]."<br />\n<br />\nAny encountered problems should be directed towards your technical support staff.</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		exit();
	}
}










// create the header for any processing below...
#if ($_GET['action'] != '' || $_POST['action'] != '') {
#if ($_POST['action'] != '') {
	if ($_POST['A'] == 'Load' && $_POST['T'] == 'Module') {	# if we're loading the HTML/css, then...
		header('Content-Type: text/html; charset=utf-8');
	} else {						# otherwise, we're interacting with the database and need to use XML
		header('Content-Type: text/xml; charset=utf-8');
		echo "<?xml version='1.0' encoding='UTF-8'?>\n\n";
	}
#}










# -- ApplicationSettings API --

switch ($_POST['T']) {						# Process the submitted (T)arget

    case 'Module':
	# sends the modules html
	if ($_POST['A'] == 'Load') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }

		# load the users account info in the global variable
		if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }


		# check that the submitting account has permission to access the module
# UPDATED 2025/03/05
#		$__sInfo['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission.";
#		$__sInfo['command'] = "SELECT id FROM ".DB_PRFX."ApplicationSettings_Modules WHERE name='".MODULE."' LIMIT 1";
#		$__sInfo['values'] = 'None';
#		$Module = $_LinkDB->query($__sInfo['command']);
#		$module = $Module->fetch_assoc();
#
#		$__sInfo['error'] = "Failed to find the Employee record in the database when checking for access permission.";
#		$__sInfo['command'] = "SELECT `read` FROM ".DB_PRFX."Employees_Access WHERE employeeID='".$__sUser['id']."' AND moduleID='".$module['id']."' LIMIT 1";
#		$__sInfo['values'] = 'None';
#		$Access = $_LinkDB->query($__sInfo['command']);
#		if ($Access->num_rows === 0) {			# if the account can't be found, then...
#			echo "<div class='fail'>Your account does not have sufficient priviledges to open (read) data in this module.</div>";
#			exit();
#		}						# otherwise the account MAY have permission to access, so...
#		$access = $Access->fetch_assoc();		# load the access information for the account
#		if ($access['read'] == 0) {			# if the account does NOT have 'read' access for this module, then...
#			echo "<div class='fail'>Your account does not have sufficient priviledges to open (read) data in this module.</div>";
#			exit();
#		}
		if (! checkPermission('read')) { exit(); }

		# if we've made it down here then the module can be accessed by this account
		if (! file_exists("../home/".$_POST['sUsername']."/look/".substr(SCRIPT,0,-4).".html")) {
			echo "<div class='fail'>The screen contents you are requesting do NOT exist, please contact your network administrator or IT technician for assistence.</div>";
		} else {
			$page = fopen("../home/".$_POST['sUsername']."/look/".substr(SCRIPT,0,-4).".html", "r");
			while ($LINE = fgets($page)) {
				# snippets for dynamic content using shell-style-variables in the .html file
				if (strpos($LINE, '${UN}') !== false) { $LINE = str_replace('${UN}', $_POST['sUsername'], $LINE); }
				echo "$LINE";
			}
		}
		exit();




	# Initialize the UI
	} else if ($_POST['A'] == 'Initialize') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }


		# check that the submitting account has permission to access the module
# UPDATED 2025/03/05
#		$__sInfo['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission.";
#		$__sInfo['command'] = "SELECT id FROM ".DB_PRFX."ApplicationSettings_Modules WHERE name='".MODULE."' LIMIT 1";
#		$__sInfo['values'] = 'None';
#		$Module = $_LinkDB->query($__sInfo['command']);
#		$module = $Module->fetch_assoc();
#
#		$__sInfo['error'] = "Failed to find the Employee record in the database when checking for access permission.";
#		$__sInfo['command'] = "SELECT `read` FROM ".DB_PRFX."Employees_Access WHERE employeeID='".$__sUser['id']."' AND moduleID='".$module['id']."' LIMIT 1";
#		$__sInfo['values'] = 'None';
#		$Access = $_LinkDB->query($__sInfo['command']);
#		if ($Access->num_rows === 0) {			# if the account can't be found, then...
#			echo "<div class='fail'>Your account does not have sufficient priviledges to open (read) data in this module.</div>";
#			exit();
#		}						# otherwise the account MAY have permission to access, so...
#		$access = $Access->fetch_assoc();		# load the access information for the account
#		if ($access['read'] == 0) {			# if the account does NOT have 'read' access for this module, then...
#			echo "<div class='fail'>Your account does not have sufficient priviledges to open (read) data in this module.</div>";
#			exit();
#		}
		if (! checkPermission('read')) { exit(); }


		# --- RETRIEVE INFO FOR: INITIALIZING VALUES -AND- ACCOUNT INFO ---


		# 1. Store the configured dashboard groups
		$__sInfo['error'] = "Failed to obtain the 'Configured Dashboard Groups' in the database when initializing the module.";
		$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."ApplicationSettings_Groups";
		$__sInfo['values'] = 'None';
		$Groups = $_LinkDB->query($__sInfo['command']);

		# 2. Store the installed modules
		$__sInfo['error'] = "Failed to obtain the 'Installed Modules' in the database when initializing the module.";
		$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."ApplicationSettings_Modules";
		$__sInfo['values'] = 'None';
		$Modules = $_LinkDB->query($__sInfo['command']);

		# 3. Update these values to isolate the parent level and 1st-child level directories
		$sDirCron = substr($_sDirCron, strrpos($_sDirCron, '/')+1);
		$sDirData = substr($_sDirData, strrpos($_sDirData, '/')+1);
		$sDirLogs = substr($_sDirLogs, strrpos($_sDirLogs, '/')+1);
		$sDirMail = substr($_sDirMail, strrpos($_sDirMail, '/')+1);
		$sDirTemp = substr($_sDirTemp, strrpos($_sDirTemp, '/')+1);
		$sDirVrfy = substr($_sDirVrfy, strrpos($_sDirVrfy, '/')+1);


		# now write the XML to the clients browser
		echo "<s>\n";
		echo "   <xml>\n";
		echo "	<general>\n";
		echo "	   <contact sAlertsName=\"".safeXML($_sAlertsName)."\" sAlertsEmail=\"".safeXML($_sAlertsEmail)."\" sSupportName=\"".safeXML($_sSupportName)."\" sSupportEmail=\"".safeXML($_sSupportEmail)."\" sSecurityName=\"".safeXML($_sSecurityName)."\" sSecurityEmail=\"".safeXML($_sSecurityEmail)."\" />\n";
		echo "	   <operation sInterface=\"".$_sInterface."\" bHostedService=\"".($_bHostedService ? 'true' : 'false')."\" />\n";
		echo "	   <security bUseCaptchas=\"".($_bUseCaptchas ? 'true' : 'false')."\" nFailedAuth=\"".$_nFailedAuth."\" nTimeout=\"".$_nTimeout."\" />\n";
		echo "	   <maintenance bMaintenance=\"".($_bMaintenance ? 'true' : 'false')."\" sMaintenance=\"".safeXML($_sMaintenance)."\" />\n";
		echo "	   <uri sUriProject=\"".safeXML($_sUriProject)."\" sUriPayment=\"".safeXML($_sUriPayment)."\" sUriSocial=\"".safeXML($_sUriSocial)."\" />\n";
		echo "	   <logs sLogEmail=\"".safeXML($_sLogEmail)."\" sLogScript=\"".safeXML($_sLogScript)."\" sLogModule=\"".safeXML($_sLogModule)."\" sLogProject=\"".safeXML($_sLogProject)."\" />\n";
		echo "	   <dirs sDirCron=\"".safeXML($sDirCron)."\" sDirData=\"".safeXML($sDirData)."\" sDirLogs=\"".safeXML($sDirLogs)."\" sDirMail=\"".safeXML($sDirMail)."\" sDirTemp=\"".safeXML($sDirTemp)."\" sDirVrfy=\"".safeXML($sDirVrfy)."\" />\n";
		echo "	   <database DB_HOST=\"".DB_HOST."\" DB_NAME=\"".DB_NAME."\" DB_ROUN=\"".DB_ROUN."\" DB_ROPW=\"\" DB_RWUN=\"".DB_RWUN."\" DB_RWPW=\"\" DB_PRFX=\"".DB_PRFX."\" />\n";
		echo "	   <authentication AUTH_TB=\"".AUTH_TB."\" AUTH_ID=\"".AUTH_ID."\" AUTH_UN=\"".AUTH_UN."\" AUTH_PW=\"".AUTH_PW."\" />\n";
		echo "	   <module sUpdates=\"".$_sUpdates."\" sInstall=\"".$_sInstall."\" />\n";
		echo "	</general>\n";

		if ($_bHostedService) {
			// get the correct *LAST* record for each type of desired info below
			$__sInfo['error'] = "Failed to obtain the 'Balance' in the database when initializing the module.";
			$__sInfo['command'] = "SELECT balance FROM ".DB_PRFX."Funds ORDER BY createdOn DESC LIMIT 1";					// since each record maintains the latest correct 'balance' value, we need to get the *LAST* record for the most up-to-date info
			$__sInfo['values'] = 'None';
			$Balance = $_LinkDB->query($__sInfo['command']);

			$__sInfo['error'] = "Failed to obtain the 'Non-PayPal' records in the database when initializing the module.";
			$__sInfo['command'] = "SELECT custom1,custom2 FROM ".DB_PRFX."Funds WHERE type<>'paypal' ORDER BY createdOn DESC LIMIT 1";	// since the 'paypal' records do NOT store the 'logins' and 'support' values, we need to get the last record that is NOT a 'paypal' record for this info
			$__sInfo['values'] = 'None';
			$Options = $_LinkDB->query($__sInfo['command']);

			if ($Balance->num_rows === 0) {
				$balance = '0.00';
			} else {
				$funds = $Balance->fetch_assoc();
				$balance = $funds['balance'];
			}
			if ($Options->num_rows === 0) {
				$logins = 1;
				$support = 0;
			} else {
				$options = $Options->fetch_assoc();
				$logins = $options['custom1'];
				$support = $options['custom2'];
			}

			echo "	   <system sUriSocial=\"".safeXML($_sUriSocial)."\" accessURI=\"".basename(substr(getcwd(),0,-17))."\" logins='".$logins."' support='".$support."' balance='".$balance."' DB_PRFX='".DB_PRFX."' />\n";
		}

		echo "	<modules>\n";
		while ($module = $Modules->fetch_assoc())
			{ echo "	   <installed id=\"".$module['id']."\" name=\"".safeXML($module['name'])."\" icon=\"".safeXML($module['icon'])."\" />\n"; }
		if (file_exists($_sDirTemp.'/update')) {
			$updates = scandir($_sDirTemp.'/update');
			foreach ($updates as $key=>$val) {
				# skip the . and .. and .md5 entries in the directory listing
				if ($val == '.' || $val == '..' || substr($val, -4) == 'hash') { continue; }

				echo "	   <update module=\"".safeXML($val)."\" />\n";
			}
		}
		echo "	</modules>\n";

		echo "	<groups>\n";
		while ($group = $Groups->fetch_assoc()) {
			echo "	   <group id=\"".$group['id']."\" name=\"".safeXML($group['name'])."\" icon=\"".safeXML($group['icon'])."\" />\n";
# REMOVED 2025/03/24 - just call the ApplicationSettings('group') function
#			echo "	   <group id=\"".$group['id']."\" name=\"".safeXML($group['name'])."\" icon=\"".safeXML($group['icon'])."\">\n";
#
#			# 4b. Cycle all the grouped modules
#			$__sInfo['error'] = "Failed to obtain the 'Grouped Modules' in the database when initializing the module.";
#			$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."ApplicationSettings_Grouped WHERE groupID='".$group['id']."'";
#			$__sInfo['values'] = 'None';
#			$Grouped = $_LinkDB->query($__sInfo['command']);
#			while ($grouped = $Grouped->fetch_assoc()) {
#				# 4c. Cycle all the modules in the iterated group
#				$__sInfo['error'] = "Failed to obtain the 'Grouped Module' in the database when initializing the module.";
#				$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."ApplicationSettings_Modules WHERE id='".$grouped['moduleID']."'";
#				$__sInfo['values'] = 'None';
#				$GrpMods = $_LinkDB->query($__sInfo['command']);
#				while ($grpmod = $GrpMods->fetch_assoc())
#					{ echo "		<module id=\"".$grpmod['id']."\" name=\"".safeXML($grpmod['name'])."\" icon=\"".safeXML($grpmod['icon'])."\" />\n"; }
#			}
#
#			echo "	   </group>\n";
		}
		echo "	</groups>\n";
		echo "   </xml>\n";
		echo "</s>";
		exit();
	}
	break;




    # --- GENERAL TAB ---


    case 'settings':
	# Save settings to config.php
	if ($_POST['A'] == 'save') {
		if ($_bHostedService) {
			echo "<f><msg>The hosted services policies prevent the settings on this tab from being adjusted.</msg></f>";
			exit();
		}

		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['bMaintenance_ApplicationSettings'],5,'{true|false}')) { exit(); }
		if (! validate($_POST['sMaintenance_ApplicationSettings'],128,'!=<>;\'')) { exit(); }		# NOTE: exclude the "'" character since that's the string encapsulation character
		if (! validate($_POST['sAlertsName_ApplicationSettings'],128,'!=<>;\'')) { exit(); }
		if (! validate($_POST['sAlertsEmail_ApplicationSettings'],128,'a-zA-Z0-9_\.@\-')) { exit(); }
		if (! validate($_POST['sSupportName_ApplicationSettings'],128,'!=<>;\'')) { exit(); }
		if (! validate($_POST['sSupportEmail_ApplicationSettings'],128,'a-zA-Z0-9_\.@\-')) { exit(); }
		if (! validate($_POST['sSecurityName_ApplicationSettings'],128,'!=<>;\'')) { exit(); }
		if (! validate($_POST['sSecurityEmail_ApplicationSettings'],128,'a-zA-Z0-9_\.@\-')) { exit(); }
		if (! validate($_POST['sInterface_ApplicationSettings'],3,'{pro|ent}')) { exit(); }
		if (! validate($_POST['bHostedService_ApplicationSettings'],5,'{true|false}')) { exit(); }
		if (! validate($_POST['bUseCaptchas_ApplicationSettings'],5,'{true|false}')) { exit(); }
		if (! validate($_POST['nFailedAuth_ApplicationSettings'],2,'0-9')) { exit(); }
		if (! validate($_POST['nTimeout_ApplicationSettings'],5,'0-9')) { exit(); }
		if (! validate($_POST['sUpdates_ApplicationSettings'],9,'{automatic|manual}')) { exit(); }
		if (! validate($_POST['sInstall_ApplicationSettings'],9,'{automatic|manual}')) { exit(); }
		if (! validate($_POST['sUriProject_ApplicationSettings'],128,'a-zA-Z0-9:\/%_\.\-')) { exit(); }
		if (! validate($_POST['sUriPayment_ApplicationSettings'],128,'a-zA-Z0-9:\/%_\.\-')) { exit(); }
		if (! validate($_POST['sUriSocial_ApplicationSettings'],128,'a-zA-Z0-9:\/%_\.\-')) { exit(); }
		if (! validate($_POST['sLogEmail_ApplicationSettings'],64,'!=<>;\'')) { exit(); }
		if (! validate($_POST['sLogScript_ApplicationSettings'],64,'!=<>;\'')) { exit(); }
		if (! validate($_POST['sLogModule_ApplicationSettings'],64,'!=<>;\'')) { exit(); }
		if (! validate($_POST['sLogProject_ApplicationSettings'],64,'!=<>;\'')) { exit(); }
		if (! validate($_POST['sDirTemp_ApplicationSettings'],64,'!=<>;\/\'')) { exit(); }
		if (! validate($_POST['sDirData_ApplicationSettings'],64,'!=<>;\/\'')) { exit(); }
		if (! validate($_POST['sDirCron_ApplicationSettings'],64,'!=<>;\/\'')) { exit(); }
		if (! validate($_POST['sDirLogs_ApplicationSettings'],64,'!=<>;\/\'')) { exit(); }
		if (! validate($_POST['sDirMail_ApplicationSettings'],64,'!=<>;\/\'')) { exit(); }
		if (! validate($_POST['sDirVrfy_ApplicationSettings'],64,'!=<>;\/\'')) { exit(); }
		if (! validate($_POST['DB_HOST_ApplicationSettings'],128,'a-zA-Z0-9:\/%_\.\-')) { exit(); }
		if (! validate($_POST['DB_NAME_ApplicationSettings'],24,'a-zA-Z0-9_\-')) { exit(); }
		if (! validate($_POST['DB_ROUN_ApplicationSettings'],32,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['DB_ROPW_ApplicationSettings'],32,'')) { exit(); }
		if (! validate($_POST['DB_RWUN_ApplicationSettings'],32,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['DB_RWPW_ApplicationSettings'],32,'')) { exit(); }
		if (! validate($_POST['DB_PRFX_ApplicationSettings'],16,'a-zA-Z0-9_\-')) { exit(); }
		if (! validate($_POST['AUTH_TB_ApplicationSettings'],32,'a-zA-Z0-9_\-')) { exit(); }
		if (! validate($_POST['AUTH_ID_ApplicationSettings'],32,'a-zA-Z0-9_\-')) { exit(); }
		if (! validate($_POST['AUTH_UN_ApplicationSettings'],32,'a-zA-Z0-9_\-')) { exit(); }
		if (! validate($_POST['AUTH_PW_ApplicationSettings'],32,'a-zA-Z0-9_\-')) { exit(); }

		# obtain the employee information
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'],"\$Populate['manager']<1","Your account does not have sufficient priviledges to load these settings.")) { exit(); }

		# check that the submitting account has permission to access the module
# UPDATED 2025/03/07
#		$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to save settings.";
#		$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
#		$gbl_info['values'] = 'None';
#		$Module = $linkDB->query($gbl_info['command']);
#		$module = $Module->fetch_assoc();
#
#		$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to save settings.";
#		$gbl_info['command'] = "SELECT `write` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
#		$gbl_info['values'] = 'None';
#		$Access = $linkDB->query($gbl_info['command']);
#		if ($Access->num_rows === 0) {			# if the account can't be found, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
#			exit();
#		}						# otherwise the account has permission to access, so...
#		$access = $Access->fetch_assoc();
#		if ($access['write'] == 0) {			# if the account does NOT have 'read' access for this module, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
#			exit();
#		}
		if (! checkPermission('write')) { exit(); }


# DEPRECATED 2025/03/07
#		# if we've made it here, lets write the updates to the database
#		$gbl_errs['error'] = "Failed to update the 'System Config' in the database when saving changes.";
#		$gbl_info['command'] = "UPDATE ".PREFIX."SystemConfiguration SET adminType=\"".$_POST['sAdminType_SystemConfiguration']."\",adminID=\"".$_POST['nAdminList_SystemConfiguration']."\",socialURI=\"".$_POST['sSocialURI_SystemConfiguration']."\",moduleUpdate=\"".$_POST['sModuleUpdates_SystemConfiguration']."\",moduleInstall=\"".$_POST['sInstallUpdates_SystemConfiguration']."\" WHERE id='1'";
#		$gbl_info['values'] = 'None';
#		$stmt = $linkDB->query($gbl_info['command']);
#		if ($stmt === FALSE) { return false; }


		# 1. Update these values to reconstruct the full path
		$_POST['sDirCron_ApplicationSettings'] = "\$_sDirData.'/".$_POST['sDirCron_ApplicationSettings'];	# NOTE: the trailing "'" character will be added below
		$_POST['sDirData_ApplicationSettings'] = '../'.$_POST['sDirData_ApplicationSettings'];
		$_POST['sDirLogs_ApplicationSettings'] = "\$_sDirData.'/".$_POST['sDirLogs_ApplicationSettings'];
		$_POST['sDirMail_ApplicationSettings'] = "\$_sDirData.'/".$_POST['sDirMail_ApplicationSettings'];
		$_POST['sDirTemp_ApplicationSettings'] = '../'.$_POST['sDirTemp_ApplicationSettings'];
		$_POST['sDirVrfy_ApplicationSettings'] = "\$_sDirData.'/".$_POST['sDirVrfy_ApplicationSettings'];


		# make a backup of the config file
		$__sInfo['error'] = "The \"../data/_modules/ApplicationSettings/config.php\" file can not be copied to \"../data/_modules/ApplicationSettings/config.php.bak\" when saving settings.";
		$__sInfo['command'] = "copy(\"../data/_modules/ApplicationSettings/config.php\", \"../data/_modules/ApplicationSettings/config.php.bak\")";
		$__sInfo['values'] = '';
		@copy("../data/_modules/ApplicationSettings/config.php", "../data/_modules/ApplicationSettings/config.php.bak");		# create a backup of the original

		# if we've made it here, lets write the updates to the database
		$__sInfo['error'] = "The 'config.php' file can not be opened when saving settings.";
		$__sInfo['command'] = "fopen('../data/_modules/ApplicationSettings/config.php', 'r')";
		$__sInfo['values'] = '';
		$oFileRead = fopen("../data/_modules/ApplicationSettings/config.php", 'r');
		
		$__sInfo['error'] = "The 'config.php.new' file can not be opened when saving settings.";
		$__sInfo['command'] = "fopen('../data/_modules/ApplicationSettings/config.php.new', 'w')";
		$__sInfo['values'] = '';
		$oFileWrite = fopen("../data/_modules/ApplicationSettings/config.php.new", 'w');

		# WARNING: we update ONLY the lines that were changed so that lines like "$sDirLogs=SCRIPT.'.log'" can remain intact unless SPECIFICALLY changed
		# transpose the header
		while (($sLine = fgets($oFileRead)) !== false) {
			if (substr($sLine, 0, 10) == "# Updated	") { break; }			# once we reach the 'Updated' line, then break to update it
			fwrite($oFileWrite, $sLine);						# transpose the line otherwise
		}

		# update the 'Updated' line
		fwrite($oFileWrite, "# Updated	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n");

		# copy over the rest
		while (($sLine = fgets($oFileRead)) !== false) {
			# transpose the blank lines, comments, constant definition(s), and php code
			if (trim($sLine) == '' || substr($sLine, 0, 1) == "#" || substr($sLine, 0, 7) == "define(" || substr($sLine, 0, 2) == "?>") { fwrite($oFileWrite, $sLine); continue;}

			# transpose the 'System Variables'
			if (substr($sLine, 0, 8) == '$_bDebug' || substr($sLine, 0, 8) == '$_LinkDB' || substr($sLine, 0, 3) == '$__') { fwrite($oFileWrite, $sLine); continue; }

			# transpose the remaining (changed) lines
			$sPairs = explode("=", $sLine);						# split into 'key/value' pairs
			eval("\$sPairs[1]=".$sPairs[1]);					# update the 'value' to contain just the value and not surrounding quotes and trailing semi-colon

			if ($_POST[substr($sPairs[0], 2).'_ApplicationSettings'] == $sPairs[1]) {
				fwrite($oFileWrite, $sLine);											# for unchanged values
			} else if (substr($sPairs[0], 2, 1) == 'n') {
				fwrite($oFileWrite, $sPairs[0]."=".$_POST[substr($sPairs[0], 2).'_ApplicationSettings'].";\n");			# for numeric values
			} else if (substr($sPairs[0], 2, 1) == 's') {										# for updated string values
				if (substr($_POST[substr($sPairs[0], 2).'_ApplicationSettings'], 0, 1) == '$')					#   if the value starts with a variable, then do NOT preceed a "'" character
					{ fwrite($oFileWrite, $sPairs[0]."=".$_POST[substr($sPairs[0], 2).'_ApplicationSettings']."';\n"); }
				else														#   otherwise it is a string, so include the preceeding "'" character
					{ fwrite($oFileWrite, $sPairs[0]."='".$_POST[substr($sPairs[0], 2).'_ApplicationSettings']."';\n"); }
			} else { fwrite($oFileWrite, $sPairs[0]."=".(($_POST[substr($sPairs[0], 2).'_ApplicationSettings'] == '1') ? 'true' : 'false').";\n"); }	# for updated boolean values
		}
		fclose($oFileRead);
		fclose($oFileWrite);

		# Update the config.php
		$__sInfo['error'] = "The \"".$_sDirData."/_modules/ApplicationSettings/config.php.new\" file can not be renamed to \"".$_sDirData."/_modules/ApplicationSettings/config.php\" when saving settings.";
		$__sInfo['command'] = "rename(\"".$_sDirData."/_modules/ApplicationSettings/config.php.new\", \"".$_sDirData."/_modules/ApplicationSettings/config.php\")";
		$__sInfo['values'] = '';
		@rename($_sDirData."/_modules/ApplicationSettings/config.php.new", $_sDirData."/_modules/ApplicationSettings/config.php");	# create a backup of the original

		# if any of the directories were changed, lets make the changes before adjusting the envars.php file
		# WARNING: these NEED to be in this order to prevent race conditions or other problems!!!
		if ($_sDirTemp != $_POST['sDirTemp_ApplicationSettings']) {
			$__sInfo['error'] = "The \"".$_sDirTemp."\" directory can not be renamed to \"".$_POST['sDirTemp_ApplicationSettings']."\" when saving settings.";
			$__sInfo['command'] = "rename(\"".$_sDirTemp."\", \"".$_POST['sDirTemp_ApplicationSettings']."\")";
			$__sInfo['values'] = '';
			@rename($_sDirTemp, $_POST['sDirTemp_ApplicationSettings']);
		}
		if ($_sDirCron != $_POST['sDirCron_ApplicationSettings']) {
			$__sInfo['error'] = "The \"".$_sDirCron."\" directory can not be renamed to \"".$_POST['sDirCron_ApplicationSettings']."\" when saving settings.";
			$__sInfo['command'] = "rename(\"".$_sDirCron."\", \"".$_POST['sDirCron_ApplicationSettings']."\")";
			$__sInfo['values'] = '';
			eval("\$_POST['sDirCron_ApplicationSettings']=".$_POST['sDirCron_ApplicationSettings']."';");	# NOTE: we have to eval() since it contains a variable in it's value
			@rename($_sDirCron, $_POST['sDirCron_ApplicationSettings']);
		}
		if ($_sDirMail != $_POST['sDirMail_ApplicationSettings']) {
			$__sInfo['error'] = "The \"".$_sDirMail."\" directory can not be renamed to \"".$_POST['sDirMail_ApplicationSettings']."\" when saving settings.";
			$__sInfo['command'] = "rename(\"".$_sDirMail."\", \"".$_POST['sDirMail_ApplicationSettings']."\")";
			$__sInfo['values'] = '';
			eval("\$_POST['sDirMail_ApplicationSettings']=".$_POST['sDirMail_ApplicationSettings']."';");
			@rename($_sDirMail, $_POST['sDirMail_ApplicationSettings']);
		}
		if ($_sDirVrfy != $_POST['sDirVrfy_ApplicationSettings']) {
			$__sInfo['error'] = "The \"".$_sDirVrfy."\" directory can not be renamed to \"".$_POST['sDirVrfy_ApplicationSettings']."\" when saving settings.";
			$__sInfo['command'] = "rename(\"".$_sDirVrfy."\", \"".$_POST['sDirVrfy_ApplicationSettings']."\")";
			$__sInfo['values'] = '';
			eval("\$_POST['sDirVrfy_ApplicationSettings']=".$_POST['sDirVrfy_ApplicationSettings']."';");
			@rename($_sDirVrfy, $_POST['sDirVrfy_ApplicationSettings']);
		}
		if ($_sDirLogs != $_POST['sDirLogs_ApplicationSettings']) {		# NOTE: this is low on the list so any encounters above can be logged
			$__sInfo['error'] = "The \"".$_sDirLogs."\" directory can not be renamed to \"".$_POST['sDirLogs_ApplicationSettings']."\" when saving settings.";
			$__sInfo['command'] = "rename(\"".$_sDirLogs."\", \"".$_POST['sDirLogs_ApplicationSettings']."\")";
			$__sInfo['values'] = '';
			eval("\$_POST['sDirLogs_ApplicationSettings']=".$_POST['sDirLogs_ApplicationSettings']."';");
			@rename($_sDirLogs, $_POST['sDirLogs_ApplicationSettings']);
		}
		if ($_sDirData != $_POST['sDirData_ApplicationSettings']) {
# LFFT OFF - update the '$_sDirData/_modules/webBooks' symlink to the new value so the Application.html reference does not stop working
			$__sInfo['error'] = "The \"".$_sDirData."\" directory can not be renamed to \"".$_POST['sDirData_ApplicationSettings']."\" when saving settings.";
			$__sInfo['command'] = "rename(\"".$_sDirData."\", \"".$_POST['sDirData_ApplicationSettings']."\")";
			$__sInfo['values'] = '';
			@rename($_sDirData, $_POST['sDirData_ApplicationSettings']);
		}

		echo "<s><msg>The application settings have been updated successfully!</msg></s>";
		exit();
	}
	break;




    case 'database':
	# Exports the entire database to a .tgz
	if ($_POST['A'] == 'export') {
		# http://stackoverflow.com/questions/45953/php-execute-a-background-process
		# https://florian.ec/articles/running-background-processes-in-php/
		# http://www.itworld.com/article/2833078/it-management/3-ways-to-import-and-export-a-mysql-database.html
		# http://wiki.dreamhost.com/Backup_MySQL

		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'],"\$Populate['manager']<1","Your account does not have sufficient priviledges to load these settings.")) { exit(); }

		# check that the submitting account has permission to access the module
# UPDATED 2025/03/08
#		$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
#		$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
#		$gbl_info['values'] = 'None';
#		$Module = $linkDB->query($gbl_info['command']);
#		$module = $Module->fetch_assoc();
#
#		$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
#		$gbl_info['command'] = "SELECT `read` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
#		$gbl_info['values'] = 'None';
#		$Access = $linkDB->query($gbl_info['command']);
#		if ($Access->num_rows === 0) {			# if the account can't be found, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to open (read) data in this module.</msg></f>";
#			exit();
#		}						# otherwise the account has permission to access, so...
#		$access = $Access->fetch_assoc();
#		if ($access['read'] == 0) {			# if the account does NOT have 'read' access for this module, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to open (read) data in this module.</msg></f>";
#			exit();
#		}
		if (! checkPermission('read')) { exit(); }


		# now perform the database export
# UPDATED 2025/03/08
#		$cmd = "mysqldump --opt --user=".DBUSER." --password=".DBPASS." --host=".DBHOST." ".DBNAME." > ../temp/exported.sql && php -f system_configuration.php export '".$_POST['username']."'";
#		exec(sprintf("%s >%s 2>%s &", $cmd, $log, $log));
		exec(escapeshellcmd($_sDirData."/_modules/".str_replace(' ','',MODULE).'/export.sh '.$_POST['sUsername']).' >'.$_sDirTemp.'/backup.log 2>&1 &');
		# NOTE: see the "($argv[1] == 'export')" section above for the follow-up email
# VER2 - update the export.sh to NOT use the --password switch (since it can be obtained by malicious person); update to use a file or some other method of obtaining it

		echo "<s><msg>The database information is being exported and you will be emailed once it is available for download.</msg></s>";
		exit();




	# Imports backed up SQL from .tgz/.sql file
	} else if ($_POST['A'] == 'import') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['sFilename'],64,'!=<>;')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'ro','*',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'],"\$Populate['manager']<1","Your account does not have sufficient priviledges to load these settings.")) { exit(); }

		# check that the submitting account has permission to access the module
# UPDATED 2025/03/09
#		$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
#		$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
#		$gbl_info['values'] = 'None';
#		$Module = $linkDB->query($gbl_info['command']);
#		$module = $Module->fetch_assoc();
#
#		$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
#		$gbl_info['command'] = "SELECT `write` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
#		$gbl_info['values'] = 'None';
#		$Access = $linkDB->query($gbl_info['command']);
#		if ($Access->num_rows === 0) {			# if the account can't be found, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
#			exit();
#		}						# otherwise the account has permission to access, so...
#		$access = $Access->fetch_assoc();
#		if ($access['write'] == 0) {			# if the account does NOT have 'read' access for this module, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
#			exit();
#		}
		if (! checkPermission('read')) { exit(); }


		# check the uploaded file has a correct extension
		if (substr($_POST['sFilename'], -3) != 'tgz' && substr($_POST['sFilename'], -3) != 'sql') {
			echo "<f><msg>The uploaded database file has an invalid extension. Please use those ending in 'tgz' or 'sql'.</msg></f>";
			exit();
		}
#file_put_contents('debug.txt', "01\n", FILE_APPEND);
#foreach (glob($_sDirTemp."/*") as $dataset) {
#	file_put_contents('debug.txt', "01b temp contents: ".$dataset."\n", FILE_APPEND);
#}

		# remove any prior attempts at installation
		if (file_exists($_sDirTemp.'/import'))
			{ delTree($_sDirTemp.'/import/'); }

		# (re)create the installation directory
		if (! file_exists($_sDirTemp.'/import')) {					# create the 'install' directory if it doesn't exist
			$gbl_errs['error'] = "The \"".$_sDirTemp."/import\" directory can not be created during database importation.";
			$gbl_info['command'] = "mkdir(\"".$_sDirTemp."/import\", 0775, true)";
			$gbl_info['values'] = '';
			@mkdir($_sDirTemp.'/import', 0775, true);
		}

		# move the downloaded file into the 'import' directory
		$gbl_errs['error'] = "The \"".$_sDirTemp."/".$_POST['sFilename']."\" directory can not be renamed to \"".$_sDirTemp."/import/".$_POST['sFilename']."\" during database importation.";
		$gbl_info['command'] = "rename(\"".$_sDirTemp."/".$_POST['sFilename']."\", \"".$_sDirTemp."/import/".$_POST['sFilename']."\")";
		$gbl_info['values'] = '';
		@rename($_sDirTemp."/".$_POST['sFilename'], $_sDirTemp."/import/".$_POST['sFilename']);

# UPDATED 2025/03/08
#		if (substr($_POST['sFilename'], -3) == 'tgz') {}
#		# decompress the tgz file						  http://stackoverflow.com/questions/9416508/php-untar-gz-without-exec
#		exec("tar zxf ".$_sDirTemp."/import/".$_POST['sFilename']." -C ".$_sDirTemp."/import", $gbl_null);	# uncompress the .tgz file
#
#		# install the .sql files
#		if (file_exists($_sDirTemp.'/import')) {
#			foreach (glob($_sDirTemp."/import/*.sql") as $dataset) {		# http://stackoverflow.com/questions/3062154/php-list-of-specific-files-in-a-directory
## LEFT OFF - move the below to the import.sh script
#				$cmd = "mysql --user=".DBUSER." --password=".DBPASS." --host=".DBHOST." ".DBNAME." < ".$dataset." && php -f system_configuration.php import '".$_POST['username']."' ".$dataset;	# http://stackoverflow.com/questions/147821/loading-sql-files-from-within-php
#				$log = "../".$_sDirTemp."/import/".$dataset.".log";
#				exec(sprintf("%s 2>%s &", $cmd, $log));
#				# NOTE: see the "($argv[1] == 'import')" section above for the follow-up email
#			}
#		}
## LEFT OFF - move the below into the import.sh script
#		# cleanup
#		#delTree('../'.$_sDirTemp.'/import/');						WARNING: we can NOT delete the temp imported directory here since all the calls are backgrounded and we don't know when they will complete!

		exec(escapeshellcmd($_sDirData."/_modules/".str_replace(' ','',MODULE).'/import.sh '.$_POST['sUsername']).' "'.$_POST['sFilename'].'" >'.$_sDirTemp.'/import.log 2>&1 &');
		# NOTE: see the "($argv[1] == 'import')" section above for the follow-up email
# VER2 - update the import.sh to NOT use the --password switch (since it can be obtained by malicious person); update to use a file or some other method of obtaining it

		echo "<s><msg>The database information is being imported and you will be emailed after each data set has been processed.</msg></s>";
		exit();




// LEFT OFF - update the below code
	# Archives records from database to .tgz file
	} else if ($_POST['A'] == 'archive') {
		# http://stackoverflow.com/questions/9390085/export-mysql-only-after-a-certain-date
		# http://stackoverflow.com/questions/4845888/mysql-export-table-based-on-a-specific-condition

		# validate all submitted data
		if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['date'],10,'0-9/\-')) { exit(); }

		# load the users account info in the global variable
		if (USERS == '')							# IF we need to access the native application DB table, then...
			{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'])) {exit();} }
		else									# OTHERWISE, we have mapped DB values, so pull the values from that table
			{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'])) {exit();} }

		# check that the submitting account has permission to access the module
		$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to archive the database.";
		$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
		$gbl_info['values'] = 'None';
		$Module = $linkDB->query($gbl_info['command']);
		$module = $Module->fetch_assoc();

		$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to archive the database.";
		$gbl_info['command'] = "SELECT `read` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
		$gbl_info['values'] = 'None';
		$Access = $linkDB->query($gbl_info['command']);
		if ($Access->num_rows === 0) {			# if the account can't be found, then...
			echo "<f><msg>Your account does not have sufficient priviledges to open (read) data in this module.</msg></f>";
			exit();
		}						# otherwise the account has permission to access, so...
		$access = $Access->fetch_assoc();
		if ($access['read'] == 0) {			# if the account does NOT have 'read' access for this module, then...
			echo "<f><msg>Your account does not have sufficient priviledges to open (read) data in this module.</msg></f>";
			exit();
		}


// LEFT OFF - update the below to cycle the tables looking for 'createdOn' fields
		# NOTES: we export ALL work order data from the supplied date
		#	 we export ALL quotes and invoices data from the supplied date
		#	 we only export some inventory data since certain records are relevent today as they were when they were entered (e.g. discount data - it doesn't expire!)
		$cmd = "mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."WorkOrders  > ../temp/archived-WorkOrders.sql && \
			mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(created)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."WorkOrders_Assigned  > ../temp/archived-WorkOrders_Assigned.sql && \
			mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."WorkOrders_History  > ../temp/archived-WorkOrders_History.sql && \
			mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."WorkOrders_Serials  > ../temp/archived-WorkOrders_Serials.sql && \

			mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."QuotesAndInvoices  > ../temp/archived-QuotesAndInvoices.sql && \
			mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."QuotesAndInvoices_History  > ../temp/archived-QuotesAndInvoices_History.sql && \
			mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."QuotesAndInvoices_Serials  > ../temp/archived-QuotesAndInvoices_Serials.sql && \

			mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."Inventory_Queues  > ../temp/archived-Inventory_Queues.sql && \
			mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."Inventory_History  > ../temp/archived-Inventory_History.sql && \
			mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."Inventory_Serials  > ../temp/archived-Inventory_Serials.sql && \

			php -f system_configuration.php archive '".$_POST['username']."' ".$_POST['date'];
		$log = "../temp/archived.log";
		exec(sprintf("%s 2>%s &", $cmd, $log));
		# NOTE: see the "($argv[1] == 'archive')" section above for the follow-up email

		echo "<s><msg>The database information is being archived and you will be emailed once it is available for download.</msg></s>";
		exit();
	}
	break;




    # --- MODULES TAB ---


    case 'updates':
	# Download Available Updates
	if ($_POST['A'] == 'download') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
# UPDATED 2025/03/05
#		$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
#		$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
#		$gbl_info['values'] = 'None';
#		$Module = $linkDB->query($gbl_info['command']);
#		$module = $Module->fetch_assoc();
#
#		$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
#		$gbl_info['command'] = "SELECT `read` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
#		$gbl_info['values'] = 'None';
#		$Access = $linkDB->query($gbl_info['command']);
#		if ($Access->num_rows === 0) {			# if the account can't be found, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to open (read) data in this module.</msg></f>";
#			exit();
#		}						# otherwise the account has permission to access, so...
#		$access = $Access->fetch_assoc();
#		if ($access['read'] == 0) {			# if the account does NOT have 'read' access for this module, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to open (read) data in this module.</msg></f>";
#			exit();
#		}
		if (! checkPermission('read')) { exit(); }


		# if we've made it here, the user is authorized to interact
		if (! ApplicationSettings_Update()) {
			if (count($__sMsgs) == 0)
				{ echo "<f><msg>A failure occurred while attempting to obtain software updates.</msg></f>"; }
			else
				{ echo "<f><msg>".$__sMsgs[0]."</msg></f>"; }
			exit();
		}

		$sXML = '';

		# check if any updates have been downloaded
		if (file_exists($_sDirTemp.'/update')) {
			$updates = scandir($_sDirTemp.'/update');
			foreach ($updates as $key=>$val) {
				# skip the . and .. and .md5 entries in the directory listing
				if ($val == '.' || $val == '..' || substr($val, -4) == 'hash') { continue; }

				$sXML .= "	<update module=\"".$val."\" />\n";
			}
		}

		if ($sXML == '') {				# if none have been downloaded, then alert the user!
			echo "<s><msg>The system is up-to-date!</msg></s>";
		} else {					# otherwise, populate the updates!
			echo "<s>\n";
			echo "   <xml>\n";
			echo "\t".$sXML;
			echo "   </xml>\n";
			echo "</s>";
		}
		exit();




	# Installs Selected Updates
	} else if ($_POST['A'] == 'install') {
#file_put_contents('debug.txt', "_POST:\n".print_r($_POST, true)."\n", FILE_APPEND);
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['sUpdatesList'],1280,'a-zA-Z0-9|_\.\-')) { exit(); }
# LEFT OFF - test the download and install of updates!
		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('write')) { exit(); }


		# if we've made it here, the user is authorized to interact
		$s_Installed = array();
		$s_Downloads = explode("|", $_POST['sUpdatesList']);
		foreach ($s_Downloads as $sFilename) {
file_put_contents('debug.txt', "iterating |".$sFilename."|\n", FILE_APPEND);
			if (ApplicationSettings_Install($sFilename)) {		# this function performs all the actual work
file_put_contents('debug.txt', "success!\n", FILE_APPEND);
				array_push($s_Installed, $sFilename);
			} else {
file_put_contents('debug.txt', "failure...\n", FILE_APPEND);
				if (count($__sMsgs) == 0)
					{ echo "<f><msg>A failure occurred while attempting to obtain software updates.</msg></f>"; }
				else
					{ echo "<f><msg>".$__sMsgs[0]."</msg></f>"; }
				exit();
			}
		}

		echo "<s>\n";
		echo "   <xml>\n";
		foreach ($s_Installed as $sFilename) { echo "\t<update module=\"".$sFilename."\" />\n"; }	# we need to return the filename (passed from the UI)
		echo "   </xml>\n";
		echo "</s>";
		exit();
	}
	break;




    case 'module':
	# Install Uploaded Module
	if ($_POST['A'] == 'install') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['sFilename'],64,'a-zA-Z0-9 \._\-')) { exit(); }
		# NOTE: the below will be converted into boolean since it's true/false!!!
		if (! validate($_POST['bForce'],5,'{true|false}')) { exit(); }			# if the user was informed that the uploaded file has no matching hash in our repo, and if it needs to be installed anyways...

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('write')) { exit(); }


		# if we've made it here, the user is authorized to interact
		# remove any prior attempts at installation -IF- we're not doing all this again after the user was prompted to install module anyways
		if (file_exists('../temp/install') && ! $_POST['bForce']) {
			if (! delTree('../temp/install/')) {
				echo "<f><msg>An error was encountered clearing out the temp installation directory.</msg></f>";
				exit();
			}
		}

		# (re)create the installation directory (if we just cleaned up a prior install, otherwise we are forcing the install after the user was prompted)
		if (! file_exists('../temp/install')) {				# create the 'install' directory if it doesn't exist
			$__sInfo['error'] = "The \"../temp/install\" directory can not be created during module installation.";
			$__sInfo['command'] = "mkdir(\"../temp/install\", 0775, true)";
			$__sInfo['values'] = '';
			@mkdir('../temp/install', 0775, true);
		}

		# move the downloaded file into the 'install' directory (if we just cleaned up a prior install, otherwise we are forcing the install after the user was prompted)
		if (! file_exists('../temp/install/'.$_POST['sFilename'])) {
			$__sInfo['error'] = "The \"../temp/".$_POST['sFilename']."\" file can not be moved to \"../temp/install/".$_POST['sFilename']."\" during module installation.";
			$__sInfo['command'] = "rename(\"../temp/".$_POST['sFilename']."\", \"../temp/install/".$_POST['sFilename']."\")";
			$__sInfo['values'] = '';
			@rename("../temp/".$_POST['sFilename'], "../temp/install/".$_POST['sFilename']);
		}

		# decompress the tgz file								  http://stackoverflow.com/questions/9416508/php-untar-gz-without-exec
		if (! $_POST['bForce'])									# NOTE: if 'bForce == false' then it will be the first iteration of this code which will uncompress the file
			{ exec("tar zxf ../temp/install/".$_POST['sFilename']." -C ../temp/install", $__sNull); }		# uncompress the .soft (.tar.gz) file
		if (! file_exists('../temp/install/software/MODULE')) {
			echo "<f><msg>The module installation has stopped due to the upload missing a 'MODULE' file.</msg></f>";
			delTree('../temp/install/');
			exit();
		}
		$sMODULE = trim(file_get_contents('../temp/install/software/MODULE'));
		$sModule = str_replace(' ','',$sMODULE);						# store the modules name in CamelCase (e.g. 'ApplicationSettings')

		# lets obtain the hash file for the module from our online repo
		if (file_exists('../temp/install/software/data/_modules/'.$sModule.'/version') && ! $_POST['bForce']) {		# if we've never checked for updates for this module, then...
			$sInstalled = trim(file_get_contents('../temp/install/software/data/_modules/'.$sModule.'/version'));	# obtain the module version from the file to know which hash to fetch

			# prompt the user if they want to install the (3rd-party?) module anyways without validating the uploaded file?
			# https://stackoverflow.com/questions/10444059/file-exists-returns-false-for-remote-url
			$s_Headers = @get_headers('http://repo.cliquesoft.org/vanilla/1.0/webbooks/_exts/'.$sModule.'/'.$sInstalled.'.md5');
			if (strpos($s_Headers[0], '404')) {
				echo "<f><msg>The module installation has stopped due to the absence of a corresponding\nhash file in the repo, preventing validation of the module. This is normally\ntriggered by third-party modules that have not been added to the online repo.\nDo you want to continue the installation of this file?</msg><data prompt='true' /></f>";
				exit();
			}

			# NOTE: we place the file in this location so the installation script will work correctly
			$__sInfo['error'] = "The modules' hash can not be obtained from the repo during the installation.";
			$__sInfo['command'] = "file_put_contents('../temp/install/".$sModule.".md5', file_get_contents('http://repo.cliquesoft.org/vanilla/1.0/webbooks/_exts/".$sModule."/".$sInstalled.".md5'))";
			$__sInfo['values'] = '';
			file_put_contents('../temp/install/'.$sModule.'.md5', file_get_contents('http://repo.cliquesoft.org/vanilla/1.0/webbooks/_exts/'.$sModule.'/'.$sInstalled.'.md5'));
		}

		# if we're not overriding the file hash validation step (after prompting the user), then...
		if (! $_POST['bForce']) {
			# we should have the modules' hash file at this point
			$__sInfo['error'] = "The '../temp/install/".$sModule.".md5' file can not be found while installing the module.";
			$__sInfo['command'] = "file_get_contents('../temp/install/".$sModule.".md5')";
			$__sInfo['values'] = 'None';
			$s_RepoHash = explode(" ", file_get_contents('../temp/install/'.$sModule.'.md5'));	# isolate just the hash

			# now that we've obtained the files, lets validate them against their hash file
			$sFileHash = @md5_file('../temp/install/'.$_POST['sFilename']);
			if ($sFileHash != $s_RepoHash[0]) {
				echo "<f><msg>The \"".$sMODULE."\" did not validate against its hash file and was deleted:&lt;br /&gt;".$sFileHash."&lt;br /&gt;".$s_RepoHash[0]."</msg></f>";
				delTree('../temp/install/');
				exit();
			}
		}

		# run the setup if the file validated
		if (! file_exists('../temp/install/software/code/'.$sModule.'_Setup.php')) {
			echo "<f><msg>The module installation has stopped due to the upload missing a \"".$sModule."_Setup.php\" file.</msg></f>";
			exit();
		}
		require('../temp/install/software/code/'.$sModule.'_Setup.php');
		if (! call_user_func($sModule.'_Install')) {			# http://stackoverflow.com/questions/1005857/how-to-call-php-function-from-string-stored-in-a-variable
			# NOTE: the function will be responsible for displaying any error messages
			delTree('../temp/install/');
			exit();
		}

		# cleanup
		delTree('../temp/install/');

		$sMsg = '';
		if (count($__sMsgs) > 0) { $sMsg = " ".$__sMsgs[0]; }
		# NOTE: the '$__sInfo['id']' value is stored in the installation script
		echo "<s><msg>The module has been installed successfully!".$sMsg."</msg><data id='".$__sInfo['id']."' sName=\"".$sMODULE."\" /></s>";
		exit();




	# Uninstall Selected Module
	} else if ($_POST['A'] == 'uninstall') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['sModule'],24,'a-zA-Z0-9 ')) { exit(); }
		if (! validate($_POST['bRetainData'],5,'{true|false}')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# check that the submitting account has permission to access the module
		if (! checkPermission('write')) { exit(); }


		# if we've made it here, the user is authorized to interact

		$sModule = str_replace(' ', '', $_POST['sModule']);						# convert the module name into CamelCase

		# move the modules' setup file back into './code'
		if (! file_exists($sModule.'_Setup.php')) {
			if (! file_exists('../data/_modules/'.$sModule.'/'.$sModule.'_Setup.php')) {
				echo "<f><msg>The modules' setup file could not be found to perform the uninstall.</msg></f>";
				exit();
			}

			$__sInfo['error'] = "The \"../data/_modules/".$sModule."/".$sModule."_Setup.php\" file can not be moved to \"".$sModule."_Setup.php\" during module installation.";
			$__sInfo['command'] = "rename(\"../data/_modules/".$sModule."/".$sModule."_Setup.php\", \"".$sModule."_Setup.php\")";
			$__sInfo['values'] = '';
			@rename("../data/_modules/".$sModule."/".$sModule."_Setup.php", $sModule."_Setup.php");
		}

		# call the uninstall function for the module
		require($sModule.'_Setup.php');
		if (! call_user_func($sModule.'_Uninstall')) { exit(); }					# http://stackoverflow.com/questions/1005857/how-to-call-php-function-from-string-stored-in-a-variable

		# delete the modules' setup file
		if (file_exists($sModule.'_Setup.php')) { 
			$__sInfo['error'] = "The 'code/".$sModule."_Setup.php' file can not be deleted.";
			$__sInfo['command'] = "unlink('".$sModule."_Setup.php')";
			$__sInfo['values'] = 'None';
			@unlink($sModule.'_Setup.php');
		}

		echo "<s><msg>The module has been uninstalled successfully!</msg></s>";
		exit();
	}
	break;




    # --- GROUPS TAB ---


    case 'group':
	# Loads the selected groups info
	if ($_POST['A'] == 'load') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
# VER2 - the checks are no longer working...
#		if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'],"\$Populate['manager']<1","Your account does not have sufficient priviledges to load these settings.")) { exit(); }
		if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }


		# 1. Store the selected group information
		$__sInfo['error'] = "Failed to obtain the 'Configured Dashboard Groups' in the database when loading the selected group.";
		$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."ApplicationSettings_Groups WHERE id='".$_POST['id']."' LIMIT 1";
		$__sInfo['values'] = 'None';
		$Groups = $_LinkDB->query($__sInfo['command']);
		$group = $Groups->fetch_assoc();

# VER2 - create a single call for this block and the following once (using 'LEFT JOIN')
		# 2. Store all the modules within the selected group
		$__sInfo['error'] = "Failed to obtain the 'Grouped Modules' in the database when loading the selected group.";
		$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."ApplicationSettings_Grouped WHERE groupID='".$group['id']."'";
		$__sInfo['values'] = 'None';
		$Grouped = $_LinkDB->query($__sInfo['command']);


		# now relay the information to the user
		$XML =	"<s>\n" .
			"   <xml>\n" .
			"	<groups>\n" .
			"	   <group id=\"".$group['id']."\" sName=\"".safeXML($group['name'])."\" sIcon=\"".safeXML($group['icon'])."\">\n";
		while ($grouped = $Grouped->fetch_assoc()) {
			# 3. Cycle all the modules in the selected group
			$__sInfo['error'] = "Failed to obtain the 'Grouped Module' in the database when loading the selected group.";
			$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."ApplicationSettings_Modules WHERE id='".$grouped['moduleID']."'";
			$__sInfo['values'] = 'None';
			$Modules = $_LinkDB->query($__sInfo['command']);
			while ($module = $Modules->fetch_assoc())
				{ $XML .= "		<module id=\"".$module['id']."\" sName=\"".safeXML($module['name'])."\" sIcon=\"".safeXML($module['icon'])."\" />\n"; }
		}
		$XML .=	"	   </group>\n" .
			"	</groups>\n" .
			"   </xml>\n" .
			"</s>\n";
		echo $XML;
		exit();




	# Creates/Updates a group
	} else if ($_POST['A'] == 'save') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['sName'],24,'a-zA-Z0-9 _\-')) { exit(); }
		if (! validate($_POST['sIcon'],48,'!=<>;')) { exit(); }
		# NOTE: the below determines whether this is a creation or update
		if (array_key_exists('id', $_POST)) { if (! validate($_POST['id'],20,'0-9')) {exit();} } else { $_POST['id'] = 0; }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'],"\$Populate['manager']<1","Your account does not have sufficient priviledges to load these settings.")) { exit(); }


		# check that the submitting account has permission to access the module
# UPDATED 2025/03/05
#		$__sInfo['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission.";
#		$__sInfo['command'] = "SELECT id FROM ".DB_PRFX."ApplicationSettings_Modules WHERE name='".MODULE."' LIMIT 1";
#		$__sInfo['values'] = 'None';
#		$Module = $_LinkDB->query($__sInfo['command']);
#		$module = $Module->fetch_assoc();
#
#		$__sInfo['error'] = "Failed to find the Employee record in the database when checking for access permission.";
#		$__sInfo['command'] = "SELECT `add` FROM ".DB_PRFX."Employees_Access WHERE employeeID='".$__sUser['id']."' AND moduleID='".$module['id']."' LIMIT 1";
#		$__sInfo['values'] = 'None';
#		$Access = $_LinkDB->query($__sInfo['command']);
#		if ($Access->num_rows === 0) {			# if the account can't be found, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to add (add) data in this module.</msg></f>";
#			exit();
#		}						# otherwise the account MAY have permission to access, so...
#		$access = $Access->fetch_assoc();		# load the access information for the account
#		if ($access['add'] == 0) {			# if the account does NOT have 'read' access for this module, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to add (add) data in this module.</msg></f>";
#			exit();
#		}
		if (! checkPermission('add')) { exit(); }


		# if we've made it here, the user is authorized to interact
		if ($_POST['id'] == 0) {
			$__sInfo['error'] = "Failed to create a new dashboard group in the database.";
			$__sInfo['command'] = "INSERT INTO ".DB_PRFX."ApplicationSettings_Groups (name,icon) VALUES (?,?)";
			$__sInfo['values'] = '[s] '.$_POST['sName'].', [s] '.$_POST['sIcon'];
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			$stmt->bind_param('ss', $_POST['sName'], $_POST['sIcon']);
			$stmt->execute();

			echo "<s><msg>The group has been created successfully!</msg><data id='".$stmt->insert_id."'></data></s>";
		} else {
			$__sInfo['error'] = "Failed to update an existing dashboard group in the database.";
			$__sInfo['command'] = "UPDATE ".DB_PRFX."ApplicationSettings_Groups SET name=?,icon=? WHERE id=?";
			$__sInfo['values'] = '[s] '.$_POST['sName'].', [s] '.$_POST['sIcon'].', [i] '.$_POST['id'];
			$stmt = $_LinkDB->prepare($__sInfo['command']);
			$stmt->bind_param('ssi', $_POST['sName'], $_POST['sIcon'], $_POST['id']);
			$stmt->execute();

			echo "<s><msg>The group has been updated successfully!</msg></s>";
		}
		exit();




	# Delete the selected group
	} else if ($_POST['A'] == 'delete') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['id'],20,'0-9')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'],"\$Populate['manager']<1","Your account does not have sufficient priviledges to load these settings.")) { exit(); }


		# check that the submitting account has permission to access the module
# UPDATED 2025/03/05
#		$__sInfo['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission.";
#		$__sInfo['command'] = "SELECT id FROM ".DB_PRFX."ApplicationSettings_Modules WHERE name='".MODULE."' LIMIT 1";
#		$__sInfo['values'] = 'None';
#		$Module = $_LinkDB->query($__sInfo['command']);
#		$module = $Module->fetch_assoc();
#
#		$__sInfo['error'] = "Failed to find the Employee record in the database when checking for access permission.";
#		$__sInfo['command'] = "SELECT `del` FROM ".DB_PRFX."Employees_Access WHERE employeeID='".$__sUser['id']."' AND moduleID='".$module['id']."' LIMIT 1";
#		$__sInfo['values'] = 'None';
#		$Access = $_LinkDB->query($__sInfo['command']);
#		if ($Access->num_rows === 0) {			# if the account can't be found, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to remove (delete) data in this module.</msg></f>";
#			exit();
#		}						# otherwise the account MAY have permission to access, so...
#		$access = $Access->fetch_assoc();		# load the access information for the account
#		if ($access['del'] == 0) {			# if the account does NOT have 'read' access for this module, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to remove (delete) data in this module.</msg></f>";
#			exit();
#		}
		if (! checkPermission('del')) { exit(); }


		# if we've made it here, the user is authorized to interact
		$__sInfo['error'] = "Failed to delete an existing dashboard group from the database.";
		$__sInfo['command'] = "DELETE FROM ".DB_PRFX."ApplicationSettings_Groups WHERE id=? LIMIT 1";
		$__sInfo['values'] = '[i] '.$_POST['id'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('i', $_POST['id']);
		$stmt->execute();

		$__sInfo['error'] = "Failed to delete an existing modules within the dashboard group from the database.";
		$__sInfo['command'] = "DELETE FROM ".DB_PRFX."ApplicationSettings_Grouped WHERE groupID=?";
		$__sInfo['values'] = '[i] '.$_POST['id'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('i', $_POST['id']);
		$stmt->execute();

		echo "<s><msg>The group has been deleted successfully!</msg></s>";
		exit();
	}
	break;





    case 'Module':
	# Include a module in a group
	if ($_POST['A'] == 'include') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['group'],20,'0-9')) { exit(); }
		if (! validate($_POST['module'],20,'0-9')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'],"\$Populate['manager']<1","Your account does not have sufficient priviledges to load these settings.")) { exit(); }

		# check that the submitting account has permission to access the module
# UPDATED 2025/03/05
#		$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
#		$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
#		$gbl_info['values'] = 'None';
#		$Module = $linkDB->query($gbl_info['command']);
#		$module = $Module->fetch_assoc();
#
#		$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
#		$gbl_info['command'] = "SELECT `add` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
#		$gbl_info['values'] = 'None';
#		$Access = $linkDB->query($gbl_info['command']);
#		if ($Access->num_rows === 0) {			# if the account can't be found, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to add (add) data in this module.</msg></f>";
#			exit();
#		}						# otherwise the account has permission to access, so...
#		$access = $Access->fetch_assoc();
#		if ($access['add'] == 0) {			# if the account does NOT have 'read' access for this module, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to add (add) data in this module.</msg></f>";
#			exit();
#		}
		if (! checkPermission('add')) { exit(); }


		# if we've made it here, the user is authorized to interact
		$__sInfo['error'] = "Failed to create a new dashboard group in the database.";
		$__sInfo['command'] = "INSERT INTO ".DB_PRFX."ApplicationSettings_Grouped (groupID,moduleID) VALUES (?,?)";
		$__sInfo['values'] = '[i] '.$_POST['group'].', [i] '.$_POST['module'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('ii', $_POST['group'], $_POST['module']);
		$stmt->execute();

		echo "<s><msg>The module has been added to the group successfully!</msg></s>";
		exit();




	# Remove a module from a group
	} else if ($_POST['A'] == 'remove') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['group'],20,'0-9')) { exit(); }
		if (! validate($_POST['module'],20,'0-9')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'],"\$Populate['manager']<1","Your account does not have sufficient priviledges to load these settings.")) { exit(); }

		# check that the submitting account has permission to access the module
# UPDATED 2025/03/05
#		$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
#		$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
#		$gbl_info['values'] = 'None';
#		$Module = $linkDB->query($gbl_info['command']);
#		$module = $Module->fetch_assoc();
#
#		$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
#		$gbl_info['command'] = "SELECT `del` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
#		$gbl_info['values'] = 'None';
#		$Access = $linkDB->query($gbl_info['command']);
#		if ($Access->num_rows === 0) {			# if the account can't be found, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to remove (delete) data in this module.</msg></f>";
#			exit();
#		}						# otherwise the account has permission to access, so...
#		$access = $Access->fetch_assoc();
#		if ($access['del'] == 0) {			# if the account does NOT have 'read' access for this module, then...
#			echo "<f><msg>Your account does not have sufficient priviledges to remove (delete) data in this module.</msg></f>";
#			exit();
#		}
		if (! checkPermission('del')) { exit(); }


		# if we've made it here, the user is authorized to interact
		$__sInfo['error'] = "Failed to delete an existing dashboard group from the database.";
		$__sInfo['command'] = "DELETE FROM ".DB_PRFX."ApplicationSettings_Grouped WHERE groupID=? AND moduleID=? LIMIT 1";
		$__sInfo['values'] = '[i] '.$_POST['group'].', [i] '.$_POST['module'];
		$stmt = $_LinkDB->prepare($__sInfo['command']);
		$stmt->bind_param('ii', $_POST['group'], $_POST['module']);
		$stmt->execute();

		echo "<s><msg>The module has been deleted from the group successfully!</msg></s>";
		exit();
	}
	break;




    # --- LOGS TAB ---


    case 'logs':
	# Loads the selected groups info
	if ($_POST['A'] == 'load') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }


		# 1. Obtain all the logs to return to the UI
		$gbl_errs['error'] = "Failed to scan the existing logs directory for files.";
		$gbl_info['command'] = "scandir('../data/_logs',1)";
		$gbl_info['values'] = 'None';
		$files = array_diff(scandir('../data/_logs',1), array('..', '.'));	# get the file(s) in the directory minus the '.' and '..'

		echo "<s>\n";
		echo "   <xml>\n";
		echo "	<logs>\n";
		if ($files) {					# if files have been found in the directory, then...
			for ($i=0; $i<count($files); $i++)
				{ echo "		<log sName=\"".basename($files[$i], '.log')."\" />\n"; }
		}
		echo "	</logs>\n";
		echo "   </xml>\n";
		echo "</s>";
		exit();
	}




    case 'log':
	# Loads the selected log contents
	if ($_POST['A'] == 'load') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['sLog'],40,'!=<>;')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }


		# 1. Return the log contents
		$sContents = file_get_contents('../data/_logs/'.$_POST['sLog']);
		echo "<s><xml><log>".safeXML($sContents)."</log></xml></s>";
		exit();




	# Deletes the selected log
	} else if ($_POST['A'] == 'delete') {
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@\._\-')) { exit(); }
		if (! validate($_POST['sLog'],40,'!=<>;')) { exit(); }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'ro','id',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }


		# 1. Return the log contents
		if (unlink('../data/_logs/'.$_POST['sLog']))
			{ echo "<s><msg>The log file has been deleted successfully!</msg></s>"; }
		else
			{ echo "<f><msg>An error has occurred while attempting to delete the requested log.</msg></f>"; }
	}
	break;
}




# An invalid API was attempted so report this to security
echo "<f><msg>An invalid request has occurred, our staff has been notified.</msg></f>";
if (array_key_exists('sUsername', $_POST)) { $__sInfo['name'] = $_POST['sUsername']; }
if (array_key_exists('email', $_POST)) { $__sInfo['contact'] = $_POST['email']; }
file_put_contents('../data/_logs/'.$_sLogModule, "---------- [ Possible Cracking Attempt ] ----------\nDate: ".gmdate("Y-m-d H:i:s",time())." GMT\nFrom: ".$_SERVER['REMOTE_ADDR']."\n\nProject: ".PROJECT."\nModule: ".MODULE."\nScript: ".SCRIPT."\n\nDB Host: ".DB_HOST."\nDB Name: ".DB_NAME."\nDB Prefix: ".DB_PRFX."\n\nName: ".$__sInfo['name']."\nContact: ".$__sInfo['contact']."\nOther: ".$__sInfo['other']."\n\nSummary: An invalid API value was passed to the script.\n\nVar Dump:\n\n_POST\n".print_r($_POST, true)."\n_GET\n".print_r($_GET, true)."\n\n\n\n", FILE_APPEND);
sendMail($_sSecurityEmail,$_sSecurityName,$_sAlertsEmail,$_sAlertsName,'!!! Possible Cracking Attempt !!!',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nWe might have had a possible cracking attempt made on ".$_.", from ".$_SERVER['REMOTE_ADDR'].", while attempting to pass an invalid API value.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nName: ".$__sInfo['name']."<br />\nContact: ".$__sInfo['contact']."<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: An invalid API value was passed to the script.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br />\n<br />\n[".SCRIPT."; Body]<br />\n<br />\nVar Dump:<br />\n</p>\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre><br />\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");










# --- LEGACY ---
exit();











if ($_POST['action'] == 'init' && $_POST['target'] == 'screen') {			# INITIALIZE THE SCREEN CONTENTS						MIGRATED
	exit();
}


// create the header for any processing below...
#if ($_GET['action'] != '' || $_POST['action'] != '') {
if ($_POST['action'] != '') {
	header('Content-Type: text/xml; charset=utf-8');
	echo "<?xml version='1.0' encoding='UTF-8'?>\n\n";
}


if ($_POST['action'] == 'init' && $_POST['target'] == 'values') {		# INITIALIZE THE SCREEN VALUES								MIGRATED
echo "<f></msg>DEPRECATED 2025/03/04</msg></f>";
exit();
#	# 1. Obtain the system configuration
#	$gbl_errs['error'] = "Failed to obtain the 'System Configuration' in the database when initializing the module.";
#	$gbl_info['command'] = "SELECT * FROM ".PREFIX."SystemConfiguration";
#	$gbl_info['values'] = 'None';
#	$System = $linkDB->query($gbl_info['command']);
#	$system = $System->fetch_assoc();
#
#	# 2. Get the admin account information
#	if ($system['adminType'] == 'employee') {
#		$gbl_errs['error'] = "Failed to obtain the 'Admin Account' in the (Employees) database when initializing the module.";
#		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees WHERE id='".$system['adminID']."'";
#		$gbl_info['values'] = 'None';
#		$Admin = $linkDB->query($gbl_info['command']);
#	} else if ($system['adminType'] == 'provider' || $system['adminType'] == 'vendor') {
#		$gbl_errs['error'] = "Failed to obtain the 'Admin Account' in the (Contacts) database when initializing the module.";
#		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Contacts WHERE id='".$system['adminID']."'";
#		$gbl_info['values'] = 'None';
#		$Admin = $linkDB->query($gbl_info['command']);
#	}
#
#	# 3. Get the employee account information
#	if ($system['adminType'] == 'employee') {
#		$gbl_errs['error'] = "Failed to obtain all 'Employee Accounts' in the (Employees) database when initializing the module.";
#		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees";
#		$gbl_info['values'] = 'None';
#		$People = $linkDB->query($gbl_info['command']);
#	} else if ($system['adminType'] == 'provider' || $system['adminType'] == 'vendor') {
#		$gbl_errs['error'] = "Failed to obtain all 'Employee Accounts' in the (Contacts) database when initializing the module.";
#		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Contacts WHERE type='".$system['adminType']."'";
#		$gbl_info['values'] = 'None';
#		$People = $linkDB->query($gbl_info['command']);
#	}
#
#
#	# now write the XML to the clients browser
#	echo "<s>\n";
#	echo "   <xml>\n";
#	echo "	<general>\n";
#	while ($admin = $Admin->fetch_assoc())
#		{ echo "	   <admin id=\"".$admin['id']."\" name=\"".$admin['name']."\" type=\"".$system['adminType']."\" email=\"".$admin['workEmail']."\" phone=\"".$admin['workPhone']."\" ext=\"".$admin['workExt']."\" mobile=\"".$admin['workMobile']."\" sms=\"".$admin['workMobileSMS']."\" mail=\"".$admin['workMobileEmail']."\" />\n"; }
#
#	if (! HOSTED) {
#		echo "	   <system socialURI=\"".safeXML($system['socialURI'])."\" />\n";
#	} else {
#		// get the correct *LAST* record for each type of desired info below
#		$gbl_errs['error'] = "Failed to obtain the 'Balance' in the database when initializing the module.";
#		$gbl_info['command'] = "SELECT balance FROM ".PREFIX."Funds ORDER BY createdOn DESC LIMIT 1";					// since each record maintains the latest correct 'balance' value, we need to get the *LAST* record for the most up-to-date info
#		$gbl_info['values'] = 'None';
#		$Balance = $linkDB->query($gbl_info['command']);
#
#		$gbl_errs['error'] = "Failed to obtain the 'Non-PayPal' records in the database when initializing the module.";
#		$gbl_info['command'] = "SELECT custom1,custom2 FROM ".PREFIX."Funds WHERE type<>'paypal' ORDER BY createdOn DESC LIMIT 1";	// since the 'paypal' records do NOT store the 'logins' and 'support' values, we need to get the last record that is NOT a 'paypal' record for this info
#		$gbl_info['values'] = 'None';
#		$Options = $linkDB->query($gbl_info['command']);
#
#		if ($Balance->num_rows === 0) {
#			$balance = '0.00';
#		} else {
#			$funds = $Balance->fetch_assoc();
#			$balance = $funds['balance'];
#		}
#		if ($Options->num_rows === 0) {
#			$logins = 1;
#			$support = 0;
#		} else {
#			$options = $Options->fetch_assoc();
#			$logins = $options['custom1'];
#			$support = $options['custom2'];
#		}
#
#		echo "	   <system socialURI=\"".safeXML($system['socialURI'])."\" accessURI=\"".basename(substr(getcwd(),0,-17))."\" logins='".$logins."' support='".$support."' balance='".$balance."' prefix='".PREFIX."' />\n";
#	}
#
#	echo "	   <module update=\"".$system['moduleUpdate']."\" install=\"".$system['moduleInstall']."\" />\n";
#
#	if (file_exists('../temp/update')) {
#		$updates = scandir('../temp/update');
#		foreach ($updates as $key=>$val) {
#			# skip the . and .. and .md5 entries in the directory listing
#			if ($val == '.' || $val == '..' || substr($val, -3) == 'md5') { continue; }
#
#			echo "	   <update module=\"".safeXML($val)."\" />";
#		}
#	}
#
#	echo "	   <dirs data=\"".safeXML($gbl_dirData)."\" logs=\"".safeXML($gbl_dirLogs)."\" cron=\"".safeXML($gbl_dirCron)."\" temp=\"".safeXML($gbl_dirTemp)."\" />\n";
#	echo "	</general>\n";
#
#
#	echo "	<people>\n";
#	while ($person = $People->fetch_assoc())
#		{ echo "	   <person id=\"".$person['id']."\" name=\"".safeXML($person['name'])."\" />\n"; }
#	echo "	</people>\n";
#
#
#	echo "	<groups>\n";
#	while ($group = $Groups->fetch_assoc()) {
#		echo "	   <group id=\"".$group['id']."\" name=\"".safeXML($group['name'])."\" icon=\"".safeXML($group['icon'])."\">\n";
#
## LEFT OFF - create a single call for this block (using 'LEFT JOIN')
#		# 4b. Cycle all the grouped modules
#		$gbl_errs['error'] = "Failed to obtain the 'Grouped Modules' in the database when initializing the module.";
#		$gbl_info['command'] = "SELECT * FROM ".PREFIX."SystemConfiguration_GroupedModules WHERE groupID='".$group['id']."'";
#		$gbl_info['values'] = 'None';
#		$Grouped = $linkDB->query($gbl_info['command']);
#		while ($grouped = $Grouped->fetch_assoc()) {
#			# 4c. Cycle all the modules in the iterated group
#			$gbl_errs['error'] = "Failed to obtain the 'Grouped Module' in the database when initializing the module.";
#			$gbl_info['command'] = "SELECT * FROM ".PREFIX."SystemConfiguration_Modules WHERE id='".$grouped['moduleID']."'";
#			$gbl_info['values'] = 'None';
#			$GrpMods = $linkDB->query($gbl_info['command']);
#			while ($grpmod = $GrpMods->fetch_assoc())
#				{ echo "		<module id=\"".$grpmod['id']."\" name=\"".safeXML($grpmod['name'])."\" icon=\"".safeXML($grpmod['icon'])."\" />\n"; }
#		}
#
#		echo "	   </group>\n";
#	}
#	while ($module = $Modules->fetch_assoc())
#		{ echo "	   <installed id=\"".$module['id']."\" name=\"".safeXML($module['name'])."\" icon=\"".safeXML($module['icon'])."\" />\n"; }
#	echo "	</groups>\n";
#
#	echo "   </xml>\n";
#	echo "</s>";
	exit();


} else if ($_POST['action'] == 'load' && $_POST['target'] == 'admins') {		# LOAD THE POSSIBLE ADMINS BASED ON THE ADMIN TYPE SELECTED			DEPRECATED
echo "<f></msg>DEPRECATED 2025/03/04</msg></f>";
exit();
	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['type'],8,'{employee|provider|vendor}')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }


	# 1. Obtain any admin info from the DB based on the admin type
	if ($_POST['type'] == 'employee') {			# if we need to pull data from the 'Employees' table, then...
		$gbl_errs['error'] = "Failed to obtain the 'Admin account' in the (Employees) database while loading all admin accounts.";
		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees ORDER BY name";
	}  else {						# otherwise we're looking for a vendor or service provider, so...
		$gbl_errs['error'] = "Failed to obtain the 'Admin account' in the (Contacts) database while loading all admin accounts.";
		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Contacts WHERE type='".$_POST['type']."' ORDER BY name";
	}
	$gbl_info['values'] = 'None';
	$Admins = $linkDB->query($gbl_info['command']);


	# now relay the information to the user
	echo "<s>\n";
	echo "   <xml>\n";
	echo "	<admins>\n";
	while ($admin = $Admins->fetch_assoc())
		{ echo "	   <admin id=\"".$admin['id']."\" name=\"".safeXML($admin['name'])."\" />\n"; }
	echo "	</admins>\n";
	echo "   </xml>\n";
	echo "</s>";
	exit();


} else if ($_POST['action'] == 'load' && $_POST['target'] == 'admin') {		# LOAD THE SPECIFIC ADMIN INFO								DEPRECATED
echo "<f></msg>DEPRECATED 2025/03/04</msg></f>";
exit();
	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['type'],8,'{employee|provider|vendor}')) { exit(); }
	if (! validate($_POST['id'],20,'0-9')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }


	# 1. Obtain any admin info from the DB based on the admin type
	if ($_POST['type'] == 'employee') {			# if we need to pull data from the 'Employees' table, then...
		$gbl_errs['error'] = "Failed to obtain the 'Admin account' in the (Employees) database while loading a specific admin account.";
		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees WHERE id='".$_POST['id']."'";
	}  else {						# otherwise we're looking for a vendor or service provider, so...
		$gbl_errs['error'] = "Failed to obtain the 'Admin account' in the (Contacts) database while loading a specific admin account.";
		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Contacts WHERE id='".$_POST['id']."'";
	}
	$gbl_info['values'] = 'None';
	$Admins = $linkDB->query($gbl_info['command']);


	# now relay the information to the user			  WARNING: we store the XML in a variable until everything is completely processed so if any errors occur in the 'while' loop, they are reported correctly
	$XML =	"<s>\n" .
		"   <xml>\n" .
		"	<admins>\n";
	while ($admin = $Admins->fetch_assoc())
		{ $XML .= "	   <admin id=\"".$admin['id']."\" name=\"".safeXML($admin['name'])."\" email=\"".$admin['workEmail']."\" phone=\"".$admin['workPhone']."\" ext=\"".$admin['workExt']."\" mobile=\"".$admin['workMobile']."\" sms=\"".$admin['workMobileSMS']."\" mail=\"".$admin['workMobileEmail']."\" />\n"; }
	$XML .=	"	</admins>\n" .
		"   </xml>\n" .
		"</s>";
	echo $XML;
	exit();


} else if ($_POST['action'] == 'load' && $_POST['target'] == 'group') {		# LOAD THE GROUP INFO THAT IS SELECTED							MIGRATED
echo "<f></msg>DEPRECATED 2025/03/04</msg></f>";
exit();
	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['groupID'],20,'0-9')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }


	# 1. Store the selected group information
	$gbl_errs['error'] = "Failed to obtain the 'Configured Dashboard Groups' in the database when loading the selected group.";
	$gbl_info['command'] = "SELECT * FROM ".PREFIX."SystemConfiguration_Groups WHERE id='".$_POST['groupID']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Groups = $linkDB->query($gbl_info['command']);
	$group = $Groups->fetch_assoc();

	# 2. Store all the modules within the selected group
	$gbl_errs['error'] = "Failed to obtain the 'Grouped Modules' in the database when loading the selected group.";
	$gbl_info['command'] = "SELECT * FROM ".PREFIX."SystemConfiguration_GroupedModules WHERE groupID='".$group['id']."'";
	$gbl_info['values'] = 'None';
	$Grouped = $linkDB->query($gbl_info['command']);


	# now relay the information to the user
	echo "<s>\n";
	echo "   <xml>\n";
	echo "	<groups>\n";
	echo "	   <group id=\"".$group['id']."\" name=\"".safeXML($group['name'])."\" icon=\"".safeXML($group['icon'])."\">\n";
	while ($grouped = $Grouped->fetch_assoc()) {
		# 3. Cycle all the modules in the selected group
		$gbl_errs['error'] = "Failed to obtain the 'Grouped Module' in the database when loading the selected group.";
		$gbl_info['command'] = "SELECT * FROM ".PREFIX."SystemConfiguration_Modules WHERE id='".$grouped['moduleID']."'";
		$gbl_info['values'] = 'None';
		$Modules = $linkDB->query($gbl_info['command']);
		while ($module = $Modules->fetch_assoc())
			{ echo "		<module id=\"".$module['id']."\" name=\"".safeXML($module['name'])."\" icon=\"".safeXML($module['icon'])."\" />\n"; }
	}
	echo "	   </group>\n";
	echo "	</groups>\n";
	echo "   </xml>\n";
	echo "</s>";
	exit();




} else if ($_POST['action'] == 'export' && $_POST['target'] == 'database') {	# EXPORT DATABASE INFO									MIGRATED
echo "<f></msg>DEPRECATED 2025/03/04</msg></f>";
exit();
	# http://stackoverflow.com/questions/45953/php-execute-a-background-process
	# https://florian.ec/articles/running-background-processes-in-php/
	# http://www.itworld.com/article/2833078/it-management/3-ways-to-import-and-export-a-mysql-database.html
	# http://wiki.dreamhost.com/Backup_MySQL

	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'])) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'])) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT `read` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to open (read) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['read'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to open (read) data in this module.</msg></f>";
		exit();
	}


	# now perform the database export
	$cmd = "mysqldump --opt --user=".DBUSER." --password=".DBPASS." --host=".DBHOST." ".DBNAME." > ../temp/exported.sql && php -f system_configuration.php export '".$_POST['username']."'";
	$log = "../temp/exported.log";
	exec(sprintf("%s 2>%s &", $cmd, $log));
	# NOTE: see the "($argv[1] == 'export')" section above for the follow-up email

	echo "<s><msg>The database information is being exported and you will be emailed once it is available for download.</msg></s>";
	exit();


} else if ($_POST['action'] == 'import' && $_POST['target'] == 'database') {	# IMPORT DATABASE INFO									MIGRATED
echo "<f></msg>DEPRECATED 2025/03/04</msg></f>";
exit();
	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['filename'],64,'!=<>;')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'])) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'])) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT `write` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['write'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
		exit();
	}


	# remove any prior attempts at installation
	if (file_exists('../temp/import'))
		{ delTree('../temp/import/'); }

	# (re)create the installation directory
	if (! file_exists('../temp/import')) {					# create the 'install' directory if it doesn't exist
		$gbl_errs['error'] = "The \"../temp/import\" directory can not be created during database importation.";
		$gbl_info['command'] = "mkdir(\"../temp/import\", 0775, true)";
		$gbl_info['values'] = '';
		mkdir('../temp/import', 0775, true);
	}

	// move the downloaded file into the 'import' directory
	$gbl_errs['error'] = "The \"../../temp/".$_POST['filename']."\" directory can not be renamed to \"../../temp/import/".$_POST['filename']."\" during database importation.";
	$gbl_info['command'] = "rename(\"../../temp/".$_POST['filename']."\", \"../../temp/import/".$_POST['filename']."\")";
	$gbl_info['values'] = '';
	rename("../../temp/".$_POST['filename'], "../../temp/import/".$_POST['filename']);

	# decompress the tgz file						  http://stackoverflow.com/questions/9416508/php-untar-gz-without-exec
	exec("tar zxf ../temp/import/".$_POST['filename']." -C ../temp/import", $gbl_null);	# uncompress the .tgz file

	# install the .sql files
	if (file_exists('../temp/import')) {
		foreach (glob("../temp/import/*.sql") as $dataset) {		# http://stackoverflow.com/questions/3062154/php-list-of-specific-files-in-a-directory
			$cmd = "mysql --user=".DBUSER." --password=".DBPASS." --host=".DBHOST." ".DBNAME." < ".$dataset." && php -f system_configuration.php import '".$_POST['username']."' ".$dataset;	# http://stackoverflow.com/questions/147821/loading-sql-files-from-within-php
			$log = "../../temp/import/".$dataset.".log";
			exec(sprintf("%s 2>%s &", $cmd, $log));
			# NOTE: see the "($argv[1] == 'import')" section above for the follow-up email
		}
	}

	# cleanup
	#delTree('../../temp/import/');						WARNING: we can NOT delete the temp imported directory here since all the calls are backgrounded and we don't know when they will complete!

	echo "<s><msg>The database information is being imported and you will be emailed after each data set has been processed.</msg></s>";
	exit();


} else if ($_POST['action'] == 'archive' && $_POST['target'] == 'database') {	# ARCHIVE DATABASE INFO									MIGRATED

echo "<f></msg>DEPRECATED 2025/03/04</msg></f>";
exit();
	# http://stackoverflow.com/questions/9390085/export-mysql-only-after-a-certain-date
	# http://stackoverflow.com/questions/4845888/mysql-export-table-based-on-a-specific-condition

	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['date'],10,'0-9/\-')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'])) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'])) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to archive the database.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to archive the database.";
	$gbl_info['command'] = "SELECT `read` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to open (read) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['read'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to open (read) data in this module.</msg></f>";
		exit();
	}


	# NOTES: we export ALL work order data from the supplied date
	#	 we export ALL quotes and invoices data from the supplied date
	#	 we only export some inventory data since certain records are relevent today as they were when they were entered (e.g. discount data - it doesn't expire!)
	$cmd = "mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."WorkOrders  > ../temp/archived-WorkOrders.sql && \
		mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(created)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."WorkOrders_Assigned  > ../temp/archived-WorkOrders_Assigned.sql && \
		mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."WorkOrders_History  > ../temp/archived-WorkOrders_History.sql && \
		mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."WorkOrders_Serials  > ../temp/archived-WorkOrders_Serials.sql && \

		mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."QuotesAndInvoices  > ../temp/archived-QuotesAndInvoices.sql && \
		mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."QuotesAndInvoices_History  > ../temp/archived-QuotesAndInvoices_History.sql && \
		mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."QuotesAndInvoices_Serials  > ../temp/archived-QuotesAndInvoices_Serials.sql && \

		mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."Inventory_Queues  > ../temp/archived-Inventory_Queues.sql && \
		mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."Inventory_History  > ../temp/archived-Inventory_History.sql && \
		mysqldump --skip-opt --skip-add-drop-table --skip-create-options --no-create-info --add-locks --quick --extended-insert --user=".DBUSER." --password=".DBPASS." --where=\"DATE(createdOn)<DATE('".$_POST['date']."')\" --host=".DBHOST." ".DBNAME." ".PREFIX."Inventory_Serials  > ../temp/archived-Inventory_Serials.sql && \

		php -f system_configuration.php archive '".$_POST['username']."' ".$_POST['date'];
	$log = "../temp/archived.log";
	exec(sprintf("%s 2>%s &", $cmd, $log));
	# NOTE: see the "($argv[1] == 'archive')" section above for the follow-up email

	echo "<s><msg>The database information is being archived and you will be emailed once it is available for download.</msg></s>";
	exit();




} else if ($_POST['action'] == 'save' && $_POST['target'] == 'system') {	# SAVE SYSTEM CONFIGURATION								MIGRATED
	if (HOSTED) {
		echo "<f><msg>The hosted services policies prevent the settings on this tab from being adjusted.</msg></f>";
		exit();
	}

	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['sAdminType_SystemConfiguration'],8,'{employee|provider|vendor}')) { exit(); }
	if (! validate($_POST['nAdminList_SystemConfiguration'],20,'0-9')) { exit(); }
	if (! validate($_POST['sSocialURI_SystemConfiguration'],128,'a-zA-Z0-9:\/%_\.\-')) { exit(); }
	if (! validate($_POST['sModuleUpdates_SystemConfiguration'],9,'{automatic|manual}')) { exit(); }
	if (! validate($_POST['sInstallUpdates_SystemConfiguration'],9,'{automatic|manual}')) { exit(); }
	if (! validate($_POST['sLogsDir_SystemConfiguration'],64,'!=<>;')) { exit(); }
	if (! validate($_POST['sCronDir_SystemConfiguration'],64,'!=<>;')) { exit(); }
	if (! validate($_POST['sDataDir_SystemConfiguration'],64,'!=<>;')) { exit(); }
	if (! validate($_POST['sTempDir_SystemConfiguration'],64,'!=<>;')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to save settings.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to save settings.";
	$gbl_info['command'] = "SELECT `write` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['write'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
		exit();
	}


	# if we've made it here, lets write the updates to the database
	$gbl_errs['error'] = "Failed to update the 'System Config' in the database when saving changes.";
	$gbl_info['command'] = "UPDATE ".PREFIX."SystemConfiguration SET adminType=\"".$_POST['sAdminType_SystemConfiguration']."\",adminID=\"".$_POST['nAdminList_SystemConfiguration']."\",socialURI=\"".$_POST['sSocialURI_SystemConfiguration']."\",moduleUpdate=\"".$_POST['sModuleUpdates_SystemConfiguration']."\",moduleInstall=\"".$_POST['sInstallUpdates_SystemConfiguration']."\" WHERE id='1'";
	$gbl_info['values'] = 'None';
	$stmt = $linkDB->query($gbl_info['command']);
	if ($stmt === FALSE) { return false; }

	# if any of the directories were changed, lets make the changes before adjusting the envars.php file
	if ($gbl_dirLogs != $_POST['sLogsDir_SystemConfiguration']) { 
		$gbl_errs['error'] = "The \"".$gbl_dirLog."\" directory can not be renamed to \"".$_POST['sLogsDir_SystemConfiguration']."\" when saving settings.";
		$gbl_info['command'] = "rename(\"".$gbl_dirLog."\", \"".$_POST['sLogsDir_SystemConfiguration']."\")";
		$gbl_info['values'] = '';
		rename($gbl_dirLogs, $_POST['sLogsDir_SystemConfiguration']);
	}
	if ($gbl_dirCron != $_POST['sCronDir_SystemConfiguration']) { 
		$gbl_errs['error'] = "The \"".$gbl_dirCron."\" directory can not be renamed to \"".$_POST['sCronDir_SystemConfiguration']."\" when saving settings.";
		$gbl_info['command'] = "rename(\"".$gbl_dirCron."\", \"".$_POST['sCronDir_SystemConfiguration']."\")";
		$gbl_info['values'] = '';
		rename($gbl_dirCron, $_POST['sCronDir_SystemConfiguration']);
	}
	if ($gbl_dirData != $_POST['sDataDir_SystemConfiguration']) { 
		$gbl_errs['error'] = "The \"".$gbl_dirData."\" directory can not be renamed to \"".$_POST['sDataDir_SystemConfiguration']."\" when saving settings.";
		$gbl_info['command'] = "rename(\"".$gbl_dirData."\", \"".$_POST['sDataDir_SystemConfiguration']."\")";
		$gbl_info['values'] = '';
		rename($gbl_dirData, $_POST['sDataDir_SystemConfiguration']);
	}
	if ($gbl_dirTemp != $_POST['sTempDir_SystemConfiguration']) { 
		$gbl_errs['error'] = "The \"".$gbl_dirTemp."\" directory can not be renamed to \"".$_POST['sTempDir_SystemConfiguration']."\" when saving settings.";
		$gbl_info['command'] = "rename(\"".$gbl_dirTemp."\", \"".$_POST['sTempDir_SystemConfiguration']."\")";
		$gbl_info['values'] = '';
		rename($gbl_dirTemp, $_POST['sTempDir_SystemConfiguration']);
	}

	# Update the envars.php with the new directories
	$gbl_errs['error'] = "The \"../data/config.php\" file can not be renamed to \"../data/config.php.bak\" when saving settings.";
	$gbl_info['command'] = "rename(\"../data/config.php\", \"../data/config.php.bak\")";
	$gbl_info['values'] = '';
	rename("../data/config.php", "../data/config.php.bak");		# create a backup of the original

	$gbl_errs['error'] = "The 'config.php' file can not be opened when saving settings.";
	$gbl_info['command'] = "fopen('../data/config.php', 'w')";
	$gbl_info['values'] = '';
	$fh = fopen("../data/config.php", 'w');
	fwrite($fh, "<?php\n");
	fwrite($fh, "# config.php	the global definitions used by all projects distributed by Cliquesoft.org\n");
	fwrite($fh, "# Created	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n");
	fwrite($fh, "# Updated	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n\n");
	fwrite($fh, "# Global Constant Definitions\n");
	fwrite($fh, "define('PROJECT','".PROJECT."');\n");
	fwrite($fh, "define('TIMEOUT',".TIMEOUT.");\n");
	fwrite($fh, "define('CAPTCHAS',".(CAPTCHAS == 1 ? 'true' : 'false').");\n");
	fwrite($fh, "define('HOSTED',".(HOSTED == 1 ? 'true' : 'false').");\n\n");
	fwrite($fh, "# Global Directory Definitions\n");
	fwrite($fh, "\$gbl_dirCron='".$_POST['sCronDir_SystemConfiguration']."';\n");
	fwrite($fh, "\$gbl_dirData='".$_POST['sDataDir_SystemConfiguration']."';\n");
	fwrite($fh, "\$gbl_dirLogs='".$_POST['sLogsDir_SystemConfiguration']."';\n");
	fwrite($fh, "\$gbl_dirMail='".$gbl_dirMail."';\n");
	fwrite($fh, "\$gbl_dirTemp='".$_POST['sTempDir_SystemConfiguration']."';\n");
	fwrite($fh, "\$gbl_dirVerify='".$gbl_dirVerify."';\n\n");
	fwrite($fh, "# Global Log Definitions\n");
	fwrite($fh, "\$gbl_logEmail='".$gbl_logEmail."';\n");
	fwrite($fh, "\$gbl_logScript=SCRIPT.'.log';\n");
	fwrite($fh, "\$gbl_logModule=MODULE.'.log';\n");
	fwrite($fh, "\$gbl_logProject=PROJECT.'.log';\n\n");
	fwrite($fh, "# Global URI Definitions\n");
	fwrite($fh, "\$gbl_uriPPV='".$gbl_uriPPV."';\n");
	fwrite($fh, "\$gbl_uriContact='".$gbl_uriContact."';\n");
	fwrite($fh, "\$gbl_uriProject='".$gbl_uriProject."';\n\n");
	fwrite($fh, "# Global Mail Definitions\n");
	fwrite($fh, "\$gbl_nameNoReply='".$gbl_nameNoReply."';\n");
	fwrite($fh, "\$gbl_nameHackers='".$gbl_nameHackers."';\n");
	fwrite($fh, "\$gbl_nameCrackers='".$gbl_nameCrackers."';\n");
	fwrite($fh, "\$gbl_emailNoReply='noreply@'.\$gbl_uriContact;\n");
	fwrite($fh, "\$gbl_emailHackers='hackers@'.\$gbl_uriContact;\n");
	fwrite($fh, "\$gbl_emailCrackers='crackers@'.\$gbl_uriContact;\n\n");
	fwrite($fh, "# Global Failure Definitions\n");
	fwrite($fh, "\$gbl_intFailedAuth=".$gbl_intFailedAuth.";\n");
	fwrite($fh, "\$gbl_intFailedCaptcha=".$gbl_intFailedCaptcha.";\n\n");
	fwrite($fh, "# Global System Variables\n");
	fwrite($fh, "\$gbl_intMaintenance=".$gbl_intMaintenance.";\n");
	fwrite($fh, "\$gbl_strMaintenance=\"".$gbl_strMaintenance."\";\n");
	fwrite($fh, "\$gbl_debug=0;\n");
	fwrite($fh, "\$gbl_info=array();\n");
	fwrite($fh, "\$gbl_fail=array();\n");
	fwrite($fh, "\$gbl_succ=array();\n");
	fwrite($fh, "\$gbl_warn=array();\n");
	fwrite($fh, "\$gbl_user=array();\n");
	fwrite($fh, "\$gbl_null=array();\n");
	fwrite($fh, "\$linkDB;\n");
	fwrite($fh, "?>\n");
	fclose($fh);

	echo "<s><msg>The system configuration has been updated successfully!</msg></s>";
	exit();


} else if ($_POST['action'] == 'check' && $_POST['target'] == 'updates') {	# CHECK/OBTAIN SOFTWARE UPDATES								MIGRATED
	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'])) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'])) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT `read` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to open (read) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['read'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to open (read) data in this module.</msg></f>";
		exit();
	}


	# now we can start processing - this function performs all the actual work
	if (! system_configuration_update()) { exit(); }

	echo "<s>\n";
	echo "   <xml>\n";
	if (file_exists('../temp/update')) {
		$updates = scandir('../temp/update');
		foreach ($updates as $key=>$val) {
			# skip the . and .. and .md5 entries in the directory listing
			if ($val == '.' || $val == '..' || substr($val, -3) == 'md5') { continue; }

			echo "	<update module=\"".$val."\" />\n\n";
		}
	}
	echo "   </xml>\n";
	echo "</s>";
	exit();


} else if ($_POST['action'] == 'install' && $_POST['target'] == 'updates') {	# INSTALL SOFTWARE UPDATES
	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['nUpdatesList_SystemConfiguration'],32,'a-zA-Z0-9_\.\-')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'])) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'])) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT `write` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['write'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
		exit();
	}


	# now perform some work
	$install = array();
	$modules = explode("|", $_POST['nUpdatesList_SystemConfiguration']);
	foreach ($modules as $module) {
		$onMOD = ucwords(str_replace('_', ' ', basename($module, ".tgz")));		# create the original name for the module (e.g. asset_management.tgz > Asset Management)	NOTE: we have to do this because the values being passed into this function are filenames, not the normal names of the modules!
		$ccMOD = str_replace(' ', '', $onMOD);		# remove the space in the module name to store in CamelCase
		$usMOD = str_replace(' ', '_', $onMOD);		# replace the space in the module name with an underscore

		if (system_configuration_install($onMOD)) { array_push($install, $module); } else { exit(); }	# this function performs all the actual work
	}

	echo "<s>\n";
	echo "   <xml>\n";
	foreach ($install as $module) { echo "	<update module=\"".$module."\" />\n"; }		# we need to return the filename (passed from the UI)
# LEFT OFF - add a 'refresh' value so that the user doesn't have to refresh manually
#	also in this version include a message that says "please manually refresh your browser before updating any other modules" - if the 'core' was the module being updated
	echo "   </xml>\n";
	echo "</s>";
	exit();




// FUNCTIONALITY OF 'GROUPS' TAB...

} else if ($_POST['action'] == 'new' && $_POST['target'] == 'group') {		# CREATE A NEW GROUP									MIGRATED

echo "<f><msg>DEPRECATED 2025/03/04</msg></f>";
exit();
	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['name'],24,'a-zA-Z0-9 _\-')) { exit(); }
	if (! validate($_POST['icon'],48,'!=<>;')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT `add` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to add (add) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['add'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to add (add) data in this module.</msg></f>";
		exit();
	}


	# if we've made it here, the user is authorized to create records in the DB
	$gbl_errs['error'] = "Failed to create a new dashboard group in the database.";
	$gbl_info['command'] = "INSERT INTO ".PREFIX."SystemConfiguration_Groups (name,icon) VALUES (?,?)";
	$gbl_info['values'] = '[s] '.$_POST['name'].', [s] '.$_POST['icon'];
	$stmt = $linkDB->prepare($gbl_info['command']);
	$stmt->bind_param('ss', $_POST['name'], $_POST['icon']);
	$stmt->execute();

	echo "<s><msg>The group has been created successfully!</msg><data id='".$stmt->insert_id."'></data></s>";
	exit();


} else if ($_POST['action'] == 'delete' && $_POST['target'] == 'group') {		# DELETE AN EXISTING GROUP (and associated modules)				MIGRATED

echo "<f><msg>DEPRECATED 2025/03/04</msg></f>";
exit();
	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['groupID'],20,'0-9')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT `del` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to remove (delete) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['del'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to remove (delete) data in this module.</msg></f>";
		exit();
	}


	# if we've made it here, the user is authorized to delete records in the DB
	$gbl_errs['error'] = "Failed to delete an existing dashboard group from the database.";
	$gbl_info['command'] = "DELETE FROM ".PREFIX."SystemConfiguration_Groups WHERE id=? LIMIT 1";
	$gbl_info['values'] = '[i] '.$_POST['groupID'];
	$stmt = $linkDB->prepare($gbl_info['command']);
	$stmt->bind_param('i', $_POST['groupID']);
	$stmt->execute();

	$gbl_errs['error'] = "Failed to delete an existing modules within the dashboard group from the database.";
	$gbl_info['command'] = "DELETE FROM ".PREFIX."SystemConfiguration_GroupedModules WHERE groupID=?";
	$gbl_info['values'] = '[i] '.$_POST['groupID'];
	$stmt = $linkDB->prepare($gbl_info['command']);
	$stmt->bind_param('i', $_POST['groupID']);
	$stmt->execute();

	echo "<s><msg>The group has been deleted successfully!</msg></s>";
	exit();


} else if ($_POST['action'] == 'update' && $_POST['target'] == 'group') {		# UPDATE AN EXISTING GROUPS INFO						MIGRATED
echo "<f><msg>DEPRECATED 2025/03/05</msg></f>";
exit();
	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['name'],128,'!=<>;')) { exit(); }
	if (! validate($_POST['icon'],128,'!=<>;')) { exit(); }
	if (! validate($_POST['groupID'],20,'0-9')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT `add` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to add (add) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['add'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to add (add) data in this module.</msg></f>";
		exit();
	}


	# if we've made it here, the user is authorized to update records in the DB
	$gbl_errs['error'] = "Failed to update an existing dashboard group in the database.";
	$gbl_info['command'] = "UPDATE ".PREFIX."SystemConfiguration_Groups SET name=\"".$_POST['name']."\",icon=\"".$_POST['icon']."\" WHERE id='".$_POST['groupID']."'";
	$gbl_info['values'] = '[s] '.$_POST['name'].', [s] '.$_POST['icon'].', [i] '.$_POST['groupID'];
	$stmt = $linkDB->prepare($gbl_info['command']);
	$stmt->bind_param('ssi', $_POST['name'], $_POST['icon'], $_POST['groupID']);
	$stmt->execute();

	echo "<s><msg>The group has been updated successfully!</msg></s>";
	exit();




} else if ($_POST['action'] == 'add' && $_POST['target'] == 'grpmod') {		# ADD A MODULE TO A GROUP								MIGRATED

echo "<f><msg>DEPRECATED 2025/03/04</msg></f>";
exit();
	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['groupID'],20,'0-9')) { exit(); }
	if (! validate($_POST['moduleID'],20,'0-9')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT `add` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to add (add) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['add'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to add (add) data in this module.</msg></f>";
		exit();
	}


	# if we've made it here, the user is authorized to create records in the DB
	$gbl_errs['error'] = "Failed to create a new dashboard group in the database.";
	$gbl_info['command'] = "INSERT INTO ".PREFIX."SystemConfiguration_GroupedModules (groupID,moduleID) VALUES (?,?)";
	$gbl_info['values'] = '[i] '.$_POST['groupID'].', [i] '.$_POST['moduleID'];
	$stmt = $linkDB->prepare($gbl_info['command']);
	$stmt->bind_param('ii', $_POST['groupID'], $_POST['moduleID']);
	$stmt->execute();

	echo "<s><msg>The module has been added to the group successfully!</msg></s>";
	exit();


} else if ($_POST['action'] == 'delete' && $_POST['target'] == 'grpmod') {	# DELETE A MODULE FROM A GROUP								MIGRATED

echo "<f><msg>DEPRECATED 2025/03/04</msg></f>";
exit();
	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['groupID'],20,'0-9')) { exit(); }
	if (! validate($_POST['moduleID'],20,'0-9')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT `del` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to remove (delete) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['del'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to remove (delete) data in this module.</msg></f>";
		exit();
	}


	# if we've made it here, the user is authorized to delete records in the DB
	$gbl_errs['error'] = "Failed to delete an existing dashboard group from the database.";
	$gbl_info['command'] = "DELETE FROM ".PREFIX."SystemConfiguration_GroupedModules WHERE groupID=? AND moduleID=? LIMIT 1";
	$gbl_info['values'] = '[i] '.$_POST['groupID'].', [i] '.$_POST['moduleID'];
	$stmt = $linkDB->prepare($gbl_info['command']);
	$stmt->bind_param('ii', $_POST['groupID'], $_POST['moduleID']);
	$stmt->execute();

	echo "<s><msg>The module has been deleted from the group successfully!</msg></s>";
	exit();




} else if ($_POST['action'] == 'add' && $_POST['target'] == 'module') {		# ADD A MODULE FROM A LOCALLY DOWNLOADED FILE
	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['filename'],64,'!=<>;')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT `add` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to add (add) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['add'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to add (add) data in this module.</msg></f>";
		exit();
	}


	# if we've made it here, the user is authorized to make the requested modifications, so...
	# remove any prior attempts at installation
	if (file_exists('../temp/install'))
		{ delTree('../temp/install/'); }

	# (re)create the installation directory
	if (! file_exists('../temp/install')) {			# create the 'install' directory if it doesn't exist
		$gbl_errs['error'] = "The \"../temp/install\" directory can not be created during module installation.";
		$gbl_info['command'] = "mkdir(\"../temp/install\", 0775, true)";
		$gbl_info['values'] = '';
		mkdir('../temp/install', 0775, true);
	}

	# move the downloaded file into the 'install' directory
	$gbl_errs['error'] = "The \"../temp/".$_POST['filename']."\" file can not be renamed to \"../temp/install/".$_POST['filename']."\" during module installation.";
	$gbl_info['command'] = "rename(\"../temp/".$_POST['filename']."\", \"../temp/install/".$_POST['filename']."\")";
	$gbl_info['values'] = '';
	rename("../temp/".$_POST['filename'], "../temp/install/".$_POST['filename']);

	# decompress the tgz file					http://stackoverflow.com/questions/9416508/php-untar-gz-without-exec
	exec("tar zxf ../temp/install/".$_POST['filename']." -C ../temp/install", $gbl_null);	# uncompress the .soft (.tar.gz) file

	# run the setup
	require('../temp/install/software/modules/webbooks/'.strtolower(str_replace(" ", "_", basename($_POST['filename'], ".tgz"))).'_setup.php');
	if (! call_user_func(strtolower(str_replace(" ", "_", basename($_POST['filename'], ".tgz"))).'_install')) {		// http://stackoverflow.com/questions/1005857/how-to-call-php-function-from-string-stored-in-a-variable
		delTree('../temp/install/');
		echo "<f><msg>An error was encountered attempting to install the module.</msg></f>";
		exit();
	}

	# cleanup
	delTree('../temp/install/');

	echo "<s><msg>The module has been added to webBooks successfully!</msg><data id='".$stmt->insert_id."' name=\"".basename($_POST['filename'], ".tgz")."\" /></s>";
	exit();


} else if ($_POST['action'] == 'delete' && $_POST['target'] == 'module') {	# DELETE A MODULE FROM THE SYSTEM
	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['moduleID'],20,'0-9')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT `del` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to remove (delete) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['del'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to remove (delete) data in this module.</msg></f>";
		exit();
	}


	# first lets check that the user isn't trying to uninstall a core module
	if ($_POST['moduleID'] == 1 || $_POST['moduleID'] == 2 || $_POST['moduleID'] == 3) {
		echo "<f><msg>This module is a core module and can not be uninstalled.</msg></f>";
		exit();
	}

	# now lets obtain the name of the module to-be-deleted
	$gbl_errs['error'] = "Failed to find the selected module in the database when deleting the module.";
	$gbl_info['command'] = "SELECT name FROM ".PREFIX."SystemConfiguration_Modules WHERE id=? LIMIT 1";
	$gbl_info['values'] = '[i] '.$_POST['moduleID'];
	$stmt = $linkDB->prepare($gbl_info['command']);
	$stmt->bind_param('i', $_POST['moduleID']);
	$stmt->execute();
	$Module = $stmt->get_result();
	if ($Module->num_rows > 0) {
		echo "<f><msg>The module could not be found in the database to be uninstalled.</msg></f>";
		exit();
	}
	$module = $Module->fetch_assoc();

	# lastly, run the uninstall routine
	require('../data/_modules/'.str_replace(" ", "_", $module['name']).'/setup.php');
	if (! call_user_func(strtolower(str_replace(" ", "_", $module['name'])).'_uninstall')) {		# http://stackoverflow.com/questions/1005857/how-to-call-php-function-from-string-stored-in-a-variable
		echo "<f><msg>An error was encountered attempting to uninstall the module.</msg></f>";
		exit();
	}

	echo "<s><msg>The module has been deleted from webBooks successfully!</msg></s>";
	exit();


} else if ($_POST['action'] == 'reload' && $_POST['target'] == 'dashboard') {		# RELOAD THE DASHBOARD								MIGRATED

echo "<f><msg>DEPRECATED 2025/03/05</msg></f>";
exit();

	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }

	# connect to the DB for reading below
	if (! connect2DB(DBHOST,DBNAME,DBUNRO,DBPWRO)) { exit(); }		# NOTE: the connect2DB has its own error handling so we don't need to do it here!

	# define general info for any error generated below
	$gbl_info['name'] = 'Unknown';
	$gbl_info['contact'] = 'Unknown';
	$gbl_info['other'] = 'n/a';


	# 1. Gather all the configured dashboard groups for the program
	$gbl_errs['error'] = "Failed to find the dashboard groups in the database.";
	$gbl_info['command'] = "SELECT * FROM ".PREFIX."SystemConfiguration_Groups";
	$gbl_info['values'] = 'None';
	$Groups = $linkDB->query($gbl_info['command']);


	echo "<s>\n";
	echo "   <xml>\n";

	echo "	<groups>\n";
	while ($group = $Groups->fetch_assoc()) {
		echo "	   <group id='".$group['id']."' name=\"".safeXML($group['name'])."\" icon=\"".safeXML($group['icon'])."\">\n";

		# 2. Obtain the modules related to each iterated group
		$gbl_errs['error'] = "Failed to find the modules related to the iterated group in the database.";
		$gbl_info['command'] = "SELECT * FROM ".PREFIX."SystemConfiguration_GroupedModules WHERE groupID='".$group['id']."'";
		$gbl_info['values'] = 'None';							# NOTE: the above "$group['id']" value was pulled from the DB, not passed, so no security threat here!
		$Grouped = $linkDB->query($gbl_info['command']);
		while ($grouped = $Grouped->fetch_assoc()) {
			# 2a. Obtain the iterated modules' information
			$gbl_errs['error'] = "Failed to find the iterated modules' information in the database.";
			$gbl_info['command'] = "SELECT * FROM ".PREFIX."SystemConfiguration_Modules WHERE id='".$grouped['moduleID']."'";
			$gbl_info['values'] = 'None';							# NOTE: the above "$wo['acctID']" value was pulled from the DB, not passed, so no security threat here!
			$Modules = $linkDB->query($gbl_info['command']);
			while ($module = $Modules->fetch_assoc())
				{ echo "		<module id='".$module['id']."' name=\"".safeXML($module['name'])."\" icon=\"".safeXML($module['icon'])."\" />\n"; }
		}

		echo "	   </group>\n";
	}
	echo "	</groups>\n";

	echo "   </xml>\n";
	echo "</s>";
	exit();




} else if (HOSTED && $_POST['action'] == 'save' && $_POST['target'] == 'hosted') {		# SAVES HOSTED SERVICES DETAILS
	# validate all submitted data
	if (! validate($_POST['SID'],40,'a-zA-Z0-9')) { exit(); }
	if (! validate($_POST['username'],128,'a-zA-Z0-9@\._\-')) { exit(); }
	if (! validate($_POST['support'],1,'{0|1}')) { exit(); }
	if (! validate($_POST['logins'],4,'0-9')) { exit(); }
	if (! validate($_POST['uri'],128,'a-zA-Z0-9:/%\._\-')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'],"\$gbl_user['manager']<1","Your account does not have sufficient priviledges to load these settings.")) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."SystemConfiguration_Modules WHERE name='System Configuration' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission to export the database.";
	$gbl_info['command'] = "SELECT `write` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['write'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
		exit();
	}


	# pull the current configuration to determine which settings have been changed
	$gbl_errs['error'] = "Failed to obtain the 'Non-PayPal' records in the database when saving the hosted configuration.";
	$gbl_info['command'] = "SELECT custom1,custom2 FROM ".PREFIX."Funds WHERE type<>'paypal' ORDER BY createdOn DESC LIMIT 1";	// since the 'paypal' records do NOT store the 'logins' and 'support' values, we need to get the last record that is NOT a 'paypal' record for this info
	$gbl_info['values'] = 'None';
	$Config = $linkDB->query($gbl_info['command']);
	$config = mysql_fetch_row($Config,MYSQL_ASSOC);
	$DATA = '';							# create the variable for XML info relay to the users webbrowser

	# if we need to update the logins or support values, then...
	if ($config['custom1'] != $_POST['logins'] || $config['custom2'] != $_POST['support']) {
		# get the correct *LAST* record for each type of desired info below
		$gbl_errs['error'] = "Failed to obtain the 'Balance' in the database when saving the hosted configuration.";
		$gbl_info['command'] = "SELECT balance FROM ".PREFIX."Funds WHERE type='charge' ORDER BY createdOn DESC LIMIT 1";	// store the 'balance' value of the *LAST* 'charge' record on file since that was the payment at the beginning of the current billing cycle
		$gbl_info['values'] = 'None';
		$Charge = $linkDB->query($gbl_info['command']);

		$gbl_errs['error'] = "Failed to obtain the last 'PayPal' funding record in the database when saving the hosted configuration.";
		$gbl_info['command'] = "SELECT amount,createdOn FROM ".PREFIX."Funds WHERE type='paypal' ORDER BY createdOn DESC LIMIT 1";		// store the *LAST* funding record (since it may have come after the last 'charge', but still within this billing cycle) so that we can calculate the correct balance below
		$gbl_info['values'] = 'None';
		$Funding = $linkDB->query($gbl_info['command']);

		$balance = '0.00';						# set the default value
		if ($Charge->num_rows !== 0) {					# process the last 'charge' record (there should be at least one record since this is created during the hosted services account setup)
			$charge = $Charge->fetch_assoc();
			$balance = $charge['balance'];
		}
		if ($Funding->num_rows !== 0) {					# if there is a payment record within this billing cycle, then lets process it!
			$funding = $Funding->fetch_assoc();
			$today = new DateTime(date('Y-m-d H:i:s'));
			$created = new DateTime($funding['createdOn']);
			$days = $today->diff($created)->format("%a");
			if ($days <= 30)					# if the 'paypal' record was made within this billing cycle, then lets add it to the overall balance!
				{ $balance = $balance + $funding['amount']; }	# NOTE: we add the current balance value (from above) to the *AMOUNT* value that was added via the 'paypal' record, not the 'balance' value since that will not reflect the correct balance
		}

		# calculate the monthly costs here as an extra precaution!
		$support = $_POST['support'];
		$logins = $_POST['logins'];
		$charge = 0;

		if ($logins < 2) {						# entrepreneur
			$charge = 0;
			if ($support) { $charge = $charge + (10 * $logins); }
		} else if ($logins >= 2 && $logins <= 10) {			# SOHO
			$charge = + 2 * $logins;
			if ($support) { $charge = $charge + (9 * $logins); }
		} else if ($logins >= 11 && $logins <= 50) {			# small office
			$charge = 1.75 * $logins;
			if ($support) { $charge = $charge + (8 * $logins); }
		} else if ($logins >= 51 && $logins <= 250) {			# medium office
			$charge = 1.5 * $logins;
			if ($support) { $charge = $charge + (7 * $logins); }
		} else if ($logins >= 251 && $logins <= 1000) {			# large office
			$charge = 1.25 * $logins;
			if ($support) { $charge = $charge + (6 * $logins); }
		} else if ($logins >= 1001) {					# enterprise
			$charge = 1 * $logins;
			if ($support) { $charge = $charge + (5 * $logins); }
		}
		if ($balance < $charge) {
			echo "<f><msg>It appears that your account does not currently have the funds to allow that configuration.  Please add funds to your account before continuing.</msg></f>";
			exit();
		}

		# if we've made it here, we can add the SQL record
		$gbl_errs['error'] = "Failed to charge the account based on the specified settings when saving the hosted configuration.";
		$gbl_info['command'] = "INSERT INTO ".PREFIX."Funds (type,amount,balance,custom1,custom2,createdOn) VALUES ('system','0.00','".($balance-$charge)."',?,?,'".$_."')";
		$gbl_info['values'] = '[i] '.$_POST['logins'].', [i] '.$_POST['support'];
		$stmt = $linkDB->prepare($gbl_info['command']);
		$stmt->bind_param('ii', $_POST['logins'], $_POST['support']);
		$stmt->execute();

		$fmtNo = numfmt_create('en_US', NumberFormatter::DECIMAL);
		numfmt_set_attribute($fmtNo, NumberFormatter::MAX_FRACTION_DIGITS, 2);
		# https://www.php.net/manual/en/function.money-format.php
		# https://www.php.net/manual/en/numberformatter.formatcurrency.php
		# https://www.php.net/manual/en/class.locale.php
		# https://www.iban.com/currency-codes
		$DATA = "balance='".numfmt_format($fmtNo, ($balance-$charge))."' ";
	}

	# if we need to adjust the hosted services URI, then...
	if (basename(substr(getcwd(),0,-5)) != $_POST['uri']) {			# STEPS: 1) substr(getcwd(),0,-5) = removes the fixed '/code' ending   2) basename() = isolates the CURRENT directory name used
		$prefix = substr(getcwd(),0,-5).'/data/';			# gets the absolute directory storing the softwares' data
		if (file_exists($prefix.$_POST['uri'])) {
			echo "<f><msg>The URI can not be updated because it is already in use, please try another name.</msg></f>";
			exit();
		}

		# Update the config.php and config.js files
		$gbl_errs['error'] = "The \"../data/config.php\" file can not be renamed to \"../data/config.php.bak\" when saving the hosted configuration.";
		$gbl_info['command'] = "rename(\"../data/config.php\", \"../data/config.php.bak\")";
		$gbl_info['values'] = '';
		rename("../data/config.php", "../data/config.php.bak");		# create a backup of the original

		$gbl_errs['error'] = "The 'config.php' file can not be opened when saving the hosted configuration.";
		$gbl_info['command'] = "fopen('../data/config.php', 'w')";
		$gbl_info['values'] = '';
		$fh = fopen("../data/config.php", 'w');
		fwrite($fh, "<?php\n");
		fwrite($fh, "# config.php	the global definitions used by all projects distributed by Cliquesoft.org\n");
		fwrite($fh, "# Created	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n");
		fwrite($fh, "# Updated	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n\n");
		fwrite($fh, "# Global Constant Definitions\n");
		fwrite($fh, "define('PROJECT','".PROJECT."');\n");
		fwrite($fh, "define('TIMEOUT',".TIMEOUT.");\n");
		fwrite($fh, "define('CAPTCHAS',".CAPTCHAS.");\n");
		fwrite($fh, "define('HOSTED',true);\n\n");
		fwrite($fh, "# Global Directory Definitions\n");
		fwrite($fh, "\$gbl_dirCron='../data/_cron';\n");
		fwrite($fh, "\$gbl_dirData='../data';\n");
		fwrite($fh, "\$gbl_dirLogs='../data/_logs';\n");
		fwrite($fh, "\$gbl_dirMail='".$gbl_dirMail."';\n");
		fwrite($fh, "\$gbl_dirTemp='../temp';\n");
		fwrite($fh, "\$gbl_dirVerify='../data/_verify';\n\n");
		fwrite($fh, "# Global Log Definitions\n");
		fwrite($fh, "\$gbl_logEmail='".$gbl_logEmail."';\n");
		fwrite($fh, "\$gbl_logScript=SCRIPT.'.log';\n");
		fwrite($fh, "\$gbl_logModule=MODULE.'.log';\n");
		fwrite($fh, "\$gbl_logProject=PROJECT.'.log';\n\n");
		fwrite($fh, "# Global URI Definitions\n");
		fwrite($fh, "\$gbl_uriPPV='".$gbl_uriPPV."';\n");
		fwrite($fh, "\$gbl_uriContact='".$gbl_uriContact."';\n");
		fwrite($fh, "\$gbl_uriProject='".$gbl_uriProject."';\n\n");
		fwrite($fh, "# Global Mail Definitions\n");
		fwrite($fh, "\$gbl_nameNoReply='".$gbl_nameNoReply."';\n");
		fwrite($fh, "\$gbl_nameHackers='".$gbl_nameHackers."';\n");
		fwrite($fh, "\$gbl_nameCrackers='".$gbl_nameCrackers."';\n");
		fwrite($fh, "\$gbl_emailNoReply='".$gbl_uriContact."';\n");
		fwrite($fh, "\$gbl_emailHackers='".$gbl_uriContact."';\n");
		fwrite($fh, "\$gbl_emailCrackers='".$gbl_uriContact."';\n\n");
		fwrite($fh, "# Global Failure Definitions\n");
		fwrite($fh, "\$gbl_intFailedAuth=".$gbl_intFailedAuth.";\n");
		fwrite($fh, "\$gbl_intFailedCaptcha=".$gbl_intFailedCaptcha.";\n\n");
		fwrite($fh, "# Global System Variables\n");
		fwrite($fh, "\$gbl_intMaintenance=".$gbl_intMaintenance.";\n");
		fwrite($fh, "\$gbl_strMaintenance=\"".$gbl_strMaintenance."\";\n");
		fwrite($fh, "\$gbl_debug=0;\n");
		fwrite($fh, "\$gbl_info=array();\n");
		fwrite($fh, "\$gbl_fail=array();\n");
		fwrite($fh, "\$gbl_succ=array();\n");
		fwrite($fh, "\$gbl_warn=array();\n");
		fwrite($fh, "\$gbl_user=array();\n");
		fwrite($fh, "\$gbl_null=array();\n");
		fwrite($fh, "\$linkDB;\n");
		fwrite($fh, "?>\n");
		fclose($fh);


		$gbl_errs['error'] = "The \"../data/config.js\" file can not be renamed to \"../data/config.js.bak\" when saving the hosted configuration.";
		$gbl_info['command'] = "rename(\"../data/config.js\", \"../data/config.js.bak\")";
		$gbl_info['values'] = '';
		rename("../data/config.js", "../data/config.js.bak");		# create a backup of the original

		$gbl_errs['error'] = "The 'config.js' file can not be opened when saving the hosted configuration.";
		$gbl_info['command'] = "fopen('../data/config.js', 'w')";
		$gbl_info['values'] = '';
		$fh = fopen("../data/config.js", 'w');
		fwrite($fh, "# config.js	the global definitions used by all projects distributed by Cliquesoft.org\n");
		fwrite($fh, "# Created	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n");
		fwrite($fh, "# Updated	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n\n");
		fwrite($fh, "// Global Declarations\n");
		fwrite($fh, "var gbl_nameUser='guest';\n\n");
		fwrite($fh, "var gbl_uriContact='".$gbl_uriContact."';\n");
		fwrite($fh, "var gbl_uriProject='';\n");
		fwrite($fh, "var gbl_uriPaypal='https://www.paypal.com/cgi-bin/webscr';\n\n");
		fwrite($fh, "var gbl_aBookmark=new Array();\n");
		fwrite($fh, "var gbl_PID=0;\n\n");
		fwrite($fh, "// Module Declarations\n");
		fwrite($fh, "var CAPTCHAS=".CAPTCHAS.";\n");				# WARNING: this can NOT be changed in hosted services website for security reasons!
		fwrite($fh, "var MAPPED=false;\n");
		fwrite($fh, "var HOSTED=true;\n");
		fclose($fh);

		# now rename the directory
		$gbl_errs['error'] = "The \"../../".basename(substr(getcwd(),0,-5))."\" file can not be renamed to \"../../".$_POST['uri']."\" when saving the hosted configuration.";
		$gbl_info['command'] = "rename(\"../../".basename(substr(getcwd(),0,-5))."\", \"../../".$_POST['uri']."\")";
		$gbl_info['values'] = '';
		$gbl_info['continue'] = TRUE;
		if (! rename("../../".basename(substr(getcwd(),0,-5)), "../../".$_POST['uri'])) {
			$gbl_errs['error'] = "The \"../data/config.php\" file can not be renamed to \"../data/config.php.bak\" when saving the hosted configuration.";
			$gbl_info['command'] = "rename(\"../data/config.php\", \"../data/config.php.bak\")";
			$gbl_info['values'] = '';
			$gbl_info['continue'] = TRUE;
			rename("../data/config.php.bak", "../data/config.php");		# restore the original

			$gbl_errs['error'] = "The \"../data/config.js\" file can not be renamed to \"../data/config.js.bak\" when saving the hosted configuration.";
			$gbl_info['command'] = "rename(\"../data/config.js\", \"../data/config.js.bak\")";
			$gbl_info['values'] = '';
			$gbl_info['continue'] = FALSE;
			rename("../data/config.js.bak", "../data/config.js");		# restore the original
		}

		# create the new URI to return to the users webbrowser		WARNING: this MUST come before the directory gets renamed below!
		$DATA .= "uri='".str_replace(basename(substr(getcwd(),0,-5)), $_POST['uri'], $gbl_uriProject)."'";	# STEPS: 1) substr(getcwd(),0,-5) = removes the fixed '/code' ending   2) basename() = isolates the CURRENT directory name used   3) str_replace() = replaces the CURRENT directory name with the NEW one, but using the original directory path so everything else stays in place
	}

	echo "<s><msg>The hosted services settings have been saved successfully!</msg><data ".$DATA." /></s>";
	exit();




} else {					# otherwise, we need the content pane contents, then...
	echo "<f><msg>An invalid request has occurred, our staff has been notified.</msg></f>";
	if (! array_key_exists('username', $gbl_user)) { $gbl_user['username'] = 'guest'; }
	if (! array_key_exists('email', $gbl_user)) { $gbl_user['email'] = 'Not Provided'; }
# UPDATED 2025/03/008
#	sendMail($gbl_emailCrackers,$gbl_nameCrackers,$gbl_emailNoReply,$gbl_nameNoReply,'!!! Possible Cracking Attempt !!!',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nWe might have had a possible cracking attempt made on ".$_.", from ".$_SERVER['REMOTE_ADDR'].", while attempting to pass an invalid API value.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nUsername: ".$__sUser['username']."<br />\nAddress: ".$__sUser['email']."<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: An invalid API value was passed to the script.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br />\n<br />\n[".SCRIPT."; Body]<br />\n<br />\nVar Dump:<br />\n</p>\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre><br />\n</td>\n<td>&nbsp;</td>\n</tr>\n</table>\n</body>\n</html>");
	sendMail($_sSecurityEmail,$_sSecurityName,$_sAlertsEmail,$_sAlertsName,'!!! Possible Cracking Attempt !!!',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nWe might have had a possible cracking attempt made on ".$_.", from ".$_SERVER['REMOTE_ADDR'].", while attempting to pass an invalid API value.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nUsername: ".$__sUser['username']."<br />\nAddress: ".$__sUser['email']."<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: An invalid API value was passed to the script.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br />\n<br />\n[".SCRIPT."; Body]<br />\n<br />\nVar Dump:<br />\n</p>\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre><br />\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");


}
?>
