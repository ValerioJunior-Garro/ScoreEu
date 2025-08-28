<?php
session_start();
require "conexao.php";

if (!isset($_SESSION['rm'])) {
    header("Location: login.php");
    exit;
}

$meuRM = $_SESSION['rm'];
$meuNome = $_SESSION['nome'] ?? "";

// Buscar outros usuários
$sql = "SELECT RM, Nome FROM usuario WHERE RM <> ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$meuRM]);
$contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Chat</title>
<link rel="stylesheet" href="estilo.css">
<style>
   .btn-config {
    position: fixed;
    bottom: 20px;
    left: 20px;
    width: 60px;
    height: 60px;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    z-index: 2000;
    background: #fff;  /* fundo branco */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px; /* espaço interno para o ícone */
    transition: transform 0.2s ease;
}

.btn-config img {
    width: 70%;  /* controla o tamanho do ícone */
    height: 70%;
    object-fit: contain;
    pointer-events: none; /* para não bloquear o clique */
}

.btn-config:hover {
    transform: scale(1.1);
}


    .sidebar {
        position: fixed;
        top: 0;
        right: 0;
        width: 320px;
        height: 100%;
        background: #fff;
        color: #333;
        box-shadow: -4px 0 20px rgba(0,0,0,0.3);
        padding: 30px 20px;
        transition: transform 0.3s ease, opacity 0.3s ease;
        z-index: 1999;
        overflow-y: auto;
        transform: translateX(100%);
        opacity: 0;
        pointer-events: none;
    }

    .sidebar.active {
        transform: translateX(0);
        opacity: 1;
        pointer-events: auto;
    }

    .sidebar h2 { margin-top: 0; font-size: 1.4rem; margin-bottom: 20px; }
    .sidebar label { display: block; margin: 15px 0 5px; }
    .sidebar input[type="text"],
    .sidebar select { width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 6px; border: 1px solid #ccc; }
    .close-sidebar { position: absolute; top: 10px; right: 15px; font-size: 1.5rem; cursor: pointer; color: #333; }

    .mensagem { margin-bottom: 8px; padding: 5px 8px; border-radius: 6px; display: inline-block; max-width: 70%; }
    .enviada { background: #d1ffd6; align-self: flex-end; }
    .recebida { background: #f1f1f1; align-self: flex-start; }
    .mensagem .data { display: block; font-size: 0.7em; color: #666; }
</style>
</head>
<body>
<div class="chat-container">

    <div class="computadores">
        <h3>Contatos</h3>
        <?php foreach ($contatos as $c): ?>
            <button class="btn-computador" data-rm="<?= $c['RM'] ?>">
                <?= htmlspecialchars($c['Nome']) ?>
            </button>
        <?php endforeach; ?>

        <form action="logout.php" method="post">
            <button type="submit" class="btn-voltar">Sair</button>
        </form>
    </div>

    <div class="chat">
        <h2>Selecione um contato</h2>
        <div id="chatBody" class="mensagens"></div>
        <form id="formEnvio" class="form-envio">
            <textarea id="texto" placeholder="Digite sua mensagem..."></textarea>
            <button type="submit">Enviar</button>
        </form>
    </div>
</div>

<button class="btn-config" id="openConfig" aria-label="Configurações">
    <img src="incoconfig.png" alt="Configurações">
</button>


<div class="sidebar" id="sidebar">
    <span class="close-sidebar" id="closeSidebar">&times;</span>
    <h2>Configurações</h2>

    <label for="nomeUsuario">Alterar Nome:</label>
    <input type="text" id="nomeUsuario" placeholder="<?= htmlspecialchars($meuNome) ?>">

    <label for="tamanhoFonte">Tamanho da Fonte:</label>
    <select id="tamanhoFonte">
        <option value="14px">Pequena</option>
        <option value="16px" selected>Média</option>
        <option value="18px">Grande</option>
        <option value="30px">Muito Grande</option>
    </select>

    <label for="somNotificacao">Som de Notificação:</label>
    <select id="somNotificacao">
        <option value="padrao">Padrão</option>
        <option value="som1">Som 1</option>
        <option value="som2">Som 2</option>
    </select>
</div>

<script>
const openConfig = document.getElementById('openConfig');
const sidebar = document.getElementById('sidebar');
const closeSidebar = document.getElementById('closeSidebar');

openConfig.addEventListener('click', () => sidebar.classList.add('active'));
closeSidebar.addEventListener('click', () => sidebar.classList.remove('active'));
window.addEventListener('click', e => {
    if (!sidebar.contains(e.target) && e.target !== openConfig) sidebar.classList.remove('active');
});

const tamanhoFonte = document.getElementById('tamanhoFonte');
tamanhoFonte.addEventListener('change', () => {
    document.querySelector('.chat').style.fontSize = tamanhoFonte.value;
    document.querySelector('.computadores').style.fontSize = tamanhoFonte.value;
});

document.getElementById('nomeUsuario').addEventListener('change', e => alert("Nome alterado para: "+e.target.value));
document.getElementById('somNotificacao').addEventListener('change', e => alert("Som de notificação alterado para: "+e.target.value));

window.__CHAT__ = { rmLogado: <?= json_encode($meuRM) ?>, rmDestino: null };

function appendMensagemLocal({ texto, rmAutor, dataEnvio }) {
    const chat = document.getElementById("chatBody");
    if (!chat) return;
    const minha = window.__CHAT__.rmLogado == rmAutor;
    const div = document.createElement("div");
    div.className = "mensagem " + (minha ? "enviada" : "recebida");

    let dataFormatada = "";
    if (dataEnvio) {
        const dt = new Date(dataEnvio);
        if (!isNaN(dt)) dataFormatada = dt.toLocaleString("pt-BR", { day:"2-digit",month:"2-digit",year:"numeric",hour:"2-digit",minute:"2-digit" });
    }

    div.innerHTML = `<span class="texto">${texto}</span><br><small class="data">${dataFormatada}</small>`;
    chat.appendChild(div);
    chat.scrollTop = chat.scrollHeight;
}

async function enviarMensagem(plaintext) {
    const rmDestino = window.__CHAT__.rmDestino;
    if (!rmDestino) throw new Error("Nenhum destinatário selecionado.");
    const body = new URLSearchParams({ rmDestino: String(rmDestino), mensagem: plaintext });
    const response = await fetch("enviar_mensagem.php", { method:"POST", headers:{ "Content-Type":"application/x-www-form-urlencoded" }, body });
    if (!response.ok) throw new Error(await response.text());
    const result = await response.json();
    appendMensagemLocal({ texto: plaintext, rmAutor: window.__CHAT__.rmLogado, dataEnvio: result.data || new Date().toISOString() });
}

async function carregarMensagens(rmDestino) {
    try {
        const response = await fetch("carregar_mensagens.php?rmDestino=" + encodeURIComponent(rmDestino));
        if (!response.ok) throw new Error(await response.text());
        const mensagens = await response.json();
        const chat = document.getElementById("chatBody");
        chat.innerHTML = "";
        mensagens.forEach(msg => appendMensagemLocal({ texto: msg.Mensagem, rmAutor: parseInt(msg.RM,10), dataEnvio: msg.DataEnvio || new Date().toISOString() }));
    } catch(e){ console.error("Erro:", e); }
}

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formEnvio");
    const textarea = document.getElementById("texto");

    document.querySelectorAll(".btn-computador").forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelectorAll(".btn-computador").forEach(b=>b.classList.remove("ativo"));
            btn.classList.add("ativo");
            const rm = btn.getAttribute("data-rm");
            if (!rm) return alert("Contato sem RM definido!");
            window.__CHAT__.rmDestino = rm;
            const titulo = document.querySelector(".chat h2");
            if (titulo) titulo.textContent = "Conversando com " + btn.textContent;
            carregarMensagens(rm);
        });
    });

    if (form && textarea) {
        form.addEventListener("submit", async ev => {
            ev.preventDefault();
            const textoValue = (textarea.value||"").trim();
            if (!textoValue) return;
            textarea.value = "";
            try { await enviarMensagem(textoValue); } 
            catch(e){ alert("Não foi possível enviar: "+e.message); }
        });

        // ---------- Enviar mensagem ao pressionar Enter ----------
        textarea.addEventListener("keydown", async ev => {
            if (ev.key === "Enter" && !ev.shiftKey) {
                ev.preventDefault();
                const textoValue = (textarea.value||"").trim();
                if (!textoValue) return;
                textarea.value = "";
                try { await enviarMensagem(textoValue); } 
                catch(e){ alert("Não foi possível enviar: "+e.message); }
            }
        });
    }

    setInterval(()=>{ if(window.__CHAT__.rmDestino) carregarMensagens(window.__CHAT__.rmDestino); }, 3000);
});
</script>
</body>
</html>
