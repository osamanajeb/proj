<?php
/**
 * صفحة عرض الفئة
 * Category page
 */

require_once 'config/config.php';
require_once 'classes/Category.php';
require_once 'classes/Product.php';

// التحقق من وجود معرف الفئة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect(SITE_URL . '/index.php');
}

$category_id = (int)$_GET['id'];

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائنات الفئات والمنتجات
$categoryObj = new Category($db);
$productObj = new Product($db);

// الحصول على بيانات الفئة
$category = $categoryObj->getCategoryById($category_id);

if (!$category) {
    $_SESSION['message'] = ['text' => 'الفئة غير موجودة', 'type' => 'error'];
    redirect(SITE_URL . '/index.php');
}

// الحصول على منتجات الفئة
$products = $productObj->getProductsByCategory($category_id);

// إعداد معلومات الصفحة
$page_title = $category['name'];
$page_description = !empty($category['description']) ? $category['description'] : 'تصفح منتجات ' . $category['name'];

include 'includes/header.php';
?>

<div class="category-page">
    <!-- رأس الفئة -->
    <div class="category-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">الرئيسية</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($category['name']); ?></li>
                        </ol>
                    </nav>
                    
                    <h1 class="category-title">
                        <i class="fas fa-tag"></i>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </h1>
                    
                    <?php if (!empty($category['description'])): ?>
                        <p class="category-description">
                            <?php echo htmlspecialchars($category['description']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="category-stats">
                        <span class="badge bg-primary">
                            <i class="fas fa-box"></i>
                            <?php echo count($products); ?> منتج
                        </span>
                    </div>
                </div>
                
                <?php if (!empty($category['image'])): ?>
                    <div class="col-md-4 text-center">
                        <div class="category-image">
                            <img src="<?php echo SITE_URL; ?>/uploads/categories/<?php echo $category['image']; ?>" 
                                 alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                 class="img-fluid rounded">
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- منتجات الفئة -->
    <div class="category-products">
        <div class="container">
            <?php if (empty($products)): ?>
                <div class="empty-category">
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <h3>لا توجد منتجات في هذه الفئة</h3>
                        <p class="text-muted">سيتم إضافة منتجات جديدة قريباً</p>
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                            <i class="fas fa-home"></i> العودة للرئيسية
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- فلاتر وترتيب -->
                <div class="products-controls mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4>منتجات <?php echo htmlspecialchars($category['name']); ?></h4>
                            <p class="text-muted mb-0">عرض <?php echo count($products); ?> منتج</p>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end">
                                <select class="form-select" id="sortProducts" style="max-width: 200px;">
                                    <option value="newest">الأحدث</option>
                                    <option value="price_low">السعر: من الأقل للأعلى</option>
                                    <option value="price_high">السعر: من الأعلى للأقل</option>
                                    <option value="name">الاسم: أ-ي</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- شبكة المنتجات -->
                <div class="products-grid" id="productsGrid">
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4 product-item" 
                                 data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                 data-price="<?php echo $product['price']; ?>"
                                 data-date="<?php echo strtotime($product['created_at']); ?>">
                                <div class="product-card">
                                    <div class="product-image">
                                        <?php if (!empty($product['main_image'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $product['main_image']; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="img-fluid">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($product['discount_price']): ?>
                                            <div class="discount-badge">
                                                <?php echo round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>% خصم
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="product-overlay">
                                            <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $product['id']; ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> عرض
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="product-info">
                                        <h5 class="product-name">
                                            <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $product['id']; ?>">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </a>
                                        </h5>
                                        
                                        <div class="product-price">
                                            <?php if ($product['discount_price']): ?>
                                                <span class="current-price"><?php echo number_format($product['discount_price'], 2); ?> ر.س</span>
                                                <span class="original-price"><?php echo number_format($product['price'], 2); ?> ر.س</span>
                                            <?php else: ?>
                                                <span class="current-price"><?php echo number_format($product['price'], 2); ?> ر.س</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="product-actions">
                                            <button class="btn btn-primary btn-sm add-to-cart" 
                                                    data-product-id="<?php echo $product['id']; ?>">
                                                <i class="fas fa-shopping-cart"></i> أضف للسلة
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm add-to-wishlist" 
                                                    data-product-id="<?php echo $product['id']; ?>">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.category-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
}

.breadcrumb {
    background: none;
    padding: 0;
    margin-bottom: 1rem;
}

.breadcrumb-item a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
}

.breadcrumb-item.active {
    color: white;
}

.category-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.category-description {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 1rem;
}

.category-stats .badge {
    font-size: 1rem;
    padding: 0.5rem 1rem;
}

.category-image img {
    max-height: 200px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.products-controls {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.product-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    height: 100%;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
}

.product-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.product-card:hover .product-image img {
    transform: scale(1.1);
}

.no-image {
    width: 100%;
    height: 100%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #dee2e6;
    font-size: 3rem;
}

.discount-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #dc3545;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: bold;
}

.product-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.product-card:hover .product-overlay {
    opacity: 1;
}

.product-info {
    padding: 1.5rem;
}

.product-name a {
    color: #333;
    text-decoration: none;
    font-weight: 600;
}

.product-name a:hover {
    color: #667eea;
}

.product-price {
    margin: 1rem 0;
}

.current-price {
    font-size: 1.2rem;
    font-weight: bold;
    color: #28a745;
}

.original-price {
    font-size: 1rem;
    color: #6c757d;
    text-decoration: line-through;
    margin-right: 0.5rem;
}

.product-actions {
    display: flex;
    gap: 0.5rem;
}

.product-actions .btn {
    flex: 1;
}

.empty-category {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}
</style>

<script>
// ترتيب المنتجات
document.getElementById('sortProducts').addEventListener('change', function() {
    const sortBy = this.value;
    const grid = document.getElementById('productsGrid');
    const products = Array.from(grid.querySelectorAll('.product-item'));
    
    products.sort((a, b) => {
        switch(sortBy) {
            case 'price_low':
                return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
            case 'price_high':
                return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
            case 'name':
                return a.dataset.name.localeCompare(b.dataset.name, 'ar');
            case 'newest':
            default:
                return parseInt(b.dataset.date) - parseInt(a.dataset.date);
        }
    });
    
    const row = grid.querySelector('.row');
    row.innerHTML = '';
    products.forEach(product => row.appendChild(product));
});
</script>

<?php include 'includes/footer.php'; ?>
