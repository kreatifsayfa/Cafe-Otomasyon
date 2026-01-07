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
            $tarih = isset($_GET['tarih']) ? $_GET['tarih'] : date('Y-m-d');
            $stmt = $db->prepare("SELECT r.*, m.masa_no, m.kapasite, mu.ad_soyad as musteri_adi 
                                 FROM rezervasyon r
                                 JOIN masa m ON r.masa_id = m.id
                                 LEFT JOIN musteri mu ON r.musteri_id = mu.id
                                 WHERE DATE(r.rezervasyon_tarihi) = ?
                                 ORDER BY r.rezervasyon_saati");
            $stmt->execute([$tarih]);
            @ob_clean();
            echo json_encode($stmt->fetchAll());
        } catch(Exception $e) {
            error_log("Rezervasyon listeleme hatası: " . $e->getMessage());
            @ob_clean();
            echo json_encode(['success' => false, 'message' => 'Rezervasyonlar yüklenirken hata oluştu']);
        }
        break;
        
    case 'getir':
        try {
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Geçersiz rezervasyon ID');
            }
            
            $stmt = $db->prepare("SELECT r.*, m.masa_no, mu.ad_soyad as musteri_adi 
                                 FROM rezervasyon r
                                 JOIN masa m ON r.masa_id = m.id
                                 LEFT JOIN musteri mu ON r.musteri_id = mu.id
                                 WHERE r.id = ?");
            $stmt->execute([$id]);
            $rezervasyon = $stmt->fetch();
            @ob_clean();
            echo json_encode($rezervasyon ?: null);
        } catch(Exception $e) {
            error_log("Rezervasyon getirme hatası: " . $e->getMessage());
            @ob_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'ekle':
        if ($method == 'POST') {
            checkRoleAPI(['admin', 'kasiyer', 'garson']); // Rezervasyon ekleme yetkisi
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception('Geçersiz veri formatı');
                }
                
                $musteri_id = !empty($data['musteri_id']) ? intval($data['musteri_id']) : null;
                $masa_id = intval($data['masa_id'] ?? 0);
                if ($masa_id <= 0) {
                    throw new Exception('Masa seçimi gereklidir');
                }
                
                $rezervasyon_tarihi = $data['rezervasyon_tarihi'] ?? '';
                if (empty($rezervasyon_tarihi)) {
                    throw new Exception('Rezervasyon tarihi gereklidir');
                }
                
                $rezervasyon_saati = $data['rezervasyon_saati'] ?? '';
                if (empty($rezervasyon_saati)) {
                    throw new Exception('Rezervasyon saati gereklidir');
                }
                
                $kisi_sayisi = intval($data['kisi_sayisi'] ?? 0);
                if ($kisi_sayisi <= 0) {
                    throw new Exception('Kişi sayısı 0\'dan büyük olmalıdır');
                }
                
                $notlar = cleanInput($data['notlar'] ?? '');
                $durum = cleanInput($data['durum'] ?? 'beklemede');
                
                $db->beginTransaction();
                
                $stmt = $db->prepare("INSERT INTO rezervasyon (musteri_id, masa_id, rezervasyon_tarihi, rezervasyon_saati, kisi_sayisi, durum, notlar) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt->execute([$musteri_id, $masa_id, $rezervasyon_tarihi, $rezervasyon_saati, $kisi_sayisi, $durum, $notlar])) {
                    throw new Exception('Veritabanı hatası');
                }
                
                // Masa durumunu güncelle
                $stmt = $db->prepare("UPDATE masa SET durum = 'rezerve' WHERE id = ?");
                $stmt->execute([$masa_id]);
                
                require_once '../includes/log_activity.php';
                logActivity($db, 'rezervasyon_ekle', 'rezervasyon', $db->lastInsertId(), 'Rezervasyon eklendi');
                
                $db->commit();
                @ob_clean();
                echo json_encode(['success' => true, 'message' => 'Rezervasyon eklendi']);
            } catch(Exception $e) {
                $db->rollBack();
                error_log("Rezervasyon ekleme hatası: " . $e->getMessage());
                @ob_clean();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    case 'guncelle':
        if ($method == 'PUT') {
            checkRoleAPI(['admin', 'kasiyer']); // Rezervasyon güncelleme yetkisi
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception('Geçersiz veri formatı');
                }
                
                $id = intval($data['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Geçersiz rezervasyon ID');
                }
                
                $musteri_id = !empty($data['musteri_id']) ? intval($data['musteri_id']) : null;
                $masa_id = intval($data['masa_id'] ?? 0);
                if ($masa_id <= 0) {
                    throw new Exception('Masa seçimi gereklidir');
                }
                
                $rezervasyon_tarihi = $data['rezervasyon_tarihi'] ?? '';
                if (empty($rezervasyon_tarihi)) {
                    throw new Exception('Rezervasyon tarihi gereklidir');
                }
                
                $rezervasyon_saati = $data['rezervasyon_saati'] ?? '';
                if (empty($rezervasyon_saati)) {
                    throw new Exception('Rezervasyon saati gereklidir');
                }
                
                $kisi_sayisi = intval($data['kisi_sayisi'] ?? 0);
                if ($kisi_sayisi <= 0) {
                    throw new Exception('Kişi sayısı 0\'dan büyük olmalıdır');
                }
                
                $notlar = cleanInput($data['notlar'] ?? '');
                $durum = cleanInput($data['durum'] ?? '');
                if (empty($durum)) {
                    throw new Exception('Durum gereklidir');
                }
                
                $db->beginTransaction();
                
                $stmt = $db->prepare("UPDATE rezervasyon SET musteri_id = ?, masa_id = ?, rezervasyon_tarihi = ?, 
                                     rezervasyon_saati = ?, kisi_sayisi = ?, durum = ?, notlar = ? WHERE id = ?");
                if (!$stmt->execute([$musteri_id, $masa_id, $rezervasyon_tarihi, $rezervasyon_saati, $kisi_sayisi, $durum, $notlar, $id])) {
                    throw new Exception('Veritabanı hatası');
                }
                
                // Masa durumunu güncelle
                if ($durum == 'onaylandi') {
                    $stmt = $db->prepare("UPDATE masa SET durum = 'rezerve' WHERE id = ?");
                } elseif ($durum == 'iptal' || $durum == 'tamamlandi') {
                    $stmt = $db->prepare("UPDATE masa SET durum = 'bos' WHERE id = ?");
                }
                if (isset($stmt)) {
                    $stmt->execute([$masa_id]);
                }
                
                require_once '../includes/log_activity.php';
                logActivity($db, 'rezervasyon_guncelle', 'rezervasyon', $id, 'Rezervasyon güncellendi');
                
                $db->commit();
                @ob_clean();
                echo json_encode(['success' => true, 'message' => 'Rezervasyon güncellendi']);
            } catch(Exception $e) {
                $db->rollBack();
                error_log("Rezervasyon güncelleme hatası: " . $e->getMessage());
                @ob_clean();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    case 'sil':
        if ($method == 'DELETE') {
            checkRoleAPI(['admin', 'kasiyer']); // Rezervasyon silme yetkisi
            try {
                $id = intval($_GET['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Geçersiz rezervasyon ID');
                }
                
                $db->beginTransaction();
                
                // Önce masa ID'sini al
                $stmt = $db->prepare("SELECT masa_id FROM rezervasyon WHERE id = ?");
                $stmt->execute([$id]);
                $rezervasyon = $stmt->fetch();
                
                if (!$rezervasyon) {
                    throw new Exception('Rezervasyon bulunamadı');
                }
                
                $stmt = $db->prepare("DELETE FROM rezervasyon WHERE id = ?");
                if (!$stmt->execute([$id])) {
                    throw new Exception('Veritabanı hatası');
                }
                
                // Masa durumunu güncelle
                $stmt = $db->prepare("UPDATE masa SET durum = 'bos' WHERE id = ?");
                $stmt->execute([$rezervasyon['masa_id']]);
                
                require_once '../includes/log_activity.php';
                logActivity($db, 'rezervasyon_sil', 'rezervasyon', $id, 'Rezervasyon silindi');
                
                $db->commit();
                @ob_clean();
                echo json_encode(['success' => true, 'message' => 'Rezervasyon silindi']);
            } catch(Exception $e) {
                $db->rollBack();
                error_log("Rezervasyon silme hatası: " . $e->getMessage());
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


