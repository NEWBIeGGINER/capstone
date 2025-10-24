const container = document.getElementById('product-list');

function renderProducts(products) {
    container.innerHTML = '';

    products.forEach(product => {
        const box = document.createElement('div');
        box.className = 'box';

        // 🔹 BEST PRODUCT BADGE
        const bestBadge = product.is_best_product ? '<div class="badge-best-top">BEST PRODUCT</div>' : '';

        // 🔹 SALE RIBBON
        let amountHTML = '';
        let saleRibbonHTML = '';
        if (product.on_sale && product.sale_price) {
            const discount = Math.round(((product.price - product.sale_price) / product.price) * 100);
            amountHTML = `
                <span class="old-price">₱${parseFloat(product.price).toFixed(2)}</span>
                <span class="sale-price">₱${parseFloat(product.sale_price).toFixed(2)}</span>
            `;
            saleRibbonHTML = `<div class="sale-ribbon">-${discount}%</div>`;
        } else {
            amountHTML = `₱${parseFloat(product.price).toFixed(2)}`;
        }

        // 🔹 FAVORITE RIBBON
        const favoriteRibbon = product.is_favorite ? '<div class="favorite-ribbon">FAVORITE</div>' : '';

        // 🔹 STOCK BUTTON
        const stockButton = product.stock > 0
            ? `<button class="order-now-btn" onclick="goToProduct(${product.id})">
                   <i class="fas fa-shopping-cart"></i> Order Now
               </button>
               <p class="stock" style="margin-top:5px;">Available: ${product.stock}</p>`
            : `<button class="order-now-btn" disabled>
                   <i class="fas fa-ban"></i> Unavailable
               </button>
               <p class="unavailable" style="color:red;font-weight:bold;">Out of Stock</p>`;

        // Compose box innerHTML
        box.innerHTML = `
            ${bestBadge}
            ${saleRibbonHTML}
            ${favoriteRibbon}
            <div class="image">
                <img src="uploaded_files/${product.image}" alt="${product.name}">
            </div>
            <h3>${product.name}</h3>
            <p>${product.description}</p>
            <div class="amount">${amountHTML}</div>
            ${stockButton}
        `;

        container.appendChild(box);
    });
}

function goToProduct(productId) {
    window.location.href = `products.php?product_id=${productId}`;
}

// Fetch products from backend
function fetchProducts() {
    fetch('?fetch_products_real=1')
        .then(res => res.json())
        .then(data => renderProducts(data))
        .catch(err => console.error(err));
}

// Initial load
fetchProducts();