<?php
// Hata raporlamayı kapat - JSON API için
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Output buffering başlat
ob_start();

header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../config/config.php';
} catch(Exception $e) {
    @ob_clean();
    echo json_encode(['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()]);
    exit();
}

checkLoginAPI();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Yazıcıya yazdırma fonksiyonu
function yaziciyaYazdir($db, $lokasyon, $icerik, $yazici_tipi = 'fis') {
    // Aktif yazıcıyı bul
    $stmt = $db->prepare("SELECT * FROM yazici WHERE lokasyon = ? AND yazici_tipi = ? AND durum = 'aktif' LIMIT 1");
    $stmt->execute([$lokasyon, $yazici_tipi]);
    $yazici = $stmt->fetch();
    
    if (!$yazici) {
        return ['success' => false, 'message' => 'Yazıcı bulunamadı'];
    }
    
    // Eğer IP adresi varsa, network yazıcısına yazdır
    if (!empty($yazici['ip_adresi'])) {
        return networkYaziciyaYazdir($yazici, $icerik);
    }
    
    // Local yazıcıya yazdır (Windows için)
    return localYaziciyaYazdir($yazici, $icerik);
}

function networkYaziciyaYazdir($yazici, $icerik) {
    $ip = $yazici['ip_adresi'];
    $port = $yazici['port'] ?? 9100;
    
    try {
        $socket = @fsockopen($ip, $port, $errno, $errstr, 2);
        if (!$socket) {
            return ['success' => false, 'message' => "Yazıcıya bağlanılamadı: $errstr"];
        }
        
        fwrite($socket, $icerik);
        fclose($socket);
        
        return ['success' => true, 'message' => 'Yazdırma başarılı'];
    } catch(Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function localYaziciyaYazdir($yazici, $icerik) {
    // Windows için local yazıcıya yazdırma
    $yazici_adi = $yazici['yazici_adi'];
    
    // Geçici dosya oluştur
    $temp_file = sys_get_temp_dir() . '/print_' . uniqid() . '.txt';
    file_put_contents($temp_file, $icerik);
    
    // Windows print komutu
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $command = "print /D:\"$yazici_adi\" \"$temp_file\"";
        exec($command, $output, $return_var);
        
        if ($return_var === 0) {
            unlink($temp_file);
            return ['success' => true, 'message' => 'Yazdırma başarılı'];
        } else {
            unlink($temp_file);
            return ['success' => false, 'message' => 'Yazdırma hatası'];
        }
    }
    
    // Linux için
    $command = "lp -d \"$yazici_adi\" \"$temp_file\"";
    exec($command, $output, $return_var);
    
    unlink($temp_file);
    
    if ($return_var === 0) {
        return ['success' => true, 'message' => 'Yazdırma başarılı'];
    } else {
        return ['success' => false, 'message' => 'Yazdırma hatası'];
    }
}

// Fiş formatı oluştur
function fisFormatiOlustur($siparis, $detaylar) {
    $fis = "";
    $fis .= str_repeat("=", 42) . "\n";
    $fis .= str_pad("CAFE OTOMASYON", 42, " ", STR_PAD_BOTH) . "\n";
    $fis .= str_repeat("=", 42) . "\n";
    $fis .= "Sipariş No: " . $siparis['siparis_no'] . "\n";
    $fis .= "Masa: " . $siparis['masa_no'] . "\n";
    $fis .= "Tarih: " . date('d.m.Y H:i', strtotime($siparis['olusturma_tarihi'])) . "\n";
    $fis .= str_repeat("-", 42) . "\n";
    
    foreach ($detaylar as $detay) {
        $fis .= sprintf("%-20s %2dx %8.2f\n", 
            substr($detay['urun_adi'], 0, 20), 
            $detay['adet'], 
            $detay['toplam_fiyat']
        );
    }
    
    $fis .= str_repeat("-", 42) . "\n";
    $fis .= sprintf("%-30s %10.2f\n", "Ara Toplam:", $siparis['toplam_tutar']);
    $fis .= sprintf("%-30s %10.2f\n", "KDV (%20):", $siparis['kdv_tutari']);
    $fis .= sprintf("%-30s %10.2f\n", "İndirim:", $siparis['indirim_tutari'] ?? 0);
    $fis .= str_repeat("=", 42) . "\n";
    $fis .= sprintf("%-30s %10.2f\n", "TOPLAM:", 
        $siparis['toplam_tutar'] + $siparis['kdv_tutari'] - ($siparis['indirim_tutari'] ?? 0));
    $fis .= str_repeat("=", 42) . "\n";
    $fis .= "\n\n";
    
    return $fis;
}

// Mutfak sipariş formatı
function mutfakSiparisFormati($siparis, $detaylar) {
    $fis = "";
    $fis .= str_repeat("=", 42) . "\n";
    $fis .= str_pad("MUTFAK SIPARISI", 42, " ", STR_PAD_BOTH) . "\n";
    $fis .= str_repeat("=", 42) . "\n";
    $fis .= "Sipariş No: " . $siparis['siparis_no'] . "\n";
    $fis .= "Masa: " . $siparis['masa_no'] . "\n";
    $fis .= "Saat: " . date('H:i', strtotime($siparis['olusturma_tarihi'])) . "\n";
    $fis .= str_repeat("-", 42) . "\n";
    
    foreach ($detaylar as $detay) {
        $fis .= sprintf("%2dx %-30s\n", $detay['adet'], substr($detay['urun_adi'], 0, 30));
        if (!empty($detay['notlar'])) {
            $fis .= "    Not: " . substr($detay['notlar'], 0, 35) . "\n";
        }
    }
    
    $fis .= str_repeat("=", 42) . "\n";
    $fis .= "\n\n";
    
    return $fis;
}

switch($action) {
    case 'siparis_yazdir':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $siparis_id = intval($data['siparis_id']);
            $lokasyon = cleanInput($data['lokasyon'] ?? 'kasa');
            
            // Sipariş bilgilerini al
            $stmt = $db->prepare("SELECT s.*, m.masa_no FROM siparis s 
                                JOIN masa m ON s.masa_id = m.id 
                                WHERE s.id = ?");
            $stmt->execute([$siparis_id]);
            $siparis = $stmt->fetch();
            
            if (!$siparis) {
                @ob_clean();
                echo json_encode(['success' => false, 'message' => 'Sipariş bulunamadı']);
                exit;
            }
            
            // Sipariş detaylarını al
            $stmt = $db->prepare("SELECT sd.*, u.urun_adi FROM siparis_detay sd 
                                JOIN menu_urun u ON sd.urun_id = u.id 
                                WHERE sd.siparis_id = ?");
            $stmt->execute([$siparis_id]);
            $detaylar = $stmt->fetchAll();
            
            // Lokasyona göre filtrele
            if ($lokasyon === 'mutfak' || $lokasyon === 'bar') {
                $stmt = $db->prepare("SELECT sd.*, u.urun_adi, u.lokasyon FROM siparis_detay sd 
                                    JOIN menu_urun u ON sd.urun_id = u.id 
                                    WHERE sd.siparis_id = ? AND u.lokasyon = ?");
                $stmt->execute([$siparis_id, $lokasyon]);
                $detaylar = $stmt->fetchAll();
                
                if (count($detaylar) > 0) {
                    $icerik = mutfakSiparisFormati($siparis, $detaylar);
                    $result = yaziciyaYazdir($db, $lokasyon, $icerik, 'mutfak');
                } else {
                    $result = ['success' => true, 'message' => 'Bu lokasyon için sipariş yok'];
                }
            } else {
                // Kasa için fiş
                $icerik = fisFormatiOlustur($siparis, $detaylar);
                $result = yaziciyaYazdir($db, 'kasa', $icerik, 'fis');
            }
            
            @ob_clean();
            echo json_encode($result);
        }
        break;
        
    case 'otomatik_yazdir':
        // Sipariş oluşturulduğunda otomatik yazdır
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $siparis_id = intval($data['siparis_id']);
            
            // Ayarlardan otomatik yazdır kontrolü
            $stmt = $db->query("SELECT ayar_degeri FROM ayarlar WHERE ayar_adi = 'otomatik_yazdir'");
            $ayar = $stmt->fetch();
            
            if ($ayar && $ayar['ayar_degeri'] == '1') {
                // Sipariş bilgilerini al
                $stmt = $db->prepare("SELECT s.*, m.masa_no FROM siparis s 
                                    JOIN masa m ON s.masa_id = m.id 
                                    WHERE s.id = ?");
                $stmt->execute([$siparis_id]);
                $siparis = $stmt->fetch();
                
                if ($siparis) {
                    // Mutfak ve bar için ayrı ayrı yazdır
                    $stmt = $db->prepare("SELECT sd.*, u.urun_adi, u.lokasyon FROM siparis_detay sd 
                                        JOIN menu_urun u ON sd.urun_id = u.id 
                                        WHERE sd.siparis_id = ? AND u.lokasyon IN ('mutfak', 'bar')");
                    $stmt->execute([$siparis_id]);
                    $detaylar = $stmt->fetchAll();
                    
                    // Lokasyona göre grupla
                    $lokasyonlar = [];
                    foreach ($detaylar as $detay) {
                        $lokasyonlar[$detay['lokasyon']][] = $detay;
                    }
                    
                    foreach ($lokasyonlar as $lokasyon => $lokasyon_detaylar) {
                        $icerik = mutfakSiparisFormati($siparis, $lokasyon_detaylar);
                        yaziciyaYazdir($db, $lokasyon, $icerik, 'mutfak');
                    }
                }
            }
            
            @ob_clean();
            echo json_encode(['success' => true]);
        }
        break;
        
    default:
        @ob_clean();
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        break;
}

@ob_end_flush();
?>

