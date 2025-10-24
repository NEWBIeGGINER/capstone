// ---------- UPDATE TOTAL & SYNC FORM FIELDS ----------
function updateTotal() {
  let subtotal = 0;
  const modalProducts = document.getElementById("modalProducts");
  if (!modalProducts) return;

  modalProducts.querySelectorAll(".product-item").forEach(item => {
    const price = parseFloat(item.dataset.price) || 0;
    const qtyInput = item.querySelector(".qty");
    const qty = parseInt(qtyInput?.value) || 1;
    const sub = price * qty;
    subtotal += sub;

    // Update subtotal display
    const subEl = item.querySelector(".subtotal");
    if (subEl) subEl.textContent = sub.toFixed(2);

    // 🔹 Update hidden quantities[] in multi-checkout
    const hiddenQty = item.querySelector('input[name="quantities[]"]');
    if (hiddenQty) hiddenQty.value = qty;

    // 🔹 Update hidden single checkout fields
    if (modalProducts.classList.contains("single-checkout")) {
      const hiddenPrice = document.getElementById("hiddenPrice");
      const hiddenQtySingle = document.getElementById("hiddenQty");
      if (hiddenPrice) hiddenPrice.value = price;
      if (hiddenQtySingle) hiddenQtySingle.value = qty;
    }
  });

  // Delivery fee
  let deliveryFee = 0;
  const deliverySelect = document.getElementById("delivery_method");
  if (deliverySelect) {
    const selected = deliverySelect.options[deliverySelect.selectedIndex];
    deliveryFee = parseFloat(selected?.dataset.fee) || 0;
  }

  // Grand total
  const grandTotal = subtotal + deliveryFee;
  const totalEl = document.getElementById("totalAmount");
  if (totalEl) totalEl.textContent = grandTotal.toFixed(2);
}

// ---------- QUANTITY BUTTON HANDLERS ----------
function bindQtyButtons() {
  document.querySelectorAll(".qty-box").forEach(box => {
    const input = box.querySelector(".qty");
    const minus = box.querySelector(".minus");
    const plus = box.querySelector(".plus");
    const maxStock = parseInt(input.max) || parseInt(box.dataset.stock) || Infinity;

    // Helper to normalize & update total
    const syncAndUpdate = () => {
      let val = parseInt(input.value) || 1;
      if (val > maxStock) {
        val = maxStock;
        Swal.fire({
          icon: "warning",
          title: "Max Stock Reached",
          text: `Only ${maxStock} unit(s) available.`
        });
      } else if (val < 1) val = 1;
      input.value = val;
      updateTotal(); // 🔹 ensures hidden qty & total update
    };

    minus?.addEventListener("click", () => {
      let val = parseInt(input.value) || 1;
      if (val > 1) input.value = val - 1;
      syncAndUpdate();
    });

    plus?.addEventListener("click", () => {
      let val = parseInt(input.value) || 1;
      if (val < maxStock) {
        input.value = val + 1;
      } else {
        input.value = maxStock;
        Swal.fire({
          icon: "warning",
          title: "Max Stock Reached",
          text: `Only ${maxStock} unit(s) available.`
        });
      }
      syncAndUpdate();
    });

    input?.addEventListener("input", syncAndUpdate);
  });
}

// ---------- LOAD DELIVERY OPTIONS ----------
async function loadDeliveryOptions() {
  const deliverySelect = document.getElementById("delivery_method");
  if (!deliverySelect) return;

  deliverySelect.innerHTML = `
    <option value="">Choose delivery method</option>
    <option value="pickup" data-fee="0">Pickup (Local - Free)</option>
  `;

  try {
    const res = await fetch("components/promotions.php");
    const data = await res.json();

    const courierOption = document.createElement("option");
    if (data && data.label) {
      courierOption.value = "courier";
      courierOption.textContent = data.label;
      courierOption.dataset.fee = data.value;
    } else {
      courierOption.value = "courier";
      courierOption.textContent = "Courier (J&T/LBC – ₱50)";
      courierOption.dataset.fee = 50;
    }
    deliverySelect.appendChild(courierOption);

    deliverySelect.value = "courier";
    deliverySelect.dispatchEvent(new Event("change"));
  } catch (err) {
    console.error("❌ Delivery options load failed:", err);
    const option = document.createElement("option");
    option.value = "courier";
    option.textContent = "Courier (J&T/LBC – ₱50)";
    option.dataset.fee = 50;
    deliverySelect.appendChild(option);

    deliverySelect.value = "courier";
    deliverySelect.dispatchEvent(new Event("change"));
  }

  updateTotal();
}

// ---------- DOM CONTENT LOADED ----------
document.addEventListener("DOMContentLoaded", () => {
  const deliverySelect = document.getElementById("delivery_method");
  if (deliverySelect) {
    deliverySelect.addEventListener("change", function () {
      const paymentSelect = document.getElementById("payment_method");
      if (!paymentSelect) return;

      if (this.value === "pickup") {
        paymentSelect.innerHTML = `
          <option value="">Choose payment method</option>
          <option value="gcash" selected>GCash</option>
          <option value="pay_shop">Pay at Shop</option>
        `;
      } else if (this.value === "courier") {
        paymentSelect.innerHTML = `
          <option value="">Choose payment method</option>
          <option value="cod">Cash on Delivery</option>
          <option value="gcash" selected>GCash</option>
        `;
      } else {
        paymentSelect.innerHTML = `<option value="">Choose payment method</option>`;
      }

      updateTotal();
    });
  }

  const closeBtn = document.querySelector("#checkoutModal .close");
  const cancelBtn = document.querySelector("#checkoutModal .cancel");
  const checkoutModal = document.getElementById("checkoutModal");

  closeBtn?.addEventListener("click", () => (checkoutModal.style.display = "none"));
  cancelBtn?.addEventListener("click", () => (checkoutModal.style.display = "none"));

  loadDeliveryOptions();
  bindQtyButtons();
});

// ---------- OPEN SINGLE CHECKOUT ----------
function openSingleCheckout(product) {
  const box = document.querySelector(`.box .order-btn[data-id='${product.id}']`)?.closest(".box");
  product.stock = box ? parseInt(box.querySelector(".stock")?.textContent.replace("Available: ", "")) || 0 : 0;

  const modalProducts = document.getElementById("modalProducts");
  modalProducts.innerHTML = `
    <div class="product-item" data-price="${product.price}">
      <img src="${product.img}">
      <div class="details">
        <h3>${product.name}</h3>
        <p>Price: ₱${product.price.toFixed(2)}</p>
        <label>Quantity</label>
        <div class="qty-box" data-stock="${product.stock}">
          <button type="button" class="qty-btn minus">-</button>
          <input type="number" id="quantity" class="qty" value="1" min="1" max="${product.stock}">
          <button type="button" class="qty-btn plus">+</button>
        </div>
        <div class="subtotal-box">
          Subtotal: ₱<span class="subtotal">${product.price.toFixed(2)}</span>
        </div>
      </div>
    </div>
  `;

  document.getElementById("hiddenProductId").value = product.id;
  document.getElementById("hiddenPrice").value = product.price;
  document.getElementById("hiddenQty").value = 1;

  modalProducts.className = "single-checkout";
  document.getElementById("checkoutModal").style.display = "flex";

  loadDeliveryOptions();
  bindQtyButtons();
  updateTotal();
}

// ---------- OPEN MULTI CHECKOUT ----------
function openMultipleCheckout(cartItems) {
  const modalProducts = document.getElementById("modalProducts");
  modalProducts.innerHTML = "";
  modalProducts.className = "multi-checkout";

  cartItems.forEach(item => {
    const box = document.querySelector(`.box .order-btn[data-id='${item.id}']`)?.closest(".box");
    const stockElem = box ? box.querySelector(".stock") : null;
    const currentStock = stockElem ? parseInt(stockElem.textContent.replace("Available: ", "")) : 0;

    modalProducts.innerHTML += `
      <div class="product-item" data-price="${item.price}">
        <img src="${item.img}">
        <div class="details">
          <h3>${item.name}</h3>
          <p>Price: ₱${item.price.toFixed(2)}</p>
          <label>Qty</label>
          <div class="qty-box" data-stock="${currentStock}">
            <button type="button" class="qty-btn minus">-</button>
            <input type="number" class="qty" value="${item.qty}" min="1" max="${currentStock}">
            <button type="button" class="qty-btn plus">+</button>
          </div>
          <!-- Hidden form inputs -->
          <input type="hidden" name="product_ids[]" value="${item.id}">
          <input type="hidden" name="quantities[]" value="${item.qty}">
          <div class="subtotal-box">
            Subtotal: ₱<span class="subtotal">${(item.price * item.qty).toFixed(2)}</span>
          </div>
        </div>
      </div>
    `;
  });

  document.getElementById("checkoutModal").style.display = "flex";
  loadDeliveryOptions();
  bindQtyButtons();
  updateTotal();
}

// ---------- VALIDATE MULTI CHECKOUT ----------
function validateMultiCheckout() {
  const overStockItems = [];
  const productIds = [];
  const quantities = [];

  document.querySelectorAll(".multi-checkout .product-item").forEach(item => {
    const qtyInput = item.querySelector(".qty");
    const qty = parseInt(qtyInput.value) || 1;

    const prodId = item.querySelector('input[name="product_ids[]"]').value;
    const mainBoxStockElem = document.querySelector(`.box .order-btn[data-id='${prodId}']`)?.closest(".box").querySelector(".stock");
    const stock = mainBoxStockElem ? parseInt(mainBoxStockElem.textContent.replace("Available: ", "")) : 0;

    if (qty > stock) {
      overStockItems.push({ name: item.querySelector("h3").textContent, stock });
    }

    productIds.push(prodId);
    quantities.push(qty);
  });

  return { overStockItems, productIds, quantities };
}
