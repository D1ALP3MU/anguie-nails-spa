-- =====================================================
-- 002 · Elimina las tablas de tienda en línea
-- =====================================================
--
-- productos, carrito_items, pedidos y pedido_items estaban en el
-- esquema desde el inicio y ningún código las usaba. Correspondían
-- a un producto distinto —una tienda— que no se va a construir: el
-- valor del sistema está en la agenda, no en vender producto por
-- internet.
--
-- Se eliminan en lugar de dejarlas dormidas. Un esquema con tablas
-- que nadie toca confunde a quien lo lea, y recrearlas el día que
-- hagan falta cuesta minutos; además se diseñarían mejor, con el
-- cobro resuelto desde el principio en vez de parcheado.
--
-- pagos se conserva: cuelga de citas y sí se va a implementar.
--
-- El orden importa: primero las que tienen claves foráneas hacia
-- las otras.
-- =====================================================

DROP TABLE IF EXISTS pedido_items;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS carrito_items;
DROP TABLE IF EXISTS productos;

-- Reversión: no la hay. Si la tienda vuelve, se diseña de nuevo
-- a partir de database/schema.sql.
