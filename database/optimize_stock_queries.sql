-- تحسين استعلامات المخزون وإدارة الطلبات
-- Optimize stock queries and order management

-- إضافة فهارس لتحسين الأداء
-- Add indexes for better performance

-- فهرس على حالة الطلب لتسريع البحث عن الطلبات الملغية
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);

-- فهرس على كمية المخزون لتسريع البحث عن المنتجات منخفضة المخزون
CREATE INDEX IF NOT EXISTS idx_products_stock ON products(stock_quantity);

-- فهرس على معرف الطلب في جدول عناصر الطلب
CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items(order_id);

-- فهرس على معرف المنتج في جدول عناصر الطلب
CREATE INDEX IF NOT EXISTS idx_order_items_product_id ON order_items(product_id);

-- فهرس مركب على معرف المستخدم وحالة الطلب
CREATE INDEX IF NOT EXISTS idx_orders_user_status ON orders(user_id, status);

-- فهرس على تاريخ إنشاء الطلب
CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at);

-- إضافة عمود لتتبع آخر تحديث للمخزون (اختياري)
ALTER TABLE products ADD COLUMN IF NOT EXISTS stock_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- إنشاء جدول لتتبع تغييرات المخزون (اختياري للمراجعة)
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    previous_stock INT NOT NULL,
    new_stock INT NOT NULL,
    reference_type ENUM('order', 'cancellation', 'manual', 'return') NOT NULL,
    reference_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- فهرس على جدول تتبع المخزون
CREATE INDEX IF NOT EXISTS idx_stock_movements_product ON stock_movements(product_id);
CREATE INDEX IF NOT EXISTS idx_stock_movements_type ON stock_movements(movement_type);
CREATE INDEX IF NOT EXISTS idx_stock_movements_reference ON stock_movements(reference_type, reference_id);
CREATE INDEX IF NOT EXISTS idx_stock_movements_date ON stock_movements(created_at);

-- إنشاء view لعرض المنتجات مع معلومات المخزون
CREATE OR REPLACE VIEW products_stock_view AS
SELECT 
    p.id,
    p.name,
    p.price,
    p.discount_price,
    p.stock_quantity,
    p.category_id,
    c.name as category_name,
    p.status,
    p.featured,
    p.sales_count,
    p.created_at,
    p.updated_at,
    CASE 
        WHEN p.stock_quantity = 0 THEN 'out_of_stock'
        WHEN p.stock_quantity <= 5 THEN 'low_stock'
        WHEN p.stock_quantity <= 20 THEN 'medium_stock'
        ELSE 'high_stock'
    END as stock_status,
    CASE 
        WHEN p.stock_quantity = 0 THEN 'نفد المخزون'
        WHEN p.stock_quantity <= 5 THEN 'مخزون منخفض'
        WHEN p.stock_quantity <= 20 THEN 'مخزون متوسط'
        ELSE 'مخزون جيد'
    END as stock_status_ar
FROM products p
LEFT JOIN categories c ON p.category_id = c.id;

-- إنشاء view لعرض الطلبات الملغية مع تفاصيل المنتجات
CREATE OR REPLACE VIEW cancelled_orders_view AS
SELECT 
    o.id as order_id,
    o.user_id,
    o.total_amount,
    o.created_at as order_date,
    o.updated_at as cancelled_date,
    u.first_name,
    u.last_name,
    u.email,
    COUNT(oi.id) as items_count,
    SUM(oi.quantity) as total_quantity,
    GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as products_summary
FROM orders o
JOIN users u ON o.user_id = u.id
LEFT JOIN order_items oi ON o.id = oi.order_id
LEFT JOIN products p ON oi.product_id = p.id
WHERE o.status = 'cancelled'
GROUP BY o.id, o.user_id, o.total_amount, o.created_at, o.updated_at, u.first_name, u.last_name, u.email
ORDER BY o.updated_at DESC;

-- إنشاء stored procedure لإرجاع المخزون (اختياري)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS RestoreStockForOrder(IN order_id_param INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE product_id_var INT;
    DECLARE quantity_var INT;
    DECLARE cur CURSOR FOR 
        SELECT product_id, quantity 
        FROM order_items 
        WHERE order_id = order_id_param;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    START TRANSACTION;
    
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO product_id_var, quantity_var;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- تحديث المخزون
        UPDATE products 
        SET stock_quantity = stock_quantity + quantity_var,
            stock_updated_at = CURRENT_TIMESTAMP
        WHERE id = product_id_var;
        
        -- تسجيل حركة المخزون (إذا كان الجدول موجود)
        INSERT INTO stock_movements (
            product_id, 
            movement_type, 
            quantity, 
            previous_stock, 
            new_stock, 
            reference_type, 
            reference_id,
            notes
        ) 
        SELECT 
            product_id_var,
            'in',
            quantity_var,
            stock_quantity - quantity_var,
            stock_quantity,
            'cancellation',
            order_id_param,
            CONCAT('إرجاع مخزون من إلغاء الطلب #', order_id_param)
        FROM products 
        WHERE id = product_id_var;
        
    END LOOP;
    CLOSE cur;
    
    COMMIT;
END //
DELIMITER ;

-- إنشاء trigger لتسجيل تغييرات المخزون تلقائياً (اختياري)
DELIMITER //
CREATE TRIGGER IF NOT EXISTS products_stock_update_trigger
    AFTER UPDATE ON products
    FOR EACH ROW
BEGIN
    IF OLD.stock_quantity != NEW.stock_quantity THEN
        INSERT INTO stock_movements (
            product_id,
            movement_type,
            quantity,
            previous_stock,
            new_stock,
            reference_type,
            notes
        ) VALUES (
            NEW.id,
            CASE 
                WHEN NEW.stock_quantity > OLD.stock_quantity THEN 'in'
                ELSE 'out'
            END,
            ABS(NEW.stock_quantity - OLD.stock_quantity),
            OLD.stock_quantity,
            NEW.stock_quantity,
            'manual',
            'تحديث تلقائي للمخزون'
        );
    END IF;
END //
DELIMITER ;

-- إنشاء function لحساب قيمة المخزون الإجمالية
DELIMITER //
CREATE FUNCTION IF NOT EXISTS CalculateTotalStockValue() 
RETURNS DECIMAL(15,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE total_value DECIMAL(15,2) DEFAULT 0;
    
    SELECT SUM(
        stock_quantity * COALESCE(discount_price, price)
    ) INTO total_value
    FROM products 
    WHERE status = 'active';
    
    RETURN COALESCE(total_value, 0);
END //
DELIMITER ;

-- تحديث إحصائيات قاعدة البيانات
ANALYZE TABLE products;
ANALYZE TABLE orders;
ANALYZE TABLE order_items;

-- إضافة تعليقات للجداول
ALTER TABLE products COMMENT = 'جدول المنتجات مع تتبع المخزون';
ALTER TABLE orders COMMENT = 'جدول الطلبات مع حالات مختلفة';
ALTER TABLE order_items COMMENT = 'تفاصيل المنتجات في كل طلب';
ALTER TABLE stock_movements COMMENT = 'تتبع حركات المخزون للمراجعة';

-- إنشاء view للتقارير السريعة
CREATE OR REPLACE VIEW stock_summary AS
SELECT 
    COUNT(*) as total_products,
    SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_count,
    SUM(CASE WHEN stock_quantity <= 5 AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock_count,
    SUM(stock_quantity) as total_stock_units,
    SUM(stock_quantity * COALESCE(discount_price, price)) as total_stock_value,
    AVG(stock_quantity) as avg_stock_per_product
FROM products 
WHERE status = 'active';
