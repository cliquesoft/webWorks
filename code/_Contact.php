<?php
# _Contact.php
#
# Created	2005/11/30 by Mike Stubbs
# Expanded	2012/11/05 by Dave Henderson (support@cliquesoft.org)
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
# NOTES:
# - https://code.google.com/p/cool-php-captcha/
#
# - Errors encountered attempting to send email to @yahoo.com accounts
#	https://help.yahoo.com/kb/SLN24016.html
#	https://github.com/PHPMailer/PHPMailer/releases
#	https://stackoverflow.com/questions/27404183/i-can-not-receive-from-php-mail-function
#	https://www.php.net/manual/en/function.mail.php




if (! defined("MODULE")) {					# if this script IS being called directly, then...
	# Constant Definitions
	define("MODULE",'_Contact');				#   the name of this module (NOTE: this can be the same as the PROJECT constant in the envars.php file)
	define("SCRIPT",basename($_SERVER['SCRIPT_NAME']));	#   the name of this script (for tracing bugs and automated messages)

	# Module Requirements					  NOTE: these must be declared in the calling script
	require_once('../../sqlaccess');
	require_once('../data/_modules/ApplicationSettings/config.php');
	require_once('_Project.php');
	require_once('_Database.php');
	require_once('_Security.php');

	# Start or resume the PHP session			  NOTE: gains access to $_SESSION variables in this script
	session_start();





	# format the dates in UTC
	$_ = gmdate("Y-m-d H:i:s",time());			# used this mannor so all the times will be the exact same (also see http://php.net/manual/en/function.gmdate.php)

	header('Content-Type: text/xml; charset=utf-8');
	echo "<?xml version='1.0' encoding='UTF-8'?>\n";
}




# Class Declarations

class mimeMail {
	var $parts;
	var $to;
	var $from;
	var $cc;
	var $bcc;
	var $headers;
	var $subject;
	var $body;

	# constructor; initialize members to something sane
	function __construct() {
		$this->parts   = array();
		$this->to      = "";
		$this->from    = "";
		$this->cc      = "";
		$this->bcc     = "";
		$this->subject = "";
		$this->body    = "";
		$this->headers = "";
	}

	function add_attachment($message, $name = "", $ctype = "application/octet-stream") {
# VER2 - get this to work
		$this->parts[] = array (
			"ctype"   => $ctype,
			"message" => $message,
			"encode"  => $encode,
			"name"    => $name
		);
	}

	function build_message($part) {
		$message  = $part["message"];
		$message  = chunk_split(base64_encode($message));
		$encoding = "base64";
		return( "Content-Type: ".$part["ctype"].($part["name"] ? "; name = \"".$part["name"]."\"" : "")."\nContent-Transfer-Encoding: $encoding\n\n$message\n" );
	}

	function build_multipart() {
		$boundary = "b".md5(uniqid(time()));
		$multipart = "Content-Type: multipart/mixed; boundary = $boundary\n\nThis is a MIME encoded message.\n\n--$boundary";

		for( $i = sizeof($this->parts)-1; $i >= 0; $i-- )
			{ $multipart .= "\n".$this->build_message($this->parts[$i]). "--$boundary"; }

		return( $multipart.=  "--\n" );
	}

	# build headers and post message
	function send() {					# http://stackoverflow.com/questions/30887610/error-with-php-mail-multiple-or-malformed-newlines-found-in-additional-header	http://stackoverflow.com/questions/2265579/php-e-mail-encoding
		global $_sAlertsEmail,$_sSupportEmail;

		$head = "";					# for the headers
		$mime = "";					# for the mime encoded email
		$boundary = "b".md5(uniqid(time()));		# create the boundary ID

		$head .= "X-Mailer: PHP v".phpversion()."\r\n";
		$head .= "From: ".$this->from."\r\n";
# LEFT OFF - update the below line to be dynamic
		if ($this->from == 'announcement@domain.com')
			{ $head .= "Reply-To: ".$_sAlertsEmail."\r\n"; }
		$head .= "Reply-To: ".$this->from."\r\n";
		$head .= "Return-Path: ".$_sSupportEmail."\r\n";
		if (! empty($this->cc))      { $head .= "Cc: ".$this->cc."\r\n"; }
		if (! empty($this->bcc))     { $head .= "Bcc: ".$this->bcc."\r\n"; }
		if (! empty($this->headers)) { $head .= $this->headers."\r\n"; }
		$head .= "MIME-Version: 1.0\r\n";
		$head .= "Content-type: text/html; charset=utf-8\r\n";
		$head .= "Content-Transfer-Encoding: 8bit\r\n\r\n";

		$mime .= $this->body."\r\n";
		return( mail($this->to, $this->subject, $mime, $head) );
	}
}




# Function Declarations

function sendMail($sToEmail,$sToName,$sFromEmail,$sFromName,$sSubject,$sMessage,$sCC='',$sHeaders='') {
# A wrapper script that sets the appropriate variables then sends the MIME encoded email
# sToEmail	the 'To' email address of the contact
# sToName	the name of the person/group that is receiving the email
# sFromEmail	the 'From' email address of the sender
# sFromName	the name of the person/group that is sending the email
# sSubject	the subject of the email
# sMessage	the message/body of the email
# sCC		the comma separated list of email addresses to carbon copy
# sHeaders	optional headers to add to the email
	global $_sDirLogs,$_sLogEmail,$_sSupportEmail;


	$mail = new mimeMail();
	if ($sFromName != '') { $mail->from = '"'.$sFromName.'" <'.$sFromEmail.'>'; } else { $mail->from = $sFromEmail; }
	$mail->cc = "";								# $_POST["cc"];
	$mail->headers = "Errors-To: ".$_sSupportEmail.($sHeaders=='' ? '' : "\n".$sHeaders);			# WARNING: this line can NOT have a trailing '\n' as it will cause problems with the mimeMail class!
	if ($sFromName != '') { $mail->to = '"'.$sToName.'" <'.$sToEmail.'>'; } else { $mail->to = $sToEmail; }
	$mail->subject = $sSubject;
	$mail->body = $sMessage;

	if ($mail->send()) {
		if (file_exists($_sDirLogs)) {					# write the success/failure status to file if the directory exists
			if ($EML = fopen($_sDirLogs.'/'.$_sLogEmail,'a'))
				{ fwrite($EML,"Email successfully sent to user '".$sToName."' (".$sToEmail.") on ".date("m/d/Y H:i:s",time()).".\n"); }
		}
		return 1;
	} else {
		if (file_exists($_sDirLogs)) {
			if ($EML = fopen($_sDirLogs.'/'.$_sLogEmail,'a'))
				{ fwrite($EML,"Couldn't send the email to user '".$sToName."' (".$sToEmail.") on ".date("m/d/Y H:i:s",time()).".\n"); }
		}
		return 0;
	}
}




function verifyEmail($sSearchField,$sSearchValue,$sReplaceField,$sReplaceValue,$sName,$sEmail,$sTable,$sErrors='x') {
# Creates a dynamically named .php file that will be used to perform validation of an accounts' email address
# NOTE: this also CHANGES a database column/field value called 'status' equal to 'active' upon success, no
#	change otherwise.
# sSearchField	the database field (column) name to search for the users account in
# sSearchValue	the UNIQUE value that will identify the users account (e.g. primary key, username, email, etc)
# sReplaceField	the database field (column) name to adjust upon success (e.g. disabled)
# sReplaceValue	the value to change the 'sReplaceField' to
# sName		the real name, username, alias, etc that the user is known by; this is part of the email 'To' field
# sEmail	the email address associated with the users account
# sTable	the table containing the user accounts
# sErrors	defines how errors should be handled; valid values: (a)rray, (h)tml, (t)ext, (x)ml, blank value disables output
	global $_sDirVrfy,$_sSupportName,$_sSupportEmail,$_sAlertsName,$_sAlertsEmail,$_sUriProject,$__sMsgs,$_LinkDB;

	while (1) {								# make sure not to overwrite an existing verification script
		$nRandom = rand(1000,1000000000);
		if (! file_exists($_sDirVrfy.'/'.$nRandom.'.php')) { break; }
	}

	if (! $sScript = fopen($_sDirVrfy.'/'.$nRandom.'.php','w')) {
		sendMail($_sSupportEmail,$_sSupportName,$_sAlertsEmail,$_sAlertsName,'*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\nFunction: verifyEmail<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\n<br />\nOur Error: The random-named php script could not be created on the server.<br />\nExec Error: ".print_r(error_get_last(), true)."<br />\n<br />\nVar Dump:<br />\nDirectory > ".$_sDirVrfy.", Script > ".$nRandom.".php<br />\n</p>\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre></td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		# NOTE: if the sendmail fails above, our staff will never know about this error!

		if ($sErrors == 'a') { $__sMsgs[] = "There was an error creating the verification script and our staff has been notified of the error.  Please try again in a couple of minutes."; return 0; }
		else if ($sErrors == 'h') { echo "<div class='divFail'>There was an error creating the verification script and our staff has been notified of the error.  Please try again in a couple of minutes.</div>\n"; return 0; }
		else if ($sErrors == 't') { echo "There was an error creating the verification script and our staff has been notified of the error.  Please try again in a couple of minutes.\n"; return 0; }
		else if ($sErrors == 'x') { echo "<f><msg>There was an error creating the verification script and our staff has been notified of the error.  Please try again in a couple of minutes.</msg></f>"; return 0; }
	} else {

		$sHTML = '<?php
# Constant Definitions
define("MODULE","'.MODULE.'");
define("NAME","Verify");			# the actual name of this script
define("SCRIPT","'.$nRandom.'.php");

# Module Requirements
require_once("../../../sqlaccess");
require_once("../../data/_modules/ApplicationSettings/config.php");
require_once("../../code/_Project.php");
require_once("../../code/_Contact.php");
require_once("../../_Database.php");

# Start or resume the PHP session
session_start();




$__sInfo[\'continue\'] = TRUE;
if (! connect2DB(DB_HOST,DB_NAME,DB_RWUN,DB_RWPW)) {
	$color="#f00";
	$msg="Uh-oh!  Something went wrong so a message has been sent to the system administrator.  We appreciate your patience and apologize for the inconvenience.  You will be redirected back to the site in 15 seconds.\n";
} else {
	$__sInfo[\'error\'] = "The user account can not be enabled in the database.";
	$__sInfo[\'command\'] = "UPDATE '.DB_PRFX.$sTable.' SET '.$sReplaceField.'=\''.$sReplaceValue.'\',status=\'active\' WHERE '.$sSearchField.'=\''.$sSearchValue.'\'";
	if (! $_LinkDB->query($__sInfo[\'command\'])) {
		$color="#f00";
		$msg="Uh-oh!  Something went wrong so a message has been sent to the system administrator.  We appreciate your patience and apologize for the inconvenience.  You will be redirected back to the site in 15 seconds.\n";
		sendMail($_sSupportEmail,$_sSupportName,$_sAlertsEmail,$_sAlertsName,"*** Script Execution Error ***","<html>\n<body topmargin=\'0\' leftmargin=\'0\' marginwidth=\'0\' marginheight=\'0\' offset=\'0\' bgcolor=\'#ffffff\'>\n<table width=\'100%\'>\n<tr>\n<td>&nbsp;</td>\n<td width=\'500\'>\n<img src=\''.$_sUriProject.'/home/guest/imgs/email_error.png\' border=\'0\' style=\'float:right; padding-left: 5px;\' />\n<h1 style=\'padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;\'>'.PROJECT.'</h1>\n<h2 style=\'margin-bottom: 5px; font: 12pt verdana bold; color: #808080;\'>Script Execution Error</h2><br />\n<p style=\'font: 12px/17px verdana; color: #808080; text-align: justify;\'>\nTeam,<br />\n<br />\nOne of the users was interacting with our \''.PROJECT.'\' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: '.PROJECT.'<br />\nModule: '.MODULE.'<br />\nScript: ".SCRIPT."<br />\nFunction: verifyEmail<br />\n<br />\nDB Host: '.DB_HOST.'<br />\nDB Name: '.DB_NAME.'<br />\nDB Prefix: '.DB_PRFX.'<br />\n<br />\nOur Error: An error occurred while connecting to the DB server or database itself.<br />\nSQL Error: ".mysqli_errno($_LinkDB).": ".mysqli_error($_LinkDB)."<br />\n<br />\nVar Dump:<br />\nSearch Key > '.$sSearchField.', Search Val > '.$sSearchValue.', Replace Key > '.$sReplaceField.', Replace Val > '.$sReplaceValue.', Target Name > '.$sName.', Target Email > '.$sEmail.'<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
	} else {
		$color="#a9d260";
		$msg="\tCongrats!<br />\n\tYou have finished the registration process.  If this page does not redirect you in 15 seconds <a href=\"'.$_sUriProject.'\">click here</a>.\n";
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Registration Complete</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="refresh" content="15; '.$_sUriProject.'" />
<link rel="icon" href="favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<link rel="stylesheet" href="home/guest/look/_global.css" type="text/css" />
<style type="text/css" media="screen">
	body {
		margin: 10px auto;
		width: 400px;
		font: 12px/13pt verdana;
		color: <?php print $color; ?>;
		background-color: #fff;
		cursor: default;
		text-align: justify;
	}
	h2 { margin-bottom: 20px; font-size: 14pt; color: #76a7dc; text-align: center; }
</style>
</head>
<body>
<div id="divBody">
	<center><h2>'.PROJECT.'</h2></center>
	<div class="center">
<?php print $msg; ?>
	</div>
</div>
</body>
<html>
<?php unlink("'.$nRandom.'.php"); ?>';

		fwrite($sScript,$sHTML);
		fclose($sScript);
		if (! sendMail($sEmail,$sName,$_sAlertsEmail,PROJECT,'Account Verification',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/email_verify.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Verify Identity</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$sName.",<br />\n<br />\nThanks for taking the time to create an account with our website.  We know this process can be annoying, but it\nhelps us to ensure that we have a good means of contact for you.  To help streamline this process, we have\nincluded a link below to click on that will handle the rest of the verification steps.  Clicking that link will\nresult in making your account active immediately and providing you with full acccess so you can start interacting\nonline! Also, please note, your password will <u>never</u> be asked for by any staff member and should <u>not</u>\nbe given to anyone.<br />\n<br />\n<a href='".$_sUriProject.$_sDirVrfy."/".$nRandom.".php'>Click here to verify</a><br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>")) {
			sendMail($_sSupportEmail,$_sSupportName,$_sAlertsEmail,$_sAlertsName,'*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\nFunction: verifyEmail<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: The verification script could not be sent to the receipient.<br />\n<br />\nVar Dump:<br />\nDirectory > ".$_sDirVrfy.", Script > ".$nRandom.".php<br />\n</p>\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre></td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
			# NOTE: if the sendmail fails above, our staff will never know about this error!

			if ($sErrors == 'a') { $__sMsgs[] = "There was an error sending the verification script and our staff has been notified of the error.  Please try again in a couple of minutes."; return 0; }
			else if ($sErrors == 'h') { echo "<div class='divFail'>There was an error sending the verification script and our staff has been notified of the error.  Please try again in a couple of minutes.</div>\n"; return 0; }
			else if ($sErrors == 't') { echo "There was an error sending the verification script and our staff has been notified of the error.  Please try again in a couple of minutes.\n"; return 0; }
			else if ($sErrors == 'x') { echo "<f><msg>There was an error sending the verification script and our staff has been notified of the error.  Please try again in a couple of minutes.</msg></f>"; return 0; }
		}
	}
	return 1;
}





# EXIT IF APPROPRIATE
#	the below lines are triggered when this script is sourced [e.g. require_once('../../script');] from within another so that
#	the above functions are accessible within it, but the below code isn't executed - which is specific to webpage submissions
#
$included_files = get_included_files();
if ($included_files[0] != __FILE__) { return; }
#
# -- no wrapper script should use code below this point --










# process ajax submissions
switch ($_POST['A']) {						# Process the submitted (A)ction

    case 'send':						# Sending some form of communication
	if ($_POST['T'] == 'share') {				# Sends our official communication to internal staff and third parties
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@._-')) { exit(); }
		if ($_bUseCaptchas) { if (! validate($_POST['sCaptcha'],16,'a-zA-Z\- ')) {exit();} }
		#												  contact us (our staff)		contact others (share)
		if (! validate($_POST['sContact'],64,'a-zA-Z0-9 ,.-')) { exit(); }				# sender contact name			referred contact name
		if (! validate($_POST['sEmail'],128,'a-zA-Z0-9@._-')) { exit(); }				# sender email				referred contact email
		if (! validate($_POST['sName'],64,'a-zA-Z0-9 ,._-')) { exit(); }				# target department name		product/project/website=name, referral/support=contact
		if (! validate($_POST['sType'],32,'a-zA-Z0-9._-')) { exit(); }					# target department email prefix	the type of share: product, project, referral, support, website
		if (! validate($_POST['sID'],128,'a-zA-Z0-9 ._@%#:\/-')) { exit(); }				# email subject				product=model,project=UPC,referral/support=email,website=url
		if (! validate($_POST['sTag'],24,'!=<>;')) { exit(); }
		if (! validate($_POST['sMessage'],1024,'')) { exit(); }						# NOTE: since this is just an email that gets sent, there is no reason to perform these checks; besides the &lt;/&gt; conversion will still retain a semicolon!

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

		# load the users account info in the global variable
		if (! loadUser($_nTimeout,$__sUser,'rw','id,name',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }

		# connect to the DB for writing below
		if (! connect2DB(DB_HOST,DB_NAME,DB_RWUN,DB_RWPW)) { exit(); }

		# define some default values
		$sFromName = $__sUser['name'];
		$sToName = 'Sir/Madam';
		if ($_POST['sContact'] != '') { $sToName = $_POST['sContact']; }

		if ($_POST['sType'] == 'referral') {
			# check if the referral is already a member
			$__sInfo['error'] = "The (existing) referred user account can not be found in the database.";
			$__sInfo['command'] = "SELECT id FROM ".PREFIX."Contacts WHERE workEmail=? LIMIT 1";
			$__sInfo['values'] = '[s] '.$_POST['sEmail'];
			$Exists = $_LinkDB->prepare($__sInfo['command']);
			$Exists->bind_param('s', $_POST['sEmail']);
			$Exists->execute();
			$Exists = $Exists->get_result();

			if ($Exists->num_rows > 0) {		# if the email is already associated with an existing account, then...
				echo "<f><msg>Thanks for the referral, but it appears that person is already a member.</msg></f>";
				exit();
			}

# REMOVED 2025/08/21 - to prevent spamming, the user MUST be logged into the system in order to send referrals
#			# if the user is logged in, then obtain some of their information
#			if ($_POST['sUsername'] != 'guest' && $_POST['sUsername'] != '') {
#				$__sInfo['error'] = "The user account can not be found in the database.";
#				$__sInfo['command'] = "
#					SELECT name FROM ".DB_PRFX."CustomerAccounts WHERE username=?";
#				$__sInfo['values'] = '[s] '.$_POST['sUsername'];
#				$Accounts = $_LinkDB->prepare($__sInfo['command']);
#				$Accounts->bind_param('s', $_POST['sUsername']);
#				$Accounts->execute();
#				$account = $Accounts->get_result()->fetch_assoc();
#				$sFromName = $account['name'];
#			}
		}

		switch ($_POST['sType']) {
			case 'product':
				if ($_POST['sType'] == 'product')
					{ $sMessage = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>You have been referred!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$sToName.",<br />\nYou have been referred to look at the \"".$_POST['sName']."\" product in our online market by \"".$sFromName."\" who thinks you would be interested in it! Please feel free <a href='".$_POST['sID']."' target='_new'>to visit</a> and read about this and other ".$_POST['sType']."s. If you have any questions, comments, or concerns, please contact our staff at your earliest convenience.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br /><br /><br /><br />Optional Message:<br />\n".$_POST['sMessage']."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>"; }
			case 'project':
				if ($_POST['sType'] == 'project')
					{ $sMessage = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>You have been referred!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$sToName.",<br />\nYou have been referred to look at our \"".$_POST['sName']."\" project in our online market by \"".$sFromName."\" who thinks you would be interested in it! Please feel free <a href='".$_POST['sID']."' target='_new'>to visit</a> and explore this and other ".$_POST['sType']."s. It is also important to note that our software is free of charge with optional technical support, alternative licensing, and more! If you have any questions, comments, or concerns, please contact our staff at your earliest convenience.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br /><br /><br /><br />Optional Message:<br />\n".$_POST['sMessage']."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>"; }
			case 'referral':
				if ($_POST['sType'] == 'referral')
					{ $sMessage = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>You have been referred!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$sToName.",<br />\nYou have been referred by \"".$sFromName."\" who thinks you might find interest in what we have to offer. So feel free to <a href='".$_sUriProject."' target='_new'>visit us</a> and find out what all the fuss is about! And by creating an account after using the preceding link, you will have access to fully interact with other users! If you have any questions, comments, or concerns, please contact our staff at your earliest convenience.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br /><br /><br /><br />Optional Message:<br />\n".$_POST['sMessage']."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>"; }
			case 'support':
				if ($_POST['sType'] == 'support')
					{ $sMessage = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>You have been referred!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$sToName.",<br />\nYou have been notified by \"".$sFromName."\" who needs support with <a href='".$_sUriProject."' target='_new'>".PROJECT."</a>. Some additional information will be included below to help isolate and resolve the issue. If this communication has been wrongfully directed to you, please forward the request to the appropriate entity so that help can be provided.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br /><br /><br /><br />Optional Message:<br />\n".$_POST['sMessage']."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>"; }
			case 'website':
				if ($_POST['sType'] == 'support')
					{ $sMessage = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>You have been referred!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$sToName.",<br />\nYou have been referred to our website by \"".$sFromName."\" who thinks you would be interested in it. So feel free to visit <a href='".$_POST['sID']."' target='_new'>".$_POST['sName']."</a> to find out what all the fuss is about! And by creating an account after using the preceding link, you will have access to fully interact with other users! If you have any questions, comments, or concerns, please contact our staff at your earliest convenience.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br /><br /><br /><br />Optional Message:<br />\n".$_POST['sMessage']."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>"; }

				$sFromEmail = $_sAlertsEmail;
				if ($_POST['sType'] == 'support') { $sToEmail = $_sSupportEmail; }		# support should go to your stored contact
				else { $sToEmail = $_POST['sEmail']; }						# otherwise, direct to the submitted value
				$sSubject = "You have been referred!";

				if (sendMail($sToEmail,$sToName,$sFromEmail,$sFromName,$sSubject,$sMessage))
					{ echo "<s><msg>Your mail has been sent successfully!</msg></s>"; }
				else
					{ echo "<f><msg>An error was encountered while attempting to send the mail, our staff has been alerted.</msg></f>"; }
				break;

			default:
				$sFromEmail = $_sAlertsEmail;
				$sCC = $_POST["sEmail"];							# send a copy of the correspondence to the senders email address
				$sToEmail = $_POST['sType'].substr($_sSupportEmail, strpos($_sSupportEmail,'@'));
				$sSubject = $_POST["sID"];
				$sMessage = $_POST["sMessage"];

				if (sendMail($sToEmail,$sToName,$sFromEmail,$sFromName,$sSubject,$sMessage,$sCC))
					{ echo "<s><msg>Your mail has been sent successfully to our staff!</msg></s>"; }
				else
					{ echo "<f><msg>An error was encountered while attempting to send the mail, our staff has been alerted.</msg></f>"; }
		}
		exit();




	} else if ($_POST['T'] == 'email') {				# Sends any type of email
		if (! validate($_POST['sSessionID'],40,'a-zA-Z0-9')) { exit(); }
		if (! validate($_POST['sUsername'],128,'a-zA-Z0-9@._-]')) { exit(); }
		if ($_bUseCaptchas && array_key_exists('sCaptcha', $_POST)) { if (! validate($_POST['sCaptcha'],16,'a-zA-Z -')) {exit();} }
		if (! validate($_POST['sToName'],64,'a-zA-Z0-9 ,._-')) { exit(); }
		if (! validate($_POST['sToEmail'],128,'a-zA-Z0-9@._-')) { exit(); }
		if (! validate($_POST['sFromName'],64,'a-zA-Z0-9 ,._-')) { exit(); }
		if (! validate($_POST['sFromEmail'],128,'a-zA-Z0-9@._-')) { exit(); }
		if (! validate($_POST['sSubject'],128,'')) { exit(); }
		if (! validate($_POST['sMessage'],1024,'')) { exit(); }						# NOTE: since this is just an email that gets sent, there is no reason to perform these checks; besides the &lt;/&gt; conversion will still retain a semicolon!

		if (sendMail($_POST['sToEmail'],$_POST['sToName'],$_POST['sFromEmail'],$_POST['sFromName'],$_POST['sSubject'],$_POST['sMessage']))
			{ echo "<s><msg>Your mail has been sent successfully!</msg></s>"; }
		else
			{ echo "<f><msg>An error was encountered while attempting to send the mail, our staff has been alerted.</msg></f>"; }
	}
	break;




    default:							# otherwise, we need to indicate that an invalid request was made
	echo "<f><msg>An invalid request has occurred, our staff has been notified.</msg></f>";
	sendMail($_sSecurityEmail,$_sSecurityName,$_sAlertsEmail,$_sAlertsName,'*** Possible Cracking Attempt ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/webbooks.email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\n<br />\nOur Error: A user is attempting to pass an invalid 'action' or 'target' values.<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
}










//  --- DEPRECATED/LEGACY ---


if ($_POST['action'] == 'send' && $_POST['target'] == 'share') {		// Send the referal!
	// make appropriate substitutions
	$_POST['txtContactMsg'] = str_replace('<','&lt;',$_POST['txtContactMsg']);
	$_POST['txtContactMsg'] = str_replace('>','&gt;',$_POST['txtContactMsg']);

	# validate all submitted data
	if (! validate($_POST['username'],32,'[^a-zA-Z0-9_\-]')) { exit(); }
	if (! validate($_POST['txtContact'],64,'[^a-zA-Z0-9_\- ]')) { exit(); }
	if (! validate($_POST['txtCaptcha'],16,'[^a-zA-Z\- ]')) { exit(); }
	if (! validate($_POST['txtContactEmail'],128,'[^a-zA-Z0-9\.@\-_]')) { exit(); }
	if (! validate($_POST['hidType'],8,'{product|project|referral|support|website}')) { exit(); }
	if (! validate($_POST['hidCategory'],11,'{Accessories|Boards|Components|Devices|Elements}')) { exit(); }
	if ($_POST['hidType'] != 'website') {
		if (! validate($_POST['hidName'],16,'[^a-zA-Z\. ]')) {exit();}
		if (! validate($_POST['hidID'],64,'[^0-9]')) {exit();}
	} else {
		if (! validate($_POST['hidName'],64,'[^a-zA-Z0-9\.\-_ ]')) {exit();}
		if (! validate($_POST['hidID'],128,'[^a-zA-Z0-9\.\-_%:/]')) {exit();}
	}
	#if (! validate($_POST['txtContactMsg'],256,'![=<>;]')) { exit(); }	# NOTE: since this is just an email that gets sent, there is no reason to perform these checks; besides the &lt;/&gt; conversion will still retain a semicolon!

	# check the captcha is valid
	if (empty($_POST['txtCaptcha'])) {
		echo "<f><msg>You must enter the captcha text before notifying your contact.</msg></f>";
		exit();
	} else if (empty($_SESSION['captcha']) || trim(strtolower($_POST['txtCaptcha'])) != $_SESSION['captcha']) {
		echo "<f><msg>You captcha text you entered does NOT match what is found in the graphic, please try again.</msg></f>";
		exit();
	}
	unset($_SESSION['captcha']);

	if (! connect2DB(DBHOST,DBNAME,DBUNRO,DBPWRO)) { exit(); }

	# check if the referral is already a member
	if ($_POST['hidType'] == 'referral') {
		$gbl_errs['error'] = "The (existing) user account can not be found in the database.";
		if (USERS == '')						# IF we need to access the native Tracker DB table, then...
			{ $gbl_info['command'] = "SELECT id FROM ".PREFIX."Accounts WHERE email=? LIMIT 1"; }
		else
			{ $gbl_info['command'] = "SELECT id FROM ".PREFIX.USERS." WHERE ".EMAIL."=? LIMIT 1"; }
		$gbl_info['values'] = '[s] '.$_POST['txtContactEmail'];
		$stmt = $linkDB->prepare($gbl_info['command']);
		$stmt->bind_param('s', $_POST['txtContactEmail']);
		$stmt->execute();
		$existing = $stmt->get_result()->fetch_assoc();
		if ($existing) {
			echo "<f><msg>Thanks for the referral, but it appears that person is already a member!</msg></f>";
			exit();
		}
	}

	# if we've made it here, we can send the referral!
	$id = 0;								# set default values
	$name = 'Undisclosed';
	$contact = 'Sir/Madam,';
	if ($_POST['txtContact'] != '') { $contact = $_POST['txtContact']; }

	if ($_POST['username'] != '_guest' && $_POST['username'] != '') {	# if the user is logged in, we can tag their account with the referral so 
		$gbl_errs['error'] = "The user account can not be found in the database.";
		if (USERS == '')						# IF we need to access the native Tracker DB table, then...
			{ $gbl_info['command'] = "SELECT id,pes,alias,first,last FROM ".PREFIX."Accounts WHERE username=? LIMIT 1"; }
		else
			{ $gbl_info['command'] = "SELECT ".UID.",".ALIAS.",".FIRST.",".LAST." FROM ".PREFIX.USERS." WHERE ".USERNAME."=? LIMIT 1"; }
		$gbl_info['values'] = '[s] '.$_POST['username'];
		$stmt = $linkDB->prepare($gbl_info['command']);
		$stmt->bind_param('s', $_POST['username']);
		$stmt->execute();
		$account = $stmt->get_result()->fetch_assoc();
		if ($account) {
			if (USERS == '') {
				# decrypt the account information
				$salt = file_get_contents('../../denaccess');		# decrypt the users 'personal encryption string' (pes)
				$account['pes'] = Cipher::decrypt($account['pes'], $salt);	# use the 'pes' to decrypt the users account fields
				if (! is_null($account['last']) && $account['last'] != '') { $account['last'] = Cipher::decrypt($account['last'], $account['pes']); }	# NOTE: the "!= ''" is for accounts with a password reset

				if ($account['first'] != '') { $name = $account['first']; }
				else if ($account['alias'] != '') { $name = $account['alias']; }

				if ($account['first'] != '' && $account['last'] != '') { $name .= " ".$account['last']; }
			} else {
				if ($account['first'] != '') { $name = $account['first']; }
				else if ($account['alias'] != '') { $name = $account['alias']; }

				if ($account['first'] != '' && $account['last'] != '') { $name .= " ".$account['last']; }
			}
			$id = $account['id'];
		}
	}

	# construct the email to send to the contact
	$mail = new mimeMail();
	$mail->from = $gbl_emailNoReply;
	$mail->cc = "";
	$mail->headers = "Errors-To: ".$gbl_emailHackers;
	if ($_POST['hidType'] == 'support') { $mail->to = $_sSupportEmail; }		# support should go to your stored contact
	else { $mail->to = $_POST['txtContactEmail']; }					# otherwise, direct to the submitted value
	$mail->subject = "You have been referred!";
	if ($_POST['hidType'] == 'product')
// UPDATED 2025/02/15 - to be more generic
//		{ $mail->body = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>You have been referred!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$contact.",<br />\nYou have been referred to look at the \"".$_POST['hidName']."\" product in our online market by \"".$name."\" who thinks you would be interested in it! Please feel free to visit <a href='".$gbl_uriProject."/#".$_POST['hidCategory']."=".$_POST['hidID']."' target='_new'>our site</a>, to read about this and other ".$_POST['hidType']."s. If you have any questions, comments, or concerns, please contact our staff at your earliest convenience.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br /><br /><br /><br />Optional Message:<br />\n".$_POST['txtContactMsg']."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>"; }
		{ $mail->body = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>You have been referred!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$contact.",<br />\nYou have been referred to look at the \"".$_POST['hidName']."\" product in our online market by \"".$name."\" who thinks you would be interested in it! Please feel free <a href='".$_POST['hidID']."' target='_new'>to visit</a>, to read about this and other ".$_POST['hidType']."s. If you have any questions, comments, or concerns, please contact our staff at your earliest convenience.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br /><br /><br /><br />Optional Message:<br />\n".$_POST['txtContactMsg']."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>"; }
	else if ($_POST['hidType'] == 'project')
// UPDATED 2025/02/15 - to be more generic
//		{ $mail->body = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>You have been referred!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$contact.",<br />\nYou have been referred to look at our \"".$_POST['hidName']."\" project in our online market by \"".$name."\" who thinks you would be interested in it! Please feel free to visit <a href='".$gbl_uriProject."/#Shoppe=".$_POST['hidID']."' target='_new'>our site</a>, to read about this and other ".$_POST['hidType']."s. It is also important to note that our software is free of charge with optional technical support, alternative licensing, and more! If you have any questions, comments, or concerns, please contact our staff at your earliest convenience.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br /><br /><br /><br />Optional Message:<br />\n".$_POST['txtContactMsg']."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>"; }
		{ $mail->body = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>You have been referred!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$contact.",<br />\nYou have been referred to look at our \"".$_POST['hidName']."\" project in our online market by \"".$name."\" who thinks you would be interested in it! Please feel free <a href='".$_POST['hidID']."' target='_new'>to visit</a>, to explore this and other ".$_POST['hidType']."s. It is also important to note that our software is free of charge with optional technical support, alternative licensing, and more! If you have any questions, comments, or concerns, please contact our staff at your earliest convenience.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br /><br /><br /><br />Optional Message:<br />\n".$_POST['txtContactMsg']."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>"; }
	else if ($_POST['hidType'] == 'referral')
		{ $mail->body = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>You have been referred!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$contact.",<br />\nYou have been referred by \"".$name."\" who thinks you might find interest in what we have to offer. So feel free to <a href='".$gbl_uriProject."' target='_new'>visit us</a>, to find out what all the fuss is about! And by creating an account after using the preceding link, you will have access to fully interact with other users! If you have any questions, comments, or concerns, please contact our staff at your earliest convenience.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br /><br /><br /><br />Optional Message:<br />\n".$_POST['txtContactMsg']."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>"; }
	else if ($_POST['hidType'] == 'support')
		{ $mail->body = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>You have been referred!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$contact.",<br />\nYou have been notified by \"".$name."\" who needs support with <a href='".$gbl_uriProject."' target='_new'>".PROJECT."</a>. Some additional information will be included below to help isolate and resolve the issue. If this communication has been wrongfully directed to you, please forward the request to the appropriate entity so that help can be provided.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br /><br /><br /><br />Optional Message:<br />\n".$_POST['txtContactMsg']."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>"; }
	else if ($_POST['hidType'] == 'website')
// UPDATED 2025/02/15
//		{ $mail->body = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>You have been referred!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$contact.",<br />\nYou have been referred to our website by \"".$name."\". Perhaps they think it contains information you have been looking for or require, or just something that you may have interest in. So feel free to visit <a href='".$_POST['hidID']."' target='_new'>".$_POST['hidName']."</a>, to find out what all the fuss is about when you have a chance! If you have any questions, comments, or concerns, please contact our staff at your earliest convenience.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br /><br /><br /><br />Optional Message:<br />\n".$_POST['txtContactMsg']."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>"; }
		{ $mail->body = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>You have been referred!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$contact.",<br />\nYou have been referred to our website by \"".$name."\" who thinks you would be interested in it. So feel free to visit <a href='".$_POST['hidID']."' target='_new'>".$_POST['hidName']."</a>, to find out what all the fuss is about. And by creating an account after using the preceding link, you will have access to fully interact with other users! If you have any questions, comments, or concerns, please contact our staff at your earliest convenience.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br /><br /><br /><br />Optional Message:<br />\n".$_POST['txtContactMsg']."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>"; }

	if ($mail->send())
		{ echo "<s><msg>The referred person has been contacted - we appreciate it!</msg></s>"; }
	else
		{ echo "<f><msg>An error was encountered while attempting to send the mail, our staff has been alerted.</msg></f>"; }
	exit();




// LEFT OFF - none of the below are getting called anywhere!



} else if ($_POST['action'] == 'send' && $_POST['target'] == 'referral') {	// send the referral!
	# validate all submitted data
	if (! validate($_POST['id'],64,'[^0-9]')) { exit(); }

	# connect to the DB for writing below
	if (! connect2DB(DBHOST,DBNAME,DBUNRO,DBPWRO)) { exit(); }		# since the errors from this function are handled internal, we can just 'exit()' during a failure

	$name = 'Anonymous';
	if ($_POST['id'] > 0) {							# if the user was logged in when sending the referral, then lets fetch their account info
		$gbl_errs['error'] = "The user account can not be found in the database.";
		if (USERS == '')						# IF we need to access the native Tracker DB table, then...
			{ $gbl_info['command'] = "SELECT pes,alias,first,last,company FROM ".PREFIX."Accounts WHERE id=? LIMIT 1"; }
		else
			{ $gbl_info['command'] = "SELECT ".ALIAS.",".FIRST.",".LAST." FROM ".PREFIX.USERS." WHERE ".UID."=? LIMIT 1"; }
		$gbl_info['values'] = '[i] '.$_POST['id'];
		$stmt = $linkDB->prepare($gbl_info['command']);
		$stmt->bind_param('i', $_POST['id']);
		$stmt->execute();
		$Referral = $stmt->get_result()->fetch_assoc();
		if (! $Referral) {
			echo "<f><msg>The referrals account could not be found in the database.</msg></f>";
			exit();
		}

		if (USERS == '') {
			$salt = file_get_contents('../../denaccess');		# decrypt the users 'personal encryption string' (pes)
			$Referral['pes'] = Cipher::decrypt($Referral['pes'], $salt);	# use the 'pes' to decrypt the users account fields
			if (! is_null($Referral['last']) && $Referral['last'] != '') { $Referral['last'] = Cipher::decrypt($Referral['last'], $Referral['pes']); }	# NOTE: the "!= ''" is for accounts with a password reset

			if ($Referral['first'] != '') { $name = $Referral['first']; } else if ($Referral['alias'] != '') { $name = $Referral['alias']; }
			if ($Referral['first'] != '' && $Referral['last'] != '') { $name .= " ".$Referral['last']; }
			if ($Referral['company'] != '') {
				if ($name == '') { $name = $Referral['company']; } else { $name .= ' @ '.$Referral['company']; }
			}
		} else {
			if ($Referral['first'] != '') { $name = $Referral['first']; } else if ($Referral['alias'] != '') { $name = $Referral['alias']; }
			if ($Referral['first'] != '' && $Referral['last'] != '') { $name .= " ".$Referral['last']; }
		}
	}

	echo "<s><data id='".$_POST['id']."' name=\"".$name."\" /></s>\n";
	exit();


} else if ($_POST['action'] == 'send' && $_POST['target'] == 'job') {		// send the job referral!
	// make appropriate substitutions
	$_POST['txtContactMsg'] = str_replace('<','&lt;',$_POST['txtContactMsg']);
	$_POST['txtContactMsg'] = str_replace('>','&gt;',$_POST['txtContactMsg']);

	# validate all submitted data
	if (! validate($_POST['txtContact'],64,'[^a-zA-Z0-9_\- ]')) { exit(); }
	if (! validate($_POST['txtContactEmail'],128,'[^a-zA-Z0-9\.@\-_]')) { exit(); }
	#if (! validate($_POST['txtContactMsg'],256,'![=<>;]')) { exit(); }	# NOTE: since this is just an email that gets sent, there is no reason to perform these checks; besides the &lt;/&gt; conversion will still retain a semicolon!
	if (! validate($_POST['title'],10,'{Marketing|Sales|Service|Support}')) { exit(); }

	# construct the email to send to the contact
	$mail = new mimeMail();
	$mail->from = $gbl_emailNoReply;
	$mail->cc = "";
	$mail->headers = "Errors-To: ".$gbl_emailHackers;
	$mail->to = $_POST['txtContactEmail'];
	$mail->subject = "You have been referred!";
	$mail->body = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>You have been referred!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\n".$_POST['txtContact'].",<br />\nYou have been referred to look at the \"".$_POST['title']."\" job posting on our website by someone who thinks you would be interested in it! Please feel free to visit <a href='".$gbl_uriProject."/#Earn' target='_new'>our site</a>, to read about this and other jobs and methods of earning money by working with us. If you have any questions, comments, or concerns, please contact our staff at your earliest convenience.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br /><br /><br /><br />Optional Message:<br />\n".$_POST['txtContactMsg']."</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>";

	if ($mail->send())
		{ echo "<s><msg>The referred person has been contacted - we appreciate it!</msg></s>"; }
	else
		{ echo "<f><msg>An error was encountered while attempting to send the mail, our staff has been alerted.</msg></f>"; }
	exit();


} else if ($_POST['action'] == 'apply' && $_POST['target'] == 'job') {		// apply for the job posting
	# validate all submitted data
	if (! validate($_POST['id'],6,'[^0-9]')) { exit(); }
	if (! validate($_POST['title'],10,'{Marketing|Sales|Service|Support}')) { exit(); }

	# construct the email to send to the contact (with attachment)
#	echo email::sendMail("dhenderson@digital-pipe.com", "Test Attach ".date("H:i:s"), "This is the body", $_POST['hidResume'], '', '', false);

	$mail = new mimeMail();
	$mail->from = $gbl_emailNoReply;
	$mail->cc = "";
	$mail->headers = "Errors-To: ".$gbl_emailHackers;
	$mail->to = 'jobs@'.$gbl_uriContact;
	$mail->subject = "A job application has been received!";
	$mail->body = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_invite.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Job applicant submission!</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\nWe have had a visitor to our site take interest in one of our job postings! The application is for our open position in \"".$_POST['title']."\" and has the random prefix ID of \"".$_POST['id']."\". Please review this applicant at your earliest convenience and response to their submission if they meet our requirements.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>";
#	if ($_POST['hidCover'] != '') {
#file_put_contents('debug.txt', "we have a cover value!!!\n", FILE_APPEND);
#		$mail->
#	}
#if ($_POST['hidResume'] != '') {
#file_put_contents('debug.txt', "we have a resume value!!!\n", FILE_APPEND);
#}

	if ($mail->send())
		{ echo "<s></s>"; }
	else
		{ echo "<f><msg>An error was encountered while attempting to process the request, our staff has been alerted.</msg></f>"; }
	exit();


} else if ($_POST['action'] == 'send' && $_POST['target'] == 'bug') {		// send the external bug submission
	// make appropriate substitutions
	$_POST['description'] = str_replace('<','&lt;',$_POST['description']);
	$_POST['description'] = str_replace('>','&gt;',$_POST['description']);

	# validate all submitted data
	if (! validate($_POST['txtCaptcha'],16,'[^a-zA-Z\- ]')) { exit(); }
	if (! validate($_POST['title'],128,"![=<>;]")) { exit(); }
	if (! validate($_POST['description'],3072,"![=<>;]")) { exit(); }

	# construct the email to send to the contact
	$mail = new mimeMail();
	$mail->from = $gbl_emailNoReply;
	$mail->cc = "";
	$mail->headers = "Errors-To: ".$gbl_emailHackers;
	$mail->to = 'hackers@cliquesoft.org';
	$mail->subject = "External Bug Submission";
	$mail->body = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>External Bug Submission</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\nWe have had a bug submission from an external installation of our ".PROJECT." software. All of the necessary information will be included below in order to identify the problem at hand along with the contact information for the organization hosting an instance of our software. Please investigate this matter at your earliest convenience and add any verified bug to our internal tracking for resolution.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff<br />\n<br />\n<br />\nProject: ".$gbl_uriProject."<br />\nHackers: ".$gbl_emailHackers."<br />\nCrackers: ".$gbl_emailCrackers."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nTitle: ".$_POST['title']."<br />\nDescription: ".$_POST['description']."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>";

	if ($mail->send())
		{ echo "<s><msg>The Cliquesoft staff has been contacted - we appreciate it!</msg></s>"; }
	else
		{ echo "<f><msg>An error was encountered while attempting to send the mail, our staff has been alerted.</msg></f>"; }
	exit();


} else if ($_POST['action'] == 'send' && $_POST['target'] == 'feature') {	// send the external feature request
	// make appropriate substitutions
	$_POST['description'] = str_replace('<','&lt;',$_POST['description']);
	$_POST['description'] = str_replace('>','&gt;',$_POST['description']);

	# validate all submitted data
	if (! validate($_POST['txtCaptcha'],16,'[^a-zA-Z\- ]')) { exit(); }
	if (! validate($_POST['title'],128,"![=<>;]")) { exit(); }
	if (! validate($_POST['description'],3072,"![=<>;]")) { exit(); }

	# construct the email to send to the contact
	$mail = new mimeMail();
	$mail->from = $gbl_emailNoReply;
	$mail->cc = "";
	$mail->headers = "Errors-To: ".$gbl_emailHackers;
	$mail->to = 'hackers@cliquesoft.org';
	$mail->subject = "External Feature Request";
	$mail->body = "<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>External Feature Request</h2>\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\nWe have had a new feature request from an external installation of our ".PROJECT." software. All of the necessary information will be included below in order to explain the new feature along with the contact information for the organization hosting an instance of our software. Please investigate this matter at your earliest convenience and add any legitimate request to our internal tracking for future inclusion.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff<br />\n<br />\n<br />\nProject: ".$gbl_uriProject."<br />\nHackers: ".$gbl_emailHackers."<br />\nCrackers: ".$gbl_emailCrackers."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nTitle: ".$_POST['title']."<br />\nDescription: ".$_POST['description']."<br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>";

	if ($mail->send())
		{ echo "<s><msg>The Cliquesoft staff has been contacted - we appreciate it!</msg></s>"; }
	else
		{ echo "<f><msg>An error was encountered while attempting to send the mail, our staff has been alerted.</msg></f>"; }
	exit();




} else {					// otherwise, we need to indicate that an invalid request was made

	echo "<f><msg>An invalid request has occurred, our staff has been notified.</msg></f>";
	sendMail($gbl_emailCrackers,$gbl_nameCrackers,$gbl_emailNoReply,$gbl_nameNoReply,'!!! Possible Cracking Attempt !!!',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".$gbl_nameProject."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nWe might have had a possible cracking attempt made on ".$_.", from ".$_SERVER['REMOTE_ADDR'].", while attempting to pass an invalid API value.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nUsername: ".$gbl_user['username']."<br />\nAddress: ".$gbl_user['email']."<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\n<br />\nOur Error: An invalid API value was passed to the script.<br />\n<br />\nVar Dump:<br />\n".print_r($_GET, true)."<br />\n".print_r($_POST, true)."<br />\n<br />\n[".SCRIPT."; Body]<br />\n<br />\nSincerely,<br />\n".$gbl_nameProject." Staff\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n</table>\n</body>\n</html>");


}
?>
