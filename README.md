UKMsms_recieve
==============

For å teste selv:
UKMsms_recieve (sic) tar i mot GET-request med parameterne msg og number.
Prefix (de første bokstavene før et mellomrom).

Eksempel på URL for å kjøre en valideringskode til UKMDelta:
http://ukm.sms.no/index.php?msg=V+24&number=98004248

Denne URLen dekodes til $PREFIX = 'v', $MESSAGE = ' 24' og $NUMBER = 98004248.

Anbefaler sterkt å kommentere ut $SMS-ok()-funksjonen fra det man tester. Hvis ikke får man maaaaaaange SMS.