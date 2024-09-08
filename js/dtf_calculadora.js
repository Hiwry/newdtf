document.addEventListener('DOMContentLoaded', function () {
    let historico = [];

    // Função para buscar o valor por cm² baseado na quantidade
    async function obterValorPorCm2(quantidade) {
        try {
            const response = await fetch(`get_valor_por_cm2.php?quantidade=${quantidade}`);
            const data = await response.json();
            if (data.valor_por_cm2) {
                return parseFloat(data.valor_por_cm2);
            } else {
                throw new Error(data.error || 'Erro desconhecido');
            }
        } catch (error) {
            console.error('Erro ao buscar valor por cm²:', error);
            return 0; // Valor padrão em caso de erro
        }
    }

    // Função para calcular o valor da aplicação e atualizar o histórico
    async function calcularValorOtimizado(input) {
        const aplicacaoDiv = input.closest('.aplicacao');
        let larguraItem = parseFloat(aplicacaoDiv.querySelector('.largura').value) || 0;
        let alturaItem = parseFloat(aplicacaoDiv.querySelector('.altura').value) || 0;
        const quantidade = parseInt(aplicacaoDiv.querySelector('.quantidade').value) || 0;

        if (!larguraItem || !alturaItem || !quantidade) return;

        if (larguraItem > alturaItem) {
            [larguraItem, alturaItem] = [alturaItem, larguraItem]; // Inverter valores se necessário
        }

        const larguraFolha = 57;
        const valorPorCm2 = await obterValorPorCm2(quantidade);

        const itensPorLinha = Math.floor(larguraFolha / larguraItem);
        const numLinhas = Math.ceil(quantidade / itensPorLinha);
        const alturaTotal = numLinhas * alturaItem;
        const area = larguraItem * alturaItem;
        const valorTotal = area * valorPorCm2 * quantidade + (quantidade * 2);

        const id = aplicacaoDiv.dataset.id || Date.now().toString();
        aplicacaoDiv.dataset.id = id;

        atualizarHistorico(id, larguraItem, alturaItem, quantidade, area, valorPorCm2, valorTotal, alturaTotal);
        aplicacaoDiv.querySelector('.valor-exibido').textContent = valorTotal.toFixed(2);
    }

    // Função para adicionar uma nova aplicação
    function adicionarAplicacao(tipo, largura, altura) {
        const aplicacoesDiv = document.getElementById('aplicacoes');
        const cardId = Date.now().toString();
        const card = document.createElement('div');
        card.classList.add('card', 'aplicacao');
        card.dataset.id = cardId;
    
        card.innerHTML = `
            <h3>${tipo}</h3>
            <label>Largura (cm):</label>
            <input type="number" step="0.01" class="largura" value="${largura}" required><br>
    
            <label>Altura (cm):</label>
            <input type="number" step="0.01" class="altura" value="${altura}" required><br>
    
            <label>Quantidade:</label>
            <input type="number" class="quantidade" value="1" required><br>
    
            <div><strong>Valor Total: R$ <span class="valor-exibido">0,00</span></strong></div>
    
            <button type="button" class="remover" data-id="${cardId}">Remover</button>
        `;
    
        aplicacoesDiv.appendChild(card);
    
        // Calcula o valor inicial da aplicação e atualiza o histórico
        calcularValorOtimizado(card.querySelector('.quantidade'));
    
        card.querySelector('.largura').addEventListener('input', () => calcularValorOtimizado(card.querySelector('.largura')));
        card.querySelector('.altura').addEventListener('input', () => calcularValorOtimizado(card.querySelector('.altura')));
        card.querySelector('.quantidade').addEventListener('input', () => calcularValorOtimizado(card.querySelector('.quantidade')));
        card.querySelector('.remover').addEventListener('click', () => removerAplicacao(cardId));
    }

    // Função para remover uma aplicação
    function removerAplicacao(cardId) {
        const aplicacaoDiv = document.querySelector(`.aplicacao[data-id='${cardId}']`);
        historico = historico.filter(item => item.id !== cardId);
        aplicacaoDiv.remove();
        recalcularHistorico();
    }

    // Função para atualizar o histórico das aplicações
    function atualizarHistorico(id, largura, altura, quantidade, area, valorPorCm2, valorTotal, alturaTotal) {
        const aplicacaoDiv = document.querySelector(`.aplicacao[data-id="${id}"]`);
        const nomeArte = document.getElementById('nome-arte').value.trim();  // Captura o nome da arte
        
        const index = historico.findIndex(item => item.id === id);
    
        if (index === -1) {
            historico.push({
                id,
                largura,
                altura,
                quantidade,
                area: area * quantidade,
                valorPorCm2,
                valorTotal,
                alturaTotal,
                valorUnitario: valorTotal / quantidade,
                nome_arte: nomeArte // Inclui o nome da arte no histórico
            });
        } else {
            historico[index] = {
                id,
                largura,
                altura,
                quantidade,
                area: area * quantidade,
                valorPorCm2,
                valorTotal,
                alturaTotal,
                valorUnitario: valorTotal / quantidade,
                nome_arte: nomeArte // Atualiza o nome da arte
            };
        }
    
        console.log("Histórico atualizado:", historico);
        recalcularHistorico();
    }
    

// Função para recalcular o histórico e mostrar os valores totais
function recalcularHistorico() {
    let totalValor = 0, totalArea = 0, totalAltura = 0, totalUnitario = 0;
    const relatorio = document.getElementById('relatorioAplicacoes');
    relatorio.innerHTML = '';

    historico.forEach((item, index) => {
        totalValor += item.valorTotal;
        totalArea += item.area;
        totalAltura += item.alturaTotal;
        totalUnitario += item.valorUnitario; // Soma o valor unitário

        relatorio.innerHTML += `
            <div class="historico-item">
                <p><strong>Aplicação ${index + 1}</strong></p>
                <p>Tamanho: ${item.largura.toFixed(2)} x ${item.altura.toFixed(2)} cm</p>
                <p>Quantidade: ${item.quantidade}</p>
                <p>Área Total: ${(item.area).toFixed(2)} cm²</p>
                <p>Altura Total Utilizada: ${item.alturaTotal.toFixed(2)} cm</p> <!-- Aqui exibe a Altura Total Utilizada -->
                <p>Valor Unitário: R$ ${item.valorUnitario.toFixed(2)}</p>
                <p>Valor Total: R$ ${item.valorTotal.toFixed(2)}</p>
            </div>
        `;
    });

    relatorio.innerHTML += `
        <hr>
        <h3>Total Vendido</h3>
        <p>Área Total Vendida: ${totalArea.toFixed(2)} cm²</p>
        <p>Altura Total Utilizada: ${totalAltura.toFixed(2)} cm</p>
        <p>Valor Total Unitário: R$ ${totalUnitario.toFixed(2)}</p>
        <p>Valor Total Geral: R$ ${totalValor.toFixed(2)}</p>
    `;
}


    // Função para gerar o pedido
    async function gerarPedido() {
        const nomeCliente = document.getElementById('nome-cliente').value.trim();
        const numeroNT = document.getElementById('numero-nt').value.trim();
        const nomeArte = document.getElementById('nome-arte').value.trim(); // Captura o nome da arte
        const dataEntrega = document.getElementById('data-entrega').value;
        const valorTotal = historico.reduce((acc, item) => acc + item.valorTotal, 0);

        if (!nomeCliente || !numeroNT || !nomeArte || !dataEntrega) {
            alert("Por favor, preencha todos os campos para gerar o pedido.");
            return;
        }

        console.log("Aplicações enviadas no pedido:", historico);

        const pedidoData = {
            nomeCliente,
            numeroNT,
            nomeArte, // Inclui o nome da arte
            dataEntrega,
            valorTotal,
            aplicacoes: historico
        };

        try {
            const response = await fetch('salvar_pedido.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(pedidoData)
            });

            const textResponse = await response.text();
            console.log("Resposta bruta do servidor:", textResponse);

            let data;
            try {
                data = JSON.parse(textResponse);
            } catch (e) {
                throw new Error("Erro ao processar a resposta JSON: " + e.message + ". Resposta recebida: " + textResponse);
            }

            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('Erro ao salvar pedido:', error);
        }
    }

    document.querySelector('#gerar-pedido-form').addEventListener('submit', function (event) {
        const nomeArte = document.getElementById('nome-arte').value.trim();
        
        if (!nomeArte) {
            alert("Por favor, insira o nome da arte.");
            event.preventDefault(); // Impede o envio do formulário
            return;
        }
    
        gerarPedido(); // Função que processa o pedido
    });
    

    window.adicionarAplicacao = adicionarAplicacao;
    window.gerarPedido = gerarPedido;
});
