<?php

$SMS = new SMS('UkmTips','false');
$SMS->text($number . ' har sendt dette tipset:' . $melding)
	->to(90069626)
	->from('UKMNorge')
	->ok();
		
$SMS->text('Takk for ditt tips!')
	->to($NUMBER)
	->from('UKMNorge')
	->ok();
