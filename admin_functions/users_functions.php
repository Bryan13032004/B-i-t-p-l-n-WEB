<?php
// Hàm xử lý người dùng

function add_user($conn, $data) {
    // Validation
    $username = isset($data['username']) ? trim($data['username']) : '';
    $email = isset($data['email']) ? trim($data['email']) : '';
    $password = isset($data['password']) ? $data['password'] : '';
    $full_name = isset($data['full_name']) ? trim($data['full_name']) : '';
    
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        return ['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin'];
    }
    
    if (strlen($username) < 3 || strlen($username) > 50) {
        return ['success' => false, 'message' => 'Tên đăng nhập phải từ 3-50 ký tự'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email không hợp lệ'];
    }
    
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự'];
    }
    
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Prepared Statement
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("ssss", $username, $email, $password_hash, $full_name);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Thêm nhân viên thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        // Kiểm tra lỗi duplicate
        if (strpos($error, 'Duplicate entry') !== false) {
            return ['success' => false, 'message' => 'Tên đăng nhập hoặc email đã tồn tại'];
        }
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function edit_user($conn, $data) {
    // Validation
    $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
    $email = isset($data['email']) ? trim($data['email']) : '';
    $full_name = isset($data['full_name']) ? trim($data['full_name']) : '';

    if ($user_id <= 0) {
        return ['success' => false, 'message' => 'ID nhân viên không hợp lệ'];
    }
    
    if (empty($email) || empty($full_name)) {
        return ['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email không hợp lệ'];
    }
// Validation
    $user_id = intval($user_id);
    
    if ($user_id <= 0) {
        return ['success' => false, 'message' => 'ID nhân viên không hợp lệ'];
    }
    
    // Xóa user_roles trước
    $stmt = $conn->prepare("DELETE FROM user_roles WHERE user_id=?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Xóa user
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Xóa nhân viên thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error]
        $error = $stmt->error;
        $stmt->close();
        if (strpos($error, 'Duplicate entry') !== false) {
            return ['success' => false, 'message' => 'Email này đã được sử dụng'];
        }
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function delete_user($conn, $user_id) {
    // Validation
    $user_id = intval($user_id);
    $role_id = intval($role_id);
    
    if ($user_id <= 0 || $role_id <= 0) {
        return ['success' => false, 'message' => 'ID không hợp lệ'];
    }
    
    // Xóa vai trò cũ
    $stmt = $conn->prepare("DELETE FROM user_roles WHERE user_id=?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Thêm vai trò mới
    $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi: ' . $conn->error];
    }
    
    $stmt->bind_param("ii", $user_id, $role_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Gán vai trò thành công!'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

function assign_role($conn, $user_id, $role_id) {
    $user_id = intval($user_id);
    $role_id = intval($role_id);
    
    // Xóa vai trò cũ
    $conn->query("DELETE FROM user_roles WHERE user_id=$user_id");
    
    // Thêm vai trò mới
    $sql = "INSERT INTO user_roles (user_id, role_id) VALUES ($user_id, $role_id)";
    
    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        return false;
    }
}
?>
