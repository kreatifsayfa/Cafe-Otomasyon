<?php
require_once 'config/config.php';
checkLogin();
// Sadece barmen, şef ve admin görebilir
checkRole(['admin', 'sef', 'barmen']);
$page_title = 'Mutfak Görünümü';
?>
<?php include 'includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar_tailwind.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <header class="bg-white shadow-sm border-b border-gray-200 z-40">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Mutfak Görünümü</h1>
                        <p class="text-sm text-gray-500 mt-1">Siparişleri takip edin ve durumlarını güncelleyin</p>
                    </div>
                    <button onclick="loadSiparisler()" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold px-6 py-2.5 rounded-lg shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-sync-alt mr-2"></i>Yenile
                    </button>
                </div>
            </header>
            
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Bilgilendirme -->
                <div class="mb-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                        <div class="flex-1">
                            <p class="text-sm text-blue-800">
                                <strong>Önemli:</strong> Siparişleri görüntüleyin ve hazır olduğunda "Hazır" butonuna tıklayın. 
                                Sipariş hazır işaretlenmeden ödeme alınamaz.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Beklemede -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Bekleyen Siparişler</h2>
                            <span id="beklemede-count" class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">0</span>
                        </div>
                        <div id="beklemede-list" class="space-y-3 max-h-[calc(100vh-300px)] overflow-y-auto">
                            <!-- Bekleyen siparişler -->
                        </div>
                    </div>
                    
                    <!-- Hazır -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Hazır Siparişler</h2>
                            <span id="hazir-count" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">0</span>
                        </div>
                        <div id="hazir-list" class="space-y-3 max-h-[calc(100vh-300px)] overflow-y-auto">
                            <!-- Hazır siparişler -->
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
            loadSiparisler();
            setInterval(loadSiparisler, 10000);
        });
        
        function loadSiparisler() {
            // Sadece ödenmemiş ve iptal edilmemiş siparişleri getir
            fetch(apiUrl('siparis.php?action=mutfak_listele'))
                .then(response => response.json())
                .then(data => {
                    // Beklemede ve hazırlanıyor durumundaki siparişler "beklemede" kolonunda
                    const beklemede = data.filter(s => s.durum === 'beklemede' || s.durum === 'hazirlaniyor');
                    // Hazır durumundaki siparişler
                    const hazir = data.filter(s => s.durum === 'hazir');
                    
                    document.getElementById('beklemede-count').textContent = beklemede.length;
                    document.getElementById('hazir-count').textContent = hazir.length;
                    
                    renderSiparisler('beklemede-list', beklemede);
                    renderSiparisler('hazir-list', hazir);
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Siparişler yüklenirken hata oluştu');
                });
        }
        
        function renderSiparisler(containerId, siparisler) {
            const container = document.getElementById(containerId);
            
            if (siparisler.length === 0) {
                container.innerHTML = '<p class="text-center text-gray-500 py-8">Sipariş yok</p>';
                return;
            }
            
            container.innerHTML = siparisler.map(siparis => `
                <div class="bg-gray-50 border-2 border-gray-200 rounded-xl p-4 hover:border-amber-500 hover:shadow-lg transition-all">
                    <div class="flex items-center justify-between mb-3 pb-3 border-b border-gray-200">
                        <h3 class="font-bold text-gray-900">${siparis.siparis_no}</h3>
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">Masa ${siparis.masa_no}</span>
                    </div>
                    <div id="detay-${siparis.id}" class="mb-3 space-y-2">
                        <p class="text-sm text-gray-500">Yükleniyor...</p>
                    </div>
                    <div class="flex space-x-2">
                        ${siparis.durum === 'beklemede' || siparis.durum === 'hazirlaniyor' ? `
                            <button onclick="siparisHazirYap(${siparis.id})" class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-all shadow-lg hover:shadow-xl">
                                <i class="fas fa-check-circle mr-2"></i>Hazır
                            </button>
                        ` : ''}
                        ${siparis.durum === 'hazir' ? `
                            <div class="w-full bg-green-100 text-green-800 font-semibold py-3 px-4 rounded-lg text-center">
                                <i class="fas fa-check-double mr-2"></i>Hazır - Ödeme Alınabilir
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');
            
            siparisler.forEach(siparis => {
                loadSiparisDetay(siparis.id);
            });
        }
        
        function loadSiparisDetay(siparisId) {
            fetch(apiUrl(`siparis.php?action=detay&siparis_id=${siparisId}`))
                .then(response => response.json())
                .then(detaylar => {
                    const container = document.getElementById(`detay-${siparisId}`);
                    container.innerHTML = detaylar.map(detay => `
                        <div class="flex items-center justify-between p-2 bg-white rounded-lg">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-amber-600">${detay.adet}x</span>
                                <span class="text-sm text-gray-700">${detay.urun_adi}</span>
                            </div>
                        </div>
                    `).join('');
                })
                .catch(error => console.error('Hata:', error));
        }
        
        function siparisHazirYap(siparisId) {
            if (!confirm('Bu siparişi hazır olarak işaretlemek istediğinize emin misiniz? Hazır işaretlendikten sonra ödeme alınabilir.')) {
                return;
            }
            
            fetch(apiUrl('siparis.php?action=siparis_hazir'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ siparis_id: siparisId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Sipariş hazır olarak işaretlendi! Artık ödeme alınabilir.');
                    loadSiparisler();
                } else {
                    showError(data.message || 'Hata oluştu');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showError('İşlem sırasında hata oluştu');
            });
        }
    </script>
</body>
</html>
