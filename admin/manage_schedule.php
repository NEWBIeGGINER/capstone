<?php
require_once '../components/connect.php';
require_once '../components/auth_admin.php';

if (!$is_admin_logged_in) {
    header("Location: login.php");
    exit;
}

// ================= ADD SCHEDULE =================
if (isset($_POST['add_schedule'])) {
    $date = $_POST['slots_date'];
    $slots = (int)$_POST['slots'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $duration = (int)$_POST['duration'];

    $status = $slots > 0 ? 'available' : 'noslots';

    $stmt = $conn->prepare("
        INSERT INTO schedules (slots_date, slots, total_slots, start_time, end_time, duration, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$date, $slots, $slots, $start_time, $end_time, $duration, $status]);

    header("Location: " . $_SERVER['PHP_SELF'] . "?msg=added");
    exit;
}

// ================= UPDATE SCHEDULE =================
if (isset($_POST['update_schedule'])) {
    $id = $_POST['id'];
    $date = $_POST['slots_date'];
    $new_slots = (int)$_POST['slots'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $duration = (int)$_POST['duration'];

    // Fetch current slots info
    $stmt = $conn->prepare("SELECT total_slots, slots FROM schedules WHERE id=?");
    $stmt->execute([$id]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$schedule) exit("Schedule not found");

    $total_slots = $schedule['total_slots'];
    $booked = $total_slots - $schedule['slots'];

    // Ensure new slots doesn't go below booked
    $adjusted_slots = max($new_slots - $booked, 0);
    $new_total_slots = max($adjusted_slots + $booked, $adjusted_slots);
    $status = $adjusted_slots > 0 ? 'available' : 'noslots';

    $stmt = $conn->prepare("
        UPDATE schedules
        SET slots_date=?, slots=?, total_slots=?, start_time=?, end_time=?, duration=?, status=?
        WHERE id=?
    ");
    $stmt->execute([$date, $adjusted_slots, $new_total_slots, $start_time, $end_time, $duration, $status, $id]);

    header("Location: " . $_SERVER['PHP_SELF'] . "?msg=updated");
    exit;
}

// ================= DELETE SCHEDULE =================
// Using POST so it matches SweetAlert form submission
if (isset($_POST['delete_schedule'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM schedules WHERE id=?");
    $stmt->execute([$id]);

    header("Location: " . $_SERVER['PHP_SELF'] . "?msg=deleted");
    exit;
}

// ================= FETCH SCHEDULES =================
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Count total rows for pagination
$totalRows = $conn->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Fetch schedules for current page
$stmt = $conn->prepare("
    SELECT * FROM schedules
    ORDER BY slots_date ASC
    LIMIT ?, ?
");
$stmt->bindValue(1, $start, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Schedule</title>
    <link rel="stylesheet" href="./../assets/css/admin/admin_header.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./../assets/css/admin/manage_schedule.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<?php include '../components/admin_header.php'; ?>

<div class="admin-main">
  <div class="header-row">
      <h1>Manage Schedule</h1>
  </div>

  <div class="card">
      <div class="card-header">
          <h2>Schedules</h2>
          <button class="btn btn-primary add-btn" onclick="openScheduleModal()">+ Add Schedule</button>
      </div>
      <div class="table-container">
          <table>
              <thead>
                  <tr>
                      <th>#</th>
                      <th>Total Slots</th>
                      <th>Available Slots</th>
                      <th>Date</th>
                      <th>Start Time</th>
                      <th>End Time</th>
                      <th>Duration</th>
                      <th class="actions">Actions</th>
                  </tr>
              </thead>
              <tbody>
                  <?php $i = $start + 1; foreach ($schedules as $s): ?>
                  <tr data-id="<?= $s['id'] ?>">
                      <td><?= $i++ ?></td>
                      <td><?= $s['total_slots'] ?></td>
                      <td><?= $s['slots'] ?></td>
                      <td><?= $s['slots_date'] ?></td>
                      <td><?= $s['start_time'] ?></td>
                      <td><?= $s['end_time'] ?></td>
                      <td><?= $s['duration'] ?></td>
                      <td class="actions">
                          <button class="btn btn-primary edit-btn">✏️ Edit</button>
                          <button class="btn btn-danger" onclick="confirmDeleteSchedule(<?= $s['id'] ?>)">🗑️ Delete</button>
                      </td>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>

          <!-- Pagination Links -->
          <div class="pagination">
              <?php if($page > 1): ?>
                  <a href="?page=<?= $page-1 ?>" class="btn btn-primary">Previous</a>
              <?php endif; ?>

              <?php for($p = 1; $p <= $totalPages; $p++): ?>
                  <a href="?page=<?= $p ?>" class="btn <?= $p==$page?'btn-primary':'btn-light' ?>"><?= $p ?></a>
              <?php endfor; ?>

              <?php if($page < $totalPages): ?>
                  <a href="?page=<?= $page+1 ?>" class="btn btn-primary">Next</a>
              <?php endif; ?>
          </div>
      </div>
  </div>
</div>

<!-- Add/Edit Schedule Modal -->
<div id="scheduleModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
        <h3 id="modal-title">Add Schedule</h3>
        <span class="close" id="closeScheduleModal">&times;</span>
    </div>
    <form method="POST">
        <input type="hidden" name="id" id="schedule-id">
        <div class="input-field">
            <p>Total Slots:</p>
            <input type="number" name="slots" id="schedule-slots" min="1" required>
        </div>
        <div class="input-field">
            <p>Date:</p>
            <input type="date" name="slots_date" id="schedule-date" required>
        </div>
        <div class="input-field">
            <p>Start Time:</p>
            <input type="time" name="start_time" id="schedule-start" required>
        </div>
        <div class="input-field">
            <p>End Time:</p>
            <input type="time" name="end_time" id="schedule-end" required>
        </div>
        <div class="input-field">
            <p>Duration (minutes):</p>
            <input type="number" name="duration" id="schedule-duration" min="1" required>
        </div>
        <button type="submit" name="add_schedule" id="modal-submit" class="btn btn-primary">Add Schedule</button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('scheduleModal');
    const closeBtn = document.getElementById('closeScheduleModal');
    const modalTitle = document.getElementById('modal-title');
    const modalSubmit = document.getElementById('modal-submit');
    const scheduleId = document.getElementById('schedule-id');
    const scheduleSlots = document.getElementById('schedule-slots');
    const scheduleDate = document.getElementById('schedule-date');
    const scheduleStart = document.getElementById('schedule-start');
    const scheduleEnd = document.getElementById('schedule-end');
    const scheduleDuration = document.getElementById('schedule-duration');

    function openScheduleModal(isEdit=false, data={}) {
        modal.classList.add('show');
        if(isEdit){
            modalTitle.innerText = 'Edit Schedule';
            modalSubmit.name = 'update_schedule';
            scheduleId.value = data.id;
            scheduleSlots.value = data.slots;
            scheduleDate.value = data.slots_date;
            scheduleStart.value = data.start_time;
            scheduleEnd.value = data.end_time;
            scheduleDuration.value = data.duration;
        } else {
            modalTitle.innerText = 'Add Schedule';
            modalSubmit.name = 'add_schedule';
            scheduleId.value = '';
            scheduleSlots.value = '';
            scheduleDate.value = '';
            scheduleStart.value = '';
            scheduleEnd.value = '';
            scheduleDuration.value = '';
        }
    }

    function closeScheduleModal() {
        modal.classList.remove('show');
    }

    // Add button
    document.querySelector('button[onclick="openScheduleModal()"]').addEventListener('click', () => openScheduleModal());

    // Close button
    closeBtn.addEventListener('click', closeScheduleModal);

    // Close modal on clicking outside content
    window.addEventListener('click', function(event){
        if(event.target === modal){
            closeScheduleModal();
        }
    });

    // Edit buttons
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('tr');
            openScheduleModal(true, {
                id: row.dataset.id,
                slots: row.cells[1].innerText,
                total_slots: row.cells[2].innerText,
                slots_date: row.cells[3].innerText,
                start_time: row.cells[4].innerText,
                end_time: row.cells[5].innerText,
                duration: row.cells[6].innerText
            });
        });
    });

    // Mini SweetAlert toast for add/update/delete
    <?php if(isset($_GET['msg'])): ?>
        let msg = "<?php echo $_GET['msg']; ?>";
        let title = "";
        if(msg === "added") title = "Schedule added successfully!";
        else if(msg === "updated") title = "Schedule updated successfully!";
        else if(msg === "deleted") title = "Schedule deleted!";

        if(title) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: title,
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                customClass: { popup: 'swal2-mini' }
            });

            // Remove query string to prevent repeat
            if (window.history.replaceState) {
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: cleanUrl}, '', cleanUrl);
            }
        }
    <?php endif; ?>

});

// Mini SweetAlert for Delete Confirmation
function confirmDeleteSchedule(id) {
    Swal.fire({
        title: "Delete this schedule?",
        text: "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, delete it",
        cancelButtonText: "Cancel",
        customClass: { popup: 'swal2-mini' } // <-- Mini styling
    }).then((result) => {
        if(result.isConfirmed){
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            form.innerHTML = `<input type="hidden" name="delete_schedule" value="1">
                              <input type="hidden" name="id" value="${id}">`;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>




</body>
</html>
