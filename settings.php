<?php
// settings.php
require 'admin_header.php';

// Mövcud dəyərləri bazadan oxumaq
$settings_result = $conn->query("SELECT * FROM settings");
$settings = [];
while($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="content-header"><h1>Ayarlar</h1></div>
<div class="content-box">
    <?php if(isset($_GET['success'])): ?>
        <p class="success" style="padding: 15px; border-radius: 5px; background-color: #d4edda; color: #155724;">Ayarlar uğurla yeniləndi!</p>
    <?php endif; ?>

    <form action="update_settings.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <label for="comments_per_page">Hər Skrolda Yüklənəcək Rəy Sayı:</label>
        <input type="number" id="comments_per_page" name="settings[comments_per_page]" value="<?php echo htmlspecialchars($settings['comments_per_page'] ?? '15'); ?>" required min="1">
        
        <label for="weekly_winner_prize">Həftənin Qalibi üçün Mükafat:</label>
        <input type="text" id="weekly_winner_prize" name="settings[weekly_winner_prize]" value="<?php echo htmlspecialchars($settings['weekly_winner_prize'] ?? '10 AZN'); ?>">

        <button type="submit">Yadda Saxla</button>
    </form>
</div>

<?php
require 'admin_footer.php';?>
