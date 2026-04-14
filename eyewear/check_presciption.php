<?php
// Tên file: check_prescription.php
require 'db_connection.php'; // Gọi file kết nối database

function validatePrescription($conn, $customer_id) {
    // Truy vấn lấy toa kính mới nhất của khách hàng
    $sql = "SELECT right_sph, left_sph, pd 
            FROM prescriptions 
            WHERE customer_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1";

    // Dùng prepared statement để tránh lỗi SQL injection
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        echo "<p style='color:red;'>Lỗi truy vấn: " . $conn->error . "</p>";
        return false;
    }

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $right = $row['right_sph'];
        $left = $row['left_sph'];
        $pd = $row['pd'];

        echo "Độ cận mắt phải: <strong>$right</strong> | 
              Mắt trái: <strong>$left</strong> | 
              Khoảng cách đồng tử: <strong>$pd</strong> <br>";

        // Logic kiểm tra nghiệp vụ Sales
        if ($right < -10.00 || $left < -10.00) {
            echo "<p style='color:red;'>⚠️ Cảnh báo: Độ cận quá cao, hệ thống tạm giữ đơn. 
                  Vui lòng liên hệ tư vấn khách hàng đổi sang tròng chiết suất siêu mỏng!</p>";
            return false;
        } else {
            echo "<p style='color:green;'>✅ Toa kính hợp lệ. 
                  Thông số kỹ thuật phù hợp để tiến hành tạo đơn hàng.</p>";
            return true;
        }
    } else {
        echo "<p style='color:orange;'>Khách hàng này chưa có hồ sơ đo mắt điện tử.</p>";
        return false;
    }
}

// Chạy thử logic với khách hàng có ID = 1 (dữ liệu mẫu)
echo "<h3>--- HỆ THỐNG SALES: KIỂM TRA TOA KÍNH ---</h3>";
validatePrescription($conn, 1);

$conn->close();
?>
