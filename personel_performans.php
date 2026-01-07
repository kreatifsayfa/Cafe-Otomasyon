<?php
require_once 'config/config.php';
checkLogin();
checkRole(['admin']);
$page_title = 'Personel Performans';
?>
<?php include 'includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar_tailwind.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <header class="bg-white shadow-sm border-b border-gray-200 z-40">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Personel Performans Raporları</h1>
                        <p class="text-sm text-gray-500 mt-1">Personel performansını analiz edin</p>
                    </div>
                    <div class="flex space-x-3">
                        <select id="personel-filtre" onchange="loadPerformans()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                            <option value="">Tüm Personel</option>
                        </select>
                        <input type="month" id="ay-filtre" value="<?php echo date('Y-m'); ?>" onchange="loadPerformans()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
            </header>
            
            <main class="flex-1 overflow-y-auto p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div id="performans-list" class="overflow-x-auto">
                        <!-- Performans listesi -->
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/base_url_script.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/tailwind-helpers.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadPersoneller();
            loadPerformans();
        });
        
        function loadPersoneller() {
            fetch(apiUrl('personel.php?action=listele'))
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('personel-filtre');
                    data.forEach(personel => {
                        const option = new Option(personel.ad_soyad, personel.id);
                        select.add(option);
                    });
                });
        }
        
        function loadPerformans() {
            const personelId = document.getElementById('personel-filtre').value;
            const ay = document.getElementById('ay-filtre').value;
            
            let url = apiUrl(`personel_performans.php?action=listele&ay=${ay}`);
            if (personelId) url += `&personel_id=${personelId}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('performans-list');
                    
                    if (data.length > 0) {
                        container.innerHTML = `
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Personel</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Toplam Sipariş</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Toplam Tutar</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ortalama Sipariş</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Çalışma Süresi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    ${data.map(perf => `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${perf.personel_adi}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">${formatDate(perf.tarih)}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">${perf.toplam_siparis}</td>
                                            <td class="px-4 py-3 text-sm font-semibold text-amber-600">${formatMoney(perf.toplam_tutar)}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${formatMoney(perf.ortalama_siparis_tutari)}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">${perf.calisma_suresi} dk</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        `;
                    } else {
                        container.innerHTML = '<p class="text-center text-gray-500 py-12">Performans verisi bulunamadı</p>';
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Performans verileri yüklenirken hata oluştu');
                });
        }
    </script>
</body>
</html>
