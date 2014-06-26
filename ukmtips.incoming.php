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