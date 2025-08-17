<?php
/**
 * صفحة البحث
 * Search page
 */

require_once 'config/config.php';
require_once 'classes/Product.php';
require_once 'classes/Category.php';

// الحصول على كلمة البحث
$search_query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';

if (empty($search_query)) {
    redirect(SITE_URL . '/products.php');
}

$page_title = 'نتائج البحث عن: ' . $search_query;
$page_description = 'نتائج البحث في المتجر';

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائنات الفئات
$productObj = new Product($db);
$categoryObj = new Category($db);

// البحث في المنتجات
$products = $productObj->searchProducts($search_query);

// الحصول على الفئات للتصفية
$categories = $categoryObj->getAllCategories();

// تضمين الرأس
include 'includes/header.php';
?>

<div class="search-results">
    <div class="search-header mb-4">
        <h1>نتائج البحث عن: "<?php echo htmlspecialchars($search_query); ?>"</h1>
        <p class="text-muted">تم العثور على <?php echo count($products); ?> منتج</p>
    </div>
    
    <?php if (empty($products)): ?>
        <!-- لا توجد نتائج -->
        <div class="no-results text-center py-5">
            <i class="fas fa-search fa-5x text-muted mb-3"></i>
            <h3>لم يتم العثور على نتائج</h3>
            <p class="text-muted">لم نجد أي منتجات تطابق كلمة البحث "<?php echo htmlspecialchars($search_query); ?>"</p>
            
            <div class="search-suggestions mt-4">
                <h5>اقتراحات للبحث:</h5>
                <ul class="list-unstyled">
                    <li>• تأكد من كتابة الكلمات بشكل صحيح</li>
                    <li>• جرب كلمات مختلفة أو أكثر عمومية</li>
                    <li>• استخدم كلمات أقل في البحث</li>
                </ul>
            </div>
            
            <div class="mt-4">
                <a href="products.php" class="btn btn-primary">تصفح جميع المنتجات</a>
            </div>
        </div>
        
        <!-- اقتراحات المنتجات -->
        <?php
        $suggested_products = $productObj->getFeaturedProducts(4);
        if (!empty($suggested_products)):
        ?>
        <section class="suggested-products mt-5">
            <h3>منتجات قد تعجبك</h3>
            <div class="products-grid">
                <?php foreach ($suggested_products as $product): ?>
                    <div class="product-card">
                        <?php if ($product['discount_price']): ?>
                            <div class="discount-badge">
                                خصم <?php echo round((1 - $product['discount_price'] / $product['price']) * 100); ?>%
                            </div>
                        <?php endif; ?>
                        
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                            <img src="uploads/products/<?php echo $product['main_image'] ?: 'default.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-image">
                        </a>
                        
                        <div class="product-info">
                            <h3 class="product-title">
                                <a href="product.php?id=<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            
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
        </section>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- نتائج البحث -->
        <div class="row">
            <!-- شريط التصفية الجانبي -->
            <div class="col-lg-3 col-md-4 mb-4">
                <div class="filter-sidebar">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-filter"></i> تصفية النتائج</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="search.php">
                                <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                                
                                <!-- الفئات -->
                                <div class="mb-3">
                                    <label class="form-label">الفئة</label>
                                    <select name="category" class="form-select">
                                        <option value="">جميع الفئات</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" 
                                                    <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>>
                                                <?php echo $cat['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- نطاق السعر -->
                                <div class="mb-3">
                                    <label class="form-label">نطاق السعر</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <input type="number" name="min_price" class="form-control" 
                                                   value="<?php echo isset($_GET['min_price']) ? $_GET['min_price'] : ''; ?>" 
                                                   placeholder="من" min="0" step="0.01">
                                        </div>
                                        <div class="col-6">
                                            <input type="number" name="max_price" class="form-control" 
                                                   value="<?php echo isset($_GET['max_price']) ? $_GET['max_price'] : ''; ?>" 
                                                   placeholder="إلى" min="0" step="0.01">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> تطبيق التصفية
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- النتائج -->
            <div class="col-lg-9 col-md-8">
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <?php if ($product['discount_price']): ?>
                                <div class="discount-badge">
                                    خصم <?php echo round((1 - $product['discount_price'] / $product['price']) * 100); ?>%
                                </div>
                            <?php endif; ?>
                            
                            <a href="product.php?id=<?php echo $product['id']; ?>">
                                <img src="uploads/products/<?php echo $product['main_image'] ?: 'default.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="product-image">
                            </a>
                            
                            <div class="product-info">
                                <h3 class="product-title">
                                    <a href="product.php?id=<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                                
                                <p class="product-description">
                                    <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                                </p>
                                
                                <div class="product-category">
                                    <small class="text-muted">
                                        <i class="fas fa-tag"></i> <?php echo $product['category_name']; ?>
                                    </small>
                                </div>
                                
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
                                
                                <div class="product-stock">
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <small class="text-success">
                                            <i class="fas fa-check"></i> متوفر
                                        </small>
                                    <?php else: ?>
                                        <small class="text-danger">
                                            <i class="fas fa-times"></i> غير متوفر
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-cart-plus"></i> أضف إلى السلة
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="fas fa-times"></i> غير متوفر
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.search-header h1 {
    color: #333;
    margin-bottom: 0.5rem;
}

.no-results {
    background: white;
    border-radius: 15px;
    padding: 3rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.search-suggestions {
    text-align: right;
}

.search-suggestions ul li {
    margin-bottom: 0.5rem;
    color: #666;
}

.suggested-products {
    margin-top: 3rem;
}

.suggested-products h3 {
    text-align: center;
    margin-bottom: 2rem;
    color: #333;
}
</style>

<?php
// تضمين التذييل
include 'includes/footer.php';
?>
