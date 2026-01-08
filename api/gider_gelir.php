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
$tip = $_GET['tip'] ?? '';

switch($action) {
    case 'listele':
        $tipFiltre = isset($_GET['tip']) ? cleanInput($_GET['tip']) : '';
        $baslangic = isset($_GET['baslangic']) ? cleanInput($_GET['baslangic']) : '';
        $bitis = isset($_GET['bitis']) ? cleanInput($_GET['bitis']) : '';
        
        $sql = "SELECT 'gider' as tip, g.*, p.ad_soyad as personel_adi 
                FROM giderler g 
                LEFT JOIN personel p ON g.personel_id = p.id 
                WHERE 1=1";
        $params = [];
        
        if ($baslangic && $bitis) {
            $sql .= " AND g.tarih BETWEEN ? AND ?";
            $params[] = $baslangic;
            $params[] = $bitis;
        }
        
        $sql .= " UNION ALL 
                SELECT 'gelir' as tip, ge.*, p2.ad_soyad as personel_adi 
                FROM gelirler ge 
                LEFT JOIN personel p2 ON ge.personel_id = p2.id 
                WHERE 1=1";
        
        if ($baslangic && $bitis) {
            $sql .= " AND ge.tarih BETWEEN ? AND ?";
            $params[] = $baslangic;
            $params[] = $bitis;
        }
        
        $sql .= " ORDER BY tarih DESC, id DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        if ($tipFiltre) {
            $results = array_filter($results, function($r) use ($tipFiltre) {
                return $r['tip'] === $tipFiltre;
            });
        }
        
        @ob_clean();
        echo json_encode(array_values($results));
        break;
        
    case 'getir':
        $id = intval($_GET['id']);
        if ($tip === 'gider') {
            $stmt = $db->prepare("SELECT * FROM giderler WHERE id = ?");
        } else {
            $stmt = $db->prepare("SELECT * FROM gelirler WHERE id = ?");
        }
        $stmt->execute([$id]);
        @ob_clean();
        echo json_encode($stmt->fetch());
        break;
        
    case 'ekle':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($tip === 'gider') {
                $gider_tipi = cleanInput($data['gider_tipi']);
                $kategori = cleanInput($data['kategori'] ?? '');
                $tutar = floatval($data['tutar']);
                $tarih = cleanInput($data['tarih']);
                $aciklama = cleanInput($data['aciklama'] ?? '');
                
                $stmt = $db->prepare("INSERT INTO giderler (gider_tipi, kategori, tutar, tarih, aciklama, personel_id) 
                                     VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$gider_tipi, $kategori, $tutar, $tarih, $aciklama, $_SESSION['personel_id']])) {
                    require_once '../includes/log_activity.php';
                    logActivity($db, 'gider_ekle', 'giderler', $db->lastInsertId(), "Gider eklendi: $gider_tipi - " . formatMoney($tutar));
                    @ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Gider eklendi']);
                } else {
                    @ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
                }
            } else {
                $gelir_tipi = cleanInput($data['gelir_tipi']);
                $kategori = cleanInput($data['kategori'] ?? '');
                $tutar = floatval($data['tutar']);
                $tarih = cleanInput($data['tarih']);
                $aciklama = cleanInput($data['aciklama'] ?? '');
                
                $stmt = $db->prepare("INSERT INTO gelirler (gelir_tipi, kategori, tutar, tarih, aciklama, personel_id) 
                                     VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$gelir_tipi, $kategori, $tutar, $tarih, $aciklama, $_SESSION['personel_id']])) {
                    require_once '../includes/log_activity.php';
                    logActivity($db, 'gelir_ekle', 'gelirler', $db->lastInsertId(), "Gelir eklendi: $gelir_tipi - " . formatMoney($tutar));
                    @ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Gelir eklendi']);
                } else {
                    @ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
                }
            }
        }
        break;
        
    case 'guncelle':
        if ($method == 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id']);
            
            if ($tip === 'gider') {
                $gider_tipi = cleanInput($data['gider_tipi']);
                $kategori = cleanInput($data['kategori'] ?? '');
                $tutar = floatval($data['tutar']);
                $tarih = cleanInput($data['tarih']);
                $aciklama = cleanInput($data['aciklama'] ?? '');
                
                $stmt = $db->prepare("UPDATE giderler SET gider_tipi = ?, kategori = ?, tutar = ?, tarih = ?, aciklama = ? WHERE id = ?");
                if ($stmt->execute([$gider_tipi, $kategori, $tutar, $tarih, $aciklama, $id])) {
                    require_once '../includes/log_activity.php';
                    logActivity($db, 'gider_guncelle', 'giderler', $id, "Gider güncellendi: $gider_tipi");
                    @ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Gider güncellendi']);
                } else {
                    @ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
                }
            } else {
                $gelir_tipi = cleanInput($data['gelir_tipi']);
                $kategori = cleanInput($data['kategori'] ?? '');
                $tutar = floatval($data['tutar']);
                $tarih = cleanInput($data['tarih']);
                $aciklama = cleanInput($data['aciklama'] ?? '');
                
                $stmt = $db->prepare("UPDATE gelirler SET gelir_tipi = ?, kategori = ?, tutar = ?, tarih = ?, aciklama = ? WHERE id = ?");
                if ($stmt->execute([$gelir_tipi, $kategori, $tutar, $tarih, $aciklama, $id])) {
                    require_once '../includes/log_activity.php';
                    logActivity($db, 'gelir_guncelle', 'gelirler', $id, "Gelir güncellendi: $gelir_tipi");
                    @ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Gelir güncellendi']);
                } else {
                    @ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
                }
            }
        }
        break;
        
    case 'sil':
        if ($method == 'DELETE') {
            $id = intval($_GET['id']);
            
            if ($tip === 'gider') {
                $stmt = $db->prepare("DELETE FROM giderler WHERE id = ?");
            } else {
                $stmt = $db->prepare("DELETE FROM gelirler WHERE id = ?");
            }
            
            if ($stmt->execute([$id])) {
                require_once '../includes/log_activity.php';
                logActivity($db, $tip . '_sil', $tip . 'ler', $id, ucfirst($tip) . ' silindi');
                @ob_clean();
                echo json_encode(['success' => true, 'message' => ucfirst($tip) . ' silindi']);
            } else {
                @ob_clean();
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
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


