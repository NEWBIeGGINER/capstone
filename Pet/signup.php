<?php
require_once 'components/connect.php';
require_once 'components/auth.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

include 'components/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $id      = unique_id();
    $name    = filter_var($_POST['name'], FILTER_SANITIZE_SPECIAL_CHARS);
    $email   = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone   = filter_var($_POST['phone'], FILTER_SANITIZE_NUMBER_INT);
    $address = filter_var($_POST['address'], FILTER_SANITIZE_SPECIAL_CHARS);
    $pass    = $_POST['pass'];
    $cpass   = $_POST['cpass'];

    $select_users = $conn->prepare("SELECT * FROM `users` WHERE email = ?");
    $select_users->execute([$email]);

    if ($select_users->rowCount() > 0) {
        $warning_msg[] = 'Email already taken!';
    } elseif ($pass !== $cpass) {
        $warning_msg[] = 'Confirm password not matched';
    } else {
        $hashedPass   = password_hash($pass, PASSWORD_DEFAULT);
        $verification_code = rand(100000, 999999); // 6-digit OTP

        $insert_users = $conn->prepare("INSERT INTO `users`
            (id, name, email, phone, address, password, verification_code, is_verified)
            VALUES(?,?,?,?,?,?,?,0)");
        $insert_users->execute([$id, $name, $email, $phone, $address, $hashedPass, $verification_code]);

        // ✅ Send verification code via email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'petcare608@gmail.com';
            $mail->Password   = 'ghor bkvb qyly riiz';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('petcare608@gmail.com', 'Petcare');
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = "Your Petcare Verification Code";
            $mail->Body    = "
                <h2>Hello, $name!</h2>
                <p>Welcome to <b>Petcare</b>.</p>
                <p>Your verification code is:</p>
                <h1 style='letter-spacing:3px;'>$verification_code</h1>
                <p>Please enter this code on the verification page.</p>
            ";

            $mail->send();

            // ✅ Save pending user session
            $_SESSION['pending_user'] = $id;

            // ✅ Redirect para hindi mag-resend pag refresh
            header("Location: verify_code.php?registered=1");
            exit();
        } catch (Exception $e) {
            $warning_msg[] = "User registered, but email not sent. Error: {$mail->ErrorInfo}";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Petcare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/signup.css">
    <link rel="stylesheet" href="assets/css/header.css">
</head>
<body>
    <?php include 'components/user_header.php'; ?>

    <div class="signup-container">
        <div class="signup-box">
            <a href="index.php" class="close-btn">&times;</a>

            <form action="" method="post" enctype="multipart/form-data" class="register">
                <h3>Create Account</h3>

                <div class="input-field">
                    <input type="text" name="name" placeholder="Enter your name" maxlength="50" required class="box">
                </div>
                <div class="input-field">
                    <input type="email" name="email" placeholder="Enter your email" maxlength="50" required class="box">
                </div>
                <div class="input-field">
                    <input type="text" name="phone" placeholder="Enter your phone number" maxlength="15" required class="box">
                </div>
                <div class="input-field">
                    <input type="text" name="address" placeholder="Enter your address" maxlength="255" required class="box">
                </div>
                <div class="input-field">
                    <input type="password" name="pass" placeholder="Enter your password" maxlength="50" required class="box">
                </div>
                <div class="input-field">
                    <input type="password" name="cpass" placeholder="Confirm your password" maxlength="50" required class="box">
                </div>

                <button type="submit" name="register" class="btn">Register Now</button>
                <p class="link">Already have an account? <a href="signin.php">Login</a></p>
            </form>
        </div>
    </div>

    <script src="assets/js/sweetalert.min.js"></script>
    <?php include 'components/alert.php'; ?>
    <script src="assets/js/script.js"></script>
</body>
</html>
