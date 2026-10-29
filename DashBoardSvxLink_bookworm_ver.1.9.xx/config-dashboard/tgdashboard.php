<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* 
  Configurazione dei pulsanti Talkgroup rapidi da mostrare sulla dashboard.
  Formato: ['Nome Pulsante', 'Comando DTMF/Key', ID_Talkgroup]
  - ID_Talkgroup: 0 per Idle/Reset, oppure il numero del TG (es. 212, 2379)
*/
$custom_talkgroups = [
    ['Idle', '910#', 0],
    ['AL', '91212#', 212],
    ['SV', '91213#', 213],
    ['SVX595', '912379#', 2379],
    ['SYSOP', '9190#', 90],
    ['TO', '91110#', 110],
    ['PIEMONTE', '9122212#', 22212],
    ['ITALIA', '91222#', 222]
];
?>