<?php
/**
 * صفحة تفاصيل المنتج
 * Product details page
 */

require_once 'config/config.php';
require_once 'classes/Product.php';
require_once 'classes/Review.php';

// التحقق من وجود معرف المنتج
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$product_id = (int)$_GET['id'];

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائن المنتج
$productObj = new Product($db);
$reviewObj = new Review($db);

// الحصول على بيانات المنتج
$product = $productObj->getProductById($product_id);

if (!$product) {
    header('Location: products.php');
    exit;
}

// الحصول على صور المنتج
$product_images = $productObj->getProductImages($product_id);

// الحصول على منتجات مشابهة
$related_products = $productObj->getProductsByCategory($product['category_id'], 4);

// الحصول على التقييمات
$reviews = $reviewObj->getProductReviews($product_id);
$rating_data = $reviewObj->getProductRating($product_id);
$rating_distribution = $reviewObj->getRatingDistribution($product_id);

// معالجة إضافة تقييم
$review_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review'])) {
    if (!is_logged_in()) {
        $review_message = 'يجب تسجيل الدخول لإضافة تقييم';
    } else {
        $rating = (int)$_POST['rating'];
        $comment = sanitize_input($_POST['comment']);

        if ($rating >= 1 && $rating <= 5) {
            $result = $reviewObj->addReview($product_id, $_SESSION['user_id'], $rating, $comment);
            $review_message = $result['message'];

            if ($result['success']) {
                // إعادة تحميل التقييمات
                $reviews = $reviewObj->getProductReviews($product_id);
                $rating_data = $reviewObj->getProductRating($product_id);
            }
        } else {
            $review_message = 'يرجى اختيار تقييم صحيح';
        }
    }
}

$page_title = $product['name'];
$page_description = substr($product['description'], 0, 160);

// تضمين الرأس
include 'includes/header.php';
?>

<div class="row">
    <!-- صور المنتج -->
    <div class="col-lg-6 mb-4">
        <div class="product-images">
            <!-- الصورة الرئيسية -->
            <div class="main-image mb-3">
                <img id="mainImage"
                     src="uploads/<?php echo $product['main_image'] ?: 'default.jpg'; ?>"
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     class="img-fluid rounded">
            </div>
            
            <!-- الصور الإضافية -->
            <?php if (!empty($product_images)): ?>
                <div class="thumbnail-images">
                    <div class="row">
                        <!-- الصورة الرئيسية -->
                        <div class="col-3 mb-2">
                            <img src="uploads/<?php echo $product['main_image'] ?: 'default.jpg'; ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="img-fluid rounded thumbnail-img active"
                                 onclick="changeMainImage(this.src)">
                        </div>
                        
                        <!-- الصور الإضافية -->
                        <?php foreach ($product_images as $image): ?>
                            <div class="col-3 mb-2">
                                <img src="uploads/<?php echo $image['image_path']; ?>"
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="img-fluid rounded thumbnail-img"
                                     onclick="changeMainImage(this.src)">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- تفاصيل المنتج -->
    <div class="col-lg-6">
        <div class="product-details">
            <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="product-category mb-3">
                <span class="badge bg-secondary">
                    <i class="fas fa-tag"></i> <?php echo $product['category_name']; ?>
                </span>
                <?php if ($product['featured']): ?>
                    <span class="badge bg-warning">
                        <i class="fas fa-star"></i> منتج مميز
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- السعر -->
            <div class="product-price mb-4">
                <div class="current-price">
                    <?php echo format_price($product['discount_price'] ?: $product['price']); ?>
                </div>
                <?php if ($product['discount_price']): ?>
                    <div class="original-price">
                        <?php echo format_price($product['price']); ?>
                    </div>
                    <div class="discount-percentage">
                        خصم <?php echo round((1 - $product['discount_price'] / $product['price']) * 100); ?>%
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- حالة التوفر -->
            <div class="stock-status mb-4">
                <?php if ($product['stock_quantity'] > 0): ?>
                    <div class="in-stock">
                        <i class="fas fa-check-circle text-success"></i>
                        <span class="text-success">متوفر في المخزون (<?php echo $product['stock_quantity']; ?> قطعة)</span>
                    </div>
                <?php else: ?>
                    <div class="out-of-stock">
                        <i class="fas fa-times-circle text-danger"></i>
                        <span class="text-danger">غير متوفر حالياً</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- إضافة إلى السلة -->
            <?php if (!is_admin()): ?>
                <?php if ($product['stock_quantity'] > 0): ?>
                    <div class="add-to-cart-section mb-4">
                        <div class="row">
                            <div class="col-4">
                                <label class="form-label">الكمية</label>
                                <input type="number" id="quantity" class="form-control"
                                       value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                            </div>
                            <div class="col-8">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-primary w-100"
                                        id="add-to-cart-btn"
                                        onclick="addToCartWithQuantity()">
                                    <i class="fas fa-cart-plus"></i> أضف إلى السلة
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> هذا المنتج غير متوفر حالياً
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    أنت مسجل دخول كمشرف. يمكنك
                    <a href="<?php echo SITE_URL; ?>/admin/create-order.php" class="alert-link">إنشاء طلب مباشر</a>
                    من لوحة التحكم.
                </div>
            <?php endif; ?>
            
            <!-- الوصف -->
            <div class="product-description">
                <h5>وصف المنتج</h5>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <!-- التقييمات -->
            <div class="product-rating mt-4">
                <h5>التقييمات والمراجعات</h5>
                <?php if ($rating_data['total'] > 0): ?>
                    <div class="rating-summary mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="overall-rating">
                                    <span class="rating-number"><?php echo $rating_data['average']; ?></span>
                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $rating_data['average'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted">(<?php echo $rating_data['total']; ?> تقييم)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="rating-breakdown">
                                    <?php foreach ($rating_distribution as $stars => $count): ?>
                                        <div class="rating-bar">
                                            <span><?php echo $stars; ?> نجوم</span>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?php echo $rating_data['total'] > 0 ? ($count / $rating_data['total']) * 100 : 0; ?>%"></div>
                                            </div>
                                            <span><?php echo $count; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted">لا توجد تقييمات لهذا المنتج بعد</p>
                <?php endif; ?>
            </div>
            
            <!-- معلومات إضافية -->
            <div class="product-info mt-4">
                <div class="row">
                    <div class="col-6">
                        <div class="info-item">
                            <i class="fas fa-truck"></i>
                            <span>شحن مجاني للطلبات أكثر من 200 ريال</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-item">
                            <i class="fas fa-undo"></i>
                            <span>إمكانية الإرجاع خلال 14 يوم</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>ضمان الجودة</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-item">
                            <i class="fas fa-headset"></i>
                            <span>دعم فني 24/7</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- منتجات مشابهة -->
<?php if (!empty($related_products) && count($related_products) > 1): ?>
<section class="section mt-5">
    <h2 class="section-title">منتجات مشابهة</h2>
    <div class="products-grid">
        <?php foreach ($related_products as $related_product): ?>
            <?php if ($related_product['id'] != $product['id']): ?>
                <div class="product-card">
                    <?php if ($related_product['discount_price']): ?>
                        <div class="discount-badge">
                            خصم <?php echo round((1 - $related_product['discount_price'] / $related_product['price']) * 100); ?>%
                        </div>
                    <?php endif; ?>
                    
                    <a href="product.php?id=<?php echo $related_product['id']; ?>">
                        <img src="uploads/products/<?php echo $related_product['main_image'] ?: 'default.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($related_product['name']); ?>" 
                             class="product-image">
                    </a>
                    
                    <div class="product-info">
                        <h3 class="product-title">
                            <a href="product.php?id=<?php echo $related_product['id']; ?>">
                                <?php echo htmlspecialchars($related_product['name']); ?>
                            </a>
                        </h3>
                        
                        <div class="product-price">
                            <span class="current-price">
                                <?php echo format_price($related_product['discount_price'] ?: $related_product['price']); ?>
                            </span>
                            <?php if ($related_product['discount_price']): ?>
                                <span class="original-price">
                                    <?php echo format_price($related_product['price']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!is_admin()): ?>
                            <?php if ($related_product['stock_quantity'] > 0): ?>
                                <button class="add-to-cart" data-product-id="<?php echo $related_product['id']; ?>">
                                    <i class="fas fa-cart-plus"></i> أضف إلى السلة
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="fas fa-times"></i> غير متوفر
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>/admin/create-order.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-plus"></i> إنشاء طلب
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<style>
.product-details .current-price {
    font-size: 2rem;
    font-weight: bold;
    color: #ff4757;
}

.product-details .original-price {
    font-size: 1.2rem;
    text-decoration: line-through;
    color: #999;
    margin-right: 1rem;
}

.product-details .discount-percentage {
    display: inline-block;
    background: #ff4757;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 15px;
    font-size: 0.9rem;
    margin-right: 1rem;
}

.thumbnail-img {
    cursor: pointer;
    border: 2px solid transparent;
    transition: border-color 0.3s;
}

.thumbnail-img:hover,
.thumbnail-img.active {
    border-color: #667eea;
}

.info-item {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.info-item i {
    color: #667eea;
    margin-left: 0.5rem;
    width: 20px;
}

#mainImage {
    max-height: 500px;
    object-fit: cover;
    width: 100%;
}
</style>

<script>
function changeMainImage(src) {
    document.getElementById('mainImage').src = src;
    
    // تحديث الصورة النشطة
    document.querySelectorAll('.thumbnail-img').forEach(img => {
        img.classList.remove('active');
    });
    event.target.classList.add('active');
}

function addToCartWithQuantity() {
    const quantity = document.getElementById('quantity').value;
    const productId = <?php echo $product['id']; ?>;
    const button = document.getElementById('add-to-cart-btn');

    // التحقق من صحة الكمية
    if (quantity < 1) {
        showAlert('يرجى إدخال كمية صحيحة', 'danger');
        return;
    }

    // تعطيل الزر لمنع النقر المتكرر
    button.disabled = true;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإضافة...';

    // استدعاء API إضافة إلى السلة
    fetch('ajax/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('تم إضافة المنتج إلى السلة بنجاح', 'success');
            updateCartCount();

            // تأثير بصري للنجاح
            button.style.background = '#28a745';
            button.innerHTML = '<i class="fas fa-check"></i> تم الإضافة';

            setTimeout(() => {
                button.style.background = '';
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        } else {
            showAlert(data.message || 'حدث خطأ أثناء إضافة المنتج', 'danger');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('حدث خطأ في الاتصال', 'danger');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>

<?php
// تضمين التذييل
include 'includes/footer.php';
?>
