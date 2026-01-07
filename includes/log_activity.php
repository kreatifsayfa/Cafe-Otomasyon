<?php
// Aktivite loglama fonksiyonu
function logActivity($db, $islem_tipi, $tablo_adi = null, $kayit_id = null, $aciklama = null) {
    $personel_id = isset($_SESSION['personel_id']) ? $_SESSION['personel_id'] : null;
    $ip_adresi = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    try {
        $stmt = $db->prepare("INSERT INTO aktivite_log (personel_id, islem_tipi, tablo_adi, kayit_id, aciklama, ip_adresi, user_agent) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$personel_id, $islem_tipi, $tablo_adi, $kayit_id, $aciklama, $ip_adresi, $user_agent]);
    } catch(Exception $e) {
        // Log hatası sessizce geç
    }
}

// Bildirim oluşturma fonksiyonu
function createNotification($db, $personel_id, $baslik, $mesaj, $tip = 'info', $link = '') {
    try {
        $stmt = $db->prepare("INSERT INTO bildirim (personel_id, baslik, mesaj, tip, link) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$personel_id, $baslik, $mesaj, $tip, $link]);
        return $db->lastInsertId();
    } catch(Exception $e) {
        return false;
    }
}

// Tüm personellere bildirim gönder
function createNotificationAll($db, $baslik, $mesaj, $tip = 'info', $link = '') {
    try {
        $stmt = $db->query("SELECT id FROM personel WHERE durum = 'aktif'");
        $personeller = $stmt->fetchAll();
        foreach ($personeller as $personel) {
            createNotification($db, $personel['id'], $baslik, $mesaj, $tip, $link);
        }
        return true;
    } catch(Exception $e) {
        return false;
    }
}

// Stok uyarısı kontrolü
function checkStockAlerts($db) {
    $stmt = $db->query("SELECT * FROM stok WHERE miktar <= minimum_stok");
    $dusuk_stoklar = $stmt->fetchAll();
    
    if (!empty($dusuk_stoklar)) {
        // Admin'lere bildirim gönder
        $stmt = $db->query("SELECT id FROM personel WHERE rol = 'admin' AND durum = 'aktif'");
        $adminler = $stmt->fetchAll();
        
        foreach ($adminler as $admin) {
            createNotification(
                $db,
                $admin['id'],
                'Düşük Stok Uyarısı',
                count($dusuk_stoklar) . ' ürünün stoğu minimum seviyenin altında!',
                'warning',
                'stok.php'
            );
        }
    }
}
?>

