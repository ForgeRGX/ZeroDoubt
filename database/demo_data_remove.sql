-- Elimina el usuario «demo» y todos sus datos (movimientos, reglas, presupuestos y categorías).
-- Uso:
--   docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -u root "$MYSQL_DATABASE"' < database/demo_data_remove.sql
START TRANSACTION;
SET @uid = (SELECT id FROM users WHERE username = 'demo');
DELETE FROM transactions    WHERE user_id = @uid;
DELETE FROM recurring_rules WHERE user_id = @uid;
DELETE FROM budgets         WHERE user_id = @uid;
DELETE FROM categories      WHERE user_id = @uid;
DELETE FROM users           WHERE id = @uid;
COMMIT;
