<?php
include 'components/connect.php';
require_once 'components/connect.php';
require_once 'components/auth.php';



if (isset($_POST['login'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['pass'];

    // ===== Check Admin =====
    $stmtAdmin = $conn->prepare("SELECT * FROM `admin` WHERE email = ? AND role = 'admin' LIMIT 1");
    $stmtAdmin->execute([$email]);
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        // Admin login success
        $_SESSION['admin_id'] = $admin['id'];
        setcookie('admin_id', $admin['id'], time() + 60*60*24*30, '/');
        header("Location: admin/dashboard.php");
        exit;
    }

    // ===== Check User =====
    $stmtUser = $conn->prepare("SELECT * FROM `users` WHERE email = ? AND role = 'user' LIMIT 1");
    $stmtUser->execute([$email]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['is_verified'] == 0) {
            // User not yet verified (OTP)
            $_SESSION['pending_user'] = $user['id'];
            header("Location: verify_code.php");
            exit;
        }

        // User login success
        $_SESSION['user_id'] = $user['id'];
        setcookie('user_id', $user['id'], time() + 60*60*24*30, '/');
        header("Location: index.php");
        exit;
    }

    // If login fails
    $warning_msg[] = 'Incorrect email or password';
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | Petcare</title>
  <!--Font awesome-->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- SweetAlert -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Your CSS -->
  <link rel="stylesheet" href="assets/css/signin.css">

  <link rel="stylesheet" href="assets/css/header.css">
</head>
<body>
  <?php include 'components/user_header.php'; ?>

    <!-- login form -->
    <div class="signin-container">
        <div class="signin-box">
            <!-- X button -->
            <a href="index.php" class="close-btn">&times;</a>

            <form action="" method="post" enctype="multipart/form-data" class="login">
                <h3>Sign In</h3>
                
                <div class="input-field">
                    <input type="email" name="email" class="box" placeholder="Enter your email" maxlength="50" required>
                </div>
                <div class="input-field">
                    <input type="password" name="pass" class="box" placeholder="Enter your password" maxlength="50" required>
                </div>
                <button type="submit" name="login" class="btn">Login</button>
                <p class="link">Don't have an account? <a href="signup.php">Register now</a></p>
            </form>
        </div>
    </div>


  <!-- sweet alert cnd link -->
  <script src="assets/js/sweetalert.min.js"></script>

  <?php include 'components/alert.php'; ?>

  <script src="assets/js/script.js"></script>
</body>
</html>
