<?php
/**
 * إدارة الطلبات - لوحة التحكم
 * Orders management - Admin panel
 */

$page_title = 'إدارة الطلبات';
$page_description = 'إدارة ومتابعة طلبات العملاء';

require_once '../config/config.php';
require_once '../classes/Order.php';

// التحقق من صلاحيات المشرف
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . '/login.php');
}

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائن الطلبات
$orderObj = new Order($db);

// معالجة تحديث حالة الطلب
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = sanitize_input($_POST['status']);

    try {
        // الحصول على الحالة الحالية للطلب
        $current_order = $orderObj->getOrderById($order_id);
        $current_status = $current_order['status'];

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

    redirect(SITE_URL . '/admin/orders.php');
}

// الحصول على الطلبات
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$orders = $orderObj->getAllOrders();

// تصفية الطلبات
if ($status_filter) {
    $orders = array_filter($orders, function($order) use ($status_filter) {
        return $order['status'] === $status_filter;
    });
}

if ($search) {
    $orders = array_filter($orders, function($order) use ($search) {
        return stripos($order['first_name'] . ' ' . $order['last_name'], $search) !== false ||
               stripos($order['email'], $search) !== false ||
               strpos(str_pad($order['id'], 6, '0', STR_PAD_LEFT), $search) !== false;
    });
}

// تضمين رأس المشرف
include 'includes/admin_header.php';
?>

<div class="orders-management">
    <div class="page-header">
        <h1><i class="fas fa-shopping-cart"></i> إدارة الطلبات</h1>
    </div>
    
    <!-- أدوات التصفية والبحث -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">البحث</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="ابحث برقم الطلب أو اسم العميل...">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">حالة الطلب</label>
                    <select name="status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>في الانتظار</option>
                        <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>مؤكد</option>
                        <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>تم الشحن</option>
                        <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>تم التسليم</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> بحث
                        </button>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="button" class="btn btn-success" onclick="exportOrders()">
                            <i class="fas fa-download"></i> تصدير Excel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <?php
        $stats = [
            'pending' => count(array_filter($orderObj->getAllOrders(), fn($o) => $o['status'] == 'pending')),
            'confirmed' => count(array_filter($orderObj->getAllOrders(), fn($o) => $o['status'] == 'confirmed')),
            'shipped' => count(array_filter($orderObj->getAllOrders(), fn($o) => $o['status'] == 'shipped')),
            'delivered' => count(array_filter($orderObj->getAllOrders(), fn($o) => $o['status'] == 'delivered'))
        ];
        ?>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p>في الانتظار</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon bg-info">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $stats['confirmed']; ?></h3>
                    <p>مؤكدة</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon bg-primary">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $stats['shipped']; ?></h3>
                    <p>تم الشحن</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $stats['delivered']; ?></h3>
                    <p>تم التسليم</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- جدول الطلبات -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> قائمة الطلبات (<?php echo count($orders); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h5>لا توجد طلبات</h5>
                    <p>لم يتم العثور على طلبات تطابق معايير البحث</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>الإجمالي</th>
                                <th>الحالة</th>
                                <th>طريقة الدفع</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr data-id="<?php echo $order['id']; ?>">
                                    <td>
                                        <strong>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo format_price($order['total_amount']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php
                                            $payment_status_labels = [
                                                'pending' => 'في الانتظار',
                                                'paid' => 'مدفوع',
                                                'failed' => 'فشل'
                                            ];
                                            echo $payment_status_labels[$order['payment_status']] ?? $order['payment_status'];
                                            ?>
                                        </small>
                                    </td>
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
                                    <td>
                                        <?php
                                        $payment_methods = [
                                            'cod' => 'الدفع عند الاستلام',
                                            'credit_card' => 'بطاقة ائتمان',
                                            'bank_transfer' => 'تحويل بنكي',
                                            'paypal' => 'PayPal'
                                        ];
                                        echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                                        ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-outline-info" title="عرض التفاصيل">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="updateOrderStatus(<?php echo $order['id']; ?>)" title="تحديث الحالة">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- نموذج تحديث حالة الطلب -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تحديث حالة الطلب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="statusOrderId">
                    <div class="form-group">
                        <label for="status" class="form-label">الحالة الجديدة</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="pending">في الانتظار</option>
                            <option value="confirmed">مؤكد</option>
                            <option value="shipped">تم الشحن</option>
                            <option value="delivered">تم التسليم</option>
                            <option value="cancelled">ملغي</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" name="update_status" class="btn btn-primary">تحديث</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateOrderStatus(orderId) {
    document.getElementById('statusOrderId').value = orderId;
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

function exportOrders() {
    window.open('export-orders.php', '_blank');
}

// البحث المباشر
document.querySelector('input[name="search"]').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.data-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>

<?php
// تضمين تذييل المشرف
include 'includes/admin_footer.php';
?>
