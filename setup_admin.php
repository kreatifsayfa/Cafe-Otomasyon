<?php
// Admin şifresini oluşturma scripti
// Bu dosyayı bir kez çalıştırın, sonra silin

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Admin şifresini güncelle
$email = 'admin@cafe.com';
$sifre = 'admin123';
$hash = password_hash($sifre, PASSWORD_DEFAULT);

try {
    // Önce admin kullanıcısını kontrol et
    $stmt = $db->prepare("SELECT id FROM personel WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        // Mevcut admin şifresini güncelle
        $stmt = $db->prepare("UPDATE personel SET sifre = ? WHERE email = ?");
        $stmt->execute([$hash, $email]);
        echo "✅ Admin şifresi başarıyla güncellendi!<br>";
        echo "E-posta: $email<br>";
        echo "Şifre: $sifre<br>";
        echo "Hash: $hash<br><br>";
        echo "⚠️ Bu dosyayı güvenlik için silin!";
    } else {
        // Yeni admin kullanıcısı oluştur
        $stmt = $db->prepare("INSERT INTO personel (ad_soyad, email, telefon, sifre, rol, durum) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['Admin Kullanıcı', $email, '05551234567', $hash, 'admin', 'aktif']);
        echo "✅ Admin kullanıcısı başarıyla oluşturuldu!<br>";
        echo "E-posta: $email<br>";
        echo "Şifre: $sifre<br>";
        echo "Hash: $hash<br><br>";
        echo "⚠️ Bu dosyayı güvenlik için silin!";
    }
} catch(Exception $e) {
    echo "❌ Hata: " . $e->getMessage();
}
?>


