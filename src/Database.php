<?php

function db(): Database
{
    return Database::instance();
}

class Database
{
    private static ?self $instance = null;
    private PDO $pdo;
    private string $driver;

    private function __construct()
    {
        $this->driver = config('driver', 'sqlite');
        if ($this->driver === 'mysql') {
            $m = config('mysql');
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $m['host'],
                $m['name'],
                $m['charset'] ?? 'utf8mb4'
            );
            $this->pdo = new PDO($dsn, $m['user'], $m['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } else {
            $path = config('sqlite_path');
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $this->pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
        }
        $this->migrate();
    }

    public static function instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function query(string $sql): PDOStatement
    {
        return $this->pdo->query($sql);
    }

    public function prepare(string $sql): PDOStatement
    {
        return $this->pdo->prepare($sql);
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function upsert(string $table, string $keyColumn, array $data): void
    {
        $columns = array_keys($data);
        if ($this->driver === 'mysql') {
            $placeholders = array_map(fn ($c) => ':' . $c, $columns);
            $updates = [];
            foreach ($columns as $c) {
                if ($c === $keyColumn) {
                    continue;
                }
                $updates[] = "`$c` = VALUES(`$c`)";
            }
            $sql = sprintf(
                'INSERT INTO `%s` (`%s`) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
                $table,
                implode('`,`', $columns),
                implode(',', $placeholders),
                implode(',', $updates)
            );
            $stmt = $this->pdo->prepare($sql);
            foreach ($data as $k => $v) {
                $stmt->bindValue(':' . $k, $v);
            }
            $stmt->execute();
            return;
        }

        // SQLite serverless (Vercel) n’accepte pas toujours INSERT … ON CONFLICT.
        $exists = $this->pdo->prepare("SELECT 1 FROM {$table} WHERE {$keyColumn} = ?");
        $exists->execute([$data[$keyColumn] ?? null]);
        if ($exists->fetch()) {
            $sets = [];
            $params = [];
            foreach ($data as $column => $value) {
                if ($column === $keyColumn) {
                    continue;
                }
                $sets[] = "{$column} = ?";
                $params[] = $value;
            }
            if (!$sets) {
                return;
            }
            $params[] = $data[$keyColumn];
            $this->pdo->prepare(sprintf(
                'UPDATE %s SET %s WHERE %s = ?',
                $table,
                implode(',', $sets),
                $keyColumn
            ))->execute($params);
            return;
        }

        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $this->pdo->prepare(sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(',', $columns),
            $placeholders
        ))->execute(array_values($data));
    }

    private function pk(): string
    {
        return $this->driver === 'mysql'
            ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY'
            : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    }

    private function migrate(): void
    {
        $pk = $this->pk();
        $text = $this->driver === 'mysql' ? 'VARCHAR(191)' : 'TEXT';
        $long = $this->driver === 'mysql' ? 'TEXT' : 'TEXT';
        $dt = $this->driver === 'mysql' ? 'DATETIME' : 'TEXT';
        $dec = $this->driver === 'mysql' ? 'DECIMAL(10,2)' : 'REAL';

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id $pk,
            username $text NOT NULL UNIQUE,
            password_hash $text NOT NULL,
            created_at $dt NOT NULL
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            setting_key $text PRIMARY KEY,
            setting_value $long NOT NULL
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS rate_seasons (
            id $pk,
            label $text NOT NULL,
            year INT NULL,
            start_month INT NOT NULL,
            start_day INT NOT NULL,
            end_month INT NOT NULL,
            end_day INT NOT NULL,
            nightly_rate $dec NOT NULL DEFAULT 0,
            is_closed INT NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS discounts (
            id $pk,
            min_nights INT NOT NULL,
            max_nights INT NOT NULL,
            percent $dec NOT NULL
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
            id $pk,
            status $text NOT NULL,
            check_in $text NOT NULL,
            check_out $text NOT NULL,
            nights INT NOT NULL,
            adults INT NOT NULL DEFAULT 2,
            children INT NOT NULL DEFAULT 0,
            guest_name $text NOT NULL DEFAULT '',
            guest_email $text NOT NULL DEFAULT '',
            guest_phone $text NOT NULL DEFAULT '',
            guest_message $long,
            guest_lang $text NOT NULL DEFAULT 'fr',
            rental_subtotal $dec NOT NULL DEFAULT 0,
            discount_percent $dec NOT NULL DEFAULT 0,
            discount_amount $dec NOT NULL DEFAULT 0,
            cleaning_fee $dec NOT NULL DEFAULT 0,
            total $dec NOT NULL DEFAULT 0,
            deposit_amount $dec NOT NULL DEFAULT 0,
            caution $dec NOT NULL DEFAULT 0,
            admin_notes $long,
            guest_first_name $text NOT NULL DEFAULT '',
            guest_last_name $text NOT NULL DEFAULT '',
            guest_country $text NOT NULL DEFAULT '',
            occupants $long,
            extras $long,
            created_at $dt NOT NULL,
            updated_at $dt NOT NULL
        )");

        $this->addColumn('bookings', 'guest_first_name', "$text NOT NULL DEFAULT ''");
        $this->addColumn('bookings', 'guest_last_name', "$text NOT NULL DEFAULT ''");
        $this->addColumn('bookings', 'guest_country', "$text NOT NULL DEFAULT ''");
        $this->addColumn('bookings', 'occupants', $long);
        $this->addColumn('bookings', 'extras', $long);
        $this->addColumn('bookings', 'deposit_paid', 'INT NOT NULL DEFAULT 0');
        $this->addColumn('bookings', 'balance_paid', 'INT NOT NULL DEFAULT 0');
        $this->addColumn('bookings', 'deposit_reminded_at', $text);
        $this->addColumn('bookings', 'balance_reminded_at', $text);
        $this->addColumn('bookings', 'prearrival_sent_at', $text);
        $this->addColumn('bookings', 'thanks_sent_at', $text);
        $this->addColumn('bookings', 'deposit_percent', "$dec NOT NULL DEFAULT 0");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS email_log (
            id $pk,
            booking_id INT,
            recipient $text NOT NULL DEFAULT '',
            kind $text NOT NULL DEFAULT '',
            ok INT NOT NULL DEFAULT 0,
            created_at $dt NOT NULL
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS booking_events (
            id $pk,
            booking_id INT NOT NULL,
            event_type $text NOT NULL,
            created_at $dt NOT NULL
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS email_templates (
            id $pk,
            kind $text NOT NULL,
            lang $text NOT NULL,
            subject $text NOT NULL DEFAULT '',
            body $long NOT NULL,
            updated_at $dt NOT NULL,
            UNIQUE (kind, lang)
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS messages (
            id $pk,
            name $text NOT NULL,
            email $text NOT NULL,
            phone $text NOT NULL DEFAULT '',
            subject $text NOT NULL DEFAULT '',
            body $long NOT NULL,
            lang $text NOT NULL DEFAULT 'fr',
            created_at $dt NOT NULL
        )");

        $this->seed();
    }

    private function addColumn(string $table, string $column, string $definition): void
    {
        if ($this->driver === 'mysql') {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $stmt->execute([$column]);
            if (!$stmt->fetch()) {
                $this->pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
            return;
        }
        $cols = $this->pdo->query("PRAGMA table_info($table)")->fetchAll();
        $names = array_column($cols, 'name');
        if (!in_array($column, $names, true)) {
            $this->pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
        }
    }

    private function seed(): void
    {
        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count === 0) {
            $stmt = $this->pdo->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, ?)');
            $stmt->execute([
                config('admin_user', 'admin'),
                password_hash((string) config('admin_password', 'VillaMariolu2027'), PASSWORD_DEFAULT),
                date('Y-m-d H:i:s'),
            ]);
        }

        $defaults = [
            'cleaning_fee' => '100',
            'caution' => '300',
            'deposit_percent' => '20',
            'min_nights' => '6',
            'max_nights' => '21',
            'cin' => 'IT090091C2000S5635',
            'iun' => 'S5635',
            'address' => 'Via Patroclo, 07051 Budoni (Tanaunella) — SS',
            'email' => 'VillaMarioluBeach@gmail.com',
            'instagram' => 'https://www.instagram.com/villa_mariolu_beach_budoni/',
            'facebook' => 'https://www.facebook.com/VillaMarioluBeachBudoniSardegna',
            'google_reviews' => 'https://share.google/uUeSIM645i1E55wSX',
            'maps' => 'https://maps.app.goo.gl/LtroS2vSAdnhrzWV7',
            'checkin_time' => '16:00',
            'checkout_time' => '10:00',
            'shardana_maps' => 'https://www.google.be/maps/place/Restaurant+Shardana/@40.6975405,9.7251881,15z',
            'bank_name' => 'Villa Mariolu Beach – Owner',
            'iban' => 'BE81 9501 9418 3524',
            'bic' => 'CTBKBEBX',
            'payment_ref' => 'Holiday reservation in Sardinia',
            'mail_enabled' => '1',
            'mail_from' => '',
            'mail_deposit_remind_days_1' => '3',
            'mail_deposit_remind_days_2' => '10',
            'mail_balance_days_1' => '60',
            'mail_balance_days_2' => '30',
        ];
        foreach ($defaults as $k => $v) {
            $exists = $this->pdo->prepare('SELECT 1 FROM settings WHERE setting_key = ?');
            $exists->execute([$k]);
            if (!$exists->fetch()) {
                $this->pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)')
                    ->execute([$k, $v]);
            }
        }

        $this->ensureOwnerRates();
        $this->ensureFivePercentDiscount();
        $this->pdo->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ? AND setting_value = ?')
            ->execute(['20', 'deposit_percent', '10']);
    }

    private static function ownerRateRows(): array
    {
        return [
            ['Janvier', 1, 1, 1, 31, 350, 0, 10],
            ['Février / Mars', 2, 1, 3, 31, 0, 1, 20],
            ['Avril', 4, 1, 4, 30, 290, 0, 30],
            ['Mai', 5, 1, 5, 31, 290, 0, 40],
            ['Juin', 6, 1, 6, 30, 350, 0, 50],
            ['Juillet', 7, 1, 7, 31, 400, 0, 60],
            ['Août', 8, 1, 8, 31, 400, 0, 70],
            ['Septembre', 9, 1, 9, 30, 350, 0, 80],
            ['Octobre', 10, 1, 10, 31, 290, 0, 90],
            ['Novembre', 11, 1, 11, 30, 250, 0, 100],
            ['Décembre', 12, 1, 12, 31, 350, 0, 110],
        ];
    }

    private function insertOwnerDiscounts(): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO discounts (min_nights, max_nights, percent) VALUES (?, ?, ?)');
        $stmt->execute([6, 9, 5]);
        $stmt->execute([10, 13, 10]);
        $stmt->execute([14, 21, 20]);
    }

    private function ensureFivePercentDiscount(): void
    {
        $coversSix = (int) $this->pdo->query(
            'SELECT COUNT(*) FROM discounts WHERE min_nights <= 6 AND max_nights >= 6'
        )->fetchColumn();
        if ($coversSix > 0) {
            return;
        }
        $this->pdo->prepare('INSERT INTO discounts (min_nights, max_nights, percent) VALUES (?, ?, ?)')
            ->execute([6, 9, 5]);
    }

    private function ensureOwnerRates(): void
    {
        $flag = $this->pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $flag->execute(['rates_pack']);
        $pack = $flag->fetchColumn();
        if ($pack === '2027-owner') {
            $seasons = (int) $this->pdo->query('SELECT COUNT(*) FROM rate_seasons')->fetchColumn();
            $discounts = (int) $this->pdo->query('SELECT COUNT(*) FROM discounts')->fetchColumn();
            if ($seasons > 0 && $discounts > 0) {
                return;
            }
        }
        $this->pdo->exec('DELETE FROM rate_seasons');
        $this->pdo->exec('DELETE FROM discounts');
        $stmt = $this->pdo->prepare(
            'INSERT INTO rate_seasons (label, year, start_month, start_day, end_month, end_day, nightly_rate, is_closed, sort_order)
             VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach (self::ownerRateRows() as $r) {
            $stmt->execute([$r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7]]);
        }
        $this->insertOwnerDiscounts();
        $this->upsert('settings', 'setting_key', [
            'setting_key' => 'rates_pack',
            'setting_value' => '2027-owner',
        ]);
        $this->pdo->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?')
            ->execute(['20', 'deposit_percent']);
    }
}
