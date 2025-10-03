// _Security.js
//
// Created	2019-09-13 by Dave Henderson (support@cliquesoft.org)
// Updated	2025-09-02 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.




// REMOVED 2025/08/16
//var _bSecurityInvalid = false;					// indicates if validate returned an invalid character
var aKeys = {
8:"backspace", 
9:"tab",
13:"enter",
16:"shift",
17:"ctrl",
18:"alt",
19:"pause/break",
20:"caps lock",
27:"escape",
33:"page up",
34:"page down",
35:"end",
36:"home",
37:"left arrow",
38:"up arrow",
39:"right arrow",
40:"down arrow",
45:"insert",
46:"delete",
91:"left window",
92:"right window",
93:"select key",
96:"numpad 0",
97:"numpad 1",
98:"numpad 2",
99:"numpad 3",
100:"numpad 4",
101:"numpad 5",
102:"numpad 6",
103:"numpad 7",
104:"numpad 8",
105:"numpad 9",
106:"multiply",
107:"add",
109:"subtract",
110:"decimal point",
111:"divide",
112:"F1",
113:"F2",
114:"F3",
115:"F4",
116:"F5",
117:"F6",
118:"F7",
119:"F8",
120:"F9",
121:"F10",
122:"F11",
123:"F12",
144:"num lock",
145:"scroll lock",
186:";",
187:"=",
188:",",
189:"-",
190:".",
191:"/",
192:"`",
219:"[",
220:"\\",
221:"]",
222:"'"
};




// -- Session API --

function Security(sAction) {
	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var mCallback = null;											// the callback to perform

	switch(sAction) {
		case "Validate":
			if (arguments.length < 4) { mRequirements = false; } else { mRequirements = 4; }
			if (arguments.length > 6) { mCallback = arguments[6]; }
			break;
		case "undoXML":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 2) { mCallback = arguments[2]; }
			break;
		case "TogglePassword":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 2) { mCallback = arguments[2]; }
			break;


		default:
			Project('Popup','fail',"ERROR: Security('"+sAction+"') is not a valid action for the function API.");
			return false;
	}

	// sanity checks
	if (typeof mRequirements == 'boolean' && ! mRequirements) {						// first check if the mandatory parameter count is met
		alert("ERROR: Security('"+sAction+"') was called without meeting a sufficent number of parameters.");
		return false;
	} else if (typeof mRequirements == 'number') {								// second check if those mandatory parameters have an actual value
		for (let i=0; i<mRequirements; i++) {
			if (arguments[i] == '') {
				alert("ERROR: Security('"+sAction+"') was called with mandatory parameter #"+i+" being blank.");
				return false;
			}
		}
	}

	// lets perform some work!
	switch(sAction) {
		   // OVERVIEW		Validates the Input for Objects
		   // SYNTAX		Security('Validate',oCheck,sRegEx,sName,eEvent='',bAlert=true,mCallback='');
		case "Validate":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: oCheck		[string][object] The object to check									'name'
		   // 2: sRegEx		[string] The regular expression to check against							'^[0-9a-zA-Z]+$'
		   // 3: sName		[string] The name of the field to reference in the error message					'Username'
		   // 4: eEvent		[event] The event that was fired on the object								event			[null]
		   // 5: bAlert		[boolean] If the user needs to be alerted, or just exit silently					'false'			[true]
		   // 6: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			if (arguments.length < 5) { arguments[4] = null; }
			if (arguments.length < 6) { arguments[5] = true; }

			// perform task
			// WARNING: do NOT include the 'g' flag since it may make the test() call fail (see the link above for details)		var RegEx = new RegExp(arguments[2],"g");
			var oCheck = (typeof arguments[1] === "object") ? arguments[1] : document.getElementById(arguments[1]);
			var sAccepted = arguments[2].replace(/^\!|^\^\[|^\[\^|^\[|\]\+\$$|\]\*\$$|\]\$$|\$\]$|\]$/g, '');			// remove any RegEx formatting so we can isolate just the characters that are (dis)allowed
			var bProblems = false;

			sAccepted = sAccepted.replace('0-9', '0-9, ');						// adjust the values so the display is readable for the user
			sAccepted = sAccepted.replace('A-Z', 'A-Z, ');
			sAccepted = sAccepted.replace('a-z', 'a-z, ');
			if (sAccepted.lastIndexOf(', ') == sAccepted.length-2)					// if one of the above replacements occured, but there are no trailing single characters, then erase the ', ' postfix
				{ sAccepted = sAccepted.substring(0,sAccepted.length-2); }
			else if (sAccepted.indexOf(', ') > -1)							// if one of the above replacements occured, then comma separate all following single characters
				{ sAccepted = sAccepted.substring(0,sAccepted.lastIndexOf(', '))+', '+sAccepted.substring(sAccepted.lastIndexOf(', ')+2).split('').join(', '); }
			else											// otherwise we have no groups of characters, so separate them all as single characters
				{ sAccepted = sAccepted.split('').join(', '); }

			if (arguments[2].substring(0,1) == '^' || arguments[2].substring(0,1) == '[' || arguments[2].substring(0,1) == '(') {	// if we need to process a custom RegEx, then...
				var RegEx = new RegExp(arguments[2]);
				if (RegEx.test(oCheck.value)) { bProblems = true; }
				sAccepted = '';

			} else if (arguments[2].substring(0,1) == '!') {					// if we need to process invalid, illegal characters, then...
				var RegEx = new RegExp('['+arguments[2].substring(1)+']');
				if (RegEx.test(oCheck.value)) { bProblems = true; }
				sAccepted = ' All characters are allowed EXCEPT: ' + sAccepted;

			} else if (arguments[2].substring(0,1) != '!') {					// if we need to process valid, allowed characters, then...
				var RegEx = new RegExp('^['+arguments[2]+']*$');
				if (! RegEx.test(oCheck.value)) { bProblems = true; }
				sAccepted = ' The ONLY allowed characters are: ' + sAccepted;
			}

			if (bProblems) {
// REMOVED 2025/08/16
//				_bSecurityInvalid = true;
				if (arguments[4] && arguments[5])
// UPDATED 2025/09/02
//					{ alert("The \""+aKeys[arguments[4].keyCode]+"\" character is not allowed in this value."); }	// String.fromCharCode(arguments[4].keyCode)
					{ Project(_sProjectUI,'fail',"The \""+aKeys[arguments[4].keyCode]+"\" character is not allowed in this value."); }			// String.fromCharCode(arguments[4].keyCode)
				else if (! arguments[4] && arguments[5])
// UPDATED 2025/09/02
//					{ alert("There is an invalid character in the \""+arguments[3]+"\" value."+sAccepted); }
					{ Project(_sProjectUI,'fail',"There is an invalid character in the \""+arguments[3]+"\" value."+sAccepted); }
				setTimeout(function(){oCheck.focus();},1);			// put the focus back to the form element that needs addressing
				setTimeout("$('#"+oCheck.id+"').animate({opacity: 0.1}, {duration: 2000,complete: function(){document.getElementById('"+oCheck.id+"').className+=' fail';$('#"+oCheck.id+"').animate({opacity: 1}, {duration: 2000});}});", 1);
				return false;
			}
// LEFT OFF - update the below class with 'error' instead of 'fail' (since that's for popups)
			oCheck.className = oCheck.className.replace(/ fail/g, '');
// REMOVED 2025/08/16
//			_bSecurityInvalid = false;
			break;




		   // OVERVIEW		Returns the safeXML conversions from PHP back to their original characters
		   // SYNTAX		var result = Security('undoXML',sString,mCallback='');
		case "undoXML":	//														EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sString	[string] The string to process										'hello world'
		   // 2: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			if (arguments[1] == '') { return ''; }
			break;




		   // OVERVIEW		Toggles a Password Field From Hidden to Text
		   // SYNTAX		Security('TogglePassword',oToggle,mCallback='');
		case "TogglePassword":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: oToggle	[string][object] The object to check									'password'
		   // 2: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			var oToggle = (typeof arguments[1] === "object") ? arguments[1] : document.getElementById(arguments[1]);
			if (oToggle.type == 'password') { oToggle.type = 'textbox'; } else { oToggle.type = 'password'; }
			break;
	}


	// Perform any passed callback
	if (typeof(mCallback) === 'function') { mCallback(); }							// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
	else if (typeof(mCallback) === 'string' && mCallback != '') { eval(mCallback); }			// using this line, the value can be passed as: "alert('hello world');"


	// return desired results
	if (sAction != 'undoXML') {
		return true;
	} else {
		return arguments[1].replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&apos;/g, "'");
	}
}










//  --- DEPRECATED/LEGACY ---


function validate(objCheck,evtKCode,strRegEx,strName,intAlert) {
// validation to check if the input is incorrect
// objCheck	the form object to check its value (typically passed as 'this')
// evtKCode	the event that was fired on the object (typically passed as 'event')
// strRegEx	the regular expression to check against (e.g. '^[0-9a-zA-Z]+$')
// strName	the name of the field to reference in the error message
// intAlert	if the user needs to be alerted, or just exit silently: 0=silent, 1=alert
// NOTES
//		https://stackoverflow.com/questions/10940137/regex-test-v-s-string-match-to-know-if-a-string-matches-a-regular-expression

alert("validate() is deprecated; updated your code.");
return false;


	// WARNING: do NOT include the 'g' flag since it may make the test() call fail (see the link above for details)		var RegEx = new RegExp(strRegEx,"g");
	if (strRegEx.substring(0,1) != '!') { var RegEx = new RegExp(strRegEx); } else { var RegEx = new RegExp(strRegEx.substring(1)); }
	var Value = strRegEx.replace(/^\!\[|^\^\[|^\[\^|^\[|\]\+\$$|\]\*\$$|\]\$$|\$\]$|\]$/g, '');	// remove any RegEx formatting so we can isolate just the characters that are (dis)allowed

	Value = Value.replace('0-9', '0-9, ');			// adjust the values so the display is readable for the user
	Value = Value.replace('A-Z', 'A-Z, ');
	Value = Value.replace('a-z', 'a-z, ');
	if (Value.lastIndexOf(', ') == Value.length-2)		// if one of the above replacements occured, but there are no trailing single characters, then erase the ', ' postfix
		{ Value = Value.substring(0,Value.length-2); }
	else if (Value.indexOf(', ') > -1)			// if one of the above replacements occured, then comma separate all following single characters
		{ Value = Value.substring(0,Value.lastIndexOf(', '))+', '+Value.substring(Value.lastIndexOf(', ')+2).split('').join(', '); }
	else							// otherwise we have no groups of characters, so separate them all as single characters
		{ Value = Value.split('').join(', '); }

	if (strRegEx.substring(0,1) == '[') {			// if we need to process valid, allowed characters, then...
		if (RegEx.test(objCheck.value)) {
			if (evtKCode && intAlert)
				{ alert("The \""+aKeys[evtKCode.keyCode]+"\" character is not allowed in this value."); }	// String.fromCharCode(evtKCode.keyCode)
			else if (! evtKCode && intAlert)
				{ alert("There is an invalid character in the \""+strName+"\" value. The\nallowed characters are: "+Value); }
			setTimeout(function(){objCheck.focus();},100);		// put the focus back to the form element that needs addressing
			return false; 
		}

	} else if (strRegEx.substring(0,1) == '^') {		// if we need to process valid, allowed characters, then...
		if (! RegEx.test(objCheck.value)) {
			if (evtKCode && intAlert)
				{ alert("The \""+aKeys[evtKCode.keyCode]+"\" character is not allowed in this value."); }
			else if (! evtKCode && intAlert)
				{ alert("There is an invalid character in the \""+strName+"\" value. The\nallowed characters are: "+Value); }
			setTimeout(function(){objCheck.focus();},100);
			return false; 
		}

	} else if (strRegEx.substring(0,1) == '!') {		// if we need to process invalid, illegal characters, then...
		if (RegEx.test(objCheck.value)) {
			if (evtKCode && intAlert)
				{ alert("The \""+aKeys[evtKCode.keyCode]+"\" character is not allowed in this value."); }
			else if (! evtKCode && intAlert)
				{ alert("There is an invalid character in the \""+strName+"\" value. The\ndisallowed characters are: "+Value); }
			setTimeout(function(){objCheck.focus();},100);
			return false; 
		}
	}
	return true;
}




function togglePassword(Element) {
// toggles the password field from being dots to the actual text (so a person can check what they've entered)
// Element	the form element to work with; can be passed as the element itself, or its 'id'

alert("togglePassword() is deprecated; updated your code.");
return false;

	var E = (typeof Element === "object") ? Element : document.getElementById(Element);

	if (E.type == 'password') { E.type = 'textbox'; } else { E.type = 'password'; }
}

