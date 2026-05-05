<?php
// Hàm xử lý Ticket hỗ trợ

function add_support_ticket($conn, $data) {
    // Validation
    $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
    $order_id = isset($data['order_id']) ? intval($data['order_id']) : NULL;
    $issue_type = isset($data['issue_type']) ? trim($data['issue_type']) : 'Consultation';
    $title = isset($data['title']) ? trim($data['title']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    $priority = isset($data['priority']) ? trim($data['priority']) : 'Medium';
    
    if ($user_id <= 0) {
        return ['success' => false, 'message' => 'Khách hàng không hợp lệ'];
    }
    
    if (empty($title)) {
        return ['success' => false, 'message' => 'Tiêu đề ticket không được để trống'];
    }
    
    if (strlen($title) > 150) {
        return ['success' => false, 'message' => 'Tiêu đề không vượt quá 150 ký tự'];
    }
    
    if (empty($description)) {
        return ['success' => false, 'message' => 'Mô tả vấn đề không được để trống'];
    }
    
    $valid_types = ['Warranty', 'Return', 'Replacement', 'Refund', 'Consultation'];
    if (!in_array($issue_type, $valid_types)) {
        $issue_type = 'Consultation';
    }
    
    $valid_priorities = ['Low', 'Medium', 'High'];
    if (!in_array($priority, $valid_priorities)) {
        $priority = 'Medium';
    }

    $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, order_id, issue_type, title, description, priority) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("iissss", $user_id, $order_id, $issue_type, $title, $description, $priority);
    
    if ($stmt->execute()) {
        $ticket_id = $conn->insert_id;
        $stmt->close();
        return ['success' => true, 'message' => 'Tạo ticket hỗ trợ thành công!', 'ticket_id' => $ticket_id];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function update_ticket_status($conn, $ticket_id, $status) {
    $ticket_id = intval($ticket_id);
    
    if ($ticket_id <= 0) {
        return ['success' => false, 'message' => 'ID ticket không hợp lệ'];
    }
    
    $valid_statuses = ['Open', 'In_Progress', 'Waiting_Customer', 'Resolved', 'Closed'];
    if (!in_array($status, $valid_statuses)) {
        return ['success' => false, 'message' => 'Trạng thái không hợp lệ'];
    }

    $stmt = $conn->prepare("UPDATE support_tickets SET status=? WHERE ticket_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("si", $status, $ticket_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Cập nhật trạng thái ticket thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function assign_ticket($conn, $ticket_id, $staff_id) {
    $ticket_id = intval($ticket_id);
    $staff_id = intval($staff_id);
    
    if ($ticket_id <= 0) {
        return ['success' => false, 'message' => 'ID ticket không hợp lệ'];
    }
    
    if ($staff_id <= 0) {
        return ['success' => false, 'message' => 'Nhân viên không hợp lệ'];
    }

    $stmt = $conn->prepare("UPDATE support_tickets SET assigned_to=?, status='In_Progress' WHERE ticket_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("ii", $staff_id, $ticket_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Giao ticket cho nhân viên thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function add_ticket_resolution($conn, $ticket_id, $resolution_notes) {
    $ticket_id = intval($ticket_id);
    $resolution_notes = trim($resolution_notes);
    
    if ($ticket_id <= 0) {
        return ['success' => false, 'message' => 'ID ticket không hợp lệ'];
    }
    
    if (empty($resolution_notes)) {
        return ['success' => false, 'message' => 'Ghi chú giải quyết không được để trống'];
    }

    $stmt = $conn->prepare("UPDATE support_tickets SET resolution_notes=?, status='Resolved', resolved_at=NOW() WHERE ticket_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("si", $resolution_notes, $ticket_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Ghi nhận giải quyết ticket thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function get_all_tickets($conn, $status = null) {
    if ($status) {
        $stmt = $conn->prepare("SELECT t.*, u.full_name as customer_name, o.order_id, s.full_name as assigned_staff_name 
                               FROM support_tickets t 
                               LEFT JOIN users u ON t.user_id = u.user_id
                               LEFT JOIN orders o ON t.order_id = o.order_id
                               LEFT JOIN users s ON t.assigned_to = s.user_id
                               WHERE t.status = ?
                               ORDER BY t.created_at DESC");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        return $stmt->get_result();
    } else {
        $sql = "SELECT t.*, u.full_name as customer_name, o.order_id, s.full_name as assigned_staff_name 
                FROM support_tickets t 
                LEFT JOIN users u ON t.user_id = u.user_id
                LEFT JOIN orders o ON t.order_id = o.order_id
                LEFT JOIN users s ON t.assigned_to = s.user_id
                ORDER BY t.created_at DESC";
        return $conn->query($sql);
    }
}

function get_ticket_by_id($conn, $ticket_id) {
    $stmt = $conn->prepare("SELECT t.*, u.full_name as customer_name, o.order_id, s.full_name as assigned_staff_name 
                           FROM support_tickets t 
                           LEFT JOIN users u ON t.user_id = u.user_id
                           LEFT JOIN orders o ON t.order_id = o.order_id
                           LEFT JOIN users s ON t.assigned_to = s.user_id
                           WHERE t.ticket_id=?");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();
    $stmt->close();
    
    return $ticket;
}

function close_ticket($conn, $ticket_id) {
    $ticket_id = intval($ticket_id);
    
    if ($ticket_id <= 0) {
        return ['success' => false, 'message' => 'ID ticket không hợp lệ'];
    }

    $stmt = $conn->prepare("UPDATE support_tickets SET status='Closed' WHERE ticket_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("i", $ticket_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Đóng ticket thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}
?>
