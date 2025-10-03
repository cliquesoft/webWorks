// _Ajax.js
//
// Created	unknown by Dave Henderson (support@cliquesoft.org)
// Updated	2025-09-11 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.
//
// ADDITIONAL:
// http://www.jtricks.com/javascript_tutorials/varargs.html
// http://stackoverflow.com/questions/2856059/passing-an-array-as-a-function-parameter-in-javascript
// http://stackoverflow.com/questions/7301062/passing-variable-number-of-arguments-from-one-function-to-another
// http://stackoverflow.com/questions/3120017/javascript-forwarding-function-calls-that-take-variable-number-of-arguments




// -- Global Variables --

var _oAjax;					// used for this modules' AJAX communication
var _oAjaxDisable;				// used for en/dis-abling a passed "submit button"
var MESSAGE = '';				// the returned message to display to the user
var STATUS;					// access to any optionally returned status value
var PIPED;					// access to pipe '|' separated data (e.g. PIPED = "val1|val2|val3|..."
var DATA = new Object;				// access to returned XML <data> attributes as associative array (e.g. DATA['name'])
var XML;					// access to raw XML data returned from the server




// -- Session API --

function Ajax(sAction) {
	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var mCallback = null;											// the callback to perform

	switch(sAction) {
		case "failure":
		case "inactive":
		case "timeout":
			break;
		case "success":
			if (arguments.length > 1) { mCallback = arguments[1]; }
			break;

		case "Call":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			//if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "Download":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "FIFO":
		case "Socket":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "Progress":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		case "Append":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;
		case "xml2str":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			//if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "BuildURI":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			//if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "FindChecked":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			//if (arguments.length > 5) { mCallback = arguments[5]; }
			break;


		default:
			Project('Popup','fail',"ERROR: Ajax('"+sAction+"') is not a valid action for the function API.");
			return false;
	}

	// sanity checks
	if (typeof mRequirements == 'boolean' && ! mRequirements) {						// first check if the mandatory parameter count is met
		Project('Popup','fail',"ERROR: Ajax('"+sAction+"') was called without meeting a sufficent number of parameters.");
		return false;
	} else if (typeof mRequirements == 'number') {								// second check if those mandatory parameters have an actual value
		for (let i=0; i<mRequirements; i++) {
			if (arguments[i] == '') {
				Project('Popup','fail',"ERROR: Ajax('"+sAction+"') was called with mandatory parameter #"+i+" being blank.");
				return false;
			}
		}
	}

	// lets perform some work!
	switch(sAction) {
		// --- BUILT-IN ACTIONS ---	   (for base callback functionality of ajax)

		case "failure":
			// if no message was passed, but we're supposed to report something, create a generic default
			if (MESSAGE == '') { MESSAGE = "An error has occurred while attempting to perform the request. Please try again in a few minutes."; }
// VER2 - pull this message from the javascript language file
		case "inactive":
			if (MESSAGE == '') { MESSAGE = "The server has not responded to the request. Please try again in a few moments."; }
		case "success":
			if (MESSAGE == '') { MESSAGE = "The request was performed successfully!"; }
		case "timeout":
			if (MESSAGE == '') { MESSAGE = "The request timed out while communicating with the server. Please try again in a few moments."; }

			var sType = (sAction == 'success') ? 'succ' : 'fail';
			Project(_sProjectUI,sType,MESSAGE);

			// reset the (form) objects
			MESSAGE = '';										// erase the value now that we're done

			// Perform any passed callback
			if (typeof(Callback) === 'function') { Callback(); }					// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
			else if (typeof(Callback) === 'string' && Callback != '') { eval(Callback); }		// using this line, the value can be passed as: "alert('hello world');"

			if (sAction == 'success') { return true; } else { return false; }
			break;




		// --- CUSTOM ACTIONS ---


		   // OVERVIEW		Makes an ajax call to the server
		   // SYNTAX		Ajax('Call',oXHR,sScript,sQuery='',mDisable='',mSuccess=Ajax('success'),mFailure=Ajax('failure'),mInactive=Ajax('inactive'),mTimeout=Ajax('timeout'),sForm='',sSkip='',mTarget='',sType='post',nVerbose=4);
		case "Call":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   //  1: oXHR		[object] the variable name to use for the AJAX request							_oAjax
		   //  2: sScript	[string] server-side script URL that will handle the file generation					'download.php'
		   //  3: sQuery	[string] additional query; must already be formatted and escaped					'key1=val1&key2=val2'	['']
		   //			[ NOTE ] accepts comma separated list of short-hand form					 	'!send!,>email<,(sUsername),(sSessionID),[sName],[sEmail],...'
		   //				 !action_name!,>target_name<,(cookie_name),[element_name],{global_variable_name}
		   //  4: mDisable	[string][object] the object to disable during ajax communication (to prevent starting multiple calls)	'Send'			['']
		   //  5: mSuccess	[string][function] callback on a successful AJAX request; null retains default				"success('hurray');"	[Ajax('success')]
		   //  6: mFailure	[string][function] callback on a failed AJAX request; null retains default				"failure('oops...');"	[Ajax('failure')]
		   //  7: mInactive	[string][function] callback ifserver returns 'reload' (expired user session); null retains default	"expired('oops...');"	[Ajax('inactive')]
		   //  8: mTimeout	[string][function] callback when an AJAX request has timed out; null retains default			"timeout('oops...');"	[Ajax('timeout')]
		   //  9: sForm		[string] form name containing objects to construct a query string from					'account'		['']
		   // 10: sSkip		[string] comma separated list of objects to skip (if disabled) in the form; '*' skips all disabled	'name,status,location'	['']
		   // 11: mTarget	[string][object] the object to receive HTML output generated by server					'results'		['']
		   // 12: sType		[string] type of request to make			      [get, post, postxml (for XML mime types)]	'get'			['post']
		   // 13: nVerbose	[number] level of verbosity 	  [0=none,1=support,2=timeout/support,3=busy/timeout/support,4=all]	2			[4]
		   //  *: oListboxes	[ list ] ONLY <select>'s whose ENTIRE list of values to add to the query REGARDLESS what's selected	Ajax('Call',...,'Employees','Status',...);
		   //			[ NOTE ] names must be VERBATIM as they are on the form (including any trailing '[]' for php users)
			// default value assignments
			var oXHR =	arguments[1];
			var sScript =	arguments[2];								// retain the default values if 'null' was passed
			var sQuery =	(arguments.length > 3) ? arguments[3] : '';				sQuery = (sQuery != null) ? sQuery : '';
			var mDisable =	(arguments.length > 4) ? arguments[4] : '';				mDisable = (mDisable != null) ? mDisable : '';
			var mSuccess =	(arguments.length > 5) ? arguments[5] : "Ajax('success');";		mSuccess = (mSuccess != null) ? mSuccess : "Ajax('success');";
			var mFailure =	(arguments.length > 6) ? arguments[6] : "Ajax('failure');";		mFailure = (mFailure != null) ? mFailure : "Ajax('failure');";
			var mInactive =	(arguments.length > 7) ? arguments[7] : "Ajax('inactive');";		mInactive = (mInactive != null) ? mInactive : "Ajax('inactive');";
			var mTimeout =	(arguments.length > 8) ? arguments[8] : "Ajax('timeout');";		mTimeout = (mTimeout != null) ? mTimeout : "Ajax('timeout');";
			var sForm =	(arguments.length > 9) ? arguments[9] : '';				sForm = (sForm != null) ? sForm : '';
			var sSkip =	(arguments.length > 10) ? arguments[10] : '';				sSkip = (sSkip != null) ? sSkip : '';
			var mTarget =	(arguments.length > 11) ? arguments[11] : '';				mTarget = (mTarget != null) ? mTarget : '';
			var sType =	(arguments.length > 12) ? arguments[12] : 'post';			sType = (sType != null) ? sType : 'post';
			var nVerbose =	(arguments.length > 13) ? arguments[13] : 4;				nVerbose = (nVerbose != null) ? nVerbose : 4;

			var sMessage = '';									// used to display a returned error message
			var bSuccess = false;									// used to indicate whether the callbacks need to be run
			var bFailure = false;
			var bInactive = false;

			// perform task

			// optional disable of the "submit button"
			if (mDisable) {
			   	var oDisable = (typeof mDisable === "object") ? mDisable : document.getElementById(mDisable);
				if (typeof oDisable !== "undefined") {						// makes sure the objects exists
					_oAjaxDisable = oDisable;

					oDisable.disabled = true;
					oDisable.className += ' disabled';

					// optional re-enable of the "submit button" by appending appropriate code
					// https://stackoverflow.com/questions/9134686/adding-code-to-a-javascript-function-programmatically
					// https://stackoverflow.com/questions/42002334/javascript-add-parameters-to-a-function-passed-as-a-parameter
					if (typeof mSuccess === "function") {
						var prior_s = mSuccess;
						mSuccess = function() { oDisable.disabled=false; oDisable.className=oDisable.className.replace(/\s*disabled/g,''); prior_s(); };
					} else { mSuccess += " _oAjaxDisable.disabled=false; _oAjaxDisable.className=_oAjaxDisable.className.replace(/\s*disabled/g,'');"; }
					if (typeof mFailure === "function") {
						var prior_f = mFailure;
						mFailure = function() { oDisable.disabled=false; oDisable.className=oDisable.className.replace(/\s*disabled/g,''); prior_f(); };
					} else { mFailure += " _oAjaxDisable.disabled=false; _oAjaxDisable.className=_oAjaxDisable.className.replace(/\s*disabled/g,'');"; }
					if (typeof mInactive === "function") {
						var prior_i = mInactive;
						mInactive = function() { oDisable.disabled=false; oDisable.className=oDisable.className.replace(/\s*disabled/g,''); prior_i(); };
					} else { mInactive += " _oAjaxDisable.disabled=false; _oAjaxDisable.className=_oAjaxDisable.className.replace(/\s*disabled/g,'');"; }
					if (typeof mTimeout === "function") {
						var prior_t = mTimeout;
						mTimeout = function() { oDisable.disabled=false; oDisable.className=oDisable.className.replace(/\s*disabled/g,''); prior_t(); };
					} else { mTimeout += " _oAjaxDisable.disabled=false; _oAjaxDisable.className=_oAjaxDisable.className.replace(/\s*disabled/g,'');"; }
				}
			}

			// make any neccessary changes to sQuery
			sQuery = (sQuery.substring(0,1) == '&') ? sQuery.substring(1) : sQuery;			// remove any preceeding '&' character
			if (sQuery.search(/^[!\>\(\{\[]/) > -1) { sQuery = Ajax('BuildURI',sQuery); }		// if we were give a comma list of short-hand, then convert it

			// pre-pend to the query all the form element values if we're doing an AXAJ 'form submission'
			if (sForm != '') {
// UPDATED 2025/07/16
//				let p = new Array(sForm,sSkip);
//				for (let i=14; i<arguments.length; i++) { p.push(arguments[i]); }		// add each optionally appended parameters (*)
//				sQuery = buildURI.apply(null, p)+'&'+sQuery;
				let p = new Array('BuildURI',sForm,sSkip);					// define the first 3 parameters to pass to Ajax()
				for (let i=14; i<arguments.length; i++) { p.push(arguments[i]); }		// add each optionally appended parameters (*)
				sQuery = Ajax.apply(null ,p)+'&'+sQuery;					// pass all the info to Ajax() and append any existing sQuery values from the above 'if'
			}

			if (window.XMLHttpRequest) {								// for all browsers except microsoft
				oXHR = new XMLHttpRequest();
			} else if (window.ActiveXObject) {							// for Microsoft
				oXHR = new ActiveXObject("Microsoft.XMLHTTP");
			} else {
				if (verbose) { alert("Unfortunately your browser doesn't support ajax requests.\nPlease upgrade your browser to a current version."); }
				return -3;
			}

			document.body.style.cursor = "wait";							// change the mouse cursor to indicate that a process is happening
			if (oXHR) {
				if (sType == 'get') {								// start a GET request
					oXHR.open("GET", sScript+"?"+sQuery);
					oXHR.send();
				} else {									// start a POST request
					oXHR.open("POST", sScript, true);
					oXHR.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
					if (sType == 'postxml') { oXHR.overrideMimeType('text/xml'); }
					oXHR.send(sQuery);
					// http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
					// http://www.javascriptkit.com/dhtmltutors/ajaxgetpost2.shtml
					// http://stackoverflow.com/questions/75980/best-practice-escape-or-encodeuri-encodeuricomponent
				}

				oXHR.onreadystatechange = function() {
					// 0	UNSENT			client has been created, open() not called yet
					// 1	OPENED			open() has been called
					// 2	HEADERS_RECEIVED 	send() has been called, headers and status are available
					// 3	LOADING			downloading, responseText holds partial data
					// 4	DONE			the operation is complete
					if (oXHR.readyState == 1) {						// this if statement creates a way to abort the request and let the user know what happened.
// VER2 - test this
						setTimeout(function() {						// this never gets returned since it's in a setTimeout call
							if (oXHR.readyState != 4) {
								oXHR.abort();
								document.body.style.cursor = "default";		// change the mouse cursor back to the default to indicate the job was completed
// UPDATED 2025/09/02
//								if (verbose > 1) { alert("The request timed out, please try again."); }
								if (verbose > 1) { Project(_sProjectUI,'fail',"The request timed out, please try again."); }
								if (timeout != '') { eval(timeout); }		// execute reqTimeout code if passed
							}
						}, 20000);
					}

					if (oXHR.readyState == 4) {
						//alert(oXHR.responseText);					// for debugging
						if (oXHR.responseXML) {						// if we got an XML response, then...
							// if "<extra ... action='reload' />" was passed as part of the XML element, then adjust the 'responseText' value to trigger the following 'if'
							if (oXHR.responseXML.getElementsByTagName("extra").item(0)) {
								let e = oXHR.responseXML.getElementsByTagName("extra").item(0);
								if (e.hasAttribute('action')) { if (e.getAttribute('action') == 'reload') {bInactive = true;} }
							// this section deals with a server returning ONLY the text 'reload' -OR- '<reload />' as a returned XML element
							} else if (oXHR.responseText == 'reload' || oXHR.responseXML.getElementsByTagName("reload").item(0)) {
								bInactive = true;
							// if the XML response is either success or failure, then...
							} else if (oXHR.responseXML.getElementsByTagName("s").item(0) || oXHR.responseXML.getElementsByTagName("f").item(0)) {
								if (oXHR.responseXML.getElementsByTagName("html").item(0)){ HTML = oXHR.responseXML.getElementsByTagName("html").item(0); }
								if (oXHR.responseXML.getElementsByTagName("xml").item(0)) { XML = oXHR.responseXML.getElementsByTagName("xml").item(0); }
								if (oXHR.responseXML.getElementsByTagName("msg").item(0)) { sMessage = oXHR.responseXML.getElementsByTagName("msg").item(0).firstChild.data; }
								if (oXHR.responseXML.getElementsByTagName("status").item(0)) { STATUS = oXHR.responseXML.getElementsByTagName("status").item(0).firstChild.data; }
								if (oXHR.responseXML.getElementsByTagName("data").item(0)) {			// if a <data> block was returned, then...
									var node = oXHR.responseXML.getElementsByTagName("data").item(0);
									if (node.firstChild) { PIPED = node.firstChild.data; }			//   IF WE HAVE data between the <data></data> tags, then store it!
									if (node.attributes.length > 0) {					//   if the returned XML contains attributes (e.g. <data key1=val1 key2=val2 ...></data>), then...
										for (i=0; i<node.attributes.length; i++)			//      store it so it can be retrieved in hash form (e.g. DATA['key1'] = "val1")
											{ DATA[node.attributes[i].name] = node.attributes[i].value; }
									}
								}
								if (oXHR.responseXML.getElementsByTagName("s").item(0)) { bSuccess = true; }	// indicate we need to execute success code if passed
								if (oXHR.responseXML.getElementsByTagName("f").item(0)) { bFailure = true; }	// indicate we need to execute failed code if passed
							}
						} else if (mTarget != '') {					// if we want the output from the server sent as the innerHTML for a form object, then... (NOTE: this is after the <f> processing so any error messages can be displayed to the user)
							if ('value' in document.getElementById(mTarget)) {	// if the element has a '.value' property, then...	https://stackoverflow.com/questions/52393114/html-element-does-value-attribute-or-property-exist
								document.getElementById(mTarget).value = '';	// clear any prior contents beforehand
								document.getElementById(mTarget).value = oXHR.responseText.replace(/&lt;br \/&gt;/g,'<br />');
							} else {						// otherwise we have to use '.innerHTML', so...
								document.getElementById(mTarget).innerHTML = '';	// clear any prior contents beforehand
								document.getElementById(mTarget).innerHTML = oXHR.responseText.replace(/&lt;br \/&gt;/g,'<br />');
							}
							if (oXHR.responseText != '') { bSuccess = true; }	// indicate we need to execute success code if passed
							if (oXHR.responseText == '') { bFailure = true; }	// indicate we need to execute failed code if passed
						} else { sMessage = oXHR.responseText; }			// otherwise display any error messages

						if (sMessage != '') {
							// determine which way to display the message (0=use the browsers alert(), 1 or 2=store the message in a variable to use the UI display method
							if (_sProjectUI == 'Alert') { alert(sMessage); } else { MESSAGE = sMessage; }
						}
						if (bSuccess) {
							if (typeof mSuccess === "function") { mSuccess(); }
							else if (typeof mSuccess === 'string' && mSuccess != '') { eval(mSuccess); }
						} else if (bFailure) {
							if (typeof mFailure === "function") { mFailure(); }
							else if (typeof mFailure === 'string' && mFailure != '') { eval(mFailure); }
						} else if (bInactive) {
							if (typeof mInactive === "function") { mInactive(); }
							else if (typeof mInactive === 'string' && mInactive != '') { eval(mInactive); }
						}

						document.body.style.cursor = "default";				// change the mouse cursor back to the default to indicate the job was completed
					}
				}
				return 1;
			}
			break;




		   // OVERVIEW		Download a dynamically generated file (e.g. pdf, csv, etc)
		   // SYNTAX		Ajax('Download',sMime,sScript,sQuery='',sFilename='',mCallback='');
		case "Download":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sMime		[string] mime type of the file to download (e.g. application/pdf, plain/text, etc)			'plain/text'
		   // 2: sScript	[string] server-side script URL that will handle the file generation					'download.php'
		   // 3: sQuery		[string] additional query; must already be formatted and escaped					'key1=val1&key2=val2'	['']
		   // 4: sFilename	[string] the filename to save-as									'temp_download.txt'	['']
		   //			[ NOTE ] ignore if this value is specified on the server-side (in the 'Content-Disposition' header)
		   // 5: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
		   //    NOTES		https://nehalist.io/downloading-files-from-post-requests/
		   //			https://stackoverflow.com/questions/4545311/download-a-file-by-jquery-ajax
			// default value assignments
			if (arguments.length < 4) { arguments[3] = 'get'; }

			// store passed values in variables so they can be used in callbacks
			var sMime = arguments[1];
			var sScript = arguments[2];
			var sQuery = arguments[3];
			var sFilename = arguments[4];
			var oXHR = new XMLHttpRequest();

			// perform task
			oXHR.open('POST', sScript, true);
			oXHR.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
			oXHR.responseType = 'blob';
			oXHR.send(sQuery);

			oXHR.onload = function() {
				// Only handle status code 200
				if (oXHR.status === 200) {
					// Try to find out the filename from the content disposition 'filename' value
					var sDisposition = oXHR.getResponseHeader('content-disposition');
					var s_Matches = /"([^"]*)"/.exec(sDisposition);
					var sFile = (s_Matches != null && s_Matches[1]) ? s_Matches[1] : sFilename;

					// The actual download
					var oBlob = new Blob([oXHR.response], { type: sMime });
					var oLink = document.createElement('a');
					oLink.href = window.URL.createObjectURL(oBlob);
					oLink.download = sFile;

					document.body.appendChild(oLink);
					oLink.click();
					document.body.removeChild(oLink);
				}
// VER2 - some error handling should be done here...
			}
			break;




		   // OVERVIEW		(Re)establishes a "socket" to the server for instant and persistent communication
		   // SYNTAX		Ajax('Socket',oXHR,sScript,sQuery='',sType='get',mCallback='');
		case "FIFO":
		case "Socket":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: oXHR		[object] the variable name to use for the AJAX request							_oAjax
		   // 2: sScript	[string] server-side script URL that will handle the file generation					'download.php'
		   // 3: sQuery		[string] additional query; must already be formatted and escaped					'key1=val1&key2=val2'	['']
		   // 4: sType		[string] the request type								    [get, post] 'post'			['get']
		   // 5: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			if (arguments.length < 4) { arguments[3] = ''; }
			if (arguments.length < 5) { arguments[4] = 'get'; }

			// perform task
			var oXHR = arguments[1];
			var sScript = arugments[2];
			var sQuery = arugments[3];
			var sType = arguments[4];
			var nOffset = 0;									// used to get the last command (since they are all clumped together)
			var nQuit = 0;										// used to stop this function from continously calling itself (allowing it to be stopped)

			if (window.XMLHttpRequest) {								// for all browsers except microsoft
				oXHR = new XMLHttpRequest();
			} else if (window.ActiveXObject) {							// for Microsoft
				oXHR = new ActiveXObject("Microsoft.XMLHTTP");
			} else {
				alert("Unfortunately your browser doesn't support ajax requests.\nPlease upgrade your browser to a current version.");
				return 1;
			}

			if (! oXHR) { alert('No XHR could be setup for this communication.'); return 0; }

			if (sType == 'get' || sType == '') {							// start a GET request
				oXHR.open("GET", sScript+'?'+sQuery, true);					// NOTE: the 'true' value is for asynchronous communication
				oXHR.send();
			} else {										// start a POST request
				oXHR.open("POST", sScript, true);
				oXHR.send(sQuery);
			}

			oXHR.onreadystatechange = function() {
				// since any communication can come in at any time, we must use this 'readyState' for processing
				if (oXHR.readyState == 3 && oXHR.responseText != '' && oXHR.responseText != '\n') {
					var sLast = oXHR.responseText.substring(nOffset).trim();
					var sFunc = sLast.indexOf('(') ? sLast.substring(0, sLast.indexOf('(')) : '';

					if (sLast == 'QUIT') { nQuit = 1; }
					else if (sLast.substring(0,7) == 'ERROR: ') { Project(_sProjectUI,'fail',sLast); }
					else if (sFunc != '') { eval(sLast); }
					nOffset = oXHR.responseText.length;					// store the new starting point for the next communication
				}

				// upon receiving the end of the communication, re-establish the connection to the server
				if (oXHR.readyState == 4 && oXHR.status == 200 && nQuit == 0)
					{ setTimeout(Ajax('Socket',oXHR,sType,sQuery),100); }
			}
			break;




		   // OVERVIEW		Supplemental function to 'Socket'; Adjusts progress using 'meters' or 'info'
		   // SYNTAX		Ajax('Progress',mObject,sValue,sType='meter',mCallback='');
		case "Progress":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mObject	[string][object] the object to receive the update							'oProgressBar'
		   // 2: sValue		[string] meter width (e.g. '80%', '200px', etc), info message (e.g. 1250/2345 blocks copied)		'25%'
		   // 3: sType		[string] the progress type								  [meter, info] 'meter'			['meter']
		   // 4: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			if (arguments.length < 4) { arguments[3] = 'meter'; }

			// perform task
			var oTarget = (typeof arguments[1] === "object") ? arguments[1] : document.getElementById(arguments[1]);

			if (arguments[3] == 'meter') {
				oTarget.style.width = arguments[2];
			} else if (arguments[3] == 'info') {
				if (oTarget.hasAttribute('value')) { oTarget.value = arguments[2]; }		// for elements like a <textbox>
				else { oTarget.innerHTML = arguments[2]; }					// for elements like a <span>
			}
			break;




		   // OVERVIEW		Supplemental function to 'Socket'; Appends responses to an object (e.g. IM app)
		   // SYNTAX		Ajax('Append',mObject,sValue,mCallback='');
		case "Append":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mObject	[string][object] the object to receive the update							'oProgressBar'
		   // 2: sValue		[string] info or message										'Hello World'
		   // 3: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			var oTarget = (typeof arguments[1] === "object") ? arguments[1] : document.getElementById(arguments[1]);

			if (oTarget.hasAttribute('value')) { oTarget.value += arguments[2]; }		// for elements like a <textbox>
			else { oTarget.innerHTML += arguments[2]; }					// for elements like a <span>
			break;




		   // OVERVIEW		Converts XML nodes into strings
		   // SYNTAX		alert(Ajax('xml2str',XML));
		   //			document.getElementById("my-element").innerHTML = Ajax('xml2str',XML);
		   //			var Output = Ajax('xml2str',XML);
		case "xml2str":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sXMLNode	[string] server-side script URL that will handle the file generation					'download.php'
		   // NOTES		https://stackoverflow.com/questions/349250/how-to-display-xml-in-javascript
			// perform task
			if (arguments[1].xml) {
				return arguments[1].xml;
			} else if (XMLSerializer) {
				var xml_serializer = new XMLSerializer();
				return xml_serializer.serializeToString(arguments[1]);
			} else {
				alert("ERROR: Please update your browser as it is extremely old and deprecated.");
				return "";
			}
			break;




		   // OVERVIEW		Supplemental function to 'Call'; Builds a query string to submit to the server side script
		   // SYNTAX		var Query = Ajax('BuildURI',sTarget,sSkip='');
		case "BuildURI":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sTarget	[string] the form name or comma separated short-hand to process; can accept null			'colors'
		   //			[ NOTE ] accepts comma separated list of short-hand form plus pre-escaped syntax		 	'!send!,>email<,(sUsername),(sSessionID),[sName],[sEmail],&key1=value1&key2=value2&...'
		   //				 !action_name!,>target_name<,(cookie_name),[element_name],{global_variable_name}
		   // 2: sSkip		[string] comma separated list of objects to skip (if disabled) in the form; '*' skips all disabled	'name,status,location'	['']
		   // *: oListboxes	[ list ] ONLY <select>'s whose ENTIRE list of values to add to the query REGARDLESS what's selected	Ajax('BuildURI',...,'Employees','Status',...);
		   //			[ NOTE ] names must be VERBATIM as they are on the form (including any trailing '[]' for php users)
			// default value assignments
			if (arguments.length < 3) { arguments[2] = ''; }

			// perform task
			var sQuery = '';
			var oElms = (arguments[1].indexOf(',') == -1) ? document.forms[arguments[1]].elements : '';
			var oSkip = new Array();

			// if we have comma separated short-hand, then process each to be processed below
			if (oElms == '' && arguments[1] != null) {
				let sIDs = arguments[1].split(',');
				for (let i=0; i<sIDs.length; i++) {
					let sFirst = sIDs[i].substring(0, 1);

					if (sFirst == '!') { sQuery += '&A='+sIDs[i].slice(1,-1); }
					else if (sFirst == '>') { sQuery += '&T='+sIDs[i].slice(1,-1); }
					else if (sFirst == '(') { sQuery += '&'+sIDs[i].slice(1,-1)+'='+encodeURIComponent( Cookie('Obtain',sIDs[i].slice(1,-1)) ); }
					else if (sFirst == '{') { sQuery += '&'+sIDs[i].slice(1,-1)+'='+eval('encodeURIComponent('+sIDs[i].slice(1,-1)+')'); }
					else if (sFirst == '[') { oElms.push( document.getElementById(sIDs[i].slice(1,-1)) ); }
					else if (sFirst == '&') { sQuery += sIDs[i]; }				// for normal pre-escaped syntax
				}
			}

			// if we need to process specific form objects for disability, then store them in an array
			if (arguments[2] != '*') { oSkip = arguments[2].split(','); }

			// for each object within the passed form...
			for (let i=0; i<oElms.length; i++) {
				// skip any applicable form objects
				if (arguments[2] == '*' && oElms[i].disabled) {					// skip all 'disabled' objects if requested
					continue;
				} else if (oSkip.length > 0) {							// skip all passed objects based on 'disabled' value
					let bSkip = false;
					for (let j=0; j<oSkip.length; j++) {
						if (oElms[i].id == oSkip[j].id) {
							if (oSkip[j].disabled) { bSkip = true; }
							break;
						}
					}
					if (bSkip) { continue; }
				}

				// CHECKBOX
				if (oElms[i].type == 'checkbox') {
					if (oElms[i].checked) { sQuery += '&'+oElms[i].id+'=1'; } else { sQuery += '&'+oElms[i].id+'=0'; }

				// RADIO BUTTONS
				} else if (oElms[i].type == 'radio') {
					if (prior == oElms[i].name) { continue; }				// skip any other unchecked radio buttons
					prior = oElms[i].name;							// store the name so only one in the array gets processed
					sQuery += '&'+oElms[i].name+'='+encodeURIComponent(findCheckedValue(oElms[i].name,'value'));

				// SELECT LISTS
				} else if (oElms[i].type == 'select-one' || oElms[i].type == 'select-multiple') {
					if (oElms[i].options.length == 0) {					// if there aren't any options in the combobox, then...
						sQuery += '&'+oElms[i].id+'=';					//   designate a blank value for the element (this is so that the validate() calls work correctly in php)
						continue;							//   iterate to the next element
					}

					for (var j=2; j<arguments.length; j++)					// check to see if the iterated combobox was also a passed parameter to this function
						{ if (arguments[j] == oElms[i].id) {break;} }			//   if it IS, then break out of this 'for' loop
					if (arguments[j] == oElms[i].id) { continue; }				// if we just processed a combobox in the above 'for' loop, then iterate the TOP 'for' loop

					sQuery += '&'+oElms[i].id+'=';						// add the combobox name to the query string and process its value below

					// if we've made it down here, we need to process ONLY what is selected in the combobox, so...
					for (var k=0; k<oElms[i].options.length; k++)				//   go through each option and submit ONLY its selected values
						{ if (oElms[i].options[k].selected) {sQuery += encodeURIComponent(oElms[i].options[k].value+'|');} }
					if (oElms[i].options.selectedIndex > -1) { sQuery = sQuery.slice(0,-3); }  // remove the trailing '|' symbol (-3 since '|' will be encoded as '%7C') if there was any value selections

				// ANYTHING ELSE
				} else { sQuery += '&'+oElms[i].id+'='+encodeURIComponent(oElms[i].value); }
			}

			// https://stackoverflow.com/questions/8015088/html-forms-multi-select-using-get/8015161
			// https://stackoverflow.com/questions/316781/how-to-build-query-string-with-javascript
			// http://php.net/manual/en/function.http-build-query.php
			// http://api.jquery.com/jquery.param/
			// someStr=value1&someObj[a]=5&someObj[b]=6&someArr[]=1&someArr[]=2
			for (let i=3; i<arguments.length; i++) {						// processes any optionally appended parameters (*)
				if (arguments[i].slice(-2) != '[]') {						// if we need to add all the options in a <select> without trailing '[]' characters (for php)
					let oListbox = document.getElementById(arguments[i]);
					sQuery += '&'+oListbox.id+'=';						//   add the combobox name to the query string and process its value below
					for (let k=0; k<oListbox.options.length; k++)				//   go through each option and add its values to the query string (regardless if selected)
						{ sQuery += encodeURIComponent(oListbox.options[k].value+'|'); }
					if (oListbox.options.selectedIndex > -1) {sQuery = sQuery.slice(0,-3);}	//   remove the trailing '|' symbol (-3 since '|' will be encoded as '%7C') if there was any value selections
				} else {									// otherwise we do have trailing '[]' characters, so...
					let oListbox = document.getElementById(arguments[i].slice(0,-2));	//   get the id without the '[]' characters
					for (let k=0; k<oListbox.options.length; k++) {				//   go through each option and add its values to the query string (regardless if selected)
						sQuery += '&'+oListbox.id+'%5B%5D=';				//   but append the raw value in the query	NOTE: adding two more '[]' will make the value an array
						sQuery += encodeURIComponent(oListbox.options[k].value);
					}
				}
			}

			return sQuery.substring(1);								// remove the very first '&' symbol (see NOTE at top of function declaration)
			break;




		   // OVERVIEW		Supplemental function to 'BuildURI'
		   // SYNTAX		var Value = Ajax('FindChecked',mRadio,sValue='value');
		case "FindChecked":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mRadio		[string][object] the radio button to process								'colors'
		   // 2: sValue		[string] The element attribute to check							  [name, value]	'name'			['value']
			// default value assignments
			if (arguments.length < 3) { arguments[2] = 'value'; }

			// perform task
			var oRadio = (typeof arguments[1] === "object") ? arguments[1] : document.getElementById(arguments[1]);

			for (let i=0; i<oRadio.length; i++) {
				if (oRadio[i].checked) {
					if (arguments[2] == 'name') { return oRadio[i].name; }
					else if (arguments[2] == 'value') { return oRadio[i].value; }
				}
			}
			return '';				// failsafe return
			break;
	}


	// Perform any passed callback
	if (typeof(mCallback) === 'function') { mCallback(); }							// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
	else if (typeof(mCallback) === 'string' && mCallback != '') { eval(mCallback); }			// using this line, the value can be passed as: "alert('hello world');"


	// return desired results
	return true;
}










//  --- DEPRECATED/LEGACY ---


var callVarious=false;					// used to process AJAX calls using the 'ajaxReq' function below
var reqVarious;						// used to process AJAX requests using the 'ajaxSubmit' function below
var aryButtons = new Array();				// used to store buttons passed as objects instead of by name





function ajax(variable,verbose,type,script,query,form,skip,submit,target,transition,success,failed,busy,timeout,inactive) {
// the jsAPI way of AJAX interaction with the server
// NOTE: this builds the URI before the transition occurs (so screen changes can take place, for instance)
// variable	[variable]	the variable name to use for the AJAX request
// verbose	[integer]	the level of verbosity. 0=none 1=support 2=timeout,support 3=busy,timeout,support 4=all
// type		[string]	what type of request to make 'get', 'post', or 'postxml' (for XML mime types)  are the only valid values
// script	[string]	the name|URI of the server-side script that will be called (e.g. http://www.mydomain.com/whatever.php)
// query	[string]	additional string to add onto the AJAX query (must already be formatted and escaped - NO preceeding '&' sign)
// form		[string]	the name of the form containing the form objects to perform an AJAX form-submission (a non-blank value here constructs the query string based on all the objects contained within the form; NOTE: see the 'skip' description below for additional control)
// skip		[string]	if this value is 'true', all disabled objects on the form are skipped; '' disables; if this value is a comma separated list, each form object in the list is skipped during the form submission. NOTE: this is only relevant if the 'form' value is non-blank!
// submit	[string]	the name/object of the form object (typically a button or submit) that was clicked to call this function; non-blank values disable object (so it can't be clicked more than once)
// target	[string]	the name of the form object to receive HTML output generated by server via this AJAX request; a null value disables.
// transition	[string]	code to execute that will transition from one screen to another before submitting the query (e.g. adjLayout('Results') - from #Search to #Results)
// success	[string]	CALLBACK: code to execute on a successful AJAX request
// failed	[string]	CALLBACK: code to execute on a failed AJAX request (there was an error on the server processing the request)
// busy		[string]	CALLBACK: code to execute if this function is already processing the same AJAX request
// timeout	[string]	CALLBACK: code to execute after an AJAX request has timed out when communicating with the server (e.g. can prompt user for retry, redirect to local server of a device, etc)
// inactive	[string]	CALLBACK: code to execute if a server returns the string 'reload' indicating a user has been inactive to long
// *		ALL appended parameters MUST be the names of <select> objects whose ENTIRE list of values need
//		to be added to the query string REGARDLESS of what is currently selected, otherwise only the
//		currently selected value (if any) is passed to the server.
//		NOTE: names must be VERBATIM as they are on the form (including any trailing '[]' for php users)!

alert("ajax() is deprecated; updated your code.");
return false;

   // optional disable of the "submit button"
   if (submit) {
   	var Elm = (typeof submit === "object") ? submit : document.getElementById(submit);
	if (typeof Elm === "undefined") {					// in case the value passed was a string (which would execute the second side of the above line), but there isn't that element on the page (e.g. calling a function from another script), then test once more to see if it actually exists
		submit = null;
	} else {
		var Index = aryButtons.push(Elm) - 1;				// https://stackoverflow.com/questions/16139752/how-can-i-find-out-the-index-number-of-an-object-that-i-pushed-into-an-array

		Elm.disabled = true;
		Elm.className += ' disabled';
	}
   }

   // pre-pend to the query all the form element values if we're doing an AXAJ 'form submission'
   //if (form != '') { query = buildURI(form,skip)+query; }						ERROR: can't pass optional additional function parameters
   //if (form != '') { query = buildURI.apply(null, Array().slice.call(arguments, 15))+query; }		ERROR: can't pass the 'form' and 'skip' variables
   if (form != '') {
// LEFT OFF - expand this to not only be a form, but can also be a comma separated list of objects
	p = new Array(form,skip);
	for (var i=15; i<arguments.length; i++) { p.push(arguments[i]); }	// add each optionally appended parameters (*)
	query = buildURI.apply(null, p)+'&'+query;
   }

   // MAKE ANY WEBPAGE TRANSITIONS
// LEFT OFF - is there a way to pre-pend a call back to the 'success' field value in the ajaxReq query below to solve this delima?
   if (transition != '') { eval(transition); }

   // optional re-enable of the "submit button" by appending appropriate code
   // https://stackoverflow.com/questions/9134686/adding-code-to-a-javascript-function-programmatically
   // https://stackoverflow.com/questions/42002334/javascript-add-parameters-to-a-function-passed-as-a-parameter
   if (submit) {
	if (typeof success === "function") {
		var prior_s = success;
		success = function() { Elm.disabled=false; Elm.className=Elm.className.replace(/\s*disabled/g,''); prior_s(); };
	} else { success += " aryButtons["+Index+"].disabled=false; aryButtons["+Index+"].className=aryButtons["+Index+"].className.replace(/\s*disabled/g,'');"; }
	if (typeof failed === "function") {
		var prior_f = failed;
		failed = function() { Elm.disabled=false; Elm.className=Elm.className.replace(/\s*disabled/g,''); prior_f(); };
	} else { failed += " aryButtons["+Index+"].disabled=false; aryButtons["+Index+"].className=aryButtons["+Index+"].className.replace(/\s*disabled/g,'');"; }
	if (typeof busy === "function") {
		var prior_b = failed;
		busy = function() { Elm.disabled=false; Elm.className=Elm.className.replace(/\s*disabled/g,''); prior_b(); };
	} else { busy += " aryButtons["+Index+"].disabled=false; aryButtons["+Index+"].className=aryButtons["+Index+"].className.replace(/\s*disabled/g,'');"; }
	if (typeof timeout === "function") {
		var prior_t = failed;
		timeout = function() { Elm.disabled=false; Elm.className=Elm.className.replace(/\s*disabled/g,''); prior_t(); };
	} else { timeout += " aryButtons["+Index+"].disabled=false; aryButtons["+Index+"].className=aryButtons["+Index+"].className.replace(/\s*disabled/g,'');"; }
	if (typeof inactive === "function") {
		var prior_i = failed;
		inactive = function() { Elm.disabled=false; Elm.className=Elm.className.replace(/\s*disabled/g,''); prior_i(); };
	} else { inactive += " aryButtons["+Index+"].disabled=false; aryButtons["+Index+"].className=aryButtons["+Index+"].className.replace(/\s*disabled/g,'');"; }
   }

   // AJAX CALL TO SERVER
   ajaxReq(variable,type,script+"?"+query,target,success,failed,busy,timeout,inactive,verbose);
}


function ajaxRetrieve() {		// rename to: ajaxPoll
// maybe pass this a form name and it will cycle through each form object to store its related value from the server
// using selListbox() and other custom built functions. may have to pass the values back in the same order they are
// cycled (and detecting what the object is [eg textbox, select, button, etc], it will know whether to assign a
// value or call the selListbox() function, etc. -OR- you can perhaps name the db values and the form objects the same.

	// UPDATE: this needs to be the "ajax long-polling" for IM/"real-time" capabilities

}


function ajaxDownload(script,query,mime,filename) {
// This function allows the downloading of a dynamically generated file (e.g. pdf, csv, etc).
// script	[string]	the URL to the server-side script that will handle the file generation
// query	[string]	additional string to add onto the AJAX query (must already be formatted and escaped - NO preceeding '&' sign)
// mime		[string]	the mime type of the file to download (e.g. application/pdf, plain/text, etc)
// filename	[string]	the filename to save-as; NOTE: if this value is specified on the server-side (in the 'Content-Disposition' header), then you can ignore this value in js
// https://nehalist.io/downloading-files-from-post-requests/
// https://stackoverflow.com/questions/4545311/download-a-file-by-jquery-ajax

alert("ajaxDownload() is deprecated; updated your code.");
return false;

	var objXMLreq = new XMLHttpRequest();
	objXMLreq.open('POST', script, true);
	objXMLreq.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
	objXMLreq.responseType = 'blob';

	objXMLreq.onload = function() {
		// Only handle status code 200
		if (objXMLreq.status === 200) {
			// Try to find out the filename from the content disposition `filename` value
			var disposition = objXMLreq.getResponseHeader('content-disposition');
			var matches = /"([^"]*)"/.exec(disposition);
			var file = (matches != null && matches[1] ? matches[1] : filename);

			// The actual download
			var blob = new Blob([objXMLreq.response], { type: mime });
			var link = document.createElement('a');
			link.href = window.URL.createObjectURL(blob);
			link.download = file;

			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
		}
	      
// LEFT OFF - some error handling should be done here...
	};

	objXMLreq.send(query);




//function downloadFile(urlToSend) {
//     var req = new XMLHttpRequest();
//     req.open("GET", urlToSend, true);
//     req.responseType = "blob";
//     req.onload = function (event) {
//         var blob = req.response;
//         var fileName = req.getResponseHeader("fileName") //if you have the fileName header available
//         var link=document.createElement('a');
//         link.href=window.URL.createObjectURL(blob);
//         link.download=fileName;
//         link.click();
//     };
//
//     req.send();
// }
}




function findCheckedValue(strObjArrayName,strValue2Return) {
// This function goes through the object array and returns the specified value (currently: 'value','name').

alert("findCheckedValue() is deprecated; updated your code.");
return false;

   var e=document.getElementsByName(strObjArrayName);
   for (var i=0; i<e.length; i++) {
	if (e[i].checked) {
	   if (strValue2Return == 'value') { return e[i].value; }
	   else if (strValue2Return == 'name') { return e[i].name; }
	}
   }
}


function buildURI(form,skip) {
// builds the URI to submit to the server based on the form objects contained within the passed form name.
// NOTE: the preceeding '&' is removed on purpose incase the returned value has to follow the '?' character.
// form		[string]	the name of the form containing the form objects to submit (a non-blank value here constructs the query string based on all the objects contained within the form; see the 'skip' description above for additional control)
// skip		[string]	if this value is 'true', all disabled objects are skipped; if this value is a comma separated list, each form object in the list is skipped during the form submission. NOTE: this is only relevant if the 'form' value is non-blank!
// *		ALL appended parameters MUST be the names of <select> objects whose ENTIRE list of values need
//		to be added to the query string REGARDLESS of what is currently selected, otherwise only the
//		currently selected value is processed.
//		NOTE: names must be VERBATIM as they are on the form (including any trailing '[]' for php users)

alert("buildURI() is deprecated; updated your code.");
return false;

   var query='',prior='',e=document.forms[form].elements;

   for (var i=0; i<e.length; i++) {					// for each object within the passed form...
	// skip any applicable form objects
	var regex = new RegExp(e[i].id,'i');
	if (typeof(skip) == 'boolean' && skip == true && e[i].disabled) { continue; }		// skip all 'disabled' objects if requested
// UPDATED 2019/01/16 - the parameter value is now deprecated
//	else if (typeof(skip) != 'boolean' && skip.search(e[i].id,'i') > -1) { continue; }	// skip all passed objects regardless of 'disabled' value
	else if (typeof(skip) != 'boolean' && skip.search(regex) > -1) { continue; }		// skip all passed objects regardless of 'disabled' value

	// CHECKBOX
	if (e[i].type == 'checkbox') {
	   if (e[i].checked) { query+='&'+e[i].id+'=1'; } else { query+='&'+e[i].id+'=0'; }

	// RADIO BUTTONS
	} else if (e[i].type == 'radio') {
	   if (prior == e[i].name) { continue; }			// skip any other unchecked radio buttons
	   prior=e[i].name;						// store the name so only one in the array gets processed
	   query+='&'+e[i].name+'='+encodeURIComponent(findCheckedValue(e[i].name,'value'));

	// SELECT LISTS
	} else if (e[i].type == 'select-one' || e[i].type == 'select-multiple') {
	   if (e[i].options.length == 0) {				// if there aren't any options in the combobox, then...
		query += '&'+e[i].id+'=';				//   designate a blank value for the element (this is so that the validate() calls work correctly in php)
		continue;						//   iterate to the next element
	   }

	   for (var j=2; j<arguments.length; j++)			// check to see if the iterated combobox was also a passed parameter to this function
		{ if (arguments[j] == e[i].id) {break;} }		//   if it IS, then break out of this 'for' loop
	   if (arguments[j] == e[i].id) { continue; }			// if we just processed a combobox in the above 'for' loop, then iterate the TOP 'for' loop

	   query+='&'+e[i].id+'=';					// add the combobox name to the query string and process its value below

	   // if we've made it down here, we need to process ONLY what is selected in the combobox, so...
	   for (var k=0; k<e[i].options.length; k++)			//   go through each option and submit ONLY its selected values
		{ if (e[i].options[k].selected) {query += encodeURIComponent(e[i].options[k].value+'|');} }
	   if (e[i].options.selectedIndex > -1) { query = query.slice(0,-3); }		// remove the trailing '|' symbol (-3 since '|' will be encoded as '%7C') if there was any value selections

	// ANYTHING ELSE
	} else { query += '&'+e[i].id+'='+encodeURIComponent(e[i].value); }
   }

   // https://stackoverflow.com/questions/8015088/html-forms-multi-select-using-get/8015161
   // https://stackoverflow.com/questions/316781/how-to-build-query-string-with-javascript
   // http://php.net/manual/en/function.http-build-query.php
   // http://api.jquery.com/jquery.param/
   // someStr=value1&someObj[a]=5&someObj[b]=6&someArr[]=1&someArr[]=2
   for (i=2; i<arguments.length; i++) {					// processes any optionally appended parameters (*)
	if (arguments[i].substr(-2) != '[]') {				// if we need to add all the options in a <select> without trailing '[]' characters (for php)
		e=document.getElementById(arguments[i]);
		query+='&'+e.id+'=';					//   add the combobox name to the query string and process its value below
		for (var k=0; k<e.options.length; k++)			//   go through each option and add its values to the query string (regardless if selected)
			{ query += encodeURIComponent(e.options[k].value+'|'); }
		if (e.options.selectedIndex > -1) {query = query.slice(0,-3);}	//   remove the trailing '|' symbol (-3 since '|' will be encoded as '%7C') if there was any value selections
	} else {							// otherwise we do have trailing '[]' characters, so...
		e=document.getElementById(arguments[i].substring(0,arguments[i].length-2));	// get the id without the '[]' characters
		for (var k=0; k<e.options.length; k++) {		//   go through each option and add its values to the query string (regardless if selected)
			query += '&'+e.id+'%5B%5D=';			//   but append the raw value in the query	NOTE: adding two more '[]' will make the value an array
			query += encodeURIComponent(e.options[k].value);
		}
	}
   }

   return query.substring(1);						// remove the very first '&' symbol (see NOTE at top of function declaration)
}


function ajaxReq(objXMLreq,type,query,target,success,failed,busy,timeout,inactive,verbose) {
// this function sends a request to the script to be processed then informs the user of the outcome.  Return codes
// are as follows: 1=success,0=inactivity timeout,-1=invalid query value,-2=busy,-3=browser not supported
// NOTE: to access data returned by the server-side script, see the descriptions of the STATUS, PIPED, and DATA variables above.
// objXMLreq	the variable name to use for the XML request
// type		[string] what type of request to make 'get', 'post', or 'postxml' (for XML mime types)  are the only valid values
// query	[string] the escaped URI (including all key=value pairs) to send the AJAX request
// target	[string] the name of the form object to receive HTML output generated by the AJAX request (from the server).  A null value disables.
//			 NOTE: for any "code" parameter below, pass '' for no additional processing
// success	[string] code to execute on a successful AJAX call (the server processing was successful)
// failed	[string] code to execute on a failed AJAX call (there was an error on the server processing the request)
// busy		[string] code to execute if this function is already processing an AJAX request
// timeout	[string] code to execute on a processing AJAX call that timed out in communicating with the server
// inactive	[string] code to execute if a server returns the string 'reload' indicating a user has been inactive to long
// verbose	[integer] the level of verbosity. 0=none 1=support 2=timeout,support 3=busy,timeout,support 4=all

   if (query == '') {							// if there was no passed data, exit this function
	if (verbose>3) { alert("You must pass a value in for 'query' to know where\nto send the AJAX request."); }
	return -1;
   }

// LEFT OFF - maybe convert callVarious to an array so this function can handle mutliple calls at a time (each can keep track of their index value in the array - whether its on or off)
//	would it be safe to change callVarious where the 'return 1' current is (as the request has already been sent to the server)?
//   if (callVarious) {							// if we're already processing an AJAX request, then...
//	if (verbose>2) { alert("A request is currently being processed.  Please\nwait until it's completed before trying again."); }
//	if (busy != '') { eval(busy); }					// execute busy code if passed
//	return -2;
//   } else { callVarious = true; }					// change the global variable so that the same request can't be initiated at the same time

   if (window.XMLHttpRequest)						// for all browsers except microsoft
	{ objXMLreq = new XMLHttpRequest(); }
   else if (window.ActiveXObject)					// for Microsoft
	{ objXMLreq = new ActiveXObject("Microsoft.XMLHTTP"); }
   else {
	if (verbose) { alert("Unfortunately your browser doesn't support ajax requests.\nPlease upgrade your browser to a current version."); }
	return -3;
   }

   document.body.style.cursor = "wait";					// change the mouse cursor to indicate that a process is happening
   if (objXMLreq) {
	if (type == 'get' || type == '') {				// start a GET request
	   objXMLreq.open("GET", query);
	} else {							// start a POST request
	   var postparts=query.split('?');
	   objXMLreq.open("POST", postparts[0], true);
	   objXMLreq.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	   if (type == 'postxml') { objXMLreq.overrideMimeType('text/xml'); }
	   //objXMLreq.setRequestHeader("Content-length", postparts[1].length);
// REMOVED 2019/01/17 - not sure if this was required for anything...
//	   objXMLreq.setRequestHeader("Connection", "close");
// REMOVED 2019/01/17 - this was causing multiple problems (maybe because there were two send() calls?) and has been moved to the bottom
//	   objXMLreq.send(postparts[1]);

	   // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
	   // http://www.javascriptkit.com/dhtmltutors/ajaxgetpost2.shtml
	   // http://stackoverflow.com/questions/75980/best-practice-escape-or-encodeuri-encodeuricomponent
	}

	objXMLreq.onreadystatechange = function() {
	   if (objXMLreq.readyState == 1) {				// this if statement creates a way to abort the request and let the user know what happened.
		setTimeout(function() {					// this never gets returned since it's in a setTimeout call
		   if (objXMLreq.readyState != 4) {
//			callVarious=false;
			objXMLreq.abort();
			document.body.style.cursor = "default";		// change the mouse cursor back to the default to indicate the job was completed
			if (verbose>1) { alert("The request timed out, please try again."); }
			if (timeout != '') { eval(timeout); }		// execute reqTimeout code if passed
		   }
		}, 20000);
	   }
	   if (objXMLreq.readyState == 4) {
		//alert(objXMLreq.responseText);			// for debugging

// allow files to be downloaded via ajax!!!		https://stackoverflow.com/questions/220231/accessing-the-web-pages-http-headers-in-javascript
//alert(objXMLreq.getAllResponseHeaders());
//var HEAD = objXMLreq.getAllResponseHeaders().toLowerCase().match(/content-type: .*/);
//if (HEAD[0].indexOf('text/xml') == -1 && HEAD[0].indexOf('text/plain') == -1) {
//alert('head |'+HEAD[0]+'|');
//	window.location = 
//	return 0;
//}

		// this section deals with a server returning ONLY the text 'reload'  -OR-  '<reload />' as a returned XML element  -OR-  "<extra ... action='reload' />" as an attribute to an 'extra' XML element
		if (objXMLreq.responseText == 'reload' || (objXMLreq.responseXML && objXMLreq.responseXML.getElementsByTagName("reload").item(0))) {
		   if (inactive != '') {
			   if (typeof inactive === "function") { inactive(); }
			   else { eval(inactive); }
		   }
		   return 0;
		}
		if (objXMLreq.responseXML && objXMLreq.responseXML.getElementsByTagName("extra").item(0)) {
		   var e = objXMLreq.responseXML.getElementsByTagName("extra").item(0);
		   if (e.hasAttribute('action') && e.getAttribute('action') == 'reload') {
			if (inactive != '') {
				if (typeof inactive === "function") { inactive(); }
				else { eval(inactive); }
			}
			return 0;
		   }
		}

		if (objXMLreq.responseXML && objXMLreq.responseXML.getElementsByTagName("s").item(0)) {			// if there was no errors, hide the "popup"
		   if (objXMLreq.responseXML.getElementsByTagName("html").item(0)) { HTML = objXMLreq.responseXML.getElementsByTagName("html").item(0); }
		   if (objXMLreq.responseXML.getElementsByTagName("xml").item(0)) { XML = objXMLreq.responseXML.getElementsByTagName("xml").item(0); }
		   if (objXMLreq.responseXML.getElementsByTagName("msg").item(0)) {
			if (MESSAGE == 0)	// if the global variable is set to 0, then don't use the UI to display the message, use the browsers' alert()
				{ alert(objXMLreq.responseXML.getElementsByTagName("msg").item(0).firstChild.data); }
			else			// otherwise, store the message in MESSAGE to be accessed by the calling function to render in the UI
				{ MESSAGE = objXMLreq.responseXML.getElementsByTagName("msg").item(0).firstChild.data; }
		   }
		   if (objXMLreq.responseXML.getElementsByTagName("status").item(0)) { STATUS = objXMLreq.responseXML.getElementsByTagName("status").item(0).firstChild.data; }
		   if (objXMLreq.responseXML.getElementsByTagName("data").item(0)) {					// if a <data> block was returned, then...
			var node = objXMLreq.responseXML.getElementsByTagName("data").item(0);
			if (node.firstChild) { PIPED = node.firstChild.data; }						//   IF WE HAVE data between the <data></data> tags, then store it!
			if (node.attributes.length > 0) {								//   if the returned XML contains attributes (e.g. <data key1=val1 key2=val2 ...></data>), then...
			   for (i=0; i<node.attributes.length; i++)							//      store it so it can be retrieved in hash form (e.g. DATA['key1'] = "val1")
				{ DATA[node.attributes[i].name] = node.attributes[i].value; }
			}
		   }
		   if (success != '') {		// execute success code if passed
			if (typeof success === "function") { success(); }
			else { eval(success); }
		   }
		} else if (objXMLreq.responseXML && objXMLreq.responseXML.getElementsByTagName("f").item(0)) {
		   if (objXMLreq.responseXML.getElementsByTagName("xml").item(0)) { XML = objXMLreq.responseXML.getElementsByTagName("xml").item(0); }
		   if (objXMLreq.responseXML.getElementsByTagName("msg").item(0)) {
			if (MESSAGE == 0)	// if the global variable is set to 0, then don't use the UI to display the message, use the browsers' alert()
				{ alert(objXMLreq.responseXML.getElementsByTagName("msg").item(0).firstChild.data); }
			else			// otherwise, store the message in MESSAGE to be accessed by the calling function to render in the UI
				{ MESSAGE = objXMLreq.responseXML.getElementsByTagName("msg").item(0).firstChild.data; }
		   }
		   if (objXMLreq.responseXML.getElementsByTagName("status").item(0)) { STATUS = objXMLreq.responseXML.getElementsByTagName("status").item(0).firstChild.data; }
		   if (objXMLreq.responseXML.getElementsByTagName("data").item(0)) {
			var node = objXMLreq.responseXML.getElementsByTagName("data").item(0);
			if (node.firstChild) { PIPED = node.firstChild.data; }						// see above for notes on this
			if (node.attributes.length > 0) {
			   for (i=0; i<node.attributes.length; i++)
				{ DATA[node.attributes[i].name] = node.attributes[i].value; }
			}
		   }
		   if (failed != '') {		// execute failed code if passed
			   if (typeof failed === "function") { failed(); }
			   else { eval(failed); }
		   }
		} else if (target != '') {				// if we want the output from the server sent as the innerHTML for a form object, then... (NOTE: this is after the <f> processing so any error messages can be displayed to the user)
// UPDATED 2022/10/18 - add the '.replace' so that php's safeXML() can escape successfully while the UI gets to parse correctly as well
		   document.getElementById(target).innerHTML = objXMLreq.responseText.replace(/&lt;br \/&gt;/g,'<br />');
		   if (objXMLreq.responseText != '' && success != '') {
			   if (typeof success === "function") { success(); }
			   else { eval(success); }
		   }
		   if (objXMLreq.responseText == '' && failed != '') {
			   if (typeof failed === "function") { failed(); }
			   else { eval(failed); }
		   }
		} else { alert(objXMLreq.responseText); }		// this captures and displays error messages

//		callVarious=false;
		document.body.style.cursor = "default";			// change the mouse cursor back to the default to indicate the job was completed
	   }
	}
// UPDATED 2019/01/23 - this was causing multiple problems (maybe because there were two send() calls?) when using 'post'
	if (type == 'get' || type == '') { objXMLreq.send(null); }
	if (type == 'post') { objXMLreq.send(postparts[1]); }		// NOTE: this appears to need to come after the 'onreadystatechange' call above for POST calls
	return 1;
   }
}


function xml2string(xml_node) {
// https://stackoverflow.com/questions/349250/how-to-display-xml-in-javascript
// This is a debug function that will let a developer see what is being passed back from a server call.
// Can call like:
//	alert(xml2string(XML));							< display in alert box
//	document.getElementById("my-element").innerHTML = xml2string(XML);	< display in an HTML element

alert("xml2string() is deprecated; updated your code.");
return false;

        if (xml_node.xml) {
		return xml_node.xml;
        } else if (XMLSerializer) {
		var xml_serializer = new XMLSerializer();
		return xml_serializer.serializeToString(xml_node);
	} else {
		alert("ERROR: Please update your browser as it is extremely old and deprecated.");
		return "";
	}
}

