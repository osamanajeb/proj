<?php
/**
 * فئة إدارة المنتجات
 * Product management class
 */

class Product {
    private $conn;
    private $table_name = "products";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * الحصول على جميع المنتجات
     * Get all products
     */
    public function getAllProducts($limit = null, $offset = 0) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.status = 'active' 
                  ORDER BY p.created_at DESC";
        
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
     * الحصول على المنتجات المميزة
     * Get featured products
     */
    public function getFeaturedProducts($limit = 8) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.status = 'active' AND p.featured = 1 
                  ORDER BY p.created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على منتج بواسطة المعرف
     * Get product by ID
     */
    public function getProductById($id) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.id = :id AND p.status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * البحث في المنتجات
     * Search products
     */
    public function searchProducts($keyword, $category_id = null, $min_price = null, $max_price = null) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.status = 'active' AND (p.name LIKE :keyword OR p.description LIKE :keyword)";
        
        $params = [':keyword' => '%' . $keyword . '%'];
        
        if ($category_id) {
            $query .= " AND p.category_id = :category_id";
            $params[':category_id'] = $category_id;
        }
        
        if ($min_price) {
            $query .= " AND p.price >= :min_price";
            $params[':min_price'] = $min_price;
        }
        
        if ($max_price) {
            $query .= " AND p.price <= :max_price";
            $params[':max_price'] = $max_price;
        }
        
        $query .= " ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على المنتجات حسب الفئة
     * Get products by category
     */
    public function getProductsByCategory($category_id, $limit = null) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.status = 'active' AND p.category_id = :category_id 
                  ORDER BY p.created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT :limit";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        
        if ($limit) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * إضافة منتج جديد
     * Add new product
     */
    public function addProduct($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, description, price, discount_price, stock_quantity, category_id, main_image, featured) 
                  VALUES (:name, :description, :price, :discount_price, :stock_quantity, :category_id, :main_image, :featured)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':price' => $data['price'],
            ':discount_price' => $data['discount_price'],
            ':stock_quantity' => $data['stock_quantity'],
            ':category_id' => $data['category_id'],
            ':main_image' => $data['main_image'],
            ':featured' => $data['featured'] ?? 0
        ]);
    }

    /**
     * تحديث منتج
     * Update product
     */
    public function updateProduct($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, description = :description, price = :price, 
                      discount_price = :discount_price, stock_quantity = :stock_quantity, 
                      category_id = :category_id, main_image = :main_image, featured = :featured 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':price' => $data['price'],
            ':discount_price' => $data['discount_price'],
            ':stock_quantity' => $data['stock_quantity'],
            ':category_id' => $data['category_id'],
            ':main_image' => $data['main_image'],
            ':featured' => $data['featured'] ?? 0
        ]);
    }

    /**
     * حذف منتج
     * Delete product
     */
    public function deleteProduct($id) {
        $query = "UPDATE " . $this->table_name . " SET status = 'inactive' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * الحصول على صور المنتج
     * Get product images
     */
    public function getProductImages($product_id) {
        $query = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_main DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على عدد المنتجات
     * Get products count
     */
    public function getProductsCount($category_id = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = 'active'";
        
        if ($category_id) {
            $query .= " AND category_id = :category_id";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($category_id) {
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}
?>
