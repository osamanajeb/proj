<?php
/**
 * فئة إدارة قائمة الأمنيات
 * Wishlist management class
 */

class Wishlist {
    private $conn;
    private $table_name = "wishlist";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * إضافة منتج إلى قائمة الأمنيات
     * Add product to wishlist
     */
    public function addToWishlist($user_id, $product_id) {
        // التحقق من وجود المنتج في القائمة
        if ($this->isInWishlist($user_id, $product_id)) {
            return ['success' => false, 'message' => 'المنتج موجود في قائمة الأمنيات بالفعل'];
        }

        $query = "INSERT INTO " . $this->table_name . " (user_id, product_id) VALUES (:user_id, :product_id)";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            ':user_id' => $user_id,
            ':product_id' => $product_id
        ]);

        if ($result) {
            return ['success' => true, 'message' => 'تم إضافة المنتج إلى قائمة الأمنيات'];
        } else {
            return ['success' => false, 'message' => 'حدث خطأ أثناء إضافة المنتج'];
        }
    }

    /**
     * إزالة منتج من قائمة الأمنيات
     * Remove product from wishlist
     */
    public function removeFromWishlist($user_id, $product_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id AND product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            ':user_id' => $user_id,
            ':product_id' => $product_id
        ]);

        if ($result) {
            return ['success' => true, 'message' => 'تم إزالة المنتج من قائمة الأمنيات'];
        } else {
            return ['success' => false, 'message' => 'حدث خطأ أثناء إزالة المنتج'];
        }
    }

    /**
     * التحقق من وجود المنتج في قائمة الأمنيات
     * Check if product is in wishlist
     */
    public function isInWishlist($user_id, $product_id) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE user_id = :user_id AND product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':product_id' => $product_id
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * الحصول على قائمة أمنيات المستخدم
     * Get user's wishlist
     */
    public function getUserWishlist($user_id) {
        $query = "SELECT w.*, p.name, p.price, p.discount_price, p.main_image, p.stock_quantity, c.name as category_name 
                  FROM " . $this->table_name . " w 
                  JOIN products p ON w.product_id = p.id 
                  JOIN categories c ON p.category_id = c.id 
                  WHERE w.user_id = :user_id AND p.status = 'active' 
                  ORDER BY w.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على عدد المنتجات في قائمة الأمنيات
     * Get wishlist count
     */
    public function getWishlistCount($user_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " w 
                  JOIN products p ON w.product_id = p.id 
                  WHERE w.user_id = :user_id AND p.status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    /**
     * إفراغ قائمة الأمنيات
     * Clear wishlist
     */
    public function clearWishlist($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':user_id' => $user_id]);
    }

    /**
     * نقل جميع المنتجات من قائمة الأمنيات إلى السلة
     * Move all products from wishlist to cart
     */
    public function moveAllToCart($user_id) {
        require_once 'Cart.php';
        $cartObj = new Cart($this->conn);
        
        $wishlist_items = $this->getUserWishlist($user_id);
        $moved_count = 0;
        
        foreach ($wishlist_items as $item) {
            if ($item['stock_quantity'] > 0) {
                $result = $cartObj->addToCart($item['product_id'], 1);
                if ($result) {
                    $this->removeFromWishlist($user_id, $item['product_id']);
                    $moved_count++;
                }
            }
        }
        
        return [
            'success' => true,
            'moved_count' => $moved_count,
            'message' => "تم نقل {$moved_count} منتج إلى السلة"
        ];
    }

    /**
     * الحصول على المنتجات الأكثر إضافة لقائمة الأمنيات
     * Get most wishlisted products
     */
    public function getMostWishlistedProducts($limit = 10) {
        $query = "SELECT p.*, c.name as category_name, COUNT(w.id) as wishlist_count 
                  FROM products p 
                  JOIN categories c ON p.category_id = c.id 
                  LEFT JOIN " . $this->table_name . " w ON p.id = w.product_id 
                  WHERE p.status = 'active' 
                  GROUP BY p.id 
                  ORDER BY wishlist_count DESC, p.created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على إحصائيات قائمة الأمنيات
     * Get wishlist statistics
     */
    public function getWishlistStats() {
        $stats = [];
        
        // إجمالي المنتجات في قوائم الأمنيات
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_items'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // عدد المستخدمين الذين لديهم قوائم أمنيات
        $query = "SELECT COUNT(DISTINCT user_id) as users FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['users_with_wishlist'] = $stmt->fetch(PDO::FETCH_ASSOC)['users'];
        
        // متوسط عدد المنتجات في قائمة الأمنيات
        if ($stats['users_with_wishlist'] > 0) {
            $stats['avg_items_per_user'] = round($stats['total_items'] / $stats['users_with_wishlist'], 1);
        } else {
            $stats['avg_items_per_user'] = 0;
        }
        
        return $stats;
    }

    /**
     * إشعار المستخدمين عند توفر المنتج
     * Notify users when product is back in stock
     */
    public function notifyStockAvailable($product_id) {
        $query = "SELECT DISTINCT u.email, u.first_name 
                  FROM " . $this->table_name . " w 
                  JOIN users u ON w.user_id = u.id 
                  WHERE w.product_id = :product_id AND u.status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':product_id' => $product_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // هنا يمكن إضافة كود إرسال الإشعارات عبر البريد الإلكتروني
        // أو نظام الإشعارات الداخلي
        
        return $users;
    }

    /**
     * الحصول على اقتراحات بناءً على قائمة الأمنيات
     * Get recommendations based on wishlist
     */
    public function getRecommendations($user_id, $limit = 5) {
        // الحصول على الفئات المفضلة للمستخدم
        $query = "SELECT c.id, COUNT(*) as count 
                  FROM " . $this->table_name . " w 
                  JOIN products p ON w.product_id = p.id 
                  JOIN categories c ON p.category_id = c.id 
                  WHERE w.user_id = :user_id 
                  GROUP BY c.id 
                  ORDER BY count DESC 
                  LIMIT 3";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        $preferred_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($preferred_categories)) {
            return [];
        }
        
        $category_ids = array_column($preferred_categories, 'id');
        $placeholders = str_repeat('?,', count($category_ids) - 1) . '?';
        
        // الحصول على منتجات مقترحة من الفئات المفضلة
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  JOIN categories c ON p.category_id = c.id 
                  WHERE p.category_id IN ($placeholders) 
                  AND p.status = 'active' 
                  AND p.id NOT IN (
                      SELECT product_id FROM " . $this->table_name . " WHERE user_id = ?
                  ) 
                  ORDER BY p.featured DESC, p.created_at DESC 
                  LIMIT ?";
        
        $params = array_merge($category_ids, [$user_id, $limit]);
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
