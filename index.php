<?php
require_once 'config/config.php';
checkLogin();
$page_title = 'Ana Sayfa';
?>
<?php include 'includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar_tailwind.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 z-40">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 sm:py-4 gap-3">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Dashboard</h1>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></p>
                    </div>
                    <div class="flex items-center space-x-2 sm:space-x-4 w-full sm:w-auto">
                        <!-- Notifications -->
                        <div class="relative">
                            <button onclick="toggleNotifications()" class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                                <i class="fas fa-bell text-xl"></i>
                                <span id="notification-count" class="absolute top-0 right-0 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center hidden">0</span>
                            </button>
                            <!-- Notification Dropdown -->
                            <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-900">Bildirimler</h3>
                                    <button onclick="markAllRead()" class="text-sm text-amber-600 hover:text-amber-700">Tümünü Okundu İşaretle</button>
                                </div>
                                <div id="notification-list" class="max-h-96 overflow-y-auto">
                                    <!-- Notifications will be loaded here -->
                                </div>
                            </div>
                        </div>
                        <!-- User Badge -->
                        <div class="flex items-center space-x-2 sm:space-x-3 px-2 sm:px-4 py-2 bg-gradient-to-r from-amber-50 to-amber-100 rounded-lg border border-amber-200">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-amber-500 to-amber-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white text-sm sm:text-base"></i>
                            </div>
                            <div class="hidden sm:block">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo ucfirst($_SESSION['rol']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Alerts -->
            <?php if (isset($error_message) && !empty($error_message)): ?>
            <div class="mx-6 mt-4 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            </div>
            <?php endif; ?>
            <?php if (isset($success_message) && !empty($success_message)): ?>
            <div class="mx-6 mt-4 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-4 sm:mb-6">
                    <!-- Toplam Masa -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Toplam Masa</p>
                                <p class="text-3xl font-bold text-gray-900" id="toplam-masa">-</p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-table text-white text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dolu Masa -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Dolu Masa</p>
                                <p class="text-3xl font-bold text-gray-900" id="dolu-masa">-</p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-check-circle text-white text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bekleyen Sipariş -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Bekleyen Sipariş</p>
                                <p class="text-3xl font-bold text-gray-900" id="bekleyen-siparis">-</p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-shopping-cart text-white text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Günlük Ciro -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Günlük Ciro</p>
                                <p class="text-3xl font-bold text-gray-900" id="gunluk-ciro">-</p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-lira-sign text-white text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($_SESSION['rol'] == 'admin'): ?>
                    <!-- Düşük Stok -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Düşük Stok</p>
                                <p class="text-3xl font-bold text-gray-900" id="dusuk-stok">-</p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Toplam Müşteri -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Toplam Müşteri</p>
                                <p class="text-3xl font-bold text-gray-900" id="toplam-musteri">-</p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-user-friends text-white text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Aktif Kampanya -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Aktif Kampanya</p>
                                <p class="text-3xl font-bold text-gray-900" id="aktif-kampanya">-</p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-tags text-white text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Charts & Tables -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                    <!-- Sales Chart -->
                    <?php if ($_SESSION['rol'] == 'admin'): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Satış Grafiği</h3>
                        <canvas id="salesChart" class="w-full" style="max-height: 300px;"></canvas>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Recent Orders -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Son Siparişler</h3>
                        <div id="son-siparisler" class="space-y-3">
                            <!-- Orders will be loaded here -->
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/keyboard-shortcuts.js"></script>
    <script>
        let salesChart = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboard();
            loadNotifications();
            loadChart('gunluk');
            setInterval(loadDashboard, 30000);
            setInterval(loadNotifications, 10000);
        });
        
        function toggleNotifications() {
            const dropdown = document.getElementById('notification-dropdown');
            dropdown.classList.toggle('hidden');
        }
        
        function loadNotifications() {
            fetch(apiUrl('bildirimler.php?action=say'))
                .then(response => {
                    if (!response.ok) return;
                    return response.json();
                })
                .then(data => {
                    if (!data) return;
                    const countEl = document.getElementById('notification-count');
                    countEl.textContent = data.sayi;
                    if (data.sayi > 0) {
                        countEl.classList.remove('hidden');
                    } else {
                        countEl.classList.add('hidden');
                    }
                });
            
            fetch(apiUrl('bildirimler.php?action=listele&okunmamis=1'))
                .then(response => {
                    if (!response.ok) return;
                    return response.json();
                })
                .then(data => {
                    if (!data) return;
                    const container = document.getElementById('notification-list');
                    if (data.length > 0) {
                        container.innerHTML = data.map(notif => `
                            <div class="p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors" onclick="readNotification(${notif.id})">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full bg-${notif.tip === 'danger' ? 'red' : notif.tip === 'success' ? 'green' : notif.tip === 'warning' ? 'yellow' : 'blue'}-100 flex items-center justify-center">
                                            <i class="fas fa-${notif.tip === 'danger' ? 'exclamation-circle' : notif.tip === 'success' ? 'check-circle' : notif.tip === 'warning' ? 'exclamation-triangle' : 'info-circle'} text-${notif.tip === 'danger' ? 'red' : notif.tip === 'success' ? 'green' : notif.tip === 'warning' ? 'yellow' : 'blue'}-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm font-medium text-gray-900">${notif.baslik}</p>
                                        <p class="text-sm text-gray-500 mt-1">${notif.mesaj}</p>
                                        <p class="text-xs text-gray-400 mt-1">${formatDateTime(notif.olusturma_tarihi)}</p>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<p class="text-center text-gray-500 py-8">Yeni bildirim yok</p>';
                    }
                });
        }
        
        function readNotification(id) {
            fetch(apiUrl('bildirimler.php?action=okundu'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(() => {
                loadNotifications();
            });
        }
        
        function markAllRead() {
            fetch(apiUrl('bildirimler.php?action=okundu_tumu'), { method: 'POST' })
                .then(() => {
                    loadNotifications();
                });
        }
        
        function loadDashboard() {
            fetch(apiUrl('dashboard.php?action=istatistikler'))
                .then(response => response.json())
                .then(data => {
                    document.getElementById('toplam-masa').textContent = data.toplam_masa || 0;
                    document.getElementById('dolu-masa').textContent = data.dolu_masa || 0;
                    document.getElementById('bekleyen-siparis').textContent = data.bekleyen_siparis || 0;
                    document.getElementById('gunluk-ciro').textContent = formatMoney(data.bugun_ciro || 0);
                    <?php if ($_SESSION['rol'] == 'admin'): ?>
                    document.getElementById('dusuk-stok').textContent = data.dusuk_stok || 0;
                    document.getElementById('toplam-musteri').textContent = data.toplam_musteri || 0;
                    document.getElementById('aktif-kampanya').textContent = data.aktif_kampanya || 0;
                    <?php endif; ?>
                })
                .catch(error => {
                    console.error('Hata:', error);
                });
            
            // Son siparişler
            fetch(apiUrl('siparis.php?action=listele&limit=5'))
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('son-siparisler');
                    if (data.length > 0) {
                        container.innerHTML = data.map(siparis => `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-receipt text-amber-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">${siparis.siparis_no}</p>
                                        <p class="text-xs text-gray-500">Masa ${siparis.masa_no}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900">${formatMoney(siparis.toplam_tutar)}</p>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-${siparis.durum === 'hazir' ? 'green' : siparis.durum === 'hazirlaniyor' ? 'yellow' : 'blue'}-100 text-${siparis.durum === 'hazir' ? 'green' : siparis.durum === 'hazirlaniyor' ? 'yellow' : 'blue'}-800">
                                        ${siparis.durum}
                                    </span>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<p class="text-center text-gray-500 py-8">Sipariş bulunamadı</p>';
                    }
                });
        }
        
        function loadChart(tip) {
            fetch(apiUrl(`dashboard.php?action=grafik_verileri&tip=${tip}`))
                .then(response => response.json())
                .then(data => {
                    const ctx = document.getElementById('salesChart');
                    if (!ctx) return;
                    
                    if (salesChart) {
                        salesChart.destroy();
                    }
                    
                    salesChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.map(d => formatDate(d.tarih || d.saat + ':00')),
                            datasets: [{
                                label: 'Satış (₺)',
                                data: data.map(d => d.toplam || 0),
                                borderColor: 'rgb(217, 119, 6)',
                                backgroundColor: 'rgba(217, 119, 6, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                });
        }
    </script>
</body>
</html>
