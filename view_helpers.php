<?php
// Bu fayl HTML yaradan köməkçi funksiyaları saxlayır

function generate_comment_html($comment, $is_admin, $sticker_page_url) {
    $is_logged_in = isset($_SESSION['user_id']);
    $current_user_id = $_SESSION['user_id'] ?? 0;

    $disabled = ($comment['liked_by_current_user'] ?? 0) ? 'disabled' : '';
    $like_button_title = !$is_logged_in ? 'Bəyənmək üçün daxil olun' : '';

    $user_is_owner = $is_logged_in && ($current_user_id == $comment['user_id']);
    $can_manage_comment = $is_admin || $user_is_owner;
    
    $management_buttons = '';
    if ($can_manage_comment) {
        $management_buttons = "<div class='admin-actions'>
                                  <a href='edit_comment.php?id={$comment['id']}' class='edit-btn admin-edit-btn'>Redaktə</a>
                                  <button class='delete-btn admin-delete-btn' data-id='{$comment['id']}'>Sil</button>
                               </div>";
    }

    $user_avatar = '';
    if (!empty($comment['profile_picture_url'])) {
        $user_avatar = "<img src='" . htmlspecialchars($comment['profile_picture_url']) . "' class='comment-avatar' alt='profil şəkli'>";
    } else {
        $first_letter = mb_substr(htmlspecialchars($comment['username'] ?? 'Qonaq'), 0, 1);
        $user_avatar = "<div class='comment-avatar guest-avatar'>" . ($first_letter ?: 'Q') . "</div>";
    }

    $comment_link = $sticker_page_url . "?sort=best#comment-" . $comment['id'];
    $share_text = "Zəhmət olmasa, bu rəyi bəyən: " . $comment_link;
    $whatsapp_link = "https://api.whatsapp.com/send?text=" . urlencode($share_text);
    
    $reply_action_buttons = "<button class='reply-btn' data-username='" . htmlspecialchars($comment['username'] ?? 'Qonaq') . "'>Cavab Yaz</button>";
    $reply_count = $comment['reply_count'] ?? 0;
    if ($comment['parent_id'] === null && $reply_count > 0) {
        $reply_action_buttons = "<button class='toggle-replies-btn' data-parent-id='{$comment['id']}'>💬 {$reply_count} </button> " . $reply_action_buttons;
    }

    $comment_body = preg_replace_callback('/(@[\p{L}]+(?:\s[\p{L}]+)?)(?=\s|$)/u', function($matches) {
        $mention = trim($matches[1]);
        return "<strong>{$mention}</strong>,";
    }, $comment['comment']);
    $comment_body = htmlspecialchars($comment_body, ENT_QUOTES, 'UTF-8');
    $comment_body = preg_replace('/&lt;(\/?strong)&gt;/', '<$1>', $comment_body);
    $comment_body = nl2br($comment_body);

    // ================== DƏYİŞİKLİK BURADADIR ==================
    $user_display_name = htmlspecialchars($comment['username'] ?? 'Qonaq');
    $user_id = $comment['user_id'] ?? 0;

    // Əgər rəyin sahibi real bir istifadəçidirsə (qonaq deyilsə), adını linkə çeviririk.
    if ($user_id > 0) {
        $user_link_html = "<a href='/fikir/profil.php?id={$user_id}' class='comment-author-link'><strong>{$user_display_name}</strong></a>";
    } else {
        $user_link_html = "<strong>{$user_display_name}</strong>";
    }
    // ==========================================================

    return "<div class='comment" . ($comment['parent_id'] !== null ? " is-reply" : "") . "' id='comment-{$comment['id']}' data-comment-id='{$comment['id']}'>
                <div class='comment-header'>{$user_avatar}{$user_link_html}<span class='comment-date'>" . date('d/m/Y H:i', strtotime($comment['created_at'])) . "</span></div>
                <p class='comment-body'>{$comment_body}</p>
                <div class='comment-footer'>
                    <div>
                        <button class='like-btn' data-id='{$comment['id']}' {$disabled} title='{$like_button_title}'>
                            ❤️ <span class='like-count'>{$comment['likes']}</span>
                        </button>
                        <a href='{$whatsapp_link}' class='whatsapp-share-btn' target='_blank' title='WhatsApp-da Paylaş'>Paylaş</a>
                        {$reply_action_buttons}
                    </div>
                    {$management_buttons}
                </div>
                <div class='reply-form-container'></div>
                <div class='replies-wrapper'></div>
            </div>";
}

// ... (generate_advanced_pagination funksiyası olduğu kimi qalır) ...

// generate_advanced_pagination funksiyası olduğu kimi qalır...
function generate_advanced_pagination($page, $total_pages, $sticker_id, $sort) {
    // Bu funksiyanın məzmunu dəyişməz qalır.
    if ($total_pages <= 1) return '';
    $adjacents = 2; 
    $base_url = $sticker_id . '?sort=' . $sort;
    $html = '<ul class="pagination">';
    if ($page > 1) { $html .= '<li class="page-item"><a class="page-link" href="'.$base_url.'&page='.($page-1).'">&laquo; Əvvəlki</a></li>'; } else { $html .= '<li class="page-item disabled"><span class="page-link">&laquo; Əvvəlki</span></li>';}
    if ($total_pages < 7 + ($adjacents * 2)) { for ($i = 1; $i <= $total_pages; $i++) { $html .= ($i == $page) ? '<li class="page-item active"><span class="page-link">'.$i.'</span></li>' : '<li class="page-item"><a class="page-link" href="'.$base_url.'&page='.$i.'">'.$i.'</a></li>';}} 
    elseif ($total_pages > 5 + ($adjacents * 2)) {
        if ($page < 1 + ($adjacents * 2)) {
            for ($i = 1; $i < 4 + ($adjacents * 2); $i++) { $html .= ($i == $page) ? '<li class="page-item active"><span class="page-link">'.$i.'</span></li>' : '<li class="page-item"><a class="page-link" href="'.$base_url.'&page='.$i.'">'.$i.'</a></li>';}
            $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>'; $html .= '<li class="page-item"><a class="page-link" href="'.$base_url.'&page='.$total_pages.'">'.$total_pages.'</a></li>';
        } elseif ($total_pages - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
            $html .= '<li class="page-item"><a class="page-link" href="'.$base_url.'&page=1">1</a></li>'; $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            for ($i = $page - $adjacents; $i <= $page + $adjacents; $i++) { $html .= ($i == $page) ? '<li class="page-item active"><span class="page-link">'.$i.'</span></li>' : '<li class="page-item"><a class="page-link" href="'.$base_url.'&page='.$i.'">'.$i.'</a></li>';}
            $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>'; $html .= '<li class="page-item"><a class="page-link" href="'.$base_url.'&page='.$total_pages.'">'.$total_pages.'</a></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="'.$base_url.'&page=1">1</a></li>'; $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            for ($i = $total_pages - (2 + ($adjacents * 2)); $i <= $total_pages; $i++) { $html .= ($i == $page) ? '<li class="page-item active"><span class="page-link">'.$i.'</span></li>' : '<li class="page-item"><a class="page-link" href="'.$base_url.'&page='.$i.'">'.$i.'</a></li>';}
        }
    }
    if ($page < $total_pages) { $html .= '<li class="page-item"><a class="page-link" href="'.$base_url.'&page='.($page+1).'">Növbəti &raquo;</a></li>';} else { $html .= '<li class="page-item disabled"><span class="page-link">Növbəti &raquo;</span></li>';}
    $html .= '</ul>'; return $html;
}?>
