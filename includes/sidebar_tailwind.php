<!-- Mobile Menu Overlay -->
<div id="mobile-menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed left-0 top-0 h-screen w-64 bg-gradient-to-b from-gray-900 to-gray-800 text-white shadow-2xl z-50 transition-transform duration-300 ease-in-out transform -translate-x-full lg:translate-x-0">
    <div class="flex flex-col h-full">
        <!-- Logo & User -->
        <div class="p-6 border-b border-gray-700">
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-amber-700 rounded-lg flex items-center justify-center">
                    <i class="fas fa-coffee text-white text-lg"></i>
                </div>
                <h2 class="text-xl font-bold text-white">Cafe Otomasyon</h2>
            </div>
            <p class="text-sm text-gray-300 truncate">
                <i class="fas fa-user-circle mr-2"></i>
                <?php echo htmlspecialchars($_SESSION['ad_soyad']); ?>
            </p>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4 px-3">
            <div class="space-y-1">
                <!-- Ana Sayfa -->
                <a href="<?php echo BASE_URL; ?>index.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-home w-5 mr-3"></i>
                    <span>Ana Sayfa</span>
                </a>
                
                <!-- Masalar -->
                <a href="<?php echo BASE_URL; ?>masalar.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'masalar.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-table w-5 mr-3"></i>
                    <span>Masalar</span>
                </a>
                
                <!-- Siparişler -->
                <a href="<?php echo BASE_URL; ?>siparisler.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'siparisler.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-shopping-cart w-5 mr-3"></i>
                    <span>Siparişler</span>
                </a>
                
                <!-- Sipariş Al -->
                <a href="<?php echo BASE_URL; ?>siparis.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'siparis.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-plus-circle w-5 mr-3"></i>
                    <span>Sipariş Al</span>
                </a>
                
                <!-- Mutfak Görünümü (Sadece Barmen ve Şef) -->
                <?php if (in_array($_SESSION['rol'], ['admin', 'sef', 'barmen'])): ?>
                <a href="<?php echo BASE_URL; ?>mutfak.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'mutfak.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-utensils w-5 mr-3"></i>
                    <span>Mutfak Görünümü</span>
                </a>
                <?php endif; ?>
                
                <!-- Ayarlar -->
                <?php if ($_SESSION['rol'] == 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>ayarlar.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'ayarlar.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-cog w-5 mr-3"></i>
                    <span>Ayarlar</span>
                </a>
                <?php endif; ?>
                
                <!-- Menü -->
                <a href="<?php echo BASE_URL; ?>menu.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-utensils w-5 mr-3"></i>
                    <span>Menü Yönetimi</span>
                </a>
                
                <!-- Hesap Kesme -->
                <a href="<?php echo BASE_URL; ?>hesap.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'hesap.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-cash-register w-5 mr-3"></i>
                    <span>Hesap Kesme</span>
                </a>
                
                <!-- Raporlar -->
                <a href="<?php echo BASE_URL; ?>raporlar.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'raporlar.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-chart-bar w-5 mr-3"></i>
                    <span>Raporlar</span>
                </a>
                
                <?php if ($_SESSION['rol'] == 'admin'): ?>
                <div class="pt-4 mt-4 border-t border-gray-700">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Yönetim</p>
                </div>
                
                <!-- Stok -->
                <a href="<?php echo BASE_URL; ?>stok.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'stok.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-box w-5 mr-3"></i>
                    <span>Stok Yönetimi</span>
                </a>
                
                <!-- Personel -->
                <a href="<?php echo BASE_URL; ?>personel.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'personel.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-users w-5 mr-3"></i>
                    <span>Personel</span>
                </a>
                
                <!-- Müşteriler -->
                <a href="<?php echo BASE_URL; ?>musteri.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'musteri.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-user-friends w-5 mr-3"></i>
                    <span>Müşteriler</span>
                </a>
                
                <!-- Rezervasyonlar -->
                <a href="<?php echo BASE_URL; ?>rezervasyon.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'rezervasyon.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-calendar w-5 mr-3"></i>
                    <span>Rezervasyonlar</span>
                </a>
                
                <!-- Kampanyalar -->
                <a href="<?php echo BASE_URL; ?>kampanya.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'kampanya.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-tags w-5 mr-3"></i>
                    <span>Kampanyalar</span>
                </a>
                
                <!-- Gider-Gelir -->
                <a href="<?php echo BASE_URL; ?>gider_gelir.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'gider_gelir.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-chart-line w-5 mr-3"></i>
                    <span>Gider-Gelir</span>
                </a>
                
                <!-- Personel Performans -->
                <a href="<?php echo BASE_URL; ?>personel_performans.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'personel_performans.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-chart-pie w-5 mr-3"></i>
                    <span>Personel Performans</span>
                </a>
                
                <!-- QR Kod -->
                <a href="<?php echo BASE_URL; ?>qr_masa.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'qr_masa.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-qrcode w-5 mr-3"></i>
                    <span>QR Kod Sistemi</span>
                </a>
                <?php endif; ?>
                
                <?php if ($_SESSION['rol'] == 'admin' || $_SESSION['rol'] == 'garson'): ?>
                <!-- Masa İşlemleri -->
                <a href="<?php echo BASE_URL; ?>masa_islemleri.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'masa_islemleri.php' ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-exchange-alt w-5 mr-3"></i>
                    <span>Masa İşlemleri</span>
                </a>
                <?php endif; ?>
            </div>
        </nav>
        
        <!-- Logout -->
        <div class="p-4 border-t border-gray-700">
            <a href="<?php echo BASE_URL; ?>logout.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-red-400 hover:bg-red-900/20 hover:text-red-300 transition-all duration-200">
                <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                <span>Çıkış Yap</span>
            </a>
        </div>
    </div>
</aside>

<!-- Mobile Menu Toggle Button -->
<button id="mobile-menu-btn" onclick="toggleSidebar()" class="fixed top-4 left-4 z-50 lg:hidden bg-white shadow-lg rounded-lg p-3 text-gray-700 hover:bg-gray-100 transition-colors">
    <i class="fas fa-bars text-xl"></i>
</button>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-menu-overlay');
    const isOpen = !sidebar.classList.contains('-translate-x-full');
    
    if (isOpen) {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    } else {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    }
}

// Close sidebar when clicking outside on mobile
document.getElementById('mobile-menu-overlay')?.addEventListener('click', toggleSidebar);

// Close sidebar when window is resized to desktop
window.addEventListener('resize', function() {
    if (window.innerWidth >= 1024) {
        document.getElementById('sidebar')?.classList.remove('-translate-x-full');
        document.getElementById('mobile-menu-overlay')?.classList.add('hidden');
    }
});
</script>


