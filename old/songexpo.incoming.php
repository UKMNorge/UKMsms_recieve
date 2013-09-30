<?php
die('VIRKER IKKE');
require_once('UKM/sql.class.php');

function godkjenn($nummer, $melding) {
	$qry = new SQL("SELECT `p`.`p_id`, `p_email`, `p_firstname`, `p_lastname`, `k`.`idfylke` AS `fylke`
					FROM `smartukm_participant` AS `p`
					JOIN `smartukm_rel_b_p` AS `rel` ON (`rel`.`p_id` = `p`.`p_id`)
					JOIN `smartukm_band` AS `b` ON (`b`.`b_id` = `rel`.`b_id`)
					JOIN `smartukm_kommune` AS `k` ON (`k`.`id` = `b`.`b_kommune`)
					JOIN `smartukm_fylkestep` AS `fs` ON (`fs`.`b_id` = `b`.`b_id`)
					WHERE `fs`.`pl_id` = 1517
					AND `p_phone` = '#phone'
					ORDER BY `p`.`p_id` DESC LIMIT 1",
					array('phone'=>$nummer));
	$person = $qry->run('array');

	############################################################
	#### FANT INGEN FESTIVALDELTAKERE, GI TILBAKEMELDING
	if(!$person) {
		svevesms_sendSMS('ukm',
					'Beklager, vi finner ikke ditt mobilnummer registrert som deltaker på UKM-festivalen. 
For å kunne melde deg på Song:expo må du fortelle oss hvem du er.
Svar på denne meldingen med 
UKM RIKTIG ditt navn og fylke

For eksempel:
UKM RIKTIG Jens Jensen fra Hedmark',
					$nummer,
					'1963',
					0,
					'SongExpoFail');
					die();	
	}

	
	$insert = new SQLins('ukm_songexpo');
	$insert->add('mobilnummer',$nummer);
	$insert->add('navn',$person['p_firstname'].' '.$person['p_lastname']);
	$insert->add('epost',$person['p_email']);
	$insert->run();
	
	svevesms_sendSMS('ukm',
					'Takk! Vi har nå registrert din interesse. Du vil bli kontaktet før UKM-festivalen',
					$nummer,
					'UKMNorge',
					0,
					'SongExpoOK');
}
		godkjenn($number, $message);
?>