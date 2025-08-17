-- تحديث قاعدة البيانات لتتبع المبيعات
-- Update database for sales tracking

-- إضافة عمود عدد المبيعات إلى جدول المنتجات
ALTER TABLE products ADD COLUMN IF NOT EXISTS sales_count INT DEFAULT 0;

-- إضافة عمود تاريخ التسليم إلى جدول الطلبات
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL;

-- تحديث عدد المبيعات للمنتجات الموجودة بناءً على الطلبات المدفوعة
UPDATE products p 
SET sales_count = (
    SELECT COALESCE(SUM(oi.quantity), 0)
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id 
    WHERE oi.product_id = p.id 
    AND o.payment_status = 'paid'
);

-- تحديث تاريخ التسليم للطلبات المسلمة
UPDATE orders 
SET delivered_at = updated_at 
WHERE status = 'delivered' AND delivered_at IS NULL;

-- إنشاء فهرس لتحسين الأداء
CREATE INDEX IF NOT EXISTS idx_orders_payment_status ON orders(payment_status);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_delivered_at ON orders(delivered_at);
CREATE INDEX IF NOT EXISTS idx_products_sales_count ON products(sales_count);

-- إنشاء جدول إحصائيات المبيعات اليومية (اختياري للتحسين المستقبلي)
CREATE TABLE IF NOT EXISTS daily_sales_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_date DATE NOT NULL UNIQUE,
    total_orders INT DEFAULT 0,
    total_revenue DECIMAL(10,2) DEFAULT 0,
    avg_order_value DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج البيانات الحالية في جدول الإحصائيات اليومية
INSERT INTO daily_sales_stats (sale_date, total_orders, total_revenue, avg_order_value)
SELECT 
    DATE(created_at) as sale_date,
    COUNT(*) as total_orders,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_order_value
FROM orders 
WHERE payment_status = 'paid'
GROUP BY DATE(created_at)
ON DUPLICATE KEY UPDATE
    total_orders = VALUES(total_orders),
    total_revenue = VALUES(total_revenue),
    avg_order_value = VALUES(avg_order_value);
