// _Database.js
//
// Created	2025-09-25 by Dave Henderson (support@cliquesoft.org)
// Updated	2025-09-25 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.




// -- Global Variables --
var _oDatabase;					// used for this modules' AJAX communication




// -- Application API --

function Database(sAction) {
	// NOTE: these functions are universal to the webWorks framework

	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var mCallback = null;											// the callback to perform

	switch(sAction) {
		case "Load":
			if (arguments.length < 5) { mRequirements = false; } else { mRequirements = 5; }
			if (arguments.length > 8) { mCallback = arguments[8]; }
			break;
		case "s_Load":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;
		case "Save":
			if (arguments.length < 5) { mRequirements = false; } else { mRequirements = 5; }
			if (arguments.length > 7) { mCallback = arguments[7]; }
			break;
		case "s_Save":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;
		case "Delete":
		case "Disable":
			if (arguments.length < 5) { mRequirements = false; } else { mRequirements = 5; }
			if (arguments.length > 7) { mCallback = arguments[7]; }
			break;
		case "s_Delete":
		case "s_Disable":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 2) { mCallback = arguments[2]; }
			break;


		default:
			Project('Popup','fail',"ERROR: Database('"+sAction+"') is not a valid action for the function API.");
			return false;
	}

	// sanity checks
	if (typeof mRequirements == 'boolean' && ! mRequirements) {						// first check if the mandatory parameter count is met
		alert("ERROR: Database('"+sAction+"') was called without meeting a sufficent number of parameters.");
		return false;
	} else if (typeof mRequirements == 'number') {								// second check if those mandatory parameters have an actual value
		for (let i=0; i<mRequirements; i++) {
			if (arguments[i] == '') {
				alert("ERROR: Database('"+sAction+"') was called with mandatory parameter #"+i+" being blank.");
				return false;
			}
		}
	}

	// lets perform some work!
	switch(sAction) {
		   // OVERVIEW		Loads the requested module/group record information (all tabs for modules)
		   // SYNTAX		Database('Load',sModule,sTab,sGroup,sType,oTarget,oDisable='',nOffset=0,mCallback='');
		case "Load":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sType		[string] Defines the type of target: List (e.g. History/Contacts), Record (e.g. Invoice/Asset)		'Record'
		   //			  NOTE   'List' loads all records for the group, 'Record' loads a specific record spanning multiple tabs
		   // 2: sModule	[string] The name of the module containing to information to process (in CamelCase)			'CustomerAccounts'	
		   // 3: sTab		[string] The name of the tab containing to information to process					'General'
		   // 4: sGroup		[string] defines the <form> name containing the objects to process					'Contacts'
		   // 5: oTarget	[string][function] Object containing the db record id to process    (if sType==Record, blank otherwise)	'oContacts_CustomerAccounts'	['']
		   // 6: oDisable	[string][function] Object to disable while the ajax is working						'oLoad_CustomerAccounts'	['']
		   // 7: nOffset	[number] The <option> offset to prevent processing (e.g. if oTarget=<listbox> & <option 0>='Select...')	1			[0]
		   // 8: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// perform some safety checks
			var sType = arguments[1];
			if (sType != 'List' || sType != 'Record') {
				Project(_sProjectUI,'fail',"An illegal value has been passed for the loading type.");
				return false;
			}
//			if (sType == 'List' && (arguments[3] == '' || arguments[4] == '')) {			// NOTE: we included this since this will be mandatory depending on the value of sType
//				Project(_sProjectUI,'fail',"The Tab and Group names must be included with this request.");
//				return false;
//			}
//			if (sType == 'Record' && arguments[5] == '') {						// NOTE: we included this since this will be mandatory depending on the value of sType
//				Project(_sProjectUI,'fail',"The Target object must be included with this request.");
//				return false;
//			}

			var oTarget = (typeof arguments[5] === 'object') ? arguments[5] : document.getElementById(arguments[5])
			// if we're dealing with a listbox|combobox, then...
			if (oTarget.type == 'select-one' || oTarget.type == 'select-multiple'))
				{ if (oTarget.selectedIndex < arguments[7]) {return false;} }			// exit if the user clicked 'Select...' or 'New...'
			// otherwise we're dealing with a textbox|textarea|password, so...
			else
				{ if (oTarget.value == '') {return false;} }					// exit if the user tried submitting a blank value

			if (arguments[6] == '') { var oDisable = ''; }						// if there's no object to disable...
			else { var oDisable = (typeof arguments[6] === 'object') ? arguments[6] : document.getElementById(arguments[6]); }	// otherwise assign a value to the variable

			// now construct the tabs/group syntax (e.g. '?||General:CustomerAccounts_General:Application_Contacts[name|postion]||...')
			var oTabs = document.getElementById('ulTabs_'+arguments[2]).getElementsByTagName('li');
			var sTabs = '';
			if (sType == 'List') {
				sTabs += (arguments[3] != '&#9776;' ? arguments[3] : 'LIST');
				if (arguments[4] == 'Associated' || arguments[4] == 'Contacts' || arguments[4] == 'Discounts' || arguments[4] == 'FreightAccounts')
					{ sTabs += ':' + 'Application_' + arguments[4]; }
				else
					{ sTabs += ':' + arguments[2] + '_' + arguments[3] + (arguments[3] != arguments[4] ? '_'+arguments[4] : ''); }

				if (arguments[4] == 'Associated')
					{ sTabs += '=fkModules'; }
				else
					{ sTabs += '=sName'; }

// left off - add in a data-listing html attribute and append that here

			}else if (sType == 'Record') {
// UPDATED 2025/09/26 - this now includes groups within each table
//				for (var i=0; i<oTabs.length; i++) { sTabs += (sTabs=='' ? '' : '|') + (oTabs[i].innerHTML.trim() != '&#9776;' ? oTabs[i].innerHTML.trim() : 'LIST'); }
				for (var i=0; i<oTabs.length; i++) {
					var sTab = oTabs[i].innerHTML.trim();
					// add in each tab to the list (e.g. '?|General|...')
					sTabs += (sTabs=='' ? '' : '||') + (sTab != '&#9776;' ? sTab : 'LIST');

					sTab = sTab.toLowerCase();
					if (sTab == 'data' || sTab == 'notes' || sTab == 'specs') { continue; }		// we don't need to process these tabs with the code below

					// now add in each group within the tab (e.g. '?||General:CustomerAccounts_General:Application_Contacts[name|postion]||...')		NOTE: these are double piped (||) as separators, with listing text being single pipe separated
					// NOTE: we need to incorporate the module name and group name in the naming convention (unless the group is part of the module itself)
					//	 so we know where to pull the data from in the database (e.g. Contacts_Application = 'Application' module and the 'Contacts' group)
					var oGroups = document.getElementById('oTabs'+i+"_"arguments[2]).getElementsByTagName('form');
					for (var j=0; j<oGroups.length; j++) { sTabs += ':'+oGroups[j].id; }
				}
			}

			Ajax('Call',_oDatabase,_sUriProject+'code/_Database.php','!'+sAction+'!,(sUsername),(sSessionID),&sTabs='+escape(sTabs)+'&sModule='+arguments[2]+'&sTab='+arguments[3]+'&sGroup='+arguments[4]+'&sType='+sType+'&id='+oTarget.value,oDisable,function(){Database('s_'+sAction,sType,sTabs,mCallback);});
			break;
		case 's_Load':		// success!
		   // 1: sType		[string] Defines the type of target: List (e.g. History/Contacts), Record (e.g. Invoice/Asset)
		   // 2: sTabs		[string] A pipe separated list of tabs within the module
		   // 3: mCallback	[string][function] The callback to execute upon success
			var T = (arguments[1] =='Record') ? XML.getElementsByTagName('tab') : XML.getElementsByTagName('group');
			for (var i=0; i<T.length; i++) {							// cycle each target (tab or group) to process the returned values
//				// NOTE: the following tabs/groups will have their processing skipped in php: Contacts, Associated, Notes, Specs, Data

				var F = T[i].getElementsByTagName('field');
				for (var j=0; j<F.length; j++) {						// cycle each field of the tab/group to populate the values
					var oElement = document.getElementById(F[j].getAttribute('sName'));
					switch(oElement) {
						case 'checkbox':						// checkbox
						case 'radio':							// radio button
							oElement.checked = (F[j].getAttribute('sValue') == 0) ? false : true;
							break;
						case 'select-one':						// combobox/listbox
						case 'select-multiple':
							if (oElement.size == 1) {				//   if we're selecting an <option> in a listbox, then...
								Listbox('SelectOption',oElement,F[j].getAttribute('sValue'));
							} else {						//   otherwise we're populating a list (e.g. Contacts), so...
								var I = F[j].getElementsByTagName('item');
								for (var k=0; k<I.length; k++)			//   cycle each returned item to add an <option> to the combobox
									{ Listbox('AddOption',oElement,I[k].getAttribute('sValue'),I[k].getAttribute('sText'),I[k].getAttribute('sOptgroup'),I[k].getAttribute('sClasses')); }
							}
							break;
						case 'span':							// infobox
							oElement.innerHTML = F[j].getAttribute('sValue');
							break;
						case 'input':							// textbox
						case 'textarea':						// textarea
							oElement.value = F[j].getAttribute('sValue');
							break;
						case 'table':							// table
							var TBL = F[j].getElementsByTagName('sName');
							var I = F[j].getElementsByTagName('item');
							for (var k=0; k<I.length; k++) {			// cycle each option of the list to add the values
								var oRow0 = TBL.insertRow(-1);			// insert a new row at the end of the table
								var oCells = new Array();			// create an array for each cell in the row
								var oCell;					// create a variable to store any separated cell\n";
// REMOVED 2025/09/13 - RETAIN this for the moment until the code can be tested to ensure we do NOT need to perform the commented out steps
//								var oTexts = new Array();			// do the same for text nodes
//								var oText;
								var nCount = I[k].attributes.length;		// store the number of columns the rows will have
								if (F[j].getElementsByTagName('sSeparate') != '') {
									var oRow1 = t.insertRow(-1);
									nCount--;				// remove one from the count since one cell will be on a line by itself
								}

								// Create the cells for the iterated row
								for (var l=0; l<nCount; l++)
									{ oCells.push(oRow0.insertCell(l)); }
								if (I[k].attributes.length > nCount) { oCell = oRow1.insertCell(0); }		// if we separated a cell, then store it

// REMOVED 2025/09/13 - RETAIN this for the moment until the code can be tested to ensure we do NOT need to perform the commented out steps
//								// Create text for each cell in the row
//								for (var l=0; l<nCount; l++)
//									{ oTexts.push(document.createTextNode(I[k].getAttribute('sColumn'+l))); }
//								if (I[k].attributes.length > nCount) { oText = document.createTextNode(I[k].getAttribute('sColumn'+(l+1))); }	// if we separated a cell, then store its text

								// Merge all the above components together
								for (var l=0; l<nCount; l++) {
									oCells[l].className = I[k].getAttribute('sClasses'+l);
									if (F[j].getAttribute('sDescription')=='sColumn'+l && I[k].firstChild)	// if there is a description, then...
										{ oCells[l].innerHTML = I[k].firstChild.data.replace(/\r\n|\r|\n/g,'<br />'); }
									else									// otherwise it's just simple text in the cell, so...
										{ oCells[l].innerHTML = I[k].getAttribute('sColumn'+l); }
								}
								if (I[k].attributes.length > nCount) {		// if we separated a cell, then...
									if (I[k].firstChild)			//   if there is a description, then...
										{ oCell.innerHTML = I[k].firstChild.data.replace(/\r\n|\r|\n/g,'<br />'); }
									else					//   otherwise it's just simple text in the cell, so...
										{ oCell.innerHTML = I[k].getAttribute('sColumn'+(l+1)); }
								}
							}
							break;
					}
				}
			}

			// we now need to process any of these: Contacts, Associated, Notes, Specs, Data
//			var s_Tabs = arguments[2].split('|');
//			for (var i=0; i<s_Tabs.length; i++) {
//				if (s_Tabs[i] == Notes) { Application('LoadNote',sModule,id,sType='Customer',sScript='application.php',mCallback=''); }
//			}
			break;




		   // OVERVIEW		Saves the requested module record information (one tab only)
		   // SYNTAX		Database('Save',sModule,sTab,sGroup,oTarget,oDisable='',sName='',mCallback='');
		case "Save":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The name of the module containing to information to process (in CamelCase)			'CustomerAccounts'	
		   // 2: sTab		[string] The name of the tab containing to information to process					'General'	
		   // 3: sGroup		[string] defines the <form> name containing the objects to process					'Contacts'
		   // 4: oTarget	[string][function] Object containing the id of the record to process (e.g. <textbox> or <option> value)	'oContacts_CustomerAccounts'
		   // 5: oDisable	[string][function] Object to disable while the ajax is working						'oSave_CustomerAccounts'	['']
		   // 6: sName		[string] Defines the name of the new item being CREATED (this is blank when UPDATING)			'John Doe'		['']
		   // 7: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// perform some safety checks
			var oTarget = (typeof arguments[4] === 'object') ? arguments[4] : document.getElementById(arguments[4])
			var sName = (arguments.length < 5) ? '' : arguments[6];
			var oElements = document.forms[arguments[3]].elements;
			for (var i=0; i<oElements.length; i++) {			// cycle each element in the group to check for mandatory values
				if (oElements[i].hasAttribute('data-mandatory')) {		// if the iterated element must have a value, then check that it does!
					// this is for blank textboxes|textareas|passwords, 'Select...' listboxes|comboboxes
					if (oElements[i].value == '') {
						Project(_sProjectUI,'fail',"The '"+oElements[i].getAttribute('data-description')+"' field can not have a blank or unselected value.");
						return false;
					// these are for blank or duplicate 'New...' listboxes|comboboxes
					} else if (oElements[i].value == '+' && sName == '') {
						Project(_sProjectUI,'fail',"You must enter a value before saving a new entry for the '"+oElements[i].getAttribute('data-description')+"' field.");
						return false;
					} else if (oElements[i].value == '+' && Listbox('CheckOption',oTarget,sName,'text')) {
						Project(_sProjectUI,'fail',"An entry with that value already exists in the '"+oElements[i].getAttribute('data-description')+"' field.");
						return false;
					}
				}

				// this is for textboxes|textareas|passwords
				if (oElements[i].hasAttribute('data-validate'))	// NOTE: this will also check any associated 'new' textbox (e.g. sModule)
					{ if (! Security('Validate',oElements[i],oElements[i].getAttribute('data-validate'),oElements[i].getAttribute('data-description'))) {return false;} }
			}

			if (arguments[5] == '') { var oDisable = ''; }	// if there's no object to disable...
			else { var oDisable = (typeof arguments[5] === 'object') ? arguments[5] : document.getElementById(arguments[5]); }	// otherwise assign a value to the variable

			var id = (oTarget.value == '+') ? '%2B' : escape(oTarget.value);	// NOTE: we have to take this step because PHP can convert '+' to ' ' causing an error

			Ajax('Call',_oDatabase,_sUriProject+'code/_Database.php','!'+sAction+'!,(sUsername),(sSessionID),&sModule='+arguments[1]+'&sTab='+arguments[2]+'&sGroup='+arguments[3]+'&id='+id,oDisable,function(){Database('s_'+sAction,oTarget,sName,mCallback);},null,null,null,arguments[3]);
			break;
		case 's_Save':		// success!
		   // 1: oTarget	[string][function] Object containing the id of the record to save (e.g. <textbox> or <option> value)
		   // 2: sName		[string] Defines the name of the new item being CREATED (this is blank when UPDATING)
		   // 3: mCallback	[string][function] The callback to execute upon success
			// send a notification to the user
			Project(_sProjectUI,'succ');

			if (DATA.hasOwnProperty('id')) {	// if we are creating a record, then...
				// if we dealing with a listbox|combobox, then...
				if (arguments[1].type == 'select-one' || arguments[1].type == 'select-multiple'))
					{ Listbox('AddOption',arguments[1], DATA['id'], arguments[2]); }			// add the new entry to the listbox
				// otherwise we're dealing with a textbox|textarea|password, so...
				else
					{ arguments[1].value = DATA['id']; }
			} else {				// otherwise we are updating a record, so...
				if (arguments[1].type == 'select-one' || arguments[1].type == 'select-multiple'))
					{ Listbox('ReplaceOption',arguments[1],arguments[1].value,arguments[2]); }	// replace the old value with the new one
			}
								"
			// perform some cleanup
			if (DATA.hasOwnProperty('id')) { delete DATA['id']; }		// to prevent contamination between calls
			break;




		   // OVERVIEW		Deletes/Disables the requested tab/group record information
		   // SYNTAX		Database('Delete',sModule,sTab,sGroup,oTarget,oDisable='',nOffset=0,mCallback='');
		   //			Database('Disable',sModule,sTab,sGroup,oTarget,oDisable='',nOffset=0,mCallback='');
		case 'Delete':
		case "Disable":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sModule	[string] The name of the module containing to information to process (in CamelCase)			'CustomerAccounts'	
		   // 2: sTab		[string] The name of the tab containing to information to process					'General'	
		   // 3: sGroup		[string] defines the <form> name containing the objects to process					'Contacts'
		   // 4: oTarget	[string][function] Object containing the id of the record to process (e.g. <textbox> or <option> value)	'oContacts_CustomerAccounts'
		   // 5: oDisable	[string][function] Object to disable while the ajax is working						'oSave_CustomerAccounts'	['']
		   // 6: nOffset	[number] The <option> offset to prevent processing (e.g. if oTarget=<listbox> & <option 0>='Select...')	1			[0]
		   // 7: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// perform some safety checks
			var oTarget = (typeof arguments[4] === 'object') ? arguments[4] : document.getElementById(arguments[4])
			// if we're dealing with a listbox|combobox, then...
			if (oTarget.type == 'select-one' || oTarget.type == 'select-multiple'))
				{ if (oTarget.selectedIndex < arguments[6]) {return false;} }
			// otherwise we're dealing with a textbox|textarea|password, so...
			else
				{ if (oTarget.value == '') {return false;} }

			if (arguments[5] == '') { var oDisable = ''; }	// if there's no object to disable...
			else { var oDisable = (typeof arguments[5] === 'object') ? arguments[5] : document.getElementById(arguments[5]); }	// otherwise assign a value to the variable

			Ajax('Call',_oDatabase,_sUriProject+'code/_Database.php','!'+sAction+'!,(sUsername),(sSessionID),&sModule='+arguments[1]+'&sTab='+arguments[2]+'&sGroup='+arguments[3]+'&id='+oTarget.value,oDisable,function(){Database('s_'+sAction,oTarget,mCallback);});
			break;
		case 's_Delete':		// success!
		case 's_Disable':		// success!
		   // 1: oTarget	[string][function] Object containing the id of the record to save (e.g. <textbox> or <option> value)	'oContacts_CustomerAccounts'
		   // 2: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// send a notification to the user
			Project(_sProjectUI,'succ');

			// delete the selected item from the list
			if (oTarget.type == 'select-one' || oTarget.type == 'select-multiple'))
				{ Listbox('RemoveOption',arguments[1]); }
			break;
	}


	// Perform any passed callback
	if (sAction.substring(0,2) == 's_') {								// only execute these lines if a 'success' return has been made
		if (typeof(mCallback) === 'function') { mCallback(); }					// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
		else if (typeof(mCallback) === 'string') { eval(mCallback); }				// using this line, the value can be passed as: "alert('hello world');"
	}
}
