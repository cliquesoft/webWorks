#!/usr/bin/env php
<?php
# install.php	the CLI script to obtain the configuration details and then
#		call the code/setup.php script
#
# created	2015/03/10 by Dave Henderson (support@cliquesoft.org)
# updated	2025/02/18 by Dave Henderson (support@cliquesoft.org)
#
# Unless a valid Cliquesoft Proprietary License (CPLv1) has been purchased
# for this device, this software is licensed under the Cliquesoft Public
# License (CPLv2) as found on the Cliquesoft website at www.cliquesoft.org
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.
#
# NOTES:
# - readline was not used due to it not being standard in php builds




# Variables typically found in config.php
$__sInfo=array();
$__sMsgs=array();

$update=0;					# whether we need to update the config files
$exit=0;


# ask all the install questions below before handing over control to the setup.php script
echo "\nWelcome\n";
echo "--------------------------------------------------------------------------------\n";
echo "Prior to asking a series of questions to configure this software, lets determine\n";
echo "if that is even necessary. If you are migrating from a prior installation, then\n";
echo "we can skip some steps...\n\n";
echo "   Do you have the config files from the prior installation? [Y/N]: ";
$bExistingConfigs = rtrim(fgets(STDIN), "\n");
if (strtolower($bExistingConfigs) == 'y') { $bExistingConfigs='true'; } else { $bExistingConfigs='false'; }

if ($bExistingConfigs == 'true') {
	echo "   Have you already copied them to the 'data' directory? [Y/N]: ";
	$bInstalledConfigs = rtrim(fgets(STDIN), "\n");
	if (strtolower($bInstalledConfigs) == 'y') { $bInstalledConfigs='true'; } else { $bInstalledConfigs='false'; }

	if ($bInstalledConfigs == 'true') {
		# check for file existence
		if (! file_exists('../sqlaccess') || ! file_exists('data/config.php') || ! file_exists('data/config.webbooks.php')) {
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   At least one configuration file is missing:\n";
			echo "      sqlaccess           : ".print_r(file_exists('../sqlaccess'))."\n";
			echo "      Application.php     : ".print_r(file_exists('data/_cfgs/Application.php'))."\n";	# WARNING: This file -OR- the two below should be present!!!
			echo "      config.php          : ".print_r(file_exists('data/config.php'))."\n";
			echo "      config.webbooks.php : ".print_r(file_exists('data/config.webbooks.php'))."\n";
			echo "   --------------------------------------------------------------------------\n\n";
			exit();
		}

		# acquire their data
		if (file_exists('../data/_cfgs/Application.php')) { require_once('../sqlaccess'); }
		if (file_exists('../data/_cfgs/Application.php')) { require_once('../data/_cfgs/Application.php'); }
		if (file_exists('../data/config.php')) { require_once('../data/config.php'); }
		if (file_exists('../data/config.webbooks.php')) { require_once('../data/config.webbooks.php'); }

		# convert legacy naming conventions to modern
		if (file_exists('../data/config.php')) {
			$sAlertName = $gbl_nameNoReply;
			$sAlertEmail = $gbl_emailNoReply;
			$sSupportName = $gbl_nameHackers;
			$sSupportEmail = $gbl_emailHackers;
			$sSecurityName = $gbl_nameCrackers;
			$sSecurityEmail = $gbl_emailCrackers;
			# Test if the variable was read in with any of the 'require_once' calls
			if (! array_key_exists('sAdminPassword', $GLOBALS)) { $sAdminPassword=''; $update=1; }		# if we're dealing with an outdated config file, indicate we need to obtain this value
			if (! array_key_exists('sInterface', $GLOBALS)) { $sInterface == ''; $update=1; }		# if we're dealing with an outdated config file, indicate we need to obtain this value
			if (! array_key_exists('sOperation', $GLOBALS)) { $sOperation == ''; $update=1; }		# if we're dealing with an outdated config file, indicate we need to obtain this value
			if (CAPTCHAS) { $bUseCaptchas = 'true'; } else { $bUseCaptchas = 'false'; }			# convert the boolean into string (that's expected in Setup.php)
			if (HOSTED) { $bHostedService = 'true'; } else { $bHostedService = 'false'; }
			$sSQLServer = DBHOST;
			$sDatabaseName = DBNAME;
			$sROUsername = DBUNRO;
# VER2 - store the passwords in a file so that info can't be obtained maliciously by polling system calls
			$sROPassword = DBPWRO;
			$sRWUsername = DBUNRW;
			$sRWPassword = DBPWRW;
			$sTablePrefix = PREFIX;
			$sTableName = USERS;
			$sUIDColumn = UID;
			$sUsernameColumn = USERNAME;
			$sPasswordColumn = PASSWORD;
		}
	} else {
		echo "\nBefore we can continue, those files will need to be copied into the 'data'\n";
		echo "directory of this installation. Once completed, please re-run this script.\n\n";
		exit();
	}
}

echo "   Do you have the database from the prior installation? [Y/N]: ";
$bExistingDatabase = rtrim(fgets(STDIN), "\n");
if (strtolower($bExistingDatabase) == 'y') { $bExistingDatabase='true'; } else { $bExistingDatabase='false'; }

if ($bExistingDatabase == 'true') {
	echo "   Is the prior database currently accessible by the SQL Server? [Y/N]: ";
	$bInstalledDBData = rtrim(fgets(STDIN), "\n");
	if (strtolower($bInstalledDBData) == 'y') { $bInstalledDBData='true'; } else { $bInstalledDBData='false'; }

	if ($bInstalledDBData == 'false') {
		echo "\nBefore we can continue, the database will need to be accessible by the SQL\n";
		echo "server used by this installation. Once completed, please re-run this script.\n\n";
		exit();
	}
}


# WARNING: this order of operations MUST remain to trigger correctly
if ($bExistingConfigs == 'false' || $bExistingDatabase == 'false') {				# if we do NOT have any prior config files/database, then we need all of the remaining questions...
	echo "\nBased on the answers provided above, we will need to go through the entire list\n";
	echo "of questions to complete the setup of this software. To recieve more information\n";
	echo "about any prompt, you can enter a question mark (?). Pressing the 'Enter' key\n";
	echo "will use the value in parenthesis.\n";
} else if ($bExistingConfigs == 'true' && $bExistingDatabase == 'true' && ! $update) {		# if we DO have any prior config files/database -AND- no updates are required, then we can skip all of the questions!
	echo "\nBased on the answers provided above, we can safely skip all of the remaining\n";
	echo "questions and begin the installation of this software!\n";
} else if ($bExistingConfigs == 'true' || $bExistingDatabase == 'true') {				# if we DO have a prior database or config files, then we can skip some of the remaining questions...
	echo "\nBased on the answers provided above, we can safely skip some of the remaining\n";
	echo "questions before installation of this software. To recieve more information\n";
	echo "about any prompt, you can enter a question mark (?).\n";
}

if ($bExistingConfigs == 'false') {						# if we do NOT have any prior CONFIG files, then we need these answers
	echo "\nApplication\n";
	echo "--------------------------------------------------------------------------------\n";

	do {
		echo "\n   Please provide a name for the automated email sent by webBooks.\n";
		echo "   [?] (webBooks Alert): ";
		$sAlertName = rtrim(fgets(STDIN), "\n");
		if ($sAlertName == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This field relates to the name and email address that will be used in the\n";
			echo "   'From' field when this software sends automated messages. These two fields\n";
			echo "   should reflect an account that will NOT be able to receive replies and is\n";
			echo "   usually a 'noreply@mydomain.com' address.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if ($sAlertName == '') {					# take the default value
			$sAlertName = 'webBooks Alert';
		} else if (strlen($sAlertName) < 10) {				# answer is too small
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be more than 10 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sAlertName) > 128) {				# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be less than 128 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sAlertName) < 10 || strlen($sAlertName) > 128);	# NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers

	do {
		echo "\n   Please provide an associated email address.\n";
		echo "   [?] (e.g. noreply@mydomain.com): ";
		$sAlertEmail = rtrim(fgets(STDIN), "\n");
		if ($sAlertEmail == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This field relates to the name and email address that will be used in the\n";
			echo "   'From' field when this software sends automated messages. These two fields\n";
			echo "   should reflect an account that will NOT be able to receive replies and is\n";
			echo "   usually a 'noreply@mydomain.com' address.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if ($sAlertEmail == '') {				# take the default value
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This is only an example, not an actual default value.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sAlertEmail) < 10) {				# answer is too small
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be more than 10 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sAlertEmail) > 128) {			# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be less than 128 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sAlertEmail) < 10 || strlen($sAlertEmail) > 128);	# NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers

	do {
		echo "\n   Please provide the name of the person to receive technical emails.\n";
		echo "   [?] (Network Administrator): ";
		$sSupportName = rtrim(fgets(STDIN), "\n");
		if ($sSupportName == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This field, as it suggests, needs to be directed to an individual or group\n";
			echo "	 that can process technical issues related to the operation of this softwar\n";
			echo "   when errors occur. This would typically be your network administrator.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if ($sSupportName == '') {				# take the default value
			$sSupportName = 'Network Administrator';
		} else if (strlen($sSupportName) < 10) {			# answer is too small
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be more than 10 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sSupportName) > 128) {			# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be less than 128 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sSupportName) < 10 || strlen($sSupportName) > 128);	# NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers

	do {
		echo "\n   Please provide an associated email address.\n";
		echo "   [?] (e.g. support@myITcompany.com): ";
		$sSupportEmail = rtrim(fgets(STDIN), "\n");
		if ($sSupportEmail == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This field, as it suggests, needs to be directed to an individual or group\n";
			echo "	 that can process technical issues related to the operation of this softwar\n";
			echo "   when errors occur. This would typically be your network administrator.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if ($sSupportEmail == '') {				# take the default value
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This is only an example, not an actual default value.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sSupportEmail) < 10) {			# answer is too small
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be more than 10 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sSupportEmail) > 128) {			# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be less than 128 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sSupportEmail) < 10 || strlen($sSupportEmail) > 128);	# NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers

	do {
		echo "\n   Please provide the name of the person to receive security emails.\n";
		echo "   [?] (Network Administrator): ";
		$sSecurityName = rtrim(fgets(STDIN), "\n");
		if ($sSecurityName == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This field, concludes the list of contacts and relates to an individual or\n";
			echo "	 group that can understand notifications related to potential malicious\n";
			echo "   activity that has been detected by this software. For most applications,\n";
			echo "   this would also usually be your network administrator.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if ($sSecurityName == '') {				# take the default value
			$sSecurityName = 'Network Administrator';
		} else if (strlen($sSecurityName) < 10) {			# answer is too small
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be more than 10 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sSecurityName) > 128) {			# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be less than 128 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sSecurityName) < 10 || strlen($sSecurityName) > 128);	# NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers

	do {
		echo "\n   Please provide an associated email address.\n";
		echo "   [?] (e.g. security@myITcompany.com): ";
		$sSecurityEmail = rtrim(fgets(STDIN), "\n");
		if ($sSecurityEmail == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This field, concludes the list of contacts and relates to an individual or\n";
			echo "	 group that can understand notifications related to potential malicious\n";
			echo "   activity that has been detected by this software. For most applications,\n";
			echo "   this would also usually be your network administrator.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if ($sSecurityEmail == '') {				# take the default value
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This is only an example, not an actual default value.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sSecurityEmail) < 10) {			# answer is too small
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be more than 10 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sSecurityEmail) > 128) {			# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be less than 128 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sSecurityEmail) < 10 || strlen($sSecurityEmail) > 128);  # NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers
}

if ($sAdminPassword == '') {							# if we're updating an outdated config file, we need to get this value now...
	do {
		echo "\n   Please provide a password for the admin account.\n";
		echo "   [?] : ";
		$sAdminPassword = rtrim(fgets(STDIN), "\n");
		$sAdminPassword = str_replace('"', '\"', $sAdminPassword);	# replace double quotes with an escaped version (since the php 'exec' call is in double quotes)
		$sAdminPassword = str_replace("'", "\\\\'", $sAdminPassword);	# replace single quotes with a double escaped version (so the version written to file remains escaped since it will be in single quotes)
		if ($sAdminPassword == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This software comes with an administrator account to perform initial\n";
			echo "   configuration, as well as any administrative tasks that are not accessible\n";
			echo "   by others. Instead of using a generic default value, which may not get\n";
			echo "   changed and leave a security hole, this field allows you to set the\n";
			echo "   password for this account so you know exactly what to use to login.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if ($sAdminPassword == '') {				# take the default value
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   There is no default value for this prompt.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sAdminPassword) < 12) {			# answer is too small
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be 12 characters or more in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sAdminPassword) > 24) {			# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be 24 characters or less in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sAdminPassword) < 12 || strlen($sAdminPassword) > 24);	# NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers
}

if ($sInterface == '') {							# if we're updating an outdated config file, we need to get this value now...
	do {
		echo "\n   Which interface should be used, (P)rofessional or (E)nterprise?\n";
		echo "   [?/P/E] (P):";
		$sInterface = rtrim(fgets(STDIN), "\n");
		if ($sInterface == '?') {							# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   There are two modes that the user interface can use to make it easier for\n";
			echo "   employees to operate. If you operate a home or small office, you should\n";
			echo "   probably select the \"Professional\" interface as it will minimize the\n";
			echo "   available options to reduce clutter. Alternatively, if this will be used\n";
			echo "   by a medium-sized or enterprise organization where employees spend most,\n";
			echo "   or all, of their working hours interacting with this software, you should\n";
			echo "   probably select \"Enterprise\" since it will keep all available options\n";
			echo "   displayed at all times.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strtolower($sInterface) == 'e') { $sInterface='ent'; }		# specified value
		else { $sInterface='pro'; }							# default value (also any invalid answers)
	} while ($sInterface != 'pro' && $sInterface != 'ent');
}

if ($sOperation == '') {							# if we're updating an outdated config file, we need to get this value now...
	do {
		echo "\n   Use this software as (B)usiness Management or (P)roject Framework?\n";
		echo "   [?/B/P] (B): ";
		$sOperation = rtrim(fgets(STDIN), "\n");
		if ($sOperation == '?') {							# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   By default this software will operate as a company management software\n";
			echo "   (CMS), known as webBooks, so that businesses can keep track of their\n";
			echo "   various data, tasks, and employees. Alternatively, this software can also\n";
			echo "   work without the need to keep track of business activities and act as a\n";
			echo "   project framework, known as webWorks. This functionality can be changed at\n";
			echo "   any time by (un)installing the \"Business Management\" module.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strtolower($sOperation) == 'p') { $sOperation='fw'; }		# specified value
		else { $sOperation='app'; }							# default value (also any invalid answers)
	} while ($sOperation != 'app' && $sOperation != 'fw');
}

if ($bExistingConfigs == 'false') {						# if we do NOT have any prior CONFIG files, then we need these answers
	do {
		echo "\n   Would you like to enable the use of captchas?\n";
		echo "   [?/Y/N] (Y): ";
		$bUseCaptchas = rtrim(fgets(STDIN), "\n");
		if ($bUseCaptchas == '?') {							# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This field is requesting the use of captchas which will provide a basic\n";
			echo "   level of complexity to certain functions in this software. For those who\n";
			echo "   are unfamiliar with captchas, they are the strings of random text, usually\n";
			echo "   6 characters or so, that you must enter before proceeding in some sort of\n";
			echo "   process (e.g. account creation). If you are unsure, select 'Yes'.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strtolower($bUseCaptchas) == 'n') { $bUseCaptchas='false'; }		# specified value
		else { $bUseCaptchas='true'; }							# default value (also any invalid answers)
	} while ($bUseCaptchas != 'true' && $bUseCaptchas != 'false');

	do {
		echo "\n   Allow multiple (hosted) instances of this software to run?\n";
		echo "   [?/Y/N] (N): ";
		$bHostedService = rtrim(fgets(STDIN), "\n");
		if ($bHostedService == '?') {							# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The final field is related to how this software operates. Most small\n";
			echo "   businessess would select 'No' here as they would only have their single\n";
			echo "   business to run. This option is geared towards medium and large-sized\n";
			echo "   businesses that would need multiple instances (one per business) of this\n";
			echo "   software. In addition to that, this option also allows for monetary\n";
			echo "   charging per instance, enabling a company to host access for a fee. If you\n";
			echo "   are unsure, select 'No'.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strtolower($bHostedService) == 'y') { $bHostedService='true'; }	# specified value
		else { $bHostedService='false'; }						# default value (also any invalid answers)
	} while ($bHostedService != 'true' && $bHostedService != 'false');


	echo "\nServer\n";
	echo "--------------------------------------------------------------------------------\n";

	do {
		echo "\n   Please provide the full URI for your SQL server.\n";
		echo "   [?] (e.g. sql.mydomain.com): ";
		$sSQLServer = rtrim(fgets(STDIN), "\n");
		if ($sSQLServer == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This section deals with the database server and the table names within the\n";
			echo "   database itself. The first set of options need to point to the SQL Server\n";
			echo "   (which is usually 'localhost'), the name of the database that is going to\n";
			echo "   store your data (any value will suffice, but should provide some form of\n";
			echo "   identification), and the login credentials of the two accounts with\n";
			echo "   read-only ('R/O') and read-write ('R/W') access to the database. The SQL\n";
			echo "   server, database, and user accounts will need to be accessible before\n";
			echo "   continuing with the setup. This information is usually provided by your\n";
			echo "   network administrator.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if ($sSQLServer == '') {					# take the default value
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This is only an example, not an actual default value.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sSQLServer) < 10) {				# answer is too small
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be more than 10 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sSQLServer) > 128) {				# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be less than 128 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sSQLServer) < 10 || strlen($sSQLServer) > 128);	# NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers


	do {
		echo "\n   Please provide the name of the database storing your data.\n";
		echo "   [?] (webBooks_YYYY-MM-DD): ";
		$sDatabaseName = rtrim(fgets(STDIN), "\n");
		if ($sDatabaseName == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This section deals with the database server and the table names within the\n";
			echo "   database itself. The first set of options need to point to the SQL Server\n";
			echo "   (which is usually 'localhost'), the name of the database that is going to\n";
			echo "   store your data (any value will suffice, but should provide some form of\n";
			echo "   identification), and the login credentials of the two accounts with\n";
			echo "   read-only ('R/O') and read-write ('R/W') access to the database. The SQL\n";
			echo "   server, database, and user accounts will need to be accessible before\n";
			echo "   continuing with the setup. This information is usually provided by your\n";
			echo "   network administrator.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if ($sDatabaseName == '') {				# take the default value
			$sDatabaseName = 'webBooks_'.date('Y-m-d');
		} elseif (strlen($sDatabaseName) < 10) {			# answer is too small
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be more than 10 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sDatabaseName) > 24) {			# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be 24 characters or less in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sDatabaseName) < 10 || strlen($sDatabaseName) > 24);	# NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers

	do {
		echo "\n   Please provide the username with read-only access to the database.\n";
		echo "   [?] : ";
		$sROUsername = rtrim(fgets(STDIN), "\n");
		if ($sROUsername == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This section deals with the database server and the table names within the\n";
			echo "   database itself. The first set of options need to point to the SQL Server\n";
			echo "   (which is usually 'localhost'), the name of the database that is going to\n";
			echo "   store your data (any value will suffice, but should provide some form of\n";
			echo "   identification), and the login credentials of the two accounts with\n";
			echo "   read-only ('R/O') and read-write ('R/W') access to the database. The SQL\n";
			echo "   server, database, and user accounts will need to be accessible before\n";
			echo "   continuing with the setup. This information is usually provided by your\n";
			echo "   network administrator.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if ($sROUsername == '') {				# take the default value
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   There is no default value for this prompt.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sROUsername) < 10) {				# answer is too small
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be more than 10 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sROUsername) > 32) {				# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be 32 characters or less in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sROUsername) < 10 || strlen($sROUsername) > 32);	# NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers

	do {
		echo "\n   Please provide the password to that account.\n";
		echo "   [?] : ";
		$sROPassword = rtrim(fgets(STDIN), "\n");
		$sROPassword = str_replace('"', '\"', $sROPassword);		# replace double quotes with an escaped version (since the php 'exec' call is in double quotes)
		$sROPassword = str_replace("'", "\\\\'", $sROPassword);		# replace single quotes with a double escaped version (so the version written to file remains escaped since it will be in single quotes)
		if ($sROPassword == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This section deals with the database server and the table names within the\n";
			echo "   database itself. The first set of options need to point to the SQL Server\n";
			echo "   (which is usually 'localhost'), the name of the database that is going to\n";
			echo "   store your data (any value will suffice, but should provide some form of\n";
			echo "   identification), and the login credentials of the two accounts with\n";
			echo "   read-only ('R/O') and read-write ('R/W') access to the database. The SQL\n";
			echo "   server, database, and user accounts will need to be accessible before\n";
			echo "   continuing with the setup. This information is usually provided by your\n";
			echo "   network administrator.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if ($sROPassword == '') {				# take the default value
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   There is no default value for this prompt.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sROPassword) < 10) {				# answer is too small
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be 10 characters or more in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sROPassword) > 24) {				# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be 24 characters or less in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sROPassword) < 10 || strlen($sROPassword) > 24);	# NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers

	do {
		echo "\n   Please provide the username with read-write access to the database.\n";
		echo "   [?] : ";
		$sRWUsername = rtrim(fgets(STDIN), "\n");
		if ($sRWUsername == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This section deals with the database server and the table names within the\n";
			echo "   database itself. The first set of options need to point to the SQL Server\n";
			echo "   (which is usually 'localhost'), the name of the database that is going to\n";
			echo "   store your data (any value will suffice, but should provide some form of\n";
			echo "   identification), and the login credentials of the two accounts with\n";
			echo "   read-only ('R/O') and read-write ('R/W') access to the database. The SQL\n";
			echo "   server, database, and user accounts will need to be accessible before\n";
			echo "   continuing with the setup. This information is usually provided by your\n";
			echo "   network administrator.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if ($sRWUsername == '') {				# take the default value
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   There is no default value for this prompt.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sRWUsername) < 10) {				# answer is too small
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be more than 10 characters in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sRWUsername) > 32) {				# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be 32 characters or less in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sRWUsername) < 10 || strlen($sRWUsername) > 32);	# NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers

	do {
		echo "\n   Please provide the password to that account.\n";
		echo "   [?] : ";
		$sRWPassword = rtrim(fgets(STDIN), "\n");
		$sRWPassword = str_replace('"', '\"', $sRWPassword);		# replace double quotes with an escaped version (since the php 'exec' call is in double quotes)
		$sRWPassword = str_replace("'", "\\\\'", $sRWPassword);		# replace single quotes with a double escaped version (so the version written to file remains escaped since it will be in single quotes)
		if ($sRWPassword == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This section deals with the database server and the table names within the\n";
			echo "   database itself. The first set of options need to point to the SQL Server\n";
			echo "   (which is usually 'localhost'), the name of the database that is going to\n";
			echo "   store your data (any value will suffice, but should provide some form of\n";
			echo "   identification), and the login credentials of the two accounts with\n";
			echo "   read-only ('R/O') and read-write ('R/W') access to the database. The SQL\n";
			echo "   server, database, and user accounts will need to be accessible before\n";
			echo "   continuing with the setup. This information is usually provided by your\n";
			echo "   network administrator.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if ($sRWPassword == '') {				# take the default value
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   There is no default value for this prompt.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sRWPassword) < 10) {				# answer is too small
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be 10 characters or more in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sRWPassword) > 24) {				# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must be 24 characters or less in length.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sRWPassword) < 10 || strlen($sRWPassword) > 24);	# NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers

	do {
		echo "\n   Optionally provide a prefix for the table names created in the database.\n";
		echo "   [?] : ";
		$sTablePrefix = rtrim(fgets(STDIN), "\n");
		if ($sTablePrefix == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The last field in this section involves the manipulation of the database\n";
			echo "   table names. Usually this can be left blank, but could be useful in\n";
			echo "   situations where data within an existing database needs to remain\n";
			echo "   untouched. Instances can include sectioning webBooks data every X number\n";
			echo "   of years, data comparison for auditing or forensic inspection, one\n";
			echo "   database for multiple businesses, and more!\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sTablePrefix) > 16) {			# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must not exceed 16 characters.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sTablePrefix) > 16 || $sTablePrefix == '?');


	echo "\nAuthentication\n";
	echo "--------------------------------------------------------------------------------\n";

	do {
		echo "\n   Optionally provide the name of an existing database with employee accounts.\n";
		echo "   [?] : ";
		$sTableName = rtrim(fgets(STDIN), "\n");
		if ($sTableName == '?') {					# user needs help
			echo "   [HELP]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   This section deals with the integration of employee authentication into\n";
			echo "   webBooks, allowing it to work with other software to validate, in\n";
			echo "   real-time, the credentials of your employees instead of using the built-in\n";
			echo "   mechanisms. This means you can authenticate using webdav, LDAP, Microsoft\n";
			echo "   Active Directory, an existing custom-built application, and more! Anything\n";
			echo "   that can be utilized with php, you have the flexibility in which to work.\n";
			echo "   If you are unsure about these fields, leave them blank.\n";
			echo "   --------------------------------------------------------------------------\n";
		} else if (strlen($sTableName) > 32) {				# answer is too large
			echo "   [ERROR]\n";
			echo "   --------------------------------------------------------------------------\n";
			echo "   The response must not exceed 32 characters.\n";
			echo "   --------------------------------------------------------------------------\n";
		}
	} while (strlen($sTableName) > 32 || $sTableName == '?');

	if ($sTableName == '') {						# if the user did NOT specify an 'Accounts' table mapping, then store blank values for these variables...
		$sUIDColumn = '';
		$sUsernameColumn = '';
		$sPasswordColumn = '';
	} else {								# otherwise, we need to get the values for these variables, so lets ask the user!
		do {
			echo "\n   Please provide the column name containing employees' unique ID.\n";
			echo "   [?] : ";
			$sUIDColumn = rtrim(fgets(STDIN), "\n");
			if ($sUIDColumn == '?') {					# user needs help
				echo "   [HELP]\n";
				echo "   --------------------------------------------------------------------------\n";
				echo "   This section deals with the integration of employee authentication into\n";
				echo "   webBooks, allowing it to work with other software to validate, in\n";
				echo "   real-time, the credentials of your employees instead of using the built-in\n";
				echo "   mechanisms. This means you can authenticate using webdav, LDAP, Microsoft\n";
				echo "   Active Directory, an existing custom-built application, and more! Anything\n";
				echo "   that can be utilized with php, you have the flexibility in which to work.\n";
				echo "   If you are unsure about these fields, leave them blank.\n";
				echo "   --------------------------------------------------------------------------\n";
			} else if ($sUIDColumn == '') {					# take the default value
				echo "   [ERROR]\n";
				echo "   --------------------------------------------------------------------------\n";
				echo "   There is no default value for this prompt.\n";
				echo "   --------------------------------------------------------------------------\n";
			} else if (strlen($sUIDColumn) < 5) {				# answer is too small
				echo "   [ERROR]\n";
				echo "   --------------------------------------------------------------------------\n";
				echo "   The response must be more than 5 characters in length.\n";
				echo "   --------------------------------------------------------------------------\n";
			} else if (strlen($sUIDColumn) > 32) {				# answer is too large
				echo "   [ERROR]\n";
				echo "   --------------------------------------------------------------------------\n";
				echo "   The response must not exceed 32 characters.\n";
				echo "   --------------------------------------------------------------------------\n";
			}
		} while (strlen($sUIDColumn) < 5 || strlen($sUIDColumn) > 32);		# NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers

		do {
			echo "\n   Please provide the column name containing employees' usernames.\n";
			echo "   [?] : ";
			$sUsernameColumn = rtrim(fgets(STDIN), "\n");
			if ($sUsernameColumn == '?') {					# user needs help
				echo "   [HELP]\n";
				echo "   --------------------------------------------------------------------------\n";
				echo "   This section deals with the integration of employee authentication into\n";
				echo "   webBooks, allowing it to work with other software to validate, in\n";
				echo "   real-time, the credentials of your employees instead of using the built-in\n";
				echo "   mechanisms. This means you can authenticate using webdav, LDAP, Microsoft\n";
				echo "   Active Directory, an existing custom-built application, and more! Anything\n";
				echo "   that can be utilized with php, you have the flexibility in which to work.\n";
				echo "   If you are unsure about these fields, leave them blank.\n";
				echo "   --------------------------------------------------------------------------\n";
			} else if ($sUsernameColumn == '') {				# take the default value
				echo "   [ERROR]\n";
				echo "   --------------------------------------------------------------------------\n";
				echo "   There is no default value for this prompt.\n";
				echo "   --------------------------------------------------------------------------\n";
			} else if (strlen($sUsernameColumn) < 5) {			# answer is too small
				echo "   [ERROR]\n";
				echo "   --------------------------------------------------------------------------\n";
				echo "   The response must be more than 5 characters in length.\n";
				echo "   --------------------------------------------------------------------------\n";
			} else if (strlen($sUsernameColumn) > 32) {			# answer is too large
				echo "   [ERROR]\n";
				echo "   --------------------------------------------------------------------------\n";
				echo "   The response must not exceed 32 characters.\n";
				echo "   --------------------------------------------------------------------------\n";
			}
		} while (strlen($sUsernameColumn) < 5 || strlen($sUsernameColumn) > 32);  # NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers

		do {
			echo "\n   Please provide the column name containing employees' passwords.\n";
			echo "   [?] : ";
			$sPasswordColumn = rtrim(fgets(STDIN), "\n");
			if ($sPasswordColumn == '?') {					# user needs help
				echo "   [HELP]\n";
				echo "   --------------------------------------------------------------------------\n";
				echo "   This section deals with the integration of employee authentication into\n";
				echo "   webBooks, allowing it to work with other software to validate, in\n";
				echo "   real-time, the credentials of your employees instead of using the built-in\n";
				echo "   mechanisms. This means you can authenticate using webdav, LDAP, Microsoft\n";
				echo "   Active Directory, an existing custom-built application, and more! Anything\n";
				echo "   that can be utilized with php, you have the flexibility in which to work.\n";
				echo "   If you are unsure about these fields, leave them blank.\n";
				echo "   --------------------------------------------------------------------------\n";
			} else if ($sPasswordColumn == '') {				# take the default value
				echo "   [ERROR]\n";
				echo "   --------------------------------------------------------------------------\n";
				echo "   There is no default value for this prompt.\n";
				echo "   --------------------------------------------------------------------------\n";
			} else if (strlen($sPasswordColumn) < 5) {			# answer is too small
				echo "   [ERROR]\n";
				echo "   --------------------------------------------------------------------------\n";
				echo "   The response must be more than 5 characters in length.\n";
				echo "   --------------------------------------------------------------------------\n";
			} else if (strlen($sPasswordColumn) > 32) {			# answer is too large
				echo "   [ERROR]\n";
				echo "   --------------------------------------------------------------------------\n";
				echo "   The response must not exceed 32 characters.\n";
				echo "   --------------------------------------------------------------------------\n";
			}
		} while (strlen($sPasswordColumn) < 5 || strlen($sPasswordColumn) > 32); # NOTE: the minimum count prevents '?' or <ENTER> from being acceptible answers
	}
}

echo "\nConfirmation\n";
echo "-------------------------,------------------------------------------------------\n";
echo "   Automated Email Name  | ".$sAlertName."\n";
echo "   Automated Email Addr  | ".$sAlertEmail."\n";
echo "   Support Contact Name  | ".$sSupportName."\n";
echo "   Support Email Addr    | ".$sSupportEmail."\n";
echo "   Security Contact Name | ".$sSecurityName."\n";
echo "   Security Email Addr   | ".$sSecurityEmail."\n";
echo "   Admin Password        | ****\n";
echo "   Look and Feel         | ";  if ($sInterface == 'pro') { echo "Professional\n"; } else { echo "Enterprise\n"; }
echo "   Mode of Operation     | ";  if ($sOperation == 'app') { echo "Business Management\n"; } else { echo "Project Framework\n"; }
echo "   Use of Captchas       | ".$bUseCaptchas."\n";
echo "   Run Multiple Instances| ".$bHostedService."\n";
echo "   SQL Server URI        | ".$sSQLServer."\n";
echo "   Database Name         | ".$sDatabaseName."\n";
echo "   R/O Database Username | ".$sROUsername."\n";
echo "   R/O Database Password | ****\n";
echo "   R/W Database Username | ".$sRWUsername."\n";
echo "   R/W Database Password | ****\n";
echo "   Database Prefix       | ".$sTablePrefix."\n";
echo "   Auth Database Name    | ".$sTableName."\n";
echo "   Auth DB Unique ID     | ".$sUIDColumn."\n";
echo "   Auth DB Username      | ".$sUsernameColumn."\n";
echo "   Auth DB Password      | ".$sPasswordColumn."\n";
echo "-------------------------'------------------------------------------------------\n";
echo "At this point, no changes have been made to the system.  Do you wish to install\n";
echo "the software using the above configuration? [Y/N]: ";
$install = rtrim(fgets(STDIN), "\n");
if (strtolower($install) != 'y') {
	echo "\n   The installation has been cancelled.\n\n";
	exit();
}




if (! @chdir('code')) {								# change into the 'code' directory so the following code works right
	echo "[ERROR]\n";
	echo "--------------------------------------------------------------------------------\n";
	echo "There was a problem changing into the 'code' directory, exiting.\n";
	echo "--------------------------------------------------------------------------------\n";
	exit();
}

# transpose these values so we don't run into other errors during the running of this script
$__sInfo['admin'] = $sSupportName;
$__sInfo['email'] = $sSupportEmail;

# now start the installation
exec("php -f Setup.php action=install target=software sAlertName=\"$sAlertName\" sAlertEmail=\"$sAlertEmail\" sSupportName=\"$sSupportName\" sSupportEmail=\"$sSupportEmail\" sSecurityName=\"$sSecurityName\" sSecurityEmail=\"$sSecurityEmail\" sAdminPassword='$sAdminPassword' sInterface='$sInterface' sOperation='$sOperation' bUseCaptchas=\"$bUseCaptchas\" bHostedService=\"$bHostedService\" sSQLServer=\"$sSQLServer\" sDatabaseName=\"$sDatabaseName\" sROUsername=\"$sROUsername\" sROPassword='$sROPassword' sRWUsername=\"$sRWUsername\" sRWPassword='$sRWPassword' sTablePrefix=\"$sTablePrefix\" sTableName=\"$sTableName\" sUIDColumn=\"$sUIDColumn\" sUsernameColumn=\"$sUsernameColumn\" sPasswordColumn=\"$sPasswordColumn\" bExistingConfigs=\"$bExistingConfigs\" bExistingDatabase=\"$bExistingDatabase\"", $__sMsgs, $exit);
if ($exit == 0) {								# if there was NOT a problem with the installation, then...
	echo "\nThe software has been installed successfully!\n\n";
} else {									# otherwise, show the error message!
	echo "[ERROR]\n";
	echo "--------------------------------------------------------------------------------\n";
	foreach ($__sMsgs as &$msg) { echo wordwrap($msg,80)."\n"; }
	echo "--------------------------------------------------------------------------------\n";
}


?>
