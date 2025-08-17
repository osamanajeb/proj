<?php
/**
 * صفحة الملف الشخصي
 * User profile page
 */

$page_title = 'الملف الشخصي';
$page_description = 'إدارة الملف الشخصي والطلبات';

require_once 'config/config.php';
require_once 'classes/User.php';
require_once 'classes/Order.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    redirect(SITE_URL . '/login.php');
}

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائنات الفئات
$userObj = new User($db);
$orderObj = new Order($db);

// الحصول على بيانات المستخدم
$user = $userObj->getUserById($_SESSION['user_id']);

// الحصول على طلبات المستخدم
$user_orders = $orderObj->getUserOrders($_SESSION['user_id'], 10);

$error_message = '';
$success_message = '';

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $phone = sanitize_input($_POST['phone']);
        $address = sanitize_input($_POST['address']);
        $city = sanitize_input($_POST['city']);
        
        if (empty($first_name) || empty($last_name)) {
            $error_message = 'الاسم الأول والأخير مطلوبان';
        } else {
            $user_data = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'phone' => $phone,
                'address' => $address,
                'city' => $city
            ];
            
            $result = $userObj->updateUser($_SESSION['user_id'], $user_data);
            
            if ($result) {
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $success_message = 'تم تحديث البيانات بنجاح';
                $user = $userObj->getUserById($_SESSION['user_id']); // إعادة تحميل البيانات
            } else {
                $error_message = 'حدث خطأ أثناء تحديث البيانات';
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'يرجى ملء جميع حقول كلمة المرور';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'كلمة المرور الجديدة وتأكيدها غير متطابقتين';
        } else {
            $result = $userObj->changePassword($_SESSION['user_id'], $old_password, $new_password);
            
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

// تضمين الرأس
include 'includes/header.php';
?>

<div class="profile-page">
    <div class="row">
        <!-- القائمة الجانبية -->
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="profile-sidebar">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="profile-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                
                <div class="profile-menu mt-3">
                    <div class="list-group">
                        <a href="#profile-info" class="list-group-item list-group-item-action active" data-bs-toggle="tab">
                            <i class="fas fa-user"></i> المعلومات الشخصية
                        </a>
                        <a href="#orders" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                            <i class="fas fa-shopping-bag"></i> طلباتي
                        </a>
                        <a href="#change-password" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                            <i class="fas fa-lock"></i> تغيير كلمة المرور
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- المحتوى الرئيسي -->
        <div class="col-lg-9 col-md-8">
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="tab-content">
                <!-- المعلومات الشخصية -->
                <div class="tab-pane fade show active" id="profile-info">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-user"></i> المعلومات الشخصية</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="first_name" class="form-label">الاسم الأول *</label>
                                            <input type="text" id="first_name" name="first_name" class="form-control" 
                                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="last_name" class="form-label">الاسم الأخير *</label>
                                            <input type="text" id="last_name" name="last_name" class="form-control" 
                                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" id="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                    <small class="form-text text-muted">لا يمكن تغيير البريد الإلكتروني</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone" class="form-label">رقم الهاتف</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?: ''); ?>">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="address" class="form-label">العنوان</label>
                                            <input type="text" id="address" name="address" class="form-control" 
                                                   value="<?php echo htmlspecialchars($user['address'] ?: ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="city" class="form-label">المدينة</label>
                                            <select id="city" name="city" class="form-select">
                                                <option value="">اختر المدينة</option>
                                                <option value="الرياض" <?php echo ($user['city'] == 'الرياض') ? 'selected' : ''; ?>>الرياض</option>
                                                <option value="جدة" <?php echo ($user['city'] == 'جدة') ? 'selected' : ''; ?>>جدة</option>
                                                <option value="الدمام" <?php echo ($user['city'] == 'الدمام') ? 'selected' : ''; ?>>الدمام</option>
                                                <option value="مكة المكرمة" <?php echo ($user['city'] == 'مكة المكرمة') ? 'selected' : ''; ?>>مكة المكرمة</option>
                                                <option value="المدينة المنورة" <?php echo ($user['city'] == 'المدينة المنورة') ? 'selected' : ''; ?>>المدينة المنورة</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ التغييرات
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- الطلبات -->
                <div class="tab-pane fade" id="orders">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-shopping-bag"></i> طلباتي</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($user_orders)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                    <h5>لا توجد طلبات</h5>
                                    <p class="text-muted">لم تقم بأي طلبات بعد</p>
                                    <a href="products.php" class="btn btn-primary">ابدأ التسوق</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>رقم الطلب</th>
                                                <th>التاريخ</th>
                                                <th>الحالة</th>
                                                <th>الإجمالي</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($user_orders as $order): ?>
                                                <tr>
                                                    <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                                                    <td>
                                                        <?php
                                                        $status_classes = [
                                                            'pending' => 'warning',
                                                            'confirmed' => 'info',
                                                            'shipped' => 'primary',
                                                            'delivered' => 'success',
                                                            'cancelled' => 'danger'
                                                        ];
                                                        $status_labels = [
                                                            'pending' => 'في الانتظار',
                                                            'confirmed' => 'مؤكد',
                                                            'shipped' => 'تم الشحن',
                                                            'delivered' => 'تم التسليم',
                                                            'cancelled' => 'ملغي'
                                                        ];
                                                        $class = $status_classes[$order['status']] ?? 'secondary';
                                                        $label = $status_labels[$order['status']] ?? $order['status'];
                                                        ?>
                                                        <span class="badge bg-<?php echo $class; ?>"><?php echo $label; ?></span>
                                                    </td>
                                                    <td><?php echo format_price($order['total_amount']); ?></td>
                                                    <td>
                                                        <a href="order-confirmation.php?order=<?php echo $order['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i> عرض
                                                        </a>
                                                        <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                                                            <button class="btn btn-sm btn-outline-danger"
                                                                    onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                                                <i class="fas fa-times"></i> إلغاء
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- تغيير كلمة المرور -->
                <div class="tab-pane fade" id="change-password">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-lock"></i> تغيير كلمة المرور</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="old_password" class="form-label">كلمة المرور الحالية *</label>
                                    <input type="password" id="old_password" name="old_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password" class="form-label">كلمة المرور الجديدة *</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" 
                                           minlength="6" required>
                                    <small class="form-text text-muted">6 أحرف على الأقل</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور الجديدة *</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                           minlength="6" required>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key"></i> تغيير كلمة المرور
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-avatar {
    font-size: 4rem;
    color: #667eea;
    margin-bottom: 1rem;
}

.profile-menu .list-group-item {
    border: none;
    border-radius: 10px;
    margin-bottom: 0.5rem;
}

.profile-menu .list-group-item.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
}

.profile-menu .list-group-item i {
    margin-left: 0.5rem;
    width: 20px;
}

.form-group {
    margin-bottom: 1.5rem;
}
</style>

<script>
// دالة عرض التنبيهات
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // إدراج التنبيه في أعلى الصفحة
    const container = document.querySelector('.profile-page');
    container.insertBefore(alertDiv, container.firstChild);

    // إزالة التنبيه تلقائياً بعد 5 ثوان
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function cancelOrder(orderId) {
    if (confirm('هل أنت متأكد من إلغاء هذا الطلب؟')) {
        fetch('ajax/cancel_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message || 'حدث خطأ أثناء إلغاء الطلب', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('حدث خطأ في الاتصال', 'error');
        });
    }
}

// التحقق من تطابق كلمة المرور
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('كلمة المرور غير متطابقة');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php
// تضمين التذييل
include 'includes/footer.php';
?>
