# Fikir Layihəsi

Bu repozitoriyada stikerlərə fikir yazmaq və rəylər toplamaq üçün hazırlanmış PHP tətbiqi yerləşir. Layihədə Google ilə daxilolma, e-poçt bildirişləri və istifadəçi idarəetməsi kimi funksiyalar mövcuddur.

## Tələblər

- **PHP** 8.0 və ya daha yeni versiya
- **Composer** paket meneceri
- **MySQL** verilənlər bazası

## Quraşdırma

1. Repozitoriyanı klonlayın və kök qovluqda `composer install` əmrini işlədirək, lazımi paketlər qurulacaq.
2. Layihənin kökündə `.env` faylı yaradın və aşağıda sadalanan mühit dəyişənlərini doldurun.
3. Verilənlər bazasında `DB_NAME` dəyərinə uyğun baza yaradın.
4. PHP serverini istənilən veb serverdə qurun və ya inkişaf mühiti üçün `php -S localhost:8000` əmrindən istifadə edin.

## Mühit Dəyişənləri

### db.php
- `DB_HOST` – MySQL serverinin ünvanı
- `DB_USER` – verilənlər bazası istifadəçi adı
- `DB_PASS` – verilənlər bazası parolu
- `DB_NAME` – istifadə olunacaq baza adı

### email_sender.php
- `GMAIL_USER` – bildirişlər göndəriləcək Gmail hesabı
- `GMAIL_APP_PASSWORD` – həmin hesab üçün yaradılmış tətbiq parolu

### google-auth.php
- `GOOGLE_CLIENT_ID` – Google OAuth müştəri identifikatoru
- `GOOGLE_CLIENT_SECRET` – Google OAuth gizli açarı


