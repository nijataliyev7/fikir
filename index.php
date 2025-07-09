<?php

// SEO Dəyişənlərini və Səhifə Başlığını Təyin Etmək
$page_title = 'Fikirler ve Yorumlar';
$page_description = "Sticker için yazılmış yaratıcı fikirler ve yorumlar";
$page_keywords = 'sticker, fikir, düşünce, yorum';



// Tüm sayfalarda gerekli olan head.php dosyasını çağırıyoruz.
require 'head.php';
?>

<div class="container">

    <?php
    if (file_exists('reklam_yuxari.php')) {
        require 'reklam_yuxari.php';
    }
    ?>

    <?php
    require 'sticker_gallery_partial.php';
    ?>

    <?php
    if (file_exists('reklam.php')) {
        require 'reklam.php';
    }
    ?>

    <?php
    require 'leaderboard_partial.php';
    ?>

    <div class="how-it-works-section">
        <hr>
        <h2>Nasıl Çalışır?</h2>
        <p class="intro-text">Sadece 3 adımda katıl, puanları topla ve haftanın kazananı ol!</p>
        
        <div class="swiper how-it-works-slider">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="step-card">
                        <div class="step-icon">✍️</div>
                        <h3>1. Fikir Belirt</h3>
                        <p>Sticker'lara göz at, en yaratıcı ve komik fikrini yaz. Unutma, en iyi fikirler ödül kazanır!</p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="step-card">
                        <div class="step-icon">❤️</div>
                        <h3>2. Beğeni Topla</h3>
                        <p>Fikrini arkadaşlarınla paylaşarak daha çok beğeni topla. En çok beğenilen fikirler haftanın kazananı olur.</p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="step-card">
                        <div class="step-icon">🏆</div>
                        <h3>3. Kazanan Ol</h3>
                        <p>Hem en çok beğeniyi toplayarak hem de puan sisteminde aktif olarak haftalık para ödülünü kazanma şansı yakala!</p>
                    </div>
                </div>
                 <div class="swiper-slide">
                    <div class="step-card">
                        <div class="step-icon">🎮</div>
                        <h3>4. Oyunda Yarış</h3>
                        <p>Her gün "Günlük Test" oyununa katıl, bilgini ve hızını göstererek ek puanlar kazan!</p>
                    </div>
                </div>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
    <?php
    // Ana səhifə üçün olan blog məzmununu burada çağırırıq.
    if (file_exists('blog_index.php')) {
        require 'blog_index.php';
    }
    ?>

</div>

<?php
// Bütün səhifələrdə lazım olan footer.php faylını çağırırıq.
require 'footer.php';?>
