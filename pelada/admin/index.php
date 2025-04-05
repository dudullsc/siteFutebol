<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Pelada</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <h1>⚽ Painel Admin - Pelada</h1>
  </header>

  <main>
    <!-- Limpar confirmações -->
    <section class="box">
      <h2>🧹 Limpar Confirmações</h2>
      <button onclick="limparConfirmacoes()">Limpar Tudo</button>
      <p id="respostaLimpar"></p>
    </section>

    <!-- Ver confirmações -->
    <section class="box">
      <h2>👥 Ver Confirmações</h2>
      <button onclick="carregarConfirmacoes()">🔄 Atualizar Lista</button>
      <div id="listaConfirmacoes" class="confirm-list">
        <p>Carregue para ver os jogadores confirmados.</p>
      </div>
    </section>

    <!-- Adicionar jogador -->
    <section class="box">
      <h2>➕ Adicionar Jogador</h2>
      <form id="formAdicionar" onsubmit="event.preventDefault(); handleAdicionarJogador();">
        <input type="text" id="nomeNovoJogador" placeholder="Nome do jogador" required>
        <select id="categoriaJogador">
          <option value="Titular">Mensalista</option>
          <option value="Avulso">Avulso</option>
          <option value="Goleiro">Goleiro</option>
        </select>
        <button type="submit">Adicionar</button>
      </form>
    </section>

    <!-- Remover jogador -->
    <section class="box">
      <h2>❌ Remover Jogador</h2>
      <form id="formRemover" onsubmit="event.preventDefault(); handleRemoverJogador();">
        <input type="text" id="nomeRemover" placeholder="Nome do jogador" required>
        <button type="submit">Remover</button>
      </form>
    </section>

    <!-- Renomear jogador -->
    <section class="box">
      <h2>✏️ Renomear Jogador</h2>
      <form id="formRenomear" onsubmit="event.preventDefault(); handleRenomearJogador();">
        <input type="text" id="nomeAntigo" placeholder="Nome atual" required>
        <input type="text" id="nomeNovo" placeholder="Novo nome" required>
        <button type="submit">Renomear</button>
      </form>
    </section>
  </main>

  <footer>
    <p>&copy; 2025 Pelada FC - Painel Admin</p>
  </footer>

  <script src="script.js"></script>
  <script>
    function handleAdicionarJogador() {
      const nome = document.getElementById('nomeNovoJogador').value.trim();
      const categoria = document.getElementById('categoriaJogador').value;
      if (nome) adicionarJogador(nome, categoria);
    }

    function handleRemoverJogador() {
      const nome = document.getElementById('nomeRemover').value.trim();
      if (nome) removerJogador(nome);
    }

    function handleRenomearJogador() {
      const antigo = document.getElementById('nomeAntigo').value.trim();
      const novo = document.getElementById('nomeNovo').value.trim();
      if (antigo && novo) renomearJogador(antigo, novo);
    }
  </script>
</body>
</html>
