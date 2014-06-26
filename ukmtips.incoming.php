<?php
require_once('../send.php');

svevesms_sendSMS(
    'ukm',
    $number . ' har sendt dette tipset:' . $melding,
    90069626,
    'UKMNorge',
    0,
    'ukmtips'
);

svevesms_sendSMS(
    'ukm',
    'Takk for ditt tips!',
    $number,
    'UKMNorge',
    0,
    'ukmtips2'
);