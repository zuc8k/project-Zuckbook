<?php
session_start();
require_once __DIR__ . "/backend/config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

// Check if user has admin role
$user_id = intval($_SESSION['user_id']);
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !in_array($user['role'], ['cofounder', 'mod', 'sup'])) {
    header("Location: /home.php");
    exit;
}

// Check if PIN is already verified in this session
if (isset($_SESSION['admin_pin_verified']) && $_SESSION['admin_pin_verified'] === true) {
    header("Location: /admin_dashboard.php");
    exit;
}

// Handle PIN submission
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'] ?? '';
    
    if ($pin === '1234') {
        $_SESSION['admin_pin_verified'] = true;
        header("Location: /admin_dashboard.php");
        exit;
    } else {
        $error = "الرقم السري غير صحيح";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الوصول إلى لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            text-align: center;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .lock-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .lock-icon i {
            font-size: 48px;
            color: white;
        }

        h1 {
            font-size: 28px;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 40px;
        }

        .pin-form {
            margin-bottom: 20px;
        }

        .pin-input-container {
            position: relative;
            margin-bottom: 25px;
        }

        .pin-input {
            width: 100%;
            padding: 18px 20px;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            outline: none;
            transition: all 0.3s ease;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }

        .pin-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .pin-input::placeholder {
            letter-spacing: normal;
            font-family: 'Cairo', sans-serif;
            font-size: 16px;
            font-weight: 400;
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            margin-top: 20px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #667eea;
        }

        .security-note {
            background: #f3f4f6;
            padding: 16px;
            border-radius: 12px;
            margin-top: 30px;
            font-size: 13px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .security-note i {
            color: #667eea;
            font-size: 18px;
        }

        /* Number pad for mobile */
        .number-pad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 20px;
        }

        .num-btn {
            padding: 20px;
            background: #f3f4f6;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .num-btn:hover {
            background: #e5e7eb;
            transform: scale(1.05);
        }

        .num-btn:active {
            transform: scale(0.95);
        }

        .num-btn.clear {
            background: #fee2e2;
            color: #dc2626;
        }

        .num-btn.clear:hover {
            background: #fecaca;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 40px 30px;
            }

            h1 {
                font-size: 24px;
            }

            .lock-icon {
                width: 80px;
                height: 80px;
            }

            .lock-icon i {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="lock-icon">
            <i class="fas fa-shield-halved"></i>
        </div>

        <h1>لوحة التحكم</h1>
        <p class="subtitle">أدخل الرقم السري للوصول إلى لوحة التحكم</p>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="pin-form" id="pinForm">
            <div class="pin-input-container">
                <input 
                    type="password" 
                    name="pin" 
                    id="pinInput" 
                    class="pin-input" 
                    placeholder="أدخل الرقم السري"
                    maxlength="4"
                    autocomplete="off"
                    autofocus
                    required
                >
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-unlock"></i> دخول
            </button>
        </form>

        <div class="number-pad">
            <button type="button" class="num-btn" onclick="addDigit('1')">1</button>
            <button type="button" class="num-btn" onclick="addDigit('2')">2</button>
            <button type="button" class="num-btn" onclick="addDigit('3')">3</button>
            <button type="button" class="num-btn" onclick="addDigit('4')">4</button>
            <button type="button" class="num-btn" onclick="addDigit('5')">5</button>
            <button type="button" class="num-btn" onclick="addDigit('6')">6</button>
            <button type="button" class="num-btn" onclick="addDigit('7')">7</button>
            <button type="button" class="num-btn" onclick="addDigit('8')">8</button>
            <button type="button" class="num-btn" onclick="addDigit('9')">9</button>
            <button type="button" class="num-btn clear" onclick="clearPin()">
                <i class="fas fa-backspace"></i>
            </button>
            <button type="button" class="num-btn" onclick="addDigit('0')">0</button>
            <button type="button" class="num-btn" onclick="document.getElementById('pinForm').submit()">
                <i class="fas fa-check"></i>
            </button>
        </div>

        <a href="/home.php" class="back-link">
            <i class="fas fa-arrow-right"></i>
            العودة للصفحة الرئيسية
        </a>

        <div class="security-note">
            <i class="fas fa-info-circle"></i>
            <span>هذه المنطقة محمية. يجب إدخال الرقم السري الصحيح للوصول.</span>
        </div>
    </div>

    <script>
        const pinInput = document.getElementById('pinInput');

        function addDigit(digit) {
            if (pinInput.value.length < 4) {
                pinInput.value += digit;
                
                // Auto submit when 4 digits entered
                if (pinInput.value.length === 4) {
                    setTimeout(() => {
                        document.getElementById('pinForm').submit();
                    }, 300);
                }
            }
        }

        function clearPin() {
            pinInput.value = pinInput.value.slice(0, -1);
        }

        // Allow keyboard input
        pinInput.addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Auto submit when 4 digits entered
            if (this.value.length === 4) {
                setTimeout(() => {
                    document.getElementById('pinForm').submit();
                }, 300);
            }
        });

        // Prevent paste
        pinInput.addEventListener('paste', function(e) {
            e.preventDefault();
        });
    </script>
</body>
</html>
