// _Tabs.js
//
// Created	2004/07/10 by Dave Henderson (support@cliquesoft.org)
// Updated	2025/06/26 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.
//
// RESOURCES:
// http://stackoverflow.com/questions/2793688/how-do-i-put-unordered-list-items-into-an-array




// -- Tabs API --

function Tabs(sAction) {
	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var mCallback = null;											// the callback to perform

	switch(sAction) {
		case "Select":
			if (arguments.length < 5) { mRequirements = false; } else { mRequirements = 5; }
			if (arguments.length > 6) { mCallback = arguments[6]; }
			break;


		default:
			Project('Popup','fail',"ERROR: Tabs('"+sAction+"') is not a valid action for the function API.");
			return false;
	}

	// sanity checks
	if (typeof mRequirements == 'boolean' && ! mRequirements) {						// first check if the mandatory parameter count is met
		alert("ERROR: Tabs('"+sAction+"') was called without meeting a sufficent number of parameters.");
		return false;
	} else if (typeof mRequirements == 'number') {								// second check if those mandatory parameters have an actual value
		for (let i=0; i<mRequirements; i++) {
			if (arguments[i] == '') {
				alert("ERROR: Tabs('"+sAction+"') was called with mandatory parameter #"+i+" being blank.");
				return false;
			}
		}
	}

	// lets perform some work!
	switch(sAction) {
		   // OVERVIEW		Selects a tab from the list
		   // SYNTAX		Tabs('Select',oContainer,oClicked,sClass,sSelected,sType='consecutive',mCallback='');
		case "Select":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mContainer	[string][object] The <ul> containing the tabs; if 5='separated' this becomes a prefix to match against	'ulTabs'
		   // 2: mClicked	[string][object] The <li> tab that was just clicked							this
		   // 3: sClass		[string] The name of the class indicated the object (e.g. <li>) is a tab				'tab'
		   // 4: sSelected	[string] The name of the class to apply to the selected tab						'selected'
		   // 5: sType		[string] The tab type to interact with					       [consecutive, separated] 'separated'		['consecutive']
		   // 6: mCallback	[string][function] The callback to execute upon success							"alert('done!');"	['']
		   //			[ NOTE ] You can pass associated screens (e.g. <div>'s) that correspond with the tab by including a "rel=''" attribute
			// default value assignments
			if (arguments.length < 6) { arguments[5] = 'consecutive'; }

			if (arguments[5] == 'consecutive') {
   				var oContainer = (typeof arguments[1] === "object") ? arguments[1] : document.getElementById(arguments[1]);
				var oTabs = oContainer.getElementsByTagName('li');								// store all the <li> tabs
			} else { var sTab = arguments[1]; }
   			var oClicked = (typeof arguments[2] === "object") ? arguments[2] : document.getElementById(arguments[2]);
			var reClass = new RegExp(arguments[3], 'g');										// allows a variable to be used for the class name
			var reSelected = new RegExp(arguments[4], 'g');										// allows a variable to be used for the class name

			if (oClicked.className.indexOf(arguments[4]) > -1) { return true; }							// if the tab is already selected, then exit

			if (arguments[5] == 'consecutive') {											// if the tabs are in a single <ul>, then...
				for (var i=0; i<oTabs.length; i++) {										//   cycle each (nested) <li> looking for tabs
					if (oTabs[i].className.match(reClass)) {								//   process only relevant <li>'s based on the a matching class name
						oTabs[i].className = oTabs[i].className.replace(reSelected, "");				//   strip the "selected class" from each iterated tab
						if (oTabs[i].hasAttribute('rel') && document.getElementById(oTabs[i].getAttribute('rel'))) { document.getElementById(oTabs[i].getAttribute('rel')).style.display='none'; }	// hide an associated screen if there's a "rel='...'" value
					}
				}
			} else {														// otherwise the tabs can be spreadout in the UI
				var i = 0;
				while (document.getElementById(sTab + i)) {									//   while we have a valid tab (the first non-existing occurance terminates this 'while' loop), then...
					var oTab = document.getElementById(sTab + i);
					oTab.className = oTab.className.replace(reSelected, "");						//   strip the "selected" class from each iterated tab
					if (oTab.hasAttribute('rel') && document.getElementById(oTab.getAttribute('rel'))) { document.getElementById(oTab.getAttribute('rel')).style.display='none'; }	// hide each associated tab screen
					i++;
				}
			}

			oClicked.className += ' ' + arguments[4];										// add the selected class name to the tab (<li>)
			if (oClicked.hasAttribute('rel') && document.getElementById(oClicked.getAttribute('rel'))) { document.getElementById(oClicked.getAttribute('rel')).style.display='block'; }	// show the associated screen if the tab has a "rel='...'" value
			break;
	}


	// Perform any passed callback
	if (typeof(mCallback) === 'function') { mCallback(); }							// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
	else if (typeof(mCallback) === 'string' && mCallback != '') { eval(mCallback); }			// using this line, the value can be passed as: "alert('hello world');"


	// return desired results
	switch(sAction) {
		case "Select":
			return true;
			break;
	}
}










//  --- DEPRECATED/LEGACY ---


function adjTabs(strUL,strLIMatch,strLIClass,Selected,Callback) {
// Sets the tab (<li>) that needs to represent which is selected out of the tab list - INDEPENDENT of numeric indices!
// NOTE:
//	- this works with nested <ul>'s too!
//	- any <li> containing a "rel='...'" attribute with the name of a corresponding <div> ID, its 'display' value will be toggled depending on the selected state of the tab
// strUL	the <ul> ID containing the tabs (<li>)
// strLIMatch	the <li> css class name that matches actual tabs (e.g. liTab) - useful if some of the <li>'s are used for navigation which would be skipped by not giving them this class name
// strLIClass	the <li> css class name to apply to the selected tab
// Selected	the <li> that was just clicked; this value can typically be passed as 'this'; can pass as string or object
// Callback	code that should execute processing this function; can pass as string or function

alert("adjTabs() is deprecated; updated your code.");
return false;

   	var objSel = (typeof Selected === "object") ? Selected : document.getElementById(Selected);
	if (! objSel) { return true; }								// if the tab (<li>) no longer exists, then exit this function

	var LIs = document.getElementById(strUL).getElementsByTagName('li');			// stores all the <li> nodes in a variable
	var REM = new RegExp(strLIMatch, 'g');							// allows a variable to be used in the below .match() call with additional parameters ('g')
	var REP = new RegExp(strLIClass, 'g');							// allows a variable to be used in the below .replace() call with additional parameters ('g')

	for (var i=0; i<LIs.length; i++) {							// cycle EACH <li> - everyone of them!
		if (LIs[i].className.match(REM)) {						// filter only the relevant <li> based on the passed matching class name
			LIs[i].className = LIs[i].className.replace(REP, "");			// strip the "selected class" from each iterated tab
			if (LIs[i].getAttribute('rel') && document.getElementById(LIs[i].getAttribute('rel'))) { document.getElementById(LIs[i].getAttribute('rel')).style.display='none'; }	// hide each associated <div> if each iterated <li>'s "rel='...'" value
		}
	}

	objSel.className += ' ' + strLIClass;							// add the selected class name to the tab (<li>)
	if (objSel.getAttribute('rel') && document.getElementById(objSel.getAttribute('rel'))) { document.getElementById(objSel.getAttribute('rel')).style.display='block'; }			// show the associated <div> if the <li> has a "rel='...'" value

	if (typeof Callback === "function") { Callback(); }
	else if (Callback != '') { eval(Callback); }						// execute the callback if it was passed
}


function adjTabs2(strLIClass,strLIPrefix,strScrPrefix,intIndex,Callback) {
// Sets the tab (<li>) that needs to represent which is selected out of the tab list - DEPENDENT of numeric indices!
// NOTE:
//	- this works with nested <ul>'s too!
//	- there can NOT be any non-consecutive index values; e.g. we can't have 'liTab0', 'liTab1', 'liTab3' - there has to be a 'liTab2' also!
// strLIClass	the <li> css class name to apply to the selected tab
// strLIPrefix	the naming convention prefix of the tab (<li>) IDs, for example, id="liTab0" would mean strLIPrefix="liTab".
// strScrPrefix	the optional naming convention prefix of the corresponding tabs' (<li>) screen (usually a div)
// intIndex	the index of the tab (and corresponding screen) clicked; given the above example, this value would be '0'.
// Callback	code that should execute processing this function

alert("adjTabs2() is deprecated; updated your code.");
return false;

	var i=0;
	var regEx = new RegExp(strLIClass, 'g');						// allows a variable to be used in the below .replace() call

	while (document.getElementById(strLIPrefix + i)) {					// while we have a valid tab (the first non-existing occurance completes this 'while' loop), then...
		if (document.getElementById(strScrPrefix + i)) { document.getElementById(strScrPrefix + i).style.display = 'none'; }		// hide each associated tab screen
		document.getElementById(strLIPrefix + i).className = document.getElementById(strLIPrefix + i).className.replace(regEx, "");	// strip the "selected class" from each iterated tab
		i++;
	}

	// now make the changes to reflect the newly selected tab
	if (document.getElementById(strScrPrefix + intIndex)) { document.getElementById(strScrPrefix + intIndex).style.display = 'block'; }
	document.getElementById(strLIPrefix + intIndex).className += ' ' + strLIClass;

	if (typeof Callback === "function") { Callback(); }
	else if (Callback != '') { eval(Callback); }						// execute the callback if it was passed
}

