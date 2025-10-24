<?php 
require_once '../components/connect.php';
require_once '../components/auth_admin.php';


if (!$is_admin_logged_in) {
    header("Location: login.php");
    exit;
}

// === SHOW SUCCESS/ERROR MSGS AFTER REDIRECT ===
$success_msg = [];
$sale_active_msg = null;

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $success_msg[] = "Product added successfully!";
    } elseif ($_GET['msg'] === 'updated') {
        $success_msg[] = "Product updated successfully!";
    } elseif ($_GET['msg'] === 'deleted') {
        $success_msg[] = "Product deleted successfully!";
    }
}

// Sale info (hindi isasama sa SweetAlert)
if (isset($_GET['sale_active']) && isset($_GET['sale_price']) && isset($_GET['price'])) {
    $sale_price = number_format((float)$_GET['sale_price'], 2);
    $price = number_format((float)$_GET['price'], 2);
    $sale_active_msg = "Sale Active: Now ₱{$sale_price} (was ₱{$price})";
}



// === ADD PRODUCT ===
if (isset($_POST['add_product'])) {
    $status = $_POST['status'];
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $sale_price = !empty($_POST['sale_price']) ? $_POST['sale_price'] : null;
    $description = $_POST['description'];
    $on_sale = isset($_POST['on_sale']) ? 1 : 0;
    $is_best_product = isset($_POST['is_best_product']) ? 1 : 0; // NEW

    $stock = (int)$_POST['add_stock'];

    // Validation
    if (!empty($sale_price) && $sale_price >= $price) {
        $sale_price = null;
    }
    if ($stock < 0) {
        $stock = 0;
    }

    $image = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];

    if (!empty($image)) {
        $image = time().'_'.$image;
        $folder = '../uploaded_files/'.$image;
        move_uploaded_file($image_tmp, $folder);

        $insert = $conn->prepare("INSERT INTO product 
            (name, category, price, sale_price, stock, description, status, image, on_sale, is_best_product) 
            VALUES (?,?,?,?,?,?,?,?,?,?)");
        $insert->execute([$name, $category, $price, $sale_price, $stock, $description, $status, $image, $on_sale, $is_best_product]);

        header("Location: manage_products.php?msg=added"
            . ($on_sale && $sale_price ? "&sale_active=1&sale_price={$sale_price}&price={$price}" : "")
        );
        exit;
    }
}

// === UPDATE PRODUCT ===
if (isset($_POST['update_product'])) {
    $id = $_POST['product_id'];
    $status = $_POST['status'];
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $sale_price = !empty($_POST['sale_price']) ? $_POST['sale_price'] : null;
    $description = $_POST['description'];
    $on_sale = isset($_POST['on_sale']) ? 1 : 0;
    $is_best_product = isset($_POST['is_best_product']) ? 1 : 0; // NEW

    $current_stock = (int)$_POST['current_stock'];
    $add_stock = (int)$_POST['add_stock'];
    $stock = $current_stock + $add_stock;

    if (!empty($sale_price) && $sale_price >= $price) {
        $sale_price = null;
    }
    if ($stock < 0) {
        $stock = 0;
    }

    $old_image = $_POST['old_image'];
    $image = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];

    if (!empty($image)) {
        $image = time().'_'.$image;
        $folder = '../uploaded_files/'.$image;
        move_uploaded_file($image_tmp, $folder);

        if (!empty($old_image) && file_exists('../uploaded_files/'.$old_image)) {
            unlink('../uploaded_files/'.$old_image);
        }

        $update = $conn->prepare("UPDATE product 
            SET name=?, category=?, price=?, sale_price=?, stock=?, description=?, status=?, image=?, on_sale=?, is_best_product=? 
            WHERE id=?");
        $update->execute([$name, $category, $price, $sale_price, $stock, $description, $status, $image, $on_sale, $is_best_product, $id]);
    } else {
        $update = $conn->prepare("UPDATE product 
            SET name=?, category=?, price=?, sale_price=?, stock=?, description=?, status=?, on_sale=?, is_best_product=? 
            WHERE id=?");
        $update->execute([$name, $category, $price, $sale_price, $stock, $description, $status, $on_sale, $is_best_product, $id]);
    }

    header("Location: manage_products.php?msg=updated"
        . ($on_sale && $sale_price ? "&sale_active=1&sale_price={$sale_price}&price={$price}" : "")
    );
    exit;
}


// === DELETE PRODUCT ===
if (isset($_POST['delete_product'])) {
    $id = $_POST['product_id'];

    $select = $conn->prepare("SELECT image FROM product WHERE id=?");
    $select->execute([$id]);
    $row = $select->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['image']) && file_exists('../uploaded_files/'.$row['image'])) {
        unlink('../uploaded_files/'.$row['image']);
    }

    $delete = $conn->prepare("DELETE FROM product WHERE id=?");
    $delete->execute([$id]);

    header("Location: manage_products.php?msg=deleted");
    exit;
}

// === FETCH PRODUCTS ===
$select_products = $conn->prepare("SELECT * FROM product ORDER BY id DESC");
$select_products->execute();
$products = $select_products->fetchAll(PDO::FETCH_ASSOC);

// ✅ Sort products: stock = 0 first, then low stock (<5), then normal, then recent
usort($products, function($a, $b) {
    // Out of stock first
    if ($a['stock'] == 0 && $b['stock'] != 0) return -1;
    if ($b['stock'] == 0 && $a['stock'] != 0) return 1;

    // Low stock (<5) second
    if ($a['stock'] < 5 && $b['stock'] >= 5) return -1;
    if ($b['stock'] < 5 && $a['stock'] >= 5) return 1;

    // Otherwise, most recent products first
    return $b['id'] - $a['id'];
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products</title>
    <link rel="stylesheet" href="./../assets/css/admin/manage_products.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./../assets/css/admin/admin_header.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="sidebar-overlay" id="overlay"></div>
    <div class="admin-container">
        <?php include '../components/admin_header.php' ?>
        <div class="admin-main">
            <div class="header-row">
                <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>
            </div>

            <div id="productForm" class="form-card">
                <h2 id="formTitle">Add Product</h2>
                <form action="" method="post" enctype="multipart/form-data" class="form-grid">
                    <input type="hidden" name="product_id" id="form_id">
                    <input type="hidden" name="old_image" id="form_old_image">

                    <!-- Status -->
                    <div class="input-field">
                        <p>Status</p>
                        <select name="status" id="form_status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <!-- Product Name -->
                    <div class="input-field">
                        <p>Product Name</p>
                        <input type="text" name="name" id="form_name" required>
                    </div>

                    <!-- Category -->
                    <div class="input-field">
                        <p>Category</p>
                        <select name="category" id="form_category" required>
                            <option value="Dog Food">Dog Food</option>
                            <option value="Cat Food">Cat Food</option>
                            <option value="Accessories">Accessories</option>
                            <option value="Toys">Toys</option>
                            <option value="Grooming">Grooming</option>
                            <option value="Medicine">Medicine</option>
                        </select>
                    </div>

                    <!-- Price -->
                    <div class="input-field">
                        <p>Price</p>
                        <input type="number" step="0.01" name="price" id="form_price" required>
                    </div>

                    <!-- Sale Price -->
                    <div class="input-field">
                        <p>Sale Price</p>
                        <input type="number" step="0.01" name="sale_price" id="form_sale_price">
                    </div>

                    <!-- Stock -->
                    <div class="input-field">
                        <p>Stock (Current: <span id="current_stock_display"></span>)</p>
                        <input type="hidden" name="current_stock" id="form_current_stock">
                        <input type="number" name="add_stock" id="form_add_stock" min="0" value="0" placeholder="Add stock">
                    </div>

                    <!-- On Sale -->
                    <div class="input-field">
                        <p>On Sale?</p>
                        <label class="switch">
                            <input type="checkbox" name="on_sale" id="form_on_sale" value="1">
                            <span class="slider round"></span>
                        </label>
                        <span class="switch-label">Enable sale price for this product</span>
                    </div>

                    <!-- Sale Active Message (dynamic via JS) -->
                    <div id="saleActiveMessage" class="sale-active" style="display:none;"></div>

                    <!-- Best Product Checkbox -->
                    <div class="input-field">
                        <p>Best Product?</p>
                        <label class="switch">
                            <input type="checkbox" name="is_best_product" id="form_best_product" value="1">
                            <span class="slider round"></span>
                        </label>
                        <span class="switch-label">Show this product on Home Page as Best Product</span>
                    </div>

                    <!-- Description -->
                    <div class="input-field full-width">
                        <p>Description</p>
                        <textarea name="description" id="form_description" required></textarea>
                    </div>

                    <!-- Image Upload -->
                    <div class="input-field full-width">
                        <p>Image</p>
                        <input type="file" name="image" accept="image/*" id="form_image">
                        <img id="form_preview" src="" width="100" style="margin-top:10px; display:none;">
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions full-width">
                        <button type="button" class="btn btn-danger" onclick="closeForm()">Cancel</button>
                        <button type="submit" id="formSubmitBtn" name="add_product" class="btn btn-primary">Add</button>
                    </div>
                </form>

            </div>


            <!-- 🔹 PRODUCT LIST -->
            <div class="card">
                <div class="card-header">
                    <button onclick="openForm('add')" class="btn btn-primary add-btn">+ Add Product</button>
                </div>

                <div class="products-grid">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <?php 
                                $isOnSale = $product['on_sale'] && !empty($product['sale_price']);
                                $discount = $isOnSale 
                                    ? round((($product['price'] - $product['sale_price']) / $product['price']) * 100)
                                    : 0;
                                $stockColor = $product['stock'] <= 0 
                                    ? 'red' 
                                    : ($product['stock'] < 5 ? 'orange' : '#333');
                            ?>

                            <div class="product-card">
                                <!-- 🔹 IMAGE CONTAINER -->
                                <div class="product-image-wrapper" style="position: relative; text-align: center;">
                                    
                                    <!-- 🔹 BEST PRODUCT BADGE (center top) -->
                                    <?php if ($product['is_best_product']): ?>
                                        <div class="badge badge-best-top">Best Product</div>
                                    <?php endif; ?>

                                    <!-- 🔹 SALE RIBBON -->
                                    <?php if ($isOnSale): ?>
                                        <div class="ribbon">-<?= $discount; ?>%</div>
                                    <?php endif; ?>

                                    <img src="../uploaded_files/<?= htmlspecialchars($product['image']); ?>" 
                                        alt="<?= htmlspecialchars($product['name']); ?>" 
                                        class="product-img">
                                </div>

                                <!-- 🔹 BODY -->
                                <div class="product-body">
                                    <h3 class="product-title"><?= htmlspecialchars($product['name']); ?></h3>
                                    <p class="product-category"><?= htmlspecialchars($product['category']); ?></p>
                                    <p class="product-description">
                                        <?= htmlspecialchars(mb_strimwidth($product['description'], 0, 60, '...')); ?>
                                    </p>

                                    <div class="product-meta">
                                        <?php if ($isOnSale): ?>
                                            <span class="old-price">₱<?= number_format($product['price'], 2); ?></span>
                                            <span class="sale-price">₱<?= number_format($product['sale_price'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="regular-price">₱<?= number_format($product['price'], 2); ?></span>
                                        <?php endif; ?>

                                        <span class="badge <?= $product['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?= ucfirst($product['status']); ?>
                                        </span>
                                    </div>

                                    <!-- 🔹 STOCK -->
                                    <p class="product-stock" style="margin-top:5px; font-weight:600; color:<?= $stockColor; ?>;">
                                        Stock: 
                                        <?= $product['stock'] <= 0 
                                            ? 'Out of stock ❌' 
                                            : htmlspecialchars($product['stock']); ?>
                                    </p>
                                </div>

                                <!-- 🔹 FOOTER (ACTIONS) -->
                                <div class="product-footer">
                                    <button class="btn btn-primary" 
                                            onclick='openForm("edit", <?= json_encode($product) ?>)'>✏️ Edit</button>

                                    <button type="button" 
                                            class="btn btn-danger" 
                                            onclick="confirmDelete(<?= (int)$product['id']; ?>)">
                                        🗑️ Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-products">No products found</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // ✅ SweetAlert Toasts
    <?php if(!empty($success_msg)): ?>
        <?php foreach($success_msg as $msg): ?>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: "<?= htmlspecialchars($msg) ?>",
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                customClass: { popup: 'swal2-mini' }
            });
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if(!empty($warning_msg)): ?>
        <?php foreach($warning_msg as $msg): ?>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: "<?= htmlspecialchars($msg) ?>",
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                customClass: { popup: 'swal2-mini' }
            });
        <?php endforeach; ?>
    <?php endif; ?>

    // ✅ Remove query params to prevent alert repetition on refresh
    if (window.history.replaceState) {
        const url = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({ path: url }, '', url);
    }

    // ✅ Start real-time product fetching
    fetchProducts();
    setInterval(fetchProducts, 5000);
});

/* ===========================================================
   🧾 FORM CONTROL
   =========================================================== */
function openForm(mode, data = null) {
    const form = document.getElementById("productForm");
    const title = document.getElementById("formTitle");
    const submitBtn = document.getElementById("formSubmitBtn");
    const productList = document.querySelector(".card");

    form.classList.add("show");
    productList.style.display = "none";

    if (mode === "add") {
        title.textContent = "Add Product";
        submitBtn.name = "add_product";
        submitBtn.textContent = "Add";

        document.getElementById("form_id").value = "";
        document.getElementById("form_old_image").value = "";
        document.getElementById("form_status").value = "active";
        document.getElementById("form_name").value = "";
        document.getElementById("form_category").value = "Dog Food";
        document.getElementById("form_price").value = "";
        document.getElementById("form_sale_price").value = "";
        document.getElementById("form_description").value = "";

        document.getElementById("form_current_stock").value = 0;
        document.getElementById("current_stock_display").textContent = 0;
        document.getElementById("form_add_stock").value = 0;

        document.getElementById("form_on_sale").checked = false;
        document.getElementById("form_best_product").checked = false; // ✅ Best Product reset
        document.getElementById("form_preview").style.display = "none";
        document.getElementById("saleActiveMessage").style.display = "none";

        toggleSalePrice();
    } 
    else if (mode === "edit" && data) {
        title.textContent = "Edit Product";
        submitBtn.name = "update_product";
        submitBtn.textContent = "Update";

        document.getElementById("form_id").value = data.id;
        document.getElementById("form_old_image").value = data.image;
        document.getElementById("form_status").value = data.status;
        document.getElementById("form_name").value = data.name;
        document.getElementById("form_category").value = data.category;
        document.getElementById("form_price").value = data.price;
        document.getElementById("form_sale_price").value = data.sale_price || "";
        document.getElementById("form_description").value = data.description;

        document.getElementById("form_current_stock").value = data.stock;
        document.getElementById("current_stock_display").textContent = data.stock;
        document.getElementById("form_add_stock").value = 0;

        document.getElementById("form_on_sale").checked = data.on_sale == 1;
        document.getElementById("form_best_product").checked = data.is_best_product == 1; // ✅ Best Product toggle

        const preview = document.getElementById("form_preview");
        preview.src = "../uploaded_files/" + data.image;
        preview.style.display = "block";

        const saleMessage = document.getElementById("saleActiveMessage");
        if (data.on_sale == 1 && data.sale_price) {
            saleMessage.style.display = "block";
            saleMessage.textContent = 
                `Sale Active: Now ₱${parseFloat(data.sale_price).toFixed(2)} (was ₱${parseFloat(data.price).toFixed(2)})`;
        } else {
            saleMessage.style.display = "none";
        }

        toggleSalePrice();
    }
}


function closeForm() {
    Swal.fire({
        title: "Cancel editing?",
        text: "Any unsaved changes will be lost.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, close it",
        cancelButtonText: "Stay here",
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        customClass: { popup: 'swal2-mini' }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById("productForm");
            const productList = document.querySelector(".card");
            form.classList.remove("show");
            productList.style.display = "block";
        }
    });
}

/* ===========================================================
   💸 SALE PRICE TOGGLE
   =========================================================== */
document.addEventListener("DOMContentLoaded", () => {
    window.onSaleCheckbox = document.getElementById("form_on_sale");
    window.salePriceInput = document.getElementById("form_sale_price");

    window.toggleSalePrice = function () {
        if (onSaleCheckbox.checked) {
            salePriceInput.disabled = false;
            salePriceInput.required = true;
            salePriceInput.style.backgroundColor = "#fff";
        } else {
            salePriceInput.disabled = true;
            salePriceInput.required = false;
            salePriceInput.value = "";
            salePriceInput.style.backgroundColor = "#f1f5f9";
        }
    };

    toggleSalePrice();
    onSaleCheckbox.addEventListener("change", toggleSalePrice);
});

/* ===========================================================
   🔄 REAL-TIME PRODUCT FETCHING
   =========================================================== */
function fetchProducts() {
    fetch('fetch_products.php')
        .then(res => res.json())
        .then(products => {
            const grid = document.querySelector('.products-grid');
            if (!grid) return;

            if (products.length === 0) {
                grid.innerHTML = '<p class="no-products">No products found</p>';
                return;
            }

            grid.innerHTML = '';
            products.forEach(product => {
                const discount = product.on_sale && product.sale_price
                    ? Math.round((product.price - product.sale_price) / product.price * 100)
                    : 0;

                const bestBadge = product.is_best_product == 1
                    ? `<div class="badge badge-best-top">Best Product</div>`
                    : '';

                grid.innerHTML += `
                    <div class="product-card">
                        <div class="product-image-wrapper" style="position: relative; text-align: center;">
                            ${bestBadge}
                            ${discount > 0 ? `<div class="ribbon">-${discount}%</div>` : ''}
                            <img src="../uploaded_files/${product.image}" alt="${product.name}" class="product-img">
                        </div>
                        <div class="product-body">
                            <h3 class="product-title">${product.name}</h3>
                            <p class="product-category">${product.category}</p>
                            <p class="product-description">${product.description.substring(0,60)}...</p>
                            <div class="product-meta">
                                ${product.on_sale && product.sale_price 
                                    ? `<span class="old-price">₱${parseFloat(product.price).toFixed(2)}</span>
                                    <span class="sale-price">₱${parseFloat(product.sale_price).toFixed(2)}</span>`
                                    : `<span class="regular-price">₱${parseFloat(product.price).toFixed(2)}</span>`}
                                <span class="badge ${product.status === 'active' ? 'badge-success' : 'badge-danger'}">
                                    ${product.status.charAt(0).toUpperCase() + product.status.slice(1)}
                                </span>
                            </div>
                        </div>
                        <div class="product-footer">
                            <button class="btn btn-primary" onclick='openForm("edit", ${JSON.stringify(product)})'>✏️ Edit</button>
                            <button class="btn btn-danger" onclick="confirmDelete(${product.id})">🗑️ Delete</button>
                        </div>
                    </div>
                `;
            });

        })
        .catch(err => console.error('Error fetching products:', err));
}

/* ===========================================================
   🗑️ SWEETALERT DELETE CONFIRM
   =========================================================== */
function confirmDelete(id) {
    Swal.fire({
        title: "Delete this product?",
        text: "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, delete it",
        cancelButtonText: "Cancel",
        customClass: { popup: 'swal2-mini' }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            form.innerHTML = `
                <input type="hidden" name="product_id" value="${id}">
                <input type="hidden" name="delete_product" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>




    <script src="./../assets/js/dashboard.js"></script>
</body>
</html>
