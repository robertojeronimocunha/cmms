-- Solicitações de peça na OS guardam só cópia dos dados; sem FK ao catálogo.
ALTER TABLE os_solicitacoes_pecas DROP COLUMN IF EXISTS peca_catalogo_id;
