<?php
require_once 'components/connect.php';
require_once 'components/auth.php';

if(!isset($_GET['transaction_no'])) die("Invalid request");
$transaction_no = $_GET['transaction_no'];

// Cancel request
if(isset($_POST['cancel'])){
    // Fetch appointment(s) for this transaction (all statuses)
    $stmtA = $conn->prepare("SELECT appointment_date, time_slot, status FROM appointments WHERE transaction_no=? AND user_id=?");
    $stmtA->execute([$transaction_no,$user_id]);
    $appointments = $stmtA->fetchAll(PDO::FETCH_ASSOC);

    // Cancel transactions & appointments (Pending lang)
    $stmt = $conn->prepare("UPDATE transactions SET status='Cancelled' WHERE transaction_no=? AND user_id=? AND status='Pending'");
    $stmt->execute([$transaction_no,$user_id]);

    $stmt2 = $conn->prepare("UPDATE appointments SET status='Cancelled' WHERE transaction_no=? AND user_id=? AND status='Pending'");
    $stmt2->execute([$transaction_no,$user_id]);

    // Restore slots only for Pending/Approved appointments
    foreach($appointments as $appt){
        if(!in_array($appt['status'], ['Pending','Approved'])) continue;

        $times = explode(' - ', $appt['time_slot']);
        $start_time = date('H:i', strtotime($times[0]));
        $end_time = date('H:i', strtotime($times[1]));

        // Update schedule slots: increase by 1 but not exceeding total_slots
        $stmtS = $conn->prepare("
            UPDATE schedules 
            SET slots = slots + 1 
            WHERE slots_date=? AND start_time=? AND end_time=? AND slots < total_slots
        ");
        $stmtS->execute([$appt['appointment_date'], $start_time, $end_time]);
    }

    $msg = ($stmt->rowCount() || $stmt2->rowCount()) ? 'canceled':'not_allowed';
    header("Location: transactions.php?transaction_no=$transaction_no&msg=$msg"); 
    exit;
}



// Fetch transaction + appointment
$stmt = $conn->prepare("
SELECT t.transaction_no, t.service, t.appointment_date, t.time_slot, a.date_seen, a.id AS appointment_id,
       t.status AS transaction_status, a.status AS appointment_status,
       CASE 
           WHEN t.status='Cancelled' OR a.status='Cancelled' THEN 'Cancelled'
           WHEN t.status='Completed' OR a.status='Completed' THEN 'Completed'
           WHEN t.status='Approved'  OR a.status='Approved'  THEN 'Approved'
           WHEN t.status='Rejected'  OR a.status='Rejected'  THEN 'Rejected'
           ELSE 'Pending'
       END AS apt_status
FROM transactions t
LEFT JOIN appointments a ON t.transaction_no = a.transaction_no
WHERE t.user_id=? AND t.transaction_no=?
LIMIT 1
");
$stmt->execute([$user_id,$transaction_no]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$transaction) die("Transaction not found");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transaction Details</title>
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.details-container{max-width:700px;margin:100px auto;padding:20px;background:#fff;border-radius:12px;box-shadow:0 4px 10px rgba(0,0,0,0.08);}
.details-container h2{margin-bottom:20px;}
.details-row{margin-bottom:12px;}
.details-label{font-weight:bold;display:inline-block;width:180px;}
.status-badge{padding:4px 10px;border-radius:12px;color:#fff;font-size:13px;text-transform:capitalize;}
.status-badge.pending{background:#facc15;}
.status-badge.approved{background:#22c55e;}
.status-badge.completed{background:#3b82f6;}
.status-badge.rejected{background:#ef4444;}
.status-badge.cancelled{background:#6b7280;}
.actions{margin-top:20px;}
.actions a,.actions button{padding:8px 14px;border-radius:8px;font-size:14px;margin-right:10px;cursor:pointer;}
.actions .btn-receipt{background:#3b82f6;color:#fff;border:none;}
.actions .btn-cancel{background:#ef4444;color:#fff;border:none;}
</style>
</head>
<body>
<?php include 'components/user_header.php'; ?>

<div class="details-container">
    <h2>Transaction Details</h2>
    <div class="details-row"><span class="details-label">Transaction No.:</span><?= htmlspecialchars($transaction['transaction_no']) ?></div>
    <div class="details-row"><span class="details-label">Service:</span><?= htmlspecialchars($transaction['service']) ?></div>
    <div class="details-row"><span class="details-label">Appointment Date:</span><?= htmlspecialchars($transaction['appointment_date']) ?></div>
    <div class="details-row"><span class="details-label">Time Slot:</span><?= htmlspecialchars($transaction['time_slot']) ?></div>
    <div class="details-row"><span class="details-label">Date Seen:</span><?= $transaction['date_seen'] ?? '-' ?></div>
    <div class="details-row"><span class="details-label">Status:</span>
        <span class="status-badge <?= strtolower($transaction['apt_status']) ?>"><?= ucfirst($transaction['apt_status']) ?></span>
    </div>

    <div class="actions">
        <?php if(in_array($transaction['apt_status'],['Approved','Completed'])): ?>
            <a href="receipt.php?transaction_no=<?= $transaction['transaction_no'] ?>" target="_blank" class="btn-receipt">🧾 View Receipt</a>
        <?php endif; ?>
        <?php if(strtolower($transaction['apt_status'])=='pending'): ?>
        <form method="POST" onsubmit="return confirm('Cancel this appointment?');">
            <button type="submit" name="cancel" class="btn-cancel">❌ Cancel</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
