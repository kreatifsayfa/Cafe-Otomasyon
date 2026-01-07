<?php
require_once 'config/config.php';
checkLogin();
$page_title = 'Hesap Kesme';
?>
<?php include 'includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar_tailwind.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 z-40">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 sm:py-4 gap-3">
                    <div>
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900">Hesap Kesme</h1>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Siparişleri görüntüleyin ve ödeme alın</p>
                    </div>
                    <select id="masa-select" onchange="masaSiparisleriYukle()" class="w-full sm:w-auto px-3 sm:px-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <option value="">Masa Seçin</option>
                    </select>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                    <!-- Siparişler -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Siparişler</h2>
                        <div id="siparisler-list" class="space-y-3">
                            <p class="text-gray-500 text-center py-8">Lütfen masa seçin</p>
                        </div>
                    </div>
                    
                    <!-- Hesap Detayı -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hidden" id="hesap-detay-card">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Hesap Detayı</h2>
                        <div id="hesap-detay" class="mb-6">
                            <!-- Hesap detayları buraya yüklenecek -->
                        </div>
                        
                        <div class="space-y-4 border-t border-gray-200 pt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kampanya Seç (Opsiyonel)</label>
                                <select id="kampanya_sec" onchange="kampanyaSecildi()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                    <option value="">Kampanya Seçin</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">İndirim (₺)</label>
                                <input type="number" id="indirim_tutari" step="0.01" min="0" value="0" onchange="hesapHesapla()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ödeme Tipi</label>
                                <select id="odeme_tipi" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                    <option value="nakit">Nakit</option>
                                    <option value="kredi_karti">Kredi Kartı</option>
                                    <option value="havale">Havale</option>
                                    <option value="karma">Karma</option>
                                </select>
                            </div>
                            
                            <!-- Özet -->
                            <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Ara Toplam:</span>
                                    <span class="font-medium text-gray-900" id="ara-toplam">0,00 ₺</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">KDV (%20):</span>
                                    <span class="font-medium text-gray-900" id="kdv-tutar">0,00 ₺</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">İndirim:</span>
                                    <span class="font-medium text-red-600" id="indirim-goster">0,00 ₺</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold pt-3 border-t border-gray-200">
                                    <span class="text-gray-900">Genel Toplam:</span>
                                    <span class="text-amber-600" id="genel-toplam">0,00 ₺</span>
                                </div>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button onclick="hesapKes()" id="hesap-kes-btn" class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-3 px-4 rounded-lg shadow-lg hover:shadow-xl transition-all">
                                    <i class="fas fa-cash-register mr-2"></i>Hesabı Kes ve Ödeme Al
                                </button>
                                <button onclick="fisYazdir()" id="fis-btn" class="hidden bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-4 rounded-lg transition-colors">
                                    <i class="fas fa-print mr-2"></i>Fiş Yazdır
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Karma Ödeme Modal -->
    <div id="karma-odeme-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Karma Ödeme</h3>
                    <button onclick="karmaOdemeModalKapat()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-2">Toplam Tutar:</p>
                    <p class="text-2xl font-bold text-amber-600" id="karma-toplam-tutar">0,00 ₺</p>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nakit Tutarı (₺)</label>
                        <input type="number" id="karma-nakit" step="0.01" min="0" value="0" oninput="karmaOdemeHesapla()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kart Tutarı (₺)</label>
                        <input type="number" id="karma-kart" step="0.01" min="0" value="0" oninput="karmaOdemeHesapla()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Nakit:</span>
                            <span class="font-medium text-gray-900" id="karma-nakit-goster">0,00 ₺</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Kart:</span>
                            <span class="font-medium text-gray-900" id="karma-kart-goster">0,00 ₺</span>
                        </div>
                        <div class="flex justify-between text-sm font-bold pt-2 border-t border-gray-200">
                            <span class="text-gray-900">Toplam:</span>
                            <span class="text-amber-600" id="karma-toplam-goster">0,00 ₺</span>
                        </div>
                        <div id="karma-fark" class="mt-2 text-sm font-semibold"></div>
                    </div>
                </div>
                
                <div class="flex space-x-3 mt-6">
                    <button onclick="karmaOdemeModalKapat()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-4 rounded-lg transition-colors">
                        İptal
                    </button>
                    <button onclick="karmaOdemeOnayla()" id="karma-onay-btn" disabled class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-3 px-4 rounded-lg shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-check mr-2"></i>Onayla
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
        let birlesikSiparisler = [];
        
        document.addEventListener('DOMContentLoaded', function() {
            loadMasalar();
            loadAktifKampanyalar();
        });
        
        function loadAktifKampanyalar() {
            fetch(apiUrl('kampanya.php?action=listele&durum=aktif'))
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('kampanya_sec');
                    const bugun = new Date();
                    const aktifKampanyalar = data.filter(k => {
                        const baslangic = new Date(k.baslangic_tarihi);
                        const bitis = new Date(k.bitis_tarihi);
                        return bugun >= baslangic && bugun <= bitis;
                    });
                    
                    aktifKampanyalar.forEach(kampanya => {
                        const indirimText = kampanya.indirim_tipi === 'yuzde' 
                            ? `%${kampanya.indirim_degeri}` 
                            : `${formatMoney(kampanya.indirim_degeri)}`;
                        const option = new Option(`${kampanya.kampanya_adi} (${indirimText})`, kampanya.id);
                        option.dataset.kampanya = JSON.stringify(kampanya);
                        select.add(option);
                    });
                })
                .catch(error => console.error('Hata:', error));
        }
        
        function kampanyaSecildi() {
            const select = document.getElementById('kampanya_sec');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption.value) {
                const kampanya = JSON.parse(selectedOption.dataset.kampanya);
                
                // Birleşik hesap toplamını al
                if (!seciliMasaId || !birlesikSiparisler || birlesikSiparisler.length === 0) {
                    showError('Lütfen önce masa seçin');
                    select.value = '';
                    return;
                }
                
                const toplamTutar = birlesikSiparisler.reduce((sum, s) => sum + parseFloat(s.toplam_tutar), 0);
                
                if (kampanya.min_tutar > 0 && toplamTutar < kampanya.min_tutar) {
                    showError(`Bu kampanya için minimum ${formatMoney(kampanya.min_tutar)} tutarında sipariş gerekli`);
                    select.value = '';
                    return;
                }
                
                let indirim = 0;
                if (kampanya.indirim_tipi === 'yuzde') {
                    indirim = toplamTutar * (kampanya.indirim_degeri / 100);
                } else {
                    indirim = kampanya.indirim_degeri;
                }
                
                document.getElementById('indirim_tutari').value = indirim.toFixed(2);
                hesapHesapla();
                showSuccess(`Kampanya uygulandı: ${kampanya.kampanya_adi}`);
            } else {
                document.getElementById('indirim_tutari').value = 0;
                hesapHesapla();
            }
        }
        
        function loadMasalar() {
            fetch(apiUrl('masalar.php'))
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('masa-select');
                    data.forEach(masa => {
                        if (masa.durum === 'dolu' || masa.durum === 'rezerve') {
                            const option = new Option(`Masa ${masa.masa_no}`, masa.id);
                            select.add(option);
                        }
                    });
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Masalar yüklenirken hata oluştu');
                });
        }
        
        function masaSiparisleriYukle() {
            const masaId = document.getElementById('masa-select').value;
            if (!masaId) {
                document.getElementById('siparisler-list').innerHTML = '<p class="text-gray-500 text-center py-8">Lütfen masa seçin</p>';
                document.getElementById('hesap-detay-card').classList.add('hidden');
                return;
            }
            
            // Masa seçildiğinde otomatik olarak tüm siparişleri birleştirerek göster
            masaHesapYukle(masaId);
        }
        
        function masaHesapYukle(masaId) {
            fetch(apiUrl(`siparis.php?action=masa_hesap&masa_id=${masaId}`))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.siparisler && data.siparisler.length > 0) {
                        // Sipariş listesini göster (bilgi amaçlı)
                        const container = document.getElementById('siparisler-list');
                        container.innerHTML = `
                            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <p class="text-sm text-blue-800"><i class="fas fa-info-circle mr-2"></i>Bu masada ${data.siparisler.length} adet ödenmemiş sipariş bulunmaktadır. Tüm siparişler birleşik olarak ödenecektir.</p>
                            </div>
                            ${data.siparisler.map(siparis => {
                                const hazirMi = siparis.durum === 'hazir' || siparis.durum === 'teslim_edildi';
                                return `
                                    <div class="p-3 border border-gray-200 rounded-lg mb-2 ${hazirMi ? 'bg-green-50' : 'bg-red-50'}">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">${siparis.siparis_no}</p>
                                                <p class="text-xs text-gray-500">${formatDateTime(siparis.olusturma_tarihi)}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-bold text-amber-600">${formatMoney(siparis.toplam_tutar)}</p>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${hazirMi ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                                    ${hazirMi ? 'Hazır' : 'Hazır Değil'}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        `;
                        
                        // Birleşik hesap detayını göster
                        seciliMasaId = masaId;
                        birlesikHesapGoster(data);
                    } else {
                        document.getElementById('siparisler-list').innerHTML = '<p class="text-gray-500 text-center py-8">Bu masada ödenmemiş sipariş yok</p>';
                        document.getElementById('hesap-detay-card').classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Siparişler yüklenirken hata oluştu');
                });
        }
        
        let seciliMasaId = null;
        let birlesikSiparisler = [];
        
        function birlesikHesapGoster(data) {
            birlesikSiparisler = data.siparisler;
            const tumDetaylar = data.detaylar || [];
            
            document.getElementById('hesap-detay-card').classList.remove('hidden');
            
            // Tüm siparişlerin hazır olup olmadığını kontrol et
            const hazirOlmayan = birlesikSiparisler.filter(s => s.durum !== 'hazir' && s.durum !== 'teslim_edildi');
            
            let html = '';
            
            // Hazır olmayan siparişler varsa uyarı göster
            if (hazirOlmayan.length > 0) {
                html += `
                    <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                            <div>
                                <p class="font-semibold text-red-900">${hazirOlmayan.length} Sipariş Henüz Hazır Değil!</p>
                                <p class="text-sm text-red-700 mt-1">Hazır olmayan siparişler: ${hazirOlmayan.map(s => s.siparis_no).join(', ')}</p>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Sipariş numaralarını göster
            html += `
                <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm font-medium text-gray-700 mb-2">Sipariş Numaraları:</p>
                    <div class="flex flex-wrap gap-2">
                        ${birlesikSiparisler.map(s => `
                            <span class="px-2 py-1 bg-white border border-gray-300 rounded text-xs font-semibold">${s.siparis_no}</span>
                        `).join('')}
                    </div>
                </div>
            `;
            
            html += '<div class="space-y-2 mb-4">';
            
            // Ürünleri grupla (aynı ürünler birleştirilsin)
            const urunMap = {};
            tumDetaylar.forEach(detay => {
                const key = detay.urun_id;
                if (!urunMap[key]) {
                    urunMap[key] = {
                        urun_adi: detay.urun_adi,
                        toplam_adet: 0,
                        toplam_fiyat: 0,
                        birim_fiyat: detay.birim_fiyat
                    };
                }
                urunMap[key].toplam_adet += detay.adet;
                urunMap[key].toplam_fiyat += detay.toplam_fiyat;
            });
            
            // Gruplanmış ürünleri göster
            Object.values(urunMap).forEach(urun => {
                html += `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">${urun.urun_adi}</p>
                            <p class="text-sm text-gray-500">${urun.toplam_adet} x ${formatMoney(urun.birim_fiyat)}</p>
                        </div>
                        <p class="font-semibold text-gray-900">${formatMoney(urun.toplam_fiyat)}</p>
                    </div>
                `;
            });
            
            html += '</div>';
            document.getElementById('hesap-detay').innerHTML = html;
            
            // Hesap kes butonunu duruma göre aktif/pasif yap
            const hesapKesBtn = document.getElementById('hesap-kes-btn');
            if (hesapKesBtn) {
                if (hazirOlmayan.length > 0) {
                    hesapKesBtn.disabled = true;
                    hesapKesBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    hesapKesBtn.title = 'Tüm siparişler hazır olmadığı için ödeme alınamaz';
                } else {
                    hesapKesBtn.disabled = false;
                    hesapKesBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    hesapKesBtn.title = '';
                }
            }
            
            birlesikHesapHesapla(data);
        }
        
        function birlesikHesapHesapla(data) {
            const toplamTutar = data.toplam_tutar || 0;
            const indirim = parseFloat(document.getElementById('indirim_tutari').value) || 0;
            const araToplam = toplamTutar - indirim;
            const kdv = araToplam * 0.20;
            const genelToplam = araToplam + kdv;
            
            document.getElementById('ara-toplam').textContent = formatMoney(araToplam);
            document.getElementById('kdv-tutar').textContent = formatMoney(kdv);
            document.getElementById('indirim-goster').textContent = formatMoney(indirim);
            document.getElementById('genel-toplam').textContent = formatMoney(genelToplam);
        }
        
        // Eski siparisSec fonksiyonu kaldırıldı - artık masa seçildiğinde otomatik birleşik hesap gösteriliyor
        
        function hesapHesapla() {
            if (!seciliMasaId) return;
            
            // Birleşik hesap hesaplama
            fetch(apiUrl(`siparis.php?action=masa_hesap&masa_id=${seciliMasaId}`))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        birlesikHesapHesapla(data);
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Hesaplama yapılırken hata oluştu');
                });
        }
        
        function hesapKes() {
            if (!seciliMasaId || !birlesikSiparisler || birlesikSiparisler.length === 0) {
                showError('Lütfen masa seçin');
                return;
            }
            
            // Tüm siparişlerin hazır olup olmadığını kontrol et
            const hazirOlmayan = birlesikSiparisler.filter(s => s.durum !== 'hazir' && s.durum !== 'teslim_edildi');
            if (hazirOlmayan.length > 0) {
                showError(`${hazirOlmayan.length} sipariş henüz hazır değil! Mutfak görünümünden siparişlerin hazır olduğunu onaylamanız gerekiyor.`);
                return;
            }
            
            const indirim = parseFloat(document.getElementById('indirim_tutari').value) || 0;
            const odemeTipi = document.getElementById('odeme_tipi').value;
            
            // Toplam tutarı hesapla
            const toplamTutar = birlesikSiparisler.reduce((sum, s) => sum + parseFloat(s.toplam_tutar), 0);
            const araToplam = toplamTutar - indirim;
            const kdv = araToplam * 0.20;
            const genelToplam = araToplam + kdv;
            
            // Karma ödeme için modal aç
            if (odemeTipi === 'karma') {
                karmaOdemeModalAc(genelToplam);
                return;
            }
            
            if (!confirm(`Bu masadaki ${birlesikSiparisler.length} siparişin hesabını kesmek istediğinize emin misiniz?`)) {
                return;
            }
            
            hesapKesDevam(indirim, odemeTipi);
        }
        
        function hesapKesDevam(indirim, odemeTipi, nakitTutar = null, kartTutar = null) {
            const siparisIds = birlesikSiparisler.map(s => s.id);
            
            const payload = {
                masa_id: seciliMasaId,
                siparis_ids: siparisIds,
                indirim_tutari: indirim,
                odeme_tipi: odemeTipi
            };
            
            // Karma ödeme için nakit ve kart tutarlarını ekle
            if (odemeTipi === 'karma' && nakitTutar !== null && kartTutar !== null) {
                payload.nakit_tutar = nakitTutar;
                payload.kart_tutar = kartTutar;
            }
            
            fetch(apiUrl('odeme.php?action=masa_hesap_kes'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(`${siparisIds.length} siparişin hesabı başarıyla kesildi!`);
                    document.getElementById('fis-btn').classList.remove('hidden');
                    seciliMasaId = null;
                    birlesikSiparisler = [];
                    document.getElementById('masa-select').value = '';
                    document.getElementById('hesap-detay-card').classList.add('hidden');
                    document.getElementById('siparisler-list').innerHTML = '<p class="text-gray-500 text-center py-8">Lütfen masa seçin</p>';
                    loadMasalar();
                } else {
                    showError(data.message || 'Hesap kesilirken hata oluştu');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showError('Hesap kesilirken hata oluştu');
            });
        }
        
        function fisYazdir() {
            if (!seciliSiparis) {
                showError('Lütfen önce bir sipariş seçin');
                return;
            }
            
            fetch(apiUrl('yazici.php?action=siparis_yazdir'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    siparis_id: seciliSiparis.id,
                    lokasyon: 'kasa'
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Fiş yazdırıldı');
                } else {
                    showError(result.message || 'Yazdırma hatası');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showError('Yazdırma sırasında hata oluştu');
            });
        }
        
        function fisYazdirOld() {
            if (!seciliSiparis) {
                showError('Lütfen sipariş seçin');
                return;
            }
            
            window.open(apiUrl(`odeme.php?action=fis&siparis_id=${seciliSiparis}`), '_blank');
        }
        
        // Karma ödeme modal fonksiyonları
        function karmaOdemeModalAc(genelToplam) {
            document.getElementById('karma-toplam-tutar').textContent = formatMoney(genelToplam);
            document.getElementById('karma-nakit').value = '';
            document.getElementById('karma-kart').value = '';
            document.getElementById('karma-nakit').max = genelToplam;
            document.getElementById('karma-kart').max = genelToplam;
            document.getElementById('karma-odeme-modal').classList.remove('hidden');
            karmaOdemeHesapla();
        }
        
        function karmaOdemeModalKapat() {
            document.getElementById('karma-odeme-modal').classList.add('hidden');
        }
        
        function karmaOdemeHesapla() {
            const nakit = parseFloat(document.getElementById('karma-nakit').value) || 0;
            const kart = parseFloat(document.getElementById('karma-kart').value) || 0;
            const toplam = parseFloat(document.getElementById('karma-toplam-tutar').textContent.replace(/[^\d,]/g, '').replace(',', '.')) || 0;
            
            document.getElementById('karma-nakit-goster').textContent = formatMoney(nakit);
            document.getElementById('karma-kart-goster').textContent = formatMoney(kart);
            document.getElementById('karma-toplam-goster').textContent = formatMoney(nakit + kart);
            
            const fark = (nakit + kart) - toplam;
            const farkDiv = document.getElementById('karma-fark');
            const onayBtn = document.getElementById('karma-onay-btn');
            
            if (Math.abs(fark) < 0.01) {
                // Tam eşit
                farkDiv.innerHTML = '<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Tutar eşleşiyor</span>';
                onayBtn.disabled = false;
            } else if (fark > 0) {
                // Fazla ödeme
                farkDiv.innerHTML = `<span class="text-red-600"><i class="fas fa-exclamation-triangle mr-1"></i>Fazla ödeme: ${formatMoney(fark)}</span>`;
                onayBtn.disabled = true;
            } else {
                // Eksik ödeme
                farkDiv.innerHTML = `<span class="text-orange-600"><i class="fas fa-info-circle mr-1"></i>Eksik: ${formatMoney(Math.abs(fark))}</span>`;
                onayBtn.disabled = true;
            }
        }
        
        function karmaOdemeOnayla() {
            const nakit = parseFloat(document.getElementById('karma-nakit').value) || 0;
            const kart = parseFloat(document.getElementById('karma-kart').value) || 0;
            const toplam = parseFloat(document.getElementById('karma-toplam-tutar').textContent.replace(/[^\d,]/g, '').replace(',', '.')) || 0;
            
            if (Math.abs((nakit + kart) - toplam) >= 0.01) {
                showError('Nakit ve kart tutarlarının toplamı, genel toplam ile eşleşmelidir');
                return;
            }
            
            if (!confirm('Karma ödeme ile hesabı kesmek istediğinize emin misiniz?')) {
                return;
            }
            
            const indirim = parseFloat(document.getElementById('indirim_tutari').value) || 0;
            karmaOdemeModalKapat();
            hesapKesDevam(indirim, 'karma', nakit, kart);
        }
    </script>
</body>
</html>
