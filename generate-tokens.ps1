<# 
.SYNOPSIS
    Generate CSS custom properties dari design-tokens.json

.DESCRIPTION
    Script ini membaca design-tokens.json dan generate file public/css/design-tokens.css
    Jalankan setelah mengubah design-tokens.json

.EXAMPLE
    .\generate-tokens.ps1
#>

param()

$tokensPath = "C:\coffeeshop\design-tokens.json"
$outputPath = "C:\coffeeshop\public\css\design-tokens.css"

if (-not (Test-Path $tokensPath)) {
    Write-Error "File tidak ditemukan: $tokensPath"
    exit 1
}

$json = Get-Content $tokensPath -Raw | ConvertFrom-Json

# Hanya proses kategori token, skip metadata
$tokenCategories = @(
    "color", "typography", "spacing", "borderRadius", 
    "shadows", "transitions", "zIndex", "breakpoints", 
    "container", "components"
)

function FlattenTokens($obj, $prefix = "") {
    $result = @{}
    foreach ($prop in $obj.PSObject.Properties) {
        $name = $prop.Name
        $value = $prop.Value
        $key = if ($prefix) { "$prefix-$name" } else { "mz-$name" }
        
        if ($value -is [System.Management.Automation.PSCustomObject] -and $value.PSObject.Properties.Count -gt 0) {
            # Check DIRECT "value" property (not recursive)
            if ($value.PSObject.Properties["value"]) {
                $result[$key] = $value.value
            } else {
                $nested = FlattenTokens $value $key
                $result += $nested
            }
        } elseif ($value -is [string] -or $value -is [int] -or $value -is [double] -or $value -is [bool]) {
            $result[$key] = $value
        }
    }
    return $result
}

$allTokens = @{}
foreach ($cat in $tokenCategories) {
    if ($json.PSObject.Properties.Match($cat)) {
        $catTokens = FlattenTokens $json.$cat $cat
        $allTokens += $catTokens
    }
}

$css = @"
/* =========================================================
   Magezz Coffee — Design Tokens (Auto-generated from design-tokens.json)
   JANGAN EDIT LANGSUNG — edit design-tokens.json lalu jalankan generate-tokens.ps1
   Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
   ========================================================= */

:root {
"@

foreach ($item in $allTokens.GetEnumerator() | Sort-Object Key) {
    if ($null -ne $item.Value -and $item.Value -ne "") {
        $css += "  --$($item.Key): $($item.Value);`n"
    }
}

$css += "}`n"

# Tulis tanpa BOM agar konsisten dengan file CSS lain (hindari karakter BOM liar)
$cleanUtf8 = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($outputPath, $css, $cleanUtf8)
Write-Host "Generated: $outputPath" -ForegroundColor Green
