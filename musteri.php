<?php
require_once 'config/config.php';
checkLogin();
checkRole(['admin']);
$page_title = 'Müşteri Yönetimi';
?>
<?php include 'includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar_tailwind.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <header class="bg-white shadow-sm border-b border-gray-200 z-40">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 sm:py-4 gap-3">
                    <div>
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900">Müşteri Yönetimi</h1>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Müşterileri görüntüleyin ve yönetin</p>
                    </div>
                    <button onclick="yeniMusteriModal()" class="w-full sm:w-auto bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold px-4 sm:px-6 py-2 sm:py-2.5 text-sm sm:text-base rounded-lg shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-plus mr-2"></i>Yeni Müşteri
                    </button>
                </div>
            </header>
            
            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                    <div class="mb-4">
                        <input type="text" id="musteri-ara" placeholder="Müşteri ara (ad, telefon, email)..." onkeyup="loadMusteriler()" class="w-full px-3 sm:px-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div class="table-responsive overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ad Soyad</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telefon</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">E-posta</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Toplam Harcama</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Puan</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody id="musteri-list" class="bg-white divide-y divide-gray-200">
                                <!-- Müşteri listesi -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="musteriModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 p-6 text-white">
                <h2 id="modal-title" class="text-2xl font-bold">Yeni Müşteri</h2>
            </div>
            <form id="musteriForm" class="p-6 space-y-4">
                <input type="hidden" id="musteri_id" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ad Soyad</label>
                    <input type="text" id="ad_soyad" name="ad_soyad" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Telefon</label>
                    <input type="tel" id="telefon" name="telefon" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">E-posta</label>
                    <input type="email" id="email" name="email" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('musteriModal')" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 px-4 rounded-lg">
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
            loadMusteriler();
        });
        
        function loadMusteriler() {
            const arama = document.getElementById('musteri-ara').value;
            let url = apiUrl('musteri.php?action=listele');
            if (arama) url += `&arama=${encodeURIComponent(arama)}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('musteri-list');
                    if (data.length > 0) {
                        tbody.innerHTML = data.map(musteri => `
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">${musteri.ad_soyad}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">${musteri.telefon || '-'}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">${musteri.email || '-'}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-amber-600">${formatMoney(musteri.toplam_harcama || 0)}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-semibold">${musteri.puan || 0} Puan</span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex space-x-2">
                                        <button onclick="musteriDuzenle(${musteri.id})" class="text-amber-600 hover:text-amber-700">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="musteriSil(${musteri.id}, '${musteri.ad_soyad}')" class="text-red-600 hover:text-red-700">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Müşteri bulunamadı</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Müşteriler yüklenirken hata oluştu');
                });
        }
        
        function yeniMusteriModal() {
            document.getElementById('musteriForm').reset();
            document.getElementById('musteri_id').value = '';
            document.getElementById('modal-title').textContent = 'Yeni Müşteri';
            openModal('musteriModal');
        }
        
        function musteriDuzenle(id) {
            fetch(apiUrl(`musteri.php?action=getir&id=${id}`))
                .then(response => response.json())
                .then(musteri => {
                    document.getElementById('musteri_id').value = musteri.id;
                    document.getElementById('ad_soyad').value = musteri.ad_soyad;
                    document.getElementById('telefon').value = musteri.telefon || '';
                    document.getElementById('email').value = musteri.email || '';
                    document.getElementById('modal-title').textContent = 'Müşteri Düzenle';
                    openModal('musteriModal');
                });
        }
        
        function musteriSil(id, ad) {
            if (confirm(`"${ad}" adlı müşteriyi silmek istediğinize emin misiniz?`)) {
                fetch(apiUrl(`musteri.php?action=sil&id=${id}`), { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess('Müşteri silindi');
                            loadMusteriler();
                        } else {
                            showError(data.message);
                        }
                    });
            }
        }
        
        document.getElementById('musteriForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            const id = data.id;
            
            fetch(apiUrl(`musteri.php?action=${id ? 'guncelle' : 'ekle'}`), {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess(result.message);
                    closeModal('musteriModal');
                    loadMusteriler();
                } else {
                    showError(result.message);
                }
            });
        });
        
        document.getElementById('musteriModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal('musteriModal');
        });
    </script>
</body>
</html>
