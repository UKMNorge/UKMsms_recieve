<?php
# register_user.incoming.php
# Filen inneholder funksjoner for reverse-validering av en ny bruker i UKMDelta.
# Oppgaven er å endre validated-status i SMSValidation-tabellen.
# Av sikkerhetshensyn sender denne spørringer direkte til MySQL-tabellen,
# som Symfony i UKMDelta leser.


// Definer konstanter før include av SQL-klassen
#define('UKM_DB_NAME', 'ukmdelta_db');

use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;

ini_set('display_errors', true);

require_once('UKMconfig.inc.php');
require_once('UKM/Autoloader.php');

// Entry point
// Sjekk at nummeret er et fullstendig telefonnummer
if (!is_numeric($NUMBER) || (strlen($NUMBER) != 8)) {
    // Systemfeil - logg og si fra til admins?
    validate_log("Telefonnummeret må være et telefonnummer: ".$NUMBER);
    die('Telefonnummeret må være et telefonnummer!');
}
// Sjekk at msg er en integer / trim whitespace.
$MESSAGE = trim($MESSAGE);
if (!is_numeric($MESSAGE)) {
    svar('Koden du sendte ble dessverre ikke gjenkjent, dobbeltsjekk at tallet '.$MESSAGE
        .' stemmer med tallet på nettsiden, og at det er et mellomrom mellom V og tallet.', $NUMBER);
    validate_log('Bruker-ID må være et tall!');
    die('Bruker-id må være et tall!');
}

$sql = new Insert(
    'SMSValidation',
    [
        'phone' => $NUMBER,
        'user_id' => $MESSAGE
    ],
    'ukmdelta'
);
$sql->add('validated', 1);
$res = $sql->run();
if ($res >= 1) {
    // Done, everything okay.
    // (1 or more affected row)
    notifySupport('Deltaker med mobilnummer '.$NUMBER.' har sendt svar-SMS som ble mottatt korrekt. Valideringen er godkjent i databasen. Steg 2 av 3.', $NUMBER);
    die();
}

if ($res == 0) {
    // Ingen endringer - finnes det en validert bruker?
    $sql = new Query(
        "SELECT COUNT(*) 
        FROM SMSValidation 
        WHERE `phone` = '#phone' 
        AND `user_id` = '#u_id'", 
        [
            'phone' => $NUMBER,
            'u_id' => $MESSAGE
        ],
        'ukmdelta'
    );
    $res = $sql->getField();
    if ($res > 0) {
        // Do nothing, already validated.
        notifySupport('Deltaker med mobilnummer '.$NUMBER.' har sendt svar-SMS. Godkjenning feilet fordi den allerede er godkjent i databasen. Brukeren får IKKE SMS tilbake om denne feilen. Har brukeren glemt å trykke på knappen "Trykk her når du har sendt meldingen", eller sendt flere meldinger? Steg 2 av 3.', $NUMBER);
        validate_log("Allerede validert (".$NUMBER.")!");
        die('Allerede validert.');
    }
    // Nei, i så fall svar at det oppsto en feil
    else {
        // Or reply to the user with an error
        svar("Klarte ikke å godkjenne telefonnummeret - ta kontakt med support@ukm.no.", $NUMBER);
        validate_log("Kunne ikke endre status til validert (".$NUMBER.")!");
        notifySupport('Deltaker med mobilnummer '.$NUMBER.' har sendt svar-SMS, men vi klarte ikke å endre status til godkjent i databasen. Brukeren har fått SMS om at det har skjedd en feil, og beskjed om å kontakte support. Steg 2 av 3.', $NUMBER);
        die('Klarte ikke endre status.');
    }
}
else {
    #svar("Det oppsto en feil - ta kontakt med support@ukm.no", $NUMBER);
    validate_log("Det oppsto en feil i MySQL for nummer ".$NUMBER.": ".$sql->error());
    notifySupport('Deltaker med mobilnummer '.$NUMBER.' har sendt svar-SMS, men vi traff på en ukjent feil mens vi prøvde å godkjenne brukeren. Brukeren har IKKE fått SMS om at det har skjedd en feil, og lokalkontakt bør kanskje kontaktes? Steg 2 av 3.', $NUMBER);
    die('Something went wrong!');
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
		subject('Re: Manuell validering for '.$phone)->
		message($message);
	if('ukm.dev' == UKM_HOSTNAME) {
        echo '<h2>SEND EMAIL to support@ukm.no FROM delta@'. UKM_HOSTNAME . ' WITH SUBJECT: Re: Manuell validering for '.$phone .'</h2><code>'. $message .'</code>';
		error_log("UKMsms: Not sending email in dev due to timeouts!");
	} else {
		error_log("UKMsms: Sending reverse sms notification email.");
		$mail_result = $mail->ok();	
	}
}

function svar($message, $number) {
    if( UKM_HOSTNAME == 'ukm.dev' ) {
        echo 'SMS: '. $message .' TO '. $number .' FROM UKMNorge';
        return;
    }
	$SMS = new SMS('UKM-brukervalidering', 'false');
	$SMS->text($message)
		->to($number)
		->from('UKMNorge')
		->ok();
}
?>