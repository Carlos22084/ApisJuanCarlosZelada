# Sistema web A&A Multiservicios

Aplicación web desarrollada a partir del proyecto de grado **“Sistema web inteligente para la gestión y personalización de pedidos para la Tapicería A&A Multiservicios”**.

## Funciones implementadas

- Sitio público responsive con servicios y personalizador visual.
- Registro, inicio de sesión y roles: administrador, trabajador y cliente.
- Gestión centralizada de clientes, pedidos, materiales e inventario.
- Órdenes de trabajo con costos, adelantos, saldos, prioridad y fechas.
- Flujo de producción: evaluación, corte, costura, tapizado y acabado.
- Asignación de responsables y actualización de cada etapa.
- Seguimiento público mediante código único de pedido.
- Historial de estados visible para el cliente.
- Panel con indicadores, alertas de stock y trabajos en curso.
- Reportes por periodo imprimibles o exportables a PDF desde el navegador.
- Asistente virtual local para consultas frecuentes y recomendaciones.
- Estudio de personalización con modelo 3D conceptual, carga de fotografía y edición visual mediante IA.

## Inicio rápido

Requisitos: PHP 8.2+, extensiones SQLite, OpenSSL y Mbstring, y Composer.

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Abra `http://127.0.0.1:8000`.

La instalación actual ya contiene `.env`, clave, base SQLite y datos de demostración. Normalmente basta con ejecutar:

```bash
php artisan serve
```

## Accesos de demostración

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@aya.bo | Admin123! |
| Trabajador | trabajador@aya.bo | Trabajador123! |
| Cliente | cliente@aya.bo | Cliente123! |

Código público de seguimiento: `AYA-2608-K7P2A`.

## Activar la generación visual con IA

El modelo 3D conceptual funciona sin servicios externos. Para generar una propuesta fotorrealista a partir de la fotografía del cliente, cree un token de Cloudflare Workers AI con permisos `Workers AI - Read` y `Workers AI - Edit`, y configure `.env`:

```env
CLOUDFLARE_ACCOUNT_ID=coloque_aqui_su_account_id
CLOUDFLARE_API_TOKEN=coloque_aqui_su_token
CLOUDFLARE_AI_MODEL=@cf/black-forest-labs/flux-2-klein-4b
CLOUDFLARE_AI_WIDTH=1024
CLOUDFLARE_AI_HEIGHT=1024
```

Después ejecute:

```bash
php artisan config:clear
```

El token debe configurarse únicamente en el servidor; nunca debe incluirse en JavaScript ni guardarse en el repositorio. La fotografía se reduce a un máximo de 512x512 antes de enviarse al modelo FLUX.2 y la propuesta resultante se guarda en el disco público.

## Activar correos con Resend

El sistema envía un correo al cliente cuando se registra un pedido y cada vez que cambia su estado. El correo incluye código, trabajo, servicio, estado, fecha estimada, total, saldo y un enlace al seguimiento público. Un error de Resend no impide guardar el pedido o su nuevo estado.

En [Resend](https://resend.com/) agregue y verifique un dominio, cree una API key con permiso exclusivo de envío y configure `.env`:

```env
MAIL_MAILER=resend
MAIL_FROM_ADDRESS="pedidos@su-dominio.com"
MAIL_FROM_NAME="A&A Multiservicios"
RESEND_API_KEY=re_coloque_aqui_su_clave
RESEND_NOTIFICATIONS_ENABLED=true
```

El dominio de `MAIL_FROM_ADDRESS` debe ser el mismo dominio verificado en Resend. Para desactivar temporalmente los avisos sin eliminar la clave use `RESEND_NOTIFICATIONS_ENABLED=false`. Después de cualquier cambio ejecute:

```bash
php artisan config:clear
```

La clave permanece únicamente en el servidor. La plantilla visual se encuentra en `resources/views/emails/orders/notification.blade.php` y el envío está centralizado en `app/Services/OrderEmailService.php`.

## Uso con XAMPP y MySQL

La aplicación usa SQLite por defecto para funcionar inmediatamente. Para MySQL:

1. Cree una base llamada `aya_multiservicios` desde phpMyAdmin.
2. Cambie en `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aya_multiservicios
DB_USERNAME=root
DB_PASSWORD=
```

3. Ejecute `php artisan migrate:fresh --seed`.
4. En producción, configure el DocumentRoot del servidor hacia la carpeta `public` del proyecto.

## Pruebas

```bash
php artisan test
```

La suite verifica páginas públicas, seguimiento, autenticación, permisos, paneles administrativos, creación de pedidos, etapas de producción y asistente virtual.

Para ejecutar la automatización con reportes JUnit y Markdown en Windows:

```powershell
composer test:report
```

La configuración, matriz de casos y análisis se documentan en `docs/PRUEBAS_AUTOMATIZADAS.md`.

## Estructura principal

- `app/Http/Controllers`: lógica de los módulos.
- `app/Models`: entidades y relaciones.
- `database/migrations`: modelo relacional normalizado.
- `database/seeders`: datos demostrativos.
- `resources/views`: interfaces Blade.
- `public/css` y `public/js`: diseño e interacción sin compilación adicional.
- `tests/Feature/SystemTest.php`: pruebas funcionales del sistema.

## Nota sobre inteligencia artificial

La primera versión implementa un asistente inteligente basado en reglas y conocimiento del negocio, sin depender de una API ni generar costos. La arquitectura permite reemplazarlo posteriormente por un modelo externo conservando la misma interfaz.

## Publicación gratuita

El repositorio incluye `Dockerfile` y `render.yaml` para desplegar Laravel en Render. La arquitectura recomendada para la demostración académica utiliza:

- Render Free para ejecutar la aplicación.
- TiDB Cloud Starter en modo compatible con MySQL para conservar la base de datos.
- Cloudflare R2 para conservar fotografías y propuestas visuales entre reinicios.
- Variables protegidas en el panel de Render; el archivo `.env` nunca se publica.

[Desplegar en Render](https://render.com/deploy?repo=https://github.com/Carlos22084/ApisJuanCarlosZelada)

El procedimiento completo, las variables requeridas y el material para la defensa se encuentran en [`docs/PUBLICACION_HOSTING_GRATUITO.md`](docs/PUBLICACION_HOSTING_GRATUITO.md).
