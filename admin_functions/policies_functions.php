<?php
// Hàm xử lý chính sách

function add_policy($conn, $data) {
    // Validation
    $policy_name = isset($data['policy_name']) ? trim($data['policy_name']) : '';
    $policy_description = isset($data['policy_description']) ? trim($data['policy_description']) : '';
    $warranty_period = isset($data['warranty_period']) && !empty($data['warranty_period']) ? intval($data['warranty_period']) : NULL;
    $return_period = isset($data['return_period']) && !empty($data['return_period']) ? intval($data['return_period']) : NULL;
    
    if (empty($policy_name) || empty($policy_description)) {
        return ['success' => false, 'message' => 'Vui lòng điền tên và mô tả chính sách'];
    }
    
    if (strlen($policy_name) > 100) {
        return ['success' => false, 'message' => 'Tên chính sách không vượt quá 100 ký tự'];
    }

    // Prepared Statement
    $stmt = $conn->prepare("INSERT INTO policies_and_configs (policy_name, policy_description, warranty_period, return_period) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("ssii", $policy_name, $policy_description, $warranty_period, $return_period);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Thêm chính sách thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function edit_policy($conn, $data) {
    // Validation
    $policy_id = isset($data['policy_id']) ? intval($data['policy_id']) : 0;
    $policy_name = isset($data['policy_name']) ? trim($data['policy_name']) : '';
    $policy_description = isset($data['policy_description']) ? trim($data['policy_description']) : '';
    $warranty_period = isset($data['warranty_period']) && !empty($data['warranty_period']) ? intval($data['warranty_period']) : NULL;
    $return_period = isset($data['return_period']) && !empty($data['return_period']) ? intval($data['return_period']) : NULL;
    
    if ($policy_id <= 0) {
        return ['success' => false, 'message' => 'ID chính sách không hợp lệ'];
    }
    
    if (empty($policy_name) || empty($policy_description)) {
        return ['success' => false, 'message' => 'Vui lòng điền tên và mô tả chính sách'];
    }
    
    if (strlen($policy_name) > 100) {
        return ['success' => false, 'message' => 'Tên chính sách không vượt quá 100 ký tự'];
    }

    // Prepared Statement
    $stmt = $conn->prepare("UPDATE policies_and_configs SET policy_name=?, policy_description=?, warranty_period=?, return_period=? WHERE policy_id=?");
    // Validation
    $policy_id = intval($policy_id);
    
    if ($policy_id <= 0) {
        return ['success' => false, 'message' => 'ID chính sách không hợp lệ'];
    }
    
    // Prepared Statement
    $stmt = $conn->prepare("DELETE FROM policies_and_configs WHERE policy_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("i", $policy_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Xóa chính sách thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error]
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function delete_policy($conn, $policy_id) {
    $policy_id = intval($policy_id);
    
    $sql = "DELETE FROM policies_and_configs WHERE policy_id=$policy_id";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>Xóa chính sách thành công!</div>";
        return true;
    } else {
        echo "<div class='error'>Lỗi: " . $conn->error . "</div>";
        return false;
    }
}

function get_all_policies($conn) {
    $sql = "SELECT * FROM policies_and_configs";
    return $conn->query($sql);
}
?>
