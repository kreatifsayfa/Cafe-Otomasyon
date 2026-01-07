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
        $stmt = $db->query("SELECT * FROM personel ORDER BY ad_soyad");
        @ob_clean();
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'getir':
        $id = intval($_GET['id']);
        $stmt = $db->prepare("SELECT id, ad_soyad, email, telefon, rol, maas, durum, baslangic_tarihi FROM personel WHERE id = ?");
        $stmt->execute([$id]);
        @ob_clean();
        echo json_encode($stmt->fetch());
        break;
        
    case 'ekle':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $ad_soyad = cleanInput($data['ad_soyad']);
            $email = cleanInput($data['email']);
            $telefon = cleanInput($data['telefon'] ?? '');
            $sifre = $data['sifre'] ?? '';
            $rol = cleanInput($data['rol']);
            $maas = floatval($data['maas'] ?? 0);
            $durum = cleanInput($data['durum'] ?? 'aktif');
            
            if (empty($sifre)) {
                @ob_clean();
                echo json_encode(['success' => false, 'message' => 'Şifre gerekli']);
                exit;
            }
            
            $hash = password_hash($sifre, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO personel (ad_soyad, email, telefon, sifre, rol, maas, durum) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$ad_soyad, $email, $telefon, $hash, $rol, $maas, $durum])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'personel_ekle', 'personel', $db->lastInsertId(), "$ad_soyad eklendi");
                    @ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Personel eklendi']);
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
            $ad_soyad = cleanInput($data['ad_soyad']);
            $email = cleanInput($data['email']);
            $telefon = cleanInput($data['telefon'] ?? '');
            $rol = cleanInput($data['rol']);
            $maas = floatval($data['maas'] ?? 0);
            $durum = cleanInput($data['durum'] ?? 'aktif');
            $sifre = $data['sifre'] ?? '';
            
            if (!empty($sifre)) {
                $hash = password_hash($sifre, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE personel SET ad_soyad = ?, email = ?, telefon = ?, sifre = ?, rol = ?, maas = ?, durum = ? WHERE id = ?");
                $stmt->execute([$ad_soyad, $email, $telefon, $hash, $rol, $maas, $durum, $id]);
            } else {
                $stmt = $db->prepare("UPDATE personel SET ad_soyad = ?, email = ?, telefon = ?, rol = ?, maas = ?, durum = ? WHERE id = ?");
                $stmt->execute([$ad_soyad, $email, $telefon, $rol, $maas, $durum, $id]);
            }
            
            if ($stmt->rowCount() > 0) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'personel_guncelle', 'personel', $id, "$ad_soyad güncellendi");
                @ob_clean();
                echo json_encode(['success' => true, 'message' => 'Personel güncellendi']);
            } else {
                @ob_clean();
                echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
            }
        }
        break;
        
    case 'sil':
        if ($method == 'DELETE') {
            $id = intval($_GET['id']);
            if ($id == $_SESSION['personel_id']) {
                @ob_clean();
                echo json_encode(['success' => false, 'message' => 'Kendi hesabınızı silemezsiniz']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM personel WHERE id = ?");
            if ($stmt->execute([$id])) {
                require_once '../includes/log_activity.php';
                logActivity($db, 'personel_sil', 'personel', $id, 'Personel silindi');
                @ob_clean();
                echo json_encode(['success' => true, 'message' => 'Personel silindi']);
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

@ob_end_clean();
?>


