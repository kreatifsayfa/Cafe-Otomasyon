<?php
require_once 'config/config.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['personel_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$hata = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Email veya kullanıcı adı olarak email kullanılabilir
    $email = cleanInput($_POST['email'] ?? $_POST['username'] ?? '');
    $sifre = $_POST['sifre'] ?? $_POST['password'] ?? '';
    
    if (empty($email) || empty($sifre)) {
        $hata = 'Lütfen tüm alanları doldurun!';
    } else {
        // Email ile giriş (kullanıcı adı olarak email kullanılabilir)
        $stmt = $db->prepare("SELECT * FROM personel WHERE email = ? AND durum = 'aktif'");
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
            $hata = 'E-posta veya şifre hatalı!';
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="tr">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Kafe Otomasyon Sistemi - Giriş</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "primary": "#d47311",
              "background-light": "#f8f7f6",
              "background-dark": "#221910",
              "text-light": "#1b140d",
              "text-dark": "#f8f7f6",
              "subtle-light": "#9a734c",
              "subtle-dark": "#a1988e",
              "input-light": "#f3ede7",
              "input-dark": "#3a2d20",
              "border-light": "#e7dbcf",
              "border-dark": "#4f4031"
            },
            fontFamily: {
              "display": ["Work Sans", "sans-serif"]
            },
            borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
          },
        },
      }
    </script>
    <style>
      .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
      }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
<div class="relative flex min-h-screen w-full flex-col group/design-root overflow-x-hidden">
<div class="flex-grow flex items-center justify-center">
<main class="w-full max-w-4xl mx-auto p-4 md:p-8">
<div class="grid grid-cols-1 md:grid-cols-2 shadow-xl rounded-xl overflow-hidden bg-background-light dark:bg-background-dark">
<!-- Left Panel - Visual -->
<div class="relative hidden md:block">
<div class="absolute inset-0 bg-center bg-no-repeat bg-cover" data-alt="Modern cafe interior with warm lighting" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuCkPNF2hpIPPa18THhT65YDg6ayrvoy9Piyi6KOhi3TToWIUcyYCcmL0bDSY1PQyONX99A--p0jRNihmZiwP--7yBwmsibObgCtGuOHr-IE_ZXFrmiJ0gUa5YrPedOnFGewLnP_MUVtyR6dfiP3avYB5Z5jY3a31dTKjeZmWXdX1Kqerv5LFBFjG30-kg3XE_98ihzyaw3ikQ2ZFg-57lKplsl8XFeERAjrqN5_UoErSlmPDGBysPp9Bwa1XCrUJ-8Ok--BcBW0kBAV');"></div>
<div class="absolute inset-0 bg-black/40"></div>
<div class="relative h-full flex flex-col justify-between p-8 text-white">
<div>
<h1 class="text-2xl font-bold tracking-tight"><?php echo SITE_NAME; ?></h1>
<p class="text-sm mt-1 text-white/80">Yönetim Paneline Hoş Geldiniz</p>
</div>
<p class="text-xs">Lezzetin ve teknolojinin buluşma noktası.</p>
</div>
</div>
<!-- Right Panel - Form -->
<div class="flex flex-col justify-center p-8 sm:p-12 bg-background-light dark:bg-background-dark">
<div class="flex flex-col gap-6">
<div class="flex flex-col gap-2 text-left">
<h1 class="text-text-light dark:text-text-dark text-3xl font-black leading-tight tracking-[-0.03em]">Giriş Yap</h1>
<h2 class="text-subtle-light dark:text-subtle-dark text-base font-normal leading-normal">Lütfen devam etmek için bilgilerinizi girin.</h2>
</div>

<?php if ($hata): ?>
<div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 p-4 rounded-lg">
    <div class="flex items-center">
        <span class="material-symbols-outlined mr-2" style="font-size: 20px;">error</span>
        <p><?php echo htmlspecialchars($hata); ?></p>
    </div>
</div>
<?php endif; ?>

<form method="POST" class="flex flex-col gap-4">
<!-- Username/Email Field -->
<div class="flex flex-col w-full">
<label class="text-text-light dark:text-text-dark text-sm font-medium leading-normal pb-2" for="username">E-posta</label>
<div class="flex w-full flex-1 items-stretch rounded-lg">
<div class="text-subtle-light dark:text-subtle-dark flex border border-border-light dark:border-border-dark bg-input-light dark:bg-input-dark items-center justify-center pl-4 rounded-l-lg border-r-0">
<span class="material-symbols-outlined" style="font-size: 20px;">person</span>
</div>
<input class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden text-text-light dark:text-text-dark focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-input-light dark:bg-input-dark h-14 placeholder:text-subtle-light dark:placeholder:text-subtle-dark p-3.5 pr-2 rounded-r-lg border-l-0 text-base font-normal leading-normal" id="username" name="email" placeholder="ornek@cafe.com" type="email" required autofocus value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"/>
</div>
</div>
<!-- Password Field -->
<div class="flex flex-col w-full">
<label class="text-text-light dark:text-text-dark text-sm font-medium leading-normal pb-2" for="password">Şifre</label>
<div class="flex w-full flex-1 items-stretch rounded-lg">
<div class="text-subtle-light dark:text-subtle-dark flex border border-border-light dark:border-border-dark bg-input-light dark:bg-input-dark items-center justify-center pl-4 rounded-l-lg border-r-0">
<span class="material-symbols-outlined" style="font-size: 20px;">lock</span>
</div>
<input class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden text-text-light dark:text-text-dark focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-input-light dark:bg-input-dark h-14 placeholder:text-subtle-light dark:placeholder:text-subtle-dark p-3.5 pr-2 rounded-r-lg border-l-0 text-base font-normal leading-normal" id="password" name="sifre" placeholder="Şifre" type="password" required/>
</div>
</div>
<!-- Login Button -->
<div class="pt-2">
<button class="flex w-full min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-12 px-5 bg-primary text-white text-base font-bold leading-normal tracking-[0.015em] hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary/50 focus:ring-offset-background-light dark:focus:ring-offset-background-dark transition-colors" type="submit">
<span class="truncate">Giriş Yap</span>
</button>
</div>
</form>

<div class="mt-6 pt-6 border-t border-border-light dark:border-border-dark">
    <p class="text-xs text-center text-subtle-light dark:text-subtle-dark">
        Varsayılan: <span class="font-semibold">admin@cafe.com</span> / <span class="font-semibold">admin123</span>
    </p>
</div>
</div>
</div>
</div>
</main>
</div>
<footer class="w-full py-5 text-center">
<p class="text-subtle-light dark:text-subtle-dark text-sm font-normal leading-normal">© <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> | Tüm Hakları Saklıdır.</p>
</footer>
</div>
</body>
</html>
