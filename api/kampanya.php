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
checkRoleAPI(['admin']);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($action) {
    case 'listele':
        $arama = isset($_GET['arama']) ? cleanInput($_GET['arama']) : '';
        $durum = isset($_GET['durum']) ? cleanInput($_GET['durum']) : '';
        
        $sql = "SELECT * FROM kampanyalar WHERE 1=1";
        $params = [];
        
        if ($arama) {
            $sql .= " AND (kampanya_adi LIKE ? OR aciklama LIKE ?)";
            $params[] = "%$arama%";
            $params[] = "%$arama%";
        }
        
        if ($durum) {
            if ($durum === 'bitmis') {
                $sql .= " AND bitis_tarihi < CURDATE()";
            } else {
                $sql .= " AND durum = ?";
                $params[] = $durum;
            }
        }
        
        $sql .= " ORDER BY baslangic_tarihi DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        @ob_clean();
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'getir':
        $id = intval($_GET['id']);
        $stmt = $db->prepare("SELECT * FROM kampanyalar WHERE id = ?");
        $stmt->execute([$id]);
        @ob_clean();
        echo json_encode($stmt->fetch());
        break;
        
    case 'ekle':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $kampanya_adi = cleanInput($data['kampanya_adi']);
            $baslangic_tarihi = cleanInput($data['baslangic_tarihi']);
            $bitis_tarihi = cleanInput($data['bitis_tarihi']);
            $indirim_tipi = cleanInput($data['indirim_tipi']);
            $indirim_degeri = floatval($data['indirim_degeri']);
            $min_tutar = floatval($data['min_tutar'] ?? 0);
            $urun_id = isset($data['urun_id']) && $data['urun_id'] ? intval($data['urun_id']) : null;
            $kategori_id = isset($data['kategori_id']) && $data['kategori_id'] ? intval($data['kategori_id']) : null;
            $aciklama = cleanInput($data['aciklama'] ?? '');
            $durum = cleanInput($data['durum'] ?? 'aktif');
            
            $stmt = $db->prepare("INSERT INTO kampanyalar (kampanya_adi, baslangic_tarihi, bitis_tarihi, indirim_tipi, indirim_degeri, min_tutar, urun_id, kategori_id, aciklama, durum) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$kampanya_adi, $baslangic_tarihi, $bitis_tarihi, $indirim_tipi, $indirim_degeri, $min_tutar, $urun_id, $kategori_id, $aciklama, $durum])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'kampanya_ekle', 'kampanyalar', $db->lastInsertId(), "Kampanya eklendi: $kampanya_adi");
                    @ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Kampanya eklendi']);
            } else {
                @ob_clean();
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
            }
        }
        break;
        
    case 'guncelle':
        if ($method == 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id']);
            
            $kampanya_adi = cleanInput($data['kampanya_adi']);
            $baslangic_tarihi = cleanInput($data['baslangic_tarihi']);
            $bitis_tarihi = cleanInput($data['bitis_tarihi']);
            $indirim_tipi = cleanInput($data['indirim_tipi']);
            $indirim_degeri = floatval($data['indirim_degeri']);
            $min_tutar = floatval($data['min_tutar'] ?? 0);
            $urun_id = isset($data['urun_id']) && $data['urun_id'] ? intval($data['urun_id']) : null;
            $kategori_id = isset($data['kategori_id']) && $data['kategori_id'] ? intval($data['kategori_id']) : null;
            $aciklama = cleanInput($data['aciklama'] ?? '');
            $durum = cleanInput($data['durum'] ?? 'aktif');
            
            $stmt = $db->prepare("UPDATE kampanyalar SET kampanya_adi = ?, baslangic_tarihi = ?, bitis_tarihi = ?, indirim_tipi = ?, 
                                 indirim_degeri = ?, min_tutar = ?, urun_id = ?, kategori_id = ?, aciklama = ?, durum = ? WHERE id = ?");
            if ($stmt->execute([$kampanya_adi, $baslangic_tarihi, $bitis_tarihi, $indirim_tipi, $indirim_degeri, $min_tutar, $urun_id, $kategori_id, $aciklama, $durum, $id])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'kampanya_guncelle', 'kampanyalar', $id, "Kampanya güncellendi: $kampanya_adi");
                    @ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Kampanya güncellendi']);
            } else {
                @ob_clean();
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
            }
        }
        break;
        
    case 'sil':
        if ($method == 'DELETE') {
            $id = intval($_GET['id']);
            $stmt = $db->prepare("DELETE FROM kampanyalar WHERE id = ?");
            if ($stmt->execute([$id])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'kampanya_sil', 'kampanyalar', $id, 'Kampanya silindi');
                @ob_clean();
                echo json_encode(['success' => true, 'message' => 'Kampanya silindi']);
            } else {
                @ob_clean();
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
            }
        }
        break;
        
    case 'aktif_kampanyalar':
        $tutar = floatval($_GET['tutar'] ?? 0);
        $urun_id = isset($_GET['urun_id']) ? intval($_GET['urun_id']) : null;
        $kategori_id = isset($_GET['kategori_id']) ? intval($_GET['kategori_id']) : null;
        
        $sql = "SELECT * FROM kampanyalar WHERE durum = 'aktif' 
                AND baslangic_tarihi <= CURDATE() AND bitis_tarihi >= CURDATE()";
        
        $params = [];
        
        if ($tutar > 0) {
            $sql .= " AND (min_tutar = 0 OR min_tutar <= ?)";
            $params[] = $tutar;
        }
        
        if ($urun_id) {
            $sql .= " AND (urun_id IS NULL OR urun_id = ?)";
            $params[] = $urun_id;
        }
        
        if ($kategori_id) {
            $sql .= " AND (kategori_id IS NULL OR kategori_id = ?)";
            $params[] = $kategori_id;
        }
        
        $sql .= " ORDER BY indirim_degeri DESC LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $kampanya = $stmt->fetch();
        
        @ob_clean();
        echo json_encode($kampanya ?: null);
        break;
        
    default:
        @ob_clean();
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        break;
}

@ob_end_flush();
?>


