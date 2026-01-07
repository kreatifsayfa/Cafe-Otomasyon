<?php
require_once 'config/config.php';
checkLogin();
checkRole(['admin', 'garson']);
$page_title = 'Masa İşlemleri';
?>
<?php include 'includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar_tailwind.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <header class="bg-white shadow-sm border-b border-gray-200 z-40">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Masa İşlemleri</h1>
                        <p class="text-sm text-gray-500 mt-1">Masaları birleştirin veya transfer edin</p>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="masaBirlesModal()" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold px-6 py-2.5 rounded-lg shadow-lg hover:shadow-xl transition-all">
                            <i class="fas fa-compress-arrows-alt mr-2"></i>Masa Birleştir
                        </button>
                        <button onclick="masaTransferModal()" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-6 py-2.5 rounded-lg transition-colors">
                            <i class="fas fa-exchange-alt mr-2"></i>Masa Transfer
                        </button>
                    </div>
                </div>
            </header>
            
            <main class="flex-1 overflow-y-auto p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-4">
                        <select id="masa-filtre" onchange="loadMasalar()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                            <option value="">Tüm Masalar</option>
                            <option value="dolu">Dolu Masalar</option>
                            <option value="bos">Boş Masalar</option>
                        </select>
                    </div>
                    <div id="masalar-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <!-- Masalar buraya yüklenecek -->
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Masa Birleştirme Modal -->
    <div id="birlesModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 p-6 text-white">
                <h2 class="text-2xl font-bold">Masa Birleştir</h2>
            </div>
            <form id="birlesForm" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ana Masa</label>
                    <select id="ana_masa" name="ana_masa_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        <option value="">Masa Seçin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Birleştirilecek Masa</label>
                    <select id="birlesen_masa" name="birlesen_masa_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        <option value="">Masa Seçin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Açıklama</label>
                    <textarea id="birles_aciklama" name="aciklama" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"></textarea>
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('birlesModal')" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 px-4 rounded-lg">
                        İptal
                    </button>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-2.5 px-4 rounded-lg">
                        Birleştir
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Masa Transfer Modal -->
    <div id="transferModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 p-6 text-white">
                <h2 class="text-2xl font-bold">Masa Transfer</h2>
            </div>
            <form id="transferForm" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Eski Masa</label>
                    <select id="eski_masa" name="eski_masa_id" required onchange="loadSiparisler()" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        <option value="">Masa Seçin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sipariş Seç</label>
                    <select id="siparis_sec" name="siparis_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        <option value="">Önce masa seçin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Yeni Masa</label>
                    <select id="yeni_masa" name="yeni_masa_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        <option value="">Masa Seçin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Açıklama</label>
                    <textarea id="transfer_aciklama" name="aciklama" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"></textarea>
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('transferModal')" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 px-4 rounded-lg">
                        İptal
                    </button>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white font-semibold py-2.5 px-4 rounded-lg">
                        Transfer Et
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
            loadMasalar();
        });
        
        function loadMasalar() {
            const durum = document.getElementById('masa-filtre').value;
            let url = apiUrl('masalar.php');
            if (durum) url += `?durum=${durum}`;
            
            fetch(apiUrl('masalar.php' + (durum ? '?durum=' + durum : '')))
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('masalar-container');
                    const doluMasalar = data.filter(m => m.durum === 'dolu');
                    
                    container.innerHTML = data.map(masa => {
                        const durumRenk = {
                            'bos': 'bg-green-100 text-green-800 border-green-200',
                            'dolu': 'bg-red-100 text-red-800 border-red-200',
                            'rezerve': 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'temizlik': 'bg-gray-100 text-gray-800 border-gray-200'
                        };
                        
                        return `
                            <div class="bg-white border-2 ${durumRenk[masa.durum]} rounded-xl p-5 hover:shadow-lg transition-all">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-lg font-semibold text-gray-900">Masa ${masa.masa_no}</h3>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold border ${durumRenk[masa.durum]}">${getMasaDurumText(masa.durum)}</span>
                                </div>
                                <div class="space-y-2">
                                    <p class="text-sm text-gray-600"><i class="fas fa-users mr-2"></i>${masa.kapasite} Kişi</p>
                                    <p class="text-sm text-gray-600"><i class="fas fa-map-marker-alt mr-2"></i>${masa.konum || 'Belirtilmemiş'}</p>
                                </div>
                            </div>
                        `;
                    }).join('');
                    
                    const anaMasa = document.getElementById('ana_masa');
                    const birlesenMasa = document.getElementById('birlesen_masa');
                    const eskiMasa = document.getElementById('eski_masa');
                    const yeniMasa = document.getElementById('yeni_masa');
                    
                    [anaMasa, birlesenMasa, eskiMasa, yeniMasa].forEach(select => {
                        if (select) {
                            select.innerHTML = '<option value="">Masa Seçin</option>';
                            doluMasalar.forEach(masa => {
                                const option = new Option(`Masa ${masa.masa_no}`, masa.id);
                                select.add(option);
                            });
                        }
                    });
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Masalar yüklenirken hata oluştu');
                });
        }
        
        function loadSiparisler() {
            const masaId = document.getElementById('eski_masa').value;
            if (!masaId) {
                document.getElementById('siparis_sec').innerHTML = '<option value="">Önce masa seçin</option>';
                return;
            }
            
            fetch(apiUrl(`siparis.php?action=listele&masa_id=${masaId}`))
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('siparis_sec');
                    select.innerHTML = '<option value="">Sipariş Seçin</option>';
                    data.forEach(siparis => {
                        if (siparis.odeme_durumu !== 'odendi') {
                            const option = new Option(`${siparis.siparis_no} - ${formatMoney(siparis.toplam_tutar)}`, siparis.id);
                            select.add(option);
                        }
                    });
                });
        }
        
        function masaBirlesModal() {
            document.getElementById('birlesForm').reset();
            openModal('birlesModal');
        }
        
        function masaTransferModal() {
            document.getElementById('transferForm').reset();
            document.getElementById('siparis_sec').innerHTML = '<option value="">Önce masa seçin</option>';
            openModal('transferModal');
        }
        
        document.getElementById('birlesForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            if (data.ana_masa_id === data.birlesen_masa_id) {
                showError('Aynı masayı seçemezsiniz!');
                return;
            }
            
            fetch(apiUrl('masa_islemleri.php?action=birles'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Masalar başarıyla birleştirildi');
                    closeModal('birlesModal');
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
        
        document.getElementById('transferForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            if (data.eski_masa_id === data.yeni_masa_id) {
                showError('Aynı masayı seçemezsiniz!');
                return;
            }
            
            fetch(apiUrl('masa_islemleri.php?action=transfer'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Sipariş başarıyla transfer edildi');
                    closeModal('transferModal');
                    loadMasalar();
                    setTimeout(() => window.location.href = url('index.php'), 1500);
                } else {
                    showError(result.message || 'İşlem başarısız');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showError('İşlem sırasında hata oluştu');
            });
        });
        
        document.querySelectorAll('[id$="Modal"]').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) this.classList.add('hidden');
            });
        });
    </script>
</body>
</html>
