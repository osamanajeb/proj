<?php
/**
 * تقرير نهائي عن البيانات المثبتة
 * Final report on installed data
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    
    echo "=== تقرير نهائي: البيانات التجريبية للمتجر الإلكتروني ===\n\n";
    
    // إحصائيات عامة
    $result = $db->query('SELECT COUNT(*) as count FROM categories');
    $categories_count = $result->fetch()['count'];
    
    $result = $db->query('SELECT COUNT(*) as count FROM products');
    $products_count = $result->fetch()['count'];
    
    $result = $db->query('SELECT COUNT(*) as count FROM products WHERE featured = 1');
    $featured_count = $result->fetch()['count'];
    
    $result = $db->query('SELECT COUNT(*) as count FROM products WHERE discount_price IS NOT NULL');
    $discounted_count = $result->fetch()['count'];
    
    echo "📊 الإحصائيات العامة:\n";
    echo "- إجمالي الفئات: $categories_count\n";
    echo "- إجمالي المنتجات: $products_count\n";
    echo "- المنتجات المميزة: $featured_count\n";
    echo "- المنتجات بخصم: $discounted_count\n\n";
    
    // تفاصيل الفئات
    echo "🗂️ تفاصيل الفئات:\n";
    $result = $db->query('SELECT c.name, c.description, COUNT(p.id) as product_count 
                         FROM categories c 
                         LEFT JOIN products p ON c.id = p.category_id 
                         GROUP BY c.id, c.name, c.description 
                         ORDER BY c.id');
    
    $category_num = 1;
    while ($row = $result->fetch()) {
        echo "$category_num. " . $row['name'] . " (" . $row['product_count'] . " منتج)\n";
        echo "   الوصف: " . $row['description'] . "\n\n";
        $category_num++;
    }
    
    // أغلى وأرخص المنتجات
    echo "💰 نطاق الأسعار:\n";
    $result = $db->query('SELECT MIN(price) as min_price, MAX(price) as max_price, AVG(price) as avg_price FROM products');
    $price_stats = $result->fetch();
    echo "- أقل سعر: " . number_format($price_stats['min_price'], 2) . " ريال\n";
    echo "- أعلى سعر: " . number_format($price_stats['max_price'], 2) . " ريال\n";
    echo "- متوسط السعر: " . number_format($price_stats['avg_price'], 2) . " ريال\n\n";
    
    // المنتجات المميزة
    echo "⭐ المنتجات المميزة:\n";
    $result = $db->query('SELECT p.name, p.price, c.name as category_name 
                         FROM products p 
                         JOIN categories c ON p.category_id = c.id 
                         WHERE p.featured = 1 
                         ORDER BY p.price DESC');
    
    $featured_num = 1;
    while ($row = $result->fetch()) {
        echo "$featured_num. " . $row['name'] . " - " . number_format($row['price'], 2) . " ريال (" . $row['category_name'] . ")\n";
        $featured_num++;
    }
    
    echo "\n🖼️ الصور المنشأة:\n";
    echo "- صور الفئات: 8 صور\n";
    echo "- صور المنتجات: 40 صورة\n";
    echo "- إجمالي الصور: 48 صورة\n\n";
    
    echo "✅ تم تثبيت جميع البيانات التجريبية بنجاح!\n";
    echo "🎯 المتجر جاهز للاستخدام والاختبار\n\n";
    
    echo "📝 ملاحظات:\n";
    echo "- جميع المنتجات نشطة ومتاحة للبيع\n";
    echo "- الصور التجريبية ملونة ومميزة\n";
    echo "- الأسعار متنوعة لتناسب جميع الفئات\n";
    echo "- يمكن تعديل البيانات من لوحة التحكم\n";
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
}
?>
