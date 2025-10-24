// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'toastSlideIn 0.3s ease-out reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Modal functions
function openModal() { 
    document.getElementById("postModal").style.display = "block"; 
}

function closeModal() { 
    document.getElementById("postModal").style.display = "none"; 
}

function openPostModal(post) {
    document.getElementById('modal-title').textContent = post.title;
    document.getElementById('modal-meta').textContent = `Posted by u/${post.name} • ${post.created_at}`;
    
    const modalImage = document.getElementById('modal-image');
    if(post.image) {
        modalImage.src = post.image;
        modalImage.style.display = "block";
    } else {
        modalImage.style.display = "none";
    }
    
    document.getElementById('modal-content').innerHTML = post.content.replace(/\n/g,"<br>");
    document.getElementById('modal-score').textContent = post.score;
    document.getElementById('modal-comments-count').textContent = post.comment_count;
    document.getElementById('modal-views').textContent = post.views;

    document.getElementById('postViewModal').style.display = "block";
}

function closePostModal() { 
    document.getElementById('postViewModal').style.display = "none"; 
}

// Close modal when clicking outside
window.onclick = function(e) {
    if(e.target.classList.contains("modal")) {
        e.target.style.display = "none";
    }
}

// Voting system
document.querySelectorAll(".vote-btn").forEach(btn => {
    btn.addEventListener("click", function() {
        const postId = this.dataset.id;
        const voteType = this.dataset.voteType; // 'upvote' or 'downvote'
        const card = this.closest('.post-card');

        card.classList.add('loading');

        fetch("community_action.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ action: "vote", post_id: postId, vote_type: voteType })
        })
        .then(res => res.json())
        .then(data => {
            card.classList.remove('loading');

            if (data.status === "success") {
                // Update counts
                const upvoteSpan = card.querySelector('.upvote-count');
                const downvoteSpan = card.querySelector('.downvote-count');
                upvoteSpan.textContent = data.upvote_count;
                downvoteSpan.textContent = data.downvote_count;

                // Highlight buttons
                const upBtn = card.querySelector('.vote-btn.upvote-btn');
                const downBtn = card.querySelector('.vote-btn.downvote-btn');
                upBtn.classList.remove('voted');
                downBtn.classList.remove('voted');

                if (data.user_vote === 'upvote') {
                    upBtn.classList.add('voted', 'animate');
                    setTimeout(() => upBtn.classList.remove('animate'), 300);
                }
                if (data.user_vote === 'downvote') {
                    downBtn.classList.add('voted', 'animate');
                    setTimeout(() => downBtn.classList.remove('animate'), 300);
                }

                showToast('Vote recorded!');
            } else {
                showToast(data.message || 'Error voting', 'error');
            }
        })
        .catch(error => {
            card.classList.remove('loading');
            showToast('Network error occurred', 'error');
        });
    });
});

document.querySelectorAll(".post-card").forEach(card => {
    const postId = card.querySelector(".vote-btn").dataset.id;

    fetch("community_action.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "get_user_vote", post_id: postId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success" && data.user_vote) {
            const upBtn = card.querySelector('.vote-btn.upvote-btn');
            const downBtn = card.querySelector('.vote-btn.downvote-btn');

            if (data.user_vote === 'upvote') upBtn.classList.add('voted');
            if (data.user_vote === 'downvote') downBtn.classList.add('voted');
        }
    });
});


// --- Add Comment (inline + FB modal) ---
document.querySelectorAll(".add-comment-btn, #fb-modal-add-comment").forEach(btn => {
    btn.addEventListener("click", function() {
        const isModal = this.id === "fb-modal-add-comment";
        const postId = isModal
            ? document.getElementById('fbCommentsModal').dataset.postId
            : this.dataset.post;

        const textarea = isModal
            ? document.getElementById('fb-modal-new-comment')
            : document.querySelector(`.new-comment[data-post='${postId}']`);

        const comment = textarea.value.trim();
        if(comment === "") {
            showToast('Please enter a comment', 'error');
            return;
        }

        this.disabled = true;
        this.textContent = 'Posting...';

        fetch("community_action.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ action: "add_comment", post_id: postId, comment: comment })
        })
        .then(res => res.json())
        .then(data => {
            this.disabled = false;
            this.textContent = 'Comment';

            if(data.status === "success") {
                const createCommentDiv = (author="You") => {
                    const div = document.createElement("div");
                    div.className = "comment";
                    div.id = `comment-${data.comment_id}`;
                    div.innerHTML = `
                        <strong>u/${author}</strong>
                        <small>Just now</small>
                        <p>${comment.replace(/\n/g, "<br>")}</p>
                        <button class="delete-comment-btn" data-id="${data.comment_id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    `;
                    return div;
                };

                // --- Inline update ---
                const commentsList = document.querySelector(`#comments-${postId} .comments-list`);
                commentsList.appendChild(createCommentDiv());

                // ✅ Maintain only 3 visible comments inline
                const allComments = commentsList.querySelectorAll(".comment");
                if (allComments.length > 3) {
                    allComments[0].remove(); // remove oldest, keep last 3
                    // ensure "View all" button exists
                    let showMoreBtn = document.querySelector(`#comments-${postId} .show-more-comments`);
                    if (!showMoreBtn) {
                        showMoreBtn = document.createElement("button");
                        showMoreBtn.className = "show-more-comments";
                        showMoreBtn.dataset.postId = postId;
                        showMoreBtn.textContent = "View all comments";
                        commentsList.insertAdjacentElement("afterend", showMoreBtn);
                    }
                }

                // --- Update comment count ---
                const commentBtn = document.querySelector(`.post-card[data-post-id='${postId}'] .comment-btn`);
                if(commentBtn) {
                    const currentCount = parseInt(commentBtn.textContent.match(/\d+/)[0]) || 0;
                    commentBtn.innerHTML = `<i class="fas fa-comment"></i> ${currentCount + 1} Comments`;
                }

                // --- FB Modal update ---
                const fbModal = document.getElementById('fbCommentsModal');
                const fbList = document.getElementById('fb-modal-comments-list');
                if(fbModal && fbList && fbModal.dataset.postId == postId) {
                    fbList.appendChild(createCommentDiv());
                }

                textarea.value = "";
                showToast('Comment posted!');
            } else {
                showToast(data.message || 'Error posting comment', 'error');
            }
        })
        .catch(() => {
            this.disabled = false;
            this.textContent = 'Comment';
            showToast('Network error occurred', 'error');
        });
    });
});

// --- Show More Comments handler ---
document.addEventListener("click", function(e) {
    if (e.target.classList.contains("show-more-comments")) {
        const postId = e.target.dataset.postId;
        const list = document.querySelector(`#comments-${postId} .comments-list`);

        fetch("community_action.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ action: "fetch_all_comments", post_id: postId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                list.innerHTML = "";
                data.comments.forEach(c => {
                    const div = document.createElement("div");
                    div.className = "comment";
                    div.id = "comment-" + c.id;
                    div.innerHTML = `
                        <strong>u/${c.name}</strong>
                        <small>${c.created_at}</small>
                        <p>${c.comment}</p>
                        ${c.can_delete ? `<button class="delete-comment-btn" data-id="${c.id}"><i class="fas fa-trash"></i> Delete</button>` : ""}
                    `;
                    list.appendChild(div);
                });
                e.target.remove(); // remove button
            }
        });
    }
});

document.addEventListener("click", e => {
    // --- Toggle menu ---
    if (e.target.closest(".menu-btn")) {
        const menu = e.target.closest(".post-menu");
        menu.classList.toggle("open");
    } else {
        document.querySelectorAll(".post-menu").forEach(m => m.classList.remove("open"));
    }

    // --- Delete post (with live count update) ---
    if (e.target.closest(".delete-post-btn")) {
        const btn = e.target.closest(".delete-post-btn");
        const postId = btn.dataset.id;
        if (!postId) return;

        Swal.fire({
            title: 'Delete this post?',
            text: "Once deleted, you won't be able to recover it!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#666',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Disable button + show spinner
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

                fetch("community_action.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({
                        action: "delete_post",
                        post_id: postId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        // ✅ Remove inline post
                        const postDiv = document.querySelector(`.post-card[data-post-id='${postId}']`);
                        if (postDiv) postDiv.remove();

                        // ✅ Decrease the post count
                        const postCountEl = document.querySelector('.community-stats div strong');
                        if (postCountEl) {
                            let currentCount = parseInt(postCountEl.textContent.trim()) || 0;
                            if (currentCount > 0) postCountEl.textContent = currentCount - 1;
                        }

                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Your post has been removed.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                        Swal.fire('Error', data.message || 'Error deleting post', 'error');
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                    Swal.fire('Error', 'Network error occurred', 'error');
                });
            }
        });
    }
});





// --- Open FB Comments Modal ---
function openFBCommentsModal(postId) {
    const modal = document.getElementById('fbCommentsModal');
    const list = document.getElementById('fb-modal-comments-list');
    if (!modal) return;

    modal.dataset.postId = postId;
    list.innerHTML = '';

    // Clone only visible inline comments (max 3)
    const inlineComments = document.querySelectorAll(`#comments-${postId} .comment`);
    inlineComments.forEach(c => list.appendChild(c.cloneNode(true)));

    modal.style.display = 'flex';        // consistent
    requestAnimationFrame(() => {
        modal.classList.add('show');
    });
}

// --- Close FB Comments Modal ---
function closeFBCommentsModal() {
    const modal = document.getElementById('fbCommentsModal');
    if (!modal) return;

    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';   // reset after animation
        modal.dataset.postId = '';
    }, 200);
}


// Close when clicking outside
window.addEventListener('click', function(e) {
    const modal = document.getElementById('fbCommentsModal');
    if (modal && e.target === modal) {
        closeFBCommentsModal();
    }
});

// Close with ESC key
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('fbCommentsModal');
        if (modal && modal.style.display === 'flex') {
            closeFBCommentsModal();
        }
    }
});

// Open modal on comment button click
document.querySelectorAll(".comment-btn").forEach(btn => {
    btn.addEventListener("click", function() {
        const postId = btn.closest('.post-card').dataset.postId;
        openFBCommentsModal(postId); // direct call
    });
});

// --- Open modal on comment button click ---
document.querySelectorAll(".comment-btn").forEach(btn => {
    btn.addEventListener("click", function() {
        const postId = btn.closest('.post-card').dataset.postId;
        const modal = document.getElementById('fbCommentsModal');
        modal.style.display = 'flex';
        requestAnimationFrame(() => openFBCommentsModal(postId));
    });
});


// --- Delete comment (inline + modal) ---
document.addEventListener("click", function(e) {
    if(e.target.closest('.delete-comment-btn')) {
        const btn = e.target.closest('.delete-comment-btn');
        const commentId = btn.dataset.id;
        if(!commentId) return;

        // SweetAlert confirm
        Swal.fire({
            title: 'Delete this comment?',
            text: "Once deleted, you won't be able to recover it!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#666',
            confirmButtonText: 'Yes',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

                fetch("community_action.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({ action: "delete_comment", comment_id: commentId })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === "success") {
                        // Remove inline
                        const commentDiv = document.getElementById("comment-" + commentId);
                        if(commentDiv) commentDiv.remove();

                        // Remove FB modal
                        const fbCommentDiv = document.querySelector(`#fbCommentsModal .comment#comment-${commentId}`);
                        if(fbCommentDiv) fbCommentDiv.remove();

                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Your comment has been removed.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                        Swal.fire('Error', data.message || 'Error deleting comment', 'error');
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                    Swal.fire('Error', 'Network error occurred', 'error');
                });
            }
        });
    }
});


// Auto-resize textareas
document.querySelectorAll('textarea').forEach(textarea => {
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        const activeElement = document.activeElement;
        if(activeElement.tagName === 'TEXTAREA') {
            const form = activeElement.closest('form');
            const submitBtn = activeElement.parentElement.querySelector('button[type="submit"], .add-comment-btn');
            if(form && submitBtn) {
                e.preventDefault();
                submitBtn.click();
            }
        }
    }
    
    if(e.key === 'Escape') {
        const openModal = document.querySelector('.modal[style*="block"]');
        if(openModal) {
            openModal.style.display = 'none';
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const postCards = document.querySelectorAll('.post-card');
    postCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease-out';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// ================= HEARTBEAT PING =================
const pingActivity = async () => {
    if (typeof user_id !== 'undefined' && user_id) {
        try {
            await fetch('community_action.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'update_activity' })
            });
        } catch (err) {
            console.error('Activity ping failed', err);
        }
    }
};

// Run ping immediately and every 30s
pingActivity();
setInterval(pingActivity, 30000);

// ================= UPDATE ONLINE USERS =================
const updateOnlineUsers = async () => {
    try {
        const res = await fetch('community_action.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'get_online_users' })
        });
        const data = await res.json();
        if (data.status !== 'success') return;

        const onlineIds = data.online.map(Number);

        document.querySelectorAll('.online-dot').forEach(dot => {
            const uid = Number(dot.dataset.userId);
            const isOnline = onlineIds.includes(uid);

            // ✅ Online = green, Offline = gray
            dot.style.background = isOnline ? 'limegreen' : 'gray';

            // Tooltip
            if (isOnline) {
                dot.title = 'Online';
            } else if (dot.dataset.lastActivity) {
                const lastActivity = new Date(dot.dataset.lastActivity);
                const diffMinutes = Math.floor((Date.now() - lastActivity.getTime()) / 60000);
                dot.title = `Last active ${diffMinutes} min ago`;
            } else {
                dot.title = 'Offline';
            }
        });

        // Update online count display
        const onlineCountEl = document.getElementById('online-count');
        if (onlineCountEl) onlineCountEl.textContent = data.count;

    } catch (err) {
        console.error('Failed to fetch online users', err);
    }
};

// Initial load + refresh every 15 seconds
updateOnlineUsers();
setInterval(updateOnlineUsers, 15000);

// ================= LOGOUT HANDLER =================
document.getElementById('logout-btn')?.addEventListener('click', () => {
    if (typeof user_id !== 'undefined' && user_id) {
        const currentDot = document.querySelector(`.online-dot[data-user-id='${user_id}']`);
        if (currentDot) {
            currentDot.style.background = 'gray';
            currentDot.title = 'Offline';
        }

        // Update online count immediately
        const onlineCountEl = document.getElementById('online-count');
        if (onlineCountEl) {
            let currentCount = parseInt(onlineCountEl.textContent) || 0;
            onlineCountEl.textContent = Math.max(currentCount - 1, 0);
        }
    }

    // Then perform your normal logout action (redirect/fetch)
});


let isModalOpen = false;
let currentModalElements = {};

function getModalElements() {
    if (Object.keys(currentModalElements).length > 0) return currentModalElements;

    const modal = document.getElementById('userProfileModal');
    const modalPosts = document.getElementById('modalProfilePosts');
    const modalPic = document.getElementById('modalProfilePic');
    const modalName = document.getElementById('modalProfileName');
    const modalAdminBadge = document.getElementById('modalAdminBadge');

    if (!modal || !modalPosts || !modalPic || !modalName || !modalAdminBadge) {
        console.error('Profile modal elements missing!');
    }

    currentModalElements = { modal, modalPosts, modalPic, modalName, modalAdminBadge };
    return currentModalElements;
}

function openProfileModal(userId) {
    const { modal, modalPosts, modalPic, modalName, modalAdminBadge } = getModalElements();
    if (!modal) return;

    if (isModalOpen && modal.dataset.currentUserId === userId) return;

    modal.dataset.currentUserId = userId;
    isModalOpen = true;

    // Show loading state
    modalPosts.innerHTML = '<div class="loading">Loading...</div>';
    modalPic.src = 'uploads/user.png';
    modalName.textContent = 'Loading...';
    modalAdminBadge.style.display = 'none';

    fetch(`community_profile_ajax.php?user_id=${userId}`)
        .then(res => res.ok ? res.text() : Promise.reject(`HTTP ${res.status}`))
        .then(html => {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;

            // ===== Header =====
            const header = tempDiv.querySelector('.profile-header');
            if (header) {
                const img = header.querySelector('img');
                const h3 = header.querySelector('h3');
                if (img) modalPic.src = img.src;
                if (h3) {
                    const role = h3.dataset.role;
                    modalName.textContent = h3.textContent.replace(/\s*Admin\s*$/,'').trim();
                    modalAdminBadge.style.display = role === 'admin' ? 'inline-block' : 'none';
                }
            }

            // ===== Posts =====
            const posts = tempDiv.querySelectorAll('.post-content, .admin-post');
            modalPosts.innerHTML = '';
            if (posts.length > 0) {
                posts.forEach(post => modalPosts.insertAdjacentHTML('beforeend', post.outerHTML));
            } else {
                modalPosts.innerHTML = '<div class="no-posts">No posts yet.</div>';
            }

            // Show modal
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            modal.scrollTop = 0;
        })
        .catch(err => {
            console.error('Error loading profile:', err);
            modalPosts.innerHTML = '<div class="error">Failed to load profile. Please try again.</div>';
        });
}

function closeProfileModal() {
    const { modal, modalPosts } = getModalElements();
    if (!modal) return;

    modal.style.display = 'none';
    document.body.style.overflow = '';
    delete modal.dataset.currentUserId;
    modalPosts.innerHTML = '';
    isModalOpen = false;
}

// ===== Event delegation =====
document.addEventListener('click', e => {
    const target = e.target.closest('.post-author-pic, .post-author');
    if (target) {
        const userId = target.dataset.userId;
        if (userId) {
            e.preventDefault();
            openProfileModal(userId);
        }
    }

    if (isModalOpen) {
        const modal = getModalElements().modal;
        if (modal && e.target === modal) closeProfileModal();
    }
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && isModalOpen) closeProfileModal();
});


document.addEventListener('DOMContentLoaded', () => {
    const tags = document.querySelectorAll('.trending-tag');
    const posts = document.querySelectorAll('.post-card');
    let activeTag = null;

    tags.forEach(tag => {
        tag.addEventListener('click', () => {
            const selectedTag = tag.dataset.tag;

            // Toggle same tag (click again = show all)
            if (activeTag === selectedTag) {
                activeTag = null;
                tag.style.backgroundColor = '';
                tag.style.color = '';
                posts.forEach(p => p.style.display = '');
            } else {
                activeTag = selectedTag;
                // Reset all tag styles
                tags.forEach(t => { t.style.backgroundColor = ''; t.style.color = ''; });
                // Highlight active tag
                tag.style.backgroundColor = '#e67e22';
                tag.style.color = '#fff';

                // Filter posts
                posts.forEach(post => {
                    const text = post.textContent || '';
                    post.style.display = text.includes(`#${selectedTag}`) ? '' : 'none';
                });
            }
        });
    });
});
