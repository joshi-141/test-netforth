<?php
include_once ($_SERVER['DOCUMENT_ROOT'] . '/classes/database.class.php');   
include_once ($_SERVER['DOCUMENT_ROOT'] . '/classes/Listing.class.php');  
include_once ($_SERVER['DOCUMENT_ROOT'] . '/saffronstaysSheet/quickstartNew.php');  
ini_set('date.timezone', 'Europe/Lisbon');
ini_set('intl.default_locale', 'es_ES');


$emailToSearch = 'host@saffronstays.com';
 $where=" AND L.FEATURED ='true'  AND L.APPROVAL_STATUS='A' AND L.STATUS='L' "; 
$listingAll = new Listing();
$listingDAO = new ListingDAO();
$listingAll = $listingDAO->readAll($where);


$BACKDAYS=4;
$NEXTDAYS=48;
if($listingAll!=null){

 $daysHeading = ['Property Name','Location','Max capacity'];
	$startDate = strtotime('-'.$BACKDAYS.' day', time());
	for ($i=0;$i<=($NEXTDAYS+$BACKDAYS);$i++)
	{ 
		$currentDay=date('D', strtotime('+'.$i.' day',$startDate ));
		
	if($currentDay== 'Fri' || $currentDay== 'Sat')
	{
		$daysHeading[]=date('d M (D)', strtotime('+'.$i.' day',$startDate ));	
	}else {
		$daysHeading[]=date('d M (D)', strtotime('+'.$i.' day',$startDate ));

		}
	}
$i=0;
$googleSheetRows=[];
foreach($listingAll as $list){
	$sheetRow=[];
	$i++;
	$listingcalenderFile=$_SERVER['DOCUMENT_ROOT'] . '/advanced_calendar/dopbcp/php-file/data/content'.$list->getId().'.txt';
	
if (file_exists($listingcalenderFile)) 
{
$listingcalenderContent = file_get_contents($listingcalenderFile);
} 

else
{
$listingcalenderFile=$_SERVER['DOCUMENT_ROOT'] . '/advanced_calendar/dopbcp/php-file/data/contentnL3haLvp124b2afB.txt'; //so default calendar with everything empty and available
$listingcalenderContent = file_get_contents($listingcalenderFile);   
}
$listingcalenderContent= json_decode($listingcalenderContent, true);
if($list->getFileReference()!="")
$sheetRow[] = $list->getFileReference();
else
	$sheetRow[] = $list->getTitle();
$sheetRow[] = $list->getLocation();
$sheetRow[] = $list->getNumSimilarRooms();

	for ($j=0;$j<=($NEXTDAYS+$BACKDAYS);$j++)
	{
		 $dateIndex=date('Y-m-d', strtotime('+'.$j.' day',$startDate ));
		
	 if( isset($listingcalenderContent[$dateIndex])){
		 
	if($listingcalenderContent[$dateIndex]['status']== 'unavailable')
	{ 
	$sheetRow[] = -1; //changed from 0 to -1 by deven 
	}elseif($listingcalenderContent[$dateIndex]['status']== 'booked' || $listingcalenderContent[$dateIndex]['available']== 0)
	{ 
	$sheetRow[] = 0;	
	}
	elseif(($listingcalenderContent[$dateIndex]['status']== 'available' || $listingcalenderContent[$dateIndex]['status']== 'special') && $listingcalenderContent[$dateIndex]['available']>=$list->getNumSimilarRooms())
	{ 
	$sheetRow[]=$listingcalenderContent[$dateIndex]['available'];	
	}
	elseif(($listingcalenderContent[$dateIndex]['status']== 'available' || $listingcalenderContent[$dateIndex]['status']== 'special') && $listingcalenderContent[$dateIndex]['available']<$list->getNumSimilarRooms())
	{ 
		$sheetRow[]=$listingcalenderContent[$dateIndex]['available'];
	
	}
	 }else{ 
	 $sheetRow[]=$list->getNumSimilarRooms();
		}

	 }
	$googleSheetRows[]=$sheetRow;
}
	
}else{
	 $sheetRow=[];
}


$values[]=$daysHeading;
foreach($googleSheetRows as $row)
{
	$values[]=$row;
}
/* echo "<pre>";
print_r($values);
exit; */

//update Sheet1
$spreadsheetId = '1gNVfdi-ups0Vcwp__sx-3sYDDKeM4I8reqh9CKUKsCE';
$range = 'Calendar!A1';
updateSheet($spreadsheetId,$range,$values);


/**********************************************************   MONTHLY SHEET UPDATE STARTS HERE ************************************/
$values =[];
$emailToSearch = 'host@saffronstays.com';
				 $where=" AND L.FEATURED ='true'  AND L.APPROVAL_STATUS='A' AND L.STATUS='L' "; 
$listingAll = new Listing();
$listingDAO = new ListingDAO();
$listingAll = $listingDAO->readAll($where);

// calculate number of months since dec 2015
$d1 = date_create('2015-12-1');
$d2= date_create('now');
$interval= date_diff($d2, $d1);
$BACKMONTHS = (($interval->format('%y') * 12) + $interval->format('%m')); 
	// Start month BACKMONTHS back
	 $startMonth=date("Y-m-1",strtotime("-".$BACKMONTHS." Months"));
	 // Add next 6 months from now for output
	$BACKMONTHS +=6;
	
	$headingRowFirst = ["","",""];
	$headingRowSecond = ["Property Name","Location","Target"];
	
 for ($i=0;$i<=$BACKMONTHS;$i++)
	{
		
		$headingRowFirst[]=date('F  Y', strtotime($startMonth.' +'.$i.' month'));
		$headingRowFirst[]="";
		$headingRowSecond[] = "Bookings";
		$headingRowSecond[] = "Rs";
		
 }
 
$monthsArray= array("","January","February","March","April","May","June","July","August","September","October","November","December");

$SQL_SELECT="select LISTING_ID, LS.TITLE AS LISTING_TITLE, LS.LOCATION AS LISTING_LOCATION, LS.LISTING_TARGET AS TARGET, count(0) AS `NUM_BOOKINGS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_GUESTS`)) AS `PERSON_NIGHTS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_ROOMS`)) AS `ROOM_NIGHTS`,month(`LISTING_BOOKING`.`FROM_DATE`) AS `MONTH`,year(`LISTING_BOOKING`.`FROM_DATE`) AS `YEAR`,round(sum((`LISTING_BOOKING`.`SUB_TOTAL` + `LISTING_BOOKING`.`BOOKING_FEE`)),2) AS `Sales`,round((sum(`LISTING_BOOKING`.`SUB_TOTAL`) - sum(`LISTING_BOOKING`.`PG_FEE_WITH_TAX`)),2) AS `NET_GMV`,round((((coalesce(sum(`LISTING_BOOKING`.`BOOKING_FEE`),0) + coalesce(sum(`LISTING_BOOKING`.`HOST_FEE`),0)) + coalesce(sum(`LISTING_BOOKING`.`SS_SHARE_AMOUNT`),0)) - coalesce(sum(`LISTING_BOOKING`.`PG_FEE`),0)),2) AS `NET_SS_EARNINGS` from `LISTING_BOOKING` JOIN LISTING_MASTER AS LS
  ON LISTING_BOOKING.LISTING_ID = LS.ID where ((`LISTING_BOOKING`.`LISTING_ID` IN (select ID from LISTING_MASTER WHERE FEATURED='true')) AND `LISTING_BOOKING`.`BOOKING_STATUS`='9') group by `LISTING_BOOKING`.`LISTING_ID`,year(`LISTING_BOOKING`.`FROM_DATE`),month(`LISTING_BOOKING`.`FROM_DATE`) order by `LISTING_BOOKING`.`LISTING_ID`, year(`LISTING_BOOKING`.`FROM_DATE`) desc,month(`LISTING_BOOKING`.`FROM_DATE`) desc";
		
		$database = new Database();
		$database->query($SQL_SELECT);
		$database->execute();
		$results = array();
		$records = $database->resultset();
		$count=0;
		$currentListid="";
		$newRecords="";
		if(count($records)>0) {
		foreach($records as $record) {
			$newRecords[$record['LISTING_ID']][$monthsArray[$record['MONTH']]."-".$record['YEAR']]=$record;
			$newRecords[$record['LISTING_ID']]['title']=$record['LISTING_TITLE'];
			$newRecords[$record['LISTING_ID']]['location']=$record['LISTING_LOCATION'];
			$newRecords[$record['LISTING_ID']]['target']=$record['TARGET'];
		}
		$serialNo=1;
			$monthSales="";
		$monthEarnings="";
		$sheetRows=[];
	foreach($newRecords as $index=>$record) {
		$totalSales=0;
		$totalEarnings=0;
		$sheetRow = [];
	$sheetRow[] = $record['title'];
	$sheetRow[] = $record['location'];
	$sheetRow[] = (int)$record['target'];
	
	?>
<?php for ($i=0;$i<=$BACKMONTHS;$i++)
		{	
		$currentMonth=date('F-Y', strtotime($startMonth.' +'.$i.' month'));
		if(isset($record[$currentMonth]))
		{	if(isset($monthSales[$currentMonth]))
			$monthSales[$currentMonth] = $monthSales[$currentMonth]+$record[$currentMonth]['Sales'];
			else
			$monthSales[$currentMonth] = $record[$currentMonth]['Sales'];	
			
			if(isset($monthEarnings[$currentMonth]))
			$monthEarnings[$currentMonth] = $monthEarnings[$currentMonth]+$record[$currentMonth]['NET_SS_EARNINGS'];
			else
			$monthEarnings[$currentMonth] = $record[$currentMonth]['NET_SS_EARNINGS'];	
			$totalSales=$totalSales+$record[$currentMonth]['Sales'];
			$totalEarnings=$totalEarnings+$record[$currentMonth]['NET_SS_EARNINGS'];
			
			$sheetRow [] =$record[$currentMonth]['NUM_BOOKINGS'];
			$sheetRow [] =$record[$currentMonth]['Sales'];
		}else { 
		$sheetRow [] ="";
			$sheetRow [] ="";
			} 
	
	}
	$sheetRows[] =$sheetRow;
	} 
		}
		else 
		{
		$sheetRows = [];
 }
 
$values[]=$headingRowFirst;
$values[]=$headingRowSecond;
foreach($sheetRows as $row)
{
	$values[] = $row;
	
}
//update MONTHLY BOOKINGS SHEET
$spreadsheetId = '1gNVfdi-ups0Vcwp__sx-3sYDDKeM4I8reqh9CKUKsCE';
$range = 'Monthly Bookings!A1';
updateSheet($spreadsheetId,$range,$values);

/**********************************************************   WEEKLY BOOKINGS SHEET UPDATE STARTS HERE ************************************/
$values =[];
 $firstOfMonth = date("Y-m-01", strtotime('first Monday of previous month'));
    //Apply above formula.
   $firstWeek  = intval(date("W", strtotime($firstOfMonth)));
    $nextMonthLastDay  = date("Y-m-t", strtotime("+1 month"));
    $lastWeek  = intval(date("W",  strtotime($nextMonthLastDay)));
	$headingRowFirst = ["",""];
	$headingRowSecond = ["Property Name","Location"];
	
for ($i=$firstWeek;$i<=$lastWeek;$i++)
	{
		//echo date('d M Y',  strtotime('monday this week',strtotime('1 Jan + '.$i.' weeks')))." (Week".$i.")" ;
		$headingRowFirst[]=date('d M Y', strtotime('monday last week',strtotime('1 Jan + '.$i.' weeks')))." (Week".$i.")" ;
		$headingRowFirst[]="";
		$headingRowSecond[] = "Bookings";
		$headingRowSecond[] = "Rs";
		
 }

$SQL_SELECT="select LISTING_ID, LS.TITLE AS LISTING_TITLE, LS.LOCATION AS LISTING_LOCATION, count(*) AS `NUM_BOOKINGS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_GUESTS`)) AS `PERSON_NIGHTS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_ROOMS`)) AS `ROOM_NIGHTS`,month(`LISTING_BOOKING`.`FROM_DATE`) AS `MONTH`,year(`LISTING_BOOKING`.`FROM_DATE`) AS `YEAR`, WEEK(`LISTING_BOOKING`.`FROM_DATE`,1) AS WEEK_NAME, round(sum((`LISTING_BOOKING`.`SUB_TOTAL` + `LISTING_BOOKING`.`BOOKING_FEE`)),2) AS `Sales`,round((sum(`LISTING_BOOKING`.`SUB_TOTAL`) - sum(`LISTING_BOOKING`.`PG_FEE_WITH_TAX`)),2) AS `NET_GMV`,round((((coalesce(sum(`LISTING_BOOKING`.`BOOKING_FEE`),0) + coalesce(sum(`LISTING_BOOKING`.`HOST_FEE`),0)) + coalesce(sum(`LISTING_BOOKING`.`SS_SHARE_AMOUNT`),0)) - coalesce(sum(`LISTING_BOOKING`.`PG_FEE`),0)),2) AS `NET_SS_EARNINGS` from `LISTING_BOOKING` JOIN LISTING_MASTER AS LS
  ON LISTING_BOOKING.LISTING_ID = LS.ID where (`LISTING_BOOKING`.`BOOKING_STATUS`='9' AND `LISTING_BOOKING`.`FROM_DATE` >='".$firstOfMonth."'  AND `LISTING_BOOKING`.`FROM_DATE` <='".$nextMonthLastDay."') group by `LISTING_BOOKING`.`LISTING_ID`,WEEK(`LISTING_BOOKING`.`FROM_DATE`,1) order by `LISTING_BOOKING`.`LISTING_ID`, year(`LISTING_BOOKING`.`FROM_DATE`) ,week(`LISTING_BOOKING`.`FROM_DATE`,1)";
		
		$database = new Database();
		$database->query($SQL_SELECT);
		$database->execute();
		$results = array();
		$records = $database->resultset();
		$count=0;
		$currentListid="";
		$newRecords="";
		if(count($records)>0) {
			echo "<pre>";
			//print_r($records);
		foreach($records as $record) {
			$newRecords[$record['LISTING_ID']][$record['WEEK_NAME']]=$record;
			$newRecords[$record['LISTING_ID']]['title']=$record['LISTING_TITLE'];
			$newRecords[$record['LISTING_ID']]['location']=$record['LISTING_LOCATION'];
		}
		$serialNo=1;
			$monthSales="";
		$monthEarnings="";
		$sheetRows=[];
	foreach($newRecords as $index=>$record) {
		$totalSales=0;
		$totalEarnings=0;
		$sheetRow = [];
	$sheetRow[] = $record['title'];
	$sheetRow[] = $record['location'];
	
	?>
<?php for ($i=$firstWeek;$i<=$lastWeek;$i++)
		{	
		if(isset($record[$i]))
		{	if(isset($monthSales[$i]))
			$monthSales[$i] = $monthSales[$i]+$record[$i]['Sales'];
			else
			$monthSales[$i] = $record[$i]['Sales'];	
			
			if(isset($monthEarnings[$i]))
			$monthEarnings[$i] = $monthEarnings[$i]+$record[$i]['NET_SS_EARNINGS'];
			else
			$monthEarnings[$i] = $record[$i]['NET_SS_EARNINGS'];	
			$totalSales=$totalSales+$record[$i]['Sales'];
			$totalEarnings=$totalEarnings+$record[$i]['NET_SS_EARNINGS'];
			
			$sheetRow [] =$record[$i]['NUM_BOOKINGS'];
			$sheetRow [] =$record[$i]['Sales'];
		}else { 
		$sheetRow [] ="";
			$sheetRow [] ="";
			} 
	
	}
	$sheetRows[] =$sheetRow;
	} 
		}
		else 
		{
		$sheetRows = [];
 }
 
$values[]=$headingRowFirst;
$values[]=$headingRowSecond;
foreach($sheetRows as $row)
{
	$values[] = $row;
	
}
//update MONTHLY SHEET
$spreadsheetId = '1gNVfdi-ups0Vcwp__sx-3sYDDKeM4I8reqh9CKUKsCE'; //Original sheet
//$spreadsheetId = '11-ltiOdo1ch4C9GRHwIbYPy58qHfBJpP3v7yqfJeUP4'; // test sheet
$range = 'Weekly Bookings!A1';
updateSheet($spreadsheetId,$range,$values);

/**********************************************************   WEEKLY REFUND UPDATE STARTS HERE ************************************/
$values =[];
  $firstOfMonth = date("Y-m-01");
    //Apply above formula.
   $firstWeek  = intval(date("W", strtotime($firstOfMonth)));
    $nextMonthLastDay  = date("Y-m-t", strtotime("+1 month"));
    $lastWeek  = intval(date("W",  strtotime($nextMonthLastDay)));
	$headingRowFirst = ["",""];
	$headingRowSecond = ["Property Name","Location"];
	
for ($i=$firstWeek;$i<=$lastWeek;$i++)
	{
		//echo date('d M Y',  strtotime('monday this week',strtotime('1 Jan + '.$i.' weeks')))." (Week".$i.")" ;
		$headingRowFirst[]=date('d M Y', strtotime('monday last week',strtotime('1 Jan + '.$i.' weeks')))." (Week".$i.")" ;
		$headingRowFirst[]="";
		$headingRowSecond[] = "Bookings";
		$headingRowSecond[] = "Rs";
		
 }

$SQL_SELECT="select LISTING_ID, LS.TITLE AS LISTING_TITLE, LS.LOCATION AS LISTING_LOCATION, count(*) AS `NUM_BOOKINGS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_GUESTS`)) AS `PERSON_NIGHTS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_ROOMS`)) AS `ROOM_NIGHTS`,month(`LISTING_BOOKING`.`FROM_DATE`) AS `MONTH`,year(`LISTING_BOOKING`.`FROM_DATE`) AS `YEAR`, WEEK(`LISTING_BOOKING`.`FROM_DATE`,1) AS WEEK_NAME, round(sum((`LISTING_BOOKING`.`SUB_TOTAL` + `LISTING_BOOKING`.`BOOKING_FEE`)),2) AS `Sales`,round((sum(`LISTING_BOOKING`.`SUB_TOTAL`) - sum(`LISTING_BOOKING`.`PG_FEE_WITH_TAX`)),2) AS `NET_GMV`,round((((coalesce(sum(`LISTING_BOOKING`.`BOOKING_FEE`),0) + coalesce(sum(`LISTING_BOOKING`.`HOST_FEE`),0)) + coalesce(sum(`LISTING_BOOKING`.`SS_SHARE_AMOUNT`),0)) - coalesce(sum(`LISTING_BOOKING`.`PG_FEE`),0)),2) AS `NET_SS_EARNINGS` from `LISTING_BOOKING` JOIN LISTING_MASTER AS LS
  ON LISTING_BOOKING.LISTING_ID = LS.ID where (`LISTING_BOOKING`.`SUB_TOTAL`<=0 AND `LISTING_BOOKING`.`BOOKING_STATUS`='9' AND `LISTING_BOOKING`.`FROM_DATE` >='".$firstOfMonth."'  AND `LISTING_BOOKING`.`FROM_DATE` <='".$nextMonthLastDay."') group by `LISTING_BOOKING`.`LISTING_ID`,WEEK(`LISTING_BOOKING`.`FROM_DATE`,1) order by `LISTING_BOOKING`.`LISTING_ID`, year(`LISTING_BOOKING`.`FROM_DATE`) ,week(`LISTING_BOOKING`.`FROM_DATE`,1)";
		
		$database = new Database();
		$database->query($SQL_SELECT);
		$database->execute();
		$results = array();
		$records = $database->resultset();
		$count=0;
		$currentListid="";
		$newRecords="";
		if(count($records)>0) {
			echo "<pre>";
			//print_r($records);
		foreach($records as $record) {
			$newRecords[$record['LISTING_ID']][$record['WEEK_NAME']]=$record;
			$newRecords[$record['LISTING_ID']]['title']=$record['LISTING_TITLE'];
			$newRecords[$record['LISTING_ID']]['location']=$record['LISTING_LOCATION'];
		}
		$serialNo=1;
			$monthSales="";
		$monthEarnings="";
		$sheetRows=[];
	foreach($newRecords as $index=>$record) {
		$totalSales=0;
		$totalEarnings=0;
		$sheetRow = [];
	$sheetRow[] = $record['title'];
	$sheetRow[] = $record['location'];
	
	?>
<?php for ($i=$firstWeek;$i<=$lastWeek;$i++)
		{	
		if(isset($record[$i]))
		{	if(isset($monthSales[$i]))
			$monthSales[$i] = $monthSales[$i]+$record[$i]['Sales'];
			else
			$monthSales[$i] = $record[$i]['Sales'];	
			
			if(isset($monthEarnings[$i]))
			$monthEarnings[$i] = $monthEarnings[$i]+$record[$i]['NET_SS_EARNINGS'];
			else
			$monthEarnings[$i] = $record[$i]['NET_SS_EARNINGS'];	
			$totalSales=$totalSales+$record[$i]['Sales'];
			$totalEarnings=$totalEarnings+$record[$i]['NET_SS_EARNINGS'];
			
			$sheetRow [] =$record[$i]['NUM_BOOKINGS'];
			$sheetRow [] =$record[$i]['Sales'];
		}else { 
		$sheetRow [] ="";
			$sheetRow [] ="";
			} 
	
	}
	$sheetRows[] =$sheetRow;
	} 
		}
		else 
		{
		$sheetRows = [];
 }
 
$values[]=$headingRowFirst;
$values[]=$headingRowSecond;
foreach($sheetRows as $row)
{
	$values[] = $row;
	
}
//update MONTHLY SHEET
$spreadsheetId = '1gNVfdi-ups0Vcwp__sx-3sYDDKeM4I8reqh9CKUKsCE'; //Original sheet
//$spreadsheetId = '11-ltiOdo1ch4C9GRHwIbYPy58qHfBJpP3v7yqfJeUP4'; // test sheet
$range = 'Weekly Refunds!A1';
updateSheet($spreadsheetId,$range,$values);



/**********************************************************   MONTHLY PAYOUTS SHEET UPDATE STARTS HERE ************************************/
$values =[];
$emailToSearch = 'host@saffronstays.com';
				 $where=" AND L.FEATURED ='true'  AND L.APPROVAL_STATUS='A' AND L.STATUS='L' "; 
$listingAll = new Listing();
$listingDAO = new ListingDAO();
$listingAll = $listingDAO->readAll($where);

$BACKMONTHS=9;
	// Max Booking upto this Month	
	 $startMonth=date("Y-m-1",strtotime("-3 Months"));
	$headingRowFirst = ["",""];
	$headingRowSecond = ["Property Name","Location"];
 for ($i=0;$i<=$BACKMONTHS;$i++)
	{
		
		$headingRowFirst[]=date('F  Y', strtotime($startMonth.' +'.$i.' month'));
		$headingRowFirst[]="";
		$headingRowSecond[] = "Bookings";
		$headingRowSecond[] = "Rs";
		
 }
 
$monthsArray= array("","January","February","March","April","May","June","July","August","September","October","November","December");

$SQL_SELECT="select LISTING_ID, LS.TITLE AS LISTING_TITLE, LS.LOCATION AS LISTING_LOCATION, count(0) AS `NUM_BOOKINGS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_GUESTS`)) AS `PERSON_NIGHTS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_ROOMS`)) AS `ROOM_NIGHTS`,month(`LISTING_BOOKING`.`FROM_DATE`) AS `MONTH`,year(`LISTING_BOOKING`.`FROM_DATE`) AS `YEAR`,round(sum((`LISTING_BOOKING`.`NET_PAYOUT`)),2) AS `Sales`,round((sum(`LISTING_BOOKING`.`SUB_TOTAL`) - sum(`LISTING_BOOKING`.`PG_FEE_WITH_TAX`)),2) AS `NET_GMV`,round((((coalesce(sum(`LISTING_BOOKING`.`BOOKING_FEE`),0) + coalesce(sum(`LISTING_BOOKING`.`HOST_FEE`),0)) + coalesce(sum(`LISTING_BOOKING`.`SS_SHARE_AMOUNT`),0)) - coalesce(sum(`LISTING_BOOKING`.`PG_FEE`),0)),2) AS `NET_SS_EARNINGS` from `LISTING_BOOKING` JOIN LISTING_MASTER AS LS
  ON LISTING_BOOKING.LISTING_ID = LS.ID where ((`LISTING_BOOKING`.`LISTING_ID` IN (select ID from LISTING_MASTER WHERE FEATURED='true' AND PAYOUT_FREQUENCY='1')) AND `LISTING_BOOKING`.`BOOKING_STATUS`='9') group by `LISTING_BOOKING`.`LISTING_ID`,year(`LISTING_BOOKING`.`FROM_DATE`),month(`LISTING_BOOKING`.`FROM_DATE`) order by `LISTING_BOOKING`.`LISTING_ID`, year(`LISTING_BOOKING`.`FROM_DATE`) desc,month(`LISTING_BOOKING`.`FROM_DATE`) desc";
		
		$database = new Database();
		$database->query($SQL_SELECT);
		$database->execute();
		$results = array();
		$records = $database->resultset();
		$count=0;
		$currentListid="";
		$newRecords="";
		if(count($records)>0) {
		foreach($records as $record) {
			$newRecords[$record['LISTING_ID']][$monthsArray[$record['MONTH']]."-".$record['YEAR']]=$record;
			$newRecords[$record['LISTING_ID']]['title']=$record['LISTING_TITLE'];
			$newRecords[$record['LISTING_ID']]['location']=$record['LISTING_LOCATION'];
		}
		$serialNo=1;
			$monthSales="";
		$monthEarnings="";
		$sheetRows=[];
	foreach($newRecords as $index=>$record) {
		$totalSales=0;
		$totalEarnings=0;
		$sheetRow = [];
	$sheetRow[] = $record['title'];
	$sheetRow[] = $record['location'];
	
	?>
<?php for ($i=0;$i<=$BACKMONTHS;$i++)
		{	
		$currentMonth=date('F-Y', strtotime($startMonth.' +'.$i.' month'));
		if(isset($record[$currentMonth]))
		{	if(isset($monthSales[$currentMonth]))
			$monthSales[$currentMonth] = $monthSales[$currentMonth]+$record[$currentMonth]['Sales'];
			else
			$monthSales[$currentMonth] = $record[$currentMonth]['Sales'];	
			
			if(isset($monthEarnings[$currentMonth]))
			$monthEarnings[$currentMonth] = $monthEarnings[$currentMonth]+$record[$currentMonth]['NET_SS_EARNINGS'];
			else
			$monthEarnings[$currentMonth] = $record[$currentMonth]['NET_SS_EARNINGS'];	
			$totalSales=$totalSales+$record[$currentMonth]['Sales'];
			$totalEarnings=$totalEarnings+$record[$currentMonth]['NET_SS_EARNINGS'];
			
			$sheetRow [] =$record[$currentMonth]['NUM_BOOKINGS'];
			$sheetRow [] =$record[$currentMonth]['Sales'];
		}else { 
		$sheetRow [] ="";
			$sheetRow [] ="";
			} 
	
	}
	$sheetRows[] =$sheetRow;
	} 
		}
		else 
		{
		$sheetRows = [];
 }
 
$values[]=$headingRowFirst;
$values[]=$headingRowSecond;
foreach($sheetRows as $row)
{
	$values[] = $row;
	
}
//update MONTHLY SHEET
$spreadsheetId = '1gNVfdi-ups0Vcwp__sx-3sYDDKeM4I8reqh9CKUKsCE';
$range = 'Monthly Payouts!A1';
updateSheet($spreadsheetId,$range,$values);



/**********************************************************   WEEKLY PAYOUTS SHEET UPDATE STARTS HERE ************************************/
$values =[];
  $firstOfMonth = date("Y-m-01");
    //Apply above formula.
   $firstWeek  = intval(date("W", strtotime($firstOfMonth)));
    $nextMonthLastDay  = date("Y-m-t", strtotime("+1 month"));
    $lastWeek  = intval(date("W",  strtotime($nextMonthLastDay)));
	$headingRowFirst = ["",""];
	$headingRowSecond = ["Property Name","Location"];
	
for ($i=$firstWeek;$i<=$lastWeek;$i++)
	{
		//echo date('d M Y',  strtotime('monday this week',strtotime('1 Jan + '.$i.' weeks')))." (Week".$i.")" ;
		$headingRowFirst[]=date('d M Y', strtotime('monday last week',strtotime('1 Jan + '.$i.' weeks')))." (Week".$i.")" ;
		$headingRowFirst[]="";
		$headingRowSecond[] = "Bookings";
		$headingRowSecond[] = "Rs";
		
 }

$SQL_SELECT="select LISTING_ID, LS.TITLE AS LISTING_TITLE, LS.LOCATION AS LISTING_LOCATION, count(*) AS `NUM_BOOKINGS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_GUESTS`)) AS `PERSON_NIGHTS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_ROOMS`)) AS `ROOM_NIGHTS`,month(`LISTING_BOOKING`.`FROM_DATE`) AS `MONTH`,year(`LISTING_BOOKING`.`FROM_DATE`) AS `YEAR`, WEEK(`LISTING_BOOKING`.`FROM_DATE`,1) AS WEEK_NAME, round(sum((`LISTING_BOOKING`.`NET_PAYOUT`)),2) AS `Sales`,round((sum(`LISTING_BOOKING`.`SUB_TOTAL`) - sum(`LISTING_BOOKING`.`PG_FEE_WITH_TAX`)),2) AS `NET_GMV`,round((((coalesce(sum(`LISTING_BOOKING`.`BOOKING_FEE`),0) + coalesce(sum(`LISTING_BOOKING`.`HOST_FEE`),0)) + coalesce(sum(`LISTING_BOOKING`.`SS_SHARE_AMOUNT`),0)) - coalesce(sum(`LISTING_BOOKING`.`PG_FEE`),0)),2) AS `NET_SS_EARNINGS` from `LISTING_BOOKING` JOIN LISTING_MASTER AS LS
  ON LISTING_BOOKING.LISTING_ID = LS.ID where LS.PAYOUT_FREQUENCY='2' AND (`LISTING_BOOKING`.`BOOKING_STATUS`='9' AND `LISTING_BOOKING`.`FROM_DATE` >='".$firstOfMonth."'  AND `LISTING_BOOKING`.`FROM_DATE` <='".$nextMonthLastDay."') group by `LISTING_BOOKING`.`LISTING_ID`,WEEK(`LISTING_BOOKING`.`FROM_DATE`,1) order by `LISTING_BOOKING`.`LISTING_ID`, year(`LISTING_BOOKING`.`FROM_DATE`) ,week(`LISTING_BOOKING`.`FROM_DATE`,1)";
		
		$database = new Database();
		$database->query($SQL_SELECT);
		$database->execute();
		$results = array();
		$records = $database->resultset();
		$count=0;
		$currentListid="";
		$newRecords="";
		if(count($records)>0) {
			echo "<pre>";
			//print_r($records);
		foreach($records as $record) {
			$newRecords[$record['LISTING_ID']][$record['WEEK_NAME']]=$record;
			$newRecords[$record['LISTING_ID']]['title']=$record['LISTING_TITLE'];
			$newRecords[$record['LISTING_ID']]['location']=$record['LISTING_LOCATION'];
		}
		$serialNo=1;
			$monthSales="";
		$monthEarnings="";
		$sheetRows=[];
	foreach($newRecords as $index=>$record) {
		$totalSales=0;
		$totalEarnings=0;
		$sheetRow = [];
	$sheetRow[] = $record['title'];
	$sheetRow[] = $record['location'];
	
	?>
<?php for ($i=$firstWeek;$i<=$lastWeek;$i++)
		{	
		if(isset($record[$i]))
		{	if(isset($monthSales[$i]))
			$monthSales[$i] = $monthSales[$i]+$record[$i]['Sales'];
			else
			$monthSales[$i] = $record[$i]['Sales'];	
			
			if(isset($monthEarnings[$i]))
			$monthEarnings[$i] = $monthEarnings[$i]+$record[$i]['NET_SS_EARNINGS'];
			else
			$monthEarnings[$i] = $record[$i]['NET_SS_EARNINGS'];	
			$totalSales=$totalSales+$record[$i]['Sales'];
			$totalEarnings=$totalEarnings+$record[$i]['NET_SS_EARNINGS'];
			
			$sheetRow [] =$record[$i]['NUM_BOOKINGS'];
			$sheetRow [] =$record[$i]['Sales'];
		}else { 
		$sheetRow [] ="";
			$sheetRow [] ="";
			} 
	
	}
	$sheetRows[] =$sheetRow;
	} 
		}
		else 
		{
		$sheetRows = [];
 }
 
$values[]=$headingRowFirst;
$values[]=$headingRowSecond;
foreach($sheetRows as $row)
{
	$values[] = $row;
	
}
//update MONTHLY SHEET
$spreadsheetId = '1gNVfdi-ups0Vcwp__sx-3sYDDKeM4I8reqh9CKUKsCE'; //Original sheet
//$spreadsheetId = '11-ltiOdo1ch4C9GRHwIbYPy58qHfBJpP3v7yqfJeUP4'; // test sheet
$range = 'Weekly Payouts!A1';
updateSheet($spreadsheetId,$range,$values);

/**********************************************************   MONTHLY SALES SHEET UPDATE STARTS HERE ************************************/
$values =[];
$emailToSearch = 'host@saffronstays.com';
				 $where=" AND L.FEATURED ='true'  AND L.APPROVAL_STATUS='A' AND L.STATUS='L' "; 
$listingAll = new Listing();
$listingDAO = new ListingDAO();
$listingAll = $listingDAO->readAll($where);

/* $BACKMONTHS=9;
	// Max Booking upto this Month	
	 $startMonth=date("Y-m-1",strtotime("-3 Months")); */
	 
	 // calculate number of months since dec 2015
	$d1 = date_create('2015-12-1');
	$d2= date_create('now');
	$interval= date_diff($d2, $d1);
	$BACKMONTHS = (($interval->format('%y') * 12) + $interval->format('%m')); 
	// Start month BACKMONTHS back
	 $startMonth=date("Y-m-1",strtotime("-".$BACKMONTHS." Months"));
	 // Add next 6 months from now for output
	$BACKMONTHS +=6;
	$headingRowFirst = ["","",""];
	$headingRowSecond = ["Property Name","Location","Target"];
 for ($i=0;$i<=$BACKMONTHS;$i++)
	{
		
		$headingRowFirst[]=date('F  Y', strtotime($startMonth.' +'.$i.' month'));
		$headingRowFirst[]="";
		$headingRowSecond[] = "Bookings";
		$headingRowSecond[] = "Rs";
		
 }
 
$monthsArray= array("","January","February","March","April","May","June","July","August","September","October","November","December");

$SQL_SELECT="select LISTING_ID, LS.TITLE AS LISTING_TITLE, LS.LOCATION AS LISTING_LOCATION, LS.LISTING_TARGET AS TARGET, count(0) AS `NUM_BOOKINGS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_GUESTS`)) AS `PERSON_NIGHTS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_ROOMS`)) AS `ROOM_NIGHTS`,month(`LISTING_BOOKING`.`CREATED_ON`) AS `MONTH`,year(`LISTING_BOOKING`.`CREATED_ON`) AS `YEAR`,round(sum((`LISTING_BOOKING`.`SUB_TOTAL` + `LISTING_BOOKING`.`BOOKING_FEE`)),2) AS `Sales`,round(sum((`LISTING_BOOKING`.`SS_SHARE_AMOUNT` + `LISTING_BOOKING`.`HOST_FEE` + `LISTING_BOOKING`.`BOOKING_FEE`)),2) AS `Earnings`,round((((coalesce(sum(`LISTING_BOOKING`.`BOOKING_FEE`),0) + coalesce(sum(`LISTING_BOOKING`.`HOST_FEE`),0)) + coalesce(sum(`LISTING_BOOKING`.`SS_SHARE_AMOUNT`),0)) - coalesce(sum(`LISTING_BOOKING`.`PG_FEE`),0)),2) AS `NET_SS_EARNINGS` from `LISTING_BOOKING` JOIN LISTING_MASTER AS LS
  ON LISTING_BOOKING.LISTING_ID = LS.ID where ((`LISTING_BOOKING`.`LISTING_ID` IN (select ID from LISTING_MASTER WHERE FEATURED='true')) AND `LISTING_BOOKING`.`BOOKING_STATUS`='9') group by `LISTING_BOOKING`.`LISTING_ID`,year(`LISTING_BOOKING`.`CREATED_ON`),month(`LISTING_BOOKING`.`CREATED_ON`) order by `LISTING_BOOKING`.`LISTING_ID`, year(`LISTING_BOOKING`.`CREATED_ON`) desc,month(`LISTING_BOOKING`.`CREATED_ON`) desc";
		
		$database = new Database();
		$database->query($SQL_SELECT);
		$database->execute();
		$results = array();
		$records = $database->resultset();
		$count=0;
		$currentListid="";
		$newRecords="";
		if(count($records)>0) {
		foreach($records as $record) {
			$newRecords[$record['LISTING_ID']][$monthsArray[$record['MONTH']]."-".$record['YEAR']]=$record;
			$newRecords[$record['LISTING_ID']]['title']=$record['LISTING_TITLE'];
			$newRecords[$record['LISTING_ID']]['location']=$record['LISTING_LOCATION'];
			$newRecords[$record['LISTING_ID']]['target']=$record['TARGET'];
		}
		$serialNo=1;
			$monthSales="";
		$monthEarnings="";
		$sheetRows=[];
		$sheetRowsEarnings=[];
	foreach($newRecords as $index=>$record) {
		$totalSales=0;
		$totalEarnings=0;
		$sheetRow = [];
		$sheetRowEarnings = [];
	$sheetRowEarnings[] = $sheetRow[] = $record['title'];
	$sheetRowEarnings[] = $sheetRow[] = $record['location'];
	$sheetRowEarnings[] = $sheetRow[] = $record['target'];
	
	?>
<?php for ($i=0;$i<=$BACKMONTHS;$i++)
		{	
		$currentMonth=date('F-Y', strtotime($startMonth.' +'.$i.' month'));
		if(isset($record[$currentMonth]))
		{   
			$sheetRow [] = $sheetRowEarnings [] =$record[$currentMonth]['NUM_BOOKINGS'];
			$sheetRow [] =$record[$currentMonth]['Sales'];
			if(isset($record[$currentMonth]['Earnings']))
			$sheetRowEarnings [] =$record[$currentMonth]['Earnings'];
			else
			$sheetRowEarnings [] ="";
			
		}else { 
		$sheetRow [] = $sheetRowEarnings[] = "";
	    $sheetRow [] = $sheetRowEarnings[] = "";
			} 
	
	}
	$sheetRows[] =$sheetRow;
	$sheetRowsEarnings[] =$sheetRowEarnings;
	} 
		}
		else 
		{
		$sheetRows = [];
		$sheetRowsEarnings = [];
 }
 
$values[]=$headingRowFirst;
$values[]=$headingRowSecond;
foreach($sheetRows as $row)
{
	$values[] = $row;
	
}
//update MONTHLY Sales SHEET
$spreadsheetId = '1gNVfdi-ups0Vcwp__sx-3sYDDKeM4I8reqh9CKUKsCE';
$range = 'Monthly Sales!A1';
updateSheet($spreadsheetId,$range,$values);

$values = [];
$values[]=$headingRowFirst;
$values[]=$headingRowSecond;
foreach($sheetRowsEarnings as $row)
{
	$values[] = $row;
	
}
//update MONTHLY Earning SHEET
$spreadsheetId = '1gNVfdi-ups0Vcwp__sx-3sYDDKeM4I8reqh9CKUKsCE';
$range = 'Monthly Earnings!A1';
updateSheet($spreadsheetId,$range,$values);

/**********************************************************   MONTHLY EARNINGS SHEET UPDATE STARTS HERE ************************************/
$values =[];
$emailToSearch = 'host@saffronstays.com';
				 $where=" AND L.FEATURED ='true'  AND L.APPROVAL_STATUS='A' AND L.STATUS='L' "; 
$listingAll = new Listing();
$listingDAO = new ListingDAO();
$listingAll = $listingDAO->readAll($where);

 $BACKMONTHS=9;
	// Max Booking upto this Month	
	 $startMonth=date("Y-m-1",strtotime("-3 Months")); 
	 // calculate number of months since dec 2015
/* 	$d1 = date_create('2015-12-1');
	$d2= date_create('now');
	$interval= date_diff($d2, $d1);
	$BACKMONTHS = (($interval->format('%y') * 12) + $interval->format('%m')); 
	// Start month BACKMONTHS back
	 $startMonth=date("Y-m-1",strtotime("-".$BACKMONTHS." Months"));
	 // Add next 6 months from now for output
	$BACKMONTHS +=6; */
	 
	$headingRowFirst = ["",""];
	$headingRowSecond = ["Property Name","Location"];
 for ($i=0;$i<=$BACKMONTHS;$i++)
	{
		
		$headingRowFirst[]=date('F  Y', strtotime($startMonth.' +'.$i.' month'));
		$headingRowFirst[]="";
		$headingRowSecond[] = "Bookings";
		$headingRowSecond[] = "Rs";
		
 }
 
$monthsArray= array("","January","February","March","April","May","June","July","August","September","October","November","December");

$SQL_SELECT="select LISTING_ID, LS.TITLE AS LISTING_TITLE, LS.LOCATION AS LISTING_LOCATION, count(0) AS `NUM_BOOKINGS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_GUESTS`)) AS `PERSON_NIGHTS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_ROOMS`)) AS `ROOM_NIGHTS`,month(`LISTING_BOOKING`.`FROM_DATE`) AS `MONTH`,year(`LISTING_BOOKING`.`FROM_DATE`) AS `YEAR`,round(sum((`LISTING_BOOKING`.`SS_SHARE_AMOUNT` + `LISTING_BOOKING`.`HOST_FEE` + `LISTING_BOOKING`.`BOOKING_FEE`)),2) AS `Sales`,round((sum(`LISTING_BOOKING`.`SUB_TOTAL`) - sum(`LISTING_BOOKING`.`PG_FEE_WITH_TAX`)),2) AS `NET_GMV` from `LISTING_BOOKING` JOIN LISTING_MASTER AS LS
  ON LISTING_BOOKING.LISTING_ID = LS.ID where ((`LISTING_BOOKING`.`LISTING_ID` IN (select ID from LISTING_MASTER WHERE FEATURED='true')) AND `LISTING_BOOKING`.`BOOKING_STATUS`='9') group by `LISTING_BOOKING`.`LISTING_ID`,year(`LISTING_BOOKING`.`FROM_DATE`),month(`LISTING_BOOKING`.`FROM_DATE`) order by `LISTING_BOOKING`.`LISTING_ID`, year(`LISTING_BOOKING`.`FROM_DATE`) desc,month(`LISTING_BOOKING`.`FROM_DATE`) desc";
		
		$database = new Database();
		$database->query($SQL_SELECT);
		$database->execute();
		$results = array();
		$records = $database->resultset();
		$count=0;
		$currentListid="";
		$newRecords="";
		if(count($records)>0) {
		foreach($records as $record) {
			$newRecords[$record['LISTING_ID']][$monthsArray[$record['MONTH']]."-".$record['YEAR']]=$record;
			$newRecords[$record['LISTING_ID']]['title']=$record['LISTING_TITLE'];
			$newRecords[$record['LISTING_ID']]['location']=$record['LISTING_LOCATION'];
		}
		$serialNo=1;
			$monthSales="";
		$monthEarnings="";
		$sheetRows=[];
	foreach($newRecords as $index=>$record) {
		$totalSales=0;
		$totalEarnings=0;
		$sheetRow = [];
	$sheetRow[] = $record['title'];
	$sheetRow[] = $record['location'];
	
	?>
<?php for ($i=0;$i<=$BACKMONTHS;$i++)
		{	
		$currentMonth=date('F-Y', strtotime($startMonth.' +'.$i.' month'));
		if(isset($record[$currentMonth]))
		{	if(isset($monthSales[$currentMonth]))
			$monthSales[$currentMonth] = $monthSales[$currentMonth]+$record[$currentMonth]['Sales'];
			else
			$monthSales[$currentMonth] = $record[$currentMonth]['Sales'];	
			
			if(isset($monthEarnings[$currentMonth]))
			$monthEarnings[$currentMonth] = $monthEarnings[$currentMonth]+$record[$currentMonth]['NET_SS_EARNINGS'];
			else
			$monthEarnings[$currentMonth] = $record[$currentMonth]['NET_SS_EARNINGS'];	
			$totalSales=$totalSales+$record[$currentMonth]['Sales'];
			$totalEarnings=$totalEarnings+$record[$currentMonth]['NET_SS_EARNINGS'];
			
			$sheetRow [] =$record[$currentMonth]['NUM_BOOKINGS'];
			$sheetRow [] =$record[$currentMonth]['Sales'];
		}else { 
		$sheetRow [] ="";
			$sheetRow [] ="";
			} 
	
	}
	$sheetRows[] =$sheetRow;
	} 
		}
		else 
		{
		$sheetRows = [];
 }
 
$values[]=$headingRowFirst;
$values[]=$headingRowSecond;
foreach($sheetRows as $row)
{
	$values[] = $row;
	
}
//update MONTHLY SHEET
$spreadsheetId = '1gNVfdi-ups0Vcwp__sx-3sYDDKeM4I8reqh9CKUKsCE';
$range = 'SS Earnings Monthly!A1';
updateSheet($spreadsheetId,$range,$values);

/**********************************************************   WEEKLY SALES SHEET UPDATE STARTS HERE ************************************/
$values =[];
  $firstOfMonth = date("Y-m-01", strtotime('first Monday of previous month'));
    //Apply above formula.
   $firstWeek  = intval(date("W", strtotime($firstOfMonth)));
    $nextMonthLastDay  = date("Y-m-t", strtotime("+1 month"));
    $lastWeek  = intval(date("W",  strtotime($nextMonthLastDay)));
	$headingRowFirst = ["",""];
	$headingRowSecond = ["Property Name","Location"];
	
for ($i=$firstWeek;$i<=$lastWeek;$i++)
	{
		//echo date('d M Y',  strtotime('monday this week',strtotime('1 Jan + '.$i.' weeks')))." (Week".$i.")" ;
		$headingRowFirst[]=date('d M Y', strtotime('monday last week',strtotime('1 Jan + '.$i.' weeks')))." (Week".$i.")" ;
		$headingRowFirst[]="";
		$headingRowSecond[] = "Bookings";
		$headingRowSecond[] = "Rs";
		
 }

$SQL_SELECT="select LISTING_ID, LS.TITLE AS LISTING_TITLE, LS.LOCATION AS LISTING_LOCATION, count(*) AS `NUM_BOOKINGS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_GUESTS`)) AS `PERSON_NIGHTS`,sum((`LISTING_BOOKING`.`NO_OF_NIGHTS`*`LISTING_BOOKING`.`NO_OF_ROOMS`)) AS `ROOM_NIGHTS`,month(`LISTING_BOOKING`.`CREATED_ON`) AS `MONTH`,year(`LISTING_BOOKING`.`CREATED_ON`) AS `YEAR`, WEEK(`LISTING_BOOKING`.`CREATED_ON`,1) AS WEEK_NAME, round(sum((`LISTING_BOOKING`.`SUB_TOTAL` + `LISTING_BOOKING`.`BOOKING_FEE`)),2) AS `Sales`,round((sum(`LISTING_BOOKING`.`SUB_TOTAL`) - sum(`LISTING_BOOKING`.`PG_FEE_WITH_TAX`)),2) AS `NET_GMV`,round((((coalesce(sum(`LISTING_BOOKING`.`BOOKING_FEE`),0) + coalesce(sum(`LISTING_BOOKING`.`HOST_FEE`),0)) + coalesce(sum(`LISTING_BOOKING`.`SS_SHARE_AMOUNT`),0)) - coalesce(sum(`LISTING_BOOKING`.`PG_FEE`),0)),2) AS `NET_SS_EARNINGS` from `LISTING_BOOKING` JOIN LISTING_MASTER AS LS
  ON LISTING_BOOKING.LISTING_ID = LS.ID where (`LISTING_BOOKING`.`BOOKING_STATUS`='9' AND `LISTING_BOOKING`.`CREATED_ON` >='".$firstOfMonth."'  AND `LISTING_BOOKING`.`CREATED_ON` <='".$nextMonthLastDay."') group by `LISTING_BOOKING`.`LISTING_ID`,WEEK(`LISTING_BOOKING`.`CREATED_ON`,1) order by `LISTING_BOOKING`.`LISTING_ID`, year(`LISTING_BOOKING`.`CREATED_ON`) ,week(`LISTING_BOOKING`.`CREATED_ON`,1)";
		
		$database = new Database();
		$database->query($SQL_SELECT);
		$database->execute();
		$results = array();
		$records = $database->resultset();
		$count=0;
		$currentListid="";
		$newRecords="";
		if(count($records)>0) {
			echo "<pre>";
			//print_r($records);
		foreach($records as $record) {
			$newRecords[$record['LISTING_ID']][$record['WEEK_NAME']]=$record;
			$newRecords[$record['LISTING_ID']]['title']=$record['LISTING_TITLE'];
			$newRecords[$record['LISTING_ID']]['location']=$record['LISTING_LOCATION'];
		}
		$serialNo=1;
			$monthSales="";
		$monthEarnings="";
		$sheetRows=[];
	foreach($newRecords as $index=>$record) {
		$totalSales=0;
		$totalEarnings=0;
		$sheetRow = [];
	$sheetRow[] = $record['title'];
	$sheetRow[] = $record['location'];
	
	?>
<?php for ($i=$firstWeek;$i<=$lastWeek;$i++)
		{	
		if(isset($record[$i]))
		{	if(isset($monthSales[$i]))
			$monthSales[$i] = $monthSales[$i]+$record[$i]['Sales'];
			else
			$monthSales[$i] = $record[$i]['Sales'];	
			
			if(isset($monthEarnings[$i]))
			$monthEarnings[$i] = $monthEarnings[$i]+$record[$i]['NET_SS_EARNINGS'];
			else
			$monthEarnings[$i] = $record[$i]['NET_SS_EARNINGS'];	
			$totalSales=$totalSales+$record[$i]['Sales'];
			$totalEarnings=$totalEarnings+$record[$i]['NET_SS_EARNINGS'];
			
			$sheetRow [] =$record[$i]['NUM_BOOKINGS'];
			$sheetRow [] =$record[$i]['Sales'];
		}else { 
		$sheetRow [] ="";
			$sheetRow [] ="";
			} 
	
	}
	$sheetRows[] =$sheetRow;
	} 
		}
		else 
		{
		$sheetRows = [];
 }
 
$values[]=$headingRowFirst;
$values[]=$headingRowSecond;
foreach($sheetRows as $row)
{
	$values[] = $row;
	
}
//update MONTHLY SHEET
$spreadsheetId = '1gNVfdi-ups0Vcwp__sx-3sYDDKeM4I8reqh9CKUKsCE'; //Original sheet
//$spreadsheetId = '11-ltiOdo1ch4C9GRHwIbYPy58qHfBJpP3v7yqfJeUP4'; // test sheet
$range = 'Weekly Sales!A1';
updateSheet($spreadsheetId,$range,$values);


?>











