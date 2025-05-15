-- Atualizar apostas sem revendedor para associá-las ao revendedor ID 9 (Adriano Cunha)
UPDATE apostas SET revendedor_id = 9 WHERE revendedor_id IS NULL;

-- Verificar a contagem após a atualização
-- SELECT COUNT(*) FROM apostas WHERE revendedor_id = 9; 