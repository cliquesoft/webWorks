// Setup.js	Used to store all the javascript code associated with
//		any particular "issue" associated with a project.
//
// Created	2015/03/04 by Dave Henderson (support@cliquesoft.org)
// Updated	2025/06/26 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.




var _oSetup;						// used with _ajax.js
var _nSetupPidTimer;					// used with setInterval()


function Setup(sAction) {
	var HTML = "";

	switch(sAction) {
		case "install":
			document.getElementById('divPopup').style.display = 'none';		// make sure the popup isn't showing
			document.getElementById('divOverlay').style.display = 'none';

			if (! document.getElementById('bExistingConfigs').checked) {		// if the configuration files are NOT present from a prior install, then check the proper values have been filled out!
				if (document.getElementById('sAlertName').value == '') { alert("Before the software can be installed, you must supply a 'No Reply Name' value."); return false; }
				if (document.getElementById('sAlertEmail').value == '') { alert("Before the software can be installed, you must supply a 'No Reply Email' value."); return false; }
				if (document.getElementById('sSupportName').value == '') { alert("Before the software can be installed, you must supply a 'Support Name' value."); return false; }
				if (document.getElementById('sSupportEmail').value == '') { alert("Before the software can be installed, you must supply a 'Support Email' value."); return false; }
				if (document.getElementById('sSecurityName').value == '') { alert("Before the software can be installed, you must supply a 'Security Name' value."); return false; }
				if (document.getElementById('sSecurityEmail').value == '') { alert("Before the software can be installed, you must supply a 'Security Email' value."); return false; }
				if (document.getElementById('sAdminPassword').value == '') { alert("Before the software can be installed, you must supply an 'Admin Password' value."); return false; }

				if (document.getElementById('sSQLServer').value == '') { alert("Before the software can be installed, you must supply a 'SQL Server' value."); return false; }
				if (document.getElementById('sDatabaseName').value == '') { alert("Before the software can be installed, you must supply a 'Database Name' value."); return false; }
				if (document.getElementById('sROUsername').value == '') { alert("Before the software can be installed, you must supply a 'R/O Username' value."); return false; }
				if (document.getElementById('sROPassword').value == '') { alert("Before the software can be installed, you must supply a 'R/O Password' value."); return false; }
				if (document.getElementById('sRWUsername').value == '') { alert("Before the software can be installed, you must supply a 'R/W Username' value."); return false; }
				if (document.getElementById('sRWPassword').value == '') { alert("Before the software can be installed, you must supply a 'R/W Password' value."); return false; }

				if (document.getElementById('sTableName').value == '' && document.getElementById('sUIDColumn').value != '') { alert("You must specify a \"Mappings\" value in order for the mapped \"ID Column\" value to work."); return false; }
				if (document.getElementById('sTableName').value == '' && document.getElementById('sUsernameColumn').value != '') { alert("You must specify a \"Mappings\" value in order for the mapped \"Username Column\" value to work."); return false; }
				if (document.getElementById('sTableName').value == '' && document.getElementById('sPasswordColumn').value != '') { alert("You must specify a \"Mappings\" value in order for the mapped \"Password Column\" value to work."); return false; }
			}

			// store the overlay to indicate we are processing the install...
			HTML =	"<div id='divPopupClose' class='disabled' onClick=''>&times;</div>" +
				"<h3>&nbsp;Installing...&nbsp;</h3>" +
				"<div class='divBody divBodyFull'>" +
				"	<ul>" +
				"		<li class='fleft'><img src='imgs/default/loading.gif' />" +
				"		<li class='justify'>Please be patient while the software is being installed..." + 
				"	</ul>" +
				"</div>";

			document.getElementById('divPopup').innerHTML = HTML;
			document.getElementById('divOverlay').style.display = 'block';
			document.getElementById('divPopup').className=document.getElementById('divPopup').className += ' PopupMin';
			document.getElementById('divPopup').style.display = 'block';

			// NOTE: we have to use the below 'location.href' string since the envars file have NOT been created yet!
			Ajax('Call',_oSetup,"code/Setup.php",'A='+sAction+'&T=software','oInstall',"Setup('s_"+sAction+"');",null,null,null,'formInstall');
			break;
		case "s_install":
			// store default message to user
			HTML =	"<div id='divPopupClose'class='disabled'  onClick=''>&times;</div>" +
				"<h3>&nbsp;Success!&nbsp;</h3>" +
				"<div class='divBody divBodyFull'>" +
				"	<ul>" +
				"		<li class='fleft'><img src='imgs/default/email_info.png' />" +
				"		<li class='justify'>Your software has been installed successfully! You'll be redirected there in seconds..." +
				"		<li class='center bold' id='liCounter'>5" +
				"	</ul>" +
				"</div>";
			if (DATA['hosted'] != 'false' && DATA['hosted'] != '0') {
				HTML =	"<div id='divPopupClose'class='disabled'  onClick=''>&times;</div>" +
					"<h3>&nbsp;Success!&nbsp;</h3>" +
					"<div class='divBody divBodyFull'>" +
					"	<ul>" +
					"		<li class='fleft'><img src='imgs/default/email_info.png' />" +
					"		<li class='justify'>Your software has been installed successfully!  Please check your email for the login information to access your account. You'll be redirected there in seconds..." +
					"		<li class='center bold' id='liCounter'>5" +
					"	</ul>" +
					"</div>";
			}

			document.getElementById('divPopup').innerHTML = HTML;
			_nSetupPidTimer = setInterval(function(){
				var e = document.getElementById('liCounter');
				var i = parseInt(e.innerHTML) - 1;
				e.innerHTML = i;
				if (i == 1) {
					clearInterval(_nSetupPidTimer);
					// if we're using a hosted service, then go to the returned URI, otherwise reload the page (had to add this due to the page just caching Setup.html)
					if (window.location.href != DATA['URI']) { window.location.href = DATA['URI']; } else { window.location.reload(); }
				}
			}, 1000);
			return true;
			break;
	}
}

