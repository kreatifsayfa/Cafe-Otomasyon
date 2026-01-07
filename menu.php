<?php
require_once 'config/config.php';
checkLogin();
checkRole(['admin']);
$page_title = 'Menü Yönetimi';
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
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900">Menü Yönetimi</h1>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Kategoriler ve ürünleri yönetin</p>
                    </div>
                    <button onclick="yeniUrunModal()" class="w-full sm:w-auto bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold px-4 sm:px-6 py-2 sm:py-2.5 text-sm sm:text-base rounded-lg shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-plus mr-2"></i>Yeni Ürün
                    </button>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                    <!-- Tabs -->
                    <div class="flex space-x-2 border-b border-gray-200 mb-4 sm:mb-6 overflow-x-auto">
                        <button onclick="tabAc('kategoriler')" id="tab-kategoriler" class="px-6 py-3 font-semibold text-gray-700 border-b-2 border-amber-500 transition-colors">
                            Kategoriler
                        </button>
                        <button onclick="tabAc('urunler')" id="tab-urunler" class="px-6 py-3 font-semibold text-gray-500 hover:text-gray-700 border-b-2 border-transparent transition-colors">
                            Ürünler
                        </button>
                    </div>
                    
                    <!-- Kategoriler Tab -->
                    <div id="kategoriler-tab" class="tab-content">
                        <div class="mb-4">
                            <button onclick="yeniKategoriModal()" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold px-4 py-2 rounded-lg">
                                <i class="fas fa-plus mr-2"></i>Yeni Kategori
                            </button>
                        </div>
                        <div id="kategoriler-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Kategoriler buraya yüklenecek -->
                        </div>
                    </div>
                    
                    <!-- Ürünler Tab -->
                    <div id="urunler-tab" class="tab-content hidden">
                        <div class="flex space-x-3 mb-6">
                            <select id="kategori-filtre" onchange="loadUrunler()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                                <option value="0">Tüm Kategoriler</option>
                            </select>
                            <input type="text" id="urun-ara" placeholder="Ürün ara..." onkeyup="loadUrunler()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div id="urunler-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Ürünler buraya yüklenecek -->
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Kategori Modal -->
    <div id="kategoriModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 p-6 text-white">
                <h2 id="kategori-modal-title" class="text-2xl font-bold">Yeni Kategori</h2>
            </div>
            <form id="kategoriForm" class="p-6 space-y-4">
                <input type="hidden" id="kategori_id" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kategori Adı</label>
                    <input type="text" id="kategori_adi" name="kategori_adi" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Açıklama</label>
                    <textarea id="kategori_aciklama" name="aciklama" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"></textarea>
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('kategoriModal')" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 px-4 rounded-lg">
                        İptal
                    </button>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-2.5 px-4 rounded-lg">
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Ürün Modal -->
    <div id="urunModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 p-6 text-white">
                <h2 id="urun-modal-title" class="text-2xl font-bold">Yeni Ürün</h2>
            </div>
            <form id="urunForm" class="p-6 space-y-4">
                <input type="hidden" id="urun_id" name="id">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                        <select id="kategori_id" name="kategori_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                            <option value="">Seçiniz</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fiyat (₺)</label>
                        <input type="number" id="fiyat" name="fiyat" step="0.01" min="0" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ürün Adı</label>
                    <input type="text" id="urun_adi" name="urun_adi" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Açıklama</label>
                    <textarea id="aciklama" name="aciklama" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"></textarea>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="stok_var_mi" name="stok_var_mi" onchange="stokToggle()" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                    <label for="stok_var_mi" class="ml-2 text-sm font-medium text-gray-700">Stok Takibi Yap</label>
                </div>
                <div id="stok-miktar-group" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stok Miktarı</label>
                    <input type="number" id="stok_miktari" name="stok_miktari" min="0" value="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('urunModal')" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 px-4 rounded-lg">
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
            loadKategoriler();
            loadUrunler();
        });
        
        function tabAc(tab) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.add('hidden'));
            document.getElementById(tab + '-tab').classList.remove('hidden');
            
            document.querySelectorAll('[id^="tab-"]').forEach(btn => {
                btn.classList.remove('border-amber-500', 'text-gray-700');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            document.getElementById('tab-' + tab).classList.remove('border-transparent', 'text-gray-500');
            document.getElementById('tab-' + tab).classList.add('border-amber-500', 'text-gray-700');
        }
        
        function loadKategoriler() {
            fetch(apiUrl('menu.php?action=kategoriler'))
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('kategoriler-list');
                    if (data.length > 0) {
                        container.innerHTML = data.map(kat => `
                            <div class="bg-white border-2 border-gray-200 rounded-xl p-5 hover:border-amber-500 hover:shadow-lg transition-all">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">${kat.kategori_adi}</h3>
                                <p class="text-sm text-gray-500 mb-4">${kat.aciklama || ''}</p>
                                <div class="flex space-x-2">
                                    <button onclick="kategoriDuzenle(${kat.id})" class="flex-1 bg-amber-100 hover:bg-amber-200 text-amber-800 font-semibold py-2 px-4 rounded-lg transition-colors">
                                        <i class="fas fa-edit mr-1"></i>Düzenle
                                    </button>
                                    <button onclick="kategoriSil(${kat.id}, '${kat.kategori_adi}')" class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-800 rounded-lg transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<p class="col-span-full text-center text-gray-500 py-12">Kategori bulunamadı</p>';
                    }
                });
        }
        
        function loadUrunler() {
            const kategoriId = document.getElementById('kategori-filtre').value || 0;
            const arama = document.getElementById('urun-ara').value;
            
            let endpoint = 'menu.php?action=urunler';
            if (kategoriId > 0) endpoint += `&kategori_id=${kategoriId}`;
            
            fetch(apiUrl(endpoint))
                .then(response => response.json())
                .then(data => {
                    let urunler = data;
                    if (arama) {
                        urunler = urunler.filter(u => u.urun_adi.toLowerCase().includes(arama.toLowerCase()));
                    }
                    
                    const container = document.getElementById('urunler-list');
                    if (urunler.length > 0) {
                        container.innerHTML = urunler.map(urun => `
                            <div class="bg-white border-2 border-gray-200 rounded-xl p-5 hover:border-amber-500 hover:shadow-lg transition-all">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-lg font-semibold text-gray-900">${urun.urun_adi}</h3>
                                    <span class="px-3 py-1 bg-${urun.durum === 'aktif' ? 'green' : 'gray'}-100 text-${urun.durum === 'aktif' ? 'green' : 'gray'}-800 text-xs rounded-full">${urun.durum}</span>
                                </div>
                                <p class="text-sm text-gray-500 mb-3">${urun.aciklama || ''}</p>
                                <p class="text-xl font-bold text-amber-600 mb-4">${formatMoney(urun.fiyat)}</p>
                                <div class="flex space-x-2">
                                    <button onclick="urunDuzenle(${urun.id})" class="flex-1 bg-amber-100 hover:bg-amber-200 text-amber-800 font-semibold py-2 px-4 rounded-lg transition-colors">
                                        <i class="fas fa-edit mr-1"></i>Düzenle
                                    </button>
                                    <button onclick="urunSil(${urun.id}, '${urun.urun_adi}')" class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-800 rounded-lg transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<p class="col-span-full text-center text-gray-500 py-12">Ürün bulunamadı</p>';
                    }
                });
            
            // Kategori filtre select'ini doldur
            fetch(apiUrl('menu.php?action=kategoriler'))
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('kategori-filtre');
                    select.innerHTML = '<option value="0">Tüm Kategoriler</option>';
                    data.forEach(kat => {
                        const option = new Option(kat.kategori_adi, kat.id);
                        select.add(option);
                    });
                });
        }
        
        function yeniKategoriModal() {
            document.getElementById('kategoriForm').reset();
            document.getElementById('kategori_id').value = '';
            document.getElementById('kategori-modal-title').textContent = 'Yeni Kategori';
            openModal('kategoriModal');
        }
        
        function kategoriDuzenle(id) {
            fetch(apiUrl(`menu.php?action=kategori_getir&id=${id}`))
                .then(response => response.json())
                .then(kat => {
                    document.getElementById('kategori_id').value = kat.id;
                    document.getElementById('kategori_adi').value = kat.kategori_adi;
                    document.getElementById('kategori_aciklama').value = kat.aciklama || '';
                    document.getElementById('kategori-modal-title').textContent = 'Kategori Düzenle';
                    openModal('kategoriModal');
                });
        }
        
        function kategoriSil(id, ad) {
            if (confirm(`"${ad}" kategorisini silmek istediğinize emin misiniz?`)) {
                fetch(apiUrl(`menu.php?action=kategori_sil&id=${id}`), { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess('Kategori silindi');
                            loadKategoriler();
                        } else {
                            showError(data.message);
                        }
                    });
            }
        }
        
        function yeniUrunModal() {
            document.getElementById('urunForm').reset();
            document.getElementById('urun_id').value = '';
            document.getElementById('stok-miktar-group').classList.add('hidden');
            document.getElementById('urun-modal-title').textContent = 'Yeni Ürün';
            
            // Kategorileri yükle
            fetch(apiUrl('menu.php?action=kategoriler'))
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('kategori_id');
                    select.innerHTML = '<option value="">Seçiniz</option>';
                    data.forEach(kat => {
                        const option = new Option(kat.kategori_adi, kat.id);
                        select.add(option);
                    });
                    // Varsayılan olarak ilk kategoriyi seçme, kullanıcı seçsin
                    select.value = '';
                });
            
            openModal('urunModal');
        }
        
        function urunDuzenle(id) {
            fetch(apiUrl(`menu.php?action=urun_getir&id=${id}`))
                .then(response => response.json())
                .then(urun => {
                    document.getElementById('urun_id').value = urun.id;
                    document.getElementById('urun_adi').value = urun.urun_adi;
                    document.getElementById('aciklama').value = urun.aciklama || '';
                    document.getElementById('fiyat').value = urun.fiyat;
                    document.getElementById('stok_var_mi').checked = urun.stok_var_mi;
                    document.getElementById('stok_miktari').value = urun.stok_miktari || 0;
                    
                    if (urun.stok_var_mi) {
                        document.getElementById('stok-miktar-group').classList.remove('hidden');
                    }
                    
                    // Kategorileri yükle
                    fetch(apiUrl('menu.php?action=kategoriler'))
                        .then(response => response.json())
                        .then(data => {
                            const select = document.getElementById('kategori_id');
                            select.innerHTML = '<option value="">Seçiniz</option>';
                            data.forEach(kat => {
                                const option = new Option(kat.kategori_adi, kat.id);
                                // Kategori ID'yi karşılaştırırken type coercion yap
                                if (parseInt(kat.id) === parseInt(urun.kategori_id)) {
                                    option.selected = true;
                                }
                                select.add(option);
                            });
                            
                            // Kategori seçili değilse ve ürünün kategori_id'si varsa seç
                            if (select.value === '' && urun.kategori_id) {
                                select.value = urun.kategori_id;
                            }
                        });
                    
                    document.getElementById('urun-modal-title').textContent = 'Ürün Düzenle';
                    openModal('urunModal');
                });
        }
        
        function urunSil(id, ad) {
            if (confirm(`"${ad}" ürününü silmek istediğinize emin misiniz?`)) {
                fetch(apiUrl(`menu.php?action=urun_sil&id=${id}`), { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess('Ürün silindi');
                            loadUrunler();
                        } else {
                            showError(data.message);
                        }
                    });
            }
        }
        
        function stokToggle() {
            const checked = document.getElementById('stok_var_mi').checked;
            document.getElementById('stok-miktar-group').classList.toggle('hidden', !checked);
        }
        
        document.getElementById('kategoriForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            const id = data.id;
            
            // Validasyon
            if (!data.kategori_adi || data.kategori_adi.trim() === '') {
                showError('Lütfen kategori adı girin');
                return;
            }
            
            // Boş alanları temizle
            if (!data.aciklama) data.aciklama = '';
            
            fetch(apiUrl(`menu.php?action=${id ? 'kategori_guncelle' : 'kategori_ekle'}`), {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    showSuccess(result.message || 'İşlem başarılı');
                    closeModal('kategoriModal');
                    loadKategoriler();
                } else {
                    showError(result.message || 'İşlem başarısız');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showError('Kategori kaydedilirken hata oluştu: ' + error.message);
            });
        });
        
        document.getElementById('urunForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // FormData yerine direkt değerleri al
            const id = document.getElementById('urun_id').value;
            const kategori_id = document.getElementById('kategori_id').value;
            const urun_adi = document.getElementById('urun_adi').value.trim();
            const aciklama = document.getElementById('aciklama').value.trim();
            const fiyat = parseFloat(document.getElementById('fiyat').value);
            const stok_var_mi = document.getElementById('stok_var_mi').checked;
            const stok_miktari = parseInt(document.getElementById('stok_miktari').value) || 0;
            
            // Validasyon
            if (!kategori_id || kategori_id === '' || kategori_id === '0') {
                showError('Lütfen kategori seçin');
                return;
            }
            if (!urun_adi) {
                showError('Lütfen ürün adı girin');
                return;
            }
            if (!fiyat || fiyat <= 0) {
                showError('Lütfen geçerli bir fiyat girin');
                return;
            }
            
            // Veriyi hazırla
            const data = {
                id: id || null,
                kategori_id: parseInt(kategori_id),
                urun_adi: urun_adi,
                aciklama: aciklama,
                fiyat: fiyat,
                stok_var_mi: stok_var_mi,
                stok_miktari: stok_miktari
            };
            
            fetch(apiUrl(`menu.php?action=${id ? 'urun_guncelle' : 'urun_ekle'}`), {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    showSuccess(result.message || 'İşlem başarılı');
                    closeModal('urunModal');
                    loadUrunler();
                } else {
                    showError(result.message || 'İşlem başarısız');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showError('Ürün kaydedilirken hata oluştu: ' + error.message);
            });
        });
        
        // Modal dışına tıklanınca kapat
        document.querySelectorAll('[id$="Modal"]').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
