/* ---------- GLOBAL ---------- */
const modal = document.getElementById("checkoutModal");
const closeBtn = document.querySelector(".close");
const cancelBtn = document.querySelector(".cancel");
const cartCountSpan = document.getElementById('cart-count');
const categoryFilter = document.getElementById('categoryFilter');
let lastData = [];
let selectedCategory = '';
const productElements = new Map(); // store product DOM for smooth updates
let fetchTimer = null; // timer for fetch loop
const favoritesSet = new Set(); // memory for favorites
let isFetching = false;

/* ===========================================================
   🛒 FETCH PRODUCTS REALTIME
   =========================================================== */
function fetchProductsRealtime() {
  if (isFetching) return; // 🚫 prevent overlap
  isFetching = true;

  let url = `?fetch_products_real=1`;
  if (selectedCategory && selectedCategory !== 'all') {
    url += `&category=${encodeURIComponent(selectedCategory)}`;
  }

  fetch(url)
    .then(r => r.json())
    .then(data => {
      // 🛠 Flatten if nested arrays
      if (Array.isArray(data) && Array.isArray(data[0])) data = data.flat();

      // Normalize IDs + remove duplicates
      data = Array.from(
        new Map(data.map(p => [String(p.id), { ...p, id: String(p.id) }])).values()
      );

      updateProducts(data);
      lastData = data;
    })
    .catch(console.error)
    .finally(() => {
      isFetching = false;
      clearTimeout(fetchTimer);
      fetchTimer = setTimeout(fetchProductsRealtime, 5000);
    });
}

fetchProductsRealtime();


/* ===========================================================
   🔍 LIVE SEARCH + CATEGORY FILTER (FINAL FIXED VERSION)
   =========================================================== */
let currentSearch = '';
const searchInput = document.getElementById('searchInput');

/* --- Search --- */
if (searchInput) {
  searchInput.addEventListener('input', () => {
    currentSearch = searchInput.value.trim().toLowerCase();
    applyFilters();
  });
}

/* --- Category --- */
if (categoryFilter) {
  categoryFilter.addEventListener('change', () => {
    selectedCategory = categoryFilter.value.trim().toLowerCase();

    // 🧹 Clear existing items before fetching new ones
    lastData = [];
    productElements.forEach(box => fadeOutRemove(box));
    productElements.clear();

    clearTimeout(fetchTimer);
    fetchProductsRealtime();

    // 🪄 Ensure dropdown opens downward
    categoryFilter.style.position = 'relative';
    categoryFilter.style.zIndex = '9999';
  });
}

/* --- Apply both filters (Optimized for performance & smoothness) --- */
function applyFilters() {
  if (window.__filtering__) return; // prevent rapid consecutive calls
  window.__filtering__ = true;

  const boxes = document.querySelectorAll('#product-list .box');
  const search = currentSearch;
  const cat = selectedCategory;
  let visibleCount = 0;

  // Use requestAnimationFrame batching for smoother transitions
  requestAnimationFrame(() => {
    boxes.forEach(box => {
      const name = (box.querySelector('h3')?.textContent || '').toLowerCase();
      const desc = (box.querySelector('p')?.textContent || '').toLowerCase();
      const productCategory = (box.dataset.category || '').toLowerCase();

      const matchesCategory = !cat || productCategory.includes(cat);
      const matchesSearch = !search || name.includes(search) || desc.includes(search);
      const shouldShow = matchesCategory && matchesSearch;

      if (shouldShow) {
        visibleCount++;
        if (box.classList.contains("hidden")) {
          box.classList.remove("hidden");
          box.style.display = "block";
          requestAnimationFrame(() => {
            box.style.opacity = "1";
            box.style.transform = "scale(1)";
          });
        }
      } else {
        if (!box.classList.contains("hidden")) {
          box.classList.add("hidden");
          box.style.opacity = "0";
          box.style.transform = "scale(0.97)";
          setTimeout(() => {
            if (box.classList.contains("hidden")) box.style.display = "none";
          }, 180); // shorter fade duration
        }
      }
    });

    // ✅ Show/hide "no results" message efficiently
    let msg = document.getElementById('noResultsMsg');
    if (!msg) {
      msg = document.createElement('div');
      msg.id = 'noResultsMsg';
      msg.textContent = 'No products found.';
      msg.style.cssText = `
        text-align:center;
        color:#888;
        margin-top:20px;
        display:none;
        transition:opacity 0.3s ease;
      `;
      document.getElementById('product-list').appendChild(msg);
    }
    msg.style.display = visibleCount === 0 ? 'block' : 'none';
    msg.style.opacity = visibleCount === 0 ? '1' : '0';

    window.__filtering__ = false;
  });
}

/* ===========================================================
   🧩 HELPER FUNCTIONS
   =========================================================== */
function fadeOutRemove(box) {
  if (!box) return;
  box.style.transition = "opacity 0.3s ease";
  box.style.opacity = "0";
  setTimeout(() => box.remove(), 300);
}


/* ===========================================================
   🖼️ UPDATE PRODUCTS (with Best Product badge)
   =========================================================== */
function updateProducts(data) {
  const container = document.getElementById("product-list");
  const currentIds = new Set(data.map(p => String(p.id)));

  // 🧹 Remove missing items
  container.querySelectorAll(".box").forEach(el => {
    if (!currentIds.has(el.dataset.id)) el.remove();
  });

  // Remove products no longer present in productElements map
  productElements.forEach((box, id) => {
    if (!currentIds.has(String(id))) {
      fadeOutRemove(box);
      productElements.delete(id);
    }
  });

  // Render or update each product
  data.forEach(p => {
    const pid = String(p.id);
    const discount = (p.on_sale && p.sale_price)
      ? Math.round(((p.price - p.sale_price) / p.price) * 100)
      : 0;

    const isFav = favoritesSet.has(pid) || p.is_favorite == 1;
    const isFavClass = isFav ? "red-heart" : "";
    const favRibbonClass = isFav ? "" : "hide";
    if (p.is_favorite == 1) favoritesSet.add(pid);

    let box = productElements.get(pid);
    const isNewBox = !box;

    if (isNewBox) {
      container.querySelectorAll(`.box[data-id="${pid}"]`).forEach(el => el.remove());
      box = document.createElement("div");
      box.className = "box";
      box.dataset.id = pid;
      box.dataset.category = (p.category || '').toLowerCase();
      box.style.opacity = "0";
      container.appendChild(box);
      productElements.set(pid, box);
    }

    const newHTML = `
      ${p.is_best_product == 1 ? `<div class="badge-best-top">BEST PRODUCT</div>` : ""}
      ${discount ? `<div class="sale-ribbon">-${discount}%</div>` : ""}
      <div class="favorite-ribbon ${favRibbonClass}">FAVORITE</div>
      <div class="icons">
        ${p.stock > 0
          ? `<a href="javascript:void(0);" class="add-to-cart" data-id="${pid}">
               <i class="fas fa-shopping-cart"></i>
             </a>`
          : `<a href="javascript:void(0);" style="color:#aaa" title="Out of Stock">
               <i class="fas fa-ban"></i>
             </a>`}
        <a href="javascript:void(0);" class="product-heart" data-id="${pid}">
          <i class="fas fa-heart ${isFavClass}"></i>
        </a>
        <a href="#"><i class="fas fa-eye"></i></a>
      </div>
      <img src="uploaded_files/${p.image}" alt="${p.name}">
      <h3>${p.name}</h3>
      <p>${p.description}</p>
      <div class="amount">
        ${p.on_sale && p.sale_price
          ? `<span class="old-price">₱${Number(p.price).toFixed(2)}</span>
             <span class="sale-price">₱${Number(p.sale_price).toFixed(2)}</span>`
          : `₱${Number(p.price).toFixed(2)}`}
      </div>
      ${p.stock > 0
        ? `<button class="order-btn"
              data-id="${pid}"
              data-name="${p.name}"
              data-price="${p.on_sale && p.sale_price ? p.sale_price : p.price}"
              data-img="uploaded_files/${p.image}">
              <i class="fas fa-shopping-cart"></i> Order Now
           </button>
           <p class="stock" data-stock="${p.stock}">Available: ${p.stock}</p>`
        : `<button class="order-btn" disabled style="background:#ccc;cursor:not-allowed;">
              <i class="fas fa-ban"></i> Unavailable
           </button>
           <p class="unavailable" style="color:red;font-weight:bold;">
              This product is Out of Stock
           </p>`}
    `;

    if (isNewBox || box.innerHTML !== newHTML) {
      box.innerHTML = newHTML;
    }

    if (isNewBox) {
      requestAnimationFrame(() => {
        box.style.transition = "opacity 0.3s ease";
        box.style.opacity = "1";
      });
    }
  });

  // Sync ribbons & hearts
  productElements.forEach((box, id) => {
    const ribbon = box.querySelector(".favorite-ribbon");
    if (ribbon) ribbon.classList.toggle("hide", !favoritesSet.has(String(id)));
    const heart = box.querySelector(".product-heart i");
    if (heart) heart.classList.toggle("red-heart", favoritesSet.has(String(id)));
  });

  renderFavorites();
  applyFilters(); 
  attachCartListeners();
  attachInfoListeners();
  attachOrderNowListeners();
}



/* ---------- CART ANIMATION & ADD (server-side stock check) ---------- */
function attachCartListeners() {
  const cartIcon = document.querySelector('.header-cart i');

  document.querySelectorAll('.box .add-to-cart').forEach(btn => {
    btn.onclick = e => {
      e.preventDefault();

      const id = btn.dataset.id;
      const box = btn.closest(".box");
      const img = box.querySelector("img");
      let currentQty = parseInt(box.dataset.cartQty || '0', 10);

      // --- Add to cart request with server-side stock check ---
      fetch('components/cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_to_cart&product_id=${id}&quantity=1`
      })
      .then(r => r.json())
      .then(d => {
        if (d.status === 'success') {
          // --- Animation only if add is successful ---
          if (img && cartIcon) {
            const rect = img.getBoundingClientRect();
            const f = img.cloneNode(true);
            f.style.cssText = `
              position:fixed;
              left:${rect.left}px;
              top:${rect.top}px;
              width:${rect.width}px;
              height:${rect.height}px;
              transition:all 0.8s ease-in-out;
              z-index:9999;
              pointer-events:none;
            `;
            document.body.appendChild(f);
            f.getBoundingClientRect();
            const c = cartIcon.getBoundingClientRect();
            setTimeout(() => {
              f.style.left = c.left + 'px';
              f.style.top = c.top + 'px';
              f.style.width = '0px';
              f.style.height = '0px';
              f.style.opacity = 0;
            }, 20);
            f.addEventListener('transitionend', () => f.remove());

            cartIcon.classList.remove('cart-bounce'); 
            void cartIcon.offsetWidth; 
            cartIcon.classList.add('cart-bounce');
            setTimeout(() => cartIcon.classList.remove('cart-bounce'), 500);
          }

          // --- Update cart count and UI counter ---
          cartCountSpan.textContent = d.cart_count;
          box.dataset.cartQty = (currentQty + 1).toString();

        } else if (d.status === 'error') {
          // --- Stock full alert ---
          Swal.fire({
            icon: "warning",
            title: "Max Stock Reached",
            text: d.message
          });
        }
      })
      .catch(console.error);
    };
  });
}




/* ---------- FAVORITES LIST ---------- */
async function renderFavorites() {
  const favoritesContainer = document.getElementById("favorites-container");
  if (!favoritesContainer) return;

  if (!favoritesContainer.querySelector("#favorites-list")) {
    favoritesContainer.innerHTML = `<h4 style="margin-bottom:5px;">Favorites</h4><div id="favorites-list"></div>`;
  }
  const listEl = favoritesContainer.querySelector("#favorites-list");

  // Remove items no longer favorites
  listEl.querySelectorAll(".fav-item").forEach(item => {
    const id = item.querySelector(".fav-heart")?.dataset.id;
    if (id && !favoritesSet.has(id)) {
      item.style.transition = "opacity 0.3s ease";
      item.style.opacity = "0";
      setTimeout(() => item.remove(), 300);
    }
  });

  // Add new favorites
  favoritesSet.forEach(id => {
    if (listEl.querySelector(`.fav-heart[data-id='${id}']`)) return;

    const box = document.querySelector(`.product-heart[data-id='${id}']`)?.closest(".box");
    if (!box) return;

    const favItem = document.createElement("div");
    favItem.className = "fav-item";
    favItem.style.display = "flex";
    favItem.style.alignItems = "center";
    favItem.style.marginBottom = "5px";
    favItem.style.opacity = "0";
    favItem.style.transition = "opacity 0.3s ease";
    favItem.innerHTML = `
      <img src="${box.querySelector("img").src}" style="width:30px;height:30px;margin-right:5px;border-radius:4px;">
      <span style="flex:1">${box.querySelector("h3").textContent}</span>
      <a href="javascript:void(0);" class="fav-heart" data-id="${id}">
        <i class="fas fa-heart red-heart"></i>
      </a>
    `;
    listEl.appendChild(favItem);
    requestAnimationFrame(() => { favItem.style.opacity = "1"; });
  });

  if (listEl.children.length === 0) listEl.innerHTML = "<p style='text-align:center;'></p>";
}

/* ---------- FAVORITES CLICK WITH ANIMATION ---------- */
document.addEventListener("click", async e => {
  const btn = e.target.closest(".product-heart, .fav-heart");
  if (!btn) return;
  e.preventDefault();

  const productId = btn.dataset.id;
  if (!productId) return;

  const isAdded = !favoritesSet.has(productId);
  if (isAdded) favoritesSet.add(productId);
  else favoritesSet.delete(productId);

  // ✅ Enhanced heart animation with particles
  document.querySelectorAll(`.product-heart[data-id='${productId}'] i`).forEach(i => {
    i.classList.toggle("red-heart", isAdded);
    const ribbon = i.closest(".box")?.querySelector(".favorite-ribbon");
    if (ribbon) ribbon.classList.toggle("hide", !isAdded);
    
    // Heart pop animation
    i.style.transition = "transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55)";
    i.style.transform = "scale(1.5)";
    setTimeout(() => i.style.transform = "scale(1)", 300);

    // ✅ Create floating hearts animation when adding to favorites
    if (isAdded) {
      const rect = i.getBoundingClientRect();
      for (let j = 0; j < 8; j++) {
        const particle = document.createElement("i");
        particle.className = "fas fa-heart";
        particle.style.cssText = `
          position: fixed;
          left: ${rect.left + rect.width / 2}px;
          top: ${rect.top + rect.height / 2}px;
          color: #ff4757;
          font-size: ${Math.random() * 10 + 10}px;
          pointer-events: none;
          z-index: 9999;
          opacity: 1;
          transition: all 1s ease-out;
        `;
        document.body.appendChild(particle);
        
        const angle = (j / 8) * Math.PI * 2;
        const distance = 50 + Math.random() * 50;
        
        requestAnimationFrame(() => {
          particle.style.transform = `translate(${Math.cos(angle) * distance}px, ${Math.sin(angle) * distance - 30}px) rotate(${Math.random() * 360}deg)`;
          particle.style.opacity = "0";
        });
        
        setTimeout(() => particle.remove(), 1000);
      }
    }
  });

  const favContainer = document.getElementById("favorites-container");
  if (favContainer && favContainer.style.display !== "none") await renderFavorites();

  try {
    await fetch("components/favorites_action.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `product_id=${productId}`
    });
  } catch (err) { console.error(err); }
});




/* ---------- PRODUCT INFO ---------- */
function attachInfoListeners() {
    document.querySelectorAll(".fa-eye").forEach(icon => {
        icon.closest("a").onclick = e => {
            e.preventDefault();
            const box = icon.closest(".box");
            const btn = box.querySelector(".order-btn");
            const product = {
                id: btn.dataset.id,
                name: box.querySelector("h3").textContent,
                description: box.querySelector("p").textContent,
                price: parseFloat(btn.dataset.price),
                stock: parseInt(box.querySelector(".stock")?.textContent.replace("Available: ", "")) || 0,
                img: box.querySelector("img").src,
                rating: parseFloat(box.dataset.rating || 4.6),
                reviewCount: parseInt(box.dataset.reviewCount || 12)
            };
            openInfoModal(product);
        };
    });
}

function openInfoModal(product) {
    const modal = document.getElementById("productInfoModal");
    if (!modal) return;

    modal.innerHTML = "";

    // Modal wrapper
    const modalContent = document.createElement("div");
    modalContent.className = "modal-content";
    modalContent.style.display = "flex";
    modalContent.style.gap = "40px";
    modalContent.style.position = "relative";
    modalContent.style.padding = "30px";
    modalContent.style.maxWidth = "900px";

    // Close button
    const closeBtn = document.createElement("span");
    closeBtn.className = "close-info";
    closeBtn.innerHTML = "&times;";
    closeBtn.style.fontSize = "28px";
    closeBtn.onclick = () => modal.style.display = "none";
    modalContent.appendChild(closeBtn);

    // --- Left column: Image, Price, Rating + Reviews ---
    const leftCol = document.createElement("div");
    leftCol.style.display = "flex";
    leftCol.style.flexDirection = "column";
    leftCol.style.alignItems = "center";
    leftCol.style.gap = "20px";
    leftCol.style.minWidth = "300px";

    // Product image
    const imgEl = document.createElement("img");
    imgEl.src = product.img;
    imgEl.alt = product.name;
    imgEl.style.width = "320px";
    imgEl.style.height = "350px";
    imgEl.style.objectFit = "cover";
    imgEl.style.borderRadius = "12px";
    leftCol.appendChild(imgEl);

    // Price
    const priceEl = document.createElement("p");
    priceEl.textContent = `₱${product.price.toFixed(2)}`;
    priceEl.style.fontWeight = "700";
    priceEl.style.fontSize = "22px";
    priceEl.style.marginBottom = "4px"; // konting space lang sa ibaba
    priceEl.style.color = "#e67e22";
    leftCol.appendChild(priceEl);

    // Rating + reviews
    const ratingEl = document.createElement("div");
    ratingEl.style.textAlign = "center";
    ratingEl.style.marginTop = "6px"; // optional konting taas para separation
    const avgRating = product.avgRating || 4.5;
    const totalReviews = product.reviewCount || 12;
    const fullStars = Math.floor(avgRating);
    const halfStar = avgRating % 1 >= 0.5 ? 1 : 0;
    const emptyStars = 5 - fullStars - halfStar;

    let starsHTML = "";
    for (let i = 0; i < fullStars; i++) starsHTML += "★";
    if (halfStar) starsHTML += "½";
    for (let i = 0; i < emptyStars; i++) starsHTML += "☆";

    ratingEl.innerHTML = `
        <div style="font-size:24px;color:#f1c40f;">${starsHTML}</div>
        <div style="font-size:16px;color:#555;">(${totalReviews} reviews)</div>
    `;
    leftCol.appendChild(ratingEl);

    modalContent.appendChild(leftCol);


    // --- Right column: Name, Description, Feedback, Order Button ---
    const rightCol = document.createElement("div");
    rightCol.style.display = "flex";
    rightCol.style.flexDirection = "column";
    rightCol.style.gap = "16px";
    rightCol.style.flex = "1";

    // Name
    const nameEl = document.createElement("h3");
    nameEl.textContent = product.name;
    nameEl.style.fontSize = "24px";
    nameEl.style.textAlign = "center";
    nameEl.style.fontWeight = "700";
    rightCol.appendChild(nameEl);

    // --- Description BELOW name, above feedback ---
    const descEl = document.createElement("p");
    descEl.textContent = product.description;
    descEl.style.fontSize = "16px";
    descEl.style.maxHeight = "170px";        // fixed height
    descEl.style.overflowY = "auto";         // enables scroll
    descEl.style.padding = "6px";            // internal padding
    descEl.style.marginTop = "12px";         // space below name
    descEl.style.marginBottom = "10px";      // space above feedback
    descEl.style.border = "1px solid #eee";  // optional border
    descEl.style.borderRadius = "6px";       // rounded edges
    descEl.style.background = "#fdfdfd";     // subtle background
    descEl.style.boxSizing = "border-box";   // include padding in height
    rightCol.appendChild(descEl);

    // Buyer Feedback header
    const fbHeader = document.createElement("h4");
    fbHeader.textContent = "Buyer Feedback";
    fbHeader.style.fontSize = "18px";
    fbHeader.style.marginTop = "0";    // no extra spacing
    fbHeader.style.marginBottom = "5px"; 
    rightCol.appendChild(fbHeader);


    // Scrollable feedback
    const feedbackContainer = document.createElement("div");
    feedbackContainer.style.maxHeight = "120px";       // fixed height
    feedbackContainer.style.overflowY = "auto";       // enables scroll
    feedbackContainer.style.padding = "6px";          // internal padding
    feedbackContainer.style.marginBottom = "12px";    // space below feedback
    feedbackContainer.style.border = "1px solid #eee"; // optional border
    feedbackContainer.style.borderRadius = "8px";     // rounded corners
    feedbackContainer.style.background = "#fafafa";   // subtle background
    feedbackContainer.style.boxSizing = "border-box"; // include padding in height

    const feedbackList = product.feedback || [
        { name: "John Doe", rating: 5, comment: "Amazing product! Highly recommended." },
        { name: "Maria Santos", rating: 4, comment: "Good quality, fast shipping!" },
        { name: "Alex Cruz", rating: 3, comment: "Okay product, packaging could be better." },
        { name: "John Doe", rating: 5, comment: "Amazing product! Highly recommended." },
        { name: "John Doe", rating: 5, comment: "Amazing product! Highly recommended." },
        { name: "John Doe", rating: 5, comment: "Amazing product! Highly recommended." }
    ];

    feedbackList.forEach(fb => {
        const fbDiv = document.createElement("div");
        fbDiv.style.marginBottom = "10px";
        let fbStars = "";
        for (let i = 0; i < fb.rating; i++) fbStars += "★";
        for (let i = fb.rating; i < 5; i++) fbStars += "☆";
        fbDiv.innerHTML = `<strong>${fb.name}</strong> <span style="color:#f1c40f;font-size:18px;">${fbStars}</span><br>${fb.comment}`;
        feedbackContainer.appendChild(fbDiv);
    });

    rightCol.appendChild(feedbackContainer);


    // Order button
    const btn = document.createElement("button");
    btn.textContent = product.stock > 0 ? "Order Now" : "Unavailable";
    btn.disabled = product.stock <= 0;
    btn.style.marginTop = "auto";
    btn.style.background = product.stock > 0 ? "#e67e22" : "#ccc";
    btn.style.color = "#fff";
    btn.style.padding = "12px 20px";
    btn.style.fontSize = "18px";
    btn.style.borderRadius = "8px";
    btn.style.border = "none";
    btn.style.cursor = product.stock > 0 ? "pointer" : "default";
    btn.onclick = () => {
        modal.style.display = "none";
        if (product.stock > 0) openSingleCheckout(product);
    };
    rightCol.appendChild(btn);

    modalContent.appendChild(rightCol);
    modal.appendChild(modalContent);

    // Show modal
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
        zIndex: "9999",
    });

        // Close modal when clicking outside the modal content
    modal.onclick = (e) => {
        if (e.target === modal) modal.style.display = "none";
    };
}




/* ---------- ORDER NOW ---------- */
function attachOrderNowListeners() {
  document.querySelectorAll(".order-btn").forEach(btn => {
    btn.onclick = () => {
      const box = btn.closest(".box");
      const product = {
        id: btn.dataset.id,
        name: btn.dataset.name,
        price: parseFloat(btn.dataset.price),
        img: btn.dataset.img,
        stock: parseInt(box.querySelector(".stock")?.textContent.replace("Available: ", "")) || 0
      };
      openSingleCheckout(product);
    };
  });
}

/* ---------- SINGLE CHECKOUT ---------- */
function openSingleCheckout(product) {
  const modalProducts = document.getElementById("modalProducts");
  modalProducts.className = "single-checkout";
  modalProducts.innerHTML = `
    <div class="product-item" data-price="${product.price}">
      <img src="${product.img}">
      <div class="details">
        <h3>${product.name}</h3>
        <p>Price: ₱${product.price.toFixed(2)}</p>
        <label>Quantity</label>
        <div class="qty-box" data-stock="${product.stock}">
          <button class="qty-btn minus">-</button>
          <input type="number" id="quantity" class="qty" value="1" min="1" max="${product.stock}">
          <button class="qty-btn plus">+</button>
        </div>
        <div class="subtotal-box">Subtotal: ₱<span class="subtotal">${product.price.toFixed(2)}</span></div>
      </div>
    </div>
  `;
  document.getElementById("hiddenProductId").value = product.id;
  document.getElementById("hiddenPrice").value = product.price;
  document.getElementById("hiddenQty").value = 1;
  modal.style.display = "flex";
  loadDeliveryOptions();
  bindQtyButtons();
  updateTotal();
}

/* ---------- MULTI CHECKOUT ---------- */
function openMultipleCheckout(cartItems) {
  const modalProducts = document.getElementById("modalProducts");
  modalProducts.className = "multi-checkout";
  modalProducts.innerHTML = cartItems.map(item => `
    <div class="product-item" data-price="${item.price}">
      <img src="${item.img}">
      <div class="details">
        <h3>${item.name}</h3>
        <p>Price: ₱${item.price.toFixed(2)}</p>
        <label>Qty</label>
        <div class="qty-box" data-stock="${item.stock}">
          <button class="qty-btn minus">-</button>
          <input type="number" class="qty" name="quantities[]" value="${item.qty}" min="1" max="${item.stock}" data-stock="${item.stock}">
          <button class="qty-btn plus">+</button>
        </div>
        <input type="hidden" name="product_ids[]" value="${item.id}">
        <div class="subtotal-box">Subtotal: ₱<span class="subtotal">${(item.price * item.qty).toFixed(2)}</span></div>
      </div>
    </div>`).join('');
  modal.style.display = "flex";
  loadDeliveryOptions();
  bindQtyButtons();
  updateTotal();
}

/* ---------- QTY BIND ---------- */
function bindQtyButtons() {
  document.querySelectorAll(".qty-box").forEach(box => {
    const minus = box.querySelector(".minus"),
          plus = box.querySelector(".plus"),
          qtyInput = box.querySelector(".qty");
    const stockLimit = parseInt(qtyInput.dataset.stock) || Infinity;
    const subtotal = box.closest(".product-item").querySelector(".subtotal");
    const price = parseFloat(box.closest(".product-item").dataset.price);
    const hiddenQty = document.getElementById("hiddenQty");

    function updateAll() {
      if (hiddenQty) hiddenQty.value = qtyInput.value;
      updateSubtotal(subtotal, price, qtyInput.value);
      updateTotal();
    }

    minus.onclick = () => { let v = parseInt(qtyInput.value) || 1; if (v > 1) qtyInput.value = v - 1; updateAll(); };
    plus.onclick = () => {
      let v = parseInt(qtyInput.value) || 1;
      if (v < stockLimit) qtyInput.value = v + 1;
      else {
        qtyInput.value = stockLimit;
        Swal.fire({ icon: "warning", title: "Max Stock Reached", text: `Only ${stockLimit} unit(s) available.` });
      }
      updateAll();
    };

    qtyInput.addEventListener("input", () => {
      let v = parseInt(qtyInput.value) || 1;
      if (v > stockLimit) qtyInput.value = stockLimit;
      else if (v < 1) qtyInput.value = 1;
      updateAll();
    });
  });
}

function updateSubtotal(elem, price, qty) { elem.textContent = (price * qty).toFixed(2); }

/* ---------- STOCK UPDATE ---------- */
function updateStockAfterOrder(single = true) {
  if (single) {
    const productId = document.getElementById("hiddenProductId").value;
    const qty = parseInt(document.getElementById("hiddenQty").value) || 1;
    const box = document.querySelector(`.box .order-btn[data-id='${productId}']`)?.closest(".box");
    if (!box) return;
    const stockElem = box.querySelector(".stock");
    let newStock = Math.max(0, parseInt(stockElem.dataset.stock) - qty);
    stockElem.dataset.stock = newStock;
    if (newStock <= 0) {
      stockElem.textContent = "This product is unavailable";
      stockElem.style.color = "red";
      stockElem.style.fontWeight = "bold";
      const btn = box.querySelector(".order-btn");
      btn.disabled = true;
      btn.style.background = "#ccc";
      btn.innerHTML = `<i class="fas fa-ban"></i> Unavailable`;
    } else stockElem.textContent = `Available: ${newStock}`;
  } else {
    document.querySelectorAll(".multi-checkout .product-item").forEach(item => {
      const qtyInput = item.querySelector(".qty");
      const qty = parseInt(qtyInput.value) || 1;
      const prodId = item.querySelector("input[name='product_ids[]']").value;
      const box = document.querySelector(`.box .order-btn[data-id='${prodId}']`)?.closest(".box");
      if (box) {
        const stockElem = box.querySelector(".stock");
        let newStock = Math.max(0, parseInt(stockElem.dataset.stock) - qty);
        stockElem.dataset.stock = newStock;
        if (newStock <= 0) {
          stockElem.textContent = "This product is unavailable";
          stockElem.style.color = "red";
          stockElem.style.fontWeight = "bold";
          const btn = box.querySelector(".order-btn");
          btn.disabled = true;
          btn.style.background = "#ccc";
          btn.innerHTML = `<i class="fas fa-ban"></i> Unavailable`;
        } else stockElem.textContent = `Available: ${newStock}`;
        qtyInput.dataset.stock = newStock;
      }
    });
  }
}

/* ---------- CLOSE MODAL ---------- */
closeBtn.onclick = () => modal.style.display = "none";
cancelBtn.onclick = () => modal.style.display = "none";

/* ---------- INIT ---------- */
attachCartListeners();
attachInfoListeners();
attachOrderNowListeners();