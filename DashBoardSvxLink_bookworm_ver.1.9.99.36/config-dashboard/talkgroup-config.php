<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/* talkgroup / number alias database */
$tgdb_array = [
    '0'     => 'Idle',
    '212'   => 'Cluster Alessandria',
    '213'   => 'Cluster Savona',
    '2379'  => 'SVX595-DMR',
    '90'    => 'SYSOP',
    '110'   => 'Cluster Torino',
    '112'   => 'C.O.T.A. Brescia',
    '214'   => 'Cluster Rovigo',
    '22212' => 'Cluster Piemonte',
    '222'   => 'Italia FM',
    '9'     => 'Laghi Bresciani',
    '118'   => 'Cluster NordEst',
    '20'    => 'Cluster NordEst',
    '2221278' => 'Cluster Torino DMR',
    '32'    => 'Cluster Rete Trentina',
    '44'    => 'Rete Emilia Ovest MP',
    '70'    => 'Cluster San Marino',
    '23580' => 'FM UK West Midlands',
    '23590' => 'FM UK East Midlands',
    '240'   => 'FM Sweden',
    '255'   => 'FM Ukraine',
    '260'   => 'FM Poland',
    '262'   => 'FM Germany',
    'Any TG' => 'can be used anywhere even if unlisted',
];
?>