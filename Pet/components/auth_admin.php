<?php
require_once 'connect.php';

session_start();

$admin_id = '';
$is_admin_logged_in = false;

// 10/8s
// ✅ Priority: session
if (!empty($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];
}
// ✅ Fallback: cookie → resync sa session
elseif (!empty($_COOKIE['admin_id'])) {
    $admin_id = $_COOKIE['admin_id'];
    $_SESSION['admin_id'] = $admin_id;
}

// ✅ Verify kung talagang existing pa yung admin
if (!empty($admin_id)) {
    $stmt = $conn->prepare("SELECT id, email, name FROM admin WHERE id = ? LIMIT 1");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        $is_admin_logged_in = true;
    } else {
        // ❌ Invalid session/cookie, linisin
        $admin_id = '';
        $is_admin_logged_in = false;
        session_unset();
        setcookie('admin_id', '', time() - 3600, '/');
    }
}

// 10/8e
?>
