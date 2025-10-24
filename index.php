<?php
require_once 'components/connect.php';
require_once 'components/auth.php';

// --- Real-time AJAX handler ---
if (isset($_GET['fetch_products_real'])) {
    $select_products = $conn->prepare("SELECT * FROM product WHERE status = 'active' AND is_best_product = 1");
    $select_products->execute();
    $products = $select_products->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($products);
    exit;
}

// Normal product fetch for page load (best products only)
$select_products = $conn->prepare("SELECT * FROM product WHERE status='active' AND is_best_product = 1 ORDER BY id ASC LIMIT 9");
$select_products->execute();
$products = $select_products->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petcare | Home</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <link rel="stylesheet" href="assets/css/header.css">


    <!--Font awesome-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


</head>

<style>
<style>
/* Best Product Badge */
.badge-best-top {
    position: absolute;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #ff7f50, #ff6347);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.5px;
    padding: 5px 12px;
    border-radius: 14px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.25);
    text-transform: uppercase;
    z-index: 10;
}

/* Optional hover effect */
.badge-best-top:hover {
    transform: translateX(-50%) translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.3);
}

/* Buttons for JS rendered products */
.order-now-btn {
    background: #e67e22; /* carrot color */
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}
.order-now-btn:hover {
    background: #cf6a1a;
}
.order-now-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

/* Make box position relative so badges appear correctly */
.box {
    position: relative;
    overflow: hidden;
}
</style>
</style>

<body>

    <?php include 'components/user_header.php'?>

    <!-- home section start -->
    <section class="home" id="home">
        <div class="content">
            <h3> Welcome to <br> <span>Petcare</span></h3>
            <a href="products.php" class="btn">Shop now</a>
            <a href="appointment.php" class="btn">Book an appointment</a>
        </div>

        <!-- Bottom Wave -->
        <div class="wave">
            <svg viewBox="0 0 1440 320" xmlns="http://www.w3.org/2000/svg">
                <path fill="#fff" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,229.3C672,256,768,288,864,282.7C960,277,1056,235,1152,224C1248,213,1344,235,1392,245.3L1440,256V320H0Z"></path>
            </svg>
        </div>
    </section>
    <!-- home section end -->

    <!-- about section start -->
    <section class="about" id="about">
        <div class="image">
            <img src="assets/images/bowl.png" alt="">
        </div>

        <div class="content">
            <h3>premium <span>pet food</span> manufacturer</h3>
            <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.
                Lorem Ipsum has been the industry's.</p>
            <a href="#" class="btn">read more</a>
        </div>
    </section>
    <!-- about section end -->

    <!-- dog & cat food banner section start -->
    <div class="dog-food">
        <div class="image">
            <img src="assets/images/foods.png" alt="">
        </div>

        <div class="content">
            <h3> <span>air dried</span> dog food</h3>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Cum, ipsum blanditiis nihil,
                libero itaque voluptate ratione voluptates dolore reprehenderit asperiores explicabo
                sequi non aliquid doloremque voluptatibus, recusandae sit! Ratione, similique?</p>
            <div class="amount">₱16.00 - ₱30.00</div>
            <a href="products.php"> <img src="assets/images/bone1.png" alt=""> </a>
        </div>
    </div>

    <div class="cat-food">
        <div class="content">
            <h3> <span>air dried</span> cat food</h3>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Cum, ipsum blanditiis nihil,
                libero itaque voluptate ratione voluptates dolore reprehenderit asperiores explicabo
                sequi non aliquid doloremque voluptatibus, recusandae sit! Ratione, similique?</p>
            <div class="amount">₱16.00 - ₱30.00</div>
            <a href="products.php"> <img src="assets/images/fish.png" alt=""> </a>
        </div>

        <div class="image">
            <img src="assets/images/cat.png" alt="">
        </div>
    </div>
    <!-- dog & cat food banner section end -->

    <!-- shop selection starts -->
    <section class="shop" id="shop">
        <h1 class="heading">our <span>best products</span></h1>

        <div class="box-container" id="product-list">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $p): ?>
                <div class="box">
                    
                    <!-- 🔹 BEST PRODUCT BADGE -->
                    <?php if (!empty($p['is_best_product'])): ?>
                        <div class="badge-best-top">BEST PRODUCT</div>
                    <?php endif; ?>

                    <!-- 🔹 SALE RIBBON -->
                    <?php if ($p['on_sale'] && !empty($p['sale_price'])): ?>
                        <?php $discount = round((($p['price'] - $p['sale_price']) / $p['price']) * 100); ?>
                        <div class="sale-ribbon">-<?= $discount; ?>%</div>
                    <?php endif; ?>

                    <!-- 🔹 FAVORITE RIBBON -->
                    <?php if ($p['is_favorite']): ?>
                        <div class="favorite-ribbon">FAVORITE</div>
                    <?php endif; ?>

                    <!-- ICONS -->
                    <div class="icons">
                        <?php if ($p['stock'] > 0): ?>
                            <a href="products.php?product_id=<?= $p['id'] ?>" class="add-to-cart">
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

                    <!-- IMAGE -->
                    <div class="image">
                        <img src="uploaded_files/<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    </div>

                    <!-- CONTENT -->
                    <div class="content">
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
                            <button class="order-btn" onclick="window.location.href='products.php?product_id=<?= $p['id'] ?>'">
                                <i class="fas fa-shopping-cart"></i> Order Now
                            </button>
                            <p class="stock" style="margin-top:5px;">Available: <?= $p['stock'] ?></p>
                        <?php else: ?>
                            <button class="order-btn" disabled style="background:#ccc; cursor:not-allowed;">
                                <i class="fas fa-ban"></i> Unavailable
                            </button>
                            <p class="unavailable" style="color:red; font-weight:bold;">Out of Stock</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products available</p>
            <?php endif; ?>
        </div>

        <!-- See more button -->
        <div style="text-align:center; margin-top:20px;">
            <a href="products.php" class="btn">See More</a>
        </div>
    </section>
    <!-- shop selection end -->






    <!-- service section start -->
    <section class="services" id="services">
        <h1 class="heading"> our <span>services</span></h1>

        <div class="box-container">
            <div class="box">
                <i class="fas fa-dog"></i>
                <h3>dog boarding</h3>
                <a href="#" class="btn">read more</a>
            </div>

            <div class="box">
                <i class="fas fa-cat"></i>
                <h3>cat boarding</h3>
                <a href="#" class="btn">read more</a>
            </div>

            <div class="box">
                <i class="fas fa-bath"></i>
                <h3>spa & grooming</h3>
                <a href="#" class="btn">read more</a>
            </div>

            <div class="box">
                <i class="fas fa-drumstick-bite"></i>
                <h3>healthy meal</h3>
                <a href="#" class="btn">read more</a>
            </div>

            <div class="box">
                <i class="fas fa-baseball-ball"></i>
                <h3>activity exercise</h3>
                <a href="#" class="btn">read more</a>
            </div>

            <div class="box">
                <i class="fas fa-heartbeat"></i>
                <h3>health care</h3>
                <a href="#" class="btn">read more</a>
            </div>
        </div>
    </section>
    <!-- service section end -->


    <!-- contact section start -->
    <section class="contact" id="contact">
        <div class="image">
            <img src="assets/images/b2.png" alt="">
        </div>

        <form action="">
            <h3>contact us</h3>
            <input type="text" placeholder="your name" class="box">
            <input type="email" placeholder="your email" class="box">
            <input type="number" placeholder="your number" class="box">
            <textarea name="" placeholder="your message" id="" cols="30" rows="10"></textarea>
            <a type="submit" value="send message" class="btn">send message</a>
        </form>
    </section>
    <!-- contact section end -->

    <!-- footer section start -->
    <footer class="footer">
        <!-- Logo + About -->
        <div class="footer-top">
            <div class="footer-brand">
                <a href="#" alt="PetCare Logo" class="logo"><i class="fas fa-paw"></i></a>
                <p>🐾 Caring for pets with love, wellness, and trusted services. From grooming to adoption, PetCare is
                    here for your furry friends.</p>
            </div>

            <!-- Quick Links -->
            <div class="footer-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="appointment.php">Appointment</a></li>
                    <li><a href="community">Community</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="footer-contact">
                <h3>Contact</h3>
                <p><i class="fas fa-map-marker-alt"></i> 123 Pet Street, City, PH</p>
                <p><i class="fas fa-phone"></i> +63 912 345 6789</p>
                <p><i class="fas fa-envelope"></i> support@petcare.com</p>
            </div>

            <!-- Social Media -->
            <div class="footer-socials">
                <h3>Follow Us</h3>
                <a href="#" class="btn1"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="btn1"><i class="fab fa-twitter"></i></a>
                <a href="#" class="btn1"><i class="fab fa-instagram"></i></a>
                <a href="#" class="btn1"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>

        <!-- Bottom Strip -->
        <div class="footer-bottom">
            <p>&copy; 2025 <span>PetCare</span>. All Rights Reserved.</p>
        </div>
    </footer>
    <!-- footer section end -->


    <script src="assets/js/script.js"></script>

    <script src="assets/js/home.js"></script>

    <script src="components/alert.php"></script>
    
</body>

</html>