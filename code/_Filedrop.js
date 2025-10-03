// _Filedrop.js
//
// Created	2014-06-16 by Dave Henderson (support@cliquesoft.org)
// Updated	2026-07-03 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.
//
// DEPENDENCIES
//	_Ajax.js
//	_Projects.js




// -- Global Variables --

var nFiledropDelay = 0;
var oFiledropHash = null;					// used to store the <span> to display the file upload hashes




// -- Project API --

function Filedrop(sAction) {
	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var mCallback = null;											// the callback to perform

	switch(sAction) {
		case "Alert":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;
		case "Send":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 9) { mCallback = arguments[9]; }
			break;
		case "Hashes":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;


		default:
			Project('Popup','fail',"ERROR: Filedrop('"+sAction+"') is not a valid action for the function API.");
			return false;
	}

	// sanity checks
	if (typeof mRequirements == 'boolean' && ! mRequirements) {						// first check if the mandatory parameter count is met
		alert("ERROR: Filedrop('"+sAction+"') was called without meeting a sufficent number of parameters.");
		return false;
	} else if (typeof mRequirements == 'number') {								// second check if those mandatory parameters have an actual value
		for (let i=0; i<mRequirements; i++) {
			if (arguments[i] == '') {
				alert("ERROR: Filedrop('"+sAction+"') was called with mandatory parameter #"+i+" being blank.");
				return false;
			}
		}
	}

	// lets perform some work!
	switch(sAction) {
		   // OVERVIEW		Initializes a filedrop instance
		   // SYNTAX		Filedrop('Init',mFiledrop,sPath,sExtension='',mCheck='',sValue=null,nIndex=null,nLimit=0,nOverwrite=0,sRename='',sThumb='',bMultiple=true,mCallback='');
		case "Init":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		case "Restart":
		   //  1: mFiledrop	[string][object] The filedrop object to manipulate							"Employees"
		   //  2: sPath		[string] The storage path for the uploaded file								'some/directory'
		   //  3: sExtension	[string] Comma separated path extension using elements, attributes, variables				"name,!variable"	['']
		   //			[ note ] Useful for variable parts of the path and can contain:
		   //				 (cookie_name),[element_name],<element_name|attribute_name>,{variable_name}
		   //  4: mCheck	[string][object] An object whose value needs to be checked before allowing uploads			"Allow_Uploads"		['']
		   //  5: sValue	[string] The value of 'mCheck' that will prevent uploads						false			[null]
		   //  6: nIndex	[number] The index of the object set being manipulated (progress bar and filedrop instance)		2			[null]
		   //  7: nLimit	[number] The size limit, in bytes, for the uploaded file (0 disables)					1024			[0]
		   //  8: nOverwrite	[number] How file overwritting is to be handled	       [0=no, 1=yes, 2=yes despite different extension]	1			[0]
		   //  9: sRename	[string] The new name on the server for the uploaded file (preserving file extension)			'a_new_name'		['']
		   //			[ note ] Can supply a name or a special value: EPOCH (YYYYMMDDhhmmss), DATE (YYYYMMDD), TIME (hhmmss)
		   // 10: sThumb	[string] The size of a thumbnail to create of the uploaded file						'800x600'		['']
// left off - add re-init's itself in the callback; default is true
		   // 11: bMultiple	[boolean] Whether the filedrop instance should permit multiple uploads (instead of just one)		false			[true]
		   // 12: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
		   //	 NOTES
		   //			If the value is a string, several values will dynamically be replaced by actual values:
		   //			'FILENAME' will be replaced by the actual filename just uploaded
		   //			'FILESIZE' will be replaced by the actual file size
		   //			'CRCHASH' will be replaced by the CRC32B hash of the file just uploaded
		   //			'MD5HASH' will be replaced by the MD5 hash of the file just uploaded
		   //			'256HASH' will be replaced by the SHA256 hash of the file just uploaded
			// default value assignments								retain the default values if 'null' was passed
			if (arguments.length < 4) { arguments[3] = ''; }					arguments[3] = (arguments[3] != null) ? arguments[3] : '';
			if (arguments.length < 5) { arguments[4] = ''; }					arguments[5] = (arguments[5] != null) ? arguments[5] : '';
			if (arguments.length < 6) { arguments[5] = null; }
			if (arguments.length < 7) { arguments[6] = null; }
			if (arguments.length < 8) { arguments[7] = 0; }						arguments[7] = (arguments[7] != null) ? arguments[7] : 0;
			if (arguments.length < 9) { arguments[8] = 0; }						arguments[8] = (arguments[8] != null) ? arguments[8] : 0;
			if (arguments.length < 10) { arguments[9] = ''; }					arguments[9] = (arguments[9] != null) ? arguments[9] : '';
			if (arguments.length < 11) { arguments[10] = ''; }					arguments[10] = (arguments[10] != null) ? arguments[10] : '';
			if (arguments.length < 12) { arguments[11] = ''; }					arguments[11] = (arguments[11] != null) ? arguments[11] : true;

			var sOptions = {iframe: {url: 'code/_Filedrop.php'}};					// Tell FileDrop we can deal with iframe uploads using this URL
			var oZone = new FileDrop(arguments[1], sOptions);					// Attach FileDrop to an area ('zone' is an ID but you can also give a DOM node)

			// Do something when a user chooses or drops a file:
			oZone.event('send', function(oFiles) {
				// if a value was passed to check -AND- only allow uploads if a module record is loaded...
				if (arguments[4]) {
					let oCheck = (typeof arguments[4] === "object") ? arguments[4] : document.getElementById(arguments[4]);

					if (oCheck.type == "text") {
						if (oCheck.value == arguments[5]) {
							Project(_sProjectUI,'fail',"You must enter a value before uploads are allowed.");
							return false;
						}
					} else if (oCheck.type == "select" || oCheck.type == "select-one" || oCheck.type == "select-multiple") {
						if (oCheck.selectedIndex == arguments[5]) {
							Project(_sProjectUI,'fail',"You must select a record before uploads are allowed.");
							return false;
						}
					}
				}

				// if we've made it here, we're clear to begin uploads!
				oFiles.each(function(oFile) {							// Depending on browser support files (FileList) might contain multiple items.
					if (arguments[7] > 0 && arguments[7] < oFile.size) {			// check that the iterated file is within the size limits (if provided)		https://stackoverflow.com/questions/4112575/client-checking-file-size-using-html5
						Project(_sProjectUI,'fail',"The size of the \""+oFile.name+"\" file is larger than the limit allowed.");
						return false;
					}

					var nIndex = document.getElementById(arguments[1]).getElementsByTagName('div').length-1;		// this is used so that each file upload is independent
					for (let i=0; i<document.getElementById(arguments[1]).getElementsByTagName('span').length; i++) {
						if (document.getElementById(arguments[1]).getElementsByTagName('span')[i].className == 'oHashes') {
							oFileDropHash = document.getElementById(arguments[1]).getElementsByTagName('span')[i];
							break;
						}
					}

					// create a file upload icon with progress bar in the FileDrop <div>
					document.getElementById(arguments[1]).innerHTML += "<div id='oFiledrop"+nIndex+"' class='Filedrop' title=\""+oFile.name+"\" data-hashes='Uploading...' onMouseOver=\"Filedrop('Hashes','show',this,event);\" onMouseOut=\"Filedrop('Hashes','hide',this,event);\"><img src='home/"+Cookie('Obtain','sUsername')+"/imgs/upload.png' /><span id='oProgressBar"+nIndex+"' class='ProgressBar'></span></div>";

					// Reset the progress when a new upload starts
					oFile.event('xhrSend', function(){ fd.byID('oProgressBar'+nIndex).style.width = 0; });

					// now start/queue the uploads
					if (arguments[9] != 'EPOCH' && arguments[9] != 'TIME') {		// if we are not renaming the files (where seconds matter), then there is no reason to queue/delay the upload, so process them immediately
// UPDATED 2025/07/03 - so we don't have to pass the filedrop element into the 'send' function
//						Filedrop('Send',oFile,arguments[2],arguments[3],nIndex,arguments[7],arguments[8],arguments[9],arguments[10]);
						document.getElementById('oFiledrop'+nIndex).setAttribute('data-hashes', Filedrop('Send',oFile,arguments[2],arguments[3],nIndex,arguments[7],arguments[8],arguments[9],arguments[10]));
					} else {								// otherwise we need to queue/delay, by 1 second, each upload so the generated name does not overwrite another uploaded file
//						setTimeout(function(){document.getElementById('oFiledrop'+nIndex).setAttribute('data-hashes', Filedrop('Send',oFile,arguments[2],arguments[3],nIndex,arguments[7],arguments[8],arguments[9],arguments[10]));}, nFiledropDelay);
						nFiledropDelay += 1000;
					}
				});
				nFiledropDelay = 0;								// this reset the value so another batch of uploads starts immediately instead of what the value ended with in the above .each loop
			});

			// React on successful iframe fallback upload (this is a separate mechanism for proper AJAX upload, hence another handler)
			oZone.event('iframeDone', function(xhr) {
				// if the repsonse contains 'ERROR', then...
				if (xhr.responseText.substr(0, 8) == '<f><msg>') {
					Project(_sProjectUI,'fail',xhr.responseText.substr(8, xhr.responseText.length - 18));
					return false;
				}
				if (xhr.responseText.substr(0, 7) == 'ERROR: ') {
					Project(_sProjectUI,'fail',"The following error has occurred while uploading the file(s):\n\n" + xhr.responseText.substr(7));
					return false;
				}
				// otherwise, we had a successful upload, so...
// MOVED 2025/07/03
//				var RESPONSE = xhr.responseText.split("\n");
//				var NAME = RESPONSE[1].substring((RESPONSE[1].indexOf("Stored name: ")+13));
//				var SIZE = RESPONSE[2].substring((RESPONSE[2].indexOf("Size: ")+6));
//				var CRC32B = RESPONSE[3].substring((RESPONSE[3].indexOf("CRC32B: ")+8));
//				var MD5 = RESPONSE[4].substring((RESPONSE[4].indexOf("MD5: ")+5));
//				var SHA256 = RESPONSE[5].substring((RESPONSE[5].indexOf("SHA256: ")+8));

				//alert('You have successfully uploaded the file(s) with these details:\n\n' + xhr.responseText);
				document.getElementById('oFiledrop'+nIndex).setAttribute('data-hashes',xhr.responseText.replace(/[\n]/g,'<br />'));

// MOVED 2025/07/03
//				if (CB != '') {									// if we have a callback function, then...
//					if (typeof CB === "function") {						//   if we have something like "function(){whatever(a,b,c);}" as this value, then call it! Also you can reference the just-uploaded-filename via the passed value (e.g. initFileDrop(...,function(NAME){alert(NAME);}) )
//						CB(NAME,SIZE,CRC32B,MD5,SHA256);
//					} else {								//   otherwise, we need to eval because the function was passed as a string (e.g. "whatever(a,b,c)") while replacing any reference to special names
//						var objMap = { FILENAME:NAME, FILESIZE:SIZE, CRCHASH:CRC32B, MD5HASH:MD5, 256HASH:SHA256 };	// https://stackoverflow.com/questions/15604140/replace-multiple-strings-with-multiple-other-strings
//						var reMatch = new RegExp(Object.keys(objMap).join("|"),"g");
//						CB = CB.replace(reMatch, function(matched){ return objMap[matched]; });
//
//						eval(CB);
//					}
//				}
				if (typeof arguments[12] === "function") {				//   if we have something like "function(){whatever(a,b,c);}" as this value, then call it! Also you can reference the just-uploaded-filename via the passed value (e.g. initFileDrop(...,function(NAME){alert(NAME);}) )
					arguments[12]();
				} else if (typeof(arguments[12]) === 'string' && arguments[12] != '') {	//   otherwise, we need to eval because the function was passed as a string (e.g. "whatever(a,b,c)") while replacing any reference to special names
					let RESPONSE = xhr.responseText.split("\n");
					let NAME = RESPONSE[1].substring((RESPONSE[1].indexOf("Stored name: ")+13));
					let SIZE = RESPONSE[2].substring((RESPONSE[2].indexOf("Size: ")+6));
					let CRC32B = RESPONSE[3].substring((RESPONSE[3].indexOf("CRC32B: ")+8));
					let MD5 = RESPONSE[4].substring((RESPONSE[4].indexOf("MD5: ")+5));
					let SHA256 = RESPONSE[5].substring((RESPONSE[5].indexOf("SHA256: ")+8));

					let oMap = { FILENAME:NAME, FILESIZE:SIZE, CRCHASH:CRC32B, MD5HASH:MD5, SHAHASH:SHA256 };	// https://stackoverflow.com/questions/15604140/replace-multiple-strings-with-multiple-other-strings
					let reMatch = new RegExp(Object.keys(oMap).join("|"),"g");
					arguments[12] = arguments[12].replace(reMatch, function(matched){ return oMap[matched]; });

					eval(arguments[12]);
				}
			});

			// A bit of sugar - toggling multiple selection
			fd.addEvent(fd.byID('multiple'), 'change', function(e){oZone.multiple(e.currentTarget || e.srcElement.checked);});
			break;




		   // OVERVIEW		Responsible for actually sending the file(s); supplemental to 'Init'
		   // SYNTAX		Filedrop('Send',oFile,sPath,sExtension='',nIndex=null,nLimit=0,nOverwrite=0,sRename='',sThumb='');
		case "Send":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: oFile		[object] The file object to manipulate									oFile
		   // 2: sPath		[string] The storage path for the uploaded file								'some/directory'
		   // 3: sExtension	[string] Comma separated path extension using elements, attributes, variables				"name,!variable"	['']
		   //			[ note ] Useful for variable parts of the path and can contain:
		   //				 (cookie_name),[element_name],<element_name|attribute_name>,{variable_name}
		   // 4: nIndex		[number] The index of the object set being manipulated (progress bar and filedrop instance)		2			[null]
		   // 5: nLimit		[number] The size limit, in bytes, for the uploaded file (0 disables)					1024			[0]
		   // 6: nOverwrite	[number] How file overwritting is to be handled	       [0=no, 1=yes, 2=yes despite different extension]	1			[0]
		   // 7: sRename	[string] The new name on the server for the uploaded file (preserving file extension)			'a_new_name'		['']
		   //			[ note ] Can supply a name or a special value: EPOCH (YYYYMMDDhhmmss), DATE (YYYYMMDD), TIME (hhmmss)
		   // 8: sThumb		[string] The size of a thumbnail to create of the uploaded file						'800x600'		['']
		   // 9: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
		   //	 NOTES
		   //			If the value is a string, several values will dynamically be replaced by actual values:
		   //			'FILENAME' will be replaced by the actual filename just uploaded
		   //			'FILESIZE' will be replaced by the actual file size
		   //			'CRCHASH' will be replaced by the CRC32B hash of the file just uploaded
		   //			'MD5HASH' will be replaced by the MD5 hash of the file just uploaded
		   //			'256HASH' will be replaced by the SHA256 hash of the file just uploaded
			// default value assignments
			if (arguments.length < 4) { arguments[3] = ''; }
			if (arguments.length < 5) { arguments[4] = null; }
			if (arguments.length < 6) { arguments[5] = 0; }
			if (arguments.length < 7) { arguments[6] = 0; }
			if (arguments.length < 8) { arguments[7] = ''; }
			if (arguments.length < 9) { arguments[8] = ''; }
			if (arguments.length < 10) { arguments[9] = ''; }

			// Update progress when browser reports it
			arguments[1].event('progress', function(current, total){
				var width = current / total * 100 + '%'
				fd.byID('oProgressBar'+arguments[4]).style.width = width;
			})

			// React to errors:
			arguments[1].event('error', function(e, xhr)
				{ Project(_sProjectUI,'fail','The following error has occurred while uploading the file(s):\n\n' + xhr.status + ': ' + xhr.statusText); });

			// React on successful AJAX upload:
			arguments[1].event('done', function(xhr){
				// if the repsonse contains 'ERROR', then...
				if (xhr.responseText.substr(0, 8) == '<f><msg>') {
					Project(_sProjectUI,'fail',xhr.responseText.substr(8, xhr.responseText.length - 18));
					return false;
				}
				if (xhr.responseText.substr(0, 7) == 'ERROR: ') {
					Project(_sProjectUI,'fail','The following error has occurred while uploading the file(s):\n\n' + xhr.responseText.substr(7));
					return false;
				}
				// otherwise, we had a successful upload, so...
				fd.byID('oProgressBar'+arguments[4]).style.width = '100%';			// so the progress bar always ends at 100%
// MOVED 2025/07/03 - this is now a return value from this function call
//				document.getElementById('divFileDrop'+arguments[4]).setAttribute('data-hashes',xhr.responseText.replace(/[\n]/g,'<br />'));

//				if (CB != '') {									// if we have a callback function, then...
					if (typeof arguments[9] === "function") {				//   if we have something like "function(){whatever(a,b,c);}" as this value, then call it! Also you can reference the just-uploaded-filename via the passed value (e.g. initFileDrop(...,function(NAME){alert(NAME);}) )
						arguments[9]();
					} else if (typeof(arguments[9]) === 'string' && arguments[9] != '') {	//   otherwise, we need to eval because the function was passed as a string (e.g. "whatever(a,b,c)") while replacing any reference to special names
						let RESPONSE = xhr.responseText.split("\n");
						let NAME = RESPONSE[1].substring((RESPONSE[1].indexOf("Stored name: ")+13));
						let SIZE = RESPONSE[2].substring((RESPONSE[2].indexOf("Size: ")+6));
						let CRC32B = RESPONSE[3].substring((RESPONSE[3].indexOf("CRC32B: ")+8));
						let MD5 = RESPONSE[4].substring((RESPONSE[4].indexOf("MD5: ")+5));
						let SHA256 = RESPONSE[5].substring((RESPONSE[5].indexOf("SHA256: ")+8));

						let oMap = { FILENAME:NAME, FILESIZE:SIZE, CRCHASH:CRC32B, MD5HASH:MD5, SHAHASH:SHA256 };	// https://stackoverflow.com/questions/15604140/replace-multiple-strings-with-multiple-other-strings
						let reMatch = new RegExp(Object.keys(oMap).join("|"),"g");
						arguments[9] = arguments[9].replace(reMatch, function(matched){ return oMap[matched]; });

						eval(arguments[9]);
					}
//				}

				return xhr.responseText.replace(/[\n]/g,'<br />');
			});

			// Send the file:
			var sExt = '';
			if (arguments[3] != '') {
				let oExt = arguments[3].split(',');						// if the developer needs to have multiple form object values added to the upload path, then obtain each objects name here
				for (let i=0; i<oExt.length; i++) {						// now go through each object and add it's value to the path
					let sFirst = oExt.substring(0, 1);

					if (sFirst == '(') { sExt += encodeURIComponent( Cookie('Obtain',oExt[i].slice(1,-1)) ) + '/'; }
					else if (sFirst == '[') { sExt += document.getElementById(oExt[i]).value + '/'; }
					else if (sFirst == '<') { sExt += document.getElementById(oExt[i].split('|')[0]).getAttribute(oExt[i].split('|')[1]) + '/'; }
					else if (sFirst == '{') { sExt += eval('encodeURIComponent('+oExt[i].slice(1,-1)+')') + '/'; }
				}
			}
			// WARNING:	We can NOT use POST to send additional data since the file stream is using that to send the file.	http://filedropjs.org/#scusdat
			//		As a result, do NOT send any private values as they will be exposed in the URL!!!			+'&username='+encodeURIComponent(document.getElementById('hidUsername').value)+'&SID='+encodeURIComponent(document.getElementById('hidSID').value)
			//		As a work around, do NOT allow access to the upload area if the user is not logged in.
			arguments[1].sendTo('code/_Filedrop.php?rename='+arguments[7]+'&overwrite='+arguments[6]+'&thumb='+arguments[8]+'&path='+encodeURIComponent(arguments[2])+'&ext='+encodeURIComponent(sExt)+'&limit='+arguments[5]);
			break;




		   // OVERVIEW		Displays a popup with the hashes of the just-uploaded file
		   // SYNTAX		Filedrop('Hashes',sVisibility,mHash,ePosition=null,mCallback='');
		case "Hashes":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sVisibility	[string] Whether to show or hide the popup						   [hide, show]	'show'
		   // 2: mHash		[string][object] The object that will show the hashes							"Hashes"
		   // 3: ePosition	[event] The passed event so the hash popup shows where the mouse cursor is				event			[null]
		   // 4: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			// default value assignments
			if (arguments.length < 4) { arguments[3] = null; }

			// perform task
			let oHash = (typeof arguments[2] === "object") ? arguments[2] : document.getElementById(arguments[2]);

			if (arguments[1] != 'show') {
				oHash.style.display = 'none';
			} else {
				oHash.style.display = 'block';
				oHash.innerHTML = This.getAttribute('data-hashes');
				if (arguments[3]) {								// if the user wants the 'hash span' to move were the cursor goes instead of a set-position showing, then...
					oHash.style.top = arguments[3].clientY + 'px';
					oHash.style.left = arguments[3].clientX + 'px';
				}
			}
			break;
	}


	// Perform any passed callback
	if (typeof(mCallback) === 'function') { mCallback(); }							// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
	else if (typeof(mCallback) === 'string' && mCallback != '') { eval(mCallback); }			// using this line, the value can be passed as: "alert('hello world');"


	// return desired results
	return true;
}










//  --- DEPRECATED/LEGACY ---

var intFileDropDelay = 0;
var oFileDropHash = null;					// used to store the <span> to display the file upload hashes


function initFileDrop(sFileDrop,sCheckID,sCheckVal,sPath,sPathExt,sRename,iOverwrite,sThumb,iSizeLimit,CB='') {
// initializes a filedrop instance within the opened module
// sFileDrop	the id of the filedrop instance that needs to initialized
// sCheckID	the id of the form object that needs to have a value (sCheckVal) checked before allowing uploads; blank disables
// sCheckVal	the default value of the form object (sCheckID) that prevents uploads
// sPath	the path where the uploads are going to be stored (this needs to be pre-escaped)	WARNING: end the path with a trailing '/'!!!
// sPathExt	the name of the form object(s) to extended the sPath by					WARNING: end the path with a trailing '/'!!!
//		NOTES:
//		- this value can also be used for variable parts of the path and can contain of any (mix) of the values listed on the following line!
//		- the values can be strings (as element id's), variables (via '!Variable'), attributes (via 'element>attribute'), or reference a global variable (via '@Variable' for entire path value)
//		- this can contain multiple values separated via the pipe (e.g. 'txtUsername|cmbModule')
// sRename	if the uploaded file should be renamed after it is uploaded (preserving the extension); can supply a name or a special value: EPOCH (YYYYMMDDhhmmss), DATE (YYYYMMDD), TIME (hhmmss)
// iOverwrite	if the uploaded file should overwrite one that exists with the same name; 0=no, 1=yes, 2=yes despite a different extension
// sThumb	if the uploaded file should have a thumbnail created for it; to enable this functionality pass the dimensions desired as the value in WxH format (e.g. 800x600)
// iSizeLimit	checks that the file being uploaded is less than the limit passed (in bytes); 0 disables
// [callback]	a string or function to call once the upload has been completed
//		NOTES:
//		- if the value is a string, several values will dynamically be replaced by actual values:
//			'FILENAME' will be replaced by the actual filename just uploaded
//			'FILESIZE' will be replaced by the actual file size
//			'CRCHASH' will be replaced by the CRC32B hash of the file just uploaded
//			'MD5HASH' will be replaced by the MD5 hash of the file just uploaded
//			'SHAHASH' will be replaced by the SHA256 hash of the file just uploaded

alert("initFileDrop() is deprecated; updated your code.");
return false;

	var options = {iframe: {url: 'code/_filedrop.php'}};					// Tell FileDrop we can deal with iframe uploads using this URL
	var zone = new FileDrop(sFileDrop, options);						// Attach FileDrop to an area ('zone' is an ID but you can also give a DOM node)

	// Do something when a user chooses or drops a file:
	zone.event('send', function(files) {
		// if a value was passed to check -AND- only allow uploads if a module record is loaded...
		if (sCheckID != '') {
			if (document.getElementById(sCheckID).type == "text") {
				if (document.getElementById(sCheckID).value == sCheckVal) {
					alert("You must enter a value before uploads are allowed.");
					return false;
				}
			} else if (document.getElementById(sCheckID).type == "select" || document.getElementById(sCheckID).type == "select-one" || document.getElementById(sCheckID).type == "select-multiple") {
				if (document.getElementById(sCheckID).selectedIndex == sCheckVal) {
					alert("You must select a record before uploads are allowed.");
					return false;
				}
			}
		}

		// if we've made it here, we're clear to begin uploads!
		files.each(function(file) {							// Depending on browser support files (FileList) might contain multiple items.
			if (iSizeLimit > 0 && iSizeLimit < file.size) {				// check that the iterated file is within the size limits (if provided)		https://stackoverflow.com/questions/4112575/client-checking-file-size-using-html5
				alert("The size of the \""+file.name+"\" file is larger than the limit allowed.");
				return false;
			}

			var index = document.getElementById(sFileDrop).getElementsByTagName('div').length-1;	// this is used so that each file upload is independent
			for (let i=0; i<document.getElementById(sFileDrop).getElementsByTagName('span').length; i++) {
				if (document.getElementById(sFileDrop).getElementsByTagName('span')[i].className == 'oHashes') {
					oFileDropHash = document.getElementById(sFileDrop).getElementsByTagName('span')[i];
					break;
				}
			}

			// create a file upload icon with progress bar in the FileDrop <div>
			document.getElementById(sFileDrop).innerHTML += "<div id='divFileDrop"+index+"' class='divFileDrop' title=\""+file.name+"\" data-hashes='Uploading...' onMouseOver=\"toggleHashes('show',this,oFileDropHash,event);\" onMouseOut=\"toggleHashes('hide',this,oFileDropHash,event);\"><img src='home/"+Cookie('Obtain','sUsername')+"/imgs/upload.png' onMouseOver=\"toggleHashes('show',this,oFileDropHash,event);\" onMouseOut=\"toggleHashes('hide',this,oFileDropHash,event);\" /><span id='progressBar"+index+"' class='spanProgressBar'></span></div>"

			// Reset the progress when a new upload starts
			file.event('xhrSend', function(){ fd.byID('progressBar'+index).style.width = 0; })

			// now queue the uploads
			if (sRename != 'EPOCH' && sRename != 'DATE' && sRename != 'TIME') {							// if we are not renaming the files, then there is no reason to queue/delay the upload, so process them immediately
				sendFileDrop(sFileDrop,sCheckID,sCheckVal,sPath,sPathExt,sRename,iOverwrite,sThumb,iSizeLimit,CB,file,index);
			} else {								// otherwise we need to queue/delay, by 1 second, each upload so the generated name does not overwrite another uploaded file
				setTimeout(function(){sendFileDrop(sFileDrop,sCheckID,sCheckVal,sPath,sPathExt,sRename,iOverwrite,sThumb,iSizeLimit,CB,file,index);}, intFileDropDelay);
				intFileDropDelay += 1000;
			}
		});
		intFileDropDelay = 0;								// this reset the value so another batch of uploads starts immediately instead of what the value ended with in the above .each loop
	});

	// React on successful iframe fallback upload (this is a separate mechanism for proper AJAX upload, hence another handler)
	zone.event('iframeDone', function(xhr){
		// if the repsonse contains 'ERROR', then...
		if (xhr.responseText.substr(0, 8) == '<f><msg>') {
			alert(xhr.responseText.substr(8, xhr.responseText.length - 18));
			return false;
		}
		if (xhr.responseText.substr(0, 7) == 'ERROR: ') {
			alert('The following error has occurred while uploading the file(s):\n\n' + xhr.responseText.substr(7));
			return false;
		}
		// otherwise, we had a successful upload, so...
		var RESPONSE = xhr.responseText.split("\n");
		var NAME = RESPONSE[1].substring((RESPONSE[1].indexOf("Stored name: ")+13));
		var SIZE = RESPONSE[2].substring((RESPONSE[2].indexOf("Size: ")+6));
		var CRC32B = RESPONSE[3].substring((RESPONSE[3].indexOf("CRC32B: ")+8));
		var MD5 = RESPONSE[4].substring((RESPONSE[4].indexOf("MD5: ")+5));
		var SHA256 = RESPONSE[5].substring((RESPONSE[5].indexOf("SHA256: ")+8));

		//alert('You have successfully uploaded the file(s) with these details:\n\n' + xhr.responseText);
		document.getElementById('divFileDrop'+index).setAttribute('data-hashes',xhr.responseText.replace(/[\n]/g,'<br />'));

		if (CB != '') {									// if we have a callback function, then...
			if (typeof CB === "function") {						//   if we have something like "function(){whatever(a,b,c);}" as this value, then call it! Also you can reference the just-uploaded-filename via the passed value (e.g. initFileDrop(...,function(NAME){alert(NAME);}) )
				CB(NAME,SIZE,CRC32B,MD5,SHA256);
			} else {								//   otherwise, we need to eval because the function was passed as a string (e.g. "whatever(a,b,c)") while replacing any reference to special names
				var objMap = { FILENAME:NAME, FILESIZE:SIZE, CRCHASH:CRC32B, MD5HASH:MD5, SHAHASH:SHA256 };	// https://stackoverflow.com/questions/15604140/replace-multiple-strings-with-multiple-other-strings
				var reMatch = new RegExp(Object.keys(objMap).join("|"),"g");
				CB = CB.replace(reMatch, function(matched){ return objMap[matched]; });

				eval(CB);
			}
		}
	});

	// A bit of sugar - toggling multiple selection
	fd.addEvent(fd.byID('multiple'), 'change', function(e){
		zone.multiple(e.currentTarget || e.srcElement.checked);
	});
}


function sendFileDrop(sFileDrop,sCheckID,sCheckVal,sPath,sPathExt,sRename,iOverwrite,sThumb,iSizeLimit,CB,file,index) {
// responsible for actually sending the file(s)

alert("sendFileDrop() is deprecated; updated your code.");
return false;

	// Update progress when browser reports it
	file.event('progress', function(current, total){
		var width = current / total * 100 + '%'
		fd.byID('progressBar'+index).style.width = width;
	})

	// React to errors:
	file.event('error', function(e, xhr){
		alert('The following error has occurred while uploading the file(s):\n\n' + xhr.status + ': ' + xhr.statusText);
	})

	// React on successful AJAX upload:
	file.event('done', function(xhr){
		// if the repsonse contains 'ERROR', then...
		if (xhr.responseText.substr(0, 8) == '<f><msg>') {
			alert(xhr.responseText.substr(8, xhr.responseText.length - 18));
			return false;
		}
		if (xhr.responseText.substr(0, 7) == 'ERROR: ') {
			alert('The following error has occurred while uploading the file(s):\n\n' + xhr.responseText.substr(7));
			return false;
		}
		// otherwise, we had a successful upload, so...
		var RESPONSE = xhr.responseText.split("\n");
		var NAME = RESPONSE[1].substring((RESPONSE[1].indexOf("Stored name: ")+13));
		var SIZE = RESPONSE[2].substring((RESPONSE[2].indexOf("Size: ")+6));
		var CRC32B = RESPONSE[3].substring((RESPONSE[3].indexOf("CRC32B: ")+8));
		var MD5 = RESPONSE[4].substring((RESPONSE[4].indexOf("MD5: ")+5));
		var SHA256 = RESPONSE[5].substring((RESPONSE[5].indexOf("SHA256: ")+8));

		fd.byID('progressBar'+index).style.width = '100%';				// so the progress bar always ends at 100%

		// 'this' here points to fd.File instance that has triggered the event.
		//alert('You have successfully uploaded the file(s) with these details:\n\n' + xhr.responseText);
		document.getElementById('divFileDrop'+index).setAttribute('data-hashes',xhr.responseText.replace(/[\n]/g,'<br />'));

		if (CB != '') {									// if we have a callback function, then...
			if (typeof CB === "function") {						//   if we have something like "function(){whatever(a,b,c);}" as this value, then call it! Also you can reference the just-uploaded-filename via the passed value (e.g. initFileDrop(...,function(NAME){alert(NAME);}) )
				CB(NAME,SIZE,CRC32B,MD5,SHA256);
			} else {								//   otherwise, we need to eval because the function was passed as a string (e.g. "whatever(a,b,c)") while replacing any reference to special names
				var objMap = { FILENAME:NAME, FILESIZE:SIZE, CRCHASH:CRC32B, MD5HASH:MD5, SHAHASH:SHA256 };	// https://stackoverflow.com/questions/15604140/replace-multiple-strings-with-multiple-other-strings
				var reMatch = new RegExp(Object.keys(objMap).join("|"),"g");
				CB = CB.replace(reMatch, function(matched){ return objMap[matched]; });

				eval(CB);
			}
		}
	});

	// Send the file:
	var ext = '';
	if (sPathExt != '') {
		var obj = sPathExt.split('|');							// if the developer needs to have multiple form object values added to the upload path, then obtain each objects name here
		for (var I=0; I<obj.length; I++) {						// now go through each object and add it's value to the path
			if (obj[I].indexOf('@') == 0)						//   if we need to use a global variable...
				{ ext += eval(obj[I].substring(1)) + '/'; }
			else if (obj[I].indexOf('!') == 0)					//   if we need to use a variable in a '|' separated list...
				{ ext += eval(obj[I].substring(1)) + '/'; }
			else if (obj[I].indexOf('>') > -1)					//   if we need to use an elements' attribute value...
				{ ext += document.getElementById(obj[I].split('>')[0]).getAttribute(obj[I].split('>')[1]) + '/'; }
			else if (document.getElementById(obj[I]).type == "text" || document.getElementById(obj[I]).type == "hidden")
				{ ext += document.getElementById(obj[I]).value + '/'; }
			else if (document.getElementById(obj[I]).type == "select" || document.getElementById(obj[I]).type == "select-one" || document.getElementById(obj[I]).type == "select-multiple")
				{ ext += document.getElementById(obj[I]).options[document.getElementById(obj[I]).selectedIndex].value + '/'; }
		}
	}
	// WARNING:	We can NOT use POST to send additional data since the file stream is using that to send the file.	http://filedropjs.org/#scusdat
	//		As a result, do NOT send any private values as they will be exposed in the URL!!!			+'&username='+encodeURIComponent(document.getElementById('hidUsername').value)+'&SID='+encodeURIComponent(document.getElementById('hidSID').value)
	//		As a work around, do NOT allow access to the upload area if the user is not logged in.
	file.sendTo('code/_filedrop.php?rename='+sRename+'&overwrite='+iOverwrite+'&thumb='+sThumb+'&path='+encodeURIComponent(sPath)+'&ext='+encodeURIComponent(ext)+'&limit='+iSizeLimit);
}


function toggleHashes(sAction,This,oHash,Event) {
// shows a popup with the hashes of the file just uploaded
// sAction	the action to execute within the function: show, hide
// This		pass the calling object into this function as 'this'
// oHash	the <span> which needs to contain the hash information
// Event	pass the event into this function as 'event'

alert("toggleHashes() is deprecated; updated your code.");
return false;

	if (oHash == null) { return true; }							// if there is nothing to store the hashes in, then no need to perform any actions
	var Elm = (typeof oHash === "object") ? oHash : document.getElementById(oHash);

	if (sAction != 'show') {
		Elm.style.display = 'none';
	} else {
		if (! Event) {									// if the user wants a set-position showing, then...
			Elm.style.display = 'block';
			Elm.innerHTML = This.getAttribute('data-hashes');
		} else {									// otherwise, move the 'hash span' were the cursor goes
			Elm.style.top = Event.clientY + 'px';
			Elm.style.left = Event.clientX + 'px';
		}
	}
}


