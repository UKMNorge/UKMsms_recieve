<?php
$pass =   chr(rand(97,122)) 
		. chr(rand(97,122))
		. rand(0,9)
		. rand(0,9)
		. strtoupper(chr(rand(65,90)))
		. strtoupper(chr(rand(65,90)))
		;
require_once('UKM/sql.class.php');

$qry = new SQLins('ukm_ambassador_personal_invite', array('invite_phone'=>$NUMBER));
$qry->add('invite_code', $pass);
$qry->add('invite_confirmed', 'true');
$res = $qry->run();

$SMS = new SMS('AmbassadorInvite', false);
$SMS->to($NUMBER)->from('UKMNorge');

if(!$res) {
	$SMS->text('Beklager, vi kunne ikke finne en invitasjon med ditt nummer..
Hvis du vil være ambassadør, følg oss på facebook.com/UKMNorge og kanskje noe dukker opp :)');
} else {
	$SMS->text('En UKM-ambassadør forteller andre om UKM, og bidrar til at flere melder seg på. Du får en gratis T-skjorte og noen tips fra oss, men det viktigste verktøyet er deg selv og dine erfaringer! Det er ingen forpliktelser, men om du gjør en god jobb kan du vinne en gratistur til UKM-festivalen. 
Registrer deg på: http://ukm.no/ambassador så er du i gang!');
}
$SMS->ok();	
die(true);
?>