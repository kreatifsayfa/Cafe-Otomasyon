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

switch($action) {
    case 'listele':
        try {
            $arama = isset($_GET['arama']) ? cleanInput($_GET['arama']) : '';
            $sql = "SELECT * FROM musteri WHERE 1=1";
            $params = [];
            
            if ($arama) {
                $sql .= " AND (ad_soyad LIKE ? OR telefon LIKE ? OR email LIKE ?)";
                $params[] = "%$arama%";
                $params[] = "%$arama%";
                $params[] = "%$arama%";
            }
            
            $sql .= " ORDER BY ad_soyad LIMIT 100";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            @ob_clean();
            echo json_encode($stmt->fetchAll());
        } catch(Exception $e) {
            error_log("Müşteri listeleme hatası: " . $e->getMessage());
            @ob_clean();
            echo json_encode(['success' => false, 'message' => 'Müşteriler yüklenirken hata oluştu']);
        }
        break;
        
    case 'getir':
        try {
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Geçersiz müşteri ID');
            }
            
            $stmt = $db->prepare("SELECT * FROM musteri WHERE id = ?");
            $stmt->execute([$id]);
            $musteri = $stmt->fetch();
            @ob_clean();
            echo json_encode($musteri ?: null);
        } catch(Exception $e) {
            error_log("Müşteri getirme hatası: " . $e->getMessage());
            @ob_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'ekle':
        if ($method == 'POST') {
            checkRoleAPI(['admin', 'kasiyer', 'garson']); // Müşteri ekleme yetkisi
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception('Geçersiz veri formatı');
                }
                
                $ad_soyad = cleanInput($data['ad_soyad'] ?? '');
                if (empty($ad_soyad)) {
                    throw new Exception('Ad soyad gereklidir');
                }
                
                $telefon = cleanInput($data['telefon'] ?? '');
                $email = cleanInput($data['email'] ?? '');
                $adres = cleanInput($data['adres'] ?? '');
                $dogum_tarihi = !empty($data['dogum_tarihi']) ? $data['dogum_tarihi'] : null;
                
                $stmt = $db->prepare("INSERT INTO musteri (ad_soyad, telefon, email, adres, dogum_tarihi) 
                                     VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$ad_soyad, $telefon, $email, $adres, $dogum_tarihi])) {
                    require_once '../includes/log_activity.php';
                    logActivity($db, 'musteri_ekle', 'musteri', $db->lastInsertId(), "$ad_soyad eklendi");
                    @ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Müşteri eklendi', 'id' => $db->lastInsertId()]);
                } else {
                    throw new Exception('Veritabanı hatası');
                }
            } catch(Exception $e) {
                error_log("Müşteri ekleme hatası: " . $e->getMessage());
                @ob_clean();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    case 'guncelle':
        if ($method == 'PUT') {
            checkRoleAPI(['admin', 'kasiyer']); // Müşteri güncelleme yetkisi
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception('Geçersiz veri formatı');
                }
                
                $id = intval($data['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Geçersiz müşteri ID');
                }
                
                $ad_soyad = cleanInput($data['ad_soyad'] ?? '');
                if (empty($ad_soyad)) {
                    throw new Exception('Ad soyad gereklidir');
                }
                
                $telefon = cleanInput($data['telefon'] ?? '');
                $email = cleanInput($data['email'] ?? '');
                $adres = cleanInput($data['adres'] ?? '');
                $dogum_tarihi = !empty($data['dogum_tarihi']) ? $data['dogum_tarihi'] : null;
                
                $stmt = $db->prepare("UPDATE musteri SET ad_soyad = ?, telefon = ?, email = ?, adres = ?, dogum_tarihi = ? WHERE id = ?");
                if ($stmt->execute([$ad_soyad, $telefon, $email, $adres, $dogum_tarihi, $id])) {
                    require_once '../includes/log_activity.php';
                    logActivity($db, 'musteri_guncelle', 'musteri', $id, "$ad_soyad güncellendi");
                    @ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Müşteri güncellendi']);
                } else {
                    throw new Exception('Veritabanı hatası');
                }
            } catch(Exception $e) {
                error_log("Müşteri güncelleme hatası: " . $e->getMessage());
                @ob_clean();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    case 'puan_ekle':
        if ($method == 'POST') {
            checkRoleAPI(['admin', 'kasiyer']); // Puan ekleme yetkisi
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception('Geçersiz veri formatı');
                }
                
                $musteri_id = intval($data['musteri_id'] ?? 0);
                if ($musteri_id <= 0) {
                    throw new Exception('Geçersiz müşteri ID');
                }
                
                $puan = intval($data['puan'] ?? 0);
                $aciklama = cleanInput($data['aciklama'] ?? '');
                $siparis_id = isset($data['siparis_id']) ? intval($data['siparis_id']) : null;
                
                $db->beginTransaction();
                
                // Puan geçmişine ekle
                $stmt = $db->prepare("INSERT INTO musteri_puan_gecmis (musteri_id, puan, aciklama, siparis_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$musteri_id, $puan, $aciklama, $siparis_id]);
                
                // Müşteri puanını güncelle
                $stmt = $db->prepare("UPDATE musteri SET puan = puan + ? WHERE id = ?");
                $stmt->execute([$puan, $musteri_id]);
                
                $db->commit();
                @ob_clean();
                echo json_encode(['success' => true, 'message' => 'Puan eklendi']);
            } catch(Exception $e) {
                $db->rollBack();
                error_log("Puan ekleme hatası: " . $e->getMessage());
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

@ob_end_clean();
?>


