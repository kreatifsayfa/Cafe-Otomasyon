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

$action = $_GET['action'] ?? '';

switch($action) {
    case 'istatistikler':
        // Genel istatistikler
        $stats = [];
        
        // Toplam masa sayısı
        $stmt = $db->query("SELECT COUNT(*) as sayi FROM masa");
        $stats['toplam_masa'] = $stmt->fetch()['sayi'];
        
        // Dolu masa sayısı
        $stmt = $db->query("SELECT COUNT(*) as sayi FROM masa WHERE durum = 'dolu'");
        $stats['dolu_masa'] = $stmt->fetch()['sayi'];
        
        // Bugünkü sipariş sayısı
        $stmt = $db->prepare("SELECT COUNT(*) as sayi FROM siparis WHERE DATE(olusturma_tarihi) = ?");
        $stmt->execute([date('Y-m-d')]);
        $stats['bugun_siparis'] = $stmt->fetch()['sayi'];
        
        // Bugünkü ciro
        $stmt = $db->prepare("SELECT SUM(toplam_tutar + kdv_tutari - indirim_tutari) as toplam 
                             FROM siparis WHERE DATE(olusturma_tarihi) = ? AND odeme_durumu = 'odendi'");
        $stmt->execute([date('Y-m-d')]);
        $stats['bugun_ciro'] = $stmt->fetch()['toplam'] ?? 0;
        
        // Bekleyen siparişler
        $stmt = $db->query("SELECT COUNT(*) as sayi FROM siparis WHERE durum IN ('beklemede', 'hazirlaniyor')");
        $stats['bekleyen_siparis'] = $stmt->fetch()['sayi'];
        
        // Bu ay toplam ciro
        $stmt = $db->prepare("SELECT SUM(toplam_tutar + kdv_tutari - indirim_tutari) as toplam 
                             FROM siparis WHERE DATE_FORMAT(olusturma_tarihi, '%Y-%m') = ? AND odeme_durumu = 'odendi'");
        $stmt->execute([date('Y-m')]);
        $stats['aylik_ciro'] = $stmt->fetch()['toplam'] ?? 0;
        
        // Toplam müşteri sayısı
        $stmt = $db->query("SELECT COUNT(*) as sayi FROM musteri");
        $stats['toplam_musteri'] = $stmt->fetch()['sayi'];
        
        // Düşük stok uyarısı
        $stmt = $db->query("SELECT COUNT(*) as sayi FROM stok WHERE miktar <= minimum_stok");
        $stats['dusuk_stok'] = $stmt->fetch()['sayi'];
        
        // Aktif kampanya sayısı
        $stmt = $db->query("SELECT COUNT(*) as sayi FROM kampanyalar 
                           WHERE durum = 'aktif' 
                           AND baslangic_tarihi <= CURDATE() 
                           AND bitis_tarihi >= CURDATE()");
        $stats['aktif_kampanya'] = $stmt->fetch()['sayi'];
        
        echo json_encode($stats);
        break;
        
    case 'grafik_verileri':
        $tip = $_GET['tip'] ?? 'gunluk';
        
        if ($tip == 'gunluk') {
            // Son 7 günün satış verileri
            $stmt = $db->prepare("SELECT 
                                    DATE(olusturma_tarihi) as tarih,
                                    COUNT(*) as siparis_sayisi,
                                    SUM(toplam_tutar + kdv_tutari - indirim_tutari) as toplam
                                 FROM siparis 
                                 WHERE DATE(olusturma_tarihi) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                                 AND odeme_durumu = 'odendi'
                                 GROUP BY DATE(olusturma_tarihi)
                                 ORDER BY tarih");
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
        } elseif ($tip == 'saatlik') {
            // Bugünkü saatlik satışlar
            $stmt = $db->prepare("SELECT 
                                    HOUR(olusturma_tarihi) as saat,
                                    COUNT(*) as siparis_sayisi,
                                    SUM(toplam_tutar + kdv_tutari - indirim_tutari) as toplam
                                 FROM siparis 
                                 WHERE DATE(olusturma_tarihi) = CURDATE() 
                                 AND odeme_durumu = 'odendi'
                                 GROUP BY HOUR(olusturma_tarihi)
                                 ORDER BY saat");
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
        } elseif ($tip == 'kategori') {
            // Kategori bazlı satışlar (son 30 gün)
            $stmt = $db->prepare("SELECT 
                                    k.kategori_adi,
                                    SUM(sd.toplam_fiyat) as toplam,
                                    SUM(sd.adet) as adet
                                 FROM siparis_detay sd
                                 JOIN menu_urun u ON sd.urun_id = u.id
                                 JOIN menu_kategori k ON u.kategori_id = k.id
                                 JOIN siparis s ON sd.siparis_id = s.id
                                 WHERE DATE(s.olusturma_tarihi) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                 AND s.odeme_durumu = 'odendi'
                                 GROUP BY k.id, k.kategori_adi
                                 ORDER BY toplam DESC");
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
        }
        break;
        
    case 'son_aktiviteler':
        $limit = intval($_GET['limit'] ?? 10);
        $stmt = $db->prepare("SELECT 
                                al.*,
                                p.ad_soyad as personel_adi
                             FROM aktivite_log al
                             LEFT JOIN personel p ON al.personel_id = p.id
                             ORDER BY al.olusturma_tarihi DESC
                             LIMIT ?");
        $stmt->execute([$limit]);
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

