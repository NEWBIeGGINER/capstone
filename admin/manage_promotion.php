<?php 
require_once '../components/connect.php';
require_once '../components/auth_admin.php';

if (!$is_admin_logged_in) {
    header("Location: login.php");
    exit;
}

/* ===========================================================
   ADD PROMOTION
   =========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_promo'])) {
    $delivery_fee = isset($_POST['delivery_fee']) ? (float)$_POST['delivery_fee'] : null;
    $promo_note   = trim($_POST['promo_note'] ?? '');
    $start_date   = $_POST['start_date'] ?? null;
    $end_date     = $_POST['end_date'] ?? null;

    if ($delivery_fee !== null && $start_date && $end_date) {
        if (strtotime($end_date) < strtotime($start_date)) {
            $_SESSION['error_msg'][] = "End date cannot be earlier than start date.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO promotion (delivery_fee, promo_note, start_date, end_date, status, updated_at)
                VALUES (?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$delivery_fee, $promo_note, $start_date, $end_date]);
            $_SESSION['success_msg'][] = "Promotion added successfully!";
        }
    } else {
        $_SESSION['error_msg'][] = "Please fill in all required fields.";
    }

    header("Location: manage_promotion.php");
    exit;
}

/* ===========================================================
   AJAX: TOGGLE STATUS
   =========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    header('Content-Type: application/json');
    $id = (int)$_POST['id'];

    $stmt = $conn->prepare("UPDATE promotion SET status = IF(status='active','inactive','active') WHERE id = ?");
    $stmt->execute([$id]);

    $stmt = $conn->prepare("SELECT status FROM promotion WHERE id = ?");
    $stmt->execute([$id]);
    $newStatus = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'new_status' => $newStatus]);
    exit;
}

/* ===========================================================
   AJAX: DELETE PROMOTION
   =========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_promo') {
    header('Content-Type: application/json');
    $id = (int)$_POST['id'];

    $stmt = $conn->prepare("DELETE FROM promotion WHERE id = ?");
    $success = $stmt->execute([$id]);

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Promotion deleted successfully!' : 'Failed to delete promotion.'
    ]);
    exit;
}

/* ===========================================================
   FETCH PROMOTIONS
   =========================================================== */
$promos = $conn->query("SELECT * FROM promotion ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

/* ===========================================================
   SESSION MESSAGES
   =========================================================== */
$success_msg = $_SESSION['success_msg'] ?? [];
$error_msg   = $_SESSION['error_msg'] ?? [];
$warning_msg = $_SESSION['warning_msg'] ?? [];
$info_msg    = $_SESSION['info_msg'] ?? [];
unset($_SESSION['success_msg'], $_SESSION['error_msg'], $_SESSION['warning_msg'], $_SESSION['info_msg']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Promotion - PetCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin/admin_header.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/admin/manage_promotion.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
<?php include '../components/admin_header.php'; ?>

<div class="admin-main">
    <div class="header-row">
        <h1>Promotions</h1>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Active & Inactive Promotions</h2>
            <button class="btn btn-primary" id="openModalBtn">
                <i class="fas fa-plus-circle"></i> Add Promotion
            </button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Delivery Fee</th>
                        <th>Note</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th class="actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="promoTableBody">
                    <?php if ($promos): ?>
                        <?php foreach ($promos as $p): ?>
                            <tr id="promo-<?= $p['id'] ?>">
                                <td><?= htmlspecialchars($p['id']) ?></td>
                                <td>₱<?= number_format($p['delivery_fee'], 2) ?></td>
                                <td><?= htmlspecialchars($p['promo_note'] ?: '—') ?></td>
                                <td><?= htmlspecialchars($p['start_date']) ?></td>
                                <td><?= htmlspecialchars($p['end_date']) ?></td>
                                <td>
                                    <span class="status-text <?= $p['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                                        <?= ucfirst($p['status']) ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <button class="btn btn-primary toggle-status-btn" data-id="<?= $p['id'] ?>" title="Toggle Status">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <button class="btn btn-danger delete-promo-btn" data-id="<?= $p['id'] ?>" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;">No promotions found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL -->
<div class="modal" id="promoModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Promotion</h3>
            <span class="close" id="closeModalBtn">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="add_promo" value="1">

            <div class="input-field">
                <p>Delivery Fee (₱)</p>
                <input type="number" name="delivery_fee" step="0.01" required placeholder="Enter delivery fee">
            </div>

            <div class="input-field">
                <p>Promo Note</p>
                <textarea name="promo_note" placeholder="Enter promo note"></textarea>
            </div>

            <div class="input-field">
                <p>Start Date</p>
                <input type="date" name="start_date" required>
            </div>

            <div class="input-field">
                <p>End Date</p>
                <input type="date" name="end_date" required>
            </div>

            <button type="submit" class="btn btn-primary">Save Promotion</button>
        </form>
    </div>
</div>

<script>
/* ===========================================================
   MODAL CONTROL
   =========================================================== */
const modal = document.getElementById("promoModal");
document.getElementById("openModalBtn").onclick = () => modal.classList.add("show");
document.getElementById("closeModalBtn").onclick = () => modal.classList.remove("show");
window.onclick = e => { if (e.target === modal) modal.classList.remove("show"); };

/* ===========================================================
   DELETE & TOGGLE STATUS
   =========================================================== */
document.addEventListener("click", e => {
    const delBtn = e.target.closest(".delete-promo-btn");
    const toggleBtn = e.target.closest(".toggle-status-btn");

    if (delBtn) {
        const id = delBtn.dataset.id;
        Swal.fire({
            title: 'Delete Promotion?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#666',
            confirmButtonText: 'Yes, delete it'
        }).then(result => {
            if (result.isConfirmed) {
                fetch("", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({ action: "delete_promo", id })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`promo-${id}`)?.remove();
                        Swal.fire({ 
                            toast: true, 
                            position: 'top-end', 
                            icon: 'success', 
                            title: data.message, 
                            showConfirmButton: false, 
                            timer: 2000,
                            timerProgressBar: true
                        });
                        if (!document.querySelector("#promoTableBody tr"))
                            document.querySelector("#promoTableBody").innerHTML = `<tr><td colspan="7" style="text-align:center;">No promotions found.</td></tr>`;
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Network error occurred.', 'error'));
            }
        });
    }

    if (toggleBtn) {
        const id = toggleBtn.dataset.id;
        fetch("", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ action: "toggle_status", id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const statusEl = document.querySelector(`#promo-${id} .status-text`);
                statusEl.textContent = data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1);
                statusEl.className = `status-text status-${data.new_status}`;
                Swal.fire({ 
                    toast: true, 
                    position: 'top-end', 
                    icon: 'info', 
                    title: `Promotion is now ${data.new_status}`, 
                    showConfirmButton: false, 
                    timer: 2000,
                    timerProgressBar: true
                });
            }
        })
        .catch(() => Swal.fire('Error', 'Network error occurred.', 'error'));
    }
});

document.addEventListener("DOMContentLoaded", () => {
    const deliveryFeeInput = document.getElementById("delivery_fee");
    fetch("../components/promotions.php")
        .then(res => res.json())
        .then(data => {
            if (data && data.delivery_fee !== null) {
                deliveryFeeInput.value = data.delivery_fee;
            }
        })
        .catch(err => console.error("Error loading promotions:", err));
});
</script>

<?php if (!empty($success_msg) || !empty($error_msg) || !empty($warning_msg) || !empty($info_msg)): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
    <?php foreach ($success_msg as $msg): ?>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: <?= json_encode($msg) ?>,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    <?php endforeach; ?>

    <?php foreach ($error_msg as $msg): ?>
        Swal.fire({
            title: "Error",
            text: <?= json_encode($msg) ?>,
            icon: "error",
            confirmButtonColor: "#d33",
            customClass: { popup: "swal2-mini" }
        });
    <?php endforeach; ?>

    <?php foreach ($warning_msg as $msg): ?>
        Swal.fire({
            title: "Warning",
            text: <?= json_encode($msg) ?>,
            icon: "warning",
            confirmButtonColor: "#e67e22",
            customClass: { popup: "swal2-mini" }
        });
    <?php endforeach; ?>

    <?php foreach ($info_msg as $msg): ?>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: <?= json_encode($msg) ?>,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    <?php endforeach; ?>
});
</script>
<?php endif; ?>

<?php include './../components/alert.php'; ?>

</body>
</html>
