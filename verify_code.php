<?php
session_start();
include 'components/connect.php';
require_once 'components/connect.php';
require_once 'components/auth.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Redirect if no pending user
if (!isset($_SESSION['pending_user'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['pending_user'];
$error = "";
$success = "";

// Function to send verification email
function sendVerificationEmail($user, $code, $minutes = 2) {
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
        $mail->addAddress($user['email'], $user['name']);
        $mail->isHTML(true);
        $mail->Subject = "Your Petcare Verification Code";
        $mail->Body    = "
            <h2>Hello, {$user['name']}!</h2>
            <p>Your verification code is:</p>
            <h1 style='color:#2c7;'>$code</h1>
            <p>This code will expire in <b>{$minutes} minutes</b>.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Failed to send email: " . $mail->ErrorInfo;
    }
}

// ===================== Handle Verification =====================
if (isset($_POST['verify'])) {
    $code = trim($_POST['code']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND verification_code = ? LIMIT 1");
    $stmt->execute([$user_id, $code]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (!empty($user['code_expires_at']) && strtotime($user['code_expires_at']) < time()) {
            $error = "Verification code expired. Please request a new one.";
        } else {
            $update = $conn->prepare("UPDATE users 
                                      SET is_verified = 1, verification_code = NULL, code_expires_at = NULL 
                                      WHERE id = ?");
            $update->execute([$user_id]);

            $_SESSION['user_id'] = $user_id;
            unset($_SESSION['pending_user']);
            header("Location: index.php");
            exit();
        }
    } else {
        $error = "Invalid verification code!";
    }
}

// ===================== Handle Resend =====================
if (isset($_POST['resend'])) {
    $newCode = rand(100000, 999999);
    $expiresAt = date("Y-m-d H:i:s", strtotime("+2 minutes")); // 2-minute expiry

    $update = $conn->prepare("UPDATE users SET verification_code = ?, code_expires_at = ? WHERE id = ?");
    $update->execute([$newCode, $expiresAt, $user_id]);

    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $result = sendVerificationEmail($user, $newCode, 2);
        if ($result === true) {
            $success = "A new verification code has been sent to your email.";
        } else {
            $error = $result;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Code</title>
    <link rel="stylesheet" href="assets/css/signup.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .otp-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .otp-input {
            width: 50px;
            height: 50px;
            font-size: 24px;
            text-align: center;
            border: 2px solid #ccc;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.2s;
        }
        .otp-input:focus {
            border-color: #007bff;
        }
    </style>
</head>
<body>
 <?php include 'components/user_header.php'; ?>

<div class="signup-container">
    <div class="signup-box">
        <h3>Enter Verification Code</h3>

        <?php if (!empty($error)) echo "<p style='color:red'>$error</p>"; ?>
        <?php if (!empty($success)) echo "<p style='color:green'>$success</p>"; ?>

        <p id="timer" style="font-weight:bold;">Code expires in: 02:00</p>

        <form method="post" id="verifyForm">
            <div class="otp-container">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" maxlength="1" class="otp-input" pattern="[0-9]*" inputmode="numeric">
                <?php endfor; ?>
            </div>
            <input type="hidden" name="code" id="otp-code">
            <button type="submit" name="verify" class="btn" id="verifyBtn">Verify</button>
        </form>

        <form method="post" style="margin-top:10px;">
            <button type="submit" name="resend" class="btn" id="resendBtn">Resend Code</button>
        </form>
    </div>
</div>

<script>
const inputs = document.querySelectorAll('.otp-input');
const hiddenInput = document.getElementById('otp-code');
const form = document.getElementById('verifyForm');
const timerEl = document.getElementById('timer');
const verifyBtn = document.getElementById('verifyBtn');

let countdown = 120; // 2 minutes in seconds

// Update hidden input from visible inputs
function updateHiddenInput() {
    hiddenInput.value = Array.from(inputs).map(i => i.value).join('');
}

// Focus behavior for OTP inputs
inputs.forEach((input, index) => {
    input.addEventListener('input', e => {
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
        if (input.value && index < inputs.length - 1) inputs[index + 1].focus();
        updateHiddenInput();
    });
    input.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !input.value && index > 0) inputs[index - 1].focus();
    });
});

// Update hidden input on submit
form.addEventListener('submit', () => updateHiddenInput());

// Countdown timer
function startTimer() {
    const interval = setInterval(() => {
        const minutes = String(Math.floor(countdown / 60)).padStart(2, '0');
        const seconds = String(countdown % 60).padStart(2, '0');
        timerEl.textContent = `Code expires in: ${minutes}:${seconds}`;

        if (countdown <= 0) {
            clearInterval(interval);
            verifyBtn.disabled = true;
            timerEl.textContent = "Code expired. Please resend.";
        }
        countdown--;
    }, 1000);
}

// Start timer on page load
startTimer();
</script>

</body>
</html>
