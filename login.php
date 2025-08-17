<?php
/**
 * صفحة تسجيل الدخول
 * Login page
 */

$page_title = 'تسجيل الدخول';
$page_description = 'تسجيل الدخول إلى حسابك';

require_once 'config/config.php';
require_once 'classes/User.php';
require_once 'classes/Cart.php';

// إعادة توجيه المستخدم المسجل دخوله
if (is_logged_in()) {
    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
    redirect(SITE_URL . '/' . $redirect);
}

$error_message = '';
$success_message = '';

// معالجة تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($email) || empty($password)) {
        $error_message = 'يرجى إدخال البريد الإلكتروني وكلمة المرور';
    } else {
        $db = getDBConnection();
        $userObj = new User($db);
        
        $result = $userObj->login($email, $password);
        
        if ($result['success']) {
            // نقل السلة من الجلسة إلى المستخدم
            $cartObj = new Cart($db);
            $cartObj->transferCartToUser($_SESSION['user_id']);
            
            // تذكرني
            if ($remember_me) {
                setcookie('remember_token', session_id(), time() + (30 * 24 * 60 * 60), '/'); // 30 يوم
            }
            
            $success_message = $result['message'];
            
            // إعادة التوجيه
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
            $_SESSION['message'] = ['text' => $success_message, 'type' => 'success'];
            redirect(SITE_URL . '/' . $redirect);
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
        <div class="auth-card">
                <div class="auth-header">
                    <h2><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</h2>
                    <p>مرحباً بعودتك! يرجى تسجيل الدخول إلى حسابك</p>
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
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> البريد الإلكتروني
                        </label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> كلمة المرور
                        </label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" class="form-control" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <div class="form-check">
                            <input type="checkbox" id="remember_me" name="remember_me" class="form-check-input">
                            <label for="remember_me" class="form-check-label">تذكرني</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-password">نسيت كلمة المرور؟</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>ليس لديك حساب؟ <a href="register.php">سجل الآن</a></p>
                </div>
                
                <!-- تسجيل دخول تجريبي -->
                
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

function fillDemoData(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
}
</script>

<?php
// تضمين التذييل
include 'includes/footer.php';
?>
