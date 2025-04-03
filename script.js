document.addEventListener('DOMContentLoaded', () => {

    // --- Configurações ---
    const APPS_SCRIPT_URL = 'https://script.google.com/macros/s/AKfycbwVBXpCYZIb8n04UG7ib5ayb2K5BKXWJkYU3iqG5qlkB_Sf6VNVgG1Gd3_18MhTJ2w0oQ/exec'; // CONFIRA SEU URL
    const STORAGE_KEY = 'peladaAppState';

    // --- Seletores de Elementos ---
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


    loadStateFromLocalStorage(); // Carrega estado, aplica visual e atualiza contadores
    loadPhotos(); // Carrega fotos na inicialização

    // --- Lógica de Navegação ---
    navButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetId = button.dataset.target;
            contentSections.forEach(section => section.classList.remove('active'));
            const targetSection = document.getElementById(targetId);
            if (targetSection) targetSection.classList.add('active');
            navButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            // Recarrega fotos se for para a seção de fotos
            if (targetId === 'fotos-section') { loadPhotos(); }
        });
    });

    // --- Lógica de Confirmação ---

    function saveStateToLocalStorage() {
        const stateToSave = { gameDate: dataJogoFormatada, states: playerStates };
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(stateToSave)); }
        catch (e) { console.error("Erro ao salvar no localStorage:", e); }
    }

    function loadStateFromLocalStorage() {
        try {
            const savedStateJSON = localStorage.getItem(STORAGE_KEY);
            if (savedStateJSON) {
                const savedState = JSON.parse(savedStateJSON);
                if (savedState.gameDate === dataJogoFormatada && savedState.states) {
                    playerStates = savedState.states;
                } else { localStorage.removeItem(STORAGE_KEY); playerStates = {}; }
            } else { playerStates = {}; }
        } catch (e) { console.error("Erro ao carregar localStorage:", e); playerStates = {}; localStorage.removeItem(STORAGE_KEY); }
        applyStateToButtons(playerStates);
        updateCounts();
    }

    function applyStateToButtons(states) {
         if (!playerListContainer) return;
         playerListContainer.querySelectorAll('.player-item').forEach(item => {
             const playerName = item.dataset.player;
             const vouButton = item.querySelector('.btn.vou');
             const naoVouButton = item.querySelector('.btn.nao-vou');
             vouButton.classList.remove('selected', 'disabled');
             naoVouButton.classList.remove('selected', 'disabled');
             if (states[playerName]) {
                 const state = states[playerName];
                 if (state.status === 'Vou') { vouButton.classList.add('selected'); }
                 else if (state.status === 'Não Vou') { naoVouButton.classList.add('selected'); }
                 if (state.locked === true) { vouButton.classList.add('disabled'); naoVouButton.classList.add('disabled'); }
             }
         });
         // console.log("Estado visual/travas aplicados."); // Log opcional
    }

    function updateCounts() {
         if (!playerListContainer || !countConfirmedEl) return;
         const allPlayerItems = playerListContainer.querySelectorAll('.player-item');
         const totalPlayers = allPlayerItems.length;
         let confirmedCount = 0; let declinedCount = 0;
         for (const playerName in playerStates) {
             if (playerStates[playerName]) {
                 if (playerStates[playerName].status === 'Vou') { confirmedCount++; }
                 else if (playerStates[playerName].status === 'Não Vou') { declinedCount++; }
             }
         }
         const pendingCount = totalPlayers - confirmedCount - declinedCount;
         countConfirmedEl.textContent = confirmedCount;
         countDeclinedEl.textContent = declinedCount;
         countPendingEl.textContent = pendingCount >= 0 ? pendingCount : 0;
         countTotalEl.textContent = totalPlayers;
    }

    // Listener de clique na lista de jogadores (COM CONFIRMAÇÃO ANTES DE FINALIZAR)
    if (playerListContainer) {
        playerListContainer.addEventListener('click', (event) => {
            if (event.target.classList.contains('btn') && !event.target.classList.contains('disabled')) {
                const clickedButton = event.target;
                const playerItem = clickedButton.closest('.player-item');
                if (!playerItem) return;
                const playerName = playerItem.dataset.player || 'Desconhecido';

                if (playerStates[playerName] && playerStates[playerName].locked === true) { return; } // Já travado

                const playerCategory = playerItem.dataset.category || 'Outro';
                const finalStatus = clickedButton.classList.contains('vou') ? 'Vou' : 'Não Vou';

                // --- JANELA DE CONFIRMAÇÃO ---
                const confirmationMessage = `Tem certeza que ${finalStatus === 'Vou' ? 'VAI jogar' : 'NÃO VAI jogar'} na sexta (${dataJogoFormatada})?`;

                if (confirm(confirmationMessage)) { // Só continua se o usuário clicar "OK"
                    // --- CONFIRMADO PELO USUÁRIO ---
                    const vouButton = playerItem.querySelector('.btn.vou');
                    const naoVouButton = playerItem.querySelector('.btn.nao-vou');

                    // Lógica Visual + Trava
                    vouButton.classList.remove('selected'); naoVouButton.classList.remove('selected');
                    clickedButton.classList.add('selected');
                    vouButton.classList.add('disabled'); naoVouButton.classList.add('disabled');

                    // Atualiza e Salva Estado
                    playerStates[playerName] = { status: finalStatus, locked: true };
                    saveStateToLocalStorage();

                    // Atualiza Contadores
                    updateCounts();

                    // Envia Dados
                    const dataToSend = { nome: playerName, status: finalStatus, categoria: playerCategory, dataJogo: dataJogoFormatada };
                    sendDataToSheet(dataToSend);
                } else {
                    // --- CANCELADO PELO USUÁRIO ---
                    console.log("Confirmação cancelada pelo usuário.");
                    // Não faz nada, mantém o estado anterior
                    return;
                }
                // --- FIM DA CONFIRMAÇÃO ---

            } else if (event.target.classList.contains('btn') && event.target.classList.contains('disabled')) {
                 showStatusMessage("Escolha já registrada para esta semana.", "loading", true); // Feedback de botão travado
            }
        });
    } else { console.error("Container '#playerList' não encontrado."); }

    // Função sendDataToSheet (sem alterações)
    async function sendDataToSheet(playerData) { /* ... (código igual ao anterior) ... */ }

    // Função showStatusMessage (sem alterações)
    function showStatusMessage(message, type, autoHide) { /* ... (código igual ao anterior) ... */ }

    // --- Lógica da Galeria de Fotos (ATUALIZADA para buscar do PHP) ---
    async function loadPhotos() {
        if (!photoGalleryContainer) { console.error("Container '#photoGalleryContainer' não encontrado."); return; }
        photoGalleryContainer.innerHTML = '<p>Carregando fotos...</p>';
        try {
            const response = await fetch('list_files.php'); // Chama o PHP
            if (!response.ok) throw new Error(`Erro HTTP: ${response.status}`);
            const data = await response.json();
            if (data.error) throw new Error(`Erro servidor: ${data.error}`);
            const imageFiles = data.files || [];
            photoGalleryContainer.innerHTML = ''; // Limpa "Carregando"
            if (imageFiles.length === 0) { photoGalleryContainer.innerHTML = '<p>Nenhuma foto na galeria.</p>'; return; }
            const imagesToShow = imageFiles.slice(0, 10); // Limita a 10
            imagesToShow.forEach(imageUrl => { const divItem = document.createElement('div'); divItem.className = 'photo-item'; const img = document.createElement('img'); img.src = imageUrl; img.alt = "Foto da Pelada"; img.loading = "lazy"; divItem.appendChild(img); photoGalleryContainer.appendChild(divItem); });
            if (imageFiles.length > 10) console.log(`Mostrando 10 de ${imageFiles.length} fotos.`);
        } catch (error) { console.error("Erro ao carregar fotos:", error); photoGalleryContainer.innerHTML = `<p>Erro ao carregar fotos: ${error.message}.</p>`; }
    }

}); // Fim do DOMContentLoaded

// --- Função da Data (sem alterações) ---
function getProximaSextaFormatada(short = false) { /* ... (código igual ao anterior) ... */ }