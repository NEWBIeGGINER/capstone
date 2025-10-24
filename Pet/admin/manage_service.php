<?php 
require_once '../components/connect.php';
require_once '../components/auth_admin.php';

if (!$is_admin_logged_in) {
    header("Location: login.php");
    exit;
}

// === ADD SERVICE ===
if (isset($_POST['add_service'])) {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $content = filter_var($_POST['content'], FILTER_SANITIZE_STRING);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

    $image = $_FILES['image']['name'];
    $image_size = $_FILES['image']['size'];
    $image_tmp_name = $_FILES['image']['tmp_name'];

    if (!empty($image)) {
        // check kung may kaparehong image sa DB
        $check_image = $conn->prepare("SELECT * FROM `services` WHERE image = ?");
        $check_image->execute([$image]);

        if ($check_image->rowCount() > 0) {
            $warning_msg[] = 'Your image already exists, please rename your file!';
        } elseif ($image_size > 2000000) {
            $warning_msg[] = 'Image size is too large';
        } else {
            $image_folder = '../uploaded_files/'.$image;

            $insert_service = $conn->prepare("
                INSERT INTO `services` (name, service_detail, status, image) 
                VALUES (?, ?, ?, ?)
            ");
            $insert_service->execute([$name, $content, $status, $image]);

            move_uploaded_file($image_tmp_name, $image_folder);

            header("Location: manage_service.php?msg=added");
            exit();
        }
    }
}

// === EDIT SERVICE ===
if (isset($_POST['update_service'])) {
    $service_id = filter_var($_POST['service_id'], FILTER_SANITIZE_STRING);
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $content = filter_var($_POST['content'], FILTER_SANITIZE_STRING);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

    $old_image = $_POST['old_image'];
    $image = $_FILES['image']['name'];
    $image_size = $_FILES['image']['size'];
    $image_tmp_name = $_FILES['image']['tmp_name'];

    $has_error = false;

    if (!empty($image)) {
        $check_image = $conn->prepare("SELECT * FROM `services` WHERE image = ? AND id != ?");
        $check_image->execute([$image, $service_id]);

        if ($check_image->rowCount() > 0) {
            $warning_msg[] = 'Your image already exists, please rename your file!';
            $has_error = true;
        } elseif ($image_size > 2000000) {
            $warning_msg[] = 'Image size is too large';
            $has_error = true;
        }
    }

    if (!$has_error) {
        $update_service = $conn->prepare("UPDATE `services` SET name=?, service_detail=?, status=? WHERE id=?");
        $update_service->execute([$name, $content, $status, $service_id]);

        if (!empty($image)) {
            $image_folder = '../uploaded_files/'.$image;

            $update_image = $conn->prepare("UPDATE `services` SET image=? WHERE id=?");
            $update_image->execute([$image, $service_id]);

            move_uploaded_file($image_tmp_name, $image_folder);

            if ($old_image != $image && !empty($old_image) && file_exists('../uploaded_files/'.$old_image)) {
                unlink('../uploaded_files/'.$old_image);
            }
        }

        header("Location: manage_service.php?msg=updated");
        exit();
    }
}

// === DELETE SERVICE ===
if (isset($_POST['delete_service'])) {
    $service_id = filter_var($_POST['service_id'], FILTER_SANITIZE_STRING);

    $delete_image = $conn->prepare("SELECT image FROM `services` WHERE id=? LIMIT 1");
    $delete_image->execute([$service_id]);
    $fetch_delete_image = $delete_image->fetch(PDO::FETCH_ASSOC);

    if ($fetch_delete_image && !empty($fetch_delete_image['image'])) {
        $image_path = '../uploaded_files/'.$fetch_delete_image['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    $delete_service = $conn->prepare("DELETE FROM `services` WHERE id=?");
    $delete_service->execute([$service_id]);

    header("Location: manage_service.php?msg=deleted");
    exit();
}

// === SHOW SUCCESS/ERROR MSGS ===
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $success_msg[] = "Service added successfully!";
    } elseif ($_GET['msg'] === 'updated') {
        $success_msg[] = "Service updated successfully!";
    } elseif ($_GET['msg'] === 'deleted') {
        $success_msg[] = "Service deleted successfully!";
    }
}

// === FETCH SERVICES ===
$select_services = $conn->prepare("SELECT * FROM `services` ORDER BY id DESC");
$select_services->execute();
$services = $select_services->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Services</title>
    <link rel="stylesheet" href="./../assets/css/admin/manage_service.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
   <!-- Overlay -->
<div class="sidebar-overlay" id="overlay"></div>

<div class="admin-container">
    <?php include '../components/admin_header.php' ?>

    <div class="admin-main">
        <div class="header-row">
            <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>
            <h1>Manage Services</h1>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Services List</h2>
                <button onclick="openModal('addModal')" class="btn btn-primary add-btn">+ Add Service</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th class="actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><img src="../uploaded_files/<?= htmlspecialchars($service['image']); ?>" class="service-img"></td>
                                    <td><?= htmlspecialchars($service['name']); ?></td>
                                    <td><?= htmlspecialchars(ucfirst($service['status'])); ?></td>
                                    <td class="description" data-full="<?= htmlspecialchars($service['service_detail']); ?>">
                                        <?= htmlspecialchars(mb_strimwidth($service['service_detail'], 0, 60, '...')); ?>
                                    </td>
                                    <td class="actions">
                                        <button class="btn btn-primary" 
                                                onclick='openEditModal(<?= json_encode($service, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                            ✏️ Edit
                                        </button>
                                        <button type="button" class="btn btn-danger" 
                                                onclick="confirmDeleteService(<?= (int)$service['id']; ?>)">
                                            🗑️ Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5">No services found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addModal')">&times;</span>
        <h2>Add Service</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="input-field">
                <p>Status</p>
                <select name="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="input-field">
                <p>Service Name</p>
                <input type="text" name="name" required>
            </div>
            <div class="input-field">
                <p>Description</p>
                <textarea name="content" required></textarea>
            </div>
            <div class="input-field">
                <p>Image</p>
                <input type="file" name="image" accept="image/*" required>
            </div>
            <button type="submit" name="add_service" class="btn">Add</button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editModal')">&times;</span>
        <h2>Edit Service</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="service_id" id="edit_id">
            <input type="hidden" name="old_image" id="edit_old_image">
            <div class="input-field">
                <p>Status</p>
                <select name="status" id="edit_status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="input-field">
                <p>Service Name</p>
                <input type="text" name="name" id="edit_name" required>
            </div>
            <div class="input-field">
                <p>Description</p>
                <textarea name="content" id="edit_content" required></textarea>
            </div>
            <div class="input-field">
                <p>Image</p>
                <input type="file" name="image" accept="image/*">
                <img id="edit_preview" src="" width="100" style="margin-top:10px;">
            </div>
            <button type="submit" name="update_service" class="btn">Update</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    <?php if(!empty($success_msg)): ?>
        <?php foreach($success_msg as $msg): ?>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: "<?= htmlspecialchars($msg); ?>",
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                customClass: { popup: 'swal2-mini' }
            });
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if(!empty($warning_msg)): ?>
        <?php foreach($warning_msg as $msg): ?>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: "<?= htmlspecialchars($msg); ?>",
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                customClass: { popup: 'swal2-mini' }
            });
        <?php endforeach; ?>
    <?php endif; ?>

    // Remove query params from URL so alerts don’t repeat on refresh
    if (window.history.replaceState) {
        const url = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({path:url}, '', url);
    }
});

// Modal functions
function openModal(id) {
    document.getElementById(id).classList.add("show");
}
function closeModal(id) {
    document.getElementById(id).classList.remove("show");
}

// Populate Edit Modal
function openEditModal(service) {
    document.getElementById('edit_id').value = service.id;
    document.getElementById('edit_old_image').value = service.image;
    document.getElementById('edit_status').value = service.status;
    document.getElementById('edit_name').value = service.name;
    document.getElementById('edit_content').value = service.service_detail;
    document.getElementById('edit_preview').src = "../uploaded_files/" + service.image;

    openModal('editModal');
}

// SweetAlert Delete with mini style
function confirmDeleteService(id) {
    Swal.fire({
        title: "Delete this service?",
        text: "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, delete it",
        cancelButtonText: "Cancel",
        customClass: { popup: 'swal2-mini' }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            form.innerHTML = `
                <input type="hidden" name="service_id" value="${id}">
                <input type="hidden" name="delete_service" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>



    <script src="./../assets/js/dashboard.js"></script>
</body>
</html>
