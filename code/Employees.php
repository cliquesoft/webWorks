<?php
# Employees.php
#
# Created	2014/01/30 by Dave Henderson (support@cliquesoft.org)
# Updated	2025/03/01 by Dave Henderson (support@cliquesoft.org)
#
# Unless a valid Cliquesoft Private License (CPLv1) has been purchased for your
# device, this software is licensed under the Cliquesoft Public License (CPLv2)
# as found on the Cliquesoft website at www.cliquesoft.org.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the appropriate Cliquesoft License for details.


# Constant Definitions
define("MODULE",'Employees');					# the name of this module (NOTE: this can be the same as the PROJECT constant in the envars.php file)
define("SCRIPT",basename($_SERVER['SCRIPT_NAME']));		# the name of this script (for tracing bugs and automated messages)

# Module Requirements						  NOTE: MUST come below Module Constant Definitions
require_once('../../sqlaccess');
require_once('../data/_modules/ApplicationSettings/config.php');
# DEPRECATED 2025/03/01
#require_once('../data/config.php');
#if (file_exists('../data/config.'.strtolower(MODULE).'.php')) { require_once('../data/config.'.strtolower(MODULE).'.php'); }
require_once('_mimemail.php');
require_once('_global.php');

# Start or resume the PHP session				  NOTE: gains access to $_SESSION variables in this script
session_start();




# format the dates in UTC
$_ = gmdate("Y-m-d H:i:s",time());		# used this mannor so all the times will be the exact same (also see http://php.net/manual/en/function.gmdate.php)




# define general info for any error generated below
$gbl_info['name'] = 'Unknown';
$gbl_info['contact'] = 'Unknown';
$gbl_info['other'] = 'n/a';


# define the maintenance function
function employees_maintenance() {
	global $gbl_errs,$gbl_info,$linkDB;

	$gbl_errs['error'] = "Failed to find the employee 'Pay Info' in the database when performing maintenance.";
	$gbl_info['command'] = "SELECT payDay,payTerm FROM ".PREFIX."BusinessConfiguration WHERE id='1'";
	$gbl_info['values'] = 'None';
	$Config = $linkDB->query($gbl_info['command']);
	$config = $Config->fetch_assoc();

	$today = new DateTime(date('Y-m-d'));
	$payday = new DateTime($config['payDay']);
	$days = $today->diff($payday)->format("%a");			# find the number of days between today and the first day that started the organizations billing cycle

	if ($config['payTerm'] == 'weekly') {				# occurs every 7 days
		if (($days % 7) == 0)					# if we are at the end of the weekly pay period, then...
			{ employees_recreate($config['payTerm']); }
	} else if ($config['payTerm'] == 'biweekly') {			# occurs every 14 days
		if (($days % 14) == 0)					# if we are at the end of the biweekly pay period, then...
			{ employees_recreate($config['payTerm']); }
	} else if ($config['payTerm'] == 'bimonthly') {			# occurs every 1st and 15th
		if (date('j') == '1' || date('j') == '15')		# if the day is the 1st or 15th, then...
			{ employees_recreate($config['payTerm']); }
	} else if ($config['payTerm'] == 'monthly') {			# occurs on the same day every month	WARNING: if the user selects a day greater than 28, then certain months may get skipped due to them not having that day in the month (e.g. there is no February 30th)
		if (date('j') == substr($config['payDay'], 5, 2))	# if the day of the month is the same as the selected initial payday day, then...
			{ employees_recreate($config['payTerm']); }
	}

# V2 - copy appropriate info from the 'Notes' section to the BBN if the organization has requested access to it
	return true;
}


function employees_recreate($payTerm) {
# this is a supplementary function for the employees_maintenance() function that performs the actual creation of the 'pay' and 'leave' records
	global $gbl_errs,$gbl_info,$linkDB,$_;

	$gbl_errs['error'] = "Failed to find employees in the database when creating pay/leave records.";
	$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees WHERE status<>'disabled'";
	$gbl_info['values'] = 'None';
	$Employee = $linkDB->query($gbl_info['command']);
	while ($employee = $Employee->fetch_assoc()) {			# cycle each non-disabled employee of the organization
		# obtain the employees prior pay period 'pay' record (which is the start date of the CURRENT pay period)
		$payments=array();

		$gbl_errs['error'] = "Failed to find employees in the database when creating pay/leave records.";
		$gbl_info['command'] = "SELECT time,memo FROM ".PREFIX."Employees_Timesheets WHERE employeeID='".$employees['id']."' AND type='pay' ORDER BY time DESC LIMIT 1";
		$gbl_info['values'] = 'None';
		$Pay = $linkDB->query($gbl_info['command']);
		if ($Pay->num_rows === 0) {				# if there isn't any prior records (e.g. a new employee), then assign default values
			$payments['PPPAP'] = 0.00;			# prior pay periods accrued pay
			$payments['YTDAP'] = 0.00;			# year-to-date accrued pay
			$payments['PPPAC'] = 0.00;			# prior pay periods accrued commissions
			$payments['YTDAC'] = 0.00;			# year-to-date accrued commissions
			$payments['PPPPR'] = 0.00;			# prior pay periods paid reimbursements
			$payments['YTDPR'] = 0.00;			# year-to-date paid reimbursements
		} else {
			$pay = $Pay->fetch_assoc();
			$Payments = explode("|", $pay['memo']);

			$payments['PPPAP'] = $Payments[0];
			$payments['YTDAP'] = $Payments[1];
			$payments['PPPAC'] = $Payments[2];
			$payments['YTDAC'] = $Payments[3];
# LEFT OFF - need to get the reimbursements figures working below
			$payments['PPPPR'] = 0.00;
			$payments['YTDPR'] = 0.00;
		}

		# obtain all the CURRENT pay periods timesheet records (after $pay['time'])
		$time = 0;

		$gbl_errs['error'] = "Failed to find iterated employees' timesheet in the database when creating pay/leave records.";
		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees_Timesheets WHERE employeeID='".$employees['id']."' AND type<>'pay' AND type<>'leave' AND time > '".$pay['time']."' ORDER BY time ASC";
		$gbl_info['values'] = 'None';
		$Current = $linkDB->query($gbl_info['command']);
		while ($current = $Current->fetch_assoc()) {
			if ($PAY != '' && $current['type'] == 'out') {			# if we reached the corresponding 'out' of an 'in' (in,pto,sick) record, then...
				$diff = strtotime($current['time']) - strtotime($PAY);	# returns in seconds based on the 'pto' time value and its corresponding 'out'
				$mins = floor($diff/60);				# converts to minutes
				$time += round($mins/60, 2);				# converts to fractional hours and stores in the array for inclusion in the XML below
			}

			$PAY = '';							# (re)set the values each iteration
			if ($current['type'] != 'unpaid') { $PAY = $current['time']; }
		}

		# calculate the CURRENT periods accrued pay and add it to the array based on the type of pay
		if ($employees['payType'] == 'hourly') {
			$wage = round($employees['basePay']/52/40, 2);			# annual 40-hour wage / 52 weeks in a year / 40 hours a week = hourly wage
			$ot = round($wage * $employees['OTRate'], 2);			# calculate the OT hourly wage

			if ($time <= 40) {						# if the employee has worked 40 hours or less, then pay the regular hourly wage
				$payments['PPPAP'] = round($time * $wage, 2);
			} else {							# otherwise, we need to add in OT pay, so...
				$diff = $time - 40;
				$payments['PPPAP'] = round(($time-$diff) * $wage, 2);	# add the first 40 hours at normal pay
				$payments['PPPAP'] += round(($time-40) * $ot, 2);	# add all the OT at OT pay
			}
			$payments['PPPAC'] = 0;						# store the commissions for this type of pay

		} else if ($employees['payType'] == 'salary') {
			if ($payTerm == 'weekly')
				{ $salary = round($employees['basePay']/52, 2); }	# annual salary / 52 weeks in a year = weekly salary
			else if ($payTerm == 'biweekly')
				{ $salary = round($employees['basePay']/26, 2); }	# annual salary / 26 weeks in a year = biweekly salary
			else if ($payTerm == 'bimonthly')
				{ $salary = round($employees['basePay']/24, 2); }	# annual salary / 24 pay periods in a year (twice/month) = bimonthly salary
			else if ($payTerm == 'monthly')
				{ $salary = round($employees['basePay']/12, 2); }	# annual salary / 12 months in a year = monthly salary

			$payments['PPPAP'] = round($salary, 2);				# store the employees accrued pay
			$payments['PPPAC'] = 0;						# store the commissions for this type of pay

		} else if ($employees['payType'] == 'salary+ot') {
			$wage = round($employees['basePay']/52/40, 2);			# annual salary / 52 weeks in a year / 40 hours a week = hourly wage
			$ot = round($wage * $employees['OTRate'], 2);			# calculate the OT hourly wage

			# calculate the normal salary wage for under 40 hours worked
			if ($payTerm == 'weekly')
				{ $salary = round($employees['basePay']/52, 2); }	# annual salary / 52 weeks in a year = weekly salary
			else if ($payTerm == 'biweekly')
				{ $salary = round($employees['basePay']/26, 2); }	# annual salary / 26 weeks in a year = biweekly salary
			else if ($payTerm == 'bimonthly')
				{ $salary = round($employees['basePay']/24, 2); }	# annual salary / 24 pay periods in a year (twice/month) = bimonthly salary
			else if ($payTerm == 'monthly')
				{ $salary = round($employees['basePay']/12, 2); }	# annual salary / 12 months in a year = monthly salary

			if ($time <= 40) {						# if the employee has worked 40 hours or less, then pay the regular salary
				$payments['PPPAP'] = $salary;
			} else {							# otherwise, we need to add in OT pay, so...
				$diff = $time - 40;
				$payments['PPPAP'] = $salary;				# add the first 40 hours at normal salary pay
				$payments['PPPAP'] += round(($time-40) * $ot, 2);	# add all the OT at OT pay
			}
			$payments['PPPAC'] = 0;						# store the commissions for this type of pay

		} else if ($employees['payType'] == 'commission') {			# this is for the sales staff
			$commission = 0;
			# get all the invoices (from the CURRENT pay period) where the employee is the sales rep for the customer account of the invoice
			$gbl_errs['error'] = "Failed to find iterated employees' associated invoices in the database when creating pay/leave records.";
			$gbl_info['command'] = "SELECT tblQI.id FROM ".PREFIX."QuotesAndInvoices tblQI LEFT JOIN ".PREFIX."CustomerAccounts tblCA ON tblQI.acctID = tblCA.id WHERE tblQI.type='invoice' AND tblQI.createdOn > '".$pay['time']."' AND tblCA.salesRepID='".$employees['id']."'";
			$gbl_info['values'] = 'None';
			$QI = $linkDB->query($gbl_info['command']);
			while ($qi = $QI->fetch_assoc()) {
				# cycle the history details of each invoice to tally all the commissioned items
				$gbl_errs['error'] = "Failed to find iterated employees' iterated invoice values in the database when creating pay/leave records.";
				$gbl_info['command'] = "SELECT tblQIH.qty,tblQIH.bo,tblQIH.price,tblINV.commissionAMT FROM ".PREFIX."QuotesAndInvoices_History tblQIH LEFT JOIN ".PREFIX."Inventory tblINV ON tblQIH.idcode = tblINV.id WHERE tblQIH.rowID='".$qi['id']."' AND tblINV.commission='1'";
				$gbl_info['values'] = 'None';
				$Item = $linkDB->query($gbl_info['command']);
				while ($item = $Item->fetch_assoc()) {
					if (strpos($item['commissionAMT'], '$') !== false)		# if there is a fixed commission, then...
						{ $commission += ($item['qty'] + $item['bo']) * str_replace('$', '', $item['commissionAMT']); }
					else if (strpos($item['commissionAMT'], '%') !== false)		# if there is a percentage commission, then...
						{ $commission += ($item['qty'] + $item['bo']) * $item['price'] * (str_replace('%', '', $item['commissionAMT']) / 100); }
				}
			}
			$payments['PPPAP'] = 0;						# there is no pay for this type
			$payments['PPPAC'] = round($commission, 2);			# add the tallied commissions

		} else if ($employees['payType'] == 'commission+base') {		# this is for the sales staff
			# calculate the base pay
			if ($payTerm == 'weekly')
				{ $base = round($employees['basePay']/52, 2); }		# base pay / 52 weeks in a year = weekly pay
			else if ($payTerm == 'biweekly')
				{ $base = round($employees['basePay']/26, 2); }		# base pay / 26 weeks in a year = biweekly pay
			else if ($payTerm == 'bimonthly')
				{ $base = round($employees['basePay']/24, 2); }		# base pay / 24 pay periods in a year (twice/month) = bimonthly pay
			else if ($payTerm == 'monthly')
				{ $base = round($employees['basePay']/12, 2); }		# base pay / 12 months in a year = monthly pay

			# calculate the commissions
			$commission = 0;
			# get all the invoices (from the CURRENT pay period) where the employee is the sales rep for the customer account of the invoice
			$gbl_errs['error'] = "Failed to find iterated employees' associated invoices in the database when creating pay/leave records.";
			$gbl_info['command'] = "SELECT tblQI.* FROM ".PREFIX."QuotesAndInvoices tblQI LEFT JOIN ".PREFIX."CustomerAccounts tblCA ON tblQI.acctID = tblCA.id WHERE tblQI.type='invoice' AND tblQI.createdOn > '".$pay['time']."' AND tblCA.salesRepID='".$employees['id']."'";
			$gbl_info['values'] = 'None';
			$QI = $linkDB->query($gbl_info['command']);
			while ($qi = $QI->fetch_assoc()) {
				# cycle the history details of each invoice to tally all the commissioned items
				$gbl_errs['error'] = "Failed to find iterated employees' iterated invoice values in the database when creating pay/leave records.";
				$gbl_info['command'] = "SELECT tblQIH.qty,tblQIH.bo,tblQIH.price,tblINV.commissionAMT FROM ".PREFIX."QuotesAndInvoices_History tblQIH LEFT JOIN ".PREFIX."Inventory tblINV ON tblQIH.idcode = tblINV.id WHERE tblQIH.rowID='".$qi['id']."' AND tblINV.commission='1'";
				$gbl_info['values'] = 'None';
				$Item = $linkDB->query($gbl_info['command']);
				while ($item = $Item->fetch_assoc()) {
					if (strpos($item['commissionAMT'], '$') !== false)		# if there is a fixed commission, then...
						{ $commission += ($item['qty'] + $item['bo']) * str_replace('$', '', $item['commissionAMT']); }
					else if (strpos($item['commissionAMT'], '%') !== false)		# if there is a percentage commission, then...
						{ $commission += ($item['qty'] + $item['bo']) * $item['price'] * (str_replace('%', '', $item['commissionAMT']) / 100); }
				}
			}
			$payments['PPPAP'] = round($base, 2);				# add the base pay
			$payments['PPPAC'] = round($commission, 2);			# add the tallied commissions

		} else if ($employees['payType'] == 'job' || $employees['payType'] == 'piece') {	# this is for the 'grunt' workers
			$jobs = 0;
			# store all the invoices from the CURRENT pay period that were created by the employee		http://stackoverflow.com/questions/581521/whats-faster-select-distinct-or-group-by-in-mysql
			$gbl_errs['error'] = "Failed to find iterated employees' associated invoices in the database when creating pay/leave records.";
			$gbl_info['command'] = "SELECT DISTINCT jobNo,id FROM ".PREFIX."QuotesAndInvoices WHERE type='invoice' AND createdOn > '".$pay['time']."' AND createdBy='".$employees['id']."'";
			$gbl_info['values'] = 'None';
			$QI = $linkDB->query($gbl_info['command']);
			if ($QI->num_rows !== 0) { $jobs = $QI->num_rows; }

			$payments['PPPAP'] = round($employees['basePay'] * $jobs, 2);	# add the per-job pay * the number of jobs completed
			$payments['PPPAC'] = 0;						# store the commissions for this type of pay
		}


		# obtain the employees prior pay period 'leave' record (which is the start date of the CURRENT pay period)
		$timeoff=array();
		$gbl_errs['error'] = "Failed to find iterated employees' prior pay period leave record in the database when creating pay/leave records.";
		$gbl_info['command'] = "SELECT time,memo FROM ".PREFIX."Employees_Timesheets WHERE employeeID='".$employees['id']."' AND type='leave' ORDER BY time DESC LIMIT 1";
		$gbl_info['values'] = 'None';
		$Leave = $linkDB->query($gbl_info['command']);
		if ($Leave->num_rows === 0) {					# if there isn't any prior record (e.g. a new employee), then assign default values
			$timeoff['PPPAP'] = 0.00;				# prior pay periods accrued pto
			$timeoff['YTDAP'] = 0.00;				# year-to-date accrued pto (less the used pto - to show available leave)
			$timeoff['PPPUP'] = 0.00;				# prior pay periods used pto
			$timeoff['YTDUP'] = 0.00;				# year-to-date used pto
			$timeoff['PPPAS'] = 0.00;				# prior pay periods accrued sick leave
			$timeoff['YTDAS'] = 0.00;				# year-to-date accrued sick leave (less the used sick leave - to show available leave)
			$timeoff['PPPUS'] = 0.00;				# prior pay periods used pto
			$timeoff['YTDUS'] = 0.00;				# year-to-date used sick leave
		} else {
			$leave = $Leave->fetch_assoc();
			$Timeoff = explode("|", $leave['memo']);		# populate the below array with the prior pay periods leave values

			$timeoff['PPPAP'] = $Timeoff[0];
			$timeoff['YTDAP'] = $Timeoff[1];
			$timeoff['PPPUP'] = 0.00;				# set default value
			$timeoff['YTDUP'] = $Timeoff[2];
			$timeoff['PPPAS'] = $Timeoff[3];
			$timeoff['YTDAS'] = $Timeoff[4];
			$timeoff['PPPUS'] = 0.00;				# set default value
			$timeoff['YTDUS'] = $Timeoff[5];
		}

		# calculate the CURRENT periods accrued leave and add it to the array (using the $pay['time'] acquired from above)
		$PTO = 0;
		$SICK = 0;

		$gbl_errs['error'] = "Failed to find iterated employees' timesheet record in the database when creating pay/leave records.";
		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees_Timesheets WHERE employeeID='".$employees['id']."' AND type<>'pay' AND type<>'leave' AND time > '".$pay['time']."' ORDER BY time ASC";
		$gbl_info['values'] = 'None';
		$Current = $linkDB->query($gbl_info['command']);
		while ($current = $Current->fetch_assoc()) {
			if ($pto != '' && $current['type'] == 'out') {			# if we reached the corresponding 'out' of a 'pto' record, then...
				$diff = strtotime($current['time']) - strtotime($pto);	# returns in seconds based on the 'pto' time value and its corresponding 'out'
				$mins = floor($diff/60);				# converts to minutes
				$PTO += round($mins/60, 2);				# converts to fractional hours and stores the value
			} else if ($sick != '' && $current['type'] == 'out') {		# if we reached the corresponding 'out' of a 'sick' record, then...
				$diff = strtotime($current['time']) - strtotime($sick);
				$mins = floor($diff/60);
				$SICK += round($mins/60, 2);
			}

			$pto = '';					# (re)set the values each iteration
			$sick = '';
			if ($current['type'] == 'pto') { $pto = $current['time']; }
			else if ($current['type'] == 'sick') { $sick = $current['time']; }
		}


		# now add the pay and leave records below
		$gbl_errs['error'] = "Failed to create the 'Employee Timesheet Pay' record in the database.";
		$gbl_info['command'] = "INSERT INTO ".PREFIX."Employees_Timesheets (employeeID,type,time,memo,createdBy,createdOn,updatedBy,updatedOn) VALUES ('".$employees['id']."','pay','".$_."',\"".$payments['PPPAP']."|".($payments['YTDAP'] + $payments['PPPAP'])."|".$payments['PPPAC']."|".($payments['YTDAC'] + $payments['PPPAC'])."\",'0','".$_."','0','".$_."')";
		$gbl_info['values'] = 'None';
		$stmt = $linkDB->prepare($gbl_info['command']);
#		$stmt->bind_param('ssisiiissssisiissssssssssssssssssssssiss', $_POST['sName_BusinessConfiguration'], $_POST['sType_BusinessConfiguration'], $_POST['nFEIN_BusinessConfiguration'], $_POST['sSalesTax_BusinessConfiguration'], $_POST['nSalesTaxRate_BusinessConfiguration'], $_POST['nUnemploymentTax_BusinessConfiguration'], $_POST['nUnemploymentTaxRate_BusinessConfiguration'], $_POST['eFounded_BusinessConfiguration'], $_POST['sCountry_BusinessConfiguration'], $_POST['eFiscal_BusinessConfiguration'], $_POST['sTimezone_BusinessConfiguration'], $_POST['nBeginHour_BusinessConfiguration'].$_POST['nBeginMin_BusinessConfiguration'].'00', $_POST['nEndHour_BusinessConfiguration'].$_POST['nEndMin_BusinessConfiguration'].'00', $_POST['nPhone_BusinessConfiguration'], $_POST['nFax_BusinessConfiguration'], $_POST['sWebsite_BusinessConfiguration'], $_POST['sMainAddr1_BusinessConfiguration'], $_POST['sMainAddr2_BusinessConfiguration'], $_POST['sMainCity_BusinessConfiguration'], $_POST['sMainState_BusinessConfiguration'], $_POST['sMainZip_BusinessConfiguration'], $_POST['sMainCountry_BusinessConfiguration'], $_POST['sBillAddr1_BusinessConfiguration'], $_POST['sBillAddr2_BusinessConfiguration'], $_POST['sBillCity_BusinessConfiguration'], $_POST['sBillState_BusinessConfiguration'], $_POST['sBillZip_BusinessConfiguration'], $_POST['sBillCountry_BusinessConfiguration'], $_POST['sShipAddr1_BusinessConfiguration'], $_POST['sShipAddr2_BusinessConfiguration'], $_POST['sShipCity_BusinessConfiguration'], $_POST['sShipState_BusinessConfiguration'], $_POST['sShipZip_BusinessConfiguration'], $_POST['sShipCountry_BusinessConfiguration'], $_POST['sMerchant_BusinessConfiguration'], $mid, $_POST['sOPoID_BusinessConfiguration'], $_POST['bUseBBN_BusinessConfiguration'], $_POST['ePayDay_BusinessConfiguration'], $_POST['sPaySchedule_BusinessConfiguration']);
		$stmt->execute();

		$gbl_errs['error'] = "Failed to create the 'Employee Timesheet Leave' record in the database.";
		$gbl_info['command'] = "INSERT INTO ".PREFIX."Employees_Timesheets (employeeID,type,time,memo,createdBy,createdOn,updatedBy,updatedOn) VALUES ('".$employees['id']."','leave','".$_."',\"".$employees['PTORate']."|".($timeoff['YTDAP'] + $employees['PTORate'] - $PTO)."|".($timeoff['YTDUP'] + $PTO)."|".$employees['SickRate']."|".($timeoff['YTDAS'] + $employees['SickRate'] - $SICK)."|".($timeoff['YTDUS'] + $SICK)."\",'0','".$_."','0','".$_."')";
		$gbl_info['values'] = 'None';
		$stmt = $linkDB->prepare($gbl_info['command']);
#		$stmt->bind_param('ssisiiissssisiissssssssssssssssssssssiss', $_POST['sName_BusinessConfiguration'], $_POST['sType_BusinessConfiguration'], $_POST['nFEIN_BusinessConfiguration'], $_POST['sSalesTax_BusinessConfiguration'], $_POST['nSalesTaxRate_BusinessConfiguration'], $_POST['nUnemploymentTax_BusinessConfiguration'], $_POST['nUnemploymentTaxRate_BusinessConfiguration'], $_POST['eFounded_BusinessConfiguration'], $_POST['sCountry_BusinessConfiguration'], $_POST['eFiscal_BusinessConfiguration'], $_POST['sTimezone_BusinessConfiguration'], $_POST['nBeginHour_BusinessConfiguration'].$_POST['nBeginMin_BusinessConfiguration'].'00', $_POST['nEndHour_BusinessConfiguration'].$_POST['nEndMin_BusinessConfiguration'].'00', $_POST['nPhone_BusinessConfiguration'], $_POST['nFax_BusinessConfiguration'], $_POST['sWebsite_BusinessConfiguration'], $_POST['sMainAddr1_BusinessConfiguration'], $_POST['sMainAddr2_BusinessConfiguration'], $_POST['sMainCity_BusinessConfiguration'], $_POST['sMainState_BusinessConfiguration'], $_POST['sMainZip_BusinessConfiguration'], $_POST['sMainCountry_BusinessConfiguration'], $_POST['sBillAddr1_BusinessConfiguration'], $_POST['sBillAddr2_BusinessConfiguration'], $_POST['sBillCity_BusinessConfiguration'], $_POST['sBillState_BusinessConfiguration'], $_POST['sBillZip_BusinessConfiguration'], $_POST['sBillCountry_BusinessConfiguration'], $_POST['sShipAddr1_BusinessConfiguration'], $_POST['sShipAddr2_BusinessConfiguration'], $_POST['sShipCity_BusinessConfiguration'], $_POST['sShipState_BusinessConfiguration'], $_POST['sShipZip_BusinessConfiguration'], $_POST['sShipCountry_BusinessConfiguration'], $_POST['sMerchant_BusinessConfiguration'], $mid, $_POST['sOPoID_BusinessConfiguration'], $_POST['bUseBBN_BusinessConfiguration'], $_POST['ePayDay_BusinessConfiguration'], $_POST['sPaySchedule_BusinessConfiguration']);
		$stmt->execute();
# LEFT OFF - create an 'accounts payable' record for each paycheck (if the module is installed)
	}
}


// define the commerce function
function employees_commerce() {
	# there is currently no processing for commerce
	return true;
}


// now exit this script if it is being called from the maintenance.php file (since we just needed access to the above functions
if(php_sapi_name() == 'cli' || empty($_SERVER['REMOTE_ADDR'])) { return true; }

file_put_contents('debug.txt', "EMP 1\n", FILE_APPEND);



// create the header for any processing below...
#if ($_GET['action'] != '' || $_POST['action'] != '') {
#if ($_POST['action'] != '') {
	if ($_POST['A'] == 'initialize' && $_POST['T'] == 'module') {	# if we're loading the HTML/css, then...
file_put_contents('debug.txt', "EMP html\n", FILE_APPEND);
		header('Content-Type: text/html; charset=utf-8');
	} else {						# otherwise, we're interacting with the database and need to use XML
file_put_contents('debug.txt', "EMP xml\n", FILE_APPEND);
		header('Content-Type: text/xml; charset=utf-8');
		echo "<?xml version='1.0' encoding='UTF-8'?>\n\n";
	}
#}


file_put_contents('debug.txt', "EMP 1b\n", FILE_APPEND);
# UPDATING MODULE - change 'gbl_user' to $gbl_user in loadUser()
# UPDATING MODULE - change $linkDB to $_LinkDB


# -- Employees API --

switch ($_POST['A']) {						# Process the submitted (A)ction

    case 'initialize':
	if ($_POST['T'] == 'module') {				# Process the submitted (T)arget [sends the modules' html/css]
file_put_contents('debug.txt', "EMP module |".substr(SCRIPT,0,-4)."|\n", FILE_APPEND);
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'[^a-zA-Z0-9]')) { exit(); }
		if (! validate($_POST['sUsername'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
file_put_contents('debug.txt', "EMP module 1\n", FILE_APPEND);

		# load the users account info in the global variable
		if (! loadUser($_nTimeout,$__sUser,'rw','*',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }
file_put_contents('debug.txt', "EMP module 2\n", FILE_APPEND);

		# check that the submitting account has permission to access the module
		$__sInfo['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission.";
		$__sInfo['command'] = "SELECT id FROM ".DB_PRFX."Application_Modules WHERE name='".MODULE."' LIMIT 1";
		$__sInfo['values'] = 'None';
		$Module = $_LinkDB->query($__sInfo['command']);
		$module = $Module->fetch_assoc();
file_put_contents('debug.txt', "EMP module 3\n", FILE_APPEND);

		$__sInfo['error'] = "Failed to find the Employee record in the database when checking for access permission.";
		$__sInfo['command'] = "SELECT `read` FROM ".DB_PRFX."Employees_Access WHERE employeeID='".$__sUser['id']."' AND moduleID='".$module['id']."' LIMIT 1";
		$__sInfo['values'] = 'None';
		$Access = $_LinkDB->query($__sInfo['command']);
		if ($Access->num_rows === 0) {			# if the account can't be found, then...
			echo "<div class='fail'>Your account does not have sufficient priviledges to open (read) data in this module.</div>";
			exit();
		}						# otherwise the account MAY have permission to access, so...
		$access = $Access->fetch_assoc();		# load the access information for the account
		if ($access['read'] == 0) {			# if the account does NOT have 'read' access for this module, then...
			echo "<div class='fail'>Your account does not have sufficient priviledges to open (read) data in this module.</div>";
			exit();
		}
file_put_contents('debug.txt', "EMP module 4\n", FILE_APPEND);

		# if we've made it down here then the module can be accessed by this account
		if (! file_exists("../home/".$_POST['sUsername']."/look/".substr(SCRIPT,0,-4).".html")) {
file_put_contents('debug.txt', "EMP top\n", FILE_APPEND);
			echo "<div class='fail'>The screen contents you are requesting do NOT exist, please contact your network administrator or IT technician for assistence.</div>";
		} else {
file_put_contents('debug.txt', "EMP btm\n", FILE_APPEND);
			$page = fopen("../home/".$_POST['sUsername']."/look/".substr(SCRIPT,0,-4).".html", "r");
			while ($LINE = fgets($page)) {
				# snippets for dynamic content using shell-style-variables in the .html file
				if (strpos($LINE, '${UN}') !== false) { $LINE = str_replace('${UN}', $_POST['sUsername'], $LINE); }
				echo "$LINE";
			}
		}
file_put_contents('debug.txt', "EMP end\n", FILE_APPEND);
		exit();




	} else if ($_POST['T'] == 'values') {			# Process the submitted (T)arget [initialize UI values -OR- load requested account (below)]
		# do nothing here, proceed to the following block of code	(see the "load > values" case below)
file_put_contents('debug.txt', "EMP - IV-1\n", FILE_APPEND);
	}
	#break;							# allow the next 'case' to process (the above 'if' and this share code)


//if (($_POST['action'] == 'init' && $_POST['target'] == 'values') || ($_POST['action'] == 'load' && $_POST['target'] == 'employee')) {			UPDATE: employee > account


    case 'load':						# loads the requested account info	WARNING: this MUST come directly after the "} else if ($_POST['T'] == 'values') {" line above!!!
file_put_contents('debug.txt', "Emp - Load\n", FILE_APPEND);
	if ($_POST['T'] == 'account' || $_POST['T'] == 'values') {		# Process the submitted (T)arget [load requested account -OR- initialize UI values (above)]
file_put_contents('debug.txt', "Emp - LA -1\n", FILE_APPEND);
		# validate all submitted data
		if (! validate($_POST['sSessionID'],40,'[^a-zA-Z0-9]')) { exit(); }
		if (! validate($_POST['sUsername'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
		if ($_POST['T'] == 'account') { if (! validate($_POST['id'],20,'[^0-9]')) {exit();} }

		# obtain the employee information of the person WHO SAVED THE RECORD
		if (! loadUser($_nTimeout,$__sUser,'rw','*',DB_PRFX.'Employees','username','s|'.$_POST['sUsername'],'sid','s|'.$_POST['sSessionID'])) { exit(); }


		# check that the submitting account has permission to access the module
		$__sInfo['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission.";
		$__sInfo['command'] = "SELECT id FROM ".DB_PRFX."Application_Modules WHERE name='".MODULE."' LIMIT 1";
		$__sInfo['values'] = 'None';
		$Module = $_LinkDB->query($__sInfo['command']);
		$module = $Module->fetch_assoc();
file_put_contents('debug.txt', "EMP module 3\n", FILE_APPEND);

		$__sInfo['error'] = "Failed to find the Employee record in the database when checking for access permission.";
		$__sInfo['command'] = "SELECT `read` FROM ".DB_PRFX."Employees_Access WHERE employeeID='".$__sUser['id']."' AND moduleID='".$module['id']."' LIMIT 1";
		$__sInfo['values'] = 'None';
		$Access = $_LinkDB->query($__sInfo['command']);
		if ($Access->num_rows === 0) {			# if the account can't be found, then...
			echo "<div class='fail'>Your account does not have sufficient priviledges to open (read) data in this module.</div>";
			exit();
		}						# otherwise the account MAY have permission to access, so...
		$access = $Access->fetch_assoc();		# load the access information for the account
		if ($access['read'] == 0) {			# if the account does NOT have 'read' access for this module, then...
			echo "<div class='fail'>Your account does not have sufficient priviledges to open (read) data in this module.</div>";
			exit();
		}
file_put_contents('debug.txt', "EMP module 4\n", FILE_APPEND);


		# --- RETRIEVE INFO FOR: INITIALIZING VALUES -AND- ACCOUNT INFO ---


		# 1. Obtain all installed modules for account access
		$__sInfo['error'] = "Failed to find all 'Modules' in the database when loading an account.";
		$__sInfo['command'] = "SELECT id,name FROM ".DB_PRFX."Application_Modules";
		$__sInfo['values'] = 'None';
		$Module = $_LinkDB->query($__sInfo['command']);
file_put_contents('debug.txt', "EMP module 5\n", FILE_APPEND);

		# 2. Obtain all the staff (employees) of the business
		$__sInfo['error'] = "Failed to find all 'Employees' in the database when loading an account.";
		$__sInfo['command'] = "SELECT id,name FROM ".DB_PRFX."Employees";
		$__sInfo['values'] = 'None';
		$Staff = $_LinkDB->query($__sInfo['command']);
file_put_contents('debug.txt', "EMP module 6\n", FILE_APPEND);

		# 3. [Tab - General] [Optional] Check if the 'Business Configuration' module is installed (for extra account fields)		NOTE: this needs to run in both 'initialize' and 'load'
		$__sInfo['error'] = "Failed to obtain the existence of the 'Business Configuration' table when loading the module.";
		$__sInfo['command'] = "SHOW TABLES LIKE '".DB_PRFX."BusinessConfiguration'";
		$__sInfo['values'] = 'None';
		$optional = $_LinkDB->query($__sInfo['command']);
file_put_contents('debug.txt', "EMP module 6 - pre |".print_r($optional,true)."|\n", FILE_APPEND);
		if ($optional->num_rows > 0) {					# if the optional module IS installed, then...
file_put_contents('debug.txt', "EMP module 6 - inside\n", FILE_APPEND);
			# 4. Obtain the business configuration (or lack thereof if this is the initial loading without a business configuration)
			$__sInfo['error'] = "Failed to obtain the 'Business Configuration' in the database when initializing the module.";
			$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."BusinessConfiguration WHERE id='1' LIMIT 1";
			$__sInfo['values'] = 'None';
			$Business = $_LinkDB->query($__sInfo['command']);
#			if ($Business->num_rows === 0) {
# UPDATED 2025/02/23 - no longer mandatory
#				echo "<f><msg>Before working with employee accounts, you must configure the business first.</msg></f>";
#				exit();
file_put_contents('debug.txt', "EMP module 6 - top\n", FILE_APPEND);
#			}
			$Business = false;					# set initial values to false (for testing below)
			$Location = false;
			$Department = false;
			$Position = false;
			if ($Business !== false) {
file_put_contents('debug.txt', "EMP module 6 - btm\n", FILE_APPEND);
				# 5. Obtain all the alternative locations for the business
				$__sInfo['error'] = "Failed to find all 'Locations' in the database when loading an account.";
				$__sInfo['command'] = "SELECT id,name FROM ".DB_PRFX."BusinessConfiguration_Additional WHERE type='location'";
				$__sInfo['values'] = 'None';
				$Location = $_LinkDB->query($__sInfo['command']);

				# 6. Store all the departments created for the business
				$__sInfo['error'] = "Failed to find all 'Departments' in the database when loading an account.";
				$__sInfo['command'] = "SELECT id,name FROM ".DB_PRFX."BusinessConfiguration_Departments ORDER BY name";
				$__sInfo['values'] = 'None';
				$Department = $_LinkDB->query($__sInfo['command']);
			   	# if the department ID is set (e.g. NOT a new employee), then store that number, otherwise we're dealing with a new employee.  Used so that the correct positions get loaded correctly below.
# REMOVED 2025/02/23 - not used
#				$DID = 0;
#				if (array_key_exists('departmentID', $employee)) { if ($employee['departmentID'] != 0) {$DID = $employee['departmentID'];} }
				if ($Department->num_rows !== 0) {				# if there are defined departments, then...
					$department = $Department->fetch_assoc();		#   store the first one in the results list
					$DeptID = $department['id'];				#   store the id of that first department
					$Department->data_seek(0);				#   reset the SQL record pointer back to the first record in the results list
				} else { $DeptID = 0; }						# otherwise, none have been configured, so indicate that

				# 7. Store all created positions for the business
				if ($DeptID == 0) {						# if no departments have been configured, then...
# test that this will not throw errors below in the 'while' loop
					$Position = null;
				} else {
					$__sInfo['error'] = "Failed to find all 'Positions' in the database when loading an account.";
# LEFT OFF - here is what is failing
					$__sInfo['command'] = "SELECT id,name FROM ".DB_PRFX."BusinessConfiguration_Positions WHERE deptID='".$DeptID."'";
					$__sInfo['values'] = 'None';
					$Position = $_LinkDB->query($__sInfo['command']);
				}
			}
		}
file_put_contents('debug.txt', "EMP module 7\n", FILE_APPEND);

		# --- RETRIEVE INFO FOR: ACCOUNT INFO ---

# VER2 - move these into separate functions (one function per UI tab) to cut down on work to be done all at once
		if ($_POST['T'] == 'account') {
			# 8. [Tab - General] Store the specific employee account info
			$__sInfo['error'] = "Failed to find the 'Employee Info' in the database when loading an account.";
			$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."Employees WHERE id='".$_POST['id']."' LIMIT 1";
			$__sInfo['values'] = 'None';
			$Employee = $_LinkDB->query($__sInfo['command']);
			$employee = $Employee->fetch_assoc();
// REMOVED 2025/02/23 - just needed the above block, webBooks doesn't interact with 3rd-party databases
//			if (USERS == '' && $_POST['target'] == 'employee') {		# IF we need to access the native webBooks DB table, then...
//			} else if (USERS != '' && $_POST['target'] == 'employee') {	# OTHERWISE, we have mapped DB values, so pull the values from that table
//				$__sInfo['error'] = "Failed to find the 'Employee Info' in the database when loading an account.";
//				$__sInfo['command'] = "SELECT * FROM ".PREFIX.USERS." WHERE id='".$_POST['id']."' LIMIT 1";
//				$__sInfo['values'] = 'None';
//				$Employee = $_LinkDB->query($__sInfo['command']);
//				$employee = $Employee->fetch_assoc();
//			} else { $employee = array(); }					# create the blank array so as to not throw errors when checking for valid keys
file_put_contents('debug.txt', "EMP module 8\n", FILE_APPEND);

			# 8b. Decrypt private account information			  NOTE: but only if the parameters have values (e.g. not the newly created 'admin' account after a fresh install)
			$salt = file_get_contents('../../denaccess');			# obtain the decryption string
file_put_contents('debug.txt', "EMP module 8a ||\n", FILE_APPEND);
			if (! is_null($employee['homePhone']) && strlen($employee['homePhone']) > 1) { $phone = Cipher::decrypt($employee['homePhone'], $salt); } else { $phone = ''; }
file_put_contents('debug.txt', "EMP module 8b\n", FILE_APPEND);
			if (! is_null($employee['homeMobile']) && strlen($employee['homeMobile']) > 1) { $mobile = Cipher::decrypt($employee['homeMobile'], $salt); } else { $mobile = ''; }
			if (! is_null($employee['homeEmail']) && strlen($employee['homeEmail']) > 1) { $email = Cipher::decrypt($employee['homeEmail'], $salt); } else { $email = ''; }
file_put_contents('debug.txt', "EMP module 8d\n", FILE_APPEND);
			if (! is_null($employee['homeAddr1']) && strlen($employee['homeAddr1']) > 1) { $addr = Cipher::decrypt($employee['homeAddr1'], $salt); } else { $addr = ' '; }
			if (! is_null($employee['driversLicense']) && strlen($employee['driversLicense']) > 1) { $license = Cipher::decrypt($employee['driversLicense'], $salt); } else { $license = ''; }
file_put_contents('debug.txt', "EMP module 8f\n", FILE_APPEND);
			if (! is_null($employee['ssn']) && strlen($employee['ssn']) > 1) { $ssn = Cipher::decrypt($employee['ssn'], $salt); } else { $ssn = ''; }
			if (! is_null($employee['answer1']) && strlen($employee['answer1']) > 1) { $a1 = Cipher::decrypt($employee['answer1'], $salt); } else { $a1 = ''; }
file_put_contents('debug.txt', "EMP module 8h\n", FILE_APPEND);
			if (! is_null($employee['answer2']) && strlen($employee['answer2']) > 1) { $a2 = Cipher::decrypt($employee['answer2'], $salt); } else { $a2 = ''; }
			if (! is_null($employee['answer3']) && strlen($employee['answer3']) > 1) { $a3 = Cipher::decrypt($employee['answer3'], $salt); } else { $a3 = ''; }
file_put_contents('debug.txt', "EMP module 9\n", FILE_APPEND);

			# 9. [Tab - General] Obtain the donated time records for the employee for the last year (for year-to-date which also contains the prior pay periods records too)
			# NOTE: the reason we are obtaining all the yearly records as we don't envision there being a lot of these records, so the number to process will be small
			$donated=array();
			$__sInfo['error'] = "Failed to find all 'Employee Prior Leave' records in the database when loading an account.";
			$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."Employees_Donation WHERE sourceID='".$employee['id']."' AND DATE(created) >= '".date("Y")."-01-01' ORDER BY created ASC";
			$__sInfo['values'] = 'None';
			$Donations = $_LinkDB->query($__sInfo['command']);
			# set the default values
			$donated['PPPDP'] = 0.00;				# prior pay periods donated pto
			$donated['YTDDP'] = 0.00;				# year-to-date donated pto
			$donated['PPPDS'] = 0.00;				# prior pay periods donated sick leave
			$donated['YTDDS'] = 0.00;				# year-to-date donated sick leave
			# update the default values if any records exist
			if ($Donations->num_rows !== 0) {			# if there are donation records (which infer that you are NOT a new employee since you have to acquire 'leave' records to have time to donate), then...
				$start = strtotime($prior['time']);		# store the start epoch of the prior pay period (obtained from the above 'if')
				$end = strtotime($leave['time']);		# ditto for the end epoch

				while ($donations = $Donations->fetch_assoc()) {
					if ($donations['type'] == 'pto') {			# if we encountered a PTO donation, then...
						$donated['YTDDP'] += $donations['hours'];	# update the year-to-date count regardless

						$occurred = strtotime($donations['created']);
						if ($occurred > $start && $occurred < $end)	# update the prior-pay-period if it falls within that date range
							{ $donated['PPPDP'] += $donations['hours']; }

					} else if ($donations['type'] == 'sick') {
						$donated['YTDDS'] += $donations['hours'];

						$occurred = strtotime($donations['created']);
						if ($occurred > $start && $occurred < $end)
							{ $donated['PPPDS'] += $donations['hours']; }
					}
				}
			}

			# 10. [Tab - Time] Store all the work records from the last 7 days
			$__sInfo['error'] = "Failed to find the last 7 'Work Records' in the database when loading an account.";
			$__sInfo['command'] = "SELECT tblET.*,tblEM.name FROM ".DB_PRFX."Employees_Timesheets tblET LEFT JOIN ".DB_PRFX."Employees tblEM ON tblET.updatedBy = tblEM.id WHERE employeeID='".$employee['id']."' AND tblET.type<>'pay' AND tblET.type<>'leave' AND DATE(tblET.time)>DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY tblET.time";
			$__sInfo['values'] = 'None';
			$Work = $_LinkDB->query($__sInfo['command']);

			# 11. [Tab - Notes] Obtain all the notes relavent to the various access levels of the employee
			$conditions = "'everyone','".$employee['departmentID']."'";	# define the default value which includes notes for 'everyone' and notes for their department (via the record ID)
			if ($employee['manager'] > 0) { $conditions .= ",'managers'"; }	# if the user is a manager, then include those too!

			$__sInfo['error'] = "Failed to find all 'Notes' in the database when loading an account.";
			$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."Notes WHERE type='employee' AND rowID='".$employee['id']."' AND access IN (".$conditions.")";
			$__sInfo['values'] = 'None';
			$Note = $_LinkDB->query($__sInfo['command']);

			# 12. [Tab - Data] Store all the files uploaded to the module
			$__sInfo['error'] = "Failed to find all 'Uploads' in the database when loading an account.";
			$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."Uploads WHERE `table`='Employees' AND rowID='".$employee['id']."'";
			$__sInfo['values'] = 'None';
			$Upload = $_LinkDB->query($__sInfo['command']);

# VER2 - this should only be utilized if the 'Accounting' module is installed (so we can get the figures precisely)
			# --. [Tab - General] Obtain the prior pay periods 'pay' record (which is the cut off date of the prior pay period) for the employee
			$payments=array();
			$__sInfo['error'] = "Failed to find the 'Employee Prior Pay' record in the database when loading an account.";
			$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."Employees_Timesheets WHERE employeeID='".$employee['id']."' AND type='pay' ORDER BY time DESC LIMIT 1";
			$__sInfo['values'] = 'None';
			$Pay = $_LinkDB->query($__sInfo['command']);
			if ($Pay->num_rows === 0) {				# if there isn't any prior record (e.g. a new employee), then assign default values
				$payments['PPPAP'] = 0.00;			# prior pay periods accrued pay
				$payments['YTDAP'] = 0.00;			# year-to-date accrued pay
				$payments['PPPAC'] = 0.00;			# prior pay periods accrued commissions
				$payments['YTDAC'] = 0.00;			# year-to-date accrued commissions
				$payments['PPPPR'] = 0.00;			# prior pay periods paid reimbursements
				$payments['YTDPR'] = 0.00;			# year-to-date paid reimbursements
			} else {
				$pay = $Pay->fetch_assoc();
				$Payments = explode("|", $pay['memo']);

				$payments['PPPAP'] = $Payments[0];
				$payments['YTDAP'] = $Payments[1];
				$payments['PPPAC'] = $Payments[2];
				$payments['YTDAC'] = $Payments[3];
# LEFT OFF - need to get the reimbursements figures working below
				$payments['PPPPR'] = 0.00;
				$payments['YTDPR'] = 0.00;
			}

# VER2 - this should only be utilized if the 'Accounting' module is installed (so we can get the figures precisely)
			# --. [Tab - General] Obtain the prior pay periods 'leave' record (which is the cut off date of the prior pay period) for the employee
			$timeoff=array();
			$__sInfo['error'] = "Failed to find the 'Employee Prior Leave' record in the database when loading an account.";
			$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."Employees_Timesheets WHERE employeeID='".$employee['id']."' AND type='leave' ORDER BY time DESC LIMIT 1";
			$__sInfo['values'] = 'None';
			$Leave = $_LinkDB->query($__sInfo['command']);
			if ($Leave->num_rows === 0) {				# if there isn't any prior record (e.g. a new employee), then assign default values
				$timeoff['PPPAP'] = 0.00;			# prior pay periods accrued pto
				$timeoff['YTDAP'] = 0.00;			# year-to-date accrued pto (less the used pto - to show available leave)
				$timeoff['PPPUP'] = 0.00;			# prior pay periods used pto
				$timeoff['YTDUP'] = 0.00;			# year-to-date used pto
				$timeoff['PPPAS'] = 0.00;			# prior pay periods accrued sick leave
				$timeoff['YTDAS'] = 0.00;			# year-to-date accrued sick leave (less the used sick leave - to show available leave)
				$timeoff['PPPUS'] = 0.00;			# prior pay periods used pto
				$timeoff['YTDUS'] = 0.00;			# year-to-date used sick leave
			} else {
				$leave = $Leave->fetch_assoc();
				$Timeoff = explode("|", $leave['memo']);	# populate the below array with the prior pay periods leave values

				$timeoff['PPPAP'] = $Timeoff[0];
				$timeoff['YTDAP'] = $Timeoff[1];
				$timeoff['PPPUP'] = 0.00;			# set default value
				$timeoff['YTDUP'] = $Timeoff[2];
				$timeoff['PPPAS'] = $Timeoff[3];
				$timeoff['YTDAS'] = $Timeoff[4];
				$timeoff['PPPUS'] = 0.00;			# set default value
				$timeoff['YTDUS'] = $Timeoff[5];

				# find the next-to-latest prior pay periods timesheet 'leave' record (which is the start date of the prior pay period) for its 'time' value
				$__sInfo['error'] = "Failed to find the 'Employee Next-to-Last Prior Leave' record in the database when loading an account.";
				$__sInfo['command'] = "SELECT time FROM ".DB_PRFX."Employees_Timesheets WHERE employeeID='".$employee['id']."' AND type='leave' AND time < '".$leave['time']."' AND id<>'".$leave['id']."' ORDER BY time DESC LIMIT 1";
				$__sInfo['values'] = 'None';
				$Prior = $_LinkDB->query($__sInfo['command']);
				$prior = $Prior->fetch_assoc();

				# obtain all the prior pay periods timesheet records (between the $prior['time'] and $leave['time'])
				$__sInfo['error'] = "Failed to find all 'Employee Prior Leave' records in the database when loading an account.";
				$__sInfo['command'] = "SELECT * FROM ".DB_PRFX."Employees_Timesheets WHERE employeeID='".$employee['id']."' AND type<>'pay' AND type<>'leave' AND time BETWEEN '".$prior['time']."' AND '".$leave['time']."' ORDER BY time ASC";
				$__sInfo['values'] = 'None';
				$Priors = $_LinkDB->query($__sInfo['command']);
				while ($priors = $Priors->fetch_assoc()) {
					$pto = '';				# (re)set the values each iteration
					$sick = '';
					if ($priors['type'] == 'pto') { $pto = $priors['time']; }
					else if ($priors['type'] == 'sick') { $sick = $priors['time']; }

					if ($pto != '' && $priors['type'] == 'out') {				# if we reached the corresponding 'out' of a 'pto' record, then...
						$diff = strtotime($priors['time']) - strtotime($pto);		# returns in seconds based on the 'pto' time value and its corresponding 'out'
						$mins = floor($diff/60);					# converts to minutes
						$timeoff['PPPUP'] += round($mins/60, 2);			# converts to fractional hours and stores in the array for inclusion in the XML below
					} else if ($sick != '' && $priors['type'] == 'out') {			# if we reached the corresponding 'out' of a 'sick' record, then...
						$diff = strtotime($priors['time']) - strtotime($sick);
						$mins = floor($diff/60);
						$timeoff['PPPUS'] += round($mins/60, 2);
					}
				}
			}
		}
file_put_contents('debug.txt', "EMP module 10\n", FILE_APPEND);


		$XML =	"<s>\n".
			"   <xml>\n";

		# 1. Obtain all installed modules for account access
		$XML .=	"	<modules>\n";
// LEFT OFF - rename <modules> to <access>; then move inside <general>
		while ($module = $Module->fetch_assoc())
			{ $XML .= "	   <module id=\"".$module['id']."\" name=\"".safeXML($module['name'])."\" />\n"; }
		$XML .=	"	</modules>\n";

		# 2. Obtain all the staff (employees) of the business
		$XML .=	"	<staff>\n";
// LEFT OFF - rename <person> to <employee>
		while ($staff = $Staff->fetch_assoc())
			{ $XML .= "	   <person id=\"".$staff['id']."\" name=\"".safeXML($staff['name'])."\" />\n"; }
		$XML .=	"	</staff>\n";

		# 3-7. [Tab - General] [Optional] Check if the 'Business Configuration' module is installed (for extra account fields)
		if ($optional->num_rows > 0 && $Business !== false) {					# if the optional module IS installed -AND- configured, then...
			$XML .=	"	<locations>\n";
			if ($Location !== false) {
				while ($location = $Location->fetch_assoc())
					{ $XML .= "	   <location id=\"".$location['id']."\" name=\"".safeXML($location['name'])."\" />\n"; }
			}
			$XML .= "	</locations>\n";

			$XML .=	"	<departments>\n";
			if ($Department !== false) {
				while ($department = $Department->fetch_assoc())
					{ $XML .= "	   <dept id=\"".$department['id']."\" name=\"".safeXML($department['name'])."\" />\n"; }
			}
			$XML .= "	</departments>\n";

			$XML .=	"	<positions>\n";
			if (! is_null($Position)) {					# if no positions have been created, then...
				while ($position = $Position->fetch_assoc())
					{ $XML .= "	   <pos id=\"".$position['id']."\" name=\"".safeXML($position['name'])."\" />\n"; }
			}
			$XML .=	"	</positions>\n";
		}

		# [Tab - General]
		$XML .=	"	<supervisors>\n";
		$Staff->data_seek(0);						# reset the SQL record pointer back to the first record in the results list
		while ($staff = $Staff->fetch_assoc())
			{ $XML .= "	   <supervisor id=\"".$staff['id']."\" name=\"".safeXML($staff['name'])."\" />\n"; }
		$XML .= "	</supervisors>\n";

		# [Tab - Looks]
		$XML .=	"	<looks>\n";
		if ($_POST['T'] == 'account') {
			$skin = readlink('../home/'.$employee['username'].'/look');
			$skin = substr($skin, strrpos($skin,'/')+1);
			$icons = readlink('../home/'.$employee['username'].'/imgs');
			$icons = substr($icons, strrpos($icons,'/')+1);
		} else {
			$skin = readlink('../home/'.$_POST['sUsername'].'/look');
			$skin = substr($skin, strrpos($skin,'/')+1);
			$icons = readlink('../home/'.$_POST['sUsername'].'/imgs');
			$icons = substr($icons, strrpos($icons,'/')+1);
		}
		$XML .=	"	   <look skin=\"".$skin."\" icons=\"".$icons."\" />\n";
		if ($path = opendir('../look')) {
			while (false !== ($dir = readdir($path))) {
				if ($dir == "." || $dir == "..") { continue; }
				$XML .=	"	   <skin>".$dir."</skin>\n";
			}
			closedir($path);
		}
		if ($path = opendir('../imgs')) {
			while (false !== ($dir = readdir($path))) {
				if ($dir == "." || $dir == "..") { continue; }
				$XML .=	"	   <image>".$dir."</image>\n";
			}
			closedir($path);
		}
		$XML .=	"	</looks>\n";

		if ($_POST['T'] == 'account') {
			# 8,9. [Tab - General] Store the specific employee account info
			$XML .= "	<general>\n".
				"		<employee id=\"".$employee['id']."\" name=\"".safeXML($employee['name'])."\" OPoID=\"".safeXML($employee['OPoID'])."\" homePhone=\"".$phone."\" homeMobile=\"".$mobile."\" homeMobileSMS=\"".$employee['homeMobileSMS']."\" homeMobileEmail=\"".$employee['homeMobileEmail']."\" homeEmail=\"".$email."\" workPhone=\"".$employee['workPhone']."\" workExt=\"".$employee['workExt']."\" workMobile=\"".$employee['workMobile']."\" workMobileSMS=\"".$employee['workMobileSMS']."\" workMobileEmail=\"".$employee['workMobileEmail']."\" workEmail=\"".$employee['workEmail']."\" location=\"".$employee['locationID']."\" department=\"".safeXML($employee['departmentID'])."\" supervisor=\"".$employee['supervisorID']."\" position=\"".safeXML($employee['positionID'])."\" payTerms=\"".$employee['payTerms']."\" payType=\"".$employee['payType']."\" basePay=\"".$employee['basePay']."\" OTRate=\"".$employee['OTRate']."\" PTORate=\"".$employee['PTORate']."\" SickRate=\"".$employee['SickRate']."\" payCOLA=\"".$employee['payCOLA']."\" payMileage=\"".$employee['payMileage']."\" payPerDiem=\"".$employee['payPerDiem']."\" hired=\"".$employee['hired']."\" driversLicense=\"".$license."\" gender=\"".$employee['gender']."\" ssn=\"".$ssn."\" dob=\"".$employee['dob']."\" race=\"".$employee['race']."\" married=\"".$employee['married']."\" withholdings=\"".$employee['withholdings']."\" additional=\"".$employee['additional']."\" dependents=\"".$employee['dependents']."\" manager=\"".$employee['manager']."\" status=\"".$employee['status']."\" attempts=\"".$employee['attempts']."\" username=\"".$employee['username']."\" created=\"".$employee['created']."\" updated=\"".$employee['updated']."\" login=\"".$employee['login']."\" logout=\"".$employee['logout']."\" />\n".
				"		<address type=\"home\" addr1=\"".safeXML($addr)."\" addr2=\"".safeXML($employee['homeAddr2'])."\" city=\"".$employee['homeCity']."\" state=\"".$employee['homeState']."\" zip=\"".$employee['homeZip']."\" country=\"".$employee['homeCountry']."\" />\n".
				"		<address type=\"work\" addr1=\"".safeXML($employee['workAddr1'])."\" addr2=\"".safeXML($employee['workAddr2'])."\" city=\"".$employee['workCity']."\" state=\"".$employee['workState']."\" zip=\"".$employee['workZip']."\" country=\"".$employee['workCountry']."\" />\n".
				"		<payments PPPAP='".$payments['PPPAP']."' YTDAP='".$payments['YTDAP']."' PPPAC='".$payments['PPPAC']."' YTDAC='".$payments['YTDAC']."' PPPPR='".$payments['PPPPR']."' YTDPR='".$payments['YTDPR']."' />\n".
				"		<leave PPPAP='".$timeoff['PPPAP']."' YTDAP='".$timeoff['YTDAP']."' PPPUP='".$timeoff['PPPUP']."' YTDUP='".$timeoff['YTDUP']."' PPPAS='".$timeoff['PPPAS']."' YTDAS='".$timeoff['YTDAS']."' PPPUS='".$timeoff['PPPUS']."' YTDUS='".$timeoff['YTDUS']."' />\n".
				"		<donated PPPDP='".$donated['PPPDP']."' YTDDP='".$donated['YTDDP']."' PPPDS='".$donated['PPPDS']."' YTDDS='".$donated['YTDDS']."' />\n".
				"		<security>\n" .
				"			<question id='1'>".$employee['question1']."</question>\n" .
				"			<question id='2'>".$employee['question2']."</question>\n" .
				"			<question id='3'>".$employee['question3']."</question>\n" .
				"			<answer id='1'>".$a1."</answer>\n" .
				"			<answer id='2'>".$a2."</answer>\n" .
				"			<answer id='3'>".$a3."</answer>\n" .
				"		</security>\n" .
				"	</general>\n";

			# 10. [Tab - Time] Store all the work records from the last 7 days
			$XML .=	"	<time>\n";
			while ($work = $Work->fetch_assoc()) {
				if ($work['createdBy'] == 0) { $name = '_SYSTEM_'; } else { $name = $work['name']; }
				$XML .=	"		<record id=\"".$work['id']."\" type=\"".$work['type']."\" occurred=\"".$work['time']."\" createdID=\"".$work['createdBy']."\" createdBy=\"".$name."\" />\n";
			}
			$XML .=	"	</time>\n";

			# 11. [Tab - Notes] Obtain all the notes relavent to the various access levels of the employee
			$XML .=	"	<notes>\n";
			while ($note = $Note->fetch_assoc()) {
# LEFT OFF - merge the below SQL call into the main call at the top of this section
				# 8b. Obtain all the notes relavent to the various access levels of the employee
				$__sInfo['error'] = "Failed to find the 'Note Creators' in the database when loading an account.";
				$__sInfo['command'] = "SELECT name FROM ".PREFIX."Employees WHERE id='".$note['creatorID']."' LIMIT 1";
				$__sInfo['values'] = 'None';
				$Creator = $_LinkDB->query($__sInfo['command']);
				$creator = $Creator->fetch_assoc();
				$XML .=	"	   <note id=\"".$note['id']."\" type=\"".$note['type']."\" creator=\"".safeXML($creator['name'])."\" created=\"".$note['created']."\" updated=\"".$note['updated']."\">".safeXML($note['note'])."</note>\n";
			}
			$XML .=	"	</notes>\n";

			# 12. [Tab - Data] Store all the files uploaded to the module
			if (file_exists("../data/_modules/Employees/".$employee['id'])) {
				$XML .=	"	<data>\n";
				while ($upload = $Upload->fetch_assoc())
					{ $XML .=	"	   <entry id='".$upload['id']."' title=\"".safeXML($upload['name'])."\" filename=\"".safeXML($upload['filename'])."\" />\n"; }
				if ($dir = opendir('../data/_modules/Employees/'.$employee['id'])) {
					while (false !== ($file = readdir($dir))) {
						if ($file != "." && $file != "..") { $XML .=	"	   <file filename=\"".safeXML($file)."\" />\n"; }
					}
					closedir($dir);
				}
				$XML .=	"	</data>\n";
			}
		}

		$XML .=	"   </xml>\n".
			"</s>\n";

file_put_contents('debug.txt', "EMP XML:\n".$XML."\n", FILE_APPEND);
		echo $XML;
		exit();
	}
	break;
}
file_put_contents('debug.txt', "EMP very end\n", FILE_APPEND);











# --- LEGACY / DEPRECATED ---




if ($_POST['action'] == 'init' && $_POST['target'] == 'screen') {			# INITIALIZE THE SCREEN CONTENTS
	exit();
}




# INITIALIZE THE SCREEN VALUES -OR- LOAD A SPECIFIC ACCOUNT
if (($_POST['action'] == 'init' && $_POST['target'] == 'values') || ($_POST['action'] == 'load' && $_POST['target'] == 'employee')) {
	exit();


} else if ($_POST['action'] == 'load' && $_POST['target'] == 'positions') {	# LOAD THE POSITIONS RELATED TO THE PASSED DEPARTMENT
	# validate all submitted data
	if (! validate($_POST['deptID'],20,'[^0-9]')) { exit(); }

	# connect to the DB for reading below
	if (! connect2DB(DBHOST,DBNAME,DBUNRO,DBPWRO)) { return false; }	# NOTE: the connect2DB has its own error handling so we don't need to do it here!


	# 1. Obtain all the created positions of the business
	$gbl_errs['error'] = "Failed to find all 'Job Positions' in the database.";
	$gbl_info['command'] = "SELECT id,name FROM ".PREFIX."BusinessConfiguration_Positions WHERE deptID=?";
	$gbl_info['values'] = '[i] '.$_POST['deptID'];
	$stmt = $linkDB->prepare($gbl_info['command']);
	$stmt->bind_param('i', $_POST['deptID']);
	$stmt->execute();
	$Position = $stmt->get_result();


	echo "<s>\n";
	echo "   <xml>\n";
	echo "	<positions>\n";
	while ($position = $Position->fetch_assoc())
		{ echo "	   <pos id=\"".$position['id']."\" name=\"".safeXML($position['name'])."\" />\n"; }
	echo "	</positions>\n";
	echo "   </xml>\n";
	echo "</s>";
	exit();


} else if ($_POST['action'] == 'load' && $_POST['target'] == 'position') {	# LOAD THE POSITION INFORMATION (e.g. Base Pay, OT Rate, etc)
	# validate all submitted data
	if (! validate($_POST['SID'],40,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['username'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
	if (! validate($_POST['posID'],20,'[^0-9]')) { exit(); }

	# obtain the employee information of the person WHO SAVED THE RECORD
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'])) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'])) {exit();} }

	# NOTE: we do NOT check for access to this module since all employees should at least have access to their own records


	# 1. Store all the position info
	$gbl_errs['error'] = "Failed to find the 'Position' in the database.";
	$gbl_info['command'] = "SELECT * FROM ".PREFIX."BusinessConfiguration_Positions WHERE id=? LIMIT 1";
	$gbl_info['values'] = '[i] '.$_POST['posID'];
	$stmt = $linkDB->prepare($gbl_info['command']);
	$stmt->bind_param('i', $_POST['posID']);
	$stmt->execute();
	$Position = $stmt->get_result();


	echo "<s>\n";
	echo "   <xml>\n";
	echo "	<positions>\n";
	if ($_POST['posID'] == $gbl_user['positionID']) {
		echo "	   <pos id=\"".$gbl_user['positionID']."\" name=\"\" dept=\"".$gbl_user['departmentID']."\" payType=\"".$gbl_user['payType']."\" payTerms=\"".$gbl_user['payTerms']."\" basePay=\"".$gbl_user['basePay']."\" OTRate=\"".$gbl_user['OTRate']."\" PTORate=\"".$gbl_user['PTORate']."\" SickRate=\"".$gbl_user['SickRate']."\" payCOLA=\"".$gbl_user['payCOLA']."\" payMileage=\"".$gbl_user['payMileage']."\" payPerDiem=\"".$gbl_user['payPerDiem']."\" />\n";
	} else {
		while ($position = $Position->fetch_assoc())
			{ echo "	   <pos id=\"".$position['id']."\" name=\"".safeXML($position['name'])."\" dept=\"".$position['deptID']."\" payType=\"".$position['type']."\" payTerms=\"".$position['pay']."\" basePay=\"".$position['basePay']."\" OTRate=\"".$position['OTRate']."\" PTORate=\"".$position['PTORate']."\" SickRate=\"".$position['SickRate']."\" payCOLA=\"".$position['payCOLA']."\" payMileage=\"".$position['payMileage']."\" payPerDiem=\"".$position['payPerDiem']."\" />\n"; }
	}
	echo "	</positions>\n";
	echo "   </xml>\n";
	echo "</s>";
	exit();


} else if ($_POST['action'] == 'load' && $_POST['target'] == 'access') {		# LOAD THE MODULE ACCESS
	# validate all submitted data
	if (! validate($_POST['SID'],40,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['username'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
	if (! validate($_POST['accountID'],20,'[^0-9]')) { exit(); }
	if (! validate($_POST['moduleID'],20,'[^0-9]')) { exit(); }

	# obtain the employee information of the person WHO SAVED THE RECORD
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'])) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'])) {exit();} }

	# NOTE: we do NOT check for access to this module since all employees should at least have access to their own records


	# 1. Store all the position info
	$gbl_errs['error'] = "Failed to find the 'Position' in the database.";
	$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees_Access WHERE employeeID=? AND moduleID=? LIMIT 1";
	$gbl_info['values'] = '[i] '.$_POST['accountID'].', [i] '.$_POST['moduleID'];
	$stmt = $linkDB->prepare($gbl_info['command']);
	$stmt->bind_param('ii', $_POST['accountID'], $_POST['moduleID']);
	$stmt->execute();
	$Module = $stmt->get_result();


	echo "<s>\n";
	echo "   <xml>\n";
	echo "	<access>\n";
	while ($module = $Module->fetch_assoc())
		{ echo "	   <module id=\"".$module['id']."\" read=\"".$module['read']."\" write=\"".$module['write']."\" add=\"".$module['add']."\" del=\"".$module['del']."\" />\n"; }
	echo "	</access>\n";
	echo "   </xml>\n";
	echo "</s>";
	exit();




} else if ($_POST['action'] == 'save' && $_POST['target'] == 'employee') {	# SAVE THE FORM VALUES
	# strip any non-numbers from the following values:
	$_POST['nSSN_Employees'] = preg_replace('/[^0-9]/','',$_POST['nSSN_Employees']);
	$_POST['sDriversLicense_Employees'] = preg_replace('/[^a-zA-Z0-9]/','',$_POST['sDriversLicense_Employees']);
	$_POST['nHomePhone_Employees'] = preg_replace('/[^0-9]/','',$_POST['nHomePhone_Employees']);
	$_POST['nHomeMobile_Employees'] = preg_replace('/[^0-9]/','',$_POST['nHomeMobile_Employees']);
	$_POST['sHomeZip_Employees'] = preg_replace('/[^0-9]/','',$_POST['sHomeZip_Employees']);
	$_POST['nWorkPhone_Employees'] = preg_replace('/[^0-9]/','',$_POST['nWorkPhone_Employees']);
	$_POST['nWorkMobile_Employees'] = preg_replace('/[^0-9]/','',$_POST['nWorkMobile_Employees']);
	$_POST['sWorkZip_Employees'] = preg_replace('/[^0-9]/','',$_POST['sWorkZip_Employees']);

	# validate all submitted data
	if (! validate($_POST['SID'],40,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['username'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
	# -----
	if (! validate($_POST['nManager_Employees'],1,'{0|1}')) { exit(); }
	if (! validate($_POST['sAccountStatus_Employees'],9,'{verifying|active|locked|suspended}')) { exit(); }
	if (! validate($_POST['sUsername_Employees'],32,'[^a-zA-Z0-9_\-]')) { exit(); }
	if (! validate($_POST['sPassword_Employees'],24,'![=<>]')) { exit(); }
	if (! validate($_POST['sEmployeeName_Employees'],128,'![=<>;]')) { exit(); }
	if (! validate($_POST['sOPoID_Employees'],64,'[^a-zA-Z0-9\._\-]')) { exit(); }
	if (! validate($_POST['nHomePhone_Employees'],15,'[^0-9]')) { exit(); }
	if (! validate($_POST['nHomeMobile_Employees'],15,'[^0-9]')) { exit(); }
	if (! validate($_POST['bHomeMobileSMS_Employees'],1,'{0|1}')) { exit(); }
	if (! validate($_POST['bHomeMobileEmail_Employees'],1,'{0|1}')) { exit(); }
	if (! validate($_POST['sHomeEmail_Employees'],128,'[^a-zA-Z0-9_\.@\-]')) { exit(); }
	if (! validate($_POST['sHomeAddr1_Employees'],48,'![=<>]')) { exit(); }
	if (! validate($_POST['sHomeAddr2_Employees'],48,'![=<>]')) { exit(); }
	if (! validate($_POST['sHomeCity_Employees'],48,'[^a-zA-Z \-]')) { exit(); }
	if (! validate($_POST['sHomeState_Employees'],2,'[^a-zA-Z]')) { exit(); }
	if (! validate($_POST['sHomeZip_Employees'],10,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['sHomeCountry_Employees'],2,'[^a-z]')) { exit(); }
	if (! validate($_POST['nWorkPhone_Employees'],15,'[^0-9]')) { exit(); }
	if (! validate($_POST['nWorkExt_Employees'],7,'[^0-9]')) { exit(); }
	if (! validate($_POST['nWorkMobile_Employees'],15,'[^0-9]')) { exit(); }
	if (! validate($_POST['bWorkMobileSMS_Employees'],1,'{0|1}')) { exit(); }
	if (! validate($_POST['bWorkMobileEmail_Employees'],1,'{0|1}')) { exit(); }
	if (! validate($_POST['sWorkEmail_Employees'],128,'[^a-zA-Z0-9_\.@\-]')) { exit(); }
	if (! validate($_POST['sWorkAddr1_Employees'],48,'![=<>]')) { exit(); }
	if (! validate($_POST['sWorkAddr2_Employees'],48,'![=<>]')) { exit(); }
	if (! validate($_POST['sWorkCity_Employees'],48,'[^a-zA-Z \-]')) { exit(); }
	if (! validate($_POST['sWorkState_Employees'],2,'[^a-zA-Z]')) { exit(); }
	if (! validate($_POST['sWorkZip_Employees'],10,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['sWorkCountry_Employees'],2,'[^a-z]')) { exit(); }
	if (! validate($_POST['nWorkLocation_Employees'],20,'[^0-9]')) { exit(); }
	if (! validate($_POST['sDept_Employees'],20,'[^0-9]')) { exit(); }
	if (! validate($_POST['sSupervisor_Employees'],20,'[^0-9]')) { exit(); }
	if (! validate($_POST['sJobTitle_Employees'],20,'[^0-9]')) { exit(); }
	if (! validate($_POST['sPayTerms_Employees'],10,'{part-time|full-time|contractor|call|internship|volunteer}')) { exit(); }
	if (! validate($_POST['sPayType_Employees'],16,'{hourly|salary|salary+ot|commission|commission+base|job}')) { exit(); }
	if (! validate($_POST['nStandardPay_Employees'],10,'[^0-9\.]')) { exit(); }
	if (! validate($_POST['nOTPay_Employees'],5,'[^0-9\.]')) { exit(); }
	if (! validate($_POST['nPersonalLeave_Employees'],5,'[^0-9\.]')) { exit(); }
	if (! validate($_POST['nSickLeave_Employees'],5,'[^0-9\.]')) { exit(); }
	if (! validate($_POST['nCOLA_Employees'],5,'[^0-9\.]')) { exit(); }
	if (! validate($_POST['nMileage_Employees'],5,'[^0-9\.]')) { exit(); }
	if (! validate($_POST['nPerDiem_Employees'],10,'[^0-9\.]')) { exit(); }
	if (! validate($_POST['eHired_Employees'],10,'[^0-9\-]')) { exit(); }
	if (! validate($_POST['sDriversLicense_Employees'],24,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['sGender_Employees'],6,'{male|female}')) { exit(); }
	if (! validate($_POST['nSSN_Employees'],9,'[^0-9]')) { exit(); }
	if (! validate($_POST['eDOB_Employees'],10,'[^0-9\-]')) { exit(); }
	if (! validate($_POST['sRace_Employees'],10,'{african|asian|caucasian|hispanic|indian|me|na|mr|pi|other}')) { exit(); }
	if (! validate($_POST['sMarried_Employees'],10,'{single|married}')) { exit(); }
	if (! validate($_POST['nWithheld_Employees'],8,'[^0-9\.]')) { exit(); }
	if (! validate($_POST['nAllowance_Employees'],8,'[^0-9]')) { exit(); }
	if (! validate($_POST['nDependents_Employees'],2,'[^0-9]')) { exit(); }
	if (! validate($_POST['txtQuestion01'],128,'![=<>;]')) { exit(); }
	if (! validate($_POST['txtQuestion02'],128,'![=<>;]')) { exit(); }
	if (! validate($_POST['txtQuestion03'],128,'![=<>;]')) { exit(); }
	if (! validate($_POST['txtAnswer01'],32,'![=<>;]')) { exit(); }
	if (! validate($_POST['txtAnswer02'],32,'![=<>;]')) { exit(); }
	if (! validate($_POST['txtAnswer03'],32,'![=<>;]')) { exit(); }

	# obtain the employee information of the person WHO SAVED THE RECORD
	if (USERS == '') {							# IF we need to access the native application DB table, then...
		if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'])) {exit();}
		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees WHERE username=? LIMIT 1";
	} else {								# OTHERWISE, we have mapped DB values, so pull the values from that table
		if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'])) {exit();}
		$gbl_info['command'] = "SELECT * FROM ".PREFIX.USERS." WHERE username=? LIMIT 1";
	}

	# 1. Store the employee information of the account being looked at
	$gbl_errs['error'] = "Failed to find the 'Employee Account' in the database when saving information.";
	#$gbl_info['command'] = "";			# defined above
	$gbl_info['values'] = '[s] '.$_POST['sUsername_Employees'];
	$Employee = $linkDB->prepare($gbl_info['command']);
	$Employee->bind_param('s', $_POST['sUsername_Employees']);
	$Employee->execute();
	$Employee->store_result();						# before you can get the 'num_rows', you must first store the result
	if ($Employee->num_rows === 0) {					# if the account can't be found, then...
		$employee = array();
		mysqli_stmt_free_result($Employee);				#   this has to be called so further SQL queries can be made	https://www.php.net/manual/en/function.maxdb-stmt-free-result.php 	https://www.php.net/manual/en/mysqli-result.free.php 	https://stackoverflow.com/questions/3632075/why-is-mysqli-giving-a-commands-out-of-sync-error
	} else {
		#$Employee->bind_result(...);					# NOTE: we do NOT use this because we would have to specify all the variables to be filled out. Instead we adopted the below code via https://www.php.net/manual/en/mysqli-stmt.bind-result comments
		#$employee = $Employee->fetch_assoc();				# NOTE: this does NOT work with 'bind_result()' calls!
		$employee = array();
		$params = array();
		$meta = $Employee->result_metadata();						# used to get each array 'key' value
		while ($field = $meta->fetch_field()) { $params[] = &$row[$field->name]; }	# store those in the 'params' array
		call_user_func_array(array($Employee, 'bind_result'), $params);			# builds the 'Employees' associative array (using the 'keys' from 'params')
		while ($Employee->fetch())
			{ foreach($row as $key => $val) {$employee[$key] = $val;} }		# now store the values for each array 'key'
	}

	# 2. check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."Application_Modules WHERE name='Employees' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission.";
	$gbl_info['command'] = "SELECT `add`,`write` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to create (add) or save (write) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if (count($employee) === 0) {			# if a new account is attempting to be created by the user, then...
		if ($access['add'] == 0) {		#   if the account does NOT have 'add' access for this module, then...
			echo "<f><msg>Your account does not have sufficient priviledges to create (add) data in this module.</msg></f>";
			exit();
		}
		if ($gbl_user['manager'] == 0) {	#   check that the account attempting to create this new account is a manager
			echo "<f><msg>You must be a manager in order to create new accounts.</msg></f>";
			exit();
		}
	} else {					# otherwise the account exists, so...
		$password = $employee['password'];						# store the existing password as a default value

		if ($access['write'] == 0) {		#   if the account does NOT have 'read' access for this module, then...
			echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
			exit();
		}
		if ($gbl_user['id'] != $employee['id'] && $gbl_user['manager'] == 0) {		# if the employee isn't updating their own record -AND- they're not a manager, then...
			echo "<f><msg>Your account does not have the ability to update another employee.</msg></f>";
			exit();
		}
	}

	# 3. Encrypt the following values
	$salt = file_get_contents('../../denaccess');
	if (strlen($_POST['nHomePhone_Employees']) > 1) { $phone = Cipher::encrypt($_POST['nHomePhone_Employees'], $salt); } else { $phone = ''; }
	if (strlen($_POST['nHomeMobile_Employees']) > 1) { $mobile = Cipher::encrypt($_POST['nHomeMobile_Employees'], $salt); } else { $mobile = ''; }
	if (strlen($_POST['sHomeEmail_Employees']) > 1) { $email = Cipher::encrypt($_POST['sHomeEmail_Employees'], $salt); } else { $email = ''; }
	if (strlen($_POST['sHomeAddr1_Employees']) > 1) { $addr = Cipher::encrypt($_POST['sHomeAddr1_Employees'], $salt); } else { $addr = ''; }
	if (strlen($_POST['sDriversLicense_Employees']) > 1) { $license = Cipher::encrypt($_POST['sDriversLicense_Employees'], $salt); } else { $license = ''; }
	if (strlen($_POST['nSSN_Employees']) > 1) { $ssn = Cipher::encrypt($_POST['nSSN_Employees'], $salt); } else { $ssn = ''; }
	if ($_POST['sPassword_Employees'] != '') {				# if a new password was entered, then...
		$hash = md5($_POST['sPassword_Employees']);			#    hash the password and store that value, not the actual password!!!	https://stackoverflow.com/questions/9262109/simplest-two-way-encryption-using-php
		$password = Cipher::encrypt($hash, $salt); 
	} else { $password = ''; }
	if (strlen($_POST['txtAnswer01']) > 1) { $ans1 = Cipher::encrypt($_POST['txtAnswer01'], $salt); } else { $ans1 = ''; }
	if (strlen($_POST['txtAnswer02']) > 1) { $ans2 = Cipher::encrypt($_POST['txtAnswer02'], $salt); } else { $ans2 = ''; }
	if (strlen($_POST['txtAnswer03']) > 1) { $ans3 = Cipher::encrypt($_POST['txtAnswer03'], $salt); } else { $ans3 = ''; }

	if (strlen($_POST['eDOB_Employees']) > 1) { $dob = $_POST['eDOB_Employees']; } else { $dob = ''; }
	if ($_POST['sAccountStatus_Employees'] == 'active') { $disabled=0; } else { $disabled=1; }

	# if we've made it here, we are clear to do some work
	if (count($employee) === 0) {			# if a new account is attempting to be created by the user, then...
		# make sure if the username is changing, it is available
		if (file_exists("../home/".$_POST['sUsername_Employees'])) {
			echo "<f><msg>The username you are requesting already belongs to another employee.</msg></f>";
			exit();
		}

		$gbl_errs['error'] = "Failed to create a new employee in the database.";
		$gbl_info['command'] = "INSERT INTO ".PREFIX."Employees (manager,disabled,status,username,password,timeStatus,timeAvail,name,OPoID,homePhone,homeMobile,homeMobileSMS,homeMobileEmail,homeEmail,homeAddr1,homeAddr2,homeCity,homeState,homeZip,homeCountry,workPhone,workExt,workMobile,workMobileSMS,workMobileEmail,workEmail,workAddr1,workAddr2,workCity,workState,workZip,workCountry,locationID,departmentID,supervisorID,positionID,payTerms,payType,basePay,OTRate,PTORate,SickRate,payCOLA,payMileage,payPerDiem,hired,driversLicense,gender,ssn,dob,race,married,withholdings,additional,dependents,question1,question2,question3,answer1,answer2,answer3,created,updated) VALUES (?,?,?,?,?,'out','no',?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'".$_."','".$_."')";
		$gbl_info['values'] = '[i] '.$_POST['nManager_Employees'].', [i] '.$disabled.', [s] '.$_POST['sAccountStatus_Employees'].', [s] '.$_POST['sUsername_Employees'].', [s] '.$password.', [s] '.$_POST['sEmployeeName_Employees'].', [s] '.$_POST['sOPoID_Employees'].', [i] '.$phone.', [i] '.$mobile.', [i] '.$_POST['bHomeMobileSMS_Employees'].', [i] '.$_POST['bHomeMobileEmail_Employees'].', [s] '.$email.', [s] '.$addr.', [s] '.$_POST['sHomeAddr2_Employees'].', [s] '.$_POST['sHomeCity_Employees'].', [s] '.$_POST['sHomeState_Employees'].', [s] '.$_POST['sHomeZip_Employees'].', [s] '.$_POST['sHomeCountry_Employees'].', [i] '.$_POST['nWorkPhone_Employees'].', [i] '.$_POST['nWorkExt_Employees'].', [i] '.$_POST['nWorkMobile_Employees'].', [i] '.$_POST['bWorkMobileSMS_Employees'].', [i] '.$_POST['bWorkMobileEmail_Employees'].', [s] '.$_POST['sWorkEmail_Employees'].', [s] '.$_POST['sWorkAddr1_Employees'].', [s] '.$_POST['sWorkAddr2_Employees'].', [s] '.$_POST['sWorkCity_Employees'].', [s] '.$_POST['sWorkState_Employees'].', [s] '.$_POST['sWorkZip_Employees'].', [s] '.$_POST['sWorkCountry_Employees'].', [i] '.$_POST['nWorkLocation_Employees'].', [i] '.$_POST['sDept_Employees'].', [i] '.$_POST['sSupervisor_Employees'].', [s] '.$_POST['sJobTitle_Employees'].', [s] '.$_POST['sPayTerms_Employees'].', [s] '.$_POST['sPayType_Employees'].', [d] '.$_POST['nStandardPay_Employees'].', [d] '.$_POST['nOTPay_Employees'].', [d] '.$_POST['nPersonalLeave_Employees'].', [d] '.$_POST['nSickLeave_Employees'].', [d] '.$_POST['nCOLA_Employees'].', [d] '.$_POST['nMileage_Employees'].', [d] '.$_POST['nPerDiem_Employees'].', [s] '.$_POST['eHired_Employees'].', [s] '.$license.', [s] '.$_POST['sGender_Employees'].', [s] '.$ssn.', [s] '.$_POST['eDOB_Employees'].', [s] '.$_POST['sRace_Employees'].', [s] '.$_POST['sMarried_Employees'].', [d] '.$_POST['nWithheld_Employees'].', [i] '.$_POST['nAllowance_Employees'].', [i] '.$_POST['nDependents_Employees'].', [s] '.$_POST['txtQuestion01'].', [s] '.$_POST['txtQuestion02'].', [s] '.$_POST['txtQuestion03'].', [s] '.$ans1.', [s] '.$ans2.', [s] '.$ans3;
		$stmt = $linkDB->prepare($gbl_info['command']);
		$stmt->bind_param('iisssssiiiisssssssiiiiisssssssiiisssdddddddsssssssdiissssss', $_POST['nManager_Employees'], $disabled, $_POST['sAccountStatus_Employees'], $_POST['sUsername_Employees'], $password, $_POST['sEmployeeName_Employees'], $_POST['sOPoID_Employees'], $phone, $mobile, $_POST['bHomeMobileSMS_Employees'], $_POST['bHomeMobileEmail_Employees'], $email, $addr, $_POST['sHomeAddr2_Employees'], $_POST['sHomeCity_Employees'], $_POST['sHomeState_Employees'], $_POST['sHomeZip_Employees'], $_POST['sHomeCountry_Employees'], $_POST['nWorkPhone_Employees'], $_POST['nWorkExt_Employees'], $_POST['nWorkMobile_Employees'], $_POST['bWorkMobileSMS_Employees'], $_POST['bWorkMobileEmail_Employees'], $_POST['sWorkEmail_Employees'], $_POST['sWorkAddr1_Employees'], $_POST['sWorkAddr2_Employees'], $_POST['sWorkCity_Employees'], $_POST['sWorkState_Employees'], $_POST['sWorkZip_Employees'], $_POST['sWorkCountry_Employees'], $_POST['nWorkLocation_Employees'], $_POST['sDept_Employees'], $_POST['sSupervisor_Employees'], $_POST['sJobTitle_Employees'], $_POST['sPayTerms_Employees'], $_POST['sPayType_Employees'], $_POST['nStandardPay_Employees'], $_POST['nOTPay_Employees'], $_POST['nPersonalLeave_Employees'], $_POST['nSickLeave_Employees'], $_POST['nCOLA_Employees'], $_POST['nMileage_Employees'], $_POST['nPerDiem_Employees'], $_POST['eHired_Employees'], $license, $_POST['sGender_Employees'], $ssn, $_POST['eDOB_Employees'], $_POST['sRace_Employees'], $_POST['sMarried_Employees'], $_POST['nWithheld_Employees'], $_POST['nAllowance_Employees'], $_POST['nDependents_Employees'], $_POST['txtQuestion01'], $_POST['txtQuestion02'], $_POST['txtQuestion03'], $ans1, $ans2, $ans3);
		$stmt->execute();

		# make the users home directory and 'Looks' symlinks
		if (! file_exists("../home/".$_POST['sUsername_Employees'])) {
			$gbl_errs['error'] = "The \"../home/".$_POST['sUsername_Employees']."\" directory can not be created for the employee.";
			$gbl_info['command'] = "mkdir(\"../home/".$_POST['sUsername_Employees']."\", 0775, true)";
			$gbl_info['values'] = 'None';
			mkdir("../home/".$_POST['sUsername_Employees'], 0775, true);
		}
		if (! file_exists("../home/".$_POST['sUsername_Employees']."/imgs")) {
			$gbl_errs['error'] = "The \"../home/".$_POST['sUsername_Employees']."/imgs\" symlink can not be created for the employee.";
			$gbl_info['command'] = "symlink(\"../../imgs/default\", \"../home/".$_POST['sUsername_Employees']."/imgs\")";
			$gbl_info['values'] = 'None';
			symlink("../../imgs/default","../home/".$_POST['sUsername_Employees']."/imgs");
		}
		if (! file_exists("../home/".$_POST['sUsername_Employees']."/look")) {
			$gbl_errs['error'] = "The \"../home/".$_POST['sUsername_Employees']."/look\" symlink can not be created for the employee.";
			$gbl_info['command'] = "symlink(\"../../look/default\", \"../home/".$_POST['sUsername_Employees']."/look\")";
			$gbl_info['values'] = 'None';
			symlink("../../look/default","../home/".$_POST['sUsername_Employees']."/look");
		}

		echo "<s><msg>The employee account has been created successfully!</msg><data id='".$stmt->insert_id."'></data></s>";	# NOTE: do NOT return a <data>...</data> element since that would affect the username and decrypt values of the *CURRENT* user, not the one being created!
		exit();						# exit no matter if success or failure at this point
	}

	# if we've made it here, then we need to update an existing account
	if ($gbl_user['id'] == $employee['id']) {		# if the user is updating their own account, then...
		$gbl_errs['error'] = "Failed to update the employees' own account in the database.";
		$gbl_info['command'] = "UPDATE ".PREFIX."Employees SET disabled=?,password=?,name=?,OPoID=?,homePhone=?,homeMobile=?,homeMobileSMS=?,homeMobileEmail=?,homeEmail=?,homeAddr1=?,homeAddr2=?,homeCity=?,homeState=?,homeZip=?,homeCountry=?,driversLicense=?,gender=?,ssn=?,dob=?,race=?,married=?,withholdings=?,additional=?,dependents=?,question1=?,question2=?,question3=?,answer1=?,answer2=?,answer3=?,updated='".$_."' WHERE id='".$employee['id']."'";
		$gbl_info['values'] = '[i] '.$disabled.', [s] '.$password.', [s] '.$_POST['sEmployeeName_Employees'].', [s] '.$_POST['sOPoID_Employees'].', [s] '.$phone.', [s] '.$mobile.', [i] '.$_POST['bHomeMobileSMS_Employees'].', [i] '.$_POST['bHomeMobileEmail_Employees'].', [s] '.$email.', [s] '.$addr.', [s] '.$_POST['sHomeAddr2_Employees'].', [s] '.$_POST['sHomeCity_Employees'].', [s] '.$_POST['sHomeState_Employees'].', [s] '.$_POST['sHomeZip_Employees'].', [s] '.$_POST['sHomeCountry_Employees'].', [s] '.$license.', [s] '.$_POST['sGender_Employees'].', [s] '.$ssn.', [s] '.$_POST['eDOB_Employees'].', [s] '.$_POST['sRace_Employees'].', [s] '.$_POST['sMarried_Employees'].', [d] '.$_POST['nWithheld_Employees'].', [i] '.$_POST['nAllowance_Employees'].', [i] '.$_POST['nDependents_Employees'].', [s] '.$_POST['txtQuestion01'].', [s] '.$_POST['txtQuestion02'].', [s] '.$_POST['txtQuestion03'].', [s] '.$ans1.', [s] '.$ans2.', [s] '.$ans3;
		$stmt = $linkDB->prepare($gbl_info['command']);
		$stmt->bind_param('isssssiisssssssssssssdiissssss', $disabled, $password, $_POST['sEmployeeName_Employees'], $_POST['sOPoID_Employees'], $phone, $mobile, $_POST['bHomeMobileSMS_Employees'], $_POST['bHomeMobileEmail_Employees'], $email, $addr, $_POST['sHomeAddr2_Employees'], $_POST['sHomeCity_Employees'], $_POST['sHomeState_Employees'], $_POST['sHomeZip_Employees'], $_POST['sHomeCountry_Employees'], $license, $_POST['sGender_Employees'], $ssn, $_POST['eDOB_Employees'], $_POST['sRace_Employees'], $_POST['sMarried_Employees'], $_POST['nWithheld_Employees'], $_POST['nAllowance_Employees'], $_POST['nDependents_Employees'], $_POST['txtQuestion01'], $_POST['txtQuestion02'], $_POST['txtQuestion03'], $ans1, $ans2, $ans3);
		$stmt->execute();
	} else {						# otherwise a manager is updating an existing employee account
		# make sure if the username is changing, it is available
		if ($employee['username'] != $_POST['sUsername_Employees'] && file_exists("../home/".$_POST['sUsername_Employees'])) {
			echo "<f><msg>The username you are requesting already belongs to another employee.</msg></f>";
			exit();
		}

		$gbl_errs['error'] = "Failed to update an existing employee in the database.";
		$gbl_info['command'] = "UPDATE ".PREFIX."Employees SET manager=?,disabled=?,status=?,username=?,password=?,name=?,OPoID=?,homePhone=?,homeMobile=?,homeMobileSMS=?,homeMobileEmail=?,homeEmail=?,homeAddr1=?,homeAddr2=?,homeCity=?,homeState=?,homeZip=?,homeCountry=?,workPhone=?,workExt=?,workMobile=?,workMobileSMS=?,workMobileEmail=?,workEmail=?,workAddr1=?,workAddr2=?,workCity=?,workState=?,workZip=?,workCountry=?,locationID=?,departmentID=?,supervisorID=?,positionID=?,payTerms=?,payType=?,basePay=?,OTRate=?,PTORate=?,SickRate=?,payCOLA=?,payMileage=?,payPerDiem=?,hired=?,driversLicense=?,gender=?,ssn=?,dob=?,race=?,married=?,withholdings=?,additional=?,dependents=?,question1=?,question2=?,question3=?,answer1=?,answer2=?,answer3=?,updated='".$_."' WHERE id='".$employee['id']."'";
		$gbl_info['values'] = '[i] '.$_POST['nManager_Employees'].', [i] '.$disabled.', [s] '.$_POST['sAccountStatus_Employees'].', [s] '.$_POST['sUsername_Employees'].', [s] '.$password.', [s] '.$_POST['sEmployeeName_Employees'].', [s] '.$_POST['sOPoID_Employees'].', [s] '.$phone.', [s] '.$mobile.', [i] '.$_POST['bHomeMobileSMS_Employees'].', [i] '.$_POST['bHomeMobileEmail_Employees'].', [s] '.$email.', [s] '.$addr.', [s] '.$_POST['sHomeAddr2_Employees'].', [s] '.$_POST['sHomeCity_Employees'].', [s] '.$_POST['sHomeState_Employees'].', [s] '.$_POST['sHomeZip_Employees'].', [s] '.$_POST['sHomeCountry_Employees'].', [i] '.$_POST['nWorkPhone_Employees'].', [i] '.$_POST['nWorkExt_Employees'].', [i] '.$_POST['nWorkMobile_Employees'].', [i] '.$_POST['bWorkMobileSMS_Employees'].', [i] '.$_POST['bWorkMobileEmail_Employees'].', [s] '.$_POST['sWorkEmail_Employees'].', [s] '.$_POST['sWorkAddr1_Employees'].', [s] '.$_POST['sWorkAddr2_Employees'].', [s] '.$_POST['sWorkCity_Employees'].', [s] '.$_POST['sWorkState_Employees'].', [s] '.$_POST['sWorkZip_Employees'].', [s] '.$_POST['sWorkCountry_Employees'].', [i] '.$_POST['nWorkLocation_Employees'].', [i] '.$_POST['sDept_Employees'].', [i] '.$_POST['sSupervisor_Employees'].', [s] '.$_POST['sJobTitle_Employees'].', [s] '.$_POST['sPayTerms_Employees'].', [s] '.$_POST['sPayType_Employees'].', [d] '.$_POST['nStandardPay_Employees'].', [d] '.$_POST['nOTPay_Employees'].', [d] '.$_POST['nPersonalLeave_Employees'].', [d] '.$_POST['nSickLeave_Employees'].', [d] '.$_POST['nCOLA_Employees'].', [d] '.$_POST['nMileage_Employees'].', [d] '.$_POST['nPerDiem_Employees'].', [s] '.$_POST['eHired_Employees'].', [s] '.$license.', [s] '.$_POST['sGender_Employees'].', [s] '.$ssn.', [s] '.$_POST['eDOB_Employees'].', [s] '.$_POST['sRace_Employees'].', [s] '.$_POST['sMarried_Employees'].', [d] '.$_POST['nWithheld_Employees'].', [i] '.$_POST['nAllowance_Employees'].', [i] '.$_POST['nDependents_Employees'].', [s] '.$_POST['txtQuestion01'].', [s] '.$_POST['txtQuestion02'].', [s] '.$_POST['txtQuestion03'].', [s] '.$ans1.', [s] '.$ans2.', [s] '.$ans3;
		$stmt = $linkDB->prepare($gbl_info['command']);
		$stmt->bind_param('iisssssssiisssssssiiiiisssssssiiisssdddddddsssssssdiissssss', $_POST['nManager_Employees'], $disabled, $_POST['sAccountStatus_Employees'], $_POST['sUsername_Employees'], $password, $_POST['sEmployeeName_Employees'], $_POST['sOPoID_Employees'], $phone, $mobile, $_POST['bHomeMobileSMS_Employees'], $_POST['bHomeMobileEmail_Employees'], $email, $addr, $_POST['sHomeAddr2_Employees'], $_POST['sHomeCity_Employees'], $_POST['sHomeState_Employees'], $_POST['sHomeZip_Employees'], $_POST['sHomeCountry_Employees'], $_POST['nWorkPhone_Employees'], $_POST['nWorkExt_Employees'], $_POST['nWorkMobile_Employees'], $_POST['bWorkMobileSMS_Employees'], $_POST['bWorkMobileEmail_Employees'], $_POST['sWorkEmail_Employees'], $_POST['sWorkAddr1_Employees'], $_POST['sWorkAddr2_Employees'], $_POST['sWorkCity_Employees'], $_POST['sWorkState_Employees'], $_POST['sWorkZip_Employees'], $_POST['sWorkCountry_Employees'], $_POST['nWorkLocation_Employees'], $_POST['sDept_Employees'], $_POST['sSupervisor_Employees'], $_POST['sJobTitle_Employees'], $_POST['sPayTerms_Employees'], $_POST['sPayType_Employees'], $_POST['nStandardPay_Employees'], $_POST['nOTPay_Employees'], $_POST['nPersonalLeave_Employees'], $_POST['nSickLeave_Employees'], $_POST['nCOLA_Employees'], $_POST['nMileage_Employees'], $_POST['nPerDiem_Employees'], $_POST['eHired_Employees'], $license, $_POST['sGender_Employees'], $ssn, $_POST['eDOB_Employees'], $_POST['sRace_Employees'], $_POST['sMarried_Employees'], $_POST['nWithheld_Employees'], $_POST['nAllowance_Employees'], $_POST['nDependents_Employees'], $_POST['txtQuestion01'], $_POST['txtQuestion02'], $_POST['txtQuestion03'], $ans1, $ans2, $ans3);
		$stmt->execute();

		# now change the home directory if the username has indeed changed
		if ($employee['username'] != $_POST['sUsername_Employees']) {
			$gbl_errs['error'] = "The \"../home/".$_POST['sUsername_Employees']."\" home directory can not be renamed for the employee.";
			$gbl_info['command'] = "rename(\"../home/".$employee['username']."\", \"../home/".$_POST['sUsername_Employees']."\")";
			$gbl_info['values'] = 'None';
			rename("../home/".$employee['username'], "../home/".$_POST['sUsername_Employees']);
		}
	}
#file_put_contents('debug.txt', "08\n", FILE_APPEND);

	# NOTE: the 'username' and 'decrypt' value are passed below ONLY if the account is adjusting itself (incase those values where adjusted by the user)
	echo "<s><msg>The employee information has been updated successfully!</msg><data id='".$employee['id']."' ";   if ($employee['id'] == $gbl_user['id']) { echo "username=\"".$_POST['sUsername_Employees']."\" decrypt=\"\""; }   echo "></data></s>";	# NOTE: return a <data>...</data> element so that updated values get stored correctly for the *CURRENT* user
	exit();




} else if ($_POST['action'] == 'donate' && $_POST['target'] == 'time') {		# DONATE TIME
	# validate all submitted data
	if (! validate($_POST['SID'],40,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['username'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
	# -----
	if (! validate($_POST['sourceID'],20,'[^0-9]')) { exit(); }
	if (! validate($_POST['targetID'],20,'[^0-9]')) { exit(); }
	if (! validate($_POST['type'],4,'{pto|sick}')) { exit(); }
	if (! validate($_POST['hours'],3,'[^0-9]')) { exit(); }

	# obtain the employee information of the person WHO SAVED THE RECORD
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'])) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'])) {exit();} }

	# NOTE: we do NOT check for access to this module since all employees should at least have access to their own records


	# 1. Obtain the LAST 'leave' record (which is the cut off date of the prior pay period) for the employee
	$gbl_errs['error'] = "Failed to obtain the last employee 'Leave Time' in the database when donating time.";
	$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees_Timesheets WHERE employeeID='".$gbl_user['id']."' AND type='leave' ORDER BY time DESC LIMIT 1";
	$gbl_info['values'] = 'None';
	$Leave = $linkDB->query($gbl_info['command']);

	# 2. Store the hours for PTO and SICK
	$timeoff=array();
	$PTO = 0;
	$SICK = 0;
	if ($Leave->num_rows === 0) {					# if there isn't any prior record (e.g. a new employee), then assign default values
		#$timeoff['PPPAP'] = 0.00;				# prior pay periods accrued pto
		$timeoff['YTDAP'] = 0.00;				# year-to-date accrued pto (less the used pto - to show available leave)
		#$timeoff['PPPUP'] = 0.00;				# prior pay periods used pto
		#$timeoff['YTDUP'] = 0.00;				# year-to-date used pto
		#$timeoff['PPPAS'] = 0.00;				# prior pay periods accrued sick leave
		$timeoff['YTDAS'] = 0.00;				# year-to-date accrued sick leave (less the used sick leave - to show available leave)
		#$timeoff['PPPUS'] = 0.00;				# prior pay periods used pto
		#$timeoff['YTDUS'] = 0.00;				# year-to-date used sick leave
	} else {
		$leave = $Leave->fetch_assoc();
		$Timeoff = explode("|", $leave['memo']);		# populate the below array with the prior pay periods leave values

		#$timeoff['PPPAP'] = $Timeoff[0];
		$timeoff['YTDAP'] = $Timeoff[1];
		#$timeoff['PPPUP'] = 0.00;				# set default value
		#$timeoff['YTDUP'] = $Timeoff[2];
		#$timeoff['PPPAS'] = $Timeoff[3];
		$timeoff['YTDAS'] = $Timeoff[4];
		#$timeoff['PPPUS'] = 0.00;				# set default value
		#$timeoff['YTDUS'] = $Timeoff[5];

		# obtain all the CURRENT pay periods timesheet records (after the $leave['time']) to get the most update-to-date values of the employees leave (e.g. the employee has used some of their leave in the CURRENT pay period which is NOT reflected in the last 'leave' record values)
		$gbl_errs['error'] = "Failed to obtain the current employee 'Leave Time' in the database when donating time.";
		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees_Timesheets WHERE employeeID='".$gbl_user['id']."' AND type<>'pay' AND type<>'leave' AND time > '".$leave['time']."' ORDER BY time ASC";
		$gbl_info['values'] = 'None';
		$Current = $linkDB->query($gbl_info['command']);
		while ($current = $Current->fetch_assoc()) {
			if ($pto != '' && $current['type'] == 'out') {			# if we reached the corresponding 'out' of a 'pto' record, then...
				$diff = strtotime($current['time']) - strtotime($pto);	# returns in seconds based on the 'pto' time value and its corresponding 'out'
				$mins = floor($diff/60);				# converts to minutes
				$PTO += round($mins/60, 2);				# converts to fractional hours and stores in the variable for calculations below
			} else if ($sick != '' && $current['type'] == 'out') {		# if we reached the corresponding 'out' of a 'sick' record, then...
				$diff = strtotime($current['time']) - strtotime($sick);
				$mins = floor($diff/60);
				$SICK += round($mins/60, 2);
			}

			$pto = '';					# (re)set the values each iteration
			$sick = '';
			if ($current['type'] == 'pto') { $pto = $current['time']; }
			else if ($current['type'] == 'sick') { $sick = $current['time']; }
		}
	}


	# if the user is trying to donate PTO -AND- the available time minus any used in the CURRENT billing cycle is greater than the donated time, then...
	if ($_POST['type'] == 'pto' && ($timeoff['YTDAP'] - $PTO) < $_POST['hours']) {
		echo "<f><msg>You do not currently have enough PTO to donate that quantity.</msg></f>";
		exit();
	} else if ($_POST['type'] == 'sick' && ($timeoff['YTDAS'] - $SICK) < $_POST['hours']) {
		echo "<f><msg>You do not currently have enough PTO to donate that quantity.</msg></f>";
		exit();
	}

	# if we've made it down here, everything is good to make the donation
	$gbl_errs['error'] = "Failed to create the leave donation record in the database.";
	$gbl_info['command'] = "INSERT INTO ".PREFIX."Employees_Donation (sourceID,targetID,hours,type,created,updated) VALUES (?,?,?,?,'".$_."','".$_."')";
	$gbl_info['values'] = '[i] '.$_POST['sourceID'].', [i] '.$_POST['targetID'].', [i] '.$_POST['hours'].', [s] '.$_POST['type'];
	$stmt = $linkDB->prepare($gbl_info['command']);
	$stmt->bind_param('iiis', $_POST['sourceID'], $_POST['targetID'], $_POST['hours'], $_POST['type']);
	$stmt->execute();

	echo "<s><msg>The time has been donated successfully!</msg></s>";
	exit();




} else if ($_POST['action'] == 'update' && $_POST['target'] == 'access') {	# SAVE MODULE ACCESS
	# validate all submitted data
	if (! validate($_POST['SID'],40,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['username'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
	# -----
	if (! validate($_POST['accountID'],20,'[^0-9]')) { exit(); }
	if (! validate($_POST['sModuleList_Employees'],20,'[^0-9]')) { exit(); }
	if (! validate($_POST['type'],4,'{pto|sick}')) { exit(); }
	if (! validate($_POST['hours'],2,'[^0-9]')) { exit(); }
# LEFT OFF - update the above to check for the correct passed values
#		also see about putting the <select> in the main <form> in the .html file

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'],"\$gbl_user['manager']<1|\$gbl_user['supervisorID']!=0","Your account does not have sufficient priviledges to modify the module access.|You will need to have your supervisor adjust the module access for your account.")) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','ro','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'],"\$gbl_user['manager']<1|\$gbl_user['supervisorID']!=0","Your account does not have sufficient priviledges to modify the module acc.|You will need to have your supervisor adjust the module access for your account.")) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."Application_Modules WHERE name='Employees' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission.";
	$gbl_info['command'] = "SELECT `write` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['write'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to save (write) data in this module.</msg></f>";
		exit();
	}


	# if we've made it here, we are good to make changes in the database
	$gbl_errs['error'] = "Failed to find the Employee access record in the database when updating module access permissions.";
	$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees_Access WHERE employeeID=? AND moduleID=? LIMIT 1";
	$gbl_info['values'] = '[i] '.$_POST['accountID'].', [i] '.$_POST['sModuleList_Employees'];
	$Perm = $linkDB->prepare($gbl_info['command']);
	$Perm->bind_param('ii', $_POST['accountID'], $_POST['sModuleList_Employees']);
	$Perm->execute();
	if ($Perm->num_rows === 0) {			# if not, then create it!
		$gbl_errs['error'] = "Failed to create the module access permission record in the database.";
		$gbl_info['command'] = "INSERT INTO ".PREFIX."Employees_Access (employeeID,moduleID,`read`,`write`,`add`,`del`) VALUES (?,?,?,?,?,?)";
		$gbl_info['values'] = '[i] '.$_POST['accountID'].', [i] '.$_POST['sModuleList_Employees'].', [i] '.$_POST['chkRead_Employees'].', [i] '.$_POST['chkWrite_Employees'].', [i] '.$_POST['chkAddRecords_Employees'].', [i] '.$_POST['chkDelRecords_Employees'];
		$stmt = $linkDB->prepare($gbl_info['command']);
		$stmt->bind_param('iiiiii', $_POST['accountID'], $_POST['sModuleList_Employees'], $_POST['chkRead_Employees'], $_POST['chkWrite_Employees'], $_POST['chkAddRecords_Employees'], $_POST['chkDelRecords_Employees']);
		$stmt->execute();
	} else {					# otherwise we do, so just update it!
		$perm = $Perm->fetch_assoc();

		$gbl_errs['error'] = "Failed to update the module access permission record in the database.";
		$gbl_info['command'] = "UPDATE ".PREFIX."Employees_Access SET `read`=?,`write`=?,`add`=?,`del`=? WHERE id='".$record['id']."'";
		$gbl_info['values'] = '[i] '.$_POST['chkRead_Employees'].', [i] '.$_POST['chkWrite_Employees'].', [i] '.$_POST['chkAddRecords_Employees'].', [i] '.$_POST['chkDelRecords_Employees'];
		$stmt = $linkDB->prepare($gbl_info['command']);
		$stmt->bind_param('iiii', $_POST['chkRead_Employees'], $_POST['chkWrite_Employees'], $_POST['chkAddRecords_Employees'], $_POST['chkDelRecords_Employees']);
		$stmt->execute();
	}

	echo "<s><msg>The module access has been updated successfully!</msg></s>";
	exit();




} else if ($_POST['action'] == 'update' && ($_POST['target'] == 'skin' || $_POST['target'] == 'theme' || $_POST['target'] == 'icons')) {		# UPDATE THE SKIN, THEME, ICON SET
	# validate all submitted data
	if (! validate($_POST['SID'],40,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['username'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
	# -----
	if (! validate($_POST['target'],4,'{skin|theme|icons}')) { exit(); }
	if (! validate($_POST['sSkinsList_Employees'],64,'[^a-zA-Z0-9_\. \-]')) { exit(); }
	if (! validate($_POST['sIconsList_Employees'],64,'[^a-zA-Z0-9_\. \-]')) { exit(); }

	switch ($_POST['target']) {
		case 'skin':
			if (! file_exists('../look/'.$_POST['sSkinsList_Employees'])) {
				echo "<f><msg>The ".$_POST['target']." does not exist, no changes have been made.</msg></f>";
				exit();
			}
			unlink('../home/'.$_POST['username'].'/look');		# delete the prior symlink
			symlink('../../look/'.$_POST['sSkinsList_Employees'], '../home/'.$_POST['username'].'/look');
			break;
# REMOVED 2020/06/03 - this is no longer applicable
#		case 'theme':
#			if (! file_exists('../../themes/'.$_POST['sSkinsList_Employees'].'/styles/'.$_POST['sThemesList_Employees'])) {
#				echo "<f><msg>The ".$_POST['target']." does not exist, no changes have been made.</msg></f>";
#				exit();
#			}
#			unlink('../../data/'.$_POST['username'].'/_theme/styles');
#			symlink('../../../themes/'.$_POST['sSkinsList_Employees'].'/styles/'.$_POST['sThemesList_Employees'], '../../data/'.$_POST['username'].'/_theme/styles');
#			break;
		case 'icons':
			if (! file_exists('../imgs/'.$_POST['sIconsList_Employees'])) {
				echo "<f><msg>The ".$_POST['target']." does not exist, no changes have been made.</msg></f>";
				exit();
			}
			unlink('../home/'.$_POST['username'].'/imgs');
			symlink('../../imgs/'.$_POST['sIconsList_Employees'], '../home/'.$_POST['username'].'/imgs');

			# provide symlinks to the default image set icons that are lacking in the newly selected image set for this employee account
			if ($_POST['sIconsList_Employees'] != 'default') {			// if the selected icon set is anything other than the 'default', then...
				if ($path = opendir('../imgs/default')) {			// WARNING: this path must contain all the 'default's
					while (false !== ($file = readdir($path))) {		// traverse each image in the default set
						if ($file == "." || $file == "..") { continue; }

						// create a symlink for any missing images (so there are icons for any installed modules that don't have an included image in the newly selected image set)
						if (! file_exists('../imgs/'.$_POST['sIconsList_Employees'].'/'.$file))
							{ @symlink('../default/'.$file, '../imgs/'.$_POST['sIconsList_Employees'].'/'.$file); }
					}
					closedir($path);
				}
			}
			break;
	}

	echo "<s><msg>The ".$_POST['target']." has been updated successfully!</msg></s>";
	exit();




} else if ($_POST['action'] == 'update' && $_POST['target'] == 'work') {		# CREATE/UPDATE THE TIME RECORD IN THE LIST
	# validate all submitted data
	if (! validate($_POST['SID'],40,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['username'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
	# -----
	if (! validate($_POST['id'],20,'[^0-9]')) { exit(); }
	if (! validate($_POST['employee'],20,'[^0-9]')) { exit(); }
	if (! validate($_POST['sLeaveType_Employees'],6,'{in|out|pto|sick|unpaid}')) { exit(); }
	if (! validate($_POST['eLeaveDate_Employees'],10,'[^0-9\-]')) { exit(); }
	if (! validate($_POST['sLeaveHalf_Employees'],2,'{am|pm}')) { exit(); }
	if (! validate($_POST['nLeaveHour_Employees'],2,'[^0-9]')) { exit(); }
	if (! validate($_POST['nLeaveMin_Employees'],2,'[^0-9]')) { exit(); }
	if (! validate($_POST['eLeaveHours_Employees'],5,'[^0-9\.]')) { exit(); }
	if (! validate($_POST['sLeaveMemo_Employees'],64,'![=<>;]')) { exit(); }

	# obtain the employee information of the person WHO SAVED THE RECORD
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'])) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'])) {exit();} }

	# NOTE: we do NOT check for access to this module since all employees should at least have access to their own records


	# if we've made it here, the user can make changes
	if ($_POST['id'] == 0) {		# if we need to create a new record, then...
		if ($_POST['sLeaveType_Employees'] == 'in' || $_POST['sLeaveType_Employees'] == 'out') {
			# if the record isn't a _SYSTEM_ record -AND - a manager has updated the record -AND- they have a supervisor (e.g. not the CEO), then...
			if ($time['updatedBy'] != 0 && $time['updatedBy'] != $gbl_user['id'] && $gbl_user['supervisorID'] > 0) {
				echo "<f><msg>The record has been updated by a manager which prevents modification by your account.</msg></f>";
				exit();

			# if the user didn't create the record (this includes _SYSTEM_ records for security!) -AND- they have a supervisor (e.g. not the CEO), then...
			} else if ($time['createdBy'] != $gbl_user['id'] && $gbl_user['supervisorID'] > 0) {
				echo "<f><msg>The record is not a leave request which prevents modification by your account.</msg></f>";
				exit();
			}

			# if we've made it here, then the user is authorized to create the timesheet record
			$time = $_POST['eLeaveDate_Employees'].' ';		# add the date
			if ($_POST['sLeaveHalf_Employees'] == 'pm') { $time .= ($_POST['nLeaveHour_Employees'] + 12); } else { $time .= $_POST['nLeaveHour_Employees']; }
			$time .= ':'.$_POST['nLeaveMin_Employees'].':00';	# add the minutes and seconds (from the existing record)

#file_put_contents('debug.txt', "INSERT INTO ".PREFIX."Employees_Timesheets (employeeID,type,time,memo,createdBy,createdOn,updatedBy,updatedOn) VALUES ('".$_POST['employee']."','".$_POST['sLeaveType_Employees']."','".$time."',\"".$_POST['sLeaveMemo_Employees']."\",'".$gbl_user['id']."','".$_."','".$gbl_user['id']."','".$_."')\n", FILE_APPEND);
			$gbl_errs['error'] = "Failed to create a new 'in/out' timesheet record in the database.";
			$gbl_info['command'] = "INSERT INTO ".PREFIX."Employees_Timesheets (employeeID,type,time,memo,createdBy,createdOn,updatedBy,updatedOn) VALUES (?,?,?,?,'".$gbl_user['id']."','".$_."','".$gbl_user['id']."','".$_."')";
			$gbl_info['values'] = '[i] '.$_POST['employee'].', [s] '.$_POST['sLeaveType_Employees'].', [i] '.$time.', [s] '.$_POST['sLeaveMemo_Employees'];
			$stmt = $linkDB->prepare($gbl_info['command']);
			$stmt->bind_param('isis', $_POST['employee'], $_POST['sLeaveType_Employees'], $time, $_POST['sLeaveMemo_Employees']);
			$stmt->execute();

		} else {			# otherwise we need to request leave (e.g. pto, sick, or unpaid)
			$time = $_POST['eLeaveDate_Employees'].' ';		# add the date
			if ($_POST['sLeaveHalf_Employees'] == 'pm') { $time .= ($_POST['nLeaveHour_Employees'] + 12); } else { $time .= $_POST['nLeaveHour_Employees']; }
			$time .= ':'.$_POST['nLeaveMin_Employees'].':00';	# add the minutes and seconds (from the existing record)

#file_put_contents('debug.txt', "INSERT INTO ".PREFIX."Employees_Timesheets (employeeID,type,time,memo,createdBy,createdOn,updatedBy,updatedOn) VALUES ('".$_POST['employee']."','".$_POST['sLeaveType_Employees']."','".$time."',\"".$_POST['sLeaveMemo_Employees']."\",'".$gbl_user['id']."','".$_."','".$gbl_user['id']."','".$_."')\n", FILE_APPEND);
			$gbl_errs['error'] = "Failed to create a new 'pto/sick/unpaid' timesheet record in the database.";
			$gbl_info['command'] = "INSERT INTO ".PREFIX."Employees_Timesheets (employeeID,type,time,memo,createdBy,createdOn,updatedBy,updatedOn) VALUES (?,?,?,?,'".$gbl_user['id']."','".$_."','".$gbl_user['id']."','".$_."')";
			$gbl_info['values'] = '[i] '.$_POST['employee'].', [s] '.$_POST['sLeaveType_Employees'].', [i] '.$time.', [s] '.$_POST['sLeaveMemo_Employees'];
			$stmt = $linkDB->prepare($gbl_info['command']);
			$stmt->bind_param('isis', $_POST['employee'], $_POST['sLeaveType_Employees'], $time, $_POST['sLeaveMemo_Employees']);
			$stmt->execute();

			$mins = round($_POST['eLeaveHours_Employees']*60, 1);	# convert the leave hours requested into minutes for the next line
			$new = strtotime("+".$mins." minutes",strtotime($time));
#file_put_contents('debug.txt', "mins is now :".$mins.":\n", FILE_APPEND);

#file_put_contents('debug.txt', "INSERT INTO ".PREFIX."Employees_Timesheets (employeeID,type,time,memo,createdBy,createdOn,updatedBy,updatedOn) VALUES ('".$_POST['employee']."','out','".date('Y-m-d H:i:s', $new)."',\"\",'".$gbl_user['id']."','".$_."','".$gbl_user['id']."','".$_."')\n", FILE_APPEND);
			$gbl_errs['error'] = "Failed to create a follow-up 'out' timesheet record in the database.";
			$gbl_info['command'] = "INSERT INTO ".PREFIX."Employees_Timesheets (employeeID,type,time,memo,createdBy,createdOn,updatedBy,updatedOn) VALUES (?,'out','".date('Y-m-d H:i:s', $new)."',?,'".$gbl_user['id']."','".$_."','".$gbl_user['id']."','".$_."')";
			$gbl_info['values'] = '[i] '.$_POST['employee'].', [s] '.$_POST['sLeaveMemo_Employees'];
			$stmt = $linkDB->prepare($gbl_info['command']);
			$stmt->bind_param('is', $_POST['employee'], $_POST['sLeaveMemo_Employees']);
			$stmt->execute();
		}


	} else {				# otherwise we need to update the record...
		# obtain the record-to-be-updated to make a couple of checks below
		$gbl_errs['error'] = "Failed to find the specified timesheet record in the database when updating the timesheet.";
		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees_Timesheets WHERE id=? LIMIT 1";
		$gbl_info['values'] = '[i] '.$_POST['id'];
		$Work = $linkDB->prepare($gbl_info['command']);
		$Work->bind_param('i', $_POST['id']);
		$Work->execute();
		$work = $Work->get_result()->fetch_assoc();

		# now get the timesheet record that follows the obtained record from above (we are looking for the corresponding 'out' record)
		# WARNING: this is executed BEFORE the below record update to prevent any bugs where the 'in|pto|sick|etc' record time gets adjusted AFTER the time of the corresponding 'out' record!
		$gbl_errs['error'] = "Failed to find the corresponding 'out' record in the database when updating the timesheet.";
		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees_Timesheets WHERE employeeID='".$work['employeeID']."' AND time > '".$work['time']."' ORDER BY time LIMIT 1";	# gets the next record according to the next largest record timestamp
		$gbl_info['values'] = 'None';
		$Next = $linkDB->query($gbl_info['command']);

		# if the record isn't a _SYSTEM_ record -AND - a manager has updated the record -AND- they have a supervisor (e.g. not the CEO), then...
		if ($work['updatedBy'] != 0 && $work['updatedBy'] != $gbl_user['id'] && $gbl_user['supervisorID'] > 0) {
			echo "<f><msg>The record has been updated by a manager which prevents modification by your account.</msg></f>";
			exit();

		# if the user didn't create the record (this includes _SYSTEM_ records for security!) -AND- they have a supervisor (e.g. not the CEO), then...
		} else if ($work['createdBy'] != $gbl_user['id'] && $gbl_user['supervisorID'] > 0) {
			echo "<f><msg>The record is not a leave request which prevents modification by your account.</msg></f>";
			exit();
		}

		# if we've made it here, the account is authorized to update the selected record from the UI, so...
		$time = $_POST['eLeaveDate_Employees'].' ';					# add the date
		if ($_POST['sLeaveHalf_Employees'] == 'pm') { $time .= ($_POST['nLeaveHour_Employees'] + 12); } else { $time .= $_POST['nLeaveHour_Employees']; }
		$time .= ':'.$_POST['nLeaveMin_Employees'].':'.substr($work['time'], -2);	# add the minutes and seconds (from the existing record)

#file_put_contents('debug.txt', "UPDATE ".PREFIX."Employees_Timesheets SET type=\"".$_POST['sLeaveType_Employees']."\",time=\"".$time."\",memo=\"".$_POST['sLeaveMemo_Employees']."\",updatedBy=\"".$gbl_user['id']."\",updatedOn=\"".$_."\" WHERE id='".$_POST['id']."'\n", FILE_APPEND);
		$gbl_errs['error'] = "Failed to update the specified timesheet record in the database when updating the timesheet.";
		$gbl_info['command'] = "UPDATE ".PREFIX."Employees_Timesheets SET type=?,time=?,memo=?,updatedBy=\"".$gbl_user['id']."\",updatedOn=\"".$_."\" WHERE id=?";
		$gbl_info['values'] = '[s] '.$_POST['sLeaveType_Employees'].', [i] '.$time.', [s] '.$_POST['sLeaveMemo_Employees'].', [i] '.$_POST['id'];
		$Work = $linkDB->prepare($gbl_info['command']);
		$Work->bind_param('sisi', $_POST['sLeaveType_Employees'], $time, $_POST['sLeaveMemo_Employees'], $_POST['id']);
		$Work->execute();

		if ($work['type'] != 'out') {			# if the user is modifying the first record of a pair (e.g. pto, sick, unpaid), then lets make sure that the number of hours hasn't been adjusted
#file_put_contents('debug.txt', "we are obtaining a trailing 'out' record...\n", FILE_APPEND);
			if ($Next->num_rows !== 0) {
				$next = $Next->fetch_assoc();

				if ($next['type'] == 'out') {							# only process the below code if 'next' contains the corresponding 'out' record to a 'pto' or 'sick'
					$diff = strtotime($next['time']) - strtotime($time);			# returns in seconds based on the updated time value passed for the record being updated
					$mins = floor($diff/60);						# converts to minutes
					$hour = round($mins/60, 2);
#file_put_contents('debug.txt', "comparing :".$hour.": to :".$_POST['eLeaveHours_Employees'].":\n", FILE_APPEND);

					// if the records 'time' value has been modified -OR- the number of hours has been adjusted, then we need to update the 'time' value of the corresponding 'out' record
					if ($time != $work['time'] || $hour != $_POST['eLeaveHours_Employees']) {
						$mins = round($_POST['eLeaveHours_Employees']*60, 1);		# convert the leave hours requested into minutes for the next line
#file_put_contents('debug.txt', "   mins is now :".$mins.":\n", FILE_APPEND);
						$new = strtotime("+".$mins." minutes",strtotime($time));

#file_put_contents('debug.txt', "   we need to adjust the corresponding 'out' record...\n   UPDATE ".PREFIX."Employees_Timesheets SET time=\"".date('Y-m-d H:i:s', $new)."\",updatedBy=\"".$gbl_user['id']."\",updatedOn=\"".$_."\" WHERE id='".$next['id']."'\n", FILE_APPEND);
						$gbl_errs['error'] = "Failed to update the specified time for the record in the database when updating the timesheet.";
						$gbl_info['command'] = "UPDATE ".PREFIX."Employees_Timesheets SET time=\"".date('Y-m-d H:i:s', $new)."\",updatedBy=\"".$gbl_user['id']."\",updatedOn=\"".$_."\" WHERE id='".$next['id']."'";
						$gbl_info['values'] = 'None';
						$stmt = $linkDB->query($gbl_info['command']);
					}
				}
			}
		}
	}

	# now obtain and return the updated time list based on any filtered items that were given
	$query = '';
	if ($_POST['date'] != '') {
		if ($_POST['direction'] == 'before')
			{ $query .= " AND DATE(tblET.time) < '".$_POST['date']."'"; }
		else
			{ $query .= " AND DATE(tblET.time) > '".$_POST['date']."'"; }
	}

	$gbl_errs['error'] = "Failed to find all employee time records in the database when updating the timesheet.";
	$gbl_info['command'] = "SELECT tblET.*,tblEM.name FROM ".PREFIX."Employees_Timesheets tblET LEFT JOIN ".PREFIX."Employees tblEM ON tblET.updatedBy = tblEM.id WHERE tblET.employeeID='".$gbl_user['id']."' AND tblET.type<>'pay' AND tblET.type<>'leave'".$query." ORDER BY tblET.time";
	$gbl_info['values'] = 'None';
	$Work = $linkDB->query($gbl_info['command']);

	echo "<s>\n";
	echo "   <xml>\n";

	echo "	<time>\n";
	while ($work = $Work->fetch_assoc()) {
		if ($work['createdBy'] == 0) { $name = '_SYSTEM_'; } else { $name = $work['name']; }
		echo "	<record id=\"".$work['id']."\" type=\"".$work['type']."\" occurred=\"".$work['time']."\" createdID=\"".$work['createdBy']."\" createdBy=\"".safeXML($name)."\" />\n";
	}
	echo "	</time>\n";

	echo "   </xml>\n";
	echo "</s>";
	exit();




} else if ($_POST['action'] == 'delete' && $_POST['target'] == 'work') {		# DELETE THE TIME RECORD FROM THE LIST
	# validate all submitted data
	if (! validate($_POST['SID'],40,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['username'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
	# -----
	if (! validate($_POST['id'],20,'[^0-9]')) { exit(); }
	if (! validate($_POST['date'],10,'[^0-9\-]')) { exit(); }
	if (! validate($_POST['direction'],6,'{before|after}')) { exit(); }

	# obtain the employee information of the person WHO SAVED THE RECORD
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'])) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'])) {exit();} }

	# NOTE: we do NOT check for access to this module since all employees should at least have access to their own records


	# obtain the record-to-be-deleted to make a couple of checks below
	$gbl_errs['error'] = "Failed to find specific time record in the database when deleting an entry.";
	$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees_Timesheets WHERE id=? LIMIT 1";
	$gbl_info['values'] = '[i] '.$_POST['id'];
	$Time = $linkDB->prepare($gbl_info['command']);
	$Time->bind_param('i', $_POST['id']);
	$Time->execute();
	$time = $Time->get_result()->fetch_assoc();

	# if the record isn't a _SYSTEM_ record -AND - a manager has updated the record -AND- they are a supervisor (e.g. not the CEO), then...
	if ($time['updatedBy'] != 0 && $time['updatedBy'] != $gbl_user['id'] && $gbl_user['supervisorID'] > 0) {
		echo "<f><msg>The record has been updated by a manager which prevents modification by your account.</msg></f>";
		exit();

	# if the user didn't create the record (this includes _SYSTEM_ records for security!) -AND- they have a supervisor (e.g. not the CEO), then...
	} else if ($time['createdBy'] != $gbl_user['id'] && $gbl_user['supervisorID'] > 0) {
		echo "<f><msg>The record is not a leave request which prevents modification by your account.</msg></f>";
		exit();
	}

	# if we've made it here, the account is authorized to delete the record, so...
	$gbl_errs['error'] = "Failed to delete the specific time record in the database.";
	$gbl_info['command'] = "DELETE FROM ".PREFIX."Employees_Timesheets WHERE id=? LIMIT 1";
	$gbl_info['values'] = '[i] '.$_POST['id'];
	$Time = $linkDB->prepare($gbl_info['command']);
	$Time->bind_param('i', $_POST['id']);
	$Time->execute();

	# now obtain and return the updated time list based on any filtered items that were given
	$query = '';
	if ($_POST['date'] != '') {
		if ($_POST['direction'] == 'before')
			{ $query .= " AND DATE(tblET.time) < '".$_POST['date']."'"; }
		else
			{ $query .= " AND DATE(tblET.time) > '".$_POST['date']."'"; }
	}

	$gbl_errs['error'] = "Failed to find all employee time records in the database when updating the timesheet.";
	$gbl_info['command'] = "SELECT tblET.*,tblEM.name FROM ".PREFIX."Employees_Timesheets tblET LEFT JOIN ".PREFIX."Employees tblEM ON tblET.updatedBy = tblEM.id WHERE tblET.employeeID='".$gbl_user['id']."' AND tblET.type<>'pay' AND tblET.type<>'leave'".$query." ORDER BY tblET.time";
	$gbl_info['values'] = 'None';
	$Work = $linkDB->query($gbl_info['command']);

	echo "<s>\n";
	echo "   <xml>\n";

	echo "	<time>\n";
	while ($work = $Work->fetch_assoc()) {
		if ($work['createdBy'] == 0) { $name = '_SYSTEM_'; } else { $name = $work['name']; }
		echo "	<record id=\"".$work['id']."\" type=\"".$work['type']."\" occurred=\"".$work['time']."\" createdID=\"".$work['createdBy']."\" createdBy=\"".$name."\" />\n";
	}
	echo "	</time>\n";

	echo "   </xml>\n";
	echo "</s>";
	exit();




} else if ($_POST['action'] == 'load' && $_POST['target'] == 'time') {		# LOAD A TIME ENTRY FROM THE LIST
	# validate all submitted data
	if (! validate($_POST['SID'],40,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['username'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
	# -----
	if (! validate($_POST['id'],20,'[^0-9]')) { exit(); }

	# obtain the employee information of the person WHO SAVED THE RECORD
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'])) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'])) {exit();} }

	# NOTE: we do NOT check for access to this module since all employees should at least have access to their own records


	# 1. Obtain the entire timesheet for the user
	$gbl_errs['error'] = "Failed to find all employee time records in the database when loading the timesheet.";
	$gbl_info['command'] = "SELECT tblET.*,tblEM.name FROM ".PREFIX."Employees_Timesheets tblET LEFT JOIN ".PREFIX."Employees tblEM ON tblET.updatedBy = tblEM.id WHERE tblET.id=? LIMIT 1";
	$gbl_info['values'] = '[i] '.$_POST['id'];
	$Work = $linkDB->prepare($gbl_info['command']);
	$Work->bind_param('i', $_POST['id']);
	$Work->execute();


	echo "<s>\n";
	echo "   <xml>\n";

	echo "	<time>\n";
	while ($work = $Work->fetch_assoc()) {
		$additional = '';

		# now get the timesheet record that follows the obtained record from above (we are looking for the corresponding 'out' record)
		$gbl_errs['error'] = "Failed to find all employee time records in the database when loading the timesheet.";
		$gbl_info['command'] = "SELECT * FROM ".PREFIX."Employees_Timesheets WHERE employeeID='".$work['employeeID']."' AND time > '".$work['time']."' ORDER BY time LIMIT 1";	// gets the next record according to the next largest record timestamp
		$gbl_info['values'] = 'None';
		$Next = $linkDB->query($gbl_info['command']);
		if ($Next->num_rows !== 0) {
			$next = $Next->fetch_assoc();

			if ($next['type'] == 'out') {							# only create the additional XML parameters if 'next' contains the corresponding 'out' record to a 'pto' or 'sick' (if the employee is looking over their own record)
				$diff = strtotime($next['time']) - strtotime($work['time']);		# returns in seconds	http://stackoverflow.com/questions/2622774/find-difference-between-two-dates-in-php-or-mysql
				$mins = floor($diff/60);						# converts to minutes

				$additional = "time='".$mins."'";
			}
		}

		if ($work['createdBy'] == 0) { $name = '_SYSTEM_'; } else { $name = $work['name']; }
			echo "	<record id=\"".$work['id']."\" type=\"".$work['type']."\" occurred=\"".$work['time']."\" createdID=\"".$work['createdBy']."\" createdBy=\"".safeXML($name)."\" updatedID=\"".$work['updatedBy']."\" ".$additional.">".safeXML($work['memo'])."</record>\n";
	}
	echo "	</time>\n";

	echo "   </xml>\n";
	echo "</s>";
	exit();




} else if ($_POST['action'] == 'filter' && $_POST['target'] == 'work') {	# FILTER THE TIME LIST
	# validate all submitted data
	if (! validate($_POST['SID'],40,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['username'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
	# -----
	if (! validate($_POST['date'],10,'[^0-9\-]')) { exit(); }
	if (! validate($_POST['direction'],6,'{before|after}')) { exit(); }

	# obtain the employee information of the person WHO SAVED THE RECORD
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'])) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'])) {exit();} }

	# NOTE: we do NOT check for access to this module since all employees should at least have access to their own records


	# 1. Obtain the entire timesheet for the user
	$query = '';
	if ($_POST['date'] != '') {
		if ($_POST['direction'] == 'before')
			{ $query .= " AND DATE(tblET.time) < '".$_POST['date']."'"; }
		else
			{ $query .= " AND DATE(tblET.time) > '".$_POST['date']."'"; }
	}

	$gbl_errs['error'] = "Failed to find all employee time records in the database when filtering the timesheet.";
	$gbl_info['command'] = "SELECT tblET.*,tblEM.name FROM ".PREFIX."Employees_Timesheets tblET LEFT JOIN ".PREFIX."Employees tblEM ON tblET.updatedBy = tblEM.id WHERE tblET.employeeID='".$gbl_user['id']."' AND tblET.type<>'pay' AND tblET.type<>'leave'".$query." ORDER BY tblET.time";
	$gbl_info['values'] = 'None';
	$Work = $linkDB->query($gbl_info['command']);

	echo "<s>\n";
	echo "   <xml>\n";

	echo "	<time>\n";
	while ($work = $Work->fetch_assoc()) {
		if ($work['createdBy'] == 0) { $name = '_SYSTEM_'; } else { $name = $work['name']; }
		echo "	<record id=\"".$work['id']."\" type=\"".$work['type']."\" occurred=\"".$work['time']."\" createdID=\"".$work['createdBy']."\" createdBy=\"".safeXML($name)."\" />\n";
	}
	echo "	</time>\n";

	echo "   </xml>\n";
	echo "</s>";
	exit();




} else if ($_POST['action'] == 'save' && $_POST['target'] == 'note') {		# SAVE A NOTE ON THE EMPLOYEE
	# validate all submitted data
	if (! validate($_POST['SID'],40,'[^a-zA-Z0-9]')) { exit(); }
	if (! validate($_POST['username'],128,'[^a-zA-Z0-9@\._\-]')) { exit(); }
	# -----
	if (! validate($_POST['id'],20,'[^0-9]')) { exit(); }
	if ($_POST['sNoteAccess_Employees'] != 'everyone' && $_POST['sNoteAccess_Employees'] != 'managers')
		{ if (! validate($_POST['sNoteAccess_Employees'],20,'[^0-9]')) {exit();} }
	if (! validate($_POST['sNote_Employees'],3072,'![=<>;]')) { exit(); }

	# load the users account info in the global variable
	if (USERS == '')							# IF we need to access the native application DB table, then...
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.'Employees','username','s|'.$_POST['username'],'sid','s|'.$_POST['SID'],"\$gbl_user['manager']<1|\$gbl_user['supervisorID']!=0","Your account does not have sufficient priviledges to modify the module access.|You will need to have your supervisor adjust the module access for your account.")) {exit();} }
	else									# OTHERWISE, we have mapped DB values, so pull the values from that table
		{ if (! loadUser(TIMEOUT,'gbl_user','rw','*',PREFIX.USERS,USERNAME,'s|'.$_POST['username'],SES,'s|'.$_POST['SID'],"\$gbl_user['manager']<1|\$gbl_user['supervisorID']!=0","Your account does not have sufficient priviledges to modify the module acc.|You will need to have your supervisor adjust the module access for your account.")) {exit();} }

	# check that the submitting account has permission to access the module
	$gbl_errs['error'] = "Failed to find the 'Modules ID' value in the database when checking for access permission.";
	$gbl_info['command'] = "SELECT id FROM ".PREFIX."Application_Modules WHERE name='Employees' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Module = $linkDB->query($gbl_info['command']);
	$module = $Module->fetch_assoc();

	$gbl_errs['error'] = "Failed to find the Employee record in the database when checking for access permission.";
	$gbl_info['command'] = "SELECT `add` FROM ".PREFIX."Employees_Access WHERE employeeID='".$gbl_user['id']."' AND moduleID='".$module['id']."' LIMIT 1";
	$gbl_info['values'] = 'None';
	$Access = $linkDB->query($gbl_info['command']);
	if ($Access->num_rows === 0) {			# if the account can't be found, then...
		echo "<f><msg>Your account does not have sufficient priviledges to create (add) data in this module.</msg></f>";
		exit();
	}						# otherwise the account has permission to access, so...
	$access = $Access->fetch_assoc();
	if ($access['add'] == 0) {			# if the account does NOT have 'read' access for this module, then...
		echo "<f><msg>Your account does not have sufficient priviledges to create (add) data in this module.</msg></f>";
		exit();
	}

	# if we've made it here, the user is authorized to create records in the DB
	$gbl_errs['error'] = "Failed to create the note in the database.";
	$gbl_info['command'] = "INSERT INTO ".PREFIX."Notes (type,rowID,creatorID,access,note,created,updated) VALUES ('employee',?,'".$gbl_user['id']."',?,?,'".$_."','".$_."')";
	if ($_POST['sNoteAccess_Employees'] != 'everyone' && $_POST['sNoteAccess_Employees'] != 'managers')
		{ $gbl_info['values'] = '[i] '.$_POST['id'].', [i] '.$_POST['sNoteAccess_Employees'].', [s] '.$_POST['sNote_Employees']; }
	else
		{ $gbl_info['values'] = '[i] '.$_POST['id'].', [s] '.$_POST['sNoteAccess_Employees'].', [s] '.$_POST['sNote_Employees']; }
	$Work = $linkDB->prepare($gbl_info['command']);
	if ($_POST['sNoteAccess_Employees'] != 'everyone' && $_POST['sNoteAccess_Employees'] != 'managers')
		{ $Work->bind_param('iis', $_POST['id'], $_POST['sNoteAccess_Employees'], $_POST['sNote_Employees']); }
	else
		{ $Work->bind_param('iss', $_POST['id'], $_POST['sNoteAccess_Employees'], $_POST['sNote_Employees']); }
	$Work->execute();

	echo "<s><msg>The employee note has been saved successfully!</msg><data date='".$_."' creator='".$gbl_user['name']."'>".safeXML($_POST['sNote_Employees'])."</data></s>";
	exit();




} else {					// otherwise, we need the content pane contents, then...
	echo "<f><msg>An invalid request has occurred, our staff has been notified.</msg></f>";
	if (! array_key_exists('username', $gbl_user)) { $gbl_user['username'] = 'guest'; }
	if (! array_key_exists('email', $gbl_user)) { $gbl_user['email'] = 'Not Provided'; }
	sendMail($gbl_emailCrackers,$gbl_nameCrackers,$gbl_emailNoReply,$gbl_nameNoReply,'!!! Possible Cracking Attempt !!!',"<html>\n<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' offset='0' bgcolor='#ffffff'>\n<table width='100%'>\n<tr>\n<td>&nbsp;</td>\n<td width='500'>\n<img src='".$gbl_uriProject."/home/guest/imgs/email_error.png' border='0' style='float:right; padding-left: 5px;' />\n<h1 style='padding: 50px 0 10px 0; font-size: 32px; font-variant: small-caps; color: #92bfe5;'>".PROJECT."</h1><br />\n<h2 style='margin-bottom: 5px; font: 12pt verdana bold; color: #808080;'>Possible Cracking Attempt</h2><br />\n<p style='font: 12px/17px verdana; color: #808080; text-align: justify;'>\nTeam,<br />\n<br />\nWe might have had a possible cracking attempt made on ".$_.", from ".$_SERVER['REMOTE_ADDR'].", while attempting to pass an invalid API value.  Please investigate and correct this problem as soon as possible.  If the problem warrants contacting the end user, please do so as well by referencing the relevant information below:<br />\n<br />\n<br />\nUsername: ".$gbl_user['username']."<br />\nAddress: ".$gbl_user['email']."<br />\n<br />\nProject: ".PROJECT."<br />\nModule: ".MODULE."<br />\nScript: ".SCRIPT."<br />\n<br />\nDB Host: ".DBHOST."<br />\nDB Name: ".DBNAME."<br />\nDB Prefix: ".PREFIX."<br />\n<br />\nOur Error: An invalid API value was passed to the script.<br />\n<br />\nSincerely,<br />\n".PROJECT." Staff\n<br />\n<br />\n[".SCRIPT."; Body]<br />\n<br />\nVar Dump:<br />\n</p>\n<pre>_POST\n".print_r($_POST, true)."</pre><br />\n<pre>_GET\n".print_r($_GET, true)."</pre><br />\n</td>\n<td>&nbsp;</td>\n</tr>\n</table>\n</body>\n</html>");


}
?>
