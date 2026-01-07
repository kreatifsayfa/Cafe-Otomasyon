// Klavye Kısayolları
document.addEventListener('DOMContentLoaded', function() {
    // Global kısayollar
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K kombinasyonları
        if ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey) {
            switch(e.key.toLowerCase()) {
                case 'k':
                    e.preventDefault();
                    // Hızlı arama modal'ı aç
                    if (typeof openQuickSearch === 'function') {
                        openQuickSearch();
                    }
                    break;
                case 'n':
                    e.preventDefault();
                    // Yeni kayıt modal'ı aç (sayfaya göre)
                    if (typeof yeniKayitModal === 'function') {
                        yeniKayitModal();
                    } else if (typeof yeniMasaModal === 'function') {
                        yeniMasaModal();
                    } else if (typeof yeniUrunModal === 'function') {
                        yeniUrunModal();
                    }
                    break;
                case 's':
                    e.preventDefault();
                    // Kaydet (form submit)
                    const activeForm = document.querySelector('form:not([style*="display: none"])');
                    if (activeForm && !activeForm.querySelector('input[type="submit"]:disabled')) {
                        activeForm.requestSubmit();
                    }
                    break;
            }
        }
        
        // Escape tuşu - Modal kapat
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
        
        // Sayfa bazlı kısayollar
        const page = window.location.pathname.split('/').pop();
        
        switch(page) {
            case 'index.php':
            case '':
                // Ana sayfa kısayolları
                if (e.key === '1') {
                    e.preventDefault();
                    window.location.href = 'masalar.php';
                } else if (e.key === '2') {
                    e.preventDefault();
                    window.location.href = 'siparis.php';
                } else if (e.key === '3') {
                    e.preventDefault();
                    window.location.href = 'hesap.php';
                } else if (e.key === '4') {
                    e.preventDefault();
                    window.location.href = 'raporlar.php';
                }
                break;
                
            case 'siparis.php':
                // Sipariş sayfası kısayolları
                if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'BUTTON') {
                    const urunAra = document.getElementById('urun-ara');
                    if (urunAra && document.activeElement === urunAra) {
                        e.preventDefault();
                        // İlk ürünü sepete ekle
                        const ilkUrun = document.querySelector('.urun-item');
                        if (ilkUrun) {
                            const btn = ilkUrun.querySelector('button');
                            if (btn) btn.click();
                        }
                    }
                }
                break;
                
            case 'hesap.php':
                // Hesap kesme kısayolları
                if (e.key === 'Enter' && e.ctrlKey) {
                    e.preventDefault();
                    const hesapKesBtn = document.querySelector('button[onclick="hesapKes()"]');
                    if (hesapKesBtn && !hesapKesBtn.disabled) {
                        hesapKes();
                    }
                }
                break;
        }
    });
    
    // Hızlı arama fonksiyonu
    window.openQuickSearch = function() {
        const searchInput = document.querySelector('input[type="text"][placeholder*="ara"], input[type="text"][id*="ara"]');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    };
    
    // Kısayol yardımı göster
    window.showKeyboardShortcuts = function() {
        const shortcuts = {
            'Ctrl/Cmd + K': 'Hızlı arama',
            'Ctrl/Cmd + N': 'Yeni kayıt',
            'Ctrl/Cmd + S': 'Kaydet',
            'Esc': 'Modal kapat',
            '1-4': 'Ana sayfa menü geçişi'
        };
        
        let html = '<div class="keyboard-shortcuts-help"><h3>Klavye Kısayolları</h3><ul>';
        for (const [key, desc] of Object.entries(shortcuts)) {
            html += `<li><kbd>${key}</kbd> - ${desc}</li>`;
        }
        html += '</ul></div>';
        
        showNotification(html, 'info', 5000);
    };
    
    // İlk yüklemede kısayol bilgisi göster (sadece bir kez)
    if (!sessionStorage.getItem('shortcuts_shown')) {
        setTimeout(() => {
            // Sessizce göster
            sessionStorage.setItem('shortcuts_shown', 'true');
        }, 3000);
    }
});

// Kısayol yardım butonu ekle
if (document.querySelector('.header-actions')) {
    const helpBtn = document.createElement('button');
    helpBtn.className = 'btn btn-icon';
    helpBtn.title = 'Klavye Kısayolları (Ctrl+K)';
    helpBtn.innerHTML = '<i class="fas fa-keyboard"></i>';
    helpBtn.onclick = showKeyboardShortcuts;
    helpBtn.style.marginLeft = '10px';
    
    // Header actions'a ekle
    const headerActions = document.querySelector('.header-actions');
    if (headerActions) {
        headerActions.appendChild(helpBtn);
    }
}


