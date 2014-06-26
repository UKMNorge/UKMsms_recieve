<?php

$SMS = new SMS('UkmTips','false');
$SMS->text($NUMBER . ' har sendt dette tipset:' . $MESSAGE)
	->to(90069626)
	->from('UKMNorge')
	->ok();
		
$SMS->text('Takk for ditt tips!')
	->to($NUMBER)
	->from('UKMNorge')
	->ok();
