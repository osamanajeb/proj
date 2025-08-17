<?php
/**
 * تثبيت البيانات التجريبية المصححة
 * Install corrected sample data
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    echo "بدء تثبيت البيانات التجريبية...\n";
    
    // تنظيف البيانات الموجودة
    echo "تنظيف البيانات الموجودة...\n";
    $db->exec('DELETE FROM product_images');
    $db->exec('DELETE FROM products');
    $db->exec('DELETE FROM categories');
    
    // إعادة تعيين AUTO_INCREMENT
    $db->exec('ALTER TABLE categories AUTO_INCREMENT = 1');
    $db->exec('ALTER TABLE products AUTO_INCREMENT = 1');
    $db->exec('ALTER TABLE product_images AUTO_INCREMENT = 1');
    
    // إدراج الفئات
    echo "إدراج الفئات...\n";
    $categories_sql = "INSERT INTO categories (name, description, image) VALUES
    ('الإلكترونيات', 'أجهزة إلكترونية متنوعة من هواتف ذكية وأجهزة كمبيوتر', 'electronics.jpg'),
    ('الملابس', 'ملابس رجالية ونسائية وأطفال بأحدث الموديلات', 'clothing.jpg'),
    ('المنزل والحديقة', 'أدوات منزلية وديكورات وأثاث ومستلزمات الحديقة', 'home-garden.jpg'),
    ('الرياضة واللياقة', 'معدات رياضية وملابس رياضية ومكملات اللياقة البدنية', 'sports.jpg'),
    ('الكتب والقرطاسية', 'كتب متنوعة وأدوات مكتبية وقرطاسية', 'books.jpg'),
    ('الجمال والعناية', 'منتجات التجميل والعناية الشخصية والعطور', 'beauty.jpg'),
    ('الألعاب والهوايات', 'ألعاب أطفال وألعاب إلكترونية وأدوات الهوايات', 'toys.jpg'),
    ('السيارات والدراجات', 'قطع غيار السيارات وإكسسوارات ودراجات', 'automotive.jpg')";
    
    $db->exec($categories_sql);
    
    // إدراج المنتجات للإلكترونيات
    echo "إدراج منتجات الإلكترونيات...\n";
    $electronics_sql = "INSERT INTO products (name, description, price, discount_price, category_id, main_image, stock_quantity, status, featured) VALUES
    ('هاتف ذكي سامسونج جالاكسي S23', 'هاتف ذكي متطور بكاميرا عالية الدقة وشاشة AMOLED مقاس 6.1 بوصة، معالج Snapdragon 8 Gen 2، ذاكرة 256GB', 3500.00, 3200.00, 1, 'samsung-s23.jpg', 25, 'active', 1),
    ('لابتوب ديل XPS 13', 'لابتوب عالي الأداء بمعالج Intel Core i7 الجيل 12، ذاكرة 16GB RAM، قرص SSD 512GB، شاشة 13.3 بوصة', 4500.00, NULL, 1, 'dell-xps13.jpg', 15, 'active', 1),
    ('سماعات أبل AirPods Pro', 'سماعات لاسلكية بتقنية إلغاء الضوضاء النشط، مقاومة للماء، بطارية تدوم 30 ساعة مع العلبة', 899.00, 799.00, 1, 'airpods-pro.jpg', 50, 'active', 1),
    ('تابلت آيباد Air', 'تابلت بشاشة 10.9 بوصة، معالج M1، ذاكرة 64GB، يدعم Apple Pencil، مثالي للعمل والترفيه', 2200.00, NULL, 1, 'ipad-air.jpg', 20, 'active', 0),
    ('ساعة ذكية أبل واتش Series 8', 'ساعة ذكية بمستشعرات صحية متقدمة، GPS، مقاومة للماء، شاشة Retina دائمة التشغيل', 1599.00, 1399.00, 1, 'apple-watch-s8.jpg', 30, 'active', 1)";
    
    $db->exec($electronics_sql);
    
    // إدراج منتجات الملابس
    echo "إدراج منتجات الملابس...\n";
    $clothing_sql = "INSERT INTO products (name, description, price, discount_price, category_id, main_image, stock_quantity, status, featured) VALUES
    ('قميص رجالي قطني كلاسيكي', 'قميص رجالي أنيق من القطن الخالص، متوفر بألوان متعددة، مناسب للعمل والمناسبات', 120.00, 99.00, 2, 'mens-shirt.jpg', 100, 'active', 0),
    ('فستان نسائي صيفي', 'فستان نسائي أنيق بتصميم عصري، قماش خفيف ومريح، مثالي للصيف والمناسبات الكاجوال', 180.00, NULL, 2, 'summer-dress.jpg', 75, 'active', 1),
    ('جينز رجالي كلاسيكي', 'بنطلون جينز رجالي بقصة كلاسيكية مريحة، قماش عالي الجودة، متوفر بمقاسات متعددة', 250.00, 199.00, 2, 'mens-jeans.jpg', 80, 'active', 0),
    ('حقيبة يد نسائية جلدية', 'حقيبة يد أنيقة من الجلد الطبيعي، تصميم عملي وأنيق، مناسبة للعمل والخروج', 320.00, 280.00, 2, 'leather-handbag.jpg', 40, 'active', 1),
    ('حذاء رياضي للجري', 'حذاء رياضي مريح للجري والمشي، تقنية امتصاص الصدمات، خامات عالية الجودة', 299.00, NULL, 2, 'running-shoes.jpg', 60, 'active', 0)";
    
    $db->exec($clothing_sql);
    
    // إدراج منتجات المنزل والحديقة
    echo "إدراج منتجات المنزل والحديقة...\n";
    $home_sql = "INSERT INTO products (name, description, price, discount_price, category_id, main_image, stock_quantity, status, featured) VALUES
    ('طقم أواني طبخ ستانلس ستيل', 'طقم أواني طبخ من الستانلس ستيل عالي الجودة، 12 قطعة، مناسب لجميع أنواع المواقد', 450.00, 399.00, 3, 'cookware-set.jpg', 35, 'active', 1),
    ('مكنسة كهربائية لاسلكية', 'مكنسة كهربائية لاسلكية قوية، بطارية تدوم 45 دقيقة، خفيفة الوزن وسهلة الاستخدام', 380.00, NULL, 3, 'vacuum-cleaner.jpg', 25, 'active', 0),
    ('طقم أثاث حديقة', 'طقم أثاث حديقة من الخشب المعالج، يتضمن طاولة و4 كراسي، مقاوم للعوامل الجوية', 1200.00, 999.00, 3, 'garden-furniture.jpg', 12, 'active', 1),
    ('مصباح LED ذكي', 'مصباح LED ذكي قابل للتحكم عبر التطبيق، ألوان متعددة، توفير في الطاقة', 85.00, 69.00, 3, 'smart-led.jpg', 150, 'active', 0),
    ('مرآة حمام بإضاءة LED', 'مرآة حمام أنيقة بإضاءة LED مدمجة، مقاومة للرطوبة، تصميم عصري', 220.00, NULL, 3, 'bathroom-mirror.jpg', 30, 'active', 0)";

    $db->exec($home_sql);

    // إدراج منتجات الرياضة واللياقة
    echo "إدراج منتجات الرياضة واللياقة...\n";
    $sports_sql = "INSERT INTO products (name, description, price, discount_price, category_id, main_image, stock_quantity, status, featured) VALUES
    ('دراجة هوائية جبلية', 'دراجة هوائية جبلية بـ 21 سرعة، إطار من الألومنيوم، مناسبة للطرق الوعرة والمدينة', 850.00, 750.00, 4, 'mountain-bike.jpg', 18, 'active', 1),
    ('أوزان حديدية قابلة للتعديل', 'طقم أوزان حديدية قابلة للتعديل من 5 إلى 25 كيلو، مثالية للتمارين المنزلية', 320.00, NULL, 4, 'adjustable-weights.jpg', 45, 'active', 0),
    ('حصيرة يوغا مطاطية', 'حصيرة يوغا عالية الجودة، مقاومة للانزلاق، سماكة 6 مم، مع حقيبة حمل', 95.00, 79.00, 4, 'yoga-mat.jpg', 80, 'active', 0),
    ('ساعة رياضية ذكية', 'ساعة رياضية ذكية بمراقب معدل ضربات القلب، GPS، مقاومة للماء، بطارية تدوم أسبوع', 299.00, 249.00, 4, 'fitness-watch.jpg', 55, 'active', 1),
    ('كرة قدم جلدية أصلية', 'كرة قدم من الجلد الطبيعي، مطابقة للمواصفات الدولية، مناسبة للمحترفين والهواة', 120.00, NULL, 4, 'football.jpg', 90, 'active', 0)";

    $db->exec($sports_sql);

    // إدراج منتجات الكتب والقرطاسية
    echo "إدراج منتجات الكتب والقرطاسية...\n";
    $books_sql = "INSERT INTO products (name, description, price, discount_price, category_id, main_image, stock_quantity, status, featured) VALUES
    ('كتاب فن إدارة الوقت', 'كتاب مفيد عن إدارة الوقت وزيادة الإنتاجية، مترجم للعربية، 300 صفحة', 45.00, 39.00, 5, 'time-management-book.jpg', 200, 'active', 0),
    ('دفتر ملاحظات جلدي فاخر', 'دفتر ملاحظات أنيق بغلاف جلدي، 200 صفحة، مناسب للاجتماعات والملاحظات المهمة', 85.00, NULL, 5, 'leather-notebook.jpg', 120, 'active', 1),
    ('قلم حبر فاخر', 'قلم حبر أنيق بتصميم كلاسيكي، مناسب للهدايا والاستخدام الرسمي', 150.00, 129.00, 5, 'luxury-pen.jpg', 60, 'active', 0),
    ('آلة حاسبة علمية', 'آلة حاسبة علمية متقدمة للطلاب والمهندسين، شاشة كبيرة وواضحة', 89.00, NULL, 5, 'calculator.jpg', 100, 'active', 0),
    ('كتاب الطبخ العربي', 'كتاب شامل للطبخ العربي التقليدي، أكثر من 200 وصفة مع الصور', 65.00, 55.00, 5, 'arabic-cookbook.jpg', 80, 'active', 1)";

    $db->exec($books_sql);

    // التحقق من النتائج النهائية
    $result = $db->query('SELECT COUNT(*) as count FROM categories');
    $categories_count = $result->fetch()['count'];
    echo "عدد الفئات المثبتة: $categories_count\n";

    $result = $db->query('SELECT COUNT(*) as count FROM products');
    $products_count = $result->fetch()['count'];
    echo "إجمالي عدد المنتجات المثبتة: $products_count\n";

    echo "تم تثبيت جميع البيانات التجريبية بنجاح!\n";
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
}
?>
