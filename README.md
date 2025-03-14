# Sistema Sou + Digital

Sistema de gerenciamento de atividades técnicas, controle de chamados e gestão de reembolsos.

## Sobre o Projeto

O Sistema Sou + Digital é uma plataforma web completa desenvolvida para otimizar o gerenciamento de atividades técnicas, controle de chamados e gestão de reembolsos. O sistema oferece uma interface moderna e intuitiva, com recursos avançados de gestão e controle.

### Principais Funcionalidades

- Gestão de chamados técnicos e relatórios
- Sistema completo de reembolsos com workflow de aprovação
- Blog corporativo com publicação de artigos
- Sistema de notificações em tempo real
- Gerenciamento de usuários
- Exportação de relatórios em Excel e PDF
- Suporte para upload de arquivos de grande porte
- Integração com GitHub para atualizações automáticas

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
   
   Este comando irá instalar as dependências definidas no composer.json.

3. **Configure o Banco de Dados**
   - Inicie o MySQL/MariaDB
   - Crie um novo banco de dados
   ```sql
   CREATE DATABASE sou_digital;
   ```
   - Importe o arquivo de estrutura do banco
   ```bash
   mysql -u seu_usuario -p sou_digital < db/database.sql
   ```

4. **Configure o Servidor Web**
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

5. **Configure a Conexão com o Banco de Dados**
   - Verifique se o arquivo `db.php` está configurado com as credenciais corretas:
   ```php
   $servername = "localhost";
   $username = "sou_digital";
   $password = "SuaSenhaSegura123!";
   $dbname = "sou_digital";
   ```
   - Crie o diretório includes e um link simbólico para o arquivo db.php:
   ```bash
   mkdir -p /var/www/html/soudigital/includes
   ln -s /var/www/html/soudigital/db.php /var/www/html/soudigital/includes/db.php
   ```

6. **Crie Diretórios de Upload**
   - Crie os diretórios necessários para armazenar os uploads:
   ```bash
   mkdir -p /var/www/html/soudigital/uploads/chamados
   mkdir -p /var/www/html/soudigital/uploads/reembolsos
   mkdir -p /var/www/html/soudigital/uploads/usuarios
   mkdir -p /var/www/html/soudigital/uploads/temp
   mkdir -p /var/www/html/soudigital/logs
   mkdir -p /var/www/html/soudigital/backups/db
   mkdir -p /var/www/html/soudigital/backups/files
   ```

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

## Configuração do PHP

Para trabalhar com uploads de arquivos grandes, ajuste os limites no arquivo `.user.ini` na raiz do projeto ou em um diretório específico:

```ini
upload_max_filesize = 40M
post_max_size = 42M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
```

Isso permite o upload de arquivos de até 40MB. Para arquivos maiores, o sistema implementa um mecanismo de upload em chunks através do `processar-upload-grande.php`.

## Configuração do GitHub Webhook

O sistema inclui suporte para atualização automática por meio de um webhook do GitHub:

1. Acesse as configurações do seu repositório no GitHub
2. Navegue até "Webhooks" > "Add webhook"
3. Insira a URL: `https://seudominio.com/github-webhook.php`
4. Selecione "Content type" como `application/json`
5. Defina um segredo seguro 
6. Escolha "Just the push event"
7. Verifique que o webhook está "Active"
8. Salve o webhook

Isso possibilita atualizações automáticas do código quando um push é realizado para o repositório.

## Primeiro Acesso

1. Acesse o sistema através do navegador usando o IP ou nome do servidor
2. Use as credenciais padrão:
   - Usuário: `admin`
   - Senha: `admin123`
3. **IMPORTANTE**: Altere a senha padrão imediatamente após o primeiro acesso

## Solução de Problemas Comuns

### 1. Erros de Conexão ao Banco de Dados (HTTP ERROR 500)

Se encontrar erros de conexão:

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
   
3. Para arquivos grandes, use o formulário alternativo em `page/formulario-alternativo.php`

## Atualizando o Sistema

Para atualizar o sistema e suas dependências:

1. **Backup dos Dados**
   ```bash
   # Fazer backup do banco de dados
   mysqldump -u seu_usuario -p sou_digital | gzip > backups/db/backup_$(date +%Y%m%d).sql.gz
   
   # Fazer backup dos arquivos de upload
   tar -czvf backups/files/uploads_$(date +%Y%m%d).tar.gz uploads/
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
   mysql -u seu_usuario -p sou_digital < db/updates/update_latest.sql
   ```

## Estrutura de Diretórios Principal

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
- **login.php**: Página de autenticação
- **includes/header.php**, **includes/sidebar.php**, **includes/footer.php**: Componentes da interface
- **includes/upload_functions.php**: Funções para manipulação de uploads
- **processar-upload.php** e **processar-upload-grande.php**: Processamento de uploads
- **page/meus-reembolsos.php**: Gerenciamento de reembolsos
- **page/gerar-script.php**: Criação de relatórios/chamados
- **page/visualizar-relatorios.php**: Visualização de relatórios

## Módulos Principais

1. **Sistema de Autenticação**:
   - Login/Logout
   - Gerenciamento de Usuários
   - Perfis e Fotos de Perfil
   
2. **Chamados e Relatórios**:
   - Registro de Chamados
   - Controle de Quilometragem
   - Geração de Scripts de Atendimento
   
3. **Reembolsos**:
   - Solicitação de Reembolsos
   - Workflow de Aprovação
   - Exportação de Relatórios
   
4. **Blog Corporativo**:
   - Criação de Posts
   - Aprovação de Conteúdo
   - Comentários e Reações

5. **Notificações**:
   - Alertas em Tempo Real
   - Centro de Notificações
   - Histórico de Atividades

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

**Atualizado em Junho de 2023** 