-- ============================================================
-- S.3.2 — Migration: Preparar BD para criptografia de campos
-- Executar UMA vez antes de usar o sistema com DB_ENCRYPT_KEY.
-- ============================================================

-- 1. Alargar colunas para comportar o ciphertext base64
--    (AES-256-CBC: IV 16 bytes + dados + padding → base64 ≈ 3-4× o tamanho original)
ALTER TABLE usuario
    MODIFY COLUMN nome_usuario TEXT        NOT NULL,
    MODIFY COLUMN email        TEXT        NOT NULL,
    ADD    COLUMN email_hash   VARCHAR(64) NULL AFTER email;

-- 2. Índice no blind index (busca por email sem expor o valor cifrado)
CREATE INDEX idx_email_hash ON usuario (email_hash(64));

-- 3. Alargar colunas de PII sensível em proprietario_ong (cpf, rg, telefone)
ALTER TABLE proprietario_ong
    MODIFY COLUMN cpf      TEXT NOT NULL,
    MODIFY COLUMN rg       TEXT NOT NULL,
    MODIFY COLUMN telefone TEXT NOT NULL;

-- 4. Alargar colunas sensíveis em usuario_verificacao (telegram_id, codVerificador)
--    + blind index do telegram_id (precisa de busca exata por chat_id no bot)
ALTER TABLE usuario_verificacao
    MODIFY COLUMN telegram_id     TEXT        NULL,
    MODIFY COLUMN codVerificador  TEXT        NULL,
    ADD    COLUMN telegram_id_hash VARCHAR(64) NULL AFTER telegram_id;

CREATE INDEX idx_telegram_id_hash ON usuario_verificacao (telegram_id_hash);

-- ATENÇÃO: registros existentes com email em texto puro terão email_hash = NULL.
-- Esses registros não conseguirão mais logar pelo e-mail até serem atualizados.
-- Para re-cadastrar: delete os usuários de teste e cadastre novamente.
-- ============================================================
