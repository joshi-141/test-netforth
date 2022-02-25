<?php
include_once ($_SERVER['DOCUMENT_ROOT'] . '/templates/SitePageTemplate.php');
include_once ($_SERVER['DOCUMENT_ROOT'] . '/classes/database.class.php'); 
include_once ($_SERVER['DOCUMENT_ROOT'] . '/classes/User.class.php');
include_once ($_SERVER['DOCUMENT_ROOT'] . '/classes/Listing.class.php');
include_once ($_SERVER['DOCUMENT_ROOT'] . '/classes/ListingImages.class.php');
include_once ($_SERVER['DOCUMENT_ROOT'] . '/classes/ListingBooking.class.php');
$template = new SitePageTemplate();
$template->PageHeader();
if(isset($_REQUEST["status"]) && ($_REQUEST["status"] == md5("confirmPaymentInstant")) || $_REQUEST["status"] == md5("confirmPayment")) {
 $lbObj = new ListingBooking();
    $lbDAOObj = new ListingBookingDAO();
$bookingId = htmlentities($_REQUEST["bid"]);
 $listList = new Listing();
    $listDAOList = new ListingDAO();
    $listingBookingDetail = $lbDAOObj->readById($bookingId);
	$listingDetail = $listDAOList->readById($listingBookingDetail->getListingId());
?>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-49531817-1']);
  _gaq.push(['_trackPageview']);
  _gaq.push(['_addTrans',
    '<? echo $listingBookingDetail->getBookingId() ?>',           // transaction ID - required
    '<? echo $listingDetail->getTitle(); ?>',  // affiliation or store name
    '<? echo $listingBookingDetail->getPayableAtTimeBooking() - $listingBookingDetail->getBookingFeeServiceTaxAmount() - $listingBookingDetail->getAdjAgainstCoupon(); ?>',          // total - required
    '<? echo $listingBookingDetail->getBookingFeeServiceTaxAmount(); ?>',           // tax
    '<? echo $listingBookingDetail->getBookingFee() + $listingBookingDetail->getHostFee(); ?>', // shipping host and guest fees
    '<? echo $listingDetail->getCity(); ?>',       // city
    '<? echo $listingDetail->getState(); ?>',     // state or province
    '<? echo $listingDetail->getCountry(); ?>'             // country
  ]);

   // add item might be called for every item in the shopping cart
   // where your ecommerce engine loops through each item in the cart and
   // prints out _addItem for each
  _gaq.push(['_addItem',
    '<? echo $listingBookingDetail->getBookingId() ?>',           // transaction ID - required
    '<? echo $listingDetail->getId(); ?>',           // SKU/code - required
    '<? echo $listingDetail->getTitle(); ?>',        // product name
    '<? echo $listingDetail->getCategory();?>',   // category or variation
    '<? echo $listingBookingDetail->getPayableAtTimeBooking() - $listingBookingDetail->getBookingFeeServiceTaxAmount() - $listingBookingDetail->getAdjAgainstCoupon(); ?>',          // unit price - required
    '1'               // quantity - required
  ]);
  _gaq.push(['_trackTrans']); //submits transaction to the Analytics servers

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
<iframe src="https://couponraja.go2cloud.org/aff_l?offer_id=137&adv_sub=<?php echo $listingBookingDetail->getBookingId() ?>&amount=<?php echo $listingBookingDetail->getPayableAtTimeBooking() - $listingBookingDetail->getBookingFeeServiceTaxAmount() - $listingBookingDetail->getAdjAgainstCoupon(); ?>" scrolling="no" frameborder="0" width="1" height="1"></iframe>
<? } ?>
<meta name="robots" content="noindex,nofollow" />
<div id="wrap">
<div class="container">
<div class="row">
<div class="col-lg-12">
<div class="pt10"></div>
</div>
</div>
</div>

<div class="container" style="margin-bottom:50px;">
<div class="row">
<div class="col-lg-12"><h2 class="center" style="padding-bottom:5px !important;"></h2></div>
<div class="col-lg-12">
<div class="col-lg-12">
<div class="col-lg-12">
<h1 class="center bookings_titel">Booking Status</h1>
</div>
<div class="col-lg-8">
<div class="row">
<div class="col-lg-2">
<img src="images/status_icon1.png" />
</div>
<div class="col-lg-10">
<?php
if(isset($_REQUEST["status"]) && $_REQUEST["status"] == md5("confirm"))
{    
?>  
    <h3>Your booking has been successfully confirmed.</h3>
    
<?php
}else if(isset($_REQUEST["status"]) && $_REQUEST["status"] == md5("confirmPayment"))
{    
?>
    <h3>Thank You for Your Payment, your booking has been CONFIRMED.  Check your Email and SMS for details.  We have also connected to your Host family.  Feel free to connect with them.</h3>
    <h3>Tejas Parulekar, Co-founder, SaffronStays</h3>
    <h3>"Where Families Come Together"</h3>
 
<?php
}else if(isset($_REQUEST["status"]) && $_REQUEST["status"] == md5("confirmPaymentInstant"))
{    
?>
    <h3>Thank you for your Payment.  Instant Booking by Guest, With 100% advance payment. But, approval needed from Host. Credit Card will be blocked, but will be charged only after Host approves booking.  Check your Email and SMS for details. We have also connected to your Host family. Feel free to connect with them.</h3>
    <h3>Tejas Parulekar, Co-founder, SaffronStays</h3>
    <h3>"Where Families Come Together"</h3>
 
<?php
}else if(isset($_REQUEST["status"]) && $_REQUEST["status"] == md5("confirmAdvance"))
{    
?>
    <h3>Your booking request has been sent to the Host</h3>
<?php
}else if(isset($_REQUEST["status"]) && $_REQUEST["status"] == md5("cancel"))
{
?>
    <h3>You have rejected the booking.</h3>

<?php
}else if(isset($_REQUEST["status"]) && $_REQUEST["status"] == md5("paymentCancel"))
{
?>
    <h3>Unfortunately Your payment was not successful please try again.</h3>

<?php
}else if(isset($_REQUEST["status"]) && $_REQUEST["status"] == md5("askss"))
{
?>
    <h3>Your booking has been successfully submitted for review by admin.</h3>

<?php
}else if(isset($_REQUEST["status"]) && $_REQUEST["status"] == md5("invalid"))
{
?>      
     <h3>Invalid request found.</h3>
<?php        
}
else if(isset($_REQUEST["status"]) && $_REQUEST["status"] == md5("cancelbyGuest"))
{
?>      
     <h3>Your Booking has been cancelled and Refund is processing underway.</h3>
<?php        
}

if(isset($_REQUEST['redirectURI']))
	{ 
	?>
     <h3>Redirecting to Dashboard Page in <span id="lblTime"></span></h3>   
<?
}
else
{
?>	 
<h3>Redirecting to Home Page in <span id="lblTime"></span></h3> 
<?
}
?>
</div>
</div>
</div>
<div class="col-lg-4 right">
<img src="images/404.png"/>
</div>



</div>
</div>
<!--description end-->

</div>
</div>

<script type="text/javascript">
var counter = 0;
function ShowCurrentTime() {
   // var dt = new Date();
    document.getElementById("lblTime").innerHTML = 10 - counter + " Seconds";
    counter++;
    console.log(counter);
	if (counter == 10) {
	<?php 
	if(isset($_REQUEST['redirectURI']))
	{ ?>
        setTimeout("location.href='/<? echo $_REQUEST['redirectURI']; ?>'", 0);
    <? } else { ?>
	 setTimeout("location.href='/'", 0);
	<? } ?>
	}
    window.setTimeout("ShowCurrentTime()", 1000); 
}
window.onload=function(){

    ShowCurrentTime();
}

<?php if ($_SERVER['HTTP_HOST'] == "www.saffronstays.com" OR $_SERVER['HTTP_HOST']=="saffronstays.com")
{ // check for Root or UAT 
  ?>
    
<?php if($_REQUEST['type']=='booking_confirm_payment') { ?>
// payment confirmation conversion code goes here
  
//Google Code for Bookings Conversion Page
/* <![CDATA[ */
var google_conversion_id = 987558256;
var google_conversion_language = "en";
var google_conversion_format = "1";
var google_conversion_color = "ffffff";
var google_conversion_label = "LpT5COLItlYQ8OLz1gM";
var google_conversion_value = 1.00;
var google_conversion_currency = "INR";
var google_remarketing_only = false;
/* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<div style="display:inline;">
<img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/987558256/?value=1.00&amp;currency_code=INR&amp;label=LpT5COLItlYQ8OLz1gM&amp;guid=ON&amp;script=0"/>
</div>
</noscript>

<!-- Facebook Conversion Code for Confirmed Bookings -->
<script>(function() {
var _fbq = window._fbq || (window._fbq = []);
if (!_fbq.loaded) {
var fbds = document.createElement('script');
fbds.async = true;
fbds.src = '//connect.facebook.net/en_US/fbds.js';
var s = document.getElementsByTagName('script')[0];
s.parentNode.insertBefore(fbds, s);
_fbq.loaded = true;
}
})();
window._fbq = window._fbq || [];
window._fbq.push(['track', '6028869834551', {'value':'0.00','currency':'INR'}]);
</script>
<noscript><img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/tr?ev=6028869834551&amp;cd[value]=0.00&amp;cd[currency]=INR&amp;noscript=1" /></noscript>
  
<? } //confirmed bookings
  ?>
  
<? if($_REQUEST['type']=='host_confirm_booking') { ?>
  
<!-- Host booking confirmation conversion code goes here-->

<!-- Facebook Conversion Code for Pending Payments -->
<script>(function() {
var _fbq = window._fbq || (window._fbq = []);
if (!_fbq.loaded) {
var fbds = document.createElement('script');
fbds.async = true;
fbds.src = '//connect.facebook.net/en_US/fbds.js';
var s = document.getElementsByTagName('script')[0];
s.parentNode.insertBefore(fbds, s);
_fbq.loaded = true;
}
})();
window._fbq = window._fbq || [];
window._fbq.push(['track', '6029002127751', {'value':'0.00','currency':'INR'}]);
</script>
<noscript><img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/tr?ev=6029002127751&amp;cd[value]=0.00&amp;cd[currency]=INR&amp;noscript=1" /></noscript>
  
<? } //pending payments
  ?>
  
<? if($_REQUEST['type']=='send_booking') { ?>
  
<!-- Facebook Conversion Code for Booking requests -->
<script>(function() {
var _fbq = window._fbq || (window._fbq = []);
if (!_fbq.loaded) {
var fbds = document.createElement('script');
fbds.async = true;
fbds.src = '//connect.facebook.net/en_US/fbds.js';
var s = document.getElementsByTagName('script')[0];
s.parentNode.insertBefore(fbds, s);
_fbq.loaded = true;
}
})();
window._fbq = window._fbq || [];
window._fbq.push(['track', '6028869941951', {'value':'0.00','currency':'INR'}]);
</script>
<noscript><img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/tr?ev=6028869941951&amp;cd[value]=0.00&amp;cd[currency]=INR&amp;noscript=1" /></noscript>
  
  <? } //booking requests
  ?>
  
<? } // check for root or UAT ends here 
  ?> 

</script>    
<?
$template->PageFooter();
?>
</div>

