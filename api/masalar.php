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

switch($method) {
    case 'GET':
        try {
            if (isset($_GET['id'])) {
                // Tek masa getir
                $id = intval($_GET['id']);
                $stmt = $db->prepare("SELECT * FROM masa WHERE id = ?");
                $stmt->execute([$id]);
                $masa = $stmt->fetch();
                @ob_clean();
                echo json_encode($masa ?: null);
            } else {
                // Tüm masaları getir
                $stmt = $db->query("SELECT * FROM masa ORDER BY masa_no");
                $masalar = $stmt->fetchAll();
                @ob_clean();
                echo json_encode($masalar);
            }
        } catch(Exception $e) {
            error_log("Masalar listeleme hatası: " . $e->getMessage());
            @ob_clean();
            echo json_encode(['success' => false, 'message' => 'Masalar yüklenirken hata oluştu']);
        }
        break;
        
    case 'POST':
        checkRoleAPI(['admin']); // Sadece admin masa ekleyebilir
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Geçersiz veri formatı');
            }
            
            $masa_no = cleanInput($data['masa_no'] ?? '');
            if (empty($masa_no)) {
                throw new Exception('Masa numarası gereklidir');
            }
            
            $kapasite = intval($data['kapasite'] ?? 0);
            if ($kapasite <= 0) {
                throw new Exception('Kapasite 0\'dan büyük olmalıdır');
            }
            
            $konum = cleanInput($data['konum'] ?? '');
            $durum = cleanInput($data['durum'] ?? 'bos');
            
            $stmt = $db->prepare("INSERT INTO masa (masa_no, kapasite, konum, durum) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$masa_no, $kapasite, $konum, $durum])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'masa_ekle', 'masa', $db->lastInsertId(), "Masa eklendi: $masa_no");
                @ob_clean();
                echo json_encode(['success' => true, 'message' => 'Masa eklendi']);
            } else {
                throw new Exception('Veritabanı hatası');
            }
        } catch(Exception $e) {
            error_log("Masa ekleme hatası: " . $e->getMessage());
            @ob_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        checkRoleAPI(['admin']); // Sadece admin masa güncelleyebilir
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Geçersiz veri formatı');
            }
            
            $id = intval($data['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Geçersiz masa ID');
            }
            
            $masa_no = cleanInput($data['masa_no'] ?? '');
            if (empty($masa_no)) {
                throw new Exception('Masa numarası gereklidir');
            }
            
            $kapasite = intval($data['kapasite'] ?? 0);
            if ($kapasite <= 0) {
                throw new Exception('Kapasite 0\'dan büyük olmalıdır');
            }
            
            $konum = cleanInput($data['konum'] ?? '');
            $durum = cleanInput($data['durum'] ?? 'bos');
            
            $stmt = $db->prepare("UPDATE masa SET masa_no = ?, kapasite = ?, konum = ?, durum = ? WHERE id = ?");
            if ($stmt->execute([$masa_no, $kapasite, $konum, $durum, $id])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'masa_guncelle', 'masa', $id, "Masa güncellendi: $masa_no");
                @ob_clean();
                echo json_encode(['success' => true, 'message' => 'Masa güncellendi']);
            } else {
                throw new Exception('Veritabanı hatası');
            }
        } catch(Exception $e) {
            error_log("Masa güncelleme hatası: " . $e->getMessage());
            @ob_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        checkRoleAPI(['admin']); // Sadece admin masa silebilir
        try {
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Geçersiz masa ID');
            }
            
            // Masa üzerinde aktif sipariş var mı kontrol et
            $stmt = $db->prepare("SELECT COUNT(*) as sayi FROM siparis WHERE masa_id = ? AND odeme_durumu != 'odendi'");
            $stmt->execute([$id]);
            $siparis = $stmt->fetch();
            
            if ($siparis['sayi'] > 0) {
                throw new Exception('Bu masada aktif siparişler bulunmaktadır. Önce siparişleri tamamlayın.');
            }
            
            $stmt = $db->prepare("DELETE FROM masa WHERE id = ?");
            if ($stmt->execute([$id])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'masa_sil', 'masa', $id, 'Masa silindi');
                @ob_clean();
                echo json_encode(['success' => true, 'message' => 'Masa silindi']);
            } else {
                throw new Exception('Veritabanı hatası');
            }
        } catch(Exception $e) {
            error_log("Masa silme hatası: " . $e->getMessage());
            @ob_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    default:
        @ob_clean();
        echo json_encode(['success' => false, 'message' => 'Geçersiz HTTP metodu']);
        break;
}

@ob_end_flush();
?>

