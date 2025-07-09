<?php
require 'admin_header.php';

// --- Səhifənin PHP məntiqi ---

$all_stickers = $conn->query("SELECT id, title FROM stickers ORDER BY title ASC")->fetch_all(MYSQLI_ASSOC);
$selected_sticker_id = isset($_GET['sticker_id']) && is_numeric($_GET['sticker_id']) ? intval($_GET['sticker_id']) : null;
$page_subtitle = "Ən Çox Bəyənilən Top 10 Rəy (Bütün Stikerlər Üzrə)";

// DÜZƏLİŞ: Sıralamaya ", c.created_at ASC" əlavə edildi
$sql_winners = "
    SELECT 
        c.id, c.comment, c.likes, c.created_at, 
        u.name, u.email, u.whatsapp_number,
        s.title AS sticker_title, s.id AS sticker_id
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN stickers s ON c.sticker_id = s.id
";

if ($selected_sticker_id) {
    $sql_winners .= " WHERE s.id = ?";
}

// BƏRABƏRLİK HALINI NƏZƏRƏ ALAN YENİ SIRALAMA
$sql_winners .= " ORDER BY c.likes DESC, c.created_at ASC LIMIT 10";

$winners_stmt = $conn->prepare($sql_winners);

if ($selected_sticker_id) {
    $winners_stmt->bind_param("i", $selected_sticker_id);
    
    $sticker_title_query = $conn->prepare("SELECT title FROM stickers WHERE id = ?");
    $sticker_title_query->bind_param("i", $selected_sticker_id);
    $sticker_title_query->execute();
    $result = $sticker_title_query->get_result();
    if ($result->num_rows > 0) {
        $page_subtitle = "'" . htmlspecialchars($result->fetch_assoc()['title']) . "' üçün Top 10 Rəy";
    }
}

$winners_stmt->execute();
$winners_result = $winners_stmt->get_result();

?>

<div class="content-header">
    <h1>Qaliblər Siyahısı</h1>
</div>

<div class="content-box">
    
    <form action="winners.php" method="GET" class="filter-form" style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <label for="sticker_id" style="font-weight: bold; margin: 0;">Stikerə görə filtrlə:</label>
        <select name="sticker_id" id="sticker_id" onchange="this.form.submit()" style="padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
            <option value="">Bütün Stikerlər</option>
            <?php foreach($all_stickers as $sticker): ?>
                <option value="<?php echo $sticker['id']; ?>" <?php if ($sticker['id'] == $selected_sticker_id) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($sticker['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit">Filtrlə</button></noscript>
    </form>
    
    <h2><?php echo $page_subtitle; ?></h2>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Yer</th>
                <th>Ad (İstifadəçi)</th>
                <th>E-poçt</th>
                <th>WhatsApp Nömrəsi</th>
                <th>❤️ Sayı</th>
                <th>Rəy</th>
                <?php if (!$selected_sticker_id): ?>
                    <th>Stiker</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($winners_result && $winners_result->num_rows > 0): ?>
                <?php $place = 1; while($winner = $winners_result->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo $place++; ?>.</strong></td>
                    <td><?php echo htmlspecialchars($winner['name']); ?></td>
                    <td><?php echo htmlspecialchars($winner['email']); ?></td>
                    <td><?php echo htmlspecialchars($winner['whatsapp_number'] ?? 'Daxil edilməyib'); ?></td>
                    <td><?php echo $winner['likes']; ?></td>
                    <td>"<?php echo htmlspecialchars(mb_strimwidth($winner['comment'], 0, 70, "...")); ?>"</td>
                    <?php if (!$selected_sticker_id): ?>
                        <td><a href="<?php echo $winner['sticker_id']; ?>" target="_blank"><?php echo htmlspecialchars($winner['sticker_title']); ?></a></td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="<?php echo $selected_sticker_id ? '6' : '7'; ?>" style="text-align:center;">Nəticə tapılmadı.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
require 'admin_footer.php';
?>