-- Adicionar coluna cargo na tabela usuario
ALTER TABLE `usuario` ADD COLUMN `cargo` ENUM('cliente', 'dev') NOT NULL DEFAULT 'cliente' AFTER `foto_perfil`;

-- Atualizar usuários existentes para ter cargo 'cliente' por padrão
UPDATE `usuario` SET `cargo` = 'cliente' WHERE `cargo` IS NULL OR `cargo` = '';
