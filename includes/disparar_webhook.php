<?php
/**
 * Função para disparar um webhook para uma URL externa.
 *
 * @param array $deposito Os dados do depósito que foi aprovado.
 * Ex: ['id' => 123, 'user_id' => 45, 'valor' => 50.00, 'transactionId' => 'xyz-123', 'gateway' => 'digitopay']
 */
function dispararWebhookDepositoAprovado($deposito)
{
    // !!! IMPORTANTE: Insira a URL do seu webhook aqui !!!
    $webhookUrl = 'https://webhook.felipeyago.com/webhook/dep-aprovado-raspadinhabr.bet';

    // Prepara os dados que serão enviados no corpo da requisição
    $payload = json_encode([
        'tipo' => 'DEPOSITO_APROVADO',
        'id_deposito' => $deposito['id'],
        'id_usuario' => $deposito['user_id'],
        'valor' => $deposito['valor'],
        'transaction_id' => $deposito['transactionId'] ?? null, // Adicionado para compatibilidade
        'gateway' => $deposito['gateway'] ?? 'desconhecido',
        'timestamp' => date('Y-m-d H:i:s'),
    ]);

    // Inicia o cURL
    $ch = curl_init($webhookUrl);

    // Configura as opções do cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload),
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 segundos
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    // Executa a requisição
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    // Fecha a conexão cURL
    curl_close($ch);

    // (Opcional) Você pode registrar o resultado do disparo do webhook em um log
    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        $logMessage = "Disparo de Webhook para Deposito ID {$deposito['id']}: ";
        if ($error) {
            $logMessage .= "Erro cURL: " . $error;
        } else {
            $logMessage .= "Status: {$httpCode} | Resposta: {$response}";
        }
        writeLog($logMessage);
    }
}