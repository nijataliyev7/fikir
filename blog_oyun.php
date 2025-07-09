<?php
// Bu fayla birbaşa girişi əngəlləyirik
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    exit('Bu fayla birbaşa giriş qadağandır.');
}
?>
<div class="article-section" style="margin-top: 40px;">
    <hr>
    <?php
    // Yuxarı reklam blokunu çağırırıq
    if (file_exists('../reklam_yuxari.php')) {
        require '../reklam_yuxari.php';
    }
    ?>
    <h2>Günlük Bilgi Yarışması: Hem Eğlen Hem Puanları Topla!</h2>
    <p>
        Platformamızdaki rekabete her gün yeni bir heyecan katmak için tasarlanan "Günlük Sınav" oyunumuza hoş geldin! Bu oyun, sadece bilgini test etmekle kalmaz, aynı zamanda hızını da ödüllendirerek haftalık liderlik tablosunda yükselmen için sana harika bir fırsat sunar.
    </p>

    <h3>Oyunun Kuralları Nedir?</h3>
    <p>Oyun oldukça basit ve eğlencelidir. Karşına farklı kategorilerden toplam 3 adet soru veya görev çıkacak. Bunlar:</p>
    <ul>
        <li><strong>Atasözü:</strong> Yarım bırakılmış bir atasözünü doğru şekilde tamamlaman istenir.</li>
        <li><strong>Mantık:</strong> Zekanı ve dikkatini ölçen basit bir bilmeceyi cevaplaman gerekir.</li>
        <li><strong>Matematik:</strong> Temel bir matematiksel işlemi hızlıca çözmen beklenir.</li>
    </ul>
    <p>Her aşamayı geçmek için doğru cevabı verilen kutucuğa yazıp "Cavabla" (Cevapla) butonuna basman yeterlidir.</p>

    <?php
    // Ara reklam blokunu çağırırıq
    if (file_exists('../reklam.php')) {
        require '../reklam.php';
    }
    ?>

    <h3>Puan Sistemi Nasıl Çalışır? (Hızını Ödüllendiriyoruz!)</h3>
    <p>
        Bu oyunda sadece doğru cevap vermek yetmez, hız da çok önemlidir! Puan sistemimiz, ne kadar hızlı olursan o kadar çok puan kazanacağın şekilde tasarlanmıştır:
    </p>
    <ul>
        <li><strong>Temel Ödül:</strong> Testi başarıyla tamamlayan her kullanıcı sabit olarak <strong>+50 Puan</strong> kazanır.</li>
        <li><strong>Hız Bonusu:</strong> Bu temel puana ek olarak, tamamlama sürene göre bonuslar kazanırsın:
            <ul>
                <li><strong>20 saniyeye kadar:</strong> <span style="color: #28a745; font-weight: bold;">+50 bonus puan</span> (Toplam 100 Puan)</li>
                <li><strong>21-45 saniye arası:</strong> <span style="color: #ffc107; font-weight: bold;">+30 bonus puan</span> (Toplam 80 Puan)</li>
                <li><strong>46-90 saniye arası:</strong> <span style="color: #007bff; font-weight: bold;">+15 bonus puan</span> (Toplam 65 Puan)</li>
            </ul>
        </li>
    </ul>
    <p>
        Unutma, her gün sadece bir kez oynama hakkın var. Bu yüzden hem dikkatli hem de hızlı olmaya çalış. "Günün En Hızlı Düşüneni" listesinde adını görmek ve haftanın sonunda büyük ödülü kazanmak için her gün şansını dene! Başarılar!
    </p>

    <div style="text-align: center; margin-top: 30px;">
        <a href="<?php echo $base_url; ?>/" class="comment-button" style="text-decoration: none; display: inline-block; width: auto;">Ana Sayfaya Dön</a>
    </div>
    </div>