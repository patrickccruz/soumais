<?php
/**
 * Funções auxiliares para gerenciamento de uploads
 */

/**
 * Cria a estrutura de diretórios para um upload
 * @param string $base_path Caminho base (uploads/)
 * @param array $subdirs Array com os subdiretórios a serem criados
 * @return string Caminho completo criado
 */
function create_upload_path($base_path, $subdirs) {
    $log_file = dirname(__DIR__) . '/logs/upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    
    file_put_contents($log_file, "[$timestamp] Criando caminho de upload: $base_path\n", FILE_APPEND);
    
    // Garantir que o diretório base existe
    if (!is_dir($base_path)) {
        mkdir($base_path, 0777, true);
        file_put_contents($log_file, "[$timestamp] Diretório base criado: $base_path\n", FILE_APPEND);
    }
    
    $current_path = $base_path;
    foreach ($subdirs as $dir) {
        $current_path .= '/' . $dir;
        if (!is_dir($current_path)) {
            if (mkdir($current_path, 0777, true)) {
                file_put_contents($log_file, "[$timestamp] Subdiretório criado: $current_path\n", FILE_APPEND);
            } else {
                file_put_contents($log_file, "[$timestamp] ERRO ao criar subdiretório: $current_path\n", FILE_APPEND);
            }
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
 * Move um arquivo para o diretório de destino com múltiplas tentativas
 * @param string $tmp_name Nome temporário do arquivo
 * @param string $destination Caminho de destino completo
 * @param bool $log_enabled Ativar logging detalhado
 * @return bool True se o arquivo foi movido com sucesso
 */
function move_uploaded_file_safe($tmp_name, $destination, $log_enabled = true) {
    // Garantir que o diretório de log existe
    $log_dir = dirname(__DIR__) . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $log_file = $log_dir . '/upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    
    if ($log_enabled) {
        file_put_contents($log_file, "\n[$timestamp] === INICIANDO UPLOAD SEGURO ===\n", FILE_APPEND);
        file_put_contents($log_file, "[$timestamp] Arquivo origem: $tmp_name\n", FILE_APPEND);
        file_put_contents($log_file, "[$timestamp] Destino: $destination\n", FILE_APPEND);
    }
    
    // Converter caminho relativo para absoluto
    $absolute_path = $destination;
    if (strpos($destination, '/') !== 0 && strpos($destination, ':') === false) {
        $absolute_path = dirname(__DIR__) . '/' . $destination;
    }
    
    if ($log_enabled) {
        file_put_contents($log_file, "[$timestamp] Caminho absoluto: $absolute_path\n", FILE_APPEND);
    }
    
    // Criar diretório se não existir
    $dir = dirname($absolute_path);
    if ($log_enabled) {
        file_put_contents($log_file, "[$timestamp] Diretório destino: $dir\n", FILE_APPEND);
    }
    
    if (!is_dir($dir)) {
        if ($log_enabled) {
            file_put_contents($log_file, "[$timestamp] Criando diretório: $dir\n", FILE_APPEND);
        }
        
        $mkdir_result = mkdir($dir, 0777, true);
        
        if ($log_enabled) {
            file_put_contents($log_file, "[$timestamp] Resultado da criação: " . ($mkdir_result ? "Sucesso" : "Falha") . "\n", FILE_APPEND);
        }
        
        if (!$mkdir_result) {
            if ($log_enabled) {
                $error = error_get_last();
                file_put_contents($log_file, "[$timestamp] ERRO ao criar diretório: " . ($error ? $error['message'] : 'Desconhecido') . "\n", FILE_APPEND);
            }
            return false;
        }
    }
    
    // Verificar se o arquivo temporário existe
    if (!file_exists($tmp_name)) {
        if ($log_enabled) {
            file_put_contents($log_file, "[$timestamp] ERRO: Arquivo temporário não existe\n", FILE_APPEND);
        }
        return false;
    }
    
    // Verificar se o arquivo temporário é legível
    if (!is_readable($tmp_name)) {
        if ($log_enabled) {
            file_put_contents($log_file, "[$timestamp] ERRO: Arquivo temporário não é legível\n", FILE_APPEND);
        }
        return false;
    }
    
    // Tentativas de upload usando diferentes métodos
    $upload_success = false;
    
    // Método 1: move_uploaded_file (melhor para uploads reais de formulários)
    if (is_uploaded_file($tmp_name)) {
        if ($log_enabled) {
            file_put_contents($log_file, "[$timestamp] Tentativa 1: move_uploaded_file\n", FILE_APPEND);
        }
        
        if (move_uploaded_file($tmp_name, $absolute_path)) {
            $upload_success = true;
            if ($log_enabled) {
                file_put_contents($log_file, "[$timestamp] SUCESSO com move_uploaded_file\n", FILE_APPEND);
            }
        } else {
            if ($log_enabled) {
                $error = error_get_last();
                file_put_contents($log_file, "[$timestamp] FALHA com move_uploaded_file: " . ($error ? $error['message'] : 'Desconhecido') . "\n", FILE_APPEND);
            }
        }
    } else {
        if ($log_enabled) {
            file_put_contents($log_file, "[$timestamp] Não é um upload de formulário, pulando move_uploaded_file\n", FILE_APPEND);
        }
    }
    
    // Método 2: copy (alternativa para qualquer arquivo)
    if (!$upload_success) {
        if ($log_enabled) {
            file_put_contents($log_file, "[$timestamp] Tentativa 2: copy\n", FILE_APPEND);
        }
        
        if (copy($tmp_name, $absolute_path)) {
            $upload_success = true;
            if ($log_enabled) {
                file_put_contents($log_file, "[$timestamp] SUCESSO com copy\n", FILE_APPEND);
            }
            
            // Tentar remover o arquivo original após a cópia (apenas se não for um upload real)
            if (!is_uploaded_file($tmp_name)) {
                @unlink($tmp_name);
            }
        } else {
            if ($log_enabled) {
                $error = error_get_last();
                file_put_contents($log_file, "[$timestamp] FALHA com copy: " . ($error ? $error['message'] : 'Desconhecido') . "\n", FILE_APPEND);
            }
        }
    }
    
    // Método 3: file_put_contents (último recurso)
    if (!$upload_success) {
        if ($log_enabled) {
            file_put_contents($log_file, "[$timestamp] Tentativa 3: file_put_contents\n", FILE_APPEND);
        }
        
        $file_content = @file_get_contents($tmp_name);
        if ($file_content !== false) {
            if (file_put_contents($absolute_path, $file_content)) {
                $upload_success = true;
                if ($log_enabled) {
                    file_put_contents($log_file, "[$timestamp] SUCESSO com file_put_contents\n", FILE_APPEND);
                }
                
                // Tentar remover o arquivo original (apenas se não for um upload real)
                if (!is_uploaded_file($tmp_name)) {
                    @unlink($tmp_name);
                }
            } else {
                if ($log_enabled) {
                    $error = error_get_last();
                    file_put_contents($log_file, "[$timestamp] FALHA com file_put_contents: " . ($error ? $error['message'] : 'Desconhecido') . "\n", FILE_APPEND);
                }
            }
        } else {
            if ($log_enabled) {
                file_put_contents($log_file, "[$timestamp] FALHA ao ler conteúdo do arquivo temporário\n", FILE_APPEND);
            }
        }
    }
    
    // Verificar resultado final
    if ($upload_success) {
        // Definir permissões para o arquivo
        chmod($absolute_path, 0644);
        
        if ($log_enabled) {
            file_put_contents($log_file, "[$timestamp] Arquivo salvo com sucesso em $absolute_path\n", FILE_APPEND);
            file_put_contents($log_file, "[$timestamp] Permissões definidas: 0644\n", FILE_APPEND);
        }
    } else {
        if ($log_enabled) {
            file_put_contents($log_file, "[$timestamp] FALHA: Todas as tentativas de upload falharam\n", FILE_APPEND);
            
            // Verificar permissões de diretórios
            $tmp_dir_perms = substr(sprintf('%o', fileperms(dirname($tmp_name))), -4);
            $dest_dir_perms = substr(sprintf('%o', fileperms($dir)), -4);
            
            file_put_contents($log_file, "[$timestamp] Permissões do diretório temporário: $tmp_dir_perms\n", FILE_APPEND);
            file_put_contents($log_file, "[$timestamp] Permissões do diretório de destino: $dest_dir_perms\n", FILE_APPEND);
            
            // Verificar espaço em disco
            $disk_free = disk_free_space($dir);
            $file_size = filesize($tmp_name);
            
            file_put_contents($log_file, "[$timestamp] Espaço livre no disco: " . format_bytes($disk_free) . "\n", FILE_APPEND);
            file_put_contents($log_file, "[$timestamp] Tamanho do arquivo: " . format_bytes($file_size) . "\n", FILE_APPEND);
        }
    }
    
    if ($log_enabled) {
        file_put_contents($log_file, "[$timestamp] === FIM DO UPLOAD ===\n", FILE_APPEND);
    }
    
    return $upload_success;
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
 * Retorna o caminho relativo para upload baseado no tipo
 * @param string $type Tipo de upload (profile, blog, reimbursement, reports)
 * @param array $params Parâmetros adicionais (user_id, post_id, etc)
 * @return string Caminho relativo para upload
 */
function get_upload_path($type, $params = []) {
    $log_file = dirname(__DIR__) . '/logs/upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    
    file_put_contents($log_file, "[$timestamp] get_upload_path chamado para tipo: $type, params: " . json_encode($params) . "\n", FILE_APPEND);
    
    $base_path = dirname(__DIR__) . '/uploads';
    
    file_put_contents($log_file, "[$timestamp] Base path calculado: $base_path\n", FILE_APPEND);
    
    // Criar o diretório base se não existir
    if (!is_dir($base_path)) {
        file_put_contents($log_file, "[$timestamp] Diretório base não existe, tentando criar: $base_path\n", FILE_APPEND);
        $mkdir_result = mkdir($base_path, 0777, true);
        file_put_contents($log_file, "[$timestamp] Resultado da criação do diretório base: " . ($mkdir_result ? "Sucesso" : "Falha") . "\n", FILE_APPEND);
    }
    
    switch($type) {
        case 'reimbursement':
            if (!isset($params['reimbursement_id'])) {
                throw new Exception('ID do reembolso é necessário');
            }
            $path = $base_path . '/reimbursements/' . $params['reimbursement_id'];
            break;
        case 'reports':
            if (!isset($params['report_id'])) {
                throw new Exception('ID do relatório é necessário');
            }
            $path = $base_path . '/reports/' . $params['report_id'];
            break;
        case 'profile':
            $path = $base_path . '/profiles';
            break;
        case 'blog':
            if (!isset($params['post_id'])) {
                throw new Exception('ID do post é necessário');
            }
            $path = $base_path . '/blog/' . $params['post_id'];
            break;
        default:
            $path = $base_path . '/misc';
    }
    
    // Criar o diretório específico se não existir
    if (!is_dir($path)) {
        file_put_contents($log_file, "[$timestamp] Diretório específico não existe, tentando criar: $path\n", FILE_APPEND);
        $mkdir_result = mkdir($path, 0777, true);
        file_put_contents($log_file, "[$timestamp] Resultado da criação do diretório específico: " . ($mkdir_result ? "Sucesso" : "Falha") . "\n", FILE_APPEND);
        if (!$mkdir_result) {
            file_put_contents($log_file, "[$timestamp] ERRO ao criar diretório: " . error_get_last()['message'] . "\n", FILE_APPEND);
        }
    } else {
        file_put_contents($log_file, "[$timestamp] Diretório específico já existe: $path\n", FILE_APPEND);
    }
    
    $relative_path = 'uploads' . substr($path, strlen($base_path));
    file_put_contents($log_file, "[$timestamp] Caminho relativo calculado: $relative_path\n", FILE_APPEND);
    
    // Retornar o caminho relativo para armazenamento no banco de dados
    return $relative_path;
} 
} 