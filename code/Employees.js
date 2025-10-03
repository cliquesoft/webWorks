// Employees.js
//
// Created	2014/01/30 by Dave Henderson (support@cliquesoft.org)
// Updated	2025/02/22 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.




// GLOBALS

var _oEmployees;					// used for this modules' AJAX communication
var _nEmployeesTimeout = 0;				// used to acknowledge the module's html/css has loaded so any preliminary initialization javascript calls can start
var _sEmployeesPrior = '';				// the prior action that was attempted but failed (e.g. the server was busy, so re-attempt previous action)




// -- Session API --

function Employees(sAction,Callback) {
// sAction	the available actions that this function can process: initialize (module)
// Callback	the callback to execute upon success; value can be a string or function()
	var HTML = "";

	switch(sAction) {
		// --- BUILT-IN ACTIONS ---	   (for base callback functionality of ajax)

		case "inactive":
			if (MESSAGE.toString() == "1") { var sType='popup'; }
			if (MESSAGE.toString() == "2") { var sType='notice'; }

			// if no message was passed, but we're supposed to report something, create a generic default
			if (MESSAGE.toString() == "1" || MESSAGE.toString() == "2") { MESSAGE = "There appears to be a delay in communcation. Please try again in a few moments."; }

			if (sType == 'notice') {				// show a small notice
				Application('notice',null,'fail');
			} else if (sType == 'popup') {
				// form the popup to display			   show a large popup
				HTML =	"<div id='divPopupClose' onClick=\"togglePopup('hide');\">&times;</div>" +
					"<h3>&nbsp;ERROR&nbsp;</h3>" +
					"<div class='divBody divBodyFull'>" +
					"	<ul>" +
					"		<li class='fleft'><img src='home/guest/imgs/webbooks.email_alert.png' />" +
					"		<li class='justify'>" + MESSAGE +
					"	</ul>" +
					"</div>";

				// display the popup to the user
				document.getElementById('divPopup').innerHTML = HTML;
			}
			MESSAGE = 0;						// turn this back off now that we're done
			return false;
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			Employees(_sEmployeesPrior,Callback);
			return false;
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			Employees(_sEmployeesPrior,Callback);
			return false;
			break;
		case "fail":
			if (MESSAGE.toString() == "1") { var sType='popup'; }
			if (MESSAGE.toString() == "2") { var sType='notice'; }

			// if no message was passed, but we're supposed to report something, create a generic default
			if (MESSAGE.toString() == "1" || MESSAGE.toString() == "2") { MESSAGE = "An error has occurred while attempting to perform that action. Please try again in a few minutes."; }

			if (sType == 'notice') {				// show a small notice
				Application('notice',null,'fail');
			} else if (sType == 'popup') {
				// form the popup to display			   show a large popup
				HTML =	"<div id='divPopupClose' onClick=\"togglePopup('hide');\">&times;</div>" +
					"<h3>&nbsp;ERROR&nbsp;</h3>" +
					"<div class='divBody divBodyFull'>" +
					"	<ul>" +
					"		<li class='fleft'><img src='home/guest/imgs/webbooks.email_alert.png' />" +
					"		<li class='justify'>" + MESSAGE +
					"	</ul>" +
					"</div>";

				// display the popup to the user
				document.getElementById('divPopup').innerHTML = HTML;
			}
			MESSAGE = 0;						// turn this back off now that we're done
			return false;
			break;


		// --- CUSTOM ACTIONS ---


		// Initializes the UI Values
		case "initialize":
			if (! document.getElementById('sName_Employees')) {	// if the HTML/css side hasn't loaded yet, then...
				if (_nEmployeesTimeout == 30) {			//   check if we've met the 30 second timeout threshold
					_nEmployeesTimeout == 0;		//   reset the value
					MESSAGE = "The HTML file for the module could not be loaded. Please contact support for assistance.";	// store the problem being encountered
					Employees('fail',Callback);		//   relay the error to the end user
					return false;				//   exit since we can't use this module under these conditions
				}

				// if we've made it here, the modules' html hasn't yet loaded, so let wait a second and try again
				_nEmployeesTimeout++;				// increase the timeout counter by one for each failed attempt
				setTimeout(Employees(sAction,Callback),1000);
				return false;					// in any case, exit this function to prevent JS problems!
			}

			ajax(_oEmployees,4,'post',_sUriProject+"code/Employees.php",'A='+sAction+'&T=values&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')),'','','','','',function(){Employees('s_'+sAction,Callback);},function(){Employees('fail',Callback);},function(){Employees('busy',Callback);},function(){Employees('timeout',Callback);},function(){Employees('inactive',Callback);});
			break;
		case "s_initialize":
			// Listbox value population on 'General' tab
			var l = XML.getElementsByTagName("location");
			for (var I=0; I<l.length; I++)
			   { Add2List('nWorkLocation_Employees', l[I].getAttribute("id"), l[I].getAttribute("name"), 1, 1, 0); }

			var d = XML.getElementsByTagName("dept");
			for (I=0; I<d.length; I++)
			   { Add2List('sDept_Employees', d[I].getAttribute("id"), d[I].getAttribute("name"), 1, 1, 0); }

			var s = XML.getElementsByTagName("supervisor");
			for (I=0; I<s.length; I++)
			   { Add2List('sSupervisor_Employees', s[I].getAttribute("id"), s[I].getAttribute("name"), 1, 1, 0); }

			var p = XML.getElementsByTagName("pos");
			for (I=0; I<p.length; I++)
			   { Add2List('sJobTitle_Employees', p[I].getAttribute("id"), p[I].getAttribute("name"), 1, 1, 0); }

			var e = XML.getElementsByTagName("person");
			for (I=0; I<e.length; I++)
			   { Add2List('nEmployeeList_Employees', e[I].getAttribute("id"), e[I].getAttribute("name"), 1, 1, 0); }

			var m = XML.getElementsByTagName("module");
			for (I=0; I<m.length; I++)
			   { Add2List('sModuleList_Employees', m[I].getAttribute("id"), m[I].getAttribute("name"), 1, 1, 0); }

			// Listbox value population on 'Looks' tab
			    s = XML.getElementsByTagName("skin");
			for (I=0; I<s.length; I++)
			   { Add2List('sSkinsList_Employees', s[I].firstChild.data, s[I].firstChild.data, 1, 1, 0); }

			var t = XML.getElementsByTagName("theme");
			for (I=0; I<t.length; I++)
			   { Add2List('sThemesList_Employees', t[I].firstChild.data, t[I].firstChild.data, 1, 1, 0); }

			var i = XML.getElementsByTagName("image");
			for (I=0; I<i.length; I++)
			   { Add2List('sIconsList_Employees', i[I].firstChild.data, i[I].firstChild.data, 1, 1, 0); }

			// 'Notes' tab data
			//    d = XML.getElementsByTagName("dept");
			for (I=0; I<d.length; I++)
			   { Add2List('sNoteAccess_Employees', d[I].getAttribute("id"), d[I].getAttribute("name"), 1, 1, 0); }

			//var date = new Date();				// Insert the current date on the 'Notes' tab
			//document.getElementById('divDate_Employees').innerHTML = (date.getYear()+1900) + '/' + (date.getMonth()+1) + '/' + date.getDate();
			document.getElementById('divCreator_Employees').innerHTML = getCookie('sUsername');

			// initialization of the FileDrop object
			initFileDrop('divFileDrop_Employees','','','data/_modules/Employees','',1,function(){initFileDrop('divFileDrop_Employees','','','data/_modules/Employees','',1)});


// REMOVED 2020/06/29 - initing the user account without an 'id' number is not possible
if (1 == 0) {
			// fill out all the form values with the users information
			fillEmployee_Employees();

			// enable any form objects now that the account has been loaded
// VER2
//			document.getElementById('objRequest_Employees').className=document.getElementById('objRequest_Employees').className.replace(/ disabled/g,'');
//			document.getElementById('objRequest_Employees').disabled = false;

			document.getElementById('btnCopyHomeAddr_Employees').className=document.getElementById('btnCopyHomeAddr_Employees').className.replace(/ disabled/g,'');
			document.getElementById('btnCopyHomeAddr_Employees').disabled = false;

			document.getElementById('btnDonate_Employees').className=document.getElementById('btnDonate_Employees').className.replace(/ disabled/g,'');
			document.getElementById('btnDonate_Employees').disabled = false;

//			document.getElementById('btnSaveAccess_Employees').className=document.getElementById('btnSaveAccess_Employees').className.replace(/ disabled/g,'');
//			document.getElementById('btnSaveAccess_Employees').disabled = false;

			$('#formAccess_Employees :input').removeClass('disabled');
			$('#formAccess_Employees :input').prop('disabled', false);

			$('#formLooks_Employees :input').removeClass('disabled');
			$('#formLooks_Employees :input').prop('disabled', false);

			$('#formNotes_Employees :input, #formNotes_Employees div').removeClass('disabled');
			$('#formNotes_Employees :input, #formNotes_Employees div').prop('disabled', false);
}

			// if the user selected the "I have read these" buttons, then there's no reason to display this moudules balloons
			if ((getCookie('noballoons') == undefined) ? 0 : 1 || getCookie('noballoons') == 1) { return true; }
			// Now show the init ballons to help the user identify basic functionality	NOTE: at this point, the initBalloons() will have been called from the webbooks.html file, so there should be an initial value for this module's initBalloons()
			if ((getCookie('balloons_Employees') == undefined) ? 1 : 0 || getCookie('balloons_Employees') == 1) {
				initBalloons_Employees(1);
			} else {
				var iBalloons = getCookie('balloons_Employees');
				if (iBalloons < 2) { initBalloons_Employees(parseInt(iBalloons[1])+1); }
			}
			break;




		// Loads a specific employee's account (admin)
		case "load":			// if you're an admin, you can load other employee accounts		EXAMPLES
		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
// LEFT OFF - the below needs to also allow for string 'id'/object which we'll obtain the value from
		   // arguments[2]		[number] the employee id						1234
			if (arguments[2] == 0) {				// if the '_NEW EMPLOYEE_' value was selected from the list
				var name = document.getElementById('sName_Employees').value;		// temporarily store the name that was just entered
				clearEmployee_Employees('req');				//   clear the form of any prior values
				document.getElementById('sName_Employees').value = name;		// restore it now that the form has been cleared
				return true;					//   exit now that we're done
			}
			
			// don't process this function if no value was passed
			if (document.getElementById('sName_Employees') == '') { return false; }

			ajax(_oEmployees,4,'post',_sUriProject+"code/Employees.php",'A='+sAction+'&T=account&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID'))+'&id='+arguments[2],'','','sName_Employees','','',function(){Employees('s_'+sAction,Callback,arguments[2]);},function(){Employees('fail',Callback,arguments[2]);},function(){Employees('busy',Callback,arguments[2]);},function(){Employees('timeout',Callback,arguments[2]);},function(){Employees('inactive',Callback,arguments[2]);});
			break;
		case "s_load":
			// hide the search box
			document.getElementById('lstMatches_Employees').style.display='none';

			// fill out all the form object values
			fillEmployee_Employees();
			
			// enable any form objects now that the account has been loaded
// VER2
//			document.getElementById('objRequest_Employees').className=document.getElementById('objRequest_Employees').className.replace(/ disabled/g,'');
//			document.getElementById('objRequest_Employees').disabled = false;

			document.getElementById('btnCopyHomeAddr_Employees').className=document.getElementById('btnCopyHomeAddr_Employees').className.replace(/ disabled/g,'');
			document.getElementById('btnCopyHomeAddr_Employees').disabled = false;

			document.getElementById('btnDonate_Employees').className=document.getElementById('btnDonate_Employees').className.replace(/ disabled/g,'');
			document.getElementById('btnDonate_Employees').disabled = false;
			
			//		document.getElementById('btnSaveAccess_Employees').className=document.getElementById('btnSaveAccess_Employees').className.replace(/ disabled/g,'');
			//		document.getElementById('btnSaveAccess_Employees').disabled = false;
			
			$('#formAccess_Employees :input').removeClass('disabled');
			$('#formAccess_Employees :input').prop('disabled', false);
			
			$('#formLooks_Employees :input').removeClass('disabled');
			$('#formLooks_Employees :input').prop('disabled', false);
			
			$('#formNotes_Employees :input, #formNotes_Employees div').removeClass('disabled');
			$('#formNotes_Employees :input, #formNotes_Employees div').prop('disabled', false);
			break;

	}
}












// LEGACY




//var Employees=0;					// used as the identify if the 'init' function has been executed
var reqEmployees;					// used for AJAX calls via interaction with the 'IO' pane itself




// INITIALIZATION FUNCTIONS

function init_Employees(strAction) {
// initializes the module by loading all of the form object values after the screen contents have been added
	Employees('initialize');

	//case "req":
	//	ajax(reqEmployees,4,'post',gbl_uriProject+"code/employees.php",'action=init&target=values&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'','','','','',"init_Employees('succ');","init_Employees('fail');","init_Employees('busy');","init_Employees('timeout');","init_Employees('inactive');");

}


function initBalloons_Employees(intStep) {
// controls if the 'welcome' balloons should be shown to a new user
	if (Mobile) { return true; }		// don't process any popups on mobile devices

	var balloon = document.getElementById('divBalloon');

	// reset the balloon parameters each run
	balloon.className='';
	balloon.style.left = 'auto';
	balloon.style.right = 'auto';
	balloon.style.top = 'auto';
	balloon.style.bottom = 'auto';
	balloon.style.marginLeft = 0;
	balloon.style.marginTop = 0;
	balloon.innerHTML = "";
	balloon.setAttribute("onclick", "$(this).fadeOut('slow');");

	if (intStep == 0) {			// blank out all the prior config parameters
		var balvals = (getCookie('balloons_Employees') == undefined) ? 1 : getCookie('balloons_Employees');
		setCookie('balloons_Employees', parseInt(balvals)+2);		// prevent the initial balloons from showing any longer by setting a cookie
		return true;
	} else if (intStep == 1) {
		balloon.className='balloonTL';
		balloon.style.left = '50%';
		balloon.style.top = '110px';
		balloon.style.marginLeft = '-300px';
		balloon.innerHTML = "You may have noticed that some of the fields have a red outline which indicates they have additional functionality. All of these objects are submission fields that you can use to pull up specific or matching records. To use, simply enter an exact (e.g. invoice number) or partial value (e.g. customer name) and press return.";
		balloon.setAttribute("onclick", "$(this).fadeOut('slow',function(){initBalloons_Employees(2)});");
	} else if (intStep == 2) {
		balloon.className='balloonTL';
		balloon.style.left = '50%';
		balloon.style.top = '182px';
		balloon.style.marginLeft = '-320px';
		balloon.innerHTML = "There are also some fields that have a green border which indicates that these values will be encrypted in the database to help prevent the confiscation of sensative information in the event of a malicious attack. For more information on how the data is encrypted, please refer to our online documentation.";
		balloon.setAttribute("onclick", "$(this).fadeOut('slow',function(){initBalloons_Employees(0)});");
	}

	// NOTE: the below line was modified to fade-in the very first balloon to match the other UI initialization
	if (intStep == 1) { $('#divBalloon').fadeIn(2000); } else { balloon.style.display = 'block'; }
}


function clickTab_Employee(Tab) {
// Makes form adjustments to show the content of the selected tab

alert('deprecated function - clickTab_Employee()');
return true;
	var objTab = (typeof Tab === "object") ? Tab : document.getElementById(Tab);
	switch(objTab.innerHTML.trim()) {
		case "?":
			adjTabs('ulTabs_Employees','liTab','liSel',objTab);
			switchBtns('ulButtons_Employees');
			switchTabs('divTabs_Employees',0,'https://wiki.cliquesoft.org/index.php?title=webBooks-Employees');
			break;
		case "General":
			adjTabs('ulTabs_Employees','liTab','liSel',objTab);
			switchBtns('ulButtons_Employees','Clear',"clearEmployee_Employees('req');",'Save',"saveEmployee_Employees('req');");
			switchTabs('divTabs_Employees',1);
			break;
		case "Looks":
			adjTabs('ulTabs_Employees','liTab','liSel',objTab);
			switchBtns('ulButtons_Employees');
			switchTabs('divTabs_Employees',2);
			break;
		case "Time":
			adjTabs('ulTabs_Employees','liTab','liSel',objTab);
			switchBtns('ulButtons_Employees');
			switchTabs('divTabs_Employees',3);
			break;
		case "Notes":
			adjTabs('ulTabs_Employees','liTab','liSel',objTab);
			switchBtns('ulButtons_Employees','Save',"if(document.getElementById('hidID_Employees').value=='0'){alert('You must load an employee account before adding a note.'); return false;} saveNote('req','Employee','Employees',document.getElementById('hidID_Employees').value);");
			switchTabs('divTabs_Employees',4);
			break;
		case "Data":
			adjTabs('ulTabs_Employees','liTab','liSel',objTab);
			switchBtns('ulButtons_Employees');
			switchTabs('divTabs_Employees',5);
			break;
	}}




// FUNCTIONALITY OF BUTTONS ON THE 'GENERAL' TAB

function loadEmployee_Employees(strAction,intEmployeeID) {
// loads all the employee information based on the selected item from search results
	Employees('load',null,intEmployeeID);

	//case "req":
	//	ajax(reqEmployees,4,'post',gbl_uriProject+"code/employees.php",'action=load&target=employee&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+intEmployeeID,'','','sEmployeeName_Employees','','',"loadEmployee_Employees('succ','"+intEmployeeID+"');","loadEmployee_Employees('fail','"+intEmployeeID+"');","loadEmployee_Employees('busy','"+intEmployeeID+"');","loadEmployee_Employees('timeout','"+intEmployeeID+"');","loadEmployee_Employees('inactive','"+intEmployeeID+"');");
}


function clearResults_Employees() {
// clears the search results box
	document.getElementById('sName_Employees').value='';
	document.getElementById('hidID_Employees').value='0';
	document.getElementById('lstMatches_Employees').style.display='none';
}


function clearEmployee_Employees(strAction) {
// reset the form so new employees data can be entered.
	// hide any search box
	document.getElementById('lstMatches_Employees').style.display='none';

	// reset the 'General' tab info
	document.getElementById('formEmployee_Employees').reset();

	document.getElementById('hidID_Employees').value = '0';

	document.getElementById('divCurrentLogin_Employees').innerHTML = '&nbsp;';
	document.getElementById('divLastLogin_Employees').innerHTML = '&nbsp;';
	//document.getElementById('divTPPHours_Employees').innerHTML = '&nbsp;';
	//document.getElementById('divYTDHours_Employees').innerHTML = '&nbsp;';
	document.getElementById('divTPPPay_Employees').innerHTML = '&nbsp;';
	document.getElementById('divYTDPay_Employees').innerHTML = '&nbsp;';
	document.getElementById('divTPPCom_Employees').innerHTML = '&nbsp;';
	document.getElementById('divYTDCom_Employees').innerHTML = '&nbsp;';
	document.getElementById('divTPPReimb_Employees').innerHTML = '&nbsp;';
	document.getElementById('divYTDReimb_Employees').innerHTML = '&nbsp;';
	document.getElementById('divTPPAPTO_Employees').innerHTML = '&nbsp;';
	document.getElementById('divYTDAPTO_Employees').innerHTML = '&nbsp;';
	document.getElementById('divTPPASick_Employees').innerHTML = '&nbsp;';
	document.getElementById('divYTDASick_Employees').innerHTML = '&nbsp;';
	document.getElementById('divTPPUPTO_Employees').innerHTML = '&nbsp;';
	document.getElementById('divYTDUPTO_Employees').innerHTML = '&nbsp;';
	document.getElementById('divTPPUSick_Employees').innerHTML = '&nbsp;';
	document.getElementById('divYTDUSick_Employees').innerHTML = '&nbsp;';
	document.getElementById('divTPPDPTO_Employees').innerHTML = '&nbsp;';
	document.getElementById('divYTDDPTO_Employees').innerHTML = '&nbsp;';
	document.getElementById('divTPPDSick_Employees').innerHTML = '&nbsp;';
	document.getElementById('divYTDDSick_Employees').innerHTML = '&nbsp;';
	document.getElementById('nEmployeeList_Employees').options.selectedIndex = -1;
	document.getElementById('nDonateAmount_Employee').value = '0';
	document.getElementById('sDonateType_Employees').options.length = 0;

	document.getElementById('chkRead_Employees').checked = false;
	document.getElementById('chkWrite_Employees').checked = false;
	document.getElementById('chkAddRecords_Employees').checked = false;
	document.getElementById('chkDelRecords_Employees').checked = false;
	document.getElementById('sModuleList_Employees').options.selectedIndex = -1;

	$('.question').hide('slow');

	// reset the 'Looks' tab info
	document.getElementById('sSkinsList_Employees').selectedIndex = -1;
	document.getElementById('sThemesList_Employees').selectedIndex = -1;
	document.getElementById('sIconsList_Employees').selectedIndex = -1;

	// delete all the 'Notes' tab info
	for(var i=document.getElementById("tblNotes_Employees").rows.length-1; i>=0; i--)
		{ document.getElementById("tblNotes_Employees").deleteRow(i); }

	// clear the 'Data' tab uploads
	var LIs = $('#divData_Employees .ulColData li').get();		// remove all the custom upload <li>'s
	for (var i=(LIs.length-1); i>5; i--)
		{ $('#divData_Employees .ulColData li').eq(i).remove(); }

	$('#divFileDrop_Employees .divFileDrop').remove();			// remove all the <div>'s in the filedrop parent container

	$('#sCustomFilename_Employees').empty()				// remove all the uploaded file <option>'s in the <select>
	$('#sEmployeePhoto_Employees').empty()
	$('#sSSNCard_Employees').empty()
	$('#sDriversLicenseID_Employees').empty()
	$('#sResume_Employees').empty()

	// disable sections of the module
	$('#formAccess_Employees :input').addClass('disabled');
	$('#formAccess_Employees :input').prop('disabled', true);

	$('#formLooks_Employees :input').addClass('disabled');
	$('#formLooks_Employees :input').prop('disabled', true);

	$('#formNotes_Employees :input, #formNotes_Employees div').addClass('disabled');
	$('#formNotes_Employees :input, #formNotes_Employees div').prop('disabled', true);
}


function saveEmployee_Employees(strAction) {
// initializes the module by loading all of the form object values after the screen contents have been added
   switch(strAction) {
	case "req":
		// WARNING: the additional check for the home and work address prevents the default values for the admin account from being saved
		if (document.getElementById('sName_Employees').value == '') { alert("You must specify the employees name before creating or updating the account."); return false; }
		if (document.getElementById('eHired_Employees').value == '') { alert("You must specify the hired date of the employee before creating or updating the account."); return false; }
		if (document.getElementById('nSSN_Employees').value == '') { alert("You must specify the social security number of the employee before creating or updating the account."); return false; }
		if (document.getElementById('eDOB_Employees').value == '') { alert("You must specify the date of birth for the employee before creating or updating the account."); return false; }
		if (document.getElementById('sHomeAddr1_Employees').value == '' || document.getElementById('sHomeAddr1_Employees').value == ' ') { alert("You must specify the line 1 of the home mailing address before creating or updating the account."); return false; }
		if (document.getElementById('sHomeCity_Employees').value == '' || document.getElementById('sHomeCity_Employees').value == ' ') { alert("You must specify the city of the home mailing address before creating or updating the account."); return false; }
		if (document.getElementById('sHomeState_Employees').value == '' || document.getElementById('sHomeState_Employees').value == ' ') { alert("You must specify the state of the home mailing address before creating or updating the account."); return false; }
		if (document.getElementById('sHomeZip_Employees').value == '' || document.getElementById('sHomeZip_Employees').value == ' ') { alert("You must specify the zip/postal code of the home mailing address before creating or updating the account."); return false; }
		if (document.getElementById('sWorkAddr1_Employees').value == '' || document.getElementById('sWorkAddr1_Employees').value == ' ') { alert("You must specify the line 1 of the work mailing address before creating or updating the account."); return false; }
		if (document.getElementById('sWorkCity_Employees').value == '' || document.getElementById('sWorkCity_Employees').value == ' ') { alert("You must specify the city of the work mailing address before creating or updating the account."); return false; }
		if (document.getElementById('sWorkState_Employees').value == '' || document.getElementById('sWorkState_Employees').value == ' ') { alert("You must specify the state of the work mailing address before creating or updating the account."); return false; }
		if (document.getElementById('sWorkZip_Employees').value == '' || document.getElementById('sWorkZip_Employees').value == ' ') { alert("You must specify the zip/postal code of the work mailing address before creating or updating the account."); return false; }
		if (document.getElementById('sUsername_Employees').value == '') { alert("You must specify the username for the employee before creating or updating the account."); return false; }
		if (document.getElementById('sPassword_Employees').value != document.getElementById('sConfirm_Employees').value) {
			alert("The passwords do NOT match, please re-enter them to make sure they are the same.");
			document.getElementById('sPassword_Employees').value = '';
			document.getElementById('sConfirm_Employees').value = '';
			document.getElementById('sPassword_Employees').focus();
			return false;
		}
		if (document.getElementById('txtQuestion01').value == '') { alert("You must specify the first security question before creating or updating the account."); return false; }
		if (document.getElementById('txtQuestion02').value == '') { alert("You must specify the second security question before creating or updating the account."); return false; }
		if (document.getElementById('txtQuestion03').value == '') { alert("You must specify the third security question before creating or updating the account."); return false; }
		if (document.getElementById('txtAnswer01').value == '') { alert("You must specify the first security question answer before creating or updating the account."); return false; }
		if (document.getElementById('txtAnswer02').value == '') { alert("You must specify the second security question answer before creating or updating the account."); return false; }
		if (document.getElementById('txtAnswer03').value == '') { alert("You must specify the third security question answer before creating or updating the account."); return false; }

		for (var key in DATA) { delete DATA[key]; }	// remove any prior values for the DATA object (to prevent incorrect triggering in the 'success' callback below)

		ajax(reqEmployees,4,'post',gbl_uriProject+"code/Employees.php",'action=save&target=employee&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'formEmployee_Employees','','','','',"saveEmployee_Employees('succ');","saveEmployee_Employees('fail');","saveEmployee_Employees('busy');","saveEmployee_Employees('timeout');","saveEmployee_Employees('inactive');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		saveEmployee_Employees('req');
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		saveEmployee_Employees('req');
		break;
	case "succ":
		// if <s><data>...</data></s> was returned, then we need to set cookies and form values (since we're not creating a new account [which shouldn't adjust those values!], plus the 'username' is only returned when editing their own account to update that value if necessary)
		if (DATA.hasOwnProperty('username') === true) {				// see: http://stackoverflow.com/questions/135448/how-do-i-check-to-see-if-an-object-has-a-property-in-javascript
		   document.getElementById('liUserAccount').innerHTML = document.getElementById('sName_Employees').value;
		   document.getElementById('hidUsername').value = DATA['username'];	// stores the logged in username
		   setCookie('username', DATA['username'], null, '/');			// these SHOULD be a session cookie!
		   setCookie('decrypt', DATA['decrypt'], null, '/');
		}
		document.getElementById('hidID_Employees').value = DATA['id'];		// stores the id of the account (store this no matter what)

		var date = new Date();				// Insert the current date on the 'Updated by' date field
		document.getElementById('divUpdated_Employees').innerHTML = (date.getYear()+1900) + '-' + (date.getMonth()+1) + '-' + date.getDate();

		// enabled sections of the module
		$('#formAccess_Employees :input').removeClass('disabled');
		$('#formAccess_Employees :input').prop('disabled', false);

		$('#formLooks_Employees :input').removeClass('disabled');
		$('#formLooks_Employees :input').prop('disabled', false);

		$('#formNotes_Employees :input, #formNotes_Employees div').removeClass('disabled');
		$('#formNotes_Employees :input, #formNotes_Employees div').prop('disabled', false);
		break;
	case "fail":
		// no reason to display anything because the server-side script will handle the message
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}


function fillEmployee_Employees() {
// this is a supplemental function to init_Employees and loadEmployee_Employees from above
	// 'General' tab data
	var e = XML.getElementsByTagName("employee").item(0);
	var h = XML.getElementsByTagName("address").item(0);
	var w = XML.getElementsByTagName("address").item(1);
	var p = XML.getElementsByTagName("payments").item(0);
	var l = XML.getElementsByTagName("leave").item(0);
	var d = XML.getElementsByTagName("donated").item(0);
	var Q = XML.getElementsByTagName("security")[0].getElementsByTagName("question");
	var A = XML.getElementsByTagName("security")[0].getElementsByTagName("answer");

	document.getElementById('hidID_Employees').value = e.getAttribute("id");
	document.getElementById('sName_Employees').value = e.getAttribute("name");
// VER2
//	document.getElementById('sOPoID_Employees').value = e.getAttribute("OPoID");
	document.getElementById('nHomePhone_Employees').value = e.getAttribute("homePhone");
	document.getElementById('nHomeMobile_Employees').value = e.getAttribute("homeMobile");
	document.getElementById('bHomeMobileSMS_Employees').checked = (e.getAttribute("homeMobileSMS") == 0) ? false : true;
	document.getElementById('bHomeMobileEmail_Employees').checked = (e.getAttribute("homeMobileEmail") == 0) ? false : true;
	document.getElementById('sHomeEmail_Employees').value = e.getAttribute("homeEmail");
	document.getElementById('sHomeAddr1_Employees').value = h.getAttribute("addr1");
	document.getElementById('sHomeAddr2_Employees').value = h.getAttribute("addr2");
	document.getElementById('sHomeCity_Employees').value = h.getAttribute("city");
	document.getElementById('sHomeState_Employees').value = h.getAttribute("state");
	document.getElementById('sHomeZip_Employees').value = h.getAttribute("zip");
	selListbox('sWorkCountry_Employees',h.getAttribute("country"));
	document.getElementById('nWorkPhone_Employees').value = e.getAttribute("workPhone");
	document.getElementById('nWorkExt_Employees').value = e.getAttribute("workExt");
	document.getElementById('nWorkMobile_Employees').value = e.getAttribute("workMobile");
	document.getElementById('bWorkMobileSMS_Employees').checked = (e.getAttribute("workMobileSMS") == 0) ? false : true;
	document.getElementById('bWorkMobileEmail_Employees').checked = (e.getAttribute("workMobileEmail") == 0) ? false : true;
	document.getElementById('sWorkEmail_Employees').value = e.getAttribute("workEmail");
	document.getElementById('sWorkAddr1_Employees').value = w.getAttribute("addr1");
	document.getElementById('sWorkAddr2_Employees').value = w.getAttribute("addr2");
	document.getElementById('sWorkCity_Employees').value = w.getAttribute("city");
	document.getElementById('sWorkState_Employees').value = w.getAttribute("state");
	document.getElementById('sWorkZip_Employees').value = w.getAttribute("zip");
	selListbox('sWorkCountry_Employees',w.getAttribute("country"));
	selListbox('nWorkLocation_Employees',e.getAttribute("location"));
	selListbox('sDept_Employees',e.getAttribute("department"));
	selListbox('sSupervisor_Employees',e.getAttribute("supervisor"));
	selListbox('sJobTitle_Employees',e.getAttribute("position"));
	selListbox('sPayTerms_Employees',e.getAttribute("payTerms"));
	selListbox('sPayType_Employees',e.getAttribute("payType"));
	document.getElementById('nStandardPay_Employees').value = e.getAttribute("basePay");
	document.getElementById('nOTPay_Employees').value = e.getAttribute("OTRate");
	document.getElementById('nPersonalLeave_Employees').value = e.getAttribute("PTORate");
	document.getElementById('nSickLeave_Employees').value = e.getAttribute("SickRate");
	document.getElementById('nCOLA_Employees').value = e.getAttribute("payCOLA");
	document.getElementById('nMileage_Employees').value = e.getAttribute("payMileage");
	document.getElementById('nPerDiem_Employees').value = e.getAttribute("payPerDiem");
	document.getElementById('eHired_Employees').value = e.getAttribute("hired");
	selListbox('nManager_Employees',e.getAttribute("manager"));
	document.getElementById('sDriversLicense_Employees').value = e.getAttribute("driversLicense");
	selListbox('sGender_Employees',e.getAttribute("gender"));
	document.getElementById('nSSN_Employees').value = e.getAttribute("ssn");
	document.getElementById('eDOB_Employees').value = e.getAttribute("dob");
	selListbox('sRace_Employees',e.getAttribute("race"));
	selListbox('sMarried_Employees',e.getAttribute("married"));
	document.getElementById('nWithheld_Employees').value = e.getAttribute("withholdings");
	document.getElementById('nAllowance_Employees').value = e.getAttribute("additional");
	document.getElementById('nDependents_Employees').value = e.getAttribute("dependents");
	document.getElementById('sUsername_Employees').value = e.getAttribute("username");
	selListbox('sAccountStatus_Employees',e.getAttribute("status"));
	document.getElementById('divInvalidLogins_Employees').innerHTML = e.getAttribute("attempts");
	document.getElementById('divCreated_Employees').innerHTML = e.getAttribute("created");
	document.getElementById('divUpdated_Employees').innerHTML = e.getAttribute("updated");
	document.getElementById('divCurrentLogin_Employees').innerHTML = e.getAttribute("login");
	document.getElementById('divLastLogin_Employees').innerHTML = e.getAttribute("logout");

	if (! Q[0].firstChild) { document.getElementById('lstQuestion01').selectedIndex = 0; } else {
		if (! selListbox('lstQuestion01',Q[0].firstChild.data.replace("\\'","'")))
			{ document.getElementById('lstQuestion01').selectedIndex = document.getElementById('lstQuestion01').options.length-1; }
	}
	if (document.getElementById('lstQuestion01').selectedIndex == document.getElementById('lstQuestion01').options.length-1) { document.getElementById('lstQuestion01').onchange(); }	// if 'Custom Question' was selected, then execute the code to show the associated textbox
	document.getElementById('txtQuestion01').value = (Q[0].firstChild ? Q[0].firstChild.data : document.getElementById('lstQuestion01').options[document.getElementById('lstQuestion01').selectedIndex].value);
	document.getElementById('txtAnswer01').value = (A[0].firstChild ? A[0].firstChild.data : '');

	if (! Q[1].firstChild) { document.getElementById('lstQuestion02').selectedIndex = 0; } else {
		if (! selListbox('lstQuestion02',Q[1].firstChild.data.replace("\\'","'")))
			{ document.getElementById('lstQuestion02').selectedIndex = document.getElementById('lstQuestion02').options.length-1; }
	}
	if (document.getElementById('lstQuestion02').selectedIndex == document.getElementById('lstQuestion02').options.length-1) { document.getElementById('lstQuestion02').onchange(); }
	document.getElementById('txtQuestion02').value = (Q[1].firstChild ? Q[1].firstChild.data : document.getElementById('lstQuestion02').options[document.getElementById('lstQuestion02').selectedIndex].value);
	document.getElementById('txtAnswer02').value = (A[1].firstChild ? A[1].firstChild.data : '');

	if (! Q[2].firstChild) { document.getElementById('lstQuestion03').selectedIndex = 0; } else {
		if (! selListbox('lstQuestion03',Q[2].firstChild.data.replace("\\'","'")))
			{ document.getElementById('lstQuestion03').selectedIndex = document.getElementById('lstQuestion03').options.length-1; }
	}
	if (document.getElementById('lstQuestion03').selectedIndex == document.getElementById('lstQuestion03').options.length-1) { document.getElementById('lstQuestion03').onchange(); }
	document.getElementById('txtQuestion03').value = (Q[2].firstChild ? Q[2].firstChild.data : document.getElementById('lstQuestion03').options[document.getElementById('lstQuestion03').selectedIndex].value);
	document.getElementById('txtAnswer03').value = (A[2].firstChild ? A[2].firstChild.data : '');

	document.getElementById('divTPPPay_Employees').innerHTML = p.getAttribute('PPPAP');
	document.getElementById('divYTDPay_Employees').innerHTML = p.getAttribute('YTDAP');
	document.getElementById('divTPPCom_Employees').innerHTML = p.getAttribute('PPPAC');
	document.getElementById('divYTDCom_Employees').innerHTML = p.getAttribute('YTDAC');
	document.getElementById('divTPPReimb_Employees').innerHTML = p.getAttribute('PPPPR');
	document.getElementById('divYTDReimb_Employees').innerHTML = p.getAttribute('YTDPR');
	
	document.getElementById('divTPPAPTO_Employees').innerHTML = l.getAttribute('PPPAP');
	document.getElementById('divYTDAPTO_Employees').innerHTML = l.getAttribute('YTDAP');
	document.getElementById('divTPPUPTO_Employees').innerHTML = l.getAttribute('PPPUP');
	document.getElementById('divYTDUPTO_Employees').innerHTML = l.getAttribute('YTDUP');
	document.getElementById('divTPPASick_Employees').innerHTML = l.getAttribute('PPPAS');
	document.getElementById('divYTDASick_Employees').innerHTML = l.getAttribute('YTDAS');
	document.getElementById('divTPPUSick_Employees').innerHTML = l.getAttribute('PPPUS');
	document.getElementById('divYTDUSick_Employees').innerHTML = l.getAttribute('YTDUS');
	
	document.getElementById('divTPPDPTO_Employees').innerHTML = d.getAttribute('PPPDP');
	document.getElementById('divYTDDPTO_Employees').innerHTML = d.getAttribute('YTDDP');
	document.getElementById('divTPPDSick_Employees').innerHTML = d.getAttribute('PPPDS');
	document.getElementById('divYTDDSick_Employees').innerHTML = d.getAttribute('YTDDS');
	
	// 'Looks' tab data
	l = XML.getElementsByTagName("look").item(0);
	selListbox('sSkinsList_Employees',l.getAttribute("skin"));
	selListbox('sThemesList_Employees',l.getAttribute("theme"));
	selListbox('sIconsList_Employees',l.getAttribute("icons"));
	
	// 'Notes' tab data
	var n = XML.getElementsByTagName("note");
	t = document.getElementById('tblNotes_Employees');
	for (I=0; I<n.length; I++) {
		var r = t.insertRow(-1);							// Insert a row in the second position from the top (since the first is the input section)
		
		// Insert the cells in the row
		var c0 = r.insertCell(0);
		var c1 = r.insertCell(1);
		var c2 = r.insertCell(2);
		
		// Append a text to the appropriate cell
		var t0 = document.createTextNode(n[I].getAttribute("updated"));
		var t1 = document.createTextNode(n[I].getAttribute("creator"));
		
		c0.className = 'thDateLong center';
		c0.appendChild(t0);
		c1.className = 'thName';
		c1.appendChild(t1);
		c2.className = 'thDesc';
		c2.innerHTML = n[I].firstChild.data.replace(/\r\n|\r|\n/g,'<br />');		// WARNING: createTextNode does NOT handle HTML formatting, so we use innerHTML here instead!
	}
	
	// 'Time' tab data
	var R = XML.getElementsByTagName("record");
	t = document.getElementById('tblTime_Employees');
	for (I=0; I<R.length; I++) {				// adds all the uploaded files to all the <select>
		var r = t.insertRow(-1);							// Insert a row in the second position from the top (since the first is the input section)
		
		// Insert the cells in the row
		var c0 = r.insertCell(0);
		var c1 = r.insertCell(1);
		var c2 = r.insertCell(2);
		
		// Append a text to the appropriate cell
		//var t0 = document.createTextNode(R[I].getAttribute("createdBy"));
		var t1 = document.createTextNode(R[I].getAttribute("type"));
		var t2 = document.createTextNode(R[I].getAttribute("occurred"));
		
		c0.className = 'thName';
		c0.innerHTML = "<input type='radio' name='radTimeID_Employees' value='"+R[I].getAttribute("id")+"' onClick=\"loadTime_Employees('req','"+R[I].getAttribute("id")+"');\" /> "+R[I].getAttribute("createdBy");
		c1.className = 'thType';
		c1.appendChild(t1);
		c2.className = 'thDate';
		c2.appendChild(t2);
	}
	
	// 'Data' tab data
	var e = XML.getElementsByTagName("entry");
	f = XML.getElementsByTagName("file");
	for (I=0; I<f.length; I++) {				// adds all the uploaded files to all the <select>
		// add the selectable files to the 'template' <select> (which will automatically be added as the dynamic <select>'s are created below)
		Add2List('sCustomFilename_Employees', f[I].getAttribute('filename'), f[I].getAttribute('filename'), 1, 1, 0);
		
		// add the selectable files to the static <select>
		Add2List('sEmployeePhoto_Employees', f[I].getAttribute('filename'), f[I].getAttribute('filename'), 1, 1, 0);
		Add2List('sSSNCard_Employees', f[I].getAttribute('filename'), f[I].getAttribute('filename'), 1, 1, 0);
		Add2List('sDriversLicenseID_Employees', f[I].getAttribute('filename'), f[I].getAttribute('filename'), 1, 1, 0);
		Add2List('sResume_Employees', f[I].getAttribute('filename'), f[I].getAttribute('filename'), 1, 1, 0);
	}
	
	// de-select any filename for the static <select>
	document.getElementById('sEmployeePhoto_Employees').selectedIndex = -1;
	document.getElementById('sSSNCard_Employees').selectedIndex = -1;
	document.getElementById('sDriversLicenseID_Employees').selectedIndex = -1;
	document.getElementById('sResume_Employees').selectedIndex = -1;
	
	var LIs = $('#divTabs_Employees5 .ulColData li').get();
	for (I=0; I<e.length; I++) {				// adds all the dynamic <li>'s to the list
		// handle all the static <select>'s (e.g. Customer Logo)
		if (e[I].getAttribute("title") == "Employee Photo") {
			LIs[2].innerHTML = LIs[2].innerHTML.replace(/,0\);/, ','+e[I].getAttribute("id")+');');	// update the passed DB 'id' value to use when communicating with the server
			selListbox('sEmployeePhoto_Employees',e[I].getAttribute("filename"));		// WARNING: this line MUST come below the above line (since it overwrites the value set here)!
			continue;
		} else if (e[I].getAttribute("title") == "Social Security Card") {
			LIs[3].innerHTML = LIs[3].innerHTML.replace(/,0\);/, ','+e[I].getAttribute("id")+');');
			selListbox('sSSNCard_Employees',e[I].getAttribute("filename"));
			continue;
		} else if (e[I].getAttribute("title") == "Drivers License") {
			LIs[4].innerHTML = LIs[4].innerHTML.replace(/,0\);/, ','+e[I].getAttribute("id")+');');
			selListbox('sDriversLicenseID_Employees',e[I].getAttribute("filename"));
			continue;
		} else if (e[I].getAttribute("title") == "Employee Resume") {
			LIs[5].innerHTML = LIs[5].innerHTML.replace(/,0\);/, ','+e[I].getAttribute("id")+');');
			selListbox('sResume_Employees',e[I].getAttribute("filename"));
			continue;
		}
		
		// handle all the dynamic <select>'s (e.g. Video Drivers)
		var index = addDataFields('divTabs_Employees5',e[I].getAttribute("id"));
		document.getElementById('sCustomFileTitle'+index+'_Employees').value = e[I].getAttribute("title");
		selListbox('sCustomFilename'+index+'_Employees',e[I].getAttribute("filename"));
	}
}


function loadPositions_Employees(strAction) {
// updates the positions in the listbox when the department is changed
	switch(strAction) {
		case "req":
			ajax(reqEmployees,4,'post',gbl_uriProject+"code/Employees.php",'action=load&target=positions&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&deptID='+document.getElementById('sDept_Employees').options[document.getElementById('sDept_Employees').selectedIndex].value,'','','sDept_Employees','','',"loadPositions_Employees('succ');","loadPositions_Employees('fail');","loadPositions_Employees('busy');","loadPositions_Employees('timeout');","loadPositions_Employees('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			loadPositions_Employees('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			loadPositions_Employees('req');
			break;
		case "succ":
			var p = XML.getElementsByTagName("pos");
			
			document.getElementById('sJobTitle_Employees').options.length = 0;	// remove any prior values
			for (var I=0; I<p.length; I++)
			{ Add2List('sJobTitle_Employees', p[I].getAttribute("id"), p[I].getAttribute("name"), 1, 1, 0); }
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function loadPositionInfo_Employees(strAction) {
// puts the associated position information (e.g. Base Pay, OT Rate, etc) in the form objects when this value changes.
// NOTE: if the original position is selected, their current values are put back instead of the default values associated with the position.
	switch(strAction) {
		case "req":
			ajax(reqEmployees,4,'post',gbl_uriProject+"code/Employees.php",'action=load&target=position&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&posID='+document.getElementById('sJobTitle_Employees').options[document.getElementById('sJobTitle_Employees').selectedIndex].value,'','','sJobTitle_Employees','','',"loadPositionInfo_Employees('succ');","loadPositionInfo_Employees('fail');","loadPositionInfo_Employees('busy');","loadPositionInfo_Employees('timeout');","loadPositionInfo_Employees('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			loadPositionInfo_Employees('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			loadPositionInfo_Employees('req');
			break;
		case "succ":
			var p = XML.getElementsByTagName("pos").item(0);
			
			selListbox('sPayTerms_Employees',p.getAttribute("payTerms"));
			selListbox('sPayType_Employees',p.getAttribute("payType"));
			document.getElementById('nStandardPay_Employees').value = p.getAttribute("basePay");
			document.getElementById('nOTPay_Employees').value = p.getAttribute("OTRate");
			document.getElementById('nPersonalLeave_Employees').value = p.getAttribute("PTORate");
			document.getElementById('nSickLeave_Employees').value = p.getAttribute("SickRate");
			document.getElementById('nCOLA_Employees').value = p.getAttribute("payCOLA");
			document.getElementById('nMileage_Employees').value = p.getAttribute("payMileage");
			document.getElementById('nPerDiem_Employees').value = p.getAttribute("payPerDiem");
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function donateTime_Employees(strAction) {
// donates the time from one employee to another
   switch(strAction) {
	case "req":
		if (document.getElementById('hidID_Employees').value=='0') { alert('You must load an employee account before modifying the access.'); return false; }
		if (document.getElementById('nDonateAmount_Employee').value < 1) { alert("You must donate at least one hour."); return false; }

		ajax(reqEmployees,4,'post',gbl_uriProject+"code/Employees.php",'action=donate&target=time&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&sourceID='+document.getElementById('hidID_Employees').value+'&targetID='+document.getElementById('nEmployeeList_Employees').options[document.getElementById('nEmployeeList_Employees').selectedIndex].value+'&type='+document.getElementById('sDonateType_Employees').options[document.getElementById('sDonateType_Employees').selectedIndex].value+'&hours='+document.getElementById('nDonateAmount_Employee').value,'','','btnDonate_Employees','','',"donateTime_Employees('succ');","donateTime_Employees('fail');","donateTime_Employees('busy');","donateTime_Employees('timeout');","donateTime_Employees('inactive');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		donateTime_Employees('req');
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		donateTime_Employees('req');
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


function loadAccess_Employees(strAction) {
// marks the associated checkboxes for the access that the user account has with the selected module
	switch(strAction) {
		case "req":
			ajax(reqEmployees,4,'post',gbl_uriProject+"code/Employees.php",'action=load&target=access&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&moduleID='+document.getElementById('sModuleList_Employees').options[document.getElementById('sModuleList_Employees').selectedIndex].value+'&accountID='+document.getElementById('hidID_Employees').value,'','','sModuleList_Employees','','',"loadAccess_Employees('succ');","loadAccess_Employees('fail');","loadAccess_Employees('busy');","loadAccess_Employees('timeout');","loadAccess_Employees('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			loadAccess_Employees('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			loadAccess_Employees('req');
			break;
		case "succ":
			var m = XML.getElementsByTagName("module").item(0);
			
			if (m != null) {				// if there is a record in the DB, then mark accordingly
				//if (m.hasAttribute("read") === true) {	// if there is a record in the DB, then mark accordingly
				document.getElementById('chkRead_Employees').checked = (m.getAttribute("read") == 0) ? false : true;
				document.getElementById('chkWrite_Employees').checked = (m.getAttribute("write") == 0) ? false : true;
				document.getElementById('chkAddRecords_Employees').checked = (m.getAttribute("add") == 0) ? false : true;
				document.getElementById('chkDelRecords_Employees').checked = (m.getAttribute("del") == 0) ? false : true;
			} else {
				document.getElementById('chkRead_Employees').checked = false;
				document.getElementById('chkWrite_Employees').checked = false;
				document.getElementById('chkAddRecords_Employees').checked = false;
				document.getElementById('chkDelRecords_Employees').checked = false;
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


function updateAccess_Employees(strAction) {
// update the selected module access for the loaded employee account
   switch(strAction) {
	case "req":
		if (document.getElementById('hidID_Employees').value=='0') { alert('You must load an employee account before modifying the access.'); return false; }
		if (document.getElementById('sModuleList_Employees').selectedIndex == -1) { alert("You must select a module before modifying the employee access."); return false; }

		ajax(reqEmployees,4,'post',gbl_uriProject+"code/Employees.php",'action=update&target=access&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&accountID='+document.getElementById('hidID_Employees').value,'formAccess_Employees','','btnSaveAccess_Employees','','',"updateAccess_Employees('succ');","updateAccess_Employees('fail');","updateAccess_Employees('busy');","updateAccess_Employees('timeout');","updateAccess_Employees('inactive');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		updateAccess_Employees('req');
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		updateAccess_Employees('req');
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




// FUNCTIONALITY OF BUTTONS ON THE 'LOOKS' TAB

function updateLooks_Employees(strAction,strType) {
// changes the type (skin, theme, icon set) of look based on the listbox selection
   switch(strAction) {
	case "req":
		if (document.getElementById('hidID_Employees').value=='0') {
			alert('You must load an employee account before modifying the looks.');
			document.getElementById('sSkinsList_Employees').options.selectedIndex = -1;
			document.getElementById('sThemesList_Employees').options.selectedIndex = -1;
			document.getElementById('sIconsList_Employees').options.selectedIndex = -1;
			return false;
		}
		if (! confirm("Updating the looks requires a refresh of the application which will \nerase any unsaved information.  Are you sure you want to continue?")) {
			setTimeout("document.getElementById('sIconsList_Employees').selectedIndex = document.getElementById('sIconsList_Employees').priorIndex;", 500);		// http://stackoverflow.com/questions/1909992/how-to-get-old-value-with-onchange-event-in-text-box
			return 0;
		}

		ajax(reqEmployees,4,'post',gbl_uriProject+"code/Employees.php",'action=update&target='+strType.toLowerCase()+'&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'formLooks_Employees','','s'+strType+'List_Employees','','',"updateLooks_Employees('succ','"+strType+"');","updateLooks_Employees('fail','"+strType+"');","updateLooks_Employees('busy','"+strType+"');","updateLooks_Employees('timeout','"+strType+"');","updateLooks_Employees('inactive','"+strType+"');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		updateLooks_Employees('req',strType);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		updateLooks_Employees('req',strType);
		break;
	case "succ":
		location.reload(true);			// http://stackoverflow.com/questions/2099201/javascript-hard-refresh-of-current-page
		break;
	case "fail":
		// no reason to display anything because the server-side script will handle the message
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}




// FUNCTIONALITY OF BUTTONS ON THE 'TIME' TAB

function loadTime_Employees(strAction,intID) {
// loads the selected time record for the loaded employee
	switch(strAction) {
		case "req":
			document.getElementById('formTime_Employees').reset();		// reset the form so the right section gets the returned data
			
			ajax(reqEmployees,4,'post',gbl_uriProject+"code/Employees.php",'action=load&target=time&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+intID,'','','','','',"loadTime_Employees('succ',"+intID+");","loadTime_Employees('fail',"+intID+");","loadTime_Employees('busy',"+intID+");","loadTime_Employees('timeout',"+intID+");","loadTime_Employees('inactive',"+intID+");");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			loadTime_Employees('req',intID);
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			loadTime_Employees('req',intID);
			break;
		case "succ":
			var R = XML.getElementsByTagName("record");
			var date = R[0].getAttribute("occurred").substr(0,10);
			var hour = R[0].getAttribute("occurred").substr(11,2);
			var mins = R[0].getAttribute("occurred").substr(14,2);
			var ampm = (hour < 12) ? "am" : "pm";
			hour = hour % 12 || 12;				// WARNING: this MUST come as the last statment since it overwrites the variable value that is used in other assignments above
			
			// if the time record was created by the user (e.g. PTO) -AND- it hasn't been modified by a supervisor, then...
			//			if (R[0].getAttribute("createdID") == document.getElementById('hidID_Employees').value && R[0].getAttribute("createdID") == R[0].getAttribute("updatedID")) {
			//				if (R[0].hasAttribute("time") === false) {	// this is so that the employee can adjust the record as a whole, not one portion (the first 'pto|sick' or last 'out'); the supervisor can edit either record independently
			//					alert("The only records that can be adjusted have a type of 'pto' or 'sick'.");
			//					return false;
			//				}
			//			}
			document.getElementById('eLeaveDate_Employees').value = date;
			document.getElementById('nLeaveHour_Employees').value = hour;
			document.getElementById('nLeaveMin_Employees').value = mins;
			selListbox('sLeaveHalf_Employees', ampm);
			if (R[0].hasAttribute("time") === true)
			{ document.getElementById('eLeaveHours_Employees').value = addChange((parseInt(R[0].getAttribute("time"))/60), 2); }	// convert into hours (from minutes that's returned from the server)
			else
			{ document.getElementById('eLeaveHours_Employees').value = ''; }
			if (R[0].firstChild != null)
			{ document.getElementById('sLeaveMemo_Employees').value = R[0].firstChild.data; }
			else
			{ document.getElementById('sLeaveMemo_Employees').value = ''; }
			selListbox('sLeaveType_Employees', R[0].getAttribute("type"));
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function delTime_Employees(strAction) {
// deletes the selected time record from the employees timesheet
	switch(strAction) {
		case "req":
			if (! document.querySelector('[name="radTimeID_Employees"]:checked')) { alert("You must select a timesheet record below before it can be deleted."); return false; }
			document.getElementById('formTime_Employees').reset();		// reset the form so the right section gets the returned data
			
			ajax(reqEmployees,4,'post',gbl_uriProject+"code/Employees.php",'action=delete&target=work&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+document.querySelector('[name="radTimeID_Employees"]:checked').value+'&direction='+document.querySelector('[name="bFilterWorkDate_Employees"]:checked').value+'&date='+document.getElementById('eFilterWorkDate_Employees').value,'','','btnDelWork_Employees','','',"delTime_Employees('succ');","delTime_Employees('fail');","delTime_Employees('busy');","delTime_Employees('timeout');","delTime_Employees('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			delTime_Employees('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			delTime_Employees('req');
			break;
		case "succ":
			// delete existing time list...
			for(var i=document.getElementById("tblTime_Employees").rows.length-1; i>=3; i--)
				{ document.getElementById("tblTime_Employees").deleteRow(i); }
			
			// add the filtered time list...
			var R = XML.getElementsByTagName("record");
			var t = document.getElementById('tblTime_Employees');
			for (I=0; I<R.length; I++) {
				var r = t.insertRow(-1);						// Insert a row in the last position
				
				// Insert the cells in the row
				var c0 = r.insertCell(0);
				var c1 = r.insertCell(1);
				var c2 = r.insertCell(2);
				
				// Append a text to the appropriate cell
				//var t0 = document.createTextNode(R[I].getAttribute("createdBy"));
				var t1 = document.createTextNode(R[I].getAttribute("type"));
				var t2 = document.createTextNode(R[I].getAttribute("occurred"));
				
				c0.className = 'thName';
				c0.innerHTML = "<input type='radio' name='radTimeID_Employees' value='"+R[I].getAttribute("id")+"' onClick=\"loadTime_Employees('req','"+R[I].getAttribute("id")+"');\" /> "+R[I].getAttribute("createdBy");
				c1.className = 'thType';
				c1.appendChild(t1);
				c2.className = 'thDate';
				c2.appendChild(t2);
			}
			
			alert('The timesheet record has been deleted successfully!');
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function updateTime_Employees(strAction) {
// adjusts the selected timesheet record
	switch(strAction) {
		case "req":
			if (! document.querySelector('[name="radTimeID_Employees"]:checked')) {
				if (document.getElementById('eLeaveDate_Employees').value == '') { alert("You must either select a timesheet record to modify or provide the date of occurrence before creating a new record."); return false; }
				if (document.getElementById('nLeaveHour_Employees').value == '') { alert("You must either select a timesheet record to modify or provide the hour of occurrence before creating a new record."); return false; }
				if (document.getElementById('nLeaveMin_Employees').value == '') { alert("You must either select a timesheet record to modify or provide the minute of occurrence before creating a new record."); return false; }
				
				var radioID = 0;					// set the value of the selected timesheet record to be passed to the server (0 = create a record instead of updating one)
				var cmbType = document.getElementById('sLeaveType_Employees');
				if (document.getElementById('eLeaveHours_Employees').value == '' && cmbType.options[cmbType.selectedIndex].value != 'in' && cmbType.options[cmbType.selectedIndex].value != 'out') { alert("You must either select a timesheet record to modify or provide the number of leave hours before creating a new record."); return false; }
			} else { var radioID = document.querySelector('[name="radTimeID_Employees"]:checked').value; }
			
			ajax(reqEmployees,4,'post',gbl_uriProject+"code/Employees.php",'action=update&target=work&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+radioID+'&direction='+document.querySelector('[name="bFilterWorkDate_Employees"]:checked').value+'&date='+document.getElementById('eFilterWorkDate_Employees').value+'&employee='+document.getElementById('hidID_Employees').value,'formTime_Employees','','btnLeaveSave_Employees','','',"updateTime_Employees('succ');","updateTime_Employees('fail');","updateTime_Employees('busy');","updateTime_Employees('timeout');","updateTime_Employees('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			updateTime_Employees('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			updateTime_Employees('req');
			break;
		case "succ":
			// delete existing time list...
			for(var i=document.getElementById("tblTime_Employees").rows.length-1; i>=3; i--)
			{ document.getElementById("tblTime_Employees").deleteRow(i); }
			
			// add the filtered time list...
			var R = XML.getElementsByTagName("record");
			var t = document.getElementById('tblTime_Employees');
			for (I=0; I<R.length; I++) {
				var r = t.insertRow(-1);						// Insert a row in the last position
				
				// Insert the cells in the row
				var c0 = r.insertCell(0);
				var c1 = r.insertCell(1);
				var c2 = r.insertCell(2);
				
				// Append a text to the appropriate cell
				//var t0 = document.createTextNode(R[I].getAttribute("createdBy"));
				var t1 = document.createTextNode(R[I].getAttribute("type"));
				var t2 = document.createTextNode(R[I].getAttribute("occurred"));
				
				c0.className = 'thName';
				c0.innerHTML = "<input type='radio' name='radTimeID_Employees' value='"+R[I].getAttribute("id")+"' onClick=\"loadTime_Employees('req','"+R[I].getAttribute("id")+"');\" /> "+R[I].getAttribute("createdBy");
				c1.className = 'thType';
				c1.appendChild(t1);
				c2.className = 'thDate';
				c2.appendChild(t2);
			}
			
			document.getElementById('formTime_Employees').reset();		// reset the form so the right section gets the returned data
			alert("The timesheet record has been updated successfully!");
			break;
		case "fail":
			// no reason to display anything because the server-side script will handle the message
			break;
		case "inactive":
			// no reason to display anything because this section isn't applicable to this function
			break;
	}
}


function filterTimeRecords_Employees(strAction) {
// used to filter the timesheet of the employee based on the entered date
	switch(strAction) {
		case "req":
			if (document.getElementById('eFilterWorkDate_Employees').value == '') { return false; }
			document.getElementById('formTime_Employees').reset();		// reset the form so the right section gets the returned data

			ajax(reqEmployees,4,'post',gbl_uriProject+"code/Employees.php",'action=filter&target=work&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&direction='+document.querySelector('[name="bFilterWorkDate_Employees"]:checked').value+'&date='+document.getElementById('eFilterWorkDate_Employees').value,'','','','','',"filterTimeRecords_Employees('succ');","filterTimeRecords_Employees('fail');","filterTimeRecords_Employees('busy');","filterTimeRecords_Employees('timeout');","filterTimeRecords_Employees('inactive');");
			break;
		case "busy":
			if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
			filterTimeRecords_Employees('req');
			break;
		case "timeout":
			if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
			filterTimeRecords_Employees('req');
			break;
		case "succ":
			// delete existing time list...
			for(var i=document.getElementById("tblTime_Employees").rows.length-1; i>=3; i--)
				{ document.getElementById("tblTime_Employees").deleteRow(i); }
			
			// add the filtered time list...
			var R = XML.getElementsByTagName("record");
			var t = document.getElementById('tblTime_Employees');
			for (I=0; I<R.length; I++) {
				var r = t.insertRow(-1);						// Insert a row in the last position
				
				// Insert the cells in the row
				var c0 = r.insertCell(0);
				var c1 = r.insertCell(1);
				var c2 = r.insertCell(2);
				
				// Append a text to the appropriate cell
				//var t0 = document.createTextNode(R[I].getAttribute("createdBy"));
				var t1 = document.createTextNode(R[I].getAttribute("type"));
				var t2 = document.createTextNode(R[I].getAttribute("occurred"));
				
				c0.className = 'thName';
				c0.innerHTML = "<input type='radio' name='radTimeID_Employees' value='"+R[I].getAttribute("id")+"' onClick=\"loadTime_Employees('req','"+R[I].getAttribute("id")+"');\" /> "+R[I].getAttribute("createdBy");
				c1.className = 'thType';
				c1.appendChild(t1);
				c2.className = 'thDate';
				c2.appendChild(t2);
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




// FUNCTIONALITY OF BUTTONS ON THE 'NOTES' TAB

//function saveNote(strAction) {					see webbooks.js
// initializes the module by loading all of the form object values after the screen contents have been added
//}




// FUNCTIONALITY OF BUTTONS ON THE 'DATA' TAB

//function addDataFields(strFormID,strModuleName) {			see webbooks.js
// dynamically creates a new line to upload additional data in the module
//}


//function delDataFields(strFormID,strModuleName,intSkip,intIndex) {	see webbooks.js
// delete an associated data field in the module
//}




// MISCELLANEOUS FUNCTIONALITY OF THE MODULE




// AUTO-EXECUTE CALLS UPON THIS FILE BEING LOADED INTO THE DOM		see last answer on http://stackoverflow.com/questions/8586446/dynamically-load-external-javascript-file-and-wait-for-it-to-load-without-usi

//init_Employees('req');
