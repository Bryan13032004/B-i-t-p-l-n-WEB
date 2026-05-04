<?php
// Hàm xử lý Khuyến mại (Promotions)

function add_promotion($conn, $data) {
    // Validation
    $promo_code = isset($data['promo_code']) ? strtoupper(trim($data['promo_code'])) : '';
    $discount_type = isset($data['discount_type']) ? trim($data['discount_type']) : 'Percentage';
    $discount_value = isset($data['discount_value']) ? floatval($data['discount_value']) : 0;
    $valid_from = isset($data['valid_from']) ? $data['valid_from'] : date('Y-m-d');
    $valid_to = isset($data['valid_to']) ? $data['valid_to'] : date('Y-m-d', strtotime('+30 days'));
    $max_uses = isset($data['max_uses']) ? intval($data['max_uses']) : 999;
    $min_order_value = isset($data['min_order_value']) ? floatval($data['min_order_value']) : 0;
    
    if (empty($promo_code)) {
        return ['success' => false, 'message' => 'Mã khuyến mại không được để trống'];
    }
    
    if (strlen($promo_code) > 20) {
        return ['success' => false, 'message' => 'Mã khuyến mại không vượt quá 20 ký tự'];
    }
    
    // Kiểm tra mã đã tồn tại
    $stmt = $conn->prepare("SELECT promo_id FROM promotions WHERE promo_code=?");
    $stmt->bind_param("s", $promo_code);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Mã khuyến mại đã tồn tại'];
    }
    $stmt->close();
    
    if ($discount_value <= 0) {
        return ['success' => false, 'message' => 'Giá trị giảm phải lớn hơn 0'];
    }
    
    if ($discount_type == 'Percentage' && $discount_value > 100) {
        return ['success' => false, 'message' => 'Phần trăm không được vượt quá 100%'];
    }

    $stmt = $conn->prepare("INSERT INTO promotions (promo_code, discount_type, discount_value, valid_from, valid_to, max_uses, min_order_value) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("ssddsid", $promo_code, $discount_type, $discount_value, $valid_from, $valid_to, $max_uses, $min_order_value);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Tạo khuyến mại thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function edit_promotion($conn, $promo_id, $data) {
    $promo_id = intval($promo_id);
    
    $discount_type = isset($data['discount_type']) ? trim($data['discount_type']) : 'Percentage';
    $discount_value = isset($data['discount_value']) ? floatval($data['discount_value']) : 0;
    $valid_from = isset($data['valid_from']) ? $data['valid_from'] : date('Y-m-d');
    $valid_to = isset($data['valid_to']) ? $data['valid_to'] : date('Y-m-d');
    $max_uses = isset($data['max_uses']) ? intval($data['max_uses']) : 999;
    $min_order_value = isset($data['min_order_value']) ? floatval($data['min_order_value']) : 0;
    
    if ($promo_id <= 0) {
        return ['success' => false, 'message' => 'ID khuyến mại không hợp lệ'];
    }
    
    if ($discount_value <= 0) {
        return ['success' => false, 'message' => 'Giá trị giảm phải lớn hơn 0'];
    }

    $stmt = $conn->prepare("UPDATE promotions SET discount_type=?, discount_value=?, valid_from=?, valid_to=?, max_uses=?, min_order_value=? WHERE promo_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("sdddsii", $discount_type, $discount_value, $valid_from, $valid_to, $max_uses, $min_order_value, $promo_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Cập nhật khuyến mại thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function delete_promotion($conn, $promo_id) {
    $promo_id = intval($promo_id);
    
    if ($promo_id <= 0) {
        return ['success' => false, 'message' => 'ID khuyến mại không hợp lệ'];
    }
    
    $stmt = $conn->prepare("DELETE FROM promotions WHERE promo_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("i", $promo_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Xóa khuyến mại thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function get_all_promotions($conn) {
    $sql = "SELECT * FROM promotions ORDER BY created_at DESC";
    return $conn->query($sql);
}

function get_promotion_by_id($conn, $promo_id) {
    $stmt = $conn->prepare("SELECT * FROM promotions WHERE promo_id=?");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $promo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $promo = $result->fetch_assoc();
    $stmt->close();
    
    return $promo;
}

function validate_promo_code($conn, $promo_code, $order_total) {
    $stmt = $conn->prepare("SELECT * FROM promotions WHERE promo_code=? AND valid_from <= NOW() AND valid_to >= NOW()");
    if (!$stmt) {
        return ['valid' => false, 'message' => 'Lỗi hệ thống'];
    }
    
    $stmt->bind_param("s", $promo_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $stmt->close();
        return ['valid' => false, 'message' => 'Mã khuyến mại không hợp lệ hoặc đã hết hạn'];
    }
    
    $promo = $result->fetch_assoc();
    $stmt->close();
    
    if ($order_total < $promo['min_order_value']) {
        return ['valid' => false, 'message' => 'Đơn hàng không đủ giá trị tối thiểu'];
    }
    
    if ($promo['current_uses'] >= $promo['max_uses']) {
        return ['valid' => false, 'message' => 'Mã khuyến mại đã hết lượt sử dụng'];
    }
    
    // Tính discount
    $discount = ($promo['discount_type'] == 'Percentage') ? ($order_total * $promo['discount_value'] / 100) : $promo['discount_value'];
    
    return ['valid' => true, 'discount' => $discount, 'promo_id' => $promo['promo_id']];
}
?>
