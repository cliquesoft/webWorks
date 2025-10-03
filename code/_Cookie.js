// _Cookie.js
//
// Created	unknown    by Dave Henderson (support@cliquesoft.org)
// Updated	2025-05-14 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.
//
// NOTES:
// - This is based on code by Ronnie T. Moore




// -- Cookie API --

function Cookie(sAction) {
	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var mCallback = null;											// the callback to perform

	switch(sAction) {
		case "Create":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 8) { mCallback = arguments[8]; }
			break;
		case "Delete":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "Obtain":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 2) { mCallback = arguments[2]; }
			break;


		default:
			Project('Popup','fail',"ERROR: Cookie('"+sAction+"') is not a valid action for the function API.");
			return false;
	}

	// sanity checks
	if (typeof mRequirements == 'boolean' && ! mRequirements) {						// first check if the mandatory parameter count is met
		alert("ERROR: Cookie('"+sAction+"') was called without meeting a sufficent number of parameters.");
		return false;
	} else if (typeof mRequirements == 'number') {								// second check if those mandatory parameters have an actual value
		for (let i=0; i<mRequirements; i++) {
			if (arguments[i] == '') {
				alert("ERROR: Cookie('"+sAction+"') was called with mandatory parameter #"+i+" being blank.");
				return false;
			}
		}
	}

	// lets perform some work!
	switch(sAction) {
		   // OVERVIEW		Adds a cookie to the browser via javascript
		   // SYNTAX		Cookie('Create',sName,sValue,eExpires=[+10years],sDomain='',sPath='/',sAccess='Strict',bSecure=true,mCallback='');
		case "Create":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sName		[string] Name of the cookie to create									'Username'
		   // 2: sValue		[string] Value to assign to the cookie									'Dave'
		   // 3: eExpires	[epoch] Expiration date of the cookie									new Date('January 1, 2025 00:01:00')	[*current epoch + 10 years*]
		   // 4: sDomain	[string] Sub-domains accessible by the cookie								'sub.mydomain.com'	['']
		   // 5: sPath		[string] Path of the cookie										'folder/'		['/']
		   // 6: sAccess	[string] Access to the cookie										'*'			['Strict']
		   // 7: bSecure	[boolean] If the cookie needs to have a secure connection (e.g. https)					false			[true]
		   // 8: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var eExpires = new Date();
			    eExpires.setFullYear(eExpires.getFullYear() + 10);					// add 10 years to the current date
			var sExpires = '; expires=' + ((arguments.length < 4) ? eExpires.toGMTString() : arguments[3]);
			var sDomain = (arguments.length < 5) ? '' : '; domain='+arguments[4];
			var sPath = '; path=' + ((arguments.length < 6) ? "'/'" : arguments[5]);
			var sAccess = '; SameSite=' + ((arguments.length < 7) ? 'Strict' : arguments[6]);
			var sSecure = (arguments.length < 8) ? '; secure' : arguments[7];

			document.cookie = arguments[1] + "=" + escape(arguments[2]) + sExpires + sPath + sDomain + sAccess + sSecure;
			break;




		   // OVERVIEW		Deletes a cookie from the browser via javascript
		   // SYNTAX		Cookie('Delete',sName,sDomain='',sPath='/',bSecure=true,mCallback='');
		case "Delete":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sName		[string] Name of the cookie to create									'Username'
		   // 2: sDomain	[string] Sub-domains accessible by the cookie								'sub.mydomain.com'	['']
		   // 3: sPath		[string] Path of the cookie										'folder/'		['/']
		   // 4: bSecure	[boolean] If the cookie needs to have a secure connection (e.g. https)					false			[true]
		   // 5: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var sExpires = '; expires=Thu, 01 Jan 1970 00:00:01 GMT';
			var sDomain = (arguments.length < 3) ? '' : '; domain='+arguments[2];
			var sPath = '; path=' + ((arguments.length < 4) ? "'/'" : arguments[3]);
			var sSecure = (arguments.length < 5) ? '; secure' : arguments[4];

			document.cookie = arguments[1] + "=" + escape(arguments[2]) + sExpires + sPath + sDomain + sSecure;
			break;




		   // OVERVIEW		Returns a cookie value from the browser via javascript
		   // SYNTAX		var Value = Cookie('Obtain',sName,mCallback='');
		case "Obtain":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sName		[string] Name of the cookie to create									'Username'
		   // 2: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			var s_Cookies = document.cookie.split('; ');						// split all stored cookies into an array
			var s_Cookie = new Array();

			for (var i=0; i<s_Cookies.length; i++) {						// cycle each cookie in the array
				s_Cookie = s_Cookies[i].split('=');						// split each cookie into a key/value pair
				if (s_Cookie[0] == arguments[1]) { break; }					// if the requested cookie is found, then break the 'for' loop
			}
			if (s_Cookie.length == 0) { return ''; }
			break;
	}


	// Perform any passed callback
	if (typeof(mCallback) === 'function') { mCallback(); }							// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
	else if (typeof(mCallback) === 'string' && mCallback != '') { eval(mCallback); }			// using this line, the value can be passed as: "alert('hello world');"


	// return desired results
	switch(sAction) {
		case "Create":
		case "Delete":
			return true;
			break;
		case "Obtain":
			return (s_Cookie[0] == arguments[1]) ? s_Cookie[1] : '';				// return the cookie value or blank if unsuccessfully finding a matching cookie
			break;
	}
}










//  --- DEPRECATED/LEGACY ---


// Create and/or set the default values of used variables for this file
var expDays = 30;
var exp = new Date();
    exp.setTime(exp.getTime() + (expDays*24*60*60*1000));




function setCookie(strName, strValue) {
// Adds a cookie to the browser via javascript.		NOTE: cookie values can ONLY be strings!
// strName:	the name of the cookie to create
// strValue:	the value you want to assign to the cookie
// object list:	additional parameters include (optional, but in this order): expiration date, path (directory name), domain name, access, secure (ssl xfer only)

alert("setCookie() is deprecated; updated your code.");
return false;

	var argv = setCookie.arguments;
	var argc = setCookie.arguments.length;
// UPDATED 2025/01/21 - made this more dynamic
//	var date = new Date('January 1, 2035 00:01:00');
	var date = new Date();
	    date.setFullYear(date.getFullYear() + 10);		// add 10 years to the current date

	var expires = (argc > 2) ? argv[2] : null;
	var path = (argc > 3) ? argv[3] : null;
	var domain = (argc > 4) ? argv[4] : null;
	var access = (argc > 5) ? argv[5] : null;
	var secure = (argc > 6) ? argv[6] : false;
	document.cookie = strName + "=" + escape(strValue) +
		((expires == null) ? "; expires="+date : ("; expires=" + expires.toGMTString())) +
		((path == null) ? "; path='/'" : ("; path=" + path)) +
		((domain == null) ? "" : ("; domain=" + domain)) +
		((access == null) ? "; SameSite=Strict" : ("; SameSite=" + access)) +
		((secure == true) ? "; secure" : "");
}


function delCookie(strName) {
// Deletes a cookie from the browser via javascript.
// strName:	the name of the cookie to delete
// object list:	additional parameters include (optional, but in this order): path (directory name), domain name, secure (ssl xfer only)

alert("delCookie() is deprecated; updated your code.");
return false;

	var exp = new Date();
	    exp.setTime(exp.getTime() - 1);
	var argv = delCookie.arguments;
	var argc = delCookie.arguments.length;

	var cval = getCookie(strName);
	var path = (argc > 1) ? argv[1] : null;
	var domain = (argc > 2) ? argv[2] : null;
	var secure = (argc > 3) ? argv[3] : false;

	document.cookie = strName + "=" + cval + "; expires=Thu, 01 Jan 1970 00:00:01 GMT" +
		((path == null) ? "; path='/'" : ("; path=" + path)) +
		((domain == null) ? "" : ("; domain=" + domain)) +
		((secure == true) ? "; secure" : "");
}


function getCookie(strName) {
// Obtains an existing cookie value stored in the browser via javascript.

alert("getCookie() is deprecated; updated your code.");
return false;

	var arg = strName + "=";
	var alen = arg.length;
	var clen = document.cookie.length;
	var i = 0;

	while (i < clen) {
		var j = i + alen;
		if (document.cookie.substring(i, j) == arg) { return getCookieVal(j); }
		i = document.cookie.indexOf(" ", i) + 1;
		if (i == 0) break;
	}

	return '';					// NOTE: a blank value equals failure
}


function getCookieVal(offset) {
// A dependency function for the 'getCookie' function.

alert("getCookieVal() is deprecated; updated your code.");
return false;

	var endstr = document.cookie.indexOf(";", offset);

	if (endstr == -1) { endstr = document.cookie.length; }
	return unescape(document.cookie.substring(offset, endstr));
}

