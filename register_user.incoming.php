<?php
# register_user.incoming.php
# Filen inneholder funksjoner for reverse-validering av en ny bruker i UKMDelta.
# Oppgaven er å endre validated-status i SMSValidation-tabellen.
# Av sikkerhetshensyn sender denne spørringer direkte til MySQL-tabellen,
# som Symfony i UKMDelta leser.


// Definer konstanter før include av SQL-klassen
define('UKM_DB_NAME', 'ukmdelta_db');

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

	//echo $sql->debug();
	$res = $sql->run();
	// var_dump($res);
	if ($res == 1) {
		// Done, everything okay.
		// (1 affected row)
		return;
	}
	if ($res == 0) {
		// Ingen endringer
		die('Allerede validert.');
	}
	else {
		echo mysql_error().'<br>';
		die('Something went wrong!');
	}
}


?>