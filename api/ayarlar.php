<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/config.php';
checkLoginAPI();
checkRoleAPI(['admin']);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($action) {
    case 'listele':
        $stmt = $db->query("SELECT ayar_adi, ayar_degeri FROM ayarlar");
        $ayarlar = [];
        while ($row = $stmt->fetch()) {
            $ayarlar[$row['ayar_adi']] = $row['ayar_degeri'];
        }
        echo json_encode($ayarlar);
        break;
        
    case 'kaydet':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            try {
                $db->beginTransaction();
                
                foreach ($data as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO ayarlar (ayar_adi, ayar_degeri) VALUES (?, ?) 
                                         ON DUPLICATE KEY UPDATE ayar_degeri = ?");
                    $stmt->execute([$key, $value, $value]);
                }
                
                require_once '../includes/log_activity.php';
                logActivity($db, 'ayarlar_guncelle', 'ayarlar', 0, 'Ayarlar güncellendi');
                
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Ayarlar kaydedildi']);
            } catch(Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    case 'yazicilar':
        $stmt = $db->query("SELECT * FROM yazici ORDER BY lokasyon, yazici_adi");
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'yazici_getir':
        $id = intval($_GET['id']);
        $stmt = $db->prepare("SELECT * FROM yazici WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode($stmt->fetch());
        break;
        
    case 'yazici_ekle':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $yazici_adi = cleanInput($data['yazici_adi']);
            $lokasyon = cleanInput($data['lokasyon']);
            $yazici_tipi = cleanInput($data['yazici_tipi']);
            $ip_adresi = cleanInput($data['ip_adresi'] ?? '');
            $port = intval($data['port'] ?? 9100);
            $aciklama = cleanInput($data['aciklama'] ?? '');
            $durum = cleanInput($data['durum'] ?? 'aktif');
            
            $stmt = $db->prepare("INSERT INTO yazici (yazici_adi, lokasyon, yazici_tipi, ip_adresi, port, aciklama, durum) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$yazici_adi, $lokasyon, $yazici_tipi, $ip_adresi, $port, $aciklama, $durum])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'yazici_ekle', 'yazici', $db->lastInsertId(), "Yazıcı eklendi: $yazici_adi");
                echo json_encode(['success' => true, 'message' => 'Yazıcı eklendi']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
            }
        }
        break;
        
    case 'yazici_guncelle':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id']);
            $yazici_adi = cleanInput($data['yazici_adi']);
            $lokasyon = cleanInput($data['lokasyon']);
            $yazici_tipi = cleanInput($data['yazici_tipi']);
            $ip_adresi = cleanInput($data['ip_adresi'] ?? '');
            $port = intval($data['port'] ?? 9100);
            $aciklama = cleanInput($data['aciklama'] ?? '');
            $durum = cleanInput($data['durum'] ?? 'aktif');
            
            $stmt = $db->prepare("UPDATE yazici SET yazici_adi = ?, lokasyon = ?, yazici_tipi = ?, 
                                 ip_adresi = ?, port = ?, aciklama = ?, durum = ? WHERE id = ?");
            if ($stmt->execute([$yazici_adi, $lokasyon, $yazici_tipi, $ip_adresi, $port, $aciklama, $durum, $id])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'yazici_guncelle', 'yazici', $id, "Yazıcı güncellendi: $yazici_adi");
                echo json_encode(['success' => true, 'message' => 'Yazıcı güncellendi']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
            }
        }
        break;
        
    case 'yazici_sil':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id']);
            
            $stmt = $db->prepare("DELETE FROM yazici WHERE id = ?");
            if ($stmt->execute([$id])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'yazici_sil', 'yazici', $id, 'Yazıcı silindi');
                echo json_encode(['success' => true, 'message' => 'Yazıcı silindi']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
            }
        }
        break;
        
    case 'personel_yetkileri':
        $personel_id = intval($_GET['personel_id']);
        $stmt = $db->prepare("SELECT * FROM kullanici_yetki WHERE personel_id = ?");
        $stmt->execute([$personel_id]);
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'yetki_guncelle':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $personel_id = intval($data['personel_id']);
            $yetki_adi = cleanInput($data['yetki_adi']);
            $durum = $data['durum'] ? 1 : 0;
            
            $stmt = $db->prepare("INSERT INTO kullanici_yetki (personel_id, yetki_adi, durum) 
                                 VALUES (?, ?, ?) 
                                 ON DUPLICATE KEY UPDATE durum = ?");
            if ($stmt->execute([$personel_id, $yetki_adi, $durum, $durum])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'yetki_guncelle', 'kullanici_yetki', $personel_id, "Yetki güncellendi: $yetki_adi");
                echo json_encode(['success' => true, 'message' => 'Yetki güncellendi']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
            }
        }
        break;
        
    case 'yazicilari_listele':
        // Windows'taki yazıcıları listele
        $yazicilar = [];
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Yöntem 1: WMIC ile yazıcıları listele
            $command = 'wmic printer get name,portname /format:list';
            exec($command, $output, $return_var);

            if ($return_var === 0 && !empty($output)) {
                $current_name = '';
                $current_port = '';
                foreach ($output as $line) {
                    $line = trim($line);
                    if (strpos($line, 'Name=') === 0) {
                        $name = substr($line, 5);
                        if (!empty($name) && $name !== $current_name) {
                            if (!empty($current_name)) {
                                $ip = '';
                                $port = 9100;

                                if (!empty($current_port)) {
                                    if (preg_match('/IP_([0-9]{1,3}(?:\.[0-9]{1,3}){3})/', $current_port, $matches)) {
                                        $ip = $matches[1];
                                    } elseif (preg_match('/([0-9]{1,3}(?:\.[0-9]{1,3}){3})/', $current_port, $matches)) {
                                        $ip = $matches[1];
                                    } elseif (preg_match('/([0-9]{1,3}(?:\.[0-9]{1,3}){3}):([0-9]+)/', $current_port, $matches)) {
                                        $ip = $matches[1];
                                        $port = intval($matches[2]);
                                    }
                                }

                                $yazicilar[] = [
                                    'name' => $current_name,
                                    'display_name' => $current_name . (!empty($ip) ? ' (' . $ip . ')' : ''),
                                    'ip' => $ip,
                                    'port' => $port
                                ];
                            }
                            $current_name = $name;
                            $current_port = '';
                        }
                    } elseif (strpos($line, 'PortName=') === 0) {
                        $current_port = substr($line, 9);
                    }
                }

                if (!empty($current_name)) {
                    $ip = '';
                    $port = 9100;

                    if (!empty($current_port)) {
                        if (preg_match('/IP_([0-9]{1,3}(?:\.[0-9]{1,3}){3})/', $current_port, $matches)) {
                            $ip = $matches[1];
                        } elseif (preg_match('/([0-9]{1,3}(?:\.[0-9]{1,3}){3})/', $current_port, $matches)) {
                            $ip = $matches[1];
                        } elseif (preg_match('/([0-9]{1,3}(?:\.[0-9]{1,3}){3}):([0-9]+)/', $current_port, $matches)) {
                            $ip = $matches[1];
                            $port = intval($matches[2]);
                        }
                    }

                    $yazicilar[] = [
                        'name' => $current_name,
                        'display_name' => $current_name . (!empty($ip) ? ' (' . $ip . ')' : ''),
                        'ip' => $ip,
                        'port' => $port
                    ];
                }
            }

            // Yöntem 2: PowerShell ile yazıcıları listele (WMIC yoksa)
            if (empty($yazicilar)) {
                $ps_command = 'powershell -NoProfile -Command "Get-Printer | Select-Object -ExpandProperty Name"';
                exec($ps_command, $ps_output, $ps_return);

                $port_command = 'powershell -NoProfile -Command "Get-Printer | Select-Object Name,PortName | ConvertTo-Json"';
                exec($port_command, $port_output, $port_return);

                $port_map = [];
                if ($port_return === 0 && !empty($port_output)) {
                    $decoded = json_decode(implode('', $port_output), true);
                    if (is_array($decoded)) {
                        $entries = isset($decoded[0]) ? $decoded : [$decoded];
                        foreach ($entries as $entry) {
                            if (!empty($entry['Name']) && !empty($entry['PortName'])) {
                                $port_map[$entry['Name']] = $entry['PortName'];
                            }
                        }
                    }
                }

                $port_info_command = 'powershell -NoProfile -Command "Get-PrinterPort | Select-Object Name,PrinterHostAddress,PortNumber | ConvertTo-Json"';
                exec($port_info_command, $port_info_output, $port_info_return);
                $port_info_map = [];
                if ($port_info_return === 0 && !empty($port_info_output)) {
                    $decoded_ports = json_decode(implode('', $port_info_output), true);
                    if (is_array($decoded_ports)) {
                        $entries = isset($decoded_ports[0]) ? $decoded_ports : [$decoded_ports];
                        foreach ($entries as $entry) {
                            if (!empty($entry['Name'])) {
                                $port_info_map[$entry['Name']] = [
                                    'ip' => $entry['PrinterHostAddress'] ?? '',
                                    'port' => $entry['PortNumber'] ?? 9100
                                ];
                            }
                        }
                    }
                }

                if ($ps_return === 0 && !empty($ps_output)) {
                    foreach ($ps_output as $printer_name) {
                        $printer_name = trim($printer_name);
                        if (empty($printer_name)) {
                            continue;
                        }

                        $ip = '';
                        $port = 9100;
                        if (!empty($port_map[$printer_name])) {
                            $port_name = $port_map[$printer_name];
                            if (!empty($port_info_map[$port_name]['ip'])) {
                                $ip = $port_info_map[$port_name]['ip'];
                            }
                            if (!empty($port_info_map[$port_name]['port'])) {
                                $port = intval($port_info_map[$port_name]['port']);
                            }
                        }

                        $yazicilar[] = [
                            'name' => $printer_name,
                            'display_name' => $printer_name . (!empty($ip) ? ' (' . $ip . ')' : ''),
                            'ip' => $ip,
                            'port' => $port
                        ];
                    }
                }
            }
            
        } else {
            // Linux için lpstat komutu
            exec('lpstat -p 2>/dev/null | awk \'{print $2}\'', $output, $return_var);
            $uri_map = [];
            exec('lpstat -v 2>/dev/null', $uri_output, $uri_return);
            if ($uri_return === 0 && !empty($uri_output)) {
                foreach ($uri_output as $line) {
                    if (preg_match('/device for ([^:]+): (.+)$/', $line, $matches)) {
                        $uri_map[trim($matches[1])] = trim($matches[2]);
                    }
                }
            }

            if ($return_var === 0 && !empty($output)) {
                foreach ($output as $printer_name) {
                    $printer_name = trim($printer_name);
                    if (empty($printer_name)) {
                        continue;
                    }

                    $ip = '';
                    $port = 9100;
                    $uri = $uri_map[$printer_name] ?? '';

                    if (!empty($uri)) {
                        if (preg_match('/([0-9]{1,3}(?:\.[0-9]{1,3}){3})/', $uri, $matches)) {
                            $ip = $matches[1];
                        }
                        if (preg_match('/:([0-9]+)/', $uri, $matches)) {
                            $port = intval($matches[1]);
                        }
                    }

                    $yazicilar[] = [
                        'name' => $printer_name,
                        'display_name' => $printer_name . (!empty($ip) ? ' (' . $ip . ')' : ''),
                        'ip' => $ip,
                        'port' => $port
                    ];
                }
            }
        }
        
        // Duplicate'leri temizle
        $unique_printers = [];
        $seen = [];
        foreach ($yazicilar as $printer) {
            $name_key = strtolower(trim($printer['name']));
            if (!in_array($name_key, $seen)) {
                $unique_printers[] = $printer;
                $seen[] = $name_key;
            }
        }
        
        echo json_encode(['success' => true, 'yazicilar' => $unique_printers]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        break;
}
?>
