<?php
if (!defined('IN_PORTOFOLIO')) define('IN_PORTOFOLIO', true);

function wa_env_value($key, $fallback) {
    $hardcoded = [
        'WA_PROVIDER'        => 'mock',
        'WA_HARDCODED_MOCK'  => true,
    ];
    if (isset($hardcoded[$key]) && $hardcoded[$key] !== '') return $hardcoded[$key];
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    return $fallback;
}

// ============================================================
// 🎯 CONFIGURE AQUI O PROVIDER QUE VOCÊ QUER USAR:
//    'meta'      = WhatsApp Cloud API Oficial (Meta)
//    'evolution' = Evolution API (SaaS ou Self-Hosted)
//    'twilio'    = Twilio API
//    'zapi'      = Z-API (popular no Brasil)
//    'mock'      = NÃO ENVIA, grava arquivo local (padrão, para testar)
// ============================================================
define('WA_PROVIDER', wa_env_value('WA_PROVIDER', 'mock'));

define('WA_USE_MOCK_FALLBACK', true);
define('WA_MOCK_DIR',   __DIR__ . '/wa_pendentes');
define('WA_LOG_DIR',    __DIR__ . '/wa_pendentes/logs');

// Pastas (auto-cria)
foreach ([WA_MOCK_DIR, WA_LOG_DIR] as $d) {
    if (!is_dir($d)) @mkdir($d, 0777, true);
}
$idxHtml = WA_MOCK_DIR . '/index.html';
if (!file_exists($idxHtml)) {
    @file_put_contents($idxHtml, '<html><head><meta charset="utf-8"></head><body>Acesso negado.</body></html>');
    @file_put_contents(WA_MOCK_DIR . '/.htaccess', "Options -Indexes\nDeny from all\n");
}

// ============================================================
// 🔑 CREDENCIAIS DOS PROVIDERS (coloque aqui quando assinar)
// ============================================================

// --- Provider 1: META WhatsApp Cloud API (Oficial, GRATIS 1000/mês)
define('WA_META_PHONE_NUMBER_ID', wa_env_value('WA_META_PHONE_NUMBER_ID', '')); // Ex: 1234567890
define('WA_META_ACCESS_TOKEN',   wa_env_value('WA_META_ACCESS_TOKEN', ''));    // Token de 24h ou permanente
define('WA_META_GRAPH_VERSION',  'v20.0');
define('WA_META_FROM_NUMBER',    wa_env_value('WA_META_FROM_NUMBER', ''));     // Ex: 5511999998888 (com 55, sem +)

// --- Provider 2: EVOLUTION API (SaaS ou self-hosted)
define('WA_EVOLUTION_BASE_URL', wa_env_value('WA_EVOLUTION_BASE_URL', '')); // Ex: https://evolucao.seudominio.com/message/sendText/seu-instance
define('WA_EVOLUTION_API_KEY',  wa_env_value('WA_EVOLUTION_API_KEY', ''));  // Global Api Key
define('WA_EVOLUTION_INSTANCE', wa_env_value('WA_EVOLUTION_INSTANCE', '')); // Nome da instance (opcional)

// --- Provider 3: TWILIO
define('WA_TWILIO_ACCOUNT_SID', wa_env_value('WA_TWILIO_ACCOUNT_SID', ''));
define('WA_TWILIO_AUTH_TOKEN',  wa_env_value('WA_TWILIO_AUTH_TOKEN', ''));
define('WA_TWILIO_FROM',        wa_env_value('WA_TWILIO_FROM', 'whatsapp:+14155238886')); // Twilio sandbox ou numero alugado

// --- Provider 4: Z-API (Brasileiro, facil)
define('WA_ZAPI_BASE_URL',  wa_env_value('WA_ZAPI_BASE_URL', ''));  // Ex: https://api.z-api.io/instances/SEU_INSTANCE/token/SEU_TOKEN
define('WA_ZAPI_CLIENT_KEY',wa_env_value('WA_ZAPI_CLIENT_KEY', ''));// Client-Token (no header)


// ============================================================
// 🧩 FUNÇÕES AUXILIARES
// ============================================================

function wa_format_phone($numero) {
    if ($numero === null || $numero === '') return false;
    $limpo = preg_replace('/\D/', '', $numero);
    if (strlen($limpo) < 10) return false;
    if (strpos($limpo, '55') === 0 && strlen($limpo) >= 12) {
        return $limpo;
    }
    return '55' . $limpo;
}

function wa_template_notificacao($moradorNome, $unidade, $codigoEtiqueta, $origem, $porteiro, $dataRecebimento, $isResend = false) {
    $saudacao = 'Olá';
    $action = $isResend ? 'Lembrete! Sua encomenda continua aguardando retirada 📦' : 'Chegou! 🎉 Temos uma nova encomenda para você';
    $mensagem =
"*🏢 Portaria Orquídeas Vivendas*

$saudacao $moradorNome!
*$action*

📋 *Detalhes do Pacote:*
   🏠 Unidade: $unidade
   📦 Código: $codigoEtiqueta
   🚚 Origem: $origem
   👤 Recebido por: $porteiro
   ⏰ Horário: $dataRecebimento

📍 *Onde retirar:*
Portaria principal, de segunda a domingo, das 08:00 às 22:00.
*Atenção:* Não esqueça de trazer um documento com foto!
Se não puder retirar, autorize alguém pelo app.

*Abra o autoatendimento para confirmar retirada:*
🔗 http://localhost/encomendas/retirada_morador.php

Se você já retirou, por favor desconsidere.
Atenciosamente,
Equipe da Portaria 👋";
    return $mensagem;
}

function wa_save_mock($to, $toName, $mensagemTexto, $provider, $erro = null) {
    $file = WA_MOCK_DIR . '/' . date('Ymd_His') . '_' . substr(preg_replace('/[^a-zA-Z0-9]/', '_', $to), 0, 30) . '_' . uniqid() . '.txt';
    $body = "";
    $body .= "============================================================================\n";
    $body .= "  WHATSAPP MOCK (GRAVADO LOCALMENTE) - " . date('d/m/Y H:i:s') . "\n";
    $body .= "  Provider tentado: $provider" . ($erro ? " | ERRO: " . trim($erro) : "") . "\n";
    $body .= "============================================================================\n";
    $body .= "\n";
    $body .= "📱 Para: " . ($toName ? "$toName " : "") . "(+ " . $to . ")\n";
    $body .= "🔗 Link de envio manual via WhatsApp Web:\n";
    $body .= "   https://wa.me/" . $to . "?text=" . rawurlencode($mensagemTexto) . "\n";
    $body .= "\n";
    $body .= "----------------------------------------------------------------------------\n";
    $body .= "  MENSAGEM:\n";
    $body .= "----------------------------------------------------------------------------\n";
    $body .= $mensagemTexto . "\n";
    $body .= "\n";
    $body .= "============================================================================\n";
    $body .= "  COMO ENVIAR MANUALMENTE:\n";
    $body .= "  1) Copie o link acima (🔗 wa.me/...) e abra no navegador logado com WhatsApp Web,\n";
    $body .= "     ou clique no botão abaixo.\n";
    $body .= "  2) Seu WhatsApp abre com a mensagem PRONTA.\n";
    $body .= "  3) Basta apertar ENVIAR no seu app do WhatsApp.\n";
    $body .= "============================================================================\n";
    @file_put_contents($file, $body);
    return $file;
}

function wa_save_log($line) {
    $file = WA_LOG_DIR . '/wa_' . date('Y-m-d') . '.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . trim($line) . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND);
}


// ============================================================
// 🚀 FUNÇÃO PRINCIPAL - USA O PROVIDER SELECIONADO
// ============================================================

function whatsapp_enviar($numero, $nomeDestinatario, $mensagemTexto) {
    $fone = wa_format_phone($numero);
    if (!$fone) {
        return [
            'ok' => false,
            'via' => 'erro',
            'msg' => 'Número de telefone inválido: "' . htmlspecialchars($numero) . '".',
        ];
    }
    if (trim($mensagemTexto) === '') {
        return ['ok' => false, 'via' => 'erro', 'msg' => 'Mensagem vazia.'];
    }

    $provider = WA_PROVIDER;
    $result = ['ok' => false, 'via' => $provider, 'msg' => 'Nenhum provider executou.'];
    $erro = null;

    try {
        switch ($provider) {
            // ---------------- META CLOUD API (OFICIAL) ----------------
            case 'meta':
                if (WA_META_ACCESS_TOKEN === '' || WA_META_PHONE_NUMBER_ID === '') {
                    $erro = 'Meta Cloud API: Credenciais WA_META_PHONE_NUMBER_ID e WA_META_ACCESS_TOKEN não configuradas.';
                    break;
                }
                $url = 'https://graph.facebook.com/' . WA_META_GRAPH_VERSION . '/' . WA_META_PHONE_NUMBER_ID . '/messages';
                $payload = [
                    'messaging_product' => 'whatsapp',
                    'to'                => $fone,
                    'type'              => 'text',
                    'text'              => ['body' => $mensagemTexto, 'preview_url' => false],
                ];
                $headers = [
                    'Authorization: Bearer ' . WA_META_ACCESS_TOKEN,
                    'Content-Type: application/json',
                ];
                list($ok, $resp) = wa_http($url, $headers, $payload, 'POST', 'json');
                if (!$ok) { $erro = 'Meta Cloud API HTTP Error: ' . $resp; break; }
                $arr = @json_decode($resp, true);
                if (isset($arr['error'])) { $erro = 'Meta API Erro: ' . trim($arr['error']['message'] ?? json_encode($arr)); break; }
                if (isset($arr['messages'][0]['id'])) {
                    $result = ['ok' => true, 'via' => 'META Cloud API', 'msg' => 'Enviado (msg_id=' . $arr['messages'][0]['id'] . ')', 'id' => $arr['messages'][0]['id']];
                    wa_save_log("META OK to +$fone: id=" . $arr['messages'][0]['id']);
                    return $result;
                }
                $erro = 'Meta API resposta inesperada: ' . substr($resp, 0, 400);
                break;

            // ---------------- EVOLUTION API ----------------
            case 'evolution':
                $base = trim(WA_EVOLUTION_BASE_URL, '/ ');
                if ($base === '' || WA_EVOLUTION_API_KEY === '') {
                    $erro = 'Evolution API: WA_EVOLUTION_BASE_URL e WA_EVOLUTION_API_KEY não configurados.';
                    break;
                }
                $url = $base;
                if (stripos($base, '/sendText') === false) {
                    $instance = WA_EVOLUTION_INSTANCE ?: 'portaria';
                    $url = rtrim($base, '/') . '/message/sendText/' . $instance;
                }
                $payload = [
                    'number'   => $fone,
                    'options'  => ['delay' => 600, 'presence' => 'composing'],
                    'textMessage' => ['text' => $mensagemTexto],
                ];
                $headers = [
                    'Content-Type: application/json',
                    'apikey: ' . WA_EVOLUTION_API_KEY,
                ];
                list($ok, $resp) = wa_http($url, $headers, $payload, 'POST', 'json');
                if (!$ok) { $erro = 'Evolution HTTP Error: ' . $resp; break; }
                $arr = @json_decode($resp, true);
                if (isset($arr['error']) || (isset($arr['status']) && $arr['status'] === 400)) {
                    $erro = 'Evolution Erro: ' . trim(($arr['message'] ?? '') . ' ' . ($arr['error']['message'] ?? json_encode($arr))); break;
                }
                $result = ['ok' => true, 'via' => 'Evolution API', 'msg' => 'Enviado com sucesso.', 'raw' => $arr];
                wa_save_log("Evolution OK to +$fone");
                return $result;

            // ---------------- TWILIO ----------------
            case 'twilio':
                if (WA_TWILIO_ACCOUNT_SID === '' || WA_TWILIO_AUTH_TOKEN === '') {
                    $erro = 'Twilio: Credenciais WA_TWILIO_ACCOUNT_SID e WA_TWILIO_AUTH_TOKEN não configuradas.';
                    break;
                }
                $url = 'https://api.twilio.com/2010-04-01/Accounts/' . WA_TWILIO_ACCOUNT_SID . '/Messages.json';
                $payload = [
                    'From' => WA_TWILIO_FROM,
                    'To'   => 'whatsapp:+' . $fone,
                    'Body' => $mensagemTexto,
                ];
                $headers = [
                    'Authorization: Basic ' . base64_encode(WA_TWILIO_ACCOUNT_SID . ':' . WA_TWILIO_AUTH_TOKEN),
                ];
                list($ok, $resp) = wa_http($url, $headers, $payload, 'POST', 'form');
                if (!$ok) { $erro = 'Twilio HTTP Error: ' . $resp; break; }
                $arr = @json_decode($resp, true);
                if (isset($arr['sid']) && !isset($arr['code'])) {
                    $result = ['ok' => true, 'via' => 'Twilio', 'msg' => 'Enviado sid=' . $arr['sid'], 'id' => $arr['sid']];
                    wa_save_log("Twilio OK to +$fone sid=" . $arr['sid']);
                    return $result;
                }
                $erro = 'Twilio Erro: ' . trim(($arr['message'] ?? '') . ' code=' . ($arr['code'] ?? ''));
                break;

            // ---------------- Z-API ----------------
            case 'zapi':
                $base = trim(WA_ZAPI_BASE_URL, '/ ');
                if ($base === '') {
                    $erro = 'Z-API: WA_ZAPI_BASE_URL não configurado.';
                    break;
                }
                $url = $base . '/send-text';
                $payload = [
                    'phone'  => $fone,
                    'text'   => $mensagemTexto,
                    'delay'  => 600,
                ];
                $headers = ['Content-Type: application/json'];
                if (WA_ZAPI_CLIENT_KEY !== '') {
                    $headers[] = 'Client-Token: ' . WA_ZAPI_CLIENT_KEY;
                }
                list($ok, $resp) = wa_http($url, $headers, $payload, 'POST', 'json');
                if (!$ok) { $erro = 'Z-API HTTP Error: ' . $resp; break; }
                $arr = @json_decode($resp, true);
                if (isset($arr['erro']) || (isset($arr['status']) && in_array($arr['status'], ['Error', 'error']))) {
                    $erro = 'Z-API Erro: ' . trim($arr['message'] ?? json_encode($arr)); break;
                }
                $result = ['ok' => true, 'via' => 'Z-API', 'msg' => 'Enviado com sucesso.', 'raw' => $arr];
                wa_save_log("Z-API OK to +$fone");
                return $result;

            // ---------------- MOCK (pula direto para fallback sem erro) ----------------
            case 'mock':
                $erro = null;
                break;

            default:
                $erro = "Provider desconhecido: $provider.";
                break;
        }
    } catch (\Throwable $t) {
        $erro = 'Exception: ' . $t->getMessage();
    }

    // ---------------- FALLBACK MOCK ----------------
    if (WA_USE_MOCK_FALLBACK) {
        $file = wa_save_mock($fone, $nomeDestinatario, $mensagemTexto, $provider, $erro);
        wa_save_log("FALLBACK MOCK to +$fone (tentou $provider, erro: " . trim($erro ?? 'nenhum') . ") file=" . basename($file));
        $viaMsg = 'MOCK (gravado localmente';
        if ($erro) $viaMsg .= "; tentou $provider mas: " . trim(mb_strimwidth($erro, 0, 60, "..."));
        $viaMsg .= ')';
        return [
            'ok' => true,
            'via' => $viaMsg,
            'msg' => 'Mensagem gravada localmente.',
            'file' => $file,
            'wa_me' => 'https://wa.me/' . $fone . '?text=' . rawurlencode($mensagemTexto),
        ];
    }

    return [
        'ok' => false,
        'via' => $provider,
        'msg' => $erro ?: 'Falha desconhecida.',
    ];
}

function wa_http($url, $headers, $payload, $method = 'POST', $encode = 'json') {
    $ch = curl_init();
    if ($encode === 'json') {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $headers[] = 'Content-Length: ' . strlen($body);
    } else { // form
        $body = http_build_query($payload);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // dev mode; em producao deixar true
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($resp === false) return [false, 'CURL Error: ' . $err];
    if ($httpCode >= 400) return [false, "HTTP $httpCode: " . $resp];
    return [true, $resp];
}

function wa_link_wame($numero, $mensagem) {
    $fone = wa_format_phone($numero);
    if (!$fone) return '#';
    return 'https://wa.me/' . $fone . '?text=' . rawurlencode($mensagem);
}
?>