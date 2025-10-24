<?php
require_once 'connect.php';
require_once 'auth.php'; // $user_id is already set from auth.php

header('Content-Type: application/json');

if (!$user_id) {
    echo json_encode(['status' => 'error', 'msg' => 'Not logged in']);
    exit;
}

// ---------- Toggle favorite ----------
if (isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    if (!$product_id) {
        echo json_encode(['status' => 'error', 'msg' => 'Invalid product ID']);
        exit;
    }

    // Check if product is already in favorites
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ? LIMIT 1");
    $stmt->execute([$user_id, $product_id]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $del = $conn->prepare("DELETE FROM favorites WHERE id = ?");
        $del->execute([$exists['id']]);
        echo json_encode(['status' => 'removed']);
    } else {
        $ins = $conn->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
        $ins->execute([$user_id, $product_id]);
        echo json_encode(['status' => 'added']);
    }
    exit;
}

// ---------- Get favorites list ----------
if (isset($_POST['action']) && $_POST['action'] === 'get_favorites') {
    $stmt = $conn->prepare("
        SELECT f.product_id AS id, p.name, p.image
        FROM favorites f
        JOIN product p ON p.id = f.product_id
        WHERE f.user_id = ? AND f.status = 1
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'items' => $items]);
    exit;
}

// Default fallback
echo json_encode(['status' => 'error', 'msg' => 'Invalid request']);
