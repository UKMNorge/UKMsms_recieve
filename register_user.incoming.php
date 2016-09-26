<?php
# register_user.incoming.php
# Filen inneholder funksjoner for reverse-validering av en ny bruker i UKMDelta.
# Oppgaven er å endre validated-status i SMSValidation-tabellen.
# Av sikkerhetshensyn sender denne spørringer direkte til MySQL-tabellen,
# som Symfony i UKMDelta leser.


// Definer konstanter før include av SQL-klassen
#define('UKM_DB_NAME', 'ukmdelta_db');

require_once('UKM/sql.class.php');

// Entry point
// Her er $MESSAGE og $NUMBER de interessante verdiene.
validerBruker($MESSAGE, $NUMBER);

## 
function validerBruker($msg, $nummer) {
	// Sjekk at nummeret er et fullstendig telefonnummer
	if (!is_numeric($nummer) || (strlen($nummer) != 8)) {
		// Systemfeil - logg og si fra til admins?
		validate_log("Telefonnummeret må være et telefonnummer: ".$nummer);
		die('Telefonnummeret må være et telefonnummer!');
	}
	// Sjekk at msg er en integer / trim whitespace.
	$msg = trim($msg);
	if (!is_numeric($msg)) {
		svar('Koden du sendte ble dessverre ikke gjenkjent, dobbeltsjekk at tallet '.$msg
			.' stemmer med tallet på nettsiden, og at det er et mellomrom mellom V og tallet.', $nummer);
		validate_log('Bruker-ID må være et tall!');
		die('Bruker-id må være et tall!');
	}
	
	$sql = new SQLins('SMSValidation', array('phone' => $nummer, 'user_id' => $msg), 'ukmdelta');
	$sql->add('validated', 1);

	//echo $sql->debug();
	$res = $sql->run();
	// var_dump($res);
	if ($res >= 1) {
		// Done, everything okay.
		// (1 or more affected row)
		return;
	}
	if ($res == 0) {
		// Ingen endringer - finnes det en validert bruker?
		$sql = new SQL("SELECT COUNT(*) FROM SMSValidation WHERE `phone` = '#phone' AND `user_id` = '#u_id'", array('phone' => $nummer, 'u_id' => $msg), 'ukmdelta');
		$res = $sql->run('field', 'COUNT(*)');
		if ($res > 0) {
			// Do nothing, already validated.
			validate_log("Allerede validert (".$nummer.")!");
			die('Allerede validert.');
		}
		// Nei, i så fall svar at det oppsto en feil
		else {
			// Or reply to the user with an error
			svar("Klarte ikke å godkjenne telefonnummeret - ta kontakt med support@ukm.no.", $nummer);
			validate_log("Kunne ikke endre status til validert (".$nummer.")!");
			die('Klarte ikke endre status.');
		}
	}
	else {
		#svar("Det oppsto en feil - ta kontakt med support@ukm.no", $nummer);
		validate_log("Det oppsto en feil i MySQL for nummer ".$nummer.": ".$sql->error());
		die('Something went wrong!');
	}
}

function validate_log($message) {
	error_log('UKMsms_recieve: SMSValidation: '. $message);
}

function svar($message, $number) {
	$SMS = new SMS('UKM-brukervalidering', 'false');
	$SMS->text($message)
		->to($number)
		->from('UKMNorge')
		->ok();
}
?>