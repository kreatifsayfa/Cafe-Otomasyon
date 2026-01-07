<?php
require_once 'config/config.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['personel_id'])) {
    if ($_SESSION['rol'] == 'garson') {
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    } else {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

$hata = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = cleanInput($_POST['email']);
    $sifre = $_POST['sifre'];
    
    $stmt = $db->prepare("SELECT * FROM personel WHERE email = ? AND durum = 'aktif' AND rol = 'garson'");
    $stmt->execute([$email]);
    $personel = $stmt->fetch();
    
    if ($personel && password_verify($sifre, $personel['sifre'])) {
        $_SESSION['personel_id'] = $personel['id'];
        $_SESSION['ad_soyad'] = $personel['ad_soyad'];
        $_SESSION['rol'] = $personel['rol'];
        $_SESSION['email'] = $personel['email'];
        
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    } else {
        $hata = 'E-posta veya şifre hatalı! Sadece garson hesapları giriş yapabilir.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garson Girişi - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-amber-500 to-amber-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user-tie text-white text-4xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Garson Girişi</h1>
            <p class="text-gray-600">Sadece garson hesapları giriş yapabilir</p>
        </div>
        
        <?php if ($hata): ?>
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-3"></i>
                <p><?php echo htmlspecialchars($hata); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2 text-amber-600"></i>E-posta
                    </label>
                    <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="garson@cafe.com">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-amber-600"></i>Şifre
                    </label>
                    <input type="password" name="sifre" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="••••••••">
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-3 px-4 rounded-lg shadow-lg hover:shadow-xl transition-all">
                    <i class="fas fa-sign-in-alt mr-2"></i>Giriş Yap
                </button>
            </div>
        </form>
        
        <div class="mt-6 text-center">
            <a href="<?php echo BASE_URL; ?>login.php" class="text-sm text-amber-600 hover:text-amber-700">
                <i class="fas fa-arrow-left mr-1"></i>Admin/Kasiyer Girişi
            </a>
        </div>
    </div>
</body>
</html>

