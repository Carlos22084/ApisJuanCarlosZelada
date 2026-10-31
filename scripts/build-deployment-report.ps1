$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$root = if ($PSScriptRoot) { Split-Path -Parent $PSScriptRoot } else { (Get-Location).Path }
$sourcePath = Join-Path $root 'docs\PUBLICACION_HOSTING_GRATUITO.md'
$outputPath = Join-Path $root 'docs\Informe_Publicacion_Proyecto_Titulacion.docx'
$tempRoot = [System.IO.Path]::GetTempPath()
$buildPath = Join-Path $tempRoot ('aya-publicacion-' + [guid]::NewGuid().ToString('N'))
$utf8 = New-Object System.Text.UTF8Encoding($false)

function Xml-Escape([string]$Text) {
    return [System.Security.SecurityElement]::Escape($Text)
}

function Plain-Text([string]$Text) {
    $value = $Text.Trim()
    $value = [regex]::Replace($value, '\[([^\]]+)\]\(([^\)]+)\)', '$1 ($2)')
    $value = [regex]::Replace($value, '<(https?://[^>]+)>', '$1')
    $value = $value.Replace('**', '').Replace('`', '').Replace('“', '"').Replace('”', '"')
    return $value
}

function Run-Xml {
    param([string]$Text, [bool]$Bold = $false, [bool]$Italic = $false, [int]$Size = 20, [string]$Color = '28201C')
    $properties = '<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="' + $Size + '"/><w:szCs w:val="' + $Size + '"/><w:color w:val="' + $Color + '"/>'
    if ($Bold) { $properties += '<w:b/>' }
    if ($Italic) { $properties += '<w:i/>' }
    $properties += '</w:rPr>'
    return '<w:r>' + $properties + '<w:t xml:space="preserve">' + (Xml-Escape $Text) + '</w:t></w:r>'
}

function Paragraph-Xml {
    param(
        [string]$Text,
        [string]$Alignment = 'left',
        [bool]$Bold = $false,
        [bool]$Italic = $false,
        [int]$Size = 20,
        [string]$Color = '28201C',
        [int]$After = 100,
        [int]$Left = 0,
        [bool]$KeepNext = $false
    )
    $properties = '<w:pPr><w:jc w:val="' + $Alignment + '"/><w:spacing w:after="' + $After + '" w:line="276" w:lineRule="auto"/>'
    if ($Left -gt 0) { $properties += '<w:ind w:left="' + $Left + '"/>' }
    if ($KeepNext) { $properties += '<w:keepNext/>' }
    $properties += '</w:pPr>'
    return '<w:p>' + $properties + (Run-Xml $Text $Bold $Italic $Size $Color) + '</w:p>'
}

function Heading-Xml([string]$Text, [int]$Level) {
    $size = if ($Level -eq 1) { 29 } elseif ($Level -eq 2) { 25 } else { 22 }
    $color = if ($Level -eq 1) { '633A24' } else { '825337' }
    return Paragraph-Xml (Plain-Text $Text) 'left' $true $false $size $color 110 0 $true
}

function Table-Xml([object[]]$Rows) {
    $xml = '<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblBorders><w:top w:val="single" w:sz="5" w:color="A98770"/><w:left w:val="single" w:sz="5" w:color="A98770"/><w:bottom w:val="single" w:sz="5" w:color="A98770"/><w:right w:val="single" w:sz="5" w:color="A98770"/><w:insideH w:val="single" w:sz="4" w:color="D6C4B8"/><w:insideV w:val="single" w:sz="4" w:color="D6C4B8"/></w:tblBorders></w:tblPr>'
    for ($rowIndex = 0; $rowIndex -lt $Rows.Count; $rowIndex++) {
        $xml += '<w:tr>'
        foreach ($cell in @($Rows[$rowIndex])) {
            $shade = if ($rowIndex -eq 0) { '<w:shd w:val="clear" w:fill="EEE3DC"/>' } else { '' }
            $xml += '<w:tc><w:tcPr>' + $shade + '<w:tcMar><w:top w:w="60" w:type="dxa"/><w:left w:w="70" w:type="dxa"/><w:bottom w:w="60" w:type="dxa"/><w:right w:w="70" w:type="dxa"/></w:tcMar></w:tcPr>'
            $xml += Paragraph-Xml (Plain-Text ([string]$cell)) 'left' ($rowIndex -eq 0) $false 16 '28201C' 20
            $xml += '</w:tc>'
        }
        $xml += '</w:tr>'
    }
    return $xml + '</w:tbl><w:p><w:pPr><w:spacing w:after="80"/></w:pPr></w:p>'
}

function Capture-Placeholder([string]$Label) {
    $labelXml = Paragraph-Xml $Label 'center' $true $false 18 '6B5B52' 45
    $noteXml = Paragraph-Xml 'Inserte aquí la captura final con los datos sensibles ocultos.' 'center' $false $true 17 '7A706A' 30
    return '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders><w:top w:val="dashed" w:sz="8" w:color="A98770"/><w:left w:val="dashed" w:sz="8" w:color="A98770"/><w:bottom w:val="dashed" w:sz="8" w:color="A98770"/><w:right w:val="dashed" w:sz="8" w:color="A98770"/></w:tblBorders></w:tblPr><w:tr><w:trPr><w:trHeight w:val="1500" w:hRule="atLeast"/></w:trPr><w:tc><w:tcPr><w:shd w:val="clear" w:fill="F8F4F1"/><w:vAlign w:val="center"/></w:tcPr>' + $labelXml + $noteXml + '</w:tc></w:tr></w:tbl><w:p><w:pPr><w:spacing w:after="120"/></w:pPr></w:p>'
}

New-Item -ItemType Directory -Path (Join-Path $buildPath '_rels') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $buildPath 'word\_rels') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $buildPath 'docProps') -Force | Out-Null

$body = ''
$body += Paragraph-Xml 'PUBLICACIÓN DEL PROYECTO DE TITULACIÓN' 'center' $true $false 34 '3F2418' 90
$body += Paragraph-Xml 'Diseño y Programación Web III' 'center' $true $false 24 '825337' 160
$body += Paragraph-Xml 'Sistema web inteligente para la gestión y personalización de pedidos' 'center' $false $false 22 '28201C' 80
$body += Paragraph-Xml 'A&A Multiservicios' 'center' $true $false 26 '633A24' 420
$body += Paragraph-Xml 'Repositorio: https://github.com/Carlos22084/ApisJuanCarlosZelada' 'center' $false $false 18 '4F4641' 70
$body += Paragraph-Xml 'URL pública: PENDIENTE DE COMPLETAR DESPUÉS DEL DESPLIEGUE' 'center' $true $false 18 'A3462F' 70
$body += Paragraph-Xml 'Cochabamba, 30 de octubre de 2026' 'center' $false $false 18 '4F4641' 60
$body += '<w:p><w:r><w:br w:type="page"/></w:r></w:p>'

$lines = @(Get-Content -LiteralPath $sourcePath -Encoding UTF8)
for ($index = 1; $index -lt $lines.Count; $index++) {
    $line = $lines[$index]
    if ([string]::IsNullOrWhiteSpace($line)) { continue }

    if ($line.StartsWith('|')) {
        $rows = @()
        while ($index -lt $lines.Count -and $lines[$index].StartsWith('|')) {
            $current = $lines[$index].Trim()
            if ($current -notmatch '^\|[\s:\-\|]+\|$') {
                $cells = @($current.Trim('|').Split('|') | ForEach-Object { $_.Trim() })
                $rows += ,$cells
            }
            $index++
        }
        $index--
        if ($rows.Count -gt 0) { $body += Table-Xml $rows }
        continue
    }

    if ($line -match '^###\s+(.+)$') { $body += Heading-Xml $Matches[1] 3; continue }
    if ($line -match '^##\s+(.+)$') { $body += Heading-Xml $Matches[1] 1; continue }
    if ($line -match '^#\s+(.+)$') { continue }
    if ($line -match '^>\s*(.+)$') { $body += Paragraph-Xml (Plain-Text $Matches[1]) 'left' $false $true 18 '6B5B52' 100 300; continue }
    if ($line -match '^[-*]\s+(.+)$') { $body += Paragraph-Xml ('• ' + (Plain-Text $Matches[1])) 'left' $false $false 18 '28201C' 55 350; continue }
    if ($line -match '^\d+\.\s+(.+)$') { $body += Paragraph-Xml (Plain-Text $line) 'left' $false $false 18 '28201C' 55 350; continue }

    $body += Paragraph-Xml (Plain-Text $line) 'both' $false $false 18 '28201C' 95
}

$body += '<w:p><w:r><w:br w:type="page"/></w:r></w:p>'
$body += Heading-Xml 'Anexo: espacios para evidencias finales' 1
foreach ($capture in @(
    'Evidencia 1. Repositorio GitHub y último commit',
    'Evidencia 2. Base de datos TiDB disponible',
    'Evidencia 3. Bucket de Cloudflare R2',
    'Evidencia 4. Variables protegidas de Render',
    'Evidencia 5. Build exitoso y servicio Live',
    'Evidencia 6. Aplicación funcionando desde la URL pública'
)) {
    $body += Capture-Placeholder $capture
}

$body += '<w:sectPr><w:footerReference w:type="default" r:id="rId2"/><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="900" w:right="850" w:bottom="900" w:left="950" w:header="500" w:footer="500" w:gutter="0"/></w:sectPr>'

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><w:body>' + $body + '</w:body></w:document>'
$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="20"/></w:rPr></w:rPrDefault></w:docDefaults><w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style></w:styles>'
$footerXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="16"/><w:color w:val="6F625A"/></w:rPr><w:t>Publicación del Proyecto de Titulación | A&amp;A Multiservicios | Página </w:t></w:r><w:fldSimple w:instr="PAGE"><w:r><w:rPr><w:sz w:val="16"/></w:rPr><w:t>1</w:t></w:r></w:fldSimple></w:p></w:ftr>'
$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/><Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>'
$rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>'
$documentRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/></Relationships>'
$coreXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Publicación del Proyecto de Titulación</dc:title><dc:subject>Diseño y Programación Web III</dc:subject><dc:creator>A&amp;A Multiservicios</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">2026-10-30T00:00:00Z</dcterms:created></cp:coreProperties>'
$appXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Microsoft Office Word</Application></Properties>'

$files = @{
    '[Content_Types].xml' = $contentTypes
    '_rels\.rels' = $rootRels
    'word\document.xml' = $documentXml
    'word\styles.xml' = $stylesXml
    'word\footer1.xml' = $footerXml
    'word\_rels\document.xml.rels' = $documentRels
    'docProps\core.xml' = $coreXml
    'docProps\app.xml' = $appXml
}

foreach ($relativePath in $files.Keys) {
    [System.IO.File]::WriteAllText((Join-Path $buildPath $relativePath), $files[$relativePath], $utf8)
}

if (Test-Path -LiteralPath $outputPath) { [System.IO.File]::Delete($outputPath) }
$outputStream = [System.IO.File]::Open($outputPath, [System.IO.FileMode]::CreateNew)
$archive = New-Object System.IO.Compression.ZipArchive($outputStream, [System.IO.Compression.ZipArchiveMode]::Create, $false)
try {
    foreach ($file in Get-ChildItem -LiteralPath $buildPath -Recurse -File) {
        $entryName = $file.FullName.Substring($buildPath.Length + 1).Replace('\', '/')
        $entry = $archive.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
        $entryStream = $entry.Open()
        $inputStream = [System.IO.File]::OpenRead($file.FullName)
        try { $inputStream.CopyTo($entryStream) } finally { $inputStream.Dispose(); $entryStream.Dispose() }
    }
} finally {
    $archive.Dispose()
    $outputStream.Dispose()
}

$resolvedBuild = [System.IO.Path]::GetFullPath($buildPath)
$resolvedTemp = [System.IO.Path]::GetFullPath($tempRoot)
if ($resolvedBuild.StartsWith($resolvedTemp, [System.StringComparison]::OrdinalIgnoreCase)) {
    Remove-Item -LiteralPath $resolvedBuild -Recurse -Force
}

Get-Item -LiteralPath $outputPath | Select-Object FullName, Length, LastWriteTime
