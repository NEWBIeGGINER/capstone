<?php 
require_once 'components/connect.php';
require_once 'components/auth.php'; // may $user_id

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {
    switch ($action) {

        // ========================= VOTE =========================
        case 'vote':
            if (!$user_id) throw new Exception('Please log in first');

            $post_id = (int)($_POST['post_id'] ?? 0);
            $vote_type = $_POST['vote_type'] ?? '';
            if (!in_array($vote_type, ['upvote', 'downvote'])) throw new Exception('Invalid vote type');

            $other_type = $vote_type === 'upvote' ? 'downvote' : 'upvote';

            $stmt = $conn->prepare("SELECT * FROM community_votes WHERE user_id = :user_id AND post_id = :post_id");
            $stmt->execute([':user_id'=>$user_id, ':post_id'=>$post_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                if ($existing[$vote_type] == 1) {
                    $stmt = $conn->prepare("UPDATE community_votes SET $vote_type = 0 WHERE id = :id");
                    $stmt->execute([':id'=>$existing['id']]);
                    $user_vote = null;
                } else {
                    $stmt = $conn->prepare("UPDATE community_votes SET $vote_type = 1, $other_type = 0 WHERE id = :id");
                    $stmt->execute([':id'=>$existing['id']]);
                    $user_vote = $vote_type;
                }
            } else {
                $up = $vote_type === 'upvote' ? 1 : 0;
                $down = $vote_type === 'downvote' ? 1 : 0;
                $stmt = $conn->prepare("INSERT INTO community_votes (post_id, user_id, upvote, downvote, created_at) VALUES (:post_id, :user_id, :upvote, :downvote, NOW())");
                $stmt->execute([':post_id'=>$post_id, ':user_id'=>$user_id, ':upvote'=>$up, ':downvote'=>$down]);
                $user_vote = $vote_type;
            }

            $upvote_count = $conn->prepare("SELECT COUNT(*) FROM community_votes WHERE post_id = :pid AND upvote = 1");
            $upvote_count->execute([':pid'=>$post_id]);
            $upvote_count = $upvote_count->fetchColumn();

            $downvote_count = $conn->prepare("SELECT COUNT(*) FROM community_votes WHERE post_id = :pid AND downvote = 1");
            $downvote_count->execute([':pid'=>$post_id]);
            $downvote_count = $downvote_count->fetchColumn();

            echo json_encode(['status'=>'success', 'upvote_count'=>$upvote_count, 'downvote_count'=>$downvote_count, 'user_vote'=>$user_vote]);
            break;

        // ========================= GET USER VOTE =========================
        case 'get_user_vote':
            $post_id = (int)($_POST['post_id'] ?? 0);
            $user_vote = null;
            if ($user_id) {
                $stmt = $conn->prepare("SELECT upvote, downvote FROM community_votes WHERE post_id = :post_id AND user_id = :user_id");
                $stmt->execute([':post_id'=>$post_id, ':user_id'=>$user_id]);
                $vote = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($vote) {
                    if ($vote['upvote'] == 1) $user_vote = 'upvote';
                    elseif ($vote['downvote'] == 1) $user_vote = 'downvote';
                }
            }
            echo json_encode(['status'=>'success','user_vote'=>$user_vote]);
            break;

        // ========================= ADD COMMENT =========================
        case 'add_comment':
            if (!$user_id) throw new Exception('Please log in first');

            $post_id = (int)($_POST['post_id'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');
            if (!$comment) throw new Exception('Comment cannot be empty');
            if (strlen($comment) > 2000) throw new Exception('Comment is too long (max 2000 characters)');

            $stmt = $conn->prepare("INSERT INTO community_comments (post_id, user_id, comment, created_at) VALUES (:post_id, :user_id, :comment, NOW())");
            $stmt->execute([':post_id'=>$post_id, ':user_id'=>$user_id, ':comment'=>$comment]);

            echo json_encode(['status'=>'success','message'=>'Comment posted','comment_id'=>$conn->lastInsertId()]);
            break;

        // ========================= DELETE COMMENT =========================
        case 'delete_comment':
            if (!$user_id) throw new Exception('Please log in first');

            $comment_id = (int)($_POST['comment_id'] ?? 0);
            $stmt = $conn->prepare("SELECT user_id FROM community_comments WHERE id = :comment_id");
            $stmt->execute([':comment_id'=>$comment_id]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$comment) throw new Exception('Comment not found');
            if ($comment['user_id'] != $user_id) throw new Exception('You can only delete your own comments');

            $stmt = $conn->prepare("DELETE FROM community_comments WHERE id = :comment_id");
            $stmt->execute([':comment_id'=>$comment_id]);

            echo json_encode(['status'=>'success','message'=>'Comment deleted']);
            break;

        // ========================= DELETE POST =========================
        case 'delete_post':
            if (!$user_id) throw new Exception('Please log in first');

            $post_id = (int)($_POST['post_id'] ?? 0);
            $stmt = $conn->prepare("SELECT user_id FROM community_posts WHERE id = :post_id");
            $stmt->execute([':post_id'=>$post_id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) throw new Exception('Post not found');
            if ($post['user_id'] != $user_id) throw new Exception('You can only delete your own posts');

            $conn->prepare("DELETE FROM community_comments WHERE post_id = :post_id")->execute([':post_id'=>$post_id]);
            $conn->prepare("DELETE FROM community_votes WHERE post_id = :post_id")->execute([':post_id'=>$post_id]);
            $conn->prepare("DELETE FROM community_posts WHERE id = :post_id")->execute([':post_id'=>$post_id]);

            echo json_encode(['status'=>'success','message'=>'Post deleted']);
            break;

        // ========================= FETCH ALL COMMENTS =========================
        case 'fetch_all_comments':
            $post_id = (int)($_POST['post_id'] ?? 0);

            $stmt = $conn->prepare("
                SELECT c.id, c.comment, c.user_id, c.created_at, u.name 
                FROM community_comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = :post_id
                ORDER BY c.created_at ASC
            ");
            $stmt->execute([':post_id'=>$post_id]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total = count($comments);
            $result = [];
            foreach ($comments as $c) {
                $result[] = [
                    'id' => $c['id'],
                    'name' => htmlspecialchars($c['name']),
                    'comment' => nl2br(htmlspecialchars($c['comment'])),
                    'created_at' => date("M d, Y h:i A", strtotime($c['created_at'])),
                    'can_delete' => ($user_id && $user_id == $c['user_id'])
                ];
            }

            echo json_encode(['status'=>'success','total'=>$total,'comments'=>$result]);
            break;

        // ========================= UPDATE ONLINE ACTIVITY =========================
        case 'update_activity':
            if (!$user_id) throw new Exception('Not logged in');
            $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = :uid")->execute([':uid'=>$user_id]);
            echo json_encode(['status'=>'success']);
            break;

        // ========================= GET ONLINE USERS =========================
        case 'get_online_users':
            $stmt = $conn->prepare("SELECT id FROM users WHERE last_activity >= (NOW() - INTERVAL 5 MINUTE)");
            $stmt->execute();
            $onlineUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode([
                'status'=>'success',
                'count' => count($onlineUsers),
                'online' => $onlineUsers
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
