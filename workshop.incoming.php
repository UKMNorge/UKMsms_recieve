<?php
die('MÅ BRUKE SMS-KLASSE');

function godkjenn($nummer, $melding) {
	$info = explode(' ', $melding);
	$workshopID = (int)$info[1];
	
	############################################################
	#### VI MANGLER WORKSHOP-ID
	if(empty($workshopID)) {
		svevesms_sendSMS('ukm',
					'Har du glemt workshopnummeret i meldingen? :) 
 Send UKM WS <tall> til 1963. 
 Se ukm.no/festivalen/workshops for alle workshop og kodeord.',
					$nummer,
					'UKMNorge',
					0,
					'WorkshopFail1');
					die();
	}
	
	############################################################
	#### FINN DELTAKERINFO
	UKM_loader('sql');
	$plid = new SQL("SELECT `pl_id`
					FROM `smartukm_place`
					WHERE `pl_kommune` = '123456789'
					AND `pl_fylke` = '123456789'
					AND `season` = '#year'",
					array('year' => date('Y')));
	$plid = $plid->run('field','pl_id');
	$qry = new SQL("SELECT `p`.`p_id`, `p_email`, `p_firstname`, `p_lastname`, `k`.`idfylke` AS `fylke`
					FROM `smartukm_participant` AS `p`
					JOIN `smartukm_rel_b_p` AS `rel` ON (`rel`.`p_id` = `p`.`p_id`)
					JOIN `smartukm_band` AS `b` ON (`b`.`b_id` = `rel`.`b_id`)
					JOIN `smartukm_kommune` AS `k` ON (`k`.`id` = `b`.`b_kommune`)
					JOIN `smartukm_fylkestep` AS `fs` ON (`fs`.`b_id` = `b`.`b_id`)
					WHERE `fs`.`pl_id` = '#plid'
					AND `p_phone` = '#phone'
					ORDER BY `p`.`p_id` DESC LIMIT 1",
					array('phone'=>$nummer, 'plid' => $plid));
	$person = $qry->run('array');

	############################################################
	#### FANT INGEN FESTIVALDELTAKERE, GI TILBAKEMELDING
	if(!$person) {
		svevesms_sendSMS('ukm',
					'Beklager, vi finner ikke ditt mobilnummer registrert som deltaker på UKM-festivalen. 
For å kunne melde deg på workshops må du fortelle oss hvem du er.
Svar på denne meldingen med 
UKM RIKTIG ditt navn og fylke

For eksempel:
UKM RIKTIG Jens Jensen fra Hedmark',
					$nummer,
					'1963',
					0,
					'WorkshopFail2');
					die();	
	}

	############################################################
	#### REGISTRER VEDKOMMENDE HVIS IKKE TIDL. REGISTRERT I
	#### WORKSHOPSYSTEMET
	$registrert = new SQL("SELECT `pam_id`
						   FROM `ukmno_ws_pameldte`
						   WHERE `mobil` = '#mobil'",
						   array('mobil'=>$nummer));
	$registrert = $registrert->run('array');
	if(!$registrert) {
		$insert = new SQLins('ukmno_ws_pameldte');
		$insert->add('mobil',$nummer);
		$insert->add('navn',utf8_encode($person['p_firstname'].' '.$person['p_lastname']));
		$insert->add('fylke',$person['p_email']);
		$insert->run();
		$pam_id = $insert->insid();
	} else {
		$pam_id = $registrert['pam_id'];
	}
	
	############################################################
	#### HENT INFO OM WORKSHOP'EN
	$workshop = new SQL("SELECT `navn`, `okt`,`plasser`
						 FROM `ukmno_ws_ws`
						 WHERE `ws_id` = '#ws'",
						 array('ws'=>$workshopID));
	$workshop = $workshop->run('array');

	############################################################
	#### VED UGYLDIG WORKSHOP-ID, GI FEILMELDING
	if(!$workshop) {
		svevesms_sendSMS('ukm',
				'Beklager, vi fant ikke workshop nummer '.$workshopID.'.. 
 Se ukm.no/festivalen/workshops for alle workshop og kodeord.',
				$nummer,
				'UKMNorge',
				0,
				'WorkshopFail3');
		die();
	}

	############################################################
	#### HVIS DET IKKE ER LEDIGE PLASSER, GI FEILMELDING
	$places = new SQL("SELECT `ws_id` FROM `ukmno_ws_rel` WHERE `ws_id` = '#ws';",
					array('ws'=>$workshopID));
	$places = SQL::numRows($places->run());
	if( ($workshop['plasser']-$places) <= 0) {
		svevesms_sendSMS('ukm',
					'Beklager, workshopen er full.
Lurer på noe? Se
ukm.no/festivalen/workshops',
					$nummer,
					'UKMNorge',
					0,
					'WorkshopFail4');
		die();
	}
	
	############################################################
	#### FJERN ALLE PÅMELDINGER FOR VEDKOMMENDE SAMME DAG
	$pameldinger = new SQL("SELECT `ws`.`ws_id`, `ws`.`okt`,`ws`.`navn`
							FROM `ukmno_ws_rel` AS `rel`
							JOIN `ukmno_ws_ws` AS `ws` ON (`ws`.`ws_id` = `rel`.`ws_id`)
							WHERE `rel`.`pam_id` = '#pam'",
							array('pam'=>$pam_id));
	$pameldinger = $pameldinger->run();
	$avmeldt = '';
	while($r = SQL::fetch($pameldinger)) {
		if($r['ws_id'] == $workshopID) {
			svevesms_sendSMS('ukm',
						'Du er allerede påmeldt denne workshopen.
Noe feil? Se
ukm.no/festivalen/workshops',
						$nummer,
						'UKMNorge',
						0,
						'WorkshopOK2');
			die();
		}
		
		if($r['okt'] == $workshop['okt']) {
			$del = new SQLdel('ukmno_ws_rel', array('pam_id'=>$pam_id, 'ws_id'=>$r['ws_id']));
			$del->run();
			$avmeldt = ' i stedet for "'.utf8_encode($r['navn']).'"';
		}
	}

	############################################################
	#### GJØR KLAR MELDING OG MELD PÅ
	switch($workshop['okt']) {
		case 'l1':		$workshop['dag'] = 'økt 1 (mandag)';		break;
		case 'l2':		$workshop['dag'] = 'økt 2 (mandag)';		break;
		case 's1':		$workshop['dag'] = 'økt 3 (tirsdag)';		break;
		case 's2':		$workshop['dag'] = 'økt 4 (tirsdag)';		break;
		case 'm1':		$workshop['dag'] = 'onsdag økt 1';		break;
		case 'm2':		$workshop['dag'] = 'onsdag økt 2';		break;
	}
	
	$meldpa = new SQLins('ukmno_ws_rel');
	$meldpa->add('ws_id', $workshopID);
	$meldpa->add('pam_id', $pam_id);
	$meldpa->run();

	svevesms_sendSMS('ukm',
					'Vi har nå registrert '.utf8_encode($person['p_firstname']).' '.utf8_encode($person['p_lastname'])
					.' til "'.utf8_encode($workshop['navn']).'" i '.$workshop['dag'].''.$avmeldt.'
Noe feil? Se
ukm.no/festivalen/workshops',
					$nummer,
					'UKMNorge',
					0,
					'WorkshopOK1');
}
godkjenn($number, $message);
?>