<?php
require_once '../components/connect.php';
require_once '../components/auth_admin.php';

// --- Restore Actions ---
if(isset($_GET['restore_order_id'])){
    $stmt = $conn->prepare("UPDATE orders SET status='pending', completed_at=NULL WHERE id=?");
    $stmt->execute([$_GET['restore_order_id']]);
    header("Location: archive.php?tab=orders&msg=restored");
    exit;
}

if(isset($_GET['restore_appt_id'])){
    $stmt = $conn->prepare("UPDATE appointments SET status='Pending', completed_at=NULL WHERE id=?");
    $stmt->execute([$_GET['restore_appt_id']]);
    header("Location: archive.php?tab=appointments&msg=restored");
    exit;
}

// --- Delete Actions ---
if(isset($_GET['delete_order_id'])){
    $stmt = $conn->prepare("DELETE FROM orders WHERE id=?");
    $stmt->execute([$_GET['delete_order_id']]);
    header("Location: archive.php?tab=orders&msg=deleted");
    exit;
}

if(isset($_GET['delete_appt_id'])){
    $stmt = $conn->prepare("DELETE FROM appointments WHERE id=?");
    $stmt->execute([$_GET['delete_appt_id']]);
    header("Location: archive.php?tab=appointments&msg=deleted");
    exit;
}

// --- Cancel Actions ---
if(isset($_GET['cancel_order_id'])){
    $stmt = $conn->prepare("UPDATE orders SET status='cancelled', completed_at=NOW() WHERE id=?");
    $stmt->execute([$_GET['cancel_order_id']]);
    header("Location: archive.php?tab=orders&msg=cancelled");
    exit;
}

if(isset($_GET['cancel_appt_id'])){
    $stmt = $conn->prepare("UPDATE appointments SET status='cancelled', completed_at=NOW() WHERE id=?");
    $stmt->execute([$_GET['cancel_appt_id']]);
    header("Location: archive.php?tab=appointments&msg=cancelled");
    exit;
}

// --- Fetch Completed Orders (delivered + cancelled) ---
$stmtOrders = $conn->prepare("
    SELECT * FROM orders 
    WHERE status IN ('delivered','cancelled') 
    ORDER BY completed_at DESC
");
$stmtOrders->execute();
$completedOrders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch Completed & Cancelled Appointments ---
$stmtAppointments = $conn->prepare("
    SELECT a.*, u.name AS client_name, s.name AS service_name
    FROM appointments a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN services s ON a.service_id = s.id
    WHERE a.status IN ('completed','cancelled')
    ORDER BY a.completed_at DESC
");
$stmtAppointments->execute();
$completedAppointments = $stmtAppointments->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Archive - Completed Orders & Appointments</title>
<link rel="stylesheet" href="./../assets/css/admin/archive.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="./../assets/css/admin/admin_header.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include '../components/admin_header.php'; ?>

<div class="admin-container">
    <div class="admin-main">
        <h1>Archive</h1>

        <!-- Tabs -->
        <div class="archive-tabs">
            <button class="tab-btn active" data-target="orders">Completed Orders</button>
            <button class="tab-btn" data-target="appointments">Completed Appointments</button>
        </div>

        <!-- Completed Orders -->
        <section id="orders" class="tab-content active">
            <?php if(count($completedOrders) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order_ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Completed_At</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($completedOrders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['id']) ?></td>
                            <td><?= htmlspecialchars($order['fullname']) ?></td>
                            <td><?= htmlspecialchars(date("M d, Y h:i A", strtotime($order['created_at']))) ?></td>
                            <td>₱<?= number_format($order['total'],2) ?></td>
                            <td>
                                <?= $order['completed_at'] 
                                    ? htmlspecialchars(date("M d, Y h:i A", strtotime($order['completed_at']))) 
                                    : '-' ?>
                            </td>
                            <td>
                                <?php if($order['status'] === 'delivered'): ?>
                                    <span class="status-badge delivered">Delivered</span>
                                <?php elseif($order['status'] === 'cancelled'): ?>
                                    <span class="status-badge cancelled">Cancelled</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="action-btn restore" href="?restore_order_id=<?= $order['id'] ?>" onclick="return confirm('Restore this order?');">Restore</a>
                                <a class="action-btn delete" href="?delete_order_id=<?= $order['id'] ?>" onclick="return confirm('Delete this order permanently?');">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No completed or cancelled orders.</p>
            <?php endif; ?>
        </section>

        <!-- Completed Appointments -->
        <section id="appointments" class="tab-content">
            <?php if(count($completedAppointments) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Appoint_ID</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Time_Slot</th>
                            <th>Service</th>
                            <th>Completed_At</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($completedAppointments as $appt): ?>
                        <tr>
                            <td><?= htmlspecialchars($appt['id']) ?></td>
                            <td><?= htmlspecialchars($appt['client_name']) ?></td>
                            <td><?= date("M d, Y h:i A", strtotime($appt['created_at'])) ?></td>
                            <td><span class="time-slot"><?= htmlspecialchars($appt['time_slot']) ?></span></td>
                            <td><?= htmlspecialchars($appt['service_name']) ?></td>
                            <td>
                                <?= $appt['completed_at'] 
                                    ? htmlspecialchars(date("M d, Y h:i A", strtotime($appt['completed_at']))) 
                                    : '-' ?>
                            </td>
                            <td>
                                <?php if(strtolower($appt['status']) === 'completed'): ?>
                                    <span class="status-badge delivered">Completed</span>
                                <?php elseif(strtolower($appt['status']) === 'cancelled'): ?>
                                    <span class="status-badge cancelled">Cancelled</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-cell">
                                <a class="action-btn restore" href="?restore_appt_id=<?= $appt['id'] ?>" onclick="return confirm('Restore this appointment?');">Restore</a>
                                <a class="action-btn delete" href="?delete_appt_id=<?= $appt['id'] ?>" onclick="return confirm('Delete this appointment permanently?');">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No completed or cancelled appointments.</p>
            <?php endif; ?>
        </section>
    </div>
</div>

<!-- Toast container -->
<div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

<script>
// Toast function
function showToast(message, type = 'success', duration = 3000) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.minWidth = '200px';
    toast.style.marginTop = '10px';
    toast.style.padding = '12px 20px';
    toast.style.color = '#fff';
    toast.style.borderRadius = '8px';
    toast.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.5s, transform 0.5s';
    toast.style.transform = 'translateX(100%)';
    
    // Color based on type
    if(type === 'success') toast.style.backgroundColor = '#4BB543';
    else if(type === 'error') toast.style.backgroundColor = '#FF4C4C';
    else toast.style.backgroundColor = '#333';

    container.appendChild(toast);

    // Animate in
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 100);

    // Remove after duration
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => container.removeChild(toast), 500);
    }, duration);
}

// Show toast based on URL param
const urlParams = new URLSearchParams(window.location.search);
const msg = urlParams.get('msg');
if(msg){
    showToast("Successfully " + msg + "!");
}

// Keep active tab
const tabs = document.querySelectorAll('.tab-btn');
const contents = document.querySelectorAll('.tab-content');
function activateTab(targetId){
    tabs.forEach(t => t.classList.remove('active'));
    contents.forEach(c => c.classList.remove('active'));
    document.querySelector(`.tab-btn[data-target="${targetId}"]`).classList.add('active');
    document.getElementById(targetId).classList.add('active');
}
const activeTab = urlParams.get('tab') || 'orders';
activateTab(activeTab);

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        activateTab(tab.dataset.target);
        history.replaceState(null, "", "archive.php?tab=" + tab.dataset.target);
    });
});
</script>


</body>
</html>
