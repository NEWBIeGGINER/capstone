<?php
require_once '../config.php';
require_once '../components/connect.php';
require_once '../components/auth.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

// --- Ensure user is logged in
if (empty($user_id) || !$is_logged_in) {
    echo json_encode(["success" => false, "msg" => "User not logged in"]);
    exit;
}

// --- Helper: get effective price
function getEffectivePrice($orig_price, $sale_price, $on_sale) {
    $orig = (float)$orig_price;
    $sale = is_null($sale_price) ? 0.0 : (float)$sale_price;
    return (!empty($on_sale) && $sale > 0 && $sale < $orig) ? $sale : $orig;
}

// --- Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $fullname        = trim($_POST['fullname'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $address         = trim($_POST['address'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $payment_method  = trim($_POST['payment_method'] ?? '');
    $delivery_method = trim($_POST['delivery_method'] ?? '');
    $checkout_type   = $_POST['checkout_type'] ?? 'multi';

    if ($fullname === '' || $email === '' || $address === '' || $phone === '' || $payment_method === '' || $delivery_method === '') {
        echo json_encode(["success" => false, "msg" => "Please fill all required fields."]);
        exit;
    }

    $cart_items = [];
    $total = 0.0;

    // --- Determine shipping fee
    if (strtolower($delivery_method) === 'pickup') {
        $shipping_fee = 0.00;
        $shipping_note = "Free shipping (Pickup)";
    } else {
        $shipping_fee = 35.00; // default fee
        $shipping_note = null;

        // --- Fetch latest ACTIVE promotion
        $stmtPromo = $conn->prepare("
            SELECT delivery_fee, promo_note
            FROM promotion
            WHERE status = 'Active' AND CURDATE() BETWEEN start_date AND end_date
            ORDER BY updated_at DESC
            LIMIT 1
        ");
        $stmtPromo->execute();
        $promo = $stmtPromo->fetch(PDO::FETCH_ASSOC);

        if ($promo && !empty($promo['delivery_fee'])) {
            $shipping_fee = (float)$promo['delivery_fee'];
            $shipping_note = $promo['promo_note'] ?? null;
        }
    }

    try {
        // --- Single product checkout
        if ($checkout_type === 'single' && !empty($_POST['product_id']) && !empty($_POST['quantity'])) {
            $product_id = (int)$_POST['product_id'];
            $quantity = max(1, (int)$_POST['quantity']);

            $stmt = $conn->prepare("SELECT id, name, price AS orig_price, sale_price, on_sale, stock, image FROM product WHERE id = ? LIMIT 1");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new Exception("Product not found");
            }

            if ((int)$product['stock'] < $quantity) {
                throw new Exception("Insufficient stock for '{$product['name']}'");
            }

            $effective = getEffectivePrice($product['orig_price'], $product['sale_price'], $product['on_sale']);
            $cart_items[] = [
                "product_id" => $product['id'],
                "name"       => $product['name'],
                "price"      => $effective,
                "orig_price" => (float)$product['orig_price'],
                "quantity"   => $quantity,
                "image"      => $product['image'] ?? ''
            ];
            $total += $effective * $quantity;

        } else {
            // --- Multi-product checkout from cart
            $stmt = $conn->prepare("
                SELECT c.product_id, c.quantity, p.name, p.price AS orig_price, p.sale_price, p.on_sale, p.stock, p.image
                FROM cart c
                JOIN product p ON c.product_id = p.id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$user_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                throw new Exception("Your cart is empty");
            }

            foreach ($rows as $r) {
                $qty = max(1, (int)$r['quantity']);
                if ((int)$r['stock'] < $qty) {
                    throw new Exception("Insufficient stock for '{$r['name']}'");
                }
                $eff = getEffectivePrice($r['orig_price'], $r['sale_price'], $r['on_sale']);
                $cart_items[] = [
                    "product_id" => $r['product_id'],
                    "name"       => $r['name'],
                    "price"      => $eff,
                    "orig_price" => (float)$r['orig_price'],
                    "quantity"   => $qty,
                    "image"      => $r['image'] ?? ''
                ];
                $total += $eff * $qty;
            }
        }

        $total_with_shipping = $total + $shipping_fee;

        // --- Begin transaction
        $conn->beginTransaction();

        // --- Insert order
        $stmtOrder = $conn->prepare("
            INSERT INTO orders (user_id, fullname, checkout_email, address, phone, payment_method, total, shipping_fee, delivery_method, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
        ");
        $stmtOrder->execute([$user_id, $fullname, $email, $address, $phone, $payment_method, $total_with_shipping, $shipping_fee, $delivery_method]);
        $order_id = $conn->lastInsertId();

        // --- Insert order items and update stock
        $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmtUpdateStock = $conn->prepare("UPDATE product SET stock = stock - ? WHERE id = ?");

        foreach ($cart_items as $item) {
            $stmtItem->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            $stmtUpdateStock->execute([$item['quantity'], $item['product_id']]);
        }

        // --- Clear cart if multi
        if ($checkout_type === 'multi') {
            $stmtClearCart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmtClearCart->execute([$user_id]);
        }

        $conn->commit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        echo json_encode(["success" => false, "msg" => $e->getMessage()]);
        exit;
    }

    // --- Build email HTML
    function buildEmailItemsHtml($items) {
        $html = '';
        foreach ($items as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $img = !empty($item['image']) ? $item['image'] : 'default.png';
            $priceHtml = ($item['price'] < $item['orig_price']) 
                ? "<p>Price: <del>₱".number_format($item['orig_price'],2)."</del> <b style='color:#2c7be5;'>₱".number_format($item['price'],2)."</b></p>" 
                : "<p>Price: ₱".number_format($item['price'],2)."</p>";

            $html .= "
            <div class='product-box'>
                <img src='http://{$_SERVER['HTTP_HOST']}/uploaded_files/{$img}' alt='Product Image' />
                <div class='info'>
                    <p><b>".htmlspecialchars($item['name'])."</b></p>
                    <p>Quantity: {$item['quantity']}</p>
                    {$priceHtml}
                    <p><b>Subtotal:</b> ₱".number_format($subtotal,2)."</p>
                </div>
            </div>";
        }
        return $html;
    }

    $itemsHtml = buildEmailItemsHtml($cart_items);
    $deliveryText = strtolower($delivery_method) === 'pickup' ? 'Pickup' : 'Courier (J&T / LBC)';

    // --- Send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_USERNAME, 'Petcare');
        $mail->addAddress($email, $fullname);

        $mail->isHTML(true);
        $mail->Subject = "Order Confirmation - Petcare";
        $mail->Body = "
        <!DOCTYPE html><html><head><meta charset='utf-8'><style>
        body{font-family:Arial,sans-serif;background:#f4f6f9;margin:0;padding:20px;color:#333}
        .container{max-width:600px;background:#fff;margin:auto;border-radius:10px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.1)}
        .header{background:#2c7be5;color:#fff;text-align:center;padding:20px}
        .section{padding:20px;border-bottom:1px solid #eee}
        .product-box{display:flex;align-items:center;border:1px solid #eee;border-radius:8px;padding:10px;margin-bottom:10px}
        .product-box img{width:80px;height:80px;border-radius:8px;margin-right:15px;object-fit:cover}
        .total{font-size:16px;font-weight:bold;color:#2c7be5}
        </style></head><body>
        <div class='container'>
            <div class='header'><h1>🐾 Order Confirmation</h1><p>Order #{$order_id}</p></div>
            <div class='section'>
                <h2>Customer Information</h2>
                <p><b>Name:</b> ".htmlspecialchars($fullname)."</p>
                <p><b>Email:</b> ".htmlspecialchars($email)."</p>
                <p><b>Phone:</b> ".htmlspecialchars($phone)."</p>
                <p><b>Address:</b> ".htmlspecialchars($address)."</p>
            </div>
            <div class='section'><h2>Order Details</h2>
                {$itemsHtml}
                <p><b>Delivery Method:</b> {$deliveryText}</p>
                ".($deliveryText !== 'Pickup' ? "<p><b>Shipping Fee:</b> ₱".number_format($shipping_fee,2)."</p>" : "")."
                ".($shipping_note ? "<p><i style='color:#555'>Promo Applied: ".htmlspecialchars($shipping_note)."</i></p>" : "")."
                <p class='total'>Total Amount: ₱".number_format($total_with_shipping,2)."</p>
                <p><b>Payment Method:</b> ".htmlspecialchars($payment_method)."</p>
                <p><b>Status:</b> Pending</p>
            </div>
            <div class='section' style='text-align:center;color:#666'>Thank you for ordering with <b>Petcare</b> 🐾</div>
        </div></body></html>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mail error: " . $e->getMessage());
    }

    echo json_encode(["success" => true, "msg" => "Order successful! Check your email for confirmation."]);
    exit;
}
?>
