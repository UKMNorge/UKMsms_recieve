<?php
# register_user.incoming.php
# Filen inneholder funksjoner for reverse-validering av en ny bruker i UKMDelta.
# Oppgaven er å endre validated-status i SMSValidation-tabellen.
# Av sikkerhetshensyn sender denne spørringer direkte til MySQL-tabellen,
# som Symfony i UKMDelta leser.


// Definer konstanter før include av SQL-klassen
define('UKM_DB_NAME', 'ukmdelta_db');
define('UKM_DB_USER', 'ukmdelta');
// Passord burde bli definert i UKMconfig.inc.php, men vet ikke om det gjør det når DB_NAME er definert....
// Se på dette, det gjør at den feiler!
require_once('UKM/sql.class.php');





// Entry point
// Her er $MESSAGE og $NUMBER de interessante verdiene.
validerBruker($MESSAGE, $NUMBER);

// echo 'End of include.';

## 
function validerBruker($msg, $nummer) {
	// Sjekk at nummeret er et fullstendig telefonnummer
	if (!is_numeric($nummer) || (strlen($nummer) != 8)) {
		die('Telefonnummeret må være et telefonnummer!');
	}
	// Sjekk at msg er en integer / trim whitespace.
	$msg = trim($msg);
	if (!is_numeric($msg)) {
		die('Bruker-id må være et tall!');
	}

	$sql = new SQLins('SMSValidation', array('phone' => $nummer, 'user_id' => $msg));
	$sql->add('validated', 1);

	// echo $sql->debug();
	$res = $sql->run();
	if ($res == 1) {
		// Done, everything okay.
		// (1 affected row)
		return;
	}
	else {
		die('Something went wrong!');
	}
}


?>