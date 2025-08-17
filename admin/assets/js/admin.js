/**
 * ملف JavaScript للوحة تحكم المشرف
 * JavaScript file for Admin Dashboard
 */

// تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
    initializeDataTables();
    initializeImageUpload();
    initializeConfirmDialogs();
});

/**
 * تهيئة الشريط الجانبي
 * Initialize sidebar
 */
function initializeSidebar() {
    // التحكم في إظهار/إخفاء الشريط الجانبي على الشاشات الصغيرة
    if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('adminSidebar');
        if (sidebar) {
            sidebar.classList.add('collapsed');
        }
    }
}

/**
 * تبديل حالة الشريط الجانبي
 * Toggle sidebar
 */
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const main = document.querySelector('.admin-main');
    
    if (sidebar) {
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('show');
        } else {
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('expanded');
        }
    }
}

/**
 * تهيئة جداول البيانات
 * Initialize data tables
 */
function initializeDataTables() {
    // إضافة وظائف البحث والترتيب للجداول
    const tables = document.querySelectorAll('.data-table');
    tables.forEach(table => {
        addTableSearch(table);
        addTableSort(table);
    });
}

/**
 * إضافة البحث للجدول
 * Add table search
 */
function addTableSearch(table) {
    const searchInput = table.parentElement.querySelector('.table-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
}

/**
 * إضافة الترتيب للجدول
 * Add table sort
 */
function addTableSort(table) {
    const headers = table.querySelectorAll('th[data-sort]');
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const column = this.dataset.sort;
            const direction = this.dataset.direction === 'asc' ? 'desc' : 'asc';
            this.dataset.direction = direction;
            
            sortTable(table, column, direction);
        });
    });
}

/**
 * ترتيب الجدول
 * Sort table
 */
function sortTable(table, column, direction) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.querySelector(`[data-value="${column}"]`)?.textContent || '';
        const bValue = b.querySelector(`[data-value="${column}"]`)?.textContent || '';
        
        if (direction === 'asc') {
            return aValue.localeCompare(bValue, 'ar');
        } else {
            return bValue.localeCompare(aValue, 'ar');
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

/**
 * تهيئة رفع الصور
 * Initialize image upload
 */
function initializeImageUpload() {
    const uploadAreas = document.querySelectorAll('.image-upload-area');
    uploadAreas.forEach(area => {
        const input = area.querySelector('input[type="file"]');
        
        // النقر لاختيار الملف
        area.addEventListener('click', () => {
            if (input) input.click();
        });
        
        // السحب والإفلات
        area.addEventListener('dragover', (e) => {
            e.preventDefault();
            area.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', () => {
            area.classList.remove('dragover');
        });
        
        area.addEventListener('drop', (e) => {
            e.preventDefault();
            area.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0 && input) {
                input.files = files;
                handleImagePreview(input);
            }
        });
        
        // معاينة الصورة عند الاختيار
        if (input) {
            input.addEventListener('change', () => {
                handleImagePreview(input);
            });
        }
    });
}

/**
 * معالجة معاينة الصورة
 * Handle image preview
 */
function handleImagePreview(input) {
    const file = input.files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = input.parentElement.querySelector('.image-preview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            } else {
                // إنشاء معاينة جديدة
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'image-preview';
                img.style.maxWidth = '200px';
                img.style.maxHeight = '200px';
                img.style.objectFit = 'cover';
                img.style.borderRadius = '8px';
                img.style.marginTop = '1rem';
                input.parentElement.appendChild(img);
            }
        };
        reader.readAsDataURL(file);
    }
}

/**
 * تهيئة مربعات الحوار التأكيدية
 * Initialize confirm dialogs
 */
function initializeConfirmDialogs() {
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'هل أنت متأكد؟';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * حذف عنصر عبر AJAX
 * Delete item via AJAX
 */
function deleteItem(url, itemId, confirmMessage = 'هل أنت متأكد من الحذف؟') {
    if (confirm(confirmMessage)) {
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${itemId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message || 'تم الحذف بنجاح', 'success');
                // إزالة الصف من الجدول
                const row = document.querySelector(`tr[data-id="${itemId}"]`);
                if (row) {
                    row.remove();
                }
            } else {
                showAlert(data.message || 'حدث خطأ أثناء الحذف', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('حدث خطأ في الاتصال', 'error');
        });
    }
}

/**
 * تحديث حالة العنصر
 * Update item status
 */
function updateStatus(url, itemId, status) {
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${itemId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message || 'تم تحديث الحالة بنجاح', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'حدث خطأ أثناء التحديث', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('حدث خطأ في الاتصال', 'error');
    });
}

/**
 * تصدير البيانات
 * Export data
 */
function exportData(format, url) {
    const exportUrl = `${url}?export=${format}`;
    window.open(exportUrl, '_blank');
}

/**
 * طباعة التقرير
 * Print report
 */
function printReport() {
    window.print();
}

/**
 * تحديث الإحصائيات
 * Update statistics
 */
function updateStats() {
    fetch('ajax/get_stats.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // تحديث بطاقات الإحصائيات
            Object.keys(data.stats).forEach(key => {
                const element = document.querySelector(`[data-stat="${key}"]`);
                if (element) {
                    element.textContent = data.stats[key];
                }
            });
        }
    })
    .catch(error => {
        console.error('Error updating stats:', error);
    });
}

/**
 * تحديث الإشعارات
 * Update notifications
 */
function updateNotifications() {
    fetch('ajax/get_notifications.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notificationBadge = document.querySelector('.notification-badge');
            if (notificationBadge) {
                notificationBadge.textContent = data.count;
                notificationBadge.style.display = data.count > 0 ? 'inline' : 'none';
            }
        }
    })
    .catch(error => {
        console.error('Error updating notifications:', error);
    });
}

/**
 * تحديث الصفحة تلقائياً
 * Auto refresh page
 */
function startAutoRefresh(interval = 300000) { // 5 دقائق
    setInterval(() => {
        updateStats();
        updateNotifications();
    }, interval);
}

/**
 * تهيئة المحرر النصي
 * Initialize text editor
 */
function initializeEditor(selector) {
    // يمكن إضافة محرر نصي مثل TinyMCE أو CKEditor هنا
    const textareas = document.querySelectorAll(selector);
    textareas.forEach(textarea => {
        // إضافة وظائف التحرير المتقدمة
        textarea.style.minHeight = '200px';
    });
}

/**
 * التحقق من صحة النموذج
 * Validate form
 */
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

/**
 * حفظ النموذج عبر AJAX
 * Save form via AJAX
 */
function saveForm(form, url) {
    if (!validateForm(form)) {
        showAlert('يرجى ملء جميع الحقول المطلوبة', 'warning');
        return;
    }
    
    const formData = new FormData(form);
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message || 'تم الحفظ بنجاح', 'success');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            }
        } else {
            showAlert(data.message || 'حدث خطأ أثناء الحفظ', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('حدث خطأ في الاتصال', 'error');
    });
}

// تشغيل التحديث التلقائي عند تحميل الصفحة
if (document.querySelector('.dashboard')) {
    startAutoRefresh();
}

// إغلاق الشريط الجانبي عند النقر خارجه على الشاشات الصغيرة
document.addEventListener('click', function(e) {
    if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('adminSidebar');
        const toggle = document.querySelector('.sidebar-toggle');
        
        if (sidebar && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
            sidebar.classList.remove('show');
        }
    }
});

// تحديث التخطيط عند تغيير حجم النافذة
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('adminSidebar');
    const main = document.querySelector('.admin-main');
    
    if (window.innerWidth > 768) {
        if (sidebar) sidebar.classList.remove('show');
        if (main) main.classList.remove('expanded');
    }
});
