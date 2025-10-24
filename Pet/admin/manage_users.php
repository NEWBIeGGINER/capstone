<?php
require_once '../config.php';
require_once '../components/connect.php';
require_once '../components/auth_admin.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

// Redirect if not admin
if (!$is_admin_logged_in) {
    header("Location: ../signin.php");
    exit;
}

// ================= Helper Functions =================
function unique_id() {
    return bin2hex(random_bytes(16)); // 32-char unique ID
}

function sendAccountEmail($name, $email, $role, $password = null, $code = null) {
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
        $mail->addAddress($email, $name);
        $mail->isHTML(true);

        if ($role === 'user' && $code) {
            $mail->Subject = "Petcare Verification Code";
            $mail->Body = "<h2>Hello, $name!</h2>
                           <p>Your verification code: <b>$code</b></p>";
        } elseif ($role === 'admin' && $password) {
            $mail->Subject = "Petcare Admin Account Created";
            $mail->Body = "<h2>Hello, $name!</h2>
                           <p>Your temporary admin password: <b>$password</b></p>";
        }

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
    }
}

// ================= Handle POST Actions =================
if (isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $id       = !empty($_POST['id']) ? $_POST['id'] : unique_id();
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $status   = $_POST['status'] ?? 'active';
    $role     = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';

    if ($_POST['action'] === 'add' && !$password && $role === 'admin') {
        $password = bin2hex(random_bytes(4)); // temporary password for admin
    }

    $hashedPassword = $password ? password_hash($password, PASSWORD_DEFAULT) : null;

    try {
        // ===== ADD =====
        if ($_POST['action'] === 'add') {
            $table = $role === 'admin' ? 'admin' : 'users';
            $columns = ['id','name','email','phone','password','address','status','role'];
            $values  = [$id, $name, $email, $phone, $hashedPassword, $address, $status, $role];

            if ($role === 'user') {
                $columns[] = 'verification_code';
                $columns[] = 'is_verified';
                $values[] = rand(100000,999999);
                $values[] = 0;
            }

            $placeholders = implode(',', array_fill(0, count($values), '?'));
            $stmt = $conn->prepare("INSERT INTO $table (".implode(',', $columns).") VALUES ($placeholders)");
            $stmt->execute($values);

            sendAccountEmail($name, $email, $role, $password, $values[8] ?? null);
            echo json_encode(['success'=>true,'msg'=>ucfirst($role).' added successfully','id'=>$id]);
            exit;
        }

        // ===== EDIT =====
        if ($_POST['action'] === 'edit') {
            // Determine table
            $table = null;
            $userCheck = $conn->prepare("SELECT id FROM users WHERE id=?");
            $userCheck->execute([$id]);
            if ($userCheck->fetch()) $table = 'users';
            else {
                $adminCheck = $conn->prepare("SELECT id FROM admin WHERE id=?");
                $adminCheck->execute([$id]);
                if ($adminCheck->fetch()) $table = 'admin';
            }

            if (!$table) {
                echo json_encode(['success'=>false,'msg'=>'Record not found']);
                exit;
            }

            $fields = ['name=?','email=?','phone=?','address=?','status=?','role=?'];
            $params = [$name,$email,$phone,$address,$status,$role];
            if (!empty($password)) { $fields[]='password=?'; $params[] = $hashedPassword; }
            $params[] = $id;

            $stmt = $conn->prepare("UPDATE $table SET ".implode(',', $fields)." WHERE id=?");
            $stmt->execute($params);

            echo json_encode(['success'=>true,'msg'=>ucfirst($table).' updated successfully']);
            exit;
        }

        // ===== DELETE =====
        if ($_POST['action'] === 'delete') {
            $table = $type = null;

            $userCheck = $conn->prepare("SELECT id FROM users WHERE id=?"); $userCheck->execute([$id]);
            if($userCheck->fetch()){ $table='users'; $type='user'; }
            else { 
                $adminCheck=$conn->prepare("SELECT id FROM admin WHERE id=?"); $adminCheck->execute([$id]);
                if($adminCheck->fetch()){ $table='admin'; $type='admin'; }
            }

            if(!$type){ 
                echo json_encode(['success'=>false,'msg'=>'Record not found']); 
                exit; 
            }

            // Prevent self-delete
            if($type==='admin' && $_SESSION['admin_id']===$id){
                echo json_encode(['success'=>false,'msg'=>"You cannot delete your own account"]);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM $table WHERE id=?"); 
            $stmt->execute([$id]);

            echo json_encode(['success'=>true,'msg'=>ucfirst($type).' deleted successfully']);
            exit;
        }

    } catch(PDOException $ex) {
        echo json_encode(['success'=>false,'msg'=>'Database error: '.$ex->getMessage()]);
        exit;
    }
}

// ================= FETCH ALL ACCOUNTS =================
$accounts = $conn->query("
    SELECT id,name,email,phone,address,status,role,created_at FROM users
    UNION ALL
    SELECT id,name,email,phone,address,status,role,created_at FROM admin
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Add numeric row numbers
foreach ($accounts as $i => &$user) { 
    $user['row_num'] = $i+1; 
}
unset($user);
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users</title>
<link rel="stylesheet" href="../assets/css/admin/admin_header.css?v=<?= time(); ?>">
<link rel="stylesheet" href="../assets/css/admin/manage_users.css?v=<?= time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include '../components/admin_header.php'; ?>

<div class="admin-main">
<h1>👤 Manage Users</h1>

<div class="card add-user-form">
<h2 id="formTitle">Add New Account</h2>
<form id="addUserForm">
<input type="hidden" name="id" id="userId">
<input type="hidden" name="action" id="formAction" value="add">
<input type="text" name="name" id="name" placeholder="Full Name" required>
<input type="email" name="email" id="email" placeholder="Email" required>
<input type="text" name="phone" id="phone" placeholder="Phone">
<input type="text" name="address" id="address" placeholder="Address" required>
<input type="password" name="password" id="password" placeholder="Password">
<select name="role" id="role" required>
<option value="user">User</option>
<option value="admin">Admin</option>
</select>
<select name="status" id="status">
<option value="active">Active</option>
<option value="inactive">Inactive</option>
<option value="banned">Banned</option>
</select>
<button type="submit" class="btn btn-success" id="submitBtn">Create Account</button>
</form>
</div>

<div class="card">
<div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
<h2>Existing Users</h2>
<div>
<label for="roleFilter">Filter </label>
<select id="roleFilter">
<option value="all">All</option>
<option value="user">User</option>
<option value="admin">Admin</option>
</select>
</div>
</div>
<div class="table-container">
<table id="usersTable">
<thead>
<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Status</th><th>Role</th><th>Actions</th></tr>
</thead>
<tbody>
<?php if(count($accounts) > 0): foreach($accounts as $user): ?>
<tr data-id="<?= $user['id'] ?>" data-name="<?= htmlspecialchars($user['name']) ?>"
data-email="<?= htmlspecialchars($user['email']) ?>" data-phone="<?= htmlspecialchars($user['phone']) ?>"
data-address="<?= htmlspecialchars($user['address']) ?>" data-status="<?= $user['status'] ?>"
data-role="<?= $user['role'] ?>">
<td><?= $user['row_num'] ?></td>
<td><?= htmlspecialchars($user['name']) ?></td>
<td><?= htmlspecialchars($user['email']) ?></td>
<td><?= htmlspecialchars($user['phone']) ?></td>
<td><?= htmlspecialchars($user['address']) ?></td>
<td><?= $user['status'] ?></td>
<td><?= $user['role'] ?></td>
<td>
<button class="btn btn-primary btn-edit">Edit</button>
<button class="btn btn-danger btn-delete">Delete</button>
</td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="8" style="text-align:center;">No users found</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>

<script>
document.getElementById('roleFilter').addEventListener('change', e=>{
    const val=e.target.value;
    document.querySelectorAll('#usersTable tbody tr').forEach(r=>{
        r.style.display=(val==='all'||r.dataset.role===val)?'':'none';
    });
});

function showToast(msg,type='success'){
    Swal.fire({toast:true,position:'top-end',icon:type,title:msg,showConfirmButton:false,timer:2500,timerProgressBar:true,customClass:{popup:'swal2-mini'}});
}

async function postFormData(formData){ return await (await fetch('',{method:'POST',body:formData})).json(); }

const addUserForm=document.getElementById('addUserForm');
addUserForm.addEventListener('submit',async e=>{
    e.preventDefault();
    const fd=new FormData(addUserForm);
    fd.set('action',document.getElementById('userId').value?'edit':'add');
    const data=await postFormData(fd);
    showToast(data.msg,data.success?'success':'error');
    if(data.success) setTimeout(()=>location.reload(),1000);
});

document.querySelectorAll('.btn-edit').forEach(btn=>btn.addEventListener('click',e=>{
    const tr=e.currentTarget.closest('tr');
    document.getElementById('userId').value=tr.dataset.id;
    document.getElementById('name').value=tr.dataset.name;
    document.getElementById('email').value=tr.dataset.email;
    document.getElementById('phone').value=tr.dataset.phone;
    document.getElementById('address').value=tr.dataset.address;
    document.getElementById('role').value=tr.dataset.role;
    document.getElementById('status').value=tr.dataset.status;
    document.getElementById('password').value='';
    document.getElementById('formAction').value='edit';
    document.getElementById('formTitle').innerText='Edit Account';
    document.getElementById('submitBtn').innerText='Save Changes';
    addUserForm.scrollIntoView({behavior:'smooth'});
}));

document.querySelectorAll('.btn-delete').forEach(btn=>btn.addEventListener('click',e=>{
    const tr=e.currentTarget.closest('tr');
    const id=tr.dataset.id;
    Swal.fire({
        title:'Delete this user?',
        text:'This action cannot be undone.',
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#d33',
        cancelButtonColor:'#6c757d',
        confirmButtonText:'Yes, delete it',
        cancelButtonText:'Cancel',
        customClass:{popup:'swal2-mini'}
    }).then(async r=>{
        if(r.isConfirmed){
            const fd=new FormData();
            fd.append('action','delete');
            fd.append('id',id);
            const data=await postFormData(fd);
            showToast(data.msg,data.success?'success':'error');
            if(data.success) setTimeout(()=>location.reload(),1000);
        }
    });
}));
</script>




<style>
.modal {display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.5);}
.modal-content {background:#fff; margin:10% auto; padding:20px; border-radius:8px; width:400px; max-width:90%;}
.close {float:right; font-size:24px; cursor:pointer;}
.card {background:#fff; padding:20px; border-radius:8px; margin-bottom:20px; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
.card-header h2 {margin:0 0 10px;}
.btn {padding:6px 12px; border:none; border-radius:6px; cursor:pointer;}
.btn-success {background:#4BB543; color:#fff;}
.btn-primary {background:#1E90FF; color:#fff;}
.btn-danger {background:#FF4C4C; color:#fff;}
table {width:100%; border-collapse:collapse;}
th, td {padding:10px; border-bottom:1px solid #ddd;}
.swal2-mini {
  width: 300px !important;
  padding: 15px !important;
  font-size: 14px !important;
  border-radius: 10px !important;
}
.swal2-mini .swal2-title { font-size: 16px !important; }
.swal2-mini .swal2-html-container { font-size: 13px !important; }
.swal2-mini .swal2-actions button { padding: 5px 10px !important; font-size: 13px !important; }

/* Filter Container */
.card-header select#roleFilter {
    padding: 6px 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    background: #fff;
    font-size: 14px;
    cursor: pointer;
    outline: none;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.card-header select#roleFilter:focus {
    border-color: #1E90FF;
    box-shadow: 0 0 5px rgba(30,144,255,0.5);
}

/* Optional: label spacing */
.card-header label {
    margin-right: 8px;
    font-weight: 500;
    font-size: 14px;
}
</style>
</body>
</html>
