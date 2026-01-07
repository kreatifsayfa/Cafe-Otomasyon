<?php
require_once 'config/config.php';
checkLogin();
checkRole(['admin']);
$page_title = 'Personel Yönetimi';
?>
<?php include 'includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar_tailwind.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <header class="bg-white shadow-sm border-b border-gray-200 z-40">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 sm:py-4 gap-3">
                    <div>
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900">Personel Yönetimi</h1>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Personelleri görüntüleyin ve yönetin</p>
                    </div>
                    <button onclick="yeniPersonelModal()" class="w-full sm:w-auto bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold px-4 sm:px-6 py-2 sm:py-2.5 text-sm sm:text-base rounded-lg shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-plus mr-2"></i>Yeni Personel
                    </button>
                </div>
            </header>
            
            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                    <div class="table-responsive overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ad Soyad</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">E-posta</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telefon</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Maaş</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody id="personel-list" class="bg-white divide-y divide-gray-200">
                                <!-- Personel listesi -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="personelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 p-6 text-white">
                <h2 id="modal-title" class="text-2xl font-bold">Yeni Personel</h2>
            </div>
            <form id="personelForm" class="p-6 space-y-4">
                <input type="hidden" id="personel_id" name="id">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ad Soyad</label>
                        <input type="text" id="ad_soyad" name="ad_soyad" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">E-posta</label>
                        <input type="email" id="email" name="email" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Telefon</label>
                        <input type="text" id="telefon" name="telefon" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rol</label>
                        <select id="rol" name="rol" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                            <option value="garson">Garson</option>
                            <option value="kasiyer">Kasiyer</option>
                            <option value="mutfak">Mutfak</option>
                            <option value="barmen">Barmen</option>
                            <option value="sef">Şef</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Şifre</label>
                    <input type="password" id="sifre" name="sifre" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    <p class="text-xs text-gray-500 mt-1">Yeni personel için zorunlu, düzenlemede boş bırakılırsa değişmez</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Maaş (₺)</label>
                        <input type="number" id="maas" name="maas" step="0.01" min="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Durum</label>
                        <select id="durum" name="durum" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                            <option value="aktif">Aktif</option>
                            <option value="pasif">Pasif</option>
                        </select>
                    </div>
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('personelModal')" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 px-4 rounded-lg">
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
        function rolAdiGetir(rol) {
            const roller = {
                'admin': 'Admin',
                'garson': 'Garson',
                'kasiyer': 'Kasiyer',
                'mutfak': 'Mutfak',
                'barmen': 'Barmen',
                'sef': 'Şef'
            };
            return roller[rol] || rol;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            loadPersonel();
        });
        
        function loadPersonel() {
            fetch(apiUrl('personel.php?action=listele'))
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('personel-list');
                    if (data.length > 0) {
                        tbody.innerHTML = data.map(personel => `
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">${personel.ad_soyad}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">${personel.email}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">${personel.telefon || '-'}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">${rolAdiGetir(personel.rol)}</span>
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900">${personel.maas ? formatMoney(personel.maas) : '-'}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold ${personel.durum === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">${personel.durum === 'aktif' ? 'Aktif' : 'Pasif'}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex space-x-2">
                                        <button onclick="personelDuzenle(${personel.id})" class="text-amber-600 hover:text-amber-700">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        ${personel.id != <?php echo $_SESSION['personel_id']; ?> ? `
                                        <button onclick="personelSil(${personel.id}, '${personel.ad_soyad}')" class="text-red-600 hover:text-red-700">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Personel bulunamadı</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Personel listesi yüklenirken hata oluştu');
                });
        }
        
        function yeniPersonelModal() {
            document.getElementById('personelForm').reset();
            document.getElementById('personel_id').value = '';
            document.getElementById('sifre').required = true;
            document.getElementById('modal-title').textContent = 'Yeni Personel';
            openModal('personelModal');
        }
        
        function personelDuzenle(id) {
            fetch(apiUrl(`personel.php?action=getir&id=${id}`))
                .then(response => response.json())
                .then(personel => {
                    document.getElementById('personel_id').value = personel.id;
                    document.getElementById('ad_soyad').value = personel.ad_soyad;
                    document.getElementById('email').value = personel.email;
                    document.getElementById('telefon').value = personel.telefon || '';
                    document.getElementById('sifre').value = '';
                    document.getElementById('sifre').required = false;
                    document.getElementById('rol').value = personel.rol;
                    document.getElementById('maas').value = personel.maas || '';
                    document.getElementById('durum').value = personel.durum;
                    document.getElementById('modal-title').textContent = 'Personel Düzenle';
                    openModal('personelModal');
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Personel bilgileri yüklenirken hata oluştu');
                });
        }
        
        function personelSil(id, ad) {
            if (confirm(`"${ad}" adlı personeli silmek istediğinize emin misiniz?`)) {
                fetch(apiUrl(`personel.php?action=sil&id=${id}`), { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess('Personel silindi');
                            loadPersonel();
                        } else {
                            showError(data.message || 'Personel silinirken hata oluştu');
                        }
                    })
                    .catch(error => {
                        console.error('Hata:', error);
                        showError('Personel silinirken hata oluştu');
                    });
            }
        }
        
        document.getElementById('personelForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            const id = data.id;
            
            if (!id && !data.sifre) {
                showError('Yeni personel için şifre zorunludur');
                return;
            }
            
            fetch(apiUrl(`personel.php?action=${id ? 'guncelle' : 'ekle'}`), {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess(result.message);
                    closeModal('personelModal');
                    loadPersonel();
                } else {
                    showError(result.message || 'İşlem başarısız');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showError('İşlem sırasında hata oluştu');
            });
        });
        
        document.getElementById('personelModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal('personelModal');
        });
    </script>
</body>
</html>
