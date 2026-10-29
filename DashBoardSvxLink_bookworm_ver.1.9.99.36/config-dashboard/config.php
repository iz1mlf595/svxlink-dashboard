<?php
/**
 * Configurazione Centralizzata Dashboard SvxLink
 * Posizione: config-dashboard/config.php
 */

// Impostazioni Generali Nodo
$callsign = "XXXXXX";                      // Il tuo nominativo
$node_freq = "144.000 MHz";                // Frequenza operativa RX/TX
$timezone = "Europe/Rome";                 // Fuso orario della dashboard
$fmnetwork = "SvxReflector";               // Nome o IP della rete/reflector

// Password di accesso al pannello "Gestione Sistema"
$sys_password = "admin";                  // Modifica questa password a piacimento

// Offset temperatura CPU (opzionale per calibrazione Raspberry Pi)
if (!defined('CPU_TEMP_OFFSET')) {
    define('CPU_TEMP_OFFSET', 0);
}
?>