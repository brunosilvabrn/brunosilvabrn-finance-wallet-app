# finance-wallet-app
## 📝 Carteira Digital

Uma aplicação monolítica em PHP 8.2 + CodeIgniter 4, protegida por JWT, para cadastro de usuários, depósito, transferência e reversão de transações.

---

### 📦 Tecnologias

* **Linguagem:** PHP 8.2
* **Framework:** CodeIgniter 4
* **Banco de dados:** MySQL
* **Orquestração:** Docker & Docker Compose
* **UI:** Vanilla JS + TailwindCSS
* **Autenticação:** JWT (`firebase/php-jwt`)

---

## ⚙️ Setup com Docker

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    container_name: codeigniter_app
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
    environment:
      - CI_ENVIRONMENT=development
    depends_on:
      - db

  db:
    image: mysql:8.0
    container_name: mysql_db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: codeigniter
      MYSQL_USER: ci_user
      MYSQL_PASSWORD: ci_password
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: phpmyadmin
    restart: always
    ports:
      - "8081:80"
    environment:
      PMA_HOST: db
      PMA_USER: ci_user
      PMA_PASSWORD: ci_password
    depends_on:
      - db

volumes:
  db_data:
```

> Ajuste as senhas e portas conforme sua necessidade.
> OBS. Se alterar a url ou/e porta do server PHP alterar o seguinte paramentro *URL_BASE*  nos arquivos
> app\Views\ > wallet.php , register.php e login.php
```
# Sua url configurada no env
const URL_BASE = 'http://localhost:8080';
```

---

## 📝 Variáveis de ambiente
Renomear o arquivo **env.example** para .env
No arquivo `.env` na raiz do projeto, configure:

```ini
CI_ENVIRONMENT = development

# Database
database.default.hostname = db
database.default.database = codeigniter
database.default.username = ci_user
database.default.password = ci_password
database.default.DBDriver   = MySQLi

# JWT
jwt.secret = SUA_CHAVE_SECRETA_FORTE
jwt.ttl    = 3600
```

---

## 🗄️ Banco de Dados
> Migrate : php spark migrate

### Tabelas principais

```sql
-- users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  balance DECIMAL(15,2) DEFAULT 0.00,
  created_at DATETIME,
  updated_at DATETIME
);

-- transactions
CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id_from INT NULL,
  user_id_to   INT NOT NULL,
  type ENUM('deposit','transfer') NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  status ENUM('completed','reversed') DEFAULT 'completed',
  reversed_transaction_id INT NULL,
  created_at DATETIME,
  FOREIGN KEY (user_id_from) REFERENCES users(id),
  FOREIGN KEY (user_id_to)   REFERENCES users(id),
  FOREIGN KEY (reversed_transaction_id) REFERENCES transactions(id)
);
```

---

## 🚦 Rotas e Endpoints

### Públicas (sem JWT)

| Método | Rota             | Controller::Método       | Descrição              |
| ------ | ---------------- | ------------------------ | ---------------------- |
| GET    | `/login`         | Home::login              | Formulário de login    |
| GET    | `/register`      | Home::register           | Formulário de registro |
| POST   | `/auth/register` | AuthController::register | Cria usuário           |
| POST   | `/auth/login`    | AuthController::login    | Gera JWT               |
| GET    | `/dashboard`     | Home::wallet             | View principal (guest) |

### Protegidas (JWT)

> **Filtro aplicado:** `jwt`

| Método | Rota                              | Controller::Método                  | Descrição                          |
| ------ | --------------------------------- | ----------------------------------- | ---------------------------------- |
| GET    | `/dashboard`                      | Home::wallet                        | View do dashboard (usuário logado) |
| GET    | `/loaduserdata`                   | WalletController::dataUserDashboard | `{ name, balance, transactions }`  |
| POST   | `/wallet/deposit`                 | WalletController::deposit           | Depósito na carteira               |
| POST   | `/wallet/transfer`                | WalletController::transfer          | Transferência entre usuários       |
| POST   | `/wallet/reverse/{transactionId}` | WalletController::reverse           | Reversão de transação              |
| POST   | `/auth/logout`                    | AuthController::logout              | Remove cookie JWT e encerra sessão |

---

## 📂 Estrutura de Pastas

```
app/
├── Config/
│   ├── Filters.php
│   └── JWT.php
├── Controllers/
│   ├── AuthController.php
│   ├── Home.php
│   └── WalletController.php
├── Filters/
│   ├── Auth.php
│   └── JwtAuth.php
├── Models/
│   ├── TransactionModel.php
│   └── UserModel.php
├── Services/
│   └── WalletService.php
└── Views/
    |── login.php
    |── register.php
    └── wallet.php
```

---

## 🔒 Fluxo de Autenticação

1. **Registro** (`POST /auth/register`) → grava usuário e senha hasheada.
2. **Login** (`POST /auth/login`) → valida credenciais e retorna JWT.
3. **Cookie HTTP-Only**: `AuthController::login` define `jwt_token` com `HttpOnly`.
4. **Filtros**:

   * **`Guest`** redireciona para `/dashboard` se já estiver logado.
   * **`Auth/JwtAuth`** redireciona para `/login` se não estiver logado.
5. **Logout** (`POST /auth/logout`) → servidor emite `Set-Cookie` para expirar `jwt_token`.

---

## 🔧 Como executar

```bash
# Renomear o arquivo env.example para .env
# Levanta os containers
docker-compose up -d --build

# Dentro do container app (Instalar dependencias coposer e rodar migrate):
docker exec -it codeigniter_app bash

# Dentro dele:
composer install
php spark migrate

# Acesse via:
# - Aplicação: http://localhost:8080
# - phpMyAdmin: http://localhost:8081



```

---

### 🎯 Boas Práticas Aplicadas

* **SOLID & SRP:** Controllers finos, lógica de negócio em Services.
* **Transações BD:** `deposit`, `transfer` e `reverse` dentro de transações atômicas.
* **Validações:** `UserModel` com regras, Controllers usando `validate()`.
* **Segurança:** senhas com `password_hash`, JWT em cookie `HttpOnly`.
* **Testabilidade:** serviços isolados, fáceis de mockar.
* **Docker:** ambiente reproduzível em PHP 8.2 + MySQL 8.0.
