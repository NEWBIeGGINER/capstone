<?php
require_once 'components/connect.php';
require_once 'components/auth.php'; // $user_id
session_start(); // Required for session tracking

// ================= UPDATE LAST ACTIVITY =================
if ($user_id) {
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = :uid");
    $stmt->execute([':uid' => $user_id]);
}

// ================= INITIALIZE SESSION VIEW TRACKING =================
if (!isset($_SESSION['viewed_posts'])) {
    $_SESSION['viewed_posts'] = [];
}

// ================= HANDLE NEW COMMENTS/REPLIES =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ===== Add comment/reply =====
    if ($action === 'add_comment') {
        $post_id   = $_POST['post_id'] ?? null;
        $parent_id = $_POST['parent_id'] ?? null;
        $comment   = trim($_POST['comment'] ?? '');

        if ($post_id && $comment && $user_id) {
            $stmt = $conn->prepare("SELECT id FROM community_posts WHERE id = :pid AND status='active'");
            $stmt->execute([':pid' => $post_id]);
            if ($stmt->fetchColumn()) {
                $stmt = $conn->prepare("
                    INSERT INTO community_comments (post_id, user_id, parent_id, comment, created_at)
                    VALUES (:post_id, :user_id, :parent_id, :comment, NOW())
                ");
                $stmt->execute([
                    ':post_id'   => $post_id,
                    ':user_id'   => $user_id,
                    ':parent_id' => $parent_id ?: null,
                    ':comment'   => $comment
                ]);
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        }
    }

    // ===== Create or Edit Post =====
    if ($action === 'create_post' || $action === 'edit_post') {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $imagePath = null;

        // Fetch user status/role
        $stmt = $conn->prepare("SELECT status, role FROM users WHERE id = :uid LIMIT 1");
        $stmt->execute([':uid' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['status'] === 'banned') {
            header("Location: community.php?error=banned");
            exit;
        }

        $isAdminPost = ($user['role'] === 'admin') ? 1 : 0;

        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = "uploads/community/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $filename = time() . "_" . basename($_FILES['image']['name']);
            $target = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $imagePath = $target;
            }
        }

        if ($action === 'create_post') {
            if ($title && $content && $user_id) {
                $stmt = $conn->prepare("
                    INSERT INTO community_posts 
                    (user_id, title, content, image, created_at, score, views, status, is_admin_post, is_announcement)
                    VALUES (:user_id, :title, :content, :image, NOW(), 0, 0, 'active', :is_admin_post, 0)
                ");
                $stmt->execute([
                    ':user_id'       => $user_id,
                    ':title'         => $title,
                    ':content'       => $content,
                    ':image'         => $imagePath,
                    ':is_admin_post' => $isAdminPost
                ]);
                header("Location: community.php?success=1");
                exit;
            }
        } elseif ($action === 'edit_post') {
            $postId = $_POST['post_id'] ?? null;
            if ($postId && $user_id) {
                // Only allow owner to edit
                $stmt = $conn->prepare("
                    UPDATE community_posts 
                    SET title = :title, content = :content" . ($imagePath ? ", image = :image" : "") . "
                    WHERE id = :id AND user_id = :uid
                ");
                $params = [
                    ':title' => $title,
                    ':content' => $content,
                    ':id' => $postId,
                    ':uid' => $user_id
                ];
                if ($imagePath) $params[':image'] = $imagePath;
                $stmt->execute($params);

                header("Location: community.php?success=edited");
                exit;
            }
        }
    }

    // ===== Handle view increment via AJAX =====
    if ($action === 'increment_view') {
        $post_id = $_POST['post_id'] ?? '';
        if ($post_id && $user_id) {
            if (!in_array($post_id, $_SESSION['viewed_posts'])) {
                $stmt = $conn->prepare("UPDATE community_posts SET views = views + 1 WHERE id = :post_id");
                $stmt->execute([':post_id' => $post_id]);
                $_SESSION['viewed_posts'][] = $post_id;
            }
            $stmt = $conn->prepare("SELECT views FROM community_posts WHERE id = :post_id");
            $stmt->execute([':post_id' => $post_id]);
            $views = (int)$stmt->fetchColumn();
            echo json_encode(['success' => true, 'views' => $views]);
            exit;
        }
    }
}


// ================= ENSURE 'is_announcement' COLUMN EXISTS =================
try {
    $conn->query("SELECT is_announcement FROM community_posts LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("ALTER TABLE community_posts ADD COLUMN is_announcement TINYINT(1) NOT NULL DEFAULT 0");
}

// ================= FETCH LATEST ANNOUNCEMENT =================
$stmt = $conn->prepare("
    SELECT title, content, created_at 
    FROM community_posts 
    WHERE is_announcement = 1 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute();
$announcement = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

// ================= FETCH ALL REGULAR POSTS =================
$stmt = $conn->query("
    SELECT 
        p.*,
        COALESCE(u.name, 'Unknown') AS name,
        CASE 
            WHEN u.profile_pic IS NULL OR u.profile_pic = '' THEN 'uploads/user.png'
            ELSE u.profile_pic
        END AS profile_pic,
        CASE
            WHEN p.is_admin_post = 1 OR p.user_id = 'admin' THEN 'admin'
            ELSE 'user'
        END AS poster_type,
        (SELECT COUNT(*) FROM community_comments c WHERE c.post_id = p.id) AS comment_count,
        (SELECT COUNT(*) FROM community_votes v WHERE v.post_id = p.id AND v.upvote = 1) AS upvote_count,
        (SELECT COUNT(*) FROM community_votes v WHERE v.post_id = p.id AND v.downvote = 1) AS downvote_count
    FROM community_posts p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.status = 'active' AND p.is_announcement = 0
    ORDER BY p.created_at DESC
");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================= CLEANUP POSTS =================
foreach ($posts as &$post) {
    $post['profile_pic'] = (!empty($post['profile_pic']) && file_exists($post['profile_pic']))
        ? $post['profile_pic']
        : 'uploads/user.png';
    $post['upvote_count']   = (int)($post['upvote_count'] ?? 0);
    $post['downvote_count'] = (int)($post['downvote_count'] ?? 0);
    $post['comment_count']  = (int)($post['comment_count'] ?? 0);

    // ================= FETCH COMMENTS & REPLIES FOR POST =================
    $comments_stmt = $conn->prepare("
        SELECT c.*, COALESCE(u.name, 'Admin') AS name
        FROM community_comments c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ");
    $comments_stmt->execute([$post['id']]);
    $all_comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize comments by parent_id for easy display
    $comments_tree = [];
    $replies_map = [];

    foreach ($all_comments as $comment) {
        $comment['replies'] = [];
        if ($comment['parent_id'] === null) {
            $comments_tree[$comment['id']] = $comment;
        } else {
            $replies_map[] = $comment;
        }
    }

    foreach ($replies_map as $reply) {
        if (isset($comments_tree[$reply['parent_id']])) {
            $comments_tree[$reply['parent_id']]['replies'][] = $reply;
        }
    }

    $post['comments'] = $comments_tree;
}
unset($post);

// ================= FETCH VERIFIED NON-BANNED USERS =================
$total_members_stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM users 
    WHERE is_verified = 1 
      AND status != 'banned'
      AND role = 'user'
");
$total_members_stmt->execute();
$total_members = (int)$total_members_stmt->fetchColumn();

// ================= FETCH ONLINE USERS =================
$online_stmt = $conn->prepare("
    SELECT id 
    FROM users 
    WHERE last_activity >= (NOW() - INTERVAL 5 MINUTE)
      AND is_verified = 1
      AND status != 'banned'
      AND role = 'user'
");
$online_stmt->execute();
$online_ids = $online_stmt->fetchAll(PDO::FETCH_COLUMN);
$online_count = count($online_ids);

// ================= FIX OLD ADMIN POSTS =================
$conn->exec("UPDATE community_posts SET is_admin_post = 1 WHERE user_id = 'admin'");
?>





<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Petcare | Community</title>
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/community.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    .admin-badge {
    background-color: #dc2626;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 6px;
    margin-left: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.admin-badge i {
    font-size: 12px;
}

.admin-badge:hover {
    background-color: #b91c1c;
    cursor: default;
}
/* Modal Overlay */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background-color: rgba(0,0,0,0.5);
}
.profile-posts {
    overflow-y: auto;
    flex: 1;
    padding: 16px;
    max-height: calc(80vh - 150px); /* adjust based on header size */
}

.profile-post {
    border-bottom: 1px solid #eee;
    padding: 10px 0;
}

.profile-post:last-child {
    border-bottom: none;
}


.profile-post strong {
    display: block;
    font-size: 16px;
    margin-bottom: 4px;
}

.profile-post p {
    font-size: 14px;
    color: #555;
    margin: 0 0 4px;
}

.profile-post small {
    font-size: 12px;
    color: #999;
}

/* Scrollbar styling */
.profile-posts::-webkit-scrollbar {
    width: 6px;
}

.profile-posts::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.2);
    border-radius: 3px;
}

.profile-posts::-webkit-scrollbar-track {
    background: transparent;
}

.modal-content {
    background-color: #fff;
    margin: 50px auto;
    padding: 20px;
    border-radius: 12px;
    width: 90%;
    max-width: 700px;
}
</style>
</head>
<body>

<?php include 'components/user_header.php'?> 

    <!-- Community Header -->
    <div class="community-header">
        <div class="community-title">
            <div class="community-icon">
                <i class="fas fa-paw"></i>
            </div>
            <div>
                <h2>PetCare</h2>
                <div style="font-size: 14px; color: var(--text-secondary); margin-top: 4px;">
                    A community for pet lovers to share stories, ask questions, and get advice
                </div>
            </div>
            <div class="community-stats">
                <div><strong><?= count($posts) ?></strong><br><small>Posts</small></div>
                <div><strong><?= $total_members ?></strong><br><small>Members</small></div>
                <div><strong><?= $online_count ?></strong><br><small>Online</small></div>
            </div>

        </div>

        <!-- 🔶 Admin Announcement Banner -->
        <?php if ($announcement): ?>
        <div class="announcement-banner">
            <i class="fas fa-bullhorn"></i>
            <div class="announcement-text">
                <strong><?= htmlspecialchars($announcement['title']); ?></strong>   
                <p><?= nl2br(htmlspecialchars($announcement['content'])); ?></p>
                <small>Posted on <?= date("M d, Y", strtotime($announcement['created_at'])); ?></small>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="main-container">
       <div class="content-area">
            <!-- Create Post -->
            <div class="create-post-container">
                <button class="btn-create" onclick="openModal()">
                    <i class="fas fa-plus" style="margin-right: 8px;"></i>
                    Create a post about your pet...
                </button>
            </div>

            <!-- Posts -->
            <?php foreach($posts as $index => $post): ?>
            <div class="post-card" data-post-id="<?= $post['id'] ?>">
                <div class="post-content">
                    <!-- Post Header -->
                    <div class="post-header">
                       <div class="post-author-pic-wrapper" style="position: relative; display: inline-block;">
                            <?php
                                $profileImage = !empty($post['profile_pic']) && file_exists($post['profile_pic'])
                                    ? $post['profile_pic']
                                    : 'uploads/user.png';
                                $isOnline = in_array($post['user_id'], $online_ids);
                            ?>
                            <img src="<?= htmlspecialchars($profileImage) ?>" 
                                alt="Profile Picture" 
                                class="post-author-pic" 
                                style="cursor:pointer;"
                                data-user-id="<?= $post['user_id'] ?>">

                            <?php if($isOnline): ?>
                                <span style="
                                    position: absolute;
                                    bottom: 4px;
                                    right: 4px;
                                    width: 12px;
                                    height: 12px;
                                    background: #4CAF50;
                                    border: 2px solid #fff;
                                    border-radius: 50%;
                                    display: inline-block;
                                " title="Online"></span>
                            <?php endif; ?>
                        </div>

                        <span class="post-community">r/PetCare</span>
                        <span>•</span>
                        <span>Posted by</span>
                        <span class="post-author" style="cursor:pointer;" data-user-id="<?= $post['user_id'] ?>">
                            <?php if ($post['poster_type'] === 'admin'): ?>
                                u/Admin
                                <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
                            <?php else: ?>
                                u/<?= htmlspecialchars($post['name'] ?: 'Unknown') ?>
                            <?php endif; ?>
                        </span>
                        <span><?= date("j M Y", strtotime($post['created_at'])) ?></span>

                        <?php if ($user_id == $post['user_id']): ?>
                            <div class="post-menu">
                                <button class="menu-btn"><i class="fas fa-ellipsis-h"></i></button>
                                <div class="menu-dropdown">
                                    <button class="edit-post-btn" 
                                            data-id="<?= $post['id'] ?>" 
                                            data-title="<?= htmlspecialchars($post['title'], ENT_QUOTES) ?>" 
                                            data-content="<?= htmlspecialchars($post['content'], ENT_QUOTES) ?>" 
                                            data-image="<?= !empty($post['image']) ? htmlspecialchars($post['image'], ENT_QUOTES) : '' ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <button class="delete-post-btn" data-id="<?= $post['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                        <?php endif; ?>
                    </div>


                    <!-- Post Title -->
                    <div class="post-title" onclick='openPostModal(<?= htmlspecialchars(json_encode([
                        "title" => $post['title'],
                        "name" => $post['name'],
                        "created_at" => date("M d, Y h:i A", strtotime($post['created_at'])),
                        "content" => $post['content'],
                        "image" => $post['image'],
                        "score" => $post['score'],
                        "comment_count" => $post['comment_count'],
                        "views" => $post['views']
                    ]), ENT_QUOTES) ?>)'>
                        <?= htmlspecialchars($post['title']) ?>
                    </div>

                    <!-- Post Snippet -->
                    <div class="post-snippet">
                        <?php 
                        $snippet = htmlspecialchars($post['content']);
                        echo strlen($snippet) > 200 ? substr($snippet, 0, 200) . "..." : $snippet;
                        ?>
                    </div>

                    <!-- Post Image -->
                    <?php if (!empty($post['image']) && file_exists($post['image'])): ?>
                    <img src="<?= htmlspecialchars($post['image']) ?>" 
                        class="post-image"
                        onclick='openPostModal(<?= htmlspecialchars(json_encode([
                            "title" => $post['title'],
                            "name" => $post['name'],
                            "created_at" => date("M d, Y h:i A", strtotime($post['created_at'])),
                            "content" => $post['content'],
                            "image" => $post['image'],
                            "score" => $post['score'],
                            "comment_count" => $post['comment_count'],
                            "views" => $post['views']
                        ]), ENT_QUOTES) ?>)'>
                    <?php endif; ?>

                    <!-- Post Actions -->
                    <div class="post-actions">
                        <div class="vote-buttons">
                            <button class="vote-btn upvote-btn <?= $user_vote === 'upvote' ? 'voted' : '' ?>" 
                                    data-id="<?= $post['id'] ?>" data-vote-type="upvote">
                                <i class="fas fa-thumbs-up"></i>
                                <span class="upvote-count"><?= (int)$post['upvote_count'] ?></span>
                            </button>

                            <button class="vote-btn downvote-btn <?= $user_vote === 'downvote' ? 'voted' : '' ?>" 
                                    data-id="<?= $post['id'] ?>" data-vote-type="downvote">
                                <i class="fas fa-thumbs-down"></i>
                                <span class="downvote-count"><?= (int)$post['downvote_count'] ?></span>
                            </button>
                        </div>

                        <button class="action-btn comment-btn" data-post-id="<?= $post['id'] ?>">
                            <i class="fas fa-comment"></i>
                            <?= (int)$post['comment_count'] ?> Comments
                        </button>

                        <button class="action-btn view-btn" data-post-id="<?= $post['id'] ?>">
                            <i class="fas fa-eye"></i> <span class="view-count"><?= (int)$post['views'] ?></span>
                        </button>
                    </div>

                    <div class="comments-section" id="comments-<?= $post['id'] ?>">
                        <h4><i class="fas fa-comments" style="margin-right: 8px;"></i>Comments</h4>
                        <div class="comments-list scrollable-comments">

                            <?php foreach($post['comments'] as $comment): ?>
                                <div class="comment" id="comment-<?= $comment['id'] ?>">
                                    <strong><?= htmlspecialchars($comment['name']) ?></strong>
                                    <small><?= date("M d, Y h:i A", strtotime($comment['created_at'])) ?></small>
                                    <p><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>

                                    <div class="comment-actions">
                                        <button class="reply-btn" data-id="<?= $comment['id'] ?>" data-post="<?= $post['id'] ?>">Reply</button>
                                        <?php if($user_id == $comment['user_id']): ?>
                                            <button class="delete-comment-btn" data-id="<?= $comment['id'] ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Replies -->
                                    <?php if(!empty($comment['replies'])): ?>
                                        <div class="replies" style="margin-left: 24px;">
                                            <?php foreach($comment['replies'] as $reply): ?>
                                                <div class="comment reply" id="comment-<?= $reply['id'] ?>">
                                                    <strong><?= htmlspecialchars($reply['name']) ?></strong>
                                                    <small><?= date("M d, Y h:i A", strtotime($reply['created_at'])) ?></small>
                                                    <p><?= nl2br(htmlspecialchars($reply['comment'])) ?></p>
                                                    <?php if($user_id == $reply['user_id']): ?>
                                                        <button class="delete-comment-btn" data-id="<?= $reply['id'] ?>">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            <?php endforeach; ?>

                        </div>

                        <?php if($user_id): ?>
                            <!-- Replying indicator -->
                            <div class="replying-to" id="replying-to-<?= $post['id'] ?>" style="display:none; margin-bottom:8px; font-size:14px; color:var(--text-secondary);">
                                Replying to: <span class="reply-name"></span>
                                <button type="button" class="cancel-reply-btn" style="margin-left:8px;">Cancel</button>
                            </div>

                            <textarea class="new-comment" data-post="<?= $post['id'] ?>" rows="2" placeholder="What are your thoughts?"></textarea>
                            <button class="add-comment-btn" data-post="<?= $post['id'] ?>">Comment</button>
                        <?php else: ?>
                            <p style="text-align:center;padding:16px;color:var(--text-secondary);">
                                <a href="signin.php" style="color:var(--reddit-blue);">Log in</a> to join the conversation
                            </p>
                        <?php endif; ?>
                    </div>



                </div>
            </div>
            <?php endforeach; ?>
        </div>


        <!-- Facebook-style Comments Modal -->
        <div id="fbCommentsModal" class="modal">
            <div class="modal-content fb-comments">
                <span class="close-btn" onclick="closeFBCommentsModal()">&times;</span>

                <div class="comments-container">
                    <!-- Replying to indicator -->
                    <div id="fb-modal-replying-to" style="display:none; margin-bottom:8px;">
                        Replying to <span class="reply-name"></span>
                        <button class="cancel-reply-btn" style="margin-left:8px;">Cancel</button>
                    </div>

                    <div id="fb-modal-comments-list"></div>
                </div>

                <?php if($user_id): ?>
                <div class="fb-comment-input">
                    <textarea id="fb-modal-new-comment" data-post="" rows="2" placeholder="Write a comment..."></textarea>
                    <button id="fb-modal-add-comment">Comment</button>
                </div>
                <?php else: ?>
                <p style="text-align:center;padding:16px;color:var(--text-secondary);">
                    <a href="signin.php" style="color:var(--reddit-blue);">Log in</a> to join the conversation
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- User Profile Modal -->
        <div id="userProfileModal" class="modal">
            <div class="modal-content" style="max-width: 600px; width: 90%; height: 80vh; position: relative; display: flex; flex-direction: column; border-radius: 12px; background: #fff;">
                <span class="close-btn" onclick="closeProfileModal()" 
                    style="position: absolute; top: 12px; right: 16px; cursor: pointer; font-size: 24px; font-weight: bold;">&times;</span>
                
                <!-- Profile Header -->
                <div class="profile-header" style="text-align:center; padding: 20px; border-bottom: 1px solid #ddd;">
                    <img id="modalProfilePic" src="uploads/user.png" 
                        style="width:100px;height:100px;border-radius:50%; margin-bottom:10px; object-fit:cover;">
                    <h3 id="modalProfileName" style="margin:0; font-size: 18px; line-height:1.2;">
                        u/username
                        <span id="modalAdminBadge" class="admin-badge" style="display:none; margin-left:6px;">
                            <i class="fas fa-shield-alt"></i> Admin
                        </span>
                    </h3>
                </div>

                <!-- User Posts -->
                <div class="profile-posts" id="modalProfilePosts" 
                    style="overflow-y:auto; flex:1; padding: 16px;">
                    <!-- Posts will be injected via AJAX -->
                </div>
            </div>
        </div>





        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                    About Community
                </div>
                <div class="sidebar-content">
                    <p style="font-size: 14px; line-height: 1.4; color: var(--text-secondary); margin-bottom: 16px;">
                        Welcome to PetCare! A community dedicated to helping pet owners provide the best care for their furry, feathered, and scaled friends.
                    </p>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-secondary);">
                        <span>Created Jan 1, 2024</span>
                    </div>
                </div>
            </div>

            <div class="sidebar-card">
                <div class="sidebar-header">
                    <i class="fas fa-rules" style="margin-right: 8px;"></i>
                    Community Rules
                </div>
                <div class="sidebar-content">
                    <ol class="community-rules">
                        <li>Be respectful and kind</li>
                        <li>No spam or self-promotion</li>
                        <li>Pet safety comes first</li>
                        <li>Use appropriate post flair</li>
                        <li>No medical advice</li>
                    </ol>
                </div>
            </div>

                <!-- ✅ Sidebar -->
                <div class="sidebar-card"> 
                    <div class="sidebar-header">
                        <i class="fas fa-chart-line" style="margin-right: 8px;"></i>
                        Trending Topics
                    </div>
                    <div class="sidebar-content">
                        <div class="trending-tags" style="display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                            <div class="trending-tag" data-tag="DogTraining" style="padding: 6px 0; border-bottom: 1px solid var(--reddit-light-gray); cursor: pointer;">
                                #DogTraining
                            </div>
                            <div class="trending-tag" data-tag="CatHealth" style="padding: 6px 0; border-bottom: 1px solid var(--reddit-light-gray); cursor: pointer;">
                                #CatHealth
                            </div>
                            <div class="trending-tag" data-tag="PetPhotography" style="padding: 6px 0; border-bottom: 1px solid var(--reddit-light-gray); cursor: pointer;">
                                #PetPhotography
                            </div>
                            <div class="trending-tag" data-tag="AdoptDontShop" style="padding: 6px 0; cursor: pointer;">
                                #AdoptDontShop
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Post Modal -->
    <div id="postModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2 id="modal-title"><i class="fas fa-plus-circle" style="margin-right: 8px; color: var(--reddit-blue);"></i>Create a post</h2>
            <form id="postForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create_post" id="postAction">
                <input type="hidden" name="post_id" value="" id="postId">
                <input type="text" name="title" placeholder="An interesting title" required id="postTitle">
                <textarea name="content" rows="6" placeholder="What's on your mind? Share your pet story, ask for advice, or start a discussion..." required id="postContent"></textarea>
                <input type="file" name="image" accept="image/*" style="margin-bottom: 20px;" id="postImage">
                <button type="submit" id="postSubmitBtn">
                    <i class="fas fa-paper-plane" style="margin-right: 8px;"></i>
                    Post
                </button>
            </form>
        </div>
    </div>


    <!-- Post View Modal -->
    <div id="postViewModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close-btn" onclick="closePostModal()">&times;</span>
            <div class="post-header" style="margin-bottom: 16px;">
                <span class="post-community">PetCare</span>
                <span>•</span>
                <span id="modal-meta"></span>
            </div>
            <h2 id="modal-title" style="margin-bottom: 16px;"></h2>
            <img id="modal-image" src="" style="max-width:100%;border-radius:8px;margin:16px 0;display:none;">
            <div id="modal-content" style="font-size:16px;line-height:1.6;color:var(--text-primary);margin-bottom:20px;"></div>
            <div style="display:flex;gap:20px;align-items:center;padding:16px 0;border-top:1px solid var(--reddit-light-gray);">
                <span style="display:flex;align-items:center;gap:4px;color:var(--text-secondary);">
                    <i class="fas fa-arrow-up" style="color:var(--upvote-color);"></i>
                    <span id="modal-score">0</span>
                </span>
                <span style="display:flex;align-items:center;gap:4px;color:var(--text-secondary);">
                    <i class="fas fa-comment"></i>
                    <span id="modal-comments-count">0</span>
                </span>
                <span style="display:flex;align-items:center;gap:4px;color:var(--text-secondary);">
                    <i class="fas fa-eye"></i>
                    <span id="modal-views">0</span>
                </span>
            </div>
        </div>
    </div>

    <script>
      const user_id = <?= json_encode($user_id ?? null) ?>;
      document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const postId = btn.getAttribute('data-post-id');
                    const span = btn.querySelector('.view-count');

                    fetch('', { // same file
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=increment_view&post_id=' + encodeURIComponent(postId)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && span) {
                            span.textContent = data.views;
                        }
                    });
                });
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            // When clicking a Reply button
            document.querySelectorAll('.reply-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const commentId = btn.getAttribute('data-id');
                    const postId = btn.getAttribute('data-post');
                    const postSection = document.getElementById(`comments-${postId}`);
                    const replyingDiv = postSection.querySelector(`#replying-to-${postId}`);
                    const textarea = postSection.querySelector('.new-comment');

                    // Find comment name
                    const commentName = btn.closest('.comment').querySelector('strong').textContent;

                    // Show replying indicator
                    replyingDiv.style.display = 'block';
                    replyingDiv.querySelector('.reply-name').textContent = commentName;

                    // Set parent ID on main textarea
                    textarea.setAttribute('data-parent', commentId);
                    textarea.focus();
                });
            });

            // Cancel reply
            document.querySelectorAll('.cancel-reply-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const replyingDiv = btn.closest('.replying-to');
                    const postId = replyingDiv.id.split('-').pop();
                    const postSection = document.getElementById(`comments-${postId}`);
                    const textarea = postSection.querySelector('.new-comment');

                    // Hide indicator and clear parent
                    replyingDiv.style.display = 'none';
                    textarea.removeAttribute('data-parent');
                    textarea.value = '';
                });
            });

            // Submit comment or reply
            document.querySelectorAll('.add-comment-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const postId = btn.getAttribute('data-post');
                    const postSection = document.getElementById(`comments-${postId}`);
                    const textarea = postSection.querySelector('.new-comment');
                    const content = textarea.value.trim();
                    const parentId = textarea.getAttribute('data-parent') || null;

                    if (!content) return;

                    // Send via AJAX to same page
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=add_comment&post_id=${postId}&parent_id=${parentId}&comment=${encodeURIComponent(content)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Reset
                            textarea.value = '';
                            textarea.removeAttribute('data-parent');
                            const replyingDiv = postSection.querySelector(`#replying-to-${postId}`);
                            replyingDiv.style.display = 'none';

                            // Reload page or dynamically add comment
                            location.reload();
                        } else {
                            alert(data.error || 'Failed to submit comment.');
                        }
                    });
                });
            });
        });


        document.addEventListener('click', function(e){
            if(e.target.closest('.edit-post-btn')){
                const btn = e.target.closest('.edit-post-btn');
                const postId = btn.dataset.id;
                const title = btn.dataset.title;
                const content = btn.dataset.content;

                // Fill modal
                document.getElementById('modal-title').innerHTML = '<i class="fas fa-edit"></i> Edit Post';
                document.getElementById('postAction').value = 'edit_post';
                document.getElementById('postId').value = postId;
                document.getElementById('postTitle').value = title;
                document.getElementById('postContent').value = content;

                // Show modal
                document.getElementById('postModal').style.display = 'block';
            }
        });




    </script>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
    <script src="assets/js/community.js"></script>
</body>
</html>