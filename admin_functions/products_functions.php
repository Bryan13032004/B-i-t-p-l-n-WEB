<?php
// Hàm xử lý Sản phẩm

function add_product($conn, $data) {
    // Validation
    $category_id = isset($data['category_id']) ? intval($data['category_id']) : 0;
    $product_name = isset($data['product_name']) ? trim($data['product_name']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    $base_price = isset($data['base_price']) ? floatval($data['base_price']) : 0;
    $image_url = isset($data['image_url']) ? trim($data['image_url']) : '';
    
    if ($category_id <= 0) {
        return ['success' => false, 'message' => 'Vui lòng chọn phân loại'];
    }
    
    if (empty($product_name)) {
        return ['success' => false, 'message' => 'Tên sản phẩm không được để trống'];
    }
    
    if (strlen($product_name) > 150) {
        return ['success' => false, 'message' => 'Tên sản phẩm không vượt quá 150 ký tự'];
    }
    
    if ($base_price <= 0) {
        return ['success' => false, 'message' => 'Giá sản phẩm phải lớn hơn 0'];
    }

    // Prepared Statement
    $stmt = $conn->prepare("INSERT INTO products (category_id, product_name, description, base_price, image_url) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("issds", $category_id, $product_name, $description, $base_price, $image_url);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Thêm sản phẩm thành công!', 'product_id' => $conn->insert_id];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function edit_product($conn, $data) {
    // Validation
    $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
    $category_id = isset($data['category_id']) ? intval($data['category_id']) : 0;
    $product_name = isset($data['product_name']) ? trim($data['product_name']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    $base_price = isset($data['base_price']) ? floatval($data['base_price']) : 0;
    $image_url = isset($data['image_url']) ? trim($data['image_url']) : '';
    $status = isset($data['status']) ? trim($data['status']) : 'Active';
    
    if ($product_id <= 0) {
        return ['success' => false, 'message' => 'ID sản phẩm không hợp lệ'];
    }
    
    if ($category_id <= 0) {
        return ['success' => false, 'message' => 'Vui lòng chọn phân loại'];
    }
    
    if (empty($product_name)) {
        return ['success' => false, 'message' => 'Tên sản phẩm không được để trống'];
    }
    
    if ($base_price <= 0) {
        return ['success' => false, 'message' => 'Giá sản phẩm phải lớn hơn 0'];
    }

    // Prepared Statement
    $stmt = $conn->prepare("UPDATE products SET category_id=?, product_name=?, description=?, base_price=?, image_url=?, status=? WHERE product_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("issdssi", $category_id, $product_name, $description, $base_price, $image_url, $status, $product_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Cập nhật sản phẩm thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function delete_product($conn, $product_id) {
    // Validation
    $product_id = intval($product_id);
    
    if ($product_id <= 0) {
        return ['success' => false, 'message' => 'ID sản phẩm không hợp lệ'];
    }
    
    // Xóa variants trước
    $stmt = $conn->prepare("DELETE FROM product_variants WHERE product_id=?");
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Xóa product
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Xóa sản phẩm thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function get_all_products($conn, $category_id = null) {
    if ($category_id !== null) {
        $stmt = $conn->prepare("SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.category_id = ? ORDER BY p.product_name ASC");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        return $stmt->get_result();
    } else {
        $sql = "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id ORDER BY p.product_name ASC";
        return $conn->query($sql);
    }
}

function get_product_by_id($conn, $product_id) {
    $stmt = $conn->prepare("SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.product_id=?");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    return $product;
}

// Hàm xử lý Biến thể sản phẩm
function add_product_variant($conn, $data) {
    // Validation
    $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
    $sku = isset($data['sku']) ? trim($data['sku']) : '';
    $color = isset($data['color']) ? trim($data['color']) : '';
    $size = isset($data['size']) ? trim($data['size']) : '';
    $quantity_in_stock = isset($data['quantity_in_stock']) ? intval($data['quantity_in_stock']) : 0;
    $price = isset($data['price']) ? floatval($data['price']) : 0;
    
    if ($product_id <= 0) {
        return ['success' => false, 'message' => 'ID sản phẩm không hợp lệ'];
    }
    
    if (empty($sku)) {
        return ['success' => false, 'message' => 'SKU không được để trống'];
    }
    
    if ($quantity_in_stock < 0) {
        return ['success' => false, 'message' => 'Số lượng không được âm'];
    }
    
    if ($price <= 0) {
        return ['success' => false, 'message' => 'Giá không được để trống hoặc <= 0'];
    }

    // Prepared Statement
    $stmt = $conn->prepare("INSERT INTO product_variants (product_id, sku, color, size, quantity_in_stock, price) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("isssid", $product_id, $sku, $color, $size, $quantity_in_stock, $price);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Thêm biến thể thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        if (strpos($error, 'Duplicate entry') !== false) {
            return ['success' => false, 'message' => 'SKU này đã tồn tại'];
        }
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function edit_product_variant($conn, $data) {
    // Validation
    $variant_id = isset($data['variant_id']) ? intval($data['variant_id']) : 0;
    $sku = isset($data['sku']) ? trim($data['sku']) : '';
    $color = isset($data['color']) ? trim($data['color']) : '';
    $size = isset($data['size']) ? trim($data['size']) : '';
    $quantity_in_stock = isset($data['quantity_in_stock']) ? intval($data['quantity_in_stock']) : 0;
    $price = isset($data['price']) ? floatval($data['price']) : 0;
    
    if ($variant_id <= 0) {
        return ['success' => false, 'message' => 'ID biến thể không hợp lệ'];
    }
    
    if (empty($sku)) {
        return ['success' => false, 'message' => 'SKU không được để trống'];
    }
    
    if ($quantity_in_stock < 0) {
        return ['success' => false, 'message' => 'Số lượng không được âm'];
    }
    
    if ($price <= 0) {
        return ['success' => false, 'message' => 'Giá không được để trống hoặc <= 0'];
    }

    // Prepared Statement
    $stmt = $conn->prepare("UPDATE product_variants SET sku=?, color=?, size=?, quantity_in_stock=?, price=? WHERE variant_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("sssidi", $sku, $color, $size, $quantity_in_stock, $price, $variant_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Cập nhật biến thể thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        if (strpos($error, 'Duplicate entry') !== false) {
            return ['success' => false, 'message' => 'SKU này đã được sử dụng'];
        }
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function delete_product_variant($conn, $variant_id) {
    // Validation
    $variant_id = intval($variant_id);
    
    if ($variant_id <= 0) {
        return ['success' => false, 'message' => 'ID biến thể không hợp lệ'];
    }
    
    // Prepared Statement
    $stmt = $conn->prepare("DELETE FROM product_variants WHERE variant_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("i", $variant_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Xóa biến thể thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function get_variants_by_product($conn, $product_id) {
    $stmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id=? ORDER BY color, size");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    return $stmt->get_result();
}

function get_variant_by_id($conn, $variant_id) {
    $stmt = $conn->prepare("SELECT * FROM product_variants WHERE variant_id=?");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $variant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $variant = $result->fetch_assoc();
    $stmt->close();
    
    return $variant;
}
?>
