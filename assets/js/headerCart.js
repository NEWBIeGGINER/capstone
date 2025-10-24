document.addEventListener("DOMContentLoaded", () => {
    // ======= DOM refs =======
    const userDropdownBtn = document.querySelector(".dropdown.user-dropdown .user-btn");
    const userDropdown = document.querySelector(".dropdown.user-dropdown");

    const cartDropdown = document.querySelector(".cart-dropdown");
    const cartContent = document.getElementById("cart-content");
    const cartBtn = document.querySelector(".cart-dropdown .header-cart");
    const cartCountBadge = document.getElementById("cart-count");

    const checkoutModal = document.getElementById("checkoutModal");
    const closeBtn = checkoutModal.querySelector(".close");
    const checkoutForm = document.getElementById("checkoutForm");
    const multiContainer = document.getElementById("checkoutProductsContainer");
    const totalEl = document.getElementById("checkout_total");

    const favContainer = document.createElement("div");
    favContainer.id = "favorites-container";
    favContainer.style.display = "none";

    // ======= Helpers =======
    async function postData(url, data) {
        const formData = new FormData();
        for (const key in data) formData.append(key, data[key]);
        const res = await fetch(url, { method: "POST", body: formData });
        return await res.json();
    }

    // ======= LOAD CART =======
    async function loadCart() {
        cartContent.innerHTML = '<p style="text-align:center;">Loading...</p>';
        const data = await postData("components/cart_action.php", { action: "get_items" });
        const items = (data.status === "success" && Array.isArray(data.items)) ? data.items : [];
        cartCountBadge.textContent = data.cart_count || 0;

        renderCart(items);
    }

// ======= Prepare central product data =======
const productsData = new Map();
document.querySelectorAll(".box").forEach(box => {
    const id = box.dataset.id;
    const name = box.querySelector("h3")?.textContent || "Unnamed";
    const owner = box.querySelector(".owner")?.textContent || "";
    const priceText = box.querySelector(".sale-price")?.textContent
        || box.querySelector(".amount")?.textContent
        || "0";
    const price = parseFloat(priceText.replace(/[^\d.]/g, "")) || 0;
    const img = box.querySelector("img")?.src || "";
    const stock = parseInt(box.dataset.stock) || 1; // ← add stock
    productsData.set(id, { name, owner, price, image: img, stock });
});

// ======= RENDER CART & FAVORITES (with Max Stock Alert + Empty Favorites Message) =======
function renderCart(items) {
    cartContent.innerHTML = "";

    // --- Tabs ---
    const tabsNav = document.createElement("div");
    Object.assign(tabsNav.style, { display: "flex", borderBottom: "1px solid #ccc" });

    const cartTab = document.createElement("div");
    cartTab.textContent = "Cart";
    Object.assign(cartTab.style, {
        flex: "1",
        padding: "6px",
        textAlign: "center",
        cursor: "pointer",
        fontWeight: "600",
        borderBottom: "2px solid #e67e22"
    });
    cartTab.dataset.tab = "cart";

    const favTab = document.createElement("div");
    favTab.textContent = "Favorites";
    Object.assign(favTab.style, {
        flex: "1",
        padding: "6px",
        textAlign: "center",
        cursor: "pointer"
    });
    favTab.dataset.tab = "";

    tabsNav.append(cartTab, favTab);
    cartContent.appendChild(tabsNav);

    // --- Scroll container ---
    const scrollContainer = document.createElement("div");
    Object.assign(scrollContainer.style, {
        maxHeight: "200px",
        overflowY: "auto",
        padding: "8px"
    });
    scrollContainer.id = "cart-scroll-container";
    cartContent.appendChild(scrollContainer);

    // --- Cart items container ---
    const cartItemsContainer = document.createElement("div");
    cartItemsContainer.id = "cart-items-container";
    scrollContainer.appendChild(cartItemsContainer);

    // --- Favorites container ---
    favContainer.style.display = "none";
    scrollContainer.appendChild(favContainer);

    // --- Render cart items ---
    let total = 0;
    if (items.length === 0) {
        cartItemsContainer.innerHTML = '<p style="text-align:center; color:#999;">Your cart is empty</p>';
    } else {
        items.forEach(item => {
            total += item.price * item.quantity;
            const itemDiv = document.createElement("div");
            itemDiv.className = "cart-item";
            itemDiv.dataset.id = item.product_id;
            Object.assign(itemDiv.style, {
                display: "flex",
                alignItems: "center",
                marginBottom: "6px",
                fontSize: "15px",
                cursor: "pointer"
            });

            itemDiv.innerHTML = `
                <img src="uploaded_files/${item.image}" alt="${item.name}" 
                    style="width:24px;height:24px;border-radius:3px;margin-right:4px;">
                <div class="info" style="flex:1; line-height:1.1;">
                    <div class="name" style="font-weight:400; font-size:15px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:120px;">
                        ${item.name}
                    </div>
                    <div class="price" style="font-size:11px; color:#555;">
                        ₱${parseFloat(item.price).toFixed(2)} × <span class="qty">${item.quantity}</span>
                    </div>
                    <div class="quantity" style="margin-top:1px;">
                        <button class="decrease" data-id="${item.product_id}" style="padding:0 2px;font-size:10px;line-height:1;">-</button>
                        <button class="increase" data-id="${item.product_id}" style="padding:0 2px;font-size:10px;line-height:1;">+</button>
                    </div>
                </div>
            `;

            // --- Click opens product modal ---
            itemDiv.addEventListener("click", (e) => {
                if (e.target.classList.contains("increase") || e.target.classList.contains("decrease")) return;
                openInfoModal({
                    img: `uploaded_files/${item.image}`,
                    name: item.name,
                    price: item.price,
                    description: item.description || "No description available",
                    stock: item.stock || 0,
                    avgRating: item.avgRating || 4.5,
                    reviewCount: item.reviewCount || 12,
                    feedback: item.feedback || []
                });
            });

            cartItemsContainer.appendChild(itemDiv);
        });
    }

    // --- Bottom total + checkout ---
    const bottomDiv = document.createElement("div");
    Object.assign(bottomDiv.style, {
        padding: "8px",
        borderTop: "1px solid #eee",
        textAlign: "center"
    });
    bottomDiv.innerHTML = `
        <div id="cart-total" style="font-weight:bold; font-size:15px; color:#27ae60; margin-bottom:4px;">
            Total: ₱${parseFloat(total).toFixed(2)}
        </div>
        <div class="cart-footer" style="padding-top:2px; text-align:center;">
            <button id="checkoutBtnHeader" style="display:inline-block; background:#e67e22; color:white; padding:6px 10px; border:none; border-radius:4px; font-size:12px; font-weight:600; cursor:pointer;">
                🛒 Checkout
            </button>
        </div>
    `;
    cartContent.appendChild(bottomDiv);

    function renderFavoritesFast() {
        favContainer.innerHTML = "";

        if (!favoritesSet || favoritesSet.size === 0) {
            const emptyMsg = document.createElement("div");
            emptyMsg.className = "fav-empty-msg";
            Object.assign(emptyMsg.style, {
                display: "flex",
                flexDirection: "column",
                alignItems: "center",
                justifyContent: "center",
                height: "120px",
                color: "#999",
                fontSize: "14px",
                fontWeight: "500",
                textAlign: "center",
                gap: "6px"
            });

            // 🩶 Removed the inner “Favorites” label — only message remains
            emptyMsg.innerHTML = `
                <i class="fas fa-heart" style="font-size:22px;color:#ccc;"></i>
                <span>No favorites yet</span>
            `;

            favContainer.appendChild(emptyMsg);
            return;
        }

        favoritesSet.forEach(id => {
            let favItem = favElements.get(id);
            const product = productsData.get(id);
            if (!product) return;

            if (!favItem) {
                favItem = document.createElement("div");
                favItem.className = "fav-item";
                favItem.dataset.id = id;
                Object.assign(favItem.style, {
                    display: "flex",
                    alignItems: "center",
                    marginBottom: "6px",
                    fontSize: "14px",
                    cursor: "pointer",
                    opacity: "0",
                    transform: "translateY(4px)",
                    transition: "opacity 0.2s ease, transform 0.2s ease"
                });

                favItem.innerHTML = `
                    <img src="${product.image}" alt="${product.name}" 
                        style="width:24px;height:24px;object-fit:cover;border-radius:3px;margin-right:6px;">
                    <div style="flex:1; line-height:1.2;">
                        <div class="name" style="font-weight:500; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            ${product.name}
                        </div>
                        <div class="owner" style="font-size:12px; color:#555;">
                            ${product.owner}
                        </div>
                        <div class="price" style="font-size:11px; color:#555;">
                            ₱${parseFloat(product.price).toFixed(2)}
                        </div>
                    </div>
                    <a href="javascript:void(0);" class="fav-heart" data-id="${id}" 
                        style="color:#e74c3c; font-size:14px; margin-left:6px;">
                        <i class="fas fa-heart red-heart"></i>
                    </a>
                `;

                favItem.addEventListener("click", (e) => {
                    if (e.target.closest(".fav-heart")) return;
                    openInfoModal({
                        img: product.image,
                        name: product.name,
                        price: product.price,
                        description: product.description || "No description available",
                        stock: product.stock || 0,
                        avgRating: product.avgRating || 4.5,
                        reviewCount: product.reviewCount || 12,
                        feedback: product.feedback || []
                    });
                });

                favElements.set(id, favItem);
            }

            favContainer.appendChild(favItem);

            requestAnimationFrame(() => {
                favItem.style.opacity = "1";
                favItem.style.transform = "translateY(0)";
            });
        });
    }


    // --- Tab switching ---
    function showCartTab() {
        cartTab.style.borderBottom = "2px solid #e67e22";
        favTab.style.borderBottom = "none";
        cartItemsContainer.style.display = "block";
        favContainer.style.display = "none";
        bottomDiv.style.display = "block";
    }

function showFavTab() {
    favTab.style.borderBottom = "2px solid #e67e22";
    cartTab.style.borderBottom = "none";
    cartItemsContainer.style.display = "none";
    favContainer.style.display = "block";
    bottomDiv.style.display = "none";

    // ✅ Render only if needed (avoid auto-refresh glitch)
    const hasFavItems = favContainer.querySelector(".fav-item");
    const hasEmptyMsg = favContainer.querySelector(".fav-empty-msg");

    if (!hasFavItems && !hasEmptyMsg) {
        renderFavoritesFast();
    }
}

    cartTab.addEventListener("click", showCartTab);
    favTab.addEventListener("click", showFavTab);
    showCartTab();
}

// ======= HEART CLICK =======
cartContent.addEventListener("click", async (e) => {
    const btn = e.target.closest(".product-heart, .fav-heart");
    if (!btn) return;
    e.preventDefault();

    const productId = btn.dataset.id;
    if (!productId) return;

    // Main grid heart & ribbon
    const gridHeart = document.querySelector(`.product-heart[data-id='${productId}'] i`);
    const ribbon = gridHeart?.closest(".box")?.querySelector(".favorite-ribbon");

    const isAdding = !gridHeart?.classList.contains("red-heart");

    // Toggle heart + ribbon
    gridHeart?.classList.toggle("red-heart", isAdding);
    if (ribbon) ribbon.classList.toggle("hide", !isAdding);

    if (isAdding) {
        // Add favorite
        favoritesSet.add(productId);

        // Create & append favorite item if not exist
        if (!favElements.has(productId)) {
            const product = productsData.get(productId);
            if (!product) return;

            const favItem = document.createElement("div");
            favItem.className = "fav-item";
            favItem.dataset.id = productId;
            Object.assign(favItem.style, {
                display: "flex",
                alignItems: "center",
                marginBottom: "6px",
                fontSize: "14px",
                cursor: "pointer",
                opacity: "0",
                transform: "translateY(4px)",
                transition: "opacity 0.2s ease, transform 0.2s ease"
            });

            favItem.innerHTML = `
                <img src="${product.image}" alt="${product.name}" style="width:24px;height:24px;object-fit:cover;border-radius:3px;margin-right:6px;">
                <div style="flex:1; line-height:1.2;">
                    <div class="name" style="font-weight:500; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        ${product.name}
                    </div>
                    <div class="owner" style="font-size:12px; color:#555;">
                        ${product.owner}
                    </div>
                    <div class="price" style="font-size:11px; color:#555;">
                        ₱${parseFloat(product.price).toFixed(2)}
                    </div>
                </div>
                <a href="javascript:void(0);" class="fav-heart" data-id="${productId}" style="color:#e74c3c; font-size:14px; margin-left:6px;">
                    <i class="fas fa-heart red-heart"></i>
                </a>
            `;

            favItem.addEventListener("click", (e) => {
                if (e.target.closest(".fav-heart")) return;
                openInfoModal(product);
            });

            favElements.set(productId, favItem);
            favContainer.appendChild(favItem);

            requestAnimationFrame(() => {
                favItem.style.opacity = "1";
                favItem.style.transform = "translateY(0)";
            });
        }

    } else {
        // Remove favorite
        favoritesSet.delete(productId);

        const favItem = favElements.get(productId);
        if (favItem) {
            favItem.style.opacity = "0";
            favItem.style.transform = "translateY(-8px)";
            favItem.style.transition = "opacity 0.2s ease, transform 0.2s ease";
            setTimeout(() => {
                favItem.remove();
                favElements.delete(productId);

                if (favoritesSet.size === 0) {
                    // show empty message
                    const emptyMsg = document.createElement("div");
                    emptyMsg.className = "fav-empty-msg";
                    Object.assign(emptyMsg.style, {
                        display: "flex",
                        flexDirection: "column",
                        alignItems: "center",
                        justifyContent: "center",
                        height: "120px",
                        color: "#999",
                        fontSize: "14px",
                        fontWeight: "500",
                        textAlign: "center",
                        gap: "6px"
                    });
                    emptyMsg.innerHTML = `<i class="fas fa-heart" style="font-size:22px;color:#ccc;"></i><span>No favorites yet</span>`;
                    favContainer.appendChild(emptyMsg);
                }
            }, 200);
        }
    }

    // Sync with backend
    try {
        await fetch("components/favorites_action.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `product_id=${productId}`
        });
    } catch (err) {
        console.error("Favorite sync failed:", err);
    }
});



// ======= FAVORITES CACHE WITH VIEW INFO =======
const favElements = new Map(); // id -> DOM element

function renderFavoritesFast() {
    favContainer.innerHTML = ""; // clear first

    if (!favoritesSet || favoritesSet.size === 0) {
        const emptyMsg = document.createElement("div");
        emptyMsg.className = "fav-empty-msg";
        Object.assign(emptyMsg.style, {
            display: "flex",
            flexDirection: "column",
            alignItems: "center",
            justifyContent: "center",
            height: "120px",
            color: "#999",
            fontSize: "14px",
            fontWeight: "500",
            textAlign: "center",
            gap: "6px"
        });

        emptyMsg.innerHTML = `
            <i class="fas fa-heart" style="font-size:22px;color:#ccc;"></i>
            <span>No favorites yet</span>
        `;

        favContainer.appendChild(emptyMsg);
        return;
    }

    favoritesSet.forEach(id => {
        let favItem = favElements.get(id);

        if (!favItem) {
            const product = productsData.get(id);
            if (!product) return;

            favItem = document.createElement("div");
            favItem.className = "fav-item";
            favItem.dataset.id = id;
            Object.assign(favItem.style, {
                display: "flex",
                alignItems: "center",
                marginBottom: "6px",
                fontSize: "14px",
                cursor: "pointer",
                opacity: "0",
                transform: "translateY(4px)",
                transition: "opacity 0.2s ease, transform 0.2s ease",
            });

            favItem.innerHTML = `
                <img src="${product.image}" alt="${product.name}" 
                    style="width:24px;height:24px;object-fit:cover;border-radius:3px;margin-right:6px;">
                <div style="flex:1; line-height:1.2;">
                    <div class="name" style="font-weight:500; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        ${product.name}
                    </div>
                    <div class="owner" style="font-size:12px; color:#555;">
                        ${product.owner}
                    </div>
                    <div class="price" style="font-size:11px; color:#555;">
                        ₱${parseFloat(product.price).toFixed(2)}
                    </div>
                </div>
                <a href="javascript:void(0);" class="fav-heart" data-id="${id}" 
                    style="color:#e74c3c; font-size:14px; margin-left:6px;">
                    <i class="fas fa-heart red-heart"></i>
                </a>
            `;

            // --- Add click event to open info modal ---
            favItem.addEventListener("click", (e) => {
                if (e.target.closest(".fav-heart")) return;
                openInfoModal({
                    img: product.image,
                    name: product.name,
                    price: product.price,
                    description: product.description || "No description available",
                    stock: product.stock || 0,
                    avgRating: product.avgRating || 4.5,
                    reviewCount: product.reviewCount || 12,
                    feedback: product.feedback || []
                });
            });

            favElements.set(id, favItem);
        }

        favContainer.appendChild(favItem);

        // fade-in
        requestAnimationFrame(() => {
            favItem.style.opacity = "1";
            favItem.style.transform = "translateY(0)";
        });
    });
}  




function openInfoModal(product) {
    const modal = document.getElementById("productInfoModal");
    if (!modal) return;

    modal.innerHTML = "";

    // --- Modal wrapper ---
    const modalContent = document.createElement("div");
    modalContent.className = "modal-content";
    Object.assign(modalContent.style, {
        display: "flex",
        gap: "40px",
        position: "relative",
        padding: "30px",
        maxWidth: "900px",
        background: "#fff",
        borderRadius: "12px",
        boxShadow: "0 4px 15px rgba(0,0,0,0.2)"
    });

    // --- Close button ---
    const closeBtn = document.createElement("span");
    closeBtn.innerHTML = "&times;";
    Object.assign(closeBtn.style, {
        fontSize: "28px",
        position: "absolute",
        top: "10px",
        right: "20px",
        cursor: "pointer"
    });
    closeBtn.onclick = () => modal.style.display = "none";
    modalContent.appendChild(closeBtn);

    // --- Left column (image, price, rating) ---
    const leftCol = document.createElement("div");
    Object.assign(leftCol.style, {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        gap: "20px",
        minWidth: "300px"
    });

    const imgEl = document.createElement("img");
    imgEl.src = product.img;
    imgEl.alt = product.name;
    Object.assign(imgEl.style, {
        width: "320px",
        height: "350px",
        objectFit: "cover",
        borderRadius: "12px"
    });
    leftCol.appendChild(imgEl);

    const priceEl = document.createElement("p");
    priceEl.textContent = `₱${parseFloat(product.price).toFixed(2)}`;
    Object.assign(priceEl.style, {
        fontWeight: "700",
        fontSize: "22px",
        color: "#e67e22",
        margin: "0"
    });
    leftCol.appendChild(priceEl);

    // Rating
    const ratingEl = document.createElement("div");
    ratingEl.style.textAlign = "center";
    const avgRating = product.avgRating || 4.5;
    const fullStars = Math.floor(avgRating);
    const halfStar = avgRating % 1 >= 0.5 ? 1 : 0;
    const emptyStars = 5 - fullStars - halfStar;
    let starsHTML = "★".repeat(fullStars) + (halfStar ? "½" : "") + "☆".repeat(emptyStars);
    ratingEl.innerHTML = `<div style="font-size:24px;color:#f1c40f;">${starsHTML}</div>
                          <div style="font-size:16px;color:#555;">(${product.reviewCount || 0} reviews)</div>`;
    leftCol.appendChild(ratingEl);

    modalContent.appendChild(leftCol);

    // --- Right column (name, description, feedback, button) ---
    const rightCol = document.createElement("div");
    Object.assign(rightCol.style, {
        display: "flex",
        flexDirection: "column",
        gap: "16px",
        flex: "1"
    });

    // Name
    const nameEl = document.createElement("h3");
    nameEl.textContent = product.name;
    Object.assign(nameEl.style, { fontSize: "24px", fontWeight: "700", textAlign: "center", margin: "0" });
    rightCol.appendChild(nameEl);

    // Description
    const descEl = document.createElement("p");
    descEl.textContent = product.description || "No description available";
    Object.assign(descEl.style, {
        fontSize: "16px",
        maxHeight: "170px",
        overflowY: "auto",
        padding: "8px",
        border: "1px solid #eee",
        borderRadius: "6px",
        background: "#fdfdfd",
        margin: "0"
    });
    rightCol.appendChild(descEl);

    // Feedback
    const fbHeader = document.createElement("h4");
    fbHeader.textContent = "Buyer Feedback";
    Object.assign(fbHeader.style, { fontSize: "18px", margin: "5px 0 0 0" });
    rightCol.appendChild(fbHeader);

    const feedbackContainer = document.createElement("div");
    Object.assign(feedbackContainer.style, {
        maxHeight: "120px",
        overflowY: "auto",
        padding: "6px",
        border: "1px solid #eee",
        borderRadius: "8px",
        background: "#fafafa"
    });
    const feedbackList = product.feedback || [];
    feedbackList.forEach(fb => {
        const fbDiv = document.createElement("div");
        fbDiv.style.marginBottom = "8px";
        let fbStars = "★".repeat(fb.rating) + "☆".repeat(5 - fb.rating);
        fbDiv.innerHTML = `<strong>${fb.name}</strong> <span style="color:#f1c40f;font-size:16px;">${fbStars}</span><br>${fb.comment}`;
        feedbackContainer.appendChild(fbDiv);
    });
    rightCol.appendChild(feedbackContainer);

    // Order button
    const btn = document.createElement("button");
    btn.textContent = product.stock > 0 ? "Order Now" : "Unavailable";
    btn.disabled = product.stock <= 0;
    Object.assign(btn.style, {
        marginTop: "auto",
        background: product.stock > 0 ? "#e67e22" : "#ccc",
        color: "#fff",
        padding: "12px 20px",
        fontSize: "18px",
        borderRadius: "8px",
        border: "none",
        cursor: product.stock > 0 ? "pointer" : "default"
    });
    btn.onclick = () => {
        if (product.stock > 0) openSingleCheckout(product);
        modal.style.display = "none";
    };
    rightCol.appendChild(btn);

    modalContent.appendChild(rightCol);
    modal.appendChild(modalContent);

    // --- Show modal ---
    Object.assign(modal.style, {
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        position: "fixed",
        top: "0",
        left: "0",
        width: "100%",
        height: "100%",
        background: "rgba(0,0,0,0.65)",
        zIndex: "9999"
    });

    // Close modal when clicking outside
    modal.onclick = (e) => { if (e.target === modal) modal.style.display = "none"; };
}

// ======= Centralized Cart Click Listener (with Max Stock Alert) =======
cartContent.addEventListener("click", async e => {
    const target = e.target;
    if (!target) return;

    const productId = target.dataset.id;
    let action = null;

    if (target.classList.contains("increase")) action = "increase";
    else if (target.classList.contains("decrease")) action = "decrease";

    if (action && productId) {
        e.stopPropagation();

        const data = await postData("components/cart_action.php", { action, product_id: productId });
        if (data.status !== "success") return;

        const itemDiv = cartContent.querySelector(`.cart-item[data-id="${productId}"]`);
        const qtyEl = itemDiv?.querySelector(".qty");

        // --- Max stock alert for increase ---
        if (action === "increase" && data.new_quantity > data.max_stock) {
            if (qtyEl) qtyEl.textContent = data.max_stock;
            Swal.fire({
                icon: "warning",
                title: "Max Stock Reached",
                text: `Only ${data.max_stock} unit(s) available.`
            });
            return; // stop further updates
        }

        // --- Update quantity or remove item ---
        if (data.new_quantity > 0 && qtyEl) {
            qtyEl.textContent = data.new_quantity;
        } else if (itemDiv) {
            itemDiv.remove();
        }

        // Update cart count badge
        cartCountBadge.textContent = data.cart_count;

        // Update total
        const cartTotalEl = document.getElementById("cart-total");
        if (cartTotalEl) {
            cartTotalEl.textContent =
                data.cart_total > 0
                    ? `Total: ₱${parseFloat(data.cart_total).toFixed(2)}`
                    : 'Your cart is empty';
        }
    }

    // --- Checkout button ---
    if (target.id === "checkoutBtnHeader") {
        e.preventDefault();
        const data = await postData("components/cart_action.php", { action: "get_items" });
        if (data.status === "success" && data.items.length > 0) {
            const cartItems = data.items.map(item => ({
                id: item.product_id,
                name: item.name,
                price: parseFloat(item.price),
                qty: parseInt(item.quantity),
                img: "uploaded_files/" + item.image
            }));
            openMultipleCheckout(cartItems);
        } else {
            Swal.fire({
                icon: "info",
                title: "Empty Cart",
                text: "Your cart is empty."
            });
        }
    }
});

    // ======= Dropdowns =======
    userDropdownBtn.addEventListener("click", e => {
        e.stopPropagation();
        userDropdown.classList.toggle("show");
    });

    cartBtn.addEventListener("click", async e => {
        e.stopPropagation();
        const isOpen = cartDropdown.classList.contains("show");
        document.querySelectorAll(".dropdown, .cart-dropdown").forEach(el => el.classList.remove("show"));
        if (!isOpen) {
            cartDropdown.classList.add("show");
            await loadCart();
        }
    });

    document.addEventListener("click", () => {
        userDropdown.classList.remove("show");
        cartDropdown.classList.remove("show");
    });

    cartDropdown.addEventListener("click", e => e.stopPropagation());
    userDropdown.addEventListener("click", e => e.stopPropagation());


    // ======= Close modal =======
    closeBtn.addEventListener("click", () => checkoutModal.style.display = "none");
    checkoutForm.querySelector(".cancel").addEventListener("click", () => checkoutModal.style.display = "none");
    window.addEventListener("click", e => { if (e.target === checkoutModal) checkoutModal.style.display = "none"; });

    // ======= Checkout submit =======
    checkoutForm.addEventListener("submit", async e => {
        e.preventDefault();
        e.stopImmediatePropagation();

        const btn = checkoutForm.querySelector('button[type="submit"]');
        const originalBtnHtml = btn.innerHTML;
        if (btn.disabled) return;

        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Placing...`;

        const formData = new FormData(checkoutForm);
        formData.append("place_order", "1");

        try {
            const res = await fetch("components/checkout.php", { method: "POST", body: formData });
            const text = await res.text();
            let data;
            try { data = JSON.parse(text); } catch { throw new Error("Server returned invalid JSON"); }

            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;

            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Order Successful!', html: data.msg || 'Check your email.', timer: 2000, timerProgressBar: true, showConfirmButton: false });
                checkoutForm.reset();
                if (multiContainer) multiContainer.innerHTML = "";
                checkoutModal.style.display = "none";

                await postData("components/cart_action.php", { action: "clear_cart" });
                cartCountBadge.textContent = 0;
                cartContent.innerHTML = '<p style="text-align:center;">Your cart is empty</p>';
            } else {
                Swal.fire({ icon: 'error', title: 'Order Failed', html: data.msg || 'Something went wrong.', showConfirmButton: true });
            }
        } catch (err) {
            console.error("Checkout error:", err);
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Something went wrong.', showConfirmButton: true });
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;
        }
    });
});
