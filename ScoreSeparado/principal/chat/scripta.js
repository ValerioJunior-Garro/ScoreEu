// Função para adicionar mensagens no chat
function appendMensagemLocal({ texto, rmAutor, dataEnvio }) {
  const chat = document.getElementById("chatBody");
  if (!chat) return;

  const minha = window.__CHAT__.rmLogado == rmAutor;
  const div = document.createElement("div");

  div.className = "mensagem " + (minha ? "enviada" : "recebida");

  // Formatar data/hora com fallback
  let dataFormatada = "";
  if (dataEnvio) {
      const dt = new Date(dataEnvio);
      if (!isNaN(dt)) {
          dataFormatada = dt.toLocaleString("pt-BR", {
              day: "2-digit",
              month: "2-digit",
              year: "numeric",
              hour: "2-digit",
              minute: "2-digit"
          });
      }
  }

  div.innerHTML = `<span class="texto">${texto}</span><br><small class="data">${dataFormatada}</small>`;
  chat.appendChild(div);
  chat.scrollTop = chat.scrollHeight;
}

// Enviar mensagem para o servidor
async function enviarMensagem(plaintext) {
  const rmDestino = window.__CHAT__.rmDestino;
  if (!rmDestino) throw new Error("Nenhum destinatário selecionado.");

  const body = new URLSearchParams({
      rmDestino: String(rmDestino),
      mensagem: plaintext
  });

  const response = await fetch("enviar_mensagem.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body
  });

  if (!response.ok) {
      const text = await response.text();
      throw new Error(text || "Falha ao enviar mensagem");
  }

  const result = await response.json();
  appendMensagemLocal({
      texto: plaintext,
      rmAutor: window.__CHAT__.rmLogado,
      dataEnvio: result.data || new Date().toISOString() // fallback se PHP não enviar
  });
}

// Carregar mensagens do servidor
async function carregarMensagens(rmDestino) {
  try {
      const response = await fetch("carregar_mensagens.php?rmDestino=" + encodeURIComponent(rmDestino));
      if (!response.ok) throw new Error(await response.text());

      const mensagens = await response.json();
      const chat = document.getElementById("chatBody");
      chat.innerHTML = "";

      mensagens.forEach(msg => {
          appendMensagemLocal({
              texto: msg.Mensagem,
              rmAutor: parseInt(msg.RM, 10),
              dataEnvio: msg.DataEnvio || new Date().toISOString() // fallback
          });
      });

  } catch (e) {
      console.error("Erro ao carregar mensagens:", e);
  }
}

// Inicialização do chat
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("formEnvio");
  const textarea = document.getElementById("texto");

  // Selecionar contato
  document.querySelectorAll(".btn-computador").forEach(btn => {
      btn.addEventListener("click", () => {
          document.querySelectorAll(".btn-computador").forEach(b => b.classList.remove("ativo"));
          btn.classList.add("ativo");

          const rm = btn.getAttribute("data-rm");
          if (!rm) {
              alert("Contato sem RM definido!");
              return;
          }
          window.__CHAT__.rmDestino = rm;

          const titulo = document.querySelector(".chat h2");
          if (titulo) titulo.textContent = "Conversando com " + btn.textContent;

          carregarMensagens(rm);
      });
  });

  // Envio de mensagem
  if (form && textarea) {
      form.addEventListener("submit", async (ev) => {
          ev.preventDefault();
          const textoValue = (textarea.value || "").trim();
          if (!textoValue) return;
          textarea.value = "";
          try {
              await enviarMensagem(textoValue);
          } catch (e) {
              alert("Não foi possível enviar: " + e.message);
          }
      });
  }

  // Atualizar chat a cada 3 segundos
  setInterval(() => {
      if (window.__CHAT__.rmDestino) carregarMensagens(window.__CHAT__.rmDestino);
  }, 3000);
});
