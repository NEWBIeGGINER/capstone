<?php
require_once 'connect.php';
require_once 'auth.php';

// 🔑 Helper function for effective price
function getEffectivePrice($row) {
    if (!empty($row['on_sale']) && !empty($row['sale_price']) && $row['sale_price'] > 0) {
        return (float)$row['sale_price'];
    }
    return (float)$row['price'];
}

$action = $_POST['action'] ?? '';
$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

switch ($action) {

    case 'get_items':
        $stmt = $conn->prepare("
            SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.sale_price, p.on_sale, p.image
            FROM cart c
            JOIN product p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $items = [];
        $cart_count = 0;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $effective_price = getEffectivePrice($row);
            $items[] = [
                'id'         => $row['id'],
                'product_id' => $row['product_id'],
                'name'       => $row['name'],
                'image'      => $row['image'],
                'quantity'   => $row['quantity'],
                'price'      => $effective_price
            ];
            $cart_count += $row['quantity'];
        }

        echo json_encode([
            'status' => 'success',
            'items' => $items,
            'cart_count' => $cart_count
        ]);
        exit;

    case 'increase':
    case 'decrease':
        handleQuantityChange($conn, $user_id, $product_id, $action);
        break;

    case 'add_to_cart':
        // --- Get available stock from product ---
        $stmt = $conn->prepare("SELECT stock FROM product WHERE id = ?");
        $stmt->execute([$product_id]);
        $available_stock = (int)$stmt->fetchColumn();

        // --- Get current cart quantity ---
        $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $current_qty = (int)$stmt->fetchColumn();

        // --- Check if adding exceeds stock ---
        if ($current_qty + $quantity > $available_stock) {
            echo json_encode([
                'status' => 'error',
                'message' => "Max Stock Reached. Only $available_stock unit(s) available."
            ]);
            exit;
        }

        // --- Add or update cart ---
        if ($current_qty > 0) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $user_id, $product_id]);
        } else {
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity]);
        }

        returnCartUpdate($conn, $user_id, $product_id);
        break;

    case 'clear_cart':
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        echo json_encode(['status' => 'success', 'cart_count' => 0]);
        exit;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        exit;
}

// -------------------- HELPER FUNCTIONS --------------------

function handleQuantityChange($conn, $user_id, $product_id, $action) {
    $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $current_qty = (int)$stmt->fetchColumn();

    if ($action === 'increase') {
        $stmt2 = $conn->prepare("SELECT stock FROM product WHERE id = ?");
        $stmt2->execute([$product_id]);
        $available_stock = (int)$stmt2->fetchColumn();

        if ($current_qty < $available_stock) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Max Stock Reached. Only $available_stock unit(s) available."]);
            exit;
        }
    }

    if ($action === 'decrease') {
        if ($current_qty > 1) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = quantity - 1 WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
        } else {
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
        }
    }

    returnCartUpdate($conn, $user_id, $product_id);
}

function returnCartUpdate($conn, $user_id, $product_id) {
    $stmt = $conn->prepare("
        SELECT c.product_id, c.quantity, p.price, p.sale_price, p.on_sale
        FROM cart c
        JOIN product p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);

    $cart_total = 0;
    $row_sub = 0;
    $qty = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $effective_price = getEffectivePrice($row);
        $subtotal = $effective_price * $row['quantity'];
        $cart_total += $subtotal;

        if ($row['product_id'] == $product_id) {
            $row_sub = $subtotal;
            $qty = $row['quantity'];
        }
    }

    echo json_encode([
        'status' => 'success',
        'new_quantity' => $qty,
        'new_subtotal' => $row_sub,
        'cart_total' => $cart_total
    ]);
    exit;
}
?>
