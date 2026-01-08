<?php
// Genel Ayarlar
session_start();

// Hata Raporlama (Production'da kapatılmalı)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zaman Dilimi
date_default_timezone_set('Europe/Istanbul');

// Site Ayarları - Base URL Otomatik Algılama
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$base_path = str_replace('\\', '/', $script_path);
if ($base_path !== '/' && $base_path !== '') {
    $base_path = rtrim($base_path, '/') . '/';
} else {
    $base_path = '/';
}

define('BASE_URL', $protocol . $host . $base_path);
define('SITE_URL', BASE_URL); // Geriye dönük uyumluluk
define('SITE_NAME', 'Cafe Otomasyonu');

// Veritabanı Bağlantısı
require_once __DIR__ . '/database.php';
$database = new Database();
$db = $database->getConnection();

// Yardımcı Fonksiyonlar
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function formatMoney($amount) {
    return number_format($amount, 2, ',', '.') . ' ₺';
}

function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d.m.Y H:i', strtotime($datetime));
}

// Oturum Kontrolü
function checkLogin() {
    if (!isset($_SESSION['personel_id'])) {
        header('Location: ' . SITE_URL . 'login.php');
        exit();
    }
}

// API için oturum kontrolü - JSON döndürür
function checkLoginAPI() {
    if (!isset($_SESSION['personel_id'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor', 'redirect' => true]);
        exit();
    }
}

// Yetki Kontrolü
function checkRole($required_roles = []) {
    checkLogin();
    if (!empty($required_roles) && !in_array($_SESSION['rol'], $required_roles)) {
        $_SESSION['error'] = 'Bu sayfaya erişim yetkiniz yok!';
        header('Location: ' . SITE_URL . 'index.php');
        exit();
    }
}

// API için yetki kontrolü - JSON döndürür
function checkRoleAPI($required_roles = []) {
    checkLoginAPI();
    if (!empty($required_roles) && !in_array($_SESSION['rol'], $required_roles)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok']);
        exit();
    }
}

// Yetki kontrolü (kullanıcı yetkileri tablosundan)
function checkPermission($db, $yetki_adi) {
    checkLogin();
    
    // Admin her zaman yetkili
    if ($_SESSION['rol'] == 'admin') {
        return true;
    }
    
    // Yetki kontrolü
    $stmt = $db->prepare("SELECT COUNT(*) as sayi FROM kullanici_yetki 
                         WHERE personel_id = ? AND yetki_adi = ? AND durum = 1");
    $stmt->execute([$_SESSION['personel_id'], $yetki_adi]);
    $yetki = $stmt->fetch();
    
    return $yetki && $yetki['sayi'] > 0;
}

// Rol bazlı yetki kontrolü
function hasRole($required_roles = []) {
    if (empty($required_roles)) return true;
    return in_array($_SESSION['rol'], $required_roles);
}

// Hata mesajı göster
$error_message = '';
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Başarı mesajı göster
$success_message = '';
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

