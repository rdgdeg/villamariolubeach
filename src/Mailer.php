<?php

class Mailer
{
    public static function enabled(): bool
    {
        return setting('mail_enabled', '1') !== '0';
    }

    public static function fromAddress(): string
    {
        $from = trim((string) setting('mail_from', ''));
        if (filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return $from;
        }
        $email = trim((string) setting('email', ''));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : 'noreply@localhost';
    }

    public static function send(string $to, string $subject, string $body, ?string $replyTo = null, ?string $kind = null, ?int $bookingId = null): bool
    {
        $to = trim($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || $subject === '' || $body === '') {
            return false;
        }
        if (!self::enabled()) {
            return false;
        }
        $from = self::fromAddress();
        $encoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: Villa Mariolu Beach <' . $from . '>',
            'Reply-To: ' . ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? $replyTo : $from),
            'X-Mailer: VillaMariolu',
        ];
        $ok = @mail($to, $encoded, $body, implode("\r\n", $headers));
        self::log($bookingId, $to, $kind ?: $subject, $ok ? 1 : 0);
        return (bool) $ok;
    }

    public static function sendBooking(array $booking, string $kind): bool
    {
        $msg = StayCopy::email($booking, $kind);
        return self::send(
            (string) $booking['guest_email'],
            $msg['subject'],
            $msg['body'],
            null,
            $kind,
            (int) ($booking['id'] ?? 0)
        );
    }

    public static function notifyHost(string $subject, string $body, ?string $replyTo = null): bool
    {
        $host = trim((string) setting('email', ''));
        if (!filter_var($host, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return self::send($host, $subject, $body, $replyTo);
    }

    public static function notifyHostNewRequest(array $booking): bool
    {
        $host = trim((string) setting('email', ''));
        $msg = StayCopy::email($booking, 'host');
        return self::send(
            $host,
            $msg['subject'],
            $msg['body'],
            (string) $booking['guest_email'],
            'host',
            (int) ($booking['id'] ?? 0)
        );
    }

    private static function log(?int $bookingId, string $to, string $kind, int $ok): void
    {
        try {
            db()->prepare(
                'INSERT INTO email_log (booking_id, recipient, kind, ok, created_at) VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $bookingId ?: null,
                $to,
                substr($kind, 0, 80),
                $ok,
                date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // Logging must never break the request.
        }
    }
}
