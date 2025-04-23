<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "rajkumar@123@";
$dbname = "hackathon";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Login handler
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
        $email = $_POST["email"];
        $password_input = $_POST["password"];

        $stmt = $conn->prepare("SELECT * FROM admin WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && $admin["password"] === $password_input) {
            $_SESSION["admin_logged_in"] = true;
            $_SESSION["admin_email"] = $email;
            echo "<script>window.location.href = 'admin_panel.php';</script>";
            exit();
        } else {
            echo "<script>alert('Invalid Email or Password');</script>";
        }
    }

    if (isset($_GET["logout"])) {
        session_destroy();
        header("Location: admin_login.php");
        exit();
    }

} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
}

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login | Smart QueryBot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --error-color: #ff3333;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.8s ease-in-out;
        }
        
        .login-illustration {
            flex: 1;
            background: linear-gradient(to bottom right, #4361ee, #3a0ca3);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: white;
            text-align: center;
        }
        
        .login-illustration img {
            width: 80%;
            max-width: 300px;
            margin-bottom: 30px;
            animation: float 6s ease-in-out infinite;
        }
        
        .login-illustration h2 {
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .login-illustration p {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .login-form {
            flex: 1;
            background-color: white;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .logo {
            width: 185px;
            margin-bottom: 20px;
            align-self: center;
        }
        
        .login-form h1 {
            font-size: 28px;
            color: var(--dark-color);
            margin-bottom: 10px;
            text-align: center;
            font-weight: 600;
        }
        
        .login-form p {
            color: #666;
            margin-bottom: 30px;
            text-align: center;
            font-size: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background-color: #f9f9f9;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }
        
        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
        }
        
        .toggle-password:hover {
            color: var(--primary-color);
        }
        
        .btn {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #888;
            font-size: 13px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-illustration {
                padding: 30px 20px;
            }
            
            .login-form {
                padding: 40px 30px;
            }
            
            .login-illustration img {
                width: 150px;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-illustration">
            <img src="admin.png" alt="Admin Illustration">
            <h2>Welcome Back, Admin!</h2>
            <p>Access your dashboard to manage the Smart QueryBot system and user queries.</p>
        </div>
        
        <div class="login-form">
            <img src="logo.png" alt="Logo" class="logo">
            <h1>Admin Login</h1>
            <p>Please enter your credentials to continue</p>
            
            <form action="admin_login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="admin@example.com" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                
                <button type="submit" name="login" class="btn">Sign In</button>
                
                <p class="footer-text">© 2025 Smart QueryBot. All rights reserved.</p>
            </form>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // Add input focus effects
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentNode.querySelector('label').style.color = '#4361ee';
            });
            
            input.addEventListener('blur', function() {
                this.parentNode.querySelector('label').style.color = '#555';
            });
        });
    </script>
</body>
</html>