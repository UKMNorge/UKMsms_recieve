<?php
error_log( 'UKMSJEKK_WARN:'. $NUMBER );
require_once('UKM/sql.class.php');

# HVIS NUMMER ALLEREDE FINNES I DATABASEN
$qry = new SQL("SELECT * FROM `ukm_sjekk` WHERE `phone` = '#mobile'", array('mobile' => $NUMBER));
$res = $qry->run('array');
if ($res) {
	$url = 'http://delta.ukm.no/sjekk/'.$NUMBER.'/'.$res['hash'];
}
else {
	# Generer hash
	$data = $NUMBER + time();
	$hash = hash("sha256", $data);
	$hash = substr($hash, 32, 8);

	## Lagre mobilnummer og hash i databasen
	$qry = new SQLins("ukm_sjekk");
	$qry->add('phone', $NUMBER);
	$qry->add('hash', $hash);
	$res = $qry->run();

	if($res != 1) {
		error_log('UKMSJEKK: Klarte ikke å lagre i databasen. Nr: '.$NUMBER);
		if("ukm.dev" == UKM_HOSTNAME)
			echo "UKMSJEKK: Klarte ikke å lagre i databasen.";
		die();
	}

	$url = 'http://delta.ukm.no/sjekk/'.$NUMBER.'/'.$hash;
}

if( 'ukm.dev' == UKM_HOSTNAME) {
	echo 'Not sending SMS in dev mode - text is: "'."Sjekk informasjonen vi har om deg her: ".$url.'" to number '.$NUMBER.'.';
} else {
	$SMS = new SMS('UKMsjekk', 'false');
	$SMS->text('Sjekk informasjonen vi har om deg her: '.$url)
		->to($NUMBER)
		->from('UKMNorge')
		->ok();
}
die();
/*
$SMS = new SMS('UKMsjekk','false');
$SMS->text('Vi har en feil i funksjonen. Du vil få en SMS fra oss så fort dette er i orden. Det skal være i orden senest torsdag 14.april')
	->to($NUMBER)
	->from('UKMNorge')
	->ok();
	die();
*/