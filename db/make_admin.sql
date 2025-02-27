-- Atualizar usuário para administrador
UPDATE users SET is_admin = TRUE WHERE username = 'SEU_USERNAME';

-- Verificar se a atualização funcionou
SELECT id, username, name, is_admin FROM users WHERE username = 'SEU_USERNAME'; 