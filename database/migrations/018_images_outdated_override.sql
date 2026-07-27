-- Tri-state manual override for the "atualizada/desatualizada" status of a
-- photo. NULL = follow automatic detection (captured_at before the cutoff
-- date is outdated); TRUE = force outdated; FALSE = force up to date.
ALTER TABLE images ADD COLUMN IF NOT EXISTS outdated_override BOOLEAN NULL;
