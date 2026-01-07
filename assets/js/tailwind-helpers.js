// Tailwind CSS uyumlu helper fonksiyonlar

// Başarı bildirimi
function showSuccess(message) {
    showToast(message, 'success');
}

// Hata bildirimi
function showError(message) {
    showToast(message, 'error');
}

// Bilgi bildirimi
function showInfo(message) {
    showToast(message, 'info');
}

// Toast bildirimi (Tailwind CSS)
function showToast(message, type = 'info') {
    const colors = {
        success: {
            bg: 'bg-green-500',
            border: 'border-green-600',
            icon: 'fa-check-circle',
            text: 'text-white'
        },
        error: {
            bg: 'bg-red-500',
            border: 'border-red-600',
            icon: 'fa-exclamation-circle',
            text: 'text-white'
        },
        info: {
            bg: 'bg-blue-500',
            border: 'border-blue-600',
            icon: 'fa-info-circle',
            text: 'text-white'
        },
        warning: {
            bg: 'bg-yellow-500',
            border: 'border-yellow-600',
            icon: 'fa-exclamation-triangle',
            text: 'text-white'
        }
    };
    
    const color = colors[type] || colors.info;
    
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 ${color.bg} ${color.text} px-6 py-4 rounded-lg shadow-2xl border-l-4 ${color.border} z-50 flex items-center space-x-3 min-w-[300px] max-w-md transform transition-all duration-300 translate-x-full`;
    toast.innerHTML = `
        <i class="fas ${color.icon} text-xl"></i>
        <p class="flex-1 font-medium">${message}</p>
        <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(toast);
    
    // Animasyon
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 10);
    
    // Otomatik kapat
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Modal aç/kapat (Tailwind uyumlu)
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

// Modal dışına tıklanınca kapat
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-backdrop')) {
        const modal = e.target.closest('.modal-backdrop');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }
});


