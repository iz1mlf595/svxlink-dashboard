<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusione della configurazione centralizzata da config-dashboard
if (file_exists(__DIR__ . "/../config-dashboard/config.php")) {
    include_once __DIR__ . "/../config-dashboard/config.php";
} elseif (file_exists(__DIR__ . "/config.php")) {
    include_once __DIR__ . "/config.php";
}

include_once "tools.php";        
include_once "functions.php";
?>
<div style="width:180px;"><span style="font-weight: bold;font-size:14px;">SVXLink Info</span></div>
<fieldset style="width:175px;background-color:#e8e8e8e8;margin-top:6px;margin-bottom:0px;margin-left:0px;margin-right:3px;font-size:12px;border-top-left-radius: 10px; border-top-right-radius: 10px;border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
<?php

if (isProcessRunning('svxlink')) {

    echo "<table style=\"margin-top:4px;margin-bottom:13px;\">\n";
    echo "<tr><th><span style=\"font-size:12px;\">Active Logics</span></th></tr>\n";
    if ((defined('SVXCONFIG')) && (defined('SVXCONFPATH'))) {
        $svxConfigFile = SVXCONFPATH . "/" . SVXCONFIG;
    } else {
        $svxConfigFile = SVXCONFPATH . "/" . SVXCONFIG;
    }
    if (fopen($svxConfigFile, 'r')) {
        $svxconfig = parse_ini_file($svxConfigFile, true, INI_SCANNER_RAW);
    }
    $callsign = $svxconfig['ReflectorLogic']['CALLSIGN'];     
    $check_logics = explode(",", $svxconfig['GLOBAL']['LOGICS']);
    
    foreach ($check_logics as $key) {
        echo "<tr><td style=\"background:#ffffed;\"><span style=\"color:#b5651d;font-weight: bold;\">" . $key . "</span></td></tr>";
    }
    echo "</table>\n";
    
    echo "<table style=\"margin-top:2px;margin-bottom:13px;\">\n";
    $modules = "";
    if (($check_logics[0] == "RepeaterLogic") && (isset($svxconfig['RepeaterLogic']['MODULES']))) {
        $modules = explode(",", str_replace('Module', '', $svxconfig['RepeaterLogic']['MODULES']));
    }
    if (($check_logics[0] == "SimplexLogic") && (isset($svxconfig['SimplexLogic']['MODULES']))) {
        $modules = explode(",", str_replace('Module', '', $svxconfig['SimplexLogic']['MODULES']));
    }
    
    $modecho = "False";
    $inReflectorDefaultLang = explode(",", $svxconfig['ReflectorLogic']['DEFAULT_LANG']);

    if (!empty($modules)) {
        define("SVXMODULES", $modules);
        $admodules = getActiveModules();
        echo "<tr><th><span style=\"font-size:12px;\">Modules Loaded</span></th></tr>\n";
        foreach ($modules as $key) {
            if (isset($admodules[$key]) && $admodules[$key] == "On") {
                $activemod = "<td style=\"background:MediumSeaGreen;color:#464646;font-weight: bold;\">";
            } else {
                $activemod = "<td style=\"background:#ffffed;color:#b5651d;font-weight: bold;\">";
            }
            echo "<tr>" . $activemod . "" . $key . "</td></tr>";
            if ($key == "EchoLink") { $modecho = "True"; }
        }
    } else {
        echo "<tr><td style=\"background: #ffffed;\"><span style=\"color:#b0b0b0;\"><b>No Modules</b></span></td></tr>";
    }
    echo "</table>\n";

    $tgtmp = trim(getSVXTGTMP());
    echo "<table style=\"margin-top:4px;margin-bottom:13px;width:100%;\">\n";
    $tgdefault = $svxconfig['ReflectorLogic']['DEFAULT_TG'];
    $tgmon = explode(",", $svxconfig['ReflectorLogic']['MONITOR_TGS']);
    echo "<tr><th width=\"50%\">TG Default</th><td style=\"background:#ffffed;color:green;font-weight: bold;\">" . $tgdefault . "</td></tr>\n";
    echo "<tr><th width=\"50%\">TG Monitor</th><td style=\"background:#ffffed;color:#b44010;font-weight: bold;\">";
    echo "<div style=\"white-space:normal;\">";
    foreach ($tgmon as $key) {
        echo $key . " ";
    }
    echo "<span style=\"background: #ffffed;color:#0065ff;font-weight: bold;\">" . $tgtmp . "</span>";
    echo "</div></td></tr>\n";

    $tgselect = trim(getSVXTGSelect());
    if ($tgselect == "0") { $tgselect = ""; }
    echo "<tr><th width=\"50%\">TG Active</th><td style=\"background: #ffffed;color:#0065ff;font-weight: bold;\">" . $tgselect . "</td></tr>\n";
    echo "</table>\n";

    if (($check_logics[0] == "RepeaterLogic") && ($svxconfig['RepeaterLogic']['TX'] !== "NONE")) {
        echo "<table style=\"margin-top:4px;margin-bottom:13px;width:100%;\"><tr><th>Repeater Status</th></tr><tr><td>";
        echo getTXInfo();
        echo "</td></tr></table>\n";
    }
    if (($check_logics[0] == "SimplexLogic") && ($svxconfig['SimplexLogic']['TX'] !== "NONE")) {
        echo "<table style=\"margin-top:4px;margin-bottom:13px;width:100%;\"><tr><th>Radio Status</th></tr><tr><td>";
        echo getTXInfo();
        echo "</td></tr></table>\n";
    }

    echo "<table style=\"margin-top:4px;margin-bottom:13px;width:100%;\"><tr><th>" . (isset($fmnetwork) ? $fmnetwork : "SvxReflector") . "</th></tr>";
    $svxrstatus = getSVXRstatus();
    if ($svxrstatus == "Connected") {
        echo "<tr><td style=\"background:#c3e5cc;\"><div style=\"margin-top:2px;margin-bottom:2px;color:#b44010;font-weight:bold;\">" . $svxrstatus . "</div></td></tr>";
    } elseif ($svxrstatus == "Not connected") {
        echo "<tr><td style=\"background:#ff9;\"><div style=\"margin-top:2px;margin-bottom:2px;color:#454545;font-weight:bold;\">" . $svxrstatus . "</div></td></tr>";
    } else {
        echo "<tr><td style=\"background:#ffffed;\"><div style=\"margin-top:2px;margin-bottom:2px;color:#b0b0b0;font-weight:bold;\">" . $svxrstatus . "</div></td></tr>";
    }
    echo "</table>\n";

    if ($modecho == "True") {
        $users = [];
        if (function_exists('getEchoLog') && function_exists('getConnectedEcholink')) {
            @$echolog = getEchoLog();
            @$users = getConnectedEcholink($echolog);
        }
        if (!is_array($users)) { $users = []; }
        $users = array_values(array_filter(array_map('trim', $users), function($u) {
            return $u !== '';
        }));

        echo "<table style=\"margin-top:4px;margin-bottom:13px;width:100%;\">";
        echo "<tr><th colspan=\"2\">EchoLink Connected Nodes</th></tr>";

        if (count($users) != 0) {
            echo "<tr><td colspan=\"2\" style=\"background:#f6f6bd;\"><div style=\"margin-top:4px;margin-bottom:4px;color:#0065ff;font-weight:bold;\">";
            foreach ($users as $user) {
                echo "<a href=\"http://www.qrz.com/db/" . $user . "\" target=\"_blank\"><b>" . str_replace("0", "&Oslash;", $user) . "</b></a> ";
            }
            echo "</div></td></tr>";
        } else {
            echo "<tr><td colspan=\"2\" style=\"background:#ffffed;\"><div style=\"margin-top:4px;margin-bottom:4px;color:#b0b0b0;font-weight:bold;\">Not connected</div></td></tr>";
        }
        
        $echocurrent = '-';
        $echolink_current_file = '/etc/svxlink/svxlink.d/ink_current_tx.txt';
        if (file_exists($echolink_current_file)) {
            $tmp = trim(@file_get_contents($echolink_current_file));
            if ($tmp !== '') { $echocurrent = htmlspecialchars($tmp, ENT_QUOTES, 'UTF-8'); }
        }
        
        $echotxing = '-';
        $file = '/etc/svxlink/svxlink.d/ink_last_node.txt';
        if (file_exists($file)) {
            $tmp = trim(@file_get_contents($file));
            if ($tmp !== '') { $echotxing = htmlspecialchars($tmp, ENT_QUOTES, 'UTF-8'); }
        }
        
        echo "<tr><th width=\"50%\">Active Tx</th><td style=\"background:#eaffea;color:red;font-weight:bold;\">" . $echocurrent . "</td></tr>";
        echo "<tr><th width=\"50%\">Last Tx</th><td style=\"background:#ffffed;color:green;font-weight:bold;\">" . $echotxing . "</td></tr>";
        echo "</table>\n";
    }

    echo "<table style=\"margin-top:4px;margin-bottom:13px;width:100%;\"><tr><th colspan=\"2\">Systeminfo</th></tr>";
    echo "<tr><td colspan=\"2\" style=\"background:#ffffed;\"><div style=\"margin-top:4px;margin-bottom:4px;color:#000000;font-weight: bold;\">Last Reboot<br>" . exec('uptime -s') . "</div></td></tr>";
    if ($check_logics[0] == "RepeaterLogic") {
        echo "<tr><td colspan=\"2\" style=\"background:#ffffed;\"><div style=\"margin-top:4px;margin-bottom:4px;color:#0a7d29;font-weight: bold;\">Mode: duplex</div></td></tr>";
    }
    if ($check_logics[0] == "SimplexLogic") {
        echo "<tr><td colspan=\"2\" style=\"background:#ffffed;\"><div style=\"margin-top:4px;margin-bottom:4px;color:#0a7d29;font-weight: bold;\">Mode: simplex</div></td></tr>";
    }
    echo "</table>\n";

    echo "<table style=\"margin-top:4px;margin-bottom:13px;width:100%;\"><tr><th colspan=\"2\">Editing</th></tr>";
    if (isset($_SESSION['auth']) && $_SESSION['auth'] === "UNAUTHORISED") {
        echo "<tr><td colspan=\"2\" style=\"background:#ffffed;\"><div style=\"margin-top:4px;margin-bottom:4px;color:#ff0000;font-weight: bold;\">Not Authorised</div></td></tr>";
    }
    if (isset($_SESSION['auth']) && $_SESSION['auth'] === "AUTHORISED") {
        echo "<tr><td colspan=\"2\" style=\"background:#ffffed;\"><div style=\"margin-top:4px;margin-bottom:4px;color:#0a7d29;font-weight: bold;\">Authorised</div></td></tr>";
    }
    
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $net1 = cidr_match($ip, "192.168.0.126/16");
    $net2 = cidr_match($ip, "192.175.43.91/8");
    $net3 = cidr_match($ip, "127.0.0.0/8");
    $net4 = cidr_match($ip, "192.168.1.0/8");

    if ($net1 == TRUE || $net2 == TRUE || $net3 == TRUE || $net4 == TRUE || (defined('FULLACCESS_OUTSIDE') && FULLACCESS_OUTSIDE == 1)) {
        echo "<tr><td colspan=\"2\" style=\"background:#ffffed;\"><div style=\"margin-top:4px;margin-bottom:4px;color:#ff0000;font-weight: bold;\">DB Access Level:<br>Full/Intranet/VPN</div></td></tr>";
    }
    echo "</table>\n";

} else {
    echo "<span style=\"color:red;font-size:13.5px;font-weight: bold;\">SvxLink is not <br>running</span>";
}
?>
</fieldset>