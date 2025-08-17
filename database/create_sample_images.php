<?php
/**
 * إنشاء صور تجريبية للمنتجات والفئات
 * Create sample images for products and categories
 */

require_once __DIR__ . '/../config/config.php';

// التأكد من وجود مجلد الصور
$upload_dir = __DIR__ . '/../uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// دالة لإنشاء صورة تجريبية
function createSampleImage($filename, $text, $width = 400, $height = 300, $bg_color = null) {
    global $upload_dir;
    
    // إنشاء صورة جديدة
    $image = imagecreate($width, $height);
    
    // تحديد الألوان
    if ($bg_color) {
        $background = imagecolorallocate($image, $bg_color[0], $bg_color[1], $bg_color[2]);
    } else {
        // ألوان عشوائية جميلة
        $colors = [
            [52, 152, 219],   // أزرق
            [46, 204, 113],   // أخضر
            [155, 89, 182],   // بنفسجي
            [241, 196, 15],   // أصفر
            [230, 126, 34],   // برتقالي
            [231, 76, 60],    // أحمر
            [149, 165, 166],  // رمادي
            [52, 73, 94]      // أزرق داكن
        ];
        $color = $colors[array_rand($colors)];
        $background = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    }
    
    $text_color = imagecolorallocate($image, 255, 255, 255); // أبيض
    $border_color = imagecolorallocate($image, 0, 0, 0); // أسود
    
    // رسم إطار
    imagerectangle($image, 0, 0, $width-1, $height-1, $border_color);
    imagerectangle($image, 2, 2, $width-3, $height-3, $border_color);
    
    // إضافة النص
    $font_size = 5;
    $text_width = imagefontwidth($font_size) * strlen($text);
    $text_height = imagefontheight($font_size);
    
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    
    imagestring($image, $font_size, $x, $y, $text, $text_color);
    
    // حفظ الصورة
    $filepath = $upload_dir . $filename;
    imagejpeg($image, $filepath, 90);
    imagedestroy($image);
    
    return file_exists($filepath);
}

echo "<h2>إنشاء الصور التجريبية للمتجر</h2>";
echo "<div style='font-family: Arial; direction: rtl;'>";

// صور الفئات
$categories = [
    'electronics.jpg' => 'الإلكترونيات',
    'clothing.jpg' => 'الملابس',
    'home-garden.jpg' => 'المنزل والحديقة',
    'sports.jpg' => 'الرياضة واللياقة',
    'books.jpg' => 'الكتب والقرطاسية',
    'beauty.jpg' => 'الجمال والعناية',
    'toys.jpg' => 'الألعاب والهوايات',
    'automotive.jpg' => 'السيارات والدراجات'
];

echo "<h3>إنشاء صور الفئات:</h3>";
foreach ($categories as $filename => $name) {
    if (createSampleImage($filename, $name, 300, 200)) {
        echo "<p style='color: green;'>✅ تم إنشاء صورة: $filename - $name</p>";
    } else {
        echo "<p style='color: red;'>❌ فشل في إنشاء صورة: $filename</p>";
    }
}

// صور المنتجات
$products = [
    // الإلكترونيات
    'samsung-s23.jpg' => 'Samsung Galaxy S23',
    'dell-xps13.jpg' => 'Dell XPS 13',
    'airpods-pro.jpg' => 'AirPods Pro',
    'ipad-air.jpg' => 'iPad Air',
    'apple-watch-s8.jpg' => 'Apple Watch S8',
    
    // الملابس
    'mens-shirt.jpg' => 'قميص رجالي',
    'summer-dress.jpg' => 'فستان صيفي',
    'mens-jeans.jpg' => 'جينز رجالي',
    'leather-handbag.jpg' => 'حقيبة جلدية',
    'running-shoes.jpg' => 'حذاء رياضي',
    
    // المنزل والحديقة
    'cookware-set.jpg' => 'طقم أواني طبخ',
    'vacuum-cleaner.jpg' => 'مكنسة كهربائية',
    'garden-furniture.jpg' => 'أثاث حديقة',
    'smart-led.jpg' => 'مصباح LED ذكي',
    'bathroom-mirror.jpg' => 'مرآة حمام',
    
    // الرياضة واللياقة
    'mountain-bike.jpg' => 'دراجة جبلية',
    'adjustable-weights.jpg' => 'أوزان قابلة للتعديل',
    'yoga-mat.jpg' => 'حصيرة يوغا',
    'fitness-watch.jpg' => 'ساعة رياضية',
    'football.jpg' => 'كرة قدم',
    
    // الكتب والقرطاسية
    'time-management-book.jpg' => 'كتاب إدارة الوقت',
    'leather-notebook.jpg' => 'دفتر جلدي',
    'pen-set.jpg' => 'طقم أقلام',
    'calculator.jpg' => 'آلة حاسبة',
    'cooking-books.jpg' => 'كتب الطبخ',
    
    // الجمال والعناية
    'mens-perfume.jpg' => 'عطر رجالي',
    'face-cream.jpg' => 'كريم الوجه',
    'natural-shampoo.jpg' => 'شامبو طبيعي',
    'makeup-set.jpg' => 'طقم مكياج',
    'face-brush.jpg' => 'فرشاة الوجه',
    
    // الألعاب والهوايات
    'lego-set.jpg' => 'ليغو للأطفال',
    'kids-tricycle.jpg' => 'دراجة أطفال',
    'drawing-tablet.jpg' => 'لوحة رسم',
    'watercolor-set.jpg' => 'ألوان مائية',
    'chess-set.jpg' => 'شطرنج خشبي',
    
    // السيارات والدراجات
    'car-tires.jpg' => 'إطارات سيارة',
    'engine-oil.jpg' => 'زيت محرك',
    'car-stereo.jpg' => 'مشغل موسيقى',
    'motorcycle-helmet.jpg' => 'خوذة دراجة نارية',
    'car-tools.jpg' => 'أدوات إصلاح'
];

echo "<h3>إنشاء صور المنتجات:</h3>";
$count = 0;
foreach ($products as $filename => $name) {
    if (createSampleImage($filename, $name, 400, 300)) {
        echo "<p style='color: green;'>✅ تم إنشاء صورة: $filename - $name</p>";
        $count++;
    } else {
        echo "<p style='color: red;'>❌ فشل في إنشاء صورة: $filename</p>";
    }
}

echo "<hr>";
echo "<h3>ملخص العملية:</h3>";
echo "<p><strong>إجمالي الصور المنشأة:</strong> " . ($count + count($categories)) . "</p>";
echo "<p><strong>صور الفئات:</strong> " . count($categories) . "</p>";
echo "<p><strong>صور المنتجات:</strong> " . $count . "</p>";

echo "<hr>";
echo "<h3>الخطوات التالية:</h3>";
echo "<ol>";
echo "<li>قم بتشغيل ملف <code>sample_data.sql</code> في قاعدة البيانات</li>";
echo "<li>تأكد من أن مجلد <code>uploads</code> له صلاحيات الكتابة</li>";
echo "<li>يمكنك استبدال الصور التجريبية بصور حقيقية لاحقاً</li>";
echo "</ol>";

echo "</div>";
?>
