<?php
// Hàm xử lý Đơn hàng

function add_order($conn, $data) {
    // Validation
    $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
    $sales_staff_id = isset($data['sales_staff_id']) ? intval($data['sales_staff_id']) : NULL;
    $promo_id = isset($data['promo_id']) ? intval($data['promo_id']) : NULL;
    $customer_name = isset($data['customer_name']) ? trim($data['customer_name']) : '';
    $customer_email = isset($data['customer_email']) ? trim($data['customer_email']) : '';
    $customer_phone = isset($data['customer_phone']) ? trim($data['customer_phone']) : '';
    $shipping_address = isset($data['shipping_address']) ? trim($data['shipping_address']) : '';
    $order_type = isset($data['order_type']) ? trim($data['order_type']) : 'Ready_Stock';
    $notes = isset($data['notes']) ? trim($data['notes']) : '';
    
    if ($user_id <= 0) {
        return ['success' => false, 'message' => 'Khách hàng không hợp lệ'];
    }
    
    if (empty($customer_name)) {
        return ['success' => false, 'message' => 'Tên khách hàng không được để trống'];
    }
    
    if (empty($customer_email) || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email khách hàng không hợp lệ'];
    }
    
    if (empty($shipping_address)) {
        return ['success' => false, 'message' => 'Địa chỉ giao hàng không được để trống'];
    }

    // Prepared Statement
    $stmt = $conn->prepare("INSERT INTO orders (user_id, sales_staff_id, promo_id, customer_name, customer_email, customer_phone, shipping_address, order_type, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("iiisissss", $user_id, $sales_staff_id, $promo_id, $customer_name, $customer_email, $customer_phone, $shipping_address, $order_type, $notes);
    
    if ($stmt->execute()) {
        $order_id = $conn->insert_id;
        $stmt->close();
        return ['success' => true, 'message' => 'Tạo đơn hàng thành công!', 'order_id' => $order_id];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function update_order_status($conn, $order_id, $status) {
    $order_id = intval($order_id);
    
    if ($order_id <= 0) {
        return ['success' => false, 'message' => 'ID đơn hàng không hợp lệ'];
    }
    
    $valid_statuses = ['Pending', 'Confirmed', 'Processing', 'Ready_to_Ship', 'Shipped', 'Delivered', 'Cancelled'];
    if (!in_array($status, $valid_statuses)) {
        return ['success' => false, 'message' => 'Trạng thái không hợp lệ'];
    }

    $stmt = $conn->prepare("UPDATE orders SET status=? WHERE order_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Cập nhật trạng thái thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function add_order_item($conn, $order_id, $variant_id, $quantity, $unit_price) {
    $order_id = intval($order_id);
    $variant_id = intval($variant_id);
    $quantity = intval($quantity);
    $unit_price = floatval($unit_price);
    $prescription_id = null;
    
    if ($order_id <= 0 || $variant_id <= 0 || $quantity <= 0 || $unit_price <= 0) {
        return ['success' => false, 'message' => 'Dữ liệu không hợp lệ'];
    }
    
    $total_price = $quantity * $unit_price;

    $stmt = $conn->prepare("INSERT INTO order_items (order_id, variant_id, prescription_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("iiiid", $order_id, $variant_id, $prescription_id, $quantity, $unit_price, $total_price);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Thêm mặt hàng vào đơn thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function get_all_orders($conn) {
    $sql = "SELECT o.*, u.full_name as customer_name_user FROM orders o LEFT JOIN users u ON o.user_id = u.user_id ORDER BY o.order_date DESC";
    return $conn->query($sql);
}

function get_order_by_id($conn, $order_id) {
    $stmt = $conn->prepare("SELECT o.*, u.full_name, u.email, u.phone FROM orders o LEFT JOIN users u ON o.user_id = u.user_id WHERE o.order_id=?");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    return $order;
}

function get_order_items($conn, $order_id) {
    $stmt = $conn->prepare("SELECT oi.*, pv.sku, p.product_name FROM order_items oi 
                           LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
                           LEFT JOIN products p ON pv.product_id = p.product_id
                           WHERE oi.order_id=?");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    return $stmt->get_result();
}

function delete_order($conn, $order_id) {
    $order_id = intval($order_id);
    
    if ($order_id <= 0) {
        return ['success' => false, 'message' => 'ID đơn hàng không hợp lệ'];
    }
    
    // Xóa order_items trước
    $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id=?");
    if ($stmt) {
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Xóa order
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Xóa đơn hàng thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}
?>
