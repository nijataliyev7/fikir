<?php
require 'admin_header.php';

// --- Səhifənin PHP məntiqi olduğu kimi qalır ---
$limit = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sort_key = $_GET['sort'] ?? 'created_at_desc';
$sort_options = [
    'created_at_desc' => 'u.created_at DESC', 'created_at_asc' => 'u.created_at ASC',
    'comment_count_desc' => 'comment_count DESC', 'comment_count_asc' => 'comment_count ASC',
    'login_count_desc' => 'u.login_count DESC', 'login_count_asc' => 'u.login_count ASC',
    'likes_given_desc' => 'likes_given_count DESC', 'likes_given_asc' => 'likes_given_count ASC'
];
$order_by = $sort_options[$sort_key] ?? $sort_options['created_at_desc'];

$search_term = $_GET['search'] ?? '';
$sql_where = '';
$params = [];
$types = '';
if (!empty($search_term)) {
    $sql_where = "WHERE u.name LIKE ? OR u.email LIKE ?";
    $like_term = "%" . $search_term . "%";
    $params = [$like_term, $like_term];
    $types = 'ss';
}

$count_sql = "SELECT COUNT(*) as total FROM users u $sql_where";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) { $count_stmt->bind_param($types, ...$params); }
$count_stmt->execute();
$total_users = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);
$count_stmt->close();

$users_sql = "
    SELECT u.id, u.name, u.email, u.status, u.created_at, u.whatsapp_number, u.profile_picture_url, u.login_count,
    COUNT(DISTINCT c.id) as comment_count, COUNT(DISTINCT cl.id) as likes_given_count
    FROM users u
    LEFT JOIN comments c ON u.id = c.user_id LEFT JOIN comment_likes cl ON u.id = cl.user_id
    $sql_where GROUP BY u.id ORDER BY $order_by LIMIT ? OFFSET ?";
$users_stmt = $conn->prepare($users_sql);
$final_params = array_merge($params, [$limit, $offset]);
$final_types = $types . 'ii';
$users_stmt->bind_param($final_types, ...$final_params);
$users_stmt->execute();
$users = $users_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$users_stmt->close();
$total_users_stat = $conn->query("SELECT COUNT(id) as total FROM users")->fetch_assoc()['total'];

function getSortLink($current_sort, $column_name, $search_term) {
    $sort_asc = $column_name . '_asc'; $sort_desc = $column_name . '_desc';
    $icon = ''; $link = "?sort=";
    if ($current_sort === $sort_desc) { $link .= $sort_asc; $icon = '🔽'; } 
    else if ($current_sort === $sort_asc) { $link .= $sort_desc; $icon = '🔼'; } 
    else { $link .= $sort_desc; }
    if (!empty($search_term)) { $link .= "&search=" . urlencode($search_term); }
    return ['link' => $link, 'icon' => $icon];
}
?>

<div class="content-header">
    <h1>İstifadəçilərin İdarəsi</h1>
</div>

<div class="content-box">
    <div class="email-section">
        <h2>Bütün İstifadəçilərə E-poçt Göndər</h2>
        <form id="bulk-email-form">
            <label for="subject">Mövzu:</label>
            <input type="text" id="subject" name="subject" required>
            <label for="message_body">Mətn (`{USER_NAME}` istifadə edə bilərsiniz):</label>
            <textarea id="message_body" name="message_body" rows="8" required></textarea>
            <button type="submit">Göndərməyə Başla</button>
        </form>
        <div id="bulk-email-progress">
            <p id="progress-text">Göndərilir...</p>
            <div class="progress-bar"><div class="progress-bar-inner" id="progress-bar-inner">0%</div></div>
        </div>
    </div>

    <p><strong>Ümumi istifadəçi sayı:</strong> <?php echo $total_users_stat; ?></p>
    <form action="manage_users.php" method="GET" class="user-search-form">
        <input type="text" name="search" placeholder="Ad və ya e-poçt ilə axtar..." value="<?php echo htmlspecialchars($search_term); ?>">
        <button type="submit">Axtar</button>
        <?php if(!empty($search_term)): ?><a href="manage_users.php">Axtarışı Təmizlə</a><?php endif; ?>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th><th>Profil</th><th>Ad</th><th>E-poçt</th>
                <th><a href="<?php echo getSortLink($sort_key, 'comment_count', $search_term)['link']; ?>">Rəy Sayı <?php echo getSortLink($sort_key, 'comment_count', $search_term)['icon']; ?></a></th>
                <th><a href="<?php echo getSortLink($sort_key, 'login_count', $search_term)['link']; ?>">Giriş Sayı <?php echo getSortLink($sort_key, 'login_count', $search_term)['icon']; ?></a></th>
                <th><a href="<?php echo getSortLink($sort_key, 'likes_given', $search_term)['link']; ?>">Verilən Bəyəni <?php echo getSortLink($sort_key, 'likes_given', $search_term)['icon']; ?></a></th>
                <th>Status</th>
                <th><a href="<?php echo getSortLink($sort_key, 'created_at', $search_term)['link']; ?>">Qeydiyyat <?php echo getSortLink($sort_key, 'created_at', $search_term)['icon']; ?></a></th>
                <th>Əməliyyat</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr id="user-row-<?php echo $user['id']; ?>">
                <td><?php echo $user['id']; ?></td>
                <td><img src="<?php echo htmlspecialchars($user['profile_picture_url']); ?>" class="profile-image" alt="profil"></td>
                <td><?php echo htmlspecialchars($user['name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><a href="view_user_comments.php?user_id=<?php echo $user['id']; ?>" target="_blank"><?php echo $user['comment_count']; ?></a></td>
                <td><?php echo $user['login_count']; ?></td>
                <td><?php echo $user['likes_given_count']; ?></td>
                <td><span class="status-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                <td>
                    <?php if ($user['status'] === 'active'): ?>
                        <button class="toggle-status-btn block-btn" data-user-id="<?php echo $user['id']; ?>" data-new-status="blocked">Blokla</button>
                    <?php else: ?>
                        <button class="toggle-status-btn unblock-btn" data-user-id="<?php echo $user['id']; ?>" data-new-status="active">Aktiv et</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?><tr><td colspan="10" style="text-align:center;">Nəticə tapılmadı.</td></tr><?php endif; ?>
        </tbody>
    </table>

    <div class="pagination-controls">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&sort=<?php echo $sort_key; ?>&search=<?php echo urlencode($search_term); ?>" class="<?php if($i == $page) echo 'active'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
</div>


<script>
// --- TEST ÜÇÜN ALERT ---
// Bu, skriptin ümumiyyətlə işə düşüb-düşmədiyini yoxlayacaq.


document.addEventListener('DOMContentLoaded', function() {
    // Bloklama/Aktivləşdirmə Məntiqi
    document.querySelectorAll('.toggle-status-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            var userId = this.dataset.userId;
            var newStatus = this.dataset.newStatus;
            if (!confirm(newStatus === 'blocked' ? 'Bu istifadəçini bloklamağa əminsiniz?' : 'Bu istifadəçini aktiv etməyə əminsiniz?')) { return; }
            var formData = new URLSearchParams();
            formData.append('user_id', userId);
            formData.append('status', newStatus);
            fetch('toggle_user_status.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') { window.location.reload(); } 
                    else { alert('Xəta: ' + data.message); }
                }).catch(error => console.error('Error:', error));
        });
    });

    // Bütün İstifadəçilərə E-poçt Göndərmə Məntiqi
    const emailForm = document.getElementById('bulk-email-form');
    if (emailForm) {
        const progressDiv = document.getElementById('bulk-email-progress');
        const progressText = document.getElementById('progress-text');
        const progressBarInner = document.getElementById('progress-bar-inner');
        const submitButton = emailForm.querySelector('button[type="submit"]');

        emailForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!confirm('Bütün aktiv istifadəçilərə e-poçt göndərməyə əminsiniz?')) { return; }
            submitButton.disabled = true;
            submitButton.innerText = 'Göndərilir...';
            progressDiv.style.display = 'block';
            
            const formData = new FormData(emailForm);
            function sendBatch(offset = 0) {
                const batchFormData = new FormData();
                batchFormData.append('subject', formData.get('subject'));
                batchFormData.append('message_body', formData.get('message_body'));
                batchFormData.append('offset', offset);
                fetch('send_bulk_email.php', { method: 'POST', body: batchFormData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'error') { throw new Error(data.message); }
                        let percentage = data.total_users > 0 ? Math.round((data.sent_count / data.total_users) * 100) : 0;
                        if (percentage > 100) percentage = 100;
                        progressBarInner.style.width = percentage + '%';
                        progressBarInner.innerText = percentage + '%';
                        progressText.innerText = 'Göndərildi: ' + data.sent_count + ' / ' + data.total_users;
                        if (data.status === 'continue') {
                            sendBatch(data.next_offset);
                        } else if (data.status === 'done') {
                            progressText.innerText = 'Bütün e-poçtlar uğurla göndərildi!';
                            progressBarInner.style.backgroundColor = '#28a745';
                            submitButton.disabled = false;
                            submitButton.innerText = 'Göndərməyə Başla';
                        }
                    })
                    .catch(error => {
                        progressText.innerText = 'Xəta baş verdi: ' + error.message;
                        progressBarInner.style.backgroundColor = '#dc3545';
                        submitButton.disabled = false;
                        submitButton.innerText = 'Yenidən Cəhd Et';
                    });
            }
            sendBatch(0);
        });
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Səhifəyə məxsus JavaScript kodları burada qalır
    document.querySelectorAll('.toggle-status-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            var userId = this.dataset.userId;
            var newStatus = this.dataset.newStatus;
            if (!confirm(newStatus === 'blocked' ? 'Bu istifadəçini bloklamağa əminsiniz?' : 'Bu istifadəçini aktiv etməyə əminsiniz?')) { return; }
            var formData = new URLSearchParams();
            formData.append('user_id', userId);
            formData.append('status', newStatus);
            fetch('toggle_user_status.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => { if (data.status === 'success') { window.location.reload(); } else { alert('Xəta: ' + data.message); } })
                .catch(error => console.error('Error:', error));
        });
    });

    const emailForm = document.getElementById('bulk-email-form');
    if (emailForm) {
        // ... (Mövcud toplu e-poçt JavaScript kodunuz burada olduğu kimi qalır) ...
        const progressDiv = document.getElementById('bulk-email-progress');
        const progressText = document.getElementById('progress-text');
        const progressBarInner = document.getElementById('progress-bar-inner');
        const submitButton = emailForm.querySelector('button[type="submit"]');

        emailForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!confirm('Bütün aktiv istifadəçilərə e-poçt göndərməyə əminsiniz?')) { return; }
            submitButton.disabled = true;
            submitButton.innerText = 'Göndərilir...';
            progressDiv.style.display = 'block';
            
            const formData = new FormData(emailForm);
            function sendBatch(offset = 0) {
                const batchFormData = new FormData();
                batchFormData.append('subject', formData.get('subject'));
                batchFormData.append('message_body', formData.get('message_body'));
                batchFormData.append('offset', offset);
                fetch('send_bulk_email.php', { method: 'POST', body: batchFormData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'error') { throw new Error(data.message); }
                        let percentage = data.total_users > 0 ? Math.round((data.sent_count / data.total_users) * 100) : 0;
                        if (percentage > 100) percentage = 100;
                        progressBarInner.style.width = percentage + '%';
                        progressBarInner.innerText = percentage + '%';
                        progressText.innerText = 'Göndərildi: ' + data.sent_count + ' / ' + data.total_users;
                        if (data.status === 'continue') {
                            sendBatch(data.next_offset);
                        } else if (data.status === 'done') {
                            progressText.innerText = 'Bütün e-poçtlar uğurla göndərildi!';
                            progressBarInner.style.backgroundColor = '#28a745';
                            submitButton.disabled = false;
                            submitButton.innerText = 'Göndərməyə Başla';
                        }
                    })
                    .catch(error => {
                        progressText.innerText = 'Xəta baş verdi: ' + error.message;
                        progressBarInner.style.backgroundColor = '#dc3545';
                        submitButton.disabled = false;
                        submitButton.innerText = 'Yenidən Cəhd Et';
                    });
            }
            sendBatch(0);
        });
    }
});
</script>

<?php
require 'admin_footer.php';
?>