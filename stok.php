<?php
require_once 'config/config.php';
checkLogin();
checkRole(['admin']);
$page_title = 'Stok Yönetimi';
?>
<?php include 'includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar_tailwind.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <header class="bg-white shadow-sm border-b border-gray-200 z-40">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Stok Yönetimi</h1>
                        <p class="text-sm text-gray-500 mt-1">Stokları görüntüleyin ve yönetin</p>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="loadUyarilar()" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-4 py-2.5 rounded-lg transition-colors">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Düşük Stok Uyarıları
                        </button>
                        <button onclick="yeniStokModal()" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold px-6 py-2.5 rounded-lg shadow-lg hover:shadow-xl transition-all">
                            <i class="fas fa-plus mr-2"></i>Yeni Stok
                        </button>
                    </div>
                </div>
            </header>
            
            <main class="flex-1 overflow-y-auto p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-4">
                        <input type="text" id="stok-ara" placeholder="Stok ara..." onkeyup="loadStok()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div id="stok-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Stok listesi -->
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="stokModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 p-6 text-white">
                <h2 id="modal-title" class="text-2xl font-bold">Yeni Stok</h2>
            </div>
            <form id="stokForm" class="p-6 space-y-4">
                <input type="hidden" id="stok_id" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Malzeme Adı</label>
                    <input type="text" id="malzeme_adi" name="malzeme_adi" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Miktar</label>
                        <input type="number" id="miktar" name="miktar" step="0.01" min="0" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Birim</label>
                        <select id="birim" name="birim" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                            <option value="kg">kg</option>
                            <option value="lt">lt</option>
                            <option value="adet">adet</option>
                            <option value="paket">paket</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Stok</label>
                    <input type="number" id="minimum_stok" name="minimum_stok" step="0.01" min="0" value="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tedarikçi</label>
                    <input type="text" id="tedarikci" name="tedarikci" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('stokModal')" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 px-4 rounded-lg">
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
            loadStok();
        });
        
        function loadStok() {
            const arama = document.getElementById('stok-ara').value;
            let url = apiUrl('stok.php?action=listele');
            if (arama) url += `&arama=${encodeURIComponent(arama)}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('stok-list');
                    if (data.length > 0) {
                        container.innerHTML = data.map(stok => {
                            const dusukStok = parseFloat(stok.miktar) <= parseFloat(stok.minimum_stok);
                            return `
                                <div class="bg-white border-2 ${dusukStok ? 'border-red-500 bg-red-50' : 'border-gray-200'} rounded-xl p-5 hover:shadow-lg transition-all">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-lg font-semibold text-gray-900">${dusukStok ? '⚠️ ' : ''}${stok.malzeme_adi}</h3>
                                    </div>
                                    <div class="space-y-2 mb-4">
                                        <p class="text-sm text-gray-600"><i class="fas fa-box mr-2"></i>Miktar: <span class="font-semibold">${stok.miktar} ${stok.birim}</span></p>
                                        <p class="text-sm text-gray-600"><i class="fas fa-exclamation-circle mr-2"></i>Minimum: <span class="font-semibold">${stok.minimum_stok} ${stok.birim}</span></p>
                                        ${stok.tedarikci ? `<p class="text-sm text-gray-600"><i class="fas fa-truck mr-2"></i>Tedarikçi: ${stok.tedarikci}</p>` : ''}
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="stokDuzenle(${stok.id})" class="flex-1 bg-amber-100 hover:bg-amber-200 text-amber-800 font-semibold py-2 px-4 rounded-lg transition-colors">
                                            <i class="fas fa-edit mr-1"></i>Düzenle
                                        </button>
                                        <button onclick="stokSil(${stok.id}, '${stok.malzeme_adi}')" class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-800 rounded-lg transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    } else {
                        container.innerHTML = '<p class="col-span-full text-center text-gray-500 py-12">Stok kaydı bulunamadı</p>';
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Stoklar yüklenirken hata oluştu');
                });
        }
        
        function loadUyarilar() {
            fetch(apiUrl('stok.php?action=uyarilar'))
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        showWarning(`${data.length} ürünün stoğu minimum seviyenin altında!`);
                        loadStok();
                    } else {
                        showInfo('Düşük stok uyarısı yok');
                    }
                });
        }
        
        function yeniStokModal() {
            document.getElementById('stokForm').reset();
            document.getElementById('stok_id').value = '';
            document.getElementById('modal-title').textContent = 'Yeni Stok';
            openModal('stokModal');
        }
        
        function stokDuzenle(id) {
            fetch(apiUrl(`stok.php?action=getir&id=${id}`))
                .then(response => response.json())
                .then(stok => {
                    document.getElementById('stok_id').value = stok.id;
                    document.getElementById('malzeme_adi').value = stok.malzeme_adi;
                    document.getElementById('miktar').value = stok.miktar;
                    document.getElementById('birim').value = stok.birim;
                    document.getElementById('minimum_stok').value = stok.minimum_stok;
                    document.getElementById('tedarikci').value = stok.tedarikci || '';
                    document.getElementById('modal-title').textContent = 'Stok Düzenle';
                    openModal('stokModal');
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Stok bilgileri yüklenirken hata oluştu');
                });
        }
        
        function stokSil(id, ad) {
            if (confirm(`"${ad}" adlı stok kaydını silmek istediğinize emin misiniz?`)) {
                fetch(apiUrl(`stok.php?action=sil&id=${id}`), { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess('Stok silindi');
                            loadStok();
                        } else {
                            showError(data.message || 'Stok silinirken hata oluştu');
                        }
                    })
                    .catch(error => {
                        console.error('Hata:', error);
                        showError('Stok silinirken hata oluştu');
                    });
            }
        }
        
        document.getElementById('stokForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            const id = data.id;
            
            fetch(apiUrl(`stok.php?action=${id ? 'guncelle' : 'ekle'}`), {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess(result.message);
                    closeModal('stokModal');
                    loadStok();
                } else {
                    showError(result.message || 'İşlem başarısız');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showError('İşlem sırasında hata oluştu');
            });
        });
        
        document.getElementById('stokModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal('stokModal');
        });
    </script>
</body>
</html>
