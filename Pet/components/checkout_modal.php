<!-- components/checkout_modal.php -->
<div id="checkoutModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>

    <!-- LEFT product(s) -->
    <div id="modalProductsWrapper">
      <div id="modalProducts"></div>

      <!-- ✅ Single checkout product preview -->
      <div id="singleCheckoutPreview" style="display:none; margin-bottom:15px; text-align:center;">
        <img id="checkoutProductImg" src="" alt="Product" style="max-width:120px; display:block; margin:0 auto 10px;">
        <h3 id="checkoutProductName" style="margin:5px 0;"></h3>
        <p id="checkoutProductPrice" style="font-weight:600;"></p>
      </div>

      <!-- ✅ TOTAL -->
      <div class="total-box" style="margin-top:12px;text-align:right;font-weight:700;font-size:16px;">
        Total: ₱<span id="totalAmount">0.00</span>
      </div>
    </div>

    <!-- RIGHT form -->
    <div>
      <form id="checkoutForm">

        <label>Full Name</label>
        <input type="text" name="fullname" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Phone</label>
        <input type="tel" name="phone" required>

        <label>Complete Address</label>
        <input type="text" name="address" required>

        <!-- Delivery Method -->
        <label>Delivery Method</label>
        <select name="delivery_method" id="delivery_method" required>
          <option value="">Choose delivery method</option>
          <option value="pickup" data-fee="0">Pickup (Local - Free)</option>
        </select>

        <!-- Payment Method -->
        <label>Payment Method</label>
        <select name="payment_method" id="payment_method" required>
          <option value="">Choose payment method</option>
        </select>

        <!-- ✅ Hidden fields for single checkout -->
        <input type="hidden" name="checkout_type" value="single">
        <input type="hidden" name="product_id" id="hiddenProductId">
        <input type="hidden" name="price" id="hiddenPrice">
        <input type="hidden" name="quantity" id="hiddenQty">

        <!-- ⚡ Multi-checkout hidden fields are dynamically injected via JS -->

        <div class="actions">
          <button type="button" class="cancel">Cancel</button>
          <button type="submit" class="place">
            <i class="fas fa-check"></i> Place Order
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- Info Modal with Sample Rating -->
<div id="productInfoModal" class="modal">
  <div class="modal-content">
    <span class="close-info">&times;</span>

    <div class="info-left">
      <img id="infoImage" alt="Product Image" style="width:100%; max-width:300px; border-radius:10px;">
    </div>

    <div class="info-right">
      <h3 id="infoName">Product Name</h3>
      <p id="infoPrice" style="font-weight:600;">₱0.00</p>
      <p id="infoStock" style="font-weight:500; color:#28a745;">Stock: 0</p>

      <p id="infoDescription" style="margin:12px 0; color:#444; line-height:1.4;">
        Product description goes here...
      </p>

      <!-- Sample Rating -->
      <div class="info-rating" style="margin:10px 0; font-size:14px; color:#f39c12;">
        ⭐ 4.5 <span style="color:#666;">(20 reviews)</span>
      </div>

      <button id="infoOrderBtn" style="margin-top:15px; background:#28a745; color:#fff; border:none; padding:10px 18px; border-radius:8px; cursor:pointer; font-weight:600;">
        <i class="fas fa-shopping-cart"></i> Order Now
      </button>
    </div>
  </div>
</div>



<style>

/* Modal */
#productInfoModal .modal-content {
  display: flex;
  gap: 35px;
  max-width: 800px;
  width: 95%;
  padding: 30px;
  border-radius: 16px;
  background:#fff;
  animation: fadeInUp .3s ease;
}
#productInfoModal .info-left {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
}
#productInfoModal .info-left img {
  width: 350px;
  height: 350px;
  object-fit: cover;
  border-radius: 12px;
}
#productInfoModal .info-right {
  flex: 1;
  display: flex;
  flex-direction: column;
}
#productInfoModal .info-right h2 {
  font-size: 28px;
  margin: 0 0 10px;
  font-weight: 700;
}
#productInfoModal .info-right p {
  font-size: 18px;
  margin: 5px 0;
  font-weight: 500;
  color: #333;
}
#productInfoModal .info-right h4 {
  margin: 15px 0 5px;
  font-size: 16px;
  font-weight: 600;
  color: #444;
}
#productInfoModal .info-rating {
  font-size: 18px;
  color: #f39c12;
  margin-bottom: 20px;
}
#productInfoModal .info-rating span {
  color: #666;
  font-size: 16px;
  margin-left: 5px;
}
#productInfoModal #infoOrderBtn {
  background: #28a745;
  color: #fff;
  border: none;
  padding: 14px 22px;
  font-size: 18px;
  font-weight: 600;
  border-radius: 10px;
  cursor: pointer;
  transition: background .3s, transform .1s;
}
#productInfoModal #infoOrderBtn:hover {
  background: #218838;
}
#productInfoModal #infoOrderBtn:active {
  transform: scale(0.97);
}
#productInfoModal .close-info {
  position: absolute;
  top: 15px;
  right: 18px;
  font-size: 26px;
  cursor: pointer;
  color: #888;
  transition: color .2s;
}
#productInfoModal .close-info:hover { color:#000; }


/* Animation */
@keyframes fadeInUp {
  from { transform: translateY(30px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

/* Responsive */
@media (max-width: 480px) {
  #productInfoModal .modal-content {
    padding: 15px;
  }

  #productInfoModal #infoName {
    font-size: 18px;
  }

  #productInfoModal #infoPrice {
    font-size: 15px;
  }
}


 /* --- MODAL --- */
.modal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  justify-content: center;
  align-items: center;
  z-index: 1000;
}
.modal-content {
  background: #fff;
  padding: 25px;
  border-radius: 16px;
  max-width: 850px;
  width: 90%;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 25px;
  position: relative;
  animation: fadeInUp .3s ease;
  box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}
@keyframes fadeInUp {
  from {transform: translateY(30px);opacity:0;}
  to {transform: translateY(0);opacity:1;}
}
.close {
  position: absolute;
  top: 12px;
  right: 15px;
  font-size: 22px;
  cursor: pointer;
  color: #888;
  transition: color .2s;
}
.close:hover {color:#000;}
.modal h3 {margin: 8px 0;font-size: 20px;}
.modal p {color:#444;font-weight:500;margin-bottom:10px;}
.modal label {display:block;margin:8px 0 4px;font-weight:600;color:#333;}
.modal input, .modal select {
  width: 100%;
  padding: 10px;
  margin-bottom: 8px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  transition: all .2s;
}
.modal input:focus, .modal select:focus {
  border-color: var(--carrot-color);
  outline: none;
  box-shadow: 0 0 0 2px rgba(230,126,34,.15);
}

/* --- ACTIONS (sticky footer) --- */
.actions {
  grid-column: span 2;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 15px;
  position: sticky;
  bottom: 0;
  background: #fff;
  padding-top: 10px;
}
.actions button {
  padding: 10px 18px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.3s, transform 0.1s;
}
.actions button:active {transform: scale(0.96);}
.cancel {
  background: #bbb;
  color: #fff;
}
.cancel:hover {background:#999;}
.place {
  background: #28a745;
  color: #fff;
}
.place:hover {background:#218838;}
.place:disabled {opacity:0.7;cursor:not-allowed;}

/* --- MULTIPLE CHECKOUT (compact) --- */

#modalProducts.multi-checkout {
  max-height: 500px;
  overflow-y: auto;
  padding-right: 5px;
}


#modalProducts.multi-checkout .product-item {
  display: flex;              /* ✅ flex row layout */
  align-items: center;
  gap: 12px;
  border: 1px solid #eee;
  border-radius: 10px;
  padding: 8px 12px;
  margin-bottom: 10px;
  background: #fafafa;
  transition: box-shadow .2s;
}
#modalProducts.multi-checkout .product-item:hover {
  box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}
#modalProducts.multi-checkout .product-item img {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 6px;
  flex-shrink: 0; /* ✅ para hindi lumiit */
}
#modalProducts.multi-checkout .details {
  flex: 1;      /* ✅ lumapad yung details */
  font-size: 13px;
}
#modalProducts.multi-checkout .details h3 {
  margin: 0 0 3px;
  font-size: 13px;
  font-weight: 600;
  color: #333;
}
#modalProducts.multi-checkout .details p {
  margin: 0;
  font-size: 12px;
  color: #666;
}
#modalProducts.multi-checkout .subtotal-box {
  font-size: 13px;
  font-weight: 600;
  color: #444;
  text-align: right;
}



/* --- SINGLE CHECKOUT --- */
.single-checkout .product-item {
  flex-direction: column;
  align-items: center;
  text-align: center;
}
.single-checkout .product-item img {
  width: 320px;
  height: 320px;
  margin-bottom: 12px;
  border-radius: 10px;
}
.single-checkout .details {
  width: 100%;
}
.single-checkout .details h3 {
  font-size: 20px;
  margin-bottom: 8px;
}
.single-checkout .details p {
  font-size: 15px;
}

/* --- QTY BOX --- */
.qty-box {
  display: flex;
  align-items: center;
  justify-content: center; /* ✅ center horizontally */
  gap: 6px;
  margin-top: 6px;
}

.qty-box .qty-btn {
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 50%;
  background: #f0f0f0;
  font-weight: bold;
  cursor: pointer;
  transition: background .2s, transform .1s;
}
.qty-box .qty-btn:hover {
  background: #ddd;
}
.qty-box .qty-btn:active {
  transform: scale(0.9);
}

.qty-box input.qty {
  width: 50px;
  text-align: center;
  font-size: 14px;
  padding: 5px;
  border: 1px solid #ccc;
  border-radius: 6px;
}

/* Subtotal naka-align sa kanan */
.subtotal-box {
  margin-top: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #444;
}

/* --- TOTAL HIGHLIGHT --- */
.total-highlight {
  margin-top: 12px;
  text-align: right;
  font-weight: 700;
  font-size: 16px;
  background: #f8f9fa;   /* light gray background */
  padding: 10px 15px;
  border-radius: 8px;
  color: #28a745;        /* green text for amount */
  border: 1px solid #ddd;
}
.total-highlight span {
  color: #000; /* label "Total:" in black */
}

/* Red heart color */
.red-heart {
  color: red;
}
</style>
