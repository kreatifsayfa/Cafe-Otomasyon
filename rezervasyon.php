<?php
require_once 'config/config.php';
checkLogin();
checkRole(['admin']);
$page_title = 'Rezervasyonlar';
?>
<?php include 'includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar_tailwind.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <header class="bg-white shadow-sm border-b border-gray-200 z-40">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Rezervasyonlar</h1>
                        <p class="text-sm text-gray-500 mt-1">Rezervasyonları görüntüleyin ve yönetin</p>
                    </div>
                    <button onclick="yeniRezervasyonModal()" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold px-6 py-2.5 rounded-lg shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-plus mr-2"></i>Yeni Rezervasyon
                    </button>
                </div>
            </header>
            
            <main class="flex-1 overflow-y-auto p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-4">
                        <input type="date" id="rezervasyon-tarih" value="<?php echo date('Y-m-d'); ?>" onchange="loadRezervasyonlar()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div id="rezervasyon-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Rezervasyon listesi -->
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="rezervasyonModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 p-6 text-white">
                <h2 id="modal-title" class="text-2xl font-bold">Yeni Rezervasyon</h2>
            </div>
            <form id="rezervasyonForm" class="p-6 space-y-4">
                <input type="hidden" id="rezervasyon_id" name="id">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Müşteri</label>
                        <select id="musteri_id" name="musteri_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                            <option value="">Müşteri Seçin (Opsiyonel)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Masa</label>
                        <select id="masa_id" name="masa_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                            <option value="">Masa Seçin</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tarih</label>
                        <input type="date" id="rezervasyon_tarihi" name="rezervasyon_tarihi" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Saat</label>
                        <input type="time" id="rezervasyon_saati" name="rezervasyon_saati" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kişi Sayısı</label>
                    <input type="number" id="kisi_sayisi" name="kisi_sayisi" min="1" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notlar</label>
                    <textarea id="notlar" name="notlar" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Durum</label>
                    <select id="durum" name="durum" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        <option value="beklemede">Beklemede</option>
                        <option value="onaylandi">Onaylandı</option>
                        <option value="iptal">İptal</option>
                    </select>
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('rezervasyonModal')" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 px-4 rounded-lg">
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
            loadRezervasyonlar();
            loadMasalar();
            loadMusteriler();
        });
        
        function loadRezervasyonlar() {
            const tarih = document.getElementById('rezervasyon-tarih').value;
            fetch(apiUrl(`rezervasyon.php?action=listele&tarih=${tarih}`))
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('rezervasyon-list');
                    if (data.length > 0) {
                        container.innerHTML = data.map(rez => `
                            <div class="bg-white border-2 border-gray-200 rounded-xl p-5 hover:border-amber-500 hover:shadow-lg transition-all">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-lg font-semibold text-gray-900">Masa ${rez.masa_no}</h3>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold ${rez.durum === 'onaylandi' ? 'bg-green-100 text-green-800' : rez.durum === 'iptal' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'}">${getDurumText(rez.durum)}</span>
                                </div>
                                <div class="space-y-2 mb-4">
                                    <p class="text-sm text-gray-600"><i class="fas fa-calendar mr-2"></i>${formatDate(rez.rezervasyon_tarihi)}</p>
                                    <p class="text-sm text-gray-600"><i class="fas fa-clock mr-2"></i>${rez.rezervasyon_saati}</p>
                                    <p class="text-sm text-gray-600"><i class="fas fa-users mr-2"></i>${rez.kisi_sayisi} Kişi</p>
                                    ${rez.musteri_adi ? `<p class="text-sm text-gray-600"><i class="fas fa-user mr-2"></i>${rez.musteri_adi}</p>` : ''}
                                    ${rez.notlar ? `<p class="text-sm text-gray-600"><i class="fas fa-sticky-note mr-2"></i>${rez.notlar}</p>` : ''}
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="rezervasyonDuzenle(${rez.id})" class="flex-1 bg-amber-100 hover:bg-amber-200 text-amber-800 font-semibold py-2 px-4 rounded-lg transition-colors">
                                        <i class="fas fa-edit mr-1"></i>Düzenle
                                    </button>
                                    <button onclick="rezervasyonSil(${rez.id})" class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-800 rounded-lg transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<p class="col-span-full text-center text-gray-500 py-12">Bu tarihte rezervasyon bulunamadı</p>';
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Rezervasyonlar yüklenirken hata oluştu');
                });
        }
        
        function getDurumText(durum) {
            const durumlar = {
                'beklemede': 'Beklemede',
                'onaylandi': 'Onaylandı',
                'iptal': 'İptal',
                'tamamlandi': 'Tamamlandı'
            };
            return durumlar[durum] || durum;
        }
        
        function loadMasalar() {
            fetch(apiUrl('masalar.php'))
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('masa_id');
                    data.forEach(masa => {
                        const option = new Option(`Masa ${masa.masa_no} (${masa.kapasite} kişi)`, masa.id);
                        select.add(option);
                    });
                });
        }
        
        function loadMusteriler() {
            fetch(apiUrl('musteri.php?action=listele'))
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('musteri_id');
                    data.forEach(musteri => {
                        const option = new Option(musteri.ad_soyad, musteri.id);
                        select.add(option);
                    });
                });
        }
        
        function yeniRezervasyonModal() {
            document.getElementById('rezervasyonForm').reset();
            document.getElementById('rezervasyon_id').value = '';
            document.getElementById('rezervasyon_tarihi').value = new Date().toISOString().split('T')[0];
            document.getElementById('modal-title').textContent = 'Yeni Rezervasyon';
            openModal('rezervasyonModal');
        }
        
        function rezervasyonDuzenle(id) {
            fetch(apiUrl(`rezervasyon.php?action=getir&id=${id}`))
                .then(response => response.json())
                .then(rez => {
                    document.getElementById('rezervasyon_id').value = rez.id;
                    document.getElementById('musteri_id').value = rez.musteri_id || '';
                    document.getElementById('masa_id').value = rez.masa_id;
                    document.getElementById('rezervasyon_tarihi').value = rez.rezervasyon_tarihi;
                    document.getElementById('rezervasyon_saati').value = rez.rezervasyon_saati;
                    document.getElementById('kisi_sayisi').value = rez.kisi_sayisi;
                    document.getElementById('notlar').value = rez.notlar || '';
                    document.getElementById('durum').value = rez.durum;
                    document.getElementById('modal-title').textContent = 'Rezervasyon Düzenle';
                    openModal('rezervasyonModal');
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Rezervasyon bilgileri yüklenirken hata oluştu');
                });
        }
        
        function rezervasyonSil(id) {
            if (confirm('Bu rezervasyonu silmek istediğinize emin misiniz?')) {
                fetch(apiUrl(`rezervasyon.php?action=sil&id=${id}`), { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess('Rezervasyon silindi');
                            loadRezervasyonlar();
                        } else {
                            showError(data.message || 'Rezervasyon silinirken hata oluştu');
                        }
                    })
                    .catch(error => {
                        console.error('Hata:', error);
                        showError('Rezervasyon silinirken hata oluştu');
                    });
            }
        }
        
        document.getElementById('rezervasyonForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            if (!data.musteri_id) data.musteri_id = null;
            const id = data.id;
            
            fetch(apiUrl(`rezervasyon.php?action=${id ? 'guncelle' : 'ekle'}`), {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess(result.message);
                    closeModal('rezervasyonModal');
                    loadRezervasyonlar();
                } else {
                    showError(result.message || 'İşlem başarısız');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showError('İşlem sırasında hata oluştu');
            });
        });
        
        document.getElementById('rezervasyonModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal('rezervasyonModal');
        });
    </script>
</body>
</html>
