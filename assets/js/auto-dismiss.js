/**
 * نظام الإخفاء التلقائي للإشعارات
 * Auto-dismiss notifications system
 */

// دالة لإخفاء الإشعار تلقائياً
function autoDismissAlert(alertId, timeout = 5000) {
    setTimeout(function() {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
            // الحصول على نص الإشعار ونوعه لإزالته من القائمة النشطة
            const messageText = alertElement.textContent.trim().replace('×', '').trim();
            let alertType = 'info';
            if (alertElement.classList.contains('alert-success')) alertType = 'success';
            else if (alertElement.classList.contains('alert-danger')) alertType = 'danger';
            else if (alertElement.classList.contains('alert-warning')) alertType = 'warning';

            const messageKey = `${alertType}_${messageText}`;
            activeAlerts.delete(messageKey);

            // إضافة تأثير الاختفاء
            alertElement.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
            alertElement.style.opacity = '0';
            alertElement.style.transform = 'translateX(100%)';

            // إزالة العنصر بعد انتهاء التأثير
            setTimeout(function() {
                if (alertElement && alertElement.parentNode) {
                    alertElement.parentNode.removeChild(alertElement);
                }
            }, 500);
        }
    }, timeout);
}

// دالة لإزالة الإشعار فوراً
function removeAlert(alertId) {
    const alertElement = document.getElementById(alertId);
    if (alertElement) {
        // الحصول على نص الإشعار ونوعه لإزالته من القائمة النشطة
        const messageText = alertElement.textContent.trim().replace('×', '').trim();
        let alertType = 'info';
        if (alertElement.classList.contains('alert-success')) alertType = 'success';
        else if (alertElement.classList.contains('alert-danger')) alertType = 'danger';
        else if (alertElement.classList.contains('alert-warning')) alertType = 'warning';

        const messageKey = `${alertType}_${messageText}`;
        activeAlerts.delete(messageKey);

        alertElement.style.transition = 'opacity 0.3s ease-out';
        alertElement.style.opacity = '0';

        setTimeout(function() {
            if (alertElement && alertElement.parentNode) {
                alertElement.parentNode.removeChild(alertElement);
            }
        }, 300);
    }
}

// متغير لتتبع الإشعارات النشطة
let activeAlerts = new Set();

// دالة لإنشاء إشعار جديد بـ JavaScript
function createAlert(message, type = 'info', timeout = null) {
    // منع الإشعارات المكررة
    const messageKey = `${type}_${message}`;
    if (activeAlerts.has(messageKey)) {
        return null; // إشعار مماثل موجود بالفعل
    }

    // تحديد الألوان والأيقونات
    const alertTypes = {
        'success': {
            class: 'alert-success',
            icon: 'fas fa-check-circle',
            timeout: timeout || 3000
        },
        'error': {
            class: 'alert-danger',
            icon: 'fas fa-exclamation-circle',
            timeout: timeout || 5000
        },
        'danger': {
            class: 'alert-danger',
            icon: 'fas fa-exclamation-circle',
            timeout: timeout || 5000
        },
        'warning': {
            class: 'alert-warning',
            icon: 'fas fa-exclamation-triangle',
            timeout: timeout || 5000
        },
        'info': {
            class: 'alert-info',
            icon: 'fas fa-info-circle',
            timeout: timeout || 5000
        }
    };

    const alertConfig = alertTypes[type] || alertTypes['info'];
    const alertId = 'alert_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

    // إضافة الإشعار للقائمة النشطة
    activeAlerts.add(messageKey);
    
    // إنشاء عنصر الإشعار
    const alertElement = document.createElement('div');
    alertElement.id = alertId;
    alertElement.className = `alert ${alertConfig.class} alert-dismissible fade show auto-dismiss`;
    alertElement.innerHTML = `
        <i class="${alertConfig.icon}"></i> ${message}
        <button type="button" class="btn-close" onclick="removeAlert('${alertId}')">×</button>
    `;
    
    // البحث عن حاوي الإشعارات أو إنشاؤه
    let alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alert-container';
        alertContainer.style.position = 'fixed';
        alertContainer.style.top = '20px';
        alertContainer.style.right = '20px';
        alertContainer.style.zIndex = '99999';
        alertContainer.style.maxWidth = '400px';
        alertContainer.style.width = 'auto';
        alertContainer.style.minWidth = '300px';
        document.body.appendChild(alertContainer);
    }
    
    // إضافة الإشعار للحاوي
    alertContainer.appendChild(alertElement);
    
    // إخفاء تلقائي
    autoDismissAlert(alertId, alertConfig.timeout);
    
    return alertId;
}

// تشغيل النظام عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // البحث عن جميع الإشعارات الموجودة وتطبيق الإخفاء التلقائي عليها
    const existingAlerts = document.querySelectorAll('.alert.auto-dismiss');
    
    existingAlerts.forEach(function(alert) {
        if (alert.id) {
            // تحديد المدة حسب نوع الإشعار
            let timeout = 5000; // افتراضي
            
            if (alert.classList.contains('alert-success')) {
                timeout = 3000; // 3 ثوان للنجاح
            }
            
            // تطبيق الإخفاء التلقائي
            autoDismissAlert(alert.id, timeout);
        }
    });
});

// دوال مساعدة للاستخدام السريع
function showSuccessAlert(message, timeout = 3000) {
    return createAlert(message, 'success', timeout);
}

function showErrorAlert(message, timeout = 5000) {
    return createAlert(message, 'error', timeout);
}

function showWarningAlert(message, timeout = 5000) {
    return createAlert(message, 'warning', timeout);
}

function showInfoAlert(message, timeout = 5000) {
    return createAlert(message, 'info', timeout);
}

// تصدير الدوال للاستخدام العام
window.autoDismissAlert = autoDismissAlert;
window.removeAlert = removeAlert;
window.dismissAlert = removeAlert; // alias للتوافق مع script.js
window.createAlert = createAlert;
window.showSuccessAlert = showSuccessAlert;
window.showErrorAlert = showErrorAlert;
window.showWarningAlert = showWarningAlert;
window.showInfoAlert = showInfoAlert;
