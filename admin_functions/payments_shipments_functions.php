<?php
// Hàm xử lý Thanh toán

function add_payment($conn, $order_id, $payment_method, $amount) {
    $order_id = intval($order_id);
    $amount = floatval($amount);
    
    if ($order_id <= 0 || $amount <= 0) {
        return ['success' => false, 'message' => 'Dữ liệu không hợp lệ'];
    }
    
    $valid_methods = ['COD', 'Bank_Transfer', 'Credit_Card', 'Online_Payment'];
    if (!in_array($payment_method, $valid_methods)) {
        return ['success' => false, 'message' => 'Phương thức thanh toán không hợp lệ'];
    }

    $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_method, amount, payment_status) VALUES (?, ?, ?, 'Pending')");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("isd", $order_id, $payment_method, $amount);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Thêm thông tin thanh toán thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function update_payment_status($conn, $payment_id, $status) {
    $payment_id = intval($payment_id);
    
    if ($payment_id <= 0) {
        return ['success' => false, 'message' => 'ID thanh toán không hợp lệ'];
    }
    
    $valid_statuses = ['Pending', 'Completed', 'Failed', 'Refunded'];
    if (!in_array($status, $valid_statuses)) {
        return ['success' => false, 'message' => 'Trạng thái không hợp lệ'];
    }

    $stmt = $conn->prepare("UPDATE payments SET payment_status=?, payment_date=NOW() WHERE payment_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("si", $status, $payment_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Cập nhật trạng thái thanh toán thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function get_payment_by_order($conn, $order_id) {
    $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id=?");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    $stmt->close();
    
    return $payment;
}

// Hàm xử lý Vận chuyển

function add_shipment($conn, $order_id, $ops_staff_id, $shipping_method) {
    $order_id = intval($order_id);
    $ops_staff_id = intval($ops_staff_id);
    
    if ($order_id <= 0) {
        return ['success' => false, 'message' => 'ID đơn hàng không hợp lệ'];
    }
    
    if (empty($shipping_method)) {
        $shipping_method = 'Standard';
    }

    $stmt = $conn->prepare("INSERT INTO shipments (order_id, ops_staff_id, shipping_method, status) VALUES (?, ?, ?, 'Packed')");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("iis", $order_id, $ops_staff_id, $shipping_method);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Tạo phiếu vận chuyển thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function update_shipment_tracking($conn, $shipment_id, $tracking_number, $carrier) {
    $shipment_id = intval($shipment_id);
    
    if ($shipment_id <= 0) {
        return ['success' => false, 'message' => 'ID vận chuyển không hợp lệ'];
    }
    
    if (empty($tracking_number)) {
        return ['success' => false, 'message' => 'Mã vận đơn không được để trống'];
    }

    $stmt = $conn->prepare("UPDATE shipments SET tracking_number=?, carrier=?, shipped_date=NOW(), status='Shipped' WHERE shipment_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("ssi", $tracking_number, $carrier, $shipment_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Cập nhật mã vận đơn thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function update_shipment_delivery($conn, $shipment_id) {
    $shipment_id = intval($shipment_id);
    
    if ($shipment_id <= 0) {
        return ['success' => false, 'message' => 'ID vận chuyển không hợp lệ'];
    }

    $stmt = $conn->prepare("UPDATE shipments SET status='Delivered', delivery_date=NOW() WHERE shipment_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("i", $shipment_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Cập nhật trạng thái giao hàng thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function get_shipment_by_order($conn, $order_id) {
    $stmt = $conn->prepare("SELECT s.*, u.full_name as ops_staff_name FROM shipments s LEFT JOIN users u ON s.ops_staff_id = u.user_id WHERE s.order_id=?");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $shipment = $result->fetch_assoc();
    $stmt->close();
    
    return $shipment;
}

function get_all_shipments($conn) {
    $sql = "SELECT s.*, o.customer_name, u.full_name as ops_staff_name FROM shipments s 
            LEFT JOIN orders o ON s.order_id = o.order_id
            LEFT JOIN users u ON s.ops_staff_id = u.user_id
            ORDER BY s.created_at DESC";
    return $conn->query($sql);
}
?>
