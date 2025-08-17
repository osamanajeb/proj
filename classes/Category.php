<?php
/**
 * فئة إدارة الفئات
 * Category management class
 */

class Category {
    private $conn;
    private $table_name = "categories";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * الحصول على جميع الفئات
     * Get all categories
     */
    public function getAllCategories() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على فئة بواسطة المعرف
     * Get category by ID
     */
    public function getCategoryById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * إضافة فئة جديدة
     * Add new category
     */
    public function addCategory($data) {
        $query = "INSERT INTO " . $this->table_name . " (name, description, image) VALUES (:name, :description, :image)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':image' => $data['image']
        ]);
    }

    /**
     * تحديث فئة
     * Update category
     */
    public function updateCategory($id, $data) {
        $query = "UPDATE " . $this->table_name . " SET name = :name, description = :description, image = :image WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':image' => $data['image']
        ]);
    }

    /**
     * حذف فئة
     * Delete category
     */
    public function deleteCategory($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * الحصول على عدد المنتجات في الفئة
     * Get products count in category
     */
    public function getProductsCount($category_id) {
        $query = "SELECT COUNT(*) as total FROM products WHERE category_id = :category_id AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}
?>
