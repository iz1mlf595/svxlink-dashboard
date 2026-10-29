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

function getSVXLog() {
	$logLines = array();
	$logLines1 = array();
	$logLines2 = array();
	if (file_exists(SVXLOGPATH.SVXLOGPREFIX)) {
		$logPath = SVXLOGPATH.SVXLOGPREFIX;
		$logLines1 = explode("\n", `tail -10000 $logPath | egrep -a -h "Talker start on|Talker stop on" `);
	}
	$logLines1 = array_slice($logLines1, -250);
	if (sizeof($logLines1) < 250) {
		if (file_exists(SVXLOGPATH.SVXLOGPREFIX.".1")) {
			$logPath = SVXLOGPATH.SVXLOGPREFIX.".1";
			$logLines2 = explode("\n", `tail -10000 $logPath | egrep -a -h "Talker start on|Talker stop on" `);
		}
	}
	$logLines2 = array_slice($logLines2, -250);
	$logLines = array_merge($logLines1,$logLines2);
	$logLines = array_slice($logLines, -500);
	return $logLines;
}

function getLogContent() {
    $logFiles = ['/var/log/svxlink.log', '/var/log/svxlink'];
    $logContent = '';
    foreach ($logFiles as $logFile) {
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $numLines = count($lines);
            $startLine = max(0, $numLines - 10);
            $last10Lines = array_slice($lines, $startLine);
            $logContent = implode('', $last10Lines);
            break;
        }
    }
    return $logContent !== '' ? nl2br($logContent) : "Log file not found.";
}

function getSVXStatusLog() {
	$logLines = array();
	$logLines1 = array();
	$logLines2 = array();
	if (file_exists(SVXLOGPATH.SVXLOGPREFIX)) {
		$logPath = SVXLOGPATH.SVXLOGPREFIX;
		$logLines1 = explode("\n", `tail -10000 $logPath | egrep -a -h "EchoLink QSO|ransmitter|Selecting" `);
	}
	$logLines1 = array_slice($logLines1, -250);
	if (sizeof($logLines1) < 250) {
		if (file_exists(SVXLOGPATH.SVXLOGPREFIX.".1")) {
			$logPath = SVXLOGPATH.SVXLOGPREFIX.".1";
			$logLines2 = explode("\n", `tail -10000 $logPath |egrep -a -h "Talker start on|Talker stop on" `);
		}
	}
	$logLines2 = array_slice($logLines2, -250);
	$logLines = array_merge($logLines1,$logLines2);
	$logLines = array_slice($logLines, -250);
	return $logLines;
}

function getSVXRstatus() {
    $logFiles = [
        SVXLOGPATH.SVXLOGPREFIX,
        SVXLOGPATH.SVXLOGPREFIX . ".1"
    ];
    $linesToCheck = 100;
    $patterns = "Authentication|Connection established|Heartbeat timeout|No route to host|Connection refused|Connection timed out|Locally ordered disconnect|Deactivating link|Activating link";
    $svxrstat = '';

    foreach ($logFiles as $logPath) {
        if (file_exists($logPath)) {
            $svxrstat = `tail -$linesToCheck $logPath | egrep -a -h "$patterns"`;
            if ($svxrstat != '') break;
        }
    }

    if (preg_match('/Authentication OK|Connection established|Activating link/', $svxrstat)) {
        return "Connected";
    }

    if (preg_match('/Heartbeat timeout|No route to host|Connection refused|Connection timed out|Locally ordered disconnect|Deactivating link/', $svxrstat)) {
        return "Not connected";
    }

    return "Connected";
}

function getEchoLinkProxy() {
	if (file_exists(SVXLOGPATH.SVXLOGPREFIX)) {
           $elogPath = SVXLOGPATH.SVXLOGPREFIX; 
           $echoproxy = `tail -10000 $elogPath | grep -a -h "EchoLink proxy" | tail -1`;}
	if ($echoproxy=="" && file_exists(SVXLOGPATH.SVXLOGPREFIX.".1")) {
           $elogPath = SVXLOGPATH.SVXLOGPREFIX.".1"; 
           $echoproxy = `tail -10000 $elogPath | grep -a -h "EchoLink proxy" | tail -1`;}
           if(strpos($echoproxy,"Connected to EchoLink proxy")){
              $proxy=substr($echoproxy,strpos($echoproxy,"Connected to EchoLink proxy")+27);
              $eproxy="Connected to proxy<br><span style=\"color:brown;font-weight:bold;\">".$proxy."</span>";
            }
           elseif(strpos($echoproxy,"Disconnected from EchoLink proxy")){
              $proxy=substr($echoproxy,strpos($echoproxy,"Disconnected from EchoLink proxy")+32);
              $eproxy="<span style=\"color:red;font-weight:bold;\">Disconnected proxy</span><br><span style=\"color:brown;font-weight:bold;\">".$proxy."</span>";
            }
           elseif(strpos($echoproxy,"Access denied to EchoLink proxy")){
              $eproxy="Access denied to proxy";
            }
           else { $eproxy="";}

      return $eproxy;
}

function getEchoLog() {
	if (file_exists(SVXLOGPATH.SVXLOGPREFIX)) {
           $elogPath = SVXLOGPATH.SVXLOGPREFIX; 
           $echolog = explode("\n",`tail -10000 $elogPath | grep -a -h "EchoLink QSO" `);}
           $echolog = array_slice($echolog, -250);
      return $echolog;
}

function getConnectedEcholink($echolog) {
        $users = Array();
        foreach ($echolog as $ElogLine) {
                if(strpos($ElogLine,"state changed to CONNECTED")) {
                        $lineParts = explode(" ", $ElogLine);
              if (!in_array(substr($lineParts[2],0,-1), $users)) {
                                array_push($users,trim(substr($lineParts[2],0,-1)));
                        }
                }
                if(strpos($ElogLine,"state changed to DISCONNECTED")) {
                    $lineParts = explode(" ", $ElogLine);
    		    $call=substr($lineParts[2],0,-1);
        	    $pos = array_search($call, $users);
                    array_splice($users, $pos, 1);
                }
        }
        return $users;
}

function getEchoLinkTX() {
        $logPath = SVXLOGPATH.SVXLOGPREFIX;
        $echotxing="";
        $logLine = `tail -10000 $logPath | egrep -a -h "### EchoLink" | tail -1`;
        if (strpos($logLine,"### EchoLink talker start")) {
          $echotxing=substr($logLine,strpos($logLine,"start")+6,12);
         }
        return $echotxing;
}

function getSVXTGSelect() {
        $logPath = SVXLOGPATH.SVXLOGPREFIX;
        $tgselect="0";
        $logLine = `tail -10000 $logPath | egrep -a -h "Selecting" | tail -1`;
        if (strpos($logLine,"TG #")) {
          $tgselect=substr($logLine,strpos($logLine,"#")+1,12);
         }
        return $tgselect;
}

function getSVXTGTMP() {
        $logPath = SVXLOGPATH.SVXLOGPREFIX;
        $tgselect="0";
        $logLine = `tail -10000 $logPath | egrep -a -h "emporary monitor" | tail -1`;
        if (strpos($logLine,"Add")) {
          $tgselect=substr($logLine,strpos($logLine,"#")+1,12);
         }
         else {$tgselect=""; }
        return $tgselect;
}

function initModuleArray() {
    $modules = Array();
    foreach (SVXMODULES as $enabled) {
                $modules[$enabled] = 'Off';
        }
    return $modules;
}

function getActiveModules() {
    $logLines = array();
    $logPath = SVXLOGPATH.SVXLOGPREFIX;
    $logLines = explode("\n",`tail -10000 $logPath | egrep -a -h "Activating module|Deactivating module" `);
    $logLines = array_slice($logLines, -250);
    $modules = initModuleArray();
        foreach ($logLines as $logLine) {
                if(strpos($logLine,"Activating module")) {
                        $lineParts = explode(" ", $logLine);
	    $modul = substr($lineParts[5],0,-3);
                        if (!array_search($modul, $modules)) {
                                $modules[$modul] = 'On';
                        }
	    if (array_search($modul, $modules)) {
		$modules[$modul] = 'On';
	    }
                }
                if(strpos($logLine,"Deactivating module")) {
                        $lineParts = explode(" ", $logLine);
	    $modul = substr($lineParts[5],0,-3);
	    $modules[$modul] = 'Off';
                }

        }
        return $modules;
}

function getHeardList($logLines) {
	$heardList = array();
	foreach ($logLines as $logLine) {
	     if(strpos($logLine,"Tx1") || strpos($logLine,"Rx1") || strpos($logLine, ": Talker start on") || strpos($logLine, ": Talker stop on")) {
		if (strpos($logLine,": Talker stop on")) {
                $calltemp = substr($logLine,strpos($logLine,"TG")+4,27);
		$callsign = substr($calltemp,strpos($calltemp,":")+1,27);
		$callsign = trim($callsign);
                $target = "TG ".trim(get_string_between($logLine, "#", ":"));
		$source = "SVXRef";
		$timestamp = substr($logLine, 0, 19);
                $tx="OFF";
               } 
		if (strpos($logLine,": Talker start on")) {
                 $calltemp = substr($logLine,strpos($logLine,"TG")+4,27);
		 $callsign = substr($calltemp,strpos($calltemp,":")+1,27);
		 $callsign = trim($callsign);
                 $target = "TG ".trim(get_string_between($logLine, "#", ":"));
		 $source = "SVXRef";
		 $timestamp = substr($logLine, 0, 19);
                 $tmss=strtotime($timestamp);
                 $tmst=strtotime('now');
		 $diff=$tmst-$tmss;
                 if ($diff>300) {
                	$tx="OFF"; 
		    } else { $tx="ON";}
                } 
		if ( strlen($callsign) < 16 ) {
			array_push($heardList, array($timestamp, $callsign, $target, $tx, $source));
		}
	}
}
	return $heardList;
}

function getLastHeard($logLines) {
	$lastHeard = array();
	$heardCalls = array();
	$heardList = getHeardList($logLines);
	$counter = 0;
	foreach ($heardList as $listElem) {
		if ( $listElem[4] == "SVXRef" ) {
			$callUuid = $listElem[1]."#".$listElem[2];
			if(!(array_search($callUuid, $heardCalls) > -1)) {
				array_push($heardCalls, $callUuid);
				array_push($lastHeard, $listElem);$counter++;
			}
		}
	}
	return $lastHeard;
}

function getTXInfo() {
	$logPath = SVXLOGPATH.SVXLOGPREFIX;
	if (file_exists(SVXLOGPATH.SVXLOGPREFIX)) { 
                $txstat =`tail -10000 $logPath | egrep -a -h "Turning the transmitter|squelch is|squelch for" | tail -1`; 
                if (strpos($txstat, "ON")) { 
                    return "<tr><td style=\"background:#ff6600;color:white;\"><div style=\"margin-top:2px;margin-bottom:2px;font-weight:bold;\">TX</div></td></tr>\n";       
                }
	        if (strpos($txstat, "OPEN")) { 		
                 return "<tr><td style=\"background:#4aa361;color:black;\"><div style=\"margin-top:2px;margin-bottom:2px;font-weight:bold;\">RX</div></td></tr>\n";
                } ;
                return  "<td style=\"background:#c3e5cc;\"><div style=\"margin-top:2px;margin-bottom:2px;color:#464646;font-weight:bold;\">Listening</div></td></tr>\n"; 
        }
}

function getRXPeak() {
        $logPath = SVXLOGPATH.SVXLOGPREFIX;
        if (file_exists(SVXLOGPATH.SVXLOGPREFIX)) { 
                $txstat =`tail -100 $logPath | egrep -a -h "Distortion detected!" | tail -1`; 
		$timestamp = substr($txstat, 0, 19);
                $tmss=strtotime($timestamp);
                $tmst=strtotime('now');$diff=$tmst-$tmss;

                if (strpos($txstat, "Distortion") && ($diff < 1) ) { 
                    return "<tr><td style=\"background:#ff6600;color:white;\"><div style=\"margin-top:2px;margin-bottom:2px;font-weight:bold;\">DISTORTION</div></td></tr>\n";
                }
                return  "<td style=\"background:#c3e5cc;\"><div style=\"margin-top:2px;margin-bottom:2px;color:#464646;font-weight:bold;\">Peak OK</div></td></tr>\n";
        }
}

function getConfigItem($section,$key, $configs) {$sectionpos = array_search("[" . $section . "]", $configs) + 1;
	$len = count($configs);
	while(startsWith($configs[$sectionpos],$key."=") === false && $sectionpos <= ($len) ) {
		if (startsWith($configs[$sectionpos],"[")) {
			return null;
		}
		$sectionpos++;
	}
	return substr($configs[$sectionpos], strlen($key) + 1);
}

function get_string_between($string, $start,$end) {
    $string = " ".$string;
    $ini = strpos($string,$start);
    if ($ini == 0) {
	return "";
    }
    $ini += strlen($start);$len = strpos($string,$end,$ini) -$ini;
    return substr($string,$ini,$len);
}

$logLinesSVX = getSVXLog();
$reverseLogLinesSVX =$logLinesSVX;
array_multisort($reverseLogLinesSVX,SORT_DESC);
$lastHeard = getLastHeard($reverseLogLinesSVX);

function build_ini_string(array $a) {
        $out = '';$sectionless = '';
        foreach($a as $rootkey =>$rootvalue){
            if(is_array($rootvalue)){$indexed_root = array_keys($rootvalue) == range(0, count($rootvalue) - 1);
                if(!$indexed_root) $out .= PHP_EOL."[$rootkey]".PHP_EOL;
                foreach($rootvalue as $key =>$value){
                    if(is_array($value)){$indexed_item = array_keys($value) == range(0, count($value) - 1);
                        foreach($value as $subkey=>$subvalue){
                            if($indexed_item)$subkey = "";
                            $out .= "{$key}[$subkey] =$subvalue" . PHP_EOL;
                        }
                    }else{
                        if($indexed_root){$sectionless .= "{$rootkey}[] =$value" . PHP_EOL;
                        }else{
                            $out .= "$key =$value" . PHP_EOL;
                        }
                    }
                }
            }else{
                $sectionless .= "$rootkey =$rootvalue" . PHP_EOL;
            }
        }
        return $sectionless.$out;
}

function createjson($filename) {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="node_info.json');

        $json_array = [];$json_array["nodeLocation"] = $_POST['nodeLocation'];$json_array["hidden"] = FALSE;
        $json_array["sysop"] = $_POST['sysop'];

        foreach ($_POST['toneToTalkgroup'] as $key =>$value) {
                $ctcss_id = str_replace(".", "_", $value);
                $json_array["toneToTalkgroup"][$value] = intval($_POST["toneToTalkgroup_" . $ctcss_id]);
        }

        $json_array["qth"][0]["name"] = $_POST['name'];
        $json_array["qth"][0]["pos"]["lat"] = $_POST['lat'];
        $json_array["qth"][0]["pos"]["long"] = $_POST['long'];
        $json_array["qth"][0]["pos"]["loc"] = $_POST['Locator'];

        $json_array["qth"][0]["rx"][$_POST["RxL"]]["name"] = "Rx1";
        $json_array["qth"][0]["rx"][$_POST["RxL"]]["freq"] = floatval($_POST['RX_freq']);
        $json_array["qth"][0]["rx"][$_POST["RxL"]]["sqlType"] = implode(" , ", $_POST['SQL_TYPE']);$json_array["qth"][0]["rx"][$_POST["RxL"]]["ant"]["comment"] = $_POST['ant_comment'];
        $json_array["qth"][0]["rx"][$_POST["RxL"]]["ant"]["height"] = $_POST['anth'];$json_array["qth"][0]["rx"][$_POST["RxL"]]["ant"]["dir"] = $_POST['Adir'];

        $json_array["qth"][0]["tx"][$_POST["Txl"]]["name"] = "Tx1";
        $json_array["qth"][0]["tx"][$_POST["Txl"]]["freq"] = floatval($_POST['TX_freq']);
        $json_array["qth"][0]["tx"][$_POST["Txl"]]["pwr"] = $_POST['power'];$json_array["qth"][0]["tx"][$_POST["Txl"]]["ant"]["comment"] = $_POST['ant_comment'];
        $json_array["qth"][0]["tx"][$_POST["Txl"]]["ant"]["height"] = $_POST['anth'];$json_array["qth"][0]["tx"][$_POST["Txl"]]["ant"]["dir"] = $_POST['Adir'];
        $json_array["qth"][0]["tx"][$_POST["Txl"]]["ant"]["gain"] = $_POST['antg'];$json_array["qth"][0]["tx"][$_POST["Txl"]]["ant"]["Antenna_type"] = $_POST['antt'];

        return (json_encode($json_array, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function file_name($dir,$file_name) {$file_and_path = $dir .$file_name;
        return($file_and_path);
}

function file_backup($dir,$file_name){
        $backup_filename =$file_name . "." . date("YmdHis");
        $command = "sudo cp -f " . $dir . $file_name . " /var/www/html/backups/" . $backup_filename;
        echo exec($command);
        return;
}
    
function file_replace($dir,$file_name){$command = "sudo cp -r /var/www/html/svxlink/".$file_name." /etc/svxlink/".$file_name;
        echo exec($command);
}

function refreshIframe() {
        echo '<script>';
        echo 'function refreshEditIframe() {';
        echo '  var iframe = document.getElementById("editIframe");';
        echo '  iframe.src = iframe.src;';
        echo '}';  
        echo '</script>';
}

function displayEditor($content) {
        echo '<textarea name="content">'.$content.'</textarea>';
}

function parse_config($file_path,$file_name) {$file_edit = "$file_path"."$file_name";
      $lines = file($file_edit, FILE_IGNORE_NEW_LINES);$config = [];
      $header = array_slice($lines, 0, 4);
  
      foreach (array_slice($lines, 4) as$line) {
          $line = rtrim($line);
          if (strpos($line, '#') === 0) {
              $config[] = ['type' => 'comment', 'content' =>$line];
          } elseif (preg_match('/^\[.*\]$/',$line)) {
              $config[] = ['type' => 'section', 'content' =>$line];
          } else {
              $config[] = ['type' => 'parameter', 'content' =>$line];
          }
      }
      return ['header' => $header, 'config' =>$config];
}

function display_config($config) {
    foreach ($config['config'] as $i =>$entry) {
        $content = htmlspecialchars($entry['content']);
        $commented = (substr($content, 0, 1) === '#');

        echo "<tr>";
        echo "<td>";
        echo "<input type='text' value='{$content}' style='width: 100%;'>";
        echo "</td>";
        echo "<td class='action-column'>";
        if ($commented) {
            echo "<a href='?action=uncomment&line={$i}' style='text-decoration: none; color: #00aee8; font-weight: bold;'>[Uncomment]</a>";
        } else {
            echo "<a href='?action=comment&line={$i}' style='text-decoration: none; color: #00aee8; font-weight: bold;'>[Comment]</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
}    

function edit_config(&$config, $line_number,$new_content, $comment_out) {$line_number -= 4;
      if ($line_number < 0 || $line_number >= count($config['config'])) {
          echo "Invalid line number\n";
          return;
      }
      if ($config['config'][$line_number]['type'] == 'section') {
          echo "Cannot edit section headers\n";
          return;
      }
      if ($comment_out) {$config['config'][$line_number] = ['type' => 'comment', 'content' => '#' . $new_content];
      } else {
          $config['config'][$line_number] = ['type' => 'parameter', 'content' =>$new_content];
      }
}

function save_config($config,$file_path,$file_name) {$file_save="$file_path"."$file_name";
      $file_content = implode("\n", $config['header']) . "\n";
      foreach ($config['config'] as$entry) {
          $file_content .=$entry['content'] . "\n";
      }
      file_put_contents($file_save,$file_content);
}

function parse_config_with_header($file_path,$file_name) {$file_complete="$file_path"."$file_name";
    $lines = file($file_complete, FILE_IGNORE_NEW_LINES);$header = array_slice($lines, 0, 4);$config_lines = array_slice($lines, 4);$parsed_config = [
        'header' => $header,
        'config' => []
    ];

    foreach ($config_lines as $line_number =>$line) {
        $line = rtrim($line);
        if (preg_match('/^\[.*\]$/', $line)) {$parsed_config['config'][] = [
                'type' => 'section',
                'content' => $line,
                'line_number' => $line_number + 4             ];         } elseif (strpos($line, '#') === 0) {
            $parsed_config['config'][] = [
                'type' => 'comment',
                'content' => $line,
                'line_number' => $line_number + 4
            ];
        } elseif (trim($line) !== '') {$parsed_config['config'][] = [
                'type' => 'parameter',
                'content' => $line,
                'line_number' => $line_number + 4             ];         } else {$parsed_config['config'][] = [
                'type' => 'empty',
                'content' => '',
                'line_number' => $line_number + 4
            ];
        }
    }
    return $parsed_config;
}

function insert_config_with_header_into_db($parsed_config,$pdo) {
    $stmt =$pdo->prepare("INSERT INTO config_lines (line_number, type, content) VALUES (:line_number, :type, :content)");
    foreach ($parsed_config['config'] as $line) {$stmt->execute([
            ':line_number' => $line['line_number'],
            ':type' => $line['type'],
            ':content' => $line['content']
        ]);
    }
}

function custom_parse_ini_file($filename) {
    $config = [];$current_section = '';
    $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as$line) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }
        if (preg_match('/^\[(.+)\]$/', $line,$matches)) {
            $current_section = trim($matches[1]);
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key,$value) = array_map('trim', explode('=', $line, 2));$active = true;
            if (strpos($key, '#') === 0) {
                $active = false;
                $key = ltrim($key, '# ');
            }
            if (!isset($config[$current_section])) {
                $config[$current_section] = [];
            }
            $config[$current_section][$key] = [
                'value' => $value,
                'active' => $active,
            ];
        }
    }
    return $config;
}

function save_svxconfig($configFile,$svxconfig_post) {
    $handle = fopen($configFile, 'w');
    if (!$handle) {
        die("Cannot open file ($configFile) for writing. Check file permissions.");
    }

    foreach ($svxconfig_post['value'] as $section =>$entries) {
        fwrite($handle, "[$section]\n");
        foreach ($entries as $key =>$value) {
            if (isset($svxconfig_post['active'][$section][$key])) {
                fwrite($handle, "$key = {$value}\n");
            } else {
                fwrite($handle, "#$key = {$value}\n");
            }
        }
        fwrite($handle, "\n");
    }

    fclose($handle);
    echo "Configuration saved and restarted";
}
?>