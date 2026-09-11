<?php

class BookingEvents
{
    public static function labels(): array
    {
        return [
            'request' => 'Demande reçue',
            'confirmed' => 'Confirmation + acompte envoyés',
            'refused' => 'Demande refusée',
            'cancelled' => 'Réservation annulée',
            'deposit_reminder_1' => 'Relance acompte 1',
            'deposit_reminder_2' => 'Relance acompte 2',
            'balance' => 'Demande de solde envoyée',
            'balance_reminder' => 'Relance solde',
            'deposit_paid' => 'Acompte reçu',
            'balance_paid' => 'Solde reçu',
            'deposit_unpaid' => 'Acompte marqué non perçu',
            'balance_unpaid' => 'Solde marqué non perçu',
            'prearrival' => 'Mail d’arrivée envoyé',
            'thanks' => 'Mail merci / avis envoyé',
            'host' => 'Notification propriétaire',
        ];
    }

    public static function log(int $bookingId, string $type, ?string $at = null): void
    {
        if ($bookingId < 1 || $type === '') {
            return;
        }
        try {
            db()->prepare(
                'INSERT INTO booking_events (booking_id, event_type, created_at) VALUES (?, ?, ?)'
            )->execute([$bookingId, $type, $at ?: date('Y-m-d H:i:s')]);
        } catch (Throwable $e) {
            // History must never break the request.
        }
    }

    public static function forBooking(int $bookingId): array
    {
        $stmt = db()->prepare(
            'SELECT event_type, created_at FROM booking_events WHERE booking_id = ? ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute([$bookingId]);
        $labels = self::labels();
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $type = (string) $row['event_type'];
            $out[] = [
                'type' => $type,
                'label' => $labels[$type] ?? $type,
                'at' => (string) $row['created_at'],
            ];
        }
        return $out;
    }

    public static function backfill(array $booking): void
    {
        $id = (int) ($booking['id'] ?? 0);
        if ($id < 1) {
            return;
        }
        $stmt = db()->prepare('SELECT COUNT(*) FROM booking_events WHERE booking_id = ?');
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        self::log($id, 'request', (string) $booking['created_at']);

        $mails = db()->prepare('SELECT kind, created_at FROM email_log WHERE booking_id = ? ORDER BY created_at ASC');
        $mails->execute([$id]);
        foreach ($mails->fetchAll() as $row) {
            $kind = (string) $row['kind'];
            if ($kind === '' || $kind === 'host') {
                continue;
            }
            if ($kind === 'request') {
                continue;
            }
            self::log($id, $kind, (string) $row['created_at']);
        }

        $status = (string) ($booking['status'] ?? '');
        if (in_array($status, ['confirmed', 'refused', 'cancelled'], true)) {
            $has = false;
            foreach (self::forBooking($id) as $ev) {
                if ($ev['type'] === $status) {
                    $has = true;
                    break;
                }
            }
            if (!$has) {
                self::log($id, $status, (string) ($booking['updated_at'] ?? $booking['created_at']));
            }
        }
        if (!empty($booking['deposit_paid'])) {
            self::log($id, 'deposit_paid', (string) ($booking['updated_at'] ?? ''));
        }
        if (!empty($booking['balance_paid'])) {
            self::log($id, 'balance_paid', (string) ($booking['updated_at'] ?? ''));
        }
    }

    /** @return array{key:string,label:string,state:string}[] */
    public static function stages(array $booking): array
    {
        $status = (string) ($booking['status'] ?? '');
        $deposit = !empty($booking['deposit_paid']);
        $balance = !empty($booking['balance_paid']);
        $arrival = !empty($booking['prearrival_sent_at'])
            || ($status === 'confirmed' && (string) $booking['check_in'] <= date('Y-m-d'));
        $done = !empty($booking['thanks_sent_at'])
            || ($status === 'confirmed' && (string) $booking['check_out'] <= date('Y-m-d'));

        $current = 'request';
        if ($status === 'refused' || $status === 'cancelled') {
            $current = $status;
        } elseif ($status === 'confirmed') {
            $current = 'confirmed';
            if ($deposit) {
                $current = 'deposit';
            }
            if ($balance) {
                $current = 'balance';
            }
            if ($arrival && $balance) {
                $current = 'arrival';
            }
            if ($done && $balance) {
                $current = 'done';
            }
        }

        $keys = ['request', 'confirmed', 'deposit', 'balance', 'arrival', 'done'];
        $labels = [
            'request' => 'Demande',
            'confirmed' => 'Confirmation envoyée',
            'deposit' => 'Acompte reçu',
            'balance' => 'Solde reçu',
            'arrival' => 'Arrivée',
            'done' => 'Terminé',
        ];
        $order = array_flip($keys);
        $curIdx = $order[$current] ?? 0;
        $steps = [];
        foreach ($keys as $i => $key) {
            $state = 'todo';
            if ($status === 'refused' || $status === 'cancelled') {
                $state = $key === 'request' ? 'done' : ($key === 'confirmed' ? 'stop' : 'todo');
            } elseif ($i < $curIdx) {
                $state = 'done';
            } elseif ($i === $curIdx) {
                $state = 'current';
            }
            $steps[] = [
                'key' => $key,
                'label' => $labels[$key],
                'state' => $state,
            ];
        }
        return $steps;
    }
}
