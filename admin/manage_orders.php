<?php
require_once '../config.php';          // ✅ loads ORDER_SECRET_KEY, MAIL_USERNAME, MAIL_PASSWORD
require_once '../components/connect.php';
require_once '../components/auth_admin.php';

if (!$is_admin_logged_in) {
    header("Location: ../login.php");
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

/**
 * Build order email HTML
 */
function buildOrderEmailHtml($order, $items) {
    $items_html = '';
    $items_total = 0;

    foreach ($items as $item) {
        $img = !empty($item['image']) 
            ? "http://{$_SERVER['HTTP_HOST']}/uploaded_files/{$item['image']}" 
            : "http://{$_SERVER['HTTP_HOST']}/uploaded_files/placeholder.png";

        $subtotal = $item['price'] * $item['quantity'];
        $items_total += $subtotal;

        $price_html = (!empty($item['orig_price']) && $item['price'] < $item['orig_price'])
            ? "<p>Price: <del>₱".number_format($item['orig_price'],2)."</del> <b style='color:#2c7be5;'>₱".number_format($item['price'],2)."</b></p>"
            : "<p>Price: ₱".number_format($item['price'],2)."</p>";

        $items_html .= "
        <div class='product-box' style='display:flex;align-items:center;border:1px solid #eee;border-radius:8px;padding:10px;margin-bottom:10px'>
            <img src='{$img}' alt='Product Image' style='width:80px;height:80px;border-radius:8px;margin-right:15px;object-fit:cover'/>
            <div class='info'>
                <p><b>".htmlspecialchars($item['name'])."</b></p>
                <p>Quantity: {$item['quantity']}</p>
                {$price_html}
                <p><b>Subtotal:</b> ₱".number_format($subtotal,2)."</p>
            </div>
        </div>";
    }

    $courier_fee = $order['shipping_fee'] ?? 50;
    $grand_total = $items_total + $courier_fee;
    $order_date = date("M d, Y h:i A", strtotime($order['created_at']));
    $delivery_method = $order['delivery_method'] ?? 'Courier';

    $completed_html = '';
    $received_button_html = '';

    if ($order['status'] === 'delivered') {
        if (!empty($order['completed_at'])) {
            $completed_date = date("M d, Y h:i A", strtotime($order['completed_at']));
            $completed_html = "<p><b>Delivered on:</b> {$completed_date}</p>";
        }

        // --- ADD ORDER RECEIVED BUTTON using ORDER_SECRET_KEY from .env ---
        $token = md5($order['id'] . ($order['checkout_email'] ?? $order['email']) . ORDER_SECRET_KEY);
       // $received_url = "http://{$_SERVER['HTTP_HOST']}/order_received.php?order_id={$order['id']}&token={$token}";
        $received_url = "http://" . $_SERVER['HTTP_HOST'] . str_replace(' ', '%20', "/new folder/pet/admin/order_received.php?order_id={$order['id']}&token={$token}");


        $received_button_html = "
        <div style='text-align:center;margin-top:20px;'>
            <a href='{$received_url}' style='display:inline-block;padding:12px 25px;background:#2c7be5;color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;'>Order Received</a>
        </div>";
    }

    return "
    <!DOCTYPE html><html><head><meta charset='utf-8'></head>
    <body style='font-family:Arial,sans-serif;background:#f4f6f9;margin:0;padding:20px;color:#333'>
    <div style='max-width:600px;background:#fff;margin:auto;border-radius:10px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.1)'>
        <div style='background:#2c7be5;color:#fff;text-align:center;padding:20px'>
            <h1>🐾 Order Update</h1>
            <p>Order #{$order['id']}</p>
        </div>
        <div style='padding:20px;border-bottom:1px solid #eee'>
            <h2>Customer Information</h2>
            <p><b>Name:</b> ".htmlspecialchars($order['fullname'])."</p>
            <p><b>Email:</b> ".htmlspecialchars($order['checkout_email'] ?? $order['email'])."</p>
            <p><b>Phone:</b> ".htmlspecialchars($order['phone'])."</p>
            <p><b>Address:</b> ".htmlspecialchars($order['address'])."</p>
        </div>
        <div style='padding:20px;border-bottom:1px solid #eee'>
            <h2>Order Details</h2>
            {$items_html}
            <p><b>Delivery Method:</b> ".htmlspecialchars($delivery_method)."</p>
            <p><b>Courier Fee:</b> ₱".number_format($courier_fee,2)."</p>
            <p style='font-size:16px;font-weight:bold;color:#2c7be5'>Total Amount: ₱".number_format($grand_total,2)."</p>
            <p><b>Payment Method:</b> ".htmlspecialchars($order['payment_method'])."</p>
            <p><b>Status:</b> ".htmlspecialchars($order['status'])."</p>
            {$completed_html}
            {$received_button_html}
            <p>Order Date: {$order_date}</p>
        </div>
        <div style='padding:20px;text-align:center;color:#666'>Thank you for shopping with <b>Petcare</b> 🐾</div>
    </div>
    </body></html>
    ";
}

// --- Handle AJAX order status update ---
if(isset($_POST['action']) && $_POST['action']=='update_status') {
    header('Content-Type: application/json; charset=utf-8');

    $order_id = $_POST['order_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if (!$order_id || !$status) {
        echo json_encode(['success'=>false,'message'=>'Missing required parameters.']);
        exit;
    }

    try {
        // Update order status
        $stmt = $conn->prepare(
            in_array($status, ['delivered','cancelled'])
                ? "UPDATE orders SET status=?, completed_at=NOW() WHERE id=?"
                : "UPDATE orders SET status=? WHERE id=?"
        );
        $stmt->execute([$status, $order_id]);

        // Fetch updated order
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id=? LIMIT 1");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Fetch order items
            $stmt_items = $conn->prepare("
                SELECT oi.*, p.name, p.image, p.price AS orig_price, p.sale_price, p.on_sale
                FROM order_items oi
                LEFT JOIN product p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt_items->execute([$order_id]);
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            $email_html = buildOrderEmailHtml($order, $items);

            $recipient_email = !empty($order['checkout_email']) ? $order['checkout_email'] : $order['email'];

            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = MAIL_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = MAIL_USERNAME;
                $mail->Password   = MAIL_PASSWORD;
                $mail->SMTPSecure = 'tls';
                $mail->Port       = MAIL_PORT;

                $mail->setFrom(MAIL_USERNAME, 'Petcare');
                $mail->addAddress($recipient_email, $order['fullname']);

                $mail->isHTML(true);
                $mail->Subject = "Order #{$order['id']} Status Update";
                $mail->Body = $email_html;

                $mail->send();
            } catch (Exception $mailEx) {
                error_log("Mail error (manage_orders): ".$mailEx->getMessage());
            }
        }

        echo json_encode(['success'=>true,'status'=>$status]);
        exit;

    } catch (PDOException $dbEx) {
        error_log('DB error (manage_orders): '.$dbEx->getMessage());
        echo json_encode(['success'=>false,'message'=>'Database error. Check server logs.']);
        exit;
    } catch (Exception $ex) {
        error_log('General error (manage_orders): '.$ex->getMessage());
        echo json_encode(['success'=>false,'message'=>'Server error. Check server logs.']);
        exit;
    }
}

// --- Fetch active orders ---
$orders = $conn->query("
    SELECT * FROM orders
    WHERE status NOT IN ('delivered','cancelled')
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>






<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Manage Orders</title>
<link rel="stylesheet" href="./../assets/css/admin/manage_orders.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="./../assets/css/admin/admin_header.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include '../components/admin_header.php'; ?>

<div class="admin-main">
    <div class="header-row">
        <h1>📦 Manage Orders</h1>
        <div class="orders-filters">
            <input type="text" id="searchInput" class="search-input" placeholder="Search by name, email or order #">
            <select id="filterStatus" class="search-input">
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2>Recent Orders</h2></div>
        <div class="table-container">
            <table class="orders-table" id="ordersTable">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th class="actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($orders) > 0): ?>
                        <?php foreach($orders as $o):
                            $stmt = $conn->prepare("SELECT oi.*, p.name, p.image FROM order_items oi LEFT JOIN product p ON oi.product_id = p.id WHERE oi.order_id = ?");
                            $stmt->execute([$o['id']]);
                            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            $items_json = htmlspecialchars(json_encode($items), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr data-order="<?= $o['id'] ?>"
                            data-items='<?= $items_json ?>'
                            data-fullname="<?= htmlspecialchars($o['fullname'], ENT_QUOTES) ?>"
                            data-email="<?= htmlspecialchars($o['email'] ?? '', ENT_QUOTES) ?>"
                            data-phone="<?= htmlspecialchars($o['phone'] ?? '', ENT_QUOTES) ?>"
                            data-address="<?= htmlspecialchars($o['address'] ?? '', ENT_QUOTES) ?>"
                            data-total="<?= number_format($o['total'],2) ?>"
                            data-payment="<?= htmlspecialchars($o['payment_method'] ?? '', ENT_QUOTES) ?>"
                            data-status="<?= htmlspecialchars($o['status'], ENT_QUOTES) ?>"
                            data-created="<?= date("M d, Y h:i A", strtotime($o['created_at'])) ?>"
                        >
                            <td>#<?= $o['id'] ?><br><span class="small"><?= date("M d, Y", strtotime($o['created_at'])) ?></span></td>
                            <td class="customer">
                                <div><strong><?= htmlspecialchars($o['fullname']); ?></strong></div>
                                <div class="small"><?= htmlspecialchars($o['email'] ?? ''); ?></div>
                            </td>
                            <td class="items">
                                <?php
                                    $preview = [];
                                    foreach ($items as $it) $preview[] = htmlspecialchars($it['name']) . " x" . intval($it['quantity']);
                                    echo implode(", ", array_slice($preview,0,2));
                                    if(count($preview)>2) echo " +".(count($preview)-2)." more";
                                ?>
                            </td>
                            <td>₱<?= number_format($o['total'],2) ?></td>
                            <td><?= htmlspecialchars($o['payment_method'] ?? '') ?></td>
                            <td><span class="status-badge status <?= htmlspecialchars($o['status']) ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                            <td class="actions">
                                <button class="action-icon btn-view" title="View" data-action="view"><i class="fas fa-eye"></i></button>
                                <select class="status-select" data-id="<?= $o['id'] ?>"
                                    <?= in_array($o['status'], ['delivered','cancelled'])?'disabled':'' ?>
                                    style="padding:6px 8px; border-radius:6px; border:1px solid #ddd;">
                                    <option value="pending" <?= $o['status']=='pending'?'selected':'' ?>>Pending</option>
                                    <option value="confirmed" <?= $o['status']=='confirmed'?'selected':'' ?>>Confirmed</option>
                                    <option value="shipped" <?= $o['status']=='shipped'?'selected':'' ?>>Shipped</option>
                                    <option value="delivered" <?= $o['status']=='delivered'?'selected':'' ?>>Delivered</option>
                                    <option value="cancelled" <?= $o['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty-state">No orders found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="orderModal" class="modal" aria-hidden="true">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Order Details</h3>
      <span class="close" id="modalClose">&times;</span>
    </div>
    <div class="modal-body">
      <div class="order-items">
        <h4>Items</h4>
        <div id="modalItemsList"></div>
      </div>
      <div class="order-customer">
        <h4>Customer</h4>
        <p id="modalFullname"></p>
        <p id="modalEmail" class="small"></p>
        <p id="modalPhone" class="small"></p>
        <p id="modalAddress" class="small"></p>
        <hr>
        <p><strong>Total:</strong> ₱<span id="modalTotal"></span></p>
        <p><strong>Payment:</strong> <span id="modalPayment"></span></p>
        <p><strong>Status:</strong> <span id="modalStatus"></span></p>
        <p class="small" id="modalCreated"></p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-primary" id="modalPrint">Print</button>
      <button class="btn btn-danger" id="modalClose2">Close</button>
    </div>
  </div>
</div>

<script src="./../assets/js/sweetalert.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', ()=> {
    const modal=document.getElementById('orderModal');
    const modalClose=document.getElementById('modalClose');
    const modalClose2=document.getElementById('modalClose2');
    const modalItemsList=document.getElementById('modalItemsList');
    const modalFullname=document.getElementById('modalFullname');
    const modalEmail=document.getElementById('modalEmail');
    const modalPhone=document.getElementById('modalPhone');
    const modalAddress=document.getElementById('modalAddress');
    const modalTotal=document.getElementById('modalTotal');
    const modalPayment=document.getElementById('modalPayment');
    const modalStatus=document.getElementById('modalStatus');
    const modalCreated=document.getElementById('modalCreated');

    // Modal view
    document.querySelectorAll('button[data-action="view"]').forEach(btn=>{
        btn.addEventListener('click', e=>{
            const tr=e.currentTarget.closest('tr');
            if(!tr) return;
            const itemsJson=tr.dataset.items?JSON.parse(tr.dataset.items):[];
            modalItemsList.innerHTML='';
            if(itemsJson.length===0){
                modalItemsList.innerHTML='<p class="small">No items found.</p>';
            } else {
                itemsJson.forEach(item=>{
                    const div=document.createElement('div');
                    div.className='order-item';
                    div.innerHTML=`
                        <img src="../uploaded_files/${item.image||'placeholder.png'}" class="service-img" alt="">
                        <div>
                            <div class="meta"><strong>${item.name}</strong></div>
                            <div class="small">Qty: ${item.quantity} • ₱${Number(item.price).toFixed(2)}</div>
                        </div>`;
                    modalItemsList.appendChild(div);
                });
            }
            modalFullname.textContent=tr.dataset.fullname||'';
            modalEmail.textContent=tr.dataset.email||'';
            modalPhone.textContent=tr.dataset.phone||'';
            modalAddress.textContent=tr.dataset.address||'';
            modalTotal.textContent=tr.dataset.total||'';
            modalPayment.textContent=tr.dataset.payment||'';
            modalStatus.textContent=tr.dataset.status||'';
            modalCreated.textContent=tr.dataset.created||'';
            modal.classList.add('show');
            modal.setAttribute('aria-hidden','false');
        });
    });

    [modalClose, modalClose2].forEach(el=>el&&el.addEventListener('click', ()=>{
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden','true');
    }));
    window.addEventListener('click', e=>{ if(e.target===modal){ modal.classList.remove('show'); modal.setAttribute('aria-hidden','true'); } });

    // Search/filter
    const searchInput=document.getElementById('searchInput');
    const filterStatus=document.getElementById('filterStatus');
    let rows=Array.from(document.querySelectorAll('#ordersTable tbody tr'));

    function applyFilters(){
        const q=(searchInput.value||'').toLowerCase();
        const statusFilter=filterStatus.value;
        rows.forEach(r=>{
            const fullname=(r.dataset.fullname||'').toLowerCase();
            const email=(r.dataset.email||'').toLowerCase();
            const orderId=(r.dataset.order||'').toLowerCase();
            const status=r.dataset.status||'';
            let matches=true;
            if(q) matches=fullname.includes(q)||email.includes(q)||orderId.includes(q);
            if(statusFilter && statusFilter!=='' && status!==statusFilter) matches=false;
            r.style.display=matches?'':'none';
        });
    }
    searchInput.addEventListener('input', applyFilters);
    filterStatus.addEventListener('change', applyFilters);

    // Status AJAX (improved error handling)
    document.querySelectorAll('.status-select').forEach(select=>{
        select.addEventListener('change', function(){
            const orderId=this.dataset.id;
            const status=this.value;
            const row=this.closest('tr');
            const selectEl = this;
            selectEl.disabled=true;

            fetch(window.location.href, {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:`action=update_status&order_id=${orderId}&status=${status}`
            })
            .then(async res=>{
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid server response: ' + text);
                }
                if(data.success){
                    const badge=row.querySelector('.status-badge');
                    badge.textContent=status;
                    badge.className='status-badge status '+status;
                    row.dataset.status = status;

                    Swal.fire({
                        toast:true,
                        position:'top-end',
                        icon:'success',
                        title:`Status updated to ${status}`,
                        showConfirmButton:false,
                        timer:2500,
                        timerProgressBar:true
                    });

                    if(['delivered','cancelled'].includes(status)){
                        setTimeout(()=>{
                            row.style.transition='opacity 0.6s, transform 0.6s';
                            row.style.opacity='0';
                            row.style.transform='translateX(-50px)';
                            setTimeout(()=> row.remove(), 600);
                        }, 500);
                        // update rows list so filters/search won't include removed row
                        rows = Array.from(document.querySelectorAll('#ordersTable tbody tr'));
                    } else {
                        selectEl.disabled=false;
                    }
                } else {
                    throw new Error(data.message || 'Unknown error from server');
                }
            })
            .catch(err=>{
                console.error('Status update error:', err);
                Swal.fire('Error', err.message || 'Something went wrong', 'error');
                selectEl.disabled=false;
            });
        });
    });
});
</script>

<script src="./../assets/js/dashboard.js"></script>
</body>
</html>
