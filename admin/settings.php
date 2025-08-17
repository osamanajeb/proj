<?php
/**
 * صفحة إعدادات المتجر
 * Store Settings Page
 */

$page_title = 'إعدادات المتجر';
$page_description = 'إدارة إعدادات المتجر الإلكتروني';

require_once __DIR__ . '/../config/config.php';

// التحقق من صلاحيات المشرف
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . '/admin/login.php');
}

// معالجة حفظ الإعدادات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success_message = '';
    $error_message = '';
    
    try {
        $db = getDBConnection();
        
        // إنشاء جدول الإعدادات إذا لم يكن موجوداً
        $create_table_query = "
            CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(255) UNIQUE NOT NULL,
                setting_value TEXT,
                setting_type ENUM('text', 'number', 'boolean', 'email', 'url') DEFAULT 'text',
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $db->exec($create_table_query);
        
        // قائمة الإعدادات المسموح بتحديثها
        $allowed_settings = [
            'site_name' => 'text',
            'site_description' => 'text',
            'admin_email' => 'email',
            'contact_phone' => 'text',
            'contact_address' => 'text',
            'shipping_cost' => 'number',
            'free_shipping_threshold' => 'number',
            'tax_rate' => 'number',
            'currency_symbol' => 'text',
            'items_per_page' => 'number',
            'maintenance_mode' => 'boolean',
            'allow_registration' => 'boolean',
            'require_email_verification' => 'boolean'
        ];
        
        foreach ($allowed_settings as $key => $type) {
            if (isset($_POST[$key])) {
                $value = $_POST[$key];
                
                // تنظيف القيمة حسب النوع
                switch ($type) {
                    case 'number':
                        $value = floatval($value);
                        break;
                    case 'boolean':
                        $value = isset($_POST[$key]) ? '1' : '0';
                        break;
                    case 'email':
                        $value = filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
                        break;
                    default:
                        $value = sanitize_input($value);
                }
                
                // حفظ أو تحديث الإعداد
                $stmt = $db->prepare("
                    INSERT INTO settings (setting_key, setting_value, setting_type) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$key, $value, $type]);
            }
        }

        // مسح كاش الإعدادات لضمان تحديث القيم
        clear_settings_cache();

        $success_message = 'تم حفظ الإعدادات بنجاح';
        
    } catch (Exception $e) {
        $error_message = 'حدث خطأ أثناء حفظ الإعدادات: ' . $e->getMessage();
        error_log("Settings save error: " . $e->getMessage());
    }
}

// جلب الإعدادات الحالية
function getSetting($key, $default = '') {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        error_log("Error getting setting {$key}: " . $e->getMessage());
        return $default;
    }
}

// الإعدادات الافتراضية
$settings = [
    'site_name' => getSetting('site_name', DEFAULT_SITE_NAME),
    'site_description' => getSetting('site_description', 'متجر إلكتروني عربي متكامل'),
    'admin_email' => getSetting('admin_email', DEFAULT_ADMIN_EMAIL),
    'contact_phone' => getSetting('contact_phone', '+966 50 123 4567'),
    'contact_address' => getSetting('contact_address', 'الرياض، المملكة العربية السعودية'),
    'shipping_cost' => getSetting('shipping_cost', '25'),
    'free_shipping_threshold' => getSetting('free_shipping_threshold', '200'),
    'tax_rate' => getSetting('tax_rate', '15'),
    'currency_symbol' => getSetting('currency_symbol', CURRENCY_SYMBOL),
    'items_per_page' => getSetting('items_per_page', '12'),
    'maintenance_mode' => getSetting('maintenance_mode', '0'),
    'allow_registration' => getSetting('allow_registration', '1'),
    'require_email_verification' => getSetting('require_email_verification', '0')
];

require_once 'includes/admin_header.php';
?>

<div class="container-fluid">
    <!-- عنوان الصفحة -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-cog"></i> إعدادات المتجر
        </h1>
        <div class="btn-group">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('settingsForm').submit();">
                <i class="fas fa-save"></i> حفظ الإعدادات
            </button>
            <button type="button" class="btn btn-success" onclick="exportSettings();">
                <i class="fas fa-download"></i> تصدير الإعدادات
            </button>
            <button type="button" class="btn btn-info" onclick="importSettings();">
                <i class="fas fa-upload"></i> استيراد الإعدادات
            </button>
            <button type="button" class="btn btn-secondary" onclick="location.reload();">
                <i class="fas fa-undo"></i> إعادة تحميل
            </button>
        </div>

        <!-- ملف الاستيراد المخفي -->
        <input type="file" id="importFile" accept=".json" style="display: none;" onchange="handleImportFile(this)">
    </div>

    <!-- عرض الرسائل -->
    <?php if (isset($success_message) && !empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message) && !empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form id="settingsForm" method="POST" action="">
        <div class="row">
            <!-- إعدادات عامة -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-globe"></i> الإعدادات العامة
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="site_name" class="form-label">اسم المتجر</label>
                            <input type="text" class="form-control" id="site_name" name="site_name" 
                                   value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="site_description" class="form-label">وصف المتجر</label>
                            <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="admin_email" class="form-label">البريد الإلكتروني للمشرف</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                   value="<?php echo htmlspecialchars($settings['admin_email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="contact_phone" class="form-label">رقم الهاتف</label>
                            <input type="text" class="form-control" id="contact_phone" name="contact_phone" 
                                   value="<?php echo htmlspecialchars($settings['contact_phone']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="contact_address" class="form-label">العنوان</label>
                            <textarea class="form-control" id="contact_address" name="contact_address" rows="2"><?php echo htmlspecialchars($settings['contact_address']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إعدادات الشحن والدفع -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-success">
                            <i class="fas fa-truck"></i> إعدادات الشحن والدفع
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="shipping_cost" class="form-label">تكلفة الشحن</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="shipping_cost" name="shipping_cost" 
                                       value="<?php echo $settings['shipping_cost']; ?>" step="0.01" min="0">
                                <span class="input-group-text"><?php echo $settings['currency_symbol']; ?></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="free_shipping_threshold" class="form-label">حد الشحن المجاني</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="free_shipping_threshold" name="free_shipping_threshold" 
                                       value="<?php echo $settings['free_shipping_threshold']; ?>" step="0.01" min="0">
                                <span class="input-group-text"><?php echo $settings['currency_symbol']; ?></span>
                            </div>
                            <div class="form-text">الطلبات التي تزيد عن هذا المبلغ تحصل على شحن مجاني</div>
                        </div>

                        <div class="mb-3">
                            <label for="tax_rate" class="form-label">معدل الضريبة</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                       value="<?php echo $settings['tax_rate']; ?>" step="0.01" min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="currency_symbol" class="form-label">رمز العملة</label>
                            <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" 
                                   value="<?php echo htmlspecialchars($settings['currency_symbol']); ?>" maxlength="10">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- إعدادات العرض -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-info">
                            <i class="fas fa-eye"></i> إعدادات العرض
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="items_per_page" class="form-label">عدد المنتجات في الصفحة</label>
                            <select class="form-select" id="items_per_page" name="items_per_page">
                                <option value="8" <?php echo $settings['items_per_page'] == '8' ? 'selected' : ''; ?>>8 منتجات</option>
                                <option value="12" <?php echo $settings['items_per_page'] == '12' ? 'selected' : ''; ?>>12 منتج</option>
                                <option value="16" <?php echo $settings['items_per_page'] == '16' ? 'selected' : ''; ?>>16 منتج</option>
                                <option value="20" <?php echo $settings['items_per_page'] == '20' ? 'selected' : ''; ?>>20 منتج</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إعدادات النظام -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-warning">
                            <i class="fas fa-cogs"></i> إعدادات النظام
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                       <?php echo $settings['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenance_mode">
                                    وضع الصيانة
                                </label>
                            </div>
                            <div class="form-text">عند التفعيل، سيتم إخفاء المتجر عن الزوار</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="allow_registration" name="allow_registration" 
                                       <?php echo $settings['allow_registration'] == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="allow_registration">
                                    السماح بالتسجيل
                                </label>
                            </div>
                            <div class="form-text">السماح للمستخدمين الجدد بإنشاء حسابات</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="require_email_verification" name="require_email_verification" 
                                       <?php echo $settings['require_email_verification'] == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="require_email_verification">
                                    تأكيد البريد الإلكتروني
                                </label>
                            </div>
                            <div class="form-text">طلب تأكيد البريد الإلكتروني عند التسجيل</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- معلومات النظام -->
        <div class="row">
            <div class="col-lg-12 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-info">
                            <i class="fas fa-info-circle"></i> معلومات النظام
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">إصدار PHP</h6>
                                        <h5 class="text-primary"><?php echo PHP_VERSION; ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">خادم الويب</h6>
                                        <h6 class="text-info"><?php echo substr($_SERVER['SERVER_SOFTWARE'] ?? 'غير محدد', 0, 20); ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">حجم الذاكرة</h6>
                                        <h5 class="text-success"><?php echo ini_get('memory_limit'); ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">حد رفع الملفات</h6>
                                        <h5 class="text-warning"><?php echo ini_get('upload_max_filesize'); ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6><i class="fas fa-database"></i> معلومات قاعدة البيانات</h6>
                                <ul class="list-unstyled">
                                    <li><strong>الخادم:</strong> <?php echo DB_HOST; ?></li>
                                    <li><strong>اسم القاعدة:</strong> <?php echo DB_NAME; ?></li>
                                    <li><strong>الترميز:</strong> <?php echo DB_CHARSET; ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-clock"></i> معلومات الوقت</h6>
                                <ul class="list-unstyled">
                                    <li><strong>المنطقة الزمنية:</strong> <?php echo date_default_timezone_get(); ?></li>
                                    <li><strong>الوقت الحالي:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
                                    <li><strong>وقت الخادم:</strong> <?php echo date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- أزرار الحفظ -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-save"></i> حفظ جميع الإعدادات
                        </button>
                        <button type="reset" class="btn btn-secondary btn-lg">
                            <i class="fas fa-undo"></i> إعادة تعيين
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}

.form-control:focus, .form-select:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.btn-primary {
    background-color: #4e73df;
    border-color: #4e73df;
}

.btn-primary:hover {
    background-color: #2e59d9;
    border-color: #2653d4;
}

.form-check-input:checked {
    background-color: #4e73df;
    border-color: #4e73df;
}

.alert {
    border: none;
    border-radius: 0.35rem;
}

.text-gray-800 {
    color: #5a5c69 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تأكيد قبل الحفظ
    document.getElementById('settingsForm').addEventListener('submit', function(e) {
        const maintenanceMode = document.getElementById('maintenance_mode').checked;

        if (maintenanceMode) {
            if (!confirm('تحذير: سيتم تفعيل وضع الصيانة وإخفاء المتجر عن الزوار. هل أنت متأكد؟')) {
                e.preventDefault();
                return false;
            }
        }

        // إظهار مؤشر التحميل
        const submitBtn = document.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...';
        submitBtn.disabled = true;

        // إعادة تفعيل الزر بعد ثانيتين في حالة عدم إعادة تحميل الصفحة
        setTimeout(function() {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 2000);
    });

    // حفظ تلقائي للإعدادات المهمة
    const importantSettings = ['maintenance_mode', 'allow_registration', 'require_email_verification'];
    importantSettings.forEach(function(settingId) {
        const element = document.getElementById(settingId);
        if (element) {
            element.addEventListener('change', function() {
                saveSettingAjax(settingId, this.checked ? '1' : '0', 'boolean');
            });
        }
    });

    // حفظ تلقائي للإعدادات النصية عند فقدان التركيز
    const textSettings = ['site_name', 'admin_email', 'contact_phone'];
    textSettings.forEach(function(settingId) {
        const element = document.getElementById(settingId);
        if (element) {
            element.addEventListener('blur', function() {
                if (this.value.trim() !== '') {
                    const type = this.type === 'email' ? 'email' : 'text';
                    saveSettingAjax(settingId, this.value, type);
                }
            });
        }
    });
});

// دالة حفظ الإعداد عبر AJAX
function saveSettingAjax(settingKey, settingValue, settingType) {
    const formData = new FormData();
    formData.append('setting_key', settingKey);
    formData.append('setting_value', settingValue);
    formData.append('setting_type', settingType);

    fetch('ajax/save_setting.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('تم حفظ الإعداد بنجاح', 'success');
        } else {
            showNotification('خطأ: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ في الاتصال', 'error');
    });
}

// دالة إظهار الإشعارات
function showNotification(message, type) {
    // إنشاء عنصر الإشعار
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // إضافة الإشعار للصفحة
    document.body.appendChild(notification);

    // إزالة الإشعار تلقائياً بعد 3 ثوان
    setTimeout(function() {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// دالة تصدير الإعدادات
function exportSettings() {
    window.open('export_settings.php', '_blank');
}

// دالة استيراد الإعدادات
function importSettings() {
    document.getElementById('importFile').click();
}

// معالج رفع ملف الاستيراد
function handleImportFile(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.type !== 'application/json') {
            alert('يرجى اختيار ملف JSON صحيح');
            return;
        }

        const formData = new FormData();
        formData.append('import_file', file);

        fetch('import_settings.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('تم استيراد الإعدادات بنجاح', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('خطأ: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('حدث خطأ في الاستيراد', 'error');
        });
    }
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
