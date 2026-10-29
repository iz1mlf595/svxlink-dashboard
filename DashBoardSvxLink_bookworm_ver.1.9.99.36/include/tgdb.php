<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusione del database Talkgroup centralizzato
if (file_exists(__DIR__ . "/../config-dashboard/talkgroups.config.php")) {
    include_once __DIR__ . "/../config-dashboard/talkgroups.config.php";
} elseif (file_exists(__DIR__ . "/talkgroups.config.php")) {
    include_once __DIR__ . "/talkgroups.config.php";
} else {
    // Fallback di sicurezza nel caso il file centralizzato non venga trovato
    $tgdb_array = [
        '0' => 'Idle',
        '212' => 'Cluster Alessandria'
    ];
}
?>