param(
    [string] $Filter = ''
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$resultsDirectory = Join-Path $projectRoot 'storage\test-results'
$junitPath = Join-Path $resultsDirectory 'junit.xml'
$summaryPath = Join-Path $resultsDirectory 'summary.md'

Push-Location $projectRoot

try {
    if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
        throw 'PHP no esta disponible en PATH.'
    }

    if (-not (Test-Path -LiteralPath 'vendor\autoload.php')) {
        throw 'Faltan dependencias. Ejecute composer install antes de las pruebas.'
    }

    New-Item -ItemType Directory -Path $resultsDirectory -Force | Out-Null

    $arguments = @('artisan', 'test', '--log-junit', $junitPath)
    if ($Filter -ne '') {
        $arguments += "--filter=$Filter"
    }

    $startedAt = Get-Date
    & php @arguments
    $testExitCode = $LASTEXITCODE
    $finishedAt = Get-Date

    if (-not (Test-Path -LiteralPath $junitPath)) {
        throw 'PHPUnit no genero el archivo JUnit esperado.'
    }

    [xml] $report = Get-Content -LiteralPath $junitPath -Raw
    $aggregateSuite = $report.testsuites.testsuite | Select-Object -First 1
    if ($null -eq $aggregateSuite) {
        throw 'El reporte JUnit no contiene suites de prueba.'
    }

    $tests = [int] $aggregateSuite.tests
    $assertions = [int] $aggregateSuite.assertions
    $failures = [int] $aggregateSuite.failures
    $errors = [int] $aggregateSuite.errors
    $skipped = [int] $aggregateSuite.skipped
    $status = if ($testExitCode -eq 0) { 'APROBADO' } else { 'FALLIDO' }
    $elapsed = [Math]::Round(($finishedAt - $startedAt).TotalSeconds, 2)

    $summary = @(
        '# Resultado de pruebas automatizadas',
        '',
        "- Estado: **$status**",
        "- Fecha: $($finishedAt.ToString('yyyy-MM-dd HH:mm:ss'))",
        "- Casos ejecutados: $tests",
        "- Aserciones: $assertions",
        "- Fallos: $failures",
        "- Errores: $errors",
        "- Omitidos: $skipped",
        "- Duracion medida por el script: $elapsed segundos",
        '- Entorno: PHPUnit con SQLite en memoria',
        '',
        'El detalle por suite y caso esta disponible en `junit.xml`.'
    ) -join [Environment]::NewLine

    Set-Content -LiteralPath $summaryPath -Value $summary -Encoding UTF8
    Write-Host "`nResumen: $summaryPath"
    Write-Host "JUnit:  $junitPath"

    exit $testExitCode
}
finally {
    Pop-Location
}
