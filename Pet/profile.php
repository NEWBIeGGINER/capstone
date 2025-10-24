<?php
require_once 'components/connect.php';
require_once 'components/auth.php'; // provides $user_id

header('Content-Type: text/html; charset=UTF-8');

$success = '';
$error = '';
$transaction_no = $_GET['transaction_no'] ?? null;

// ==================== AJAX CANCEL HANDLER ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    header('Content-Type: application/json');

    $appointment_id = $_POST['appointment_id'];

    // Fetch appointment + transaction
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE id=? AND user_id=?");
    $stmt->execute([$appointment_id, $user_id]);
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }

    if (!in_array($appt['status'], ['Pending', 'Confirmed'])) {
        echo json_encode(['success' => false, 'message' => 'You cannot cancel this appointment.']);
        exit;
    }

    // Cancel appointment
    $stmt1 = $conn->prepare("UPDATE appointments SET status='Cancelled' WHERE id=? AND user_id=?");
    $stmt1->execute([$appointment_id, $user_id]);

    // Cancel related transaction if any
    if (!empty($appt['transaction_no'])) {
        $stmt2 = $conn->prepare("UPDATE transactions SET status='Cancelled' WHERE transaction_no=? AND user_id=?");
        $stmt2->execute([$appt['transaction_no'], $user_id]);
    }

    // Restore slot count
    $times = explode(' - ', $appt['time_slot']);
    $start_time = date('H:i', strtotime($times[0]));
    $end_time = date('H:i', strtotime($times[1]));

    $stmtS = $conn->prepare("
        UPDATE schedules 
        SET slots = slots + 1 
        WHERE slots_date=? AND start_time=? AND end_time=? AND slots < total_slots
    ");
    $stmtS->execute([$appt['appointment_date'], $start_time, $end_time]);

    echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully.']);
    exit;
}

// ==================== PROFILE UPDATE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    try {
        $conn->beginTransaction();

        // Handle profile picture upload
        if (!empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === 0) {
            $allowed = ['jpg','jpeg','png','gif'];
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                throw new Exception("Invalid image type. Only JPG, PNG, GIF allowed.");
            }

            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $filename = time() . '_' . basename($_FILES['profile_pic']['name']);
            $targetFile = $uploadDir . $filename;

            if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                throw new Exception("Failed to upload profile picture.");
            }

            // Delete old pic if custom
            $stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id=?");
            $stmt->execute([$user_id]);
            $old = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($old['profile_pic']) && file_exists($old['profile_pic']) && $old['profile_pic'] !== 'uploads/user.png') {
                @unlink($old['profile_pic']);
            }

            $stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE id=?");
            $stmt->execute([$targetFile, $user_id]);
        }

        // Update info
        $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=? WHERE id=?");
        $stmt->execute([$name, $phone, $address, $user_id]);

        $conn->commit();
        $success = "Profile updated successfully!";
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}

// ==================== FETCH USER INFO ====================
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$profilePic = (!empty($user['profile_pic']) && file_exists($user['profile_pic'])) 
    ? $user['profile_pic'] 
    : 'uploads/user.png';

// ==================== TOTALS ====================
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id='$user_id'")->fetchColumn();
$totalAppointments = $conn->query("SELECT COUNT(*) FROM appointments WHERE user_id='$user_id'")->fetchColumn();

// ==================== APPOINTMENTS ====================
$appointmentsStmt = $conn->prepare("
    SELECT a.*, s.name AS service_name, t.transaction_no
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    LEFT JOIN transactions t ON a.transaction_no = t.transaction_no
    WHERE a.user_id = ?
    ORDER BY a.appointment_date DESC
");
$appointmentsStmt->execute([$user_id]);

// ==================== ORDERS ====================
$ordersStmt = $conn->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
$ordersStmt->execute([$user_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/profile.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.profile-pic-wrapper img { border-radius: 50%; margin-bottom: 10px; }
.alert { padding:10px; margin-bottom:10px; border-radius:5px; }
.alert.success { background:#d4edda; color:#155724; }
.alert.error { background:#f8d7da; color:#721c24; }
.btn-view {
  background: #3b82f6;
  color: #fff;
  padding: 6px 10px;
  border-radius: 6px;
  text-decoration: none;
}
.btn-view:hover { background: #2563eb; }
.cancel-btn {
  background: #ef4444;
  color: #fff;
  border: none;
  padding: 6px 10px;
  border-radius: 6px;
  cursor: pointer;
}
.cancel-btn:hover { background: #dc2626; }
.status.cancelled { color: #dc2626; font-weight: bold; }

</style>
</head>
<body>
<?php include 'components/user_header.php'; ?>

<div class="profile-container">
  <h2>👤 My Profile</h2>

  <div style="margin:15px 0;">
    <strong>Total Orders:</strong> <?= $totalOrders ?> |
    <strong>Total Appointments:</strong> <?= $totalAppointments ?>
  </div>

  <div class="tabs">
    <button class="tab-btn active" data-tab="profile">Update Profile</button>
    <button class="tab-btn" data-tab="orders">My Orders</button>
    <button class="tab-btn" data-tab="appointments">My Appointments</button>
  </div>

  <!-- Profile Tab -->
  <div class="tab-content active" id="profile">
    <?php if ($success): ?><div class="alert success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>

    <form method="post" class="profile-form" enctype="multipart/form-data">
      <div class="profile-pic-wrapper">
        <img id="profile-pic-preview" src="<?= htmlspecialchars($profilePic) ?>" width="120" alt="Profile Picture">
      </div>
      <input type="file" name="profile_pic" id="profile-pic-input" accept="image/*">

      <label>Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

      <label>Email</label>
      <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>

      <label>Contact</label>
      <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>

      <label>Address</label>
      <textarea name="address" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>

      <button type="submit" name="update_profile">Update Profile</button>
    </form>
  </div>

  <!-- Orders Tab -->
  <div class="tab-content" id="orders">
    <h3>📦 My Orders</h3>
    <table>
      <thead><tr><th>Order #</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        <?php while($o = $ordersStmt->fetch(PDO::FETCH_ASSOC)): ?>
          <tr>
            <td><?= htmlspecialchars($o['id']) ?></td>
            <td>₱<?= htmlspecialchars($o['total'] ?? '0.00') ?></td>
            <td><?= ucfirst($o['status']) ?></td>
            <td><?= htmlspecialchars($o['created_at']) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Appointments Tab -->
  <div class="tab-content" id="appointments">
    <h3>🐾 My Appointments</h3>
    <table>
      <thead>
        <tr>
          <th>Service</th>
          <th>Date</th>
          <th>Time</th>
          <th>Status</th>
          <th>Receipt</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $appointmentsStmt->fetch(PDO::FETCH_ASSOC)): ?>
          <tr id="appointment-<?= $row['id'] ?>">
            <td><?= htmlspecialchars($row['service_name']) ?></td>
            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td><?= htmlspecialchars($row['time_slot']) ?></td>
            <td class="status"><?= ucfirst($row['status']) ?></td>

            <td>
              <?php if (!empty($row['transaction_no']) && in_array(strtolower($row['status']), ['approved', 'completed'])): ?>
                <a href="receipt.php?transaction_no=<?= urlencode($row['transaction_no']) ?>" class="btn-view" target="_blank">🧾 View Receipt</a>
              <?php else: ?>
                <span>-</span>
              <?php endif; ?>
            </td>

            <td>
              <?php if (in_array(strtolower($row['status']), ['pending', 'confirmed'])): ?>
                <button class="cancel-btn" data-id="<?= $row['id'] ?>">❌ Cancel</button>
              <?php else: ?>
                <span>-</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn, .tab-content').forEach(el => el.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(btn.dataset.tab).classList.add('active');
  });
});

// Image preview
const picInput = document.getElementById('profile-pic-input');
const picPreview = document.getElementById('profile-pic-preview');
if (picInput) {
  picInput.addEventListener('change', () => {
    const file = picInput.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => picPreview.src = e.target.result;
    reader.readAsDataURL(file);
  });
}

// Cancel appointment
document.querySelectorAll('.cancel-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const appointmentId = btn.dataset.id;

    Swal.fire({
      title: "Cancel Appointment?",
      text: "This action cannot be undone.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Yes, cancel it"
    }).then(result => {
      if (result.isConfirmed) {
        fetch("", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "appointment_id=" + encodeURIComponent(appointmentId)
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            Swal.fire("Cancelled!", data.message, "success");
            const row = document.getElementById("appointment-" + appointmentId);
            if (row) {
              row.querySelector(".status").textContent = "Cancelled";
              row.querySelector(".status").classList.add("cancelled");
              btn.remove();
            }
          } else {
            Swal.fire("Error", data.message || "Unable to cancel appointment.", "error");
          }
        })
        .catch(() => Swal.fire("Error", "Something went wrong.", "error"));
      }
    });
  });
});
</script>
</body>
</html>
