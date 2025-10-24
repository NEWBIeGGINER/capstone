<?php
require_once 'components/connect.php';
require_once 'components/auth.php';

$user_id_ajax = $_GET['user_id'] ?? null;
if (!$user_id_ajax) exit('Invalid user ID');

// ================= FETCH PROFILE =================
$profile_user = null;
$profile_posts = [];

// Fetch from users table
$stmt = $conn->prepare("SELECT id, name, profile_pic, role FROM users WHERE id=:uid");
$stmt->execute([':uid'=>$user_id_ajax]);
$profile_user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($profile_user) {
    $profile_user['poster_type'] = $profile_user['role'] === 'admin' ? 'admin' : 'user';

    // ✅ Always get latest profile_pic & role with JOIN
    $stmt = $conn->prepare("
        SELECT 
            p.*,
            u.profile_pic,
            u.role AS user_role
        FROM community_posts p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.status='active' AND p.user_id=:uid
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([':uid' => $user_id_ajax]);
    $profile_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // fallback for admin posts stored with user_id='admin'
    $profile_user = [
        'id' => 'admin',
        'name' => 'Admin',
        'profile_pic' => 'uploads/user.png',
        'poster_type' => 'admin'
    ];

    $stmt = $conn->prepare("
        SELECT 
            p.*, 
            'uploads/user.png' AS profile_pic, 
            'admin' AS user_role
        FROM community_posts p
        WHERE p.status='active' AND p.user_id='admin'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $profile_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ================= FALLBACK PROFILE PICTURE =================
if (empty($profile_user['profile_pic']) || !file_exists($profile_user['profile_pic'])) {
    $profile_user['profile_pic'] = 'uploads/user.png';
}

// ================= FALLBACK POSTS PROFILE PICS =================
foreach ($profile_posts as &$p) {
    if (empty($p['profile_pic']) || !file_exists($p['profile_pic'])) {
        $p['profile_pic'] = 'uploads/user.png';
    }
}
unset($p);
?>

<!-- Profile Header -->
<div class="profile-header">
    <img src="<?= htmlspecialchars($profile_user['profile_pic']) ?>" alt="Profile Picture">
    <h3 data-role="<?= $profile_user['poster_type'] === 'admin' ? 'admin' : 'user' ?>">
        <?= $profile_user['poster_type'] === 'admin' ? 'u/Admin' : 'u/' . htmlspecialchars($profile_user['name']) ?>
        <?php if ($profile_user['poster_type'] === 'admin'): ?>
            <span class="admin-badge" id="modalAdminBadge">
                <i class="fas fa-shield-alt"></i> Admin
            </span>
        <?php endif; ?>
    </h3>
</div>

<!-- User/Admin Posts -->
<div id="modalProfilePosts">
<?php if (!empty($profile_posts)): ?>
    <?php foreach($profile_posts as $post): ?>
        <div class="post-content <?= ($post['user_role'] ?? '') === 'admin' ? 'admin-post' : '' ?>">
            <div class="post-header">
                <img src="<?= htmlspecialchars($post['profile_pic']) ?>" class="post-author-pic" data-user-id="<?= $user_id_ajax ?>">
                <span class="post-community">r/PetCare</span>
                <span>•</span>
                <span><?= date("j M Y", strtotime($post['created_at'])) ?></span>
                <?php if (($post['user_role'] ?? '') === 'admin'): ?>
                    <span class="admin-badge">Admin</span>
                <?php endif; ?>
            </div>
            <div class="post-title"><?= htmlspecialchars($post['title']) ?></div>
            <div class="post-snippet">
                <?= htmlspecialchars(strlen($post['content']) > 200 ? substr($post['content'], 0, 200) . '...' : $post['content']) ?>
            </div>
            <?php if (!empty($post['image']) && file_exists($post['image'])): ?>
                <img src="<?= htmlspecialchars($post['image']) ?>" class="post-image">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="no-posts">No posts yet.</div>
<?php endif; ?>
</div>
