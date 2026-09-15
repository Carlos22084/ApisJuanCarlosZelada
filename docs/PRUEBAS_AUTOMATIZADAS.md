# Implementación de pruebas automatizadas

## 1. Objetivo y alcance

Se automatizaron pruebas para los flujos de mayor riesgo del sistema web de Tapicería A&A: autenticación, cotizaciones, pedidos, autorización por roles, producción, generación visual mediante IA y notificaciones por correo. El propósito es detectar regresiones sin alterar la base MySQL ni consumir servicios externos.

## 2. Herramienta y configuración

Se utiliza **PHPUnit 11.5**, integrado con Laravel 12 mediante `php artisan test`. La configuración está en `phpunit.xml` y separa las suites `Unit` y `Feature`.

El entorno de prueba establece:

- `APP_ENV=testing` para separar la ejecución del entorno local.
- SQLite `:memory:` para crear una base desechable por ejecución.
- `RefreshDatabase` para migrar y aislar cada caso.
- Caché y sesiones en memoria para mejorar velocidad y repetibilidad.
- Cola síncrona para evitar procesos externos.
- `Http::fake()` para simular Cloudflare sin gastar cuota ni depender de internet.

Esta configuración evita modificar `aya_multiservicios` y permite repetir las pruebas con el mismo resultado.

## 3. Diseño de casos de prueba

Los casos usan el patrón **Preparar, Ejecutar y Verificar (Arrange, Act, Assert)**. Se comprueba tanto la respuesta HTTP como los efectos persistentes y las restricciones de seguridad.

| ID | Caso | Tipo | Resultado esperado |
|---|---|---|---|
| CP-01 | Visitante intenta enviar una cotización | Seguridad | Redirección al ingreso y ningún pedido creado |
| CP-02 | Cliente registra una cotización válida | Funcional/integración | Pedido pendiente, precio base, cinco tareas e historial |
| CP-03 | Cliente intenta usar el diseño IA de otro cliente | Seguridad | HTTP 404 y ningún pedido asociado |
| CP-04 | Trabajador inicia una tarea de producción | Integración | Fecha de inicio y pedido actualizado a `in_progress` |
| CP-05 | Cliente intenta modificar una tarea | Autorización | HTTP 403 y tarea sin cambios |
| CP-06 | Administrador envía datos inválidos | Validación | Errores en nueve campos y cero escrituras |
| CP-07 | Cliente genera un diseño con IA | Integración simulada | Archivo y registro creados; llamada autenticada a Cloudflare |
| CP-08 | Faltan credenciales de Cloudflare | Excepción controlada | HTTP 503 con mensaje explícito |
| CP-09 | Creación de pedido con notificación Resend | Integración simulada | Pedido guardado y correo enviado |
| CP-10 | Cambio de estado con notificación | Integración simulada | Estado actualizado y correo enviado |
| CP-11 | Resend sin configurar | Excepción controlada | El pedido se guarda y se informa que no se envió correo |

Los casos adicionales existentes cubren páginas públicas, seguimiento, inicio de sesión, paneles por rol, creación administrativa y asistente virtual.

## 4. Script de automatización

El script `scripts/run-tests.ps1` realiza automáticamente:

1. Validación de PHP y dependencias.
2. Creación del directorio de resultados.
3. Ejecución completa o filtrada de PHPUnit.
4. Generación de `junit.xml`, formato interoperable con sistemas CI.
5. Lectura de métricas y creación de `summary.md`.
6. Propagación del código de salida: `0` si aprueba y distinto de `0` si falla.

Ejecución completa:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File scripts/run-tests.ps1
```

Ejecución de una clase específica:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File scripts/run-tests.ps1 -Filter OrderWorkflowTest
```

También se puede usar:

```powershell
composer test
composer test:report
```

Los resultados se escriben en:

```text
storage/test-results/junit.xml
storage/test-results/summary.md
```

## 5. Automatización continua

El flujo `.github/workflows/tests.yml` ejecuta las pruebas en cada `push`, cada `pull_request` y manualmente. Instala PHP 8.2 y las dependencias, prepara Laravel y ejecuta `composer test`. Un fallo produce un estado de CI fallido e impide considerar correcto el cambio.

## 6. Resultados y análisis

La ejecución de referencia registra el número total de casos, aserciones, fallos, errores, omitidos y duración. El resultado vigente y reproducible se encuentra en `storage/test-results/summary.md`.

Resultado obtenido el 15 de septiembre de 2026:

| Métrica | Resultado |
|---|---:|
| Casos ejecutados | 20 |
| Aserciones | 98 |
| Fallos | 0 |
| Errores | 0 |
| Omitidos | 0 |
| Estado final | APROBADO |

El resultado demuestra que los flujos cubiertos cumplen sus salidas esperadas en un entorno aislado. No hubo casos omitidos que reduzcan el alcance declarado ni fallos que requieran corrección funcional.

Criterios de interpretación:

- **Aprobado:** cero fallos y cero errores; el cambio satisface todos los comportamientos comprobados.
- **Fallo:** una aserción obtuvo un resultado diferente al esperado; normalmente indica una regresión funcional.
- **Error:** la prueba no pudo completar su ejecución por una excepción o problema de configuración.
- **Omitido:** caso no ejecutado por una condición explícita; debe justificarse antes de entregar.

La estrategia es eficiente porque usa una sola ejecución de PHPUnit, base en memoria y dobles de prueba para APIs. Es precisa porque no se limita a revisar códigos HTTP: valida registros, relaciones, cantidad y secuencia de tareas, historial, marcas de tiempo, propiedad de diseños y ausencia de escrituras en escenarios inválidos.

## 7. Limitaciones

- Las pruebas `Feature` ejercitan Laravel sin abrir un navegador real; no sustituyen una prueba visual manual de CSS o compatibilidad entre navegadores.
- Cloudflare y Resend se simulan deliberadamente. Una prueba contra cuentas reales sería más lenta, variable y podría consumir cuota.
- Las credenciales nunca deben agregarse a pruebas, reportes o control de versiones.

## 8. Conclusión

La configuración es aislada y reproducible, el script automatiza ejecución y reporte, los casos cubren rutas positivas, negativas y de seguridad, y la documentación permite repetir e interpretar los resultados. Esto satisface los criterios de configuración, funcionalidad, eficiencia, precisión y claridad solicitados.
