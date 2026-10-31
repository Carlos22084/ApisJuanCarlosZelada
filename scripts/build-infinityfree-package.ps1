[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

$projectRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$deployRoot = [System.IO.Path]::GetFullPath((Join-Path $projectRoot 'storage\deploy\infinityfree'))
$htdocsRoot = [System.IO.Path]::GetFullPath((Join-Path $deployRoot 'htdocs'))

if (-not $deployRoot.StartsWith($projectRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw 'La carpeta de despliegue debe permanecer dentro del proyecto.'
}

if (Test-Path -LiteralPath $deployRoot) {
    Remove-Item -LiteralPath $deployRoot -Recurse -Force
}

New-Item -ItemType Directory -Path $htdocsRoot -Force | Out-Null

$directories = @(
    'app',
    'bootstrap',
    'config',
    'database',
    'public',
    'resources',
    'routes',
    'vendor'
)

foreach ($directory in $directories) {
    $source = Join-Path $projectRoot $directory
    if (-not (Test-Path -LiteralPath $source)) {
        throw "Falta la carpeta requerida: $directory"
    }

    Copy-Item -LiteralPath $source -Destination $htdocsRoot -Recurse -Force
}

$storageDirectories = @(
    'storage\app\private',
    'storage\app\public',
    'storage\framework\cache\data',
    'storage\framework\sessions',
    'storage\framework\testing',
    'storage\framework\views',
    'storage\logs'
)

foreach ($storageDirectory in $storageDirectories) {
    New-Item -ItemType Directory -Path (Join-Path $htdocsRoot $storageDirectory) -Force | Out-Null
}

$publicStorageSource = Join-Path $projectRoot 'storage\app\public'
if (Test-Path -LiteralPath $publicStorageSource) {
    Copy-Item -Path (Join-Path $publicStorageSource '*') -Destination (Join-Path $htdocsRoot 'storage\app\public') -Recurse -Force
}

$files = @('artisan', 'composer.json', 'composer.lock')
foreach ($file in $files) {
    Copy-Item -LiteralPath (Join-Path $projectRoot $file) -Destination $htdocsRoot -Force
}

Copy-Item -LiteralPath (Join-Path $projectRoot 'deploy\infinityfree\.htaccess') -Destination (Join-Path $htdocsRoot '.htaccess') -Force

$appKey = (& php -r "echo 'base64:'.base64_encode(random_bytes(32));").Trim()
$envTemplate = Get-Content -LiteralPath (Join-Path $projectRoot 'deploy\infinityfree\.env.example') -Raw
$envContents = $envTemplate.Replace('__GENERATED_APP_KEY__', $appKey)
Set-Content -LiteralPath (Join-Path $htdocsRoot '.env') -Value $envContents -Encoding UTF8

$cacheTargets = @(
    (Join-Path $htdocsRoot 'bootstrap\cache'),
    (Join-Path $htdocsRoot 'storage\framework\cache\data'),
    (Join-Path $htdocsRoot 'storage\framework\sessions'),
    (Join-Path $htdocsRoot 'storage\framework\views'),
    (Join-Path $htdocsRoot 'storage\logs')
)

foreach ($cacheTarget in $cacheTargets) {
    $resolvedTarget = [System.IO.Path]::GetFullPath($cacheTarget)
    if (-not $resolvedTarget.StartsWith($htdocsRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "Ruta de limpieza no permitida: $resolvedTarget"
    }

    if (Test-Path -LiteralPath $resolvedTarget) {
        Get-ChildItem -LiteralPath $resolvedTarget -Force | Where-Object { $_.Name -ne '.gitignore' } | Remove-Item -Recurse -Force
    } else {
        New-Item -ItemType Directory -Path $resolvedTarget -Force | Out-Null
    }
}

Push-Location $htdocsRoot
try {
    & composer config optimize-autoloader false
    if ($LASTEXITCODE -ne 0) {
        throw 'Composer no pudo desactivar el autoload optimizado en el paquete temporal.'
    }

    & composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts
    if ($LASTEXITCODE -ne 0) {
        throw 'Composer no pudo preparar las dependencias de producción.'
    }
} finally {
    Pop-Location
}

$zipPath = Join-Path $deployRoot 'aya-multiservicios-infinityfree.zip'
& tar.exe -a -c -f $zipPath -C $htdocsRoot .
if ($LASTEXITCODE -ne 0) {
    throw 'No se pudo crear el archivo ZIP.'
}

$zip = Get-Item -LiteralPath $zipPath
$fileCount = (Get-ChildItem -LiteralPath $htdocsRoot -Recurse -File -Force | Measure-Object).Count

Write-Output "Paquete creado: $($zip.FullName)"
Write-Output "Archivos incluidos: $fileCount"
Write-Output "Tamano ZIP: $([Math]::Round($zip.Length / 1MB, 2)) MB"
Write-Output 'Antes de subir, edita htdocs\.env con el subdominio y las credenciales MySQL de InfinityFree.'
