<?php
/**
 * ملف الرأس المشترك
 * Common header file
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/Cart.php';

// الحصول على الفئات للقائمة
$db = getDBConnection();
$categoryObj = new Category($db);
$categories = $categoryObj->getAllCategories();

// الحصول على عدد المنتجات في السلة
$cartObj = new Cart($db);
$cart_count = $cartObj->getCartCount();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . get_site_name() : get_site_name(); ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : get_site_name() . ' - متجر إلكتروني متكامل'; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/auto-dismiss.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</head>
<body>
    <!-- الشريط العلوي -->
    <header class="header">
        <div class="container">
            <div class="header-top">
                <a href="<?php echo SITE_URL; ?>" class="logo">
                    <i class="fas fa-store"></i> <?php echo get_site_name(); ?>
                </a>
                
                <div class="user-actions">
                    <?php if (is_logged_in()): ?>
                        <span>مرحباً، <?php echo $_SESSION['user_name']; ?></span>
                        <a href="<?php echo SITE_URL; ?>/profile.php">
                            <i class="fas fa-user"></i> حسابي
                        </a>
                        <?php if (is_admin()): ?>
                            <a href="<?php echo SITE_URL; ?>/admin/">
                                <i class="fas fa-cog"></i> لوحة التحكم
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo SITE_URL; ?>/logout.php">
                            <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/login.php">
                            <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                        </a>
                        <a href="<?php echo SITE_URL; ?>/register.php">
                            <i class="fas fa-user-plus"></i> تسجيل جديد
                        </a>
                    <?php endif; ?>

                    <?php if (!is_admin()): ?>
                        <a href="<?php echo SITE_URL; ?>/cart.php" class="cart-icon">
                            <i class="fas fa-shopping-cart"></i> السلة
                            <span class="cart-count" style="<?php echo $cart_count > 0 ? '' : 'display: none;'; ?>">
                                <?php echo $cart_count; ?>
                            </span>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/admin/create-order.php" class="cart-icon">
                            <i class="fas fa-plus-circle"></i> إنشاء طلب
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- شريط التنقل -->
            <nav class="navbar">
                <ul class="nav-menu">
                    <li><a href="<?php echo SITE_URL; ?>"><i class="fas fa-home"></i> الرئيسية</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/products.php"><i class="fas fa-box"></i> جميع المنتجات</a></li>
                    
                    <!-- الفئات -->
                    <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                        <li>
                            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['id']; ?>">
                                <?php echo $category['name']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    
                    <li><a href="<?php echo SITE_URL; ?>/contact.php"><i class="fas fa-envelope"></i> اتصل بنا</a></li>
                </ul>
            </nav>
            
            <!-- شريط البحث -->
            <div class="search-bar">
                <form class="search-form" action="<?php echo SITE_URL; ?>/search.php" method="GET">
                    <input type="text" name="q" class="search-input" placeholder="ابحث عن المنتجات..." 
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> بحث
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <main class="main-content">
        <div class="container">
            <!-- حاوي التنبيهات -->
            <div id="alert-container">
                <?php
                // عرض الرسائل من الجلسة
                if (isset($_SESSION['message'])) {
                    display_message($_SESSION['message']['text'], $_SESSION['message']['type']);
                    unset($_SESSION['message']);
                }
                ?>
            </div>
