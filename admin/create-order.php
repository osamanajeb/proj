<?php
/**
 * صفحة إنشاء طلب مباشر للمشرف
 * Admin direct order creation page
 */

require_once '../config/config.php';
require_once '../classes/Product.php';
require_once '../classes/Cart.php';
require_once '../classes/Category.php';

// التحقق من صلاحيات المشرف
if (!is_admin()) {
    redirect(SITE_URL . '/login.php');
}

$db = getDBConnection();
$productObj = new Product($db);
$cartObj = new Cart($db);
$categoryObj = new Category($db);

$error_message = '';
$success_message = '';

// معالجة إنشاء الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $customer_data = [
        'first_name' => sanitize_input($_POST['first_name']),
        'last_name' => sanitize_input($_POST['last_name']),
        'email' => sanitize_input($_POST['email']),
        'phone' => sanitize_input($_POST['phone']),
        'address' => sanitize_input($_POST['address']),
        'city' => sanitize_input($_POST['city']),
        'payment_method' => sanitize_input($_POST['payment_method']),
        'payment_status' => sanitize_input($_POST['payment_status']),
        'admin_notes' => sanitize_input($_POST['admin_notes'])
    ];
    
    $products = [];
    if (isset($_POST['products']) && is_array($_POST['products'])) {
        foreach ($_POST['products'] as $product_data) {
            if (!empty($product_data['product_id']) && !empty($product_data['quantity'])) {
                $product = $productObj->getProductById($product_data['product_id']);
                if ($product) {
                    $price = $product['discount_price'] ?: $product['price'];
                    $products[] = [
                        'product_id' => $product_data['product_id'],
                        'quantity' => (int)$product_data['quantity'],
                        'price' => $price
                    ];
                }
            }
        }
    }
    
    if (empty($products)) {
        $error_message = 'يجب إضافة منتج واحد على الأقل';
    } else {
        $result = $cartObj->createAdminDirectOrder($products, $customer_data);
        if ($result['success']) {
            $success_message = $result['message'];
            $_SESSION['message'] = ['text' => 'تم إنشاء الطلب بنجاح - رقم الطلب: #' . str_pad($result['order_id'], 6, '0', STR_PAD_LEFT), 'type' => 'success'];
            redirect(SITE_URL . '/admin/orders.php');
        } else {
            $error_message = $result['message'];
        }
    }
}

// الحصول على المنتجات والفئات
$products = $productObj->getAllProducts();
$categories = $categoryObj->getAllCategories();

include 'includes/admin_header.php';
?>

<div class="admin-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus-circle"></i> إنشاء طلب جديد</h2>
        <a href="orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> العودة للطلبات
        </a>
    </div>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" id="orderForm">
        <div class="row">
            <!-- بيانات العميل -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-user"></i> بيانات العميل</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">الاسم الأول *</label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">الاسم الأخير *</label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">البريد الإلكتروني *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">رقم الهاتف *</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">العنوان *</label>
                            <textarea name="address" class="form-control" rows="2" required></textarea>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">المدينة *</label>
                            <select name="city" class="form-select" required>
                                <option value="">اختر المدينة</option>
                                <option value="الرياض">الرياض</option>
                                <option value="جدة">جدة</option>
                                <option value="الدمام">الدمام</option>
                                <option value="مكة المكرمة">مكة المكرمة</option>
                                <option value="المدينة المنورة">المدينة المنورة</option>
                                <option value="الطائف">الطائف</option>
                                <option value="تبوك">تبوك</option>
                                <option value="بريدة">بريدة</option>
                                <option value="خميس مشيط">خميس مشيط</option>
                                <option value="حائل">حائل</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">طريقة الدفع</label>
                                    <select name="payment_method" class="form-select">
                                        <option value="cash">نقداً عند الاستلام</option>
                                        <option value="bank_transfer">تحويل بنكي</option>
                                        <option value="credit_card">بطاقة ائتمان</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">حالة الدفع</label>
                                    <select name="payment_status" class="form-select">
                                        <option value="pending">معلق</option>
                                        <option value="paid">مدفوع</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">ملاحظات المشرف</label>
                            <textarea name="admin_notes" class="form-control" rows="3"
                                      placeholder="ملاحظات إضافية حول الطلب (اختياري)"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- المنتجات -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-shopping-bag"></i> المنتجات</h5>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addProductRow()">
                            <i class="fas fa-plus"></i> إضافة منتج
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="products-container">
                            <div class="product-row mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <select name="products[0][product_id]" class="form-select product-select" onchange="updateProductPrice(this, 0)">
                                            <option value="">اختر المنتج</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?php echo $product['id']; ?>" 
                                                        data-price="<?php echo $product['discount_price'] ?: $product['price']; ?>"
                                                        data-stock="<?php echo $product['stock_quantity']; ?>">
                                                    <?php echo htmlspecialchars($product['name']); ?> 
                                                    (<?php echo format_price($product['discount_price'] ?: $product['price']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" name="products[0][quantity]" class="form-control quantity-input" 
                                               placeholder="الكمية" min="1" onchange="updateRowTotal(0)">
                                    </div>
                                    <div class="col-md-2">
                                        <span class="row-total">0.00</span>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeProductRow(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>الإجمالي:</strong>
                            <strong id="order-total">0.00 ر.س</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center">
            <button type="submit" name="create_order" class="btn btn-success btn-lg">
                <i class="fas fa-check"></i> إنشاء الطلب
            </button>
        </div>
    </form>
</div>

<style>
.product-row {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    background: #f8f9fa;
}

.row-total {
    font-weight: bold;
    color: #28a745;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 38px;
}

#order-total {
    font-size: 1.3rem;
    color: #dc3545;
}

.product-select {
    font-size: 0.9rem;
}

.quantity-input {
    text-align: center;
}
</style>

<script>
let productRowIndex = 1;

function addProductRow() {
    const container = document.getElementById('products-container');
    const newRow = document.createElement('div');
    newRow.className = 'product-row mb-3';
    newRow.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <select name="products[${productRowIndex}][product_id]" class="form-select product-select" onchange="updateProductPrice(this, ${productRowIndex})">
                    <option value="">اختر المنتج</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>"
                                data-price="<?php echo $product['discount_price'] ?: $product['price']; ?>"
                                data-stock="<?php echo $product['stock_quantity']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                            (<?php echo format_price($product['discount_price'] ?: $product['price']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" name="products[${productRowIndex}][quantity]" class="form-control quantity-input"
                       placeholder="الكمية" min="1" onchange="updateRowTotal(${productRowIndex})">
            </div>
            <div class="col-md-2">
                <span class="row-total">0.00</span>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeProductRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
    productRowIndex++;
}

function removeProductRow(button) {
    const row = button.closest('.product-row');
    row.remove();
    updateOrderTotal();
}

function updateProductPrice(select, rowIndex) {
    const option = select.options[select.selectedIndex];
    const price = option.getAttribute('data-price') || 0;
    const stock = option.getAttribute('data-stock') || 0;

    const quantityInput = select.closest('.product-row').querySelector('.quantity-input');
    quantityInput.max = stock;
    quantityInput.title = `المتوفر: ${stock}`;

    updateRowTotal(rowIndex);
}

function updateRowTotal(rowIndex) {
    const row = document.querySelector(`[name="products[${rowIndex}][product_id]"]`).closest('.product-row');
    const select = row.querySelector('.product-select');
    const quantityInput = row.querySelector('.quantity-input');
    const totalSpan = row.querySelector('.row-total');

    const option = select.options[select.selectedIndex];
    const price = parseFloat(option.getAttribute('data-price') || 0);
    const quantity = parseInt(quantityInput.value || 0);

    const total = price * quantity;
    totalSpan.textContent = total.toFixed(2);

    updateOrderTotal();
}

function updateOrderTotal() {
    const rowTotals = document.querySelectorAll('.row-total');
    let orderTotal = 0;

    rowTotals.forEach(span => {
        orderTotal += parseFloat(span.textContent || 0);
    });

    document.getElementById('order-total').textContent = orderTotal.toFixed(2) + ' ر.س';
}

// التحقق من صحة النموذج قبل الإرسال
document.getElementById('orderForm').addEventListener('submit', function(e) {
    const productSelects = document.querySelectorAll('.product-select');
    let hasProducts = false;

    productSelects.forEach(select => {
        if (select.value) {
            hasProducts = true;
        }
    });

    if (!hasProducts) {
        e.preventDefault();
        alert('يجب إضافة منتج واحد على الأقل');
        return false;
    }

    // التحقق من الكميات
    const quantityInputs = document.querySelectorAll('.quantity-input');
    let validQuantities = true;

    quantityInputs.forEach(input => {
        const row = input.closest('.product-row');
        const select = row.querySelector('.product-select');

        if (select.value && (!input.value || input.value <= 0)) {
            validQuantities = false;
            input.focus();
            return;
        }
    });

    if (!validQuantities) {
        e.preventDefault();
        alert('يجب إدخال كمية صحيحة لجميع المنتجات');
        return false;
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>
