// ApplicationSettings.js
//
// Created	2014/01/28 by Dave Henderson (support@cliquesoft.org)
// Updated	2025/10/01 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.




// GLOBALS

var _oApplicationSettings;				// used for this modules' AJAX communication
var _nApplicationSettingsTimeout = 0;			// used to acknowledge the module's html/css has loaded so any preliminary initialization javascript calls can start
var _sApplicationSettingsUriSocial = '';		// used to indicate it needs to be refreshed if changed




// -- Session API --

function ApplicationSettings(sAction,Callback) {

	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var mCallback = null;											// the callback to perform
	var AT = sAction.replace(/([a-z])([A-Z])/g, '$1 $2').toLowerCase().split(' ');				// https://stackoverflow.com/questions/18379254/regex-to-split-camel-case
	var HTML = "";

	switch(sAction) {
		case "LoadContact":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		// NOTE: none of the other calls have parameters other than optional "oDisable", so there's no need to add them here
	}

	// sanity checks
	if (typeof mRequirements == 'boolean' && ! mRequirements) {						// first check if the mandatory parameter count is met
		alert("ERROR: ApplicationSettings('"+sAction+"') was called without meeting a sufficent number of parameters.");
		return false;
	} else if (typeof mRequirements == 'number') {								// second check if those mandatory parameters have an actual value
		for (let i=0; i<mRequirements; i++) {
			if (arguments[i] == '') {
				alert("ERROR: ApplicationSettings('"+sAction+"') was called with mandatory parameter #"+i+" being blank.");
				return false;
			}
		}
	}

	// lets perform some work!
	switch(sAction) {


		// --- STARTUP ACTIONS ---


		// Initializes the UI
		case "Initialize":						// WARNING: the 'A' and 'T' values are capitalized!
			if (! document.getElementById('DB_HOST_ApplicationSettings')) {	// if the HTML/css side hasn't loaded yet, then...
				if (_nApplicationSettingsTimeout == 30) {	//   check if we've met the 30 second timeout threshold
					_nApplicationSettingsTimeout == 0;	//   reset the value
					Project(_sProjectUI,'fail',"The HTML file for the module could not be loaded. Please contact support for assistance.");	//   relay the error to the end user
					return false;				//   exit since we can't use this module under these conditions
				}

				// if we've made it here, the modules' html hasn't yet loaded, so let wait a second and try again
				_nApplicationSettingsTimeout++;			// increase the timeout counter by one for each failed attempt
				setTimeout(ApplicationSettings(sAction,Callback),1000);
				return false;					// in any case, exit this function to prevent JS problems!
			}

// UPDATED 2025/05/14
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A='+sAction+'&T=Module&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')),'','','','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'popup');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'popup');});
// UPDATED 2025/07/09
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A='+sAction+'&T=Module&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID')),'','','','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'popup');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'popup');});
			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+sAction+'!,>Module<,(sUsername),(sSessionID)','',function(){ApplicationSettings('s_'+sAction,Callback);});
			break;
		case "s_Initialize":
			// 'General' Tab
			var c = XML.getElementsByTagName("contact").item(0);
			document.getElementById('sAlertsName_ApplicationSettings').value = c.getAttribute("sAlertsName");
			document.getElementById('sAlertsEmail_ApplicationSettings').value = c.getAttribute("sAlertsEmail");
			document.getElementById('sSupportName_ApplicationSettings').value = c.getAttribute("sSupportName");
			document.getElementById('sSupportEmail_ApplicationSettings').value = c.getAttribute("sSupportEmail");
			document.getElementById('sSecurityName_ApplicationSettings').value = c.getAttribute("sSecurityName");
			document.getElementById('sSecurityEmail_ApplicationSettings').value = c.getAttribute("sSecurityEmail");

			var o = XML.getElementsByTagName("operation").item(0);
// UPDATED 2025/07/11
//			selListbox('sInterface_ApplicationSettings',o.getAttribute("sInterface"));
//			selListbox('bHostedService_ApplicationSettings',o.getAttribute("bHostedService"));
			Listbox('SelectOption','sInterface_ApplicationSettings',o.getAttribute("sInterface"));
			Listbox('SelectOption','bHostedService_ApplicationSettings',o.getAttribute("bHostedService"));

			var s = XML.getElementsByTagName("security").item(0);
// UPDATED 2025/07/11
//			selListbox('bUseCaptchas_ApplicationSettings',s.getAttribute("bUseCaptchas"));
			Listbox('SelectOption','bUseCaptchas_ApplicationSettings',s.getAttribute("bUseCaptchas"));
			document.getElementById('nFailedAuth_ApplicationSettings').value = s.getAttribute("nFailedAuth");
			document.getElementById('nTimeout_ApplicationSettings').value = s.getAttribute("nTimeout");

			var m = XML.getElementsByTagName("maintenance").item(0);
// UPDATED 2025/07/11
//			selListbox('bMaintenance_ApplicationSettings',m.getAttribute("bMaintenance"));
			Listbox('SelectOption','bMaintenance_ApplicationSettings',m.getAttribute("bMaintenance"));
			document.getElementById('sMaintenance_ApplicationSettings').value = m.getAttribute("sMaintenance");

			var uri = XML.getElementsByTagName("uri").item(0);
			document.getElementById('sUriProject_ApplicationSettings').value = uri.getAttribute("sUriProject");
			document.getElementById('sUriPayment_ApplicationSettings').value = uri.getAttribute("sUriPayment");
			document.getElementById('sUriSocial_ApplicationSettings').value = uri.getAttribute("sUriSocial");

			var l = XML.getElementsByTagName("logs").item(0);
			document.getElementById('sLogEmail_ApplicationSettings').value = l.getAttribute("sLogEmail");
			document.getElementById('sLogScript_ApplicationSettings').value = l.getAttribute("sLogScript");
			document.getElementById('sLogModule_ApplicationSettings').value = l.getAttribute("sLogModule");
			document.getElementById('sLogProject_ApplicationSettings').value = l.getAttribute("sLogProject");

			var d = XML.getElementsByTagName("dirs").item(0);
			document.getElementById('sDirCron_ApplicationSettings').value = d.getAttribute("sDirCron");
			document.getElementById('sDirData_ApplicationSettings').value = d.getAttribute("sDirData");
			document.getElementById('sDirLogs_ApplicationSettings').value = d.getAttribute("sDirLogs");
			document.getElementById('sDirMail_ApplicationSettings').value = d.getAttribute("sDirMail");
			document.getElementById('sDirTemp_ApplicationSettings').value = d.getAttribute("sDirTemp");
			document.getElementById('sDirVrfy_ApplicationSettings').value = d.getAttribute("sDirVrfy");

			var db = XML.getElementsByTagName("database").item(0);
			document.getElementById('DB_HOST_ApplicationSettings').value = db.getAttribute("DB_HOST");
			document.getElementById('DB_NAME_ApplicationSettings').value = db.getAttribute("DB_NAME");
			document.getElementById('DB_ROUN_ApplicationSettings').value = db.getAttribute("DB_ROUN");
			document.getElementById('DB_ROPW_ApplicationSettings').value = db.getAttribute("DB_ROPW");
			document.getElementById('DB_RWUN_ApplicationSettings').value = db.getAttribute("DB_RWUN");
			document.getElementById('DB_RWPW_ApplicationSettings').value = db.getAttribute("DB_RWPW");
			document.getElementById('DB_PRFX_ApplicationSettings').value = db.getAttribute("DB_PRFX");

			var a = XML.getElementsByTagName("authentication").item(0);
			document.getElementById('AUTH_TB_ApplicationSettings').value = a.getAttribute("AUTH_TB");
			document.getElementById('AUTH_ID_ApplicationSettings').value = a.getAttribute("AUTH_ID");
			document.getElementById('AUTH_UN_ApplicationSettings').value = a.getAttribute("AUTH_UN");
			document.getElementById('AUTH_PW_ApplicationSettings').value = a.getAttribute("AUTH_PW");

			var M = XML.getElementsByTagName("module").item(0);
// UPDATED 2025/07/11
//			selListbox('sUpdates_ApplicationSettings',M.getAttribute("sUpdates"));
//			selListbox('sInstall_ApplicationSettings',M.getAttribute("sInstall"));
			Listbox('SelectOption','sUpdates_ApplicationSettings',M.getAttribute("sUpdates"));
			Listbox('SelectOption','sInstall_ApplicationSettings',M.getAttribute("sInstall"));

			_sApplicationSettingsUriSocial = uri.getAttribute("sUriSocial");	// store the URI so if it gets changed, we can update the iFrame src value (see "case: 'save'" below)



			// 'Modules' Tab
			var i = XML.getElementsByTagName("installed");
			for (I=0; I<i.length; I++)				// add all the modules currently installed to the last listbox
// UPDATED 2025/05/29
//				{ Add2List('nInstalledList_ApplicationSettings', i[I].getAttribute('id'), i[I].getAttribute('name'), 1, 1, 0); }
				{ Listbox('AddOption','nInstalledList_ApplicationSettings',i[I].getAttribute('id'),i[I].getAttribute('name'),'','',false,false,true); }

			var u = XML.getElementsByTagName("update");
			for (var I=0; I<u.length; I++)
// UPDATED 2025/05/29
//				{ Add2List('nUpdatesList_ApplicationSettings', u[I].getAttribute('module'), u[I].getAttribute('module').slice(0,-4), 1, 1, 0); }
				{ Listbox('AddOption','sUpdatesList_ApplicationSettings',u[I].getAttribute('module'),u[I].getAttribute('module').slice(0,-4),'','',false,false,true); }

			// initialization of the FileDrop object
// UPDATED 2025/07/09
//			initFileDrop('divModuleDrop_ApplicationSettings','','','temp/','','',0,'',0,"ApplicationSettings('InstallModule','','FILENAME')");
			Filedrop('Init','divModuleDrop_ApplicationSettings','temp/',null,null,null,null,null,null,null,null,null,"ApplicationSettings('InstallModule','','FILENAME')");



			// 'Groups' Tab
			var g = XML.getElementsByTagName("group");
			for (I=0; I<g.length; I++) {
				// add each group
// UPDATED 2025/05/29
//				Add2List('nGroupList_ApplicationSettings', g[I].getAttribute('id'), g[I].getAttribute('name').replace('&amp;', '&'), 1, 1, 0);
				Listbox('AddOption','nGroupList_ApplicationSettings',g[I].getAttribute('id'),g[I].getAttribute('name').replace('&amp;', '&'),'','',false,false,true);
// REMOVED 2025/03/04 - just call the ApplicationSettings('group') function
//				document.getElementById('sGroupName_ApplicationSettings').value = g[0].getAttribute('name').replace('&amp;', '&');
//				document.getElementById('sGroupIcon_ApplicationSettings').value = g[0].getAttribute('icon');
//
//				// add the modules of the first group ONLY to the list
//				if (I == 0) {
//					m = g[I].getElementsByTagName("module");
//					for (var j=0; j<m.length; j++)
//						{ Add2List('nIncludedList_ApplicationSettings', m[j].getAttribute('id'), m[j].getAttribute('name'), 1, 1, 0); }
//				}
			}
//			if (document.getElementById('nGroupList_ApplicationSettings').options.length >= 0) { document.getElementById('nGroupList_ApplicationSettings').options.selectedIndex = 0; }
			document.getElementById('nGroupList_ApplicationSettings').options.selectedIndex = -1;
//			ApplicationSettings('group');

			var i = XML.getElementsByTagName("installed");
			for (I=0; I<i.length; I++)				// add all the modules currently installed to the last listbox
// UPDATED 2025/05/29
//			   { Add2List('nInstalledListing_ApplicationSettings', i[I].getAttribute('id'), i[I].getAttribute('name'), 1, 1, 0); }
			   { Listbox('AddOption','nInstalledListing_ApplicationSettings',i[I].getAttribute('id'),i[I].getAttribute('name'),'','',false,false,true); }




			// 'Logs' Tab
			ApplicationSettings('LoadLogs');




			// 'Hosted' Tab
// UPDATED 2025/05/14
//			if (getCookie('bHostedService') == 'true') {
			if (Cookie('Obtain','bHostedService') == 'true') {
				document.getElementById('liHosted_ApplicationSettings').style.visibility = 'visible';
				document.getElementById('sAccessURI_ApplicationSettings').value = s.getAttribute('accessURI');
				document.getElementById('nRequiredLogins_ApplicationSettings').value = s.getAttribute('logins');
// UPDATED 2025/07/11
//				selListbox('nTechSupport_ApplicationSettings',s.getAttribute("support"));
				Listbox('SelectOption','nTechSupport_ApplicationSettings',s.getAttribute("support"));
				document.getElementById('nBalance_ApplicationSettings').value = addChange(s.getAttribute('balance'), 2);
				document.getElementById('custom').value = s.getAttribute('DB_PRFX');
				calcCharges_SystemConfiguration();
			}


			// Make UI adjustments now that everything's loaded
			// 'General' Tab
			document.getElementById('objDataExport_ApplicationSettings').className=document.getElementById('objDataExport_ApplicationSettings').className.replace(/ disabled/g,'');
			document.getElementById('objDataExport_ApplicationSettings').disabled = false;
			document.getElementById('objDataImport_ApplicationSettings').className=document.getElementById('objDataImport_ApplicationSettings').className.replace(/ disabled/g,'');
			document.getElementById('objDataImport_ApplicationSettings').disabled = false;
			document.getElementById('objDataArchive_ApplicationSettings').className=document.getElementById('objDataArchive_ApplicationSettings').className.replace(/ disabled/g,'');
			document.getElementById('objDataArchive_ApplicationSettings').disabled = false;
			// 'Modules' Tab
			document.getElementById('objDownloadUpdates_ApplicationSettings').className=document.getElementById('objDownloadUpdates_ApplicationSettings').className.replace(/ disabled/g,'');
			document.getElementById('objDownloadUpdates_ApplicationSettings').disabled = false;
			document.getElementById('objInstallUpdates_ApplicationSettings').className=document.getElementById('objInstallUpdates_ApplicationSettings').className.replace(/ disabled/g,'');
			document.getElementById('objInstallUpdates_ApplicationSettings').disabled = false;
			document.getElementById('sUpdatesList_ApplicationSettings').className=document.getElementById('sUpdatesList_ApplicationSettings').className.replace(/ disabled/g,'');
			document.getElementById('sUpdatesList_ApplicationSettings').disabled = false;
			document.getElementById('objInstalledDel_ApplicationSettings').className=document.getElementById('objInstalledDel_ApplicationSettings').className.replace(/ disabled/g,'');
			document.getElementById('objInstalledDel_ApplicationSettings').disabled = false;
			// 'Groups' Tab
			document.getElementById('objGroupClear_ApplicationSettings').className=document.getElementById('objGroupClear_ApplicationSettings').className.replace(/ disabled/g,'');
			document.getElementById('objGroupClear_ApplicationSettings').disabled = false;
// UPDATED 2025/03/04 - these objects are now visually toggled
//			document.getElementById('objGroupAdd_ApplicationSettings').className=document.getElementById('objGroupAdd_ApplicationSettings').className.replace(/ disabled/g,'');
//			document.getElementById('objGroupAdd_ApplicationSettings').disabled = false;
//			document.getElementById('objGroupDel_ApplicationSettings').className=document.getElementById('objGroupDel_ApplicationSettings').className.replace(/ disabled/g,'');
//			document.getElementById('objGroupDel_ApplicationSettings').disabled = false;
//			document.getElementById('objGroupUpdate_ApplicationSettings').className=document.getElementById('objGroupUpdate_ApplicationSettings').className.replace(/ disabled/g,'');
//			document.getElementById('objGroupUpdate_ApplicationSettings').disabled = false;
			document.getElementById('btnGroupIcon_ApplicationSettings').className=document.getElementById('btnGroupIcon_ApplicationSettings').className.replace(/ disabled/g,'');
			document.getElementById('btnGroupIcon_ApplicationSettings').disabled = false;
			document.getElementById('objInstalledInclude_ApplicationSettings').className=document.getElementById('objInstalledInclude_ApplicationSettings').className.replace(/ disabled/g,'');
			document.getElementById('objInstalledInclude_ApplicationSettings').disabled = false;
			document.getElementById('objIncludedRemove_ApplicationSettings').className=document.getElementById('objIncludedRemove_ApplicationSettings').className.replace(/ disabled/g,'');
			document.getElementById('objIncludedRemove_ApplicationSettings').disabled = false;
// LEFT OFF - update the below code
			// initialization of the FileDrop objects
// UPDATED 2025/03/08
//			initFileDrop('divImportDrop_SystemConfiguration','System Configuration','','','temp','','',"importData_SystemConfiguration('req',\"FILENAME\")");	// NOTE: the FILENAME value actually gets replaced by the uploaded filename!
// UPDATED 2025/07/09
//			initFileDrop('divImportDrop_ApplicationSettings','','',escape(document.getElementById('sDirTemp_ApplicationSettings').value+'/'),'',0,'',0,"ApplicationSettings('ImportDatabase',null,\"FILENAME\")");	// NOTE: the FILENAME value actually gets replaced by the uploaded filename!
			Filedrop('Init','divImportDrop_ApplicationSettings',escape(document.getElementById('sDirTemp_ApplicationSettings').value+'/'),null,null,null,null,null,null,null,null,null,"ApplicationSettings('ImportDatabase','','FILENAME')");
//			initFileDrop('divModuleDrop_SystemConfiguration','System Configuration','','','temp','','',"addModule_SystemConfiguration('req',\"FILENAME\")");	// NOTE: the FILENAME value actually gets replaced by the uploaded filename!

			// if the user selected the "I have read these" buttons, then there's no reason to display this moudules balloons
//			if ((getCookie('noballoons') == undefined) ? 0 : 1 || getCookie('noballoons') == 1) { return true; }
			// Now show the init ballons to help the user identify basic functionality
			// NOTE: this module doesn't have any fields being described in the balloons
			//var aBalloons = getCookie('balloons').split('|');
			//if (aBalloons[1] < 3) { initBalloons_SystemConfiguration(parseInt(aBalloons[1])+1); }
			break;










		// --- GENERAL TAB ---


		// Save the General Settings
		case "SaveSettings":		// saves the general settings						EXAMPLES
		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
		   // arguments[2]		[object] the object that was clicked (to disable it)			this
// LEFT OFF - update all instances of these to the new syntax using oDisable
			if (arguments.length > 2) {var oClicked = arguments[2];} else {var oClicked = '';}

// UPDATED 2025/03/07
//			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=save&target=system&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'formSystem_SystemConfiguration','','','','',"saveSystemConfiguration('succ');","saveSystemConfiguration('fail');","saveSystemConfiguration('busy');","saveSystemConfiguration('timeout');","saveSystemConfiguration('inactive');");
// UPDATED 2025/05/14
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=save&T=settings&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')),'formConfig_ApplicationSettings','',oClicked,'','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
// UPDATED 2025/07/09
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=save&T=settings&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID')),'formConfig_ApplicationSettings','',oClicked,'','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID)',oClicked,function(){ApplicationSettings('s_'+sAction,Callback);},null,null,null,'formConfig_ApplicationSettings');
			break;
		case "s_SaveSettings":
			// send a notification to the user
			Project(_sProjectUI,'succ');

			// 'Header' icons
			if (document.getElementById('sUriSocial_ApplicationSettings') != '') {	// if there is a social URI, then...
// UPDATED 2025/05/14
//				document.getElementById('imgSocial_Dashboard').src = "home/"+getCookie('sUsername')+"/imgs/webbooks.social.png";	// enable the icon
				document.getElementById('imgSocial_Dashboard').src = "home/"+Cookie('Obtain','sUsername')+"/imgs/webbooks.social.png";	// enable the icon
				_sUriSocial = document.getElementById('sUriSocial_ApplicationSettings').value;		// set the URI to the social interface
				document.getElementById('imgSocial_Dashboard').disabled = false;	// enable the social icon
			} else {
// UPDATED 2025/05/14
//				document.getElementById('imgSocial_Dashboard').src = "home/"+getCookie('sUsername')+"/imgs/webbooks.social_disabled.png";	// disable the icon
				document.getElementById('imgSocial_Dashboard').src = "home/"+Cookie('Obtain','sUsername')+"/imgs/webbooks.social_disabled.png";	// disable the icon
				_sUriSocial = '';							// set the URI to a blank value
				document.getElementById('imgSocial_Dashboard').disabled = true;	// disable the social icon
			}

			if (_sApplicationSettingsUriSocial != document.getElementById("sUriSocial_ApplicationSettings").value)	// if the URI was changed, we need to update the iFrame src value (see "case: 's_load'" below)
				{ document.getElementById('ifUriSocial_ApplicationSettings').value = document.getElementById("sUriSocial_ApplicationSettings").value; }

			// Perform any passed callback
			if (typeof(Callback) === 'function') { Callback(); }				// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
			else { eval(Callback); }							// using this line, the value can be passed as: "alert('hello world');"
			break;




		// Export Database Data
		case "ExportDatabase":		// exports all database data						EXAMPLES
// UPDATED 2025/03/07
//			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=export&target=database&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'','','','','',"exportData_SystemConfiguration('succ');","exportData_SystemConfiguration('fail');","exportData_SystemConfiguration('busy');","exportData_SystemConfiguration('timeout');","exportData_SystemConfiguration('inactive');");
// UPDATED 205/05/14
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=export&T=database&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')),'','',objDataExport_ApplicationSettings,'','',function(){ApplicationSettings('success',Callback,'notice');},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
// UPDATED 2025/07/09
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=export&T=database&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID')),'','',objDataExport_ApplicationSettings,'','',function(){ApplicationSettings('success',Callback,'notice');},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID)','objDataExport_ApplicationSettings');
			break;




		// Toggle the Import Filedrop
		case "ToggleImport":		// toggles the filedrop for the database imports upload div		EXAMPLES
			var oImportDrop = document.getElementById('divImportDrop_ApplicationSettings');

			if (oImportDrop.style.display == 'none' || oImportDrop.style.display == '') {
				oImportDrop.style.display = 'inline-block';

				// alert the user to this limitation
				Project('Popup','warn',"While the data is being imported, do <u>NOT</u> attempt another upload until this process has completed. Also, please close and re-open this application once this process has completed to access the restored data.");
			} else { oImportDrop.style.display = 'none'; }
			break;


		// Import Database Data
		case "ImportDatabase":		// imports uploaded database data					EXAMPLES
		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
		   // arguments[2]		[string] the name of the file to import; can be .sql or .tgz		'backup.sql'
			// NOTE:		existing/duplicate data will NOT be imported - only missing data!
			ApplicationSettings('ToggleImport');
// UPDATED 2025/03/07
//			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=import&target=database&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&filename='+escape(strFilename),'','','','','',"importData_SystemConfiguration('succ',\""+strFilename+"\");","importData_SystemConfiguration('fail',\""+strFilename+"\");","importData_SystemConfiguration('busy',\""+strFilename+"\");","importData_SystemConfiguration('timeout',\""+strFilename+"\");","importData_SystemConfiguration('inactive',\""+strFilename+"\");");
// UPDATED 2025/05/14
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=import&T=database&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID'))+'&sFilename='+escape(arguments[2]),'','',objDataImport_ApplicationSettings,'','',function(){ApplicationSettings('success',Callback,'notice');},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
// UPDATED 2025/07/09
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=import&T=database&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&sFilename='+escape(arguments[2]),'','',objDataImport_ApplicationSettings,'','',function(){ApplicationSettings('success',Callback,'notice');},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&sFilename='+escape(arguments[2]),'objDataImport_ApplicationSettings');
			break;




// LEFT OFF - do this over the weekend
		// Toggle the Archive Popup
		case "ToggleArchive":		// toggles the popup for the database archive settings			EXAMPLES
		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
		   // arguments[2]		[string] the event from the html page					event
// LEFT OFF - show a header div with the following info:
//  alert('Archive old data by selecting the date to backup and remove from the database.' + document.getElementById('eArchive_ApplicationSettings').value);
//  and position the calendar div under it (no cursor tracking)
//			var oImportDrop = document.getElementById('divImportDrop_ApplicationSettings');
//
//			if (oImportDrop.style.display == 'none' || oImportDrop.style.display == '') {
//				oImportDrop.style.display = 'inline-block';
//
//				// alert the user to this limitation
//				Project('Popup','warn',"While the data is being imported, do <u>NOT</u> attempt another upload until this process has completed. Also, please close and re-open this application once this process has completed to access the restored data.");
//			} else { oImportDrop.style.display = 'none'; }
Project('Calendar',event,null,null,null,null,null,'left',"ApplicationSettings('ArchiveDatabase',null,document.getElementById('eArchive_ApplicationSettings').value)");
			break;


		// Archive Database Data
		case "ArchiveDatabase":		// archives database data						EXAMPLES
		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
		   // arguments[2]		[string] the date to backup and delete from the database		'2020/01/01'
			if (document.getElementById('eArchive_ApplicationSettings').value == '') {
alert('DEBUG: exiting since no date was provided');
 return false; }	// if the user clicked the 'X' on the calendar popup, then exit this function
alert('DEBUG: calling the archive routine!');

// UPDATED 2025/03/07
//			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=archive&target=database&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&date='+escape(strDate),'','','','','',"archiveData_SystemConfiguration('succ','"+strDate+"');","archiveData_SystemConfiguration('fail','"+strDate+"');","archiveData_SystemConfiguration('busy','"+strDate+"');","archiveData_SystemConfiguration('timeout','"+strDate+"');","archiveData_SystemConfiguration('inactive','"+strDate+"');");
	//		ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=archive&T=database&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID'))+'&eDate='+escape(arguments[2]),'','',objDataArchive_ApplicationSettings,'','',function(){ApplicationSettings('success',Callback,'notice');},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
			break;










		// --- MODULES TAB ---


		// Download Available Updates
		case "DownloadUpdates":		// downloads any available updates
			document.getElementById('sUpdatesList_ApplicationSettings').options.length = 0;

// UPDATED 2025/05/14
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=download&T=updates&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')),'','','objDownloadUpdates_ApplicationSettings','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
// UPDATED 2025/07/09
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=download&T=updates&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID')),'','','objDownloadUpdates_ApplicationSettings','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID)','objDownloadUpdates_ApplicationSettings',function(){ApplicationSettings('s_'+sAction,Callback);});
			break;
		case "s_DownloadUpdates":
			// send a notification to the user
			Project(_sProjectUI,'succ');

			var u = XML.getElementsByTagName("update");
			for (var i=0; i<u.length; i++)
// UPDATED 2025/03/05
//				{ Add2List('nUpdatesList_ApplicationSettings', u[i].getAttribute('module'), u[i].getAttribute('module').slice(0,-4).replace(/_/g, " "), 1, 1, 0); }
// UPDATED 2025/05/29
//				{ Add2List('nUpdatesList_ApplicationSettings', u[i].getAttribute('module'), u[i].getAttribute('module').slice(0,-4), 1, 1, 0); }
				{ Listbox('AddOption','sUpdatesList_ApplicationSettings',u[i].getAttribute('module'),u[i].getAttribute('module').slice(0,-4),'','',false,false,true); }
			break;




		// Installs Selected Updates
		case "InstallUpdates":		// installs selected updates
			if (document.getElementById('sUpdatesList_ApplicationSettings').selectedIndex == -1) {
				Project(_sProjectUI,'fail',"You must select at least one update to install from the list before continuing.");
				return false;
			}

			// check if the 'core' software has been checked to install and prompt the user if so!
			for (var i=0; i<document.getElementById('sUpdatesList_ApplicationSettings').options.length; i++) {
				if (document.getElementById('sUpdatesList_ApplicationSettings').options[i].value == 'webbooks.tgz' && document.getElementById('sUpdatesList_ApplicationSettings').options[i].selected)
					{ if (! confirm("Installing the 'webBooks' update requires a refresh of the application which\nwill erase any unsaved information. Are you sure you want to continue?")) {return false;} }
			}

// UPDATED 2025/05/29
//			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=install&target=updates&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&nUpdatesList_SystemConfiguration='+escape(document.getElementById('nUpdatesList_SystemConfiguration').value),'','','btnInstallUpdates_SystemConfiguration','','',"installUpdates_SystemConfiguration('succ');","installUpdates_SystemConfiguration('fail');","installUpdates_SystemConfiguration('busy');","installUpdates_SystemConfiguration('timeout');","installUpdates_SystemConfiguration('inactive');");
// UPDATED 2025/07/09
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=install&T=updates&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&sUpdatesList='+escape(document.getElementById('sUpdatesList_ApplicationSettings').value),'','','objInstallUpdates_ApplicationSettings','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&sUpdatesList='+escape(document.getElementById('sUpdatesList_ApplicationSettings').value),'objInstallUpdates_ApplicationSettings',function(){ApplicationSettings('s_'+sAction,Callback);});
			break;
		case "s_InstallUpdates":	// success!
			// send a notification to the user
			Project(_sProjectUI,'succ',"The updates have been installed successfully!");

			var u = XML.getElementsByTagName("update");
			for (var I=0; I<u.length; I++)
// UPDATED - 2025/03/28
//				{ ListRemove4('nUpdatesList_SystemConfiguration', 0, 'value', u[I].getAttribute('module')); }
// UPDATED - 2025/05/29
//				{ ListRemove('nUpdatesList_SystemConfiguration', 0, 'value', u[I].getAttribute('module')); }
				{ Listbox('RemoveOption','sUpdatesList_ApplicationSettings',u[I].getAttribute('module'),'value'); }

// LEFT OFF - we need to do a refresh of the application after a ***WEBBOOKS*** core update!!! use a timeout call here so the user can see the above notice
			break;




		// Install Uploaded Module
		case "InstallModule":		// installs the uploaded module
		   // Callback			the callback to execute upon success; value can be a string or function()
		   // arguments[2]		the filename that was just uploaded
		   // arguments[3]		if the module should be installed after a prompt was issued to the user
			// default value assignments
			if (arguments.length < 4) { arguments[3] = 'false'; }

			var sFilename = arguments[2];				// so the value can used in callbacks

// UPDATED 2025/07/09
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=install&T=module&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&sFilename='+escape(sFilename)+'&bForce='+arguments[3],'','','','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('f_'+sAction,Callback,sFilename);},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&sFilename='+escape(sFilename)+'&bForce='+arguments[3],'',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('f_'+sAction,Callback);});
			break;
		case "s_InstallModule":		// success!
			// send a notification to the user
			Project(_sProjectUI,'succ');

			// reload the Dashboard
			Dashboard('load');
			Listbox('AddOption','nInstalledList_ApplicationSettings',DATA['id'],DATA['sName']);
			Listbox('RemoveOption','nInstalledList_ApplicationSettings');		// remove the module from the "installed modules" listboxes
			Listbox('RemoveOption','nInstalledListing_ApplicationSettings');

			// re-init the filedrop so more uploads can occur
// UPDATED 2025/07/09
//			initFileDrop('divModuleDrop_ApplicationSettings','','','temp/','','',0,'',0,"ApplicationSettings('InstallModule','','FILENAME')");
			Filedrop('Init','divModuleDrop_ApplicationSettings','temp/',null,null,null,null,null,null,null,null,null,"ApplicationSettings('InstallModule','','FILENAME')");

			// reset the (form) objects
			delete DATA['id'];					// to prevent contamination between calls
			delete DATA['sName'];
			break;
		case "f_InstallModule":		// failure...
		   // Callback			the callback to execute upon success; value can be a string or function()
		   // arguments[2]		the filename that was just uploaded
			// re-init the filedrop so more uploads can occur
// UPDATED 2025/07/09
//			initFileDrop('divModuleDrop_ApplicationSettings','','','temp/','','',0,'',0,"ApplicationSettings('InstallModule','','FILENAME')");
			Filedrop('Init','divModuleDrop_ApplicationSettings','temp/',null,null,null,null,null,null,null,null,null,"ApplicationSettings('InstallModule','','FILENAME')");

			// if we have a reason to prompt the user about the attempted installation (e.g. no hash could be found), then...
			if (DATA.hasOwnProperty("prompt")) {
// VER2 - update this to a dialog() call
				if (! confirm(MESSAGE)) { return 0; }
				ApplicationSettings('InstallModule','',arguments[2],'true');
				delete DATA['prompt'];				// to prevent contamination between calls
				return false;
			}
			
			// if we've made it here, some other error has occurred, so...

			// show the notification to the user
			Project(_sProjectUI,'fail');
			break;




		// Uninstall Selected Module
		case "UninstallModule":		// uninstalls the selected module
		   // Callback			the callback to execute upon success; value can be a string or function()
		   // arguments[2]		the module name to uninstall
			var sModule = arguments[2];				// so the value can used in callbacks
// VER2 - update this to a dialog() call
			var bRetainData = (confirm("The uninstall process can remove the module files while\nleaving its data behind for any future reinstallation, or\nthis process can delete the files and data so that there\nis nothing left behind. Should the data be retained?\n\n'OK' = the data will remain\n'Cancel' = everything will be deleted")) ? 'true' : 'false';

// UPDATED 2025/07/09
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=uninstall&T=module&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&sModule='+escape(sModule)+'&bRetainData='+bRetainData,'','','','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&sModule='+escape(sModule)+'&bRetainData='+bRetainData,'',function(){ApplicationSettings('s_'+sAction,Callback);});
			break;
		case "s_UninstallModule":		// success!
			// send a notification to the user
			Project(_sProjectUI,'succ');

			Dashboard('load');					// reload the Dashboard
			Listbox('RemoveOption','nInstalledList_ApplicationSettings');		// remove the module from the "installed modules" listboxes
			Listbox('RemoveOption','nInstalledListing_ApplicationSettings');
			break;










		// --- GROUPS TAB ---


		// Clear Group Form
		case "ClearGroup":		// clears the groups listing
		   // Callback			the callback to execute upon success; value can be a string or function()
		   // arguments[2]		if the "Abandon" popup should be shown
			// warn the user if they are about to potentially loose any unsaved changes
// LEFT OFF - this is triggering at the wrong times
//			if (document.getElementById('nGroupList_ApplicationSettings').selectedIndex != -1 && ! arguments[2])
//				{ if (window.confirm('Are you sure you want to abandon any unsaved changes?') == false) {return false;} }

			// reset the form
			document.getElementById('sGroupName_ApplicationSettings').value = '';
			document.getElementById('sGroupIcon_ApplicationSettings').value = '';
			document.getElementById('nGroupList_ApplicationSettings').selectedIndex = -1;

			// erase the contents of the include modules
			document.getElementById('nIncludedList_ApplicationSettings').options.length = 0;

			// toggle applicable form objects
			if (document.getElementById('objGroupAdd_ApplicationSettings').style.display == 'none') {
				$('#objGroupDel_ApplicationSettings').toggle('slow');
				$('#objGroupAdd_ApplicationSettings').toggle('slow');
				$('#objGroupUpdate_ApplicationSettings').toggle('slow');
			}
			break;




		// Load Selected Group
		case "LoadGroup":		// loads the selected group from the listing
// UPDATED 2025/05/14
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=load&T=group&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID'))+'&id='+document.getElementById('nGroupList_ApplicationSettings').options[document.getElementById('nGroupList_ApplicationSettings').selectedIndex].value,'','','nGroupList_ApplicationSettings','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
// UPDATED 2025/07/09
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=load&T=group&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&id='+document.getElementById('nGroupList_ApplicationSettings').value,'','','nGroupList_ApplicationSettings','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+document.getElementById('nGroupList_ApplicationSettings').value,'nGroupList_ApplicationSettings',function(){ApplicationSettings('s_'+sAction,Callback);});
			break;
		case "s_LoadGroup":
			// 'Groups' tab data
			var g = XML.getElementsByTagName("group");
			
			// add the group info to the top two textboxes
			document.getElementById('sGroupName_ApplicationSettings').value = g[0].getAttribute('sName').replace('&amp;', '&');
			document.getElementById('sGroupIcon_ApplicationSettings').value = g[0].getAttribute('sIcon');
			
			document.getElementById('nIncludedList_ApplicationSettings').options.length = 0;	// blank out all the existing associated modules from the previous group
			for (var I=0; I<g.length; I++) {
				// add the modules of the first group ONLY to the list
				m = g[I].getElementsByTagName("module");
				for (var j=0; j<m.length; j++)
// UPDATED 2025/05/29
//					{ Add2List('nIncludedList_ApplicationSettings', m[j].getAttribute('id'), m[j].getAttribute('sName').replace('&amp;', '&'), 1, 1, 0); }
					{ Listbox('AddOption','nIncludedList_ApplicationSettings',m[j].getAttribute('id'),m[j].getAttribute('sName').replace('&amp;', '&'),'','',false,false,true); }
			}

			// toggle applicable form objects
			if (document.getElementById('objGroupAdd_ApplicationSettings').style.display != 'none') {
				$('#objGroupAdd_ApplicationSettings').toggle('slow');
				$('#objGroupDel_ApplicationSettings').toggle('slow');
				$('#objGroupUpdate_ApplicationSettings').toggle('slow');
			}
			break;




		// Add or Update Group
		case "SaveGroup":		// adds a new group to the listing or updates the existing selected group
			if (document.getElementById('sGroupName_ApplicationSettings').value == '') {
				Project(_sProjectUI,'fail',"You must specify the groups name before adding it to the list.");
				return false;
			}
			if (document.getElementById('sGroupIcon_ApplicationSettings').value == '') {
				Project(_sProjectUI,'fail',"You must specify an associated icon with the new group before adding it to the list.");
				return false;
			}
// REMOVED 2025/03/05 - this would trigger if the user was trying to update the group icon (preventing them from doing so)
//			if (ListExists('nGroupList_ApplicationSettings','',document.getElementById('sGroupName_ApplicationSettings').value,0,0)) {
//				Project(_sProjectUI,'fail',"A group with that information has already been added to the list, please use a different name for the group.");
//				return false;
//			}

			var sUpdate = '';	// used to indicate we need to update instead of create
			if (document.getElementById('nGroupList_ApplicationSettings').selectedIndex != -1)
				{ sUpdate = '&id='+document.getElementById('nGroupList_ApplicationSettings').options[document.getElementById('nGroupList_ApplicationSettings').selectedIndex].value; }

// UPDATED 2025/05/14
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=save&T=group&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID'))+'&sName='+escape(document.getElementById('sGroupName_ApplicationSettings').value)+'&sIcon='+document.getElementById('sGroupIcon_ApplicationSettings').value + sUpdate,'','','objGroupAdd_ApplicationSettings','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
// UPDATED 2025/07/09
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=save&T=group&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&sName='+escape(document.getElementById('sGroupName_ApplicationSettings').value)+'&sIcon='+document.getElementById('sGroupIcon_ApplicationSettings').value + sUpdate,'','','objGroupAdd_ApplicationSettings','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&sName='+escape(document.getElementById('sGroupName_ApplicationSettings').value)+'&sIcon='+document.getElementById('sGroupIcon_ApplicationSettings').value+sUpdate,'objGroupAdd_ApplicationSettings',function(){ApplicationSettings('s_'+sAction,Callback);});
			break;
		case "s_SaveGroup":
			// alert the user
			Project(_sProjectUI,'succ');

			if (DATA.hasOwnProperty("id")) {		// if a new group was added, then...
// UPDATED 2025/05/29
//				Add2List('nGroupList_ApplicationSettings',DATA['id'],document.getElementById('sGroupName_ApplicationSettings').value,1,1,0);
				Listbox('AddOption','nGroupList_ApplicationSettings',DATA['id'],document.getElementById('sGroupName_ApplicationSettings').value,'','',false,false,true);
// UPDATED 2025/03/05
//				selListbox('nGroupList_ApplicationSettings',DATA['id']);	// select the newly added item in the listbox
//				loadGroup_SystemConfiguration('req');				// now load any associated modules with that group (which should be nothing at this point)
				ApplicationSettings('ClearGroup',null,false);	// now make the form changes
			} else {					// otherwise we are updating an existing group, so...
				ListReplace2('nGroupList_ApplicationSettings','',document.getElementById('sGroupName_ApplicationSettings').value,1,0,0);
			}

			Dashboard('load');						// now reload the dashboard groupings so the changes can be reflected

			// reset the (form) objects
			if (DATA.hasOwnProperty("id")) { delete DATA['id']; }		// to prevent contamination between calls
			break;




		// Delete Selected Group
		case "DeleteGroup":		// deletes the selected group from the listing
			if (document.getElementById('nGroupList_ApplicationSettings').selectedIndex == -1) {
				Project(_sProjectUI,'fail',"You must select a group to remove before continuing.");
				return false;
			}

// UPDATED 2025/05/14
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=delete&T=group&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID'))+'&id='+document.getElementById('nGroupList_ApplicationSettings').options[document.getElementById('nGroupList_ApplicationSettings').selectedIndex].value,'','','objGroupDel_ApplicationSettings','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
// UPDATED 2025/07/09
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=delete&T=group&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&id='+document.getElementById('nGroupList_ApplicationSettings').options[document.getElementById('nGroupList_ApplicationSettings').selectedIndex].value,'','','objGroupDel_ApplicationSettings','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+document.getElementById('nGroupList_ApplicationSettings').value,'objGroupDel_ApplicationSettings',function(){ApplicationSettings('s_'+sAction,Callback);});
			break;
		case "s_DeleteGroup":
			// alert the user
			Project(_sProjectUI,'succ');

			ListRemove('nGroupList_ApplicationSettings',0);
			if (document.getElementById('nGroupList_ApplicationSettings').options.length >= 0) { document.getElementById('nGroupList_ApplicationSettings').options.selectedIndex = 0; }	// select the first option in the list after the deletion
// UPDATED 2025/03/05
//			loadGroup_SystemConfiguration('req');				// now load any associated modules with that group (which should be nothing at this point)
			ApplicationSettings('ClearGroup',null,false);	// now make the form changes

			Dashboard('load');						// now reload the dashboard groupings so the changes can be reflected
			break;




		// Include Selected Module
		case "IncludeModule":		// includes the selected module into the selected group
			if (document.getElementById('nGroupList_ApplicationSettings').selectedIndex == -1) {
				Project(_sProjectUI,'fail',"You must select one of the groups from the 'Module Groups' list before adding it to the 'Included Modules' list.");
				return false;
			}

			if (document.getElementById('nInstalledListing_ApplicationSettings').selectedIndex == -1) {
				Project(_sProjectUI,'fail',"You must select one of the modules from the 'Installed Modules' list before adding it to the 'Included Modules' list.");
				return false;
			}

			if (ListExists('nIncludedList_ApplicationSettings',document.getElementById('nInstalledListing_ApplicationSettings').options[document.getElementById('nInstalledListing_ApplicationSettings').selectedIndex].value,'',0,0)) {
				Project(_sProjectUI,'fail',"That module is already a member of the selected group, please select a different module to add.");
				return false;
			}

// UPDATED 2025/05/14
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=include&T=module&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID'))+'&group='+document.getElementById('nGroupList_ApplicationSettings').options[document.getElementById('nGroupList_ApplicationSettings').selectedIndex].value+'&module='+document.getElementById('nInstalledListing_ApplicationSettings').options[document.getElementById('nInstalledListing_ApplicationSettings').selectedIndex].value,'','','objInstalledInclude_ApplicationSettings','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
// UPDATED 2025/07/09
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=include&T=Module&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&group='+document.getElementById('nGroupList_ApplicationSettings').options[document.getElementById('nGroupList_ApplicationSettings').selectedIndex].value+'&module='+document.getElementById('nInstalledListing_ApplicationSettings').options[document.getElementById('nInstalledListing_ApplicationSettings').selectedIndex].value,'','','objInstalledInclude_ApplicationSettings','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&group='+document.getElementById('nGroupList_ApplicationSettings').value+'&module='+document.getElementById('nInstalledListing_ApplicationSettings').value,'objInstalledInclude_ApplicationSettings',function(){ApplicationSettings('s_'+sAction,Callback);});
			break;
		case "s_IncludeModule":
			// alert the user
			Project(_sProjectUI,'succ');

// UPDATED 2025/05/29
//			Add2List('nIncludedList_ApplicationSettings',document.getElementById('nInstalledListing_ApplicationSettings').options[document.getElementById('nInstalledListing_ApplicationSettings').selectedIndex].value,document.getElementById('nInstalledListing_ApplicationSettings').options[document.getElementById('nInstalledListing_ApplicationSettings').selectedIndex].text,1,1,0);
			Listbox('AddOption','nIncludedList_ApplicationSettings',document.getElementById('nInstalledListing_ApplicationSettings').value,document.getElementById('nInstalledListing_ApplicationSettings').options[document.getElementById('nInstalledListing_ApplicationSettings').selectedIndex].text,'','',false,false,true);

			Dashboard('load');						// now reload the dashboard groupings so the changes can be reflected
			break;




		// Remove Selected Module
		case "RemoveModule":		// removes the selected module from the selected group
			if (document.getElementById('nGroupList_ApplicationSettings').selectedIndex == -1) {
				Project(_sProjectUI,'fail',"You must select one of the groups from the 'Module Groups' list before removing the selected module.");
				return false;
			}

			if (document.getElementById('nIncludedList_ApplicationSettings').selectedIndex == -1) {
				Project(_sProjectUI,'fail',"You must select one of the modules from the 'Included Modules' list before continuing.");
				return false;
			}

// UPDATED 2025/05/14
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=remove&T=module&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID'))+'&group='+document.getElementById('nGroupList_ApplicationSettings').options[document.getElementById('nGroupList_ApplicationSettings').selectedIndex].value+'&module='+document.getElementById('nIncludedList_ApplicationSettings').options[document.getElementById('nIncludedList_ApplicationSettings').selectedIndex].value,'','','objIncludedRemove_ApplicationSettings','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
// UPDATED 2025/07/09
//			ajax(_oApplicationSettings,4,'post',_sUriProject+"code/ApplicationSettings.php",'A=remove&T=Module&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&group='+document.getElementById('nGroupList_ApplicationSettings').options[document.getElementById('nGroupList_ApplicationSettings').selectedIndex].value+'&module='+document.getElementById('nIncludedList_ApplicationSettings').options[document.getElementById('nIncludedList_ApplicationSettings').selectedIndex].value,'','','objIncludedRemove_ApplicationSettings','','',function(){ApplicationSettings('s_'+sAction,Callback);},function(){ApplicationSettings('fail',Callback,'notice');},function(){ApplicationSettings('busy',Callback);},function(){ApplicationSettings('timeout',Callback);},function(){ApplicationSettings('inactive',Callback,'notice');});
			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&group='+document.getElementById('nGroupList_ApplicationSettings').value+'&module='+document.getElementById('nInstalledListing_ApplicationSettings').value,'objIncludedRemove_ApplicationSettings',function(){ApplicationSettings('s_'+sAction,Callback);});
			break;
		case "s_RemoveModule":
			// alert the user
			Project(_sProjectUI,'succ');

			ListRemove('nIncludedList_ApplicationSettings',0);

			Dashboard('load');						// now reload the dashboard groupings so the changes can be reflected
			break;










		// --- LOGS TAB ---


		// Load Available Logs
		case "LoadLogs":		// loads all the available logs
			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID)','sLogs_ApplicationSettings',function(){ApplicationSettings('s_'+sAction,Callback);});
			break;
		case "s_LoadLogs":
			// remove any prior listings
			document.getElementById('sLog_ApplicationSettings').value = '';
			document.getElementById('sLogs_ApplicationSettings').options.length = 1;
			
			// add each returned log
			var l = XML.getElementsByTagName("log");
			for (var i=0; i<l.length; i++)
				{ Listbox('AddOption','sLogs_ApplicationSettings',l[i].getAttribute('sName')+'.log',l[i].getAttribute('sName').replace('&amp;', '&')); }
			break;




		// Load Selected Log
		case "LoadLog":		// loads the contents of the selected log
			if (document.getElementById('sLogs_ApplicationSettings').selectedIndex == 0) {
				// remove any prior value
				document.getElementById('sLog_ApplicationSettings').value = '';
				return false;
			}

			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&sLog='+escape(document.getElementById('sLogs_ApplicationSettings').value),'sLogs_ApplicationSettings',function(){ApplicationSettings('s_'+sAction,Callback);});
			break;
		case "s_LoadLog":
			// remove any prior value
			document.getElementById('sLog_ApplicationSettings').value = '';
			
			// add each returned log
			var l = XML.getElementsByTagName("log").item(0);
			document.getElementById('sLog_ApplicationSettings').value = l.firstChild.data;
			break;




		// Delete Selected Log
		case "DeleteLog":		// deletes the selected log
			if (document.getElementById('sLogs_ApplicationSettings').selectedIndex == 0) { return false; }

			Ajax('Call',_oApplicationSettings,_sUriProject+"code/ApplicationSettings.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&sLog='+escape(document.getElementById('sLogs_ApplicationSettings').value),'sLogs_ApplicationSettings',function(){ApplicationSettings('s_'+sAction,Callback);});
			break;
		case "s_DeleteLog":
			// alert the user
			//Project(_sProjectUI,'succ');							removed since the UI was in the way

			// delete the selected log from the listing
		   	Listbox('RemoveOption','sLogs_ApplicationSettings');

			// remove any prior value
			document.getElementById('sLog_ApplicationSettings').value = '';

			// select "Select..." from the files listing
			document.getElementById('sLogs_ApplicationSettings').selectedIndex = 0;
			break;
	}


	// Perform any passed callback
	if (sAction.substring(0,2) == 's_') {								// only execute these lines if a 'success' return has been made
		if (typeof(mCallback) === 'function') { mCallback(); }					// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
		else if (typeof(mCallback) === 'string') { eval(mCallback); }				// using this line, the value can be passed as: "alert('hello world');"
	}
}





// --- LEGACY ---






// GLOBALS

var SystemConfiguration=0;				// used as the identify if the 'init' function has been executed
var reqSystemConfiguration;				// used for AJAX calls via interaction with the 'IO' pane itself




// INITIALIZATION FUNCTIONS

function init_SystemConfiguration2(strAction) {
// initializes the module by loading all of the form object values after the screen contents have been added							MIGRATED
   switch(strAction) {
	case "req":
alert('DEPRECATED - init_SystemConfiguration');
return true;

		// don't process this function if the account doesn't have permissions to open it!
		// -OR-
		// maybe the HTML/css side hasn't loaded yet during the first loading of the module
		if (! document.getElementById('sAdminType_SystemConfiguration')) {
		   // if the HTML/css side hasn't loaded yet, then...
		   if (SystemConfiguration == 0) { setTimeout("init_SystemConfiguration('req')",1000); }

		   return false;			// in any case, exit this function to prevent JS problems!
		}

		SystemConfiguration=1;			// mark this module as being initialized!	WARNING: this MUST come right above the ajax() call!

		ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=init&target=values&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'','','','','',"init_SystemConfiguration('succ');","init_SystemConfiguration('fail');","init_SystemConfiguration('busy');","init_SystemConfiguration('timeout');","init_SystemConfiguration('inactive');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		init_SystemConfiguration('req');
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		init_SystemConfiguration('req');
		break;
	case "succ":
		// if this isn't a hosted service, then don't display the 'Hosted' tab
		if (! HOSTED) { document.getElementById('liHosted_SystemConfiguration').style.display = 'none'; }

		// 'General' tab data
// UPDATED 2025/03/01
//		var s = XML.getElementsByTagName("system").item(0);
//		document.getElementById('sSocialURI_SystemConfiguration').value = s.getAttribute('socialURI');
//		if (HOSTED) {
//			document.getElementById('sAccessURI_SystemConfiguration').value = s.getAttribute('accessURI');
//			document.getElementById('nRequiredLogins_SystemConfiguration').value = s.getAttribute('logins');
//			selListbox('nTechSupport_SystemConfiguration',s.getAttribute("support"));
//			document.getElementById('nBalance_SystemConfiguration').value = addChange(s.getAttribute('balance'), 2);
//			document.getElementById('custom').value = s.getAttribute('prefix');
//			calcCharges_SystemConfiguration();
//		}
//
//		var u = XML.getElementsByTagName("update");
//		for (var I=0; I<u.length; I++)
//			{ Add2List('nUpdatesList_SystemConfiguration', u[I].getAttribute('module'), u[I].getAttribute('module').slice(0,-4).replace(/_/g, " "), 1, 1, 0); }
//
//		var a = XML.getElementsByTagName("admin").item(0);
//		    p = XML.getElementsByTagName("person");
//		selListbox('sAdminType_SystemConfiguration',a.getAttribute("type"));
//		for (I=0; I<p.length; I++)
//			{ Add2List('nAdminList_SystemConfiguration', p[I].getAttribute('id'), p[I].getAttribute('name'), 1, 1, 0); }
//		selListbox('nAdminList_SystemConfiguration',a.getAttribute("id"));
//		document.getElementById('sAdminEmail_SystemConfiguration').value = a.getAttribute("email");
//		document.getElementById('nAdminWorkPhone_SystemConfiguration').value = a.getAttribute("phone");
//		document.getElementById('nAdminWorkExt_SystemConfiguration').value = a.getAttribute("ext");
//		document.getElementById('nAdminMobilePhone_SystemConfiguration').value = a.getAttribute("mobile");
//		document.getElementById('bAdminMobileSMS_SystemConfiguration').checked = (a.getAttribute("sms") == 0) ? false : true;
//		document.getElementById('bAdminMobileEmail_SystemConfiguration').checked = (a.getAttribute("mail") == 0) ? false : true;
//
//		var d = XML.getElementsByTagName("dirs").item(0);
//		var m = XML.getElementsByTagName("module").item(0);
//		selListbox('sModuleUpdates_SystemConfiguration',m.getAttribute("update"));
//		selListbox('sInstallUpdates_SystemConfiguration',m.getAttribute("install"));
//		document.getElementById('sDataDir_SystemConfiguration').value = d.getAttribute("data");
//		document.getElementById('sLogsDir_SystemConfiguration').value = d.getAttribute("logs");
//		document.getElementById('sCronDir_SystemConfiguration').value = d.getAttribute("cron");
//		document.getElementById('sTempDir_SystemConfiguration').value = d.getAttribute("temp");
//
//		// 'Modules' tab data
//		var g = XML.getElementsByTagName("group");
//		for (I=0; I<g.length; I++) {
//		   // add each group
//		   Add2List('nGroupList_SystemConfiguration', g[I].getAttribute('id'), g[I].getAttribute('name').replace('&amp;', '&'), 1, 1, 0);
//		   document.getElementById('sGroupName_SystemConfiguration').value = g[0].getAttribute('name').replace('&amp;', '&');
//		   document.getElementById('sGroupIcon_SystemConfiguration').value = g[0].getAttribute('icon');
//
//		   // add the modules of the first group ONLY to the list
//		   if (I == 0) {
//			m = g[I].getElementsByTagName("module");
//			for (var j=0; j<m.length; j++)
//			   { Add2List('nIncludedList_SystemConfiguration', m[j].getAttribute('id'), m[j].getAttribute('name'), 1, 1, 0); }
//		   }
//		}
//		if (document.getElementById('nGroupList_SystemConfiguration').options.length >= 0) { document.getElementById('nGroupList_SystemConfiguration').options.selectedIndex = 0; }
//
//		var i = XML.getElementsByTagName("installed");
//		for (I=0; I<i.length; I++)				// add all the modules currently installed to the last listbox
//		   { Add2List('nInstalledList_SystemConfiguration', i[I].getAttribute('id'), i[I].getAttribute('name'), 1, 1, 0); }
//
//		// enable any form objects now that the account has been loaded
//		document.getElementById('btnDataExport_SystemConfiguration').className=document.getElementById('btnDataExport_SystemConfiguration').className.replace(/ disabled/g,'');
//		document.getElementById('btnDataExport_SystemConfiguration').disabled = false;
//		document.getElementById('btnDataImport_SystemConfiguration').className=document.getElementById('btnDataImport_SystemConfiguration').className.replace(/ disabled/g,'');
//		document.getElementById('btnDataImport_SystemConfiguration').disabled = false;
//		document.getElementById('btnDataArchive_SystemConfiguration').className=document.getElementById('btnDataArchive_SystemConfiguration').className.replace(/ disabled/g,'');
//		document.getElementById('btnDataArchive_SystemConfiguration').disabled = false;
//
//		document.getElementById('btnDownloadUpdates_SystemConfiguration').className=document.getElementById('btnDownloadUpdates_SystemConfiguration').className.replace(/ disabled/g,'');
//		document.getElementById('btnDownloadUpdates_SystemConfiguration').disabled = false;
//		document.getElementById('btnInstallUpdates_SystemConfiguration').className=document.getElementById('btnInstallUpdates_SystemConfiguration').className.replace(/ disabled/g,'');
//		document.getElementById('btnInstallUpdates_SystemConfiguration').disabled = false;
//		document.getElementById('nUpdatesList_SystemConfiguration').className=document.getElementById('nUpdatesList_SystemConfiguration').className.replace(/ disabled/g,'');
//		document.getElementById('nUpdatesList_SystemConfiguration').disabled = false;

//		document.getElementById('btnGroupClear_SystemConfiguration').className=document.getElementById('btnGroupClear_SystemConfiguration').className.replace(/ disabled/g,'');
//		document.getElementById('btnGroupClear_SystemConfiguration').disabled = false;
//		document.getElementById('btnGroupAdd_SystemConfiguration').className=document.getElementById('btnGroupAdd_SystemConfiguration').className.replace(/ disabled/g,'');
//		document.getElementById('btnGroupAdd_SystemConfiguration').disabled = false;
//		document.getElementById('btnGroupDel_SystemConfiguration').className=document.getElementById('btnGroupDel_SystemConfiguration').className.replace(/ disabled/g,'');
//		document.getElementById('btnGroupDel_SystemConfiguration').disabled = false;
//		document.getElementById('btnGroupUpdate_SystemConfiguration').className=document.getElementById('btnGroupUpdate_SystemConfiguration').className.replace(/ disabled/g,'');
//		document.getElementById('btnGroupUpdate_SystemConfiguration').disabled = false;
//		document.getElementById('btnGroupIcon_SystemConfiguration').className=document.getElementById('btnGroupIcon_SystemConfiguration').className.replace(/ disabled/g,'');
//		document.getElementById('btnGroupIcon_SystemConfiguration').disabled = false;

//		document.getElementById('btnIncludedDel_SystemConfiguration').className=document.getElementById('btnIncludedDel_SystemConfiguration').className.replace(/ disabled/g,'');
//		document.getElementById('btnIncludedDel_SystemConfiguration').disabled = false;
		document.getElementById('btnInstalledAdd_SystemConfiguration').className=document.getElementById('btnInstalledAdd_SystemConfiguration').className.replace(/ disabled/g,'');
		document.getElementById('btnInstalledAdd_SystemConfiguration').disabled = false;
//		document.getElementById('btnInstalledDel_SystemConfiguration').className=document.getElementById('btnInstalledDel_SystemConfiguration').className.replace(/ disabled/g,'');
//		document.getElementById('btnInstalledDel_SystemConfiguration').disabled = false;
//		document.getElementById('btnInstalledInclude_SystemConfiguration').className=document.getElementById('btnInstalledInclude_SystemConfiguration').className.replace(/ disabled/g,'');
//		document.getElementById('btnInstalledInclude_SystemConfiguration').disabled = false;

		// initialization of the FileDrop objects
//		initFileDrop('divImportDrop_SystemConfiguration','System Configuration','','','temp','','',"importData_SystemConfiguration('req',\"FILENAME\")");	// NOTE: the FILENAME value actually gets replaced by the uploaded filename!
// UPDATED 2025/07/09
//		initFileDrop('divModuleDrop_SystemConfiguration','System Configuration','','','temp','','',"addModule_SystemConfiguration('req',\"FILENAME\")");	// NOTE: the FILENAME value actually gets replaced by the uploaded filename!
		Filedrop('Init','divModuleDrop_ApplicationSettings','temp/',null,null,null,null,null,null,null,null,null,"addModule_SystemConfiguration('req',\"FILENAME\")");

		// if the user selected the "I have read these" buttons, then there's no reason to display this moudules balloons
// UPDATED 2025/05/14
//		if ((getCookie('noballoons') == undefined) ? 0 : 1 || getCookie('noballoons') == 1) { return true; }
		if ((Cookie('Obtain','noballoons') == undefined) ? 0 : 1 || Cookie('Obtain','noballoons') == 1) { return true; }
		// Now show the init ballons to help the user identify basic functionality
		// NOTE: this module doesn't have any fields being described in the balloons
		//var aBalloons = getCookie('balloons').split('|');
		//if (aBalloons[1] < 3) { initBalloons_SystemConfiguration(parseInt(aBalloons[1])+1); }
		break;
	case "fail":
		// no reason to display anything because the server-side script will handle the message
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}


function initBalloons_SystemConfiguration(intStep) {
// controls if the 'welcome' balloons should be shown to a new user
}


function clickTab_SystemConfiguration(Tab) {
// Makes form adjustments to show the content of the selected tab												MIGRATED

alert('DEPRECATED (clickTab_SystemConfiguration) 2025/03/01');
return true;
	var objTab = (typeof Tab === "object") ? Tab : document.getElementById(Tab);

	switch(objTab.innerHTML.trim()) {
		case "?":
			adjTabs('ulTabs_SystemConfiguration','liTab','liSel',objTab);
			switchBtns('ulButtons_SystemConfiguration');
			switchTabs('divTabs_SystemConfiguration',0,'https://wiki.cliquesoft.org/index.php?title=webBooks-System_Configuration');
			break;
		case "General":
			adjTabs('ulTabs_SystemConfiguration','liTab','liSel',objTab);
			switchBtns('ulButtons_SystemConfiguration','Save',"saveSystemConfiguration('req');");
			switchTabs('divTabs_SystemConfiguration',1);
			break;
		case "Modules":
			adjTabs('ulTabs_SystemConfiguration','liTab','liSel',objTab);
			switchBtns('ulButtons_SystemConfiguration');
			switchTabs('divTabs_SystemConfiguration',2);
			break;
		case "History":
			adjTabs('ulTabs_SystemConfiguration','liTab','liSel',objTab);
			switchBtns('ulButtons_SystemConfiguration');
			switchTabs('divTabs_SystemConfiguration',3);
			break;
		case "Hosted":
			adjTabs('ulTabs_SystemConfiguration','liTab','liSel',objTab);
			switchBtns('ulButtons_SystemConfiguration');
			switchTabs('divTabs_SystemConfiguration',4);
			break;
	}
}




// FUNCTIONALITY OF BUTTONS ON THE 'GENERAL' TAB

function saveSystemConfiguration(strAction) {
// saves all the information on the screen that is NOT associated with a multi-line combobox									MIGRATED

alert('DEPRECATED (saveSystemConfiguration) 2025/03/01');
return true;
	switch(strAction) {
		case "req":
// WARNING 2021/02/01: the inclusion of the form causes errors for some reason!
			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=save&target=system&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'formSystem_SystemConfiguration','','','','',"saveSystemConfiguration('succ');","saveSystemConfiguration('fail');","saveSystemConfiguration('busy');","saveSystemConfiguration('timeout');","saveSystemConfiguration('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			saveSystemConfiguration('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			saveSystemConfiguration('req');
			break;
		case "succ":
			// 'Header' icons
			if (document.getElementById('sSocialURI_SystemConfiguration') != '') {	// if there is a social URI, then...
				document.getElementById('imgSocial_Dashboard').src = "home/"+gbl_nameUser+"/imgs/webbooks.social.png";	// enable the icon
				_sUriSocial = document.getElementById('sSocialURI_SystemConfiguration').value;		// set the URI to the social interface
				document.getElementById('imgSocial_Dashboard').disabled = false;	// enable the social icon
			} else {
				document.getElementById('imgSocial_Dashboard').src = "home/"+gbl_nameUser+"/imgs/webbooks.social_disabled.png";	// disable the icon
				_sUriSocial = '';							// set the URI to a blank value
				document.getElementById('imgSocial_Dashboard').disabled = true;	// disable the social icon
			}

//			document.getElementById('ifProvider_Social').value = socialURI;		// update the 'Social' page URI
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function exportData_SystemConfiguration(strAction) {
// exports all the data for the company in a .sql file														MIGRATED

alert('DEPRECATED (exportData_SystemConfiguration) 2025/03/01');
return true;
	switch(strAction) {
		case "req":
			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=export&target=database&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'','','','','',"exportData_SystemConfiguration('succ');","exportData_SystemConfiguration('fail');","exportData_SystemConfiguration('busy');","exportData_SystemConfiguration('timeout');","exportData_SystemConfiguration('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			exportData_SystemConfiguration('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			exportData_SystemConfiguration('req');
			break;
		case "succ":
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function importData_SystemConfiguration(strAction,strFilename) {
// install a new module into webBooks from a local file														MIGRATED

alert('DEPRECATED (importData_SystemConfiguration) 2025/03/01');
return true;
	switch(strAction) {
		case "show":							// this shows the upload filedrop element so the data-to-be-imported can be uploaded
			document.getElementById('divImportDrop_SystemConfiguration').style.display = 'block';
			break;
		case "req":
			document.getElementById('divImportDrop_SystemConfiguration').style.display = 'none';
			alert("The data will now be imported. Do NOT attempt to upload another data set until this process has completed.");
			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=import&target=database&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&filename='+escape(strFilename),'','','','','',"importData_SystemConfiguration('succ',\""+strFilename+"\");","importData_SystemConfiguration('fail',\""+strFilename+"\");","importData_SystemConfiguration('busy',\""+strFilename+"\");","importData_SystemConfiguration('timeout',\""+strFilename+"\");","importData_SystemConfiguration('inactive',\""+strFilename+"\");");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			importData_SystemConfiguration('req',strFilename);
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			importData_SystemConfiguration('req',strFilename);
			break;
		case "succ":
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function archiveData_SystemConfiguration(strAction,strDate) {
// archives all the specified data from the database prior to the passed date for the company in a .sql file							MIGRATED

alert('DEPRECATED (archiveData_SystemConfiguration) 2025/03/01');
return true;
	switch(strAction) {
		case "req":
			if (document.getElementById('eArchive_SystemConfiguration').value == '') { return false; }	// if the user clicked the 'X' on the calendar popup, then exit this function

			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=archive&target=database&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&date='+escape(strDate),'','','','','',"archiveData_SystemConfiguration('succ','"+strDate+"');","archiveData_SystemConfiguration('fail','"+strDate+"');","archiveData_SystemConfiguration('busy','"+strDate+"');","archiveData_SystemConfiguration('timeout','"+strDate+"');","archiveData_SystemConfiguration('inactive','"+strDate+"');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			archiveData_SystemConfiguration('req',strDate);
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			archiveData_SystemConfiguration('req',strDate);
			break;
		case "succ":
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function loadAdmins_SystemConfiguration(strAction) {
// loads all the possible admins (employees, vendor contact, or provider contacts) depending on the admin type that is selected					DEPRECATED
	switch(strAction) {
		case "req":
			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=load&target=admins&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&type='+document.getElementById('sAdminType_SystemConfiguration').options[document.getElementById('sAdminType_SystemConfiguration').selectedIndex].value,'','','sAdminType_SystemConfiguration','','',"loadAdmins_SystemConfiguration('succ');","loadAdmins_SystemConfiguration('fail');","loadAdmins_SystemConfiguration('busy');","loadAdmins_SystemConfiguration('timeout');","loadAdmins_SystemConfiguration('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			loadAdmins_SystemConfiguration('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			loadAdmins_SystemConfiguration('req');
			break;
		case "succ":
			// 'Groups' tab data
			var a = XML.getElementsByTagName("admin");
			
			// add all the associated people into the admins list
			document.getElementById('nAdminList_SystemConfiguration').options.length = 0;	// remove any prior values from the list
			document.getElementById('sAdminEmail_SystemConfiguration').value = '';
			document.getElementById('nAdminWorkPhone_SystemConfiguration').value = '';
			document.getElementById('nAdminWorkExt_SystemConfiguration').value = '';
			document.getElementById('nAdminMobilePhone_SystemConfiguration').value = '';
			document.getElementById('bAdminMobileSMS_SystemConfiguration').checked = false;
			document.getElementById('bAdminMobileEmail_SystemConfiguration').checked = false;
			for (var I=0; I<a.length; I++)
				{ Add2List('nAdminList_SystemConfiguration', a[I].getAttribute('id'), a[I].getAttribute('name'), 1, 1, 0); }
			document.getElementById('nAdminList_SystemConfiguration').selectedIndex = -1;	// don't select anyone from the list so the user can do so
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function loadAdmin_SystemConfiguration(strAction) {
// loads all the information of selected admin (from the loadAdmins_SystemConfiguration call)									DEPRECATED
	switch(strAction) {
		case "req":
			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=load&target=admin&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&type='+document.getElementById('sAdminType_SystemConfiguration').options[document.getElementById('sAdminType_SystemConfiguration').selectedIndex].value+'&id='+document.getElementById('nAdminList_SystemConfiguration').options[document.getElementById('nAdminList_SystemConfiguration').selectedIndex].value,'','','nAdminList_SystemConfiguration','','',"loadAdmin_SystemConfiguration('succ');","loadAdmin_SystemConfiguration('fail');","loadAdmin_SystemConfiguration('busy');","loadAdmin_SystemConfiguration('timeout');","loadAdmin_SystemConfiguration('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			loadAdmin_SystemConfiguration('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			loadAdmin_SystemConfiguration('req');
			break;
		case "succ":
			// 'Groups' tab data
			var a = XML.getElementsByTagName("admin").item(0);
			
			// add all the associated people into the admins list
			document.getElementById('sAdminEmail_SystemConfiguration').value = a.getAttribute('email');
			document.getElementById('nAdminWorkPhone_SystemConfiguration').value = a.getAttribute('phone');
			document.getElementById('nAdminWorkExt_SystemConfiguration').value = a.getAttribute('ext');
			document.getElementById('nAdminMobilePhone_SystemConfiguration').value = a.getAttribute('mobile');
			document.getElementById('bAdminMobileSMS_SystemConfiguration').checked = (a.getAttribute("sms") == 0) ? false : true;
			document.getElementById('bAdminMobileEmail_SystemConfiguration').checked = (a.getAttribute("mail") == 0) ? false : true;
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function checkUpdates_SystemConfiguration(strAction) {
// saves all the information on the screen that is NOT associated with a multi-line combobox									MIGRATED
	switch(strAction) {
		case "req":
			document.getElementById('nUpdatesList_SystemConfiguration').options.length = 0;

			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=check&target=updates&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'','','btnDownloadUpdates_SystemConfiguration','','',"checkUpdates_SystemConfiguration('succ');","checkUpdates_SystemConfiguration('fail');","checkUpdates_SystemConfiguration('busy');","checkUpdates_SystemConfiguration('timeout');","checkUpdates_SystemConfiguration('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			checkUpdates_SystemConfiguration('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			checkUpdates_SystemConfiguration('req');
			break;
		case "succ":
			var u = XML.getElementsByTagName("update");
			for (var I=0; I<u.length; I++)
				{ Add2List('nUpdatesList_SystemConfiguration', u[I].getAttribute('module'), u[I].getAttribute('module').slice(0,-4).replace(/_/g, " "), 1, 1, 0); }
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function installUpdates_SystemConfiguration(strAction) {
// saves all the information on the screen that is NOT associated with a multi-line combobox
	switch(strAction) {
		case "req":
			if (document.getElementById('nUpdatesList_SystemConfiguration').selectedIndex == -1)
				{ alert("You must select at least one update to install from the list before completing this action."); return false; }

			// check if the 'core' software has been checked to install and prompt the user if so!
			for (var i=0; i<document.getElementById('nUpdatesList_SystemConfiguration').options.length; i++) {
				if (document.getElementById('nUpdatesList_SystemConfiguration').options[i].value == 'webbooks.tgz' && document.getElementById('nUpdatesList_SystemConfiguration').options[i].selected)
					{ if (! confirm("Installing the 'webBooks' update requires a refresh of the application which \nwill erase any unsaved information.  Are you sure you want to continue?")) {return false;} }
			}

			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=install&target=updates&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&nUpdatesList_SystemConfiguration='+escape(document.getElementById('nUpdatesList_SystemConfiguration').value),'','','btnInstallUpdates_SystemConfiguration','','',"installUpdates_SystemConfiguration('succ');","installUpdates_SystemConfiguration('fail');","installUpdates_SystemConfiguration('busy');","installUpdates_SystemConfiguration('timeout');","installUpdates_SystemConfiguration('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			installUpdates_SystemConfiguration('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			installUpdates_SystemConfiguration('req');
			break;
		case "succ":
			var u = XML.getElementsByTagName("update");
			for (var I=0; I<u.length; I++)
// UPDATED - 2025/03/28
//				{ ListRemove4('nUpdatesList_SystemConfiguration', 0, 'value', u[I].getAttribute('module')); }
				{ ListRemove('nUpdatesList_SystemConfiguration', 0, 'value', u[I].getAttribute('module')); }
// LEFT OFF - we need to do a refresh of the application after a ***WEBBOOKS*** core update!!!
			alert("The updates have been installed successfully!");
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}




// FUNCTIONALITY OF LISTBOXES ON THE 'MODULES' TAB

function loadGroup_SystemConfiguration(strAction) {
// loads the group name and icon info as well as the associated modules within the group									MIGRATED

alert('DEPRECATED (loadGroup) - 2025/03/04');
return true;

	switch(strAction) {
		case "req":
			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=load&target=group&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&groupID='+document.getElementById('nGroupList_SystemConfiguration').options[document.getElementById('nGroupList_SystemConfiguration').selectedIndex].value,'','','nGroupList_SystemConfiguration','','',"loadGroup_SystemConfiguration('succ');","loadGroup_SystemConfiguration('fail');","loadGroup_SystemConfiguration('busy');","loadGroup_SystemConfiguration('timeout');","loadGroup_SystemConfiguration('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			loadGroup_SystemConfiguration('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			loadGroup_SystemConfiguration('req');
			break;
		case "succ":
			// 'Groups' tab data
			var g = XML.getElementsByTagName("group");
			
			// add the group info to the top two textboxes
			document.getElementById('sGroupName_SystemConfiguration').value = g[0].getAttribute('name').replace('&amp;', '&');
			document.getElementById('sGroupIcon_SystemConfiguration').value = g[0].getAttribute('icon');
			
			document.getElementById('nIncludedList_SystemConfiguration').options.length = 0;	// blank out all the existing associated modules from the previous group
			for (var I=0; I<g.length; I++) {
				// add the modules of the first group ONLY to the list
				m = g[I].getElementsByTagName("module");
				for (var j=0; j<m.length; j++)
					{ Add2List('nIncludedList_SystemConfiguration', m[j].getAttribute('id'), m[j].getAttribute('name').replace('&amp;', '&'), 1, 1, 0); }
			}
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function clearGroup_SystemConfiguration() {
// clears the appropriate form objects so that a new record can be added to a multi-lined combobox								MIGRATED
alert('DEPRECATED (clearGroup) - 2025/03/04');
return true;
	document.getElementById('sGroupName_SystemConfiguration').value='';
	document.getElementById('sGroupIcon_SystemConfiguration').value='';
	document.getElementById('nGroupList_SystemConfiguration').selectedIndex=-1;
}


function newGroup_SystemConfiguration(strAction) {
// add a new group to the list																	MIGRATED

alert('DEPRECATED (newGroup) 2025/03/04');
return true;

	switch(strAction) {
		case "req":
			// used to help prevent duplicates from getting into the list
			if (document.getElementById('nGroupList_SystemConfiguration').selectedIndex != -1) {
				if (window.confirm('It appears that a group has been loaded for updating. Are you sure you want to create another that may be a possible duplicate of one that currently exists in the list?') == false)
					{ return false; }
			}

			if (document.getElementById('sGroupName_SystemConfiguration').value == '') { alert("You must specify the groups name before adding it to the list."); return false; }
			if (document.getElementById('sGroupIcon_SystemConfiguration').value == '') { alert("You must specify an associated icon with the new group before adding it to the list."); return false; }
			if (ListExists('nGroupList_SystemConfiguration','',document.getElementById('sGroupName_SystemConfiguration').value,0,0)) { alert("A group with that information has already been added to the list, please use a different name for the group."); return false; }

			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=new&target=group&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&name='+escape(document.getElementById('sGroupName_SystemConfiguration').value)+'&icon='+document.getElementById('sGroupIcon_SystemConfiguration').value,'','','btnGroupAdd_SystemConfiguration','','',"newGroup_SystemConfiguration('succ');","newGroup_SystemConfiguration('fail');","newGroup_SystemConfiguration('busy');","newGroup_SystemConfiguration('timeout');","newGroup_SystemConfiguration('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			newGroup_SystemConfiguration('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			newGroup_SystemConfiguration('req');
			break;
		case "succ":
			Add2List('nGroupList_SystemConfiguration',DATA['id'],document.getElementById('sGroupName_SystemConfiguration').value,1,1,0);
			selListbox('nGroupList_SystemConfiguration',DATA['id']);	// select the newly added item in the listbox
			loadGroup_SystemConfiguration('req');				// now load any associated modules with that group (which should be nothing at this point)

			reloadDashboard('req');						// now reload the dashboard groupings so the changes can be reflected
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function delGroup_SystemConfiguration(strAction) {
// delete the selected group (and associated modules)														MIGRATED

alert('DEPRECATED 2025/03/05');
return true;
	switch(strAction) {
		case "req":
			if (document.getElementById('nGroupList_SystemConfiguration').selectedIndex == -1) { alert("You must select a group to remove before continuing."); return false; }

			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=delete&target=group&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&groupID='+document.getElementById('nGroupList_SystemConfiguration').options[document.getElementById('nGroupList_SystemConfiguration').selectedIndex].value,'','','btnGroupDel_SystemConfiguration','','',"delGroup_SystemConfiguration('succ');","delGroup_SystemConfiguration('fail');","delGroup_SystemConfiguration('busy');","delGroup_SystemConfiguration('timeout');","delGroup_SystemConfiguration('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			delGroup_SystemConfiguration('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			delGroup_SystemConfiguration('req');
			break;
		case "succ":
			ListRemove('nGroupList_SystemConfiguration',0);
			if (document.getElementById('nGroupList_SystemConfiguration').options.length >= 0) { document.getElementById('nGroupList_SystemConfiguration').options.selectedIndex = 0; }	// select the first option in the list after the deletion
			loadGroup_SystemConfiguration('req');				// now load any associated modules with that group (which should be nothing at this point)

			reloadDashboard('req');						// now reload the dashboard groupings so the changes can be reflected
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function updateGroup_SystemConfiguration(strAction) {
// update the selected group name and icon info															MIGRATED

alert('DEPRECATED (clickTab_SystemConfiguration) 2025/03/01');
return true;
	switch(strAction) {
		case "req":
			// used to help prevent duplicates from getting into the list
			if (document.getElementById('nGroupList_SystemConfiguration').selectedIndex == -1) {
				alert("An existing group has not been loaded for updating, please use the '+' (Add) button.");
				return false;
			}

			if (document.getElementById('sGroupName_SystemConfiguration').value == '') { alert("You must specify the groups name before adding it to the list."); return false; }
			if (document.getElementById('sGroupIcon_SystemConfiguration').value == '') { alert("You must specify an associated icon with the new group before adding it to the list."); return false; }
			if (ListExists('nGroupList_SystemConfiguration','',document.getElementById('sGroupName_SystemConfiguration').value,0,1)) { alert("A group with that information has already been added to the list, please use a different name for the group."); return false; }

			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=update&target=group&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&groupID='+document.getElementById('nGroupList_SystemConfiguration').options[document.getElementById('nGroupList_SystemConfiguration').selectedIndex].value+'&name='+escape(document.getElementById('sGroupName_SystemConfiguration').value)+'&icon='+document.getElementById('sGroupIcon_SystemConfiguration').value,'','','btnGroupUpdate_SystemConfiguration','','',"updateGroup_SystemConfiguration('succ');","updateGroup_SystemConfiguration('fail');","updateGroup_SystemConfiguration('busy');","updateGroup_SystemConfiguration('timeout');","updateGroup_SystemConfiguration('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			updateGroup_SystemConfiguration('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			updateGroup_SystemConfiguration('req');
			break;
		case "succ":
			ListReplace2('nGroupList_SystemConfiguration','',document.getElementById('sGroupName_SystemConfiguration').value,1,0,0);

			reloadDashboard('req');						// now reload the dashboard groupings so the changes can be reflected
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function addGroupModule_SystemConfiguration(strAction) {
// add the selected installed module to the selected group (in the 'Installed Modules' listbox)									MIGRATED

alert('DEPRECATED (clickTab_SystemConfiguration) 2025/03/01');
return true;
	switch(strAction) {
		case "req":
			if (document.getElementById('nGroupList_SystemConfiguration').selectedIndex == -1) { alert("You must select one of the groups from the 'Module Groups' list before adding it to the 'Included Modules' list."); return false; }
			if (document.getElementById('nInstalledList_SystemConfiguration').selectedIndex == -1) { alert("You must select one of the modules from the 'Installed Modules' list before adding it to the 'Included Modules' list."); return false; }
			if (ListExists('nIncludedList_SystemConfiguration',document.getElementById('nInstalledList_SystemConfiguration').options[document.getElementById('nInstalledList_SystemConfiguration').selectedIndex].value,'',0,0)) { alert("That module has already been added to the list, please select a different one to add."); return false; }
			
			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=add&target=grpmod&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&groupID='+document.getElementById('nGroupList_SystemConfiguration').options[document.getElementById('nGroupList_SystemConfiguration').selectedIndex].value+'&moduleID='+document.getElementById('nInstalledList_SystemConfiguration').options[document.getElementById('nInstalledList_SystemConfiguration').selectedIndex].value,'','','btnInstalledInclude_SystemConfiguration','','',"addGroupModule_SystemConfiguration('succ');","addGroupModule_SystemConfiguration('fail');","addGroupModule_SystemConfiguration('busy');","addGroupModule_SystemConfiguration('timeout');","addGroupModule_SystemConfiguration('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			addGroupModule_SystemConfiguration('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			addGroupModule_SystemConfiguration('req');
			break;
		case "succ":
			Add2List('nIncludedList_SystemConfiguration',document.getElementById('nInstalledList_SystemConfiguration').options[document.getElementById('nInstalledList_SystemConfiguration').selectedIndex].value,document.getElementById('nInstalledList_SystemConfiguration').options[document.getElementById('nInstalledList_SystemConfiguration').selectedIndex].text,1,1,0);

			reloadDashboard('req');						// now reload the dashboard groupings so the changes can be reflected
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function delGroupModule_SystemConfiguration(strAction) {
// delete an included module from the selected group														MIGRATED

alert('DEPRECATED (delGroupModule_SystemConfiguration) 2025/03/01');
return true;
	switch(strAction) {
		case "req":
			if (document.getElementById('nGroupList_SystemConfiguration').selectedIndex == -1) { alert("You must select one of the groups from the 'Module Groups' list before continuing."); return false; }
			if (document.getElementById('nIncludedList_SystemConfiguration').selectedIndex == -1) { alert("You must select a module to remove from the selected group before continuing."); return false; }

			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=delete&target=grpmod&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&groupID='+document.getElementById('nGroupList_SystemConfiguration').options[document.getElementById('nGroupList_SystemConfiguration').selectedIndex].value+'&moduleID='+document.getElementById('nIncludedList_SystemConfiguration').options[document.getElementById('nIncludedList_SystemConfiguration').selectedIndex].value,'','','btnIncludedDel_SystemConfiguration','','',"delGroupModule_SystemConfiguration('succ');","delGroupModule_SystemConfiguration('fail');","delGroupModule_SystemConfiguration('busy');","delGroupModule_SystemConfiguration('timeout');","delGroupModule_SystemConfiguration('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			delGroupModule_SystemConfiguration('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			delGroupModule_SystemConfiguration('req');
			break;
		case "succ":
			ListRemove('nIncludedList_SystemConfiguration',0);

			reloadDashboard('req');						// now reload the dashboard groupings so the changes can be reflected
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function addModule_SystemConfiguration(strAction,strFilename) {
// install a new module into webBooks from a local file
	switch(strAction) {
		case "upload":							// this shows the upload filedrop element so the module can be uploaded
			document.getElementById('divModuleDrop_SystemConfiguration').style.display = 'block';
			break;
		case "req":
			document.getElementById('divModuleDrop_SystemConfiguration').style.display = 'none';
			alert("The module will now be installed. Do NOT attempt to install another one until this process has completed.");
			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=add&target=module&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&filename='+escape(strFilename),'','','','','',"addModule_SystemConfiguration('succ',\""+strFilename+"\");","addModule_SystemConfiguration('fail',\""+strFilename+"\");","addModule_SystemConfiguration('busy',\""+strFilename+"\");","addModule_SystemConfiguration('timeout',\""+strFilename+"\");","addModule_SystemConfiguration('inactive',\""+strFilename+"\");");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			addModule_SystemConfiguration('req',strFilename);
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			addModule_SystemConfiguration('req',strFilename);
			break;
		case "succ":
			Add2List('nInstalledList_SystemConfiguration',DATA['id'],DATA['name'],1,1,0);
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function delModule_SystemConfiguration(strAction) {				// LEFT OFF - this is not currently implemented here, but in webbooks.js via the footer icons
// uninstall an existing module from webBooks
	switch(strAction) {
		case "req":
			if (confirm("Would you like to retain the associated module data in the database?")) { var retainDB=1; } else { var retainDB=0; }	// should the module DB data be retained

			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=delete&target=module&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&moduleID='+document.getElementById('nInstalledList_SystemConfiguration').options[document.getElementById('nInstalledList_SystemConfiguration').selectedIndex].value+'&retainDB='+retainDB,'','','','','',"delModule_SystemConfiguration('succ');","delModule_SystemConfiguration('fail');","delModule_SystemConfiguration('busy');","delModule_SystemConfiguration('timeout');","delModule_SystemConfiguration('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			delModule_SystemConfiguration('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			delModule_SystemConfiguration('req');
			break;
		case "succ":
			ListRemove('nInstalledList_SystemConfiguration',0);
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function reloadDashboard(strAction) {
// refreshes the dashboard after a new group and/or module grouping has been made										MIGRATED

alert("DEPRECATED 2025/03/05");
return true;

   switch(strAction) {
	case "req":
		ajax(reqIO,4,'post',gbl_uriProject+"code/system_configuration.php",'action=reload&target=dashboard&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'','','','','',"reloadDashboard('succ');","reloadDashboard('fail');","reloadDashboard('busy');","reloadDashboard('timeout');","reloadDashboard('inactive');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		reloadDashboard('req');
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		reloadDashboard('req');
		break;
	case "succ":
		var temp, m, g = XML.getElementsByTagName("group");

		document.getElementById('ulDashboard').innerHTML = '';				// erase all the existing groups

		for (var I=0; I<g.length; I++) {
			// add each group and associated modules
			temp =	"<li><div class='Group' style='background: url(home/"+gbl_nameUser+"/imgs/"+g[I].getAttribute('icon')+") no-repeat;'>" +
		   	 	 "<ul class='Modules'>";

			m = g[I].getElementsByTagName("module");
			for (var j=0; j<m.length; j++)
				{ temp += "<li><a href='#' onClick=\"Module('focus',null,'"+m[j].getAttribute('name')+"');\" title='"+m[j].getAttribute('name')+"'><img src='home/"+gbl_nameUser+"/imgs/"+m[j].getAttribute('icon')+"' alt='"+m[j].getAttribute('name')+"' /></a>"; }

			temp += "</ul>" +
				"<label>"+g[I].getAttribute('name')+"</label>";

// LEFT OFF - couldn't get the pure JS to work correctly below, so substituted jQuery
			//var li = document.createElement('div');				// create a new <li> adding an icon to the opened modules section in the footer
			//li.className = 'liTab liSel';						// assign it the appropriate class
			//li.addEventListener('click',function(){"Module('focus',null,'"+strName+"'); adjTabs('ulOpened','liTab','liSel',this);"},false);			// http://stackoverflow.com/questions/1019078/how-to-set-onclick-attribute-with-value-containing-function-in-ie8
			//li.innerHTML = "<img title='"+strName+"' src='data/${UN}/_theme/images/webbooks."+strName.toLowerCase().replace(/ /g,'_')+".jpg' class='Screens' />";
			//document.getElementById('ulOpened').appendChild(li);			// add the <li> to the <ul>
			$("#ulDashboard").append(temp);
		}

		$('.Group').mobilyblocks({
			//trigger: 'hover',
			//direction: 'counter',
			//duration: 500,
			//zIndex: 50,
			widthMultiplier: 0.7
		});

		break;
	case "fail":
		// no reason to display anything because the server-side script will handle the message
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}




// FUNCTIONALITY OF LISTBOXES ON THE 'HOSTED' TAB

function calcCharges_SystemConfiguration() {
// calculates the charges based on what was input on the form
// http://stackoverflow.com/questions/1616724/jquery-using-ranges-in-switch-cases
	var support = parseInt(document.getElementById('nTechSupport_SystemConfiguration').options[document.getElementById('nTechSupport_SystemConfiguration').selectedIndex].value);
	var logins = parseInt(document.getElementById('nRequiredLogins_SystemConfiguration').value);
	var charge = 0.00;
	
	if (! logins || logins == 1) {
		charge = 0.00;
		if (support) { charge = charge + (10 * logins); }
	} else if (logins >= 2 && logins <= 10) {
		charge = + 2 * logins;
		if (support) { charge = charge + (9 * logins); }
	} else if (logins >= 11 && logins <= 50) {
		charge = 1.75 * logins;
		if (support) { charge = charge + (8 * logins); }
	} else if (logins >= 51 && logins <= 250) {
		charge = 1.5 * logins;
		if (support) { charge = charge + (7 * logins); }
	} else if (logins >= 251 && logins <= 1000) {
		charge = 1.25 * logins;
		if (support) { charge = charge + (6 * logins); }
	} else if (logins >= 1001) {
		charge = 1 * logins;
		if (support) { charge = charge + (5 * logins); }
	}
	
	document.getElementById('nCost_SystemConfiguration').value = addChange(charge, 2);
}


function saveHosted_SystemConfiguration(strAction) {
// saves the account adjustments for the hosted services website
	switch(strAction) {
		case "req":
			if (! document.getElementById('sAccessURI_SystemConfiguration').value.match('^[a-zA-Z0-9._\-]+$') ) { alert("The only characters allowed for the URI are [a-z, A-Z, 0-9, -, _, .], please correct this value before continuing."); return false; }
			if (document.getElementById('sAccessURI_SystemConfiguration').value == '') { alert("You must have a valid URI value in order to access your account."); return false; }
			if (parseInt(document.getElementById('nRequiredLogins_SystemConfiguration').value) == '') { alert("It appears that you have an invalid value for the number of logins, please correct this value before continuing."); return false; }
			if (parseInt(document.getElementById('nRequiredLogins_SystemConfiguration').value) == 0) { alert("You must have at least one active login, please correct this value before continuing."); return false; }
			var balance = parseFloat(document.getElementById('nBalance_SystemConfiguration').value) - parseFloat(document.getElementById('nCost_SystemConfiguration').value);
			if (balance < 0) { alert("It appears that your account does not currently have the funds to allow that configuration.  Please add funds to your account in the following section before saving these settings."); return false; }
			
			ajax(reqSystemConfiguration,4,'post',gbl_uriProject+"code/system_configuration.php",'action=save&target=hosted&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&logins='+document.getElementById('nRequiredLogins_SystemConfiguration').value+'&support='+document.getElementById('nTechSupport_SystemConfiguration').options[document.getElementById('nTechSupport_SystemConfiguration').selectedIndex].value+'&uri='+escape(document.getElementById('sAccessURI_SystemConfiguration').value),'','','btnWebfice_SystemConfiguration','','',"saveHosted_SystemConfiguration('succ');","saveHosted_SystemConfiguration('fail');","saveHosted_SystemConfiguration('busy');","saveHosted_SystemConfiguration('timeout');","saveHosted_SystemConfiguration('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			saveHosted_SystemConfiguration('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			saveHosted_SystemConfiguration('req');
			break;
		case "succ":
			if (DATA.hasOwnProperty('balance') === true)
				{ document.getElementById('nBalance_SystemConfiguration').value = DATA['balance']; }
			
			if (DATA.hasOwnProperty('uri') === true)
				{ window.location.replace(DATA['uri']); }	// NOTE: by using 'replace' the original URI doesn't get added to the history so pressing the browsers back button won't load the original URI	http://stackoverflow.com/questions/846954/change-url-and-redirect-using-jquery
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}




// MISCELLANEOUS FUNCTIONALITY OF THE MODULE




// AUTO-EXECUTE CALLS UPON THIS FILE BEING LOADED INTO THE DOM				see last answer on http://stackoverflow.com/questions/8586446/dynamically-load-external-javascript-file-and-wait-for-it-to-load-without-usi

//init_SystemConfiguration('req');
