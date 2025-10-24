<?php
require_once '../components/connect.php';
require_once '../components/auth_admin.php';

if (!$is_admin_logged_in) {
    header("Location: login.php");
    exit;
}

// === Add Comment (AJAX) ===
if (isset($_POST['add_comment'])) {
    header('Content-Type: application/json');
    $post_id = $_POST['post_id'] ?? 0;
    $comment = trim($_POST['comment']);

    if ($comment !== "") {
        $stmt = $conn->prepare("INSERT INTO community_comments (post_id, user_id, comment, created_at) VALUES (?, 'admin', ?, NOW())");
        $stmt->execute([$post_id, $comment]);
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['success' => false]);
        exit;
    }
}

// === Fetch comments for AJAX ===
if (isset($_POST['fetch_comments'])) {
    header('Content-Type: application/json');
    $post_id = $_POST['post_id'] ?? 0;
    $stmt = $conn->prepare("SELECT c.*, COALESCE(u.name, 'User') AS commenter_name FROM community_comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'comments' => $comments]);
    exit;
}


// === Handle AJAX Delete ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_post') {
    header('Content-Type: application/json');

    $post_id = $_POST['post_id'] ?? null;

    if (!$post_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit;
    }

    // Delete comments
    $stmt = $conn->prepare("DELETE FROM community_comments WHERE post_id = ?");
    $stmt->execute([$post_id]);

    // Delete image
    $stmt = $conn->prepare("SELECT image FROM community_posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $image = $stmt->fetchColumn();
    if ($image && file_exists("../" . $image)) {
        unlink("../" . $image);
    }

    // Delete post
    $stmt = $conn->prepare("DELETE FROM community_posts WHERE id = ?");
    $stmt->execute([$post_id]);

    // Update count
    $stmt = $conn->query("SELECT COUNT(*) FROM community_posts");
    $newCount = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'new_count' => $newCount]);
    exit;
}

// === Add Regular Post (by admin) ===
if (isset($_POST['add_post'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $image = null;

    if ($title !== "" && $content !== "") {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/community/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $safeName = 'post_' . time() . '.' . $ext;
            $targetPath = $uploadDir . $safeName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $image = 'uploads/community/' . $safeName;
            }
        }

        $admin_id = 'admin';
        $stmt = $conn->prepare("
            INSERT INTO community_posts 
            (user_id, title, content, image, created_at, is_announcement, is_admin_post, poster_type)
            VALUES (?, ?, ?, ?, NOW(), 0, 1, 'admin')
        ");
        $stmt->execute([$admin_id, $title, $content, $image]);

        header("Location: manage_post.php?msg=post_added");
        exit;
    }
}

// === Add Announcement ===
if (isset($_POST['add_announcement'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if ($title !== "" && $content !== "") {
        $admin_id = 'admin';
        $stmt = $conn->prepare("
            INSERT INTO community_posts 
            (user_id, title, content, image, created_at, is_announcement, is_admin_post, poster_type)
            VALUES (?, ?, ?, ?, NOW(), 1, 1, 'admin')
        ");
        $stmt->execute([$admin_id, $title, $content, null]);

        header("Location: manage_post.php?msg=announcement_added");
        exit;
    }
}

// === Fetch Posts ===
$stmt = $conn->prepare("
    SELECT 
        p.*, 
        CASE WHEN p.is_admin_post = 1 THEN 'Admin' ELSE COALESCE(u.name, 'Unknown') END AS author_name,
        CASE WHEN p.is_admin_post = 1 THEN '../uploads/user.png' ELSE COALESCE(u.profile_pic, '../uploads/user.png') END AS profile_pic,
        CASE WHEN p.is_admin_post = 1 THEN 'admin' ELSE 'user' END AS poster_type
    FROM community_posts p
    LEFT JOIN users u ON p.user_id = u.id
    ORDER BY p.is_announcement DESC, p.created_at DESC
");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === Count for Stats ===
$total_posts = count($posts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Manage Posts</title>
<link rel="stylesheet" href="../assets/css/admin/manage_post.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../assets/css/admin/admin_header.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="admin-main">
    <h2> Manage Community Posts</h2>

    <div class="community-stats">
        <div><strong id="postCount"><?= $total_posts ?></strong><br><small>Posts</small></div>
    </div>

    <div class="btn-group" style="display:flex;gap:10px;margin-bottom:15px;">
        <button class="add-btn" id="openPostModal"><i class="fas fa-edit"></i> Add Post</button>
        <button class="add-btn" id="openAnnouncementModal"><i class="fas fa-bullhorn"></i> Add Announcement</button>
    </div>

    <div class="posts-container" id="postsContainer">
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): ?>
                <?php
                    // Determine profile picture path
                    if ($post['poster_type'] === 'admin' || empty($post['profile_pic'])) {
                        $profilePic = '../uploads/user.png';
                    } else {
                        $profilePic = '../uploads/' . $post['profile_pic'];
                    }
                ?>
                <div class="post-card <?= $post['is_announcement'] ? 'announcement-card' : '' ?>" id="post-<?= $post['id'] ?>">
                    <div class="post-header">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <img src="<?= htmlspecialchars($profilePic) ?>?v=<?= time() ?>"
                                alt="Profile" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                            <div>
                                <h3>
                                    <?= htmlspecialchars($post['title']); ?>
                                    <?php if ($post['is_announcement']): ?>
                                        <span class="announcement-badge"><i class="fas fa-bullhorn"></i> Announcement</span>
                                    <?php endif; ?>
                                </h3>
                                <span class="post-meta">
                                    <?= htmlspecialchars($post['author_name'] ?: 'Admin'); ?> • <?= date("M d, Y", strtotime($post['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <p class="post-content"><?= nl2br(htmlspecialchars($post['content'])); ?></p>

                    <?php if (!empty($post['image'])): ?>
                        <div class="post-image">
                            <img src="../<?= htmlspecialchars($post['image']) ?>?v=<?= time() ?>" alt="Post image" style="max-width:300px;border-radius:8px;">
                        </div>
                    <?php endif; ?>

                    <div style="display:flex; gap: 10px; margin-top: 10px;">
                        <button class="delete-btn delete-post-btn" data-id="<?= $post['id']; ?>">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <button class="toggle-comments-btn" data-id="<?= $post['id']; ?>">
                            <i class="fas fa-comments"></i> Comments
                        </button>
                    </div>

                    <!-- COMMENTS SECTION -->
                    <div class="comments-section" id="comments-<?= $post['id']; ?>" style="display:none; margin-top:10px; max-height:150px; overflow-y:auto; border-top:1px solid #eee; padding-top:5px;">
                        <?php
                            $stmt = $conn->prepare("SELECT c.*, u.name AS commenter_name FROM community_comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
                            $stmt->execute([$post['id']]);
                            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="comments-list">
                            <?php foreach($comments as $comment): ?>
                                <div class="comment" style="margin-bottom:5px;">
                                    <strong><?= htmlspecialchars($comment['commenter_name'] ?: 'User'); ?>:</strong>
                                    <?= htmlspecialchars($comment['comment']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- ADMIN COMMENT FORM -->
                        <form class="comment-form" data-post-id="<?= $post['id']; ?>" style="display:flex; gap:5px; margin-top:5px;">
                            <input type="text" name="comment" placeholder="Write a comment..." style="flex:1; padding:5px;" required>
                            <button type="submit" style="padding:5px 10px;">Comment</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-posts">No posts found</p>
        <?php endif; ?>
    </div>


</div>


<!-- COMMENTS MODAL -->
<div class="modal" id="commentsModal">
    <div class="modal-content" style="max-width:600px; width:90%;">
        <h3><i class="fas fa-comments"></i> Comments</h3>
        <div id="modalCommentsList" style="max-height:300px; overflow-y:auto; margin-top:10px; border-top:1px solid #eee; padding-top:5px;">
            <!-- Comments will be loaded here dynamically -->
        </div>
        <form id="modalCommentForm" style="display:flex; gap:5px; margin-top:10px;">
            <input type="text" name="comment" placeholder="Write a comment..." style="flex:1; padding:5px;" required>
            <button type="submit" style="padding:5px 10px;">Comment</button>
        </form>
        <button type="button" class="close-btn" id="closeCommentsModal" style="margin-top:10px;">Close</button>
    </div>
</div>

<!-- === Modal for Regular Post === -->
<div class="modal" id="postModal">
    <div class="modal-content">
        <h3><i class="fas fa-edit"></i> New Post</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Post title" required>
            <textarea name="content" rows="5" placeholder="Write your post here..." required></textarea>
            <input type="file" name="image" accept="image/*">
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="close-btn" id="closePostModal">Cancel</button>
                <button type="submit" name="add_post" class="submit-btn">Post</button>
            </div>
        </form>
    </div>
</div>

<!-- === Modal for Announcement === -->
<div class="modal" id="announcementModal">
    <div class="modal-content">
        <h3><i class="fas fa-bullhorn"></i> New Announcement</h3>
        <form method="POST">
            <input type="text" name="title" placeholder="Announcement title" required>
            <textarea name="content" rows="5" placeholder="Write your announcement here..." required></textarea>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="close-btn" id="closeAnnouncementModal">Cancel</button>
                <button type="submit" name="add_announcement" class="submit-btn">Post</button>
            </div>
        </form>
    </div>
</div>

<script>
// === Modal Logic ===
const postModal = document.getElementById('postModal');
const annModal = document.getElementById('announcementModal');
document.getElementById('openPostModal').onclick = () => postModal.style.display = 'flex';
document.getElementById('closePostModal').onclick = () => postModal.style.display = 'none';
document.getElementById('openAnnouncementModal').onclick = () => annModal.style.display = 'flex';
document.getElementById('closeAnnouncementModal').onclick = () => annModal.style.display = 'none';
window.onclick = (e) => {
    if (e.target === postModal) postModal.style.display = 'none';
    if (e.target === annModal) annModal.style.display = 'none';
};

document.addEventListener("click", e => {
    if (e.target.closest(".delete-post-btn")) {
        const btn = e.target.closest(".delete-post-btn");
        const postId = btn.dataset.id;

        Swal.fire({
            title: 'Delete Post?',
            text: "Are you sure you want to delete this post?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#666',
            confirmButtonText: 'Yes, delete it',
            customClass: {
                popup: 'swal2-mini'
            }
        }).then(result => {
            if (result.isConfirmed) {
                fetch("", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({ action: "delete_post", post_id: postId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`post-${postId}`).remove();
                        document.getElementById("postCount").textContent = data.new_count;

                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Post deleted successfully',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true,
                            background: '#fff',
                            color: '#333',
                            didOpen: (toast) => {
                                const progressBar = toast.querySelector('.swal2-timer-progress-bar');
                                if(progressBar) progressBar.style.background = '#4caf50'; // green progress bar
                            }
                        });

                        if (!document.querySelector(".post-card")) {
                            const noPosts = document.createElement("p");
                            noPosts.className = "no-posts";
                            noPosts.textContent = "No posts found";
                            document.getElementById("postsContainer").appendChild(noPosts);
                        }
                    } else {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: data.message || "Failed to delete post",
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Network error occurred',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                });
            }
        });
    }
});


const commentsModal = document.getElementById('commentsModal');
const modalCommentsList = document.getElementById('modalCommentsList');
const modalCommentForm = document.getElementById('modalCommentForm');
let currentPostId = null;

// Open comments modal
document.querySelectorAll('.toggle-comments-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        currentPostId = btn.dataset.id;
        modalCommentsList.innerHTML = 'Loading...';
        commentsModal.style.display = 'flex';

        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ fetch_comments: 1, post_id: currentPostId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                modalCommentsList.innerHTML = '';
                data.comments.forEach(c => {
                    const div = document.createElement('div');
                    div.className = 'comment';
                    div.style.marginBottom = '5px';
                    div.innerHTML = `<strong>${c.commenter_name}:</strong> ${c.comment}`;
                    modalCommentsList.appendChild(div);
                });
            } else {
                modalCommentsList.innerHTML = 'No comments yet.';
            }
        });
    });
});

// Close comments modal
document.getElementById('closeCommentsModal').onclick = () => commentsModal.style.display = 'none';
window.onclick = e => { if (e.target === commentsModal) commentsModal.style.display = 'none'; };

// Submit comment via modal
modalCommentForm.addEventListener('submit', e => {
    e.preventDefault();
    const commentInput = modalCommentForm.querySelector('input[name="comment"]');
    const comment = commentInput.value.trim();
    if (!comment) return;

    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ add_comment: 1, post_id: currentPostId, comment })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const div = document.createElement('div');
            div.className = 'comment';
            div.style.marginBottom = '5px';
            div.innerHTML = `<strong>Admin:</strong> ${comment}`;
            modalCommentsList.appendChild(div);
            commentInput.value = '';
            modalCommentsList.scrollTop = modalCommentsList.scrollHeight;
        } else {
            alert('Failed to post comment');
        }
    });
});



</script>
<style>
/* 🔹 Make SweetAlert box smaller */
.swal2-mini {
    width: 300px !important;
    padding: 15px !important;
    font-size: 14px !important;
    border-radius: 10px !important;
}
.swal2-mini .swal2-title {
    font-size: 16px !important;
}
.swal2-mini .swal2-html-container {
    font-size: 13px !important;
}
.swal2-mini .swal2-actions button {
    padding: 5px 10px !important;
    font-size: 13px !important;
}
/* Common button styling */
.post-card button {
    display: inline-flex;
    align-items: center;
    gap: 5px; /* space between icon and text */
    padding: 5px 5px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

/* Comments button - smaller size */
.toggle-comments-btn {
    background-color: #f1f1f1;
    color: #333;
    padding: 3px 8px;      /* smaller padding */
    font-size: 4px;       /* smaller text */
    border-radius: 5px;    /* slightly smaller corners */
    gap: 3px;              /* smaller space between icon and text */
}
.toggle-comments-btn i {
    color: #007bff; /* icon color */
    font-size: 10px;   
}

.toggle-comments-btn:hover {
    background-color: #007bff;
    color: #fff;
    border-color: #007bff;
}

.toggle-comments-btn:hover i {
    color: #fff;
}

/* Delete button */
.delete-post-btn {
    background-color: #f8d7da;
    color: #721c24;
}

.delete-post-btn i {
    color: #c82333; /* icon color */
    font-size: 16px;
}

.delete-post-btn:hover {
    background-color: #c82333;
    color: #fff;
    border-color: #c82333;
}

.delete-post-btn:hover i {
    color: #fff;
}

</style>

</body>
</html>
