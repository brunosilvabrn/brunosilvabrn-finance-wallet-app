<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carteira Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto max-w-md p-4">
        <!-- Cabeçalho -->
        <header class="bg-blue-600 text-white p-4 rounded-t-lg shadow">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Minha Carteira</h1>
                    <p class="text-sm opacity-80">Bem-vindo, <span id="userName">Carregando...</span></p>
                </div>
                <div class="bg-blue-700 p-3 rounded-full">
                    <i class="fas fa-wallet text-2xl"></i>
                </div>
                <button onclick="handleLogout()" 
                    class="bg-red-500 hover:bg-red-600 px-3 py-2 rounded-lg text-sm">
                    Logout
                </button>
            </div>
        </header>

        <!-- Saldo -->
        <div class="bg-white p-6 shadow rounded-lg mt-4">
            <div class="text-center">
                <p class="text-gray-500">Saldo disponível</p>
                <h2 class="text-3xl font-bold mt-2" id="balance">R$ 0,00</h2>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="grid grid-cols-2 gap-4 mt-6">
            <button onclick="openModal('deposit')" class="bg-green-100 text-green-700 p-4 rounded-lg flex flex-col items-center justify-center hover:bg-green-200 transition">
                <i class="fas fa-money-bill-wave text-xl mb-2"></i>
                <span>Depositar</span>
            </button>
            <button onclick="openModal('send')" class="bg-blue-100 text-blue-700 p-4 rounded-lg flex flex-col items-center justify-center hover:bg-blue-200 transition">
                <i class="fas fa-paper-plane text-xl mb-2"></i>
                <span>Enviar</span>
            </button>
        </div>

        <!-- Histórico de Transações -->
        <div class="bg-white p-4 rounded-lg shadow mt-6">
            <h3 class="font-bold text-lg mb-3">Histórico de Transações</h3>
            <div id="transactionHistory" class="space-y-3">
                <p class="text-gray-500 text-center py-4">Carregando transações...</p>
            </div>
        </div>

        <!-- Modal -->
        <div id="modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <!-- Cabeçalho do Modal -->
                <div class="flex justify-between items-center mb-4">
                    <h3 id="modalTitle" class="text-xl font-bold">Depósito</h3>
                    <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Conteúdo do Modal -->
                <div id="modalContent">
                    <!-- Conteúdo será preenchido dinamicamente -->
                </div>
            </div>
        </div>
    </div>

    <script>
        const URL_BASE = 'http://localhost:8080';
        // Dados do usuário (serão preenchidos pela API)
        let user = {
            name: "",
            balance: 0,
            transactions: []
        };

        // Inicializar a página
        document.addEventListener('DOMContentLoaded', function() {
            fetchUserData();
        });

        // Buscar dados do usuário da API
        async function fetchUserData() {
            try {
                const response = await fetch(URL_BASE + '/loaduserdata');
                
                if (!response.ok) {
                    throw new Error('Erro ao carregar dados do usuário');
                }
                
                const data = await response.json();
                
                // Atualizar objeto user com os dados da API
                user = {
                    name: data.name || "Usuário",
                    balance: data.balance || 0,
                    transactions: data.transactions || []
                };
                
                updateUI();
                renderTransactionHistory();
                
            } catch (error) {
                console.error('Erro:', error);
                alert('Não foi possível carregar os dados da carteira. Por favor, tente novamente mais tarde.');
                
                // Mostrar dados de exemplo em caso de erro
                user = {
                    name: "ERRO AO CARREGA INFORMAÇÕES",
                    balance: 0.00,
                    transactions: [
                    ]
                };
                
                updateUI();
                renderTransactionHistory();
            }
        }

        // Atualizar UI com dados do usuário
        function updateUI() {
            document.getElementById('userName').textContent = user.name;
            document.getElementById('balance').textContent = formatCurrency(user.balance);
        }

        // Renderizar histórico de transações
        function renderTransactionHistory() {
            const container = document.getElementById('transactionHistory');
            container.innerHTML = '';

            if (user.transactions.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-4">Nenhuma transação realizada</p>';
                return;
            }

            user.transactions.forEach(transaction => {
                const transactionEl = document.createElement('div');
                transactionEl.className = `flex justify-between items-center p-3 border-b ${transaction.reversed ? 'opacity-60' : ''}`;
                
                const icon = transaction.type === 'deposit' ? 'fa-money-bill-wave' : 
                            transaction.type === 'send' ? 'fa-paper-plane' : 'fa-qrcode';
                const color = transaction.amount > 0 ? 'text-green-500' : 'text-red-500';
                
                transactionEl.innerHTML = `
                    <div class="flex items-center">
                        <div class="bg-gray-100 p-2 rounded-full mr-3">
                            <i class="fas ${icon} text-gray-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">${transaction.description}</p>
                            <p class="text-xs text-gray-500">${formatDate(transaction.date)}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="${color} font-medium">${formatCurrency(transaction.amount)}</p>
                        ${transaction.reversed ? '<p class="text-xs text-gray-500">Estornado</p>' : ''}
                        ${transaction.type === 'receive' ? `<button onclick="reverseTransaction(${transaction.id})" class="text-xs text-blue-500 hover:text-blue-700 mt-1">Reverter</button>` : ''}
                    </div>
                `;
                
                container.appendChild(transactionEl);
            });
        }

        // Abrir modal com formulário apropriado
        function openModal(action) {
            const modal = document.getElementById('modal');
            const modalTitle = document.getElementById('modalTitle');
            const modalContent = document.getElementById('modalContent');
            
            modalTitle.textContent = action === 'deposit' ? 'Depositar' : 
                                   action === 'send' ? 'Enviar Dinheiro' : 'Receber Dinheiro';
            
            if (action === 'deposit') {
                modalContent.innerHTML = `
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Valor do depósito</label>
                        <input type="number" id="depositAmount" class="w-full p-3 border rounded-lg" placeholder="R$ 0,00" min="0.01" step="0.01">
                    </div>
                    <button onclick="handleDeposit()" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition">
                        Confirmar Depósito
                    </button>
                `;
            } else if (action === 'send') {
                modalContent.innerHTML = `
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Para quem você quer enviar?</label>
                        <input type="email" id="recipient" class="w-full p-3 border rounded-lg mb-3" placeholder="E-mail">
                        
                        <label class="block text-gray-700 mb-2">Valor a enviar</label>
                        <input type="number" id="sendAmount" class="w-full p-3 border rounded-lg" placeholder="R$ 0,00" min="0.01" step="0.01">
                    </div>
                    <button onclick="handleSend()" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition">
                        Enviar Dinheiro
                    </button>
                `;
            } else if (action === 'receive') {
                modalContent.innerHTML = `
                    <div class="text-center mb-6">
                        <div class="bg-white p-4 inline-block border rounded-lg mb-4">
                            <i class="fas fa-qrcode text-6xl text-purple-500"></i>
                        </div>
                        <p class="text-gray-700">Mostre este QR Code para receber pagamentos</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Ou compartilhe seu link</label>
                        <div class="flex">
                            <input type="text" readonly value="carteira.com/${user.name.replace(/\s+/g, '-').toLowerCase()}" class="flex-grow p-3 border rounded-l-lg bg-gray-100">
                            <button class="bg-purple-600 text-white px-4 rounded-r-lg hover:bg-purple-700 transition">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                `;
            }
            
            modal.classList.remove('hidden');
        }

        // Fechar modal
        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
        }

        async function apiFetch(path, options = {}) {
            const token = localStorage.getItem('jwt_token');
            if (!token) throw new Error('Usuário não autenticado');

            const res = await fetch(BASE_URL + `/${path}`, {
            ...options,
            headers: {
                ...(options.headers || {}),
                'Authorization': `Bearer ${token}`,
            },
            });

            if (res.status === 401) {
            throw new Error('Sessão expirada. Faça login novamente.');
            }

            return res.json();
        }

        // -------------------------------
        // 2) Função para formatar moeda (se já não existir)
        // -------------------------------
        function formatCurrency(value) {
            return value.toFixed(2).replace('.', ',');
        }

        // Processar depósito
        async function handleDeposit() {
            const amountInput = document.getElementById('depositAmount');
            const amount = parseFloat(amountInput.value);

            if (isNaN(amount) || amount <= 0) {
                alert('Por favor, insira um valor válido para depósito.');
                return;
            }

            try {
                // 1) Chama a API de depósito
                
                const res = await apiFetch('/wallet/deposit', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ amount })
                });

                // 2) Atualiza o balance do usuário com o valor retornado pela API
                //    Supondo que a API retorne { transaction: { … }, balance: novoSaldo }
                const { transaction, balance } = res;
                user.balance = balance;

                // 3) Adiciona a nova transação no início da lista
                const newTransaction = {
                id:          transaction.id,
                type:        transaction.type,
                amount:      transaction.amount,
                date:        transaction.created_at.split(' ')[0], // 'YYYY-MM-DD HH:MM:SS'
                description: 'Depósito na carteira',
                reversed:    transaction.status !== 'completed'
                };
                user.transactions.unshift(newTransaction);

                // 4) Atualiza a UI
                updateUI();
                renderTransactionHistory();
                closeModal();

                // 5) Feedback pro usuário
                alert(`Depósito de ${formatCurrency(amount)} realizado com sucesso!`);

            } catch (error) {
                console.error('Erro ao processar depósito:', error);
                alert(error.message || 'Ocorreu um erro ao processar seu depósito. Por favor, tente novamente.');
            }
        }

        // Processar envio
        async function handleSend() {
        const recipient = document.getElementById('recipient').value;
        const amountInput = document.getElementById('sendAmount');
        const amount = parseFloat(amountInput.value);

        if (!recipient) {
            alert('Por favor, informe o destinatário.');
            return;
        }

        if (isNaN(amount) || amount <= 0) {
            alert('Por favor, insira um valor válido para envio.');
            return;
        }

        // Validar saldo local (opcional)
        if (user.balance < amount) {
            alert('Saldo insuficiente para realizar esta transferência.');
            return;
        }

        try {
            // 1) Chama a API de transferência
            //    endpoint POST /wallet/transfer { to_user_id, amount }
            const res = await apiFetch('wallet/transfer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                to_user_id: recipient,
                amount: amount
            })
            });

            if (res.status == 'error') {
                alert(res.message);
                return;
            }

            // 2) Extrai dados da resposta
            const { transaction, balance } = res;

            // 3) Atualiza o saldo conforme o servidor
            user.balance = balance;

            // 4) Monta a nova transação usando os dados retornados
            const newTransaction = {
            id:          transaction.id,
            type:        'send',
            amount:      -transaction.amount,
            date:        transaction.created_at.split(' ')[0],
            description: `Enviado para ${recipient}`,
            reversed:    transaction.status !== 'completed'
            };
            user.transactions.unshift(newTransaction);

            // 5) Atualiza a interface
            updateUI();
            renderTransactionHistory();
            closeModal();

            // 6) Confirmação para o usuário
            alert(`Transferência de ${formatCurrency(amount)} para ${recipient} realizada com sucesso!`);

        } catch (error) {
            console.error('Erro ao processar transferência:', error);
            alert(error.message || 'Ocorreu um erro ao processar sua transferência. Por favor, tente novamente.');
        }
        }

        // Reverter transação
        async function reverseTransaction(transactionId) {
            if (!confirm('Tem certeza que deseja reverter esta transação?')) {
                return;
            }

            const originalTx = user.transactions.find(t => t.id === transactionId);
            if (!originalTx || originalTx.reversed) return;

            try {
                // 1) Chama a API de reversão
                // Supondo rota POST /wallet/reverse/{id} que retorna { reversal: {...}, balance: novoSaldo }
                const res = await apiFetch(`wallet/reverse/${transactionId}`, {
                method: 'POST'
                });

                const { transaction, balance } = res;

                // 2) Marca a transação original como revertida localmente
                originalTx.reversed = true;

                // 3) Atualiza o saldo com o valor retornado pelo servidor
                user.balance = balance;

                // 4) Adiciona a transação de reversão vinda do servidor
                const newTransaction = {
                id:          transaction.id,
                type:        transaction.type,            // deve ser 'transfer' ou 'deposit'
                amount:      (transaction.user_id_from)   // sinal já vem correto do servidor
                                ? -transaction.amount 
                                : transaction.amount,
                date:        transaction.created_at.split(' ')[0],
                description: `Estorno: ${originalTx.description}`,
                reversed:    transaction.status !== 'completed'
                };
                user.transactions.unshift(newTransaction);

                // 5) Atualiza a UI
                updateUI();
                renderTransactionHistory();

                alert('Transação revertida com sucesso!');

            } catch (error) {
                console.error('Erro ao reverter transação:', error);
                alert(error.message || 'Ocorreu um erro ao reverter a transação. Por favor, tente novamente.');
            }
        }

        // Funções auxiliares
        function formatCurrency(value) {
            return 'R$ ' + value.toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+\,)/g, '$1.');
        }

        function formatDate(dateString) {
            const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
            return new Date(dateString).toLocaleDateString('pt-BR', options);
        }

        async function handleLogout() {
            // await fetch('/auth/logout', { method: 'POST', credentials: 'include' });
            await apiFetch('auth/logout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                });

            localStorage.removeItem('jwt_token');
            localStorage.removeItem('jwt_expires');

            document.cookie = 'jwt_token=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';

            window.location.href = '/login';
        }
    </script>
</body>
</html>