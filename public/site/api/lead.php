<?php
/**
 * Shipxpeed website lead endpoint.
 *
 * Receives the contact form as JSON, validates it, applies spam protection and rate limiting,
 * then delivers the lead by email (SMTP) and/or a CRM webhook.
 * Responds {ok:true} ONLY when at least one delivery channel accepted the lead.
 *
 * Configuration: copy lead-config.example.php to lead-config.php (outside git) and fill it in.
 */
declare(strict_types=1);

date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function respond(int $code, array $body): void { http_response_code($code); echo json_encode($body); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['ok' => false, 'error' => 'method_not_allowed']);

$cfgFile = __DIR__ . '/lead-config.php';
if (!is_file($cfgFile)) respond(503, ['ok' => false, 'error' => 'not_configured']);
$cfg = require $cfgFile;

// --- same-origin check -------------------------------------------------------------------
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && !in_array(rtrim($origin, '/'), $cfg['allowed_origins'] ?? [], true)) {
    respond(403, ['ok' => false, 'error' => 'forbidden_origin']);
}

// --- parse body ---------------------------------------------------------------------------
$raw = file_get_contents('php://input', false, null, 0, 20000);
$in = json_decode($raw ?: '', true);
if (!is_array($in)) respond(400, ['ok' => false, 'error' => 'bad_request']);

// --- spam protection ----------------------------------------------------------------------
if (!empty($in['website'])) respond(200, ['ok' => true]);                    // honeypot filled: silently drop
$ts = (int)($in['_ts'] ?? 0);
if ($ts > 0 && (int)(microtime(true) * 1000) - $ts < 2500) respond(422, ['ok' => false, 'errors' => ['message' => 'Please take a moment and try again.']]);

// --- rate limit: N submissions per IP per window -------------------------------------------
$dir = rtrim($cfg['storage_dir'], '/');
if (!is_dir($dir)) @mkdir($dir, 0750, true);
$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$key = $dir . '/rl_' . hash('sha256', $ip . ($cfg['rate_salt'] ?? '')) . '.json';
$now = time(); $window = (int)($cfg['rate_window_sec'] ?? 600); $max = (int)($cfg['rate_max'] ?? 5);
$hits = is_file($key) ? (json_decode((string)file_get_contents($key), true) ?: []) : [];
$hits = array_values(array_filter($hits, fn($t) => $t > $now - $window));
if (count($hits) >= $max) respond(429, ['ok' => false, 'error' => 'rate_limited']);
$hits[] = $now; @file_put_contents($key, json_encode($hits), LOCK_EX);

// --- validation -------------------------------------------------------------------------
$s = fn($k, $len) => mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', (string)($in[$k] ?? ''))), 0, $len);
$lead = [
    'name' => $s('name', 100), 'email' => $s('email', 150), 'phone' => preg_replace('/[\s-]/', '', $s('phone', 16)),
    'company' => $s('company', 120), 'volume' => $s('volume', 20), 'service' => $s('service', 30),
    'message' => mb_substr(trim((string)($in['message'] ?? '')), 0, 2000),
];
$volumes = ['0-100', '100-500', '500-2000', '2000-10000', '10000+'];
$services = ['domestic', 'b2b', 'international', 'hyperlocal', 'shopify', 'woocommerce', 'api', 'enterprise', 'pricing', 'partner', 'other'];
$err = [];
if (mb_strlen($lead['name']) < 2) $err['name'] = 'Enter your full name.';
if (!filter_var($lead['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Enter a valid business email address.';
if (!preg_match('/^(\+?91)?[6-9]\d{9}$/', $lead['phone'])) $err['phone'] = 'Enter a 10-digit Indian mobile number.';
if (mb_strlen($lead['company']) < 2) $err['company'] = 'Enter your business name.';
if (!in_array($lead['volume'], $volumes, true)) $err['volume'] = 'Choose your monthly shipment volume.';
if (!in_array($lead['service'], $services, true)) $err['service'] = 'Choose the service you are interested in.';
if (preg_match_all('#https?://#i', $lead['message']) > 2) $err['message'] = 'Please remove links from the message.';
if ($err) respond(422, ['ok' => false, 'errors' => $err]);

$lead['received_at'] = date('c');
$lead['ip_hash'] = substr(hash('sha256', $ip), 0, 16);
$lead['page'] = mb_substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 200);

// --- delivery ------------------------------------------------------------------------------
$delivered = [];

if (!empty($cfg['webhook_url'])) {
    $ch = curl_init($cfg['webhook_url']);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($lead), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $cfg['webhook_headers'] ?? [])]);
    curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($code >= 200 && $code < 300) $delivered[] = 'webhook';
}

if (!empty($cfg['smtp']['host']) && !empty($cfg['to_email'])) {
    $subject = 'New website lead: ' . $lead['company'] . ' (' . $lead['service'] . ')';
    $body = "New lead from the Shipxpeed website\r\n\r\n";
    foreach (['name', 'email', 'phone', 'company', 'volume', 'service', 'message', 'received_at', 'page'] as $k) $body .= str_pad(ucfirst($k) . ':', 14) . $lead[$k] . "\r\n";
    if (smtp_send($cfg['smtp'], $cfg['to_email'], $subject, $body, $lead['email'])) $delivered[] = 'email';
}

// keep a private copy of every lead (not a delivery channel on its own)
$fh = @fopen($dir . '/leads.csv', 'a');
if ($fh) { fputcsv($fh, array_merge($lead, ['delivered' => implode('+', $delivered) ?: 'none'])); fclose($fh); }

if (!$delivered) respond(502, ['ok' => false, 'error' => 'delivery_failed']);
respond(200, ['ok' => true]);

/** Minimal SMTP client (implicit TLS on 465 or STARTTLS on 587) with AUTH LOGIN. */
function smtp_send(array $s, string $to, string $subject, string $body, string $replyTo): bool
{
    $port = (int)($s['port'] ?? 465);
    $remote = ($port === 465 ? 'ssl://' : 'tcp://') . $s['host'] . ':' . $port;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
    $fp = @stream_socket_client($remote, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) return false;
    stream_set_timeout($fp, 10);
    $read = function () use ($fp) { $d = ''; while (($l = fgets($fp, 515)) !== false) { $d .= $l; if (isset($l[3]) && $l[3] === ' ') break; } return $d; };
    $cmd = function (string $c, array $ok) use ($fp, $read) { if ($c !== '') fwrite($fp, $c . "\r\n"); $r = $read(); return in_array((int)substr($r, 0, 3), $ok, true); };
    $host = gethostname() ?: 'localhost';
    if (!$cmd('', [220]) || !$cmd("EHLO $host", [250])) return false;
    if ($port !== 465) {
        if (!$cmd('STARTTLS', [220]) || !stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) || !$cmd("EHLO $host", [250])) return false;
    }
    if (!$cmd('AUTH LOGIN', [334]) || !$cmd(base64_encode($s['user']), [334]) || !$cmd(base64_encode($s['pass']), [235])) return false;
    $from = $s['from'] ?? $s['user'];
    if (!$cmd("MAIL FROM:<$from>", [250]) || !$cmd("RCPT TO:<$to>", [250, 251]) || !$cmd('DATA', [354])) return false;
    $safeReply = filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? $replyTo : $from;
    $headers = "From: Shipxpeed Website <$from>\r\nTo: <$to>\r\nReply-To: <$safeReply>\r\nSubject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
        . "Date: " . date('r') . "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";
    $data = $headers . "\r\n" . preg_replace('/^\./m', '..', $body) . "\r\n.";
    $ok = $cmd($data, [250]);
    $cmd('QUIT', [221]); fclose($fp);
    return $ok;
}
