<?php
/**
 * صفحة تأكيد الطلب
 * Order confirmation page
 */

$page_title = 'تأكيد الطلب';
$page_description = 'تأكيد إتمام الطلب بنجاح';

require_once 'config/config.php';
require_once 'classes/Order.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    redirect(SITE_URL . '/login.php');
}

// التحقق من وجود رقم الطلب
if (!isset($_GET['order']) || !is_numeric($_GET['order'])) {
    redirect(SITE_URL . '/index.php');
}

$order_id = (int)$_GET['order'];

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائن الطلب
$orderObj = new Order($db);

// الحصول على بيانات الطلب
$order = $orderObj->getOrderById($order_id, $_SESSION['user_id']);

if (!$order) {
    redirect(SITE_URL . '/index.php');
}

// الحصول على عناصر الطلب
$order_items = $orderObj->getOrderItems($order_id);

// تضمين الرأس
include 'includes/header.php';
?>

<div class="order-confirmation">
    <!-- رسالة النجاح -->
    <div class="success-message text-center mb-5">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>تم إنشاء طلبك بنجاح!</h1>
        <p class="lead">شكراً لك على ثقتك بنا. سيتم معالجة طلبك في أقرب وقت ممكن.</p>
    </div>
    
    <div class="row">
        <!-- تفاصيل الطلب -->
        <div class="col-lg-8">
            <div class="order-details">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-receipt"></i> تفاصيل الطلب</h3>
                    </div>
                    <div class="card-body">
                        <div class="order-info mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <strong>رقم الطلب:</strong>
                                        <span class="order-number">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <strong>تاريخ الطلب:</strong>
                                        <span><?php echo format_date($order['created_at']); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <strong>حالة الطلب:</strong>
                                        <span class="badge bg-warning">
                                            <?php
                                            $status_labels = [
                                                'pending' => 'في الانتظار',
                                                'confirmed' => 'مؤكد',
                                                'shipped' => 'تم الشحن',
                                                'delivered' => 'تم التسليم',
                                                'cancelled' => 'ملغي'
                                            ];
                                            echo $status_labels[$order['status']] ?? $order['status'];
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <strong>طريقة الدفع:</strong>
                                        <span>
                                            <?php
                                            $payment_methods = [
                                                'cod' => 'الدفع عند الاستلام',
                                                'credit_card' => 'بطاقة ائتمان',
                                                'bank_transfer' => 'تحويل بنكي',
                                                'paypal' => 'PayPal'
                                            ];
                                            echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- عنوان الشحن -->
                        <div class="shipping-address mb-4">
                            <h5><i class="fas fa-truck"></i> عنوان الشحن</h5>
                            <div class="address-box">
                                <p><strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong></p>
                                <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($order['email']); ?></p>
                            </div>
                        </div>
                        
                        <!-- ملاحظات -->
                        <?php if (!empty($order['notes'])): ?>
                            <div class="order-notes mb-4">
                                <h5><i class="fas fa-sticky-note"></i> ملاحظات</h5>
                                <div class="notes-box">
                                    <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- المنتجات -->
                        <div class="order-items">
                            <h5><i class="fas fa-box"></i> المنتجات المطلوبة</h5>
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
                                        <?php foreach ($order_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="product-info">
                                                        <img src="uploads/products/<?php echo $item['main_image'] ?: 'default.jpg'; ?>" 
                                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                             class="product-thumb">
                                                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo format_price($item['price']); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td><strong><?php echo format_price($item['price'] * $item['quantity']); ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ملخص الطلب -->
        <div class="col-lg-4">
            <div class="order-summary">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-calculator"></i> ملخص الطلب</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $subtotal = 0;
                        foreach ($order_items as $item) {
                            $subtotal += $item['price'] * $item['quantity'];
                        }
                        $shipping = $order['total_amount'] - $subtotal;
                        ?>
                        
                        <div class="summary-row">
                            <span>المجموع الفرعي:</span>
                            <span><?php echo format_price($subtotal); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>الشحن:</span>
                            <span><?php echo $shipping > 0 ? format_price($shipping) : 'مجاني'; ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="summary-row total">
                            <strong>
                                <span>الإجمالي:</span>
                                <span><?php echo format_price($order['total_amount']); ?></span>
                            </strong>
                        </div>
                        
                        <div class="payment-status mt-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>حالة الدفع:</strong>
                                <?php
                                $payment_status_labels = [
                                    'pending' => 'في الانتظار',
                                    'paid' => 'مدفوع',
                                    'failed' => 'فشل'
                                ];
                                echo $payment_status_labels[$order['payment_status']] ?? $order['payment_status'];
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- الإجراءات -->
            <div class="order-actions mt-4">
                <div class="card">
                    <div class="card-body">
                        <h6>ماذا بعد؟</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> سيتم مراجعة طلبك خلال 24 ساعة</li>
                            <li><i class="fas fa-truck text-info"></i> سيتم شحن الطلب خلال 2-3 أيام عمل</li>
                            <li><i class="fas fa-envelope text-primary"></i> ستصلك رسالة تأكيد على البريد الإلكتروني</li>
                        </ul>
                        
                        <div class="action-buttons mt-3">
                            <a href="profile.php" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-user"></i> عرض طلباتي
                            </a>
                            <a href="products.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-shopping-bag"></i> متابعة التسوق
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.success-message {
    background: white;
    padding: 3rem;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.success-icon {
    font-size: 5rem;
    color: #28a745;
    margin-bottom: 1rem;
}

.order-number {
    color: #667eea;
    font-weight: bold;
    font-size: 1.1rem;
}

.info-item {
    margin-bottom: 1rem;
}

.info-item strong {
    display: block;
    color: #333;
    margin-bottom: 0.25rem;
}

.address-box,
.notes-box {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    border-right: 4px solid #667eea;
}

.product-info {
    display: flex;
    align-items: center;
}

.product-thumb {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    margin-left: 1rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.summary-row.total {
    font-size: 1.2rem;
    margin-top: 1rem;
}

.order-actions ul li {
    margin-bottom: 0.5rem;
    padding-right: 1.5rem;
}

.order-actions ul li i {
    margin-left: 0.5rem;
}
</style>

<?php
// تضمين التذييل
include 'includes/footer.php';
?>
