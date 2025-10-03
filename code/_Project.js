// _Project.js
//
// Created	2019-08-20 by Dave Henderson (support@cliquesoft.org)
// Updated	2025-07-03 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.




// -- Global Variables --

var _oProject;					// used for this modules' AJAX communication
var _sProjectUI = 'Alert';			// which UI method should the communcation be: Alert, Notice, Popup





// -- Project API --

function Project(sAction) {
	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var mCallback = null;											// the callback to perform

	switch(sAction) {
		case "Alert":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;
		case "Notice":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 3) { mCallback = arguments[3]; }
			break;
		case "Popup":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		case "Clipboard":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 2) { mCallback = arguments[2]; }
			break;
		case "Calendar":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 5) { mCallback = arguments[5]; }
			break;


		default:
			Project('Popup','fail',"ERROR: Project('"+sAction+"') is not a valid action for the function API.");
			return false;
	}

	// sanity checks
	if (typeof mRequirements == 'boolean' && ! mRequirements) {						// first check if the mandatory parameter count is met
		alert("ERROR: Project('"+sAction+"') was called without meeting a sufficent number of parameters.");
		return false;
	} else if (typeof mRequirements == 'number') {								// second check if those mandatory parameters have an actual value
		for (let i=0; i<mRequirements; i++) {
			if (arguments[i] == '') {
				alert("ERROR: Project('"+sAction+"') was called with mandatory parameter #"+i+" being blank.");
				return false;
			}
		}
	}

	// lets perform some work!
	switch(sAction) {
		   // OVERVIEW		Displays an alert to the user via the browsers alert()
		   // SYNTAX		Project('Alert',sType,sMessage=MESSAGE,mCallback='');
		case "Alert":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sType		[string] The type of notice						[debug, info, fail, succ, warn]	'info'
		   // 2: sMessage	[string] The message to display, or uses the MESSAGE variable otherwise					"Hello World"		[MESSAGE]
		   // 3: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			if (arguments[1] == 'info') { arguments[1] = '-notice-'; }
			if (arguments[1] == 'fail') { arguments[1] = 'failure...'; }
			if (arguments[1] == 'succ' || arguments[1] == 'success') { arguments[1] = 'success!'; }
			if (arguments[1] == 'warn') { arguments[1] = 'warning:'; }
			if (arguments[1] == 'debug') { arguments[1] = '[debug]'; }

			// perform task
			if (arguments.length < 3)
				{ alert(arguments[1].toUpperCase() + "\n" + MESSAGE); }				// display the stored message
			else
				{ alert(arguments[1].toUpperCase() + "\n" + arguments[2]); }			// display the passed message
			break;


		   // OVERVIEW		Toggle a Notice to the User
		   // SYNTAX		Project('Notice',sType,sMessage=MESSAGE,mCallback='');
		case "Notice":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sType		[string] The type of notice						[debug, info, fail, succ, warn]	'info'
		   // 2: sMessage	[string] The message to display, or uses the MESSAGE variable otherwise					"Hello World"		[MESSAGE]
		   // 3: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			var oNotice = document.getElementById('oNotice');
			var oLI = document.createElement("li");							// create a new <li> for the added notice

			oLI.className = arguments[1];								// assign the proper class
			oLI.innerHTML = (arguments.length < 3) ? MESSAGE : arguments[2];			// assign the proper message
			oLI.style.display = 'none';								// start out with it hidden so we can fade in
			//oNotice.appendChild(oLI);								// add the new <li> to the end of the notice listing
			oNotice.insertBefore(oLI, oNotice.children[0]);						// add the new <li> to the beginning of the notice listing
			$(oLI).fadeIn('slow');									// fadein
			setTimeout(function(){$(oLI).fadeOut('slow');}, 5000);					// and set a 5 second fadeout call

// UPDATED 2025/09/19
//			document.getElementById('oNotice').className = arguments[1];				// assign the proper class
//			if (arguments.length < 3)
//				{ document.getElementById('oNotice').innerHTML = MESSAGE; }			// display the stored message
//			else
//				{ document.getElementById('oNotice').innerHTML = arguments[2]; }		// display the passed message
//			if (document.getElementById('oNotice').style.display != 'block') {			// if the notice isn't already displayed, then...
//				$("#oNotice").fadeIn('slow');							//   fadein
//				setTimeout(function(){$("#oNotice").fadeOut('slow');}, 5000);			//   and set a 5 second fadeout call
//			}
			break;


		   // OVERVIEW		Toggle a Popup to the User
		   // SYNTAX		Project('Popup',sType,sMessage=MESSAGE,bOverlay=false,mCallback='');
		case "Popup":		//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: sType		[string] Type of notice, or toggle a preconfigured popup    [debug, info, fail, succ, warn, show, hide]	'info'
		   // 2: sMessage	[string] The message to display, or uses the MESSAGE variable otherwise					"Hello World"		[MESSAGE]
		   // 3: bOverlay	[boolean] If an overlay needs to be shown with the popup						true			[false]
		   // 4: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			var sTitle = '';
			var sImage = '';

			// default value assignments
			if (arguments.length < 3) { arguments[2] = MESSAGE; }
			if (arguments.length < 4) { arguments[3] = false; }

			// perform task
			if (arguments[1] == 'hide') {
				document.getElementById('divOverlay').style.display = 'none';
				document.getElementById('divPopup').style.display = 'none';

				// NOTE: this is so the mobile side works correctly; counters the 'default.js > showHelp()' and 'earn.js > showJob()'
				if (Mobile) { document.body.style.overflow = 'visible'; }			// NOTE: this MUST be reset to the original value to make this site work correctly
			} else {
				if (arguments[1] != 'show') {							// if the popup is not already populated with content, then lets add requested content
					if (arguments[1] == 'debug') { sTitle='[DEBUG]'; sImage='info'; }
					else if (arguments[1] == 'info') { sTitle='NOTICE'; sImage='info'; }
					else if (arguments[1] == 'fail') { sTitle='ERROR'; sImage='error'; }
					else if (arguments[1] == 'succ' || arguments[1] == 'success') { sTitle='SUCCESS'; sImage='info'; }
					else if (arguments[1] == 'warn') { sTitle='WARNING'; sImage='alert'; }

					HTML =	"<div id='divPopupClose' onClick=\"Project('Popup','hide');\">&times;</div>" +
						"<h3>&nbsp;"+sTitle+"&nbsp;</h3>" +
						"<div class='divBody divBodyFull'>" +
						"	<ul>" +
						"		<li class='fleft'><img src='home/guest/imgs/email_"+sImage+".png' />";
					if (arguments[2] == '')
						{ HTML += "		<li class='justify'>" + MESSAGE; }	// display the stored message
					else
						{ HTML += "		<li class='justify'>" + arguments[2]; }	// display the passed message
					HTML +=	"	</ul>" +
						"</div>";

					// display the popup to the user
					document.getElementById('divPopup').className = 'PopupMin';
					document.getElementById('divPopup').innerHTML = HTML;
				}

				// now show the popup
				document.getElementById('divPopup').style.display = 'block';
				if (arguments[3]) { document.getElementById('divOverlay').style.display = 'block'; }
			}
			break;




		   // OVERVIEW		Copy Textbox Content to Clipboard
		   // SYNTAX		Project('Clipboard',sType,sMessage=MESSAGE,bOverlay=false,mCallback='');
		case "Clipboard":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mTextbox	[string][object] The textbox to copy from								'name'
		   //			[ NOTE ] The element has to be visible for this function to work!!!
		   //				  https://www.w3schools.com/howto/howto_js_copy_clipboard.asp
		   // 2: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
			var oTextbox = (typeof arguments[2] === "object") ? arguments[1] : document.getElementById(arguments[1]);

			// Select the text field
			oTextbox.select();
			oTextbox.setSelectionRange(0, 99999);							// For mobile devices

			// Copy the text inside the text field
			document.execCommand("copy");

			// Alert the copied text
			Project('Notice','succ',"The text has been copied to the clipboard!");
			break;




		   // OVERVIEW		Display the Calendar; this is a wrapper function
		   // SYNTAX		Project('Calendar',eFunction='',nYear=CURRENT,nMonth=CURRENT,nDay=CURRENT,bHide=true,bSame=true,sPosition='right',mCallback='');
		   // NOTE		This was included here instead of _Calendar.js so that it can be designed to visually fit the project instead of a constant look
		case "Calendar":	//													EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: eFunction	[event] Whether to display (pass a value) or manipulate (pass no value) the calendar			event			['']
		   // 2: nYear		[number] The year to use instead of the current one							2000			[CURRENT]
		   // 3: nMonth		[number] The month to use instead of the current one							2			[CURRENT]
		   // 4: nDay		[number] The day to use instead of the current one							13			[CURRENT]
		   // 5: bHide		[boolean] If the calendar needs to hide post-selection							false			[true]
		   // 6: bSame		[boolean] If the calendar will allow same-date selection					 	false			[true]
		   // 7: sPosition	[string] position to display 							   [left, right, clear]	'left'			['right']
		   // 8: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']

		   // 1: mCallback	[string][function] The callback to execute upon success							"alert('hello');"	['']
		   // 2: eFunction	[event][optional] the passed event data (for access to various methods)	event		NOTE: the presence of this value indicates we are displaying the calendar instead of manipulating it
		   // 3: sPosition	[string][optional] position to display: left, right, clear [value]	'right'		[default]
		   // 4: bHide		[boolean][optional] the calendar object to hide post-selection		true		[default]
		   // 5: bSame		[boolean][optional] if the calendar will allow same-date selection 	true		[default]
		   // 6: nMonth		[number][optional] the month to display instead of the current month	6
		   // 7: nYear		[number][optional] the year to display instead of the current year	2025
		   // 8: nDay		[number][optional] if an existing date from arguments[2] needs loading	12		NOTE: this is auto-assigned
			var now = new Date;
			var nNowMonth = now.getMonth();				// store todays month & year so if the 'Today' link is clicked, we'll have those accurate values
			var nNowYear = now.getFullYear();
			var html = '';

			// Define default values or convey ones passed						retain the default values if 'null' was passed
			var nYear = (arguments.length > 2) ? arguments[2] : now.getFullYear();			if (nYear == null) { nYear = now.getFullYear(); }	// ditto for the year
			var nMonth = (arguments.length > 3) ? arguments[3] : now.getMonth();			if (nMonth == null) { nMonth = now.getMonth(); }	// if this was NOT passed, then set the current month as the value
			var nDay = (arguments.length > 4) ? arguments[4] : '';					if (nDay == null) { nDay = ''; }			// this is for the day (this is auto-assigned below)
			var bHide = (arguments.length > 5) ? arguments[5] : true;				if (bHide == null) { bHide = true; }
			var bSame = (arguments.length > 6) ? arguments[6] : true;				if (bSame == null) { bSame = true; }
			var sPosition = (arguments.length > 7) ? arguments[7] : 'right';			if (sPosition == null) { sPosition = 'right'; }
			if (mCallback) { _CalendarCallback = mCallback; }					// if we were passed a callback, then store it for later use

			if (_CalendarTarget && sPosition == 'clear') {
				_CalendarTarget.value = '';							// remove any prior date value from the receiving object
				if (bHide) { document.getElementById('divCalendar').style.display = 'none'; }	// hide the calendar if there's a passed value
				return true;
			}
			if (arguments[1] && ! _CalendarTarget) {
				_CalendarTarget = document.getElementById(arguments[1].target.id);
				if (_CalendarTarget.value != '') {						// if there is an existing date in the target object, then lets set the calendar to that date!
					var aDate = _CalendarTarget.value.split('-');
					Project('Calendar',arguments[1],parseInt(aDate[0]),(parseInt(aDate[1])-1),parseInt(aDate[2]),bHide,bSame,sPosition,mCallback);
					return true;
				}
			}

			html =	"<ul class='ulCalendar'>\n" +
				"	<li><select size='1' class='listbox' onChange=\"Project('Calendar',null,"+nYear+",this.value,null,"+bHide+","+bSame+");\">\n";
			for (var i=0; i<12; i++) {				// cycle through the months to fill out the listing, and "select" the current month in the process
				if (i==nMonth)
					{ html += "		<option value='"+i+"' selected>"+(i+1)+' '+aryMonths[i]+"</option>\n"; }
				else
					{ html += "		<option value='"+i+"'>"+(i+1)+' '+aryMonths[i]+"</option>\n"; }
			}
			html += "	   </select>" +
				"	<li><span onClick=\"Project('Calendar',null,"+nNowYear+","+nNowMonth+",null,"+bHide+","+bSame+");\">Today</span> &bull; <span onClick=\"Project('Calendar',null,null,null,null,"+bHide+",null,'clear');\">Clear</span>\n" +
				"	<li><input type='textbox' value='"+nYear+"' maxlength='4' class='textbox' onKeyUp=\"if(this.value.length==4){Project('Calendar',null,this.value,"+nMonth+",null,"+bHide+","+bSame+");}\" />\n" +
				"	<li><img src='home/"+Cookie('Obtain','sUsername')+"/imgs/close.png' onClick=\"_CalendarCallback=''; _CalendarTarget=''; document.getElementById('divCalendar').style.display='none';\" />\n" +
				"</ul>\n";

			if (bHide)
				{ html += Calendar('Draw','divCalendar',nDay,nMonth,nYear,bSame); }
			else
				{ html += Calendar('Draw',null,nDay,nMonth,nYear,bSame); }

			document.getElementById('divCalendar').innerHTML = html;
			if (arguments[1]) {					// if the calendar is just now being displayed on screen (unlike the calendar adjustments in the HTML code above), then...
				Calendar('Orient','divCalendar',arguments[1],sPosition);				// orient the calendar around the mouse click
				document.getElementById('divCalendar').style.display = 'block';				// display it on screen to the user
			}
			break;
	}


	// Perform any passed callback
	if (typeof(mCallback) === 'function') { mCallback(); }							// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
	else if (typeof(mCallback) === 'string' && mCallback != '') { eval(mCallback); }			// using this line, the value can be passed as: "alert('hello world');"


	// return desired results
	return true;
}

