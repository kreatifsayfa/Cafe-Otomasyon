<?php
// Hata raporlamayı kapat - CSV export için
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Output buffering başlat
ob_start();

try {
    require_once '../config/config.php';
} catch(Exception $e) {
    @ob_clean();
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Sistem hatası: ' . $e->getMessage();
    exit();
}

checkLoginAPI();
checkRoleAPI(['admin']);

$tip = $_GET['tip'] ?? '';
$tarih = $_GET['tarih'] ?? date('Y-m-d');
$baslangic = $_GET['baslangic'] ?? '';
$bitis = $_GET['bitis'] ?? date('Y-m-d');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $tip . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// UTF-8 BOM ekle (Excel için)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

switch($tip) {
    case 'siparisler':
        fputcsv($output, ['Sipariş No', 'Masa', 'Müşteri', 'Tutar', 'KDV', 'Toplam', 'Ödeme Tipi', 'Durum', 'Tarih']);
        
        $sql = "SELECT s.*, m.masa_no, mu.ad_soyad as musteri_adi 
                FROM siparis s 
                JOIN masa m ON s.masa_id = m.id 
                LEFT JOIN musteri mu ON s.musteri_id = mu.id 
                WHERE 1=1";
        $params = [];
        
        if ($baslangic && $bitis) {
            $sql .= " AND DATE(s.olusturma_tarihi) BETWEEN ? AND ?";
            $params[] = $baslangic;
            $params[] = $bitis;
        } else {
            $sql .= " AND DATE(s.olusturma_tarihi) = ?";
            $params[] = $tarih;
        }
        
        $sql .= " ORDER BY s.olusturma_tarihi DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        while ($row = $stmt->fetch()) {
            $toplam = $row['toplam_tutar'] + $row['kdv_tutari'] - ($row['indirim_tutari'] ?? 0);
            fputcsv($output, [
                $row['siparis_no'],
                $row['masa_no'],
                $row['musteri_adi'] ?: '-',
                number_format($row['toplam_tutar'], 2, ',', '.') . ' ₺',
                number_format($row['kdv_tutari'], 2, ',', '.') . ' ₺',
                number_format($toplam, 2, ',', '.') . ' ₺',
                $row['odeme_tipi'] ?: '-',
                $row['durum'],
                date('d.m.Y H:i', strtotime($row['olusturma_tarihi']))
            ]);
        }
        break;
        
    case 'urunler':
        fputcsv($output, ['Ürün Adı', 'Kategori', 'Fiyat', 'Stok', 'Durum']);
        
        $stmt = $db->query("SELECT u.*, k.kategori_adi 
                           FROM menu_urun u 
                           JOIN menu_kategori k ON u.kategori_id = k.id 
                           ORDER BY k.sira, u.sira");
        
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['urun_adi'],
                $row['kategori_adi'],
                number_format($row['fiyat'], 2, ',', '.') . ' ₺',
                $row['stok_var_mi'] ? $row['stok_miktari'] : 'Sınırsız',
                $row['durum']
            ]);
        }
        break;
        
    case 'musteriler':
        fputcsv($output, ['Ad Soyad', 'Telefon', 'E-posta', 'Toplam Harcama', 'Puan', 'Kayıt Tarihi']);
        
        $stmt = $db->query("SELECT * FROM musteri ORDER BY toplam_harcama DESC");
        
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['ad_soyad'],
                $row['telefon'] ?: '-',
                $row['email'] ?: '-',
                number_format($row['toplam_harcama'], 2, ',', '.') . ' ₺',
                $row['puan'],
                date('d.m.Y', strtotime($row['olusturma_tarihi']))
            ]);
        }
        break;
        
    case 'personel':
        fputcsv($output, ['Ad Soyad', 'E-posta', 'Telefon', 'Rol', 'Maaş', 'Durum']);
        
        $stmt = $db->query("SELECT * FROM personel ORDER BY rol, ad_soyad");
        
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['ad_soyad'],
                $row['email'],
                $row['telefon'] ?: '-',
                $row['rol'],
                $row['maas'] ? number_format($row['maas'], 2, ',', '.') . ' ₺' : '-',
                $row['durum']
            ]);
        }
        break;
        
    case 'stok':
        fputcsv($output, ['Malzeme Adı', 'Miktar', 'Birim', 'Minimum Stok', 'Tedarikçi']);
        
        $stmt = $db->query("SELECT * FROM stok ORDER BY malzeme_adi");
        
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['malzeme_adi'],
                number_format($row['miktar'], 2, ',', '.'),
                $row['birim'],
                number_format($row['minimum_stok'], 2, ',', '.'),
                $row['tedarikci'] ?: '-'
            ]);
        }
        break;
        
    case 'gider_gelir':
        fputcsv($output, ['Tarih', 'Tip', 'Kategori', 'Açıklama', 'Tutar', 'Personel']);
        
        $baslangic = $_GET['baslangic'] ?? date('Y-m-01');
        $bitis = $_GET['bitis'] ?? date('Y-m-d');
        
        $sql = "SELECT 'Gider' as tip, g.*, p.ad_soyad as personel_adi 
                FROM giderler g 
                LEFT JOIN personel p ON g.personel_id = p.id 
                WHERE g.tarih BETWEEN ? AND ?
                UNION ALL 
                SELECT 'Gelir' as tip, ge.*, p2.ad_soyad as personel_adi 
                FROM gelirler ge 
                LEFT JOIN personel p2 ON ge.personel_id = p2.id 
                WHERE ge.tarih BETWEEN ? AND ?
                ORDER BY tarih DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$baslangic, $bitis, $baslangic, $bitis]);
        
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                date('d.m.Y', strtotime($row['tarih'])),
                $row['tip'],
                $row['kategori'] ?: '-',
                $row['aciklama'] ?: ($row['gider_tipi'] ?? $row['gelir_tipi']),
                number_format($row['tutar'], 2, ',', '.') . ' ₺',
                $row['personel_adi'] ?: '-'
            ]);
        }
        break;
}

fclose($output);
@ob_end_flush();
exit;
?>

