<?php
require_once 'config/config.php';
checkLogin();
checkRole(['admin']);
$page_title = 'Ayarlar';
?>
<?php include 'includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar_tailwind.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <header class="bg-white shadow-sm border-b border-gray-200 z-40">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Ayarlar</h1>
                        <p class="text-sm text-gray-500 mt-1">Sistem ayarlarını yönetin</p>
                    </div>
                </div>
            </header>
            
            <main class="flex-1 overflow-y-auto p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Genel Ayarlar -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Genel Ayarlar -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-cog mr-2 text-amber-600"></i>Genel Ayarlar
                            </h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Site Adı</label>
                                    <input type="text" id="site_adi" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">KDV Oranı (%)</label>
                                    <input type="number" id="kdv_orani" step="0.01" min="0" max="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Para Birimi</label>
                                    <input type="text" id="para_birimi" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" id="otomatik_yazdir" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                                    <label for="otomatik_yazdir" class="ml-2 text-sm font-medium text-gray-700">Sipariş alındığında otomatik yazdır</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" id="mutfak_görünümü_aktif" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                                    <label for="mutfak_görünümü_aktif" class="ml-2 text-sm font-medium text-gray-700">Mutfak görünümü aktif (eski sistem)</label>
                                </div>
                                <button onclick="genelAyarlariKaydet()" class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-2 px-4 rounded-lg transition-all">
                                    <i class="fas fa-save mr-2"></i>Kaydet
                                </button>
                            </div>
                        </div>
                        
                        <!-- Yazıcı Ayarları -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-print mr-2 text-amber-600"></i>Yazıcı Ayarları
                                </h2>
                                <button onclick="yeniYaziciModal()" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-4 py-2 rounded-lg transition-colors">
                                    <i class="fas fa-plus mr-2"></i>Yeni Yazıcı
                                </button>
                            </div>
                            
                            <!-- Dokümantasyon -->
                            <div class="mb-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded-lg">
                                <div class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-blue-900 mb-2">Yazıcı Kurulum Kılavuzu</h3>
                                        <div class="text-sm text-blue-800 space-y-2">
                                            <p><strong>1. Lokasyon Seçimi:</strong></p>
                                            <ul class="list-disc list-inside ml-2 space-y-1">
                                                <li><strong>Mutfak:</strong> Yemek siparişleri bu yazıcıya gönderilir</li>
                                                <li><strong>Bar:</strong> İçecek siparişleri bu yazıcıya gönderilir</li>
                                                <li><strong>Kasa:</strong> Fiş yazdırma için kullanılır</li>
                                            </ul>
                                            <p class="mt-2"><strong>2. Yazıcı Bağlantı Türleri:</strong></p>
                                            <ul class="list-disc list-inside ml-2 space-y-1">
                                                <li><strong>Network Yazıcı:</strong> IP adresi ve port girin (örn: 192.168.1.100:9100)</li>
                                                <li><strong>Local Yazıcı:</strong> IP adresi boş bırakın, Windows'ta görünen yazıcı adını girin</li>
                                            </ul>
                                            <p class="mt-2"><strong>3. Yazıcı Adı:</strong> Windows'ta "Aygıtlar ve Yazıcılar" bölümünde görünen tam adı yazın</p>
                                            <p class="mt-2"><strong>4. Test:</strong> Yazıcıyı kaydettikten sonra bir sipariş alarak test edin</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="yazicilari-list" class="space-y-3">
                                <!-- Yazıcılar buraya yüklenecek -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kullanıcı Yetkileri -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-user-shield mr-2 text-amber-600"></i>Kullanıcı Yetkileri
                        </h2>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Personel Seç</label>
                            <select id="personel-select" onchange="personelYetkileriYukle()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                <option value="">Personel Seçin</option>
                            </select>
                        </div>
                        <div id="yetkiler-list" class="space-y-2">
                            <p class="text-gray-500 text-sm text-center py-4">Lütfen personel seçin</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Yazıcı Ekleme/Düzenleme Modal -->
    <div id="yazici-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900" id="modal-baslik">Yeni Yazıcı</h3>
                <button onclick="yaziciModalKapat()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Hızlı Kılavuz -->
            <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-lightbulb text-amber-600 mt-0.5 mr-2"></i>
                    <div class="text-xs text-amber-800">
                        <p class="font-semibold mb-1">Hızlı Bilgi:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li><strong>Yazıcı Bulma:</strong> 🔍 Butonuna tıklayarak bilgisayardaki yazıcıları listeleyin</li>
                            <li><strong>Network:</strong> IP adresi girin (örn: 192.168.1.100)</li>
                            <li><strong>Local:</strong> IP boş, Windows yazıcı adını yazın veya listeden seçin</li>
                            <li><strong>Lokasyon:</strong> Mutfak=Yemek, Bar=İçecek, Kasa=Fiş</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <form id="yazici-form" onsubmit="yaziciKaydet(event)">
                <input type="hidden" id="yazici_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Yazıcı Adı <span class="text-red-500">*</span>
                            <span class="text-xs text-gray-500 font-normal ml-2">(Windows'ta görünen tam ad)</span>
                        </label>
                        <div class="flex space-x-2">
                            <input type="text" id="yazici_adi" required placeholder="Örn: HP LaserJet Pro" list="yazici-listesi" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            <button type="button" onclick="bilgisayarYazicilariniYukle()" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors" title="Bilgisayardaki yazıcıları listele">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <datalist id="yazici-listesi">
                            <!-- Yazıcılar buraya yüklenecek -->
                        </datalist>
                        <div id="yazici-yukleniyor" class="hidden mt-2 text-xs text-blue-600">
                            <i class="fas fa-spinner fa-spin mr-1"></i>Yazıcılar yükleniyor...
                        </div>
                        <div id="yazici-hata" class="hidden mt-2 text-xs text-red-600"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Lokasyon <span class="text-red-500">*</span>
                        </label>
                        <select id="yazici_lokasyon" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            <option value="mutfak">🍳 Mutfak (Yemek siparişleri)</option>
                            <option value="bar">🍹 Bar (İçecek siparişleri)</option>
                            <option value="kasa">💰 Kasa (Fiş yazdırma)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Yazıcı Tipi <span class="text-red-500">*</span>
                        </label>
                        <select id="yazici_tipi" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            <option value="fis">📄 Fiş (Kasa için)</option>
                            <option value="etiket">🏷️ Etiket</option>
                            <option value="mutfak">🍽️ Mutfak (Sipariş için)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            IP Adresi 
                            <span class="text-xs text-gray-500 font-normal ml-2">(Sadece network yazıcılar için)</span>
                        </label>
                        <input type="text" id="yazici_ip" placeholder="192.168.1.100 (boş bırakırsanız local yazıcı)" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">💡 IP adresi girmezseniz, Windows'taki yazıcı adı kullanılır</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Port 
                            <span class="text-xs text-gray-500 font-normal ml-2">(Network yazıcılar için)</span>
                        </label>
                        <input type="number" id="yazici_port" value="9100" placeholder="9100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Varsayılan port: 9100 (çoğu yazıcı için)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Açıklama</label>
                        <textarea id="yazici_aciklama" rows="2" placeholder="Yazıcı hakkında notlar..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"></textarea>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="yazici_durum" checked class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                        <label for="yazici_durum" class="ml-2 text-sm font-medium text-gray-700">Aktif (Yazıcı kullanımda)</label>
                    </div>
                    <div class="flex space-x-3 pt-4">
                        <button type="submit" class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-2 px-4 rounded-lg transition-all">
                            <i class="fas fa-save mr-2"></i>Kaydet
                        </button>
                        <button type="button" onclick="yaziciModalKapat()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-colors">
                            İptal
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Örnek Kullanım -->
            <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                <p class="text-xs font-semibold text-gray-700 mb-2">📋 Örnek Senaryolar:</p>
                <div class="text-xs text-gray-600 space-y-1">
                    <p><strong>Network Yazıcı:</strong> Adı: "Mutfak Yazıcısı", IP: "192.168.1.100", Port: "9100"</p>
                    <p><strong>Local Yazıcı:</strong> Adı: "HP LaserJet Pro M404dn" (Windows'taki tam ad), IP: boş bırakın</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/base_url_script.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/tailwind-helpers.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            ayarlariYukle();
            yazicilariYukle();
            personelleriYukle();
        });
        
        function ayarlariYukle() {
            fetch(apiUrl('ayarlar.php?action=listele'))
                .then(response => response.json())
                .then(data => {
                    if (data.site_adi) document.getElementById('site_adi').value = data.site_adi;
                    if (data.kdv_orani) document.getElementById('kdv_orani').value = data.kdv_orani;
                    if (data.para_birimi) document.getElementById('para_birimi').value = data.para_birimi;
                    if (data.otomatik_yazdir === '1') document.getElementById('otomatik_yazdir').checked = true;
                    if (data.mutfak_görünümü_aktif === '1') document.getElementById('mutfak_görünümü_aktif').checked = true;
                });
        }
        
        function genelAyarlariKaydet() {
            const data = {
                site_adi: document.getElementById('site_adi').value,
                kdv_orani: document.getElementById('kdv_orani').value,
                para_birimi: document.getElementById('para_birimi').value,
                otomatik_yazdir: document.getElementById('otomatik_yazdir').checked ? '1' : '0',
                mutfak_görünümü_aktif: document.getElementById('mutfak_görünümü_aktif').checked ? '1' : '0'
            };
            
            fetch(apiUrl('ayarlar.php?action=kaydet'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Ayarlar kaydedildi');
                } else {
                    showError(result.message || 'Hata oluştu');
                }
            });
        }
        
        function yazicilariYukle() {
            fetch(apiUrl('ayarlar.php?action=yazicilar'))
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('yazicilari-list');
                    if (data.length > 0) {
                        container.innerHTML = data.map(y => `
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <div>
                                    <h3 class="font-semibold text-gray-900">${y.yazici_adi}</h3>
                                    <p class="text-sm text-gray-500">${y.lokasyon} - ${y.yazici_tipi}</p>
                                    ${y.ip_adresi ? `<p class="text-xs text-gray-400">${y.ip_adresi}:${y.port}</p>` : ''}
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${y.durum === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                        ${y.durum === 'aktif' ? 'Aktif' : 'Pasif'}
                                    </span>
                                    <button onclick="yaziciDuzenle(${y.id})" class="text-amber-600 hover:text-amber-700">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="yaziciSil(${y.id})" class="text-red-600 hover:text-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<p class="text-gray-500 text-center py-4">Yazıcı bulunamadı</p>';
                    }
                });
        }
        
        function yeniYaziciModal() {
            document.getElementById('modal-baslik').textContent = 'Yeni Yazıcı';
            document.getElementById('yazici-form').reset();
            document.getElementById('yazici_id').value = '';
            document.getElementById('yazici_ip').value = '';
            document.getElementById('yazici_port').value = '9100';
            document.getElementById('yazici-modal').classList.remove('hidden');
            // Bilgisayardaki yazıcıları otomatik yükle
            bilgisayarYazicilariniYukle();
        }
        
        // Yazıcı verilerini global olarak sakla
        let yaziciDataMap = {};
        let yaziciEventListenersAdded = false;
        
        function bilgisayarYazicilariniYukle() {
            const yukleniyor = document.getElementById('yazici-yukleniyor');
            const hata = document.getElementById('yazici-hata');
            const datalist = document.getElementById('yazici-listesi');
            const yaziciAdiInput = document.getElementById('yazici_adi');
            
            yukleniyor.classList.remove('hidden');
            hata.classList.add('hidden');
            datalist.innerHTML = '';
            
            fetch(apiUrl('ayarlar.php?action=yazicilari_listele'))
                .then(response => response.json())
                .then(result => {
                    yukleniyor.classList.add('hidden');
                    
                    if (result.success && result.yazicilar && result.yazicilar.length > 0) {
                        // Yazıcı verilerini temizle ve yeniden doldur
                        yaziciDataMap = {};
                        
                        // Yazıcıları datalist'e ekle ve IP/Port bilgilerini sakla
                        result.yazicilar.forEach(yazici => {
                            const option = document.createElement('option');
                            option.value = yazici.name;
                            option.textContent = yazici.display_name || yazici.name;
                            datalist.appendChild(option);
                            
                            // IP ve Port bilgilerini global map'e sakla
                            yaziciDataMap[yazici.name] = {
                                ip: yazici.ip || '',
                                port: yazici.port || 9100
                            };
                        });
                        
                        // Event listener'ları sadece bir kez ekle
                        if (!yaziciEventListenersAdded) {
                            // Yazıcı seçildiğinde IP ve Port'u otomatik doldur
                            yaziciAdiInput.addEventListener('change', function() {
                                yaziciSecildi();
                            });
                            
                            // Input event'i de ekle (yazarken de çalışsın)
                            yaziciAdiInput.addEventListener('input', function() {
                                yaziciSecildi();
                            });
                            
                            yaziciEventListenersAdded = true;
                        }
                        
                        // Başarı mesajı göster
                        showSuccess(`${result.yazicilar.length} yazıcı bulundu. Listeden seçtiğinizde IP ve Port otomatik doldurulacak.`);
                    } else {
                        hata.textContent = 'Bilgisayarda yazıcı bulunamadı veya erişilemedi.';
                        hata.classList.remove('hidden');
                        showError('Yazıcılar yüklenemedi. Manuel olarak yazıcı adını girebilirsiniz.');
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    yukleniyor.classList.add('hidden');
                    hata.textContent = 'Yazıcılar yüklenirken hata oluştu. Manuel olarak yazıcı adını girebilirsiniz.';
                    hata.classList.remove('hidden');
                    showError('Yazıcılar yüklenemedi. Manuel olarak yazıcı adını girebilirsiniz.');
                });
        }
        
        function yaziciSecildi() {
            const yaziciAdiInput = document.getElementById('yazici_adi');
            const selectedName = yaziciAdiInput.value.trim();
            
            if (selectedName && yaziciDataMap[selectedName]) {
                const data = yaziciDataMap[selectedName];
                const ipInput = document.getElementById('yazici_ip');
                const portInput = document.getElementById('yazici_port');
                
                // IP ve Port'u doldur
                if (data.ip) {
                    ipInput.value = data.ip;
                } else {
                    // IP yoksa boş bırak (local yazıcı olabilir)
                    ipInput.value = '';
                }
                
                if (data.port) {
                    portInput.value = data.port;
                } else {
                    portInput.value = 9100;
                }
            }
        }
        
        function yaziciDuzenle(id) {
            fetch(apiUrl(`ayarlar.php?action=yazici_getir&id=${id}`))
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modal-baslik').textContent = 'Yazıcı Düzenle';
                    document.getElementById('yazici_id').value = data.id;
                    document.getElementById('yazici_adi').value = data.yazici_adi;
                    document.getElementById('yazici_lokasyon').value = data.lokasyon;
                    document.getElementById('yazici_tipi').value = data.yazici_tipi;
                    document.getElementById('yazici_ip').value = data.ip_adresi || '';
                    document.getElementById('yazici_port').value = data.port || 9100;
                    document.getElementById('yazici_aciklama').value = data.aciklama || '';
                    document.getElementById('yazici_durum').checked = data.durum === 'aktif';
                    document.getElementById('yazici-modal').classList.remove('hidden');
                    // Düzenleme modunda yazıcıları yükle (IP/Port güncellemesi için)
                    bilgisayarYazicilariniYukle();
                });
        }
        
        function yaziciModalKapat() {
            document.getElementById('yazici-modal').classList.add('hidden');
        }
        
        function yaziciKaydet(e) {
            e.preventDefault();
            const data = {
                id: document.getElementById('yazici_id').value || null,
                yazici_adi: document.getElementById('yazici_adi').value,
                lokasyon: document.getElementById('yazici_lokasyon').value,
                yazici_tipi: document.getElementById('yazici_tipi').value,
                ip_adresi: document.getElementById('yazici_ip').value,
                port: document.getElementById('yazici_port').value,
                aciklama: document.getElementById('yazici_aciklama').value,
                durum: document.getElementById('yazici_durum').checked ? 'aktif' : 'pasif'
            };
            
            const action = data.id ? 'yazici_guncelle' : 'yazici_ekle';
            
            fetch(apiUrl(`ayarlar.php?action=${action}`), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Yazıcı kaydedildi');
                    yaziciModalKapat();
                    yazicilariYukle();
                } else {
                    showError(result.message || 'Hata oluştu');
                }
            });
        }
        
        function yaziciSil(id) {
            if (!confirm('Bu yazıcıyı silmek istediğinize emin misiniz?')) return;
            
            fetch(apiUrl('ayarlar.php?action=yazici_sil'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Yazıcı silindi');
                    yazicilariYukle();
                } else {
                    showError(result.message || 'Hata oluştu');
                }
            });
        }
        
        function personelleriYukle() {
            fetch(apiUrl('personel.php?action=listele'))
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('personel-select');
                    select.innerHTML = '<option value="">Personel Seçin</option>';
                    data.forEach(p => {
                        const option = new Option(p.ad_soyad, p.id);
                        select.add(option);
                    });
                });
        }
        
        function personelYetkileriYukle() {
            const personelId = document.getElementById('personel-select').value;
            if (!personelId) {
                document.getElementById('yetkiler-list').innerHTML = '<p class="text-gray-500 text-sm text-center py-4">Lütfen personel seçin</p>';
                return;
            }
            
            fetch(apiUrl(`ayarlar.php?action=personel_yetkileri&personel_id=${personelId}`))
                .then(response => response.json())
                .then(data => {
                    const yetkiler = [
                        { id: 'siparis_al', adi: 'Sipariş Al', aciklama: 'Sipariş alma yetkisi' },
                        { id: 'siparis_iptal', adi: 'Sipariş İptal', aciklama: 'Sipariş iptal etme yetkisi' },
                        { id: 'odeme_al', adi: 'Ödeme Al', aciklama: 'Ödeme alma yetkisi' },
                        { id: 'masa_birlestir', adi: 'Masa Birleştir', aciklama: 'Masaları birleştirme yetkisi' },
                        { id: 'masa_degistir', adi: 'Masa Değiştir', aciklama: 'Masa değiştirme yetkisi' },
                        { id: 'siparis_hazir', adi: 'Sipariş Hazır', aciklama: 'Sipariş hazır işaretleme yetkisi' },
                        { id: 'rapor_goruntule', adi: 'Rapor Görüntüle', aciklama: 'Rapor görüntüleme yetkisi' }
                    ];
                    
                    const container = document.getElementById('yetkiler-list');
                    container.innerHTML = yetkiler.map(yetki => {
                        const aktif = data.find(y => y.yetki_adi === yetki.id && y.durum);
                        return `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <div>
                                    <p class="font-medium text-gray-900">${yetki.adi}</p>
                                    <p class="text-xs text-gray-500">${yetki.aciklama}</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" ${aktif ? 'checked' : ''} onchange="yetkiGuncelle('${yetki.id}', this.checked)" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                                </label>
                            </div>
                        `;
                    }).join('');
                });
        }
        
        function yetkiGuncelle(yetkiAdi, durum) {
            const personelId = document.getElementById('personel-select').value;
            if (!personelId) return;
            
            fetch(apiUrl('ayarlar.php?action=yetki_guncelle'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    personel_id: personelId,
                    yetki_adi: yetkiAdi,
                    durum: durum
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Yetki güncellendi');
                } else {
                    showError(result.message || 'Hata oluştu');
                }
            });
        }
    </script>
</body>
</html>

