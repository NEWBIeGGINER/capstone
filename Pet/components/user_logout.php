<?php 
require_once 'connect.php';
session_start();

// Destroy session
$_SESSION = [];
session_unset();
session_destroy();

// Clear cookie
setcookie('user_id', '', time() - 3600, '/');

// Redirect
header('Location: ../index.php');
exit();
?>
