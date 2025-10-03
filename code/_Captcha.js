// _Captcha.js
//
// Created	2019-09-13 by Dave Henderson (support@cliquesoft.org)
// Updated	2025-06-26 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.




// -- Captcha API --

function Captcha(sAction) {
	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var mCallback = null;											// the callback to perform

	switch(sAction) {
		case "Text":
			if (arguments.length < 1) { mRequirements = false; } else { mRequirements = 1; }
			if (arguments.length > 2) { mCallback = arguments[2]; }
			break;


		default:
			Project('Popup','fail',"ERROR: Captcha('"+sAction+"') is not a valid action for the function API.");
			return false;
	}

	// sanity checks
	if (typeof mRequirements == 'boolean' && ! mRequirements) {						// first check if the mandatory parameter count is met
		alert("ERROR: Captcha('"+sAction+"') was called without meeting a sufficent number of parameters.");
		return false;
	} else if (typeof mRequirements == 'number') {								// second check if those mandatory parameters have an actual value
		for (let i=0; i<mRequirements; i++) {
			if (arguments[i] == '') {
				alert("ERROR: Captcha('"+sAction+"') was called with mandatory parameter #"+i+" being blank.");
				return false;
			}
		}
	}

	// lets perform some work!
	switch(sAction) {
		   // OVERVIEW			Returns a new text captcha
		   // SYNTAX			var HTML = Captcha('Text',nIndex='',mCallback='');
		case "Text":			//											EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: nIndex			[number] The index of the set of objects to adjust					1			['']
		   // 2: mCallback		[string][function] The callback to execute upon success					"alert('done!');"	['']
			// default value assignments
			if (arguments.length < 2) { arguments[1] = ''; }

			if (! Cookie('Obtain','sUsername'))
				{ document.getElementById('oCaptcha'+arguments[1]).src = 'home/guest/imgs/busy.gif'; }
			else
				{ document.getElementById('oCaptcha'+arguments[1]).src = 'home/'+Cookie('Obtain','sUsername')+'/imgs/busy.gif'; }
			document.getElementById('oCaptcha'+arguments[1]).src='code/_Captcha.php?'+Math.random();
			document.getElementById('sCaptcha'+arguments[1]).value='';
			if (! Mobile) { document.getElementById('sCaptcha'+arguments[1]).focus(); }
			break;
	}


	// Perform any passed callback
	if (typeof(mCallback) === 'function') { mCallback(); }							// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
	else if (typeof(mCallback) === 'string' && mCallback != '') { eval(mCallback); }			// using this line, the value can be passed as: "alert('hello world');"


	// return desired results
	switch(sAction) {
		case "Text":
			return true;
			break;
	}
}










//  --- DEPRECATED/LEGACY ---


function reCaptcha(intIndex) {
// obtains a new captcha for the passed set of objects
// intIndex	the index number of the set of objects to adjust

alert("reCaptcha() is deprecated; updated your code.");
return false;

	if (! getCookie('sUsername'))
		{ document.getElementById('objCaptcha'+intIndex).src='home/guest/imgs/busy.gif'; }
	else
		{ document.getElementById('objCaptcha'+intIndex).src='home/'+getCookie('sUsername')+'/imgs/busy.gif'; }
	document.getElementById('objCaptcha'+intIndex).src='code/_captcha.php?'+Math.random();
	document.getElementById('sCaptcha'+intIndex).value='';
	if (! Mobile) { document.getElementById('sCaptcha'+intIndex).focus(); }
	return true;
}

