<?php

require_once('UKM/sql.class.php');

function valider($fra, $sms) {
	$info = explode(' ', $sms);
	$bid = $info[1];
	
	## HVIS BID ER TOM ELLER 0, HOPP OVER
	if(!is_numeric($bid)||empty($bid)||$bid==0)
		die(valider_feilet($fra,$bid, 'Beklager, vi mangler noe info i meldingen. Sikker på at du skrev inn alt?'));

	## SJEKK OM AVSENDER ER DEN VI SØKER
	$qry = new SQL("SELECT `v_phone` FROM `smartukm_band_manualvalidate`
					WHERE `b_id` = '#bid'",
					array('bid'=>$bid));
	$avsender = $qry->run('field','v_phone');
	if($avsender != $fra) {
		valider_logg($bid, 36);
		valider_feilet($fra,$bid, 'Beklager, kunne ikke validere påmeldingen. Du må sende SMS\'en fra '.$fra);
		die();
	}
	
	## HENT INN VALIDERINGEN SOM VENTER	
	$venter = new SQL("SELECT `b_id` FROM `smartukm_band_manualvalidate`
					WHERE `b_id` = '#bid'
					AND `v_phone` = '#phone'
					AND `v_complete` = 'false'",
					array('phone'=>$fra,'bid'=>$bid));
	$venter = $venter->run();
	
	## INGEN VENTER
	if(mysql_num_rows($venter)==0||!$venter) {
		valider_logg($bid, 38);
		valider_feilet($fra, $bid, 'Beklager, kan ikke finne noen innslag fra ditt nr som venter på validering');
		die();
	}
	
	## VI FANT INNSLAGET, VALIDER OG GODKJENN
	valider_really($bid, $fra);	
	die();
}

function valider_logg($bid, $kode) {
	$logg = new SQLins('ukmno_smartukm_log');
	$logg->add('log_time',time());
	$logg->add('log_b_id', $bid);
	$logg->add('log_code', $kode);
	$logg->add('log_browser', $_SERVER['HTTP_USER_AGENT']);
	$logg->run();
}

function valider_feilet($til, $bid, $melding) {
	$SMS = new SMS('pameldingUKMvalidate', 0);
	$SMS->text($melding)->to($til)->from('UKMNorge')->ok();

/*
	svevesms_sendSMS('ukm',
					$melding,
					$til,
					'UKMNorge',
					0,
					'BandValidationFail');
*/

	$body = 'Innslaget '.$bid.' ble '.time().' fors&oslash;kt manuelt validert, uten suksess'
			. '<br />'
			.'Om du ikke i l&oslash;pet av kort tid mottar en ny e-post med suksess-melding, betyr det at '
			.'dette innslaget ikke blir p&aring;meldt, og fortjener en oppf&oslash;lging.'
			.'<br /><br />'
			.'Kontaktpersonen fikk f&oslash;lgende melding:'
			.'<br />'
			. $melding
			. '<br /><br />'
			.'Mvh, Valideringssystemet';
	
	$mail = new UKMmail();
	$mail->text($body)->to('support@ukm.no')->subject('Validering av '. $bid .' FEILET')->ok();
//	sendUKMmail('support@ukm.no', 'Validering av '.$bid.' FEILET!', $body);
}

function valider_ok($til,$bid, $mail) {
	$melding = 'Du kan nå fortsette din validering! Vi har sendt brukernavn og passord til '.$mail;

	$SMS = new SMS('pameldingUKMvalidate', 0);
	$SMS->text($melding)->to($til)->from('UKMNorge')->ok();

/*
	svevesms_sendSMS('ukm',
					'Du kan nå fortsette din validering! Vi har sendt brukernavn og passord til '.$mail,
					$til,
					'UKMNorge',
					0,
					'BandValidationOK');
*/
	$body = 'Innslaget '.$bid.' fikk ikke SMS-kode fra p&aring;meldingssystemet, men '
		  . 'har n&aring; blitt manuelt validert.'
		  . '<br /><br />'
		  . '<strong>DET ER INGEN GRUNN TIL PANIKK!</strong>'
 		  .'<br />'
		  . 'Det er ikke tilfeldigvis en liten stund siden du fylte opp kaffekoppen?'
		  . '<br /><br />'
		  . 'Mvh, Valideringssystemet';
	$mail = new UKMmail();
	$mail->text($body)->to('support@ukm.no')->subject('Validering av '. $bid .' FEILET')->ok();
//	sendUKMmail('support@ukm.no', 'Validering av '.$bid.' I ORDEN!', $body);
}

function valider_really($bid, $fra) {
	## OPPDATER INNSLAGET
	$oppdater = new SQLins('smartukm_band', array('b_id'=>$bid));
	$oppdater->add('b_status',1);
	$oppdater->run();
	## LOGG STATUS
	valider_logg($bid, 37);
	
	## HENT E-POST TIL KONTAKTPERSON
	$qry = new SQL("SELECT `p_email` FROM `smartukm_band` AS `b`
					JOIN `smartukm_participant` AS `p` ON (`b`.`b_contact` = `p`.`p_id`)
					WHERE `b`.`b_id` = '#bid'",
					array('bid'=>$bid));
	$mail = $qry->run('field','p_email');

	## SEND NYTT PASSORD
/*
	UKM_loader('curl');
	curlURL('http://pamelding.ukm.no/?steg=dinside&email='.$mail);
*/
	require_once('UKM/curl.class.php');
	$curl = new UKMCURL();
	$curl->timeout(10);
	$curl->request('http://pamelding.ukm.no/?steg=dinside&email='.$mail);
	
	valider_logg($bid, 29);
	
	## VALIDER INNSLAGET OG VARSLE SUPPORT
	valider_ok($fra,$bid, $mail);
	
	## OPPDATER MANUELL-SJEKKDATABASEN SÅ DEN IKKE TREFFER IGJEN
	$opp = new SQLins('smartukm_band_manualvalidate',array('b_id'=>$bid,'v_phone'=>$fra,'v_complete'=>'false'));
	$opp->add('v_complete','true');
	$opp->run();
}

valider($number, $message);

?>