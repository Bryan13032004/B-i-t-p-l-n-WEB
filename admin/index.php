<?php
session_start();

// Nếu đã đăng nhập rồi thì vào dashboard luôn
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require '../db_connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!';
    } else {
        $stmt = $conn->prepare("SELECT user_id, username, password, full_name, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Mật khẩu không đúng!';
            }
        } else {
            $error = 'Tài khoản không tồn tại!';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin — VISIO</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            background: #fff;
            padding: 2.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 1.8rem;
        }

        .login-logo h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1714;
            letter-spacing: 0.1em;
        }

        .login-logo h1 span { color: #c8a96e; }

        .login-logo p {
            font-size: 0.85rem;
            color: #888;
            margin-top: 0.3rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #444;
            margin-bottom: 0.4rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: opacity 0.2s;
            margin-top: 0.5rem;
        }

        .btn-login:hover { opacity: 0.9; }

        .error-msg {
            background: #fde8e8;
            color: #c0392b;
            border: 1px solid #f5c6c6;
            border-radius: 6px;
            padding: 0.7rem 1rem;
            font-size: 0.88rem;
            margin-bottom: 1.2rem;
        }

        .hint {
            text-align: center;
            margin-top: 1.2rem;
            font-size: 0.8rem;
            color: #aaa;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #667eea;
            text-decoration: none;
        }

        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-logo">
            <h1>VISIO<span>.</span></h1>
            <p>Đăng nhập quản trị viên</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="username" placeholder="Nhập tên đăng nhập"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" placeholder="Nhập mật khẩu" required>
            </div>
            <button type="submit" class="btn-login">Đăng nhập</button>
        </form>

        <p class="hint">Tài khoản mặc định: <strong>admin</strong> / <strong>password</strong></p>
        <a href="../index.html" class="back-link">← Quay lại trang chủ</a>
    </div>
</body>
</html>
