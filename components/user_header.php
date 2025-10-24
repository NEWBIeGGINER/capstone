<?php
require_once 'components/connect.php';
require_once 'components/auth.php';

// ✅ Default state = logged out
$is_logged_in = false;

// ✅ Verify kung talagang existing pa yung user
if (!empty($user_id)) {
    $stmt = $conn->prepare("SELECT id, email, name FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $is_logged_in = true;
    } else {
        // ❌ Invalid cookie/session, linisin
        $user_id = '';
        $is_logged_in = false;
        session_unset();
        setcookie('user_id', '', time() - 3600, '/');
    }
}

// ✅ Cart count + total (with discount support)
$cart_count = 0;
$cart_total = 0;

if ($is_logged_in) {
    $stmt = $conn->prepare("
        SELECT c.quantity, p.price, p.sale_price, p.on_sale
        FROM cart c
        JOIN product p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $effective_price = ($row['on_sale'] && !empty($row['sale_price']) && $row['sale_price'] > 0) 
            ? $row['sale_price'] 
            : $row['price'];

        $cart_count += $row['quantity'];
        $cart_total += $effective_price * $row['quantity'];
    }
}
?>



<style>
    
.header-cart {
    position: relative;
    display: inline-block;
    font-size: 22px;
    cursor: pointer;
    margin-right: 15px;
}

#checkoutModal {
  display: none; /* default hidden */
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
}

#cart-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: red;
    color: white;
    width: 18px;
    height: 18px;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-weight: bold;
}


.search-icon {
    display: inline-block;
    font-size: 20px;
    margin-right: 15px;
    cursor: pointer;
}

.search-icon a {
    color: inherit;
    transition: color 0.3s ease;
}

.search-icon a:hover {
    color: var(--carrot-color); /* highlight on hover */
}

.header-cart,
.search-icon {
    margin-right: 0; /* reset */
}

.cart-dropdown {
    position: relative;
    display: inline-block;
    z-index: 9999;
}

/* dropdown aligned to right edge of parent */
.cart-dropdown .dropdown-content {
    display: none;
    flex-direction: column;
    position: absolute;   /* relative to .cart-dropdown */
    top: 100%;            /* just below the header/cart button */
    right: 0;             /* flush with right edge of parent */
    width: 400px;
    max-width: 90vw;
    background: #fff;
    box-shadow: -5px 5px 20px rgba(0,0,0,0.25);
    overflow-y: auto;
}

/* Show when cart is active */
.cart-dropdown.show .dropdown-content {
    display: flex;
}


/* Top nav inside panel for switching Cart / Favorites */
.panel-nav {
    display: flex;
    border-bottom: 1px solid #eee;
    position: sticky; /* fixed top sa panel */
    top: 0;
    background: #f8f9fa;
    z-index: 10;
}

.nav-btn {
    flex: 1;
    padding: 12px;
    cursor: pointer;
    background: #f8f9fa;
    border: none;
    font-weight: bold;
    text-align: center;
    transition: background 0.2s;
}

.nav-btn.active {
    background: #fff;
    border-bottom: 3px solid #e67e22;
}

/* Side Panels */
.side-panel {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
}

.panel {
    display: none;
    flex-direction: column;
}

.panel.show {
    display: flex;
}

/* Panel header */
.panel-header {
    flex-shrink: 0;
    padding: 12px 16px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: bold;
    font-size: 16px;
}

.close-btn {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #666;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.close-btn:hover {
    background: #e9ecef;
}

/* Panel body scrollable */
.panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 10px 0;
}

/* Panel footer */
.panel-footer {
    flex-shrink: 0;
    padding: 10px 16px;
    border-top: 1px solid #eee;
    background: #f8f9fa;
}

.panel-footer button {
    width: 100%;
    background: #e67e22;
    color: #fff;
    padding: 10px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.panel-footer button:hover:not(:disabled) {
    background: #d35400;
}

.panel-footer button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

/* Cart items */
.cart-item {
    display: flex;
    align-items: flex-start;
    padding: 10px 16px;
    border-bottom: 1px solid #eee;
    gap: 10px;
    transition: background 0.2s;
}

.cart-item:hover {
    background: #f8f9fa;
}

.cart-item img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
    flex-shrink: 0;
}

.cart-item .info {
    display: flex;
    flex-direction: column;
    font-size: 13px;
    flex: 1;
}

.cart-item .info .name {
    font-weight: 500;
    color: #333;
}

.cart-item .info .price {
    font-weight: bold;
    color: #27ae60;
    margin-top: 4px;
}

/* Quantity buttons */
.cart-item .quantity {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: auto;
}

.cart-item .quantity button {
    width: 28px;
    height: 28px;
    font-size: 14px;
    border: 1px solid #ddd;
    background: #f8f9fa;
    border-radius: 6px;
    cursor: pointer;
}

.cart-item .quantity button:hover {
    background: #e9ecef;
}

.cart-item .quantity button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Empty states */
.cart-empty,
.favorites-empty {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-size: 14px;
    font-style: italic;
}

/* Prevent body scroll if needed */
body.no-scroll,
body.no-scroll-fav {
    overflow: hidden;
}


@keyframes bounce {
  0%, 100% { transform: scale(1); }
  30% { transform: scale(1.3); }
  50% { transform: scale(0.9); }
  70% { transform: scale(1.1); }
}

.header-cart i.cart-bounce {
  animation: bounce 0.5s ease;
}



</style>

<header class="header">
    <a href="index.php" class="logo"> <i class="fas fa-paw"></i> Petcare</a>

    <nav class="navbar">
        <a href="index.php">home</a>
        <a href="products.php">products</a>
        <a href="services.php">services</a>
        <a href="appointment.php">appointments</a>
        <a href="community.php">community</a>
    </nav>

    <div class="icons">
        <div class="fas fa-bars" id="menu-btn"></div>

        <!-- 🔍 Search Icon -->
        <div class="search-icon">
            <a href="appointment.php"><i class="fas fa-calendar-alt"></i></a>
        </div>

        <!-- Cart dropdown -->
        <div class="dropdown cart-dropdown">
            <a href="javascript:void(0);" class="header-cart">
                <i class="fas fa-shopping-cart"></i>
                <span id="cart-count"><?= $cart_count ?></span>
            </a>

            <!-- Cart Panel -->
            <div class="dropdown-content" id="cart-content">
                <div class="side-panel show"> <!-- full-height panel -->

                    <!-- Cart Panel -->
                    <div class="panel cart-panel show">
                        <div class="panel-header cart-header">
                            Cart
                            <button class="close-btn close-cart">&times;</button>
                        </div>
                        <div class="panel-body cart-body" id="cartBody">
                            <p class="cart-empty">Your cart is empty</p>
                        </div>
                        <div class="panel-footer cart-footer">
                            <div id="cart-total">₱0.00</div>
                            <button id="checkoutBtnHeader">Checkout Cart</button>
                        </div>
                    </div>

                    <!-- Favorites Panel -->
                    <div class="panel favorites-panel" id="favoritesDropdown">
                        <div class="panel-header favorites-header">
                            
                            <button class="close-btn close-favorites">&times;</button>
                        </div>
                        <div class="panel-body favorites-body" id="favoritesContent">
                            <p class="favorites-empty"></p>
                        </div>
                        <div class="panel-footer favorites-footer">
                            <button id="viewAllFavorites">View All Favorites</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>



        <!-- User dropdown -->
        <div class="dropdown user-dropdown">
            <a class="user-btn"><i class="fas fa-user"></i></a>
            <div class="dropdown-content">
                <?php if ($is_logged_in): ?>
                    <a href="profile.php"><i class="fas fa-id-card"></i> Profile</a>
                    <a href="components/user_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="signin.php"><i class="fas fa-sign-in-alt"></i> Sign In</a>
                    <a href="signup.php"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const cartBtn = document.querySelector(".header-cart");
    if (!cartBtn) return;

    cartBtn.addEventListener("click", (e) => {
        e.preventDefault();

        const currentPage = window.location.pathname.split("/").pop();
        const cartDropdown = document.querySelector(".cart-dropdown .dropdown-content");

        // 🔒 Hide dropdown immediately (to prevent flash)
        if (cartDropdown) {
            cartDropdown.classList.remove("show-dropdown");
        }

        if (currentPage !== "products.php") {
            // 🚀 Redirect directly to products page, no dropdown flash
            window.location.href = "products.php?openCart=1";
            return;
        }

        // ✅ If already on products page, toggle dropdown normally
        if (cartDropdown) {
            const isVisible = cartDropdown.classList.toggle("show-dropdown");

            // Optional: close user dropdown if open
            const userDropdown = document.querySelector(".user-dropdown .dropdown-content");
            if (userDropdown && isVisible) {
                userDropdown.classList.remove("show-dropdown");
            }
        }
    });
});
</script>




<?php include 'components/checkout_modal.php'; ?>

<script src="assets/js/headerCart.js"></script>
