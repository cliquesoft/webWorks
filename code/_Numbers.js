// _Numbers.js
//
// Created	2020/09/12 (?) by Dave Henderson (support@cliquesoft.org)
// Updated	2025/06/28 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.




// -- Numbers API --

function Numbers(sAction) {
	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var mCallback = null;										// the callback to perform

	switch(sAction) {
		case "AddDecimal":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;
		case "Pad":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;
		case "RoundFloat":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;


		default:
			Project('Popup','fail',"ERROR: Numbers('"+sAction+"') is not a valid action for the function API.");
			return false;
	}

	// sanity checks
	if (typeof mRequirements == 'boolean' && ! mRequirements) {						// first check if the mandatory parameter count is met
		alert("ERROR: Numbers('"+sAction+"') was called without meeting a sufficent number of parameters.");
		return false;
	} else if (typeof mRequirements == 'number') {								// second check if those mandatory parameters have an actual value
		for (let i=0; i<mRequirements; i++) {
			if (arguments[i] == '') {
				alert("ERROR: Numbers('"+sAction+"') was called with mandatory parameter #"+i+" being blank.");
				return false;
			}
		}
	}

	// lets perform some work!
	switch(sAction) {
		   // OVERVIEW		Manipulates the decimal side of a number, including proper rounding and addition of trailing 0's if necessary
		   // SYNTAX		var result = Numbers('AddDecimal',nValue,nDecimals=2,mCallback='');
		case "AddDecimal":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: nValue		[number] The number to manipulate									123.456789
		   // 2: nDecimals	[number] The number of decimal places to trim to							3			2
		   // 3: mCallback	[string][function] The callback to execute upon success							"alert('done!');"	['']
		   //			[ NOTE ] The value returned is a string so it can have trailing 0's
			// default value assignments
			if (arguments.length < 3) { arguments[2] = 2; }

			var nValue = 0;
			var nMultiplier = 1;

			for (let i=0; i<arguments[2]; i++) { nMultiplier *= 10; }				// gets the correct number to multiple/divide by
			arguments[1] = Math.round(arguments[1] * nMultiplier) / nMultiplier;			// performs the rounding
			arguments[1] = String(arguments[1]);							// convert the passed number into a string so we can have trailing 0's
			arguments[1] = (arguments[1].indexOf('.') > -1) ? arguments[1] : arguments[1] += '.';	// if there isn't a decimal in the number, then add one

			// add any necessary trailing 0's to satisfy the decimal count
			let nDecimals = arguments[1].length - arguments[1].indexOf('.') + 1;
			for (i=arguments[2]; i>nDecimals; i--) { arguments[1] += '0'; }
			break;




		   // OVERVIEW		Pads a number with zero's
		   // SYNTAX		var result = Pad(nValue,nDigits,mCallback='');
		case "Pad":	//														EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: nValue		[number] The number to manipulate									123
		   // 2: nDigits	[number] The number of total number of digitals to use							10
		   // 3: mCallback	[string][function] The callback to execute upon success							"alert('done!');"	['']
		   //			[WARNING] This does NOT retain trailing zero's!!! Use this when working with math, not displaying output (for that, see 'AddChange' above)
			// this is only 1 line in the 'return' value section below
			break;




		   // OVERVIEW		Rounds floats properly
		   // SYNTAX		var result = Numbers('RoundFloat',oContainer,oClicked,sClass,sSelected,sType='consecutive',mCallback='');
		case "RoundFloat":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: nValue		[number] The number to manipulate									123.456789
		   // 2: nDecimals	[number] The number of decimal places to trim to							3			2
		   // 3: mCallback	[string][function] The callback to execute upon success							"alert('done!');"	['']
		   //			[WARNING] This does NOT retain trailing zero's!!! Use this when working with math, not displaying output (for that, see 'AddChange' above)
			// this is only 1 line in the 'return' value section below
			break;
	}


	// Perform any passed callback
	if (typeof(mCallback) === 'function') { mCallback(); }							// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
	else if (typeof(mCallback) === 'string' && mCallback != '') { eval(mCallback); }			// using this line, the value can be passed as: "alert('hello world');"


	// return desired results
	switch(sAction) {
		case "AddDecimal":
			return arguments[1];									// NOTE: we can NOT parseFloat() here since it will remove trailing zero's
			break;
		case "Pad":
			return (Math.pow(10, size) + ~~num).toString().substring(1);
			break;
		case "RoundFloat":
			// https://stackoverflow.com/questions/10015027/javascript-tofixed-not-rounding
			return parseFloat((+(Math.round(+(arguments[1] + 'e' + arguments[2])) + 'e' + -arguments[2])).toFixed(arguments[2]));
			break;
	}
}










//  --- DEPRECATED/LEGACY ---


function addChange(value2Adjust,intDecimals) {
// This function makes the change portion of a money textbox correct (performing rounding as well).
// Simply call this routine using the following syntax:
// addChange(123.45678,2);		This will automatically convert the value in the textbox.

alert("addChange() is deprecated; updated your code.");
return false;

   var i,parts,xVal=1;

   if (intDecimals < 0) { alert("The addChange function can only accept decimal places as 0 or a positive integer"); return 0; }
   for (i=0; i<intDecimals; i++) { xVal *= 10; }				// gets the correct number to multiple/divide by

   value2Adjust = Math.round(value2Adjust * xVal) / xVal;			// performs the rounding
   value2Adjust = String(value2Adjust);						// convert the passed number into a string (if it isn't already one)

   parts = value2Adjust.split(".");
   if (parts.length > 1) {
	for (i=intDecimals; i>parts[1].length; i--) { value2Adjust += '0'; }	// add any trailing 0's
   } else {
	if (intDecimals != 0) { value2Adjust += '.'; }				// add the decimal point
	for (i=0; i<intDecimals; i++) { value2Adjust += '0'; }
   }
   return value2Adjust;								// NOTE: we can NOT parseFloat() here since it will remove trailing zero's
}


function roundFloat(num, precision) {
// Since javascript's number handling is terrible, here is a solution
// WARNING: this does NOT retain trailing zero's!!! Use this when working with math, not displaying output (see addChange() above)
// https://stackoverflow.com/questions/10015027/javascript-tofixed-not-rounding

alert("roundFloat() is deprecated; updated your code.");
return false;

    return parseFloat((+(Math.round(+(num + 'e' + precision)) + 'e' + -precision)).toFixed(precision));
}
