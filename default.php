<?php
# default.php	the "boot strapper" for this project.
#
# created	2012/05/15 by Dave Henderson (dhenderson@cliquesoft.org or support@cliquesoft.org)
# updated	2025/10/01 by Dave Henderson (dhenderson@cliquesoft.org or support@cliquesoft.org)
#
# Unless a valid Cliquesoft Proprietary License (CPLv1) has been purchased
# for this device, this software is licensed under the Cliquesoft Public
# License (CPLv2) as found on the Cliquesoft website at www.cliquesoft.org
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.
#
# NOTES
#  $line =~ s/&/&amp;/g;		we may need to add string modification for XML to be transmitted correctly
#
#  $filepath = "/tmpphp/dmbigmail.file";		how to securely look for the file - LEFT OFF
#  if (!file_exists($filepath) || !is_file($filepath)) {
#	echo "$filepath not found or it is not a file."; exit; //return; //die();
#  }
#  if ($file_handle = fopen($filepath, "r")) {
#  ...


# Constant Definitions
define("MODULE",'boot strapper');				# the name of this "module"
define("SCRIPT",basename($_SERVER['SCRIPT_NAME']));		# the name of this script (for tracing bugs and automated messages)

# Module Requirements
if (file_exists('data/_modules/ApplicationSettings/config.php'))
	{ require_once('data/_modules/ApplicationSettings/config.php'); }

#https://stackoverflow.com/questions/49547/how-do-we-control-web-page-caching-across-all-browsers
#header("Cache-Control: no-cache, no-store, must-revalidate");					# HTTP 1.1
#header("Pragma: no-cache");									# HTTP 1.0
#header("Expires: 0");										# Proxies




if (isset($_COOKIE['username'])) { $user=$_COOKIE['username']; } else { $user='guest'; }
header('Content-Type: text/html; charset=utf-8');


if  (@file_exists("look/default/Setup.html"))							# IF the setup file exists (deleted upon install), then start it!			all
	{ $page = @fopen("look/default/Setup.html", "r"); }					# WARNING: this MUST come first so that the setup must complete before ANY page can be shown
else if ($_GET['p'] != '' && @file_exists("home/".$user."/look/".$_GET['p'].".html"))		# IF we have a specific file to display (and it exists), then display it!		all
	{ $page = @fopen("home/".$user."/look/".$_GET['p'].".html", "r"); }
else if  (@file_exists("home/".$user."/look/default.html"))					# IF this is being called without any specific page, load the default...		websites
	{ $page = @fopen("home/".$user."/look/default.html", "r"); }
else if  (@file_exists("home/".$user."/look/Application.html"))					# IF this is an application, load the interface...					applications
	{ $page = @fopen("home/".$user."/look/Application.html", "r"); }
else {												# LASTLY, this will display an error to the user if none of the files are found...	error
	echo "<!DOCTYPE html>
<head>
	<title>webBooks [ERROR]</title>
	<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
	<link rel='icon' type='image/x-icon' href='look/default/favicon.ico'>
	<link rel='shortcut icon' type='image/x-icon' href='look/default/favicon.ico'>
	<!-- GLOBAL -->
	<link rel='stylesheet' type='text/css' href='look/default/_global.css?v=1.0'>
	<!-- MODULE -->
	<link rel='stylesheet' type='text/css' href='look/default/default.css?v=1.0'>
	<!-- GLOBAL -->
	<script language='javascript' src='code/_ajax.js'></script>
	<!-- MODULE -->
	<script language='javascript' src='code/default.js'></script>
</head>
<body onLoad='formLoad();' onResize='formLayout();'>
	<div class='page'>
		<h1 class='fail'>Oh No!</h1>
		<p class='center'>
			It looks like the requested page does not exist! You will need to
			contact your network administrator, or tech support, to resolve
			this issue.
		</p>
	</div>
</body>
</html>";
	exit();
}


while ($LINE = fgets($page)) {									# now display the appropriate page
	# Psuedo snippets for dynamic content using shell-style-variables in the .html file
	if (defined('PROJECT'))
		{ if (strpos($LINE, '${PROJECT}') !== false) {$LINE = str_replace('${PROJECT}', PROJECT, $LINE);} }
	if (strpos($LINE, '${UN}') !== false) { $LINE = str_replace('${UN}', $user, $LINE); }
	if (strpos($LINE, '${WIKI}') !== false) { $LINE = str_replace('${WIKI}', WIKI, $LINE); }
	echo "$LINE";
}
?>
