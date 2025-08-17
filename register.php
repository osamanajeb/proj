<?php
/**
 * صفحة التسجيل
 * Registration page
 */

$page_title = 'تسجيل جديد';
$page_description = 'إنشاء حساب جديد';

require_once 'config/config.php';
require_once 'classes/User.php';

// إعادة توجيه المستخدم المسجل دخوله
if (is_logged_in()) {
    redirect(SITE_URL . '/index.php');
}

$error_message = '';
$success_message = '';

// معالجة التسجيل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);
    $city = sanitize_input($_POST['city']);
    $terms = isset($_POST['terms']);
    
    // التحقق من البيانات
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error_message = 'يرجى ملء جميع الحقول المطلوبة';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'البريد الإلكتروني غير صحيح';
    } elseif (strlen($password) < 6) {
        $error_message = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } elseif ($password !== $confirm_password) {
        $error_message = 'كلمة المرور وتأكيد كلمة المرور غير متطابقتين';
    } elseif (!$terms) {
        $error_message = 'يجب الموافقة على الشروط والأحكام';
    } else {
        $db = getDBConnection();
        $userObj = new User($db);
        
        $user_data = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'password' => $password,
            'phone' => $phone,
            'address' => $address,
            'city' => $city
        ];
        
        $result = $userObj->register($user_data);
        
        if ($result['success']) {
            $success_message = $result['message'];
            // إعادة توجيه إلى صفحة تسجيل الدخول
            $_SESSION['message'] = ['text' => 'تم التسجيل بنجاح! يمكنك الآن تسجيل الدخول', 'type' => 'success'];
            redirect(SITE_URL . '/login.php');
        } else {
            $error_message = $result['message'];
        }
    }
}

// تضمين الرأس
include 'includes/header.php';
?>

<!-- إضافة ملف CSS الخاص بصفحات المصادقة -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/auth.css">
<script>document.body.classList.add('auth-body');</script>

<div class="auth-page">
    <div class="auth-container">
        <div class="auth-card register-card">
                <div class="auth-header">
                    <h2><i class="fas fa-user-plus"></i> تسجيل جديد</h2>
                    <p>انضم إلينا وابدأ تجربة تسوق رائعة</p>
                </div>
                
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
                
                <form method="POST" action="" class="auth-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name" class="form-label">
                                    <i class="fas fa-user"></i> الاسم الأول *
                                </label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name" class="form-label">
                                    <i class="fas fa-user"></i> الاسم الأخير *
                                </label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> البريد الإلكتروني *
                        </label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i> كلمة المرور *
                                </label>
                                <div class="password-input">
                                    <input type="password" id="password" name="password" class="form-control" 
                                           minlength="6" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">6 أحرف على الأقل</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock"></i> تأكيد كلمة المرور *
                                </label>
                                <div class="password-input">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                           minlength="6" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">
                            <i class="fas fa-phone"></i> رقم الهاتف
                        </label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                               placeholder="05xxxxxxxx">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="address" class="form-label">
                                    <i class="fas fa-map-marker-alt"></i> العنوان
                                </label>
                                <input type="text" id="address" name="address" class="form-control" 
                                       value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" 
                                       placeholder="الشارع، الحي">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="city" class="form-label">
                                    <i class="fas fa-city"></i> المدينة
                                </label>
                                <select id="city" name="city" class="form-select">
                                    <option value="">اختر المدينة</option>
                                    <option value="الرياض" <?php echo (isset($_POST['city']) && $_POST['city'] == 'الرياض') ? 'selected' : ''; ?>>الرياض</option>
                                    <option value="جدة" <?php echo (isset($_POST['city']) && $_POST['city'] == 'جدة') ? 'selected' : ''; ?>>جدة</option>
                                    <option value="الدمام" <?php echo (isset($_POST['city']) && $_POST['city'] == 'الدمام') ? 'selected' : ''; ?>>الدمام</option>
                                    <option value="مكة المكرمة" <?php echo (isset($_POST['city']) && $_POST['city'] == 'مكة المكرمة') ? 'selected' : ''; ?>>مكة المكرمة</option>
                                    <option value="المدينة المنورة" <?php echo (isset($_POST['city']) && $_POST['city'] == 'المدينة المنورة') ? 'selected' : ''; ?>>المدينة المنورة</option>
                                    <option value="الطائف" <?php echo (isset($_POST['city']) && $_POST['city'] == 'الطائف') ? 'selected' : ''; ?>>الطائف</option>
                                    <option value="تبوك" <?php echo (isset($_POST['city']) && $_POST['city'] == 'تبوك') ? 'selected' : ''; ?>>تبوك</option>
                                    <option value="بريدة" <?php echo (isset($_POST['city']) && $_POST['city'] == 'بريدة') ? 'selected' : ''; ?>>بريدة</option>
                                    <option value="خميس مشيط" <?php echo (isset($_POST['city']) && $_POST['city'] == 'خميس مشيط') ? 'selected' : ''; ?>>خميس مشيط</option>
                                    <option value="حائل" <?php echo (isset($_POST['city']) && $_POST['city'] == 'حائل') ? 'selected' : ''; ?>>حائل</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="terms" name="terms" class="form-check-input" required>
                            <label for="terms" class="form-check-label">
                                أوافق على <a href="terms.php" target="_blank">الشروط والأحكام</a> و <a href="privacy.php" target="_blank">سياسة الخصوصية</a>
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-user-plus"></i> إنشاء الحساب
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>لديك حساب بالفعل؟ <a href="login.php">سجل الدخول</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggle = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        toggle.classList.remove('fa-eye');
        toggle.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        toggle.classList.remove('fa-eye-slash');
        toggle.classList.add('fa-eye');
    }
}

// التحقق من تطابق كلمة المرور
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
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
