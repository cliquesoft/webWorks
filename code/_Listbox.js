// _Listbox.js
//
// Created:	2004/07/25 by Dave Henderson (support@cliquesoft.org)
// Updated:	2025/08/30 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.




// -- Listbox API --

function Listbox(sAction) {
	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var oListbox = (typeof arguments[1] === "object") ? arguments[1] : document.getElementById(arguments[1]);
	var oOptions = '';											// the <option>'s to process: entire <select>, individual <optgroup>
	var oOptgroup = '';											// the <optgroup> to interact with
	var mOptgroup = '';											// the <optgroup>'s label or index
	var mCallback = null;											// the callback to perform
	var bCallback = false;											// whether a callback needs to be performed; NOTE: this is set WITHIN each function
	var nOffset = 0;

	switch(sAction) {
		case "CheckOption":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 4) { mOptgroup = arguments[4]; }
			if (arguments.length > 7) { mCallback = arguments[7]; }
			break;
		case "SelectOption":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 4) { mOptgroup = arguments[4]; }
			if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "AddOption":
// ADJUSTED 2025/07/23 - had to allow for 'Value' parameters to be blank
//			if (arguments.length < 4) { mRequirements = false; } else { mRequirements = 4; }
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 4) { mOptgroup = arguments[4]; }
			if (arguments.length > 12) { mCallback = arguments[12]; }
			break;
		case "MoveOption":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 3) { mOptgroup = arguments[3]; }
			if (arguments.length > 6) { mCallback = arguments[6]; }
			break;
		case "RemoveOption":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 4) { mOptgroup = arguments[4]; }
			if (arguments.length > 7) { mCallback = arguments[7]; }
			break;
		case "ReplaceOption":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 6) { mOptgroup = arguments[6]; }
			if (arguments.length > 12) { mCallback = arguments[12]; }
			break;
		case "OrderOption":
			if (arguments.length < 4) { mRequirements = false; } else { mRequirements = 4; }
			mOptgroup = '';
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		case "CopyOptions":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 3) { mOptgroup = arguments[3]; }
			if (arguments.length > 7) { mCallback = arguments[7]; }
			break;
		case "CountOptions":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 2) { mOptgroup = arguments[2]; }
			break;
// REMOVED 2025/08/07 - duplicate of CheckOption
//		case "InOptions":
//			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
//			if (arguments.length > 4) { mOptgroup = arguments[4]; }
//			break;
		case "SortOptions":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 2) { mOptgroup = arguments[2]; }
			if (arguments.length > 6) { mCallback = arguments[6]; }
			break;
		case "CheckOptgroup":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			mOptgroup = arguments[2];
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;
		case "OptionClass":
			if (arguments.length < 4) { mRequirements = false; } else { mRequirements = 4; }
			if (arguments.length > 6) { mOptgroup = arguments[6]; }
			if (arguments.length > 7) { mCallback = arguments[7]; }
			break;


		default:
			Project('Popup','fail',"ERROR: Listbox('"+sAction+"') is not a valid action for the function API.");
			return false;
	}

	// sanity checks
	if (typeof mRequirements == 'boolean' && ! mRequirements) {						// first check if the mandatory parameter count is met
		alert("ERROR: Listbox('"+sAction+"') was called without meeting a sufficent number of parameters.");
		return false;
	} else if (typeof mRequirements == 'number') {								// second check if those mandatory parameters have an actual value
		for (let i=0; i<mRequirements; i++) {
			if (arguments[i] == '') {
				alert("ERROR: Listbox('"+sAction+"') was called with mandatory parameter #"+i+" being blank.");
				return false;
			}
		}
	}

	// now lets determine if the selection is for the entire listbox or just an optgroup
	if (mOptgroup == '') {											// IF there we don't need to remove from an <optgroup>, then...
		oOptions = oListbox.options;
	} else {												// ELSE we do, so...
		if (isNaN(mOptgroup)) {										//   IF we were given a label, then lets find it using that!
			for (var i=0; i<oListbox.getElementsByTagName("optgroup").length; i++) {
				if (oListbox.getElementsByTagName("optgroup")[i].label == mOptgroup) {
					oOptgroup = oListbox.getElementsByTagName("optgroup")[i];
					oOptions = oListbox.getElementsByTagName("optgroup")[i].getElementsByTagName('option');			// NOTE: have to use getElementsByTagName() since <optgroup> doesn't have '.options'
					break;
				}
			}
			if (oOptgroup == '' && sAction != 'CheckOptgroup')
				{ Project('Popup','fail',"There is not an &lt;optgroup&gt; with that label."); return false; }
		} else {											//   ELSE we were passed an index value, so...
			if (! oListbox.getElementsByTagName("optgroup")[parseInt(mOptgroup)]) {			//     IF that <optgroup> does NOT exist, then...
				if (sAction != 'AddOption' && sAction != 'CheckOptgroup') {			//       IF we are NOT adding an <option> -OR- checking for the presence of an <optgroup>, then alert of this absence!
					Project('Popup','fail',"There is not an &lt;optgroup&gt; with that index.");
					return false;
				} else {									//       ELSE we ARE adding an <option>, so create it!
					oOptgroup = document.createElement('optgroup');				//         create it
					oListbox.appendChild(oOptgroup);					//         add it to the <ul>
// REMOVED 2025/08/07 - since oOptgroup just got created, there won't be any <option>'s in it; leave default value
//					oOptions = oOptgroup.getElementsByTagName('option');
				}
// UPDATED 2025/08/07 - these should only be executed it the indexed <optgroup> does exist (since the inverse is processed above)
//			}
//			if (oOptgroup == '') { oOptgroup = oListbox.getElementsByTagName("optgroup")[parseInt(mOptgroup)]; }
//			if (oOptions == '') { oOptions = oListbox.getElementsByTagName("optgroup")[parseInt(mOptgroup)].getElementsByTagName('option'); }		// NOTE: have to use getElementsByTagName() since <optgroup> doesn't have '.options'
			} else {										//    ELSE that optgroup DOES exist, so store the values
				oOptgroup = oListbox.getElementsByTagName("optgroup")[parseInt(mOptgroup)];
				oOptions = oListbox.getElementsByTagName("optgroup")[parseInt(mOptgroup)].getElementsByTagName('option');
			}
		}
	}

	// lets perform some work!
	switch(sAction) {
		   // OVERVIEW		Returns true/false if the <select>|<optgroup> already contains an <option> that matches passed criteria
		   // SYNTAX		if (Listbox('CheckOption',mListbox,sValue,sCheck='value',mOptgroup='',bSensitive=true,bSkipSelected=false,mCallback='')) { ...yes... } else { ...no... }
		case "CheckOption":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mListbox	[string][object] The listbox to manipulate								'Employees'
		   // 2: sValue		[string] The value 'sCheck' parameter uses in search							'Dave'
		   // 3: sCheck		[string] The aspect to check against;							  [text, value]	'text'			['value']
		   // 4: mOptgroup	[string][number] The label or index value of the <optgroup> to process					'Names'			['']
		   // 5: bSensitive	[boolean] Toggles searching to be case sensitive (true)/insensitive (false)				false			[true]
		   // 6: bSkipSelected	[boolean] Toggles exclusion (true)/inclusion (false) of the selected <option> in the search		true			[false]
		   // 7: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			if (arguments.length < 4) { arguments[3] = 'value'; }
			if (arguments.length < 5) { arguments[4] = ''; }
			if (arguments.length < 6) { arguments[5] = true; }
			if (arguments.length < 7) { arguments[6] = false; }

			// perform task
			for (var i=0; i<oOptions.length; i++) {
				if (arguments[6] && i == oListbox.selectedIndex) { continue; }

				if (arguments[3] == 'value' && arguments[5] && oOptions[i].value == arguments[2]) { bCallback=true; break; }
				if (arguments[3] == 'value' && ! arguments[5] && oOptions[i].value.toLowerCase() == arguments[2].toLowerCase()) { bCallback=true; break; }

				if (arguments[3] == 'text' && arguments[5] && oOptions[i].text == arguments[2]) { bCallback=true; break; }
				if (arguments[3] == 'text' && ! arguments[5] && oOptions[i].text.toLowerCase() == arguments[2].toLowerCase()) { bCallback=true; break; }
			}
			if (! bCallback) { return false; }
			break;											// proceed to perform any callback and return true




		   // OVERVIEW		Selects all <option>'s in the <select>|<optgroup> matching passed criteria
		   // SYNTAX		if (Listbox('SelectOption',mListbox,sValue,sCheck='value',mOptgroup='',mCallback='')) { ...yes... } else { ...no... }
		case "SelectOption":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mListbox	[string][object] The listbox to manipulate								'Employees'
		   // 2: sValue		[string] The value 'sCheck' parameter uses in search; Accepts '*'					'Dave'
		   //			[ NOTE ] If '*' is passed and the <select>'s 'multiple' is not already enabled, it will be toggled
		   // 3: sCheck		[string] The aspect to check against; '*' above invalidates			   [index, text, value]	'value'			['value']
		   // 4: mOptgroup	[string][number] The label or index value of the <optgroup> to process					'Newcomers'		['']
		   // 5: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// value checking
			if (arguments[3] == 'index') {								// if we need to select a specific <option>, then...
				if (oListbox.options.length < parseInt(arguments[2])) {				//   if the <select> doesn't have enough <option>'s, then indicate an error
					Project('Popup','fail',"There is not an &lt;option&gt; with that index to select.");
					return false;
				}
			}

			// default value assignments
			if (arguments.length < 4) { arguments[3] = 'value'; }

// REMOVED 2025/09/03 - these are no longer used
//			var nIndex = 0;
			var nSelected = 0;

			// update the value of 'sCheck' if requested so that checks below don't trigger incorrectly
			if (arguments[2] == '*') { arguments[3] = '*'; }

			// enable multi-select if it's not already enabled and we need to select everything; it is assumed multi-select would already be enabled otherwise (as we have no idea of whether to turn this off/on otherwise)
			if (arguments[2] == '*' && oListbox.multiple == false) { oListbox.multiple = true; }

			// now lets de-select any existing <option> so that doesn't pollute what we intend to do
			oListbox.selectedIndex = -1;

			// process an index value, if passed
			var nStart = (arguments[3] == 'index') ? parseInt(arguments[2]) : 0;

			// lastly, lets find and select all that we need to!
			for (i=nStart; i<oOptions.length; i++) {						// cycle each <option> in the <select>
				if (arguments[3] == 'text' && oOptions[i].text != arguments[2]) { continue; }
				if (arguments[3] == 'value' && oOptions[i].value != arguments[2]) { continue; }
				if (arguments[3] == 'index' && i != parseInt(arguments[2])) { continue; }
				// if we've made it here then none of the matches above occurred, or '*' was passed for 'sValue'

				oOptions[i].selected = true;							// select the <option> if it matches our criteria
				nSelected++;									// store the info for the selected item (for use below)
// REMOVED 2025/09/03 - these are no longer used
//				nIndex = i;

				if (arguments[2] == '*' && i < oOptions.length) { continue; }			// cycle all the <option>'s if we're instructed to do so!
				bCallback=true; break;								// break to perform any callback and return true
			}
// UPDATED 2025/09/03 - this was iterferring when optgroup's were used
//			if (nSelected == 1) { oListbox.selectedIndex = nIndex; }				// if only one selection was made, then set the .selectedIndex value to it as well (to set the actual selection in size=1 listboxes; has no effect on size>1)
			if (nSelected == 1) {									// if only one selection was made, then...
				for (var i=0; i<oListbox.options.length; i++)					//   cycle all the <option>'s to find the one that was selected above
					{ if (oListbox.options[i].selected) {oListbox.selectedIndex=i;} }	//   now set the .selectedIndex value to it as well
				// NOTE: we had to do this step because the "selected=true" above would NOT set the actual selection in size=1 listboxes; has no effect on size>1 listboxes
			}
			if (! bCallback) { return false; }							// return fail if not
			break;											// proceed to perform any callback and return true




		   // OVERVIEW		Adds an <option> to the <select>|<optgroup> matching passed criteria
		   // SYNTAX		if (Listbox('AddOption',mListbox,sValue,sText,mOptgroup='',sClass='',bSelect=false,bDuplicates=false,bSort=false,sDirection='ascend',nOffset=0,bSensitive=false,mCallback='')) { ...yes... } else { ...no... }
		case "AddOption":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mListbox	[string][object] The listbox to manipulate								'Employees'
// LEFT OFF - flip 2 & 3 below and allow the value to be blank; adjust the minimum parameter count at that time
		   // 2: sValue		[string] The value of the <option>; Accepts '{AUTO}' for auto-incrementing				'#1234'
		   // 3: sText		[string] The text of the <option>									'Dave'
		   // 4: mOptgroup	[string][number] The label or index value of the <optgroup> to process					'Newcomers'		['']
		   // 5: sClass		[string] The class name(s) to add to the <option>							'hired'			['']
		   // 6: bSelect	[boolean] Toggles the <option>'s 'selected' value							true			[false]
		   // 7: bDuplicates	[boolean] Toggles duplicate <option>'s existing in the list						true			[false]
		   // 8: bSort		[boolean] Toggles the <option>'s being sorted post-addition						true			[false]
		   // 9: sDirection	[string] The direction to sort							      [ascend, descend]	'descend'		['ascend']
		   // 10: nOffset	[number] The offset in the 'mListbox' (e.g. to skip sorting a 'Select...' 0-index)			1			[0]
		   // 11: bSensitive	[boolean] Toggles sorting to be case sensitive (true)/insensitive (false)				true			[false]
		   // 12: mCallback	[string][function] The callback to execute upon success							"alert('done!');"	['']
		   // *: Resets		[2x list] The string ids and values of the textboxes/listboxes to adjust/reset				Listbox('AddOption',...,'State','FL','Zipcode','',...);
			// default value assignments
			if (arguments.length < 5) { arguments[4] = ''; }
			if (arguments.length < 6) { arguments[5] = ''; }
			if (arguments.length < 7) { arguments[6] = false; }
			if (arguments.length < 8) { arguments[7] = false; }
			if (arguments.length < 9) { arguments[8] = false; }
			if (arguments.length < 10) { arguments[9] = 'ascend'; }
			if (arguments.length < 11) { arguments[10] = 0; }
			if (arguments.length < 12) { arguments[11] = false; }

			var oOption = document.createElement('option');
			var i;

			// check for duplicates
			if (! arguments[7]) {									// code block used to prevent the addition of duplicate "records"
				for (i=0; i<oOptions.length; i++) {
					if (oOptions[i].text == arguments[3]) {
						Project(_sProjectUI,'fail',"A \"" + oOptions[i].text + "\" entry already exist in the list.");
						return false;
					}
				}
			}

			// set the new <option> values
			oOption.selected = arguments[6];							// if this item needs to be selected in the list, then do so!
			oOption.text = arguments[3];								// set the listbox entry's display text
			if (arguments[5] != '') { oOption.className = arguments[5]; }				// if the item needs to have a class(es) added, then do so!
			if (arguments[2] == '{AUTO}')								// IF the user wants an autoincrement the <option> value, then..
				{ oOption.value = oOptions.length; }						//   add the next highest number
			else											// ELSE the user specified a value, so...
				{ oOption.value = arguments[2]; }						//   assign it to the entry
			if (typeof oOptgroup == 'string' && oOptgroup == '') {					// IF oOptgroup is the default '' value, then we have no <optgroup> to add to, so...
				oListbox.appendChild(oOption);							//   add the new entry to the <ul>
				if (arguments[8]) { Listbox('SortOptions',oListbox,arguments[4],arguments[9],arguments[10],arguments[11]); }	//   sorts entire <UL> list after user adds a new entry (if specified)
			} else {										// ELSE we do have an <optgroup> to add the <option> to, so do it!
				oOptgroup.appendChild(oOption);
				if (arguments[8]) { Listbox('SortOptions',oOptgroup,arguments[4],arguments[9],arguments[10],arguments[11]); }	//   sorts only <OPTGROUP> list after user adds a new entry (if specified)
			}

			nOffset = 13;										// set the offset incase any passed objects need resetting
			break;											// proceed to perform any callback and return true




		   // OVERVIEW		Moves the <option> up/down in the <select>|<optgroup> matching passed criteria
		   // SYNTAX		if (Listbox('MoveOption',mListbox,sDirection,mOptgroup='',sRetension='both',nOffset=0,mCallback='')) { ...yes... } else { ...no... }
		case "MoveOption":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mListbox	[string][object] The listbox/optgroup to manipulate							'Employees'
		   // 2: sDirection	[string] The direction to move								     [up, down]	'up'
// LEFT OFF - setup the below as a string and if a number is passed, then it will use as an index
		   // 3: mOptgroup	[string][number] The label or index value of the <optgroup> to process					'Newcomers'		['']
		   // 4: sRetension	[string] Which aspects of the <option> to retain				    [value, text, both]	'text'			['both']
		   // 5: nOffset	[number] The offset in the 'mListbox' (e.g. to skip moving previous to 'Select...' 0-index)		1			[0]
		   // 6: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			if (arguments.length < 4) { arguments[3] = ''; }
			if (arguments.length < 5) { arguments[4] = 'both'; }
			if (arguments.length < 6) { arguments[5] = 0; }

			var sPrior = '';
			var nIndex = 0;

			// do some sanity checks and store the corresponding <option> index
			if (arguments[2] == 'up') {
				// exit if there's nothing selected -OR- moving up enters nOffset (in <select>|<optgroup>)
				if (oListbox.selectedIndex == -1 || oListbox.options[oListbox.selectedIndex] == oOptions[parseInt(arguments[5])]) { return false; }
				nIndex = oListbox.selectedIndex - 1;						// set the index of the corresponding <option> to modify (prior/post)
			} else {
				// exit if there's nothing selected -OR- in last spot trying to move down
				if (oListbox.selectedIndex == -1 || oListbox.options[oListbox.selectedIndex] == oOptions[oOptions.length - 1]) { return false; }
				nIndex = oListbox.selectedIndex + 1;
			}

			// lets move the <option> in the direction requested
			if (arguments[4] != 'value') {
				sPrior = oOptions[nIndex].text;							// store the prior/post <option>'s aspect to a variable
				oOptions[nIndex].text = oOptions[oListbox.selectedIndex].text;			// set the prior/post <option>'s aspect to the currently selected <option>
				oOptions[oListbox.selectedIndex].text = sPrior;					// set the currently selected <option>'s aspect to the stored prior value
			}
			if (arguments[4] != 'text') {								// WARNING; these MUST be two separate 'if' statements!
				sPrior = oOptions[nIndex].value;
				oOptions[nIndex].value = oOptions[oListbox.selectedIndex].value;
				oOptions[oListbox.selectedIndex].value = sPrior;
			}
			oListbox.selectedIndex = nIndex;							// this allows the selected <option> to follow the item being moved
			break;											// proceed to perform any callback and return true




		   // OVERVIEW		Deletes all <option>'s in the <select>|<optgroup> matching passed criteria
		   // SYNTAX		if (Listbox('RemoveOption',mListbox,sValue='',sCheck='selected',mOptgroup='',nPrompt=0,nRetain=0,mCallback='')) { ...yes... } else { ...no... }
		case "RemoveOption":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mListbox	[string][object] The listbox to manipulate								'Employees'
		   // 2: sValue		[string] The value 'sCheck' parameter uses in search; Accepts '*'; '(un)selected' invalidates		'Dave'			['']
		   // 3: sCheck		[string] The aspect to check against; '*' above invalidates  [index, text, value, selected, unselected]	'value'			['selected']
		   // 4: mOptgroup	[string][number] The label or index value of the <optgroup> to process					'Newcomers'		['']
		   // 5: nPrompt	[number] Prompts prior to removing affected item(s)    [0=No Prompt, 1=Single Prompt, 2=Every Deletion]	2			[0]
		   // 6: nRetain	[number] Existing <option> count in the 'mListbox' to retain; useful when sValue='*'			1			[0]
		   // 7: mCallback	[string][function] The callback to execute upon success							"alert('done!');"	['']
			// default value assignments
			if (arguments.length < 3) { arguments[2] = ''; }					// this value is not acknowledged by default since 'sCheck=selected' is also the default
			if (arguments.length < 4) { arguments[3] = 'selected'; }
			if (arguments.length < 5) { arguments[4] = ''; }
			if (arguments.length < 6) { arguments[5] = 0; }
			if (arguments.length < 7) { arguments[6] = 0; }

			// update the value of 'sCheck' if requested so that checks below don't trigger incorrectly
			if (arguments[2] == '*') { arguments[3] = '*'; }

			// now lets perform the actual removal
			for (var i=oOptions.length-1; i>=arguments[6]; i--) {					// NOTE: we have to traverse in reverse because deleting options changes indices 
				if (arguments[3] == 'text' && oOptions[i].text != arguments[2]) { continue; }
				if (arguments[3] == 'value' && oOptions[i].value != arguments[2]) { continue; }
				if (arguments[3] == 'index' && i != parseInt(arguments[2])) { continue; }
				if (arguments[3] == 'selected' && ! oOptions[i].selected) { continue; }
				if (arguments[3] == 'unselected' && oOptions[i].selected) { continue; }
				// if we've made it here then none of the matches above occurred, or '*' was passed for 'sValue'

				if (arguments[5]) {
					if (! confirm('Are you sure you want to delete the "'+oOptions[i].text+'" entry?')) { return 0; }	// if the user clicked cancel, then exit this function
					if (arguments[5] == 1 || arguments[5] == true) {arguments[5] = false;}	// otherwise, turn off the prompt so it doesn't keep asking to delete (if desired)
				}

				oOptions[i].remove();

				if (arguments[2] == '*' && i > 0) { continue; }					// cycle all the <option>'s if we're instructed to do so!
				bCallback=true; break;								// break to perform any callback and return true
			}
			if (! bCallback) { return false; }							// return fail if not
			break;											// proceed to perform any callback and return true




		   // OVERVIEW		Replaces the <option>'s text and value in the <select>|<optgroup> matching passed criteria
		   // SYNTAX		if (Listbox('ReplaceOption',mListbox,sValue,sText,sReplace='',sCheck='selected',mOptgroup='',nPrompt=0,bSort=false,sDirection='ascend',nOffset=0,bSensitive=false,mCallback='')) { ...yes... } else { ...no... }
		case "ReplaceOption":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mListbox	[string][object] The listbox/optgroup to manipulate							'Employees'
		   // 2: sValue		[string] The new value of the <option>; null retains existing value					'#1234'			[null]
		   // 3: sText		[string] The new text of the <option>; null retains existing value					'Dave'			[null]
		   // 4: sReplace	[string] The value 'sCheck' parameter uses in search; 'selected' below invalidates			'David'			['']
		   // 5: sCheck		[string] The aspect to check against					 [index, text, value, selected]	'text'			['selected']
		   // 6: mOptgroup	[string][number] The label or index value of the <optgroup> to process					'Newcomers'		['']
		   // 7: nPrompt	[number] Prompts prior to removing affected item(s)    [0=No Prompt, 1=Single Prompt, 2=Every Deletion]	true			[0]
		   // 8: bSort		[boolean] Toggles the <option>'s being sorted post-addition						true			[false]
		   // 9: sDirection	[string] The direction to sort							      [ascend, descend]	'descend'		['ascend']
		   // 10: nOffset	[number] The offset in the 'mListbox' (e.g. to skip sorting a 'Select...' 0-index)			1			[0]
		   // 11: bSensitive	[boolean] Toggles sorting to be case sensitive (true)/insensitive (false)				true			[false]
		   // 12: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
		   // *: Resets		[2x list] The string ids and values of the textboxes/listboxes to adjust/reset				Listbox('ReplaceOption',...,'State','FL','Zipcode','',...);
			// default value assignments
			if (arguments.length < 5) { arguments[4] = ''; }					// this value is not acknowledged by default since 'sCheck=selected' is also the default
			if (arguments.length < 6) { arguments[5] = 'selected'; }
			if (arguments.length < 7) { arguments[6] = ''; }
			if (arguments.length < 8) { arguments[7] = 0; }
			if (arguments.length < 9) { arguments[8] = false; }
			if (arguments.length < 10) { arguments[9] = 'ascend'; }
			if (arguments.length < 11) { arguments[10] = 0; }
			if (arguments.length < 12) { arguments[11] = false; }

			// process an index value, if passed
			var nStart = (arguments[5] == 'index') ? parseInt(arguments[4]) : 0;

			// lets find and replace the <option>
			for (var i=nStart; i<oOptions.length; i++) {
				if (arguments[5] == 'text' && oOptions[i].text != arguments[4]) { continue; }
				if (arguments[5] == 'value' && oOptions[i].value != arguments[4]) { continue; }
				if (arguments[5] == 'selected' && i != oListbox.selectedIndex) { continue; }
				// if we've made it here then none of the matches above occurred, so...

				if (arguments[7]) {
					if (! confirm('Are you sure you want to overwrite the "'+oOptions[i].text+'" entry?')) { return 0; }	// if the user clicked cancel, then exit this function
					if (arguments[7] == 1 || arguments[7] == true) {arguments[7] = false;}	// otherwise, turn off the prompt so it doesn't keep asking to delete (if desired)
				}

				if (arguments[2] != null) { oOptions[i].value = arguments[2]; }			// if we do NOT need to retain the existing value...
				if (arguments[3] != null) { oOptions[i].text = arguments[3]; }			// ditto for text
				if (arguments[8]) { Listbox('SortOptions',oListbox,arguments[9],arguments[11],arguments[10]); }			//   sorts entire <UL> list after user adds a new entry (if specified)

				bCallback=true; break;								// break to perform any callback and return true
			}

			if (! bCallback) { return false; }							// return fail if not
			nOffset = 13;										// set the offset incase any passed objects need resetting
			break;											// proceed to perform any callback and return true




		   // OVERVIEW		Uses two <select> objects (one to select [size=1], one to manipulate [size>1]) to adjust the order of the <option>'s
		   // SYNTAX		if (Listbox('OrderOption',mListbox,mListing,sDirection,sType='incremental',mCallback='')) { ...yes... } else { ...no... }
		case "OrderOption":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mListbox	[string][object] The listbox/optgroup used as a selector						'Employees'
		   // 2: mListing	[string][object] The listbox/optgroup used to manipulate						'Access'
		   // 3: sDirection	[string] The direction to move							 [left, right up, down]	'up'
// LEFT OFF -	   // 4: sType		[string] The type of movement to make						    [incremental, full]	'full'			['incremental']
		   // 4: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			var oListing = (typeof arguments[2] === "object") ? arguments[2] : document.getElementById(arguments[2]);
			var sText = oListbox.options[oListbox.selectedIndex].text;
			var sValue = oListbox.value;
			var nLine = 0;
			var nPosition = 0;
			var sDirection = '';
			var bFound = false;
			var sItems = new Array;
			var sIDs = new Array;

			// do some sanity checks and store the corresponding <option> index
			if (oListbox.selectedIndex == -1) { return false; }					// exit if there's nothing selected

			// lets find which LISTING <option> line contains the SELECTED <option> to move
			for (nLine=0; nLine<oListing.options.length; nLine++) {					// cycle each line
				sItems = oListing.options[nLine].text.split(',');				// turn the comma separated string into an array
				for (nPosition=0; nPosition<sItems.length; nPosition++) {			// cycle that list to check if the SELECTED <option> is on that line
					if (sItems[nPosition] == sText) {					// if it is, then...
						bFound = true;							//   indicate we've found it
						break;								//   exit from the inner 'for' loop
					}
				}
				if (bFound) {									// if we've found what we're looking for, then...
					sIDs = oListing.options[nLine].value.split(',');			//   store all the ID's on the line into an array as well
					break;									//   exit from the outter 'for' loop
				}
			}

			// do a follow-up sanity check
			if (! bFound) { return false; }								// exit if there's nothing selected

			// determine what move we need to make
// UPDATED 2025/06/07
//			if (arguments[3] == 'up') {
			if (arguments[3] == 'left') {
				if (nLine == 0 && oListing.options[nLine].text.indexOf(',') == -1) { return false; }				// exit if the selection is the first and only on the line
				sDirection = (nPosition == 0) ? 'up' : 'left';									// otherwise determine if we need to go up a line or to the left on the same line
// UPDATED 2025/06/07
//			} else {
			} else if (arguments[3] == 'right') {
				if (nLine == oListing.options.length-1 && oListing.options[nLine].text.indexOf(',') == -1) { return false; }	// exit if the selection is the last and only on the last line
				sDirection = (nPosition == sItems.length-1) ? 'down' : 'right';							// otherwise determine if we need to go down a line or to the right on the same line
// ADDED 2025/06/07
			} else if (arguments[3] == 'up') {
				if (nLine == 0 && oListing.options[nLine].text.indexOf(',') == -1) { return false; }				// exit if the selection is the first and only on the line
				sDirection = 'up';
			} else if (arguments[3] == 'down') {
				if (nLine == oListing.options.length-1 && oListing.options[nLine].text.indexOf(',') == -1) { return false; }	// exit if the selection is the last and only on the last line
				sDirection = 'down';
			}

			// implement the move in the listing
			if (sDirection == 'up') {								// if we need to move the first/only item up to the prior line, then...
				if (oListing.options[nLine].text.indexOf(',') == -1) {				//   if this is the ONLY item on the line, then...
					oListing.options[nLine-1].text += ','+sText;				//     add the text to the end of the prior line
					oListing.options[nLine-1].value += ','+sValue;				//     add the value to the end of the prior line
				} else {									//   otherwise it is the FIRST item on the line, so...
					var oOption = document.createElement('option');				//     create a new <option> to move this item into
					oOption.text = sText;							//     add the text to it
					oOption.value = sValue;							//     add the value to it
					oListing.insertBefore(oOption, oListing.options[nLine]);		//     insert the new <option> before the current position of the item
					nLine++;								//     increase the value by 1 so the trailing code works correctly
				}
				sText = '';									//   reset these values
				sValue = '';
				var nStart = (arguments[3] == 'up') ? 0 : 1;					//   store where we need to start depending on if we're moving left or up
				var nOffset = 1;								//   set the offset to establish when we've iterated to the last item in the list
				if (sItems[sItems.length-1] == oListbox.options[oListbox.selectedIndex].text) { nOffset++; }			// if the <option> to move is the last one in the list, then adjust the offset so the prior entry is seen as the last one and won't put a trailing comma
				for (var i=nStart; i<sItems.length; i++) {					//   cycle the remaining items (after the one that just got moved) to reconstruct the <option> text & value
					if (arguments[3] == 'up' && sItems[i] == oListbox.options[oListbox.selectedIndex].text) { continue; }	// if we are moving up and not left -AND- we matched the <option> we're moving, then skip adding it to the list
// UPDATED 2025/06/07
//					sText += (i < sItems.length-1) ? sItems[i]+',' : sItems[i];
//					sValue += (i < sItems.length-1) ? sIDs[i]+',' : sIDs[i];
					sText += (i < sItems.length-nOffset) ? sItems[i]+',' : sItems[i];
					sValue += (i < sItems.length-nOffset) ? sIDs[i]+',' : sIDs[i];
				}
			} else if (sDirection == 'down') {							// if we need to move the last/only item down to the next line, then...
				if (oListing.options[nLine].text.indexOf(',') == -1) {				//   if this is the ONLY item on the line, then...
					oListing.options[nLine+1].text = sText+','+oListing.options[nLine+1].text;   // add the text to the beginning of the next line
					oListing.options[nLine+1].value = sValue+','+oListing.options[nLine+1].value;// add the value to the beginning of the next line
				} else {									//   otherwise it is the LAST item on the line, so...
					var oOption = document.createElement('option');				//     create a new <option> to move this item into
					oOption.text = sText;							//     add the text to it
					oOption.value = sValue;							//     add the value to it
					oListing.insertBefore(oOption, oListing.options[nLine+1]);		//     insert the new <option> before the current position of the item
				}
				sText = '';									//   reset these values
				sValue = '';
				var nLength = (arguments[3] == 'down') ? sItems.length : sItems.length-1;	//   store the length we need to traverse depending on if we're moving right or down
				var nOffset = 1;								//   set the offset to establish when we've iterated to the last item in the list
				if (sItems[nLength-1] == oListbox.options[oListbox.selectedIndex].text) { nOffset++; }				// if the <option> to move is the last one in the list, then adjust the offset so the prior entry is seen as the last one and won't put a trailing comma
// UPDATED 2025/06/07
//				for (var i=0; i<sItems.length-1; i++) {						//   cycle the remaining items (after the one that just got moved) to reconstruct the <option> text & value
				for (var i=0; i<nLength; i++) {						//   cycle the remaining items (after the one that just got moved) to reconstruct the <option> text & value
					if (arguments[3] == 'down' && sItems[i] == oListbox.options[oListbox.selectedIndex].text) { continue; }	// if we are moving down and not right -AND- we matched the <option> we're moving, then skip adding it to the list
// UPDATED 2025/06/07
//					sText += (i < sItems.length-2) ? sItems[i]+',' : sItems[i];
//					sValue += (i < sItems.length-2) ? sIDs[i]+',' : sIDs[i];
					sText += (i < nLength-nOffset) ? sItems[i]+',' : sItems[i];
					sValue += (i < nLength-nOffset) ? sIDs[i]+',' : sIDs[i];
				}
			} else if (sDirection == 'left') {							// if we need to move the item to the left, then...
				sText = '';									//   reset these values
				sValue = '';
				for (var i=0; i<nPosition-1; i++) {						//   reconstruct the <option> text & value by first cycling up to the item PRIOR to the one to move
					sText += sItems[i]+',';
					sValue += sIDs[i]+',';
				}
				sText += sItems[nPosition]+','+sItems[nPosition-1];				//   add in the one to move and the one that preceeded it
				sValue += sIDs[nPosition]+','+sIDs[nPosition-1];
				for (var i=nPosition+1; i<sItems.length; i++) {					//   continue reconstruction by now cycling the remaining items AFTER the one to move
					sText += ','+sItems[i];
					sValue += ','+sIDs[i];
				}
			} else if (sDirection == 'right') {							// if we need to move the item to the right, then...
				sText = '';									//   reset these values
				sValue = '';
				for (var i=0; i<nPosition; i++) {						//   reconstruct the <option> text & value by first cycling up to the item to move
					sText += sItems[i]+',';
					sValue += sIDs[i]+',';
				}
				sText += sItems[nPosition+1]+','+sItems[nPosition];				//   add in the one that followed the one to move and that one
				sValue += sIDs[nPosition+1]+','+sIDs[nPosition];
				for (var i=nPosition+2; i<sItems.length; i++) {					//   continue reconstruction by now cycling the remaining items AFTER the one to move
					sText += ','+sItems[i];
					sValue += ','+sIDs[i];
				}
			}

			oListing.options[nLine].text = sText;							// update the line with the adjusted content (less the item that was moved)
			oListing.options[nLine].value = sValue;

			if (oListing.options[nLine].text == '') { oListing.options[nLine].remove(); }		// if the adjusted line is now blank, then we need to delete it
			break;											// proceed to perform any callback and return true




		   // OVERVIEW		Copies the <option>'s in one <select>|<optgroup> to another, matching passed criteria
		   // SYNTAX		if (Listbox('CopyOptions',mListbox,mTarget,mOptgroup='',nOffset=0,nRetain=0,bClasses=true,mCallback='')) { ...yes... } else { ...no... }
		case "CopyOptions":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mListbox	[string][object] The source listbox to copy								'Employees'
		   // 2: mTarget	[string][object] The target listbox receiving the copy							'Backup'
		   // 3: mOptgroup	[string][number] The label or index value of a specific <optgroup> to process				'Newcomers'		['']
		   // 4: nOffset	[number] The offset in the 'mListbox' (e.g. skip copying a 'Select...' and/or 'New...')			1			[0]
		   // 5: nRetain	[number] Retain existing <option>'s in 'mTarget' (<optgroup>) (e.g. retain 'Select...'); Accepts '*'	1			[0]
		   // 6: bClasses	[boolean] Whether the classes should be copied too							false			[true]
		   // 7: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			var oTarget = (typeof arguments[2] === "object") ? arguments[2] : document.getElementById(arguments[2]);
			var nOffset = (arguments.length < 5) ? 0 : arguments[4];
			var nRetain = (arguments.length < 6) ? 0 : arguments[5];
			var bClasses = (arguments.length < 7) ? true : arguments[6];
			var mOptGroup = mOptgroup;								// store the original value since it MAY get adjusted below

			// remove any existing <option>'s in the target
			if (mOptGroup != '' && ! Listbox('CheckOptgroup',oTarget,mOptGroup)) {mOptGroup = '';}	// if the <optgroup> doesn't already exist in the target listbox (e.g. blank from form initialization), then don't check for it (since it will throw an error popup)
			if (nRetain != '*' && mOptGroup != '')							// if we need to remove any existing <option>'s in the target listbox <optgroup>, then...
				{ Listbox('RemoveOption',oTarget,'*','',mOptGroup,0,nRetain); }			//   remove the <option>'s in the <optgroup>
			else if (parseInt(nRetain) > 0 && mOptgroup == '')					// otherwise set the options.length to remove EVERYTHING (except the nRetain count)
				{ oTarget.options.length = nRetain; }						// NOTE: oOptions is set at the top of this script

			// copy the contents
			if (mOptgroup == '') {									// if no <optgroup> was passed, then...
				if (oTarget.options.length < nRetain) { nRetain = oTarget.options.length; }	//   if we have no prior contents, then adjust the value to prevent incorrect math in the 'pruning' block below; WARNING: this MUST come above the append call on the next line
				oTarget.innerHTML += oListbox.innerHTML;					//   APPEND(!!!) the entire contents of the source listbox to the target (utilizing nRetain)
				oOptions = oTarget.options;							//   now store all the updated <option>'s
			} else {										// otherwise an <optgroup> was passed, so...
				if (mOptGroup != '') {								//   NOTE: if we do go in here, we can be assured that the <optgroup> does exist in oTarget
					if (isNaN(mOptGroup)) {							//   IF we were given a label, then lets find it using that!
						for (var i=0; i<oTarget.getElementsByTagName("optgroup").length; i++) {
							if (oTarget.getElementsByTagName("optgroup")[i].label == mOptGroup)
								{ var oOptGroup = oTarget.getElementsByTagName("optgroup")[i];	break; }
						}
					} else { var oOptGroup = oTarget.getElementsByTagName("optgroup")[parseInt(mOptGroup)]; }		// ELSE we were passed an index value, so...
					oOptGroup.innerHTML += oOptgroup.innerHTML;				//     APPEND(!!!) the entire contents of the source listbox to the target (utilizing nRetain)
				} else if (mOptGroup == '') {							//   OTHERWISE we were passed an <optgroup> but it does not yet exist in oTarget, so...
					oOptGroup = document.createElement('optgroup');				//     create a new <optgroup> in the target listbox
					oOptGroup.label = oOptgroup.label;					//     copy the <optgroup> label
					oOptGroup.innerHTML = oOptgroup.innerHTML;				//     copy the contents
					oTarget.appendChild(oOptGroup);						//     and add it to the target listbox
					nRetain = 0;								//     since we have no prior contents, zero out this value to prevent incorrect math in the 'pruning' block below
				}
				oOptions = oOptGroup.getElementsByTagName('option');				//   now store all the (updated) <option>'s
			}

			// now lets prune depending on the nRetain and nOffset values
			if ((parseInt(nRetain) > 0 || nOffset > 0)) {						// if nRetain is not 0 or '*'  -OR-  nOffset has a non-zero value, then...
				for (let i=oOptions.length; i>0; i--) {						//   cycle each of the target's <option>...	NOTE: this can be for a <listbox> or <optgroup>
					if (i > nRetain && i <= (nRetain + nOffset)) {oOptions[i-1].remove();}  //   remove the source listbox <option>'s that were meant to be offset (utilizing nOffset), while retaining the <option>'s in the target listbox (utilizing nRetain)
					if (! bClasses) { oTarget.options[i].className = ''; }			//   remove any classes if desired
				}
			}
			break;											// proceed to perform any callback and return true




		   // OVERVIEW		Counts the <option>'s in the <select>|<optgroup> matching passed criteria
		   // SYNTAX		if (Listbox('CountOptions',mListbox,mOptgroup='',sType='total')) { ...yes... } else { ...no... }
		case "CountOptions":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mListbox	[string][object] The listbox/optgroup to manipulate							'Employees'
		   // 2: mOptgroup	[string][number] The label or index value of the <optgroup> to process					'Newcomers'		['']
		   // 3: sType		[string] The type of count to perform					  [selected, unselected, total]	'selected'		['total']
			// default value assignments
			if (arguments.length < 3) { arguments[2] = ''; }					// this value is not acknowledged by default since 'sCheck=selected' is also the default
			if (arguments.length < 4) { arguments[3] = 'total'; }

			var nCount = 0;
			var bSelected = (arguments[3] == 'selected') ? true : false;

			// lets return the total number of selected <options>
			if (arguments[3] == 'total') { return oOptions.length; }

			for (var i=0; i<oOptions.length; i++)
				{ nCount = (oOptions[i].selected == bSelected) ? nCount++ : nCount; }		// if the <option> is [un]selected, then increase the count
			return nCount;
			break;




// REMOVED 2025/08/07 - duplicate of CheckOption
		   // OVERVIEW		Checks if a passed value is in the <select>|<optgroup> matching passed criteria
		   // SYNTAX		if (Listbox('InOptions',mListbox,sValue,sCheck='value',mOptgroup='') { ...yes... } else { ...no... }
		case "InOptions":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mListbox	[string][object] The listbox/optgroup to manipulate							'Employees'
		   // 2: sValue		[string] The value 'sCheck' parameter uses in search							'Dave'
		   // 3: sCheck		[string] The aspect to check against							  [text, value]	'text'			['value']
		   // 4: mOptgroup	[string][number] The label or index value of the <optgroup> to process					'Newcomers'		['']
alert('DEPRECATED: use CheckOption instead');
return false;
			// default value assignments
//			if (arguments.length < 4) { arguments[3] = 'value'; }
//			if (arguments.length < 5) { arguments[4] = ''; }
//
//			for (var i=0; i<oOptions.length; i++) {
//				if (arguments[3] == 'text' && oOptions[i].text != arguments[2]) { continue; }
//				if (arguments[3] == 'value' && oOptions[i].value != arguments[2]) { continue; }
//
//				return true;
//			}
//			return false;
			break;




		   // OVERVIEW		Sorts the <option>'s in the <select>|<optgroup> matching passed criteria
		   // SYNTAX		if (Listbox('SortOptions',mListbox,mOptgroup='',sDirection='ascend',nOffset=0,bSensitive=false,mCallback='')) { ...yes... } else { ...no... }
		case "SortOptions":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mListbox	[string][object] The listbox/optgroup to manipulate							'Employees'
		   // 2: mOptgroup	[string][number] The label or index value of the <optgroup> to process					'Newcomers'		['']
		   // 3: sDirection	[string] The direction to sort							      [ascend, descend]	'descend'		['ascend']
		   // 4: nOffset	[number] The offset in the 'mListbox' (e.g. to skip sorting a 'Select...' 0-index)			1			[0]
		   // 5: bSensitive	[boolean] Toggles searching to be case sensitive (true)/insensitive (false)				true			[false]
		   // 6: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	[]
			// default value assignments
			if (arguments.length < 3) { arguments[2] = ''; }
			if (arguments.length < 4) { arguments[3] = 'ascend'; }
			if (arguments.length < 5) { arguments[4] = 0; }
			if (arguments.length < 6) { arguments[5] = false; }

			var s_Options = new Array();

			// transpose the values and text from the listbox to an array
			for (var i=0; i<oOptions.length; i++) {
				s_Options[i] = new Array(2);
				s_Options[i][0] = oOptions[i].text;
				s_Options[i][1] = oOptions[i].value;
			}
			if (arguments[5]) {									// if you want a case sensitive sort, then...
				if (arguments[3] == 'ascend') {
					s_Options.sort(function(a, b)						//   use the built-in sort function with custom comparison function
						{ return a[0] == b[0] ? 0 : (a[0] > b[0] ? 1 : -1); });		//   https://stackoverflow.com/questions/5435228/sort-an-array-with-arrays-in-it-by-string
				} else {									//   https://stackoverflow.com/questions/50415200/sort-an-array-of-arrays-in-javascript
					s_Options.sort(function(a, b)
						{ return a[0] == b[0] ? 0 : (a[0] > b[0] ? -1 : 1); });
				}
			} else {										// otherwise we want to sort case INsensitive, so lets lowercase before comparison
				if (arguments[3] == 'ascend') {
					s_Options.sort(function(a, b) {
						return a[0].toLowerCase() == b[0].toLowerCase() ? 0 : (a[0].toLowerCase() > b[0].toLowerCase() ? 1 : -1);
					});
				} else {									//   https://stackoverflow.com/questions/50415200/sort-an-array-of-arrays-in-javascript
					s_Options.sort(function(a, b) {
						return a[0].toLowerCase() == b[0].toLowerCase() ? 0 : (a[0].toLowerCase() > b[0].toLowerCase() ? -1 : 1);
					});
				}
			}
			for (var i=arguments[4]; i<s_Options.length; i++) {					// write the sorted values to the listbox
				oOptions[i].text = s_Options[i][0];
				oOptions[i].value = s_Options[i][1];
			}
			break;											// proceed to perform any callback and return true




		   // OVERVIEW		Checks if a passed <optgroup> is in the <select> matching passed criteria
		   // SYNTAX		if (Listbox('CheckOptgroup',mListbox,mOptgroup,mCallback='') { ...yes... } else { ...no... }
		case "CheckOptgroup":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mListbox	[string][object] The listbox/optgroup to manipulate							'Employees'
		   // 2: mOptgroup	[string][number] The label or index value of the <optgroup> to process					'Newcomers'		['']
		   // 3: mCallback	[string][function] The callback to execute upon success							"alert('done!');"	['']
			if (oOptgroup == '') { return false; }
			break;											// proceed to perform any callback and return true




		   // OVERVIEW		Adds/removes/toggles the <option>'s class in the <select>|<optgroup> matching passed criteria
		   // SYNTAX		if (Listbox('OptionClass',mListbox,sAction,sClass,sValue,sCheck='selected',mOptgroup='',mCallback='')) { ...yes... } else { ...no... }
		case "OptionClass":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mListbox	[string][object] The listbox to manipulate								'Employees'
		   // 2: sAction	[string] The action to take							  [add, delete, toggle]	'toggle'
		   // 3: sClass		[string] The class name(s) to add/delete/toggle to the <option>						'hired'
		   // 4: sValue		[string] The value 'sCheck' parameter uses in search; Accepts '*'; 'selected' below invalidates		'Dave'			['']
		   // 5: sCheck		[string] The aspect to check against; '*' invalidates			 [index, text, value, selected]	'value'			['selected']
		   // 6: mOptgroup	[string][number] The label or index value of the <optgroup> to process					'Newcomers'		['']
		   // 7: mCallback	[string][function] The callback to execute upon success							"alert('done!');"	['']
			// default value assignments
			if (arguments.length < 5) { arguments[4] = ''; }					// this value is not acknowledged by default since 'sCheck=selected' is also the default
			if (arguments.length < 6) { arguments[5] = 'selected'; }

			// update the value of 'sCheck' if requested so that checks below don't trigger incorrectly
			if (arguments[4] == '*') { arguments[5] = '*'; }

			// process an index value, if passed
			var nStart = (arguments[5] == 'index') ? parseInt(arguments[4]) : 0;

			// lets find and update the class
			for (var i=nStart; i<oOptions.length; i++) {
				if (arguments[5] == 'text' && oOptions[i].text != arguments[4]) { continue; }
				if (arguments[5] == 'value' && oOptions[i].value != arguments[4]) { continue; }
				if (arguments[5] == 'selected' && i != oListbox.selectedIndex) { continue; }
				// if we've made it here then none of the matches above occurred, or '*' was passed for 'sValue'

				// if we need to toggle, figure out if the class is present and adjust 'sAction' accordingly so the following code executes the correct action
				if (arguments[2] == 'toggle') { arguments[2] = (oOptions[i].className.search(arguments[3]) == -1) ? 'add' : 'delete'; }

				if (arguments[2] == 'add') { oOptions[i].className += ' '+arguments[3]; }
				else if (arguments[2] == 'delete') { oOptions[i].className = oOptions[i].className.replace(new RegExp(arguments[3],'g'), ''); }

				if (arguments[4] == '*' && i < oOptions.length) { continue; }			// cycle all the <option>'s if we're instructed to do so!
				bCallback=true; break;								// break to perform any callback and return true
			}
			if (! bCallback) { return false; }							// return fail if not
			break;											// proceed to perform any callback and return true
	}


	// reset the passed form objects to the values specified
	if (sAction == 'AddOption' || sAction == 'ReplaceOption') {
		for (var i=nOffset; i<arguments.length; i=i+2) {
			if (document.getElementById(arguments[i]).type == "text") {
				document.getElementById(arguments[i]).value = arguments[i+1];
			} else if (document.getElementById(arguments[i]).type == "select" || document.getElementById(arguments[i]).type == "select-one" || document.getElementById(arguments[i]).type == "select-multiple") {
				if (arguments[i+1] == -1) { document.getElementById(arguments[i]).selectedIndex = -1; continue; }		// if the user wants the combobox to not have any selections, then do so
				for (j=0; j<document.getElementById(arguments[i]).options.length; j++)						// otherwise, choose the selection desired
					{ if (document.getElementById(arguments[i]).options[j].value == arguments[i+1]) { document.getElementById(arguments[i]).selectedIndex = j; break; } }
			}
		}
	}


	// Perform any passed callback
	if (typeof(mCallback) === 'function') { mCallback(); }							// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
	else if (typeof(mCallback) === 'string' && mCallback != '') { eval(mCallback); }			// using this line, the value can be passed as: "alert('hello world');"

	return true;
}










//  --- DEPRECATED/LEGACY ---


var aLastSelected = new Array();		// this is used with the listCount


function ListSort(Listbox,intSensitive) {														// Use: Listbox('SortOptions')
// Sorts the entries in order (alphabatizes) in a passed ListBox
// Listbox	The listbox to add the entry to - can be the name as a string, or the object itself
// intSensitive	whether or not the list is to be sorted case sensitive (1) or case insensitive (0)

alert("ListSort() is deprecated; updated your code.");
return false;

	var i,sArray = new Array();
	var objListBox = (typeof Listbox === "object") ? Listbox : document.getElementById(Listbox);

	for (i=0; i<objListBox.options.length; i++) { 							// this for loop copies all the names from the listbox to the array
		sArray[i] = new Array(2);
		sArray[i][0] = objListBox.options[i].text;
		sArray[i][1] = objListBox.options[i].value;
	}
	if (intSensitive) {										// if you want a case sensitive sort, then...
		sArray.sort();										// use the built-in sort function
		for (i=1; i<sArray.length; i++) {							// write the sorted values to the listbox
			objListBox.options[i].text = sArray[i][0];
			objListBox.options[i].value = sArray[i][1];
		}
	} else {											// otherwise we want to sort case INsensitive, so lets use a custom method to do so
		var j=0,k,tArray = new Array();
		for (i=0; i<sArray.length; i++) {							// cycles once through the initially created array
			for (j=0; j<tArray.length; j++) {						// for each pass of that array, go entirely through the temp array that stores the indices in alphabetical order to rearrange if necessary
				if (sArray[i][0].toLowerCase() < sArray[tArray[j]][0].toLowerCase()){	// if the cycled "parent" array value is less than any other in the temp array, then...
					tArray.length++;
					for (k=(tArray.length-1); k>=j; k--) {tArray[k] = tArray[k-1];}	// move all the indices from our current point in the array to the right 1 spot to let the last line of the "for" loop write the missing value in the correct spot
					break;
				}
			}
			tArray[j] = i;									// set the current index of the temp array to the current value of the "parent" array
		}
		for (i=0; i<tArray.length; i++) {							// write the sorted values to the listbox
			objListBox.options[i].text = sArray[tArray[i]][0];
			objListBox.options[i].value = sArray[tArray[i]][1];
		}
	}
}


function listCount(Listbox,intCount,strMinMax,mOptgroup='') {												// Use: Listbox('SortOptions')
// validates the number of selected items in the list is maintained at the passed values.
// NOTE:	You must call this function from the onClick event for the listbox object itself
// Listbox	The listbox to add the entry to - can be the name as a string, or the object itself
// intCount	The number of selections to limit or mandate (depending on following value)					LEFT OFF - this needs to be the last parameter
// strMinMax	Which count do you want to check for: min(imum to select), max(imum to select)					LEFT OFF - add a 'options' value here that will return how many options are in the select/optgroup
// mOptgroup	The label value (or index number within the Listbox) of an <optgroup> to check
// https://stackoverflow.com/questions/3487263/how-to-use-onclick-or-onselect-on-option-tag-in-a-jsp-page
// https://bytes.com/topic/javascript/answers/883033-selectedindex-value-last-selected-item-select-box
// https://stackoverflow.com/questions/5767325/how-can-i-remove-a-specific-item-from-an-array

alert("listCount() is deprecated; updated your code.");
return false;

	var Elm = (typeof Listbox === "object") ? Listbox : document.getElementById(Listbox);
	var oOptgroup = '';
	var cnt = 0;								// stores the total count of selected <option>'s
	var last = -1;								// stores the <option> index that was last clicked in the list
	var found = 0;								// indicates if the iterated <option> was found in the array (as in it's already acknowledged for being a selected choice)
	var del = -1;								// indicates the array index to delete if the <option> was de-selected (either by the user or because too many were selected)

	// if we need to obtain a count of options in the <select>/<optgroup>, then...
	if (strMinMax == 'options') {
		if (mOptgroup != '') {						// if we need to check an <optgroup>, then...
			if (isNaN(mOptgroup)) {									// IF we were given a label, then lets find it using that!
				for (i=0; i<Elm.getElementsByTagName("optgroup").length; i++) {
					if (Elm.getElementsByTagName("optgroup")[i].label == mOptgroup) {
						oOptgroup = Elm.getElementsByTagName("optgroup")[i];
						break;
					}
				}
				if (oOptgroup == '')
					{ alert("There is not an &lt;optgroup&gt; with that label."); return 0; }
			} else {										// ELSE we were passed an index value, so...
				if (! Elm.getElementsByTagName("optgroup")[parseInt(mOptgroup)])	//   IF that optgroup does NOT exist, then...
					{ alert("There is not an &lt;optgroup&gt; with that label."); return 0; }
				oOptgroup = Elm.getElementsByTagName("optgroup")[parseInt(mOptgroup)];
			}
			return oOptgroup.getElementsByTagName("option").length;
		} else { return Elm.options.length; }				// otherwise we just need to count of options within the <select> itself, so...
	}

	// store each selected
	for (i=0; i<Elm.options.length; i++) {					// cycle through all the listbox <option>'s
		found = 0;							// (re)set the values
		del = -1;

		if (Elm.options[i].selected == true) { cnt++; }			// if the item is selected, then increase the count (here so no matter what happens below, selected <option>'s will be counted)

		for (j=0; j<aLastSelected.length; j++) {			// cycle through the array storing the 'previous' indices of selected <option>'s

			if (aLastSelected[j] == i) {				//   if the iterated array index equals the iterated <option> index, then...
				if (Elm.options[i].selected == true) {		//      if the <option> is selected, then...
					found = 1;				//         indicate that the index is already stored in the array
				} else {					//      otherwise it is de-selected by the user, so...
					found = -1;				//         indicate that the array value needs to be removed
					del = j;				//         store the array index that needs to be deleted
				}
				break;						//      break from the 'for' loop for efficiency
			}
		}
		if (found == 0 && Elm.options[i].selected == true) {		// if the selected <option> is NOT currently in the array, then...
			aLastSelected.push(i);					//   add it to the array
			last = i;						//   store its index value of the last <option> clicked
			break;							//   break from the 'for' loop for efficiency
		} else if (found == -1 && Elm.options[i].selected == false) {	// if the iterated <option> has been de-selected, then...
			aLastSelected.splice(del,1);				//   remove a de-selected item from the array	NOTE: we use 'splice()' to avoid having holes in the array (which 'delete' and other methods leave in the array)
			last = i;						//   store its index value of the last <option> clicked
			break;							//   break from the 'for' loop for efficiency
		}
	}
	for (j=i+1; j<Elm.options.length; j++)					// cycle through all the -REMAINING- listbox <option>'s (since the above 'for' stops after finding the <option> just clicked)
		{ if (Elm.options[j].selected == true) {cnt++;} }		// if the <option> is selected, then increase the count

	// check the count against passed values
	if (strMinMax == 'min' && cnt < intCount) {				// if the user needs a minimum, then alert them that they need more selections
		alert("You must select at least "+intCount+" items from the list.");
		return 0;
	} else if (strMinMax == 'max' && cnt > intCount) {			// otherwise if they have selected more than allowed, then alert them, de-select the just-selected <option>, and remove it from the array
		alert("You can only select up to "+intCount+" items from the list.");
		Elm.options[last].selected = false;
		aLastSelected.splice(del,1);
		return 0;
	}
	return 1;
}


function selListbox(Listbox,strValue) {															// Use: Listbox('SelectOption')
// sets the specified value in a listbox to 'selected' (if found)
// Listbox	The listbox to add the entry to - can be the name as a string, or the object itself
// strValue	[string]	the listboxes' value to select from the list; this does NOT match its' text

alert("selListbox() is deprecated; updated your code.");
return false;

	var Elm = (typeof Listbox === "object") ? Listbox : document.getElementById(Listbox);

	for (i=0; i<Elm.options.length; i++)
		{ if (Elm.options[i].value == strValue) {Elm.selectedIndex = i; return 1;} }
	return 0;
}


function selListbox2(Listbox,strValue) {														// Use: Listbox('SelectOption')
// sets multiple specified values in a listbox to 'selected' (if found)
// Listbox	The listbox to add the entry to - can be the name as a string, or the object itself
// strValue	[string]	the listboxes' value to select from the list; this does NOT match its' text

alert("selListbox2() is deprecated; updated your code.");
return false;

	var Elm = (typeof Listbox === "object") ? Listbox : document.getElementById(Listbox);

	for (i=0; i<Elm.options.length; i++)
		{ if (Elm.options[i].value == strValue) {Elm.options[i].selected = true; return 1;} }

	return 0;
}


function ListExists(Listbox,strValue,strText,intSensitive,intSkipSelected) {										// Use: Listbox('CheckOption')
// returns true/false based on if the value -OR text is already in the list. Pass null value to not check value or text.
// ListBox	The listbox you want to add the entry to - can be the name as a string, or the object itself
// strValue	The value to search for in the list (blank disables this search)
// strText	The text to search for in the list (blank disables this search
// intSensitive	whether or not the list is to be searched case sensitive (1) or case insensitive (0)
// intSkipSelected	whether or not the search should include the item currently selected in the list

alert("ListExists() is deprecated; updated your code.");
return false;

	var objListBox = (typeof Listbox === "object") ? Listbox : document.getElementById(Listbox);

	for (var i=0; i<objListBox.options.length; i++) {
		if (intSkipSelected && i == objListBox.selectedIndex) { continue; }

		if (strValue != '' && intSensitive && objListBox.options[i].value == strValue) { return true; }
		if (strValue != '' && ! intSensitive && objListBox.options[i].value.toLowerCase() == strValue.toLowerCase()) { return true; }

		if (strText != '' && intSensitive && objListBox.options[i].text == strText) { return true; }
		if (strText != '' && ! intSensitive && objListBox.options[i].text.toLowerCase() == strText.toLowerCase()) { return true; }
	}
	return false;
}


function Add2List(Listbox,strOptVal,strOptTxt,intNoDoubles=true,intSortList=false,intSensitive=false,intSelected=false,mOptgroup='',sClass='') {	// Use: Listbox('AddOption')
// This function adds the passed text to a ListBox object.  If any text boxes are appended to the arguments of a function
// call, there contents will be cleared.
// Listbox	The listbox to add the entry to - can be the name as a string, or the object itself
// strOptVal	This will give a "index" value to the entry being added.  A '-1' will autoincrement.  This can also be a string.		LEFT OFF - change the '-1' value to a less-likely used value like {AUTO}
// strOptTxt	The text of the entry you want displayed in the listbox.
//														LEFT OFF - add in a class name to add here
// intNoDoubles	Prevents the addition of duplicate entries (a value of 1 will prevent).
// intSortList	Allows the listbox to resort its entries in alphabetical order (a value of 1 will sort).
// intSensitive	Whether or not the list is to be sorted case sensitive (1) or case insensitive (0)
// intSelected	If the item just added needs to be selected in the listing
// mOptgroup	The label value (or index number within the Listbox) of an <optgroup> to add the <option> into; creates the <optgroup> if it doesn't exist
// object list	The values of the textboxes or listboxes appended to the end of the parameters list will have their value
//		changed (to the next value - i+1) after the entry has be updated/saved.  See the examples below to get an
//		idea of appended parameters:
//			Add2List('listbox name','value','text',1,1,1,'greeting','')					this will reset the username textbox to blank
//			Add2List('listbox name','value','text',1,1,1,'greeting','Hello')				this will reset the username textbox to 'Hello'
//	var newEntry = new Option();

alert("Add2List() is deprecated; updated your code.");
return false;

	var newEntry = document.createElement('option');
	var objListBox = (typeof Listbox === "object") ? Listbox : document.getElementById(Listbox);
	var oOptgroup = '';
	var i;

// LEFT OOF - add this check for all functions; and apply defaults to the passed ones as we did above in the declaration
	if (arguments.length < 3) {
		alert("ERROR: Add2List() was called without the sufficent number of parameters.");
		return false;
	}

	if (intNoDoubles == 1) {									// code block used to prevent the addition of duplicate "records"
		for (i=0; i<objListBox.options.length; i++)
			{ if (objListBox.options[i].text == strOptTxt) { alert('You already have the entry "' + objListBox.options[i].text + '" added to the list.'); return 0; } }
	}

	if (mOptgroup != '') {
		if (isNaN(mOptgroup)) {									// IF we were given a label, then lets find it using that!
			for (i=0; i<objListBox.getElementsByTagName("optgroup").length; i++) {
				if (objListBox.getElementsByTagName("optgroup")[i].label == mOptgroup) {
					oOptgroup = objListBox.getElementsByTagName("optgroup")[i];
					break;
				}
			}
		} else {										// ELSE we were passed an index value, so...
			if (! objListBox.getElementsByTagName("optgroup")[parseInt(mOptgroup)]) {	//   IF that optgroup does NOT exist, then...
				oOptgroup = document.createElement('optgroup');				//      create it
				objListBox.appendChild(oOptgroup);					//      add it to the <ul>
			} else { oOptgroup = objListBox.getElementsByTagName("optgroup")[parseInt(mOptgroup)]; }
		}
	}

	if (strOptTxt != "") {										// IF strOptTxt contains an actual value, then...
		if (strOptVal == -1)									// IF the user wants an autoincrement, then..
			{ newEntry.value = objListBox.options.length; }					//   add the next highest number
		else											// ELSE IF the user specified a value...
			{ newEntry.value = strOptVal; }							//   assign it to the entry
		newEntry.text = strOptTxt;								// set the listbox entry's display text
		newEntry.selected = intSelected;							// if this item needs to be selected in the list, then do so!
		if (sClass != '') { newEntry.className = sClass; }					// if the item needs to have a class(es) added, then do so!
		if (typeof oOptgroup == 'string' ) {							// IF oOptgroup is the default value, then we have no <optgroup> to add to, so...
			objListBox.appendChild(newEntry);						//   add the new entry to the <ul>
			if (intSortList == 1) { ListSort(objListBox,intSensitive); }			//   sorts entire <UL> list after user adds a new entry (if specified)
		} else {										// ELSE we do have an <optgroup> to add the <option> to, so do it!
			oOptgroup.appendChild(newEntry);
			if (intSortList == 1) { ListSort(oOptgroup,intSensitive); }			//   sorts only <OPTGROUP> list after user adds a new entry (if specified)
		}
		for (i=9; i<arguments.length; i=i+2) {							// resets the form objects to the values specified after the mandatory parameters to this function
			if (document.getElementById(arguments[i]).type == "text") {
				document.getElementById(arguments[i]).value = arguments[i+1];
			} else if (document.getElementById(arguments[i]).type == "select" || document.getElementById(arguments[i]).type == "select-one" || document.getElementById(arguments[i]).type == "select-multiple") {
				if (arguments[i+1] == -1) { document.getElementById(arguments[i]).selectedIndex = -1; continue; }	// if the user wants the combobox to not have any selections, then do so
				for (j=0; j<document.getElementById(arguments[i]).options.length; j++)					// otherwise, choose the selection desired
					{ if (document.getElementById(arguments[i]).options[j].value == arguments[i+1]) { document.getElementById(arguments[i]).selectedIndex = j; break; } }
			}
		}
		return 1;										// returns true if all works
	}
	return 0;											// returns fail if not
}


function ListReplace(Listbox,strNewVal,strNewTxt,strCheckAgainst,strOriginal,intSortList,intSensitive,intPrompt) {					// Use: Listbox('ReplaceOption')
// This function replaces the entry thats matched the information passed into the function.  If any textboxes or listboxes
// are appended to the arguments of this function call, there contents will be cleared or set to default values.
// Listbox	The listbox to add the entry to - can be the name as a string, or the object itself
// strNewVal	The new value to give the matched item in the list.  If it is left blank, the prior value will remain.
// strNewTxt	The new text to give the matched item in the list.  If it is left blank, the prior value will remain.
// strCheckAgainst   This is option identifies which listbox value you wish to check against.  Valid values: "text", "value".
// strOriginal  This is the ORIGINAL "value" (either 'text' or 'value') to find a matched item in the listbox that will be replaced.
// intSortList	Allows the listbox to resort its entries in alphabetical order (a value of 1 will sort).
// intSensitive	whether or not the list is to be sorted case sensitive (1) or case insensitive (0)
// intPrompt	This allows an overwrite prompt to appear for user confirmation before actually processing.
// object list	The values of the textboxes or listboxes appended to the end of the parameters list will have their value
//		changed (to the next value - i+1) after the entry has be updated/saved.  See the examples below to get an
//		idea of appended parameters:
//			ListReplace('listbox name','new value','new text','text','old text',1,1,1,'greeting','')	this will reset the username textbox to blank
//			ListReplace('listbox name','new value','new text','text','old text',1,1,1,'greeting','Hello')	this will reset the username textbox to 'Hello'

alert("ListReplace() is deprecated; updated your code.");
return false;

	var i, found = false, newEntry = new Option();
	var objListBox = (typeof Listbox === "object") ? Listbox : document.getElementById(Listbox);

	if (intPrompt == 1) {
		if (objListBox.options.selectedIndex == -1) { alert('You must select an entry from the listbox before you\ncan save any changes.'); return 0; }

		if (window.confirm('Are you sure you want to overwrite?') == false) { return 0; }
	}
	for (i=0; i<objListBox.options.length; i++) {
		if (strCheckAgainst == "value" && objListBox.options[i].value == strOriginal) { found = true; }				// we have found the listbox entry to replace!
		else if (strCheckAgainst == "text" && objListBox.options[i].text == strOriginal) { found = true; }			// we have found the listbox entry to replace!
		if (found == true) {
			if (strNewTxt != '') { objListBox.options[i].text = strNewTxt; }
			if (strNewVal != '') { objListBox.options[i].value = strNewVal; }
			if (intSortList == 1) { ListSort(objListBox,intSensitive); }			// sorts list after user adds a new entry (if specified)
			for (i=8; i<arguments.length; i=i+2) {						// resets the form objects to the values specified after the mandatory parameters to this function
				if (document.getElementById(arguments[i]).type == "text") {
					document.getElementById(arguments[i]).value = arguments[i+1];
				} else if (document.getElementById(arguments[i]).type == "select" || document.getElementById(arguments[i]).type == "select-one" || document.getElementById(arguments[i]).type == "select-multiple") {
					if (arguments[i+1] == -1) { document.getElementById(arguments[i]).selectedIndex = -1; continue; }		// if the user wants the combobox to not have any selections, then do so
					for (j=0; j<document.getElementById(arguments[i]).options.length; j++)				// otherwise, choose the selection desired
						{ if (document.getElementById(arguments[i]).options[j].value == arguments[i+1]) { document.getElementById(arguments[i]).selectedIndex = j; break; } }
				}
			}
			if (arguments.length > 8) { objListBox.selectedIndex = -1; }			// if the user decided to reset values of textboxes, unselect the entry in the listbox
			return 1;									// returns true if all works
		}
	}
	return 0;											// returns fail if not
}


function ListReplace2(Listbox,strOptVal,strOptTxt,intSortList,intSensitive,intPrompt) {									// Use: Listbox('ReplaceOption')
// this version makes it easier because it takes the item currently selected in the passed listbox and replaces it with
// the values passed.
// Listbox	The listbox to add the entry to - can be the name as a string, or the object itself
// strOptVal	This gives the entry a value.  If it is left blank, the prior value will remain.
// strOptTxt	This gives the entry a text value.  If it is left blank, the prior value will remain.
// intSortList	Allows the listbox to resort its entries in alphabetical order (a value of 1 will sort).
// intSensitive	whether or not the list is to be sorted case sensitive (1) or case insensitive (0)
// intPrompt	This allows an overwrite prompt to appear for user confirmation before actually processing.
// object list	The values of the textboxes or listboxes appended to the end of the parameters list will have their value
//		changed (to the next value - i+1) after the entry has be updated/saved.  See the examples below to get an
//		idea of appended parameters:
//			ListReplace2('listbox name','new value','',1,0,1,'greeting','')		this will reset the username textbox to blank
//			ListReplace2('listbox name','','new text',1,1,1,'greeting','Hello')	this will reset the username textbox to 'Hello'

alert("ListReplace2() is deprecated; updated your code.");
return false;

	var i, j;
	var objListBox = (typeof Listbox === "object") ? Listbox : document.getElementById(Listbox);

	if (intPrompt == 1) {
		if (objListBox.options.selectedIndex == -1) { alert('You must select an entry from the listbox before you\ncan save any changes.'); return 0; }
		if (window.confirm('Are you sure you want to overwrite?') == false) { return 0; }
	}
	if (strOptVal != '') { objListBox.options[objListBox.selectedIndex].value = strOptVal; }
	if (strOptTxt != '') { objListBox.options[objListBox.selectedIndex].text = strOptTxt; }
	if (intSortList == 1) { ListSort(objListBox,intSensitive); }					// sorts list after user adds a new entry (if specified)
	for (i=6; i<arguments.length; i=i+2) {								// resets the form objects to the values specified after the mandatory parameters to this function
		if (document.getElementById(arguments[i]).type == "text") {
			document.getElementById(arguments[i]).value = arguments[i+1];
		} else if (document.getElementById(arguments[i]).type == "select" || document.getElementById(arguments[i]).type == "select-one" || document.getElementById(arguments[i]).type == "select-multiple") {
			if (arguments[i+1] == -1) { document.getElementById(arguments[i]).selectedIndex = -1; continue; }		// if the user wants the combobox to not have any selections, then do so
			for (j=0; j<document.getElementById(arguments[i]).options.length; j++)		// otherwise, choose the selection desired
				{ if (document.getElementById(arguments[i]).options[j].value == arguments[i+1]) { document.getElementById(arguments[i]).selectedIndex = j; break; } }
		}
	}
}


function AdjustClass(mListbox,sAction,sCheck,sValue,sClass) {												// Use: Listbox('OptionClass')
// This function adds/removes the passed class to the desired <option>
// mListbox	[string/object] The listbox to manipulate
// sAction	[string] the action to take: add, delete, toggle
// sCheck	[string] what to check against: text, value, selected, *
// sValue	[string] the value of 'sCheck' to compare against; null if 'sCheck'=='selected'|'*'
// sClass	[string] the class name(s) to add to the <option>

alert("AdjustClass() is deprecated; updated your code.");
return false;

	var oListbox = (typeof mListbox === "object") ? mListbox : document.getElementById(mListbox);

	for (var i=0; i<oListbox.options.length; i++) {
		if (sCheck == 'text' && oListbox.options[i].text != sValue) { continue; }
		if (sCheck == 'value' && oListbox.options[i].value != sValue) { continue; }
		if (sCheck == 'selected' && i != oListbox.selectedIndex) { continue; }
		// if we've made it here then none of the matches above occurred, or '*' was passed for 'sCheck'

		// if we need to toggle, figure out if the class is present and adjust 'sAction' accordingly so the following code executes the correct action
		if (sAction == 'toggle') { sAction = (oListbox.options[i].className.search(sClass) == -1) ? 'add' : 'delete'; }

		if (sAction == 'add') { oListbox.options[i].className += ' '+sClass; }
		else if (sAction == 'delete') { oListbox.options[i].className = oListbox.options[i].className.replace(new RegExp(sClass,'g'), ''); }

		if (sCheck == '*' && i < oListbox.options.length) { continue; }				// cycle all the <option>'s if we're instructed to do so!
		return true;										// returns true if found
	}
	return false;											// returns fail if not
}


function ListRemove(Listbox,intPrompt) {														// Use: Listbox('RemoveOption')
// this function removes the selected option from the passed listbox.
// Listbox	The listbox to adjust - can be the name as a string, or the object itself
// intPrompt:	This allows an overwrite prompt to appear for user confirmation before actually processing.
// object list:	The values of the textboxes or listboxes appended to the end of the parameters list will have their value
//		changed (to the next value - i+1) after the entry has be updated/saved.  See the examples below to get an
//		idea of appended parameters:
//			ListRemove(document.form.listbox,1,'greeting','')			this will reset the username textbox to blank
//			ListRemove(document.form.listbox,1,'greeting','Hello')			this will reset the username textbox to 'Hello'

alert("ListRemove() is deprecated; updated your code.");
return false;

	var i,j,moveUp=0;
	var objListBox = (typeof Listbox === "object") ? Listbox : document.getElementById(Listbox);

	if (objListBox.selectedIndex == -1) { return 0; }						// error checking (if the user hasn't selected an item in the passed listbox)
	if (intPrompt) { if(window.confirm('Are you sure you want to delete the entry?') == false) { return 0; } }
	for (i=0; i<objListBox.options.length; i++) {							// cycles through all the entries in the list box
		if ((objListBox.options[i].selected) && (objListBox.options[i] != ""))			// finds the one the user selected
			{ moveUp = 1; }									// indicates we need to move all the entries aftwards, up in the list
		if ((moveUp == 1) && (i <= (objListBox.options.length - 2))) {
			objListBox.options[i].value = objListBox.options[i + 1].value;			// copies over the value from the above entry, down
			objListBox.options[i].text  = objListBox.options[i + 1].text;			// copies over the text from the above entry, down
		}
	}
	if (moveUp == 1) {										// if we successfully deleted an entry
		objListBox.options.length--;								// shorten the listbox by one
		for (i=2; i<arguments.length; i=i+2) {							// resets the form objects to the values specified after the mandatory parameters to this function
			if (document.getElementById(arguments[i]).type == "text") {
				document.getElementById(arguments[i]).value = arguments[i+1];
			} else if (document.getElementById(arguments[i]).type == "select" || document.getElementById(arguments[i]).type == "select-one" || document.getElementById(arguments[i]).type == "select-multiple") {
				if (arguments[i+1] == -1) { document.getElementById(arguments[i]).selectedIndex = -1; continue; }	// if the user wants the combobox to not have any selections, then do so
					for (j=0; j<document.getElementById(arguments[i]).options.length; j++)				// otherwise, choose the selection desired
						{ if (document.getElementById(arguments[i]).options[j].value == arguments[i+1]) { document.getElementById(arguments[i]).selectedIndex = j; break; } }
				}
			}
			return 1;									// return success
		}
	return 0;											// returns fail if not
}


function ListRemove2(Listbox,intPrompt,intIndex) {													// Use: Listbox('RemoveOption')
// this function removes the passed "index" entry from the listbox.  For example, if you know you want entry number 4
// removed from the listbox, then call this function as:  ListRemove2('listboxname', 4);
// Listbox	The listbox to add the entry to - can be the name as a string, or the object itself
// intIndex	specifies which line to remove
// intPrompt	whether or not the user should be prompt to confirm the deletion

alert("ListRemove2() is deprecated; updated your code.");
return false;

	var i;
	var objListBox = (typeof Listbox === "object") ? Listbox : document.getElementById(Listbox);

	if (intPrompt) { if(window.confirm('Are you sure you want to delete the entry?') == false) { return 0; } }
	for (i=intIndex; i<objListBox.options.length-1; i++) {						// cycles through all the entries in the list box
		objListBox.options[i].value = objListBox.options[i + 1].value;				// copies over the value from the above entry, down
		objListBox.options[i].text  = objListBox.options[i + 1].text;				// copies over the text from the above entry, down
	}
	objListBox.options.length--;									// shorten the listbox by one
}


function ListRemove3(Listbox,intPrompt,intKeepSelectedItem,intKeepItemValues) {										// Use: Listbox('RemoveOption')
// This function removes multiple items from a listbox.
// Listbox	The listbox to add the entry to - can be the name as a string, or the object itself
// intPrompt		Prompts the user if they are sure they want to remove the selected item(s) before continuing.
// intKeepSelectedItem	This value specifies what type of item to keep.  True = selected items in the list, Fasle = unselected items in the list.
// intKeepItemValues	setting true retains the <option value=""> values from the items left in the list.

alert("ListRemove3() is deprecated; updated your code.");
return false;

	var i;
	var aryNew = new Array();
	var objListBox = (typeof Listbox === "object") ? Listbox : document.getElementById(Listbox);

	if (intPrompt) { if(window.confirm('Are you sure you want to delete the entry?') == false) { return 0; } }
	aryNew.length = 0;
	for (i=0; i<objListBox.options.length; i++) {
		// if the item has a selected value that equals what the user specified  AND  the user specified not to keep the option values, then...
		if ((objListBox.options[i].selected == intKeepSelectedItem) && (intKeepItemValues == false)) {
			aryNew.length++;
			aryNew[aryNew.length-1] = objListBox.options[i].text;				// copies over the text from the current entry

		} else if ((objListBox.options[i].selected == intKeepSelectedItem) && (intKeepItemValues == true)) {
			aryNew.length += 2;
			aryNew[aryNew.length-2] = objListBox.options[i].value;				// copies over the value from the current entry

			aryNew[aryNew.length-1] = objListBox.options[i].text;				// copies over the text from the current entry
		}
	}
	objListBox.options.length = 0;									// resets the passed listbox entry count to 0
	for (i=0; i<aryNew.length; i++) {								// copies the "saved" entries back into the listbox
		if (intKeepItemValues == false)								// only copies the text portion
			{ Add2List(objListBox,aryNew[i],aryNew[i],1,-1,0); }
		else											// copies both the text and option values
			{ Add2List(objListBox,aryNew[i],aryNew[i+1],0,1,0); i++; }
	}
}


function ListRemove4(mListbox,iPrompt,sCheck='selected',sValue='') {											// Use: Listbox('RemoveOption')
// this function cycles through all the entries in the passed listbox and deletes ALL that match the passed criteria
// mListbox	[string/object] The listbox to manipulate
// iPrompt	[number] Prompts the user before removing the affected item(s): 0|false=NO, 1|true=YES (single prompt), 2=YES (for every deletion)
// sCheck	[string] The aspect to check against:  text, value, index, selected, unselected, *
// sValue	[string] The corresponding VALUE of the 'sCheck' parameter to search for; pass null if 'sCheck'==selected|unselected|*

alert("ListRemove4() is deprecated; updated your code.");
return false;

	var i, found=false;
	var oListBox = (typeof mListbox === "object") ? mListbox : document.getElementById(mListbox);

	for (i=0; i<oListBox.options.length; i++) {							// cycles through all the entries in the list box
		if (sCheck == "value" && oListBox.options[i].value == sValue) { found = true; }		// we have found the listbox entry to replace!
		else if (sCheck == "text" && oListBox.options[i].text == sValue) { found = true; }	// we have found the listbox entry to replace!
		if (found == true) {
			if (iPrompt) {
				if (! confirm('Are you sure you want to delete the entry?')) { return 0; }		// if the user clicked cancel, then exit this function
				else { iPrompt = false; }						// otherwise, turn off the prompt so it doesn't keep asking to delete
			}
			if (i != oListBox.options.length-1) {						// as long as we aren't on the last entry, then move the next entry in the list down one spot
				oListBox.options[i].value = oListBox.options[i + 1].value;
				oListBox.options[i].text  = oListBox.options[i + 1].text;
			}
		}
	}
	if (found == true) {oListBox.options.length--; return true;} else { return false; }		// shorten the listbox by one if the sought after entry was found
}


function ListPlacement(Listbox,strAdjustType) {														// Use: Listbox('MoveOption')
// This function allows the user to move a selected item either up or down in a list.

alert("ListPlacement() is deprecated; updated your code.");
return false;

	var i, j, priorValue;
	var objListBox = (typeof Listbox === "object") ? Listbox : document.getElementById(Listbox);

	if (strAdjustType == "up") {
		if ((objListBox.selectedIndex == -1) || (objListBox.selectedIndex == 0)) { return 0; }	// exit function if the user didn't select a server ip or he/she is trying to move up the first ip
		priorValue = objListBox.options[objListBox.selectedIndex-1].text;			// set the value of the listbox item thats one position up from the selected item, to the variable
		objListBox.options[objListBox.selectedIndex-1].text = objListBox.options[objListBox.selectedIndex].text;		// set the value of the listbox item up one position to the value of the selected item
		objListBox.options[objListBox.selectedIndex].text = priorValue;				// set the value of the selected listbox item to the priorValue variable
		objListBox.selectedIndex = objListBox.selectedIndex-1;					// this allows the highlight to follow the item being moved

	} else if (strAdjustType == "down") {
		if ((objListBox.selectedIndex == -1) || (objListBox.selectedIndex == objListBox.options.length-1)) { return 0; }	// exit function if the user didn't select a server ip or he/she is trying to move down the last ip
		priorValue = objListBox.options[objListBox.selectedIndex+1].text;			// same as above, but in reverse
		objListBox.options[objListBox.selectedIndex+1].text = objListBox.options[objListBox.selectedIndex].text;
		objListBox.options[objListBox.selectedIndex].text = priorValue;
		objListBox.selectedIndex = objListBox.selectedIndex+1;					// this allows the highlight to follow the item being moved
	}
}


function ListPlacement2(Listbox,strAdjustType) {													// Use: Listbox('MoveOption')
// This function allows the user to move a selected item either up or down in a list, but this one will retain values.

alert("ListPlacement2() is deprecated; updated your code.");
return false;

	var i, j, priorText, priorValue;
	var objListBox = (typeof Listbox === "object") ? Listbox : document.getElementById(Listbox);

	if (strAdjustType == "up") {
		if ((objListBox.selectedIndex == -1) || (objListBox.selectedIndex == 0)) { return 0; }	// exit function if the user didn't select a server ip or he/she is trying to move up the first ip
		priorText = objListBox.options[objListBox.selectedIndex-1].text;			// set the value of the listbox item thats one position up from the selected item, to the variable
		priorValue = objListBox.options[objListBox.selectedIndex-1].value;
		objListBox.options[objListBox.selectedIndex-1].text = objListBox.options[objListBox.selectedIndex].text;		// set the value of the listbox item up one position to the value of the selected item
		objListBox.options[objListBox.selectedIndex-1].value = objListBox.options[objListBox.selectedIndex].value;
		objListBox.options[objListBox.selectedIndex].text = priorText;				// set the value of the selected listbox item to the priorText variable
		objListBox.options[objListBox.selectedIndex].value = priorValue;
		objListBox.selectedIndex = objListBox.selectedIndex-1;					// this allows the highlight to follow the item being moved

	} else if (strAdjustType == "down") {
		if ((objListBox.selectedIndex == -1) || (objListBox.selectedIndex == objListBox.options.length-1)) { return 0; }	// exit function if the user didn't select a server ip or he/she is trying to move down the last ip
		priorText = objListBox.options[objListBox.selectedIndex+1].text;			// same as above, but in reverse
		priorValue = objListBox.options[objListBox.selectedIndex+1].value;
		objListBox.options[objListBox.selectedIndex+1].text = objListBox.options[objListBox.selectedIndex].text;
		objListBox.options[objListBox.selectedIndex+1].value = objListBox.options[objListBox.selectedIndex].value;
		objListBox.options[objListBox.selectedIndex].text = priorText;
		objListBox.options[objListBox.selectedIndex].value = priorValue;
		objListBox.selectedIndex = objListBox.selectedIndex+1;					// this allows the highlight to follow the item being moved
	}
}




