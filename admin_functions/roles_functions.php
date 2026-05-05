<?php
// Hàm xử lý vai trò

function add_role($conn, $data) {
    // Validation
    $role_name = isset($data['role_name']) ? trim($data['role_name']) : '';
    $role_description = isset($data['role_description']) ? trim($data['role_description']) : '';
    
    if (empty($role_name)) {
        return ['success' => false, 'message' => 'Tên vai trò không được để trống'];
    }
    
    if (strlen($role_name) > 50) {
        return ['success' => false, 'message' => 'Tên vai trò không vượt quá 50 ký tự'];
    }

    // Prepared Statement
    $stmt = $conn->prepare("INSERT INTO roles (role_name, role_description) VALUES (?, ?)");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("ss", $role_name, $role_description);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Thêm vai trò thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function edit_role($conn, $data) {
    // Validation
    $role_id = isset($data['role_id']) ? intval($data['role_id']) : 0;
    $role_name = isset($data['role_name']) ? trim($data['role_name']) : '';
    $role_description = isset($data['role_description']) ? trim($data['role_description']) : '';
    
    if ($role_id <= 0) {
        return ['success' => false, 'message' => 'ID vai trò không hợp lệ'];
    }
    
    if (empty($role_name)) {
        return ['success' => false, 'message' => 'Tên vai trò không được để trống'];
    }
    
    if (strlen($role_name) > 50) {
        return ['success' => false, 'message' => 'Tên vai trò không vượt quá 50 ký tự'];
    }

    // Validation
    $role_id = intval($role_id);
    
    if ($role_id <= 0) {
        return ['success' => false, 'message' => 'ID vai trò không hợp lệ'];
    }
    
    // Xóa user_roles trước (dùng Prepared Statement)
    $stmt = $conn->prepare("DELETE FROM user_roles WHERE role_id=?");
    if ($stmt) {
        $stmt->bind_param("i", $role_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Xóa role
    $stmt = $conn->prepare("DELETE FROM roles WHERE role_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("i", $role_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Xóa vai trò thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function delete_role($conn, $role_id) {
    $role_id = intval($role_id);
    
    // Xóa user_roles trước
    $conn->query("DELETE FROM user_roles WHERE role_id=$role_id");
    
    $sql = "DELETE FROM roles WHERE role_id=$role_id";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>Xóa vai trò thành công!</div>";
        return true;
    } else {
        echo "<div class='error'>Lỗi: " . $conn->error . "</div>";
        return false;
    }
}

function get_all_roles($conn) {
    $sql = "SELECT * FROM roles";
    return $conn->query($sql);
}
?>
