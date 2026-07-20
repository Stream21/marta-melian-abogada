-- Reset operativo: borra todos los expedientes y clientes.
-- Mantiene: servicio, tramite, tramite_documento_requerido, servicio_documento_requerido,
--           escrito_plantilla, hoja_encargo_plantilla, despacho_config, area_juridica, app_user.
--
-- ⚠️  IRREVERSIBLE. Ejecutar solo en desarrollo o tras backup.
--
-- Uso (desde la raíz del proyecto):
--   Get-Content scripts/reset-expedientes-clientes.sql | docker-compose exec -T postgres psql -U bufete -d bufete
--   cat scripts/reset-expedientes-clientes.sql | docker-compose exec -T postgres psql -U bufete -d bufete
--
-- Nota: los PDFs/archivos en public/storage/expedientes/ NO se borran con este script.

BEGIN;

TRUNCATE TABLE
    expediente_firma_otp,
    expediente_firma_documento,
    expediente_documento_entregado,
    expediente_documento_requerido,
    expediente_escrito,
    expediente_contratacion_paso,
    expediente_hito,
    payment,
    invoice,
    expediente,
    cliente
RESTART IDENTITY;

COMMIT;

-- Verificación (opcional):
-- SELECT 'expediente' AS tabla, COUNT(*) FROM expediente
-- UNION ALL SELECT 'cliente', COUNT(*) FROM cliente
-- UNION ALL SELECT 'servicio', COUNT(*) FROM servicio
-- UNION ALL SELECT 'tramite', COUNT(*) FROM tramite;
