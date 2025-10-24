<?php
require_once 'components/connect.php';
require_once 'components/auth.php'; // must define $user_id

header('Content-Type: application/json');

// Ensure PDO throws exceptions
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Upload directory
$targetDir = __DIR__ . '/uploads/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Check file
if (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] !== 0) {
    echo json_encode(['status'=>'error','message'=>'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['profile_pic'];
$allowed = ['jpg','jpeg','png','gif'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed)) {
    echo json_encode(['status'=>'error','message'=>'Only JPG, PNG, GIF allowed']);
    exit;
}

$filename = time() . '_' . basename($file['name']);
$targetFile = $targetDir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
    echo json_encode(['status'=>'error','message'=>'Failed to move uploaded file']);
    exit;
}

// Delete old profile picture if exists
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id=?");
$stmt->execute([$user_id]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);
if (!empty($old['profile_pic']) && file_exists($targetDir . $old['profile_pic'])) {
    @unlink($targetDir . $old['profile_pic']);
}

// Update database
try {
    $stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE id=?");
    $stmt->execute([$filename, $user_id]);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error','message'=>'DB error: ' . $e->getMessage()]);
    exit;
}

// Return JSON for JS preview
echo json_encode(['status'=>'success','image'=>'uploads/' . $filename]);
exit;
