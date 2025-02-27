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
   cd Script
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
   - Certifique-se de que o XAMPP está instalado e rodando
   - O projeto deve estar na pasta `htdocs` do XAMPP
   - Configure o Apache para apontar para o diretório do projeto
   - Acesse o projeto através do navegador: `http://localhost/Script`

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
    RewriteBase /Script/
    
    # Redirecionar para HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
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

1. Acesse o sistema através do navegador
2. Use as credenciais padrão:
   - Usuário: `admin`
   - Senha: `admin123`
3. **IMPORTANTE**: Altere a senha padrão imediatamente após o primeiro acesso

## Suporte

Em caso de problemas durante a instalação, verifique:

1. Se todas as extensões PHP necessárias estão habilitadas
   ```bash
   php -m | grep -E "mysqli|pdo|zip|gd|mbstring|xml|fileinfo"
   ```

2. Se as versões do PHP e Composer são compatíveis
   ```bash
   php -v
   composer -V
   ```

3. Se o servidor web tem as permissões corretas nos diretórios
4. Se todas as variáveis de ambiente estão configuradas corretamente
5. Se o banco de dados está acessível com as credenciais fornecidas

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

## Contribuindo

1. Faça um Fork do projeto
2. Crie uma Branch para sua Feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a Branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes. 