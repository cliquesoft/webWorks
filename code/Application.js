// Application.js
//
// Created	2019-08-20 by Dave Henderson (support@cliquesoft.org)
// Updated	2025-10-01 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.




// -- Global Variables --
var _oDashboard;				// used for this modules' AJAX communication
var _iDashboardRefresh = 0;			// used to refresh the employees listing every 10 seconds
var _sUriSocial='';				// the URI of an alternate social application
var __sScreens = new Array('Dashboard','Social');	// stores all the opened modules referenced by the taskbar




// -- Application API --

function Application(sAction) {
	// NOTE: these functions are part of Application because they span multiple modules

	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var mCallback = null;											// the callback to perform
	var AT = sAction.replace(/([a-z])([A-Z])/g, '$1 $2').toLowerCase().split(' ');				// https://stackoverflow.com/questions/18379254/regex-to-split-camel-case

	switch(sAction) {
		case "LoadContact":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		case "NewContact":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;
		case "SaveContact":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "DeleteContact":
		case "DisableContact":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		case "LoadCourier":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		case "NewCourier":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;
		case "SaveCourier":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "DeleteCourier":
		case "DisableCourier":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		case "LoadBank":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		case "NewBank":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;
		case "SaveBank":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "DeleteBank":
		case "DisableBank":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		case "LoadAssociated":
			if (arguments.length < 5) { mRequirements = false; } else { mRequirements = 5; }
			if (arguments.length > 6) { mCallback = arguments[6]; }
			break;
		case "NewAssociated":
			if (arguments.length < 5) { mRequirements = false; } else { mRequirements = 5; }
			if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "s_NewAssociated":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 2) { mCallback = arguments[2]; }
			break;
		case "DeleteAssociated":
		case "DisableAssociated":
			if (arguments.length < 4) { mRequirements = false; } else { mRequirements = 4; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		case "s_DeleteAssociated":
		case "s_DisableAssociated":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 2) { mCallback = arguments[2]; }
			break;
		case "LoadNote":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "SaveNote":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "LoadSpecs":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		case "s_LoadSpecs":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 2) { mCallback = arguments[2]; }
			break;
		case "SaveSpecs":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		case "LoadData":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "NewData":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;
		case "SaveData":
			if (arguments.length < 8) { mRequirements = false; } else { mRequirements = 8; }
			if (arguments.length > 10) { mCallback = arguments[10]; }
			break;
		case "s_SaveData":
			if (arguments.length < 4) { mRequirements = false; } else { mRequirements = 4; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		case "DeleteData":
		case "DisableData":
			if (arguments.length < 5) { mRequirements = false; } else { mRequirements = 5; }
			if (arguments.length > 6) { mCallback = arguments[6]; }
			break;
		case "s_DeleteData":
		case "s_DisableData":
			if (arguments.length < 4) { mRequirements = false; } else { mRequirements = 4; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;

		case "s_LoadContact":
		case "s_SaveContact":
		case "s_DeleteContact":
		case "s_DisableContact":
		case "s_LoadCourier":
		case "s_SaveCourier":
		case "s_DeleteCourier":
		case "s_DisableCourier":
		case "s_LoadBank":
		case "s_SaveBank":
		case "s_DeleteBank":
		case "s_DisableBank":
		case "s_LoadAssociated":
		case "s_LoadNote":
		case "s_SaveNote":
		case "s_LoadData":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;



		default:
			Project('Popup','fail',"ERROR: Application('"+sAction+"') is not a valid action for the function API.");
			return false;
	}

	// sanity checks
	if (typeof mRequirements == 'boolean' && ! mRequirements) {						// first check if the mandatory parameter count is met
		alert("ERROR: Application('"+sAction+"') was called without meeting a sufficent number of parameters.");
		return false;
	} else if (typeof mRequirements == 'number') {								// second check if those mandatory parameters have an actual value
		for (let i=0; i<mRequirements; i++) {
			if (arguments[i] == '') {
				alert("ERROR: Application('"+sAction+"') was called with mandatory parameter #"+i+" being blank.");
				return false;
			}
		}
	}

	// lets perform some work!
	switch(sAction) {
		   // OVERVIEW		Loads the contact information
		   // SYNTAX		Application('LoadContact',sModule,sType='Customer',sScript='application.php',mCallback='');
// VER2 - swith this to function(sAction,mCallback,sJSONConfig); see https://stackoverflow.com/questions/29359197/convert-json-string-to-function-parameters
		case "LoadContact":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Customer Accounts'	
		   // 2: sType		[string] Specifies which type to load					   [Customer, Provider, Vendor]	'Vendor'		['Customer']
		   // 3: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'	['application.php']
		   // 4: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var sType = (arguments.length < 3) ? 'Customer' : arguments[2];
			var sScript = (arguments.length < 4) ? 'application.php' : arguments[3];

// UPDATED 2025/07/03
//			ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=load&target=contact&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&acctID='+document.getElementById('n'+strType+'ContactList_'+camelcase).options[document.getElementById('n'+strType+'ContactList_'+camelcase).selectedIndex].value,'','','n'+strType+'ContactList_'+camelcase,'','',"loadContact('succ','"+strType+"','"+strModule+"');","loadContact('fail','"+strType+"','"+strModule+"');","loadContact('busy','"+strType+"','"+strModule+"');","loadContact('timeout','"+strType+"','"+strModule+"');","loadContact('inactive','"+strType+"','"+strModule+"');");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+document.getElementById('n'+sType+'ContactList_'+sCamelCase).value,'n'+sType+'ContactList_'+sCamelCase,function(){Application('s_'+sAction,sCamelCase,sType);});
			break;
		case "s_LoadContact":
		   // 1: sCamelCase	[string] The module name in CamelCase
		   // 2: sType		[string] Specifies which type to load
			var c = XML.getElementsByTagName("contact").item(0);

			document.getElementById('s'+arguments[2]+'ContactOPoID_'+arguments[1]).value = c.getAttribute("sOPoID");
			document.getElementById('s'+arguments[2]+'Contact_'+arguments[1]).value = c.getAttribute("sName");
			document.getElementById('s'+arguments[2]+'ContactEmail_'+arguments[1]).value = c.getAttribute("sEmail");
			document.getElementById('n'+arguments[2]+'ContactPhone_'+arguments[1]).value = c.getAttribute("nPhone");
			document.getElementById('n'+arguments[2]+'ContactExt_'+arguments[1]).value = c.getAttribute("nExt");
			document.getElementById('n'+arguments[2]+'ContactMobile_'+arguments[1]).value = c.getAttribute("nMobile");
			document.getElementById('b'+arguments[2]+'ContactMobileSMS_'+arguments[1]).checked = (c.getAttribute("bSMS") == 0) ? false : true;
			document.getElementById('b'+arguments[2]+'ContactMobileEmail_'+arguments[1]).checked = (c.getAttribute("bMail") == 0) ? false : true;

			if (arguments[2] == 'Customer') { document.getElementById('s'+arguments[2]+'ContactTitle_'+arguments[1]).value = c.getAttribute("sTitle"); }
			break;




		   // OVERVIEW		Clears the contact information
		   // SYNTAX		Application('NewContact',sModule,sType='Customer',mCallback='');
		case "NewContact":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Customer Accounts'	
		   // 2: sType		[string] Specifies which type to load					   [Customer, Provider, Vendor]	'Vendor'		['Customer']
		   // 3: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var sType = (arguments.length < 3) ? 'Customer' : arguments[2];

			document.getElementById('s'+sType+'ContactOPoID_'+sCamelCase).value = '';
			document.getElementById('s'+sType+'ContactOPoStatus_'+sCamelCase).value = '';
			document.getElementById('s'+sType+'Contact_'+sCamelCase).value = '';
			document.getElementById('s'+sType+'ContactEmail_'+sCamelCase).value = '';
			document.getElementById('n'+sType+'ContactPhone_'+sCamelCase).value = '';
			document.getElementById('n'+sType+'ContactExt_'+sCamelCase).value = '';
			if (document.getElementById('s'+sType+'ContactTitle_'+sCamelCase)) { document.getElementById('s'+sType+'ContactTitle_'+sCamelCase).value = ''; }
			document.getElementById('n'+sType+'ContactMobile_'+sCamelCase).value = '';
			document.getElementById('b'+sType+'ContactMobileSMS_'+sCamelCase).checked = false;
			document.getElementById('b'+sType+'ContactMobileEmail_'+sCamelCase).checked = false;
			document.getElementById('n'+sType+'ContactList_'+sCamelCase).options.selectedIndex = -1;
			document.getElementById('s'+sType+'ContactOPoID_'+sCamelCase).focus();
			break;




		   // OVERVIEW		Saves the (new) contact information
		   // SYNTAX		Application('SaveContact',id,sModule,sType='Customer',sScript='application.php',mCallback='');
		case "SaveContact":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: id		[number] The module database record number of the contact; '+' adds a new record			102	
		   // 2: sModule	[string] The module name										'Customer Accounts'	
		   // 3: sType		[string] Specifies which type to load					   [Customer, Provider, Vendor]	'Vendor'		['Customer']
		   // 4: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'	['application.php']
		   // 5: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
// LEFT OFF - the below may need to replace '%2B' with '+' since it's escaped in _Ajax
			var id = (arguments[1] == '+') ? '%2B' : arguments[1];									// NOTE: we have to take this step because PHP can convert '+' to ' ' causing an error
			var sCamelCase = arguments[2].replace(/ /g, "").replace(/&/g, "And");
			var sType = (arguments.length < 4) ? 'Customer' : arguments[3];
			var sScript = (arguments.length < 5) ? 'application.php' : arguments[4];

			// used to help prevent duplicates from getting into the list
			if (document.getElementById('n'+sType+'ContactList_'+sCamelCase).selectedIndex != -1) {
				if (confirm('It appears that a contact has been loaded for updating. Are you sure you want to create another that may be a possible duplicate of one that currently exists in the list?') == false)
					{ return false; }
			}
			if (document.getElementById('s'+sType+'Contact_'+sCamelCase).value == '') {
				Project(_sProjectUI,'fail',"You must specify the contacts name before adding them to the list.");
				return false;
			}
			if (Listbox('CheckOption','n'+sType+'ContactList_'+sCamelCase,document.getElementById('s'+strType+'Contact_'+camelcase).value)) {
				Project(_sProjectUI,'fail',"A contact with that description is already in the list, so use a different descriptive name before continuing.");
				return false;
			}

// UPDATED 2025/07/03
//			ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=new&target=contact&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&type='+strType+'&id='+intRelated,'form'+strType+'Contact_'+camelcase,'','btn'+strType+'ContactAdd_'+camelcase,'','',"newContact('succ','"+strType+"','"+strModule+"','"+intRelated+"');","newContact('fail','"+strType+"','"+strModule+"','"+intRelated+"');","newContact('busy','"+strType+"','"+strModule+"','"+intRelated+"');","newContact('timeout','"+strType+"','"+strModule+"','"+intRelated+"');","newContact('inactive','"+strType+"','"+strModule+"','"+intRelated+"');");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&sType='+sType+'&id='+id+'&sModule='+escape(arguments[2]),'o'+sType+'ContactSave_'+sCamelCase,function(){Application('s_'+sAction,sCamelCase,sType);},null,null,null,'form'+sType+'Contact_'+sCamelCase);
			break;
		case "s_SaveContact":
		   // 1: sCamelCase	[string] The module name in CamelCase
		   // 2: sType		[string] Specifies which type to load
			// send a notification to the user			NOTE: this had to come above the following calls so the message would get displayed (it was '1' otherwise)
			Project(_sProjectUI,'succ');

			if (DATA.hasOwnProperty("id")) {						// if this is a CREATION event, then...
				// add the new group to the listbox and set select it
				Listbox('AddOption','n'+arguments[2]+'ContactList_'+arguments[1],DATA['id'],document.getElementById('s'+arguments[2]+'Contact_'+arguments[1]).value,'','',true,false,true);
				delete DATA['id'];				// to prevent contamination between failed calls
			} else {
				// replace the old value with the new one
				Listbox('ReplaceOption','n'+arguments[2]+'ContactList_'+arguments[1],document.getElementById('n'+arguments[2]+'ContactList_'+arguments[1]).value,document.getElementById('s'+arguments[2]+'Contact_'+arguments[1]).value);
			}
			break;




		   // OVERVIEW		Actually deletes the selected contact record in the database
		   // SYNTAX		Application('DeleteContact',sModule,sType='Customer',sScript='application.php',mCallback='');
		case "DeleteContact":
		   // OVERVIEW		Disables the selected contact to appear as being deleted
		   // SYNTAX		Application('DisableContact',sModule,sType='Customer',sScript='application.php',mCallback='');
		case "DisableContact":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Customer Accounts'	
		   // 2: sType		[string] Specifies which type to load					   [Customer, Provider, Vendor]	'Vendor'		['Customer']
		   // 3: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'	['application.php']
		   // 4: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var sType = (arguments.length < 3) ? 'Customer' : arguments[2];
			var sScript = (arguments.length < 4) ? 'application.php' : arguments[3];

// UPDATED 2025/07/03
//			ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=delete&target=contact&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&acctID='+document.getElementById('n'+strType+'ContactList_'+camelcase).options[document.getElementById('n'+strType+'ContactList_'+camelcase).selectedIndex].value,'','','btn'+strType+'ContactDel_'+camelcase,'','',"delContact('succ','"+strType+"','"+strModule+"');","delContact('fail','"+strType+"','"+strModule+"');","delContact('busy','"+strType+"','"+strModule+"');","delContact('timeout','"+strType+"','"+strModule+"');","delContact('inactive','"+strType+"','"+strModule+"');");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+document.getElementById('o'+sType+'ContactDelete_'+sCamelCase).value,'o'+sType+'ContactList_'+sCamelCase,function(){Application('s_'+sAction,sCamelCase,sType);});
			break;
		case "s_DeleteContact":
		case "s_DisableContact":
		   // 1: sCamelCase	[string] The module name in CamelCase
		   // 2: sType		[string] Specifies which type to load
			// send a notification to the user
			Project(_sProjectUI,'succ');

			// delete the selected item from the list
		   	Listbox('RemoveOption','n'+arguments[2]+'ContactList_'+arguments[1]);
			break;




// LEFT OFF - move these to BusinessConfig and change Courier>FreightAccount
		   // OVERVIEW		Loads the courier information
		   // SYNTAX		Application('LoadCourier',sModule,sType='Customer',sScript='application.php',mCallback='');
		case "LoadCourier":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Customer Accounts'	
		   // 2: sType		[string] Specifies which type to load						   [Customer, Business]	'Business'		['Customer']
		   // 3: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'	['application.php']
		   // 4: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var sType = (arguments.length < 3) ? 'Customer' : arguments[2];
			var sScript = (arguments.length < 4) ? 'application.php' : arguments[3];

// UPDATED 2025/07/03
//			ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=delete&target=contact&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&acctID='+document.getElementById('n'+strType+'ContactList_'+camelcase).options[document.getElementById('n'+strType+'ContactList_'+camelcase).selectedIndex].value,'','','btn'+strType+'ContactDel_'+camelcase,'','',"delContact('succ','"+strType+"','"+strModule+"');","delContact('fail','"+strType+"','"+strModule+"');","delContact('busy','"+strType+"','"+strModule+"');","delContact('timeout','"+strType+"','"+strModule+"');","delContact('inactive','"+strType+"','"+strModule+"');");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+document.getElementById('n'+sType+'ContactList_'+sCamelCase).value,'n'+sType+'ContactList_'+sCamelCase,function(){Application('s_'+sAction,sCamelCase,sType);});
			break;
		case "s_LoadCourier":
		   // 1: sCamelCase	[string] The module name in CamelCase
		   // 2: sType		[string] Specifies which type to load
			var c = XML.getElementsByTagName("courier").item(0);

			document.getElementById('s'+arguments[2]+'CourierName_'+arguments[1]).value = c.getAttribute("sName");
			document.getElementById('s'+arguments[2]+'CourierAccount_'+arguments[1]).value = c.getAttribute("sAccount");
			break;




		   // OVERVIEW		Clears the courier information
		   // SYNTAX		Application('NewCourier',sModule,sType='Customer',mCallback='');
		case "NewCourier":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Customer Accounts'	
		   // 2: sType		[string] Specifies which type to load						   [Customer, Business]	'Business'		['Customer']
		   // 3: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var sType = (arguments.length < 3) ? 'Customer' : arguments[2];

			document.getElementById('s'+sType+'ShipName_'+sCamelCase).value = '';
			document.getElementById('s'+sType+'ShipAccount_'+sCamelCase).value = '';
			document.getElementById('n'+sType+'ShipList_'+sCamelCase).options.selectedIndex = -1;
			document.getElementById('s'+sType+'ShipName_'+sCamelCase).focus();
			break;




		   // OVERVIEW		Saves the (new) courier information
		   // SYNTAX		Application('SaveCourier',id,sModule,sType='Customer',sScript='application.php',mCallback='');
		case "SaveCourier":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: id		[number] The module database record number of the contact; '+' adds a new record			102	
		   // 2: sModule	[string] The module name										'Customer Accounts'	
		   // 3: sType		[string] Specifies which type to load						   [Customer, Business]	'Business'		['Customer']
		   // 4: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'	['application.php']
		   // 5: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var id = (arguments[1] == '+') ? '%2B' : arguments[1];									// NOTE: we have to take this step because PHP can convert '+' to ' ' causing an error
			var sCamelCase = arguments[2].replace(/ /g, "").replace(/&/g, "And");
			var sType = (arguments.length < 4) ? 'Customer' : arguments[3];
			var sScript = (arguments.length < 5) ? 'application.php' : arguments[4];

			// used to help prevent duplicates from getting into the list
			if (document.getElementById('n'+sType+'ShipList_'+sCamelCase).selectedIndex != -1) {
				if (confirm('It appears that a courier has been loaded for updating. Are you sure you want to create another that may be a possible duplicate of one that currently exists in the list?') == false)
					{ return false; }
			}
			if (document.getElementById('s'+sType+'ShipName_'+sCamelCase).value == '') {
				Project(_sProjectUI,'fail',"You must specify the couriers name before adding it to the list.");
				return false;
			}
			if (document.getElementById('s'+sType+'ShipAccount_'+sCamelCase).value == '') {
				Project(_sProjectUI,'fail',"You must specify the courier account number before adding it to the list.");
				return false;
			}
			if (Listbox('CheckOption','n'+sType+'ShipList_'+sCamelCase,document.getElementById('s'+strType+'ShipName_'+camelcase).value)) {
				Project(_sProjectUI,'fail',"A courier with that description is already in the list, so use a different descriptive name before continuing.");
				return false;
			}

// UPDATED 2025/07/03
//			ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=new&target=freight&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&type='+strType+'&id='+intRelated,'form'+strType+'Ship_'+camelcase,'','btn'+strType+'ShipAdd_'+camelcase,'','',"newFreight('succ','"+strType+"','"+strModule+"','"+intRelated+"');","newFreight('fail','"+strType+"','"+strModule+"','"+intRelated+"');","newFreight('busy','"+strType+"','"+strModule+"','"+intRelated+"');","newFreight('timeout','"+strType+"','"+strModule+"','"+intRelated+"');","newFreight('inactive','"+strType+"','"+strModule+"','"+intRelated+"');");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&sType='+sType+'&id='+id+'&sModule='+escape(arguments[2]),'o'+sType+'ShipSave_'+sCamelCase,function(){Application('s_'+sAction,sCamelCase,sType);},null,null,null,'form'+sType+'Ship_'+sCamelCase);
			break;
		case "s_SaveCourier":
		   // 1: sCamelCase	[string] The module name in CamelCase
		   // 2: sType		[string] Specifies which type to load
			// send a notification to the user			NOTE: this had to come above the following calls so the message would get displayed (it was '1' otherwise)
			Project(_sProjectUI,'succ');

			if (DATA.hasOwnProperty("id")) {						// if this is a CREATION event, then...
				// add the new group to the listbox and set select it
				Listbox('AddOption','n'+arguments[2]+'ShipList_'+arguments[1],DATA['id'],document.getElementById('s'+arguments[2]+'ShipName_'+arguments[1]).value,'','',true,false,true);
				delete DATA['id'];				// to prevent contamination between failed calls
			} else {
				// replace the old value with the new one
				Listbox('ReplaceOption','n'+arguments[2]+'ShipList_'+arguments[1],document.getElementById('n'+arguments[2]+'ShipList_'+arguments[1]).value,document.getElementById('s'+arguments[2]+'ShipName_'+arguments[1]).value);
			}
			break;




		   // OVERVIEW		Actually deletes the selected courier record in the database
		   // SYNTAX		Application('DeleteContact',sModule,sType='Customer',sScript='application.php',mCallback='');
		case "DeleteCourier":
		   // OVERVIEW		Disables the selected courier to appear as being deleted
		   // SYNTAX		Application('DisableContact',sModule,sType='Customer',sScript='application.php',mCallback='');
		case "DisableCourier":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Customer Accounts'	
		   // 2: sType		[string] Specifies which type to load					   [Customer, Provider, Vendor]	'Vendor'		['Customer']
		   // 3: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'	['application.php']
		   // 4: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var sType = (arguments.length < 3) ? 'Customer' : arguments[2];
			var sScript = (arguments.length < 4) ? 'application.php' : arguments[3];

// UPDATED 2025/07/03
//			ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=delete&target=freight&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+document.getElementById('n'+strType+'ShipList_'+camelcase).options[document.getElementById('n'+strType+'ShipList_'+camelcase).selectedIndex].value,'','','btn'+strType+'ShipDel_'+camelcase,'','',"delFreight('succ','"+strType+"','"+strModule+"');","delFreight('fail','"+strType+"','"+strModule+"');","delFreight('busy','"+strType+"','"+strModule+"');","delFreight('timeout','"+strType+"','"+strModule+"');","delFreight('inactive','"+strType+"','"+strModule+"');");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+document.getElementById('n'+sType+'ShipList_'+sCamelCase).value,'o'+sType+'ShipDelete_'+sCamelCase,function(){Application('s_'+sAction,sCamelCase,sType);});
			break;
		case "s_DeleteCourier":
		case "s_DisableCourier":
		   // 1: sCamelCase	[string] The module name in CamelCase
		   // 2: sType		[string] Specifies which type to load
			// send a notification to the user
			Project(_sProjectUI,'succ');

			// delete the selected item from the list
		   	Listbox('RemoveOption','n'+arguments[2]+'ContactList_'+arguments[1]);
			break;




		   // OVERVIEW		Loads the bank information
		   // SYNTAX		Application('LoadBank',sModule,sType='Business',sScript='application.php',mCallback='');
		case "LoadBank":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Customer Accounts'	
		   // 2: sType		[string] Specifies which type to load				 [Business, Location, Provider, Vendor]	'Location'		['Business']
		   // 3: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'	['application.php']
		   // 4: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var sType = (arguments.length < 3) ? 'Business' : arguments[2];
			var sScript = (arguments.length < 4) ? 'application.php' : arguments[3];

// UPDATED 2025/07/03
//			ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=load&target=bank&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+document.getElementById('n'+strType+'BankList_'+camelcase).options[document.getElementById('n'+strType+'BankList_'+camelcase).selectedIndex].value,'','','n'+strType+'BankList_'+camelcase,'','',"loadBank('succ','"+strType+"','"+strModule+"');","loadBank('fail','"+strType+"','"+strModule+"');","loadBank('busy','"+strType+"','"+strModule+"');","loadBank('timeout','"+strType+"','"+strModule+"');","loadBank('inactive','"+strType+"','"+strModule+"');");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+document.getElementById('n'+sType+'BankList_'+sCamelCase).value,'n'+sType+'BankList_'+sCamelCase,function(){Application('s_'+sAction,sCamelCase,sType);});
			break;
		case "s_LoadBank":
		   // 1: sCamelCase	[string] The module name in CamelCase
		   // 2: sType		[string] Specifies which type to load
			var b = XML.getElementsByTagName("bank").item(0);

			document.getElementById('s'+arguments[2]+'BankName_'+arguments[1]).value = b.getAttribute("sName");
			document.getElementById('n'+arguments[2]+'BankRouting_'+arguments[1]).value = b.getAttribute("nRouting");
			document.getElementById('n'+arguments[2]+'BankAccount_'+arguments[1]).value = b.getAttribute("nAccount");
			if (document.getElementById('s'+arguments[2]+'BankCheckType_'+arguments[1])) {
				Listbox('SelectOption','s'+arguments[2]+'BankCheckType_'+arguments[1],b.getAttribute("sType"));
				document.getElementById('n'+arguments[2]+'BankCheckNo_'+arguments[1]).value = b.getAttribute("nCheck");
			}
			break;




		   // OVERVIEW		Clears the bank information
		   // SYNTAX		Application('NewBank',sModule,sType='Business',mCallback='');
		case "NewBank":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Customer Accounts'	
		   // 2: sType		[string] Specifies which type to load				 [Business, Location, Provider, Vendor]	'Location'		['Business']
		   // 3: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var sType = (arguments.length < 3) ? 'Business' : arguments[2];

			document.getElementById('s'+sType+'BankDesc_'+sCamelCase).value = '';
			document.getElementById('n'+sType+'BankRouting_'+sCamelCase).value = '';
			document.getElementById('n'+sType+'BankAccount_'+sCamelCase).value = '';
			if (document.getElementById('s'+sType+'BankCheckType_'+sCamelCase)) {
				document.getElementById('s'+sType+'BankCheckType_'+sCamelCase).options.selectedIndex = 0;
				document.getElementById('n'+sType+'BankCheckNo_'+sCamelCase).value = '';
			}
			document.getElementById('n'+sType+'BankList_'+sCamelCase).options.selectedIndex = -1;
			document.getElementById('s'+sType+'BankDesc_'+sCamelCase).focus();
			break;




		   // OVERVIEW		Saves the (new) bank information
		   // SYNTAX		Application('SaveBank',id,sModule,sType='Business',sScript='application.php',mCallback='');
		case "SaveBank":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: id		[number] The module database record number of the contact; '+' adds a new record			102	
		   // 2: sModule	[string] The module name										'Customer Accounts'	
		   // 3: sType		[string] Specifies which type to load				 [Business, Location, Provider, Vendor]	'Location'		['Business']
		   // 4: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'	['application.php']
		   // 5: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var id = (arguments[1] == '+') ? '%2B' : arguments[1];									// NOTE: we have to take this step because PHP can convert '+' to ' ' causing an error
			var sCamelCase = arguments[2].replace(/ /g, "").replace(/&/g, "And");
			var sType = (arguments.length < 4) ? 'Business' : arguments[3];
			var sScript = (arguments.length < 5) ? 'application.php' : arguments[4];

			// used to help prevent duplicates from getting into the list
			if (document.getElementById('n'+sType+'BankList_'+sCamelCase).selectedIndex != -1) {
				if (confirm('It appears that a bank account has been loaded for updating. Are you sure you want to create another that may be a possible duplicate of one that currently exists in the list?') == false)
					{ return false; }
			}
			if (document.getElementById('s'+sType+'BankDesc_'+sCamelCase).value == '') {
				Project(_sProjectUI,'fail',"You must specify the banks name before adding it to the list.");
				return false;
			}
			if (document.getElementById('s'+sType+'BankRouting_'+sCamelCase).value == '') {
				Project(_sProjectUI,'fail',"You must specify the bank routing number before adding it to the list.");
				return false;
			}
			if (document.getElementById('s'+sType+'BankAccount_'+sCamelCase).value == '') {
				Project(_sProjectUI,'fail',"You must specify the bank account number before adding it to the list.");
				return false;
			}
			if (document.getElementById('n'+sType+'BankCheckNo_'+sCamelCase)) {
				if (document.getElementById('s'+sType+'BankCheckNo_'+sCamelCase).value == '') {
					Project(_sProjectUI,'fail',"You must specify the bank account check number before adding it to the list.");
					return false;
				}
			}
			if (Listbox('CheckOption','n'+sType+'BankList_'+sCamelCase,document.getElementById('s'+strType+'BankDesc_'+camelcase).value)) {
				Project(_sProjectUI,'fail',"A bank with that description is already in the list, so use a different descriptive name before continuing.");
				return false;
			}

// UPDATED 2025/07/03
//			ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=new&target=bank&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&type='+strType+'&id='+intRelated,'form'+strType+'Banks_'+camelcase,'','btn'+strType+'BankAdd_'+camelcase,'','',"newBank('succ','"+strType+"','"+strModule+"','"+intRelated+"');","newBank('fail','"+strType+"','"+strModule+"','"+intRelated+"');","newBank('busy','"+strType+"','"+strModule+"','"+intRelated+"');","newBank('timeout','"+strType+"','"+strModule+"','"+intRelated+"');","newBank('inactive','"+strType+"','"+strModule+"','"+intRelated+"');");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&sType='+sType+'&id='+id+'&sModule='+escape(arguments[2]),'o'+sType+'BankSave_'+sCamelCase,function(){Application('s_'+sAction,sCamelCase,sType);},null,null,null,'form'+sType+'Banks_'+sCamelCase);
			break;
		case "s_SaveBank":
		   // 1: sCamelCase	[string] The module name in CamelCase
		   // 2: sType		[string] Specifies which type to load
			// send a notification to the user			NOTE: this had to come above the following calls so the message would get displayed (it was '1' otherwise)
			Project(_sProjectUI,'succ');

			if (DATA.hasOwnProperty("id")) {						// if this is a CREATION event, then...
				// add the new group to the listbox and set select it
				Listbox('AddOption','n'+arguments[2]+'BankList_'+arguments[1],DATA['id'],document.getElementById('s'+arguments[2]+'BankDesc_'+arguments[1]).value,'','',true,false,true);
				delete DATA['id'];				// to prevent contamination between failed calls
			} else {
				// replace the old value with the new one
				Listbox('ReplaceOption','n'+arguments[2]+'BankList_'+arguments[1],document.getElementById('n'+arguments[2]+'BankList_'+arguments[1]).value,document.getElementById('s'+arguments[2]+'BankDesc_'+arguments[1]).value);
			}
			break;




		   // OVERVIEW		Actually deletes the selected bank record in the database
		   // SYNTAX		Application('DeleteBank',sModule,sType='Business',sScript='application.php',mCallback='');
		case "DeleteBank":
		   // OVERVIEW		Disables the selected bank to appear as being deleted
		   // SYNTAX		Application('DisableBank',sModule,sType='Business',sScript='application.php',mCallback='');
		case "DisableBank":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Customer Accounts'	
		   // 2: sType		[string] Specifies which type to load				 [Business, Location, Provider, Vendor]	'Location'		['Business']
		   // 3: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'	['application.php']
		   // 4: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var sType = (arguments.length < 3) ? 'Business' : arguments[2];
			var sScript = (arguments.length < 4) ? 'application.php' : arguments[3];

// UPDATED 2025/07/03
//			ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=delete&target=bank&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+document.getElementById('n'+strType+'BankList_'+camelcase).options[document.getElementById('n'+strType+'BankList_'+camelcase).selectedIndex].value,'','','btn'+strType+'BankDel_'+camelcase,'','',"delBank('succ','"+strType+"','"+strModule+"');","delBank('fail','"+strType+"','"+strModule+"');","delBank('busy','"+strType+"','"+strModule+"');","delBank('timeout','"+strType+"','"+strModule+"');","delBank('inactive','"+strType+"','"+strModule+"');");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+document.getElementById('n'+sType+'BankList_'+sCamelCase).value,'o'+sType+'BankDelete_'+sCamelCase,function(){Application('s_'+sAction,sCamelCase,sType);});
			break;
		case "s_DeleteBank":
		case "s_DisableBank":
		   // 1: sCamelCase	[string] The module name in CamelCase
		   // 2: sType		[string] Specifies which type to load
			// send a notification to the user
			Project(_sProjectUI,'succ');

			// delete the selected item from the list
		   	Listbox('RemoveOption','n'+arguments[2]+'ContactList_'+arguments[1]);
			break;




		   // OVERVIEW		Loads the bank information
		   // SYNTAX		Application('LoadAssociated',sSource,sTarget,id,mLoad,sScript='application.php',mCallback='');
		case "LoadAssociated":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
// VER2 - replace 1 & 2 below with the module id's instead of names
		   // 1: sSource	[string] The source module name										'Work Orders'
		   // 2: sTarget	[string] The target module name										'Quotes & Invoices'
		   // 3: id		[number] The source document number to convert for the target module					1234
//		   // 3: mTarget	[string][object] The target modules object used to load the associated item (textbox or listbox)	'nDocument_QuotesAndInvoices'
		   // 4: mLoad		[string][function] The function to load the document in the target module				"loadDocument_QuotesAndInvoices(DATA['id']);"
		   //			[ NOTE ] use "DATA['id']" as the string to reference the document to load (since this will be produced server-side and accessible in the 'success' block below)
		   // 5: sScript	[string] server-side script URL to process this request							'QuotesAndInvoices.php'	['application.php']
		   // 6: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sSource = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var sTarget = arguments[2].replace(/ /g, "").replace(/&/g, "And");
//			var oTarget = (typeof arguments[3] === "object") ? arguments[3] : document.getElementById(arguments[3]);
			var id = arguments[3];
			var mLoad = arguments[4];
			var sScript = (arguments.length < 6) ? 'application.php' : arguments[5];

			var sCamelCaseSource = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var sCamelCaseTarget = arguments[2].replace(/ /g, "").replace(/&/g, "And");

// UPDATED 2025/07/11
//			ajax(reqAssetManagement,4,'post',gbl_uriProject+"code/asset_management.php",'action=load&target=associated&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+document.getElementById('nAssociatedList_AssetManagement').options[document.getElementById('nAssociatedList_AssetManagement').selectedIndex].value+'&source=AssetManagement','','','nAssociatedList_AssetManagement','','',"loadAssociated_AssetManagement('succ');","loadAssociated_AssetManagement('fail');","loadAssociated_AssetManagement('busy');","loadAssociated_AssetManagement('timeout');","loadAssociated_AssetManagement('inactive');");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+id+'&sSource='+escape(sCamelCaseSource)+'&sTarget='+escape(sCamelCaseTarget),oTarget,function(){Application('s_'+sAction,sSource,oTarget,mLoad);});
			break;
		case "s_LoadAssociated":
		   // 1: sTarget	[string] The target module name
		   // 2: mLoad		[string][function] The function to load the document in the target module				"loadItem_Inventory(1234);"
//		   // 2: mTarget	[string][object] The target modules object used to load the associated item (textbox or listbox)
//		   // 3: nID		[number] The document number to load in the target module						1234
			Module('focus',arguments[2],arguments[1]);					// load the module so the associated document can be loaded!
// REMOVED 2025/07/12 - this has moved in the callback for Module('focus') above so there's no need to poll
//			Application('PollAssociated',arguments[2],DATA['id']);				// now call the 'poll' section of this function so the document can be loaded!

			delete DATA['id'];				// to prevent contamination between failed calls
			break;




// LEFT OFF - the below is no longer used and can be deleted
		   // OVERVIEW		Loads the associated item in its module (polling until that module loads, then loads the document)
		   //			NOTE: loading the module is handled in the 'success' function calling this one
		   // SYNTAX		Application('LoadAssociated',sModule,sType='Business',sScript='application.php',mCallback='');
		case "PollAssociated":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mTarget	[string][object] The target modules object used to load the associated item (textbox or listbox)	'sPSList_Inventory'
		   // 2: nID		[number] The document number to load in the target module						1234
		   // 3: mLoad		[string][function] The function to load the document in the target module				"loadItem_Inventory(1234);"
// UPDATED 2025/07/12
//			if (document.getElementById('sPSList_Inventory')) {						// this section is actually called AFTER the 'else' section below so the following calls work correctly!
//				selListbox('sPSList_Inventory',arguments[1]);						//   this is so the loadItem_Inventory() call doesn't get cancelled
//				loadItem_Inventory('req',arguments[1]);							//   this actually loads the associated document
//			} else { setTimeout("loadAssociated_AssetManagement('poll',"+arguments[1]+");", 1000); }	// this line is called repeatedly until the module has been loaded from the 'succ' section above

			var oTarget = (typeof arguments[1] === "object") ? arguments[1] : document.getElementById(arguments[1]);

			// this section is actually called AFTER the 'else' section below so the following calls work correctly!
			if (oTarget) {
				if (oTarget.type == 'select-one' || oTarget.type == 'select-multiple')			//   if the oTarget is a <select>, then...
					{ Listbox('SelectOption',oTarget,arguments[2]); }				//      select the document to load
				else											//   otherwise it's a textbox, so...
					{ oTarget.value = arguments[2]; }						//      enter the document number to load

				if (typeof(arguments[3]) === 'function') {arguments[3]; }				//   execute the function to load the document
				else if (typeof(arguments[3]) === 'string' && arguments[3] != '') { eval(arguments[3]); }

			// this line is called repeatedly until the module has been loaded from the 'succ' section above
			} else { setTimeout("Application('PollAssociated',"+oTarget+","+arguments[1]+","+arguments[2]+");", 1000); }
			break;




		   // OVERVIEW		Creates a new associated item
		   // SYNTAX		Application('NewAssociated',sSource,sTarget,id,sScript,mCallback='');
		case "NewAssociated":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sSource	[string] The source module name										'Work Orders'
		   // 2: sTarget	[string] The target module name										'Quotes & Invoices'
		   // 3: id		[number] The source document number to convert for the target module					1234
		   // 4: sScript	[string] server-side script URL to process this request							'QuotesAndInvoices.php'
		   // 5: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCaseSource = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var sCamelCaseTarget = arguments[2].replace(/ /g, "").replace(/&/g, "And");
			var id = arguments[3];
			var sScript = (arguments.length < 4) ? 'application.php' : arguments[3];

// REMOVED 2025/07/12 - not having any history will force a document to be loaded -AND- having a history; so we can forgo this check
//			// if there is a hidden object dealing with the item number, then make sure it's not 0
//			if (document.getElementById('hidID_'+sCamelCaseSource) {
//				if (document.getElementById('hidID_'+sCamelCaseSource).value == '0')
//					{ alert("You must load an item before creating one that's associated."); return false; }
//			}
			// if there's a history tab dealing with the item, then make sure it has details before continuing
			if (document.getElementById('tblHistory_'+sCamelCaseSource)) {
				if (document.getElementById('tblHistory_'+sCamelCaseSource).rows.length == 0)
						{ alert("There are no history details for this item so the conversion has been cancelled."); return false; }
			}
			// check that there isn't already a converted item
			for (let i=0; i<document.getElementById('nAssociatedList_'+sCamelCaseSource).options.length; i++) {
				if (document.getElementById('nAssociatedList_'+sCamelCaseSource).options[i].text == document.getElementById('nAssociatedTypes_'+sCamelCaseSource).options[document.getElementById('nAssociatedTypes_'+sCamelCaseSource).selectedIndex].text)
					{ alert('You already have an associated item in the list for that selection.'); return false; }
			}

// UPDATED 2025/07/12
//			ajax(reqAssetManagement,4,'post',gbl_uriProject+"code/asset_management.php",'action=new&target=associated&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&asset='+document.getElementById('hidID_AssetManagement').value+'&type='+document.getElementById('nAssociatedTypes_AssetManagement').options[document.getElementById('nAssociatedTypes_AssetManagement').selectedIndex].value,'','','btnAssociatedAdd_AssetManagement','','',"newAssociated_AssetManagement('succ');","newAssociated_AssetManagement('fail');","newAssociated_AssetManagement('busy');","newAssociated_AssetManagement('timeout');","newAssociated_AssetManagement('inactive');");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+id+'&sSource='+escape(sCamelCaseSource)+'&sTarget='+escape(sCamelCaseTarget),'btnAssociatedAdd_'+sCamelCaseSource,function(){Application('s_'+sAction,sCamelCaseSource);});
			break;
		case "s_NewAssociated":
		   // 1: sSource	[string] The source module name	in CamelCase
			Listbox('AddOption','nAssociatedList_'+arguments[1],DATA['id'],document.getElementById('nAssociatedTypes_'+arguments[1]).options[document.getElementById('nAssociatedTypes_'+arguments[1]).selectedIndex].text,'','',false,false,true);
			delete DATA['id'];				// to prevent contamination between failed calls
			break;




		   // OVERVIEW		Actually deletes the selected bank record in the database
		   // SYNTAX		Application('DeleteAssociated',sModule,id,sScript,mCallback='');
		case "DeleteAssociated":
		   // OVERVIEW		Disables the selected bank to appear as being deleted
		   // SYNTAX		Application('DisableAssociated',sModule,id,sScript,mCallback='');
		case "DisableAssociated":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Customer Accounts'	
		   // 2: id		[number] The associated database id to delete								1234
		   // 3: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'
		   // 4: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var id = arguments[2];
			var sScript = (arguments.length < 4) ? 'application.php' : arguments[3];

			if (document.getElementById('nAssociatedList_'+sCamelCase).selectedIndex == -1) { alert("You must select an associated item from the list before continuing."); return false; }

// UPDATED 2025/07/03
//			ajax(reqAssetManagement,4,'post',gbl_uriProject+"code/asset_management.php",'action=delete&target=associated&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+document.getElementById('nAssociatedList_AssetManagement').options[document.getElementById('nAssociatedList_AssetManagement').selectedIndex].value,'','','btnAssociatedDel_AssetManagement','','',"delAssociated_AssetManagement('succ');","delAssociated_AssetManagement('fail');","delAssociated_AssetManagement('busy');","delAssociated_AssetManagement('timeout');","delAssociated_AssetManagement('inactive');");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+id,'btnAssociatedDel_'+sCamelCase,function(){Application('s_'+sAction,sCamelCase);});
			break;
		case "s_DeleteAssociated":
		case "s_DisableAssociated":
		   // 1: sCamelCase	[string] The module name in CamelCase
			// send a notification to the user
			Project(_sProjectUI,'succ');

			// delete the selected item from the list
		   	Listbox('RemoveOption','nAssociatedList_'+arguments[1]);
			break;










		// -- NOTES Tab --


		   // OVERVIEW		Loads all the data uploads for the module record
		   // SYNTAX		Application('LoadNote',sModule,id,sType='Customer',sScript='application.php',mCallback='');
		case "LoadNote":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Customer Accounts'	
		   // 2: id		[number] The module database record number of the note							102	
		   // 3: sType		[string] Specifies which type to load					       [Customer, Employee, WO]	'Employee'		['Customer']
		   // 4: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'	[application.php]
		   // 5: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var id = (arguments[2] == '+') ? '%2B' : arguments[2];									// NOTE: we have to take this step because PHP can convert '+' to ' ' causing an error
			var sType = (arguments.length < 4) ? 'Customer' : arguments[3];
			var sScript = (arguments.length < 5) ? 'application.php' : arguments[4];

			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+id+'&sType='+sType+'&sModule='+escape(arguments[1]),'',function(){Application('s_'+sAction,sCamelCase,sType);});
			break;
		case "s_LoadNote":
		   // 1: sCamelCase	[string] The module name in CamelCase
		   // 2: sType		[string] Specifies which type to load
			var b = XML.getElementsByTagName("bank").item(0);

			document.getElementById('s'+arguments[2]+'BankDesc_'+arguments[1]).value = b.getAttribute("name");
			document.getElementById('n'+arguments[2]+'BankRouting_'+arguments[1]).value = b.getAttribute("routing");
			document.getElementById('n'+arguments[2]+'BankAccount_'+arguments[1]).value = b.getAttribute("account");
			if (document.getElementById('s'+arguments[2]+'BankCheckType_'+arguments[1])) {
				Listbox('SelectOption','s'+arguments[2]+'BankCheckType_'+arguments[1],b.getAttribute("type"));
				document.getElementById('n'+arguments[2]+'BankCheckNo_'+arguments[1]).value = b.getAttribute("check");
			}
			break;




		   // OVERVIEW		Saves the newly added note
		   // SYNTAX		Application('SaveNote',sModule,id,sType='Customer',sScript='application.php',mCallback='');
		case "SaveNote":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Customer Accounts'	
		   // 2: id		[number] The module database record number of the contact; '+' adds a new record			102	
// VER2 - move this to reference the module id foreign key instead of text names
		   // 3: sType		[string] Specifies which type to use					       [Customer, Employee, WO]	'Employee'		['Customer']
		   // 4: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'	[application.php]
		   // 5: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var id = (arguments[2] == '+') ? '%2B' : arguments[2];									// NOTE: we have to take this step because PHP can convert '+' to ' ' causing an error
			var sType = (arguments.length < 4) ? 'Customer' : arguments[3];
			var sScript = (arguments.length < 5) ? 'application.php' : arguments[4];

			// used to help prevent duplicates from getting into the list
			if (document.getElementById('sNoteAccess_'+sCamelCase).selectedIndex != -1) {
				Project(_sProjectUI,'fail',"You must select the access level for the note before saving it.");
				return false;
			}
			if (document.getElementById('sNote_'+sCamelCase).value == '') {
				Project(_sProjectUI,'fail',"You must enter text before saving the note.");
				return false;
			}

// UPDATED 2025/07/03
//			ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=save&target=note&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+intID,'formNotes_'+camelcase,'','','','',"saveNote('succ','"+strType+"','"+strModule+"','"+intID+"');","saveNote('fail','"+strType+"','"+strModule+"','"+intID+"');","saveNote('busy','"+strType+"','"+strModule+"','"+intID+"');","saveNote('timeout','"+strType+"','"+strModule+"','"+intID+"');","saveNote('inactive','"+strType+"','"+strModule+"','"+intID+"');");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+id+'&sType='+sType+'&sModule='+escape(arguments[1]),'',function(){Application('s_'+sAction,sCamelCase,sType);},null,null,null,'formNotes_'+sCamelCase);
			break;
		case "s_SaveNote":
		   // 1: sCamelCase	[string] The module name in CamelCase
		   // 2: sType		[string] Specifies which type to load
			// send a notification to the user			NOTE: this had to come above the following calls so the message would get displayed (it was '1' otherwise)
			Project(_sProjectUI,'succ');

			// Define the objects
			var t = document.getElementById('tblNotes_'+arguments[1]);
			var r = t.insertRow(0);								// Insert a row at the beginning of the list

			// Insert the cells in the row
			var c0 = r.insertCell(0);
			var c1 = r.insertCell(1);
			var c2 = r.insertCell(2);

			// Append a text to the appropriate cell
			var t0 = document.createTextNode(DATA['date']);
			var t1 = document.createTextNode(DATA['creator']);
			//var t2 = document.createTextNode();

			//c0.className = 'tdLast center';
			c0.appendChild(t0);
			//c1.className = 'tdLast';
			c1.appendChild(t1);
			//c1.className = 'tdLast';
			c2.innerHTML = document.getElementById('sNote_'+arguments[1]).value.replace(/\\r\\n|\\r|\\n/g,'<br />');		// WARNING: createTextNode does NOT handle HTML formatting, so we use innerHTML here instead!

			// clean up the form after submission
			document.getElementById('sNoteAccess_'+arguments[1]).options.selectedIndex = 0;
			document.getElementById('sNote_'+arguments[1]).value = '';

			delete DATA['date'];				// to prevent contamination between failed calls
			delete DATA['creator'];
			break;










		// -- SPECS Tab --


		   // OVERVIEW		Loads all the specs data for the module record
		   // SYNTAX		Application('LoadSpecs',sModule,id,sScript='application.php',mCallback='');
		case "LoadSpecs":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Asset Management'	
		   // 2: id		[number] The module database record number of the note							102	
		   // 3: sScript	[string] server-side script URL to process this request							'AssetManagement.php'	[application.php]
		   // 4: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var id = (arguments[2] == '+') ? '%2B' : arguments[2];									// NOTE: we have to take this step because PHP can convert '+' to ' ' causing an error
			var sScript = (arguments.length < 5) ? 'application.php' : arguments[4];
			var sType = (arguments[1] == 'Asset Management') ? 'asset' : 'inventory';

			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+id+'&sType='+sType,'',function(){Application('s_'+sAction,sCamelCase);});
			break;
		case "s_LoadSpecs":
		   // 1: sCamelCase	[string] The module name in CamelCase
			var s = XML.getElementsByTagName("spec");
			for (let i=0; i<s.length; i++) {
				switch(s[i].getAttribute('type')) {
					case "manufacturer":
						document.getElementById('sSpecManufacturer_'+arguments[1]).value = s[i].getAttribute('manufacturer');
						document.getElementById('sSpecOPoID_'+arguments[1]).value = s[i].getAttribute('OPoID');
						document.getElementById('nSpecPhone_'+arguments[1]).value = s[i].getAttribute('phone');
						document.getElementById('nSpecFax_'+arguments[1]).value = s[i].getAttribute('fax');
						document.getElementById('sSpecWebsite_'+arguments[1]).value = s[i].getAttribute('website');
						document.getElementById('sSpecMake_'+arguments[1]).value = s[i].getAttribute('make');
						document.getElementById('sSpecModel_'+arguments[1]).value = s[i].getAttribute('model');
						document.getElementById('sSpecVersion_'+arguments[1]).value = s[i].getAttribute('version');
						Listbox('SelectOption','sSpecUpdate_'+arguments[1],s[i].getAttribute("updating"));
						document.getElementById('sSpecDimensions_'+arguments[1]).value = s[i].getAttribute('dimensions').replace(/&quot;/g, '"');
						document.getElementById('sSpecWeight_'+arguments[1]).value = s[i].getAttribute('weight');
						document.getElementById('sSpecColor_'+arguments[1]).value = s[i].getAttribute('color');
						document.getElementById('sSpecPower_'+arguments[1]).value = s[i].getAttribute('power');
						document.getElementById('sSpecRunningTemp_'+arguments[1]).value = s[i].getAttribute('runningTemp');
						document.getElementById('sSpecRunningHumidity_'+arguments[1]).value = s[i].getAttribute('runningHumidity');
						document.getElementById('sSpecStorageTemp_'+arguments[1]).value = s[i].getAttribute('storageTemp');
						document.getElementById('sSpecStorageHumidity_'+arguments[1]).value = s[i].getAttribute('storageHumidity');
						document.getElementById('sSpecCertifications_'+arguments[1]).value = s[i].getAttribute('certifications');
						document.getElementById('sSpecWarranty_'+arguments[1]).value = s[i].getAttribute('warranty');
						Listbox('SelectOption','sSpecDesigned_'+arguments[1],s[i].getAttribute("designed"));
						Listbox('SelectOption','sSpecManufactured_'+arguments[1],s[i].getAttribute("manufactured"));
						break;
					case "vendor":
						for (let j=1; j<10; j++) {			// process the first 9 custom fields
							document.getElementById('sSpecTitle0'+j+'_'+arguments[1]).value = s[i].getAttribute('title0'+j);
							document.getElementById('sSpecValue0'+j+'_'+arguments[1]).value = s[i].getAttribute('value0'+j);
						}
						for (let j=10; j<20; j++) {			// process 10-19
							document.getElementById('sSpecTitle'+j+'_'+arguments[1]).value = s[i].getAttribute('title'+j);
							document.getElementById('sSpecValue'+j+'_'+arguments[1]).value = s[i].getAttribute('value'+j);
						}
						document.getElementById('sSpecTitle20_'+arguments[1]).value = s[i].getAttribute('title20');
						document.getElementById('sSpecValue20_'+arguments[1]).value = s[i].getAttribute('value20');
						break;
					case "internal":
						for (let j=21; j<30; j++) {			// process the first 9 custom fields
							document.getElementById('sSpecTitle'+j+'_'+arguments[1]).value = s[i].getAttribute('title0'+(j-20));
							document.getElementById('sSpecValue'+j+'_'+arguments[1]).value = s[i].getAttribute('value0'+(j-20));
						}
						for (let j=30; j<40; j++) {			// process 20-29
							document.getElementById('sSpecTitle'+j+'_'+arguments[1]).value = s[i].getAttribute('title'+(j-20));
							document.getElementById('sSpecValue'+j+'_'+arguments[1]).value = s[i].getAttribute('value'+(j-20));
						}
						document.getElementById('sSpecTitle40_'+arguments[1]).value = s[i].getAttribute('title20');
						document.getElementById('sSpecValue40_'+arguments[1]).value = s[i].getAttribute('value20');
						break;
				}
			}
			break;




		   // OVERVIEW		Saves the newly added note
		   // SYNTAX		Application('SaveSpecs',sModule,id,sScript='application.php',mCallback='');
		case "SaveSpecs":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Customer Accounts'	
		   // 2: id		[number] The module database record number of the contact; '+' adds a new record			102	
		   // 3: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'	[application.php]
		   // 4: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var id = (arguments[2] == '+') ? '%2B' : arguments[2];									// NOTE: we have to take this step because PHP can convert '+' to ' ' causing an error
			var sScript = (arguments.length < 5) ? 'application.php' : arguments[4];
			var sType = (arguments[1] == 'Asset Management') ? 'asset' : 'inventory';

// UPDATED 2025/07/03
//			ajax(reqInventory,4,'post',gbl_uriProject+"code/inventory.php",'action=save&target=specs&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+document.getElementById('sPSList_Inventory').options[document.getElementById('sPSList_Inventory').selectedIndex].value,'formSpecs_Inventory','','','','',"saveSpecs_Inventory('succ');","saveSpecs_Inventory('fail');","saveSpecs_Inventory('busy');","saveSpecs_Inventory('timeout');","saveSpecs_Inventory('inactive');");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+id+'&sType='+sType,'',null,null,null,null,'formSpecs_'+sCamelCase);
			break;










		// -- DATA Tab --


		   // OVERVIEW		Loads all the data uploads for the module record
		   // SYNTAX		Application('LoadData',sModule,sType='Business',sScript='application.php',mCallback='');
		case "LoadData":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: id		[number] The module database record number of the contact; '+' adds a new record			102	
		   // 2: sModule	[string] The module name										'Customer Accounts'	
		   // 3: nIndex		[number] The index number of the div containing the data uploads (e.g. 3 = divTabs_CustomerAccounts3)	3
		   // 4: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'	[application.php]
		   // 5: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var id = (arguments[1] == '+') ? '%2B' : arguments[1];									// NOTE: we have to take this step because PHP can convert '+' to ' ' causing an error
			var sCamelCase = arguments[2].replace(/ /g, "").replace(/&/g, "And");
			var nIndex = arguments[3];
			var sScript = (arguments.length < 5) ? 'application.php' : arguments[4];

			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+id+'&sModule='+escape(arguments[2]),'',function(){Application('s_'+sAction,sCamelCase,nIndex);});
			break;
		case "s_LoadData":
// NOTE: when updating the .html files, make ALL the objects in each <li> be generically named (e.g. sDataFilename1_MODULE) instead of specifically (e.g. sCustomerLogo_CustomerAccounts)
//	 see the 'NewData' block below to see the naming conventions to use
		   // 1: sCamelCase	[string] The module name in CamelCase
		   // 2: nIndex		[number] The index number of the div containing the data uploads
			var oLIs = $('#divTabs_'+arguments[1]+arguments[2].toString()+' ul li').get();
			var e = XML.getElementsByTagName("entry");
			var f = XML.getElementsByTagName("file");

			// add all the uploaded data files to each of the <select>'s
			for (let i=0; i<f.length; i++) {
				// add the selectable files to the 'template' <select> (which will automatically be added as the dynamic <select>'s are created below)
				Listbox('AddOption','sDataFilename_'+arguments[1],f[i].getAttribute('filename'),f[i].getAttribute('filename'),'','',false,false,true);
		
				// add the selectable files to any default static <select>'s
				for (let j=2; j<oLIs.length; j++) {			// NOTE: we start at 2 to skip the first <li> (to add a new entry) and the second <li> (which is the template)
					Listbox('AddOption','sDataFilename'+j.toString()+'_'+arguments[1],f[i].getAttribute('filename'),f[i].getAttribute('filename'),'','',false,false,true);
					document.getElementById('sDataFilename'+j.toString()+'_'+arguments[1]).selectedIndex = -1;		// de-select a value (which we'll handle below)
				}
			}
	
			// add all the dynamic <li>'s to the list and assign values to save <select>'s
			for (let i=0; i<e.length; i++) {
				// handle all the default static <select> assignments (e.g. Customer Logo)
				if (e[i].getAttribute("title") == oLIs[i+2].getElementsByTagName('labels')[0].innerHTML) {
					LIs[i+2].innerHTML = LIs[i+2].innerHTML.replace(/,0\);/, ','+e[i].getAttribute("id")+');');		// update all function calls on the line to use the database id for the record
					Listbox('SelectOption','sDataFilename'+i.toString()+'_'+arguments[1],e[I].getAttribute("filename"));	// WARNING: this line MUST come below the above line (since it overwrites the value set here)!
					continue;
				}
		
				// handle all the dynamic <select>'s (e.g. Video Drivers)
				let nIndex = Application('NewData','divTabs_'+arguments[1]+arguments[2].toString(),e[i].getAttribute("id"));	// a the new dynamic data entry <li>
				document.getElementById('sDataTitle'+nIndex+'_'+arguments[1]).value = e[i].getAttribute("title");		// set it's title value
				Listbox('SelectOption','sDataFilename'+nIndex+'_'+arguments[1],e[i].getAttribute("filename"));			// select it's saved uploaded file
			}
			break;




		   // OVERVIEW		Dynamically creates a new line to upload additional data in the module; returns index of newly added line
		   // SYNTAX		Application('NewData',sDiv,id=null,mCallback='');
		case "NewData":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sDiv		[string] The data upload <div> name									'divTabs_Employees3'	
		   // 2: id		[number] The database id of the entry (used when populating forms)					123			[null]
		   // 3: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			var oClone = $('#'+arguments[1]+' ul .liDataTemplate').clone();

// UPDATED 2025/07/06 - even default <select>'s use this syntax
//			var nLast = $('#'+arguments[1]+' ul li:last').html();
//			    nLast = /sDataTitle(\d+)/.exec(nLast);				// store all the results in an array
//			if (nLast == null) {nLast=1;} else {nLast=parseInt(nLast[0].substring(16))+1;}	// if there weren't any custom fields added, then start the index at 1, otherwise obtain the last one and increase it by 1
			var nLast = document.getELementById('divTabs_'+sCamelCase+nTab.toString()).getElementsByTagName('input').length - 1;	// NOTE: remove 1 to skip the 'add' <li> and count 'template's <li> as the new +1

			oClone.html(oClone.html().replace(/sDataTitle_/g, 'sDataTitle'+nLast+'_'));
			oClone.html(oClone.html().replace(/sDataFilename_/g, 'sDataFilename'+nLast+'_'));
			oClone.html(oClone.html().replace(/oDataSave_/g, 'oDataSave'+nLast+'_'));
			oClone.html(oClone.html().replace(/oDataDelete_/g, 'oDataDelete'+nLast+'_'));
			oClone.html(oClone.html().replace(/oDataEncrypt_/g, 'oDataEncrypt'+nLast+'_'));
			oClone.html(oClone.html().replace(/nIndex/g,nLast));
			if (arguments.length > 2) { oClone.html(oClone.html().replace(/,0\);/, ','+arguments[2]+');')); }			// if the 'intID' was passed, then adjust the <li> just created

			$('#'+arguments[1]+' ul').append("<li>" + oClone.html());			// http://stackoverflow.com/questions/16744594/how-to-use-jquery-to-add-form-elements-dynamically
			return nLast;
			break;




		   // OVERVIEW		Saves the (new) data field information
		   // SYNTAX		Application('SaveData',sModule,sTitle,sFilename,nFile,nModule,nTab,nIndex,nSkip=0,sScript='application.php',mCallback='');
		case "SaveData":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   //  1: sModule	[string] The module name										'Customer Accounts'	
		   //  2: sTitle	[string] The title of the data field									'License Certificate'
		   //  3: sFilename	[string] The name of the file associated with this data field						'2025 CPA License.jpg'
		   //  4: nFile		[number] The database id for the file; '+' adds a new record						102	
		   //  5: nModule	[number] The database id for the module record that the file is associated with				46
		   //  6: nTab		[number] The tab index number of the data <div>  (e.g. 3 = divTabs_CustomerAccounts3)			3
		   //  7: nIndex	[number] The index number of the <li> containing the to-be-saved data fields				5
		   //  8: nSkip		[number] The number of default <li> data fields associated with the module				1			[0]
		   //  9: sScript	[string] server-side script URL to process this request							'CustomerAccounts.php'	[application.php]
		   // 10: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sModule = arguments[1];
			var sTitle = arguments[2];
			var sFilename = arguments[3];
			var id = (arguments[4] == '+') ? '%2B' : arguments[4];			// NOTE: we have to take this step because PHP can convert '+' to ' ' causing an error
			var nModule = arguments[5];
			var nTab = arguments[6];
			var nIndex = arguments[7];
			var nSkip = arguments[8];
			var sScript = (arguments.length < 10) ? 'application.php' : arguments[9];

			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var oTextboxes = document.getELementById('divTabs_'+sCamelCase+nTab.toString()).getElementsByTagName('input');
			var oListboxes = document.getELementById('divTabs_'+sCamelCase+nTab.toString()).getElementsByTagName('select');

			// prevents duplicate titles from getting added
			for (let i=0; i<oTextboxes.length; i++) {
				if (oTextboxes[i].type != 'textbox') { continue; }		// skip buttons
				if (oTextboxes[i].value == arguments[2]) {
					Project(_sProjectUI,'fail',"An entry with that title already exists in the list.");
					return false;
				}
			}

			// prevents files from being selected for than once (by accident)
			for (let i=1; i<oListboxes.length; i++) {				// NOTE: we start at 1 to avoid processing the template <select>
				if (i == arguments[7]) { continue; }				// skip processing itself (which will trip the error)
				if (oListboxes[i].selectedIndex == oListboxes[arguments[7]].selectedIndex) {
					Project(_sProjectUI,'fail',"An entry with that file selection already exists in the list.");
					return false;
				}
			}

// UPDATED 2025/07/03
//			ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=update&target=upload&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&module='+escape(strModule)+'&title='+escape(strTitle)+'&filename='+escape(strFilename)+'&record='+intRecord+'&id='+intID,'','','','','',"updateDataFields('succ','"+strFormID+"','"+strModule+"',"+intSkip+",'"+intIndex+"','"+strTitle+"','"+strFilename+"',"+intRecord+","+intID+");","updateDataFields('fail','"+strFormID+"','"+strModule+"',"+intSkip+",'"+intIndex+"','"+strTitle+"','"+strFilename+"',"+intRecord+","+intID+");","updateDataFields('busy','"+strFormID+"','"+strModule+"',"+intSkip+",'"+intIndex+"','"+strTitle+"','"+strFilename+"',"+intRecord+","+intID+");","updateDataFields('timeout','"+strFormID+"','"+strModule+"',"+intSkip+",'"+intIndex+"','"+strTitle+"','"+strFilename+"',"+intRecord+","+intID+");","updateDataFields('inactive','"+strFormID+"','"+strModule+"',"+intSkip+",'"+intIndex+"','"+strTitle+"','"+strFilename+"',"+intRecord+","+intID+");");
			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&sModule='+sModule+',nModule='+nModule+'&id='+id+'&sTitle='+sTitle+'&sFilename='+escape(strFilename),'oDataSave'+nIndex+'_'+sCamelCase,function(){Application('s_'+sAction,sCamelCase,nTab,nIndex,nSkip);});
			break;
		case "s_SaveData":
		   // 1: sCamelCase	[string] The module name in CamelCase
		   // 2: nTab		[number] The tab index number of the data <div>  (e.g. 3 = divTabs_CustomerAccounts3)			3
		   // 3: nIndex		[number] The index number of the <li> containing the to-be-saved data fields				5
//		   // 4: nSkip		[number] The number of default <li> data fields associated with the module				1			[0]
			// send a notification to the user			NOTE: this had to come above the following calls so the message would get displayed (it was '1' otherwise)
			Project(_sProjectUI,'succ');

			// if <s><data>...</data></s> was returned, then we need to adjust the account number
			if (DATA.hasOwnProperty("id")) {						// if this is a CREATION event, then...
// UPDATED 2025/07/08 - removed the usage of jQuery
//				var oLIs = $('#divTabs_'+arguments[1]+arguments[2].toString()+' ul li').get();
				var oLIs = document.getElementById('divTabs_'+arguments[1]+arguments[2].toString()).getElementsByTagName('li');
				var sTitle = document.getElementById('sDataTitle'+arguments[3]+'_'+arguments[1]).value;	// backup the values prior to adjusting the HTML below (since it erases them)
				var nFile = document.getElementById('sDataFilename'+arguments[3]+'_'+arguments[1]).selectedIndex;

// UPDATED 2025/07/08 - we need to allow ALL of the data fields to be saved (pre-defined and dynamically added)
//				for (var i=arguments[4]; i<oLIs.length; i++) {				// find the <li> that needs to update its id value (so that we know to use UPDATE instead of INSERT in mySQL calls)
				for (var i=2; i<oLIs.length; i++) {					// NOTE: skip 2 for the 'add' and 'template' <li>
					if (oLIs[i].innerHTML.match('sDataTitle'+arguments[3]+'_'+arguments[1])) {
						oLIs[i].innerHTML = oLIs[i].innerHTML.replace(/,0\);/, ','+DATA['id']+');');
						break;
					}
				}

				// now restore the values after we've adjusted the HTML above!
				document.getElementById('sDataTitle'+arguments[3]+'_'+arguments[1]).value = sTitle;
				document.getElementById('sDataFilename'+arguments[3]+'_'+arguments[1]).selectedIndex = nFile;

				delete DATA['id'];				// to prevent contamination between failed calls
			}
			break;




		   // OVERVIEW		Actually deletes the selected data field in the database
		   // SYNTAX		Application('DeleteData',sModule,sType='Customer',sScript='application.php',mCallback='');
		case "DeleteData":
		   // OVERVIEW		Disables the selected data field to appear as being deleted
		   // SYNTAX		Application('DisableData',sModule,sType='Customer',sScript='application.php',mCallback='');
		case "DisableData":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The module name										'Employees'	
		   // 2: nFile		[number] The database id for the file									102	
		   // 3: nTab		[number] The tab index number of the data <div>  (e.g. 3 = divTabs_CustomerAccounts3)			3
		   // 4: nIndex		[number] The index number of the <li> containing the to-be-saved data fields				5
		   // 5: sScript	[string] server-side script URL to process this request							'Employees.php'		[application.php]
		   // 6: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sCamelCase = arguments[1].replace(/ /g, "").replace(/&/g, "And");
			var id = arguments[2];
			var nTab = arguments[3];
			var nIndex = arguments[4];
			var sScript = (arguments.length < 6) ? 'application.php' : arguments[5];

			Ajax('Call',_oDashboard,_sUriProject+"code/"+sScript,'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&id='+id,'oDataDelete_'+sCamelCase,function(){Application('s_'+sAction,sCamelCase,nTab,nIndex);});
			break;
		case "s_DeleteData":
		case "s_DisableData":
		   // 1: sCamelCase	[string] The module name in CamelCase
		   // 2: nTab		[number] The tab index number of the data <div>  (e.g. 3 = divTabs_CustomerAccounts3)			3
		   // 3: nIndex		[number] The index number of the <li> containing the to-be-saved data fields				5
			// send a notification to the user
			Project(_sProjectUI,'succ');

			// delete the selected item from the list
// UPDATED 2025/07/08 - removed the usage of jQuery
//			var LIs = $('#'+strFormID+' ul li').get();
//			for (let i=intSkip; i<LIs.length; i++)
//				{ if (LIs[i].innerHTML.match('sCustomFileTitle'+intIndex+'_'+camelcase)) {$('#'+strFormID+' ul li').eq(i).remove(); break;} }
			var oUL = document.getElementById('divTabs_'+arguments[1]+arguments[2].toString()).getElementsByTagName('ul')[0];
			oUL.removeChild(oUL.childNodes[arguments[3]]);
			break;
	}


	// Perform any passed callback
	if (sAction.substring(0,2) == 's_') {								// only execute these lines if a 'success' return has been made
		if (typeof(mCallback) === 'function') { mCallback(); }					// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
		else if (typeof(mCallback) === 'string') { eval(mCallback); }				// using this line, the value can be passed as: "alert('hello world');"
	}
}










// -- Module API --

function Module(sAction,Callback) {
// sAction	the available actions that this function can process: close (module), focus (module) [load (module) [install (module)]], navigate (module) [screen (focus), buttons (list)], transpose (values)
// Callback	the callback to execute upon success; value can be a string or function()
	var HTML = "";
	
	switch(sAction) {
		// --- BUILT-IN ACTIONS ---
		// these will refer to the Application API above


		// --- CUSTOM ACTIONS ---


		// Close the Active Module
		case "close":			// closes the module screen that is currently in focus
			var TASKBAR = document.getElementById('ulTaskbar');
			var ICONS = TASKBAR.getElementsByTagName('li');				// stores all the <li> nodes in the <ul>

			for (var i=0; i<ICONS.length; i++) {						// go through each opened screen to close the one that's currently viewed...
				if (ICONS[i].className.match(/ liSel/)) {				// if we found the selected icon, then...
// UPDATED 2025/10/01
//					if (i == 0) { return true; }					//    exit this function if the user is trying to close the Dashboard
					if (i < 2) { return true; }					//    exit this function if the user is trying to close the Dashboard or Social

					var MODULE = document.getElementById('div'+__sScreens[i]);	//    delete the module screen <div>
					MODULE.parentNode.removeChild(MODULE);

					TASKBAR.removeChild(ICONS[i]);					// delete the module screen icon in the footer

					__sScreens.splice(i, 1);					// delete the array value of the module screen to close
					break;
				}
			}
// DEPRECATED 2025/02/16
//			document.getElementById('divAddons').style.display='none';			// hide any opened CSIO screens
//			document.getElementById('divBug').style.display='none';
//			document.getElementById('divFeature').style.display='none';

			document.getElementById('liDashboard').click();					// return the user back to the dashboard
			break;




// VER2 - replace this with the Tab('Select') library call
		// Focus Active Module
		case "focus":			// changes the active modules to match the icon clicked in the taskbar	EXAMPLES
		   // arguments[2]		[string] the (unedited) module name to bring into focus			"System Configuration"
// UPDATED 2025/03/01
//			arguments[2] = arguments[2].replace(/&/g,'And');				// replace any '&' characters with 'And' to prevent processing errors below
			var MODULE = arguments[2].replace(/&/g,'And').replace(/ /g,'');			// condense module names: 'Application Settings' to 'ApplicationSettings' (aka CamelCase)
			var TASKBAR = document.getElementById('ulTaskbar').getElementsByTagName('li');	// stores all the <li> nodes in the <ul>

			for (var i=0; i<__sScreens.length; i++) {					// hide any currently viewed module screen and module icon in the footer
				document.getElementById('div'+__sScreens[i]).style.display='none';
				TASKBAR[i].className = TASKBAR[i].className.replace(/ liSel/g,'');
			}
// DEPRECATED 2025/02/21
//			document.getElementById('divAddons').style.display = 'none';			// also hide the other default screens (in case one of them was currently being viewed)

			// NOTE: the below three lines are for this project since we don't want the ability to close any 'extensions' (e.g. Admin)
// UPDATED 2025/10/01
//			if (arguments[2] == 'Dashboard' && document.getElementById('imgClose_Dashboard'))  // dis/enable the close image depending on the module screen selected;	WARNING: the 2nd part of the 'if' is neccessary on mobile since this object gets erased
			if ((arguments[2] == 'Dashboard' || arguments[2] == 'Social') && document.getElementById('imgClose_Dashboard'))  // dis/enable the close image depending on the module screen selected;	WARNING: the 2nd part of the 'if' is neccessary on mobile since this object gets erased
// UPDATED 2025/05/14
//				{ document.getElementById('imgClose_Dashboard').src = 'home/'+getCookie('sUsername')+'/imgs/close_disabled.png'; }
				{ document.getElementById('imgClose_Dashboard').src = 'home/'+Cookie('Obtain','sUsername')+'/imgs/close_disabled.png'; }
			else if (document.getElementById('imgClose_Dashboard'))
// UPDATED 2025/05/14
//				{ document.getElementById('imgClose_Dashboard').src = 'home/'+getCookie('sUsername')+'/imgs/close.png'; }
				{ document.getElementById('imgClose_Dashboard').src = 'home/'+Cookie('Obtain','sUsername')+'/imgs/close.png'; }

			if (document.getElementById('div'+MODULE)) {					// if the module is already opened, then just switch to it (otherwise, we need to open that module)
				document.getElementById('div'+MODULE).style.display = 'block';		// show the <div> we just created
				for (i=0; i<__sScreens.length; i++)					// find and assign the correct class to the appropriate module icon in the footer
					{ if (MODULE == __sScreens[i]) {TASKBAR[i].className += ' liSel'; break;} }

				if (Mobile) {								// if we're on a mobile device, we need to take additional steps...
					document.getElementById('divOverlay').style.display = 'none';
					document.getElementById('divFooter').style.display = 'none';
				}

				return true;								// exit if we just needed to show an existing screen!
			}

			// if we've made it here, then we need to open the requested module (e.g. add icon in footer, open a screen for its content, etc)
			__sScreens.push(MODULE);							// add the screen to the global variable storing all the opened screens

			var DIV = document.createElement('div');					// create a new <div> to receive the contents of the module
			DIV.id = 'div'+MODULE;								// give the new <div> an id
// REMOVED 2021/03/18 - this is not needed as we can explicitly access this <div> via the id defined in the line above
//			DIV.className = MODULE;								// add the class to the <div> for visual formatting specific to this module
			document.getElementById('divContainer_Dashboard').appendChild(DIV);		// add the <div> to the form
// LEFT OFF - move the css into the .css file;  call it 'AbsoluteCenter'
// UPDATED 2025/05/14
//			document.getElementById(DIV.id).innerHTML = "<img src='home/"+getCookie('sUsername')+"/imgs/loading.gif' style='position: absolute; left: 50%; top: 50%; margin-left: -64px; margin-top: -64px; width: 128px; height: 128px;' />";
			document.getElementById(DIV.id).innerHTML = "<img src='home/"+Cookie('Obtain','sUsername')+"/imgs/loading.gif' style='position: absolute; left: 50%; top: 50%; margin-left: -64px; margin-top: -64px; width: 128px; height: 128px;' />";
			document.getElementById(DIV.id).style.display='block';				// show the <div> we just created

// UPDATED 2025/03/01
//			$("#ulTaskbar").append("<li class='liTab liSel' onClick=\"Module('focus',null,'"+arguments[2]+"'); adjTabs('ulTaskbar','liTab','liSel',this);\"><img title='"+arguments[2]+"' src='home/"+getCookie('sUsername')+"/imgs/webbooks.module."+arguments[2].toLowerCase().replace(/ /g,'_')+".png' class='Screens' />");
// UPDATED 2025/05/14
//			$("#ulTaskbar").append("<li class='liTab liSel' onClick=\"Module('focus',null,'"+arguments[2]+"'); adjTabs('ulTaskbar','liTab','liSel',this);\"><img title='"+arguments[2]+"' src='home/"+getCookie('sUsername')+"/imgs/"+MODULE+".png' class='Screens' />");
// UPDATED 2025/05/18
//			$("#ulTaskbar").append("<li class='liTab liSel' onClick=\"Module('focus',null,'"+arguments[2]+"'); adjTabs('ulTaskbar','liTab','liSel',this);\"><img title='"+arguments[2]+"' src='home/"+Cookie('Obtain','sUsername')+"/imgs/"+MODULE+".png' class='Screens' />");
			$("#ulTaskbar").append("<li class='liTab liSel' onClick=\"Module('focus',null,'"+arguments[2]+"'); Tabs('Select','ulTaskbar',this,'liTab','liSel');\"><img title='"+arguments[2]+"' src='home/"+Cookie('Obtain','sUsername')+"/imgs/"+MODULE+".png' class='Screens' />");

			Module('load', null, arguments[2]);						// load the requested module
			break;




// UPDATED 2025/03/01
//		// Loads files that aren't already loaded								SUPPLEMENTAL TO: focus()
//		case "load":			// checks if the modules' .css/.js files have been loaded already	EXAMPLES
//		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
//		   // arguments[2]		[string] the name of the module						"Application Settings"
//			// test if the external files have already been loaded, and exit if they have!
//			var CamelCase = arguments[2].replace(/&/g,'And').replace(/ /g,'');		// CamelCase the passed module name for HTML syntax
//			var File = arguments[2].replace(/&/g,'And').replace(/ /g,'_')			// Underscore the passed module name for file io
//			var Scripts = document.getElementsByTagName("head")[0].getElementsByTagName('script');		// store all the external referenced <script> files from the page <head>
//
//			for (var i=0; i<Scripts.length; i++) {						// traverse each script to see if we already have the modules' files loaded
//				if (Scripts[i].src.indexOf(File) > -1) {				// if the external file has already been added to the DOM (aka the module has already been opened), init the form then exit!
//// OLD					ajax(_oDashboard,4,'post',_sUriProject+"code/"+File+".php",'action=init&target=screen&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'','','','div'+CamelCase,'',"init_"+CamelCase+"('req')",'',"alert('ERROR: The server is too busy with requests at the moment.');","alert('ERROR: The server has timed out the request to load the module.');","alert('ERROR: The server appears to be inactive while attempting to load the module.');");
//// NEW					ajax(_oDashboard,4,'post',_sUriProject+"code/"+File+".php",'A='+sAction+'&T=module&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')),'','','','div'+CamelCase,'',CamelCase+"('load');",function(){Dashboard('fail',Callback);},function(){Dashboard('busy',Callback);},function(){Dashboard('timeout',Callback);},function(){Dashboard('inactive',Callback););
//// COMBINATION TO PRESERVE FUNCTIONALITY
//					ajax(_oDashboard,4,'post',_sUriProject+"code/"+File+".php",'A=initialize&T=module&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')) + '&action=init&target=screen&username='+getCookie('sUsername')+'&SID='+escape(getCookie('sSessionID')),'','','','div'+CamelCase,'',"if(typeOf init_"+CamelCase+"==='function'){init_"+CamelCase+"('req');}else{"+CamelCase+"('load');}",function(){Application('fail',Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback);});
//					return true;
//				}
//			}
//
//			// if we've made it down here, then we need to download the files...
//			// WARNING: the first line below loads the screen CONTENTS (not the form values)!!!
//			var D = new Date();						// this is used as a cache buster
//// OLD			Module('install',null,'link', "home/"+getCookie('sUsername')+"/look/"+File+".css?cache="+D.getTime(), function(){ ajax(_oDashboard,4,'post',_sUriProject+"code/"+File+".php",'action=init&target=screen&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'','','','div'+CamelCase,'','','',"alert('ERROR: The server is too busy with requests at the moment.');","alert('ERROR: The server has timed out the request to load the module.');","alert('ERROR: The server appears to be inactive while attempting to load the module.');"); });
//// NEW			Module('install',null,'link', "home/"+getCookie('sUsername')+"/look/"+File+".css?cache="+D.getTime(), function(){ ajax(_oDashboard,4,'post',_sUriProject+"code/"+File+".php",'A='+sAction+'&T=module&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')),'','','','div'+CamelCase,'',CamelCase+"('load');",function(){Dashboard('fail',Callback);},function(){Dashboard('busy',Callback);},function(){Dashboard('timeout',Callback);},function(){Dashboard('inactive',Callback);}); });
//// COMBINATION TO PRESERVE FUNCTIONALITY
//			Module('install',function(){ ajax(_oDashboard,4,'post',_sUriProject+"code/"+File+".php",'A=initialize&T=module&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')),'','','','div'+CamelCase,'',CamelCase+"('initialize');",function(){Application('fail',Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback);}); },'link', "home/"+getCookie('sUsername')+"/look/"+File+".css?cache="+D.getTime());
//			Module('install',null,'script',"code/"+File+".js?cache="+D.getTime());
//			break;
////			"alert('success loading html'); if(typeOf init_"+CamelCase+"==='function'){init_"+CamelCase+"('req');}else{"+CamelCase+"('load');}",
////			function(){Module('s_'+Action,Callback,CamelCase);},
////		case "s_load":			// checks if the modules' .css/.js files have been loaded already	EXAMPLES
////alert('SUCCESS!!!!');
////			if (typeOf init_"+CamelCase+"==='function'){alert('legacy'); init_"+CamelCase+"('req');}else{alert('new way'); "+CamelCase+"('load');}
////			break;
		// Loads files that aren't already loaded						SUPPLEMENTAL TO: focus()
		case "load":			// checks if the modules' .css/.js files have been loaded already	EXAMPLES
		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
		   // arguments[2]		[string] the name of the module						"Application Settings"
			// test if the external files have already been loaded, and exit if they have!
			var MODULE = arguments[2].replace(/&/g,'And').replace(/ /g,'');			// condense module names: 'Application Settings' to 'ApplicationSettings' (aka CamelCase)
			var SCRIPTS = document.getElementsByTagName("head")[0].getElementsByTagName('script');		// store all the external referenced <script> files from the page <head>

			// WARNING: we had to construct a string to pass for success since the MODULE variable will be destroyed by the time the 'success' function is called
			// 		DEV NOTE: just erase the 'else{...}' and '&action=init&...' portions below to remove LEGACY
			var sSuccess = "if(typeof "+MODULE+"==='function'){"+MODULE+"('Initialize');}else{init_"+MODULE+"('req');}";

			for (var i=0; i<SCRIPTS.length; i++) {						// traverse each script to see if we already have the modules' files loaded
				if (SCRIPTS[i].src.indexOf(MODULE+'.css') > -1) {			// if the external file has already been added to the DOM (aka the module has already been opened), init the form then exit!
// OLD					ajax(_oDashboard,4,'post',_sUriProject+"code/"+MODULE+".php",'action=init&target=screen&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'','','','div'+CamelCase,'',"init_"+CamelCase+"('req')",'',"alert('ERROR: The server is too busy with requests at the moment.');","alert('ERROR: The server has timed out the request to load the module.');","alert('ERROR: The server appears to be inactive while attempting to load the module.');");
// NEW					ajax(_oDashboard,4,'post',_sUriProject+"code/"+MODULE+".php",'A='+sAction+'&T=module&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID')),'','','','div'+CamelCase,'',CamelCase+"('load');",function(){Dashboard('fail',Callback);},function(){Dashboard('busy',Callback);},function(){Dashboard('timeout',Callback);},function(){Dashboard('inactive',Callback););
// COMBINATION TO PRESERVE FUNCTIONALITY
// UPDATED 2025/05/14
//					ajax(_oDashboard,4,'post',_sUriProject+"code/"+MODULE+".php",'A=Load&T=Module&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')) + '&action=init&target=screen&username='+getCookie('sUsername')+'&SID='+escape(getCookie('sSessionID')),'','','','div'+MODULE,'',sSuccess,function(){Application('fail',Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback);});
// UPDATED 2026/06/28
//					ajax(_oDashboard,4,'post',_sUriProject+"code/"+MODULE+".php",'A=Load&T=Module&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID')) + '&action=init&target=screen&username='+Cookie('Obtain','sUsername')+'&SID='+escape(Cookie('Obtain','sSessionID')),'','','','div'+MODULE,'',sSuccess,function(){Application('fail',Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback);});
					Ajax('Call',_oDashboard,_sUriProject+"code/"+MODULE+".php",'!Load!,>Module<,(sUsername),(sSessionID)','',sSuccess,null,null,null,null,null,'div'+MODULE);
					return true;
				}
			}

			// if we've made it down here, then we need to download the files...
			// WARNING: the first line below loads the screen CONTENTS (not the form values)!!!
			var D = new Date();						// this is used as a cache buster
// OLD			Module('install',null,'link', "home/"+getCookie('sUsername')+"/look/"+MODULE+".css?cache="+D.getTime(), function(){ ajax(_oDashboard,4,'post',_sUriProject+"code/"+MODULE+".php",'action=init&target=screen&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'','','','div'+CamelCase,'','','',"alert('ERROR: The server is too busy with requests at the moment.');","alert('ERROR: The server has timed out the request to load the module.');","alert('ERROR: The server appears to be inactive while attempting to load the module.');"); });
// NEW			Module('install',null,'link', "home/"+Cookie('Obtain','sUsername')+"/look/"+MODULE+".css?cache="+D.getTime(), function(){ ajax(_oDashboard,4,'post',_sUriProject+"code/"+MODULE+".php",'A='+sAction+'&T=module&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID')),'','','','div'+CamelCase,'',CamelCase+"('load');",function(){Dashboard('fail',Callback);},function(){Dashboard('busy',Callback);},function(){Dashboard('timeout',Callback);},function(){Dashboard('inactive',Callback);}); });
// COMBINATION TO PRESERVE FUNCTIONALITY
// UPDATED 2025/05/14
//			Module('install',null,'link', "home/"+getCookie('sUsername')+"/look/"+MODULE+".css?cache="+D.getTime());
//			Module('install',function(){ ajax(_oDashboard,4,'post',_sUriProject+"code/"+MODULE+".php",'A=Load&T=Module&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')) + '&action=init&target=screen&username='+getCookie('sUsername')+'&SID='+escape(getCookie('sSessionID')),'','','','div'+MODULE,'',sSuccess,function(){Application('fail',Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback);}); },'script',"code/"+MODULE+".js?cache="+D.getTime());
			Module('install',null,'link',"home/"+Cookie('Obtain','sUsername')+"/look/"+MODULE+".css?cache="+D.getTime());
// UPDATED 2025/06/28
//			Module('install',function(){ ajax(_oDashboard,4,'post',_sUriProject+"code/"+MODULE+".php",'A=Load&T=Module&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID')) + '&action=init&target=screen&username='+Cookie('Obtain','sUsername')+'&SID='+escape(Cookie('Obtain','sSessionID')),'','','','div'+MODULE,'',sSuccess,function(){Application('fail',Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback);}); },'script',"code/"+MODULE+".js?cache="+D.getTime());
			Module('install',function(){ Ajax('Call',_oDashboard,_sUriProject+"code/"+MODULE+".php",'!Load!,>Module<,(sUsername),(sSessionID)','',sSuccess,null,null,null,null,null,'div'+MODULE); },'script',"code/"+MODULE+".js?cache="+D.getTime());
			break;


		// Downloads and loads the file										SUPPLEMENTAL TO: load()
		case "install":			// download the modules' .css/.js files	on-demand (lower resources)	EXAMPLES					NOTE: this is already in _ajax.js; substitue for that one VER2
		   // 				http://stackoverflow.com/questions/8586446/dynamically-load-external-javascript-file-and-wait-for-it-to-load-without-usi
		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
		   // arguments[2]		[string] the type of file to load: link, script				"script"
		   // arguments[3]		[string] the URI of the file to load					"code/Work_Orders.js"
			var REF = document.createElement(arguments[2]);

			if (arguments[2] == 'script') {
				REF.setAttribute("type","text/javascript");
				REF.setAttribute("src",arguments[3]);
			} else	if (arguments[2] == 'link') {
				REF.setAttribute("type","text/css");
				REF.setAttribute("rel","stylesheet");
				REF.setAttribute("href",arguments[3]);
			}
			document.getElementsByTagName('head')[0].appendChild(REF);

			if (Callback == '') { return true; }

			REF.async = true;
			REF.onload = function() {
				// http://stackoverflow.com/questions/1042138/javascript-check-if-function-exists/1042154#1042154
				if (typeof(Callback) === 'function') { Callback(); }					// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
				else { eval(Callback); }								// using this line, the value can be passed as: "alert('hello world');"
			};
			break;



// VER2 - replace this with the Tab('Select') library call
		// Syncs objects to the selected tab									SUPPLEMENTAL TO: load()
		case "navigate":		// download the modules' .css/.js files	on-demand (lower resources)	EXAMPLES					NOTE: this is already in _ajax.js; substitue for that one VER2
		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
		   // arguments[2]		[string/object] the tab object that was just clicked			this
		   // arguments[3]		[string] the 'id' prefix of the modules' screen <div>			"divTabs_Employees"
		   // arguments[4]		[string] [OPTIONAL] the URI to access for the 'Help' tab iframe		"https://wiki.mydomain.com"
		   // arguments[5]		[string/object] the 'id'/object storing the buttons			"ulButtons_Employees"
		   // arguments[*]		[string] [PAIRED] first is button text, second is onClick javascript	"'Save','SaveMe();','Delete','DeleteMe();'",...
			var s_Buttons = new Array();
			var oTab = (typeof arguments[2] === "object") ? arguments[2] : document.getElementById(arguments[2]);
			var oParent = oTab.parentNode;
			var oTabs = oParent.getElementsByTagName('li');
			var nIndex = 0;

			for (var i=6; i<arguments.length; i++) { s_Buttons[i-6] = arguments[i]; }	// transpose the list of buttons
			if (s_Buttons.length == 0) { s_Buttons = null; }				// if no buttons were passed, then convert this from a blank array to being null
			for (var i=0; i<oTabs.length; i++)						// determine the index of the clicked tab
				{ if (oTab == oTabs[i]) {nIndex = i; break;} }

// UPDATED 2025/05/18
//			adjTabs(Parent.id,'liTab','liSel',Tab);
			Tabs('Select',oParent.id,oTab,'liTab','liSel');
			Module('buttons',null,arguments[5],s_Buttons);
			Module('screen',null,arguments[3],nIndex,arguments[4]);

			if (typeof(Callback) === 'function') { Callback(); }				// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
			else { eval(Callback); }							// using this line, the value can be passed as: "alert('hello world');"
			break;




// VER2 - replace this with the Tab('Select') library call
		// Changes Active Screen
		case "screen":			// changes the screen to match the tab clicked at the top of each page	EXAMPLES
		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
		   // arguments[2]		[string] the general portion of the screen id value to adjust		"divTabs_Application"
		   // arguments[3]		[number] the index of arguments[2] that needs to be active		0	(means 'divTabs_Application0')
		   // arguments[4]		[string] [OPTIONAL] the URI to access for the 'Help' tab iframe		"https://wiki.mydomain.com"
			for (var i=0; i<100; i++) {							// cycle through the maximum of 100 tabs
				if (! document.getElementById(arguments[2]+i)) { break; }		// if the iterated tab does NOT exist, then we've traversed them all
				document.getElementById(arguments[2]+i).style.display = 'none';		// otherwise hide every tabs' associated screen
			}
			document.getElementById(arguments[2]+arguments[3]).style.display = 'block';	// now specifically turn on the screen that matches the tab just clicked

			if (Mobile) {									// if we're on a mobile device, we need to take additional steps...
				document.getElementById('divOverlay').style.display = 'none';
				document.getElementById('ulTabs'+arguments[2].substr(arguments[2].indexOf('_'))).style.display = 'none';
				document.getElementById('ulButtons'+arguments[2].substr(arguments[2].indexOf('_'))).style.display = 'none';
			}

			if (arguments.length > 4) {							// Lastly, if the user clicked on a 'Help' tab, then...
				if (! arguments[4]) { return true; }					//   if it's 'null' or 'undefined', then exit

				var iframe = document.getElementById(arguments[2]+arguments[3]).getElementsByTagName('iframe')[0];

				document.body.style.cursor = "wait";					// indicate that work is being done
				if (iframe.src == 'about:blank') { iframe.src = arguments[4]; }		// if the iframe doesn't have any page loaded, then load the one passed!
			}

			if (typeof(Callback) === 'function') { Callback(); }				// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
			else { eval(Callback); }							// using this line, the value can be passed as: "alert('hello world');"
			break;




		// Update the Buttons
		case "buttons":			// updates the top buttons to correspond with the newly clicked tab	EXAMPLES
		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
		   // arguments[2]		[string/object] the screen id that match the tab that was just clicked	"ulButtons_Employees"
		   // arguments[*]		[string] [PAIRED] first is button text, second is onClick javascript	"'Save','SaveMe();','Delete','DeleteMe();'",...
			var Buttons = new Array();
			var oLI, oUL=(typeof arguments[2] === "object") ? arguments[2] : document.getElementById(arguments[2]);

			oUL.innerHTML = '';								// erase the currently displayed buttons; clears <ul>
			if (! arguments[3]) { return true; }						// if a null value was passed, then exit this function

			if (Array.isArray(arguments[3])) { Buttons = arguments[3]; }			// if arguments[3] ITSELF is an array, then copy it
			else { for (var i=3; i<arguments.length; i++) {Buttons[i-3]=arguments[i];} }	// otherwise transpose the arguments[] list of buttons (as strings)

			for (var i=0; i<Buttons.length; i=i+2) {
				oLI = document.createElement("li");					// create a new <li> each iteration
				oLI.innerHTML = "<input type='button' value='"+Buttons[i]+"' class='button' onClick=\""+Buttons[i+1]+"\" />\n";
				oUL.appendChild(oLI);
			}

			if (typeof(Callback) === 'function') { Callback(); }				// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
			else { eval(Callback); }							// using this line, the value can be passed as: "alert('hello world');"
			break;




		// Transpose Field Data
		case "transpose":		// copy values to other objects (e.g. Main Address > Billing Address)
		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
		   // arguments[*]		[string] [PAIRED] first is source, second is target			"'sMainAddr1','sBillAddr1','sMainAddr2','sBillAddr2'",...
			for (var i=2; i<arguments.length; i=i+2) {
				if (document.getElementById(arguments[i]).type == "text")
					{ document.getElementById(arguments[i+1]).value = document.getElementById(arguments[i]).value; }
				else if (document.getElementById(arguments[i]).type == "select" || document.getElementById(arguments[i]).type == "select-one" || document.getElementById(arguments[i]).type == "select-multiple")
// UPDATED 2025/07/09
//					{ selListbox(arguments[i+1],document.getElementById(arguments[i]).value); }
					{ Listbox('SelectOption',arguments[i+1],document.getElementById(arguments[i]).value); }
			}
			break;
	}
}










// -- Widgets API --

function Widgets(sAction,Callback) {
// sAction	the available actions that this function can process: close, load, details, adjust
// Callback	the callback to execute upon success; value can be a string or function()
	var HTML = "";
	
	switch(sAction) {
		// --- BUILT-IN ACTIONS ---
		// these will refer to the Dashboard API above


		// --- CUSTOM ACTIONS ---


		// Close Widgets Popup
		case "close":
			document.getElementById('divOverlay').style.display = 'none';
			document.getElementById('divPopup').style.display = 'none';
			document.getElementById('divPopup').className=document.getElementById('divPopup').className.replace(/ divPopupMax/g,'');		// revert the popup size to its original value
			break;




		// Load List of Widgets
// LEFT OFF - these do NOT work
		case "load":			// shows all the available widgets for the project			EXAMPLES
		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
		   // arguments[2]		[string] the type of extension to list: exts, themes, icons		exts
// UPDATED 2025/05/14
//			HTML =	"<img src='home/"+getCookie('sUsername')+"/imgs/loading.gif' class='loadPopup' />";
			var HTML =	"<img src='home/"+Cookie('Obtain','sUsername')+"/imgs/loading.gif' class='loadPopup' />";

			if (arguments.length < 3) { arguments[2] = 'exts'; }

			document.getElementById('divPopup').innerHTML = HTML;
//			document.getElementById('divOverlay').style.display = 'block';
			document.getElementById('divPopup').className=document.getElementById('divPopup').className += ' divPopupMax';
//			document.getElementById('divPopup').style.display = 'block';

			Project('Popup','show','',true);

// UPDATED 2025/05/14
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A='+sAction+'&T='+arguments[2]+'&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')),'','','','','',function(){Widgets('s_'+sAction,Callback,arguments[2]);},function(){Widgets('f_'+sAction,Callback,arguments[2]);},function(){Application('busy',Callback,arguments[2]);},function(){Application('timeout',Callback,arguments[2]);},function(){Application('inactive',Callback,arguments[2]);});
// UPDATED 2025/06/28
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A='+sAction+'&T='+arguments[2]+'&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID')),'','','','','',function(){Widgets('s_'+sAction,Callback,arguments[2]);},function(){Widgets('f_'+sAction,Callback,arguments[2]);},function(){Application('busy',Callback,arguments[2]);},function(){Application('timeout',Callback,arguments[2]);},function(){Application('inactive',Callback,arguments[2]);});
			Ajax('Call',_oDashboard,_sUriProject+"code/Application.php",'A='+sAction+'&T='+arguments[2]+'&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID')),'',function(){Widgets('s_'+sAction,Callback,arguments[2]);},function(){Widgets('f_'+sAction,Callback,arguments[2]);});
			break;
		case "s_load":
			var WIDGETS = XML.getElementsByTagName(arguments[2].substr(0,arguments[2].length-1));
			var repo = 'http://www.cliquesoft.org/';
			var temp = '';

// UPDATED 2025/05/14
//			HTML =	"<ul id='ulWidgets' class='ulWidgets'><li class='liTab"+(arguments[2] == 'exts' ? ' liSel' : '')+"' onClick=\"adjTabs('ulWidgets','liTab','liSel',this,1); Widgets('load',null,'exts')\">Extensions</li><li class='liTab"+(arguments[2] == 'themes' ? ' liSel' : '')+"' onClick=\"adjTabs('ulWidgets','liTab','liSel',this,1); Widgets('load',null,'themes')\">Themes</li><li class='liTab"+(arguments[2] == 'icons' ? ' liSel' : '')+"' onClick=\"adjTabs('ulWidgets','liTab','liSel',this,1); Widgets('load',null,'icons')\">Icon Sets</li><li><img src='home/"+getCookie('sUsername')+"/imgs/close.png' class='closePopup' onClick=\"Widgets('close');\" title=\"Close this Window\" /></li></ul>" +
// UPDATED 2025/05/18
//			HTML =	"<ul id='ulWidgets' class='ulWidgets'><li class='liTab"+(arguments[2] == 'exts' ? ' liSel' : '')+"' onClick=\"adjTabs('ulWidgets','liTab','liSel',this,1); Widgets('load',null,'exts')\">Extensions</li><li class='liTab"+(arguments[2] == 'themes' ? ' liSel' : '')+"' onClick=\"adjTabs('ulWidgets','liTab','liSel',this,1); Widgets('load',null,'themes')\">Themes</li><li class='liTab"+(arguments[2] == 'icons' ? ' liSel' : '')+"' onClick=\"adjTabs('ulWidgets','liTab','liSel',this,1); Widgets('load',null,'icons')\">Icon Sets</li><li><img src='home/"+Cookie('Obtain','sUsername')+"/imgs/close.png' class='closePopup' onClick=\"Widgets('close');\" title=\"Close this Window\" /></li></ul>" +
			HTML =	"<ul id='ulWidgets' class='ulWidgets'><li class='liTab"+(arguments[2] == 'exts' ? ' liSel' : '')+"' onClick=\"Tabs('Select','ulWidgets',this,'liTab','liSel'); Widgets('load',null,'exts')\">Extensions</li><li class='liTab"+(arguments[2] == 'themes' ? ' liSel' : '')+"' onClick=\"Tabs('Select','ulWidgets',this,'liTab','liSel'); Widgets('load',null,'themes')\">Themes</li><li class='liTab"+(arguments[2] == 'icons' ? ' liSel' : '')+"' onClick=\"Tabs('Select','ulWidgets',this,'liTab','liSel'); Widgets('load',null,'icons')\">Icon Sets</li><li><img src='home/"+Cookie('Obtain','sUsername')+"/imgs/close.png' class='closePopup' onClick=\"Widgets('close');\" title=\"Close this Window\" /></li></ul>" +
				"<div class='divList'>" +
				"	<ul>";

			for (var i=0; i<WIDGETS.length; i++) {
				if (! parseInt(WIDGETS[i].getAttribute('installed')))
					{ HTML += "		   <li onClick=\"Widgets('info',null,'"+arguments[2]+"',&quot;"+WIDGETS[i].firstChild.data+"&quot;,'uninstalled');\"><img src='"+repo+WIDGETS[i].getAttribute('logo')+"' class='logo' /><ul><li class='bold'>"+WIDGETS[i].firstChild.data+"</li><li>"+WIDGETS[i].getAttribute('downloads')+" Downloads</li><li>"; }
				else
					{ HTML += "		   <li onClick=\"Widgets('info',null,'"+arguments[2]+"',&quot;"+WIDGETS[i].firstChild.data+"&quot;,'installed');\" class='disabled'><img src='"+repo+WIDGETS[i].getAttribute('logo')+"' class='logo' /><ul><li class='bold'>"+WIDGETS[i].firstChild.data+"</li><li>"+WIDGETS[i].getAttribute('downloads')+" Downloads</li><li>"; }

				// determine the correct rounded value for the proper star display
				if (WIDGETS[i].getAttribute('score') % 1 == 0.5) {
					temp = WIDGETS[i].getAttribute('score');
				} else {
					temp = (Math.round(WIDGETS[i].getAttribute('score')*10)/10);
					temp = Math.round(WIDGETS[i].getAttribute('score'));		// if we don't have a .5 decimal, then round to nearest integer
				}

				for (var j=0; j<parseInt(temp); j++)				// calculate full stars
// UPDATED 2025/05/14
//					{ HTML += "<img src='"+repo+"home/"+getCookie('sUsername')+"/imgs/star_full.png' class='star' />"; }
					{ HTML += "<img src='"+repo+"home/"+Cookie('Obtain','sUsername')+"/imgs/star_full.png' class='star' />"; }
				if (temp % 1 == 0.5)						// calculate half stars
// UPDATED 2025/05/14
//					{ HTML += "<img src='"+repo+"home/"+getCookie('sUsername')+"/imgs/star_half.png' class='star' />"; }
					{ HTML += "<img src='"+repo+"home/"+Cookie('Obtain','sUsername')+"/imgs/star_half.png' class='star' />"; }
				for (j=Math.round(temp); j<5; j++)				// calculate null stars
// UPDATED 2025/05/14
//					{ HTML += "<img src='"+repo+"home/"+getCookie('sUsername')+"/imgs/star_null.png' class='star' />"; }
					{ HTML += "<img src='"+repo+"home/"+Cookie('Obtain','sUsername')+"/imgs/star_null.png' class='star' />"; }

				HTML += "&nbsp;</li></ul>";
			}

			HTML += "	</ul>" +
				"</div>" +
				"<div id='divItem' class='divItem'></div>";

			document.getElementById('divPopup').innerHTML = HTML;
			if (WIDGETS.length == 0) { alert("There are currently no available "+(arguments[2] == 'exts' ? 'extensions' : arguments[2])+"."); }
			break;
		case "f_load":
			Project('Popup','hide');				// remove the Widgets popup
			Project('Popup','fail');				// show the error message

//			Widgets('close');					// remove the screen if a connection fails since it won't have any controls to close the popup
// UPDATED 2025/05/14
//			HTML += "<ul id='ulWidgets' class='ulWidgets'><li class='liTab"+(arguments[2] == 'exts' ? ' liSel' : '')+"' onClick=\"adjTabs('ulWidgets','liTab','liSel',this,1); Widgets('load',null,'exts')\">Extensions</li><li class='liTab"+(arguments[2] == 'themes' ? ' liSel' : '')+"' onClick=\"adjTabs('ulWidgets','liTab','liSel',this,1); Widgets('load',null,'themes')\">Themes</li><li class='liTab"+(arguments[2] == 'icons' ? ' liSel' : '')+"' onClick=\"adjTabs('ulWidgets','liTab','liSel',this,1); Widgets('load',null,'icons')\">Icon Sets</li><li><img src='home/"+getCookie('sUsername')+"/imgs/close.png' class='closePopup' onClick=\"Widgets('close')\" title=\"Close this Window\" /></li></ul>" +
// UPDATED 2025/05/18
//			HTML += "<ul id='ulWidgets' class='ulWidgets'><li class='liTab"+(arguments[2] == 'exts' ? ' liSel' : '')+"' onClick=\"adjTabs('ulWidgets','liTab','liSel',this,1); Widgets('load',null,'exts')\">Extensions</li><li class='liTab"+(arguments[2] == 'themes' ? ' liSel' : '')+"' onClick=\"adjTabs('ulWidgets','liTab','liSel',this,1); Widgets('load',null,'themes')\">Themes</li><li class='liTab"+(arguments[2] == 'icons' ? ' liSel' : '')+"' onClick=\"adjTabs('ulWidgets','liTab','liSel',this,1); Widgets('load',null,'icons')\">Icon Sets</li><li><img src='home/"+Cookie('Obtain','sUsername')+"/imgs/close.png' class='closePopup' onClick=\"Widgets('close')\" title=\"Close this Window\" /></li></ul>" +
// REMOVED 2025/10/02
//			HTML += "<ul id='ulWidgets' class='ulWidgets'><li class='liTab"+(arguments[2] == 'exts' ? ' liSel' : '')+"' onClick=\"Tabs('Select','ulWidgets',this,'liTab','liSel'); Widgets('load',null,'exts')\">Extensions</li><li class='liTab"+(arguments[2] == 'themes' ? ' liSel' : '')+"' onClick=\"Tabs('Select','ulWidgets',this,'liTab','liSel'); Widgets('load',null,'themes')\">Themes</li><li class='liTab"+(arguments[2] == 'icons' ? ' liSel' : '')+"' onClick=\"Tabs('Select','ulWidgets',this,'liTab','liSel'); Widgets('load',null,'icons')\">Icon Sets</li><li><img src='home/"+Cookie('Obtain','sUsername')+"/imgs/close.png' class='closePopup' onClick=\"Widgets('close')\" title=\"Close this Window\" /></li></ul>" +
//				"<div class='divList'></div>" +
//				"<div id='divItem' class='divItem'></div>";
//			document.getElementById('divPopup').innerHTML = HTML;
			break;




		// Show Widget Details
		case "details":			// shows the details of the selected widget				EXAMPLES
		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
		   // arguments[2]		[string] the type of the widget: exts, themes, icons			"exts"
		   // arguments[3]		[string] the name of the selected widget				"Work Orders"
		   // arguments[4]		[string] the install status of the widget: uninstalled, installed	"uninstalled"
// UPDATED 2025/05/14
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A=load&T='+sAction+'&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID'))+'&sType='+arguments[2]+'&sWidget='+escape(arguments[3]),'','','','','',function(){Widgets('s_adjust',null,arguments[2],arguments[3],arguments[4]);},function(){Application('fail',null,arguments[2],arguments[3],arguments[4]);},function(){Application('busy',null,arguments[2],arguments[3],arguments[4]);},function(){Application('timeout',null,arguments[2],arguments[3],arguments[4]);},function(){Application('inactive',null,arguments[2],arguments[3],arguments[4]);});
// UPDATED 2025/06/28
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A=load&T='+sAction+'&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&sType='+arguments[2]+'&sWidget='+escape(arguments[3]),'','','','','',function(){Widgets('s_adjust',null,arguments[2],arguments[3],arguments[4]);},function(){Application('fail',null,arguments[2],arguments[3],arguments[4]);},function(){Application('busy',null,arguments[2],arguments[3],arguments[4]);},function(){Application('timeout',null,arguments[2],arguments[3],arguments[4]);},function(){Application('inactive',null,arguments[2],arguments[3],arguments[4]);});
			Ajax('Call',_oDashboard,_sUriProject+"code/Application.php",'A=load&T='+sAction+'&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&sType='+arguments[2]+'&sWidget='+escape(arguments[3]),'',function(){Widgets('s_adjust',null,arguments[2],arguments[3],arguments[4]);});
			break;
		case "s_details":
// LEFT OFF - update the below variable names to be more descriptive (e.g. m > MONTH)
//	show any returned messages (for failure only)
			var temp;
			var repo = 'http://www.cliquesoft.org/';
			var today = new Date();
			var m = new Array('Jan', 'Feb', 'March', 'April', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec');
			var a = XML.getElementsByTagName("item");
			var p = XML.getElementsByTagName("pic");
			var r = XML.getElementsByTagName("review");
			var M = XML.getElementsByTagName("mine");

			// Store permanent values in hidden objects and/or cookies

			// Make GUI adjustments
			HTML = "<ul class='pics fleft'>";
			for (var i=0; i<p.length; i++)
				{ HTML += "	<li><img src='"+repo+p[i].getAttribute('name')+"' onClick=\"window.open('"+repo+p[i].getAttribute('name')+"','_blank')\" />"; }
			HTML += "</ul>";

			HTML += "<ul class='details fleft'>" +
				"	<li><label><h2>"+a[0].getAttribute('name')+"</h2></label>&nbsp;"+
				"	<li><label>"+a[0].getAttribute('author')+"</label>$"+a[0].getAttribute('price') +
				"	<li><label><a href='"+a[0].getAttribute('uri')+"' target='_new'>Website</a></label>"+a[0].getAttribute('rating').toUpperCase() +
				"	<li><label>"+a[0].getAttribute('license')+"</label>"+m[a[0].getAttribute('updated').substr(5,2)-1]+" "+a[0].getAttribute('updated').substr(8,2)+" "+a[0].getAttribute('updated').substr(0,4);
			if (arguments[4] == 'uninstalled')
				{ HTML += "	<li class='dotted'><input type='button' name='btnDownload' id='btnDownload' value='Install' class='button TTButton fright' onclick=\"Widgets('adjust',null,'"+arguments[2]+"',&quot;"+arguments[3]+"&quot;,'install')\" /><label>"+a[0].getAttribute('downloads')+" downloads</label>"; }
			else
				{ HTML += "	<li class='dotted'><input type='button' name='btnDownload' id='btnDownload' value='Uninstall' class='button TTButton fright' onclick=\"Widgets('adjust',null,'"+arguments[2]+"',&quot;"+arguments[3]+"&quot;,'uninstall')\" /><label>"+a[0].getAttribute('downloads')+" downloads</label>"; }
			HTML += "	<li style='height: 45px;'><label>";
// LEFT OFF - the download URI should be setup in the fashion window.open('http://repo.cliquesoft.org/vanilla/all/webBooks/_icons/Applesque/stable.soft')

			// determine the correct rounded value for the proper star display
			if (a[0].getAttribute('score') % 1 == 0.5) {
				temp = a[0].getAttribute('score');
			} else {
				temp = (Math.round(a[0].getAttribute('score')*10)/10);
				temp = Math.round(a[0].getAttribute('score'));		// if we don't have a .5 decimal, then round to nearest integer
			}

			for (var j=0; j<parseInt(temp); j++)				// calculate full stars
// UPDATED 2025/05/14
//				{ HTML += "<img src='"+repo+"home/"+getCookie('sUsername')+"/imgs/star_full.png' class='star' />"; }
				{ HTML += "<img src='"+repo+"home/"+Cookie('Obtain','sUsername')+"/imgs/star_full.png' class='star' />"; }
			if (temp % 1 == 0.5)						// calculate half stars
// UPDATED 2025/05/14
//				{ HTML += "<img src='"+repo+"home/"+getCookie('sUsername')+"/imgs/star_half.png' class='star' />"; }
				{ HTML += "<img src='"+repo+"home/"+Cookie('Obtain','sUsername')+"/imgs/star_half.png' class='star' />"; }
			for (j=Math.round(temp); j<5; j++)				// calculate null stars
// UPDATED 2025/05/14
//				{ HTML += "<img src='"+repo+"home/"+getCookie('sUsername')+"/imgs/star_null.png' class='star' />"; }
				{ HTML += "<img src='"+repo+"home/"+Cookie('Obtain','sUsername')+"/imgs/star_null.png' class='star' />"; }

			HTML += "<br />"+a[0].getAttribute('votes')+" votes</label>" +
				"	<li class='dotted desc'>" + a[0].firstChild.data.replace(/\n/g, '<br />');

			// add *your* review
			// NOTE: this was removed from this project

			// add all the reviews
			for (i=0; i<r.length; i++) {
				HTML += "	<li class='dotted'><label>"+r[0].getAttribute('author')+"</label>" + m[r[i].getAttribute('posted').substr(5,2)-1]+" "+r[i].getAttribute('posted').substr(8,2)+" "+r[i].getAttribute('posted').substr(0,4) +
					"	<li>";

				// determine the correct rounded value for the proper star display
				if (r[i].getAttribute('score') % 1 == 0.5) {
					temp = r[i].getAttribute('score');
				} else {
					temp = (Math.round(r[i].getAttribute('score')*10)/10);
					temp = Math.round(r[i].getAttribute('score'));	// if we don't have a .5 decimal, then round to nearest integer
				}

				for (var j=0; j<parseInt(temp); j++)			// calculate full stars
// UPDATED 2025/05/14
//					{ HTML += "<img src='"+repo+"home/"+getCookie('sUsername')+"/imgs/star_full.png' class='star' />"; }
					{ HTML += "<img src='"+repo+"home/"+Cookie('Obtain','sUsername')+"/imgs/star_full.png' class='star' />"; }
				if (temp % 1 == 0.5)					// calculate half stars
// UPDATED 2025/05/14
//					{ HTML += "<img src='"+repo+"home/"+getCookie('sUsername')+"/imgs/star_half.png' class='star' />"; }
					{ HTML += "<img src='"+repo+"home/"+Cookie('Obtain','sUsername')+"/imgs/star_half.png' class='star' />"; }
				for (j=Math.round(temp); j<5; j++)			// calculate null stars
// UPDATED 2025/05/14
//					{ HTML += "<img src='"+repo+"home/"+getCookie('sUsername')+"/imgs/star_null.png' class='star' />"; }
					{ HTML += "<img src='"+repo+"home/"+Cookie('Obtain','sUsername')+"/imgs/star_null.png' class='star' />"; }

				HTML += "&nbsp;" +
				"	<li class='justify'>" + r[i].firstChild.data;
			}
			HTML += "</ul>";

			document.getElementById('divItem').innerHTML = HTML;

			// Fill any form objects
// UPDATED 2025/07/09
//			if (M.length > 0) { selListbox('lstScore'+a[0].getAttribute('id'),M[0].getAttribute("score")); }
			if (M.length > 0) { Listbox('SelectOption','lstScore'+a[0].getAttribute('id'),M[0].getAttribute("score")); }

			break;




		// (Un)installs the Widget
		case "adjust":			// (un)installs the selected widget					EXAMPLES
		   // Callback			[string/function] a callback function for a successful operation	function(){alert 'hello world';}
		   // arguments[2]		[string] the type of extension to list: exts, themes, icons		"exts"
		   // arguments[3]		[string] the name of the selected widget				"Work Orders"
		   // arguments[4]		[string] the install status of the widget: uninstall, install		"install"
			var retainDB=0;		// NOTE: this is actually only applicable to uninstallations of extensions

// VER2 - make adjustments without having to refresh the application (just re-run init_application()?)
			if (!confirm("This will refresh webBooks causing any unsaved information to be lost, do you want to continue?")) {return 0;}
			if (arguments[2] == 'exts' && arguments[4] == 'uninstall')
				{ if (confirm("Would like to retain the module data? Click the 'Ok' button to save, or 'Cancel' to delete.")) {retainDB=1;} }

// UPDATED 2025/05/14
//			ajax(_oDashboard,4,'get',_sUriProject+"code/Application.php",'A='+sAction+'&T=widget&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID'))+'&sType='+arguments[2]+'&sWidget='+escape(arguments[3])+'&sStatus='+arguments[4]+'&retainDB='+retainDB,'','','','','',function(){Widgets('s_adjust',null,arguments[2],arguments[3],arguments[4]);},function(){Application('fail',null,arguments[2],arguments[3],arguments[4]);},function(){Application('busy',null,arguments[2],arguments[3],arguments[4]);},function(){Application('timeout',null,arguments[2],arguments[3],arguments[4]);},function(){Application('inactive',null,arguments[2],arguments[3],arguments[4]);});
// UPDATED 2025/06/28
//			ajax(_oDashboard,4,'get',_sUriProject+"code/Application.php",'A='+sAction+'&T=widget&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&sType='+arguments[2]+'&sWidget='+escape(arguments[3])+'&sStatus='+arguments[4]+'&retainDB='+retainDB,'','','','','',function(){Widgets('s_adjust',null,arguments[2],arguments[3],arguments[4]);},function(){Application('fail',null,arguments[2],arguments[3],arguments[4]);},function(){Application('busy',null,arguments[2],arguments[3],arguments[4]);},function(){Application('timeout',null,arguments[2],arguments[3],arguments[4]);},function(){Application('inactive',null,arguments[2],arguments[3],arguments[4]);});
			Ajax('Call',_oDashboard,_sUriProject+"code/Application.php",'A='+sAction+'&T=widget&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&sType='+arguments[2]+'&sWidget='+escape(arguments[3])+'&sStatus='+arguments[4]+'&retainDB='+retainDB,'',function(){Widgets('s_adjust',null,arguments[2],arguments[3],arguments[4]);});
			break;
		case "s_adjust":
			location.reload();			// refresh the screen so the new module additions take place
			break;
	}
}










// -- Dashboard API --

function Dashboard(sAction,Callback) {
// sAction	the available actions that this function can process: load (module), toggle (interface), status (update), availability (update), employees (listing), clock (keeping)
// Callback	the callback to execute upon success; value can be a string or function()
	var HTML = "";
	
	switch(sAction) {
		// --- BUILT-IN ACTIONS ---	   (for base callback functionality of ajax)
		// these will refer to the Application API above


		// --- CUSTOM ACTIONS ---


		// Load the Module
		case "initialize":			// initializes the module UI and sets account info
// UPDATED 2025/05/14
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A='+sAction+'&T=UI&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')),'','','','','',function(){Dashboard('s_'+sAction,Callback);},function(){Application('fail',Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback);});
// UPDATED 2025/06/28
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A='+sAction+'&T=UI&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID')),'','','','','',function(){Dashboard('s_'+sAction,Callback);},function(){Application('fail',Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback);});
			Ajax('Call',_oDashboard,_sUriProject+"code/Application.php",'!'+sAction+'!,>UI<,(sUsername),(sSessionID)','',function(){Dashboard('s_'+sAction,Callback);});
			break;
		case "s_initialize":
			var DASHBOARD = XML.getElementsByTagName("dashboard")[0];

			// Set the proper cookies
// UPDATED 2025/05/14
//			setCookie('bHostedService', DASHBOARD.getAttribute('bHostedService'));
//			setCookie('sInterface', DASHBOARD.getAttribute('sInterface'));
			Cookie('Create','bHostedService', DASHBOARD.getAttribute('bHostedService'));
			Cookie('Create','sInterface', DASHBOARD.getAttribute('sInterface'));

			// Setup the 'Social' Icon
			if (DASHBOARD.getAttribute('sUriSocial') != '') {					// if there is a social URI, then...
// UPDATED 2025/05/14
//				document.getElementById('imgSocial_Dashboard').src = "home/"+getCookie('sUsername')+"/imgs/webbooks.social.png";	// enable the icon
				document.getElementById('imgSocial_Dashboard').src = "home/"+Cookie('Obtain','sUsername')+"/imgs/webbooks.social.png";	// enable the icon
				_sUriSocial = DASHBOARD.getAttribute('sUriSocial');				// set the URI to the social interface
				document.getElementById('imgSocial_Dashboard').disabled = false;		// enable the social icon
			}

			// Set the Users Information
			USER = XML.getElementsByTagName("user").item(0);
			document.getElementById('liUserAccount').innerHTML = USER.getAttribute('sName');
// UPDATED 2025/07/09
//			selListbox('sStatus_Dashboard',USER.getAttribute('sStatus'));				// set the selected status for the status listbox
//			selListbox('sAvailability_Dashboard',USER.getAttribute('sAvailability'));		// set the selected availability for the availability listbox
			Listbox('SelectOption','sStatus_Dashboard',USER.getAttribute('sStatus'));		// set the selected status for the status listbox
			Listbox('SelectOption','sAvailability_Dashboard',USER.getAttribute('sAvailability'));	// set the selected availability for the availability listbox

			// Populate the 'Modules' Tab
// LEFT OFF - call reloadDashboard(strAction); from system_configuration.js (and move that function in Dashboard())
	// UPDATED 2025/03/05 - moved to its own function
	//		var MODULE, GROUP = XML.getElementsByTagName("group");
	//		for (var I=0; I<GROUP.length; I++) {							// Add the Grouped Modules
	//			// add each group and associated modules
	//			HTML =	"<li><div class='Group' style='background: url(home/"+getCookie('sUsername')+"/imgs/"+GROUP[I].getAttribute('sIcon')+") no-repeat;'>" +
	//		   	 	 "<ul class='Modules'>";

	//			MODULE = GROUP[I].getElementsByTagName("module");
	//			for (var J=0; J<MODULE.length; J++)
	//				{ HTML += "<li><a href='#' onClick=\"Module('focus',null,'"+MODULE[J].getAttribute('sName')+"');\" title='"+MODULE[J].getAttribute('sName')+"'><img src='home/"+getCookie('sUsername')+"/imgs/"+MODULE[J].getAttribute('sIcon')+"' alt='"+MODULE[J].getAttribute('sName')+"' /></a>"; }

	//			HTML += "</ul>" +
	//				"<label>"+GROUP[I].getAttribute('sName')+"</label>" +
	//				"</div>";

// LEFT OFF - possible to just: document.getElementById('ulDashboard').innerHTML += HTML;	????? (reducing jQuery usage)
	//			$("#ulDashboard").append(HTML);
	//		}
	//		$('.Group').mobilyblocks({								// Define some settings of the Groups
	//			//trigger: 'hover',
	//			//direction: 'counter',
	//			//duration: 500,
	//			//zIndex: 50,
	//			widthMultiplier: 0.7
	//		});
			Dashboard('load');

			// Populate the 'Employees' Tab
// REMOVED 2025/02/21
//			Dashboard('employees',Callback);	do NOT show this unless that tab is clicked (since we are refreshing the list while on that tab)

// VER2 - implement the "reporting scroller"
//			$("#tS2").thumbnailScroller({								// for the "information scroller" on the dashboard (e.g. frequently used modules, alerts, etc)
//			   scrollerType:"hoverPrecise",
//			   scrollerOrientation:"horizontal",
//			   scrollSpeed:2,
//			   scrollEasing:"easeOutCirc",
//			   scrollEasingAmount:600,
//			   acceleration:4,
//			   scrollSpeed:800,
//			   noScrollCenterSpace:10,
//			   autoScrolling:0,
//			   autoScrollingSpeed:2000,
//			   autoScrollingEasing:"easeInOutQuad",
//			   autoScrollingDelay:500
//			});
			break;




		// (Re)Load the Dashboard
		case "load":			// loads the dashboard layout
//			ajax(reqIO,4,'post',gbl_uriProject+"code/system_configuration.php",'action=reload&target=dashboard&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value),'','','','','',"reloadDashboard('succ');","reloadDashboard('fail');","reloadDashboard('busy');","reloadDashboard('timeout');","reloadDashboard('inactive');");
// UPDATED 2025/05/14
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A='+sAction+'&T=dashboard&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')),'','','','','',function(){Dashboard('s_'+sAction,Callback);},function(){Application('fail',Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback);});
// UPDATED 2025/06/28
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A='+sAction+'&T=dashboard&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID')),'','','','','',function(){Dashboard('s_'+sAction,Callback);},function(){Application('fail',Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback);});
			Ajax('Call',_oDashboard,_sUriProject+"code/Application.php",'!'+sAction+'!,>dashboard<,(sUsername),(sSessionID)','',function(){Dashboard('s_'+sAction,Callback);});
			break;
		case "s_load":
			var m, g = XML.getElementsByTagName("group");

			document.getElementById('ulDashboard').innerHTML = '';				// erase all the existing groups

			for (var I=0; I<g.length; I++) {
				// add each group and associated modules
// UPDATED 2025/05/14
//				HTML =	"<li><div class='Group' style='background: url(home/"+getCookie('sUsername')+"/imgs/"+g[I].getAttribute('sIcon')+") no-repeat;'>" +
				HTML =	"<li><div class='Group' style='background: url(home/"+Cookie('Obtain','sUsername')+"/imgs/"+g[I].getAttribute('sIcon')+") no-repeat;'>" +
			   	 	 "<ul class='Modules'>";

				m = g[I].getElementsByTagName("module");
				for (var j=0; j<m.length; j++)
// UPDATED 2025/05/14
//					{ HTML += "<li><a href='#' onClick=\"Module('focus',null,'"+m[j].getAttribute('sName')+"');\" title='"+m[j].getAttribute('sName')+"'><img src='home/"+getCookie('sUsername')+"/imgs/"+m[j].getAttribute('sIcon')+"' alt='"+m[j].getAttribute('sName')+"' /></a>"; }
					{ HTML += "<li><a href='#' onClick=\"Module('focus',null,'"+m[j].getAttribute('sName')+"');\" title='"+m[j].getAttribute('sName')+"'><img src='home/"+Cookie('Obtain','sUsername')+"/imgs/"+m[j].getAttribute('sIcon')+"' alt='"+m[j].getAttribute('sName')+"' /></a>"; }

				HTML += "</ul>" +
					"<label>"+g[I].getAttribute('sName')+"</label>";

// LEFT OFF - couldn't get the pure JS to work correctly below, so substituted jQuery
				//var li = document.createElement('div');				// create a new <li> adding an icon to the opened modules section in the footer
				//li.className = 'liTab liSel';						// assign it the appropriate class
				//li.addEventListener('click',function(){"Module('focus',null,'"+strName+"'); adjTabs('ulOpened','liTab','liSel',this);"},false);			// http://stackoverflow.com/questions/1019078/how-to-set-onclick-attribute-with-value-containing-function-in-ie8
				//li.innerHTML = "<img title='"+strName+"' src='data/${UN}/_theme/images/webbooks."+strName.toLowerCase().replace(/ /g,'_')+".jpg' class='Screens' />";
				//document.getElementById('ulOpened').appendChild(li);			// add the <li> to the <ul>
				$("#ulDashboard").append(HTML);
			}

			$('.Group').mobilyblocks({
				//trigger: 'hover',
				//direction: 'counter',
				//duration: 500,
				//zIndex: 50,
				widthMultiplier: 0.7
			});
			break;




		// Toggles the UI language
		case "language":			// switches which language is being used in the UI
			Project('Popup','fail',"Changing languages has not yet been implemented.");
			return false;
			break;




// DEPRECATED 2025/10/02 - this is no longer applicable since the social icon is now in the taskbar
		// Toggles the Interface
		case "toggle":			// switches focus between the Dashboard and Social interface
			// if the social is currently displayed, then show the dashboard
			if (document.getElementById('divContainer_Social').style.display == 'block') {		// if we need to show the dashboard, then...
				document.getElementById('divContainer_Social').style.display = 'none';		//    hide the social interface

				document.getElementById('divContainer_Dashboard').style.display = 'block';	//    show the dashboard
				document.getElementById('ulTaskbar').style.visibility = 'visible';		//    show the "taskbar"
														//    update the associated graphic for the toggle ---v
				if (arguments.length == 2)				// if the user has not yet clicked this icon -OR- there was success in embedding the email provider in the iframe, then...
// UPDATED 2025/05/14
//					{ document.getElementById('liSocialToggle').innerHTML = "<img id='imgSocial_Dashboard' src='home/"+getCookie('sUsername')+"/imgs/webbooks.social.png' onClick=\"if(this.disabled==false){Dashboard('toggle');}\" title=\"Switch to the social interface of webBooks\" />"; }
					{ document.getElementById('liSocialToggle').innerHTML = "<img id='imgSocial_Dashboard' src='home/"+Cookie('Obtain','sUsername')+"/imgs/webbooks.social.png' onClick=\"if(this.disabled==false){Dashboard('toggle');}\" title=\"Switch to the social interface of webBooks\" />"; }
				else							// otherwise there was a problem loading the provider, so...
// UPDATED 2025/05/14
//					{ document.getElementById('liSocialToggle').innerHTML = "<img id='imgSocial_Dashboard' src='home/"+getCookie('sUsername')+"/imgs/webbooks.social_disabled.png' title=\"Switch to the social interface of webBooks\" />"; }
					{ document.getElementById('liSocialToggle').innerHTML = "<img id='imgSocial_Dashboard' src='home/"+Cookie('Obtain','sUsername')+"/imgs/webbooks.social_disabled.png' title=\"Switch to the social interface of webBooks\" />"; }

				Dashboard('employees');					// update the Employees tab info
				return true;						// no reason to process anything below if we're going to the social interface
			}

// VER2 - make Social the 2nd-non-closable screen on the taskbar next to Dashboard instead of the small graphic in the upper-lefthand side of the screen
			// otherwise switch to the social interface
			document.getElementById('divContainer_Dashboard').style.display = 'none';
			document.getElementById('ulTaskbar').style.visibility = 'hidden';

			document.getElementById('divContainer_Social').style.display = 'block';
// UPDATED 2025/05/14
//			document.getElementById('liSocialToggle').innerHTML = "<img id='imgDashboard_Social' src='home/"+getCookie('sUsername')+"/imgs/webbooks.dashboard.png' onClick=\"Dashboard('toggle');\" title=\"Switch to the dashboard interface of webBooks\" />";
			document.getElementById('liSocialToggle').innerHTML = "<img id='imgDashboard_Social' src='home/"+Cookie('Obtain','sUsername')+"/imgs/webbooks.dashboard.png' onClick=\"Dashboard('toggle');\" title=\"Switch to the dashboard interface of webBooks\" />";

// LEFT OFF - check that the iframe doesn't already have contents (e.g. we already loaded the social interface in the iframe) to prevent un-neccessary reloads of the social interface (erasing opened items, unsaved settings/data, etc)
			document.getElementById('sUriSocial').innerHTML = "<img src='home/${UN}/imgs/loading.gif' style='position: absolute; left: 50%; top: 50%; margin-left: -64px; margin-top: -64px; width: 128px; height: 128px;' />";
// UPDATED 2025/05/14
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A='+sAction+'&T=interface&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID'))+'&_sUriSocial='+escape(_sUriSocial),'','','','','',function(){Dashboard('s_'+sAction,Callback);},function(){Application('fail',Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback);});
// UPDATED 2026/06/28
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A='+sAction+'&T=interface&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&_sUriSocial='+escape(_sUriSocial),'','','','','',function(){Dashboard('s_'+sAction,Callback);},function(){Application('fail',Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback);});
			Ajax('Call',_oDashboard,_sUriProject+"code/Application.php",'!'+sAction+'!,>interface<,(sUsername),(sSessionID),{_sUriSocial}','',function(){Dashboard('s_'+sAction,Callback);});
			break;
		case "s_toggle":
// UPDATED 2025/05/14
//			document.getElementById('liSocialToggle').innerHTML = "<img id='imgDashboard_Social' src='home/"+getCookie('sUsername')+"/imgs/webbooks.dashboard.png' onClick=\"Dashboard('interface');\" title=\"Switch to the dashboard of webBooks\" />";
			document.getElementById('liSocialToggle').innerHTML = "<img id='imgDashboard_Social' src='home/"+Cookie('Obtain','sUsername')+"/imgs/webbooks.dashboard.png' onClick=\"Dashboard('interface');\" title=\"Switch to the dashboard of webBooks\" />";
			document.getElementById('sUriSocial').src = DATA['sUriSocial'];

			// reset the (form) objects
			delete DATA['sUriSocial'];		// to prevent contamination between failed calls
			break;
		case "f_toggle":
			Project(_sProjectUI,'fail');
			break;




		// Updates the Users 'Status'
		case "status":			// adjusts the employees status (e.g. clocked in, clocked out, etc)
			var lb = document.getElementById('sStatus_Dashboard');
			if (lb.options[lb.selectedIndex].value == 'logout') {			// no reason to update the employees status if they have just logged out
// UPDATED 2025/05/14
//				Session('Logout', function(){delCookie('bHostedService','/'); delCookie('sInterface','/'); location.href=DATA['sUriProject'];});
				Session('Logout', function(){Cookie('Delete','bHostedService'); Cookie('Delete','sInterface'); location.href=location.href.substring(0,location.href.lastIndexOf('/')+1);});
				return true;
			}

			if (lb.selectedIndex != 0 && lb.selectedIndex != 3)			// if the user has selected any other status than 'Clocked In' or 'Logout', then allow them to adjust their availability
				{ document.getElementById('sAvailability_Dashboard').selectedIndex = 1; }		// update the listbox value

// UPDATED 2025/05/14
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A=update&T='+sAction+'&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID'))+'&sStatus='+lb.options[lb.selectedIndex].value,'','','sStatus_Dashboard','','',function(){Dashboard('s_'+sAction,Callback);},function(){Dashboard('f_'+sAction,Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback,'notice');});
// UPDATED 2025/07/09
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A=update&T='+sAction+'&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&sStatus='+lb.options[lb.selectedIndex].value,'','','sStatus_Dashboard','','',function(){Dashboard('s_'+sAction,Callback);},function(){Dashboard('f_'+sAction,Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback,'notice');});
// UPDATED 2025/10/01
//			Ajax('Call',_oDashboard,_sUriProject+"code/Application.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&sStatus='+lb.options[lb.selectedIndex].value,'sStatus_Dashboard',function(){Dashboard('s_'+sAction,Callback);});
			Ajax('Call',_oDashboard,_sUriProject+"code/Application.php",'!update!,>'+sAction+'<,(sUsername),(sSessionID),&sStatus='+lb.options[lb.selectedIndex].value,'sStatus_Dashboard',function(){Dashboard('s_'+sAction,Callback);});
			break;
		case "s_status":
			Project(_sProjectUI,'succ');

// UPDATED 2025/07/09
//			selListbox('sStatus_Dashboard',DATA['sStatus']);			// set the selected status for the status listbox
			Listbox('SelectOption','sStatus_Dashboard',DATA['sStatus']);		// set the selected status for the status listbox
			Dashboard('employees');							// now refresh the listing so the values are correct

			// reset the (form) objects
			delete DATA['sStatus'];			// to prevent contamination between failed calls
			break;
		case "f_status":
			Project(_sProjectUI,'fail');

// UPDATED 2025/07/09
//			selListbox('sStatus_Dashboard',DATA['sStatus']);			// if the request failed, select the prior value (as returned from the server) for the status
			Listbox('SelectOption','sStatus_Dashboard',DATA['sStatus']);		// if the request failed, select the prior value (as returned from the server) for the status

			// reset the (form) objects
			delete DATA['sStatus'];			// to prevent contamination between failed calls
			break;





		// Updates the Users 'Availability'
		case "availability":		// adjusts the employees availability (e.g. available, unavailable, on a break, etc)
			var lb = document.getElementById('sAvailability_Dashboard');

			// if anything other than 'Clocked In' is selected for the status, then the only value this listbox should display is 'unavailable'
			if (document.getElementById('sStatus_Dashboard').selectedIndex != 0) {
				lb.selectedIndex = 1;						// return the selection back to 'unavailable'
				alert("You can't change your availability if you are not clocked in.");
				return false;							// exit this function so no changes are made
			}

// UPDATED 2025/05/14
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A=update&T='+sAction+'&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID'))+'&sAvailability='+lb.options[lb.selectedIndex].value,'','','sAvailability_Dashboard','','',function(){Dashboard('s_'+sAction,Callback);},function(){Dashboard('f_'+sAction,Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback,'notice');});
// UPDATED 2025/07/09
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A=update&T='+sAction+'&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID'))+'&sAvailability='+lb.options[lb.selectedIndex].value,'','','sAvailability_Dashboard','','',function(){Dashboard('s_'+sAction,Callback);},function(){Dashboard('f_'+sAction,Callback);},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback,'notice');});
// UPDATED 2025/10/01
//			Ajax('Call',_oDashboard,_sUriProject+"code/Application.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID),&sAvailability='+lb.options[lb.selectedIndex].value,'sAvailability_Dashboard',function(){Dashboard('s_'+sAction,Callback);});
			Ajax('Call',_oDashboard,_sUriProject+"code/Application.php",'!update!,>'+sAction+'<,(sUsername),(sSessionID),&sAvailability='+lb.options[lb.selectedIndex].value,'sAvailability_Dashboard',function(){Dashboard('s_'+sAction,Callback);});
			break;
		case "s_availability":
			Project(_sProjectUI,'succ');

// UPDATED 2025/07/09
//			selListbox('sAvailability_Dashboard',DATA['sAvailability']);		// set the selected availability for the availability listbox
			Listbox('SelectOption','sAvailability_Dashboard',DATA['sAvailability']);   // set the selected availability for the availability listbox
			Dashboard('employees');							// now refresh the listing so the values are correct

			// reset the (form) objects
			delete DATA['sAvailability'];		// to prevent contamination between failed calls
			break;
		case "f_availability":
			Project(_sProjectUI,'fail');

// UPDATED 2025/07/09
//			selListbox('sAvailability_Dashboard',DATA['sAvailability']);		// if the request failed, select the prior value (as returned from the server) for the status
			Listbox('SelectOption','sAvailability_Dashboard',DATA['sAvailability']);   // set the selected availability for the availability listbox

			// reset the (form) objects
			delete DATA['sAvailability'];		// to prevent contamination between failed calls
			break;




		// Updates the Employees List
		case "employees":		// updates the employees tab information (e.g. each employees status and the users assigned work)
// UPDATED 2025/05/14
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A=update&T='+sAction+'&sUsername='+getCookie('sUsername')+'&sSessionID='+escape(getCookie('sSessionID')),'','','','','',function(){Dashboard('s_'+sAction,Callback);},function(){Application('fail',Callback,'notice');},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback,'notice');});
// UPDATED 2025/07/09
//			ajax(_oDashboard,4,'post',_sUriProject+"code/Application.php",'A=update&T='+sAction+'&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID')),'','','','','',function(){Dashboard('s_'+sAction,Callback);},function(){Application('fail',Callback,'notice');},function(){Application('busy',Callback);},function(){Application('timeout',Callback);},function(){Application('inactive',Callback,'notice');});
// UPDATED 2025/10/01
//			Ajax('Call',_oDashboard,_sUriProject+"code/Application.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID)','sAvailability_Dashboard',function(){Dashboard('s_'+sAction,Callback);});
			Ajax('Call',_oDashboard,_sUriProject+"code/Application.php",'!update!,>'+sAction+'<,(sUsername),(sSessionID)','sAvailability_Dashboard',function(){Dashboard('s_'+sAction,Callback);});
			break;
		case "s_employees":
			var EMPLOYEE = XML.getElementsByTagName("employee");
			var TABLE = document.getElementById('tblEmployees_Dashboard');				// create the 'Employee Status' table/list

			// clear any prior contents for refreshed data (by setting the contents to the table header)
			TABLE.innerHTML = "<tr><th class='employee'>Employee</th><th class='status'>Status</th><th class='avail'>Avail</th></tr>";

			for (I=0; I<EMPLOYEE.length; I++) {							// cycle each returned employee to display their info
				var ROW = TABLE.insertRow(-1);							//   insert a row in the last position

				// Add Cells in the Row
				var CELL0 = ROW.insertCell(0);
				var CELL1 = ROW.insertCell(1);
				var CELL2 = ROW.insertCell(2);

				// Create Text For Each Cell
				var NODE0 = document.createTextNode(EMPLOYEE[I].getAttribute("sName"));
				var NODE1 = document.createTextNode(EMPLOYEE[I].getAttribute("sStatus"));
				var NODE2 = document.createTextNode(EMPLOYEE[I].getAttribute("sAvailability"));

				// Add the Text to Each Cell
				CELL0.className = 'employee';
				CELL0.appendChild(NODE0);
				CELL1.className = 'status';
				CELL1.appendChild(NODE1);
				CELL2.className = 'avail';
				CELL2.appendChild(NODE2);
			}

			LIST = document.getElementById("divWork_Dashboard").getElementsByTagName('ul')[0];	// create the 'Outstanding Work' table/list
			var ROW;										// for use below to create a new <li> of the parent <ul>
			var WORK;										// create a new child <ul> to contain each job of the cycled module
			var JOB;										// create a new <li> in the child <ul>
			var MODULE = '';									// used to keep track of the different modules in the 'for' loop below
			var JOBS = XML.getElementsByTagName("job");
			for (I=0; I<JOBS.length; I++) {
				var MODULENAME = JOBS[I].getAttribute('sModuleName').replace(/ /g, "").replace(/&/g, "And");			// create a CamelCase module name

				if (JOBS[I].getAttribute('sModuleName') != MODULE) {				// if we have encountered a new module that needs to be added to the list, then...
					MODULE = JOBS[I].getAttribute('sModuleName');				// so this 'if' block will only be executed if we iterate to another module

					ROW = document.createElement("li");					// create a new <li> for the module in the parent <ul>			NOTE: create a "title" row for each iterated module
					ROW.setAttribute('onClick',"$('#li"+MODULENAME+"_Info').slideToggle('slow'); $('#lbl"+MODULENAME+"_Dashboard').toggleText('[+]','[-]');");
					ROW.innerHTML="<label id='lbl"+MODULENAME+"_Dashboard'>[+]</label> "+JOBS[I].getAttribute('sModuleName');
					LIST.appendChild(ROW);							// append the new <li> to the END of the parent <ul> list

					ROW = document.createElement("li");					// create a new <li> to contain the child <ul> for each job		NOTE: create a subsequent (collapsible) row to contain a list of all the work for the iterated module
					ROW.setAttribute('id',"li"+MODULENAME+"_Info");
					ROW.style.display = 'none';

					WORK = document.createElement("ul");					// create a new child <ul> containing each job of the module		NOTE: create the list for all of the work for the iterated module
					ROW.appendChild(WORK);							// make the new child <ul> the content of the parent <li> from above
					LIST.appendChild(ROW);							// append the new <li> from above to the parent <ul>
				}

				JOB = document.createElement("li");						// create a new <li> for the WORK ITEM in the child <ul>		NOTE: add each iterated job to the list of work
				JOB.innerHTML="<label class='lblSub'>"+JOBS[I].getAttribute('id')+"</label>&nbsp;"+JOBS[I].getAttribute('eRequired')+"<span>"+JOBS[I].getAttribute('sName')+"</span>";
				WORK.appendChild(JOB);								// append the new <li> to the END of the child <ul> list
			}

			if (! _iDashboardRefresh) { _iDashboardRefresh = setInterval("Dashboard('employees');", 10000); }			// set a 10 second refresh for this listing
			break;
		case "employeestop":		// this stops/clears the setInterval() from s_employees
			clearInterval(_iDashboardRefresh);
			_iDashboardRefresh = 0;	// this is so that the "if (! _iDashboardRefresh) {}" call above works on subsequent s_employees runs
			break;




		// Maintain the Dashboard Clocks
		case "clock":			// keeps the time up-to-date
			// http://www.elated.com/articles/creating-a-javascript-clock/
			var Now = new Date();
			var Hour = Now.getHours();
			var Minute = Now.getMinutes();
			var Half = (Hour < 12) ? "AM" : "PM";

			Minute = ((Minute < 10) ? "0" : "") + Minute;						// Pad the minutes and seconds with leading zeros, if required
			Hour = (Hour > 12) ? Hour - 12 : Hour;							// Convert the hours component to 12-hour format if needed
			Hour = ( Hour == 0 ) ? 12 : Hour;							// Convert an hours component of "0" to "12"

			document.getElementById("lblTime_Dashboard").innerHTML = Hour+":"+Minute+" "+Half;	// Update the time display
			document.getElementById("lblTime_Social").innerHTML = Hour+":"+Minute+" "+Half;
			break;
	}
}










// LEGACY - delete all the functions below after updating all the modules from using these calls

function showCalendar(strTextbox,evtCoord,intPos) {

alert("showCalendar() is deprecated; updated your code.");
return false;

	Project('Calendar',evtCoord);
}

function switchTabs(strScreen,intScreen) {

alert("switchTabs() is deprecated; updated your code.");
return false;

	Module('screen',null,strScreen,intScreen);
}

function switchBtns(strScreen) {

alert("switchBtns() is deprecated; updated your code.");
return false;

	Module('buttons',null,strScreen);
}

function switchScrn(strName) {

alert("switchScrn() is deprecated; updated your code.");
return false;

	Module('focus',null,strName);
}

function closeScrn() {

alert("closeScrn() is deprecated; updated your code.");
return false;

	Module('close');
}

function copyData() {

alert("copyData() is deprecated; updated your code.");
return false;

	Module('transpose');
}

function checkFile(filename,CamelCase) {

alert("checkFile() is deprecated; updated your code.");
return false;

	Module('load',null,filename,CamelCase);
}

function loadFile(sType,sURI,sCallback) {

alert("loadFile() is deprecated; updated your code.");
return false;

	Module('download',sCallback,sType,sURI);
}

function showWidgets(strAction,strType) {

alert("showWidgets() is deprecated; updated your code.");
return false;

	Widgets('load',null,strType);
}

function showWidgetInfo(strAction,strType,strWidget,strStatus) {

alert("showWidgetInfo() is deprecated; updated your code.");
return false;

	Widgets('info',null,strType,strWidget,strStatus);
}

function adjWidget(strAction,strType,strWidget,strStatus) {

alert("adjWidget() is deprecated; updated your code.");
return false;

	Widgets('adjust',null,strType,strWidget,strStatus);
}








// GLOBALS							LEFT OFF - rename application.js; turn all of the below functions into Application(Action) API
var reqIO;					// used for this modules' AJAX communication									LEFT OFF - rename to '_oApplication'




// INITIALIZATION FUNCTIONS

					// LEFT OFF - move this file to Application.js


// FUNCTIONALITY OF DASHBOARD HEADER




// FUNCTIONALITY OF COMMON CALLS

function loadContact(strAction,strType,strModule) {
// loads the contact information associated with the selected customer, vendor, or provider
// strType	specifies which column to create - valid values: Customer, Vendor, Provider	WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')	WARNING: this value IS case sensative!

alert("loadContact() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   switch(strAction) {
	case "req":
		ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=load&target=contact&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&acctID='+document.getElementById('n'+strType+'ContactList_'+camelcase).options[document.getElementById('n'+strType+'ContactList_'+camelcase).selectedIndex].value,'','','n'+strType+'ContactList_'+camelcase,'','',"loadContact('succ','"+strType+"','"+strModule+"');","loadContact('fail','"+strType+"','"+strModule+"');","loadContact('busy','"+strType+"','"+strModule+"');","loadContact('timeout','"+strType+"','"+strModule+"');","loadContact('inactive','"+strType+"','"+strModule+"');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		loadContact('req',strType,strModule);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		loadContact('req',strType,strModule);
		break;
	case "succ":
		var c = XML.getElementsByTagName("contact").item(0);

		document.getElementById('s'+strType+'ContactOPoID_'+camelcase).value = c.getAttribute("OPoID");
		document.getElementById('s'+strType+'Contact_'+camelcase).value = c.getAttribute("name");
		document.getElementById('s'+strType+'ContactEmail_'+camelcase).value = c.getAttribute("email");
		document.getElementById('n'+strType+'ContactPhone_'+camelcase).value = c.getAttribute("phone");
		document.getElementById('n'+strType+'ContactExt_'+camelcase).value = c.getAttribute("ext");
		document.getElementById('n'+strType+'ContactMobile_'+camelcase).value = c.getAttribute("mobile");
		document.getElementById('b'+strType+'ContactMobileSMS_'+camelcase).checked = (c.getAttribute("sms") == 0) ? false : true;
		document.getElementById('b'+strType+'ContactMobileEmail_'+camelcase).checked = (c.getAttribute("mail") == 0) ? false : true;

		switch(strType) {
		   case "Customer":
			document.getElementById('s'+strType+'ContactTitle_'+camelcase).value = c.getAttribute("title");
			break;
		   case "Vendor":
		   case "Provider":
			break;
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


function clearContact(strType,strModule) {
// clears the appropriate form objects so that a new record can be added to a multi-lined combobox
// strType	specifies which column to clear - valid values: Customer, Vendor, Provider	WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')	WARNING: this value IS case sensative!

alert("clearContact() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   document.getElementById('s'+strType+'ContactOPoID_'+camelcase).value = '';
   document.getElementById('s'+strType+'ContactOPoStatus_'+camelcase).value = '';
   document.getElementById('s'+strType+'Contact_'+camelcase).value = '';
   document.getElementById('s'+strType+'ContactEmail_'+camelcase).value = '';
   document.getElementById('n'+strType+'ContactPhone_'+camelcase).value = '';
   document.getElementById('n'+strType+'ContactExt_'+camelcase).value = '';
   if (document.getElementById('s'+strType+'ContactTitle_'+camelcase)) { document.getElementById('s'+strType+'ContactTitle_'+camelcase).value = ''; }
   document.getElementById('n'+strType+'ContactMobile_'+camelcase).value = '';
   document.getElementById('b'+strType+'ContactMobileSMS_'+camelcase).checked = false;
   document.getElementById('b'+strType+'ContactMobileEmail_'+camelcase).checked = false;
   document.getElementById('n'+strType+'ContactList_'+camelcase).options.selectedIndex = 0;

   document.getElementById('n'+strType+'ContactList_'+camelcase).options.selectedIndex = -1;
   document.getElementById('s'+strType+'ContactOPoID_'+camelcase).focus();
}


function newContact(strAction,strType,strModule,intRelated) {
// add a new contact to the list
// strType	specifies which column to create - valid values: Customer, Vendor, Provider	WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')	WARNING: this value IS case sensative!
// intRelated	the id of the record related to the type of contact (e.g. the id of the customer, vendor, or provider record)

alert("newContact() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   switch(strAction) {
	case "req":
		// used to help prevent duplicates from getting into the list
		if (document.getElementById('n'+strType+'ContactList_'+camelcase).selectedIndex != -1) {
		   if (window.confirm('It appears that a contact has been loaded for updating. Are you sure you want to create another that may be a possible duplicate of one that currently exists in the list?') == false)
			{ return false; }
		}

		if (document.getElementById('s'+strType+'Contact_'+camelcase).value == '') { alert("You must specify the contacts name before adding them to the list."); return false; }
		if (ListExists('n'+strType+'ContactList_'+camelcase,'',document.getElementById('s'+strType+'Contact_'+camelcase).value,0,0)) { alert("A contact with that description has already been added to the list, please use a different descriptive name before continuing."); return false; }

		ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=new&target=contact&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&type='+strType+'&id='+intRelated,'form'+strType+'Contact_'+camelcase,'','btn'+strType+'ContactAdd_'+camelcase,'','',"newContact('succ','"+strType+"','"+strModule+"','"+intRelated+"');","newContact('fail','"+strType+"','"+strModule+"','"+intRelated+"');","newContact('busy','"+strType+"','"+strModule+"','"+intRelated+"');","newContact('timeout','"+strType+"','"+strModule+"','"+intRelated+"');","newContact('inactive','"+strType+"','"+strModule+"','"+intRelated+"');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		newContact('req',strType,strModule,intRelated);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		newContact('req',strType,strModule,intRelated);
		break;
	case "succ":
		Add2List('n'+strType+'ContactList_'+camelcase,DATA['id'],document.getElementById('s'+strType+'Contact_'+camelcase).value,1,1,0);
// UPDATED 2025/07/09
//		selListbox('n'+strType+'ContactList_'+camelcase,DATA['id']);	// select the newly added item in the listbox
		Listbox('SelectOption','n'+strType+'ContactList_'+camelcase,DATA['id']);   	// set the selected availability for the availability listbox
		break;
	case "fail":
		// no reason to display anything because the server-side script will handle the message
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}


function delContact(strAction,strType,strModule) {
// delete the selected contact
// strType	specifies which column to create - valid values: Customer, Vendor, Provider	WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')	WARNING: this value IS case sensative!

alert("delContact() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   switch(strAction) {
	case "req":
		if (document.getElementById('n'+strType+'ContactList_'+camelcase).selectedIndex == -1) { alert("You must select a "+strType.toLowerCase()+" contact to remove before continuing."); return false; }

		ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=delete&target=contact&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&acctID='+document.getElementById('n'+strType+'ContactList_'+camelcase).options[document.getElementById('n'+strType+'ContactList_'+camelcase).selectedIndex].value,'','','btn'+strType+'ContactDel_'+camelcase,'','',"delContact('succ','"+strType+"','"+strModule+"');","delContact('fail','"+strType+"','"+strModule+"');","delContact('busy','"+strType+"','"+strModule+"');","delContact('timeout','"+strType+"','"+strModule+"');","delContact('inactive','"+strType+"','"+strModule+"');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		delContact('req',strType,strModule);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		delContact('req',strType,strModule);
		break;
	case "succ":
		ListRemove('n'+strType+'ContactList_'+camelcase,0);
		clearContact(strType,strModule);
// 2/27/2014 - don't know what this is for
//		if (document.getElementById('n'+strType+'ContactList_'+camelcase).options.length > 0) {
//		   document.getElementById('n'+strType+'ContactList_'+camelcase).options.selectedIndex = 0;		// select the first option in the list after the deletion
//		   loadAdditional_BusinessConfiguration('req',strType);										// load the businesses values in the form
//		}
		break;
	case "fail":
		// no reason to display anything because the server-side script will handle the message
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}


function updateContact(strAction,strType,strModule) {
// update the selected contact info
// strType	specifies which column to create - valid values: Customer, Vendor, Provider	WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')	WARNING: this value IS case sensative!

alert("updateContact() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   switch(strAction) {
	case "req":
		// used to help prevent duplicates from getting into the list
		if (document.getElementById('n'+strType+'ContactList_'+camelcase).selectedIndex == -1) {
		   alert("An existing contact has not been loaded for updating, please use the '+' (Add) button.");
		   return false;
		}

		if (document.getElementById('s'+strType+'Contact_'+camelcase).value == '') { alert("You must specify the contacts name before adding them to the list."); return false; }
		if (ListExists('n'+strType+'ContactList_'+camelcase,'',document.getElementById('s'+strType+'Contact_'+camelcase).value,0,1)) { alert("A contact with that description has already been added to the list, please use a different descriptive name before continuing."); return false; }

		ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=update&target=contact&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&type='+strType,'form'+strType+'Contact_'+camelcase,'','btn'+strType+'ContactUpdate_'+camelcase,'','',"updateContact('succ','"+strType+"','"+strModule+"');","updateContact('fail','"+strType+"','"+strModule+"');","updateContact('busy','"+strType+"','"+strModule+"');","updateContact('timeout','"+strType+"','"+strModule+"');","updateContact('inactive','"+strType+"','"+strModule+"');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		updateContact('req',strType,strModule);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		updateContact('req',strType,strModule);
		break;
	case "succ":
		ListReplace2('n'+strType+'ContactList_'+camelcase,'',document.getElementById('s'+strType+'Contact_'+camelcase).value,1,0,0);
		break;
	case "fail":
		// no reason to display anything because the server-side script will handle the message
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}




function loadFreight(strAction,strType,strModule) {
// loads the information associated with the selected freight account
// strType	specifies which column to create - valid values: Customer, Business		WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')	WARNING: this value IS case sensative!

alert("loadFreight() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   switch(strAction) {
	case "req":
		ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=load&target=freight&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+document.getElementById('n'+strType+'ShipList_'+camelcase).options[document.getElementById('n'+strType+'ShipList_'+camelcase).selectedIndex].value,'','','n'+strType+'ShipList_'+camelcase,'','',"loadFreight('succ','"+strType+"','"+strModule+"');","loadFreight('fail','"+strType+"','"+strModule+"');","loadFreight('busy','"+strType+"','"+strModule+"');","loadFreight('timeout','"+strType+"','"+strModule+"');","loadFreight('inactive','"+strType+"','"+strModule+"');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		loadFreight('req',strType,strModule);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		loadFreight('req',strType,strModule);
		break;
	case "succ":
		var f = XML.getElementsByTagName("freight").item(0);

		document.getElementById('s'+strType+'ShipName_'+camelcase).value = f.getAttribute("name");
		document.getElementById('s'+strType+'ShipAccount_'+camelcase).value = f.getAttribute("account");
		break;
	case "fail":
		// no reason to display anything because the server-side script will handle the message
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}


function clearFreight(strType,strModule) {
// clears the appropriate form objects so that a new record can be added to a multi-lined combobox
// strType	specifies which column to clear - valid values: Customer, Business		WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')	WARNING: this value IS case sensative!

alert("clearFreight() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   document.getElementById('s'+strType+'ShipName_'+camelcase).value = '';
   document.getElementById('s'+strType+'ShipAccount_'+camelcase).value = '';

   if (document.getElementById('n'+strType+'ShipList_'+camelcase).options.length >= 0) { document.getElementById('n'+strType+'ShipList_'+camelcase).options.selectedIndex = -1; }	// remove any selection in the combobox
   document.getElementById('s'+strType+'ShipName_'+camelcase).focus();
}


function newFreight(strAction,strType,strModule,intRelated) {
// add a new freight account to the list
// strType	specifies which column to create - valid values: Customer, Business		WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')	WARNING: this value IS case sensative!
// intRelated	the id of the record related to the type of contact (e.g. the id of the customer, vendor, or provider record)

alert("newFreight() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   switch(strAction) {
	case "req":
		// used to help prevent duplicates from getting into the list
		if (document.getElementById('n'+strType+'ShipList_'+camelcase).selectedIndex != -1) {
		   if (window.confirm('It appears that a freight account has been loaded for updating. Are you sure you want to create another that may be a possible duplicate of one that currently exists in the list?') == false)
			{ return false; }
		}

		if (document.getElementById('s'+strType+'ShipName_'+camelcase).value == '') { alert("You must specify a descriptive name for the account before adding it to the list."); return false; }
		if (document.getElementById('s'+strType+'ShipAccount_'+camelcase).value == '') { alert("You must specify the freight account number before adding it to the list."); return false; }
		if (ListExists('n'+strType+'ShipList_'+camelcase,'',document.getElementById('s'+strType+'ShipName_'+camelcase).value,0,0)) { alert("A frieght company with that description has already been added to the list, please use a different description before continuing."); return false; }

		ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=new&target=freight&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&type='+strType+'&id='+intRelated,'form'+strType+'Ship_'+camelcase,'','btn'+strType+'ShipAdd_'+camelcase,'','',"newFreight('succ','"+strType+"','"+strModule+"','"+intRelated+"');","newFreight('fail','"+strType+"','"+strModule+"','"+intRelated+"');","newFreight('busy','"+strType+"','"+strModule+"','"+intRelated+"');","newFreight('timeout','"+strType+"','"+strModule+"','"+intRelated+"');","newFreight('inactive','"+strType+"','"+strModule+"','"+intRelated+"');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		newFreight('req',strType,strModule,intRelated);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		newFreight('req',strType,strModule,intRelated);
		break;
	case "succ":
		Add2List('n'+strType+'ShipList_'+camelcase,DATA['id'],document.getElementById('s'+strType+'ShipName_'+camelcase).value,1,1,0);
// UPDATED 2025/07/09
//		selListbox('n'+strType+'ShipList_'+camelcase,DATA['id']);	// select the newly added item in the listbox
		Listbox('SelectOption','n'+strType+'ShipList_'+camelcase,DATA['id']);   	// select the newly added item in the listbox
		break;
	case "fail":
		// no reason to display anything because the server-side script will handle the message
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}


function delFreight(strAction,strType,strModule) {
// delete the selected frieght account
// strType	specifies which column to create - valid values: Customer, Business		WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')	WARNING: this value IS case sensative!

alert("delFreight() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   switch(strAction) {
	case "req":
		if (document.getElementById('n'+strType+'ShipList_'+camelcase).selectedIndex == -1) { alert("You must select a frieght company to remove before continuing."); return false; }

		ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=delete&target=freight&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+document.getElementById('n'+strType+'ShipList_'+camelcase).options[document.getElementById('n'+strType+'ShipList_'+camelcase).selectedIndex].value,'','','btn'+strType+'ShipDel_'+camelcase,'','',"delFreight('succ','"+strType+"','"+strModule+"');","delFreight('fail','"+strType+"','"+strModule+"');","delFreight('busy','"+strType+"','"+strModule+"');","delFreight('timeout','"+strType+"','"+strModule+"');","delFreight('inactive','"+strType+"','"+strModule+"');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		delFreight('req',strType,strModule);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		delFreight('req',strType,strModule);
		break;
	case "succ":
		ListRemove('n'+strType+'ShipList_'+camelcase,0);
		clearFreight(strType,strModule);
// 2/28/2014 - don't know what this is for
//		if (document.getElementById('sShipList_'+camelcase).options.length >= 0) { document.getElementById('sShipList_'+camelcase).options.selectedIndex = 0; }	// select the first option in the list after the deletion
		break;
	case "fail":
		// no reason to display anything because the server-side script will handle the message
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}


function updateFreight(strAction,strType,strModule) {
// update the selected freight account info
// strType	specifies which column to create - valid values: Customer, Business		WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')	WARNING: this value IS case sensative!

alert("updateFreight() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   switch(strAction) {
	case "req":
		// used to help prevent duplicates from getting into the list
		if (document.getElementById('n'+strType+'ShipList_'+camelcase).selectedIndex == -1) {
		   alert("An existing freight account has not been loaded for updating, please use the '+' (Add) button.");
		   return false;
		}

		if (document.getElementById('s'+strType+'ShipName_'+camelcase).value == '') { alert("You must specify a descriptive name for the freight company before adding it to the list."); return false; }
		if (document.getElementById('s'+strType+'ShipAccount_'+camelcase).value == '') { alert("You must specify the freight account number before adding it to the list."); return false; }
		if (ListExists('n'+strType+'ShipList_'+camelcase,'',document.getElementById('s'+strType+'ShipName_'+camelcase).value,0,1)) { alert("A freight company with that information has already been added to the list, please use a different description before continuing."); return false; }

		ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=update&target=freight&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&type='+strType,'form'+strType+'Ship_'+camelcase,'','btn'+strType+'ShipUpdate_'+camelcase,'','',"updateFreight('succ','"+strType+"','"+strModule+"');","updateFreight('fail','"+strType+"','"+strModule+"');","updateFreight('busy','"+strType+"','"+strModule+"');","updateFreight('timeout','"+strType+"','"+strModule+"');","updateFreight('inactive','"+strType+"','"+strModule+"');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		updateFreight('req',strType,strModule);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		updateFreight('req',strType,strModule);
		break;
	case "succ":
		ListReplace2('n'+strType+'ShipList_'+camelcase,'',document.getElementById('s'+strType+'ShipName_'+camelcase).value,1,0,0);
		break;
	case "fail":
		// no reason to display anything because the server-side script will handle the message
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}




function loadBank(strAction,strType,strModule) {
// loads the information associated with the selected bank account
// strType	specifies which column to create - valid values: Business, Location, Vendor, Provider	WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')		WARNING: this value IS case sensative!

alert("loadBank() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   switch(strAction) {
	case "req":
		ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=load&target=bank&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+document.getElementById('n'+strType+'BankList_'+camelcase).options[document.getElementById('n'+strType+'BankList_'+camelcase).selectedIndex].value,'','','n'+strType+'BankList_'+camelcase,'','',"loadBank('succ','"+strType+"','"+strModule+"');","loadBank('fail','"+strType+"','"+strModule+"');","loadBank('busy','"+strType+"','"+strModule+"');","loadBank('timeout','"+strType+"','"+strModule+"');","loadBank('inactive','"+strType+"','"+strModule+"');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		loadBank('req',strType,strModule);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		loadBank('req',strType,strModule);
		break;
	case "succ":
		var b = XML.getElementsByTagName("bank").item(0);

		document.getElementById('s'+strType+'BankDesc_'+camelcase).value = b.getAttribute("name");
		document.getElementById('n'+strType+'BankRouting_'+camelcase).value = b.getAttribute("routing");
		document.getElementById('n'+strType+'BankAccount_'+camelcase).value = b.getAttribute("account");
		if (document.getElementById('s'+strType+'BankCheckType_'+camelcase)) {
// UPDATED 2025/07/09
//			selListbox('s'+strType+'BankCheckType_'+camelcase,b.getAttribute("type"));
			Listbox('SelectOption','s'+strType+'BankCheckType_'+camelcase,b.getAttribute("type"));
			document.getElementById('n'+strType+'BankCheckNo_'+camelcase).value = b.getAttribute("check");
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


function clearBank(strType,strModule) {
// clears the appropriate form objects so that a new record can be added to a multi-lined combobox
// strType	specifies which column to clear - valid values: Business, Location, Vendor, Provider	WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')		WARNING: this value IS case sensative!

alert("clearBank() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   document.getElementById('s'+strType+'BankDesc_'+camelcase).value = '';
   document.getElementById('n'+strType+'BankRouting_'+camelcase).value = '';
   document.getElementById('n'+strType+'BankAccount_'+camelcase).value = '';
   if (document.getElementById('s'+strType+'BankCheckType_'+camelcase)) {
	document.getElementById('s'+strType+'BankCheckType_'+camelcase).options.selectedIndex = 0;
	document.getElementById('n'+strType+'BankCheckNo_'+camelcase).value = '';
   }

   if (document.getElementById('n'+strType+'BankList_'+camelcase).options.length >= 0) { document.getElementById('n'+strType+'BankList_'+camelcase).options.selectedIndex = -1; }	// remove any selection in the combobox
   document.getElementById('s'+strType+'BankDesc_'+camelcase).focus();
}


function newBank(strAction,strType,strModule,intRelated) {
// add a new bank account to the list
// strType	specifies which column to create - valid values: Business, Location, Vendor, Provider	WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')		WARNING: this value IS case sensative!
// intRelated	the id of the record related to the type of contact (e.g. the id of the customer, vendor, or provider record)

alert("newBank() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   switch(strAction) {
	case "req":
		// used to help prevent duplicates from getting into the list
		if (document.getElementById('n'+strType+'BankList_'+camelcase).selectedIndex != -1) {
		   if (window.confirm('It appears that a bank account has been loaded for updating. Are you sure you want to create another that may be a possible duplicate of one that currently exists in the list?') == false)
			{ return false; }
		}

		if (document.getElementById('s'+strType+'BankDesc_'+camelcase).value == '') { alert("You must specify the description of this bank account before adding it to the list."); return false; }
		if (document.getElementById('n'+strType+'BankRouting_'+camelcase).value == '') { alert("You must specify the routing number for this bank account before adding it to the list."); return false; }
		if (document.getElementById('n'+strType+'BankAccount_'+camelcase).value == '') { alert("You must specify the account number for this bank account before adding it to the list."); return false; }
		if (document.getElementById('n'+strType+'BankCheckNo_'+camelcase))
		   { if (document.getElementById('n'+strType+'BankCheckNo_'+camelcase).value == '') { alert("You must specify the next available check number for this bank account before adding it to the list."); return false; } }
		if (ListExists('n'+strType+'BankList_'+camelcase,'',document.getElementById('s'+strType+'BankDesc_'+camelcase).value,0,0)) { alert("A bank account with that description has already been added to the list, please use a different description before continuing."); return false; }

		ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=new&target=bank&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&type='+strType+'&id='+intRelated,'form'+strType+'Banks_'+camelcase,'','btn'+strType+'BankAdd_'+camelcase,'','',"newBank('succ','"+strType+"','"+strModule+"','"+intRelated+"');","newBank('fail','"+strType+"','"+strModule+"','"+intRelated+"');","newBank('busy','"+strType+"','"+strModule+"','"+intRelated+"');","newBank('timeout','"+strType+"','"+strModule+"','"+intRelated+"');","newBank('inactive','"+strType+"','"+strModule+"','"+intRelated+"');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		newBank('req',strType,strModule,intRelated);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		newBank('req',strType,strModule,intRelated);
		break;
	case "succ":
		Add2List('n'+strType+'BankList_'+camelcase,DATA['id'],document.getElementById('s'+strType+'BankDesc_'+camelcase).value,1,1,0);
		selListbox('n'+strType+'BankList_'+camelcase,DATA['id']);	// select the newly added item in the listbox
		break;
	case "fail":
		// no reason to display anything because the server-side script will handle the message
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}


function delBank(strAction,strType,strModule) {
// delete the selected bank account
// strType	specifies which column to create - valid values: Business, Location, Vendor, Provider	WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')		WARNING: this value IS case sensative!

alert("delBank() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   switch(strAction) {
	case "req":
		if (document.getElementById('n'+strType+'BankList_'+camelcase).selectedIndex == -1) { alert("You must select a bank account to remove before continuing."); return false; }

		ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=delete&target=bank&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+document.getElementById('n'+strType+'BankList_'+camelcase).options[document.getElementById('n'+strType+'BankList_'+camelcase).selectedIndex].value,'','','btn'+strType+'BankDel_'+camelcase,'','',"delBank('succ','"+strType+"','"+strModule+"');","delBank('fail','"+strType+"','"+strModule+"');","delBank('busy','"+strType+"','"+strModule+"');","delBank('timeout','"+strType+"','"+strModule+"');","delBank('inactive','"+strType+"','"+strModule+"');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		delBank('req',strType,strModule);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		delBank('req',strType,strModule);
		break;
	case "succ":
		ListRemove('n'+strType+'BankList_'+camelcase,0);
		if (document.getElementById('n'+strType+'BankList_'+camelcase).options.length >= 0) { document.getElementById('n'+strType+'BankList_'+camelcase).options.selectedIndex = 0; }	// select the first option in the list after the deletion
		break;
	case "fail":
		// no reason to display anything because the server-side script will handle the message
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}


function updateBank(strAction,strType,strModule) {
// update the selected bank account info
// strType	specifies which column to create - valid values: Business, Location, Vendor, Provider	WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')		WARNING: this value IS case sensative!

alert("updateBank() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   switch(strAction) {
	case "req":
		// used to help prevent duplicates from getting into the list
		if (document.getElementById('n'+strType+'BankList_'+camelcase).selectedIndex == -1) {
		   alert("An existing bank account has not been loaded for updating, please use the '+' (Add) button.");
		   return false;
		}

		if (document.getElementById('s'+strType+'BankDesc_'+camelcase).value == '') { alert("You must specify the description of this bank account before adding it to the list."); return false; }
		if (document.getElementById('n'+strType+'BankRouting_'+camelcase).value == '') { alert("You must specify the routing number for this bank account before adding it to the list."); return false; }
		if (document.getElementById('n'+strType+'BankAccount_'+camelcase).value == '') { alert("You must specify the account number for this bank account before adding it to the list."); return false; }
		if (document.getElementById('n'+strType+'BankCheckNo_'+camelcase))
		   { if (document.getElementById('n'+strType+'BankCheckNo_'+camelcase).value == '') { alert("You must specify the next available check number for this bank account before adding it to the list."); return false; } }
		if (ListExists('n'+strType+'BankList_'+camelcase,'',document.getElementById('s'+strType+'BankDesc_'+camelcase).value,0,1)) { alert("A bank account with that information has already been added to the list, please use a different name before continuing."); return false; }

		ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=update&target=bank&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&type='+strType,'form'+strType+'Banks_'+camelcase,'','btn'+strType+'BankUpdate_'+camelcase,'','',"updateBank('succ','"+strType+"','"+strModule+"');","updateBank('fail','"+strType+"','"+strModule+"');","updateBank('busy','"+strType+"','"+strModule+"');","updateBank('timeout','"+strType+"','"+strModule+"');","updateBank('inactive','"+strType+"','"+strModule+"');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		updateBank('req',strType,strModule);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		updateBank('req',strType,strModule);
		break;
	case "succ":
		ListReplace2('n'+strType+'BankList_'+camelcase,'',document.getElementById('s'+strType+'BankDesc_'+camelcase).value,1,0,0);
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

function saveNote(strAction,strType,strModule,intID) {
// initializes the module by loading all of the form object values after the screen contents have been added
// strType	specifies which column to create - valid values: Customer, Employee, WO		WARNING: this value IS case sensative!
// strModule	the name of the module calling this function (e.g. 'Business Configuration')	WARNING: this value IS case sensative!
// intID	the record ID in the associated table that this note belongs to

alert("saveNote() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   switch(strAction) {
	case "req":
		if (document.getElementById('sNoteAccess_'+camelcase).selectedIndex == -1) { alert("You must select the access level for the note before saving it."); return false; }
		if (document.getElementById('sNote_'+camelcase).value == '') { alert("You must enter text before saving the note data."); return false; }

		ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=save&target=note&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&id='+intID,'formNotes_'+camelcase,'','','','',"saveNote('succ','"+strType+"','"+strModule+"','"+intID+"');","saveNote('fail','"+strType+"','"+strModule+"','"+intID+"');","saveNote('busy','"+strType+"','"+strModule+"','"+intID+"');","saveNote('timeout','"+strType+"','"+strModule+"','"+intID+"');","saveNote('inactive','"+strType+"','"+strModule+"','"+intID+"');");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		saveNote('req',strType,strModule,intID);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		saveNote('req',strType,strModule,intID);
		break;
	case "succ":
		// Define the objects
		var t = document.getElementById('tblNotes_'+camelcase);
		var r = t.insertRow(-1);			// Insert a row at the end of the list

		// Insert the cells in the row
		var c0 = r.insertCell(0);
		var c1 = r.insertCell(1);
		var c2 = r.insertCell(2);

		// Append a text to the appropriate cell
		var t0 = document.createTextNode(DATA['date']);
		var t1 = document.createTextNode(DATA['creator']);
		//var t2 = document.createTextNode();

		c0.className = 'tdLast center';
		c0.appendChild(t0);
		c1.className = 'tdLast';
		c1.appendChild(t1);
		c1.className = 'tdLast';
		c2.innerHTML = PIPED.replace(/\\r\\n|\\r|\\n/g,'<br />');		// WARNING: createTextNode does NOT handle HTML formatting, so we use innerHTML here instead!

		// clean up the form after submission
		document.getElementById('sNoteAccess_'+camelcase).options.selectedIndex = 0;
		document.getElementById('sNote_'+camelcase).value = '';
		break;
	case "fail":
		// no reason to display anything because the server-side script will handle the message
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}




// FUNCTIONALITY OF BUTTONS ON THE 'DATA' TAB

function addDataFields(strFormID) {
// dynamically creates a new line to upload additional data in the module
// NOTE: this function returns the index of the just created <li>
// strFormID	the container div id (e.g. divTabs_CustomerAccounts2)
// [intID]	the DB id value of the entry (used when populating forms)

alert("addDataFields() is deprecated; updated your code.");
return false;

   var liClone = $('#'+strFormID+' .ulColData .liDataTemplate').clone();

   var last = $('#'+strFormID+' .ulColData li:last').html();
       //last = /sCustomFileTitle(\d+)/.exec(last)[0];			// this was a quick way to assign the [0] index to the variable, but it would also throw an error if there wasn't any values in the array
       last = /sCustomFileTitle(\d+)/.exec(last);			// store all the results in an array
   if (last == null) { last=1; } else { last = parseInt(last[0].substring(16))+1; }	// if there weren't any custom fields added, then start the index at 1, otherwise obtain the last one and increase it by 1

   liClone.html(liClone.html().replace(/sCustomFileTitle_/g, 'sCustomFileTitle'+last+'_'));
   liClone.html(liClone.html().replace(/sCustomFilename_/g, 'sCustomFilename'+last+'_'));
   liClone.html(liClone.html().replace(/btnCustomFileUpdate_/g, 'btnCustomFileUpdate'+last+'_'));
   liClone.html(liClone.html().replace(/btnCustomFileDel_/g, 'btnCustomFileDel'+last+'_'));
   liClone.html(liClone.html().replace(/btnCustomFileEncrypt_/g, 'btnCustomFileEncrypt'+last+'_'));
   liClone.html(liClone.html().replace(/intIndex/g,last));
   if (arguments.length > 1) { liClone.html(liClone.html().replace(/,0\);/, ','+arguments[1]+');')); }	// if the 'intID' was passed, then adjust the <li> just created

   $('#'+strFormID+' .ulColData').append("<li>" + liClone.html());	// http://stackoverflow.com/questions/16744594/how-to-use-jquery-to-add-form-elements-dynamically
   return last;
}


function delDataFields(strFormID,strModule,intSkip,intIndex) {
// delete an associated data field in the module
// strFormID	the container div id (e.g. divTabs_CustomerAccounts2)
// strModule	the name of the module calling this function (e.g. 'Business Configuration')	WARNING: this value IS case sensative!
// intSkip	the count of default items in the list to skip from the overall count
// intIndex	the index value assocated with the line's form objects that will be deleted

alert("delDataFields() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   var LIs = $('#'+strFormID+' .ulColData li').get();

// LEFT OFF - we will need to send an AJAX request to the server to confirm the file deletion
//		prompt the user before sending the AJAX request asking if the file needs to be deleted too
   for (var i=intSkip; i<LIs.length; i++)
	{ if (LIs[i].innerHTML.match('sCustomFileTitle'+intIndex+'_'+camelcase)) {$('#'+strFormID+' .ulColData li').eq(i).remove(); break;} }
}


function updateDataFields(strAction,strFormID,strModule,intSkip,intIndex,strTitle,strFilename,intRecord,intID) {
// updates the information related to the uploaded file entry
// strFormID	the container div id (e.g. divTabs_CustomerAccounts2)
// strModule	the name of the module calling this function (e.g. 'Business Configuration')	WARNING: this value IS case sensative!
// intSkip	the count of default items in the list to skip from the overall count
// intIndex	the index number used in the <li> that is being updated		NOTE: this is encapsulated in single quotes since the static <li>'s don't use this value (so a blank value can be passed without errors)
// strTitle	the title to assign to the uploaded file entry
// strFilename	same as above, but for the filename
// intRecord	the id of the DB row that corresponds with the module record (e.g. Customer Account Number)
// intID	the id of the DB row (in the 'Upload' table) that corresponds with this file entry -OR- 0 for a new entry

alert("updateDataFields() is deprecated; updated your code.");
return false;

   var filename = strModule.replace(/ /g, "_").toLowerCase();	// create filename
   var camelcase = strModule.replace(/ /g, "");			// create CamelCase

   switch(strAction) {
	case "req":
		ajax(reqIO,4,'post',gbl_uriProject+"code/"+filename+".php",'action=update&target=upload&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&module='+escape(strModule)+'&title='+escape(strTitle)+'&filename='+escape(strFilename)+'&record='+intRecord+'&id='+intID,'','','','','',"updateDataFields('succ','"+strFormID+"','"+strModule+"',"+intSkip+",'"+intIndex+"','"+strTitle+"','"+strFilename+"',"+intRecord+","+intID+");","updateDataFields('fail','"+strFormID+"','"+strModule+"',"+intSkip+",'"+intIndex+"','"+strTitle+"','"+strFilename+"',"+intRecord+","+intID+");","updateDataFields('busy','"+strFormID+"','"+strModule+"',"+intSkip+",'"+intIndex+"','"+strTitle+"','"+strFilename+"',"+intRecord+","+intID+");","updateDataFields('timeout','"+strFormID+"','"+strModule+"',"+intSkip+",'"+intIndex+"','"+strTitle+"','"+strFilename+"',"+intRecord+","+intID+");","updateDataFields('inactive','"+strFormID+"','"+strModule+"',"+intSkip+",'"+intIndex+"','"+strTitle+"','"+strFilename+"',"+intRecord+","+intID+");");
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		updateDataFields('req',strFormID,strModule,intSkip,intIndex,strTitle,strFilename,intRecord,intID);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		updateDataFields('req',strFormID,strModule,intSkip,intIndex,strTitle,strFilename,intRecord,intID);
		break;
	case "succ":
		// if <s><data>...</data></s> was returned, then we need to adjust the account number
		if (DATA.hasOwnProperty('id') === true) {	// see: http://stackoverflow.com/questions/135448/how-do-i-check-to-see-if-an-object-has-a-property-in-javascript
		   var LIs = $('#'+strFormID+' .ulColData li').get();
		   var title = document.getElementById('sCustomFileTitle'+intIndex+'_'+camelcase).value;	// backup the values prior to adjusting the HTML below (since it erases them)
		   var file = document.getElementById('sCustomFilename'+intIndex+'_'+camelcase).selectedIndex;

		   for (var i=intSkip; i<LIs.length; i++) {	// find the <li> that needs to update its intID value (so that we know to use UPDATE instead of INSERT in mySQL calls)
			if (LIs[i].innerHTML.match('sCustomFileTitle'+intIndex+'_'+camelcase)) {
			   LIs[i].innerHTML = LIs[i].innerHTML.replace(/,0\);/, ','+DATA['id']+');');
			   break;
			}
		   }

		   // now restore the values after we've adjusted the HTML above!
		   document.getElementById('sCustomFileTitle'+intIndex+'_'+camelcase).value = title;
		   document.getElementById('sCustomFilename'+intIndex+'_'+camelcase).selectedIndex = file;
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




// FUNCTIONALITY OF SEARCHING THE DB FOR MATCHING RESULTS							LEFT OFF - these both can be deleted in favor of using _search.js|php		DEPRECATED - delete

function submitSearch2(strAction,strScript,strTarget,strObject,strValue,pipeDisplay) {
// searches for a matching parent software project (Google-style results)
// strScript	the name of the serverside script to call
// strTarget	the target to pass to strScript to identify what to search for
// strObject	the listbox containing all the results
// strValue	the textbox that contains the string to match against in the strScript
// pipeDisplay	a pipe separated list of (returned) values to display for each item in the results list (e.g. name|id|address)
// [succCallback]	the 'success' callback function to use upon a successful search; if no value is passed, the internal function callback is used		WARNING: a successful search can also have 0 results!

alert("submitSearch2() is deprecated; updated your code.");
return false;

   switch(strAction) {
	case "req":
		if (document.getElementById(strValue).value == '') {		// if the search box was cleared, then...
			document.getElementById(strObject).style.display = 'none';	// hide the results listbox
			alert("No results were found that matched the (portion of the) search criteria.");
			return true;
		}
		if (document.getElementById(strObject).style.display == 'inline-block' && _bEditable == document.getElementById(strValue).value) { return true; }	// don't re-search if the results list is already populated **based on an existing value** (e.g. if the user alt-tabbed between apps with the search box having focus)

		if (arguments.length == 6)
			{ ajax(reqIO,4,'post',gbl_uriProject+"code/"+strScript,'action=search&target='+strTarget+'&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&value='+document.getElementById(strValue).value,'','','','','',"submitSearch('succ','"+strScript+"','"+strTarget+"','"+strObject+"','"+strValue+"','"+pipeDisplay+"');","submitSearch('fail','"+strScript+"','"+strTarget+"','"+strObject+"','"+strValue+"','"+pipeDisplay+"');","submitSearch('busy','"+strScript+"','"+strTarget+"','"+strObject+"','"+strValue+"','"+pipeDisplay+"');","submitSearch('timeout','"+strScript+"','"+strTarget+"','"+strObject+"','"+strValue+"','"+pipeDisplay+"');","submitSearch('inactive','"+strScript+"','"+strTarget+"','"+strObject+"','"+strValue+"','"+pipeDisplay+"');"); }
		else
			{ ajax(reqIO,4,'post',gbl_uriProject+"code/"+strScript,'action=search&target='+strTarget+'&username='+escape(document.getElementById('hidUsername').value)+'&SID='+escape(document.getElementById('hidSID').value)+'&value='+document.getElementById(strValue).value,'','','','','',arguments[6],"submitSearch('fail','"+strScript+"','"+strTarget+"','"+strObject+"','"+strValue+"','"+pipeDisplay+"');","submitSearch('busy','"+strScript+"','"+strTarget+"','"+strObject+"','"+strValue+"','"+pipeDisplay+"');","submitSearch('timeout','"+strScript+"','"+strTarget+"','"+strObject+"','"+strValue+"','"+pipeDisplay+"');","submitSearch('inactive','"+strScript+"','"+strTarget+"','"+strObject+"','"+strValue+"','"+pipeDisplay+"');"); }
		break;
	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		submitSearch('req',strScript,strTarget,strObject,strValue,pipeDisplay);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		submitSearch('req',strScript,strTarget,strObject,strValue,pipeDisplay);
		break;
	case "succ":
		var m = XML.getElementsByTagName("match");
		var p = pipeDisplay.split("|");
		var P = new Array();
		var t = '';					// the text to display as a search result item
		var b = false;					// boolean value indicating the use of surrounding [] characters to one of the values

//		if (m.length == 0) { return true; }		// if no results were returned, then exit
		document.getElementById(strObject).options.length = 0;		// remove any prior results
		document.getElementById(strObject).style.display = 'inline-block';

		for (var i=0; i<m.length; i++) {
		   P = p.slice(0);				// this preserves the original value passed to the function (since we modify it below)
		   t = '';					// reset the variable after each line has been added to the results list
		   for (var j=0; j<P.length; j++) {		// construct the value to show for each iterated item in the results list
			if (/\[/.test(P[j])) {			// if there needs to be a surrounding bracket around one of the values, then...
			   b = true;				// indicate that via this variable value
			   P[j] = P[j].replace(/\[|\]/g,'');	// remove those characters from the value
			}
			if (b) { t += '['; }
			if (P[j] == 'firstChild')		// if we need to include the child data, then...
			   { t += m[i].firstChild.data; }
			else					// otherwise this is an attribute value to add, so...
			   { t += m[i].getAttribute(P[j]); }
			if (b) { t += ']'; }
			t += ' ';

			b = false;				// reset the boolean value
		   }
		   Add2List(strObject,m[i].getAttribute('id'),t,1,1,0);
		   //Add2List(strObject,m[i].getAttribute('id'),m[i].firstChild.data,1,1,0);	replaced in favor of customer display above
		}
		break;
	case "fail":
		// the server-side script will handle any messages to the user
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}


function selectMatch2(strValue,strTarget,strResults) {
// makes form adjustments when one of the options in the Google-style results listbox is clicked
// strValue	what value should be placed in the strTarget: text, value
// strTarget	the id of the target textbox that should receive the selected value from the list.	NOTE: you can pass 'return' for this value to return the strValue like: var retval = selectMatch('return', ...);
// strResults	the id of the results combobox

alert("selectMatch2() is deprecated; updated your code.");
return false;

	if (strValue == 'text')
		{ var val = document.getElementById(strResults).options[document.getElementById(strResults).selectedIndex].text; }
	else
		{ var val = document.getElementById(strResults).options[document.getElementById(strResults).selectedIndex].value; }

	document.getElementById(strResults).options.length=0;	// blank out all the options to not pollute future matches
	document.getElementById(strResults).style.display='none';	// hide the matches listbox now that a selection has been made

	if (strTarget == 'return') { return val; }			// if the selected result needs to be returned instead of inserted, then...
	document.getElementById(strTarget).value = val;		// otherwise, insert the value in the passed textbox
}

