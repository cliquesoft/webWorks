<?php
# setup.php	the setup routine for the core software when updating via 'System Configuration'			NOTE: this version has syntax that works with php 7.x
#
# Created	2014/11/05 by Dave Henderson (dhenderson@cliquesoft.org or support@cliquesoft.org)
# Updated	2023/10/26 by Dave Henderson (dhenderson@cliquesoft.org or support@cliquesoft.org)
#
# Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
# device, this software is licensed under the Cliquesoft Public License (CPLv2)
# as found on the Cliquesoft website at www.cliquesoft.org.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.


# Gloabl Variable Definitions
#$SCRIPT = 'setup.php';						# replaced with "basename(__FILE__)"
#$MODULE = 'webBooks';						# replaced with a passed value to the appropriate function
#$SOFTWARE = 'webbooks';					# replaced with "strtolower(PROJECT)" from envars




function xCopy2($source, $target, $permissions = 0755) {	# NOTE: even though this function is in _global.php, it is not present in the 2017.02.14.0- version; this was called xCopy2 to prevent issues with updates post 2020.10.20.0
	// Check for symlinks
	if (is_link($source)) { return @symlink(readlink($source), $target); }

	// Simple copy for a file
	if (is_file($source)) { return @copy($source, $target); }

	// Make target directory
	if (!is_dir($target)) { @mkdir($target, $permissions); }

	// Loop through the folder
	$dir = dir($source);
	while (false !== $entry = $dir->read()) {
		// Skip pointers
		if ($entry == '.' || $entry == '..') { continue; }

		// Deep copy directories
		xCopy2("$source/$entry", "$target/$entry", $permissions);
	}

	// Clean up
	$dir->close();
	return true;
}




function webbooks_install_files($MODULE, $CLEANUP=true) {
# the function that performs the actual installation of the modules files
# MODULE	this is the modules name in proper format (e.g. 'Customer Accounts')
	global $gbl_emailHackers,$gbl_nameHackers,$gbl_emailNoReply,$gbl_uriContact,$gbl_uriProject,$gbl_debug,$gbl_errs,$gbl_info,$gbl_user;

	$gbl_errs['error'] = "The 'temp/install/software' directory does not exist.";
	$gbl_info['command'] = "chdir('../temp/install/software')";
	$gbl_info['values'] = 'None';
	chdir('../temp/install/software');

	# copy the module scripts where they need to reside
	$gbl_errs['error'] = "The 'code' directory does not exist.";
	$gbl_info['command'] = "opendir('code')";
	$gbl_info['values'] = 'None';
	$dir = opendir('code');
	$dst = '../../../code';
	if (! file_exists($dst)) {
		$gbl_errs['error'] = "The '".$dst."' directory can not be created.";
		$gbl_info['command'] = "mkdir('".$dst."', 0775, true)";
		$gbl_info['values'] = 'None';
		mkdir($dst, 0775, true);
	}
	while ( ($file = readdir($dir)) !== false ) {
		if (is_dir($file)) { xCopy2('code/'.$file, $dst.'/'.$file, 0755); }
		if ($file == '.' || $file == '..' || $file == basename(__FILE__) || is_dir($file)) { continue; }

		$gbl_errs['error'] = "The 'code/".$file."' file can not be copied to '".$dst."/".$file."'.";
		$gbl_info['command'] = "copy('code/".$file."', '".$dst."/".$file."')";
		$gbl_info['values'] = 'None';
		copy('code/'.$file, $dst.'/'.$file);
	}
	closedir($dir);

	# copy the user interface files into the system
	$gbl_errs['error'] = "The 'look/default' directory does not exist.";
	$gbl_info['command'] = "opendir('look/default')";
	$gbl_info['values'] = 'None';
	$dir = opendir('look/default');
	$dst = '../../../look/default';
	if (! file_exists($dst)) {
		$gbl_errs['error'] = "The '".$dst."' directory can not be created.";
		$gbl_info['command'] = "mkdir('".$dst."', 0775, true)";
		$gbl_info['values'] = 'None';
		mkdir($dst, 0775, true);
	}
	while ( ($file = readdir($dir)) !== false ) {
		if ($file == '.' || $file == '..' || is_dir($file)) { continue; }

		$gbl_errs['error'] = "The 'look/default/".$file."' file can not be copied to '".$dst."/".$file."'.";
		$gbl_info['command'] = "copy('code/".$file."', '".$dst."/".$file."')";
		$gbl_info['values'] = 'None';
		copy('look/default/'.$file, $dst.'/'.$file);
	}
	closedir($dir);

	# copy the images used by this module into the system
	$gbl_errs['error'] = "The 'imgs/default' directory does not exist.";
	$gbl_info['command'] = "opendir('imgs/default')";
	$gbl_info['values'] = 'None';
	$dir = opendir('imgs/default');
	$dst = '../../../imgs/default';
	if (! file_exists($dst)) {
		$gbl_errs['error'] = "The '".$dst."' directory can not be created.";
		$gbl_info['command'] = "mkdir('".$dst."', 0775, true)";
		$gbl_info['values'] = 'None';
		mkdir($dst, 0775, true);
	}
	while ( ($file = readdir($dir)) !== false ) {
		if ($file == '.' || $file == '..' || is_dir($file)) { continue; }

		$gbl_errs['error'] = "The 'imgs/default/".$file."' file can not be copied to '".$dst."/".$file."'.";
		$gbl_info['command'] = "copy('imgs/default/".$file."', '".$dst."/".$file."')";
		$gbl_info['values'] = 'None';
		copy('imgs/default/'.$file, $dst.'/'.$file);
	}
	closedir($dir);

# REMOVED 2020/07/10 - this is no longer used
#	$gbl_errs['error'] = "The 'styles/default' directory does not exist.";
#	$gbl_info['command'] = "opendir('styles/default')";
#	$gbl_info['values'] = 'None';
#	$dir = opendir('styles/default');
#	$dst = '../../../styles/default';
#	if (! file_exists($dst)) {
#		$gbl_errs['error'] = "The '".$dst."' directory can not be created.";
#		$gbl_info['command'] = "mkdir('".$dst."', 0775, true)";
#		$gbl_info['values'] = 'None';
#		mkdir($dst, 0775, true);
#	}
#	while ( ($file = readdir($dir)) !== false ) {
#		if ($file == '.' || $file == '..' || is_dir($file)) { continue; }
#
#		$gbl_errs['error'] = "The 'styles/default/".$file."' file can not be copied to '".$dst."/".$file."'.";
#		$gbl_info['command'] = "copy('styles/default/".$file."', '".$dst."/".$file."')";
#		$gbl_info['values'] = 'None';
#		copy('styles/default/'.$file, $dst.'/'.$file);
#	}
#	closedir($dir);


	# Remove the /install.php and /setup.php files (since they will not be used post-update)
	if (file_exists('../../../install.php')) {
		$gbl_errs['error'] = "The '../../../install.php' file can not be deleted.";
		$gbl_info['command'] = "unlink('../../../install.php')";
		$gbl_info['values'] = 'None';
		unlink('../../../install.php');
	}
	if (file_exists('../../../setup.php')) {
		$gbl_errs['error'] = "The '../../../setup.php' file can not be deleted.";
		$gbl_info['command'] = "unlink('../../../setup.php')";
		$gbl_info['values'] = 'None';
		unlink('../../../setup.php');
	}


	# create the modules' data directories
	if (! file_exists('../../../data/_modules/System_Configuration')) {
		$gbl_errs['error'] = "The '../../../data/_modules/System_Configuration' directory can not be created.";
		$gbl_info['command'] = "mkdir('../../../data/_modules/System_Configuration', 0775, true)";
		$gbl_info['values'] = 'None';
		mkdir('../../../data/_modules/System_Configuration', 0775, true);
	}
	if (! file_exists('../../../data/_modules/Employees')) {
		$gbl_errs['error'] = "The '../../../data/_modules/Employees' directory can not be created.";
		$gbl_info['command'] = "mkdir('../../../data/_modules/Employees', 0775, true)";
		$gbl_info['values'] = 'None';
		mkdir('../../../data/_modules/Employees', 0775, true);
	}
	if (! file_exists('../../../data/_modules/Business_Configuration')) {
		$gbl_errs['error'] = "The '../../../data/_modules/Business_Configuration' directory can not be created.";
		$gbl_info['command'] = "mkdir('../../../data/_modules/Business_Configuration', 0775, true)";
		$gbl_info['values'] = 'None';
		mkdir('../../../data/_modules/Business_Configuration', 0775, true);
	}
	if (! file_exists('../../../data/_modules/webBooks')) {
		$gbl_errs['error'] = "The '../../../data/_modules/webBooks' directory can not be created.";
		$gbl_info['command'] = "mkdir('../../../data/_modules/webBooks', 0775, true)";
		$gbl_info['values'] = 'None';
		mkdir('../../../data/_modules/webBooks', 0775, true);
	}


	# copy the MD5 file to the modules data directory
	$gbl_errs['error'] = "The '../../update/".str_replace(' ', '_', strtolower($MODULE)).".md5' file can not be copied to '../../../data/_modules/".str_replace(' ', '_', $MODULE)."/md5'.";
	$gbl_info['command'] = "copy('../../update/".str_replace(' ', '_', strtolower($MODULE)).".md5', '../../../data/_modules/".str_replace(' ', '_', $MODULE)."/md5')";
	$gbl_info['values'] = 'None';
	copy('../../update/'.str_replace(' ', '_', strtolower($MODULE)).'.md5', '../../../data/_modules/'.str_replace(' ', '_', $MODULE).'/md5');


	# change back into the prior directory (so everything that follows maintains the correct relative paths)
	$gbl_errs['error'] = "The '../../../code' directory does not exist.";
	$gbl_info['command'] = "chdir('../../../code')";
	$gbl_info['values'] = 'None';
	chdir('../../../code');


	# cleanup (optional)
	if (! $CLEANUP) { return true; }			# this is useful if we need to install file while updating, but don't need to cleanup here

	$gbl_errs['error'] = "The '../temp/update/".str_replace(' ', '_', strtolower($MODULE)).".tgz' file can not be deleted.";
	$gbl_info['command'] = "unlink('../temp/update/".str_replace(' ', '_', strtolower($MODULE)).".tgz')";
	$gbl_info['values'] = 'None';
	unlink('../temp/update/'.str_replace(' ', '_', strtolower($MODULE)).'.tgz');

	$gbl_errs['error'] = "The '../temp/update/".str_replace(' ', '_', strtolower($MODULE)).".md5' file can not be deleted.";
	$gbl_info['command'] = "unlink('../temp/update/".str_replace(' ', '_', strtolower($MODULE)).".md5')";
	$gbl_info['values'] = 'None';
	unlink('../temp/update/'.str_replace(' ', '_', strtolower($MODULE)).'.md5');

	delTree('../temp/install');
	return true;
}




function webbooks_install($MODULE) {
# this function performs the installation of the software
# MODULE	this is the modules name in proper format (e.g. 'Customer Accounts')
	# the initial setup is handled in the setup.php; this script is only used for the project 'core' upgrades


	# now install the files associated with the module
	return webbooks_install_files($MODULE);
}




function webbooks_update($MODULE) {
# this function performs the update from prior versions
# MODULE	this is the modules name in proper format (e.g. 'Customer Accounts')
	global $gbl_emailHackers,$gbl_nameHackers,$gbl_emailNoReply,$gbl_uriContact,$gbl_uriProject,$gbl_debug,$gbl_errs,$gbl_info,$gbl_user,$gbl_dirCron,$linkDB;

	# get the current MD5 hash of the installed module
	$gbl_errs['error'] = "Failed to find the MD5 hash for the 'Customer Accounts' module.";
	$gbl_info['command'] = "file_get_contents('../data/_modules/".str_replace(' ', '_', $MODULE)."/md5')";
	$gbl_info['values'] = 'None';
	$MD5 = file_get_contents('../data/_modules/'.str_replace(' ', '_', $MODULE).'/md5');		# obtain the entire MD5 file contents
	$md5 = explode(" ", $MD5);									# isolate just the MD5 hash

	if ($md5[0] == '') {					// if we couldn't detect the MD5 hash of the module, then...
		echo "<f><msg>ERROR: The module's MD5 hash could not be located for validation.</msg></f>";
		return false;
	}

	# apply any specific updates (to files or DB) starting at the current version, up to the most current!
	# NOTE: there are no 'break;' calls below so the user can have no issues updating from their current version to the latest!
	switch ($md5[0]) {
		case '6496873f5bf0c3b45d153f1eced0daab':	// initial release 2014.11.05.0
			echo '';
			# no changes are required
		case 'b8ec2864a6306c22655904e467b98e39':	// anything after version 2016.01.13.0
			$gbl_errs['error'] = "Failed to replace blank 'access' field values with 'everyone' in the 'Notes' table.";
			$gbl_info['command'] = "UPDATE ".PREFIX."Notes SET access='everyone' WHERE access=''";
			$gbl_info['values'] = 'None';
			$stmt = $linkDB->query($gbl_info['command']);
		case '858ae1761fdee1d64260daf31bb6738c':	// anything after version 2016.03.21.0
			if (file_exists('../data/_cron/maintenance.php')) {
				$gbl_errs['error'] = "The 'maintenance.php' file can not be deleted.";
				$gbl_info['command'] = "unlink('../data/_cron/maintenance.php')";
				$gbl_info['values'] = 'None';
				unlink('../data/_cron/maintenance.php');
			}
			if (! file_exists("../data/_cron/maintenance")) {
				$gbl_errs['error'] = "The 'maintenance' crontab file can not be created.";
				$gbl_info['command'] = "fopen('../data/_cron/maintenance', 'w')";
				$gbl_info['values'] = 'None';

				$fh = fopen("../data/_cron/maintenance", 'w');
				fwrite($fh, "# email address of person or group who needs execution reports\n");
				fwrite($fh, "MAILTO='".$gbl_emailHackers."'\n\n");
				fwrite($fh, "# minute (0-59),\n");
				fwrite($fh, "# |	hour (0-23),\n");
				fwrite($fh, "# |	|	day of the month (1-31),\n");
				fwrite($fh, "# |	|	|	month of the year (1-12),\n");
				fwrite($fh, "# |	|	|	|	day of the week (0-6 with 0=Sunday).\n");
				fwrite($fh, "# |	|	|	|	|	commands\n\n");
				fwrite($fh, "0	23	*	*	*	cd ".getcwd()."; ./maintenance.php\n");						// WARNING: the maintenance.php script needs to be run from the 'modules/webbooks' directory, not 'data/_cron'
				fclose($fh);

				exec('echo -e "$(crontab -l 2>/dev/null)\n$(cat "'.$gbl_dirCron.'/maintenance" | sed "/#.*/d")" | crontab -');			// NOTE: this *APPENDS* the webBooks maintenance script to the crontab for the user	http://stackoverflow.com/questions/5134952/how-can-i-set-cron-job-through-php-script
			}
		case '895ea6328267be1f46022121b47e9221':	// anything after version 2016.04.11.0
			$gbl_errs['error'] = "Failed to updating the 'payDay' column type in the 'BusinessConfiguration' table.";
			$gbl_info['command'] = "ALTER TABLE ".PREFIX."BusinessConfiguration MODIFY COLUMN payDay DATE NOT NULL AFTER bbn";
			$gbl_info['values'] = 'None';
			$stmt = $linkDB->query($gbl_info['command']);

			$gbl_errs['error'] = "Failed to obtain the existence of the 'commissionDay' column in the 'BusinessConfiguration' table.";
			$gbl_info['command'] = "SHOW COLUMNS FROM ".PREFIX."BusinessConfiguration LIKE commissionDay";		// http://stackoverflow.com/questions/23513479/check-if-column-exist-in-mysql-table-via-php
			$gbl_info['values'] = 'None';
			$table = $linkDB->query($gbl_info['command']);
			if ($table) {
				$gbl_errs['error'] = "Failed to delete the 'commissionDay' column from the 'BusinessConfiguration' table.";
				$gbl_info['command'] = "ALTER TABLE ".PREFIX."BusinessConfiguration DROP COLUMN commissionDay";
				$gbl_info['values'] = 'None';
				$stmt = $linkDB->query($gbl_info['command']);
			}

			$gbl_errs['error'] = "Failed to obtain the existence of the 'commissionTerm' column in the 'BusinessConfiguration' table.";
			$gbl_info['command'] = "SHOW COLUMNS FROM ".PREFIX."BusinessConfiguration LIKE commissionTerm";
			$gbl_info['values'] = 'None';
			$table = $linkDB->query($gbl_info['command']);
			if ($table) {
				$gbl_errs['error'] = "Failed to delete the 'commissionTerm' column from the 'BusinessConfiguration' table.";
				$gbl_info['command'] = "ALTER TABLE ".PREFIX."BusinessConfiguration DROP COLUMN commissionTerm";
				$gbl_info['values'] = 'None';
				$stmt = $linkDB->query($gbl_info['command']);
			}
		case 'e8f27f504ee6a24936c1e4378298a556':	// anything after version 2016.04.12.0
			echo '';
			# no changes are required
		case '5ed2bbc64af643225b68a6ac3be82851':	// anything after version 2016.05.19.0
			echo '';
			# no changes are required
		case '22c3a65ca06c32952dfa8fb11301b898':	// anything after version 2017.02.11.0
			echo '';
			# no changes are required
		case '0cea4cc69b61e58558f2580dc54922c0':	// anything after version 2017.02.14.0
			# 1. Create the backup storage directory
			if (! file_exists('../temp/prior_installs')) {
				$gbl_errs['error'] = "The '../temp/prior_installs' directory can not be created.";
				$gbl_info['command'] = "mkdir('../temp/prior_installs', 0775, true)";
				$gbl_info['values'] = 'None';
				mkdir('../temp/prior_installs', 0775, true);
			}

			# 2. Backup the prior installation and database
# LEFT OFF - update this later to use something other than an exec() call		https://stackoverflow.com/questions/7004989/creating-zip-or-tar-gz-archive-without-exec   http://stackoverflow.com/questions/9416508/php-untar-gz-without-exec
#			$tarball = new PharData('../temp/2017.02.14.0.tar');
#
#			# add all files from prior version
#			$tarball->buildFromDirectory('../data');
#			$tarball->buildFromDirectory('../libraries');
#			$tarball->buildFromDirectory('../modules');
#			$tarball->buildFromDirectory('../themes');
#
#			# compress the file (using a .gz extension)
#			$tarball->compress(Phar::GZ);
			exec("tar zcf ../temp/prior_installs/2017.02.14.0.tgz -C ../ data libraries modules themes", $gbl_null);

			$cmd = "mysqldump --opt --user=".DBUSER." --password=".DBPASS." --host=".DBHOST." ".DBNAME." > ../temp/exported.sql";
			$log = "../temp/exported.log";
			exec(sprintf("%s 2>%s &", $cmd, $log));
			exec("cd ../temp && tar zcf exported-2017.02.14.0.tgz exported.* && rm -f exported.*", $gbl_null);	# compress the data

			# 3. Create the denaccess, sqlaccess, and config files
			# NOTE: this MUST to be done with an external file using php v7+ since we can't do it with php 5.6 (which is what this script will be running under)
			if (! file_exists('../../denaccess'))
				{ exec("../temp/denaccess_gen.php ../../denaccess", $gbl_null); }

			if (! file_exists("../../sqlaccess")) {
				$gbl_errs['error'] = "The 'sqlaccess' config file can not be created.";
				$gbl_info['command'] = "fopen('../../sqlaccess', 'w')";
				$gbl_info['values'] = 'None';

				$fh = fopen('../../sqlaccess', 'w');
				fwrite($fh, "<?php\n");
				fwrite($fh, "# sqlaccess	the credentials to make a SQL server connection\n");
				fwrite($fh, "# Created	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n");
				fwrite($fh, "# Updated	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n\n");
				fwrite($fh, "# Global Directory Definitions\n");
				fwrite($fh, "define('DBHOST','".DBHOST."');\n");
				fwrite($fh, "define('DBNAME','".DBNAME."');\n");
				fwrite($fh, "define('DBUNRO','".DBUSER."');\n");
				fwrite($fh, "define('DBUNRW','".DBUSER."');\n");
				fwrite($fh, "define('DBPWRO','".str_replace ('$', '\$', DBPASS)."');\n");
				fwrite($fh, "define('DBPWRW','".str_replace ('$', '\$', DBPASS)."');\n");
# LEFT OFF - if any password contains the '$' symbol, it chops from that character onward.  Can we escape it?		updated 7/10 - the attempt above does not work...
# https://stackoverflow.com/questions/31640682/php-escape-dollar-sign/31640795
				fwrite($fh, "?>\n");
				fclose($fh);
			}

			if (! file_exists("../data/config.php")) {
				$gbl_errs['error'] = "The 'config.php' config file can not be created.";
				$gbl_info['command'] = "fopen('../data/config.php', 'w')";
				$gbl_info['values'] = 'None';

				$fh = fopen('../data/config.php', 'w');
				fwrite($fh, "<?php\n");
				fwrite($fh, "# config.php	the global definitions used by all projects distributed by Cliquesoft.org\n");
				fwrite($fh, "# Created	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n");
				fwrite($fh, "# Updated	".date('Y-m-d')." by ".SCRIPT." (support@cliquesoft.org)\n\n");
				fwrite($fh, "# Global Constant Definitions\n");
				fwrite($fh, "define('PROJECT','".PROJECT."');\n");
				fwrite($fh, "define('TIMEOUT',".TIMEOUT.");\n");
				fwrite($fh, "define('CAPTCHAS',".CAPTCHAS.");\n");
				fwrite($fh, "define('HOSTED',".HOSTED.");\n\n");
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
				fwrite($fh, "\$gbl_nameHackers=\"".$gbl_nameHackers."\";\n");
				fwrite($fh, "\$gbl_nameCrackers=\"".$gbl_nameCrackers."\";\n");
				fwrite($fh, "\$gbl_emailNoReply='".$gbl_emailNoReply."';\n");
				fwrite($fh, "\$gbl_emailHackers='".$gbl_emailHackers."';\n");
				fwrite($fh, "\$gbl_emailCrackers='".$gbl_emailCrackers."';\n\n");
				fwrite($fh, "# Global Failure Definitions\n");
				fwrite($fh, "\$gbl_intFailedAuth=5;\n");
				fwrite($fh, "\$gbl_intFailedCaptcha=5;\n\n");
				fwrite($fh, "# Global System Variables\n");
				fwrite($fh, "\$gbl_intMaintenance=0;\n");
				fwrite($fh, "\$gbl_strMaintenance='2:30pm EST - down for 30 min';\n");
				fwrite($fh, "\$gbl_debug=0;\n");
				fwrite($fh, "\$gbl_errs=array();\n");
				fwrite($fh, "\$gbl_info=array();\n");
				fwrite($fh, "\$gbl_fail=array();\n");
				fwrite($fh, "\$gbl_succ=array();\n");
				fwrite($fh, "\$gbl_warn=array();\n");
				fwrite($fh, "\$gbl_user=array();\n");
				fwrite($fh, "\$gbl_null=array();\n");
				fwrite($fh, "\$linkDB;\n");
				fwrite($fh, "?>\n");
				fclose($fh);
			}

			// if the file does NOT already exist (e.g. this project isn't getting added to an existing project), then create the config.php, config.PROJECT.php, and config.js files
			if (! file_exists("../data/config.js")) {
				$gbl_errs['error'] = "The 'config.js' config file can not be created.";
				$gbl_info['command'] = "fopen('../data/config.js', 'w')";
				$gbl_info['values'] = 'None';

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
				fwrite($fh, "var CAPTCHAS=".CAPTCHAS.";\n");
				fwrite($fh, "var MAPPED=".MAPPED.";\n");
				fwrite($fh, "var HOSTED=".HOSTED.";\n");
				fclose($fh);
			}

			if (! file_exists("../data/config.".strtolower(MODULE).".php")) {
				$gbl_errs['error'] = "The 'config.".strtolower(MODULE).".php' config file can not be created.";
				$gbl_info['command'] = "fopen('../data/config.".strtolower(MODULE).".php', 'w')";
				$gbl_info['values'] = 'None';

				$fh = fopen('../data/config.'.strtolower(MODULE).'.php', 'w');
				fwrite($fh, "<?php\n");
				fwrite($fh, "# config.".strtolower(MODULE).".php	this file contains the system config for the project\n\n");
				fwrite($fh, "define('PREFIX','".PREFIX."');\n");
				fwrite($fh, "define('USERS','".USERS."');\n\n");
				fwrite($fh, "define('FIRST','".FIRST."');\n");
				fwrite($fh, "define('LAST','".LAST."');\n");
				fwrite($fh, "define('USERNAME','".USERNAME."');\n");
				fwrite($fh, "define('EMAIL','');\n");
				fwrite($fh, "define('UID','".UID."');\n");
# NOTE: the below line may also have to be blank since the SID constant is already used in php
				fwrite($fh, "define('SES','');\n");
				fwrite($fh, "?>\n");
				fclose($fh);
			}

			# 4. Store the decryption variable values
			$salt = $_GET['decrypt'];			# decrypt the decryptGBL of the submitter to decrypt the SSN
			$cipher = new Cipher($salt);
			$GBL = $cipher->decrypt($gbl_user['decryptGBL']);

			$salt = $GBL;					# decrypt the Commerce SID
			$cipher = new Cipher($salt);

			# 5. Decrypt the prior encrypted database values
# LEFT OFF - update all the SQL calls with the old syntax
			# [SystemConfiguration_Commerce > sid]
			$gbl_errs['error'] = "Failed to find all the commerce records in the database.";
			$gbl_info['command'] = "SELECT id,sid FROM ".PREFIX."SystemConfiguration_Commerce ORDER BY id";
			$gbl_info['values'] = 'None';
			$Commerce = $linkDB->query($gbl_info['command']);
			while ($commerce = $Commerce->fetch_assoc()) {
				if (strlen($commerce['sid']) > 1) { $sid = $cipher->decrypt($commerce['sid']); } else { continue; }

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
			while ($merchant = $Merchant->fetch_assoc()) {
				if (strlen($merchant['merchantID']) > 1) { $id = $cipher->decrypt($merchant['merchantID']); } else { continue; }

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
			while ($card = $Card->fetch_assoc()) {
				if (strlen($card['number']) > 1) { $num = $cipher->decrypt($card['number']); } else { $num = ''; }
				if (strlen($card['cvv2']) > 1) { $cvv = $cipher->decrypt($card['cvv2']); } else { $cvv = ''; }

				$gbl_errs['error'] = "Failed to update the credit card values (id: ".$card['id'].") in the database.";
				$gbl_info['command'] = "UPDATE ".PREFIX."BusinessConfiguration_CreditCards SET number=\"".$num."\",cvv2=\"".$cvv."\" WHERE id='".$card['id']."'";
				$gbl_info['values'] = 'None';
				$stmt = $linkDB->query($gbl_info['command']);
			}
			# [Employees > password,pes,homePhone,homeMobile,homeEmail,homeAddr1,driversLicense,ssn]
			$gbl_errs['error'] = "Failed to find all the employee records in the database.";
			$gbl_info['command'] = "SELECT id,password,pes,homePhone,homeMobile,homeEmail,homeAddr1,driversLicense,ssn FROM ".PREFIX."Employees ORDER BY id";
			$gbl_info['values'] = 'None';
			$Employee = $linkDB->query($gbl_info['command']);
			while ($employee = $Employee->fetch_assoc()) {
				if (strlen($employee['password']) > 1) { $pass = $cipher->decrypt($employee['password']); } else { $pass = ''; }
				if (strlen($employee['homePhone']) > 1) { $phone = $cipher->decrypt($employee['homePhone']); } else { $phone = ''; }
				if (strlen($employee['homeMobile']) > 1) { $mobile = $cipher->decrypt($employee['homeMobile']); } else { $mobile = ''; }
				if (strlen($employee['homeEmail']) > 1) { $email = $cipher->decrypt($employee['homeEmail']); } else { $email = ''; }
				if (strlen($employee['homeAddr1']) > 1) { $addr = $cipher->decrypt($employee['homeAddr1']); } else { $addr = ''; }
				if (strlen($employee['driversLicense']) > 1) { $license = $cipher->decrypt($employee['driversLicense']); } else { $license = ''; }
				if (strlen($employee['ssn']) > 1 != '') { $ssn = $cipher->decrypt($employee['ssn']); } else { $ssn = ''; }

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
			while ($bank = $Bank->fetch_assoc()) {
				if (strlen($bank['account']) > 1) { $acct = $cipher->decrypt($bank['account']); } else { continue; }

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
			while ($freight = $Freight->fetch_assoc()) {
				if (strlen($freight['account']) > 1) { $acct = $cipher->decrypt($freight['account']); } else { continue; }

				$gbl_errs['error'] = "Failed to update the freight account value (id: ".$freight['id'].") in the database.";
				$gbl_info['command'] = "UPDATE ".PREFIX."FreightAccounts SET account=\"".$acct."\" WHERE id='".$freight['id']."'";
				$gbl_info['values'] = 'None';
				$stmt = $linkDB->query($gbl_info['command']);
			}

			# NOTE: we need to decrypt each modules data here as well instead of letting them do it individually since the old encryption ability will be gone after the core update

			# [CustomerAccounts > password,commerceSID,mainAddr1,billAddr1]
			$gbl_errs['error'] = "Failed to obtain the existence of the 'id' column in the 'CustomerAccounts' table.";	// first check that the modules have been installed
			$gbl_info['command'] = "SHOW COLUMNS FROM ".PREFIX."CustomerAccounts LIKE id";
			$gbl_info['values'] = 'None';
			$table = $linkDB->query($gbl_info['command']);
			if ($table) {
# VER2 - move the commerceSID values into the SystemConfiguration_Commerce table
				$gbl_errs['error'] = "Failed to find all the customer account records in the database.";
				$gbl_info['command'] = "SELECT id,password,commerceSID,mainAddr1,billAddr1 FROM ".PREFIX."CustomerAccounts ORDER BY id";
				$gbl_info['values'] = 'None';
				$Customer = $linkDB->query($gbl_info['command']);
				while ($customer = $Customer->fetch_assoc()) {
					if (strlen($customer['password']) > 1) { $pass = $cipher->decrypt($customer['password']); } else { $pass = ''; }
					if (strlen($customer['commerceSID']) > 1) { $sid = $cipher->decrypt($customer['commerceSID']); } else { $sid = ''; }
					if (strlen($customer['mainAddr1']) > 1) { $main = $cipher->decrypt($customer['mainAddr1']); } else { $main = ''; }
					if (strlen($customer['billAddr1']) > 1) { $bill = $cipher->decrypt($customer['billAddr1']); } else { $bill = ''; }

					$gbl_errs['error'] = "Failed to update the freight account value (id: ".$customer['id'].") in the database.";
					$gbl_info['command'] = "UPDATE ".PREFIX."CustomerAccounts SET password=\"".$pass."\",commerceSID=\"".$sid."\",mainAddr1=\"".$main."\",billAddr1=\"".$bill."\" WHERE id='".$customer['id']."'";
					$gbl_info['values'] = 'None';
					$stmt = $linkDB->query($gbl_info['command']);
				}
			}
			# [QuotesAndInvoices > altAddr1]
			$gbl_errs['error'] = "Failed to obtain the existence of the 'id' column in the 'QuotesAndInvoices' table.";
			$gbl_info['command'] = "SHOW COLUMNS FROM ".PREFIX."QuotesAndInvoices LIKE id";
			$gbl_info['values'] = 'None';
			$table = $linkDB->query($gbl_info['command']);
			if ($table) {
				$gbl_errs['error'] = "Failed to find all the freight account records in the database.";
				$gbl_info['command'] = "SELECT id,altAddr1 FROM ".PREFIX."QuotesAndInvoices ORDER BY id";
				$gbl_info['values'] = 'None';
				$Invoice = $linkDB->query($gbl_info['command']);
# VER2 - update the layout in a later version
				while ($invoice = $Invoice->fetch_assoc()) {
					if (strlen($invoice['account']) > 1) { $addr = $cipher->decrypt($invoice['altAddr1']); } else { continue; }

					$gbl_errs['error'] = "Failed to update the freight account value (id: ".$invoice['id'].") in the database.";
					$gbl_info['command'] = "UPDATE ".PREFIX."QuotesAndInvoices SET altAddr1=\"".$addr."\" WHERE id='".$invoice['id']."'";
					$gbl_info['values'] = 'None';
					$stmt = $linkDB->query($gbl_info['command']);
				}
			}

			# 6. Re-encrypt the above columns with the new encryption
			# NOTE: this MUST to be done with an external file using php v7+ since we can't do it with php 5.6 (which is what this script will be running under)
			exec("../temp/re-encrypt.php ../../denaccess", $gbl_null);

			# 7. Add/remove any database columns
			# [BusinessConfiguration > commerceURI,commerceSID]
			$gbl_errs['error'] = "Failed to delete the 'commerceURI, commerceSID' columns from the 'BusinessConfiguration' table.";
			$gbl_info['command'] = "ALTER TABLE ".PREFIX."BusinessConfiguration DROP COLUMN commerceURI, DROP COLUMN commerceSID";
			$gbl_info['values'] = 'None';
			$stmt = $linkDB->query($gbl_info['command']);

			# [Employees > question1,question2,question3,answer1,answer2,answer3]
# VER2 - remove the "DEFAULT ''" from each of these columns
			$gbl_errs['error'] = "Failed to add the 'question1,question2,question3,answer1,answer2,answer3' columns to the 'Employees' table.";
			$gbl_info['command'] = "ALTER TABLE ".PREFIX."Employees ADD (question1 VARCHAR(128) NOT NULL DEFAULT '', question2 VARCHAR(128) NOT NULL DEFAULT '', question3 VARCHAR(128) NOT NULL DEFAULT '', answer1 VARCHAR(128) NOT NULL DEFAULT '', answer2 VARCHAR(128) NOT NULL DEFAULT '', answer3 VARCHAR(128) NOT NULL DEFAULT '') AFTER dependents";
			$gbl_info['values'] = 'None';
			$stmt = $linkDB->query($gbl_info['command']);
			# [Employees > decryptGBL]
			$gbl_errs['error'] = "Failed to delete the 'decryptGBL' columns from the 'Employees' table.";
			$gbl_info['command'] = "ALTER TABLE ".PREFIX."Employees DROP COLUMN decryptGBL";
			$gbl_info['values'] = 'None';
			$stmt = $linkDB->query($gbl_info['command']);
			# [Employees > decryptIND]
			$gbl_errs['error'] = "Failed to obtain the existence of the 'decryptIND' column in the 'Employees' table.";
			$gbl_info['command'] = "SHOW COLUMNS FROM ".PREFIX."Employees LIKE decryptIND";
			$gbl_info['values'] = 'None';
			$table = $linkDB->query($gbl_info['command']);
			if ($table) {
				$gbl_errs['error'] = "Failed to delete the 'decryptIND' columns from the 'Employees' table.";
				$gbl_info['command'] = "ALTER TABLE ".PREFIX."Employees DROP COLUMN decryptIND";
				$gbl_info['values'] = 'None';
				$stmt = $linkDB->query($gbl_info['command']);
			}
			# [Employees > decryptIND]
			$gbl_errs['error'] = "Failed to obtain the existence of the 'pes' column in the 'Employees' table.";
			$gbl_info['command'] = "SHOW COLUMNS FROM ".PREFIX."Employees LIKE pes";
			$gbl_info['values'] = 'None';
			$table = $linkDB->query($gbl_info['command']);
			if ($table) {
				$gbl_errs['error'] = "Failed to delete the 'pes' columns from the 'Employees' table.";
				$gbl_info['command'] = "ALTER TABLE ".PREFIX."Employees DROP COLUMN pes";
				$gbl_info['values'] = 'None';
				$stmt = $linkDB->query($gbl_info['command']);
			}

			# 8. Replace all the '&amp;', '&dblfs;', etc 
			# [BusinessConfiguration_Additional > website,mainAddr1,mainAddr2]
			$gbl_errs['error'] = "Failed to find all the bank account records in the database.";
			$gbl_info['command'] = "SELECT id,website,mainAddr1,mainAddr2 FROM ".PREFIX."BusinessConfiguration_Additional ORDER BY id";
			$gbl_info['values'] = 'None';
			$BizCfg = $linkDB->query($gbl_info['command']);
			while ($bizcfg = $BizCfg->fetch_assoc()) {
				$gbl_errs['error'] = "Failed to update the bank account value (id: ".$bizcfg['id'].") in the database.";
				$gbl_info['command'] = "UPDATE ".PREFIX."BusinessConfiguration_Additional SET website=\"".safeSQL($bizcfg['website'],'out')."\",mainAddr1=\"".safeSQL($bizcfg['mainAddr1'],'out')."\",mainAddr2=\"".safeSQL($bizcfg['mainAddr2'],'out')."\" WHERE id='".$bizcfg['id']."'";
				$gbl_info['values'] = 'None';
				$stmt = $linkDB->query($gbl_info['command']);
			}
			# [SystemConfiguration_Commerce > uri]
			$gbl_errs['error'] = "Failed to find all the bank account records in the database.";
			$gbl_info['command'] = "SELECT id,uri FROM ".PREFIX."SystemConfiguration_Commerce ORDER BY id";
			$gbl_info['values'] = 'None';
			$Commerce = $linkDB->query($gbl_info['command']);
			while ($commerce = $Commerce->fetch_assoc()) {
				$gbl_errs['error'] = "Failed to update the bank account value (id: ".$commerce['id'].") in the database.";
				$gbl_info['command'] = "UPDATE ".PREFIX."SystemConfiguration_Commerce SET uri=\"".safeSQL($commerce['uri'],'out')."\" WHERE id='".$commerce['id']."'";
				$gbl_info['values'] = 'None';
				$stmt = $linkDB->query($gbl_info['command']);
			}
			# [SystemConfiguration_Groups > name]
			$gbl_errs['error'] = "Failed to find all the bank account records in the database.";
			$gbl_info['command'] = "SELECT id,name FROM ".PREFIX."SystemConfiguration_Groups ORDER BY id";
			$gbl_info['values'] = 'None';
			$SysGrp = $linkDB->query($gbl_info['command']);
			while ($sysgrp = $SysGrp->fetch_assoc()) {
				$gbl_errs['error'] = "Failed to update the bank account value (id: ".$sysgrp['id'].") in the database.";
				$gbl_info['command'] = "UPDATE ".PREFIX."SystemConfiguration_Groups SET name=\"".safeSQL($sysgrp['name'],'out')."\" WHERE id='".$sysgrp['id']."'";
				$gbl_info['values'] = 'None';
				$stmt = $linkDB->query($gbl_info['command']);
			}
			# [SystemConfiguration_Modules > name]
			$gbl_errs['error'] = "Failed to find all the bank account records in the database.";
			$gbl_info['command'] = "SELECT id,name FROM ".PREFIX."SystemConfiguration_Modules ORDER BY id";
			$gbl_info['values'] = 'None';
			$SysMod = $linkDB->query($gbl_info['command']);
			while ($sysmod = $SysMod->fetch_assoc()) {
				$gbl_errs['error'] = "Failed to update the bank account value (id: ".$sysmod['id'].") in the database.";
				$gbl_info['command'] = "UPDATE ".PREFIX."SystemConfiguration_Modules SET name=\"".safeSQL($sysmod['name'],'out')."\" WHERE id='".$sysmod['id']."'";
				$gbl_info['values'] = 'None';
				$stmt = $linkDB->query($gbl_info['command']);
			}

		case 'ba53654119627f6072598e97b729a2ab':	// anything after version 2020.10.20.0
			# 1. Update the 'username' field in the 'Employees' module to 128 characters so that email addresses can be used
			$gbl_errs['error'] = "Failed to update the 'username' size to 128 characters to allow for email addresses as a value.";
			$gbl_info['command'] = "ALTER TABLE ".PREFIX."Employees CHANGE username username VARCHAR(128) NOT NULL";
			$gbl_info['values'] = 'None';
			$stmt = $linkDB->query($gbl_info['command']);

# LEFT OFF - update the following to data/config.php
#		case '':
#		fwrite($fh, "\$gbl_dbug=array();\n");
# REMOVED THE FOLLOWING VARIABLES FROM config.php		this actually needs to be completely updated with new syntax
#$gbl_fail=array();		# unused
#$gbl_succ=array();		# unused
#$gbl_warn=array();		# unused

# changed table names:
#	SystemConfiguration > deleted
#	Associated > Application_Associated
#	BankAccounts > BusinessConfiguration_BankAccounts
#	SystemConfiguration_Commerce > Application_Commerce
#	Contacts > Application_Contacts
#	FreightAccounts > Application_FreightAccounts
#	Notes > Application_Notes
#	Specs > Application_Specs
#	Uploads > Application_Data
#	SystemConfiguration_Modules > ApplicationSettings_Modules
#	SystemConfiguration_Groups > ApplicationSettings_Groups
#	SystemConfiguration_GroupedModules > ApplicationSettings-Grouped
#	Employees > Employees_General
#	Employees_Access > Employees_General_Access
#	Employees_Donation > Employees_General_Donation

# update Application_Notes.type to be numeric value corresponding to Application_Modules.id
# do the same for Application_Data.table
# do the same for Application_Specs.table
# rename the column titles to remove leading zero's for the 1-9 entries (e.g. title01 > title1; value01 > value1)


#		case '':
#			NOTICE that there isn't a break here, and that's because this will allow for accumulative updates starting
#			at the point that triggers the appropriate 'case' and performs all the updates to get to the most current
#			version.  For instance, this 'case' may update a DB column name and the following 'cases' may rename it
#			again, or remove it alltogether.  This will rely on the SystemConfiguration_Module.db column value.
			break;
	}


	# now install the files associated with the module
	return webbooks_install_files($MODULE);
}




function webbooks_uninstall($MODULE) {
# this function performs the uninstallation of the software
# MODULE	this is the modules name in proper format (e.g. 'Customer Accounts')
	# there is no current uninstallation for this project
# LEFT OFF - fill this out
	return true;
}

?>
