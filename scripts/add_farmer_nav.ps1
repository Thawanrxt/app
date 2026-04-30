$files = Get-ChildItem -Path "resources/views/admin" -Filter "*.blade.php" -Recurse

$pattern = @"
            <a class="nav-item" href="/admin/srp">
                <span class="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                        <path d="M4 5h13a3 3 0 0 1 3 3v11H7a3 3 0 0 0-3 3z"></path>
                        <path d="M7 5v14"></path>
                    </svg>
                </span>
                คู่มือมาตรฐาน SRP
            </a>
"@

$replacement = @"
            <a class="nav-item" href="/admin/srp">
                <span class="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                        <path d="M4 5h13a3 3 0 0 1 3 3v11H7a3 3 0 0 0-3 3z"></path>
                        <path d="M7 5v14"></path>
                    </svg>
                </span>
                คู่มือมาตรฐาน SRP
            </a>
            <a class="nav-item" href="/admin/srp/farmers">
                <span class="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                        <circle cx="12" cy="7" r="3"></circle>
                        <path d="M4 20c2.5-4 13.5-4 16 0"></path>
                    </svg>
                </span>
                ข้อมูลเกษตรกร
            </a>
"@

foreach ($file in $files) {
    $content = Get-Content -Raw $file.FullName
    if ($content -match [regex]::Escape($pattern)) {
        $content = $content -replace [regex]::Escape($pattern), [System.Text.RegularExpressions.Regex]::Escape($replacement)
        # Unescape backslashes
        $content = $content -replace '\\\\', '\'
        Set-Content -Path $file.FullName -Value $content
    }
}
