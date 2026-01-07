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
        $personel_id = $_SESSION['personel_id'];
        $okunmamis = isset($_GET['okunmamis']) && $_GET['okunmamis'] == '1';
        
        $sql = "SELECT * FROM bildirim WHERE personel_id = ? OR personel_id IS NULL";
        if ($okunmamis) {
            $sql .= " AND okundu = FALSE";
        }
        $sql .= " ORDER BY olusturma_tarihi DESC LIMIT 50";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$personel_id]);
        @ob_clean();
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'okundu':
        if ($method == 'POST') {
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception('Geçersiz veri formatı');
                }
                
                $id = intval($data['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Geçersiz bildirim ID');
                }
                
                $stmt = $db->prepare("UPDATE bildirim SET okundu = TRUE WHERE id = ?");
                $stmt->execute([$id]);
                @ob_clean();
                echo json_encode(['success' => true]);
            } catch(Exception $e) {
                error_log("Bildirim okundu işaretleme hatası: " . $e->getMessage());
                @ob_clean();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    case 'okundu_tumu':
        if ($method == 'POST') {
            try {
                $personel_id = $_SESSION['personel_id'];
                $stmt = $db->prepare("UPDATE bildirim SET okundu = TRUE WHERE (personel_id = ? OR personel_id IS NULL) AND okundu = FALSE");
                $stmt->execute([$personel_id]);
                @ob_clean();
                echo json_encode(['success' => true]);
            } catch(Exception $e) {
                error_log("Tüm bildirimleri okundu işaretleme hatası: " . $e->getMessage());
                @ob_clean();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    case 'say':
        try {
            $personel_id = $_SESSION['personel_id'];
            $stmt = $db->prepare("SELECT COUNT(*) as sayi FROM bildirim WHERE (personel_id = ? OR personel_id IS NULL) AND okundu = FALSE");
            $stmt->execute([$personel_id]);
            $result = $stmt->fetch();
            @ob_clean();
            echo json_encode(['sayi' => $result['sayi']]);
        } catch(Exception $e) {
            error_log("Bildirim sayma hatası: " . $e->getMessage());
            @ob_clean();
            echo json_encode(['success' => false, 'sayi' => 0]);
        }
        break;
        
    case 'olustur':
        if ($method == 'POST') {
            checkRoleAPI(['admin']);
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception('Geçersiz veri formatı');
                }
                
                $personel_id = isset($data['personel_id']) ? intval($data['personel_id']) : null;
                $baslik = cleanInput($data['baslik'] ?? '');
                if (empty($baslik)) {
                    throw new Exception('Başlık gereklidir');
                }
                
                $mesaj = cleanInput($data['mesaj'] ?? '');
                if (empty($mesaj)) {
                    throw new Exception('Mesaj gereklidir');
                }
                
                $tip = cleanInput($data['tip'] ?? 'info');
                $link = cleanInput($data['link'] ?? '');
                
                $stmt = $db->prepare("INSERT INTO bildirim (personel_id, baslik, mesaj, tip, link) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$personel_id, $baslik, $mesaj, $tip, $link]);
                @ob_clean();
                echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            } catch(Exception $e) {
                error_log("Bildirim oluşturma hatası: " . $e->getMessage());
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


