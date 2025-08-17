<?php
/**
 * فئة إدارة الطلبات
 * Order management class
 */

class Order {
    private $conn;
    private $table_name = "orders";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * إنشاء طلب جديد
     * Create new order
     */
    public function createOrder($user_id, $cart_items, $shipping_address, $payment_method, $notes = '') {
        try {
            $this->conn->beginTransaction();
            
            // حساب إجمالي الطلب
            $total_amount = 0;
            foreach ($cart_items as $item) {
                $price = $item['discount_price'] ?: $item['price'];
                $total_amount += $price * $item['quantity'];
            }
            
            // إضافة تكلفة الشحن
            $shipping_cost = $total_amount >= 200 ? 0 : 25;
            $total_amount += $shipping_cost;
            
            // إنشاء الطلب
            $query = "INSERT INTO " . $this->table_name . " 
                      (user_id, total_amount, shipping_address, payment_method, notes) 
                      VALUES (:user_id, :total_amount, :shipping_address, :payment_method, :notes)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':user_id' => $user_id,
                ':total_amount' => $total_amount,
                ':shipping_address' => $shipping_address,
                ':payment_method' => $payment_method,
                ':notes' => $notes
            ]);
            
            $order_id = $this->conn->lastInsertId();
            
            // إضافة عناصر الطلب
            $this->addOrderItems($order_id, $cart_items);
            
            // تحديث المخزون
            $this->updateStock($cart_items);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'order_id' => $order_id,
                'message' => 'تم إنشاء الطلب بنجاح'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Order creation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الطلب'
            ];
        }
    }

    /**
     * إضافة عناصر الطلب
     * Add order items
     */
    private function addOrderItems($order_id, $cart_items) {
        $query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                  VALUES (:order_id, :product_id, :quantity, :price)";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($cart_items as $item) {
            $price = $item['discount_price'] ?: $item['price'];
            $stmt->execute([
                ':order_id' => $order_id,
                ':product_id' => $item['product_id'],
                ':quantity' => $item['quantity'],
                ':price' => $price
            ]);
        }
    }

    /**
     * تحديث المخزون
     * Update stock
     */
    private function updateStock($cart_items) {
        $query = "UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id";
        $stmt = $this->conn->prepare($query);
        
        foreach ($cart_items as $item) {
            $stmt->execute([
                ':quantity' => $item['quantity'],
                ':product_id' => $item['product_id']
            ]);
        }
    }

    /**
     * الحصول على طلب بواسطة المعرف
     * Get order by ID
     */
    public function getOrderById($order_id, $user_id = null) {
        $query = "SELECT o.*, u.first_name, u.last_name, u.email 
                  FROM " . $this->table_name . " o 
                  JOIN users u ON o.user_id = u.id 
                  WHERE o.id = :order_id";
        
        $params = [':order_id' => $order_id];
        
        if ($user_id) {
            $query .= " AND o.user_id = :user_id";
            $params[':user_id'] = $user_id;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على عناصر الطلب
     * Get order items
     */
    public function getOrderItems($order_id) {
        $query = "SELECT oi.*, p.name, p.main_image 
                  FROM order_items oi 
                  JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = :order_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':order_id' => $order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على طلبات المستخدم
     * Get user orders
     */
    public function getUserOrders($user_id, $limit = null) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT :limit";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        if ($limit) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على جميع الطلبات (للمشرف)
     * Get all orders (for admin)
     */
    public function getAllOrders($limit = null, $offset = 0) {
        $query = "SELECT o.*, u.first_name, u.last_name, u.email 
                  FROM " . $this->table_name . " o 
                  JOIN users u ON o.user_id = u.id 
                  ORDER BY o.created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($limit) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * تحديث حالة الطلب
     * Update order status
     */
    public function updateOrderStatus($order_id, $status) {
        try {
            $this->conn->beginTransaction();

            // تحديث حالة الطلب
            $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :order_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':order_id' => $order_id,
                ':status' => $status
            ]);

            // إذا تم تسليم الطلب، تحديث حالة الدفع إلى "مدفوع"
            if ($status === 'delivered') {
                $payment_query = "UPDATE " . $this->table_name . " SET payment_status = 'paid' WHERE id = :order_id";
                $payment_stmt = $this->conn->prepare($payment_query);
                $payment_stmt->execute([':order_id' => $order_id]);

                // تحديث تاريخ التسليم (إذا كان العمود موجوداً)
                try {
                    $delivery_query = "UPDATE " . $this->table_name . " SET delivered_at = NOW() WHERE id = :order_id";
                    $delivery_stmt = $this->conn->prepare($delivery_query);
                    $delivery_stmt->execute([':order_id' => $order_id]);
                } catch (PDOException $e) {
                    // العمود غير موجود، تجاهل الخطأ
                    error_log("عمود delivered_at غير موجود: " . $e->getMessage());
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * تحديث حالة الدفع
     * Update payment status
     */
    public function updatePaymentStatus($order_id, $payment_status) {
        $query = "UPDATE " . $this->table_name . " SET payment_status = :payment_status WHERE id = :order_id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':order_id' => $order_id,
            ':payment_status' => $payment_status
        ]);
    }

    /**
     * إلغاء الطلب
     * Cancel order
     */
    public function cancelOrder($order_id, $user_id = null) {
        try {
            $this->conn->beginTransaction();

            // التحقق من إمكانية الإلغاء
            $order = $this->getOrderById($order_id, $user_id);
            if (!$order) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'الطلب غير موجود'];
            }

            if (in_array($order['status'], ['shipped', 'delivered'])) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'لا يمكن إلغاء طلب تم شحنه أو تسليمه'];
            }

            if ($order['status'] === 'cancelled') {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'الطلب ملغي مسبقاً'];
            }

            // الحصول على عناصر الطلب
            $order_items = $this->getOrderItems($order_id);
            if (empty($order_items)) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'لا توجد منتجات في هذا الطلب'];
            }

            // إرجاع المخزون
            $this->restoreStock($order_items);

            // تحديث حالة الطلب مباشرة (بدون transaction منفصل)
            $query = "UPDATE " . $this->table_name . " SET status = 'cancelled' WHERE id = :order_id";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([':order_id' => $order_id]);

            if (!$result) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'فشل في تحديث حالة الطلب'];
            }

            $this->conn->commit();

            // تسجيل عملية الإلغاء
            error_log("Order #{$order_id} cancelled successfully. Stock restored for " . count($order_items) . " items.");

            return ['success' => true, 'message' => 'تم إلغاء الطلب وإرجاع المنتجات للمخزون بنجاح'];

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Order cancellation error for order #{$order_id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ أثناء إلغاء الطلب'];
        }
    }

    /**
     * إرجاع المخزون
     * Restore stock
     */
    private function restoreStock($order_items) {
        if (empty($order_items)) {
            return true;
        }

        $query = "UPDATE products SET stock_quantity = stock_quantity + :quantity WHERE id = :product_id";
        $stmt = $this->conn->prepare($query);

        foreach ($order_items as $item) {
            try {
                // التحقق من صحة البيانات
                if (!isset($item['quantity']) || !isset($item['product_id']) ||
                    $item['quantity'] <= 0 || $item['product_id'] <= 0) {
                    error_log("Invalid order item data for stock restoration: " . json_encode($item));
                    continue;
                }

                $result = $stmt->execute([
                    ':quantity' => (int)$item['quantity'],
                    ':product_id' => (int)$item['product_id']
                ]);

                if ($result) {
                    error_log("Stock restored for product {$item['product_id']}: +{$item['quantity']} units");
                } else {
                    error_log("Failed to restore stock for product {$item['product_id']}");
                }

            } catch (Exception $e) {
                error_log("Error restoring stock for product {$item['product_id']}: " . $e->getMessage());
                // لا نوقف العملية، نكمل مع باقي المنتجات
            }
        }

        return true;
    }

    /**
     * الحصول على إحصائيات الطلبات
     * Get order statistics
     */
    public function getOrderStats() {
        $stats = [];
        
        // إجمالي الطلبات
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // الطلبات المعلقة
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['pending_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // إجمالي المبيعات
        $query = "SELECT SUM(total_amount) as total FROM " . $this->table_name . " WHERE payment_status = 'paid'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_sales'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        
        return $stats;
    }

    /**
     * الحصول على إحصائيات المبيعات المفصلة
     * Get detailed sales statistics
     */
    public function getSalesStats($period = 'month') {
        $stats = [];

        // تحديد الفترة الزمنية
        switch ($period) {
            case 'today':
                $date_condition = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            default:
                $date_condition = "1=1"; // جميع الفترات
        }

        // إجمالي المبيعات المدفوعة
        $query = "SELECT
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_order_value
                  FROM " . $this->table_name . "
                  WHERE payment_status = 'paid' AND " . $date_condition;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $stats['total_orders'] = $result['total_orders'] ?: 0;
        $stats['total_revenue'] = $result['total_revenue'] ?: 0;
        $stats['avg_order_value'] = $result['avg_order_value'] ?: 0;

        // المبيعات حسب اليوم (آخر 7 أيام)
        $query = "SELECT
                    DATE(created_at) as sale_date,
                    COUNT(*) as orders_count,
                    SUM(total_amount) as daily_revenue
                  FROM " . $this->table_name . "
                  WHERE payment_status = 'paid'
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY sale_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['daily_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }

    /**
     * تحديث إحصائيات المبيعات عند تسليم الطلب
     * Update sales statistics when order is delivered
     */
    public function updateSalesStatistics($order_id) {
        try {
            // الحصول على تفاصيل الطلب
            $order = $this->getOrderById($order_id);

            if ($order && $order['payment_status'] === 'paid') {
                // تحديث إحصائيات المنتجات المباعة
                $this->updateProductSalesStats($order_id);
                return true;
            }

            return true; // إرجاع true حتى لو لم يكن الطلب مدفوعاً لتجنب الأخطاء

        } catch (Exception $e) {
            // تسجيل الخطأ ولكن عدم إيقاف العملية
            error_log("خطأ في تحديث إحصائيات المبيعات: " . $e->getMessage());
            return true; // إرجاع true لتجنب إيقاف تحديث حالة الطلب
        }
    }

    /**
     * تحديث إحصائيات مبيعات المنتجات
     * Update product sales statistics
     */
    private function updateProductSalesStats($order_id) {
        try {
            // التحقق من وجود عمود sales_count
            $check_query = "SHOW COLUMNS FROM products LIKE 'sales_count'";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                // العمود موجود، تحديث الإحصائيات
                $query = "UPDATE products p
                          JOIN order_items oi ON p.id = oi.product_id
                          SET p.sales_count = COALESCE(p.sales_count, 0) + oi.quantity
                          WHERE oi.order_id = :order_id";

                $stmt = $this->conn->prepare($query);
                return $stmt->execute([':order_id' => $order_id]);
            } else {
                // العمود غير موجود، إضافته أولاً
                $alter_query = "ALTER TABLE products ADD COLUMN sales_count INT DEFAULT 0";
                $this->conn->exec($alter_query);

                // ثم تحديث الإحصائيات
                $query = "UPDATE products p
                          JOIN order_items oi ON p.id = oi.product_id
                          SET p.sales_count = COALESCE(p.sales_count, 0) + oi.quantity
                          WHERE oi.order_id = :order_id";

                $stmt = $this->conn->prepare($query);
                return $stmt->execute([':order_id' => $order_id]);
            }

        } catch (Exception $e) {
            // تسجيل الخطأ ولكن عدم إيقاف العملية
            error_log("خطأ في تحديث إحصائيات المنتجات: " . $e->getMessage());
            return true;
        }
    }
}
?>
