<?php
/**
 * فئة إدارة سلة المشتريات
 * Shopping cart management class
 */

class Cart {
    private $conn;
    private $table_name = "cart";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * إضافة منتج إلى السلة
     * Add product to cart
     */
    public function addToCart($product_id, $quantity = 1) {
        // منع المشرفين من استخدام سلة المشتريات
        if (is_admin()) {
            return false;
        }

        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $session_id = get_cart_session_id();

        // التحقق من وجود المنتج في السلة
        $existing_item = $this->getCartItem($product_id, $user_id, $session_id);

        if ($existing_item) {
            // تحديث الكمية
            return $this->updateCartItem($existing_item['id'], $existing_item['quantity'] + $quantity);
        } else {
            // إضافة منتج جديد
            $query = "INSERT INTO " . $this->table_name . " (user_id, session_id, product_id, quantity)
                      VALUES (:user_id, :session_id, :product_id, :quantity)";

            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':user_id' => $user_id,
                ':session_id' => $session_id,
                ':product_id' => $product_id,
                ':quantity' => $quantity
            ]);
        }
    }

    /**
     * الحصول على عنصر من السلة
     * Get cart item
     */
    private function getCartItem($product_id, $user_id, $session_id) {
        if ($user_id) {
            $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id AND product_id = :product_id";
            $params = [':user_id' => $user_id, ':product_id' => $product_id];
        } else {
            $query = "SELECT * FROM " . $this->table_name . " WHERE session_id = :session_id AND product_id = :product_id";
            $params = [':session_id' => $session_id, ':product_id' => $product_id];
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * تحديث كمية المنتج في السلة
     * Update cart item quantity
     */
    public function updateCartItem($cart_id, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($cart_id);
        }
        
        $query = "UPDATE " . $this->table_name . " SET quantity = :quantity WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $cart_id,
            ':quantity' => $quantity
        ]);
    }

    /**
     * إزالة منتج من السلة
     * Remove product from cart
     */
    public function removeFromCart($cart_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $cart_id]);
    }

    /**
     * الحصول على محتويات السلة
     * Get cart contents
     */
    public function getCartItems() {
        // منع المشرفين من الوصول لسلة المشتريات
        if (is_admin()) {
            return [];
        }

        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $session_id = get_cart_session_id();

        if ($user_id) {
            $query = "SELECT c.*, p.name, p.price, p.discount_price, p.main_image, p.stock_quantity
                      FROM " . $this->table_name . " c
                      JOIN products p ON c.product_id = p.id
                      WHERE c.user_id = :user_id AND p.status = 'active'";
            $params = [':user_id' => $user_id];
        } else {
            $query = "SELECT c.*, p.name, p.price, p.discount_price, p.main_image, p.stock_quantity
                      FROM " . $this->table_name . " c
                      JOIN products p ON c.product_id = p.id
                      WHERE c.session_id = :session_id AND p.status = 'active'";
            $params = [':session_id' => $session_id];
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * حساب إجمالي السلة
     * Calculate cart total
     */
    public function getCartTotal() {
        $items = $this->getCartItems();
        $total = 0;
        
        foreach ($items as $item) {
            $price = $item['discount_price'] ? $item['discount_price'] : $item['price'];
            $total += $price * $item['quantity'];
        }
        
        return $total;
    }

    /**
     * حساب عدد المنتجات في السلة
     * Count cart items
     */
    public function getCartCount() {
        // منع المشرفين من الوصول لسلة المشتريات
        if (is_admin()) {
            return 0;
        }

        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $session_id = get_cart_session_id();

        if ($user_id) {
            $query = "SELECT SUM(quantity) as total FROM " . $this->table_name . " WHERE user_id = :user_id";
            $params = [':user_id' => $user_id];
        } else {
            $query = "SELECT SUM(quantity) as total FROM " . $this->table_name . " WHERE session_id = :session_id";
            $params = [':session_id' => $session_id];
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ? $result['total'] : 0;
    }

    /**
     * إفراغ السلة
     * Clear cart
     */
    public function clearCart() {
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $session_id = get_cart_session_id();

        if ($user_id) {
            $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
            $params = [':user_id' => $user_id];
        } else {
            $query = "DELETE FROM " . $this->table_name . " WHERE session_id = :session_id";
            $params = [':session_id' => $session_id];
        }
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * نقل السلة من الجلسة إلى المستخدم عند تسجيل الدخول
     * Transfer cart from session to user on login
     */
    public function transferCartToUser($user_id) {
        $session_id = get_cart_session_id();
        
        $query = "UPDATE " . $this->table_name . " SET user_id = :user_id WHERE session_id = :session_id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':user_id' => $user_id,
            ':session_id' => $session_id
        ]);
    }

    /**
     * التحقق من توفر المنتجات في المخزون
     * Check product availability in stock
     */
    public function checkStockAvailability() {
        $items = $this->getCartItems();
        $unavailable_items = [];
        
        foreach ($items as $item) {
            if ($item['stock_quantity'] < $item['quantity']) {
                $unavailable_items[] = [
                    'name' => $item['name'],
                    'requested' => $item['quantity'],
                    'available' => $item['stock_quantity']
                ];
            }
        }
        
        return $unavailable_items;
    }

    /**
     * إنشاء طلب مباشر للمشرف (بدون سلة مشتريات)
     * Create direct order for admin (without cart)
     */
    public function createAdminDirectOrder($products, $customer_data) {
        if (!is_admin()) {
            return ['success' => false, 'message' => 'غير مصرح لك بهذا الإجراء'];
        }

        try {
            $this->conn->beginTransaction();

            // حساب الإجمالي
            $total = 0;
            foreach ($products as $product) {
                $total += $product['price'] * $product['quantity'];
            }

            // إنشاء الطلب
            $order_query = "INSERT INTO orders (first_name, last_name, email, phone, address, city, total, payment_method, payment_status, status, created_by_admin, admin_notes)
                           VALUES (:first_name, :last_name, :email, :phone, :address, :city, :total, :payment_method, :payment_status, :status, 1, :admin_notes)";

            $order_stmt = $this->conn->prepare($order_query);
            $order_result = $order_stmt->execute([
                ':first_name' => $customer_data['first_name'],
                ':last_name' => $customer_data['last_name'],
                ':email' => $customer_data['email'],
                ':phone' => $customer_data['phone'],
                ':address' => $customer_data['address'],
                ':city' => $customer_data['city'],
                ':total' => $total,
                ':payment_method' => $customer_data['payment_method'] ?? 'cash',
                ':payment_status' => $customer_data['payment_status'] ?? 'pending',
                ':status' => 'pending',
                ':admin_notes' => $customer_data['admin_notes'] ?? 'طلب تم إنشاؤه بواسطة المشرف'
            ]);

            if (!$order_result) {
                throw new Exception('فشل في إنشاء الطلب');
            }

            $order_id = $this->conn->lastInsertId();

            // إضافة منتجات الطلب
            $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)";
            $item_stmt = $this->conn->prepare($item_query);

            foreach ($products as $product) {
                $item_result = $item_stmt->execute([
                    ':order_id' => $order_id,
                    ':product_id' => $product['product_id'],
                    ':quantity' => $product['quantity'],
                    ':price' => $product['price']
                ]);

                if (!$item_result) {
                    throw new Exception('فشل في إضافة منتجات الطلب');
                }

                // تحديث المخزون
                $stock_query = "UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id";
                $stock_stmt = $this->conn->prepare($stock_query);
                $stock_stmt->execute([
                    ':quantity' => $product['quantity'],
                    ':product_id' => $product['product_id']
                ]);
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'تم إنشاء الطلب بنجاح', 'order_id' => $order_id];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * الحصول على إحصائيات سلة المشتريات للعملاء (للمشرف)
     * Get cart statistics for customers (for admin)
     */
    public function getCartStatistics() {
        if (!is_admin()) {
            return [];
        }

        $query = "SELECT
                    COUNT(DISTINCT CASE WHEN user_id IS NOT NULL THEN user_id ELSE session_id END) as active_carts,
                    SUM(c.quantity) as total_items,
                    SUM(c.quantity * COALESCE(p.discount_price, p.price)) as total_value,
                    AVG(c.quantity * COALESCE(p.discount_price, p.price)) as avg_cart_value
                  FROM " . $this->table_name . " c
                  JOIN products p ON c.product_id = p.id
                  WHERE p.status = 'active'";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على السلال المهجورة (للمشرف)
     * Get abandoned carts (for admin)
     */
    public function getAbandonedCarts($days = 7) {
        if (!is_admin()) {
            return [];
        }

        $query = "SELECT
                    c.user_id,
                    c.session_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    COUNT(c.id) as items_count,
                    SUM(c.quantity * COALESCE(p.discount_price, p.price)) as cart_value,
                    MAX(c.created_at) as last_activity
                  FROM " . $this->table_name . " c
                  LEFT JOIN users u ON c.user_id = u.id
                  JOIN products p ON c.product_id = p.id
                  WHERE c.created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
                    AND p.status = 'active'
                  GROUP BY COALESCE(c.user_id, c.session_id)
                  ORDER BY cart_value DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
