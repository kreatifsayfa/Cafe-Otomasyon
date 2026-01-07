<?php
require_once 'config/config.php';
checkLogin();
checkRole(['admin', 'kasiyer']);
$page_title = 'Raporlar';
?>
<?php include 'includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar_tailwind.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 z-40">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Raporlar</h1>
                        <p class="text-sm text-gray-500 mt-1">Satış raporlarını görüntüleyin ve analiz edin</p>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="exportExcel('siparisler')" class="bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-file-excel mr-2"></i>Excel Export
                        </button>
                        <button onclick="window.print()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-print mr-2"></i>Yazdır
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <div class="space-y-6">
                    <!-- Günlük Rapor -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Günlük Rapor</h2>
                            <input type="date" id="gunluk-tarih" value="<?php echo date('Y-m-d'); ?>" onchange="loadGunlukRapor()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div id="gunluk-rapor" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Günlük rapor buraya yüklenecek -->
                        </div>
                    </div>
                    
                    <!-- Aylık Rapor -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Aylık Rapor</h2>
                            <input type="month" id="aylik-ay" value="<?php echo date('Y-m'); ?>" onchange="loadAylikRapor()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div id="aylik-rapor" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Aylık rapor buraya yüklenecek -->
                        </div>
                    </div>
                    
                    <!-- En Çok Satan Ürünler -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">En Çok Satan Ürünler</h2>
                            <div class="flex space-x-2">
                                <input type="date" id="baslangic-tarih" value="<?php echo date('Y-m-01'); ?>" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                                <input type="date" id="bitis-tarih" value="<?php echo date('Y-m-d'); ?>" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                                <button onclick="loadEnCokSatan()" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold px-4 py-2 rounded-lg">
                                    <i class="fas fa-search mr-2"></i>Yükle
                                </button>
                            </div>
                        </div>
                        <div id="en-cok-satan" class="space-y-3">
                            <!-- En çok satan ürünler buraya yüklenecek -->
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/base_url_script.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/tailwind-helpers.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadGunlukRapor();
            loadAylikRapor();
            loadEnCokSatan();
        });
        
        function exportExcel(tip) {
            const tarih = document.getElementById('gunluk-tarih')?.value || '';
            const baslangic = document.getElementById('baslangic-tarih')?.value || '';
            const bitis = document.getElementById('bitis-tarih')?.value || '';
            
            let url = apiUrl(`export.php?tip=${tip}`);
            if (baslangic && bitis) {
                url += `&baslangic=${baslangic}&bitis=${bitis}`;
            } else if (tarih) {
                url += `&tarih=${tarih}`;
            }
            
            window.open(url, '_blank');
            showSuccess('Excel dosyası indiriliyor...');
        }
        
        function loadGunlukRapor() {
            const tarih = document.getElementById('gunluk-tarih').value;
            fetch(apiUrl(`raporlar.php?action=gunluk&tarih=${tarih}`))
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('gunluk-rapor');
                    container.innerHTML = `
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 border border-blue-200">
                            <p class="text-sm text-blue-600 mb-2">Toplam Sipariş</p>
                            <p class="text-3xl font-bold text-blue-900">${data.toplam_siparis || 0}</p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border border-green-200">
                            <p class="text-sm text-green-600 mb-2">Toplam Ciro</p>
                            <p class="text-3xl font-bold text-green-900">${formatMoney(data.toplam_ciro || 0)}</p>
                        </div>
                        <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl p-5 border border-amber-200">
                            <p class="text-sm text-amber-600 mb-2">Ortalama Sipariş</p>
                            <p class="text-3xl font-bold text-amber-900">${formatMoney(data.ortalama_siparis || 0)}</p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-5 border border-purple-200">
                            <p class="text-sm text-purple-600 mb-2">Toplam Müşteri</p>
                            <p class="text-3xl font-bold text-purple-900">${data.toplam_musteri || 0}</p>
                        </div>
                    `;
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Günlük rapor yüklenirken hata oluştu');
                });
        }
        
        function loadAylikRapor() {
            const ay = document.getElementById('aylik-ay').value;
            fetch(apiUrl(`raporlar.php?action=aylik&ay=${ay}`))
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('aylik-rapor');
                    container.innerHTML = `
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 border border-blue-200">
                            <p class="text-sm text-blue-600 mb-2">Toplam Sipariş</p>
                            <p class="text-3xl font-bold text-blue-900">${data.toplam_siparis || 0}</p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border border-green-200">
                            <p class="text-sm text-green-600 mb-2">Toplam Ciro</p>
                            <p class="text-3xl font-bold text-green-900">${formatMoney(data.toplam_ciro || 0)}</p>
                        </div>
                        <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl p-5 border border-amber-200">
                            <p class="text-sm text-amber-600 mb-2">Ortalama Sipariş</p>
                            <p class="text-3xl font-bold text-amber-900">${formatMoney(data.ortalama_siparis || 0)}</p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-5 border border-purple-200">
                            <p class="text-sm text-purple-600 mb-2">En İyi Gün</p>
                            <p class="text-3xl font-bold text-purple-900">${formatMoney(data.en_iyi_gun || 0)}</p>
                        </div>
                    `;
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Aylık rapor yüklenirken hata oluştu');
                });
        }
        
        function loadEnCokSatan() {
            const baslangic = document.getElementById('baslangic-tarih').value;
            const bitis = document.getElementById('bitis-tarih').value;
            
            fetch(apiUrl(`raporlar.php?action=en_cok_satan&baslangic=${baslangic}&bitis=${bitis}`))
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('en-cok-satan');
                    if (data.length > 0) {
                        container.innerHTML = data.map((urun, index) => `
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-amber-600 rounded-lg flex items-center justify-center text-white font-bold">
                                        ${index + 1}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">${urun.urun_adi}</p>
                                        <p class="text-sm text-gray-500">${urun.adet} adet satıldı</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-amber-600">${formatMoney(urun.toplam)}</p>
                                    <p class="text-xs text-gray-500">${formatMoney(urun.ortalama)} ortalama</p>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<p class="text-center text-gray-500 py-8">Veri bulunamadı</p>';
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('En çok satan ürünler yüklenirken hata oluştu');
                });
        }
    </script>
</body>
</html>
