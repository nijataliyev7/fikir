<?php
// xal/versus_block_view.php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    exit('Bu dosyaya doğrudan erişim yasaktır.');
}
require_once __DIR__ . '/versus_logic.php';
$vs_data = get_versus_data($conn);

if ($vs_data):
    $leader = $vs_data['leader'];
    $challenger = $vs_data['challenger'];
    $leader_percentage = 0;
    $total_score = $leader['activity_score'] + $challenger['activity_score'];
    if ($total_score > 0) {
        $leader_percentage = ($leader['activity_score'] / $total_score) * 100;
    }
?>
<div class="vs-block-container">
    <div class="vs-block-title">
        <h2>🔥 10 Manat Kazanmak İçin Haftalık Mücadele! 🔥</h2>
        <p>Haftanın sonunda en çok puanı toplayan kazanır!</p>
    </div>
    <div class="vs-block">
        <div class="vs-block__player vs-block__player--leader">
            <a href="<?php echo $base_url; ?>/profil.php?id=<?php echo $leader['id']; ?>" class="vs-block__user-info" title="<?php echo htmlspecialchars($leader['name']); ?>">
                <img src="<?php echo htmlspecialchars($leader['profile_picture_url']); ?>" alt="<?php echo htmlspecialchars($leader['name']); ?>">
                <span><?php echo htmlspecialchars($leader['name']); ?></span>
            </a>
            <div class="vs-block__score vs-block__score--leader">
                <?php echo $leader['activity_score']; ?>
            </div>
        </div>

        <div class="vs-block__center">
            <span>VS</span>
        </div>

        <div class="vs-block__player vs-block__player--challenger">
            <div class="vs-block__score vs-block__score--challenger">
                <?php echo $challenger['activity_score']; ?>
            </div>
             <a href="<?php echo $base_url; ?>/profil.php?id=<?php echo $challenger['id']; ?>" class="vs-block__user-info" title="<?php echo htmlspecialchars($challenger['name']); ?>">
                <span><?php echo htmlspecialchars($challenger['name']); ?></span>
                <img src="<?php echo htmlspecialchars($challenger['profile_picture_url']); ?>" alt="<?php echo htmlspecialchars($challenger['name']); ?>">
            </a>
        </div>
    </div>

    <div class="vs-block__progress" 
         style="background: linear-gradient(75deg, #4f46e5 <?php echo $leader_percentage; ?>%, #be123c <?php echo $leader_percentage; ?>%);">
    </div>
    
    <div class="vs-block-cta">
        <a href="<?php echo $base_url; ?>/puan-kazan.php" class="how-to-earn-points-link">⭐️ Xalları Topla</a>
    </div>
</div>
<?php endif; ?>