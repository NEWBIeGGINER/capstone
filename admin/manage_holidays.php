<?php
require_once '../components/connect.php';
require_once '../components/auth_admin.php';

if (!$is_admin_logged_in) {
    header("Location: login.php");
    exit;
}

// ================= ADD HOLIDAY =================
if (isset($_POST['add_holiday'])) {
    $date = $_POST['holiday_date'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO holidays (id, holiday_date, description) VALUES (UUID(), ?, ?)");
    $stmt->execute([$date, $description]);

    header("Location: " . $_SERVER['PHP_SELF'] . "?msg=added");
    exit;
}

// ================= UPDATE HOLIDAY =================
if (isset($_POST['update_holiday'])) {
    $id = $_POST['id'];
    $date = $_POST['holiday_date'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("UPDATE holidays SET holiday_date=?, description=? WHERE id=?");
    $stmt->execute([$date, $description, $id]);

    header("Location: " . $_SERVER['PHP_SELF'] . "?msg=updated");
    exit;
}

// ================= DELETE HOLIDAY =================
// Use POST instead of GET to trigger SweetAlert correctly
if (isset($_POST['delete_holiday'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM holidays WHERE id=?");
    $stmt->execute([$id]);

    header("Location: " . $_SERVER['PHP_SELF'] . "?msg=deleted");
    exit;
}

// ================= FETCH HOLIDAYS =================
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$totalRows = $conn->query("SELECT COUNT(*) FROM holidays")->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $conn->prepare("SELECT * FROM holidays ORDER BY holiday_date ASC LIMIT ?, ?");
$stmt->bindValue(1, $start, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->execute();
$holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Holidays</title>
    <link rel="stylesheet" href="./../assets/css/admin/admin_header.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./../assets/css/admin/manage_holidays.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
 <?php include '../components/admin_header.php'; ?>

<div class="admin-main">
  <div class="header-row">
      <h1>Manage Holidays</h1>
  </div>

  <div class="card">
      <div class="card-header">
          <h2>Holidays</h2>
          <button class="btn btn-primary" id="addHolidayBtn">+ Add Holiday</button>
      </div>
      <div class="table-container">
          <table>
              <thead>
                  <tr>
                      <th>#</th>
                      <th>Date</th>
                      <th>Description</th>
                      <th class="actions">Actions</th>
                  </tr>
              </thead>
              <tbody>
                  <?php $i = $start + 1; foreach ($holidays as $h): ?>
                  <tr data-id="<?= $h['id'] ?>">
                      <td><?= $i++ ?></td>
                      <td><?= $h['holiday_date'] ?></td>
                      <td><?= $h['description'] ?></td>
                      <td class="actions">
                          <button 
                            class="btn btn-primary edit-btn"
                            data-id="<?= $h['id'] ?>"
                            data-date="<?= $h['holiday_date'] ?>"
                            data-description="<?= htmlspecialchars($h['description'], ENT_QUOTES) ?>"
                          >✏️ Edit</button>
                          <button class="btn btn-danger delete-btn" data-id="<?= $h['id'] ?>">🗑️ Delete</button>
                      </td>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>

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

<!-- Add/Edit Modal -->
<div id="holidayModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
        <h3 id="modal-title">Add Holiday</h3>
        <span class="close" id="closeModalBtn">&times;</span>
    </div>
    <form method="POST" id="holidayForm">
        <input type="hidden" name="id" id="holiday-id">
        <div class="input-field">
            <p>Date (MM-DD):</p>
            <input type="text" name="holiday_date" id="holiday-date" placeholder="MM-DD" required>
        </div>
        <div class="input-field">
            <p>Description:</p>
            <input type="text" name="description" id="holiday-description" placeholder="Holiday description" required>
        </div>
        <button type="submit" name="add_holiday" id="modal-submit" class="btn btn-primary">Add Holiday</button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('holidayModal');
    const addBtn = document.getElementById('addHolidayBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const modalTitle = document.getElementById('modal-title');
    const modalSubmit = document.getElementById('modal-submit');
    const holidayId = document.getElementById('holiday-id');
    const holidayDate = document.getElementById('holiday-date');
    const holidayDescription = document.getElementById('holiday-description');
    const holidayForm = document.getElementById('holidayForm');

    function openModal(isEdit=false, data={}) {
        modal.classList.add('show');
        if(isEdit){
            modalTitle.innerText = 'Edit Holiday';
            modalSubmit.name = 'update_holiday';
            holidayId.value = data.id;
            holidayDate.value = data.date;
            holidayDescription.value = data.description;
        } else {
            modalTitle.innerText = 'Add Holiday';
            modalSubmit.name = 'add_holiday';
            holidayId.value = '';
            holidayDate.value = '';
            holidayDescription.value = '';
        }
    }

    function closeModal() {
        modal.classList.remove('show');
    }

    addBtn.addEventListener('click', () => openModal());
    closeBtn.addEventListener('click', closeModal);
    window.addEventListener('click', e => { if(e.target === modal) closeModal(); });

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            openModal(true, {
                id: btn.dataset.id,
                date: btn.dataset.date,
                description: btn.dataset.description
            });
        });
    });

    // Mini SweetAlert delete confirmation
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            Swal.fire({
                title: 'Delete this holiday?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel',
                customClass: { popup: 'swal2-mini' }
            }).then(result => {
                if(result.isConfirmed){
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';
                    form.innerHTML = `<input type="hidden" name="delete_holiday" value="1">
                                      <input type="hidden" name="id" value="${id}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });

    // Mini SweetAlert toast for add/update/delete
    <?php if(isset($_GET['msg'])): ?>
        let msg = "<?php echo $_GET['msg']; ?>";
        let title = '';
        if(msg === 'added') title = 'Holiday added successfully!';
        else if(msg === 'updated') title = 'Holiday updated successfully!';
        else if(msg === 'deleted') title = 'Holiday deleted!';

        if(title){
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
            if(window.history.replaceState){
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: cleanUrl}, '', cleanUrl);
            }
        }
    <?php endif; ?>
});
</script>


</body>
</html>
