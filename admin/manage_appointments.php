<?php 
require_once '../config.php';          // ✅ loads ORDER_SECRET_KEY, MAIL_USERNAME, MAIL_PASSWORD
require_once '../components/connect.php';
require_once '../components/auth_admin.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

if (!$is_admin_logged_in) {
    header("Location: login.php");
    exit;
}

// --- Function to send email asynchronously ---
function sendStatusEmail($transaction_no, $status, $user_id){
    global $conn;
    $stmtUser = $conn->prepare("SELECT name,email FROM users WHERE id=? LIMIT 1");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if($user && filter_var($user['email'], FILTER_VALIDATE_EMAIL)){
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = 'tls';
            $mail->Port       = MAIL_PORT;
            $mail->setFrom(MAIL_USERNAME, 'Petcare');
            $mail->addAddress($user['email'], $user['name']);
            $mail->isHTML(true);
            $mail->Subject = "Appointment #{$transaction_no} Status Update";

            $mail->Body = "
            <html>
            <body>
                <p>Hi <b>{$user['name']}</b>,</p>
                <p>Your appointment <b>#{$transaction_no}</b> status has been updated to <b>{$status}</b>.</p>
                <p>Thank you for using Petcare!</p>
            </body>
            </html>
            ";

            $mail->send();
        } catch (Exception $e) {
            // log error if needed
        }
    }
}

// --- Handle AJAX status update ---
if(isset($_POST['action']) && $_POST['action']=='update_status'){
    $id = $_POST['id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("SELECT status, transaction_no, user_id FROM appointments WHERE id=?");
    $stmt->execute([$id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$appointment){
        echo json_encode(['success'=>false,'error'=>'Appointment not found.']);
        exit;
    }

    $currentStatus = strtolower($appointment['status']);
    if(in_array($currentStatus, ['completed','cancelled'])){
        echo json_encode(['success'=>false,'error'=>'This appointment is locked and cannot be modified.']);
        exit;
    }
    if($currentStatus=='approved' && $status=='pending'){
        echo json_encode(['success'=>false,'error'=>'Cannot revert Approved back to Pending.']);
        exit;
    }

    // Update DB
    if(in_array(strtolower($status), ['completed','cancelled'])){
        $stmt = $conn->prepare("UPDATE appointments SET status=?, date_seen=NOW(), completed_at=NOW() WHERE id=?");
    } else {
        $stmt = $conn->prepare("UPDATE appointments SET status=?, date_seen=NOW() WHERE id=?");
    }

    $stmt->execute([$status, $id]);

    if(!empty($appointment['transaction_no'])){
        $stmt2 = $conn->prepare("UPDATE transactions SET status=? WHERE transaction_no=?");
        $stmt2->execute([$status, $appointment['transaction_no']]);
    }

    // Respond immediately
    echo json_encode(['success'=>true,'status'=>$status,'date_seen'=>date('Y-m-d H:i:s')]);
    flush();

    // Send email in background
    if($status !== $currentStatus){
        sendStatusEmail($appointment['transaction_no'], $status, $appointment['user_id']);
    }

    exit;
}

// --- Pagination & Filters ---
$limit = 10;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

$where = "1=1 AND a.status NOT IN ('completed','cancelled')"; // exclude completed & cancelled
$params = [];

if($search!==''){
    $where .= " AND (u.name LIKE ? OR s.name LIKE ? OR a.transaction_no LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if($status_filter!=='' && in_array($status_filter,['pending','approved','completed','cancelled'])){
    $where .= " AND a.status=?";
    $params[] = $status_filter;
}

// Count & fetch appointments
$totalRowsStmt = $conn->prepare("SELECT COUNT(*) FROM appointments a LEFT JOIN users u ON a.user_id=u.id LEFT JOIN services s ON a.service_id=s.id WHERE $where");
$totalRowsStmt->execute($params);
$totalRows = $totalRowsStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $conn->prepare("
    SELECT a.*, u.name AS fullname, u.email, u.phone, s.name AS service_name, a.transaction_no
    FROM appointments a
    LEFT JOIN users u ON a.user_id=u.id
    LEFT JOIN services s ON a.service_id=s.id
    WHERE $where
    ORDER BY a.id DESC
    LIMIT ?, ?
");
$i=1;
foreach($params as $param){ $stmt->bindValue($i++, $param); }
$stmt->bindValue($i++, $start, PDO::PARAM_INT);
$stmt->bindValue($i++, $limit, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Manage Appointments</title>
<link rel="stylesheet" href="./../assets/css/admin/manage_appointment.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="./../assets/css/admin/admin_header.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.status-select{padding:6px 8px; border-radius:6px; border:1px solid #ddd;}
.orders-filters{display:flex; gap:10px; margin-bottom:10px;}
.orders-filters input, .orders-filters select{padding:6px 10px; border-radius:6px; border:1px solid #ddd;}
.orders-filters select{cursor:pointer;}
</style>
</head>
<body>
<?php include '../components/admin_header.php'; ?>
<div class="admin-main">
    <h1>🐾 Manage Appointments</h1>
    <div class="orders-filters">
        <input type="text" id="searchInput" placeholder="Search by transaction, owner or service" value="<?= htmlspecialchars($search) ?>">
        <select id="statusFilter">
            <option value="">All Statuses</option>
            <option value="pending" <?= $status_filter=='pending'?'selected':'' ?>>Pending</option>
            <option value="approved" <?= $status_filter=='approved'?'selected':'' ?>>Approved</option>
            <option value="completed" <?= $status_filter=='completed'?'selected':'' ?>>Completed</option>
            <option value="cancelled" <?= $status_filter=='cancelled'?'selected':'' ?>>Cancelled</option>
        </select>
    </div>

    <div class="card">
        <div class="card-header"><h2>Recent Appointments</h2></div>
        <div class="table-container">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Status</th>
                        <th>Transaction No.</th>
                        <th>Service</th>
                        <th>Date Appointment</th>
                        <th>Time</th>
                        <th>Date Seen</th>
                        <th class="actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(count($appointments)>0): ?>
                    <?php $count=$start+1; foreach($appointments as $a): ?>
                    <?php $status = strtolower($a['status'] ?? 'pending'); ?>
                    <tr data-id="<?= $a['id'] ?>">
                        <td><?= $count++ ?></td>
                        <td><span class="status-badge <?= $status ?>"><?= ucfirst($status) ?></span></td>
                        <td><?= htmlspecialchars($a['transaction_no'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($a['service_name']) ?></td>
                        <td><?= htmlspecialchars($a['appointment_date']) ?></td>
                        <td><?= htmlspecialchars($a['time_slot']) ?></td>
                        <td><?= in_array($status,['approved','completed'])?$a['date_seen']:'-' ?></td>
                        <td class="actions">
                            <button class="action-icon btn-view" title="View"><i class="fas fa-eye"></i></button>
                            <select class="status-select" data-id="<?= $a['id'] ?>"
                                <?= in_array($status,['completed','cancelled']) ? 'disabled' : '' ?>>
                                <option value="pending" <?= $status=='pending'?'selected':'' ?> <?= in_array($status,['approved','completed','cancelled']) ? 'disabled':'' ?>>Pending</option>
                                <option value="approved" <?= $status=='approved'?'selected':'' ?>>Approved</option>
                                <option value="completed" <?= $status=='completed'?'selected':'' ?>>Completed</option>
                                <option value="cancelled" <?= $status=='cancelled'?'selected':'' ?>>Cancelled</option>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty-state">No appointments found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    function updateRowStatus(row, status, date_seen){
        // Update badge
        row.querySelector('.status-badge').textContent = status.charAt(0).toUpperCase() + status.slice(1);
        row.querySelector('.status-badge').className = 'status-badge ' + status;

        // Update Date Seen
        row.querySelector('td:nth-child(7)').textContent = ['approved','completed'].includes(status) ? date_seen : '-';

        // Disable dropdown only for Completed or Cancelled
        const select = row.querySelector('.status-select');
        if(['completed','cancelled'].includes(status)){
            select.disabled = true;
        } else {
            select.disabled = false;
        }
    }

    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function(){
            const id = this.dataset.id;
            const status = this.value;
            const row = this.closest('tr');

            // Show friendly processing text
            const originalOptions = Array.from(this.options).map(opt => ({value: opt.value, text: opt.text, selected: opt.selected, disabled: opt.disabled}));
            this.innerHTML = '';
            const processingOption = document.createElement('option');
            let friendlyText = 'Processing...';
            if(status === 'approved') friendlyText = 'Approving...';
            if(status === 'completed') friendlyText = 'Completing...';
            if(status === 'cancelled') friendlyText = 'Cancelling...';
            processingOption.textContent = friendlyText;
            processingOption.disabled = true;
            processingOption.selected = true;
            this.appendChild(processingOption);
            this.disabled = true;

            fetch('', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body: `action=update_status&id=${id}&status=${status}`
            })
            .then(res => res.json())
            .then(data => {
                // Restore options
                this.innerHTML = '';
                originalOptions.forEach(optData => {
                    const opt = document.createElement('option');
                    opt.value = optData.value;
                    opt.textContent = optData.text;
                    if(optData.selected) opt.selected = true;
                    if(optData.disabled) opt.disabled = optData.disabled;
                    this.appendChild(opt);
                });

                if(data.success){
                    if(status === 'completed' || status === 'cancelled'){
                    // Animate fade out
                    row.style.transition = 'opacity 0.6s, transform 0.6s';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-50px)';
                    setTimeout(() => { row.remove(); }, 600);
                } else {
                    updateRowStatus(row, status, data.date_seen);
                }

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `Status updated to ${status}`,
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true
                    });
                } else {
                    Swal.fire('Error', data.error, 'error');
                    this.value = originalOptions.find(o => o.selected)?.value || 'pending';
                    this.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                // Restore options on error
                this.innerHTML = '';
                originalOptions.forEach(optData => {
                    const opt = document.createElement('option');
                    opt.value = optData.value;
                    opt.textContent = optData.text;
                    if(optData.selected) opt.selected = true;
                    if(optData.disabled) opt.disabled = optData.disabled;
                    this.appendChild(opt);
                });
                this.disabled = false;
                Swal.fire('Error', 'Something went wrong', 'error');
            });
        });
    });
});
</script>


</body>
</html>
