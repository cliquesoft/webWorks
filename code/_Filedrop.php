<?php
# _filedrop.php	the file upload handler
#
# created	2015/11/13 by Dave Henderson (support@cliquesoft.org)
# updated	2021/05/03 by Dave Henderson (support@cliquesoft.org)
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
#	if large files are unable to upload, you have to increase the post_max_size and the upload_max_filesize
#	-----
#	FileDrop v2 - server-side upload handler sample in public domain
#	http://filedropjs.org
#
#	AJAX upload provides raw file data as POST input while IFrame is a POST request
#	with $_FILES member set.
#
#	Result is either output as HTML with JavaScript code to invoke the callback
#	(like JSONP) or in plain text if none is given (it's usually absent on AJAX).


// Constant Definitions
define("MODULE",'_filedrop');					# the name of this module; NOTE: MUST be the same in all php files in this MODULE
define("SCRIPT",basename($_SERVER['SCRIPT_NAME']));		# the name of this script (for tracing bugs and automated messages)

// Variable Definitions
$_SERVER["REMOTE_ADDR"] = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '127.0.0.1';	# https://stackoverflow.com/questions/52142570/notice-undefined-index-remote-addr

// Module Requirements						  NOTE: MUST come below Module Constant Definitions
if (file_exists('../../sqlaccess')) { require_once('../../sqlaccess'); }
# UPDATED 2025/03/11
#require_once('../data/config.php');
#if (file_exists('../data/config.'.strtolower(MODULE).'.php')) { require_once('../data/config.'.strtolower(MODULE).'.php'); }
require_once('../data/_modules/ApplicationSettings/config.php');
require_once('_Project.php');
require_once('_Contact.php');

// Start or resume the PHP session				  NOTE: gains access to $_SESSION variables in this script
#session_start();



// connect the to DB for writing below
#if (! connect2DB(DBHOST,DBNAME,DBUSER,DBPASS)) { exit(); }

// process the user account before continuing...
#if (USERS == '')				# IF we need to access the native webBooks DB table, then...
#	{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Accounts','username','s|'.$_GET['username'],'sid','s|'.$_GET['SID'])) {exit();} }
#else						# OTHERWISE, we have mapped DB values, so pull the values from that table
#	{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_GET['username'],SES,'s|'.$_GET['SID'])) {exit();} }

# DEBUG
#file_put_contents('debug.txt', "POST:\n".print_r($_POST, true)."\nGET:\n".print_r($_GET, true)."\n", FILE_APPEND);


header('Content-Type: text/plain; charset=utf-8');


// If an error causes output to be generated before headers are sent - catch it.
ob_start();

// Callback name is passed if upload happens via iframe, not AJAX (FileAPI).
$callback = &$_REQUEST['fd-callback'];

// check that the path even exists (e.g. if this is a new project being created)
if (! file_exists("../".$_GET['path'].$_GET['ext']))
	{ mkdir("../".$_GET['path'].$_GET['ext'], 0775, true); }
$path = "../".$_GET['path'].$_GET['ext'];




// ***** CUSTOM CODE FOR THIS PROJECT *****

## change into the directory storing the temp uploads								REMOVED 2025/05/27 - this was for filedrop.infinciti.com
#$gbl_errs['error'] = "The 'data' directory does not exist.";
#$gbl_info['command'] = "chdir('../data')";
#$gbl_info['values'] = 'None';
#chdir('../data');

## now check if the IP address is already associated with a file upload
#$gbl_errs['error'] = "The 'temp' directory does not exist.";
#$gbl_info['command'] = "opendir('temp')";
#$gbl_info['values'] = 'None';
#$Dir = opendir('temp');
#while ( ($dir = readdir($Dir)) !== false ) {
#	if ($dir == '.' || $dir == '..' || ! is_dir('temp/'.$dir) || strlen($dir) > 10) { continue; }		# NOTE: we skip directories with a name longer than 10 characters because it will have a '.flag' extension
#	if (! file_exists('temp/'.$dir.'/.meta')) { continue; }							# if there is no .meta file for some reason, then no need to do any processing...
#
#	$ip = file_get_contents('temp/'.$dir.'/.meta');
#	if ($ip == $_SERVER['REMOTE_ADDR']) {
#		echo "ERROR: You can only upload one file every 12 hours. Please try again when that limit has expired.";
#		exit();
#	}
#}

// ****** END OF PROJECT CUSTOM CODE ******




// Upload data can be POST'ed as raw form data or uploaded via <iframe> and <form>
// using regular multipart/form-data enctype (which is handled by PHP $_FILES).
if (! empty($_FILES['fd-file']) and is_uploaded_file($_FILES['fd-file']['tmp_name'])) {	// Regular multipart/form-data upload.
	$name = $_FILES['fd-file']['name'];						//   stores the original filename
	$NAME = $name;									//   store the original filename for $output below
	if ($_GET['rename'] != '') {							//   check if we need to rename the uploaded to file to a unique name
		if ($_GET['rename'] == 'EPOCH') {					//     using the current epoch (while retaining the original file extension)
			while (1 == 1) {						//       create an infinite loop to make sure a file doesn't already exist with the new name
				$name = gmdate("YmdHis",time()) . substr($name, strrpos($name, '.'));
				if (! file_exists($path.$name) || $_GET['overwrite']) { break; }   // break if none are found -OR- it's ok to overwrite existing files
				sleep(1);						//         sleep for 1 second and try again otherwise
			}
		} else if ($_GET['rename'] == 'DATE') {					//     using the current date (while retaining the original file extension)
			$name = gmdate("Ymd",time()) . substr($name, strrpos($name, '.'));
			if (file_exists($path.$name) && ! $_GET['overwrite']) {
				echo "ERROR: A file already exists on the server with that name.";
				unlink($path.$NAME);					//       now delete the file from the server
				exit();
			}
		} else if ($_GET['rename'] == 'TIME') {					//     using the current time (while retaining the original file extension)
			$name = gmdate("His",time()) . substr($name, strrpos($name, '.'));
			if (file_exists($path.$name) && ! $_GET['overwrite']) {
				echo "ERROR: A file already exists on the server with that name.";
				unlink($path.$NAME);
				exit();
			}
		} else {								//     otherwise rename the file to the value passed (while retaining the original file extension)
			$name = $_GET['rename'] . substr($name, strrpos($name, '.'));
			if (file_exists($path.$name) && ! $_GET['overwrite']) {
				echo "ERROR: A file already exists on the server with that name.";
				unlink($path.$NAME);
				exit();
			}
		}
	}

	if ($_GET['overwrite'] == 2)							//   if we need to erase any existing file, regardless of extension, then do so now...
		{ foreach (glob($path.'avatar.*') as $file) {unlink($file);} }

	$data = file_get_contents($_FILES['fd-file']['tmp_name']);			//   store the uploaded file data
	file_put_contents($path.$name, $data);						//   write it to file

} else {										// Raw POST data.
	$name = urldecode(@$_SERVER['HTTP_X_FILE_NAME']);				//   stores the original filename
	$NAME = $name;									//   store the original filename for $output below
	if ($_GET['rename'] != '') {							//   check if we need to rename the uploaded to file to a unique name
		if ($_GET['rename'] == 'EPOCH') {					//     using the current epoch (while retaining the original file extension)
			while (1 == 1) {						//       create an infinite loop to make sure a file doesn't already exist with the new name
				$name = gmdate("YmdHis",time()) . substr($name, strrpos($name, '.'));
				if (! file_exists($path.$name) || $_GET['overwrite']) { break; }   // break if none are found -OR- it's ok to overwrite existing files
				sleep(1);						//         sleep for 1 second and try again otherwise
			}
		} else if ($_GET['rename'] == 'DATE') {					//     using the current date (while retaining the original file extension)
			$name = gmdate("Ymd",time()) . substr($name, strrpos($name, '.'));
			if (file_exists($path.$name) && ! $_GET['overwrite']) {
				echo "ERROR: A file already exists on the server with that name.";
				unlink($path.$NAME);					//       now delete the file from the server
				exit();
			}
		} else if ($_GET['rename'] == 'TIME') {					//     using the current time (while retaining the original file extension)
			$name = gmdate("His",time()) . substr($name, strrpos($name, '.'));
			if (file_exists($path.$name) && ! $_GET['overwrite']) {
				echo "ERROR: A file already exists on the server with that name.";
				unlink($path.$NAME);
				exit();
			}
		} else {								//     otherwise rename the file to the value passed (while retaining the original file extension)
			$name = $_GET['rename'] . substr($name, strrpos($name, '.'));
			if (file_exists($path.$name) && ! $_GET['overwrite']) {
				echo "ERROR: A file already exists on the server with that name.";
				unlink($path.$NAME);
				exit();
			}
		}
	}

	if ($_GET['overwrite'] == 2)							//   if we need to erase any existing file, regardless of extension, then do so now...
		{ foreach (glob($path.'avatar.*') as $file) {unlink($file);} }

	$hSource = fopen('php://input', 'r');						//   store the uploaded file data		http://stackoverflow.com/questions/9595616/php-using-fwrite-and-fread-with-input-stream
	$hDest = fopen($path.$name, 'w');
	while (!feof($hSource)) {
		$chunk = fread($hSource, 1024);						//   read in 1k chunks (as a rule of thumb keep it to 1/4 of php memory_limit) so large files can be uploaded
		fwrite($hDest, $chunk);
	}
	fclose($hSource);
	fclose($hDest);
}


// Check if the file is within any limits passed
if ($_GET['limit'] > 0 && $_GET['limit'] < filesize($path.$name)) {
	header('Content-Type: text/plain; charset=utf-8');
	echo "ERROR: The size of the \"".$name."\" file is larger than the limit allowed.";	// alert the user that the file could not be stored
	unlink($path.$name);								// now delete the file from the server
	exit();
}


// If we need to create a thumbnail, then do so now!
if ($_GET['thumb'] != '') { 
	$dim = explode('x', $_GET['thumb']);
	createThumbs($dim[0], $dim[1], $path.$name, $path.substr($name, 0, strrpos($name, '.')).'.thumb'.substr($name, strrpos($name, '.')));
}




// obtain the crc32 info for the file
$hash = unpack('N', pack('H*', hash_file('crc32b', $path.$name)));
$crc32 = $hash[1];

// Output message for the upload.
$output = sprintf("Original name: %s\nStored name: %s\nSize: %s\nCRC32B: %08X\nMD5: %s\nSHA256: %s", $NAME, $name, filesize($path.$name), $crc32, hash_file('md5', $path.$name), hash_file('sha256', $path.$name));

// In FileDrop sample this demonstrates the passing of custom ?query variables along with an AJAX/iframe upload.
//$opt = &$_REQUEST['upload_option'];
//isset($opt) and $output .= "\nReceived upload_option with value $opt";

if ($callback) {
	// Escape output so it remains valid when inserted into a JS 'string'.
	$output = addcslashes($output, "\\\"\0..\x1F");

	// Callback function given - the caller loads response into a hidden <iframe> so
	// it expects it to be a valid HTML calling this callback function.
	header('Content-Type: text/html; charset=utf-8');

	// Finally output the HTML with an embedded JavaScript to call the function giving
	// it our message(in your app it doesn't have to be a string) as the first parameter.
	echo '<!DOCTYPE html><html><head></head><body><script type="text/javascript">', "try{window.top.$callback(\"$output\")}catch(e){}</script></body></html>";

} else {
	// Caller reads data with XMLHttpRequest so we can output it raw. Real apps would
	// usually pass and read a JSON object instead of plan text.
	header('Content-Type: text/plain; charset=utf-8');
	echo $output;
}
