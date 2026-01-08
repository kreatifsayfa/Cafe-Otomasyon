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
        $sql = "SELECT * FROM stok WHERE 1=1";
        $params = [];
        
        if ($arama) {
            $sql .= " AND malzeme_adi LIKE ?";
            $params[] = "%$arama%";
        }
        
        $sql .= " ORDER BY malzeme_adi";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        @ob_clean();
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'getir':
        $id = intval($_GET['id']);
        $stmt = $db->prepare("SELECT * FROM stok WHERE id = ?");
        $stmt->execute([$id]);
        @ob_clean();
        echo json_encode($stmt->fetch());
        break;
        
    case 'ekle':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $malzeme_adi = cleanInput($data['malzeme_adi']);
            $miktar = floatval($data['miktar']);
            $birim = cleanInput($data['birim'] ?? 'kg');
            $minimum_stok = floatval($data['minimum_stok'] ?? 0);
            $tedarikci = cleanInput($data['tedarikci'] ?? '');
            $son_alim_fiyati = floatval($data['son_alim_fiyati'] ?? 0);
            
            $stmt = $db->prepare("INSERT INTO stok (malzeme_adi, miktar, birim, minimum_stok, tedarikci, son_alim_fiyati) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$malzeme_adi, $miktar, $birim, $minimum_stok, $tedarikci, $son_alim_fiyati])) {
                $stok_id = $db->lastInsertId();
                
                // Stok hareketi kaydet
                $stmt = $db->prepare("INSERT INTO stok_hareket (stok_id, hareket_tipi, miktar, birim_fiyat, personel_id, aciklama) 
                                     VALUES (?, 'giris', ?, ?, ?, 'İlk stok girişi')");
                $stmt->execute([$stok_id, $miktar, $son_alim_fiyati, $_SESSION['personel_id']]);
                
                // Aktivite log
                require_once '../includes/log_activity.php';
                logActivity($db, 'stok_ekle', 'stok', $stok_id, "$malzeme_adi eklendi");
                
                    @ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Stok eklendi']);
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
            $malzeme_adi = cleanInput($data['malzeme_adi']);
            $miktar = floatval($data['miktar']);
            $birim = cleanInput($data['birim'] ?? 'kg');
            $minimum_stok = floatval($data['minimum_stok'] ?? 0);
            $tedarikci = cleanInput($data['tedarikci'] ?? '');
            
            $stmt = $db->prepare("UPDATE stok SET malzeme_adi = ?, miktar = ?, birim = ?, minimum_stok = ?, tedarikci = ? WHERE id = ?");
            if ($stmt->execute([$malzeme_adi, $miktar, $birim, $minimum_stok, $tedarikci, $id])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'stok_guncelle', 'stok', $id, "$malzeme_adi güncellendi");
                    @ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Stok güncellendi']);
            } else {
                @ob_clean();
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
            }
        }
        break;
        
    case 'sil':
        if ($method == 'DELETE') {
            $id = intval($_GET['id']);
            $stmt = $db->prepare("DELETE FROM stok WHERE id = ?");
            if ($stmt->execute([$id])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'stok_sil', 'stok', $id, 'Stok silindi');
                @ob_clean();
                echo json_encode(['success' => true, 'message' => 'Stok silindi']);
            } else {
                @ob_clean();
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
            }
        }
        break;
        
    case 'hareket_ekle':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $stok_id = intval($data['stok_id']);
            $hareket_tipi = cleanInput($data['hareket_tipi']);
            $miktar = floatval($data['miktar']);
            $birim_fiyat = floatval($data['birim_fiyat'] ?? 0);
            $tedarikci_id = isset($data['tedarikci_id']) ? intval($data['tedarikci_id']) : null;
            $aciklama = cleanInput($data['aciklama'] ?? '');
            
            try {
                $db->beginTransaction();
                
                // Stok hareketi ekle
                $stmt = $db->prepare("INSERT INTO stok_hareket (stok_id, hareket_tipi, miktar, birim_fiyat, tedarikci_id, personel_id, aciklama) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$stok_id, $hareket_tipi, $miktar, $birim_fiyat, $tedarikci_id, $_SESSION['personel_id'], $aciklama]);
                
                // Stok miktarını güncelle
                if ($hareket_tipi == 'giris') {
                    $stmt = $db->prepare("UPDATE stok SET miktar = miktar + ?, son_alim_tarihi = CURDATE(), son_alim_fiyati = ? WHERE id = ?");
                } elseif ($hareket_tipi == 'cikis') {
                    $stmt = $db->prepare("UPDATE stok SET miktar = miktar - ? WHERE id = ?");
                    $stmt->execute([$miktar, $stok_id]);
                } else {
                    $stmt = $db->prepare("UPDATE stok SET miktar = ? WHERE id = ?");
                    $stmt->execute([$miktar, $stok_id]);
                }
                
                if ($hareket_tipi == 'giris') {
                    $stmt->execute([$miktar, $birim_fiyat, $stok_id]);
                }
                
                $db->commit();
                
                require_once '../includes/log_activity.php';
                logActivity($db, 'stok_hareket', 'stok_hareket', $db->lastInsertId(), "Stok hareketi: $hareket_tipi");
                
                    @ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Stok hareketi eklendi']);
            } catch(Exception $e) {
                $db->rollBack();
                error_log("Stok hareket ekleme hatası: " . $e->getMessage());
                @ob_clean();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    case 'uyarilar':
        $stmt = $db->query("SELECT * FROM stok WHERE miktar <= minimum_stok ORDER BY miktar ASC");
        @ob_clean();
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'hareketler':
        $stok_id = isset($_GET['stok_id']) ? intval($_GET['stok_id']) : 0;
        if ($stok_id > 0) {
            $stmt = $db->prepare("SELECT sh.*, p.ad_soyad as personel_adi, t.firma_adi as tedarikci_adi 
                                 FROM stok_hareket sh
                                 LEFT JOIN personel p ON sh.personel_id = p.id
                                 LEFT JOIN tedarikci t ON sh.tedarikci_id = t.id
                                 WHERE sh.stok_id = ? ORDER BY sh.olusturma_tarihi DESC");
            $stmt->execute([$stok_id]);
        } else {
            $stmt = $db->query("SELECT sh.*, s.malzeme_adi, p.ad_soyad as personel_adi, t.firma_adi as tedarikci_adi 
                               FROM stok_hareket sh
                               JOIN stok s ON sh.stok_id = s.id
                               LEFT JOIN personel p ON sh.personel_id = p.id
                               LEFT JOIN tedarikci t ON sh.tedarikci_id = t.id
                               ORDER BY sh.olusturma_tarihi DESC LIMIT 50");
        }
        @ob_clean();
        echo json_encode($stmt->fetchAll());
        break;
        
    default:
        @ob_clean();
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        break;
}

@ob_end_flush();
?>


