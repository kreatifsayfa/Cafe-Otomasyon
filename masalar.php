<?php
require_once 'config/config.php';
checkLogin();
$page_title = 'Masa Yönetimi';
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
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900">Masa Yönetimi</h1>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Masaları görüntüleyin ve yönetin</p>
                    </div>
                    <button onclick="yeniMasaModal()" class="w-full sm:w-auto bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold px-4 sm:px-6 py-2 sm:py-2.5 text-sm sm:text-base rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                        <i class="fas fa-plus mr-2"></i>Yeni Masa
                    </button>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div id="masalar-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <!-- Masalar buraya yüklenecek -->
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="masaModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 p-6 text-white">
                <h2 id="modal-title" class="text-2xl font-bold">Yeni Masa</h2>
            </div>
            <form id="masaForm" class="p-6 space-y-4">
                <input type="hidden" id="masa_id" name="id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Masa No</label>
                    <input type="text" id="masa_no" name="masa_no" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kapasite</label>
                    <input type="number" id="kapasite" name="kapasite" required min="1" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Konum</label>
                    <input type="text" id="konum" name="konum" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Durum</label>
                    <select id="durum" name="durum" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <option value="bos">Boş</option>
                        <option value="dolu">Dolu</option>
                        <option value="rezerve">Rezerve</option>
                        <option value="temizlik">Temizlik</option>
                    </select>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('masaModal')" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 px-4 rounded-lg transition-colors">
                        İptal
                    </button>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow-lg hover:shadow-xl transition-all">
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'includes/base_url_script.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadMasalar();
        });
        
        function loadMasalar() {
            fetch(apiUrl('masalar.php'))
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('masalar-container');
                    if (data.length > 0) {
                        container.innerHTML = data.map(masa => {
                            const durumRenk = {
                                'bos': 'bg-green-100 text-green-800 border-green-200',
                                'dolu': 'bg-red-100 text-red-800 border-red-200',
                                'rezerve': 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                'temizlik': 'bg-gray-100 text-gray-800 border-gray-200'
                            };
                            const durumText = {
                                'bos': 'Boş',
                                'dolu': 'Dolu',
                                'rezerve': 'Rezerve',
                                'temizlik': 'Temizlik'
                            };
                            
                            return `
                                <div class="bg-white border-2 ${durumRenk[masa.durum]} rounded-xl p-5 hover:shadow-lg transition-all duration-200 transform hover:-translate-y-1">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-xl font-bold">Masa ${masa.masa_no}</h3>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold border ${durumRenk[masa.durum]}">
                                            ${durumText[masa.durum]}
                                        </span>
                                    </div>
                                    <div class="space-y-2 mb-4">
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-users mr-2"></i>${masa.kapasite} Kişi
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-map-marker-alt mr-2"></i>${masa.konum || 'Belirtilmemiş'}
                                        </p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="masaSec(${masa.id}, '${masa.masa_no}')" class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-all">
                                            <i class="fas fa-shopping-cart mr-1"></i>Sipariş
                                        </button>
                                        <button onclick="masaDuzenle(${masa.id})" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="masaSil(${masa.id}, '${masa.masa_no}')" class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    } else {
                        container.innerHTML = '<p class="col-span-full text-center text-gray-500 py-12">Masa bulunamadı</p>';
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Masalar yüklenirken hata oluştu');
                });
        }
        
        function getDurumText(durum) {
            const durumlar = {
                'bos': 'Boş',
                'dolu': 'Dolu',
                'rezerve': 'Rezerve',
                'temizlik': 'Temizlik'
            };
            return durumlar[durum] || durum;
        }
        
        function yeniMasaModal() {
            document.getElementById('masaForm').reset();
            document.getElementById('masa_id').value = '';
            document.getElementById('modal-title').textContent = 'Yeni Masa';
            openModal('masaModal');
        }
        
        function masaDuzenle(id) {
            fetch(apiUrl(`masalar.php?id=${id}`))
                .then(response => response.json())
                .then(masa => {
                    document.getElementById('masa_id').value = masa.id;
                    document.getElementById('masa_no').value = masa.masa_no;
                    document.getElementById('kapasite').value = masa.kapasite;
                    document.getElementById('konum').value = masa.konum || '';
                    document.getElementById('durum').value = masa.durum;
                    document.getElementById('modal-title').textContent = 'Masa Düzenle';
                    openModal('masaModal');
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Masa bilgileri yüklenirken hata oluştu');
                });
        }
        
        function masaSil(id, masaNo) {
            if (confirm(`"${masaNo}" masasını silmek istediğinize emin misiniz?`)) {
                fetch(apiUrl(`masalar.php?id=${id}`), { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess('Masa silindi');
                            loadMasalar();
                        } else {
                            showError(data.message || 'Masa silinirken hata oluştu');
                        }
                    })
                    .catch(error => {
                        console.error('Hata:', error);
                        showError('Masa silinirken hata oluştu');
                    });
            }
        }
        
        function masaSec(masaId, masaNo) {
            window.location.href = url(`siparis.php?masa_id=${masaId}&masa_no=${masaNo}`);
        }
        
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
        
        document.getElementById('masaForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            const id = data.id;
            
            const endpoint = 'masalar.php';
            const method = id ? 'PUT' : 'POST';
            
            fetch(apiUrl(endpoint), {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess(result.message);
                    closeModal('masaModal');
                    loadMasalar();
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
        document.getElementById('masaModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal('masaModal');
            }
        });
    </script>
</body>
</html>
