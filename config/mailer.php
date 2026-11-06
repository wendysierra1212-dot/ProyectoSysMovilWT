<?php
declare(strict_types=1);

function smtpRespuesta($socket, array $codigos): string
{
    $respuesta = '';
    do {
        $linea = fgets($socket, 515);
        if ($linea === false) throw new RuntimeException('El servidor SMTP cerró la conexión.');
        $respuesta .= $linea;
    } while (isset($linea[3]) && $linea[3] === '-');

    $codigo = (int)substr($respuesta, 0, 3);
    if (!in_array($codigo, $codigos, true)) {
        throw new RuntimeException('Respuesta SMTP inesperada: ' . trim($respuesta));
    }
    return $respuesta;
}

function smtpComando($socket, string $comando, array $codigos): string
{
    if (fwrite($socket, $comando . "\r\n") === false) throw new RuntimeException('No se pudo escribir al servidor SMTP.');
    return smtpRespuesta($socket, $codigos);
}

function enviarCorreoSysmovil(string $destino, string $asunto, string $html): void
{
    $config = require __DIR__ . '/correo.php';
    foreach (['host', 'username', 'password', 'from_address'] as $campo) {
        if ($config[$campo] === '') throw new RuntimeException('Falta configurar SMTP: ' . $campo);
    }
    if (!filter_var($destino, FILTER_VALIDATE_EMAIL) || !filter_var($config['from_address'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('El correo de destino o remitente no es válido.');
    }

    $host = (string)$config['host'];
    $seguridad = (string)$config['encryption'];
    $prefijo = $seguridad === 'ssl' ? 'ssl://' : '';
    $contexto = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false]]);
    $socket = @stream_socket_client(
        $prefijo . $host . ':' . (int)$config['port'],
        $errorNumero,
        $errorTexto,
        15,
        STREAM_CLIENT_CONNECT,
        $contexto
    );
    if (!$socket) throw new RuntimeException('No fue posible conectar al servidor SMTP: ' . $errorTexto);
    stream_set_timeout($socket, 15);

    try {
        smtpRespuesta($socket, [220]);
        $dominio = preg_replace('/[^a-z0-9.-]/i', '', (string)($_SERVER['SERVER_NAME'] ?? 'localhost')) ?: 'localhost';
        smtpComando($socket, 'EHLO ' . $dominio, [250]);

        if ($seguridad === 'tls') {
            smtpComando($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('No se pudo activar TLS para SMTP.');
            }
            smtpComando($socket, 'EHLO ' . $dominio, [250]);
        } elseif ($seguridad !== 'ssl') {
            throw new RuntimeException('La seguridad SMTP debe ser tls o ssl.');
        }

        smtpComando($socket, 'AUTH LOGIN', [334]);
        smtpComando($socket, base64_encode((string)$config['username']), [334]);
        smtpComando($socket, base64_encode((string)$config['password']), [235]);
        smtpComando($socket, 'MAIL FROM:<' . $config['from_address'] . '>', [250]);
        smtpComando($socket, 'RCPT TO:<' . $destino . '>', [250, 251]);
        smtpComando($socket, 'DATA', [354]);

        $limpio = static fn(string $valor): string => str_replace(["\r", "\n"], '', $valor);
        $limpioHtml = str_replace(["\r\n", "\r"], "\n", $html);
        $limpioHtml = str_replace("\n", "\r\n", $limpioHtml);
        $cuerpo = chunk_split(base64_encode($limpioHtml), 76, "\r\n");
        $nombreRemitente = '=?UTF-8?B?' . base64_encode($limpio((string)$config['from_name'])) . '?=';
        $asuntoCodificado = '=?UTF-8?B?' . base64_encode($limpio($asunto)) . '?=';
        $mensaje = implode("\r\n", [
            'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
            'From: ' . $nombreRemitente . ' <' . $config['from_address'] . '>',
            'To: <' . $destino . '>',
            'Subject: ' . $asuntoCodificado,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            '',
            $cuerpo,
        ]);
        $mensaje = preg_replace('/^\./m', '..', $mensaje) ?? $mensaje;
        fwrite($socket, $mensaje . "\r\n.\r\n");
        smtpRespuesta($socket, [250]);
        smtpComando($socket, 'QUIT', [221]);
    } finally {
        fclose($socket);
    }
}
