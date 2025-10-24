<?php
require_once 'components/connect.php';
require_once 'components/auth.php';

// --- Default courier fee ---
$courier_fee = 50;

// --- Fetch latest active promotion ---
$stmt_promo = $conn->prepare("
    SELECT delivery_fee, promo_note
    FROM promotion
    WHERE CURDATE() BETWEEN start_date AND end_date
      AND status = 'Active'
    ORDER BY updated_at DESC
    LIMIT 1
");
$stmt_promo->execute();
$promo = $stmt_promo->fetch(PDO::FETCH_ASSOC);

// Override courier fee if an active promotion exists
$promo_text = '';
if ($promo) {
    $courier_fee = $promo['delivery_fee'] ?? $courier_fee;
    $promo_text  = $promo['promo_note'] ?? '';
}

// --- Prepare banner note ---
$current_promo_note = null;
if ($promo) {
    $current_promo_note = !empty($promo_text)
        ? "Delivery Promo: Courier delivery now only ₱{$courier_fee} – {$promo_text}"
        : "Delivery Promo: Courier delivery now only ₱{$courier_fee}";
}

// --- Fetch user's favorite products ---
$favorites = [];
if (!empty($user_id)) {
    $stmt_fav = $conn->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
    $stmt_fav->execute([$user_id]);
    $favorites = $stmt_fav->fetchAll(PDO::FETCH_COLUMN); // array of product IDs
}

// --- Helper for SQL IN clause ---
$favorite_ids_quoted = !empty($favorites) 
    ? array_map(fn($id) => "'$id'", $favorites) 
    : ["'0'"];

// --- AJAX handler for real-time product fetch ---
if (isset($_GET['fetch_products_real'])) {
    $category = $_GET['category'] ?? '';

    $query = "
        SELECT p.*, IF(p.id IN (".implode(',', $favorite_ids_quoted)."), 1, 0) AS is_favorite
        FROM product p
        WHERE p.status = 'active'
    ";
    $params = [];

    if (!empty($category)) {
        $query .= " AND category = ?";
        $params[] = $category;
    }

    $query .= " ORDER BY CASE WHEN p.stock > 0 THEN 0 ELSE 1 END, p.id DESC";

    $stmt_products = $conn->prepare($query);
    $stmt_products->execute($params);
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($products);
    exit;
}

// --- Fetch products for initial page load ---
$query = "
    SELECT p.*, IF(p.id IN (".implode(',', $favorite_ids_quoted)."), 1, 0) AS is_favorite
    FROM product p
    WHERE p.status = 'active'
    ORDER BY CASE WHEN p.stock > 0 THEN 0 ELSE 1 END, p.id DESC
";
$stmt_products = $conn->prepare($query);
$stmt_products->execute();
$products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Petcare | Products</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link rel="stylesheet" href="assets/css/header.css">
  <link rel="stylesheet" href="assets/css/products.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>

</style>
</head>
<body>
  <?php include 'components/user_header.php'; ?>



  <!-- PetCare Shop Header -->
  <div class="shop-header">
      <h1><i class="fas fa-paw"></i> Premium PetCare Collection</h1>
      <p>Discover healthy pets, quality products, and essentials delivered safely to your doorstep.<br>
      Cash on Delivery available nationwide!</p>

    <!-- Promo Banner -->
    <?php if (!empty($current_promo_note)): ?>
      <div class="promo-banner">
        <i class="fas fa-bullhorn"></i>
        <?= htmlspecialchars($current_promo_note) ?>
      </div>
    <?php endif; ?>
  </div>
  <section class="shop" id="shop">
    <!--<h1 class="heading"> our <span> products </span></h1>-->
<!-- 🔹 Category Filter + Search Bar -->
<div class="category-filter-container">
  <!-- 🔍 Search + Category Filter -->
  <div class="category-filter-wrapper">
    <div class="filter-bar">
      <!-- Search -->
      <div class="filter-group search-group">
        <input type="text" id="searchInput" placeholder="Search products...">
      </div>

      <!-- Category -->
      <div class="filter-group category-group">
        <div class="custom-select-wrapper">
          <select id="categoryFilter">
            <option value="">All</option>
            <option value="Dog Food">Dog Food</option>
            <option value="Cat Food">Cat Food</option>
            <option value="Accessories">Accessories</option>
            <option value="Toys">Toys</option>
            <option value="Grooming">Grooming</option>
            <option value="Medicine">Medicine</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- 🔹 Spacer (forces dropdown to open downward) -->
  <div class="dropdown-spacer"></div>
</div>



   <div class="box-container" id="product-list">
      <?php 
      $has_unavailable = false;
      foreach ($products as $p): 
      ?>

      <div class="box">
        <!-- 🔹 SALE RIBBON -->
        <?php if ($p['on_sale'] && !empty($p['sale_price'])): ?>
          <?php $discount = round((($p['price'] - $p['sale_price']) / $p['price']) * 100); ?>
          <div class="sale-ribbon">-<?= $discount; ?>%</div>
        <?php endif; ?>

        <!-- 🔹 FAVORITE RIBBON -->
        <?php if ($p['is_favorite']): ?>
          <div class="favorite-ribbon">FAVORITE</div>
        <?php endif; ?>

        <div class="icons">
          <?php if ($p['stock'] > 0): ?>
            <a href="javascript:void(0);" class="add-to-cart" data-id="<?= $p['id']; ?>">
              <i class="fas fa-shopping-cart"></i>
            </a>
          <?php else: ?>
            <a href="javascript:void(0);" style="color:#aaa;" title="Out of Stock">
              <i class="fas fa-ban"></i>
            </a>
          <?php endif; ?>
          <a href="javascript:void(0);" class="product-heart" data-id="<?= $p['id'] ?>">
              <i class="fas fa-heart <?= $p['is_favorite'] ? 'red-heart' : '' ?>"></i>
          </a>
          <a href="#"><i class="fas fa-eye"></i></a>
        </div>

        <!-- IMAGE, TITLE, DESCRIPTION, PRICE, STOCK, BUTTON -->
        <img src="uploaded_files/<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['name']) ?>">
        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <p><?= htmlspecialchars($p['description']) ?></p>

        <div class="amount">
          <?php if ($p['on_sale'] && !empty($p['sale_price'])): ?>
            <span class="old-price">₱<?= number_format($p['price'], 2) ?></span>
            <span class="sale-price">₱<?= number_format($p['sale_price'], 2) ?></span>
          <?php else: ?>
            ₱<?= number_format($p['price'], 2) ?>
          <?php endif; ?>
        </div>

        <?php if ($p['stock'] > 0): ?>
          <button class="order-btn" 
            data-id="<?= $p['id'] ?>" 
            data-name="<?= htmlspecialchars($p['name']) ?>" 
            data-price="<?= ($p['on_sale'] && !empty($p['sale_price'])) ? $p['sale_price'] : $p['price'] ?>" 
            data-img="uploaded_files/<?= $p['image'] ?>"> 
            <i class="fas fa-shopping-cart"></i> Order Now
          </button>
          <p class="stock">Available: <?= $p['stock']; ?></p>
        <?php else: ?>
          <button class="order-btn" disabled style="background:#ccc;cursor:not-allowed;">
            <i class="fas fa-ban"></i> Unavailable
          </button>
          <p class="unavailable" style="color:red;font-weight:bold;">This product is unavailable</p>
        <?php endif; ?>

      </div> <!-- end of .box -->

      <?php endforeach; ?>
    </div>

  </section>




<?php include 'components/checkout_modal.php'?>



<script src="assets/js/script.js"></script>

<script src="assets/js/products.js"></script>
<script src="assets/js/checkout.js"></script>

<script src="components/alert.php"></script>
</body>
</html>
