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
checkRoleAPI(['admin', 'garson', 'kasiyer']); // Masa işlemleri için yetki

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($action) {
    case 'birles':
        if ($method == 'POST') {
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception('Geçersiz veri formatı');
                }
                
                $ana_masa_id = intval($data['ana_masa_id'] ?? 0);
                $birlesen_masa_id = intval($data['birlesen_masa_id'] ?? 0);
                
                if ($ana_masa_id <= 0 || $birlesen_masa_id <= 0) {
                    throw new Exception('Geçersiz masa ID');
                }
                
                if ($ana_masa_id == $birlesen_masa_id) {
                    throw new Exception('Aynı masayı birleştiremezsiniz');
                }
                
                $aciklama = cleanInput($data['aciklama'] ?? '');
                
                $db->beginTransaction();
                
                // Birleştirme kaydı oluştur
                $stmt = $db->prepare("INSERT INTO masa_birlesim (ana_masa_id, birlesen_masa_id, personel_id, aciklama) 
                                     VALUES (?, ?, ?, ?)");
                $stmt->execute([$ana_masa_id, $birlesen_masa_id, $_SESSION['personel_id'], $aciklama]);
                
                // Birleşen masanın siparişlerini ana masaya transfer et
                $stmt = $db->prepare("UPDATE siparis SET masa_id = ? WHERE masa_id = ? AND odeme_durumu != 'odendi'");
                $stmt->execute([$ana_masa_id, $birlesen_masa_id]);
                
                // Birleşen masayı boşalt
                $stmt = $db->prepare("UPDATE masa SET durum = 'bos' WHERE id = ?");
                $stmt->execute([$birlesen_masa_id]);
                
                require_once '../includes/log_activity.php';
                logActivity($db, 'masa_birles', 'masa_birlesim', $db->lastInsertId(), "Masa birleştirme: $ana_masa_id + $birlesen_masa_id");
                
                $db->commit();
                @ob_clean();
                echo json_encode(['success' => true, 'message' => 'Masalar başarıyla birleştirildi']);
            } catch(Exception $e) {
                $db->rollBack();
                error_log("Masa birleştirme hatası: " . $e->getMessage());
                @ob_clean();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    case 'transfer':
        if ($method == 'POST') {
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception('Geçersiz veri formatı');
                }
                
                $eski_masa_id = intval($data['eski_masa_id'] ?? 0);
                $yeni_masa_id = intval($data['yeni_masa_id'] ?? 0);
                $siparis_id = intval($data['siparis_id'] ?? 0);
                
                if ($eski_masa_id <= 0 || $yeni_masa_id <= 0 || $siparis_id <= 0) {
                    throw new Exception('Geçersiz masa veya sipariş ID');
                }
                
                if ($eski_masa_id == $yeni_masa_id) {
                    throw new Exception('Aynı masaya transfer edilemez');
                }
                
                $aciklama = cleanInput($data['aciklama'] ?? '');
                
                $db->beginTransaction();
                
                // Siparişin mevcut masasını kontrol et
                $stmt = $db->prepare("SELECT masa_id FROM siparis WHERE id = ?");
                $stmt->execute([$siparis_id]);
                $siparis = $stmt->fetch();
                
                if (!$siparis) {
                    throw new Exception('Sipariş bulunamadı');
                }
                
                if ($siparis['masa_id'] != $eski_masa_id) {
                    throw new Exception('Sipariş bu masaya ait değil');
                }
                
                // Transfer kaydı oluştur
                $stmt = $db->prepare("INSERT INTO masa_transfer (eski_masa_id, yeni_masa_id, siparis_id, personel_id, aciklama) 
                                     VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$eski_masa_id, $yeni_masa_id, $siparis_id, $_SESSION['personel_id'], $aciklama]);
                
                // Siparişi yeni masaya transfer et
                $stmt = $db->prepare("UPDATE siparis SET masa_id = ? WHERE id = ?");
                $stmt->execute([$yeni_masa_id, $siparis_id]);
                
                // Eski masayı kontrol et (başka sipariş var mı?)
                $stmt = $db->prepare("SELECT COUNT(*) as sayi FROM siparis WHERE masa_id = ? AND odeme_durumu != 'odendi'");
                $stmt->execute([$eski_masa_id]);
                $eski_masa_siparis = $stmt->fetch();
                
                if ($eski_masa_siparis['sayi'] == 0) {
                    $stmt = $db->prepare("UPDATE masa SET durum = 'bos' WHERE id = ?");
                    $stmt->execute([$eski_masa_id]);
                }
                
                // Yeni masayı dolu yap
                $stmt = $db->prepare("UPDATE masa SET durum = 'dolu' WHERE id = ?");
                $stmt->execute([$yeni_masa_id]);
                
                require_once '../includes/log_activity.php';
                logActivity($db, 'masa_transfer', 'masa_transfer', $db->lastInsertId(), "Masa transfer: $eski_masa_id -> $yeni_masa_id");
                
                $db->commit();
                @ob_clean();
                echo json_encode(['success' => true, 'message' => 'Sipariş başarıyla transfer edildi']);
            } catch(Exception $e) {
                $db->rollBack();
                error_log("Masa transfer hatası: " . $e->getMessage());
                @ob_clean();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    default:
        @ob_clean();
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        break;
}

@ob_end_flush();
?>


