<?php

class Pricing
{
    public static function seasons(): array
    {
        return db()->query('SELECT * FROM rate_seasons ORDER BY sort_order, id')->fetchAll();
    }

    public static function nextSortOrder(): int
    {
        return (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM rate_seasons')->fetchColumn() + 10;
    }

    public static function moveSeason(int $id, string $direction): bool
    {
        $seasons = self::seasons();
        $index = null;
        foreach ($seasons as $i => $season) {
            if ((int) $season['id'] === $id) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return false;
        }
        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if (!isset($seasons[$swap])) {
            return false;
        }
        $item = $seasons[$index];
        array_splice($seasons, $index, 1);
        array_splice($seasons, $swap, 0, [$item]);
        $stmt = db()->prepare('UPDATE rate_seasons SET sort_order = ? WHERE id = ?');
        foreach ($seasons as $i => $season) {
            $stmt->execute([($i + 1) * 10, (int) $season['id']]);
        }
        return true;
    }

    public static function discounts(): array
    {
        return db()->query('SELECT * FROM discounts ORDER BY min_nights, id')->fetchAll();
    }

    public static function discountPercentForNights(int $nights): float
    {
        $percent = 0.0;
        foreach (self::discounts() as $d) {
            if ($nights >= (int) $d['min_nights'] && $nights <= (int) $d['max_nights']) {
                $percent = (float) $d['percent'];
            }
        }
        return $percent;
    }

    public static function seasonForDate(DateTimeImmutable $date, array $seasons): ?array
    {
        $year = (int) $date->format('Y');
        $md = ((int) $date->format('n') * 100) + (int) $date->format('j');

        $yearSpecific = [];
        $recurring = [];
        foreach ($seasons as $season) {
            if ($season['year'] !== null && (int) $season['year'] === $year) {
                $yearSpecific[] = $season;
            } elseif ($season['year'] === null || $season['year'] === '') {
                $recurring[] = $season;
            }
        }
        foreach ([$yearSpecific, $recurring] as $pool) {
            $matches = [];
            foreach ($pool as $season) {
                if (self::dateInSeason($md, $season)) {
                    $matches[] = $season;
                }
            }
            if ($matches) {
                usort($matches, static fn (array $a, array $b): int => self::seasonLength($a) <=> self::seasonLength($b));
                return $matches[0];
            }
        }
        return null;
    }

    private static function dateInSeason(int $md, array $season): bool
    {
        $start = ((int) $season['start_month'] * 100) + (int) $season['start_day'];
        $end = ((int) $season['end_month'] * 100) + (int) $season['end_day'];
        if ($start <= $end) {
            return $md >= $start && $md <= $end;
        }
        // Wrap around year (e.g. 20 Dec – 6 Jan)
        return $md >= $start || $md <= $end;
    }

    private static function seasonLength(array $season): int
    {
        $start = DateTimeImmutable::createFromFormat('!n-j', (int) $season['start_month'] . '-' . (int) $season['start_day']);
        $end = DateTimeImmutable::createFromFormat('!n-j', (int) $season['end_month'] . '-' . (int) $season['end_day']);
        if (!$start || !$end) {
            return 999;
        }
        if ($end < $start) {
            $end = $end->modify('+1 year');
        }
        return (int) $start->diff($end)->days + 1;
    }

    /** @return array{date:string,rate:float,label:string}[] */
    public static function nightsBreakdown(string $checkIn, string $checkOut): array
    {
        $in = DateTimeImmutable::createFromFormat('Y-m-d', $checkIn);
        $out = DateTimeImmutable::createFromFormat('Y-m-d', $checkOut);
        if (!$in || !$out || $in->format('Y-m-d') !== $checkIn || $out->format('Y-m-d') !== $checkOut || $out <= $in) {
            return [];
        }
        $seasons = self::seasons();
        $nightly = [];
        $cursor = $in;
        while ($cursor < $out) {
            $season = self::seasonForDate($cursor, $seasons);
            $closed = !$season || (int) $season['is_closed'] === 1;
            $nightly[] = [
                'date' => $cursor->format('Y-m-d'),
                'rate' => $closed ? 0.0 : (float) $season['nightly_rate'],
                'label' => $closed ? 'Fermé / hors grille' : (string) $season['label'],
            ];
            $cursor = $cursor->modify('+1 day');
        }
        return $nightly;
    }

    public static function quote(string $checkIn, string $checkOut, ?int $ignoreBookingId = null): array
    {
        $in = DateTimeImmutable::createFromFormat('Y-m-d', $checkIn);
        $out = DateTimeImmutable::createFromFormat('Y-m-d', $checkOut);
        $min = (int) setting('min_nights', 6);
        $max = (int) setting('max_nights', 21);

        if (!$in || !$out || $in->format('Y-m-d') !== $checkIn || $out->format('Y-m-d') !== $checkOut) {
            return ['ok' => false, 'error' => 'invalid_dates'];
        }
        if ($out <= $in) {
            return ['ok' => false, 'error' => 'checkout_before_checkin'];
        }

        $nights = (int) $in->diff($out)->days;
        if ($nights < $min) {
            return ['ok' => false, 'error' => 'min_nights', 'min' => $min, 'nights' => $nights];
        }
        if ($nights > $max) {
            return ['ok' => false, 'error' => 'max_nights', 'max' => $max, 'nights' => $nights];
        }

        $availability = Availability::rangeStatus($checkIn, $checkOut, $ignoreBookingId);
        if ($availability['blocked']) {
            return ['ok' => false, 'error' => 'unavailable', 'blocked_dates' => $availability['blocked_dates']];
        }

        $seasons = self::seasons();
        $nightly = [];
        $closed = [];
        $cursor = $in;
        $subtotal = 0.0;

        for ($i = 0; $i < $nights; $i++) {
            $season = self::seasonForDate($cursor, $seasons);
            if (!$season || (int) $season['is_closed'] === 1) {
                $closed[] = $cursor->format('Y-m-d');
            } else {
                $rate = (float) $season['nightly_rate'];
                $nightly[] = [
                    'date' => $cursor->format('Y-m-d'),
                    'rate' => $rate,
                    'label' => $season['label'],
                ];
                $subtotal += $rate;
            }
            $cursor = $cursor->modify('+1 day');
        }

        if ($closed) {
            return ['ok' => false, 'error' => 'closed', 'closed_dates' => $closed];
        }

        $discountPercent = self::discountPercentForNights($nights);
        $discountAmount = round($subtotal * $discountPercent / 100, 2);
        $rental = round($subtotal - $discountAmount, 2);
        $cleaning = (float) setting('cleaning_fee', 100);
        $caution = (float) setting('caution', 300);
        $total = round($rental + $cleaning, 2);
        $depositPercent = (float) setting('deposit_percent', 15);
        $deposit = round($rental * $depositPercent / 100, 2);

        return [
            'ok' => true,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'nights' => $nights,
            'nights_detail' => $nightly,
            'rental_subtotal' => $subtotal,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'rental' => $rental,
            'cleaning_fee' => $cleaning,
            'total' => $total,
            'deposit_percent' => $depositPercent,
            'deposit_amount' => $deposit,
            'balance' => round($total - $deposit, 2),
            'caution' => $caution,
        ];
    }
}
