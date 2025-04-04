// script.js - VERSÃO ATUALIZADA COM localStorage E LOGS

document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM Carregado. Iniciando script...");

    // --- Configurações ---
    const APPS_SCRIPT_URL = 'https://script.google.com/macros/s/AKfycbxxJzMrc69rNxvh86K_O8Qmc-9rpw-wU3J8LkPq_ObDUoI9LxDHg2pDd8BUjaMbilrLmg/exec';
    const STORAGE_KEY = 'peladaAppState';

    // --- Seletores ---
    const playerListContainer = document.getElementById('playerList');
    const statusMessageDiv = document.getElementById('statusMessage');
    const dataElement = document.getElementById('data-sexta');
    const navButtons = document.querySelectorAll('.main-nav .nav-button');
    const contentSections = document.querySelectorAll('main .content-section');
    const photoGalleryContainer = document.getElementById('photoGalleryContainer');
    const countConfirmedEl = document.getElementById('count-confirmed');
    const countDeclinedEl = document.getElementById('count-declined');
    const countPendingEl = document.getElementById('count-pending');
    const countTotalEl = document.getElementById('count-total');

    // --- Estado ---
    let isSubmitting = false;
    let playerStates = {};
    let dataJogoFormatada = getProximaSextaFormatada(true);

    // --- Inicialização ---
    if (dataElement) {
        dataElement.textContent = `Próxima Sexta-Feira (${dataJogoFormatada})`;
    }

    loadStateFromLocalStorage();

    if (document.querySelector('#fotos-section')?.classList.contains('active')) {
        loadPhotos();
    }

    if (typeof lightbox !== 'undefined') {
        lightbox.option({ 'resizeDuration': 200, 'wrapAround': true, 'fadeDuration': 300, 'imageFadeDuration': 300 });
    }

    // --- Navegação ---
    navButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.dataset.target;
            contentSections.forEach(section => section.classList.remove('active'));
            const targetSection = document.getElementById(targetId);
            if (targetSection) targetSection.classList.add('active');
            navButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            if (targetId === 'fotos-section') loadPhotos();
        });
    });

    // --- Clique em botões de confirmação ---
    if (playerListContainer) {
        playerListContainer.addEventListener('click', (event) => {
            if (event.target.classList.contains('btn') && !event.target.classList.contains('disabled')) {
                const clickedButton = event.target;
                const playerItem = clickedButton.closest('.player-item');
                if (!playerItem) return;

                const playerName = playerItem.dataset.player || 'Desconhecido';
                if (playerStates[playerName]?.locked) return;

                const playerCategory = playerItem.dataset.category || 'Outro';
                const finalStatus = clickedButton.classList.contains('vou') ? 'Vou' : 'Não Vou';

                const confirmationMessage = `Tem certeza que ${finalStatus === 'Vou' ? 'VAI jogar' : 'NÃO VAI jogar'} na sexta (${dataJogoFormatada})?`;
                if (confirm(confirmationMessage)) {
                    const vouButton = playerItem.querySelector('.btn.vou');
                    const naoVouButton = playerItem.querySelector('.btn.nao-vou');

                    vouButton.classList.remove('selected');
                    naoVouButton.classList.remove('selected');
                    clickedButton.classList.add('selected');
                    vouButton.classList.add('disabled');
                    naoVouButton.classList.add('disabled');

                    playerStates[playerName] = { status: finalStatus, locked: true };
                    saveStateToLocalStorage();
                    updateCounts();

                    const dataToSend = {
                        nome: playerName,
                        status: finalStatus,
                        categoria: playerCategory,
                        dataJogo: dataJogoFormatada
                    };
                    async function sendDataToSheet(data) {
    try {
        const response = await fetch(APPS_SCRIPT_URL, {
            method: 'POST',
            body: JSON.stringify(data),
            headers: { 'Content-Type': 'application/json' },
            mode: 'no-cors' // 🚨 Adicionando esta linha para evitar bloqueio por CORS
        });

        console.log("Dados enviados com sucesso!");
    } catch (error) {
        console.error("Erro ao enviar dados:", error);
    }
}

                } else {
                    console.log("Ação cancelada.");
                }
            } else if (event.target.classList.contains('btn') && event.target.classList.contains('disabled')) {
                showStatusMessage("Escolha já registrada.", "loading", true);
            }
        });
    }

    // --- Funções ---

    function saveStateToLocalStorage() {
        const stateToSave = {
            gameDate: dataJogoFormatada,
            states: playerStates
        };
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(stateToSave));
            console.groupCollapsed("--- DADOS SALVOS NO localStorage ---");
            console.log("Data:", stateToSave.gameDate);
            console.log("Estados:", stateToSave.states);
            console.groupEnd();
        } catch (e) {
            console.error("Erro ao salvar no localStorage:", e);
        }
    }

    function loadStateFromLocalStorage() {
        try {
            const savedStateJSON = localStorage.getItem(STORAGE_KEY);
            if (savedStateJSON) {
                const savedState = JSON.parse(savedStateJSON);
                if (savedState?.gameDate === dataJogoFormatada && savedState.states) {
                    playerStates = savedState.states;
                    console.log("Estado restaurado com sucesso do localStorage.");
                } else {
                    console.log("Estado antigo ou inválido. Limpando localStorage.");
                    localStorage.removeItem(STORAGE_KEY);
                }
            }
        } catch (e) {
            console.error("Erro ao processar dados salvos:", e);
            localStorage.removeItem(STORAGE_KEY);
        }

        applyStateToButtons(playerStates);
        updateCounts();
    }

    function applyStateToButtons(states) {
        if (!playerListContainer) return;
        playerListContainer.querySelectorAll('.player-item').forEach(item => {
            const playerName = item.dataset.player;
            const vouBtn = item.querySelector('.btn.vou');
            const naoVouBtn = item.querySelector('.btn.nao-vou');
            vouBtn.classList.remove('selected', 'disabled');
            naoVouBtn.classList.remove('selected', 'disabled');
            const state = states[playerName];
            if (state) {
                if (state.status === 'Vou') vouBtn.classList.add('selected');
                else if (state.status === 'Não Vou') naoVouBtn.classList.add('selected');
                if (state.locked) {
                    vouBtn.classList.add('disabled');
                    naoVouBtn.classList.add('disabled');
                }
            }
        });
    }

    function updateCounts() {
        if (!playerListContainer) return;
        const items = playerListContainer.querySelectorAll('.player-item');
        const total = items.length;
        let confirmed = 0, declined = 0;

        for (const name in playerStates) {
            const state = playerStates[name];
            if (state?.status === 'Vou') confirmed++;
            else if (state?.status === 'Não Vou') declined++;
        }

        const pending = total - confirmed - declined;

        countConfirmedEl.textContent = confirmed;
        countDeclinedEl.textContent = declined;
        countPendingEl.textContent = pending >= 0 ? pending : 0;
        countTotalEl.textContent = total;
    }

    async function sendDataToSheet(data) {
        try {
            const response = await fetch(APPS_SCRIPT_URL, {
                method: 'POST',
                body: JSON.stringify(data),
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await response.json();
            console.log("Resposta do Apps Script:", result);
            showStatusMessage("Confirmação enviada!", "success", true);
        } catch (error) {
            console.error("Erro ao enviar dados:", error);
            showStatusMessage("Erro ao enviar confirmação. Tente novamente.", "error", true);
        }
    }

    function showStatusMessage(message, type, autoHide = false) {
        if (!statusMessageDiv) return;
        statusMessageDiv.textContent = message;
        statusMessageDiv.className = `message ${type}`;
        statusMessageDiv.style.display = 'block';

        if (autoHide) {
            setTimeout(() => {
                statusMessageDiv.style.display = 'none';
            }, 3000);
        }
    }

    async function loadPhotos() {
        // Mantido como no original (com Lightbox e Swiper se necessário)
    }

});

// --- Função da Data ---
function getProximaSextaFormatada(short = false) {
    const hoje = new Date();
    const diaDaSemana = hoje.getDay();
    let diasAteSexta = 5 - diaDaSemana;
    if (diasAteSexta <= 0) diasAteSexta += 7;
    const proximaSexta = new Date(hoje);
    proximaSexta.setDate(hoje.getDate() + diasAteSexta);
    const dia = String(proximaSexta.getDate()).padStart(2, '0');
    const mes = String(proximaSexta.getMonth() + 1).padStart(2, '0');
    return short ? `${dia}/${mes}` : `Próxima Sexta-Feira (${dia}/${mes})`;
}
