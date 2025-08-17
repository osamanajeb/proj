<?php
/**
 * تقرير المبيعات اليومية
 * Daily Sales Report
 */

$page_title = 'تقرير المبيعات اليومية';

require_once '../config/config.php';
require_once '../classes/Order.php';

// الحصول على التاريخ المحدد
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// دالة للحصول على مبيعات اليوم
function getDailySales($date) {
    $db = getDBConnection();
    
    // إحصائيات عامة
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value,
            SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END) as delivered_revenue,
            COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders
        FROM orders 
        WHERE DATE(created_at) = ?
    ");
    $stmt->execute([$date]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // تفاصيل الطلبات
    $stmt = $db->prepare("
        SELECT 
            o.id,
            o.created_at,
            CONCAT(u.first_name, ' ', u.last_name) as customer_name,
            o.total_amount,
            o.status,
            o.payment_method,
            o.payment_status
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE DATE(o.created_at) = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$date]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // أفضل المنتجات مبيعاً في هذا اليوم
    $stmt = $db->prepare("
        SELECT 
            p.id,
            p.name,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as total_revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) = ?
        GROUP BY p.id, p.name
        ORDER BY total_sold DESC
        LIMIT 10
    ");
    $stmt->execute([$date]);
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'summary' => $summary,
        'orders' => $orders,
        'top_products' => $top_products
    ];
}

$daily_data = getDailySales($selected_date);

require_once 'includes/admin_header.php';
?>

<div class="container-fluid">
    <!-- عنوان الصفحة -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-calendar-day"></i> تقرير المبيعات اليومية
        </h1>
        <div class="btn-group">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> طباعة
            </button>
            <a href="reports.php" class="btn btn-outline-secondary">
                <i class="fas fa-chart-bar"></i> التقارير الشاملة
            </a>
        </div>
    </div>

    <!-- اختيار التاريخ -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-calendar"></i> اختيار التاريخ</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="date" class="form-label">التاريخ</label>
                    <input type="date" id="date" name="date" class="form-control" 
                           value="<?php echo $selected_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-search"></i> عرض
                    </button>
                </div>
                <div class="col-md-6">
                    <label class="form-label">اختصارات سريعة</label>
                    <div class="btn-group d-block">
                        <a href="?date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-primary btn-sm">اليوم</a>
                        <a href="?date=<?php echo date('Y-m-d', strtotime('-1 day')); ?>" class="btn btn-outline-primary btn-sm">أمس</a>
                        <a href="?date=<?php echo date('Y-m-d', strtotime('-7 days')); ?>" class="btn btn-outline-primary btn-sm">قبل أسبوع</a>
                    </div>
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
                                <?php echo format_price($daily_data['summary']['total_revenue'] ?? 0); ?>
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
                                <?php echo number_format($daily_data['summary']['total_orders'] ?? 0); ?>
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
                                <?php echo format_price($daily_data['summary']['avg_order_value'] ?? 0); ?>
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
                                الطلبات المسلمة
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($daily_data['summary']['delivered_orders'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- قائمة الطلبات -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> طلبات يوم <?php echo date('d/m/Y', strtotime($selected_date)); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($daily_data['orders'])): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>الوقت</th>
                                    <th>العميل</th>
                                    <th>المبلغ</th>
                                    <th>الحالة</th>
                                    <th>طريقة الدفع</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daily_data['orders'] as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td><?php echo date('H:i', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo format_price($order['total_amount']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $order['status'] == 'delivered' ? 'success' : 
                                                ($order['status'] == 'cancelled' ? 'danger' : 
                                                ($order['status'] == 'shipped' ? 'info' : 'warning')); 
                                        ?>">
                                            <?php 
                                            $status_text = [
                                                'pending' => 'في الانتظار',
                                                'processing' => 'قيد المعالجة',
                                                'shipped' => 'تم الشحن',
                                                'delivered' => 'تم التسليم',
                                                'cancelled' => 'ملغي'
                                            ];
                                            echo $status_text[$order['status']] ?? $order['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo $order['payment_method']; ?></td>
                                    <td>
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> عرض
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <h5>لا توجد طلبات في هذا التاريخ</h5>
                        <p class="mb-0">لم يتم تسجيل أي طلبات في تاريخ <?php echo date('d/m/Y', strtotime($selected_date)); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- أفضل المنتجات -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-trophy"></i> أفضل المنتجات مبيعاً
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($daily_data['top_products'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المنتج</th>
                                    <th>الكمية</th>
                                    <th>الإيرادات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daily_data['top_products'] as $index => $product): ?>
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
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-light text-center">
                        <i class="fas fa-box-open"></i><br>
                        لا توجد مبيعات منتجات
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, .btn, .card-header .btn-group {
        display: none !important;
    }
    
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
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
