<?php
require_once 'db_connection.php';

function checkPrescription($conn, $prescription_id) {
    $sql = "SELECT * FROM prescriptions WHERE prescription_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        return ["status" => "error", "message" => $conn->error];
    }

    $stmt->bind_param("i", $prescription_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Logic nghiệp vụ: Kiểm tra độ cận an toàn (ví dụ không quá -10.00)
        if (abs($row['right_sph']) > 10.00 || abs($row['left_sph']) > 10.00) {
            return [
                "status" => "warning", 
                "message" => "Độ cận quá cao, cần tư vấn tròng chiết suất cao (1.67, 1.74)."
            ];
        }
        return ["status" => "success", "message" => "Toa kính hợp lệ."];
    }
    return ["status" => "not_found", "message" => "Không tìm thấy toa kính."];
}
?>