$files = Get-ChildItem -Path "resources/views/admin" -Filter "*.blade.php" -Recurse
$cssParts = @()

foreach ($file in $files) {
    $content = Get-Content -Raw $file.FullName
    if ($content -match '(?s)<style>(.*?)</style>') {
        $css = $matches[1].Trim()
        if ($css) {
            $cssParts += "/* SOURCE: $($file.FullName) */`n$css`n"
        }
        $content = [regex]::Replace($content, '(?s)<style>.*?</style>', '    <link rel="stylesheet" href="/css/admin.css">', 1)
    } elseif ($content -notmatch 'admin\.css') {
        $content = $content -replace '(</title>\s*)', "`$1`n    <link rel=`"stylesheet`" href=`"/css/admin.css`">`n"
    }
    Set-Content -Path $file.FullName -Value $content
}

if (-not (Test-Path "public/css")) {
    New-Item -ItemType Directory -Path "public/css" | Out-Null
}

$cssContent = ($cssParts -join "`n")
Set-Content -Path "public/css/admin.css" -Value $cssContent
