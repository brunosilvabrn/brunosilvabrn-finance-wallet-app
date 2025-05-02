<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-lg">
        <div class="text-center">
            <h1 class="text-3xl font-extrabold text-gray-900">Crie sua conta</h1>
            <p class="mt-2 text-gray-600">Preencha os campos para se cadastrar</p>
        </div>

        <form id="registerForm" class="mt-6 space-y-6">
            <div class="space-y-4">
                <!-- Campo Nome -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nome completo</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input id="name" name="name" type="text" autocomplete="name" required
                            class="py-3 pl-10 block w-full border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Seu nome completo">
                    </div>
                    <p id="nameError" class="mt-2 text-sm text-red-600"></p>
                </div>

                <!-- Campo Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required
                            class="py-3 pl-10 block w-full border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            placeholder="seu@email.com">
                    </div>
                    <p id="emailError" class="mt-2 text-sm text-red-600"></p>
                </div>

                <!-- Campo Senha -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="new-password" required
                            class="py-3 pl-10 block w-full border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            placeholder="••••••••">
                    </div>
                    <p id="passwordError" class="mt-2 text-sm text-red-600"></p>
                </div>
            </div>

            <div>
                <button type="submit"
                    class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-user-plus text-blue-300 group-hover:text-blue-200"></i>
                    </span>
                    Cadastrar
                </button>
            </div>

            <div id="successMessage" class="text-center text-sm text-green-600"></div>
        </form>

        <div class="text-center text-sm text-gray-600">
            Já tem uma conta? <a href="<?php echo base_url('login') ?>" class="font-medium text-blue-600 hover:text-blue-500">Faça login</a>
        </div>
    </div>

    <script>
        const URL_BASE = 'http://192.168.1.10:8080';

        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Limpa mensagens de erro anteriores
            document.getElementById('nameError').textContent = '';
            document.getElementById('emailError').textContent = '';
            document.getElementById('passwordError').textContent = '';
            document.getElementById('successMessage').textContent = '';
            
            // Obtém os valores dos campos
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            // Validação dos campos
            let isValid = true;
            
            if (!name) {
                document.getElementById('nameError').textContent = 'Por favor, insira seu nome.';
                isValid = false;
            } else if (name.length < 3) {
                document.getElementById('nameError').textContent = 'O nome deve ter pelo menos 3 caracteres.';
                isValid = false;
            }
            
            if (!email) {
                document.getElementById('emailError').textContent = 'Por favor, insira seu e-mail.';
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                document.getElementById('emailError').textContent = 'Por favor, insira um e-mail válido.';
                isValid = false;
            }
            
            if (!password) {
                document.getElementById('passwordError').textContent = 'Por favor, insira sua senha.';
                isValid = false;
            } else if (password.length < 6) {
                document.getElementById('passwordError').textContent = 'A senha deve ter pelo menos 6 caracteres.';
                isValid = false;
            }
            
            if (!isValid) {
                return;
            }
            
            try {
                // Mostra estado de loading
                const button = e.target.querySelector('button[type="submit"]');
                button.disabled = true;
                button.innerHTML = `
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-spinner fa-spin text-blue-300"></i>
                    </span>
                    Cadastrando...
                `;
                
                // Faz a requisição para a API
                const response = await fetch(URL_BASE + '/auth/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        name: name,
                        email: email,
                        password: password
                    })
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Erro ao cadastrar');
                }
                
                // Se chegou aqui, o cadastro foi bem-sucedido
                document.getElementById('successMessage').textContent = 'Cadastro realizado com sucesso!';
                console.log('Resposta da API:', data);

                const responseLogin = await fetch(URL_BASE + '/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password,
                    })
                });
                
                const dataLogin = await responseLogin.json();
                
                if (!responseLogin.ok) {
                    throw new Error(dataLogin.message || 'Erro ao fazer login');
                }
                
                localStorage.setItem('jwt_token', dataLogin.token);
                const expiresAt = Date.now() + (dataLogin.expires_in * 1000);
                localStorage.setItem('jwt_expires', expiresAt);


                document.getElementById('successMessage').textContent = 'Login realizado com sucesso!';
                console.log('Resposta da API:', dataLogin);
                
                window.location.href = '/dashboard';

                
            } catch (error) {
                document.getElementById('passwordError').textContent = error.message || 'Erro ao conectar com o servidor';
                console.error('Erro:', error);
            } finally {
                // Restaura o botão
                const button = e.target.querySelector('button[type="submit"]');
                button.disabled = false;
                button.innerHTML = `
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-user-plus text-blue-300 group-hover:text-blue-200"></i>
                    </span>
                    Cadastrar
                `;
            }
        });
    </script>
</body>
</html>