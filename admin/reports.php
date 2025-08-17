<?php
/**
 * صفحة التقارير الشاملة
 * Comprehensive Reports Page
 */

$page_title = 'التقارير';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/User.php';

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائنات الفئات
$orderObj = new Order($db);
$productObj = new Product($db);
$userObj = new User($db);

// الحصول على التواريخ من المعاملات
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // بداية الشهر الحالي
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // اليوم الحالي
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';

// دالة للحصول على إحصائيات المبيعات
function getSalesStats($start_date, $end_date) {
    $db = getDBConnection();
    
    // إجمالي المبيعات
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ? 
        AND status != 'cancelled'
    ");
    $stmt->execute([$start_date, $end_date]);
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // المبيعات حسب الحالة
    $stmt = $db->prepare("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY status
    ");
    $stmt->execute([$start_date, $end_date]);
    $sales_by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'summary' => $sales,
        'by_status' => $sales_by_status
    ];
}

// دالة للحصول على أفضل المنتجات مبيعاً
function getTopProducts($start_date, $end_date, $limit = 10) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT
            p.id,
            p.name,
            p.price,
            p.discount_price,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as total_revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        AND o.status != 'cancelled'
        GROUP BY p.id, p.name, p.price, p.discount_price
        ORDER BY total_sold DESC
        LIMIT ?
    ");
    $stmt->bindParam(1, $start_date, PDO::PARAM_STR);
    $stmt->bindParam(2, $end_date, PDO::PARAM_STR);
    $stmt->bindParam(3, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// دالة للحصول على إحصائيات العملاء
function getCustomerStats($start_date, $end_date) {
    $db = getDBConnection();
    
    // إجمالي العملاء الجدد
    $stmt = $db->prepare("
        SELECT COUNT(*) as new_customers
        FROM users 
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND role = 'customer'
    ");
    $stmt->execute([$start_date, $end_date]);
    $new_customers = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // أفضل العملاء
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            COUNT(o.id) as total_orders,
            SUM(o.total_amount) as total_spent
        FROM users u
        JOIN orders o ON u.id = o.user_id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        AND o.status != 'cancelled'
        GROUP BY u.id, u.first_name, u.last_name, u.email
        ORDER BY total_spent DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $top_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'new_customers' => $new_customers['new_customers'],
        'top_customers' => $top_customers
    ];
}

// دالة للحصول على إحصائيات المخزون
function getInventoryStats() {
    $db = getDBConnection();
    
    // المنتجات منخفضة المخزون
    $stmt = $db->prepare("
        SELECT id, name, stock_quantity, price
        FROM products 
        WHERE stock_quantity <= 10 
        AND status = 'active'
        ORDER BY stock_quantity ASC
    ");
    $stmt->execute();
    $low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // إجمالي قيمة المخزون
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_products,
            SUM(stock_quantity) as total_items,
            SUM(stock_quantity * price) as total_value
        FROM products 
        WHERE status = 'active'
    ");
    $stmt->execute();
    $inventory_summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'low_stock' => $low_stock,
        'summary' => $inventory_summary
    ];
}

// الحصول على البيانات حسب نوع التقرير
$sales_stats = getSalesStats($start_date, $end_date);
$top_products = getTopProducts($start_date, $end_date);
$customer_stats = getCustomerStats($start_date, $end_date);
$inventory_stats = getInventoryStats();

require_once 'includes/admin_header.php';
?>

<div class="container-fluid">
    <!-- عنوان الصفحة -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-bar"></i> التقارير والإحصائيات
        </h1>
        <div class="btn-group no-print">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> طباعة
            </button>
            <button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                <i class="fas fa-download"></i> تصدير
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="exportReport('csv')">
                    <i class="fas fa-file-csv"></i> تصدير CSV
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="exportReport('excel')">
                    <i class="fas fa-file-excel"></i> تصدير Excel
                </a></li>
            </ul>
        </div>
    </div>

    <!-- فلاتر التقرير -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter"></i> فلاتر التقرير</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">من تاريخ</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" 
                           value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">إلى تاريخ</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" 
                           value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="report_type" class="form-label">نوع التقرير</label>
                    <select id="report_type" name="report_type" class="form-select">
                        <option value="overview" <?php echo $report_type == 'overview' ? 'selected' : ''; ?>>نظرة عامة</option>
                        <option value="sales" <?php echo $report_type == 'sales' ? 'selected' : ''; ?>>تقرير المبيعات</option>
                        <option value="products" <?php echo $report_type == 'products' ? 'selected' : ''; ?>>تقرير المنتجات</option>
                        <option value="customers" <?php echo $report_type == 'customers' ? 'selected' : ''; ?>>تقرير العملاء</option>
                        <option value="inventory" <?php echo $report_type == 'inventory' ? 'selected' : ''; ?>>تقرير المخزون</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-search"></i> تطبيق الفلاتر
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                إجمالي المبيعات
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo format_price($sales_stats['summary']['total_revenue'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                عدد الطلبات
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($sales_stats['summary']['total_orders'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                متوسط قيمة الطلب
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo format_price($sales_stats['summary']['avg_order_value'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                عملاء جدد
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($customer_stats['new_customers']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- المحتوى الرئيسي للتقارير -->
    <div class="row">
        <?php if ($report_type == 'overview' || $report_type == 'sales'): ?>
        <!-- تقرير المبيعات -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-area"></i> تفاصيل المبيعات
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>حالة الطلب</th>
                                    <th>عدد الطلبات</th>
                                    <th>إجمالي المبيعات</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_orders = $sales_stats['summary']['total_orders'] ?? 1;
                                foreach ($sales_stats['by_status'] as $status):
                                    $percentage = ($status['count'] / $total_orders) * 100;
                                    $status_text = [
                                        'pending' => 'في الانتظار',
                                        'processing' => 'قيد المعالجة',
                                        'shipped' => 'تم الشحن',
                                        'delivered' => 'تم التسليم',
                                        'cancelled' => 'ملغي'
                                    ];
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php
                                            echo $status['status'] == 'delivered' ? 'success' :
                                                ($status['status'] == 'cancelled' ? 'danger' :
                                                ($status['status'] == 'shipped' ? 'info' : 'warning'));
                                        ?>">
                                            <?php echo $status_text[$status['status']] ?? $status['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($status['count']); ?></td>
                                    <td><?php echo format_price($status['revenue']); ?></td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- رسم بياني للمبيعات -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie"></i> توزيع الطلبات
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" width="100" height="100"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($report_type == 'overview' || $report_type == 'products'): ?>
        <!-- أفضل المنتجات مبيعاً -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-trophy"></i> أفضل المنتجات مبيعاً
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>اسم المنتج</th>
                                    <th>الكمية المباعة</th>
                                    <th>إجمالي الإيرادات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $index => $product): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <a href="edit-product.php?id=<?php echo $product['id']; ?>"
                                           class="text-decoration-none">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo number_format($product['total_sold']); ?></td>
                                    <td><?php echo format_price($product['total_revenue']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($top_products)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        لا توجد مبيعات في الفترة المحددة
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($report_type == 'overview' || $report_type == 'customers'): ?>
        <!-- أفضل العملاء -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-star"></i> أفضل العملاء
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>اسم العميل</th>
                                    <th>عدد الطلبات</th>
                                    <th>إجمالي المشتريات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customer_stats['top_customers'] as $index => $customer): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                    </td>
                                    <td><?php echo number_format($customer['total_orders']); ?></td>
                                    <td><?php echo format_price($customer['total_spent']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($customer_stats['top_customers'])): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        لا توجد بيانات عملاء في الفترة المحددة
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($report_type == 'overview' || $report_type == 'inventory'): ?>
        <!-- تقرير المخزون -->
        <div class="col-lg-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-boxes"></i> تقرير المخزون
                    </h6>
                </div>
                <div class="card-body">
                    <!-- ملخص المخزون -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title">إجمالي المنتجات</h5>
                                    <h3 class="text-primary"><?php echo number_format($inventory_stats['summary']['total_products']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title">إجمالي القطع</h5>
                                    <h3 class="text-info"><?php echo number_format($inventory_stats['summary']['total_items']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title">قيمة المخزون</h5>
                                    <h3 class="text-success"><?php echo format_price($inventory_stats['summary']['total_value']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- المنتجات منخفضة المخزون -->
                    <?php if (!empty($inventory_stats['low_stock'])): ?>
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> تحذير: منتجات منخفضة المخزون</h6>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>اسم المنتج</th>
                                        <th>الكمية المتبقية</th>
                                        <th>السعر</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventory_stats['low_stock'] as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $product['stock_quantity'] <= 5 ? 'danger' : 'warning'; ?>">
                                                <?php echo $product['stock_quantity']; ?> قطعة
                                            </span>
                                        </td>
                                        <td><?php echo format_price($product['price']); ?></td>
                                        <td>
                                            <a href="edit-product.php?id=<?php echo $product['id']; ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i> تحديث المخزون
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> جميع المنتجات لديها مخزون كافي
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// رسم بياني دائري للمبيعات
<?php if ($report_type == 'overview' || $report_type == 'sales'): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');

    const salesData = {
        labels: [
            <?php foreach ($sales_stats['by_status'] as $status):
                $status_text = [
                    'pending' => 'في الانتظار',
                    'processing' => 'قيد المعالجة',
                    'shipped' => 'تم الشحن',
                    'delivered' => 'تم التسليم',
                    'cancelled' => 'ملغي'
                ];
                echo "'" . ($status_text[$status['status']] ?? $status['status']) . "',";
            endforeach; ?>
        ],
        datasets: [{
            data: [
                <?php foreach ($sales_stats['by_status'] as $status):
                    echo $status['count'] . ',';
                endforeach; ?>
            ],
            backgroundColor: [
                '#4e73df',
                '#1cc88a',
                '#36b9cc',
                '#f6c23e',
                '#e74a3b'
            ],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: salesData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });
});
<?php endif; ?>

// تحديث التواريخ التلقائي
document.addEventListener('DOMContentLoaded', function() {
    const reportType = document.getElementById('report_type');
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');

    // تحديث التواريخ عند تغيير نوع التقرير
    reportType.addEventListener('change', function() {
        const today = new Date();
        const currentMonth = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0');

        switch(this.value) {
            case 'overview':
                startDate.value = currentMonth + '-01';
                endDate.value = today.toISOString().split('T')[0];
                break;
            case 'sales':
                // آخر 30 يوم
                const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
                startDate.value = thirtyDaysAgo.toISOString().split('T')[0];
                endDate.value = today.toISOString().split('T')[0];
                break;
            case 'products':
                // آخر 7 أيام
                const sevenDaysAgo = new Date(today.getTime() - (7 * 24 * 60 * 60 * 1000));
                startDate.value = sevenDaysAgo.toISOString().split('T')[0];
                endDate.value = today.toISOString().split('T')[0];
                break;
            case 'customers':
                startDate.value = currentMonth + '-01';
                endDate.value = today.toISOString().split('T')[0];
                break;
            case 'inventory':
                // تقرير المخزون لا يحتاج تواريخ محددة
                break;
        }
    });

    // التحقق من صحة التواريخ
    function validateDates() {
        const start = new Date(startDate.value);
        const end = new Date(endDate.value);

        if (start > end) {
            alert('تاريخ البداية يجب أن يكون قبل تاريخ النهاية');
            return false;
        }

        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays > 365) {
            alert('لا يمكن أن تتجاوز الفترة سنة واحدة');
            return false;
        }

        return true;
    }

    startDate.addEventListener('change', validateDates);
    endDate.addEventListener('change', validateDates);
});

// تصدير التقرير
function exportReport(format) {
    const currentUrl = new URL(window.location);
    const exportUrl = new URL('export-report.php', currentUrl.origin + '/proj/admin/');

    // نسخ جميع المعاملات الحالية
    exportUrl.searchParams.set('format', format);
    exportUrl.searchParams.set('report_type', currentUrl.searchParams.get('report_type') || 'overview');
    exportUrl.searchParams.set('start_date', currentUrl.searchParams.get('start_date') || '<?php echo $start_date; ?>');
    exportUrl.searchParams.set('end_date', currentUrl.searchParams.get('end_date') || '<?php echo $end_date; ?>');

    window.open(exportUrl.toString(), '_blank');
}

// طباعة التقرير
function printReport() {
    window.print();
}
</script>

<style>
@media print {
    .no-print {
        display: none !important;
    }

    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }

    .btn {
        display: none !important;
    }

    .sidebar {
        display: none !important;
    }

    .container-fluid {
        padding: 0 !important;
    }
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.text-xs {
    font-size: 0.7rem;
}

.shadow {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}
</style>

<?php require_once 'includes/admin_footer.php'; ?>
