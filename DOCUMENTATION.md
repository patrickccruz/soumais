# Documentação Técnica - Sistema Sou + Digital

## Visão Geral

O Sistema Sou + Digital é uma plataforma web desenvolvida para gerenciamento de atividades técnicas, controle de chamados e gestão de reembolsos. O sistema foi construído com foco em usabilidade, segurança e desempenho, utilizando as seguintes tecnologias:

- PHP 7.4+
- MySQL/MariaDB
- JavaScript/jQuery
- HTML5/CSS3
- Bootstrap 4
- PHPSpreadsheet (para geração de relatórios em Excel)
- TCPDF (para geração de PDFs)
- PHPMailer (para envio de emails)

## Estrutura de Diretórios

O sistema está organizado na seguinte estrutura:

```
/var/www/html/soudigital/
├── ajax/               # Endpoints para requisições AJAX
│   ├── check_notifications.php
│   └── mark_notification_read.php
├── api/                # APIs para integração com outros sistemas
├── assets/             # Recursos estáticos (CSS, JS, imagens)
│   ├── css/
│   ├── js/
│   ├── images/
│   └── vendors/        # Bibliotecas de terceiros
├── backups/            # Diretório para armazenar backups
├── db/                 # Scripts e arquivos de banco de dados
│   ├── database.sql    # Estrutura principal do banco
│   └── make_admin.sql  # Script para promover usuário a administrador
├── includes/           # Arquivos PHP reutilizáveis
│   ├── header.php      # Header compartilhado entre páginas
│   ├── footer.php      # Footer compartilhado entre páginas
│   ├── sidebar.php     # Menu lateral da aplicação
│   ├── functions.php   # Funções utilitárias
│   ├── upload_functions.php # Funções de gerenciamento de uploads
│   ├── db.php          # Link simbólico para o arquivo de conexão
│   └── send_notification.php # Sistema de notificações
├── logs/               # Logs do sistema
├── page/               # Páginas principais do sistema
│   ├── admin/          # Área administrativa
│   ├── chamados/       # Gestão de chamados/relatórios
│   ├── reembolsos/     # Sistema de reembolsos
│   └── ... (outras páginas)
├── scripts/            # Scripts auxiliares
│   └── migrate_uploads.php # Migração de arquivos de upload
├── uploads/            # Armazenamento de arquivos enviados
│   ├── chamados/
│   ├── reembolsos/
│   ├── usuarios/       # Fotos de perfil e documentos
│   └── temp/           # Armazenamento temporário
├── vendor/             # Dependências do Composer
├── .env                # Variáveis de ambiente
├── .htaccess           # Configurações do Apache
├── check_db_connections.php # Script para verificar conexões de banco
├── composer.json       # Configuração de dependências
├── db.php              # Arquivo central de conexão com o banco
├── login.php           # Página de login
├── processar-upload.php # Processamento de uploads
├── index.php           # Página inicial
├── github-webhook.php  # Webhook para integração com GitHub
└── README.md           # Instruções básicas
```

## Configuração de Conexão com o Banco de Dados

### Estrutura de Conexão

O sistema utiliza um arquivo centralizado `db.php` na raiz do projeto para todas as conexões de banco de dados. Esta abordagem oferece:

1. Centralização das credenciais do banco de dados
2. Configuração consistente do conjunto de caracteres (UTF-8)
3. Fallback automático para ambiente de desenvolvimento
4. Gerenciamento de erros padronizado

**Atualização 2023**: O arquivo db.php agora inclui um mecanismo de fallback que, em caso de falha na conexão principal, tenta uma conexão alternativa com as credenciais de desenvolvimento. Este comportamento só é recomendado para ambientes de desenvolvimento.

```php
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Verificar conexão...
} catch (Exception $e) {
    // Se falhar, tentar conexão alternativa (apenas para desenvolvimento)
    try {
        $conn = new mysqli($servername, "root", "", $dbname);
        // Configurações adicionais...
    } catch (Exception $e2) {
        die("Erro crítico de conexão com banco de dados: " . $e2->getMessage());
    }
}
```

**Importante**: O arquivo `db.php` está na raiz, mas também é acessado através de um link simbólico em `/includes/db.php`:

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

- **Nunca use conexões diretas** em arquivos individuais
- Sempre use o arquivo centralizado através de `require_once '../includes/db.php'`
- Utilize prepared statements para todas as consultas SQL
- Feche a conexão após o uso com `$conn->close()` quando apropriado

### Tabelas Principais do Banco de Dados

| Tabela | Descrição | Principais Campos |
|--------|-----------|-------------------|
| users | Armazena informações dos usuários | id, name, email, username, password, profile_image, is_admin |
| reports | Registros de chamados técnicos | id, user_id, data_chamado, numero_chamado, tipo_chamado, cliente, status_chamado |
| reembolsos | Solicitações de reembolso | id, user_id, data_chamado, valor, tipo_reembolso, status, arquivo_path |
| blog_posts | Artigos do blog | id, user_id, titulo, conteudo, imagem_capa, status, data_criacao |
| blog_comentarios | Comentários nos posts | id, post_id, user_id, comentario, data_criacao |
| notificacoes | Sistema de notificações | id, user_id, tipo, titulo, mensagem, link, lida, data_criacao |

## Módulos do Sistema

### 1. Sistema de Autenticação

O sistema utiliza autenticação baseada em sessões PHP. Os principais arquivos são:

- `login.php`: Formulário de login
- `page/processar-login.php`: Validação de credenciais
- `page/sair.php`: Encerramento de sessão
- `page/cadastro.php` e `page/processar-cadastro.php`: Registro de novos usuários

**Atualização 2023**: O sistema de autenticação agora inclui medidas adicionais de segurança:
- Bloqueio temporário após tentativas falhas de login
- Verificação de força de senha
- Tokens de sessão contra ataques CSRF

A autenticação verifica se o usuário é administrador através do campo `is_admin` na tabela `users`.

### 2. Sistema de Gestão de Chamados e Relatórios

O módulo de chamados (reports) permite:

- Registro de chamados de implantação e sustentação
- Controle de quilometragem
- Registro de horários de atendimento
- Upload de comprovantes
- Geração de relatórios

Principais arquivos:
- `page/meus-scripts.php`: Visualização de chamados do usuário
- `page/gerar-script.php`: Criação de novos chamados/relatórios
- `page/visualizar-relatorios.php`: Relatórios gerenciais
- `page/exportar-relatorios.php`: Exportação para Excel/PDF

### 3. Sistema de Reembolsos

Gerencia solicitações de reembolso com:

- Cadastro de solicitações por tipo (estacionamento, pedágio, alimentação, etc.)
- Upload de comprovantes
- Workflow de aprovação (pendente, aprovado, criticado, reprovado)
- Geração de relatórios financeiros
- Exportação em Excel e PDF

Principais arquivos:
- `page/meus-reembolsos.php`: Visualização de reembolsos do usuário
- `page/solicitar-reembolso.php`: Criação de solicitações
- `page/todos-reembolsos.php`: Interface de gerenciamento (admins)
- `page/exportar-reembolsos-excel.php` e `page/exportar-reembolsos-pdf.php`: Exportação

### 4. Sistema de Blog

Permite a publicação de artigos e notícias com:

- Editor de texto avançado
- Upload de imagens
- Sistema de aprovação de conteúdo
- Comentários e reações

Principais arquivos:
- `page/gerenciar-posts.php`: Listagem de artigos
- `page/visualizar-post.php`: Visualização de artigo individual
- `page/criar-post.php`: Criação de novos posts
- `page/deletar-post.php`: Remoção de posts

### 5. Sistema de Notificações

**Novidade 2023**: Sistema de notificações em tempo real para:
- Aprovação ou rejeição de reembolsos
- Comentários em posts
- Mensagens do sistema

Implementado com:
- Tabela `notificacoes` no banco de dados
- AJAX para verificação periódica (`ajax/check_notifications.php`)
- Marcação de leitura (`ajax/mark_notification_read.php`)
- Interface unificada (`page/notificacoes.php`)

### 6. Gerenciamento de Usuários

Controle completo dos usuários do sistema:

- Criação de novos usuários
- Edição de perfis
- Alteração de senhas
- Upload de fotos de perfil

Principais arquivos:
- `page/gerenciar-usuarios.php`: Interface de administração
- `page/criar-usuario.php`: Criação de usuários
- `page/editar-usuario.php`: Edição de perfis
- `page/meu-perfil.php`: Perfil do usuário logado
- `page/atualizar-foto-perfil.php`: Upload de foto de perfil

## Sistema de Upload de Arquivos

### Estrutura de Uploads

Os uploads são organizados em subdiretórios por tipo:

- `uploads/chamados/`: Arquivos relacionados a chamados
- `uploads/reembolsos/`: Comprovantes de reembolsos
- `uploads/usuarios/`: Fotos de perfil e documentos de usuários
- `uploads/temp/`: Armazenamento temporário

### Funções de Upload

O arquivo `includes/upload_functions.php` contém funções avançadas para:

- Validação segura de tipos de arquivo
- Verificação de tamanho
- Geração de nomes únicos
- Movimentação segura de arquivos
- Tratamento de erros
- Registro de logs

**Atualização 2023**: O sistema agora suporta uploads maiores através do arquivo `processar-upload-grande.php`, que implementa upload em chunks para arquivos de grande porte.

Exemplo de uso:
```php
include_once '../includes/upload_functions.php';

// Upload de arquivo
$result = process_file_upload($_FILES['arquivo'], 'reembolsos', [
    'jpg', 'jpeg', 'png', 'pdf'
], 5242880, // 5MB
'reembolso_' . $reembolso_id);

if($result['success']) {
    $arquivo_path = $result['file_path'];
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
3. Importe a estrutura do banco de dados de `db/database.sql`
4. Configure o VirtualHost do Apache para o projeto
5. Crie o link simbólico do arquivo db.php
6. Configure permissões de diretórios
7. Acesse o sistema pelo navegador

Para instruções detalhadas, consulte o arquivo [README.md](README.md).

## Solução de Problemas Comuns

### 1. Erro de Conexão com o Banco de Dados (HTTP 500)

**Sintomas**: Páginas retornam erro HTTP 500 ou páginas em branco.

**Causa comum**: O arquivo db.php não está sendo acessado corretamente.

**Solução**:
1. Verifique se existe o link simbólico em `/includes/db.php`
2. Remova conexões diretas ao banco de dados nos arquivos
3. Verifique as credenciais no arquivo db.php
4. Reinicie o Apache após as alterações

```bash
# Verifique o link simbólico
ls -la /var/www/html/soudigital/includes/db.php

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
3. Verifique as configurações do PHP para uploads no `php.ini` ou no arquivo local `.user.ini`:
   ```
   upload_max_filesize = 10M
   post_max_size = 10M
   max_execution_time = 30
   ```
4. Para arquivos grandes, use o formulário alternativo em `page/formulario-alternativo.php` que utiliza upload em chunks

## Segurança

### Proteção contra SQL Injection

O sistema implementa:
- Prepared statements para consultas SQL
- Sanitização de entradas de usuário
- Validação de dados de formulários

Exemplo de uso correto:
```php
// CORRETO: Uso de prepared statements
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// INCORRETO: Vulnerável a SQL Injection
$query = "SELECT * FROM users WHERE email = '$email'"; // NÃO FAÇA ISSO
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

Recomendações para backup regular:

1. **Backup do Banco de Dados**:
   ```bash
   # Substitua usuário/senha pelos valores corretos
   mysqldump -u sou_digital -p sou_digital > /var/www/html/soudigital/backups/db/backup_$(date +%Y%m%d).sql
   
   # Compressão do arquivo
   gzip /var/www/html/soudigital/backups/db/backup_$(date +%Y%m%d).sql
   ```

2. **Backup de Arquivos**:
   ```bash
   # Compressão do diretório de uploads
   tar -czvf /var/www/html/soudigital/backups/files/uploads_$(date +%Y%m%d).tar.gz /var/www/html/soudigital/uploads/
   ```

### Monitoramento de Logs

Os logs do sistema são armazenados em:

- **Logs de Erro do PHP/Apache**: `/var/log/apache2/soudigital_error.log`
- **Logs de Acesso**: `/var/log/apache2/soudigital_access.log`
- **Logs Internos do Sistema**: `/var/www/html/soudigital/logs/system.log`
- **Logs de Upload**: `/var/www/html/soudigital/logs/uploads.log`

Para monitorar erros em tempo real:
```bash
tail -f /var/log/apache2/soudigital_error.log
```

## Diretrizes de Desenvolvimento

### Padrões de Codificação

O sistema segue estas convenções:

- **Nomenclatura**:
  - Arquivos: minúsculas com hífens (ex: `meus-reembolsos.php`)
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

**Novidade 2023**: O sistema agora inclui um webhook do GitHub (`github-webhook.php`) que permite atualizações automáticas do código quando novas alterações são enviadas ao repositório.

### Desenvolvimento de Novas Funcionalidades

Para adicionar novas funcionalidades:

1. Crie a estrutura da base de dados necessária
2. Desenvolva a lógica de negócios em arquivos apropriados
3. Crie a interface de usuário
4. Implemente os endpoints Ajax se necessário
5. Teste exaustivamente
6. Documente a funcionalidade

## Agendamento de Tarefas (Cron Jobs)

Recomendações para tarefas agendadas:

```
# Backup diário do banco às 2h da manhã
0 2 * * * mysqldump -u sou_digital -p'SuaSenhaSegura123!' sou_digital | gzip > /var/www/html/soudigital/backups/db/backup_$(date +\%Y\%m\%d).sql.gz

# Backup semanal de arquivos aos domingos às 3h da manhã
0 3 * * 0 tar -czvf /var/www/html/soudigital/backups/files/uploads_$(date +\%Y\%m\%d).tar.gz -C /var/www/html/soudigital uploads/

# Limpeza de arquivos temporários a cada 6 horas
0 */6 * * * find /var/www/html/soudigital/uploads/temp -type f -mtime +1 -delete

# Envio de notificações a cada hora
0 * * * * php /var/www/html/soudigital/includes/send_notification.php
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

Documentação atualizada em: Junho de 2023 