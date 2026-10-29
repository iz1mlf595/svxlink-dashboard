<?php
/**
 * File di configurazione ponte per SvxLink Dashboard
 * Include i parametri centralizzati da config-dashboard/config.php
 */

// Inclusione della configurazione centralizzata
if (file_exists(__DIR__ . "/../config-dashboard/config.php")) {
    include_once __DIR__ . "/../config-dashboard/config.php";
} elseif (file_exists(__DIR__ . "/config-dashboard/config.php")) {
    include_once __DIR__ . "/config-dashboard/config.php";
}

// Fallback di sicurezza nel caso le costanti non siano definite
if (!defined("FMNETWORK")) {
    define("FMNETWORK", isset($fmnetwork) ? $fmnetwork : "SvxReflector");
}
if (!defined("CPU_TEMP_OFFSET")) {
    define("CPU_TEMP_OFFSET", defined('CPU_TEMP_OFFSET') ? CPU_TEMP_OFFSET : "0");
}

// Header lines for information
define("HEADER_CAT", "FM-Repeater");
define("HEADER_QTH", isset($qth) ? $qth : "");
define("HEADER_QRG", isset($node_freq) ? $node_freq : "");
define("HEADER_SYSOP", "");
define("FMNETWORK_EXTRA", "");
define("FULLACCESS_OUSIDE", "Full Access Outside");
define("EL_NODE_NR", isset($EL_node) ? $EL_node : "");
define("CALLSIGN", isset($callsign) ? $callsign : "");
define("LOGICS", "");
define("REPORT_CTCSS", "");
define("DTMF_CTRL_PTY", "");
define("API", "");
define("FMNET", "");
define("TG_URI", "NULL");
define("NODE_INFO_FILE", "");
define("RF_MODULE", "");
define("GLOBAL", "");
define("ReflectorLogic", "");
define("LinkToReflector", "");
define("Rx1", "");
define("Tx1", "");
define("Macros", "");
define("LocationInfo", "");
define("RepeaterLogic", "");
define("SimplexLogic", "");

// DTMF Default keys
define("KEY1", array('D1','D1#','green'));
define("KEY2", array('D2','D2#','orange'));
define("KEY3", array('D3','D3#','orange'));
define("KEY4", array('D4','D4#','orange'));
define("KEY5", array('D5', 'D5#','purple'));
define("KEY6", array('D6','D6#','purple'));
define("KEY7", array('D7','D7#','purple'));
define("KEY8", array('D8','D8#','blue'));
define("KEY9", array('D9','D9#','blue'));
define("KEY10", array('D10','D10#','red'));
define("KEY11", array('D11','D11#','green'));
define("KEY12", array('D12','D12#','orange'));
define("KEY13", array('D13','D13#','orange'));
define("KEY14", array('D14','D14#','orange'));
define("KEY15", array('D15','D15#','purple'));
define("KEY16", array('D16','D16#','purple'));
define("KEY17", array('D17','D17#','orange'));
define("KEY18", array('D18','D18#','blue'));
define("KEY19", array('D19','D19#','blue'));
define("KEY20", array('D20','D20#','red'));
        
define("SVXCONFPATH", "/etc/svxlink/");
define("SVXCONFIG", "svxlink.conf");
define("MODULEPATH", "/etc/svxlink/svxlink.d");
define("ECHOLINKCONFIG", "ModuleEchoLink.conf");
define("METARINFO", "ModuleMetarInfo.conf");

define("SVXLOGPATH", "/var/log/");
define("SVXLOGPREFIX", "svxlink.log");

// Inclusione del parser opzionale se presente nella stessa cartella
if (file_exists(__DIR__ . "/parse_svxconf.php")) {
    include_once "parse_svxconf.php";
}
        
error_reporting(0);

// Configurazioni API Reflector e Interfaccia
define("URLSVXRAPI", isset($refApi) ? $refApi : "");
define("MENUBUTTON", "BOTTOM");
define("SHOWPTT", "TRUE");

$svxConfigFile = '/etc/svxlink/svxlink.conf';
if (file_exists($svxConfigFile) && ($svxconfig = @parse_ini_file($svxConfigFile, true, INI_SCANNER_RAW))) {
    $refApi = isset($svxconfig['ReflectorLogic']['API']) ? $svxconfig['ReflectorLogic']['API'] : '';
    $fmnetwork = isset($svxconfig['ReflectorLogic']['HOSTS']) ? $svxconfig['ReflectorLogic']['HOSTS'] : $fmnetwork;
    $qth = isset($svxconfig['LocationInfo']['QTH']) ? $svxconfig['LocationInfo']['QTH'] : '';
    $freq = isset($svxconfig['Rx1']['RX']) ? $svxconfig['Rx1']['RX'] : '';
    $EL_node = isset($svxconfig['LocationInfo']['LOCATION']) ? $svxconfig['LocationInfo']['LOCATION'] : '';
} else { 
    if (!isset($callsign) || $callsign == "") {
        $callsign = "NOCALL";
    }
    if (!isset($fmnetwork)) {
        $fmnetwork = "no registered";
    }
}
?>