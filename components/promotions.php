<?php
require_once 'connect.php'; // adjust path
header('Content-Type: application/json');

$response = [
  "label" => null,
  "value" => null
];

$default_fee = 35;

// --- Fetch latest ACTIVE promotion ---
$stmt = $conn->prepare("
    SELECT delivery_fee 
    FROM promotion 
    WHERE status = 'Active' 
      AND CURDATE() BETWEEN start_date AND end_date
    ORDER BY updated_at DESC
    LIMIT 1
");
$stmt->execute();
$promo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($promo && !empty($promo['delivery_fee'])) {
    $fee = (float)$promo['delivery_fee'];
    $response['label'] = "Courier (J&T/LBC – ₱" . number_format($fee, 2) . ")";
    $response['value'] = $fee;
} else {
    // fallback to default
    $response['label'] = "Courier (J&T/LBC – ₱" . number_format($default_fee, 2) . ")";
    $response['value'] = $default_fee;
}

echo json_encode($response);
exit;
