class NotificationManager {
    constructor() {
        this.soundEnabled = localStorage.getItem('notificationSound') !== 'disabled';
        this.lastCheck = new Date();
        this.notificationSound = new Audio('../assets/sounds/notification.mp3');
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.startPolling();
        this.startTimeUpdates();
    }

    setupEventListeners() {
        // Marcar notificação como lida
        document.addEventListener('click', (e) => {
            const markAsReadBtn = e.target.closest('.mark-as-read');
            if (markAsReadBtn) {
                e.preventDefault();
                const notifId = markAsReadBtn.dataset.id;
                this.markAsRead(notifId);
            }
        });

        // Toggle som das notificações
        const soundToggle = document.getElementById('notification-sound-toggle');
        if (soundToggle) {
            soundToggle.checked = this.soundEnabled;
            soundToggle.addEventListener('change', (e) => {
                this.soundEnabled = e.target.checked;
                localStorage.setItem('notificationSound', e.target.checked ? 'enabled' : 'disabled');
            });
        }
    }

    startPolling() {
        this.checkNewNotifications();
        setInterval(() => this.checkNewNotifications(), 30000); // Verificar a cada 30 segundos
    }

    startTimeUpdates() {
        // Atualizar os tempos a cada minuto
        setInterval(() => this.updateAllTimes(), 60000);
    }

    updateAllTimes() {
        const timeElements = document.querySelectorAll('.notification-item small');
        timeElements.forEach(element => {
            const timestamp = element.getAttribute('data-time');
            if (timestamp) {
                element.textContent = this.formatTimeAgo(timestamp);
            }
        });
    }

    formatTimeAgo(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return "Agora mesmo";
        
        const diffInMinutes = Math.floor(diffInSeconds / 60);
        if (diffInMinutes < 60) return `${diffInMinutes} minuto${diffInMinutes > 1 ? 's' : ''} atrás`;
        
        const diffInHours = Math.floor(diffInMinutes / 60);
        if (diffInHours < 24) return `${diffInHours} hora${diffInHours > 1 ? 's' : ''} atrás`;
        
        const diffInDays = Math.floor(diffInHours / 24);
        if (diffInDays < 7) return `${diffInDays} dia${diffInDays > 1 ? 's' : ''} atrás`;
        
        const diffInWeeks = Math.floor(diffInDays / 7);
        if (diffInWeeks < 4) return `${diffInWeeks} semana${diffInWeeks > 1 ? 's' : ''} atrás`;
        
        const diffInMonths = Math.floor(diffInDays / 30);
        if (diffInMonths < 12) return `${diffInMonths} mês${diffInMonths > 1 ? 'es' : ''} atrás`;
        
        const diffInYears = Math.floor(diffInDays / 365);
        return `${diffInYears} ano${diffInYears > 1 ? 's' : ''} atrás`;
    }

    async checkNewNotifications() {
        try {
            const response = await fetch('../ajax/check_notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    lastCheck: this.lastCheck.toISOString()
                })
            });

            const data = await response.json();
            
            if (data.newNotifications && data.newNotifications.length > 0) {
                this.updateNotificationBadge(data.totalUnread);
                this.updateNotificationDropdown(data.newNotifications);
                this.showToast(data.newNotifications[0]);
                
                if (this.soundEnabled) {
                    this.notificationSound.play();
                }
            }

            this.lastCheck = new Date();
        } catch (error) {
            console.error('Erro ao verificar notificações:', error);
        }
    }

    updateNotificationBadge(total) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = total;
            badge.style.display = total > 0 ? 'block' : 'none';
        }
    }

    updateNotificationDropdown(notifications) {
        const container = document.querySelector('.notifications-list');
        if (!container) return;

        notifications.forEach(notif => {
            const notifElement = this.createNotificationElement(notif);
            container.insertBefore(notifElement, container.firstChild);
        });
    }

    createNotificationElement(notif) {
        const div = document.createElement('div');
        div.className = 'notification-item new';
        div.innerHTML = `
            <div class="icon ${notif.tipo}">
                <i class="bi ${this.getIconClass(notif.tipo)}"></i>
            </div>
            <div class="content">
                <h4>${notif.titulo}</h4>
                <p>${notif.mensagem}</p>
                <small>Agora mesmo</small>
            </div>
            <div class="actions">
                <button class="mark-as-read" data-id="${notif.id}">
                    <i class="bi bi-check2"></i>
                </button>
            </div>
        `;
        return div;
    }

    getIconClass(tipo) {
        const icons = {
            'aprovacao': 'bi-check-circle',
            'rejeicao': 'bi-x-circle',
            'comentario': 'bi-chat-dots',
            'sistema': 'bi-gear'
        };
        return icons[tipo] || 'bi-bell';
    }

    showToast(notif) {
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.innerHTML = `
            <div class="toast-header">
                <i class="bi ${this.getIconClass(notif.tipo)} me-2"></i>
                <strong>${notif.titulo}</strong>
                <button type="button" class="btn-close"></button>
            </div>
            <div class="toast-body">
                ${notif.mensagem}
            </div>
        `;

        document.body.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }, 100);
    }

    async markAsRead(notifId) {
        try {
            const response = await fetch('../ajax/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: notifId })
            });

            const data = await response.json();
            if (data.success) {
                this.updateNotificationBadge(data.totalUnread);
                const notifElement = document.querySelector(`[data-id="${notifId}"]`).closest('.notification-item');
                notifElement.classList.add('read');
            }
        } catch (error) {
            console.error('Erro ao marcar notificação como lida:', error);
        }
    }
}

// Inicializar o gerenciador de notificações
document.addEventListener('DOMContentLoaded', () => {
    window.notificationManager = new NotificationManager();
}); 