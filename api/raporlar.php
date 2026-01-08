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
checkRoleAPI(['admin', 'kasiyer']);

$action = $_GET['action'] ?? '';

switch($action) {
    case 'gunluk':
        $tarih = $_GET['tarih'] ?? date('Y-m-d');
        $stmt = $db->prepare("SELECT 
                                COUNT(*) as toplam_siparis,
                                SUM(toplam_tutar) as toplam_tutar,
                                SUM(kdv_tutari) as toplam_kdv,
                                SUM(indirim_tutari) as toplam_indirim,
                                SUM(toplam_tutar + kdv_tutari - indirim_tutari) as genel_toplam
                             FROM siparis 
                             WHERE DATE(olusturma_tarihi) = ? AND odeme_durumu = 'odendi'");
        $stmt->execute([$tarih]);
        $rapor = $stmt->fetch();
        
        // Ödeme tipine göre dağılım
        $stmt = $db->prepare("SELECT odeme_tipi, SUM(toplam_tutar + kdv_tutari - indirim_tutari) as tutar 
                             FROM siparis 
                             WHERE DATE(olusturma_tarihi) = ? AND odeme_durumu = 'odendi' 
                             GROUP BY odeme_tipi");
        $stmt->execute([$tarih]);
        $odeme_dagilimi = $stmt->fetchAll();
        
        @ob_clean();
        echo json_encode(['rapor' => $rapor, 'odeme_dagilimi' => $odeme_dagilimi]);
        break;
        
    case 'aylik':
        $ay = $_GET['ay'] ?? date('Y-m');
        $stmt = $db->prepare("SELECT 
                                DATE(olusturma_tarihi) as tarih,
                                COUNT(*) as siparis_sayisi,
                                SUM(toplam_tutar + kdv_tutari - indirim_tutari) as gunluk_toplam
                             FROM siparis 
                             WHERE DATE_FORMAT(olusturma_tarihi, '%Y-%m') = ? AND odeme_durumu = 'odendi' 
                             GROUP BY DATE(olusturma_tarihi) 
                             ORDER BY tarih");
        $stmt->execute([$ay]);
        $gunluk_raporlar = $stmt->fetchAll();
        
        $stmt = $db->prepare("SELECT 
                                SUM(toplam_tutar) as toplam_tutar,
                                SUM(kdv_tutari) as toplam_kdv,
                                SUM(indirim_tutari) as toplam_indirim,
                                SUM(toplam_tutar + kdv_tutari - indirim_tutari) as genel_toplam,
                                COUNT(*) as toplam_siparis
                             FROM siparis 
                             WHERE DATE_FORMAT(olusturma_tarihi, '%Y-%m') = ? AND odeme_durumu = 'odendi'");
        $stmt->execute([$ay]);
        $ozet = $stmt->fetch();
        
        @ob_clean();
        echo json_encode(['gunluk_raporlar' => $gunluk_raporlar, 'ozet' => $ozet]);
        break;
        
    case 'en_cok_satan':
        $baslangic = $_GET['baslangic'] ?? date('Y-m-01');
        $bitis = $_GET['bitis'] ?? date('Y-m-d');
        
        $stmt = $db->prepare("SELECT 
                                u.urun_adi,
                                SUM(sd.adet) as toplam_adet,
                                SUM(sd.toplam_fiyat) as toplam_tutar
                             FROM siparis_detay sd
                             JOIN menu_urun u ON sd.urun_id = u.id
                             JOIN siparis s ON sd.siparis_id = s.id
                             WHERE DATE(s.olusturma_tarihi) BETWEEN ? AND ? AND s.odeme_durumu = 'odendi'
                             GROUP BY u.id, u.urun_adi
                             ORDER BY toplam_adet DESC
                             LIMIT 10");
        $stmt->execute([$baslangic, $bitis]);
        @ob_clean();
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'kasa_ozet':
        $tarih = $_GET['tarih'] ?? date('Y-m-d');
        $stmt = $db->prepare("SELECT * FROM kasa_ozet WHERE tarih = ?");
        $stmt->execute([$tarih]);
        $ozet = $stmt->fetch();
        
        if (!$ozet) {
            // Bugün için özet yoksa oluştur
            $stmt = $db->prepare("SELECT kasa_bakiye FROM kasa_ozet ORDER BY tarih DESC LIMIT 1");
            $stmt->execute();
            $onceki_ozet = $stmt->fetch();
            $baslangic_bakiye = $onceki_ozet ? $onceki_ozet['kasa_bakiye'] : 0;
            
            $stmt = $db->prepare("INSERT INTO kasa_ozet (tarih, baslangic_bakiye) VALUES (?, ?)");
            $stmt->execute([$tarih, $baslangic_bakiye]);
            
            $stmt = $db->prepare("SELECT * FROM kasa_ozet WHERE tarih = ?");
            $stmt->execute([$tarih]);
            $ozet = $stmt->fetch();
        }
        
        @ob_clean();
        echo json_encode($ozet);
        break;
        
    default:
        @ob_clean();
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        break;
}

@ob_end_flush();
?>


