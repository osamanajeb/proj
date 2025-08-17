<?php
/**
 * إضافة باقي المنتجات للفئات المتبقية
 * Add remaining products for remaining categories
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    echo "إضافة باقي المنتجات...\n";
    
    // إدراج منتجات الجمال والعناية
    echo "إدراج منتجات الجمال والعناية...\n";
    $beauty_sql = "INSERT INTO products (name, description, price, discount_price, category_id, main_image, stock_quantity, status, featured) VALUES
    ('عطر رجالي فاخر', 'عطر رجالي بتركيبة فاخرة ورائحة مميزة تدوم طويلاً، حجم 100 مل', 250.00, 199.00, 6, 'mens-perfume.jpg', 70, 'active', 1),
    ('كريم الوجه المرطب', 'كريم مرطب للوجه بمكونات طبيعية، مناسب لجميع أنواع البشرة', 89.00, NULL, 6, 'face-cream.jpg', 120, 'active', 0),
    ('شامبو طبيعي للشعر', 'شامبو طبيعي خالي من الكبريتات، يغذي الشعر ويقويه', 45.00, 39.00, 6, 'natural-shampoo.jpg', 150, 'active', 0),
    ('طقم مكياج كامل', 'طقم مكياج شامل يحتوي على جميع الأساسيات، ألوان متنوعة وجودة عالية', 180.00, 149.00, 6, 'makeup-set.jpg', 60, 'active', 1),
    ('فرشاة تنظيف الوجه', 'فرشاة إلكترونية لتنظيف الوجه بعمق، مقاومة للماء، قابلة للشحن', 120.00, NULL, 6, 'face-brush.jpg', 80, 'active', 0)";
    
    $db->exec($beauty_sql);
    
    // إدراج منتجات الألعاب والهوايات
    echo "إدراج منتجات الألعاب والهوايات...\n";
    $toys_sql = "INSERT INTO products (name, description, price, discount_price, category_id, main_image, stock_quantity, status, featured) VALUES
    ('مجموعة ليغو المدينة', 'مجموعة ليغو لبناء مدينة كاملة، أكثر من 500 قطعة، مناسبة للأطفال من 6 سنوات', 299.00, 249.00, 7, 'lego-city.jpg', 40, 'active', 1),
    ('دراجة أطفال ملونة', 'دراجة أطفال آمنة وملونة، مع عجلات مساعدة، مناسبة للأعمار 3-7 سنوات', 180.00, NULL, 7, 'kids-bike.jpg', 35, 'active', 0),
    ('لوحة رسم إلكترونية', 'لوحة رسم إلكترونية للأطفال، شاشة LCD، قلم مرفق، تنمي الإبداع', 85.00, 69.00, 7, 'drawing-tablet.jpg', 90, 'active', 1),
    ('ألوان مائية احترافية', 'طقم ألوان مائية احترافي، 24 لون، فرش متنوعة، مناسب للفنانين والهواة', 65.00, NULL, 7, 'watercolors.jpg', 100, 'active', 0),
    ('لعبة شطرنج خشبية', 'لعبة شطرنج كلاسيكية من الخشب الطبيعي، قطع منحوتة بدقة، لوحة قابلة للطي', 120.00, 99.00, 7, 'chess-set.jpg', 50, 'active', 0)";
    
    $db->exec($toys_sql);
    
    // إدراج منتجات السيارات والدراجات
    echo "إدراج منتجات السيارات والدراجات...\n";
    $automotive_sql = "INSERT INTO products (name, description, price, discount_price, category_id, main_image, stock_quantity, status, featured) VALUES
    ('إطارات سيارة عالية الجودة', 'إطارات سيارة متينة ومقاومة للتآكل، مناسبة لجميع الطرق، مقاس 195/65 R15', 320.00, 280.00, 8, 'car-tires.jpg', 25, 'active', 1),
    ('زيت محرك صناعي', 'زيت محرك صناعي عالي الجودة، يحمي المحرك ويطيل عمره، 4 لتر', 89.00, NULL, 8, 'engine-oil.jpg', 100, 'active', 0),
    ('مشغل موسيقى للسيارة', 'مشغل موسيقى حديث للسيارة، بلوتوث، USB، شاشة LCD، صوت عالي الجودة', 199.00, 169.00, 8, 'car-stereo.jpg', 45, 'active', 1),
    ('خوذة دراجة نارية', 'خوذة أمان للدراجات النارية، معتمدة دولياً، تصميم أنيق ومريح', 150.00, NULL, 8, 'motorcycle-helmet.jpg', 60, 'active', 0),
    ('طقم أدوات إصلاح السيارات', 'طقم شامل لأدوات إصلاح السيارات، 50 قطعة، جودة عالية، حقيبة منظمة', 220.00, 189.00, 8, 'repair-tools.jpg', 30, 'active', 0)";
    
    $db->exec($automotive_sql);
    
    // التحقق من النتائج النهائية
    $result = $db->query('SELECT COUNT(*) as count FROM categories');
    $categories_count = $result->fetch()['count'];
    echo "عدد الفئات: $categories_count\n";
    
    $result = $db->query('SELECT COUNT(*) as count FROM products');
    $products_count = $result->fetch()['count'];
    echo "إجمالي عدد المنتجات: $products_count\n";
    
    // عرض إحصائيات لكل فئة
    echo "\nإحصائيات المنتجات لكل فئة:\n";
    $result = $db->query('SELECT c.name, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id, c.name');
    while ($row = $result->fetch()) {
        echo "- " . $row['name'] . ": " . $row['product_count'] . " منتج\n";
    }
    
    echo "\nتم تثبيت جميع البيانات التجريبية بنجاح! 🎉\n";
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
}
?>
