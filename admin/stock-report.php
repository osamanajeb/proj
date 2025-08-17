<?php
/**
 * تقرير المخزون والطلبات الملغية
 * Stock and cancelled orders report
 */

$page_title = 'تقرير المخزون والطلبات الملغية';
$page_description = 'عرض تقرير شامل عن حالة المخزون والطلبات الملغية';

require_once '../config/config.php';
require_once '../classes/Order.php';
require_once '../classes/Product.php';

// التحقق من صلاحيات المشرف
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . '/login.php');
}

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائنات الفئات
$orderObj = new Order($db);
$productObj = new Product($db);

// الحصول على الطلبات الملغية
$cancelled_orders = array_filter($orderObj->getAllOrders(), function($order) {
    return $order['status'] === 'cancelled';
});

// الحصول على المنتجات منخفضة المخزون
$low_stock_products = array_filter($productObj->getAllProducts(), function($product) {
    return $product['stock_quantity'] <= 5;
});

// إحصائيات سريعة
$total_cancelled_orders = count($cancelled_orders);
$total_low_stock_products = count($low_stock_products);

// حساب إجمالي قيمة الطلبات الملغية
$cancelled_orders_value = 0;
foreach ($cancelled_orders as $order) {
    $cancelled_orders_value += $order['total_amount'];
}

include 'includes/admin_header.php';
?>

<div class="stock-report">
    <div class="page-header">
        <h1><i class="fas fa-chart-bar"></i> تقرير المخزون والطلبات الملغية</h1>
    </div>
    
    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $total_cancelled_orders; ?></h4>
                            <p class="mb-0">طلبات ملغية</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $total_low_stock_products; ?></h4>
                            <p class="mb-0">منتجات منخفضة المخزون</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo format_price($cancelled_orders_value); ?></h4>
                            <p class="mb-0">قيمة الطلبات الملغية</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo date('Y-m-d'); ?></h4>
                            <p class="mb-0">تاريخ التقرير</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- المنتجات منخفضة المخزون -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-exclamation-triangle text-warning"></i> المنتجات منخفضة المخزون (5 قطع أو أقل)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($low_stock_products)): ?>
                <div class="text-center py-3">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5>ممتاز! جميع المنتجات لديها مخزون كافي</h5>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>الفئة</th>
                                <th>المخزون الحالي</th>
                                <th>السعر</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock_products as $product): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../uploads/products/<?php echo $product['main_image'] ?: 'default.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'غير محدد'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $product['stock_quantity'] == 0 ? 'danger' : 'warning'; ?>">
                                            <?php echo $product['stock_quantity']; ?> قطعة
                                        </span>
                                    </td>
                                    <td><?php echo format_price($product['price']); ?></td>
                                    <td>
                                        <?php if ($product['stock_quantity'] == 0): ?>
                                            <span class="badge bg-danger">نفد المخزون</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">مخزون منخفض</span>
                                        <?php endif; ?>
                                    </td>
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
            <?php endif; ?>
        </div>
    </div>
    
    <!-- الطلبات الملغية -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-times-circle text-danger"></i> الطلبات الملغية</h5>
        </div>
        <div class="card-body">
            <?php if (empty($cancelled_orders)): ?>
                <div class="text-center py-3">
                    <i class="fas fa-smile fa-3x text-success mb-3"></i>
                    <h5>رائع! لا توجد طلبات ملغية</h5>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>تاريخ الطلب</th>
                                <th>قيمة الطلب</th>
                                <th>طريقة الدفع</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($cancelled_orders, 0, 20) as $order): ?>
                                <tr>
                                    <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                    <td><?php echo format_date($order['created_at']); ?></td>
                                    <td><?php echo format_price($order['total_amount']); ?></td>
                                    <td>
                                        <?php
                                        $payment_methods = [
                                            'cash' => 'الدفع عند الاستلام',
                                            'card' => 'بطاقة ائتمان',
                                            'bank' => 'تحويل بنكي'
                                        ];
                                        echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-eye"></i> عرض التفاصيل
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($cancelled_orders) > 20): ?>
                    <div class="text-center mt-3">
                        <p class="text-muted">عرض 20 من أصل <?php echo count($cancelled_orders); ?> طلب ملغي</p>
                        <a href="orders.php?status=cancelled" class="btn btn-outline-primary">
                            عرض جميع الطلبات الملغية
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function viewOrderDetails(orderId) {
    // يمكن إضافة modal أو توجيه لصفحة تفاصيل الطلب
    window.location.href = 'orders.php?search=' + orderId;
}

// تحديث التقرير كل 5 دقائق
setTimeout(function() {
    location.reload();
}, 300000);
</script>

<?php include 'includes/admin_footer.php'; ?>
