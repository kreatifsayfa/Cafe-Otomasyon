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
    case 'kategoriler':
        // Kategorileri listeleme için admin kontrolü yok, herkes görebilir
        $stmt = $db->query("SELECT * FROM menu_kategori WHERE durum = 'aktif' ORDER BY sira");
        @ob_clean();
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'urunler':
        // Ürünleri listeleme için admin kontrolü yok, herkes görebilir
        $kategori_id = isset($_GET['kategori_id']) ? intval($_GET['kategori_id']) : 0;
        if ($kategori_id > 0) {
            $stmt = $db->prepare("SELECT u.*, k.kategori_adi FROM menu_urun u 
                                  JOIN menu_kategori k ON u.kategori_id = k.id 
                                  WHERE u.kategori_id = ? AND u.durum = 'aktif' ORDER BY u.sira");
            $stmt->execute([$kategori_id]);
        } else {
            $stmt = $db->query("SELECT u.*, k.kategori_adi FROM menu_urun u 
                               JOIN menu_kategori k ON u.kategori_id = k.id 
                               WHERE u.durum = 'aktif' ORDER BY k.sira, u.sira");
        }
        @ob_clean();
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'urun':
    case 'urun_getir':
        $id = intval($_GET['id']);
        $stmt = $db->prepare("SELECT u.*, k.kategori_adi FROM menu_urun u 
                             JOIN menu_kategori k ON u.kategori_id = k.id 
                             WHERE u.id = ?");
        $stmt->execute([$id]);
        @ob_clean();
        echo json_encode($stmt->fetch());
        break;
        
    case 'kategori_getir':
        $id = intval($_GET['id']);
        $stmt = $db->prepare("SELECT * FROM menu_kategori WHERE id = ?");
        $stmt->execute([$id]);
        @ob_clean();
        echo json_encode($stmt->fetch());
        break;
        
    case 'kategori_ekle':
        checkRoleAPI(['admin']); // Sadece admin kategori ekleyebilir
        if ($method == 'POST') {
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception('Geçersiz veri formatı');
                }
                
                $kategori_adi = cleanInput($data['kategori_adi'] ?? '');
                if (empty($kategori_adi)) {
                    throw new Exception('Kategori adı gereklidir');
                }
                
                $aciklama = cleanInput($data['aciklama'] ?? '');
                
                $stmt = $db->prepare("INSERT INTO menu_kategori (kategori_adi, aciklama) VALUES (?, ?)");
                if ($stmt->execute([$kategori_adi, $aciklama])) {
                    require_once '../includes/log_activity.php';
                    logActivity($db, 'kategori_ekle', 'menu_kategori', $db->lastInsertId(), "Kategori eklendi: $kategori_adi");
                    @ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Kategori eklendi']);
                } else {
                    throw new Exception('Veritabanı hatası');
                }
            } catch(Exception $e) {
                error_log("Kategori ekleme hatası: " . $e->getMessage());
                @ob_clean();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            @ob_clean();
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu']);
        }
        break;
        
    case 'kategori_guncelle':
        checkRoleAPI(['admin']); // Sadece admin kategori güncelleyebilir
        if ($method == 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id']);
            $kategori_adi = cleanInput($data['kategori_adi']);
            $aciklama = cleanInput($data['aciklama'] ?? '');
            
            $stmt = $db->prepare("UPDATE menu_kategori SET kategori_adi = ?, aciklama = ? WHERE id = ?");
            if ($stmt->execute([$kategori_adi, $aciklama, $id])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'kategori_guncelle', 'menu_kategori', $id, "Kategori güncellendi: $kategori_adi");
                    @ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Kategori güncellendi']);
            } else {
                @ob_clean();
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
            }
        }
        break;
        
    case 'kategori_sil':
        checkRoleAPI(['admin']); // Sadece admin kategori silebilir
        if ($method == 'DELETE') {
            $id = intval($_GET['id']);
            $stmt = $db->prepare("UPDATE menu_kategori SET durum = 'pasif' WHERE id = ?");
            if ($stmt->execute([$id])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'kategori_sil', 'menu_kategori', $id, 'Kategori silindi');
                echo json_encode(['success' => true, 'message' => 'Kategori silindi']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
            }
        }
        break;
        
    case 'urun_ekle':
        checkRoleAPI(['admin']); // Sadece admin ürün ekleyebilir
        if ($method == 'POST') {
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception('Geçersiz veri formatı');
                }
                
                $kategori_id = intval($data['kategori_id'] ?? 0);
                if ($kategori_id <= 0) {
                    throw new Exception('Kategori seçimi gereklidir');
                }
                
                $urun_adi = cleanInput($data['urun_adi'] ?? '');
                if (empty($urun_adi)) {
                    throw new Exception('Ürün adı gereklidir');
                }
                
                $aciklama = cleanInput($data['aciklama'] ?? '');
                $fiyat = floatval($data['fiyat'] ?? 0);
                if ($fiyat <= 0) {
                    throw new Exception('Fiyat 0\'dan büyük olmalıdır');
                }
                
                $stok_var_mi = isset($data['stok_var_mi']) && $data['stok_var_mi'] ? 1 : 0;
                $stok_miktari = intval($data['stok_miktari'] ?? 0);
                
                $stmt = $db->prepare("INSERT INTO menu_urun (kategori_id, urun_adi, aciklama, fiyat, stok_var_mi, stok_miktari) 
                                     VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$kategori_id, $urun_adi, $aciklama, $fiyat, $stok_var_mi, $stok_miktari])) {
                    require_once '../includes/log_activity.php';
                    logActivity($db, 'urun_ekle', 'menu_urun', $db->lastInsertId(), "Ürün eklendi: $urun_adi");
                    echo json_encode(['success' => true, 'message' => 'Ürün eklendi']);
                } else {
                    throw new Exception('Veritabanı hatası');
                }
            } catch(Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu']);
        }
        break;
        
    case 'urun_guncelle':
        checkRoleAPI(['admin']); // Sadece admin ürün güncelleyebilir
        if ($method == 'PUT') {
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception('Geçersiz veri formatı');
                }
                
                $id = intval($data['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Geçersiz ürün ID');
                }
                
                $kategori_id = intval($data['kategori_id'] ?? 0);
                if ($kategori_id <= 0) {
                    throw new Exception('Kategori seçimi gereklidir');
                }
                
                $urun_adi = cleanInput($data['urun_adi'] ?? '');
                if (empty($urun_adi)) {
                    throw new Exception('Ürün adı gereklidir');
                }
                
                $aciklama = cleanInput($data['aciklama'] ?? '');
                $fiyat = floatval($data['fiyat'] ?? 0);
                if ($fiyat <= 0) {
                    throw new Exception('Fiyat 0\'dan büyük olmalıdır');
                }
                
                $stok_var_mi = isset($data['stok_var_mi']) && $data['stok_var_mi'] ? 1 : 0;
                $stok_miktari = intval($data['stok_miktari'] ?? 0);
                
                $stmt = $db->prepare("UPDATE menu_urun SET kategori_id = ?, urun_adi = ?, aciklama = ?, 
                                     fiyat = ?, stok_var_mi = ?, stok_miktari = ? WHERE id = ?");
                if ($stmt->execute([$kategori_id, $urun_adi, $aciklama, $fiyat, $stok_var_mi, $stok_miktari, $id])) {
                    require_once '../includes/log_activity.php';
                    logActivity($db, 'urun_guncelle', 'menu_urun', $id, "Ürün güncellendi: $urun_adi");
                    echo json_encode(['success' => true, 'message' => 'Ürün güncellendi']);
                } else {
                    throw new Exception('Veritabanı hatası');
                }
            } catch(Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu']);
        }
        break;
        
    case 'urun_sil':
        checkRoleAPI(['admin']); // Sadece admin ürün silebilir
        if ($method == 'DELETE') {
            $id = intval($_GET['id']);
            $stmt = $db->prepare("UPDATE menu_urun SET durum = 'pasif' WHERE id = ?");
            if ($stmt->execute([$id])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'urun_sil', 'menu_urun', $id, 'Ürün silindi');
                echo json_encode(['success' => true, 'message' => 'Ürün silindi']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
            }
        }
        break;
}
?>

