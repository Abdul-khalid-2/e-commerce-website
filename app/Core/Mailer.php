<?php
/**
 * app/Core/Mailer.php
 *
 * A small, dependency-free SMTP client — no Composer/PHPMailer, in
 * keeping with the rest of this project. Supports AUTH LOGIN and
 * STARTTLS, which covers Gmail, most shared-hosting SMTP, and Mailtrap
 * (a good option for local testing without a real inbox).
 *
 * send() never throws to the caller and never blocks a request for
 * long on a broken connection — every failure is logged and the method
 * returns false, so a mail outage can never break checkout. Real
 * transactional email at any scale should eventually move to a proper
 * provider (SES, Postmark, etc.) or a queue; this is intentionally
 * just enough for a small business's order confirmations.
 */

declare(strict_types=1);

namespace App\Core;

final class Mailer
{
    private function __construct()
    {
    }

    /**
     * @return bool True if the message was accepted by the SMTP server.
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $socket = null;

        try {
            $socket = self::connect();
            self::expect($socket, 220);

            $localDomain = parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost';
            self::command($socket, "EHLO {$localDomain}", 250, multiline: true);

            if (MAIL_ENCRYPTION === 'tls') {
                self::command($socket, 'STARTTLS', 220);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('STARTTLS negotiation failed');
                }
                self::command($socket, "EHLO {$localDomain}", 250, multiline: true);
            }

            self::command($socket, 'AUTH LOGIN', 334);
            self::command($socket, base64_encode(MAIL_USERNAME), 334);
            self::command($socket, base64_encode(MAIL_PASSWORD), 235);

            self::command($socket, 'MAIL FROM:<' . MAIL_FROM_ADDRESS . '>', 250);
            self::command($socket, "RCPT TO:<{$toEmail}>", 250);
            self::command($socket, 'DATA', 354);

            $headers = self::buildHeaders($toEmail, $toName, $subject);
            $data = $headers . "\r\n" . self::wrapBody($htmlBody) . "\r\n.\r\n";
            fwrite($socket, $data);
            self::expect($socket, 250);

            self::command($socket, 'QUIT', 221);

            return true;
        } catch (\Throwable $e) {
            error_log('[Mailer] Failed to send to ' . $toEmail . ': ' . $e->getMessage());
            return false;
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }

    /**
     * @return resource
     */
    private static function connect()
    {
        $scheme = MAIL_ENCRYPTION === 'ssl' ? 'ssl' : 'tcp';
        $socket = @stream_socket_client(
            "{$scheme}://" . MAIL_HOST . ':' . MAIL_PORT,
            $errno,
            $errstr,
            3,
            STREAM_CLIENT_CONNECT
        );

        if ($socket === false) {
            throw new \RuntimeException("Could not connect to " . MAIL_HOST . ':' . MAIL_PORT . " ({$errstr})");
        }

        stream_set_timeout($socket, 5);

        return $socket;
    }

    /**
     * @param resource $socket
     */
    private static function command($socket, string $line, int $expectedCode, bool $multiline = false): void
    {
        fwrite($socket, $line . "\r\n");
        self::expect($socket, $expectedCode, $multiline);
    }

    /**
     * @param resource $socket
     */
    private static function expect($socket, int $expectedCode, bool $multiline = false): void
    {
        $lastLine = '';
        do {
            $lastLine = fgets($socket, 515);
            if ($lastLine === false) {
                throw new \RuntimeException('Connection closed unexpectedly while reading SMTP response');
            }
        } while ($multiline && isset($lastLine[3]) && $lastLine[3] === '-');

        $code = (int) substr($lastLine, 0, 3);
        if ($code !== $expectedCode) {
            throw new \RuntimeException("Expected SMTP {$expectedCode}, got: " . trim($lastLine));
        }
    }

    private static function buildHeaders(string $toEmail, string $toName, string $subject): string
    {
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedToName = '=?UTF-8?B?' . base64_encode($toName) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode(MAIL_FROM_NAME) . '?=';

        return implode("\r\n", [
            'From: ' . $encodedFromName . ' <' . MAIL_FROM_ADDRESS . '>',
            'To: ' . $encodedToName . ' <' . $toEmail . '>',
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'Date: ' . date('r'),
        ]);
    }

    /**
     * SMTP requires lines no longer than ~1000 chars and any line that
     * starts with a lone "." to be escaped by doubling it.
     */
    private static function wrapBody(string $html): string
    {
        $lines = explode("\n", $html);
        foreach ($lines as &$line) {
            if (isset($line[0]) && $line[0] === '.') {
                $line = '.' . $line;
            }
        }
        return wordwrap(implode("\r\n", $lines), 990, "\r\n", true);
    }
}
