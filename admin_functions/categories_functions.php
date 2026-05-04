<?php
// Hàm xử lý Phân loại sản phẩm

function add_category($conn, $data) {
    // Validation
    $category_name = isset($data['category_name']) ? trim($data['category_name']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    
    if (empty($category_name)) {
        return ['success' => false, 'message' => 'Tên phân loại không được để trống'];
    }
    
    if (strlen($category_name) > 100) {
        return ['success' => false, 'message' => 'Tên phân loại không vượt quá 100 ký tự'];
    }

    // Prepared Statement
    $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("ss", $category_name, $description);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Thêm phân loại thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        if (strpos($error, 'Duplicate entry') !== false) {
            return ['success' => false, 'message' => 'Tên phân loại này đã tồn tại'];
        }
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function edit_category($conn, $data) {
    // Validation
    $category_id = isset($data['category_id']) ? intval($data['category_id']) : 0;
    $category_name = isset($data['category_name']) ? trim($data['category_name']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    
    if ($category_id <= 0) {
        return ['success' => false, 'message' => 'ID phân loại không hợp lệ'];
    }
    
    if (empty($category_name)) {
        return ['success' => false, 'message' => 'Tên phân loại không được để trống'];
    }
    
    if (strlen($category_name) > 100) {
        return ['success' => false, 'message' => 'Tên phân loại không vượt quá 100 ký tự'];
    }

    // Prepared Statement
    $stmt = $conn->prepare("UPDATE categories SET category_name=?, description=? WHERE category_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("ssi", $category_name, $description, $category_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Cập nhật phân loại thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        if (strpos($error, 'Duplicate entry') !== false) {
            return ['success' => false, 'message' => 'Tên phân loại này đã được sử dụng'];
        }
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function delete_category($conn, $category_id) {
    // Validation
    $category_id = intval($category_id);
    
    if ($category_id <= 0) {
        return ['success' => false, 'message' => 'ID phân loại không hợp lệ'];
    }
    
    // Kiểm tra xem có sản phẩm trong danh mục này không
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id=?");
    if ($stmt) {
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row['count'] > 0) {
            return ['success' => false, 'message' => 'Không thể xóa danh mục có sản phẩm'];
        }
    }
    
    // Prepared Statement
    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("i", $category_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Xóa phân loại thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function get_all_categories($conn) {
    $sql = "SELECT * FROM categories ORDER BY category_name ASC";
    return $conn->query($sql);
}

function get_category_by_id($conn, $category_id) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id=?");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    $stmt->close();
    
    return $category;
}
?>
