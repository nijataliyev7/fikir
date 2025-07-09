$(document).ready(function() {

    // ==========================================================
    // BÖLMƏ 1: BÜTÜN SƏHİFƏLƏRDƏ İŞLƏYƏN ÜMUMİ KODLAR
    // ==========================================================

    // Sticker Qalereyasındakı GIF animasiyası
    var stickerGallery = $('.sticker-gallery');
    stickerGallery.on('mouseenter', '.gif-poster', function() {
        var $img = $(this);
        var animatedSrc = $img.data('gif');
        if ($img.attr('src') !== animatedSrc) {
            $img.attr('src', animatedSrc);
        }
    });
    stickerGallery.on('mouseleave', '.gif-poster', function() {
        var $img = $(this);
        var posterSrc = $img.data('poster');
        $img.attr('src', posterSrc);
    });

    // WhatsApp Formu
    $(document).on('input', '#whatsapp_number_input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    $('#whatsapp-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var userInput = $('#whatsapp_number_input').val();
        var fullNumber = '+994' + userInput;
        $('#full_whatsapp_number').val(fullNumber);
        var button = form.find('button[type="submit"]');
        var messageDiv = $('#whatsapp-message');
        button.prop('disabled', true).text('Gözləyin...');
        $.ajax({
            type: 'POST',
            url: 'save_whatsapp.php',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    messageDiv.text('Təşəkkür edirik!').css('color', '#28a745');
                    $('#whatsapp-prompt').slideUp(500);
                } else {
                    messageDiv.text(response.message || 'Xəta baş verdi.').css('color', '#dc3545');
                    button.prop('disabled', false).text('Yadda Saxla');
                }
            },
            error: function() {
                messageDiv.text('Serverlə əlaqə qurmaq mümkün olmadı.').css('color', '#dc3545');
                button.prop('disabled', false).text('Yadda Saxla');
            }
        });
    });

    // Bildiriş Sistemi
    $('#notification-bell').on('click', function(e) {
        e.stopPropagation();
        var dropdown = $('.notification-dropdown');
        var isOpen = dropdown.is(':visible');
        dropdown.toggle();
        if (!isOpen && !dropdown.data('loaded')) {
            $('#notification-list').html('<li><a href="#">Yüklənir...</a></li>');
            $.ajax({
                url: 'notifications_handler.php?action=fetch',
                type: 'GET',
                dataType: 'json',
                success: function(notifications) {
                    var list = $('#notification-list');
                    list.empty();
                    if (notifications.length === 0) {
                        list.html('<li><a href="#">Yeni bildiriş yoxdur.</a></li>');
                    } else {
                        notifications.forEach(function(n) {
                            var text = '';
                            var link = n.sticker_id + '?sort=new#comment-' + n.comment_id;
                            if (n.type === 'like') { text = '<span class="actor-name">' + n.actor_name + '</span> sizin rəyinizi bəyəndi.'; } 
                            else if (n.type === 'reply') { text = '<span class="actor-name">' + n.actor_name + '</span> sizin rəyinizə cavab yazdı.'; }
                            var item = '<li class="is_read_' + n.is_read + '"><a href="' + link + '">' + text + '</a></li>';
                            list.append(item);
                        });
                    }
                    dropdown.data('loaded', true);
                    setTimeout(function() {
                        $('.notification-count').fadeOut(500, function() { $(this).remove(); });
                        $.post('notifications_handler.php?action=mark_read');
                    }, 2000);
                }
            });
        }
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.notification-area').length) {
            $('.notification-dropdown').hide();
        }
    });
    
    // DİNAMİK XAL BİLDİRİŞİ (TOAST)
    function showToast(message, icon = '⭐') {
        const toastContainer = $('#toast-container');
        if (!toastContainer.length) return;
        const toast = $(`<div class="toast"><div class="toast-icon">${icon}</div><div class="toast-message">${message}</div></div>`);
        toastContainer.append(toast);
        setTimeout(function() { toast.addClass('show'); }, 100);
        setTimeout(function() {
            toast.removeClass('show');
            setTimeout(function() { toast.remove(); }, 500);
        }, 4000);
    }
    function updateUserScoreDisplay(newScore) {
        if ($('.user-score-display').length) {
            $('.user-score-display').html(`⭐ ${newScore} Xal`);
        }
    }
    var flashMessage = $('body').data('flash-message');
    if (flashMessage) {
        showToast(flashMessage, '🎉');
    }

    // ===============================================================
    // BÖLMƏ 2: YALNIZ STİKER SƏHİFƏSİ ÜÇÜN OLAN KOD (view_sticker.php)
    // ===============================================================
    if (typeof stickerId !== 'undefined') {

        // --- CANLI HƏRF SAYĞACI ---
        if ($('#comment').length) {
            var commentBox = $('#comment');
            var charCounter = $('#char-counter');
            var limit = 21;
            commentBox.on('input', function() {
                var text = commentBox.val(); var nonSpaceCount = 0; var limitExceededAtIndex = -1;
                for (var i = 0; i < text.length; i++) {
                    if (text[i].match(/[^\s]/)) {
                        nonSpaceCount++;
                        if (nonSpaceCount > limit) { limitExceededAtIndex = i; break; }
                    }
                }
                if (limitExceededAtIndex !== -1) {
                    commentBox.val(text.substring(0, limitExceededAtIndex));
                    nonSpaceCount = limit;
                }
                var remaining = limit - nonSpaceCount;
                charCounter.text(remaining);
                var wrapper = commentBox.closest('.textarea-wrapper');
                if (remaining <= 0) { wrapper.addClass('has-error'); } 
                else { wrapper.removeClass('has-error'); }
            });
        }

        // --- YARIŞMA ÜÇÜN GERİ SAYIM ---
        if ($('#contest-timer').length) {
            var timerElement = $('#contest-timer');
            var endTime = new Date(timerElement.data('end-time').replace(/-/g, '/')).getTime();
            var timerInterval = setInterval(function() {
                var now = new Date().getTime(); var distance = endTime - now;
                if (distance < 0) {
                    clearInterval(timerInterval);
                    timerElement.html("🏁 Yarışma başa çatdı!");
                    setTimeout(function(){ location.reload(); }, 2000);
                    return;
                }
                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                var timerText = "Mükafatı qazanmaq üçün son: ";
                if (days > 0) timerText += days + " gün ";
                if (hours > 0 || days > 0) timerText += hours + " saat ";
                if (minutes > 0 || hours > 0 || days > 0) timerText += minutes + " dəqiqə ";
                timerText += seconds + " saniyə";
                timerElement.html('<span class="timer-icon">⏳</span> ' + timerText);
            }, 1000);
        }

        // --- ƏSAS RƏY GÖNDƏRMƏ FORMASI ---
        $('#comment-form').on('submit', function(e) {
            e.preventDefault();
            var text = $('#comment').val(); var charCount = text.replace(/\s/g, '').length;
            if (charCount > 21) {
                $('#form-message').html('Fikiriniz 21 hərfdən çox ola bilməz! (boşluqlar sayılmır)').css('color', 'red');
                return;
            }
            var form = $(this); var messageDiv = $('#form-message'); var button = form.find('button[type="submit"]');
            button.prop('disabled', true).text('Göndərilir...');
            $.ajax({
                type: 'POST', url: 'add_comment.php', data: form.serialize(), dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        if (response.points_earned > 0) {
                            showToast(`+${response.points_earned} xal qazandınız! Ümumi: ${response.new_total_score}`);
                            updateUserScoreDisplay(response.new_total_score);
                        }
                        var newUrl = window.location.pathname.replace('index.php', '') + '?sort=new#comment-' + response.new_comment_id;
                        setTimeout(function(){ window.location.href = newUrl; }, 500);
                    } else {
                        if (response.status === 'login_required') { $('#loginModal').show(); } 
                        else {
                            var error_text = response.message || 'Naməlum xəta';
                            if(error_text === 'empty') error_text = 'Fikiriniz boş buraxıla bilməz!';
                            if(error_text === 'time_limit') error_text = 'Növbəti rəyi yazmaq üçün 1 dəqiqə gözləməlisiniz.';
                            if(error_text === 'bad_word_found') error_text = 'Təhqir olmaz!';
                            if (error_text === 'char_limit') error_text = 'Fikiriniz 21 hərfdən çox ola bilməz! (boşluqlar sayılmır)';
                            if (error_text === 'contest_finished') error_text = 'Bu yarışma artıq başa çatıb!';
                            messageDiv.html(error_text).css('color', 'red');
                        }
                        button.prop('disabled', false).text('Göndər');
                    }
                },
                error: function() { messageDiv.html('Serverlə əlaqə qurmaq mümkün olmadı.').css('color', 'red'); button.prop('disabled', false).text('Göndər'); }
            });
        });
        
        // --- BƏYƏNMƏ DÜYMƏSİ (Düzgün import ilə) ---
       $('#comments-container').on('click', '.like-btn', function() {
            var button = $(this);
            var comment_id = button.data('id');
            button.prop('disabled', true);
            var originalButtonText = button.html();

            $.ajax({
                type: 'POST',
                url: 'like_comment.php',
                data: {
                    id: comment_id,
                    sticker_id: stickerId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        button.css('cursor', 'not-allowed');
                        button.html(originalButtonText).find('.like-count').text(response.likes);

                        if (response.points_earned > 0) {
                            showToast(`+${response.points_earned} xal qazandınız! Ümumi: ${response.new_total_score}`);
                            updateUserScoreDisplay(response.new_total_score);
                        }
                    } else {
                        if (response.status === 'login_required') {
                            $('#loginModal').show();
                        }
                        if (response.status === 'already_liked' || response.message === 'Bu günlük bəyənmə limitiniz bitib.') {
                            button.css('cursor', 'not-allowed');
                        } else {
                            button.prop('disabled', false);
                        }
                        showToast(response.message || 'Xəta baş verdi', '❗');
                    }
                },
                error: function() {
                    button.prop('disabled', false);
                    showToast('Serverlə əlaqə qurmaq mümkün olmadı.', '❗');
                }
            });
        });
        
        // --- ADMİN/İSTİFADƏÇİ SİLMƏ DÜYMƏSİ ---
        $('#comments-container').on('click', '.admin-delete-btn', function() {
            if (!confirm('Bu rəyi və ona yazılan bütün cavabları silməyə əminsiniz?')) { return; }
            var button = $(this); var comment_id = button.data('id'); var comment_element = button.closest('.comment');
            $.ajax({
                type: 'POST', url: 'delete_comment.php', data: { comment_id: comment_id }, dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') { comment_element.fadeOut(500, function() { $(this).remove(); }); } 
                    else { alert("Xəta: " + response.message); }
                },
                error: function() { alert("Rəyi silmək mümkün olmadı."); }
            });
        });

        // --- POP-UP PƏNCƏRƏ ---
        var modal = $('#loginModal');
        $('.close-button').on('click', function() { modal.hide(); });
        $(window).on('click', function(event) { if ($(event.target).is(modal)) { modal.hide(); } });

        // --- CAVABLAMA SİSTEMİ ("Rəyə baxmaq", "Cavab yazmaq") ---
        $('#comments-container').on('click', '.toggle-replies-btn', function() {
            var button = $(this); var parentId = button.data('parent-id'); var repliesWrapper = button.closest('.comment').find('.replies-wrapper');
            button.toggleClass('toggled-on');
            if (repliesWrapper.data('loaded') === true) {
                repliesWrapper.slideToggle();
                var replyCount = button.data('reply-count') || 0;
                button.text(repliesWrapper.is(':visible') ? '💬 Cavabları gizlət' : '💬 ' + replyCount + ' cavab');
                return;
            }
            button.text('Yüklənir...').prop('disabled', true);
            $.ajax({
                url: 'get_replies.php', type: 'GET', data: { parent_id: parentId, sticker_id: stickerId },
                success: function(response) {
                    var replyCount = $(response).find('.comment').length;
                    repliesWrapper.html(response).slideDown();
                    repliesWrapper.data('loaded', true);
                    button.prop('disabled', false);
                    button.data('reply-count', replyCount);
                    button.text('💬 Cavabları gizlət');
                },
                error: function() { button.text('Xəta baş verdi').prop('disabled', false); button.removeClass('toggled-on');}
            });
        });
        $('#comments-container').on('click', '.reply-btn', function() {
            var button = $(this); var commentDiv = button.closest('.comment'); var commentId = commentDiv.data('comment-id');
            var formContainer = commentDiv.find('.reply-form-container');
            $('.reply-form-container').not(formContainer).html('');
            if (formContainer.is(':empty')) {
                var formTemplate = $('#reply-form-template').html();
                formContainer.html(formTemplate);
                formContainer.find('.parent-id-input').val(commentId);
                formContainer.find('textarea').focus();
            } else {
                formContainer.html('');
            }
        });
        $('#comments-container').on('click', '.cancel-reply-btn', function() {
            $(this).closest('.reply-form-container').html('');
        });
        $('#comments-container').on('submit', '.reply-form', function(e) {
            e.preventDefault();
            var form = $(this); var button = form.find('button[type="submit"]');
            button.prop('disabled', true).text('Göndərilir...');
            $.ajax({
                type: 'POST', url: 'add_comment.php', data: form.serialize(), dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        if (response.points_earned > 0) {
                            showToast(`+${response.points_earned} xal qazandınız! Ümumi: ${response.new_total_score}`);
                            updateUserScoreDisplay(response.new_total_score);
                        }
                        var newUrl = window.location.pathname.replace('index.php', '') + '?sort=new#comment-' + response.new_comment_id;
                        setTimeout(function(){ window.location.href = newUrl; }, 500);
                    } else {
                        if (response.status === 'login_required') { $('#loginModal').show(); } 
                        else { alert('Xəta: ' + (response.message || 'Naməlum xəta')); }
                        button.prop('disabled', false).text('Göndər');
                    }
                },
                error: function() { alert('Server xətası.'); button.prop('disabled', false).text('Göndər'); }
            });
        });

        // --- MOBİL CİHAZLAR ÜÇÜN ---
        $('#comments-container, #comment-form').on('focus', 'textarea', function() {
            var input = this;
            setTimeout(function() {
                input.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        });
        
        // --- BİRBAŞA RƏYƏ KEÇİD VƏ SARI YANIB-SÖNMƏ ---
        function handleDirectLinkToComment() {
            var hash = window.location.hash;
            if (hash && hash.startsWith('#comment-')) {
                var targetCommentId = hash.substring(9);
                var urlParams = new URLSearchParams(window.location.search);
                var currentSort = urlParams.get('sort') || 'best';
                $.ajax({
                    url: 'find_parent.php', type: 'GET', data: { comment_id: targetCommentId, sticker_id: stickerId, sort: currentSort }, dataType: 'json',
                    success: function(response) {
                        if (response.final_url) {
                            var currentRelativeUrl = window.location.pathname + window.location.search + window.location.hash;
                            if (currentRelativeUrl !== response.final_url) {
                                window.location.href = response.final_url;
                                return;
                            }
                        }
                        if (response.parent_id) {
                            var toggleButton = $('.toggle-replies-btn[data-parent-id="' + response.parent_id + '"]');
                            if (toggleButton.length && !toggleButton.hasClass('toggled-on')) {
                                toggleButton.click();
                            }
                        }
                        var attempts = 0;
                        var checkExist = setInterval(function() {
                            attempts++;
                            if ($(hash).length || attempts > 50) {
                                clearInterval(checkExist);
                                if ($(hash).length) {
                                    $('html, body').animate({ scrollTop: $(hash).offset().top - 100 }, 500);
                                    $(hash).css('background-color', '#fffbeb');
                                    setTimeout(function() {
                                        $(hash).css('transition', 'background-color 1s').css('background-color', '');
                                    }, 2000);
                                }
                            }
                        }, 100);
                    }
                });
            }
        }
        handleDirectLinkToComment();

    } // `if (typeof stickerId !== 'undefined')` blokunun sonu


    // ======================================================================
    // BÖLMƏ 3: YALNIZ ANA SƏHİFƏ ÜÇÜN OLAN KOD (index.php)
    // ======================================================================
    if (document.querySelector('.how-it-works-slider')) {
        var swiper = new Swiper('.how-it-works-slider', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            autoplay: {
              delay: 4000,
              disableOnInteraction: false,
              pauseOnMouseEnter: true,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                768: { slidesPerView: 2, spaceBetween: 20 },
                1024: { slidesPerView: 3, spaceBetween: 30 }
            }
        });
    }

    // ==========================================================
    // BÖLMƏ 4: "YENİ FIRSATLAR" BANNERİNİN MƏNTİQİ
    // ==========================================================
    const banner = document.getElementById('opportunity-banner');

    // Əgər banner HTML-də mövcuddursa (yəni yeni gündürsə)
    if (banner) {
        const closeBtn = document.getElementById('close-banner-btn');
        // Bu günün tarixini "YYYY-MM-DD" formatında alırıq
        const todayStr = new Date().toISOString().split('T')[0];

        // Brauzer yaddaşında bu günün tarixinin saxlanıb-saxlanmadığını yoxlayırıq
        const isHiddenForToday = localStorage.getItem('hideOpportunityBannerFor') === todayStr;

        // Əgər bu gün üçün artıq gizlədilibsə, heç nə etmirik (banner onsuz da `display:none` ilə yaradılıb)
        // Əgər gizlədilməyibsə, banneri göstəririk
        if (!isHiddenForToday) {
            banner.style.display = 'block';
        }

        // Bağlama düyməsinə kliklədikdə
        if(closeBtn) {
            closeBtn.addEventListener('click', function() {
                // Banneri gizlədirik
                banner.style.display = 'none';
                // Brauzer yaddaşına bu günün tarixini yazırıq ki, səhifə yenilənəndə təkrar görünməsin
                localStorage.setItem('hideOpportunityBannerFor', todayStr);
            });
        }
    }
    
}); // Əsas $(document).ready() funksiyasının sonu
