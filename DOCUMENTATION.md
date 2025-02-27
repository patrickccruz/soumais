# Documentação Técnica - Sistema Sou + Digital

## Visão Geral

O Sistema Sou + Digital é uma plataforma web desenvolvida para gerenciamento de atividades técnicas, controle de chamados e gestão de reembolsos. O sistema foi construído com foco em usabilidade, segurança e desempenho, utilizando as seguintes tecnologias:

- PHP 7.4+
- MySQL/MariaDB
- JavaScript/jQuery
- HTML5/CSS3
- Bootstrap 4
- PHPSpreadsheet (para geração de relatórios)
- PHPMailer (para envio de emails)

## Estrutura de Diretórios

O sistema está organizado na seguinte estrutura:

```
/var/www/html/soudigital/
├── ajax/               # Endpoints para requisições AJAX
├── api/                # APIs para integração com outros sistemas
├── assets/             # Recursos estáticos (CSS, JS, imagens)
│   ├── css/
│   ├── js/
│   ├── images/
│   └── vendors/        # Bibliotecas de terceiros
├── backups/            # Diretório para armazenar backups
├── db/                 # Scripts e arquivos de banco de dados
├── includes/           # Arquivos PHP reutilizáveis
│   ├── header.php
│   ├── footer.php
│   ├── sidebar.php
│   └── ... (outros includes)
├── logs/               # Logs do sistema
├── page/               # Páginas principais do sistema
│   ├── admin/          # Área administrativa
│   ├── chamados/       # Gestão de chamados
│   ├── reembolsos/     # Sistema de reembolsos
│   └── ... (outras páginas)
├── scripts/            # Scripts auxiliares
├── uploads/            # Armazenamento de arquivos enviados
│   ├── chamados/
│   ├── reembolsos/
│   ├── usuarios/
│   └── temp/
├── vendor/             # Dependências do Composer
├── .env                # Variáveis de ambiente
├── .htaccess           # Configurações do Apache
├── check_db_connections.php # Script para verificar conexões de banco
├── composer.json       # Configuração de dependências
├── db.php              # Arquivo central de conexão com o banco
├── index.php           # Página inicial
└── README.md           # Instruções básicas
```

## Configuração de Conexão com o Banco de Dados

### Estrutura de Conexão

O sistema utiliza um arquivo centralizado `db.php` na raiz do projeto para todas as conexões de banco de dados. Este arquivo é responsável por:

1. Estabelecer a conexão com o banco de dados
2. Configurar o conjunto de caracteres
3. Gerenciar erros de conexão

**Importante**: O arquivo `db.php` está na raiz, mas é acessado por outros arquivos em diferentes diretórios através de um link simbólico em `/includes/db.php`. Isso permite que arquivos nas pastas `page/` e outras possam incluir o arquivo de banco de dados usando:

```php
require_once '../includes/db.php';  // Para arquivos na pasta page/
// ou
require_once 'includes/db.php';  // Para arquivos na raiz
```

### Como criar o link simbólico necessário

```bash
# Criar o diretório includes se não existir
mkdir -p /var/www/html/soudigital/includes

# Criar o link simbólico
ln -s /var/www/html/soudigital/db.php /var/www/html/soudigital/includes/db.php
```

### Melhores Práticas para Conexão DB

- **Nunca use conexões diretas** como `new mysqli('localhost', 'root', '', 'sou_digital')` em arquivos individuais
- Sempre use o arquivo centralizado através de `require_once '../includes/db.php'`
- Utilize prepared statements para todas as consultas SQL
- Feche a conexão após o uso com `$conn->close()` quando apropriado

### Tabelas Principais do Banco de Dados

| Tabela | Descrição | Principais Campos |
|--------|-----------|-------------------|
| usuarios | Armazena informações dos usuários | id, nome, email, senha, nivel, status |
| chamados | Registro de chamados técnicos | id, usuario_id, titulo, descricao, status, data_criacao |
| reembolsos | Solicitações de reembolso | id, usuario_id, valor, descricao, comprovante, status |
| servicos | Catálogo de serviços oferecidos | id, nome, descricao, valor, status |
| blog_posts | Artigos do blog | id, titulo, conteudo, autor_id, data_publicacao, status |
| logs | Registro de atividades do sistema | id, usuario_id, acao, detalhes, ip, data |

## Módulos do Sistema

### 1. Sistema de Autenticação

O sistema utiliza autenticação baseada em sessões PHP. Os principais arquivos são:

- `autenticacao.php`: Formulário de login
- `processar_login.php`: Validação de credenciais
- `logout.php`: Encerramento de sessão

A autenticação verifica o nível de acesso do usuário para determinar quais funcionalidades estarão disponíveis. Os níveis são:

- **1**: Administrador (acesso total)
- **2**: Gerente (acesso parcial à área administrativa)
- **3**: Técnico (acesso às funcionalidades operacionais)
- **4**: Cliente (acesso limitado às suas próprias informações)

### 2. Sistema de Gestão de Chamados

O módulo de chamados permite:

- Abertura de novos chamados
- Atribuição a técnicos
- Acompanhamento do status
- Anexo de arquivos
- Comunicação entre cliente e técnico
- Geração de relatórios

Principais arquivos:
- `page/meus-chamados.php`: Visualização de chamados do usuário
- `page/novo-chamado.php`: Criação de chamados
- `page/detalhes-chamado.php`: Detalhes completos do chamado
- `ajax/atualizar_status_chamado.php`: Endpoint para atualização de status

### 3. Sistema de Reembolsos

Gerencia solicitações de reembolso com:

- Cadastro de solicitações
- Upload de comprovantes
- Workflow de aprovação
- Geração de relatórios financeiros

Principais arquivos:
- `page/meus-reembolsos.php`: Visualização de reembolsos do usuário
- `page/novo-reembolso.php`: Criação de solicitações
- `page/aprovar-reembolsos.php`: Interface de aprovação (admins/gerentes)
- `ajax/processar_reembolso.php`: Processamento de solicitações

### 4. Sistema de Blog

Permite a publicação de artigos e notícias com:

- Editor de texto avançado
- Gerenciamento de categorias
- Comentários
- Controle de publicação

Principais arquivos:
- `page/blog.php`: Listagem de artigos
- `page/artigo.php`: Visualização de artigo individual
- `page/admin/gerenciar-blog.php`: Administração de artigos
- `ajax/salvar_post.php`: Endpoint para salvar artigos

### 5. Área Administrativa

Fornece ferramentas para gestão do sistema:

- Gerenciamento de usuários
- Relatórios gerenciais
- Configurações do sistema
- Monitoramento de atividades

Principais arquivos:
- `page/admin/dashboard.php`: Painel principal
- `page/admin/usuarios.php`: Gerenciamento de usuários
- `page/admin/relatorios.php`: Geração de relatórios
- `page/admin/configuracoes.php`: Configurações do sistema

## Sistema de Upload de Arquivos

### Estrutura de Uploads

Os uploads são organizados em subdiretórios por tipo:

- `uploads/chamados/`: Arquivos relacionados a chamados
- `uploads/reembolsos/`: Comprovantes de reembolsos
- `uploads/usuarios/`: Fotos de perfil e documentos de usuários
- `uploads/temp/`: Armazenamento temporário

### Funções de Upload

O arquivo `upload_functions.php` contém funções reutilizáveis para:

- Validação de tipos de arquivo
- Verificação de tamanho
- Renomeação segura
- Movimentação para o diretório correto

Exemplo de uso:
```php
include_once '../includes/upload_functions.php';

// Upload de arquivo
$result = upload_file($_FILES['arquivo'], 'reembolsos', [
    'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf'],
    'max_size' => 5242880, // 5MB
    'new_filename' => 'reembolso_' . $reembolso_id
]);

if($result['success']) {
    $arquivo_path = $result['path'];
    // Salvar caminho no banco de dados
} else {
    $erro = $result['error'];
    // Tratar erro
}
```

## Instruções de Instalação e Configuração

### Requisitos do Sistema

- Servidor web (Apache/Nginx)
- PHP 7.4 ou superior
- MySQL 5.7 ou MariaDB 10.3+
- Extensões PHP: mysqli, pdo, zip, gd, mbstring, xml, fileinfo

### Processo de Instalação

1. Clone o repositório para o diretório do servidor web
2. Instale as dependências com Composer
3. Configure as variáveis de ambiente no arquivo `.env`
4. Importe a estrutura do banco de dados de `db/estrutura.sql`
5. Configure o VirtualHost do Apache para o projeto
6. Crie o link simbólico do arquivo db.php
7. Configure permissões de diretórios
8. Acesse o sistema pelo navegador

Para instruções detalhadas, consulte o arquivo [README.md](README.md).

## Solução de Problemas Comuns

### 1. Erro de Conexão com o Banco de Dados (HTTP 500)

**Sintomas**: Páginas retornam erro HTTP 500 ou páginas em branco.

**Causa comum**: O arquivo db.php não está sendo acessado corretamente.

**Solução**:
1. Verifique se existe o link simbólico em `/includes/db.php`
2. Remova conexões diretas ao banco de dados nos arquivos
3. Use o script `check_db_connections.php` para localizar conexões diretas
4. Verifique as credenciais no arquivo db.php
5. Reinicie o Apache após as alterações

```bash
# Verifique o link simbólico
ls -la /var/www/html/soudigital/includes/db.php

# Execute o verificador de conexões
php /var/www/html/soudigital/check_db_connections.php

# Reinicie o Apache
systemctl restart apache2
```

### 2. Problemas de CSS/JS não Carregados

**Sintomas**: Interface sem estilos ou funcionalidades JavaScript.

**Causa comum**: Caminhos incorretos ou variáveis não definidas.

**Solução**:
1. Verifique se a variável `$is_page` está definida no início das páginas:
   ```php
   $is_page = true;
   ```
2. Certifique-se de que os arquivos de inclusão são chamados corretamente:
   ```php
   include_once '../includes/header.php';
   include_once '../includes/sidebar.php';
   include_once '../includes/footer.php';
   ```
3. Verifique os caminhos dos arquivos CSS/JS no devtools do navegador

### 3. Problemas de Upload

**Sintomas**: Uploads falham ou arquivos não aparecem após upload.

**Causa comum**: Permissões de diretório ou configuração de PHP.

**Solução**:
1. Verifique as permissões dos diretórios de upload:
   ```bash
   ls -la /var/www/html/soudigital/uploads/
   ```
2. Ajuste as permissões se necessário:
   ```bash
   chmod -R 775 /var/www/html/soudigital/uploads/
   ```
3. Verifique as configurações do PHP para uploads no `php.ini`:
   ```
   upload_max_filesize = 10M
   post_max_size = 10M
   max_execution_time = 30
   ```
4. Reinicie o Apache após alterações no php.ini

## Segurança

### Proteção contra SQL Injection

O sistema implementa:
- Prepared statements para consultas SQL
- Sanitização de entradas de usuário
- Validação de dados de formulários

Exemplo de uso correto:
```php
// CORRETO: Uso de prepared statements
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// INCORRETO: Vulnerável a SQL Injection
$query = "SELECT * FROM usuarios WHERE email = '$email'"; // NÃO FAÇA ISSO
```

### Proteção contra XSS

Todas as saídas de dados para o navegador são protegidas com:
- `htmlspecialchars()` para escapar caracteres especiais
- Validação de entradas no cliente e servidor
- Filtragem de conteúdo HTML quando permitido

Exemplo:
```php
// CORRETO: Saída segura
echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8');

// INCORRETO: Vulnerável a XSS
echo $usuario['nome']; // NÃO FAÇA ISSO
```

### Proteção contra CSRF

Formulários são protegidos com tokens CSRF:
- Tokens gerados por sessão
- Validação de token em todas as operações sensíveis

### Autenticação e Autorização

- Senhas armazenadas com hash bcrypt
- Sistema de níveis de acesso para autorização
- Timeout de sessão para inatividade

## Manutenção e Backup

### Procedimento de Backup

O sistema inclui scripts automáticos para backup:

1. **Backup do Banco de Dados**:
   ```php
   // Em scripts/backup_database.php
   php /var/www/html/soudigital/scripts/backup_database.php
   ```
   
   Este script realiza:
   - Dump completo do banco de dados
   - Compressão do arquivo
   - Armazenamento no diretório `/backups/db/`
   - Rotação de backups (mantém os últimos 7 diários)

2. **Backup de Arquivos**:
   ```php
   // Em scripts/backup_files.php
   php /var/www/html/soudigital/scripts/backup_files.php
   ```
   
   Este script realiza:
   - Compressão do diretório de uploads
   - Armazenamento no diretório `/backups/files/`
   - Rotação de backups (mantém os últimos 7 diários)

### Monitoramento de Logs

Os logs do sistema são armazenados em:

- **Logs de Erro do PHP/Apache**: `/var/log/apache2/soudigital_error.log`
- **Logs de Acesso**: `/var/log/apache2/soudigital_access.log`
- **Logs Internos do Sistema**: `/var/www/html/soudigital/logs/system.log`

Para monitorar erros em tempo real:
```bash
tail -f /var/log/apache2/soudigital_error.log
```

### Manutenção Preventiva

Tarefas recomendadas de manutenção:

- **Diária**: Verificação de logs de erro
- **Semanal**: Backup completo (banco de dados e arquivos)
- **Mensal**: 
  - Limpeza de arquivos temporários
  - Verificação de integridade do banco de dados
  - Atualização de dependências

## Diretrizes de Desenvolvimento

### Padrões de Codificação

O sistema segue estas convenções:

- **Nomenclatura**:
  - Arquivos: minúsculas com underscores (ex: `meus_chamados.php`)
  - Classes: CamelCase (ex: `UploadManager`)
  - Funções: snake_case (ex: `process_login()`)
  - Variáveis: camelCase (ex: `$userData`)

- **Estrutura de Arquivos**:
  - Cada arquivo deve ter um único propósito
  - Separação de lógica e apresentação
  - Comentários explicativos para funções complexas

### Procedimento para Atualizações

1. **Ambiente de Desenvolvimento**:
   - Crie um branch para novas funcionalidades
   - Teste todas as alterações localmente
   - Documente as alterações

2. **Implantação**:
   - Faça backup completo antes de atualizar
   - Use git para atualizar o código
   - Atualize as dependências com Composer
   - Execute scripts de migração do banco de dados
   - Teste após a implantação

### Desenvolvimento de Novas Funcionalidades

Para adicionar novas funcionalidades:

1. Crie a estrutura da base de dados necessária
2. Desenvolva a lógica de negócios em arquivos apropriados
3. Crie a interface de usuário
4. Implemente os endpoints Ajax se necessário
5. Teste exaustivamente
6. Documente a funcionalidade

## Agendamento de Tarefas (Cron Jobs)

O sistema utiliza cron jobs para tarefas agendadas:

```
# Backup diário do banco às 2h da manhã
0 2 * * * php /var/www/html/soudigital/scripts/backup_database.php >> /var/www/html/soudigital/logs/backup.log 2>&1

# Backup semanal de arquivos aos domingos às 3h da manhã
0 3 * * 0 php /var/www/html/soudigital/scripts/backup_files.php >> /var/www/html/soudigital/logs/backup.log 2>&1

# Limpeza de arquivos temporários a cada 6 horas
0 */6 * * * php /var/www/html/soudigital/scripts/clean_temp_files.php >> /var/www/html/soudigital/logs/cleanup.log 2>&1

# Envio de notificações a cada hora
0 * * * * php /var/www/html/soudigital/scripts/send_notifications.php >> /var/www/html/soudigital/logs/notifications.log 2>&1
```

Para configurar estes jobs:
```bash
crontab -e
# Adicione as linhas acima
```

## Contato e Suporte

Para suporte técnico ou dúvidas sobre o sistema, entre em contato:

- **Email**: suporte@soudigital.com
- **Telefone**: (XX) XXXX-XXXX
- **Site**: https://www.soudigital.com

---

Documentação atualizada em: Fevereiro de 2025 