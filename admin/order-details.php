<?php
/**
 * صفحة تفاصيل الطلب - لوحة تحكم المشرف
 * Order details page - Admin panel
 */

$page_title = 'تفاصيل الطلب';

require_once '../config/config.php';
require_once '../classes/Order.php';

// إزالة التحقق من تسجيل دخول المشرف للسماح بعرض التفاصيل مباشرة
// if (!is_admin_logged_in()) {
//     redirect(SITE_URL . '/admin/login.php');
// }

// التحقق من وجود معرف الطلب
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect(SITE_URL . '/admin/orders.php');
}

$order_id = (int)$_GET['id'];

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائن الطلب
$orderObj = new Order($db);

// الحصول على بيانات الطلب
$order = $orderObj->getOrderById($order_id);

if (!$order) {
    $_SESSION['message'] = ['text' => 'الطلب غير موجود', 'type' => 'error'];
    redirect(SITE_URL . '/admin/orders.php');
}

// الحصول على عناصر الطلب
$order_items = $orderObj->getOrderItems($order_id);

// معالجة تحديث حالة الطلب
if (isset($_POST['update_status']) && isset($_POST['status'])) {
    $status = sanitize_input($_POST['status']);

    try {
        // إذا كان التغيير إلى "ملغي"، استخدم دالة الإلغاء المخصصة
        if ($status === 'cancelled') {
            $result = $orderObj->cancelOrder($order_id);

            if ($result['success']) {
                $_SESSION['message'] = ['text' => 'تم إلغاء الطلب وإرجاع المنتجات للمخزون بنجاح', 'type' => 'success'];
            } else {
                $_SESSION['message'] = ['text' => $result['message'], 'type' => 'error'];
            }
        } else {
            // تحديث الحالة العادي
            $result = $orderObj->updateOrderStatus($order_id, $status);

            if ($result) {
                // إذا تم تسليم الطلب، تحديث إحصائيات المبيعات
                if ($status === 'delivered') {
                    try {
                        $orderObj->updateSalesStatistics($order_id);
                    } catch (Exception $e) {
                        error_log("Error updating sales statistics: " . $e->getMessage());
                    }
                }
                $_SESSION['message'] = ['text' => 'تم تحديث حالة الطلب بنجاح', 'type' => 'success'];
            } else {
                $_SESSION['message'] = ['text' => 'حدث خطأ أثناء تحديث حالة الطلب', 'type' => 'error'];
            }
        }
    } catch (Exception $e) {
        $_SESSION['message'] = ['text' => 'حدث خطأ: ' . $e->getMessage(), 'type' => 'error'];
    }

    redirect(SITE_URL . '/admin/order-details.php?id=' . $order_id);
}

// تضمين رأس المشرف
include 'includes/admin_header.php';

// تعريف حالات الطلب
$status_labels = [
    'pending' => 'في الانتظار',
    'confirmed' => 'مؤكد',
    'shipped' => 'تم الشحن',
    'delivered' => 'تم التسليم',
    'cancelled' => 'ملغي'
];

$status_classes = [
    'pending' => 'warning',
    'confirmed' => 'info',
    'shipped' => 'primary',
    'delivered' => 'success',
    'cancelled' => 'danger'
];

$payment_methods = [
    'cash_on_delivery' => 'الدفع عند الاستلام',
    'bank_transfer' => 'تحويل بنكي',
    'credit_card' => 'بطاقة ائتمان'
];

$payment_status_labels = [
    'pending' => 'في الانتظار',
    'paid' => 'مدفوع',
    'failed' => 'فشل'
];

$payment_status_classes = [
    'pending' => 'warning',
    'paid' => 'success',
    'failed' => 'danger'
];
?>

<div class="container-fluid">
    <!-- عرض الرسائل -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message']['type'] === 'error' ? 'danger' : $_SESSION['message']['type']; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $_SESSION['message']['type'] === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
            <?php echo $_SESSION['message']['text']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- رأس الصفحة -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">تفاصيل الطلب #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h1>
            <p class="text-muted">عرض وإدارة تفاصيل الطلب</p>
        </div>
        <div>
            <a href="orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة للطلبات
            </a>
        </div>
    </div>

    <div class="row">
        <!-- معلومات الطلب -->
        <div class="col-lg-8">
            <!-- تفاصيل الطلب -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> معلومات الطلب</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>رقم الطلب:</strong></td>
                                    <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>العميل:</strong></td>
                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>البريد الإلكتروني:</strong></td>
                                    <td><?php echo htmlspecialchars($order['email']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>تاريخ الطلب:</strong></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>الحالة:</strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_classes[$order['status']]; ?>">
                                            <?php echo $status_labels[$order['status']]; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>طريقة الدفع:</strong></td>
                                    <td><?php echo $payment_methods[$order['payment_method']] ?? $order['payment_method']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>حالة الدفع:</strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $payment_status_classes[$order['payment_status']]; ?>">
                                            <?php echo $payment_status_labels[$order['payment_status']]; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>المبلغ الإجمالي:</strong></td>
                                    <td><strong><?php echo format_price($order['total_amount']); ?></strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['shipping_address'])): ?>
                        <div class="mt-3">
                            <h6><i class="fas fa-map-marker-alt"></i> عنوان الشحن:</h6>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- عناصر الطلب -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-shopping-cart"></i> عناصر الطلب</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($order_items)): ?>
                        <p class="text-muted">لا توجد عناصر في هذا الطلب</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>السعر</th>
                                        <th>الكمية</th>
                                        <th>الإجمالي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $subtotal = 0;
                                    foreach ($order_items as $item): 
                                        $item_total = $item['price'] * $item['quantity'];
                                        $subtotal += $item_total;
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($item['main_image'])): ?>
                                                        <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo htmlspecialchars($item['main_image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                             class="product-thumb me-3">
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo format_price($item['price']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><?php echo format_price($item_total); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3">الإجمالي:</th>
                                        <th><?php echo format_price($subtotal); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- الإجراءات -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-cogs"></i> إجراءات الطلب</h5>
                </div>
                <div class="card-body">
                    <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                        <form method="POST" action="">
                            <div class="form-group mb-3">
                                <label for="status" class="form-label">تحديث حالة الطلب:</label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="">اختر الحالة</option>
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>في الانتظار</option>
                                    <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>مؤكد</option>
                                    <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>تم الشحن</option>
                                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>تم التسليم</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i> تحديث الحالة
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            لا يمكن تعديل هذا الطلب لأنه <?php echo $status_labels[$order['status']]; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.product-thumb {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 5px;
}

.table-borderless td {
    border: none;
    padding: 0.5rem 0;
}
</style>

<?php include 'includes/admin_footer.php'; ?>
