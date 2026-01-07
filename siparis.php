<?php
require_once 'config/config.php';
checkLogin();

$masa_id = isset($_GET['masa_id']) ? intval($_GET['masa_id']) : 0;
$masa_no = isset($_GET['masa_no']) ? cleanInput($_GET['masa_no']) : '';
$page_title = 'Sipariş Al';
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
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900">Sipariş Al - Masa <?php echo htmlspecialchars($masa_no); ?></h1>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Ürün seçin ve sipariş verin</p>
                    </div>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-4 w-full sm:w-auto">
                        <select id="musteri-select" onchange="musteriSecildi()" class="w-full sm:w-auto px-3 sm:px-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            <option value="">Müşteri Seç (Opsiyonel)</option>
                        </select>
                        <a href="<?php echo BASE_URL; ?>index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-3 sm:px-4 py-2 text-sm sm:text-base rounded-lg transition-colors text-center">
                            <i class="fas fa-arrow-left mr-2"></i>Geri
                        </a>
                    </div>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 sm:gap-6 h-full">
                    <!-- Left Sidebar: Kategoriler & Sepet -->
                    <div class="lg:col-span-1 space-y-4 sm:space-y-6 order-2 lg:order-1">
                        <!-- Kategoriler -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Kategoriler</h2>
                            <div id="kategoriler-list" class="space-y-2">
                                <!-- Kategoriler buraya yüklenecek -->
                            </div>
                        </div>
                        
                        <!-- Sepet -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex flex-col" style="max-height: calc(100vh - 300px);">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Sepet</h2>
                            <div id="sepet-list" class="flex-1 overflow-y-auto space-y-2 mb-4">
                                <p class="text-gray-500 text-sm text-center py-8">Sepet boş</p>
                            </div>
                            <div class="border-t border-gray-200 pt-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">Toplam:</span>
                                    <span id="sepet-toplam" class="text-lg font-bold text-amber-600">0,00 ₺</span>
                                </div>
                                <button onclick="siparisGonder()" id="siparis-btn" disabled class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-3 px-4 rounded-lg shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-paper-plane mr-2"></i>Siparişi Gönder
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Main: Ürünler -->
                    <div class="lg:col-span-3 order-1 lg:order-2">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 sm:mb-6 gap-3">
                                <h2 id="kategori-baslik" class="text-lg sm:text-xl font-bold text-gray-900">Tüm Ürünler</h2>
                                <input type="text" id="urun-ara" placeholder="Ürün ara..." class="w-full sm:w-64 px-3 sm:px-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            </div>
                            <div id="urunler-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                                <!-- Ürünler buraya yüklenecek -->
                            </div>
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
        const masaId = <?php echo $masa_id; ?>;
        const masaNo = '<?php echo $masa_no; ?>';
        let sepet = [];
        let seciliKategori = 0;
        let seciliMusteri = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            loadKategoriler();
            loadUrunler();
            loadMusteriler();
            
            document.getElementById('urun-ara').addEventListener('input', function(e) {
                loadUrunler(e.target.value);
            });
        });
        
        function loadMusteriler() {
            fetch(apiUrl('musteri.php?action=listele'))
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('musteri-select');
                    data.forEach(musteri => {
                        const option = new Option(musteri.ad_soyad, musteri.id);
                        select.add(option);
                    });
                })
                .catch(error => console.error('Hata:', error));
        }
        
        function musteriSecildi() {
            seciliMusteri = document.getElementById('musteri-select').value || null;
        }
        
        function loadKategoriler() {
            fetch(apiUrl('menu.php?action=kategoriler'))
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('kategoriler-list');
                    container.innerHTML = `
                        <button onclick="kategoriSec(0)" class="w-full text-left px-4 py-2 rounded-lg transition-colors ${seciliKategori == 0 ? 'bg-amber-100 text-amber-900 font-semibold' : 'text-gray-700 hover:bg-gray-100'}">
                            Tümü
                        </button>
                        ${data.map(kat => `
                            <button onclick="kategoriSec(${kat.id})" class="w-full text-left px-4 py-2 rounded-lg transition-colors ${seciliKategori == kat.id ? 'bg-amber-100 text-amber-900 font-semibold' : 'text-gray-700 hover:bg-gray-100'}">
                                ${kat.kategori_adi}
                            </button>
                        `).join('')}
                    `;
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Kategoriler yüklenirken hata oluştu');
                });
        }
        
        function kategoriSec(kategoriId) {
            seciliKategori = kategoriId;
            // Kategorileri tekrar yükleme, sadece ürünleri yükle
            loadUrunler();
            
            // Sadece seçili kategoriyi vurgula (DOM'u güncelle)
            const container = document.getElementById('kategoriler-list');
            if (container) {
                container.querySelectorAll('button').forEach(btn => {
                    btn.classList.remove('bg-amber-100', 'text-amber-900', 'font-semibold');
                    btn.classList.add('text-gray-700');
                });
                const seciliBtn = container.querySelector(`button[onclick="kategoriSec(${kategoriId})"]`);
                if (seciliBtn) {
                    seciliBtn.classList.remove('text-gray-700');
                    seciliBtn.classList.add('bg-amber-100', 'text-amber-900', 'font-semibold');
                }
            }
        }
        
        function loadUrunler(arama = '') {
            let endpoint = 'menu.php?action=urunler';
            if (seciliKategori > 0) {
                endpoint += `&kategori_id=${seciliKategori}`;
            }
            
            fetch(apiUrl(endpoint))
                .then(response => response.json())
                .then(data => {
                    let urunler = data;
                    
                    if (arama) {
                        urunler = urunler.filter(u => 
                            u.urun_adi.toLowerCase().includes(arama.toLowerCase())
                        );
                    }
                    
                    const container = document.getElementById('urunler-grid');
                    if (urunler.length > 0) {
                        container.innerHTML = urunler.map(urun => `
                            <div class="bg-white border-2 border-gray-200 rounded-xl p-4 hover:border-amber-500 hover:shadow-lg transition-all transform hover:-translate-y-1">
                                <div class="text-center mb-3">
                                    <div class="w-16 h-16 bg-gradient-to-br from-amber-100 to-amber-200 rounded-xl flex items-center justify-center mx-auto mb-2">
                                        <i class="fas fa-utensils text-amber-600 text-2xl"></i>
                                    </div>
                                    <h3 class="font-semibold text-gray-900 mb-1">${urun.urun_adi}</h3>
                                    <p class="text-xs text-gray-500 mb-2">${urun.aciklama || ''}</p>
                                    <p class="text-lg font-bold text-amber-600">${formatMoney(urun.fiyat)}</p>
                                </div>
                                <button onclick="sepeteEkle(${urun.id}, '${urun.urun_adi}', ${urun.fiyat}, ${urun.kategori_id})" class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-2 px-4 rounded-lg transition-all">
                                    <i class="fas fa-plus mr-1"></i>Ekle
                                </button>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<p class="col-span-full text-center text-gray-500 py-12">Ürün bulunamadı</p>';
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Ürünler yüklenirken hata oluştu');
                });
        }
        
        function sepeteEkle(urunId, urunAdi, fiyat, kategoriId = null) {
            fetch(apiUrl(`kampanya.php?action=aktif_kampanyalar&tutar=${fiyat}&urun_id=${urunId}&kategori_id=${kategoriId || ''}`))
                .then(response => response.json())
                .then(kampanya => {
                    let indirimliFiyat = fiyat;
                    
                    if (kampanya) {
                        if (kampanya.indirim_tipi === 'yuzde') {
                            indirimliFiyat = fiyat * (1 - kampanya.indirim_degeri / 100);
                        } else if (kampanya.indirim_tipi === 'tutar') {
                            indirimliFiyat = Math.max(0, fiyat - kampanya.indirim_degeri);
                        }
                        
                        if (indirimliFiyat < fiyat) {
                            showSuccess(`Kampanya uygulandı: ${kampanya.kampanya_adi}`);
                        }
                    }
                    
                    const mevcut = sepet.find(s => s.id === urunId);
                    if (mevcut) {
                        mevcut.adet++;
                    } else {
                        sepet.push({
                            id: urunId,
                            adi: urunAdi,
                            fiyat: indirimliFiyat,
                            orijinal_fiyat: fiyat,
                            adet: 1,
                            kampanya_id: kampanya ? kampanya.id : null
                        });
                    }
                    sepetGuncelle();
                })
                .catch(error => {
                    const mevcut = sepet.find(s => s.id === urunId);
                    if (mevcut) {
                        mevcut.adet++;
                    } else {
                        sepet.push({
                            id: urunId,
                            adi: urunAdi,
                            fiyat: fiyat,
                            orijinal_fiyat: fiyat,
                            adet: 1
                        });
                    }
                    sepetGuncelle();
                });
        }
        
        function sepettenCikar(urunId) {
            sepet = sepet.filter(s => s.id !== urunId);
            sepetGuncelle();
        }
        
        function adetGuncelle(urunId, yeniAdet) {
            const item = sepet.find(s => s.id === urunId);
            if (item) {
                if (yeniAdet <= 0) {
                    sepettenCikar(urunId);
                } else {
                    item.adet = yeniAdet;
                }
            }
            sepetGuncelle();
        }
        
        function sepetGuncelle() {
            const container = document.getElementById('sepet-list');
            const toplam = sepet.reduce((sum, item) => sum + (item.fiyat * item.adet), 0);
            
            if (sepet.length > 0) {
                container.innerHTML = sepet.map(item => `
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-gray-900 text-sm">${item.adi}</span>
                            ${item.kampanya_id ? '<span class="px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded-full">Kampanya</span>' : ''}
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <button onclick="adetGuncelle(${item.id}, ${item.adet - 1})" class="w-7 h-7 bg-gray-200 hover:bg-gray-300 rounded text-gray-700 text-sm">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="font-semibold text-gray-900">${item.adet}</span>
                                <button onclick="adetGuncelle(${item.id}, ${item.adet + 1})" class="w-7 h-7 bg-gray-200 hover:bg-gray-300 rounded text-gray-700 text-sm">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900">${formatMoney(item.fiyat * item.adet)}</p>
                                ${item.orijinal_fiyat && item.fiyat < item.orijinal_fiyat ? `<p class="text-xs text-gray-400 line-through">${formatMoney(item.orijinal_fiyat * item.adet)}</p>` : ''}
                            </div>
                        </div>
                        <button onclick="sepettenCikar(${item.id})" class="mt-2 w-full text-xs text-red-600 hover:text-red-700">
                            <i class="fas fa-trash mr-1"></i>Kaldır
                        </button>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="text-gray-500 text-sm text-center py-8">Sepet boş</p>';
            }
            
            document.getElementById('sepet-toplam').textContent = formatMoney(toplam);
            document.getElementById('siparis-btn').disabled = sepet.length === 0;
        }
        
        function siparisGonder() {
            if (sepet.length === 0 || masaId === 0) {
                showError('Lütfen ürün seçin ve masa seçin');
                return;
            }
            
            const urunler = sepet.map(item => ({
                urun_id: item.id,
                adet: item.adet,
                birim_fiyat: item.fiyat,
                toplam_fiyat: item.fiyat * item.adet,
                kampanya_id: item.kampanya_id || null
            }));
            
            fetch(apiUrl('siparis.php?action=yeni'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    masa_id: masaId,
                    musteri_id: seciliMusteri,
                    urunler: urunler
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showSuccess('Sipariş başarıyla oluşturuldu! Sipariş No: ' + data.siparis_no);
                    sepet = [];
                    sepetGuncelle();
                    setTimeout(() => {
                        window.location.href = url('index.php');
                    }, 2000);
                } else {
                    showError(data.message || 'Sipariş oluşturulurken hata oluştu');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showError('Sipariş gönderilirken hata oluştu: ' + error.message);
            });
        }
    </script>
</body>
</html>
