<?php
//synctestet

$PREFIX = explode(' ', $_GET['msg']);
$PREFIX = strtolower($PREFIX[0]);
$MESSAGE = substr( $_GET['msg'], strlen( $PREFIX ) );
$NUMBER = $_GET['number'];

// var_dump($PREFIX);
// var_dump($MESSAGE);
// var_dump($NUMBER);

require_once('UKM/sms.class.php');

switch($PREFIX) {
	## REGISTRER SOM AMBASSADØR
	case 'amb':
	case 'and':
	case 'ambassador':
	case 'ambassadør':
		require_once('ambassador.incoming.php');
		die();
		
	## MANUELL VALIDERING AV INNSLAG
	case 'v':
		require_once('register_user.incoming.php');
		// OLD!require_once('band_validate.incoming.php');
		die();

	## PÅMELDING TIL FESTIVAL-WORKSHOPS
	case 'ws':
	case 'ed':
		require_once('workshop.incoming.php');
		die();
		
	## KORRIGERING AV NAVN PÅ DELTAKERE
	case 'feil':
	case 'riktig':
		require_once('nummerkorrigering.incoming.php');
		die();
		
	case 'vits':
		$SMS = new SMS('UkmVits','false');
		$SMS->text('Ditt bidrag til vitsekonkurransen er mottatt!')
			->to($NUMBER)
			->from('UKMNorge')
			->ok();
    		die();
	case 'tips':
        require_once('ukmtips.incoming.php');
        die();
	        
	case 'sjekk':
		$SMS = new SMS('UKMsjekk','false');
	$SMS->text('Vi har en feil i funksjonen. Du vil få en SMS fra oss så fort dette er i orden. Det skal være i orden senest torsdag 14.april');
		->to($NUMBER)
		->from('UKMNorge')
		->ok();

		require_once('sjekk.incoming.php');
		die();

	## FANT IKKE KODEORDET, SVAR DETTE	
	default:
		$SMS = new SMS('IllegalPrefix','false');
		$SMS->text('Beklager, kodeordet "'. $PREFIX .'" er ikke registrert i vårt system')
			->to($NUMBER)
			->from('UKMNorge')
			->ok();
		die('default die');

/*
	case 'nettred':
	case 'nettredaksjon':
		require_once('nettredaksjon.incoming.php');
		die();
*/

	## PÅMELDING TIL SONG EXPO
/*
	case 'expo':
	case 'exposé':
	case 'expose':
		require_once('songexpo.incoming.php');
		die();
*/


}
die('complete');
