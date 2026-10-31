# Publicación del sistema en InfinityFree

## Objetivo

Publicar una versión demostrativa del sistema Laravel A&A Multiservicios en InfinityFree. Debido a las restricciones del hosting gratuito, el objetivo principal es disponer de una URL pública para la presentación académica.

## Limitaciones aceptadas

- InfinityFree no permite ejecutar Composer, Artisan o npm en el servidor.
- Las migraciones y los datos se importan manualmente mediante phpMyAdmin.
- No existen trabajadores de colas ni tareas programadas; se utiliza `QUEUE_CONNECTION=sync`.
- Se utilizan sesiones y caché basadas en archivos.
- TiDB Cloud no se utiliza porque InfinityFree bloquea bases de datos externas. Se utiliza el MySQL incluido en el hosting.
- La generación de imágenes y el envío de notificaciones pueden no funcionar por los límites de ejecución y conexiones externas.

## Preparación local

Ejecutar desde PowerShell:

```powershell
cd C:\xampp\htdocs\Proyecto1
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\build-infinityfree-package.ps1
```

El paquete se genera en:

```text
storage\deploy\infinityfree\aya-multiservicios-infinityfree.zip
```

También se genera una carpeta `htdocs` descomprimida para poder editar `.env` antes de subirla.

## Configuración requerida

1. Crear una cuenta y un subdominio gratuito en InfinityFree.
2. Crear una base de datos desde `MySQL Databases`.
3. Copiar el host, nombre, usuario y contraseña proporcionados por InfinityFree.
4. Editar `storage\deploy\infinityfree\htdocs\.env` y reemplazar:
   - `APP_URL`.
   - `DB_HOST`.
   - `DB_DATABASE`.
   - `DB_USERNAME`.
   - `DB_PASSWORD`.
5. Exportar la base de datos local desde phpMyAdmin en formato SQL.
6. Importar el archivo SQL en el phpMyAdmin de InfinityFree.
7. Subir el contenido de la carpeta `htdocs` local a la carpeta `htdocs` del hosting mediante FTP.

## Verificación

- Abrir el subdominio público mediante HTTPS.
- Comprobar la portada principal.
- Iniciar sesión con un usuario importado.
- Navegar por el panel correspondiente.
- Tomar capturas del panel de InfinityFree, phpMyAdmin, FTP y la URL pública.

## Seguridad

El archivo `.env` contiene credenciales y no debe publicarse en GitHub. Las capturas deben ocultar contraseñas, tokens, claves y datos de conexión.
