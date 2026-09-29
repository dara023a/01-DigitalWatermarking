Write-Output '========================================'
Write-Output 'FULL LARAVEL FLOW TEST'
Write-Output '========================================'
Write-Output ''

$baseUrl = 'http://127.0.0.1:8000'
$tempDir = [System.IO.Path]::GetTempPath()
$testImage = Join-Path $tempDir 'test_original.png'
$testWatermark = Join-Path $tempDir 'test_watermark.png'

# Create test images using Python
Write-Output '=== Creating test images ==='
$pythonExe = 'C:\laragon\www\DigitalWatermarking\.venv\Scripts\python.exe'
$createScript = @"
import cv2
import numpy as np
import os

# Create test original image (256x256 grayscale)
img = np.zeros((256, 256), dtype=np.uint8)
for i in range(256):
    for j in range(256):
        img[i, j] = (i + j) % 256
cv2.imwrite(r'$testImage', img)

# Create test watermark (32x32 binary)
wm = np.zeros((32, 32), dtype=np.uint8)
for i in range(32):
    for j in range(32):
        wm[i, j] = 255 if (i + j) % 2 == 0 else 0
cv2.imwrite(r'$testWatermark', wm)

print('Test images created')
"@

$createScript | & $pythonExe -
Write-Output 'Test images created'
Write-Output ''

# Test 1: Embedding
Write-Output '=== TEST 1: EMBEDDING (POST /embedding) ==='
try {
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    
    # Get CSRF token
    $response = Invoke-WebRequest -Uri "$baseUrl/embedding" -WebSession $session -UseBasicParsing
    $csrfMatch = [regex]::Match($response.Content, 'name="_token" value="([^"]+)"')
    $csrfToken = $csrfMatch.Groups[1].Value
    Write-Output "CSRF Token: $csrfToken"
    
    # Create form data
    $boundary = [System.Guid]::NewGuid().ToString()
    $fileBytes = [System.IO.File]::ReadAllBytes($testImage)
    $fileContent = [System.Text.Encoding]::GetEncoding('iso-8859-1').GetString($fileBytes)
    
    $body = "--$boundary`r`nContent-Disposition: form-data; name=`"_token`"`r`n`r`n$csrfToken`r`n"
    $body += "--$boundary`r`nContent-Disposition: form-data; name=`"original_image`"; filename=`"test_original.png`"`r`nContent-Type: image/png`r`n`r`n$fileContent`r`n"
    
    $fileBytes2 = [System.IO.File]::ReadAllBytes($testWatermark)
    $fileContent2 = [System.Text.Encoding]::GetEncoding('iso-8859-1').GetString($fileBytes2)
    $body += "--$boundary`r`nContent-Disposition: form-data; name=`"watermark_image`"; filename=`"test_watermark.png`"`r`nContent-Type: image/png`r`n`r`n$fileContent2`r`n"
    $body += "--$boundary`r`nContent-Disposition: form-data; name=`"secret_key`"`r`n`r`ntest-secret-key`r`n"
    $body += "--$boundary`r`nContent-Disposition: form-data; name=`"alpha`"`r`n`r`n5`r`n"
    $body += "--$boundary`r`nContent-Disposition: form-data; name=`"redundancy`"`r`n`r`n1`r`n"
    $body += "--$boundary`r`nContent-Disposition: form-data; name=`"threshold`"`r`n`r`n127`r`n"
    $body += "--$boundary`r`nContent-Disposition: form-data; name=`"preserve_color`"`r`n`r`n1`r`n"
    $body += "--$boundary--`r`n"
    
    $headers = @{
        'Content-Type' = "multipart/form-data; boundary=$boundary"
    }
    
    $response = Invoke-WebRequest -Uri "$baseUrl/embedding" -Method POST -WebSession $session -Body $body -Headers $headers -UseBasicParsing
    Write-Output "Status: $($response.StatusCode)"
    Write-Output "PASS: Embedding works"
} catch {
    Write-Output "FAIL: $($_.Exception.Message)"
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Output "Response: $responseBody"
    }
}

Write-Output ''
Write-Output '========================================'
Write-Output 'TEST COMPLETE'
Write-Output '========================================'
