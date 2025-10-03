// _Calendar.js
//
// Created	unknown by Dave Henderson (support@cliquesoft.org)
// updated	2025/06/26 by Dave Henderson (support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.




// -- Global Variables --

var __sCalendarMonths = new Array("January","February","March","April","May","June","July","August","September","October","November","December");
var __sCalendarDays = new Array("Sun","Mon","Tue","Wed","Thu","Fri","Sat");
var _CalendarTarget='';				// [auto-assigned] the id/object to receive the selected date; blank value disables
var _CalendarCallback;				// [string/function][optional] a callback function when selecting a data on the calendar




// -- Calendar API --

function Calendar(sAction) {
	// local variable assignments
	var mRequirements = true;										// used to indicate each function meets their requirements to run (and how many are mandatory if so, for further checks)
	var mCallback = null;											// the callback to perform

	switch(sAction) {
		case "Draw":
			if (arguments.length < 1) { mRequirements = false; } else { mRequirements = 1; }
			if (arguments.length > 6) { mCallback = arguments[6]; }
			break;
		case "Orient":
			if (arguments.length < 3) { mRequirements = false; } else { mRequirements = 3; }
			if (arguments.length > 4) { mCallback = arguments[4]; }
			break;
		case "Select":
			if (arguments.length < 2) { mRequirements = false; } else { mRequirements = 2; }
			if (arguments.length > 6) { mCallback = arguments[6]; }
			break;


		default:
			Project('Popup','fail',"ERROR: Calendar('"+sAction+"') is not a valid action for the function API.");
			return false;
	}

	// sanity checks
	if (typeof mRequirements == 'boolean' && ! mRequirements) {						// first check if the mandatory parameter count is met
		alert("ERROR: Calendar('"+sAction+"') was called without the sufficent number of parameters.");
		return false;
	} else if (typeof mRequirements == 'number') {								// second check if those mandatory parameters have an actual value
		for (let i=0; i<mRequirements; i++) {
			if (arguments[i] == '') {
				alert("ERROR: Calendar('"+sAction+"') was called with mandatory parameter #"+i+" being blank.");
				return false;
			}
		}
	}

	// lets perform some work!
	switch(sAction) {
		   // OVERVIEW			Returns the Calendar in HTML
		   // SYNTAX			var HTML = Calendar('Draw',mCalendar='',nDay=CURRENT,nMonth=CURRENT,nYear=CURRENT,bSameDate=true,mCallback='');
		case "Draw":			//											EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mCalendar		[string][object] the calendar object to hide post-selection				'divCalendar'		['']
		   // 2: nDay			[number] the day that was previously selected						22			[CURRENT]		NOTE: this is auto-assigned from Project('Calendar')
		   // 3: nMonth			[number] the month to process								6			[CURRENT]
		   // 4: nYear			[number] the year to process								2025			[CURRENT]
		   // 5: bSameDate		[boolean] if the calendar will allow same-date selection 				false			[true]
		   // 6: mCallback		[string][function] The callback to execute upon success					"alert('done!');"	['']
			// default value assignments
			var HTML = "";
			var iWIM = 0;		// this keeps track of (W)hich (I)terated (M)onth is being processed in aTBL (0=previous month, 1=current month, 2=next month)
			var aTBL = new Array(41);  // create an array for 42 slots (storing days of previous, current, and next month)
			var iCELL = 0;		// the iterated table cell
			var sCLASS = '';	// the class to assign the iterated day in the calendar

			var now = new Date;
			var nCurrDay = '';
			var nCurrMonth  = now.getMonth();							// stores the current/passed month and year
			var nCurrYear = now.getFullYear();
			var nNowDay  = now.getDate();								// stores todays day, month, and year (to identify todays date in the calendar via css)
			var nNowMonth = now.getMonth();
			var nNowYear = now.getFullYear();
			var nPrevMonth = 0;									// stores the index of the previous month (e.g. 0=Jan,1=Feb,2=Mar,...) and full year
			var nPrevYear = now.getFullYear();
			var nNextMonth = 2;									// stores the index of the previous month (e.g. 0=Jan,1=Feb,2=Mar,...) and full year
			var nNextYear = now.getFullYear();

			var sCalendar = (arguments.length < 2) ? '' : (typeof arguments[1] === "object") ? arguments[1].id : arguments[1];
			    nCurrDay =  (arguments.length < 3) ? parseInt(arguments[2]) : nCurrDay;		// store a previously selected day was passed (auto-assigned from Project('Calendar'))
			    nCurrMonth =(arguments.length < 4) ? parseInt(arguments[3]) : nCurrMonth;		// if the month was passed, then use that figure instead of the current month
			    nCurrYear = (arguments.length < 5) ? parseInt(arguments[4]) : nCurrYear;		// if the year was passed, then use that figure instead of the current year
			var bSameDate = (arguments.length < 6) ? true : (arguments[5] == false) ? false : true;

			if (nCurrMonth == 0) {									// if the current month IS January, then...
				nPrevMonth = 11;								//   store the previous month as December
				nPrevYear = nCurrYear - 1;							//   and set that year to the previous one as well
			} else { nPrevMonth = nCurrMonth - 1; }							// otherwise store the prior month; we already set the year above

			if (nCurrMonth == 11) {									// if the current month IS December, then...
				nNextMonth = 0;									//   store the next month as January
				nNextYear = nCurrYear + 1;							//   and set that year to the next one as well
			} else { nNextMonth = nCurrMonth + 1; }							// otherwise store the next month; we already set the year above

			var temp = new Date();									// create a temp date to find the day of the week (DOW) of the FIRST day of the current/passed month
			temp.setMonth(nCurrMonth);								//   set that dates' month to the current/passed month
			temp.setFullYear(nCurrYear);								//   set that dates' year to the current/passed year
			temp.setDate(1);									//   set 'temp' to the 1st day of the current/passed month
			var day1 = temp.getDay();								//   store the index of that day (e.g. 0=Sun,1=Mon,2=Tue,...)
			if (day1 == 0) { day1 = 7; }								//   if that day has an index of 0, change it to 7 so the below math works correctly	NOTE: this is the offset days from prior month in the indices of aTBL


			// stores the last days of the previous month						// EXAMPLES							day1=3 (Wednesday); nPrevMonth=31 (days)
			for (var i=0; i<day1; i++) {aTBL[i] = maxDays((nPrevMonth),nCurrYear) - day1 + i + 1;}	// ----------------------------------------------------------->	aTBL[0]=29	31(nPrevMonth) - 3(day1) + 0(i) + 1
			// stores all of the days for the current month										i=1 (first day of the month)	aTBL[1]=30	31(nPrevMonth) - 3(day1) + 1(i) + 1
			for (var i=1; i<=maxDays(nCurrMonth,nCurrYear); i++) { aTBL[i+day1-1] = i; }		// --------------------------->	aTBL[3]=1   1(i) + 3(day1) - 1	aTBL[2]=31	31(nPrevMonth) - 3(day1) + 2(i) + 1
			// fills the remaining array indices with the first days of the following month						aTBL[4]=2   2(i) + 3(day1) - 1
			for (var i=1; i<=(42 - day1 - maxDays(nCurrMonth,nCurrYear)); i++) {aTBL[maxDays(nCurrMonth,nCurrYear)+day1+i-1] = i;}	//... aTBL next index is 33	8(aTBL remaining indices = 41(aTBL total) - 3(day1) - 30(nCurrMonth)

			// construct the html table for the calendar
			HTML =	"<table class='tblCalendar'>\n";

			// define the Days of the Week header
			HTML +=	"   <tr>\n";
			for (var i=0; i<=6; i++) { HTML += "	<th>" + aryDays[i] + "</th>\n"; }
			HTML +=	"   </tr>\n";

			// construct the calendar with all the days stored in the array (including previous, current, and following months)
			for (var i=1; i<=6; i++) {								// for each row of the calendar
				HTML +=	"   <tr>\n";
				for (var j=1; j<=7; j++) {							// for each cell (in the column) of the table...
					if ((iWIM == 0 && aTBL[iCELL] == 1) || (iWIM == 1 && aTBL[iCELL] == 1)) { iWIM++; }

					sCLASS = '';								// (re)set the value each iteration
					if (iWIM == 1 && aTBL[iCELL]==nNowDay && nCurrMonth==nNowMonth && nCurrYear==nNowYear) {	// if the iterated day is today, then mark it as 'today'!
						sCLASS = 'aToday';
					} else {								// otherwise, lets do some further checks...
						if (iWIM == 0 || iWIM == 2)					//   if the day is in the prior/post month, then mark it as such!
							{ sCLASS = 'aNC'; }
						else								//   otherwise, lets check if it's a weekend or weekday!
							{ if (j==1 || j==7) {sCLASS='aWeekend';} else {sCLASS='aWeekday';} }
					}

					// if the iterated day is the one passed, then mark it as 'selected'!
					if (iWIM == 1 && nCurrDay && aTBL[iCELL]==nCurrDay) { sCLASS += ' aSel'; }

					if (iWIM == 0)								// if the user selected a day from the previous year, then...
						{ HTML +=	"	<td><a href='#' class='"+sCLASS+"' onClick=\"Calendar('select',this,'"+sCalendar+"',"+nPrevMonth+","+nPrevYear+","+bSameDate+");\">"+aTBL[iCELL]+"</a></td>\n"; }
					else if (iWIM == 1)							// or if selected one from the current month, then...
						{ HTML +=	"	<td><a href='#' class='"+sCLASS+"' onClick=\"Calendar('select',this,'"+sCalendar+"',"+nCurrMonth+","+nCurrYear+","+bSameDate+");\">"+aTBL[iCELL]+"</a></td>\n"; }
					else if (iWIM == 2)							// otherwise the selection is for the next month
						{ HTML +=	"	<td><a href='#' class='"+sCLASS+"' onClick=\"Calendar('select',this,'"+sCalendar+"',"+nNextMonth+","+nNextYear+","+bSameDate+");\">"+aTBL[iCELL]+"</a></td>\n"; }
					iCELL++;								// increment the array index by 1 each iteration so the next day can be written
				}
				HTML += "   </tr>\n";
			}
			HTML +=	"</table>\n";
			break;




		   // OVERVIEW			Orients the calendar at the specified coordinates
		   // SYNTAX			if (Calendar('Orient',mCalendar,eEvent,sPosition='right',mCallback='')) { ...yes... } else { ...no... }
		case "Orient":			//											EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: mCalendar		[string][object] The calendar object to orient						'divCalendar'
		   // 2: eEvent			[event] The passed event data (so we have access to various methods)			event
		   // 3: sPosition		[string] The position arrangement					  [left, right]	'left'			['right']
		   // 4: mCallback		[string][function] The callback to execute upon success					"alert('done!');"	['']
			var oCalendar = (typeof arguments[1] === "object") ? arguments[1] : document.getElementById(arguments[1]);

			if (arguments[2].clientX) {								// obtain the coordinates of the mouse click on the receiving object
				var nLeft = arguments[2].clientX;
				var nTop = arguments[2].clientY;
			} else {
				var nLeft = arguments[2].pageX;
				var nTop = arguments[2].pageY;
			}

			if (arguments[3] && arguments[3] == 'left') { nLeft -= 300; }				// if the calendar needs to be aligned on the left-hand side of the screen
			oCalendar.style.top = nTop + 'px';
			oCalendar.style.left = nLeft + 'px';
			break;




		   // OVERVIEW			Sets the date on the calendar and returns that value
		   // SYNTAX			Calendar('Select',oCell,mCalendar='',nMonth=CURRENT,nYear=CURRENT,bSameDate=true,mCallback='');
		case "Select":			//											EXAMPLES		[INDICATES OPTIONAL; DEFAULT VALUE]
		   // 1: oCell			[object] the cell that just got clicked							this
		   // 2: mCalendar		[string][object] The calendar object to hide post-selection				'divCalendar'		['']
		   // 3: nMonth			[number] the month to process								6			[CURRENT]
		   // 4: nYear			[number] the year to process								2025			[CURRENT]
		   // 5: bSameDate		[boolean] if the calendar will allow same-date selection 				false			[true]
		   // 6: mCallback		[string][function] The callback to execute upon success					"alert('done!');"	['']
			if (! arguments[5] && arguments[1].className.indexOf('aSel') > -1) { return 1; }	// if no duplicated dates are allowed -AND- the user clicked the date that is already selected, then exit

			// Update the css of the selected day
			var oCELLS = arguments[1].parentNode.parentNode.parentNode.getElementsByTagName('a');	// store all the <a> elements within the calendar to cycle below
			for (var I=0; I<oCELLS.length; I++)							// remove the 'selected' class from each cell in the table
				{ oCELLS[I].className = oCELLS[I].className.replace(/ aSel/g,''); }
			arguments[1].className += ' aSel';							// set the cell that was just clicked the having the 'selected' class

			// hide the calendar if there's a passed value
			if (arguments[2]) { document.getElementById(arguments[2]).style.display = 'none'; }

			// set/return the date selected
			if (_CalendarTarget) {
				_CalendarTarget.value = arguments[4] + '-' + (parseInt(arguments[3])+1) + '-' + arguments[1].innerHTML;
				_CalendarTarget = '';								// reset the variable value now that the object value has been set
			}
			break;
	}


	// Perform any passed callback
	if (typeof(mCallback) === 'function') { mCallback(); }							// using this line, the value can be passed as: whatever('...') -OR- function(){alert('hello world')}
	else if (typeof(mCallback) === 'string' && mCallback != '') { eval(mCallback); }			// using this line, the value can be passed as: "alert('hello world');"

	// Execute any stored callback
	if (typeof _CalendarCallback === "function") { _CalendarCallback(); _CalendarCallback=''; }
	else if (typeof(_CalendarCallback) === 'string' && _CalendarCallback != '') { eval(_CalendarCallback); _CalendarCallback=''; }


	// return desired results
	switch(sAction) {
		case "Draw":
			return HTML;
		case "Orient":
			return true;
		case "Select":
			return arguments[4] + '-' + (parseInt(arguments[3])+1) + '-' + arguments[1].innerHTML;
	}
}










//  --- DEPRECATED/LEGACY ---


var aryMonths = new Array("January","February","March","April","May","June","July","August","September","October","November","December");
var aryDays = new Array("Sun","Mon","Tue","Wed","Thu","Fri","Sat");
//var strCloseID = 'close.png';					// the name of the 'X' graphic to close the calendar

// non-editable global variables
var objCalTarget='';								// the id of the object to receive the selected date; blank value disables
var codeCallback='';								// any callback that should be executed upon clicking a date in the calendar - should be passed like "function(){...}"; blank value disables


// UPDATED 2025/02/19 - removed chrome from display, now just the calendar is returned
function writeCalendar(boolHide,boolClose) {
// this function creates the html code for the calendar
// boolHide	if this is set to 1 then upon clicking a date, the calendar will 'hide'.
// boolClose	if this is set to 1 then the calendar can be closed without selecting a date.

alert("writeCalendar() is deprecated; updated your code.");
return false;

	var now = new Date;
	var dow = now.getDay();
	var dd  = now.getDate();
	var mm  = now.getMonth();
	var yyyy = now.getFullYear();
	var cell = 0;

	var html = "<table class='tblCalendar'>";

	// draw the titlebar at the top of the calendar
	html += "<tr><td class='tdTitle' colspan='7'>";
//	if (boolClose) { html += "<img src='data/"+gbl_nameUser+"/_theme/images/"+strCloseID+"' onClick=\"codeCallback=''; document.getElementById('divCalendar').style.display='none';\" />"; }
//if (boolClose) { html += "<img src='home/"+gbl_nameUser+"/imgs/"+strCloseID+"' onClick=\"codeCallback=''; document.getElementById('divCalendar').style.display='none';\" />"; }
if (boolClose) { html += "<img src='home/guest/imgs/close.png' onClick=\"codeCallback=''; document.getElementById('divCalendar').style.display='none';\" />"; }
	html += "<select size='1' class='listbox' onChange=\"fillCalendar(this.parentNode.parentNode.parentNode);\">";
	for (var i=0; i<=11; i++) {						// cycle through the months and "select" the current month
		if (i==mm)
			{ html += "<option value='"+i+"' selected>"+(i+1)+' '+aryMonths[i]+"</option>"; }
		else
			{ html += "<option value='"+i+"'>"+(i+1)+' '+aryMonths[i]+"</option>"; }
	}
	html += "</select><span onClick=\"this.previousElementSibling.selectedIndex='"+mm+"'; this.nextElementSibling.value='"+yyyy+"'; fillCalendar(this.parentNode.parentNode.parentNode);\">Today</span><input type='textbox' value='"+yyyy+"' maxlength='4' class='textbox' onKeyUp=\"if(this.value.length==4){fillCalendar(this.parentNode.parentNode.parentNode);}\" /></td></tr>";

	// draw the Days of the Week header
	html += "<tr>";
	for (var i=0; i<=6; i++) { html += "<th>" + aryDays[i] + "</th>"; }
	html += "</tr>";

	// draw all the days within the calendar
	for (var i=0; i<=5; i++) {						// for each row of the calendar
		html += "<tr>";
		for (var j=0; j<=6; j++) {					// for each cell of the table...
			var CLASS='';						// (re)set the value each iteration
			if (j==0 || j==6) { CLASS='tdWeekend'; }

			html += "<td class='"+CLASS+"'><a href='#' onClick=\"selCalendarDay(this);";
			if (boolHide) { html += " codeCallback=''; document.getElementById('divCalendar').style.display='none';"; }
			html += "\">&nbsp;</a></td>";
			cell += 1;
		}
		html += "</tr>";
	}
	html += "</table>";
	document.write(html);
	fillCalendar('divCalendar');
}


function selCalendarDay(objCell) {
// this function performs several changes once a cell has been clicked which includes changing the background color, performing any callbacks, etc
// objCell	the cell of the calendar that was just clicked

alert("selCalendarDay() is deprecated; updated your code.");
return false;

	if (objCell.className.indexOf('aSel') > -1) { return 1; }		// if the user has selected the date that is already selected, then exit this routine

	var year  = objCell.parentNode.parentNode.parentNode.getElementsByTagName('input')[0];
	var month = objCell.parentNode.parentNode.parentNode.getElementsByTagName('select')[0];
	var cells = objCell.parentNode.parentNode.parentNode.getElementsByTagName('a');		// store all the <a> elements within the calendar to cycle below

	for (var I=0; I<cells.length; I++)					// remove the 'selected' class from each cell in the table (so that the one cell that does have it will be affected)
		{ cells[I].className = cells[I].className.replace(/ aSel/g,''); }
	objCell.className += ' aSel';						// set the cell that was just clicked the having the 'selected' class

	if (typeof codeCallback === "function") { codeCallback(); } else if (codeCallback != '') { eval(codeCallback); }	// if a callback was passed, then execute it!

	// set any passed object to the value of the date selected
	if (objCalTarget != '') { document.getElementById(objCalTarget).value = year.value + '-' + (parseInt(month.options[month.selectedIndex].value)+1) + '-' + objCell.innerHTML; }
}


function fillCalendar(Wrapper) {
// this function fills the calendar in with the appropriate date values
// Wrapper	the 'id' or the object itself that contains the calendar <table> from writeCalendar() above

alert("fillCalendar() is deprecated; updated your code.");
return false;

	if (typeof Wrapper === "string") { var wrapper = document.getElementById(Wrapper); } else { var wrapper = Wrapper; }
	var now = new Date;

	var dow = now.getDay();
	var dd = now.getDate();
	var mm = now.getMonth();
	var yyyy = now.getFullYear();
	var temp;

	var prevMonth;
	var currYear = parseInt(wrapper.getElementsByTagName('input')[0].value);
	var currMonth = parseInt(wrapper.getElementsByTagName('select')[0].value);
	var mmyyyy = new Date();
	var arrN = new Array(41);

	if (currMonth != 0) { prevMonth = currMonth - 1; } else { prevMonth = 11; }
	mmyyyy.setFullYear(currYear);
	mmyyyy.setMonth(currMonth);
	mmyyyy.setDate(1);
	var day1 = mmyyyy.getDay();
	if (day1 == 0) { day1 = 7; }

	for (var i=0; i<day1; i++) { arrN[i] = maxDays((prevMonth),currYear) - day1 + i + 1; }	// sets the last days of the previous month
	temp = 1;
	for (var i=day1; i<=day1+maxDays(currMonth,currYear)-1; i++) {		// sets the days for the current month
		arrN[i] = temp;
		temp += 1;
	}
	temp = 1;
	for (var i=day1+maxDays(currMonth,currYear); i<=41; i++) {		// sets the days for the next month
		arrN[i] = temp;
		temp += 1;
	}
	var dCount = 0;

	var cells = wrapper.getElementsByTagName('a');				// store all the <a> elements within the calendar to cycle below
	for (var i=0; i<cells.length; i++) {
		if (((i<7) && (arrN[i]>20)) || ((i>27) && (arrN[i]<20))) {	// for all "non current month dates"
			cells[i].innerHTML = arrN[i];
			cells[i].className = 'aNC';
		} else {							// for all current month dates
			cells[i].innerHTML = arrN[i];
			if (dCount==0 || dCount==6) { cells[i].className = 'aWeekend'; } else { cells[i].className = 'aWeekday'; }
			if (arrN[i]==dd && mm==currMonth && yyyy==currYear)
				{ cells[i].className = 'aToday'; }
		}
		dCount += 1;
		if (dCount > 6) { dCount=0; }
	}
}


function maxDays(mm, yyyy) {
// this is a complementary function to fillCalendar()
	var mDay;
	if((mm == 3) || (mm == 5) || (mm == 8) || (mm == 10)){
		mDay = 30;
	} else {
		mDay = 31;
		if (mm == 1) { if (yyyy/4 - parseInt(yyyy/4) != 0) {mDay = 28;} else {mDay = 29;} }
	}
	return mDay;
}
