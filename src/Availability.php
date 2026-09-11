<?php

class Availability
{
    public const NIGHT_STATUSES = ['booked', 'pending', 'blocked', 'unavailable'];

    /**
     * Occupied nights: check-in inclusive, check-out exclusive.
     */
    public static function occupiedMap(string $from, string $to, ?int $ignoreId = null): array
    {
        $stmt = db()->prepare(
            "SELECT id, check_in, check_out, status, guest_name FROM bookings
             WHERE status IN ('confirmed', 'pending', 'blocked')
               AND check_in < ? AND check_out > ?" . ($ignoreId ? ' AND id != ?' : '')
        );
        $params = [$to, $from];
        if ($ignoreId) {
            $params[] = $ignoreId;
        }
        $stmt->execute($params);
        $map = [];
        $rank = ['booked' => 3, 'blocked' => 3, 'pending' => 2];
        foreach ($stmt->fetchAll() as $row) {
            $status = match ($row['status']) {
                'confirmed' => 'booked',
                'blocked' => 'blocked',
                'pending' => 'pending',
                default => null,
            };
            if ($status === null) {
                continue;
            }
            $cursor = new DateTimeImmutable($row['check_in']);
            $end = new DateTimeImmutable($row['check_out']);
            while ($cursor < $end) {
                $key = $cursor->format('Y-m-d');
                $prev = $map[$key] ?? null;
                if ($prev === null || ($rank[$status] ?? 0) >= ($rank[$prev] ?? 0)) {
                    $map[$key] = $status;
                }
                $cursor = $cursor->modify('+1 day');
            }
        }

        $seasons = Pricing::seasons();
        $start = new DateTimeImmutable($from);
        $end = new DateTimeImmutable($to);
        $today = new DateTimeImmutable('today', new DateTimeZone('Europe/Brussels'));
        $cursor = $start;
        while ($cursor < $end) {
            $key = $cursor->format('Y-m-d');
            $held = isset($map[$key]) && in_array($map[$key], ['booked', 'pending', 'blocked'], true);
            if ($cursor < $today) {
                $map[$key] = 'unavailable';
            } else {
                $season = Pricing::seasonForDate($cursor, $seasons);
                if (!$season || (int) $season['is_closed'] === 1) {
                    if (!$held) {
                        $map[$key] = 'unavailable';
                    }
                } elseif (!isset($map[$key])) {
                    $map[$key] = 'available';
                }
            }
            $cursor = $cursor->modify('+1 day');
        }
        return $map;
    }

    /** Jour de départ : check-out exclusif, à afficher à moitié occupé. */
    public static function turnoverMap(string $from, string $to): array
    {
        $stmt = db()->prepare(
            "SELECT check_out, status FROM bookings
             WHERE status IN ('confirmed', 'pending')
               AND check_out >= ? AND check_out < ?"
        );
        $stmt->execute([$from, $to]);
        $rank = ['booked' => 3, 'pending' => 2];
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $status = $row['status'] === 'confirmed' ? 'booked' : 'pending';
            $key = (string) $row['check_out'];
            $prev = $map[$key] ?? null;
            if ($prev === null || ($rank[$status] ?? 0) >= ($rank[$prev] ?? 0)) {
                $map[$key] = $status;
            }
        }
        return $map;
    }

    public static function isNightBlocked(?string $status): bool
    {
        return in_array($status, self::NIGHT_STATUSES, true);
    }

    public static function rangeStatus(string $checkIn, string $checkOut, ?int $ignoreId = null): array
    {
        $map = self::occupiedMap($checkIn, $checkOut, $ignoreId);
        $blocked = [];
        $cursor = new DateTimeImmutable($checkIn);
        $end = new DateTimeImmutable($checkOut);
        while ($cursor < $end) {
            $key = $cursor->format('Y-m-d');
            $status = $map[$key] ?? 'available';
            if (self::isNightBlocked($status)) {
                $blocked[] = $key;
            }
            $cursor = $cursor->modify('+1 day');
        }
        return [
            'blocked' => $blocked !== [],
            'blocked_dates' => $blocked,
        ];
    }

    public static function calendar(string $monthStart, int $months = 2, bool $withOccupancy = false): array
    {
        $start = DateTimeImmutable::createFromFormat('Y-m-d', $monthStart . '-01')
            ?: new DateTimeImmutable('first day of this month');
        $start = $start->modify('first day of this month');
        $end = $start->modify('+' . $months . ' months');
        $map = self::occupiedMap($start->format('Y-m-d'), $end->format('Y-m-d'));
        $turnovers = self::turnoverMap($start->format('Y-m-d'), $end->format('Y-m-d'));
        $occ = $withOccupancy ? self::occupancyIndex($start->format('Y-m-d'), $end->format('Y-m-d')) : [];

        $result = [];
        $cursor = $start;
        for ($m = 0; $m < $months; $m++) {
            $daysInMonth = (int) $cursor->format('t');
            $days = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = $cursor->format('Y-m') . '-' . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
                $info = $occ[$date] ?? null;
                $day = [
                    'date' => $date,
                    'day' => $d,
                    'status' => $map[$date] ?? 'available',
                ];
                $turnover = $turnovers[$date] ?? null;
                if ($turnover && !in_array($day['status'], ['booked', 'pending', 'blocked'], true)) {
                    $day['turnover'] = $turnover;
                }
                if ($withOccupancy) {
                    $day['booking_id'] = $info['id'] ?? null;
                    $day['guest'] = $info['guest'] ?? '';
                }
                $days[] = $day;
            }
            $result[] = [
                'year' => (int) $cursor->format('Y'),
                'month' => (int) $cursor->format('n'),
                'label' => $cursor->format('Y-m'),
                'start_weekday' => (int) $cursor->format('N'), // 1 = Monday
                'days' => $days,
            ];
            $cursor = $cursor->modify('+1 month');
        }
        return $result;
    }

    public static function occupancyIndex(string $from, string $to): array
    {
        $stmt = db()->prepare(
            "SELECT id, check_in, check_out, status, guest_name FROM bookings
             WHERE status IN ('confirmed', 'pending', 'blocked')
               AND check_in < ? AND check_out > ?
             ORDER BY CASE status WHEN 'confirmed' THEN 3 WHEN 'blocked' THEN 2 ELSE 1 END"
        );
        $stmt->execute([$to, $from]);
        $map = [];
        $rank = ['booked' => 3, 'blocked' => 3, 'pending' => 2];
        foreach ($stmt->fetchAll() as $row) {
            $status = match ($row['status']) {
                'confirmed' => 'booked',
                'blocked' => 'blocked',
                'pending' => 'pending',
                default => null,
            };
            if ($status === null) {
                continue;
            }
            $cursor = new DateTimeImmutable($row['check_in']);
            $end = new DateTimeImmutable($row['check_out']);
            while ($cursor < $end) {
                $key = $cursor->format('Y-m-d');
                $prev = $map[$key]['status'] ?? null;
                if ($prev === null || ($rank[$status] ?? 0) >= ($rank[$prev] ?? 0)) {
                    $map[$key] = [
                        'id' => (int) $row['id'],
                        'status' => $status,
                        'guest' => (string) $row['guest_name'],
                    ];
                }
                $cursor = $cursor->modify('+1 day');
            }
        }
        return $map;
    }
}
