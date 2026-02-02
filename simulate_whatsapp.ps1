$rand = Get-Random
$headers = @{ 'Client-Token' = 'TEST_SECURE_TOKEN' }
$body = @{ 
    type       = 'ReceivedMessage'
    phone      = '5511999999999'
    senderName = 'Novo Doador'
    messageId  = "MSG_SIM_R_$rand"
    text       = @{ message = 'Ola! Gostaria de saber como posso doar para a ONG?' } 
} | ConvertTo-Json -Depth 3

Write-Output "Enviando mensagem simulada ID: MSG_SIM_R_$rand"

try {
    $response = Invoke-RestMethod -Uri 'http://localhost/vivensi-laravel/public/api/whatsapp/webhook' -Method Post -Headers $headers -Body $body -ContentType 'application/json'
    Write-Output "Status: 200 (Success)"
    Write-Output $response
}
catch {
    Write-Output "Status: Error"
    Write-Output $_.ToString()
}
