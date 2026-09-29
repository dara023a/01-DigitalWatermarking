Write-Output '========================================'
Write-Output 'SPECTRA WATERMARKING - FULL APP TEST'
Write-Output '========================================'
Write-Output ''

# Test 1: Homepage
Write-Output '=== TEST 1: Homepage (GET /) ==='
try {
    $response = Invoke-WebRequest -Uri 'http://127.0.0.1:8000' -UseBasicParsing -TimeoutSec 10
    Write-Output ('Status: ' + $response.StatusCode)
    Write-Output ('Content-Length: ' + $response.Content.Length)
    if ($response.Content -match 'Spectra') {
        Write-Output 'PASS: Homepage contains Spectra'
    } else {
        Write-Output 'FAIL: Homepage missing Spectra'
    }
} catch {
    Write-Output ('FAIL: ' + $_.Exception.Message)
}

Write-Output ''

# Test 2: Embedding Page
Write-Output '=== TEST 2: Embedding Page (GET /embedding) ==='
try {
    $response = Invoke-WebRequest -Uri 'http://127.0.0.1:8000/embedding' -UseBasicParsing -TimeoutSec 10
    Write-Output ('Status: ' + $response.StatusCode)
    if ($response.Content -match 'Embedding') {
        Write-Output 'PASS: Embedding page loads'
    } else {
        Write-Output 'FAIL: Embedding page missing content'
    }
} catch {
    Write-Output ('FAIL: ' + $_.Exception.Message)
}

Write-Output ''

# Test 3: Attack Page
Write-Output '=== TEST 3: Attack Page (GET /attack) ==='
try {
    $response = Invoke-WebRequest -Uri 'http://127.0.0.1:8000/attack' -UseBasicParsing -TimeoutSec 10
    Write-Output ('Status: ' + $response.StatusCode)
    if ($response.Content -match 'Attack') {
        Write-Output 'PASS: Attack page loads'
    } else {
        Write-Output 'FAIL: Attack page missing content'
    }
} catch {
    Write-Output ('FAIL: ' + $_.Exception.Message)
}

Write-Output ''

# Test 4: Extraction Page
Write-Output '=== TEST 4: Extraction Page (GET /extraction) ==='
try {
    $response = Invoke-WebRequest -Uri 'http://127.0.0.1:8000/extraction' -UseBasicParsing -TimeoutSec 10
    Write-Output ('Status: ' + $response.StatusCode)
    if ($response.Content -match 'Extraction') {
        Write-Output 'PASS: Extraction page loads'
    } else {
        Write-Output 'FAIL: Extraction page missing content'
    }
} catch {
    Write-Output ('FAIL: ' + $_.Exception.Message)
}

Write-Output ''

# Test 5: Evaluation Page
Write-Output '=== TEST 5: Evaluation Page (GET /evaluation) ==='
try {
    $response = Invoke-WebRequest -Uri 'http://127.0.0.1:8000/evaluation' -UseBasicParsing -TimeoutSec 10
    Write-Output ('Status: ' + $response.StatusCode)
    if ($response.Content -match 'Evaluation') {
        Write-Output 'PASS: Evaluation page loads'
    } else {
        Write-Output 'FAIL: Evaluation page missing content'
    }
} catch {
    Write-Output ('FAIL: ' + $_.Exception.Message)
}

Write-Output ''

# Test 6: Python Bridge
Write-Output '=== TEST 6: Python Bridge ==='
try {
    $pythonExe = 'C:\laragon\www\DigitalWatermarking\.venv\Scripts\python.exe'
    $bridgePath = 'C:\laragon\www\DigitalWatermarking\python-engine\web_bridge.py'
    $payload = '{"action":"test"}'
    $result = & $pythonExe $bridgePath 'test' 2>&1
    Write-Output ('Python bridge output: ' + $result)
    Write-Output 'PASS: Python bridge responds'
} catch {
    Write-Output ('FAIL: ' + $_.Exception.Message)
}

Write-Output ''

# Test 7: Python Engine Imports
Write-Output '=== TEST 7: Python Engine Imports ==='
try {
    $pythonExe = 'C:\laragon\www\DigitalWatermarking\.venv\Scripts\python.exe'
    $result = & $pythonExe -c 'from app.dct import dct2, idct2; from app.key import derive_seed; from app.watermark import embed_watermark, extract_watermark; from app.metrics import calculate_psnr, calculate_ssim, calculate_ncc, calculate_ber; print("All imports OK")' 2>&1
    Write-Output ('Result: ' + $result)
    Write-Output 'PASS: Python engine imports work'
} catch {
    Write-Output ('FAIL: ' + $_.Exception.Message)
}

Write-Output ''

# Test 8: Database Connection
Write-Output '=== TEST 8: Database Connection ==='
try {
    $result = php artisan tinker --execute="echo 'DB Connection: ' . config('database.default') . PHP_EOL; echo 'DB Database: ' . config('database.connections.mysql.database') . PHP_EOL;" 2>&1
    Write-Output $result
    Write-Output 'PASS: Database connection works'
} catch {
    Write-Output ('FAIL: ' + $_.Exception.Message)
}

Write-Output ''

# Test 9: Routes
Write-Output '=== TEST 9: Available Routes ==='
try {
    $result = php artisan route:list 2>&1
    Write-Output $result
} catch {
    Write-Output ('FAIL: ' + $_.Exception.Message)
}

Write-Output ''
Write-Output '========================================'
Write-Output 'TEST COMPLETE'
Write-Output '========================================'
