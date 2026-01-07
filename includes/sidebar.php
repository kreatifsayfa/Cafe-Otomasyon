<div class="app-container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>☕ Cafe Otomasyonu</h2>
            <p><?php echo $_SESSION['ad_soyad']; ?></p>
        </div>
        
        <nav class="sidebar-nav">
            <a href="<?php echo BASE_URL; ?>index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Ana Sayfa
            </a>
            <a href="<?php echo BASE_URL; ?>masalar.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'masalar.php' ? 'active' : ''; ?>">
                <i class="fas fa-table"></i> Masalar
            </a>
            <a href="<?php echo BASE_URL; ?>siparis.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'siparis.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> Siparişler
            </a>
            <?php if ($_SESSION['rol'] == 'admin' || $_SESSION['rol'] == 'mutfak'): ?>
            <a href="<?php echo BASE_URL; ?>mutfak.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'mutfak.php' ? 'active' : ''; ?>">
                <i class="fas fa-utensils"></i> Mutfak Görünümü
            </a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>menu.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'active' : ''; ?>">
                <i class="fas fa-utensils"></i> Menü Yönetimi
            </a>
            <a href="<?php echo BASE_URL; ?>hesap.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'hesap.php' ? 'active' : ''; ?>">
                <i class="fas fa-cash-register"></i> Hesap Kesme
            </a>
            <a href="<?php echo BASE_URL; ?>raporlar.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'raporlar.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Raporlar
            </a>
            <?php if ($_SESSION['rol'] == 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>stok.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'stok.php' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Stok Yönetimi
            </a>
            <a href="<?php echo BASE_URL; ?>personel.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'personel.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Personel
            </a>
            <a href="<?php echo BASE_URL; ?>musteri.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'musteri.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-friends"></i> Müşteriler
            </a>
            <a href="<?php echo BASE_URL; ?>rezervasyon.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'rezervasyon.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar"></i> Rezervasyonlar
            </a>
            <a href="<?php echo BASE_URL; ?>kampanya.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'kampanya.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> Kampanyalar
            </a>
            <a href="<?php echo BASE_URL; ?>gider_gelir.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'gider_gelir.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Gider-Gelir
            </a>
            <a href="<?php echo BASE_URL; ?>personel_performans.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'personel_performans.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Personel Performans
            </a>
            <a href="<?php echo BASE_URL; ?>qr_masa.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'qr_masa.php' ? 'active' : ''; ?>">
                <i class="fas fa-qrcode"></i> QR Kod Sistemi
            </a>
            <?php endif; ?>
            <?php if ($_SESSION['rol'] == 'admin' || $_SESSION['rol'] == 'garson'): ?>
            <a href="<?php echo BASE_URL; ?>masa_islemleri.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'masa_islemleri.php' ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt"></i> Masa İşlemleri
            </a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i> Çıkış
            </a>
        </nav>
    </aside>

