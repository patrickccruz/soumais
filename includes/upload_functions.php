<?php
/**
 * Funções auxiliares para gerenciamento de uploads
 */

/**
 * Função segura para escrever nos logs sem gerar warnings
 * 
 * @param string $log_file Caminho do arquivo de log
 * @param string $message Mensagem a ser escrita
 * @return bool Se a operação foi realizada com sucesso
 */
function safe_log($log_file, $message) {
    // Verifica se o diretório existe, se não, tenta criar
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        // Tenta criar o diretório, ignora erro silenciosamente
        @mkdir($log_dir, 0777, true);
        
        // Verifica se conseguiu criar
        if (!is_dir($log_dir)) {
            return false;
        }
    }
    
    // Verifica se o diretório é gravável
    if (!is_writable($log_dir)) {
        return false;
    }
    
    // Tenta escrever no arquivo, sem gerar warnings
    return @file_put_contents($log_file, $message, FILE_APPEND) !== false;
}

/**
 * Cria os diretórios de upload com tratamento de erros adequado
 * 
 * @param string $path Caminho a ser criado
 * @return bool Se o diretório existe ou foi criado com sucesso
 */
function create_directory_safe($path) {
    if (is_dir($path)) {
        return true;
    }
    
    $result = @mkdir($path, 0777, true);
    
    // Se falhou em criar, tenta determinar se já existe (concorrência)
    if (!$result && is_dir($path)) {
        return true;
    }
    
    return $result;
}

/**
 * Cria a estrutura de diretórios para um upload
 * @param string $base_path Caminho base (uploads/)
 * @param array $subdirs Array com os subdiretórios a serem criados
 * @return string Caminho completo criado
 */
function create_upload_path($base_path, $subdirs) {
    // Verificar e criar diretório base
    if (!create_directory_safe($base_path)) {
        return false;
    }
    
    $current_path = $base_path;
    
    // Criar subdiretórios
    foreach ($subdirs as $dir) {
        if (empty($dir)) continue;
        
        $current_path .= '/' . $dir;
        
        if (!create_directory_safe($current_path)) {
            return false;
        }
    }
    
    return $current_path;
}

/**
 * Gera um nome único para o arquivo
 * @param string $original_name Nome original do arquivo
 * @param string $prefix Prefixo opcional para o nome do arquivo
 * @return string Nome único do arquivo
 */
function generate_unique_filename($original_name, $prefix = '') {
    // Limpar o nome do arquivo para segurança
    $clean_name = preg_replace('/[^a-zA-Z0-9\._-]/', '_', basename($original_name));
    $ext = pathinfo($clean_name, PATHINFO_EXTENSION);
    return $prefix . uniqid() . '_' . mt_rand(1000, 9999) . '.' . $ext;
}

/**
 * Versão segura da função move_uploaded_file
 * Realiza validações adicionais e sanitização
 * 
 * @param string $tmp_name Caminho temporário do arquivo
 * @param string $destination Destino final do arquivo
 * @param bool $log_enabled Habilitar logs detalhados
 * @return bool Se a operação foi bem sucedida
 */
function move_uploaded_file_safe($tmp_name, $destination, $log_enabled = true) {
    $log_file = dirname(__DIR__) . '/logs/upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    
    if ($log_enabled) {
        safe_log($log_file, "\n[$timestamp] === INICIANDO UPLOAD SEGURO ===\n");
        safe_log($log_file, "[$timestamp] Arquivo origem: $tmp_name\n");
        safe_log($log_file, "[$timestamp] Destino: $destination\n");
    }
    
    // Verificar se o arquivo de origem existe e é um arquivo enviado
    if (!is_uploaded_file($tmp_name)) {
        if ($log_enabled) {
            safe_log($log_file, "[$timestamp] ERRO: O arquivo não é um upload válido\n");
        }
        return false;
    }
    
    // Garantir que o diretório de destino existe
    $destination_dir = dirname($destination);
    if (!is_dir($destination_dir)) {
        if (!create_directory_safe($destination_dir) && $log_enabled) {
            safe_log($log_file, "[$timestamp] ERRO: Não foi possível criar o diretório de destino: $destination_dir\n");
            return false;
        }
    }
    
    // Tentar mover o arquivo
    if (!move_uploaded_file($tmp_name, $destination)) {
        if ($log_enabled) {
            safe_log($log_file, "[$timestamp] ERRO: Falha ao mover o arquivo\n");
        }
        return false;
    }
    
    if ($log_enabled) {
        safe_log($log_file, "[$timestamp] Arquivo movido com sucesso\n");
    }
    
    // Verificar permissões
    chmod($destination, 0644);
    
    if ($log_enabled) {
        safe_log($log_file, "[$timestamp] Permissões definidas: 0644\n");
        safe_log($log_file, "[$timestamp] === UPLOAD CONCLUÍDO ===\n");
    }
    
    return true;
}

/**
 * Verifica se o tipo de arquivo é permitido
 * @param string $file_tmp_name Caminho temporário do arquivo
 * @param array $allowed_types Array com os tipos MIME permitidos
 * @param string $custom_log_file Arquivo de log personalizado (opcional)
 * @return bool|string True se o tipo é permitido, ou string com o erro
 */
function validate_file_type($file_tmp_name, $allowed_types, $custom_log_file = null) {
    $log_file = $custom_log_file ?? dirname(__DIR__) . '/logs/upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    
    if (!file_exists($file_tmp_name)) {
        file_put_contents($log_file, "[$timestamp] ERRO: Arquivo não existe para validação de tipo\n", FILE_APPEND);
        return "Arquivo não existe";
    }
    
    // Verificar tipo MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file_tmp_name);
    
    file_put_contents($log_file, "[$timestamp] Validando tipo de arquivo: $mime_type\n", FILE_APPEND);
    file_put_contents($log_file, "[$timestamp] Tipos permitidos: " . implode(', ', $allowed_types) . "\n", FILE_APPEND);
    
    if (!in_array($mime_type, $allowed_types)) {
        file_put_contents($log_file, "[$timestamp] ERRO: Tipo de arquivo não permitido\n", FILE_APPEND);
        return "Tipo de arquivo não permitido: $mime_type. Apenas os seguintes tipos são aceitos: " . implode(', ', $allowed_types);
    }
    
    file_put_contents($log_file, "[$timestamp] Tipo de arquivo válido\n", FILE_APPEND);
    return true;
}

/**
 * Verifica o tamanho do arquivo
 * @param int $file_size Tamanho do arquivo em bytes
 * @param int $max_size Tamanho máximo permitido em bytes
 * @param string $custom_log_file Arquivo de log personalizado (opcional)
 * @return bool|string True se o tamanho é permitido, ou string com o erro
 */
function validate_file_size($file_size, $max_size, $custom_log_file = null) {
    $log_file = $custom_log_file ?? dirname(__DIR__) . '/logs/upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    
    file_put_contents($log_file, "[$timestamp] Validando tamanho do arquivo: " . format_bytes($file_size) . "\n", FILE_APPEND);
    file_put_contents($log_file, "[$timestamp] Tamanho máximo permitido: " . format_bytes($max_size) . "\n", FILE_APPEND);
    
    if ($file_size > $max_size) {
        file_put_contents($log_file, "[$timestamp] ERRO: Arquivo muito grande\n", FILE_APPEND);
        return "O arquivo é muito grande (" . format_bytes($file_size) . "). O tamanho máximo permitido é " . format_bytes($max_size);
    }
    
    file_put_contents($log_file, "[$timestamp] Tamanho do arquivo válido\n", FILE_APPEND);
    return true;
}

/**
 * Função para formatar bytes para exibição
 * @param int $bytes Número de bytes
 * @param int $precision Precisão decimal
 * @return string Tamanho formatado com unidade
 */
function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Função para processar um upload de arquivo de forma completa
 * @param array $file Array do arquivo ($_FILES['campo'])
 * @param string $destination Caminho de destino
 * @param array $allowed_types Tipos MIME permitidos
 * @param int $max_size Tamanho máximo em bytes
 * @param string $prefix Prefixo para o nome do arquivo
 * @param string $custom_log_file Arquivo de log personalizado (opcional)
 * @return array Array com resultado ['success' => bool, 'message' => string, 'path' => string]
 */
function process_file_upload($file, $destination, $allowed_types, $max_size = 10485760, $prefix = '', $custom_log_file = null) {
    // Configurar log
    $log_dir = dirname(__DIR__) . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $log_file = $custom_log_file ?? $log_dir . '/upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    
    file_put_contents($log_file, "\n[$timestamp] === INICIANDO PROCESSAMENTO DE UPLOAD ===\n", FILE_APPEND);
    
    // Validar existência do arquivo
    if (!isset($file) || !is_array($file) || empty($file) || $file['error'] != UPLOAD_ERR_OK) {
        $error_message = "Erro no upload do arquivo: ";
        
        if (isset($file['error'])) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $error_message .= "O arquivo excede o tamanho máximo permitido pelo PHP (" . ini_get('upload_max_filesize') . ")";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message .= "O arquivo excede o tamanho máximo especificado no formulário";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message .= "O arquivo foi parcialmente enviado";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_message .= "Nenhum arquivo foi enviado";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error_message .= "Diretório temporário não encontrado";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error_message .= "Falha ao gravar arquivo no disco";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error_message .= "Upload interrompido por uma extensão PHP";
                    break;
                default:
                    $error_message .= "Erro desconhecido (código " . $file['error'] . ")";
            }
        } else {
            $error_message .= "Arquivo não encontrado ou inválido";
        }
        
        file_put_contents($log_file, "[$timestamp] $error_message\n", FILE_APPEND);
        return ['success' => false, 'message' => $error_message, 'path' => ''];
    }
    
    // Registrar informações do arquivo
    file_put_contents($log_file, "[$timestamp] Arquivo recebido: " . $file['name'] . "\n", FILE_APPEND);
    file_put_contents($log_file, "[$timestamp] Tamanho: " . $file['size'] . " bytes\n", FILE_APPEND);
    file_put_contents($log_file, "[$timestamp] Tipo informado: " . $file['type'] . "\n", FILE_APPEND);
    
    // Validar tipo de arquivo
    $type_validation = validate_file_type($file['tmp_name'], $allowed_types, $log_file);
    if ($type_validation !== true) {
        return ['success' => false, 'message' => $type_validation, 'path' => ''];
    }
    
    // Validar tamanho do arquivo
    $size_validation = validate_file_size($file['size'], $max_size, $log_file);
    if ($size_validation !== true) {
        return ['success' => false, 'message' => $size_validation, 'path' => ''];
    }
    
    // Criar diretório de destino se não existir
    $upload_dir = dirname($destination);
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $error_message = "Não foi possível criar o diretório de destino: $upload_dir";
            file_put_contents($log_file, "[$timestamp] ERRO: $error_message\n", FILE_APPEND);
            return ['success' => false, 'message' => $error_message, 'path' => ''];
        }
        file_put_contents($log_file, "[$timestamp] Diretório de destino criado: $upload_dir\n", FILE_APPEND);
    }
    
    // Gerar nome único para o arquivo
    $filename = generate_unique_filename($file['name'], $prefix);
    $full_path = $upload_dir . '/' . $filename;
    
    file_put_contents($log_file, "[$timestamp] Nome de arquivo gerado: $filename\n", FILE_APPEND);
    file_put_contents($log_file, "[$timestamp] Caminho completo: $full_path\n", FILE_APPEND);
    
    // Mover o arquivo
    if (move_uploaded_file_safe($file['tmp_name'], $full_path, false)) {
        file_put_contents($log_file, "[$timestamp] Upload bem-sucedido para: $full_path\n", FILE_APPEND);
        file_put_contents($log_file, "[$timestamp] === FIM DO PROCESSAMENTO (SUCESSO) ===\n", FILE_APPEND);
        return [
            'success' => true, 
            'message' => 'Arquivo enviado com sucesso', 
            'path' => $upload_dir . '/' . $filename,
            'filename' => $filename
        ];
    } else {
        $error_message = "Falha ao mover o arquivo para o destino";
        file_put_contents($log_file, "[$timestamp] ERRO: $error_message\n", FILE_APPEND);
        file_put_contents($log_file, "[$timestamp] === FIM DO PROCESSAMENTO (FALHA) ===\n", FILE_APPEND);
        return ['success' => false, 'message' => $error_message, 'path' => ''];
    }
}

/**
 * Verifica se o tipo de arquivo é permitido (função legada)
 * @param string $mime_type Tipo MIME do arquivo
 * @param array $allowed_types Array com os tipos permitidos
 * @return bool True se o tipo é permitido
 */
function is_allowed_file_type($mime_type, $allowed_types) {
    return in_array($mime_type, $allowed_types);
}

/**
 * Remove um arquivo e seu diretório pai se estiver vazio
 * @param string $file_path Caminho completo do arquivo
 * @return bool True se o arquivo foi removido com sucesso
 */
function remove_file_and_empty_dir($file_path) {
    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            $dir = dirname($file_path);
            if (is_dir($dir) && count(scandir($dir)) <= 2) { // . e ..
                rmdir($dir);
            }
            return true;
        }
    }
    return false;
}

/**
 * Retorna o caminho para upload baseado no tipo e parâmetros
 * 
 * @param string $type Tipo de upload (user, post, etc)
 * @param array $params Parâmetros adicionais (user_id, post_id, etc)
 * @return string Caminho relativo para upload
 */
function get_upload_path($type, $params = []) {
    $log_file = dirname(__DIR__) . '/logs/upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    
    safe_log($log_file, "[$timestamp] get_upload_path chamado para tipo: $type, params: " . json_encode($params) . "\n");
    
    $base_path = dirname(__DIR__) . '/uploads';
    
    safe_log($log_file, "[$timestamp] Base path calculado: $base_path\n");
    
    // Criar o diretório base se não existir
    if (!is_dir($base_path)) {
        safe_log($log_file, "[$timestamp] Diretório base não existe, tentando criar: $base_path\n");
        if (!create_directory_safe($base_path)) {
            safe_log($log_file, "[$timestamp] ERRO ao criar diretório base: $base_path\n");
            return false;
        }
    }
    
    $year = date('Y');
    $month = date('m');
    
    $path_parts = [$type];
    
    // Adicionar ano e mês para organização
    $path_parts[] = $year;
    $path_parts[] = $month;
    
    // Adicionar parâmetros específicos
    switch ($type) {
        case 'profile':
            if (isset($params['user_id'])) {
                $path_parts[] = 'user_' . $params['user_id'];
            }
            break;
        case 'report':
            if (isset($params['report_id'])) {
                $path_parts[] = 'report_' . $params['report_id'];
            }
            break;
        case 'reimbursement':
            if (isset($params['reimbursement_id'])) {
                $path_parts[] = 'reimb_' . $params['reimbursement_id'];
            }
            break;
        default:
            // Tipo genérico
            break;
    }
    
    $upload_dir = create_upload_path($base_path, $path_parts);
    
    // Aqui está a mudança principal: retornar o caminho relativo ao diretório raiz
    // Em vez do caminho absoluto do sistema de arquivos
    $relative_path = 'uploads/' . implode('/', $path_parts);
    
    safe_log($log_file, "[$timestamp] Caminho final para upload: $relative_path\n");
    
    // Retornar tanto o caminho absoluto (para salvar o arquivo) quanto o caminho relativo (para armazenar no banco)
    return [
        'absolute_path' => $upload_dir,
        'relative_path' => $relative_path
    ];
} 