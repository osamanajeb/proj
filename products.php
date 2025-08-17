<?php
/**
 * صفحة عرض المنتجات
 * Products listing page
 */

$page_title = 'المنتجات';
$page_description = 'تصفح جميع المنتجات المتاحة في متجرنا';

require_once 'config/config.php';
require_once 'classes/Product.php';
require_once 'classes/Category.php';

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائنات الفئات
$productObj = new Product($db);
$categoryObj = new Category($db);

// معاملات البحث والتصفية
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search_query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$featured = isset($_GET['featured']) ? true : false;

// إعدادات الصفحات
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$products_per_page = 12;
$offset = ($page - 1) * $products_per_page;

// الحصول على المنتجات
if ($search_query) {
    $products = $productObj->searchProducts($search_query, $category_id, $min_price, $max_price);
    $page_title = 'نتائج البحث عن: ' . $search_query;
} elseif ($category_id) {
    $products = $productObj->getProductsByCategory($category_id);
    $category = $categoryObj->getCategoryById($category_id);
    $page_title = $category ? $category['name'] : 'المنتجات';
} elseif ($featured) {
    $products = $productObj->getFeaturedProducts();
    $page_title = 'المنتجات المميزة';
} else {
    $products = $productObj->getAllProducts($products_per_page, $offset);
}

// الحصول على الفئات للتصفية
$categories = $categoryObj->getAllCategories();

// تضمين الرأس
include 'includes/header.php';
?>

<div class="row">
    <!-- شريط التصفية الجانبي -->
    <div class="col-lg-3 col-md-4 mb-4">
        <div class="filter-sidebar">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-filter"></i> تصفية المنتجات</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="products.php">
                        <!-- البحث -->
                        <div class="mb-3">
                            <label class="form-label">البحث</label>
                            <input type="text" name="q" class="form-control" 
                                   value="<?php echo htmlspecialchars($search_query); ?>" 
                                   placeholder="ابحث عن المنتجات...">
                        </div>
                        
                        <!-- الفئات -->
                        <div class="mb-3">
                            <label class="form-label">الفئة</label>
                            <select name="category" class="form-select">
                                <option value="">جميع الفئات</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
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
                                           value="<?php echo $min_price; ?>" 
                                           placeholder="من" min="0" step="0.01">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="max_price" class="form-control" 
                                           value="<?php echo $max_price; ?>" 
                                           placeholder="إلى" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        
                        <!-- المنتجات المميزة -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="featured" value="1" class="form-check-input" 
                                       <?php echo $featured ? 'checked' : ''; ?>>
                                <label class="form-check-label">المنتجات المميزة فقط</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> تطبيق التصفية
                        </button>
                        
                        <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="fas fa-times"></i> إزالة التصفية
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- المنتجات -->
    <div class="col-lg-9 col-md-8">
        <!-- رأس الصفحة -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo $page_title; ?></h1>
            <div class="products-count">
                <span class="badge bg-primary"><?php echo count($products); ?> منتج</span>
            </div>
        </div>
        
        <?php if (empty($products)): ?>
            <!-- لا توجد منتجات -->
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-5x text-muted mb-3"></i>
                <h3>لا توجد منتجات</h3>
                <p class="text-muted">لم يتم العثور على منتجات تطابق معايير البحث الخاصة بك.</p>
                <a href="products.php" class="btn btn-primary">عرض جميع المنتجات</a>
            </div>
        <?php else: ?>
            <!-- شبكة المنتجات -->
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <?php if ($product['discount_price']): ?>
                            <div class="discount-badge">
                                خصم <?php echo round((1 - $product['discount_price'] / $product['price']) * 100); ?>%
                            </div>
                        <?php endif; ?>
                        
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                            <img src="uploads/<?php echo $product['main_image'] ?: 'default.jpg'; ?>"
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
                                        <i class="fas fa-check"></i> متوفر (<?php echo $product['stock_quantity']; ?>)
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
        <?php endif; ?>
    </div>
</div>

<style>
.filter-sidebar .card {
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-radius: 15px;
}

.filter-sidebar .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    border: none;
}

.product-category {
    margin-bottom: 0.5rem;
}

.product-stock {
    margin-bottom: 1rem;
}

.products-count .badge {
    font-size: 0.9rem;
}
</style>

<?php
// تضمين التذييل
include 'includes/footer.php';
?>
