// Base URL JavaScript için
// BASE_URL PHP'den JavaScript'e aktarılmalı

// Eğer BASE_URL tanımlı değilse, otomatik algıla
if (typeof window.BASE_URL === 'undefined') {
    const protocol = window.location.protocol + '//';
    const host = window.location.host;
    const pathname = window.location.pathname;
    // Son /'dan önceki kısmı al
    const path = pathname.substring(0, pathname.lastIndexOf('/') + 1);
    window.BASE_URL = protocol + host + path;
}

// Helper fonksiyonlar
function getBaseUrl() {
    return window.BASE_URL || '';
}

function url(path) {
    const base = getBaseUrl();
    // Path zaten / ile başlıyorsa, base'den sonraki /'yi kaldır
    if (path.startsWith('/')) {
        path = path.substring(1);
    }
    // Base'in sonunda / var mı kontrol et
    const baseUrl = base.endsWith('/') ? base : base + '/';
    return baseUrl + path;
}

function apiUrl(endpoint) {
    // Endpoint zaten api/ ile başlıyorsa, sadece url kullan
    if (endpoint.startsWith('api/')) {
        return url(endpoint);
    }
    return url('api/' + endpoint);
}

function assetUrl(path) {
    // Assets için
    if (path.startsWith('assets/')) {
        return url(path);
    }
    return url('assets/' + path);
}

// Global olarak kullanılabilir hale getir
window.getBaseUrl = getBaseUrl;
window.url = url;
window.apiUrl = apiUrl;
window.assetUrl = assetUrl;
