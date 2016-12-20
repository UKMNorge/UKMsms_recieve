<?php

######
# Dette skriptet skal sørge for at en ambassadør som svarer på "fortsett"-meldingen fortsatt 
# er registrert som ambassadør.
######

require_once('UKM/curl.class.php');
require_once('UKM/sms.class.php');
require_once('UKMconfig.inc.php');

if( 'ukm.dev' == UKM_HOSTNAME ) {
	$url = 'http://ambassador.ukm.dev/app_dev.php/fortsett/'.$NUMBER;	
}
else {
	$url = 'http://ambassador.ukm.no/fortsett/'.$NUMBER;	
}

error_log("Ambassadør-fortsett: Har mottatt UKM Hurra fra ".$NUMBER);

$curl = new UKMCURL();
$curl->headersOnly();
$res = $curl->process($url);

// Svar med SMS hvis vi er i prod
if ( 'ukm.dev' != UKM_HOSTNAME ) {	
	$svar = new SMS();
	$svar->text("Takk! Du er nå registrert som UKM-ambassadør i ett år til.")
		->to($NUMBER)
		->from("UKMNorge");
	$svar->ok();
}
else {
	echo 'Sender ikke SMS i dev - melding : '."Takk! Du er nå registrert som UKM-ambassadør i ett år til.";
}