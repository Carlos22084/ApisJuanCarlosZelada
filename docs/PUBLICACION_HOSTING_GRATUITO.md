# Publicación gratuita del Proyecto de Titulación

## Datos de la entrega

- **Asignatura:** Diseño y Programación Web III.
- **Proyecto:** Sistema web inteligente para la gestión y personalización de pedidos de A&A Multiservicios.
- **Repositorio:** <https://github.com/Carlos22084/ApisJuanCarlosZelada>
- **Aplicación pública:** pendiente de completar después de crear el servicio en Render. El nombre propuesto es `https://aya-multiservicios.onrender.com`, sujeto a disponibilidad.
- **Fecha de preparación:** 30 de octubre de 2026.

> El informe Word se genera en `docs/Informe_Publicacion_Proyecto_Titulacion.docx`. Después del despliegue se debe reemplazar la URL pendiente e insertar las capturas indicadas en la sección de evidencias.

## 1. Objetivo

Publicar en Internet la aplicación Laravel que anteriormente funcionaba únicamente en `localhost`, usando servicios con modalidad gratuita y sin comprar inicialmente un dominio. La publicación permite que administrador, trabajadores y clientes accedan desde computadoras o teléfonos mediante HTTPS.

## 2. Arquitectura seleccionada

| Componente | Servicio | Función |
|---|---|---|
| Aplicación | Render Free, contenedor Docker | Ejecuta PHP 8.2, Laravel 12 y los recursos compilados con Vite. |
| Base de datos | TiDB Cloud Starter, modo MySQL | Conserva usuarios, pedidos, materiales, etapas e historial. |
| Archivos | Cloudflare R2 | Conserva fotografías originales y resultados generados aunque Render reinicie. |
| Imágenes | Cloudflare Workers AI | Genera la propuesta visual del nuevo tapizado. |
| Correos | Resend | Envía avisos de pedidos y cambios de estado. |
| Código | GitHub | Mantiene el control de versiones y permite el despliegue automático. |

Se eligió esta separación porque el disco de una instancia gratuita de Render es efímero. La aplicación puede reiniciarse o suspenderse sin perder los datos alojados externamente.

## 3. Preparación realizada

1. Se verificó que `.env` esté incluido en `.gitignore` y que no sea un archivo rastreado por Git.
2. Se creó un `Dockerfile` reproducible con PHP 8.2, extensiones de MySQL, procesamiento de imágenes, Composer, Node.js y compilación Vite.
3. Se creó `render.yaml` con la definición del servicio y variables sensibles marcadas como `sync: false`.
4. Se configuró `DESIGNS_DISK` para usar el disco `public` en XAMPP y S3/R2 en producción.
5. Se agregó el adaptador `league/flysystem-aws-s3-v3` para conectar Laravel con R2.
6. Se agregaron rutas protegidas para mostrar fotografías desde almacenamiento local o remoto.
7. Se creó el comando `app:seed-demo-if-empty`, que carga datos demostrativos solamente cuando la base está vacía.
8. Se actualizaron las dependencias a Laravel 12.70.2 y versiones corregidas de Symfony. `composer audit` terminó sin alertas.
9. Se compiló el visor 3D con Vite.
10. Se ejecutaron 45 pruebas automatizadas con 263 aserciones aprobadas.

## 4. Procedimiento para completar el despliegue

### 4.1 TiDB Cloud

1. Crear una cuenta gratuita en TiDB Cloud.
2. Crear una instancia **Starter** con compatibilidad **MySQL**.
3. Crear la base `aya_multiservicios`.
4. Copiar de forma privada host, puerto, nombre de base, usuario y contraseña.
5. Mantener el límite de gasto en cero para trabajar solamente con la cuota gratuita.

### 4.2 Cloudflare R2

1. En el panel de Cloudflare abrir **R2 Object Storage**.
2. Crear un bucket privado llamado, por ejemplo, `aya-multiservicios`.
3. Crear credenciales S3 limitadas a ese bucket.
4. Guardar `Access Key ID`, `Secret Access Key`, nombre del bucket y endpoint S3.
5. No mostrar los valores completos en capturas ni documentos.

### 4.3 Render

1. Abrir <https://render.com/deploy?repo=https://github.com/Carlos22084/ApisJuanCarlosZelada>.
2. Iniciar sesión con GitHub y autorizar el repositorio.
3. Crear el Blueprint gratuito definido en `render.yaml`.
4. Generar `APP_KEY` localmente con `php artisan key:generate --show` y copiar el resultado al panel de Render.
5. Completar las variables marcadas como secretas.
6. Iniciar el despliegue y revisar los logs hasta observar migraciones correctas y el servidor escuchando en el puerto de Render.
7. Copiar la URL `https://...onrender.com` y asignarla a `APP_URL`.
8. Volver a desplegar para regenerar la configuración con la URL definitiva.

### 4.4 Variables que deben configurarse en Render

| Variable | Valor o procedencia |
|---|---|
| `APP_KEY` | Resultado privado de `php artisan key:generate --show`. |
| `APP_URL` | URL pública asignada por Render. |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Credenciales privadas de TiDB. |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT` | Credenciales privadas de Cloudflare R2. |
| `CLOUDFLARE_ACCOUNT_ID`, `CLOUDFLARE_API_TOKEN` | Credenciales privadas de Workers AI. |
| `RESEND_API_KEY`, `MAIL_FROM_ADDRESS` | Credenciales y remitente verificado de Resend. |

## 5. Verificación funcional

Después de publicar se debe comprobar:

- La portada abre mediante HTTPS desde otro dispositivo.
- El administrador, trabajador y cliente pueden iniciar sesión.
- Los permisos impiden acceder a módulos de otros roles.
- Se registra un pedido y se crean sus cinco etapas.
- El administrador asigna el trabajo a un trabajador.
- El porcentaje cambia al completar etapas.
- El seguimiento público encuentra el código del pedido.
- La imagen se carga, se genera y permanece disponible después de un redeploy.
- La notificación de finalización llega al correo del cliente.
- Los logs de Render no contienen errores ni credenciales visibles.

## 6. Problemas encontrados y soluciones

| Problema | Causa | Solución aplicada |
|---|---|---|
| La aplicación solo era accesible en `localhost`. | El servidor de XAMPP escuchaba únicamente en la computadora local. | Se preparó un contenedor accesible públicamente en Render. |
| Las imágenes podían perderse. | Render Free utiliza almacenamiento local efímero. | Se incorporó almacenamiento S3 compatible mediante Cloudflare R2. |
| Las credenciales no pueden publicarse. | `.env` contiene claves de base de datos y APIs. | `.env` permanece ignorado y Render solicita secretos fuera del repositorio. |
| La base local era MySQL de XAMPP. | `127.0.0.1` dentro del hosting no apunta a la computadora del desarrollador. | Se usa TiDB Cloud mediante conexión MySQL cifrada. |
| El seeder podía duplicar usuarios al reiniciar. | `DatabaseSeeder` crea registros directamente. | Se agregó un comando que solo ejecuta el seeder si la tabla de usuarios está vacía. |
| Se encontraron alertas de seguridad en dependencias. | El archivo de bloqueo contenía Laravel y Symfony anteriores. | Se actualizó Laravel 12.70.2; `composer audit` quedó sin alertas. |
| El primer acceso puede ser lento. | Render suspende la instancia gratuita tras un periodo sin tráfico. | Se documentó la espera de reactivación como limitación del plan. |

## 7. Respuestas solicitadas

### ¿Cuál es la diferencia entre ejecutar Laravel en localhost y ejecutarlo en un hosting?

En `localhost`, PHP, MySQL y los archivos funcionan dentro de la computadora del programador y normalmente solo son accesibles desde ella. En un hosting, la aplicación se ejecuta en un servidor conectado a Internet, utiliza una URL pública, HTTPS, variables de producción, una base remota y medidas adicionales de seguridad, disponibilidad y monitoreo.

### ¿Por qué no se debe subir `.env` con credenciales reales a GitHub?

Porque contiene contraseñas, tokens de APIs, datos de correo y claves de la aplicación. Si se publica, otra persona podría usar los servicios, leer o modificar información y generar gastos. Aunque después se borre, el secreto puede permanecer en el historial de Git. Las credenciales deben almacenarse en el panel privado del hosting y rotarse si se filtran.

### ¿Qué función cumple `APP_KEY`?

Es la clave criptográfica principal de Laravel. Se utiliza para cifrar cookies, sesiones y otros valores protegidos. Debe ser aleatoria, privada y estable. Si se cambia en un sistema ya publicado, los datos cifrados anteriormente pueden dejar de descifrarse y las sesiones activas se invalidan.

### ¿Qué problemas pueden aparecer al desplegar una aplicación que funciona localmente?

Pueden faltar extensiones de PHP, variar las versiones, existir permisos incorrectos, no compilarse Vite, fallar la conexión remota o SSL de la base, faltar `APP_KEY`, utilizarse rutas de Windows, perderse archivos en discos temporales, excederse tiempos de ejecución, quedar caché antigua o bloquearse correos y APIs por configuración de red.

### ¿Qué limitaciones tiene el hosting gratuito seleccionado?

Render Free suspende la aplicación cuando no recibe tráfico, por lo que el primer acceso puede tardar; ofrece recursos reducidos, no proporciona disco persistente gratuito, puede reiniciar la instancia y no está recomendado para producción comercial. TiDB y R2 también poseen cuotas gratuitas. Al superarlas, el servicio puede limitarse o requerir un plan de pago.

### ¿Qué cambios son necesarios para migrar a un hosting de pago?

Se debe seleccionar un plan con recursos garantizados, configurar dominio y DNS, mantener HTTPS, trasladar la base de datos con respaldo, cambiar las variables de conexión, usar almacenamiento persistente u objetos, ejecutar migraciones, habilitar backups, colas y tareas programadas, configurar monitoreo y probar antes de cambiar el tráfico. El código Docker actual puede reutilizarse casi sin modificaciones.

## 8. Evidencias que debe contener la versión final

Insertar capturas con los secretos ocultos:

1. Repositorio GitHub mostrando `Dockerfile`, `render.yaml` y el último commit.
2. Instancia MySQL de TiDB en estado disponible, ocultando host, usuario y contraseña.
3. Bucket R2 y credenciales parcialmente ocultas.
4. Variables de Render mostrando solo los nombres y valores sensibles ocultos.
5. Log de build exitoso.
6. Servicio Render con estado **Live** y URL pública.
7. Portada abierta desde la URL pública.
8. Inicio de sesión y panel de administrador o trabajador.
9. Resultado de las pruebas: `45 passed (263 assertions)`.

## 9. Defensa breve sugerida

“El objetivo fue convertir un sistema Laravel que funcionaba únicamente en localhost en una aplicación accesible desde Internet. Organicé el despliegue en varios servicios: GitHub conserva el código, Render ejecuta un contenedor Docker, TiDB guarda los datos mediante protocolo MySQL y Cloudflare R2 conserva las imágenes. Las credenciales no se publicaron; se configuraron como variables privadas del hosting. También actualicé dependencias vulnerables, compilé los recursos y ejecuté 45 pruebas con 263 aserciones. La principal limitación es que Render Free entra en reposo y tiene recursos reducidos, pero resulta adecuado para una demostración académica. Para producción se migraría a un plan con disponibilidad, backups, dominio y monitoreo.”

## 10. Conclusión

El sistema quedó técnicamente preparado para un despliegue reproducible, seguro y verificable. La separación entre aplicación, base de datos y almacenamiento evita depender de la computadora local y reduce la pérdida de información durante reinicios del hosting gratuito. La publicación final requiere vincular las cuentas personales de Render, TiDB y Cloudflare, ingresar los secretos en sus paneles y completar la URL y capturas del informe.
