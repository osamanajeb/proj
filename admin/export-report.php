<?php
/**
 * تصدير التقارير
 * Export Reports
 */

require_once '../config/config.php';
require_once '../classes/Order.php';
require_once '../classes/Product.php';
require_once '../classes/User.php';

// التحقق من المعاملات
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// إنشاء كائنات الفئات
$orderObj = new Order();
$productObj = new Product();
$userObj = new User();

// دالة لتصدير CSV
function exportCSV($data, $headers, $filename) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    
    // إضافة BOM للدعم العربي
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // كتابة العناوين
    fputcsv($output, $headers);
    
    // كتابة البيانات
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// دالة لتصدير Excel
function exportExcel($data, $headers, $filename) {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    
    echo "\xEF\xBB\xBF"; // BOM للدعم العربي
    echo '<table border="1">';
    
    // العناوين
    echo '<tr>';
    foreach ($headers as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr>';
    
    // البيانات
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table>';
    exit;
}

// الحصول على البيانات حسب نوع التقرير
switch ($report_type) {
    case 'sales':
        $db = getDBConnection();
        $stmt = $db->prepare("
            SELECT 
                o.id,
                o.created_at,
                CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                o.total_amount,
                o.status,
                o.payment_method,
                o.payment_status
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $headers = ['رقم الطلب', 'تاريخ الطلب', 'اسم العميل', 'إجمالي المبلغ', 'حالة الطلب', 'طريقة الدفع', 'حالة الدفع'];
        $export_data = [];
        
        foreach ($data as $row) {
            $export_data[] = [
                $row['id'],
                $row['created_at'],
                $row['customer_name'],
                $row['total_amount'] . ' ر.س',
                $row['status'],
                $row['payment_method'],
                $row['payment_status']
            ];
        }
        
        $filename = 'sales_report_' . $start_date . '_to_' . $end_date;
        break;
        
    case 'products':
        $db = getDBConnection();
        $stmt = $db->prepare("
            SELECT 
                p.id,
                p.name,
                p.price,
                p.discount_price,
                p.stock_quantity,
                c.name as category_name,
                COALESCE(SUM(oi.quantity), 0) as total_sold
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND DATE(o.created_at) BETWEEN ? AND ?
            WHERE p.status = 'active'
            GROUP BY p.id, p.name, p.price, p.discount_price, p.stock_quantity, c.name
            ORDER BY total_sold DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $headers = ['رقم المنتج', 'اسم المنتج', 'السعر', 'السعر بعد الخصم', 'المخزون', 'الفئة', 'الكمية المباعة'];
        $export_data = [];
        
        foreach ($data as $row) {
            $export_data[] = [
                $row['id'],
                $row['name'],
                $row['price'] . ' ر.س',
                $row['discount_price'] ? $row['discount_price'] . ' ر.س' : 'لا يوجد',
                $row['stock_quantity'],
                $row['category_name'] ?? 'غير محدد',
                $row['total_sold']
            ];
        }
        
        $filename = 'products_report_' . $start_date . '_to_' . $end_date;
        break;
        
    case 'customers':
        $db = getDBConnection();
        $stmt = $db->prepare("
            SELECT 
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.created_at,
                COUNT(o.id) as total_orders,
                COALESCE(SUM(o.total_amount), 0) as total_spent
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id AND DATE(o.created_at) BETWEEN ? AND ?
            WHERE u.role = 'customer'
            GROUP BY u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at
            ORDER BY total_spent DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $headers = ['رقم العميل', 'الاسم الأول', 'الاسم الأخير', 'البريد الإلكتروني', 'الهاتف', 'تاريخ التسجيل', 'عدد الطلبات', 'إجمالي المشتريات'];
        $export_data = [];
        
        foreach ($data as $row) {
            $export_data[] = [
                $row['id'],
                $row['first_name'],
                $row['last_name'],
                $row['email'],
                $row['phone'] ?? 'غير محدد',
                $row['created_at'],
                $row['total_orders'],
                $row['total_spent'] . ' ر.س'
            ];
        }
        
        $filename = 'customers_report_' . $start_date . '_to_' . $end_date;
        break;
        
    case 'inventory':
        $db = getDBConnection();
        $stmt = $db->prepare("
            SELECT 
                p.id,
                p.name,
                p.price,
                p.discount_price,
                p.stock_quantity,
                c.name as category_name,
                (p.stock_quantity * p.price) as stock_value,
                CASE 
                    WHEN p.stock_quantity <= 5 THEN 'منخفض جداً'
                    WHEN p.stock_quantity <= 10 THEN 'منخفض'
                    WHEN p.stock_quantity <= 50 THEN 'متوسط'
                    ELSE 'جيد'
                END as stock_status
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'active'
            ORDER BY p.stock_quantity ASC
        ");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $headers = ['رقم المنتج', 'اسم المنتج', 'السعر', 'السعر بعد الخصم', 'المخزون', 'الفئة', 'قيمة المخزون', 'حالة المخزون'];
        $export_data = [];
        
        foreach ($data as $row) {
            $export_data[] = [
                $row['id'],
                $row['name'],
                $row['price'] . ' ر.س',
                $row['discount_price'] ? $row['discount_price'] . ' ر.س' : 'لا يوجد',
                $row['stock_quantity'],
                $row['category_name'] ?? 'غير محدد',
                $row['stock_value'] . ' ر.س',
                $row['stock_status']
            ];
        }
        
        $filename = 'inventory_report_' . date('Y-m-d');
        break;
        
    default:
        die('نوع تقرير غير صحيح');
}

// تصدير البيانات حسب التنسيق المطلوب
if ($format == 'excel') {
    exportExcel($export_data, $headers, $filename);
} else {
    exportCSV($export_data, $headers, $filename);
}
?>
