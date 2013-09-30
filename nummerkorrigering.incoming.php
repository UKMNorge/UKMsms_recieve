<?php
		require_once('../send.php');
		svevesms_sendSMS('ukm',
				'Takk, en av våre ansatte sender deg en melding så fort det er i orden og du kan melde på workshops.',
				$number,
				'UKMNorge',
				0,
				'WorkshopRiktigSvar');
		svevesms_sendSMS('ukm',
				'Korrigering av navn: 
'.$number.' sendte følgende melding:
'.substr($message,7),
				46415500,
				'UKMNorge',
				0,
				'WorkshopRiktigBehandle');
		svevesms_sendSMS('ukm',
				'Korrigering av navn: 
'.$number.' sendte følgende melding:
'.substr($message,7),
				98846414,
				'UKMNorge',
				0,
				'WorkshopRiktigBehandle');

?>