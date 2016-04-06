<?php
error_log( 'UKMSJEKK_WARN:'. $NUMBER );

$SMS = new SMS('UKMsjekk','false');
$SMS->text('Vi har en feil i funksjonen. Du vil få en SMS fra oss så fort dette er i orden. Det skal være i orden senest torsdag 14.april')
	->to($NUMBER)
	->from('UKMNorge')
	->ok();
	die();
