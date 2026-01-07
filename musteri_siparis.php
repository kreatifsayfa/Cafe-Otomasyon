<?php
require_once 'config/config.php';

// QR menü için sadece garson girişi kontrolü
if (!isset($_SESSION['personel_id']) || $_SESSION['rol'] != 'garson') {
    header('Location: ' . BASE_URL . 'garson_giris.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$masa_id = isset($_GET['masa_id']) ? intval($_GET['masa_id']) : 0;

$stmt = $db->prepare("SELECT * FROM masa WHERE id = ?");
$stmt->execute([$masa_id]);
$masa = $stmt->fetch();

if (!$masa) {
    die('Masa bulunamadı!');
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masa <?php echo $masa['masa_no']; ?> - Sipariş Ver - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include 'includes/base_url_script.php'; ?>
</head>
<body class="bg-gradient-to-br from-amber-50 to-amber-100 min-h-screen pb-24">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="bg-white rounded-2xl shadow-2xl p-8 mb-6 text-center">
            <div class="w-20 h-20 bg-gradient-to-br from-amber-500 to-amber-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-coffee text-white text-4xl"></i>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Masa <?php echo htmlspecialchars($masa['masa_no']); ?></h1>
            <p class="text-gray-600">Menüden seçim yaparak sipariş verebilirsiniz</p>
        </div>
        
        <div class="flex space-x-3 mb-6">
            <input type="text" id="urun-ara" placeholder="Ürün ara..." onkeyup="loadUrunler()" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
            <select id="kategori-filtre" onchange="loadUrunler()" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                <option value="0">Tüm Kategoriler</option>
            </select>
        </div>
        
        <div id="urunler-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-6">
            <!-- Ürünler buraya yüklenecek -->
        </div>
    </div>
    
    <!-- Sepet -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t-4 border-amber-500 shadow-2xl z-50">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Sepet: <span id="sepet-toplam" class="text-amber-600">0,00 ₺</span></h3>
                    <p class="text-sm text-gray-500" id="sepet-adet">0 ürün</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="sepetiTemizle()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-trash mr-2"></i>Temizle
                    </button>
                    <button onclick="siparisGonder()" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold px-6 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-paper-plane mr-2"></i>Siparişi Gönder
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/tailwind-helpers.js"></script>
    <script>
        const masaId = <?php echo $masa_id; ?>;
        let sepet = [];
        let seciliKategori = 0;
        
        document.addEventListener('DOMContentLoaded', function() {
            loadKategoriler();
            loadUrunler();
        });
        
        function loadKategoriler() {
            fetch(apiUrl('menu.php?action=kategoriler'))
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('kategori-filtre');
                    data.forEach(kat => {
                        const option = new Option(kat.kategori_adi, kat.id);
                        select.add(option);
                    });
                });
        }
        
        function loadUrunler() {
            const arama = document.getElementById('urun-ara').value;
            const kategoriId = document.getElementById('kategori-filtre').value || 0;
            
            let endpoint = 'menu.php?action=urunler';
            if (kategoriId > 0) endpoint += `&kategori_id=${kategoriId}`;
            
            fetch(apiUrl(endpoint))
                .then(response => response.json())
                .then(data => {
                    let urunler = data;
                    if (arama) {
                        urunler = urunler.filter(u => u.urun_adi.toLowerCase().includes(arama.toLowerCase()));
                    }
                    
                    const container = document.getElementById('urunler-grid');
                    if (urunler.length > 0) {
                        container.innerHTML = urunler.map(urun => `
                            <div onclick="sepeteEkle(${urun.id}, '${urun.urun_adi}', ${urun.fiyat})" class="bg-white border-2 border-gray-200 rounded-xl p-5 hover:border-amber-500 hover:shadow-lg transition-all transform hover:-translate-y-1 cursor-pointer">
                                <div class="text-center">
                                    <div class="w-16 h-16 bg-gradient-to-br from-amber-100 to-amber-200 rounded-xl flex items-center justify-center mx-auto mb-3">
                                        <i class="fas fa-utensils text-amber-600 text-3xl"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">${urun.urun_adi}</h3>
                                    <p class="text-sm text-gray-500 mb-3">${urun.aciklama || ''}</p>
                                    <p class="text-xl font-bold text-amber-600">${formatMoney(urun.fiyat)}</p>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<p class="col-span-full text-center text-gray-500 py-12">Ürün bulunamadı</p>';
                    }
                });
        }
        
        function sepeteEkle(urunId, urunAdi, fiyat) {
            const mevcut = sepet.find(s => s.id === urunId);
            if (mevcut) {
                mevcut.adet++;
            } else {
                sepet.push({
                    id: urunId,
                    adi: urunAdi,
                    fiyat: fiyat,
                    adet: 1
                });
            }
            sepetGuncelle();
            showSuccess(`${urunAdi} sepete eklendi`);
        }
        
        function sepetiTemizle() {
            if (confirm('Sepeti temizlemek istediğinize emin misiniz?')) {
                sepet = [];
                sepetGuncelle();
            }
        }
        
        function sepetGuncelle() {
            const toplam = sepet.reduce((sum, item) => sum + (item.fiyat * item.adet), 0);
            const toplamAdet = sepet.reduce((sum, item) => sum + item.adet, 0);
            
            document.getElementById('sepet-toplam').textContent = formatMoney(toplam);
            document.getElementById('sepet-adet').textContent = `${toplamAdet} ürün`;
        }
        
        function siparisGonder() {
            if (sepet.length === 0) {
                showError('Sepetiniz boş!');
                return;
            }
            
            const urunler = sepet.map(item => ({
                urun_id: item.id,
                adet: item.adet,
                birim_fiyat: item.fiyat,
                toplam_fiyat: item.fiyat * item.adet
            }));
            
            fetch(apiUrl('siparis.php?action=yeni'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    masa_id: masaId,
                    musteri_id: null,
                    urunler: urunler
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Siparişiniz başarıyla alındı! Sipariş numaranız: ' + data.siparis_no);
                    sepet = [];
                    sepetGuncelle();
                    loadUrunler();
                } else {
                    showError('Hata: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showError('Sipariş gönderilirken hata oluştu');
            });
        }
    </script>
</body>
</html>
