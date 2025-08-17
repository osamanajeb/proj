<?php
/**
 * الصفحة الرئيسية للمتجر الإلكتروني
 * Main homepage for the e-commerce store
 */

$page_title = 'الرئيسية';
$page_description = 'متجر إلكتروني عربي متكامل - تسوق أفضل المنتجات بأسعار منافسة';

require_once 'config/config.php';
require_once 'classes/Product.php';
require_once 'classes/Category.php';

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائنات الفئات
$productObj = new Product($db);
$categoryObj = new Category($db);

// الحصول على المنتجات المميزة
$featured_products = $productObj->getFeaturedProducts(8);

// الحصول على أحدث المنتجات
$latest_products = $productObj->getAllProducts(8);

// الحصول على الفئات
$categories = $categoryObj->getAllCategories();

// تضمين الرأس
include 'includes/header.php';
?>

<!-- البانر الرئيسي -->
<section class="hero-banner">
    <div class="hero-content">
        <h1 class="hero-title">مرحباً بك في <?php echo get_site_name(); ?></h1>
        <p class="hero-subtitle">اكتشف أفضل المنتجات بأسعار لا تُقاوم</p>
        <a href="products.php" class="btn btn-primary">تسوق الآن</a>
    </div>
</section>

<!-- الفئات الرئيسية -->
<section class="section">
    <h2 class="section-title">تسوق حسب الفئة</h2>
    <div class="categories-grid">
        <?php foreach (array_slice($categories, 0, 6) as $category): ?>
            <a href="products.php?category=<?php echo $category['id']; ?>" class="category-card">
                <div class="category-icon">
                    <?php
                    // أيقونات الفئات
                    $icons = [
                        'الإلكترونيات' => 'fas fa-laptop',
                        'الملابس' => 'fas fa-tshirt',
                        'المنزل والحديقة' => 'fas fa-home',
                        'الكتب' => 'fas fa-book',
                        'الرياضة' => 'fas fa-dumbbell',
                        'الجمال' => 'fas fa-heart'
                    ];
                    $icon = $icons[$category['name']] ?? 'fas fa-box';
                    ?>
                    <i class="<?php echo $icon; ?>"></i>
                </div>
                <h3><?php echo $category['name']; ?></h3>
                <p><?php echo $categoryObj->getProductsCount($category['id']); ?> منتج</p>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- المنتجات المميزة -->
<?php if (!empty($featured_products)): ?>
<section class="section">
    <h2 class="section-title">المنتجات المميزة</h2>
    <div class="products-grid">
        <?php foreach ($featured_products as $product): ?>
            <div class="product-card">
                <?php if ($product['discount_price']): ?>
                    <div class="discount-badge">
                        خصم <?php echo round((1 - $product['discount_price'] / $product['price']) * 100); ?>%
                    </div>
                <?php endif; ?>
                
                <img src="uploads/products/<?php echo $product['main_image'] ?: 'default.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="product-image">
                
                <div class="product-info">
                    <h3 class="product-title">
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h3>
                    <p class="product-description">
                        <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                    </p>
                    
                    <div class="product-price">
                        <span class="current-price">
                            <?php echo format_price($product['discount_price'] ?: $product['price']); ?>
                        </span>
                        <?php if ($product['discount_price']): ?>
                            <span class="original-price">
                                <?php echo format_price($product['price']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                        <i class="fas fa-cart-plus"></i> أضف إلى السلة
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-4">
        <a href="products.php?featured=1" class="btn btn-secondary">عرض جميع المنتجات المميزة</a>
    </div>
</section>
<?php endif; ?>

<!-- أحدث المنتجات -->
<?php if (!empty($latest_products)): ?>
<section class="section">
    <h2 class="section-title">أحدث المنتجات</h2>
    <div class="products-grid">
        <?php foreach ($latest_products as $product): ?>
            <div class="product-card">
                <?php if ($product['discount_price']): ?>
                    <div class="discount-badge">
                        خصم <?php echo round((1 - $product['discount_price'] / $product['price']) * 100); ?>%
                    </div>
                <?php endif; ?>
                
                <img src="uploads/products/<?php echo $product['main_image'] ?: 'default.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="product-image">
                
                <div class="product-info">
                    <h3 class="product-title">
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h3>
                    <p class="product-description">
                        <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                    </p>
                    
                    <div class="product-price">
                        <span class="current-price">
                            <?php echo format_price($product['discount_price'] ?: $product['price']); ?>
                        </span>
                        <?php if ($product['discount_price']): ?>
                            <span class="original-price">
                                <?php echo format_price($product['price']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                        <i class="fas fa-cart-plus"></i> أضف إلى السلة
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-4">
        <a href="products.php" class="btn btn-secondary">عرض جميع المنتجات</a>
    </div>
</section>
<?php endif; ?>

<!-- مميزات المتجر -->
<section class="section">
    <h2 class="section-title">لماذا تختارنا؟</h2>
    <div class="row">
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="feature-card text-center">
                <div class="feature-icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <h4>شحن سريع</h4>
                <p>توصيل سريع وآمن لجميع أنحاء المملكة</p>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="feature-card text-center">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h4>دفع آمن</h4>
                <p>طرق دفع متعددة وآمنة 100%</p>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="feature-card text-center">
                <div class="feature-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <h4>إرجاع مجاني</h4>
                <p>إمكانية الإرجاع خلال 14 يوم</p>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="feature-card text-center">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h4>دعم 24/7</h4>
                <p>خدمة عملاء متاحة على مدار الساعة</p>
            </div>
        </div>
    </div>
</section>

<style>
.feature-card {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s;
    height: 100%;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-icon {
    font-size: 3rem;
    color: #667eea;
    margin-bottom: 1rem;
}

.feature-card h4 {
    color: #333;
    margin-bottom: 1rem;
}

.feature-card p {
    color: #666;
    margin: 0;
}
</style>

<?php
// تضمين التذييل
include 'includes/footer.php';
?>
