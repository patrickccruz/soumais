// Função para salvar os dados do formulário no localStorage
function salvarDadosFormulario() {
    const formData = {
      dataChamado: document.getElementById("dataChamado")?.value || "",
      numeroChamado: document.getElementById("numeroChamado")?.value || "",
      cliente: document.getElementById("cliente")?.value || "",
      nomeInformante: document.getElementById("nomeInformante")?.value || "",
      quantidadePatrimonios: document.getElementById("quantidadePatrimonios")?.value || "",
      kmInicial: document.getElementById("kmInicial")?.value || "",
      kmFinal: document.getElementById("kmFinal")?.value || "",
      horaChegada: document.getElementById("horaChegada")?.value || "",
      horaSaida: document.getElementById("horaSaida")?.value || "",
      enderecoPartida: document.getElementById("enderecoPartida")?.value || "",
      enderecoChegada: document.getElementById("enderecoChegada")?.value || "",
      informacoesAdicionais: document.getElementById("informacoesAdicionais")?.value || "",
    };
    localStorage.setItem("formData", JSON.stringify(formData));
}
  
// Função para carregar os dados do formulário do localStorage
function carregarDadosFormulario() {
    const formData = JSON.parse(localStorage.getItem("formData"));
    if (formData) {
      document.getElementById("dataChamado").value = formData.dataChamado || "";
      document.getElementById("numeroChamado").value = formData.numeroChamado || "";
      document.getElementById("cliente").value = formData.cliente || "";
      document.getElementById("nomeInformante").value = formData.nomeInformante || "";
      document.getElementById("quantidadePatrimonios").value = formData.quantidadePatrimonios || "";
      document.getElementById("kmInicial").value = formData.kmInicial || "";
      document.getElementById("kmFinal").value = formData.kmFinal || "";
      document.getElementById("horaChegada").value = formData.horaChegada || "";
      document.getElementById("horaSaida").value = formData.horaSaida || "";
      document.getElementById("enderecoPartida").value = formData.enderecoPartida || "";
      document.getElementById("enderecoChegada").value = formData.enderecoChegada || "";
      document.getElementById("informacoesAdicionais").value = formData.informacoesAdicionais || "";
    }
}

// Função para apagar todos os campos do formulário
function deleteRespGeral() {
    const inputs = document.querySelectorAll("#scriptForm input, #scriptForm textarea");
    inputs.forEach(input => input.value = "");
    localStorage.removeItem("formData");
}

// Função para enviar os dados do formulário para o webhook do Discord
function enviarParaDiscord() {
    return new Promise((resolve, reject) => {
        console.log("Enviando dados para o Discord...");
        const formData = new FormData(document.getElementById('scriptForm'));
        const nomeUsuario = sessionStorage.getItem("nomeUsuario") || "Usuário desconhecido";
        const arquivoInput = document.getElementById("arquivo");
        const arquivo = arquivoInput.files[0];

        const embed = {
            title: "Script de atendimento",
            fields: [
                { name: "Nome do técnico", value: nomeUsuario, inline: false },
                { name: "Data do chamado", value: formData.get('dataChamado') || "N/A", inline: false },
                { name: "Número do chamado", value: formData.get('numeroChamado') || "N/A", inline: false },
                { name: "Cliente", value: formData.get('cliente') || "N/A", inline: false },
                { name: "Nome do informante", value: formData.get('nomeInformante') || "N/A", inline: false },
                { name: "Quantidade de patrimônios", value: formData.get('quantidadePatrimonios') || "N/A", inline: false },
                { name: "KM inicial", value: formData.get('kmInicial') || "N/A", inline: false },
                { name: "KM final", value: formData.get('kmFinal') || "N/A", inline: false },
                { name: "Hora de chegada", value: formData.get('horaChegada') || "N/A", inline: false },
                { name: "Hora de saída", value: formData.get('horaSaida') || "N/A", inline: true },
                { name: "Endereço de partida", value: formData.get('enderecoPartida') || "N/A", inline: false },
                { name: "Endereço de chegada", value: formData.get('enderecoChegada') || "N/A", inline: false },
                { name: "Informações adicionais", value: formData.get('informacoesAdicionais') || "N/A", inline: false },
            ],
            color: 3066993,
            footer: {
                text: 'Atenciosamente Sou + Tecnologia',
                icon_url: 'https://i.imgur.com/sOsHaID.png'
            },
            timestamp: new Date().toISOString()
        };

        const webhookURL = "https://discord.com/api/webhooks/1326597466392498237/MdUd68kvPG4eQhiy7KB4KY0WiyzQQBSmsUwu4vOy19OKci0W5CihB8YTBh3_MJYmGyN2";
        const formDataToSend = new FormData();

        if (arquivo) {
            formDataToSend.append("file", arquivo, arquivo.name);
        }

        formDataToSend.append("payload_json", JSON.stringify({
            embeds: [embed]
        }));

        fetch(webhookURL, {
            method: "POST",
            body: formDataToSend
        })
        .then(response => {
            if (response.ok) {
                console.log("Dados enviados para o Discord com sucesso.");
                resolve();
            } else {
                reject(new Error("Erro ao enviar dados para o Discord"));
            }
        })
        .catch(error => {
            console.error("Erro ao enviar dados para o Discord:", error);
            reject(error);
        });
    });
}

// Função que combina salvar no banco e enviar para o Discord
async function salvarEEnviar() {
    try {
        salvarDadosFormulario();
        
        // Primeiro salva no banco
        const formData = new FormData(document.getElementById('scriptForm'));
        const response = await fetch('salvar_dados.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.text();
        
        if (!data.includes("sucesso")) {
            throw new Error(data);
        }

        // Se salvou no banco com sucesso, envia para o Discord
        await enviarParaDiscord();

        // Se ambas as operações foram bem-sucedidas, mostra mensagem de sucesso
        mostrarSucesso("Dados salvos no banco e enviados para o Discord com sucesso!");

    } catch (error) {
        console.error('Erro:', error);
        mostrarErro(`Erro durante o processo: ${error.message}`);
    }
}

// Carregar os dados do formulário ao carregar a página
window.onload = carregarDadosFormulario;

// Adicionar event listener para o botão de salvar
document.addEventListener('DOMContentLoaded', function() {
    const salvarTudoBtn = document.getElementById('salvarTudo');
    if (salvarTudoBtn) {
        salvarTudoBtn.addEventListener('click', function(e) {
            e.preventDefault();
            salvarEEnviar();
        });
    }
});