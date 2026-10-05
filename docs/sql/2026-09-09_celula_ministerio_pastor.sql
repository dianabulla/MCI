-- Célula: ministerio y pastor como id de persona (INT).
-- Sin valor_sobre. Entregó sobre / reportó siguen en asistencias por semana.
-- Idempotente.

ALTER TABLE celula
    ADD COLUMN IF NOT EXISTS Id_Ministerio INT NULL AFTER Id_Lider;

ALTER TABLE celula
    ADD COLUMN IF NOT EXISTS Id_Pastor INT NULL AFTER Pastor_Principal;
