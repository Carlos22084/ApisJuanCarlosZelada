$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.Drawing

$projectRoot = 'C:\xampp\htdocs\Proyecto1'
$sourceMedia = Join-Path $projectRoot 'storage\document-work\tarea-diseno-source\word\media'
$outputPath = Join-Path $projectRoot 'docs\Informe_Publicacion_InfinityFree.docx'
$buildPath = Join-Path ([System.IO.Path]::GetTempPath()) ('aya-infinityfree-report-' + [guid]::NewGuid().ToString('N'))

$images = @(
    @{ Name='github.png'; Path=(Join-Path $projectRoot 'storage\document-work\repositorio-github.png'); Rect=$null; CropT=0; CropR=0; CropB=0; CropL=0 },
    @{ Name='account-created.png'; Path=(Join-Path $sourceMedia 'image4.png'); Rect=@(0,160,1137,405); CropT=0; CropR=0; CropB=0; CropL=0 },
    @{ Name='hosting-active.png'; Path=(Join-Path $sourceMedia 'image12.png'); Rect=@(0,40,780,601); CropT=0; CropR=0; CropB=0; CropL=0 },
    @{ Name='database-imported.png'; Path=(Join-Path $sourceMedia 'image9.png'); Rect=@(0,65,1365,75); CropT=0; CropR=0; CropB=0; CropL=0 },
    @{ Name='ftp-transfer.png'; Path=(Join-Path $sourceMedia 'image11.png'); Rect=@(0,205,1262,489); CropT=0; CropR=0; CropB=0; CropL=0 },
    @{ Name='public-site.png'; Path=(Join-Path $projectRoot 'storage\document-work\portada-publica.png'); Rect=$null; CropT=0; CropR=0; CropB=0; CropL=0 }
)

foreach ($item in $images) {
    if (-not (Test-Path -LiteralPath $item.Path)) { throw "Falta la evidencia: $($item.Path)" }
}

function Xml-Escape([string]$Text) {
    return [System.Security.SecurityElement]::Escape($Text)
}

function Run-Xml {
    param([string]$Text, [bool]$Bold=$false, [bool]$Italic=$false, [int]$Size=21, [string]$Color='2D2018', [string]$Font='Arial')
    $properties = '<w:rPr><w:rFonts w:ascii="' + $Font + '" w:hAnsi="' + $Font + '"/><w:sz w:val="' + $Size + '"/><w:szCs w:val="' + $Size + '"/><w:color w:val="' + $Color + '"/>'
    if ($Bold) { $properties += '<w:b/>' }
    if ($Italic) { $properties += '<w:i/>' }
    $properties += '</w:rPr>'
    return '<w:r>' + $properties + '<w:t xml:space="preserve">' + (Xml-Escape $Text) + '</w:t></w:r>'
}

function Paragraph-Xml {
    param([string]$Text, [string]$Alignment='both', [bool]$Bold=$false, [bool]$Italic=$false, [int]$Size=21, [string]$Color='2D2018', [int]$After=90, [bool]$FirstLine=$true, [bool]$KeepNext=$false)
    $properties = '<w:pPr><w:jc w:val="' + $Alignment + '"/><w:spacing w:after="' + $After + '" w:line="285" w:lineRule="auto"/>'
    if ($FirstLine) { $properties += '<w:ind w:firstLine="420"/>' }
    if ($KeepNext) { $properties += '<w:keepNext/>' }
    $properties += '</w:pPr>'
    return '<w:p>' + $properties + (Run-Xml $Text $Bold $Italic $Size $Color) + '</w:p>'
}

function Heading-Xml([string]$Text, [int]$Level=1) {
    $size = if ($Level -eq 1) { 28 } elseif ($Level -eq 2) { 24 } else { 21 }
    $color = if ($Level -eq 1) { '633A24' } else { '8B5A3C' }
    return Paragraph-Xml $Text 'left' $true $false $size $color 90 $false $true
}

function Bullet-Xml([string]$Text) {
    return '<w:p><w:pPr><w:ind w:left="500" w:hanging="250"/><w:spacing w:after="55" w:line="270" w:lineRule="auto"/></w:pPr>' + (Run-Xml ('• ' + $Text) $false $false 20) + '</w:p>'
}

function Number-Xml([int]$Number, [string]$Text) {
    return '<w:p><w:pPr><w:ind w:left="500" w:hanging="300"/><w:spacing w:after="55" w:line="270" w:lineRule="auto"/></w:pPr>' + (Run-Xml ($Number.ToString() + '. ' + $Text) $false $false 20) + '</w:p>'
}

function Page-BreakXml {
    return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>'
}

function Table-Xml {
    param([object[]]$Rows, [int[]]$Widths)
    $xml = '<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblLayout w:type="fixed"/><w:tblBorders><w:top w:val="single" w:sz="5" w:color="8B5A3C"/><w:left w:val="single" w:sz="5" w:color="8B5A3C"/><w:bottom w:val="single" w:sz="5" w:color="8B5A3C"/><w:right w:val="single" w:sz="5" w:color="8B5A3C"/><w:insideH w:val="single" w:sz="4" w:color="D8C8BC"/><w:insideV w:val="single" w:sz="4" w:color="D8C8BC"/></w:tblBorders></w:tblPr><w:tblGrid>'
    foreach ($width in $Widths) { $xml += '<w:gridCol w:w="' + $width + '"/>' }
    $xml += '</w:tblGrid>'
    for ($row=0; $row -lt $Rows.Count; $row++) {
        $xml += '<w:tr>'
        $cells = @($Rows[$row])
        for ($column=0; $column -lt $cells.Count; $column++) {
            $shade = if ($row -eq 0) { '<w:shd w:val="clear" w:fill="E9DDD4"/>' } else { '' }
            $xml += '<w:tc><w:tcPr><w:tcW w:w="' + $Widths[$column] + '" w:type="dxa"/>' + $shade + '<w:tcMar><w:top w:w="55" w:type="dxa"/><w:left w:w="70" w:type="dxa"/><w:bottom w:w="55" w:type="dxa"/><w:right w:w="70" w:type="dxa"/></w:tcMar></w:tcPr>'
            $xml += Paragraph-Xml ([string]$cells[$column]) 'left' ($row -eq 0) $false 17 '2D2018' 20 $false $false
            $xml += '</w:tc>'
        }
        $xml += '</w:tr>'
    }
    return $xml + '</w:tbl><w:p><w:pPr><w:spacing w:after="55"/></w:pPr></w:p>'
}

$script:imageId = 100
function Image-Xml {
    param([string]$RelationshipId, [hashtable]$ImageInfo, [string]$Description, [int64]$Width=6100000)
    $source = [System.Drawing.Image]::FromFile($ImageInfo.RenderPath)
    try {
        $visibleWidth = $source.Width * (1 - (($ImageInfo.CropL + $ImageInfo.CropR) / 100000.0))
        $visibleHeight = $source.Height * (1 - (($ImageInfo.CropT + $ImageInfo.CropB) / 100000.0))
    } finally { $source.Dispose() }
    $height = [int64]($Width * ($visibleHeight / $visibleWidth))
    $script:imageId++
    $safe = Xml-Escape $Description
    $crop = '<a:srcRect t="' + $ImageInfo.CropT + '" r="' + $ImageInfo.CropR + '" b="' + $ImageInfo.CropB + '" l="' + $ImageInfo.CropL + '"/>'
    return '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="70" w:after="45"/></w:pPr><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="' + $Width + '" cy="' + $height + '"/><wp:effectExtent l="0" t="0" r="0" b="0"/><wp:docPr id="' + $script:imageId + '" name="Evidencia" descr="' + $safe + '"/><wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="1" name="' + $ImageInfo.Name + '"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="' + $RelationshipId + '"/>' + $crop + '<a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' + $Width + '" cy="' + $height + '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>'
}

function Figure-Xml([int]$Number, [string]$Caption) {
    return Paragraph-Xml ('Figura ' + $Number + '. ' + $Caption + ' Fuente: elaboración propia, 31 de octubre de 2026.') 'center' $false $true 17 '66574E' 90 $false $false
}

New-Item -ItemType Directory -Path (Join-Path $buildPath '_rels') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $buildPath 'word\_rels') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $buildPath 'word\media') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $buildPath 'docProps') -Force | Out-Null

for ($index=0; $index -lt $images.Count; $index++) {
    $destination = Join-Path $buildPath ('word\media\' + $images[$index].Name)
    if ($null -eq $images[$index].Rect) {
        Copy-Item -LiteralPath $images[$index].Path -Destination $destination
    } else {
        $sourceBitmap = New-Object System.Drawing.Bitmap($images[$index].Path)
        try {
            $rect = $images[$index].Rect
            $cropRectangle = New-Object System.Drawing.Rectangle($rect[0], $rect[1], $rect[2], $rect[3])
            $croppedBitmap = New-Object System.Drawing.Bitmap($rect[2], $rect[3])
            try {
                $graphics = [System.Drawing.Graphics]::FromImage($croppedBitmap)
                try {
                    $graphics.DrawImage($sourceBitmap, (New-Object System.Drawing.Rectangle(0,0,$rect[2],$rect[3])), $cropRectangle, [System.Drawing.GraphicsUnit]::Pixel)
                } finally { $graphics.Dispose() }
                $croppedBitmap.Save($destination, [System.Drawing.Imaging.ImageFormat]::Png)
            } finally { $croppedBitmap.Dispose() }
        } finally { $sourceBitmap.Dispose() }
    }
    $images[$index].RenderPath = $destination
}

$body = ''
$body += Paragraph-Xml 'INFORME DE PUBLICACIÓN DEL PROYECTO DE TITULACIÓN' 'center' $true $false 34 '3F2418' 120 $false $true
$body += Paragraph-Xml 'Diseño y Programación Web III' 'center' $true $false 26 '8B5A3C' 180 $false $false
$body += Paragraph-Xml 'Sistema web para la gestión y personalización de pedidos' 'center' $true $false 27 '3F2418' 40 $false $false
$body += Paragraph-Xml 'A&A Multiservicios' 'center' $true $false 28 '633A24' 220 $false $false
$body += Paragraph-Xml 'Estudiante: Juan Carlos Zelada Morales' 'center' $false $false 22 '2D2018' 35 $false $false
$body += Paragraph-Xml 'Hosting gratuito: InfinityFree' 'center' $false $false 22 '2D2018' 35 $false $false
$body += Paragraph-Xml 'URL pública: https://ayamultiservicios.page.gd' 'center' $false $false 21 '2D2018' 35 $false $false
$body += Paragraph-Xml 'Repositorio: https://github.com/Carlos22084/ApisJuanCarlosZelada' 'center' $false $false 19 '2D2018' 220 $false $false
$body += Paragraph-Xml 'Cochabamba - Bolivia' 'center' $false $false 21 '2D2018' 35 $false $false
$body += Paragraph-Xml '31 de octubre de 2026' 'center' $false $false 21 '2D2018' 0 $false $false
$body += Page-BreakXml

$body += Heading-Xml '1. Datos generales y objetivo' 1
$body += Table-Xml @(
    @('Elemento','Descripción'),
    @('Asignatura','Diseño y Programación Web III'),
    @('Proyecto','Sistema web para la gestión y personalización de pedidos de A&A Multiservicios'),
    @('Aplicación pública','https://ayamultiservicios.page.gd'),
    @('Repositorio GitHub','https://github.com/Carlos22084/ApisJuanCarlosZelada'),
    @('Hosting','InfinityFree, plan gratuito con PHP, MySQL y subdominio'),
    @('Framework','Laravel 12 con Blade, Vite y MySQL')
) @(2100,7200)
$body += Paragraph-Xml 'El objetivo fue publicar en Internet una aplicación Laravel que funcionaba únicamente en localhost, sin comprar un dominio ni contratar inicialmente un hosting de pago. La publicación permite consultar el sistema desde otros dispositivos mediante una URL pública.'
$body += Heading-Xml '2. Arquitectura de la publicación' 1
$body += Table-Xml @(
    @('Componente','Servicio o tecnología','Función'),
    @('Aplicación web','InfinityFree','Ejecuta PHP y publica Laravel mediante el subdominio gratuito.'),
    @('Base de datos','MySQL de InfinityFree','Almacena usuarios, materiales, pedidos, etapas e historial.'),
    @('Código fuente','GitHub','Conserva el código y el historial sin incluir credenciales reales.'),
    @('Interfaz','Blade, CSS, JavaScript y Vite','Presenta la portada, formularios y paneles.'),
    @('Servicios opcionales','Workers AI y Resend','Generación visual y correos, sujetos a las restricciones del hosting.')
) @(1800,2600,4900)
$body += Image-Xml 'rId3' $images[0] 'Repositorio público de GitHub.'
$body += Figure-Xml 1 'Repositorio público utilizado para el control de versiones.'
$body += Page-BreakXml

$body += Heading-Xml '3. Procedimiento de despliegue' 1
$steps = @(
    'Se creó una cuenta gratuita en InfinityFree y se registró el subdominio ayamultiservicios.page.gd.',
    'Se creó una base de datos MySQL desde el panel del hosting.',
    'Se exportó la base local de XAMPP y se importó en phpMyAdmin.',
    'Se preparó un .env exclusivo para producción con APP_ENV=production y APP_DEBUG=false.',
    'Se configuraron sesiones y caché mediante archivos y la cola en modo síncrono.',
    'Se instalaron localmente las dependencias de producción y se retiraron paquetes no necesarios.',
    'Se añadió un .htaccess para redirigir /htdocs hacia la carpeta public de Laravel.',
    'Se comprobó que ninguno de los 6.567 archivos excediera los límites del hosting.',
    'Se importaron 164 consultas SQL correctamente.',
    'Se subieron los archivos descomprimidos mediante FileZilla a /htdocs.',
    'Se verificó la aplicación desde la URL pública.'
)
for ($i=0; $i -lt $steps.Count; $i++) { $body += Number-Xml ($i+1) $steps[$i] }
$body += Heading-Xml '3.1 Creación y activación del hosting' 2
$body += Paragraph-Xml 'La cuenta fue creada con el subdominio gratuito seleccionado. Las zonas que contenían correo, usuario o contraseña fueron recortadas en las evidencias.'
$body += Image-Xml 'rId4' $images[1] 'Confirmación de creación del hosting.' 5600000
$body += Figure-Xml 2 'Confirmación de creación de la cuenta y el subdominio.'
$body += Image-Xml 'rId5' $images[2] 'Cuenta activa y dominio configurado.' 5600000
$body += Figure-Xml 3 'Cuenta activa y dominio configurado en InfinityFree.'
$body += Page-BreakXml

$body += Heading-Xml '3.2 Importación de la base de datos' 2
$body += Paragraph-Xml 'Se utilizó el MySQL incluido en InfinityFree porque el plan gratuito restringe conexiones directas con bases externas. La exportación local fue importada desde phpMyAdmin y se comprobó la ejecución exitosa de 164 consultas.'
$body += Image-Xml 'rId6' $images[3] 'Importación correcta de la base de datos.' 6100000
$body += Figure-Xml 4 'Importación correcta: 164 consultas SQL ejecutadas.'
$body += Heading-Xml '3.3 Transferencia de archivos' 2
$body += Paragraph-Xml 'El proyecto se preparó sin dependencias de desarrollo ni archivos superiores a los límites del hosting. La carga se realizó por FTP de forma descomprimida porque el ZIP completo superaba el máximo permitido.'
$body += Image-Xml 'rId7' $images[4] 'Transferencia FTP hacia htdocs.' 6100000
$body += Figure-Xml 5 'Transferencia del proyecto hacia /htdocs mediante FileZilla; credenciales recortadas.'
$body += Page-BreakXml

$body += Heading-Xml '3.4 Verificación de la URL pública' 2
$body += Paragraph-Xml 'Después de completar la transferencia se abrió el subdominio desde un navegador. La portada, navegación y acceso al sistema se mostraron correctamente fuera de localhost.'
$body += Image-Xml 'rId8' $images[5] 'Portada pública del sistema.' 6100000
$body += Figure-Xml 6 'Portada publicada en https://ayamultiservicios.page.gd.'
$body += Heading-Xml '4. Problemas encontrados y soluciones aplicadas' 1
$body += Table-Xml @(
    @('Problema','Causa','Solución aplicada'),
    @('El sistema solo funcionaba en localhost.','XAMPP ejecutaba la aplicación únicamente en la computadora local.','Se creó un hosting y un subdominio públicos.'),
    @('TiDB no podía conectarse desde el hosting.','InfinityFree restringe bases de datos externas.','Se utilizó el MySQL incluido en InfinityFree.'),
    @('No existe terminal en el hosting.','El plan gratuito no ofrece SSH, Composer ni Artisan.','Las dependencias y la compilación se prepararon localmente.'),
    @('No se podía subir el ZIP completo.','InfinityFree limita los archivos individuales a 10 MB.','Se cargaron los archivos descomprimidos mediante FTP.'),
    @('El SDK S3 contenía archivos PHP grandes.','Algunos archivos superaban el límite de 1 MB para PHP.','Se retiró la dependencia S3/R2 de la versión publicada.'),
    @('Laravel espera que public sea la raíz web.','InfinityFree fija la raíz en /htdocs.','Se añadió un .htaccess que reescribe hacia /public.'),
    @('No existen workers ni cron.','El plan gratuito no admite procesos permanentes.','Se configuró QUEUE_CONNECTION=sync.'),
    @('Los paneles mostraban datos sensibles.','Las capturas incluían usuario, host o correo.','Se recortaron las zonas sensibles y se cambió la contraseña expuesta durante el proceso.')
) @(2700,3000,3600)
$body += Page-BreakXml

$body += Heading-Xml '5. Respuestas a las preguntas de la tarea' 1
$body += Heading-Xml '5.1 ¿Cuál es la diferencia entre ejecutar Laravel en localhost y ejecutarlo en un hosting?' 2
$body += Paragraph-Xml 'En localhost, PHP, MySQL y los archivos se ejecutan dentro de la computadora del desarrollador y normalmente solo están disponibles en ella. En un hosting, la aplicación se ejecuta en un servidor conectado a Internet, utiliza una URL pública, credenciales de producción, HTTPS y recursos sujetos a las reglas del proveedor.'
$body += Heading-Xml '5.2 ¿Por qué no se debe subir .env con credenciales reales a GitHub?' 2
$body += Paragraph-Xml 'El archivo .env contiene contraseñas de la base, tokens de APIs, datos de correo y APP_KEY. Si se publica, terceras personas podrían acceder a los servicios, modificar información o generar consumo no autorizado. Borrarlo después no garantiza seguridad porque puede permanecer en el historial de Git. Por ello se mantiene en .gitignore y solo se configura de forma privada en el servidor.'
$body += Heading-Xml '5.3 ¿Qué función cumple APP_KEY?' 2
$body += Paragraph-Xml 'APP_KEY es la clave criptográfica principal de Laravel. Se utiliza para cifrar cookies, sesiones y otros valores protegidos. Debe ser aleatoria, privada y estable. Si se cambia después de publicar, las sesiones existentes se invalidan y los datos cifrados con la clave anterior pueden dejar de descifrarse.'
$body += Heading-Xml '5.4 ¿Qué problemas pueden aparecer al desplegar una aplicación que funciona localmente?' 2
$body += Paragraph-Xml 'Pueden aparecer diferencias de versión de PHP, extensiones ausentes, rutas incompatibles, permisos de escritura, credenciales incorrectas, caché antigua, recursos Vite sin compilar, límites de tamaño, falta de terminal, bloqueo de APIs externas y ausencia de colas o tareas programadas. También es común usar localhost erróneamente como host de la base en producción.'
$body += Heading-Xml '5.5 ¿Qué limitaciones tiene el hosting gratuito seleccionado?' 2
$body += Paragraph-Xml 'InfinityFree no proporciona SSH, Composer, Artisan, cron ni trabajadores de colas. Impone límites de CPU, memoria, cantidad y tamaño de archivos; además puede restringir conexiones externas y la propagación inicial del subdominio puede tardar. Es adecuado para una demostración académica, pero no para una operación comercial crítica.'
$body += Heading-Xml '5.6 ¿Qué cambios son necesarios para migrar a un hosting de pago?' 2
$body += Paragraph-Xml 'Se debe contratar un plan compatible con la versión de PHP y las extensiones de Laravel, configurar dominio y DNS, migrar la base mediante respaldo, actualizar .env, ejecutar Composer y migraciones, habilitar almacenamiento persistente, configurar colas y tareas programadas, establecer backups, monitoreo y HTTPS, y probar antes de dirigir el tráfico al nuevo servidor.'
$body += Page-BreakXml

$body += Heading-Xml '6. Resultados y verificación' 1
$results = @(
    'URL pública disponible: https://ayamultiservicios.page.gd.',
    'Repositorio disponible: https://github.com/Carlos22084/ApisJuanCarlosZelada.',
    'Base MySQL creada e importada con 164 consultas ejecutadas.',
    'Proyecto cargado en /htdocs mediante FTP.',
    'Portada y navegación principal visibles desde Internet.',
    'Credenciales reales excluidas de GitHub y de las capturas del informe.',
    'Antes del despliegue se ejecutaron 45 pruebas automatizadas con 263 aserciones aprobadas.'
)
foreach ($item in $results) { $body += Bullet-Xml $item }
$body += Heading-Xml '7. Defensa breve del trabajo realizado' 1
$body += Paragraph-Xml 'El objetivo fue transformar un sistema Laravel disponible únicamente en localhost en una aplicación accesible desde Internet. Seleccioné InfinityFree porque proporciona PHP, MySQL, HTTPS y un subdominio gratuito. Como el servicio no ofrece terminal, preparé localmente Composer y Vite, exporté la base de XAMPP y la importé mediante phpMyAdmin. Adapté la estructura pública de Laravel con .htaccess y subí los archivos por FTP. Las credenciales se mantuvieron fuera de GitHub. La solución cumple la finalidad académica; para producción se recomienda un hosting de pago con SSH, backups, colas y recursos garantizados.'
$body += Heading-Xml '8. Conclusión' 1
$body += Paragraph-Xml 'La aplicación fue publicada correctamente y dejó de depender exclusivamente de la computadora del desarrollador. El procedimiento permitió obtener una URL pública y conservar el código en GitHub sin exponer secretos. Las restricciones del plan gratuito obligaron a simplificar algunos componentes, pero el resultado permite demostrar el acceso web, la base de datos y el proceso completo de despliegue solicitado en la tarea.'

$body += '<w:sectPr><w:footerReference w:type="default" r:id="rId2"/><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="850" w:header="420" w:footer="420" w:gutter="0"/></w:sectPr>'

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><w:body>' + $body + '</w:body></w:document>'
$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="21"/></w:rPr></w:rPrDefault></w:docDefaults><w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style></w:styles>'
$footerXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="17"/><w:color w:val="6F625A"/></w:rPr><w:t>Informe de publicación | A&amp;A Multiservicios | Página </w:t></w:r><w:fldSimple w:instr="PAGE"><w:r><w:rPr><w:sz w:val="17"/></w:rPr><w:t>1</w:t></w:r></w:fldSimple></w:p></w:ftr>'
$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/><Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>'
$rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>'
$documentRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/>'
for ($index=0; $index -lt $images.Count; $index++) { $documentRels += '<Relationship Id="rId' + ($index + 3) + '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/' + $images[$index].Name + '"/>' }
$documentRels += '</Relationships>'
$coreXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Informe de Publicación del Proyecto de Titulación</dc:title><dc:subject>Diseño y Programación Web III</dc:subject><dc:creator>Juan Carlos Zelada Morales</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">2026-10-31T00:00:00Z</dcterms:created></cp:coreProperties>'
$appXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Microsoft Office Word</Application></Properties>'

$utf8 = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText((Join-Path $buildPath '[Content_Types].xml'), $contentTypes, $utf8)
[System.IO.File]::WriteAllText((Join-Path $buildPath '_rels\.rels'), $rootRels, $utf8)
[System.IO.File]::WriteAllText((Join-Path $buildPath 'word\document.xml'), $documentXml, $utf8)
[System.IO.File]::WriteAllText((Join-Path $buildPath 'word\styles.xml'), $stylesXml, $utf8)
[System.IO.File]::WriteAllText((Join-Path $buildPath 'word\footer1.xml'), $footerXml, $utf8)
[System.IO.File]::WriteAllText((Join-Path $buildPath 'word\_rels\document.xml.rels'), $documentRels, $utf8)
[System.IO.File]::WriteAllText((Join-Path $buildPath 'docProps\core.xml'), $coreXml, $utf8)
[System.IO.File]::WriteAllText((Join-Path $buildPath 'docProps\app.xml'), $appXml, $utf8)

if (Test-Path -LiteralPath $outputPath) { Remove-Item -LiteralPath $outputPath -Force }
$output = [System.IO.File]::Open($outputPath, [System.IO.FileMode]::CreateNew)
$archive = New-Object System.IO.Compression.ZipArchive($output, [System.IO.Compression.ZipArchiveMode]::Create, $false)
try {
    foreach ($file in Get-ChildItem -LiteralPath $buildPath -Recurse -File) {
        $entryName = $file.FullName.Substring($buildPath.Length + 1).Replace('\','/')
        $entry = $archive.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
        $entryStream = $entry.Open()
        $input = [System.IO.File]::OpenRead($file.FullName)
        try { $input.CopyTo($entryStream) } finally { $input.Dispose(); $entryStream.Dispose() }
    }
} finally { $archive.Dispose(); $output.Dispose() }

$resolvedBuild = [System.IO.Path]::GetFullPath($buildPath)
$resolvedTemp = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
if ($resolvedBuild.StartsWith($resolvedTemp, [System.StringComparison]::OrdinalIgnoreCase)) { Remove-Item -LiteralPath $resolvedBuild -Recurse -Force }

Get-Item -LiteralPath $outputPath | Select-Object FullName, Length, LastWriteTime
