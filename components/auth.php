<?php
session_start();
require_once 'connect.php';

$user_id = '';
$is_logged_in = false;

if (!empty($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} elseif (!empty($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
    $_SESSION['user_id'] = $user_id; // sync
}

if (!empty($user_id)) {
    $stmt = $conn->prepare("SELECT id, email, name, role FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['role'] === 'user') {
        $is_logged_in = true;
    } else {
        // cleanup kung invalid or admin trying to access user pages
        setcookie('user_id', '', time() - 3600, '/');
        $_SESSION = [];
        session_unset();
        session_destroy();
        $is_logged_in = false;
        $user_id = '';
    }
}
?>
