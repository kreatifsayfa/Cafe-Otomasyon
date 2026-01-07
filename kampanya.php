<?php
require_once 'config/config.php';
checkLogin();
checkRole(['admin']);
$page_title = 'Kampanya Yönetimi';
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
                        <h1 class="text-2xl font-bold text-gray-900">Kampanya Yönetimi</h1>
                        <p class="text-sm text-gray-500 mt-1">Kampanyaları oluşturun ve yönetin</p>
                    </div>
                    <button onclick="yeniKampanyaModal()" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold px-6 py-2.5 rounded-lg shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-plus mr-2"></i>Yeni Kampanya
                    </button>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex space-x-3 mb-6">
                        <input type="text" id="kampanya-ara" placeholder="Kampanya ara..." onkeyup="loadKampanyalar()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        <select id="durum-filtre" onchange="loadKampanyalar()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                            <option value="">Tüm Durumlar</option>
                            <option value="aktif">Aktif</option>
                            <option value="pasif">Pasif</option>
                            <option value="bitmis">Bitmiş</option>
                        </select>
                    </div>
                    <div id="kampanya-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Kampanya listesi -->
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Kampanya Modal -->
    <div id="kampanyaModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 p-6 text-white">
                <h2 id="modal-title" class="text-2xl font-bold">Yeni Kampanya</h2>
            </div>
            <form id="kampanyaForm" class="p-6 space-y-4">
                <input type="hidden" id="kampanya_id" name="id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kampanya Adı</label>
                    <input type="text" id="kampanya_adi" name="kampanya_adi" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Başlangıç Tarihi</label>
                        <input type="date" id="baslangic_tarihi" name="baslangic_tarihi" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bitiş Tarihi</label>
                        <input type="date" id="bitis_tarihi" name="bitis_tarihi" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">İndirim Tipi</label>
                    <select id="indirim_tipi" name="indirim_tipi" required onchange="indirimTipiDegisti()" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        <option value="yuzde">Yüzde (%)</option>
                        <option value="tutar">Tutar (₺)</option>
                        <option value="urun">Ürün Bazlı</option>
                        <option value="musteri">Müşteri Bazlı</option>
                    </select>
                </div>
                
                <div id="indirim-deger-group">
                    <label id="indirim-deger-label" class="block text-sm font-medium text-gray-700 mb-2">İndirim Değeri (%)</label>
                    <input type="number" id="indirim_degeri" name="indirim_degeri" step="0.01" min="0" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                
                <div id="min-tutar-group" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Tutar (₺)</label>
                    <input type="number" id="min_tutar" name="min_tutar" step="0.01" min="0" value="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                
                <div id="urun-secim-group" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ürün</label>
                    <select id="urun_id" name="urun_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        <option value="">Tüm Ürünler</option>
                    </select>
                </div>
                
                <div id="kategori-secim-group" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                    <select id="kategori_id" name="kategori_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        <option value="">Tüm Kategoriler</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Açıklama</label>
                    <textarea id="aciklama" name="aciklama" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Durum</label>
                    <select id="durum" name="durum" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        <option value="aktif">Aktif</option>
                        <option value="pasif">Pasif</option>
                    </select>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('kampanyaModal')" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 px-4 rounded-lg">
                        İptal
                    </button>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-2.5 px-4 rounded-lg">
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'includes/base_url_script.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/tailwind-helpers.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadKampanyalar();
            loadUrunler();
            loadKategoriler();
        });
        
        function loadKampanyalar() {
            const arama = document.getElementById('kampanya-ara').value;
            const durum = document.getElementById('durum-filtre').value;
            
            let url = apiUrl('kampanya.php?action=listele');
            if (arama) url += `&arama=${encodeURIComponent(arama)}`;
            if (durum) url += `&durum=${durum}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('kampanya-list');
                    if (data.length > 0) {
                        container.innerHTML = data.map(kampanya => {
                            const bugun = new Date();
                            const baslangic = new Date(kampanya.baslangic_tarihi);
                            const bitis = new Date(kampanya.bitis_tarihi);
                            let durumBadge = '';
                            
                            if (kampanya.durum === 'pasif') {
                                durumBadge = '<span class="px-3 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">Pasif</span>';
                            } else if (bugun < baslangic) {
                                durumBadge = '<span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Yakında</span>';
                            } else if (bugun > bitis) {
                                durumBadge = '<span class="px-3 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">Bitmiş</span>';
                            } else {
                                durumBadge = '<span class="px-3 py-1 bg-green-100 text-green-800 text-xs rounded-full">Aktif</span>';
                            }
                            
                            const indirimText = kampanya.indirim_tipi === 'yuzde' 
                                ? `%${kampanya.indirim_degeri}` 
                                : `${formatMoney(kampanya.indirim_degeri)}`;
                            
                            return `
                                <div class="bg-white border-2 border-gray-200 rounded-xl p-5 hover:border-amber-500 hover:shadow-lg transition-all">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-lg font-semibold text-gray-900">${kampanya.kampanya_adi}</h3>
                                        ${durumBadge}
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2"><i class="fas fa-calendar mr-2"></i>${formatDate(kampanya.baslangic_tarihi)} - ${formatDate(kampanya.bitis_tarihi)}</p>
                                    <p class="text-sm font-semibold text-amber-600 mb-2"><i class="fas fa-tag mr-2"></i>İndirim: ${indirimText}</p>
                                    ${kampanya.min_tutar > 0 ? `<p class="text-xs text-gray-500 mb-2"><i class="fas fa-info-circle mr-2"></i>Min. Tutar: ${formatMoney(kampanya.min_tutar)}</p>` : ''}
                                    ${kampanya.aciklama ? `<p class="text-xs text-gray-500 mb-4">${kampanya.aciklama}</p>` : ''}
                                    <div class="flex space-x-2">
                                        <button onclick="kampanyaDuzenle(${kampanya.id})" class="flex-1 bg-amber-100 hover:bg-amber-200 text-amber-800 font-semibold py-2 px-4 rounded-lg transition-colors">
                                            <i class="fas fa-edit mr-1"></i>Düzenle
                                        </button>
                                        <button onclick="kampanyaSil(${kampanya.id}, '${kampanya.kampanya_adi}')" class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-800 rounded-lg transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    } else {
                        container.innerHTML = '<p class="col-span-full text-center text-gray-500 py-12">Kampanya bulunamadı</p>';
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Kampanyalar yüklenirken hata oluştu');
                });
        }
        
        function indirimTipiDegisti() {
            const tip = document.getElementById('indirim_tipi').value;
            const degerGroup = document.getElementById('indirim-deger-group');
            const minTutarGroup = document.getElementById('min-tutar-group');
            const urunGroup = document.getElementById('urun-secim-group');
            const kategoriGroup = document.getElementById('kategori-secim-group');
            const label = document.getElementById('indirim-deger-label');
            
            degerGroup.classList.remove('hidden');
            minTutarGroup.classList.remove('hidden');
            urunGroup.classList.toggle('hidden', tip !== 'urun');
            kategoriGroup.classList.toggle('hidden', tip !== 'urun');
            
            if (tip === 'yuzde') {
                label.textContent = 'İndirim Değeri (%)';
                document.getElementById('indirim_degeri').setAttribute('max', '100');
            } else if (tip === 'tutar') {
                label.textContent = 'İndirim Değeri (₺)';
                document.getElementById('indirim_degeri').removeAttribute('max');
            } else {
                label.textContent = 'İndirim Değeri';
            }
        }
        
        function loadUrunler() {
            fetch(apiUrl('menu.php?action=urunler'))
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('urun_id');
                    data.forEach(urun => {
                        const option = new Option(urun.urun_adi, urun.id);
                        select.add(option);
                    });
                });
        }
        
        function loadKategoriler() {
            fetch(apiUrl('menu.php?action=kategoriler'))
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('kategori_id');
                    data.forEach(kat => {
                        const option = new Option(kat.kategori_adi, kat.id);
                        select.add(option);
                    });
                });
        }
        
        function yeniKampanyaModal() {
            document.getElementById('kampanyaForm').reset();
            document.getElementById('kampanya_id').value = '';
            document.getElementById('modal-title').textContent = 'Yeni Kampanya';
            indirimTipiDegisti();
            openModal('kampanyaModal');
        }
        
        function kampanyaDuzenle(id) {
            fetch(apiUrl(`kampanya.php?action=getir&id=${id}`))
                .then(response => response.json())
                .then(kampanya => {
                    document.getElementById('kampanya_id').value = kampanya.id;
                    document.getElementById('kampanya_adi').value = kampanya.kampanya_adi;
                    document.getElementById('baslangic_tarihi').value = kampanya.baslangic_tarihi;
                    document.getElementById('bitis_tarihi').value = kampanya.bitis_tarihi;
                    document.getElementById('indirim_tipi').value = kampanya.indirim_tipi;
                    document.getElementById('indirim_degeri').value = kampanya.indirim_degeri;
                    document.getElementById('min_tutar').value = kampanya.min_tutar || 0;
                    document.getElementById('urun_id').value = kampanya.urun_id || '';
                    document.getElementById('kategori_id').value = kampanya.kategori_id || '';
                    document.getElementById('aciklama').value = kampanya.aciklama || '';
                    document.getElementById('durum').value = kampanya.durum;
                    document.getElementById('modal-title').textContent = 'Kampanya Düzenle';
                    indirimTipiDegisti();
                    openModal('kampanyaModal');
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Kampanya bilgileri yüklenirken hata oluştu');
                });
        }
        
        function kampanyaSil(id, ad) {
            if (confirm(`"${ad}" adlı kampanyayı silmek istediğinize emin misiniz?`)) {
                fetch(apiUrl(`kampanya.php?action=sil&id=${id}`), { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess('Kampanya silindi');
                            loadKampanyalar();
                        } else {
                            showError(data.message || 'Kampanya silinirken hata oluştu');
                        }
                    })
                    .catch(error => {
                        console.error('Hata:', error);
                        showError('Kampanya silinirken hata oluştu');
                    });
            }
        }
        
        document.getElementById('kampanyaForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            const id = data.id;
            
            const url = apiUrl('kampanya.php');
            const action = id ? 'guncelle' : 'ekle';
            const method = id ? 'PUT' : 'POST';
            
            fetch(`${url}?action=${action}`, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess(result.message);
                    closeModal('kampanyaModal');
                    loadKampanyalar();
                } else {
                    showError(result.message || 'İşlem başarısız');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showError('İşlem sırasında hata oluştu');
            });
        });
        
        // Modal dışına tıklanınca kapat
        document.getElementById('kampanyaModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal('kampanyaModal');
            }
        });
    </script>
</body>
</html>
