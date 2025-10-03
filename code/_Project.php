<?php
# _Project.php
#
# Created	2009/10/08 by Dave Henderson (support@cliquesoft.org)
# Updated	2025/03/29 by Dave Henderson (support@cliquesoft.org)
#
# Unless a valid Cliquesoft Proprietary License (CPLv1) has been purchased
# for this device, this software is licensed under the Cliquesoft Public
# License (CPLv2) as found on the Cliquesoft website at www.cliquesoft.org
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.
#
# Global (Javascript & PHP):
#	b - boolean	(true/false)
#	e - epoch	(date and time)
#	n - number	(numbers, including comma and period)
#	o - object	(mimemail [php], ajax [javascript], ...)
#	s - string	(single character, words, phrases, epochs, ...)
#	m - mixed	(multiple values)
#
# PHP Prepared SQL:
#	b - blob/binary	(such as image, PDF file, etc.)
#	d - double	(decimal/floating point number)
#	i - integer	(whole number)
#
# Javascript API Parameter Name:
#	s_ 		success
#	f_ 		failure
#	*_		custom 
#   COMMONLY USED:
#	i_		implement (e.g. implement a follow-up step)
#	c_		challenge (e.g. asking account security question)
#	v_		verify (e.g. answering account security question)
#
# Variable Designations:
# 	The first letter of the name indicates the type of value stored.
#
#    NOTES:
#	- No preceeding underscore indicates a local variable (e.g. sVariable)
#	- A following underscore indicates a local array of that type (e.g. s_Variable)
#	- A preceeding underscore indicates it's a global variable (e.g. _sVariable).
#	- 2 preceeding dashes indicates it's a global array of that type (e.g. __sVariable).
#	- A mixed value array has no preceeding character (e.g. __User & User)




# -- Error Handling --


# This tells php how to handle -MOST- errors that it encounters (specifies which function to call during these encountered events)
set_error_handler("myErrorHandler");
function myErrorHandler($errno, $errstr, $errfile, $errline) {
# array definitions:
#	$__sInfo['command']	the command that was attempting to be executed that threw the error
#	$__sInfo['contact']	a phone number, email address, or some information for contacting the user
#	$__sInfo['continue']	whether or not script execution should continue after the error has triggered this function
#	$__sInfo['error']	considered "our error" which is a summary of the problem encountered
#	$__sInfo['name']	the username, name, alias, or some identifying data of the user account
#	$__sInfo['other']	any other account information relevent to the error (optional)
#	$__sInfo['output']	defines how any (error) messages should be output: (a)rray, (h)tml, (t)ext, (x)ml, blank value disables output (default)
#	$__sInfo['prompt']	the prompt to show to the user; without this value a generic default will be displayed; a blank value disables message
#	$__sInfo['values']	the values associated with the 'command' when using prepared SQL statements
# NOTES:
# https://www.php.net/manual/en/function.set-error-handler.php


# LEGACY
	if (isset($gbl_errs['error'])) {
#file_put_contents('debug.txt', "MEH - oops top!\n", FILE_APPEND);
#		$gbl_info['admin']	the name of the admin to email									moved to $_sSupportName
#		$gbl_info['email']	the email address of the admin									moved to $_sSupportEmail
#		$gbl_errs['error']	considered "our error" which is a summary of the problem encountered				moved to $__sInfo['error']

		global $gbl_emailNoReply,$gbl_nameNoReply,$gbl_uriProject,$gbl_errs,$gbl_info,$gbl_msgs,$gbl_dirLogs,$gbl_logScript;

		if (! isset($gbl_info['admin']) || $gbl_info['admin'] == '') { $gbl_info['admin'] = $gbl_nameHackers; }
		if (! isset($gbl_info['email']) || $gbl_info['email'] == '') { $gbl_info['email'] = $gbl_emailHackers; }
# VER2 - below is LEGACY WAY, remove it once project is converted to gbl_info
		if (! isset($gbl_errs['error']) || $gbl_errs['error'] == '') { $gbl_errs['error'] = 'Not Provided'; }

		$_sSupportEmail = $gbl_info['email'];
		$_sSupportName = $gbl_info['admin'];
		$_sUriProject = $gbl_uriProject;
		$__sInfo = $gbl_info;
	} else {
#file_put_contents('debug.txt', "MEH - btm\n", FILE_APPEND);
# NEW WAY
		global $_sLogProject,$_sSupportEmail,$_sSupportName,$_sAlertsEmail,$_sAlertsName,$_sUriProject,$__sInfo,$__sMsgs;
	}

	# set some default values
	if (! isset($__sInfo['command']) || $__sInfo['command'] == '') { $__sInfo['command'] = 'Not Provided'; }
	if (! isset($__sInfo['contact']) || $__sInfo['contact'] == '') { $__sInfo['contact'] = 'Unknown'; }
	if (! isset($__sInfo['error']) || $__sInfo['error'] == '') { $__sInfo['error'] = 'Not Provided'; }
	if (! isset($__sInfo['name']) || $__sInfo['name'] == '') { $__sInfo['name'] = 'Unknown'; }
	if (! isset($__sInfo['other']) || $__sInfo['other'] == '') { $__sInfo['other'] = 'None'; }
	if (! isset($__sInfo['output']) || $__sInfo['output'] == '') { $__sInfo['output'] = 'x'; }
#		if (array_key_exists('id', $_POST)) { if (! validate($_POST['id'],20,'[^0-9]')) {exit();} } else { $_POST['id'] = 0; }
	if (! isset($__sInfo['values']) || $__sInfo['values'] == '') { $__sInfo['values'] = 'None'; }
# LEFT OFF - is the below line correct?  shouldn't we display an error message and contact the staff.  the 'continue should be where the 'exit(1)' or 'return false' should be called right?
	if (! isset($__sInfo['continue']) || $__sInfo['continue'] === FALSE) {
#file_put_contents('debug.txt', "MEH - if\n", FILE_APPEND);
		#if (isset($__sInfo['prompt']) || array_key_exists('prompt',$__sInfo))				# https://www.php.net/manual/en/function.array-key-exists.php
		if (! isset($__sInfo['prompt']) || $__sInfo['prompt'] == '') {
#file_put_contents('debug.txt', "MEH - if top\n", FILE_APPEND);
			if (isset($__sInfo['output'])) {
#file_put_contents('debug.txt', "MEH - if top 2\n", FILE_APPEND);
				if ($__sInfo['output'] == 'a') { $__sMsgs[] = "There was an error processing your request and our staff has been notified.  Please try again in a few minutes."; }
				else if ($__sInfo['output'] == 'h') { echo "<div class='divFail'>There was an error processing your request and our staff has been notified.  Please try again in a few minutes.</div>\n"; }
				else if ($__sInfo['output'] == 't') { echo "There was an error processing your request and our staff has been notified.  Please try again in a few minutes.\n"; }
				else if ($__sInfo['output'] == 'x') { echo "<f><msg>There was an error processing your request and our staff has been notified.  Please try again in a few minutes.</msg></f>"; }
			}
		} else if ($__sInfo['prompt'] != 'off') {
#file_put_contents('debug.txt', "MEH - if btm\n", FILE_APPEND);
			if (isset($__sInfo['output'])) {
#file_put_contents('debug.txt', "MEH - if btm top |".$__sInfo['output']."|".$__sInfo['prompt']."|\n", FILE_APPEND);
				$__sInfo['prompt'] = str_replace("\n", " ", $__sInfo['prompt']);		# remove any newline characters

				if ($__sInfo['output'] == 'a') { $__sMsgs[] = $__sInfo['prompt']; }
				else if ($__sInfo['output'] == 'h') { echo "<div class='divFail'>".$__sInfo['prompt']."</div>\n"; }
				else if ($__sInfo['output'] == 't') { echo $__sInfo['prompt']."\n"; }
				else if ($__sInfo['output'] == 'x') {
#file_put_contents('debug.txt', "MEH - if btm XML\n", FILE_APPEND);
 echo "<f><msg>".$__sInfo['prompt']."</msg></f>"; }
			}
		}
	}
#file_put_contents('debug.txt', "MEH - after\n", FILE_APPEND);
	# Alternative to show a page instead of error message
	#header('HTTP/1.1 500 Internal Server Error', TRUE, 500);
	#readfile("500.html");
# REMOVED 2019/07/31 - this was triggering problems of its own (even if the file existed!!!)
#	error_log("[".gmdate("Y-m-d H:i:s",time())."]\n$errfile\n$errline: $errstr\n\n",3,$gbl_dirLogs.'/'.$gbl_logScript);

# LEFT OFF - remove the below two lines once gbl_errs is phased out	
	if (isset($gbl_errs['error']))
# LEGACY WAY
		{
#file_put_contents('debug.txt', "MEH - email top\n", FILE_APPEND);
 sendMail($gbl_info['email'],$gbl_info['admin'],$gbl_emailNoReply,$gbl_nameNoReply,'*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nOne of our users was attempting to interact with our site, but encountered an error which has been included below.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<u>Date:</u> ".gmdate("Y-m-d H:i:s",time())." GMT<br />\n<u>From:</u> ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\n<u>Project:</u> ".PROJECT."<br />\n<u>Module:</u> ".MODULE."<br />\n<u>Script:</u> ".SCRIPT."<br />\n<br />\n<u>DB Host:</u> ".DBHOST."<br />\n<u>DB Name:</u> ".DBNAME."<br />\n<u>DB Prefix:</u> ".PREFIX."<br />\n<br />\n<u>Name:</u> ".$gbl_info['name']."<br />\n<u>Contact:</u> ".$gbl_info['contact']."<br />\n<u>Other:</u> ".$gbl_info['other']."<br />\n<br />\n<u>Summary:</u> ".$gbl_errs['error']."<br />\n<u>Error:</u> (".$errno.") ".$errstr."<br />\n<u>Command:</u> ".$gbl_info['command']."<br />\n<u>Values:</u> ".$gbl_info['values']."<br />\n<u>File:</u> ".$errfile."<br />\n<u>Line:</u> ".$errline."<br />\n<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br />\n<br />\nVar Dump:<br />\n</p>\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre><br />\n</td>\n<td>&nbsp;</td>\n</tr>\n</table>\n</body>\n</html>"); }
	else {
# NEW WAY
# LEFT OFF - before executing the below code, make sure all the variables have values (so another error doesn't occur)

#file_put_contents('debug.txt', "MEH - email btm |".$_sSupportEmail."|".$_sSupportName."|".$_sAlertsEmail."|".$_sAlertsName."|\n", FILE_APPEND);
#file_put_contents('debug.txt', "MEH - email btm2 |".PROJECT."|".MODULE."|".SCRIPT."|".DBHOST."|".DBNAME."|".PREFIX."|\n", FILE_APPEND);
#file_put_contents('debug.txt', "MEH - email btm3 |".$__sInfo['name']."|".$__sInfo['contact']."|".$__sInfo['other']."|".$__sInfo['error']."|".$errno."|".$errstr."|".$__sInfo['command']."|".$__sInfo['values']."|".$errfile."|".$errline."|\n", FILE_APPEND);
#file_put_contents('debug.txt', "|".$_sSupportEmail."|".$_sSupportName."|".$_sAlertsEmail."|".$_sAlertsName."\n", FILE_APPEND);
		file_put_contents('../data/_logs/'.$_sLogProject, "---------- [ Script Execution Error ] ----------\nDate: ".gmdate("Y-m-d H:i:s",time())." GMT\nFrom: ".$_SERVER['REMOTE_ADDR']."\n\nProject: ".PROJECT."\nModule: ".MODULE."\nScript: ".SCRIPT."\n\nDB Host: ".DB_HOST."\nDB Name: ".DB_NAME."\nDB Prefix: ".DB_PRFX."\n\nName: ".$__sInfo['name']."\nContact: ".$__sInfo['contact']."\nOther: ".$__sInfo['other']."\n\nSummary: ".$__sInfo['error']."\nError: (".$errno.") ".$errstr."\nCommand: ".$__sInfo['command']."\nValues: ".$__sInfo['values']."\nFile: ".$errfile."\nLine: ".$errline."\n\nVar Dump:\n\n_POST\n".print_r($_POST, true)."\n_GET\n".print_r($_GET, true)."\n\n\n\n", FILE_APPEND);
		sendMail($_sSupportEmail,$_sSupportName,$_sAlertsEmail,$_sAlertsName,'*** Script Execution Error ***',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$_sUriProject."home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Script Execution Error</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nOne of our users was attempting to interact with our site, but encountered an error which has been included below.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<u>Date:</u> ".gmdate("Y-m-d H:i:s",time())." GMT<br />\n<u>From:</u> ".$_SERVER['REMOTE_ADDR']."<br />\n<br />\n<u>Project:</u> ".PROJECT."<br />\n<u>Module:</u> ".MODULE."<br />\n<u>Script:</u> ".SCRIPT."<br />\n<br />\n<u>DB Host:</u> ".DB_HOST."<br />\n<u>DB Name:</u> ".DB_NAME."<br />\n<u>DB Prefix:</u> ".DB_PRFX."<br />\n<br />\n<u>Name:</u> ".$__sInfo['name']."<br />\n<u>Contact:</u> ".$__sInfo['contact']."<br />\n<u>Other:</u> ".$__sInfo['other']."<br />\n<br />\n<u>Summary:</u> ".$__sInfo['error']."<br />\n<u>Error:</u> (".$errno.") ".$errstr."<br />\n<u>Command:</u> ".preg_replace('/\n/','<br />',$__sInfo['command'])."<br />\n<u>Values:</u> ".$__sInfo['values']."<br />\n<u>File:</u> ".$errfile."<br />\n<u>Line:</u> ".$errline."<br />\n<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br />\n<br />\nVar Dump:<br />\n</p>\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre><br />\n</td>\n<td>&nbsp;</td>\n</tr>\n</table>\n</body>\n</html>");
	}
	if (isset($__sInfo['continue'])) {
#file_put_contents('debug.txt', "MEH - continue\n", FILE_APPEND);
 if ($__sInfo['continue'] === TRUE) {return true;} }	# continue script execution
#file_put_contents('debug.txt', "MEH - exit\n", FILE_APPEND);
	exit(1);
}


# How php handles exceptions thrown in code
# WARNING: if the script enters this function, the execution will stop no matter what!!! Use $gbl_info['prompt'] to display custom error message.
set_exception_handler("myExceptionHandler");
function myExceptionHandler($exception) {
#	file_put_contents('debug.txt', get_class($exception)."\n", FILE_APPEND);
#	file_put_contents('debug.txt', print_r($exception, true)."\n", FILE_APPEND);

	# https://www.php.net/manual/en/function.set-exception-handler.php
	# https://www.php.net/manual/en/class.error.php
	# https://www.php.net/manual/en/function.gettype.php
	# https://www.php.net/manual/en/function.is-a.php
	# https://www.php.net/manual/en/function.get-class.php
	if (get_class($exception) == 'Error' || get_class($exception) == 'Exception' || get_class($exception) == 'TypeError' || get_class($exception) == 'mysqli_sql_exception' || get_class($exception) == 'ArgumentCountError' || get_class($exception) == 'ParseError')
		{ myErrorHandler($exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine()); }
	else
		{ myErrorHandler($exception['code'], $exception['message'], $exception['file'], $exception['line']); }
}


# Handles fatal errors
register_shutdown_function('myShutdownHandler');
function myShutdownHandler() {
	$error = error_get_last();				# stores all the information related to the fatal error that just occurred
	if ($error !== NULL)					# ADDED 2021/05/12 - fixes an error after updating to php 7.4 from 7.2	https://stackoverflow.com/questions/277224/how-do-i-catch-a-php-fatal-e-error-error
		{ if ($error['type'] === E_ERROR) {myErrorHandler(E_ERROR, $error['message'], $error['file'], $error['line']);} }
}









# -- Common Functions --


function genRandom($length = 40)				# generates random string of (40) characters (e.g. login SID, commerce SID, etc)
	{ return substr(sha1(rand()), 0, $length); }		# also see http://stackoverflow.com/questions/853813/how-to-create-a-random-string-using-php




# Usage Syntax
#	delTree('some/directory');
function delTree($sDir) {
# Recursively delete a directory structure
# sDir		The directory to delete
	if (! file_exists($sDir)) { return false; }

	$files = array_diff(scandir($sDir), array('.','..'));	# didn't use 'glob' since 'scandir' sees hidden files
	foreach ($files as $file) {
		if (is_link("$sDir/$file")) {			# if the target is a symlink, then...		NOTE: this prevented some problems with the 'rmdir' call at the bottom
			@unlink("$sDir/$file");			#   delete the symlink
			continue;				#   continue to the next file or directory
		}
		(is_dir("$sDir/$file")) ? delTree("$sDir/$file") : @unlink("$sDir/$file");	# otherwise the target is a normal file or directory, so take the appropriate action
	}
	return @rmdir($sDir);
}




# Copy a file, or recursively copy a folder and its contents
# @author	Aidan Lister <aidan@php.net>
# @version	1.0.1
# NOTES:	http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
function xCopy($sSource, $sTarget, $nPermissions = 0755) {
# sSource	Source path to copy from
# sTarget	Target path to copy to
# nPermissions	New folder creation permissions
	if (is_link($sSource)) { return @symlink(readlink($sSource), $sTarget); }		# Check for symlinks
	if (is_file($sSource)) { return @copy($sSource, $sTarget); }				# Simple copy for a file
	if (!is_dir($sTarget)) { @mkdir($sTarget, $nPermissions); }				# Make target directory
	$dir = dir($sSource);

	while (false !== $entry = $dir->read()) {						# Loop through the folder
		if ($entry == '.' || $entry == '..') { continue; }				#   Skip pointers

		xCopy("$sSource/$entry", "$sTarget/$entry", $nPermissions);			#   Deep copy directories
	}

	$dir->close();										# Clean up
	return true;
}




function createThumbs($nWidth,$nHeight,$sImage,$sThumb) {
# used to create thumbnail images;	http://www.howtogeek.com/109369/how-to-quickly-resize-convert-modify-images-from-the-linux-terminal/
# used to create video thumbnails:	http://blog.amnuts.com/2007/06/22/create-a-random-thumbnail-of-a-video-file/
#					http://stackoverflow.com/questions/10240972/create-thumbnail-image-from-video-in-server-in-php
# nWidth	the desired width of the thumbnail
# nHeight	the desired height of the thumbnail
# sImage	the FQDN of the image/video to generate a thumb for
# sThumb	the FQDN of the thumbnail to generate
   $info = pathinfo($sImage);					# obtain file info

   switch(strtolower($info['extension'])) {			# load image and get image size
#	case '3gp':						# for videos...
	case 'flv':
	case 'm4v':
#	case 'mp4':
#	case 'mpeg':
#	case 'mpg':
	case 'ogv':						# HAS PROBLEMS
	case 'webm':
	case 'webmv':
#	case 'wmv':
#	   system("ffmpeg -ss 3600 -i {$sImage} -deinterlace -an -t 00:00:01 -r 1 -y -s 200x150 -vcodec mjpeg -f mjpeg {$sThumb} 2>&1");
#	   system("ffmpeg -an -y -itsoffset -1 -vframes 1 -vcodec mjpeg -f rawvideo -s 200x150 -i {$sImage} {$sThumb}");
	   $ret = `ffmpeg -itsoffset -1 -i {$sImage} -vcodec mjpeg -vframes 1 -an -f rawvideo -s {$nWidth}x{$nHeight} {$sThumb}`;
	   return true;
	   break;

	case 'jpeg':						# for images...
	case 'jpg':
#	   $img = imagecreatefromjpeg( "{$sImage}" );		# NOTE: replaced in favor of the 'identify' binary call below since I was having problems with larger image sizes
#	   break;
	case 'png':
#	   $img = imagecreatefrompng( "{$sImage}" );
#	   break;
	case 'gif':
#	   $img = imagecreatefromgif( "{$sImage}" );
	   $w = `identify -format "%[fx:w]" {$sImage}`;		# obtain the size values of the picture
	   $h = `identify -format "%[fx:h]" {$sImage}`;

	   $w = trim($w);					# remove any leading & trailing whitespace for proper calculations below
	   $h = trim($h);
	   break;

	default:						# skip all the other image/video types
#	   continue;
	   break;
   }

#   $nWidth = imagesx($img);					# obtain the sizes of the image
#   $nHeight = imagesy($img);

   # NOTE: adding an '!' to the end of the -resize value will force that size instead of preserving the aspect ratio

   if ($w == $h && $w > $nWidth) {				# if the image is "square", then reduce to the smallest size needed
	if ($nWidth < $nHeight)
	   { system("convert {$sImage} -resize ".$nWidth."x".$nWidth." {$sThumb}"); }
	else
	   { system("convert {$sImage} -resize ".$nHeight."x".$nHeight." {$sThumb}"); }

   } else if ($w > $h && $w > $nWidth) {			# if we have a greater width, reduce keeping the aspect ratio
	system("convert {$sImage} -resize ".$nWidth." {$sThumb}");

   } else if ($h > $w && $h > $nHeight) {			# if we have a greater height, reduce to keep the aspect ratio
	system("convert {$sImage} -resize x".$nHeight." {$sThumb}");

   } else if ($w == $nWidth) {
	system("convert {$sImage} -resize x".$nHeight." {$sThumb}");

   } else if ($h == $nHeight) {
	system("convert {$sImage} -resize ".$nWidth." {$sThumb}");

   } else {							# otherwise, the picture is smaller than the necessary size constraints, so just copy the image over
	copy("{$sImage}", "{$sThumb}");
   }
}

?>
