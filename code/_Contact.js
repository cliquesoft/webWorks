// _Contact.js
//
// Created	2012-08-15 by Dave Henderson (support@cliquesoft.org)
// Updated	2025-08-22 by Dave Henderson (support@cliquesoft.org)
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
// - This was originally three separate files: _contact.js/php, _share.js/php,
//   and _mimemail.php.




// -- Global Variables --

var _oContact;					// used for this modules' AJAX communication




// -- Contact API --

function Contact(sAction) {
	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var mCallback = null;											// the callback to perform
	var AT = sAction.replace(/([a-z])([A-Z])/g, '$1 $2').toLowerCase().split(' ');				// https://stackoverflow.com/questions/18379254/regex-to-split-camel-case

	switch(sAction) {
		case "Others":
			if (arguments.length < 4) { mRequirements = false; } else { mRequirements = 4; }
			if (arguments.length > 5) { mCallback = arguments[5]; }
			break;
		case "Us":
			if (arguments.length < 1) { mRequirements = false; } else { mRequirements = 1; }
			if (arguments.length > 2) { mCallback = arguments[2]; }
			break;
		case "SendShare":
			if (arguments.length < 1) { mRequirements = false; } else { mRequirements = 1; }
			if (arguments.length > 2) { mCallback = arguments[2]; }
			break;


		default:
			Project('Popup','fail',"ERROR: Contact('"+sAction+"') is not a valid action for the function API.");
			return false;
	}

	// sanity checks
	if (typeof mRequirements == 'boolean' && ! mRequirements) {						// first check if the mandatory parameter count is met
		alert("ERROR: Contact('"+sAction+"') was called without meeting a sufficent number of parameters.");
		return false;
	} else if (typeof mRequirements == 'number') {								// second check if those mandatory parameters have an actual value
		for (let i=0; i<mRequirements; i++) {
			if (arguments[i] == '') {
				alert("ERROR: Contact('"+sAction+"') was called with mandatory parameter #"+i+" being blank.");
				return false;
			}
		}
	}

	// lets perform some work!
	switch(sAction) {
		   // OVERVIEW			Shows the share popup
		   // SYNTAX			Contact('Others',sName,sID,sType='referral',sTag='',mCallback='');
		case "Others":			//											EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sType			[string][object] the type of share	 [product, project, referral, support, website]	'product'
		   // 2: sName			[string] the reference name    [product|project|website=name, referral|support=contact]	'webBooks'
		   // 3: sID			[string] reference ID    [product=model,project=UPC,referral|support=email,website=url]	'1234'
		   // 4: sTag			[string] any tag to include								'Enterprise Ed'		['']
		   // 5: mCallback		[string][function] The callback to execute upon success					"alert('done!');"	['']
			// default value assignments
			if (arguments.length < 5) { arguments[4] = ''; }

			// perform task
			if (! Cookie('Obtain','sUsername') && arguments[1] == 'referral')
				{ if (!confirm("If you would like to link the referral to your account\n(for any applicable credit), you must be logged in\nbeforehand. Would you like to continue without\nbeing signed in?")) {return 0;} }

			var HTML =	"<div id='divPopupClose' onClick=\"Project('Popup','hide');\">&times;</div>";

			if (arguments[1] == 'referral')
				{ HTML += "<h3>&nbsp;Referral&nbsp;</h3>"; }
			else if (arguments[1] == 'support')
				{ HTML += "<h3>&nbsp;Support&nbsp;</h3>"; }
			else
				{ HTML += "<h3>&nbsp;Share&nbsp;</h3>"; }

			HTML +=	"<div class='divBody divBodyFull'>" +
				"	<p>";

			if (arguments[1] == 'referral')
				{ HTML += "		If you find value in what we have to offer, help spread the word about us to your friends and family!"; }
			else if (arguments[1] == 'support')
				{ HTML += "		Your communication helps with efforts to find resolutions, so the proper team will be notified with the included information."; }
			else if (arguments[1] == 'website')
				{ HTML += "		If you enjoy this website or find it helpful, chances are you probably know someone else that will like it too!"; }
			else	// product, project
				{ HTML += "		If you like this "+arguments[1]+", then chances are you probably know someone else that will like it too! "; }

			HTML +=	"		To help us connect with those people, we've included the below form for your convenience." +
				"	</p>" +
				"	<form action='' id='formContact'>" +
				"	<input type='hidden' id='sType' value='"+arguments[1]+"' /><input type='hidden' id='sName' value=\""+arguments[2]+"\" /><input type='hidden' id='sID' value='"+arguments[3]+"' /><input type='hidden' id='sTag' value=\""+arguments[4]+"\" />" +
				"	<ul>" +
				"		<li id='liMessage' onClick=\"$('#liMessage').toggle('slow'); this.innerHTML='';\">" +
				"		<li><label>Contact name</label><input type='textbox' id='sContact' maxlength='64' class='textbox' />" +
				"		<li><label>Contact email</label><input type='textbox' id='sEmail' maxlength='128' class='textbox' />" +
				"		<li><label>Optional message</label><textarea id='sMessage' class='textbox' maxlength='256' rows='3'></textarea>";

			if (Cookie('Obtain','bUseCaptchas') == 'false') {
				HTML += "		<li><input type='button' id='oSend' value='Send' class='button' onClick=\"Contact('SendShare');\" />";
			} else {
				HTML +=	"		<li><label>Captcha</label><input type='textbox' id='sCaptcha' placeholder='Captcha' maxlength='16' class='textbox' autocomplete='off' onBlur=\"Security('Validate',this,'[^a-zA-Z \\\\-]','Captcha',1);\" /><img src='home/guest/imgs/refresh.png' id='oRefresh' onclick=\"Captcha('Text');\" title=\"Refresh the captcha image\" /><input type='button' id='oSend' value='Send' class='button' onClick=\"Contact('SendShare');\" />" +
					"		<li><img src='' id='oCaptcha' />";
			}
			HTML += "	</ul>" +
				"	</form>" +
				"</div>";

			document.getElementById('divPopup').innerHTML = HTML;
			Project('Popup','show');
			if (Mobile) {						// if we are on a mobile device, move to the top (so the popup and overlay are shown correctly) along with disabling scrolling on the main document
				document.body.scrollTop = document.documentElement.scrollTop = 0;		// https://stackoverflow.com/questions/4210798/how-to-scroll-to-top-of-page-with-javascript-jquery
				document.body.style.overflow = 'hidden';
			}

			// Fill any form objects
			if (Cookie('Obtain','bUseCaptchas') == 'true') { Captcha('Text'); }

			document.getElementById('sContact').focus();		// focus the first form object
			break;




		   // OVERVIEW			Fills out the 'Contact Us' page contents
		   // SYNTAX			Contact('Us',...,mCallback='');
		case "Us":			//											EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
// LEFT OFF - fill this out; its the contents of the standard 'Contact Us' page; be sure to use the same naming conventions as in 'Share' above; this needs to call 'SendEmail'
			break;




		   // OVERVIEW			Sends our official (share/referral) communication to internal staff and third parties
		   // SYNTAX			Contact('SendShare',bMessage=true,mCallback='');
		case "SendShare":		//											EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: bMessage		[boolean] if the "optional" message is now mandatory					false			true
		   // 2: mCallback		[string][function] The callback to execute upon success					"alert('done!');"	['']
			if (document.getElementById('sContact').value == '')
				{ Project(_sProjectUI,'fail',"You must provide the contact name before proceeding."); return false; }
			if (document.getElementById('sEmail').value == '')
				{ Project(_sProjectUI,'fail',"You must provide the contact email address before proceeding."); return false; }
			if (! /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,11}$/.test(document.getElementById('sEmail').value)) {	// https://stackoverflow.com/questions/4964691/super-simple-email-validation-with-javascript
				Project(_sProjectUI,'fail',"The email address entered does not appear to be properly formatted. Please check your spelling and try again.");
				return false;
			}
			if (arguments[1] && document.getElementById('sEmail').value == '')
				{ Project(_sProjectUI,'fail',"You must provide a message before proceeding."); return false; }
			if (document.getElementById('sSubject')) {
				 if (document.getElementById('sSubject').value == '')
					{ Project(_sProjectUI,'fail',"You must provide a message subject before proceeding."); return false; }
			}
			if (Cookie('Obtain','bUseCaptchas') == 'true') {
				if (document.getElementById('sCaptcha').value == '')
					{ Project(_sProjectUI,'fail',"You must provide the captcha text before proceeding."); return false; }
			}

			// perform value validation
			if (! Security('Validate','sContact','a-zA-Z0-9 ,.-','Contact Name')) { return false; }
			if (! Security('Validate','sEmail','a-zA-Z0-9@._-','Contact Email')) { return false; }
			if (! Security('Validate','sMessage','!=<>;','Optional Message')) { return false; }

			Ajax('Call',_oContact,_sUriProject+"code/_Contact.php",'!'+AT[0]+'!,>'+AT[1]+'<,(sUsername),(sSessionID)','oSend',function(){Contact('s_'+sAction,mCallback);},function(){Contact('f_'+sAction);},null,null,'formContact');
			break;
		case "s_SendShare":		// success!
		   // 1: mCallback		[string][function] The callback to execute upon success
			Project(_sProjectUI,'succ');								// display returned message

			if (document.getElementById('divPopup').style.display != 'none') {			// if we're sharing something, then...
				if (_sProjectUI != 'Popup') { Project('Popup','hide'); }			//   hide the popup, unless the Project is configured to use a popup to display returned messages (cause the user will never see if there was success otherwise)
			} else {										// otherwise we're on the 'Contact Us' page, so...
				document.getElementById('formContact').reset();					//   reset the form
				if (Cookie('Obtain','bUseCaptchas') == 'true') { Captcha('Text'); }		//   and if we are using captchas, then refresh it
			}
			break;
		case "f_SendShare":		// failure...
			// display the error message		   NOTE: we have to do this since the divPopup is already in use with the login options
			document.getElementById('liMessage').innerHTML = MESSAGE;
			if (document.getElementById('liMessage').style.display != 'block') {				// if the notice isn't already displayed, then...
				document.getElementById('liMessage').className = 'fail';				//   set the appropriate class
				$("#liMessage").show('slow');								//   fadein
				setTimeout(function(){$("#liMessage").hide('slow');}, 5000);				//   and set a 5 second fadeout call
			}

			// reset the cookie since we had a failure
			if (Cookie('Obtain','bUseCaptchas') == 'true') { Captcha('Text'); }			// if we are using captchas, then refresh it
			break;




// VER2 - add in SendMessage (for IM); SendText (for sending text messages)
	}


	// Perform any passed callback
	if (typeof(mCallback) === 'function') { mCallback(); }							// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
	else if (typeof(mCallback) === 'string' && mCallback != '') { eval(mCallback); }			// using this line, the value can be passed as: "alert('hello world');"


	// reset the (form) objects
	MESSAGE = '';								// remove the action we were attempting now that we've achieved success


	// return desired results
	switch(sAction) {
		case "Others":
		case "Us":
		case "s_SendMessage":
			return true;
			break;
		case "f_SendMessage":
			return false;
			break;
	}
}










//  --- DEPRECATED/LEGACY ---


var reqShare;					// used to request the "Share" content via AJAX


function showShare(strAction,strType,strCategory,strName,strID) {
// displays the popup to allow a user to share with others, various communication regarding this project
// strType	the type of the share request, valid values:	product, project, (new account) referral, support, website
// strCategory	the optional category that the strType belongs to (like a custom tag)
// strName	the name of the referenced:	product (hardware name)	    project (software title)	referral (contact name)	   support (_sSupportName)     website (title)
// strID	the ID of the referenced:	product (hardware model)    project (software UPC)	referral (email address)   support (_sSupportEmail)    website (url)

alert("showShare() is deprecated; updated your code.");
return false;

   document.getElementById('divPopup').className = document.getElementById('divPopup').className.replace(/\s*PopupMin/g,'');
   document.getElementById('divPopup').className = document.getElementById('divPopup').className.replace(/\s*PopupMax/g,'');

   switch(strAction) {
	case "req":
		if (! getCookie('sUsername') && strType == 'person')
			{ if (!confirm("If you would like to link the referral to your account\n(for any applicable credit), you must be logged in\nbeforehand. Would you like to continue without\nbeing signed in?")) {return 0;} }

		var HTML = '';

		HTML =	"<div id='divPopupClose' onClick=\"togglePopup('hide');\">&times;</div>" +
			"<h3>&nbsp;Share&nbsp;</h3>" +
			"<div class='divBody divBodyFull'>" +
			"	<p>";

		if (strType == 'referral')
			{ HTML += "		If you find value in what we have to offer, help spread the word about us to your friends and family!"; }
		else if (strType == 'support')
			{ HTML += "		Your communication helps with efforts to find resolutions, so the proper help will be notified with the included information"; }
		else if (strType == 'website')
			{ HTML += "		If you enjoy this website or find it helpful, chances are you probably know someone else that will like it too!"; }
		else	// product, project
			{ HTML += "		If you like one of our "+strType+"s, chances are you probably know someone else that will like one too! "; }

		HTML +=	"		To help us connect with those people, we've included the below form for convenience." +
			"	</p>" +
			"	<form action='' id='formShare'>" +
			"	<input type='hidden' id='hidType' value='"+strType+"' /><input type='hidden' id='hidCategory' value=\""+strCategory+"\" /><input type='hidden' id='hidName' value=\""+strName+"\" /><input type='hidden' id='hidID' value='"+strID+"' />" +
			"	<ul>" +
			"		<li><label>Contact name</label><input type='textbox' id='txtContact' maxlength='64' class='textbox' onBlur=\"Security('Validate',this,'[^a-zA-Z0-9 _\\\\-]','Contact Name',1);\" />" +
			"		<li><label>Contact email</label><input type='textbox' id='txtContactEmail' maxlength='128' class='textbox' onBlur=\"Security('Validate',this,'[^a-zA-Z0-9\\\\.@_\\\\-]','Contact Email',1);\" />" +
			"		<li><label>Optional message</label><textarea id='txtContactMsg' class='textbox' maxlength='256' rows='3' onBlur=\"Security('Validate',this,'![=<>;]','Optional Message',1);\"></textarea>";

		if (! CAPTCHAS) {
			HTML += "		<li><input type='button' id='btnShare' value='Send' class='button' onClick=\"sendShare('req');\" />";
		} else {
			HTML += "		<li><label>Captcha</label><input type='textbox' id='sCaptcha' autocomplete='off' maxlength='16' class='textbox TTTextbox' /><img src='home/"+getCookie('sUsername')+"/imgs/refresh.png' id='imgCaptcha' onclick=\"reCaptcha('')\" title='Refresh the captcha image' /><input type='button' id='btnShare' value='Send' class='button' onClick=\"sendShare('req');\" />" +
				"		<li><img src='' id='objCaptcha' />";
		}
		HTML += "	</ul>" +
			"	</form>" +
			"</div>";

		togglePopup('show');
		document.getElementById('divPopup').innerHTML = HTML;
		if (Mobile) {						// if we are on a mobile device, move to the top (so the popup and overlay are shown correctly) along with disabling scrolling on the main document
			document.body.scrollTop = document.documentElement.scrollTop = 0;	// https://stackoverflow.com/questions/4210798/how-to-scroll-to-top-of-page-with-javascript-jquery
			document.body.style.overflow = 'hidden';
		}

		// Fill any form objects
		if (CAPTCHAS) { reCaptcha(''); }

		document.getElementById('txtContact').focus();		// focus the first form object
		break;
   }
}


function sendShare(strAction) {
// sends the actual (reply-less) submission from the above function

alert("sendShare() is deprecated; updated your code.");
return false;

   switch(strAction) {
	case "req":
		if (document.getElementById('txtContact').value == '') { alert("You must fill out the contacts name before proceeding."); return false; }
		if (document.getElementById('txtContactEmail').value == '') { alert("You must fill out the contacts email address before proceeding."); return false; }
		if (CAPTCHAS) { if(document.getElementById('sCaptcha').value == ''){alert("You must provide the captcha text before proceeding."); return false;} }
		if (! /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/.test(document.getElementById('txtContactEmail').value)) {		// https://stackoverflow.com/questions/4964691/super-simple-email-validation-with-javascript
			alert("The email address entered does not appear to be properly formatted. Please check your spelling and try again.");
			return false;
		}
alert('submitting');
		ajax(reqShare,4,'post',gbl_uriProject+"code/_share.php",'action=send&target=share&username='+escape(document.getElementById('hidUsername').value),'formShare','','','','',"sendShare('succ');","sendShare('fail');","sendShare('busy');","sendShare('timeout');","sendShare('inactive');");
		break;

	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		sendShare('req');
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		sendShare('req');
		break;
	case "succ":
alert('success');
		togglePopup('hide');
		break;
	case "fail":
		// obtain a new captcha if the user failed for any reason (if this was enabled)
		if (CAPTCHAS) { reCaptcha(''); }
		// the server-side script will handle any messages to the user
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}


// DEPRECATED 2025/02/15 - the same functionality is already handled in the above function (even passing the ID); plus there would be no reason to store the referral information locally

function sendReferral(strAction,intID) {
// sends the actual referral to the recipient that includes codes
// intID	the id of the account to obtain information from

alert("sendReferral() is deprecated; updated your code.");
return false;

   switch(strAction) {
	case "req":
		ajax(reqShare,4,'post',gbl_uriProject+"code/_share.php",'action=send&target=referral&id='+intID,'','','','','',"sendReferral('succ','"+intID+"');","sendReferral('fail','"+intID+"');","sendReferral('busy','"+intID+"');","sendReferral('timeout','"+intID+"');","sendReferral('inactive','"+intID+"');");
		break;

	case "busy":
		if (!confirm("There was already a request being processed.\nWould you like to retry?")) {return 0;}
		sendReferral('req',intID);
		break;
	case "timeout":
		if (!confirm("The request timed out communicating with the\nserver. Would you like to retry?")) {return 0;}
		sendReferral('req',intID);
		break;
	case "succ":
// LEFT OFF - convert the below lines to cookies
		document.getElementById('hidReferral').value = DATA['id'];
		document.getElementById('divReferralName').innerHTML = DATA['name'];
		delete DATA['id'];
		delete DATA['name'];
		break;
	case "fail":
		// the server-side script will handle any messages to the user
		break;
	case "inactive":
		// no reason to display anything because this section isn't applicable to this function
		break;
   }
}

