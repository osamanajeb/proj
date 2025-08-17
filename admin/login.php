<?php
/**
 * صفحة تسجيل دخول المشرف
 * Admin login page
 */

require_once '../config/config.php';

// إذا كان المشرف مسجل دخول بالفعل، توجيه إلى لوحة التحكم
if (is_admin_logged_in()) {
    redirect(SITE_URL . '/admin/index.php');
}

$error_message = '';

// معالجة نموذج تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    // التحقق من البيانات المطلوبة
    if (empty($username) || empty($password)) {
        $error_message = 'جميع الحقول مطلوبة';
    } else {
        // التحقق من بيانات المشرف (يمكنك تخصيص هذا حسب احتياجاتك)
        // هنا نستخدم بيانات ثابتة للمشرف، يمكنك تغييرها لاستخدام قاعدة البيانات
        $admin_username = 'admin';
        $admin_password = 'admin123'; // يجب تشفير كلمة المرور في التطبيق الحقيقي
        
        if ($username === $admin_username && $password === $admin_password) {
            // تسجيل دخول ناجح
            $_SESSION['admin_id'] = 1;
            $_SESSION['admin_username'] = $username;
            $_SESSION['message'] = ['text' => 'مرحباً بك في لوحة التحكم', 'type' => 'success'];
            redirect(SITE_URL . '/admin/index.php');
        } else {
            $error_message = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول المشرف - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-header h2 {
            margin: 0;
            font-weight: 600;
        }
        
        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .form-control.with-icon {
            border-left: none;
            border-radius: 10px 0 0 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2><i class="fas fa-shield-alt"></i> لوحة التحكم</h2>
            <p>تسجيل دخول المشرف</p>
        </div>
        
        <div class="login-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">اسم المستخدم</label>
                    <div class="input-group">
                        <input type="text" id="username" name="username" class="form-control with-icon" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               required>
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">كلمة المرور</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control with-icon" required>
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    للوصول إلى لوحة التحكم، استخدم بيانات المشرف
                </small>
            </div>
            
            <!-- معلومات تسجيل الدخول للاختبار -->
            <div class="mt-3 p-3 bg-light rounded">
                <small class="text-muted">
                    <strong>بيانات الاختبار:</strong><br>
                    اسم المستخدم: admin<br>
                    كلمة المرور: admin123
                </small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
