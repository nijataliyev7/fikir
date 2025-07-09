<?php
// manage_stickers.php - YENİLƏNMİŞ TAM KOD
require 'admin_header.php';

// Mövcud PHP məntiqi (stikerləri bazadan çəkmək)
$stickers = $conn->query("SELECT * FROM stickers ORDER BY created_at DESC");
?>

<div class="content-header">
    <h1>Stikerlərin İdarəsi</h1>
</div>

<div class="content-box">
    <h2>Yeni Stiker Yüklə</h2>

    <?php if(isset($_GET['error'])) echo "<p class='error' style='padding: 15px; border-radius: 5px;'>" . htmlspecialchars($_GET['error']) . "</p>"; ?>
    <?php if(isset($_GET['success'])) echo "<p class='success' style='padding: 15px; border-radius: 5px; background-color: #d4edda; color: #155724;'>" . htmlspecialchars($_GET['success']) . "</p>"; ?>
    
    <form action="upload_sticker.php" method="post" enctype="multipart/form-data">
        <label for="title">Stiker Başlığı:</label>
        <input type="text" name="title" id="title" required>
        
        <label for="sticker_file">Fayl (png, gif, webp):</label>
        <input type="file" name="sticker_file" id="sticker_file" required accept=".png,.gif,.webp">
        
        <label for="contest_duration">Yarışma Müddəti (saat ilə):</label>
        <input type="number" name="contest_duration" id="contest_duration" placeholder="Məs: 24 (Boş buraxılsa, daimi olar)">

        <label>Mükafat Məbləğləri (istəyə bağlı):</label>
        <input type="text" name="prize_1st" placeholder="1-ci yerin mükafatı (məs: 10 AZN)">
        <input type="text" name="prize_2nd" placeholder="2-ci yerin mükafatı (məs: 5 AZN)">
        <input type="text" name="prize_3rd" placeholder="3-cü yerin mükafatı (məs: 3 AZN)">
        <input type="text" name="prize_4th" placeholder="4-cü yerin mükafatı">
        <input type="text" name="prize_5th" placeholder="5-ci yerin mükafatı">
        
        <button type="submit">Yüklə</button>
    </form>
</div>

<div class="content-box" style="margin-top: 30px;">
    <h2>Mövcud Stikerlər</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Şəkil</th>
                <th>Başlıq</th>
                <th>Status</th>
                <th style="width: 280px;">Əməliyyat</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($stickers && $stickers->num_rows > 0): ?>
                <?php while($sticker = $stickers->fetch_assoc()): ?>
                <tr>
                    <td><img src="uploads/<?php echo htmlspecialchars($sticker['image_path']); ?>" width="80" style="border-radius: 5px;" alt="sticker"></td>
                    <td><?php echo htmlspecialchars($sticker['title']); ?></td>
                    <td><span class="status-<?php echo $sticker['status']; ?>"><?php echo ucfirst($sticker['status']); ?></span></td>
                    <td class="actions">
                        <a href="<?php echo $sticker['id']; ?>" target="_blank" class="view-btn">Bax</a>
                        <a href="edit_sticker.php?id=<?php echo $sticker['id']; ?>" class="edit-btn">Redaktə</a>
                        
                        <?php if ($sticker['status'] === 'active' && !is_null($sticker['contest_end_time'])): ?>
                            <a href="end_contest_manually.php?id=<?php echo $sticker['id']; ?>" class="end-btn" onclick="return confirm('Bu stiker üçün yarışmanı indi bitirməyə əminsiniz?');">Bitir</a>
                        <?php elseif ($sticker['status'] === 'finished'): ?>
                            <a href="restart_contest.php?id=<?php echo $sticker['id']; ?>" class="restart-btn" style="background-color: #28a745;">Yenidən Başlat</a>
                        <?php endif; ?>

                        <a href="delete_sticker.php?id=<?php echo $sticker['id']; ?>" class="delete-btn" onclick="return confirm('Bu stikeri və ona aid bütün rəyləri silməyə əminsiniz?');">Sil</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align: center;">Heç bir stiker tapılmadı.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var restartButtons = document.querySelectorAll('.restart-btn');
    restartButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var duration = prompt("Yarışmanın yeni müddətini saat ilə daxil edin (məsələn, 24):", "24");
            if (duration != null && !isNaN(duration) && duration > 0) {
                var link = this.href + "&duration=" + duration;
                window.location.href = link;
            } else if (duration != null) {
                alert("Zəhmət olmasa, müsbət bir rəqəm daxil edin.");
            }
        });
    });
});
</script>

<?php
require 'admin_footer.php'; // Yeni altlığı çağırırıq
?>