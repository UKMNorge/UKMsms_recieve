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

	
	$res = $sql->run();
	if ($res >= 1) {
		// Done, everything okay.
		// (1 or more affected row)
		notifySupport('Deltaker med mobilnummer '.$nummer.' har sendt svar-SMS som ble mottatt korrekt. Valideringen er godkjent i databasen. Steg 2 av 3.', $nummer);
		return;
	}
	if ($res == 0) {
		// Ingen endringer - finnes det en validert bruker?
		$sql = new SQL("SELECT COUNT(*) FROM SMSValidation WHERE `phone` = '#phone' AND `user_id` = '#u_id'", array('phone' => $nummer, 'u_id' => $msg), 'ukmdelta');
		$res = $sql->run('field', 'COUNT(*)');
		if ($res > 0) {
			// Do nothing, already validated.
			notifySupport('Deltaker med mobilnummer '.$nummer.' har sendt svar-SMS. Godkjenning feilet fordi den allerede er godkjent i databasen. Brukeren får IKKE SMS tilbake om denne feilen. Har brukeren glemt å trykke på knappen "Trykk her når du har sendt meldingen", eller sendt flere meldinger? Steg 2 av 3.', $nummer);
			validate_log("Allerede validert (".$nummer.")!");
			die('Allerede validert.');
		}
		// Nei, i så fall svar at det oppsto en feil
		else {
			// Or reply to the user with an error
			svar("Klarte ikke å godkjenne telefonnummeret - ta kontakt med support@ukm.no.", $nummer);
			validate_log("Kunne ikke endre status til validert (".$nummer.")!");
			notifySupport('Deltaker med mobilnummer '.$nummer.' har sendt svar-SMS, men vi klarte ikke å endre status til godkjent i databasen. Brukeren har fått SMS om at det har skjedd en feil, og beskjed om å kontakte support. Steg 2 av 3.', $nummer);
			die('Klarte ikke endre status.');
		}
	}
	else {
		#svar("Det oppsto en feil - ta kontakt med support@ukm.no", $nummer);
		validate_log("Det oppsto en feil i MySQL for nummer ".$nummer.": ".$sql->error());
		notifySupport('Deltaker med mobilnummer '.$nummer.' har sendt svar-SMS, men vi traff på en ukjent feil mens vi prøvde å godkjenne brukeren. Brukeren har IKKE fått SMS om at det har skjedd en feil, og lokalkontakt bør kanskje kontaktes? Steg 2 av 3.', $nummer);
		die('Something went wrong!');
	}
}

function validate_log($message) {
	error_log('UKMsms_recieve: SMSValidation: '. $message);
}

function notifySupport($message, $phone) {
	// Send e-post til support om at brukeren godkjennes i Deltasystemet.
	require_once('UKMconfig.inc.php');
	require_once('UKM/mail.class.php');
	$mail = new UKMmail();
	$mail->
		to("support@ukm.no")->
		setFrom('delta@'.UKM_HOSTNAME, 'UKMdelta')->
		subject('Manuell validering for '.$phone)->
		message($message);
	if('ukm.dev' == UKM_HOSTNAME) {
		error_log("UKMsms: Not sending email in dev due to timeouts!");
	} else {
		error_log("UKMsms: Sending reverse sms notification email.");
		$mail_result = $mail->ok();	
	}
}

function svar($message, $number) {
	$SMS = new SMS('UKM-brukervalidering', 'false');
	$SMS->text($message)
		->to($number)
		->from('UKMNorge')
		->ok();
}
?>