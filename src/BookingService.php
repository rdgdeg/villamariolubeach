<?php

class BookingService
{
    public static function createRequest(array $input): array
    {
        $checkIn = trim((string) ($input['check_in'] ?? ''));
        $checkOut = trim((string) ($input['check_out'] ?? ''));
        $quote = Pricing::quote($checkIn, $checkOut);
        if (empty($quote['ok'])) {
            return $quote;
        }

        $first = trim((string) ($input['guest_first_name'] ?? ''));
        $last = trim((string) ($input['guest_last_name'] ?? ''));
        $name = trim($first . ' ' . $last);
        if ($name === '') {
            $name = trim((string) ($input['guest_name'] ?? ''));
        }
        $email = trim((string) ($input['guest_email'] ?? ''));
        $phone = trim((string) ($input['guest_phone'] ?? ''));
        $country = trim((string) ($input['guest_country'] ?? ''));
        $occupants = trim((string) ($input['occupants'] ?? ''));
        $message = trim((string) ($input['guest_message'] ?? ''));
        $adults = max(1, min(6, (int) ($input['adults'] ?? 2)));
        $children = max(0, min(4, (int) ($input['children'] ?? 0)));
        $allowedExtras = ['baby_cot', 'high_chair', 'beach_towels', 'late_arrival'];
        $extrasIn = $input['extras'] ?? [];
        if (!is_array($extrasIn)) {
            $extrasIn = $extrasIn === '' ? [] : [$extrasIn];
        }
        $extras = array_values(array_intersect($extrasIn, $allowedExtras));
        $terms = $input['accept_terms'] ?? '';
        if ($adults + $children > 6) {
            return ['ok' => false, 'error' => 'capacity'];
        }
        if ($first === '' || $last === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $country === '') {
            return ['ok' => false, 'error' => 'invalid_guest'];
        }
        if ($terms !== '1' && $terms !== 'on' && $terms !== true) {
            return ['ok' => false, 'error' => 'terms'];
        }

        $now = date('Y-m-d H:i:s');
        $stmt = db()->prepare(
            'INSERT INTO bookings (
                status, check_in, check_out, nights, adults, children,
                guest_name, guest_first_name, guest_last_name, guest_email, guest_phone,
                guest_country, occupants, extras, guest_message, guest_lang,
                rental_subtotal, discount_percent, discount_amount, cleaning_fee,
                total, deposit_amount, deposit_percent, caution, admin_notes, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?
            )'
        );
        $stmt->execute([
            'pending',
            $quote['check_in'],
            $quote['check_out'],
            $quote['nights'],
            $adults,
            $children,
            $name,
            $first,
            $last,
            $email,
            $phone,
            $country,
            $occupants,
            json_encode($extras, JSON_UNESCAPED_UNICODE),
            $message,
            current_lang(),
            $quote['rental_subtotal'],
            $quote['discount_percent'],
            $quote['discount_amount'],
            $quote['cleaning_fee'],
            $quote['total'],
            $quote['deposit_amount'],
            $quote['deposit_percent'],
            $quote['caution'],
            '',
            $now,
            $now,
        ]);

        $id = (int) db()->lastInsertId();
        $booking = self::find($id);
        if ($booking) {
            BookingEvents::log($id, 'request');
            Mailer::sendBooking($booking, 'request');
            Mailer::notifyHostNewRequest($booking);
        }

        return [
            'ok' => true,
            'id' => $id,
            'quote' => $quote,
        ];
    }

    public static function setStatus(int $id, string $status, string $notes = ''): bool
    {
        $allowed = ['pending', 'confirmed', 'refused', 'cancelled', 'blocked'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $booking = self::find($id);
        if (!$booking) {
            return false;
        }
        if ($status === 'confirmed') {
            $stmt = db()->prepare(
                "SELECT id FROM bookings
                 WHERE id != ? AND status IN ('confirmed','blocked')
                   AND check_in < ? AND check_out > ?"
            );
            $stmt->execute([$id, $booking['check_out'], $booking['check_in']]);
            if ($stmt->fetch()) {
                return false;
            }
            $refuse = db()->prepare(
                "UPDATE bookings SET status = 'refused', admin_notes = ?, updated_at = ?
                 WHERE id != ? AND status = 'pending' AND check_in < ? AND check_out > ?"
            );
            $refuse->execute([
                'Refusée automatiquement : ces dates ont été confirmées pour une autre demande.',
                date('Y-m-d H:i:s'),
                $id,
                $booking['check_out'],
                $booking['check_in'],
            ]);
        }
        $stmt = db()->prepare('UPDATE bookings SET status = ?, admin_notes = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$status, $notes !== '' ? $notes : $booking['admin_notes'], date('Y-m-d H:i:s'), $id]);
        $fresh = self::find($id);
        if ($fresh && $status !== $booking['status']) {
            BookingEvents::log($id, $status);
            if ($status === 'confirmed') {
                Mailer::sendBooking($fresh, 'confirmed');
            } elseif ($status === 'refused') {
                Mailer::sendBooking($fresh, 'refused');
            } elseif ($status === 'cancelled') {
                Mailer::sendBooking($fresh, 'cancelled');
            }
        }
        return true;
    }

    public static function update(int $id, array $input): array
    {
        $booking = self::find($id);
        if (!$booking || $booking['status'] === 'blocked') {
            return ['ok' => false, 'error' => 'not_found'];
        }
        $checkIn = trim((string) ($input['check_in'] ?? $booking['check_in']));
        $checkOut = trim((string) ($input['check_out'] ?? $booking['check_out']));
        $quote = Pricing::quote($checkIn, $checkOut, $id);
        if (empty($quote['ok'])) {
            return $quote;
        }
        $first = trim((string) ($input['guest_first_name'] ?? $booking['guest_first_name']));
        $last = trim((string) ($input['guest_last_name'] ?? $booking['guest_last_name']));
        $name = trim($first . ' ' . $last);
        if ($name === '') {
            $name = trim((string) ($input['guest_name'] ?? $booking['guest_name']));
        }
        $email = trim((string) ($input['guest_email'] ?? $booking['guest_email']));
        $phone = trim((string) ($input['guest_phone'] ?? $booking['guest_phone']));
        $country = trim((string) ($input['guest_country'] ?? $booking['guest_country']));
        $occupants = trim((string) ($input['occupants'] ?? $booking['occupants']));
        $message = trim((string) ($input['guest_message'] ?? $booking['guest_message']));
        $adults = max(1, min(6, (int) ($input['adults'] ?? $booking['adults'])));
        $children = max(0, min(4, (int) ($input['children'] ?? $booking['children'])));
        $notes = (string) ($input['admin_notes'] ?? $booking['admin_notes']);
        $status = (string) ($input['status'] ?? $booking['status']);
        $depositPaid = !empty($input['deposit_paid']) ? 1 : 0;
        $balancePaid = !empty($input['balance_paid']) ? 1 : 0;
        $deposit = self::resolveDeposit((float) $quote['total'], $input, $quote);
        if ($adults + $children > 6) {
            return ['ok' => false, 'error' => 'capacity'];
        }
        $stmt = db()->prepare(
            'UPDATE bookings SET
                check_in=?, check_out=?, nights=?, adults=?, children=?,
                guest_name=?, guest_first_name=?, guest_last_name=?, guest_email=?, guest_phone=?,
                guest_country=?, occupants=?, guest_message=?,
                rental_subtotal=?, discount_percent=?, discount_amount=?, cleaning_fee=?,
                total=?, deposit_amount=?, deposit_percent=?, caution=?, admin_notes=?,
                deposit_paid=?, balance_paid=?, updated_at=?
             WHERE id=?'
        );
        $stmt->execute([
            $quote['check_in'], $quote['check_out'], $quote['nights'], $adults, $children,
            $name, $first, $last, $email, $phone,
            $country, $occupants, $message,
            $quote['rental_subtotal'], $quote['discount_percent'], $quote['discount_amount'], $quote['cleaning_fee'],
            $quote['total'], $deposit['deposit_amount'], $deposit['deposit_percent'], $quote['caution'], $notes,
            $depositPaid, $balancePaid, date('Y-m-d H:i:s'),
            $id,
        ]);
        if ($status !== $booking['status'] && !self::setStatus($id, $status, $notes)) {
            return ['ok' => false, 'error' => 'unavailable'];
        }
        $fresh = self::find($id);
        if ($fresh) {
            if ($depositPaid && empty($booking['deposit_paid'])) {
                BookingEvents::log($id, 'deposit_paid');
                Mailer::sendBooking($fresh, 'deposit_paid');
            }
            if (!$depositPaid && !empty($booking['deposit_paid'])) {
                BookingEvents::log($id, 'deposit_unpaid');
            }
            if ($balancePaid && empty($booking['balance_paid'])) {
                BookingEvents::log($id, 'balance_paid');
                Mailer::sendBooking($fresh, 'balance_paid');
            }
            if (!$balancePaid && !empty($booking['balance_paid'])) {
                BookingEvents::log($id, 'balance_unpaid');
            }
        }
        return ['ok' => true];
    }

    public static function delete(int $id): bool
    {
        $booking = self::find($id);
        if (!$booking) {
            return false;
        }
        $stmt = db()->prepare('DELETE FROM bookings WHERE id = ?');
        $stmt->execute([$id]);
        return true;
    }

    public static function applyDepositAmount(int $id, float $amount): bool
    {
        $booking = self::find($id);
        if (!$booking) {
            return false;
        }
        $rental = round((float) $booking['rental_subtotal'] - (float) $booking['discount_amount'], 2);
        $amount = round(max(0, $amount), 2);
        $percent = $rental > 0 ? round(($amount / $rental) * 100, 2) : 0.0;
        db()->prepare(
            'UPDATE bookings SET deposit_amount = ?, deposit_percent = ?, updated_at = ? WHERE id = ?'
        )->execute([$amount, $percent, date('Y-m-d H:i:s'), $id]);
        return true;
    }

    public static function markReminded(int $id, string $kind): bool
    {
        $col = str_starts_with($kind, 'balance') ? 'balance_reminded_at' : 'deposit_reminded_at';
        $stmt = db()->prepare("UPDATE bookings SET $col = ?, updated_at = ? WHERE id = ?");
        $stmt->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id]);
        return true;
    }

    /** Le % s’applique à la location (après réduction, hors nettoyage). */
    /** @return array{deposit_percent:float,deposit_amount:float} */
    public static function resolveDeposit(float $total, array $input, array $quote): array
    {
        $rental = (float) ($quote['rental'] ?? 0);
        if ($rental <= 0) {
            $rental = round($total - (float) ($quote['cleaning_fee'] ?? 0), 2);
        }
        $amountRaw = trim((string) ($input['deposit_amount'] ?? ''));
        $percentRaw = trim((string) ($input['deposit_percent'] ?? ''));
        $amount = $amountRaw !== '' ? (float) str_replace(',', '.', $amountRaw) : 0.0;
        $percent = $percentRaw !== '' ? (float) str_replace(',', '.', $percentRaw) : 0.0;
        $source = (string) ($input['deposit_source'] ?? '');
        if ($source === 'percent' && $percent > 0) {
            return [
                'deposit_percent' => $percent,
                'deposit_amount' => round($rental * $percent / 100, 2),
            ];
        }
        if ($source === 'amount' && $amount > 0) {
            return [
                'deposit_percent' => $rental > 0 ? round(($amount / $rental) * 100, 2) : 0.0,
                'deposit_amount' => round($amount, 2),
            ];
        }
        if ($percent > 0) {
            $fromPercent = round($rental * $percent / 100, 2);
            if ($amount <= 0 || abs($amount - $fromPercent) < 0.05) {
                return [
                    'deposit_percent' => $percent,
                    'deposit_amount' => $fromPercent,
                ];
            }
        }
        if ($amount > 0) {
            return [
                'deposit_percent' => $rental > 0 ? round(($amount / $rental) * 100, 2) : 0.0,
                'deposit_amount' => round($amount, 2),
            ];
        }
        return [
            'deposit_percent' => (float) ($quote['deposit_percent'] ?? setting('deposit_percent', 15)),
            'deposit_amount' => round((float) ($quote['deposit_amount'] ?? 0), 2),
        ];
    }

    public static function stats(): array
    {
        $confirmed = db()->query(
            "SELECT
                COUNT(*) AS stays,
                COALESCE(SUM(nights), 0) AS nights,
                COALESCE(SUM(total), 0) AS total,
                COALESCE(SUM(deposit_amount), 0) AS deposits_due,
                COALESCE(SUM(CASE WHEN deposit_paid = 1 THEN deposit_amount ELSE 0 END), 0) AS deposits_paid,
                COALESCE(SUM(CASE WHEN balance_paid = 1 THEN (total - deposit_amount) ELSE 0 END), 0) AS balances_paid
             FROM bookings WHERE status = 'confirmed'"
        )->fetch();
        $pending = db()->query(
            "SELECT COUNT(*) AS stays, COALESCE(SUM(total), 0) AS total
             FROM bookings WHERE status = 'pending'"
        )->fetch();
        $nights = (int) ($confirmed['nights'] ?? 0);
        return [
            'confirmed_stays' => (int) ($confirmed['stays'] ?? 0),
            'weeks' => round($nights / 7, 1),
            'nights' => $nights,
            'total' => (float) ($confirmed['total'] ?? 0),
            'deposits_due' => (float) ($confirmed['deposits_due'] ?? 0),
            'deposits_paid' => (float) ($confirmed['deposits_paid'] ?? 0),
            'balances_paid' => (float) ($confirmed['balances_paid'] ?? 0),
            'pending_stays' => (int) ($pending['stays'] ?? 0),
            'pending_total' => (float) ($pending['total'] ?? 0),
        ];
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM bookings WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function all(?string $status = null): array
    {
        if ($status) {
            $stmt = db()->prepare('SELECT * FROM bookings WHERE status = ? ORDER BY created_at DESC');
            $stmt->execute([$status]);
            return $stmt->fetchAll();
        }
        return db()->query("SELECT * FROM bookings WHERE status != 'blocked' ORDER BY created_at DESC")->fetchAll();
    }

    public static function blockDates(string $checkIn, string $checkOut, string $note = ''): array
    {
        if ($checkOut <= $checkIn) {
            return ['ok' => false, 'error' => 'invalid_dates'];
        }
        $nights = (int) (new DateTimeImmutable($checkIn))->diff(new DateTimeImmutable($checkOut))->days;
        $now = date('Y-m-d H:i:s');
        $stmt = db()->prepare(
            'INSERT INTO bookings (
                status, check_in, check_out, nights, adults, children,
                guest_name, guest_email, guest_phone, guest_message, guest_lang,
                rental_subtotal, discount_percent, discount_amount, cleaning_fee,
                total, deposit_amount, caution, admin_notes, created_at, updated_at
            ) VALUES (?,?,?,?,0,0,?,?,?,?,?,0,0,0,0,0,0,0,?,?,?)'
        );
        $stmt->execute([
            'blocked', $checkIn, $checkOut, $nights,
            'Bloqué', '', '', '', 'fr',
            $note, $now, $now,
        ]);
        return ['ok' => true, 'id' => (int) db()->lastInsertId()];
    }
}
