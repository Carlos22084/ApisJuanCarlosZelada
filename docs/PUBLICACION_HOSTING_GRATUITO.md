# Publicación gratuita del Proyecto de Titulación

## Datos de la entrega

- **Asignatura:** Diseño y Programación Web III.
- **Proyecto:** Sistema web para la gestión y personalización de pedidos de A&A Multiservicios.
- **URL pública:** <https://ayamultiservicios.page.gd>
- **Repositorio:** <https://github.com/Carlos22084/ApisJuanCarlosZelada>
- **Hosting seleccionado:** InfinityFree.
- **Fecha de publicación:** 31 de octubre de 2026.

## 1. Objetivo

Publicar en Internet el sistema Laravel que anteriormente funcionaba únicamente en `localhost`, utilizando un hosting gratuito y un subdominio sin costo. La publicación permite acceder a la portada y a los módulos del sistema desde otros dispositivos mediante una URL pública.

## 2. Arquitectura publicada

| Componente | Tecnología o servicio | Función |
|---|---|---|
| Aplicación web | InfinityFree | Ejecuta PHP y sirve públicamente el proyecto Laravel. |
| Base de datos | MySQL de InfinityFree | Almacena usuarios, pedidos, materiales, etapas e historial. |
| Framework | Laravel 12 | Implementa rutas, controladores, validaciones, autenticación y reglas del negocio. |
| Interfaz | Blade, CSS, JavaScript y Vite | Presenta la portada, formularios y paneles del sistema. |
| Código fuente | GitHub | Mantiene el control de versiones sin publicar credenciales. |
| Generación visual | Cloudflare Workers AI | Genera propuestas visuales cuando la conexión externa es admitida. |
| Notificaciones | Resend | Envía correos cuando la conexión externa y el remitente están disponibles. |

## 3. Procedimiento realizado

1. Se creó una cuenta gratuita en InfinityFree.
2. Se registró el subdominio `ayamultiservicios.page.gd`.
3. Se creó una base MySQL desde el panel de InfinityFree.
4. Se exportó la base local de XAMPP mediante `mysqldump`.
5. Se importó el archivo SQL en phpMyAdmin; se ejecutaron correctamente 164 consultas.
6. Se configuró un `.env` exclusivo para producción con `APP_ENV=production` y `APP_DEBUG=false`.
7. Se configuraron sesiones y caché mediante archivos y la cola en modo síncrono.
8. Se prepararon las dependencias de Composer sin paquetes de desarrollo.
9. Se eliminó el SDK S3/R2, que contenía archivos superiores al límite permitido por InfinityFree.
10. Se verificó que ninguno de los 6.567 archivos preparados excediera los límites del hosting.
11. Se añadió un `.htaccess` que dirige las solicitudes hacia la carpeta `public` de Laravel.
12. Se subieron los archivos descomprimidos mediante FileZilla a `/htdocs`.
13. Se comprobó la portada desde la URL pública.

## 4. Problemas encontrados y soluciones

| Problema | Causa | Solución |
|---|---|---|
| La aplicación solamente funcionaba en la computadora local. | XAMPP utiliza `localhost`. | Se publicó el proyecto en InfinityFree y se asignó un subdominio público. |
| TiDB Cloud no podía usarse desde InfinityFree. | El hosting gratuito restringe conexiones a bases externas. | Se utilizó el MySQL incluido en InfinityFree. |
| No existe terminal, Composer ni Artisan en el servidor. | El plan gratuito no proporciona SSH. | Las dependencias y recursos se prepararon localmente y la base se importó mediante phpMyAdmin. |
| El ZIP superaba el límite de 10 MB. | InfinityFree elimina archivos individuales demasiado grandes. | Se subió la carpeta descomprimida mediante FileZilla. |
| Algunos archivos del SDK de Amazon superaban 1 MB. | El SDK S3 contiene archivos PHP grandes. | Se retiró la dependencia S3/R2 porque no era necesaria para la demostración. |
| Laravel espera que `public` sea la raíz web. | InfinityFree fija la raíz en `/htdocs`. | Se agregó un `.htaccess` que reescribe las solicitudes hacia `/public`. |
| No es posible crear el enlace simbólico de almacenamiento. | El hosting no admite `storage:link`. | Se agregó una regla para servir archivos desde `storage/app/public`. |
| Las tareas en segundo plano no están disponibles. | No existen workers ni cron en el plan gratuito. | Se configuró `QUEUE_CONNECTION=sync`. |

## 5. Respuestas solicitadas

### ¿Cuál es la diferencia entre ejecutar Laravel en `localhost` y ejecutarlo en un hosting?

En `localhost`, PHP, la base de datos y los archivos se ejecutan en la computadora del desarrollador y normalmente no son accesibles desde otros dispositivos. En un hosting, Laravel se ejecuta en un servidor conectado a Internet, utiliza una URL pública, credenciales de producción, HTTPS y una base alojada en el servidor.

### ¿Por qué no se debe subir `.env` con credenciales reales a GitHub?

El archivo `.env` contiene contraseñas, tokens de APIs, claves de correo, datos de la base y `APP_KEY`. Si se publica, otra persona podría acceder a los servicios o modificar información. Aunque posteriormente se elimine, las credenciales pueden permanecer en el historial de Git. Por ello, `.env` está excluido mediante `.gitignore`.

### ¿Qué función cumple `APP_KEY`?

Es la clave criptográfica principal de Laravel. Se utiliza para cifrar cookies, sesiones y otros valores protegidos. Debe ser aleatoria, privada y estable; cambiarla invalida sesiones y puede impedir descifrar datos anteriores.

### ¿Qué problemas pueden aparecer al desplegar una aplicación que funciona localmente?

Pueden existir diferencias de versión de PHP, extensiones ausentes, permisos incorrectos, rutas incompatibles, credenciales diferentes, archivos demasiado grandes, caché antigua, límites de ejecución, restricciones de APIs y ausencia de terminal, colas o tareas programadas.

### ¿Qué limitaciones tiene InfinityFree?

El plan gratuito no proporciona SSH, Composer, Artisan, trabajadores de colas ni tareas programadas. También limita el tamaño de los archivos, recursos de CPU, memoria y cantidad de archivos. La propagación inicial del dominio puede tardar y algunas conexiones externas pueden estar restringidas. Por estas razones se utiliza para una demostración académica y no para producción comercial.

### ¿Qué cambios son necesarios para migrar a un hosting de pago?

Se debe contratar un plan compatible con Laravel, configurar el dominio y DNS, trasladar la base de datos mediante respaldo, actualizar `.env`, ejecutar migraciones, instalar dependencias en el servidor, configurar almacenamiento persistente, habilitar colas y tareas programadas, activar copias de seguridad, monitoreo y HTTPS, y realizar pruebas antes de cambiar el tráfico.

## 6. Evidencias recomendadas

Las capturas deben ocultar contraseñas, tokens y claves:

1. Repositorio de GitHub.
2. Cuenta de hosting y subdominio creado.
3. Base MySQL creada en InfinityFree.
4. Mensaje de phpMyAdmin: 164 consultas importadas correctamente.
5. Conexión FTP y transferencia terminada en FileZilla.
6. Portada abierta desde `https://ayamultiservicios.page.gd`.
7. Inicio de sesión y panel de uno de los roles.
8. Resultado previo de pruebas: 45 pruebas y 263 aserciones aprobadas.

## 7. Defensa breve

“El objetivo del trabajo fue convertir un sistema Laravel disponible únicamente en localhost en una aplicación accesible desde Internet. Seleccioné InfinityFree porque proporciona PHP, MySQL, HTTPS y un subdominio gratuito. Preparé localmente las dependencias y los recursos porque el hosting no ofrece terminal; exporté la base de XAMPP y la importé mediante phpMyAdmin. También adapté la estructura pública de Laravel mediante `.htaccess` y subí los archivos por FTP. Las credenciales permanecieron fuera de GitHub. La principal limitación es que el plan gratuito restringe procesos, archivos y conexiones externas, pero permite demostrar la publicación y el acceso desde otros dispositivos.”

## 8. Conclusión

El sistema quedó publicado correctamente en `https://ayamultiservicios.page.gd`. La aplicación dejó de depender exclusivamente de `localhost` y puede abrirse desde Internet. El despliegue cumple el objetivo académico, aunque un uso comercial requeriría un hosting con recursos garantizados, backups, soporte completo para Laravel y servicios en segundo plano.
