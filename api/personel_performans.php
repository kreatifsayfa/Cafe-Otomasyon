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

$action = $_GET['action'] ?? '';
$ay = $_GET['ay'] ?? date('Y-m');
$personel_id = isset($_GET['personel_id']) ? intval($_GET['personel_id']) : 0;

switch($action) {
    case 'listele':
        $sql = "SELECT pp.*, p.ad_soyad as personel_adi 
                FROM personel_performans pp 
                JOIN personel p ON pp.personel_id = p.id 
                WHERE DATE_FORMAT(pp.tarih, '%Y-%m') = ?";
        $params = [$ay];
        
        if ($personel_id > 0) {
            $sql .= " AND pp.personel_id = ?";
            $params[] = $personel_id;
        }
        
        $sql .= " ORDER BY pp.tarih DESC, pp.toplam_tutar DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        @ob_clean();
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'hesapla':
        try {
            // Günlük performans hesapla
            $tarih = $_GET['tarih'] ?? date('Y-m-d');
            $personel_id = intval($_GET['personel_id'] ?? 0);
            
            if ($personel_id <= 0) {
                throw new Exception('Geçersiz personel ID');
            }
            
            // Sipariş istatistikleri
            $stmt = $db->prepare("SELECT 
                                    COUNT(*) as toplam_siparis,
                                    SUM(toplam_tutar + kdv_tutari - indirim_tutari) as toplam_tutar,
                                    AVG(toplam_tutar + kdv_tutari - indirim_tutari) as ortalama_siparis_tutari
                                 FROM siparis 
                                 WHERE personel_id = ? AND DATE(olusturma_tarihi) = ? AND odeme_durumu = 'odendi'");
            $stmt->execute([$personel_id, $tarih]);
            $stats = $stmt->fetch();
            
            // Performans kaydı oluştur veya güncelle
            $stmt = $db->prepare("INSERT INTO personel_performans 
                                 (personel_id, tarih, toplam_siparis, toplam_tutar, ortalama_siparis_tutari) 
                                 VALUES (?, ?, ?, ?, ?)
                                 ON DUPLICATE KEY UPDATE 
                                 toplam_siparis = VALUES(toplam_siparis),
                                 toplam_tutar = VALUES(toplam_tutar),
                                 ortalama_siparis_tutari = VALUES(ortalama_siparis_tutari)");
            $stmt->execute([
                $personel_id,
                $tarih,
                $stats['toplam_siparis'] ?: 0,
                $stats['toplam_tutar'] ?: 0,
                $stats['ortalama_siparis_tutari'] ?: 0
            ]);
            
            @ob_clean();
            echo json_encode(['success' => true]);
        } catch(Exception $e) {
            error_log("Performans hesaplama hatası: " . $e->getMessage());
            @ob_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    default:
        @ob_clean();
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        break;
}

@ob_end_flush();
?>


