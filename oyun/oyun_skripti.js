// ==========================================================
// WORDLE TİPLİ OYUNUN MƏNTİQİ (Mövcud kod)
// ==========================================================
const keyClickSound = new Audio("files/keyClickSound.mp3");
const winSound = new Audio("files/winSound.mp3");
const SoundGreen = new Audio("files/SoundGreen.mp3");
const SoundGrayOrange = new Audio("files/SoundGrayOrange.mp3");
let soundEnabled = true;

document.addEventListener('DOMContentLoaded', async () => {
    const board = document.getElementById('game-board');
    const keyboard = document.getElementById('keyboard');
    
    // Elementlər tapılmazsa bu skriptin bu hissəsi işləməyəcək
    if (!board || !keyboard) return;

    let wordOfTheDay = '';
    let wordList = [];

    try {
        const wordResponse = await fetch('get_gunun_sozu.php');
        const wordData = await wordResponse.json();
        wordOfTheDay = wordData.word.toLowerCase();
        const listResponse = await fetch('sozler.json'); 
        wordList = await listResponse.json();
    } catch (error) {
        console.error("Oyun məlumatlarını yükləmək mümkün olmadı:", error);
        board.innerHTML = "<p style='color:red; text-align:center;'>Oyun yüklənərkən xəta baş verdi. Zəhmət olmasa, səhifəni yeniləyin.</p>";
        return;
    }

    const GUESS_LENGTH = 5;
    const TRIES = 6;
    let currentRow = 0;
    let currentCol = 0;
    let guesses = Array(TRIES).fill(null).map(() => Array(GUESS_LENGTH).fill(""));

    function createUI() {
        //... (Wordle UI yaratma funksiyası olduğu kimi qalır)
    }

    function handleKeyPress(key) {
        //... (Wordle hərf basma məntiqi olduğu kimi qalır)
    }
    
    // ... (Wordle oyununun qalan bütün köməkçi funksiyaları burada olduğu kimi qalır) ...

    // Oyunu başla
    createUI();
});


// ==========================================================
// GÜNDƏLİK SINAQ OYUNUNUN MƏNTİQİ (index.php-dən köçürülən yeni kod)
// ==========================================================
document.addEventListener('DOMContentLoaded', async () => {
    const stageContainer = document.getElementById('stage-container');
    
    // Əgər bu element səhifədə yoxdursa, deməli bu səhifə oyun səhifəsi deyil, skripti dayandırırıq.
    if (!stageContainer) return;

    const progressBar = document.getElementById('progress-bar-inner');
    const timerEl = document.getElementById('timer');
    let timerInterval = null;
    let stages = [];
    let currentStageIndex = 0;
    const startTime = Math.floor(Date.now() / 1000);

    function startTimer() {
        if (!timerEl) return;
        timerInterval = setInterval(() => {
            const elapsed = Math.floor(Date.now() / 1000) - startTime;
            const minutes = String(Math.floor(elapsed / 60)).padStart(2, '0');
            const seconds = String(elapsed % 60).padStart(2, '0');
            timerEl.textContent = `${minutes}:${seconds}`;
        }, 1000);
    }

    function renderCurrentStage() {
        if (currentStageIndex >= stages.length) {
            finishChallenge();
            return;
        }
        const stage = stages[currentStageIndex];
        const progressPercentage = (currentStageIndex / stages.length) * 100;
        progressBar.style.width = `${progressPercentage}%`;

        stageContainer.innerHTML = `
            <div class="game-riddle-box">
                <div class="game-category">${stage.category} (${currentStageIndex + 1}/${stages.length})</div>
                <div class="game-hint">« ${stage.sual} »</div>
            </div>
            <div id="game-input-area">
                <input type="text" id="guess-input" autocomplete="off" autofocus>
                <button id="guess-button" class="game-button">Cavabla</button>
                <p id="game-message" class="game-message"></p>
            </div>
        `;
        document.getElementById('guess-button').addEventListener('click', handleSubmit);
        document.getElementById('guess-input').addEventListener('keyup', (e) => e.key === 'Enter' && handleSubmit());
    }

    function handleSubmit() {
        const input = document.getElementById('guess-input');
        const messageEl = document.getElementById('game-message');
        const guess = input.value.trim();
        
        if (!guess) { messageEl.textContent = 'Zəhmət olmasa, bir cavab yazın.'; messageEl.className = 'game-message error'; return; }
        
        document.getElementById('guess-button').disabled = true;
        document.getElementById('guess-button').textContent = 'Yoxlanılır...';

        $.ajax({
            url: 'check_stage_answer.php', type: 'POST',
            data: { stageIndex: currentStageIndex, answer: guess },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.correct) {
                    if (soundEnabled) SoundGreen.play();
                    currentStageIndex++;
                    renderCurrentStage();
                } else {
                    if (soundEnabled) SoundGrayOrange.play();
                    messageEl.textContent = 'Təəssüf, səhv cavab. Diqqətlə düşünün!';
                    messageEl.className = 'game-message error';
                    document.getElementById('guess-button').disabled = false;
                    document.getElementById('guess-button').textContent = 'Yenidən Cəhd Et';
                }
            }
        });
    }
    
    function finishChallenge() {
        progressBar.style.width = '100%';
        if (timerInterval) clearInterval(timerInterval);
        if (soundEnabled) winSound.play();
        stageContainer.innerHTML = `<h2 class="game-message success">Təbriklər! Sınağı tamamladınız!</h2><p>Xalınız hesablanır...</p>`;
        
        $.ajax({
            url: 'claim_challenge_reward.php', type: 'POST', data: { startTime: startTime }, dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Bu funksiyalar ana script.js-dən gəldiyi üçün burada işləyəcək
                    showToast(`Təbriklər! Sınaqdan +${response.points_added} xal qazandınız!`, '🏆');
                    updateUserScoreDisplay(response.new_total_score);
                    setTimeout(() => { window.location.reload() }, 2500);
                } else { alert(response.message); }
            }
        });
    }

    async function loadChallenge() {
        try {
            const response = await fetch('get_gunun_sinagi.php');
            const data = await response.json();
            if (data.error) throw new Error(data.error);
            stages = data.stages;
            renderCurrentStage();
            startTimer();
        } catch (error) {
            stageContainer.innerHTML = `<p class="game-message error">Sınaq yüklənərkən xəta baş verdi.</p>`;
        }
    }
    
    const soundToggleBtn = document.getElementById('sound-toggle');
    if (soundToggleBtn) {
        soundToggleBtn.addEventListener('click', () => {
            soundEnabled = !soundEnabled;
            [keyClickSound, winSound, SoundGreen, SoundGrayOrange].forEach(a => a.muted = !soundEnabled);
            soundToggleBtn.textContent = soundEnabled ? '🔊' : '🔈';
        });
    }

    loadChallenge();
});
