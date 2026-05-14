$bytes = [System.IO.File]::ReadAllBytes("d:\GraduationProject\backend\app\Services\ChatBotService.php")
Write-Output ("BOM: " + [BitConverter]::ToString($bytes, 0, 3))
Write-Output ("File size: " + $bytes.Length)
Write-Output ("Encoding check OK")
