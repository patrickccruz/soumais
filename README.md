# Sistema Sou + Digital

Sistema de gerenciamento de atividades técnicas, controle de chamados e gestão de reembolsos.

## Sobre o Projeto

O Sistema Sou + Digital é uma plataforma web completa desenvolvida para otimizar o gerenciamento de atividades técnicas, controle de chamados e gestão de reembolsos. O sistema oferece uma interface moderna e intuitiva, com recursos avançados de gestão e controle.

### Principais Funcionalidades

- Gestão de chamados técnicos
- Sistema de reembolsos
- Blog corporativo
- Gerenciamento de usuários
- Relatórios e análises
- Backup automático

## Pré-requisitos

Antes de começar, certifique-se de ter instalado em sua máquina:

- PHP 7.4 ou superior
- Composer (Gerenciador de dependências PHP)
- XAMPP (ou outro servidor web com suporte a PHP)
- MySQL/MariaDB
- Git

### Extensões PHP Necessárias

- mysqli
- pdo
- zip (para PHPSpreadsheet)
- gd
- mbstring
- xml
- fileinfo

## Instalação

Siga estes passos para configurar o ambiente de desenvolvimento:

1. **Clone o Repositório**
   ```bash
   git clone [URL_DO_REPOSITÓRIO]
   cd soudigital
   ```

2. **Instale as Dependências PHP**
   ```bash
   composer install
   ```
   
   Este comando irá instalar as seguintes dependências:
   - phpoffice/phpspreadsheet (^1.29)
   - vlucas/phpdotenv (^5.6)

3. **Configure o Ambiente**
   ```bash
   # Copie o arquivo de exemplo de variáveis de ambiente
   cp .env.example .env
   
   # Edite o arquivo .env com suas configurações
   nano .env
   ```

   Configure as seguintes variáveis no arquivo .env:
   ```env
   DB_HOST=localhost
   DB_USER=seu_usuario
   DB_PASS=sua_senha
   DB_NAME=nome_do_banco
   
   SMTP_HOST=seu_servidor_smtp
   SMTP_USER=seu_email
   SMTP_PASS=senha_email
   SMTP_PORT=587
   ```

4. **Configure o Banco de Dados**
   - Inicie o MySQL/MariaDB
   - Crie um novo banco de dados
   ```sql
   CREATE DATABASE nome_do_banco;
   ```
   - Importe o arquivo de estrutura do banco
   ```bash
   mysql -u seu_usuario -p nome_do_banco < db/estrutura.sql
   ```

5. **Configure o Servidor Web**
   - Certifique-se de que o Apache está instalado e rodando
   - Crie um VirtualHost para o projeto:
   ```apache
   <VirtualHost *:80>
       ServerName soudigital.local
       ServerAlias 192.168.2.194
       
       DocumentRoot /var/www/html/soudigital
       
       <Directory /var/www/html/soudigital>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/soudigital_error.log
       CustomLog ${APACHE_LOG_DIR}/soudigital_access.log combined
   </VirtualHost>
   ```
   - **IMPORTANTE**: Sempre inclua o IP do servidor no `ServerAlias` para garantir acesso correto

6. **Configure a Conexão com o Banco de Dados**
   - Verifique se o arquivo `db.php` está na raiz do projeto
   - Crie o diretório includes e um link simbólico para o arquivo db.php:
   ```bash
   mkdir -p /var/www/html/soudigital/includes
   ln -s /var/www/html/soudigital/db.php /var/www/html/soudigital/includes/db.php
   ```
   - Isso garante que os arquivos no diretório `page/` consigam acessar a conexão do banco de dados

## Permissões de Diretórios

Configure as permissões corretas nos seguintes diretórios:

```bash
# No Windows (via PowerShell com privilégios administrativos)
icacls uploads /grant "Everyone:(OI)(CI)F"
icacls logs /grant "Everyone:(OI)(CI)F"
icacls backups /grant "Everyone:(OI)(CI)F"

# No Linux/Mac
chmod -R 775 uploads/
chmod -R 775 logs/
chmod -R 775 backups/
```

## Configuração do Apache

Adicione ou modifique o arquivo .htaccess na raiz do projeto:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /soudigital/
    
    # Redirecionar para HTTPS (se configurado)
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Proteger arquivos sensíveis
    RewriteRule ^(.env|composer.json|composer.lock)$ - [F,L]
</IfModule>

# Configurações de segurança
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "DENY"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

## Primeiro Acesso

1. Acesse o sistema através do navegador usando o IP ou nome do servidor
2. Use as credenciais padrão:
   - Usuário: `admin`
   - Senha: `admin123`
3. **IMPORTANTE**: Altere a senha padrão imediatamente após o primeiro acesso

## Solução de Problemas Comuns

### 1. Erros de Conexão ao Banco de Dados (HTTP ERROR 500)

Se encontrar o erro "Access denied for user 'root'@'localhost'":

1. Verifique se está usando o arquivo `db.php` centralizado:
   ```php
   require_once '../includes/db.php';  // Em páginas dentro do diretório page/
   ```
   
2. Remova qualquer conexão direta como:
   ```php
   $conn = new mysqli('localhost', 'root', '', 'sou_digital');  // EVITE ISSO!
   ```
   
3. Verifique se o link simbólico está correto:
   ```bash
   ls -la /var/www/html/soudigital/includes/db.php
   ```
   
4. Execute o script para verificar conexões diretas:
   ```bash
   php check_db_connections.php
   ```

### 2. Páginas Sem Estilo CSS

1. Verifique se a variável `$is_page` está definida no início do arquivo:
   ```php
   $is_page = true;
   ```
   
2. Certifique-se de que o header.php está sendo incluído corretamente:
   ```php
   include_once '../includes/header.php';
   ```

### 3. Uploads de Arquivos Não Funcionam

1. Verifique permissões dos diretórios:
   ```bash
   ls -la uploads/
   ```
   
2. Certifique-se de que o PHP tem permissão para escrever nos diretórios

## Atualizando o Sistema

Para atualizar o sistema e suas dependências:

1. **Backup dos Dados**
   ```bash
   # Fazer backup do banco de dados
   php backup_database.php
   
   # Fazer backup dos arquivos
   php backup_files.php
   ```

2. **Atualizar o Código**
   ```bash
   git pull origin main
   ```

3. **Atualizar Dependências**
   ```bash
   composer update
   ```

4. **Verificar Atualizações do Banco**
   ```bash
   # Se houver scripts de atualização
   mysql -u seu_usuario -p nome_do_banco < db/updates/update_latest.sql
   ```

## Estrutura de Diretórios

O sistema está organizado da seguinte forma:
```
/
├── ajax/           # Endpoints para requisições AJAX
├── assets/         # Recursos estáticos (CSS, JS, imagens)
├── backups/        # Diretório para backups do sistema
├── db/             # Scripts e arquivos relacionados ao banco de dados
├── includes/       # Arquivos PHP reutilizáveis
├── logs/           # Logs do sistema
├── page/           # Páginas do sistema
├── scripts/        # Scripts auxiliares
├── uploads/        # Arquivos enviados pelos usuários
└── vendor/         # Dependências do Composer
```

## Arquivos Principais

- **db.php**: Arquivo central de conexão com o banco de dados
- **index.php**: Página inicial do sistema
- **header.php**, **sidebar.php**, **footer.php**: Componentes reutilizáveis da interface
- **upload_functions.php**: Funções para manipulação de uploads
- **check_db_connections.php**: Script para verificar conexões diretas ao banco

## Melhores Práticas

1. **Conexão com Banco de Dados**
   - Sempre use o arquivo centralizado db.php
   - Nunca crie conexões diretas em arquivos individuais
   - Use prepared statements para todas as consultas SQL

2. **Páginas e Interface**
   - Defina sempre `$is_page = true;` no início das páginas no diretório page/
   - Inclua o header, sidebar e footer corretamente
   - Valide entradas de formulários antes de processá-las

3. **Segurança**
   - Sanitize todas as entradas de usuário
   - Use htmlspecialchars para saídas de texto
   - Valide uploads de arquivos rigorosamente

4. **Manutenção**
   - Faça backup regular do banco de dados
   - Mantenha logs de erro para diagnóstico
   - Documente alterações significativas

## Contribuindo

1. Faça um Fork do projeto
2. Crie uma Branch para sua Feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a Branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## Documentação Adicional

Para informações mais detalhadas sobre o funcionamento do sistema, consulte o arquivo [DOCUMENTATION.md](DOCUMENTATION.md).

## Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## Contato e Suporte

Para suporte técnico ou dúvidas sobre o sistema, entre em contato:

- **Email**: suporte@soudigital.com
- **Telefone**: (XX) XXXX-XXXX
- **Site**: https://www.soudigital.com

---

**Atualizado em Fevereiro de 2025** 