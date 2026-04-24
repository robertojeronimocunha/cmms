-- Permite que o próprio utilizador altere a senha (menu lateral). Por defeito ativo.
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS permite_trocar_senha BOOLEAN NOT NULL DEFAULT true;
