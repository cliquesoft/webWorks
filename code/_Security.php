<?php
# _Security.php
#
# Created	2009/10/08 by Dave Henderson (support@cliquesoft.org)
# Updated	2025/08/26 by Dave Henderson (support@cliquesoft.org)
#
# Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
# device, this software is licensed under the Cliquesoft Public License (CPLv2)
# as found on the Cliquesoft website at www.cliquesoft.org.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.


if (! defined("MODULE")) {					# if this script IS being called directly, then...
	# Constant Definitions
	define("MODULE",'_Security');				# the name of this module (NOTE: this can be the same as the PROJECT constant in the envars.php file)
	define("SCRIPT",basename($_SERVER['SCRIPT_NAME']));	# the name of this script (for tracing bugs and automated messages)

	# Module Requirements					  NOTE: these must be declared in the calling script
	#require_once('../../sqlaccess');
	#require_once('../data/_modules/ApplicationSettings/config.php');
	#require_once('_Project.php');
	#require_once('_Contact.php');
}




# Usage syntax:
#	$key = Cipher::create_encryption_key();7
#	$val = 'Sri Lanka is a beautiful country!';
#	$encrypted = Cipher::encrypt($val, $key); 
#		echo "Encrypted: ".$encrypted;
#	$decrypted = Cipher::decrypt($encrypted, $key);
#		echo "Decrypted: ".$decrypted;
# NOTES
#	https://stackoverflow.com/questions/4484246/encrypt-and-decrypt-text-with-rsa-in-php
#	https://deliciousbrains.com/php-encryption-methods/
#	https://www.zimuel.it/blog/strong-cryptography-in-php
#	Simple sodium crypto class for PHP >= 7.2
#	Author: MRK
class Cipher {
	# @return type
	static public function create_encryption_key()
		{ return base64_encode(sodium_crypto_secretbox_keygen()); }

	# Encrypt a value and return it!
	# $val	value to encrypt
	# $key	encryption key (via create_encryption_key())
	static function encrypt($val, $key) {
		if (is_null($val) || $val == '') { return ''; }			# if the passed value is blank, then no need to do any processing

		$key_decoded = base64_decode($key);
		$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

		$cipher = base64_encode($nonce . sodium_crypto_secretbox($val, $nonce, $key_decoded));
		sodium_memzero($val);
		sodium_memzero($key_decoded);
		return $cipher;
	}

	# Decrypt a value and return it!
	# $val - value to decrypt
	# $key - encryption key
	static function decrypt($val, $key) {
		if (is_null($val) || $val == '') { return ''; }			# if the passed value is blank, then no need to do any processing

		$decoded = base64_decode($val);
		$key_decoded = base64_decode($key);

		if ($decoded === false) { throw new Exception('Error: The encoding failed.'); }
		if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES))
			{ throw new Exception('Error: The message was truncated.'); }

		$nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
		$ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

		$plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $key_decoded);
		if ($plain === false)
			{ throw new Exception('Error: The message was tampered with in transit.'); }
		sodium_memzero($ciphertext);
		sodium_memzero($key_decoded);
		return $plain;
	}
}




# Usage syntax:
#	validate(variable,14,'{red|green|blue}')		this allows up to a 14 character string with the only values being red, green, or blue
#	validate(variable,5,'{true|false}')			this allows only a boolean value to be checked and will convert text values of 'true' (1) or 'false' (0) into an actual boolean value being stored
#	validate(variable,8,'!a-z0-9')				this checks that it does NOT contain lowercase letters and numbers (by comparing against disallowed characters)
#	validate(variable,25,'a-z')				this checks for values under 25 characters and that it ONLY contains lowercase letters (by comparing against allowed characters)
#	validate(variable,128,'')				this checks for values under 128 characters, but allows any characters to be submitted
#	validate(variable,#,'^[(')				this processes a custom regular expression if it's preceeded by any of those three characters
# NOTE: can be used like: if (! validate($_POST['whatever'],14,'{red|green|blue}')) { FLAG=1; }
function validate(&$sValue,$nLength,$sMatch,$sErrors='x') {	# https://stackoverflow.com/questions/9166914/using-default-arguments-in-a-function
# validates data against the 'sMatch' value
# sValue	the data to be validated (e.g. $_POST['username'])
# nLength	the string length that must not be exceeded by the 'sValue'
# sMatch	a list of values or a regular expression to match against;	NOTE: a specific value of '{true|false}' or '{1|0}' will convert text values into a boolean value
# sErrors	defines how errors should be handled; valid values: (a)rray, (h)tml, (t)ext, (x)ml, blank value disables output
	global $__sMsgs,$_sAlertsName,$_sAlertsEmail,$_sSecurityName,$_sSecurityEmail,$_sUriProject;

	# if the passed value does NOT exist (e.g. $_POST['absent']), then exit this function
	if (! isset($sValue)) { return 1; }

	# check that the length of the passed value is less than what is allowed
	if (strlen($sValue) > $nLength) {
		if ($sErrors == 'a') { $__sMsgs[] = "The \"".$sValue."\" value has more characters than are allowed (max = ".$nLength.")."; }
		else if ($sErrors == 'h') { echo "<div class='divFail'>The \"".$sValue."\" value has more characters than are allowed (max = ".$nLength.").</div>\n"; }
		else if ($sErrors == 't') { echo "The \"".$sValue."\" value has more characters than are allowed (max = ".$nLength.").\n"; }
		else if ($sErrors == 'x') { echo "<f><msg>The \"".$sValue."\" value has more characters than are allowed (max = ".$nLength.").</msg></f>"; }

		sendMail($_sSecurityEmail,$_sSecurityName,$_sAlertsEmail,$_sAlertsName,'*** Possible Cracking Attempt ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\nFunction: validate<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nOur Error: A user is attempting to pass too large of a value during validation.<br />\n<br />\nMax Length: ".$nLength." characters<br />\nProcessed Value: ".$sValue."<br />\nVar Dump:<br />\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre><br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		return 0;
	}

	# setup a variable to indicate an issue has been found
	$bFound = false;
	$bProblems = false;

	# remove any RegEx formatting so we can isolate just the characters that are (dis)allowed
	$sAccepted = preg_replace('/^\!|^\^\[|^\[\^|^\[|\]\+\$$|\]\*\$$|\]\$$|\$\]$|\]$/', '', $sMatch);

	# adjust the values so the display is readable for the user if an error occurs below
	$sAccepted = str_replace('0-9', '0-9, ', $sAccepted);
	$sAccepted = str_replace('A-Z', 'A-Z, ', $sAccepted);
	$sAccepted = str_replace('a-z', 'a-z, ', $sAccepted);
	if (strrpos($sAccepted, ', ') == strlen($sAccepted)-2)	# if one of the above replacements occured, but there are no trailing single characters, then erase the ', ' postfix
		{ $sAccepted = substr($sAccepted, 0, strlen($sAccepted)-2); }
	else if (strpos($sAccepted, ', ') > -1)			# if one of the above replacements occured, then comma separate all following single characters
		{ $sAccepted = substr($sAccepted, 0, strrpos($sAccepted, ', ')) . ', ' . implode(', ', str_split(substr($sAccepted, strrpos($sAccepted, ', ')+2))); }
	else							# otherwise we have no groups of characters, so separate them all as single characters
		{ $sAccepted = implode(', ', str_split($sAccepted)); }

	# if no value (e.g. combobox option with no value) or match (e.g. a password that accepts all characters) was passed, then we don't have any further validating so lets exit!
	if ($sValue == '') { return 1; }
	if ($sMatch == '') { return 1; }

	# now check that the value entered contains legal characters
	# https://stackoverflow.com/questions/1735972/php-fastest-way-to-check-for-invalid-characters-all-but-a-z-a-z-0-9
	# https://stackoverflow.com/questions/1972100/getting-the-first-character-of-a-string-with-str0
	if ($sMatch[0] == '^' || $sMatch[0] == '[' || $sMatch[0] == '(') {		# if we need to process a custom RegEx, then...
		if (preg_match('/'.$sMatch.'/', $sValue)) { $bProblems = true; }
		$sAccepted = '';

	} else if ($sMatch[0] == '!') {					# if we need to process invalid, illegal characters, then...
		if (preg_match('/['.substr($sMatch,1).']/', $sValue)) { $bProblems = true; }
		$sAccepted = ' All characters are allowed EXCEPT: '.$sAccepted;

	} else if ($sMatch[0] != '!' && $sMatch[0] != '{') {		# if we need to process valid, allowed characters, then...
		if (preg_match('/^['.$sMatch.']*$/', $sValue) === 0) { $bProblems = true; }
		$sAccepted = ' The ONLY allowed characters are: '.$sAccepted;

	} else if ($sMatch[0] == '{') {					# if we need to process valid, allowed values, then...
		if ($sMatch == '{true|false}' || $sMatch == '{1|0}') {	#   if one of these "special" boolean values is passed, then...
			# if the value isn't boolean (actual, text, or numeric), then...
			if ($sValue != 'true' && $sValue != 'false' && $sValue != '1' && $sValue != '0') {
				$bProblems = true;
			# otherwise it is one of the above values, and we may need further processing...
			} else {
				$bFound = true;					# this prevents a trigger of the following block of code
				$sMatch = trim($sMatch, "{}");			# remove the brackets from the value
				$Matches = explode('|',$sMatch);
				# the below converts text and number values into actual boolean values for the passed 'sValue' (so that code that depends on boolean works correctly instead of checking string or numeric values)
				if (is_numeric($sValue))
					{ $sValue = ($sValue == '1') ? 1 : 0; }
				else if (is_string($sValue))
					{ $sValue = ($sValue == 'true') ? true : false; }
			}
		} else {							#   otherwise we need to process a list of valid values
			$values = explode("|", substr($sMatch, 1, -1));
			foreach ($values as $value)				#   cycle each allowed value to see if the one passed matches one of them
				{ if ($value == $sValue) {$bFound = true; break;} }
		}
		if (! $bFound || $bProblems) {					# if the value was NOT found in the passed list -OR- an expected boolean value was NOT sent, then...
			if ($sErrors == 'a') { $__sMsgs[] = "The \"".$sValue."\" value does not match any allowed."; }
			else if ($sErrors == 'h') { echo "<div class='divFail'>The \"".$sValue."\" value does not match any allowed.</div>\n"; }
			else if ($sErrors == 't') { echo "The \"".$sValue."\" value does not match any allowed.\n"; }
			else if ($sErrors == 'x') { echo "<f><msg>The \"".$sValue."\" value does not match any allowed.</msg></f>"; }

			sendMail($_sSecurityEmail,$_sSecurityName,$_sAlertsEmail,$_sAlertsName,'*** Possible Cracking Attempt ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\nFunction: validate<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nOur Error: A user is attempting to pass a value not permitted during validation.<br />\n<br />\nAllowed Values: ".substr($sMatch, 1, -1)."<br />\nProcessed Value: ".$sValue."<br />\nVar Dump:<br />\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre><br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
			return 0;
		}
	}

	if ($bProblems) {
		if ($sErrors == 'a') { $__sMsgs[] = "There is an invalid character in the \"".$sValue."\" value.".$sAccepted; }
		else if ($sErrors == 'h') { echo "<div class='divFail'>There is an invalid character in the \"".$sValue."\" value.".$sAccepted."</div>\n"; }
		else if ($sErrors == 't') { echo "There is an invalid character in the \"".$sValue."\" value.".$sAccepted."\n"; }
		else if ($sErrors == 'x') { echo "<f><msg>There is an invalid character in the \"".$sValue."\" value.".$sAccepted."</msg></f>"; }

		sendMail($_sSecurityEmail,$_sSecurityName,$_sAlertsEmail,$_sAlertsName,'*** Possible Cracking Attempt ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/email_alert.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1>\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nOne of the users was interacting with our '".PROJECT."' project, but encountered the listed error in the process of doing so.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\nFunction: validate<br />\n<br />Calling Script: ".SCRIPT."<br />\n<br />\n\nDB Host: ".DB_HOST."<br />\nDB Name: ".DB_NAME."<br />\nDB Prefix: ".DB_PRFX."<br />\nIP Address: ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\nOur Error: A user is attempting to pass a value containings illegal characters during validation.<br />\n<br />\nAllowed Characters: ".$sMatch."<br />\nProcessed Value: ".$sValue."<br />\nVar Dump:<br />\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre><br />\n</p>\n</td>\n<td>&nbsp;</td>\n</tr>\n<table>\n</body>\n</html>");
		return 0;
	}

	return 1;								# if we've made it here, then everything checked out just fine
}




# Usage syntax:
# $variable = charSwap($_POST['obj'],'in');	makes the values safe for processing
# $variable = charSwap($_POST['obj'],'out');	reverts the values back to their original form
function charSwap(&$sValue,$sFlow) {
# Useful to swap a few characters to help prevent malicious SQL injections/attacks
	if ($sFlow == 'in') {							# remove malicious syntax
		$sValue = str_replace('=',chr(0xB1),$sValue);
		$sValue = str_replace('<',chr(0xAB),$sValue);
		$sValue = str_replace('>',chr(0xBB),$sValue);
		$sValue = str_replace(';',chr(0xA1),$sValue);
	} else if ($sFlow == 'out') {						# convert the prior values back to the originals
		$sValue = str_replace(chr(0xB1),'=',$sValue);
		$sValue = str_replace(chr(0xAB),'<',$sValue);
		$sValue = str_replace(chr(0xBB),'>',$sValue);
		$sValue = str_replace(chr(0xA1),';',$sValue);
	}
	return $sValue;
}




# Usage syntax:
# $variable = safeXML($_POST['obj']);
function safeXML(&$sValue) {
	if (! $sValue) { return ''; }						# if the parameter passed is null (or doesn't exist yet), then return a blank string

# This function makes the passed value safe for XML transmission (e.g. & > &amp;)
	$sValue = str_replace('&','&amp;',$sValue);				# used for syntax friendly xml (HAS to come first)
	$sValue = str_replace('<','&lt;',$sValue);				# these make any saved "<pre>" tags work correctly
	$sValue = str_replace('>','&gt;',$sValue);
# MOVED 2022/10/18 - this was moved up to prevent symbols (e.g. <) from being mangled (e.g. '&amp;lt;')
#	$sValue = str_replace('&','&amp;',$sValue);				# used for syntax friendly xml (HAS to come last)
	$sValue = str_replace('"','&quot;',$sValue);
	$sValue = str_replace("'",'&apos;',$sValue);
	return $sValue;
}
?>
