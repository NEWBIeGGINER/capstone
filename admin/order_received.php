<?php
require_once '../config.php';      // ✅ loads ORDER_SECRET_KEY and DB
require_once '../components/connect.php';

// --- Validate GET parameters ---
$order_id = $_GET['order_id'] ?? null;
$token = $_GET['token'] ?? null;

if (!$order_id || !$token) {
    die('Invalid link.');
}

// --- Fetch order ---
$stmt = $conn->prepare("SELECT * FROM orders WHERE id=? LIMIT 1");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('Order not found.');
}

// --- Validate token using ORDER_SECRET_KEY from .env ---
$expected_token = md5($order['id'] . ($order['checkout_email'] ?? $order['email']) . ORDER_SECRET_KEY);
if ($token !== $expected_token) {
    die('Invalid token.');
}

// --- Handle form submission ---
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $feedback = trim($_POST['feedback'] ?? '');

    if ($rating >= 1 && $rating <= 5) {
        $stmt = $conn->prepare("INSERT INTO order_reviews (order_id, rating, feedback, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$order_id, $rating, $feedback]);
        $success = "Thank you for your feedback! 🐾";
    } else {
        $error = "Please select a valid rating.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Review</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
.container { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; }
.star-rating { display: flex; gap: 5px; font-size: 24px; cursor: pointer; }
.star { color: #ccc; }
.star.selected { color: #ffca28; }
textarea { width: 100%; padding: 10px; margin-top: 10px; border-radius: 5px; border: 1px solid #ccc; }
button { padding: 10px 20px; background: #2c7be5; color: #fff; border: none; border-radius: 6px; margin-top: 10px; cursor: pointer; }
.message { margin-top: 10px; color: green; }
.error { margin-top: 10px; color: red; }
</style>
</head>
<body>
<div class="container">
    <h2>Order #<?= htmlspecialchars($order['id']) ?> Review</h2>

    <?php if($success): ?>
        <p class="message"><?= $success ?></p>
    <?php elseif($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <form method="post">
        <label>Rating:</label>
        <div class="star-rating">
            <?php for($i=1;$i<=5;$i++): ?>
                <span class="star" data-value="<?= $i ?>">&#9733;</span>
            <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" id="rating" value="0">
        <label>Feedback:</label>
        <textarea name="feedback" rows="4" placeholder="Write your feedback..."></textarea>
        <button type="submit">Submit Review</button>
    </form>
</div>

<script>
// Star rating JS
const stars = document.querySelectorAll('.star-rating .star');
const ratingInput = document.getElementById('rating');

stars.forEach(star => {
    star.addEventListener('click', () => {
        const value = star.dataset.value;
        ratingInput.value = value;
        stars.forEach(s => s.classList.remove('selected'));
        for(let i=0; i<value; i++) stars[i].classList.add('selected');
    });
});
</script>
</body>
</html>
