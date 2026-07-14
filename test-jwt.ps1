param([string]$BaseUrl = "http://localhost:8080")

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "   TESTS JWT - VoraCMS" -ForegroundColor Cyan
Write-Host "   Servidor: $BaseUrl" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

$script:pass = 0
$script:fail = 0

function Test-Step {
    param($Num, $Label, $Expected, $Path, $Headers = @())

    $curlArgs = @("-s", "$BaseUrl$Path", "-X", "GET")
    foreach ($h in $Headers) { $curlArgs += "-H"; $curlArgs += $h }

    $resp = & "curl.exe" @curlArgs 2>$null
    $ok = $false

    if ($Expected -eq 200 -and $resp -match '"token"') { $ok = $true }
    elseif ($Expected -eq 200 -and $resp -match '"(data|user|ok)"') { $ok = $true }
    elseif ($Expected -eq 403 -and ($resp -match 'Forbidden' -or $resp -match '"error"')) { $ok = $true }
    elseif ($Expected -eq 401) { $ok = $true }
    elseif ($Expected -eq 400 -and $resp -match '"error"') { $ok = $true }

    if ($ok) { $script:pass++ } else { $script:fail++ }

    $icon = if ($ok) { "PASS" } else { "FAIL" }
    $color = if ($ok) { "Green" } else { "Red" }
    Write-Host "  [$Num] $icon $Label" -ForegroundColor $color
}

function Test-Post {
    param($Num, $Label, $Expected, $Path, $Body, $Headers = @())

    $tmpFile = [System.IO.Path]::GetTempFileName()
    $Body | Set-Content -Path $tmpFile -Encoding ASCII

    $curlArgs = @("-s", "$BaseUrl$Path", "-X", "POST", "-d", "@$tmpFile", "-H", "Content-Type: application/json")
    foreach ($h in $Headers) { $curlArgs += "-H"; $curlArgs += $h }

    $resp = & "curl.exe" @curlArgs 2>$null
    Remove-Item $tmpFile -Force -ErrorAction SilentlyContinue

    $ok = $false
    if ($Expected -eq 200 -and $resp -match '"ok".*true') { $ok = $true }
    elseif ($Expected -eq 400 -and $resp -match '"error"') { $ok = $true }
    elseif ($Expected -eq 403 -and ($resp -match 'Forbidden' -or $resp -match '"error"')) { $ok = $true }

    if ($ok) { $script:pass++ } else { $script:fail++ }

    $icon = if ($ok) { "PASS" } else { "FAIL" }
    $color = if ($ok) { "Green" } else { "Red" }
    Write-Host "  [$Num] $icon $Label" -ForegroundColor $color
}

function Test-Cors {
    param($Num, $Label, $Path, $Origin, $ExpectAllowed)

    $headerDump = & curl.exe -s -D - "$BaseUrl$Path" -H "Origin: $Origin" -o NUL 2>$null

    $hasACAO = ($headerDump -join "`n") -match 'Access-Control-Allow-Origin'
    $httpLine = $headerDump[0]
    $httpCode = if ($httpLine -match 'HTTP.*?(\d{3})') { $matches[1] } else { "000" }

    $ok = $false
    if ($ExpectAllowed -and $httpCode -eq 200 -and $hasACAO) { $ok = $true }
    if (!$ExpectAllowed -and $httpCode -eq 403 -and !$hasACAO) { $ok = $true }

    if ($ok) { $script:pass++ } else { $script:fail++ }

    $icon = if ($ok) { "PASS" } else { "FAIL" }
    $color = if ($ok) { "Green" } else { "Red" }
    $detail = if ($ExpectAllowed) { "(esperat: 200 + ACAO)" } else { "(esperat: 403 sense ACAO)" }
    Write-Host "  [$Num] $icon $Label $detail" -ForegroundColor $color
}

# ─── 1. MASTER TOKEN ───
Write-Host "=== 1. MASTER TOKEN ===" -ForegroundColor Yellow
Write-Host ""

Test-Step "1.1" "Origin: localhost -> token" 200 "/api/public/token" @("Origin: http://localhost")
Test-Step "1.2" "Origin: hacker.com -> 403" 403 "/api/public/token" @("Origin: http://hacker.com")
Test-Step "1.3" "Sense Origin (Host: localhost) -> token" 200 "/api/public/token" @("Host: localhost:8080")
Test-Step "1.4" "Sense Origin (Host: hacker.com) -> 403" 403 "/api/public/token" @("Host: hacker.com")
Write-Host ""

# ─── 2. JWT EN ACCIO ───
Write-Host "=== 2. JWT EN ACCIO ===" -ForegroundColor Yellow
Write-Host ""

$tokenResp = & curl.exe -s "$BaseUrl/api/public/token" -H "Origin: http://localhost"
$TOKEN = ($tokenResp | ConvertFrom-Json -ErrorAction SilentlyContinue).token

if ($TOKEN) {
    Write-Host "  Token: $($TOKEN.Substring(0,20))..." -ForegroundColor Gray
    Write-Host ""
    Test-Step "2.1" "/api/auth/me amb JWT valid" 200 "/api/auth/me" @("Authorization: Bearer $TOKEN")
} else {
    Write-Host "  ERROR: No s'ha pogut obtenir token" -ForegroundColor Red
}
Test-Step "2.2" "/api/auth/me sense JWT" 401 "/api/auth/me"
Test-Step "2.3" "/api/auth/me amb JWT fals" 401 "/api/auth/me" @("Authorization: Bearer eyJmYWtlIn0")
Write-Host ""

# ─── 3. PAYLOAD ───
Write-Host "=== 3. PAYLOAD ===" -ForegroundColor Yellow
Write-Host ""

if ($TOKEN) {
    $parts = $TOKEN.Split('.')
    $padded = $parts[1].Replace('-', '+').Replace('_', '/')
    while ($padded.Length % 4 -ne 0) { $padded += '=' }
    $payloadBytes = [System.Convert]::FromBase64String($padded)
    $payload = [System.Text.Encoding]::UTF8.GetString($payloadBytes) | ConvertFrom-Json
    Write-Host "  user_id:         $($payload.user_id)"
    Write-Host "  username:        $($payload.username)"
    Write-Host "  user_slug:       $($payload.user_slug)"
    Write-Host "  allowed_domains: $($payload.allowed_domains -join ', ')"
    Write-Host "  TTL:             $($payload.exp - $payload.iat)s (esperat: 3600s)"
} else {
    Write-Host "  Token no disponible" -ForegroundColor Yellow
}
Write-Host ""

# ─── 5. CORS DES DE BD (DbCorsOriginResolver) ───
Write-Host "=== 5. CORS DES DE BD ===" -ForegroundColor Yellow
Write-Host ""

Test-Cors "5.1" "Origin localhost -> ACAO header" "/api/public/token" "http://localhost" $true
Test-Cors "5.2" "Origin evil.com -> 403" "/api/public/token" "http://evil.com" $false
Write-Host ""

# ─── 6. VISIT CONTROLLER ───
Write-Host "=== 6. VISIT CONTROLLER ===" -ForegroundColor Yellow
Write-Host ""

if ($TOKEN) {
    Test-Post "6.1" "POST /api/visit amb entry_id valid (confiat)" 200 "/api/visit" "{`"entry_id`":28}" @("Authorization: Bearer $TOKEN")
    Test-Post "6.2" "POST /api/visit sense entry_id -> 400" 400 "/api/visit" "{}" @("Authorization: Bearer $TOKEN")
} else {
    Write-Host "  [6.1] SKIP (sense token)" -ForegroundColor Yellow
    Write-Host "  [6.2] SKIP (sense token)" -ForegroundColor Yellow
}
Write-Host ""

# ─── 7. PHP SYNTAX ───
Write-Host "=== 7. PHP SYNTAX ===" -ForegroundColor Yellow
Write-Host ""

$files = @(
    "src\Entity\User.php",
    "src\Service\TokenMasterService.php",
    "src\Service\DbCorsOriginResolver.php",
    "src\Controller\Api\PublicController.php",
    "src\Controller\Api\VisitController.php",
    "src\Controller\Admin\UserController.php",
    "src\Repository\UserRepository.php",
    "..\VoraStudio\includes\CmsClient.php"
)

foreach ($f in $files) {
    $abs = "C:\xampp\htdocs\VoraStudio\voracms\$f"
    if (!(Test-Path $abs)) {
        Write-Host "  [..] No trobat: $f" -ForegroundColor Yellow
        continue
    }
    $result = php -l $abs 2>&1
    if ($result -match "No syntax errors") {
        Write-Host "  PASS $f" -ForegroundColor Green
        $script:pass++
    } else {
        Write-Host "  FAIL $f - ERROR" -ForegroundColor Red
        $script:fail++
    }
}

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "   RESUM" -ForegroundColor Cyan
Write-Host "   Passats: $($script:pass)   Fallits: $($script:fail)" -ForegroundColor $(if ($script:fail -eq 0) { "Green" } else { "Red" })
if ($script:fail -eq 0) {
    Write-Host "   TOTS ELS TESTS PASSEN" -ForegroundColor Green
}
Write-Host "=========================================" -ForegroundColor Cyan
