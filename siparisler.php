<?php
require_once 'config/config.php';
checkLogin();
$page_title = 'Siparişler';
?>
<?php include 'includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar_tailwind.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <header class="bg-white shadow-sm border-b border-gray-200 z-40">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 sm:py-4 gap-3">
                    <div>
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900">Siparişler</h1>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Siparişleri görüntüleyin ve yönetin</p>
                    </div>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-4 w-full sm:w-auto">
                        <select id="masa-select" onchange="masaSiparisleriYukle()" class="w-full sm:w-auto px-3 sm:px-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            <option value="">Masa Seçin</option>
                        </select>
                        <button onclick="yeniSiparisModal()" class="w-full sm:w-auto bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold px-3 sm:px-4 py-2 text-sm sm:text-base rounded-lg transition-all">
                            <i class="fas fa-plus mr-2"></i>Yeni Sipariş
                        </button>
                    </div>
                </div>
            </header>
            
            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                <div id="masa-secimi-ekrani" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Masa Seçin</h2>
                    <div id="masalar-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                        <!-- Masalar buraya yüklenecek -->
                    </div>
                </div>
                
                <div id="siparis-ekrani" class="hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                        <!-- Siparişler Listesi -->
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-lg font-semibold text-gray-900">Aktif Siparişler</h2>
                                    <button onclick="siparisleriYenile()" class="text-amber-600 hover:text-amber-700">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div id="siparisler-list" class="space-y-3">
                                    <!-- Siparişler buraya yüklenecek -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sipariş Detayı ve İşlemler -->
                        <div class="space-y-6">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Sipariş Detayı</h2>
                                <div id="siparis-detay" class="space-y-3">
                                    <p class="text-gray-500 text-center py-8">Sipariş seçin</p>
                                </div>
                            </div>
                            
                            <!-- Sipariş Hazır Butonu (Şef/Barmen için) -->
                            <?php if (in_array($_SESSION['rol'], ['admin', 'sef', 'barmen', 'mutfak'])): ?>
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hidden" id="hazir-buton-card">
                                <button onclick="siparisHazir()" id="hazir-buton" class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-all">
                                    <i class="fas fa-check-circle mr-2"></i>Sipariş Hazır
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Sipariş İptal Modal -->
    <div id="iptal-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900">Sipariş İptal</h3>
                <button onclick="iptalModalKapat()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="iptal-form" onsubmit="siparisIptal(event)">
                <input type="hidden" id="iptal_siparis_id">
                <input type="hidden" id="iptal_detay_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">İptal Nedeni</label>
                        <textarea id="iptal_nedeni" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"></textarea>
                    </div>
                    <div class="flex space-x-3 pt-4">
                        <button type="submit" class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg transition-all">
                            <i class="fas fa-times mr-2"></i>İptal Et
                        </button>
                        <button type="button" onclick="iptalModalKapat()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-colors">
                            Vazgeç
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Yeni Sipariş Modal -->
    <div id="yeni-siparis-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900">Yeni Sipariş</h3>
                <button onclick="yeniSiparisModalKapat()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Masa Seç</label>
                    <select id="yeni-siparis-masa" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <option value="">Masa Seçin</option>
                    </select>
                </div>
                <div class="flex space-x-3 pt-4">
                    <button onclick="yeniSiparisOlustur()" class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-2 px-4 rounded-lg transition-all">
                        <i class="fas fa-arrow-right mr-2"></i>Devam Et
                    </button>
                    <button type="button" onclick="yeniSiparisModalKapat()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-colors">
                        İptal
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/base_url_script.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/tailwind-helpers.js"></script>
    <script>
        let seciliMasaId = null;
        let seciliSiparisId = null;
        
        function durumAdiGetir(durum) {
            const durumlar = {
                'beklemede': 'Beklemede',
                'hazirlaniyor': 'Hazırlanıyor',
                'pişiriliyor': 'Pişiriliyor',
                'hazir': 'Hazır',
                'teslim_edildi': 'Teslim Edildi',
                'iptal': 'İptal'
            };
            return durumlar[durum] || durum;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            masalariYukle();
            setInterval(() => {
                if (seciliMasaId) {
                    masaSiparisleriYukle();
                }
            }, 5000);
        });
        
        function masalariYukle() {
            fetch(apiUrl('masalar.php?action=listele'))
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('masalar-grid');
                    container.innerHTML = data.map(masa => `
                        <button onclick="masaSec(${masa.id}, '${masa.masa_no}')" class="p-4 bg-white border-2 ${masa.durum === 'dolu' ? 'border-amber-500' : 'border-gray-200'} rounded-xl hover:shadow-lg transition-all text-center">
                            <div class="text-2xl font-bold text-gray-900 mb-1">${masa.masa_no}</div>
                            <div class="text-sm text-gray-500">${masa.kapasite} Kişi</div>
                            <div class="mt-2">
                                <span class="px-2 py-1 rounded-full text-xs font-medium ${masa.durum === 'dolu' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800'}">
                                    ${masa.durum === 'dolu' ? 'Dolu' : 'Boş'}
                                </span>
                            </div>
                        </button>
                    `).join('');
                    
                    // Select için de ekle
                    const select = document.getElementById('masa-select');
                    select.innerHTML = '<option value="">Masa Seçin</option>';
                    data.forEach(masa => {
                        const option = new Option(`Masa ${masa.masa_no}`, masa.id);
                        select.add(option);
                    });
                    
                    // Yeni sipariş modal için
                    const yeniSelect = document.getElementById('yeni-siparis-masa');
                    yeniSelect.innerHTML = '<option value="">Masa Seçin</option>';
                    data.filter(m => m.durum === 'bos').forEach(masa => {
                        const option = new Option(`Masa ${masa.masa_no}`, masa.id);
                        yeniSelect.add(option);
                    });
                });
        }
        
        function masaSec(masaId, masaNo) {
            seciliMasaId = masaId;
            document.getElementById('masa-select').value = masaId;
            document.getElementById('masa-secimi-ekrani').classList.add('hidden');
            document.getElementById('siparis-ekrani').classList.remove('hidden');
            masaSiparisleriYukle();
        }
        
        function masaSiparisleriYukle() {
            const masaId = document.getElementById('masa-select').value || seciliMasaId;
            if (!masaId) {
                document.getElementById('masa-secimi-ekrani').classList.remove('hidden');
                document.getElementById('siparis-ekrani').classList.add('hidden');
                return;
            }
            
            seciliMasaId = masaId;
            document.getElementById('masa-secimi-ekrani').classList.add('hidden');
            document.getElementById('siparis-ekrani').classList.remove('hidden');
            
            fetch(apiUrl(`siparis.php?action=listele&masa_id=${masaId}`))
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('siparisler-list');
                    if (data.length > 0) {
                        container.innerHTML = data.map(siparis => `
                            <div onclick="siparisSec(${siparis.id})" class="p-4 bg-gray-50 border-2 ${seciliSiparisId == siparis.id ? 'border-amber-500' : 'border-gray-200'} rounded-xl hover:shadow-lg transition-all cursor-pointer">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-bold text-gray-900">${siparis.siparis_no}</h3>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${siparis.durum === 'hazir' ? 'bg-green-100 text-green-800' : siparis.durum === 'hazirlaniyor' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'}">
                                        ${durumAdiGetir(siparis.durum)}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <p>Toplam: ${formatMoney(siparis.toplam_tutar)}</p>
                                    <p>Tarih: ${formatDateTime(siparis.olusturma_tarihi)}</p>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<p class="text-gray-500 text-center py-8">Bu masa için sipariş bulunamadı</p>';
                    }
                });
        }
        
        const iptalYetkisi = <?php echo (checkPermission($db, 'siparis_iptal') || $_SESSION['rol'] == 'admin') ? 'true' : 'false'; ?>;
        
        function siparisSec(siparisId) {
            seciliSiparisId = siparisId;
            fetch(apiUrl(`siparis.php?action=detay&siparis_id=${siparisId}`))
                .then(response => response.json())
                .then(detaylar => {
                    const container = document.getElementById('siparis-detay');
                    container.innerHTML = `
                        <div class="space-y-3">
                            ${detaylar.map(detay => `
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900">${detay.urun_adi}</p>
                                        <p class="text-sm text-gray-500">${detay.adet}x ${formatMoney(detay.birim_fiyat)} = ${formatMoney(detay.toplam_fiyat)}</p>
                                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-medium ${detay.durum === 'hazir' ? 'bg-green-100 text-green-800' : detay.durum === 'iptal' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'}">
                                            ${durumAdiGetir(detay.durum)}
                                        </span>
                                    </div>
                                    ${detay.durum !== 'iptal' && detay.durum !== 'teslim_edildi' ? `
                                        <div class="flex space-x-2">
                                            ${detay.durum !== 'hazir' ? `
                                                <button onclick="siparisDetayHazir(${detay.id})" class="text-green-600 hover:text-green-700" title="Hazır">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            ` : ''}
                                            ${iptalYetkisi ? `
                                            <button onclick="siparisDetayIptal(${detay.id})" class="text-red-600 hover:text-red-700" title="İptal">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            ` : ''}
                                        </div>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    `;
                    
                    // Hazır buton kontrolü (şef/barmen için)
                    const hazirCard = document.getElementById('hazir-buton-card');
                    if (hazirCard) {
                        const hazirOlmayan = detaylar.filter(d => 
                            d.durum !== 'hazir' && 
                            d.durum !== 'iptal' && 
                            d.durum !== 'teslim_edildi'
                        );
                        if (hazirOlmayan.length > 0) {
                            hazirCard.classList.remove('hidden');
                        } else {
                            hazirCard.classList.add('hidden');
                        }
                    }
                });
        }
        
        function siparisDetayHazir(detayId) {
            fetch(apiUrl('siparis.php?action=siparis_hazir'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    siparis_id: seciliSiparisId,
                    detay_id: detayId
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Sipariş hazır işaretlendi');
                    siparisSec(seciliSiparisId);
                    masaSiparisleriYukle();
                } else {
                    showError(result.message || 'Hata oluştu');
                }
            });
        }
        
        function siparisDetayIptal(detayId) {
            document.getElementById('iptal_siparis_id').value = seciliSiparisId;
            document.getElementById('iptal_detay_id').value = detayId;
            document.getElementById('iptal_nedeni').value = '';
            document.getElementById('iptal-modal').classList.remove('hidden');
        }
        
        function siparisIptal(e) {
            e.preventDefault();
            const data = {
                siparis_id: document.getElementById('iptal_siparis_id').value,
                detay_id: document.getElementById('iptal_detay_id').value || null,
                iptal_nedeni: document.getElementById('iptal_nedeni').value
            };
            
            fetch(apiUrl('siparis.php?action=iptal'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Sipariş iptal edildi');
                    iptalModalKapat();
                    siparisSec(seciliSiparisId);
                    masaSiparisleriYukle();
                } else {
                    showError(result.message || 'Hata oluştu');
                }
            });
        }
        
        function siparisHazir() {
            fetch(apiUrl('siparis.php?action=siparis_hazir'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    siparis_id: seciliSiparisId
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Sipariş hazır işaretlendi');
                    siparisSec(seciliSiparisId);
                    masaSiparisleriYukle();
                } else {
                    showError(result.message || 'Hata oluştu');
                }
            });
        }
        
        function iptalModalKapat() {
            document.getElementById('iptal-modal').classList.add('hidden');
        }
        
        function yeniSiparisModal() {
            document.getElementById('yeni-siparis-modal').classList.remove('hidden');
        }
        
        function yeniSiparisModalKapat() {
            document.getElementById('yeni-siparis-modal').classList.add('hidden');
        }
        
        function yeniSiparisOlustur() {
            const masaId = document.getElementById('yeni-siparis-masa').value;
            if (!masaId) {
                showError('Lütfen masa seçin');
                return;
            }
            
            window.location.href = url(`siparis.php?masa_id=${masaId}&masa_no=Masa ${masaId}`);
        }
        
        function siparisleriYenile() {
            masaSiparisleriYukle();
        }
    </script>
</body>
</html>

