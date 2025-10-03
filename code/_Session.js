// _Session.js
//
// Created	2012-08-15 by Dave Henderson (support@cliquesoft.org)
// Updated	2025-09-22 by Dave Henderson (support@cliquesoft.org)
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
// - This was originally two separate files: _login.js/php and _logout.js/php.
//   This has since been merged into this single file to conduct all the actions
//   from those files.




// -- Global Variables --

var _oSession;					// used for this modules' AJAX communication




// -- Session API --

function Session(sAction) {
	var HTML = "";

	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var mCallback = null;											// the callback to perform

	switch(sAction) {
		case "Authpage":
		case "Homepage":
			break;
		case "Login":
			if (arguments.length < 1) { mRequirements = false; } else { mRequirements = 1; }
			if (arguments.length > 1) { mCallback = arguments[1]; }
		case "s_Login":
			if (arguments.length > 1) { mCallback = arguments[1]; }
			break;
		case "Logout":
			if (arguments.length < 1) { mRequirements = false; } else { mRequirements = 1; }
			if (arguments.length > 1) { mCallback = arguments[1]; }
			break;
		case "s_Logout":
			if (arguments.length > 1) { mCallback = arguments[1]; }
			break;
		case "Authenticate":
			if (arguments.length < 1) { mRequirements = false; } else { mRequirements = 1; }
			if (arguments.length > 1) { mCallback = arguments[1]; }
			break;
		case "s_Authenticate":
			if (arguments.length > 1) { mCallback = arguments[1]; }
			break;
		case "f_Authenticate":
			break;
		case "Reset":
			if (arguments.length < 1) { mRequirements = false; } else { mRequirements = 1; }
			if (arguments.length > 1) { mCallback = arguments[1]; }
			break;
		case "s_Reset":
			if (arguments.length > 1) { mCallback = arguments[1]; }
			break;
		case "f_Reset":
			break;
		case "Unlock":
			if (arguments.length < 1) { mRequirements = false; } else { mRequirements = 1; }
			if (arguments.length > 1) { mCallback = arguments[1]; }
			break;
		case "c_Unlock":
			if (arguments.length > 1) { mCallback = arguments[1]; }
			break;
		case "v_Unlock":
			if (arguments.length > 1) { mCallback = arguments[1]; }
			break;
		case "s_Unlock":
			if (arguments.length > 1) { mCallback = arguments[1]; }
			break;
		case "f_Unlock":
			break;

		default:
			Project('Popup','fail',"ERROR: Session('"+sAction+"') is not a valid action for the function API.");
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
		   // OVERVIEW		Return the Projects Authentication URL
		   // SYNTAX		var URL = Session('Authpage');
		   //			location.href = Session('Authpage');
		case "Authpage":
			return location.href.substr(0, location.href.lastIndexOf('/')+1) + "default.php?p=Login";
			break;




		   // OVERVIEW		Return the Projects Homepage URL
		   // SYNTAX		var URL = Session('Homepage');
		   //			location.href = Session('Homepage');
		case "Homepage":
			return location.href.substr(0, location.href.lastIndexOf('/')+1);
			break;




		   // OVERVIEW		Shows Login Prompt
		   // SYNTAX		Session('Login',mCallback='');
		case "Login":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mCallback	[string][function] The callback to execute upon success							"alert('done!');"	['']
			Ajax('Call',_oSession,_sUriProject+"code/_Session.php",'A=check&T=settings','',function(){Session('s_'+sAction,mCallback);});
			break;
		case "s_Login":		// success!
		   // 1: mCallback	[string][function] The callback to execute upon success
			Cookie('Create','bUseCaptchas', DATA['bUseCaptchas']);

			HTML =	"<h3>&nbsp;Login&nbsp;</h3>" +
				"<div>" +
				"	<p>" +
				// ALT - an alternative message depending on the project
				//"		While all the information found here is open to the public, those that have a working account have additional" +
				//"		abilities like editing and creating issues. Use this form to login with your active account." +
				"		This program contains sensative information so you must login with an active account before gaining access." +
				"		If you have trouble logging in, try the services below or contact your support staff." +
				"	</p>" +
				"	<form action='' id='formLogin'>" +
				"	<ul>" +
				"		<li id='liMessage' onClick=\"$('#liMessage').toggle('slow'); this.innerHTML='';\">" +
				"		<li><label>Username</label><input type='textbox' id='Username' placeholder='Username' maxlength='32' class='textbox' onKeyUp=\"this.value=this.value.toLowerCase();\" onBlur=\"Security('Validate',this,'[^a-zA-Z0-9@\\\\._\\\\-]','Username');\" />" +
				"		<li><label>Password</label><input type='password' id='Password' maxlength='32' class='textbox encrypted' /><img src='home/guest/imgs/help.png' class='toggle' onClick=\"Security('TogglePassword','Password')\" title='Toggles the visibility of the password' />";
//VER2 - add one time passwords (OTP) and 2-factor authentication (2fa) options instead of just the password
			if (Cookie('Obtain','bUseCaptchas') == 'false') {
				HTML +=	"		<li><input type='button' id='oLogin' value='Login' class='button' />";
			} else {
				HTML +=	"		<li class='liCaptcha'><label>Captcha</label><input type='textbox' id='sCaptcha' placeholder='Captcha' maxlength='16' class='textbox' autocomplete='off' onBlur=\"Security('Validate',this,'[^a-zA-Z \\\\-]','Captcha');\" /><img src='home/guest/imgs/refresh.png' id='oRefresh' onclick=\"Captcha('Text');\" title=\"Refresh the captcha image\" /><input type='button' id='oLogin' value='Login' class='button' />" +
					"		<li class='liCaptcha'><img src='' id='oCaptcha' />";
			}
			HTML +=	"	</ul>" +
				"	</form>" +
				"	<form action='' id='formServices'>" +
				"	<ul>" +
				"		<li class='liReset'><label>Reset Password</label>&nbsp;" +
				"		<li class='liReset'><select id='sResetKey' size='1' class='listbox'><option value='username'>Username</option><option value='workEmail'>Email Address</option></select><input type='textbox' id='sResetValue' maxlength='128' class='textbox' placeholder='Answer' value='' onBlur=\"Security('Validate',this,'[^a-zA-Z0-9@\\\\._\\\\-]','Username');\" /><input type='button' id='oReset' value='Reset' class='button' />" +
				"		<li class='liUnlock'><label>Unlock Account</label>&nbsp;" +
				"		<li class='liUnlock'><select id='sUnlockKey' size='1' class='listbox'><option value='username'>Username</option><option value='workEmail'>Email Address</option></select><input type='textbox' id='sUnlockValue' maxlength='128' class='textbox' placeholder='Answer' value='' onBlur=\"Security('Validate',this,'[^a-zA-Z0-9@\\\\._\\\\-]','Username');\" /><input type='button' id='oUnlock' value='Unlock' class='button' />" +
				"		<li id='liQuestion' class='liChallenge'>" +
				"		<li id='liAnswer' class='liChallenge'>" +
				"	</ul>" +
				"	</form>" +
				"</div>";

			document.getElementById('divSignin').innerHTML = HTML;
			document.getElementById('oLogin').onclick = function(){ Session('Authenticate'); };					// NOTE: we have to add the onClick here because the Callback can be a function
			document.getElementById('oReset').onclick = function(){ Session('Reset'); };
			document.getElementById('oUnlock').onclick = function(){ Session('Unlock'); };

// REMOVED - 2025/09/22 - this is no longer applicable
//			Project('Popup','show');

			if (Cookie('Obtain','bUseCaptchas') == 'true') { Captcha('Text'); }
			document.getElementById('Username').focus();

			delete DATA['bUseCaptchas'];		// to prevent contamination between failed calls
			break;




		   // OVERVIEW		Logout the User From the Session
		   // SYNTAX		Session('Logout',mCallback='');
		case "Logout":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mCallback	[string][function] The callback to execute upon success							"alert('done!');"	['']
			Ajax('Call',_oSession,_sUriProject+"code/_Session.php",'A='+sAction.toLowerCase()+'&T=account&sUsername='+Cookie('Obtain','sUsername')+'&sSessionID='+escape(Cookie('Obtain','sSessionID')),'',function(){Session('s_'+sAction,mCallback);});
			break;
		case "s_Logout":	// success
		   // 1: mCallback	[string][function] The callback to execute upon success	
			// Delete the cookies storing the account info
			Cookie('Delete','sSessionID');
			Cookie('Delete','sUsername');
			Cookie('Delete','bAdmin');
			Cookie('Delete','bUseCaptchas');
			break;




		   // OVERVIEW		Authenticate User Account using the supplied credentials [complementary API function to 'Login']
		   // SYNTAX		Session('Authenticate',mCallback='');
		case "Authenticate":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mCallback	[string][function] The callback to execute upon success							"alert('done!');"	['']
			if (document.getElementById('Username').value == '') {
				DATA['issue'] = "username";
				MESSAGE="You must enter your account username before attempting to login.";
				Session('f_'+sAction,mCallback);
				return false;
			}
			if (document.getElementById('Password').value == '') {
				DATA['issue'] = "password";
				MESSAGE="You must enter your account password before attempting to login.";
				Session('f_'+sAction,mCallback);
				return false;
			}
			if (Cookie('Obtain','bUseCaptchas') == 'true') {
				if(document.getElementById('sCaptcha').value == '') {
					DATA['issue'] = "captcha";
					MESSAGE="You must enter the captcha text before attempting to login.";
					Session('f_'+sAction,mCallback);
					return false;
				}
			}

			Ajax('Call',_oSession,_sUriProject+"code/_Session.php",'A='+sAction.toLowerCase()+'&T=username','oLogin',function(){Session('s_'+sAction,mCallback);},function(){Session('f_'+sAction);},null,null,'formLogin');
			break;
		case "s_Authenticate":	// success
		   // 1: mCallback		[string][function] The callback to execute upon success
			var s_Account = PIPED.split("|");

			// --- MAKE VARIABLE ADJUSTMENTS ---

			// Store permanent values in hidden objects and/or cookies
			Cookie('Create','sSessionID', DATA['sSessionID']);
			Cookie('Create','sUsername', document.getElementById('Username').value);
			if (s_Account.length == 4) { Cookie('Create','FN', s_Account[0]); }			// if the project uses broken down names (e.g. tracker), then store the (F)irst (N)ame too
			if (DATA.hasOwnProperty("bAdmin") && DATA['bAdmin'] != '')
				{ Cookie('Create','bAdmin', DATA['bAdmin']); }					// store if the account is an admin or not (e.g. tracker); used to adjust the UI accordingly

			// --- MAKE GUI ADJUSTMENTS ---

// LEFT OFF - the below needs to be more generic (or most likely performed in the CALLBACK; probably the popup hiding below too - no GUI adjustments should be in these)
			// if these objects are present in the project (e.g. not changing to another screen after logging in), then...
			if (document.getElementById('sDisplayName')) { document.getElementById('sDisplayName').innerHTML = s_Account[0]; }	// this can be a username, first name, whole name, etc
			if (document.getElementById('sStatus_Dashboard')) {
				document.getElementById('sStatus_Dashboard').options[0].text = "Logged In";	// update the 'Status' listbox values to reflect an accurate login state
				document.getElementById('sStatus_Dashboard').options[1].text = "Logout";
			}

// REMOVED - 2025/09/22 - this is no longer applicable
//			// Hide the popup
//			Project('Popup','hide');

			location.href = DATA['sUriProject'];	// now go to the dashboard
			delete DATA['_sUriProject'];		// to prevent contamination between failed calls
			delete DATA['sSessionID'];
			delete DATA['bAdmin'];
			break;
		case "f_Authenticate":	// failure
			// if no message was passed, but we're supposed to report something, create a generic default
			if (MESSAGE == '') { MESSAGE = "An error has occurred trying to authenticate your account. Please try again in a few moments."; }

			// display the error message
			document.getElementById('liMessage').innerHTML = MESSAGE;
			if (document.getElementById('liMessage').style.display != 'block') {				// if the notice isn't already displayed, then...
				document.getElementById('liMessage').className = 'fail';				//   set the appropriate class
				$("#liMessage").show('slow');								//   fadein
				setTimeout(function(){$("#liMessage").hide('slow');}, 5000);				//   and set a 5 second fadeout call
			}

			// reset any prior modifications
			document.getElementById('sCaptcha').className = document.getElementById('sCaptcha').className.replace(/ fail/g,'');
			document.getElementById('Username').className = document.getElementById('Username').className.replace(/ fail/g,'');
			document.getElementById('Username').className = document.getElementById('Username').className.replace(/ warn/g,'');
			document.getElementById('Password').className = document.getElementById('Password').className.replace(/ fail/g,'');
			document.getElementById('sUnlockValue').className = document.getElementById('sUnlockValue').className.replace(/ disabled/g,'');
			switch(DATA['issue']) {
				case "captcha":
					setTimeout("$('.liCaptcha').animate({opacity: 0.1}, {duration: 2000,complete: function(){document.getElementById('sCaptcha').className += ' fail';$('.liCaptcha').animate({opacity: 1}, {duration: 2000});}});", 6000);
					document.getElementById('sCaptcha').focus();
					if (Cookie('Obtain','bUseCaptchas') == 'true') { Captcha('Text'); }
					break;
				// chain the next few...	   WARNING: they MUST go in that order!
				case "locked":
					setTimeout("$('.liUnlock').animate({opacity: 0.1}, {duration: 2000,complete: function(){$('.liUnlock').animate({opacity: 1}, {duration: 2000});}});", 6000);
					document.getElementById('sUnlockValue').focus();
				case "deleted":
				case "disabled":
				case "username":		// this is from the "case 'authenticate':" above, not in the php script
					setTimeout("$('#Username').fadeToggle('slow', function(){document.getElementById('Username').className += ' fail';$('#Username').fadeToggle('slow');});", 6000);
					document.getElementById('Username').focus();
					break;
				case "suspended":
					setTimeout("$('.liUnlock').animate({opacity: 0.1}, {duration: 2000,complete: function(){document.getElementById('sUnlockValue').className += ' disabled';$('.liUnlock').animate({opacity: 1}, {duration: 2000});}});", 12000);
					setTimeout("$('#Username').fadeToggle('slow', function(){document.getElementById('Username').className += ' fail';$('#Username').fadeToggle('slow');});", 6000);
					document.getElementById('Username').focus();
					break;
				case "password":
					setTimeout("$('.liReset').animate({opacity: 0.1}, {duration: 2000,complete: function(){$('.liReset').animate({opacity: 1}, {duration: 2000});}});", 8000);
					setTimeout("$('#Password').fadeToggle('slow', function(){document.getElementById('Password').className += ' fail';$('#Password').fadeToggle('slow');});", 6000);
					document.getElementById('Password').focus();
					if (Cookie('Obtain','bUseCaptchas') == 'true') { Captcha('Text'); }
					break;
				case "verfying":
					setTimeout("$('#Username').fadeToggle('slow', function(){document.getElementById('Username').className += ' warn';$('#Username').fadeToggle('slow');});", 6000);
					document.getElementById('Username').focus();
					break;
			}

			delete DATA['issue'];			// to prevent contamination between failed calls
			break;




		   // OVERVIEW		Reset Account Password
		   // SYNTAX		Session('Reset',mCallback='');
		case "Reset":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mCallback	[string][function] The callback to execute upon success							"alert('done!');"	['']
			if (Cookie('Obtain','sUsername')) {
				DATA['issue'] = "username";
				MESSAGE="No need to reset the password since you are already logged into your account!";
				Session('f_'+sAction,mCallback);
				return false;
			}

			if (document.getElementById('sResetValue').value == '') {
				DATA['issue'] = "reset";
				MESSAGE="You must provide the answer before resetting your account password.";
				Session('f_'+sAction,mCallback);
				
				return false;
			}

			Ajax('Call',_oSession,_sUriProject+"code/_Session.php",'A='+sAction.toLowerCase()+'&T=password','oReset',function(){Session('s_'+sAction,mCallback);},function(){Session('f_'+sAction);},null,null,'formServices');
			break;
		case "s_Reset":		// success
		   // 1: mCallback	[string][function] The callback to execute upon success
			// if no message was passed, but we're supposed to report something, create a generic default
			if (MESSAGE == '') { MESSAGE = "Your password has been reset succesfully!"; }

			// display the error message
			document.getElementById('liMessage').innerHTML = MESSAGE;
			if (document.getElementById('liMessage').style.display != 'block') {				// if the notice isn't already displayed, then...
				document.getElementById('liMessage').className = 'succ';				//   set the appropriate class
				$("#liMessage").show('slow');								//   fadein
				setTimeout(function(){$("#liMessage").hide('slow');}, 5000);				//   and set a 5 second fadeout call
			}
			break;
		case "f_Reset":		// failure
			// if no message was passed, but we're supposed to report something, create a generic default
			if (MESSAGE == '') { MESSAGE = "An error has occurred trying to reset your password. Please try again in a few moments."; }

			// display the error message
			document.getElementById('liMessage').innerHTML = MESSAGE;
			if (document.getElementById('liMessage').style.display != 'block') {				// if the notice isn't already displayed, then...
				document.getElementById('liMessage').className = 'fail';				//   set the appropriate class
				$("#liMessage").show('slow');								//   fadein
				setTimeout(function(){$("#liMessage").hide('slow');}, 5000);				//   and set a 5 second fadeout call
			}

			delete DATA['issue'];			// to prevent contamination between failed calls
			break;




		   // OVERVIEW		Unlock User Account
		   // SYNTAX		Session('Unlock',mCallback='');
		case "Unlock":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mCallback	[string][function] The callback to execute upon success							"alert('done!');"	['']
			if (Cookie('Obtain','sUsername')) {
				DATA['issue'] = "username";
				MESSAGE="No need to unlock your account since you are already logged in!";
				Session('f_'+sAction,mCallback);
				return false;
			}
			if (document.getElementById('sUnlockValue').value == '') {
				DATA['issue'] = "reset";
				MESSAGE="You must provide the answer before unlocking your account.";
				Session('f_'+sAction,mCallback);
				
				return false;
			}

			Ajax('Call',_oSession,_sUriProject+"code/_Session.php",'A='+sAction.toLowerCase()+'&SA=challenge&T=account','oUnlock',function(){Session('c_'+sAction,mCallback);},function(){Session('f_'+sAction);},null,null,'formServices');
			break;
		case "c_Unlock":	// success (challenge the user with a security question)
		   // 1: mCallback	[string][function] The callback to execute upon success
			// setup the challenge question for the user
			document.getElementById('liQuestion').innerHTML = PIPED;
			document.getElementById('liAnswer').innerHTML = "<input type='hidden' id='nQuestion' value='"+DATA['id']+"' /><input type='textbox' id='sAnswer' placeholder='Answer' class='textbox' autocomplete='off' question='"+DATA['id']+"' onBlur=\"Security('Validate',this,'![=<>;]','Answer');\" /><input type='button' id='oSubmit' value='Submit' class='button' />";
			$('.liChallenge').toggle('slow');
			document.getElementById('oSubmit').onclick = function(){ Session('v_Unlock',mCallback); }
			document.getElementById('sAnswer').focus();

			delete DATA['id'];			// to prevent contamination between failed calls
			break;
		case "v_Unlock":	// success (verify the answer to the security question)
		   // 1: mCallback	[string][function] The callback to execute upon success
			Ajax('Call',_oSession,_sUriProject+"code/_Session.php",'A=unlock&SA=verify&T=account&nQuestion='+document.getElementById('nQuestion').value+'&sAnswer='+escape(document.getElementById('sAnswer').value),'oSubmit',function(){Session('s_Unlock',mCallback);},function(){Session('f_Unlock');},null,null,'formServices');
			break;
		case "s_Unlock":	// success (to the entire process)
		   // 1: mCallback	[string][function] The callback to execute upon success
			// if no message was passed, but we're supposed to report something, create a generic default
			if (MESSAGE == '') { MESSAGE = "Your account has been unlocked successfully."; }

			// display the success message
			document.getElementById('liQuestion').innerHTML = '';
			document.getElementById('sAnswer').value = '';
			$('.liChallenge').toggle('slow');
			document.getElementById('liMessage').innerHTML = MESSAGE;
			if (document.getElementById('liMessage').style.display != 'block') {				// if the notice isn't already displayed, then...
				document.getElementById('liMessage').className = 'succ';				//   set the appropriate class
				$("#liMessage").show('slow');								//   fadein
				setTimeout(function(){$("#liMessage").hide('slow');}, 5000);				//   and set a 5 second fadeout call
			}

			// set focus to appropriate object
			document.getElementById('Username').focus();
			break;
		case "f_Unlock":	// failure
			// if no message was passed, but we're supposed to report something, create a generic default
			if (MESSAGE == '') { MESSAGE = "An error has occurred trying to unlock your account. Please try again in a few moments."; }

			// display the error message
			document.getElementById('liMessage').innerHTML = MESSAGE;
			if (document.getElementById('liMessage').style.display != 'block') {				// if the notice isn't already displayed, then...
				document.getElementById('liMessage').className = 'fail';				//   set the appropriate class
				$("#liMessage").show('slow');								//   fadein
				setTimeout(function(){$("#liMessage").hide('slow');}, 5000);				//   and set a 5 second fadeout call
			}

			delete DATA['issue'];			// to prevent contamination between failed calls
			break;
	}


	// Perform any passed callback
	if (sAction.substring(0,2) == 's_') {										// only execute these lines if a 'success' return has been made
		if (typeof(mCallback) === 'function') { mCallback(); }							// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
		else if (typeof(mCallback) === 'string' && mCallback != '') { eval(mCallback); }			// using this line, the value can be passed as: "alert('hello world');"
	}


	// reset the (form) objects
	MESSAGE = '';								// remove the action we were attempting now that we've achieved success


	// return desired results
	switch(sAction) {
		case "s_Login":
		case "s_Logout":
		case "s_Authenticate":
		case "s_Reset":
		case "c_Unlock":
		case "v_Unlock":
		case "s_Unlock":
			return true;
			break;
		case "f_Authenticate":
		case "f_Reset":
		case "f_Unlock":
			return false;
			break;
	}
}

