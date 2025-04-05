// 🔄 Limpar confirmações
async function limparConfirmacoes() {
  try {
    const res = await fetch('admin-api.php?action=limpar');
    const json = await res.json();
    document.getElementById('respostaLimpar').textContent = json.message;
    carregarConfirmacoes(); // atualiza lista após limpeza
  } catch (err) {
    document.getElementById('respostaLimpar').textContent = 'Erro ao limpar confirmações.';
    console.error(err);
  }
}

// 📋 Carregar confirmações
async function carregarConfirmacoes() {
  try {
    const res = await fetch('admin-api.php?action=listar');
    const json = await res.json();
    const container = document.getElementById('listaConfirmacoes');
    container.innerHTML = '';

    if (json.status === 'success') {
      if (Object.keys(json.states).length === 0) {
        container.innerHTML = '<p>Nenhuma confirmação encontrada.</p>';
        return;
      }

      const ul = document.createElement('ul');
      ul.classList.add('lista-jogadores');

      for (const [nome, status] of Object.entries(json.states)) {
        const li = document.createElement('li');
        li.textContent = `${nome}: ${status}`;
        ul.appendChild(li);
      }

      container.appendChild(ul);
    } else {
      container.textContent = json.message;
    }
  } catch (err) {
    document.getElementById('listaConfirmacoes').textContent = 'Erro ao carregar confirmações.';
    console.error(err);
  }
}

// ❌ Remover jogador
async function removerJogador(nome) {
  if (!confirm(`Tem certeza que deseja remover ${nome}?`)) return;

  try {
    const res = await fetch(`admin-api.php?action=remover&nome=${encodeURIComponent(nome)}`);
    const json = await res.json();
    alert(json.message);
    carregarConfirmacoes();
  } catch (err) {
    alert('Erro ao remover jogador.');
    console.error(err);
  }
}

// ✏️ Renomear jogador
async function renomearJogador(antigo, novo) {
  try {
    const res = await fetch(`admin-api.php?action=renomear&antigo=${encodeURIComponent(antigo)}&novo=${encodeURIComponent(novo)}`);
    const json = await res.json();
    alert(json.message);
    carregarConfirmacoes();
  } catch (err) {
    alert('Erro ao renomear jogador.');
    console.error(err);
  }
}

// 🧩 Adicionar jogador (Exemplo para futura expansão)
async function adicionarJogador(nome, categoria) {
  try {
    const hoje = new Date();
    const dia = String(hoje.getDate()).padStart(2, '0');
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const data = `${dia}/${mes}`;

    const dados = {
      nome: nome,
      status: "Vou",
      categoria: categoria,
      dataJogo: data
    };

    const res = await fetch('/api/confirmations.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(dados)
    });

    const json = await res.json();
    alert(json.message);
    carregarConfirmacoes();
  } catch (err) {
    alert('Erro ao adicionar jogador.');
    console.error(err);
  }
}

// 🔁 Carregar confirmações ao carregar a página
document.addEventListener('DOMContentLoaded', carregarConfirmacoes);
