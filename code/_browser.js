// browser.js	a standard module that provides the relevant page IO.
//
// Created	2019-10-15 by Dave Henderson (dhenderson@cliquesoft.org or support@cliquesoft.org)
// Updated	2019-10-24 by Dave Henderson (dhenderson@cliquesoft.org or support@cliquesoft.org)
//
// Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
// device, this software is licensed under the Cliquesoft Public License (CPLv2)
// as found on the Cliquesoft website at www.cliquesoft.org.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.




var Mobile=0;						// used to indicate we're on a mobile device
var URI=new Object();					// used for processing the URI (for bookmarks/favorites)




// Mobile detection

function isMobile() {
// Check if we are on a mobile device
// http://stackoverflow.com/questions/3514784/what-is-the-best-way-to-detect-a-handheld-device-in-jquery
// http://stackoverflow.com/questions/11381673/javascript-solution-to-detect-mobile-browser
	if (/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/.test(navigator.userAgent.toLowerCase())) {
		jQuery.fx.off = true;				// globally toggle jQuery effects
		Mobile=1;
	}
}


// Browser detection					   https://stackoverflow.com/questions/9847580/how-to-detect-safari-chrome-ie-firefox-and-opera-browser

// Firefox 1.0+
var isFirefox = typeof InstallTrigger !== 'undefined';
// Chrome 1 - 71
var isChrome = !!window.chrome && (!!window.chrome.webstore || !!window.chrome.runtime);
// Opera 8.0+
var isOpera = (!!window.opr && !!opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') > -1;

// Safari 3.0+
var isSafari = /constructor/i.test(window.HTMLElement) || (function (p) { return p.toString() === "[object SafariRemoteNotification]"; })(!window['safari'] || (typeof safari !== 'undefined' && safari.pushNotification));
// Internet Explorer 6-11
var isIE = /*@cc_on!@*/false || !!document.documentMode || navigator.userAgent.indexOf("Trident") > -1;
// Edge 20+
var isEdge = (!isIE && !!window.StyleMedia) || navigator.userAgent.indexOf("Edg") > -1;


function parseURI() {
// Parse the URI and call a supplemental function so "bookmarks/favorites" work
// callback	[optional] the default function to call if no parameters are called (e.g. adjTab('home'))
// NOTES:
//	- this function can process simple hastags (e.g. #Account) and traditional URI's (e.g. p=default.html&i=18&...)
//	- if just a hashtag is passed (e.g. #Account), then that value is referenced via URI['hashtag']
//	- each key/value pair is accessed via URI[key]
//	- calls a supplemental function called "processURI" if it exists
//	- a default function call can be made if no URI parameters are passed
	var uri = window.location.href.substr(window.location.href.lastIndexOf('/')+1);	// store everything after the final '/'

	if (uri != '') {								// if we have a bookmark, then...
		if (/#.+$/.test(uri)) {							//    test if it's a hashtag
			var aryValues = /#.*/.exec(uri);				//        parse all the screens that need to be shown (e.g. content and news) based on the anchor values appended to the URL
			URI['_raw'] = 'hashtag='+aryValues[0].replace('#','');		//        removes the '#' symbol from the name (for use below) while also adding in the 'hashtag' "associative array" key name
		} else if (/\?.+$/.test(uri)) {						//    test if it's traditional value pairs (e.g. p=default.html&...)
			var aryValues = /\?.*/.exec(uri);				//        store the parameters appended to the URL
			URI['_raw']=aryValues[0].replace('?','');			//        removes the '?' symbol from the string (for use below)
		}
		if (/&/.test(URI['_raw']) || /=/.test(URI['_raw'])) {			// if there ARE any "field separation characters", then...
			if (/&/.test(URI['_raw'])) {
				aryValues = URI['_raw'].split('&');			//    split each key/value pair into an array
				for (var value of aryValues) {				//    for each of those pairs...
					var pair = value.split('=');			//        split the two into a temp array value
					URI[pair[0]] = pair[1];				//        store the each value in an "associative array"
				}
			} else if (/=/.test(URI['_raw'])) {				// otherwise, process the single parameter value
				var pair = URI['_raw'].split('=');
				URI[pair[0]] = pair[1];
			}
		}
		delete URI['_raw'];							// since we no longer need the 'raw' value, lets get rid of it!
		if (typeof processURI === 'function') { processURI(); }			// if it exists, now call the supplemental function to actually process the URI parameters

	} else if (arguments.length > 0) {						// if we have a passed a default function to call, then...
		if (typeof arguments[0] === "function") { arguments[0](); }
		else { eval(arguments[0]); }
	}
}

