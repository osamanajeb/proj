<?php
/**
 * صفحة إتمام الطلب
 * Checkout page
 */

$page_title = 'إتمام الطلب';
$page_description = 'إتمام عملية الشراء';

require_once 'config/config.php';
require_once 'classes/Cart.php';
require_once 'classes/Order.php';
require_once 'classes/User.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    redirect(SITE_URL . '/login.php?redirect=checkout.php');
}

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائنات الفئات
$cartObj = new Cart($db);
$orderObj = new Order($db);
$userObj = new User($db);

// الحصول على محتويات السلة
$cart_items = $cartObj->getCartItems();

// التحقق من وجود منتجات في السلة
if (empty($cart_items)) {
    redirect(SITE_URL . '/cart.php');
}

// التحقق من توفر المنتجات
$unavailable_items = $cartObj->checkStockAvailability();
if (!empty($unavailable_items)) {
    $_SESSION['message'] = [
        'text' => 'بعض المنتجات في سلتك غير متوفرة بالكمية المطلوبة',
        'type' => 'warning'
    ];
    redirect(SITE_URL . '/cart.php');
}

// حساب الإجمالي
$cart_total = $cartObj->getCartTotal();
$shipping_cost = $cart_total >= 200 ? 0 : 25;
$final_total = $cart_total + $shipping_cost;

// الحصول على بيانات المستخدم
$user = $userObj->getUserById($_SESSION['user_id']);

$error_message = '';
$success_message = '';

// معالجة إتمام الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = sanitize_input($_POST['shipping_address']);
    $payment_method = sanitize_input($_POST['payment_method']);
    $notes = sanitize_input($_POST['notes']);
    
    if (empty($shipping_address) || empty($payment_method)) {
        $error_message = 'يرجى ملء جميع الحقول المطلوبة';
    } else {
        // إنشاء الطلب
        $result = $orderObj->createOrder($_SESSION['user_id'], $cart_items, $shipping_address, $payment_method, $notes);
        
        if ($result['success']) {
            // إفراغ السلة
            $cartObj->clearCart();
            
            // إعادة التوجيه إلى صفحة تأكيد الطلب
            $_SESSION['message'] = [
                'text' => 'تم إنشاء طلبك بنجاح! رقم الطلب: ' . $result['order_id'],
                'type' => 'success'
            ];
            redirect(SITE_URL . '/order-confirmation.php?order=' . $result['order_id']);
        } else {
            $error_message = $result['message'];
        }
    }
}

// تضمين الرأس
include 'includes/header.php';
?>

<div class="checkout-page">
    <h1><i class="fas fa-credit-card"></i> إتمام الطلب</h1>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" class="checkout-form">
        <div class="row">
            <!-- معلومات الشحن والدفع -->
            <div class="col-lg-8">
                <!-- معلومات الشحن -->
                <div class="checkout-section">
                    <h3><i class="fas fa-truck"></i> معلومات الشحن</h3>
                    
                    <div class="customer-info mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>الاسم:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($user['email']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>الهاتف:</strong> <?php echo htmlspecialchars($user['phone'] ?: 'غير محدد'); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>المدينة:</strong> <?php echo htmlspecialchars($user['city'] ?: 'غير محددة'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="shipping_address" class="form-label">
                            <i class="fas fa-map-marker-alt"></i> عنوان الشحن *
                        </label>
                        <textarea id="shipping_address" name="shipping_address" class="form-control" 
                                  rows="3" required placeholder="العنوان التفصيلي للشحن..."><?php echo htmlspecialchars($user['address'] ?: ''); ?></textarea>
                    </div>
                </div>
                
                <!-- طريقة الدفع -->
                <div class="checkout-section">
                    <h3><i class="fas fa-credit-card"></i> طريقة الدفع</h3>
                    
                    <div class="payment-methods">
                        <div class="form-check payment-option">
                            <input type="radio" id="cod" name="payment_method" value="cod" class="form-check-input" checked>
                            <label for="cod" class="form-check-label">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>الدفع عند الاستلام</span>
                                <small>ادفع نقداً عند وصول الطلب</small>
                            </label>
                        </div>
                        
                        <div class="form-check payment-option">
                            <input type="radio" id="credit_card" name="payment_method" value="credit_card" class="form-check-input">
                            <label for="credit_card" class="form-check-label">
                                <i class="fas fa-credit-card"></i>
                                <span>بطاقة ائتمان</span>
                                <small>فيزا، ماستركارد</small>
                            </label>
                        </div>
                        
                        <div class="form-check payment-option">
                            <input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer" class="form-check-input">
                            <label for="bank_transfer" class="form-check-label">
                                <i class="fas fa-university"></i>
                                <span>تحويل بنكي</span>
                                <small>تحويل مباشر إلى حساب البنك</small>
                            </label>
                        </div>
                        
                        <div class="form-check payment-option">
                            <input type="radio" id="paypal" name="payment_method" value="paypal" class="form-check-input">
                            <label for="paypal" class="form-check-label">
                                <i class="fab fa-paypal"></i>
                                <span>PayPal</span>
                                <small>ادفع بأمان عبر PayPal</small>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- ملاحظات إضافية -->
                <div class="checkout-section">
                    <h3><i class="fas fa-sticky-note"></i> ملاحظات إضافية</h3>
                    
                    <div class="form-group">
                        <label for="notes" class="form-label">ملاحظات للطلب (اختياري)</label>
                        <textarea id="notes" name="notes" class="form-control" 
                                  rows="3" placeholder="أي ملاحظات خاصة بطلبك..."></textarea>
                    </div>
                </div>
            </div>
            
            <!-- ملخص الطلب -->
            <div class="col-lg-4">
                <div class="order-summary">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-receipt"></i> ملخص الطلب</h5>
                        </div>
                        <div class="card-body">
                            <!-- المنتجات -->
                            <div class="order-items">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="order-item">
                                        <div class="item-image">
                                            <img src="uploads/products/<?php echo $item['main_image'] ?: 'default.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        </div>
                                        <div class="item-details">
                                            <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <div class="item-price">
                                                <?php $unit_price = $item['discount_price'] ?: $item['price']; ?>
                                                <span><?php echo format_price($unit_price); ?> × <?php echo $item['quantity']; ?></span>
                                                <strong><?php echo format_price($unit_price * $item['quantity']); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <hr>
                            
                            <!-- الحسابات -->
                            <div class="summary-row">
                                <span>المجموع الفرعي:</span>
                                <span><?php echo format_price($cart_total); ?></span>
                            </div>
                            
                            <div class="summary-row">
                                <span>الشحن:</span>
                                <span><?php echo $shipping_cost > 0 ? format_price($shipping_cost) : 'مجاني'; ?></span>
                            </div>
                            
                            <hr>
                            
                            <div class="summary-row total">
                                <strong>
                                    <span>الإجمالي:</span>
                                    <span><?php echo format_price($final_total); ?></span>
                                </strong>
                            </div>
                            
                            <!-- زر إتمام الطلب -->
                            <button type="submit" class="btn btn-success w-100 mt-3">
                                <i class="fas fa-check"></i> تأكيد الطلب
                            </button>
                            
                            <div class="security-notice mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt"></i>
                                    معاملتك آمنة ومحمية بتشفير SSL
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.checkout-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.checkout-section h3 {
    color: #333;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f8f9fa;
}

.customer-info {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
}

.payment-option {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.3s;
}

.payment-option:hover,
.payment-option:has(input:checked) {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

.payment-option label {
    cursor: pointer;
    width: 100%;
    margin: 0;
}

.payment-option i {
    font-size: 1.5rem;
    color: #667eea;
    margin-left: 1rem;
}

.payment-option span {
    font-weight: 600;
    display: block;
}

.payment-option small {
    color: #666;
    display: block;
    margin-top: 0.25rem;
}

.order-item {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f8f9fa;
}

.item-image {
    width: 60px;
    height: 60px;
    margin-left: 1rem;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
}

.item-details {
    flex: 1;
}

.item-details h6 {
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
}

.item-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
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

.security-notice {
    text-align: center;
}
</style>

<?php
// تضمين التذييل
include 'includes/footer.php';
?>
