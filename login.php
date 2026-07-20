<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role, guru_id, siswa_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['guru_id'] = $user['guru_id'];
                $_SESSION['siswa_id'] = $user['siswa_id'];
                
                // Redirect berdasarkan role
                switch ($user['role']) {
                    case 'admin':
                        header('Location: aplikasi/admin/index.php');
                        break;
                    case 'guru':
                        header('Location: aplikasi/guru/index.php');
                        break;
                    case 'siswa':
                        header('Location: aplikasi/siswa/index.php');
                        break;
                    case 'orang_tua':
                        header('Location: aplikasi/ortu/index.php');
                        break;
                    default:
                        header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Username atau password salah!';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    } else {
        $error = 'Harap isi username dan password!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem KAIH SMP Negeri 28 Balikpapan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 50%, #7dd3fc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }

        .login-container {
            background: white;
            border-radius: 24px;
            padding: 25px 20px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(2,132,199,0.2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .login-header .logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #0284c7;
            padding: 5px;
        }

        .login-header h1 {
            font-size: 22px;
            color: #1e293b;
            font-weight: 700;
        }

        .login-header p {
            font-size: 14px;
            color: #64748b;
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: #f8fafc;
        }

        .form-group input:focus {
            outline: none;
            border-color: #0284c7;
            background: white;
            box-shadow: 0 0 0 4px rgba(2,132,199,0.1);
        }

        .form-group input::placeholder {
            color: #94a3b8;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 5px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2,132,199,0.3);
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
        }

        .login-footer a {
            color: #0284c7;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
        }

        .copyright {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #94a3b8;
        }

        .copyright span {
            color: #0284c7;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="assets/img/logo-sekolah.png" alt="Logo Sekolah" class="logo">
            <h1>Sistem KAIH</h1>
            <p>SMP Negeri 28 Balikpapan</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Masukkan username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>

            <button type="submit" class="btn-login">Masuk</button>
        </form>

        <div class="login-footer">
            <a href="lupa-password.php">Lupa password?</a>
        </div>

        <div class="copyright">
            Copyright &copy; <?php echo date('Y'); ?> <span>SMP Negeri 28 Balikpapan</span>
        </div>
    </div>
</body>
</html>