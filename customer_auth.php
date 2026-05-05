<?php
session_start();
require 'db_connection.php';

// Nếu đã đăng nhập thì về trang chủ
if (isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$tab = $_GET['tab'] ?? 'login';

// Xử lý ĐĂNG NHẬP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            if (password_verify($password, $customer['password'])) {
                $_SESSION['customer_id']   = $customer['customer_id'];
                $_SESSION['customer_name'] = $customer['customer_name'];
                $_SESSION['customer_email']= $customer['email'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Mật khẩu không đúng!';
            }
        } else {
            $error = 'Email không tồn tại!';
        }
        $stmt->close();
    }
    $tab = 'login';
}

// Xử lý ĐĂNG KÝ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'register') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ!';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } else {
        // Kiểm tra email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email này đã được đăng ký!';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO customers (customer_name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $phone, $hash);
            if ($stmt->execute()) {
                $success = 'Đăng ký thành công! Vui lòng đăng nhập.';
                $tab = 'login';
            } else {
                $error = 'Đăng ký thất bại, thử lại!';
            }
        }
        $stmt->close();
    }
    if (empty($success)) $tab = 'register';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập / Đăng ký — VISIO</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #f5f0e8 0%, #e8dcc8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(26,23,20,0.15);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        .logo {
            text-align: center;
            padding: 2rem 2rem 1rem;
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            font-weight: 600;
            color: #1a1714;
            letter-spacing: 0.1em;
        }
        .logo span { color: #c8a96e; }
        .tabs {
            display: flex;
            border-bottom: 1px solid #e8dcc8;
        }
        .tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            color: #7a6050;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            text-decoration: none;
        }
        .tab.active {
            color: #1a1714;
            border-bottom-color: #c8a96e;
        }
        .form-body { padding: 1.8rem 2rem 2rem; }
        .form-group { margin-bottom: 1.1rem; }
        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 500;
            color: #4a3728;
            margin-bottom: 0.4rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #e8dcc8;
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #c8a96e;
        }
        .btn {
            width: 100%;
            padding: 0.85rem;
            background: #1a1714;
            color: #f5f0e8;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            transition: background 0.2s;
            margin-top: 0.5rem;
        }
        .btn:hover { background: #a07840; }
        .error {
            background: #fde8e8;
            color: #c0392b;
            border-radius: 6px;
            padding: 0.7rem 1rem;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border-radius: 6px;
            padding: 0.7rem 1rem;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        .back {
            display: block;
            text-align: center;
            margin-top: 1.2rem;
            font-size: 0.85rem;
            color: #c8a96e;
            text-decoration: none;
        }
        .back:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">VISIO<span>.</span></div>

    <div class="tabs">
        <a href="?tab=login" class="tab <?php echo $tab == 'login' ? 'active' : ''; ?>">Đăng nhập</a>
        <a href="?tab=register" class="tab <?php echo $tab == 'register' ? 'active' : ''; ?>">Đăng ký</a>
    </div>

    <div class="form-body">
        <?php if ($error): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($tab == 'login'): ?>
        <!-- FORM ĐĂNG NHẬP -->
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="example@email.com" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" placeholder="Nhập mật khẩu" required>
            </div>
            <button type="submit" class="btn">Đăng nhập</button>
        </form>

        <?php else: ?>
        <!-- FORM ĐĂNG KÝ -->
        <form method="POST">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <label>Họ và tên</label>
                <input type="text" name="name" placeholder="Nguyễn Văn A" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="example@email.com" required>
            </div>
            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="text" name="phone" placeholder="0901234567">
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" placeholder="Ít nhất 6 ký tự" required>
            </div>
            <div class="form-group">
                <label>Xác nhận mật khẩu</label>
                <input type="password" name="confirm" placeholder="Nhập lại mật khẩu" required>
            </div>
            <button type="submit" class="btn">Đăng ký</button>
        </form>
        <?php endif; ?>

        <a href="index.php" class="back">← Quay lại trang chủ</a>
    </div>
</div>
</body>
</html>
