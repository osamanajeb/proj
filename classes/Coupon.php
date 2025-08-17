<?php
/**
 * فئة إدارة الكوبونات
 * Coupon management class
 */

class Coupon {
    private $conn;
    private $table_name = "coupons";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * إنشاء كوبون جديد
     * Create new coupon
     */
    public function createCoupon($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (code, type, value, min_amount, max_uses, expires_at, description) 
                  VALUES (:code, :type, :value, :min_amount, :max_uses, :expires_at, :description)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':code' => strtoupper($data['code']),
            ':type' => $data['type'],
            ':value' => $data['value'],
            ':min_amount' => $data['min_amount'],
            ':max_uses' => $data['max_uses'],
            ':expires_at' => $data['expires_at'],
            ':description' => $data['description']
        ]);
    }

    /**
     * التحقق من صحة الكوبون
     * Validate coupon
     */
    public function validateCoupon($code, $cart_total = 0) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE code = :code AND status = 'active' 
                  AND (expires_at IS NULL OR expires_at > NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':code' => strtoupper($code)]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            return ['valid' => false, 'message' => 'كوبون غير صحيح أو منتهي الصلاحية'];
        }
        
        // التحقق من الحد الأدنى للمبلغ
        if ($coupon['min_amount'] && $cart_total < $coupon['min_amount']) {
            return [
                'valid' => false, 
                'message' => 'الحد الأدنى للطلب ' . format_price($coupon['min_amount'])
            ];
        }
        
        // التحقق من عدد الاستخدامات
        if ($coupon['max_uses'] && $coupon['used_count'] >= $coupon['max_uses']) {
            return ['valid' => false, 'message' => 'تم استنفاد عدد استخدامات هذا الكوبون'];
        }
        
        return ['valid' => true, 'coupon' => $coupon];
    }

    /**
     * تطبيق الكوبون وحساب الخصم
     * Apply coupon and calculate discount
     */
    public function applyCoupon($code, $cart_total) {
        $validation = $this->validateCoupon($code, $cart_total);
        
        if (!$validation['valid']) {
            return $validation;
        }
        
        $coupon = $validation['coupon'];
        $discount = 0;
        
        if ($coupon['type'] === 'percentage') {
            $discount = ($cart_total * $coupon['value']) / 100;
        } else {
            $discount = $coupon['value'];
        }
        
        // التأكد من أن الخصم لا يتجاوز المبلغ الإجمالي
        $discount = min($discount, $cart_total);
        
        return [
            'valid' => true,
            'discount' => $discount,
            'coupon' => $coupon,
            'message' => 'تم تطبيق الكوبون بنجاح'
        ];
    }

    /**
     * استخدام الكوبون
     * Use coupon
     */
    public function useCoupon($coupon_id, $user_id = null, $order_id = null) {
        try {
            $this->conn->beginTransaction();
            
            // زيادة عداد الاستخدام
            $query = "UPDATE " . $this->table_name . " SET used_count = used_count + 1 WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id' => $coupon_id]);
            
            // تسجيل استخدام الكوبون
            $query = "INSERT INTO coupon_usage (coupon_id, user_id, order_id) VALUES (:coupon_id, :user_id, :order_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':coupon_id' => $coupon_id,
                ':user_id' => $user_id,
                ':order_id' => $order_id
            ]);
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * الحصول على جميع الكوبونات
     * Get all coupons
     */
    public function getAllCoupons() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على كوبون بواسطة المعرف
     * Get coupon by ID
     */
    public function getCouponById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * تحديث كوبون
     * Update coupon
     */
    public function updateCoupon($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET code = :code, type = :type, value = :value, 
                      min_amount = :min_amount, max_uses = :max_uses, 
                      expires_at = :expires_at, description = :description 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $id,
            ':code' => strtoupper($data['code']),
            ':type' => $data['type'],
            ':value' => $data['value'],
            ':min_amount' => $data['min_amount'],
            ':max_uses' => $data['max_uses'],
            ':expires_at' => $data['expires_at'],
            ':description' => $data['description']
        ]);
    }

    /**
     * تحديث حالة الكوبون
     * Update coupon status
     */
    public function updateCouponStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $id,
            ':status' => $status
        ]);
    }

    /**
     * حذف كوبون
     * Delete coupon
     */
    public function deleteCoupon($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * الحصول على الكوبونات النشطة
     * Get active coupons
     */
    public function getActiveCoupons() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE status = 'active' 
                  AND (expires_at IS NULL OR expires_at > NOW()) 
                  AND (max_uses IS NULL OR used_count < max_uses) 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على إحصائيات الكوبونات
     * Get coupon statistics
     */
    public function getCouponStats() {
        $stats = [];
        
        // إجمالي الكوبونات
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // الكوبونات النشطة
        $query = "SELECT COUNT(*) as active FROM " . $this->table_name . " WHERE status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
        
        // إجمالي الاستخدامات
        $query = "SELECT SUM(used_count) as total_uses FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_uses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_uses'] ?: 0;
        
        return $stats;
    }

    /**
     * التحقق من وجود كود الكوبون
     * Check if coupon code exists
     */
    public function codeExists($code, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE code = :code";
        $params = [':code' => strtoupper($code)];
        
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
            $params[':exclude_id'] = $exclude_id;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /**
     * توليد كود كوبون عشوائي
     * Generate random coupon code
     */
    public function generateCouponCode($length = 8) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
        } while ($this->codeExists($code));
        
        return $code;
    }
}
?>
