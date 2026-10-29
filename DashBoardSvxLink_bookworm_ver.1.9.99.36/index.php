<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['auth'] = 'AUTHORISED';

// Inclusione dei file di configurazione centralizzati e delle impostazioni
require_once __DIR__ . '/config-dashboard/config.php';
require_once __DIR__ . '/config-dashboard/talkgroup-config.php';
require_once __DIR__ . '/config-dashboard/tgdashboard.php';

// Imposta il fuso orario configurato (fallback su Europe/Rome se non definito)
date_default_timezone_set($timezone ?? 'Europe/Rome');

// --- TIMEOUT DI SESSIONE PER GESTIONE SISTEMA (5 minuti = 300 secondi) ---
$inactive_timeout = 300; 

if (isset($_SESSION['system_auth_time']) && (time() - $_SESSION['system_auth_time'] > $inactive_timeout)) {
    unset($_SESSION['system_auth']);
    unset($_SESSION['system_auth_time']);
}
// -------------------------------------------------------------------------

// Recupera i valori dal file di configurazione centralizzato (con fallback di sicurezza)
$node_freq = isset($node_freq) ? $node_freq : "144.950 MHz";
$sys_password = isset($sys_password) ? $sys_password : "iz1mlf24";

if (isset($_POST['do_system_login'])) {
    if (isset($_POST['sys_pass_input']) && $_POST['sys_pass_input'] === $sys_password) {
        $_SESSION['system_auth'] = true;
        $_SESSION['system_auth_time'] = time(); // Registra il timestamp all'accesso
    } else {
        $login_error = true;
    }
}

if (isset($_POST['do_system_logout'])) {
    unset($_SESSION['system_auth']);
    unset($_SESSION['system_auth_time']);
}

$is_system_auth = isset($_SESSION['system_auth']) && $_SESSION['system_auth'] === true;
$auth_timestamp = isset($_SESSION['system_auth_time']) ? $_SESSION['system_auth_time'] : 0;

if (isset($_POST['send_custom_dtmf'])) {
    $cmd = trim($_POST['dtmf_command']);
    if ($cmd !== '') {
        shell_exec('echo "' . $cmd . '" > /dev/shm/simplex_dtmf_ctrl');
    }
}

if (isset($_POST['dtmf_key'])) {
    $key = $_POST['dtmf_key'];
    shell_exec('echo "' . $key . '" > /dev/shm/simplex_dtmf_ctrl');
}

if ($is_system_auth) {
    if (isset($_POST['do_reboot'])) {
        shell_exec('sudo reboot');
    }
    if (isset($_POST['do_poweroff'])) {
        shell_exec('sudo poweroff');
    }
    if (isset($_POST['do_restart_svxlink'])) {
        shell_exec('sudo systemctl restart svxlink.service');
    }
}

// LOGICA HARDWARE
$rawuptime = shell_exec('cat /proc/uptime');
$uptime_raw = function_exists('format_uptime') ? format_uptime(substr($rawuptime,0,strpos($rawuptime," "))) : 'N/A';
$uptime = html_entity_decode(strip_tags($uptime_raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');

$free_mem = shell_exec("free -m | awk 'NR==2{printf \"%.0f%%\", $3*100/$2 }'");
$disk_used = shell_exec("df -h | awk '\$NF==\"/\"{printf \"%s\",$5}'");

// CPU usage
$cpuLoad = sys_getloadavg();
$core_nums = trim(shell_exec("grep -P '^processor' /proc/cpuinfo | wc -l"));
$load = round($cpuLoad[0]/($core_nums + 1)*100, 2);

$cpuBadgeClass = "hw-badge-ok";
if ($load >= 75) $cpuBadgeClass = "hw-badge-warn";
if ($load >= 85) $cpuBadgeClass = "hw-badge-crit";

// CPU Temp
$cpuTempC = 0;
$hasTemp = false;
if (file_exists('/sys/class/thermal/thermal_zone0/temp')) {
    $cpuTempCRaw = exec('cat /sys/class/thermal/thermal_zone0/temp');
    if ($cpuTempCRaw !== "") {
        $cpuTempC = round(abs($cpuTempCRaw)/ 1000, 1) + (defined('CPU_TEMP_OFFSET') ? CPU_TEMP_OFFSET : 0); 
        $hasTemp = true;
    }
}
$tempBadgeClass = "hw-badge-ok";
if ($cpuTempC >= 55) $tempBadgeClass = "hw-badge-warn";
if ($cpuTempC >= 70) $tempBadgeClass = "hw-badge-crit";

// Voltage check
$throttled = trim(shell_exec('vcgencmd get_throttled 2>/dev/null'));
$voltageStatus = (strpos($throttled, '0x50000') !== false) ? 'Low' : 'OK';
$voltageBadgeClass = ($voltageStatus == 'Low') ? 'hw-badge-warn' : 'hw-badge-ok';

// Operating System Info
$os_info = shell_exec('lsb_release -d | awk -F"\t" \'{print $2}\'');
$hostname = php_uname('n');
$kernel = php_uname('r');
$ip_address = trim(shell_exec('hostname -I | awk \'{print $1}\''));
$current_timezone = $timezone ?? 'Europe/Rome';
$callsign = isset($callsign) ? $callsign : 'NOCALL';
$fmnetwork = isset($fmnetwork) ? $fmnetwork : 'SvxReflector';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="generator" content="SVXLink">
    
    <link rel="shortcut icon" href="images/favicon.ico" sizes="16x16 32x32" type="image/png">    
    <title>Dashboard for Node <?php echo htmlspecialchars($callsign, ENT_QUOTES, 'UTF-8'); ?> - SvxLink Dashboard</title>

    <?php include_once "include/browserdetect.php"; ?>
    <script type="text/javascript" src="scripts/jquery.min.js"></script>
    <script type="text/javascript" src="scripts/functions.js"></script>
    <script type="text/javascript" src="scripts/pcm-player.min.js"></script>
    <script type="text/javascript">
      $.ajaxSetup({ cache: false });
      var realReflectorHost = "<?php echo htmlspecialchars($fmnetwork, ENT_QUOTES, 'UTF-8'); ?>";
      var isSystemAuth = <?php echo $is_system_auth ? 'true' : 'false'; ?>;
      var authTimestamp = <?php echo $auth_timestamp; ?>;
      var inactiveTimeout = 300; // 5 minuti in secondi
    </script>
    <link href="css/featherlight.css" type="text/css" rel="stylesheet">
    <script src="scripts/featherlight.js" type="text/javascript" charset="utf-8"></script>

    <style>
        body {
            background-color: #e4edf6;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #2c3e50;
            margin: 0;
            padding: 15px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* DARK MODE STYLES */
        body.dark-mode {
            background-color: #0f172a !important;
            color: #f1f5f9 !important;
        }
        body.dark-mode .top-banner {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
            border-color: #334155 !important;
        }
        body.dark-mode .top-banner-title {
            color: #f8fafc !important;
            text-shadow: none !important;
        }
        body.dark-mode .top-banner-sub {
            color: #94a3b8 !important;
        }
        body.dark-mode .dash-card, 
        body.dark-mode .dtmf-tg-card {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #f1f5f9 !important;
        }
        body.dark-mode .dash-card-header {
            background: rgba(30, 41, 59, 0.8) !important;
            border-bottom-color: #334155 !important;
            color: #38bdf8 !important;
        }
        body.dark-mode .dash-card-body {
            color: #f1f5f9 !important;
        }
        body.dark-mode #display-active-tg {
            color: #38bdf8 !important;
        }
        body.dark-mode .dtmf-input {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #f1f5f9 !important;
        }
        body.dark-mode .dtmf-mk {
            background: #334155 !important;
            border-color: #475569 !important;
            color: #f1f5f9 !important;
        }
        body.dark-mode .dtmf-mk:hover {
            background: #38bdf8 !important;
            color: #0f172a !important;
        }
        body.dark-mode .logo-box {
            background: #334155 !important;
            border-color: #475569 !important;
        }
        body.dark-mode .footer-text {
            border-top-color: #334155 !important;
            color: #94a3b8 !important;
        }
        body.dark-mode .modal-content {
            background-color: #1e293b !important;
            color: #f1f5f9 !important;
            border: 1px solid #334155 !important;
        }
        body.dark-mode .modal-content h3, 
        body.dark-mode .modal-content p {
            color: #f1f5f9 !important;
        }
        body.dark-mode input[type="password"] {
            background: #0f172a !important;
            color: #f1f5f9 !important;
            border-color: #334155 !important;
        }

        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .top-banner {
            background: linear-gradient(135deg, #d2e3f8 0%, #e7f0fc 100%);
            border: 1px solid #b8d0ee;
            border-radius: 12px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        .top-banner-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo-box {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 6px 12px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.04);
        }
        .logo-box img {
            height: 32px;
            width: auto;
            display: block;
        }
        .top-banner-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a365d;
            margin: 0;
            line-height: 1.2;
            text-shadow: none !important;
        }
        .top-banner-sub {
            font-size: 13px;
            color: #4a5568;
            margin: 4px 0 0 0;
        }
        .top-banner-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .status-pill {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-pill::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            display: inline-block;
        }
        .power-btn-header {
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            border: none;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: background-color 0.3s ease;
        }
        .power-btn-header.unauthorized {
            background-color: #dc3545;
            box-shadow: 0 2px 5px rgba(220,53,69,0.3);
        }
        .power-btn-header.unauthorized:hover { background-color: #c82333; }
        
        .power-btn-header.authorized {
            background-color: #28a745;
            box-shadow: 0 2px 5px rgba(40,167,69,0.3);
        }
        .power-btn-header.authorized:hover { background-color: #218838; }

        .theme-btn-header {
            background-color: #334155;
            color: white;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: none;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            padding: 0;
        }
        .theme-btn-header:hover { background-color: #1e293b; }

        .grid-row-4 {
            display: grid;
            grid-template-columns: 1.2fr 1.2fr 1fr 1.2fr;
            gap: 15px;
        }
        .grid-row-3-uptime {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }
        .grid-main-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 15px;
        }

        .dash-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        .dash-card-header {
            background: rgba(220, 230, 245, 0.6);
            border-bottom: 1px solid #cbd5e1;
            padding: 8px 15px;
            font-size: 11px;
            font-weight: 800;
            color: #1e3a8a;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .dash-card-body {
            padding: 15px;
            font-size: 14px;
            font-weight: 600;
            color: #0f3d4c;
        }

        .dtmf-tg-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        .dtmf-inline-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dtmf-input {
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-family: monospace;
            font-size: 13px;
            width: 110px;
        }
        .dtmf-action-btn {
            background: #1e3c72;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }
        .dtmf-mini-keys {
            display: flex;
            gap: 3px;
        }
        .dtmf-mk {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 5px 9px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .dtmf-mk:hover { background: #1e3c72; color: white; }

        .tg-bar {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            align-items: center;
        }
        .tg-badge {
            background: #334155;
            color: white;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }
        .tg-badge:hover { background: #0f172a; }
        .tg-badge.active-tg { background: #16a34a !important; box-shadow: 0 0 8px rgba(22, 163, 74, 0.5); }
        .tg-badge.idle-tg { background: #dc3545; }

        .badge-tag {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            margin: 2px 2px 2px 0;
        }
        .badge-tag.logic { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .badge-tag.module { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .badge-tag.reflector-on { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        .hw-container {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        body.dark-mode .hw-container {
            background: #1e293b !important;
            border-color: #334155 !important;
        }
        .hw-header {
            background: rgba(220, 230, 245, 0.6);
            border-bottom: 1px solid #cbd5e1;
            padding: 8px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        body.dark-mode .hw-header {
            background: rgba(30, 41, 59, 0.8) !important;
            border-bottom-color: #334155 !important;
        }
        .hw-title {
            font-weight: 800;
            font-size: 11px;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        body.dark-mode .hw-title {
            color: #38bdf8 !important;
        }
        .hw-grid {
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .hw-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        body.dark-mode .hw-card {
            background: #0f172a !important;
            border-color: #334155 !important;
        }
        .hw-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        body.dark-mode .hw-label {
            color: #94a3b8 !important;
        }
        .hw-value {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        body.dark-mode .hw-value {
            color: #f1f5f9 !important;
        }
        .hw-sub {
            font-size: 10px;
            color: #64748b;
        }
        body.dark-mode .hw-sub {
            color: #94a3b8 !important;
        }
        .hw-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .hw-badge-ok { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .hw-badge-warn { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .hw-badge-crit { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        body.dark-mode .hw-badge-ok { background: rgba(22, 163, 74, 0.2); color: #4ade80; border-color: rgba(74, 222, 128, 0.3); }
        body.dark-mode .hw-badge-warn { background: rgba(217, 119, 6, 0.2); color: #fbbf24; border-color: rgba(251, 191, 36, 0.3); }
        body.dark-mode .hw-badge-crit { background: rgba(185, 28, 28, 0.2); color: #f87171; border-color: rgba(248, 113, 113, 0.3); }

        #statusInfo { display: none; }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            width: 320px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .modal-buttons { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }
        .btn-service { background-color: #17a2b8; color: #fff; padding: 10px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-config { background-color: #0d9488 !important; color: #ffffff !important; text-decoration: none !important; padding: 10px; border-radius: 6px; font-weight: 700 !important; display: inline-block; text-align: center; }
        .btn-terminal { background-color: #3b82f6 !important; color: #ffffff !important; text-decoration: none !important; padding: 10px; border-radius: 6px; font-weight: 700 !important; display: inline-block; text-align: center; }
        .btn-reboot { background-color: #ffc107; color: #000; padding: 10px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-shutdown { background-color: #dc3545; color: #fff; padding: 10px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-cancel { background-color: #6c757d; color: #fff; padding: 8px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }

        #toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(30, 60, 114, 0.95);
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            font-size: 13px;
            font-weight: 600;
            z-index: 9999;
            display: none;
            border-left: 4px solid #4cd137;
        }

        .top-menu-container, a[href*="Talkgroups"], a[href*="Buttons"], a[href*="Amixer"], a[href*="EchoLink"], a[href*="MetarInfo"], a[href*="NodeInfo"], a[href*="Dashboard"], a[href*="Nodes"], a[href*="Dtmf"], a[href*="Audio"], a[href*="Log"], a[href*="Authorise"], a[href*="Display"], a[href*="Full Edit"] {
            display: none !important;
        }
        .footer-text {
            font-size: 9pt;
            color: #64748b;
            text-align: center;
            margin-top: 15px;
            border-top: 1px solid #cbd5e1;
            padding-top: 15px;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- TOP BANNER -->
    <div class="top-banner">
        <div class="top-banner-left">
            <div class="logo-box">
                <img src="images/svxlink.ico" alt="SvxLink Logo" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='block';">
                <span style="display:none; font-weight: 800; font-size: 14px; color: #1e3c72; letter-spacing: 1px;">SVXLINK</span>
            </div>
            <div>
                <h1 class="top-banner-title">SvxLink Dashboard for Node <?php echo htmlspecialchars($callsign, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="top-banner-sub"><?php echo htmlspecialchars($fmnetwork, ENT_QUOTES, 'UTF-8'); ?> &bull; QTH: <?php echo htmlspecialchars($qth_info ?? ($qth ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <div class="top-banner-right">
            <div class="status-pill">ONLINE</div>
            
            <button type="button" id="system-auth-btn" class="power-btn-header <?php echo $is_system_auth ? 'authorized' : 'unauthorized'; ?>" onclick="openSystemMenu()">Gestione Sistema</button>

            <button type="button" class="theme-btn-header" id="theme-toggle-btn" onclick="toggleDarkMode()" title="Cambia Modalità">&#9728;</button>
        </div>
    </div>

    <!-- RIGA 1 -->
    <div class="grid-row-4">
        <div class="dash-card">
            <div class="dash-card-header">Frequency RX / TX</div>
            <div class="dash-card-body" style="font-size: 22px; font-weight: 700;">
                <?php echo htmlspecialchars($node_freq, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
        <div class="dash-card">
            <div class="dash-card-header">Radio Status</div>
            <div class="dash-card-body">
                <span id="display-radio-status" style="display:inline-block; background:#e0f2fe; color:#0369a1; padding:4px 12px; border-radius:6px; font-size:14px; border:1px solid #bae6fd;">In ascolto...</span>
            </div>
        </div>
        <div class="dash-card">
            <div class="dash-card-header">Talkgroup Attivo</div>
            <div class="dash-card-body" id="display-active-tg" style="font-size: 18px; font-weight: 700; color: #16a34a;">
                IDLE
            </div>
        </div>
        <div class="dash-card">
            <div class="dash-card-header">Modules Loaded</div>
            <div class="dash-card-body" id="display-modules-loaded">Caricamento...</div>
        </div>
    </div>

    <!-- RIGA 2: DTMF & TG BAR -->
    <div class="dtmf-tg-card">
        <form method="post" id="custom-dtmf-form" class="dtmf-inline-form">
            <span style="font-size: 12px; font-weight: 800; color: #1e3a8a;">DTMF:</span>
            <input type="text" id="dtmf-input-field" name="dtmf_command" placeholder="Es. 91212#" class="dtmf-input" autocomplete="off" required>
            <button type="submit" name="send_custom_dtmf" value="1" class="dtmf-action-btn">Invia</button>
            <button type="button" id="dtmf-delete-btn" style="background:#dc3545; color:white; border:none; padding:6px 10px; border-radius:6px; cursor:pointer;">&larr;</button>
            <div class="dtmf-mini-keys">
                <?php
                $keys = ['1','2','3','A','4','5','6','B','7','8','9','C','*','0','#','D'];
                foreach($keys as $k) {
                    echo '<button type="button" class="dtmf-key-btn dtmf-mk" data-key="'.$k.'">'.$k.'</button>';
                }
                ?>
            </div>
        </form>
        <div class="tg-bar">
            <span style="font-size: 12px; font-weight: 800; color: #1e3a8a;">TG:</span>
            <?php
            if (isset($custom_talkgroups) && is_array($custom_talkgroups)) {
                foreach ($custom_talkgroups as $tg) {
                    $label = $tg[0];
                    $key   = $tg[1];
                    $id_tg = $tg[2];
                    $extraClass = ($id_tg === 0) ? 'idle-btn tg-action-btn' : 'tg-action-btn';
                    echo '<button type="button" class="tg-badge ' . $extraClass . '" data-key="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" data-tg="' . htmlspecialchars($id_tg, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</button>' . "\n";
                }
            }
            ?>
        </div>
    </div>

    <!-- RIGA 3 -->
    <div class="grid-row-3-uptime">
        <div class="dash-card">
            <div class="dash-card-header" style="display: flex; align-items: center; gap: 6px;">
                <span>&#9201;</span> UPTIME
            </div>
            <div class="dash-card-body" style="display: flex; flex-direction: column; gap: 2px; padding: 12px 15px;">
                <span style="font-size: 22px; font-weight: 700; font-family: monospace; color: #00bcd4; letter-spacing: 1px;"><?php echo htmlspecialchars(trim($uptime), ENT_QUOTES, 'UTF-8'); ?></span>
                <span style="font-size: 11px; color: #64748b; font-weight: 600;">Last Reboot</span>
            </div>
        </div>
        <div class="dash-card">
            <div class="dash-card-header" style="display: flex; align-items: center; gap: 6px;">
                <span>&#127777;</span> CPU TEMPERATURE
            </div>
            <div class="dash-card-body" style="display: flex; flex-direction: column; gap: 2px; padding: 12px 15px;">
                <span style="font-size: 22px; font-weight: 700; font-family: monospace; color: #00bcd4; letter-spacing: 1px;"><?php echo $hasTemp ? $cpuTempC . '&deg;C' : 'N/A'; ?></span>
                <span style="font-size: 11px; color: #64748b; font-weight: 600;">CPU for Raspberry Pi</span>
            </div>
        </div>
        <div class="dash-card">
            <div class="dash-card-header" style="display: flex; align-items: center; gap: 6px;">
                <span>&#128339;</span> <span id="header-date">--</span>
            </div>
            <div class="dash-card-body" style="display: flex; flex-direction: column; gap: 2px; padding: 12px 15px;">
                <span id="header-clock" style="font-size: 22px; font-weight: 700; font-family: monospace; color: #00bcd4; letter-spacing: 1px;">00:00:00</span>
                <span style="font-size: 11px; color: #64748b; font-family: monospace; font-weight: 600;"><?php echo htmlspecialchars($current_timezone, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </div>
    </div>

    <!-- MAIN LAYOUT -->
    <div class="grid-main-layout">
        <div class="dash-card">
            <div class="dash-card-header">Activity from SVXReflector</div>
            <div style="padding: 0;">
                <div id="LastHeard" style="width:100%;">
                    <?php include "include/lh.php"; ?>
                </div>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <div class="dash-card">
                <div class="dash-card-header">TG Default & Monitor</div>
                <div class="dash-card-body" style="font-size: 13px; display: flex; flex-direction: column; gap: 6px;">
                    <div style="display: flex; justify-content: space-between;"><span>TG Default</span><strong id="display-config-tg">-</strong></div>
                    <div style="display: flex; justify-content: space-between;"><span>TG Monitor</span><strong id="display-monitor-tg">-</strong></div>
                </div>
            </div>
            <div class="dash-card">
                <div class="dash-card-header">Reflector Status</div>
                <div class="dash-card-body" id="display-reflector-status" style="font-size: 13px;">Caricamento...</div>
            </div>

            <!-- SERVER HARDWARE INFO -->
            <div class="hw-container">
                <div class="hw-header">
                    <span class="hw-title">Server Hardware Info</span>
                    <span style="font-size: 10px; font-family: monospace;" class="hw-sub">Uptime: <?php echo htmlspecialchars(trim($uptime), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="hw-grid">
                    <div class="hw-card">
                        <span class="hw-label">Hostname / IP</span>
                        <span class="hw-value" style="font-size:12px;"><?php echo htmlspecialchars($hostname, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="hw-sub">IP: <?php echo htmlspecialchars($ip_address, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="hw-card">
                        <span class="hw-label">Disk / Memory</span>
                        <div style="display: flex; justify-content: space-between; font-size: 12px;">
                            <span>Disk: <strong class="hw-value"><?php echo htmlspecialchars(trim($disk_used), ENT_QUOTES, 'UTF-8'); ?></strong></span>
                            <span>RAM: <strong class="hw-value"><?php echo htmlspecialchars(trim($free_mem), ENT_QUOTES, 'UTF-8'); ?></strong></span>
                        </div>
                    </div>
                    <div class="hw-card">
                        <span class="hw-label">CPU Usage</span>
                        <div><span class="hw-badge <?php echo $cpuBadgeClass; ?>"><?php echo $load; ?> %</span></div>
                    </div>
                    <div class="hw-card">
                        <span class="hw-label">Voltage</span>
                        <div><span class="hw-badge <?php echo $voltageBadgeClass; ?>"><?php echo $voltageStatus; ?></span></div>
                    </div>
                    <?php if ($hasTemp): ?>
                    <div class="hw-card">
                        <span class="hw-label">CPU Temp</span>
                        <div><span class="hw-badge <?php echo $tempBadgeClass; ?>"><?php echo $cpuTempC; ?>&deg;C</span></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer-text">
        SvxLink Dashboard Ver 2.3 &copy; G4NAB, SP2ONG, SP0DZ <?php echo date("Y"); ?>
    </div>
</div>

<div id="statusInfo" style="display:none;"><?php include "include/status.php"; ?></div>
<div id="toast-notification">Comando inviato con successo!</div>

<!-- MODALI -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <h3>Password Richiesta</h3>
        <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Inserisci la password per accedere a Gestione Sistema:</p>
        <form method="post">
            <input type="password" name="sys_pass_input" placeholder="Password" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 6px; box-sizing: border-box; margin-bottom: 15px; font-size: 14px;" required autocomplete="current-password">
            <?php if (isset($login_error) && $login_error): ?>
                <p style="color: #dc3545; font-size: 12px; margin: 0 0 10px 0; font-weight: 600;">Password errata!</p>
            <?php endif; ?>
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="do_system_login" style="flex: 1; background: #1e3c72; color: white; border: none; padding: 9px; border-radius: 6px; font-weight: 600; cursor: pointer;">Accedi</button>
                <button type="button" class="btn-cancel" onclick="closeLoginModal()" style="flex: 1;">Annulla</button>
            </div>
        </form>
    </div>
</div>

<div id="powerModal" class="modal">
    <div class="modal-content">
        <h3>Gestione Sistema</h3>
        <div class="modal-buttons">
            <a href="edit_config.php" class="btn-config" target="_blank">Configurazione SVXLINK</a>
            <a href="terminal.php" class="btn-terminal" target="_blank">Console Terminale Comandi</a>
            <form method="post" style="display:contents;" onsubmit="return confirm('Confermi il riavvio del servizio SvxLink?');">
                <button type="submit" name="do_restart_svxlink" class="btn-service">Riavvia Servizio SvxLink</button>
            </form>
            <form method="post" style="display:contents;" onsubmit="return confirm('Confermi il reboot del sistema?');">
                <button type="submit" name="do_reboot" class="btn-reboot">Riavvia Sistema (Reboot)</button>
            </form>
            <form method="post" style="display:contents;" onsubmit="return confirm('Confermi lo spegnimento?');">
                <button type="submit" name="do_poweroff" class="btn-shutdown">Spegni (Power Off)</button>
            </form>
        </div>
        <div style="margin-top: 12px;">
            <form method="post" style="display:inline;">
                <button type="submit" name="do_system_logout" style="background: #6c757d; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 11px; cursor: pointer; width: 100%; margin-bottom:8px;">Esci / Blocca Sessione</button>
            </form>
            <button type="button" class="btn-cancel" onclick="closePowerModal()" style="width:100%;">Chiudi</button>
        </div>
    </div>
</div>

<script type="text/javascript">
function toggleDarkMode() {
    const body = document.body;
    body.classList.toggle('dark-mode');
    const isDark = body.classList.contains('dark-mode');
    localStorage.setItem('svx_dark_mode', isDark ? 'enabled' : 'disabled');
    updateThemeButtonIcon(isDark);
}

function updateThemeButtonIcon(isDark) {
    const btn = document.getElementById('theme-toggle-btn');
    if (btn) {
        btn.innerHTML = isDark ? '&#9728;' : '&#9789;';
    }
}

if (localStorage.getItem('svx_dark_mode') === 'enabled') {
    document.body.classList.add('dark-mode');
}

document.addEventListener("DOMContentLoaded", function() {
    updateThemeButtonIcon(document.body.classList.contains('dark-mode'));

    function cleanStrayLessThan(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            if (node.textContent.trim() === "<") {
                node.remove();
            }
        } else {
            node.childNodes.forEach(child => cleanStrayLessThan(child));
        }
    }
    cleanStrayLessThan(document.body);
});

function openSystemMenu() {
    if (isSystemAuth && authTimestamp > 0) {
        var nowSec = Math.floor(Date.now() / 1000);
        if ((nowSec - authTimestamp) > inactiveTimeout) {
            isSystemAuth = false;
            updateAuthButtonUI(false);
        }
    }

    if (isSystemAuth) {
        document.getElementById('powerModal').style.display = 'flex';
    } else {
        document.getElementById('loginModal').style.display = 'flex';
    }
}

function updateAuthButtonUI(authorized) {
    var $btn = $('#system-auth-btn');
    if (authorized) {
        $btn.removeClass('unauthorized').addClass('authorized');
    } else {
        $btn.removeClass('authorized').addClass('unauthorized');
    }
}

function closeLoginModal() { document.getElementById('loginModal').style.display = 'none'; }
function closePowerModal() { document.getElementById('powerModal').style.display = 'none'; }

<?php if (isset($login_error) && $login_error): ?>
window.addEventListener('DOMContentLoaded', function() { document.getElementById('loginModal').style.display = 'flex'; });
<?php endif; ?>

function updateActiveTgState() {
    if (isSystemAuth && authTimestamp > 0) {
        var nowSec = Math.floor(Date.now() / 1000);
        if ((nowSec - authTimestamp) > inactiveTimeout) {
            isSystemAuth = false;
            updateAuthButtonUI(false);
        }
    }

    var $status = $('#statusInfo');
    var activeTgValue = '';
    var tgDefaultText = '212';
    var tgMonitorText = '212, 2379';
    var modulesLoadedText = 'Help, Parrot';

    if ($status.length && $status.text().trim() !== '') {
        $status.find('th, td').each(function() {
            var txt = $(this).text().trim().toLowerCase();
            var $nextTd = $(this).next('td');
            var nextTxt = $nextTd.text().trim();
            
            if (txt.includes('module') || txt.includes('modul')) {
                if (nextTxt) modulesLoadedText = nextTxt;
            }
            if (txt.includes('default') || txt.includes('def')) {
                if (nextTxt) tgDefaultText = nextTxt;
            }
            if (txt.includes('monitor') || txt.includes('mon')) {
                if (nextTxt) tgMonitorText = nextTxt;
            }
            if (txt.includes('active') || txt.includes('tg active') || txt.includes('talkgroup')) {
                if (nextTxt) activeTgValue = nextTxt;
            }
        });
    }

    var modulesHtml = '';
    modulesLoadedText.replace(/[\n\r]+/g, ',').split(',').forEach(function(item) {
        var cleanItem = item.trim();
        if (cleanItem) {
            modulesHtml += '<span class="badge-tag module">' + cleanItem + '</span> ';
        }
    });
    $('#display-modules-loaded').html(modulesHtml || '<span class="badge-tag module">Help</span>');

    $('#display-config-tg').text(tgDefaultText || '0');
    $('#display-monitor-tg').text(tgMonitorText || '212');

    var statusHtml = $status.length ? $status.html().toLowerCase() : '';
    var statusText = $status.length ? $status.text().toLowerCase() : '';
    var isConnected = (statusHtml.indexOf('connected') !== -1 || statusHtml.indexOf('online') !== -1 || statusHtml.indexOf('link') !== -1 || $('#LastHeard table tr').length > 1);

    var reflectorHtml = '<span class="badge-tag" style="background:#f1f5f9; color:#334155; border:1px solid #cbd5e1;" title="' + realReflectorHost + '">' + realReflectorHost + '</span> ';
    if (isConnected || realReflectorHost) {
        reflectorHtml += '<span class="badge-tag reflector-on">Connesso</span>';
    } else {
        reflectorHtml += '<span class="badge-tag" style="background:#f8d7da; color:#721c24;">Disconnesso</span>';
    }
    $('#display-reflector-status').html(reflectorHtml);

    $('.tg-badge').removeClass('active-tg');
    var $displayTg = $('#display-active-tg');

    if (activeTgValue && activeTgValue !== '' && activeTgValue !== '0') {
        $displayTg.text('TG ' + activeTgValue);
        if (!document.body.classList.contains('dark-mode')) {
            $displayTg.css('color', '#16a34a');
        } else {
            $displayTg.css('color', '#38bdf8');
        }
        $('.tg-badge[data-tg]').each(function() {
            if ($(this).attr('data-tg') === activeTgValue) {
                $(this).addClass('active-tg');
            }
        });
    } else {
        $displayTg.text('IDLE');
        if (!document.body.classList.contains('dark-mode')) {
            $displayTg.css('color', '#16a34a');
        } else {
            $displayTg.css('color', '#38bdf8');
        }
        $('.idle-btn').addClass('active-tg');
    }

    var $displayRadio = $('#display-radio-status');
    if (statusHtml.indexOf('tx') !== -1 || statusHtml.indexOf('transmitting') !== -1 || statusHtml.indexOf('trasmissione') !== -1 || statusText.indexOf('tx') !== -1) {
        $displayRadio.text('TRASMISSIONE (TX)').css({'background':'#fef3c7', 'color':'#d97706', 'border-color':'#fde68a'});
    } else if (statusHtml.indexOf('sql') !== -1 || statusHtml.indexOf('open') !== -1 || statusHtml.indexOf('rx') !== -1 || statusHtml.indexOf('ricezione') !== -1) {
        $displayRadio.text('RICEZIONE (RX)').css({'background':'#dcfce7', 'color':'#16a34a', 'border-color':'#bbf7d0'});
    } else {
        $displayRadio.text('IN ASCOLTO (IDLE)').css({'background':'#e0f2fe', 'color':'#0369a1', 'border-color':'#bae6fd'});
    }
}

function reloadStatusInfo(){
    $("#statusInfo").load("include/status.php", function(){ updateActiveTgState(); });
}
function reloadLastHeard(){
    $("#LastHeard").load("include/lh.php", function(){ setTimeout(reloadLastHeard, 3000); });
}

$(document).ready(function() {
    reloadStatusInfo();
    reloadLastHeard();
    setInterval(reloadStatusInfo, 3000);

    setInterval(function() {
        var d = new Date();
        var optionsDate = { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' };
        var dateString = d.toLocaleDateString('it-IT', optionsDate).toUpperCase().replace(/\./g, '');
        
        $('#header-date').text(dateString);
        $('#header-clock').text(d.toTimeString().split(' ')[0]);
    }, 1000);

    (function() {
        var d = new Date();
        var optionsDate = { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' };
        var dateString = d.toLocaleDateString('it-IT', optionsDate).toUpperCase().replace(/\./g, '');
        $('#header-date').text(dateString);
        $('#header-clock').text(d.toTimeString().split(' ')[0]);
    })();

    function showToast(message) {
        var $toast = $('#toast-notification');
        $toast.text(message).fadeIn(200);
        setTimeout(function() { $toast.fadeOut(400); }, 2500);
    }

    $(document).on('click', '.dtmf-mk', function() {
        var k = $(this).attr('data-key');
        var $inp = $('#dtmf-input-field');
        $inp.val($inp.val() + k).focus();
    });
    $(document).on('click', '#dtmf-delete-btn', function() {
        var $inp = $('#dtmf-input-field');
        var v = $inp.val();
        if (v.length > 0) $inp.val(v.substring(0, v.length - 1)).focus();
    });
    $(document).on('click', '.tg-action-btn', function(e) {
        e.preventDefault();
        var key = $(this).attr('data-key');
        if (!key) return;
        $.post(window.location.href, { dtmf_key: key }, function() {
            showToast('Talkgroup [' + key + '] inviato!');
            reloadLastHeard();
            reloadStatusInfo();
        });
    });
    $(document).on('submit', '#custom-dtmf-form', function(e) {
        e.preventDefault();
        var $inp = $('#dtmf-input-field');
        var cmd = $inp.val();
        if (!cmd) return;
        $.post(window.location.href, { dtmf_command: cmd, send_custom_dtmf: '1' }, function() {
            showToast('Comando [' + cmd + '] inviato!');
            $inp.val('');
            reloadLastHeard();
            reloadStatusInfo();
        });
    });
});
</script>
</body>
</html>