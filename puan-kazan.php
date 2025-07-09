<?php
$page_title = 'Puan Kazan & Kurallar';
require 'head.php';

// Əgər istifadəçi daxil olubsa, onun statistikalarını çəkirik
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT activity_score, daily_likes_left, daily_replies_left FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<div class="container">
    <div class="article-section" style="text-align: left;">
        
        <a href="<?php echo $base_url; ?>/" class="back-link" style="text-decoration: none; display: inline-block; margin-bottom: 15px;">← Ana Sayfaya Dön</a>

        <h1>Puan Kazan & Kurallar</h1>

        <?php if (isset($user_stats)): ?>
        <p>Aşağıda mevcut puan durumunu ve günlük haklarını görebilirsin.</p>
        <div class="profile-stats-grid" style="margin-bottom: 30px;">
            <div class="stat-box">
                <h3>Toplam Puanın</h3>
                <div class="stat-value">⭐ <?php echo $user_stats['activity_score']; ?></div>
            </div>
            <div class="stat-box">
                <h3>Günlük Beğeni Hakkın</h3>
                <div class="stat-value">❤️ <?php echo $user_stats['daily_likes_left']; ?></div>
            </div>
            <div class="stat-box">
                <h3>Günlük Cevap Hakkın</h3>
                <div class="stat-value">💬 <?php echo $user_stats['daily_replies_left']; ?></div>
            </div>
        </div>
        <hr>
        <?php endif; ?>
        
             <?php
    // Ara reklam blokunu çağırırıq
    if (file_exists('reklam.php')) {
        require 'reklam.php';
    }
    ?>
        
         <?php
    // Ara reklam blokunu çağırırıq
    if (file_exists('../reklam.php')) {
        require '../reklam.php';
    }
    ?>

        <h2>Nasıl Puan Kazanırım? (Tam Rehber)</h2>
        <?php // ... qalan bütün məzmun ... ?>
        <p>Platformamızda aktif olarak haftalık ödülü kazanma şansını artırabilirsin. Puan sistemi, aktif ve faydalı katılımcıları ödüllendirmek için tasarlanmıştır.</p>
        
        <h3>🏆 Günlük Test (Oyun)</h3>
        <p>Her gün bir kez oynayabileceğin "Günlük Test" oyununu tamamlayarak ek puanlar kazanabilirsin. Ödülün, testi bitirme hızına bağlıdır:</p>
        <ul>
            <li><strong>Temel Ödül:</strong> Testi başarıyla tamamlamak sana <strong>+50 Puan</strong> kazandırır.</li>
            <li><strong>Hız Bonusu:</strong> Ek olarak, hızına göre bonuslar verilir:
                <ul>
                    <li>20 saniyeye kadar tamamlama: <strong>+50 bonus puan</strong> (Toplam 100 Puan)</li>
                    <li>21-45 saniye arası tamamlama: <strong>+30 bonus puan</strong> (Toplam 80 Puan)</li>
                    <li>46-90 saniye arası tamamlama: <strong>+15 bonus puan</strong> (Toplam 65 Puan)</li>
                </ul>
            </li>
        </ul>
        <a href="oyun/" class="comment-button" style="text-decoration: none; display: inline-block; margin-top: 15px;">Oyna Kazan</a>
        <hr>

        <h3>📅 Günlük Aktivite</h3>
        <p>Platforma her gün giriş yapmak sana sürekli olarak puan kazandırır.</p>
        <ul>
            <li><strong>Günlük Giriş Bonusu:</strong> Siteye her gün ilk defa giriş yaptığında <strong>+50 Puan</strong> kazanırsın.</li>
            <li><strong>Giriş Zinciri (Streak):</strong> Eğer siteye birkaç gün art arda giriş yaparsan, bonusun artar! Her sonraki gün için bonusuna <strong>+10 Puan</strong> eklenir (maksimum 100 puana kadar).</li>
        </ul>
        <hr>

        <h3>❤️ Akıllı Beğeniler (Beğenen Kullanıcı İçin)</h3>
        <p>Başkalarının yorumlarını beğenerek de puan kazanabilirsin.</p>
        <ul>
            <li><strong>"Keşif Bonusu":</strong> Bir yorumu ilk defa sen beğenirsen, <strong>+3 Puan</strong> kazanırsın.</li>
            <li><strong>"Destek Bonusu":</strong> 1-10 arası beğenisi olan bir yorumu beğendiğinde <strong>+2 Puan</strong> kazanırsın.</li>
            <li><strong>"Popüler Onayı":</strong> 10'dan fazla beğenisi olan popüler bir yorumu beğendiğinde <strong>+1 Puan</strong> kazanırsın.</li>
        </ul>
        <p><em>Not: Günlük 10 beğeni limitin var.</em></p>
        <hr>

        <h3>💬 Faydalı Cevaplar</h3>
        <p>Aktif şekilde tartışmalara katılmak en çok puan getiren faaliyetlerden biridir.</p>
        <ul>
            <li><strong>Cevap Yazmak:</strong> Başkasının yorumuna cevap yazdığın için <strong>+5 Puan</strong> kazanırsın (günlük 5 cevap limiti var).</li>
            <li><strong>Cevabının Beğenilmesi:</strong> Senin yazdığın bir cevap başkası tarafından beğenilirse, her bir beğeni için sana <strong>+3 Puan</strong> gelir.</li>
        </ul>
        <hr>
        <?php
    // Ara reklam blokunu çağırırıq
    if (file_exists('reklam_yuxari.php')) {
        require 'reklam_yuxari.php';
    }
    ?><?php
    // Ara reklam blokunu çağırırıq
    if (file_exists('../reklam_yuxari.php')) {
        require '../reklam_yuxari.php';
    }
    ?>
        
        <h3>❌ Neler Puan Kazandırmaz?</h3>
        <ul>
            <li><strong>Ana Fikir Yazmak:</strong> Ana fikir (ilk yorum) yazmak doğrudan puan vermez.</li>
            <li><strong>Kendi Yorumunu Beğenmek:</strong> Kullanıcılar kendi yorumlarını veya cevaplarını beğenerek puan kazanamazlar.</li>
        </ul>
        
        <p>Başarılar!</p>
    </div>
</div>
<?php require 'footer.php'; ?>
