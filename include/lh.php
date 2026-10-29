<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusione corretta del file di configurazione ponte o diretto
if (file_exists(__DIR__ . "/config.php")) {
    include_once __DIR__ . "/config.php";
} elseif (file_exists(__DIR__ . "/../config-dashboard/config.php")) {
    include_once __DIR__ . "/../config-dashboard/config.php";
}

include_once __DIR__ . "/tools.php";        
include_once __DIR__ . "/functions.php";    
include_once __DIR__ . "/tgdb.php";    

$logLinesSVX = getSVXLog();
$reverseLogLinesSVX = $logLinesSVX;
if (is_array($reverseLogLinesSVX)) {
    array_multisort($reverseLogLinesSVX, SORT_DESC);
}
$lastHeard = getLastHeard($reverseLogLinesSVX);

$liveCall = null;
if (isset($lastHeard[0]) && isset($lastHeard[0][3]) && $lastHeard[0][3] === "ON") {
    $liveCall = $lastHeard[0];
}
?>
<style>
@keyframes live-pulse-light {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.3); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}
@keyframes live-blink-light {
    0% { opacity: 1; }
    50% { opacity: 0.75; }
    100% { opacity: 1; }
}
.live-transmitting-box {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%) !important;
    border: 1px solid #fecaca !important;
    animation: live-blink-light 1.5s infinite ease-in-out !important;
    box-shadow: 0 4px 20px -4px rgba(239, 68, 68, 0.2) !important;
    border-radius: 16px !important;
    color: #991b1b !important;
}
body.dark-mode .live-transmitting-box {
    background: linear-gradient(135deg, #451a03 0%, #78350f 100%) !important;
    border-color: #b45309 !important;
    color: #fde68a !important;
}

.lh-card-container {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    overflow: hidden !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
    margin-top: 20px !important;
    transition: background 0.3s ease, border-color 0.3s ease;
}
body.dark-mode .lh-card-container {
    background: #1e293b !important;
    border-color: #334155 !important;
}

.lh-table {
    width: 100% !important;
    border-collapse: collapse !important;
    text-align: left !important;
    font-size: 13px !important;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    border: none !important;
}
.lh-table thead tr {
    background: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
    transition: background 0.3s ease, border-color 0.3s ease;
}
body.dark-mode .lh-table thead tr {
    background: #0f172a !important;
    border-bottom-color: #334155 !important;
}

.lh-table th {
    padding: 14px 20px !important;
    font-size: 11px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.08em !important;
    font-weight: 700 !important;
    color: #64748b !important;
    border: none !important;
    background: transparent !important;
}
body.dark-mode .lh-table th {
    color: #94a3b8 !important;
}

.lh-table tbody tr {
    border: none !important;
    border-bottom: 1px solid #f1f5f9 !important;
    background: #ffffff !important;
    transition: background-color 0.15s ease, border-color 0.3s ease;
}
body.dark-mode .lh-table tbody tr {
    background: #1e293b !important;
    border-bottom-color: #334155 !important;
}

.lh-table tbody tr:hover {
    background-color: #f8fafc !important;
}
body.dark-mode .lh-table tbody tr:hover {
    background-color: #334155 !important;
}

.lh-table td {
    padding: 13px 20px !important;
    vertical-align: middle !important;
    color: #334155 !important;
    border: none !important;
    background: transparent !important;
}
body.dark-mode .lh-table td {
    color: #f1f5f9 !important;
}

.lh-time {
    color: #94a3b8 !important;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    font-size: 12px !important;
    font-weight: 500 !important;
}
body.dark-mode .lh-time {
    color: #94a3b8 !important;
}

.lh-callsign-link {
    color: #0f172a !important;
    text-decoration: none !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
}
body.dark-mode .lh-callsign-link {
    color: #f8fafc !important;
}

.lh-tg-badge {
    background: #eff6ff !important;
    color: #1d4ed8 !important;
    padding: 4px 10px !important;
    border-radius: 8px !important;
    font-weight: 700 !important;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    font-size: 11px !important;
    border: 1px solid #dbeafe !important;
    display: inline-block !important;
}
body.dark-mode .lh-tg-badge {
    background: #0f172a !important;
    color: #38bdf8 !important;
    border-color: #334155 !important;
}

.lh-name {
    font-weight: 600 !important;
    color: #475569 !important;
}
body.dark-mode .lh-name {
    color: #cbd5e1 !important;
}

.lh-section-title {
    font-weight: 700;
    font-size: 14px;
    color: #1e3c72;
    text-transform: uppercase;
    letter-spacing: 1px;
}
body.dark-mode .lh-section-title {
    color: #38bdf8 !important;
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <span class="lh-section-title">SVXReflector Activity</span>
</div>

<?php if ($liveCall): ?>
    <?php 
        $liveTimestampStr = isset($liveCall[0]) ? $liveCall[0] : ""; 
        $liveTimeOnly = substr($liveTimestampStr, -8);
        $liveCallsign = isset($liveCall[1]) ? $liveCall[1] : "";
        
        $rawTg = isset($liveCall[2]) ? $liveCall[2] : "";
        $liveTgCode = trim(str_replace('TG', '', $rawTg));
        if (isset($lastHeard[0]['TG'])) {
            $liveTgCode = trim($lastHeard[0]['TG']);
        }

        $liveTgName = "------";
        if (isset($tgdb_array[$liveTgCode])) {
            $liveTgName = $tgdb_array[$liveTgCode];
        } elseif (isset($tgdb_array[$rawTg])) {
            $liveTgName = $tgdb_array[$rawTg];
        }
        
        if ($liveTgName == "" ) { $liveTgName = "------"; }
        if ($liveTgCode >= 1239900 and $liveTgCode <= 1239999) { $liveTgName = "AUTO QSY"; }

        $safeLiveCallsign = htmlspecialchars(strip_tags($liveCallsign), ENT_QUOTES, 'UTF-8');
        $safeLiveTgCode = htmlspecialchars(strip_tags($liveTgCode), ENT_QUOTES, 'UTF-8');
        $safeLiveTgName = htmlspecialchars(strip_tags($liveTgName), ENT_QUOTES, 'UTF-8');
        $safeLiveTimeOnly = htmlspecialchars(strip_tags($liveTimeOnly), ENT_QUOTES, 'UTF-8');
    ?>
    <div class="live-transmitting-box" style="padding: 16px 22px; border-radius: 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <span style="background: #dc2626; color: #ffffff; font-weight: 800; font-size: 11px; padding: 6px 14px; border-radius: 30px; letter-spacing: 0.05em; display: inline-flex; align-items: center; gap: 8px; animation: live-pulse-light 2s infinite;">
                <span style="width: 7px; height: 7px; background: #ffffff; border-radius: 50%;"></span> LIVE (<span id="box-live-sec">00:00</span>)
            </span>
            <span style="font-size: 16px; font-weight: 800; font-family: monospace; letter-spacing: 0.05em;"><?php echo $safeLiveCallsign; ?></span>
            <span style="font-size: 13px;">sta trasmettendo sul <b>TG <?php echo $safeLiveTgCode; ?></b> (<?php echo $safeLiveTgName; ?>)</span>
        </div>
        <div style="font-size: 12px; font-family: monospace; background: rgba(220, 38, 38, 0.1); padding: 5px 12px; border-radius: 8px;">
            Inizio: <?php echo $safeLiveTimeOnly; ?>
        </div>
    </div>
    
    <script>
    (function() {
        const callsignKey = <?php echo json_encode($liveCallsign); ?>;
        const timeKey = <?php echo json_encode($liveTimeOnly); ?>;
        const rowKey = callsignKey + '_' + timeKey;
        
        let startTime = localStorage.getItem('svx_start_' + rowKey);
        if (!startTime) {
            startTime = Date.now();
            localStorage.setItem('svx_start_' + rowKey, startTime);
        }

        function updateLiveTimer() {
            let start = parseInt(localStorage.getItem('svx_start_' + rowKey));
            let elapsedSec = Math.floor((Date.now() - start) / 1000);
            if (elapsedSec < 0) elapsedSec = 0;

            let mins = Math.floor(elapsedSec / 60).toString().padStart(2, '0');
            let secs = (elapsedSec % 60).toString().padStart(2, '0');
            let formatted = mins + ':' + secs;

            const boxSpan = document.getElementById('box-live-sec');
            if (boxSpan) boxSpan.textContent = formatted;
        }

        updateLiveTimer();
        if (window.svxLiveInterval) clearInterval(window.svxLiveInterval);
        window.svxLiveInterval = setInterval(updateLiveTimer, 1000);
    })();
    </script>
<?php else: ?>
    <script>
    if (window.svxLiveInterval) clearInterval(window.svxLiveInterval);
    </script>
<?php endif; ?>

<div class="lh-card-container">
  <table class="lh-table">
    <thead>
      <tr>
        <th style="width: 150px;">Time</th>
        <th style="width: 200px;">Callsign</th>
        <th style="width: 100px;">TG #</th>
        <th>TG Name</th>
      </tr>
    </thead>
    <tbody>
<?php
if (!empty($lastHeard) && is_array($lastHeard)) {
    for ($i = 0; $i <= 20; $i++) {
        if (isset($lastHeard[$i])) {
            $listElem = $lastHeard[$i];
            if (isset($listElem[1]) && $listElem[1] != "") {
                $timestamp = $listElem[0];
                $local_time = substr($timestamp, -8);
                $callsign = $listElem[1];
                $targetTg = $listElem[2];
                
                $isLiveRow = (isset($listElem[3]) && $listElem[3] == "ON");
                
                $tximg = "";
                if ($isLiveRow) {
                    $tximg = "<img src='images/tx.gif' height='18' alt='TXing' title='TXing' style='vertical-align: middle; margin-left: 6px;'>";
                }

                $ref = substr($callsign, 0, 3);
                $call = $callsign;
                $ssid = strpos($callsign, '-');
                
                if ((!preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $callsign) or $ref == "XLX" or $ref == "YSF" or $ref == "M17" or substr($callsign, 0, 3) == "TG")) {
                    $callsignHtml = "<span class='lh-callsign-link'>" . htmlspecialchars($callsign, ENT_QUOTES, 'UTF-8') . "</span>" . $tximg;
                } else {
                    if ($ssid !== false) {
                        $call = substr($callsign, 0, $ssid);
                    }
                    $callsignHtml = "<a href='http://www.qrz.com/db/" . urlencode($call) . "' target='_blank' class='lh-callsign-link'>" . htmlspecialchars($callsign, ENT_QUOTES, 'UTF-8') . "</a>" . $tximg;
                }
            
                $tgnumber = trim(str_replace('TG', '', $targetTg));
                $name = isset($tgdb_array[$tgnumber]) ? $tgdb_array[$tgnumber] : "";
                if ($name == "" ) { $name = "------"; }
                if ($tgnumber >= 1239900 and $tgnumber <= 1239999) { $name = "AUTO QSY"; }

                $rowStyle = "";
                if ($isLiveRow) {
                    $rowStyle = " style='background-color: rgba(239, 68, 68, 0.12) !important; font-weight: bold;'";
                }

                echo "<tr" . $rowStyle . ">";
                echo "<td class='lh-time'>" . htmlspecialchars($local_time, ENT_QUOTES, 'UTF-8') . ($isLiveRow ? " <span style='display:inline-block; width:7px; height:7px; background:#dc2626; border-radius:50%; margin-left:6px; vertical-align:middle;'></span> <span style='color:#dc2626; font-size:10px;'>LIVE</span>" : "") . "</td>";
                echo "<td>" . $callsignHtml . "</td>";
                echo "<td><span class='lh-tg-badge'>" . htmlspecialchars($tgnumber, ENT_QUOTES, 'UTF-8') . "</span></td>";
                echo "<td class='lh-name'>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8')  . "</td>";
                echo "</tr>\n";
            }
        }
    }
}
?>
    </tbody>
  </table>
</div>