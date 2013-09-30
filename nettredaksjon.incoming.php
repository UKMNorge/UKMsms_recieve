<?php
		require_once('../send.php');
		svevesms_sendSMS('ukm',
						$number . ' har svart ja til nettredaksjonen',
						97064344,
						'UKMNorge',
						0,
						'NettredOK');
		svevesms_sendSMS('ukm',
						'Takk for at du vil være med! 
Nettredaksjonen har sitt første møte i 4. etg på Nova (rommet Ariel) kl. 10:00 på lørdag.
Det er også viktig at du melder deg inn i facebook-gruppen "UKM-festivalens nettredaksjon 2012" (http://ukm.no/nettred2012), for å få med deg viktig informasjon.
Vi sees i Trondheim!
Hilsen Johannes redaktør :)',
						$number,
						97064344,
						'NettredSVAR');
		die();
	
