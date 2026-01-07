<?php
require_once 'config/config.php';
checkLogin();
checkRole(['admin']);
$page_title = 'Gider-Gelir Yönetimi';
?>
<?php include 'includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar_tailwind.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <header class="bg-white shadow-sm border-b border-gray-200 z-40">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Gider-Gelir Yönetimi</h1>
                        <p class="text-sm text-gray-500 mt-1">Gider ve gelirleri takip edin</p>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="exportExcel('gider_gelir')" class="bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2.5 rounded-lg transition-colors">
                            <i class="fas fa-file-excel mr-2"></i>Excel Export
                        </button>
                        <button onclick="yeniGiderModal()" class="bg-red-500 hover:bg-red-600 text-white font-semibold px-4 py-2.5 rounded-lg transition-colors">
                            <i class="fas fa-minus-circle mr-2"></i>Gider Ekle
                        </button>
                        <button onclick="yeniGelirModal()" class="bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2.5 rounded-lg transition-colors">
                            <i class="fas fa-plus-circle mr-2"></i>Gelir Ekle
                        </button>
                    </div>
                </div>
            </header>
            
            <main class="flex-1 overflow-y-auto p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex space-x-3 mb-6">
                        <select id="tip-filtre" onchange="loadKayitlar()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                            <option value="">Tümü</option>
                            <option value="gider">Giderler</option>
                            <option value="gelir">Gelirler</option>
                        </select>
                        <input type="date" id="baslangic-tarih" value="<?php echo date('Y-m-01'); ?>" onchange="loadKayitlar()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        <input type="date" id="bitis-tarih" value="<?php echo date('Y-m-d'); ?>" onchange="loadKayitlar()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-red-50 border-2 border-red-200 rounded-xl p-5">
                            <h3 class="text-sm text-red-600 mb-2">Toplam Gider</h3>
                            <p class="text-3xl font-bold text-red-900" id="toplam-gider">0,00 ₺</p>
                        </div>
                        <div class="bg-green-50 border-2 border-green-200 rounded-xl p-5">
                            <h3 class="text-sm text-green-600 mb-2">Toplam Gelir</h3>
                            <p class="text-3xl font-bold text-green-900" id="toplam-gelir">0,00 ₺</p>
                        </div>
                        <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-5" id="net-kar-card">
                            <h3 class="text-sm text-blue-600 mb-2">Net Kar/Zarar</h3>
                            <p class="text-3xl font-bold text-blue-900" id="net-kar">0,00 ₺</p>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tip</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Açıklama</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tutar</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Personel</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody id="kayit-list" class="bg-white divide-y divide-gray-200">
                                <!-- Kayıtlar -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Gider Modal -->
    <div id="giderModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-red-500 to-red-600 p-6 text-white">
                <h2 class="text-2xl font-bold">Yeni Gider</h2>
            </div>
            <form id="giderForm" class="p-6 space-y-4">
                <input type="hidden" id="gider_id" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gider Tipi</label>
                    <input type="text" id="gider_tipi" name="gider_tipi" required placeholder="Örn: Kira, Elektrik, Personel" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                    <select id="gider_kategori" name="kategori" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        <option value="kira">Kira</option>
                        <option value="elektrik">Elektrik</option>
                        <option value="su">Su</option>
                        <option value="internet">İnternet</option>
                        <option value="personel">Personel</option>
                        <option value="malzeme">Malzeme</option>
                        <option value="diger">Diğer</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tutar (₺)</label>
                    <input type="number" id="gider_tutar" name="tutar" step="0.01" min="0" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tarih</label>
                    <input type="date" id="gider_tarih" name="tarih" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Açıklama</label>
                    <textarea id="gider_aciklama" name="aciklama" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500"></textarea>
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('giderModal')" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 px-4 rounded-lg">
                        İptal
                    </button>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold py-2.5 px-4 rounded-lg">
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Gelir Modal -->
    <div id="gelirModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 text-white">
                <h2 class="text-2xl font-bold">Yeni Gelir</h2>
            </div>
            <form id="gelirForm" class="p-6 space-y-4">
                <input type="hidden" id="gelir_id" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gelir Tipi</label>
                    <input type="text" id="gelir_tipi" name="gelir_tipi" required placeholder="Örn: Yatırım, Bağış, Diğer" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                    <select id="gelir_kategori" name="kategori" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="yatirim">Yatırım</option>
                        <option value="bagis">Bağış</option>
                        <option value="faiz">Faiz</option>
                        <option value="diger">Diğer</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tutar (₺)</label>
                    <input type="number" id="gelir_tutar" name="tutar" step="0.01" min="0" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tarih</label>
                    <input type="date" id="gelir_tarih" name="tarih" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Açıklama</label>
                    <textarea id="gelir_aciklama" name="aciklama" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('gelirModal')" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 px-4 rounded-lg">
                        İptal
                    </button>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-2.5 px-4 rounded-lg">
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
            loadKayitlar();
        });
        
        function loadKayitlar() {
            const tip = document.getElementById('tip-filtre').value;
            const baslangic = document.getElementById('baslangic-tarih').value;
            const bitis = document.getElementById('bitis-tarih').value;
            
            let url = apiUrl('gider_gelir.php?action=listele');
            if (tip) url += `&tip=${tip}`;
            if (baslangic) url += `&baslangic=${baslangic}`;
            if (bitis) url += `&bitis=${bitis}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('kayit-list');
                    let toplamGider = 0;
                    let toplamGelir = 0;
                    
                    if (data.length > 0) {
                        tbody.innerHTML = data.map(kayit => {
                            const tutar = parseFloat(kayit.tutar);
                            if (kayit.tip === 'gider') {
                                toplamGider += tutar;
                            } else {
                                toplamGelir += tutar;
                            }
                            
                            return `
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">${formatDate(kayit.tarih)}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${kayit.tip === 'gider' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}">${kayit.tip === 'gider' ? 'Gider' : 'Gelir'}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">${kayit.kategori || '-'}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">${kayit.aciklama || kayit.gider_tipi || kayit.gelir_tipi}</td>
                                    <td class="px-4 py-3 text-sm font-semibold ${kayit.tip === 'gider' ? 'text-red-600' : 'text-green-600'}">${kayit.tip === 'gider' ? '-' : '+'}${formatMoney(tutar)}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">${kayit.personel_adi || '-'}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex space-x-2">
                                            <button onclick="${kayit.tip === 'gider' ? 'giderDuzenle' : 'gelirDuzenle'}(${kayit.id})" class="text-amber-600 hover:text-amber-700">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="${kayit.tip === 'gider' ? 'giderSil' : 'gelirSil'}(${kayit.id})" class="text-red-600 hover:text-red-700">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Kayıt bulunamadı</td></tr>';
                    }
                    
                    document.getElementById('toplam-gider').textContent = formatMoney(toplamGider);
                    document.getElementById('toplam-gelir').textContent = formatMoney(toplamGelir);
                    const netKar = toplamGelir - toplamGider;
                    document.getElementById('net-kar').textContent = formatMoney(netKar);
                    const netKarCard = document.getElementById('net-kar-card');
                    if (netKar >= 0) {
                        netKarCard.className = 'bg-green-50 border-2 border-green-200 rounded-xl p-5';
                        netKarCard.querySelector('h3').className = 'text-sm text-green-600 mb-2';
                        netKarCard.querySelector('p').className = 'text-3xl font-bold text-green-900';
                    } else {
                        netKarCard.className = 'bg-red-50 border-2 border-red-200 rounded-xl p-5';
                        netKarCard.querySelector('h3').className = 'text-sm text-red-600 mb-2';
                        netKarCard.querySelector('p').className = 'text-3xl font-bold text-red-900';
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Kayıtlar yüklenirken hata oluştu');
                });
        }
        
        function yeniGiderModal() {
            document.getElementById('giderForm').reset();
            document.getElementById('gider_id').value = '';
            document.getElementById('gider_tarih').value = new Date().toISOString().split('T')[0];
            openModal('giderModal');
        }
        
        function yeniGelirModal() {
            document.getElementById('gelirForm').reset();
            document.getElementById('gelir_id').value = '';
            document.getElementById('gelir_tarih').value = new Date().toISOString().split('T')[0];
            openModal('gelirModal');
        }
        
        function giderDuzenle(id) {
            fetch(apiUrl(`gider_gelir.php?action=getir&tip=gider&id=${id}`))
                .then(response => response.json())
                .then(gider => {
                    document.getElementById('gider_id').value = gider.id;
                    document.getElementById('gider_tipi').value = gider.gider_tipi;
                    document.getElementById('gider_kategori').value = gider.kategori || '';
                    document.getElementById('gider_tutar').value = gider.tutar;
                    document.getElementById('gider_tarih').value = gider.tarih;
                    document.getElementById('gider_aciklama').value = gider.aciklama || '';
                    openModal('giderModal');
                });
        }
        
        function gelirDuzenle(id) {
            fetch(apiUrl(`gider_gelir.php?action=getir&tip=gelir&id=${id}`))
                .then(response => response.json())
                .then(gelir => {
                    document.getElementById('gelir_id').value = gelir.id;
                    document.getElementById('gelir_tipi').value = gelir.gelir_tipi;
                    document.getElementById('gelir_kategori').value = gelir.kategori || '';
                    document.getElementById('gelir_tutar').value = gelir.tutar;
                    document.getElementById('gelir_tarih').value = gelir.tarih;
                    document.getElementById('gelir_aciklama').value = gelir.aciklama || '';
                    openModal('gelirModal');
                });
        }
        
        function giderSil(id) {
            if (confirm('Bu gideri silmek istediğinize emin misiniz?')) {
                fetch(apiUrl(`gider_gelir.php?action=sil&tip=gider&id=${id}`), { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess('Gider silindi');
                            loadKayitlar();
                        } else {
                            showError(data.message);
                        }
                    });
            }
        }
        
        function gelirSil(id) {
            if (confirm('Bu geliri silmek istediğinize emin misiniz?')) {
                fetch(apiUrl(`gider_gelir.php?action=sil&tip=gelir&id=${id}`), { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess('Gelir silindi');
                            loadKayitlar();
                        } else {
                            showError(data.message);
                        }
                    });
            }
        }
        
        function exportExcel(tip) {
            const baslangic = document.getElementById('baslangic-tarih').value;
            const bitis = document.getElementById('bitis-tarih').value;
            window.open(apiUrl(`export.php?tip=${tip}&baslangic=${baslangic}&bitis=${bitis}`), '_blank');
        }
        
        document.getElementById('giderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            const id = data.id;
            
            fetch(apiUrl(`gider_gelir.php?action=${id ? 'guncelle' : 'ekle'}&tip=gider`), {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess(result.message);
                    closeModal('giderModal');
                    loadKayitlar();
                } else {
                    showError(result.message);
                }
            });
        });
        
        document.getElementById('gelirForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            const id = data.id;
            
            fetch(apiUrl(`gider_gelir.php?action=${id ? 'guncelle' : 'ekle'}&tip=gelir`), {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess(result.message);
                    closeModal('gelirModal');
                    loadKayitlar();
                } else {
                    showError(result.message);
                }
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
