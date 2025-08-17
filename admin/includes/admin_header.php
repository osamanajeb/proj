<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . get_site_name() : get_site_name() . ' - لوحة التحكم'; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'لوحة تحكم المشرف'; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/assets/css/admin.css">
</head>
<body class="admin-body">
    <!-- الشريط العلوي -->
    <nav class="admin-navbar">
        <div class="container-fluid">
            <div class="navbar-content">
                <div class="navbar-brand">
                    <button class="sidebar-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="<?php echo SITE_URL; ?>/admin/" class="brand-link">
                        <i class="fas fa-cog"></i> لوحة التحكم
                    </a>
                </div>
                
                <div class="navbar-actions">
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>"><i class="fas fa-home"></i> الموقع الرئيسي</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php"><i class="fas fa-user"></i> الملف الشخصي</a></li>
                            
                           
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="admin-layout">
        <!-- الشريط الجانبي -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-content">
                <ul class="sidebar-menu">
                    <li class="menu-item">
                        <a href="<?php echo SITE_URL; ?>/admin/" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>لوحة التحكم</span>
                        </a>
                    </li>
                    
                    <li class="menu-item">
                        <a href="<?php echo SITE_URL; ?>/admin/products.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                            <i class="fas fa-box"></i>
                            <span>المنتجات</span>
                        </a>
                    </li>
                    
                    <li class="menu-item">
                        <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tags"></i>
                            <span>الفئات</span>
                        </a>
                    </li>
                    
                    <li class="menu-item">
                        <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                            <i class="fas fa-shopping-cart"></i>
                            <span>الطلبات</span>
                        </a>
                    </li>

                    <li class="menu-item">
                        <a href="<?php echo SITE_URL; ?>/admin/create-order.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'create-order.php' ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i>
                            <span>إنشاء طلب</span>
                        </a>
                    </li>

                    <li class="menu-item">
                        <a href="<?php echo SITE_URL; ?>/admin/cart-analytics.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'cart-analytics.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line"></i>
                            <span>إحصائيات السلة</span>
                        </a>
                    </li>

                    <li class="menu-item">
                        <a href="<?php echo SITE_URL; ?>/admin/daily-sales.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'daily-sales.php' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-day"></i>
                            <span>المبيعات اليومية</span>
                        </a>
                    </li>

                    <li class="menu-item">
                        <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i>
                            <span>التقارير الشاملة</span>
                        </a>
                    </li>

                    <li class="menu-item">
                        <a href="<?php echo SITE_URL; ?>/admin/stock-report.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'stock-report.php' ? 'active' : ''; ?>">
                            <i class="fas fa-warehouse"></i>
                            <span>تقرير المخزون</span>
                        </a>
                    </li>

                   

                    <li class="menu-item">
                        <a href="<?php echo SITE_URL; ?>/admin/users.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>المستخدمين</span>
                        </a>
                    </li>
                    
                    <li class="menu-item">
                        <a href="<?php echo SITE_URL; ?>/admin/settings.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            <span>الإعدادات</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>
        
        <!-- المحتوى الرئيسي -->
        <main class="admin-main">
            <div class="container-fluid">
                <?php
                // عرض الرسائل من الجلسة
                if (isset($_SESSION['message'])) {
                    display_message($_SESSION['message']['text'], $_SESSION['message']['type']);
                    unset($_SESSION['message']);
                }
                ?>
