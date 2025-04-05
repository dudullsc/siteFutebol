<?php
require_once __DIR__ . '/vendor/autoload.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexão com MongoDB
$client = new MongoDB\Client("mongodb://localhost:27017");
$collection = $client->pelada->jogadores;

// Função para buscar por categoria
function getJogadoresPorCategoria($collection, $categoria) {
    return $collection->find(['categoria' => $categoria]);
}

$mensalistas = getJogadoresPorCategoria($collection, 'Mensalista');
$avulsos = getJogadoresPorCategoria($collection, 'Avulso');
$goleiros = getJogadoresPorCategoria($collection, 'Goleiro');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelada Organização</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" crossorigin="anonymous" />
</head>
<body>

<div class="background-overlay"></div>

<header class="site-header">
    <div class="header-container">
        <h1 class="site-title"><i class="fa-solid fa-futbol"></i> Pelada Semanal</h1>
        <nav class="main-nav">
            <ul>
                <li><button class="nav-button active" data-target="home-section">Home</button></li>
                <li><button class="nav-button" data-target="confirmacao-section">Confirmação</button></li>
                <li><button class="nav-button" data-target="fotos-section">Fotos</button></li>
            </ul>
        </nav>
    </div>
</header>

<main class="main-content">
    <p class="data-jogo" id="data-sexta">Carregando data...</p>

    <section id="home-section" class="content-section active">
        <div class="section-content">
            <h2>Bem-vindo à Pelada Semanal!</h2>
            <p>Use o menu acima para confirmar sua presença ou ver as fotos.</p>
            <p>Organização e resenha garantida toda sexta!</p>
        </div>
    </section>

    <section id="confirmacao-section" class="content-section">
        <div class="section-content">
            <h2>Confirmação de Presença</h2>
            <div class="confirmation-summary">
                <span>Confirmados: <strong id="count-confirmed">0</strong></span> |
                <span>Ausentes: <strong id="count-declined">0</strong></span> |
                <span>Pendentes: <strong id="count-pending">0</strong></span> /
                <span>Total: <strong id="count-total">0</strong></span>
            </div>
            <div id="playerList">

                <!-- MENSALISTAS -->
                <section class="player-group">
                    <h3>Mensalistas</h3>
                    <ul class="list">
                        <?php foreach ($mensalistas as $jogador): ?>
                            <li class="player-item" data-player="<?= htmlspecialchars($jogador['nome']) ?>" data-category="Mensalista">
                                <span class="player-name"><?= htmlspecialchars($jogador['nome']) ?></span>
                                <div class="player-actions">
                                    <button class="btn vou">Vou</button>
                                    <button class="btn nao-vou">Não Vou</button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <!-- AVULSOS -->
                <section class="player-group">
                    <h3>Avulsos</h3>
                    <ul class="list">
                        <?php foreach ($avulsos as $jogador): ?>
                            <li class="player-item" data-player="<?= htmlspecialchars($jogador['nome']) ?>" data-category="Avulso">
                                <span class="player-name"><?= htmlspecialchars($jogador['nome']) ?></span>
                                <div class="player-actions">
                                    <button class="btn vou">Vou</button>
                                    <button class="btn nao-vou">Não Vou</button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <!-- GOLEIROS -->
                <section class="player-group">
                    <h3>Goleiros 🥅</h3>
                    <ul class="list">
                        <?php foreach ($goleiros as $jogador): ?>
                            <li class="player-item" data-player="<?= htmlspecialchars($jogador['nome']) ?>" data-category="Goleiro">
                                <span class="player-name"><?= htmlspecialchars($jogador['nome']) ?></span>
                                <div class="player-actions">
                                    <button class="btn vou">Vou</button>
                                    <button class="btn nao-vou">Não Vou</button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>

            </div>

            <div id="statusMessage" class="status-message" style="display: none;"></div>
        </div>
    </section>

    <section id="fotos-section" class="content-section">
        <div class="section-content">
            <h2>Fotos da Galera</h2>

            <div class="swiper photo-swiper-container">
                <div class="swiper-wrapper" id="photoGallerySwiperWrapper">
                    <div class="swiper-slide"><p>Carregando fotos...</p></div>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
            <p class="gallery-hint">Clique na foto para ampliar ou arraste.</p>
        </div>
    </section>

</main>

<footer class="site-footer">
    <p>Pelada App © 2025</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
<script src="script.js"></script>
</body>
</html>
