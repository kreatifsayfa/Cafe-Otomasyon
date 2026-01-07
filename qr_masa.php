<?php
require_once 'config/config.php';
checkLogin();
checkRole(['admin']);
$page_title = 'QR Kod Masa Sistemi';
?>
<?php include 'includes/head.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar_tailwind.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <header class="bg-white shadow-sm border-b border-gray-200 z-40">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">QR Kod Masa Sistemi</h1>
                        <p class="text-sm text-gray-500 mt-1">Masalar için QR kodlar oluşturun ve yazdırın</p>
                    </div>
                    <button onclick="tumQRKodlariOlustur()" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold px-6 py-2.5 rounded-lg shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-qrcode mr-2"></i>Tüm QR Kodları Oluştur
                    </button>
                </div>
            </header>
            
            <main class="flex-1 overflow-y-auto p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded-lg">
                        <p class="text-sm text-blue-700">
                            <i class="fas fa-info-circle mr-2"></i>
                            QR kodlar müşterilerin masalarından direkt sipariş verebilmesi için kullanılır.
                        </p>
                    </div>
                    <div id="qr-kodlar-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <!-- QR kodlar buraya yüklenecek -->
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/base_url_script.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/tailwind-helpers.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadMasalar();
        });
        
        function loadMasalar() {
            fetch(apiUrl('masalar.php'))
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('qr-kodlar-container');
                    container.innerHTML = data.map(masa => {
                        const qrUrl = url('musteri_siparis.php?masa_id=' + masa.id);
                        const qrId = `qr-${masa.id}`;
                        
                        return `
                            <div class="bg-white border-2 border-gray-200 rounded-xl p-5 hover:border-amber-500 hover:shadow-lg transition-all">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Masa ${masa.masa_no}</h3>
                                    <button onclick="qrKodYazdir(${masa.id}, '${masa.masa_no}')" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-3 py-1.5 rounded-lg text-sm transition-colors">
                                        <i class="fas fa-print mr-1"></i>Yazdır
                                    </button>
                                </div>
                                <div id="${qrId}" class="flex justify-center items-center p-4 bg-white rounded-lg border border-gray-200 mb-3"></div>
                                <p class="text-xs text-gray-500 text-center">
                                    Müşteri bu QR kodu okutarak sipariş verebilir
                                </p>
                            </div>
                        `;
                    }).join('');
                    
                    data.forEach(masa => {
                        const qrUrl = url('musteri_siparis.php?masa_id=' + masa.id);
                        new QRCode(document.getElementById(`qr-${masa.id}`), {
                            text: qrUrl,
                            width: 200,
                            height: 200,
                            colorDark: '#000000',
                            colorLight: '#ffffff',
                            correctLevel: QRCode.CorrectLevel.H
                        });
                    });
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showError('Masalar yüklenirken hata oluştu');
                });
        }
        
        function tumQRKodlariOlustur() {
            showSuccess('QR kodlar oluşturuldu!');
        }
        
        function qrKodYazdir(masaId, masaNo) {
            const qrElement = document.getElementById(`qr-${masaId}`);
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>QR Kod - Masa ${masaNo}</title>
                        <style>
                            body { 
                                font-family: Arial, sans-serif; 
                                padding: 40px; 
                                text-align: center;
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                justify-content: center;
                                min-height: 100vh;
                            }
                            .qr-title {
                                font-size: 24px;
                                font-weight: bold;
                                margin-bottom: 20px;
                            }
                            .qr-instruction {
                                margin-top: 20px;
                                font-size: 14px;
                                color: #666;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="qr-title">Masa ${masaNo}</div>
                        ${qrElement.innerHTML}
                        <div class="qr-instruction">
                            QR kodu okutarak sipariş verebilirsiniz
                        </div>
                    </body>
                </html>
            `);
            printWindow.document.close();
            setTimeout(() => {
                printWindow.print();
            }, 250);
        }
    </script>
</body>
</html>
