<?php
/**
 * صفحة سلة المشتريات
 * Shopping cart page
 */

$page_title = 'سلة المشتريات';
$page_description = 'عرض وإدارة منتجات سلة المشتريات';

require_once 'config/config.php';
require_once 'classes/Cart.php';

// منع المشرفين من الوصول لسلة المشتريات
if (is_admin()) {
    $_SESSION['message'] = ['text' => 'المشرفون لا يمكنهم استخدام سلة المشتريات. يمكنك إنشاء طلبات مباشرة من لوحة التحكم.', 'type' => 'info'];
    redirect(SITE_URL . '/admin/create-order.php');
}

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائن السلة
$cartObj = new Cart($db);

// الحصول على محتويات السلة
$cart_items = $cartObj->getCartItems();

// حساب الإجمالي
$cart_total = $cartObj->getCartTotal();

// تضمين الرأس
include 'includes/header.php';
?>

<div class="cart-page">
    <h1><i class="fas fa-shopping-cart"></i> سلة المشتريات</h1>
    
    <?php if (empty($cart_items)): ?>
        <!-- السلة فارغة -->
        <div class="empty-cart text-center py-5">
            <i class="fas fa-shopping-cart fa-5x text-muted mb-3"></i>
            <h3>سلة المشتريات فارغة</h3>
            <p class="text-muted">لم تقم بإضافة أي منتجات إلى سلة المشتريات بعد.</p>
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag"></i> ابدأ التسوق
            </a>
        </div>
    <?php else: ?>
        <!-- محتويات السلة -->
        <div class="row">
            <div class="col-lg-8">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" data-cart-id="<?php echo $item['id']; ?>">
                            <div class="row align-items-center">
                                <!-- صورة المنتج -->
                                <div class="col-md-2 col-3">
                                    <img src="uploads/<?php echo $item['main_image'] ?: 'default.jpg'; ?>"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="img-fluid rounded">
                                </div>
                                
                                <!-- تفاصيل المنتج -->
                                <div class="col-md-4 col-9">
                                    <h5 class="item-name">
                                        <a href="product.php?id=<?php echo $item['product_id']; ?>">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                    </h5>
                                    <div class="item-price">
                                        <?php $unit_price = $item['discount_price'] ?: $item['price']; ?>
                                        <span class="current-price"><?php echo format_price($unit_price); ?></span>
                                        <?php if ($item['discount_price']): ?>
                                            <span class="original-price"><?php echo format_price($item['price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- حالة التوفر -->
                                    <?php if ($item['stock_quantity'] < $item['quantity']): ?>
                                        <div class="stock-warning">
                                            <small class="text-warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                متوفر فقط <?php echo $item['stock_quantity']; ?> قطعة
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- الكمية -->
                                <div class="col-md-3 col-6">
                                    <div class="quantity-controls">
                                        <label class="form-label">الكمية</label>
                                        <div class="input-group">
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control text-center" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $item['stock_quantity']; ?>"
                                                   onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)"
                                                    <?php echo $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : ''; ?>>
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- الإجمالي والحذف -->
                                <div class="col-md-3 col-6">
                                    <div class="item-total">
                                        <div class="total-price">
                                            <?php echo format_price($unit_price * $item['quantity']); ?>
                                        </div>
                                        <button class="btn btn-outline-danger btn-sm mt-2" 
                                                onclick="removeFromCart(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-trash"></i> حذف
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- أزرار إضافية -->
                <div class="cart-actions mt-4">
                    <a href="products.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-right"></i> متابعة التسوق
                    </a>
                    <button class="btn btn-outline-danger" onclick="clearCart()">
                        <i class="fas fa-trash"></i> إفراغ السلة
                    </button>
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
                            <div class="summary-row">
                                <span>المجموع الفرعي:</span>
                                <span><?php echo format_price($cart_total); ?></span>
                            </div>
                            
                            <div class="summary-row">
                                <span>الشحن:</span>
                                <span>
                                    <?php 
                                    $shipping_cost = $cart_total >= 200 ? 0 : 25;
                                    echo $shipping_cost > 0 ? format_price($shipping_cost) : 'مجاني';
                                    ?>
                                </span>
                            </div>
                            
                            <?php if ($cart_total >= 200): ?>
                                <div class="free-shipping-notice">
                                    <small class="text-success">
                                        <i class="fas fa-truck"></i> تهانينا! حصلت على شحن مجاني
                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="shipping-notice">
                                    <small class="text-info">
                                        <i class="fas fa-info-circle"></i> 
                                        أضف <?php echo format_price(200 - $cart_total); ?> للحصول على شحن مجاني
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <div class="summary-row total">
                                <strong>
                                    <span>الإجمالي:</span>
                                    <span><?php echo format_price($cart_total + $shipping_cost); ?></span>
                                </strong>
                            </div>
                            
                            <div class="checkout-actions mt-3">
                                <?php if (is_logged_in()): ?>
                                    <a href="checkout.php" class="btn btn-success w-100 mb-2">
                                        <i class="fas fa-credit-card"></i> إتمام الطلب
                                    </a>
                                <?php else: ?>
                                    <a href="login.php?redirect=checkout.php" class="btn btn-success w-100 mb-2">
                                        <i class="fas fa-sign-in-alt"></i> تسجيل الدخول للمتابعة
                                    </a>
                                <?php endif; ?>
                                
                                <div class="payment-methods">
                                    <small class="text-muted">طرق الدفع المقبولة:</small>
                                    <div class="payment-icons mt-1">
                                        <i class="fab fa-cc-visa"></i>
                                        <i class="fab fa-cc-mastercard"></i>
                                        <i class="fab fa-cc-paypal"></i>
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.cart-item {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.item-name a {
    color: #333;
    text-decoration: none;
}

.item-name a:hover {
    color: #667eea;
}

.current-price {
    font-weight: bold;
    color: #ff4757;
}

.original-price {
    text-decoration: line-through;
    color: #999;
    margin-right: 0.5rem;
}

.quantity-controls .input-group {
    max-width: 120px;
}

.item-total {
    text-align: center;
}

.total-price {
    font-size: 1.2rem;
    font-weight: bold;
    color: #333;
}

.order-summary .card {
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-radius: 15px;
}

.order-summary .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    border: none;
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

.payment-icons i {
    font-size: 1.5rem;
    margin-left: 0.5rem;
    color: #667eea;
}

.stock-warning {
    margin-top: 0.5rem;
}

.free-shipping-notice,
.shipping-notice {
    margin: 0.5rem 0;
    padding: 0.5rem;
    border-radius: 5px;
    background: rgba(0,123,255,0.1);
}

.free-shipping-notice {
    background: rgba(40,167,69,0.1);
}
</style>

<script>
function updateQuantity(cartId, quantity) {
    if (quantity < 1) {
        removeFromCart(cartId);
        return;
    }
    
    updateCartQuantity(cartId, quantity);
}

function clearCart() {
    if (confirm('هل أنت متأكد من إفراغ السلة؟')) {
        fetch('ajax/clear_cart.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showAlert(data.message || 'حدث خطأ أثناء إفراغ السلة', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('حدث خطأ في الاتصال', 'error');
        });
    }
}
</script>

<?php
// تضمين التذييل
include 'includes/footer.php';
?>
