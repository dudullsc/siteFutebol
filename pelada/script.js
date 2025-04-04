// script.js - VERSÃO FINAL COM LEITURA DO BACKEND PHP/MONGO (POST FUNCIONAL)

document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM Carregado. Iniciando script v2 (Backend)...");

    // --- Configurações ---
    const API_ENDPOINT = 'api/confirmations.php';

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

    let isSubmitting = false;
    let playerStates = {};
    let dataJogoFormatada = "";

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

    // --- Inicialização ---
    dataJogoFormatada = getProximaSextaFormatada(true);
    if (dataElement) dataElement.textContent = `Próxima Sexta-Feira (${dataJogoFormatada})`;

    async function initializeApp() {
        await loadStateFromServer();
        if (document.querySelector('#fotos-section')?.classList.contains('active')) {
            loadPhotos();
        }
        if (typeof lightbox !== 'undefined') {
            lightbox.option({ 'resizeDuration': 200, 'wrapAround': true, 'fadeDuration': 300, 'imageFadeDuration': 300 });
        }
    }
    initializeApp();

    // --- Carrega estado do servidor ---
    async function loadStateFromServer() {
        playerStates = {};
        try {
            const url = `${API_ENDPOINT}?t=${new Date().getTime()}`;
            const response = await fetch(url);
            if (!response.ok) {
                let errorBody = await response.text();
                console.error("Resposta de Erro do Servidor:", errorBody);
                throw new Error(`Erro HTTP ${response.status} ao buscar estado.`);
            }

            const data = await response.json();
            if (data.status === 'success' && typeof data.states === 'object') {
                playerStates = data.states;
                if (data.gameDate && dataElement) {
                    dataJogoFormatada = data.gameDate;
                    dataElement.textContent = `Próxima Sexta-Feira (${dataJogoFormatada})`;
                }
            } else {
                throw new Error(data.message || "Resposta JSON inválida do servidor.");
            }
        } catch (error) {
            console.error("Falha CRÍTICA ao buscar estado do servidor:", error);
            showStatusMessage(`Erro ao carregar confirmações: ${error.message}`, "error", false);
            playerStates = {};
        } finally {
            applyStateToButtons(playerStates);
            updateCounts();
        }
    }

    // --- Aplica estado visual ---
    function applyStateToButtons(states) {
        if (!playerListContainer) return;
        playerListContainer.querySelectorAll('.player-item').forEach(item => {
            const playerName = item.dataset.player;
            const vouButton = item.querySelector('.btn.vou');
            const naoVouButton = item.querySelector('.btn.nao-vou');
            if (!vouButton || !naoVouButton) return;

            vouButton.classList.remove('selected', 'disabled');
            naoVouButton.classList.remove('selected', 'disabled');

            if (states && states[playerName]) {
                const statusFromServer = states[playerName];
                if (statusFromServer === 'Vou') vouButton.classList.add('selected');
                else if (statusFromServer === 'Não Vou') naoVouButton.classList.add('selected');
                vouButton.classList.add('disabled');
                naoVouButton.classList.add('disabled');
            }
        });
    }

    // --- Atualiza contadores ---
    function updateCounts() {
        if (!playerListContainer || !countConfirmedEl) return;
        const allPlayerItems = playerListContainer.querySelectorAll('.player-item');
        const totalPlayers = allPlayerItems.length;
        let confirmedCount = 0, declinedCount = 0;
        for (const playerName in playerStates) {
            if (playerStates[playerName] === 'Vou') confirmedCount++;
            else if (playerStates[playerName] === 'Não Vou') declinedCount++;
        }
        const pendingCount = totalPlayers - confirmedCount - declinedCount;
        countConfirmedEl.textContent = confirmedCount;
        countDeclinedEl.textContent = declinedCount;
        countPendingEl.textContent = pendingCount >= 0 ? pendingCount : 0;
        countTotalEl.textContent = totalPlayers;
    }

    // --- Clique nos botões ---
    if (playerListContainer) {
        playerListContainer.addEventListener('click', (event) => {
            if (event.target.classList.contains('btn') && !event.target.classList.contains('disabled')) {
                const clickedButton = event.target;
                const playerItem = clickedButton.closest('.player-item');
                const playerName = playerItem.dataset.player || 'Desconhecido';
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

                    updateCounts();

                    const dataToSend = {
                        nome: playerName,
                        status: finalStatus,
                        categoria: playerCategory,
                        dataJogo: dataJogoFormatada
                    };

                    sendConfirmationToServer(dataToSend);
                }
            } else if (event.target.classList.contains('btn') && event.target.classList.contains('disabled')) {
                showStatusMessage("Escolha já registrada.", "loading", true);
            }
        });
    }

    // --- ENVIO POST COM VALIDAÇÃO ---
    async function sendConfirmationToServer(playerData) {
        if (isSubmitting) {
            console.log("⏳ Envio já em andamento, ignorando novo clique.");
            return;
        }

        if (!API_ENDPOINT || API_ENDPOINT.length < 5) {
            console.error("API_ENDPOINT inválido:", API_ENDPOINT);
            showStatusMessage("Erro: Endpoint da API não configurado.", "error", true);
            return;
        }

        const requiredFields = ["nome", "status", "categoria", "dataJogo"];
        for (const field of requiredFields) {
            if (!playerData[field] || playerData[field].trim() === "") {
                console.error(`❌ Campo obrigatório faltando ou vazio: ${field}`);
                alert(`Erro: Campo obrigatório "${field}" está vazio.`);
                return;
            }
        }

        isSubmitting = true;
        showStatusMessage("Salvando...", "loading", false);

        console.log("--- Enviando confirmação ao servidor ---");
        console.log("Dados a serem enviados:", playerData);

        try {
            const response = await fetch(API_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(playerData)
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                console.log(`[LOG] ${playerData.nome} escolheu "${playerData.status}" para ${playerData.dataJogo} (Categoria: ${playerData.categoria})`);
                showStatusMessage("Salvo!", "success", true);
                await loadStateFromServer();
            } else {
                throw new Error(result.message || `Erro ${response.status} ao salvar.`);
            }

        } catch (error) {
            console.error("❌ Erro ao salvar confirmação:", error);
            showStatusMessage(`Erro ao salvar: ${error.message}`, "error", true);
            await loadStateFromServer();
        } finally {
            isSubmitting = false;
        }
    }

    // --- STATUS MESSAGE ---
    function showStatusMessage(message, type, autoHide = true) {
        if (!statusMessageDiv) return;
        statusMessageDiv.textContent = message;
        statusMessageDiv.className = `status-message ${type}`;
        statusMessageDiv.style.display = 'block';

        if (autoHide) {
            setTimeout(() => {
                statusMessageDiv.style.display = 'none';
            }, 2500);
        }
    }

    // --- FOTOS / GALERIA ---
    async function loadPhotos() {
        if (!photoGalleryContainer) return;
        photoGalleryContainer.innerHTML = '<p>Carregando fotos...</p>';

        try {
            const response = await fetch('api/photos.json');
            if (!response.ok) throw new Error("Erro ao carregar fotos");
            const photos = await response.json();
            if (!Array.isArray(photos)) throw new Error("Formato inválido");

            let html = '';
            photos.forEach(photo => {
                html += `
                    <a href="${photo.full}" data-lightbox="galeria">
                        <img src="${photo.thumb}" alt="Foto da Pelada">
                    </a>`;
            });

            photoGalleryContainer.innerHTML = html;

        } catch (err) {
            console.error("Erro ao carregar fotos:", err);
            photoGalleryContainer.innerHTML = '<p>Erro ao carregar fotos.</p>';
        }
    }

}); // Fim do DOMContentLoaded

// --- FUNÇÃO DE DATA ---
function getProximaSextaFormatada(short = false) {
    const hoje = new Date();
    const diaSemana = hoje.getDay();
    const diasParaSexta = (5 - diaSemana + 7) % 7 || 7;
    const proximaSexta = new Date(hoje.getFullYear(), hoje.getMonth(), hoje.getDate() + diasParaSexta);

    const optionsLong = { weekday: 'long', day: 'numeric', month: 'long' };
    const optionsShort = { day: '2-digit', month: '2-digit', year: 'numeric' };

    return proximaSexta.toLocaleDateString('pt-BR', short ? optionsShort : optionsLong);
}

