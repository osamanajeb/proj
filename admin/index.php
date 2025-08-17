<?php
/**
 * لوحة تحكم المشرف - الصفحة الرئيسية
 * Admin dashboard - Main page
 */

$page_title = 'لوحة التحكم';
$page_description = 'لوحة تحكم المشرف';

require_once '../config/config.php';
require_once '../classes/Product.php';
require_once '../classes/Order.php';
require_once '../classes/User.php';

// التحقق من صلاحيات المشرف
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . '/login.php');
}

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائنات الفئات
$productObj = new Product($db);
$orderObj = new Order($db);
$userObj = new User($db);

// الحصول على الإحصائيات
$stats = [
    'total_products' => $productObj->getProductsCount(),
    'total_users' => count($userObj->getAllUsers()),
    'orders' => $orderObj->getOrderStats()
];

// الحصول على إحصائيات المبيعات المفصلة
$sales_stats = $orderObj->getSalesStats('month');

// الحصول على أحدث الطلبات
$recent_orders = $orderObj->getAllOrders(5);

// الحصول على المنتجات منخفضة المخزون
$low_stock_products = $productObj->getAllProducts();
$low_stock_products = array_filter($low_stock_products, function($product) {
    return $product['stock_quantity'] <= 5;
});

// تضمين رأس المشرف
include 'includes/admin_header.php';
?>

<div class="dashboard">
    <h1><i class="fas fa-tachometer-alt"></i> لوحة التحكم</h1>
    
    <!-- بطاقات الإحصائيات -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon bg-primary">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo number_format($stats['total_products']); ?></h3>
                    <p>إجمالي المنتجات</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon bg-success">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo number_format($stats['orders']['total_orders']); ?></h3>
                    <p>إجمالي الطلبات</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo number_format($stats['orders']['pending_orders']); ?></h3>
                    <p>الطلبات المعلقة</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon bg-info">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo number_format($stats['total_users']); ?></h3>
                    <p>إجمالي المستخدمين</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- أحدث الطلبات -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-shopping-bag"></i> أحدث الطلبات</h5>
                    <a href="orders.php" class="btn btn-sm btn-primary">عرض الكل</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد طلبات</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>رقم الطلب</th>
                                        <th>العميل</th>
                                        <th>الإجمالي</th>
                                        <th>الحالة</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>
                                                <a href="order-details.php?id=<?php echo $order['id']; ?>">
                                                    #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                            <td><?php echo format_price($order['total_amount']); ?></td>
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
                                            <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- المنتجات منخفضة المخزون -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-exclamation-triangle"></i> تنبيهات المخزون</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($low_stock_products)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">جميع المنتجات متوفرة</p>
                        </div>
                    <?php else: ?>
                        <div class="low-stock-list">
                            <?php foreach (array_slice($low_stock_products, 0, 5) as $product): ?>
                                <div class="low-stock-item">
                                    <div class="product-info">
                                        <h6><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <small class="text-muted">المتوفر: <?php echo $product['stock_quantity']; ?> قطعة</small>
                                    </div>
                                    <div class="stock-status">
                                        <?php if ($product['stock_quantity'] == 0): ?>
                                            <span class="badge bg-danger">نفد المخزون</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">مخزون منخفض</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($low_stock_products) > 5): ?>
                            <div class="text-center mt-3">
                                <a href="products.php?low_stock=1" class="btn btn-sm btn-outline-warning">
                                    عرض جميع المنتجات منخفضة المخزون
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- البيانات التجريبية -->
    <?php if ($total_products < 10): // إظهار القسم فقط إذا كان عدد المنتجات قليل ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-database"></i> البيانات التجريبية
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6>هل تريد ملء المتجر ببيانات تجريبية؟</h6>
                            <p class="text-muted mb-0">
                                احصل على 8 فئات و40 منتج مع صور ملونة لبدء تشغيل متجرك بسرعة
                            </p>
                            <small class="text-muted">
                                يشمل: الإلكترونيات، الملابس، المنزل والحديقة، الرياضة، الكتب، الجمال، الألعاب، السيارات
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="../database/install_sample_data.php" class="btn btn-info btn-lg">
                                <i class="fas fa-download"></i> تثبيت البيانات التجريبية
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- إحصائيات المبيعات -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> إحصائيات المبيعات</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="sales-stat">
                                <h3 class="text-success"><?php echo format_price($stats['orders']['total_sales']); ?></h3>
                                <p>إجمالي المبيعات</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="sales-stat">
                                <h3 class="text-info"><?php echo number_format($stats['orders']['total_sales'] / max($stats['orders']['total_orders'], 1), 2); ?> ر.س</h3>
                                <p>متوسط قيمة الطلب</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="sales-stat">
                                <h3 class="text-primary"><?php echo number_format(($stats['orders']['total_orders'] - $stats['orders']['pending_orders']) / max($stats['orders']['total_orders'], 1) * 100, 1); ?>%</h3>
                                <p>معدل إتمام الطلبات</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- إحصائيات المبيعات اليومية -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line"></i> المبيعات اليومية (آخر 7 أيام)
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($sales_stats['daily_sales'])): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>عدد الطلبات</th>
                                    <th>إجمالي المبيعات</th>
                                    <th>متوسط قيمة الطلب</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales_stats['daily_sales'] as $day): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($day['sale_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $day['orders_count']; ?></span>
                                        </td>
                                        <td>
                                            <strong class="text-success"><?php echo format_price($day['daily_revenue']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo format_price($day['daily_revenue'] / max($day['orders_count'], 1)); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <p class="text-muted">لا توجد مبيعات في الأيام السابقة</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.stats-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    transition: transform 0.3s;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 1rem;
    font-size: 1.5rem;
    color: white;
}

.stats-content h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: bold;
    color: #333;
}

.stats-content p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    border: none;
}

.low-stock-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.low-stock-item:last-child {
    border-bottom: none;
}

.sales-stat {
    padding: 1rem;
}

.sales-stat h3 {
    margin-bottom: 0.5rem;
}

.sales-stat p {
    margin: 0;
    color: #666;
}
</style>

<?php
// تضمين تذييل المشرف
include 'includes/admin_footer.php';
?>
